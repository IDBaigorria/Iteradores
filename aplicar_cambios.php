<?php
/**
 * Aplicador — Tanda M: modo teatro del visualizador.
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
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.80y"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.80z"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de estilos-grafo.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.80y">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.80z">',
        ],
    ],

    // ============================================================
    // B) CSS: reemplazar el bloque de fullscreen por acciones +
    //    teatro + fullscreen.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Acciones flotantes + modo teatro + fullscreen',
        'buscar' => [
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
            '/* Pantalla completa: el target es el contenedor entero,',
            '   así se ven la barra de controles y el panel lateral. */',
            '#grafo_visual_contenedor:fullscreen {',
            '    display: flex;',
            '    flex-direction: column;',
            '    background: #fff;',
            '    padding: 12px;',
            '    gap: 10px;',
            '    overflow: hidden;',
            '    box-sizing: border-box;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-controles {',
            '    margin-bottom: 0;',
            '    flex-shrink: 0;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-layout {',
            '    flex: 1;',
            '    align-items: stretch;',
            '    min-height: 0;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-wrapper {',
            '    border: 0;',
            '    border-radius: 0;',
            '    background: #fafafa;',
            '    height: 100%;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-info {',
            '    background: rgba(255,255,255,0.95);',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-panel {',
            '    max-height: 100%;',
            '}',
        ],
        'reemplazar' => [
            '/* Acciones flotantes abajo a la derecha (teatro y fullscreen). */',
            '.grafo-visual-acciones {',
            '    position: absolute;',
            '    bottom: 12px;',
            '    right: 12px;',
            '    display: flex;',
            '    gap: 6px;',
            '    z-index: 5;',
            '}',
            '.grafo-visual-accion-btn {',
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
            '    transition: background var(--transition);',
            '}',
            '.grafo-visual-accion-btn:hover { background: #f9d857; }',
            '',
            '/* Modo teatro: el contenedor se fija al viewport con un',
            '   margen de 10px y bloquea el scroll de la página. El',
            '   usuario sigue viendo las pestañas del navegador. */',
            '#grafo_visual_contenedor.grafo-visual-teatro {',
            '    position: fixed;',
            '    inset: 10px;',
            '    z-index: 9999;',
            '    background: #fff;',
            '    padding: 12px;',
            '    border-radius: 8px;',
            '    box-shadow: 0 8px 30px rgba(0,0,0,0.35);',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 10px;',
            '    overflow: hidden;',
            '    box-sizing: border-box;',
            '}',
            '#grafo_visual_contenedor.grafo-visual-teatro .grafo-visual-controles {',
            '    margin-bottom: 0;',
            '    flex-shrink: 0;',
            '}',
            '#grafo_visual_contenedor.grafo-visual-teatro .grafo-visual-layout {',
            '    flex: 1;',
            '    align-items: stretch;',
            '    min-height: 0;',
            '}',
            '#grafo_visual_contenedor.grafo-visual-teatro .grafo-visual-wrapper {',
            '    height: 100%;',
            '}',
            '#grafo_visual_contenedor.grafo-visual-teatro .grafo-visual-panel {',
            '    max-height: 100%;',
            '}',
            'body.grafo-visual-teatro-on { overflow: hidden; }',
            '',
            '/* Pantalla completa: también sobre el contenedor entero,',
            '   así se ven la barra de controles y el panel lateral. */',
            '#grafo_visual_contenedor:fullscreen {',
            '    display: flex;',
            '    flex-direction: column;',
            '    background: #fff;',
            '    padding: 12px;',
            '    gap: 10px;',
            '    overflow: hidden;',
            '    box-sizing: border-box;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-controles {',
            '    margin-bottom: 0;',
            '    flex-shrink: 0;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-layout {',
            '    flex: 1;',
            '    align-items: stretch;',
            '    min-height: 0;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-wrapper {',
            '    border: 0;',
            '    border-radius: 0;',
            '    background: #fafafa;',
            '    height: 100%;',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-info {',
            '    background: rgba(255,255,255,0.95);',
            '}',
            '#grafo_visual_contenedor:fullscreen .grafo-visual-panel {',
            '    max-height: 100%;',
            '}',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Bump @version de estilos-grafo.css',
        'buscar' => [
            ' * @version 1.5piloto.80y',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.80z',
        ],
    ],

    // ============================================================
    // C) plan_actual: anotar la mejora.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v80z',
        'buscar' => [
            '- Botón de pantalla completa, abajo a la derecha del',
            '  wrapper. Usa la Fullscreen API (`requestFullscreen`',
            '  sobre `#grafo_visual_contenedor`, para que en',
            '  pantalla completa se vean también la barra de',
            '  controles y el panel lateral). Esc sale. El ícono',
            '  cambia entre expandir (`⤢`) y contraer (`⤡`) según',
            '  `fullscreenchange`.',
        ],
        'reemplazar' => [
            '- Botón de pantalla completa, abajo a la derecha del',
            '  wrapper. Usa la Fullscreen API (`requestFullscreen`',
            '  sobre `#grafo_visual_contenedor`, para que en',
            '  pantalla completa se vean también la barra de',
            '  controles y el panel lateral). Esc sale. El ícono',
            '  cambia entre expandir (`⤢`) y contraer (`⤡`) según',
            '  `fullscreenchange`.',
            '- Modo teatro (`⛶` / `▭`): botón al lado del de',
            '  pantalla completa. Aplica `position: fixed;',
            '  inset: 10px` al contenedor y bloquea el scroll de',
            '  la página con `body.grafo-visual-teatro-on',
            '  { overflow: hidden }`. No es Fullscreen API: el',
            '  usuario sigue viendo las pestañas del navegador.',
            '  Esc sale si no está en fullscreen. Se puede',
            '  combinar con fullscreen (el fullscreen gana',
            '  mientras está activo; al salir, el teatro sigue).',
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
            ' * @version 1.5piloto.80y',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.80z',
        ],
    ],

    // ============================================================
    // E) grafo_visual.js: contenedor de acciones + dos botones.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Contenedor de acciones con botón de teatro',
        'buscar' => [
            '    // Botón de pantalla completa (abajo a la derecha).',
            '    const btn_fs = document.createElement(\'button\');',
            '    btn_fs.type = \'button\';',
            '    btn_fs.id = \'grafo_visual_btn_fullscreen\';',
            '    btn_fs.className = \'grafo-visual-fullscreen\';',
            '    btn_fs.title = \'Pantalla completa\';',
            '    btn_fs.textContent = \'\\u2922\';',
            '    btn_fs.addEventListener(\'click\', _grafo_visual_toggle_fullscreen);',
            '    wrapper.appendChild(btn_fs);',
        ],
        'reemplazar' => [
            '    // Acciones flotantes (abajo a la derecha): teatro + fullscreen.',
            '    const acciones = document.createElement(\'div\');',
            '    acciones.className = \'grafo-visual-acciones\';',
            '',
            '    const btn_teatro = document.createElement(\'button\');',
            '    btn_teatro.type = \'button\';',
            '    btn_teatro.id = \'grafo_visual_btn_teatro\';',
            '    btn_teatro.className = \'grafo-visual-accion-btn\';',
            '    btn_teatro.title = \'Modo teatro\';',
            '    btn_teatro.textContent = \'\\u26F6\';',
            '    btn_teatro.addEventListener(\'click\', _grafo_visual_toggle_teatro);',
            '    acciones.appendChild(btn_teatro);',
            '',
            '    const btn_fs = document.createElement(\'button\');',
            '    btn_fs.type = \'button\';',
            '    btn_fs.id = \'grafo_visual_btn_fullscreen\';',
            '    btn_fs.className = \'grafo-visual-accion-btn\';',
            '    btn_fs.title = \'Pantalla completa\';',
            '    btn_fs.textContent = \'\\u2922\';',
            '    btn_fs.addEventListener(\'click\', _grafo_visual_toggle_fullscreen);',
            '    acciones.appendChild(btn_fs);',
            '',
            '    wrapper.appendChild(acciones);',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: toggle de teatro + actualizador de botones.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Funciones de modo teatro y actualización de botones',
        'buscar' => [
            '// ============================================================',
            '// Pantalla completa',
            '// ============================================================',
            '',
            'function _grafo_visual_toggle_fullscreen() {',
            '    const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '    if (!cont) return;',
            '    if (document.fullscreenElement === cont) {',
            '        if (document.exitFullscreen) document.exitFullscreen();',
            '    } else {',
            '        if (cont.requestFullscreen) {',
            '            const p = cont.requestFullscreen();',
            '            if (p && typeof p.catch === \'function\') p.catch(() => {});',
            '        }',
            '    }',
            '}',
            '',
            'function _grafo_visual_actualizar_boton_fullscreen() {',
            '    const btn = document.getElementById(\'grafo_visual_btn_fullscreen\');',
            '    if (!btn) return;',
            '    const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '    const en_fullscreen = (document.fullscreenElement === cont);',
            '    btn.textContent = en_fullscreen ? \'\\u2921\' : \'\\u2922\';',
            '    btn.title = en_fullscreen ? \'Salir de pantalla completa\' : \'Pantalla completa\';',
            '}',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Pantalla completa y modo teatro',
            '// ============================================================',
            '',
            'function _grafo_visual_toggle_fullscreen() {',
            '    const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '    if (!cont) return;',
            '    if (document.fullscreenElement === cont) {',
            '        if (document.exitFullscreen) document.exitFullscreen();',
            '    } else {',
            '        if (cont.requestFullscreen) {',
            '            const p = cont.requestFullscreen();',
            '            if (p && typeof p.catch === \'function\') p.catch(() => {});',
            '        }',
            '    }',
            '}',
            '',
            'function _grafo_visual_toggle_teatro() {',
            '    // Si está en fullscreen, no hacer nada: el fullscreen manda.',
            '    if (document.fullscreenElement) return;',
            '    const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '    if (!cont) return;',
            '    const en_teatro = cont.classList.toggle(\'grafo-visual-teatro\');',
            '    document.body.classList.toggle(\'grafo-visual-teatro-on\', en_teatro);',
            '    _grafo_visual_actualizar_botones();',
            '}',
            '',
            'function _grafo_visual_actualizar_botones() {',
            '    const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '',
            '    const btn_teatro = document.getElementById(\'grafo_visual_btn_teatro\');',
            '    if (btn_teatro && cont) {',
            '        const en_teatro = cont.classList.contains(\'grafo-visual-teatro\');',
            '        btn_teatro.textContent = en_teatro ? \'\\u25AD\' : \'\\u26F6\';',
            '        btn_teatro.title = en_teatro ? \'Salir del modo teatro\' : \'Modo teatro\';',
            '    }',
            '',
            '    const btn_fs = document.getElementById(\'grafo_visual_btn_fullscreen\');',
            '    if (btn_fs) {',
            '        const en_fs = (document.fullscreenElement === cont);',
            '        btn_fs.textContent = en_fs ? \'\\u2921\' : \'\\u2922\';',
            '        btn_fs.title = en_fs ? \'Salir de pantalla completa\' : \'Pantalla completa\';',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // G) grafo_visual.js: listener de fullscreen y de Esc.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Listeners de fullscreenchange y Esc',
        'buscar' => [
            '        _conectar_subtabs_grafo();',
            '        // Cambiar el ícono del botón al entrar/salir de fullscreen.',
            '        document.addEventListener(\'fullscreenchange\', _grafo_visual_actualizar_boton_fullscreen);',
            '    }',
        ],
        'reemplazar' => [
            '        _conectar_subtabs_grafo();',
            '        // Actualizar íconos al entrar/salir de fullscreen.',
            '        document.addEventListener(\'fullscreenchange\', _grafo_visual_actualizar_botones);',
            '        // Esc cierra el modo teatro (si no está en fullscreen).',
            '        document.addEventListener(\'keydown\', (e) => {',
            '            if (e.key !== \'Escape\') return;',
            '            const cont = document.getElementById(\'grafo_visual_contenedor\');',
            '            if (!cont) return;',
            '            if (document.fullscreenElement) return;',
            '            if (!cont.classList.contains(\'grafo-visual-teatro\')) return;',
            '            cont.classList.remove(\'grafo-visual-teatro\');',
            '            document.body.classList.remove(\'grafo-visual-teatro-on\');',
            '            _grafo_visual_actualizar_botones();',
            '        });',
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