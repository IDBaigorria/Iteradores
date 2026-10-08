<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76j (cierre de la fase 2 del framework).
 *   - index.php: bump @version a 1.5piloto.76j.
 *   - prompts/prompt_piloto.md: registrar el cierre de la
 *     fase 2 del framework (SQL64 + IndexedDB64), actualizar
 *     §8.7 con el estado de los pasos del framework y con la
 *     deuda del Nodo limpio. Actualizar §12 y §13.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // index.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76j',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76h',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76j',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bloque "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76i',
            '(solo documentación. Se agrega §8.7 con el plan de',
            'contextos del piloto: dueños y tipos como IDs',
            'especiales, integración con el plan del framework',
            '§11.4, fases y preguntas abiertas.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76j',
            '(cierre de la fase 2 del framework. El framework',
            'quedó en 1.5i.7k: SQL64 en PHP e IndexedDB64 en JS,',
            'ambos cerrados y verificados. §8.7 se actualiza con',
            'el estado del framework (pasos 1-2 completados, más',
            'la deuda del Nodo limpio). §12 y §13 reflejan el',
            'cierre. Bump de `index.php` a `1.5piloto.76j`.).',
            'Antes: v1.5piloto.76i',
            '(solo documentación. Se agrega §8.7 con el plan de',
            'contextos del piloto: dueños y tipos como IDs',
            'especiales, integración con el plan del framework',
            '§11.4, fases y preguntas abiertas.).',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — historial, entrada v76j
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v76j al historial',
        'buscar' => [
            '- **v76i**: solo documentación. Se agrega la sección',
            '  §8.7 con el plan de contextos del piloto (dueños',
            '  y tipos como IDs especiales, integración con el',
            '  plan del framework §11.4). No hay cambios de código.',
        ],
        'reemplazar' => [
            '- **v76j**: cierre de la fase 2 del framework',
            '  (contextos). El framework PHP llegó a 1.5i.7k con',
            '  `PerdurarSuperestructuraStringSQL64` (3 tablas',
            '  nuevas, BFS multi-fuente, `cargar_parcial`); el',
            '  espejo JS llegó a la misma versión con',
            '  `PerdurarSuperestructuraStringIndexedDB64` (base',
            '  de datos separada `HyS_ctx`). Se agregó la',
            '  interfaz `PerdurarSuperestructuraConContexto` y',
            '  los métodos `cargar_parcial`, `guardar_parcial`',
            '  (stub), `listar_contextos` y `es_grafo_parcial`',
            '  en ambos `Controlador`. Fix del bug de tipos de',
            '  ID en el `Map` de `_superestructura` (JS):',
            '  normalización a `String(id)` de las claves y de',
            '  `Nodo.existe` / `Nodo.nodo_por_id`. Deuda de',
            '  diseño anotada: dejar el Nodo limpio antes de',
            '  agregar más métodos de indexación de contextos.',
            '  Ver §11.4 del prompt del framework. Solo',
            '  documentación del piloto + bump de `index.php`.',
            '- **v76i**: solo documentación. Se agrega la sección',
            '  §8.7 con el plan de contextos del piloto (dueños',
            '  y tipos como IDs especiales, integración con el',
            '  plan del framework §11.4). No hay cambios de código.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — §8.7 "Cuándo se implementa"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: actualizar "Cuándo se implementa"',
        'buscar' => [
            '**Cuándo se implementa.** El orden es:',
            '',
            '1. **Fase 1 del framework**: `SQL64` (bitmask + 3',
            '   tablas nuevas). Sin tocar el piloto.',
            '2. **Fase 2 del framework**: `IndexedDB64` (espejo).',
            '3. **Fase 3 del framework**: `JSON64` / `XML64`.',
            '4. **Cambio del piloto**: convertir dueños a IDs',
            '   especiales. Requiere migración de datos y de',
            '   código. Es una tanda grande.',
            '5. **Segundo cambio del piloto**: agregar los',
            '   `tipo_*` como IDs especiales.',
            '6. **Tercer cambio del piloto**: aprovechar la carga',
            '   parcial en las operaciones más frecuentes',
            '   (listar viajes, listar ventas, etc.).',
            '',
            'Los pasos 1-3 son del framework. Los pasos 4-6 son',
            'del piloto. Cada paso en su propia tanda, con sus',
            'dos scripts donde corresponda.',
        ],
        'reemplazar' => [
            '**Cuándo se implementa.** El orden es:',
            '',
            '1. **Fase 1 del framework**: `SQL64` (bitmask + 3',
            '   tablas nuevas). Sin tocar el piloto.',
            '   **Completado** (framework 1.5i.7k).',
            '2. **Fase 2 del framework**: `IndexedDB64` (espejo).',
            '   **Completado** (framework 1.5i.7k).',
            '3. **Fase 3 del framework**: `JSON64` / `XML64`.',
            '   Pendiente.',
            '4. **Refactor del framework: dejar el Nodo limpio.**',
            '   Antes de agregar más métodos de indexación de',
            '   contextos (256 bits, producto de primos, etc.),',
            '   hay que mover la máscara fuera del Nodo. Ver',
            '   §11.4 del prompt del framework. Pendiente,',
            '   bloqueante de la Fase 5.',
            '5. **Cambio del piloto**: convertir dueños a IDs',
            '   especiales. Requiere migración de datos y de',
            '   código. Es una tanda grande.',
            '6. **Segundo cambio del piloto**: agregar los',
            '   `tipo_*` como IDs especiales.',
            '7. **Tercer cambio del piloto**: aprovechar la carga',
            '   parcial en las operaciones más frecuentes',
            '   (listar viajes, listar ventas, etc.).',
            '',
            'Los pasos 1-4 son del framework. Los pasos 5-7 son',
            'del piloto. Cada paso en su propia tanda, con sus',
            'dos scripts donde corresponda.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — §12 Estado de la conversación
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullets del cierre de fase 2',
        'buscar' => [
            '  propio prompt (sección 11 nueva).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '  propio prompt (sección 11 nueva).',
            '- Cerramos en v76j el cierre de la fase 2 del framework',
            '  (contextos). El framework PHP llegó a 1.5i.7k con',
            '  `PerdurarSuperestructuraStringSQL64` completo (3 tablas',
            '  nuevas, BFS multi-fuente desde los IDs especiales,',
            '  `cargar_parcial` con filtro por bitmask). El espejo',
            '  JS llegó a la misma versión con',
            '  `PerdurarSuperestructuraStringIndexedDB64` (base de',
            '  datos separada `HyS_ctx`, mismos almacenes y misma',
            '  API). Se agregó la interfaz',
            '  `PerdurarSuperestructuraConContexto` en ambos lenguajes.',
            '  El `Controlador` (PHP y JS) ganó 4 métodos nuevos:',
            '  `cargar_parcial`, `guardar_parcial` (stub en fase 1-2),',
            '  `listar_contextos` y `es_grafo_parcial`. Nuevo flag',
            '  `$grafo_parcial` (PHP) / `_grafo_parcial` (JS) que',
            '  bloquea `guardar()` sobre grafo parcial.',
            '- Bug de tipos de ID en el `Map` de `_superestructura`',
            '  (JS) detectado y corregido: en JS, `Map.has("1")` y',
            '  `Map.has(1)` son distintos, a diferencia de PHP que',
            '  coerciona strings numéricos a int en claves de array.',
            '  Fix: normalizar a `String(id)` las claves de',
            '  `_superestructura` y `_nodos_especiales`, y normalizar',
            '  los lookups en `Nodo.existe` y `Nodo.nodo_por_id`.',
            '  Documentado en §12.3 del prompt del framework.',
            '- Deuda de diseño anotada en §11.4 del prompt del',
            '  framework: **dejar el Nodo limpio** antes de agregar',
            '  más métodos de indexación de contextos (256 bits,',
            '  producto de primos, etc.). Hoy el campo',
            '  `contexto_mascara` / `_contexto_mascara` vive en el',
            '  Nodo; el objetivo es moverlo a una estructura auxiliar',
            '  de la capa de persistencia. Bloqueante de la Fase 5',
            '  del framework.',
            '- Cerramos en v76j el cierre formal del piloto: bump de',
            '  `index.php` a `1.5piloto.76j` y actualización de este',
            '  prompt. Sin cambios de código de aplicación.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — §13 "Estado del proyecto al cierre"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: actualizar estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76i (framework 1.5i.7j).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76j (framework 1.5i.7k).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76j al bloque de estado acumulado',
        'buscar' => [
            'v76h: planilla de pasajeros "vacía" (parámetro `$vacia`',
            'en `imprimir_planilla_pasajeros_micro` + botón en el',
            'croquis del micro).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5w)',
            'tiene 56 pruebas corriendo.',
        ],
        'reemplazar' => [
            'v76h: planilla de pasajeros "vacía" (parámetro `$vacia`',
            'en `imprimir_planilla_pasajeros_micro` + botón en el',
            'croquis del micro).',
            'v76j: cierre de la fase 2 del framework (contextos).',
            'El framework quedó en 1.5i.7k, con SQL64 (PHP) y',
            'IndexedDB64 (JS) completos. Deuda de diseño anotada:',
            'dejar el Nodo limpio antes de agregar más métodos',
            'de indexación de contextos (ver §11.4 del prompt del',
            'framework).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5w)',
            'tiene 56 pruebas corriendo.',
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