<?php
/**
 * Aplicador — Tanda final de cobertura de guardar_ambos.
 *
 * Los 10 flujos restantes del piloto que llaman guardar_ambos()
 * sin chequear el resultado. Con esta tanda, TODOS los flujos
 * del piloto aplican el patrón C.6: si guardar_ambos falla,
 * devuelven exito: false con error específico.
 *
 * Excepciones (documentadas en plan_actual.md):
 *   - migrar_pasajeros_de_micro: ya cubierto (Tanda 1, 1.5piloto.82e).
 *   - limpiar_ventas_de_prueba: no llama guardar_ambos
 *     (cada cancelar_venta interno lo hace).
 *   - listar_viajes_de_dueno (drenar buzón): lectura con
 *     side-effect. Si falla, se loguea pero se devuelve el
 *     listado igual.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83h';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// Viaje.php — 8 flujos
// ============================================================

// 1. agregar_viaje (legacy, no usado por el enrutador)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'agregar_viaje: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("agregar_viaje: guardar_ambos falló para viaje=$nombre_viaje");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El alta del viaje se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 2. editar_viaje (legacy, no usado por el enrutador)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'editar_viaje: chequear guardar_ambos',
    'buscar' => [
        '        if (!$resultado_paradas[\'exito\']) {',
        '            return $resultado_paradas;',
        '        }',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
        '',
        '/**',
        ' * Elimina un viaje.',
    ],
    'reemplazar' => [
        '        if (!$resultado_paradas[\'exito\']) {',
        '            return $resultado_paradas;',
        '        }',
        '    }',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("editar_viaje: guardar_ambos falló para viaje=$nombre_viaje");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La edición del viaje se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
        '',
        '/**',
        ' * Elimina un viaje.',
    ],
],

// 3. guardar_viaje_completo (el que usa el enrutador)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'guardar_viaje_completo: chequear guardar_ambos',
    'buscar' => [
        '    $aplicar_retroactivo = (($datos[\'aplicar_retroactivo\'] ?? \'\') === \'1\');',
        '    $resultado_opciones = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones, $aplicar_retroactivo);',
        '    if (!$resultado_opciones[\'exito\']) {',
        '        return $resultado_opciones;',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $aplicar_retroactivo = (($datos[\'aplicar_retroactivo\'] ?? \'\') === \'1\');',
        '    $resultado_opciones = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones, $aplicar_retroactivo);',
        '    if (!$resultado_opciones[\'exito\']) {',
        '        return $resultado_opciones;',
        '    }',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    // Nota: guardar_opciones_avanzadas_viaje ya guardó internamente;',
        '    // este segundo guardar_ambos es redundante pero inofensivo. Si falla,',
        '    // algo raro pasó (conexión perdida entre medio) y conviene alertar.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("guardar_viaje_completo: guardar_ambos final falló para viaje=$nombre_viaje");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El viaje y sus opciones se procesaron en memoria pero no se pudo confirmar el guardado final en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 4. agregar_terminal_autorizada
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'agregar_terminal_autorizada: chequear guardar_ambos',
    'buscar' => [
        '    _sincronizar_terminal_en_compartido_del_viaje($nodo_viaje, $nombre_terminal, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    _sincronizar_terminal_en_compartido_del_viaje($nodo_viaje, $nombre_terminal, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("agregar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La autorización de la terminal se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 5. eliminar_terminal_autorizada
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'eliminar_terminal_autorizada: chequear guardar_ambos',
    'buscar' => [
        '    _desincronizar_terminal_de_compartido_del_viaje($nodo_viaje, $nombre_terminal);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    _desincronizar_terminal_de_compartido_del_viaje($nodo_viaje, $nombre_terminal);',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("eliminar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La desautorización de la terminal se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 6. guardar_opciones_terminal_viaje
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'guardar_opciones_terminal_viaje: chequear guardar_ambos',
    'buscar' => [
        '    $cambio_permite_terminal = ($config_antes[\'permite_efectivo\'] !== $config_despues[\'permite_efectivo\']',
        '        || $config_antes[\'permite_transferencia\'] !== $config_despues[\'permite_transferencia\']);',
        '    if ($cambio_permite_terminal) {',
        '        _aplicar_retroactivo_a_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, $snapshot_ventas, $aplicar_retroactivo);',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $cambio_permite_terminal = ($config_antes[\'permite_efectivo\'] !== $config_despues[\'permite_efectivo\']',
        '        || $config_antes[\'permite_transferencia\'] !== $config_despues[\'permite_transferencia\']);',
        '    if ($cambio_permite_terminal) {',
        '        _aplicar_retroactivo_a_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, $snapshot_ventas, $aplicar_retroactivo);',
        '    }',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("guardar_opciones_terminal_viaje: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'Las opciones de la terminal se procesaron en memoria pero no se pudieron guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 7. guardar_declaracion_jurada
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'guardar_declaracion_jurada: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_dj = $nodo_viaje->adyacente($enlace);',
        '    if ($nodo_dj) {',
        '        $nodo_dj->_dato($contenido);',
        '    } else {',
        '        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($contenido), $enlace);',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_dj = $nodo_viaje->adyacente($enlace);',
        '    if ($nodo_dj) {',
        '        $nodo_dj->_dato($contenido);',
        '    } else {',
        '        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($contenido), $enlace);',
        '    }',
        '',
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("guardar_declaracion_jurada: guardar_ambos falló para viaje=$nombre_viaje tipo=$tipo");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La declaración jurada se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 8. limpiar_viajes_de_prueba
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'limpiar_viajes_de_prueba: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
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
        '}',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("limpiar_viajes_de_prueba: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La limpieza se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
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
        '}',
    ],
],

// ============================================================
// Pasajero.php — 1 flujo
// ============================================================

// 9. limpiar_pasajeros_de_prueba
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'limpiar_pasajeros_de_prueba: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'borrados\' => $borrados,',
        '        \'cantidad_borrados\' => count($borrados),',
        '        \'conservados_con_referencias\' => $conservados_con_referencias,',
        '        \'conservados_no_prueba\' => $conservados_no_prueba,',
        '    ];',
        '}',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("limpiar_pasajeros_de_prueba: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La limpieza se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'borrados\' => $borrados,',
        '        \'cantidad_borrados\' => count($borrados),',
        '        \'conservados_con_referencias\' => $conservados_con_referencias,',
        '        \'conservados_no_prueba\' => $conservados_no_prueba,',
        '    ];',
        '}',
    ],
],

// ============================================================
// Venta.php — 1 flujo
// ============================================================

// 10. limpiar_cancelaciones_de_prueba
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'limpiar_cancelaciones_de_prueba: chequear guardar_ambos',
    'buscar' => [
        '    if (!empty($borrados)) {',
        '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'borrados\' => $borrados,',
        '        \'cantidad_borrados\' => count($borrados),',
        '        \'salteados\' => $salteados,',
        '        \'cantidad_salteados\' => count($salteados),',
        '    ];',
        '}',
    ],
    'reemplazar' => [
        '    if (!empty($borrados)) {',
        '        // Fix 1.5piloto.83h: chequear guardar_ambos.',
        '        $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '        if (!$ok_guardar) {',
        '            Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");',
        '            return [',
        '                \'exito\' => false,',
        '                \'error\' => \'La limpieza se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '            ];',
        '        }',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'borrados\' => $borrados,',
        '        \'cantidad_borrados\' => count($borrados),',
        '        \'salteados\' => $salteados,',
        '        \'cantidad_salteados\' => count($salteados),',
        '    ];',
        '}',
    ],
],

// ============================================================
// plan_actual.md — docs
// ============================================================

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83h',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83g (tanda de los',
        '15 flujos con `guardar_ambos` sin chequear. Aplicado el',
        'patrón C.6: ahora reportan `exito: false` si el guardado',
        'falla, en lugar de mentir con "éxito" con la DB intacta).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83h (tanda final de',
        'cobertura de `guardar_ambos`. Los 10 flujos restantes del',
        'piloto aplican el patrón C.6. **Todos los flujos del',
        'piloto que llaman `guardar_ambos` chequean el resultado.**',
        'Excepciones documentadas en §4.7).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: marcar los 10 flujos restantes',
    'buscar' => [
        '- **Deuda técnica restante (tanda chiquita siguiente):**',
        '  quedan ~10 flujos más con el mismo patrón:',
        '  `agregar_viaje`, `editar_viaje`,',
        '  `guardar_viaje_completo`,',
        '  `agregar_terminal_autorizada`,',
        '  `eliminar_terminal_autorizada`,',
        '  `guardar_opciones_terminal_viaje`,',
        '  `guardar_declaracion_jurada`,',
        '  `limpiar_viajes_de_prueba`,',
        '  `limpiar_pasajeros_de_prueba`,',
        '  `migrar_pasajeros_de_micro`,',
        '  `limpiar_ventas_de_prueba`,',
        '  `limpiar_cancelaciones_de_prueba`.',
    ],
    'reemplazar' => [
        '- **10 flujos restantes: CERRADOS en v1.5piloto.83h.**',
        '  `agregar_viaje`, `editar_viaje`,',
        '  `guardar_viaje_completo`,',
        '  `agregar_terminal_autorizada`,',
        '  `eliminar_terminal_autorizada`,',
        '  `guardar_opciones_terminal_viaje`,',
        '  `guardar_declaracion_jurada`,',
        '  `limpiar_viajes_de_prueba`,',
        '  `limpiar_pasajeros_de_prueba`,',
        '  `limpiar_cancelaciones_de_prueba`. Todos aplican el',
        '  patrón C.6.',
        '- **Excepciones (no requieren fix):**',
        '  - `migrar_pasajeros_de_micro`: ya cubierto (Tanda 1,',
        '    1.5piloto.82e).',
        '  - `limpiar_ventas_de_prueba`: no llama `guardar_ambos`',
        '    directamente (cada `cancelar_venta` interno lo hace',
        '    y ya está chequeado desde v81w).',
        '  - `listar_viajes_de_dueno` (drenar buzón): lectura',
        '    con side-effect. Si falla, se loguea pero se devuelve',
        '    el listado igual.',
        '- **Estado de cobertura: COMPLETA.** Todos los flujos',
        '  del piloto que llaman `guardar_ambos` chequean el',
        '  resultado.',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado del proyecto al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83g',
        '(framework 1.5i.7u). **Bug §4.7 contenido:** cerrado',
        'como bug activo tras sesión de diagnóstico que no logró',
        'reproducirlo post-restauración + blindaje. Los 4 bugs',
        'encadenados del flujo de venta en micro de 2 pisos',
        'quedaron cerrados. Blindaje activo (rollback si pierde',
        'bits, nodos o aristas) + logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). Los 15 flujos con',
        '`guardar_ambos` sin chequear quedaron cubiertos con el',
        'patrón C.6 (v1.5piloto.83g). Queda deuda de Fase 4',
        '(no saltar nodos con máscara 0 + propagar a fixpoint)',
        'para sesión del framework, y ~10 flujos más con el',
        'mismo patrón para tanda chiquita. SQL64 activo,',
        'topología admin/soporte, auditoría topológica',
        'disponible. **La migración de pasajeros sigue en pausa**',
        'hasta validación post-83g.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83h',
        '(framework 1.5i.7u). **Bug §4.7 contenido:** cerrado',
        'como bug activo tras sesión de diagnóstico que no logró',
        'reproducirlo post-restauración + blindaje. Los 4 bugs',
        'encadenados del flujo de venta en micro de 2 pisos',
        'quedaron cerrados. Blindaje activo (rollback si pierde',
        'bits, nodos o aristas) + logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). **Cobertura de',
        '`guardar_ambos`: COMPLETA.** Todos los flujos del',
        'piloto chequean el resultado (v1.5piloto.83g + 83h).',
        'Queda deuda de Fase 4 (no saltar nodos con máscara 0 +',
        'propagar a fixpoint) para sesión del framework. SQL64',
        'activo, topología admin/soporte, auditoría topológica',
        'disponible. **La migración de pasajeros sigue en pausa**',
        'hasta validación post-83h.',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
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

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
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

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";