<?php
/**
 * Aplicador — Tanda 7: nota de cierre en plan_actual.md.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80l';

// El piloto no toca HTML en esta tanda.
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Nueva sección §1.14 en plan_actual.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.14: cierre de la línea del grafo del plugin',
        'buscar' => [
            '### 1.13 Cierre del arco del plugin (interfaz de usuario)',
        ],
        'reemplazar' => [
            '### 1.14 Cierre de la línea del grafo del plugin (v8b-v8f)',
            '',
            '**Estado:** cerrada. Plugin en v1.5plugin.8f.',
            '',
            '**Motivación.** El plugin guardaba cada corrida como un',
            'nodo suelto colgando de un contenedor plano (`corridas`).',
            'El estado "en vivo" (qué prueba corría ahora) vivía en',
            '`chrome.storage.session`, separado del historial. El',
            'popup no leía nada de eso: solo mostraba el resultado',
            'de la corrida actual, y se perdía todo al cerrar.',
            '',
            '**Qué se hizo.**',
            '',
            '- **v8b** — Rediseño de `GrafoPlugin.js`. Estructura',
            '  nueva: `plugin → pruebas → <id_prueba> → (hmi →',
            '  cadena de corridas con cierre)`. Cada prueba tiene',
            '  su propio mini-árbol con máximo 5 corridas',
            '  (`CORRIDAS_POR_PRUEBA_MAX`). El autoenlace',
            '  `corriendo` marca la prueba en ejecución. Se elimina',
            '  `chrome.storage.session`. `servicio.js` gana los',
            '  mensajes `estado_pruebas`, `corrida_iniciada`,',
            '  `corrida_terminada`. Migración automática desde el',
            '  contenedor viejo `corridas`.',
            '',
            '- **v8c** — UI del popup. Indicador de estado por fila',
            '  (última corrida persistida). Click despliega el',
            '  historial (hasta 5 corridas: resultado · fecha ·',
            '  duración · detalle). Refresco por broadcast.',
            '',
            '- **v8d** — Fix del import dinámico. `arranque.js`',
            '  usaba `await import("../Controlador/index.js")`,',
            '  prohibido en `ServiceWorkerGlobalScope` (MV3).',
            '  Síntoma: el SW se reiniciaba por inactividad y las',
            '  corridas dejaban de persistirse. Nuevo',
            '  `arranque_config.js` con la configuración como',
            '  efecto de lado, importado estáticamente ANTES del',
            '  Controlador. Los imports ES se evalúan depth-first',
            '  en orden de aparición, así que el orden importa.',
            '',
            '- **v8e** — Fix de iteración de `Map` en `GrafoPlugin`.',
            '  `Object.entries(x)` sobre un Map devuelve `[]`',
            '  silenciosamente. Afectaba a `obtener_estado_por_prueba`',
            '  (devolvía vacío y el popup veía todo `—`), a',
            '  `_podar_si_excede` (nunca podaba), a',
            '  `limpiar_autoenlaces_colgados` y a',
            '  `_destruir_contenedor_corridas_viejo`. Fix:',
            '  `for (const [k, v] of map)`.',
            '',
            '- **v8f** — Cierre de documentación (prompt del plugin',
            '  actualizado).',
            '',
            '**Lecciones aprendidas.**',
            '',
            '17. **`Object.entries(Map)` devuelve `[]`.** Para',
            '    iterar un Map, `for (const [k, v] of map)`.',
            '    `Nodo.adyacentes()` devuelve un Map; el bug es',
            '    silencioso (no error, simplemente no itera).',
            '',
            '18. **En MV3, todos los imports del service worker',
            '    tienen que ser estáticos.** Cualquier `await',
            '    import(...)` que aparezca en el grafo de',
            '    dependencias del SW rompe cuando el SW se',
            '    reinicia por inactividad (aunque las primeras',
            '    ejecuciones funcionen por timing).',
            '',
            '19. **Para configurar el framework antes de que un',
            '    módulo se auto-inicialice, usar un módulo',
            '    separado con la configuración como efecto de',
            '    lado, importado ANTES del módulo que la',
            '    consume.** Los imports ES se evalúan depth-first',
            '    en orden de aparición.',
            '',
            '**Pendientes que abre este cierre.**',
            '',
            '- **Persistencia de la pestaña activa del plugin.**',
            '  Sigue en `localStorage`. Migrarla a IndexedDB.',
            '  Anotado como `ANOTADO` en el código.',
            '- El resto de §4 (pendientes consolidados) sigue',
            '  vigente.',
            '',
            '### 1.13 Cierre del arco del plugin (interfaz de usuario)',
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