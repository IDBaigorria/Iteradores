<?php
/**
 * Aplicador — Tanda 1 (backend): migrar pasajeros entre micros.
 *
 * Plan: prompts/plan_migrar_micro.md §3 + §8.
 *
 * Cambios:
 * 1. ViajeMicros.php: include de Venta.php (arrastra
 *    ViajeAsientos.php y FuncionesAuxiliares.php).
 * 2. ViajeMicros.php: nueva función migrar_pasajeros_de_micro
 *    + helpers _hay_asientos_seleccionados_en_viaje y
 *    _recolectar_asientos_por_numero.
 * 3. Enrutador.php: nueva subacción viajes/migrar_pasajeros
 *    (dueño/admin/soporte, no terminal).
 * 4. plan_actual.md: registrar la Tanda 1 en el arco en curso.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.82e';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. ViajeMicros.php: agregar include de Venta.php
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: include de Venta.php (arrastra ViajeAsientos y FuncionesAuxiliares)',
    'buscar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Nodos/Nodo.php");',
        'include_once("./Controlador/Controlador.php");',
        'include_once("./Aplicacion/Compartidos.php");',
    ],
    'reemplazar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Nodos/Nodo.php");',
        'include_once("./Controlador/Controlador.php");',
        'include_once("./Aplicacion/Compartidos.php");',
        '// Tanda 1 (v1.5piloto.82e): migración de pasajeros entre',
        '// micros. Venta.php arrastra ViajeAsientos.php (donde vive',
        '// _buscar_asiento_en_venta_persistente) y FuncionesAuxiliares.php.',
        'include_once("./Aplicacion/Ventas/Venta.php");',
    ],
],

// ============================================================
// 2. ViajeMicros.php: nueva función + helpers, después de
//    eliminar_micro_de_viaje (antes de actualizar_monto_micro)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: migrar_pasajeros_de_micro + helpers',
    'buscar' => [
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
        '',
        '/**',
        ' * Actualiza el monto de un micro.',
        ' */',
    ],
    'reemplazar' => [
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
        '',
        '/**',
        ' * Migra los pasajeros (asientos vendidos y reservados) del',
        ' * micro origen al micro destino, dentro del mismo viaje.',
        ' *',
        ' * Reglas (cerradas en plan_migrar_micro.md §2):',
        ' *  - Mapeo por número de asiento. Si un asiento ocupado del',
        ' *    origen no existe en el destino (por número), falla.',
        ' *  - Todo o nada: si un chequeo previo falla, no se toca el grafo.',
        ' *  - Destino debe estar vacío: todos sus asientos en "libre" o',
        ' *    "no disponible".',
        ' *  - No venta en curso: si hay asientos "seleccionado" en algún',
        ' *    micro del viaje, falla.',
        ' *  - Estados migrados: "vendido" (conserva pasajero y venta) y',
        ' *    "reservado" (conserva reservado_por y, si tiene, pasajero).',
        ' *  - "no disponible" y "libre" del origen no se migran.',
        ' *  - Si el asiento destino ya viene "no disponible" y el origen',
        ' *    está ocupado en ese número, falla (conflicto real).',
        ' *  - Actualiza venta->micro y asiento-en-venta->asiento.',
        ' *  - Después de la migración, el origen queda sin asientos',
        ' *    ocupados (solo puede conservar "no disponible" y "libre").',
        ' *',
        ' * @param string    $nombre_dueno',
        ' * @param string    $nombre_viaje',
        ' * @param string    $nombre_micro_origen',
        ' * @param string    $nombre_micro_destino',
        ' * @param Nodo|null $nodo_contexto',
        ' * @return array',
        ' * @since 1.5piloto.82e',
        ' */',
        'function migrar_pasajeros_de_micro(',
        '    string $nombre_dueno,',
        '    string $nombre_viaje,',
        '    string $nombre_micro_origen,',
        '    string $nombre_micro_destino,',
        '    ?Nodo $nodo_contexto = null',
        '): array {',
        '    // 1. Resolver viaje.',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        '',
        '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
        '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
        '',
        '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
        '    if (!$nodo_micros) return [\'exito\' => false, \'error\' => \'No hay micros en el viaje\'];',
        '',
        '    // 2. Micro origen y destino.',
        '    if ($nombre_micro_origen === $nombre_micro_destino) {',
        '        return [\'exito\' => false, \'error\' => \'El micro origen y destino son el mismo\'];',
        '    }',
        '    $nodo_micro_origen = $nodo_micros->adyacente($nombre_micro_origen);',
        '    if (!$nodo_micro_origen) return [\'exito\' => false, \'error\' => \'Micro origen no encontrado\'];',
        '    $nodo_micro_destino = $nodo_micros->adyacente($nombre_micro_destino);',
        '    if (!$nodo_micro_destino) return [\'exito\' => false, \'error\' => \'Micro destino no encontrado\'];',
        '',
        '    // 3. Chequeos previos (todos antes de tocar el grafo).',
        '    // 3.1. No venta en curso.',
        '    if (_hay_asientos_seleccionados_en_viaje($nodo_micros)) {',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'Hay una venta en curso (asientos seleccionados). Reiniciá la selección antes de migrar.\',',
        '        ];',
        '    }',
        '',
        '    // 3.2. Recolectar asientos por número.',
        '    $asientos_origen = _recolectar_asientos_por_numero($nodo_micro_origen);',
        '    $asientos_destino = _recolectar_asientos_por_numero($nodo_micro_destino);',
        '',
        '    // 3.3. Destino vacío.',
        '    foreach ($asientos_destino as $num => $asiento) {',
        '        $estado = $asiento->adyacente(\'estado\');',
        '        $estado_str = $estado ? $estado->dato() : \'libre\';',
        '        if ($estado_str !== \'libre\' && $estado_str !== \'no disponible\') {',
        '            return [',
        '                \'exito\' => false,',
        '                \'error\' => \'El micro destino ya tiene asientos ocupados. Migrá a un micro vacío.\',',
        '            ];',
        '        }',
        '    }',
        '',
        '    // 3.4. Recolectar asientos ocupados del origen.',
        '    $migraciones = [];',
        '    foreach ($asientos_origen as $num => $asiento) {',
        '        $estado = $asiento->adyacente(\'estado\');',
        '        $estado_str = $estado ? $estado->dato() : \'libre\';',
        '        if ($estado_str !== \'vendido\' && $estado_str !== \'reservado\') continue;',
        '        $migraciones[] = [',
        '            \'numero\'          => (string)$num,',
        '            \'asiento_origen\'  => $asiento,',
        '            \'estado\'          => $estado_str,',
        '            \'pasajero\'        => $asiento->adyacente(\'pasajero\'),',
        '            \'venta\'           => $asiento->adyacente(\'venta\'),',
        '            \'reservado_por\'   => $asiento->adyacente(\'reservado_por\'),',
        '        ];',
        '    }',
        '',
        '    // 3.5. Capacidad suficiente.',
        '    $cant_ocupados_origen = count($migraciones);',
        '    $cant_destino = count($asientos_destino);',
        '    if ($cant_ocupados_origen > $cant_destino) {',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => "El micro destino no tiene capacidad suficiente ($cant_destino asientos vs $cant_ocupados_origen ocupados)",',
        '        ];',
        '    }',
        '',
        '    // 3.6. Todos los números existen en destino Y no están "no disponible".',
        '    foreach ($migraciones as $m) {',
        '        if (!isset($asientos_destino[$m[\'numero\']])) {',
        '            return [',
        '                \'exito\' => false,',
        '                \'error\' => "El micro destino no tiene el asiento {$m[\'numero\']}. No se puede migrar.",',
        '            ];',
        '        }',
        '        $estado_destino = $asientos_destino[$m[\'numero\']]->adyacente(\'estado\');',
        '        $estado_destino_str = $estado_destino ? $estado_destino->dato() : \'libre\';',
        '        if ($estado_destino_str === \'no disponible\') {',
        '            return [',
        '                \'exito\' => false,',
        '                \'error\' => "El asiento {$m[\'numero\']} del micro destino está marcado como no disponible. No se puede migrar.",',
        '            ];',
        '        }',
        '    }',
        '',
        '    // 4. Aplicación. try/catch por defensa: si algo falla acá,',
        '    //    el grafo en memoria queda modificado pero NO se persiste.',
        '    $vendidos = 0;',
        '    $reservados = 0;',
        '    $detalles = [];',
        '    $ventas_migradas = [];',
        '',
        '    try {',
        '        foreach ($migraciones as $m) {',
        '            $nodo_asiento_destino = $asientos_destino[$m[\'numero\']];',
        '',
        '            // 4.1. Cambiar estado del destino.',
        '            $estado_destino = $nodo_asiento_destino->adyacente(\'estado\');',
        '            if ($estado_destino) {',
        '                $estado_destino->_dato($m[\'estado\']);',
        '            } else {',
        '                $nodo_asiento_destino->_adyacente_en(Nodo::crear_con_dato($m[\'estado\']), \'estado\');',
        '            }',
        '',
        '            // 4.2. Asignar pasajero si lo hay.',
        '            if ($m[\'pasajero\']) {',
        '                $nodo_asiento_destino->_adyacente_en($m[\'pasajero\'], \'pasajero\', true);',
        '            }',
        '',
        '            // 4.3. Asignar venta o reservado_por según estado.',
        '            if ($m[\'estado\'] === \'vendido\') {',
        '                if ($m[\'venta\']) {',
        '                    $nodo_asiento_destino->_adyacente_en($m[\'venta\'], \'venta\', true);',
        '',
        '                    // Actualizar venta->micro una sola vez por venta.',
        '                    $id_venta = (string)$m[\'venta\']->dato();',
        '                    if ($id_venta !== \'\' && !isset($ventas_migradas[$id_venta])) {',
        '                        $m[\'venta\']->_adyacente_en($nodo_micro_destino, \'micro\', true);',
        '                        $ventas_migradas[$id_venta] = true;',
        '                    }',
        '',
        '                    // Actualizar asiento-en-venta persistente.',
        '                    $av_persistente = _buscar_asiento_en_venta_persistente($m[\'venta\'], $m[\'asiento_origen\']);',
        '                    if ($av_persistente) {',
        '                        $av_persistente->_adyacente_en($nodo_asiento_destino, \'asiento\', true);',
        '                    }',
        '                }',
        '                $vendidos++;',
        '                $detalles[] = "Asiento {$m[\'numero\']}: vendido -> vendido";',
        '            } else {',
        '                // Reservado.',
        '                if ($m[\'reservado_por\']) {',
        '                    $nodo_asiento_destino->_adyacente_en($m[\'reservado_por\'], \'reservado_por\', true);',
        '                }',
        '                $reservados++;',
        '                $con_pasajero = $m[\'pasajero\'] ? \' (con pasajero)\' : \' (sin pasajero)\';',
        '                $detalles[] = "Asiento {$m[\'numero\']}: reservado{$con_pasajero} -> reservado";',
        '            }',
        '',
        '            // 4.4. Limpiar asiento origen.',
        '            $estado_origen = $m[\'asiento_origen\']->adyacente(\'estado\');',
        '            if ($estado_origen) $estado_origen->_dato(\'libre\');',
        '            $m[\'asiento_origen\']->eliminar_adyacente(\'pasajero\');',
        '            $m[\'asiento_origen\']->eliminar_adyacente(\'venta\');',
        '            $m[\'asiento_origen\']->eliminar_adyacente(\'reservado_por\');',
        '            $m[\'asiento_origen\']->eliminar_adyacente(\'seleccionado_por\');',
        '        }',
        '    } catch (\\Throwable $e) {',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'Error durante la migración: \' . $e->getMessage() . \'. No se guardó nada.\',',
        '        ];',
        '    }',
        '',
        '    // 5. Post-migración: contadores.',
        '    actualizar_contadores_micro($nodo_micro_origen);',
        '    actualizar_contadores_micro($nodo_micro_destino);',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '',
        '    // 6. ¿Origen vacío? (sin vendidos ni reservados).',
        '    $origen_vacio = true;',
        '    foreach ($asientos_origen as $asiento) {',
        '        $estado = $asiento->adyacente(\'estado\');',
        '        $estado_str = $estado ? $estado->dato() : \'libre\';',
        '        if ($estado_str === \'vendido\' || $estado_str === \'reservado\') {',
        '            $origen_vacio = false;',
        '            break;',
        '        }',
        '    }',
        '',
        '    // 7. Guardar.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La migración se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'migrados\' => $vendidos + $reservados,',
        '        \'vendidos_migrados\' => $vendidos,',
        '        \'reservados_migrados\' => $reservados,',
        '        \'detalles\' => $detalles,',
        '        \'origen_vacio\' => $origen_vacio,',
        '    ];',
        '}',
        '',
        '/**',
        ' * Devuelve true si algún micro del viaje tiene asientos en estado',
        ' * "seleccionado". Sirve para bloquear la migración cuando hay',
        ' * una venta en curso.',
        ' *',
        ' * @param Nodo $nodo_micros Contenedor `micros` del viaje.',
        ' * @return bool',
        ' * @since 1.5piloto.82e',
        ' */',
        'function _hay_asientos_seleccionados_en_viaje(Nodo $nodo_micros): bool {',
        '    foreach ((array)$nodo_micros->adyacentes() as $nodo_micro) {',
        '        $copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
        '        if (!$copia) continue;',
        '        $asientos = $copia->adyacente(\'asientos\');',
        '        if (!$asientos) continue;',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            $seg = 0;',
        '            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {',
        '                $estado = $actual->adyacente(\'estado\');',
        '                if ($estado && $estado->dato() === \'seleccionado\') return true;',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '                $seg++;',
        '            }',
        '        }',
        '    }',
        '    return false;',
        '}',
        '',
        '/**',
        ' * Recolecta los asientos de un micro indexados por su número.',
        ' * Recorre los pisos 1 y 2 del vehiculo_copia. Si un número se',
        ' * repite entre pisos (no debería pasar por el chequeo de',
        ' * duplicados del frontend), el último gana.',
        ' *',
        ' * @param Nodo $nodo_micro',
        ' * @return array<string, Nodo>',
        ' * @since 1.5piloto.82e',
        ' */',
        'function _recolectar_asientos_por_numero(Nodo $nodo_micro): array {',
        '    $asientos = [];',
        '    $copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
        '    if (!$copia) return $asientos;',
        '    $nodo_asientos = $copia->adyacente(\'asientos\');',
        '    if (!$nodo_asientos) return $asientos;',
        '    for ($i = 1; $i <= 2; $i++) {',
        '        $piso = $nodo_asientos->adyacente("piso_$i");',
        '        if (!$piso) continue;',
        '        $cabeza = $piso->adyacente(\'asientos\');',
        '        if (!$cabeza) continue;',
        '        $actual = $cabeza->adyacente(\'primer\');',
        '        $seg = 0;',
        '        while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {',
        '            $num = (string)$actual->dato();',
        '            if ($num !== \'\') {',
        '                $asientos[$num] = $actual;',
        '            }',
        '            $actual = $actual->adyacente(\'siguiente\');',
        '            $seg++;',
        '        }',
        '    }',
        '    return $asientos;',
        '}',
        '',
        '/**',
        ' * Actualiza el monto de un micro.',
        ' */',
    ],
],

