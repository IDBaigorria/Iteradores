<?php
namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\NodoElectrico;

/**
 * Interfaz que extiende Adyacentes con métodos para asignar adyacentes con peso.
 *
 * Combina la creación de un enlace adyacente y la asignación de un peso en un solo paso.
 * Estos métodos son implementados por la clase {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico}.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since 1.2.9
 */
interface AdyacenteConPeso extends Adyacentes
{
    /**
     * Asigna un adyacente con nombre único y le asigna un peso.
     *
     * Genera automáticamente un nombre de enlace único basado en el id del nodo destino,
     * luego asigna el peso en la dimensión especificada (o en la dimensión por defecto
     * si no se indica ninguna).
     *
     * @param NodoElectrico $un_nodo   Nodo que se desea enlazar.
     * @param mixed         $peso      Peso a asignar al nuevo enlace.
     * @param string|null   $dimension Dimensión del peso (null para la por defecto).
     * @return string|null Nombre del enlace generado, o null si hubo error.
     *
     * @see _adyacente()
     * @see _peso()
     * @since 1.2.9
     */
    public function _adyacente_con_peso(NodoElectrico $un_nodo, $peso, ?string $dimension = null): ?string;

    /**
     * Establece un nodo adyacente con nombre de enlace específico y le asigna un peso.
     *
     * Si el enlace ya existía y se permite reemplazar, el peso se asigna al nuevo enlace.
     *
     * @param NodoElectrico $un_nodo    Nodo a establecer como adyacente.
     * @param string        $enlace     Nombre del enlace.
     * @param mixed         $peso       Peso a asignar.
     * @param string|null   $dimension  Dimensión del peso (null para la por defecto).
     * @param bool          $reemplazar Si true, reemplaza un enlace existente.
     * @return bool True si se creó/reemplazó correctamente, false si hubo error.
     *
     * @see _adyacente_en()
     * @see _peso()
     * @since 1.2.9
     */
    public function _adyacente_con_peso_en(NodoElectrico $un_nodo, $enlace, $peso, ?string $dimension = null, bool $reemplazar = false): bool;
}