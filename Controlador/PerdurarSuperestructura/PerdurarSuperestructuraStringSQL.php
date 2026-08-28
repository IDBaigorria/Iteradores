<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
include_once("./Nucleo/Objeto.php");
include_once("./Nodos/NodoElectrico.php");
include_once("./Configuracion/Configuracion.php");
include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");

/**
 * Clase PerdurarSuperestructuraStringSQL
 * 
 * @version 1.5i.4 
 *
 * @author Ignacio David Baigorria
 *
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
		//echo "</br>host: ".Conf::SUPERESTRUCTURA_HOST_SQL;
        //echo "</br>usuario: ".Conf::SUPERESTRUCTURA_USUARIO_SQL;
        //echo "</br>contra: ".Conf::SUPERESTRUCTURA_CONTRASENA_SQL;
        //echo "</br>nombreBD: ". Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL;
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
	static private function crear_consulta_insertar_sql($sql, $nombre)
	{
		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
			return $nodo->dato();
		}, null);

		$consulta = "INSERT INTO nodo (idsuperestructura, idnodo, dato) values";
		$separador = " ";
		$primero = true;

		foreach ($datos as $id => $dato) {
			if (!is_string($dato) && !is_null($dato) && !is_int($dato)) {
				$dato = null;
			}
			$id_escapado = mysqli_real_escape_string($sql, (string)$id);
			$dato_escapado = is_null($dato) ? '' : mysqli_real_escape_string($sql, (string)$dato);
			$consulta .= $separador . "('" . $nombre . "','" . $id_escapado . "','" . $dato_escapado . "')";
			if ($primero) {
				$primero = false;
				$separador = ", ";
			}
		}
		$consulta .= ";";
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
	static private function crear_consulta_insertar2_sql($sql, $nombre)
	{
		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
			return $nodo->por_cada_adyacente_ejecutar(function ($nodo) {
				return $nodo->id();
			});
		});

		$consulta = 'INSERT INTO adyacente (idsuperestructura, idnodo, enlace, idadyacente) values';
		$separador = " ";
		$primero = true;

		foreach ($datos as $idnodo => $arreglo) {
			if (is_array($arreglo)) {
				foreach ($arreglo as $enlace => $idadyacente) {
					$idnodo_escapado = mysqli_real_escape_string($sql, (string)$idnodo);
					$enlace_escapado = mysqli_real_escape_string($sql, (string)$enlace);
					$idadyacente_escapado = mysqli_real_escape_string($sql, (string)$idadyacente);
					$consulta .= $separador . "('" . $nombre . "','" . $idnodo_escapado . "','" . $enlace_escapado . "','" . $idadyacente_escapado . "')";
					if ($primero) {
						$primero = false;
						$separador = ", ";
					}
				}
			}
		}
		$consulta .= ';';
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
		$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';");
		$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");
		$sql->query(self::crear_consulta_insertar_sql($sql, $nombre));
		$sql->query(self::crear_consulta_insertar2_sql($sql, $nombre));
		$sql->close();
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
		$sql = self::crear_conexion_sql();
		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) error intentado ver si la superestructura existe");
			$sql->close();
			return null;
		}
		$cant = $rcontar->fetch_assoc()['COUNT(*)'];
		$r = false;
		if ($cant > 0) {
			$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';");
			$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");
			$r = true;
		} else {
			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no existe superestructura con ese nombre");			
		}
		$sql->close();
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

		if (!$nodos = $sql->query("SELECT * FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo cargar, no cargo nada");
			return null;
		}
		$nodo = $nodos->fetch_assoc();
		if (!$nodo) {
			self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");
			return false;
		}

		// La superestructura ya fue vaciada por el Controlador
		$equivalencias = array();
		while ($nodo != null) {
			$id = $nodo["idnodo"];
			if (Nodo::es_id_especial($id)) {
				if (!$naux = Nodo::nodo_por_id($id)) {
					Nodo::crear_con_dato_e_id($nodo["dato"], $id);
				} else {
					$naux->_dato($nodo["dato"]);
				}
			} else {
				$idnuevo = Nodo::crear_con_dato($nodo["dato"])->id();
				$equivalencias[$id] = $idnuevo;
			}
			$nodo = $nodos->fetch_assoc();
		}

		$adyacentes = $sql->query("SELECT * FROM `adyacente` WHERE `idsuperestructura`='" . $nombre . "';");

		$adyacente = $adyacentes->fetch_assoc();
		while ($adyacente != null) {
			$idnod = $adyacente["idnodo"];
			if (!Nodo::es_id_especial($idnod)) {
				if (!isset($equivalencias[$idnod])) {
					self::_error("No se encontró equivalencia para idnodo=$idnod");
					$adyacente = $adyacentes->fetch_assoc();
					continue;
				}
				$idnod = $equivalencias[$idnod];
			}

			$nodo = Nodo::nodo_por_id($idnod);

			$idady = $adyacente["idadyacente"];
			if (!Nodo::es_id_especial($idady)) {
				if (!isset($equivalencias[$idady])) {
					self::_error("No se encontró equivalencia para idadyacente=$idady");
					$adyacente = $adyacentes->fetch_assoc();
					continue;
				}
				$idady = $equivalencias[$idady];
			}

			$nodoady = Nodo::nodo_por_id($idady);

			if (!$nodo || !$nodoady) {
				self::_error("No se pudo reconstruir el enlace: idnodo=$idnod, idadyacente=$idady, enlace={$adyacente['enlace']}");
				$adyacente = $adyacentes->fetch_assoc();
				continue;
			}

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
		if (!is_string($nombre)) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre), el identificador pasado como parametro no es un string");
			return null;
		}
		if (!$sql = self::crear_conexion_sql()) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo crear la conexion");
			return null;
		}

		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`='" . $nombre . "';")) {
			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo contar");
			return null;
		}
		$cant = $rcontar->fetch_assoc()['COUNT(*)'];
		$sql->close();
		return $cant > 0;
	}
}
?>