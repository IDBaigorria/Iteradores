<?php

namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;
use Iteradores\Configuracion\Conf;

/**
 * Interfaz FabricaDeNodosNumericos – Contrato para la creación de nodos numéricos.
 *
 * Centraliza las fábricas estáticas que permiten construir los distintos tipos
 * de nodos con identidad matricial: primos, secuencias compuestas y paralelos.
 *
 * Es implementada por {@link \Iteradores\Nodos\NodoNumerico}, que actúa como
 * orquestador de todas las creaciones, garantizando la coherencia del pool de
 * nodos libres y de los p‑gramas multifase.
 *
 * ## Responsabilidades
 *
 * - Crear nodos primos (comandos atómicos, positivos o negativos).
 * - Crear nodos compuestos (secuencias ordenadas de p‑grama).
 * - Crear nodos de sincronización (paralelos).
 *
 * @package Iteradores\Nodos\Interfaces
 * @version 1.4.4
 * @since 1.4.2
 * @see \Iteradores\Nodos\NodoNumerico
 * @see \Iteradores\Nodos\NodoPrimo
 * @see \Iteradores\Nodos\NodoParalelo
 */
interface FabricaDeNodosNumericos
{
    /**
     * Crea un nodo primo con el número primo indicado.
     *
     * @param int   $primo      Número primo (positivo para comando constructivo,
     *                          negativo para destructivo).
     * @param int   $capacidad  Capacidad máxima de energía.
     * @param float $fuga       Fuga de energía por ciclo.
     * @return NodoPrimo|null   El NodoPrimo creado, o null si el valor absoluto no es primo.
     */
    public static function crear_primo(
        int $primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoPrimo;

    /**
     * Crea un nodo numérico compuesto (secuencia ordenada de p‑grama).
     *
     * @param NodoNumerico[] $componentes Componentes de la secuencia (cantidad prima).
     * @param int            $capacidad   Capacidad máxima de energía.
     * @param float          $fuga        Fuga de energía por ciclo.
     * @return NodoNumerico|null El nuevo nodo, o null si la cantidad no es prima.
     */
    public static function crear_numerico(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoNumerico;

    /**
     * Crea un nodo de sincronización (paralelo) con los componentes dados.
     *
     * @param NodoNumerico[] $componentes Componentes (cantidad prima).
     * @param int            $capacidad
     * @param float          $fuga
     * @return NodoParalelo|null
     */
    public static function crear_paralelo(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo;
}