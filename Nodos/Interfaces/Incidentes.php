<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;
/**
 * Interfaz que define el manejo de Adyacentes en un nodo.
 *
 *
 * @package Iteradores\Nodos\Interfaces
 * @since 
 */
interface Incidentes {
    /**
	 * Verifica si el nodo es adyacente de al menos un nodo.
	 *
	 * Evalúa si no existe al menos otro nodo que lo tenga él como adyacente. O dicho de 
	 * otro modo, si no tiene conexiones "entrantes"; en tal caso se concidera "suelto"
	 * y devuelve true; caso contrario devuelve false.
	 * 
	 * Si el nodo está autoenlazado, es decir tiene algun enlace que sale de él hacia él
	 * mismo ya no se concidera "suelto" y devuelve false. 
	 * 
	 * ⚠️ Importante: verifica las conexiciones de "entrada", pero no las de "salida".
	 * Para verificar las conexiones de "salida" utilice 
	 * {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_adyacente tiene_adyacente}
     * 
 	 * @return bool Devuelve **true** si el nodo está considerado suelto, o **false** en caso contrario.
     * @deprecated usar {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_adyacente tiene_adyacente}
     * @public
     * @sice 2.9
     * @deprecated usar {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html#tiene_incidente tiene_incidente}
     */

    public function es_nodo_suelto();

    /**
	 * Verifica si el nodo es adyacente de al menos un nodo.
	 *
	 * Evalúa si no existe al menos otro nodo que lo tenga él como adyacente. O dicho de 
	 * otro modo, si no tiene conexiones "entrantes"; en tal caso se concidera "suelto"
	 * y devuelve true; caso contrario devuelve false.
	 * 
	 * Si el nodo está autoenlazado, es decir tiene algun enlace que sale de él hacia él
	 * mismo ya no se concidera "suelto" y devuelve false. 
	 * 
	 * ⚠️ Importante: verifica las conexiciones de "entrada", pero no las de "salida".
	 * Para verificar las conexiones de "salida" utilice 
	 * {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_adyacente tiene_adyacente}
     * 
	 * @return bool Devuelve **true** si el nodo está considerado suelto, o **false** en caso contrario.
     * @public
     * @since 3.2.3
     */

    public function tiene_incidente();

     /**
     * Verifica si el nodo actual es adyacente del nodo indicado.
     *
     * Indica si el nodo actual está enlazado desde el nodo pasado como parámetro.  
     * Devuelve el nombre del enlace en caso de existir, o `false` en caso contrario.
     *
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_adyacente_a tiene_adyacente_a()}
     * 
     * @param Nodo $nodo Nodo a verificar.
     * @return string|false Nombre del enlace si existe, `false` en caso contrario.    
     * @public
     * @since 3.2.3
     * 
     */
    public function tiene_incidente_a($nodo);


    /**
     * Devuelve la cantidad de incidentes.
     *
     * Retorna el número de nodos incidentes o con conexiones entrantes al nodo.  
     * Si no existen incidentes, devuelve 0.
     *
     * @param
     * @return int Número de incidentes actuales
     * @public
     * @since 3.2.3
     */
    public function cantidad_de_incidentes(): int;

    
}
?>