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
 * ## Esquema (v1.5i.8a)
 *
 * `nodo_contexto` identifica cada nodo por `id_fila`
 * (auto-increment, estable entre requests). La columna
 * `id_especial` guarda el ID especial del framework cuando
 * el nodo lo tiene (NULL para los numéricos, que son
 * efímeros).
 *
 * `adyacente_contexto` referencia sus dos extremos por
 * `idnodo_fila` / `idadyacente_fila`, no por los IDs del
 * framework. Así el guardado parcial puede borrar
 * quirúrgicamente las filas del subgrafo cargado sin tocar
 * las de otros subgrafos.
 *
 * ## Mapa en memoria
 *
 * `$_mapa_id_a_fila[$nombre][$id_nodo_framework] = $id_fila`
 * se llena al cargar y se consulta al guardar parcial.
 * Resuelve el problema de los IDs numéricos efímeros:
 * aunque el framework cambie el ID de un nodo entre
 * cargas, la fila de la DB es siempre la misma.
 *
 * ## Estrategia de guardado
 *
 * En los dos modos (total y parcial) se pisa con DELETE
 * primero. Por eso NUNCA se hace UPDATE: siempre INSERT.
 * Los nodos que ya tenían `id_fila` (están en el mapa) se
 * insertan con ese `id_fila` explícito, para preservar la
 * identidad. Los nuevos van con auto-increment.
 *
 * @author Ignacio David Baigorria
 * @version 1.5i.8b
 * @since 1.5i.7k
 */
class PerdurarSuperestructuraStringSQL64 extends PerdurarSuperestructuraStringSQL implements PerdurarSuperestructuraConContexto {

    /**
     * Mapa [idsuperestructura => [id_nodo_framework => id_fila]].
     *
     * Los IDs numéricos del framework son efímeros: cambian
     * entre cargas. Los `id_fila` de la tabla son estables.
     * Este mapa los relaciona dentro de una misma request: se
     * llena al cargar y se consulta al guardar parcial.
     *
     * @var array<string, array<string, int>>
     * @since 1.5i.8a
     */
    protected static array $_mapa_id_a_fila = [];

    protected static function crear_tablas_sql($sql) {
        if (!parent::crear_tablas_sql($sql)) {
            return false;
        }
        if (!$sql->query("CREATE TABLE IF NOT EXISTS nodo_contexto (
                            id_fila           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                            idsuperestructura VARCHAR(50) NOT NULL,
                            id_especial       VARCHAR(50) NULL,
                            dato              BLOB,
                            contexto_mascara  BIGINT UNSIGNED NOT NULL DEFAULT 0,
                            PRIMARY KEY (id_fila),
                            UNIQUE KEY uq_especial (idsuperestructura, id_especial),
                            INDEX idx_super_ctx (idsuperestructura, contexto_mascara)
                        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")) {
            self::_error("SQL64: no se pudo crear la tabla nodo_contexto");
            return false;
        }
        if (!$sql->query("CREATE TABLE IF NOT EXISTS adyacente_contexto (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            idnodo_fila       BIGINT UNSIGNED NOT NULL,
                            enlace            VARCHAR(100) NOT NULL,
                            idadyacente_fila  BIGINT UNSIGNED NOT NULL,
                            PRIMARY KEY (idsuperestructura, idnodo_fila, enlace, idadyacente_fila),
                            INDEX idx_ady_dest (idsuperestructura, idadyacente_fila)
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
     * Con $mascara_filtro !== 0 hace un guardado quirúrgico
     * usando $_mapa_id_a_fila:
     *   - DELETE de aristas: solo las que tienen sus DOS
     *     extremos entre las filas cargadas.
     *   - DELETE de nodos: solo las filas cargadas.
     *   - INSERT de nodos: con id_fila explícito si estaban
     *     en el mapa, auto si son nuevos.
     *   - INSERT de aristas: solo entre nodos cargados.
     *
     * @param string $nombre
     * @param int    $mascara_filtro 0 = guardar todo.
     * @return bool
     * @since 1.5i.7o
     * @version 1.5i.8b
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

        $mf_parcial = (int)$mascara_filtro;

        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);
        if ($mascaras === null) {
            $sql->close();
            return false;
        }

        // Con filtro, unir las máscaras del BFS con las que el
        // nodo ya tenía en memoria (fijadas al cargar).
        if ($mf_parcial !== 0) {
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {
                $id = $nodo->id();
                $m_previa = (int)$nodo->contexto_mascara();
                $m_bfs = (int)($mascaras[$id] ?? 0);
                $mascaras[$id] = $m_previa | $m_bfs;
            });
        }

        // Snapshot del mapa actual (IDs del framework -> id_fila).
        // Los que están acá fueron cargados de la DB. Los que no
        // fueron creados durante esta request.
        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];

        $filas_cargadas = [];
        if ($mf_parcial !== 0) {
            $filas_cargadas = array_values(array_unique($mapa_previo));
        }

        $sql->begin_transaction();
        try {
            $nombre_escapado = $sql->real_escape_string((string)$nombre);
            $mf = (int)$mascara_filtro;

            if ($mf === 0) {
                // Guardado total: reemplaza todo.
                if (!$sql->query("DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);
                }
                if (!$sql->query("DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);
                }
                // Los id_fila viejos ya no sirven: el próximo
                // INSERT va a generar ids nuevos por auto-increment.
                self::$_mapa_id_a_fila[$nombre] = [];
                $mapa_previo = [];
            } elseif (!empty($filas_cargadas)) {
                // Guardado parcial: solo las filas cargadas.
                if (!$sql->query("CREATE TEMPORARY TABLE IF NOT EXISTS _sql64_filas (id BIGINT UNSIGNED PRIMARY KEY) ENGINE=MEMORY")) {
                    throw new \RuntimeException("SQL64::guardar: fallo CREATE TEMPORARY TABLE: " . $sql->error);
                }
                if (!$sql->query("DELETE FROM _sql64_filas")) {
                    throw new \RuntimeException("SQL64::guardar: fallo limpiar TEMPORARY TABLE: " . $sql->error);
                }
                foreach (array_chunk($filas_cargadas, 500) as $chunk) {
                    $values = [];
                    foreach ($chunk as $fid) {
                        $values[] = "(" . (int)$fid . ")";
                    }
                    if (!$sql->query("INSERT INTO _sql64_filas (id) VALUES " . implode(",", $values) . ";")) {
                        throw new \RuntimeException("SQL64::guardar: fallo INSERT TEMPORARY TABLE: " . $sql->error);
                    }
                }

                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND idnodo_fila IN (SELECT id FROM _sql64_filas)"
                           . " AND idadyacente_fila IN (SELECT id FROM _sql64_filas);";
                if (!$sql->query($q_del_ady)) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);
                }
                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND id_fila IN (SELECT id FROM _sql64_filas);";
                if (!$sql->query($q_del_nod)) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);
                }

                $sql->query("DROP TEMPORARY TABLE IF EXISTS _sql64_filas");
            }

