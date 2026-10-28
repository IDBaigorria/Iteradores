<?php
/**
 * Aplicador — Tanda de los 15 flujos con guardar_ambos sin chequear.
 *
 * Motivo: 15 flujos del piloto llaman guardar_ambos() y devuelven
 * exito: true sin chequear el resultado. Si el guardado falla (por
 * el bug §4.7 contenido, por timeout, por cualquier razón), el
 * backend miente: reporta "éxito" con la DB intacta. Patrón C.6
 * aplicado en confirmar_venta_actual y pagar_cupon_venta (v1.5piloto.83d).
 *
 * Flujos cubiertos (15):
 *   ViajeAsientos.php: reservar, asignar, liberar, seleccionar,
 *     deseleccionar, cambiar_asiento.
 *   ViajeMicros.php: agregar_micro, eliminar_micro, actualizar_monto.
 *   Viaje.php: eliminar_viaje.
 *   Pasajero.php: crear, actualizar, eliminar, subir_dj, eliminar_dj.
 *
 * No cubre (deuda técnica siguiente tanda):
 *   agregar_viaje, editar_viaje, guardar_viaje_completo,
 *   agregar_terminal_autorizada, eliminar_terminal_autorizada,
 *   guardar_opciones_terminal_viaje, guardar_declaracion_jurada,
 *   limpiar_viajes_de_prueba, limpiar_pasajeros_de_prueba,
 *   migrar_pasajeros_de_micro, limpiar_ventas_de_prueba,
 *   limpiar_cancelaciones_de_prueba, listar_viajes_de_dueno (drenar
 *   buzón, es lectura).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83g';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// ViajeAsientos.php — 6 flujos
// ============================================================

// 1. reservar_asiento_micro
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'reservar_asiento_micro: chequear guardar_ambos',
    'buscar' => [
        '        $nodo_asiento->eliminar_adyacente(\'pasajero\');',
        '        $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '        $nodo_asiento->eliminar_adyacente(\'pasajero\');',
        '        $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear el resultado del guardado.',
        '    // Antes se ignoraba y el backend respondía "éxito" aunque',
        '    // la DB no se hubiera actualizado. Patrón C.6.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La reserva se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 2. asignar_pasajero_a_reserva
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'asignar_pasajero_a_reserva: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("asignar_pasajero_a_reserva: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La asignación de pasajero se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 3. liberar_reserva_asiento_micro
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'liberar_reserva_asiento_micro: chequear guardar_ambos',
    'buscar' => [
        '    // A partir de v1.5piloto.38: al liberar la reserva se limpia también el pasajero.',
        '    $nodo_asiento->eliminar_adyacente(\'pasajero\');',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    // A partir de v1.5piloto.38: al liberar la reserva se limpia también el pasajero.',
        '    $nodo_asiento->eliminar_adyacente(\'pasajero\');',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("liberar_reserva_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La liberación de reserva se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 4. seleccionar_asiento_micro
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'seleccionar_asiento_micro: chequear guardar_ambos',
    'buscar' => [
        '    } else {',
        '        $cabeza_venta->eliminar_adyacente(\'primer\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    } else {',
        '        $cabeza_venta->eliminar_adyacente(\'primer\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("seleccionar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La selección de asiento se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 5. deseleccionar_asiento_micro
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'deseleccionar_asiento_micro: chequear guardar_ambos',
    'buscar' => [
        '                if (!empty($nodos_filtrados)) {',
        '                    $primer = $nodos_filtrados[0];',
        '                    $cabeza_venta->_adyacente_en($primer, \'primer\');',
        '                    $anterior = null;',
        '                    foreach ($nodos_filtrados as $nodo_venta) {',
        '                        if ($anterior) {',
        '                            $anterior->_adyacente_en($nodo_venta, \'siguiente\');',
        '                        }',
        '                        $anterior = $nodo_venta;',
        '                    }',
        '                    if ($anterior) {',
        '                        $anterior->_adyacente_en($cabeza_venta, \'siguiente\');',
        '                    }',
        '                }',
        '            }',
        '        }',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '                if (!empty($nodos_filtrados)) {',
        '                    $primer = $nodos_filtrados[0];',
        '                    $cabeza_venta->_adyacente_en($primer, \'primer\');',
        '                    $anterior = null;',
        '                    foreach ($nodos_filtrados as $nodo_venta) {',
        '                        if ($anterior) {',
        '                            $anterior->_adyacente_en($nodo_venta, \'siguiente\');',
        '                        }',
        '                        $anterior = $nodo_venta;',
        '                    }',
        '                    if ($anterior) {',
        '                        $anterior->_adyacente_en($cabeza_venta, \'siguiente\');',
        '                    }',
        '                }',
        '            }',
        '        }',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("deseleccionar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La deselección de asiento se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 6. cambiar_asiento_pasaje
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'cambiar_asiento_pasaje: chequear guardar_ambos',
    'buscar' => [
        '    // === Actualizar el asiento-en-venta persistente ===',
        '    if ($nodo_av_persistente) {',
        '        $nodo_av_persistente->eliminar_adyacente(\'asiento\');',
        '        $nodo_av_persistente->_adyacente_en($nodo_asiento_nuevo, \'asiento\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    // === Actualizar el asiento-en-venta persistente ===',
        '    if ($nodo_av_persistente) {',
        '        $nodo_av_persistente->eliminar_adyacente(\'asiento\');',
        '        $nodo_av_persistente->_adyacente_en($nodo_asiento_nuevo, \'asiento\');',
        '    }',
        '',
        '    actualizar_contadores_micro($nodo_micro);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("cambiar_asiento_pasaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El cambio de asiento se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// ============================================================
// ViajeMicros.php — 3 flujos
// ============================================================

// 7. agregar_micro_a_viaje
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'agregar_micro_a_viaje: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
        '}',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El alta del micro se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
        '}',
    ],
],

// 8. eliminar_micro_de_viaje
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'eliminar_micro_de_viaje: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_micros->eliminar_adyacente($nombre_micro);',
        '    _destruir_micro($nodo_micro);',
        '',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_micros->eliminar_adyacente($nombre_micro);',
        '    _destruir_micro($nodo_micro);',
        '',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("eliminar_micro_de_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La baja del micro se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 9. actualizar_monto_micro
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'actualizar_monto_micro: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_monto = $nodo_micro->adyacente(\'monto\');',
        '    if ($nodo_monto) {',
        '        $nodo_monto->_dato($monto);',
        '    } else {',
        '        $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), \'monto\');',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_monto = $nodo_micro->adyacente(\'monto\');',
        '    if ($nodo_monto) {',
        '        $nodo_monto->_dato($monto);',
        '    } else {',
        '        $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), \'monto\');',
        '    }',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("actualizar_monto_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La actualización del monto se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// ============================================================
// Viaje.php — 1 flujo (eliminar_viaje)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'eliminar_viaje: chequear guardar_ambos',
    'buscar' => [
        '    _destruir_viaje_completo($nodo_viaje);',
        '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
        '    Nodo::eliminar($nodo_viaje);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    _destruir_viaje_completo($nodo_viaje);',
        '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
        '    Nodo::eliminar($nodo_viaje);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("eliminar_viaje: guardar_ambos falló para viaje=$nombre_viaje");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La baja del viaje se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// ============================================================
// Pasajero.php — 5 flujos
// ============================================================

// 11. crear_pasajero
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'crear_pasajero: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato(date(\'Y-m-d\')), \'fecha_ultima_modificacion\');',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'pasajero\' => formatear_pasajero($dni_norm, $nodo_pasajero),',
        '    ];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato(date(\'Y-m-d\')), \'fecha_ultima_modificacion\');',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("crear_pasajero: guardar_ambos falló para dni=$dni_norm");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El alta del pasajero se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'pasajero\' => formatear_pasajero($dni_norm, $nodo_pasajero),',
        '    ];',
        '}',
    ],
],

// 12. actualizar_pasajero
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'actualizar_pasajero: chequear guardar_ambos',
    'buscar' => [
        '    if ($hubo_cambios) {',
        '        $hoy = date(\'Y-m-d\');',
        '        $nodo_fecha = $nodo_pasajero->adyacente(\'fecha_ultima_modificacion\');',
        '        if ($nodo_fecha) {',
        '            $nodo_fecha->_dato($hoy);',
        '        } else {',
        '            $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($hoy), \'fecha_ultima_modificacion\');',
        '        }',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    $activos = pasajero_tiene_pasajes_activos($nombre_dueno, $dni, $nodo_contexto);',
    ],
    'reemplazar' => [
        '    if ($hubo_cambios) {',
        '        $hoy = date(\'Y-m-d\');',
        '        $nodo_fecha = $nodo_pasajero->adyacente(\'fecha_ultima_modificacion\');',
        '        if ($nodo_fecha) {',
        '            $nodo_fecha->_dato($hoy);',
        '        } else {',
        '            $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($hoy), \'fecha_ultima_modificacion\');',
        '        }',
        '    }',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("actualizar_pasajero: guardar_ambos falló para dni=$dni");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La actualización del pasajero se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    $activos = pasajero_tiene_pasajes_activos($nombre_dueno, $dni, $nodo_contexto);',
    ],
],

// 13. eliminar_pasajero
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'eliminar_pasajero: chequear guardar_ambos',
    'buscar' => [
        '    // Fase 2, v76: destruir el subárbol completo antes de',
        '    // desenlazar del contenedor.',
        '    $contenedor->eliminar_adyacente($dni);',
        '    _destruir_pasajero_completo($nodo_pasajero);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    // Fase 2, v76: destruir el subárbol completo antes de',
        '    // desenlazar del contenedor.',
        '    $contenedor->eliminar_adyacente($dni);',
        '    _destruir_pasajero_completo($nodo_pasajero);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("eliminar_pasajero: guardar_ambos falló para dni=$dni");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La baja del pasajero se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// 14. subir_declaracion_jurada_pasajero
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'subir_declaracion_jurada_pasajero: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_dj->_adyacente_en(Nodo::crear_con_dato(date(\'d/m/Y H:i\')), \'fecha_subida\');',
        '    $nodo_pasajero->_adyacente_en($nodo_dj, \'declaracion_jurada\');',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'declaracion_jurada\' => formatear_pasajero($dni_norm, $nodo_pasajero)[\'declaracion_jurada\'],',
        '    ];',
        '}',
    ],
    'reemplazar' => [
        '    $nodo_dj->_adyacente_en(Nodo::crear_con_dato(date(\'d/m/Y H:i\')), \'fecha_subida\');',
        '    $nodo_pasajero->_adyacente_en($nodo_dj, \'declaracion_jurada\');',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("subir_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La subida de la declaración jurada se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'declaracion_jurada\' => formatear_pasajero($dni_norm, $nodo_pasajero)[\'declaracion_jurada\'],',
        '    ];',
        '}',
    ],
],

// 15. eliminar_declaracion_jurada_pasajero
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
    'descripcion' => 'eliminar_declaracion_jurada_pasajero: chequear guardar_ambos',
    'buscar' => [
        '    // Fase 2, v76: destruir el nodo DJ con sus 4 sub-hijos',
        '    // en lugar de solo desenlazarlo.',
        '    $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
        '    _destruir_declaracion_jurada_pasajero($nodo_dj);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    // Fase 2, v76: destruir el nodo DJ con sus 4 sub-hijos',
        '    // en lugar de solo desenlazarlo.',
        '    $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
        '    _destruir_declaracion_jurada_pasajero($nodo_dj);',
        '',
        '    // Fix 1.5piloto.83g: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("eliminar_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La baja de la declaración jurada se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
        '}',
    ],
],

// ============================================================
// plan_actual.md — docs
// ============================================================

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83g',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83f (cierre formal',
        'de §4.7 como "contenido, no reproducido". El bug del',
        'guardado parcial no se reprodujo post-restauración +',
        'blindaje en 20+ guardados de la sesión de diagnóstico.',
        'Los 4 bugs encadenados del flujo de venta en micro de 2',
        'pisos quedaron cerrados. Queda un hueco del blindaje',
        '(nodos con máscara 0 no detectados) documentado para la',
        'Fase 4, sesión del framework).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83g (tanda de los',
        '15 flujos con `guardar_ambos` sin chequear. Aplicado el',
        'patrón C.6: ahora reportan `exito: false` si el guardado',
        'falla, en lugar de mentir con "éxito" con la DB intacta).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.7 quitar deuda de los 15 flujos',
    'buscar' => [
        '- **Deuda técnica (tanda aparte):** ~15 flujos más',
        '  llaman `guardar_ambos` sin chequear.',
        '  `reservar_asiento_micro`, `asignar_pasajero_a_reserva`,',
        '  `liberar_reserva_asiento_micro`, `seleccionar_asiento_micro`,',
        '  `deseleccionar_asiento_micro`, `cambiar_asiento_pasaje`,',
        '  `agregar_micro_a_viaje`, `eliminar_micro_de_viaje`,',
        '  `actualizar_monto_micro`, `eliminar_viaje`,',
        '  `crear_pasajero`, `actualizar_pasajero`,',
        '  `eliminar_pasajero`,',
        '  `eliminar_declaracion_jurada_pasajero`,',
        '  `subir_declaracion_jurada_pasajero`. Todos tienen el',
        '  mismo síntoma: "éxito" sin persistir. Hacer después',
        '  de cerrar §4.7.',
    ],
    'reemplazar' => [
        '- **15 flujos con `guardar_ambos` sin chequear: CERRADOS',
        '  en v1.5piloto.83g.** `reservar_asiento_micro`,',
        '  `asignar_pasajero_a_reserva`,',
        '  `liberar_reserva_asiento_micro`, `seleccionar_asiento_micro`,',
        '  `deseleccionar_asiento_micro`, `cambiar_asiento_pasaje`,',
        '  `agregar_micro_a_viaje`, `eliminar_micro_de_viaje`,',
        '  `actualizar_monto_micro`, `eliminar_viaje`,',
        '  `crear_pasajero`, `actualizar_pasajero`,',
        '  `eliminar_pasajero`,',
        '  `eliminar_declaracion_jurada_pasajero`,',
        '  `subir_declaracion_jurada_pasajero`. Todos aplican',
        '  el patrón C.6: si `guardar_ambos` falla, devuelven',
        '  `exito: false` con error específico.',
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
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado del proyecto al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83b',
        '(framework 1.5i.7t). **Bug crítico abierto:** corrupción',
        'del grafo por guardado parcial (§4.7). Base restaurada',
        'desde backup + reparaciones idempotentes. Fases 1 y 2',
        'cerradas: blindaje del guardado parcial (rollback si',
        'pierde bits, nodos o aristas) y logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). Fase 3 (reproducir en',
        'local) es el próximo paso. SQL64 activo, topología',
        'admin/soporte, auditoría topológica disponible. **La',
        'migración de pasajeros queda en pausa** hasta que el bug',
        '§4.7 esté resuelto.',
    ],
    'reemplazar' => [
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