<?php
/**
 * Funciones para opciones avanzadas de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.12
 * @version   1.5piloto.32
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Obtiene las opciones avanzadas de un viaje.
 * Si no existen, devuelve valores por defecto.
 *
 * A partir de v1.5piloto.32 incluye las condiciones de pago:
 * - permite_efectivo, cuotas_efectivo_max
 * - permite_transferencia, cuotas_transferencia_max
 */
function obtener_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje): array {
    $defaults = [
        'mostrar_ficha_medica' => '0',
        'restriccion_edad' => '0',
        'edad_minima' => '18',
        'edad_maxima' => '80',
        'permite_efectivo' => '1',
        'cuotas_efectivo_max' => '3',
        'permite_transferencia' => '1',
        'cuotas_transferencia_max' => '1',
    ];

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return $defaults;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return $defaults;

    $nodo_opciones = $nodo_viaje->adyacente('opciones_avanzadas');
    if (!$nodo_opciones) return $defaults;

    $opciones = $defaults;
    $campos = [
        'mostrar_ficha_medica',
        'restriccion_edad',
        'edad_minima',
        'edad_maxima',
        'permite_efectivo',
        'cuotas_efectivo_max',
        'permite_transferencia',
        'cuotas_transferencia_max',
    ];
    foreach ($campos as $campo) {
        $nodo_campo = $nodo_opciones->adyacente($campo);
        if ($nodo_campo) $opciones[$campo] = $nodo_campo->dato();
    }

    return $opciones;
}

/**
 * Guarda las opciones avanzadas de un viaje.
 */
function guardar_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, array $opciones): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_opciones = $nodo_viaje->adyacente('opciones_avanzadas');
    if (!$nodo_opciones) {
        $nodo_opciones = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_opciones, 'opciones_avanzadas');
    }

    // Sanitizar valores
    $mostrar_ficha = ($opciones['mostrar_ficha_medica'] ?? '0') === '1' ? '1' : '0';
    $restriccion = ($opciones['restriccion_edad'] ?? '0') === '1' ? '1' : '0';
    $edad_min = (string)(int)($opciones['edad_minima'] ?? 18);
    $edad_max = (string)(int)($opciones['edad_maxima'] ?? 80);

    $permite_efectivo = ($opciones['permite_efectivo'] ?? '1') === '1' ? '1' : '0';
    $permite_transferencia = ($opciones['permite_transferencia'] ?? '1') === '1' ? '1' : '0';

    // No se puede deshabilitar ambos métodos
    if ($permite_efectivo === '0' && $permite_transferencia === '0') {
        return ['exito' => false, 'error' => 'Debe permitirse al menos un método de pago'];
    }

    $cuotas_efectivo_max = _clipear_cuotas($opciones['cuotas_efectivo_max'] ?? '3');
    $cuotas_transferencia_max = _clipear_cuotas($opciones['cuotas_transferencia_max'] ?? '1');

    _actualizar_o_crear_campo($nodo_opciones, 'mostrar_ficha_medica', $mostrar_ficha);
    _actualizar_o_crear_campo($nodo_opciones, 'restriccion_edad', $restriccion);
    _actualizar_o_crear_campo($nodo_opciones, 'edad_minima', $edad_min);
    _actualizar_o_crear_campo($nodo_opciones, 'edad_maxima', $edad_max);
    _actualizar_o_crear_campo($nodo_opciones, 'permite_efectivo', $permite_efectivo);
    _actualizar_o_crear_campo($nodo_opciones, 'cuotas_efectivo_max', $cuotas_efectivo_max);
    _actualizar_o_crear_campo($nodo_opciones, 'permite_transferencia', $permite_transferencia);
    _actualizar_o_crear_campo($nodo_opciones, 'cuotas_transferencia_max', $cuotas_transferencia_max);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Helper para actualizar o crear un campo simple en un nodo contenedor.
 */
function _actualizar_o_crear_campo(Nodo $nodo_padre, string $nombre, string $valor): void {
    $nodo_campo = $nodo_padre->adyacente($nombre);
    if ($nodo_campo) {
        $nodo_campo->_dato($valor);
    } else {
        $nodo_padre->_adyacente_en(Nodo::crear_con_dato($valor), $nombre);
    }
}

/**
 * Clipea la cantidad de cuotas a un entero entre 1 y 12.
 * Devuelve un string para no romper la convención de datos.
 */
function _clipear_cuotas($valor): string {
    $n = (int)$valor;
    if ($n < 1) $n = 1;
    if ($n > 12) $n = 12;
    return (string)$n;
}