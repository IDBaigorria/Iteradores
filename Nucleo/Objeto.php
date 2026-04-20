<?php
namespace Iteradores\Nucleo;
use Iteradores\Configuracion\Conf;
use Iteradores\Nucleo\Interfaces\Id;
use Iteradores\Nucleo\Interfaces\ErroresYAlertas;
session_start();
header("Cache-control: no-cache, must-revalidate");
require_once(".\configuracion\Configuracion.php");
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
 *   - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
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
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	//// INTERFACE ERRORESYALERTAS
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////

	//************************************************************************************************
	//Interface Errores (ErroresYAlertas) **********************************************************
	//*********************///////////////////////////////////////////////////////////////////////////

	/**
	 * Lista de errores ocurridos
	 *
	 * @var array<int,array{fecha:string,mensaje:string,pila:array}>
	 */
	private static $errores;
	/**
	 * Contador de errores acumulados
	 *
	 * @var int
	 */
	private static $contador_errores = 0;
	/**
	 * Habilita o deshabilita el registro de errores
	 *
	 * @var bool
	 */
	private static $activar_rec_errores = Conf::ACTIVAR_ERRORES;
	/**
	 * Determina si se incluyen argumentos en la pila de errores
	 *
	 * @var bool
	 */
	private static $incluir_args_errores = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS;
	/**
	 * Determina si se incluyen objetos en la pila de errores
	 *
	 * @var bool
	 */
	private static $incluir_objetos_errores = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS;
	/**
	 * Profundidad maxima de la pila de errores
	 *
	 * @var int
	 */
	private static $limite_pila_de_llamadas_errores = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE + 2;//Le sumo 2 porque 2 se pierden en las llamadas internas para agregar los errores

	/**
	 * Auxiliar. Agrega un error a la lista de errores internos.
	 *
	 * Con el error agrega lista la 'fecha', el 'mensaje' y la 'traza' o pila de llamadas. 
	 * 
	 * @param string $error Mensaje de error a registrar.
	 * @see Objeto::_error()
	 * @return void
	 */
	private static function agregar_error($error)
	{
		$d = new \DateTime("now");
		$flags = 0;
		if (!Objeto::$incluir_args_errores) {
			$flags |= DEBUG_BACKTRACE_IGNORE_ARGS;
		}
		if (Objeto::$incluir_objetos_errores) {
			$flags |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
		}
		$traza = debug_backtrace($flags, self::$limite_pila_de_llamadas_errores);
		Objeto::$errores[Objeto::$contador_errores] = [
			'fecha' => $d->format("Y-m-d H:i:s.u"),
			'mensaje' => $error,
			'pila' => $traza
		];
		Objeto::$contador_errores++;
	}

	/**
	 * Registra un error si el sistema de errores está activado (Interfaz Errores)
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Lo que hace es recibir un mensaje (un string) como parámetro que de sierta información 
	 * que pueda ser necesaria al programador para que hubique rapidamente el el error que se produjo y 
	 * poder corregirlo. Cuando el mensaje es enviado la funcion lo agrega a una lista o pila de mensajes 
	 * de error. Para poder observar los mensajes de error existe otra funcion llamada imprimir_errores().
	 * 
	 * La lista de errores puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado. 
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 *
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos  
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * 
	 * Ejemplo de uso:
	 * ```php
	 * class Mi_clase extends Objeto{ //Objeto implementa la inteface Errores
	 * ... 
	 *      function una_funcion(){
	 *      ...
	 *          If (...){//Se produjo un error
	 *      		// ✅ Esto anda perfecto:
	 *      		MiClase::_error("Error desde MiClase");
	 *
	 *      		// También podrías:
	 *      		Objeto::_error("Error desde MiClase");
	 * 
	 *      		// Y también:
	 *  	   		self::_error("Error desde MiClase");
	 *      		return false;
	 *          }
	 *      ...
	 *      }//fin una funcion
	 *      ...
	 * }//Fin clase 
	 * ...
	 * $mi_objeto= new Mi_clase();
	 * ...
	 * $mi_objeto->una_funcion() or die(Mi_clase::imprimir_errores());
 	 * ```
	 * @param string $error Mensaje de error a registrar.
	 * @return void
	 */
	public static function _error($error)
	{
		if (Objeto::$activar_rec_errores) {
			//Inicializacion perezosa
			if (Objeto::$errores == null) {
				Objeto::$errores = array();
			}
			;
			if (!is_string($error)) {
				//este es el unico alerta que no aparecera el nombre de la clase y el objeto de manera automatica
				Objeto::agregar_error("Objeto::_error(error) El error asignando un mensaje de error; los mensajes de error deben ser String");
			} else {
				Objeto::agregar_error($error);
			}
		}
	}

	/**
	 * Imprime en consola (o en la salida estándar) la lista de errores
	 * registrados (interfaz Errores)
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método muestra todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 *
	 * La lista de errores puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de Uso:
	 * ```php
	 * class MiClase extends Objeto { //Objeto implementa la interfaz Errores
	 *     public function una_funcion() {
	 *         if (...) { // Se produjo un error
	 *             self::_error("Error desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Imprimir todos los errores registrados hasta el momento
	 *     MiClase::imprimir_errores();
	 * }
	 *```
	 * @return void No devuelve ningún valor.
	 */
	public static function imprimir_errores()
	{
		//self::_error(45);
		if (empty(self::$errores)) {
			echo "<p><i>No hay errores registrados.</i></p>";
			return;
		}
		echo "<ul id='inicio_errores'><strong style='font-size:xx-large'>Errores:</strong>";

		foreach (self::$errores as $error) {
			$pila = $error['pila'];
			$cant = count($pila);
			// Nivel de origen
			$ini = $cant>=3?2:1;//2
			//echo $ini;
			//echo $cant."hg";
			$origen = $pila[$ini] ?? null;
			//$origen = $pila[2] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			echo "<li style='margin-bottom:25px' >";
			echo "<strong>[{$error['fecha']}] " . htmlspecialchars($error['mensaje']) . "</strong>";
			$archivo = $origen['file'] ?? '';
			$linea = $origen['line'] ?? '';
			if ($archivo) {
				echo " en <strong>$archivo:$linea</strong>";
			}
			if ($firma_origen) {
				echo "<br><em>Origen:</em> " . htmlspecialchars($firma_origen);
				if ($obj = $origen['object'] ?? null) {
					echo "<pre>" . htmlspecialchars(print_r($obj, true)) . "</pre>";
				} else {
					echo "<br/><br/>";
				}
			}
			echo "<a href='#inicio_errores'>↑ Volver al primer error</a> </div>  <br>";

			// Pila de llamadas
			if ($ini + 1 < $cant) {
				echo "<br><u>Pila de llamadas:</u>";
			}
			echo "<ul>";
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';

				echo "<li style=margin-bottom:25px>" . htmlspecialchars($firma);
				if ($archivo) {
					echo " en <strong>$archivo:$linea</strong>";
				}
				if ($obj = $origen['object'] ?? null) {
					echo "<pre>" . htmlspecialchars(print_r($obj, true)) . "</pre>";
				} else {
					echo "<br/><br/>";
				}
				echo "<a href='#inicio_errores'>↑ Volver al primer error</a> </div>  <br/>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</li>";
		}
		echo "</ul>";
	}

	/**
	 * Imprime en la consola (salida estándar con formato) todos los errores
	 * registrados (Interfaz Errores).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método muestra todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()},
	 * este método está pensado para mostrar los errores directamente en la
	 * consola del entorno de desarrollo (CLI o navegador con consola activa)
	 * en un formato más claro y legible.
	 *
	 * La lista de errores puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 * 
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * Ejemplo de uso:
	 * ```php
	 * class MiClase extends Objeto {
	 *     public function una_funcion() {
	 *         if (...) {
	 *             self::_error("Error desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Imprime los errores en la consola
	 *     MiClase::imprimir_errores_consola();
	 * }
	 * ```
	 *
	 * @return void No devuelve ningún valor.
	 */
	public static function imprimir_errores_consola()
	{
		if (empty(self::$errores)) {
			echo "(No hay errores registrados)\n";
			return;
		}

		echo "===== ERRORES =====\n";
		foreach (self::$errores as $error) {
			$pila = $error['pila'];

			// Nivel de origen
			$cant = count($pila);
			// Nivel de origen
			$ini = $cant>=3?2:1;//2
			//echo $cant."hg";
			$origen = $pila[$ini] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			echo "[{$error['fecha']}] {$error['mensaje']}\n";
			$archivo = $origen['file'] ?? '';
			$linea = $origen['line'] ?? '';
			if ($archivo) {
				echo " en $archivo:$linea";
			}
			if ($firma_origen) {
				echo "  Origen: $firma_origen\n";
				if ($obj = $origen['object'] ?? null) {
					echo "  Objeto:\n";
					print_r($obj); // lo dejamos en formato crudo
				} else {
					echo "\n\n";
				}
			}

			// Pila de llamadas
			if ($ini + 1 < $cant) {
				echo "  Pila de llamadas:\n";
			}
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';

				echo "   → $firma";
				if ($archivo) {
					echo " en $archivo:$linea";
				}
				echo "\n";

				if ($obj = $nivel['object'] ?? null) {
					echo "     Objeto:\n";
					print_r($obj);
				} else {
					echo "\n\n";
				}
			}

			echo "------------------------\n";
		}
	}

	/**
	 * Genera y devuelve un bloque HTML con todos los errores registrados.
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método devuelve todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()},
	 * este método no imprime directamente los errores, sino que devuelve
	 * una cadena HTML que puede insertarse en una página para mostrar
	 * la información de los errores al usuario o desarrollador.
	 *
	 * La lista de errores puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * Ejemplo de uso:
	 * ```php
	 * class MiClase extends Objeto {
	 *     public function una_funcion() {
	 *         if (...) {
	 *             self::_error("Error desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Obtiene el HTML de los errores y lo muestra en la página
	 *     echo MiClase::html_errores();
	 * }
	 * ```
	 *
	 * @return string HTML con la representación de todos los errores registrados.
	 */
	public static function html_errores()
	{
		ob_start();
		self::imprimir_errores();
		return ob_get_clean();
	}

	/**
	 * Devuelve la lista de errores registrados en formato JSON (Interfaz Errores).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método devuelve todos los mensajes de error que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()},
	 * este método no imprime directamente los errores, sino que devuelve
	 * una cadena JSON.
	 * Esto permite transportar o almacenar la información de errores de manera estructurada.
	 *
	 * La lista de errores puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * $miObjeto = new MiClase();
	 * ...
	 * if (!$miObjeto->una_funcion()) {
	 *     echo MiClase::json_errores(); // Devuelve un JSON con todos los errores
	 * }
	 * ```
	 *
	 * @return string JSON con la lista de errores registrados.
	 */
	public static function json_errores() {
		self::_error(45);

		if (empty(self::$errores)) {
			return json_encode([
				'mensaje' => 'No hay errores registrados',
				'errores' => []
			]);
		}

		$erroresJSON = [];

		foreach (self::$errores as $error) {
			$pila = $error['pila'] ?? [];
			$cant = count($pila);
			$ini = 2; // mismo nivel de origen que en imprimir_errores
			$origen = $pila[$ini] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			// Construir la pila de llamadas
			$pilaLlamadas = [];
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';
				$obj = $nivel['object'] ?? null;

				$pilaLlamadas[] = [
					'firma' => $firma,
					'archivo' => $archivo,
					'linea' => $linea,
					'object' => $obj
				];
			}

			$erroresJSON[] = [
				'fecha' => $error['fecha'] ?? null,
				'mensaje' => $error['mensaje'] ?? '',
				'origen' => [
					'firma' => $firma_origen,
					'archivo' => $origen['file'] ?? '',
					'linea' => $origen['line'] ?? '',
					'object' => $origen['object'] ?? null
				],
				'pila' => $pilaLlamadas
			];
		}

		return json_encode([
			'mensaje' => count(self::$errores) . ' error(es) registrados',
			'errores' => $erroresJSON
		], JSON_PRETTY_PRINT);
	}

	/**
	 * Activa la recolección de errores del sistema (Interface Errores).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Esta función permite habilitar dinamicamente la captura y almacenamiento de errores
	 * dentro del sistema centralizado, independientemente del valor inicial configurado en
	 * la constante {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}. Permite
	 * sobrescribir temporalmente la configuración predeterminada para activar la recopilación
	 * de mensajes de error durante la ejecución. 
	 * 
	 * Una vez activada, cualquier error registrado mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()} 
	 * se almacenará y podrá ser consultado posteriormente mediante los métodos de visualización.
	 *
     * Para volver a desactivar dinamicamente la recoleccion de errores puede usarse {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * 
	 * La lista de errores puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()} 
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::activar_errores();
	 * ```
	 *
	 * @return void
	 */
	public static function activar_errores()
	{
		Objeto::$activar_rec_errores = true;
	}
	/**
	 * Desactiva la recolección de errores del sistema (Interface Errores).
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Errores.html Interfaz Errores}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método deshabilita dinámicamente la recolección de errores en el sistema, incluso si la constante 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES} 
	 * está establecida en true. De esta forma, permite sobrescribir temporalmente el comportamiento 
	 * predeterminado para detener la recopilación de mensajes de error.
	 * 
	 * De esta manera se impide que los errores registrados mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()} 
	 * se agreguen a la lista centralizada. 
	 * 
	 * Los errores que ocurran mientras el sistema esté
	 * desactivado no serán almacenados ni mostrados por los métodos de visualización.
	 *
	 * Para volver a activar la recoleccion de errores puede usarse {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * 
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al error registrado.     		
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *      - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Configuración relacionada:
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf:: ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 *
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::desactivar_errores();
	 * ```
	 *
	 * @return void
	 */
	public static function desactivar_errores()
	{
		Objeto::$activar_rec_errores = false;
	}
	
	
	//************************************************************************************************
	//Interface Alerta (ErroresYAlertas)**************************************************************
	//*********************///////////////////////////////////////////////////////////////////////////

	/**
	 * Lista de alertas ocurridos
	 *
	 * @var array<int,array{fecha:string,mensaje:string,pila:array}>
	 */
	private static $alertas;
	/**
	 * Contador de alertas acumulados
	 *
	 * @var int
	 */
	private static $contador_alertas = 0;
	/**
	 * Habilita o deshabilita el registro de alertas
	 *
	 * @var bool
	 */
	private static $activar_rec_alertas = Conf::ACTIVAR_ALERTAS;
	/**
	 * Determina si se incluyen argumentos en la pila de alertas
	 *
	 * @var bool
	 */
	private static $incluir_args_alertas = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS;
	/**
	 * Determina si se incluyen objetos en la pila de alertas
	 *
	 * @var bool
	 */
	private static $incluir_objetos_alertas = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS;
	/**
	 * Profundidad maxima de la pila de alertas
	 *
	 * @var int
	 */
	private static $limite_pila_de_llamadas_alertas = Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE + 2;//Le sumo 2 porque 2 se pierden en las llamadas internas para agregar las alertas

	/**
	 * Auxiliar. Agrega un alerta a la lista de alertas internos.
	 *
	 * Con el alerta agrega lista la 'fecha', el 'mensaje' y la 'traza' o pila de llamadas. 
	 * 
	 * @param string $alerta Mensaje de alerta a registrar.
	 * @see Objeto::_alerta()
	 * @return void
	 */
	private static function agregar_alerta($alerta)
	{
		$d = new \DateTime("now");
		$flags = 0;
		if (!Objeto::$incluir_args_alertas) {
			$flags |= DEBUG_BACKTRACE_IGNORE_ARGS;
		}
		if (Objeto::$incluir_objetos_alertas) {
			$flags |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
		}
		$traza = debug_backtrace($flags, self::$limite_pila_de_llamadas_alertas);
		Objeto::$alertas[Objeto::$contador_alertas] = [
			'fecha' => $d->format("Y-m-d H:i:s.u"),
			'mensaje' => $alerta,
			'pila' => $traza
		];
		Objeto::$contador_alertas++;
	}

