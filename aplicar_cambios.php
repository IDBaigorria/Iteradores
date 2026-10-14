<?php
/**
 * Aplicador — Fix runner v2: bumps no pisan los reemplazos.
 *
 * Bug: al bumpear, el runner releía el archivo desde el disco
 * (sin los reemplazos pendientes) y pisaba la entrada en memoria.
 * Resultado: en archivos con reemplazos + bump, los reemplazos se
 * perdían. Detectado en la aplicación de P55 (v1.5plugin.6x).
 *
 * Fix: si el archivo ya está en $archivos_a_escribir (tiene
 * reemplazos pendientes), el bump parte de ese contenido en
 * memoria. Si no, lee del disco.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.79o';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Fix en prompt_runner_aplicar_cambios.md (bloque 2a: @version).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_runner_aplicar_cambios.md',
        'descripcion' => 'Runner: bumps parten del contenido en memoria',
        'buscar' => [
            'if ($bumpear) {',
            '    // 2a. @version de .php y .js',
            '    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {',
            '        $ruta_abs = $raiz_proyecto . \'/\' . $archivo_rel;',
            '        if (!file_exists($ruta_abs)) continue;',
            '        $contenido_original = file_get_contents($ruta_abs);',
            '        $eol = detectar_eol($contenido_original);',
            '        $contenido = normalizar_a_unix($contenido_original);',
            '        $patron = \'/(\*\s+@version\s+)(\S+)/\';',
        ],
        'reemplazar' => [
            'if ($bumpear) {',
            '    // 2a. @version de .php y .js',
            '    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {',
            '        $ruta_abs = $raiz_proyecto . \'/\' . $archivo_rel;',
            '        if (!file_exists($ruta_abs)) continue;',
            '        // v79o: si el archivo ya tiene reemplazos pendientes en',
            '        // memoria, bumpear SOBRE ellos. Antes se releía del disco',
            '        // y se pisaban los reemplazos (bug detectado aplicando',
            '        // P55 v6x). Si no está en memoria, leer del disco.',
            '        if (isset($archivos_a_escribir[$ruta_abs])) {',
            '            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];',
            '        } else {',
            '            $contenido_original = file_get_contents($ruta_abs);',
            '            $eol = detectar_eol($contenido_original);',
            '            $contenido = normalizar_a_unix($contenido_original);',
            '        }',
            '        $patron = \'/(\*\s+@version\s+)(\S+)/\';',
        ],
    ],
    // ============================================================
    // Fix en prompt_runner_aplicar_cambios.md (bloque 2b: ?v=).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_runner_aplicar_cambios.md',
        'descripcion' => 'Runner: ?v= parte del contenido en memoria',
        'buscar' => [
            '    // 2b. ?v= en los HTMLs',
            '    foreach ($htmls_a_bumpear as $html_rel) {',
            '        $html_abs = $raiz_proyecto . \'/\' . $html_rel;',
            '        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }',
            '        $html_orig = file_get_contents($html_abs);',
            '        $eol = detectar_eol($html_orig);',
            '        $html = normalizar_a_unix($html_orig);',
            '        $html_antes = $html;',
        ],
        'reemplazar' => [
            '    // 2b. ?v= en los HTMLs',
            '    foreach ($htmls_a_bumpear as $html_rel) {',
            '        $html_abs = $raiz_proyecto . \'/\' . $html_rel;',
            '        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }',
            '        // v79o: mismo criterio que los .php/.js. Si el HTML ya',
            '        // tiene reemplazos pendientes en memoria, bumpear sobre',
            '        // ellos. Si no, leer del disco.',
            '        if (isset($archivos_a_escribir[$html_abs])) {',
            '            list($html, $eol) = $archivos_a_escribir[$html_abs];',
            '        } else {',
            '            $html_orig = file_get_contents($html_abs);',
            '            $eol = detectar_eol($html_orig);',
            '            $html = normalizar_a_unix($html_orig);',
            '        }',
            '        $html_antes = $html;',
        ],
    ],
];

// ============================================================
// Runner (el de siempre)
// ============================================================

echo "=== Aplicador — Fix runner v2: bumps no pisan reemplazos ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
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

if ($version_nueva !== '') {
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
        if ($count === 0) continue;
        if ($viejo === $version_nueva) continue;
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
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

if (!empty($bumps_detalle)) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($version_nueva !== '') echo "Bumps @version:    $bumps_atversion\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";