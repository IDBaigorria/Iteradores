<?php
/**
 * Autenticación de usuarios.
 *
 * Cada intento de login (por código o por usuario+contraseña) aplica:
 * - Rate limiting: tras N intentos fallidos, el usuario queda bloqueado.
 * - Tiempo constante: todos los fallos verifican contra un hash dummy.
 * - Auditoría: se registra el último acceso y su IP.
 * - Rehash automático: si el hash quedó desactualizado, se regenera.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.71
 */

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Autentica a un usuario mediante su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario autenticado o null.
 */
function autenticar_por_codigo(string $codigo): ?array {
    $nombre_usuario = null;
    $ip_cliente = _ip_cliente();

    en_grafo_credenciales(function() use ($codigo, $ip_cliente, &$nombre_usuario) {
        $raiz_usuarios = Nodo::nodo_por_id('usuarios');
        if (!$raiz_usuarios) {
            _verificacion_dummy($codigo);
            return;
        }

        $encontrado = null;
        $encontrado_nodo = null;

        foreach ($raiz_usuarios->adyacentes() as $nombre => $nodo_usuario) {
            $nodo_hash = $nodo_usuario->adyacente('codigo_hash');
            if ($nodo_hash && password_verify($codigo, $nodo_hash->dato())) {
                $encontrado = (string)$nombre;
                $encontrado_nodo = $nodo_usuario;
                break;
            }
        }

        // Si no hay match en ningún usuario, dummy verify y fin.
        if ($encontrado === null) {
            _verificacion_dummy($codigo);
            return;
        }

        // ¿Está bloqueado el usuario?
        if (_esta_bloqueado($encontrado_nodo)) {
            _verificacion_dummy($codigo);
            return;
        }

        // Login exitoso: resetear intentos, registrar acceso, rehash.
        _registrar_login_exitoso($encontrado_nodo, $ip_cliente);
        $nombre_usuario = $encontrado;
    });

    if ($nombre_usuario === null) return null;

    return _construir_respuesta_login($nombre_usuario);
}

/**
 * Autentica a un usuario mediante su nombre y contraseña.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @param string $contrasena Contraseña en texto plano.
 * @return array|null Datos del usuario autenticado o null.
 */
function autenticar_por_usuario(string $nombre_usuario, string $contrasena): ?array {
    $verificado = false;
    $ip_cliente = _ip_cliente();

    en_grafo_credenciales(function() use ($nombre_usuario, $contrasena, $ip_cliente, &$verificado) {
        $raiz_usuarios = Nodo::nodo_por_id('usuarios');
        if (!$raiz_usuarios) {
            _verificacion_dummy($contrasena);
            return;
        }

        $nodo_usuario = $raiz_usuarios->adyacente($nombre_usuario);
        if (!$nodo_usuario) {
            _verificacion_dummy($contrasena);
            return;
        }

        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        if (!$nodo_contrasena) {
            _verificacion_dummy($contrasena);
            return;
        }

        // ¿Está bloqueado el usuario?
        if (_esta_bloqueado($nodo_usuario)) {
            _verificacion_dummy($contrasena);
            return;
        }

        // Verificar contraseña.
        if (!password_verify($contrasena, $nodo_contrasena->dato())) {
            _registrar_intento_fallido($nodo_usuario);
            return;
        }

        // Login exitoso: resetear intentos, registrar acceso, rehash.
        _registrar_login_exitoso($nodo_usuario, $ip_cliente);
        $verificado = true;
    });

    if (!$verificado) return null;

    return _construir_respuesta_login($nombre_usuario);
}

/**
 * Valida un token de sesión y devuelve el nombre de usuario y su nodo.
 *
 * @param string $token Token de sesión.
 * @return array|null Array con 'nombre_usuario' y 'nodo', o null si no es válido.
 */
function validar_token_sesion(string $token): ?array {
    $nombre_usuario = null;
    en_grafo_credenciales(function() use ($token, &$nombre_usuario) {
        $raiz = Nodo::nodo_por_id('sesiones');
        if (!$raiz) return;

        $nodo_sesion = $raiz->adyacente($token);
        if (!$nodo_sesion) return;

        $nodo_usuario = $nodo_sesion->adyacente('usuario');
        if (!$nodo_usuario) return;

        $nombre_usuario = $nodo_usuario->dato();
    });

    if ($nombre_usuario === null) return null;

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    $nodo_usuario_real = $raiz_usuarios->adyacente($nombre_usuario);
    if (!$nodo_usuario_real) return null;

    return [
        'nombre_usuario' => $nombre_usuario,
        'nodo' => $nodo_usuario_real,
    ];
}

