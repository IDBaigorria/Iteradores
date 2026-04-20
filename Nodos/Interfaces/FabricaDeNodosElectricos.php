<?php
namespace Iteradores\Nodos\Interfaces;
use Iteradores\Nodos\NodoElectrico;
/**
 * Interfaz para crear y eliminar nodos electricos
 * 
 * La principal diferencia entre los nodos electricos y los comunes es que los electricos
 * trabajan en "fase". Esto es asi para poder tener distintos comportamientos del nodo usando 
 * los mismos metodos pero diferenciando enlaces. Esto significa que los adyacentes del nodo
 * comun ahora se ven replicados por cada face. Replicados en el espacio de nombres pero no en
 * el contenido. Interface es una especie de matris donde por cada fase tienen enlaces a nodos
 * y dependiendo en que fase se este trabajando el enlace puede existir o sinificar una
 * cosa totalmente distinta. Esto es un poco asi porque se piensa en este caso como que cada
 * nodo es en realidad un componente eletronico interconectado a otros componentes realizando
 * una simulacion, entonces el mismo componente puede representar un comportamiento distinto para
 * cada "fase" de trabajo, pero para esto debe tener algunas cosas unicas de cada fase, como
 * los adyacentes (de la interfaz adyacente).
 * 
 * Siguiendo este razonamiento de componentes electricos cada nodo tiene un codenzador que le
 * da su tiempo de vida. si este se queda sin energia el nodo y el nodo llega a cero. por ahora
 * se guarda una funcion distinta por cada fase que se ejecuta cuando llega a cero la energia 
 * dependiendo la fase y otra funcion tambien por fase para cuando se desborda la energia (pasa
 * la capacidad maxima que se guardae una propiedad de instancia). La propiedad energia va
 * a tener entonces un array con la energia de cada fase, similar a como se maneja el resto
 * de la clase.
 * 
 * Espero que se entienda. y sino q lo lea una ia jeeeee
 * 
 * Vamos al diseño de la fabrica para que todo funcione cada instancia de NodoElectrico tiene una 
 * capacidad (maxima) y un fuga. La fuga es para imitar lo que sucede en verdad en un capacitor 
 * electrolitico que nunca es perfecto y va perdiendo "energia" con el paso del tiempo. (esto ayudara 
 * mas adelante para ver como transcurre el tiempo). 
 * 
 * Entonces todos los nodos electricos tienen tres propiedades de instancia:
 * energia (actual)
 * capacidad (maxima)
 * fuga (de energia por ciclo de tiempo)
 * 
 * energia es dinamico y tiene su propia interfaz. en esta interfaz nos concentraremos en las constantes
 * de creacion capacidad y fuga. Para que sea compatible con la intefaz de nodo las pondremos opciones
 * en cada entrada de las funciones de creacion y tomaremos los valores por defecto de la clase Conf si 
 * es que no se proporcionan.
 * 
 * @package Iteradores\Nodos\Interfaces
 * @since V1.2.3
 */
interface FabricaDeNodosElectricos extends FabricaDeNodos{
    /**
     * Crea una nueva instancia de un nodo.
     *
     * Define la operación de fábrica que deben implementar las clases
     * encargadas de generar Nodos vacios.
     *
     * @static
     * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param float $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.

     * @return NodoElectrico Una nueva instancia de nodo.
     */
    public static function crear(int $capacidad=Conf::CAPACIDAD_NODO_ELECTRICO, float $fuga=Conf::FUGA_NODO_ELECTRICO): NodoElectrico;

    /**
     * Crear un nuevo nodo encapsulando el dato recibido.
     *
     * Devuelve una nueva instancia de la clase NodoElectrico que contiene el dato provisto.  
     * Este método no discrimina el tipo del dato, puede ser un valor primitivo o un objeto complejo, 
     * que será encapsulado directamente en el nodo.
     * @static
     * @param mixed $dato Valor a encapsular en el nuevo nodo.
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.

     * @return NodoElectrico Instancia de nodo que encapsula el dato.
     *
     */
	public static function crear_con_dato($dato, $todos = false, $capacidad=Conf::CAPACIDAD_NODO_ELECTRICO, $fuga=Conf::FUGA_NODO_ELECTRICO):NodoElectrico;
    
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
     * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.

     * @return NodoElectrico|null Instancia de nodo con identificador *especial* 
     *                   o null si el identificador no era *especial*.
     *
     */
	public static function crear_con_id($id, $capacidad=Conf::CAPACIDAD_NODO_ELECTRICO, $fuga=Conf::FUGA_NODO_ELECTRICO): NodoElectrico|null;
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
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.

     * @return NodoElectrico|null Instancia de nodo con dato e identificador *especial* 
     *                   o null si el identificador no era *especial*.
     */
	public static function crear_con_dato_e_id($dato, $id, $capacidad=Conf::CAPACIDAD_NODO_ELECTRICO, $fuga=Conf::FUGA_NODO_ELECTRICO): NodoElectrico|null;

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
     * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde: 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.

     * @return NodoElectrico Nodo válido que encapsula el valor recibido.
     *
     */
    public static function nodo($elemento=null, &$es_nodo=null, $capacidad=Conf::CAPACIDAD_NODO_ELECTRICO, $fuga=Conf::FUGA_NODO_ELECTRICO): NodoElectrico|null;

}
?>