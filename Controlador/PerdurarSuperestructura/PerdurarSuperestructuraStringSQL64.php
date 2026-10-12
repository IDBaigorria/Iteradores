<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;
include_once("./Nucleo/Objeto.php");
include_once("./Nodos/NodoElectrico.php");
include_once("./Configuracion/Configuracion.php");
include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");
include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructuraConContexto.php");
include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php");

/**
 * Persistencia SQL con soporte de contextos (hasta 64).
 *
 * Hereda de PerdurarSuperestructuraStringSQL. Usa tres
 * tablas nuevas (nodo_contexto, adyacente_contexto,
 * contexto) y deja las tablas nodo/adyacente intactas, así
 * los dos métodos (SQL y SQL64) coexisten sin pisarse.
 *
 * Los contextos son los IDs especiales del grafo. Cada
 * nodo lleva un entero `contexto_mascara` con los bits de
 * sus contextos alcanzantes.
 *
 * @author Ignacio David Baigorria
 * @version 1.5i.7o
 * @since 1.5i.7k
 */
class PerdurarSuperestructuraStringSQL64 extends PerdurarSuperestructuraStringSQL implements PerdurarSuperestructuraConContexto {

    protected static function crear_tablas_sql($sql) {
        if (!parent::crear_tablas_sql($sql)) {
            return false;
        }
        if (!$sql->query("CREATE TABLE IF NOT EXISTS nodo_contexto (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            idnodo            VARCHAR(50) NOT NULL,
                            dato              BLOB,
                            contexto_mascara  BIGINT UNSIGNED NOT NULL DEFAULT 0,
                            PRIMARY KEY (idsuperestructura, idnodo),
                            INDEX idx_nodo_contexto_super_ctx (idsuperestructura, contexto_mascara)
                        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")) {
            self::_error("SQL64: no se pudo crear la tabla nodo_contexto");
            return false;
        }
        if (!$sql->query("CREATE TABLE IF NOT EXISTS adyacente_contexto (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            idnodo            VARCHAR(50) NOT NULL,
                            enlace            VARCHAR(100) NOT NULL,
                            idadyacente       VARCHAR(50) NOT NULL,
                            PRIMARY KEY (idsuperestructura, idnodo, enlace, idadyacente)
                        ) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")) {
            self::_error("SQL64: no se pudo crear la tabla adyacente_contexto");
            return false;
        }
        if (!$sql->query("CREATE TABLE IF NOT EXISTS contexto (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            bit               TINYINT UNSIGNED NOT NULL,
                            nombre            VARCHAR(100) NOT NULL,
                            PRIMARY KEY (idsuperestructura, bit),
                            UNIQUE KEY uq_ctx_nombre (idsuperestructura, nombre)
                        ) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")) {
            self::_error("SQL64: no se pudo crear la tabla contexto");
            return false;
        }
        return true;
    }

    static public function guardar($nombre) {
        return self::_guardar_con_filtro($nombre, 0);
    }

    /**
     * Implementación común de guardar y guardar_parcial.
     *
     * Con $mascara_filtro === 0 reemplaza toda la
     * superestructura (comportamiento de guardar).
     *
     * Con $mascara_filtro !== 0 solo toca los nodos cuyo
     * bitmask intersecta el filtro. Regla de borrado:
     * se borran las aristas salientes de esos nodos
     * (idnodo in filtro) y después los nodos. Las
     * aristas entrantes desde nodos fuera del filtro
     * no se tocan: cada operación gestiona las
     * salientes de sus nodos.
     *
     * El DELETE de adyacente_contexto va ANTES del de
     * nodo_contexto: la subquery consulta nodo_contexto
     * y tiene que ver las filas todavía.
     *
     * @param string $nombre
     * @param int    $mascara_filtro 0 = guardar todo.
     * @return bool
     * @since 1.5i.7o
     */
    static private function _guardar_con_filtro($nombre, int $mascara_filtro) {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_error("SQL64::guardar: no hay nodos.");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::guardar: no se pudo crear conexión.");
            return false;
        }

        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);
        if ($mascaras === null) {
            $sql->close();
            return false;
        }

