<?php
namespace Iteradores\Nodos;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Interfaces\AccesoAEspeciales;
use Iteradores\Nodos\Interfaces\AccesoASuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL;
use Iteradores\Nodos\Interfaces\FabricaDeNodos;
use Iteradores\Nodos\Interfaces\Datos;
use Iteradores\Nodos\Interfaces\Adyacentes;
use Iteradores\Nodos\Interfaces\Incidentes;
//include_once("Objeto V1.3.180822.php");
//namespace MyApp;
include_once(".\Nucleo\Objeto.php");
//include_once(".\Nodos\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL.php");
include_once(".\Nodos\Interfaces\FabricaDeNodos.php");
include_once(".\Nodos\Interfaces\Datos.php");
include_once(".\Nodos\Interfaces\Adyacentes.php");
include_once(".\Nodos\Interfaces\Incidentes.php");
include_once(".\Nodos\Interfaces\AccesoASuperestructura.php");
include_once(".\Nodos\Interfaces\AccesoAEspeciales.php");
include_once(".\miscelaneas\generarUUID.php");
/**
 * Clase: Nodo
 *
 * Clase de prueba implementando objetivos pendientes sugeridos en el manual v1.0.
 * Se añaden interfaces para manipulación de atributos de nodos y enlaces (como el "peso").
 * Mantiene compatibilidad con implementaciones previas hasta que se actualicen.
 *
 *
 * ##CONSIDERACIONES GENERALES
 * - Clase base para la construcción de Nodos para grafos.
 * - Administra datos internos y enlaces a nodos adyacentes.
 * - Controla referencias y pertenencia a superestructuras comunes o especiales.
 *
 * ##VARIABLES DE INSTANCIA
 * - `$referencias` → Número de enlaces que apuntan al nodo.
 * - `$dato` → Dato contenido en el nodo (con interfaz de acceso).
 * - `$adyacentes` → Array de nodos adyacentes.
 *
 * ##VARIABLES DE CLASE
 * - `static $cant` → Cantidad total de nodos creados.
 * - `static $superestructura` → raíz de la superestructura común.
 * - `static $nodos_especiales` → raíz de la superestructura de nodos especiales.
 *
 * ---
 * 
 * ##INTERFAZ FABRICADENODOS
 * 
 * Implementa la interfaz {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
 * ofreciendo la implementación real de los métodos de creación, validación y eliminación de nodos.
 *
 * Cada nodo encapsula un dato y puede poseer un identificador *especial*. Además, mantiene enlaces
 * hacia otros nodos adyacentes dentro de la estructura de grafos.
 *
 * Funcionalidad principal:
 * - Crear nodos vacíos o con datos encapsulados.
 * - Generar nodos con identificadores *especiales* validados.
 * - Convertir cualquier valor en un nodo válido usando `nodo()`.
 * - Llevar un contador estático de instancias creadas.
 * - Eliminar nodos de manera segura bajo las condiciones de integridad definidas.
 * 
 * ---
 * 
 * ##INTERFAZ DATOS
 * 
 * Implementa la interfaz {@link ./classes/Iteradores-Nucleo-Interfaces-Datos.html Datos},
 * proporcionando la capacidad de almacenar y recuperar un valor cualquiera.
 *
 * Métodos implementados de la interfaz Datos:
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__dato _dato()} : asigna un valor al nodo.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_dato dato()}   : devuelve el valor almacenado en el nodo.
 * 
 * ---
 * 
 * ##INTERFAZ ADYACENTE
 * 
 * La clase {@see Nodo} también implementa la interfaz {@see Adyacentes} y la interfaz {@see Incidentes} 
 * la cuales cuales son complementarias y proporcionan un conjunto de métodos para gestionar y consultar 
 * los enlaces entre los nodos.
 *
 * Gracias a esto, un nodo puede:
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente} Verificar si tiene adyacentes.
 * - {@link ./classes/Iteradores-Nodos_Nodo.html#method_tiene_incidente tiene_incidente} Verificar si tiene incidentes.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes} Obtener la cantidad de adyacentes.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes} Obtener la cantidad de incidentes.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente} Agregar un nodo adyacente generando un enlace automáticamente.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacentes_en} Agregar un nodo adyacente en un enlace suministrado.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente} Eliminar un enlace hacia un adyacente específico.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes} Eliminar todos los enlaces a adyacentes.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente} Recuperar un adyacente en particular.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes} Recuperar todos los adyacentes.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a} Verificar si existe adyacente en un enlace específico.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a} Verificar si este nodo es adyacente de un nodo suministrado.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#validad_nombre_enlace validad_nombre_enlace} Verificar que el nombre del enlace sea válido.
 * - {@link ./classes/Iteradores-Nodos-Nodo.html#por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar} Ejecutar una función en cada adyacente.
 *
 * ##HISTORIAL DE CAMBIOS
 *
 * - 23/06/2012: Comenzado el proceso de refactorización según decisiones tomadas.  
 * - 04/07/2012: Sigo refactorización.  
 * - 09/07/2012: "  
 * - 12/07/2012: Fin refactorización.  
 * - 09/08/2012: V1.6 Solucionados varios bugs.  
 * - 25/02/2013: V1.7 Refactorizo y modifico.  
 * - 27/02/2013: Sigo trabajo.  
 * - 05/07/2013: Pruebas. Arreglada función tiene_adyacente().  
 * - 07/07/2013: V1.9 Comienzo nueva refactorización.  
 * - 17/07/2013: V2.1 Interfaz creación.  
 * - 17/07/2013: V2.3 Interfaz de acceso a la superestructura.  
 * - 18/07/2013: V2.3.1 Interfaz de manipulación de entorno, ejecutar función por cada adyacente.  
 * - 18/07/2013: V2.3.3 Superestructura pasará a ser un nodo. Se agrega la función ejecutar por cada nodo.  
 * - 19/07/2013: V2.3.5 Agregado eliminar_enlaces_a() y eliminar(nodo).  
 * - 19/07/2013: V2.5 Guardar y cargar superestructura.  
 * - 20/07/2013: V2.5.1 Guardar.  
 * - 20/07/2013: V2.5.3 Cargar.  
 * - 21/07/2013: V2.5.5 Cambiado guardar, se agrega parámetro con nombre.  
 * - 28/12/2016: V2.161228 Cambio de nomenclatura, revisión general.  
 * - 07/01/2017: V2.170107 Se agrega alerta en eliminar_enlace().  
 * - 23/01/2017: V2.170123 Intento cambio de adyacente_en() → adyacente().  
 * - 30/01/2017: V2.170130 Cambio include_once por nueva versión de Objeto.  
 * - 08/02/2017: V2.170208 Nodo especial "compartidos" será guardado aparte.  
 * - 06/03/2017: V2.7.170306 Cambio estrategia de eliminación.  
 * - 07/03/2017: V2.7.170307 Uso de variable privada para referencias.  
 * - 30/06/2017: V2.7.170630 Modifico eliminar_enlace().  
 * - 01/11/2017: V2.7.171101 Actualizo a PHP7.  
 * - 07/11/2017: V2.7.171107 eliminar_enlace devuelve nodo eliminado.  
 * - 25/04/2018: V2.8.0 Actualizo versión de Objeto, arreglo bug en tiene_adyacente.  
 * - 30/05/2018: V2.8.3 Adapto PerdurarSuperestructuraString.  
 * - 03/08/2018: V2.9.0 Inicio refactorización BETA.  
 * - 17/08/2018: V2.9.0 Agrego mecanismo para nodos sueltos.  
 * - 18/08/2018: V2.9.0 eliminar_nodos_sueltos, pruebas superadas.  
 * - 19/08/2018: V2.9.1 Revisión de nodos desconectados, nueva solución propuesta.  
 * - 22/08/2018: V2.9.1 Interfaces acceso superestructura y nodos especiales terminadas.  
 * - 22/08/2018: V2.9.2 Separación de PerdurarSuperestructuraString.  
 * - 23/08/2018: V2.9.2 Refactorizo interfaz Dato y manejo adyacentes.  
 * - 11/11/2018: V2.9.2 Refactorizo impresión.  
 * - 06/01/2019: V2.9.3 Agrego es_nodo().  
 * - 15/02/2019: V2.9.3 Modifico _adyacente_en(), agrego alertas.  
 * - 16/12/2019: V2.9.3 Agrego eliminar_autoenlazado().  
 * - 17/11/2020: V2.9.4 Correcciones en por_cada_adyacente_ejecutar().  
 * - 03/06/2021: V2.9.4 Pruebas en 000webhost.  
 * - 24/03/2023: V3.0.0 Continuación refactorización.  
 * - 23/04/2024: V3.0.1 crear_con_dato permite convertir objetos a nodos.  
 * - 27/04/2024: V3.0.1 Ajustes en arrays con enlaces "siguiente".  
 * - 26/08/2025: V3.1.0 Quito números de versiones.  
 * - 01/09/2025: V3.1.1 Agrego eliminar_superestructura().  
 * - 04/09/2025: V3.1.1 Agrego links en imprimir_superestructura().  
 * - 07/09/2025: V3.1.2 Elimino nombres de clase y función en alertas y errores.  
 * - V3.2.0.250918: Comienzo gran refactorizacion de la capa Nodo y espejado en JS
 *     - Achique el historial para que se vea mas lindo
 *     - Comienzo a refactorizar la interfaz de creacion y destrucion 
 *       (ahora se llama FabricaDeNodos y está definida aparte)
 *     - En el metodo crear_con_dato antes se permitia crear estructuras complejas 
 *       a partir de un array o un objeto. Esa capacidad es eliminada (se va a implementas
 *       mas adelante en algun Iterador. Tendre que ver porque seguro me generá problemas)
 * - V3.2.1.250922: **Finalizada refactorizacion de interfaz FabricaDeNodos**
 *      -Deprequeto eliminar_autoenlazado
 * - V3.2.2.250923: **Finalizada refactorizacion de interfas Datos**
 * - **V3.2.3.250923** **Desicion importante**: A partir de ahora todos los enlaces seran string para 
 *            homogeneizar y simplicar algunas tareas como eliminar_enlace. 
 *            Esto reducirá los tiempos de eliminar_enlace de O(n) a O(1).
 *            Se tiene en cuenta que la gran mayoria de los casos de uso reales no he 
 *            usado numeros para los enteros. Solo al principio. 
 *          - agrego validar_nombre_enlace
 *          - modifico adyacentes para que devuelva una "foto" del estado actual del nodo y asegurarme
 *            de que no se modifique el estado interno
 * - V3.2.3.250930: **Finalizada refactorizacion de interfas Adyacentes** 
 * - V3.2.4.250930: Comienzo con interfaces AccesoASuperestructura y AccesoAEspeciales
 * - V3.2.4.251002: Pruebas de rendimiento y modificaciones. Primera conclucion la inicializacion perezosa sirve
 * 					al contrario que js.   
 * - **V3.2.5.251006:** **Desicion importante**: Para optimizar al maximo la velocidad y memoria se cambia
 * 					el enfoque de como se como se maneja la superestrutura y los nodos_especiales, ya no
 * 					serán un nodo cada una, sino directamente arrays asignados a una propiedad privada estatica
 * 					de la clase. Esto implicara otro cambios que debo ir vienolos. Paro vale la pena dado
 * 					el aumento de velocidad logrado con este y otros cambios (se redujo el consumo de cpu 
 * 					en un 75% aprox)
 * - **V3.2.5.251021:** elimine todos los vestigios de superestructura y nodos_especiales como Nodo (abora va a ser un array), 
 *                    modifique los lugares donde se usaba referencias === 1 o 2.
 *                    terminada interfaz de accesoASuperestructura, ahora voy por especiales
 * - **V3.2.6.251121:** agrego interfaz Incidentes
 * - **V3.3.0.260108: Retomo despues de un tiempo con ideas mas claras. Comienzo revicion y agregado de lo que falta**
 * 
 * 
 * @class
 * @author Ignacio David Baigorria
 * @package Iteradores\Nodos
 * @version 3.2.6
 * @since 0.0
 * @implements Interfaces\FabricaDeNodos
 * @implements Interfaces\Datos
 * @implements Interfaces\Adyacentes
 * @implements Interfaces\Incidentes
 * @implements Interfaces\AccesoASuperestructura
 * @implements Interfaces\AccesoAEspeciales
 */
