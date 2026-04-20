<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
include_once(".\Nucleo\Objeto.php");
include_once(".\Nodos\Nodo.php");
include_once(".\Configuracion\Configuracion.php");
include_once(".\Controlador\PerdurarSuperestructura\PerdurarSuperestructura.php");


//include_once("../../Nucleo.php");
//include_once("../Nodo.php");
//************************************************//
//*	 Clase: PerdurarSuperestructuraString		 *//
//*												 *//
//*												 *//
//************************************************//
//*CLASE DE PRUEBA
//*Implementando los objetivos pendientes de la clase sugeridos en el manual de la v.1.0 e implementando dos nuevas intefaces para la manipulacion de atributos asignables a los nodos y atributos para cada uno de los enlaces del nodo, como puede ser el 'peso' del enlace.
//*Por ahora se intentara no tocar las interfaces ya implementadas en el pasado para no perder compatibilidad con las clases ya implementadas hasta que se actualizen.
//23/06/2012: Comenzado el proceso de refactorizacion segun las deciones tomadas.
//04/07/2012: Sigo refactorizacion
//09/07/2012: "
//12/07/2012: Fin refactorizacion.
//09/08/2012: V1.6 Solucionados varios bugs
//25/02/2013: V1.7 Refactorizo y modifico:
//27/02/2013:      Sigo trabajo
//05/07/2013:	   Pruebas.
//				   Arreglada funcion tiene_adyacente()	
//07/07/2013: V1.9 Comienzo nueva refactorizacion.
//17/07/2013: V2.1 Interfaz creacion 
//17/07/2013: V2.3 Interfaz de acceso a la superestructura
//18/07/2013: V2.3.1 Interfaz de manipulacion de entorno, ejecutar funcion por cada adyacente.
//18/07/2013: V2.3.3 Superestructura pasara a ser un nodo. se agregara la funcion ejecutar por cada nodo.
//19/07/2013: V2.3.5 Agregado eliminar_enalces_a() de la interfaz de manipulacion de entorno.
//					 Agregado eliminar(nodo) de la interface de manejo de superestructura
//19/07/2013: V2.5 Guardar y cargar superestructura.
//20/07/2013: V2.5.1 Guardar
//20/07/2013: V2.5.3 Cargar
//21/07/2013: V2.5.5 Cambiado metodo Guardar se agrago parametro con nombre. En todos los lugares donde se utiilizaba el id secion como id de la superestructura se cmabia por un nombre especifico. esto significa que la clase ya no se hara responsable del nombre con que se guarda la superestructura.
//					 Agregue hay_nodos() a la clase Nodo.
//					 Se agrega en guardar una clausala para que vea si existen nodos antes.
//28/12/2016: V2.161228 Cambio de nomenclatura, revision general, version estable.
//						En imprimir hago que imprima mayor informacion sobre el nodo
//						Solciono problema en _adyacente_en($un_nodo, $enlace)
//07/01/2017: V2.170107 Se agrego un mensaje de alerta en eliminar_enlace();
//			  V2.170123 Se intenta cambiar adyacente_en(enlace) por adyacente(enlace)
//			  V2.170130 Cambio include once por la nueva version de Objeto
//			  V2.170208 A partir de ahora un nodo espacial con id "compartidos" sera guardado aparte cuando se guarde la superestructura. La intencion es que todos los nodos que cualguen de "compartidos" sean compartidos entre todas las aplicaciones. 
//				CANCELADOS ULTIMOS CAMBIOS
//						Voy a revisar los tiempos de las distintas funciones importantes
//V2.7.170306	Despues de revisar los tiempos de ejecucion se llego a la conclusion de que se debe cambiar la estrategia para eliminar los nodos, ya que con el meodo actual hace falta recorrer toda la superestructura para cada eliminar. Cuando la superestructura crece los tiempos de eliminar se hacen gigantes. Se propone, que la funcion eliminar solo "marque" al nodo para ser eliminado, pero no lo elimine en el momento. Y se implementara otra funcion recolector_de_basura() que recorrera toda la estructura pero eliminando todos los nodos en una sola pasada. Esta funcion debera ser llamada cada sierto tiempo para evitar la acumulacion de nodos inservibles... Luego de esto va a haber que revisar todas las otras clases para ver que sean compatibles con esta nueva forma de eliminar.
//				Elimino la funcion eliminar_enlaces_a().
//V2.7.170306a	Veo que recorrer todos los nodos por mas que se haga una sola vez es una tarea sumamente lenta por lo que descarto la idea del recolector_de_basura. Se piensa en una solcion mas elegante: no es correcto eliminar un nodo desde cualquier adyacente si este tiene esta referenciado por otros enlaces que llegan a el. Por lo cual solo se puede eliminar enlaces... y cuando no hay ningun enlace desde ningun nodo que apunten al que queremos eliminar.. entonces si.. se puede eliminar de la superestructura. La solucion propuesta es la sieguiente.. eliminar_enlace elimina el enlace.. desde ahora eliminar va a intentar eliminar de la superestructura... si el nodo esta referenciado por algun otro enlace en otra parte de la estructura no podra ser eliminado... tiene bastante logica y soluciona todo el problema
//V2.7.170307	Para saber si un nodo esta referenciado a traves de un enlace desde otro nodo se utilizara una variable privada que llevara la cuenta de cuantas veces el nodo ha sido referenciado.. Hay que modificar el constructor... _adyacente() _adyacente_en() eliminar_enlace() _destructor() y eliminar()
//V2.7.170308...??
//V2.7.170630	Modifico eliminar_enlace para que elimine el key del dato que genera errores.
//V2.7.171101	La solucion implementada para eliminar el key del array de adyacentes que representa al enlace elminido en eliminar_enlace hace (nuevamente) que haya que recorrer toda la superestructura.. sino que le encima le hace una copia!! esto sucede porque elminar de la superestructura llama a eliminar_enlace. Como solucion se propone modificar nuevamente eliminar_enlace para que distinga si esta parado en la superestructura, en cuyo caso asignara el valor null y nada mas. Esto puede generar problemas en otros lados.. parches parches sobre parches... ya lo iremos viendo
//V2.7.171101	Actualizo codigo para php7
//V2.7.171107	Cambio eliminar_enlace para que devuelva el nodo eliminado.
//V2.8.0.180425	actualizo la version de objeto
//				areglo bug en tiene_adyacente
/*$iabiertos	debo agregar otro parametro a la funcion por_cada_adyacente_ejecutar, debe ser de forma que no afecte las funciones que ya existen, sino, se debe agregar una nueva funcion que reciba especificamente 2 parametros. esto es necesario porque antes habia pensado que cualquier dato podia ser pasado como parametro en un nodo, a lo sumo, un nodo complejo, pero resulta que ultilice parametro para algo mucho mas interesando, pasar un iteredaro, el problema es que ahora necesito pasar ademas del iterador, datos! bue.. a solucionarlo de alguna manrea...
				hoja de ruta:
					probar modificar funcion
						modificar la funcion
						pruebas, muchas. para cero, un parametro y para dos
					sino, funcion nueva
						pruebas
	funciono a la primera!
	pruebas superadas
*//*
V2.8.0.180425	eliminar_enlaces() que elimina todos los enlaces de un nodo
V2.8.3.180530	adapto PerdurarSuperestructuraString a los nuevos requerimientos de biblioteca
V2.9.0.180803	Inicio refactorizacion BETA
				hoja: 0-4: clase Nodo
					  5-9: clase PerdurarSuperestructuraString	

				Inicio trabajo
				Interfaz de construccion y destruccion de nodos
V2.9.0.180817	Agrego un mecanismo para guardar los nodos que queden sueltos en _adyacente_en. 
				agregado es_nodo_suelto()
V2.9.0.180818	eliminar_nodos_sueltos
				pruebas superadas
V2.9.1.180819	inicio refactorizacion BETA, la hoja de ruta va a ser un abance por interfaz
				ATENCION:la solucion de detectar nodos unicos perdidos para que no ocupen memoria no funciona, porque pueden haber grupos de nodos desconectados de las estructuras principales, pero que no sean detectables como sueltos porque estan interconectados entre ellos. Esa prueba invalida cualquier solucion al estilo "recolector de basura" recorriendo todos los nodos. deberan deshacerse los cambios implentados en 2.9.0.
				La solucion es otra. Debe disponerse de un metodo para guardar siertas partes de la estructura a partir de un enlace. Con ese metodo podran guardarse y recuperarse partes de la estructura.
				Para lograr el objetivo deberan guardarse aparte de la superestructura un nodo que enlace a todos los nodos "especiales" nodos normales cuyos "ids" fueron asignados por el usuario 
V2.9.1.180820	Interfaz de acceso a nodos especiales
V2.9.1.180822	Terminadas interfaces de acceso a superestructura y a nodos especiales
				Comienzo refactorizacion otra vez de la interfaz de creacion y destruccion de nodos
				fin refactorizacion interfaz creacion y destruccion; acceso a superestructura;  acceso a nodos especiales.
V2.9.2.180822	comienzo refactorizacion de la clase PerdurarSuperestructuraString
				voy a separarla en un archivo aparte
		INICIO NUEVO DOCUMENTO CON LA CLASE
				ahora si, comienzo refactorizacion BETA
				pruebas superadas
				ahora carga bien los nodos especiales
				finalizada refactorizacion beta
V2.9.3.210524	Agrego constantes para facilitar el nombre de usuario, contrasena, host, y nombre de la base de datos
V2.9.3.210601	Agrego la sentencia encode_utf8 para codifcicar los datos antes de insertarlos en la tabla. Esto aumenta la compatibilidad con las bases de datos de los hosting (al menos 000webhosting) 
V2.9.3.210603	//PRUEBAS EN 000WEBHOST//
//			  Decision: a partir de este punto las bases de datos para la superestructura va a ser la misma q para los hilos. Se llamara HyS
V2.9.3.210604	Desicion: dada la complicada tarea de exportar la base de datos e inportarla manteniendo la integridad de los caracteres (despues de varios dias de pruebas y agotando todas las opciones investigadas) decido reemplazar el tipo varchar de la comlumna dato de la tabla nodo por blob. Esta opcion elimina el problema y permite guardar otros tipos de datos no solo texto, cosa q mas adelante talvez sirva.
				//PRUEBAS EN 000WEBHOST// Superadas
************************************************************************* 
V2.9.4.250826 Quito los numeros de las versiones. Simplifico las query que crean las tablas
V2.9.5.250829 Reemplazo las variables "local" y las de acceso a la BD por las constantes definidas en el archivo de configuracion
V2.9.6.250901 Agrego eliminar_sql()
V3.3.1.251023 ahora esta clase es parte de un patron de diseño para lograr diferentes implementaciones 
				de la interfaz PerdurarSuperestructura


*/

