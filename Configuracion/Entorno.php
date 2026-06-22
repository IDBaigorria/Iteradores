<?php
namespace Iteradores\Configuracion;

use Iteradores\Nucleo\Objeto;

//include_once(".\Nucleo\Objeto.php");

/**
 * Gestión del entorno de ejecución y conciencia geográfica.
 *
 * Centraliza la configuración del contexto en el que corre la aplicación:
 * modo de ejecución (desarrollo, pruebas, producción), tipo de salida
 * (consola o HTML), método de persistencia activo para la superestructura
 * (SQL, JSON, XML) y la ubicación geográfica del servidor o cliente.
 *
 * La ubicación se obtiene de forma automática utilizando la mejor fuente
 * disponible (detección por IP del cliente o coordenadas predefinidas).
 * En PHP no es posible detectar cambios de ubicación en tiempo real, por lo
 * que el método {@link escuchar_cambios} es un placeholder preparado para
 * una futura implementación mediante sondeo.
 *
 * Esta clase actúa como fuente única de verdad para todos los componentes
 * que necesiten adaptar su comportamiento al entorno actual.
 *
 * @author Ignacio David Baigorria
 * @version 1.3.6
 * @since 1.2.6
 * @package Iteradores\Configuracion
 */
class Entorno extends Objeto
{
    // ──────────────────────────────────────────────
    // Constantes de modo de ejecución
    // ──────────────────────────────────────────────
    /** @var string Entorno de desarrollo. */
    public const MODO_DESARROLLO  = 'desarrollo';
    /** @var string Entorno de pruebas. */
    public const MODO_PRUEBAS     = 'pruebas';
    /** @var string Entorno de producción. */
    public const MODO_PRODUCCION  = 'produccion';

    // ──────────────────────────────────────────────
    // Constantes de tipo de salida
    // ──────────────────────────────────────────────
    /** @var string Salida para consola / CLI. */
    public const SALIDA_CONSOLA = 'consola';
    /** @var string Salida para navegador (HTML). */
    public const SALIDA_HTML    = 'html';

    // ──────────────────────────────────────────────
    // Constantes de método de persistencia
    // ──────────────────────────────────────────────
    /** @var string Persistencia en base de datos SQL. */
    public const PERSISTENCIA_SQL  = 'sql';
    /** @var string Persistencia en archivos JSON. */
    public const PERSISTENCIA_JSON = 'json';
    /** @var string Persistencia en archivos XML. */
    public const PERSISTENCIA_XML  = 'xml';

    // ──────────────────────────────────────────────
    // Propiedades estáticas privadas
    // ──────────────────────────────────────────────
    /** @var string Modo de ejecución actual. */
    private static string $modo = self::MODO_DESARROLLO;

    /** @var string Tipo de salida actual. */
    private static string $salida = self::SALIDA_HTML;

    /** @var string Método de persistencia activo. */
    private static string $persistencia = self::PERSISTENCIA_SQL;

    // ══════════════════════════════════════════════
    // MODO DE EJECUCIÓN
    // ══════════════════════════════════════════════

    /**
     * Define el modo de ejecución de la aplicación.
     *
     * Solo se aceptan los valores definidos en las constantes `MODO_*`.
     * Se recomienda llamar a este método al inicio del bootstrap, basándose
     * en una variable de entorno del servidor.
     *
     * @param string $modo Nombre del modo (desarrollo, pruebas, produccion).
     * @return bool `true` si se estableció correctamente, `false` en caso contrario.
     *
     * @example
     * Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
     */
    public static function establecer_modo(string $modo): bool
    {
        $modo = strtolower(trim($modo));
        if (in_array($modo, [self::MODO_DESARROLLO, self::MODO_PRUEBAS, self::MODO_PRODUCCION], true)) {
            self::$modo = $modo;
            return true;
        }
        self::_error("Modo de ejecución inválido: '$modo'. Se mantiene el anterior.");
        return false;
    }

    /**
     * Devuelve el modo de ejecución actual.
     *
     * @return string 'desarrollo', 'pruebas' o 'produccion'.
     */
    public static function modo(): string
    {
        return self::$modo;
    }

