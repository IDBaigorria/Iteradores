<?php
/**
 * Gestión de ventas persistentes.
 * Utiliza árbol (hmi/hd) para almacenar las ventas.
 *
 * @package   Iteradores
 * @since     1.5piloto.14
 * @version   1.5piloto.46c
 */


use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");

/**
 * Obtiene el contenedor de ventas de un dueño (raíz del árbol de ventas), creándolo si no existe.
 */
function obtener_contenedor_ventas_dueno(string $nombre_dueno) {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return null;

    $nodo_ventas = $nodo_dueno->adyacente('ventas');
    if (!$nodo_ventas) {
        $nodo_ventas = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_ventas, 'ventas');
    }
    return $nodo_ventas;
}

/**
 * Crea un nodo pasajero si no existe, basado en DNI.
 */
function obtener_o_crear_pasajero(string $nombre_dueno, string $dni, array $datos_pasajero): ?Nodo {
    $dni = trim($dni);
    if (empty($dni)) return null;

    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return null;

    $nodo_pasajero = $contenedor->adyacente($dni);
    if (!$nodo_pasajero) {
        $nodo_pasajero = Nodo::crear_con_dato($dni);
        $contenedor->_adyacente_en($nodo_pasajero, $dni);
    }

    // Actualizar datos si se proporcionan
    $campos = ['nombres', 'apellido', 'email', 'celular', 'celular_emergencia', 'fecha_nacimiento', 'localidad', 'direccion'];    
    foreach ($campos as $campo) {
        if (isset($datos_pasajero[$campo]) && $datos_pasajero[$campo] !== '') {
            $nodo_campo = $nodo_pasajero->adyacente($campo);
            if ($nodo_campo) $nodo_campo->_dato($datos_pasajero[$campo]);
            else $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($datos_pasajero[$campo]), $campo);
        }
    }

    return $nodo_pasajero;
}

/**
 * Confirma la venta actual de una terminal, creando una venta persistente.
 *
 * @param string $nombre_terminal Nombre de la terminal.
 * @param string $metodo_pago 'efectivo' o 'transferencia'.
 * @param int    $cuotas Número de cuotas (1-3, solo efectivo).
 * @param float  $monto_pagado Monto abonado en el momento de la venta.
 * @param string $comprador_dni DNI del comprador.
 * @param string $comprador_apellido Apellido del comprador.
 * @param string $comprador_nombres Nombres del comprador.
 * @param string $comprador_email Email del comprador (opcional).
 * @param string $comprador_celular Celular del comprador.
 * @param array  $pasajeros_por_asiento Array de pasajeros por asiento.
 * @param string|null $fecha_hora Fecha y hora en formato "DD/MM/YYYY HH:MM". Si es null se usará la fecha/hora actual del servidor.
 * @param string|null $fecha_pago Fecha y hora del último pago en mismo formato. Si es null se usará $fecha_hora.
 * @return array Resultado con éxito o error.
 */
