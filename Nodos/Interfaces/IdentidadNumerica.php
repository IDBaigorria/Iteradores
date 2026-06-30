<?php

namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\Matriz2x2;

/**
 * Interfaz IdentidadNumerica.
 *
 * Define el contrato para cualquier nodo que posea una **identidad
 * matricial 2×2** y que pueda indicar si su estructura es ordenada
 * (secuencia) o no (conjunto / paralelo).
 *
 * ## El Entrelazamiento Contextual (Conexión Cuántica)
 *
 * La entrada `b` de la matriz identidad actúa como un **canvas de
 * pertenencia**. Un {@link NodoConjunto} (con identidad negativa) "pinta"
 * a sus miembros multiplicando su `b` por un número primo único. Esto
 * crea un **vínculo algebraico bidireccional**:
 *
 * - **Del conjunto al miembro:** La matriz del miembro es alterada,
 *   codificando su pertenencia.
 * - **Del miembro al conjunto:** El conjunto mantiene enlaces de vuelta
 *   con pesos multidimensionales, indicando el *grado* de pertenencia.
 *
 * Esta "pintura" es la **conexión cuántica** que buscábamos: una
 * modificación directa de la identidad que entrelaza dos nodos sin
 * requerir una base de datos externa. La pertenencia se puede verificar
 * con una simple operación O(1): `$b % $primoConjunto == 0`.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since 1.4.2
 * @see Matriz2x2
 * @see NodoNumerico
 * @see NodoConjunto
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
     * Indica si el nodo representa una secuencia ordenada.
     *
     * @return bool
     */
    public function ordenado(): bool;
}