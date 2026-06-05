<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Interfaces\Energia;
use Iteradores\Nodos\Interfaces\FabricaDeNodosElectricos;
use Iteradores\Nodos\Interfaces\Fase;
use Iteradores\Nodos\Interfaces\Incidentes;
use Iteradores\Nodos\Interfaces\IncidentesDobleVia;
use Iteradores\Nodos\Nodo;

include_once ('Nodo.php');
include_once ('Interfaces/IncidentesDobleVia.php');
include_once ('Interfaces/FabricaDeNodosElectricos.php');
include_once ('Interfaces/Energia.php');
include_once ('Interfaces/Fase.php');

/**
 * Clase NodoElectrico
 *
 * Todos los enlaces que generen estos nodos seran de "doble via", por eso cada Nodo maneja dos estrucuras
 * internas: adyacentes e incidentes. conteniendo los enlaces de salida y de entrada respectivamente.
 *
 * Extiente la clase Nodo implementando FabricaDeNodosElectricos (estatica), Fase, Energia e IncidentesDobleVia.
 *
 * ###INTERFACES
 *
 * ##FASE
 * Vamos a manejar el concepdo de "fase" o "frecuencia" de trabajo.
 * Por ahora la fase es simplemente un string con un nombre
 * similar a los nombres de ayacentes, pero generando un plano o
 * matriz con estos o llave doble (fase, nombre_de_enlace).
 * Es añadirle una dimencion mas a los adyacentes que ahora trabajaran en un plano.
 *
 * La fase sera concretamente una propiedad ligada a la superestructura y manejada por
 * el controlador. Pero para eso tenemos que tener en la clase nodo (junto a la superestructura)
 * una propiedad privada (o protegida) llamada "fase" con el nombre de la fase en string,
 * de modo que cada instancia de nodo pueda consultarla para saber donde insertar los adyacentes
 * dentro de su (ahora) matris de adyacentes.
 *
 * La matris de edyacentes no tendra tamaños prestablecido, sera mas bien un array de arrays
 * de tamaños variables.
 *
 * La mayor complejidad viene a la hora de manejar los incidentes. No alcanza una estructura de dos
 * dimenciones para poder representarlos ya que puede haber coliciones en los nombres de los enlaces.
 * Me explico: supon que tenemos 3 nodos a, b, y c, con a y b apuntando ambos a traves de un enlace
 * "enlace a c"; en este caso en c, tendriamos dos incidentes que comparten el nombre del enlace
 * incidente, por lo que es necesario agregar una tercera dimencion que podria ser -y lo hacemos asi-
 * el id del nodo incidente. Ver las propiedades privadas (o protegias) adyacentes e incidentes para
 * ver bien la estrucura
 *
 *
 * ##DINAMICOS
 * Hay que agregar por_cada_fase_ejecutar y establecer_fase
 * ###MODIFIFICACIONES
 *
 * ##INTERFAZ ADYACENTES e INCIDENTES
 * hay que reescribir cada funcion de la interfaz "adyacentes" para que cumplan con el nuevo estandar
 *
 * ##HISTORIA
 * **V0.0.1:**
 * **V0.0.2.251110:** terminada la interfaz Adyacentes y Incidentes vamos a completar toda la clase
 * **V0.0.3.251117:** terminada toda la clase realizo pruebas, probado hasta ahora: todos los crear
 * 					establecer_fase, el sistema de tokens, agregados los imprimir. verificado tambien
 * 					_adyacente y adyacente_en junto con _incidente_en, por_cada_nodo_ejecutar tambien
 * 					funciona. me falta probar los eliminar. Falta _energia. Falta ver que pasa con los
 * 					guardar sql json y xml.
 * **V0.0.3.251118:** probados tiene_adyacente, tiene_incidente, por_cada_adyacente_ejecutar y
 * 					por_cada_incidente_ejecutar.
 * 					agregados _ejecutar_cuando_satura_por_fase y ejecutar_cuando_agota_por_fase
 * 					probados toda la interfaz Energia. echos los imprimir2 faltan los elimina.
 * 					echo eliminar. falta eliminar autoenlazado, pero lo voy a dejara para dentro
 * 					de un ratito.
 * **V0.0.3.251121:** voy a intentar dar la vuelta final tanto por el lado de php como por el lado de js
 * 					voy a ir comprobando la completitud de cada interfaz y probando su funcionamiento
 * 					ademas de ver que se cree medianamente bien la documentacion.
 * **V0.0.3.251124:** termine y deje prolijo la doc de todos los crear falta los eliminar para terminar la interfaz
 * 					FabricaDeNodosElectricos
 *
 * 					FALTAN DOS INTERFACES FABRICADENODOSELECTRICOS Y ENERGIA
 *
 *
 *
 *
 * @class
 * @author Ignacio David Baigorria
 * @package Iteradores\Nodos
 * @version 0.0.3
 * @since 1.2
 * @extends Nodo
 * @implements Interfaces\IncidentesDobleVia
 * @implements Interfaces\FabricaDeNodosElectricos
 * @implements Interfaces\Energia
 * @implements Interfaces\Fase
 */
class NodoElectrico extends Nodo implements IncidentesDobleVia, FabricaDeNodosElectricos, Energia, Fase
{
	/**
	 * Manejador de Incidentes
	 * @var array | array<array<array<NodoElectrico>>>>
	 * Doble llave para acceder al array de los incidentes:[id del incidente][nombre del encase]
	 */
	private $incidentes;

	/**
	 * Manejador de Salidas
	 * @var
	 */
	// private $adyacentes=null;

	/**
	 * Configuracion Interna
	 * @var
	 */
	private $CI = null;

	/**********************************************************************************************
	 *  INTERFAZ FASE (ESTATICA)
	 **********************************************************************************************/

	/**
	 * Fase de trabajo actual (global)
	 * @var string
	 */
	protected static $fase = 'a';

	/**
	 * Historial de fases utilizadas (clave = nombre de fase, valor = true)
	 * @var array
	 */
	protected static $fases = [];

	/**
	 * Establece la fase global en la que trabajan todos los nodos.
	 * Requiere token de autorización.
	 *
	 * @param string $token Token de autorización
	 * @param string $fase  Nombre de la nueva fase (no vacío)
	 * @return void
	 *
	 * @example
	 * NodoElectrico::_fase($token, 'beta');
	 */
	public static function _fase(string $token, string $fase): void
	{
		
		if (self::$token === $token) {
			self::$fase = $fase;
			self::$fases[$fase] = true;
		} else {
			self::_alerta('INTENSO DE ACCESO NO AUTORIZADO');
		}
	}

	/**
	 * Devuelve la fase global actual (sin necesidad de token).
	 *
	 * @return string
	 */
	public static function fase(): string
	{
		return self::$fase;
	}

	/**
	 * Ejecuta una función por cada fase registrada en el sistema (global).
	 * Este método es estático y recorre TODAS las fases que se hayan usado alguna vez.
	 *
	 * Requiere token de seguridad.
	 *
	 * @param string   $token   Token de autorización
	 * @param callable $funcion Función que recibe (string $fase)
	 * @return void
	 * @since V1.2.6
	 * @example
	 * NodoElectrico::por_cada_fase_global_ejecutar($token, function($fase) {
	 *     echo "Fase global: $fase\n";
	 * });
	 */
	public static function por_cada_fase_global_ejecutar(string $token, callable $funcion): void
	{
		if (self::$token !== $token) {
			self::_alerta('INTENTO DE ACCESO NO AUTORIZADO');
			return;
		}

		foreach (self::$fases as $fase => $usada) {
			$funcion($fase);
		}
	}

	/**********************************************************************************************
	 *  INTERFAZ FASE (INSTANCIA)
	 **********************************************************************************************/

	/**
	 * Ejecuta una función por cada fase en la que el nodo actual tiene actividad
	 * (es decir, tiene al menos un adyacente o un incidente en esa fase).
	 *
	 * Requiere token de seguridad.
	 *
	 * @param string   $token   Token de autorización
	 * @param callable $funcion Función que recibe (string $fase)
	 * @return void
	 *
	 * @example
	 * $nodo->por_cada_fase_ejecutar($token, function($fase) {
	 *     echo "El nodo tiene actividad en: $fase\n";
	 * });
	 */
	public function por_cada_fase_ejecutar(string $token, callable $funcion): void
	{
		if (self::$token !== $token) {
			self::_alerta('INTENTO DE ACCESO NO AUTORIZADO');
			return;
		}

		$fases_unicas = [];

		// Recorrer adyacentes: estructura $this->adyacentes[fase][enlace] = Nodo
		if (is_array($this->adyacentes)) {
			foreach (array_keys($this->adyacentes) as $fase) {
				if (!empty($this->adyacentes[$fase])) {
					$fases_unicas[$fase] = true;
				}
			}
		}

		// Recorrer incidentes: estructura $this->incidentes[idNodo][fase][enlace] = Nodo
		if (is_array($this->incidentes)) {
			foreach ($this->incidentes as $fases_por_nodo) {
				if (is_array($fases_por_nodo)) {
					foreach (array_keys($fases_por_nodo) as $fase) {
						if (!empty($fases_por_nodo[$fase])) {
							$fases_unicas[$fase] = true;
						}
					}
				}
			}
		}

		// Ejecutar callback para cada fase única
		foreach (array_keys($fases_unicas) as $fase) {
			$funcion($fase);
		}
	}

