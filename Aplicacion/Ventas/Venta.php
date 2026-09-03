<?php
/**
 * Gestión de ventas persistentes.
 * Utiliza árbol (hmi/hd) para almacenar las ventas.
 *
 * @package   Iteradores
 * @since     1.5piloto.14
 * @version   1.5piloto.16
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
    $campos = ['nombre', 'email', 'celular', 'celular_emergencia', 'fecha_nacimiento'];
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
 * Actualiza los contadores de un micro (ocupación, seleccionados, vendidos) basado en los estados reales de sus asientos.
 */
function actualizar_contadores_micro(Nodo $nodo_micro): void {
    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return;

    $total = 0;
    $seleccionados = 0;
    $vendidos = 0;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            $contador_seguridad = 0;
            while ($actual && $actual->id() !== $cabeza->id() && $contador_seguridad < 1000) {
                $estado = $actual->adyacente('estado');
                if ($estado) {
                    $estado_str = $estado->dato();
                    if ($estado_str === 'seleccionado') $seleccionados++;
                    elseif ($estado_str === 'vendido' || $estado_str === 'no disponible') $vendidos++;
                    $total++;
                }
                $actual = $actual->adyacente('siguiente');
                $contador_seguridad++;
            }
        }
    }

    $nodo_micro->adyacente('ocupacion')?->_dato((string)$total);
    $nodo_micro->adyacente('seleccionados')?->_dato((string)$seleccionados);
    $nodo_micro->adyacente('vendidos')?->_dato((string)$vendidos);
}


/**
 * Confirma la venta actual de una terminal, creando una venta persistente.
 *
 * @param string $nombre_terminal Nombre de la terminal.
 * @param string $metodo_pago 'efectivo' o 'transferencia'.
 * @param int    $cuotas Número de cuotas (1-3, solo efectivo).
 * @param float  $monto_pagado Monto abonado en el momento de la venta.
 * @param string $comprador_dni DNI del comprador.
 * @param string $comprador_nombre Nombre del comprador.
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
    string $comprador_nombre,
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

    // Validar método de pago y cuotas
    $metodo_pago = strtolower($metodo_pago);
    if (!in_array($metodo_pago, ['efectivo', 'transferencia'])) {
        return ['exito' => false, 'error' => 'Método de pago inválido'];
    }
    if ($metodo_pago === 'transferencia') {
        $cuotas = 1;
    } else {
        if ($cuotas < 1 || $cuotas > 3) {
            return ['exito' => false, 'error' => 'Cantidad de cuotas inválida (1-3)'];
        }
    }

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
    $cuotas_restantes = 0;
    if ($metodo_pago === 'efectivo') {
        if ($cuotas == 1) {
            $cuotas_restantes = 0;
        } else {
            if ($monto_pagado < $total) {
                $cuotas_restantes = $cuotas - 1;
            } else {
                $cuotas_restantes = 0;
            }
        }
    } else { // transferencia
        $cuotas_restantes = 0;
        $monto_pagado = $total;
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
        'nombre' => $comprador_nombre,
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

        if (empty($dni_pasajero) || empty($fecha_nacimiento_pasajero) || empty($datos_pasajero['celular']) || empty($datos_pasajero['celular_emergencia'])) {
            return ['exito' => false, 'error' => 'Faltan datos obligatorios del pasajero ' . ($indice_asiento + 1)];
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

    $nombre_terminal = $nodo_terminal ? $nodo_terminal->dato() : '';
    $nombre_viaje = $nodo_viaje ? $nodo_viaje->dato() : '';
    $nombre_micro = $nodo_micro ? ($nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '') : '';
    $total = $nodo_total ? $nodo_total->dato() : '0';

    $fecha = '';
    if ($nodo_fecha) {
        $valor_fecha = $nodo_fecha->dato();
        // Si es numérico, es un timestamp antiguo; lo convertimos con la zona horaria configurada.
        if (is_numeric($valor_fecha)) {
            $fecha = date('d/m/Y H:i', (int)$valor_fecha);
        } else {
            // Ya es una cadena formateada desde el frontend, se devuelve tal cual.
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

    return [
        'id_venta' => $id_venta,
        'terminal' => $nombre_terminal,
        'viaje' => $nombre_viaje,
        'micro' => $nombre_micro,
        'total' => $total,
        'fecha' => $fecha,
        'cantidad_asientos' => $cantidad_asientos,
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
        $datos['comprador'] = [
            'dni' => $nodo_comprador->dato(),
            'nombre' => $nodo_comprador->adyacente('nombre') ? $nodo_comprador->adyacente('nombre')->dato() : '',
            'email' => $nodo_comprador->adyacente('email') ? $nodo_comprador->adyacente('email')->dato() : '',
            'celular' => $nodo_comprador->adyacente('celular') ? $nodo_comprador->adyacente('celular')->dato() : '',
            'celular_emergencia' => $nodo_comprador->adyacente('celular_emergencia') ? $nodo_comprador->adyacente('celular_emergencia')->dato() : '',
            'fecha_nacimiento' => $nodo_comprador->adyacente('fecha_nacimiento') ? $nodo_comprador->adyacente('fecha_nacimiento')->dato() : '',
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
            $asiento_info = [
                'numero' => $nodo_asiento_real ? $nodo_asiento_real->dato() : '',
                'fila' => $nodo_asiento_real && $nodo_asiento_real->adyacente('fila') ? $nodo_asiento_real->adyacente('fila')->dato() : '',
                'columna' => $nodo_asiento_real && $nodo_asiento_real->adyacente('columna') ? $nodo_asiento_real->adyacente('columna')->dato() : '',
            ];
            if ($nodo_pasajero) {
                $asiento_info['pasajero'] = [
                    'dni' => $nodo_pasajero->dato(),
                    'nombre' => $nodo_pasajero->adyacente('nombre') ? $nodo_pasajero->adyacente('nombre')->dato() : '',
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

    return $datos;
}

/**
 * Cancela una venta, libera asientos y revierte montos.
 */
