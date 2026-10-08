<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76o (solo documentación).
 *   - prompts/prompt_framework_iteradores.md: historial 1.5i.7l
 *     con los pendientes del espejo JS y la deuda del
 *     grafo:crear_niveles_usuario.
 *   - prompts/prompt_piloto.md: historial v76o con el mismo
 *     registro.
 *
 * Sin cambios de código.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // prompts/prompt_framework_iteradores.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: historial 1.5i.7l',
        'buscar' => [
            '  `Nodo.nodo_por_id`, para replicar el comportamiento de PHP.',
            '  Ver §12.3 y §11.4.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
        'reemplazar' => [
            '  `Nodo.nodo_por_id`, para replicar el comportamiento de PHP.',
            '  Ver §12.3 y §11.4.',
            '',
            '- **1.5i.7l**: comando `grafo:raices` agregado al framework',
            '  PHP (tanda 76n) y espejado en JS. Lista los nodos raíz',
            '  del grafo (IDs especiales) con sus adyacentes directos.',
            '  Pendientes anotados para resolver en una tanda futura',
            '  (no bloquean el trabajo actual):',
            '',
            '  - **Espejar `grafo:reemplazar_referencias` en JS.**',
            '    Se agregó al PHP en la tanda 76k (recibe un mapa',
            '    `{viejo → nuevo}` y redirige las aristas cruzadas).',
            '    Es genérico, no específico del piloto, así que',
            '    corresponde espejarlo al `Controlador.js`.',
            '  - **Mover `grafo:crear_niveles_usuario` fuera del',
            '    framework.** Es un comando específico del piloto',
            '    (crea contenedores `publico` y `privado` en cada',
            '    nodo usuario). Debe levantarse al vuelo desde la',
            '    app (con `Controlador::registrar_comando(...)`',
            '    invocado desde `Aplicacion/Migraciones/Comandos.php`',
            '    o similar), no vivir en el `Controlador` del',
            '    framework. Hoy está en el framework como deuda',
            '    técnica.',
            '  - **No se pudo verificar `grafo:raices` desde la consola',
            '    del service worker del plugin.** MV3 prohíbe',
            '    `import()` dinámico en `ServiceWorkerGlobalScope`',
            '    (error: *"import() is disallowed on',
            '    ServiceWorkerGlobalScope by the HTML specification"*).',
            '    La verificación se hizo desde el piloto PHP, donde la',
            '    pestaña Grafo invoca el comando vía el enrutador. Si',
            '    se quiere verificar desde el plugin, hay que exponer',
            '    `globalThis.Controlador = Controlador` en el bootstrap',
            '    del service worker (práctica habitual para debug',
            '    desde la consola del SW en MV3).',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 (estado de la conversación)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar bullet v76o',
        'buscar' => [
            '- Cerramos en v76n la pestaña Grafo ampliada y el sistema',
        ],
        'reemplazar' => [
            '- Cerramos en v76o con documentación. Se dejan asentados',
            '  tres pendientes sobre el framework, sin tocar código:',
            '  (1) espejar `grafo:reemplazar_referencias` en el',
            '  `Controlador.js` (se agregó al PHP en 76k, es genérico);',
            '  (2) mover `grafo:crear_niveles_usuario` fuera del',
            '  `Controlador` del framework: es específico del piloto',
            '  y debe levantarse al vuelo desde la app;',
            '  (3) no se pudo verificar `grafo:raices` desde la consola',
            '  del service worker del plugin, porque MV3 prohíbe',
            '  `import()` dinámico en `ServiceWorkerGlobalScope`. La',
            '  verificación se hizo desde el piloto PHP vía la pestaña',
            '  Grafo. Si se quiere verificar desde el plugin, hay que',
            '  exponer `globalThis.Controlador = Controlador` en el',
            '  bootstrap del SW. Pendiente anotado, sin urgencia.',
            '- Cerramos en v76n la pestaña Grafo ampliada y el sistema',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76o',
        'buscar' => [
            '- **v76n**: pestaña Grafo ampliada con dos secciones nuevas',
        ],
        'reemplazar' => [
            '- **v76o**: solo documentación. Se dejan asentados tres',
            '  pendientes sobre el framework, sin tocar código:',
            '  (1) espejar `grafo:reemplazar_referencias` en el',
            '  `Controlador.js`;',
            '  (2) mover `grafo:crear_niveles_usuario` fuera del',
            '  `Controlador` del framework (es específico del piloto,',
            '  debe levantarse al vuelo desde la app);',
            '  (3) `grafo:raices` no se pudo verificar desde la consola',
            '  del SW del plugin por limitación de MV3 (`import()`',
            '  dinámico prohibido). La verificación se hizo desde el',
            '  piloto PHP. Si se quiere verificar desde el plugin,',
            '  exponer `globalThis.Controlador = Controlador` en el',
            '  bootstrap del SW.',
            '- **v76n**: pestaña Grafo ampliada con dos secciones nuevas',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: §13 estado al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76n (framework 1.5i.7k).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76o (framework 1.5i.7l).',
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