/**
 * Clase PerdurarSuperestructuraStringSQL
 * 
 * @version 2.9.6 (Última revisión: 01/09/2025)
 * @author ...
 * 
 * @extends Objeto
 * 
 * @description
 * Clase responsable de la persistencia de la superestructura en una base de datos SQL.
 * Se encarga de crear la conexión, la base de datos y las tablas necesarias
 * para almacenar los nodos y enlaces que conforman la estructura.
 * 
 * @history
 * - 23/06/2012: Comienza proceso de refactorización según decisiones de la v1.0  
 * - 19/07/2013: Se agregan métodos para eliminar enlaces y nodos.  
 * - 20/07/2013: Implementación de guardado y carga de superestructuras.  
 * - 28/12/2016: Cambio de nomenclatura y versión estable.  
 * - 07/01/2017–03/06/2017: Mejoras en los métodos de eliminación y corrección de errores.  
 * - 25/08/2025: Simplificación de queries y unificación de la configuración SQL.  
 * - 01/09/2025: Se añade método `eliminar_sql()`.  
 * 
 * @notes
 * Esta clase ha evolucionado significativamente desde las versiones iniciales.
 * Se mantuvo compatibilidad con versiones anteriores hasta completar la transición
 * a PHP 7+ y codificación UTF-8 completa en base de datos (utf8mb4).
 */