// ============================================================
// 3. Enrutador.php: nueva subacción viajes/migrar_pasajeros
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: case viajes/migrar_pasajeros',
    'buscar' => [
        '                case \'eliminar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'eliminar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
        '',
        '                case \'migrar_pasajeros\':',
        '                    // Tanda 1 (v1.5piloto.82e): mueve los asientos',
        '                    // vendidos/reservados de un micro a otro dentro',
        '                    // del mismo viaje. Dueño/admin/soporte. Terminal no.',
        '                    $nombre_solicitante_mig = $post[\'nombre_solicitante\'] ?? \'\';',
        '                    if ($nombre_solicitante_mig === \'\') {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Solicitante no especificado\']);',
        '                    }',
        '                    $nodo_sol_mig = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante_mig);',
        '                    $nivel_mig = \'\';',
        '                    if ($nodo_sol_mig) {',
        '                        $nivel_mig = $nodo_sol_mig->adyacente(\'nivel\') ? $nodo_sol_mig->adyacente(\'nivel\')->dato() : \'\';',
        '                        if ($nivel_mig === \'\') {',
        '                            $pub_mig = $nodo_sol_mig->adyacente(\'publico\');',
        '                            if ($pub_mig) $nivel_mig = $pub_mig->adyacente(\'nivel\') ? $pub_mig->adyacente(\'nivel\')->dato() : \'\';',
        '                        }',
        '                    }',
        '                    if (!in_array($nivel_mig, [\'admin\', \'soporte\', \'dueno\'], true)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Solo dueño, admin o soporte pueden migrar pasajeros\']);',
        '                    }',
        '                    $nombre_dueno_mig = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $nombre_viaje_mig = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro_origen_mig = $post[\'nombre_micro_origen\'] ?? \'\';',
        '                    $nombre_micro_destino_mig = $post[\'nombre_micro_destino\'] ?? \'\';',
        '                    if (empty($nombre_dueno_mig) || empty($nombre_viaje_mig) || empty($nombre_micro_origen_mig) || empty($nombre_micro_destino_mig)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $nodo_contexto_mig = _contexto_solicitante($nombre_solicitante_mig);',
        '                    $resultado_mig = migrar_pasajeros_de_micro(',
        '                        $nombre_dueno_mig,',
        '                        $nombre_viaje_mig,',
        '                        $nombre_micro_origen_mig,',
        '                        $nombre_micro_destino_mig,',
        '                        $nodo_contexto_mig',
        '                    );',
        '                    responder_json($resultado_mig);',
        '                    break;',
    ],
],

