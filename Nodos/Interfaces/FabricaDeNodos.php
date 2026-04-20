<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\Nodo;
/**
 * Interfaz FabricaDeNodos
 *
 * Define el contrato para la creación y eliminación de instancias de {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.
 *
 * Esta interfaz abstrae la lógica de construcción y destrucción de nodos, garantizando que todas las clases que la implementen
 * proporcionen operaciones consistentes para trabajar con la estructura de nodos en un grafo o red de datos.
 *
 * Métodos principales:
 * - Creación básica de nodos vacíos o con datos encapsulados.
 * - Creación de nodos con identificadores *especiales* validados externamente.
 * - Conversión forzada de cualquier valor a un nodo válido mediante `nodo()`.
 * - Obtención de la cantidad de instancias vivas en el sistema.
 * - Eliminación segura de nodos, bajo la condición de no poseer enlaces entrantes.
 *
 * ⚠️ Nota sobre `eliminar_autoenlazado`:  
 * Este método se conserva únicamente por compatibilidad histórica y está marcado como `@deprecated`.  
 * La eliminación de autoenlaces debe ser gestionada manualmente por el programador.
 * 
 * @package Iteradores\Nodos\Interfaces
 * @since V3.2
 */
interface FabricaDeNodos
{
    /**
     * Devuelve la cantidad de nodos actuales.
     *
     * Define la operación que debe implementar cualquier clase
     * que actúe como fábrica de nodos.
     *
     * @since V2.7
     * @static
     * @return int Número de instancias de Nodo existentes.
     */
    public static function cantidad_de_nodos();
    /**
     * Crea una nueva instancia de un nodo.
     *
     * Define la operación de fábrica que deben implementar las clases
     * encargadas de generar Nodos vacios.
     *
     * @static
     * @return Nodo Una nueva instancia de nodo.
     */
    public static function crear();
    /**
     * Crear un nuevo nodo encapsulando el dato recibido.
     *
     * Devuelve una nueva instancia de la clase Nodo que contiene el dato provisto.  
     * Este método no discrimina el tipo del dato, puede ser un valor primitivo o un objeto complejo, 
     * que será encapsulado directamente en el nodo.
     * @static
     * @param mixed $dato Valor a encapsular en el nuevo nodo.
     * @return Nodo Instancia de nodo que encapsula el dato.
     *
     */
    public static function crear_con_dato($dato);

    /**
     * Crear un nuevo nodo asignándole un identificador *especial*.
     *
     * El identificador pasado como argumento debe ser unico y superar positivamente la verificación realizada
     * por el método `{@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_id_especial es_id_especial}` 
     * definido en la interfaz {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Nucleo/Interfaces/Id}.
     * 
     * 🔗 Métodos relacionado:
	 * - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_especial es_especial()}
     *
     * @static
     * @param mixed $id Identificador *especial* a asignar al nuevo nodo.
     * @return Nodo|null Instancia de nodo con identificador *especial* 
     *                   o null si el identificador no era *especial*.
     *
     */
    public static function crear_con_id($id);
    /**
     * Crear un nuevo nodo encapsulando un dato y asignándole un identificador *especial* válido.
     *
     * El identificador pasado como argumento debe ser unico y superar positivamente la verificación realizada
     * por el método `{@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_id_especial es_id_especial}` 
     * definido en la interfaz {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Nucleo/Interfaces/Id}.
     *
     * 🔗 Métodos relacionado:
	 * - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html#method_es_especial es_especial()}
     * 
     * @static
     * @param mixed $dato Valor a encapsular en el nodo.
     * @param mixed $id Identificador *especial* a asignar al nuevo nodo.
     * @return Nodo|null Instancia de nodo con dato e identificador *especial* 
     *                   o null si el identificador no era *especial*.
     */
    public static function crear_con_dato_e_id($dato, $id);

    /**
     * Garantizar que el elemento entregado sea un nodo válido.
     *
     * Este método recibe un valor cualquiera o un posible nodo y asegura que el resultado final
     * sea siempre una instancia de {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.  
     *
     * Comportamiento general:
     * - Si no recibe ningun parametro devuelve un nuevo Nodo vacío totalmente válido.
     * - Si el parámetro recibido **ya es un Nodo**, simplemente se retorna y la variable de salida
     *   `$es_nodo` toma el valor `true`.  
     * - Si el parámetro **no es un Nodo**, se encapsula en una nueva instancia creada mediante
     *   {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html#method_crear_con_dato crear_con_dato()}, y `$es_nodo`
     *   se establece en `false`.  
     * - Si no se pasa ningún valor, se genera un nodo válido que encapsula explícitamente `null`.  
     *
     * El segundo parámetro `&$es_nodo` funciona como una **variable de salida por referencia** que
     * permite al llamador conocer si el valor original ya era un nodo o si fue transformado.  
     *
     * Esta interfaz existe para abstraer el detalle de construcción de nodos y garantizar que
     * las implementaciones que la utilicen trabajen siempre con objetos válidos sin necesidad de
     * comprobaciones adicionales.  
     *
     *
     * @param mixed $elemento Valor a encapsular o un nodo existente.  
     *                        Si es `null`, se crea un nodo vacío válido.  
     * @param bool|null &$es_nodo Variable de salida por referencia.  
     *                            Devuelve `true` si `$elemento` ya era un nodo, `false` en caso contrario.  
     * @return Nodo Nodo válido que encapsula el valor recibido.
     *
     * @since V2.9.3
     */
    public static function nodo($elemento = null, &$es_nodo = null);
    
   /**
     * Elimina un nodo del sistema
     *  
     * Elimina un nodo del sistema, incluyendo su enlace desde la superestructura
     * y desde los nodos especiales (si corresponde).
     *
     * ⚠️ Es condición imprescindible que el nodo **no tenga enlaces de otros nodos apuntando a él**.
     * En caso contrario, devuelve `false` y lanza un error.
     *
     *
     * @param Nodo $nodo Nodo a eliminar.
     * @return bool|null `true` si fue eliminado, `false` si no pudo eliminarse,
     *                   `null` si el parámetro no es válido.
     */
    public static function eliminar($nodo): bool|null;

     /**
     * Elimina un nodo que solo tiene autoenlaces
     *
     * Elimina un nodo considerando autoenlaces (enlaces desde el propio nodo hacia sí mismo).
     * 
     * ⚠️ **Este método está obsoleto**:
     * 
     * Ya no corresponde a la responsabilidad de la interfaz manejar la eliminación de autoenlaces.
     * El programador debe asegurarse de limpiar manualmente todos los enlaces —incluyendo los
     * autoenlaces— antes de invocar el 
     * {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html#method_eliminar 
     * método de eliminación estándar}.
     * 
     * Si el nodo tiene autoenlaces pueden eliminarse usando el metodo 
	   * {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#eliminar_enlace() eliminar_enlace} 
	   * que elimina los enlaces de a uno; o el metodo
	   * {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html#eliminar_enlaces() eliminar_enlaces}
	   * que elimina todos los enlaces que salen del nodo, incluyendo los que apuntan a sí mismo
     * 
     * @deprecated Este metodo ya no debe usarse
     * @static
     * @param Nodo $nodo Nodo a eliminar.
     * @return bool|null Devuelve true si se eliminó correctamente, false si no fue posible, o null si el parámetro no es válido.
     */
    public static function eliminar_autoenlazado(Nodo $nodo);
}
?>