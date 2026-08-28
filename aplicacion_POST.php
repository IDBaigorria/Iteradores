<?php
/**
 * Manejador de peticiones POST (API interna).
 * Recibe un parámetro 'accion' que define la operación a realizar.
 * Las acciones pueden ser jerárquicas usando '/', por ejemplo:
 *   - administrador/verificar
 *   - administrador/listar_usuarios
 *   - administrador/agregar_usuario
 *   - administrador/listar_sesiones
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

include_once("./Configuracion/Configuracion.php");
// Función auxiliar para enviar respuesta JSON
function responder($datos) {
    echo json_encode($datos);
    exit;
}

// ---- Funciones específicas de administración ----

function verificar_codigo_admin($codigo) {
    return $codigo === Conf::CODIGO_ADMIN;
}

function listar_usuarios() {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];
    $usuarios = [];
    foreach ($raiz->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $contrasena_nodo = $nodo_usuario->adyacente('contrasena');
        $efectivo_nodo = $nodo_usuario->adyacente('efectivo');
        $banco_nodo = $nodo_usuario->adyacente('banco');
        $nivel_nodo = $nodo_usuario->adyacente('nivel');
        $nombre_real_nodo = $nodo_usuario->adyacente('nombre_real');

        $banco_nombre = '';
        $banco_cuenta = '';
        if ($banco_nodo) {
            $nombre = $banco_nodo->adyacente('nombre');
            $cuenta = $banco_nodo->adyacente('cuenta');
            $banco_nombre = $nombre ? $nombre->dato() : '';
            $banco_cuenta = $cuenta ? $cuenta->dato() : '';
        }

        $usuarios[] = [
            'nombre_usuario' => $nombre_usuario,
            'nombre_real' => $nombre_real_nodo ? $nombre_real_nodo->dato() : '',
            'nivel' => $nivel_nodo ? $nivel_nodo->dato() : 'usuario',
            'efectivo' => $efectivo_nodo ? $efectivo_nodo->dato() : '0',
            'banco' => [
                'nombre' => $banco_nombre,
                'cuenta' => $banco_cuenta,
            ],
        ];
    }
    return $usuarios;
}

function listar_sesiones() {
    $raiz = Nodo::nodo_por_id('sesiones');
    if (!$raiz) return [];
    $sesiones = [];
    foreach ($raiz->adyacentes() as $token => $nodo_sesion) {
        $usuario_nodo = $nodo_sesion->adyacente('usuario');
        $creado_nodo = $nodo_sesion->adyacente('creado_en');
        $sesiones[] = [
            'token' => $token,
            'usuario' => $usuario_nodo ? $usuario_nodo->dato() : 'desconocido',
            'creado_en' => $creado_nodo ? $creado_nodo->dato() : '',
        ];
    }
    return $sesiones;
}

function agregar_usuario($datos) {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    $contrasena = $datos['contrasena'] ?? '';
    $nivel = trim($datos['nivel'] ?? 'usuario');
    $efectivo = trim($datos['efectivo'] ?? '0');
    $banco_nombre = trim($datos['banco_nombre'] ?? '');
    $banco_cuenta = trim($datos['banco_cuenta'] ?? '');
    $nombre_real = trim($datos['nombre_real'] ?? '');

    if (empty($nombre_usuario) || empty($contrasena)) {
        return ['exito' => false, 'error' => 'Nombre de usuario y contraseña son obligatorios'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        // Asegurar estructura
        if (!Nodo::nodo_por_id('usuarios')) {
            Nodo::crear_con_id('usuarios');
        }
        if (!Nodo::nodo_por_id('sesiones')) {
            Nodo::crear_con_id('sesiones');
        }
        $raiz = Nodo::nodo_por_id('usuarios');
    }

    if ($raiz->adyacente($nombre_usuario)) {
        return ['exito' => false, 'error' => 'El nombre de usuario ya existe'];
    }

    // Crear nodo usuario con dato vacío
    $nodo_usuario = Nodo::crear_con_dato('');

    // Enlazar datos
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), 'contrasena');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    if ($nombre_real !== '') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    }

    // Nodo banco
    if ($banco_nombre !== '' || $banco_cuenta !== '') {
        $nodo_banco = Nodo::crear_con_dato('');
        if ($banco_nombre !== '') {
            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        }
        if ($banco_cuenta !== '') {
            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        }
        $nodo_usuario->_adyacente_en($nodo_banco, 'banco');
    }

    // Enlazar al raíz usando nombre de usuario como enlace
    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);

    // Persistir
    Controlador::guardar('sistema_viajes');

    return ['exito' => true];
}

// ---- Enrutador principal ----

$accion = $_POST['accion'] ?? '';

// Separar la acción en partes (por ejemplo: "administrador/agregar_usuario")
$partes = explode('/', $accion);
$modulo = $partes[0] ?? '';
$subaccion = $partes[1] ?? '';

switch ($modulo) {
    case 'administrador':
        switch ($subaccion) {
            case 'verificar':
                $codigo = $_POST['codigo'] ?? '';
                if (verificar_codigo_admin($codigo)) {
                    responder(['exito' => true]);
                } else {
                    responder(['exito' => false, 'error' => 'Código incorrecto']);
                }
                break;

            case 'listar_usuarios':
                responder(['exito' => true, 'usuarios' => listar_usuarios()]);
                break;

            case 'listar_sesiones':
                responder(['exito' => true, 'sesiones' => listar_sesiones()]);
                break;

            case 'agregar_usuario':
                $resultado = agregar_usuario($_POST);
                responder($resultado);
                break;

            default:
                responder(['exito' => false, 'error' => 'Subacción de administrador no válida']);
        }
        break;

    default:
        responder(['exito' => false, 'error' => 'Módulo no reconocido']);
}