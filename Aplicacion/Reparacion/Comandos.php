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
 * @version   1.5piloto.78z
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
}