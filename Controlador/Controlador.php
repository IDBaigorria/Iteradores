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
use Iteradores\Controlador\interfaces\Comunicadores;
use Iteradores\Comunicadores\Comunicador;
require_once(".\configuracion\Configuracion.php");
include_once(".\Nucleo\Objeto.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructura.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraElectricosStringSQL.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringJSON.php");
require_once("PerdurarSuperestructura\PerdurarSuperestructuraStringXML.php");
require_once("interfaces\Comandos.php");
require_once(".\Comandos\Comando.php");
require_once("interfaces\Comunicadores.php");
require_once(".\Comunicadores\Comunicador.php");
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
class Controlador extends Objeto implements PerdurarSuperestructura, Comandos, Comunicadores {

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

    /**
     * Mapa de comandos registrados.
     *
     * @var array<string, array{
     *     manejador: callable,
     *     reversa: ?callable,
     *     clase: ?string
     * }>
     */    
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
     * @param string        $nombre          Nombre único del comando (ej. 'depuracion:imprimir').
     * @param callable      $manejador       Función que ejecuta el comando.
     * @param callable|null $reversa         Función opcional para deshacer el comando.
     * @param bool          $solo_desarrollo Si `true`, el comando no se registra en producción.
     *
     * @return bool `true` si se registró correctamente, `false` si fue bloqueado por el entorno.
     *
     * @example
     * Controlador::registrar_comando('depuracion:imprimir', function($token) {
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
            'clase'     => null,
        ];
        return true;
    }

    /**
     * Registra un comando a partir de una instancia que implementa {@link Comando}.
     *
     * Extrae los metadatos (nombre, reversa, desarrollo) de la instancia,
     * construye los callables necesarios y los registra internamente.
     *
     * Además, valida que los nombres de los parámetros definidos por el comando
     * no colisionen con las {@link \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS
     * palabras reservadas}. Si se detecta una colisión, el registro se rechaza
     * y se emite un error.
     *
     * @param Comando $comando Instancia del comando.
     * @return bool
     *
     * @since 1.3.1
     * @version 1.3.2
     */
    public static function registrar_comando_desde_instancia(Comando $comando): bool
    {
        $nombre = $comando::nombre();
        $solo_desarrollo = $comando::solo_desarrollo();
        $clase = get_class($comando);

        // Verificar palabras reservadas
        if (!self::validar_parametros_reservados($clase)) {
            return false;
        }

        $manejador = function(string $token, $args) use ($comando) {
            return $comando->ejecutar($token, $args);
        };

        $reversa = null;
        $reversa_callable = $comando->reversa();
        if ($reversa_callable !== null) {
            $reversa = function(string $token, $args) use ($comando) {
                return $comando->reversa()($token, $args);
            };
        }

        // Guardar también la clase para el parseo
        self::$comandos[$nombre] = [
            'manejador' => $manejador,
            'reversa'   => $reversa,
            'clase'     => $clase,
        ];

        return true;
    }

    /**
     * Registra un comando a partir de una clase que implementa {@link Comando}.
     *
     * Instancia la clase y delega en {@link registrar_comando_desde_instancia()}.
     * 
     * Además, valida que los nombres de los parámetros definidos por el comando
     * no colisionen con las {@link \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS
     * palabras reservadas}. Si se detecta una colisión, el registro se rechaza
     * y se emite un error.
     *
     * @param string $clase Nombre cualificado de la clase.
     * @return bool
     *
     * @since 1.3.1
     * @version 1.3.2
     */
    public static function registrar_comando_desde_clase(string $clase): bool
    {
        if (!is_subclass_of($clase, Comando::class)) {
            self::_error("La clase '$clase' no implementa la interfaz Comando.");
            return false;
        }

        // Verificar palabras reservadas
        if (!self::validar_parametros_reservados($clase)) {
            return false;
        }

        $instancia = new $clase();
        return self::registrar_comando_desde_instancia($instancia);
    }

