<?php
/**
 * Aplicador — Reiniciar seleccion de asientos al arrancar la app.
 *
 * Cuando un terminal arranca la app (login o validacion de token
 * guardado), limpia cualquier venta_actual colgada. Cubre el caso
 * "usuario cerro el navegador a mitad de una venta".
 *
 * Endpoint: viajes/reiniciar_seleccion_completa (ya existia,
 * v1.5piloto.79h). El frontend solo lo llama al arrancar.
 *
 * Best-effort: si falla, loguea warning y sigue. No bloquea el
 * arranque de la app.
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80h';

$htmls_a_bumpear = [];

$cambios = [

    // 1. Nuevo helper despues de es_admin_o_soporte.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: nuevo helper _reiniciar_seleccion_si_terminal',
        'buscar' => [
            'function es_admin_o_soporte() {',
            '    if (!usuario_actual) return false;',
            '    return usuario_actual.nivel === \'admin\' || usuario_actual.nivel === \'soporte\';',
            '}',
        ],
        'reemplazar' => [
            'function es_admin_o_soporte() {',
            '    if (!usuario_actual) return false;',
            '    return usuario_actual.nivel === \'admin\' || usuario_actual.nivel === \'soporte\';',
            '}',
            '',
            '/**',
            ' * Limpia la venta_actual colgada de un terminal.',
            ' *',
            ' * Se llama al arrancar la app (login o validacion de',
            ' * token guardado). Cubre el caso "usuario cerro el',
            ' * navegador a mitad de una venta": los asientos',
            ' * quedan en estado "seleccionado" en el backend, y',
            ' * este helper los libera y destruye la venta_actual.',
            ' *',
            ' * Best-effort: si falla, loguea un warning y no',
            ' * bloquea el arranque. El endpoint es idempotente.',
            ' */',
            'function _reiniciar_seleccion_si_terminal() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'terminal\') return;',
            '    const nombre_terminal = usuario_actual.nombre_usuario;',
            '    if (!nombre_terminal) return;',
            '    fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "viajes/reiniciar_seleccion_completa",',
            '            nombre_terminal',
            '        })',
            '    }).catch(e => {',
            '        console.warn("No se pudo reiniciar la seleccion de asientos:", e);',
            '    });',
            '}',
        ],
    ],

    // 2. Llamarla al final de _aplicar_login_exitoso.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: llamada en _aplicar_login_exitoso',
        'buscar' => [
            '    _aplicar_clases_de_rol(usuario);',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '}',
        ],
        'reemplazar' => [
            '    _aplicar_clases_de_rol(usuario);',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '',
            '    // Si el que entra es terminal, limpiar cualquier',
            '    // venta_actual colgada (por ejemplo, si cerro el',
            '    // navegador a mitad de una venta).',
            '    _reiniciar_seleccion_si_terminal();',
            '}',
        ],
    ],

    // 3. Llamarla tambien en el camino de token guardado.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: llamada en DOMContentLoaded (token guardado)',
        'buscar' => [
            '                _aplicar_clases_de_rol(usuario_actual);',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
            '                ocultar_detalle_viaje();',
            '            } else {',
        ],
        'reemplazar' => [
            '                _aplicar_clases_de_rol(usuario_actual);',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
            '                ocultar_detalle_viaje();',
            '',
            '                // Mismo caso que en _aplicar_login_exitoso:',
            '                // si el que arranca es terminal, limpiar',
            '                // la venta_actual colgada. Aca entra cuando',
            '                // el navegador conservo el token y la app',
            '                // se reabre sin pedir login.',
            '                _reiniciar_seleccion_si_terminal();',
            '            } else {',
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