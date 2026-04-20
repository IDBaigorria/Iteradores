<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;
/**
 * Interfaz que define el manejo de datos en un nodo.
 *
 * Permite establecer y recuperar un valor almacenado en el nodo.
 * Estos métodos son implementados por la clase {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.
 * @package Iteradores\Nodos\Interfaces
 * 
 */
interface Datos {

    /**
     * Asigna un dato al nodo.
     * 
     * Este método encapsula un dato pasado por parametro en un nodo de clase 
     * {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}, para ser accedido luego con 
     * {@link ./classes/Iteradores-Nodos-Interfaces-Datos.html#dato dato()}. 
     *
     * @param mixed $dato El valor a almacenar (puede ser cualquier tipo).
     * @return void
     *
     */
    public function _dato($dato);

    /**
     * Devuelve el dato almacenado en el nodo.
     * 
     * Este método retorna el valor previamente encapsulado mediante 
     * {@link ./classes/Iteradores-Nodos-Interfaces-Datos.html#_dato _dato()}. 
     * en una instancia de la clase
     * {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}. 
     * Si no existe ningún dato, devuelve null.
     * 
     * @return mixed|null El dato almacenado o null si no hay valor asignado.
     *
     */
    public function dato();
}

?>