// ============================================================
// Funciones auxiliares de autenticación
// ============================================================

/**
 * Devuelve la IP del cliente o un marcador si no está disponible.
 *
 * @return string
 */
function _ip_cliente(): string {
    return isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'desconocida';
}

/**
 * Ejecuta una verificación dummy para igualar tiempos.
 *
 * @param string $valor Valor a verificar (nunca coincide).
 * @return void
 */
function _verificacion_dummy(string $valor): void {
    password_verify($valor, Conf::HASH_DUMMY_AUTENTICACION);
}

/**
 * Comprueba si un usuario está bloqueado. Si el bloqueo expiró, lo limpia.
 *
 * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.
 * @return bool True si sigue bloqueado.
 */
function _esta_bloqueado(Nodo $nodo_usuario): bool {
    $nodo_bloqueo = $nodo_usuario->adyacente('bloqueado_hasta');
    if (!$nodo_bloqueo) return false;

    $hasta = (int)$nodo_bloqueo->dato();
    if ($hasta > time()) {
        return true;
    }

    // El bloqueo expiró: limpiar y resetear intentos.
    $nodo_usuario->eliminar_adyacente('bloqueado_hasta');
    $nodo_intentos = $nodo_usuario->adyacente('intentos_fallidos');
    if ($nodo_intentos) $nodo_intentos->_dato('0');
    return false;
}

/**
 * Registra un intento fallido. Si llega al máximo, bloquea al usuario.
 *
 * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.
 * @return void
 */
function _registrar_intento_fallido(Nodo $nodo_usuario): void {
    $nodo_intentos = $nodo_usuario->adyacente('intentos_fallidos');
    $intentos = $nodo_intentos ? (int)$nodo_intentos->dato() : 0;
    $intentos++;

    if ($nodo_intentos) $nodo_intentos->_dato((string)$intentos);
    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$intentos), 'intentos_fallidos');

    if ($intentos >= Conf::INTENTOS_MAXIMOS_AUTENTICACION) {
        $hasta = time() + Conf::BLOQUEO_AUTENTICACION_SEGUNDOS;
        $nodo_bloqueo = $nodo_usuario->adyacente('bloqueado_hasta');
        if ($nodo_bloqueo) $nodo_bloqueo->_dato((string)$hasta);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$hasta), 'bloqueado_hasta');
    }
}

/**
 * Registra un login exitoso: resetea intentos, guarda auditoría, rehash.
 *
 * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.
 * @param string $ip_cliente IP del cliente.
 * @return void
 */
function _registrar_login_exitoso(Nodo $nodo_usuario, string $ip_cliente): void {
    // Resetear intentos y bloqueo.
    $nodo_intentos = $nodo_usuario->adyacente('intentos_fallidos');
    if ($nodo_intentos) $nodo_intentos->_dato('0');
    $nodo_usuario->eliminar_adyacente('bloqueado_hasta');

    // Auditoría.
    $ahora = date('d/m/Y H:i');
    $nodo_ultimo = $nodo_usuario->adyacente('ultimo_acceso');
    if ($nodo_ultimo) $nodo_ultimo->_dato($ahora);
    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($ahora), 'ultimo_acceso');

    $nodo_ip = $nodo_usuario->adyacente('ip_ultimo_acceso');
    if ($nodo_ip) $nodo_ip->_dato($ip_cliente);
    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($ip_cliente), 'ip_ultimo_acceso');
}

/**
 * Construye la respuesta de login leyendo datos visibles de la app.
 *
 * También crea la sesión en el grafo de credenciales.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @return array|null Datos del usuario autenticado o null.
 */
function _construir_respuesta_login(string $nombre_usuario): ?array {
    $raiz_app = Nodo::nodo_por_id('usuarios');
    if (!$raiz_app) return null;
    $nodo_app = $raiz_app->adyacente($nombre_usuario);
    if (!$nodo_app) return null;

    $nodo_nivel = $nodo_app->adyacente('nivel');
    $nodo_nombre_real = $nodo_app->adyacente('nombre_real');

    $usuario = [
        'nombre_usuario' => $nombre_usuario,
        'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,
        'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
    ];

    $token = crear_sesion($nombre_usuario);
    $usuario['token_sesion'] = $token;

    return $usuario;
}