<?php
/**
 * Aplicador — Tanda K: pantalla completa del visualizador.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) HTML: bumps de ?v=.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de grafo_visual.js',
        'buscar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.80w"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.80x"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de estilos-grafo.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.80w">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.80x">',
        ],
    ],

    // ============================================================
    // B) estilos-grafo.css: botón de fullscreen + ajuste :fullscreen.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Estilo del botón de pantalla completa',
        'buscar' => [
            '/* Aviso cuando no hay datos o hay error */',
            '.grafo-visual-aviso {',
        ],
        'reemplazar' => [
            '/* Botón de pantalla completa, abajo a la derecha. */',
            '.grafo-visual-fullscreen {',
            '    position: absolute;',
            '    bottom: 12px;',
            '    right: 12px;',
            '    width: 36px;',
            '    height: 36px;',
            '    border: 1px solid var(--border);',
            '    border-radius: 6px;',
            '    background: rgba(255,255,255,0.92);',
            '    color: #333;',
            '    font-size: 18px;',
            '    line-height: 1;',
            '    padding: 0;',
            '    cursor: pointer;',
            '    z-index: 5;',
            '    transition: background var(--transition);',
            '}',
            '.grafo-visual-fullscreen:hover { background: #f9d857; }',
            '',
            '/* Ajustes cuando el wrapper está en pantalla completa. */',
            '.grafo-visual-wrapper:fullscreen {',
            '    border: 0;',
            '    border-radius: 0;',
            '    background: #fafafa;',
            '}',
            '.grafo-visual-wrapper:fullscreen .grafo-visual-info {',
            '    background: rgba(255,255,255,0.95);',
            '}',
            '',
            '/* Aviso cuando no hay datos o hay error */',
            '.grafo-visual-aviso {',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Bump @version de estilos-grafo.css',
        'buscar' => [
            ' * @version 1.5piloto.80w',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.80x',
        ],
    ],

    // ============================================================
    // C) plan_actual: anotar la mejora.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v80x',
        'buscar' => [
            '- Click en un enlace: la vista hace pan animado',
            '  (ease-out cubic ~400 ms) hasta centrar el nodo',
            '  destino. El enlace tiene un área de click invisible',
            '  (`stroke-width: 14`, transparente) sobre el trazo',
            '  visible fino. El hover sobre esa área resalta el',
            '  trazo visible (clase `.hover`, no `:hover`, porque',
            '  el hit está arriba del path).',
        ],
        'reemplazar' => [
            '- Click en un enlace: la vista hace pan animado',
            '  (ease-out cubic ~400 ms) hasta centrar el nodo',
            '  destino. El enlace tiene un área de click invisible',
            '  (`stroke-width: 14`, transparente) sobre el trazo',
            '  visible fino. El hover sobre esa área resalta el',
            '  trazo visible (clase `.hover`, no `:hover`, porque',
            '  el hit está arriba del path).',
            '- Botón de pantalla completa, abajo a la derecha del',
            '  wrapper. Usa la Fullscreen API (`requestFullscreen`',
            '  sobre `#grafo_visual_wrapper`). Esc sale. El ícono',
            '  cambia entre expandir (`⤢`) y contraer (`⤡`) según',
            '  `fullscreenchange`.',
        ],
    ],

    // ============================================================
    // D) grafo_visual.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Bump @version de grafo_visual.js',
        'buscar' => [
            ' * @version 1.5piloto.80w',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.80x',
        ],
    ],

    // ============================================================
    // E) grafo_visual.js: agregar el botón al wrapper.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Botón de pantalla completa en el wrapper',
        'buscar' => [
            '    wrapper.appendChild(info);',
            '',
            '    _conectar_pan_zoom(svg);',
        ],
        'reemplazar' => [
            '    wrapper.appendChild(info);',
            '',
            '    // Botón de pantalla completa (abajo a la derecha).',
            '    const btn_fs = document.createElement(\'button\');',
            '    btn_fs.type = \'button\';',
            '    btn_fs.id = \'grafo_visual_btn_fullscreen\';',
            '    btn_fs.className = \'grafo-visual-fullscreen\';',
            '    btn_fs.title = \'Pantalla completa\';',
            '    btn_fs.textContent = \'\\u2922\';',
            '    btn_fs.addEventListener(\'click\', _grafo_visual_toggle_fullscreen);',
            '    wrapper.appendChild(btn_fs);',
            '',
            '    _conectar_pan_zoom(svg);',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: funciones de fullscreen + listener.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Funciones de pantalla completa',
        'buscar' => [
            '// ============================================================',
            '// Init',
            '// ============================================================',
            '',
            '(function _init_grafo_visual() {',
            '    function _setup() {',
            '        if (_grafo_visual_inicializado) return;',
            '        _grafo_visual_inicializado = true;',
            '        _conectar_subtabs_grafo();',
            '    }',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Pantalla completa',
            '// ============================================================',
            '',
            'function _grafo_visual_toggle_fullscreen() {',
            '    const wrapper = document.getElementById(\'grafo_visual_wrapper\');',
            '    if (!wrapper) return;',
            '    if (document.fullscreenElement === wrapper) {',
            '        if (document.exitFullscreen) document.exitFullscreen();',
            '    } else {',
            '        if (wrapper.requestFullscreen) {',
            '            const p = wrapper.requestFullscreen();',
            '            if (p && typeof p.catch === \'function\') p.catch(() => {});',
            '        }',
            '    }',
            '}',
            '',
            'function _grafo_visual_actualizar_boton_fullscreen() {',
            '    const btn = document.getElementById(\'grafo_visual_btn_fullscreen\');',
            '    if (!btn) return;',
            '    const wrapper = document.getElementById(\'grafo_visual_wrapper\');',
            '    const en_fullscreen = (document.fullscreenElement === wrapper);',
            '    btn.textContent = en_fullscreen ? \'\\u2921\' : \'\\u2922\';',
            '    btn.title = en_fullscreen ? \'Salir de pantalla completa\' : \'Pantalla completa\';',
            '}',
            '',
            '// ============================================================',
            '// Init',
            '// ============================================================',
            '',
            '(function _init_grafo_visual() {',
            '    function _setup() {',
            '        if (_grafo_visual_inicializado) return;',
            '        _grafo_visual_inicializado = true;',
            '        _conectar_subtabs_grafo();',
            '        // Cambiar el ícono del botón al entrar/salir de fullscreen.',
            '        document.addEventListener(\'fullscreenchange\', _grafo_visual_actualizar_boton_fullscreen);',
            '    }',
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