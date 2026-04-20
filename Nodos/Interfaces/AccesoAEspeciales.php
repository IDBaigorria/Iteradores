<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;

/**
 * Interfaz que define el acceso a los nodos especiales.
 *
 * Define los métodos que permiten interactuar con el conjunto de nodos especiales.
 * Los nodos especiales forman una colección separada de la superestructura.
 * 
 * Estos métodos son implementados por la clase {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since V3.2.4
 */
interface AccesoAEspeciales {
    /**
     * Indica si existen nodos especiales registrados.
     *
     * @return bool `true` si hay nodos especiales, `false` en caso contrario.
     */
    public static function hay_nodos_especiales(): bool;

    /**
     * Ejecuta una función sobre cada nodo especial.
     *
     * @param callable $funcion Función a ejecutar.
     * @param mixed $parametro1 Parámetro opcional.
     * @param mixed $parametro2 Parámetro opcional.
     * @return array|null Resultados devueltos por la función.
     */
    public static function por_cada_nodo_especial_ejecutar(callable $funcion, mixed ...$parametros): ?array;
}