function confirmar_venta_actual(
    string $nombre_terminal,
    string $metodo_pago,
    int $cuotas,
    float $monto_pagado,
    string $comprador_dni,
    string $comprador_apellido,
    string $comprador_nombres,
    string $comprador_email,
    string $comprador_celular,
    array $pasajeros_por_asiento,
    ?string $fecha_hora = null,
    ?string $fecha_pago = null
): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);
    if (!$nodo_terminal) return ['exito' => false, 'error' => 'Terminal no encontrada'];

    $venta_actual = $nodo_terminal->adyacente('venta_actual');
    if (!$venta_actual) return ['exito' => false, 'error' => 'No hay venta actual'];

    $nodo_nombre_micro = $venta_actual->adyacente('micro');
    $nodo_nombre_viaje = $venta_actual->adyacente('viaje');
    if (!$nodo_nombre_micro || !$nodo_nombre_viaje) {
        return ['exito' => false, 'error' => 'Venta actual incompleta'];
    }

    $nombre_micro = $nodo_nombre_micro->dato();
    $nombre_viaje = $nodo_nombre_viaje->dato();

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'La terminal no tiene dueño asignado'];
    $nombre_dueno = $nodo_dueno->dato();

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'No se encontraron viajes del dueño'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros en el viaje'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    // Obtener opciones avanzadas del viaje
    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
    $restriccion_edad = $opciones['restriccion_edad'] === '1';
    $edad_min = (int)$opciones['edad_minima'];
    $edad_max = (int)$opciones['edad_maxima'];

    // Resolver configuración de pago: override del TerminalViaje > viaje > defaults.
    $opciones_viaje_pago = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
    $opciones_terminal_pago = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);

    $resolver = function(string $campo, string $default) use ($opciones_terminal_pago, $opciones_viaje_pago) {
        // Override del terminal: si está seteado (no vacío), gana.
        if (isset($opciones_terminal_pago[$campo]) && trim((string)$opciones_terminal_pago[$campo]) !== '') {
            return (string)$opciones_terminal_pago[$campo];
        }
        if (isset($opciones_viaje_pago[$campo]) && trim((string)$opciones_viaje_pago[$campo]) !== '') {
            return (string)$opciones_viaje_pago[$campo];
        }
        return $default;
    };

    $permite_efectivo = $resolver('permite_efectivo', '1');
    $cuotas_efectivo_max = (int)$resolver('cuotas_efectivo_max', '3');
    $permite_transferencia = $resolver('permite_transferencia', '1');
    $cuotas_transferencia_max = (int)$resolver('cuotas_transferencia_max', '1');

    // === Punto de subida/bajada ===
    // Solo aplica si la terminal tiene configurada la opción de cambiar el
    // punto de subida/bajada predeterminado, y hay un origen definido.
    $cambiar_punto = $opciones_terminal_pago['cambiar_punto_predeterminado'] ?? '0';
    $punto_subida_bajada_terminal = trim((string)($opciones_terminal_pago['punto_subida_bajada'] ?? ''));
    $selector_subida_bajada_activo = ($cambiar_punto === '1' && $punto_subida_bajada_terminal !== '');

    $origen_viaje = $nodo_viaje->adyacente('origen') ? trim($nodo_viaje->adyacente('origen')->dato()) : '';
    if ($origen_viaje === '') {
        // Sin origen no se puede ofrecer la opción. No es un error:
        // simplemente no se muestra el selector.
        $selector_subida_bajada_activo = false;
    }

    // Pre-indexar las paradas del viaje para resolver la hora estimada
    // de la parada elegida por cada pasajero.
    $paradas_viaje_por_nombre = [];  // nombre => hora_estimada (string)
    if ($selector_subida_bajada_activo) {
        $nodo_paradas = $nodo_viaje->adyacente('paradas_intermedias');
        if ($nodo_paradas) {
            $actual_parada = hmi($nodo_paradas);
            $seg = 0;
            while ($actual_parada && $seg < 100) {
                $nodo_hora = $actual_parada->adyacente('hora_estimada');
                $paradas_viaje_por_nombre[$actual_parada->dato()] = $nodo_hora ? $nodo_hora->dato() : '';
                $actual_parada = hd($actual_parada);
                $seg++;
            }
        }
    }

    // Validar método de pago y cuotas
    $metodo_pago = strtolower($metodo_pago);
    if (!in_array($metodo_pago, ['efectivo', 'transferencia'])) {
        return ['exito' => false, 'error' => 'Método de pago inválido'];
    }
    if ($metodo_pago === 'efectivo') {
        if ($permite_efectivo !== '1') {
            return ['exito' => false, 'error' => 'El pago en efectivo no está permitido'];
        }
        if ($cuotas < 1 || $cuotas > $cuotas_efectivo_max) {
            return ['exito' => false, 'error' => "Cantidad de cuotas inválida (1-$cuotas_efectivo_max)"];
        }
    } else { // transferencia
        if ($permite_transferencia !== '1') {
            return ['exito' => false, 'error' => 'La transferencia no está permitida'];
        }
        if ($cuotas < 1 || $cuotas > $cuotas_transferencia_max) {
            return ['exito' => false, 'error' => "Cantidad de cuotas inválida (1-$cuotas_transferencia_max)"];
        }
    }

    // === Validaciones de campos del comprador ===
    $err = validar_dni($comprador_dni);
    if ($err !== null) return ['exito' => false, 'error' => 'Comprador - ' . $err];
    $err = validar_nombre_o_apellido($comprador_apellido);
    if ($err !== null) return ['exito' => false, 'error' => 'Comprador - Apellido: ' . $err];
    $err = validar_nombre_o_apellido($comprador_nombres);
    if ($err !== null) return ['exito' => false, 'error' => 'Comprador - Nombres: ' . $err];
    $err = validar_email($comprador_email);
    if ($err !== null) return ['exito' => false, 'error' => 'Comprador - ' . $err];
    $err = validar_telefono($comprador_celular);
    if ($err !== null) return ['exito' => false, 'error' => 'Comprador - Celular: ' . $err];

    // Recorrer lista circular de asientos-en-venta de la venta actual
    $cabeza_venta_actual = $venta_actual->adyacente('asientos');
    if (!$cabeza_venta_actual) return ['exito' => false, 'error' => 'Venta actual sin asientos'];

    $asientos_seleccionados = [];
    $actual_venta = $cabeza_venta_actual->adyacente('primer');
    $contador_seguridad = 0;
    while ($actual_venta && $actual_venta->id() !== $cabeza_venta_actual->id() && $contador_seguridad < 100) {
        $asiento_real = $actual_venta->adyacente('asiento');
        if ($asiento_real) {
            $asientos_seleccionados[] = $asiento_real;
        }
        $actual_venta = $actual_venta->adyacente('siguiente');
        $contador_seguridad++;
    }

    if (empty($asientos_seleccionados)) {
        return ['exito' => false, 'error' => 'No hay asientos seleccionados'];
    }

    $nodo_monto = $nodo_micro->adyacente('monto');
    $monto_por_asiento = $nodo_monto ? (float)$nodo_monto->dato() : 0;
    $total = $monto_por_asiento * count($asientos_seleccionados);

    // Validar monto pagado
    if ($monto_pagado <= 0) {
        return ['exito' => false, 'error' => 'El monto a pagar debe ser mayor que cero'];
    }
    if ($monto_pagado > $total) {
        return ['exito' => false, 'error' => 'El monto a pagar no puede superar el total'];
    }

    // Calcular cuotas restantes
    // Regla general: si el monto pagado cubre el total, no quedan cuotas.
    // Si no lo cubre, quedan cuotas - 1.
    $cuotas_restantes = 0;
    if ($monto_pagado >= $total) {
        $cuotas_restantes = 0;
        // Ajustar el monto pagado al total (no se puede cobrar más de lo que vale)
        $monto_pagado = $total;
    } else {
        $cuotas_restantes = max(0, $cuotas - 1);
    }

    // Crear nodo venta persistente
    $id_venta = 'venta_' . time();
    $nodo_venta = Nodo::crear_con_dato($id_venta);
    $nodo_venta->_adyacente_en($nodo_terminal, 'terminal');
    $nodo_venta->_adyacente_en($nodo_viaje, 'viaje');
    $nodo_venta->_adyacente_en($nodo_micro, 'micro');

    // Usar fecha enviada desde frontend si está disponible; si no, usar fecha actual del servidor
    if ($fecha_hora === null || trim($fecha_hora) === '') {
        $fecha_hora = date('d/m/Y H:i');
    }
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato($fecha_hora), 'fecha_hora');

    // Fecha de último pago: si no se envía, se usa la misma que fecha_hora
    if ($fecha_pago === null || trim($fecha_pago) === '') {
        $fecha_pago = $fecha_hora;
    }
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato($fecha_pago), 'fecha_ultimo_pago');

    $nodo_venta->_adyacente_en(Nodo::crear_con_dato($metodo_pago), 'metodo_pago');
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)$total), 'total');
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)$cuotas), 'cuotas');
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)$monto_pagado), 'pagado');
    $nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)$cuotas_restantes), 'cuotas_restantes');

    // Obtener o crear comprador
    $comprador_datos = [
        'apellido' => $comprador_apellido,
        'nombres' => $comprador_nombres,
        'email' => $comprador_email,
        'celular' => $comprador_celular,
    ];
    if (!empty($comprador_dni)) {
        $nodo_comprador = obtener_o_crear_pasajero($nombre_dueno, $comprador_dni, $comprador_datos);
        if ($nodo_comprador) $nodo_venta->_adyacente_en($nodo_comprador, 'comprador');
    }

    // Crear lista enlazada de asientos-en-venta persistente (no circular)
    $cabeza_asientos_venta = Nodo::crear_con_dato('');
    $nodo_venta->_adyacente_en($cabeza_asientos_venta, 'asientos');
    $primer_asiento_venta = null;
    $anterior_asiento_venta = null;

    $indice_asiento = 0;
    foreach ($asientos_seleccionados as $asiento_real) {
        $datos_pasajero = $pasajeros_por_asiento[$indice_asiento] ?? [];
        $dni_pasajero = $datos_pasajero['dni'] ?? '';
        $fecha_nacimiento_pasajero = $datos_pasajero['fecha_nacimiento'] ?? '';

        $num_pas = $indice_asiento + 1;

        $err = validar_dni($dni_pasajero);
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - $err"];
        $err = validar_nombre_o_apellido($datos_pasajero['apellido'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Apellido: $err"];
        $err = validar_nombre_o_apellido($datos_pasajero['nombres'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Nombres: $err"];
        $err = validar_email($datos_pasajero['email'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - $err"];
        $err = validar_telefono($datos_pasajero['celular'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Celular: $err"];
        $err = validar_telefono($datos_pasajero['celular_emergencia'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Celular de emergencia: $err"];
        $err = validar_fecha_nacimiento($fecha_nacimiento_pasajero);
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - $err"];
        $err = validar_localidad($datos_pasajero['localidad'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Localidad: $err"];
        $err = validar_direccion($datos_pasajero['direccion'] ?? '');
        if ($err !== null) return ['exito' => false, 'error' => "Pasajero $num_pas - Dirección: $err"];

        if ($restriccion_edad) {
            $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento_pasajero);
            if (!$fecha_nac) {
                return ['exito' => false, 'error' => 'Fecha de nacimiento inválida del pasajero ' . ($indice_asiento + 1)];
            }
            $hoy = new DateTime();
            $edad = $hoy->diff($fecha_nac)->y;
            if ($edad < $edad_min || $edad > $edad_max) {
                return ['exito' => false, 'error' => "El pasajero " . ($indice_asiento + 1) . " no cumple con la restricción de edad ($edad_min-$edad_max años)"];
            }
        }

        // Punto de subida/bajada: validar y preparar los valores a guardar.
        // $hora_elegida: null = sin hora; string = hora estimada de la parada.
        $punto_elegido = null;
        $hora_elegida = null;
        if ($selector_subida_bajada_activo) {
            $punto_elegido = trim((string)($datos_pasajero['punto_subida_bajada'] ?? ''));
            $opciones_validas = [$punto_subida_bajada_terminal, $origen_viaje];
            if (!in_array($punto_elegido, $opciones_validas, true)) {
                return [
                    'exito' => false,
                    'error' => 'Punto de subida/bajada inválido para el pasajero ' . ($indice_asiento + 1)
                ];
            }
            // Si eligió la parada (y no el origen), tomar su hora estimada.
            if ($punto_elegido === $punto_subida_bajada_terminal && $punto_elegido !== $origen_viaje) {
                $hora_elegida = $paradas_viaje_por_nombre[$punto_elegido] ?? '';
            }
        }

        if (empty($datos_pasajero['localidad']) || empty($datos_pasajero['direccion'])) {
            return ['exito' => false, 'error' => 'Faltan datos obligatorios del pasajero ' . ($indice_asiento + 1) . ': localidad y dirección'];
        }

        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $dni_pasajero, $datos_pasajero);
        if (!$nodo_pasajero) {
            return ['exito' => false, 'error' => 'No se pudo crear el pasajero para el asiento ' . ($indice_asiento + 1)];
        }
        // Guardar ficha de salud si viene
        if (isset($datos_pasajero['salud']) && is_array($datos_pasajero['salud'])) {
            guardar_ficha_salud($nombre_dueno, $dni_pasajero, $datos_pasajero['salud']);
        }

        // Cambiar estado del asiento real a vendido
        $estado = $asiento_real->adyacente('estado');
        if ($estado) $estado->_dato('vendido');
        else $asiento_real->_adyacente_en(Nodo::crear_con_dato('vendido'), 'estado');

        $asiento_real->eliminar_adyacente('seleccionado_por');
        $asiento_real->eliminar_adyacente('pasajero');
        $asiento_real->_adyacente_en($nodo_pasajero, 'pasajero');
        $asiento_real->eliminar_adyacente('venta');
        $asiento_real->_adyacente_en($nodo_venta, 'venta');

        // Crear nodo asiento-en-venta y enlazar en lista simple
        $nodo_asiento_venta = Nodo::crear_con_dato('');
        $nodo_asiento_venta->_adyacente_en($asiento_real, 'asiento');
        $nodo_asiento_venta->_adyacente_en($nodo_pasajero, 'pasajero');
        if ($punto_elegido !== null) {
            $nodo_asiento_venta->_adyacente_en(Nodo::crear_con_dato($punto_elegido), 'punto_subida_bajada');
            if ($hora_elegida !== null && $hora_elegida !== '') {
                $nodo_asiento_venta->_adyacente_en(Nodo::crear_con_dato($hora_elegida), 'hora_subida_bajada');
            }
        }

        if ($anterior_asiento_venta) {
            $anterior_asiento_venta->_adyacente_en($nodo_asiento_venta, 'siguiente');
        } else {
            $primer_asiento_venta = $nodo_asiento_venta;
        }
        $anterior_asiento_venta = $nodo_asiento_venta;

        $indice_asiento++;
    }

    if ($primer_asiento_venta) {
        $cabeza_asientos_venta->_adyacente_en($primer_asiento_venta, 'primer');
    }

    // Crear la lista de cupones. El contenedor `cupones` es el padre,
    // y cada cupón es un hijo enlazado con la estructura de árbol
    // (hmi/hd/p) de miscelaneas/Arbol.php. El cupón 1 refleja el
    // monto real abonado al momento de la venta; los cupones
    // pendientes reparten el saldo restante.
    _crear_lista_cupones_venta($nodo_venta, $cuotas, (string)$total, (string)$monto_pagado, $fecha_pago);

    // Actualizar contadores
    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    // Actualizar montos de terminal
    if ($metodo_pago === 'efectivo') {
        $nodo_efectivo = $nodo_terminal->adyacente('efectivo');
        if (!$nodo_efectivo) {
            $nodo_efectivo = Nodo::crear_con_dato('0');
            $nodo_terminal->_adyacente_en($nodo_efectivo, 'efectivo');
        }
        $nuevo_efectivo = (float)$nodo_efectivo->dato() + $monto_pagado;
        $nodo_efectivo->_dato((string)$nuevo_efectivo);
    } else {
        $nodo_banco = $nodo_terminal->adyacente('banco');
        if (!$nodo_banco) return ['exito' => false, 'error' => 'La terminal no tiene datos bancarios'];
        $monto_banco_actual = (float)$nodo_banco->dato();
        $nodo_banco->_dato((string)($monto_banco_actual + $monto_pagado));
    }

    // Insertar venta en el árbol de ventas del dueño usando _hmi
    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor_ventas) {
        return ['exito' => false, 'error' => 'No se pudo obtener contenedor de ventas'];
    }
    _hmi($contenedor_ventas, $nodo_venta);

    // Eliminar venta actual de la terminal
    $nodo_terminal->eliminar_adyacente('venta_actual');

    Controlador::guardar(Conf::NOMBRE_APP);

    return [
        'exito' => true,
        'id_venta' => $id_venta,
        'total' => (string)$total,
        'pagado' => (string)$monto_pagado,
        'cuotas_restantes' => (string)$cuotas_restantes,
        'asientos' => count($asientos_seleccionados),
    ];
}

/**
 * Lista ventas de un dueño recorriendo el árbol de ventas.
 */
function listar_ventas_por_dueno(string $nombre_dueno): array {
    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor) return [];

    $ventas = [];
    $actual = hmi($contenedor);
    while ($actual) {
        $ventas[] = formatear_venta_resumida($actual);
        $actual = hd($actual);
    }
    return $ventas;
}

/**
 * Lista ventas de una terminal (filtra las del dueño).
 */
function listar_ventas_por_terminal(string $nombre_terminal): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);
    if (!$nodo_terminal) return [];

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno) return [];

    $nombre_dueno = $nodo_dueno->dato();
    $ventas_dueno = listar_ventas_por_dueno($nombre_dueno);
    $ventas_terminal = array_filter($ventas_dueno, function($venta) use ($nombre_terminal) {
        return $venta['terminal'] === $nombre_terminal;
    });

    return array_values($ventas_terminal);
}

/**
 * Devuelve un resumen formateado de una venta.
 */
function formatear_venta_resumida(Nodo $nodo_venta): array {
    $id_venta = $nodo_venta->dato();
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    $nodo_micro = $nodo_venta->adyacente('micro');
    $nodo_total = $nodo_venta->adyacente('total');
    $nodo_fecha = $nodo_venta->adyacente('fecha_hora');

    // Terminal vendedora: nombre de usuario (identificador) y nombre real.
    $nombre_terminal = $nodo_terminal ? $nodo_terminal->dato() : '';
    $nombre_terminal_real = $nombre_terminal;
    if ($nodo_terminal && $nodo_terminal->adyacente('nombre_real')) {
        $nombre_terminal_real = $nodo_terminal->adyacente('nombre_real')->dato();
    }

    // Viaje: guardamos el identificador y también el nombre visible.
    $viaje_id = $nodo_viaje ? $nodo_viaje->dato() : '';
    $viaje_visible = $viaje_id;
    if ($nodo_viaje && $nodo_viaje->adyacente('nombre')) {
        $viaje_visible = $nodo_viaje->adyacente('nombre')->dato();
    }

    // Micro: el dato del Nodo Micro es vacío por diseño. El nombre visible
    // sale de `vehiculo_copia->nombre`. Se mantiene `micro` (identificador,
    // que viene siendo el nombre del enlace) por compatibilidad con otros
    // flujos que puedan necesitarlo.
    $nombre_micro = '';
    $micro_nombre_visible = '';
    if ($nodo_micro) {
        $nombre_micro = $nodo_micro->dato();
        $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
        if ($nodo_copia && $nodo_copia->adyacente('nombre')) {
            $micro_nombre_visible = $nodo_copia->adyacente('nombre')->dato();
        }
    }

    $total = $nodo_total ? $nodo_total->dato() : '0';
    $pagado = $nodo_venta->adyacente('pagado') ? $nodo_venta->adyacente('pagado')->dato() : '0';
    $pendiente = number_format(max(0, (float)$total - (float)$pagado), 2, '.', '');

    $metodo_pago = $nodo_venta->adyacente('metodo_pago') ? $nodo_venta->adyacente('metodo_pago')->dato() : '';
    $cuotas = $nodo_venta->adyacente('cuotas') ? $nodo_venta->adyacente('cuotas')->dato() : '1';
    $nodo_cuotas_restantes = $nodo_venta->adyacente('cuotas_restantes');
    $cuotas_restantes = $nodo_cuotas_restantes ? $nodo_cuotas_restantes->dato() : '0';
    $estado_pago = ((int)$cuotas_restantes > 0) ? 'cuotas_pendientes' : 'pagado';

    $fecha = '';
    if ($nodo_fecha) {
        $valor_fecha = $nodo_fecha->dato();
        if (is_numeric($valor_fecha)) {
            $fecha = date('d/m/Y H:i', (int)$valor_fecha);
        } else {
            $fecha = $valor_fecha;
        }
    }

    $cantidad_asientos = 0;
    $cabeza_asientos = $nodo_venta->adyacente('asientos');
    if ($cabeza_asientos) {
        $actual = $cabeza_asientos->adyacente('primer');
        $seguridad = 0;
        while ($actual && $seguridad < 100) {
            $cantidad_asientos++;
            $actual = $actual->adyacente('siguiente');
            $seguridad++;
        }
    }

    // Comprador: se arma un resumen con los campos que necesita la tarjeta
    // del panel Vendidos. La versión completa (formatear_venta_completa)
    // vuelve a sobrescribir este campo con datos más extensos.
    $comprador = null;
    $nodo_comprador = $nodo_venta->adyacente('comprador');
    if ($nodo_comprador) {
        $comp_apellido = $nodo_comprador->adyacente('apellido') ? $nodo_comprador->adyacente('apellido')->dato() : '';
        $comp_nombres = $nodo_comprador->adyacente('nombres') ? $nodo_comprador->adyacente('nombres')->dato() : '';
        $comprador = [
            'dni' => $nodo_comprador->dato(),
            'dni_visible' => normalizar_dni($nodo_comprador->dato()),
            'apellido' => $comp_apellido,
            'nombres' => $comp_nombres,
            'nombre_completo' => formatear_nombre_completo($comp_apellido, $comp_nombres),
            'email' => $nodo_comprador->adyacente('email') ? $nodo_comprador->adyacente('email')->dato() : '',
            'celular' => $nodo_comprador->adyacente('celular') ? $nodo_comprador->adyacente('celular')->dato() : '',
            'direccion' => $nodo_comprador->adyacente('direccion') ? $nodo_comprador->adyacente('direccion')->dato() : '',
            'localidad' => $nodo_comprador->adyacente('localidad') ? $nodo_comprador->adyacente('localidad')->dato() : '',
        ];
    }

    return [
        'id_venta' => $id_venta,
        'terminal' => $nombre_terminal,
        'terminal_nombre_real' => $nombre_terminal_real,
        'viaje' => $viaje_id,
        'viaje_visible' => $viaje_visible,
        'micro' => $nombre_micro,
        'micro_nombre_visible' => $micro_nombre_visible,
        'total' => $total,
        'pagado' => $pagado,
        'pendiente' => $pendiente,
        'metodo_pago' => $metodo_pago,
        'cuotas' => $cuotas,
        'cuotas_restantes' => $cuotas_restantes,
        'estado_pago' => $estado_pago,
        'fecha' => $fecha,
        'cantidad_asientos' => $cantidad_asientos,
        'comprador' => $comprador,
    ];
}

/**
 * Obtiene el detalle completo de una venta por su ID.
 */
function obtener_venta_por_id(string $id_venta): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_nivel = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

        $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);
        if (!$contenedor) continue;

        $actual = hmi($contenedor);
        while ($actual) {
            if ($actual->dato() === $id_venta) {
                return formatear_venta_completa($actual);
            }
            $actual = hd($actual);
        }
    }
    return null;
}

/**
 * Formatea una venta con todos sus detalles.
 */
function formatear_venta_completa(Nodo $nodo_venta): array {
    // 'fecha' aquí es la fecha de venta (timestamp formateado o string según corresponda)
    $datos = formatear_venta_resumida($nodo_venta);
    $datos['metodo_pago'] = $nodo_venta->adyacente('metodo_pago') ? $nodo_venta->adyacente('metodo_pago')->dato() : '';
    $datos['cuotas'] = $nodo_venta->adyacente('cuotas') ? $nodo_venta->adyacente('cuotas')->dato() : '1';
    $datos['pagado'] = $nodo_venta->adyacente('pagado') ? $nodo_venta->adyacente('pagado')->dato() : '0';
    $datos['cuotas_restantes'] = $nodo_venta->adyacente('cuotas_restantes') ? $nodo_venta->adyacente('cuotas_restantes')->dato() : '0';

    // Fecha de último pago
    $datos['fecha_pago'] = $nodo_venta->adyacente('fecha_ultimo_pago') ? $nodo_venta->adyacente('fecha_ultimo_pago')->dato() : '';

    // Comprador
    $nodo_comprador = $nodo_venta->adyacente('comprador');
    if ($nodo_comprador) {
        $comp_apellido = $nodo_comprador->adyacente('apellido') ? $nodo_comprador->adyacente('apellido')->dato() : '';
        $comp_nombres = $nodo_comprador->adyacente('nombres') ? $nodo_comprador->adyacente('nombres')->dato() : '';
        $datos['comprador'] = [
            'dni' => $nodo_comprador->dato(),
            'dni_visible' => normalizar_dni($nodo_comprador->dato()),
            'apellido' => $comp_apellido,
            'nombres' => $comp_nombres,
            'nombre_completo' => formatear_nombre_completo($comp_apellido, $comp_nombres),
            'email' => $nodo_comprador->adyacente('email') ? $nodo_comprador->adyacente('email')->dato() : '',
            'celular' => $nodo_comprador->adyacente('celular') ? $nodo_comprador->adyacente('celular')->dato() : '',
            'celular_emergencia' => $nodo_comprador->adyacente('celular_emergencia') ? $nodo_comprador->adyacente('celular_emergencia')->dato() : '',
            'fecha_nacimiento' => $nodo_comprador->adyacente('fecha_nacimiento') ? $nodo_comprador->adyacente('fecha_nacimiento')->dato() : '',
            'direccion' => $nodo_comprador->adyacente('direccion') ? $nodo_comprador->adyacente('direccion')->dato() : '',
            'localidad' => $nodo_comprador->adyacente('localidad') ? $nodo_comprador->adyacente('localidad')->dato() : '',
        ];
    }

    // Asientos
    $asientos = [];
    $cabeza_asientos = $nodo_venta->adyacente('asientos');
    if ($cabeza_asientos) {
        $actual = $cabeza_asientos->adyacente('primer');
        $seguridad = 0;
        while ($actual && $seguridad < 100) {
            $nodo_asiento_real = $actual->adyacente('asiento');
            $nodo_pasajero = $actual->adyacente('pasajero');
            $nodo_punto_sb = $actual->adyacente('punto_subida_bajada');
            $nodo_hora_sb = $actual->adyacente('hora_subida_bajada');
            $asiento_info = [
                'numero' => $nodo_asiento_real ? $nodo_asiento_real->dato() : '',
                'fila' => $nodo_asiento_real && $nodo_asiento_real->adyacente('fila') ? $nodo_asiento_real->adyacente('fila')->dato() : '',
                'columna' => $nodo_asiento_real && $nodo_asiento_real->adyacente('columna') ? $nodo_asiento_real->adyacente('columna')->dato() : '',
                'punto_subida_bajada' => $nodo_punto_sb ? $nodo_punto_sb->dato() : null,
                'hora_subida_bajada' => $nodo_hora_sb ? $nodo_hora_sb->dato() : null,
            ];
            if ($nodo_pasajero) {
                $pas_apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';
                $pas_nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';
                $asiento_info['pasajero'] = [
                    'dni' => $nodo_pasajero->dato(),
                    'dni_visible' => normalizar_dni($nodo_pasajero->dato()),
                    'apellido' => $pas_apellido,
                    'nombres' => $pas_nombres,
                    'nombre_completo' => formatear_nombre_completo($pas_apellido, $pas_nombres),
                    'email' => $nodo_pasajero->adyacente('email') ? $nodo_pasajero->adyacente('email')->dato() : '',
                    'celular' => $nodo_pasajero->adyacente('celular') ? $nodo_pasajero->adyacente('celular')->dato() : '',
                    'celular_emergencia' => $nodo_pasajero->adyacente('celular_emergencia') ? $nodo_pasajero->adyacente('celular_emergencia')->dato() : '',
                    'fecha_nacimiento' => $nodo_pasajero->adyacente('fecha_nacimiento') ? $nodo_pasajero->adyacente('fecha_nacimiento')->dato() : '',
                ];
            }
            $asientos[] = $asiento_info;
            $actual = $actual->adyacente('siguiente');
            $seguridad++;
        }
    }
    $datos['asientos'] = $asientos;

    // Datos del viaje: se usa un campo separado para la fecha del viaje
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    if ($nodo_viaje) {
        $datos['viaje_visible'] = $nodo_viaje->adyacente('nombre') ? $nodo_viaje->adyacente('nombre')->dato() : $nodo_viaje->dato();
        $datos['fecha_viaje'] = $nodo_viaje->adyacente('fecha') ? $nodo_viaje->adyacente('fecha')->dato() : '';
        $datos['hora'] = $nodo_viaje->adyacente('hora') ? $nodo_viaje->adyacente('hora')->dato() : '';
        $datos['origen'] = $nodo_viaje->adyacente('origen') ? $nodo_viaje->adyacente('origen')->dato() : '';
        $datos['destino'] = $nodo_viaje->adyacente('destino') ? $nodo_viaje->adyacente('destino')->dato() : '';
    }

    // Datos del micro
    $nodo_micro = $nodo_venta->adyacente('micro');
    if ($nodo_micro) {
        $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
        $datos['micro_nombre_visible'] = $nodo_copia && $nodo_copia->adyacente('nombre') ? $nodo_copia->adyacente('nombre')->dato() : '';
        $datos['empresa'] = $nodo_micro->adyacente('empresa') ? $nodo_micro->adyacente('empresa')->dato() : '';
        $datos['patente'] = $nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '';
    }

    // Cupones: si la venta tiene el contenedor `cupones` en el grafo,
    // se leen directamente. Si no (ventas viejas sin migrar), se
    // derivan al vuelo para que el frontend siempre tenga datos.
    $cupones = _leer_cupones_de_venta($nodo_venta);
    if (empty($cupones)) {
        $cupones = _construir_cupones_derivados($nodo_venta);
    }

    // Rellenar metodo_pago en cada cupón que no lo tenga: hereda el
    // de la venta. Solo se escribe el enlace en el cupón cuando el
    // método del pago efectivo difiere del de la venta.
    $metodo_pago_venta = $nodo_venta->adyacente('metodo_pago')
        ? $nodo_venta->adyacente('metodo_pago')->dato() : '';
    foreach ($cupones as &$c) {
        if (empty($c['metodo_pago'])) {
            $c['metodo_pago'] = $metodo_pago_venta;
        }
    }
    unset($c);
    $datos['cupones'] = $cupones;

    // Métodos de pago permitidos para esta venta: se resuelven con el
    // override del TerminalViaje > viaje > default, igual que al vender.
    $datos['metodos_permitidos'] = _resolver_metodos_permitidos_venta($nodo_venta);

    return $datos;
}

/**
 * Busca una venta por ID y devuelve el nodo junto con el nombre del
 * dueño. Devuelve [null, ''] si no la encuentra.
 *
 * @param string $id_venta
 * @return array{0: ?Nodo, 1: string}
 */
function _buscar_venta_por_id(string $id_venta): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [null, ''];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $cont = obtener_contenedor_ventas_dueno($nombre_dueno);
        if (!$cont) continue;
        $actual = hmi($cont);
        $seg = 0;
        while ($actual && $seg < 1000) {
            if ($actual->dato() === $id_venta) {
                return [$actual, $nombre_dueno];
            }
            $actual = hd($actual);
            $seg++;
        }
    }
    return [null, ''];
}

