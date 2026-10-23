<?php
/**
 * Registro de comandos de reparación de la app.
 *
 * Los comandos de la app se registran al vuelo desde
 * index.php (el Controlador ya está inicializado).
 * Mismo patrón que registrar_comandos_migraciones().
 *
 * @package   Iteradores
 * @since     1.5piloto.78z
 * @version   1.5piloto.81v
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Registra los comandos de reparación de la app.
 *
 * @return void
 */
function registrar_comandos_reparacion(): void {
    Controlador::registrar_comando(
        'app:reparar_arboles',
        function (string $token, array $args) {
            require_once __DIR__ . '/RepararArboles.php';
            $res = reparar_arboles($token);
            if (!empty($res['cambios'])) {
                guardar_ambos(ConfiguracionApli::NOMBRE_APP);
            }
            return $res;
        },
        null,
        false // disponible en todos los entornos
    );

    Controlador::registrar_comando(
        'app:auditar_topologia',
        function (string $token, array $args) {
            require_once __DIR__ . '/Auditoria.php';
            return auditar_topologia($token);
        },
        null,
        false // disponible en todos los entornos
    );

    Controlador::registrar_comando(
        'app:reparar_cancelaciones_compartidos',
        function (string $token, array $args) {
            require_once __DIR__ . '/RepararCancelaciones.php';
            $res = reparar_cancelaciones_compartidos($token);
            if (!empty($res['cambios'])) {
                guardar_ambos(ConfiguracionApli::NOMBRE_APP);
            }
            return $res;
        },
        null,
        false // disponible en todos los entornos
    );

    Controlador::registrar_comando(
        'app:reparar_cancelaciones_arboles',
        function (string $token, array $args) {
            require_once __DIR__ . '/RepararCancelaciones.php';
            $res = reparar_cancelaciones_arboles($token);
            if (!empty($res['cambios'])) {
                guardar_ambos(ConfiguracionApli::NOMBRE_APP);
            }
            return $res;
        },
        null,
        false // disponible en todos los entornos
    );
}