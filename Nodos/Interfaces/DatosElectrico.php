<?php
namespace Iteradores\Nodos\Interfaces;

use Iteradores\Nodos\Interfaces\Datos;

/**
 * Interfaz que define el manejo de datos **multifase** y **multidimensional**
 * implementado por {@link \Iteradores\Nodos\NodoElectrico}.
 *
 * Extiende la interfaz {@link Datos} para reflejar el comportamiento real del
 * sistema eléctrico:
 * - Los datos se almacenan **en la fase activa** del sistema (obtenida internamente).
 * - Dentro de cada fase, los datos se organizan en **dimensiones con nombre**.
 * - Si no se especifica dimensión, se usa una clave vacía (`''`) como
 *   "dimensión por defecto", que corresponde al dato plano tradicional.
 *
 * Esta interfaz es una **extracción** del trabajo realizado en la versión 1.4.1.
 *
 * @package Iteradores\Nodos\Interfaces
 * @see \Iteradores\Nodos\NodoElectrico
 * @since 1.4.1
 */
interface DatosElectrico extends Datos
{
    /**
     * Asigna un dato en la **fase actual** y **dimensión** especificada.
     *
     * @param mixed       $valor     Valor a almacenar.
     * @param string|null $dimension Nombre de la dimensión.
     *                               Si es `null` se usa la dimensión por defecto (clave `''`).
     * @return void
     *
     * @example
     * $nodo->_dato("principal");               // dimensión por defecto
     * $nodo->_dato($matriz, 'abajo');          // dimensión 'abajo'
     */
    public function _dato($valor, ?string $dimension = null): void;

    /**
     * Recupera un dato de la **fase actual** y **dimensión** indicada.
     *
     * @param string|null $dimension Nombre de la dimensión.
     *                               Si es `null` se devuelve el dato de la dimensión por defecto.
     * @return mixed El valor almacenado, o `null` si no existe en la fase activa.
     *
     * @example
     * $valor = $nodo->dato();               // dimensión por defecto
     * $matriz = $nodo->dato('compuesta');   // dimensión 'compuesta'
     */
    public function dato(?string $dimension = null);
}