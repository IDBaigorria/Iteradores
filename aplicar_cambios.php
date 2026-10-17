<?php
/**
 * Aplicador — Tanda O: dato en el nodo + ID solo para especiales.
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
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81a"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81b"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de estilos-grafo.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81a">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81b">',
        ],
    ],

    // ============================================================
    // B) estilos-grafo.css: estilos del contenido del nodo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Estilos del contenido HTML del nodo',
        'buscar' => [
            '.grafo-nodo text {',
            '    font-family: Consolas, Monaco, monospace;',
            '    font-size: 11px;',
            '    fill: #333;',
            '    pointer-events: none;',
            '}',
        ],
        'reemplazar' => [
            '.grafo-nodo text {',
            '    font-family: Consolas, Monaco, monospace;',
            '    font-size: 11px;',
            '    fill: #333;',
            '    pointer-events: none;',
            '}',
            '',
            '/* Contenido del nodo: HTML dentro de un <foreignObject>,',
            '   para tener text-overflow: ellipsis real. */',
            '.grafo-nodo-texto {',
            '    font-family: Consolas, Monaco, monospace;',
            '    width: 100%;',
            '    height: 100%;',
            '    padding: 6px 8px;',
            '    box-sizing: border-box;',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 1px;',
            '    overflow: hidden;',
            '    color: #333;',
            '    pointer-events: none;',
            '    user-select: none;',
            '}',
            '.grafo-nodo-tipo {',
            '    font-size: 11px;',
            '    font-weight: 600;',
            '    white-space: nowrap;',
            '    overflow: hidden;',
            '    text-overflow: ellipsis;',
            '}',
            '.grafo-nodo-dato {',
            '    font-size: 10px;',
            '    color: #555;',
            '    white-space: nowrap;',
            '    overflow: hidden;',
            '    text-overflow: ellipsis;',
            '}',
            '.grafo-nodo-id {',
            '    font-size: 9px;',
            '    color: #888;',
            '    font-style: italic;',
            '    white-space: nowrap;',
            '    overflow: hidden;',
            '    text-overflow: ellipsis;',
            '    margin-top: auto;',
            '}',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Bump @version de estilos-grafo.css',
        'buscar' => [
            ' * @version 1.5piloto.81a',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81b',
        ],
    ],

    // ============================================================
    // C) plan_actual: anotar la mejora.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v81b',
        'buscar' => [
            '- Modo teatro (`⛶` / `▭`) y modo ampliado (`□` / `■`):',
        ],
        'reemplazar' => [
            '- Contenido del nodo: tipo arriba, dato abajo, ID solo',
            '  si es especial. Usa `<foreignObject>` con HTML',
            '  adentro, para tener `text-overflow: ellipsis` real',
            '  (sin calcular el ancho del texto a mano). El',
            '  contenido completo aparece en un `<title>` (tooltip',
            '  nativo de SVG) al hover. El drag ahora mueve rect',
            '  + foreignObject (sin tocar textos individuales).',
            '- Modo teatro (`⛶` / `▭`) y modo ampliado (`□` / `■`):',
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
            ' * @version 1.5piloto.81a',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81b',
        ],
    ],

    // ============================================================
    // E) grafo_visual.js: contenido del nodo con foreignObject.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Contenido del nodo con foreignObject',
        'buscar' => [
            '        const texto_tipo = document.createElementNS(svg_ns, \'text\');',
            '        texto_tipo.setAttribute(\'x\', p.x + 8);',
            '        texto_tipo.setAttribute(\'y\', p.y + 22);',
            '        texto_tipo.setAttribute(\'font-weight\', \'600\');',
            '        texto_tipo.textContent = n.tipo || \'?\';',
            '        g.appendChild(texto_tipo);',
            '',
            '        const texto_id = document.createElementNS(svg_ns, \'text\');',
            '        texto_id.setAttribute(\'x\', p.x + 8);',
            '        texto_id.setAttribute(\'y\', p.y + 46);',
            '        texto_id.setAttribute(\'fill\', \'#888\');',
            '        texto_id.textContent = n.es_especial ? n.id : (\'#\' + n.id);',
            '        g.appendChild(texto_id);',
        ],
        'reemplazar' => [
            '        // Texto del nodo: tipo arriba, dato abajo, ID (solo',
            '        // si es especial) al pie. Usa foreignObject + HTML',
            '        // para tener ellipsis real sin calcular el ancho',
            '        // del texto a mano.',
            '        const ns_xhtml = \'http://www.w3.org/1999/xhtml\';',
            '        const fo = document.createElementNS(svg_ns, \'foreignObject\');',
            '        fo.setAttribute(\'x\', p.x);',
            '        fo.setAttribute(\'y\', p.y);',
            '        fo.setAttribute(\'width\', NODO_W);',
            '        fo.setAttribute(\'height\', NODO_H);',
            '        fo.setAttribute(\'pointer-events\', \'none\');',
            '',
            '        const div_texto = document.createElementNS(ns_xhtml, \'div\');',
            '        div_texto.setAttribute(\'class\', \'grafo-nodo-texto\');',
            '',
            '        const div_tipo = document.createElementNS(ns_xhtml, \'div\');',
            '        div_tipo.setAttribute(\'class\', \'grafo-nodo-tipo\');',
            '        div_tipo.textContent = n.tipo || \'?\';',
            '        div_texto.appendChild(div_tipo);',
            '',
            '        if (n.dato) {',
            '            const div_dato = document.createElementNS(ns_xhtml, \'div\');',
            '            div_dato.setAttribute(\'class\', \'grafo-nodo-dato\');',
            '            div_dato.textContent = n.dato;',
            '            div_texto.appendChild(div_dato);',
            '        }',
            '',
            '        if (n.es_especial) {',
            '            const div_id = document.createElementNS(ns_xhtml, \'div\');',
            '            div_id.setAttribute(\'class\', \'grafo-nodo-id\');',
            '            div_id.textContent = n.id;',
            '            div_texto.appendChild(div_id);',
            '        }',
            '',
            '        fo.appendChild(div_texto);',
            '        g.appendChild(fo);',
            '',
            '        // Tooltip con el contenido completo.',
            '        const titulo_g = document.createElementNS(svg_ns, \'title\');',
            '        const partes_titulo = [n.tipo || \'?\'];',
            '        if (n.dato) partes_titulo.push(n.dato);',
            '        if (n.es_especial) partes_titulo.push(\'ID: \' + n.id);',
            '        titulo_g.textContent = partes_titulo.join(\' · \');',
            '        g.appendChild(titulo_g);',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: refs del nodo guardan fo, no textos.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Refs del nodo guardan foreignObject',
        'buscar' => [
            '        g_nodos.appendChild(g);',
            '        _grafo_visual_refs_nodos[n.id] = { g, rect, textos: [texto_tipo, texto_id] };',
        ],
        'reemplazar' => [
            '        g_nodos.appendChild(g);',
            '        _grafo_visual_refs_nodos[n.id] = { g, rect, fo };',
        ],
    ],

    // ============================================================
    // G) grafo_visual.js: drag actualiza rect + fo (no textos).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Drag actualiza rect + foreignObject',
        'buscar' => [
            '    Object.keys(_grafo_visual_refs_nodos).forEach(id => {',
            '        const ref = _grafo_visual_refs_nodos[id];',
            '        const p = posiciones[id];',
            '        if (!ref || !p) return;',
            '        ref.rect.setAttribute(\'x\', p.x);',
            '        ref.rect.setAttribute(\'y\', p.y);',
            '        if (ref.textos && ref.textos.length === 2) {',
            '            ref.textos[0].setAttribute(\'x\', p.x + 8);',
            '            ref.textos[0].setAttribute(\'y\', p.y + 22);',
            '            ref.textos[1].setAttribute(\'x\', p.x + 8);',
            '            ref.textos[1].setAttribute(\'y\', p.y + 46);',
            '        }',
            '    });',
        ],
        'reemplazar' => [
            '    Object.keys(_grafo_visual_refs_nodos).forEach(id => {',
            '        const ref = _grafo_visual_refs_nodos[id];',
            '        const p = posiciones[id];',
            '        if (!ref || !p) return;',
            '        ref.rect.setAttribute(\'x\', p.x);',
            '        ref.rect.setAttribute(\'y\', p.y);',
            '        if (ref.fo) {',
            '            ref.fo.setAttribute(\'x\', p.x);',
            '            ref.fo.setAttribute(\'y\', p.y);',
            '        }',
            '    });',
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