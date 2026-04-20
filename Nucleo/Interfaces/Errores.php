<?php
namespace Iteradores\Nucleo\Interfaces;

/**
 * Define la interfaz para el manejo de errores dentro de una clase.
 *
 * Las clases que implementen esta interfaz deberán proporcionar mecanismos
 * para registrar, imprimir y obtener errores formateados.
 *  * Ejemplo de uso:
 * ```php
 * class Mi_clase extends Objeto{ //Objeto implementa la inteface Errores
 * ... 
 *      function una_funcion(){
 *      ...
 *          If (...){//Se produjo un error
 *      		// ✅ Esto anda perfecto:
 *      		MiClase::_error("Error desde MiClase");
 *
 *      		// También podrías:
 *      		Objeto::_error("Error desde MiClase");
 * 
 *      		// Y también:
 *  	   		self::_error("Error desde MiClase");
 *      		return false;
 *          }
 *      ...
 *      }//fin una funcion
 *      ...
 * }//Fin clase 
 * ...
 * $mi_objeto= new Mi_clase();
 * ...
 * $mi_objeto->una_funcion() or die(Mi_clase::imprimir_errores());
 * ```
 * @example 
 * 
 * 
 * @package Iteradores\Nucleo\Interfaces
 */
interface Errores
{
    /**
     * Registra un nuevo error en el sistema de errores.
     * 
     * Lo que hace es recibir un mensaje (un string) como parámetro que de sierta información 
	 * que pueda ser necesaria al programador para que hubique rapidamente el el error que se produjo y 
	 * poder corregirlo. Cuando el mensaje es enviado la funcion lo agrega a una lista o pila de mensajes 
	 * de error. Para poder observar los mensajes de error existen otra funciones llamadas 
     * imprimir_errores(), imprimir_errores_conosola() y HTML_errores()
     * @param string $error Mensaje de error a registrar.
     * @return void
     */
    public static function _error($error);

    /**
     * Imprime en HTML la lista de errores registrados.
     *
     * Este método muestra todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * @return void
     */
    public static function imprimir_errores();

    /**
     * Imprime en consola la lista de errores registrados.
     *
     * Este método muestra todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * A diferencia de {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method_imprimir_errores imprimir_errores()},
     * este método está pensado para mostrar los errores directamente en la
     * consola del entorno de desarrollo (CLI o navegador con consola activa)
     * en un formato más claro y legible.
     * @return void
     */
    public static function imprimir_errores_consola();

    /**
     * Devuelve un string HTML con la lista de errores registrados.
     *
     * Este método devuelve todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * A diferencia de otros métodos de salida, este no imprime directamente
     * los errores, sino que devuelve el HTML para que pueda insertarse en
     * una página web y ser mostrado de forma legible.
     * @return string HTML con la lista de errores registrados.
     */
    public static function html_errores();

     /**
     * Devuelve un string JSON con la lista de errores registrados.
     *
     * Este método devuelve todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * A diferencia de otros métodos de salida, este no imprime directamente
     * los errores, sino que los devuelve como un string JSON, 
     * facilitando su transporte o almacenamiento.
     * @return string HTML con la lista de errores registrados.
     */
    public static function json_errores(); 

    /**
     * Habilita la recolección de errores en el sistema.
     *
     * Permite que los errores registrados mediante _error() sean almacenados
     * en la lista centralizada para su posterior consulta y visualización.
     *
     * @return void
     */
    public static function activar_errores();

    /**
     * Desactiva la recolección de errores en el sistema.
     *
     * Los errores registrados mientras el sistema esté desactivado
     * no se almacenarán ni se mostrarán en la lista centralizada.
     *
     * @return void
     */
    public static function desactivar_errores();
}