class Nodo extends Objeto implements FabricaDeNodos, Datos, Adyacentes, Incidentes, AccesoASuperestructura, AccesoAEspeciales {

    ////////////////////////////////////////////////////
    // VARIABLES DE INSTANCIA
    ////////////////////////////////////////////////////

    /** @var int Número de enlaces que apuntan al nodo */
    protected $referencias = 0;

    /** @var mixed Dato contenido en el nodo */
    protected $dato;

    /** 
	 * @var array | array<array> Matriz de enlaces a nodos adyacentes
	 * */
    protected $adyacentes;

    ////////////////////////////////////////////////////
    // VARIABLES DE CLASE
    ////////////////////////////////////////////////////

    /** @var int Cantidad total de nodos actuales */
    protected static $cant = 0;

    /** @var array Superestructura de nodos comunes */
    protected static $superestructura=[];

    /** @var array Superestructura de nodos especiales */
    protected static $nodos_especiales=[];

/*************************************************************************************************************/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
//										                     ************************************************//
// Interfaz de Construccion y Destruccion (Fabrica de Nodos) //////////////////////////////////////////////////
//										                     ************************************************//
/*************************************************************************************************************/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

	/**
	 * Constructor de la clase Nodo 
	 *
	 * Construcción y destrucción (FabricaDeNodos)
	 * Caso de uso: Crea internamente un nodo
	 *
	 * @private
	 *
	 * @note Incluye un mecanismo para contar la cantidad de nodos existentes con la variable estática $cant
	 *
	 * @return Nodo
	 */
	private function __construct() {
		self::$cant++;
	}

	/**
	 * Destructor de la clase Nodo
	 *
	 * Interfaz: Construcción y destrucción
	 * Caso de uso: Destruye internamente un nodo
	 *
	 * @private
	 *
	 * @note Incluye un mecanismo para actualizar la cantidad de nodos existentes con la variable estática $cant
	 *
	 * @return void
	 */
	function __destruct() {
		self::$cant--;
	}
	
	/**
	 * Devuelve la cantidad de nodos existentes actualmente en el sistema.
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}	
	 *
	 * La cantidad de nodos se lleva mediante un contador estático
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#$cant Nodo::$cant}, que es incrementado cada vez que se crea un nodo 
	 * y decrementado cada vez que se destruye.
	 * 
	 * ---
	 * 🔗 Métodos relacionados
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 *
	 * ---
	 *
	 * Ejemplo de uso en un script PHP
	 * ```php
	 * echo "Cantidad de nodos existentes: " . Nodo::cantidad_de_nodos();
	 *
	 * $n1 = Nodo::crear();   
	 * $n2 = Nodo::crear();
	 *
	 * echo "Cantidad de nodos actuales: " . Nodo::cantidad_de_nodos(); 
	 * // Salida esperada: 2
	 *
	 * Nodo::eliminar($n1);
	 * echo "Cantidad de nodos actuales: " . Nodo::cantidad_de_nodos();
	 * // Salida esperada: 1
	 * ```
	 * 
	 * @since V2.7
	 * @static
	 * @return int Número total de instancias de {@link ./classes/Iteradores-Nodos-Nodo.html Nodo} existentes.
	 *
	 * @note Este método no recibe parámetros porque la cuenta es global 
	 *       y depende únicamente de las operaciones internas de creación
	 *       y destrucción de instancias.
	 *
	 */
	public static function cantidad_de_nodos(){
		return self::$cant;
	}

	/**
	 * Crea una nueva instancia de vacia de Nodo (Interfaz FabricaDeNodos)
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}	 
	 * 
	 * El constructor de la clase es privado, con lo que se asegura que las instancias 
	 * no puedan crearse de forma directa desde el exterior, por lo que éste método es una
	 * de las formas válidas de crear nodos.
	 * 
	 * 🔗 Otros métodos de creacion que se pueden usar son:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos Nodo::cantidad_de_nodos()}  
	 *
	 * ---
	 * Ejemplo de uso en un script PHP
	 * ```php
	 * $n1 = Nodo::crear();
	 * $n2 = Nodo::crear();
	 *
	 * echo "Nodos actuales: " . Nodo::cantidad_de_nodos();
	 * // Salida esperada: 2
	 * ```
	 * @static
	 * @return Nodo Una nueva instancia de la clase {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.
	 *
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-Nodo.html#$cant Nodo::$cant}), 
	 * 		 y lo agrega a la Superestructura
	 *       lo cual permite llevar un registro global de Nodos.
	 *
	 */
	public static function crear(){
		$nodo= new static();
		self::$superestructura[$nodo->id()]=$nodo;
		return $nodo;
	}

	/**
	 * Crear un nuevo nodo encapsulando el dato recibido (Interfaz FabricaDeNodos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 * 
	 * Este método crea una nueva instancia de la clase Nodo a partir de un dato cualquiera.  
	 * El dato no es procesado ni verificado: se encapsula directamente en el nodo, lo que lo hace
	 * muy flexible tanto para valores primitivos como complejos. Incluso se pueden encapusalar 
	 * otros nodos!.
	 *
	 * El constructor de la clase es privado, con lo que se asegura que las instancias 
	 * no puedan crearse de forma directa desde el exterior, por lo que éste método es una
	 * de las formas válidas de crear nodos.
	 * 
	 * 🔗 Otros métodos de creacion que se pueden usar son:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 * 
	 * ---
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos cantidad_de_nodos()}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_dato("Hola Mundo");
	 * echo $nodo->dato(); // Devuelve: "Hola Mundo"
	 * ```
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-Nodo.html#$cant Nodo::$cant}), 
	 * 		 y lo agrega a la Superestructura
	 *       lo cual permite llevar un registro global de Nodos.
	 * @static
	 * @param mixed $dato Valor a encapsular en el nuevo nodo.
	 * @param boolean $todos lo dejo por compatibilidad pero en cualquier momento lo borro
	 * @return Nodo Instancia de nodo que encapsula el dato.
	 *
	 */
	public static function crear_con_dato($dato, $todos = false): Nodo
	{
		if (!$todos) {//Creacion simple, ya sea un dato de tipo elemental, un objeto o un array, lo coloca encapsula entero dentro del nodo
			$nodo = new static();
			self::$superestructura[$nodo->id()]=$nodo;
			$nodo->dato=$dato;
			return $nodo;
		} else {//esta parte esta por compatibilidad pero la voy a quitar cuando encuentre donde se usa
			if (!is_object($dato)) {
				if (is_array($dato)) {
					$nodo = static::crear_con_dato("ARRAY");
                    $nodoaux=$nodo;
					foreach ($dato as $propiedad => $valor) {
                        $nodoaux2=static::crear_con_dato($valor, true);
						$nodo->_adyacente_en($nodoaux2,"siguiente");
                        $nodo=$nodoaux2;
					}
					return $nodoaux;
				} else {
					$nodo = static::crear();
					$nodo->_dato($dato);
					return $nodo;
				}
			} else {
				$nodo = static::crear();
				foreach ($dato as $propiedad => $valor) {
					$nodo->_adyacente_en(static::crear_con_dato($valor, true), $propiedad);
				}
				return $nodo;
			}
		}
	}

	/**
	 * Crear un nuevo nodo asignándole un identificador válido (Interfaz FabricaDeNodos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 *
	 * Este método permite crear un nodo directamente a partir de un identificador.  
	 * Antes de instanciar el nodo, el identificador es evaluado mediante el método
	 * `{@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_id_especial es_id_especial(id)}` 
	 * definido en la clase Objeto, garantizando que cumple con los
	 * criterios internos de validez. Si el identificador no supera la verificación,
	 * el nodo no será creado y se deberá manejar el error en consecuencia.
	 *
	 * El constructor de la clase Nodo es privado, por lo que esta función constituye
	 * una de las formas válidas de construir nodos desde el exterior.
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos cantidad_de_nodos()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_especial es_especial()}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_id("soy_especial");
	 * echo $nodo->id(); // Devuelve: "soy_especial"
	 * ```
	 *
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-Nodo.html#$cant Nodo::$cant}) 
	 *       y lo agrega a la Superestructura.
	 *
	 * @static
	 * @param mixed $id Identificador a asignar al nuevo nodo (debe ser único y *especial*).
	 * @return Nodo|null Instancia de nodo con el identificador *especial* en caso de exito, null en caso contrario.
	 */
	public static function crear_con_id($id): Nodo|null{
		if (Objeto::es_id_especial($id)) {
			$nodo=new static();
			if ($nodo->_id_interno($id)){
				self::$superestructura[$id]=$nodo;
				self::$nodos_especiales[$id]=$nodo;
				return $nodo;
			}
			static::_error("no se pudo crear el nodo en crear_con_dato_e_id(dato,id)");
			return null;
		}
		static::_error("Para asignar un id, este debe ser especial");
		return null;
	}

	/**
	 * Crear un nuevo nodo encapsulando un dato y asignándole un identificado *especial* (Interfaz FabricaDeNodos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 *
	 * Este método combina las capacidades de {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}  
	 * y {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}.  
	 * Permite instanciar un nodo con un valor cualquiera (primitivo, complejo u otro nodo) y a la vez asignarle
	 * un identificador único *especial* que debe pasar la validación de 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_id_especial es_id_especial()}.
	 *
	 * El constructor de la clase Nodo es privado, de modo que esta función constituye una de las formas válidas de creación de nodos.
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos cantidad_de_nodos()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_especial es_especial()}
	 * 
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_dato_e_id("Hola Mundo", "soy_especial");
	 * echo $nodo->dato(); // Devuelve: "Hola Mundo"
	 * echo $nodo->id();   // Devuelve: "soy_especial"
	 * ```
	 *
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-Nodo.html#$cant Nodo::$cant}), 
	 *       y lo registra en la Superestructura para un seguimiento global.
	 *
	 * @static
	 * @param mixed $dato Valor a encapsular en el nodo.
	 * @param mixed $id Identificador del nodo (debe pasar verificación).
	 * @return Nodo|null Instancia de nodo con dato e identificador *especial* en caso de exito, null en caso contrario.
	 *
	 */
	public static function crear_con_dato_e_id($dato, $id): Nodo|null{
		
		if (Objeto::es_id_especial($id)) {
			$nodo=new static();
			if ($nodo->_id_interno($id)){
				self::$superestructura[$id]=$nodo;
				self::$nodos_especiales[$id]=$nodo;
				$nodo->dato=$dato;
				return $nodo;
			}
			static::_error("no se pudo crear el nodo en crear_con_dato_e_id(dato,id)");
			return null;
		}
		static::_error("Para asignar un id, este debe ser especial");
		return null;
	}
	

