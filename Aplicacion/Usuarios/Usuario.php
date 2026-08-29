<?php
/**
 * Funciones de gestión de usuarios.
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
 * Busca un usuario por su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario o null si no existe.
 */
function buscar_usuario_por_codigo(string $codigo): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    foreach ($raiz_usuarios->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $nodo_codigo = $nodo_usuario->adyacente('codigo_acceso');
        if ($nodo_codigo && $nodo_codigo->dato() === $codigo) {
            $nodo_nivel = $nodo_usuario->adyacente('nivel');
            $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
            return [
                'nombre_usuario' => $nombre_usuario,
                'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,
                'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
            ];
        }
    }
    return null;
}

/**
 * Lista todos los usuarios registrados con sus datos.
 *
 * @return array Lista de usuarios.
 */
function listar_usuarios(): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];

    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return [];

    $usuarios = [];
    foreach ($adyacentes as $nombre_usuario => $nodo_usuario) {
        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        $nodo_efectivo = $nodo_usuario->adyacente('efectivo');
        $nodo_banco = $nodo_usuario->adyacente('banco');
        $nodo_nivel = $nodo_usuario->adyacente('nivel');
        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        $nodo_codigo_acceso = $nodo_usuario->adyacente('codigo_acceso');
        $nodo_email = $nodo_usuario->adyacente('email');

        $banco_nombre = '';
        $banco_cuenta = '';
        $monto_banco = '0';
        if ($nodo_banco) {
            $monto_banco = $nodo_banco->dato();
            $nombre = $nodo_banco->adyacente('nombre');
            $cuenta = $nodo_banco->adyacente('cuenta');
            $banco_nombre = $nombre ? $nombre->dato() : '';
            $banco_cuenta = $cuenta ? $cuenta->dato() : '';
        }

        $usuario = [
            'nombre_usuario' => $nombre_usuario,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
            'email' => $nodo_email ? $nodo_email->dato() : '',
            'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
            'efectivo' => $nodo_efectivo ? $nodo_efectivo->dato() : '0',
            'bancarizado' => $monto_banco,
            'codigo_acceso' => $nodo_codigo_acceso ? $nodo_codigo_acceso->dato() : '',
            'banco' => [
                'nombre' => $banco_nombre,
                'cuenta' => $banco_cuenta,
            ],
        ];

        if ($usuario['nivel'] === 'terminal') {
            $nodo_dueno = $nodo_usuario->adyacente('dueno');
            $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
        }

        if ($usuario['nivel'] === 'dueno') {
            $terminales = [];
            $nodo_terminales = $nodo_usuario->adyacente('terminales');
            if ($nodo_terminales) {
                foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {
                    $terminales[] = $nombre_terminal;
                }
            }
            $usuario['terminales'] = $terminales;
        }

        $usuarios[] = $usuario;
    }
    return $usuarios;
}

/**
 * Lista los usuarios con nivel 'dueno'.
 *
 * @return array Lista de dueños con nombre de usuario y nombre real.
 */
function listar_duenos(): array {
    $todos = listar_usuarios();
    $duenos = [];
    foreach ($todos as $usuario) {
        if ($usuario['nivel'] === 'dueno') {
            $duenos[] = [
                'nombre_usuario' => $usuario['nombre_usuario'],
                'nombre_real' => $usuario['nombre_real'],
            ];
        }
    }
    return $duenos;
}

/**
 * Agrega un nuevo usuario a la estructura.
 *
 * @param array $datos Datos del formulario.
 * @return array Resultado de la operación.
 */
