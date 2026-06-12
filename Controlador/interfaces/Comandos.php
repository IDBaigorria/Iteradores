<?php
namespace Iteradores\Controlador\Interfaces;
use Iteradores\Comandos\Comando; 

/**
 * Define la interfaz para la gestión de comandos.
 *
 * Un comando es una operación invocable a través del {@link Controlador},
 * que centraliza la ejecución de tareas del sistema. Esta interfaz permite
 * registrar, ejecutar y deshacer comandos, estableciendo las bases para
 * una arquitectura extensible basada en el patrón Command.
 *
 * La clase que implementa esta interfaz (actualmente {@link Controlador})
 * mantiene un mapa de comandos registrados, una pila de reversiones para
 * deshacer, y una función de verificación de permisos (placeholder) que
 * facilitará la integración futura con sistemas de autenticación.
 *
 * **Registro de comandos**
 *
 * Los comandos se registran mediante {@link registrar_comando()}. Por defecto,
 * el registro se permite en cualquier entorno, pero si el parámetro
 * `$solo_desarrollo` se establece en `true`, el comando solo se registrará
 * en modo desarrollo. Esto protege la aplicación en producción de exponer
 * herramientas internas accidentalmente.
 *
 * **Ejecución y deshacer**
 *
 * {@link ejecutar_comando()} invoca el manejador asociado al comando,
 * pasando el token de seguridad interno y los argumentos recibidos.
 * Si el comando definió una función de reversa, esta se almacena en una
 * pila global, permitiendo deshacer la última operación mediante
 * {@link deshacer_ultimo()}.
 *
 * **Guía para desarrolladores externos**
 *
 * 1. **Crear un comando nuevo**: Copie la plantilla de {@link Comando}
 *    y complete los métodos requeridos. La línea final de autoencolación
 *    hará que el comando esté disponible automáticamente al inicializar
 *    el sistema.
 * 2. **Registrar manualmente**: Si necesita registrar un comando sin
 *    archivo (por ejemplo, generado dinámicamente), use
 *    `Controlador::registrar_comando_desde_instancia()`.
 * 3. **Ejecutar**: `Controlador::ejecutar_comando('debug:imprimir', 'arg1')`.
 * 4. **Deshacer**: `Controlador::deshacer_ultimo()`.
 * 5. **Seguridad**: El token se inyecta automáticamente. Los comandos
 *    marcados con `$solo_desarrollo = true` se omiten en producción.
 *
 * **Permisos (futuro)**
 *
 * {@link tiene_permiso()} es un placeholder que siempre retorna `true`.
 * Su propósito es ser el punto de entrada para un sistema de autorización
 * (roles, ACL, etc.) sin modificar el resto de la interfaz.
 *
 * ---
 * 🔗 Relación con otros componentes:
 * - {@link Controlador} es la implementación actual.
 * - {@link Configuracion.Entorno} determina si el entorno permite el registro.
 * - {@link Configuracion.Conf} almacena las credenciales y parámetros que los comandos pueden necesitar.
 *
 * @package Iteradores\Controlador\Interfaces
 * @since 1.3.1
 * 
 */
interface Comandos
{
    /**
     * Registra un nuevo comando en el sistema.
     *
     * El registro se permite en todos los entornos de forma predeterminada.
     * Si se establece `$solo_desarrollo = true`, el comando solo se registrará
     * en modo desarrollo, evitando exponer herramientas de depuración en producción.
     *
     * @param string   $nombre           Nombre único del comando (ej. 'debug:imprimir').
     * @param callable $manejador        Función que ejecuta el comando.
     *                                   Recibe el token de seguridad y los argumentos como ...$args.
     * @param callable|null $reversa     Función opcional para deshacer el comando.
     *                                   Debe aceptar la misma firma que el manejador.
     * @param bool     $solo_desarrollo  Si `true`, el comando no se registra en producción.
     *                                   Por defecto `false` (se registra en todos los entornos).
     *
     * @return bool `true` si se registró correctamente, `false` si fue bloqueado por el entorno.
     *
     * @example
     * Controlador::registrar_comando('debug:imprimir', function($token) {
     *     if (!Entorno::permite_pruebas()) { ... }
     *     Objeto::imprimir_errores();
     * }, null, true);
     *
     * @since 1.3.1
     */
    public static function registrar_comando(
        string $nombre,
        callable $manejador,
        ?callable $reversa = null,
        bool $solo_desarrollo = false
    ): bool;

    /**
     * Registra un comando a partir de una clase que implementa {@link Comando}.
     *
     * La clase debe proporcionar los métodos estáticos `nombre()` y
     * `solo_desarrollo()`, así como los métodos de instancia `ejecutar()`
     * y `reversa()`.
     *
     * @param string $clase Nombre cualificado de la clase.
     * @return bool
     */
    public static function registrar_comando_desde_clase(string $clase): bool;

    /**
     * Registra un comando a partir de una instancia que implementa {@link Comando}.
     *
     * Extrae los metadatos (nombre, reversa, desarrollo) de la instancia,
     * construye los callables necesarios y los registra internamente.
     *
     * @param Comando $comando Instancia del comando.
     * @return bool
     */
    public static function registrar_comando_desde_instancia(Comando $comando): bool;

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
     * @return mixed El resultado del manejador, o `null` si el comando no existe,
     *               el entorno no permite la ejecución o se deniega el permiso.
     *
     * @example
     * Controlador::ejecutar_comando('debug:imprimir');
     *
     * @since 1.3.1
     */
    public static function ejecutar_comando(string $nombre, ...$args);

    /**
     * Verifica si el usuario actual tiene permiso para ejecutar el comando.
     *
     * Actualmente es un placeholder que retorna `true` para cualquier comando.
     * En el futuro se integrará con un sistema de roles/permisos.
     *
     * @param string $nombre_comando Nombre del comando.
     * @return bool
     * @since 1.3.1
     */
    public static function tiene_permiso(string $nombre_comando): bool;

    /**
     * Deshace el último comando ejecutado que tuviera reversa.
     *
     * Extrae de la pila de historial la reversa más reciente y la invoca.
     * Si no hay comandos para deshacer, emite una alerta y retorna `null`.
     *
     * @return mixed El resultado de la reversa, o `null` si no hay nada que deshacer.
     * @since 1.3.1
     */
    public static function deshacer_ultimo();
}