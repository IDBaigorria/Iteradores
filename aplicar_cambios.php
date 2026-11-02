<?php
/**
 * Aplicador — v83s: eliminar §8 (histórico v82d) del
 * plan_actual.md + borrar el archivo obsoleto
 * plan_auditar_topologia.md.
 *
 * Solo toca prompts. No toca código.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Header: bump a v83s + mención de §8 eliminado
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'header: bump a v83s + §8 eliminado',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83q + framework 1.5i.7x',
        '(**Validado en producción:** una semana en pruebas y',
        'una semana en producción sin errores visibles ni',
        '`[error]` en el log de persistencia. Todos los guardados',
        'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
        '(lock, usuario en log, cobertura de `guardar_ambos`,',
        'returns tempranos, login optimizado) confirmada en',
        'producción. Grupo A de limpieza aplicado (v83q). Ver',
        '§2 y §4.8).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83s + framework 1.5i.7x',
        '(**Validado en producción:** una semana en pruebas y',
        'una semana en producción sin errores visibles ni',
        '`[error]` en el log de persistencia. Todos los guardados',
        'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
        '(lock, usuario en log, cobertura de `guardar_ambos`,',
        'returns tempranos, login optimizado) confirmada en',
        'producción. Grupo A de limpieza aplicado (v83q). §8',
        '(nota de cierre de v82d) eliminado en v83s: cumplió su',
        'función, los pendientes reales viven en §4.1-4.4 y',
        '§4.8. Ver §2 y §4.8).',
    ],
],

// ============================================================
// 2. Línea de "Deuda técnica del arco" del cierre
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'cierre: actualizar línea de deuda del arco',
    'buscar' => [
        '**Deuda técnica del arco** en §4.7: endpoints temporales',
        'de diagnóstico, espejo `_hmi` a JS, espejo C.7-C.10 a JS,',
        '`VERSION_APP` en pruebas, `error_log` de diagnóstico.',
    ],
    'reemplazar' => [
        '**Deuda técnica del arco** en §4.8: endpoints temporales',
        'de diagnóstico, espejo `_hmi` a JS, espejo C.7-C.10 a JS.',
        '(`VERSION_APP` y `error_log` de diagnóstico ya cerrados',
        'en v83q. El bug §4.7 ya no es deuda: está cerrado y',
        'validado en producción.)',
    ],
],

// ============================================================
// 3. §8 completo: borrar (título + 8.1 + 8.2 + 8.3 + 8.4 + 8.5)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => '§8: eliminar histórico v82d',
    'buscar' => [
        '## 8. NOTA DE CIERRE — v1.5piloto.82d',
        '',
        'Documento de retomada del arco "cancelaciones en compartidos',
        '+ persistencia robusta en hostings compartidos". Se puede',
        'borrar cuando la próxima sesión haya revisado los pendientes.',
        '',
        '### 8.1 Estado verificado',
        '',
        'En pruebas (InfinityFree):',
        '',
        '  - guardar_total.ok:        true, 551ms',
        '  - guardar_parcial.ok:      true, 592ms',
        '  - Cancelación desde carmen2: persiste',
        '  - Auditoría topológica:    0 críticos / 0 errores / 0 warnings',
        '',
        'Sin bugs abiertos de prioridad alta.',
        '',
        '### 8.2 Pendientes por prioridad',
        '',
        '**Media (deuda del arco):**',
        '- Espejar `_hmi` a JS (`iteradoresJS/miscelaneas/Arbol.js`).',
        '- Espejar C.7-C.10 a JS (`PerdurarSuperestructuraStringIndexedDB64.js`).',
        '- Limpieza de endpoints temporales: `?diag_persistencia=1`,',
        '  `?diagnostico_cancelaciones=1`, `error_log(...)` en SQL base.',
        '- `\\ConfiguracionApli::VERSION_APP` devuelve "0.0.0" en pruebas.',
        '',
        '**Baja (futuro):**',
        '- Framework Fase 3/4/5 (§5.2).',
        '- Iteradores con ID especial + ocupante (§5.2).',
        '- Auditar estados colgados (§4.1).',
        '- Deuda de seguridad en impresión (§4.1).',
        '- Fusionar las dos §11 de `prompt_plugin_piloto.md`.',
        '',
        '### 8.3 Aprendizajes clave (compactos)',
        '',
        '1. **PHP 8.4 rompe `mysqli_report` y `mysqli_ping`.**',
        '   `MYSQLI_REPORT_STRICT` es default: cualquier fallo lanza',
        '   excepción. El `@` no la atrapa. Usar `mysqli_report(OFF)`',
        '   + `SELECT 1` en lugar de `ping()`.',
        '',
        '2. **Hostings compartidos tienen privilegios limitados.**',
        '   `CREATE TEMPORARY TABLES`, `SET SESSION wait_timeout`,',
        '   rate limit de conexiones. Solución: singleton de conexión,',
        '   `IN (...)` directo, `SET SESSION wait_timeout = 300`.',
        '   **Testear siempre en el hosting real antes de dar un fix',
        '   por bueno.**',
        '',
        '3. **Los errores silenciosos son peores que los ruidosos.**',
        '   `cancelar_venta` no chequeaba `guardar_ambos`. Lección:',
        '   toda operación de escritura que pueda fallar debe',
        '   chequear el resultado y reportarlo.',
        '',
        '4. **Un bug que cambia de síntoma no significa que el fix',
        '   no sirvió.** §4.6 tuvo 4 capas de causa raíz. Cada fix',
        '   movió el error sin resolverlo. Cuando el síntoma cambia',
        '   pero persiste, hay una causa más profunda.',
        '',
        '5. **`_hmi` debe ser idempotente.** Antes de C.4b:',
        '   reinsertar un nodo que ya era el hmi generaba autoenlace',
        '   `hd → sí mismo` (ciclo). Fix: no-op si el hijo ya es el',
        '   hmi; limpiar `hd` residual cuando el padre no tenía hmi.',
        '',
        '6. **Cycle guard en lectura de cadenas.** Aunque `_hmi` esté',
        '   bien, un bug en otro lugar puede generar ciclos. Todo',
        '   `while ($actual && $seg < 2000)` que recorre una cadena',
        '   `hd`/`hd_<term>` debe trackear nodos visitados por id.',
        '   Aplica a cancelaciones hoy; auditar ventas, rendiciones,',
        '   liquidaciones en el futuro.',
        '',
        '7. **Soft delete coordinado también aplica a cancelaciones.**',
        '   El patrón de "árboles paralelos + buzón diferido" no era',
        '   solo para ventas.',
        '',
        '8. **Bloques `buscar` con estructura repetida necesitan',
        '   desambiguación.** El prompt del plugin tiene dos §11. Los',
        '   `buscar` deben anclar al header único, no al contenido',
        '   compartido.',
        '',
        '9. **Invertir en la herramienta de diagnóstico paga.**',
        '   `?auditar_topologia=1` encontró en 500ms lo que a ojo',
        '   hubiera costado horas.',
        '',
        '10. **Cuando el reporte de la auditoría tiene muchos items',
        '    de un mismo tipo, revisar la verificación antes de',
        '    asumir que hay muchos bugs.** Los 96 `p_apunta_a_otro`',
        '    y 7 `terminal_dueno_real` eran falsos positivos.',
        '',
        '### 8.4 Próximos pasos sugeridos',
        '',
        '1. Limpieza de endpoints temporales (§4.7).',
        '2. Espejo `_hmi` a JS.',
        '3. Auditar cycle guard en otros flujos (ventas,',
        '   rendiciones, liquidaciones).',
        '4. Fusionar las dos §11 de `prompt_plugin_piloto.md`.',
        '',
        '### 8.5 Archivo obsoleto',
        '',
        '`prompts/plan_auditar_topologia.md` quedó obsoleto. Su',
        'contenido está cubierto por:',
        '- §3.5 de `prompt_piloto.md` (auditoría).',
        '- §12 de `prompt_plugin_piloto.md` (botón del plugin).',
        '- §2 de este archivo (arco cerrado).',
        '',
        'Se puede borrar.',
    ],
    'reemplazar' => [
        '## 8. NOTA HISTÓRICA',
        '',
        'La nota de cierre de v1.5piloto.82d (arco "cancelaciones',
        'en compartidos + persistencia robusta en hostings',
        'compartidos") fue eliminada en v83s. Cumplió su función:',
        'los pendientes reales están consolidados en §4.1-4.4 y',
        '§4.8, y los aprendizajes en `prompt_seguridad.md` §8.10',
        'y en el historial de `prompt_piloto.md`.',
    ],
],

// ============================================================
// 4. Eliminar archivo obsoleto plan_auditar_topologia.md
// ============================================================
[
    'tipo' => 'eliminar',
    'archivo' => 'prompts/plan_auditar_topologia.md',
    'descripcion' => 'eliminar archivo obsoleto',
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