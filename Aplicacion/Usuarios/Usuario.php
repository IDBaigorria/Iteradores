<?php
/**
 * Funciones de gestión de usuarios.
 *
 * @package   Iteradores
 * @version   1.5piloto.1
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

/**
 * Busca un usuario por su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario o null si no existe.
 */
function buscar_usuario_por_codigo(string $codigo): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        return null;
    }

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
    if (!$raiz) {
        return [];
    }

    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) {
        return [];
    }

    $usuarios = [];
    foreach ($adyacentes as $nombre_usuario => $nodo_usuario) {
        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        $nodo_efectivo = $nodo_usuario->adyacente('efectivo');
        $nodo_banco = $nodo_usuario->adyacente('banco');
        $nodo_nivel = $nodo_usuario->adyacente('nivel');
        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        $nodo_codigo_acceso = $nodo_usuario->adyacente('codigo_acceso');

        $banco_nombre = '';
        $banco_cuenta = '';
        if ($nodo_banco) {
            $nombre = $nodo_banco->adyacente('nombre');
            $cuenta = $nodo_banco->adyacente('cuenta');
            $banco_nombre = $nombre ? $nombre->dato() : '';
            $banco_cuenta = $cuenta ? $cuenta->dato() : '';
        }

        $usuario = [
            'nombre_usuario' => $nombre_usuario,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
            'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
            'efectivo' => $nodo_efectivo ? $nodo_efectivo->dato() : '0',
            'codigo_acceso' => $nodo_codigo_acceso ? $nodo_codigo_acceso->dato() : '',
            'banco' => [
                'nombre' => $banco_nombre,
                'cuenta' => $banco_cuenta,
            ],
        ];

        // Si es terminal, incluir a qué dueño pertenece
        if ($usuario['nivel'] === 'terminal') {
            $nodo_dueno = $nodo_usuario->adyacente('dueno');
            $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
        }

        // Si es dueño, incluir lista de sus terminales
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
 * Agrega un nuevo usuario a la estructura.
 *
 * @param array $datos Datos del formulario.
 * @return array Resultado de la operación.
 */
function agregar_usuario(array $datos): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    $contrasena = $datos['contrasena'] ?? '';
    $nivel = trim($datos['nivel'] ?? 'terminal');
    $efectivo = trim($datos['efectivo'] ?? '0');
    $banco_nombre = trim($datos['banco_nombre'] ?? '');
    $banco_cuenta = trim($datos['banco_cuenta'] ?? '');
    $nombre_real = trim($datos['nombre_real'] ?? '');
    $codigo_acceso = trim($datos['codigo_acceso'] ?? '');
    $dueno = trim($datos['dueno'] ?? '');

    if (empty($nombre_usuario) || empty($contrasena) || empty($codigo_acceso)) {
        return ['exito' => false, 'error' => 'Nombre de usuario, contraseña y código de acceso son obligatorios'];
    }

    // Verificar unicidad del código de acceso
    if (buscar_usuario_por_codigo($codigo_acceso)) {
        return ['exito' => false, 'error' => 'El código de acceso ya está en uso'];
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

    // Enlazar datos básicos
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), 'contrasena');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($codigo_acceso), 'codigo_acceso');
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

    // Si es terminal, asociar al dueño indicado
    if ($nivel === 'terminal') {
        if (empty($dueno)) {
            return ['exito' => false, 'error' => 'Debe especificar el dueño para una terminal'];
        }
        $nodo_dueno = $raiz->adyacente($dueno);
        if (!$nodo_dueno) {
            return ['exito' => false, 'error' => 'El dueño especificado no existe'];
        }
        // Verificar que el dueño sea nivel 'dueno'
        $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') {
            return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];
        }
        // Enlazar la terminal al dueño
        $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');
        // Agregar la terminal al contenedor de terminales del dueño
        $nodo_terminales = $nodo_dueno->adyacente('terminales');
        if (!$nodo_terminales) {
            $nodo_terminales = Nodo::crear_con_dato('');
            $nodo_dueno->_adyacente_en($nodo_terminales, 'terminales');
        }
        $nodo_terminales->_adyacente_en($nodo_usuario, $nombre_usuario);
    }

    // Enlazar usuario al nodo raíz usando nombre de usuario como enlace
    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);

    // Persistir
    Controlador::guardar(Conf::NOMBRE_APP);

    return ['exito' => true];
}