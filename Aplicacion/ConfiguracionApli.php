<?php
/**
 * Configuración propia de la aplicación piloto.
 *
 * Contiene las constantes que describen a la aplicación concreta
 * (nombre, credenciales, rate limiting de autenticación, prefijo
 * de sesión). Hereda de {@link \Iteradores\Configuracion\Conf}
 * para tener acceso a los defaults del framework.
 *
 * Los valores de producción son los defaults. Los valores
 * reducidos para modo pruebas se eligen en tiempo de ejecución
 * vía `Entorno::es_pruebas()`.
 *
 * @author Ignacio David Baigorria
 * @version 1.5piloto.76e
 * @since   1.5piloto.76e
 */

require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Configuracion/Entorno.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;

class ConfiguracionApli extends Conf {

    // --- Sobre la aplicación ---

    /**
     * Nombre del grafo principal de la aplicación.
     *
     * @var string
     */
    public const NOMBRE_APP = "AdministradorDeViajes";

    /**
     * Versión de la aplicación piloto.
     *
     * @var string
     */
    public const VERSION_APP = "0.0.0";

    /**
     * Autor de la aplicación piloto.
     *
     * @var string
     */
    public const AUTOR_APP = "Ignacio David Baigorria";

    /**
     * Nombre del grafo de credenciales.
     *
     * Es un grafo separado del principal que contiene únicamente:
     * - los nodos usuarios con codigo_hash y contrasena
     * - los nodos de sesiones activas
     *
     * El nombre de usuario (clave del enlace en `usuarios`) es el
     * punto de unión entre ambos grafos.
     *
     * @var string
     * @since 1.5piloto.69
     */
    public const NOMBRE_APP_CREDENCIALES = self::NOMBRE_APP . "_credenciales";

    // --- Rate limiting de autenticación ---

    /**
     * Cantidad de intentos fallidos consecutivos antes de bloquear
     * a un usuario, en modo producción.
     *
     * @var int
     * @since 1.5piloto.71
     */
    public const INTENTOS_MAXIMOS_AUTENTICACION = 5;

    /**
     * Cantidad de intentos fallidos consecutivos antes de bloquear
     * a un usuario, en modo pruebas.
     *
     * Se deja igual al valor de producción para no cambiar la
     * semántica del rate limiting: solo cambia la duración del
     * bloqueo (ver {@link BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS}).
     *
     * @var int
     * @since 1.5piloto.76e
     */
    public const INTENTOS_MAXIMOS_AUTENTICACION_PRUEBAS = 5;

    /**
     * Duración del bloqueo por intentos fallidos, en segundos,
     * en modo producción. 900 segundos = 15 minutos.
     *
     * @var int
     * @since 1.5piloto.71
     */
    public const BLOQUEO_AUTENTICACION_SEGUNDOS = 900;

    /**
     * Duración del bloqueo por intentos fallidos, en segundos,
     * en modo pruebas. Reducido para que las pruebas del plugin
     * puedan esperar la expiración del bloqueo.
     *
     * @var int
     * @since 1.5piloto.76e
     */
    public const BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS = 2;

    /**
     * Hash bcrypt válido usado como señuelo para igualar tiempos
     * de respuesta. Cuando un usuario no existe, no tiene
     * credencial, o está bloqueado, se ejecuta password_verify()
     * contra este hash para que el tiempo total del intento sea
     * similar al de un login exitoso. Evita ataques de
     * temporización.
     *
     * @var string
     * @since 1.5piloto.71
     */
    public const HASH_DUMMY_AUTENTICACION = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * Nombre de usuario del administrador creado automáticamente
     * en el primer arranque.
     *
     * @var string
     */
    public const NOMBRE_ADMIN = 'Administrador';

    /**
     * Prefijo de sesión basado en el nombre de la app.
     *
     * @var string
     */
    public const PREFIJO_SESSION = self::NOMBRE_APP . "_";

    // --- Métodos conmutados por modo de ejecución ---

    /**
     * Devuelve el máximo de intentos fallidos según el modo actual.
     *
     * @return int
     * @since 1.5piloto.76e
     */
    public static function intentos_maximos_autenticacion(): int
    {
        return Entorno::es_pruebas()
            ? self::INTENTOS_MAXIMOS_AUTENTICACION_PRUEBAS
            : self::INTENTOS_MAXIMOS_AUTENTICACION;
    }

    /**
     * Devuelve la duración del bloqueo (segundos) según el modo
     * actual.
     *
     * @return int
     * @since 1.5piloto.76e
     */
    public static function bloqueo_autenticacion_segundos(): int
    {
        return Entorno::es_pruebas()
            ? self::BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS
            : self::BLOQUEO_AUTENTICACION_SEGUNDOS;
    }
}

?>