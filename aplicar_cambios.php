<?php
/**
 * Aplicador — Endpoint reiniciar_seleccion_completa.
 *
 * Agrega un helper que limpia la venta_actual de una terminal
 * (si quedó colgada) y libera los asientos reales que había
 * seleccionado. Nueva subacción viajes/reiniciar_seleccion_completa.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$nueva_funcion = [
    '',
    '/**',
    ' * Reinicia por completo la selección de asientos de una',
    ' * terminal: libera los asientos reales que tenga en estado',
    ' * "seleccionado" y destruye la venta_actual colgada.',
    ' *',
    ' * A diferencia de "deseleccionar todos los propios" del',
    ' * frontend (que itera asiento por asiento con la info del',
    ' * viaje actual), este helper limpia la venta_actual entera',
    ' * aunque el viaje ya no esté en la lista. Sirve para casos',
    ' * donde el usuario cerró la ventana a mitad de una venta, o',
    ' * para las pruebas automatizadas que dejaron una venta a',
    ' * medias de una corrida anterior fallida.',
    ' *',
    ' * Idempotente: si no hay venta_actual, devuelve exito con',
    ' * habia_venta = false.',
    ' *',
    ' * @param string $nombre_terminal',
    ' * @return array',
    ' * @since 1.5piloto.79h',
    ' */',
    'function reiniciar_seleccion_completa_terminal(string $nombre_terminal): array {',
    '    if ($nombre_terminal === \'\') {',
    '        return [\'exito\' => false, \'error\' => \'Terminal no especificada\'];',
    '    }',
    '',
    '    // Fase B3.3b: ID especial directo.',
    '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
    '    if (!$nodo_terminal) {',
    '        return [\'exito\' => false, \'error\' => \'Terminal no encontrada\'];',
    '    }',
    '',
    '    $venta_actual = $nodo_terminal->adyacente(\'venta_actual\');',
    '    if (!$venta_actual) {',
    '        return [\'exito\' => true, \'habia_venta\' => false];',
    '    }',
    '',
    '    // 1. Liberar los asientos reales referenciados. Solo se',
    '    //    pisan los que estén en estado "seleccionado" y cuya',
    '    //    referencia `seleccionado_por` apunte a esta terminal.',
    '    //    Los asientos vendidos o reservados por otros flujos',
    '    //    se respetan.',
    '    $cabeza = $venta_actual->adyacente(\'asientos\');',
    '    if ($cabeza) {',
    '        $actual_av = $cabeza->adyacente(\'primer\');',
    '        $seg_av = 0;',
    '        while ($actual_av && $actual_av->id() !== $cabeza->id() && $seg_av < 200) {',
    '            $nodo_asiento_real = $actual_av->adyacente(\'asiento\');',
    '            if ($nodo_asiento_real) {',
    '                $estado = $nodo_asiento_real->adyacente(\'estado\');',
    '                if ($estado && $estado->dato() === \'seleccionado\') {',
    '                    $estado->_dato(\'libre\');',
    '                }',
    '                $sel_por = $nodo_asiento_real->adyacente(\'seleccionado_por\');',
    '                if ($sel_por && $sel_por->dato() === $nombre_terminal) {',
    '                    $nodo_asiento_real->eliminar_adyacente(\'seleccionado_por\');',
    '                }',
    '            }',
    '            $actual_av = $actual_av->adyacente(\'siguiente\');',
    '            $seg_av++;',
    '        }',
    '    }',
    '',
    '    // 2. Desenlazar la venta_actual del terminal y destruirla',
    '    //    con todo su subárbol (asientos-en-venta, cabeza).',
    '    $nodo_terminal->eliminar_adyacente(\'venta_actual\');',
    '    _destruir_venta_actual($venta_actual);',
    '',
    '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
    '    return [\'exito\' => true, \'habia_venta\' => true];',
    '}',
];

$cambios = [
    // 1. Helper nuevo al final de ViajeAsientos.php.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Nuevo helper reiniciar_seleccion_completa_terminal',
        'buscar' => [
            'function _buscar_asiento_en_venta_persistente(Nodo $nodo_venta, Nodo $nodo_asiento) {',
            '    $cabeza = $nodo_venta->adyacente(\'asientos\');',
            '    if (!$cabeza) return null;',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $seg < 200) {',
            '        $nodo_asiento_ref = $actual->adyacente(\'asiento\');',
            '        if ($nodo_asiento_ref && $nodo_asiento_ref->id() === $nodo_asiento->id()) {',
            '            return $actual;',
            '        }',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => array_merge(
            [
                'function _buscar_asiento_en_venta_persistente(Nodo $nodo_venta, Nodo $nodo_asiento) {',
                '    $cabeza = $nodo_venta->adyacente(\'asientos\');',
                '    if (!$cabeza) return null;',
                '    $actual = $cabeza->adyacente(\'primer\');',
                '    $seg = 0;',
                '    while ($actual && $seg < 200) {',
                '        $nodo_asiento_ref = $actual->adyacente(\'asiento\');',
                '        if ($nodo_asiento_ref && $nodo_asiento_ref->id() === $nodo_asiento->id()) {',
                '            return $actual;',
                '        }',
                '        $actual = $actual->adyacente(\'siguiente\');',
                '        $seg++;',
                '    }',
                '    return null;',
                '}',
            ],
            $nueva_funcion
        ),
    ],

    // 2. Bump ViajeAsientos.php.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Bump ViajeAsientos.php',
        'buscar' => [' * @version   1.5piloto.77u'],
        'reemplazar' => [' * @version   1.5piloto.79h'],
    ],

    // 3. Subacción nueva en el Enrutador.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Nueva subacción reiniciar_seleccion_completa',
        'buscar' => [
            '                case \'deseleccionar_asiento\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $fila = $post[\'fila\'] ?? \'\';',
            '                    $columna = $post[\'columna\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno) || empty($nombre_terminal)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = deseleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'deseleccionar_asiento\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $fila = $post[\'fila\'] ?? \'\';',
            '                    $columna = $post[\'columna\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno) || empty($nombre_terminal)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = deseleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'reiniciar_seleccion_completa\':',
            '                    // Limpia la venta_actual de una terminal (si',
            '                    // quedó colgada) y libera los asientos reales',
            '                    // que tenía seleccionados. Idempotente.',
            '                    // @since 1.5piloto.79h',
            '                    $nombre_terminal_rsc = $post[\'nombre_terminal\'] ?? \'\';',
            '                    if (empty($nombre_terminal_rsc)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Terminal no especificada\']);',
            '                    }',
            '                    $resultado_rsc = reiniciar_seleccion_completa_terminal($nombre_terminal_rsc);',
            '                    responder_json($resultado_rsc);',
            '                    break;',
        ],
    ],

    // 4. Bump Enrutador.php.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Bump Enrutador.php',
        'buscar' => [' * @version   1.5piloto.78x'],
        'reemplazar' => [' * @version   1.5piloto.79h'],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Endpoint reiniciar_seleccion_completa ===\n\n";

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