class PerdurarSuperestructuraStringSQL extends Objeto implements PerdurarSuperestructura
{

    /**
     * @var string Token de seguridad recibido de la clase Nodo.
     */
    protected static string $token = '';

    /**
     * Recibe el token de seguridad desde la clase Controlador
     *
     * @param string $token Token de seguridad proporcionado por Nodo.
     * @return void
     */
    public static function recibir_token(string $token): void {
        static::$token = $token;
    }
    /**
     * Crea y devuelve una conexión MySQLi válida para la superestructura.
     * 
     * @usecase Establecer conexión con el servidor y preparar la base de datos.
     * 
     * @preconditions
     * - Las constantes de configuración (host, usuario, contraseña y base de datos)
     *   deben estar definidas en la clase Conf.
     * 
     * @return \mysqli|null Retorna el objeto de conexión SQL si tuvo éxito, o null en caso de error.
     * 
     * @postconditions
     * - La base de datos y las tablas necesarias quedan creadas y seleccionadas.
     * 
     * @notes
     * Si la conexión es local, primero crea la base de datos.
     * Si es remota, asume que ya existe.
     * Fuerza la codificación a utf8mb4 para compatibilidad total.
     */
	static private function crear_conexion_sql()
	{
		if (Conf::LOCAL) {
			if ($sql = new \mysqli(Conf::SUPERESTRUCTURA_HOST_SQL, Conf::SUPERESTRUCTURA_USUARIO_SQL, Conf::SUPERESTRUCTURA_CONTRASENA_SQL)) {
				self::crear_base_de_datos_sql($sql);
				$sql->select_db(Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL);
				self::crear_tablas_sql($sql);
				//return $sql;
			} else {
				self::_error("no se pudo conectar a la base de datos");
				return null;
			}
		} else {
			if ($sql = new \mysqli(Conf::SUPERESTRUCTURA_HOST_SQL, Conf::SUPERESTRUCTURA_USUARIO_SQL, Conf::SUPERESTRUCTURA_CONTRASENA_SQL, Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL)) {
				//self::crear_base_de_datos_sql($sql)
				self::crear_tablas_sql($sql);
				//return $sql;
			} else {
				self::_error("no se pudo conectar a la base de datos");
				return null;
			}
		}
		$charset = $sql->character_set_name();
		if ($charset != "utf8mb4") {
			$sql->set_charset("utf8mb4");
		}
		//echo $charset;
		return $sql;
	}