    /**
     * Verifica si el modo actual es desarrollo.
     *
     * @return bool
     */
    public static function es_desarrollo(): bool
    {
        return self::$modo === self::MODO_DESARROLLO;
    }

    /**
     * Verifica si el modo actual es pruebas.
     *
     * @return bool
     */
    public static function es_pruebas(): bool
    {
        return self::$modo === self::MODO_PRUEBAS;
    }

    /**
     * Verifica si el modo actual es producción.
     *
     * @return bool
     */
    public static function es_produccion(): bool
    {
        return self::$modo === self::MODO_PRODUCCION;
    }

    /**
     * Indica si el modo actual permite ejecutar pruebas o funciones de depuración.
     *
     * @return bool `true` si NO es producción.
     */
    public static function permite_pruebas(): bool
    {
        return self::$modo !== self::MODO_PRODUCCION;
    }

    // ══════════════════════════════════════════════
    // TIPO DE SALIDA
    // ══════════════════════════════════════════════

    /**
     * Define el tipo de salida esperado para los métodos de impresión o logging.
     *
     * @param string $tipo Debe ser `Entorno::SALIDA_CONSOLA` o `Entorno::SALIDA_HTML`.
     * @return bool `true` si se asignó correctamente.
     */
    public static function establecer_salida(string $tipo): bool
    {
        $tipo = strtolower(trim($tipo));
        if (in_array($tipo, [self::SALIDA_CONSOLA, self::SALIDA_HTML], true)) {
            self::$salida = $tipo;
            return true;
        }
        self::_error("Tipo de salida inválido: '$tipo'. Debe ser 'consola' o 'html'.");
        return false;
    }

    /**
     * Obtiene el tipo de salida configurado.
     *
     * @return string 'consola' o 'html'.
     */
    public static function salida(): string
    {
        return self::$salida;
    }

    /**
     * Comprueba si la salida está configurada para consola.
     *
     * @return bool
     */
    public static function es_consola(): bool
    {
        return self::$salida === self::SALIDA_CONSOLA;
    }

    /**
     * Comprueba si la salida está configurada para HTML.
     *
     * @return bool
     */
    public static function es_html(): bool
    {
        return self::$salida === self::SALIDA_HTML;
    }

    /**
     * Devuelve la secuencia de escape ANSI para el código dado,
     * solo si la salida actual es una terminal interactiva.
     *
     * En entornos no interactivos (navegador, archivo) retorna cadena vacía,
     * evitando caracteres extraños.
     *
     * @param string $codigo Código ANSI (ej. '31' para rojo, '0' para reset).
     * @return string Secuencia "\033[{$codigo}m" o ''.
     *
     * @example
     * echo Entorno::color_ansi('31') . 'Texto rojo' . Entorno::color_ansi('0');
     */
    public static function color_ansi(string $codigo): string
    {
        if (!defined('STDOUT') || !stream_isatty(STDOUT)) {
            return '';
        }
        return "\033[{$codigo}m";
    }

    // ══════════════════════════════════════════════
    // MÉTODO DE PERSISTENCIA
    // ══════════════════════════════════════════════

    /**
     * Establece el método de persistencia activo para la superestructura.
     *
     * El valor debe ser uno de los definidos en las constantes `PERSISTENCIA_*`.
     * Otros componentes (como el Controlador) consultan este valor para decidir
     * cómo guardar o cargar los datos.
     *
     * @param string $metodo Método de persistencia (sql, json, xml).
     * @return bool `true` si se asignó correctamente.
     */
    public static function establecer_persistencia(string $metodo): bool
    {
        $metodo = strtolower(trim($metodo));
        if (in_array($metodo, [self::PERSISTENCIA_SQL, self::PERSISTENCIA_JSON, self::PERSISTENCIA_XML], true)) {
            self::$persistencia = $metodo;
            return true;
        }
        self::_error("Método de persistencia inválido: '$metodo'. Use 'sql', 'json' o 'xml'.");
        return false;
    }

    /**
     * Devuelve el método de persistencia activo.
     *
     * @return string 'sql', 'json' o 'xml'.
     */
    public static function persistencia(): string
    {
        return self::$persistencia;
    }