/**
 * Calcula cuánto hay que devolver por cada método al cancelar una
 * venta, leyendo los cupones pagados. Cada cupón aporta su monto al
 * método que tenga asignado, o al de la venta si no tiene uno propio.
 *
 * @param Nodo $nodo_venta
 * @return array{efectivo: float, banco: float, total: float}
 */
function _calcular_devolucion_venta(Nodo $nodo_venta): array {
    $metodo_venta = $nodo_venta->adyacente('metodo_pago') ? $nodo_venta->adyacente('metodo_pago')->dato() : 'efectivo';
    $efectivo = 0.0;
    $banco = 0.0;

    $contenedor = $nodo_venta->adyacente('cupones');
    if ($contenedor) {
        $actual = hmi($contenedor);
        $seg = 0;
        while ($actual && $seg < 200) {
            $estado = $actual->adyacente('estado') ? $actual->adyacente('estado')->dato() : 'pendiente';
            if ($estado === 'pagado') {
                $monto = (float)($actual->adyacente('monto') ? $actual->adyacente('monto')->dato() : '0');
                $metodo_cupon = $actual->adyacente('metodo_pago') ? $actual->adyacente('metodo_pago')->dato() : $metodo_venta;
                if ($metodo_cupon === 'transferencia') {
                    $banco += $monto;
                } else {
                    $efectivo += $monto;
                }
            }
            $actual = hd($actual);
            $seg++;
        }
    } else {
        // Venta sin contenedor de cupones: usar el pagado global.
        $pagado = (float)($nodo_venta->adyacente('pagado') ? $nodo_venta->adyacente('pagado')->dato() : '0');
        if ($metodo_venta === 'transferencia') {
            $banco = $pagado;
        } else {
            $efectivo = $pagado;
        }
    }

    return [
        'efectivo' => $efectivo,
        'banco' => $banco,
        'total' => $efectivo + $banco,
    ];
}

