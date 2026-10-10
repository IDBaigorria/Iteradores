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

// ============================================================
// COMPARTIDOS POR TERMINAL (Fase B2.1, v76q)
// ============================================================

/**
 * ¿Todos los terminales autorizados en algún viaje tienen
 * su contenedor `compartido_con_*` en el dueño?
 *
 * @param string $token
 * @return bool
 */
/**
 * ¿Está aplicada la migración de compartidos por terminal?
 *
 * Fix v76y: la detección solo devuelve true si NO hay nada
 * que migrar (no hay dueños con terminales autorizados) o si
 * TODOS los compartidos esperados ya existen. Antes devolvía
 * true por vacío, lo cual auto-marcaba la migración como
 * aplicada sin estarlo.
 *
 * @param string $token
 * @return bool
 */
function detectar_compartidos_terminal(string $token): bool {
    $falta_alguno = false;
    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {
        if ($falta_alguno) return null;
        $id = (string)$nodo->id();
        if (strpos($id, 'us_') !== 0) return null;

        $nivel_nodo = $nodo->adyacente('nivel');
        if (!$nivel_nodo) {
            $publico = $nodo->adyacente('publico');
            if ($publico) $nivel_nodo = $publico->adyacente('nivel');
        }
        if (!$nivel_nodo || $nivel_nodo->dato() !== 'dueno') return null;

        $cont_viajes = $nodo->adyacente('viajes');
        if (!$cont_viajes) {
            $priv = $nodo->adyacente('privado');
            if ($priv) $cont_viajes = $priv->adyacente('viajes');
        }
        if (!$cont_viajes) return null;

        $terminales = [];
        foreach ((array)$cont_viajes->adyacentes() as $nv => $nodo_viaje) {
            $tas = $nodo_viaje->adyacente('terminales_autorizadas');
            if (!$tas) continue;
            foreach ((array)$tas->adyacentes() as $nombre_t => $nodo_tv) {
                $terminales[(string)$nombre_t] = true;
            }
        }
        foreach (array_keys($terminales) as $nombre_terminal) {
            if (!$nodo->adyacente('compartido_con_' . $nombre_terminal)) {
                $falta_alguno = true;
                return null;
            }
        }
        return null;
    }, null);
    return !$falta_alguno;
}

/**
 * Aplica la migración de compartidos por terminal.
 *
 * Delega en el comando `app:crear_compartidos_terminal`.
 *
 * @param string $token
 * @return array{exito: bool, detalles: array}
 */
function aplicar_migracion_compartidos_terminal(string $token): array {
    $res = Controlador::ejecutar_comando('app:crear_compartidos_terminal', ['dueno' => 'todos', 'terminal' => 'todos']);
    if (!is_array($res)) {
        return ['exito' => false, 'detalles' => ['El comando no devolvió un resumen válido.']];
    }
    if (!empty($res['errores'])) {
        return ['exito' => false, 'detalles' => $res];
    }
    return ['exito' => true, 'detalles' => $res];
}

// ============================================================
// ÁRBOLES PARALELOS EN COMPARTIDOS (Fase B2.3.3, v76x)
// ============================================================

/**
 * ¿Todos los compartidos tienen el marcador `_es_compartido`?
 *
 * @param string $token
 * @return bool
 */
/**
 * ¿Está aplicada la migración de árboles paralelos?
 *
 * Fix v76y: devuelve true solo si NO hay compartidos o si
 * TODOS los compartidos tienen el marcador `_es_compartido`.
 * Antes devolvía true por vacío.
 *
 * @param string $token
 * @return bool
 */
function detectar_arboles_compartidos(string $token): bool {
    $falta_alguno = false;
    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {
        if ($falta_alguno) return null;
        $id = (string)$nodo->id();
        if (strpos($id, 'us_') !== 0) return null;

        $nivel_nodo = $nodo->adyacente('nivel');
        if (!$nivel_nodo) {
            $publico = $nodo->adyacente('publico');
            if ($publico) $nivel_nodo = $publico->adyacente('nivel');
        }
        if (!$nivel_nodo || $nivel_nodo->dato() !== 'dueno') return null;

        $ady = (array)$nodo->adyacentes();
        foreach ($ady as $enlace => $hijo) {
            $enlace = (string)$enlace;
            if (strpos($enlace, 'compartido_con_') !== 0) continue;
            if (!$hijo->adyacente('_es_compartido')) {
                $falta_alguno = true;
                return null;
            }
        }
        return null;
    }, null);
    return !$falta_alguno;
}

/**
 * Aplica la migración de árboles paralelos.
 *
 * Delega en el comando `app:construir_arboles_compartidos`.
 *
 * @param string $token
 * @return array{exito: bool, detalles: array}
 */
function aplicar_migracion_arboles_compartidos(string $token): array {
    $res = Controlador::ejecutar_comando('app:construir_arboles_compartidos', ['dueno' => 'todos', 'terminal' => 'todos']);
    if (!is_array($res)) {
        return ['exito' => false, 'detalles' => ['El comando no devolvió un resumen válido.']];
    }
    if (!empty($res['errores'])) {
        return ['exito' => false, 'detalles' => $res];
    }
    return ['exito' => true, 'detalles' => $res];
}

// ============================================================
// REPUNTADO DE TERMINALES AL COMPARTIDO (Fase B2.3.5a, v77f)
// ============================================================

/**
 * ¿Está aplicado el repuntado?
 *
 * Devuelve true si todos los terminales con dueño apuntan al
 * compartido (que tiene `_es_compartido`), o si no hay
 * terminales. Devuelve false si algún terminal con dueño
 * sigue apuntando al nodo del dueño real.
 *
 * @param string $token
 * @return bool
 */
function detectar_repuntado_compartido(string $token): bool {
    $falta_alguno = false;
    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {
        if ($falta_alguno) return null;
        $id = (string)$nodo->id();
        if (strpos($id, 'us_') !== 0) return null;

        $nivel_nodo = $nodo->adyacente('nivel');
        if (!$nivel_nodo) {
            $publico = $nodo->adyacente('publico');
            if ($publico) $nivel_nodo = $publico->adyacente('nivel');
        }
        if (!$nivel_nodo || $nivel_nodo->dato() !== 'terminal') return null;

        $nodo_dueno = $nodo->adyacente('dueno');
        if (!$nodo_dueno) return null; // terminal sin dueño: no aplica

        if (!$nodo_dueno->adyacente('_es_compartido')) {
            $falta_alguno = true;
        }
        return null;
    }, null);
    return !$falta_alguno;
}

/**
 * Aplica el repuntado.
 *
 * Delega en el comando `app:repuntar_terminales_compartido`.
 *
 * @param string $token
 * @return array{exito: bool, detalles: array}
 */
function aplicar_migracion_repuntado_compartido(string $token): array {
    $res = Controlador::ejecutar_comando('app:repuntar_terminales_compartido', ['dueno' => 'todos', 'terminal' => 'todos']);
    if (!is_array($res)) {
        return ['exito' => false, 'detalles' => ['El comando no devolvió un resumen válido.']];
    }
    if (!empty($res['errores'])) {
        return ['exito' => false, 'detalles' => $res];
    }
    return ['exito' => true, 'detalles' => $res];
}
?>