    /**
     * Crea la base de datos si no existe.
     * 
     * @usecase Inicializa la base de datos definida en las constantes de configuración.
     * 
     * @preconditions Debe existir una conexión válida a MySQL.
     * 
     * @param \mysqli $sql Conexión activa al servidor MySQL.
     * 
     * @return bool `true` si la base de datos fue creada o ya existía, `false` en caso de error.
     * 
     * @postconditions La base de datos queda disponible para seleccionar y utilizar.
     */
	static private function crear_base_de_datos_sql($sql)
	{
		//echo "KKKKKKKKKKKKKKKKKKLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLKKKgKKKKKKKKKKKLLLLLLLLLLLLL";
		if ($sql->query("CREATE DATABASE IF NOT EXISTS " . Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL)) {
			return true;
		} else {
			self::_error("no se pudo crear la base de datos");
			return false;
		}
	}

    /**
     * Crea las tablas necesarias para la persistencia de la superestructura.
     * 
     * @usecase Genera las tablas `nodo` y `adyacente` si aún no existen.
     * 
     * @preconditions Debe existir una base de datos seleccionada en la conexión SQL.
     * 
     * @param \mysqli $sql Conexión activa a la base de datos.
     * 
     * @return bool `true` si las tablas fueron creadas correctamente, `false` en caso de error.
     * 
     * @postconditions Asegura la existencia de las tablas requeridas para almacenar nodos y enlaces.
     */
	static private function crear_tablas_sql($sql)
	{
		/*if (!mysql_select_db("superestructura")){
			PerdurarSuperestructuraString::_error("no se pudo seleccuinar la base de datos en crearTablas");
		}*/
		if (
			!$sql->query("CREATE TABLE IF NOT EXISTS nodo (
    						idsuperestructura VARCHAR(50) NOT NULL,
    						idnodo 			VARCHAR(50) NOT NULL,
    						dato 			BLOB,
    						PRIMARY KEY (idsuperestructura, idnodo)
						) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
		) {
			self::_error("no se pudo crear la tabla nodo");
			return false;
		}
		if (
			!$sql->query("CREATE TABLE IF NOT EXISTS adyacente (
    						idsuperestructura VARCHAR(50) NOT NULL,
    						idnodo           VARCHAR(50) NOT NULL,
   							enlace           VARCHAR(100) NOT NULL,
    						idadyacente      VARCHAR(50) NOT NULL,
    						PRIMARY KEY (idsuperestructura, idnodo, enlace, idadyacente)
						) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;")
		) {
			self::_error("no se pudo crear la tabla adyacente");
			return false;
		}
		return true;
	}
  	/**
     * Crea la consulta SQL para insertar nodos.
     * 
     * @usecase Construye consulta de inserción para nodos en la base de datos.
     * 
     * @param string $nombre Identificador de la superestructura en la base de datos.
     * 
     * @return string Consulta SQL lista para ejecutar.
     * 
     * @notes Genera una consulta INSERT con todos los nodos de la superestructura actual.
     */
	static private function crear_consulta_insertar_sql($nombre)
	{

		//$nombre=session_id()."_".$GLOBALS['num_hilo'];
		//recupero datos
		//echo "recuperodatos".self::$token;
		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
			return $nodo->dato(); }, null);

		//creao consulta
		$consulta = "INSERT INTO nodo (idsuperestructura, idnodo, dato) values";
		$separador = " ";
		$primero = true;
		//if (is_array($datos)){echo "si es";};

		foreach ($datos as $id => $dato) {
			if (!is_string($dato) and !is_null($dato) and !is_int($dato)) {
				$dato = null;
			}
			$consulta = $consulta . $separador . "('" . $nombre . "','" . $id . "','" ./*utf8_encode(*/ $dato/*)*/ . "')";
			if ($primero) {
				$primero = false;
				$separador = ", ";
			}
		}
		$consulta = $consulta . ";";
		//echo $consulta;
		return $consulta;
	}
    /**
     * Crea la consulta SQL para insertar enlaces.
     * 
     * @usecase Construye consulta de inserción para relaciones adyacentes en la base de datos.
     * 
     * @param string $nombre Identificador de la superestructura en la base de datos.
     * 
     * @return string Consulta SQL lista para ejecutar.
     * 
     * @notes Genera una consulta INSERT con todas las relaciones adyacentes de la superestructura.
     */
	static private function crear_consulta_insertar2_sql($nombre)
	{

		//$nombre=session_id()."_".$GLOBALS['num_hilo'];
		//recupero datos
		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
			return $nodo->por_cada_adyacente_ejecutar(function ($nodo) {
				return $nodo->id(); }); });

		//creao consulta
		$consulta = 'INSERT INTO adyacente (idsuperestructura, idnodo, enlace, idadyacente) values';
		$separador = " ";
		$primero = true;
		//if (is_array($datos)){echo "si es";};

		foreach ($datos as $idnodo => $arreglo) {
			if (is_array($arreglo)) {
				foreach ($arreglo as $enlace => $idadyacente) {
					$consulta = $consulta . $separador . "('" . $nombre . "','" . $idnodo . "','" . $enlace . "','" . $idadyacente . "')";
					if ($primero) {
						$primero = false;
						$separador = ", ";
					}
				}
			}
		}
		$consulta = $consulta . ';';
		//echo $consulta;
		return $consulta;
	}
    /**
     * Guarda la superestructura en la base de datos.
     * 
	 * @interface PerdurarSuperestructura
	 * 
     * @usecase Persistir toda la superestructura en SQL.
     * 
     * @preconditions Debe existir al menos un nodo en la superestructura.
     * 
     * @param string $nombre Identificador único para guardar la superestructura.
     * 
     * @return bool `true` si la operación fue exitosa, `false` en caso contrario.
     * 
     * @notes 
     * - Elimina cualquier versión previa con el mismo nombre
     * - Ejecuta inserciones tanto para nodos como para enlaces
     */
	static public function guardar($nombre)
	{
		if (!Nodo::hay_nodos_en_superestructura()) {
			self::_error("error en guardar, no existe ningun nodo en la superestructura");
			return false;
		}
		$sql = self::crear_conexion_sql();
		/*PerdurarSuperestructuraString::crear_base_de_datos_sql($sql);
		$sql->select_db(Conf::NOMBRE_BD_SQL_SUPERESTRUCTURA);
		PerdurarSuperestructuraString::crear_tablas_sql($sql);*/
		$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';");
		$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");
		$sql->query(self::crear_consulta_insertar_sql($nombre));
		$sql->query(self::crear_consulta_insertar2_sql($nombre));
		$sql->close();
		//return session_id()."_".$GLOBALS['num_hilo'];
		return true;
	}
    /**
     * Elimina una superestructura de la base de datos.
     * 
	 * @interface PerdurarSuperestructura
	 * 
     * @usecase Remover una superestructura persistida por nombre.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a eliminar.
     * 
     * @return bool|null `true` si fue eliminada, `false` si no existía, `null` en caso de error.
     * 
     * @postconditions La superestructura con ese nombre queda eliminada de la BD.
     */
	static public function eliminar($nombre): bool|null
	{
		if (!is_string($nombre)) {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre), el identificador pasado como parametro no es un string");
			return null;
		}
		if (!$sql = self::crear_conexion_sql()) {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no se pudo crear la conexion");
			return null;
		}
		;
		$sql = self::crear_conexion_sql();
		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) error intentado ver si la superestructura existe");
			$sql->close();
			return null;
		}
		//echo "mamasa";
		$cant = $rcontar->fetch_assoc()['COUNT(*)'];
		$r=false;
		if ($cant > 0) {
			//echo "mamerta";
			$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';");
			$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");
			$r=true;
		} else {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no existe superestructura con ese nombre");			
		}
		$sql->close();
		//return session_id()."_".$GLOBALS['num_hilo'];
		return $r;
	}
    /**
     * Carga una superestructura desde la base de datos.
     * 
	 * @interface PerdurarSuperestructura
	 * 
     * @usecase Recuperar una superestructura persistida por nombre.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a cargar.
     * 
     * @return bool|null `true` si la carga fue exitosa, `false` si no existe, `null` en caso de error.
     * 
     * @postconditions La superestructura queda cargada en memoria.
     * 
     * @notes Maneja equivalencias de IDs y reconstruye las relaciones entre nodos.
     */
	static public function cargar($nombre): bool|null
	{
		if (!is_string($nombre)) {
			self::_error("PerdurarSuperestructuraString::cargar(nombre), el identificador pasado como parametro no es un string");
			return false;
		}
		if (!$sql = self::crear_conexion_sql()) {
			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo crear la conexion");
			return null;
		}
		;
		/*PerdurarSuperestructuraString::crear_base_de_datos_sql($sql);
		$sql->select_db(Conf::NOMBRE_BD_SQL_SUPERESTRUCTURA);
		PerdurarSuperestructuraString::crear_tablas_sql($sql);*/
		//echo "SELECT * FROM `nodo` WHERE `idsuperestructura`='".$identificador."';";

		if (!$nodos = $sql->query("SELECT * FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo cargar, no cargo nada");
			return null;
		}
		$nodo = $nodos->fetch_assoc();
		if (!$nodo) {
			self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");
			return false;
		}
		Nodo::vaciar_superestructura(static::$token);
		$equivalencias = array();
		while ($nodo != null) {
			$id = $nodo["idnodo"];
			if (self::es_id_especial($id)) {
				//echo "ppp".$id."klj";
				if (!$naux = Nodo::nodo_por_id($id)) {
					Nodo::crear_con_dato_e_id(/*utf8_decode(*/ $nodo["dato"]/*)*/ , $id);
				} else {
					$naux->_dato(/*utf8_decode(*/ $nodo["dato"]/*)*/);
				}
			} else {
				$idnuevo = Nodo::crear_con_dato(/*utf8_decode(*/ $nodo["dato"]/*)*/)->id();
				$equivalencias[$id] = $idnuevo;
			}
			$nodo = $nodos->fetch_assoc();
		}

		$adyacentes = $sql->query("SELECT * FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");

		$adyacente = $adyacentes->fetch_assoc();
		while ($adyacente != null) {
			$idnod = $adyacente["idnodo"];
			if (!self::es_id_especial($idnod)) {
				$idnod = $equivalencias[$adyacente["idnodo"]];
			}
			$nodo = Nodo::nodo_por_id($idnod);
			$idady = $adyacente["idadyacente"];
			if (!self::es_id_especial($idady)) {
				$idady = $equivalencias[$adyacente["idadyacente"]];
			}
			$nodoady = Nodo::nodo_por_id($idady);
			$nodo->_adyacente_en($nodoady, $adyacente["enlace"]);
			$adyacente = $adyacentes->fetch_assoc();
		}
		$sql->close();
		return true;
	}
    /**
     * Verifica la existencia de una superestructura en la base de datos.
     * 
	 * @interface PerdurarSuperestructura
	 * 
     * @usecase Consultar si existe una superestructura persistida por nombre.
     * 
     * @param string $nombre Identificador de la superestructura a verificar.
     * 
     * @return bool|null `true` si existe, `false` si no existe, `null` en caso de error.
     */
	static public function existe($nombre): bool|null
	{
		//echo "HHHHHHHHHH5HHHH";
		if (!is_string($nombre)) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre), el identificador pasado como parametro no es un string");
			return null;
		}
		if (!$sql = self::crear_conexion_sql()) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo crear la conexion");
			return null;
		}
		;
		/*PerdurarSuperestructuraString::crear_base_de_datos_sql($sql);
		$sql->select_db(Conf::NOMBRE_BD_SQL_SUPERESTRUCTURA);
		PerdurarSuperestructuraString::crear_tablas_sql($sql);*/
		//echo "SELECT * FROM `nodo` WHERE `idsuperestructura`='".$identificador."';";

		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo contar");
			return null;
		}
		$cant = $rcontar->fetch_assoc()['COUNT(*)'];
		$sql->close();
		return $cant > 0;
	}

}
/*
$nodo=Nodo::crear_con_id("lola");
$nodo->_adyacente_en($a=Nodo::crear_con_id("a"),"aca");
$nodo->eliminar_enlace("aca");
$nodo->_adyacente_en(Nodo::crear_con_dato("BBBB"),"aca");
$nodo->_adyacente_en(Nodo::crear_con_id("c"),"aca");
Nodo::crear();
Nodo::eliminar($a);

Nodo::guardar_superestructura("BETA", "sQl");
Nodo::cargar_superestructura("BETA", "sQl");
Nodo::eliminar(Nodo::nodo_por_id("1"));

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


/*	//lo convierto a nodo
	$n1=Nodo::crear_con_dato(1);
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
	$n0->por_cada_adyacente_ejecutar($agregar_hijo,"tata", "tita");*/
//Nodo::imprimir_superestructura();
//Nodo::imprimir_errores();
?>