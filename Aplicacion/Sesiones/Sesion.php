<?php
/**
 * Gestión de sesiones de usuario.
 *
 * @package   Iteradores
 * @version   1.5piloto.2
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Crea una nueva sesión para un usuario.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return string Token de sesión generado.
 */
function crear_sesion(string $nombre_usuario): string {
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
    if (!$raiz) {
        return [];
    }

    $adyacentes = $raiz->adyacentes();
    // Si no hay adyacentes o es null, devolvemos lista vacía
    if (!$adyacentes) {
        return [];
    }

    $sesiones = [];
    foreach ($adyacentes as $token => $nodo_sesion) {
        $nodo_usuario = $nodo_sesion->adyacente('usuario');
        $nodo_creado = $nodo_sesion->adyacente('creado_en');
        $sesiones[] = [
            'token' => $token,
            'usuario' => $nodo_usuario ? $nodo_usuario->dato() : 'desconocido',
            'creado_en' => $nodo_creado ? $nodo_creado->dato() : '',
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
    if (!$raiz) {
        return false;
    }
    $nodo_sesion = $raiz->adyacente($token);
    if (!$nodo_sesion) {
        return false;
    }
    $raiz->eliminar_adyacente($token);
    Nodo::eliminar($nodo_sesion);
    Controlador::guardar(Conf::NOMBRE_APP);
    return true;
}