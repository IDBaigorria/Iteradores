<?php
/**
 * Autenticación de usuarios por código de acceso.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.3
 */

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");

/**
 * Autentica a un usuario mediante su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario autenticado o null.
 */
function autenticar_por_codigo(string $codigo): ?array {
    $usuario = buscar_usuario_por_codigo($codigo);
    if ($usuario) {
        $token = crear_sesion($usuario['nombre_usuario']);
        $usuario['token_sesion'] = $token;
        return $usuario;
    }

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

/**
 * Valida un token de sesión y devuelve el nombre de usuario y su nodo.
 *
 * @param string $token Token de sesión.
 * @return array|null Array con 'nombre_usuario' y 'nodo', o null si no es válido.
 */
function validar_token_sesion(string $token): ?array {
    $raiz = Nodo::nodo_por_id('sesiones');
    if (!$raiz) return null;

    $nodo_sesion = $raiz->adyacente($token);
    if (!$nodo_sesion) return null;

    $nodo_usuario = $nodo_sesion->adyacente('usuario');
    if (!$nodo_usuario) return null;

    $nombre_usuario = $nodo_usuario->dato();

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    $nodo_usuario_real = $raiz_usuarios->adyacente($nombre_usuario);
    if (!$nodo_usuario_real) return null;

    return [
        'nombre_usuario' => $nombre_usuario,
        'nodo' => $nodo_usuario_real,
    ];
}