	/**
	 * Garantizar que el elemento entregado sea un nodo válido (Interfaz FabricaDeNodos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 *
	 * Este método recibe un valor cualquiera (o ninguno) o un posible nodo y asegura que el resultado final
	 * sea siempre una instancia de {@link ./classes/Iteradores-Nodos-Nodo.html Nodo}.  
	 * - Si no recibe ningun parámetro crea un Nodo vacío totalmente válido
	 * - Si el parámetro recibido **ya es un Nodo**, simplemente lo retorna y marca la variable
	 *   de referencia `$es_nodo` como `true`.  
	 * - Si el parámetro **no es un Nodo**, crea uno nuevo con 
	 *   {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}, 
	 *   lo retorna y establece `$es_nodo` como `false`.  
	 * - Si no se pasa ningún valor en el parámetro `$elemento`, crea un nodo vacío totalmente valido
	 *   encapsulando `null`.  

	 *
	 * Este método es especialmente útil cuando se procesan entradas heterogéneas, ya que garantiza
	 * que siempre se trabaje con un Nodo válido sin tener que comprobarlo manualmente.
	 *
 	 * El constructor de la clase Nodo es privado, de modo que esta función constituye una de las
	 * formas válidas de creación de nodos.
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos cantidad_de_nodos()}
	 * 
	 * ---
	 * Ejemplo de uso
	 * ```php
	 * // Caso 0: se llama sin ningun parametro(crea nodo vacio completamente valido): 
	 * $nodo0= Nodo::nodo();
     * echo $nodo0->dato(); // 
     * echo $nodo0->id(); //0
	 * 
     * // Caso 1: se le pasa un parámetro no Nodo (crea un nodo con el dato pasado por parametro)
     * $nodo1=Nodo::nodo("Soy el nodo 1");
     * echo $nodo1->dato(); // "Soy el nodo 1"
     * echo $nodo1->id();//1
	 * 
     * // Caso 2: se le pasa un parametro que es un nodo (no crea ningun nodo, devuelve el mismo nodo)
     * $nodo2=Nodo::nodo($nodo1);
     * echo $nodo2->dato(); // "soy el nodo 1"
     * echo $nodo2->id(); //1
	 * 
     * // Caso 3: se le pasa un parametro no Nodo y un segundo parametro por referencia (crea una 
	 * //nueva instancia de Nodo con el dato pasado en el primer parametro. Además asigna un valor 
	 * //booleano al segundo parámetro para que se pueda verificar si el primer parametro era un Nodo o no.
	 * $esNodo=null;
     * $nodo3 = Nodo::Nodo("soy nodo 3", $esNodo);
     * if ($esNodo){
     *    echo "el parametro de entrada era un nodo";
     * }else{
     *    echo "el parametro de entrada no era un nodo"; // Imprime esto
     * }
     * echo $nodo3->id();//2
	 * 
     * // Caso 4: se le pasa un parametro Nodo y un segundo parametro por referencia (crea una 
	 * //nueva instancia de Nodo con el dato pasado en el primer parametro. Además asigna un valor 
	 * //booleano al segundo parámetro para que se pueda verificar si el primer parametro era un Nodo o no.
	 * $esNodo=null;
     * $nodo3 = Nodo::Nodo($nodo3, $esNodo);
     * if ($esNodo){
     *    echo "el parametro de entrada era un nodo"; // Imprime esto
     * }else{
     *    echo "el parametro de entrada no era un nodo"; 
     * }
     * echo $nodo3->id();//2
	 * ```
	 *
	 * @static
	 * @param mixed $elemento Valor a encapsular o nodo existente.  
	 *                        Si es `null`, se crea un nodo vacío válido.  
	 * @param bool|null &$es_nodo Parámetro de salida por referencia.  
	 *                            Devuelve `true` si `$elemento` ya era un nodo, `false` en caso contrario.
	 * @return Nodo Nodo válido que encapsula el valor recibido.
	 *
	 * @since V2.9.3
	 */
	public static function nodo($elemento=null, &$es_nodo=null): mixed{
		if ($elemento instanceof static){
			$es_nodo=true;
			return $elemento;
		}
		$nodo = new static();
		self::$superestructura[$nodo->id()]=$nodo;
		$es_nodo=false;
		$nodo->dato=$elemento;
		return $nodo;
	}


    /**
     * Elimina un nodo tatalmente del sistema (Interfaz FabricaDeNodos)
	 * 
     * 🔗 Interfaz:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 * 
	 * Este método intenta eliminar el nodo, incluyendo de la superestructura
     * y de los nodos especiales (si corresponde). Devuelve `true` en
 	 * caso de éxito.
     *
	 * ⚠️ Condición imprescindible: el nodo no debe tener enlaces hacia el
	 * incluso si provienen desde sí mismo. Si existen, la operación devuelve `false` y lanza
	 * un error.  
     *
     * ---
     * 🔗 Métodos de creación relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear crear()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato crear_con_dato()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_id crear_con_id()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_nodo nodo()}
	 * 
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_nodos cantidad_de_nodos()}
     *
	 * ---
	 * ⚠️ Nota importante sobre PHP:
	 * - Si se invoca `eliminar($nodo);` y luego se usa `$nodo->dato()`,
	 *   la llamada seguirá funcionando, salvo que se haga también
	 *   `unset($nodo);` fuera de la función.
	 * - Un `unset` dentro de `eliminar()` solo borraria la referencia local,
	 *   no afecta la referencia externa.  
	 * - El destructor `__destruct()` se ejecuta únicamente cuando la **última
	 *   referencia** al objeto desaparece.
	 * 
     * ---
     * Ejemplo de uso:
     * ```php
     * // Crear un nodo
     * $nodo = Nodo::crear_con_dato("Eliminarme");
     *
     * // Eliminar el nodo
     * $resultado = Nodo::eliminar($nodo);
     * var_dump($resultado); // true
     *
     * // Intentar eliminar un nodo con referencias
     * $nodoA = Nodo::crear_con_dato("A");
     * $nodoB = Nodo::crear_con_dato("B");
     * $nodoA->_adyacente($nodoB);
     * var_dump(Nodo::eliminar($nodoB)); // false
     * ```
     *
     * @static
     * @param Nodo $nodo Nodo a eliminar.
     * @return bool `true` si fue eliminado, `false` si no pudo eliminarse,
     *                   `null` si el parámetro no es válido.
     */
	public static function eliminar($nodo): bool|null {
		// Validación del parámetro: debe ser instancia de Nodo
		if (!($nodo instanceof static)) {
			static::_error("el parámetro no es de la clase Nodo");
			return null;
		}

		// Caso 1: El nodo solo tiene 0 referencia
		if ($nodo->referencias === 0) {
			// Se elimina de la superestructura y nodos especiales
			$enlace=$nodo->id();
			unset(Nodo::$superestructura[$enlace]);
			unset(Nodo::$nodos_especiales[$enlace]);
			// Si el nodo tiene adyacentes, reducir la referencia de cada uno
			if ($nodo->adyacentes!==null) {
				foreach ($nodo->adyacentes as $nodo2) {
					$nodo2->referencias--;
				}
			}
			
			return true;
		}
		// Caso 3: El nodo tiene más referencias → no se puede eliminar
		static::_error("debe eliminar todos los enlaces que enlazan hacia el nodo antes de intentar eliminarlo ");
		return false;
	}
    /**
	 * Elimina un nodo que solo tiene autoenlaces (Interfaz FabricaDeNodos)
	 * 
     * 🔗 Interfaz:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodos.html FabricaDeNodos}
	 * 
	 * Elimina un nodo que solo tiene autoenlaces (enlaces hacia sí mismo).
	 * 
     * ⚠️ **Este método está obsoleto**:
     * 
     * Ya no corresponde a la responsabilidad de la clase manejar la eliminación de autoenlaces.
     * El programador debe asegurarse de limpiar manualmente todos los enlaces —incluyendo los
     * autoenlaces— antes de invocar el 
     * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar 
     * método de eliminación estándar}.
	 * 
	 * Si el nodo tiene autoenlaces pueden eliminarse usando el metodo 
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente() eliminar_adyacente} 
	 * que elimina los enlaces uno por uno; o el metodo
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes() eliminar_adyacentes}
	 * que elimina todos los enlaces que salen del nodo, incluyendo los que apuntan a sí mismo
	 * 
     * @deprecated Este metodo ya no debe usarse
	 * @static
     * @param Nodo $nodo Nodo a eliminar.
     * @return bool|null Devuelve true si se eliminó, false si no fue posible, o null si el parámetro no es válido.
     */
	static public function eliminar_autoenlazado($nodo) {
		if (!($nodo instanceof Nodo)) {
			static::_error("el parámetro no es de la clase Nodo");
			return null;
		}

		// Contar enlaces que apuntan al mismo nodo (autoenlaces)
		$contauto = 0;
		$contcomunes=0;
		$id = $nodo->id();
		if ($nodo->adyacentes!==null) {
			foreach ($nodo->adyacentes as $nodo2) {
				if ($id === $nodo2->id()) {
					$contauto++;
				}else{
					$contcomunes++; //cuenta enlaces comunes, si tiene algun enlace no cumple lo de autoenlazado
				}
			}
		}

		// Calcular referencias externas (descontando autoenlaces)
		$numref = $nodo->referencias - $contauto;
		
		if ($numref === 0 && $contcomunes===0 ) {
			// Caso normal
			//Nodo::$superestructura->eliminar_enlace($id);
			unset(Nodo::$superestructura[$id]);
			unset(Nodo::$nodos_especiales[$id]);
			return true;
		} /*elseif ($numref === 2 && $nodo->es_especial()) {
			// Caso nodo especial
			Nodo::$superestructura->eliminar_enlace($id);
			Nodo::$nodos_especiales->eliminar_enlace($id);
			return true;
		}*/

		// No cumple condiciones para eliminar
		return false;
	}
	/*FUNCION*******************************************
		Nombre: es_nodo(elemento)
		
		Interfaz: Construccion y destruccion

		Caso de uso: saber si es nodo
		
		Agregado en version: 

		+--------------------------------------------
		Precondiciones:

		+--------------------------------------------
		Datos de entrada: 
			$elemento el elemento que se desea saber si es o no un nodo

	+--------------------------------------------
		Notas:

	+--------------------------------------------
		Cuerpo:
	*/
	static public function es_nodo($elemento){
		if (!($elemento instanceof Nodo)){
			//self::_alerta("el nodo que intenta validar no es una instancia de la interfaz Nodo");
			return false;
		}else{
			return true;
		}
	}
	/*-------------------------------------------
		Datos de salida: true si el elemento es una instancia de la clase nodo, false en caso contrario.

	+--------------------------------------------
		Poscondiciones: 

	+--------------------------------------------
		Fin de es_nodo(elemento)

	*********************************************/	

	
/*************************************************************************************************************/////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//interfaz de acceso a la superestructura ***********************************************************************//
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*************************************************************************************************************/////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/////


