<?php
/**
 * Aplicador — Mostrar botones de impresión del micro a la terminal.
 *
 * La condición actual solo muestra los botones a dueño, admin y
 * soporte. Agregar terminal. (v79e)
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. viajes-asientos.js: condición de visibilidad
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: botones de impresión para terminal',
        'buscar' => [
            '    // Botones de impresión: solo dueño y admin.',
            '    if (usuario_actual.nivel === \'dueno\' || es_admin_o_soporte()) {',
        ],
        'reemplazar' => [
            '    // Botones de impresión: dueño, terminal, admin y soporte.',
            '    // La terminal se agrega en v79e. Antes se la excluía porque',
            '    // el backend no estaba adaptado a carga parcial; hoy sí lo',
            '    // está. Los datos que exponen croquis y planilla ya los ve',
            '    // la terminal en el modal "Ver pasaje" y en el propio croquis,',
            '    // así que no es una fuga nueva.',
            '    if (usuario_actual.nivel === \'dueno\' || usuario_actual.nivel === \'terminal\' || es_admin_o_soporte()) {',
        ],
    ],

    // ============================================================
    // 2. viajes-asientos.js: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Bump viajes-asientos.js',
        'buscar' => [
            ' * @version 1.5piloto.77d',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.79e',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md: agregar §1.5.13
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.5.13',
        'buscar' => [
            '### 1.6 Pendientes de la línea',
        ],
        'reemplazar' => [
            '#### 1.5.13 Botones de impresión del micro para terminal (v79e)',
            '',
            '**Síntoma:** los botones "Imprimir Croquis",',
            '"Imprimir Planilla" e "Imprimir Planilla Vacía"',
            'aparecían para el dueño pero no para la terminal.',
            '',
            '**Causa:** condición JS en `renderizar_pasaje_micro`',
            '(`viajes-asientos.js`):',
            '',
            '```js',
            'if (usuario_actual.nivel === \'dueno\' || es_admin_o_soporte())',
            '```',
            '',
            'La terminal quedaba afuera. Cuando se agregaron esos',
            'botones, el backend de impresión no estaba adaptado a',
            'carga parcial. Hoy sí: `_contextos_para_operacion`',
            'devuelve `[\'us_<dueno>\']` para `?imprimir=1&dueno=X`',
            '(salvo los tipos B3.3c, que no incluyen croquis ni',
            'planilla).',
            '',
            '**Solución:** agregar `terminal` a la condición.',
            '',
            '**Nota:** la información que exponen croquis y',
            'planilla (nombre de pasajero, DNI, contacto) ya la',
            've la terminal en el modal "Ver pasaje" y en el',
            'propio croquis. No es una fuga nueva.',
            '',
            '**Deuda de seguridad anotada:** las URLs de impresión',
            '(`?imprimir=1&tipo=...`) no validan sesión ni',
            'permisos. Cualquiera con la URL puede ver los datos.',
            'Es un problema preexistente. Anotado para una tanda',
            'futura.',
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
        'descripcion' => 'plan_actual: bump a 79e',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79d.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79e.',
        ],
    ],

    // ============================================================
    // 5. plan_actual.md: deuda de seguridad de impresión
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.6: deuda de seguridad',
        'buscar' => [
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-',
            '  tandas cortas.',
            '- **Plugin: dividir la ventana en pestañas, una por rol.**',
            '  Idea anotada al cierre de v78v.',
        ],
        'reemplazar' => [
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-',
            '  tandas cortas.',
            '- **Plugin: dividir la ventana en pestañas, una por rol.**',
            '  Idea anotada al cierre de v78v.',
            '- **Deuda de seguridad en impresión.** Las URLs de',
            '  impresión (`?imprimir=1&tipo=...`) no validan sesión',
            '  ni permisos. Cualquiera con la URL ve el HTML. Se',
            '  anota para una tanda futura, cuando se rehaga el',
            '  módulo de impresión o se agregue un chequeo general.',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Botones de impresión para terminal ===\n\n";

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