<?php
/**
 * Aplicador — Cierre §1.5.10 + lecciones de naming.
 *
 * 1. Fix del mismo filtro `compartido_con_us_` en reparar_arboles.php
 *    (bug latente detectado al revisar el helper hermano).
 * 2. plan_actual.md: §1.5.10 con la nota del bug del filtro.
 * 3. prompt_seguridad.md: §5.10 (naming de compartidos) y
 *    §5.11 (lecciones de método).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. reparar_arboles.php: fix del filtro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Reparacion/RepararArboles.php',
        'descripcion' => 'Fix filtro compartidos en reparar_arboles.php',
        'buscar' => [
            '        foreach ($ady_dueno as $enlace_comp => $nodo_comp) {',
            '            $enlace_comp = (string)$enlace_comp;',
            '            if (strpos($enlace_comp, \'compartido_con_us_\') !== 0) continue;',
            '            if (!$nodo_comp->adyacente(\'_es_compartido\')) continue;',
            '',
            '            $nombre_terminal = substr($enlace_comp, strlen(\'compartido_con_us_\'));',
            '            if ($nombre_terminal === \'\') continue;',
        ],
        'reemplazar' => [
            '        foreach ($ady_dueno as $enlace_comp => $nodo_comp) {',
            '            $enlace_comp = (string)$enlace_comp;',
            '            // Los compartidos se llaman `compartido_con_<nombre>`',
            '            // (con el nombre de usuario del terminal, sin',
            '            // prefijo `us_`). Bug de filtro detectado al',
            '            // revisar este helper (el mismo que tenía',
            '            // _procesar_pendientes_del_dueno en Venta.php).',
            '            if (strpos($enlace_comp, \'compartido_con_\') !== 0) continue;',
            '            if (!$nodo_comp->adyacente(\'_es_compartido\')) continue;',
            '',
            '            $nombre_terminal = substr($enlace_comp, strlen(\'compartido_con_\'));',
            '            if ($nombre_terminal === \'\') continue;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Reparacion/RepararArboles.php',
        'descripcion' => 'Bump RepararArboles 78z -> 79b',
        'buscar' => [
            ' * @version   1.5piloto.78z',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79b',
        ],
    ],

    // ============================================================
    // 2. plan_actual.md: nota del bug del filtro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.5.10: nota del bug del filtro',
        'buscar' => [
            '**Verificación:**',
            '',
            '1. Como terminal: vender 2 ventas.',
            '2. Como dueño: verificar que aparecen las 2.',
            '3. Como terminal: cancelar 1.',
            '4. Como dueño: verificar que aparece solo la otra.',
        ],
        'reemplazar' => [
            '**Bug del filtro (v79b).** En la primera prueba, el',
            'buzón se creaba y las ventas nuevas se guardaban con',
            'la máscara correcta (bit del dueño), pero el dueño no',
            'las veía. Causa: el filtro de compartidos en',
            '`_procesar_pendientes_del_dueno` era',
            '`strpos($enlace, \'compartido_con_us_\')`, pero los',
            'compartidos se llaman `compartido_con_<nombre>` (con',
            'el nombre de usuario del terminal, sin `us_`). El',
            'filtro nunca matcheaba, el buzón nunca se drenaba, y',
            'las ventas quedaban varadas.',
            '',
            'Fix: `strpos($enlace, \'compartido_con_\')`. El mismo',
            'bug estaba latente en `reparar_arboles.php` (helper',
            'hermano), corregido en la misma tanda. Lección:',
            'auditar todos los filtros de compartidos con',
            '`grep \'compartido_con_us_\'`.',
            '',
            '**Verificación:**',
            '',
            '1. Como terminal: vender 2 ventas.',
            '2. Como dueño: verificar que aparecen las 2.',
            '3. Como terminal: cancelar 1.',
            '4. Como dueño: verificar que aparece solo la otra.',
            '5. SQL: `SELECT enlace FROM adyacente_contexto WHERE',
            '   enlace LIKE \'pendientes_%\';` debe devolver 0',
            '   filas (buzones drenados).',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: bump a 79b',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79a.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79b.',
        ],
    ],

    // ============================================================
    // 4. prompt_seguridad.md: §5.10 y §5.11
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: §5.10 y §5.11',
        'buscar' => [
            '**Regla de detección para estructuras nuevas:**',
            '',
            '1. ¿Es un árbol (hmi/hd/p)? Si no, no.',
            '2. ¿El terminal puede agregar nodos nuevos a ese árbol?',
            '   Si no, no.',
            '3. ¿El contenedor raíz del árbol es alcanzable desde',
            '   ambos contextos (dueño y terminal)? Si sí, sí.',
            '',
            'Si las tres son "sí", necesita el patrón del buzón.',
            '',
            '---',
            '',
            '## 6. ANTI-PATRONES',
        ],
        'reemplazar' => [
            '**Regla de detección para estructuras nuevas:**',
            '',
            '1. ¿Es un árbol (hmi/hd/p)? Si no, no.',
            '2. ¿El terminal puede agregar nodos nuevos a ese árbol?',
            '   Si no, no.',
            '3. ¿El contenedor raíz del árbol es alcanzable desde',
            '   ambos contextos (dueño y terminal)? Si sí, sí.',
            '',
            'Si las tres son "sí", necesita el patrón del buzón.',
            '',
            '### 5.10 Naming de los compartidos',
            '',
            '**Regla del nombre.** Los compartidos se llaman',
            '`compartido_con_<nombre_usuario>`, donde',
            '`<nombre_usuario>` es el **nombre de usuario** del',
            'terminal, **sin el prefijo `us_`**. Ejemplo:',
            '`compartido_con_secretaria_carmen`.',
            '',
            'Esto es consistente con la convención general del',
            'piloto: los nombres visibles son nombres de usuario,',
            'no IDs especiales. El ID especial (`us_<nombre>`) es',
            'para referenciar el nodo entre cargas, no para el',
            'nombre de enlace.',
            '',
            '**Trampa común.** Escribir',
            '`strpos($enlace, \'compartido_con_us_\')` en un filtro',
            'que deba detectar compartidos. **Nunca matchea**',
            'porque el `us_` de más no está en el nombre real.',
            'El filtro falla silenciosamente: el loop recorre los',
            'compartidos, no encuentra ninguno, y el bug se',
            'manifiesta más tarde como "el dueño no ve las ventas".',
            '',
            '**Fix.** El filtro correcto es:',
            '`strpos($enlace, \'compartido_con_\') === 0`.',
            'La parte del nombre de usuario se extrae con',
            '`substr($enlace, strlen(\'compartido_con_\'))`.',
            '',
            '**Auditoría.** Antes de dar por cerrado cualquier',
            'flujo que toque compartidos, correr:',
            '',
            '```bash',
            'grep -rn "compartido_con_us_" Aplicacion/',
            '```',
            '',
            'Si devuelve algo, es un bug latente del mismo tipo.',
            'En la sesión v79b aparecieron dos:',
            '`_procesar_pendientes_del_dueno` (Venta.php) y',
            '`reparar_arboles.php`.',
            '',
            '### 5.11 Lecciones de método',
            '',
            '**1. El bitmask correcto no garantiza que el flujo',
            'funcione.** En v79b, los SQLs de diagnóstico mostraron',
            'que las ventas nuevas tenían la máscara 120 (con el',
            'bit del dueño) y que el buzón existía. Todo el',
            'pipeline parecía sano. El bug era un filtro de',
            'naming que fallaba silenciosamente antes de llegar',
            'al bitmask. Moraleja: cuando el estado persistido',
            'parece correcto pero el comportamiento no, **el bug',
            'está en el código que lo consume, no en los datos**.',
            '',
            '**2. Verificar por SQL el estado real antes de asumir.**',
            'Cuando un fix no resuelve el síntoma, antes de',
            'proponer otro fix, correr SQLs que muestren el',
            'estado real. En v79b eso evitó seguir buscando en el',
            'lugar equivocado (el guardado parcial) cuando el',
            'problema estaba en la lectura (el filtro de drenado).',
            '',
            '**3. Un bug en un helper sugiere el mismo bug en sus',
            'hermanos.** Cuando se detecta un patrón problemático',
            '(por ejemplo, un filtro mal escrito), auditar los',
            'demás helpers que usan el mismo patrón. En v79b, el',
            'fix del filtro en `Venta.php` reveló el mismo bug',
            'en `reparar_arboles.php`.',
            '',
            '---',
            '',
            '## 6. ANTI-PATRONES',
        ],
    ],

    // ============================================================
    // 5. prompt_seguridad.md: agregar anti-patrón §6.10
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: anti-patrón §6.10',
        'buscar' => [
            '### 6.9 Confundir el ID del framework con la fila de la DB',
            '',
            'Los IDs del framework (`Nodo::id()`) son efímeros. Los `id_fila`',
            'de la DB son estables. Nunca usar el ID del framework como',
            'clave persistente entre requests.',
            '',
            '**Caso real:** el bug §1.5. Los fixes v78r/v78s/v78t/v78u',
            'intentaban mapear "ID del framework" ↔ "fila de la DB", pero',
            'los IDs del framework cambiaban entre cargas. El grafo creció a',
            '99k nodos por inserciones duplicadas.',
        ],
        'reemplazar' => [
            '### 6.9 Confundir el ID del framework con la fila de la DB',
            '',
            'Los IDs del framework (`Nodo::id()`) son efímeros. Los `id_fila`',
            'de la DB son estables. Nunca usar el ID del framework como',
            'clave persistente entre requests.',
            '',
            '**Caso real:** el bug §1.5. Los fixes v78r/v78s/v78t/v78u',
            'intentaban mapear "ID del framework" ↔ "fila de la DB", pero',
            'los IDs del framework cambiaban entre cargas. El grafo creció a',
            '99k nodos por inserciones duplicadas.',
            '',
            '### 6.10 Filtrar compartidos con el prefijo `us_` de más',
            '',
            'Ver §5.10. Los compartidos se llaman',
            '`compartido_con_<nombre_usuario>`, no',
            '`compartido_con_us_<nombre>`. Un filtro con el `us_` de',
            'más nunca matchea y falla silenciosamente.',
            '',
            '**Caso real:** v79b. `_procesar_pendientes_del_dueno`',
            'tenía el filtro mal. El buzón de ventas nunca se',
            'drenaba y el dueño no veía las ventas nuevas. El',
            'mismo bug latente en `reparar_arboles.php`.',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Cierre §1.5.10 + lecciones ===\n\n";

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

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
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
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";