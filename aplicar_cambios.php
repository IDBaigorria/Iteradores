<?php
/**
 * Aplicador — Limpiar viajes de prueba con ventas de prueba.
 *
 * Extiende limpiar_viajes_de_prueba: si un viaje con prefijo de
 * prueba tiene ventas y TODAS son de prueba (comprador con email
 * @test.local), las cancela antes de destruir el viaje. Los nodos
 * Cancelación se dejan como auditoría. Si alguna venta NO es de
 * prueba, el viaje se conserva (comportamiento anterior).
 *
 * Nuevo helper _todas_las_ventas_del_viaje_son_de_prueba.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$helper = [
    '',
    '/**',
    ' * Chequea si TODAS las ventas de un viaje tienen comprador',
    ' * con email terminado en @test.local. Solo en ese caso el',
    ' * viaje se puede destruir como "de prueba".',
    ' *',
    ' * Devuelve:',
    ' *   [',
    ' *     \'todas\'      => bool,  // true si TODAS son de prueba',
    ' *     \'conteo\'     => int,   // cantidad de ventas del viaje',
    ' *     \'ids_venta\'  => array, // ids de ventas de prueba (a cancelar)',
    ' *     \'no_prueba\'  => array, // ids de ventas NO de prueba (bloqueantes)',
    ' *   ]',
    ' *',
    ' * Se llama desde limpiar_viajes_de_prueba. La decisión de',
    ' * cancelar o conservar la toma el llamador.',
    ' *',
    ' * @param string $nombre_dueno',
    ' * @param string $nombre_viaje',
    ' * @return array',
    ' * @since 1.5piloto.79l',
    ' */',
    'function _todas_las_ventas_del_viaje_son_de_prueba(string $nombre_dueno, string $nombre_viaje): array {',
    '    $res = [',
    '        \'todas\' => true,',
    '        \'conteo\' => 0,',
    '        \'ids_venta\' => [],',
    '        \'no_prueba\' => [],',
    '    ];',
    '',
    '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
    '    if (!$contenedor_ventas) return $res;',
    '',
    '    $venta = hmi($contenedor_ventas);',
    '    $seg = 0;',
    '    while ($venta && $seg < 2000) {',
    '        $seg++;',
    '        $nodo_viaje = $venta->adyacente(\'viaje\');',
    '        if (!$nodo_viaje || $nodo_viaje->dato() !== $nombre_viaje) {',
    '            $venta = hd($venta);',
    '            continue;',
    '        }',
    '        $res[\'conteo\']++;',
    '        $id_venta = (string)$venta->dato();',
    '',
    '        // Chequear comprador. Solo el email @test.local',
    '        // marca la venta como de prueba. Email real, vacío',
    '        // o inválido => venta real (no se toca).',
    '        $es_prueba = false;',
    '        $nodo_comprador = $venta->adyacente(\'comprador\');',
    '        if ($nodo_comprador) {',
    '            $nodo_email = $nodo_comprador->adyacente(\'email\');',
    '            if ($nodo_email) {',
    '                $email = (string)$nodo_email->dato();',
    '                if (substr($email, -11) === \'@test.local\') {',
    '                    $es_prueba = true;',
    '                }',
    '            }',
    '        }',
    '',
    '        if ($es_prueba) {',
    '            $res[\'ids_venta\'][] = $id_venta;',
    '        } else {',
    '            $res[\'todas\'] = false;',
    '            $res[\'no_prueba\'][] = $id_venta;',
    '        }',
    '',
    '        $venta = hd($venta);',
    '    }',
    '    return $res;',
    '}',
];