	/**
	 * Determina si existen nodos en la superestructura (Interfaz AccesoASuperestructura).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-AccesoASuperestructura.html#method_hay_nodos_en_superestructura hay_nodos_en_superestructura()}
	 *
	 * Verifica si la superestructura global contiene nodos registrados.  
	 * Devuelve `true` si el array estático `Nodo::$superestructura` **no está vacío**,  
	 * lo que indica que existen nodos actualmente en la red.  
	 * 
	 * Este método aprovecha la inicialización estática de `superestructura` (como `[]`),  
	 * evitando verificaciones redundantes con `null` o estructuras no inicializadas.
	 *
	 * ---
	 * 🔗 Otros métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_superestructura superestructura()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_agregar_a_superestructura agregar_a_superestructura()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_limpiar_superestructura limpiar_superestructura()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_hay_adyacentes hay_adyacentes()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes()}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * if (Nodo::hay_nodos_en_superestructura()) {
	 *     echo "Hay nodos registrados en la superestructura.";
	 * } else {
	 *     echo "No hay nodos cargados.";
	 * }
	 * ```
	 *
	 * @note Esta comprobación es extremadamente ligera: simplemente evalúa `Nodo::$superestructura !== []`.
	 * 
	 * @return bool `true` si existen nodos en la superestructura, `false` si está vacía.
	 */
	public static function hay_nodos_en_superestructura(): bool{
		return Nodo::$superestructura !== [];
	}
	/**
	 * Obtiene un nodo existente por su identificador (Interfaz AccesoASuperestructura).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-AccesoASuperestructura.html#method_nodo_por_id nodo_por_id()}
	 *
	 * Devuelve el nodo registrado en la superestructura con el id especificado.  
	 * Si no existe ningún nodo con ese id, retorna `null` y genera una alerta controlada.
	 *
	 * ---
	 * 🔗 Otros métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_hay_nodos_en_superestructura hay_nodos_en_superestructura()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_agregar_a_superestructura agregar_a_superestructura()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_de_superestructura eliminar_de_superestructura()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_id id()}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_es_id_especial es_id_especial()}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::nodo_por_id("A12");
	 * if ($nodo) {
	 *     echo "Nodo encontrado: " . $nodo->id();
	 * } else {
	 *     echo "No existe nodo con ese id.";
	 * }
	 * ```
	 *
	 * @note Esta función asume que `Nodo::$superestructura` es un array indexado por id.
	 *
	 * @param string $id Identificador del nodo que se desea recuperar.
	 * @return ?Nodo El nodo correspondiente o `null` si no se encuentra.
	 */
	public static function nodo_por_id(string $id): ?Nodo
	{
		if (Nodo::$superestructura === []) {
			static::_alerta("Nodo::nodo_por_id(id) — No existe ningún nodo en la superestructura.");
			return null;
		}

		if (isset(Nodo::$superestructura[$id])) {
			return Nodo::$superestructura[$id];
		}

		static::_alerta("Nodo::nodo_por_id(id) — No existe nodo con ese id.");
		return null;
	}

	/**
	 * Ejecuta una función de callback sobre todos los nodos existentes en la superestructura.
	 *
	 * @param {string} token Token de seguridad que autoriza la operación.
	 * @param callable $funcion    Función a ejecutar para cada nodo. Recibe el nodo como primer argumento, 
	 *                             seguido de los parámetros adicionales indicados en $parametros.
	 * @param mixed ...$parametros Parámetros opcionales adicionales que se pasarán al callback.
	 * @return array|null          Un array asociativo con los resultados devueltos por cada ejecución del callback,
	 *                             indexado por el ID del nodo. Devuelve `null` si no existe la superestructura
	 *                             o si está vacía.
	 * 
	 * @example
	 * ```php
	 * Nodo::por_cada_nodo_ejecutar(function($nodo) {
	 *     echo "Nodo: " . $nodo->id();
	 * });
	 * ```
	 *
	 * @see Nodo::nodo_por_id()
	 * @see Nodo::adyacente()
	 */
	static public function por_cada_nodo_ejecutar(string $token, callable $funcion, mixed ...$parametros): ?array {
		echo ("<br>a".$token."<br>*b*".self::$token."<br>c");
		if ($token===self::$token){
			if (count(Nodo::$superestructura)==0) {
				static::_alerta("alerta no existe adyacente");
				return null;
			}

			$resultados = [];
			foreach (Nodo::$superestructura as $id => $nodo) {
				if ($nodo) {
					$resultados[$id] = $funcion($nodo, ...$parametros);
				}
			}
			
			return $resultados;
		}else{
			static::_alerta("PELIGRO, INTENTO DE ACCESO NO AUTORIZADO");
			throw new \Exception("PELIGRO, INTENTO DE ACCESO NO AUTORIZADO", 1);
			
			//return null;
		}
	}	
    /**
     * Vacía completamente la superestructura de nodos en memoria.
     *
     * @usecase Restablece la estructura principal a su estado inicial,
     * eliminando todos los nodos, enlaces y referencias especiales.
     *
     * @param string $token Token de seguridad que valida la autorización
     *                      para realizar la operación. Debe coincidir con self::$token.
     *
     * @return bool|null Devuelve `true` si la operación se realizó con éxito,
     *                   o `null` si se detectó un intento de acceso no autorizado.
     *
     * @behavior
     * - Si el token coincide con el interno:
     *   - Reinicia `self::$superestructura` como un nuevo array vacío.
     *   - Reinicia el contador `self::$cant` a `0`.
     *   - Limpia `self::$nodos_especiales`.
     * - Si el token no coincide, se registra un error mediante `_error()`
     *   y no se modifica el estado interno.
     *
     * @notes
     * - Este método se usa antes de cargar una nueva superestructura
     *   desde una fuente persistente (por ejemplo, SQLite o IndexedDB espejo).
     * - Su invocación directa está restringida a componentes del sistema
     *   que posean el token válido.
     * - Evita fugas de memoria y garantiza consistencia entre sesiones.
     *
     * @future
     * - Podría implementarse un evento de notificación o callback
     *   (`onReset`) para informar a otros módulos.
     * - También podría añadirse registro histórico de limpiezas
     *   para diagnóstico o auditoría.
     *
     * @example
     * // Ejemplo de uso desde un controlador autorizado
     * $token = Nodo::obtener_token();
     * $resultado = Nodo::vaciar_superestructura($token);
     * if ($resultado === true) {
     *     echo "Superestructura reiniciada correctamente.";
     * } else {
     *     echo "Error: token inválido o acceso no autorizado.";
     * }
     */
    public static function vaciar_superestructura(string $token): ?bool
    {
        if ($token === self::$token) {
            static::$superestructura = [];
            static::$cant = 0;
            static::$nodos_especiales = [];
            return true;
        } else {
            static::_error("Intento de acceso no autorizado");
            return null;
        }
    }

	/**
	 * Llave de seguridad interna utilizada para autorizar operaciones sensibles
	 * sobre la superestructura de nodos.
	 *
	 * Este token es generado por la clase Nodo y entregado únicamente al Controlador
	 * durante el proceso de registro, con el fin de validar las operaciones que
	 * invoquen directa o indirectamente a {@see Nodo::por_cada_nodo_ejecutar()}.
	 *
	 * @var string Token de seguridad único generado por la clase Nodo.
	 * 
	 * @see Nodo::registrar_controlador()
	 */
	static protected $token=null;
	/**
	 * Registra el controlador principal que gestionará las operaciones sobre la superestructura.
	 *
	 * Durante el registro, el Controlador recibe el token de seguridad interno que le permitirá
	 * autorizar el uso de {@see Nodo::por_cada_nodo_ejecutar()} en contextos controlados.
	 *
	 * @param string $controlador Instancia del Controlador que será registrada.
	 * @return void
	 *
	 * @example
	 * ```php
	 * $controlador = Controlador::instancia();
	 * Nodo::registrar_controlador($controlador);
	 * ```
	 *
	 * @see Nodo::$token
	 * @see Nodo::por_cada_nodo_ejecutar()
	 */
	static public function registrar_controlador(string $controlador){

		if (class_exists($controlador) && method_exists($controlador, 'recibir_token')) {
           // echo "KKKKK";
			if (self::$token===null){
				self::$token=generarUUID();
			}	
			$controlador::recibir_token(self::$token);
        }
	}
/******************************************************************************************************************/
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
//interfaz de acceso a los nodos especiales *********************************************************************/*/
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
/****************************************************************************************************************///
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*//////
	
	
    /**
     * Indica si existen nodos especiales en la superestructura.
     *
     * Comprueba si el arreglo estático `$nodos_especiales` contiene
     * al menos un nodo. Este método es estático y actúa sobre el
     * conjunto global de nodos especiales gestionados por la clase `Nodo`.
     *
     * Métodos relacionados:
     * - {@link ./classes/Nodo.html#method_por_cada_nodo_especial_ejecutar Nodo::por_cada_nodo_especial_ejecutar()}
     *
     * @return bool `true` si existen nodos especiales, `false` si el arreglo está vacío.
     */
	static public function hay_nodos_especiales(): bool
	{
		return Nodo::$nodos_especiales !== [];
	}


    /**
     * Ejecuta una función sobre cada nodo especial (Interfaz AccesoAEspeciales).
     *
     * Recorre todos los nodos almacenados en `$nodos_especiales` y ejecuta
     * la función indicada sobre cada uno, pasando parámetros adicionales
     * si fueran necesarios. Devuelve un arreglo asociativo con los resultados
     * indexados por el identificador del nodo.
     *
     * ---
     * 🔗 Otros métodos complementarios:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_hay_nodos_especiales Nodo::hay_nodos_especiales()}
     *
     * ---
     * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_nodo_ejecutar Nodo::por_cada_nodo_ejecutar()}
     *
     * ---
     * Ejemplo de uso:
     * ```php
     * $resultados = Nodo::por_cada_nodo_especial_ejecutar(
     *     function($nodo) { return $nodo->id(); }
     * );
     * ```
     *
     * @note Devuelve `null` si no existen nodos especiales.
     * @param callable $funcion Función a ejecutar por cada nodo.
     * @param mixed ...$parametros Parámetros adicionales que se pasarán a la función.
     * @return ?array Resultados indexados por id, o `null` si no hay nodos especiales.
     */
	static public function por_cada_nodo_especial_ejecutar(callable $funcion, mixed ...$parametros): ?array {

		if (count(Nodo::$nodos_especiales)===0) {
			static::_alerta("no hay nodos especiales");
			return null;
		}

		$resultados = [];
		foreach (Nodo::$nodos_especiales as $id => $nodo) {
			if ($nodo) {
				$resultados[$id] = $funcion($nodo, ...$parametros);
			}
		}
		
		return $resultados;
	}




