<?php
/**
 * Migración: convertir cada nodo usuario a ID especial
 * us_<nombre>.
 *
 * Los enlaces desde `usuarios` siguen llamándose <nombre>
 * (nombre visible), así todos los accesos por adyacente()
 * siguen funcionando sin cambios.
 *
 * Idempotente: los usuarios ya migrados (con ID que empieza
 * por us_) se saltean.
 *
 * @since 1.5piloto.76k
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Ejecuta la migración en ambos grafos.
 *
 * @param string $nombre_app Nombre de la superestructura de la app.
 * @return array Resumen de la operación.
 */
function migrar_usuarios_a_especiales(string $nombre_app): array {
    $res = [
        'app' => null,
        'credenciales' => null,
    ];

    // Migrar el grafo de la aplicación.
    $res['app'] = _migrar_usuarios_en_grafo_actual();

    // Migrar el grafo de credenciales. `en_grafo_credenciales`
    // guarda la app al entrar (con los usuarios ya migrados)
    // y la recarga al salir.
    $res['credenciales'] = en_grafo_credenciales(function() {
        return _migrar_usuarios_en_grafo_actual();
    });

    return $res;
}

/**
 * Migra los usuarios del grafo actualmente cargado.
 *
 * Pasos:
 *   1. Recolectar usuarios comunes (ID numérico).
 *   2. Crear los nodos especiales us_<nombre>.
 *   3. Copiar los adyacentes de cada viejo al nuevo,
 *      redirigiendo destinos que también sean viejos.
 *   4. Actualizar el enlace en `usuarios`.
 *   5. Redirigir referencias externas (vía comando).
 *   6. Destruir los nodos viejos.
 *
 * @return array Resumen.
 */
function _migrar_usuarios_en_grafo_actual(): array {
    $res = [
        'migrados' => 0,
        'saltados' => 0,
        'enlaces_actualizados' => 0,
        'errores' => [],
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        $res['errores'][] = 'No existe el nodo usuarios.';
        return $res;
    }

    $adyacentes = $raiz_usuarios->adyacentes();
    if (!$adyacentes) {
        return $res;
    }

    // --- Paso 1 y 2: recolectar y crear los nuevos nodos ---
    $migraciones = [];
    foreach ($adyacentes as $nombre_enlace => $nodo_viejo) {
        $nombre_enlace = (string)$nombre_enlace;
        $id_viejo = (string)$nodo_viejo->id();

        if (strpos($id_viejo, 'us_') === 0) {
            $res['saltados']++;
            continue;
        }
        if (!is_numeric($id_viejo)) {
            $res['errores'][] = "ID no numérico ni us_: $id_viejo (enlace: $nombre_enlace)";
            continue;
        }

        $id_nuevo = 'us_' . $nombre_enlace;
        if (Nodo::existe($id_nuevo)) {
            $res['errores'][] = "Ya existe el nodo especial $id_nuevo";
            continue;
        }

        $nodo_nuevo = Nodo::crear_con_dato_e_id($nodo_viejo->dato(), $id_nuevo);
        if (!$nodo_nuevo) {
            $res['errores'][] = "No se pudo crear $id_nuevo";
            continue;
        }

        $migraciones[] = [
            'viejo' => $nodo_viejo,
            'nuevo' => $nodo_nuevo,
            'enlace' => $nombre_enlace,
            'id_viejo' => $id_viejo,
            'id_nuevo' => $id_nuevo,
        ];
    }

    if (empty($migraciones)) {
        return $res;
    }

    // Mapa id_viejo → id_nuevo. Lo usan el paso 3 y el comando del paso 5.
    $mapa = [];
    foreach ($migraciones as $m) {
        $mapa[$m['id_viejo']] = $m['id_nuevo'];
    }

    // --- Paso 3: copiar adyacentes del viejo al nuevo ---
    // Los destinos que también son viejos se redirigen al nuevo
    // correspondiente (para no dejar referencias a nodos que se
    // van a destruir).
    foreach ($migraciones as $m) {
        $ady_viejos = $m['viejo']->adyacentes();
        if (!$ady_viejos) continue;
        foreach ($ady_viejos as $enlace_ady => $destino) {
            $enlace_ady = (string)$enlace_ady;
            $id_destino = (string)$destino->id();
            if (isset($mapa[$id_destino])) {
                $nodo_destino = Nodo::nodo_por_id($mapa[$id_destino]);
                if ($nodo_destino) {
                    $m['nuevo']->_adyacente_en($nodo_destino, $enlace_ady);
                }
            } else {
                $m['nuevo']->_adyacente_en($destino, $enlace_ady);
            }
        }
    }

    // --- Paso 4: actualizar el enlace en `usuarios` ---
    foreach ($migraciones as $m) {
        $raiz_usuarios->_adyacente_en($m['nuevo'], $m['enlace'], true);
    }

    // --- Paso 5: redirigir referencias externas ---
    // Usa el comando `grafo:reemplazar_referencias` que tiene
    // el token encapsulado. Busca todas las aristas del grafo
    // que apunten a un nodo viejo y las redirige al nuevo.
    $resultado = Controlador::ejecutar_comando('grafo:reemplazar_referencias', $mapa);
    if (is_array($resultado) && isset($resultado['reemplazos'])) {
        $res['enlaces_actualizados'] = (int)$resultado['reemplazos'];
    } else {
        $res['errores'][] = 'El comando grafo:reemplazar_referencias no devolvió un resultado válido.';
    }

    // --- Paso 6: destruir los nodos viejos ---
    // Primero, quitarles todas las salientes (para que los
    // destinos dejen de tener referencias entrantes desde
    // los viejos). Después, destruirlos.
    foreach ($migraciones as $m) {
        $m['viejo']->eliminar_adyacentes();
    }
    foreach ($migraciones as $m) {
        if (Nodo::eliminar($m['viejo'])) {
            $res['migrados']++;
        } else {
            $res['errores'][] = "No se pudo eliminar el nodo viejo {$m['id_viejo']}";
        }
    }

    return $res;
}
?>