$cambios = [
    // ============================================================
    // 1. Reemplazar la lógica de "viaje con ventas" en
    //    limpiar_viajes_de_prueba.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Lógica nueva para viajes con ventas de prueba',
        'buscar' => [
            '        // No eliminar si tiene ventas registradas.',
            '        if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {',
            '            $con_ventas[] = $nombre_viaje;',
            '            continue;',
            '        }',
        ],
        'reemplazar' => [
            '        // Si tiene ventas, chequear si TODAS son de prueba',
            '        // (comprador con email @test.local). Solo en ese caso',
            '        // se cancelan y se procede a destruir el viaje. Si',
            '        // alguna venta NO es de prueba, se conserva el viaje.',
            '        if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {',
            '            $analisis = _todas_las_ventas_del_viaje_son_de_prueba(',
            '                $nombre_dueno, $nombre_viaje',
            '            );',
            '            if (!$analisis[\'todas\']) {',
            '                // Hay al menos una venta real: conservar el viaje.',
            '                $con_ventas[] = $nombre_viaje;',
            '                continue;',
            '            }',
            '',
            '            // Cancelar cada venta de prueba. cancelar_venta',
            '            // destruye el nodo venta y crea un Nodo Cancelación',
            '            // (auditoría). Los asientos se liberan y los cupones',
            '            // se destruyen como en una cancelación normal.',
            '            $cancelacion_fallida = false;',
            '            foreach ($analisis[\'ids_venta\'] as $id_venta_cancelar) {',
            '                $res_cancel = cancelar_venta(',
            '                    $id_venta_cancelar,',
            '                    \'Limpieza de pruebas automáticas\',',
            '                    null,',
            '                    $nombre_dueno',
            '                );',
            '                if (!$res_cancel || empty($res_cancel[\'exito\'])) {',
            '                    $cancelacion_fallida = true;',
            '                    break;',
            '                }',
            '            }',
            '            if ($cancelacion_fallida) {',
            '                // Algo falló a mitad de camino: conservar el',
            '                // viaje. Si quedó alguna venta cancelada, no',
            '                // afecta al usuario: se ve como cancelación',
            '                // normal en la pestaña Vendidos.',
            '                $con_ventas[] = $nombre_viaje;',
            '                continue;',
            '            }',
            '            // Ahora el viaje ya no tiene ventas activas. Sigue',
            '            // el flujo normal de destrucción.',
            '        }',
        ],
    ],

    // ============================================================
    // 2. Insertar el helper antes de la lista de prefijos
    //    (dentro del mismo archivo, para que quede cerca de
    //    limpiar_viajes_de_prueba).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Nuevo helper _todas_las_ventas_del_viaje_son_de_prueba',
        'buscar' => [
            'function limpiar_viajes_de_prueba(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado o sin viajes\'];',
            '    }',
        ],
        'reemplazar' => array_merge(
            $helper,
            [
                '',
                'function limpiar_viajes_de_prueba(string $nombre_dueno): array {',
                '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
                '    if (!$nodo_viajes) {',
                '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado o sin viajes\'];',
                '    }',
            ]
        ),
    ],

    // ============================================================
    // 3. Bump Viaje.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump Viaje.php',
        'buscar' => [' * @version   1.5piloto.79k'],
        'reemplazar' => [' * @version   1.5piloto.79l'],
    ],

    // ============================================================
    // 4. Reportar las cancelaciones en la respuesta del backend
    //    (agregar el array `canceladas` para futura UI).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reportar canceladas en la respuesta',
        'buscar' => [
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    $borrados = [];',
            '    $conservados = [];',
            '    $con_ventas = [];',
        ],
        'reemplazar' => [
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    $borrados = [];',
            '    $conservados = [];',
            '    $con_ventas = [];',
            '    $canceladas = [];',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Acumular canceladas en el loop',
        'buscar' => [
            '                if (!$res_cancel || empty($res_cancel[\'exito\'])) {',
            '                    $cancelacion_fallida = true;',
            '                    break;',
            '                }',
            '            }',
        ],
        'reemplazar' => [
            '                if (!$res_cancel || empty($res_cancel[\'exito\'])) {',
            '                    $cancelacion_fallida = true;',
            '                    break;',
            '                }',
            '                $canceladas[] = $id_venta_cancelar;',
            '            }',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Devolver canceladas en el response',
        'buscar' => [
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'conservados\' => $conservados,',
            '        \'con_ventas\' => $con_ventas,',
            '    ];',
        ],
        'reemplazar' => [
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'conservados\' => $conservados,',
            '        \'con_ventas\' => $con_ventas,',
            '        // v79l: ids de ventas de prueba canceladas durante',
            '        // la limpieza. Quedan como nodos Cancelación en el',
            '        // árbol del dueño (auditoría). Se puede agregar',
            '        // un botón "Eliminar cancelaciones de prueba" en',
            '        // una tanda futura.',
            '        \'canceladas\' => $canceladas,',
            '        \'cantidad_canceladas\' => count($canceladas),',
            '    ];',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Limpiar viajes con ventas de prueba ===\n\n";

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