    /**
     * Verifica que los parámetros del comando no usen palabras reservadas.
     *
     * @param string $clase Nombre de la clase comando.
     * @return bool
     */
    private static function validar_parametros_reservados(string $clase): bool
    {
        if (!method_exists($clase, 'parametros')) {
            return true;
        }

        $reservadas = Conf::PALABRAS_RESERVADAS_COMANDOS;
        $parametros = $clase::parametros();
        foreach ($parametros as $param) {
            if (in_array($param['nombre'], $reservadas, true)) {
                self::_error(
                    "El comando '{$clase::nombre()}' usa una palabra reservada como parámetro: '{$param['nombre']}'."
                );
                return false;
            }
        }
        return true;
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
     * Este método es el punto central de ejecución del sistema de comandos.
     * Se encarga de localizar el manejador asociado al comando, verificar
     * permisos, parsear y validar argumentos, mostrar ayuda cuando se solicita
     * y, finalmente, invocar la lógica del comando.
     *
     * **Flujo de ejecución detallado:**
     *
     * 1. **Búsqueda del comando:** Busca el nombre en el mapa interno de
     *    comandos registrados. Si no existe, registra un error con
     *    {@link _error()} y retorna `null`.
     *
     * 2. **Verificación de permisos:** Invoca {@link tiene_permiso()} para
     *    comprobar si el usuario actual está autorizado. Si no lo está,
     *    registra un error y retorna `null`. Por ahora, {@link tiene_permiso()}
     *    es un placeholder que retorna `true`.
     *
     * 3. **Detección de solicitud de ayuda:** Examina cada argumento en
     *    busca de las palabras reservadas definidas en
     *    {@link \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS}
     *    (`man`, `help`, `h`). Si encuentra alguna, invoca
     *    {@link mostrar_ayuda()} con la clase del comando y retorna `true`
     *    sin ejecutar el comando.
     *
     * 4. **Parseo y validación de argumentos:** Si el comando tiene una clase
     *    asociada y ésta implementa el método {@link Comando::parametros()},
     *    se obtiene la definición de parámetros y se invoca
     *    {@link parsear_y_validar_args()} para convertir los argumentos
     *    crudos en una estructura normalizada. Si hay errores de validación
     *    (flags/opciones desconocidas, parámetros obligatorios faltantes),
     *    se registran con {@link _error()}, se muestra la ayuda y se retorna
     *    `null`.
     *
     * 5. **Ejecución del manejador:** Invoca el manejador del comando con el
     *    token de seguridad interno y los argumentos parseados (o crudos, si
     *    no hay definición de parámetros).
     *
     * 6. **Registro de reversa:** Si el comando tiene definida una función de
     *    reversa (proporcionada durante el registro), la almacena en la pila
     *    de historial para que pueda ser deshecha posteriormente con
     *    {@link deshacer_ultimo()}.
     *
     * **Solicitudes de ayuda:**
     * Las palabras reservadas (`--man`, `--help`, `-h`) están centralizadas
     * en {@link \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS}.
     * Al detectar cualquiera de ellas, el sistema muestra automáticamente
     * la ayuda generada a partir de {@link Comando::descripcion()},
     * {@link Comando::parametros()} y {@link Comando::ejemplos()}, adaptando
     * el formato al entorno (consola o HTML). La ejecución del comando **no**
     * se realiza.
     *
     * **Validación de argumentos:**
     * Si el comando define parámetros, el método {@link parsear_y_validar_args()}
     * compara cada argumento recibido contra la definición. Los argumentos
     * que no coinciden con ningún parámetro declarado se registran como error
     * y provocan la visualización de la ayuda.
     *
     * ⚠️ **Importante para desarrolladores de comandos:**
     * No utilice ninguna de las palabras reservadas como nombre de un
     * parámetro en {@link Comando::parametros()}. El sistema rechazará el
     * registro de comandos que infrinjan esta regla mediante
     * {@link registrar_comando_desde_instancia()} o
     * {@link registrar_comando_desde_clase()}.
     *
     * @param string $nombre Nombre del comando (ej. 'depuracion:imprimir').
     * @param mixed  ...$args Argumentos para el manejador (crudos, serán parseados).
     *
     * @return mixed El resultado devuelto por el manejador del comando, o
     *               `null` si el comando no existe, no hay permiso o los
     *               argumentos son inválidos. Retorna `true` si se mostró
     *               la ayuda.
     *
     * @example
     * // Ejecución básica
     * Controlador::ejecutar_comando('depuracion:imprimir');
     *
     * // Con argumentos
     * Controlador::ejecutar_comando('depuracion:imprimir', '--errores');
     *
     * // Solicitar ayuda
     * Controlador::ejecutar_comando('depuracion:imprimir', '--man');
     *
     * @see registrar_comando()
     * @see tiene_permiso()
     * @see deshacer_ultimo()
     * @see mostrar_ayuda()
     * @see parsear_y_validar_args()
     * @see \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS
     * @since 1.3.1
     * @version 1.3.2
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
        $clase = $registro['clase'] ?? null;
        $manejador = $registro['manejador'];
       // echo "ee".$registro['clase'];
        // Detectar solicitud de ayuda (palabras reservadas)
        $ayuda_flags = Conf::PALABRAS_RESERVADAS_COMANDOS;
        foreach ($args as $arg) {
            $sin_guiones = ltrim((string)$arg, '-');
            if (in_array($sin_guiones, $ayuda_flags, true)) {
                if ($clase !== null) {
                    self::mostrar_ayuda($clase);
                } else {
                    echo "Comando '$nombre' (sin ayuda disponible).\n";
                }
                return true;
            }
        }

        // Parsear y validar argumentos solo si el comando tiene definición
        if ($clase && method_exists($clase, 'parametros')) {
            $definicion = $clase::parametros();
            $args_parseados = self::parsear_y_validar_args($definicion, $args, $clase);
            if ($args_parseados === null) {
                // Los errores ya se registraron y la ayuda se mostró
                return null;
            }
        } else {
            // Sin definición de parámetros: pasar los argumentos tal cual
            $args_parseados = $args;
        }

        $token = self::$token;
        $reversa = $registro['reversa'] ?? null;

        $resultado = $manejador($token, $args_parseados);

        if ($reversa !== null) {
            self::$historial[] = function() use ($reversa, $token, $args_parseados) {
                return $reversa($token, $args_parseados);
            };
        }

        return $resultado;
    }
    /**
     * Valida los argumentos crudos contra la definición de parámetros del comando.
     *
     * Si se encuentran errores de validación, los registra con {@link _error()}
     * y muestra la ayuda del comando.
     *
     * @param array  $definicion Definición de parámetros del comando.
     * @param array  $args       Argumentos crudos.
     * @param string $clase      Nombre de la clase del comando (para mostrar ayuda).
     *
     * @return array|null Estructura con 'posicionales', 'banderas' y 'opciones',
     *                    o `null` si hay errores.
     *
     * @since 1.3.2
     */
    private static function parsear_y_validar_args(array $definicion, array $args, string $clase): ?array
    {
        $posicionales = [];
        $banderas = [];
        $opciones = [];

        // Inicializar valores por defecto
        foreach ($definicion as $param) {
            $nombre = $param['nombre'];
            switch ($param['tipo']) {
                case 'bandera':
                    $banderas[$nombre] = $param['defecto'] ?? false;
                    break;
                case 'opcion':
                    if (array_key_exists('defecto', $param)) {
                        $opciones[$nombre] = $param['defecto'];
                    }
                    break;
            }
        }

        // Parsear argumentos crudos
        $pos_index = 0;
        foreach ($args as $arg) {
            if (is_string($arg) && str_starts_with($arg, '--')) {
                $sin_guiones = substr($arg, 2);
                if (str_contains($sin_guiones, '=')) {
                    [$clave, $valor] = explode('=', $sin_guiones, 2);
                    $opciones[$clave] = $valor;
                } else {
                    $banderas[$sin_guiones] = true;
                }
            } else {
                $posicionales[$pos_index++] = $arg;
            }
        }

        $errores = [];
        $nombres_conocidos = array_column($definicion, 'nombre');

        // Validar banderas desconocidas
        foreach ($banderas as $nombre => $_) {
            if (!in_array($nombre, $nombres_conocidos, true)) {
                $errores[] = "Flag desconocido: '--$nombre'.";
            }
        }

        // Validar opciones desconocidas
        foreach ($opciones as $nombre => $_) {
            if (!in_array($nombre, $nombres_conocidos, true)) {
                $errores[] = "Opción desconocida: '--$nombre'.";
            }
        }

        // Validar parámetros según la definición
        $pos_def = 0; // índice para los posicionales en la definición
        foreach ($definicion as $param) {
            $nombre = $param['nombre'];
            $tipo = $param['tipo'];
            $obligatorio = $param['obligatorio'] ?? false;
            $valores_permitidos = $param['valores'] ?? null;

            if ($tipo === 'posicional') {
                // ¿Está presente?
                if ($obligatorio && !isset($posicionales[$pos_def])) {
                    $errores[] = "Falta el argumento posicional '{$nombre}' (obligatorio). Valores permitidos: ". implode(', ', $valores_permitidos) . ".";
                } elseif (isset($posicionales[$pos_def]) && $valores_permitidos !== null) {
                    // Validar valor permitido
                    if (!in_array($posicionales[$pos_def], $valores_permitidos, true)) {
                        $errores[] = "Valor inválido para '{$nombre}': '{$posicionales[$pos_def]}'. Valores permitidos: " . implode(', ', $valores_permitidos) . ".";
                    }
                }
                $pos_def++;
            } elseif ($tipo === 'opcion' && $valores_permitidos !== null && isset($opciones[$nombre])) {
                // Validar valor de opción
                if (!in_array($opciones[$nombre], $valores_permitidos, true)) {
                    $errores[] = "Valor inválido para '--{$nombre}': '{$opciones[$nombre]}'. Valores permitidos: " . implode(', ', $valores_permitidos) . ".";
                }
            }
        }

        if (!empty($errores)) {
            foreach ($errores as $error) {
                self::_error($error);
            }
            self::mostrar_ayuda($clase);
            return null;
        }

        return [
            'posicionales' => $posicionales,
            'banderas'     => $banderas,
            'opciones'     => $opciones,
        ];
    }

