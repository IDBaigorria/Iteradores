<?php
namespace Iteradores\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Controlador\interfaces\Dominios;
use Iteradores\Controlador\interfaces\VectorGravitacional;
use Iteradores\Controlador\interfaces\Motor;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringJSON;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringXML;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL;
use iteradores\Nodos\NodoNumerico;
use Iteradores\Controlador\interfaces\Comandos;
use Iteradores\Comandos\Comando;
use Iteradores\Controlador\interfaces\Comunicadores;
use Iteradores\Comunicadores\Comunicador;
use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Tiempo\RelojAstronomico;
use Iteradores\Controlador\ProcesadorDeDominio;
use Iteradores\Controlador\Senal;
use Iteradores\Controlador\Talamo;

require_once("PerdurarSuperestructura\PerdurarSuperestructura.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringJSON.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringXML.php");
require_once("interfaces\Comandos.php");
require_once("interfaces\Comunicadores.php");
require_once("interfaces\VectorGravitacional.php");
require_once("interfaces\Motor.php");
require_once("interfaces\Dominios.php");

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
 * @author Ignacio David Baigorria
 *
 * @implements PerdurarSuperestructura
 * @implements Comandos
 * @implements Comunicadores
 * @implements VectorGravitacional
 * @implements Dominios
 * @since V1.2.0
 */
