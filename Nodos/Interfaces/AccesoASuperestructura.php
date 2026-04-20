<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;
/**
 * Interfaz que define el acceso a los nodos de la superestructura.
 *
 * Define los métodos que permiten interactuar con la superestructura global de nodos.
 * La superestructura es un contenedor estático que mantiene referencias a todos los nodos existentes.
 * 
 * Estos métodos son implementados por la clase {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since V3.2.4
 */
interface AccesoASuperestructura {
    /**
     * Comprueba si existen nodos en la superestructura.
     *
     * Determina si la superestructura global de nodos ha sido inicializada y contiene al menos un nodo.
     * Si la superestructura aún no fue creada o su lista de adyacentes está vacía, devuelve `false`.
     *
     * @return bool `true` si hay nodos cargados en la superestructura, `false` en caso contrario.
     */
    public static function hay_nodos_en_superestructura(): bool;

    /**
     * Obtiene un nodo a partir de su identificador único.
     *
     * @param string $id Identificador del nodo.
     * @return mixed Nodo encontrado o null si no existe.
     */
    public static function nodo_por_id(string $id);

    /**
     * Ejecuta una función sobre cada nodo especial.
     *
     * Permite recorrer todos los nodos marcados como especiales y
     * aplicar una función personalizada sobre cada uno de ellos.
     *
     * @param callable $funcion Función que se ejecutará sobre cada nodo.
     * @param mixed ...$parametros Parámetros adicionales.
     * @return ?array Resultados obtenidos o `null` si no hay nodos especiales.
     */
    public static function por_cada_nodo_ejecutar(string $token, callable $funcion, mixed ...$parametros): ?array ;
    
    /**
     * Vacía la superestructura actual, eliminando todos los nodos y enlaces.
     *
     * @param string $token Token de autenticación que valida la autorización
     *                      para vaciar la estructura.
     *
     * @return bool|null Devuelve `true` si la operación fue exitosa,
     *                   o `null` si se denegó por token inválido.
     *
     * @contract
     * - El método debe eliminar todos los nodos y enlaces de la estructura actual.
     * - Debe restablecer los contadores y colecciones internas a su estado inicial.
     * - No debe modificar el estado si la autenticación falla.
     */
    public static function vaciar_superestructura(string $token): ?bool;
}