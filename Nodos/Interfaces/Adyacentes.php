<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;
/**
 * Interfaz que define el manejo de adyacentes en un nodo.
 *
 * Permite establecer y recuperar adyacentes enlazados por el nodo.
 * Estos métodos son implementados por la clase {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}
 * y herederos.
 * 
 * La representacion interna puede variar segun la implementacion
 *
 * @package Iteradores\Nodos\Interfaces
 * @since V0.1.9
 */
interface Adyacentes {
    /**
	 * Verifica si el nodo tiene al menos un adyacente.
	 *
	 * Este método comprueba si tiene al menos un nodo adyacente. O dicho de otro modo
	 * si tiene conexiones "salientes"; en tal caso devuelve true; caso contrario devuelve 
	 * false
	 * 
	 * Si el nodo está autoenlazado, es decir tiene algun enlace que sale de él hacia él
	 * mismo tambien devuelve true. 
	 *  
	 *⚠️ Importante: verifica las conexiciones de "salida", pero no las de "entrada". Para
	 * verificar las conexiones de entrada use
	 * {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_adyacente tiene_adyacente}
     * 
     * @return bool Devuelve **true** si tiene adyacentes, o **false** en caso contrario.
     */
    public function tiene_adyacente();


    /**
     * Verifica si el nodo actual tiene como adyacente al nodo indicado.
     *
     * Indica si el nodo actual enlaza directamente hacia el nodo pasado como parámetro.  
     * Devuelve el nombre del enlace en caso de existir, o `false` en caso contrario.
     * 
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#tiene_incidente_a tiene_incidente_a()}
     * 
     * @param Nodo $nodo Nodo a verificar.
     * @return string|false Nombre del enlace si existe, `false` en caso contrario.
     * @public
     * @since 3.2.3
     * 
     */
    public function tiene_adyacente_a($nodo);



    /**
     * Valida un nombre de enlace.
     *
     * Comprueba que el nombre de un enlace sea correcto antes de usarse en un grafo.  
     * Solo se permiten `int` o `string`, pero no `0`, `"0"` ni `""`.  
     * Este método debe implementarse de forma estática.
     * 
     * @param int|string $enlace Nombre del enlace a validar
     * @return bool `true` si es válido, `false` en caso contrario
	 * @since 3.2.3
     * @static
     */
    public static function validar_nombre_enlace($enlace): bool;

    /**
     * Devuelve el nodo adyacente en el enlace especificado.
     *
     * Comprueba si existe un nodo en el enlace indicado y lo devuelve;  
     * si no existe, devuelve `null`.
     * 
     * @param int|string $enlace El identificador del enlace a consultar
     * @return Nodo|null Nodo adyacente si existe, `null` en caso contrario
     */
    public function adyacente($enlace): ?Nodo;

    /**
     * Devuelve todos los adyacentes del nodo.
     *
     * Retorna una colección con todos los enlaces a los nodos adyacentes si existen, 
     * o `null` en caso contrario.  
     * 
     * Se usa cuando se necesita trabajar sobre una "foto" de los enlaces sin tocar la
     * estructura interna.
     *
     * @public
     * @return ?array Array con nodos adyacentes o `null` si no existen
     */
    public function adyacentes(): array|null;

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
    public function _adyacente($nodo) :?string;

    /**
     * Asigna un nodo adyacente en un enlace específico.
     *
	 * Permite enlazar un nodo adyacente en un enlace identificado por un string. 
	 * 
	 * Si ya existía un nodo en esa posición, puede reemplazarse explícitamente con `$reemplazar=true`.
	 * Si `$reemplazar=false` (comportamiento predeterminado), y ya hay un nodo en el enlace dado
	 * genera un mensaje de error.
     * 
     * @param Nodo $un_nodo Nodo que se enlazará.
     * @param string $enlace Identificador del enlace.
     * @param bool $reemplazar Si `true`, permite sobreescribir un nodo existente.
     * @return bool `true` si la asignación fue exitosa, `false` en caso contrario.
     * @public
     */
    public function _adyacente_en($un_nodo, $enlace, $reemplazar=false): bool;

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
     * @deprecated usar eliminar_adyacente($enlace)
     * @public
     */
    public function eliminar_enlace($enlace): ?Nodo;

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
     * @deprecated usar eliminar_adyacente($enlace)
     */
    public function eliminar_enlaces(): array;
    /**
     * Elimina un enlace adyacente del nodo.
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
    public function eliminar_adyacente($enlace): ?Nodo;

    /**
     * Elimina todos los adyacentes del nodo.
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
    public function eliminar_adyacentes(): array;
    /**
     * Devuelve la cantidad de adyacentes.
     *
     * Retorna el número de nodos adyacentes vinculados al nodo.  
     * Si no existen adyacentes, devuelve 0.
     *
     * @param
     * @return int Número de adyacentes actuales
     * @public
     * @since 2.9.4
     */
    public function cantidad_de_adyacentes(): int;


    /**
     * Ejecuta una función sobre cada nodo adyacente.
     *
     * Recorre todos los adyacentes y aplica la función indicada sobre cada uno.  
     * La función recibe el nodo, el enlace y parámetros adicionales en caso de proveerse.  
     * Retorna un array con los resultados, o `null` si no existen adyacentes.
     * 
     * @param callable $funcion Función a ejecutar sobre cada nodo adyacente.
     * @param mixed ...$parametros Parámetros adicionales para la función.
     * @return array|null Resultados de la ejecución o `null` si no hay adyacentes.
     */
    public function por_cada_adyacente_ejecutar(callable $funcion, mixed ...$parametros): ?array;
}
?>