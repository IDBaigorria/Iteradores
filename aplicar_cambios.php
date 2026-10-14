<?php
/**
 * Aplicador — Fix del helper de nombres de árbol + lecciones.
 *
 * Bug §1.5.11: tres funciones de Pasajero.php llaman
 * `_nombres_arbol_para_contexto($ctx, '')`. Con `''` como
 * nombre de terminal, el helper devuelve null y las
 * funciones caen a los nombres default (`hmi`/`hd`). Pero
 * el contenedor del compartido usa nombres parametrizados
 * (`hmi_<term>`). Resultado: el terminal nunca ve el badge
 * "tiene pasajes" ni los botones relacionados.
 *
 * Fix: hacer que `_nombres_arbol_para_contexto` derive el
 * nombre del terminal desde el propio compartido cuando no
 * se lo pasan. Retrocompatible.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. Arbol.php: helper + fix de _nombres_arbol_para_contexto
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/Arbol.php',
        'descripcion' => 'Arbol.php: derivar nombre terminal del compartido',
        'buscar' => [
            '/**',
            ' * Devuelve el array de nombres de enlace que un terminal',
            ' * debe usar para recorrer el árbol de un contenedor, según',
            ' * el contexto.',
            ' *',
            ' * Fase B2.3.2 del modelo topológico (v76w). Reglas:',
            ' * - Si no hay contexto (dueño o admin), devuelve null',
            ' *   → las funciones de árbol usan los nombres default.',
            ' * - Si el contexto tiene el marcador `_es_compartido`,',
            ' *   devuelve los nombres parametrizados con el sufijo del',
            ' *   terminal (p_<terminal>, hd_<terminal>, hmi_<terminal>).',
            ' * - Si el contexto no es un compartido (todavía), null.',
            ' *',
            ' * Mientras no existan compartidos marcados, esta función',
            ' * siempre devuelve null y el comportamiento es idéntico al',
            ' * actual. Los marcadores los agrega la migración de B2.3.3.',
            ' *',
            ' * @param Nodo|null $nodo_contexto',
            ' * @param string    $nombre_terminal',
            ' * @return array|null',
            ' */',
            'function _nombres_arbol_para_contexto(?Nodo $nodo_contexto, string $nombre_terminal): ?array {',
            '    if ($nodo_contexto === null || $nombre_terminal === \'\') return null;',
            '    if (!$nodo_contexto->adyacente(\'_es_compartido\')) return null;',
            '    return [',
            '        \'p\'   => \'p_\'   . $nombre_terminal,',
            '        \'hd\'  => \'hd_\'  . $nombre_terminal,',
            '        \'hmi\' => \'hmi_\' . $nombre_terminal,',
            '    ];',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Deriva el nombre del terminal desde un nodo compartido.',
            ' *',
            ' * El compartido tiene un contenedor `terminales` con un',
            ' * único hijo cuyo nombre de enlace es el nombre del',
            ' * terminal. Devuelve ese nombre, o \'\' si no se puede',
            ' * derivar.',
            ' *',
            ' * Se usa desde `_nombres_arbol_para_contexto` cuando el',
            ' * llamador no pasa el nombre explícitamente (por ejemplo,',
            ' * funciones de Pasajero.php que solo reciben el contexto).',
            ' * Bug §1.5.11: esas funciones antes recibían null y',
            ' * caían a los nombres default, pero el contenedor del',
            ' * compartido usa nombres parametrizados.',
            ' *',
            ' * @param Nodo $nodo_compartido',
            ' * @return string',
            ' * @since 1.5piloto.79c',
            ' */',
            'function _nombre_terminal_de_compartido(Nodo $nodo_compartido): string {',
            '    $cont = $nodo_compartido->adyacente(\'terminales\');',
            '    if (!$cont) return \'\';',
            '    foreach ((array)$cont->adyacentes() as $enlace => $nodo_term) {',
            '        return (string)$enlace;',
            '    }',
            '    return \'\';',
            '}',
            '',
            '/**',
            ' * Devuelve el array de nombres de enlace que un terminal',
            ' * debe usar para recorrer el árbol de un contenedor, según',
            ' * el contexto.',
            ' *',
            ' * Fase B2.3.2 del modelo topológico (v76w). Reglas:',
            ' * - Si no hay contexto (dueño o admin), devuelve null',
            ' *   → las funciones de árbol usan los nombres default.',
            ' * - Si el contexto tiene el marcador `_es_compartido`,',
            ' *   devuelve los nombres parametrizados con el sufijo del',
            ' *   terminal (p_<terminal>, hd_<terminal>, hmi_<terminal>).',
            ' * - Si el contexto no es un compartido (todavía), null.',
            ' *',
            ' * A partir de v79c (bug §1.5.11): si el llamador no pasa',
            ' * el nombre del terminal, se deriva del propio compartido',
            ' * (`terminales → <nombre>`). Retrocompatible: si el',
            ' * nombre viene, se usa tal cual.',
            ' *',
            ' * @param Nodo|null $nodo_contexto',
            ' * @param string    $nombre_terminal',
            ' * @return array|null',
            ' * @version 1.5piloto.79c',
            ' */',
            'function _nombres_arbol_para_contexto(?Nodo $nodo_contexto, string $nombre_terminal): ?array {',
            '    if ($nodo_contexto === null) return null;',
            '    if (!$nodo_contexto->adyacente(\'_es_compartido\')) return null;',
            '',
            '    // Si no nos pasaron el nombre del terminal, derivarlo',
            '    // del propio compartido.',
            '    if ($nombre_terminal === \'\') {',
            '        $nombre_terminal = _nombre_terminal_de_compartido($nodo_contexto);',
            '        if ($nombre_terminal === \'\') return null;',
            '    }',
            '',
            '    return [',
            '        \'p\'   => \'p_\'   . $nombre_terminal,',
            '        \'hd\'  => \'hd_\'  . $nombre_terminal,',
            '        \'hmi\' => \'hmi_\' . $nombre_terminal,',
            '    ];',
            '}',
        ],
    ],

    // ============================================================
    // 2. Arbol.php: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/Arbol.php',
        'descripcion' => 'Bump Arbol.php',
        'buscar' => [
            ' * @version   1.5piloto.76v',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79c',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md: §1.5.11
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.5.11',
        'buscar' => [
            '### 1.6 Pendientes de la línea',
        ],
        'reemplazar' => [
            '#### 1.5.11 Helper de nombres de árbol con contexto implícito (v79c)',
            '',
            '**Síntoma:** logueado como terminal, en la pestaña',
            'Clientes, un pasajero con pasajes no mostraba el badge',
            '"tiene pasajes" ni el botón "Ver pasajes" ni el aviso',
            'de "imprimir pasajes actualizados". Como dueño sí',
            'funcionaba.',
            '',
            '**Causa:** tres funciones de `Pasajero.php`',
            '(`pasajero_tiene_pasajes`,',
            '`pasajero_tiene_pasajes_activos`,',
            '`pasajero_tiene_ventas_activas`) llamaban',
            '`_nombres_arbol_para_contexto($nodo_contexto, \'\')`.',
            'Con `\'\'` como nombre de terminal, el helper devolvía',
            'null (por el `if ($nombre_terminal === \'\')`), y las',
            'funciones caían a los nombres default (`hmi`/`hd`).',
            'Pero el contenedor de ventas del compartido usa',
            'nombres parametrizados (`hmi_<term>`). `hmi($cont, null)`',
            'buscaba `hmi`, no lo encontraba, devolvía null, y el',
            'while no corría.',
            '',
            '**Solución:** hacer que `_nombres_arbol_para_contexto`',
            'derive el nombre del terminal desde el propio',
            'compartido cuando no se lo pasan. El compartido tiene',
            '`terminales → <nombre_terminal>`, y ese nombre es el',
            'que se necesita para construir los enlaces',
            'parametrizados. Retrocompatible: si el llamador pasa el',
            'nombre, se usa tal cual.',
            '',
            '**Helper nuevo:** `_nombre_terminal_de_compartido`',
            '(`miscelaneas/Arbol.php`). Deriva el nombre del',
            'terminal del contenedor `terminales` del compartido.',
            '',
            '**Lección:** un helper que necesita un dato que el',
            'llamador no tiene por qué conocer debe derivarlo del',
            'contexto, no confiar en que el llamador se lo pase.',
            'El patrón "`\'\'` = no aplica" es una trampa: mezcla',
            '"no aplica" con "no me lo pasaron". Ver §5.12 del',
            'prompt de seguridad.',
            '',
            '### 1.6 Pendientes de la línea',
        ],
    ],

    // ============================================================
    // 4. plan_actual.md: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: bump a 79c',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79b.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79c.',
        ],
    ],

    // ============================================================
    // 5. prompt_seguridad.md: §5.12
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: §5.12',
        'buscar' => [
            '### 5.11 Lecciones de método',
        ],
        'reemplazar' => [
            '### 5.12 Helpers con contexto implícito',
            '',
            'Cuando un helper necesita información que el llamador',
            'no tiene por qué conocer, **derivarla del contexto en',
            'vez de exigir que se la pasen**. Firma robusta: el',
            'parámetro es opcional, y si no viene, el helper lo',
            'obtiene por su cuenta.',
            '',
            '**Caso real (§1.5.11).** Tres funciones de',
            '`Pasajero.php` llaman a',
            '`_nombres_arbol_para_contexto($ctx, \'\')`. Pasar `\'\'`',
            'como nombre del terminal hacía que el helper devolviera',
            'null, y las funciones cayeran silenciosamente a los',
            'nombres default. Pero el contenedor del compartido usa',
            'nombres parametrizados. Resultado: el terminal nunca',
            'veía los badges ni los botones relacionados.',
            '',
            '**Patrón de la trampa.** El llamador no conoce el',
            'nombre del terminal (solo tiene el nodo compartido).',
            'El helper exigía el nombre. El llamador pasaba `\'\'`',
            'para "no aplica". El helper interpretaba `\'\'` como',
            '"no hay contexto" y devolvía null. Bug silencioso:',
            'sin errores, sin warnings, comportamiento incorrecto.',
            '',
            '**Fix.** El helper deriva el nombre del propio',
            'contexto cuando no se lo pasan. El compartido tiene',
            '`terminales → <nombre>`, de donde se puede leer.',
            'Retrocompatible: si el nombre viene, se usa tal cual.',
            '',
            '**Regla.** Antes de agregar un parámetro obligatorio a',
            'un helper, preguntarse: "¿el llamador realmente lo',
            'tiene?". Si no, el helper debe derivarlo del contexto',
            'o aceptar `null`/`\'\'` como "derivalo vos".',
            '',
            '### 5.11 Lecciones de método',
        ],
    ],

    // ============================================================
    // 6. prompt_seguridad.md: anti-patrón §6.11
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: anti-patrón §6.11',
        'buscar' => [
            '### 6.10 Filtrar compartidos con el prefijo `us_` de más',
        ],
        'reemplazar' => [
            '### 6.11 Pasar `\'\'` a `_nombres_arbol_para_contexto`',
            '',
            'Ver §5.12. Pasar cadena vacía como nombre del',
            'terminal cuando el contexto es un compartido hace que',
            'el helper devuelva null y las funciones caigan a los',
            'nombres default, pero el contenedor del compartido usa',
            'nombres parametrizados. Bug silencioso.',
            '',
            '**Caso real:** v79c. `pasajero_tiene_pasajes`,',
            '`pasajero_tiene_pasajes_activos` y',
            '`pasajero_tiene_ventas_activas` en `Pasajero.php`.',
            'El helper ahora deriva el nombre del propio',
            'compartido.',
            '',
            '### 6.10 Filtrar compartidos con el prefijo `us_` de más',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Fix helper nombres de árbol ===\n\n";

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