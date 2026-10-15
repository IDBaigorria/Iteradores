<?php
/**
 * Aplicador — DIAGNOSTICO TEMPORAL de autenticacion.
 *
 * Agrega error_log en 3 puntos del flujo de autenticacion para
 * ver por que el bloqueo no se aplica entre requests:
 *   - autenticar_por_usuario: estado al entrar.
 *   - _esta_bloqueado: valor leido.
 *   - _registrar_intento_fallido: intentos antes/despues.
 *
 * Los mensajes van con prefijo [DIAG-AUTH] para filtrarlos
 * facil. SE SACAN CUANDO TERMINE EL DIAGNOSTICO.
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80c';

$htmls_a_bumpear = [];

$cambios = [

    // 1. autenticar_por_usuario: log al entrar
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'DIAG: log en autenticar_por_usuario',
        'buscar' => [
            '        // ¿Está bloqueado el usuario?',
            '        if (_esta_bloqueado($nodo_usuario)) {',
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        // Verificar contraseña.',
            '        if (!password_verify($contrasena, $nodo_contrasena->dato())) {',
            '            _registrar_intento_fallido($nodo_usuario);',
            '            return;',
            '        }',
        ],
        'reemplazar' => [
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
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        // Verificar contraseña.',
            '        if (!password_verify($contrasena, $nodo_contrasena->dato())) {',
            '            _registrar_intento_fallido($nodo_usuario);',
            '            return;',
            '        }',
        ],
    ],

    // 2. _esta_bloqueado: log del valor leido
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'DIAG: log en _esta_bloqueado',
        'buscar' => [
            'function _esta_bloqueado(Nodo $nodo_usuario): bool {',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if (!$nodo_bloqueo) return false;',
            '',
            '    $hasta = (int)$nodo_bloqueo->dato();',
            '    if ($hasta > time()) {',
            '        return true;',
            '    }',
        ],
        'reemplazar' => [
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
    ],

    // 3. _registrar_intento_fallido: intentos antes/despues
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'DIAG: log en _registrar_intento_fallido',
        'buscar' => [
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
        'reemplazar' => [
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