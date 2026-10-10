<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77j (fix de zona horaria).
 *   - index.php: fijar date_default_timezone_set a
 *     America/Argentina/Buenos_Aires justo después de
 *     session_start(). Sin esto, PHP usa UTC (default) y
 *     date('Y-m-d') puede quedar un día adelantado respecto del
 *     navegador del usuario entre las 21 y las 24 hora argentina.
 *     Eso rompía el cálculo de "Fecha de actualizacion invalida"
 *     en el autocompletado de pasajeros.
 *   - Bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // index.php — bump + fix zona horaria
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77j',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77i',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77j',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: fijar zona horaria',
        'buscar' => [
            '<?php',
            'session_start();',
            'header("Cache-control: no-cache, must-revalidate");',
            '',
            'use Iteradores\\Controlador\\Controlador;',
        ],
        'reemplazar' => [
            '<?php',
            '// Zona horaria del servidor. Sin esto, PHP usa UTC por',
            '// defecto y date(\'Y-m-d\') puede quedar un día adelantado',
            '// respecto del navegador del usuario entre las 21 y las',
            '// 24 hora argentina. Eso rompía el cálculo de antigüedad',
            '// de los datos del pasajero en el autocompletado por DNI',
            '// (avisaba "Fecha de actualizacion invalida").',
            'date_default_timezone_set(\'America/Argentina/Buenos_Aires\');',
            '',
            'session_start();',
            'header("Cache-control: no-cache, must-revalidate");',
            '',
            'use Iteradores\\Controlador\\Controlador;',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77j',
        'buscar' => [
            '**Tanda actual:** v77i (Fase B2.3.5b.3: contexto en',
            'empresas, vehículos y pasajeros).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77j (fix de zona horaria).',
            '',
            '**v77j — Fix de zona horaria.** El servidor no tenía',
            '`date_default_timezone_set`, así que PHP usaba UTC por',
            'defecto. Entre las 21 y las 24 hora argentina,',
            '`date(\'Y-m-d\')` devolvía la fecha del día siguiente',
            'respecto del navegador. El cálculo de antigüedad del',
            'autocompletado de pasajeros (`_calcular_antiguedad_datos`',
            'en `aplicacion.js`) comparaba la fecha del servidor con la',
            'del navegador y daba `diff_dias < 0`, mostrando "Fecha de',
            'actualizacion invalida". Fix: `date_default_timezone_set(',
            '\'America/Argentina/Buenos_Aires\')` al inicio de `index.php`.',
            'Bug latente desde que existe el autocompletado (v58).',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";