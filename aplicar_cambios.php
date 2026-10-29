<?php
/**
 * Aplicador — Piloto 1.5piloto.83n: cobertura de guardar_ambos
 * en 15 flujos + log_persistencia faltante + 4 comandos de admin.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83n';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Empresa.php: agregar_empresa (línea 88)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Empresas/Empresa.php',
    'descripcion' => 'agregar_empresa: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_empresas->_adyacente_en($nodo_empresa, $nombre_empresa);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_empresas->_adyacente_en($nodo_empresa, $nombre_empresa);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_empresa: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_empresa: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La empresa se creó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 2. Empresa.php: actualizar_empresa (línea 125)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Empresas/Empresa.php',
    'descripcion' => 'actualizar_empresa: chequear guardar_ambos',
    'buscar' => [
        '        $nodo_empresa->_adyacente_en(Nodo::crear_con_dato($nuevo_nombre), \'nombre\');',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '        $nodo_empresa->_adyacente_en(Nodo::crear_con_dato($nuevo_nombre), \'nombre\');',
        '    }',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_empresa: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_empresa: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La actualización se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 3. Empresa.php: eliminar_empresa (línea 180)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Empresas/Empresa.php',
    'descripcion' => 'eliminar_empresa: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
        '    _destruir_campos_simples($nodo_empresa);',
        '    Nodo::eliminar($nodo_empresa);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
        '    _destruir_campos_simples($nodo_empresa);',
        '    Nodo::eliminar($nodo_empresa);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("eliminar_empresa: guardar_ambos falló", \'error\');',
        '        Controlador::_error("eliminar_empresa: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La eliminación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 4. Usuario.php: agregar_usuario (línea 819) — solo log
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'agregar_usuario: log de guardar_ambos',
    'buscar' => [
        '    // Si es soporte, asignar dueños.',
        '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
        '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
    ],
    'reemplazar' => [
        '    // Si es soporte, asignar dueños.',
        '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
        '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
        '    }',
        '',
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales, y un return acá dejaría el usuario sin credenciales.',
        '    // El return temprano correcto requiere revisar el formato exacto',
        '    // de la respuesta; queda como deuda técnica en plan_actual.md.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_usuario: guardar_ambos falló");',
        '    }',
    ],
],

// ============================================================
// 5. Usuario.php: actualizar_usuario (línea 1020) — solo log
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'actualizar_usuario: log de guardar_ambos',
    'buscar' => [
        '            // huérfanos (grafo:eliminar_huerfanos).',
        '            _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_usuario, $dueno, $nombre_usuario);',
        '        }',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
    ],
    'reemplazar' => [
        '            // huérfanos (grafo:eliminar_huerfanos).',
        '            _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_usuario, $dueno, $nombre_usuario);',
        '        }',
        '    }',
        '',
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales. Ver nota en agregar_usuario.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_usuario: guardar_ambos falló");',
        '    }',
    ],
],

// ============================================================
// 6. Usuario.php: eliminar_usuario (línea 1229) — solo log
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'eliminar_usuario: log de guardar_ambos',
    'buscar' => [
        '    Nodo::eliminar($nodo_usuario);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    // === 7. Destruir en credenciales: sesiones + nodo + campos ===',
    ],
    'reemplazar' => [
        '    Nodo::eliminar($nodo_usuario);',
        '',
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales. Ver nota en agregar_usuario.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("eliminar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("eliminar_usuario: guardar_ambos falló");',
        '    }',
        '',
        '    // === 7. Destruir en credenciales: sesiones + nodo + campos ===',
    ],
],

// ============================================================
// 7. Vehiculo.php: agregar_vehiculo (línea 178)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
    'descripcion' => 'agregar_vehiculo: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_vehiculo: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_vehiculo: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El vehículo se creó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 8. Vehiculo.php: actualizar_vehiculo (línea 243)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
    'descripcion' => 'actualizar_vehiculo: chequear guardar_ambos',
    'buscar' => [
        '            $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), \'asientos\');',
        '        }',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '            $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), \'asientos\');',
        '        }',
        '    }',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_vehiculo: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_vehiculo: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La actualización se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 9. Vehiculo.php: actualizar_configuracion_vehiculo (línea 352)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
    'descripcion' => 'actualizar_configuracion_vehiculo: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_asientos->_dato((string)$total_asientos);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_asientos->_dato((string)$total_asientos);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_configuracion_vehiculo: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_configuracion_vehiculo: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La configuración se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 10. Vehiculo.php: eliminar_vehiculo (línea 391)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
    'descripcion' => 'eliminar_vehiculo: chequear guardar_ambos',
    'buscar' => [
        '    $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
        '    _destruir_vehiculo_completo($nodo_vehiculo);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
        '    _destruir_vehiculo_completo($nodo_vehiculo);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("eliminar_vehiculo: guardar_ambos falló", \'error\');',
        '        Controlador::_error("eliminar_vehiculo: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La eliminación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 11. Vehiculo.php: subir_foto_vehiculo (línea 501)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
    'descripcion' => 'subir_foto_vehiculo: chequear guardar_ambos',
    'buscar' => [
        '    // Guardar persistencia',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [\'exito\' => true, \'foto\' => $ruta_relativa];',
    ],
    'reemplazar' => [
        '    // Guardar persistencia.',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("subir_foto_vehiculo: guardar_ambos falló", \'error\');',
        '        Controlador::_error("subir_foto_vehiculo: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La foto se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [\'exito\' => true, \'foto\' => $ruta_relativa];',
    ],
],

// ============================================================
// 12. Liquidacion.php: agregar_liquidacion (línea 142)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
    'descripcion' => 'agregar_liquidacion: chequear guardar_ambos',
    'buscar' => [
        '    _hmi($contenedor, $nodo_liq);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_liquidacion\' => $id_liquidacion,',
    ],
    'reemplazar' => [
        '    _hmi($contenedor, $nodo_liq);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_liquidacion: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_liquidacion: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La liquidación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_liquidacion\' => $id_liquidacion,',
    ],
],

// ============================================================
// 13. Rendicion.php: agregar_rendicion (línea 520)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
    'descripcion' => 'agregar_rendicion: chequear guardar_ambos',
    'buscar' => [
        '    // Insertar la rendición al inicio del contenedor del dueño.',
        '    _hmi($contenedor, $nodo_rendicion);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_rendicion\' => $id_rendicion,',
    ],
    'reemplazar' => [
        '    // Insertar la rendición al inicio del contenedor del dueño.',
        '    _hmi($contenedor, $nodo_rendicion);',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_rendicion: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_rendicion: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La rendición se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_rendicion\' => $id_rendicion,',
    ],
],

// ============================================================
// 14. Rendicion.php: aceptar_ajuste_rendicion (línea 789)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
    'descripcion' => 'aceptar_ajuste_rendicion: chequear guardar_ambos',
    'buscar' => [
        '    $ajuste->_adyacente_en(Nodo::crear_con_dato(date(\'d/m/Y H:i\')), \'aceptada_en\');',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    $ajuste->_adyacente_en(Nodo::crear_con_dato(date(\'d/m/Y H:i\')), \'aceptada_en\');',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("aceptar_ajuste_rendicion: guardar_ambos falló", \'error\');',
        '        Controlador::_error("aceptar_ajuste_rendicion: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La aceptación del ajuste se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 15. ViajeOpciones.php: guardar_opciones_avanzadas_viaje (línea 134)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
    'descripcion' => 'guardar_opciones_avanzadas_viaje: chequear guardar_ambos',
    'buscar' => [
        '    if ($cambio_permite_viaje) {',
        '        _aplicar_retroactivo_a_ventas_del_viaje($nombre_dueno, $nombre_viaje, $snapshot_ventas, $aplicar_retroactivo);',
        '    }',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
    ],
    'reemplazar' => [
        '    if ($cambio_permite_viaje) {',
        '        _aplicar_retroactivo_a_ventas_del_viaje($nombre_dueno, $nombre_viaje, $snapshot_ventas, $aplicar_retroactivo);',
        '    }',
        '',
        '    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("guardar_opciones_avanzadas_viaje: guardar_ambos falló", \'error\');',
        '        Controlador::_error("guardar_opciones_avanzadas_viaje: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'Las opciones se procesaron en memoria pero no se pudieron guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true];',
    ],
],

// ============================================================
// 16. ViajeMicros.php: migrar_pasajeros_de_micro (línea 505)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'migrar_pasajeros_de_micro: agregar log_persistencia',
    'buscar' => [
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino");',
    ],
    'reemplazar' => [
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino", \'error\');',
        '        Controlador::_error("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino");',
    ],
],

// ============================================================
// 17. Enrutador.php: grafo/eliminar_huerfanos (línea 1665)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'grafo/eliminar_huerfanos: chequear guardar_ambos',
    'buscar' => [
        '                    $resultado_huerfanos = Controlador::ejecutar_comando(\'grafo:eliminar_huerfanos\');',
        '                    if ($resultado_huerfanos === null) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'No se pudo ejecutar la eliminación\']);',
        '                    }',
        '                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    responder_json([',
        '                        \'exito\' => true,',
        '                        \'eliminados\' => $resultado_huerfanos[\'eliminados\'],',
        '                        \'total_huerfanos\' => $resultado_huerfanos[\'total_huerfanos\'],',
        '                    ]);',
    ],
    'reemplazar' => [
        '                    $resultado_huerfanos = Controlador::ejecutar_comando(\'grafo:eliminar_huerfanos\');',
        '                    if ($resultado_huerfanos === null) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'No se pudo ejecutar la eliminación\']);',
        '                    }',
        '                    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '                    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    if (!$ok_guardar) {',
        '                        log_persistencia("grafo/eliminar_huerfanos: guardar_ambos falló", \'error\');',
        '                        Controlador::_error("grafo/eliminar_huerfanos: guardar_ambos falló");',
        '                        responder_json([',
        '                            \'exito\' => false,',
        '                            \'error\' => \'La eliminación se procesó en memoria pero no se pudo guardar en la base de datos.\',',
        '                            \'eliminados\' => $resultado_huerfanos[\'eliminados\'],',
        '                            \'total_huerfanos\' => $resultado_huerfanos[\'total_huerfanos\'],',
        '                        ]);',
        '                    }',
        '                    responder_json([',
        '                        \'exito\' => true,',
        '                        \'eliminados\' => $resultado_huerfanos[\'eliminados\'],',
        '                        \'total_huerfanos\' => $resultado_huerfanos[\'total_huerfanos\'],',
        '                    ]);',
    ],
],

// ============================================================
// 18. Enrutador.php: grafo/migraciones_listar (línea 1738)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'grafo/migraciones_listar: chequear guardar_ambos',
    'buscar' => [
        '                    // Si hubo auto-marcado, guardar.',
        '                    if (!empty($lista[\'auto_marcadas\'])) {',
        '                        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    }',
        '                    responder_json([\'exito\' => true, \'migraciones\' => $lista[\'migraciones\']]);',
    ],
    'reemplazar' => [
        '                    // Si hubo auto-marcado, guardar.',
        '                    if (!empty($lista[\'auto_marcadas\'])) {',
        '                        // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '                        $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                        if (!$ok_guardar) {',
        '                            log_persistencia("grafo/migraciones_listar: guardar_ambos falló", \'error\');',
        '                            Controlador::_error("grafo/migraciones_listar: guardar_ambos falló");',
        '                            responder_json([',
        '                                \'exito\' => false,',
        '                                \'error\' => \'Las migraciones se auto-marcaron en memoria pero no se pudo guardar en la base de datos.\',',
        '                                \'migraciones\' => $lista[\'migraciones\'],',
        '                            ]);',
        '                        }',
        '                    }',
        '                    responder_json([\'exito\' => true, \'migraciones\' => $lista[\'migraciones\']]);',
    ],
],

// ============================================================
// 19. Enrutador.php: grafo/migracion_limpiar (línea 1756)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'grafo/migracion_limpiar: chequear guardar_ambos',
    'buscar' => [
        '                    $res_lim = Controlador::ejecutar_comando(\'app:migracion_limpiar_marcadores\', [\'id\' => $id_lim]);',
        '                    if (!is_array($res_lim) || empty($res_lim[\'exito\'])) {',
        '                        $err = is_array($res_lim) && isset($res_lim[\'error\']) ? $res_lim[\'error\'] : \'Error al limpiar.\';',
        '                        responder_json([\'exito\' => false, \'error\' => $err]);',
        '                    }',
        '                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    responder_json([\'exito\' => true, \'limpiados\' => $res_lim[\'limpiados\'] ?? 0]);',
    ],
    'reemplazar' => [
        '                    $res_lim = Controlador::ejecutar_comando(\'app:migracion_limpiar_marcadores\', [\'id\' => $id_lim]);',
        '                    if (!is_array($res_lim) || empty($res_lim[\'exito\'])) {',
        '                        $err = is_array($res_lim) && isset($res_lim[\'error\']) ? $res_lim[\'error\'] : \'Error al limpiar.\';',
        '                        responder_json([\'exito\' => false, \'error\' => $err]);',
        '                    }',
        '                    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '                    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    if (!$ok_guardar) {',
        '                        log_persistencia("grafo/migracion_limpiar: guardar_ambos falló", \'error\');',
        '                        Controlador::_error("grafo/migracion_limpiar: guardar_ambos falló");',
        '                        responder_json([',
        '                            \'exito\' => false,',
        '                            \'error\' => \'La limpieza se procesó en memoria pero no se pudo guardar en la base de datos.\',',
        '                            \'limpiados\' => $res_lim[\'limpiados\'] ?? 0,',
        '                        ]);',
        '                    }',
        '                    responder_json([\'exito\' => true, \'limpiados\' => $res_lim[\'limpiados\'] ?? 0]);',
    ],
],

// ============================================================
// 20. Enrutador.php: grafo/migraciones_aplicar (línea 1774)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'grafo/migraciones_aplicar: chequear guardar_ambos',
    'buscar' => [
        '                    $res_mig = Controlador::ejecutar_comando(\'app:migracion_aplicar\', [\'id\' => $id_mig]);',
        '                    if (!is_array($res_mig) || empty($res_mig[\'exito\'])) {',
        '                        $err = is_array($res_mig) && isset($res_mig[\'error\']) ? $res_mig[\'error\'] : \'Error al aplicar.\';',
        '                        responder_json([\'exito\' => false, \'error\' => $err]);',
        '                    }',
        '                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    responder_json([\'exito\' => true, \'detalles\' => $res_mig[\'detalles\'] ?? []]);',
    ],
    'reemplazar' => [
        '                    $res_mig = Controlador::ejecutar_comando(\'app:migracion_aplicar\', [\'id\' => $id_mig]);',
        '                    if (!is_array($res_mig) || empty($res_mig[\'exito\'])) {',
        '                        $err = is_array($res_mig) && isset($res_mig[\'error\']) ? $res_mig[\'error\'] : \'Error al aplicar.\';',
        '                        responder_json([\'exito\' => false, \'error\' => $err]);',
        '                    }',
        '                    // Fix 1.5piloto.83n: chequear guardar_ambos.',
        '                    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                    if (!$ok_guardar) {',
        '                        log_persistencia("grafo/migraciones_aplicar: guardar_ambos falló", \'error\');',
        '                        Controlador::_error("grafo/migraciones_aplicar: guardar_ambos falló");',
        '                        responder_json([',
        '                            \'exito\' => false,',
        '                            \'error\' => \'La migración se aplicó en memoria pero no se pudo guardar en la base de datos.\',',
        '                            \'detalles\' => $res_mig[\'detalles\'] ?? [],',
        '                        ]);',
        '                    }',
        '                    responder_json([\'exito\' => true, \'detalles\' => $res_mig[\'detalles\'] ?? []]);',
    ],
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
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
}

$bumps_atversion = 0;
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
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";