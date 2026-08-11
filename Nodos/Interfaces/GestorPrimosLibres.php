<?php

namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\NodoPrimo;

/**
 * Interfaz GestorPrimosLibres – Contrato para la gestión del pool de primos libres.
 *
 * Define los métodos estáticos que permiten administrar el conjunto de
 * {@link \Iteradores\Nodos\NodoPrimo} disponibles en cada fase. Estos nodos
 * se utilizan como representantes atómicos de acciones compuestas cuando
 * ascienden de fase.
 *
 * Es implementada por {@link \Iteradores\Nodos\NodoPrimo}.
 *
 * ## Funcionamiento del pool
 *
 * - Cada fase tiene su propio pool de primos libres (instancias reutilizables).
 * - Se puede establecer un límite máximo de primos por fase con {@link inicializar_fase}.
 * - {@link siguiente_primo_libre} extrae un nodo del pool o crea uno nuevo si no se ha alcanzado el límite.
 * - {@link devolver_primo_libre} retorna un nodo al pool para su futura reutilización.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Nodos\Interfaces
 * @version 1.4.4
 * @since 1.4.4
 * @see \Iteradores\Nodos\NodoPrimo
 */
interface GestorPrimosLibres
{
    /**
     * Inicializa la reserva de primos libres para una fase determinada.
     *
     * Establece el límite máximo de primos que se pueden generar en esa fase
     * y asegura que el pool exista (aunque esté vacío).
     *
     * @param string $fase   Nombre de la fase.
     * @param int    $limite Cantidad máxima de primos libres en la fase.
     * @return void
     */
    public static function inicializar_fase(string $fase, int $limite): void;

    /**
     * Devuelve el siguiente NodoPrimo libre en la fase indicada.
     *
     * El algoritmo de selección:
     * 1. Si hay nodos en el pool, extrae y retorna el primero (FIFO).
     * 2. Si el pool está vacío y no se alcanzó el límite, crea uno nuevo
     *    usando el siguiente primo positivo de la fase.
     * 3. Si se alcanzó el límite, retorna `null`.
     *
     * @param string|null $fase Fase de trabajo (null = fase actual).
     * @return NodoPrimo|null Un NodoPrimo listo para usar, o null si se agotó el límite.
     */
    public static function siguiente_primo_libre(?string $fase = null): ?NodoPrimo;

    /**
     * Devuelve un NodoPrimo al pool de libres de una fase.
     *
     * Tras invocar este método, el nodo podrá ser reclamado nuevamente por
     * {@link siguiente_primo_libre} en la misma fase.
     *
     * @param NodoPrimo   $nodo Nodo a devolver al pool.
     * @param string|null $fase Fase a la que se devuelve (null = fase actual).
     * @return void
     */
    public static function devolver_primo_libre(NodoPrimo $nodo, ?string $fase = null): void;
}