<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77b (cambiar de asiento: backend).
 *   - Venta.php: formatear_venta_resumida agrega micro_enlace.
 *     El dato del nodo micro es vacío, así que la única forma de
 *     referenciarlo desde otros flujos es el nombre del enlace.
 *   - ViajeAsientos.php: nueva función cambiar_asiento_pasaje
 *     + helper _buscar_asiento_en_venta_persistente.
 *   - Enrutador.php: subacción viajes/cambiar_asiento.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Venta.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.77b',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.76w',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.77b',
        ],
    ],

    // ============================================================
    // Venta.php — agregar $micro_enlace al cómputo
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: computar micro_enlace en formatear_venta_resumida',
        'buscar' => [
            '    $nombre_micro = \'\';',
            '    $micro_nombre_visible = \'\';',
            '    if ($nodo_micro) {',
            '        $nombre_micro = $nodo_micro->dato();',
            '        $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '        if ($nodo_copia && $nodo_copia->adyacente(\'nombre\')) {',
            '            $micro_nombre_visible = $nodo_copia->adyacente(\'nombre\')->dato();',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    $nombre_micro = \'\';',
            '    $micro_nombre_visible = \'\';',
            '    $micro_enlace = \'\';',
            '    if ($nodo_micro) {',
            '        $nombre_micro = $nodo_micro->dato();',
            '        $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '        if ($nodo_copia && $nodo_copia->adyacente(\'nombre\')) {',
            '            $micro_nombre_visible = $nodo_copia->adyacente(\'nombre\')->dato();',
            '        }',
            '        // v77b: nombre del enlace del micro dentro del contenedor',
            '        // `micros` del viaje. El dato del nodo micro es vacío por',
            '        // diseño, así que la única forma de referenciarlo desde',
            '        // otros flujos (por ejemplo, el cambio de asiento desde',
            '        // la pestaña Clientes) es a través del nombre del enlace.',
            '        if ($nodo_viaje) {',
            '            $nodos_micros_v = $nodo_viaje->adyacente(\'micros\');',
            '            if ($nodos_micros_v) {',
            '                foreach ((array)$nodos_micros_v->adyacentes() as $nombre_e => $nodo_m) {',
            '                    if ($nodo_m->id() === $nodo_micro->id()) {',
            '                        $micro_enlace = (string)$nombre_e;',
            '                        break;',
            '                    }',
            '                }',
            '            }',
            '        }',
            '    }',
        ],
    ],

    // ============================================================
    // Venta.php — agregar micro_enlace al return
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: agregar micro_enlace al return',
        'buscar' => [
            '        \'micro\' => $nombre_micro,',
            '        \'micro_nombre_visible\' => $micro_nombre_visible,',
        ],
        'reemplazar' => [
            '        \'micro\' => $nombre_micro,',
            '        \'micro_enlace\' => $micro_enlace,',
            '        \'micro_nombre_visible\' => $micro_nombre_visible,',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos.php: bump @version a 1.5piloto.77b',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76t',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77b',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — agregar cambiar_asiento_pasaje al final
    //
    // Ancla: el bloque `if (!empty($nodos_filtrados))` dentro de
    // deseleccionar_asiento_micro es único (solo aparece ahí). El
    // cierre del `while` de deseleccionar + `guardar_ambos` se
    // repite en otras funciones, así que anclamos con el bloque
    // de arriba, que es inequívoco.
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos.php: agregar cambiar_asiento_pasaje al final',
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
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
            '',
            '/**',
            ' * Cambia un pasaje de un asiento a otro dentro del mismo micro.',
            ' *',
            ' * Tanda v77b.',
            ' *',
            ' * Reglas:',
            ' * - El asiento viejo debe estar vendido o reservado y tener',
            ' *   pasajero asignado.',
            ' * - Terminal: solo puede mover asientos que él mismo vendió.',
            ' *   El asiento nuevo debe estar libre.',
            ' * - Dueño, admin o soporte: puede mover vendidos y reservados.',
            ' *   El asiento nuevo debe estar libre o reservado sin pasajero.',
            ' * - Si el asiento viejo era reservado y $dejar_reservado_viejo',
            ' *   es true, el asiento viejo queda reservado sin pasajero.',
            ' *   Si no, queda libre.',
            ' * - El id_venta no cambia. Solo se mueve el enlace del',
            ' *   asiento-en-venta persistente al nodo asiento nuevo.',
            ' *',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_micro',
            ' * @param string $fila_vieja',
            ' * @param string $columna_vieja',
            ' * @param string $fila_nueva',
            ' * @param string $columna_nueva',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_solicitante',
            ' * @param bool   $dejar_reservado_viejo',
            ' * @return array',
            ' */',
            'function cambiar_asiento_pasaje(',
            '    string $nombre_viaje,',
            '    string $nombre_micro,',
            '    string $fila_vieja,',
            '    string $columna_vieja,',
            '    string $fila_nueva,',
            '    string $columna_nueva,',
            '    string $nombre_dueno,',
            '    string $nombre_solicitante,',
            '    bool $dejar_reservado_viejo = false',
            '): array {',
            '    if ($nombre_viaje === \'\' || $nombre_micro === \'\' || $nombre_dueno === \'\' || $nombre_solicitante === \'\') {',
            '        return [\'exito\' => false, \'error\' => \'Parámetros incompletos\'];',
            '    }',
            '    if ($fila_vieja === $fila_nueva && $columna_vieja === $columna_nueva) {',
            '        return [\'exito\' => false, \'error\' => \'El asiento nuevo es el mismo que el actual\'];',
            '    }',
            '',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if (!$nodo_micros) return [\'exito\' => false, \'error\' => \'No hay micros\'];',
            '    $nodo_micro = $nodo_micros->adyacente($nombre_micro);',
            '    if (!$nodo_micro) return [\'exito\' => false, \'error\' => \'Micro no encontrado\'];',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '    if (!$nodo_copia) return [\'exito\' => false, \'error\' => \'No existe copia del vehículo\'];',
            '',
            '    // Buscar los dos asientos.',
            '    $nodo_asiento_viejo = null;',
            '    $nodo_asiento_nuevo = null;',
            '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if (!$piso) continue;',
            '            $cabeza = $piso->adyacente(\'asientos\');',
            '            if (!$cabeza) continue;',
            '            $actual = $cabeza->adyacente(\'primer\');',
            '            $seg = 0;',
            '            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {',
            '                $f = $actual->adyacente(\'fila\');',
            '                $c = $actual->adyacente(\'columna\');',
            '                if ($f && $c) {',
            '                    if ($f->dato() === $fila_vieja && $c->dato() === $columna_vieja) $nodo_asiento_viejo = $actual;',
            '                    if ($f->dato() === $fila_nueva && $c->dato() === $columna_nueva) $nodo_asiento_nuevo = $actual;',
            '                }',
            '                $actual = $actual->adyacente(\'siguiente\');',
            '                $seg++;',
            '            }',
            '        }',
            '    }',
            '    if (!$nodo_asiento_viejo) return [\'exito\' => false, \'error\' => \'Asiento actual no encontrado\'];',
            '    if (!$nodo_asiento_nuevo) return [\'exito\' => false, \'error\' => \'Asiento nuevo no encontrado\'];',
            '    if ($nodo_asiento_viejo->id() === $nodo_asiento_nuevo->id()) {',
            '        return [\'exito\' => false, \'error\' => \'El asiento nuevo es el mismo que el actual\'];',
            '    }',
            '',
            '    // El asiento viejo debe tener pasajero.',
            '    $nodo_pasajero = $nodo_asiento_viejo->adyacente(\'pasajero\');',
            '    if (!$nodo_pasajero) return [\'exito\' => false, \'error\' => \'El asiento actual no tiene pasajero asignado\'];',
            '',
            '    $nodo_estado_viejo = $nodo_asiento_viejo->adyacente(\'estado\');',
            '    $estado_viejo = $nodo_estado_viejo ? $nodo_estado_viejo->dato() : \'\';',
            '    if ($estado_viejo !== \'vendido\' && $estado_viejo !== \'reservado\') {',
            '        return [\'exito\' => false, \'error\' => \'Solo se pueden cambiar asientos vendidos o reservados\'];',
            '    }',
            '',
            '    // Validar permisos del solicitante.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios\'];',
            '    $nodo_sol = $raiz_usuarios->adyacente($nombre_solicitante);',
            '    if (!$nodo_sol) return [\'exito\' => false, \'error\' => \'Solicitante no encontrado\'];',
            '    $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
            '    $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
            '',
            '    $nodo_venta_viejo = null;',
            '    if ($estado_viejo === \'vendido\') {',
            '        $nodo_venta_viejo = $nodo_asiento_viejo->adyacente(\'venta\');',
            '        if ($nivel_sol === \'terminal\') {',
            '            if (!$nodo_venta_viejo) return [\'exito\' => false, \'error\' => \'No se pudo verificar la venta del asiento\'];',
            '            $nodo_term_vta = $nodo_venta_viejo->adyacente(\'terminal\');',
            '            $nombre_term_vta = $nodo_term_vta ? $nodo_term_vta->dato() : \'\';',
            '            if ($nombre_term_vta !== $nombre_solicitante) {',
            '                return [\'exito\' => false, \'error\' => \'Solo podés cambiar asientos vendidos por tu terminal\'];',
            '            }',
            '        }',
            '    } elseif ($estado_viejo === \'reservado\') {',
            '        if ($nivel_sol === \'terminal\') {',
            '            return [\'exito\' => false, \'error\' => \'No podés cambiar asientos reservados\'];',
            '        }',
            '    }',
            '',
            '    // Validar el asiento nuevo.',
            '    $nodo_pasajero_nuevo = $nodo_asiento_nuevo->adyacente(\'pasajero\');',
            '    if ($nodo_pasajero_nuevo) return [\'exito\' => false, \'error\' => \'El asiento nuevo ya tiene un pasajero asignado\'];',
            '',
            '    $nodo_estado_nuevo = $nodo_asiento_nuevo->adyacente(\'estado\');',
            '    $estado_nuevo = $nodo_estado_nuevo ? $nodo_estado_nuevo->dato() : \'libre\';',
            '    if ($nivel_sol === \'terminal\') {',
            '        if ($estado_nuevo !== \'libre\') return [\'exito\' => false, \'error\' => \'Solo podés mover a un asiento libre\'];',
            '    } else {',
            '        if ($estado_nuevo !== \'libre\' && $estado_nuevo !== \'reservado\') {',
            '            return [\'exito\' => false, \'error\' => \'El asiento nuevo no está disponible\'];',
            '        }',
            '    }',
            '',
            '    // Guardar referencias antes de tocar el grafo.',
            '    $nodo_reservado_por_viejo = $nodo_asiento_viejo->adyacente(\'reservado_por\');',
            '    $nodo_av_persistente = null;',
            '    if ($nodo_venta_viejo) {',
            '        $nodo_av_persistente = _buscar_asiento_en_venta_persistente($nodo_venta_viejo, $nodo_asiento_viejo);',
            '    }',
            '',
            '    // === Liberar el asiento viejo ===',
            '    $estado_viejo_str = ($estado_viejo === \'reservado\' && $dejar_reservado_viejo) ? \'reservado\' : \'libre\';',
            '    if ($nodo_estado_viejo) {',
            '        $nodo_estado_viejo->_dato($estado_viejo_str);',
            '    } else {',
            '        $nodo_asiento_viejo->_adyacente_en(Nodo::crear_con_dato($estado_viejo_str), \'estado\');',
            '    }',
            '    $nodo_asiento_viejo->eliminar_adyacente(\'pasajero\');',
            '    if ($estado_viejo_str === \'libre\') {',
            '        $nodo_asiento_viejo->eliminar_adyacente(\'reservado_por\');',
            '        $nodo_asiento_viejo->eliminar_adyacente(\'venta\');',
            '    }',
            '',
            '    // === Ocupar el asiento nuevo ===',
            '    if ($nodo_estado_nuevo) {',
            '        $nodo_estado_nuevo->_dato($estado_viejo);',
            '    } else {',
            '        $nodo_asiento_nuevo->_adyacente_en(Nodo::crear_con_dato($estado_viejo), \'estado\');',
            '    }',
            '    $nodo_asiento_nuevo->eliminar_adyacente(\'pasajero\');',
            '    $nodo_asiento_nuevo->_adyacente_en($nodo_pasajero, \'pasajero\');',
            '',
            '    if ($estado_viejo === \'reservado\') {',
            '        // Heredar reservado_por del viejo si quedó reservado.',
            '        if ($dejar_reservado_viejo && $nodo_reservado_por_viejo) {',
            '            $nodo_asiento_nuevo->eliminar_adyacente(\'reservado_por\');',
            '            $nodo_asiento_nuevo->_adyacente_en($nodo_reservado_por_viejo, \'reservado_por\');',
            '        }',
            '    } else {',
            '        // Vendido: heredar la venta.',
            '        if ($nodo_venta_viejo) {',
            '            $nodo_asiento_nuevo->eliminar_adyacente(\'venta\');',
            '            $nodo_asiento_nuevo->_adyacente_en($nodo_venta_viejo, \'venta\');',
            '        }',
            '    }',
            '',
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
            '',
            '/**',
            ' * Busca el nodo asiento-en-venta persistente que referencia',
            ' * al asiento dado dentro de una venta. Devuelve null si no',
            ' * lo encuentra. La lista es simple (`primer`/`siguiente`).',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @param Nodo $nodo_asiento',
            ' * @return Nodo|null',
            ' */',
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
    ],

    // ============================================================
    // Enrutador.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump @version a 1.5piloto.77b',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.76n',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77b',
        ],
    ],

    // ============================================================
    // Enrutador.php — subacción viajes/cambiar_asiento
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar case viajes/cambiar_asiento',
        'buscar' => [
            '                case \'liberar_reserva_asiento\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $fila = $post[\'fila\'] ?? \'\';',
            '                    $columna = $post[\'columna\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'liberar_reserva_asiento\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $fila = $post[\'fila\'] ?? \'\';',
            '                    $columna = $post[\'columna\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'cambiar_asiento\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $fila_vieja = $post[\'fila_vieja\'] ?? \'\';',
            '                    $columna_vieja = $post[\'columna_vieja\'] ?? \'\';',
            '                    $fila_nueva = $post[\'fila_nueva\'] ?? \'\';',
            '                    $columna_nueva = $post[\'columna_nueva\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $dejar_reservado_viejo = (($post[\'dejar_reservado_viejo\'] ?? \'0\') === \'1\');',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || $fila_vieja === \'\' || $columna_vieja === \'\' || $fila_nueva === \'\' || $columna_nueva === \'\' || empty($nombre_dueno) || empty($nombre_solicitante)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = cambiar_asiento_pasaje(',
            '                        $nombre_viaje, $nombre_micro,',
            '                        $fila_vieja, $columna_vieja,',
            '                        $fila_nueva, $columna_nueva,',
            '                        $nombre_dueno, $nombre_solicitante,',
            '                        $dejar_reservado_viejo',
            '                    );',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77b',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77a',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77b',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77b',
        'buscar' => [
            '**Tanda actual:** v77a (fix del bug 1, parte 2).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77b (nueva funcionalidad: cambiar de',
            'asiento). Backend.',
            '',
            '**Nueva funcionalidad "Cambiar de asiento".** Desde el',
            'detalle de un pasaje (croquis o pestaña Clientes), botón',
            'para mover el pasaje a otro asiento del mismo micro.',
            'Reglas:',
            '',
            '- Terminal: solo puede mover asientos que él mismo vendió,',
            '  y solo a un asiento libre. No toca reservados.',
            '- Dueño/admin/soporte: puede mover vendidos y reservados,',
            '  a un asiento libre o reservado sin pasajero.',
            '- El asiento viejo vendido queda libre.',
            '- El asiento viejo reservado puede quedar libre o',
            '  reservado sin pasajero (checkbox "Dejar el asiento',
            '  viejo reservado", por defecto tildado).',
            '- El asiento nuevo reservado solo se acepta si NO tiene',
            '  pasajero asignado (regla confirmada por el usuario).',
            '- El id_venta no cambia. El asiento-en-venta persistente',
            '  solo cambia su enlace `asiento`.',
            '- El `punto_subida_bajada` del asiento-en-venta no cambia.',
            '- Solo se puede cambiar dentro del mismo micro.',
            '',
            '**Backend v77b:** `Venta.php` agrega `micro_enlace` a',
            '`formatear_venta_resumida` (el dato del micro es vacío, el',
            'nombre del enlace es la única referencia). Nueva función',
            '`cambiar_asiento_pasaje` en `ViajeAsientos.php` + helper',
            '`_buscar_asiento_en_venta_persistente`. Subacción',
            '`viajes/cambiar_asiento` en el enrutador.',
            '',
            '**Frontend pendiente (v77c):** botón "Cambiar de asiento"',
            'en los dos modales de detalle del pasaje, modal con croquis',
            'y selector de asientos disponibles.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";