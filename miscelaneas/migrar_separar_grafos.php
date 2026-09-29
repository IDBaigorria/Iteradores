<?php
/**
 * Migración v1.5piloto.69: separar credenciales en un grafo aparte.
 *
 * Copia codigo_hash y contrasena de cada usuario al grafo de credenciales
 * y los elimina del grafo de la aplicación.
 *
 * Idempotente: si un usuario ya tiene las credenciales en el grafo de
 * credenciales, lo saltea.
 *
 * @package   Iteradores
 * @since     1.5piloto.69
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

/**
 * Ejecuta la migración de separación de grafos.
 *
 * @return array Contadores de la operación.
 */
function migrar_separar_grafos(): array {
    $contadores = [
        'usuarios_procesados'  => 0,
        'credenciales_copiadas' => 0,
        'codigos_copiados'      => 0,
        'contrasenas_copiadas'  => 0,
        'ya_migrados'          => 0,
        'sin_credenciales'     => 0,
    ];

    // Asegurar que el grafo de credenciales exista.
    en_grafo_credenciales(function() {});

    // Recorrer los usuarios de la app.
    $raiz_usuarios_app = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios_app) return $contadores;

    $para_migrar = [];
    foreach ($raiz_usuarios_app->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $contadores['usuarios_procesados']++;
        $nombre_usuario = (string)$nombre_usuario;
        $nodo_codigo = $nodo_usuario->adyacente('codigo_hash');
        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        if (!$nodo_codigo && !$nodo_contrasena) {
            $contadores['sin_credenciales']++;
            continue;
        }
        $para_migrar[$nombre_usuario] = [
            'codigo_hash' => $nodo_codigo ? $nodo_codigo->dato() : null,
            'contrasena'  => $nodo_contrasena ? $nodo_contrasena->dato() : null,
        ];
    }

    if (empty($para_migrar)) return $contadores;

    // Copiar a credenciales.
    en_grafo_credenciales(function() use ($para_migrar, &$contadores) {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) {
            Nodo::crear_con_id('usuarios');
            $raiz_cred = Nodo::nodo_por_id('usuarios');
        }
        foreach ($para_migrar as $nombre_usuario => $datos) {
            $nodo_cred = $raiz_cred->adyacente($nombre_usuario);
            if ($nodo_cred) {
                if ($nodo_cred->adyacente('codigo_hash') || $nodo_cred->adyacente('contrasena')) {
                    $contadores['ya_migrados']++;
                    continue;
                }
            } else {
                $nodo_cred = Nodo::crear_con_dato($nombre_usuario);
                $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);
            }
            if ($datos['codigo_hash'] !== null) {
                $nodo_cred->_adyacente_en(Nodo::crear_con_dato($datos['codigo_hash']), 'codigo_hash');
                $contadores['codigos_copiados']++;
            }
            if ($datos['contrasena'] !== null) {
                $nodo_cred->_adyacente_en(Nodo::crear_con_dato($datos['contrasena']), 'contrasena');
                $contadores['contrasenas_copiadas']++;
            }
            $contadores['credenciales_copiadas']++;
        }
    });

    // Eliminar credenciales del grafo de la aplicación.
    $raiz_usuarios_app = Nodo::nodo_por_id('usuarios');
    if ($raiz_usuarios_app) {
        $cambios = false;
        foreach ($para_migrar as $nombre_usuario => $datos) {
            $nodo_usuario = $raiz_usuarios_app->adyacente($nombre_usuario);
            if (!$nodo_usuario) continue;
            if ($nodo_usuario->adyacente('codigo_hash')) {
                $nodo_usuario->eliminar_adyacente('codigo_hash');
                $cambios = true;
            }
            if ($nodo_usuario->adyacente('contrasena')) {
                $nodo_usuario->eliminar_adyacente('contrasena');
                $cambios = true;
            }
        }
        if ($cambios) {
            Controlador::guardar(Conf::NOMBRE_APP);
        }
    }

    return $contadores;
}