	/*FUNCION*******************************************
		Nombre: guardar_superestructura($nombrem, $metodo="sql")
		
		Interfaz: de acceso a la super estructura

		Caso de uso: guardar la superestructura
		
		Agregado en version: 2.9.1

		+--------------------------------------------
		Precondiciones:

		+--------------------------------------------
		Datos de entrada: el $nombre con el que se va a guardar tiene que ser un strig, 
		el $metodo con el que se va a guardar por ahora sera "sql"
	+--------------------------------------------
		Notas:	
	+--------------------------------------------
		Cuerpo:
	*/
	/*static public function guardar_superestructura($nombre, $metodo="sql"){
		if (!is_string($nombre)){
			static::_error("Nodo::guardar_superestructura(nombre, metodo), el nombre no es un string");
			return null;
		}
		if (!is_string($metodo)){
			static::_error("Nodo::guardar_superestructura(nombre, metodo), el metodo no es un string");
			return null;
		}
		switch (strtoupper($metodo)){
			case "SQL": return PerdurarSuperestructura::guardar($nombre);
		}
		
	}*/

	/*-------------------------------------------
		Datos de salida: 

	+--------------------------------------------
		Poscondiciones: Se guarda la superestrucura en la base de datos sql con el nombre dado
	+--------------------------------------------
		Fin de guardar_superestructura()

	*********************************************/
	/*FUNCION*******************************************
		Nombre: cargar_superestructura($nombrem, $metodo="sql")
		
		Interfaz: de acceso a la super estructura

		Caso de uso: guardar la superestructura
		
		Agregado en version: 2.9.1

		+--------------------------------------------
		Precondiciones:

		+--------------------------------------------
		Datos de entrada: el $nombre con el que se guardo tiene que ser un strig, 
		el $metodo con el que se va a guardar por ahora sera "sql"
	+--------------------------------------------
		Notas:	
	+--------------------------------------------
		Cuerpo:
	*/
	/*static public function cargar_superestructura($nombre, $metodo="sql"){
		if (!is_string($nombre)){
			static::_error("Nodo::cargar_superestructura(nombre, metodo), el nombre no es un string");
			return null;
		}
		if (!is_string($metodo)){
			static::_error("Nodo::cargar_superestructura(nombre, metodo), el metodo no es un string");
			return null;
		}
		switch (strtoupper($metodo)){
			case "SQL": return PerdurarSuperestructura::cargar_sql($nombre);
		}
		
	}
*/
	/*-------------------------------------------
		Datos de salida: 

	+--------------------------------------------
		Poscondiciones: Se carga la superestrucura
	+--------------------------------------------
		Fin de guardar_superestructura()

	*********************************************/
	/*FUNCION*******************************************
		Nombre: eliminar_superestructura($nombrem, $metodo="sql")
		
		Interfaz: de acceso a la super estructura

		Caso de uso: guardar la superestructura
		
		Agregado en version: 2.9.1

		+--------------------------------------------
		Precondiciones:

		+--------------------------------------------
		Datos de entrada: el $nombre con el que se guardo, tiene que ser un strig, 
		el $metodo con el que se va a eliminar por ahora sera "sql"
	+--------------------------------------------
		Notas:	
	+--------------------------------------------
		Cuerpo:
	*/
	/*static public function eliminar_superestructura($nombre, $metodo="sql"){
		if (!is_string($nombre)){
			static::_error("Nodo::eliminar_superestructura(nombre, metodo), el nombre no es un string");
			return null;
		}
		if (!is_string($metodo)){
			static::_error("Nodo::eliminar_superestructura(nombre, metodo), el metodo no es un string");
			return null;
		}
		echo "mamita";
		switch (strtoupper($metodo)){
			case "SQL": return PerdurarSuperestructura::eliminar_sql($nombre);
		}
	}
*/
	/*-------------------------------------------
		Datos de salida: 

	+--------------------------------------------
		Poscondiciones: Se carga la superestrucura
	+--------------------------------------------
		Fin de eliminar_superestructura()

	*********************************************/
	/*FUNCION*******************************************
		Nombre: existe_superestructura_guardada($nombrem, $metodo="sql")
		
		Interfaz: de acceso a la super estructura

		Caso de uso: comprueba si existe superestructura guardada
		
		Agregado en version: 2.9.2

		+--------------------------------------------
		Precondiciones:

		+--------------------------------------------
		Datos de entrada: el $nombre con el que se guardo tiene que ser un strig, 
		el $metodo con el que se va a guardar por ahora sera "sql"
	+--------------------------------------------
		Notas:	
	+--------------------------------------------
		Cuerpo:
	*/
	/*static public function existe_superestructura_guardada($nombre, $metodo="sql"){
		if (!is_string($nombre)){
			static::_error("Nodo::existe_superestructura_guardada(nombre, metodo), el nombre no es un string");
			return null;
		}
		if (!is_string($metodo)){
			static::_error("Nodo::existe_superestructura_guardada(nombre, metodo), el metodo no es un string");
			return null;
		}
		switch (strtoupper($metodo)){
			case "SQL": return PerdurarSuperestructura::existe_sql($nombre);
		}
		
	}*/

	/*-------------------------------------------
		Datos de salida: Devuelve true o false si existe o no la superestructura guardada

	+--------------------------------------------
		Poscondiciones: 
	+--------------------------------------------
		Fin de existe_superestructura_guardada()

	*********************************************/





/////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Interface Datos********************************************************************////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Establece el dato dentro del nodo (Interfaz Datos).
	 *
	 * Forma parte de la interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Datos.html Datos}.
	 * 
	 * Este método recibe un valor de cualquier tipo y lo asigna
	 * como contenido interno del nodo. 
	 * 
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_dato dato()} Para obtener el dato almacenado en el nodo.
	 * 
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $nodo->_dato("Hola Mundo");
	 * echo $nodo->dato(); // Devuelve: Hola Mundo
	 * ```
	 * @param mixed $dato El valor a almacenar (puede ser cualquier tipo: número, cadena, objeto, etc.).
	 * @return void No devuelve nada, solo modifica el estado interno del nodo.
	 * @public
	 */
	public function _dato($dato){
		$this->dato=$dato;
	}
	
	/**
	 * Devuelve el dato almacenado en el nodo (Interfaz Datos).
	 *
	 * Forma parte de la interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Datos.html Datos}.
	 * 
	 * Este método retorna el valor previamente asignado mediante
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method__dato _dato()}.
	 * Si no existe ningún dato, devuelve null.
	 *
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__dato _dato()} Para asignar un dato al nodo.
	 * 
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $nodo->_dato(42);
	 * echo $nodo->dato(); // Imprime: 42
	 * $otroNodo = Nodo::crear();
	 * echo $otroNodo->dato(); // Imprime: 
	 * ```
	 * 
	 * @return mixed|null El dato almacenado o null si no se asignó ninguno.
	 * @public
	 */
	public function dato(){
        if ($this->dato!==null){
		    return $this->dato;
        }else{
            return null;
        }
	}


