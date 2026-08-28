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

require_once("PerdurarSuperestructura/PerdurarSuperestructura.php");
require_once("PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php");
require_once("PerdurarSuperestructura/PerdurarSuperestructuraElectricosStringSQL.php");
require_once("PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php");
require_once("PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php");
require_once("interfaces/Comandos.php");
require_once("interfaces/Comunicadores.php");
require_once("interfaces/VectorGravitacional.php");
require_once("interfaces/Motor.php");
require_once("interfaces/Dominios.php");

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
    /**
     * Verifica que no haya nodos ocupados (autoenlace "ocupado") en la superestructura.
     *
     * @since 1.5i.4
     * @version 1.5i.4
     * @return bool `true` si todos los nodos están desocupados, `false` en caso contrario.
     */
    protected static function verificar_superestructura_desocupada(): bool
    {
        $ocupados = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            $ady = $nodo->adyacente("ocupado");
            return ($ady === $nodo);
        }, null);

        if ($ocupados) {
            foreach ($ocupados as $id => $esta_ocupado) {
                if ($esta_ocupado) {
                    static::_error("No se puede guardar: el nodo con ID $id está ocupado.");
                    return false;
                }
            }
        }
        return true;
    }


    // ======= Métodos públicos de operación =======

    /** @return bool 
     * @version 1.5i.4
    */
    public static function guardar($nombre): bool {
        if (!static::verificar_superestructura_desocupada()) {
            return false;
        }
        return (bool) static::delegar('guardar', $nombre);
    }

    /** @return bool 
     * @version 1.5i.4
    */
    public static function cargar($nombre): bool {
        Nodo::vaciar_superestructura(static::$token);
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

    /**
     * Registra un nuevo comando en el sistema.
     *
     * @param string        $nombre          Nombre único del comando.
     * @param callable      $manejador       Función que ejecuta el comando.
     * @param callable|null $reversa         Función opcional para deshacer el comando.
     * @param bool          $solo_desarrollo Si `true`, el comando no se registra en producción.
     * @return bool `true` si se registró correctamente, `false` si fue bloqueado por el entorno.
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
     * @param Comando $comando Instancia del comando.
     * @return bool
     * @since 1.3.1
     * @version 1.3.4
     */
    public static function registrar_comando_desde_instancia(Comando $comando): bool
    {
        $nombre = $comando::nombre();
        $solo_desarrollo = $comando::solo_desarrollo();
        $clase = get_class($comando);

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
     * @param string $clase Nombre cualificado de la clase.
     * @return bool
     * @since 1.3.1
     * @version 1.3.4
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
     * Ejecuta un comando previamente registrado.
     *
     * Este método es el punto central de ejecución del sistema de comandos.
     * Se encarga de localizar el manejador asociado al comando, verificar
     * permisos, parsear los argumentos cuando es posible y, finalmente,
     * invocar la lógica del comando.
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
     * 3. **Parseo de argumentos (opcional):** Si el comando tiene una clase
     *    asociada y ésta implementa el método {@link Comando::parametros()},
     *    se obtiene la definición de parámetros y se invoca
     *    {@link parsear_y_validar_args()} para convertir los argumentos
     *    crudos en una estructura normalizada.
     *    Si no hay definición de parámetros, los argumentos se pasan
     *    directamente al manejador como un array crudo.
     *
     * 4. **Ejecución del manejador:** Invoca el manejador del comando con el
     *    token de seguridad interno y los argumentos (parseados o crudos).
     *
     * 5. **Registro de reversa:** Si el comando tiene definida una función de
     *    reversa (proporcionada durante el registro), la almacena en la pila
     *    de historial para que pueda ser deshecha posteriormente con
     *    {@link deshacer_ultimo()}.
     *
     * @param string $nombre Nombre del comando (ej. 'depuracion:imprimir').
     * @param mixed  ...$args Argumentos para el manejador (crudos, serán parseados si hay definición).
     *
     * @return mixed El resultado devuelto por el manejador del comando, o
     *               `null` si el comando no existe, no hay permiso o los
     *               argumentos son inválidos.
     *
     * @example
     * // Ejecución básica
     * Controlador::ejecutar_comando('depuracion:imprimir');
     *
     * // Con argumentos
     * Controlador::ejecutar_comando('depuracion:imprimir', '--errores');
     * Controlador::ejecutar_comando('comunicacion:escribir', 'archivo', '/ruta', 'contenido');
     *
     * @see registrar_comando()
     * @see tiene_permiso()
     * @see deshacer_ultimo()
     * @since 1.3.1
     * @version 1.3.4
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

        // Parsear argumentos solo si el comando tiene definición
        if ($clase && method_exists($clase, 'parametros')) {
            $definicion = $clase::parametros();
            $args_parseados = self::parsear_y_validar_args($definicion, $args, $clase);
            if ($args_parseados === null) {
                return null;
            }
        } else {
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
     * Si se encuentran errores de validación, los registra con {@link _error()}.
     *
     * @param array  $definicion Definición de parámetros del comando.
     * @param array  $args       Argumentos crudos.
     * @param string $clase      Nombre de la clase del comando.
     *
     * @return array|null Estructura con 'posicionales', 'banderas' y 'opciones',
     *                    o `null` si hay errores.
     *
     * @since 1.3.2
     * @version 1.3.4 (eliminada la llamada a mostrar_ayuda)
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
        $pos_def = 0;
        foreach ($definicion as $param) {
            $nombre = $param['nombre'];
            $tipo = $param['tipo'];
            $obligatorio = $param['obligatorio'] ?? false;
            $valores_permitidos = $param['valores'] ?? null;

            if ($tipo === 'posicional') {
                if ($obligatorio && !isset($posicionales[$pos_def])) {
                    $errores[] = "Falta el argumento posicional '{$nombre}' (obligatorio). Valores permitidos: ". implode(', ', $valores_permitidos) . ".";
                } elseif (isset($posicionales[$pos_def]) && $valores_permitidos !== null) {
                    if (!in_array($posicionales[$pos_def], $valores_permitidos, true)) {
                        $errores[] = "Valor inválido para '{$nombre}': '{$posicionales[$pos_def]}'. Valores permitidos: " . implode(', ', $valores_permitidos) . ".";
                    }
                }
                $pos_def++;
            } elseif ($tipo === 'opcion' && $valores_permitidos !== null && isset($opciones[$nombre])) {
                if (!in_array($opciones[$nombre], $valores_permitidos, true)) {
                    $errores[] = "Valor inválido para '--{$nombre}': '{$opciones[$nombre]}'. Valores permitidos: " . implode(', ', $valores_permitidos) . ".";
                }
            }
        }

        if (!empty($errores)) {
            foreach ($errores as $error) {
                self::_error($error);
            }
            return null;
        }

        return [
            'posicionales' => $posicionales,
            'banderas'     => $banderas,
            'opciones'     => $opciones,
        ];
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
     * @var array<string, array{instancia: Comunicador, clase: string}>
     */
    private static array $comunicadores = [];

    /**
     * Registra un nuevo comunicador a partir de una clase que implementa la interfaz Comunicador.
     *
     * @param string $clase Nombre cualificado de la clase del comunicador.
     * @return bool `true` si se registró correctamente.
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
     * Registra un nuevo comunicador a partir de una instancia.
     *
     * @param Comunicador $comunicador Instancia del comunicador.
     * @return bool `true` si se registró correctamente.
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
     * Obtiene la instancia única de un comunicador por su nombre.
     *
     * Si se invoca sin argumentos (o con el valor especial `'predeterminado'`),
     * devuelve automáticamente el comunicador de salida estándar correspondiente
     * al entorno actual:
     * - En **consola** → `consola`
     * - En **navegador** → `html`
     *
     * @param string $nombre Nombre del comunicador.
     * @return Comunicador|null La instancia del comunicador, o `null` si no está disponible.
     * @since 1.3.3
     * @version 1.4.7 (nombres actualizados a 'consola' y 'html')
     */
    public static function comunicador(string $nombre = 'predeterminado'): ?Comunicador
    {
        if ($nombre === 'predeterminado') {
            $nombre = Entorno::es_consola() ? 'consola' : 'html';
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
     * @param string $nombre Nombre del comunicador.
     * @return bool
     * @since 1.3.3
     */
    public static function tiene_permiso_comunicador(string $nombre): bool
    {
        return true;
    }

    /**
     * Registra los comandos genéricos de comunicación.
     *
     * @return void
     * @since 1.3.3
     * @version 1.4.7 (traducción delegada al Tálamo, eliminación usa método propio)
     */
    private static function registrar_comandos_comunicacion(): void
    {
        // ─── comunicación:leer ───────────────────────────────
        self::registrar_comando('comunicacion:leer', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $fuente  = $args[1] ?? '';
            if (!$medio) {
                self::_error("Falta el medio para 'comunicacion:leer'.");
                return null;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;

            return $comunicador->solicitar($fuente);
        }, null, false);

        // ─── comunicación:escribir ────────────────────────────
        self::registrar_comando('comunicacion:escribir', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $senal   = $args[1] ?? null;   // Debe ser una Senal
            $destino = $args[2] ?? '';
            if (!$medio || !$senal) {
                self::_error("Faltan medio o señal para 'comunicacion:escribir'.");
                return false;
            }
            if (!($senal instanceof Senal)) {
                self::_error("El parámetro debe ser una Senal.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;

            $comunicador->enviar($destino, $senal);
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
                $respuesta = trim(fgets(STDIN));
                return Talamo::obtener()->traducir_entrada($respuesta);
            }
            return $comunicador->solicitar($mensaje);
        }, null, false);

        // ─── comunicación:eliminar ────────────────────────────
        self::registrar_comando('comunicacion:eliminar', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $destino = $args[1] ?? '';
            if (!$medio) {
                self::_error("Falta el medio para 'comunicacion:eliminar'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;

            // Si el comunicador tiene un método 'eliminar', lo usamos
            if (method_exists($comunicador, 'eliminar')) {
                $comunicador->eliminar($destino);
                return true;
            }

            self::_error("El comunicador '$medio' no soporta eliminación.");
            return false;
        }, null, false);

        // ─── comunicación:listar ──────────────────────────────
        self::registrar_comando('comunicacion:listar', function(string $token, array $args) {
            $medio      = $args[0] ?? null;
            $directorio = $args[1] ?? '.';
            if (!$medio) {
                self::_error("Falta el medio para 'comunicacion:listar'.");
                return null;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;

            // Si tiene método 'listar' propio, lo usamos
            if (method_exists($comunicador, 'listar')) {
                return $comunicador->listar($directorio);
            }

            self::_error("El comunicador '$medio' no soporta listado.");
            return null;
        }, null, false);

        // ─── comunicación:escuchar ─────────────────────────────
        self::registrar_comando('comunicacion:escuchar', function(string $token, array $args) {
            $medio = $args[0] ?? null;
            if (!$medio) {
                self::_error("Falta el medio para 'comunicacion:escuchar'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;

            $comunicador->escuchar(function($senal) use ($medio) {
                $salida = self::comunicador();
                if ($salida) {
                    $salida->enviar('', $senal);
                }
            });
            return true;
        }, null, false);
    }

    /**
     * Escribe un mensaje en la salida estándar configurada según el entorno.
     *
     * Convierte el texto a {@link Senal} mediante el {@link Talamo} y lo envía
     * a través del comunicador predeterminado.
     *
     * @param string $mensaje Texto a escribir en la salida estándar.
     * @return void
     * @since 1.3.3
     * @version 1.4.7 (usa Talamo para convertir el string)
     */
    public static function escribir_salida(string $mensaje): void
    {
        $salida = self::comunicador();
        if ($salida !== null) {
            $senal = Talamo::obtener()->traducir_entrada($mensaje);
            $salida->enviar('', $senal);
        }
    }
    // ═══════════════════════════════════════════════════════════
    // RELOJ ASTRONÓMICO Y UBICACIÓN (v1.3.6)
    // ═══════════════════════════════════════════════════════════

    /**
     * Instancia del reloj astronómico asociada al controlador.
     *
     * Se inicializa en {@link inicializar} con las coordenadas obtenidas
     * de {@link \Iteradores\Configuracion\Entorno::obtener_coordenadas}.
     *
     * @var RelojAstronomico|null
     * @since 1.3.6
     */
    private static ?RelojAstronomico $_reloj = null;

    /**
     * Devuelve el vector gravitacional correspondiente al instante actual
     * (o al timestamp proporcionado) según la ubicación del controlador.
     *
     * @param int|null $timestamp Timestamp Unix. Si es null, se usa el instante actual.
     * @return array{x: float, y: float, z: float}|null Vector unitario, o null si el reloj no está inicializado.
     * @since 1.3.6
     */
    public static function vector_gravitacional_actual(?int $timestamp = null): ?array
    {
        if (self::$_reloj === null) {
            self::_alerta('Reloj astronómico no inicializado.');
            return null;
        }
        return self::$_reloj->vector($timestamp);
    }

    /**
     * Actualiza manualmente la ubicación del controlador y del reloj astronómico.
     *
     * @param float $latitud  Nueva latitud.
     * @param float $longitud Nueva longitud.
     * @return void
     * @since 1.3.6
     */
    public static function _actualizar_ubicacion(float $latitud, float $longitud): void
    {
        if (self::$_reloj !== null) {
            self::$_reloj->_ubicacion($latitud, $longitud);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MOTOR DE EJECUCIÓN (v1.3.7)
    // ═══════════════════════════════════════════════════════════

    /**
     * Estados posibles del motor.
     *
     * @var string
     * @since 1.3.7
     */
    public const MOTOR_DETENIDO = 'detenido';
    public const MOTOR_ACTIVO = 'activo';
    public const MOTOR_PAUSADO = 'pausado';
    public const MOTOR_PAUSA_URGENTE = 'pausa_urgente';

    /**
     * Estado actual del motor.
     *
     * @var string
     * @since 1.3.7
     */
    private static string $estado_motor = self::MOTOR_DETENIDO;

    /**
     * Identificador de la fase que será atendida en el próximo ciclo.
     *
     * @var string|null
     * @since 1.3.7
     * @version 1.3.8 (cambiado a string)
     */
    private static ?string $indice_fase_actual = null;

    /**
     * Razón de la última pausa urgente.
     *
     * @var string
     * @since 1.3.7
     */
    private static string $razon_pausa_urgente = '';

    /**
     * Marca de tiempo (Unix) en la que se inició la pausa urgente.
     *
     * @var int|null
     * @since 1.3.7
     */
    private static ?int $pausa_urgente_inicio = null;

    /**
     * Inicia el motor de ejecución.
     *
     * Si ya está activo o pausado, no hace nada.
     * Arranca el bucle principal que se ejecutará periódicamente
     * según {@link \Iteradores\Configuracion\Conf::MOTOR_INTERVALO_MS}.
     *
     * @return void
     * @since 1.3.7
     */
    public static function iniciar_motor(): void
    {
        if (in_array(self::$estado_motor, [self::MOTOR_ACTIVO, self::MOTOR_PAUSADO], true)) {
            return;
        }

        self::$estado_motor = self::MOTOR_ACTIVO;
        self::$indice_fase_actual = 0;

        $ciclos = 0;
        $max_ciclos = Conf::MOTOR_MAX_CICLOS;

        while (self::$estado_motor === self::MOTOR_ACTIVO) {
            self::bucle_motor();
            usleep(Conf::MOTOR_INTERVALO_MS * 1000);
            $ciclos++;
            if ($max_ciclos > 0 && $ciclos >= $max_ciclos) {
                self::$estado_motor = self::MOTOR_DETENIDO;
                break;
            }
        }
    }


    /**
     * Pausa el motor por solicitud explícita.
     *
     * El estado se conserva para poder reanudar después.
     * Se debería persistir la superestructura aquí para evitar pérdidas
     * en caso de que el proceso sea destruido.
     *
     * @return void
     * @since 1.3.7
     */
    public static function pausar_motor(): void
    {
        if (self::$estado_motor !== self::MOTOR_ACTIVO) {
            return;
        }

        self::$estado_motor = self::MOTOR_PAUSADO;
        // TODO: persistir superestructura cuando esté operativo
        // PerdurarSuperestructura::guardar('motor');
    }

    /**
     * Reanuda el motor tras una pausa explícita.
     *
     * @return void
     * @since 1.3.7
     */
    public static function reanudar_motor(): void
    {
        if (self::$estado_motor !== self::MOTOR_PAUSADO) {
            return;
        }

        // TODO: cargar superestructura para restaurar estado
        // PerdurarSuperestructura::cargar('motor');

        self::$estado_motor = self::MOTOR_ACTIVO;
    }

    /**
     * Detiene el motor completamente.
     *
     * Limpia el estado interno. Para volver a usar el motor,
     * es necesario llamar a {@link iniciar_motor}.
     *
     * @return void
     * @since 1.3.7
     */
    public static function detener_motor(): void
    {
        // TODO: persistir superestructura antes de detener
        self::$estado_motor = self::MOTOR_DETENIDO;
        self::$indice_fase_actual = 0;
    }

    /**
     * Pausa el motor de forma urgente, generalmente porque un comando
     * requiere intervención del usuario o una respuesta externa.
     *
     * Se programa una reanudación automática tras
     * {@link \Iteradores\Configuracion\Conf::MOTOR_PAUSA_URGENTE_TIMEOUT_S}
     * segundos si la pausa no se levanta antes.
     *
     * @param string $razon Motivo de la pausa (para depuración).
     * @return void
     * @since 1.3.7
     */
    public static function pausar_urgente(string $razon = ''): void
    {
        if (self::$estado_motor !== self::MOTOR_ACTIVO) {
            return;
        }

        self::$estado_motor = self::MOTOR_PAUSA_URGENTE;
        self::$razon_pausa_urgente = $razon;
        self::$pausa_urgente_inicio = time();

        // TODO: persistir superestructura para evitar pérdida de estado
    }

    /**
     * Ejecuta una rodaja de trabajo del motor.
     *
     * Atiende a la fase actual (según el péndulo) y ejecuta
     * hasta {@link \Iteradores\Configuracion\Conf::MOTOR_QUANTUM} comandos.
     * Si la fase se queda sin comandos, el péndulo avanza inmediatamente.
     *
     * @return void
     * @since 1.3.7
     * @version 1.3.9
     */
    private static function bucle_motor(): void
    {
        // Verificar timeout de pausa urgente
        if (self::$estado_motor === self::MOTOR_PAUSA_URGENTE) {
            $transcurrido = time() - (self::$pausa_urgente_inicio ?? time());
            if ($transcurrido >= Conf::MOTOR_PAUSA_URGENTE_TIMEOUT_S) {
                self::_alerta("Timeout de pausa urgente alcanzado ({$transcurrido}s). Reanudando.");
                self::$estado_motor = self::MOTOR_ACTIVO;
                self::$razon_pausa_urgente = '';
                self::$pausa_urgente_inicio = null;
            } else {
                return;
            }
        }

        if (self::$estado_motor !== self::MOTOR_ACTIVO) {
            return;
        }

        // Obtener la fase actual del péndulo
        $fase = self::$indice_fase_actual;
        if ($fase === null) {
            $fase = self::pendulo(null);
            if ($fase === null) {
                // No hay fases activas: detener el motor automáticamente
                self::$estado_motor = self::MOTOR_DETENIDO;
                return;
            }
        }

        $quantum = Conf::MOTOR_QUANTUM;

        for ($i = 0; $i < $quantum; $i++) {
            $entrada = self::siguiente_comando_en_fase($fase);
            if ($entrada === null) {
                break;
            }
            [$nombre_comando, $args] = $entrada;
            self::ejecutar_comando($nombre_comando, ...$args);
        }

        // Si la fase se vació, determinar la siguiente acción
        if (self::siguiente_comando_en_fase($fase) === null) {
            // Si estábamos en modo dominio, buscar otro dominio o volver a global
            if (self::$dominio_actual !== null) {
                $siguiente = self::siguiente_dominio(self::$dominio_actual);
                if ($siguiente !== null) {
                    self::activar_dominio($siguiente);
                    $fase = self::pendulo(null);
                } else {
                    self::desactivar_dominio();
                    $fase = self::pendulo(null);
                }
            }
        }

        // Avanzar el péndulo para el próximo ciclo (si aún hay fases)
        if (!empty(self::$colas_comandos)) {
            self::$indice_fase_actual = self::pendulo($fase);
        } else {
            self::$indice_fase_actual = null;
            // Si no hay más fases, detener el motor
            self::$estado_motor = self::MOTOR_DETENIDO;
        }
    }

    /**
     * Deshace el último comando ejecutado por el motor.
     *
     * Solo puede ejecutarse cuando el motor está en estado DETENIDO.
     * Delega en {@link \Iteradores\Controlador\Controlador::deshacer_ultimo()}.
     *
     * @return mixed Resultado de la reversa, o null si no se puede deshacer.
     * @since 1.3.9
     */
    public static function deshacer_motor(): mixed
    {
        if (self::$estado_motor !== self::MOTOR_DETENIDO) {
            self::_alerta('El motor debe estar DETENIDO para deshacer.');
            return null;
        }
        return self::deshacer_ultimo();
    }
    // ═══════════════════════════════════════════════════════════
    // COLAS DE COMANDOS POR FASE (v1.3.8)
    // ═══════════════════════════════════════════════════════════

    /**
     * Colas de comandos pendientes, indexadas por identificador de fase.
     *
     * Cada clave es un string que identifica la fase (ej. "0", "html:0").
     * Cada valor es un array indexado de arrays [nombre_comando, args].
     *
     * Las fases se crean bajo demanda al encolar el primer comando.
     *
     * @var array<string, array<int, array{string, array}>>
     * @since 1.3.8
     * @version 1.3.9 (cambiada la estructura interna)
     */
    private static array $colas_comandos = [];

    /**
     * Añade un comando a la cola de una fase.
     *
     * Si la fase no existe, se crea automáticamente.
     *
     * @param string $fase           Identificador de la fase (ej. "0", "html:entrada:0").
     * @param string $nombre_comando Nombre del comando registrado.
     * @param mixed  ...$args        Argumentos para el comando.
     * @return void
     * 
     * @since 1.3.8
     * @version 1.3.8
     */
    public static function encolar_comando_en_fase(string $fase, string $nombre_comando, ...$args): void
    {
        if (!isset(self::$colas_comandos[$fase])) {
            self::$colas_comandos[$fase] = [];
        }
        self::$colas_comandos[$fase][] = [$nombre_comando, $args];
    }

    /**
     * Obtiene el siguiente comando pendiente en una fase (y lo retira de la cola).
     *
     * @param string $fase Identificador de la fase.
     * @return array{string, array}|null Tupla [nombre_comando, args] o null.
     * @since 1.3.8
     * @version 1.3.9 (devuelve array en lugar de callable)
     */
    private static function siguiente_comando_en_fase(string $fase): ?array
    {
        if (empty(self::$colas_comandos[$fase])) {
            unset(self::$colas_comandos[$fase]);
            return null;
        }
        return array_shift(self::$colas_comandos[$fase]);
    }

    /**
     * Devuelve el siguiente dominio con comandos pendientes (excluyendo el tálamo).
     *
     * @param string|null $dominio_actual Dominio actual.
     * @return string|null Siguiente dominio, o null si no hay.
     * @since 1.3.9
     */
    private static function siguiente_dominio(?string $dominio_actual): ?string
    {
        $dominios = [];
        foreach (self::$colas_comandos as $fase => $cola) {
            if (empty($cola)) continue;
            $partes = explode(':', $fase, 2);
            $dom = count($partes) > 1 ? $partes[0] : 'talamo';
            if ($dom !== 'talamo' && !in_array($dom, $dominios, true)) {
                $dominios[] = $dom;
            }
        }

        if (empty($dominios)) {
            return null;
        }

        sort($dominios, SORT_STRING);

        if ($dominio_actual === null || !in_array($dominio_actual, $dominios, true)) {
            return $dominios[0];
        }

        $indice = array_search($dominio_actual, $dominios, true);
        return $dominios[($indice + 1) % count($dominios)];
    }
    /**
     * Devuelve la siguiente fase que debe ser atendida (péndulo).
     *
     * Ahora itera sobre las fases activas (las que tienen cola no vacía).
     * Si no hay ninguna, retorna null.
     *
     * @param string|null $fase_actual Fase que acaba de ser atendida, o null.
     * @return string|null Siguiente fase, o null si no hay.
     * @since 1.3.7
     * @version 1.3.9
     */
    private static function pendulo(?string $fase_actual): ?string
    {
        // Obtener fases con comandos pendientes, aplicando filtro de dominio si es necesario
        $fases = [];
        foreach (self::$colas_comandos as $fase => $cola) {
            if (empty($cola)) continue;
            // Si hay un dominio activo, solo incluir fases que empiecen por ese prefijo
            if (self::$dominio_actual !== null && !str_starts_with($fase, self::$dominio_actual . ':')) {
                continue;
            }
            $fases[] = $fase;
        }

        if (empty($fases)) {
            return null;
        }

        sort($fases, SORT_STRING);

        if ($fase_actual === null || !in_array($fase_actual, $fases, true)) {
            return $fases[0];
        }

        $indice = array_search($fase_actual, $fases, true);
        return $fases[($indice + 1) % count($fases)];
    }

    /**
     * Registra los comandos genéricos de dominio.
     *
     * @return void
     * @since 1.3.3
     * @version 1.4.7 (adaptados a Senal y Talamo)
     */
    private static function registrar_comandos_dominio(): void
    {
        // ─── dominio:leer_byte ──────────────────────────────
        self::registrar_comando('dominio:leer_byte', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $fuente  = $args[1] ?? '';
            if (!$medio) {
                self::_error("Falta el parámetro 'medio' para 'dominio:leer_byte'.");
                return null;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return null;

            // El comunicador devuelve una Senal (posiblemente con un solo byte)
            return $comunicador->solicitar($fuente);
        }, null, true);

        // ─── dominio:escribir_byte ───────────────────────────
        self::registrar_comando('dominio:escribir_byte', function(string $token, array $args) {
            $medio   = $args[0] ?? null;
            $byte    = $args[1] ?? null;
            $destino = $args[2] ?? '';
            if (!$medio || $byte === null) {
                self::_error("Faltan parámetros 'medio' o 'byte' para 'dominio:escribir_byte'.");
                return false;
            }
            $comunicador = self::comunicador($medio);
            if (!$comunicador) return false;

            // Convertir el byte en una Senal de una sola matriz
            $senal = Talamo::obtener()->traducir_entrada(chr($byte));
            $comunicador->enviar($destino, $senal);
            return true;
        }, null, true);
    }

    // ══════════════════════════════════════════════════════
    // INTERFAZ DOMINIOS
    // ══════════════════════════════════════════════════════
    
    /**
     * Dominio actualmente activo (null = modo global).
     *
     * @var string|null
     * @since 1.3.9
     */
    private static ?string $dominio_actual = null;

    /**
     * Activa el modo exclusivo para un dominio.
     *
     * Mientras un dominio está activo, el péndulo solo itera sobre fases
     * cuyo nombre comience por el prefijo del dominio (ej. 'html:').
     *
     * @param string $dominio Nombre del dominio (sin ':').
     * @return void
     * @since 1.3.9
     */
    public static function activar_dominio(string $dominio): void
    {
        self::$dominio_actual = $dominio;
        self::$indice_fase_actual = null;
    }

    /**
     * Desactiva el modo exclusivo de dominio.
     *
     * El péndulo vuelve a considerar todas las fases activas.
     *
     * @return void
     * @since 1.3.9
     */
    public static function desactivar_dominio(): void
    {
        self::$dominio_actual = null;
        self::$indice_fase_actual = null;
    }

    // ═══════════════════════════════════════════
    // V 1.4.6 – PROCESADORES DE DOMINIO
    // ═══════════════════════════════════════════


    /**
     * Procesadores de dominio (entrada/salida), indexados por prefijo.
     *
     * @var array<string, ProcesadorDeDominio>
     * @since 1.4.6
     */
    private static array $procesadores = [];
    
    /**
     * Obtiene (o crea) el procesador para un medio y dirección.
     *
     * @param string $medio     Nombre del medio (ej. 'Archivo', 'Talamo').
     * @param string $direccion 'entrada' o 'salida'.
     * @return ProcesadorDeDominio
     * @since 1.4.6
     */
    public static function procesador(string $medio, string $direccion): ProcesadorDeDominio
    {
        $clave = "{$medio}:{$direccion}";
        if (!isset(self::$procesadores[$clave])) {
            self::$procesadores[$clave] = new ProcesadorDeDominio($medio, $direccion);
            self::$procesadores[$clave]::recibir_token(self::$token);
        }
        return self::$procesadores[$clave];
    }

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