    /**
     * Muestra la ayuda de un comando en el formato adecuado según el entorno.
     *
     * La ayuda se genera dinámicamente consultando los métodos
     * {@link Comando::descripcion()}, {@link Comando::parametros()} y
     * {@link Comando::ejemplos()} de la clase del comando. Si alguno de estos
     * métodos no está definido, se omite la sección correspondiente.
     *
     * @param string $clase Nombre cualificado de la clase comando.
     *
     * @return void
     *
     * @since 1.3.2
     */
    private static function mostrar_ayuda(string $clase): void
    {
        $nombre = $clase::nombre();
        $ayuda = "Comando: $nombre\n";

        // Descripción (opcional)
        if (method_exists($clase, 'descripcion')) {
            $ayuda .= $clase::descripcion() . "\n";
        }

        // Parámetros (opcionales)
        if (method_exists($clase, 'parametros')) {
            $parametros = $clase::parametros();
            if (!empty($parametros)) {
                $ayuda .= "\nParámetros:\n";
                foreach ($parametros as $p) {
                    $obligatorio = !empty($p['obligatorio']) ? ' (obligatorio)' : '';
                    $ayuda .= "  --{$p['nombre']} [{$p['tipo']}]$obligatorio: {$p['descripcion']}\n";
                }
            }
        }

        // Ejemplos (opcionales)
        if (method_exists($clase, 'ejemplos')) {
            $ejemplos = $clase::ejemplos();
            if (!empty($ejemplos)) {
                $ayuda .= "\nEjemplos:\n";
                foreach ($ejemplos as $ej) {
                    $ayuda .= "  $ej\n";
                }
            }
        }

        // Salida según entorno
        if (Entorno::es_consola()) {
            echo $ayuda;
        } else {
            echo '<pre>' . htmlspecialchars($ayuda) . '</pre>';
        }
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

    // ══════════════════════════════════════════════════════
    // INTERFAZ COMUNICADORES
    // ══════════════════════════════════════════════════════

    /**
     * Mapa de comunicadores registrados.
     *
     * Estructura:
     * [
     *     'nombre_comunicador' => [
     *         'instancia' => Comunicador,   // Instancia única del comunicador
     *         'clase'     => string,        // Nombre cualificado de la clase
     *     ],
     *     ...
     * ]
     *
     * @var array<string, array{instancia: Comunicador, clase: string}>
     */
    private static array $comunicadores = [];

    /**
     * Lista de comunicadores pendientes de registro.
     *
     * Se pobla mediante {@link encolar_comunicador()} durante la carga
     * de archivos. Al finalizar la inicialización, {@link cargar_comunicadores_pendientes()}
     * los procesa y registra.
     *
     * @var array<int, array{clase?: string, instancia?: Comunicador}>
     */
    private static array $registro_comunicadores_pendiente = [];

    /**
     * Registra un nuevo comunicador a partir de una clase que implementa
     * la interfaz {@link \Iteradores\Comunicadores\Comunicador}.
     *
     * Instancia la clase, verifica que no exista ya otro comunicador con el
     * mismo nombre y, si todo es correcto, lo almacena en el mapa interno.
     * Solo se permite un comunicador por nombre.
     *
     * @param string $clase Nombre cualificado de la clase del comunicador.
     *
     * @return bool `true` si se registró correctamente,
     *              `false` si la clase no es válida o ya existe un comunicador con ese nombre.
     *
     * @example
     * Controlador::registrar_comunicador_desde_clase(ComunicadorHTTP::class);
     *
     * @since 1.3.3
     */
    public static function registrar_comunicador_desde_clase(string $clase): bool
    {
        if (!is_subclass_of($clase, Comunicador::class)) {
            self::_error("La clase '$clase' no implementa la interfaz Comunicador.");
            return false;
        }

        $instancia = new $clase();
        return self::registrar_comunicador_desde_instancia($instancia);
    }

    /**
     * Registra un nuevo comunicador a partir de una instancia que implementa
     * la interfaz {@link \Iteradores\Comunicadores\Comunicador}.
     *
     * Si ya existe un comunicador con el mismo nombre, emite una alerta y
     * sobrescribe la entrada anterior.
     *
     * @param Comunicador $comunicador Instancia del comunicador.
     *
     * @return bool `true` si se registró correctamente.
     *
     * @example
     * Controlador::registrar_comunicador_desde_instancia(new ComunicadorHTTP());
     *
     * @since 1.3.3
     */
    public static function registrar_comunicador_desde_instancia(Comunicador $comunicador): bool
    {
        $nombre = $comunicador::nombre();
        $clase = get_class($comunicador);

        if (isset(self::$comunicadores[$nombre])) {
            self::_alerta("El comunicador '$nombre' ya está registrado y será sobrescrito.");
        }

        self::$comunicadores[$nombre] = [
            'instancia' => $comunicador,
            'clase'     => $clase,
        ];

        return true;
    }

    /**
     * Encola un comunicador para registro diferido o inmediato.
     *
     * Este método es invocado automáticamente por la línea de autoencolación
     * al final de cada archivo de comunicador. Acepta tanto un string (nombre
     * de clase) como una instancia de {@link \Iteradores\Comunicadores\Comunicador}.
     *
     * **Comportamiento según el estado del Controlador:**
     * - Si el Controlador **ya está inicializado**, el comunicador se registra
     *   de inmediato (útil para registro en caliente).
     * - Si el Controlador **no está inicializado**, el comunicador se guarda en
     *   una lista temporal y será registrado al llamar a
     *   {@link cargar_comunicadores_pendientes()}.
     *
     * @param string|Comunicador $comunicador Clase o instancia.
     *
     * @return void
     *
     * @example
     * // En el archivo del comunicador:
     * Controlador::encolar_comunicador(ComunicadorHTTP::class);
     *
     * @since 1.3.3
     */
    public static function encolar_comunicador(string|Comunicador $comunicador): void
    {
        if (self::$inicializo) {
            // Registro inmediato
            if ($comunicador instanceof Comunicador) {
                self::registrar_comunicador_desde_instancia($comunicador);
            } else {
                self::registrar_comunicador_desde_clase($comunicador);
            }
            return;
        }

        // Registro diferido
        if ($comunicador instanceof Comunicador) {
            self::$registro_comunicadores_pendiente[] = ['instancia' => $comunicador];
        } else {
            self::$registro_comunicadores_pendiente[] = ['clase' => $comunicador];
        }
    }

    /**
     * Procesa la lista de comunicadores autoencolados y los registra.
     *
     * Recorre la lista temporal de comunicadores pendientes y los registra
     * uno por uno. Finalmente, vacía la lista.
     *
     * Este método es invocado al final de {@link inicializar()}, después de
     * que todos los archivos de comunicadores han sido incluidos.
     *
     * @return int Número de comunicadores registrados exitosamente en esta llamada.
     *
     * @since 1.3.3
     */
    public static function cargar_comunicadores_pendientes(): int
    {
        $contador = 0;

        foreach (self::$registro_comunicadores_pendiente as $entrada) {
            $exito = false;

            if (isset($entrada['instancia'])) {
                $exito = self::registrar_comunicador_desde_instancia($entrada['instancia']);
            } elseif (isset($entrada['clase'])) {
                $exito = self::registrar_comunicador_desde_clase($entrada['clase']);
            }

            if ($exito) {
                $contador++;
            }
        }

        self::$registro_comunicadores_pendiente = [];
        return $contador;
    }

    /**
     * Obtiene la instancia única de un comunicador por su nombre.
     *
     * Si se invoca sin argumentos (o con el valor especial `'predeterminado'`),
     * devuelve automáticamente el comunicador de salida estándar correspondiente
     * al entorno actual:
     * - En **consola** → {@link SalidaDepuracionConsola} (`salida_depuracion_consola`).
     * - En **navegador** → {@link SalidaDepuracionHTML} (`salida_depuracion_html`).
     *
     * En cualquier otro caso, busca el comunicador en el mapa interno y verifica
     * que el usuario actual tenga permiso para utilizarlo mediante
     * {@link tiene_permiso_comunicador()}.
     *
     * Si el comunicador no existe o el usuario no tiene permiso, retorna `null`
     * y registra un error.
     *
     * @param string $nombre Nombre del comunicador (ej. `'archivo'`, `'http'`).
     *                       Si se omite o es `'predeterminado'`, se usa la salida
     *                       estándar según el entorno.
     *
     * @return Comunicador|null La instancia del comunicador,
     *                                                     o `null` si no está disponible.
     *
     * @example
     * // Obtener la salida estándar (consola o HTML según Entorno)
     * $salida = Controlador::comunicador();
     * $salida->enviar('', 'Hola mundo');
     *
     * // Obtener un comunicador específico
     * $http = Controlador::comunicador('http');
     * if ($http) {
     *     $http->enviar('https://api.example.com', $datos);
     * }
     *
     * @see SalidaDepuracionHTML
     * @see SalidaDepuracionConsola
     * @since 1.3.3
     */
    public static function comunicador(string $nombre = 'predeterminado'): ?Comunicador
    {
        // Resolver nombre del comunicador de salida según entorno
        if ($nombre === 'predeterminado') {
            $nombre = Entorno::es_consola()
                ? 'salida_depuracion_consola'
                : 'salida_depuracion_html';
        }

        if (!isset(self::$comunicadores[$nombre])) {
            self::_error("Comunicador desconocido: '$nombre'.");
            return null;
        }

        if (!self::tiene_permiso_comunicador($nombre)) {
            self::_error("Permiso denegado para el comunicador '$nombre'.");
            return null;
        }

        return self::$comunicadores[$nombre]['instancia'];
    }

    /**
     * Verifica si el usuario actual tiene permiso para usar el comunicador.
     *
     * **Placeholder:** actualmente retorna `true` para cualquier comunicador.
     * En el futuro se integrará con un sistema de roles/permisos.
     *
     * @param string $nombre Nombre del comunicador.
     *
     * @return bool `true` si el usuario tiene permiso, `false` en caso contrario.
     *
     * @see comunicador()
     * @since 1.3.3
     */
    public static function tiene_permiso_comunicador(string $nombre): bool
    {
        // TODO: implementar verificación real de permisos
        return true;
    }

    /**
     * Registra los comandos genéricos de comunicación.
     *
     * Se invoca durante {@link inicializar()} para que estén disponibles
     * tanto para programadores como para el futuro sistema de aprendizaje.
     *
     * @return void
     * @since 1.3.3
     */
    private static function registrar_comandos_comunicacion(): void
    {
        // ─── comunicación:leer ───────────────────────────────
        self::registrar_comando('comunicacion:leer', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $destino = $args[1] ?? '';
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'comunicacion:leer'.");
                return null;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;
            return $comunicador->solicitar($destino, null, ['accion' => 'leer']);
        }, null, false);

        // ─── comunicación:escribir ────────────────────────────
        /**
         * Comando: comunicacion:escribir
         *
         * Envía un mensaje a través del comunicador indicado.
         *
         * @param string $medio   Nombre del comunicador (ej. 'salida_depuracion_consola', 'archivo').
         * @param string $mensaje Contenido a escribir.
         * @param string $destino (Opcional) Destino del mensaje (ruta de archivo, URL, etc.).
         *                        Por defecto es cadena vacía (salida estándar).
         * @return bool `true` si se escribió correctamente.
         */
        self::registrar_comando('comunicacion:escribir', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $mensaje = $args[1] ?? '';
            $destino = $args[2] ?? '';   // predeterminado: cadena vacía
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'comunicacion:escribir'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;
            $comunicador->enviar($destino, $mensaje);
            return true;
        }, null, false);