class Controlador extends Objeto implements PerdurarSuperestructura, Comandos, Comunicadores, VectorGravitacional, Motor, Dominios {

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
       // echo "IIII".static::$token;
        static::$implementaciones[strtoupper($nombre)] = $clase;
        // Si ya existe el token, lo transmite a la clase registrada
        if (static::$token && class_exists($clase) && method_exists($clase, 'recibir_token')) {
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

    /**
     * Imprime todos los nodos de la superestructura en el formato adecuado
     * según el entorno configurado (HTML o consola).
     *
     * Delega en {@link Nodo::imprimir()} (o el método correspondiente de
     * cada nodo) para la representación individual. La iteración se realiza a
     * través del método protegido {@link Nodo::por_cada_nodo_ejecutar()}, usando el
     * token interno que {@link Controlador} recibió durante la inicialización.
     *
     * Si la superestructura está vacía, muestra un mensaje informativo
     * en lugar de una alerta.
     *
     * @return bool `true` si se ejecutó sin errores, `false` en caso de problema.
     *
     * @since 1.3.0 Unifica imprimir_superestructura e imprimir_superestructura2.
     * @version 1.3.2 Añadido mensaje informativo cuando la superestructura está vacía.
     *
     * @see Nodo::imprimir()
     * @see Configuracion.Entorno
     */
    public static function imprimir_superestructura(): bool
    {
        $encabezado = "===== SUPERESTRUCTURA =====";
        $colores = Conf::NODOS_COLORES;

        // Modo consola: aplicar color ANSI si es posible
        if (Entorno::es_consola()) {
            $color = Entorno::color_ansi($colores['ansi_texto'] ?? '34');
            $reset = $color ? Entorno::color_ansi('0') : '';
            echo $color . $encabezado . "\n" . $reset;
        } else {
            // Modo HTML: usar estilos definidos en Conf
            $fondo = htmlspecialchars($colores['fondo'] ?? '#eef6ff');
            $texto = htmlspecialchars($colores['texto'] ?? '#003366');
            $borde = htmlspecialchars($colores['borde'] ?? '#0066cc');
            echo "<div style='background:{$fondo}; color:{$texto}; padding:1em; margin:1em 0; border:1px solid {$borde}; font-family:monospace; white-space:pre-wrap;'>";
            echo "<h3>{$encabezado}</h3>";
        }

        // Verificar si hay nodos
        if (!Nodo::hay_nodos_en_superestructura()) {
            $mensaje = "No hay nodos en la superestructura.";
            if (Entorno::es_consola()) {
                echo $mensaje . "\n";
            } else {
                echo "<p>{$mensaje}</p>";
                echo "</div>"; // cerrar el contenedor HTML
            }
            return false; // No es un error, solo informativo
        }

        // Iterar sobre los nodos
        $funcion = function($nodo) {
            $nodo->imprimir();
        };
        Nodo::por_cada_nodo_ejecutar(self::$token, $funcion, null);

        // En HTML, cerrar el contenedor después de la lista de nodos
        if (!Entorno::es_consola()) {
            echo "</div>";
        }

        return true;
    }

    // ──────────────────────────────────────────────────────────
    // MÉTODO PARA PRUEBAS: ejecutarPrueba
    // ──────────────────────────────────────────────────────────

    /**
     * Ejecuta una función de prueba inyectando el token de seguridad.
     *
     * Este método está diseñado exclusivamente para entornos de desarrollo y pruebas.
     * Permite que código externo (como suites de prueba) pueda invocar operaciones
     * que requieren el token de seguridad sin necesidad de conocerlo.
     *
     * El token se pasa como único argumento a la función callback, la cual puede
     * usarlo para llamar a métodos protegidos como NodoElectrico::_fase()
     * o NodoElectrico::por_cada_nodo_ejecutar().
     *
     * ⚠️ **ADVERTENCIA**: Este método no debe estar disponible en producción.
     *
     * @param callable $callback Función que recibirá el token como único parámetro.
     * @return void
     * @since 0.0.1
     * @static
     */
    public static function ejecutar_prueba(callable $callback)
    {
        if (!Entorno::permite_pruebas()) {
            self::_error('ejecutar_prueba() no está disponible en entorno de producción');
            return;
        }
        $callback(self::$token);
    }

 /*.................*/

    // ══════════════════════════════════════════════════════
    // INICIALIZACION
    // ══════════════════════════════════════════════════════

    /** @var bool Indica si el controlador ya ha sido inicializado. */
    private static $inicializo = false;

    /**
     * Inicializa el controlador principal del sistema.
     *
     * Procesa los comandos y comunicadores pendientes desde {@link RegistroGlobal}
     * y registra los comandos de comunicación.
     *
     * @return void
     * @since V3.3.0
     * @version 1.4.6 (inicialización del tálamo y procesadores)
     */
    public static function inicializar(): void
    {
        if (!static::$inicializo) {
            // ─── Registro del controlador ante Nodo ────────────
            Nodo::registrar_controlador("Iteradores\Controlador\Controlador");

            // ─── Implementaciones de persistencia ──────────────
            Controlador::registrar_implementacion("SQL", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL");
            Controlador::registrar_implementacion("JSON", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringJSON");
            Controlador::registrar_implementacion("XML", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringXML");
            Controlador::registrar_implementacion("ESQL", "Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL");
            Controlador::establecer_metodo("ESQL");

            // ─── Inicializar cache de primos ─────────────────────
            NodoNumerico::inicializar_cache_primos();

            // ─── Inicializar Tálamo (singleton) y precargar los 256 bytes ──
           /* $talamo = Talamo::obtener();
            $talamo::recibir_token(self::$token);
            $talamo->precargar();
            self::$procesadores['Talamo:entrada'] = $talamo;*/

            // ─── Procesar comandos pendientes ──────────────────
            foreach (RegistroGlobal::$comandos_pendientes as $entrada) {
                if (isset($entrada['clase'])) {
                    self::registrar_comando_desde_clase($entrada['clase']);
                } elseif (isset($entrada['nombre'])) {
                    self::registrar_comando($entrada['nombre'], $entrada['manejador']);
                }
            }

            // ─── Procesar comunicadores pendientes ─────────────
            foreach (RegistroGlobal::$comunicadores_pendientes as $entrada) {
                self::registrar_comunicador_desde_clase($entrada['clase']);
            }

            // ─── Limpiar pendientes e inyectar Controlador ─────
            RegistroGlobal::limpiar();
            RegistroGlobal::_controlador(self::class);

            // ─── Inicializar reloj astronómico con ubicación ───────
            $coordenadas = Entorno::coordenadas();
            self::$_reloj = new RelojAstronomico($coordenadas['latitud'], $coordenadas['longitud']);

            // ─── Comandos genéricos de comunicación ────────────
            self::registrar_comandos_comunicacion();
            
            // ─── Comandos genéricos de dominio ──────────────────
            self::registrar_comandos_dominio();
            static::$inicializo = true;
        }
    }
}

Controlador::inicializar();