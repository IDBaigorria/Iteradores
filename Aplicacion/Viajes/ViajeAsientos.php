<?php
/**
 * Funciones de gestión de asientos dentro de micros de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.41
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
include_once("./Aplicacion/Ventas/Venta.php");
include_once("./Aplicacion/Pasajeros/Pasajero.php");

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
 * Helper privado: verifica si un DNI ya está asignado a algún asiento del viaje.
 *
 * Recorre todos los micros del viaje y todos sus asientos. Devuelve true si
 * encuentra un asiento con un enlace `pasajero` cuyo DNI (normalizado) coincide
 * con el DNI buscado. Se usa para impedir asignar el mismo pasajero a dos
 * asientos del mismo viaje.
 *
 * La comparación se hace con normalizar_dni para tolerar DNIs históricos con
 * puntos y DNIs nuevos sin puntos.
 *
 * @param string $nombre_dueno
 * @param string $nombre_viaje
 * @param string $dni
 * @return bool
 */
function _dni_asignado_en_viaje(string $nombre_dueno, string $nombre_viaje, string $dni): bool {
    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') return false;

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return false;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return false;

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return false;

    $adyacentes_micros = (array) $nodo_micros->adyacentes();
    foreach ($adyacentes_micros as $nodo_micro) {
        $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
        if (!$nodo_copia) continue;
        $nodo_asientos = $nodo_copia->adyacente('asientos');
        if (!$nodo_asientos) continue;

        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            $seg = 0;
            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {
                $nodo_pasajero = $actual->adyacente('pasajero');
                if ($nodo_pasajero && normalizar_dni($nodo_pasajero->dato()) === $dni_norm) {
                    return true;
                }
                $actual = $actual->adyacente('siguiente');
                $seg++;
            }
        }
    }

    return false;
}

/**
 * Reserva un asiento para el equipo (dueño).
 *
 * A partir de v1.5piloto.38 puede recibir un array opcional $datos_pasajero
 * con los datos del pasajero para asignarlo en el mismo paso. Si viene vacío,
 * se comporta como antes (solo reserva, sin pasajero).
 *
 * @param string $nombre_viaje
 * @param string $nombre_micro
 * @param string $fila
 * @param string $columna
 * @param string $nombre_dueno
 * @param array  $datos_pasajero Datos opcionales del pasajero (mismo formato que en venta).
 * @return array
 */