function agregar_usuario(array $datos): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    $contrasena = trim($datos['contrasena'] ?? '');
    $nivel = trim($datos['nivel'] ?? 'dueno');
    $efectivo = '0';
    $banco_nombre = trim($datos['banco_nombre'] ?? '');
    $banco_cuenta = trim($datos['banco_cuenta'] ?? '');
    $nombre_real = trim($datos['nombre_real'] ?? '');
    $codigo_acceso = trim($datos['codigo_acceso'] ?? '');
    $dueno = trim($datos['dueno'] ?? '');
    $email = trim($datos['email'] ?? '');

    if (empty($nombre_usuario) || empty($codigo_acceso)) {
        return ['exito' => false, 'error' => 'Nombre de usuario y código de acceso son obligatorios'];
    }

    if (buscar_usuario_por_codigo($codigo_acceso)) {
        return ['exito' => false, 'error' => 'El código de acceso ya está en uso'];
    }

    if ($nivel === 'terminal' && empty($dueno)) {
        return ['exito' => false, 'error' => 'Debe especificar el dueño para una terminal'];
    }

    if ($nivel === 'terminal' && (empty($banco_nombre) || empty($banco_cuenta))) {
        return ['exito' => false, 'error' => 'Banco y cuenta son obligatorios para terminales'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        if (!Nodo::nodo_por_id('usuarios')) Nodo::crear_con_id('usuarios');
        if (!Nodo::nodo_por_id('sesiones')) Nodo::crear_con_id('sesiones');
        $raiz = Nodo::nodo_por_id('usuarios');
    }

    if ($raiz->adyacente($nombre_usuario)) {
        return ['exito' => false, 'error' => 'El nombre de usuario ya existe'];
    }

    $nodo_usuario = Nodo::crear_con_dato($nombre_usuario);

    if ($contrasena !== '') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), 'contrasena');
    }

    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($codigo_acceso), 'codigo_acceso');
    if ($nombre_real !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    if ($email !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($email), 'email');

    if ($nivel === 'terminal') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');

        $nodo_banco = Nodo::crear_con_dato('0');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        $nodo_usuario->_adyacente_en($nodo_banco, 'banco');

        $nodo_dueno = $raiz->adyacente($dueno);
        if (!$nodo_dueno) return ['exito' => false, 'error' => 'El dueño especificado no existe'];
        $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];
        $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');

        $nodo_terminales = $nodo_dueno->adyacente('terminales');
        if (!$nodo_terminales) {
            $nodo_terminales = Nodo::crear_con_dato('');
            $nodo_dueno->_adyacente_en($nodo_terminales, 'terminales');
        }
        $nodo_terminales->_adyacente_en($nodo_usuario, $nombre_usuario);
    }

    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);
    Controlador::guardar(Conf::NOMBRE_APP);

    return ['exito' => true];
}

/**
 * Actualiza los datos de un usuario existente.
 *
 * @param array $datos Datos del formulario.
 * @return array Resultado de la operación.
 */
