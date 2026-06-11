<?php
namespace Iteradores\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringSQL;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringJSON;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraStringXML;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL;
use Iteradores\Controlador\interfaces\Comandos;
use Iteradores\Comandos\Comando;
require_once(".\configuracion\Configuracion.php");
include_once(".\Nucleo\Objeto.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructura.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringJSON.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringXML.php");
require_once("interfaces\Comandos.php");
require_once(".\Comandos\Comando.php");
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
 * @implements Comandos
 * @since V3.4.0
 */
class Controlador extends Objeto implements PerdurarSuperestructura, Comandos {

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

    /**
     * Imprime todos los nodos de la superestructura en el formato adecuado
     * según el entorno configurado (HTML o consola).
     *
     * Delega en {@link NodoElectrico::imprimir()} (o el método correspondiente de
     * cada nodo) para la representación individual. La iteración se realiza a
     * través del método protegido {@link Nodo::por_cada_nodo_ejecutar()}, usando el
     * token interno que {@link Controlador} recibió durante la inicialización.
     *
     * Si la superestructura está vacía, emite una alerta y retorna `false`.
     *
     * @return bool `true` si se imprimió al menos un nodo, `false` en caso contrario.
     *
     * @since 1.3.0 Unifica imprimir_superestructura e imprimir_superestructura2.
     *
     * @see Nodo::imprimir()
     * @see Configuracion.Entorno
     */
    public static function imprimir_superestructura(): bool
    {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_alerta("Controlador::imprimir_superestructura() — la superestructura está vacía");
            return false;
        }

        // Encabezado opcional para consola
        if (Entorno::es_consola()) {
            $colores = Conf::NODOS_COLORES;
            $color   = Entorno::color_ansi($colores['ansi_texto'] ?? '34');
            $reset   = $color ? Entorno::color_ansi('0') : '';
            echo $color . "===== SUPERESTRUCTURA =====\n" . $reset;
        }

        // Iterar sobre todos los nodos llamando a su imprimir()
        $funcion = function($nodo) {
            $nodo->imprimir();
        };
        Nodo::por_cada_nodo_ejecutar(self::$token, $funcion, null);

        return true;
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
            NodoElectrico::_fase(self::$token, "a");
            // ──────────────────────────────────────────────
            // Carga y registro de comandos (siempre)
            // ──────────────────────────────────────────────
            require_once (__DIR__."/../Comandos/index.php");
            self::cargar_comandos_pendientes();

