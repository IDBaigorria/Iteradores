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
interface Fase{

    //ESTATICOS/////////////////////////////////////////////////////////////
    /**
     * Establece la fase en la que van a trabajar todos los nodos
     * 
     * Se necesita acceso autorizado por token
     * @param string $token autorizacion
     * @param string $fase nombre de la fase
     * @return void
     */
    public static function establecer_fase(string $token, string $fase);

    //INSTANCIA////////////////////////////////////////////////////////////////////
    /**
     * Ejecuta una función por cada fase registrada en el nodo (Interfaz Fase).
     *
     *
     * Permite iterar sobre todas las fases registradas en el nodo ejecutando una función
     * callback en cada una. Requiere token de seguridad por ser una operación sensible.
     * 
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-Fase.html#method_establecer_fase establecer_fase}
     *
     *
     * @note Requiere token de seguridad válido.
     * @param string token Token de autorización
     * @param callable $funcion Función a ejecutar en cada fase
     * @return void
     * @public
     * @since 1.2.0
     */
    public function por_cada_fase_ejecutar(string $token, callable $funcion);
}
?>