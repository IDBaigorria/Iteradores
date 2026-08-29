<?php
/**
 * Lógica del panel de administración.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.3
 */

use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");

/**
 * Verifica el código de administrador definido en Configuracion.
 *
 * @param string $codigo Código ingresado.
 * @return bool Verdadero si coincide.
 */
function verificar_codigo_admin(string $codigo): bool {
    return $codigo === Conf::CODIGO_ADMIN;
}