            static::$inicializo = true;
        }
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
     * Se recomienda envolver su definición condicionalmente:
     * ```php
     * if (defined('ENV') && ENV === 'development') {
     *     public static function ejecutarPrueba(callable $callback) { ... }
     * }
     * ```
     *
     * 🔗 Métodos relacionados que requieren token:
     * - {@link Iteradores\Nodos\NodoElectrico::_fase()}
     * - {@link Iteradores\Nodos\NodoElectrico::por_cada_nodo_ejecutar()}
     * - {@link Iteradores\Nodos\NodoElectrico::por_cada_fase_ejecutar()}
     *
     * ---
     * @example
     * ```php
     * // Ejemplo de uso en test.php
     * Controlador::ejecutarPrueba(function($token) {
     *     NodoElectrico::_fase($token, 'fase_test');
     *     NodoElectrico::por_cada_fase_ejecutar($token, function($fase) {
     *         echo "Fase: $fase\n";
     *     });
     * });
     * ```
     *
     * @param callable $callback Función que recibirá el token como único parámetro.
     *                           La función debe respetar la firma: `function(string $token): void`.
     * @return void
     * 
     * @note Este método solo debe ser usado en entornos de prueba/desarrollo.
     *       En producción, se recomienda eliminarlo o deshabilitarlo.
     * @since 0.0.1
     * @access public
     * @static
     */
    public static function ejecutar_prueba(callable $callback)
    {
        // En entorno de desarrollo, se ejecuta; en producción, se podría lanzar una excepción
        if (!Entorno::permite_pruebas()) {
            self::_error('ejecutar_prueba() no está disponible en entorno de producción');
            return;
        }
        $callback(self::$token);
    }

    // ══════════════════════════════════════════════════════
    // INTERFAZ COMANDOS
    // ══════════════════════════════════════════════════════

    /** @var array<string, array{manejador: callable, reversa: ?callable}> Mapa de comandos registrados. */
    private static array $comandos = [];

    /** @var array<callable> Pila de reversiones para deshacer. */
    private static array $historial = [];

    /** @var array<string, array{clase?: string, instancia?: Comando}> Lista de comandos pendientes de registro. */
    private static array $registro_pendiente = [];

    /**
     * Registra un nuevo comando en el sistema.
     *
     * El registro se permite en todos los entornos de forma predeterminada.
     * Si se establece `$solo_desarrollo = true`, el comando solo se registrará
     * en modo desarrollo, evitando exponer herramientas de depuración en producción.
     *
     * Si el comando ya existía, se sobrescribe y se emite una alerta.
     *
     * @param string        $nombre          Nombre único del comando (ej. 'debug:imprimir').
     * @param callable      $manejador       Función que ejecuta el comando.
     * @param callable|null $reversa         Función opcional para deshacer el comando.
     * @param bool          $solo_desarrollo Si `true`, el comando no se registra en producción.
     *
     * @return bool `true` si se registró correctamente, `false` si fue bloqueado por el entorno.
     *
     * @example
     * Controlador::registrar_comando('debug:imprimir', function($token) {
     *     if (!Entorno::permite_pruebas()) { ... }
     *     Objeto::imprimir_errores();
     * }, null, true);
     *
     * @see ejecutar_comando()
     * @see deshacer_ultimo()
     * @since 1.3.1
     */
    public static function registrar_comando(
        string $nombre,
        callable $manejador,
        ?callable $reversa = null,
        bool $solo_desarrollo = false
    ): bool {
        if ($solo_desarrollo && !Entorno::es_desarrollo()) {
            self::_alerta(
                "El comando '$nombre' es de desarrollo y no puede registrarse en el entorno actual."
            );
            return false;
        }

        if (isset(self::$comandos[$nombre])) {
            self::_alerta("El comando '$nombre' ya está registrado y será sobrescrito.");
        }

        self::$comandos[$nombre] = [
            'manejador' => $manejador,
            'reversa'   => $reversa,
        ];
        return true;
    }

    /**
     * Registra un comando a partir de una instancia que implementa {@link Comando}.
     *
     * Extrae los metadatos (nombre, reversa, desarrollo) de la instancia,
     * construye los callables necesarios y los registra internamente.
     *
     * @param Comando $comando Instancia del comando.
     * @return bool
     *
     * @since 1.3.1
     */
    public static function registrar_comando_desde_instancia(Comando $comando): bool
    {
        $nombre = $comando::nombre();
        $solo_desarrollo = $comando::solo_desarrollo();

        $manejador = function(string $token, ...$args) use ($comando) {
            return $comando->ejecutar($token, ...$args);
        };

        $reversa = null;
        $reversa_callable = $comando->reversa();
        if ($reversa_callable !== null) {
            $reversa = function(string $token, ...$args) use ($comando) {
                return $comando->reversa()($token, ...$args);
            };
        }

        return self::registrar_comando($nombre, $manejador, $reversa, $solo_desarrollo);
    }

    /**
     * Registra un comando a partir de una clase que implementa {@link Comando}.
     *
     * Instancia la clase y delega en {@link registrar_comando_desde_instancia()}.
     *
     * @param string $clase Nombre cualificado de la clase.
     * @return bool
     *
     * @since 1.3.1
     */
    public static function registrar_comando_desde_clase(string $clase): bool
    {
        if (!is_subclass_of($clase, Comando::class)) {
            self::_error("La clase '$clase' no implementa la interfaz Comando.");
            return false;
        }

        $instancia = new $clase();
        return self::registrar_comando_desde_instancia($instancia);
    }

    /**
     * Encola un comando para registro diferido o inmediato.
     *
     * Acepta tanto un string (nombre de clase) como una instancia de {@link Comando}.
     * Si el Controlador ya está inicializado, el comando se registra de inmediato;
     * en caso contrario, se almacena en la lista de registro pendiente.
     *
     * @param string|Comando $comando Clase o instancia.
     * @return void
     *
     * @since 1.3.1
     */
    public static function encolar_comando(string|Comando $comando): void
    {
        if (self::$inicializo) {
            if ($comando instanceof Comando) {
                self::registrar_comando_desde_instancia($comando);
            } else {
                self::registrar_comando_desde_clase($comando);
            }
            return;
        }

        if ($comando instanceof Comando) {
            self::$registro_pendiente[] = ['instancia' => $comando];
        } else {
            self::$registro_pendiente[] = ['clase' => $comando];
        }
    }

    /**
     * Procesa la lista de comandos autoencolados y los registra.
     *
     * @return int Número de comandos registrados exitosamente.
     *
     * @since 1.3.1
     */
    public static function cargar_comandos_pendientes(): int
    {
        $contador = 0;
        foreach (self::$registro_pendiente as $entrada) {
            if (isset($entrada['instancia'])) {
                if (self::registrar_comando_desde_instancia($entrada['instancia'])) {
                    $contador++;
                }
            } elseif (isset($entrada['clase'])) {
                if (self::registrar_comando_desde_clase($entrada['clase'])) {
                    $contador++;
                }
            }
        }
        self::$registro_pendiente = [];
        return $contador;
    }

    /**
     * Ejecuta un comando previamente registrado.
     *
     * Busca el manejador asociado al nombre, verifica los permisos
     * mediante {@link tiene_permiso()} y lo invoca con el token interno
     * y los argumentos proporcionados.
     *
     * Si el comando tiene definida una reversa, esta se guarda en el
     * historial para poder deshacerla posteriormente con {@link deshacer_ultimo()}.
     *
     * @param string $nombre Nombre del comando.
     * @param mixed  ...$args Argumentos adicionales para el manejador.
     *
     * @return mixed El resultado del manejador, o `null` si falla.
     *
     * @example
     * Controlador::ejecutar_comando('debug:imprimir');
     *
     * @see registrar_comando()
     * @see tiene_permiso()
     * @see deshacer_ultimo()
     * @since 1.3.1
     */
    public static function ejecutar_comando(string $nombre, ...$args)
    {
        if (!isset(self::$comandos[$nombre])) {
            self::_error("Comando desconocido: '$nombre'.");
            return null;
        }

        if (!self::tiene_permiso($nombre)) {
            self::_error("Permiso denegado para el comando '$nombre'.");
            return null;
        }

        $registro = self::$comandos[$nombre];
        $manejador = $registro['manejador'];
        $reversa   = $registro['reversa'] ?? null;

        $token = self::$token;
        $resultado = $manejador($token, ...$args);

        if ($reversa !== null) {
            self::$historial[] = function() use ($reversa, $token, $args) {
                return $reversa($token, ...$args);
            };
        }

        return $resultado;
    }

    /**
     * Verifica si el usuario actual tiene permiso para ejecutar el comando.
     *
     * **Placeholder:** actualmente retorna `true` para cualquier comando.
     *
     * @param string $nombre_comando Nombre del comando.
     * @return bool
     *
     * @see ejecutar_comando()
     * @since 1.3.1
     */
    public static function tiene_permiso(string $nombre_comando): bool
    {
        return true;
    }

    /**
     * Deshace el último comando ejecutado que tuviera reversa.
     *
     * @return mixed El resultado de la reversa, o `null` si no hay nada que deshacer.
     *
     * @see ejecutar_comando()
     * @see registrar_comando()
     * @since 1.3.1
     */
    public static function deshacer_ultimo()
    {
        if (empty(self::$historial)) {
            self::_alerta('No hay comandos para deshacer.');
            return null;
        }

        $reversa = array_pop(self::$historial);
        return $reversa();
    }

}

Controlador::inicializar();
