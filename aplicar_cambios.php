<?php
/**
 * Aplicador — Piloto 1.5piloto.83m: setear el usuario en el log
 * de persistencia (validar_token_sesion + login).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83m';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Autenticacion: include de LogPersistencia
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'Autenticacion: include de LogPersistencia',
    'buscar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Nodos/Nodo.php");',
        'include_once("./Controlador/Controlador.php");',
    ],
    'reemplazar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Configuracion/LogPersistencia.php");',
        'include_once("./Nodos/Nodo.php");',
        'include_once("./Controlador/Controlador.php");',
    ],
],

// ============================================================
// 2. autenticar_por_codigo: setear usuario en el callback
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'autenticar_por_codigo: setear usuario',
    'buscar' => [
        '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente, \'codigo_hash\', $codigo);',
        '        $nombre_usuario = $encontrado;',
        '    });',
    ],
    'reemplazar' => [
        '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente, \'codigo_hash\', $codigo);',
        '        $nombre_usuario = $encontrado;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario((string)$encontrado);',
        '    });',
    ],
],

// ============================================================
// 3. autenticar_por_usuario: setear usuario en el callback
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'autenticar_por_usuario: setear usuario',
    'buscar' => [
        '        _registrar_login_exitoso($nodo_usuario, $ip_cliente, \'contrasena\', $contrasena);',
        '        $verificado = true;',
        '    });',
    ],
    'reemplazar' => [
        '        _registrar_login_exitoso($nodo_usuario, $ip_cliente, \'contrasena\', $contrasena);',
        '        $verificado = true;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario($nombre_usuario);',
        '    });',
    ],
],

// ============================================================
// 4. validar_token_sesion: setear usuario después de resolver
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'validar_token_sesion: setear usuario',
    'buscar' => [
        '    $nodo_usuario_real = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_usuario_real) return null;',
        '',
        '    return [',
        '        \'nombre_usuario\' => $nombre_usuario,',
        '        \'nodo\' => $nodo_usuario_real,',
        '    ];',
    ],
    'reemplazar' => [
        '    $nodo_usuario_real = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_usuario_real) return null;',
        '',
        '    // Fix 1.5piloto.83m: setear el usuario en el log de persistencia.',
        '    // A partir de acá, todos los `log_persistencia` del request',
        '    // llevan el tag `[user=<nombre>]`.',
        '    log_persistencia_establecer_usuario((string)$nombre_usuario);',
        '',
        '    return [',
        '        \'nombre_usuario\' => $nombre_usuario,',
        '        \'nodo\' => $nodo_usuario_real,',
        '    ];',
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