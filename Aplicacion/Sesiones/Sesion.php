<?php
/**
 * Gestión de sesiones de usuario.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.84a
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");


/**
 * Elimina todas las sesiones activas de un usuario.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return void
 */
function eliminar_sesiones_de_usuario(string $nombre_usuario): void {
    en_grafo_credenciales(function() use ($nombre_usuario) {
        $raiz = Nodo::nodo_por_id('sesiones');
        if (!$raiz) return;

        $sesiones = $raiz->adyacentes();
        if (!$sesiones) return;

        foreach ($sesiones as $token => $nodo_sesion) {
            $nodo_usuario = $nodo_sesion->adyacente('usuario');
            if ($nodo_usuario && $nodo_usuario->dato() === $nombre_usuario) {
                $raiz->eliminar_adyacente($token);
                // Fase 2, v75: destruir los campos del nodo
                // sesión (usuario, creado_en). Antes quedaban
                // huérfanos (~2 nodos por sesión eliminada).
                _destruir_campos_simples($nodo_sesion);
                Nodo::eliminar($nodo_sesion);
            }
        }
    });
}

/**
 * Crea una nueva sesión para un usuario, eliminando antes las anteriores.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return string Token de sesión generado.
 */
function crear_sesion(string $nombre_usuario): string {
    // Fix 1.5piloto.83o: no guardar la app (solo credenciales). La app
    // no cambia al crear una sesión; el guardado total de la app era
    // innecesario y duplicaba el trabajo en cada login.
    return en_grafo_credenciales(function() use ($nombre_usuario) {
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

        return $token;
    }, false);
}

/**
 * Lista todas las sesiones activas.
 *
 * @return array Lista de sesiones.
 */
function listar_sesiones(): array {
    return en_grafo_credenciales_solo_lectura(function() {
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
    });
}

/**
 * Cierra una sesión (la elimina).
 *
 * @param string $token Token de sesión.
 * @return bool Verdadero si se eliminó correctamente.
 */
function cerrar_sesion(string $token): bool {
    return en_grafo_credenciales(function() use ($token) {
        $raiz = Nodo::nodo_por_id('sesiones');
        if (!$raiz) return false;

        $nodo_sesion = $raiz->adyacente($token);
        if (!$nodo_sesion) return false;

        $raiz->eliminar_adyacente($token);
        // Fase 2, v75: destruir los campos del nodo sesión
        // (usuario, creado_en).
        _destruir_campos_simples($nodo_sesion);
        Nodo::eliminar($nodo_sesion);
        return true;
    });
}

/**
 * Lista las sesiones activas de un conjunto de usuarios.
 *
 * @param array $nombres_usuarios Lista de nombres de usuario.
 * @return array Lista de sesiones filtradas.
 */
function listar_sesiones_de_usuarios(array $nombres_usuarios): array {
    if (empty($nombres_usuarios)) return [];

    return en_grafo_credenciales_solo_lectura(function() use ($nombres_usuarios) {
        $raiz = Nodo::nodo_por_id('sesiones');
        if (!$raiz) return [];

        $adyacentes = $raiz->adyacentes();
        if (!$adyacentes) return [];

        $sesiones = [];
        foreach ($adyacentes as $token => $nodo_sesion) {
            $nodo_usuario = $nodo_sesion->adyacente('usuario');
            if (!$nodo_usuario) continue;
            $nombre_usuario = $nodo_usuario->dato();
            if (in_array($nombre_usuario, $nombres_usuarios, true)) {
                $nodo_creado = $nodo_sesion->adyacente('creado_en');
                $timestamp = $nodo_creado ? (int)$nodo_creado->dato() : 0;
                $sesiones[] = [
                    'token' => $token,
                    'usuario' => $nombre_usuario,
                    'creado_en' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '',
                ];
            }
        }
        return $sesiones;
    });
}

// ============================================================
// Fase 1 de seguridad (1.5piloto.84a): sesion PHP
//
// Guarda la identidad del usuario en $_SESSION, sobre la cookie
// PHPSESSID que ya crea session_start() en index.php. Sirve como
// punto de verdad server-side para las requests del mismo
// navegador. El token en el grafo de credenciales (sesiones/
// <token>) sigue siendo la fuente de verdad para invalidar
// sesiones remotamente: si el token se borra, la proxima
// sesiones/validar falla y limpia $_SESSION.
// ============================================================

/**
 * Guarda la identidad del usuario en $_SESSION.
 *
 * Se llama desde:
 *   - `_construir_respuesta_login` (login por codigo o
 *     usuario+contrasena), despues de crear el token en el
 *     grafo de credenciales.
 *   - `sesiones/validar` (rehidratacion al recargar la pagina
 *     con un token del localStorage).
 *
 * La cookie PHPSESSID viaja en cada request del navegador,
 * asi que a partir de aca las requests del mismo navegador
 * tienen identidad server-side.
 *
 * @param array $usuario Array con al menos nombre_usuario y
 *                       nivel. Opcionalmente dueno (terminal),
 *                       duenos (soporte), token_sesion.
 * @return void
 */
function guardar_sesion_php(array $usuario): void {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $prefijo = Entorno::prefijo_sesion();
    $_SESSION[$prefijo . 'usuario'] = [
        'nombre_usuario' => (string)($usuario['nombre_usuario'] ?? ''),
        'nivel' => (string)($usuario['nivel'] ?? ''),
        'dueno' => (string)($usuario['dueno'] ?? ''),
        'duenos' => is_array($usuario['duenos'] ?? null) ? $usuario['duenos'] : [],
        'token_sesion' => (string)($usuario['token_sesion'] ?? ''),
    ];
    $_SESSION[$prefijo . 'ultima_actividad'] = time();
}

/**
 * Devuelve el usuario guardado en $_SESSION, o null si no hay.
 *
 * @return array|null
 */
function usuario_sesion_actual(): ?array {
    if (session_status() !== PHP_SESSION_ACTIVE) return null;
    $prefijo = Entorno::prefijo_sesion();
    $u = $_SESSION[$prefijo . 'usuario'] ?? null;
    return is_array($u) ? $u : null;
}

/**
 * Limpia la identidad del usuario en $_SESSION.
 *
 * Se llama desde:
 *   - `sesiones/cerrar` cuando el token cerrado coincide con
 *     el de la sesion PHP actual (logout local).
 *   - `sesiones/validar` cuando el token enviado ya no es
 *     valido (expirado o cerrado remotamente).
 *
 * No destruye la cookie PHPSESSID; solo limpia las claves
 * propias de la app. La proxima request del mismo navegador
 * arranca sin identidad y el JS pide login.
 *
 * @return void
 */
function limpiar_sesion_php(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $prefijo = Entorno::prefijo_sesion();
    unset($_SESSION[$prefijo . 'usuario']);
    unset($_SESSION[$prefijo . 'ultima_actividad']);
}

/**
 * Devuelve el token de sesion guardado en $_SESSION, o null.
 *
 * Se usa en `sesiones/cerrar` para distinguir:
 *   - logout local (el token cerrado es el propio);
 *   - cierre remoto (dueno/admin cerrando el de otro, no
 *     tocar la sesion PHP del solicitante).
 *
 * @return string|null
 */
function token_sesion_actual(): ?string {
    $u = usuario_sesion_actual();
    if (!$u) return null;
    $t = (string)($u['token_sesion'] ?? '');
    return $t !== '' ? $t : null;
}