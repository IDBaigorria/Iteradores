<?php
namespace Iteradores\Nodos\Interfaces;
/**
 * Intefaz Fase
 * 
 * Esta interfaz es para la clase NodoElectrico y sus herederas. 
 * 
 * Proporciona los metodos necesarios para el manejo de la "fase"
 * en la que trabaja todo el sistema
 * 
 * 
 * @package Iteradores\Nodos\Interfaces
 * @since V1.2.3
 */
interface Fase
{
    // ================== ESTÁTICOS ==================

    /**
     * Establece la fase en la que van a trabajar todos los nodos.
     * Se necesita acceso autorizado por token.
     *
     * @param string $token Token de autorización
     * @param string $fase  Nombre de la nueva fase
     * @return void
     */
    public static function _fase(string $token, string $fase);

    /**
     * Devuelve la fase actual de trabajo (global).
     * El acceso autorizado es para establecer la fase, pero no para leerla,
     * por lo que no es necesario el token de seguridad.
     *
     * @return string
     */
    public static function fase(): string;

    /**
     * Ejecuta una función por cada fase registrada en el sistema (global).
     * Requiere token de seguridad.
     *
     * @param string   $token   Token de autorización
     * @param callable $funcion Función que recibe (string $fase) => void
     * @return void
     * @since V1.2.6
     */
    public static function por_cada_fase_global_ejecutar(string $token, callable $funcion);

    // ================== INSTANCIA ==================

    /**
     * Ejecuta una función por cada fase en la que el nodo tiene actividad.
     * Requiere token de seguridad.
     *
     * @param string   $token   Token de autorización
     * @param callable $funcion Función a ejecutar en cada fase (recibe el nombre de la fase)
     * @return void
     */
    public function por_cada_fase_ejecutar(string $token, callable $funcion);
}