function cancelar_venta(string $id_venta): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios'];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_nivel = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

        $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);
        if (!$contenedor) continue;

        $anterior = null;
        $actual = hmi($contenedor);
        while ($actual) {
            if ($actual->dato() === $id_venta) {
                // Liberar asientos
                $cabeza_asientos = $actual->adyacente('asientos');
                if ($cabeza_asientos) {
                    $asiento_venta = $cabeza_asientos->adyacente('primer');
                    $seguridad = 0;
                    while ($asiento_venta && $seguridad < 100) {
                        $nodo_asiento_real = $asiento_venta->adyacente('asiento');
                        if ($nodo_asiento_real) {
                            $nodo_asiento_real->adyacente('estado')?->_dato('libre');
                            $nodo_asiento_real->eliminar_adyacente('seleccionado_por');
                            $nodo_asiento_real->eliminar_adyacente('pasajero');
                            $nodo_asiento_real->eliminar_adyacente('venta');
                        }
                        $asiento_venta = $asiento_venta->adyacente('siguiente');
                        $seguridad++;
                    }
                }

                // Revertir montos de terminal
                $nodo_terminal = $actual->adyacente('terminal');
                if ($nodo_terminal) {
                    $metodo_pago = $actual->adyacente('metodo_pago') ? $actual->adyacente('metodo_pago')->dato() : '';
                    $monto_pagado = $actual->adyacente('pagado') ? (float)$actual->adyacente('pagado')->dato() : 0;
                    if ($metodo_pago === 'efectivo') {
                        $nodo_efectivo = $nodo_terminal->adyacente('efectivo');
                        if ($nodo_efectivo) {
                            $nuevo = (float)$nodo_efectivo->dato() - $monto_pagado;
                            $nodo_efectivo->_dato((string)max(0, $nuevo));
                        }
                    } else {
                        $nodo_banco = $nodo_terminal->adyacente('banco');
                        if ($nodo_banco) {
                            $nuevo = (float)$nodo_banco->dato() - $monto_pagado;
                            $nodo_banco->_dato((string)max(0, $nuevo));
                        }
                    }
                }

                // Actualizar contadores del micro y viaje
                $nodo_micro = $actual->adyacente('micro');
                $nodo_viaje = $actual->adyacente('viaje');
                if ($nodo_micro) actualizar_contadores_micro($nodo_micro);
                if ($nodo_viaje && $nodo_micro) {
                    $nombre_viaje = $nodo_viaje->dato();
                    $nombre_dueno_actual = $nodo_dueno->dato();
                    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno_actual);
                }

                // Eliminar la venta del árbol
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
                        $contenedor->_adyacente_en($siguiente, 'hmi', true);
                    } else {
                        $contenedor->eliminar_adyacente('hmi');
                    }
                }

                Controlador::guardar(Conf::NOMBRE_APP);
                return ['exito' => true];
            }
            $anterior = $actual;
            $actual = hd($actual);
        }
    }

    return ['exito' => false, 'error' => 'Venta no encontrada'];
}