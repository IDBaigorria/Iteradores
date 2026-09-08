<?php
/**
 * Funciones de gestión de asientos dentro de micros de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.26
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Obtiene la configuración de un piso con los estados actuales de sus asientos.
 */
function obtener_configuracion_piso_con_estado($nodo_piso): ?array {
    $nodo_filas = $nodo_piso->adyacente('filas');
    $nodo_columnas = $nodo_piso->adyacente('columnas');
    if (!$nodo_filas || !$nodo_columnas) return null;

    $filas = (int)$nodo_filas->dato();
    $columnas = (int)$nodo_columnas->dato();

    $asientos = [];
    $nodo_cabeza = $nodo_piso->adyacente('asientos');
    if ($nodo_cabeza) {
        $actual = $nodo_cabeza->adyacente('primer');
        while ($actual && $actual->id() !== $nodo_cabeza->id()) {
            $fila = $actual->adyacente('fila');
            $columna = $actual->adyacente('columna');
            $estado = $actual->adyacente('estado');
            $seleccionado_por = $actual->adyacente('seleccionado_por');
            $reservado_por = $actual->adyacente('reservado_por');

            $asiento_info = [
                'fila' => $fila ? $fila->dato() : '',
                'columna' => $columna ? $columna->dato() : '',
                'numero' => $actual->dato(),
                'estado' => $estado ? $estado->dato() : 'libre',
                'seleccionado_por' => $seleccionado_por ? $seleccionado_por->dato() : null,
                'reservado_por' => $reservado_por ? $reservado_por->dato() : null,
                'pasajero' => null,
                'venta' => null
            ];

            if (in_array($asiento_info['estado'], ['vendido', 'no disponible'])) {
                $nodo_pasajero = $actual->adyacente('pasajero');
                if ($nodo_pasajero) {
                    $pasajero = [];
                    $adyacentes_pasajero = (array) $nodo_pasajero->adyacentes();
                    foreach ($adyacentes_pasajero as $campo => $nodo_campo) {
                        $pasajero[$campo] = $nodo_campo->dato();
                    }
                    $asiento_info['pasajero'] = $pasajero;
                }
            }

            if ($asiento_info['estado'] === 'vendido') {
                $nodo_venta = $actual->adyacente('venta');
                if ($nodo_venta) {
                    $asiento_info['venta'] = true;
                }
            }

            $asientos[] = $asiento_info;
            $actual = $actual->adyacente('siguiente');
        }
    }

    return [
        'filas' => $filas,
        'columnas' => $columnas,
        'asientos' => $asientos
    ];
}

/**
 * Reserva un asiento para el equipo (dueño).
 */
function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    // Buscar asiento
    $nodo_asiento = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento->adyacente('estado');
    if ($estado && $estado->dato() !== 'libre') {
        return ['exito' => false, 'error' => 'El asiento no está libre para reservar'];
    }

    if ($estado) $estado->_dato('reservado');
    else $nodo_asiento->_adyacente_en(Nodo::crear_con_dato('reservado'), 'estado');

    $nodo_asiento->eliminar_adyacente('seleccionado_por');

    $reservado_por = $nodo_asiento->adyacente('reservado_por');
    if ($reservado_por) $reservado_por->_dato($nombre_dueno);
    else $nodo_asiento->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'reservado_por');

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Libera la reserva de un asiento.
 */
function liberar_reserva_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $nodo_asiento = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento->adyacente('estado');
    if (!$estado || $estado->dato() !== 'reservado') {
        return ['exito' => false, 'error' => 'El asiento no está reservado'];
    }

    $estado->_dato('libre');
    $nodo_asiento->eliminar_adyacente('reservado_por');

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Obtiene el estado resumido de todos los asientos de un micro.
 */
function obtener_estados_asientos_micro(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $asientos_estados = [];
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $fila = $actual->adyacente('fila');
                $columna = $actual->adyacente('columna');
                $estado = $actual->adyacente('estado');
                $seleccionado_por = $actual->adyacente('seleccionado_por');
                $asientos_estados[] = [
                    'fila' => $fila ? $fila->dato() : '',
                    'columna' => $columna ? $columna->dato() : '',
                    'numero' => $actual->dato(),
                    'estado' => $estado ? $estado->dato() : 'libre',
                    'seleccionado_por' => $seleccionado_por ? $seleccionado_por->dato() : null
                ];
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    return ['exito' => true, 'asientos' => $asientos_estados];
}

/**
 * Selecciona un asiento para una terminal.
 */
