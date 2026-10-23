<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
include_once("./Nucleo/Objeto.php");
include_once("./Nodos/NodoElectrico.php");
include_once("./Configuracion/Configuracion.php");
include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");

/**
 * Clase PerdurarSuperestructuraStringSQL
 *
 * @version 1.5piloto.82c
 *
 * @author Ignacio David Baigorria
 *
 * @notes
 * Desde v1.5piloto.82c usa mysqli_report(MYSQLI_REPORT_OFF)
 * al principio de crear_conexion_sql. En PHP 8.4, con
 * mysqli_report(MYSQLI_REPORT_STRICT) activo (default),
 * cualquier fallo de mysqli lanza excepción. Eso rompe
 * el flujo silenciosamente. Desactivar mysqli_report hace
 * que mysqli vuelva a la semántica clásica (devuelve false).
 */
class PerdurarSuperestructuraStringSQL extends Objeto implements PerdurarSuperestructura
{

    protected static string $token = '';

    protected static ?\mysqli $_sql_singleton = null;

    public static function recibir_token(string $token): void {
        static::$token = $token;
    }

    static protected function crear_conexion_sql()
    {
        // Fix 1.5piloto.82c: mysqli_report OFF. En PHP 8.4
        // mysqli lanza excepción por default en fallos. Eso
        // hace que un simple ping() o new \mysqli en fallo
        // propague una excepción hasta el guardado. Desactivarlo
        // devuelve la semántica clásica: false en fallo.
        mysqli_report(MYSQLI_REPORT_OFF);

        // Log de versión para diagnóstico (aparece en error_log).
        error_log("[CREAR_CONEXION] version=82c, singleton=" . (self::$_sql_singleton ? "vivo" : "nulo"));

        // Reusar la conexión si sigue viva.
        if (self::$_sql_singleton !== null) {
            $ping_ok = false;
            try {
                $r_ping = @self::$_sql_singleton->query("SELECT 1");
                if ($r_ping) {
                    $r_ping->free();
                    $ping_ok = true;
                }
            } catch (\Throwable $e) {
                $ping_ok = false;
            }
            if ($ping_ok) {
                return self::$_sql_singleton;
            }
            try { @self::$_sql_singleton->close(); } catch (\Throwable $e) { /* ignorar */ }
            self::$_sql_singleton = null;
        }

        // Crear la conexión.
        try {
            if (Conf::LOCAL) {
                $sql = @new \mysqli(Conf::SUPERESTRUCTURA_HOST_SQL, Conf::SUPERESTRUCTURA_USUARIO_SQL, Conf::SUPERESTRUCTURA_CONTRASENA_SQL);
            } else {
                $sql = @new \mysqli(Conf::SUPERESTRUCTURA_HOST_SQL, Conf::SUPERESTRUCTURA_USUARIO_SQL, Conf::SUPERESTRUCTURA_CONTRASENA_SQL, Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL);
            }
        } catch (\Throwable $e) {
            self::_error("excepción al crear conexión: " . $e->getMessage());
            return null;
        }

        if (!$sql || $sql->connect_errno) {
            $msg = $sql ? $sql->connect_error : "mysqli devolvió false/null";
            self::_error("no se pudo conectar a la base de datos: " . $msg);
            return null;
        }

        if (Conf::LOCAL) {
            static::crear_base_de_datos_sql($sql);
            $sql->select_db(Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL);
            static::crear_tablas_sql($sql);
        } else {
            static::crear_tablas_sql($sql);
        }

        $charset = $sql->character_set_name();
        if ($charset != "utf8mb4") {
            $sql->set_charset("utf8mb4");
        }
        @$sql->query("SET SESSION wait_timeout = 300");
        try { @$sql->rollback(); } catch (\Throwable $e) { /* ignorar */ }

        self::$_sql_singleton = $sql;
        return $sql;
    }

    static protected function _liberar_conexion_sql($sql): void {
        // No-op: la conexión es singleton. Se cierra al
        // terminar el request o cuando crear_conexion_sql
        // detecte que ya no responde.
    }

    static protected function crear_base_de_datos_sql($sql)
    {
        if ($sql->query("CREATE DATABASE IF NOT EXISTS " . Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL)) {
            return true;
        } else {
            self::_error("no se pudo crear la base de datos");
            return false;
        }
    }

