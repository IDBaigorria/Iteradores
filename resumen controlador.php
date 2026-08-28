<?php
namespace Iteradores\Nucleo;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Interfaces\Id;
use Iteradores\Nucleo\Interfaces\ErroresYAlertas;

/*require_once(".\configuracion\Configuracion.php");
require_once(".\configuracion\Entorno.php");*/
require_once(".\Nucleo\Interfaces\Id.php");
require_once(".\Nucleo\Interfaces\ErroresYAlertas.php");
/**
 * Clase base de todo el sistema en PHP.
 * 
 * Esta clase fue creada para ser el “padre” de todas las clases implementadas en el sistema.
 * Su objetivo principal es agrupar funciones y propiedades comunes a todos los objetos del sistema.
 * Los objetivos especificos se iran imponiendo segun las necesidades con cambios de version.
 *
 * En la **version 2.0** su propósito principal puede agruparse en tres grandes ejes:
 *
 * ---
 *
 * ### 📌 Gestión de identificadores únicos (Interface {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Id})
 *
 * Cada instancia de `Objeto` posee un **identificador único** que se genera automáticamente
 * de forma perezosa. Esto significa que el id se asigna solo en el momento en que es requerido
 * por primera vez, a través de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_id id()}.
 *
 * Además, se permite asignar manualmente un id "especial" (cadenas no numéricas) mediante
 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()}. Antes de aceptar el id,
 * el sistema valida que:
 *
 * - Sea un id especial, verificado por {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_id_especial es_id_especial()}.
 * - No exista otro objeto en ejecución con el mismo id.
 *
 * También se puede comprobar si el id actual es especial usando
 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_especial es_especial()}.
 *
 * Esta funcionalidad está definida contractualmente por la interfaz
 * {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Id}, que establece que todo objeto
 * debe ser capaz de proporcionar su identificador único.
 *
 * ---
 *
 * ### ⚠️ Sistema de recolección de errores y alertas
 *
 * `Objeto` implementa un sistema de **registro y recolección de errores y alertas**
 * diseñado para facilitar el seguimiento de incidencias en tiempo de ejecución.
 * Esta capacidad proviene de implementar las interfaces
 * {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Errores} y
 * {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Alertas},
 * unificadas en la interfaz compuesta
 * {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html ErroresYAlertas}.
 *
 * Cada error o alerta registrado incluye su mensaje y la traza de la pila de llamadas en el
 * momento en que ocurrió. Estos datos se almacenan de forma interna y pueden visualizarse
 * posteriormente.
 *
 * Entre los métodos destacados que gestionan este sistema se incluyen:
 *
 * - Registro interno (solo uso protegido):
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}
 *
 * - Activación y desactivación dinámica de la recolección:
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
 *   - También existen variantes combinadas:
 *     {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()} y
 *     {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
 *
 * - Visualización de los errores y alertas acumulados:
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
 *   - Y sus equivalentes para alertas
 *
 * - Exportación en formato JSON para depuración automatizada:
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
 *   - y su equivalete para alertas
 *
 * Este mecanismo se activa o desactiva de forma predeterminada según la configuración inicial
 * provista por la clase {@link ./classes/Iteradores-Configuracion-Conf.html Conf}, pero puede
 * cambiarse dinámicamente durante la ejecución.
 *
 * ---
 *
 * ### ⚙️ Configuración mediante constantes (Clase {@link ./classes/Iteradores-Configuracion-Conf.html Conf})
 *
 * `Objeto` depende de varias constantes definidas en la clase
 * {@link ./classes/Iteradores-Configuracion-Conf.html Conf}, que controlan su comportamiento
 * inicial y el nivel de detalle que se almacena:
 *
 * - **Activación predeterminada de recolección:**
 *   - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES ACTIVAR_ERRORES}
 *   - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS ACTIVAR_ALERTAS}
 *
 *   Estas constantes determinan si la recolección de errores y alertas comienza activada o
 *   desactivada al construir cualquier objeto. Sin embargo, este estado inicial puede ser
 *   modificado en tiempo de ejecución mediante los métodos de activación y desactivación.
 *
 * - **Control de la pila de llamadas almacenada:**
 *   - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
 *   - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
 *   - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
 *
 *   Estas constantes determinan cuántos niveles de la pila se guardan junto con cada error
 *   o alerta, y si se incluyen o no los argumentos y los objetos referenciados en cada nivel.
 *   Esto permite controlar el consumo de memoria, ya que capturar argumentos y objetos puede
 *   implicar estructuras muy pesadas.
 *
 * ---
 *
 * ### 🧩 Rol como clase base en el sistema
 *
 * La clase `Objeto` está pensada para ser **extendida** por todas las clases del sistema ya que
 * requerirán:
 *
 * - Un identificador único garantizado y gestionado automáticamente.
 * - Capacidades integradas para recolectar, almacenar y mostrar errores y alertas.
 * - Un comportamiento configurable centralizadamente desde la clase
 *   {@link ./classes/Iteradores-Configuracion-Conf.html Conf}.
 *
 * Gracias a esta arquitectura, cualquier clase que herede de `Objeto` obtiene de inmediato
 * estas capacidades sin necesidad de reimplementarlas.
 *
 *
 * ---
 * ### HISTORIAL DE CAMBIOS
 *
 * - **V1.4.1**: Cambiada la funcion `error()` de `private` a `public`.
 * - **V1.5**: Refactorizacion, cambiando el protocolo de errores.
 *   - Interfaces: `Error`, `Id`.
 * - **V1.6**: Estable.
 * - **V1.7**: Interface `Alerta`.
 * - **26/07/2013 (V1.7)**: Eliminado error en etiqueta HTML `<br>` en `imprimirErrores()` y `imprimirAlertas()`.
 * - **V1.7.1**: Agregado `devolverErrores()` y `devolverAlertas()`.
 * - **28/12/2016 (V1.161228)**: Cambio de nomenclatura.
 * - **30/01/2017 (V1.170130)**: Se agrega fecha y hora a cada mensaje de alerta y error.
 * - **V1.1.171103**: Adaptacion a PHP 7.
 * - **V1.1.171108**: Agregadas funciones para activar/desactivar recoleccion de mensajes de error/alerta.
 * - **V1.1.180425**: Actualizacion de `microtime()`.
 * - **V1.1.180818**: Refactorizacion a BETA.
 * - **V1.2.180818**: Seguridad en `_id()`; no se puede asignar ID a un objeto que ya tenia uno.
 * - **V1.3.180822**: Funciones para IDs “especiales” asignados por el usuario.
 *   - `static es_id_especial($id)`
 *   - `es_especial()`
 * - **V1.4.210524**: Constantes para host, usuario, contrasena y nombre de BD.
 * - **V1.4.210603**: Pruebas en 000WEBHOST; decision sobre base de datos HyS.
 * - **V1.5.250826**: Ajuste para PHP 8, se eliminan numeros de version visibles.
 * - **V1.5.1.250829**: Se agrega archivo de configuracion y reemplazo variables locales/BD por constantes.
 * - **V1.5.2.250904**: Cambios en inicializacion de base de datos y formato de errores y alertas.
 *   - Ahora muestra pila de llamadas y argumentos de funciones.
 * - **V1.5.3.250910**: Comienzo de documentacion con PHPDoc.
 *   - Inicializacion de bases de datos via `inicializacion()`.
 * - **V1.5.4.250911**: Agrego interfaces
 *   - agrego `Interface\Id`
 *   - decrepo num_hilo y la iniclizacion de las bases de datos (`inicializacion()`)
 * - **V2.0.0.250917**: Quedó totalmente refactorizada a PHP 8.2 y Documentada con PHPDoc
 *   - Las interfaces se declaron en archivos aparte en un subpaquete (namespace y carpeta)
 *   - Se agrego json_errores y json_alertas 
 * - **V2.0.0.250930**: Cambio los imprimir, la variable $ini incializa con 1 o 2 y no siempre 2,
 * 						además agrego un if para que no muestre el mensaje "Pila de llamadas" si no es necesario
 * - **V2.0.1.251006**: Agrego _id_interno() y realizo optimizaciones en toda la interfaz id para que consuma menos cpu
 * 						y memoria.
 * 
 * 
 * @class
 * @author Ignacio David Baigorria
 * @package Iteradores\Nucleo
 * @version 2.0.1.251006
 * @since 0.0
 * @implements Interfaces\Id
 * @implements Interfaces\ErroresYAlertas
 */
