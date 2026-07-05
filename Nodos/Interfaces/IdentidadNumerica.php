<?php

namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\Matriz2x2;

/**
 * Interfaz IdentidadNumerica – Contrato para nodos con identidad matricial y p‑grama.
 *
 * Define los métodos que debe implementar cualquier clase que actúe como
 * un nodo con identidad numérica dentro del sistema de fases. En la práctica,
 * la implementan {@link \Iteradores\Nodos\NodoNumerico} y todas sus subclases
 * ({@link \Iteradores\Nodos\NodoPrimo}, {@link \Iteradores\Nodos\NodoParalelo}).
 *
 * ## Responsabilidades
 *
 * - Proveer una **matriz identidad** 2×2 única e inmutable para el nodo.
 * - Proveer el **p‑grama** (lista de factores primos) único del nodo.
 * - Permitir consultar si el nodo es atómico (primo) o compuesto.
 *
 * ## Identidad única
 *
 * A partir de la versión 1.4.5, la matriz identidad y el p‑grama son propiedades
 * únicas del nodo, independientes de la fase. Esto simplifica el modelo y refleja
 * que la identidad numérica es intrínseca, no contextual.
 *
 * @package Iteradores\Nodos\Interfaces
 * @version 1.4.5
 * @since 1.4.2
 * @see \Iteradores\Nodos\Matriz2x2
 * @see \Iteradores\Nodos\NodoNumerico
 */
interface IdentidadNumerica
{
    /**
     * Obtiene la matriz identidad del nodo.
     *
     * @return Matriz2x2
     */
    public function identidad(): Matriz2x2;

    /**
     * Obtiene el p‑grama (lista de factores primos) del nodo.
     *
     * Si el nodo no tiene p‑grama, se debe devolver un array vacío.
     *
     * @return int[] Lista de identificadores, o array vacío.
     */
    public function pgrama(): array;

    /**
     * Indica si el nodo es un NodoPrimo (identidad atómica).
     *
     * @return bool `true` si el nodo es atómico, `false` si es compuesto.
     */
    public function es_primo(): bool;
}