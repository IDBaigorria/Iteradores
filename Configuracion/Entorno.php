<?php
namespace Iteradores\Configuracion;

use Iteradores\Nucleo\Objeto;

/**
 * Gestión del entorno de ejecución.
 *
 * Permite definir y consultar en qué etapa se encuentra la aplicación:
 * desarrollo, pruebas o producción. Esto condiciona comportamientos
 * sensibles (como la disponibilidad de métodos de prueba).
 *
 * @author Ignacio David Baigorria
 * @version 1.0.0
 * @since 1.2.6
 * @package Iteradores\Configuracion
 */
class Entorno extends Objeto
{
    /**
     * Entorno de desarrollo.
     * @var string
     */
    private const DESARROLLO = 'desarrollo';

    /**
     * Entorno de pruebas (testing).
     * @var string
     */
    private const PRUEBAS = 'pruebas';

    /**
     * Entorno de producción.
     * @var string
     */
    private const PRODUCCION = 'produccion';

    /**
     * Entorno actual.
     * @var string
     */
    private static $entorno = self::DESARROLLO; // por defecto, desarrollo

    /**
     * Establece el entorno actual.
     *
     * Solo se permiten los valores definidos en las constantes de la clase.
     * Se recomienda llamar a este método lo antes posible en el punto de entrada
     * de la aplicación (index.php, bootstrap, etc.), basándose en variables de
     * entorno del servidor o en un archivo de configuración.
     *
     * @param string $entorno Nombre del entorno (desarrollo, pruebas, produccion).
     * @return bool True si se estableció correctamente, false si el valor no es válido.
     *
     * @example
     * ```php
     * // En index.php o bootstrap:
     * $env = getenv('APP_ENV') ?: 'desarrollo';
     * Entorno::establecer($env);
     * ```
     */
    public static function establecer(string $entorno): bool
    {
        $entorno = strtolower(trim($entorno));
        if (in_array($entorno, [self::DESARROLLO, self::PRUEBAS, self::PRODUCCION], true)) {
            self::$entorno = $entorno;
            return true;
        }
        self::_error("Entorno inválido: '$entorno'. Usando el anterior.");
        return false;
    }

    /**
     * Obtiene el entorno actual.
     *
     * @return string
     */
    public static function actual(): string
    {
        return self::$entorno;
    }

    /**
     * Verifica si el entorno actual es desarrollo.
     *
     * @return bool
     */
    public static function es_desarrollo(): bool
    {
        return self::$entorno === self::DESARROLLO;
    }

    /**
     * Verifica si el entorno actual es pruebas.
     *
     * @return bool
     */
    public static function es_pruebas(): bool
    {
        return self::$entorno === self::PRUEBAS;
    }

    /**
     * Verifica si el entorno actual es producción.
     *
     * @return bool
     */
    public static function es_produccion(): bool
    {
        return self::$entorno === self::PRODUCCION;
    }

    /**
     * Verifica si el entorno actual permite funciones de depuración/prueba.
     *
     * Este método es útil para condicionar la ejecución de código sensible,
     * como el método Controlador::ejecutarPrueba().
     *
     * @return bool True si el entorno NO es producción (es decir, desarrollo o pruebas).
     */
    public static function permite_pruebas(): bool
    {
        return self::$entorno !== self::PRODUCCION;
    }
}

?>