<?php
/**
 * Aplicador — Tanda D.4: actualizar aplicacion_POST.php.
 *
 * Cambios mínimos:
 *   1. @version 1.5piloto.79p → 1.5piloto.82d.
 *   2. Título "Estructura de nodos actual (v1.5piloto.79o)" →
 *      "(v1.5piloto.82d)".
 *   3. Nota nueva al final del docblock con el estado del arco
 *      de cancelaciones + persistencia robusta.
 *
 * La estructura vieja (bloque "Sección eliminada del docblock
 * viejo") se mantiene intacta. La estructura nueva documentada
 * (modelo por capas + espejo viejo) también.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios_d4.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Header @version
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'POST: @version a 82d',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.79p',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.82d',
    ],
],

// ============================================================
// 2. Título de la sección
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'POST: título de la sección a 82d',
    'buscar' => [
        ' * ## Estructura de nodos actual (v1.5piloto.79o)',
    ],
    'reemplazar' => [
        ' * ## Estructura de nodos actual (v1.5piloto.82d)',
    ],
],

// ============================================================
// 3. Nota nueva al final del docblock (antes de @package)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'POST: nota de estado del arco 82d',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.82d',
        ' */',
    ],
    'reemplazar' => [
        ' * ## Estado al cierre de v1.5piloto.82d (arco de cancelaciones',
        ' * + persistencia robusta)',
        ' *',
        ' * **Estructura de nodos:** sin cambios estructurales. La',
        ' * estructura documentada arriba (modelo por capas + espejo',
        ' * viejo + estructura vieja al final) sigue vigente. **No se',
        ' * borró nada de la estructura vieja**: se mantiene como',
        ' * referencia hasta que se pruebe en producción.',
        ' *',
        ' * **Cambios de comportamiento del arco v81t-82d:**',
        ' *',
        ' * 1. **Árbol de cancelaciones del compartido con nombres',
        ' *    parametrizados.**',
        ' *    - Escritura (`_crear_nodo_cancelacion` en Venta.php):',
        ' *      cuando el contexto es un compartido, inserta con',
        ' *      `hmi_<term>`/`hd_<term>`/`p_<term>`. Antes insertaba',
        ' *      con `hmi` default sobre el contenedor del compartido,',
        ' *      que lee con parametrizados. La cancelación quedaba en',
        ' *      el árbol pero no se encontraba al leer.',
        ' *    - Lectura (`listar_cancelaciones_de_dueno`,',
        ' *      `obtener_cancelacion_por_id`): usan',
        ' *      `_nombres_arbol_para_contexto` para elegir default vs',
        ' *      parametrizado. Antes leían siempre con `hmi`/`hd`.',
        ' *    - Buzón `pendientes_cancelaciones`: el terminal encola,',
        ' *      el dueño drena en su próxima carga. Sigue vigente.',
        ' *    - Cycle guard en lectura: trackear nodos visitados por',
        ' *      id. Defensa en profundidad contra ciclos residuales.',
        ' *',
        ' * 2. **`_hmi` idempotente** (miscelaneas/Arbol.php, v81v).',
        ' *    - No-op si el hijo ya es el hmi actual.',
        ' *    - Limpia `hd` residual cuando el padre no tenía hmi.',
        ' *    - Antes: reinsertar un nodo que ya era el hmi generaba',
        ' *      autoenlace `hd → sí mismo` (ciclo).',
        ' *',
        ' * 3. **Comandos de reparación nuevos.**',
        ' *    - `app:reparar_cancelaciones_compartidos`',
        ' *      (`?reparar_cancelaciones=1`): reinserta cancelaciones',
        ' *      en ambos árboles. También repara ventas canceladas',
        ' *      incompletas (tienen cancelación pero no autoenlace',
        ' *      `cancelada`).',
        ' *    - `app:reparar_cancelaciones_arboles`',
        ' *      (`?reparar_cancelaciones_arboles=1`): detecta ciclos',
        ' *      en las cadenas `hd`/`hd_<term>` y reconstruye como',
        ' *      cadena lineal.',
        ' *    - `app:auditar_topologia` (`?auditar_topologia=1`):',
        ' *      14 verificaciones topológicas en 3 severidades. Ver',
        ' *      `prompt_piloto.md` §3.5.',
        ' *    - `?eliminar_huerfanos=1`: elimina los nodos no',
        ' *      alcanzables desde raíces (fuerza carga total, la',
        ' *      pestaña Grafo en parcial no los ve).',
        ' *    - Todos idempotentes, todos admin/soporte.',
        ' *',
        ' * 4. **`cancelar_venta` chequea `guardar_ambos`.**',
        ' *    - Si falla, `exito: false` con mensaje. Antes respondía',
        ' *      "éxito" aunque la DB no se hubiera actualizado.',
        ' *',
        ' * **Bug §4.6: cerrado.** No era bug del flujo de',
        ' * cancelaciones, era persistencia silenciosa en InfinityFree.',
        ' * Causa raíz en 4 capas: `CREATE TEMPORARY TABLE` no',
        ' * permitida, `mysqli_report` strict en PHP 8.4, rate limit',
        ' * de conexiones, `cancelar_venta` no chequeaba',
        ' * `guardar_ambos`. Fixes en framework 1.5i.8d-f (singleton',
        ' * de conexión, `mysqli_report(OFF)`, `SELECT 1` en lugar de',
        ' * `ping()`, `IN (...)` directo). Ver `prompt_seguridad.md`',
        ' * §8.10 y `prompt_framework_iteradores.md` §6.7.',
        ' *',
        ' * **Migración del plugin:** secuencia de 12 pasos.',
        ' * Pasos 10 (`reparar_cancelaciones`) y 11',
        ' * (`reparar_cancelaciones_arboles`) agregados entre',
        ' * `reparar_arboles` y el drenado. Ver',
        ' * `miscelaneas/migraciones.md` y `prompt_plugin_piloto.md`',
        ' * §11.1.',
        ' *',
        ' * **Deuda de la estructura vieja:** el bloque "Sección',
        ' * eliminada del docblock viejo" sigue al pie de la letra.',
        ' * Se eliminará cuando la batería de pruebas del plugin',
        ' * valide la topología nueva en producción. Hasta entonces,',
        ' * sigue siendo la referencia para entender el modelo',
        ' * pre-B1.',
        ' *',
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.82d',
        ' */',
    ],
],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador — Tanda D.4 ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar.\n\n";

$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
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