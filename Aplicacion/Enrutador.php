<?php
/**
 * Enrutador central de peticiones POST.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.3
 */

/**
 * Envía una respuesta JSON y termina la ejecución.
 *
 * @param array $datos Datos a codificar.
 * @return void
 */
function responder_json(array $datos): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Enruta una petición POST según la acción indicada.
 *
 * @param string $accion Acción en formato "modulo/subaccion".
 * @param array $post Datos recibidos por POST.
 * @return void
 */
function enrutar_peticion_post(string $accion, array $post): void {
    $partes = explode('/', $accion);
    $modulo = $partes[0] ?? '';
    $subaccion = $partes[1] ?? '';

    switch ($modulo) {
        case 'autenticar':
            switch ($subaccion) {
                case 'verificar':
                    $codigo = $post['codigo'] ?? '';
                    $usuario = autenticar_por_codigo($codigo);
                    if ($usuario) {
                        responder_json(['exito' => true, 'usuario' => $usuario]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Código incorrecto']);
                    }
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de autenticación no válida']);
            }
            break;

        case 'administrador':
            switch ($subaccion) {
                case 'verificar':
                    $codigo = $post['codigo'] ?? '';
                    if (verificar_codigo_admin($codigo)) {
                        responder_json(['exito' => true]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Código incorrecto']);
                    }
                    break;

                case 'listar_usuarios':
                    responder_json(['exito' => true, 'usuarios' => listar_usuarios()]);
                    break;

                case 'listar_duenos':
                    responder_json(['exito' => true, 'duenos' => listar_duenos()]);
                    break;

                case 'listar_sesiones':
                    responder_json(['exito' => true, 'sesiones' => listar_sesiones()]);
                    break;

                case 'agregar_usuario':
                    $resultado = agregar_usuario($post);
                    responder_json($resultado);
                    break;

                case 'actualizar_usuario':
                    $resultado = actualizar_usuario($post);
                    responder_json($resultado);
                    break;

                case 'eliminar_usuario':
                    $nombre_usuario = $post['nombre_usuario'] ?? '';
                    $resultado = eliminar_usuario($nombre_usuario);
                    responder_json($resultado);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de administrador no válida']);
            }
            break;

        case 'sesiones':
            if ($subaccion === 'cerrar') {
                $token = $post['token'] ?? '';
                if ($token && cerrar_sesion($token)) {
                    responder_json(['exito' => true]);
                } else {
                    responder_json(['exito' => false, 'error' => 'Token no válido']);
                }
            } elseif ($subaccion === 'validar') {
                $token = $post['token'] ?? '';
                $resultado = validar_token_sesion($token);
                if ($resultado) {
                    $nodo_usuario = $resultado['nodo'];
                    $nombre_usuario = $resultado['nombre_usuario'];
                    $nodo_nivel = $nodo_usuario->adyacente('nivel');
                    $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
                    $usuario = [
                        'nombre_usuario' => $nombre_usuario,
                        'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,
                        'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
                    ];
                    responder_json(['exito' => true, 'usuario' => $usuario]);
                } else {
                    responder_json(['exito' => false, 'error' => 'Sesión no válida']);
                }
            } else {
                responder_json(['exito' => false, 'error' => 'Subacción de sesiones no válida']);
            }
            break;

        default:
            responder_json(['exito' => false, 'error' => 'Módulo no reconocido']);
    }
}