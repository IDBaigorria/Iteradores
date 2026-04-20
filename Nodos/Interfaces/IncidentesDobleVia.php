<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Interfaces\Incidentes;
include_once("Incidentes.php");
/**
 * Interfaz que define el manejo de Incidentes cuando se usan enlaces 
 * de ida y vuelta.
 *
 * Se debe disponer de una estructura separada a adyacentes en cada nodo para poder
 * implementar esta intarfaz. una forma de registrar los nodos incidentes y poder volver
 * en algun momento. Una linda norma seria que seimpre que se use un enlace de ida se use
 * luego en enlace de vuelta cerrando un ciclo y dando finalizado el recorrido
 * @extends Incidentes
 * @package Iteradores\Nodos\Interfaces
 * @since 
 */
interface IncidentesDobleVia extends Incidentes{


   /**
     * Asigna un adyacente con nombre único.
     *
     * Agrega un nodo como adyacente generando automáticamente un nombre de enlace único
     * basado en el `id()` del nodo destino.  
     * Si ya existe un enlace con ese nombre, se crean variantes incrementales (`id.1`, `id.2`, ...).
     *
     * @param Nodo $un_nodo Nodo que se desea enlazar
     * @return null|string Nodo adyacente recién asignado
     * @public
     */
   // public function _incidente($nodo) :?string; no por ahora no, porque no tiene sentido ya que el incidente
   //es la contraparte del adyacente y no puede existir por si solo

    /**
     * Asigna un nodo incidente en un enlace específico.
     *
	   * Permite enlazar un nodo incidente en un enlace identificado por un string. 
	   * 
	   * Si ya existía un nodo en esa posición se reemplaza
     * 
     * @param Nodo $un_nodo Nodo que se enlazará.
     * @param string $enlace Identificador del enlace.
     * @return bool `true` si la asignación fue exitosa, `false` en caso contrario.
     * @public
     */
   // protected function _incidente_en($un_nodo, $enlace): bool;//este metodo creo que debe ser protegido

    /**
     * Elimina un enlace del nodo.
     *
     * Busca y elimina un enlace dado si existe. Y devuelve el nodo
     * que estaba enlazado en dicho enlace o devuelve `null` en caso contrario.  
     *
     * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo, este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html#method_eliminar Nodo::eliminar($nodo)}
     * 
     * @param string $enlace Nombre del enlace a eliminar
     * @return ?Nodo Nodo eliminado o `null` si no existe
     * @public
     */
   // private function eliminar_incidente($enlace): ?Nodo;

    /**
     * Elimina todos los enlaces del nodo.
     *
     * Borra todas las conexiones salientes y devuelve los nodos eliminados como array.  
     * Si no hay adyacentes, retorna un array vacío.
     *
     * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo, este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html#method_eliminar Nodo::eliminar($nodo)}
     *
     * @param
     * @return Nodo[] Array de nodos eliminados, o array vacío si no existen
     */
 //   private function eliminar_incidentes(): ?array;

    


    /**
     * Ejecuta una funcion por cada incidente
     *
     * Recorre todos los adyacentes y aplica la función indicada sobre cada uno.  
     * La función recibe el nodo, el enlace y parámetros adicionales en caso de proveerse.  
     * Retorna un array con los resultados, o `null` si no existen adyacentes.
     * 
     * @param callable $funcion Función a ejecutar sobre cada nodo adyacente.
     * @param mixed ...$parametros Parámetros adicionales para la función.
     * @return array|null Resultados de la ejecución o `null` si no hay adyacentes.
     */
    public function por_cada_incidente_ejecutar(callable $funcion, mixed ...$parametros): ?array;
    
}
?>