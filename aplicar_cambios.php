<?php
/**
 * Aplicador — listar_viajes_de_dueno drena buzones.
 *
 * Con el fix v79a (inserción diferida), cuando un admin o
 * terminal hace una venta, la venta va al buzón del
 * compartido. Solo se drena desde listar_ventas_por_dueno.
 * Si el admin mira listar_viajes_de_dueno sin haber pasado
 * por Vendidos, el viaje aparece con tiene_ventas="0".
 *
 * Fix: drenar buzones también desde listar_viajes_de_dueno.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'listar_viajes_de_dueno drena buzones',
        'buscar' => [
            'function listar_viajes_de_dueno(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [];',
            '',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    if (!$adyacentes) return [];',
            '',
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ. Antes, formatear_viaje recorría todo el contenedor',
            '    // de ventas del dueño por cada viaje (O(V × W)).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno);',
        ],
        'reemplazar' => [
            'function listar_viajes_de_dueno(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [];',
            '',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    if (!$adyacentes) return [];',
            '',
            '    // Drenar buzones pendientes. Con el fix v79a (inserción',
            '    // diferida), el admin y el terminal dejan las ventas',
            '    // nuevas en el buzón del compartido. Si no las drena',
            '    // nadie antes de armar el índice, los viajes aparecen',
            '    // con tiene_ventas="0". Idempotente. Bug §1.5.15.',
            '    if (function_exists(\'_procesar_pendientes_del_dueno\')) {',
            '        $hubo_drenado = _procesar_pendientes_del_dueno($nombre_dueno);',
            '        if ($hubo_drenado) {',
            '            guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '        }',
            '    }',
            '',
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ. Antes, formatear_viaje recorría todo el contenedor',
            '    // de ventas del dueño por cada viaje (O(V × W)).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno);',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump Viaje.php a 79g',
        'buscar' => [
            ' * @version   1.5piloto.78j',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79g',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — listar_viajes_de_dueno drena buzones ===\n\n";

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
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
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
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";