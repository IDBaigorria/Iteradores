<?php
namespace Iteradores\Configuracion;

use Iteradores\Nucleo\Objeto;

include_once(".\Nucleo\Objeto.php");

/**
 * Gestión del entorno de ejecución.
 *
 * Centraliza la configuración del contexto en el que corre la aplicación,
 * incluyendo el modo de ejecución (desarrollo, pruebas, producción),
 * el tipo de salida esperado (consola o HTML) y el método de persistencia
 * activo para la superestructura (sql, json, xml, etc.).
 *
 * Esta clase actúa como fuente única de verdad para todos los componentes
 * que necesiten adaptar su comportamiento al entorno actual.
 *
 * @author Ignacio David Baigorria
 * @version 1.0.1
 * @since 1.2.6
 * @package Iteradores\Configuracion
 */
class Entorno
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
}