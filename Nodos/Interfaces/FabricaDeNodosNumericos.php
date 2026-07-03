<?php
namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;
use Iteradores\Nodos\NodoConjunto;
use Iteradores\Configuracion\Conf;

/**
 * Interfaz que define las fábricas centralizadas de nodos numéricos.
 *
 * Extiende {@link FabricaDeNodosElectricos} y es implementada por
 * {@link NodoNumerico}, que actúa como orquestador de sus tres subclases:
 * - {@link NodoPrimo}
 * - {@link NodoParalelo}
 * - {@link NodoConjunto}
 *
 * Todas las creaciones pasan por estos métodos para mantener la coherencia
 * del índice global de identidades y del diccionario de conceptos.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since 1.4.2
 */
interface FabricaDeNodosNumericos extends FabricaDeNodosElectricos
{
    /**
     * Crea un nodo numérico con una identidad no prima.
     *
     * @param Matriz2x2 $identidad Identidad matricial (determinante no primo).
     * @param int $capacidad Capacidad máxima de energía.
     * @param float $fuga Fuga de energía por ciclo.
     * @return NodoNumerico|null
     */
  /*  public static function crear_numerico(
        Matriz2x2 $identidad,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoNumerico;*/

    /**
     * Crea (o recupera) un nodo primo con el número primo indicado.
     *
     * @param int $primo Número primo (ej. 2, 3, 5...).
     * @param int $capacidad
     * @param float $fuga
     * @return NodoPrimo|null
     */
   /* public static function crear_primo(
        int $primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoPrimo;*/

    /**
     * Crea un nodo de sincronización con los componentes dados.
     *
     * @param NodoNumerico[] $componentes Array de nodos (cantidad prima).
     * @param int $capacidad
     * @param float $fuga
     * @return NodoParalelo|null
     */
  /* public static function crear_paralelo(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo;*/

    /**
     * Crea un nuevo concepto semántico (sin nombre).
     *
     * @param int $capacidad
     * @param float $fuga
     * @return NodoConjunto
     */
    /*public static function crear_conjunto(
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoConjunto;
*/
    /**
     * Recupera un nodo del índice global por su identidad.
     *
     * @param Matriz2x2 $identidad
     * @return NodoNumerico|null
     */
 //   public static function nodo_por_identidad(Matriz2x2 $identidad): ?NodoNumerico;
}