            // INSERT de nodos: uno por uno, para capturar
            // insert_id() sin ambigüedad. Si el nodo estaba en
            // el mapa previo, se inserta con su id_fila
            // explícito (preserva identidad). Si no, auto.
            $ids_a_insertar = [];
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar) {
                $id = $nodo->id();
                $mascara = (int)($mascaras[$id] ?? 0);
                if ($mf !== 0 && ($mascara & $mf) === 0) return;
                $ids_a_insertar[] = $id;
            });

            foreach ($ids_a_insertar as $id_nodo) {
                $nodo = Nodo::nodo_por_id($id_nodo);
                if (!$nodo) continue;
                $mascara = (int)($mascaras[$id_nodo] ?? 0);
                $dato = $nodo->dato();
                if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;
                $dato_escapado = is_null($dato) ? "" : $sql->real_escape_string((string)$dato);
                $id_especial = Nodo::es_id_especial($id_nodo) ? $sql->real_escape_string((string)$id_nodo) : null;
                $id_especial_sql = is_null($id_especial) ? "NULL" : "'" . $id_especial . "'";

                if (isset($mapa_previo[$id_nodo])) {
                    // Reinsertar con el id_fila original.
                    $id_fila = (int)$mapa_previo[$id_nodo];
                    $q = "INSERT INTO nodo_contexto (id_fila, idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("
                       . $id_fila . ", '" . $nombre_escapado . "', " . $id_especial_sql . ", '" . $dato_escapado . "', " . $mascara . ");";
                } else {
                    // Nodo nuevo: auto-increment.
                    $q = "INSERT INTO nodo_contexto (idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("
                       . "'" . $nombre_escapado . "', " . $id_especial_sql . ", '" . $dato_escapado . "', " . $mascara . ");";
                }
                if (!$sql->query($q)) {
                    throw new \RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);
                }
                if (!isset($mapa_previo[$id_nodo])) {
                    $nuevo_id_fila = (int)$sql->insert_id;
                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $nuevo_id_fila;
                } else {
                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];
                }
            }

            // INSERT de aristas: solo entre nodos que están en el
            // mapa final (los cargados + los recién insertados).
            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];
            $filas_aristas = [];
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {
                $id_origen = $nodo->id();
                $m_o = (int)($mascaras[$id_origen] ?? 0);
                if ($mf !== 0 && ($m_o & $mf) === 0) return;
                if (!isset($mapa_final[$id_origen])) return;
                $fila_origen = (int)$mapa_final[$id_origen];
                $ady = $nodo->adyacentes();
                if (!is_array($ady)) return;
                foreach ($ady as $enlace => $adyacente) {
                    $id_dest = $adyacente->id();
                    $m_d = (int)($mascaras[$id_dest] ?? 0);
                    if ($mf !== 0 && ($m_d & $mf) === 0) continue;
                    if (!isset($mapa_final[$id_dest])) continue;
                    $fila_dest = (int)$mapa_final[$id_dest];
                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
                }
            });

            if (!empty($filas_aristas)) {
                $limite_bytes = 200 * 1024;
                $lote = [];
                $tamano = 0;
                foreach ($filas_aristas as $fila_ar) {
                    list($fo, $enl, $fd) = $fila_ar;
                    $enl_esc = $sql->real_escape_string($enl);
                    $fila_str = "('" . $nombre_escapado . "'," . $fo . ",'" . $enl_esc . "'," . $fd . ")";
                    $tam = strlen($fila_str) + 2;
                    if (!empty($lote) && ($tamano + $tam) > $limite_bytes) {
                        $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";
                        if (!$sql->query($q_ins)) {
                            throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);
                        }
                        $lote = [];
                        $tamano = 0;
                    }
                    $lote[] = $fila_str;
                    $tamano += $tam;
                }
                if (!empty($lote)) {
                    $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";
                    if (!$sql->query($q_ins)) {
                        throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);
                    }
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
        self::$_mapa_id_a_fila[$nombre] = [];
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::cargar: no se pudo crear conexión.");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $equivalencias = array();
        $ok = self::_cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, $equivalencias, false);
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
        self::$_mapa_id_a_fila[$nombre] = [];
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
        $ok = self::_cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, $equivalencias, true, $q);
        if ($ok === null) { $sql->close(); return null; }
        if ($ok === false) { $sql->close(); return false; }
        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);
        if ($ok2 === false) { $sql->close(); return null; }
        $sql->close();
        return true;
    }

    /**
     * Guarda solo el subgrafo en memoria filtrando por
     * contextos. Ver _guardar_con_filtro para la lógica.
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
        unset(self::$_mapa_id_a_fila[$nombre]);
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

    /**
     * Carga nodos desde nodo_contexto. Llena $_mapa_id_a_fila
     * (id_nodo_framework => id_fila) y $equivalencias
     * (id_fila => id_nodo_framework) para resolver aristas.
     *
     * @param \mysqli $sql
     * @param string  $nombre
     * @param string  $nombre_escapado
     * @param array   $equivalencias  Por referencia.
     * @param bool    $solo_filtrados
     * @param string  $q              Query cuando $solo_filtrados.
     * @return bool|null
     * @version 1.5i.8a
     */
    private static function _cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, &$equivalencias, bool $solo_filtrados, string $q = ''): bool|null {
        if (!$solo_filtrados) {
            $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';";
        }
        if (!$nodos = $sql->query($q)) {
            self::_error("SQL64: fallo SELECT nodos: " . $sql->error);
            return null;
        }
        $nodo_fila = $nodos->fetch_assoc();
        if (!$solo_filtrados && !$nodo_fila) {
            self::_alerta("SQL64::cargar: no existe '" . $nombre_escapado . "'.");
            return false;
        }
        while ($nodo_fila !== null) {
            $id_fila = (int)$nodo_fila["id_fila"];
            $id_especial = $nodo_fila["id_especial"];
            $mascara = (int)$nodo_fila["contexto_mascara"];
            $dato = $nodo_fila["dato"];
            if ($id_especial !== null && $id_especial !== '') {
                if (!$naux = Nodo::nodo_por_id($id_especial)) {
                    $naux = Nodo::crear_con_dato_e_id($dato, $id_especial);
                } else {
                    $naux->_dato($dato);
                }
                if ($naux) {
                    $naux->establecer_contexto_mascara($mascara);
                    self::$_mapa_id_a_fila[$nombre][$naux->id()] = $id_fila;
                    $equivalencias[$id_fila] = $naux->id();
                }
            } else {
                $nuevo = Nodo::crear_con_dato($dato);
                $nuevo->establecer_contexto_mascara($mascara);
                self::$_mapa_id_a_fila[$nombre][$nuevo->id()] = $id_fila;
                $equivalencias[$id_fila] = $nuevo->id();
            }
            $nodo_fila = $nodos->fetch_assoc();
        }
        return true;
    }

    /**
     * Carga aristas desde adyacente_contexto. Resuelve ambos
     * extremos por id_fila usando $equivalencias
     * (id_fila => id_nodo_framework).
     *
     * @param \mysqli $sql
     * @param string  $nombre_escapado
     * @param array   $equivalencias
     * @return bool|null
     * @version 1.5i.8a
     */
    private static function _cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias): bool|null {
        if (!$adyacentes = $sql->query("SELECT idnodo_fila, enlace, idadyacente_fila FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
            self::_error("SQL64: fallo SELECT adyacentes: " . $sql->error);
            return false;
        }
        $ady = $adyacentes->fetch_assoc();
        while ($ady !== null) {
            $fila_origen = (int)$ady["idnodo_fila"];
            $fila_dest = (int)$ady["idadyacente_fila"];
            if (!isset($equivalencias[$fila_origen]) || !isset($equivalencias[$fila_dest])) {
                $ady = $adyacentes->fetch_assoc();
                continue;
            }
            $id_origen = $equivalencias[$fila_origen];
            $id_dest = $equivalencias[$fila_dest];
            $nodo = Nodo::nodo_por_id($id_origen);
            $nodoady = Nodo::nodo_por_id($id_dest);
            if ($nodo && $nodoady) {
                $nodo->_adyacente_en($nodoady, $ady["enlace"]);
            }
            $ady = $adyacentes->fetch_assoc();
        }
        return true;
    }
}
?>