<?php
/**
 * Aplicador de cambios — Fix del test de contextos.
 *
 * Tanda V1.5i.7k (corrección): reescribir prueba_contextos.php
 * para que los hijos tengan IDs normales y solo los roots
 * sean contextos. Documentar la convención en el prompt.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'crear',
        'archivo' => 'Pruebas/prueba_contextos.php',
        'descripcion' => 'Reescribir test de contextos',
        'contenido' => [
            '<?php',
            '/**',
            ' * Prueba funcional de la fase 1 de contextos (SQL64).',
            ' *',
            ' * Se ejecuta con ?probar_contextos=1 desde index.php.',
            ' *',
            ' * Convención: todo ID especial es un contexto. Por eso',
            ' * acá los roots (ctx_a, ctx_b) son especiales, y los',
            ' * nodos hijos tienen IDs normales (no especiales).',
            ' *',
            ' * @since 1.5i.7k',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            '',
            'header(\'Content-Type: text/plain; charset=utf-8\');',
            'echo "=== Prueba de contextos (SQL64) ===\\n\\n";',
            '',
            '$nombre_test = \'TestContextos_\' . date(\'YmdHis\');',
            '$res = [];',
            '',
            'Controlador::establecer_metodo(\'SQL64\');',
            '',
            '// --- 1. Crear grafo ---',
            '// ctx_a y ctx_b son IDs especiales → contextos.',
            '// n1, n2, n3 son IDs normales → no son contextos.',
            'Controlador::ejecutar_prueba(function($token) use (&$res) {',
            '    Nodo::vaciar_superestructura($token);',
            '    $raiz_a = Nodo::crear_con_id(\'ctx_a\');',
            '    $raiz_b = Nodo::crear_con_id(\'ctx_b\');',
            '    $n1 = Nodo::crear_con_dato(\'nodo1\');',
            '    $n2 = Nodo::crear_con_dato(\'nodo2\');',
            '    $n3 = Nodo::crear_con_dato(\'nodo3\');',
            '    $raiz_a->_adyacente_en($n1, \'hijo\');',
            '    $raiz_b->_adyacente_en($n2, \'hijo\');',
            '    $n1->_adyacente_en($n3, \'compartido\');',
            '    $n2->_adyacente_en($n3, \'compartido\');',
            '});',
            '',
            '// --- 2. Guardar y cargar completo ---',
            '$res[\'guardar\'] = Controlador::guardar($nombre_test);',
            '$res[\'cargar\'] = Controlador::cargar($nombre_test);',
            '',
            '// --- 3. Verificar máscaras ---',
            '// n3 es alcanzable desde ctx_a Y desde ctx_b, entonces',
            '// su máscara debe ser 3 (bits 0 y 1).',
            'Controlador::ejecutar_prueba(function($token) use (&$res) {',
            '    $raiz_a = Nodo::nodo_por_id(\'ctx_a\');',
            '    $raiz_b = Nodo::nodo_por_id(\'ctx_b\');',
            '    $n1 = $raiz_a ? $raiz_a->adyacente(\'hijo\') : null;',
            '    $n2 = $raiz_b ? $raiz_b->adyacente(\'hijo\') : null;',
            '    $n3 = $n1 ? $n1->adyacente(\'compartido\') : null;',
            '    $res[\'mascara_ctx_a\'] = $raiz_a ? $raiz_a->contexto_mascara() : \'NO\';',
            '    $res[\'mascara_ctx_b\'] = $raiz_b ? $raiz_b->contexto_mascara() : \'NO\';',
            '    $res[\'mascara_n1\'] = $n1 ? $n1->contexto_mascara() : \'NO\';',
            '    $res[\'mascara_n2\'] = $n2 ? $n2->contexto_mascara() : \'NO\';',
            '    $res[\'mascara_n3\'] = $n3 ? $n3->contexto_mascara() : \'NO\';',
            '});',
            '',
            '// --- 4. Listar contextos ---',
            '$res[\'contextos\'] = Controlador::listar_contextos($nombre_test);',
            '',
            '// --- 5. Cargar parcial por ctx_a ---',
            '$res[\'cargar_parcial\'] = Controlador::cargar_parcial($nombre_test, [\'ctx_a\']);',
            '$res[\'es_parcial\'] = Controlador::es_grafo_parcial();',
            '',
            'Controlador::ejecutar_prueba(function($token) use (&$res) {',
            '    $raiz_a = Nodo::nodo_por_id(\'ctx_a\');',
            '    $n1 = $raiz_a ? $raiz_a->adyacente(\'hijo\') : null;',
            '    $n3 = $n1 ? $n1->adyacente(\'compartido\') : null;',
            '    $res[\'tiene_ctx_a_tras_parcial\'] = Nodo::existe(\'ctx_a\');',
            '    $res[\'tiene_ctx_b_tras_parcial\'] = Nodo::existe(\'ctx_b\');',
            '    $res[\'tiene_n1_tras_parcial\'] = ($n1 !== null);',
            '    $res[\'tiene_n3_tras_parcial\'] = ($n3 !== null);',
            '});',
            '',
            '// --- 6. Guardar completo sobre parcial (debe fallar) ---',
            '$res[\'guardar_sobre_parcial\'] = Controlador::guardar($nombre_test);',
            '',
            '// --- 7. Limpieza ---',
            '$res[\'eliminar\'] = Controlador::eliminar($nombre_test);',
            '',
            'echo "--- Resultados ---\\n";',
            'foreach ($res as $k => $v) {',
            '    echo $k . " => " . var_export($v, true) . "\\n";',
            '}',
            'echo "\\n--- Fin ---\\n";',
            '?>',
        ],
    ],

    // ============================================================
    // Prompt del framework: agregar nota sobre la convención
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => '§11.4: aclarar "todo ID especial es contexto"',
        'buscar' => [
            '**Definición de contexto.** Un **contexto** es un ID',
            'especial del grafo (nodo con ID no numérico) que actúa',
            'como raíz. Cualquier nodo alcanzable desde ese ID',
            'especial pertenece a ese contexto. El framework no',
            'distingue la semántica de un contexto (usuarios,',
            'sesiones, tipos, dueños, etc.): todos son contextos por',
            'igual. Esta abstracción es la clave del diseño: el',
            'framework solo entiende "contextos".',
        ],
        'reemplazar' => [
            '**Definición de contexto.** Un **contexto** es un ID',
            'especial del grafo (nodo con ID no numérico) que actúa',
            'como raíz. Cualquier nodo alcanzable desde ese ID',
            'especial pertenece a ese contexto. El framework no',
            'distingue la semántica de un contexto (usuarios,',
            'sesiones, tipos, dueños, etc.): todos son contextos por',
            'igual. Esta abstracción es la clave del diseño: el',
            'framework solo entiende "contextos".',
            '',
            '**Todo ID especial es contexto.** No hay exclusiones.',
            'Si un nodo tiene ID especial, es contexto por',
            'definición. Los nodos que cuelgan de él, si tienen ID',
            'normal (generado), **no son contextos**: solo heredan',
            'los bits de los contextos que los alcanzan. Si más',
            'adelante hace falta un ID especial que NO sea contexto,',
            'se agrega una lista de exclusión en `ConfiguracionApli`',
            '(pendiente, sin caso de uso actual).',
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
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
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