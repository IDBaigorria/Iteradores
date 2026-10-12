<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1a: guardar_parcial real en SQL64 (framework 1.5i.7o).
 *
 * Uso:
 *   php aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: bump @version a 1.5i.7o',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @version 1.5i.7k',
        ' * @since 1.5i.7k',
        ' */',
        'class PerdurarSuperestructuraStringSQL64',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @version 1.5i.7o',
        ' * @since 1.5i.7k',
        ' */',
        'class PerdurarSuperestructuraStringSQL64',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: guardar wrapper + _guardar_con_filtro',
    'buscar' => [
        '    static public function guardar($nombre) {',
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
        '        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);',
        '        if ($mascaras === null) {',
        '            $sql->close();',
        '            return false;',
        '        }',
        '',
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            if (!$sql->query("DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '                throw new \\RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);',
        '            }',
        '            if (!$sql->query("DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';")) {',
        '                throw new \\RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);',
        '            }',
        '',
        '            $chunks_nodos = self::_crear_chunks_insertar_nodos_con_mascara($sql, $nombre, $mascaras);',
        '            foreach ($chunks_nodos as $consulta) {',
        '                if (!$sql->query($consulta)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);',
        '                }',
        '            }',
        '',
        '            $chunks_ady = self::_crear_chunks_insertar_adyacentes_contexto($sql, $nombre);',
        '            foreach ($chunks_ady as $consulta) {',
        '                if (!$sql->query($consulta)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                }',
        '            }',
        '',
        '            $sql->commit();',
        '            $sql->close();',
        '            return true;',
        '        } catch (\\Throwable $e) {',
        '            $sql->rollback();',
        '            self::_error($e->getMessage());',
        '            $sql->close();',
        '            return false;',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    static public function guardar($nombre) {',
        '        return self::_guardar_con_filtro($nombre, 0);',
        '    }',
        '',
        '    /**',
        '     * Implementación común de guardar y guardar_parcial.',
        '     *',
        '     * Con $mascara_filtro === 0 reemplaza toda la',
        '     * superestructura (comportamiento de guardar).',
        '     *',
        '     * Con $mascara_filtro !== 0 solo toca los nodos cuyo',
        '     * bitmask intersecta el filtro. Regla de borrado:',
        '     * se borran las aristas salientes de esos nodos',
        '     * (idnodo in filtro) y después los nodos. Las',
        '     * aristas entrantes desde nodos fuera del filtro',
        '     * no se tocan: cada operación gestiona las',
        '     * salientes de sus nodos.',
        '     *',
        '     * El DELETE de adyacente_contexto va ANTES del de',
        '     * nodo_contexto: la subquery consulta nodo_contexto',
        '     * y tiene que ver las filas todavía.',
        '     *',
        '     * @param string $nombre',
        '     * @param int    $mascara_filtro 0 = guardar todo.',
        '     * @return bool',
        '     * @since 1.5i.7o',
        '     */',
        '    static private function _guardar_con_filtro($nombre, int $mascara_filtro) {',
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
        '        $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);',
        '        if ($mascaras === null) {',
        '            $sql->close();',
        '            return false;',
        '        }',
        '',
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
        '',
        '            // DELETE de adyacentes. Con filtro, borra las',
        '            // aristas cuyo origen está en el filtro. Sin',
        '            // filtro, borra todo.',
        '            if ($mf === 0) {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';";',
        '            } else {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND idnodo IN (SELECT idnodo FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND (contexto_mascara & " . $mf . ") != 0);";',
        '            }',
        '            if (!$sql->query($q_del_ady)) {',
        '                throw new \\RuntimeException("SQL64::guardar: fallo DELETE adyacente_contexto: " . $sql->error);',
        '            }',
        '',
        '            // DELETE de nodos. Con filtro, solo los del filtro.',
        '            if ($mf === 0) {',
        '                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';";',
        '            } else {',
        '                $q_del_nod = "DELETE FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND (contexto_mascara & " . $mf . ") != 0;";',
        '            }',
        '            if (!$sql->query($q_del_nod)) {',
        '                throw new \\RuntimeException("SQL64::guardar: fallo DELETE nodo_contexto: " . $sql->error);',
        '            }',
        '',
        '            $chunks_nodos = self::_crear_chunks_insertar_nodos_con_mascara($sql, $nombre, $mascaras, $mf);',
        '            foreach ($chunks_nodos as $consulta) {',
        '                if (!$sql->query($consulta)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);',
        '                }',
        '            }',
        '',
        '            $chunks_ady = self::_crear_chunks_insertar_adyacentes_contexto($sql, $nombre, $mascaras, $mf);',
        '            foreach ($chunks_ady as $consulta) {',
        '                if (!$sql->query($consulta)) {',
        '                    throw new \\RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                }',
        '            }',
        '',
        '            $sql->commit();',
        '            $sql->close();',
        '            return true;',
        '        } catch (\\Throwable $e) {',
        '            $sql->rollback();',
        '            self::_error($e->getMessage());',
        '            $sql->close();',
        '            return false;',
        '        }',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: guardar_parcial implementado',
    'buscar' => [
        '    static public function guardar_parcial($nombre, array $contextos): bool {',
        '        self::_error("SQL64::guardar_parcial: aún no implementado (fase 1).");',
        '        return false;',
        '    }',
    ],
    'reemplazar' => [
        '    /**',
        '     * Guarda solo el subgrafo en memoria filtrando por',
        '     * contextos.',
        '     *',
        '     * Requiere que los contextos estén registrados',
        '     * previamente. Los registra el primer guardar (que',
        '     * recorre los IDs especiales del grafo y asigna bits',
        '     * en la tabla `contexto`).',
        '     *',
        '     * El guardado es total sobre los nodos cargados',
        '     * (modificados o no): el contrato es "persistir lo que',
        '     * tengo en memoria, restringido al filtro de',
        '     * contextos". No hace delta de cambios.',
        '     *',
        '     * @param string   $nombre',
        '     * @param string[] $contextos',
        '     * @return bool',
        '     * @since 1.5i.7o',
        '     */',
        '    static public function guardar_parcial($nombre, array $contextos): bool {',
        '        if (!is_string($nombre)) {',
        '            self::_error("SQL64::guardar_parcial: el nombre no es string.");',
        '            return false;',
        '        }',
        '        if (empty($contextos)) {',
        '            self::_error("SQL64::guardar_parcial: no se especificaron contextos.");',
        '            return false;',
        '        }',
        '',
        '        $sql = self::crear_conexion_sql();',
        '        if (!$sql) {',
        '            self::_error("SQL64::guardar_parcial: no se pudo crear conexión.");',
        '            return false;',
        '        }',
        '        $bits_registrados = self::_cargar_contextos_registrados($sql, $nombre);',
        '        if ($bits_registrados === null) {',
        '            $sql->close();',
        '            return false;',
        '        }',
        '',
        '        $mascara_pedida = 0;',
        '        foreach ($contextos as $ctx) {',
        '            $ctx_str = (string)$ctx;',
        '            if (!isset($bits_registrados[$ctx_str])) {',
        '                self::_error("SQL64::guardar_parcial: contexto \'" . $ctx_str . "\' no registrado en \'" . $nombre . "\'.");',
        '                $sql->close();',
        '                return false;',
        '            }',
        '            $mascara_pedida |= (1 << (int)$bits_registrados[$ctx_str]);',
        '        }',
        '        $sql->close();',
        '',
        '        return self::_guardar_con_filtro($nombre, $mascara_pedida);',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: _crear_chunks_insertar_nodos_con_mascara con filtro',
    'buscar' => [
        '    private static function _crear_chunks_insertar_nodos_con_mascara($sql, $nombre, array $mascaras): array {',
        '        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras) {',
        '            $id = $nodo->id();',
        '            return [\'dato\' => $nodo->dato(), \'mascara\' => $mascaras[$id] ?? 0];',
        '        });',
        '        if (empty($datos)) return [];',
    ],
    'reemplazar' => [
        '    private static function _crear_chunks_insertar_nodos_con_mascara($sql, $nombre, array $mascaras, int $mascara_filtro = 0): array {',
        '        $datos = [];',
        '        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mascara_filtro, &$datos) {',
        '            $id = $nodo->id();',
        '            $mascara = $mascaras[$id] ?? 0;',
        '            if ($mascara_filtro !== 0 && ((int)$mascara & $mascara_filtro) === 0) {',
        '                return;',
        '            }',
        '            $datos[$id] = [\'dato\' => $nodo->dato(), \'mascara\' => $mascara];',
        '        });',
        '        if (empty($datos)) return [];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: _crear_chunks_insertar_adyacentes_contexto con filtro',
    'buscar' => [
        '    private static function _crear_chunks_insertar_adyacentes_contexto($sql, $nombre): array {',
        '        $datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {',
        '            $enlaces = [];',
        '            $ady = $nodo->adyacentes();',
        '            if (is_array($ady)) {',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $enlaces[$enlace] = $adyacente->id();',
        '                }',
        '            }',
        '            return $enlaces;',
        '        });',
        '        if (empty($datos)) return [];',
    ],
    'reemplazar' => [
        '    private static function _crear_chunks_insertar_adyacentes_contexto($sql, $nombre, array $mascaras = [], int $mascara_filtro = 0): array {',
        '        $datos = [];',
        '        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mascara_filtro, &$datos) {',
        '            $id_origen = $nodo->id();',
        '            if ($mascara_filtro !== 0) {',
        '                $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                if (($m_origen & $mascara_filtro) === 0) return;',
        '            }',
        '            $enlaces = [];',
        '            $ady = $nodo->adyacentes();',
        '            if (is_array($ady)) {',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    if ($mascara_filtro !== 0) {',
        '                        $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                        if (($m_dest & $mascara_filtro) === 0) continue;',
        '                    }',
        '                    $enlaces[$enlace] = $id_dest;',
        '                }',
        '            }',
        '            if (!empty($enlaces)) {',
        '                $datos[$id_origen] = $enlaces;',
        '            }',
        '        });',
        '        if (empty($datos)) return [];',
    ],
],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";