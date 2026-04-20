<?php
namespace Iteradores\Nucleo\Interfaces;

/**
 * Interfaz Id
 *
 * Proporciona un contrato para el manejo de identificadores únicos
 * en objetos. Permite obtener, asignar y verificar la validez de un ID.
 *
 * @package Iteradores\Nucleo\Interfaces
 */
interface Id
{
    /**
     * Determina si un identificador es especial.
     *
     * @param string $id El id a comprobar.
     * @return bool `true` si el id es especial, `false` en caso contrario.
     */
    public static function es_id_especial(string $id): bool;
    /**
     * Devuelve el identificador único del objeto.
     *
     * Si el objeto aún no tiene un id, se le asigna uno nuevo automáticamente,
     * asegurando que sea único dentro del sistema.
     *
     * @return string El id único del objeto.
     */
    public function id(): string;
    /**
     * Asigna un identificador único al objeto.
     *
     * El id proporcionado debe ser especial (debe poder pasar positivamente la 
   	 * verificacion realizada por {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_id_especial es_id_especial(id)})
     * y no estar repetido en otros objetos.
     *
     * @param string $id El id a asignar.
     * @return bool `true` si el id fue asignado exitosamente, `false` en caso contrario.
     */
    public function _id(string $id): bool;

    /**
     * Asigna directamente un identificador interno al objeto sin validaciones.
     *
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()}, 
	 * esta versión **no verifica** si el objeto ya posee id ni si el id es especial.
	 * Se debe usar **exclusivamente** en clases que heredan de esta, y **bajo responsabilidad del programador**,
	 * asegurando que:
	 * - El id no haya sido previamente asignado.
	 * - El id sea válido y único.
	 *
	 * Está pensada para contextos donde el control ya se realiza externamente,
	 * permitiendo ahorrar CPU y memoria al omitir verificaciones redundantes.
     *
     * @param string $id_interno Identificador interno a asignar.
     * @return void
     */
    //protected function _id_interno(string $id): bool;

    /**
     * Comprueba si el objeto actual posee un id especial.
     *
     * Se considera **especial** cuando el id del objeto puede pasar positivamente la 
   	 * verificacion realizada por {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_id_especial es_id_especial(id)})
     * de manera de poder distinguirlo de otros objetos **comunes**.
     * Si el objeto aún no tiene ningún id, este método le asignará uno común automáticamente.
     *
     * @return bool `true` si el objeto tiene un id especial, `false` en caso contrario.
     */
    public function es_especial(): bool;
}