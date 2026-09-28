<?php
/**
 * Migración v1.5piloto.68: hashear los códigos de acceso en texto plano.
 *
 * Por cada usuario, si tiene `codigo_acceso` (string en texto plano) y no
 * tiene `codigo_hash`, se genera el hash con password_hash() y se guarda
 * en `codigo_hash`. Se elimina el `codigo_acceso` original.
 *
 * Idempotente: si un usuario ya tiene `codigo_hash` o no tiene código,
 * se saltea.
 *
 * @package   Iteradores
 * @since     1.5piloto.68
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

/**
 * Ejecuta la migración de hasheo de credenciales.
 *
 * @return array Contadores de la operación.
 */
function migrar_hashear_credenciales(): array {
    $contadores = [
        'usuarios_procesados' => 0,
        'migrados'            => 0,
        'ya_migrados'         => 0,
        'sin_codigo'          => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        return $contadores;
    }

    foreach ($raiz_usuarios->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $contadores['usuarios_procesados']++;

        $nodo_codigo_hash = $nodo_usuario->adyacente('codigo_hash');
        if ($nodo_codigo_hash) {
            $contadores['ya_migrados']++;
            continue;
        }

        $nodo_codigo_viejo = $nodo_usuario->adyacente('codigo_acceso');
        if (!$nodo_codigo_viejo) {
            $contadores['sin_codigo']++;
            continue;
        }

        $codigo_texto = $nodo_codigo_viejo->dato();
        if ($codigo_texto === '') {
            $contadores['sin_codigo']++;
            continue;
        }

        $hash = password_hash($codigo_texto, PASSWORD_DEFAULT);
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($hash), 'codigo_hash');
        $nodo_usuario->eliminar_adyacente('codigo_acceso');

        $contadores['migrados']++;
    }

    if ($contadores['migrados'] > 0) {
        Controlador::guardar(Conf::NOMBRE_APP);
    }

    return $contadores;
}