/**
	 * Registra una alerta si el sistema de alertas está activado (Interfaz Alertas)
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Lo que hace es recibir un mensaje (un string) como parámetro que de sierta información 
	 * que pueda ser necesaria al programador para que hubique rapidamente el el alerta que se produjo y 
	 * poder corregirlo. Cuando el mensaje es enviado la funcion lo agrega a una lista o pila de mensajes 
	 * de alerta. Para poder observar los mensajes de alerta existe otra funcion llamada imprimir_alertas().
	 * 
	 * La lista de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos  
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * 
	 * Ejemplo de uso:
	 * ```php
	 * class Mi_clase extends Objeto{ //Objeto implementa la inteface Alertas
	 * ... 
	 *      function una_funcion(){
	 *      ...
	 *          If (...){//Se produjo una alerta
	 *      		// ✅ Esto anda perfecto:
	 *      		MiClase::_alerta("Alerta desde MiClase");
	 *
	 *      		// También podrías:
	 *      		Objeto::_alerta("Alerta desde MiClase");
	 * 
	 *      		// Y también:
	 *  	   		self::_alerta("Alerta desde MiClase");
	 *      		return false;
	 *          }
	 *      ...
	 *      }//fin una funcion
	 *      ...
	 * }//Fin clase 
	 * ...
	 * $mi_objeto= new Mi_clase();
	 * ...
	 * $mi_objeto->una_funcion() or die(Mi_clase::imprimir_alertas());
 	 * ```
	 * @param string $alerta Mensaje de alerta a registrar.
	 * @return void
	 */
	static public function _alerta($alerta)
	{
		if (Objeto::$activar_rec_alertas) {
			//echo "JJAA56A";
			//Inicializacion perezosa
			if (Objeto::$alertas == null) {
				Objeto::$alertas = array();
			}
			;
			if (!is_string($alerta)) {
				//este es el unico alerta que no aparecera el nombre de la clase y el objeto de manera automatica
				Objeto::agregar_error("Objeto::_alerta(alerta) El alerta asignando un mensaje de alerta; los mensajes de alerta deben ser String");
			} else {
				Objeto::agregar_alerta($alerta);
			}
		}
	}

	/**
	 * Imprime en consola (o en la salida estándar) la lista de alertas
	 * registrados (interfaz Alertas)
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método muestra todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}, al sistema 
     * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 *
	 * La lista de alertas puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de Uso:
	 * ```php
	 * class MiClase extends Objeto { //Objeto implementa la interfaz Alertas
	 *     public function una_funcion() {
	 *         if (...) { // Se produjo una alerta
	 *             self::_alerta("Alerta desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Imprimir todos los alertas registrados hasta el momento
	 *     MiClase::imprimir_alertas();
	 * }
	 *```
	 * @return void No devuelve ningún valor.
	 */
	public static function imprimir_alertas()
	{
		if (empty(self::$alertas)) {
			echo "<p><i>No hay alertas registrados.</i></p>";
			return;
		}
		echo "<a ></a>";
		echo "<ul id='inicio_alertas'><strong style='font-size:xx-large'>Alertas:</strong>";
		foreach (self::$alertas as $alerta) {
			$pila = $alerta['pila'];
			$cant = count($pila);
			// Nivel de origen
			$ini = 2;//$cant>=3?2:1;
			//echo $cant."hg";
			$origen = $pila[$ini] ?? null;
			// Nivel de origen
			$ini = $cant>=3?2:1;//2
			//echo $cant."hg";
			$origen = $pila[$ini] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			echo "<li style=margin-bottom:25px>";
			echo "<strong>[{$alerta['fecha']}] " . htmlspecialchars($alerta['mensaje']) . "</strong>";
			$archivo = $origen['file'] ?? '';
			$linea = $origen['line'] ?? '';
			if ($archivo) {
				echo " en <strong>$archivo:$linea</strong>";
			}
			if ($firma_origen) {
				echo "<br><em>Origen:</em> " . htmlspecialchars($firma_origen);
				if ($obj = $origen['object'] ?? null) {
					echo "<pre>" . htmlspecialchars(print_r($obj, true)) . "</pre>";
				} else {
					echo "<br/><br/>";
				}
			}
			echo "<a href='#inicio_alertas'>↑ Volver al primer alerta</a> </div>  <br/>";

			// Pila de llamadas
			if ($ini + 1 < $cant) {
				echo "<br><u>Pila de llamadas:</u><ul>";
			}
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';

				echo "<li style=margin-bottom:25px>" . htmlspecialchars($firma);
				if ($archivo) {
					echo " en <strong>$archivo:$linea</strong>";
				}
				if ($obj = $origen['object'] ?? null) {
					echo "<pre>" . htmlspecialchars(print_r($obj, true)) . "</pre>";
				} else {
					echo "<br/><br/>";
				}
				echo "<a href='#inicio_alertas'>↑ Volver al primer alerta</a> </div>  <br/>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</li>";
		}
		echo "</ul>";
	}

	/**
	 * Imprime en la consola (salida estándar con formato) todos los alertas
	 * registrados (Interfaz Alertas).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método muestra todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()},
	 * este método está pensado para mostrar los alertas directamente en la
	 * consola del entorno de desarrollo (CLI o navegador con consola activa)
	 * en un formato más claro y legible.
	 *
	 * La lista de alertas puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 * 
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * Ejemplo de uso:
	 * ```php
	 * class MiClase extends Objeto {
	 *     public function una_funcion() {
	 *         if (...) {
	 *             self::_alerta("Alerta desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Imprime los alertas en la consola
	 *     MiClase::imprimir_alertas_consola();
	 * }
	 * ```
	 *
	 * @return void No devuelve ningún valor.
	 */
	public static function imprimir_alertas_consola()
	{
		if (empty(self::$alertas)) {
			echo "(No hay alertas registrados)\n";
			return;
		}

		echo "===== ALERTAS =====\n";
		foreach (self::$alertas as $alerta) {
			$pila = $alerta['pila'];

			// Nivel de origen
			$cant = count($pila);
			// Nivel de origen
			$ini = $cant>=3?2:1;//2
			//echo $cant."hg";
			$origen = $pila[$ini] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			echo "[{$alerta['fecha']}] {$alerta['mensaje']}\n";
			$archivo = $origen['file'] ?? '';
			$linea = $origen['line'] ?? '';
			if ($archivo) {
				echo " en $archivo:$linea";
			}
			if ($firma_origen) {
				echo "  Origen: $firma_origen\n";
				if ($obj = $origen['object'] ?? null) {
					echo "  Objeto:\n";
					print_r($obj); // lo dejamos en formato crudo
				} else {
					echo "\n\n";
				}
			}

			// Pila de llamadas
			if ($ini + 1 < $cant) {
				echo "  Pila de llamadas:\n";
			}
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';

				echo "   → $firma";
				if ($archivo) {
					echo " en $archivo:$linea";
				}
				echo "\n";

				if ($obj = $nivel['object'] ?? null) {
					echo "     Objeto:\n";
					print_r($obj);
				} else {
					echo "\n\n";
				}
			}

			echo "------------------------\n";
		}
	}

	/**
	 * Genera y devuelve un bloque HTML con todos los alertas registrados.
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método devuelve todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()},
	 * este método no imprime directamente los alertas, sino que devuelve
	 * una cadena HTML que puede insertarse en una página para mostrar
	 * la información de los alertas al usuario o desarrollador.
	 *
	 * La lista de alertas puede visualizarse usando también:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * Ejemplo de uso:
	 * ```php
	 * class MiClase extends Objeto {
	 *     public function una_funcion() {
	 *         if (...) {
	 *             self::_alerta("Alerta desde MiClase");
	 *             return false;
	 *         }
	 *         return true;
	 *     }
	 * }
	 *
	 * $miObjeto = new MiClase();
	 * if (!$miObjeto->una_funcion()) {
	 *     // ✅ Obtiene el HTML de los alertas y lo muestra en la página
	 *     echo MiClase::html_alertas();
	 * }
	 * ```
	 *
	 * @return string HTML con la representación de todos los alertas registrados.
	 */

	public static function html_alertas()
	{
		ob_start();
		self::imprimir_alertas();
		return ob_get_clean();
	}
	/**
	 * Devuelve la lista de alertas registrados en formato JSON (Interfaz Alertas).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método devuelve todos los mensajes de alerta que fueron agregados
	 * con llamadas a {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()}, al sistema 
	 * centralizado, junto con la pila de llamadas, 
	 * permitiendo al programador diagnosticar y depurar más fácilmente el
	 * origen de los problemas.
	 * 
	 * A diferencia de {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()},
	 * este método no imprime directamente los alertas, sino que devuelve
	 * una cadena JSON.
	 * Esto permite transportar o almacenar la información de alertas de manera estructurada.
	 *
	 * La lista de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * $miObjeto = new MiClase();
	 * ...
	 * if (!$miObjeto->una_funcion()) {
	 *     echo MiClase::json_alertas(); // Devuelve un JSON con todos los alertas
	 * }
	 * ```
	 *
	 * @return string JSON con la lista de alertas registrados.
	 */
	public static function json_alertas() {
		self::_alerta(45);

		if (empty(self::$alertas)) {
			return json_encode([
				'mensaje' => 'No hay alertas registrados',
				'alertas' => []
			]);
		}

		$alertasJSON = [];

		foreach (self::$alertas as $alerta) {
			$pila = $alerta['pila'] ?? [];
			$cant = count($pila);
			$ini = 2; // mismo nivel de origen que en imprimir_alertas
			$origen = $pila[$ini] ?? null;
			$firma_origen = $origen ? self::obtener_firma_funcion($origen) : '';

			// Construir la pila de llamadas
			$pilaLlamadas = [];
			for ($i = $ini + 1; $i < $cant; $i++) {
				$nivel = $pila[$i];
				$firma = self::obtener_firma_funcion($nivel);
				$archivo = $nivel['file'] ?? '';
				$linea = $nivel['line'] ?? '';
				$obj = $nivel['object'] ?? null;

				$pilaLlamadas[] = [
					'firma' => $firma,
					'archivo' => $archivo,
					'linea' => $linea,
					'object' => $obj
				];
			}

			$alertasJSON[] = [
				'fecha' => $alerta['fecha'] ?? null,
				'mensaje' => $alerta['mensaje'] ?? '',
				'origen' => [
					'firma' => $firma_origen,
					'archivo' => $origen['file'] ?? '',
					'linea' => $origen['line'] ?? '',
					'object' => $origen['object'] ?? null
				],
				'pila' => $pilaLlamadas
			];
		}

		return json_encode([
			'mensaje' => count(self::$alertas) . ' alerta(es) registrados',
			'alertas' => $alertasJSON
		], JSON_PRETTY_PRINT);
	}
	/**
	 * Activa la recolección de alertas del sistema (Interface Alertas).
	 *
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Esta función permite habilitar dinamicamente la captura y almacenamiento de alertas
	 * dentro del sistema centralizado, independientemente del valor inicial configurado en la constante 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}. 
	 * Permite sobrescribir temporalmente la configuración predeterminada para activar la recopilación
	 * de mensajes de error durante la ejecución. 
	 * 
	 * Una vez activada, cualquier alerta registrado mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()} 
	 * se almacenará y podrá ser consultado posteriormente mediante los métodos de visualización.
	 *
     * Para volver a desactivar la recoleccion de alertas puede usarse {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * 
	 * La lista de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()} 
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::activar_alertas();
	 * ```
	 *
	 * @return void
	 */
		public static function activar_alertas()
	{
		Objeto::$activar_rec_alertas = true;
	}
	/**
	 * Desactiva la recolección de alertas del sistema (Interface Alertas).
	 * 
	 * Este metodo pertenece a las interfaces:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-Alertas.html Interfaz Alertas}
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
	 * Este método deshabilita dinámicamente la recolección de alertas en el sistema, incluso si la constante 
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS} 
	 * está establecida en true. De esta forma, permite sobrescribir temporalmente el comportamiento 
	 * predeterminado para detener la recopilación de mensajes de alerta.
	 * 
	 * De esta manera se impide que las alertas registradas mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()} 
	 * se agreguen a la lista centralizada. 
	 * 
	 * Las alertas que ocurran mientras el sistema esté
	 * desactivado no serán almacenadas ni mostradas por los métodos de visualización.
	 *
	 * Para volver a activar dinamicamente la recoleccion de alertas puede usarse 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * 
	 * La lista de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()} 
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 * 
	 * Dependiendo de dicha configuración, se puede reducir el consumo de memoria impidiendo la recoleccion 
	 * y limitando la profundidad de la traza o excluyendo argumentos y objetos
	 * 
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::desactivar_alertas();
	 * ```
	 *
	 * @return void
	 */

	public static function desactivar_alertas()
	{
		Objeto::$activar_rec_alertas = false;
	}

	//************************************************************************************************
	//Interface ErroresYAlerta*********************
	//*********************///////////////////////////////////////////////////////////////////////////

	/**
	 * Genera una representación legible de la firma de una función o método, 
	 * incluyendo parámetros y valores actuales.
	 *
	 * @note funcion auxiliar utilizada por los metodos que imprimen los errores y las alertas
	 * @see Objeto::imprimir_errores()
	 * @see Objeto::imprimir_errores_consola()
	 * @see Objeto::imprimir_alertas()
	 * @see Objeto::imprimir_alertas_consola()
	 * @param array $nivel profundidad de nivel de pila de llamadas (debug_backtrace).
	 * @return string Firma generada de la función o método.
	 */
	private static function obtener_firma_funcion(array $nivel): string
	{
		$clase = $nivel['class'] ?? null;
		$funcion = $nivel['function'] ?? '';
		$obj = $nivel['object'] ?? null;
		$args = $nivel['args'] ?? [];
		$tipo = $nivel['type'] ?? ' ';

		$parametros = [];
		$valores = [];

		try {
			if ($clase) {
				// Compatibilidad PHP <=8.3 y >=8.4
				if (method_exists('\ReflectionMethod', 'createFromMethodName')) {
					$ref = \ReflectionMethod::createFromMethodName($clase . "::" . $funcion);
				} else {
					$ref = new \ReflectionMethod($clase, $funcion);
				}

			} else {
				$ref = new \ReflectionFunction($funcion);
			}
			$i = 0;
			foreach ($ref->getParameters() as $p) {
				$nombre = '$' . $p->getName();
				$valor = array_key_exists($i, $args) ? json_encode($args[$i]) : '—';
				$parametros[] = "$nombre=$valor";
				$i++;
			}
		} catch (\ReflectionException $e) {
			// fallback: si no podemos reflejar, mostramos valores sin nombres
			foreach ($args as $a) {
				$parametros[] = json_encode($a);
			}
		}

		$firma = '';
		if ($clase)
			$firma .= $clase . $tipo;
		$firma .= $funcion . "(" . implode(", ", $parametros) . ")";

		if ($obj) {
			$firma .= " [Objeto: " . get_class($obj) . "]";
		}

		return $firma;
	}

	/**
	 * Activa la recolección de errores y alertas en el sistema (Interfaz ErroresYAlertas).
	 *
	 * Este metodo pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
   	 * Esta función permite habilitar dinamicamente la captura y almacenamiento de errores y de alertas
   	 * dentro del sistema centralizado, independientemente del valor inicial configurado en las constantes
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES} y 
   	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ALERTAS}. 
	 * Permite sobrescribir temporalmente la configuración predeterminada para activar la recopilación
   	 * de mensajes de error y de alerta durante la ejecución. 
   	 * 
   	 * Una vez activada, cualquier error o alerta registrado mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()}  
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()} 
	 * se almacenará y podrá ser consultado posteriormente mediante los métodos de visualización.
	 * 
	 * Para desactivar la recoleccion puede usarse 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()} 
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
   	 * para desactivar solo uno de los dos tipos de recoleccion	 
	 *
	 * Los errores y alertas pueden registrarse usando:
   	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error _error()} 
   	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()} 
   	 * 
	 * Las listas de errores y de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 *
	 * Configuración relacionada:
	 * - Para activar o desactivar la recoleccion de forma predeterminada (tambien puede hacerse dinamicamente con los metodos relacionados de mas abajo)
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS}
	 * - Para determinar cuánta información de la pila de llamadas se incluye junto al alerta registrado. 
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 *     - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 *
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas desactivar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::activar_errores_y_alertas();
	 * ```
	 *
	 * @return void
	 */
	public static function activar_errores_y_alertas()
	{
		Objeto::$activar_rec_errores = true;
		Objeto::$activar_rec_alertas = true;
	}

	/**
	 * Activa la recolección de errores y alertas en el sistema (Interfaz ErroresYAlertas).
	 *
	 * Este metodo pertenece a la interfaz:
	 *  - {@link ./classes/Iteradores-Nucleo-Interfaces-ErroresYAlertas.html Interfaz ErroresYAlertas}
	 * 
 	 * Este método deshabilita dinámicamente la recolección de errores y de alertas en el sistema, 
	 * incluso si la constantes de configuracio
	 * {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ALERTAS Conf::ACTIVAR_ALERTAS} 
	 * y {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES} 
	 * están establecida en true. De esta forma, permite sobrescribir temporalmente el comportamiento 
	 * predeterminado para detener la recopilación de mensajes de error y de alerta.
	 * 
	 * De esta manera se impide que los errores y las alertas registradas mediante 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__errores _errores()} 
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta _alerta()} 
	 * se agreguen a las listas centralizadas. 
	 * 
	 * Los errores y las alertas que ocurran mientras el sistema esté
	 * desactivado no serán almacenadas ni mostradas por los métodos de visualización.
	 *
	 * Para volver a activar dinamicamente la recoleccion de errores y alertas puede usarse 
	 * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()} 
	 * o {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
   	 * para reactivar solo uno de los dos tipos de recoleccion	
	 *  
	 * Las listas de errores y de alertas puede luego visualizarse usando métodos como:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores imprimir_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_errores_consola imprimir_errores_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_errores html_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_errores json_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas imprimir_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_imprimir_alertas_consola imprimir_alertas_consola()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_html_alertas html_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_json_alertas json_alertas()}
	 *
	 * Configuración relacionada:
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ALERTAS}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ACTIVAR_ERRORES Conf::ACTIVAR_ERRORES}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS Conf::ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS}
	 * - {@link ./classes/Iteradores-Configuracion-Conf.html#constant_ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE Conf:: ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE}
	 *
	 * Métodos relacionados:
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores activar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores desactivar_errores()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas activar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas desactivar_alertas()}
	 * - {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas activar_errores_y_alertas()}
	 *
	 * Ejemplo de uso:
	 * ```php
	 * Objeto::activar_errores_y_alertas();
	 * ```
	 *
	 * @return void
	 */
	public static function desactivar_errores_y_alertas()
	{
		Objeto::$activar_rec_errores = false;
		Objeto::$activar_rec_alertas = false;
	}


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