	/******************************************************************************************
	 * Interfaz FabricaDeNodosElectricos
	 * 
	 * 
	 ******************************************************************************************/

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
	protected function __construct()
	{
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
	function __destruct()
	{
		//self::$cant--;
		echo '</br>destruccion!</br>';
	}



	/**
	 * Crea una nueva instancia de vacia de NodoElectrico (Interfaz FabricaDeNodosElectricos)
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
	 *
	 * El constructor de la clase es privado, con lo que se asegura que las instancias
	 * no puedan crearse de forma directa desde el exterior, por lo que éste método es una
	 * de las formas válidas de crear nodos.
	 *
	 * Redefino en la clase hija NodoElectrico y como el resto de los crear tendra dos parametros
	 * extra: $capacidad y $fuga, si no se les asigna ningun valor se les asignara el valo por defecto
	 * (ver:{@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}
	 *  y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO})
	 *
	 * 🔗 Otros métodos de creacion que se pueden usar son:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos Nodo::cantidad_de_nodos()}
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
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.
	 * @return NodoElectrico Una nueva instancia de la clase {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico}.
	 *
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-NodoElectrico.html#$cant Nodo::$cant}),
	 * 		 y lo agrega a la Superestructura
	 *       lo cual permite llevar un registro global de Nodos.
	 */
	public static function crear(int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO, float $fuga = Conf::FUGA_NODO_ELECTRICO): NodoElectrico
	{
		$nodo = parent::crear();
		$nodo->fuga = $fuga;
		$nodo->capacidad = $capacidad;
		return $nodo;
	}

	/**
	 * Crear un nuevo nodo encapsulando el dato recibido (Interfaz FabricaDeNodosElectricos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
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
	 * Redefino en la clase hija NodoElectrico y como el resto de los crear tendra dos parametros
	 * extra: $capacidad y $fuga, si no se les asigna ningun valor se les asignara el valo por defecto
	 * (ver:{@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}
	 *  y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO})
	 *
	 * 🔗 Otros métodos de creacion que se pueden usar son:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos cantidad_de_nodos()}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = Nodo::crear_con_dato("Hola Mundo");
	 * echo $nodo->dato(); // Devuelve: "Hola Mundo"
	 * ```
	 * @note Este método incrementa el contador estático de nodos
	 *       ({@link ./classes/Iteradores-Nodos-NodoElectrico.html#$cant Nodo::$cant}),
	 * 		 y lo agrega a la Superestructura
	 *       lo cual permite llevar un registro global de Nodos.
	 * @static
	 * @param mixed $dato Valor a encapsular en el nuevo nodo.
	 * @param boolean $todos lo dejo por compatibilidad pero en cualquier momento lo borro
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.
	 * @return NodoElectrico Una nueva instancia de la clase {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico}.
	 */
	public static function crear_con_dato($dato, $todos = false, $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO, $fuga = Conf::FUGA_NODO_ELECTRICO): NodoElectrico
	{
		$nodo = parent::crear_con_dato($dato, $todos);
		$nodo->capacidad = $capacidad;
		$nodo->fuga = $fuga;
		return $nodo;
	}

	/**
	 * Crear un nuevo nodo asignándole un identificador válido (Interfaz FabricaDeNodosElectricos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
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
	 * Redefino en la clase hija NodoElectrico y como el resto de los crear tendra dos parametros
	 * extra: $capacidad y $fuga, si no se les asigna ningun valor se les asignara el valo por defecto
	 * (ver:{@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}
	 *  y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO})
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos cantidad_de_nodos()}
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
	 *       ({@link ./classes/Iteradores-Nodos-NodoElectrico.html#$cant Nodo::$cant})
	 *       y lo agrega a la Superestructura.
	 *
	 * @static
	 * @param mixed $id Identificador a asignar al nuevo nodo (debe ser único y *especial*).
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.
	 * @return NodoElectrico|null Instancia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico} con el identificador *especial* en caso de exito, null en caso contrario.
	 */
	public static function crear_con_id($id, $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO, $fuga = Conf::FUGA_NODO_ELECTRICO): NodoElectrico|null
	{
		$nodo = parent::crear_con_id($id);
		if ($nodo !== null) {
			$nodo->capacidad = $capacidad;
			$nodo->fuga = $fuga;
		}
		return $nodo;
	}

	/**
	 * Crear un nuevo nodo encapsulando un dato y asignándole un identificado *especial* (Interfaz FabricaDeNodosElectricos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
	 *
	 * Este método combina las capacidades de {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * y {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}.
	 * Permite instanciar un nodo con un valor cualquiera (primitivo, complejo u otro nodo) y a la vez asignarle
	 * un identificador único *especial* que debe pasar la validación de
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_id_especial es_id_especial()}.
	 *
	 * El constructor de la clase Nodo es privado, de modo que esta función constituye una de las formas válidas de creación de nodos.
	 *
	 * Redefino en la clase hija NodoElectrico y como el resto de los crear tendra dos parametros
	 * extra: $capacidad y $fuga, si no se les asigna ningun valor se les asignara el valo por defecto
	 * (ver:{@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}
	 *  y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO})
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_nodo nodo()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos cantidad_de_nodos()}
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
	 *       ({@link ./classes/Iteradores-Nodos-NodoElectrico.html#$cant Nodo::$cant}),
	 *       y lo registra en la Superestructura para un seguimiento global.
	 *
	 * @static
	 * @param mixed $dato Valor a encapsular en el nodo.
	 * @param mixed $id Identificador del nodo (debe pasar verificación).
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.
	 * @return NodoElectrico|null Instancia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico} con dato
	 * e identificador *especial* en caso de exito, null en caso contrario.
	 */
	public static function crear_con_dato_e_id($dato, $id, $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO, $fuga = Conf::FUGA_NODO_ELECTRICO): NodoElectrico|null
	{
		$nodo = parent::crear_con_dato_e_id($dato, $id);
		if ($nodo !== null) {
			$nodo->capacidad = $capacidad;
			$nodo->fuga = $fuga;
		}
		return $nodo;
	}

	/**
	 * Garantizar que el elemento entregado sea un nodo válido (Interfaz FabricaDeNodosElectricos).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
	 *
	 * Este método recibe un valor cualquiera (o ninguno) o un posible nodo y asegura que el resultado final
	 * sea siempre una instancia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html Nodo}.
	 * - Si no recibe ningun parámetro crea un Nodo vacío totalmente válido
	 * - Si el parámetro recibido **ya es un Nodo**, simplemente lo retorna y marca la variable
	 *   de referencia `$es_nodo` como `true`.
	 * - Si el parámetro **no es un Nodo**, crea uno nuevo con
	 *   {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()},
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
	 * Redefino en la clase hija NodoElectrico y como el resto de los crear tendra dos parametros
	 * extra: $capacidad y $fuga, si no se les asigna ningun valor se les asignara el valo por defecto
	 * (ver:{@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}
	 *  y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO})
	 *
	 * ---
	 * 🔗 Otros métodos de creación:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos cantidad_de_nodos()}
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
	 * @param int $capacidad Opcional. Capacidad maxima de energia del nodo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_CAPACIDAD_NODO_ELECTRICO Conf::CAPACIDAD_NODO_ELECTRICO}.
	 * @param int $fuga Opcional. Fuga de energia por ciclo. El valor por defecto se configura desde:
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_FUGA_NODO_ELECTRICO Conf::FUGA_NODO_ELECTRICO}.
	 * @return NodoElectrico Instancia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico}
	 *
	 * @since V2.9.3
	 */
	public static function nodo($elemento = null, &$es_nodo = null, $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO, $fuga = Conf::FUGA_NODO_ELECTRICO): NodoElectrico
	{
		$nodo = parent::nodo($elemento, $es_nodo);
		if ($nodo !== null && !$es_nodo) {
			$nodo->capacidad = $capacidad;
			$nodo->fuga = $fuga;
		}
		return $nodo;
	}

	/**
	 * Elimina un nodo tatalmente del sistema (Interfaz FabricaDeNodosElectricos)
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodoElectricos.html FabricaDeNodosElectricos}
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear crear()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato crear_con_dato()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_id crear_con_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_crear_con_dato_e_id crear_con_dato_e_id()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_nodo nodo()}
	 *
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar eliminar()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_nodos cantidad_de_nodos()}
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
	 * @param NodoElectrico $nodo Nodo a eliminar.
	 * @return bool `true` si fue eliminado, `false` si no pudo eliminarse,
	 *                   `null` si el parámetro no es válido.
	 */
	public static function eliminar($nodo): bool|null
	{
		// Validación del parámetro: debe ser instancia de Nodo
		if (!($nodo instanceof static)) {
			static::_error('el parámetro no es de la clase Nodo');
			return null;
		}

		// Caso 1: El nodo solo tiene 0 referencia
		if ($nodo->referencias === 0) {
			// Se elimina de la superestructura y nodos especiales
			$enlace = $nodo->id();
			unset(Nodo::$superestructura[$enlace]);
			unset(Nodo::$nodos_especiales[$enlace]);
			// Si el nodo tiene adyacentes, reducir la referencia de cada uno
			if ($nodo->adyacentes !== null) {
				foreach ($nodo->adyacentes as $fase => $adyacentes) {  // recorro las fases
					if ($adyacentes !== null) {
						foreach ($adyacentes as $enlace2 => $nodo2) {
							echo '**n';
							unset($nodo2->incidentes[$fase][$enlace2]);
							// $nodo2->eliminar_incidente($enlace2, $fase);
							$nodo2->referencias--;
							unset($nodo->adyacentes[$fase][$enlace2]);
						}
					}
					unset($nodo->adyacentes[$fase]);
				}
			}
			//	$nodo->imprimir();
			// Decrementar contador global AHORA, no esperar al destructor
			self::$cant--;
			
			// Eliminar la referencia local (opcional, el destructor hará limpieza final)
			unset($nodo);
				return true;
		}
		// Caso 3: El nodo tiene más referencias → no se puede eliminar
		static::_error('debe eliminar todos los enlaces que enlazan hacia el nodo antes de intentar eliminarlo ');
		return false;
	}

	/**
	 * Elimina un nodo que solo tiene autoenlaces (Interfaz FabricaDeNodosElectricos)
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-FabricaDeNodosElectricos.html FabricaDeNodosElectricos}
	 *
	 * Elimina un nodo que solo tiene autoenlaces (enlaces hacia sí mismo).
	 *
	 * ⚠️ **Este método está obsoleto**:
	 *
	 * Ya no corresponde a la responsabilidad de la clase manejar la eliminación de autoenlaces.
	 * El programador debe asegurarse de limpiar manualmente todos los enlaces —incluyendo los
	 * autoenlaces— antes de invocar el
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar
	 * método de eliminación estándar}.
	 *
	 * Si el nodo tiene autoenlaces pueden eliminarse usando el metodo
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente() eliminar_adyacente}
	 * que elimina los enlaces uno por uno; o el metodo
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes() eliminar_adyacentes}
	 * que elimina todos los enlaces que salen del nodo, incluyendo los que apuntan a sí mismo
	 *
	 * @deprecated Este metodo ya no debe usarse
	 * @static
	 * @param NodoElectrico $nodo Nodo a eliminar.
	 * @return bool|null Devuelve true si se eliminó, false si no fue posible, o null si el parámetro no es válido.
	 */
	static public function eliminar_autoenlazado($nodo)
	{  // VOY POR ACA
		if (!($nodo instanceof NodoElectrico)) {
			static::_error('el parámetro no es de la clase NodoElectrico');
			return null;
		}

		// Contar enlaces que apuntan al mismo nodo (autoenlaces)
		$contauto = 0;
		$contcomunes = 0;
		$id = $nodo->id();
		if ($nodo->adyacentes !== null) {
			foreach ($nodo->adyacentes as $fase => $adyacentes2) {  // recorro cada fase
				if ($adyacentes2 !== null) {
					foreach ($adyacentes2 as $enlace2 => $nodo2) {
						if ($id === $nodo2->id()) {
							$contauto++;
						} else {
							$contcomunes++;  // cuenta enlaces comunes, si tiene algun enlace no cumple lo de autoenlazado
						}
					}
				}
			}
		}

		// Calcular referencias externas (descontando autoenlaces)
		$numref = $nodo->referencias - $contauto;

		if ($numref === 0 && $contcomunes === 0) {
			// Caso normal
			// Nodo::$superestructura->eliminar_adyacente($id);
			unset(Nodo::$superestructura[$id]);
			unset(Nodo::$nodos_especiales[$id]);
			return true;
		}  /*elseif ($numref === 2 && $nodo->es_especial()) {
			 // Caso nodo especial
			 Nodo::$superestructura->eliminar_adyacente($id);
			 Nodo::$nodos_especiales->eliminar_adyacente($id);
			 return true;
		 }*/

		// No cumple condiciones para eliminar
		return false;
	}

	/***************************************************************************************
	 * INTERFAZ ADYACENTES (INSTANCIA)
	 *
	 *  Reemplazo de los metodos existentes
	 **************************************************************************************/

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
	 * ⚠️ Importante: verifica las conexiciones de "salida", pero no las de "entrada". Para
	 * verificar las conexiones de entrada use
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * if ($nodo->tiene_adyacente()) {
	 *     echo "El nodo tiene adyacentes<br>";
	 * }else{
	 *     echo "El nodo no tiene adyacentes<br>";//imprime esto
	 * }
	 * $otroNodo = NodoElectrico::crear();
	 * $nodo->_adyacente($otroNodo);
	 * if ($nodo->tiene_adyacente()) {
	 *     echo "El nodo tiene adyacentes<br>";//imprime esto
	 * }else{
	 *     echo "El nodo no tiene adyacentes<br>";
	 * }
	 * ```
	 *
	 * @note Internamente utiliza la colección `$this->S[NodoElectrico::$fase]`.
	 * @return bool Devuelve **true** si existe al menos un adyacente, o **false** en caso contrario.
	 * @public
	 */
	public function tiene_adyacente(): bool
	{
		// echo "das";
		if ($this->adyacentes === null) {
			// echo "das1";
			return false;
		}
		if (!count($this->adyacentes)) {
			// echo "das2";
			return false;
		}
		$faseactual = self::$fase;
		if (!isset($this->adyacentes[$faseactual])) {
			// echo "das3";
			return false;
		}
		if (!count($this->adyacentes[$faseactual])) {
			//	echo "das4";
			return false;
		}
		// echo "das5";
		return true;
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodoA = NodoElectrico::crear_con_dato("A");
	 * $nodoB = NodoElectrico::crear_con_dato("B");
	 * $nodoA->_adyacente_en($nodoB, "enlaceAB");
	 *
	 * $enlace = $nodoA->tiene_adyacente_a($nodoB);
	 * if ($enlace !== false) {
	 *     echo "Existe el enlace '$enlace' desde A hacia B";
	 * } else {
	 *     echo "No existe enlace";
	 * }
	 * ```
	 *
	 * @note Solo devuelve el nombre del enlace si realmente existe; `false` en caso contrario.
	 *
	 * @param Nodo $nodo Nodo a verificar.
	 * @return string|false Nombre del enlace si existe, `false` en caso contrario.
	 * @public
	 * @since 0.0.1
	 */
	public function tiene_adyacente_a($nodo)
	{
		if (!($nodo instanceof NodoElectrico)) {
			Nodo::_error('El nodo que intenta comprobar no es una instancia de la clase NodoElectrico.');
			return false;
		}
		$faseActual = NodoElectrico::$fase;
		if (!isset($this->adyacentes[$faseActual])) {
			return false;
		}
		$idObjetivo = $nodo->id();
		foreach ($this->adyacentes[$faseActual] as $nombreEnlace => $nodoAdyacente) {
			if ($nodoAdyacente->id() === $idObjetivo) {
				return $nombreEnlace;
			}
		}
		return false;
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $n1 = NodoElectrico::crear_con_dato("A");
	 * $n2 = NodoElectrico::crear_con_dato("B");
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
	public function adyacente($enlace): ?Nodo
	{
		echo 'b0';
		if (!Nodo::validar_nombre_enlace($enlace)) {  // esto se deja porque si intento acceder al array con algo q no sea un entero o un string en php salta un warning
			self::_error('El enlace debe ser un string');
			return null;
		}
		echo 'b1';
		if ($this->adyacentes === null) {
			return null;
		}
		echo 'b12';
		if (!count($this->adyacentes)) {
			return null;
		}
		echo 'b13';
		$faseactual = NodoElectrico::$fase;
		echo "<br/>$faseactual<br/>";
		if (!isset($this->adyacentes[$faseactual])) {
			return null;
		}
		echo 'b14';
		if (!count($this->adyacentes[$faseactual])) {
			return null;
		}
		echo 'b15';
		return $this->adyacentes[$faseactual][$enlace] ?? null;
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * $nodo->_adyacente_en(NodoElectrico::crear_con_dato_e_id("hola", "Id_hola"), "enlace hola");
	 * $nodo->_adyacente_en(NodoElectrico::crear_con_dato_e_id("chau", "Id_chau"), "enlace chau");
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
	 * @since V0.0.1
	 * @return ?array Array asociativo con enlaces y nodos, o `null` si no hay adyacentes
	 */
	public function adyacentes(): ?array
	{
		if (!$this->tiene_adyacente()) {
			return null;
		}
		$faseActual = NodoElectrico::$fase;
		if (!isset($this->adyacentes[$faseActual]) || empty($this->adyacentes[$faseActual])) {
			return null;
		}
		return $this->adyacentes[$faseActual];
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * $otro1 = NodoElectrico::crear();
	 * $otro2 = NodoElectrico::crear();
	 * $nodo->_adyacente_en($otro1, "X");
	 * $nodo->_adyacente_en($otro2, "Y");
	 * echo $nodo->cantidad_de_adyacentes(); // 2
	 * ```
	 *
	 * @note Si no hay adyacentes inicializados, retorna 0 directamente.
	 * @param
	 * @return int Cantidad de adyacentes del nodo
	 * @public
	 * @sice 0.0.1
	 */
	public function cantidad_de_adyacentes(): int
	{
		if ($this->adyacentes !== null && count($this->adyacentes) > 0 && isset($this->adyacentes[NodoElectrico::$fase])) {
			return count($this->adyacentes[NodoElectrico::$fase]);
		}
		return 0;
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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 *
	 * ---
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
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
	public function _adyacente($un_nodo): ?string
	{
		if (!($un_nodo instanceof NodoElectrico)) {
			static::_error('el parametro debe ser una instancia de Nodo');
			return null;
		}
		// inicializacion perezosa
		if ($this->adyacentes === null) {
			$this->adyacentes = [];
		}
		$fase = NodoElectrico::$fase;
		if (!isset($this->adyacentes[$fase])) {
			$this->adyacentes[$fase] = [];
		}
		$adyacentes = $this->adyacentes[$fase];
		$cont = 1;
		$id = $un_nodo->id();
		$enlace = (string) $id;
		while (isset($adyacentes[$enlace])) {
			$enlace = $id . '.' . $cont;
			$cont++;
		}
		// asigno adyacente
		echo ' <br/>J' . $fase . $enlace . ' <br/>';
		$this->adyacentes[$fase][$enlace] = $un_nodo;

		$un_nodo->_incidente_en($this, $enlace);
		// sumo la referencias del nodo enlazado
		$un_nodo->referencias++;
		return $enlace;
	}

	/**
	 * Establece un nodo adyacente con nombre de enlace específico (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Crea o reemplaza una relación de adyacencia con otro nodo usando un nombre de enlace
	 * específico. Maneja inicialización perezosa de estructuras y actualiza referencias.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 *
	 * ---
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo1 = NodoElectrico::crear();
	 * $nodo2 = NodoElectrico::crear();
	 * $nodo1->_adyacente_en($nodo2, "enlace_principal", true);
	 * ```
	 *
	 * @note Si $reemplazar es false y el enlace existe, fallará.
	 * @param NodoElectrico $un_nodo Nodo a establecer como adyacente
	 * @param mixed $enlace Nombre del enlace
	 * @param bool $reemplazar Permite reemplazar enlace existente
	 * @return bool True si éxito, false si error
	 * @public
	 * @since 0.0.1
	 */
	public function _adyacente_en($un_nodo, $enlace, $reemplazar = false): bool
	{
		echo 'holanaaaa';
		if (!($un_nodo instanceof NodoElectrico)) {
			static::_error('el nodo que intenta asignar no es un NodoElectrico');
			return false;
		};
		if (!static::validar_nombre_enlace($enlace)) {
			static::_error('el enlace al intenta asignar debe ser un string');
			return false;
		}
		// inicializacion perezosa
		if ($this->adyacentes === null) {
			$this->adyacentes = [];
		}
		$fase = NodoElectrico::$fase;
		if (!isset($this->adyacentes[$fase])) {
			echo 'ma0';
			$this->adyacentes[$fase] = [];
		}
		$adyacentes = $this->adyacentes[$fase];
		// reviso a ver si no existia un nodo en esa posicion
		echo 'ma1';
		if (isset($adyacentes[$enlace])) {
			echo 'ma2';
			if ($reemplazar) {
				echo 'ma3';
				$adyacentes[$enlace]->referencias--;
				$adyacentes[$enlace]->eliminar_incidente($this, $enlace);
			} else {
				echo 'ma4';
				static::_error('ya existia un nodo en el enlace que intenta asignar');
				return false;
			}
		}
		// asigno adyacente
		$this->adyacentes[$fase][$enlace] = $un_nodo;
		$un_nodo->_incidente_en($this, $enlace);
		// sumo la referencias del nodo enlazado
		$un_nodo->referencias++;
		return true;
	}

	/**
	 * Elimina un nodo adyacente específico (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Remueve la relación de adyacencia en el enlace especificado y actualiza las
	 * referencias del nodo eliminado. Devuelve el nodo eliminado o null si no existe.
	 *
	 * ---
	 * 🔗 Métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_incidente eliminar_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 *
	 * ---
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * $eliminado = $nodo->eliminar_adyacente("enlace_especifico");
	 * ```
	 *
	 * @note También elimina la relación incidente correspondiente.
	 * @param mixed $enlace Nombre del enlace a eliminar
	 * @return Nodo|null Nodo eliminado o null si no existe
	 * @public
	 * @since 0.0.1
	 */
	public function eliminar_adyacente($enlace): Nodo|null
	{
		// Validación de tipo
		if (!static::validar_nombre_enlace($enlace)) {
			self::_error('el enlace a eliminar no es valido');
			return null;
		}
		// verificar inicialización perezosa
		if ($this->adyacentes === null) {
			self::_alerta('no hay adyacentes para eliminar');
			return null;
		}
		if ($this->adyacentes[NodoElectrico::$fase] === null) {
			self::_alerta('no hay adyacentes para eliminar en la fase');
			return null;
		}
		// Verificar existencia del enlace
		if (!array_key_exists($enlace, $this->adyacentes[NodoElectrico::$fase])) {
			self::_alerta('el enlace ' . $enlace . ' que se intenta eliminar no existe');
			return null;
		}

		$eliminado = $this->adyacentes[NodoElectrico::$fase][$enlace];
		$eliminado->referencias--;
		$eliminado->eliminar_incidente($this, $enlace);
		unset($this->adyacentes[NodoElectrico::$fase][$enlace]);
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
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar Nodo::eliminar($nodo)}
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear_con_id("nodo");
	 * $otroA = NodoElectrico::crear_con_id("otroA");
	 * $otroB = NodoElectrico::crear_con_id("otroB");
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
	public function eliminar_adyacentes(): array
	{
		if ($this->adyacentes === null or !count($this->adyacentes) > 0) {
			self::_alerta('no hay enlaces para eliminar');
			return [];
		}
		$fase = NodoElectrico::$fase;
		if (!isset($this->adyacentes[$fase])) {
			self::_alerta('no hay enlaces a eliminar en la fase actual');
			return [];
		}
		$copia = $this->adyacentes[$fase];

		foreach ($this->adyacentes[$fase] as $enlace => $eliminado) {
			echo 'Y' . $enlace;
			$eliminado->referencias--;
			$eliminado->eliminar_incidente($this, $enlace);
		}
		$this->adyacentes[$fase] = [];
		return $copia;
	}

	/**
	 * Ejecuta una función por cada nodo adyacente (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Itera sobre todos los nodos adyacentes ejecutando una función callback para cada uno.
	 * La función recibe el nodo adyacente, nombre del enlace y parámetros adicionales.
	 *
	 * ---
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_incidente_ejecutar por_cada_incidente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo->por_cada_adyacente_ejecutar(function($ady, $enlace) {
	 *     echo "Procesando adyacente en enlace: $enlace";
	 * }, $parametro_extra);
	 * ```
	 *
	 * @note Devuelve array con resultados de cada ejecución.
	 * @param callable $funcion Función a ejecutar
	 * @param mixed ...$parametros Parámetros adicionales para la función
	 * @return array|null Array asociativo con resultados o null si no hay adyacentes
	 * @public
	 * @since 0.0.1
	 */
	public function por_cada_adyacente_ejecutar(callable $funcion, mixed ...$parametros): ?array
	{
		if (!$this->tiene_adyacente()) {
			static::_alerta('alerta no existe adyacente');
			return null;
		}

		$resultados = [];
		foreach ($this->adyacentes[NodoElectrico::$fase] as $enlace => $nodo) {
			echo '<br/>fir';
			if ($nodo) {
				echo '<br/>fir1';
				$resultados[$enlace] = $funcion($nodo, $enlace, ...$parametros);
			}
		}

		return $resultados;
	}

	/**
	 * Devuelve la cantidad total de adyacentes (salientes) sumando todas las fases.
	 *
	 * A diferencia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes()}
	 * que solo cuenta en la **fase actual**, este método recorre todas las fases
	 * en las que el nodo tiene actividad y suma la totalidad de enlaces salientes.
	 *
	 * Es especialmente útil cuando se trabaja con múltiples fases y se necesita
	 * conocer el grado de salida global del nodo, independientemente de la fase activa.
	 *
	 * La implementación es **opcional** según la interfaz, pero en `NodoElectrico`
	 * se implementa completamente.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes_global cantidad_de_incidentes_global()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados (adyacentes):
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 *
	 * ---
	 * @example
	 * // Supongamos dos fases con diferentes enlaces
	 * Controlador::_fase($token, 'fase1');
	 * $nodo->_adyacente_en($otro, 'enlace1');
	 * Controlador::_fase($token, 'fase2');
	 * $nodo->_adyacente_en($otro2, 'enlace2');
	 *
	 * echo $nodo->cantidad_de_adyacentes_global(); // 2
	 * echo $nodo->cantidad_de_adyacentes(); // 1 (solo fase actual 'fase2')
	 *
	 * @return int
	 * @public
	 * @since V1.2.7
	 */
	public function cantidad_de_adyacentes_global(): int {
		$total = 0;
		if (is_array($this->adyacentes)) {
			foreach ($this->adyacentes as $adyacentesFase) {
				if (is_array($adyacentesFase)) {
					$total += count($adyacentesFase);
				}
			}
		}
		return $total;
	}
	/*
	 * INTERFAZ INCIDENTES (INSTANCIA)
	 *
	 *  Reemplazo de los metodos existentes
	 *  Esta interfaz talves si valga la pena extraerla y diferenciarla de la de Adyacentes
	 */

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
	 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * if ($nodo->tiene_incidente()) {
	 *     echo "El nodo tiene conexiones entrantes.<br>";
	 * }else{
	 *     echo "El nodo no tiene conexiones entrantes.<br>";//imprime esto
	 * }
	 * $otroNodo= NodoElectrico::crear();
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
	 * @since 0.0.1
	 */
	public function tiene_incidente()
	{
		if ($this->incidentes !== null && count($this->incidentes) > 0) {
			$idincidentes = $this->incidentes;
			$fase = NodoElectrico::$fase;
			foreach ($idincidentes as $idincidente => $fases) {
				if (isset($fases[$fase])) {
					$res = count($fases[$fase]);
					if ($res)
						return true;
				}
			}
		}
		return false;
	}

	/**
	 * Devuelve la cantidad total de incidentes (entrantes) sumando todas las fases.
	 *
	 * A diferencia de {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes()}
	 * que solo cuenta en la **fase actual**, este método recorre todas las fases
	 * en las que el nodo recibe enlaces y suma la totalidad de conexiones entrantes.
	 *
	 * Es útil cuando se necesita conocer el grado de entrada global del nodo,
	 * independientemente de la fase activa.
	 *
	 * La implementación es **opcional** según la interfaz, pero en `NodoElectrico`
	 * se implementa completamente.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes()}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes_global cantidad_de_adyacentes_global()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados (incidentes):
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__incidente_en _incidente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_incidentes incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_incidente eliminar_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_incidente_ejecutar por_cada_incidente_ejecutar}
	 *
	 * ---
	 * @example
	 * // Supongamos dos fases con diferentes incidentes
	 * Controlador::_fase($token, 'faseA');
	 * $otroNodo->_adyacente_en($nodo, 'entrada1');
	 * Controlador::_fase($token, 'faseB');
	 * $otroNodo2->_adyacente_en($nodo, 'entrada2');
	 *
	 * echo $nodo->cantidad_de_incidentes_global(); // 2
	 * echo $nodo->cantidad_de_incidentes(); // 1 (solo fase actual 'faseB')
	 *
	 * @return int
	 * @public
	 * @since V1.2.7
	 */
	public function cantidad_de_incidentes_global(): int {
		$total = 0;
		if (is_array($this->incidentes)) {
			foreach ($this->incidentes as $fasesPorNodo) {
				if (is_array($fasesPorNodo)) {
					foreach ($fasesPorNodo as $incidentesFase) {
						if (is_array($incidentesFase)) {
							$total += count($incidentesFase);
						}
					}
				}
			}
		}
		return $total;
	}
	/**
	 * Verifica si el nodo actual es adyacente del nodo indicado (Interfaz Adyacentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Adyacentes.html Adyacentes}
	 *
	 * Comprueba si el nodo actual se encuentra enlazado desde el nodo pasado como parámetro.
	 * Para optimizar, se valida tanto que el nodo actual posea conexiones entrantes
	 * como que el nodo objetivo tenga adyacentes salientes.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a()}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodoA = NodoElectrico::crear_con_dato("A");
	 * $nodoB = NodoElectrico::crear_con_dato("B");
	 * $nodoA->_adyacente_en($nodoB, "enlaceAB");  // desde A hacia B
	 *
	 * // En nodoB, ver si existe incidente desde A
	 * $enlace = $nodoB->tiene_incidente_a($nodoA);
	 * if ($enlace !== false) {
	 *     echo "Existe el enlace '$enlace' desde A hacia B (incidente en B)";
	 * } else {
	 *     echo "No existe incidente";
	 * }
	 * ```
	 *
	 * @note Solo devuelve el nombre del enlace si realmente existe; `false` en caso contrario.
	 * @public
	 * @since 3.2.3
	 * @param Nodo $nodo Nodo a verificar.
	 * @return string|false Nombre del enlace si existe, `false` en caso contrario.
	 */
	public function tiene_incidente_a($nodo)
	{
		if (!($nodo instanceof NodoElectrico)) {
			Nodo::_error('El nodo que intenta comprobar no es una instancia de la clase Nodo.');
			return false;
		}

		if ($this->incidentes !== null) {
			$id = (string) $nodo->id();
			if (isset($this->incidentes[$id])) {
				$fases = $this->incidentes[$id];
				$faseActual = NodoElectrico::$fase;
				if (isset($fases[$faseActual])) {
					$enlaces = $fases[$faseActual];
					if (!empty($enlaces)) {
						// Devolvemos el primer nombre de enlace (el que sea)
						return array_key_first($enlaces);
					}
				}
			}
		}
		return false;
	}

	/*
	 * Devuelve el nodo incidente en el enlace especificado (Interfaz Incidentes)
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
	 *
	 * Comprueba si existe un nodo en el enlace indicado y lo devuelve;
	 * si no existe, devuelve `null`. El enlace debe ser `int` o `string`.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_incidentes incidentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $n1 = NodoElectrico::crear_con_dato("A");
	 * $n2 = NodoElectrico::crear_con_dato("B");
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
	/*
	 * public function incidente($enlace): ?Nodo{
	 * 	if (!Nodo::validar_nombre_enlace($enlace)) {// esto se deja porque si intento acceder al array con algo q no sea un entero o un string en php salta un warning
	 * 		self::_error("El enlace debe ser un string");
	 * 		return null;
	 * 	}
	 * 	if ($this->incidentes===null){
	 * 		return null;
	 * 	}
	 * 	if (!count($this->incidentes)){
	 * 		return null;
	 * 	}
	 *     $faseactual=NodoElectrico::$fase;
	 *     if (!isset($this->incidentes[$faseactual])){
	 *         return null;
	 *     }
	 *     if (!count($this->incidentes[$faseactual])) {
	 * 		return null;
	 * 	}
	 *
	 * 	return $this->incidentes[$faseactual][$enlace] ?? null;
	 * }
	 */

	/**
	 * Devuelve una copia de todos los incidentes (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html# incidentes}
	 *
	 * Retorna todos los nodos incidentes del nodo actual en una estructura independiente,
	 * asegurando que sea una "foto" del estado al momento de la llamada.
	 * Si el nodo no tiene incidentes, devuelve `null`.
	 * Se utiliza para obtener de manera segura los enlaces actuales sin exponer la referencia interna.
	 *
	 * ---
	 * 🔗 Métodos complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_incidente incidente}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * $nodo->_incidente_en(Nodo::crear_con_dato_e_id("hola", "Id_hola"), "enlace hola");
	 * $nodo->_incidente_en(Nodo::crear_con_dato_e_id("chau", "Id_chau"), "enlace chau");
	 * $todos = $nodo->incidentes();
	 * if ($todos !== null) {
	 *     foreach ($todos as $enlace => $inc) {
	 *         echo "Enlace: $enlace, Nodo ID: " . $inc->id() . ", Nodo Dato: ". $inc->dato() ."<br>";// imprimo
	 * 	       unset($todos[$enlace]); //no modifico los enlaces en el nodo original
	 *     }
	 * }
	 * echo "compruebo eliminacion en resultado<br>";
	 * foreach ($todos as $enlace => $inc) {
	 *     echo "Enlace: $enlace, Nodo ID: " . $inc->id() . ", Nodo Dato: ". $inc->dato() ."<br>";// imprimo
	 * }
	 * echo "comprobacion nuevo resultado<br>";
	 * $todos2 = $nodo->incidentes();
	 * foreach ($todos2 as $enlace => $inc) {
	 *    echo "Enlace: $enlace, Nodo ID: " . $inc->id() . ", Nodo Dato: ". $inc->dato() ."<br>";// imprimo
	 * }
	 * ```
	 *
	 * @note Se devuelve una copia superficial del array interno de adyacentes.
	 * @public
	 * @since 0.0.1
	 * @return ?array Array asociativo con enlaces y nodos, o `null` si no hay adyacentes
	 */
	public function incidentes(): ?array
	{
		if (!$this->tiene_incidente()) {
			return null;
		}
		$res = [];
		$faseActual = NodoElectrico::$fase;
		foreach ($this->incidentes as $idIncidente => $fases) {
			if (isset($fases[$faseActual]) && !empty($fases[$faseActual])) {
				$res[$idIncidente] = $fases[$faseActual];
			}
		}
		return empty($res) ? null : $res;
	}

	/**
	 * Devuelve la cantidad de incidentes (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
	 *
	 * Retorna el número total de nodos incidentes actualmente vinculados al nodo.
	 * Si no existen incidentes, devuelve `0`.
	 * Este método permite conocer de manera rápida el grado de salida del nodo.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 *
	 * ---
	 * 🔗 Otros métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente _adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo = NodoElectrico::crear();
	 * $otro1 = NodoElectrico::crear();
	 * $otro2 = NodoElectrico::crear();
	 * $nodo->_incidente_en($otro1, "X");
	 * $nodo->_incidente_en($otro2, "Y");
	 * echo $nodo->cantidad_de_incidentes(); // 2
	 * ```
	 *
	 * @note Si no hay adyacentes inicializados, retorna 0 directamente.
	 * @param
	 * @return int Cantidad de incidentes del nodo
	 * @public
	 * @sice 0.0.1
	 */
	public function cantidad_de_incidentes(): int
	{
		if ($this->incidentes !== null && count($this->incidentes) > 0) {
			$total = 0;
			$idincidentes = $this->incidentes;
			$fase = NodoElectrico::$fase;
			foreach ($idincidentes as $idincidente => $fases) {
				if (isset($fases[$fase])) {
					$total += count($fases[$fase]);
				}
			}
			//	return count($this->incidentes[NodoElectrico::$fase]);
			return $total;
		}
		return 0;
	}

	/**
	 * Establece un nodo incidente internamente (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
	 *
	 * Método interno para establecer la relación incidente correspondiente a una
	 * adyacencia. Verifica que exista previamente la relación de adyacencia.
	 *
	 * ---
	 * 🔗 Método complementario:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__adyacente_en _adyacente_en}
	 *
	 * ---
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 * @note Solo para uso interno del sistema.
	 * @param NodoElectrico $un_nodo Nodo incidente a establecer
	 * @param mixed $enlace Nombre del enlace
	 * @return bool True si éxito, false si error
	 * @private
	 * @since 0.0.1
	 */
	private function _incidente_en($un_nodo, $enlace): bool
	{
		if (!$un_nodo->adyacente($enlace)) {  // verifico que ya se haya agregado el enlace de ida
			static::_alerta('No se puede agregar el enlace de vuelta antes que el de ida');
			return false;
		}
		echo '<br/>_inidente_en' . $enlace . '<br/>';
		// inicializacion perezosa
		if ($this->incidentes === null) {
			$this->incidentes = [];
		}
		$idstring = (string) $un_nodo->id();
		if (!isset($this->incidentes[$idstring])) {  // una entrada por cada nodo incidente
			$this->incidentes[$idstring] = [];
		}
		$fases = $this->incidentes[$idstring];
		$fase = NodoElectrico::$fase;
		// asigno adyacente
		if (!isset($fases[$fase])) {
			$this->incidentes[$idstring][$fase] = [];
		}
		echo '<br/>_inidente_333en' . $idstring . $fase . $enlace . '<br/>';
		$this->incidentes[$idstring][$fase][$enlace] = $un_nodo;
		return true;
	}

	/**
	 * Elimina un nodo incidente específico internamente (Interfaz Incidentes).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-Incidentes.html Incidentes}
	 *
	 * Método interno para remover una relación incidente. Verifica que previamente
	 * se haya eliminado la relación de adyacencia correspondiente.
	 *
	 * ---
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__incidente_en _incidente_en}
	 *
	 * ---
	 *
	 * 🔗 Método relacionado:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 *
	 *
	 * @note Solo para uso interno del sistema.
	 * @param NodoElectrico $incidente Nodo incidente
	 * @param string $enlace Nombre del enlace a eliminar
	 * @private
	 * @since 0.0.1
	 */
	private function eliminar_incidente($incidente, $enlace)
	{
		// Validación de tipo
		/*if (!static::validar_nombre_enlace($enlace)) {
			self::_error("el enlace a eliminar no es valido");
			return null;
		}*/
		echo '*eliminar*';
		// verificar inicialización perezosa
		if ($this->incidentes === null) {
			self::_alerta('no hay incidente para eliminar');
			return null;
		}
		$id = (string) $incidente->id();
		if (!isset($this->incidentes[$id])) {
			self::_alerta('no hay incidente para eliminar 2');
			return null;
		}
		$fase = self::$fase;
		$fases = $this->incidentes[$id];

		if (!isset($fases[$fase])) {
			self::_alerta('no hay incidnetes para eliminar en la fase');
			return null;
		}
		// echo $this->incidentes[$fase];
		$incidentes = $fases[$fase];
		/*	foreach ($incidentes as $en=>$no){
			echo "</br>fase: ".$fase." enlace: ".$en." nodo: ".$no->id()." busco: ".$enlace;
		}*/
		// Verificar existencia del enlace
		if (!isset($incidentes[$enlace])) {
			self::_alerta('el enlace ' . $enlace . ' que se intenta eliminar no existe');
			return null;
		}
		$eliminado = $incidentes[$enlace];
		unset($this->incidentes[$id][$fase][$enlace]);
		return $eliminado;
		/*if ($eliminado->adyacentes[$fase][$enlace]!==null){//verifico que ya se haya eliminado el enlace de ida
			static::_alerta("No se puede eliminar el enlace de vuelta antes que el de ida");
			return null;
		}*/
	}

	/*	private function eliminar_incidentes(): ?array{

			if ($this->E===null or !count($this->E)>0) {
				self::_alerta("no hay incidentes para eliminar");
				return [];
			}
			if ($this->E[NodoElectrico::$fase]===null or !count($this->E[NodoElectrico::$fase])){
				self::_alerta("no hay incidentes para eliminar en esta fase");
				return [];
			}
			$incidentes=$this->E[NodoElectrico::$fase];
			$copia=[...$incidentes];
			foreach ($incidentes as $eliminado) {
				$eliminado->referencias--;
			}
			$this->adyacentes=[];
			return $copia;

		}*/

	/**
	 * Ejecuta una función por cada nodo incidente (Interfaz IncidentesDobleVia).
	 *
	 * 🔗 Interfaz:
	 * - {@link ./classes/Iteradores-Nodos-Interfaces-IncidentesDobleVia.html IncidentesDobleVia}
	 *
	 * Itera sobre todos los nodos incidentes ejecutando una función callback para cada uno.
	 * La función recibe el nodo incidente, nombre del enlace y parámetros adicionales.
	 *
	 * ---
	 * 🔗 Métodos complementarios:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_por_cada_adyacente_ejecutar por_cada_adyacente_ejecutar}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_incidentes incidentes}
	 *
	 * ---
	 * 🔗 Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacente eliminar_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__incidente_en _incidente_en}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_adyacentes cantidad_de_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_cantidad_de_incidentes cantidad_de_incidentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente tiene_incidente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacente adyacente}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_adyacentes adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente_a tiene_adyacente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_incidente_a tiene_incidente_a}
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_eliminar_adyacentes eliminar_adyacentes}
	 * - {@link ./classes/Iteradores-Nodos-Nodo.html#method_validar_nombre_enlace validar_nombre_enlace}
	 * ---
	 * Ejemplo de uso:
	 * ```php
	 * $nodo->por_cada_incidente_ejecutar(function($inc, $enlace) {
	 *     echo "Procesando incidente en enlace: $enlace";
	 * }, $parametro_extra);
	 * ```
	 *
	 * @note Devuelve array con resultados de cada ejecución.
	 * @param callable $funcion Función a ejecutar
	 * @param mixed ...$parametros Parámetros adicionales para la función
	 * @return array|null Array asociativo con resultados o null si no hay incidentes
	 * @public
	 * @since 0.0.1
	 */
	public function por_cada_incidente_ejecutar(callable $funcion, mixed ...$parametros): ?array
	{
		if ($this->incidentes === null || count($this->incidentes) < 1) {
			static::_alerta('alerta no existe incidente');
			return [];
		}
		$resultados = [];
		$fase = NodoElectrico::$fase;
		foreach ($this->incidentes as $idincidente => $fases) {
			if (isset($fases[$fase])) {
				$resultados[$idincidente] = [];
				$faseaux = $fases[$fase];
				foreach ($faseaux as $enlace => $incidente) {
					$resultados[$idincidente][$enlace] = $funcion($incidente, $enlace, ...$parametros);
				}
			}
		}

		return $resultados;
	}

    // =================================================================
    // INTERFAZ ENERGÍA
    // =================================================================

    // -----------------------------------------------------------------
    // Propiedades privadas
    // -----------------------------------------------------------------

    /**
     * Energía actual por fase.
     * @var array<string, int>
     */
    private $energia = [];

    /**
     * Último timestamp (microtime) en que se aplicó fuga por fase.
     * @var array<string, float>
     */
    private $ultima_fuga = [];

    /**
     * Capacidad máxima de energía del nodo (valor fijo).
     * @var int
     */
    protected $capacidad = 256;

    /**
     * Fuga de energía por ciclo de tiempo (valor fijo).
     * @var float
     */
    protected $fuga = 0;

    /**
     * Callbacks de saturación registrados por instancia y fase.
     * Estructura: [fase => [callable $callback, bool $reemplazar]]
     * @var array<string, array{0: callable, 1: bool}>|null
     */
    private $ejecutar_cuando_satura = null;

    /**
     * Callbacks de agotamiento registrados por instancia y fase.
     * Estructura: [fase => [callable $callback, bool $reemplazar]]
     * @var array<string, array{0: callable, 1: bool}>|null
     */
    private $ejecutar_cuando_agota = null;

    /**
     * Callbacks por defecto de saturación asociados a una fase (estáticos).
     * @var array<string, callable>|null
     */
    private static $ejecutar_cuando_satura_por_defecto_por_fase = null;

    /**
     * Callbacks por defecto de agotamiento asociados a una fase (estáticos).
     * @var array<string, callable>|null
     */
    private static $ejecutar_cuando_agota_por_defecto_por_fase = null;

    /**
     * Callback global cuando todas las fases del nodo están sin energía.
     * @var callable|null
     */
    private static $ejecutar_cuando_agota_global = null;

    // -----------------------------------------------------------------
    // Getters básicos
    // -----------------------------------------------------------------

    /**
     * Devuelve la capacidad máxima de energía del nodo.
     *
     * Este valor se establece en el momento de la creación del nodo
     * (a través de los métodos estáticos de fábrica) y no puede modificarse
     * durante la vida del nodo.
     *
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_fuga fuga()}
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_energia energia()}
     *
     * ---
     * @example
     * $nodo = NodoElectrico::crear(1000, 0.5);
     * echo $nodo->capacidad(); // 1000
     *
     * @return int
     * @public
     * @since V1.2.8
     */
    public function capacidad(): int {
        return $this->capacidad;
    }

    /**
     * Devuelve la fuga de energía por ciclo del nodo.
     *
     * Este valor se establece en la creación del nodo (a través de los métodos
     * estáticos de fábrica). Representa la cantidad de energía que el nodo pierde
     * espontáneamente en cada ciclo de simulación (definido por `Conf::TIEMPO_CICLO`).
     *
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_capacidad capacidad()}
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_energia energia()}
     *
     * ---
     * @example
     * $nodo = NodoElectrico::crear(1000, 0.5);
     * echo $nodo->fuga(); // 0.5
     *
     * @return float
     * @public
     * @since V1.2.8
     */
    public function fuga(): float {
        return $this->fuga;
    }

    /**
     * Devuelve la energía actual del nodo en la fase activa,
     * aplicando previamente todas las fugas pendientes según el tiempo real transcurrido.
     *
     * Este método llama internamente a `fugar()` para actualizar la energía
     * de todas las fases antes de devolver el valor de la fase actual.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__energia _energia()}
     *
     * ---
     * @example
     * $nodo->_energia(100);
     * sleep(2); // espera 2 segundos (2 ciclos de 1 segundo)
     * echo $nodo->energia(); // 100 - (fuga * 2)
     *
     * @return int Energía en la fase actual (0 <= valor <= capacidad)
     * @public
     * @since V1.2.8
     */
    public function energia(): int {
        $this->fugar();
        return $this->energia[self::$fase] ?? 0;
    }

    // -----------------------------------------------------------------
    // Método de fuga (privado)
    // -----------------------------------------------------------------

    /**
     * Aplica la fuga de energía basada en el tiempo real transcurrido.
     *
     * Para cada fase con energía registrada, calcula cuántos ciclos completos
     * han pasado desde la última actualización (según `Conf::TIEMPO_CICLO`)
     * y resta `fuga * ciclos`. Si la energía llega a 0, se ejecuta el callback
     * de agotamiento correspondiente (instancia o fase). Al final, si todas
     * las fases tienen energía 0, se ejecuta el callback global.
     *
     * Este método es llamado automáticamente desde `energia()` y `_energia()`.
     *
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__energia _energia()}
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_energia energia()}
     *
     * ---
     * @return void
     * @private
     * @since V1.2.8
     */
    private function fugar(): void {
        $ahora = microtime(true);
        $todas_cero = true;

        foreach ($this->energia as $fase => &$energia) {
            $ultimo = $this->ultima_fuga[$fase] ?? $ahora;
            $delta = $ahora - $ultimo;
            $ciclos = (int)floor($delta / Conf::TIEMPO_CICLO);
            if ($ciclos > 0 && $this->fuga > 0) {
                $perdida = $this->fuga * $ciclos;
                $energia = max(0, $energia - $perdida);
                $this->ultima_fuga[$fase] = $ahora;

                if ($energia === 0 && $perdida > 0) {
                    $this->ejecutar_callback_agotamiento($fase);
                }
            }
            if ($energia > 0) {
                $todas_cero = false;
            }
        }
        unset($energia);

        if ($todas_cero && self::$ejecutar_cuando_agota_global !== null) {
            call_user_func(self::$ejecutar_cuando_agota_global, $this);
        }
    }

    // -----------------------------------------------------------------
    // Ejecución de callbacks (privados)
    // -----------------------------------------------------------------

    /**
     * Ejecuta el callback de saturación para la fase actual.
     *
     * Respeta el modo `reemplazar` (true = solo instancia si existe, si no la de fase;
     * false = ambos, instancia primero y luego fase).
     *
     * @return void
     * @private
     * @since V1.2.8
     */
    private function ejecutar_callback_saturacion(): void {
        $fase = self::$fase;
        $instanciaData = ($this->ejecutar_cuando_satura !== null && isset($this->ejecutar_cuando_satura[$fase]))
            ? $this->ejecutar_cuando_satura[$fase]
            : [null, true];
        $fase_cb = self::ejecutar_cuando_satura_por_fase($fase);
        [$instancia_cb, $reemplazar] = $instanciaData;

        if ($reemplazar) {
            if ($instancia_cb) {
                $instancia_cb($this);
            } elseif ($fase_cb) {
                $fase_cb($this);
            }
        } else {
            if ($instancia_cb) {
                $instancia_cb($this);
            }
            if ($fase_cb) {
                $fase_cb($this);
            }
        }
    }

    /**
     * Ejecuta el callback de agotamiento para una fase específica.
     *
     * Respeta el modo `reemplazar` (true = solo instancia si existe, si no la de fase;
     * false = ambos, instancia primero y luego fase).
     *
     * @param string $fase Nombre de la fase en la que se ha agotado la energía.
     * @return void
     * @private
     * @since V1.2.8
     */
    private function ejecutar_callback_agotamiento(string $fase): void {
        $instanciaData = ($this->ejecutar_cuando_agota !== null && isset($this->ejecutar_cuando_agota[$fase]))
            ? $this->ejecutar_cuando_agota[$fase]
            : [null, true];
        $fase_cb = self::ejecutar_cuando_agota_por_fase($fase);
        [$instancia_cb, $reemplazar] = $instanciaData;

        if ($reemplazar) {
            if ($instancia_cb) {
                $instancia_cb($this);
            } elseif ($fase_cb) {
                $fase_cb($this);
            }
        } else {
            if ($instancia_cb) {
                $instancia_cb($this);
            }
            if ($fase_cb) {
                $fase_cb($this);
            }
        }
    }

    // -----------------------------------------------------------------
    // Método público de energía
    // -----------------------------------------------------------------

    /**
     * Añade energía al nodo en la fase activa.
     *
     * **Secuencia de operaciones:**
     * 1. Aplica las fugas pendientes llamando a `fugar()`.
     * 2. Incrementa la energía de la fase actual con `$cantidad_energia`.
     * 3. Si supera la capacidad, la ajusta y ejecuta el callback de saturación.
     * 4. Si queda en cero (o se vuelve cero), ejecuta el callback de agotamiento.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_energia energia()}
     *
     * ---
     * @param int $cantidad_energia Cantidad a añadir (puede ser negativa, aunque se recomienda usar la fuga para decrementar).
     * @return void
     * @public
     * @since V1.2.8
     */
    public function _energia(int $cantidad_energia): void {
        $this->fugar();

        $fase = self::$fase;
        if (!isset($this->energia[$fase])) {
            $this->energia[$fase] = 0;
            $this->ultima_fuga[$fase] = microtime(true);
        }

        $this->energia[$fase] += $cantidad_energia;

        if ($this->energia[$fase] > $this->capacidad) {
            $this->energia[$fase] = $this->capacidad;
            $this->ejecutar_callback_saturacion();
        }

        if ($this->energia[$fase] <= 0) {
            $this->energia[$fase] = 0;
            $this->ejecutar_callback_agotamiento($fase);
        }
    }

    // -----------------------------------------------------------------
    // Callbacks por instancia
    // -----------------------------------------------------------------

    /**
     * Registra un callback para cuando el nodo se satura (por instancia).
     *
     * **Modos de ejecución:**
     * - `$reemplazar = true` (por defecto): este callback **reemplaza** al callback por defecto de la fase.
     *   Solo se ejecutará este, a menos que sea null, en cuyo caso se ejecuta el de fase.
     * - `$reemplazar = false`: este callback **complementa** al de fase. Se ejecutan ambos,
     *   primero el de instancia y luego el de fase (si existe).
     *
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_ejecutar_cuando_satura ejecutar_cuando_satura()}
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__ejecutar_cuando_satura_por_fase _ejecutar_cuando_satura_por_fase()}
     *
     * ---
     * @param callable $funcion Callback que recibirá el nodo como único argumento.
     * @param bool $reemplazar Si `true`, reemplaza; si `false`, complementa.
     * @return void
     * @public
     * @since V1.2.8
     */
    public function _ejecutar_cuando_satura(callable $funcion, bool $reemplazar = true): void {
        if ($this->ejecutar_cuando_satura === null) {
            $this->ejecutar_cuando_satura = [];
        }
        $this->ejecutar_cuando_satura[self::$fase] = [$funcion, $reemplazar];
    }

    /**
     * Devuelve el callback de saturación registrado para la instancia (fase actual)
     * junto con el indicador de si reemplaza o complementa.
     *
     * El valor devuelto es un array con dos elementos: `[callable|null $callback, bool $reemplazar]`.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__ejecutar_cuando_satura _ejecutar_cuando_satura()}
     *
     * ---
     * @return array{0: callable|null, 1: bool}
     * @public
     * @since V1.2.8
     */
    public function ejecutar_cuando_satura(): array {
        return ($this->ejecutar_cuando_satura !== null && isset($this->ejecutar_cuando_satura[self::$fase]))
            ? $this->ejecutar_cuando_satura[self::$fase]
            : [null, true];
    }

    /**
     * Registra un callback para cuando el nodo se agota (energía llega a 0) por instancia.
     *
     * **Modos de ejecución:**
     * - `$reemplazar = true`: reemplaza al callback por defecto de la fase.
     * - `$reemplazar = false`: complementa (se ejecutan ambos, primero este).
     *
     * ---
     * 🔗 Métodos relacionados:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_ejecutar_cuando_agota ejecutar_cuando_agota()}
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__ejecutar_cuando_agota_por_fase _ejecutar_cuando_agota_por_fase()}
     *
     * ---
     * @param callable $funcion Callback que recibirá el nodo como único argumento.
     * @param bool $reemplazar Si `true`, reemplaza; si `false`, complementa.
     * @return void
     * @public
     * @since V1.2.8
     */
    public function _ejecutar_cuando_agota(callable $funcion, bool $reemplazar = true): void {
        if ($this->ejecutar_cuando_agota === null) {
            $this->ejecutar_cuando_agota = [];
        }
        $this->ejecutar_cuando_agota[self::$fase] = [$funcion, $reemplazar];
    }

    /**
     * Devuelve el callback de agotamiento registrado para la instancia (fase actual)
     * junto con el indicador de si reemplaza o complementa.
     *
     * El valor devuelto es un array con dos elementos: `[callable|null $callback, bool $reemplazar]`.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method__ejecutar_cuando_agota _ejecutar_cuando_agota()}
     *
     * ---
     * @return array{0: callable|null, 1: bool}
     * @public
     * @since V1.2.8
     */
    public function ejecutar_cuando_agota(): array {
        return ($this->ejecutar_cuando_agota !== null && isset($this->ejecutar_cuando_agota[self::$fase]))
            ? $this->ejecutar_cuando_agota[self::$fase]
            : [null, true];
    }

    // -----------------------------------------------------------------
    // Callbacks por defecto por fase (estáticos)
    // -----------------------------------------------------------------

    /**
     * Registra un callback por defecto de saturación para una fase determinada.
     *
     * Este callback se ejecutará cuando un nodo en esa fase se sature,
     * **siempre que no exista un callback de instancia que lo reemplace**.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_ejecutar_cuando_satura_por_fase ejecutar_cuando_satura_por_fase()}
     *
     * ---
     * @param callable $funcion Callback que recibirá el nodo como argumento.
     * @param string|null $fase Nombre de la fase. Si es `null`, se usa la fase actual del sistema.
     * @return void
     * @public
     * @static
     * @since V1.2.8
     */
    public static function _ejecutar_cuando_satura_por_fase(callable $funcion, ?string $fase = null): void {
        if ($fase === null) {
            $fase = self::$fase;
        }
        if (self::$ejecutar_cuando_satura_por_defecto_por_fase === null) {
            self::$ejecutar_cuando_satura_por_defecto_por_fase = [];
        }
        self::$ejecutar_cuando_satura_por_defecto_por_fase[$fase] = $funcion;
    }

    /**
     * Obtiene el callback por defecto de saturación registrado para una fase.
     *
     * @param string|null $fase Nombre de la fase. Si es `null`, se usa la fase actual.
     * @return callable|null El callback, o `null` si no hay ninguno registrado.
     * @public
     * @static
     * @since V1.2.8
     */
    public static function ejecutar_cuando_satura_por_fase(?string $fase = null): ?callable {
        if ($fase === null) {
            $fase = self::$fase;
        }
        if (self::$ejecutar_cuando_satura_por_defecto_por_fase === null) {
            return null;
        }
        return self::$ejecutar_cuando_satura_por_defecto_por_fase[$fase] ?? null;
    }

    /**
     * Registra un callback por defecto de agotamiento para una fase determinada.
     *
     * @param callable $funcion Callback que recibirá el nodo como argumento.
     * @param string|null $fase Nombre de la fase. Si es `null`, se usa la fase actual.
     * @return void
     * @public
     * @static
     * @since V1.2.8
     */
    public static function _ejecutar_cuando_agota_por_fase(callable $funcion, ?string $fase = null): void {
        if ($fase === null) {
            $fase = self::$fase;
        }
        if (self::$ejecutar_cuando_agota_por_defecto_por_fase === null) {
            self::$ejecutar_cuando_agota_por_defecto_por_fase = [];
        }
        self::$ejecutar_cuando_agota_por_defecto_por_fase[$fase] = $funcion;
    }

    /**
     * Obtiene el callback por defecto de agotamiento registrado para una fase.
     *
     * @param string|null $fase Nombre de la fase. Si es `null`, se usa la fase actual.
     * @return callable|null
     * @public
     * @static
     * @since V1.2.8
     */
    public static function ejecutar_cuando_agota_por_fase(?string $fase = null): ?callable {
        if ($fase === null) {
            $fase = self::$fase;
        }
        if (self::$ejecutar_cuando_agota_por_defecto_por_fase === null) {
            return null;
        }
        return self::$ejecutar_cuando_agota_por_defecto_por_fase[$fase] ?? null;
    }

    // -----------------------------------------------------------------
    // Callback global (todas las fases)
    // -----------------------------------------------------------------

    /**
     * Registra un callback global que se ejecutará cuando **todas las fases**
     * del nodo se queden sin energía (energía = 0).
     *
     * Este callback es útil para detectar que el nodo ha quedado completamente inactivo.
     *
     * ---
     * 🔗 Método complementario:
     * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_ejecutar_cuando_agota_global ejecutar_cuando_agota_global()}
     *
     * ---
     * @param callable $funcion Callback que recibirá el nodo como argumento.
     * @return void
     * @public
     * @static
     * @since V1.2.8
     */
    public static function _ejecutar_cuando_agota_global(callable $funcion): void {
        self::$ejecutar_cuando_agota_global = $funcion;
    }

    /**
     * Devuelve el callback global de agotamiento (si está registrado).
     *
     * @return callable|null
     * @public
     * @static
     * @since V1.2.8
     */
    public static function ejecutar_cuando_agota_global(): ?callable {
        return self::$ejecutar_cuando_agota_global;
    }
	/*************************************************************************************************************/
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	// INTERFACE PARA IMPRIMIR LOS NODOS*************************************************/////////////////////////*/
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////*/
	/*************************************************************************************************************/

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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_imprimir2 imprimir2()} — versión en texto plano.
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_id id()} — obtiene el identificador del nodo.
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_dato dato()} — obtiene el dato asociado al nodo.
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_tiene_adyacente tiene_adyacente()} — comprueba adyacencias.
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
	public function imprimir()
	{
		echo "<div id='nodo-" . $this->id() . "' style='margin-bottom:20px;'>";
		echo '>>NODOELECTRICO4 ' . $this->id();
		if ($this->es_especial())
			echo ' (ESP)';
		echo ' - Dato: ';

		$dato = $this->dato();
		if (is_string($dato)) {
			echo $dato;
		} elseif ($dato === null) {
			echo 'null';
		} else {
			echo 'este dato no es un string';
		}
		echo '<br/>Referencias: ' . $this->referencias;
		echo '<br/>Capacidad: ' . $this->capacidad;
		echo '<br/>Fuga: ' . $this->fuga;
		// if ($this->energia!==null && count($this->energia)>0){
		echo '<br/>Energia: ' . $this->energia();

		/*	}else{
			echo "<br/>Energia: 0";
		}*/
		echo '<br/>Adyacentes:<br/>';
		if ($this->adyacentes !== null) {
			echo '<ul>';
			foreach ($this->adyacentes as $fase => $adyacentes) {
				echo '<h3>fase: ' . $fase . '</h3>';
				echo '<ul>';
				foreach ($adyacentes as $enlace => $nodo) {
					echo "<li>[$enlace] => <a href='#nodo-" . $nodo->id() . "'>" . $nodo->id() . '</a></li>';
				}
				echo '</ul>';
			}
			/*$this->por_cada_adyacente_ejecutar(function($nodo,$enlace){
				echo "<li>[$enlace] => <a href='#nodo-" . $nodo->id() . "'>" . $nodo->id() . "</a></li>";
			});*/
			echo '</ul>';
		} else {
			echo 'No tiene<br/>';
		}

		echo 'Incidentes:<br/>';
		if ($this->incidentes !== null) {
			echo '<ul>';
			$nodos = $this->incidentes;
			foreach ($nodos as $idnodo => $fases) {
				echo '<h3>idnodo: ' . $idnodo . '</h3>';
				echo '<ul>';
				foreach ($fases as $fase => $incidentes) {
					echo '<h4>fase: ' . $fase . '</h4>';
					echo '<ul>';
					foreach ($incidentes as $enlace => $incidente) {
						echo '<li>[' . $enlace . "] => <a href='#nodo-" . $incidente->id() . "'>" . $incidente->id() . '</a></li>';
					}
					echo '</ul>';
				}
				echo '</ul>';
			}
			echo '</ul>';
		} else {
			echo 'No tiene<br/>';
		}
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
	/*static public function imprimir_superestructura() {
		echo "NNNNNNNNNNNNNNN";
		if (!Nodo::hay_nodos_en_superestructura()) {
			static::_alerta("Nodo::imprimir_superestructura() — la superestructura está vacía");
			return false;
		}
		echo "<a id='inicio'></a>";
		$funcion = function($nodo) {
			$nodo->imprimir();
		};
		$faseoriginal=self::$fase;
		foreach (self::$fases as $fase=>$valor){
			self::establecer_fase(self::$token, $fase);
			echo "<h1> Fase: ".$fase."</h1>";
			self::por_cada_nodo_ejecutar(self::$token, $funcion, null);
		}
		self::establecer_fase(self::$token,$faseoriginal);
		return true;
	}*/

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
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_imprimir imprimir()} — versión HTML.
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_id id()} — obtiene el identificador del nodo.
	 * - {@link ./classes/Iteradores-Nodos-NodoElectrico.html#method_dato dato()} — obtiene el dato asociado al nodo.
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
	public function imprimir2()
	{
		echo "\n>>NODO " . $this->id();
		if ($this->es_especial())
			echo ' (ESP)';
		echo ' - Dato: ';

		$dato = $this->dato();
		if (is_string($dato) || is_numeric($dato)) {
			echo $dato;
		} elseif ($dato === null) {
			echo 'null';
		} else {
			echo 'este dato no es un string';
		}
		echo "\nCapacidad: " . $this->capacidad;
		echo "\nFufa: " . $this->fuga;
		echo "\nEnergia: " . $this->energia();
		echo "\nAdyacentes:\n";

		if ($this->tiene_adyacente()) {
			/*foreach ($this->adyacentes as $fase=>$adyacentes){//fases
				echo '\nAdyacentes fase "'.$fase.'":\n';
				foreach ($adyacentes as $enlace => $nodo) {
					echo "\n[$enlace] => " . $nodo->id();
				}
			}*/
			$this->por_cada_adyacente_ejecutar(function ($nodo, $enlace) {
				echo "\n[$enlace] => " . $nodo->id();
			});
		} else {
			echo "No tiene\n";
		}

		echo "\nIncidentes:\n";

		if ($this->tiene_incidente()) {
			/*	foreach ($this->incidentes as $fase=>$incidentes){//fases
				echo '\nIncidentes fase "'.$fase.'":\n';
				foreach ($incidentes as $enlace => $nodo) {
					echo "\n[$enlace] => " . $nodo->id();
				}
			}*/
			$this->por_cada_incidente_ejecutar(function ($nodo, $enlace) {
				echo "\n[$enlace] => " . $nodo->id();
			});
		} else {
			echo "No tiene\n";
		}

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

	/*
	 * static public function imprimir_superestructura2() {
	 * 	if (!Nodo::hay_nodos_en_superestructura()) {
	 * 		static::_alerta("Nodo::imprimir_superestructura2() — la superestructura está vacía");
	 * 		return false;
	 * 	}
	 *
	 * 	$funcion = function($nodo) {
	 * 		$nodo->imprimir2();
	 * 	};
	 * 	$faseoriginal=self::$fase;
	 * 	foreach(self::$fases as $fase=>$valor){
	 * 		self::establecer_fase(self::$token, $fase);
	 * 		echo "\nFase: ".$fase."\n";
	 * 		self::por_cada_nodo_ejecutar(self::$token, $funcion, null);
	 * 	}
	 * 	self::establecer_fase(self::$token, $faseoriginal);
	 * 	return true;
	 * }
	 */
}
