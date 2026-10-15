<?php
/**
 * Aplicador — Limpieza de Autenticacion.php.
 *
 * 1. Saca los error_log temporales [DIAG-AUTH] que agregamos
 *    para diagnosticar el bug de la prueba 54.
 * 2. Fix del doble time() en _esta_bloqueado: cachea el valor
 *    en $ahora y usa esa variable tanto para el log interno
 *    como para la comparación. Antes, si el segundo cambiaba
 *    entre las dos llamadas, la comparación podía fallar en
 *    el borde (bug latente, no se manifestaba con 10s de
 *    bloqueo).
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80d';

$htmls_a_bumpear = [];

$cambios = [

    // 1. autenticar_por_usuario: sacar el bloque DIAG-AUTH
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion: sacar DIAG-AUTH de autenticar_por_usuario',
        'buscar' => [
            '        // [DIAG-AUTH] Estado del usuario al entrar.',
            '        $diag_int = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '        $diag_blq = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '        error_log("[DIAG-AUTH] autenticar_por_usuario usuario=" . $nombre_usuario',
            '            . " intentos=" . ($diag_int ? $diag_int->dato() : "(null)")',
            '            . " bloqueado_hasta=" . ($diag_blq ? $diag_blq->dato() : "(null)")',
            '            . " time=" . time());',
            '',
            '        // ¿Está bloqueado el usuario?',
            '        if (_esta_bloqueado($nodo_usuario)) {',
        ],
        'reemplazar' => [
            '        // ¿Está bloqueado el usuario?',
            '        if (_esta_bloqueado($nodo_usuario)) {',
        ],
    ],

    // 2. _esta_bloqueado: sacar logs y cachear time()
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion: limpiar _esta_bloqueado y cachear time()',
        'buscar' => [
            'function _esta_bloqueado(Nodo $nodo_usuario): bool {',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if (!$nodo_bloqueo) {',
            '        error_log("[DIAG-AUTH] _esta_bloqueado: sin nodo bloqueado_hasta. Usuario id=" . $nodo_usuario->id());',
            '        return false;',
            '    }',
            '',
            '    $hasta = (int)$nodo_bloqueo->dato();',
            '    error_log("[DIAG-AUTH] _esta_bloqueado: hasta=" . $hasta . " time=" . time() . " (diff=" . ($hasta - time()) . "s)");',
            '    if ($hasta > time()) {',
            '        return true;',
            '    }',
        ],
        'reemplazar' => [
            'function _esta_bloqueado(Nodo $nodo_usuario): bool {',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if (!$nodo_bloqueo) return false;',
            '',
            '    $hasta = (int)$nodo_bloqueo->dato();',
            '    // Cachear time() en $ahora: comparar contra dos valores',
            '    // distintos de time() puede fallar en el borde del',
            '    // segundo si el reloj avanza entre las dos llamadas.',
            '    $ahora = time();',
            '    if ($hasta > $ahora) {',
            '        return true;',
            '    }',
        ],
    ],

    // 3. _registrar_intento_fallido: sacar log y volver a la version limpia
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion: limpiar _registrar_intento_fallido',
        'buscar' => [
            'function _registrar_intento_fallido(Nodo $nodo_usuario): void {',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    $intentos_antes = $nodo_intentos ? (int)$nodo_intentos->dato() : 0;',
            '    $intentos = $intentos_antes + 1;',
            '',
            '    if ($nodo_intentos) $nodo_intentos->_dato((string)$intentos);',
            '    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$intentos), \'intentos_fallidos\');',
            '',
            '    $max = ConfiguracionApli::intentos_maximos_autenticacion();',
            '    $bloqueo = ($intentos >= $max);',
            '    $hasta_str = "";',
            '    if ($bloqueo) {',
            '        $hasta = time() + ConfiguracionApli::bloqueo_autenticacion_segundos();',
            '        $hasta_str = (string)$hasta;',
            '        $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '        if ($nodo_bloqueo) $nodo_bloqueo->_dato($hasta_str);',
            '        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($hasta_str), \'bloqueado_hasta\');',
            '    }',
            '    error_log("[DIAG-AUTH] _registrar_intento_fallido: intentos " . $intentos_antes',
            '        . " -> " . $intentos . " (max=" . $max . ", bloquea=" . ($bloqueo ? "si" : "no")',
            '        . ", hasta=" . ($hasta_str !== "" ? $hasta_str : "-") . ")"',
            '        . " nodo_id=" . $nodo_usuario->id());',
            '}',
        ],
        'reemplazar' => [
            'function _registrar_intento_fallido(Nodo $nodo_usuario): void {',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    $intentos = $nodo_intentos ? (int)$nodo_intentos->dato() : 0;',
            '    $intentos++;',
            '',
            '    if ($nodo_intentos) $nodo_intentos->_dato((string)$intentos);',
            '    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$intentos), \'intentos_fallidos\');',
            '',
            '    if ($intentos >= ConfiguracionApli::intentos_maximos_autenticacion()) {',
            '        $hasta = time() + ConfiguracionApli::bloqueo_autenticacion_segundos();',
            '        $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '        if ($nodo_bloqueo) $nodo_bloqueo->_dato((string)$hasta);',
            '        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$hasta), \'bloqueado_hasta\');',
            '    }',
            '}',
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
if ($bumpear) echo "Bumps @version:    $bumps_atversion\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";