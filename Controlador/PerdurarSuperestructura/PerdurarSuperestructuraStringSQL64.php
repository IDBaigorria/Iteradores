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
 * v1.5piloto.82d: sin CREATE TEMPORARY TABLE. InfinityFree
 * no permite ese privilegio. Se usa IN (...) directo.
 *
 * @author Ignacio David Baigorria
 * @version 1.5i.7u
 * @since 1.5i.7k
 */
class PerdurarSuperestructuraStringSQL64 extends PerdurarSuperestructuraStringSQL implements PerdurarSuperestructuraConContexto {

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

    static private function _guardar_con_filtro($nombre, int $mascara_filtro, array $contextos = []) {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_error("SQL64::guardar: no hay nodos.");
            return false;
        }
        $sql = self::crear_conexion_sql();
        if (!$sql) {
            self::_error("SQL64::guardar: no se pudo crear conexión.");
            return false;
        }

        if ($mascara_filtro === -1) {
            $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);
            if ($bits_registrados === null) {
                static::_liberar_conexion_sql($sql);
                return false;
            }
            $mascara_calculada = 0;
            foreach ($contextos as $ctx) {
                $ctx_str = (string)$ctx;
                if (!isset($bits_registrados[$ctx_str])) {
                    self::_error("SQL64::guardar_parcial: contexto '" . $ctx_str . "' no registrado en '" . $nombre . "'.");
                    static::_liberar_conexion_sql($sql);
                    return false;
                }
                $mascara_calculada |= (1 << (int)$bits_registrados[$ctx_str]);
            }
            $mascara_filtro = $mascara_calculada;
        }

        $mf_parcial = (int)$mascara_filtro;

        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);
        if ($mascaras === null) {
            static::_liberar_conexion_sql($sql);
            return false;
        }

        if ($mf_parcial !== 0) {
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {
                $id = $nodo->id();
                $m_previa = (int)$nodo->contexto_mascara();
                $m_bfs = (int)($mascaras[$id] ?? 0);
                $mascaras[$id] = $m_previa | $m_bfs;
            });
        }

        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];

        if ($mf_parcial !== 0 && !empty($mapa_previo)) {
            $nuevos = [];
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {
                $id = $nodo->id();
                if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }
            });
            if (!empty($nuevos)) {
                $cambio = true;
                $iter = 0;
                while ($cambio && $iter < 10) {
                    $cambio = false;
                    $iter++;
                    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {
                        $id_origen = $nodo->id();
                        $m_origen = (int)($mascaras[$id_origen] ?? 0);
                        if ($m_origen === 0) return;
                        $ady = $nodo->adyacentes();
                        if (!is_array($ady)) return;
                        foreach ($ady as $destino) {
                            $id_dest = $destino->id();
                            if (!isset($nuevos[$id_dest])) continue;
                            $m_dest = (int)($mascaras[$id_dest] ?? 0);
                            $m_nueva = $m_dest | $m_origen;
                            if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }
                        }
                    });
                }
            }
        }

        $filas_cargadas = [];
        if ($mf_parcial !== 0) {
            $filas_cargadas = array_values(array_unique($mapa_previo));
        }

        // Fix 1.5i.7t: pre-DELETE, leer las máscaras viejas de
        // los nodos que se van a borrar. Se usa para verificar,
        // después del INSERT, que ningún nodo perdió bits. Si un
        // nodo pierde bits (o queda con máscara 0), se aborta.
        // Solo aplica al guardado parcial: el total borra todo,
        // no hay "viejo" con qué comparar.
        $mascaras_viejas = [];
        $mapa_fila_a_id_previo = [];
        if ($mf_parcial !== 0 && !empty($filas_cargadas)) {
            $ids_in_pre = implode(",", array_map("intval", $filas_cargadas));
            $q_pre = "SELECT id_fila, contexto_mascara FROM nodo_contexto"
                   . " WHERE idsuperestructura='" . $sql->real_escape_string((string)$nombre) . "'"
                   . " AND id_fila IN (" . $ids_in_pre . ");";
            if ($res_pre = $sql->query($q_pre)) {
                $f_pre = $res_pre->fetch_assoc();
                while ($f_pre !== null) {
                    $mascaras_viejas[(int)$f_pre["id_fila"]] = (int)$f_pre["contexto_mascara"];
                    $f_pre = $res_pre->fetch_assoc();
                }
            }
            foreach ($mapa_previo as $id_n_pre => $fila_pre) {
                $mapa_fila_a_id_previo[(int)$fila_pre] = $id_n_pre;
            }
        }

        if (Conf::DEBUG_PERSISTENCIA) {
            $modo_log = ($mf_parcial !== 0) ? "parcial" : "total";
            error_log("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log
                . " nodos_en_memoria=" . count($mascaras)
                . " nodos_previos=" . count($mapa_previo)
                . " filas_cargadas=" . count($filas_cargadas)
                . " mascaras_viejas=" . count($mascaras_viejas));
        }

        $sql->begin_transaction();
        try {
            $nombre_escapado = $sql->real_escape_string((string)$nombre);
            $mf = (int)$mascara_filtro;

            if ($mf === 0) {
                if (!$sql->query("DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);
                }
                if (!$sql->query("DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "';")) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);
                }
                self::$_mapa_id_a_fila[$nombre] = [];
                $mapa_previo = [];
            } elseif (!empty($filas_cargadas)) {
                // Fix 1.5piloto.82d: sin CREATE TEMPORARY TABLE.
                // InfinityFree no permite ese privilegio. Usamos
                // IN (...) directo con todos los ids. El grafo
                // típico tiene 1000-2000 nodos, ~20KB de IDs.
                // Cabe en max_allowed_packet (10MB).
                $ids_in = implode(",", array_map("intval", $filas_cargadas));

                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND idnodo_fila IN (" . $ids_in . ")"
                           . " AND idadyacente_fila IN (" . $ids_in . ");";
                if (!$sql->query($q_del_ady)) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);
                }
                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
                           . " AND id_fila IN (" . $ids_in . ");";
                if (!$sql->query($q_del_nod)) {
                    throw new \RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);
                }
            }

            $ids_a_insertar = [];
            $nodos_mascara_0 = [];
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {
                $id = $nodo->id();
                $mascara = (int)($mascaras[$id] ?? 0);
                if ($mf !== 0 && ($mascara & $mf) === 0) {
                    if ($mascara === 0) $nodos_mascara_0[] = $id;
                    return;
                }
                $ids_a_insertar[] = $id;
            });

            if (Conf::DEBUG_PERSISTENCIA) {
                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)
                    . " nodos_mascara_0=" . count($nodos_mascara_0)
                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));
            }

            // Fix 1.5i.7t: si el guardado es parcial y no hay
            // ningún nodo a insertar, es un error. El grafo en
            // memoria no matchea el filtro. Antes se hacía INSERT
            // vacío y el DELETE había borrado todo lo que estaba
            // en DB. Ahora se aborta con rollback.
            if ($mf !== 0 && empty($ids_a_insertar)) {
                throw new \RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");
            }

            $filas_insertadas_ahora = [];
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
                    $id_fila = (int)$mapa_previo[$id_nodo];
                    $q = "INSERT INTO nodo_contexto (id_fila, idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("
                       . $id_fila . ", '" . $nombre_escapado . "', " . $id_especial_sql . ", '" . $dato_escapado . "', " . $mascara . ");";
                } else {
                    $q = "INSERT INTO nodo_contexto (idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("
                       . "'" . $nombre_escapado . "', " . $id_especial_sql . ", '" . $dato_escapado . "', " . $mascara . ");";
                }
                if (!$sql->query($q)) {
                    throw new \RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);
                }
                if (!isset($mapa_previo[$id_nodo])) {
                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = (int)$sql->insert_id;
                    $filas_insertadas_ahora[] = (int)$sql->insert_id;
                } else {
                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];
                    $filas_insertadas_ahora[] = (int)$mapa_previo[$id_nodo];
                }
            }

            // Fix 1.5i.7t: verificación de máscaras. Para cada
            // nodo que estaba en DB con máscara > 0 y sigue en
            // el grafo, la máscara nueva debe contener todos los
            // bits viejos. Si perdió alguno (o quedó con 0), el
            // guardado parcial está corrompiendo la topología.
            // Rollback.
            if ($mf_parcial !== 0 && !empty($mascaras_viejas)) {
                $perdidos = [];
                foreach ($mascaras_viejas as $id_fila_viejo => $mascara_vieja) {
                    if ($mascara_vieja === 0) continue;
                    $id_nodo_prev = $mapa_fila_a_id_previo[$id_fila_viejo] ?? null;
                    if ($id_nodo_prev === null) continue; // nodo borrado, válido
                    // Fix 1.5i.7u: si el nodo ya no está en el grafo,
                    // se destruyó legítimamente durante la request
                    // (por ejemplo, la venta_actual al confirmar).
                    // No es pérdida de bits, es borrado. Válido.
                    if (!Nodo::existe($id_nodo_prev)) continue;
                    $mascara_nueva = (int)($mascaras[$id_nodo_prev] ?? 0);
                    if (($mascara_nueva & $mascara_vieja) !== $mascara_vieja) {
                        $perdidos[] = ["id_nodo" => $id_nodo_prev, "id_fila" => $id_fila_viejo, "vieja" => $mascara_vieja, "nueva" => $mascara_nueva];
                    }
                }
                if (!empty($perdidos)) {
                    if (Conf::DEBUG_PERSISTENCIA) {
                        foreach ($perdidos as $p) {
                            error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . " id_fila=" . $p["id_fila"] . " vieja=" . $p["vieja"] . " nueva=" . $p["nueva"]);
                        }
                    }
                    throw new \RuntimeException("SQL64::guardar: " . count($perdidos) . " nodo(s) perdieron bits de máscara. Rollback.");
                }
                if (Conf::DEBUG_PERSISTENCIA) {
                    error_log("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . count($mascaras_viejas));
                }
            }

            // Fix 1.5i.7t: verificación de nodos. Todos los id_fila
            // que se insertaron deben estar en DB.
            if (!empty($filas_insertadas_ahora)) {
                $ids_in_ins = implode(",", array_map("intval", $filas_insertadas_ahora));
                $q_cnt_n = "SELECT COUNT(*) AS c FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "' AND id_fila IN (" . $ids_in_ins . ");";
                $res_cnt_n = $sql->query($q_cnt_n);
                $cnt_n = $res_cnt_n ? (int)$res_cnt_n->fetch_assoc()["c"] : -1;
                if ($cnt_n !== count($filas_insertadas_ahora)) {
                    if (Conf::DEBUG_PERSISTENCIA) {
                        error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);
                    }
                    throw new \RuntimeException("SQL64::guardar: verificación de nodos falló. Esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);
                }
                if (Conf::DEBUG_PERSISTENCIA) {
                    error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n);
                }
            }

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

            if (Conf::DEBUG_PERSISTENCIA) {
                error_log("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas));
            }

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

            // Fix 1.5i.7t: verificación de aristas. Todas las
            // aristas que se calcularon para insertar deben estar
            // en DB con ambos extremos en las filas insertadas.
            if (!empty($filas_aristas) && !empty($filas_insertadas_ahora)) {
                $ids_in_ar = implode(",", array_map("intval", $filas_insertadas_ahora));
                $q_cnt_a = "SELECT COUNT(*) AS c FROM adyacente_contexto"
                         . " WHERE idsuperestructura='" . $nombre_escapado . "'"
                         . " AND idnodo_fila IN (" . $ids_in_ar . ")"
                         . " AND idadyacente_fila IN (" . $ids_in_ar . ");";
                $res_cnt_a = $sql->query($q_cnt_a);
                $cnt_a = $res_cnt_a ? (int)$res_cnt_a->fetch_assoc()["c"] : -1;
                if ($cnt_a !== count($filas_aristas)) {
                    if (Conf::DEBUG_PERSISTENCIA) {
                        error_log("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . count($filas_aristas) . " en_db=" . $cnt_a);
                    }
                    throw new \RuntimeException("SQL64::guardar: verificación de aristas falló. Esperado=" . count($filas_aristas) . " en_db=" . $cnt_a);
                }
                if (Conf::DEBUG_PERSISTENCIA) {
                    error_log("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a);
                }
            }

            if (!$sql->ping()) {
                throw new \RuntimeException("SQL64::guardar: conexión perdida antes del commit.");
            }
            if (!$sql->commit()) {
                throw new \RuntimeException("SQL64::guardar: commit falló: " . $sql->error);
            }
            static::_liberar_conexion_sql($sql);
            return true;
        } catch (\Throwable $e) {
            @$sql->rollback();
            self::_error($e->getMessage());
            static::_liberar_conexion_sql($sql);
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
        if ($ok === null) { static::_liberar_conexion_sql($sql); return null; }
        if ($ok === false) { static::_liberar_conexion_sql($sql); return false; }
        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);
        if ($ok2 === false) { static::_liberar_conexion_sql($sql); return null; }
        static::_liberar_conexion_sql($sql);
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
        if ($bits_registrados === null) { static::_liberar_conexion_sql($sql); return null; }
        $mascara_pedida = 0;
        foreach ($contextos as $ctx) {
            $ctx_str = (string)$ctx;
            if (!isset($bits_registrados[$ctx_str])) {
                self::_error("SQL64::cargar_parcial: contexto '" . $ctx_str . "' no registrado en '" . $nombre . "'.");
                static::_liberar_conexion_sql($sql);
                return false;
            }
            $mascara_pedida |= (1 << (int)$bits_registrados[$ctx_str]);
        }
        $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura='" . $nombre_escapado . "'"
           . " AND (contexto_mascara & " . (int)$mascara_pedida . ") != 0;";
        $equivalencias = array();
        $ok = self::_cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, $equivalencias, true, $q);
        if ($ok === null) { static::_liberar_conexion_sql($sql); return null; }
        if ($ok === false) { static::_liberar_conexion_sql($sql); return false; }
        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);
        if ($ok2 === false) { static::_liberar_conexion_sql($sql); return null; }
        static::_liberar_conexion_sql($sql);
        return true;
    }

    static public function guardar_parcial($nombre, array $contextos): bool {
        if (!is_string($nombre)) {
            self::_error("SQL64::guardar_parcial: el nombre no es string.");
            return false;
        }
        if (empty($contextos)) {
            self::_error("SQL64::guardar_parcial: no se especificaron contextos.");
            return false;
        }
        return self::_guardar_con_filtro($nombre, -1, $contextos);
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
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $nombres = [];
        $fila = $res->fetch_assoc();
        while ($fila !== null) {
            $nombres[] = (string)$fila["nombre"];
            $fila = $res->fetch_assoc();
        }
        static::_liberar_conexion_sql($sql);
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
                static::_liberar_conexion_sql($sql);
                return null;
            }
        }
        unset(self::$_mapa_id_a_fila[$nombre]);
        static::_liberar_conexion_sql($sql);
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
            static::_liberar_conexion_sql($sql);
            return null;
        }
        $cant = $rcontar->fetch_assoc()['COUNT(*)'];
        static::_liberar_conexion_sql($sql);
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
            if (!is_numeric($id)) { $especiales[] = (string)$id; }
        });
        sort($especiales, SORT_STRING);
        $bit_por_contexto = self::_cargar_contextos_registrados($sql, $nombre);
        if ($bit_por_contexto === null) return null;
        $siguiente_bit = 0;
        foreach ($bit_por_contexto as $b) { if ($b >= $siguiente_bit) $siguiente_bit = $b + 1; }
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