function actualizar_usuario(array $datos): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_usuario = $raiz->adyacente($nombre_usuario);
    if (!$nodo_usuario) return ['exito' => false, 'error' => 'Usuario no encontrado'];

    $nivel_actual = $nodo_usuario->adyacente('nivel');
    $nivel_actual = $nivel_actual ? $nivel_actual->dato() : 'terminal';

    $nivel = trim($datos['nivel'] ?? $nivel_actual);
    $nombre_real = trim($datos['nombre_real'] ?? '');
    $email = trim($datos['email'] ?? '');
    $codigo_acceso = trim($datos['codigo_acceso'] ?? '');
    $contrasena = trim($datos['contrasena'] ?? '');

    if (!empty($codigo_acceso)) {
        $existente = buscar_usuario_por_codigo($codigo_acceso);
        if ($existente && $existente['nombre_usuario'] !== $nombre_usuario) {
            return ['exito' => false, 'error' => 'El código de acceso ya está en uso por otro usuario'];
        }
    }

    if ($nombre_real !== '') {
        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        if ($nodo_nombre_real) $nodo_nombre_real->_dato($nombre_real);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    }
    if ($email !== '') {
        $nodo_email = $nodo_usuario->adyacente('email');
        if ($nodo_email) $nodo_email->_dato($email);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($email), 'email');
    }
    if ($codigo_acceso !== '') {
        $nodo_codigo = $nodo_usuario->adyacente('codigo_acceso');
        if ($nodo_codigo) $nodo_codigo->_dato($codigo_acceso);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($codigo_acceso), 'codigo_acceso');
    }
    if ($contrasena !== '') {
        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        if ($nodo_contrasena) $nodo_contrasena->_dato($hash);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($hash), 'contrasena');
    }

    $nodo_nivel = $nodo_usuario->adyacente('nivel');
    if ($nodo_nivel) $nodo_nivel->_dato($nivel);
    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');

    if ($nivel_actual !== $nivel) {
        if ($nivel !== 'terminal') {
            $nodo_usuario->eliminar_adyacente('efectivo');
            $nodo_usuario->eliminar_adyacente('banco');
            $nodo_usuario->eliminar_adyacente('dueno');
        }
        if ($nivel_actual === 'dueno' && $nivel !== 'dueno') {
            $nodo_usuario->eliminar_adyacente('terminales');
        }
    }

    if ($nivel === 'terminal') {
        $banco_nombre = trim($datos['banco_nombre'] ?? '');
        $banco_cuenta = trim($datos['banco_cuenta'] ?? '');
        if (empty($banco_nombre) || empty($banco_cuenta)) {
            return ['exito' => false, 'error' => 'Banco y cuenta son obligatorios para terminales'];
        }

        $nodo_banco = $nodo_usuario->adyacente('banco');
        if (!$nodo_banco) {
            $nodo_banco = Nodo::crear_con_dato('0');
            $nodo_usuario->_adyacente_en($nodo_banco, 'banco');
        }
        $nodo_banco_nombre = $nodo_banco->adyacente('nombre');
        if ($nodo_banco_nombre) $nodo_banco_nombre->_dato($banco_nombre);
        else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        $nodo_banco_cuenta = $nodo_banco->adyacente('cuenta');
        if ($nodo_banco_cuenta) $nodo_banco_cuenta->_dato($banco_cuenta);
        else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');

        $dueno = trim($datos['dueno'] ?? '');
        if (empty($dueno)) return ['exito' => false, 'error' => 'Debe seleccionar un dueño'];
        $nodo_dueno = $raiz->adyacente($dueno);
        if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no existe'];
        $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];

        $nodo_usuario->eliminar_adyacente('dueno');
        $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Elimina un usuario existente.
 *
 * @param string $nombre_usuario Nombre del usuario a eliminar.
 * @return array Resultado de la operación.
 */
function eliminar_usuario(string $nombre_usuario): array {
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    if ($nombre_usuario === Conf::NOMBRE_ADMIN) {
        return ['exito' => false, 'error' => 'No se puede eliminar al administrador principal'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_usuario = $raiz->adyacente($nombre_usuario);
    if (!$nodo_usuario) return ['exito' => false, 'error' => 'Usuario no encontrado'];

    $nodo_terminales = $nodo_usuario->adyacente('terminales');
    if ($nodo_terminales && $nodo_terminales->adyacentes()) {
        return ['exito' => false, 'error' => 'No se puede eliminar un dueño con terminales asociadas'];
    }

    $nodo_dueno = $nodo_usuario->adyacente('dueno');
    if ($nodo_dueno) {
        $dueno_nombre = $nodo_dueno->dato();
        $nodo_dueno_real = $raiz->adyacente($dueno_nombre);
        if ($nodo_dueno_real) {
            $nodo_contenedor_terminales = $nodo_dueno_real->adyacente('terminales');
            if ($nodo_contenedor_terminales) {
                $nodo_contenedor_terminales->eliminar_adyacente($nombre_usuario);
            }
        }
    }

    $raiz->eliminar_adyacente($nombre_usuario);
    Nodo::eliminar($nodo_usuario);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}