    /**
     * Verifica si el método de persistencia es SQL.
     *
     * @return bool
     */
    public static function es_persistencia_sql(): bool
    {
        return self::$persistencia === self::PERSISTENCIA_SQL;
    }

    /**
     * Verifica si el método de persistencia es JSON.
     *
     * @return bool
     */
    public static function es_persistencia_json(): bool
    {
        return self::$persistencia === self::PERSISTENCIA_JSON;
    }

    /**
     * Verifica si el método de persistencia es XML.
     *
     * @return bool
     */
    public static function es_persistencia_xml(): bool
    {
        return self::$persistencia === self::PERSISTENCIA_XML;
    }

    // ═══════════════════════════════════════════════════════════
    // UBICACIÓN GEOGRÁFICA (v1.3.6)
    // ═══════════════════════════════════════════════════════════

    /**
     * Caché estático de coordenadas para evitar múltiples consultas externas
     * durante la misma ejecución.
     *
     * @var array{latitud: float, longitud: float}|null
     * @since 1.3.6
     */
    private static ?array $_coordenadas_cacheadas = null;

    /**
     * Obtiene las coordenadas geográficas actuales utilizando la mejor
     * fuente disponible.
     *
     * Orden de prioridad:
     * 1. Coordenadas ya almacenadas en sesión (si existe y está activa).
     * 2. Detección por IP del cliente mediante servicio externo configurable.
     * 3. Coordenadas predefinidas en {@link \Iteradores\Configuracion\Conf::LATITUD_PREDETERMINADA}.
     *
     * El resultado se cachea en la propiedad {@link $_coordenadas_cacheadas}
     * para evitar múltiples consultas externas durante la misma ejecución.
     *
     * @return array{latitud: float, longitud: float}
     * @since 1.3.6
     */
    public static function coordenadas(): array
    {
        // Si ya las tenemos cacheadas en esta ejecución, las devolvemos
        if (self::$_coordenadas_cacheadas !== null) {
            return self::$_coordenadas_cacheadas;
        }

        $clave_sesion = Conf::PREFIJO_SESSION . 'coordenadas';

        // Intento 1: Recuperar de sesión (si existe)
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$clave_sesion])) {
            self::$_coordenadas_cacheadas = $_SESSION[$clave_sesion];
            return self::$_coordenadas_cacheadas;
        }

        // Intento 2: Detección por IP mediante servicio externo configurable
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // Evitar consultar IPs privadas
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = '';
        }
        if ($ip) {
            $url = Conf::GEOLOCALIZACION_URL . $ip;
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['lat']) && isset($data['lon'])) {
                    $coords = [
                        'latitud'  => (float) $data['lat'],
                        'longitud' => (float) $data['lon'],
                    ];
                    // Guardar en sesión para futuras peticiones
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        $_SESSION[$clave_sesion] = $coords;
                    }
                    self::$_coordenadas_cacheadas = $coords;
                    return $coords;
                }
            }
        }

        // Intento 3: Coordenadas predefinidas en Conf
        $coords = [
            'latitud'  => Conf::LATITUD_PREDETERMINADA,
            'longitud' => Conf::LONGITUD_PREDETERMINADA,
        ];
        self::$_coordenadas_cacheadas = $coords;
        return $coords;
    }
    /**
     * Registra un callback para ser notificado cuando cambie la ubicación.
     *
     * **Importante:** En PHP no existe un mecanismo nativo para detectar
     * cambios de ubicación en tiempo real. La ubicación se determina una
     * vez por cada petición HTTP. Este método existe por coherencia con la
     * interfaz de JavaScript y queda como un placeholder preparado para una
     * futura implementación (por ejemplo, mediante sondeo periódico en
     * aplicaciones de larga duración).
     *
     * @param callable $callback Función que recibiría (float $latitud, float $longitud).
     * @return void
     * @since 1.3.6
     */
    public static function escuchar_cambios(callable $callback): void
    {
        // En PHP la ubicación se determina por petición HTTP.
        // No podemos escuchar cambios en tiempo real.
        // Este método es un placeholder por coherencia con la API de JS.
    }
}