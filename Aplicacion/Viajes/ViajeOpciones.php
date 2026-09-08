<?php
/**
 * Funciones para opciones avanzadas de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.12
 * @version   1.5piloto.26
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
 */
function obtener_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje): array {
    $defaults = [
        'mostrar_ficha_medica' => '0',
        'restriccion_edad' => '0',
        'edad_minima' => '18',
        'edad_maxima' => '80',
    ];

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return $defaults;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return $defaults;

    $nodo_opciones = $nodo_viaje->adyacente('opciones_avanzadas');
    if (!$nodo_opciones) return $defaults;

    $opciones = $defaults;
    $campo = $nodo_opciones->adyacente('mostrar_ficha_medica');
    if ($campo) $opciones['mostrar_ficha_medica'] = $campo->dato();

    $campo = $nodo_opciones->adyacente('restriccion_edad');
    if ($campo) $opciones['restriccion_edad'] = $campo->dato();

    $campo = $nodo_opciones->adyacente('edad_minima');
    if ($campo) $opciones['edad_minima'] = $campo->dato();

    $campo = $nodo_opciones->adyacente('edad_maxima');
    if ($campo) $opciones['edad_maxima'] = $campo->dato();

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

    $mostrar_ficha = ($opciones['mostrar_ficha_medica'] ?? '0') === '1' ? '1' : '0';
    $restriccion = ($opciones['restriccion_edad'] ?? '0') === '1' ? '1' : '0';
    $edad_min = (string)($opciones['edad_minima'] ?? '18');
    $edad_max = (string)($opciones['edad_maxima'] ?? '80');

    _actualizar_o_crear_campo($nodo_opciones, 'mostrar_ficha_medica', $mostrar_ficha);
    _actualizar_o_crear_campo($nodo_opciones, 'restriccion_edad', $restriccion);
    _actualizar_o_crear_campo($nodo_opciones, 'edad_minima', $edad_min);
    _actualizar_o_crear_campo($nodo_opciones, 'edad_maxima', $edad_max);

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