/**
 * Devuelve la información necesaria para el modal de cancelación,
 * sin modificar el grafo. Se usa para que el cajero vea el monto a
 * devolver y en qué terminal está ese dinero antes de confirmar.
 *
 * @param string $id_venta
 * @return array
 */
function obtener_info_cancelacion(string $id_venta): array {
    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta);
    if (!$nodo_venta) return ['exito' => false, 'error' => 'Venta no encontrada'];

    // Datos de viaje y micro.
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    $viaje_visible = '';
    if ($nodo_viaje) {
        $viaje_visible = $nodo_viaje->adyacente('nombre') ? $nodo_viaje->adyacente('nombre')->dato() : $nodo_viaje->dato();
    }
    $nodo_micro = $nodo_venta->adyacente('micro');
    $micro_visible = '';
    if ($nodo_micro) {
        $copia = $nodo_micro->adyacente('vehiculo_copia');
        if ($copia && $copia->adyacente('nombre')) {
            $micro_visible = $copia->adyacente('nombre')->dato();
        }
    }

    // Comprador.
    $comprador = null;
    $nodo_comprador = $nodo_venta->adyacente('comprador');
    if ($nodo_comprador) {
        $ap = $nodo_comprador->adyacente('apellido') ? $nodo_comprador->adyacente('apellido')->dato() : '';
        $nom = $nodo_comprador->adyacente('nombres') ? $nodo_comprador->adyacente('nombres')->dato() : '';
        $comprador = [
            'nombre_completo' => formatear_nombre_completo($ap, $nom),
            'celular' => $nodo_comprador->adyacente('celular') ? $nodo_comprador->adyacente('celular')->dato() : '',
        ];
    }

    // Terminal que hizo la venta.
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    $terminal_usuario = $nodo_terminal ? $nodo_terminal->dato() : '';
    $terminal_visible = $terminal_usuario;
    if ($nodo_terminal && $nodo_terminal->adyacente('nombre_real')) {
        $terminal_visible = $nodo_terminal->adyacente('nombre_real')->dato();
    }

    // Montos.
    $pagado = (float)($nodo_venta->adyacente('pagado') ? $nodo_venta->adyacente('pagado')->dato() : '0');
    $devolucion = _calcular_devolucion_venta($nodo_venta);

    // Fecha de compra.
    $fecha_compra = $nodo_venta->adyacente('fecha_hora') ? $nodo_venta->adyacente('fecha_hora')->dato() : '';

    // ¿La terminal tiene saldo suficiente para devolver?
    $puede_cancelar = true;
    if ($nodo_terminal) {
        if ($devolucion['efectivo'] > 0) {
            $saldo_ef = (float)($nodo_terminal->adyacente('efectivo') ? $nodo_terminal->adyacente('efectivo')->dato() : '0');
            if ($saldo_ef + 0.001 < $devolucion['efectivo']) $puede_cancelar = false;
        }
        if ($devolucion['banco'] > 0) {
            $saldo_banco = (float)($nodo_terminal->adyacente('banco') ? $nodo_terminal->adyacente('banco')->dato() : '0');
            if ($saldo_banco + 0.001 < $devolucion['banco']) $puede_cancelar = false;
        }
    }

    return [
        'exito' => true,
        'info' => [
            'id_venta' => $id_venta,
            'viaje_visible' => $viaje_visible,
            'micro_visible' => $micro_visible,
            'fecha_compra' => $fecha_compra,
            'comprador' => $comprador,
            'terminal_usuario' => $terminal_usuario,
            'terminal_visible' => $terminal_visible,
            'pagado' => number_format($pagado, 2, '.', ''),
            'efectivo_a_devolver' => number_format($devolucion['efectivo'], 2, '.', ''),
            'banco_a_devolver' => number_format($devolucion['banco'], 2, '.', ''),
            'total_a_devolver' => number_format($devolucion['total'], 2, '.', ''),
            'puede_cancelar' => $puede_cancelar,
        ],
    ];
}

