<?php
namespace Iteradores\Nodos\Interfaces;
/**
 * Interfaz de impresión de un nodo.
 *
 * Define un único método `imprimir()` que se adapta automáticamente al tipo de
 * salida configurado en {@link Configuracion.Entorno Entorno} (HTML o consola).
 *
 * @package Iteradores\Nodos\Interfaces
 * @since V3.2.5
 */
interface Impresion
{
    /**
     * Imprime el nodo en el formato adecuado según el tipo de salida del entorno.
     * 
	 * **Restricción de entorno:** solo se ejecuta en desarrollo o pruebas.
	 * En producción, emite una alerta y no genera salida, ya que este método está pensado
	 * exclusivamente para depuración.
     *
     * Si `Entorno::es_consola()` → salida texto plano (CLI).
     * Si `Entorno::es_html()` → bloque HTML con datos de la fase actual.
     * 
     * @return void
     * @since 1.3.0 Unificado con entorno; desaparece imprimir2.
     */
    public function imprimir(): void;

    /**
     * Imprime todos los nodos de la superestructura.
     * 
     * Debe recorrer todos los nodos existentes en la superestructura y mostrarlos en formato HTML,
     * invocando el método `imprimir()` de cada nodo.  
     * Este método está destinado exclusivamente a tareas de depuración.
     *
     * @return bool Devuelve `true` si existen nodos y se imprimen correctamente, `false` en caso contrario.
     */
 //   static public function imprimir_superestructura();

    /**
     * Imprime el nodo en formato texto plano.
     *
     * Genera una salida legible en consola con la información básica del nodo y sus adyacencias.
     *
     * @return bool Devuelve `true` si se imprimió correctamente.
     */
 //   public function imprimir2();
    /**
     * Imprime todos los nodos de la superestructura en formato de texto.
     * 
     * Recorre todos los nodos existentes y ejecuta `imprimir2()` en cada uno de ellos,
     * mostrando la salida en consola o texto plano.  
     * Está destinada exclusivamente a depuración.
     *
     * @return bool Devuelve `true` si se imprimieron nodos, `false` si no existen.
     * @since V3.2.5
     */
  //  static public function imprimir_superestructura2();

}