    static protected function crear_tablas_sql($sql)
    {
        if (
            !$sql->query("CREATE TABLE IF NOT EXISTS nodo (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            idnodo           VARCHAR(50) NOT NULL,
                            dato             BLOB,
                            PRIMARY KEY (idsuperestructura, idnodo)
                        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        ) {
            self::_error("no se pudo crear la tabla nodo");
            return false;
        }
        if (
            !$sql->query("CREATE TABLE IF NOT EXISTS adyacente (
                            idsuperestructura VARCHAR(50) NOT NULL,
                            idnodo           VARCHAR(50) NOT NULL,
                            enlace           VARCHAR(100) NOT NULL,
                            idadyacente      VARCHAR(50) NOT NULL,
                            PRIMARY KEY (idsuperestructura, idnodo, enlace, idadyacente)
                        ) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")
        ) {
            self::_error("no se pudo crear la tabla adyacente");
            return false;
        }
        return true;
    }

    static private function crear_consulta_insertar_sql($sql, $nombre)
    {
        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            return $nodo->dato();
        }, null);
        if (empty($datos)) return '';
        $consulta = "INSERT INTO nodo (idsuperestructura, idnodo, dato) values";
        $separador = " ";
        $primero = true;
        foreach ($datos as $id => $dato) {
            if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;
            $id_escapado = mysqli_real_escape_string($sql, (string)$id);
            $dato_escapado = is_null($dato) ? '' : mysqli_real_escape_string($sql, (string)$dato);
            $consulta .= $separador . "('" . $nombre . "','" . $id_escapado . "','" . $dato_escapado . "')";
            if ($primero) { $primero = false; $separador = ", "; }
        }
        $consulta .= ";";
        return $consulta;
    }

    static private function crear_consulta_insertar2_sql($sql, $nombre)
    {
        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            return $nodo->por_cada_adyacente_ejecutar(function ($nodo) {
                return $nodo->id();
            });
        });
        if (empty($datos)) return '';
        $hubo_alguno = false;
        foreach ($datos as $arreglo) {
            if (is_array($arreglo) && !empty($arreglo)) { $hubo_alguno = true; break; }
        }
        if (!$hubo_alguno) return '';
        $consulta = 'INSERT INTO adyacente (idsuperestructura, idnodo, enlace, idadyacente) values';
        $separador = " ";
        $primero = true;
        foreach ($datos as $idnodo => $arreglo) {
            if (is_array($arreglo)) {
                foreach ($arreglo as $enlace => $idadyacente) {
                    $idnodo_escapado = mysqli_real_escape_string($sql, (string)$idnodo);
                    $enlace_escapado = mysqli_real_escape_string($sql, (string)$enlace);
                    $idadyacente_escapado = mysqli_real_escape_string($sql, (string)$idadyacente);
                    $consulta .= $separador . "('" . $nombre . "','" . $idnodo_escapado . "','" . $enlace_escapado . "','" . $idadyacente_escapado . "')";
                    if ($primero) { $primero = false; $separador = ", "; }
                }
            }
        }
        $consulta .= ';';
        return $consulta;
    }

    static public function guardar($nombre)
    {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_error("error en guardar, no existe ningun nodo en la superestructura");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("guardar: no se pudo crear la conexion");
            return false;
        }
        $sql->begin_transaction();
        try {
            $nombre_escapado = $sql->real_escape_string((string)$nombre);
            if (!$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
                throw new \RuntimeException("guardar: fallo DELETE en nodo: " . $sql->error);
            }
            if (!$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
                throw new \RuntimeException("guardar: fallo DELETE en adyacente: " . $sql->error);
            }
            $chunks_nodos = self::crear_chunks_insertar_nodos($sql, $nombre);
            foreach ($chunks_nodos as $consulta) {
                if (!$sql->query($consulta)) {
                    throw new \RuntimeException("guardar: fallo INSERT en nodo: " . $sql->error);
                }
            }
            $chunks_ady = self::crear_chunks_insertar_adyacentes($sql, $nombre);
            foreach ($chunks_ady as $consulta) {
                if (!$sql->query($consulta)) {
                    throw new \RuntimeException("guardar: fallo INSERT en adyacente: " . $sql->error);
                }
            }
            $sql->commit();
            static::_liberar_conexion_sql($sql);
            return true;
        } catch (\Throwable $e) {
            @$sql->rollback();
            self::_error($e->getMessage());
            static::_liberar_conexion_sql($sql);
            return false;
        }
    }

    static private function crear_chunks_insertar_nodos($sql, $nombre): array
    {
        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            return $nodo->dato();
        }, null);
        if (empty($datos)) return [];
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        $limite_bytes = 200 * 1024;
        $chunks = [];
        $lote = [];
        $tamano_lote = 0;
        foreach ($datos as $id => $dato) {
            if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;
            $id_escapado = $sql->real_escape_string((string)$id);
            $dato_escapado = is_null($dato) ? '' : $sql->real_escape_string((string)$dato);
            $fila = "('" . $nombre_escapado . "','" . $id_escapado . "','" . $dato_escapado . "')";
            $tamano_fila = strlen($fila) + 2;
            if (!empty($lote) && ($tamano_lote + $tamano_fila) > $limite_bytes) {
                $chunks[] = "INSERT INTO nodo (idsuperestructura, idnodo, dato) VALUES " . implode(", ", $lote) . ";";
                $lote = [];
                $tamano_lote = 0;
            }
            $lote[] = $fila;
            $tamano_lote += $tamano_fila;
        }
        if (!empty($lote)) {
            $chunks[] = "INSERT INTO nodo (idsuperestructura, idnodo, dato) VALUES " . implode(", ", $lote) . ";";
        }
        return $chunks;
    }

    static private function crear_chunks_insertar_adyacentes($sql, $nombre): array
    {
        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            $enlaces = [];
            $ady = $nodo->adyacentes();
            if (is_array($ady)) {
                foreach ($ady as $enlace => $adyacente) {
                    $enlaces[$enlace] = $adyacente->id();
                }
            }
            return $enlaces;
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
                    $chunks[] = "INSERT INTO adyacente (idsuperestructura, idnodo, enlace, idadyacente) VALUES " . implode(", ", $lote) . ";";
                    $lote = [];
                    $tamano_lote = 0;
                }
                $lote[] = $fila;
                $tamano_lote += $tamano_fila;
            }
        }
        if (!empty($lote)) {
            $chunks[] = "INSERT INTO adyacente (idsuperestructura, idnodo, enlace, idadyacente) VALUES " . implode(", ", $lote) . ";";
        }
        return $chunks;
    }

    static public function eliminar($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre), el identificador pasado como parametro no es un string");
            return null;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no se pudo crear la conexion");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
            self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) error intentado ver si la superestructura existe");
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $cant = $rcontar->fetch_assoc()['COUNT(*)'];
        $r = false;
        if ($cant > 0) {
            if (!$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
                self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) fallo DELETE en nodo: " . $sql->error);
                static::_liberar_conexion_sql($sql);
                return null;
            }
            if (!$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
                self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) fallo DELETE en adyacente: " . $sql->error);
                static::_liberar_conexion_sql($sql);
                return null;
            }
            $r = true;
        } else {
            self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no existe superestructura con ese nombre");
        }
        static::_liberar_conexion_sql($sql);
        return $r;
    }

    static public function cargar($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("PerdurarSuperestructuraString::cargar(nombre), el identificador pasado como parametro no es un string");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo crear la conexion");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$nodos = $sql->query("SELECT * FROM `nodo` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
            self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo cargar, no cargo nada");
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $nodo = $nodos->fetch_assoc();
        if (!$nodo) {
            self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");
            static::_liberar_conexion_sql($sql);
            return false;
        }
        $equivalencias = array();
        while ($nodo != null) {
            $id = $nodo["idnodo"];
            if (Nodo::es_id_especial($id)) {
                if (!$naux = Nodo::nodo_por_id($id)) {
                    Nodo::crear_con_dato_e_id($nodo["dato"], $id);
                } else {
                    $naux->_dato($nodo["dato"]);
                }
            } else {
                $idnuevo = Nodo::crear_con_dato($nodo["dato"])->id();
                $equivalencias[$id] = $idnuevo;
            }
            $nodo = $nodos->fetch_assoc();
        }
        if (!$adyacentes = $sql->query("SELECT * FROM `adyacente` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
            self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudieron cargar los adyacentes: " . $sql->error);
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $adyacente = $adyacentes->fetch_assoc();
        while ($adyacente != null) {
            $idnod = $adyacente["idnodo"];
            if (!Nodo::es_id_especial($idnod)) {
                if (!isset($equivalencias[$idnod])) {
                    self::_error("No se encontró equivalencia para idnodo=$idnod");
                    $adyacente = $adyacentes->fetch_assoc();
                    continue;
                }
                $idnod = $equivalencias[$idnod];
            }
            $nodo = Nodo::nodo_por_id($idnod);
            $idady = $adyacente["idadyacente"];
            if (!Nodo::es_id_especial($idady)) {
                if (!isset($equivalencias[$idady])) {
                    self::_error("No se encontró equivalencia para idadyacente=$idady");
                    $adyacente = $adyacentes->fetch_assoc();
                    continue;
                }
                $idady = $equivalencias[$idady];
            }
            $nodoady = Nodo::nodo_por_id($idady);
            if (!$nodo || !$nodoady) {
                self::_error("No se pudo reconstruir el enlace: idnodo=$idnod, idadyacente=$idady, enlace={$adyacente['enlace']}");
                $adyacente = $adyacentes->fetch_assoc();
                continue;
            }
            $nodo->_adyacente_en($nodoady, $adyacente["enlace"]);
            $adyacente = $adyacentes->fetch_assoc();
        }
        static::_liberar_conexion_sql($sql);
        return true;
    }

    static public function existe($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("PerdurarSuperestructuraString::existe_sql(nombre), el identificador pasado como parametro no es un string");
            return null;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo crear la conexion");
            return null;
        }
        $nombre_escapado = $sql->real_escape_string((string)$nombre);
        if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre_escapado . "';")) {
            self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo contar");
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $cant = $rcontar->fetch_assoc()['COUNT(*)'];
        static::_liberar_conexion_sql($sql);
        return $cant > 0;
    }
}
?>