/**
 * Cancela una venta, libera asientos, elimina cupones y
 * asientos-en-venta persistentes, y revierte los montos en la
 * terminal según el método de cada cupón pagado.
 *
 * @param string $id_venta
 * @return array
 */
function cancelar_venta(string $id_venta): array {
    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta);
    if (!$nodo_venta) return ['exito' => false, 'error' => 'Venta no encontrada'];

    // 1. Revertir montos: calcular por método y restar de la terminal.
    $devolucion = _calcular_devolucion_venta($nodo_venta);
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    if ($nodo_terminal) {
        if ($devolucion['efectivo'] > 0) {
            $nodo_ef = $nodo_terminal->adyacente('efectivo');
            if ($nodo_ef) {
                $nuevo = max(0, (float)$nodo_ef->dato() - $devolucion['efectivo']);
                $nodo_ef->_dato((string)$nuevo);
            }
        }
        if ($devolucion['banco'] > 0) {
            $nodo_banco = $nodo_terminal->adyacente('banco');
            if ($nodo_banco) {
                $nuevo = max(0, (float)$nodo_banco->dato() - $devolucion['banco']);
                $nodo_banco->_dato((string)$nuevo);
            }
        }
    }

    // 2. Liberar asientos reales (en vehiculo_copia) y eliminar los
    //    nodos asiento-en-venta persistentes de la lista.
    $cabeza_asientos = $nodo_venta->adyacente('asientos');
    if ($cabeza_asientos) {
        $asiento_venta = $cabeza_asientos->adyacente('primer');
        $seg = 0;
        while ($asiento_venta && $seg < 200) {
            $nodo_asiento_real = $asiento_venta->adyacente('asiento');
            if ($nodo_asiento_real) {
                $estado = $nodo_asiento_real->adyacente('estado');
                if ($estado) $estado->_dato('libre');
                $nodo_asiento_real->eliminar_adyacente('seleccionado_por');
                $nodo_asiento_real->eliminar_adyacente('pasajero');
                $nodo_asiento_real->eliminar_adyacente('venta');
            }
            $asiento_venta = $asiento_venta->adyacente('siguiente');
            $seg++;
        }
        // Eliminar la lista de asientos-en-venta persistentes.
        while ($asiento_a_borrar = eliminar_hmi($cabeza_asientos)) {
            Nodo::eliminar($asiento_a_borrar);
        }
        // Ahora la cabeza no tiene hijos, se puede eliminar.
        $nodo_venta->eliminar_adyacente('asientos');
        Nodo::eliminar($cabeza_asientos);
    }

    // 3. Eliminar los cupones y su contenedor.
    $contenedor_cupones = $nodo_venta->adyacente('cupones');
    if ($contenedor_cupones) {
        while ($cupon_a_borrar = eliminar_hmi($contenedor_cupones)) {
            Nodo::eliminar($cupon_a_borrar);
        }
        $nodo_venta->eliminar_adyacente('cupones');
        Nodo::eliminar($contenedor_cupones);
    }

    // 4. Actualizar contadores del micro y del viaje.
    $nodo_micro = $nodo_venta->adyacente('micro');
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    if ($nodo_micro) actualizar_contadores_micro($nodo_micro);
    if ($nodo_viaje && $nodo_micro) {
        $nombre_viaje = $nodo_viaje->dato();
        actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);
    }

    // 5. Desenlazar la venta del árbol del dueño.
    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if ($contenedor_ventas) {
        $anterior = null;
        $actual = hmi($contenedor_ventas);
        $seg = 0;
        while ($actual && $seg < 1000) {
            if ($actual->id() === $nodo_venta->id()) {
                if ($anterior) {
                    $siguiente = hd($actual);
                    if ($siguiente) {
                        $anterior->_adyacente_en($siguiente, 'hd', true);
                    } else {
                        $anterior->eliminar_adyacente('hd');
                    }
                } else {
                    $siguiente = hd($actual);
                    if ($siguiente) {
                        $contenedor_ventas->_adyacente_en($siguiente, 'hmi', true);
                    } else {
                        $contenedor_ventas->eliminar_adyacente('hmi');
                    }
                }
                break;
            }
            $anterior = $actual;
            $actual = hd($actual);
            $seg++;
        }
    }

    // 6. Eliminar el nodo venta entero.
    Nodo::eliminar($nodo_venta);

    Controlador::guardar(Conf::NOMBRE_APP);

    return [
        'exito' => true,
        'devolucion' => [
            'efectivo' => number_format($devolucion['efectivo'], 2, '.', ''),
            'banco' => number_format($devolucion['banco'], 2, '.', ''),
            'total' => number_format($devolucion['total'], 2, '.', ''),
        ],
    ];
}

