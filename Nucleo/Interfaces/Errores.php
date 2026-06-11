<?php
namespace Iteradores\Nucleo\Interfaces;

/**
 * Define la interfaz para el manejo de errores dentro de una clase.
 *
 * Las clases que implementen esta interfaz deberán proporcionar mecanismos
 * para registrar, imprimir y obtener errores formateados.
 * 
 * Las clases que implementen esta interfaz deberán proporcionar mecanismos
 * para registrar y mostrar errores, adaptando el formato de salida al entorno
 * configurado en {@link Configuracion.Entorno}.
 * 
 * Ejemplo de uso:
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
 * 
 * @version 0.0.2 (1.3.1) Unificado el método de impresión.
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
     * Elimina todos los errores registrados en el sistema.
     *
     * Vacía por completo la pila de errores acumulados, permitiendo
     * comenzar un nuevo ciclo de diagnóstico sin interferencias
     * de mensajes anteriores.
     *
     * @return void
     * @since 1.3.1
     */
    public static function limpiar_errores(): void;

    /**
     * Imprime todos los errores registrados
     *
     * Este método muestra todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * La elección del formato de salida se basa en la configuración establecida en
     * {@link ./classes/Iteradores-Configuracion-Entorno.html Entorno}.
     * Si {@link ./classes/Iteradores-Configuracion-Entorno.html#method_es_consola Entorno::es_consola()}
     * retorna `true`, se delega en {@link _imprimir_errores_consola()}.
     * En caso contrario, se utiliza {@link _imprimir_errores_html()}.
     *
     * Para modificar el tipo de salida durante la ejecución, invoque
     * {@link ./classes/Iteradores-Configuracion-Entorno.html#method_establecer_tipo_salida Entorno::establecer_tipo_salida()}.
     * 
     * @return void
     */
    public static function imprimir_errores();


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