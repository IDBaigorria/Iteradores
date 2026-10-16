<?php
/**
 * Aplicador — Plan de la línea "Visualizador de grafo".
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80m';
$htmls_a_bumpear = [];

$cambios = [
    // Insertar la nueva sección §1.15 antes de §1.14.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.15: plan de la línea visualizador de grafo',
        'buscar' => [
            '### 1.14 Cierre de la línea del grafo del plugin (v8b-v8f)',
        ],
        'reemplazar' => [
            '### 1.15 Nueva línea: visualizador de grafo (v80m–)',
            '',
            '**Estado:** arrancando. Fase 1 en curso.',
            '',
            '**Motivación.** La pestaña Grafo hoy es una tabla.',
            'Funciona para leer datos, pero es imposible ver',
            'topología (quién apunta a quién, dónde hay cuellos,',
            'qué compartidos hay). El objetivo es sumar una vista',
            'visual en la misma pestaña, con nodos y flechas,',
            'navegable y con zoom.',
            '',
            '**Decisiones tomadas:**',
            '',
            '- La tabla se queda. Sub-pestañas **Tabla** / **Visual**.',
            '- SVG puro, sin librerías (CSP + cero dependencias).',
            '- Nodos: rectángulos redondeados. Círculo reservado',
            '  para raíces especiales.',
            '- Fondo: grilla con `<pattern>` de SVG.',
            '- Layout inicial: grilla ordenada por BFS desde las',
            '  raíces. Force-directed después, con toggle.',
            '- Flechas curvas (Bézier) con `<marker>`.',
            '- Colores por tipo inferido (`_grafo_inferir_tipo`',
            '  ya existe).',
            '- Al abrir: todas las raíces especiales + 1 nivel de',
            '  adyacentes.',
            '- Límite duro de 500 nodos en pantalla; si se supera,',
            '  avisar y cortar.',
            '- Panel lateral derecho para el detalle del nodo.',
            '- Filtro "todos / alcanzables / huérfanos", igual que',
            '  la tabla.',
            '- Reusar `grafo:nodo` para el detalle. Nuevo endpoint',
            '  `grafo/vecindario` para la carga inicial.',
            '',
            '**Nombres nuevos:**',
            '',
            '- CSS: `estilos-grafo.css`.',
            '- JS: `Aplicacion/grafo_visual.js`.',
            '- Endpoint: `grafo/vecindario`.',
            '',
            '**Fases:**',
            '',
            '- [ ] **Fase 1 — Esqueleto visual.**',
            '  Sub-pestañas Tabla/Visual. SVG con grilla de fondo.',
            '  Pan + zoom funcionales. Nodos como rectángulos',
            '  (sin flechas). Carga inicial de las raíces + 1 nivel',
            '  vía `grafo/vecindario`.',
            '  *Archivos:* `Aplicacion/grafo_visual.js` (nuevo),',
            '  `estilos-grafo.css` (nuevo), `aplicacion_GET.html`,',
            '  `Enrutador.php`, `Controlador.php` (comando nuevo).',
            '',
            '- [ ] **Fase 2 — Flechas y colores.**',
            '  Flechas curvas Bézier con `<marker>`. Colores por',
            '  tipo inferido. Raíces con borde destacado.',
            '',
            '- [ ] **Fase 3 — Interacción.**',
            '  Panel lateral con el detalle del nodo (usa',
            '  `grafo:nodo`). Doble click → centrar y expandir',
            '  vecinos. Buscador por texto / ID.',
            '',
            '- [ ] **Fase 4 — Layout alternativo.**',
            '  Force-directed con toggle. Botones "acomodar",',
            '  "reset zoom", "centrar".',
            '',
            '- [ ] **Fase 5 — Persistencia.**',
            '  Guardar posiciones, zoom y layout elegido. Dónde',
            '  persistir (localStorage del navegador o el grafo',
            '  del plugin) a decidir en su momento.',
            '',
            '**Pendientes de la línea:**',
            '',
            '- Cómo se comporta el visualizador con grafos grandes',
            '  (miles de nodos): decidir si el límite duro de 500',
            '  nodos se queda o se hace configurable.',
            '- Cómo se resalta la pertenencia a compartidos en la',
            '  vista visual (color distinto? borde punteado? icono?).',
            '- Interacción con `eliminar_huerfanos`: después de',
            '  borrar huérfanos, refrescar la vista visual.',
            '',
            '### 1.14 Cierre de la línea del grafo del plugin (v8b-v8f)',
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

// --- Fase 1: aplicar reemplazos ---
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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";