/*************************************************************************************************************/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
//INTERFACE PARA EL MANEJO DE ADYACENTES********************************************/////////////////////////*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
/*************************************************************************************************************/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

    /**
	 * Verifica si el nodo tiene al menos un adyacente (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
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
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente()}
     *
     * ---
     * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * if ($nodo->tiene_adyacente()) {
	 *     echo "El nodo tiene adyacentes<br>";
	 * }else{
	 *     echo "El nodo no tiene adyacentes<br>";//imprime esto
	 * }
	 * $otroNodo = Nodo::crear();
	 * $nodo->_adyacente($otroNodo);
	 * if ($nodo->tiene_adyacente()) {
	 *     echo "El nodo tiene adyacentes<br>";//imprime esto
	 * }else{
	 *     echo "El nodo no tiene adyacentes<br>";
	 * }
	 * ```
	 *
	 * @note Internamente utiliza la colección `$this->adyacentes`. 
	 * esta funcion va a tener que quedar solo de manera testimnial o prototipica siendo reemp`lazada por codigo
	 * directo (if) que es mas eficiente que llamar a esta funcion
	 * @return bool Devuelve **true** si existe al menos un adyacente, o **false** en caso contrario.
	 * @public
	 */
	public function tiene_adyacente(): bool{
		if ($this->adyacentes===null){
			return false;
		}
		if (!count($this->adyacentes)){
			return false;
		}else{
			return true;
		}
	}





    /**
     * Verifica si el nodo tiene como adyacente al nodo indicado (Interfaz Adyacentes).
     *
     * 🔗 Interfaz:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacente}
     *
     * Comprueba si el nodo actual enlaza directamente hacia el nodo pasado como parámetro.  
     * Para optimizar, se valida tanto que el nodo actual posea adyacentes salientes 
     * como que el nodo objetivo tenga conexiones entrantes.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a()}
     *
     * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
     *
     * ---
     * Ejemplo de uso:
     * ```php
     * $n1 = Nodo::crear_con_dato("A");
     * $n2 = Nodo::crear_con_dato("B");
     * $n1->_adyacente_en($n2, "enlaceAB");
     *
     * if ($n1->tiene_adyacente_a($n2)) {
     *     echo "A tiene a B como adyacente";
     * }
     * ```
     *
     * @note Solo devuelve el nombre del enlace si realmente existe; `false` en caso contrario.
     *
     * @param Nodo $nodo Nodo a verificar.
     * @return string|false Nombre del enlace si existe, `false` en caso contrario.
	 * @public
	 * @since 3.2.3
     */
	public function tiene_adyacente_a($nodo){
		if (!($nodo instanceof Nodo)){
			self::_error("el nodo que intenta combrobar no es una instancia de la clase Nodo");
			return false;
		};
		if ($this->tiene_adyacente() && $nodo->tiene_incidente()){
			$id=$nodo->id();
			foreach ($this->adyacentes as $enlace => $nodoaux){
				if ($nodoaux->id()===$id){
					return $enlace;
				}
			}
		}
		return false;
	}



	/**
	 * Valida un nombre de enlace (Interfaz Adyacentes}).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacente}
	 *
	 * Este método verifica que el nombre de un enlace sea válido antes de asignarlo.  
	 * Solo se permiten valores `int` o `string`, y se prohíben nombres que puedan confundirse con `false` o `null` en la lógica del grafo (`0`, `"0"`, `""`).  
	 * Usar siempre este método si no se esta seguro antes de usar un nombre de enlace.
     *
	 *  ---
     * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * 
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nombre1="23"; //válido
	 * $nombre2="veintitres"; //válido
	 * $nombre3=23; //inválido
	 * $nombre4="0"; //inválido
	 * if (Nodo::validar_nombre_enlace($nombre1)) {
	 * 		echo ("el nombre de enlace $nombre1 es válido<br>");
	 * }else{
	 *     Nodo::_error("Nombre de enlace $nombre1 es inválido");
	 * }
	 * if (Nodo::validar_nombre_enlace($nombre2)) {
	 * 		echo ("el nombre de enlace $nombre2 es válido<br>");
	 * }else{
	 *     Nodo::_error("Nombre de enlace $nombre2 es inválido");
	 * }
	 * if (Nodo::validar_nombre_enlace($nombre3)) {
	 * 		echo ("el nombre de enlace $nombre3 es válido<br>");
	 * }else{
	 *     Nodo::_error("Nombre de enlace $nombre3 es inválido");
	 * }
	 * if (Nodo::validar_nombre_enlace($nombre4)) {
	 * 		echo ("el nombre de enlace $nombre4 es válido<br>");
	 * }else{
	 *     Nodo::_error("Nombre de enlace $nombre4 es inválido");
	 * }
	 * 
	 * Nodo::imprimir_errores();
	 * ```
	 *
	 * @note Devuelve `false` para valores inseguros aunque sean strings.
	 *
	 * @param int|string $enlace Nombre del enlace a validar
	 * @return bool `true` si es válido, `false` en caso contrario
	 * @since 3.2.3
	 * @static
	 */
	public static function validar_nombre_enlace($enlace): bool {
		if (!is_string($enlace)) {
			return false;
		}
		if ($enlace === "" || $enlace === "0") {
			return false;
		}
		return true;
	}

	/**
	 * Asigna un adyacente con nombre único (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Agrega un nodo como adyacente generando automáticamente un nombre de enlace único
	 * basado en el `id()` del nodo destino.  
	 * Si ya existe un enlace con ese nombre, se crean variantes incrementales (`id.1`, `id.2`, ...).
	 *
	 * ---
	 * 🔗 Método complementario:	
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 *
	 * ---
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $otro1 = Nodo::crear_con_id("ejemplo");
	 * $otro2 = Nodo::crear_con_id("otro_ejemplo");
	 * 
	 * $enlace1=$nodo->_adyacente($otro1); // crea enlace "ejemplo" a $otro1
	 * $enlace2=$nodo->_adyacente($otro2); // crea enlace "otro_ejemplo" a $otro2
	 * $enlace3=$nodo->_adyacente($otro1); // crea enlace "ejemplo.1" a $otro1
	 * 
	 * echo "En el enlace ".$enlace1." se agrego el nodo ".$nodo->adyacente($enlace1)->id()."<br>"; //ejemplo / ejemplo
     * echo "En el enlace ".$enlace2." se agrego el nodo ".$nodo->adyacente($enlace2)->id()."<br>"; //otro_ejemplo / otro_ejemplo
     * echo "En el enlace ".$enlace3." se agrego el nodo ".$nodo->adyacente($enlace3)->id()."<br>"; //ejemplo.1 / ejemplo
	 * ```
	 *
	 * @param Nodo $un_nodo Nodo que se desea enlazar
	 * @return null|string Nodo adyacente recién asignado
	 * @public
	 */
	public function _adyacente($un_nodo): ?string {
		if (!($un_nodo instanceof Nodo)){
			static::_error("el parametro debe ser una instancia de Nodo");
			return null;
		}
		//inicializacion perezosa
		if ($this->adyacentes===null){
			$this->adyacentes=[];
		}
		$cont=1;
		$id=(string)$un_nodo->id();
		$enlace=$id;
		while (isSet($this->adyacentes[$enlace])){
			$enlace=$id.".".$cont;
			$cont++;
		}
		//asigno adyacente
		$this->adyacentes[$enlace]=$un_nodo;
		//sumo la referencias del nodo enlazado
		$un_nodo->referencias++;
		return $enlace;
	}

    /**
	 * Asigna un nodo adyacente en un enlace dado (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Permite enlazar un nodo adyacente en un enlace identificado por un string. 
	 * 
	 * Si ya existía un nodo en esa posición, puede reemplazarse explícitamente con `$reemplazar=true`.
	 * Si `$reemplazar=false` (comportamiento predeterminado), y ya hay un nodo en el enlace dado
	 * genera un mensaje de error.
	 * 
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodoA = Nodo::crear_con_dato("A");
	 * $nodoB = Nodo::crear_con_dato("B");
	 * 
	 * // asigno nodoB como adyacente de nodoA bajo el enlace "conecta"
	 * $nodoA->_adyacente_en($nodoB, "conecta");
	 * echo $nodoA->adyacente("conecta")->dato(); //imprime "B"
	 * ```
	 *
	 * @note El método incrementa automáticamente el contador de referencias del nodo enlazado.
	 *
	 * @param Nodo $un_nodo Nodo que se desea asignar como adyacente.
	 * @param string $enlace Nombre identificador del enlace.
	 * @param bool $reemplazar Parametro opcional: indica qué debe hacer la funcion si encuentra
	 * un nodo asignado previamente en el enlace indicado. Si es true, reemplaza el adyacente,
	 * si es false (valor predeterminado), no lo reemplaza y genera un mensaje de error.
	 * @return bool `true` si la asignación fue exitosa, `false` en caso de error.
	 * @public
	 */
	public function _adyacente_en($un_nodo, $enlace, $reemplazar=false): bool{
		if (!($un_nodo instanceof Nodo)){
			static::_error("el nodo que intenta asignar no es un Nodo");
			return false;
		};
		if (!static::validar_nombre_enlace($enlace)) {
			static::_error("el enlace al intenta asignar debe ser un string");
			return false;
		}
		//inicializacion perezosa
		if ($this->adyacentes===null) {
			$this->adyacentes=[];
		}
		//reviso a ver si no existia un nodo en esa posicion
		if (array_key_exists($enlace, $this->adyacentes)){
			if ($reemplazar){
				$this->adyacentes[$enlace]->referencias--;
				//$this->adyacentes[$enlace]->eliminar_incidente($enlace);
			}else{
				static::_error("ya existia un nodo en el enlace que intenta asignar");
				return false;
			}
		}
		//asigno adyacente
		$this->adyacentes[$enlace]=$un_nodo;
		//sumo la referencias del nodo enlazado
		$un_nodo->referencias++;
		return true;
	}

	/**
	 * Devuelve el nodo adyacente en el enlace especificado (Interfaz Adayacentes)
	 * 
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Comprueba si existe un nodo en el enlace indicado y lo devuelve;  
	 * si no existe, devuelve `null`. El enlace debe ser `int` o `string`.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $n1 = Nodo::crear_con_dato("A");
	 * $n2 = Nodo::crear_con_dato("B");
	 * $n1->_adyacente_en($n2, "enlaceAB");
	 *
	 * $ady = $n1->adyacente("enlaceAB");
	 * if ($ady) echo "Nodo adyacente: ".$ady->dato();
	 * ```
	 *
	 * @note Devuelve `null` si no hay nodo en el enlace.
	 * @param int|string $enlace El identificador del enlace a consultar
	 * @return Nodo|null Nodo adyacente si existe, `null` en caso contrario
	 */
	public function adyacente($enlace): ?Nodo{
		if (!Nodo::validar_nombre_enlace($enlace)) {// esto se deja porque si intento acceder al array con algo q no sea un entero o un string en php salta un warning
			self::_error("El enlace debe ser un string");
			return null;
		}
		if ($this->adyacentes === null) {
			return null; // todavía no hay mapa creado
		}

		return $this->adyacentes[$enlace] ?? null;
	}

	/**
	 * Devuelve una copia de todos los adyacentes (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html# adyacentes}
	 *
	 * Retorna todos los nodos adyacentes del nodo actual en una estructura independiente, 
	 * asegurando que sea una "foto" del estado al momento de la llamada.  
	 * Si el nodo no tiene adyacentes, devuelve `null`.  
	 * Se utiliza para obtener de manera segura los enlaces actuales sin exponer la referencia interna.
	 *
	 * ---
	 * 🔗 Métodos complementario:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $nodo->_adyacente_en(Nodo::crear_con_dato_e_id("hola", "Id_hola"), "enlace hola");
	 * $nodo->_adyacente_en(Nodo::crear_con_dato_e_id("chau", "Id_chau"), "enlace chau");
	 * $todos = $nodo->adyacentes();
	 * if ($todos !== null) {
	 *     foreach ($todos as $enlace => $ady) {
	 *         echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 * 	       unset($todos[$enlace]); //no modifico los enlaces en el nodo original
	 *     }
	 * }
	 * echo "compruebo eliminacion en resultado<br>";
	 * foreach ($todos as $enlace => $ady) {
	 *     echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 * }
	 * echo "comprobacion nuevo resultado<br>";
	 * $todos2 = $nodo->adyacentes();
	 * foreach ($todos2 as $enlace => $ady) {
	 *    echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 * }
	 * ```
	 *
	 * @note Se devuelve una copia superficial del array interno de adyacentes.
	 * @public
     * @since Modificado en V3.2.3
	 * @return ?array Array asociativo con enlaces y nodos, o `null` si no hay adyacentes
	 */
	public function adyacentes(): array|null{
		if (!$this->tiene_adyacente()) {
				return null;
		}else {
			return $this->adyacentes;
		}
	}

	/**
	 * Elimina un enlace del nodo (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Busca y elimina el nodo asociado a un enlace dado.  
	 * Valida que el enlace sea del tipo correcto, que existan adyacentes 
	 * y que el enlace exista realmente entre los adyacentes del nodo.
	 *   
	 * Si todo es correcto elimina el enlace y retorna el nodo que estaba en ese enlace;
	 * en caso contrario devuelve null y lanza mensajes de error (si el enlace no es valido)
	 * o alertas (si no existia el enlace a eliminar)
	 * 
	 * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar Nodo::eliminar($nodo)}
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_enlaces eliminar_enlaces}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 * 
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_id("nodo");
	 * $otro = Nodo::crear_con_id("otro");
	 * $nodo->_adyacente_en($otro, "A");
	 * echo "Se agregó el nodo ".$nodo->adyacente("A")->id()."<br>";
	 * $eliminado = $nodo->eliminar_enlace("A");
	 * if ($eliminado !== null) {
	 *     echo "Se eliminó el nodo con ID: " . $eliminado->id() ."<br>";// Imprime: "otro"
	 * }
	 * echo "Comprobación de que realmente se eliminó<br>";
	 * $ady=$nodo->adyacente("A");
	 * if (!$ady){
	 * 		echo "No existe adyacente en 'A'"; //imprime esto
	 * }else{
	 * 		echo "Hasta aca no llega";
	 * }
	 * ```
	 *
	 * @note Disminuye el contador interno `referencias` del nodo eliminado.
	 *
	 * @param string $enlace Nombre del enlace a eliminar
	 * @return ?Nodo Nodo eliminado o `null` si no se pudo eliminar
	 * @public
     * @since Modificado en V3.2.3
	 * @deprecated usar eliminar_adyacente
	 */
	public function eliminar_enlace($enlace): ?Nodo{
		// Validación de tipo
		if (!static::validar_nombre_enlace($enlace)) {
			self::_error("el enlace a eliminar no es valido");
			return null;
		}
		// verificar inicialización perezosa
		if ($this->adyacentes===null) {
			self::_alerta("no hay adyacentes para eliminar");
			return null;
		}
		// Verificar existencia del enlace
		if (!array_key_exists($enlace,$this->adyacentes)) {
			self::_alerta("el enlace ".$enlace." que se intenta eliminar no existe");
			return null;
		}
		
		$eliminado = $this->adyacentes[$enlace];
		$eliminado->referencias--;
		unset($this->adyacentes[$enlace]);
		return $eliminado;
	}

	/**
	 * Elimina todos los enlaces del nodo (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Elimina todas las conexiones salientes del nodo.  
	 * Si no existen adyacentes, lanza una alerta y devuelve un array vacío.  
	 * Antes de eliminar, genera una copia de los enlaces actuales y los devuelve. 
	 *
	 * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar Nodo::eliminar($nodo)}
	 * 
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_enlace eliminar_enlace}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_id("nodo");
	 * $otroA = Nodo::crear_con_id("otroA");
	 * $otroB = Nodo::crear_con_id("otroB");
	 * $nodo->_adyacente_en($otroA, "A");
	 * $nodo->_adyacente_en($otroB, "B");
	 * echo "Se agregaron enlaces: <br>";
	 * $todos = $nodo->adyacentes();
	 * if ($todos) {
	 *     foreach ($todos as $enlace => $ady) {
	 *         echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 *     }
	 * }
	 * $copia = $nodo->eliminar_enlaces();
	 * echo "Se aliminaron enlaces: <br>";
	 * if ($copia){
	 * 	  foreach ($copia as $enlace => $ady) {
	 *       echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 * 	  }
	 * }
	 * echo "Comprobacion<br>";
	 * $todos2=$nodo->adyacentes();
	 * if ($todos2){
	 * 		echo "Aún tiene adyacentes, algo falló";
	 * } else{
	 * 		echo "No tiene ningún adyacente"; //imprime esto
	 * }
	 * ```
	 *
	 * @note Se devuelve una copia de todos los adyacentes conectados por los enlaces eliminados.
	 *
	 * @param
	 * @return Nodo[] Array de nodos eliminados, o array vacío si no había adyacentes
	 * @public
	 * @deprecated usar elimniar_adyacentes
	 */
	public function eliminar_enlaces(): array {
		if ($this->adyacentes===null or !count($this->adyacentes)>0) {
			self::_alerta("no hay enlaces para eliminar");
			return []; 
		}
		$copia=$this->adyacentes;
		foreach ($this->adyacentes as $eliminado) {
			$eliminado->referencias--;
		}
		$this->adyacentes=[];
		return $copia;
	}

	/**
	 * Elimina un adyadente del nodo (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Busca y elimina el nodo asociado a un enlace dado.  
	 * Valida que el enlace sea del tipo correcto, que existan adyacentes 
	 * y que el enlace exista realmente entre los adyacentes del nodo.
	 *   
	 * Si todo es correcto elimina el enlace y retorna el nodo que estaba en ese enlace;
	 * en caso contrario devuelve null y lanza mensajes de error (si el enlace no es valido)
	 * o alertas (si no existia el enlace a eliminar)
	 * 
	 * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar Nodo::eliminar($nodo)}
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 * 
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_id("nodo");
	 * $otro = Nodo::crear_con_id("otro");
	 * $nodo->_adyacente_en($otro, "A");
	 * echo "Se agregó el nodo ".$nodo->adyacente("A")->id()."<br>";
	 * $eliminado = $nodo->eliminar_adyacente("A");
	 * if ($eliminado !== null) {
	 *     echo "Se eliminó el nodo con ID: " . $eliminado->id() ."<br>";// Imprime: "otro"
	 * }
	 * echo "Comprobación de que realmente se eliminó<br>";
	 * $ady=$nodo->adyacente("A");
	 * if (!$ady){
	 * 		echo "No existe adyacente en 'A'"; //imprime esto
	 * }else{
	 * 		echo "Hasta aca no llega";
	 * }
	 * ```
	 *
	 * @note Disminuye el contador interno `referencias` del nodo eliminado.
	 *
	 * @param string $enlace Nombre del enlace a eliminar
	 * @return ?Nodo Nodo eliminado o `null` si no se pudo eliminar
	 * @public
     * @since Modificado en V3.2.3
	 */
	public function eliminar_adyacente($enlace): ?Nodo{
		// Validación de tipo
		if (!static::validar_nombre_enlace($enlace)) {
			self::_error("el enlace a eliminar no es valido");
			return null;
		}
		// verificar inicialización perezosa
		if ($this->adyacentes===null) {
			self::_alerta("no hay adyacentes para eliminar");
			return null;
		}
		// Verificar existencia del enlace
		if (!array_key_exists($enlace,$this->adyacentes)) {
			self::_alerta("el enlace ".$enlace." que se intenta eliminar no existe");
			return null;
		}
		
		$eliminado = $this->adyacentes[$enlace];
		$eliminado->referencias--;
		unset($this->adyacentes[$enlace]);
		return $eliminado;
	}

	/**
	 * Elimina todos los adyacentes del nodo (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Elimina todas las conexiones salientes del nodo.  
	 * Si no existen adyacentes, lanza una alerta y devuelve un array vacío.  
	 * Antes de eliminar, genera una copia de los enlaces actuales y los devuelve. 
	 *
	 * ⚠️ Importante: Este método no elimina los nodos del sistema. Si se eliminan
	 * todos los enlaces que conectan a un nodo este aún permanece en el sistema 
	 * como nodo suelto a menos que se use el metodo estatico
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar Nodo::eliminar($nodo)}
	 * 
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_id("nodo");
	 * $otroA = Nodo::crear_con_id("otroA");
	 * $otroB = Nodo::crear_con_id("otroB");
	 * $nodo->_adyacente_en($otroA, "A");
	 * $nodo->_adyacente_en($otroB, "B");
	 * echo "Se agregaron enlaces: <br>";
	 * $todos = $nodo->adyacentes();
	 * if ($todos) {
	 *     foreach ($todos as $enlace => $ady) {
	 *         echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 *     }
	 * }
	 * $copia = $nodo->eliminar_adyacentes();
	 * echo "Se aliminaron enlaces: <br>";
	 * if ($copia){
	 * 	  foreach ($copia as $enlace => $ady) {
	 *       echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	 * 	  }
	 * }
	 * echo "Comprobacion<br>";
	 * $todos2=$nodo->adyacentes();
	 * if ($todos2){
	 * 		echo "Aún tiene adyacentes, algo falló";
	 * } else{
	 * 		echo "No tiene ningún adyacente"; //imprime esto
	 * }
	 * ```
	 *
	 * @note Se devuelve una copia de todos los adyacentes conectados por los enlaces eliminados.
	 *
	 * @param
	 * @return Nodo[] Array de nodos eliminados, o array vacío si no había adyacentes
	 * @public
	 */
	public function eliminar_adyacentes(): array {
		if ($this->adyacentes===null or !count($this->adyacentes)>0) {
			self::_alerta("no hay enlaces para eliminar");
			return []; 
		}
		$copia=$this->adyacentes;
		foreach ($this->adyacentes as $eliminado) {
			$eliminado->referencias--;
		}
		$this->adyacentes=[];
		return $copia;
	}
	/**
	 * Devuelve la cantidad de adyacentes (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Retorna el número total de nodos adyacentes actualmente vinculados al nodo.  
	 * Si no existen adyacentes, devuelve `0`.  
	 * Este método permite conocer de manera rápida el grado de salida del nodo.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $otro1 = Nodo::crear();
	 * $otro2 = Nodo::crear();
	 * $nodo->_adyacente_en($otro1, "X");
	 * $nodo->_adyacente_en($otro2, "Y");
	 * echo $nodo->cantidad_de_adyacentes(); // 2
	 * ```
	 *
	 * @note Si no hay adyacentes inicializados, retorna 0 directamente.
	 * @param 
	 * @return int Cantidad de adyacentes del nodo
	 * @public
	 * @sice 2.9.4
	 */
	public function cantidad_de_adyacentes(): int{
		if ($this->adyacentes!==null){
			return count($this->adyacentes);
		}
		return 0;
	}

    /**
	 * Ejecuta una función sobre cada nodo adyacente (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html}
	 *
	 * Permite recorrer todos los nodos adyacentes y ejecutar sobre cada uno una función provista por el usuario.  
	 * La función recibe como parámetros el nodo, el nombre del enlace y los parámetros adicionales que se le pasen al método.  
	 * Devuelve un array asociativo con los resultados de cada ejecución, indexados por el nombre del enlace.  
	 *  
	 * Si no existen adyacentes, emite una alerta con `_alerta()` y retorna `null`.  
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $nodoA = Nodo::crear_con_dato("A");
	 * $nodoB = Nodo::crear_con_dato("B");
	 * $nodo->_adyacente_en($nodoA, "conectaA");
	 * $nodo->_adyacente_en($nodoB, "conectaB");
	 *
	 * // ejecuta una función sobre cada adyacente
	 * $resultados = $nodo->por_cada_adyacente_ejecutar(function($nodo, $enlace) {
	 *     return "hay nodo con dato: " . $nodo->dato(); 
	 * });
	 *
	 * if ($resultados){
	 * 	  foreach ($resultados as $enlace => $resultado) {
	 *       echo "En el enlace '$enlace' $resultado <br>";
	 * 	  }
	 * }
	 * //imprime
	 * //En enlace 'conectaA' hay nodo con dato:A
     * //En enlace 'conectaB' hay nodo con dato:B
	 * ```
	 *
	 * @note Devuelve `null` si no existen adyacentes.  
	 *
	 * @param callable $funcion Función a ejecutar sobre cada nodo adyacente.
	 * @param mixed ...$parametros Parámetros adicionales a pasar a la función.
	 * @return array|null Resultados de cada ejecución, indexados por enlace, o `null` si no existen adyacentes.
	 */
	public function por_cada_adyacente_ejecutar(callable $funcion, mixed ...$parametros): ?array {
		if (!$this->tiene_adyacente()) {
			static::_alerta("alerta no existe adyacente");
			return null;
		}

		$resultados = [];
		foreach ($this->adyacentes as $enlace => $nodo) {
			if ($nodo) {
				$resultados[$enlace] = $funcion($nodo, $enlace, ...$parametros);
			}
		}
		
		return $resultados;
	}

    /**********************************************************************************************
     *  INTERFAZ INCIDENTES (INSTANCIA)
     * 
     **********************************************************************************************/

    /**
	 * Verifica si el nodo es adyacente de al menos un nodo (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incientes}
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
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * if ($nodo->es_nodo_suelto()) {
	 *     echo "El nodo está aislado dentro del grafo.";
	 * }
	 * ```
	 *
	 * @note Utiliza la propiedad interna `$this->referencias` y el método `es_especial()`.
	 *
	 * @return bool Devuelve **true** si el nodo está considerado suelto, o **false** en caso contrario.
	 * @since 2.9
	 */		
	public function es_nodo_suelto(){
		
		/*if($this->referencias===1){
			return true;
		}elseif($this->referencias===2 && $this->es_especial()){
			return true;
		}*/
		return $this->referencias===0;
	}	
	/**
	 * Verifica si el nodo es adyacente de al menos un nodo (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
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
	 * {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * 
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente()}
     *
     * ---
     * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a()}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 * 
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * if ($nodo->tiene_incidente()) {
	 *     echo "El nodo tiene conexiones entrantes.<br>";
	 * }else{
	 *     echo "El nodo no tiene conexiones entrantes.<br>";//imprime esto
	 * }
	 * $otroNodo= Nodo::crear();
	 * $otroNodo->_adyacente($nodo);
	 * if ($nodo->tiene_incidente()) {
	 *     echo "El nodo tiene conexiones entrantes.<br>"; //imprime esto
	 * }else{
	 *     echo "El nodo no tiene conexiones entrantes.<br>";
	 * }
	 * 
	 * ```
	 *
	 * @note Utiliza la propiedad interna `$this->referencias` y el método `es_especial()`.
	 * @return bool Devuelve **true** si el nodo está considerado suelto, o **false** en caso contrario.
	 * @public
	 * @since 3.2.3
	 */		
	public function tiene_incidente(){
	/*	if($this->referencias===1){
			return false;
		}elseif($this->referencias===2 && $this->es_especial()){
			return false;
		}*/
		return $this->referencias!==0;
	}
	
	/**
     * Verifica si el nodo actual es adyacente del nodo indicado (Interfaz Incidentes).
     *
     * 🔗 Interfaz:
     * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
     *
     * Comprueba si el nodo actual se encuentra enlazado desde el nodo pasado como parámetro.  
     * Para optimizar, se valida tanto que el nodo actual posea conexiones entrantes 
     * como que el nodo objetivo tenga adyacentes salientes.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a()}
     *
     * ---
     * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
     *
     * ---
     * Ejemplo de uso:
     * ```php
     * $nA = Nodo::crear_con_dato("A");
     * $nB = Nodo::crear_con_dato("B");
     *
     * $nB->_adyacente_en($nA, "enlaceBA");
     *
     * if ($nA->tiene_incidente_a($nB)) {
     *     echo "B es incidente de A";
     * }
     * ```
     *
     * @note Solo devuelve el nombre del enlace si realmente existe; `false` en caso contrario.
     * @public
	 * @since 3.2.3
     * @param Nodo $nodo Nodo a verificar.
     * @return string|false Nombre del enlace si existe, `false` en caso contrario.
     */
	public function tiene_incidente_a($nodo){
		if (!($nodo instanceof Nodo)){
			self::_error("el nodo que intenta combrobar no es una instancia de la clase Nodo");
			return false;
		};
		if ($this->tiene_incidente() && $nodo->tiene_adyacente()){
			$id=$this->id();
			foreach ($nodo->adyacentes as $enlace => $nodoaux){
				if ($nodoaux->id()==$id){
					return $enlace;
				}
			}
		}
		return false;
	}

	/**
	 * Devuelve la cantidad de incidentes (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
	 *
	 * Retorna el número total de nodos con enlaces vinculados al nodo al nodo actual.
	 * Es decir cuenta la cantidad de enlaces "entrantes"  
	 * Si no existen incidentes, devuelve `0`.  
	 * Este método permite conocer de manera rápida el grado de entrada del nodo.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * 
	 * ---
	 * 🔗 Otros métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacente adyacente}
     * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente _adyacente}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method__adyacente_en _adyacente_en}  
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear();
	 * $otro1 = Nodo::crear();
	 * $otro2 = Nodo::crear();
	 * $otro1->_adyacente_en($nodo, "X");
	 * $otro2->_adyacente_en($nodo, "X");
	 * echo $nodo->cantidad_de_incidentes(); // 2
	 * ```
	 *
	 * @param 
	 * @return int Cantidad de incidentes del nodo
	 * @public
	 * @sice 3.2.3
	 */
	public function cantidad_de_incidentes(): int{
		/*if ($this->es_especial()){
			return $this->referencias-2;
		}else{
			return $this->referencias-1;
		}*/
		return $this->referencias;
	}

	/*************************************************************************************************************/
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	//INTERFACE PARA IMPRIMIR LOS NODOS*************************************************/////////////////////////*/
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	/*************************************************************************************************************/

		//*AGREGO FUNCION PARA IMPRIMIR UN NODO EN PANTALLA, ESTA OPCION DEBERIA SER USADA SOLO POR PROGRAMADORES

	/**
	 * Imprime el nodo en formato HTML (Interfaz Impresion).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Impresion.html Interfaz Impresion}
	 *
	 * Muestra en pantalla una representación visual del nodo con su id, su dato, 
	 * los adyacentes y el número de referencias. Se utiliza principalmente con fines 
	 * de depuración y diagnóstico visual del grafo en un entorno web.
	 *
	 * ⚠️ Debe ser usada únicamente por programadores o herramientas de depuración.  
	 * No se recomienda para salida de usuario final.
	 *
	 * ---
	 * 🔗 Otros métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_imprimir2 imprimir2()} — versión en texto plano.
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_id id()} — obtiene el identificador del nodo.
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_dato dato()} — obtiene el dato asociado al nodo.
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_tiene_adyacente tiene_adyacente()} — comprueba adyacencias.
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo->imprimir(); // imprime el nodo como bloque HTML
	 * ```
	 *
	 * @note Utiliza `echo` para generar directamente HTML. No devuelve valor.
	 * @return void
	 */
	public function imprimir() {
		$id=$this->id();
		echo "<div id='nodo-" . $id . "' style='margin-bottom:20px;'>";
		echo ">>NODO " . $id;
		if ($this->es_especial()) echo " (ESP)";
		echo " - Dato: ";

		$dato = $this->dato();
		if (is_string($dato)) {
			echo $dato;
		} elseif ($dato === null) {
			echo "null";
		} else {
			echo "este dato no es un string";
		}
		
		echo "<br/>Adyacentes:<br/>";
		if ($this->tiene_adyacente()) {
			echo "<ul>";
			foreach ($this->adyacentes as $enlace => $nodo) {
				echo "<li>[$enlace] => <a href='#nodo-" . $nodo->id() . "'>" . $nodo->id() . "</a></li>";
			}
			echo "</ul>";
		} else {
			echo "No tiene<br/>";
		}

		echo "Número de referencias a él: " . $this->referencias . "<br/>";
		echo "Fin Nodo <a href='#inicio'>↑ Volver al inicio</a></div><br/>";
	}

	/**
	 * Imprime todos los nodos de la superestructura en formato HTML.
	 *
	 * Esta función está destinada a tareas de depuración visual.  
	 * Recorre todos los nodos de la superestructura y ejecuta la función `imprimir()` de cada uno,
	 * generando una representación HTML completa de toda la red de nodos.
	 *
	 * @return bool Devuelve `true` si se imprimieron nodos, `false` si la superestructura está vacía.
	 */
	static public function imprimir_superestructura() {
		if (!Nodo::hay_nodos_en_superestructura()) {
			static::_alerta("Nodo::imprimir_superestructura() — la superestructura está vacía");
			return false;
		}
		echo "<a id='inicio'></a>";
		$funcion = function($nodo) {
			$nodo->imprimir();
		};
		self::por_cada_nodo_ejecutar(self::$token, $funcion, null);
		return true;
	}


	/**
	 * Imprime el nodo en formato texto plano (Interfaz Impresion).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Impresion.html Interfaz Impresion}
	 *
	 * Presenta una salida en consola (shell) del nodo con su id, dato y enlaces adyacentes.
	 * Es útil para depuración en entornos sin salida gráfica (CLI).
	 *
	 * ---
	 * 🔗 Otros métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_imprimir imprimir()} — versión HTML.
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_id id()} — obtiene el identificador del nodo.
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_dato dato()} — obtiene el dato asociado al nodo.
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo->imprimir2(); // imprime el nodo en la shell
	 * ```
	 *
	 * @note Devuelve `true` si se ejecutó correctamente.
	 * @return bool
	 */
	public function imprimir2() {
		echo "\n>>NODO " . $this->id();
		if ($this->es_especial()) echo " (ESP)";
		echo " - Dato: ";

		$dato = $this->dato();
		if (is_string($dato) || is_numeric($dato)) {
			echo $dato;
		} elseif ($dato === null) {
			echo "null";
		} else {
			echo "este dato no es un string";
		}

		echo "\nAdyacentes:\n";
		if ($this->tiene_adyacente()) {
			foreach ($this->adyacentes as $enlace => $nodo) {
				echo "\n[$enlace] => " . $nodo->id();
			}
		} else {
			echo "No tiene\n";
		}

		echo "\nNúmero de referencias a él: " . $this->referencias;
		echo "\nFin Nodo\n";
		return true;
	}
	/**
	 * Imprime todos los nodos de la superestructura en formato de texto (modo consola).
	 *
	 * Esta función está destinada a la depuración por consola o entornos sin salida HTML.
	 * Recorre todos los nodos de la superestructura y ejecuta la función `imprimir2()` en cada uno.
	 *
	 * @return bool Devuelve `true` si se imprimieron nodos, `false` si la superestructura está vacía.
	 */
	static public function imprimir_superestructura2() { 
		if (!Nodo::hay_nodos_en_superestructura()) {
			static::_alerta("Nodo::imprimir_superestructura2() — la superestructura está vacía");
			return false;
		}

		$funcion = function($nodo) {
			$nodo->imprimir2();
		};
		Nodo::por_cada_nodo_ejecutar(self::$token, $funcion, null);

		return true;
	}

}



