<?php
namespace Iteradores\Nucleo\Interfaces;

/**
 * Define la interfaz para el manejo de alertas dentro de una clase.
 *
 * Las clases que implementen esta interfaz deberán proporcionar mecanismos
 * para registrar y mostrar alertas, adaptando el formato de salida al entorno
 * configurado en {@link Configuracion.Entorno}.
 * 
 * Ejemplo de uso:
 * ```php
 * class Mi_clase extends Objeto{ //Objeto implementa la inteface Alertas
 * ... 
 *      function una_funcion(){
 *      ...
 *          If (...){//Se produjo un alerta
 *      		// ✅ Esto anda perfecto:
 *      		MiClase::_alerta("Alerta desde MiClase");
 *
 *      		// También podrías:
 *      		Objeto::_alerta("Alerta desde MiClase");
 * 
 *      		// Y también:
 *  	   		self::_alerta("Alerta desde MiClase");
 *      		return false;
 *          }
 *      ...
 *      }//fin una funcion
 *      ...
 * }//Fin clase 
 * ...
 * $mi_objeto= new Mi_clase();
 * ...
 * $mi_objeto->una_funcion() or die(Mi_clase::imprimir_alertas());
 * ```
 * 
 * @version 0.0.1 (1.3.0) Unificado el método de impresión.
 * 
 * @package Iteradores\Nucleo\Interfaces
 */
interface Alertas
{
    /**
     * Registra un nuevo alerta en el sistema de alertas.
     * 
     * Lo que hace es recibir un mensaje (un string) como parámetro que de sierta información 
	 * que pueda ser necesaria al programador para que hubique rapidamente el el alerta que se produjo y 
	 * poder corregirlo. Cuando el mensaje es enviado la funcion lo agrega a una lista o pila de mensajes 
	 * de alerta. Para poder observar los mensajes de alerta existen otra funciones llamadas 
     * imprimir_alertas(),  HTML_alertas() o json_alertas()
     * @param string $alerta Mensaje de alerta a registrar.
     * @return void
     */
    public static function _alerta($alerta);

    /**
     * Imprime todas las alertas registradas.
     *
     * Este método muestra todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html#method__alerta _alerta()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * La elección del formato de salida se basa en la configuración establecida en
     * {@link Configuracion.Entorno Entorno}.
     * Si {@link Configuracion.Entorno.es_consola Entorno.es_consola()}
     * retorna `true`, se delega en {@link #_imprimir_alertas_consola}.
     * En caso contrario, se utiliza {@link #_imprimir_alertas_html}.
     *
     * Para modificar el tipo de salida durante la ejecución, invoque
     * {@link Configuracion.Entorno.establecer_tipo_salida Entorno.establecer_tipo_salida()}.
     * 
     * @return void
     */
    public static function imprimir_alertas();

    /**
     * Devuelve un string HTML con la lista de alertas registrados.
     *
     * Este método devuelve todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html#method__alerta _alerta()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * A diferencia de otros métodos de salida, este no imprime directamente
     * los alertas, sino que devuelve el HTML para que pueda insertarse en
     * una página web y ser mostrado de forma legible.
     * @return string HTML con la lista de alertas registrados.
     */
    public static function html_alertas();

     /**
     * Devuelve un string JSON con la lista de alertas registrados.
     *
     * Este método devuelve todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html#method__alerta _alerta()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas. 
     * 
     * A diferencia de otros métodos de salida, este no imprime directamente
     * los alertas, sino que los devuelve como un string JSON, 
     * facilitando su transporte o almacenamiento.
     * @return string HTML con la lista de alertas registrados.
     */
    public static function json_alertas(); 

    /**
     * Habilita la recolección de alertas en el sistema.
     *
     * Permite que los alertas registrados mediante _alerta() sean almacenados
     * en la lista centralizada para su posterior consulta y visualización.
     *
     * @return void
     */
    public static function activar_alertas();

    /**
     * Desactiva la recolección de alertas en el sistema.
     *
     * Los alertas registrados mientras el sistema esté desactivado
     * no se almacenarán ni se mostrarán en la lista centralizada.
     *
     * @return void
     */
    public static function desactivar_alertas();
}
	
?>