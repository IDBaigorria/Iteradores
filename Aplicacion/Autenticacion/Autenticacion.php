<?php
/**
 * Autenticación de usuarios por código de acceso.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.68
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

/**
 * Autentica a un usuario mediante su nombre y contraseña.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @param string $contrasena Contraseña en texto plano.
 * @return array|null Datos del usuario autenticado o null.
 */
function autenticar_por_usuario(string $nombre_usuario, string $contrasena): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        password_verify($contrasena, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        return null;
    }

    $nodo_usuario = $raiz_usuarios->adyacente($nombre_usuario);
    if (!$nodo_usuario) {
        password_verify($contrasena, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        return null;
    }

    $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
    if (!$nodo_contrasena) {
        // El usuario no tiene contraseña asignada.
        password_verify($contrasena, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        return null;
    }

    if (!password_verify($contrasena, $nodo_contrasena->dato())) {
        return null;
    }

    $nodo_nivel = $nodo_usuario->adyacente('nivel');
    $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');

    return [
        'nombre_usuario' => $nombre_usuario,
        'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,
        'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
    ];
}
