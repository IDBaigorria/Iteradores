<?php
/**
 * Aplicador — Ampliar prefijos de prueba + reportar conservados.
 *
 * Dos cambios:
 *   1. Viaje.php: agregar `viajeregres` a los prefijos.
 *      La prueba listar_viajes_indice_comportamiento (Fase 3)
 *      usa viajeregresA / viajeregresB. Faltaban en la lista
 *      y los viajes quedaban conservados silenciosamente.
 *   2. viajes-nucleo.js: reportar también los conservados en
 *      el mensaje, para que el admin entienda por qué no baja
 *      el conteo.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. Agregar viajeregres a prefijos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Agregar prefijo viajeregres',
        'buscar' => [
            '    $prefijos = [',
            '        \'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\',',
            '        // Prefijos de las pruebas del plugin (agregados en v79j).',
            '        \'viajeventgraf\',    // prueba 34: cancelar venta',
            '        \'viajedeselgraf\',   // prueba 35: deseleccionar asiento',
            '        \'viajecambiomicro\', // prueba 36: cambiar micro',
            '    ];',
        ],
        'reemplazar' => [
            '    $prefijos = [',
            '        \'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\',',
            '        // Prefijos de las pruebas del plugin (agregados en v79j).',
            '        \'viajeventgraf\',    // prueba 34: cancelar venta',
            '        \'viajedeselgraf\',   // prueba 35: deseleccionar asiento',
            '        \'viajecambiomicro\', // prueba 36: cambiar micro',
            '        \'viajeregres\',      // prueba 43: listar viajes con índice',
            '    ];',
        ],
    ],

    // ============================================================
    // 2. Bump Viaje.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump Viaje.php',
        'buscar' => [' * @version   1.5piloto.79j'],
        'reemplazar' => [' * @version   1.5piloto.79k'],
    ],

    // ============================================================
    // 3. Reportar conservados en el mensaje del frontend
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Reportar conservados en el mensaje',
        'buscar' => [
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " viaje(s) de prueba.";',
            '    if (datos.con_ventas && datos.con_ventas.length > 0) {',
            '        msg += " " + datos.con_ventas.length + " no se pudieron eliminar (tienen ventas).";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
        ],
        'reemplazar' => [
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " viaje(s) de prueba.";',
            '    if (datos.con_ventas && datos.con_ventas.length > 0) {',
            '        msg += " " + datos.con_ventas.length + " no se pudieron eliminar (tienen ventas).";',
            '    }',
            '    // v79k: reportar también los conservados por no tener',
            '    // prefijo de prueba ni nombre principal. Antes quedaban',
            '    // silenciosos y el admin no entendía por qué el conteo',
            '    // total no bajaba.',
            '    const conservados_sin_prefijo = (datos.conservados || []).filter(',
            '        function (n) { return n !== null; }',
            '    );',
            '    if (conservados_sin_prefijo.length > 0) {',
            '        msg += " " + conservados_sin_prefijo.length + " conservado(s) sin prefijo de prueba.";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
        ],
    ],

    // ============================================================
    // 4. Bump viajes-nucleo.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Bump viajes-nucleo.js',
        'buscar' => [' * @version 1.5piloto.79j'],
        'reemplazar' => [' * @version 1.5piloto.79k'],
    ],

    // ============================================================
    // 5. Bump ?v= en el HTML
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de viajes-nucleo.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.79j"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.79k"></script>',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Ampliar prefijos + reportar conservados ===\n\n";

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