//FIN CLASE Nodo
//$nodo=nodo::crear_con_dato("nnnnanduùû");
//echo "hola";
/*$nodo=Nodo::crear_con_dato_e_id("a","a");
$nodo->_adyacente_en($b=Nodo::crear_con_dato_e_id("b","b"),"b");
$n0=Nodo::crear_con_dato("0");
Nodo::eliminar($n0);
$b->_adyacente_en($n0=Nodo::crear_con_dato("0"),"0");
$b->_adyacente_en($c=Nodo::crear_con_dato_e_id("c","c"),"c");
$n0->_adyacente_en($n1=Nodo::crear_con_dato("1"),"1");
$n1->_adyacente_en($c,"c");
$d=Nodo::crear_con_dato_e_id("d","d");
$n2=Nodo::crear_con_dato("2");
Nodo::guardar_superestructura("BETA9", "sQl");
/*
$nodo->eliminar_enlace("aca");
$nodo->_adyacente_en(Nodo::crear_con_dato("BBBB"),"aca");
$nodo->_adyacente_en(Nodo::crear_con_id("c"),"aca");
Nodo::crear();*/
//Nodo::eliminar($a);

//Nodo::guardar_superestructura("BETA5", "sQl");*/

/*if (Nodo::existe_superestructura_guardada("BETA6", "sQl")){
	echo "existe";
}else{
	echo "no existe";
};*//*
echo "TTTTTTTTTTTT";
$nodo=Nodo::crear();
echo "Dato1_:".$nodo->dato();
$nodo->_dato("lkhj");
echo "Dato2:".$nodo->dato();*/
//Nodo::eliminar(Nodo::nodo_por_id("1"));

