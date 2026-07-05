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
 * - Proveer una **matriz identidad** 2×2 asociada a cada fase de trabajo.
 * - Proveer el **p‑grama** (lista de factores primos) correspondiente a cada fase.
 * - Permitir consultar si el nodo es atómico (primo) o compuesto.
 *
 * ## Identidad multifase
 *
 * Tanto la matriz identidad como el p‑grama están indexados por fase. Un mismo
 * nodo puede tener distintas identidades en fases diferentes, reflejando
 * distintos niveles de abstracción o contextos de ejecución.
 *
 * @package Iteradores\Nodos\Interfaces
 * @version 1.4.4
 * @since 1.4.2
 * @see \Iteradores\Nodos\Matriz2x2
 * @see \Iteradores\Nodos\NodoNumerico
 */
interface IdentidadNumerica
{
    /**
     * Obtiene la matriz identidad del nodo en la fase indicada.
     *
     * Si no existe una matriz para la fase solicitada, se debe devolver
     * {@link \Iteradores\Nodos\Matriz2x2::inicial()}.
     *
     * @param string|null $fase Fase de trabajo (null = fase actual del sistema).
     * @return Matriz2x2
     */
    public function identidad(?string $fase = null): Matriz2x2;

    /**
     * Obtiene el p‑grama (lista de factores primos) del nodo en la fase indicada.
     *
     * Si no hay p‑grama en la fase solicitada, se debe devolver un array vacío.
     *
     * @param string|null $fase Fase de trabajo (null = fase actual del sistema).
     * @return int[] Lista de identificadores, o array vacío.
     */
    public function pgrama(?string $fase = null): array;

    /**
     * Indica si el nodo es un NodoPrimo (identidad atómica).
     *
     * @return bool `true` si el nodo es atómico, `false` si es compuesto.
     */
    public function es_primo(): bool;
}