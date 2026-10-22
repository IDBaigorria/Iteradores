<?php
/**
 * Reparación de árboles hmi/hd/p.
 *
 * El bug §1.5 borró aristas `hmi` de contenedores al
 * guardar parcial. Este módulo las reconstruye a partir
 * de los hijos con `p -> contenedor`.
 *
 * Recorre cada dueño y cada compartido, y para cada
 * contenedor de árbol (ventas, rendiciones,
 * liquidaciones, cancelaciones) reconstruye el `hmi`.
 * También limpia nodos `cabeza` residuales que sean
 * auto-loops vacíos.
 *
 * Idempotente: correrlo dos veces no rompe nada.
 *
 * Se expone como comando `app:reparar_arboles`. El
 * token de seguridad lo entrega el Controlador al
 * invocar el comando.
 *
 * @package   Iteradores
 * @since     1.5piloto.78z
 * @version   1.5piloto.81n
 */

use Iteradores\Nodos\Nodo;

/**
 * Precalcula los mapas `por_padre` y `es_dest_hd` para
 * todos los nodos del grafo. Un solo pase con el token.
 *
 * @param string $token
 * @return array{por_padre: array, es_dest_hd: array}
 */
function _reparar_arboles_precalcular(string $token): array {
    // v1.5piloto.81n: indexar por nombre de enlace, no por
    // nombre fijo. Los compartidos usan p_<term> y hd_<term>;
    // con el indexado viejo, $por_padre[$id_cont] daba vacío
    // para esos contenedores y _reparar_arbol los trataba
    // como "sin hijos", borrando el hmi_<term> del compartido.
    //
    // Estructura:
    //   $por_padre[$enlace_p][$id_padre] = [Nodo, ...]
    //   $es_dest_hd[$enlace_hd][$id_nodo] = true
    // donde $enlace_p / $enlace_hd pueden ser "p"/"hd"
    // o "p_<term>"/"hd_<term>".
    $por_padre = [];
    $es_dest_hd = [];
    Nodo::por_cada_nodo_ejecutar(
        $token,
        function ($nodo) use (&$por_padre, &$es_dest_hd) {
            $ady = (array)$nodo->adyacentes();
            foreach ($ady as $enlace => $destino) {
                $enlace = (string)$enlace;
                if ($enlace === 'p' || strpos($enlace, 'p_') === 0) {
                    $por_padre[$enlace][$destino->id()][] = $nodo;
                }
                if ($enlace === 'hd' || strpos($enlace, 'hd_') === 0) {
                    $es_dest_hd[$enlace][$destino->id()] = true;
                }
            }
        },
        null
    );
    return ['por_padre' => $por_padre, 'es_dest_hd' => $es_dest_hd];
}

/**
 * Repara el árbol de un contenedor. Reconstruye el `hmi`
 * si falta y limpia nodos `cabeza` residuales.
 *
 * @param Nodo       $contenedor
 * @param array|null $nombres    Nombres de enlace o null para default.
 * @param array      $por_padre  Mapa [id_padre => [Nodo, ...]] (precalculado).
 * @param array      $es_dest_hd Mapa [id_nodo => true] de los destinos de `hd`.
 * @return array { cambios: int, detalles: [] }
 */
function _reparar_arbol(Nodo $contenedor, ?array $nombres, array $por_padre, array $es_dest_hd): array {
    $n = _arbol_nombres($nombres);
    $id_cont = $contenedor->id();
    $cambios = 0;
    $detalles = [];

    // v1.5piloto.81n: consultar por el enlace concreto del
    // árbol (default o parametrizado), no por nombre fijo.
    $hijos = $por_padre[$n['p']][$id_cont] ?? [];
    $es_dest_hd_del_arbol = $es_dest_hd[$n['hd']] ?? [];

    $primer = null;
    $candidatos = 0;
    foreach ($hijos as $hijo) {
        $id_h = $hijo->id();
        if (!isset($es_dest_hd_del_arbol[$id_h])) {
            $primer = $hijo;
            $candidatos++;
        }
    }

    if (empty($hijos)) {
        // Guard defensivo (v1.5piloto.81n): antes de borrar
        // el hmi, verificar que el contenedor realmente no
        // tenga hijos navegables. Si el precalcular falló
        // (mapa incompleto), no queremos borrar un hmi
        // que sí es válido. Se navega el hmi y se chequea
        // que el nodo destino exista y tenga p al contenedor.
        $hmi_actual = $contenedor->adyacente($n['hmi']);
        $hmi_es_valido = false;
        if ($hmi_actual) {
            $p_del_hmi = $hmi_actual->adyacente($n['p']);
            if ($p_del_hmi && $p_del_hmi->id() === $id_cont) {
                $hmi_es_valido = true;
            }
        }
        if ($hmi_es_valido) {
            $detalles[] = "contenedor $id_cont: hmi apunta a hijo con p correcto, no se borra (revisar precalcular)";
        } elseif ($hmi_actual) {
            $contenedor->eliminar_adyacente($n['hmi']);
            $cambios++;
            $detalles[] = "contenedor $id_cont: hmi borrado (sin hijos, hmi apuntaba a nodo sin p)";
        }
    } elseif ($candidatos === 0) {
        $detalles[] = "contenedor $id_cont: estructura circular, no se toca";
    } elseif ($candidatos > 1) {
        $detalles[] = "contenedor $id_cont: $candidatos candidatos a primer hijo, no se toca";
    } else {
        $hmi_actual = $contenedor->adyacente($n['hmi']);
        if (!$hmi_actual) {
            $contenedor->_adyacente_en($primer, $n['hmi']);
            $cambios++;
            $detalles[] = "contenedor $id_cont: hmi -> " . $primer->id();
        } elseif ($hmi_actual->id() !== $primer->id()) {
            $contenedor->_adyacente_en($primer, $n['hmi'], true);
            $cambios++;
            $detalles[] = "contenedor $id_cont: hmi corregido a " . $primer->id();
        }
    }

    $nodo_cabeza = $contenedor->adyacente('cabeza');
    if ($nodo_cabeza) {
        $ady_cabeza = (array)$nodo_cabeza->adyacentes();
        $es_residuo = false;
        if (count($ady_cabeza) === 1 && isset($ady_cabeza['primer'])) {
            if ($ady_cabeza['primer']->id() === $nodo_cabeza->id()) {
                $es_residuo = true;
            }
        }
        if ($es_residuo) {
            $nodo_cabeza->eliminar_adyacente('primer');
            $contenedor->eliminar_adyacente('cabeza');
            Nodo::eliminar($nodo_cabeza);
            $cambios++;
            $detalles[] = "contenedor $id_cont: cabeza residual limpiado";
        } else {
            $detalles[] = "contenedor $id_cont: cabeza con contenido, no se toca";
        }
    }

    return ['cambios' => $cambios, 'detalles' => $detalles];
}

