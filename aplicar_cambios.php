<?php
/**
 * Aplicador — Tanda P: ID con # y tipo inferido por enlaces entrantes.
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
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81b"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81c"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de estilos-grafo.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81b">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81c">',
        ],
    ],

    // ============================================================
    // B) estilos-grafo.css: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Bump @version de estilos-grafo.css',
        'buscar' => [
            ' * @version 1.5piloto.81b',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81c',
        ],
    ],

    // ============================================================
    // C) plan_actual: anotar la mejora.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v81c',
        'buscar' => [
            '- Contenido del nodo: tipo arriba, dato abajo, ID solo',
            '  si es especial. Usa `<foreignObject>` con HTML',
            '  adentro, para tener `text-overflow: ellipsis` real',
            '  (sin calcular el ancho del texto a mano). El',
            '  contenido completo aparece en un `<title>` (tooltip',
            '  nativo de SVG) al hover. El drag ahora mueve rect',
            '  + foreignObject (sin tocar textos individuales).',
        ],
        'reemplazar' => [
            '- Contenido del nodo: tipo arriba, dato en el medio,',
            '  ID abajo (con `#` adelante, para todos los nodos).',
            '  Usa `<foreignObject>` con HTML adentro, para tener',
            '  `text-overflow: ellipsis` real (sin calcular el',
            '  ancho del texto a mano). El contenido completo',
            '  aparece en un `<title>` (tooltip nativo de SVG) al',
            '  hover. El drag mueve rect + foreignObject.',
            '- Inferencia de tipo en el frontend. Si el backend',
            '  devuelve `?`, se mira el enlace entrante:',
            '  - Un solo enlace entrante → ese nombre es el tipo.',
            '  - Varios → el más repetido.',
            '  - Empate → `?`.',
            '  Se aplica en `_grafo_visual_recalcular_tipos`, que',
            '  se llama al cargar el vecindario y al expandir. El',
            '  panel lateral usa el tipo local si lo tiene.',
            '  **Nota:** los tipos inferidos por enlace (por',
            '  ejemplo, `usuarios`, `empresas`, `viajes`) no',
            '  tienen color propio en el CSS; caen a gris.',
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
            ' * @version 1.5piloto.81b',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81c',
        ],
    ],

    // ============================================================
    // E) grafo_visual.js: llamar a recalcular_tipos al cargar.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Recalcular tipos al cargar el vecindario',
        'buscar' => [
            '    _grafo_visual_datos = datos.vecindario;',
            '    _grafo_visual_seleccionado = null;',
            '    _grafo_visual_matches = [];',
            '    _grafo_visual_match_actual = -1;',
            '    _grafo_visual_posiciones = {};',
            '    _renderizar_grafo_visual(null, { recalcular: true });',
            '}',
        ],
        'reemplazar' => [
            '    _grafo_visual_datos = datos.vecindario;',
            '    _grafo_visual_seleccionado = null;',
            '    _grafo_visual_matches = [];',
            '    _grafo_visual_match_actual = -1;',
            '    _grafo_visual_posiciones = {};',
            '    _grafo_visual_recalcular_tipos(_grafo_visual_datos);',
            '    _renderizar_grafo_visual(null, { recalcular: true });',
            '}',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: agregar la función de inferencia.
    //    Se inserta antes de _conectar_subtabs_grafo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Función de inferencia de tipo por enlaces entrantes',
        'buscar' => [
            'function _conectar_subtabs_grafo() {',
            '    const subtabs = document.querySelectorAll(\'#grafo_subtabs .grafo-subtab\');',
        ],
        'reemplazar' => [
            '/**',
            ' * Recalcula los tipos de los nodos a partir de los enlaces',
            ' * entrantes. Se aplica solo a los nodos cuyo tipo del',
            ' * backend sea "?" o vacío.',
            ' *',
            ' * Reglas:',
            ' *   - Un solo enlace entrante → ese nombre es el tipo.',
            ' *   - Varios enlaces distintos → el más repetido.',
            ' *   - Empate en la cantidad máxima → "?".',
            ' */',
            'function _grafo_visual_recalcular_tipos(datos) {',
            '    if (!datos || !datos.nodos) return;',
            '',
            '    // Contar enlaces entrantes por (destino, enlace).',
            '    const entrantes_por_id = {};',
            '    (datos.enlaces || []).forEach(e => {',
            '        if (!entrantes_por_id[e.destino]) entrantes_por_id[e.destino] = {};',
            '        entrantes_por_id[e.destino][e.enlace] =',
            '            (entrantes_por_id[e.destino][e.enlace] || 0) + 1;',
            '    });',
            '',
            '    (datos.nodos || []).forEach(n => {',
            '        const tipo_backend = String(n.tipo || \'\').trim();',
            '        if (tipo_backend !== \'\' && tipo_backend !== \'?\') return;',
            '',
            '        const cuenta = entrantes_por_id[n.id] || {};',
            '        const enlaces = Object.keys(cuenta);',
            '',
            '        if (enlaces.length === 0) {',
            '            n.tipo = \'?\';',
            '            return;',
            '        }',
            '        if (enlaces.length === 1) {',
            '            n.tipo = enlaces[0];',
            '            return;',
            '        }',
            '',
            '        let max = 0;',
            '        for (const k of enlaces) {',
            '            if (cuenta[k] > max) max = cuenta[k];',
            '        }',
            '        const con_max = enlaces.filter(k => cuenta[k] === max);',
            '        n.tipo = (con_max.length === 1) ? con_max[0] : \'?\';',
            '    });',
            '}',
            '',
            'function _conectar_subtabs_grafo() {',
            '    const subtabs = document.querySelectorAll(\'#grafo_subtabs .grafo-subtab\');',
        ],
    ],

    // ============================================================
    // G) grafo_visual.js: ID siempre visible con # adelante.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'ID visible para todos los nodos, con # adelante',
        'buscar' => [
            '        if (n.es_especial) {',
            '            const div_id = document.createElementNS(ns_xhtml, \'div\');',
            '            div_id.setAttribute(\'class\', \'grafo-nodo-id\');',
            '            div_id.textContent = n.id;',
            '            div_texto.appendChild(div_id);',
            '        }',
        ],
        'reemplazar' => [
            '        const div_id = document.createElementNS(ns_xhtml, \'div\');',
            '        div_id.setAttribute(\'class\', \'grafo-nodo-id\');',
            '        div_id.textContent = \'#\' + n.id;',
            '        div_texto.appendChild(div_id);',
        ],
    ],

    // ============================================================
    // H) grafo_visual.js: tooltip con ID siempre.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Tooltip con ID siempre',
        'buscar' => [
            '        const titulo_g = document.createElementNS(svg_ns, \'title\');',
            '        const partes_titulo = [n.tipo || \'?\'];',
            '        if (n.dato) partes_titulo.push(n.dato);',
            '        if (n.es_especial) partes_titulo.push(\'ID: \' + n.id);',
            '        titulo_g.textContent = partes_titulo.join(\' · \');',
            '        g.appendChild(titulo_g);',
        ],
        'reemplazar' => [
            '        const titulo_g = document.createElementNS(svg_ns, \'title\');',
            '        const partes_titulo = [n.tipo || \'?\'];',
            '        if (n.dato) partes_titulo.push(n.dato);',
            '        partes_titulo.push(\'ID: \' + n.id);',
            '        titulo_g.textContent = partes_titulo.join(\' · \');',
            '        g.appendChild(titulo_g);',
        ],
    ],

    // ============================================================
    // I) grafo_visual.js: recalcular tipos al expandir.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Recalcular tipos al expandir',
        'buscar' => [
            '    if (agregados > 0) {',
            '        mostrar_aviso(\'Agregados \' + agregados + \' nodo(s)\', \'exito\');',
            '        _grafo_visual_asentar_vecinos(id);',
            '        _renderizar_grafo_visual(vb_antes);',
            '        _grafo_visual_animar_centrar_en(id);',
            '    } else {',
        ],
        'reemplazar' => [
            '    if (agregados > 0) {',
            '        mostrar_aviso(\'Agregados \' + agregados + \' nodo(s)\', \'exito\');',
            '        _grafo_visual_recalcular_tipos(datos);',
            '        _grafo_visual_asentar_vecinos(id);',
            '        _renderizar_grafo_visual(vb_antes);',
            '        _grafo_visual_animar_centrar_en(id);',
            '    } else {',
        ],
    ],

    // ============================================================
    // J) grafo_visual.js: panel lateral usa tipo local.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Panel lateral usa tipo inferido local',
        'buscar' => [
            '    let detalle;',
            '    try {',
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "grafo/nodo",',
            '                nombre_solicitante: usuario_actual.nombre_usuario,',
            '                id: id',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '        if (!datos.exito) {',
            '            panel.innerHTML = \'<div class="grafo-panel-error">Error: \'',
            '                + _grafo_visual_escape(datos.error || \'\') + \'</div>\';',
            '            return;',
            '        }',
            '        detalle = datos.nodo;',
            '    } catch (e) {',
            '        panel.innerHTML = \'<div class="grafo-panel-error">Error de comunicación</div>\';',
            '        return;',
            '    }',
            '',
            '    _grafo_visual_renderizar_panel(detalle);',
            '}',
        ],
        'reemplazar' => [
            '    let detalle;',
            '    try {',
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "grafo/nodo",',
            '                nombre_solicitante: usuario_actual.nombre_usuario,',
            '                id: id',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '        if (!datos.exito) {',
            '            panel.innerHTML = \'<div class="grafo-panel-error">Error: \'',
            '                + _grafo_visual_escape(datos.error || \'\') + \'</div>\';',
            '            return;',
            '        }',
            '        detalle = datos.nodo;',
            '    } catch (e) {',
            '        panel.innerHTML = \'<div class="grafo-panel-error">Error de comunicación</div>\';',
            '        return;',
            '    }',
            '',
            '    // Usar el tipo local (inferido) si lo tenemos.',
            '    const nodo_local = (_grafo_visual_datos && _grafo_visual_datos.nodos || [])',
            '        .find(x => x.id === id);',
            '    if (nodo_local && nodo_local.tipo) {',
            '        detalle.tipo = nodo_local.tipo;',
            '    }',
            '',
            '    _grafo_visual_renderizar_panel(detalle);',
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