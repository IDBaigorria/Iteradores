<?php
/**
 * Aplicador — Tanda Q: default Visual + default Forzar.
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
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo_visual.js?v=1.5piloto.81d"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de grafo.js',
        'buscar' => [
            '<script src="Aplicacion/grafo.js?v=1.5piloto.80r"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo.js?v=1.5piloto.81d"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de estilos-grafo.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81c">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-grafo.css?v=1.5piloto.81d">',
        ],
    ],

    // ============================================================
    // B) HTML: sub-pestaña Visual activa por defecto.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Sub-pestaña Visual activa por defecto',
        'buscar' => [
            '        <div class="grafo-subtabs" id="grafo_subtabs">',
            '          <button type="button" class="grafo-subtab active" data-vista="tabla">Tabla</button>',
            '          <button type="button" class="grafo-subtab" data-vista="visual">Visual</button>',
            '        </div>',
        ],
        'reemplazar' => [
            '        <div class="grafo-subtabs" id="grafo_subtabs">',
            '          <button type="button" class="grafo-subtab" data-vista="tabla">Tabla</button>',
            '          <button type="button" class="grafo-subtab active" data-vista="visual">Visual</button>',
            '        </div>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Ocultar Tabla por defecto',
        'buscar' => [
            '        <div id="grafo_vista_tabla">',
        ],
        'reemplazar' => [
            '        <div id="grafo_vista_tabla" class="hidden">',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Mostrar Visual por defecto',
        'buscar' => [
            '        <div id="grafo_vista_visual" class="hidden">',
        ],
        'reemplazar' => [
            '        <div id="grafo_vista_visual">',
        ],
    ],

    // ============================================================
    // C) estilos-grafo.css: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-grafo.css',
        'descripcion' => 'Bump @version de estilos-grafo.css',
        'buscar' => [
            ' * @version 1.5piloto.81c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81d',
        ],
    ],

    // ============================================================
    // D) grafo.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'Bump @version de grafo.js',
        'buscar' => [
            ' * @version 1.5piloto.80r',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81d',
        ],
    ],

    // ============================================================
    // E) grafo.js: disparar carga del visual al entrar a Grafo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'Cargar visual al entrar a la pestaña Grafo',
        'buscar' => [
            '    _grafo_renderizar_resumen(datos_res.resumen);',
            '    await _grafo_cargar_tabla(0);',
            '    await cargar_grafo_raices();',
            '    await cargar_grafo_migraciones();',
            '}',
        ],
        'reemplazar' => [
            '    _grafo_renderizar_resumen(datos_res.resumen);',
            '    await _grafo_cargar_tabla(0);',
            '    await cargar_grafo_raices();',
            '    await cargar_grafo_migraciones();',
            '',
            '    // La sub-pestaña Visual es la activa por defecto.',
            '    // Cargar el visual si todavía no se cargó, para que el',
            '    // usuario la vea sin tener que hacer click en la',
            '    // sub-pestaña.',
            '    if (typeof cargar_grafo_visual === \'function\'',
            '        && typeof grafo_visual_esta_cargado === \'function\') {',
            '        const v_visual = document.getElementById(\'grafo_vista_visual\');',
            '        if (v_visual && !v_visual.classList.contains(\'hidden\')',
            '            && !grafo_visual_esta_cargado()) {',
            '            cargar_grafo_visual();',
            '        }',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // F) grafo_visual.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Bump @version de grafo_visual.js',
        'buscar' => [
            ' * @version 1.5piloto.81c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81d',
        ],
    ],

    // ============================================================
    // G) grafo_visual.js: layout default 'force'.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Layout por defecto: force',
        'buscar' => [
            'let _grafo_visual_layout = \'grilla\'; // \'grilla\' | \'force\'',
        ],
        'reemplazar' => [
            'let _grafo_visual_layout = \'force\'; // \'grilla\' | \'force\'',
        ],
    ],

    // ============================================================
    // H) grafo_visual.js: texto del botón Force → Forzar.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Botón Force renombrado a Forzar',
        'buscar' => [
            '            \'  <button type="button" data-layout="force">Force</button>\' +',
        ],
        'reemplazar' => [
            '            \'  <button type="button" data-layout="force">Forzar</button>\' +',
        ],
    ],

    // ============================================================
    // I) grafo_visual.js: getter grafo_visual_esta_cargado.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo_visual.js',
        'descripcion' => 'Getter grafo_visual_esta_cargado',
        'buscar' => [
            '    _grafo_visual_recalcular_tipos(_grafo_visual_datos);',
            '    _renderizar_grafo_visual(null, { recalcular: true });',
            '}',
            '',
            '/**',
            ' * Recalcula los tipos de los nodos a partir de los enlaces',
        ],
        'reemplazar' => [
            '    _grafo_visual_recalcular_tipos(_grafo_visual_datos);',
            '    _renderizar_grafo_visual(null, { recalcular: true });',
            '}',
            '',
            '/**',
            ' * Devuelve true si el visualizador ya tiene datos cargados.',
            ' * Lo usa grafo.js para decidir si hay que disparar la',
            ' * carga al entrar a la pestaña Grafo (la sub-pestaña',
            ' * Visual es la activa por defecto).',
            ' */',
            'function grafo_visual_esta_cargado() {',
            '    return _grafo_visual_datos !== null;',
            '}',
            '',
            '/**',
            ' * Recalcula los tipos de los nodos a partir de los enlaces',
        ],
    ],

    // ============================================================
    // J) plan_actual: anotar el ajuste.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.15: nota v81d',
        'buscar' => [
            '- Inferencia de tipo en el frontend. Si el backend',
        ],
        'reemplazar' => [
            '- Default de la pestaña Grafo: sub-pestaña Visual (antes',
            '  Tabla). Layout por defecto: Forzar (force-directed,',
            '  antes Grilla). Al entrar a la pestaña Grafo,',
            '  `grafo.js` dispara `cargar_grafo_visual()` si la',
            '  sub-pestaña Visual está activa y el visual no se',
            '  cargó todavía (vía `grafo_visual_esta_cargado()`).',
            '  El botón del toggle dice "Forzar" (antes "Force");',
            '  el `data-layout="force"` interno no cambia.',
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