class Objeto implements Id, ErroresYAlertas
{
/*.......................................*/


	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	//// INTERFACE ID	
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////////////////////
	// Interface Id - Métodos y variables auxiliares
	////////////////////////////////////////////////////////////////////////////////////////

	//VARIABLES DE CLASE PRIVADAS:

	/**
	 * Contador interno de IDs generados.
	 *
	 * @internal
	 * @var int
	 */
	private static $contador_ids = 1; //esta prohibido el id 0

	/**
	 * Depósito interno de IDs ya asignados.
	 *
	 * Evita que se repitan IDs entre objetos.
	 *
	 * @internal
	 * @var array<string, bool>
	 */
	private static $deposito_de_ids=[];

	//VARIABLES DE INSTANCIA PRIVADAS:

	/**
	 * ID del objeto.
	 *
	 * @internal
	 * @var string|null
	 */
	private $id;

	//METODOS AUXILIARES
	/**
	 * Genera un nuevo ID único para un objeto.
	 *
	 * Este método utiliza un contador interno y devuelve un string único.
	 * Es un método auxiliar, solo accesible dentro de la clase.
	 *
	 * @internal
	 * @return string El ID generado.
	 * @deprecated aunque elegante en el papel ineficiente cuando se van a crear muchisimos objetos.
	 * 				Ahora se realiza directamente en el id()
	 */