function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero = []): array {
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
    if ($estado && $estado->dato() !== 'libre') {
        return ['exito' => false, 'error' => 'El asiento no está libre para reservar'];
    }

    // === Validación y creación del pasajero (si viene) ===
    $hay_pasajero = false;
    foreach (['dni', 'apellido', 'nombres'] as $campo_req) {
        if (isset($datos_pasajero[$campo_req]) && trim((string)$datos_pasajero[$campo_req]) !== '') {
            $hay_pasajero = true;
            break;
        }
    }

    if ($hay_pasajero) {
        $err = validar_dni($datos_pasajero['dni'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => $err];
        $err = validar_nombre_o_apellido($datos_pasajero['apellido'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Apellido: ' . $err];
        $err = validar_nombre_o_apellido($datos_pasajero['nombres'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Nombres: ' . $err];
        $err = validar_email($datos_pasajero['email'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => $err];
        $err = validar_telefono($datos_pasajero['celular'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Celular: ' . $err];
        $err = validar_telefono($datos_pasajero['celular_emergencia'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Celular de emergencia: ' . $err];
        $err = validar_fecha_nacimiento($datos_pasajero['fecha_nacimiento'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => $err];
        $err = validar_localidad($datos_pasajero['localidad'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Localidad: ' . $err];
        $err = validar_direccion($datos_pasajero['direccion'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => 'Dirección: ' . $err];

        if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero['dni'])) {
            return ['exito' => false, 'error' => 'El DNI ya está asignado a otro asiento de este viaje'];
        }
    }

    // === Reservar ===
    if ($estado) $estado->_dato('reservado');
    else $nodo_asiento->_adyacente_en(Nodo::crear_con_dato('reservado'), 'estado');

    $nodo_asiento->eliminar_adyacente('seleccionado_por');

    $reservado_por = $nodo_asiento->adyacente('reservado_por');
    if ($reservado_por) $reservado_por->_dato($nombre_dueno);
    else $nodo_asiento->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'reservado_por');

    // === Enlazar pasajero si corresponde ===
    if ($hay_pasajero) {
        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $datos_pasajero['dni'], $datos_pasajero);
        if (!$nodo_pasajero) {
            return ['exito' => false, 'error' => 'No se pudo crear el pasajero'];
        }

        $nodo_asiento->eliminar_adyacente('pasajero');
        $nodo_asiento->_adyacente_en($nodo_pasajero, 'pasajero');

        // Guardar ficha de salud solo si el viaje la muestra
        $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
        if (($opciones['mostrar_ficha_medica'] ?? '0') === '1'
            && isset($datos_pasajero['salud']) && is_array($datos_pasajero['salud'])) {
            guardar_ficha_salud($nombre_dueno, $datos_pasajero['dni'], $datos_pasajero['salud']);
        }
    }

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Asigna un pasajero a un asiento que ya está reservado y no tiene pasajero.
 *
 * A partir de v1.5piloto.38. No cambia el estado (sigue reservado). Enlaza el
 * nodo del pasajero (creado o reutilizado) en el enlace `pasajero` del asiento.
 *
 * @param string $nombre_viaje
 * @param string $nombre_micro
 * @param string $fila
 * @param string $columna
 * @param string $nombre_dueno
 * @param array  $datos_pasajero
 * @return array
 */
function asignar_pasajero_a_reserva(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero): array {
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

    if ($nodo_asiento->adyacente('pasajero')) {
        return ['exito' => false, 'error' => 'El asiento ya tiene un pasajero asignado'];
    }

    // === Validaciones ===
    $err = validar_dni($datos_pasajero['dni'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => $err];
    $err = validar_nombre_o_apellido($datos_pasajero['apellido'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Apellido: ' . $err];
    $err = validar_nombre_o_apellido($datos_pasajero['nombres'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Nombres: ' . $err];
    $err = validar_email($datos_pasajero['email'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => $err];
    $err = validar_telefono($datos_pasajero['celular'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Celular: ' . $err];
    $err = validar_telefono($datos_pasajero['celular_emergencia'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Celular de emergencia: ' . $err];
    $err = validar_fecha_nacimiento($datos_pasajero['fecha_nacimiento'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => $err];
    $err = validar_localidad($datos_pasajero['localidad'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Localidad: ' . $err];
    $err = validar_direccion($datos_pasajero['direccion'] ?? '');
    if ($err !== null) return ['exito' => false, 'error' => 'Dirección: ' . $err];

    if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero['dni'])) {
        return ['exito' => false, 'error' => 'El DNI ya está asignado a otro asiento de este viaje'];
    }

    // === Crear/reutilizar pasajero y enlazar ===
    $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $datos_pasajero['dni'], $datos_pasajero);
    if (!$nodo_pasajero) {
        return ['exito' => false, 'error' => 'No se pudo crear el pasajero'];
    }

    $nodo_asiento->_adyacente_en($nodo_pasajero, 'pasajero');

    // Guardar ficha de salud solo si el viaje la muestra
    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
    if (($opciones['mostrar_ficha_medica'] ?? '0') === '1'
        && isset($datos_pasajero['salud']) && is_array($datos_pasajero['salud'])) {
        guardar_ficha_salud($nombre_dueno, $datos_pasajero['dni'], $datos_pasajero['salud']);
    }

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Libera la reserva de un asiento.
 *
 * A partir de v1.5piloto.38 también elimina el enlace `pasajero` si existía,
 * dejando el asiento limpio para un futuro uso.
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
    // A partir de v1.5piloto.38: al liberar la reserva se limpia también el pasajero.
    $nodo_asiento->eliminar_adyacente('pasajero');

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Obtiene el estado resumido de todos los asientos de un micro.
 *
 * A partir de v1.5piloto.38, cada asiento incluye también:
 *  - tiene_pasajero: bool
 *  - pasajero: objeto con datos básicos o null
 *  - venta_id: id de la venta si el asiento está vendido, o null
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
                $reservado_por = $actual->adyacente('reservado_por');
                $nodo_pasajero = $actual->adyacente('pasajero');
                $nodo_venta = $actual->adyacente('venta');

                // Nombre real del terminal que seleccionó el asiento, con
                // fallback al nombre de usuario. La comparación en el
                // frontend sigue usando `seleccionado_por` (nombre de usuario)
                // para identificar al propio usuario; este campo es solo
                // para mostrar.
                $seleccionado_por_usuario = $seleccionado_por ? $seleccionado_por->dato() : null;
                $seleccionado_por_real = $seleccionado_por_usuario;
                if ($seleccionado_por_usuario !== null) {
                    $raiz_usuarios_sel = Nodo::nodo_por_id('usuarios');
                    if ($raiz_usuarios_sel) {
                        $nodo_sel_usuario = $raiz_usuarios_sel->adyacente($seleccionado_por_usuario);
                        if ($nodo_sel_usuario && $nodo_sel_usuario->adyacente('nombre_real')) {
                            $seleccionado_por_real = $nodo_sel_usuario->adyacente('nombre_real')->dato();
                        }
                    }
                }

                $asiento_info = [
                    'fila' => $fila ? $fila->dato() : '',
                    'columna' => $columna ? $columna->dato() : '',
                    'numero' => $actual->dato(),
                    'estado' => $estado ? $estado->dato() : 'libre',
                    'seleccionado_por' => $seleccionado_por_usuario,
                    'seleccionado_por_nombre_real' => $seleccionado_por_real,
                    'reservado_por' => $reservado_por ? $reservado_por->dato() : null,
                    'tiene_pasajero' => false,
                    'pasajero' => null,
                    'venta_id' => null,
                    'venta_terminal' => null,
                    'venta_terminal_nombre_real' => null,
                ];

                if ($nodo_pasajero) {
                    $p_dni = $nodo_pasajero->dato();
                    $p_apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';
                    $p_nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';

                    $asiento_info['tiene_pasajero'] = true;
                    $asiento_info['pasajero'] = [
                        'dni' => $p_dni,
                        'dni_visible' => normalizar_dni($p_dni),
                        'apellido' => $p_apellido,
                        'nombres' => $p_nombres,
                        'nombre_completo' => formatear_nombre_completo($p_apellido, $p_nombres),
                        'email' => $nodo_pasajero->adyacente('email') ? $nodo_pasajero->adyacente('email')->dato() : '',
                        'celular' => $nodo_pasajero->adyacente('celular') ? $nodo_pasajero->adyacente('celular')->dato() : '',
                        'celular_emergencia' => $nodo_pasajero->adyacente('celular_emergencia') ? $nodo_pasajero->adyacente('celular_emergencia')->dato() : '',
                        'fecha_nacimiento' => $nodo_pasajero->adyacente('fecha_nacimiento') ? $nodo_pasajero->adyacente('fecha_nacimiento')->dato() : '',
                        'fecha_nacimiento_visible' => formatear_fecha_visible($nodo_pasajero->adyacente('fecha_nacimiento') ? $nodo_pasajero->adyacente('fecha_nacimiento')->dato() : ''),
                        'direccion' => $nodo_pasajero->adyacente('direccion') ? $nodo_pasajero->adyacente('direccion')->dato() : '',
                        'localidad' => $nodo_pasajero->adyacente('localidad') ? $nodo_pasajero->adyacente('localidad')->dato() : '',
                    ];
                }

                if ($nodo_venta) {
                    $asiento_info['venta_id'] = $nodo_venta->dato();
                    $nodo_venta_terminal = $nodo_venta->adyacente('terminal');
                    $venta_terminal_usuario = $nodo_venta_terminal ? $nodo_venta_terminal->dato() : null;
                    $asiento_info['venta_terminal'] = $venta_terminal_usuario;
                    // Nombre visible de la terminal que hizo la venta, con
                    // fallback al nombre de usuario. La comparación para saber
                    // si la venta es del usuario actual sigue siendo contra
                    // `venta_terminal` (nombre de usuario).
                    $venta_terminal_real = $venta_terminal_usuario;
                    if ($nodo_venta_terminal && $nodo_venta_terminal->adyacente('nombre_real')) {
                        $venta_terminal_real = $nodo_venta_terminal->adyacente('nombre_real')->dato();
                    }
                    $asiento_info['venta_terminal_nombre_real'] = $venta_terminal_real;
                }

                $asientos_estados[] = $asiento_info;
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

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

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

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}