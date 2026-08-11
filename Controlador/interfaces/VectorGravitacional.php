<?php
namespace Iteradores\Controlador\interfaces;

/**
 * Contrato para componentes que proporcionan vectores temporales.
 *
 * Un proveedor de vector temporal es cualquier clase que dispone de una
 * instancia de {@link \Iteradores\Tiempo\RelojAstronomico} y la utiliza
 * para devolver el vector gravitacional asociado al instante actual (o a un
 * timestamp arbitrario) y a la ubicación configurada.
 *
 * Además, permite actualizar la ubicación del reloj interno de forma dinámica,
 * por ejemplo al recibir notificaciones de cambio de posición desde
 * {@link \Iteradores\Configuracion\Entorno::escuchar_cambios()}.
 *
 * ## Rol en el sistema
 *
 * Esta interfaz la implementa el {@link \Iteradores\Controlador\Controlador}
 * para que los iteradores y otros componentes puedan obtener la huella temporal
 * del ciclo en curso sin preocuparse de los detalles de obtención de la
 * ubicación ni del modelo astronómico.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Controlador\interfaces
 * @since 1.3.6
 */
interface VectorGravitacional
{
    /**
     * Devuelve el vector gravitacional correspondiente al instante dado.
     *
     * @param int|null $timestamp Marca de tiempo Unix. Si es `null`, se usa
     *                             el instante actual (`time()`).
     * @return array{x: float, y: float, z: float}|null Vector unitario,
     *                     o `null` si el reloj astronómico aún no se ha
     *                     inicializado.
     */
    public static function vector_gravitacional_actual(?int $timestamp = null): ?array;

    /**
     * Actualiza la ubicación geográfica del reloj astronómico interno.
     *
     * Invalida cualquier caché temporal para que la siguiente llamada a
     * {@link vector_gravitacional_actual} utilice las nuevas coordenadas.
     *
     * @param float $latitud  Nueva latitud en grados (-90 a 90).
     * @param float $longitud Nueva longitud en grados (-180 a 180).
     * @return void
     */
    public static function _actualizar_ubicacion(float $latitud, float $longitud): void;
}