<?php
/**
 * Migración topológica (v1.5piloto.78f).
 *
 * Dos operaciones independientes e idempotentes:
 *
 * 1. migrar_topologia_admin():
 *    Crea el enlace `us_Administrador → usuarios`. Sirve
 *    para que el admin pueda cargar todos los usuarios
 *    navegando desde su propio contexto (us_Administrador),
 *    sin depender de que `usuarios` sea alcanzable desde
 *    otros usuarios.
 *
 * 2. migrar_topologia_soporte():
 *    Asegura el contenedor `publico` en cada soporte y
 *    repunta el enlace `dueno → soporte` para que apunte
 *    al `publico` del soporte en lugar del nodo del soporte
 *    real. Sin este repuntado, el BFS de contextos propaga
 *    el bit del dueño al soporte, y desde el soporte a sus
 *    otros dueños: fuga cruzada entre dueños que comparten
 *    un mismo soporte.
 *
 * Ambas respetan el principio: el dueño ve datos públicos
 * del soporte (nombre, email), pero no puede navegar desde
 * ahí hacia otros dueños.
 *
 * @author Ignacio David Baigorria
 * @since 1.5piloto.78f
 */

use Iteradores\Nodos\Nodo;

/**
 * Crea el enlace us_Administrador → usuarios si no existe.
 * Idempotente.
 *
 * @param string $nombre_app
 * @return array{creado: bool, ya_existia: bool, errores: array}
 */
function migrar_topologia_admin(string $nombre_app): array {
    $res = ['creado' => false, 'ya_existia' => false, 'errores' => []];

    $nodo_admin = Nodo::nodo_por_id('us_' . ConfiguracionApli::NOMBRE_ADMIN);
    if (!$nodo_admin) {
        $res['errores'][] = 'No existe el nodo del admin.';
        return $res;
    }
    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) {
        $res['errores'][] = 'No existe la raíz usuarios.';
        return $res;
    }
    if ($nodo_admin->adyacente('usuarios')) {
        $res['ya_existia'] = true;
        return $res;
    }
    $nodo_admin->_adyacente_en($nodo_usuarios, 'usuarios');
    $res['creado'] = true;
    return $res;
}

/**
 * Para cada soporte, asegura su `publico` y repunta el
 * enlace `soporte` de cada dueño asignado hacia ese
 * `publico`. Idempotente.
 *
 * @param string $nombre_app
 * @return array{soportes: int, repuntados: int, ya_ok: int, errores: array}
 */
function migrar_topologia_soporte(string $nombre_app): array {
    $res = ['soportes' => 0, 'repuntados' => 0, 'ya_ok' => 0, 'errores' => []];

    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) {
        $res['errores'][] = 'No existe la raíz usuarios.';
        return $res;
    }

    if (!function_exists('_asegurar_niveles_usuario')) {
        $res['errores'][] = '_asegurar_niveles_usuario no disponible.';
        return $res;
    }

    foreach ($nodo_usuarios->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $nivel_nodo = $nodo_usuario->adyacente('nivel');
        if (!$nivel_nodo) {
            $publico = $nodo_usuario->adyacente('publico');
            if ($publico) $nivel_nodo = $publico->adyacente('nivel');
        }
        if (!$nivel_nodo || $nivel_nodo->dato() !== 'soporte') continue;

        $res['soportes']++;

        // Asegurar publico (por las dudas no lo tenga).
        _asegurar_niveles_usuario($nodo_usuario);

        $publico = $nodo_usuario->adyacente('publico');
        if (!$publico) {
            $res['errores'][] = "No se pudo asegurar publico para soporte \"$nombre_usuario\".";
            continue;
        }

        // Repuntar los dueños de este soporte.
        $nodo_duenos = $nodo_usuario->adyacente('duenos');
        if (!$nodo_duenos) continue;

        foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {
            $enlace_actual = $nodo_dueno->adyacente('soporte');
            if (!$enlace_actual) {
                $nodo_dueno->_adyacente_en($publico, 'soporte');
                $res['repuntados']++;
                continue;
            }
            if ($enlace_actual->id() === $publico->id()) {
                $res['ya_ok']++;
                continue;
            }
            $nodo_dueno->eliminar_adyacente('soporte');
            $nodo_dueno->_adyacente_en($publico, 'soporte');
            $res['repuntados']++;
        }
    }
    return $res;
}

?>