/**
 * Resuelve la lista de métodos de pago permitidos para una venta,
 * según el override del TerminalViaje > configuración del viaje >
 * default. Devuelve un array con "efectivo" y/o "transferencia".
 *
 * @param Nodo $nodo_venta
 * @return array<int, string>
 */
function _resolver_metodos_permitidos_venta(Nodo $nodo_venta): array {
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    if (!$nodo_viaje || !$nodo_terminal) return [];

    $nombre_viaje = $nodo_viaje->dato();
    $nombre_terminal = $nodo_terminal->dato();
    $nodo_dueno = $nodo_viaje->adyacente('dueno');
    $nombre_dueno = $nodo_dueno ? $nodo_dueno->dato() : '';
    if ($nombre_dueno === '') return [];

    // Cuotas pactadas de la venta. Se usan para validar que el método
    // elegido soporte esa cantidad de cuotas según la configuración
    // del viaje o de la terminal.
    $cuotas_pactadas = (int)($nodo_venta->adyacente('cuotas') ? $nodo_venta->adyacente('cuotas')->dato() : '1');
    if ($cuotas_pactadas < 1) $cuotas_pactadas = 1;

    $opciones_viaje = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
    $opciones_terminal = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);

    $resolver = function(string $campo, string $default) use ($opciones_viaje, $opciones_terminal) {
        if (isset($opciones_terminal[$campo]) && trim((string)$opciones_terminal[$campo]) !== '') {
            return (string)$opciones_terminal[$campo];
        }
        if (isset($opciones_viaje[$campo]) && trim((string)$opciones_viaje[$campo]) !== '') {
            return (string)$opciones_viaje[$campo];
        }
        return $default;
    };

    $metodos = [];

    // Efectivo: se ofrece si está permitido y el máximo de cuotas
    // configurado alcanza para las cuotas pactadas.
    if ($resolver('permite_efectivo', '1') === '1') {
        $max_efectivo = (int)$resolver('cuotas_efectivo_max', '3');
        if ($max_efectivo >= $cuotas_pactadas) $metodos[] = 'efectivo';
    }

    // Transferencia: mismo criterio.
    if ($resolver('permite_transferencia', '1') === '1') {
        $max_transferencia = (int)$resolver('cuotas_transferencia_max', '1');
        if ($max_transferencia >= $cuotas_pactadas) $metodos[] = 'transferencia';
    }

    return $metodos;
}

/**
 * Elimina un cupón específico del contenedor `cupones`.
 *
 * @param Nodo $contenedor
 * @param Nodo $cupon
 * @return void
 */
function _eliminar_cupon_del_contenedor(Nodo $contenedor, Nodo $cupon): void {
    $primero = hmi($contenedor);
    if ($primero && $primero->id() === $cupon->id()) {
        eliminar_hmi($contenedor);
        Nodo::eliminar($cupon);
        return;
    }
    $anterior = $primero;
    $seg = 0;
    while ($anterior && $seg < 200) {
        $siguiente = hd($anterior);
        if ($siguiente && $siguiente->id() === $cupon->id()) {
            eliminar_hd($anterior);
            Nodo::eliminar($cupon);
            return;
        }
        $anterior = $siguiente;
        $seg++;
    }
}

/**
 * Registra el pago de un cupón de una venta.
 *
 * Reglas:
 *  - El cupón debe estar pendiente.
 *  - El monto debe ser > 0 y <= saldo pendiente de la venta.
 *  - Si es el último cupón pendiente, el monto debe ser exactamente el saldo.
 *  - El método de pago debe estar permitido para esa terminal/viaje.
 *  - Al pagar, se actualiza el monto del cupón al real, se marca como
 *    pagado, y se recalculan los montos teóricos de los cupones
 *    pendientes restantes (saldo / pendientes).
 *  - Si el saldo llega a 0, se eliminan los cupones pendientes sobrantes.
 *  - Se suma el monto a la terminal (efectivo o banco).
 *
 * @param string $id_venta
 * @param string $numero_cupon
 * @param string $monto
 * @param string $metodo_pago
 * @return array
 */