function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;
    if (!$nodo_terminal) return ['exito' => false, 'error' => 'Terminal no encontrada'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    // Buscar asiento
    $nodo_asiento_encontrado = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento_encontrado = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento_encontrado) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento_encontrado->adyacente('estado');
    if ($estado) {
        $estado_actual = $estado->dato();
        if ($estado_actual === 'reservado') {
            return ['exito' => false, 'error' => 'Asiento reservado para el equipo'];
        } elseif ($estado_actual === 'vendido') {
            return ['exito' => false, 'error' => 'El asiento ya está vendido'];
        } elseif ($estado_actual === 'no disponible') {
            return ['exito' => false, 'error' => 'El asiento no está disponible'];
        } elseif ($estado_actual === 'seleccionado') {
            return ['exito' => false, 'error' => 'El asiento ya está seleccionado por otra terminal'];
        }
    }

    if ($estado) $estado->_dato('seleccionado');
    else $nodo_asiento_encontrado->_adyacente_en(Nodo::crear_con_dato('seleccionado'), 'estado');

    $sel_por = $nodo_asiento_encontrado->adyacente('seleccionado_por');
    if ($sel_por) $sel_por->_dato($nombre_terminal);
    else $nodo_asiento_encontrado->_adyacente_en($nodo_terminal, 'seleccionado_por');

    // Obtener o crear venta actual de la terminal
    $venta_actual = $nodo_terminal->adyacente('venta_actual');
    if (!$venta_actual) {
        $venta_actual = Nodo::crear_con_dato('');
        $nodo_terminal->_adyacente_en($venta_actual, 'venta_actual');
        $venta_actual->_adyacente_en($nodo_terminal, 'terminal');
        $venta_actual->_adyacente_en(Nodo::crear_con_dato(''), 'asientos');
    }

    $viaje_venta = $venta_actual->adyacente('viaje');
    if ($viaje_venta) {
        $viaje_venta->_dato($nombre_viaje);
    } else {
        $venta_actual->_adyacente_en(Nodo::crear_con_dato($nombre_viaje), 'viaje');
    }

    $micro_venta = $venta_actual->adyacente('micro');
    if ($micro_venta) {
        if ($micro_venta->dato() !== $nombre_micro) {
            $micro_venta->_dato($nombre_micro);
            $limpiar_lista = true;
        } else {
            $limpiar_lista = false;
        }
    } else {
        $venta_actual->_adyacente_en(Nodo::crear_con_dato($nombre_micro), 'micro');
        $limpiar_lista = false;
    }

    $cabeza_venta = $venta_actual->adyacente('asientos');

    $nodos_venta = [];
    if (!$limpiar_lista && $cabeza_venta) {
        $actual_venta = $cabeza_venta->adyacente('primer');
        while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {
            $nodos_venta[] = $actual_venta;
            $actual_venta = $actual_venta->adyacente('siguiente');
        }
    } else {
        if ($cabeza_venta) {
            $cabeza_venta->eliminar_adyacente('primer');
        }
    }

    foreach ($nodos_venta as $nodo_venta) {
        $nodo_venta->eliminar_adyacente('siguiente');
    }

    $nodo_asiento_venta = Nodo::crear_con_dato('');
    $nodo_asiento_venta->_adyacente_en($nodo_asiento_encontrado, 'asiento');

    $nodos_venta[] = $nodo_asiento_venta;

    if (!empty($nodos_venta)) {
        $primer = $nodos_venta[0];
        $cabeza_venta->_adyacente_en($primer, 'primer');
        $anterior = null;
        foreach ($nodos_venta as $nodo_venta) {
            if ($anterior) {
                $anterior->_adyacente_en($nodo_venta, 'siguiente');
            }
            $anterior = $nodo_venta;
        }
        if ($anterior) {
            $anterior->_adyacente_en($cabeza_venta, 'siguiente');
        }
    } else {
        $cabeza_venta->eliminar_adyacente('primer');
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Deselecciona un asiento previamente seleccionado por la terminal.
 */
function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $nodo_asiento = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento->adyacente('estado');
    if (!$estado || $estado->dato() !== 'seleccionado') {
        return ['exito' => false, 'error' => 'El asiento no está seleccionado'];
    }

    $sel_por = $nodo_asiento->adyacente('seleccionado_por');
    if (!$sel_por || $sel_por->dato() !== $nombre_terminal) {
        return ['exito' => false, 'error' => 'No puedes deseleccionar un asiento de otra terminal'];
    }

    $estado->_dato('libre');
    $nodo_asiento->eliminar_adyacente('seleccionado_por');

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;
    if ($nodo_terminal) {
        $venta_actual = $nodo_terminal->adyacente('venta_actual');
        if ($venta_actual) {
            $cabeza_venta = $venta_actual->adyacente('asientos');
            if ($cabeza_venta) {
                $nodos_venta = [];
                $actual_venta = $cabeza_venta->adyacente('primer');
                while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {
                    $nodos_venta[] = $actual_venta;
                    $actual_venta = $actual_venta->adyacente('siguiente');
                }

                $cabeza_venta->eliminar_adyacente('primer');
                foreach ($nodos_venta as $nodo_venta) {
                    $nodo_venta->eliminar_adyacente('siguiente');
                }

                $nodos_filtrados = [];
                foreach ($nodos_venta as $nodo_venta) {
                    $asiento_ref = $nodo_venta->adyacente('asiento');
                    if ($asiento_ref && $asiento_ref->id() !== $nodo_asiento->id()) {
                        $nodos_filtrados[] = $nodo_venta;
                    }
                }

                if (!empty($nodos_filtrados)) {
                    $primer = $nodos_filtrados[0];
                    $cabeza_venta->_adyacente_en($primer, 'primer');
                    $anterior = null;
                    foreach ($nodos_filtrados as $nodo_venta) {
                        if ($anterior) {
                            $anterior->_adyacente_en($nodo_venta, 'siguiente');
                        }
                        $anterior = $nodo_venta;
                    }
                    if ($anterior) {
                        $anterior->_adyacente_en($cabeza_venta, 'siguiente');
                    }
                }
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}