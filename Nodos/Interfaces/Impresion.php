<?php
namespace Iteradores\Nodos\Interfaces;
interface Impresion {
    /**
     * Imprime el nodo en formato HTML.
     *
     * Genera una representación visual del nodo en HTML con su id, dato y adyacencias.
     * Usado para depuración en entorno web.
     *
     * @return void
     */
    public function imprimir();
    /**
     * Imprime todos los nodos de la superestructura.
     * 
     * Debe recorrer todos los nodos existentes en la superestructura y mostrarlos en formato HTML,
     * invocando el método `imprimir()` de cada nodo.  
     * Este método está destinado exclusivamente a tareas de depuración.
     *
     * @return bool Devuelve `true` si existen nodos y se imprimen correctamente, `false` en caso contrario.
     */
    static public function imprimir_superestructura();

    /**
     * Imprime el nodo en formato texto plano.
     *
     * Genera una salida legible en consola con la información básica del nodo y sus adyacencias.
     *
     * @return bool Devuelve `true` si se imprimió correctamente.
     */
    public function imprimir2();
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
    static public function imprimir_superestructura2();

}