        $sql->begin_transaction();
        try {
            $nombre_escapado = $sql->real_escape_string((string)$nombre);
            $mf = (int)$mascara_filtro;

            // DELETE de adyacentes. Con filtro, borra las
            // aristas cuyo origen está en el filtro. Sin
            // filtro, borra todo.
            if ($mf === 0) {
                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';";
            } else {
                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND idnodo IN (SELECT idnodo FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND (contexto_mascara & " . $mf . ") != 0);";
            }
            if (!$sql->query($q_del_ady)) {
                throw new \RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);
            }

            // DELETE de nodos. Con filtro, solo los del filtro.
            if ($mf === 0) {
                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';";
            } else {
                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND (contexto_mascara & " . $mf . ") != 0;";
            }
            if (!$sql->query($q_del_nod)) {
                throw new \RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);
            }

            $chunks_nodos = self::_crear_chunks_insertar_nodos_con_mascara($sql, $nombre, $mascaras, $mf);
            foreach ($chunks_nodos as $consulta) {
                if (!$sql->query($consulta)) {
                    throw new \RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);
                }
            }

            $chunks_ady = self::_crear_chunks_insertar_adyacentes_contexto($sql, $nombre, $mascaras, $mf);
            foreach ($chunks_ady as $consulta) {
                if (!$sql->query($consulta)) {
                    throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);
                }
            }

            $sql->commit();
            $sql->close();
            return true;
        } catch (\Throwable $e) {
            $sql->rollback();
            self::_error($e->getMessage());
            $sql->close();
            return false;
        }
    }

    static public function cargar($nombre): bool|null {
        if (!is_string($nombre)) {
            self::_error("SQL64::cargar: el nombre no es string.");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::cargar: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $equivalencias = array();
        $ok = self::_cargar_nodos_con_mascara($sql, $nombre_escapado, $equivalencias, false);
        if ($ok === null) { $sql->close(); return null; }
        if ($ok === false) { $sql->close(); return false; }
        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);
        if ($ok2 === false) { $sql->close(); return null; }
        $sql->close();
        return true;
    }

    static public function cargar_parcial($nombre, array $contextos): bool|null {
        if (!is_string($nombre)) {
            self::_error("SQL64::cargar_parcial: el nombre no es string.");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::cargar_parcial: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);

        $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);
        if ($bits_registrados === null) {
            $sql->close();
            return null;
        }
        $mascara_pedida = 0;
        foreach ($contextos as $ctx) {
            $ctx_str = (string)$ctx;
            if (!isset($bits_registrados[$ctx_str])) {
                self::_error("SQL64::cargar_parcial: contexto '" . $ctx_str . "' no registrado en '" . $nombre . "'.");
                $sql->close();
                return false;
            }
            $mascara_pedida |= (1 << (int)$bits_registrados[$ctx_str]);
        }

        $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
           . " AND (contexto_mascara & " . (int)$mascara_pedida . ") != 0;";
        $equivalencias = array();
        $ok = self::_cargar_nodos_con_mascara($sql, $nombre_escapado, $equivalencias, true, $q);
        if ($ok === null) { $sql->close(); return null; }
        if ($ok === false) { $sql->close(); return false; }
        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);
        if ($ok2 === false) { $sql->close(); return null; }
        $sql->close();
        return true;
    }

    /**
     * Guarda solo el subgrafo en memoria filtrando por
     * contextos.
     *
     * Requiere que los contextos estén registrados
     * previamente. Los registra el primer guardar (que
     * recorre los IDs especiales del grafo y asigna bits
     * en la tabla `contexto`).
     *
     * El guardado es total sobre los nodos cargados
     * (modificados o no): el contrato es "persistir lo que
     * tengo en memoria, restringido al filtro de
     * contextos". No hace delta de cambios.
     *
     * @param string   $nombre
     * @param string[] $contextos
     * @return bool
     * @since 1.5i.7o
     */
    static public function guardar_parcial($nombre, array $contextos): bool {
        if (!is_string($nombre)) {
            self::_error("SQL64::guardar_parcial: el nombre no es string.");
            return false;
        }
        if (empty($contextos)) {
            self::_error("SQL64::guardar_parcial: no se especificaron contextos.");
            return false;
        }

        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::guardar_parcial: no se pudo crear conexión.");
            return false;
        }
        $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);
        if ($bits_registrados === null) {
            $sql->close();
            return false;
        }

        $mascara_pedida = 0;
        foreach ($contextos as $ctx) {
            $ctx_str = (string)$ctx;
            if (!isset($bits_registrados[$ctx_str])) {
                self::_error("SQL64::guardar_parcial: contexto '" . $ctx_str . "' no registrado en '" . $nombre . "'.");
                $sql->close();
                return false;
            }
            $mascara_pedida |= (1 << (int)$bits_registrados[$ctx_str]);
        }
        $sql->close();

        return self::_guardar_con_filtro($nombre, $mascara_pedida);
    }

    static public function listar_contextos($nombre): ?array {
        if (!is_string($nombre)) {
            self::_error("SQL64::listar_contextos: el nombre no es string.");
            return null;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::listar_contextos: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$res = $sql->query("SELECT nombre FROM contexto WHERE idsuperestructura='" . $nombre_escapado . "' ORDER BY bit;")) {
            self::_error("SQL64::listar_contextos: fallo SELECT: " . $sql->error);
            $sql->close();
            return null;
        }
        $nombres = [];
        $fila = $res->fetch_assoc();
        while ($fila !== null) {
            $nombres[] = (string)$fila["nombre"];
            $fila = $res->fetch_assoc();
        }
        $sql->close();
        return $nombres;
    }

    static public function eliminar($nombre): bool|null {
        if (!is_string($nombre)) {
            self::_error("SQL64::eliminar: el nombre no es string.");
            return null;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::eliminar: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $qs = [
            "DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';",
            "DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';",
            "DELETE FROM contexto WHERE idsuperestructura='" . $nombre_escapado . "';",
        ];
        foreach ($qs as $q) {
            if (!$sql->query($q)) {
                self::_error("SQL64::eliminar: fallo DELETE: " . $sql->error);
                $sql->close();
                return null;
            }
        }
        $sql->close();
        return true;
    }

    static public function existe($nombre): bool|null {
        if (!is_string($nombre)) {
            self::_error("SQL64::existe: el nombre no es string.");
            return null;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::existe: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$rcontar = $sql->query("SELECT COUNT(*) FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
            self::_error("SQL64::existe: fallo SELECT: " . $sql->error);
            $sql->close();
            return null;
        }
        $cant = $rcontar->fetch_assoc()['COUNT(*)'];
        $sql->close();
        return $cant > 0;
    }

    private static function _cargar_contextos_registrados($sql, $nombre): ?array {
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$res = $sql->query("SELECT bit, nombre FROM contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
            self::_error("SQL64::_cargar_contextos_registrados: fallo SELECT: " . $sql->error);
            return null;
        }
        $mapa = [];
        $fila = $res->fetch_assoc();
        while ($fila !== null) {
            $mapa[(string)$fila["nombre"]] = (int)$fila["bit"];
            $fila = $res->fetch_assoc();
        }
        return $mapa;
    }

    private static function _calcular_y_registrar_contextos($sql, $nombre): ?array {
        $especiales = [];
        Nodo::por_cada_nodo_ejecutar(static::$token, function($nodo) use (&$especiales) {
            $id = $nodo->id();
            if (!is_numeric($id)) {
                $especiales[] = (string)$id;
            }
        });
        sort($especiales, SORT_STRING);

        $bit_por_contexto = self::_cargar_contextos_registrados($sql, $nombre);
        if ($bit_por_contexto === null) return null;
        $siguiente_bit = 0;
        foreach ($bit_por_contexto as $b) {
            if ($b >= $siguiente_bit) $siguiente_bit = $b + 1;
        }

        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        foreach ($especiales as $id) {
            if (!isset($bit_por_contexto[$id])) {
                if ($siguiente_bit >= 64) {
                    self::_error("SQL64: límite de 64 contextos alcanzado.");
                    return null;
                }
                $bit = $siguiente_bit;
                $id_escapado = $sql->real_escape_string($id);
                $q = "INSERT INTO contexto (idsuperestructura, bit, nombre) VALUES ('" . $nombre_escapado . "', " . $bit . ", '" . $id_escapado . "');";
                if (!$sql->query($q)) {
                    self::_error("SQL64: fallo INSERT contexto: " . $sql->error);
                    return null;
                }
                $bit_por_contexto[$id] = $bit;
                $siguiente_bit++;
            }
        }

        $mascaras = [];
        $cola = [];
        foreach ($especiales as $id) {
            $mascaras[$id] = 1 << $bit_por_contexto[$id];
            $cola[] = $id;
        }
        while (!empty($cola)) {
            $id = array_shift($cola);
            if (!Nodo::existe($id)) continue;
            $nodo = Nodo::nodo_por_id($id);
            if (!$nodo) continue;
            $adyacentes = $nodo->adyacentes();
            if (!$adyacentes) continue;
            foreach ($adyacentes as $enlace => $destino) {
                $id_dest = $destino->id();
                $mascara_actual = $mascaras[$id_dest] ?? 0;
                $mascara_nueva = $mascara_actual | $mascaras[$id];
                if ($mascara_nueva !== $mascara_actual) {
                    $mascaras[$id_dest] = $mascara_nueva;
                    $cola[] = $id_dest;
                }
            }
        }
        return $mascaras;
    }

    private static function _crear_chunks_insertar_nodos_con_mascara($sql, $nombre, array $mascaras, int $mascara_filtro = 0): array {
        $datos = [];
        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mascara_filtro, &$datos) {
            $id = $nodo->id();
            $mascara = $mascaras[$id] ?? 0;
            if ($mascara_filtro !== 0 && ((int)$mascara & $mascara_filtro) === 0) {
                return;
            }
            $datos[$id] = ['dato' => $nodo->dato(), 'mascara' => $mascara];
        });
        if (empty($datos)) return [];

        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $limite_bytes = 200 * 1024;
        $chunks = [];
        $lote = [];
        $tamano_lote = 0;
        foreach ($datos as $id => $info) {
            $dato = $info['dato'];
            if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;
            $id_escapado = $sql->real_escape_string((string)$id);
            $dato_escapado = is_null($dato) ? '' : $sql->real_escape_string((string)$dato);
            $mascara = (int)$info['mascara'];
            $fila = "('" . $nombre_escapado . "','" . $id_escapado . "','" . $dato_escapado . "'," . $mascara . ")";
            $tamano_fila = strlen($fila) + 2;
            if (!empty($lote) && ($tamano_lote + $tamano_fila) > $limite_bytes) {
                $chunks[] = "INSERT INTO nodo_contexto (idsuperestructura, idnodo, dato, contexto_mascara) VALUES " . implode(", ", $lote) . ";";
                $lote = [];
                $tamano_lote = 0;
            }
            $lote[] = $fila;
            $tamano_lote += $tamano_fila;
        }
        if (!empty($lote)) {
            $chunks[] = "INSERT INTO nodo_contexto (idsuperestructura, idnodo, dato, contexto_mascara) VALUES " . implode(", ", $lote) . ";";
        }
        return $chunks;
    }

    private static function _crear_chunks_insertar_adyacentes_contexto($sql, $nombre, array $mascaras = [], int $mascara_filtro = 0): array {
        $datos = [];
        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mascara_filtro, &$datos) {
            $id_origen = $nodo->id();
            if ($mascara_filtro !== 0) {
                $m_origen = (int)($mascaras[$id_origen] ?? 0);
                if (($m_origen & $mascara_filtro) === 0) return;
            }
            $enlaces = [];
            $ady = $nodo->adyacentes();
            if (is_array($ady)) {
                foreach ($ady as $enlace => $adyacente) {
                    $id_dest = $adyacente->id();
                    if ($mascara_filtro !== 0) {
                        $m_dest = (int)($mascaras[$id_dest] ?? 0);
                        if (($m_dest & $mascara_filtro) === 0) continue;
                    }
                    $enlaces[$enlace] = $id_dest;
                }
            }
            if (!empty($enlaces)) {
                $datos[$id_origen] = $enlaces;
            }
        });
        if (empty($datos)) return [];

        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $limite_bytes = 200 * 1024;
        $chunks = [];
        $lote = [];
        $tamano_lote = 0;
        foreach ($datos as $idnodo => $arreglo) {
            if (!is_array($arreglo) || empty($arreglo)) continue;
            foreach ($arreglo as $enlace => $idady) {
                $idnodo_escapado = $sql->real_escape_string((string)$idnodo);
                $enlace_escapado = $sql->real_escape_string((string)$enlace);
                $idady_escapado = $sql->real_escape_string((string)$idady);
                $fila = "('" . $nombre_escapado . "','" . $idnodo_escapado . "','" . $enlace_escapado . "','" . $idady_escapado . "')";
                $tamano_fila = strlen($fila) + 2;
                if (!empty($lote) && ($tamano_lote + $tamano_fila) > $limite_bytes) {
                    $chunks[] = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo, enlace, idadyacente) VALUES " . implode(", ", $lote) . ";";
                    $lote = [];
                    $tamano_lote = 0;
                }
                $lote[] = $fila;
                $tamano_lote += $tamano_fila;
            }
        }
        if (!empty($lote)) {
            $chunks[] = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo, enlace, idadyacente) VALUES " . implode(", ", $lote) . ";";
        }
        return $chunks;
    }

    private static function _cargar_nodos_con_mascara($sql, $nombre_escapado, &$equivalencias, bool $solo_filtrados, string $q = ''): bool|null {
        if (!$solo_filtrados) {
            $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';";
        }
        if (!$nodos = $sql->query($q)) {
            self::_error("SQL64: fallo SELECT nodos: " . $sql->error);
            return null;
        }
        $nodo = $nodos->fetch_assoc();
        if (!$solo_filtrados && !$nodo) {
            self::_alerta("SQL64::cargar: no existe '" . $nombre_escapado . "'.");
            return false;
        }
        while ($nodo !== null) {
            $id = $nodo["idnodo"];
            $mascara = (int)$nodo["contexto_mascara"];
            if (Nodo::es_id_especial($id)) {
                if (!$naux = Nodo::nodo_por_id($id)) {
                    $naux = Nodo::crear_con_dato_e_id($nodo["dato"], $id);
                } else {
                    $naux->_dato($nodo["dato"]);
                }
                if ($naux) $naux->establecer_contexto_mascara($mascara);
            } else {
                $nuevo = Nodo::crear_con_dato($nodo["dato"]);
                $nuevo->establecer_contexto_mascara($mascara);
                $equivalencias[$id] = $nuevo->id();
            }
            $nodo = $nodos->fetch_assoc();
        }
        return true;
    }

    private static function _cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias): bool|null {
        if (!$adyacentes = $sql->query("SELECT * FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
            self::_error("SQL64: fallo SELECT adyacentes: " . $sql->error);
            return false;
        }
        $adyacente = $adyacentes->fetch_assoc();
        while ($adyacente !== null) {
            $idnod = $adyacente["idnodo"];
            if (!Nodo::es_id_especial($idnod)) {
                if (!isset($equivalencias[$idnod])) { $adyacente = $adyacentes->fetch_assoc(); continue; }
                $idnod = $equivalencias[$idnod];
            }
            $idady = $adyacente["idadyacente"];
            if (!Nodo::es_id_especial($idady)) {
                if (!isset($equivalencias[$idady])) { $adyacente = $adyacentes->fetch_assoc(); continue; }
                $idady = $equivalencias[$idady];
            }
            $nodo = Nodo::nodo_por_id($idnod);
            $nodoady = Nodo::nodo_por_id($idady);
            if ($nodo && $nodoady) {
                $nodo->_adyacente_en($nodoady, $adyacente["enlace"]);
            }
            $adyacente = $adyacentes->fetch_assoc();
        }
        return true;
    }
}
?>