	private static function crear_id(): string
	{
		//$id= "s_".session_id()."_".$GLOBALS['num_hilo']."_".Objeto::$contador_ids;
		$id = Objeto::$contador_ids;
		Objeto::$contador_ids++;
		return (string)$id;
	}

	/**
	 * Intenta agregar un ID al depósito de IDs existentes.
	 *
	 * Garantiza que el ID no se haya asignado a ningún otro objeto.
	 * Se utiliza internamente en el sistema de generación de IDs.
	 *
	 * @internal
	 * @param string $id El ID que se intenta agregar.
	 * @return bool True si el ID fue agregado exitosamente (no estaba repetido), false si ya existía.
	 * @deprecated aunque elegante en el papel ineficiente cuando se van a crear muchisimos objetos.
	 * 				Ahora se realiza directamente en el _id()
	 */
	private static function agregar_id($id): bool
	{
		if (!isset(Objeto::$deposito_de_ids[$id])) {
			Objeto::$deposito_de_ids[$id] = true;
			return true;
		} else {
			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Interface Id - Métodos auxiliares protegidos
	////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Verifica si un identificador dado es especial.
	 * 
	 * Este metodo pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Interfaz Id}
	 * 
	 * Un **id especial** es aquel que es una cadena no numérica.  
	 * Se utiliza internamente para determinar si un id proporcionado
	 * es válido para ser asignado a un objeto mediante el método
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()}.
	 *
	 * Ejemplo de uso:
	 * ```php
	 * if (self::es_id_especial($id)) {
	 *		echo "el id $id es especial";
	 * }else{
	 * 		echo "el id no es especial";
	 * }
	 *```		 
 	 * @note Actualmente, lo que determina si un id es especial es simplemente
     *       que sea un string no numérico. Esto podría cambiar en el futuro
     *       si se implementa un sistema para evitar ids repetidos.
	 * @param string $id El id a comprobar.
	 * @return bool `true` si el id es especial, `false` en caso contrario.
	 */
	public static function es_id_especial(string $id): bool
	{
		return is_string($id) && !is_numeric($id);
	}
	/**
	 * Asigna un identificador único **sin realizar comprobaciones adicionales**.
	 *
	 * Este método pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Interfaz Id}
	 *
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()}, 
	 * esta versión **no verifica** si el objeto ya posee id ni si el id es especial.
	 * Se debe usar **exclusivamente** en clases que heredan de esta, y **bajo responsabilidad del programador**,
	 * asegurando que:
	 * - El id no haya sido previamente asignado.
	 * - El id sea válido y único.
	 *
	 * Está pensada para contextos donde el control ya se realiza externamente,
	 * permitiendo ahorrar CPU y memoria al omitir verificaciones redundantes.
	 *
	 * Si el id ya existe en el depósito global, se registra un error y devuelve `false`.
	 * 
	 * Métodos relacionados:
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()} Versión segura con comprobaciones.
	 *
	 * @param string $id El id a asignar.
	 * @return bool `true` si fue asignado exitosamente, `false` en caso contrario.
	 *
	 * @since V2.0.1
	 * @example
	 * // Ejemplo dentro de una clase heredera:
	 * protected function crearNodoRapido($id) {
	 * 		if (Objeto::es_id_especial($id)) {
	 *     		return $this->_id_interno($id);
	 * 		}
	 * }
	 */
	protected function _id_interno(string $id): bool
	{
		//agrego id al deposito
		if (isset(Objeto::$deposito_de_ids[$id])) {
			$this->_error("Ya existe ese id");
			return false;
		}
		Objeto::$deposito_de_ids[$id] = true;
		$this->id = $id;
		return true;
	}
	////////////////////////////////////////////////////////////////////////////////////////
	// Interface Id - Métodos publicos
	////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Devuelve el identificador único del objeto (Interfaz Id).
	 *
	 * Este metodo pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Interfaz Id}
	 * 
	 * Si el objeto aún no tiene un id, se le asigna uno nuevo de forma automática
	 * mediante **inicialización perezosa**, asegurando que no esté repetido.
	 *
	 * Este método se usa para obtener un id persistente que identifique de forma
	 * única a cada instancia del objeto en el sistema.
	 *
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id _id()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_especial es_especial()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * echo $mi_objeto->id(); // Ej: "obj_12345"
	 * ```
	 * @note A futuro se podría mejorar el algoritmo para garantizar unicidad
	 *       global incluso entre sesiones distintas.
	 * @return string El id único del objeto.
	 */
	public function id(): string
	{
		//inicializacion perezosa
		if ($this->id===null) {
			return $this->id=Objeto::$contador_ids++;
		}
		return $this->id;
	}

	/**
	 * Asigna un identificador único al objeto (Interfaz Id).
	 *
	 * Este método pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Id.html Interfaz Id}
	 * 
	 * Solo puede ejecutarse con éxito si el objeto no posee ya un id asignado.
	 * Además, el id proporcionado debe ser **especial** (debe pasar positivamente la 
	 * verificación realizada por {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_id_especial es_id_especial(id)})
	 * y no estar repetido en otros objetos.
	 *
	 * Este método complementa a:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_id id()} (para obtener el id actual)
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_es_especial es_especial()} (para verificar si es especial)
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id_interno _id_interno()} (versión optimizada para herencia y uso interno)
	 *
	 * ⚠️ Nota: Si estás implementando una clase que hereda de esta, podés usar
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__id_interno _id_interno()} 
	 * cuando estés seguro de que el id aún no fue asignado y es válido, para evitar comprobaciones redundantes.
	 *
	 * Si la asignación falla, se registrará un error mediante el sistema de errores
	 * centralizado de la clase.
	 *
	 * @param string $id El id a asignar (debe ser una cadena no numérica).
	 * @return bool `true` si el id fue asignado exitosamente, `false` en caso contrario.
	 *
	 * @example
	 * if ($obj->_id("mi_id_especial")) {
	 *     echo "Asignado id especial: ".$obj->id();
	 * } else {
	 *     echo "Error asignando id especial";
	 * }
	 */
	public function _id(string $id): bool
	{
		if ($this->id!==null) {
			$this->_error("El objeto ya tenia id");
			return false;
		}
		if (Objeto::es_id_especial($id)) {
					//agrego id al deposito
			if (isset(Objeto::$deposito_de_ids[$id])) {
				$this->_error("Ya existe ese id");
				return false;
			}
			Objeto::$deposito_de_ids[$id] = true;
			$this->id = $id;
			return true;
		}
		$this->_error("Para asignar un id, este debe ser especial");
		return false;
	}



	/**
	 * Comprueba si el objeto actual posee un id especial (Interfaz Id).
	 *
	 * Se considera especial cuando el id del objeto es una cadena no numérica.
	 * 
	 * Ejemplo de uso:
	 * ```php
	 * if ($mi_objeto-> es_especial()){
	 *      echo "el objeto tiene id especial: ".$mi_objeto->id();
	 * }else{
	 *      echo "el objeto no es especial";
	 * }
	 * ```
	 * @note Si el objeto no tenia Id especial ni común antes de llamar a este metodo, se le asigna uno comun.
	 * @see Interfaces\Id (Interface)
	 * @see Objeto::id()
	 * @see Objeto::_id()
	 * @return bool `true` si el objeto tiene un id especial, `false` en caso contrario.
	 */
	public function es_especial(): bool
	{
		if ($this->id===null){
			return false;
		}
		return Objeto::es_id_especial($this->id);
	}

	//****************************************************//
	//		REALIZA LAS OPERACIONES PARA QUE FUNCIONE LA  // 
	//		CLASE Objeto (YA NO SE USA)					  //
	//****************************************************//

	/**
	 * @var int Número de hilo utilizado anteriormente para identificar distintos hilos de las mismas
	 * sesiones o no para identificar objetos.
	 * @deprecated Esta propiedad ya no se utiliza. Se mantiene solo porque en el futuro puede volver
	 * a necesitarse.
	 */
	private static $num_hilo = 0;

	/**
	 * @var bool Indica si la clase fue inicializada.
	 * @deprecated Ya no se utiliza el sistema de inicialización basado en base de datos. Además el 
	 * sistema actual de identificación de objetos no depende de la base de datos ni del número de hilo 
	 * pero se deja porque talvez en un futuro se retome.
	 */
	private static $inicializo = false;

	/**
	 * Inicializa la clase Objeto conectando a la base de datos y gestionando el número de hilo.
	 *
	 * Realiza las operaciones de conexión a MySQL, crea la base de datos y la tabla `hilo` si no existen,
	 * inserta un registro con el `session_id()` actual para obtener un identificador incremental, y lo almacena
	 * en {@see self::$num_hilo}. Luego borra el registro anterior.
	 *
	 * @deprecated Este método ya no se utiliza. El sistema actual de identificación de objetos
	 * no depende de la base de datos ni del número de hilo pero se deja porque talvez en un futuro 
	 * se retome
	 *
	 * @return void
	 */
	public static function inicializacion()
	{
		if (self::$inicializo) {
			return;
		}
		//conecto a la bd sql
		$sql = null;
		if (Conf::LOCAL) {
			$sql = new \mysqli(Conf::HOST_SQL, Conf::USUARIO_SQL, Conf::CONTRASENA_SQL);
			//creo BD si no fue creada
			$sql->query("CREATE DATABASE IF NOT EXISTS " . Conf::NOMBRE_BD_SQL) or die("no creo database");
			//selecciono la BD
			$sql->select_db(Conf::NOMBRE_BD_SQL) or die("no select database");
		} else {
			$sql = new \mysqli(Conf::HOST_SQL, Conf::USUARIO_SQL, Conf::CONTRASENA_SQL, Conf::NOMBRE_BD_SQL);
		}

		$charset = $sql->character_set_name();
		if ($charset != "utf8mb4") {
			$sql->set_charset("utf8mb4");
		}
		//echo $sql->character_set_name();

		//Creo la tabla si no existe
		$sql->query("CREATE TABLE IF NOT EXISTS hilo (
		id MEDIUMINT NOT NULL AUTO_INCREMENT,
		idsession CHAR(32) NOT NULL,
		PRIMARY KEY (id)
		)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ") or die("no creo tabla");
		//Inserto el IdSession y guardo el id generado en la variable $num_hilo
		$sql->query("INSERT INTO hilo (idsession) values ('" . session_id() . "');");

		//**************** NUMERO DE HILO:
		self::$num_hilo = $sql->insert_id;
		//********************************
		//borro el anterior de la base de datos para que no aumente de tamano
		$sql->query("DELETE FROM `" . Conf::NOMBRE_BD_SQL . "`.`hilo` WHERE `hilo`.`id` = " . (self::$num_hilo - 1));
		//cierro coneccion
		$sql->close();
		//mysql_close($link);
		self::$inicializo = true;
	}
}//FIN Clase Objeto
//Nodo::inicializacion();
//funcion global para imprimir los errores
/*function imprimir_errores(){
	Objeto::imprimir_errores();
}*/

/*$o1=new Objeto;

$o1->_id("hola");
$o1->_id("hola");
$o1->_id(132132);
echo $o1->id();
Objeto::imprimir_errores();*/
?>