        // ─── comunicación:preguntar ───────────────────────────
        self::registrar_comando('comunicacion:preguntar', function(string $token, array $args) {
            $medio   = $args[0] ?? 'salida_depuracion_consola';
            $mensaje = $args[1] ?? '';
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;
            if ($medio === 'salida_depuracion_consola') {
                echo $mensaje . ' ';
                return trim(fgets(STDIN));
            }
            return $comunicador->solicitar('', $mensaje);
        }, null, false);

        // ─── comunicación:eliminar ────────────────────────────
        self::registrar_comando('comunicacion:eliminar', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $destino = $args[1] ?? '';
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'comunicacion:eliminar'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;
            $comunicador->enviar($destino, null, ['accion' => 'eliminar']);
            return true;
        }, null, false);

        // ─── comunicación:listar ──────────────────────────────
        self::registrar_comando('comunicacion:listar', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $destino = $args[1] ?? '.';
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'comunicacion:listar'.");
                return null;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;
            return $comunicador->solicitar($destino, null, ['accion' => 'listar']);
        }, null, false);

        // ─── comunicación:escuchar ─────────────────────────────
        self::registrar_comando('comunicacion:escuchar', function(string $token, array $args) {
            $medio = $args[0] ?? null;
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'comunicacion:escuchar'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;
            $comunicador->escuchar(function($mensaje) use ($medio) {
                $salida = self::comunicador();
                $salida?->enviar('', "[$medio] Recibido: " . json_encode($mensaje));
            });
            return true;
        }, null, false);

        // Alias archivo:leer
        self::registrar_comando('archivo:leer', function(string $token, array $args) {
            $destino = $args[0] ?? '';
            return self::ejecutar_comando('comunicacion:leer', 'archivo', $destino);
        }, null, false);

        // Alias archivo:escribir
        self::registrar_comando('archivo:escribir', function(string $token, array $args) {
            $destino = $args[0] ?? '';
            $mensaje = $args[1] ?? '';
            return self::ejecutar_comando('comunicacion:escribir', 'archivo', $destino, $mensaje);
        }, null, false);

        // Alias archivo:eliminar
        self::registrar_comando('archivo:eliminar', function(string $token, array $args) {
            $destino = $args[0] ?? '';
            return self::ejecutar_comando('comunicacion:eliminar', 'archivo', $destino);
        }, null, false);

        // Alias archivo:listar
        self::registrar_comando('archivo:listar', function(string $token, array $args) {
            $destino = $args[0] ?? '.';
            return self::ejecutar_comando('comunicacion:listar', 'archivo', $destino);
        }, null, false);
    }
    /**
     * Escribe un mensaje en la salida estándar configurada según el entorno.
     *
     * Obtiene el comunicador predeterminado ({@link SalidaDepuracionHTML}
     * o {@link SalidaConsola}) y envía el mensaje a través de él.
     *
     * Es el equivalente a `echo` o `console.log`, pero adaptado al
     * tipo de salida definido en {@link \Iteradores\Configuracion\Entorno}.
     *
     * @param string $mensaje Texto a escribir en la salida estándar.
     *
     * @return void
     *
     * @example
     * Controlador::escribir_salida("Operación completada.");
     *
     * @since 1.3.3
     */
    public static function escribir_salida(string $mensaje): void
    {
        $salida = self::comunicador();   // devuelve el predeterminado
        if ($salida !== null) {
            $salida->enviar('', $mensaje);
        }
    }

    // ══════════════════════════════════════════════════════
    // INICIALIZACION
    // ══════════════════════════════════════════════════════
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
            
            // Cargar todos los archivos de comunicadores (ejecuta sus encolamientos)
            require_once __DIR__ . '/../Comunicadores/index.php';

            // Procesar y registrar los comunicadores autoencolados
            self::cargar_comunicadores_pendientes();
            
            self::registrar_comandos_comunicacion();

            // ──────────────────────────────────────────────
            // Carga y registro de comandos (siempre)
            // ──────────────────────────────────────────────
            require_once (__DIR__."/../Comandos/index.php");
            self::cargar_comandos_pendientes();


            static::$inicializo = true;
        }
    }

}

Controlador::inicializar();
