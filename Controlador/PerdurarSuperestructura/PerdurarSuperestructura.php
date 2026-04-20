<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
/**
 * Interfaz que define los métodos principales para la persistencia
 * de la superestructura en distintos formatos o medios.
 *
 * Cada implementación concreta (SQL, JSON, Texto) debe proveer su
 * propia lógica para estos métodos.
 *
 * @interface PerdurarSuperestructura
 * @since V3.3
 */
interface PerdurarSuperestructura {

    /** @return bool */
    public static function guardar($nombre);

    /** @return bool */
    public static function cargar($nombre);

    /** @return bool */
    public static function eliminar($nombre);

    /** @return bool */
    public static function existe($nombre);

}
?>