/**
 * Repara todos los árboles del grafo cargado.
 *
 * @param string $token
 * @return array Resumen { cambios, detalles, por_categoria }
 */
function reparar_arboles(string $token): array {
    $pre = _reparar_arboles_precalcular($token);
    $por_padre = $pre['por_padre'];
    $es_dest_hd = $pre['es_dest_hd'];

    $total_cambios = 0;
    $total_detalles = [];
    $por_categoria = [
        'duenos' => 0,
        'compartidos' => 0,
        'cupones' => 0,
    ];

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        return ['cambios' => 0, 'detalles' => ['No existe el nodo usuarios.'], 'por_categoria' => $por_categoria];
    }
    $ady_usuarios = (array)$raiz->adyacentes();

    foreach ($ady_usuarios as $nombre_usuario => $nodo_usuario) {
        $nivel = $nodo_usuario->adyacente('nivel');
        if (!$nivel) {
            $pub = $nodo_usuario->adyacente('publico');
            if ($pub) $nivel = $pub->adyacente('nivel');
        }
        $nivel_str = $nivel ? $nivel->dato() : '';
        if ($nivel_str !== 'dueno') continue;

        $enlaces_arbol = ['ventas', 'rendiciones', 'liquidaciones', 'cancelaciones'];
        foreach ($enlaces_arbol as $enlace) {
            $cont = $nodo_usuario->adyacente($enlace);
            if (!$cont) continue;
            $res = _reparar_arbol($cont, null, $por_padre, $es_dest_hd);
            $total_cambios += $res['cambios'];
            foreach ($res['detalles'] as $d) {
                $total_detalles[] = "[$nombre_usuario/$enlace] $d";
                $por_categoria['duenos']++;
            }
        }

        $ady_dueno = (array)$nodo_usuario->adyacentes();
        foreach ($ady_dueno as $enlace_comp => $nodo_comp) {
            $enlace_comp = (string)$enlace_comp;
            // Los compartidos se llaman `compartido_con_<nombre>`
            // (con el nombre de usuario del terminal, sin
            // prefijo `us_`). Bug de filtro detectado al
            // revisar este helper (el mismo que tenía
            // _procesar_pendientes_del_dueno en Venta.php).
            if (strpos($enlace_comp, 'compartido_con_') !== 0) continue;
            if (!$nodo_comp->adyacente('_es_compartido')) continue;

            $nombre_terminal = substr($enlace_comp, strlen('compartido_con_'));
            if ($nombre_terminal === '') continue;

            $nombres_comp = [
                'p'   => 'p_'   . $nombre_terminal,
                'hd'  => 'hd_'  . $nombre_terminal,
                'hmi' => 'hmi_' . $nombre_terminal,
            ];

            foreach (['ventas', 'cancelaciones'] as $enlace) {
                $cont = $nodo_comp->adyacente($enlace);
                if (!$cont) continue;
                $res = _reparar_arbol($cont, $nombres_comp, $por_padre, $es_dest_hd);
                $total_cambios += $res['cambios'];
                foreach ($res['detalles'] as $d) {
                    $total_detalles[] = "[$nombre_usuario/$enlace_comp/$enlace] $d";
                    $por_categoria['compartidos']++;
                }
            }
        }

        $cont_ventas = $nodo_usuario->adyacente('ventas');
        if ($cont_ventas) {
            $hijos_ventas = $por_padre[$cont_ventas->id()] ?? [];
            foreach ($hijos_ventas as $nodo_venta) {
                $cont_cup = $nodo_venta->adyacente('cupones');
                if (!$cont_cup) continue;
                $res = _reparar_arbol($cont_cup, null, $por_padre, $es_dest_hd);
                $total_cambios += $res['cambios'];
                foreach ($res['detalles'] as $d) {
                    $total_detalles[] = "[$nombre_usuario/ventas/" . $nodo_venta->dato() . "/cupones] $d";
                    $por_categoria['cupones']++;
                }
            }
        }
    }

    return [
        'cambios' => $total_cambios,
        'detalles' => $total_detalles,
        'por_categoria' => $por_categoria,
    ];
}