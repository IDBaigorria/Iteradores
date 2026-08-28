<?php
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
/**
 * Autenticación de usuarios por código de acceso.
 *
 * @package   Iteradores
 * @version   1.5piloto.1
 */

/**
 * Autentica a un usuario mediante su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario autenticado o null.
 */
function autenticar_por_codigo(string $codigo): ?array {
    // Primero intentar con usuario normal
    $usuario = buscar_usuario_por_codigo($codigo);
    if ($usuario) {
        // Crear sesión y añadir token a los datos del usuario
        $token = crear_sesion($usuario['nombre_usuario']);
        $usuario['token_sesion'] = $token;
        return $usuario;
    }

    // Si no existe, verificar si es el código maestro de admin
    if (verificar_codigo_admin($codigo)) {
        $usuario_admin = [
            'nombre_usuario' => Conf::NOMBRE_ADMIN,
            'nombre_real' => 'Administrador',
            'nivel' => 'admin',
        ];
        $token = crear_sesion($usuario_admin['nombre_usuario']);
        $usuario_admin['token_sesion'] = $token;
        return $usuario_admin;
    }

    return null;
}