// ============================================================
// 4. plan_actual.md: registrar la Tanda 1
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: marcar Tanda 1 de migración de pasajeros',
    'buscar' => [
        '**Última actualización:** v1.5piloto.82d (cierre del arco de',
        'cancelaciones en compartidos + bug §4.6 de persistencia',
        'silenciosa en hostings compartidos).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.82e (Tanda 1 del arco de',
        'migración de pasajeros entre micros: backend. Ver §2 y §4.5).',
        '',
        '**Antes (v1.5piloto.82d):** cierre del arco de cancelaciones en',
        'compartidos + bug §4.6 de persistencia silenciosa en hostings',
        'compartidos.',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: agregar arco de migración de pasajeros',
    'buscar' => [
        '## 2. ARCOS CERRADOS RECIENTES',
        '',
        'Resumen de 1-2 líneas por arco. El detalle completo vive en',
        'los prompts correspondientes o en los commits.',
        '',
        '- **Cancelaciones en compartidos + persistencia robusta',
    ],
    'reemplazar' => [
        '## 2. ARCOS CERRADOS RECIENTES',
        '',
        'Resumen de 1-2 líneas por arco. El detalle completo vive en',
        'los prompts correspondientes o en los commits.',
        '',
        '- **Migración de pasajeros entre micros — Tanda 1',
        '  (v1.5piloto.82e).** Nueva función',
        '  `migrar_pasajeros_de_micro` en `ViajeMicros.php` +',
        '  subacción `viajes/migrar_pasajeros` en el Enrutador.',
        '  Mueve asientos vendidos/reservados de un micro a otro',
        '  dentro del mismo viaje, mapeando por número de asiento.',
        '  Todo o nada: si un chequeo previo falla, no se toca el',
        '  grafo. Frontend y tests pendientes (Tandas 2 y 3). Ver',
        '  `prompts/plan_migrar_micro.md`.',
        '- **Cancelaciones en compartidos + persistencia robusta',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: agregar sección de arco en curso',
    'buscar' => [
        '### 4.5. Próximas tandas sugeridas',
        '',
        '**Foco inmediato: Piloto — calidad.**',
    ],
    'reemplazar' => [
        '### 4.5. Próximas tandas sugeridas',
        '',
        '**Arco en curso: migración de pasajeros entre micros.**',
        '',
        'Plan completo en `prompts/plan_migrar_micro.md`.',
        '',
        '- **Tanda 1 (backend): cerrada en `1.5piloto.82e`.**',
        '  `migrar_pasajeros_de_micro` en',
        '  `Aplicacion/Viajes/ViajeMicros.php` + subacción',
        '  `viajes/migrar_pasajeros` en `Aplicacion/Enrutador.php`.',
        '- **Tanda 2 (frontend): pendiente.** Botón "Migrar pasajeros"',
        '  en la tarjeta del micro + modal con select de destino +',
        '  wire-up del POST + refresco del detalle del viaje.',
        '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
        '  + actualización de `aplicacion_POST.php`,',
        '  `prompt_piloto.md` §5.8 y §4.9, y este archivo.',
        '',
        '**Foco inmediato: Piloto — calidad.**',
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