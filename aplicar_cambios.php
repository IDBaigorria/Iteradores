<?php
/**
 * Aplicador — Framework 1.5i.7x: GET_LOCK por superestructura
 * + usuario actual en el log de persistencia.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5i.7x';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. SQL64: lock antes de begin_transaction
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: GET_LOCK antes de begin_transaction',
    'buscar' => [
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
    ],
    'reemplazar' => [
        '        // Fix 1.5i.7x: GET_LOCK por superestructura. Evita que dos',
        '        // requests concurrentes hagan DELETE + INSERT al mismo',
        '        // tiempo y se pisen entre sí (duplicados / id_fila inestable).',
        '        // Timeout 5s. Si no se obtiene, abortar con error (los',
        '        // llamadores del piloto ya chequean guardar_ambos desde v83g).',
        '        $nombre_lock = \'grafo_\' . substr((string)$nombre, 0, 50);',
        '        $nombre_lock_esc = $sql->real_escape_string($nombre_lock);',
        '        $res_lock = $sql->query("SELECT GET_LOCK(\'" . $nombre_lock_esc . "\', 5) AS l");',
        '        $lock_ok = false;',
        '        if ($res_lock) {',
        '            $f_lock = $res_lock->fetch_assoc();',
        '            $lock_ok = (isset($f_lock[\'l\']) && (int)$f_lock[\'l\'] === 1);',
        '        }',
        '        if (!$lock_ok) {',
        '            self::_error("SQL64::guardar: no se pudo obtener el lock \'" . $nombre_lock . "\' (otro request está guardando).");',
        '            static::_liberar_conexion_sql($sql);',
        '            return false;',
        '        }',
        '',
        '        try {',
        '            if (!$sql->begin_transaction()) {',
        '                throw new \\RuntimeException("SQL64::guardar: fallo begin_transaction: " . $sql->error);',
        '            }',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
    ],
],

// ============================================================
// 2. SQL64: liberar lock en éxito y en error
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: RELEASE_LOCK en éxito y error',
    'buscar' => [
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
    ],
    'reemplazar' => [
        '            if (!$sql->commit()) {',
        '                throw new \\RuntimeException("SQL64::guardar: commit falló: " . $sql->error);',
        '            }',
        '            // Fix 1.5i.7x: liberar el lock en el camino de éxito.',
        '            @$sql->query("SELECT RELEASE_LOCK(\'" . $nombre_lock_esc . "\')");',
        '            static::_liberar_conexion_sql($sql);',
        '            return true;',
        '        } catch (\\Throwable $e) {',
        '            @$sql->rollback();',
        '            // Fix 1.5i.7x: liberar el lock en el camino de error.',
        '            @$sql->query("SELECT RELEASE_LOCK(\'" . $nombre_lock_esc . "\')");',
        '            self::_error($e->getMessage());',
        '            static::_liberar_conexion_sql($sql);',
        '            return false;',
        '        }',
    ],
],

// ============================================================
// 3. LogPersistencia: usuario actual
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Configuracion/LogPersistencia.php',
    'descripcion' => 'LogPersistencia: usuario actual (global + setter + getter)',
    'buscar' => [
        'require_once __DIR__ . \'/Configuracion.php\';',
        '',
        '/**',
        ' * Escribe un mensaje al log de persistencia del proyecto.',
    ],
    'reemplazar' => [
        'require_once __DIR__ . \'/Configuracion.php\';',
        '',
        '// Fix 1.5i.7x: usuario actual del request. Lo setea el piloto',
        '// (en `validar_token_sesion` y en el login) y se incluye en',
        '// cada línea del log para saber quién disparó el guardado.',
        '// Si no se setea, el tag es `[user=-]`.',
        '$GLOBALS[\'__log_persistencia_usuario\'] = null;',
        '',
        '/**',
        ' * Establece el usuario actual para los logs de persistencia.',
        ' *',
        ' * @param string|null $nombre Nombre de usuario (null para limpiar).',
        ' * @return void',
        ' * @since 1.5i.7x',
        ' */',
        'function log_persistencia_establecer_usuario(?string $nombre): void {',
        '    $GLOBALS[\'__log_persistencia_usuario\'] = $nombre;',
        '}',
        '',
        '/**',
        ' * Devuelve el usuario actual del log de persistencia (o null).',
        ' *',
        ' * @return string|null',
        ' * @since 1.5i.7x',
        ' */',
        'function log_persistencia_usuario_actual(): ?string {',
        '    return $GLOBALS[\'__log_persistencia_usuario\'] ?? null;',
        '}',
        '',
        '/**',
        ' * Escribe un mensaje al log de persistencia del proyecto.',
    ],
],

// ============================================================
// 4. LogPersistencia: tag [user=X] en cada línea
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Configuracion/LogPersistencia.php',
    'descripcion' => 'LogPersistencia: tag [user=X] en cada línea',
    'buscar' => [
        'function log_persistencia(string $msg, string $nivel = \'info\'): void {',
        '    $nivel = strtolower($nivel);',
        '    if (!in_array($nivel, [\'info\', \'warn\', \'error\'], true)) $nivel = \'info\';',
        '    if (($nivel === \'info\' || $nivel === \'warn\') && !Conf::DEBUG_PERSISTENCIA) return;',
        '',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) {',
        '        // Fallback: si no se pudo crear el directorio, error_log.',
        '        error_log("[LOG_PERSISTENCIA] " . $msg);',
        '        return;',
        '    }',
        '',
        '    $fecha = date(\'Y-m-d\');',
        '    $ruta = $dir . $fecha . \'.log\';',
        '    $primera_vez_hoy = !file_exists($ruta);',
        '    $linea = \'[\' . date(\'Y-m-d H:i:s\') . \'] [\' . $nivel . \'] \' . $msg . "\\n";',
    ],
    'reemplazar' => [
        'function log_persistencia(string $msg, string $nivel = \'info\'): void {',
        '    $nivel = strtolower($nivel);',
        '    if (!in_array($nivel, [\'info\', \'warn\', \'error\'], true)) $nivel = \'info\';',
        '    if (($nivel === \'info\' || $nivel === \'warn\') && !Conf::DEBUG_PERSISTENCIA) return;',
        '',
        '    // Fix 1.5i.7x: prefijo `[user=X]` (o `[user=-]` si no hay',
        '    // usuario seteado). Permite saber qué usuario disparó cada',
        '    // guardado cuando hay múltiples sesiones.',
        '    $usuario = log_persistencia_usuario_actual();',
        '    $tag_usuario = \'[user=\' . (($usuario === null || $usuario === \'\') ? \'-\' : $usuario) . \']\';',
        '',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) {',
        '        // Fallback: si no se pudo crear el directorio, error_log.',
        '        error_log("[LOG_PERSISTENCIA] " . $tag_usuario . " " . $msg);',
        '        return;',
        '    }',
        '',
        '    $fecha = date(\'Y-m-d\');',
        '    $ruta = $dir . $fecha . \'.log\';',
        '    $primera_vez_hoy = !file_exists($ruta);',
        '    $linea = \'[\' . date(\'Y-m-d H:i:s\') . \'] [\' . $nivel . \'] \' . $tag_usuario . \' \' . $msg . "\\n";',
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

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

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

$archivos_bump_php_js = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
}

$bumps_atversion = 0;
$bumps_detalle = [];
$info_bumps = [];

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

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";