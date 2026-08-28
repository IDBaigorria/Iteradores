<?php
// Asumimos que ya se ha inicializado el framework y cargado la superestructura
// Controlador::inicializar();
// Controlador::establecer_metodo('JSON');


require_once 'Configuracion/Configuracion.php';
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;
use Iteradores\Controlador\Controlador;
use Configuracion\Conf;

Controlador::cargar('administrador_de_viajes'); // si existe, o crear estructura inicial

function asegurar_estructura() {
    if (!Nodo::nodo_por_id('usuarios')) {
        Nodo::crear_con_id('usuarios');
    }
    if (!Nodo::nodo_por_id('sesiones')) {
        Nodo::crear_con_id('sesiones');
    }
}

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

        $banco_nombre = $banco_nodo ? ($banco_nodo->adyacente('nombre') ? $banco_nodo->adyacente('nombre')->dato() : '') : '';
        $banco_cuenta = $banco_nodo ? ($banco_nodo->adyacente('cuenta') ? $banco_nodo->adyacente('cuenta')->dato() : '') : '';

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
            'usuario' => $usuario_nodo ? $usuario_nodo->dato() : 'desconocido', // Asumimos que el nodo usuario guarda su nombre en dato
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
        asegurar_estructura();
        $raiz = Nodo::nodo_por_id('usuarios');
    }
    if ($raiz->adyacente($nombre_usuario)) {
        return ['exito' => false, 'error' => 'El nombre de usuario ya existe'];
    }

    // Crear nodo usuario con dato vacío
    $nodo_usuario = Nodo::crear_con_dato('');

    // Crear nodos para cada campo y enlazarlos
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), 'contrasena');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    if ($nombre_real !== '') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    }

    // Nodo banco con nombre y cuenta
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

    // Enlazar usuario al nodo raíz usando el nombre de usuario como enlace
    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);

    // Guardar persistencia
    Controlador::guardar('mi_app');

    return ['exito' => true];
}

// Manejo de peticiones
header('Content-Type: application/json');
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'verificar_admin':
        $codigo = $_POST['codigo'] ?? '';
        if (verificar_codigo_admin($codigo)) {
            echo json_encode(['exito' => true]);
        } else {
            echo json_encode(['exito' => false, 'error' => 'Código incorrecto']);
        }
        break;

    case 'listar_usuarios':
        echo json_encode(['exito' => true, 'usuarios' => listar_usuarios()]);
        break;

    case 'listar_sesiones':
        echo json_encode(['exito' => true, 'sesiones' => listar_sesiones()]);
        break;

    case 'agregar_usuario':
        $resultado = agregar_usuario($_POST);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(['exito' => false, 'error' => 'Acción no válida']);
}

Controlador::guardar('administrador_de_viajes'); 