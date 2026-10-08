<?php
/**
 * Funciones de detección y aplicación de migraciones.
 *
 * Todas reciben el $token como parámetro (lo pasa el
 * comando que las invoca). Devuelven estructuras simples.
 *
 * @since 1.5piloto.76n
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

// ============================================================
// DETECCIÓN
// ============================================================

/**
 * ¿Todos los usuarios tienen ID especial us_*?
 *
 * @param string $token
 * @return bool
 */
function detectar_usuarios_especiales(string $token): bool {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return true; // sin usuarios: trivialmente aplicada
    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return true;
    foreach ($adyacentes as $nombre => $nodo) {
        $id = (string)$nodo->id();
        if (strpos($id, 'us_') !== 0) return false;
    }
    return true;
}

/**
 * ¿Todos los usuarios tienen contenedor `publico`?
 *
 * @param string $token
 * @return bool
 */
function detectar_niveles_usuario(string $token): bool {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return true;
    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return true;
    foreach ($adyacentes as $nombre => $nodo) {
        if (!$nodo->adyacente('publico')) return false;
    }
    return true;
}

// ============================================================
// APLICACIÓN
// ============================================================

/**
 * Aplica la migración de usuarios como IDs especiales.
 *
 * Delega en `migrar_usuarios_a_especiales` (que ya existe).
 * El comando que llama a este wrapper es responsable de
 * guardar los grafos.
 *
 * @param string $token
 * @return array{exito: bool, detalles: array}
 */
function aplicar_migracion_usuarios_especiales(string $token): array {
    if (!function_exists('migrar_usuarios_a_especiales')) {
        require_once __DIR__ . '/../../miscelaneas/migrar_usuarios_especiales.php';
    }
    if (!function_exists('migrar_usuarios_a_especiales')) {
        return ['exito' => false, 'detalles' => ['No se encontró migrar_usuarios_a_especiales.']];
    }
    $res = migrar_usuarios_a_especiales(ConfiguracionApli::NOMBRE_APP);
    return ['exito' => true, 'detalles' => $res];
}

/**
 * Aplica la migración de contenedores publico/privado.
 *
 * Delega en el comando `grafo:crear_niveles_usuario`.
 *
 * @param string $token
 * @return array{exito: bool, detalles: array}
 */
function aplicar_migracion_niveles_usuario(string $token): array {
    $res = Controlador::ejecutar_comando('grafo:crear_niveles_usuario', ['usuario' => 'todos']);
    if (!is_array($res)) {
        return ['exito' => false, 'detalles' => ['El comando no devolvió un resumen válido.']];
    }
    if (!empty($res['errores'])) {
        return ['exito' => false, 'detalles' => $res];
    }
    return ['exito' => true, 'detalles' => $res];
}
?>