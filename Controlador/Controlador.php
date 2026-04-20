<?php
namespace Iteradores\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringJSON;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringXML;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL;
require_once(".\configuracion\Configuracion.php");
include_once(".\Nucleo\Objeto.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructura.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringJSON.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringXML.php");
require_once(".\Nodos\NodoElectrico.php");
/**
 * Clase Controlador
 * 
 * Coordina el acceso seguro a las distintas implementaciones de persistencia
 * (SQL, JSON, texto, etc.) que implementan la interfaz PerdurarSuperestructura.
 * 
 * Gestiona el token de seguridad otorgado por la clase Nodo y lo distribuye
 * a cada clase de persistencia registrada, garantizando que solo las clases
 * autorizadas puedan ejecutar operaciones sobre la superestructura.
 *
 * @implements PerdurarSuperestructura
 * @since V3.4.0
 */
class Controlador extends Objeto implements PerdurarSuperestructura {

    /** 
     * @var string Método de persistencia activo por defecto 
     */
    protected static string $metodo = Conf::SUPERESTRUCTURA_METODO_PERDURAR;

    /**
     * @var array<string, string> Mapa de clases de persistencia disponibles.
     * La clave es el identificador del método (por ejemplo: 'sql', 'json', etc.)
     * y el valor es el nombre completo de la clase.
     */
    protected static array $implementaciones = [];

    /**
     * @var ?string Nombre de la clase de persistencia actualmente activa.
     */
    protected static ?string $claseActual = null;

    /**
     * @var string Token de seguridad recibido de la clase Nodo.
     */
    protected static string $token = '';

    /**
     * Registra una clase de persistencia disponible para el sistema.
     *
     * @param string $nombre Identificador del método ('sql', 'json', 'texto', etc.)
     * @param string $clase  Nombre completo de la clase de implementación.
     * @return void
     */
    public static function registrar_implementacion(string $nombre, string $clase): void {
       // echo "coasee ".$clase;
        echo "IIII".static::$token;
        static::$implementaciones[strtoupper($nombre)] = $clase;
        // Si ya existe el token, lo transmite a la clase registrada
        if (static::$token && class_exists($clase) && method_exists($clase, 'recibir_token')) {
          // echo "AAAAAAAAAAAAAAAA ";
            $clase::recibir_token(static::$token, "por_cada_nodo_ejecutar");
        }
    }

    /**
     * Establece qué método de persistencia será el actual.
     *
     * @param string $nuevo_metodo Identificador de la implementación ('sql', 'json', 'texto', etc.)
     * @return bool Devuelve `true` si el método fue reconocido y configurado correctamente.
     */
    public static function establecer_metodo(string $nuevo_metodo): bool {
        $nuevo_metodo=strtoupper($nuevo_metodo);
        if (isset(static::$implementaciones[$nuevo_metodo])) {
            static::$metodo = $nuevo_metodo;
            static::$claseActual = static::$implementaciones[$nuevo_metodo];
            return true;
        }
        static::_alerta("Método de persistencia '$nuevo_metodo' no reconocido");
        return false;
    }

    /**
     * Recibe el token de seguridad desde la clase Nodo y lo distribuye
     * a todas las implementaciones de persistencia registradas.
     *
     * @param string $token Token de seguridad proporcionado por Nodo.
     * @return void
     */
    public static function recibir_token(string $token): void {
        static::$token = $token;
        foreach (static::$implementaciones as $clase) {
            if (class_exists($clase) && method_exists($clase, 'recibir_token')) {
                $clase::recibir_token($token);
            }
        }
    }

    /**
     * Ejecuta una operación delegada a la clase de persistencia activa.
     *
     * @param string $funcion Nombre del método a ejecutar.
     * @param mixed $nombre   Parámetro principal de la operación.
     * @return mixed|null Devuelve el resultado de la operación o `null` si no fue posible.
     */
    protected static function delegar(string $funcion, $nombre): mixed {
        if (!is_string($nombre)){
			static::_error("el nombre no es un string");
			return null;
		}
       // echo "claseActual".static::$claseActual;
        $clase = static::$claseActual;
        if (!static::$claseActual){
            static::_alerta("salio por aca.");
            return null;
        }elseif(!class_exists($clase)) {
            static::_alerta("Clase ".static::$claseActual." de persistencia no disponible para el método actual.");
            return null;
        }
       

        if (!method_exists($clase, $funcion)) {
            static::_alerta("El método '$funcion' no existe en la clase '$clase'.");
            return null;
        }

        return $clase::$funcion($nombre);
    }

    // ======= Métodos públicos de operación =======

    /** @return bool */
    public static function guardar($nombre): bool {
       // echo "nombre: ".$nombre;
        return (bool) static::delegar('guardar', $nombre);
    }

    /** @return bool */
    public static function cargar($nombre): bool {
        return (bool) static::delegar('cargar', $nombre);
    }

    /** @return bool */
    public static function eliminar($nombre): bool {
        return (bool) static::delegar('eliminar', $nombre);
    }

    /** @return bool */
    public static function existe($nombre): bool {
        return (bool) static::delegar('existe', $nombre);
    }

    /** @return bool */
    public static function imprimir($nombre): bool {
        return (bool) static::delegar('imprimir', $nombre);
    }
    /**
     * Indica si el controlador ya ha sido inicializado.
     *
     * Esta bandera interna evita que el proceso de inicialización se
     * ejecute más de una vez. Si es `true`, significa que las clases
     * principales (como `Nodo` y las implementaciones de
     * `PerdurarSuperestructura`) ya fueron registradas correctamente.
     *
     * @var bool
     * @since V3.3.0
     */
    private static $inicializo=false;
     /**
     * Inicializa el controlador principal del sistema.
     *
     * Este método registra las clases necesarias para coordinar la
     * comunicación entre los distintos componentes:
     * 
     * - Asocia la clase `Nodo` con el `Controlador`.
     * - Registra las implementaciones concretas de la interfaz
     *   `PerdurarSuperestructura` (por ejemplo, `SQL`, `JSON`, etc.).
     *
     * La inicialización solo se ejecuta una vez gracias al uso de la
     * variable interna `$inicializo`. En llamadas posteriores, el método
     * no realiza ninguna acción.
     *
     * @return void
     * @since V3.3.0
     */
    public static function inicializar(){
        if (!static::$inicializo){
           echo "MAMAI!";
            Nodo::registrar_controlador("Iteradores\Controlador\Controlador");
            Controlador::registrar_implementacion("SQL", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL");
            Controlador::registrar_implementacion("JSON", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringJSON");
            Controlador::registrar_implementacion("XML", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringXML");
            Controlador::registrar_implementacion("ESQL", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL");
            Controlador::establecer_metodo("ESQL");
            NodoElectrico::establecer_fase(self::$token, "a");
            Controlador::$inicializo=true;
            
        }
    }

    /**
     * Auxiliar para hacer pruebas
     * @return void
     */
    public static function establecer_fase($fase){
        NodoElectrico::establecer_fase(self::$token,$fase);
    }
}

Controlador::inicializar();
