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
 * @version 1.5piloto.78r
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

/**
 * Repara enlaces `dueño → compartido_con_<terminal>` faltantes.
 *
 * Recorre los terminales del dueño, sigue el enlace `dueno` de
 * cada uno, y si el destino tiene `_es_compartido` verifica que
 * el dueño tenga un enlace `compartido_con_<nombre_terminal>`
 * apuntando a ese nodo. Si falta, lo agrega. Idempotente.
 *
 * Contexto: por algún flujo previo, el enlace del dueño al
 * compartido puede haberse perdido. El compartido sigue vivo
 * y el terminal lo sigue alcanzando (`terminal → dueno`), pero
 * el dueño no lo tiene en su lista. Eso hace que el dueño no
 * vea los recursos que viven en el compartido.
 *
 * @return array{duenos: int, reparados: int, detalles: array}
 */
function reparar_compartidos_dueno(): array {
    $res = ['duenos' => 0, 'reparados' => 0, 'detalles' => []];

    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) return $res;

    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        $cont_terminales = $nodo_dueno->adyacente('terminales');
        if (!$cont_terminales) continue;

        $arreglados = [];
        foreach ((array)$cont_terminales->adyacentes() as $nombre_term => $nodo_term) {
            $nombre_term = (string)$nombre_term;
            $compartido = $nodo_term->adyacente('dueno');
            if (!$compartido) continue;
            if (!$compartido->adyacente('_es_compartido')) continue;
            $enlace = 'compartido_con_' . $nombre_term;
            if ($nodo_dueno->adyacente($enlace)) continue;
            $nodo_dueno->_adyacente_en($compartido, $enlace);
            $arreglados[] = $enlace;
        }

        if (!empty($arreglados)) {
            $res['reparados'] += count($arreglados);
            $res['detalles'][] = ['dueno' => (string)$nombre_dueno, 'enlaces' => $arreglados];
        }
    }
    return $res;
}

/**
 * Repara viajes que están en algún compartido del dueño pero
 * no en `us_dueno/viajes`. Recolecta los compartidos por dos
 * vías: el enlace directo `compartido_con_*` del dueño y el
 * enlace `dueno` de cada terminal. Idempotente.
 *
 * @return array{duenos: int, reparados: int, detalles: array}
 */
function reparar_viajes_dueno(): array {
    $res = ['duenos' => 0, 'reparados' => 0, 'detalles' => []];

    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) return $res;

    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        $cont_dueno = $nodo_dueno->adyacente('viajes');
        if (!$cont_dueno) {
            $cont_dueno = Nodo::crear_con_dato('');
            $nodo_dueno->_adyacente_en($cont_dueno, 'viajes');
        }

        $ya_presentes = [];
        foreach ((array)$cont_dueno->adyacentes() as $n => $_) {
            $ya_presentes[(string)$n] = true;
        }

        // Recolectar compartidos por dos vías.
        $compartidos = [];
        foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {
            $enlace = (string)$enlace;
            if (strpos($enlace, 'compartido_con_') === 0) {
                $compartidos[$hijo->id()] = $hijo;
            }
        }
        $cont_term = $nodo_dueno->adyacente('terminales');
        if ($cont_term) {
            foreach ((array)$cont_term->adyacentes() as $nodo_term) {
                $comp = $nodo_term->adyacente('dueno');
                if ($comp && $comp->adyacente('_es_compartido')) {
                    $compartidos[$comp->id()] = $comp;
                }
            }
        }

        // Recolectar viajes de cada compartido.
        $faltantes = [];
        foreach ($compartidos as $comp) {
            $c_viajes = $comp->adyacente('viajes');
            if (!$c_viajes) continue;
            foreach ((array)$c_viajes->adyacentes() as $nombre_v => $nodo_v) {
                $nombre_v = (string)$nombre_v;
                if (isset($ya_presentes[$nombre_v])) continue;
                $faltantes[$nombre_v] = $nodo_v;
            }
        }

        if (empty($faltantes)) continue;

        $agregados = [];
        foreach ($faltantes as $nombre_v => $nodo_v) {
            $cont_dueno->_adyacente_en($nodo_v, $nombre_v);
            $agregados[] = $nombre_v;
        }
        $res['reparados'] += count($agregados);
        $res['detalles'][] = ['dueno' => (string)$nombre_dueno, 'viajes' => $agregados];
    }
    return $res;
}

/**
 * Repara el contenedor `empresas` del dueño si falta.
 *
 * Reconstruye el contenedor recorriendo los micros de cada
 * viaje del dueño: cada micro tiene un enlace `empresa` al
 * nodo empresa. Se agrupan los nodos únicos y se enlazan al
 * contenedor `empresas` del dueño con el nombre de empresa
 * como clave. Idempotente.
 *
 * @return array{duenos: int, reparados: int, detalles: array}
 */
function reparar_empresas_dueno(): array {
    $res = ['duenos' => 0, 'reparados' => 0, 'detalles' => []];

    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) return $res;

    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        if ($nodo_dueno->adyacente('empresas')) continue;

        $cont_viajes = $nodo_dueno->adyacente('viajes');
        if (!$cont_viajes) continue;

        $empresas = [];
        foreach ((array)$cont_viajes->adyacentes() as $nodo_viaje) {
            $cont_micros = $nodo_viaje->adyacente('micros');
            if (!$cont_micros) continue;
            foreach ((array)$cont_micros->adyacentes() as $nodo_micro) {
                $nodo_emp = $nodo_micro->adyacente('empresa');
                if (!$nodo_emp) continue;
                $empresas[$nodo_emp->id()] = $nodo_emp;
            }
        }

        if (empty($empresas)) continue;

        $cont_emp = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($cont_emp, 'empresas');
        foreach ($empresas as $nodo_emp) {
            $nombre_emp = (string)$nodo_emp->dato();
            if ($nombre_emp === '') continue;
            $cont_emp->_adyacente_en($nodo_emp, $nombre_emp);
        }

        $res['reparados']++;
        $res['detalles'][] = ['dueno' => (string)$nombre_dueno, 'empresas' => count($empresas)];
    }
    return $res;
}

/**
 * Repara el contenedor `pasajeros` del dueño si falta.
 *
 * Los compartidos de cada terminal apuntan al mismo nodo
 * `pasajeros` del dueño (es un alias). Si el enlace directo
 * del dueño se perdió, se puede recuperar desde cualquier
 * compartido del dueño. Idempotente.
 *
 * @return array{duenos: int, reparados: int, detalles: array}
 */
function reparar_pasajeros_dueno(): array {
    $res = ['duenos' => 0, 'reparados' => 0, 'detalles' => []];

    $nodo_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$nodo_usuarios) return $res;

    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        if ($nodo_dueno->adyacente('pasajeros')) continue;

        $encontrado = null;
        foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {
            $enlace = (string)$enlace;
            if (strpos($enlace, 'compartido_con_') !== 0) continue;
            $cont_pas = $hijo->adyacente('pasajeros');
            if ($cont_pas) { $encontrado = $cont_pas; break; }
        }
        if (!$encontrado) continue;

        $nodo_dueno->_adyacente_en($encontrado, 'pasajeros');
        $res['reparados']++;
        $res['detalles'][] = ['dueno' => (string)$nombre_dueno];
    }
    return $res;
}

?>