//Nodo::eliminar_nodos_sueltos();
//Sup
	/*	$agregar_hijo=function ($nodo, $enlace, $parametro1, $parametro2){
			echo $enlace.":".$parametro1."-".$parametro2;
			
			/*$arbol=
			$act=$arbol->actual();
			$arbol->_hmi($res=Nodo::crear_con_dato($enlace));
			$arbol->_adyacente_en($nodo,"nodo");
			$arbol->_actual($act);
			return $res;
		};*/

		
		//lo convierto a nodo
	/*	$n1=Nodo::crear_con_dato(1);
		$n2=Nodo::crear_con_dato(2);
		$n3=Nodo::crear_con_dato(3);
		$n4=Nodo::crear_con_dato(4);
		$n5=Nodo::crear_con_dato(5);
		$n0=Nodo::crear_con_dato(0);
		$n0->_adyacente_en($n1,"1");
		$n0->_adyacente_en($n2,"2");
		$n0->_adyacente_en($n3,"3");
		$n0->_adyacente_en($n4,"4");
		$n0->_adyacente_en($n5,"5");
		$n0->eliminar_enlace("5");
		//$n0->por_cada_adyacente_ejecutar($agregar_hijo,"tata", "tita");
		echo "####".$n0->cantidad_de_adyacentes();
		*/
		/*Nodo::imprimir_superestructura();
		Nodo::imprimir_errores();
		PerdurarSuperestructuraString::guardar_sql("sopa");*/
?>