function pagar_cupon_venta(string $id_venta, string $numero_cupon, string $monto, string $metodo_pago): array {
    $monto_num = (float)$monto;
    if ($monto_num <= 0) return ['exito' => false, 'error' => 'Monto inválido'];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    // Buscar la venta en el árbol del dueño.
    $nodo_venta = null;
    $nombre_dueno_venta = '';
    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $cont = obtener_contenedor_ventas_dueno($nombre_dueno);
        if (!$cont) continue;
        $actual = hmi($cont);
        while ($actual) {
            if ($actual->dato() === $id_venta) {
                $nodo_venta = $actual;
                $nombre_dueno_venta = $nombre_dueno;
                break 2;
            }
            $actual = hd($actual);
        }
    }
    if (!$nodo_venta) return ['exito' => false, 'error' => 'Venta no encontrada'];

    // Resolver métodos permitidos.
    $metodos_permitidos = _resolver_metodos_permitidos_venta($nodo_venta);
    $metodo_pago = strtolower($metodo_pago);
    if ($metodo_pago !== 'efectivo' && $metodo_pago !== 'transferencia') {
        return ['exito' => false, 'error' => 'Método de pago inválido'];
    }
    if (!in_array($metodo_pago, $metodos_permitidos, true)) {
        return ['exito' => false, 'error' => 'El método de pago no está permitido para esta venta'];
    }

    // Leer cupones del contenedor.
    $contenedor_cupones = $nodo_venta->adyacente('cupones');
    if (!$contenedor_cupones) return ['exito' => false, 'error' => 'La venta no tiene cupones'];

    $cupon_objetivo = null;
    $cupones_pendientes = [];
    $actual = hmi($contenedor_cupones);
    $seg = 0;
    while ($actual && $seg < 200) {
        $num = $actual->adyacente('numero') ? $actual->adyacente('numero')->dato() : '';
        $est = $actual->adyacente('estado') ? $actual->adyacente('estado')->dato() : 'pendiente';
        if ($num === $numero_cupon) $cupon_objetivo = $actual;
        if ($est === 'pendiente') $cupones_pendientes[] = $actual;
        $actual = hd($actual);
        $seg++;
    }

    if (!$cupon_objetivo) return ['exito' => false, 'error' => 'Cupón no encontrado'];
    $estado_obj = $cupon_objetivo->adyacente('estado') ? $cupon_objetivo->adyacente('estado')->dato() : 'pendiente';
    if ($estado_obj !== 'pendiente') return ['exito' => false, 'error' => 'El cupón ya está pagado'];

    // Calcular saldo.
    $total = (float)($nodo_venta->adyacente('total') ? $nodo_venta->adyacente('total')->dato() : '0');
    $pagado_previo = (float)($nodo_venta->adyacente('pagado') ? $nodo_venta->adyacente('pagado')->dato() : '0');
    $saldo = max(0, $total - $pagado_previo);

    if ($monto_num > $saldo + 0.001) {
        return ['exito' => false, 'error' => 'El monto no puede superar el saldo pendiente'];
    }

    // Es el último pendiente?
    $es_ultimo = (count($cupones_pendientes) === 1 && $cupones_pendientes[0]->id() === $cupon_objetivo->id());
    if ($es_ultimo && abs($monto_num - $saldo) > 0.001) {
        return ['exito' => false, 'error' => 'En el último cupón pendiente debe cobrarse el saldo exacto'];
    }

    $fecha_pago = date('d/m/Y H:i');

    // Actualizar monto, estado y fecha del cupón.
    $nodo_monto = $cupon_objetivo->adyacente('monto');
    $monto_str = number_format($monto_num, 2, '.', '');
    if ($nodo_monto) $nodo_monto->_dato($monto_str);
    else $cupon_objetivo->_adyacente_en(Nodo::crear_con_dato($monto_str), 'monto');

    $nodo_estado = $cupon_objetivo->adyacente('estado');
    if ($nodo_estado) $nodo_estado->_dato('pagado');
    else $cupon_objetivo->_adyacente_en(Nodo::crear_con_dato('pagado'), 'estado');

    $nodo_fecha_cupon = $cupon_objetivo->adyacente('fecha_pago');
    if ($nodo_fecha_cupon) $nodo_fecha_cupon->_dato($fecha_pago);
    else $cupon_objetivo->_adyacente_en(Nodo::crear_con_dato($fecha_pago), 'fecha_pago');

    // Método de pago: solo se escribe si difiere del de la venta.
    $metodo_venta = $nodo_venta->adyacente('metodo_pago') ? $nodo_venta->adyacente('metodo_pago')->dato() : '';
    if ($metodo_pago !== $metodo_venta) {
        $nodo_metodo_cupon = $cupon_objetivo->adyacente('metodo_pago');
        if ($nodo_metodo_cupon) $nodo_metodo_cupon->_dato($metodo_pago);
        else $cupon_objetivo->_adyacente_en(Nodo::crear_con_dato($metodo_pago), 'metodo_pago');
    } else {
        $cupon_objetivo->eliminar_adyacente('metodo_pago');
    }

    // Actualizar pagado de la venta.
    $nuevo_pagado = $pagado_previo + $monto_num;
    $nodo_pagado = $nodo_venta->adyacente('pagado');
    $nuevo_pagado_str = number_format($nuevo_pagado, 2, '.', '');
    if ($nodo_pagado) $nodo_pagado->_dato($nuevo_pagado_str);
    else $nodo_venta->_adyacente_en(Nodo::crear_con_dato($nuevo_pagado_str), 'pagado');

    $nodo_fp = $nodo_venta->adyacente('fecha_ultimo_pago');
    if ($nodo_fp) $nodo_fp->_dato($fecha_pago);
    else $nodo_venta->_adyacente_en(Nodo::crear_con_dato($fecha_pago), 'fecha_ultimo_pago');

    // Recolectar cupones pendientes restantes (post pago).
    $pendientes_restantes = [];
    $actual = hmi($contenedor_cupones);
    $seg = 0;
    while ($actual && $seg < 200) {
        $est = $actual->adyacente('estado') ? $actual->adyacente('estado')->dato() : 'pendiente';
        if ($est === 'pendiente') $pendientes_restantes[] = $actual;
        $actual = hd($actual);
        $seg++;
    }

    $saldo_restante = max(0, $total - $nuevo_pagado);

    if ($saldo_restante <= 0.001) {
        // Pagó todo: eliminar cupones pendientes sobrantes.
        foreach ($pendientes_restantes as $p) {
            _eliminar_cupon_del_contenedor($contenedor_cupones, $p);
        }
        $pendientes_restantes = [];
    } else if (count($pendientes_restantes) > 0) {
        // Recalcular montos teóricos.
        $cantidad = count($pendientes_restantes);
        $teorico = round($saldo_restante / $cantidad, 2);
        foreach ($pendientes_restantes as $i => $p) {
            $monto_nuevo = ($i === $cantidad - 1)
                ? round($saldo_restante - $teorico * $i, 2)
                : $teorico;
            $monto_nuevo_str = number_format($monto_nuevo, 2, '.', '');
            $nodo_m = $p->adyacente('monto');
            if ($nodo_m) $nodo_m->_dato($monto_nuevo_str);
            else $p->_adyacente_en(Nodo::crear_con_dato($monto_nuevo_str), 'monto');
        }
    }

    // Actualizar cuotas_restantes de la venta.
    $nodo_cr = $nodo_venta->adyacente('cuotas_restantes');
    $cr_str = (string)count($pendientes_restantes);
    if ($nodo_cr) $nodo_cr->_dato($cr_str);
    else $nodo_venta->_adyacente_en(Nodo::crear_con_dato($cr_str), 'cuotas_restantes');

    // Impacto en la terminal.
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    if ($nodo_terminal) {
        if ($metodo_pago === 'efectivo') {
            $nodo_ef = $nodo_terminal->adyacente('efectivo');
            if (!$nodo_ef) {
                $nodo_ef = Nodo::crear_con_dato('0');
                $nodo_terminal->_adyacente_en($nodo_ef, 'efectivo');
            }
            $nuevo_ef = (float)$nodo_ef->dato() + $monto_num;
            $nodo_ef->_dato((string)$nuevo_ef);
        } else {
            $nodo_banco = $nodo_terminal->adyacente('banco');
            if ($nodo_banco) {
                $nuevo_b = (float)$nodo_banco->dato() + $monto_num;
                $nodo_banco->_dato((string)$nuevo_b);
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);

    return [
        'exito' => true,
        'venta' => formatear_venta_completa($nodo_venta),
    ];
}

/**
 * Calcula el monto teórico de cada cuota y el remanente de la última.
 *
 * Devuelve [monto_cuota, monto_ultima] como strings con dos decimales.
 * La última cuota absorbe el remanente de centavos para que la suma
 * sea exactamente igual al total.
 *
 * @param string $total
 * @param int    $cuotas
 * @return array{0: string, 1: string}
 */
function _calcular_montos_cuotas(string $total, int $cuotas): array {
    if ($cuotas <= 0) {
        return ['0.00', '0.00'];
    }
    $total_num = (float)$total;
    if ($cuotas === 1) {
        $unico = number_format($total_num, 2, '.', '');
        return [$unico, $unico];
    }
    $monto_cuota = round($total_num / $cuotas, 2);
    $monto_ultima = round($total_num - $monto_cuota * ($cuotas - 1), 2);
    return [
        number_format($monto_cuota, 2, '.', ''),
        number_format($monto_ultima, 2, '.', '')
    ];
}

/**
 * Crea la lista de cupones de una venta como hijos del contenedor
 * `cupones`. Usa la estructura de árbol (hmi/hd/p) de Arbol.php.
 *
 * El cupón 1 refleja el monto real abonado al momento de la venta
 * (monto_pagado). Los cupones pendientes (2..N) reparten el saldo
 * restante en partes iguales; el último absorbe el remanente de
 * centavos para que la suma sea exactamente igual al total.
 *
 * Si el comprador pagó todo al momento de la venta, se crea un solo
 * cupón (el 1) con el monto total y no quedan oportunidades.
 *
 * @param Nodo   $nodo_venta
 * @param int    $cuotas
 * @param string $total
 * @param string $monto_pagado Monto real abonado al momento de la venta.
 * @param string $fecha_pago Fecha a usar para el cupón pagado.
 * @return void
 */
function _crear_lista_cupones_venta(Nodo $nodo_venta, int $cuotas, string $total, string $monto_pagado, string $fecha_pago): void {
    if ($cuotas <= 0) return;

    $total_num = (float)$total;
    $monto_pagado_num = (float)$monto_pagado;
    $saldo = max(0, $total_num - $monto_pagado_num);

    $contenedor_cupones = Nodo::crear_con_dato('');
    $nodo_venta->_adyacente_en($contenedor_cupones, 'cupones');

    // Caso A: pagó todo al momento de la venta. Un solo cupón pagado
    // con el monto total. No quedan más oportunidades.
    if ($saldo <= 0.001) {
        $cupon = Nodo::crear_con_dato('');
        $cupon->_adyacente_en(Nodo::crear_con_dato('1'), 'numero');
        $cupon->_adyacente_en(Nodo::crear_con_dato(number_format($total_num, 2, '.', '')), 'monto');
        $cupon->_adyacente_en(Nodo::crear_con_dato('pagado'), 'estado');
        if ($fecha_pago !== '') {
            $cupon->_adyacente_en(Nodo::crear_con_dato($fecha_pago), 'fecha_pago');
        }
        _hmi($contenedor_cupones, $cupon);
        return;
    }

    // Caso B: pagó parte. Cupón 1 pagado con el monto real. Cupones
    // 2..N pendientes con el saldo repartido.
    $cuotas_restantes = $cuotas - 1;

    // Cupón 1 (pagado).
    $cupon_1 = Nodo::crear_con_dato('');
    $cupon_1->_adyacente_en(Nodo::crear_con_dato('1'), 'numero');
    $cupon_1->_adyacente_en(Nodo::crear_con_dato(number_format($monto_pagado_num, 2, '.', '')), 'monto');
    $cupon_1->_adyacente_en(Nodo::crear_con_dato('pagado'), 'estado');
    if ($fecha_pago !== '') {
        $cupon_1->_adyacente_en(Nodo::crear_con_dato($fecha_pago), 'fecha_pago');
    }
    _hmi($contenedor_cupones, $cupon_1);
    $anterior = $cupon_1;

    // Cupones 2..N (pendientes).
    $teorico_pendiente = round($saldo / $cuotas_restantes, 2);
    for ($i = 2; $i <= $cuotas; $i++) {
        $es_ultimo = ($i === $cuotas);
        $monto_pendiente = $es_ultimo
            ? round($saldo - $teorico_pendiente * ($cuotas_restantes - 1), 2)
            : $teorico_pendiente;

        $cupon = Nodo::crear_con_dato('');
        $cupon->_adyacente_en(Nodo::crear_con_dato((string)$i), 'numero');
        $cupon->_adyacente_en(Nodo::crear_con_dato(number_format($monto_pendiente, 2, '.', '')), 'monto');
        $cupon->_adyacente_en(Nodo::crear_con_dato('pendiente'), 'estado');

        _hd($anterior, $cupon);
        $anterior = $cupon;
    }
}

/**
 * Lee los cupones de una venta desde el grafo.
 *
 * @param Nodo $nodo_venta
 * @return array<int, array{numero: string, monto: string, estado: string, fecha_pago: string}>
 */
function _leer_cupones_de_venta(Nodo $nodo_venta): array {
    $contenedor = $nodo_venta->adyacente('cupones');
    if (!$contenedor) return [];

    $cupones = [];
    $actual = hmi($contenedor);
    $seguridad = 0;
    while ($actual && $seguridad < 200) {
        $cupones[] = [
            'numero' => $actual->adyacente('numero') ? $actual->adyacente('numero')->dato() : '',
            'monto' => $actual->adyacente('monto') ? $actual->adyacente('monto')->dato() : '0.00',
            'estado' => $actual->adyacente('estado') ? $actual->adyacente('estado')->dato() : 'pendiente',
            'fecha_pago' => $actual->adyacente('fecha_pago') ? $actual->adyacente('fecha_pago')->dato() : '',
            'metodo_pago' => $actual->adyacente('metodo_pago') ? $actual->adyacente('metodo_pago')->dato() : '',
        ];
        $actual = hd($actual);
        $seguridad++;
    }
    return $cupones;
}

/**
 * Deriva los cupones al vuelo para ventas que no tienen el contenedor
 * `cupones` en el grafo (ventas viejas sin migrar).
 *
 * @param Nodo $nodo_venta
 * @return array<int, array{numero: string, monto: string, estado: string, fecha_pago: string}>
 */
function _construir_cupones_derivados(Nodo $nodo_venta): array {
    $total = $nodo_venta->adyacente('total') ? $nodo_venta->adyacente('total')->dato() : '0';
    $cuotas = (int)($nodo_venta->adyacente('cuotas') ? $nodo_venta->adyacente('cuotas')->dato() : '1');
    $cuotas_restantes = (int)($nodo_venta->adyacente('cuotas_restantes') ? $nodo_venta->adyacente('cuotas_restantes')->dato() : '0');
    $fecha_pago = $nodo_venta->adyacente('fecha_ultimo_pago') ? $nodo_venta->adyacente('fecha_ultimo_pago')->dato() : '';

    if ($cuotas <= 0) return [];

    [$monto_cuota, $monto_ultima] = _calcular_montos_cuotas($total, $cuotas);
    $cupones_pagados = max(0, $cuotas - $cuotas_restantes);

    $cupones = [];
    for ($i = 1; $i <= $cuotas; $i++) {
        $es_pagado = ($i <= $cupones_pagados);
        $cupones[] = [
            'numero' => (string)$i,
            'monto' => ($i === $cuotas) ? $monto_ultima : $monto_cuota,
            'estado' => $es_pagado ? 'pagado' : 'pendiente',
            'fecha_pago' => $es_pagado ? $fecha_pago : '',
            'metodo_pago' => '',
        ];
    }
    return $cupones;
}

