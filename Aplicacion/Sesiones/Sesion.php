<?php
/**
 * Gestión de sesiones de usuario.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.3
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");


/**
 * Elimina todas las sesiones activas de un usuario.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return void
 */
function eliminar_sesiones_de_usuario(string $nombre_usuario): void {
    $raiz = Nodo::nodo_por_id('sesiones');
    if (!$raiz) return;

    $sesiones = $raiz->adyacentes();
    if (!$sesiones) return;

    foreach ($sesiones as $token => $nodo_sesion) {
        $nodo_usuario = $nodo_sesion->adyacente('usuario');
        if ($nodo_usuario && $nodo_usuario->dato() === $nombre_usuario) {
            $raiz->eliminar_adyacente($token);
            Nodo::eliminar($nodo_sesion);
        }
    }
}

/**
 * Crea una nueva sesión para un usuario, eliminando antes las anteriores.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return string Token de sesión generado.
 */
function crear_sesion(string $nombre_usuario): string {
    eliminar_sesiones_de_usuario($nombre_usuario);

    $raiz_sesiones = Nodo::nodo_por_id('sesiones');
    if (!$raiz_sesiones) {
        $raiz_sesiones = Nodo::crear_con_id('sesiones');
    }

    $token = bin2hex(random_bytes(16));
    $nodo_sesion = Nodo::crear_con_dato('');
    $nodo_sesion->_adyacente_en(Nodo::crear_con_dato($nombre_usuario), 'usuario');
    $nodo_sesion->_adyacente_en(Nodo::crear_con_dato((string)time()), 'creado_en');

    $raiz_sesiones->_adyacente_en($nodo_sesion, $token);
    Controlador::guardar(Conf::NOMBRE_APP);

    return $token;
}

/**
 * Lista todas las sesiones activas.
 *
 * @return array Lista de sesiones.
 */
function listar_sesiones(): array {
    $raiz = Nodo::nodo_por_id('sesiones');
    if (!$raiz) return [];

    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return [];

    $sesiones = [];
    foreach ($adyacentes as $token => $nodo_sesion) {
        $nodo_usuario = $nodo_sesion->adyacente('usuario');
        $nodo_creado = $nodo_sesion->adyacente('creado_en');
        $timestamp = $nodo_creado ? (int)$nodo_creado->dato() : 0;
        $sesiones[] = [
            'token' => $token,
            'usuario' => $nodo_usuario ? $nodo_usuario->dato() : 'desconocido',
            'creado_en' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '',
        ];
    }
    return $sesiones;
}

/**
 * Cierra una sesión (la elimina).
 *
 * @param string $token Token de sesión.
 * @return bool Verdadero si se eliminó correctamente.
 */
function cerrar_sesion(string $token): bool {
    $raiz = Nodo::nodo_por_id('sesiones');
    if (!$raiz) return false;

    $nodo_sesion = $raiz->adyacente($token);
    if (!$nodo_sesion) return false;

    $raiz->eliminar_adyacente($token);
    Nodo::eliminar($nodo_sesion);
    Controlador::guardar(Conf::NOMBRE_APP);
    return true;
}