<?php
/**
 * Aplicador — Tanda R: grilla de fondo que se expande con el viewBox.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) HTML: bump ?v= de grafo_visual.js.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de grafo_visual.js',
        'buscar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81d"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81e"></script>',
        ],
    ],

    // ============================================================
    // B) grafo_visual.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Bump @version de grafo_visual.js',
        'buscar' => [
            ' * @version 1.5piloto.81d',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81e',
        ],
    ],

    // ============================================================
    // C) grafo_visual.js: globals para rect + observer.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Globals para rect de grilla y observer',
        'buscar' => [
            '// Estado del drag de nodos.',
            'let _grafo_visual_drag = null;',
        ],
        'reemplazar' => [
            '// Estado del drag de nodos.',
            'let _grafo_visual_drag = null;',
            '',
            '// Rect de la grilla de fondo y observer para redimensionarlo',
            '// cuando cambia el viewBox (pan/zoom/centrar/animaciones).',
            'let _grafo_visual_grid_rect = null;',
            'let _grafo_visual_grid_observer = null;',
        ],
    ],

    // ============================================================
    // D) grafo_visual.js: guardar la referencia al crear el fondo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Guardar referencia al rect de la grilla',
        'buscar' => [
            '    fondo.setAttribute(\'fill\', \'url(#grafo-grid)\');',
            '    svg.appendChild(fondo);',
        ],
        'reemplazar' => [
            '    fondo.setAttribute(\'fill\', \'url(#grafo-grid)\');',
            '    svg.appendChild(fondo);',
            '    _grafo_visual_grid_rect = fondo;',
        ],
    ],

    // ============================================================
    // E) grafo_visual.js: limpiar el observer anterior en cada render.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Limpiar el observer antes de re-renderizar',
        'buscar' => [
            '    wrapper.innerHTML = \'\';',
            '    _grafo_visual_refs_nodos = {};',
            '    _grafo_visual_refs_enlaces = [];',
        ],
        'reemplazar' => [
            '    // Limpiar el observer de la grilla anterior.',
            '    if (_grafo_visual_grid_observer) {',
            '        _grafo_visual_grid_observer.disconnect();',
            '        _grafo_visual_grid_observer = null;',
            '    }',
            '    _grafo_visual_grid_rect = null;',
            '',
            '    wrapper.innerHTML = \'\';',
            '    _grafo_visual_refs_nodos = {};',
            '    _grafo_visual_refs_enlaces = [];',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: crear el observer tras armar el SVG.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Observer de viewBox para expandir la grilla',
        'buscar' => [
            '    svg.appendChild(g_nodos);',
            '    wrapper.appendChild(svg);',
            '    _grafo_visual_svg = svg;',
        ],
        'reemplazar' => [
            '    svg.appendChild(g_nodos);',
            '    wrapper.appendChild(svg);',
            '    _grafo_visual_svg = svg;',
            '',
            '    // Observar cambios del viewBox para expandir la grilla',
            '    // de fondo al área visible (pan, zoom, centrar,',
            '    // animaciones).',
            '    _grafo_visual_grid_observer = new MutationObserver(_grafo_visual_actualizar_grid_rect);',
            '    _grafo_visual_grid_observer.observe(svg, { attributes: true, attributeFilter: [\'viewBox\'] });',
        ],
    ],

    // ============================================================
    // G) grafo_visual.js: helper de redimensión de la grilla.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Helper que expande la grilla con el viewBox',
        'buscar' => [
            '    return {',
            '        d: `M ${p_salida.x} ${p_salida.y} Q ${cx_control} ${cy_control} ${p_entrada.x} ${p_entrada.y}`,',
            '        px_label, py_label',
            '    };',
            '}',
            '',
            'function _grafo_visual_actualizar_toggle() {',
        ],
        'reemplazar' => [
            '    return {',
            '        d: `M ${p_salida.x} ${p_salida.y} Q ${cx_control} ${cy_control} ${p_entrada.x} ${p_entrada.y}`,',
            '        px_label, py_label',
            '    };',
            '}',
            '',
            '/**',
            ' * Expande el rect de la grilla de fondo para que cubra el',
            ' * viewBox actual con margen. Se llama desde un',
            ' * MutationObserver cada vez que cambia el viewBox (pan,',
            ' * zoom, centrar, animaciones). El patrón es',
            ' * userSpaceOnUse, así que las líneas quedan ancladas al',
            ' * origen del grafo y la grilla "se reproduce" hacia los',
            ' * costados cuando el viewBox crece.',
            ' */',
            'function _grafo_visual_actualizar_grid_rect() {',
            '    const grid = _grafo_visual_grid_rect;',
            '    const vb = _grafo_visual_vb;',
            '    if (!grid || !vb) return;',
            '    const margen_x = vb.w * 0.5;',
            '    const margen_y = vb.h * 0.5;',
            '    grid.setAttribute(\'x\', vb.x - margen_x);',
            '    grid.setAttribute(\'y\', vb.y - margen_y);',
            '    grid.setAttribute(\'width\', vb.w + 2 * margen_x);',
            '    grid.setAttribute(\'height\', vb.h + 2 * margen_y);',
            '}',
            '',
            'function _grafo_visual_actualizar_toggle() {',
        ],
    ],

    // ============================================================
    // H) plan_actual: anotar la mejora.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v81e',
        'buscar' => [
            '  el `data-layout="force"` interno no cambia.',
            '- Inferencia de tipo en el frontend. Si el backend',
        ],
        'reemplazar' => [
            '  el `data-layout="force"` interno no cambia.',
            '- Grilla de fondo: se expande con el viewBox. Un',
            '  MutationObserver sobre el `viewBox` del SVG',
            '  redimensiona el rect de la grilla cada vez que',
            '  cambia (pan, zoom, centrar, animaciones). El patrón',
            '  es `userSpaceOnUse`, así que las líneas quedan',
            '  ancladas al origen del grafo y la grilla "se',
            '  reproduce" hacia los costados cuando el viewBox',
            '  crece. Margen: 50% del viewBox en cada dirección.',
            '- Inferencia de tipo en el frontend. Si el backend',
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
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";