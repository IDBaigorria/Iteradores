<?php
/**
 * Aplicador — Tanda C.10: sin CREATE TEMPORARY TABLE.
 *
 * Motivo: InfinityFree no le da el privilegio CREATE TEMPORARY
 * TABLES a usuarios compartidos. El guardado parcial fallaba
 * con "Access denied" al crear la tabla efímera.
 *
 * Solución: reemplazar la TEMPORARY TABLE por IN (...) directo.
 * El grafo típico tiene 1000-2000 nodos. Cada id es un int
 * (~10 bytes). Total: ~20KB. Entra cómodo en una query
 * (max_allowed_packet = 10MB). No requiere privilegios.
 *
 * Uso (raíz del proyecto): php aplicar_cambios_c10.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.82d';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [
[
    'tipo' => 'crear',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: sin TEMPORARY TABLE, IN (...) directo',
    'contenido' => [
        '<?php',
        'namespace Iteradores\\Controlador\\PerdurarSuperestructura;',
        'use Iteradores\\Nucleo\\Objeto;',
        'use Iteradores\\Nodos\\Nodo;',
        'use Iteradores\\Configuracion\\Conf;',
        'include_once("./Nucleo/Objeto.php");',
        'include_once("./Nodos/NodoElectrico.php");',
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");',
        'include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructuraConContexto.php");',
        'include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php");',
        '',
        '/**',
        ' * Persistencia SQL con soporte de contextos (hasta 64).',
        ' *',
        ' * v1.5piloto.82d: sin CREATE TEMPORARY TABLE. InfinityFree',
        ' * no permite ese privilegio. Se usa IN (...) directo.',
        ' *',
        ' * @author Ignacio David Baigorria',
        ' * @version 1.5piloto.82d',
        ' * @since 1.5i.7k',
        ' */',
        'class PerdurarSuperestructuraStringSQL64 extends PerdurarSuperestructuraStringSQL implements PerdurarSuperestructuraConContexto {',
        '',
        '    protected static array $_mapa_id_a_fila = [];',
        '',
        '    protected static function crear_tablas_sql($sql) {',
        '        if (!parent::crear_tablas_sql($sql)) {',
        '            return false;',
        '        }',
        '        if (!$sql->query("CREATE TABLE IF NOT EXISTS nodo_contexto (',
        '                            id_fila           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,',
        '                            idsuperestructura VARCHAR(50) NOT NULL,',
        '                            id_especial       VARCHAR(50) NULL,',
        '                            dato              BLOB,',
        '                            contexto_mascara  BIGINT UNSIGNED NOT NULL DEFAULT 0,',
        '                            PRIMARY KEY (id_fila),',
        '                            UNIQUE KEY uq_especial (idsuperestructura, id_especial),',
        '                            INDEX idx_super_ctx (idsuperestructura, contexto_mascara)',
        '                        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")) {',
        '            self::_error("SQL64: no se pudo crear la tabla nodo_contexto");',
        '            return false;',
        '        }',
        '        if (!$sql->query("CREATE TABLE IF NOT EXISTS adyacente_contexto (',
        '                            idsuperestructura VARCHAR(50) NOT NULL,',
        '                            idnodo_fila       BIGINT UNSIGNED NOT NULL,',
        '                            enlace            VARCHAR(100) NOT NULL,',
        '                            idadyacente_fila  BIGINT UNSIGNED NOT NULL,',
        '                            PRIMARY KEY (idsuperestructura, idnodo_fila, enlace, idadyacente_fila),',
        '                            INDEX idx_ady_dest (idsuperestructura, idadyacente_fila)',
        '                        ) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")) {',
        '            self::_error("SQL64: no se pudo crear la tabla adyacente_contexto");',
        '            return false;',
        '        }',
        '        if (!$sql->query("CREATE TABLE IF NOT EXISTS contexto (',
        '                            idsuperestructura VARCHAR(50) NOT NULL,',
        '                            bit               TINYINT UNSIGNED NOT NULL,',
        '                            nombre            VARCHAR(100) NOT NULL,',
        '                            PRIMARY KEY (idsuperestructura, bit),',
        '                            UNIQUE KEY uq_ctx_nombre (idsuperestructura, nombre)',
        '                        ) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")) {',
        '            self::_error("SQL64: no se pudo crear la tabla contexto");',
        '            return false;',
        '        }',
        '        return true;',
        '    }',
        '',
        '    static public function guardar($nombre) {',
        '        return self::_guardar_con_filtro($nombre, 0);',
        '    }',
        '',
        '    static private function _guardar_con_filtro($nombre, int $mascara_filtro, array $contextos = []) {',
        '        if (!Nodo::hay_nodos_en_superestructura()) {',
        '            self::_error("SQL64::guardar: no hay nodos.");',
        '            return false;',
        '        }',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::guardar: no se pudo crear conexión.");',
        '            return false;',
        '        }',
        '',
        '        if ($mascara_filtro === -1) {',
        '            $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);',
        '            if ($bits_registrados === null) {',
        '                static::_liberar_conexion_sql($sql);',
        '                return false;',
        '            }',
        '            $mascara_calculada = 0;',
        '            foreach ($contextos as $ctx) {',
        '                $ctx_str = (string)$ctx;',
        '                if (!isset($bits_registrados[$ctx_str])) {',
        '                    self::_error("SQL64::guardar_parcial: contexto \'" . $ctx_str . "\' no registrado en \'" . $nombre . "\'.");',
        '                    static::_liberar_conexion_sql($sql);',
        '                    return false;',
        '                }',
        '                $mascara_calculada |= (1 << (int)$bits_registrados[$ctx_str]);',
        '            }',
        '            $mascara_filtro = $mascara_calculada;',
        '        }',
        '',
        '        $mf_parcial = (int)$mascara_filtro;',
        '',
        '        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);',
        '        if ($mascaras === null) {',
        '            static::_liberar_conexion_sql($sql);',
        '            return false;',
        '        }',
        '',
        '        if ($mf_parcial !== 0) {',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {',
        '                $id = $nodo->id();',
        '                $m_previa = (int)$nodo->contexto_mascara();',
        '                $m_bfs = (int)($mascaras[$id] ?? 0);',
        '                $mascaras[$id] = $m_previa | $m_bfs;',
        '            });',
        '        }',
        '',
        '        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '',
        '        if ($mf_parcial !== 0 && !empty($mapa_previo)) {',
        '            $nuevos = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {',
        '                $id = $nodo->id();',
        '                if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }',
        '            });',
        '            if (!empty($nuevos)) {',
        '                $cambio = true;',
        '                $iter = 0;',
        '                while ($cambio && $iter < 10) {',
        '                    $cambio = false;',
        '                    $iter++;',
        '                    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {',
        '                        $id_origen = $nodo->id();',
        '                        $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                        if ($m_origen === 0) return;',
        '                        $ady = $nodo->adyacentes();',
        '                        if (!is_array($ady)) return;',
        '                        foreach ($ady as $destino) {',
        '                            $id_dest = $destino->id();',
        '                            if (!isset($nuevos[$id_dest])) continue;',
        '                            $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                            $m_nueva = $m_dest | $m_origen;',
        '                            if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }',
        '                        }',
        '                    });',
        '                }',
        '            }',
        '        }',
        '',
        '        $filas_cargadas = [];',
        '        if ($mf_parcial !== 0) {',
        '            $filas_cargadas = array_values(array_unique($mapa_previo));',
        '        }',
        '',
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
        '',
        '            if ($mf === 0) {',
        '                if (!$sql->query("DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);',
        '                }',
        '                if (!$sql->query("DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);',
        '                }',
        '                self::$_mapa_id_a_fila[$nombre] = [];',
        '                $mapa_previo = [];',
        '            } elseif (!empty($filas_cargadas)) {',
        '                // Fix 1.5piloto.82d: sin CREATE TEMPORARY TABLE.',
        '                // InfinityFree no permite ese privilegio. Usamos',
        '                // IN (...) directo con todos los ids. El grafo',
        '                // típico tiene 1000-2000 nodos, ~20KB de IDs.',
        '                // Cabe en max_allowed_packet (10MB).',
        '                $ids_in = implode(",", array_map("intval", $filas_cargadas));',
        '',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND idnodo_fila IN (" . $ids_in . ")"',
        '                           . " AND idadyacente_fila IN (" . $ids_in . ");";',
        '                if (!$sql->query($q_del_ady)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);',
        '                }',
        '                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND id_fila IN (" . $ids_in . ");";',
        '                if (!$sql->query($q_del_nod)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);',
        '                }',
        '            }',
        '',
        '            $ids_a_insertar = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) return;',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            foreach ($ids_a_insertar as $id_nodo) {',
        '                $nodo = Nodo::nodo_por_id($id_nodo);',
        '                if (!$nodo) continue;',
        '                $mascara = (int)($mascaras[$id_nodo] ?? 0);',
        '                $dato = $nodo->dato();',
        '                if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;',
        '                $dato_escapado = is_null($dato) ? "" : $sql->real_escape_string((string)$dato);',
        '                $id_especial = Nodo::es_id_especial($id_nodo) ? $sql->real_escape_string((string)$id_nodo) : null;',
        '                $id_especial_sql = is_null($id_especial) ? "NULL" : "\'" . $id_especial . "\'";',
        '                if (isset($mapa_previo[$id_nodo])) {',
        '                    $id_fila = (int)$mapa_previo[$id_nodo];',
        '                    $q = "INSERT INTO nodo_contexto (id_fila, idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . $id_fila . ", \'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                } else {',
        '                    $q = "INSERT INTO nodo_contexto (idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . "\'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                }',
        '                if (!$sql->query($q)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);',
        '                }',
        '                if (!isset($mapa_previo[$id_nodo])) {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = (int)$sql->insert_id;',
        '                } else {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];',
        '                }',
        '            }',
        '',
        '            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '            $filas_aristas = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {',
        '                $id_origen = $nodo->id();',
        '                $m_o = (int)($mascaras[$id_origen] ?? 0);',
        '                if ($mf !== 0 && ($m_o & $mf) === 0) return;',
        '                if (!isset($mapa_final[$id_origen])) return;',
        '                $fila_origen = (int)$mapa_final[$id_origen];',
        '                $ady = $nodo->adyacentes();',
        '                if (!is_array($ady)) return;',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    $m_d = (int)($mascaras[$id_dest] ?? 0);',
        '                    if ($mf !== 0 && ($m_d & $mf) === 0) continue;',
        '                    if (!isset($mapa_final[$id_dest])) continue;',
        '                    $fila_dest = (int)$mapa_final[$id_dest];',
        '                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];',
        '                }',
        '            });',
        '',
        '            if (!empty($filas_aristas)) {',
        '                $limite_bytes = 200 * 1024;',
        '                $lote = [];',
        '                $tamano = 0;',
        '                foreach ($filas_aristas as $fila_ar) {',
        '                    list($fo, $enl, $fd) = $fila_ar;',
        '                    $enl_esc = $sql->real_escape_string($enl);',
        '                    $fila_str = "(\'" . $nombre_escapado . "\'," . $fo . ",\'" . $enl_esc . "\'," . $fd . ")";',
        '                    $tam = strlen($fila_str) + 2;',
        '                    if (!empty($lote) && ($tamano + $tam) > $limite_bytes) {',
        '                        $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                        if (!$sql->query($q_ins)) {',
        '                            throw new \\RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                        }',
        '                        $lote = [];',
        '                        $tamano = 0;',
        '                    }',
        '                    $lote[] = $fila_str;',
        '                    $tamano += $tam;',
        '                }',
        '                if (!empty($lote)) {',
        '                    $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                    if (!$sql->query($q_ins)) {',
        '                        throw new \\RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                    }',
        '                }',
        '            }',
        '',
        '            if (!$sql->ping()) {',
        '                throw new \\RuntimeException("SQL64::guardar: conexión perdida antes del commit.");',
        '            }',
        '            if (!$sql->commit()) {',
        '                throw new \\RuntimeException("SQL64::guardar: commit falló: " . $sql->error);',
        '            }',
        '            static::_liberar_conexion_sql($sql);',
        '            return true;',
        '        } catch (\\Throwable $e) {',
        '            @$sql->rollback();',
        '            self::_error($e->getMessage());',
        '            static::_liberar_conexion_sql($sql);',
        '            return false;',
        '        }',
        '    }',
        '',
        '    static public function cargar($nombre): bool|null {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::cargar: el nombre no es string.");',
        '            return false;',
        '        }',
        '        self::$_mapa_id_a_fila[$nombre] = [];',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::cargar: no se pudo crear conexión.");',
        '            return null;',
        '        }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        $equivalencias = array();',
        '        $ok = self::_cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, $equivalencias, false);',
        '        if ($ok === null) { static::_liberar_conexion_sql($sql); return null; }',
        '        if ($ok === false) { static::_liberar_conexion_sql($sql); return false; }',
        '        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);',
        '        if ($ok2 === false) { static::_liberar_conexion_sql($sql); return null; }',
        '        static::_liberar_conexion_sql($sql);',
        '        return true;',
        '    }',
        '',
        '    static public function cargar_parcial($nombre, array $contextos): bool|null {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::cargar_parcial: el nombre no es string.");',
        '            return false;',
        '        }',
        '        self::$_mapa_id_a_fila[$nombre] = [];',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::cargar_parcial: no se pudo crear conexión.");',
        '            return null;',
        '        }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);',
        '        if ($bits_registrados === null) { static::_liberar_conexion_sql($sql); return null; }',
        '        $mascara_pedida = 0;',
        '        foreach ($contextos as $ctx) {',
        '            $ctx_str = (string)$ctx;',
        '            if (!isset($bits_registrados[$ctx_str])) {',
        '                self::_error("SQL64::cargar_parcial: contexto \'" . $ctx_str . "\' no registrado en \'" . $nombre . "\'.");',
        '                static::_liberar_conexion_sql($sql);',
        '                return false;',
        '            }',
        '            $mascara_pedida |= (1 << (int)$bits_registrados[$ctx_str]);',
        '        }',
        '        $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '           . " AND (contexto_mascara & " . (int)$mascara_pedida . ") != 0;";',
        '        $equivalencias = array();',
        '        $ok = self::_cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, $equivalencias, true, $q);',
        '        if ($ok === null) { static::_liberar_conexion_sql($sql); return null; }',
        '        if ($ok === false) { static::_liberar_conexion_sql($sql); return false; }',
        '        $ok2 = self::_cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias);',
        '        if ($ok2 === false) { static::_liberar_conexion_sql($sql); return null; }',
        '        static::_liberar_conexion_sql($sql);',
        '        return true;',
        '    }',
        '',
        '    static public function guardar_parcial($nombre, array $contextos): bool {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::guardar_parcial: el nombre no es string.");',
        '            return false;',
        '        }',
        '        if (empty($contextos)) {',
        '            self::_error("SQL64::guardar_parcial: no se especificaron contextos.");',
        '            return false;',
        '        }',
        '        return self::_guardar_con_filtro($nombre, -1, $contextos);',
        '    }',
        '',
        '    static public function listar_contextos($nombre): ?array {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::listar_contextos: el nombre no es string.");',
        '            return null;',
        '        }',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::listar_contextos: no se pudo crear conexión.");',
        '            return null;',
        '        }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        if (!$res = $sql->query("SELECT nombre FROM contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\' ORDER BY bit;")) {',
        '            self::_error("SQL64::listar_contextos: fallo SELECT: " . $sql->error);',
        '            static::_liberar_conexion_sql($sql);',
        '            return null;',
        '        }',
        '        $nombres = [];',
        '        $fila = $res->fetch_assoc();',
        '        while ($fila !== null) {',
        '            $nombres[] = (string)$fila["nombre"];',
        '            $fila = $res->fetch_assoc();',
        '        }',
        '        static::_liberar_conexion_sql($sql);',
        '        return $nombres;',
        '    }',
        '',
        '    static public function eliminar($nombre): bool|null {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::eliminar: el nombre no es string.");',
        '            return null;',
        '        }',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::eliminar: no se pudo crear conexión.");',
        '            return null;',
        '        }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        $qs = [',
        '            "DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';",',
        '            "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';",',
        '            "DELETE FROM contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';",',
        '        ];',
        '        foreach ($qs as $q) {',
        '            if (!$sql->query($q)) {',
        '                self::_error("SQL64::eliminar: fallo DELETE: " . $sql->error);',
        '                static::_liberar_conexion_sql($sql);',
        '                return null;',
        '            }',
        '        }',
        '        unset(self::$_mapa_id_a_fila[$nombre]);',
        '        static::_liberar_conexion_sql($sql);',
        '        return true;',
        '    }',
        '',
        '    static public function existe($nombre): bool|null {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::existe: el nombre no es string.");',
        '            return null;',
        '        }',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::existe: no se pudo crear conexión.");',
        '            return null;',
        '        }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        if (!$rcontar = $sql->query("SELECT COUNT(*) FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '            self::_error("SQL64::existe: fallo SELECT: " . $sql->error);',
        '            static::_liberar_conexion_sql($sql);',
        '            return null;',
        '        }',
        '        $cant = $rcontar->fetch_assoc()[\'COUNT(*)\'];',
        '        static::_liberar_conexion_sql($sql);',
        '        return $cant > 0;',
        '    }',
        '',
        '    private static function _cargar_contextos_registrados($sql, $nombre): ?array {',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        if (!$res = $sql->query("SELECT bit, nombre FROM contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '            self::_error("SQL64::_cargar_contextos_registrados: fallo SELECT: " . $sql->error);',
        '            return null;',
        '        }',
        '        $mapa = [];',
        '        $fila = $res->fetch_assoc();',
        '        while ($fila !== null) {',
        '            $mapa[(string)$fila["nombre"]] = (int)$fila["bit"];',
        '            $fila = $res->fetch_assoc();',
        '        }',
        '        return $mapa;',
        '    }',
        '',
        '    private static function _calcular_y_registrar_contextos($sql, $nombre): ?array {',
        '        $especiales = [];',
        '        Nodo::por_cada_nodo_ejecutar(static::$token, function($nodo) use (&$especiales) {',
        '            $id = $nodo->id();',
        '            if (!is_numeric($id)) { $especiales[] = (string)$id; }',
        '        });',
        '        sort($especiales, SORT_STRING);',
        '        $bit_por_contexto = self::_cargar_contextos_registrados($sql, $nombre);',
        '        if ($bit_por_contexto === null) return null;',
        '        $siguiente_bit = 0;',
        '        foreach ($bit_por_contexto as $b) { if ($b >= $siguiente_bit) $siguiente_bit = $b + 1; }',
        '        $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '        foreach ($especiales as $id) {',
        '            if (!isset($bit_por_contexto[$id])) {',
        '                if ($siguiente_bit >= 64) {',
        '                    self::_error("SQL64: límite de 64 contextos alcanzado.");',
        '                    return null;',
        '                }',
        '                $bit = $siguiente_bit;',
        '                $id_escapado = $sql->real_escape_string($id);',
        '                $q = "INSERT INTO contexto (idsuperestructura, bit, nombre) VALUES (\'" . $nombre_escapado . "\', " . $bit . ", \'" . $id_escapado . "\');";',
        '                if (!$sql->query($q)) {',
        '                    self::_error("SQL64: fallo INSERT contexto: " . $sql->error);',
        '                    return null;',
        '                }',
        '                $bit_por_contexto[$id] = $bit;',
        '                $siguiente_bit++;',
        '            }',
        '        }',
        '        $mascaras = [];',
        '        $cola = [];',
        '        foreach ($especiales as $id) {',
        '            $mascaras[$id] = 1 << $bit_por_contexto[$id];',
        '            $cola[] = $id;',
        '        }',
        '        while (!empty($cola)) {',
        '            $id = array_shift($cola);',
        '            if (!Nodo::existe($id)) continue;',
        '            $nodo = Nodo::nodo_por_id($id);',
        '            if (!$nodo) continue;',
        '            $adyacentes = $nodo->adyacentes();',
        '            if (!$adyacentes) continue;',
        '            foreach ($adyacentes as $enlace => $destino) {',
        '                $id_dest = $destino->id();',
        '                $mascara_actual = $mascaras[$id_dest] ?? 0;',
        '                $mascara_nueva = $mascara_actual | $mascaras[$id];',
        '                if ($mascara_nueva !== $mascara_actual) {',
        '                    $mascaras[$id_dest] = $mascara_nueva;',
        '                    $cola[] = $id_dest;',
        '                }',
        '            }',
        '        }',
        '        return $mascaras;',
        '    }',
        '',
        '    private static function _cargar_nodos_con_mascara($sql, $nombre, $nombre_escapado, &$equivalencias, bool $solo_filtrados, string $q = \'\'): bool|null {',
        '        if (!$solo_filtrados) {',
        '            $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';";',
        '        }',
        '        if (!$nodos = $sql->query($q)) {',
        '            self::_error("SQL64: fallo SELECT nodos: " . $sql->error);',
        '            return null;',
        '        }',
        '        $nodo_fila = $nodos->fetch_assoc();',
        '        if (!$solo_filtrados && !$nodo_fila) {',
        '            self::_alerta("SQL64::cargar: no existe \'" . $nombre_escapado . "\'.");',
        '            return false;',
        '        }',
        '        while ($nodo_fila !== null) {',
        '            $id_fila = (int)$nodo_fila["id_fila"];',
        '            $id_especial = $nodo_fila["id_especial"];',
        '            $mascara = (int)$nodo_fila["contexto_mascara"];',
        '            $dato = $nodo_fila["dato"];',
        '            if ($id_especial !== null && $id_especial !== \'\') {',
        '                if (!$naux = Nodo::nodo_por_id($id_especial)) {',
        '                    $naux = Nodo::crear_con_dato_e_id($dato, $id_especial);',
        '                } else {',
        '                    $naux->_dato($dato);',
        '                }',
        '                if ($naux) {',
        '                    $naux->establecer_contexto_mascara($mascara);',
        '                    self::$_mapa_id_a_fila[$nombre][$naux->id()] = $id_fila;',
        '                    $equivalencias[$id_fila] = $naux->id();',
        '                }',
        '            } else {',
        '                $nuevo = Nodo::crear_con_dato($dato);',
        '                $nuevo->establecer_contexto_mascara($mascara);',
        '                self::$_mapa_id_a_fila[$nombre][$nuevo->id()] = $id_fila;',
        '                $equivalencias[$id_fila] = $nuevo->id();',
        '            }',
        '            $nodo_fila = $nodos->fetch_assoc();',
        '        }',
        '        return true;',
        '    }',
        '',
        '    private static function _cargar_adyacentes_contexto($sql, $nombre_escapado, $equivalencias): bool|null {',
        '        if (!$adyacentes = $sql->query("SELECT idnodo_fila, enlace, idadyacente_fila FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '            self::_error("SQL64: fallo SELECT adyacentes: " . $sql->error);',
        '            return false;',
        '        }',
        '        $ady = $adyacentes->fetch_assoc();',
        '        while ($ady !== null) {',
        '            $fila_origen = (int)$ady["idnodo_fila"];',
        '            $fila_dest = (int)$ady["idadyacente_fila"];',
        '            if (!isset($equivalencias[$fila_origen]) || !isset($equivalencias[$fila_dest])) {',
        '                $ady = $adyacentes->fetch_assoc();',
        '                continue;',
        '            }',
        '            $id_origen = $equivalencias[$fila_origen];',
        '            $id_dest = $equivalencias[$fila_dest];',
        '            $nodo = Nodo::nodo_por_id($id_origen);',
        '            $nodoady = Nodo::nodo_por_id($id_dest);',
        '            if ($nodo && $nodoady) {',
        '                $nodo->_adyacente_en($nodoady, $ady["enlace"]);',
        '            }',
        '            $ady = $adyacentes->fetch_assoc();',
        '        }',
        '        return true;',
        '    }',
        '}',
        '?>',
    ],
],
];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) { $htmls_a_bumpear = ['aplicacion_GET.html']; }
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = []; $archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0; $bumps_qv = 0; $bumps_detalle = []; $info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }
    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) { $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol]; }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) { echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue; }
    echo "[OK] {$creacion['archivo']} (" . ($ya_existia ? "sobrescrito" : "creado") . ")\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) { echo "Bumps @version:    $bumps_atversion\n"; echo "Bumps ?v=:         $bumps_qv\n"; }
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";