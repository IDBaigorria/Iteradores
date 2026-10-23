<?php
/**
 * Reparación de cancelaciones en compartidos (v1.5piloto.81t).
 *
 * Detecta cancelaciones que quedaron colgadas solo de un lado
 * (bug de v76x + _crear_nodo_cancelacion sin nombres
 * parametrizados) y las reinserta en ambos árboles:
 *   - El compartido del terminal (con hmi_<term>).
 *   - El árbol del dueño (con hmi default).
 *
 * Idempotente.
 *
 * @package   Iteradores
 * @since     1.5piloto.81t
 * @version   1.5piloto.81w
 */

use Iteradores\Nodos\Nodo;

function reparar_cancelaciones_compartidos(string $token): array {
    $res = [
        'cambios' => 0,
        'duenos' => 0,
        'compartidos' => 0,
        'reinsertadas_dueno' => 0,
        'huerfanas_detectadas' => 0,
        'reinsertadas_ambos' => 0,
        'ventas_reparadas' => 0,
        'detalles' => [],
    ];

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        $res['detalles'][] = 'No existe la raíz usuarios.';
        return $res;
    }

    // ─── Paso 0: reparar ventas canceladas incompletas. ────
    // Residuo del bug §4.6: la venta tiene una cancelación
    // asociada pero no el autoenlace 'cancelada' y los
    // asientos siguen vendidos. Fix 1.5piloto.81w.
    $res['ventas_reparadas'] = 0;
    _reparar_ventas_canceladas_incompletas($token, $res);

    // ─── Paso 1: para cada compartido, agregar al árbol del
    // dueño las cancelaciones que falten. ───────────────────
    foreach ($raiz->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nombre_dueno = (string)$nombre_dueno;
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        $nodo_dueno_real = Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno_real) continue;

        foreach ($nodo_dueno_real->adyacentes() as $enlace => $nodo_comp) {
            $enlace = (string)$enlace;
            if (strpos($enlace, 'compartido_con_') !== 0) continue;
            if (!$nodo_comp->adyacente('_es_compartido')) continue;
            $res['compartidos']++;

            $nombre_term = _nombre_terminal_de_compartido($nodo_comp);
            if ($nombre_term === '') continue;
            $nombres_term = [
                'p'   => 'p_'   . $nombre_term,
                'hd'  => 'hd_'  . $nombre_term,
                'hmi' => 'hmi_' . $nombre_term,
            ];

            $cont_comp = $nodo_comp->adyacente('cancelaciones');
            if (!$cont_comp) continue;
            $cont_dueno = $nodo_dueno_real->adyacente('cancelaciones');
            if (!$cont_dueno) {
                $cont_dueno = Nodo::crear_con_dato('');
                $nodo_dueno_real->_adyacente_en($cont_dueno, 'cancelaciones');
            }

            $actual = hmi($cont_comp, $nombres_term);
            $seg = 0;
            while ($actual && $seg < 2000) {
                $seg++;
                $id_canc = (string)$actual->dato();
                if (!_nodo_esta_en_arbol($cont_dueno, $id_canc)) {
                    _hmi($cont_dueno, $actual);
                    $res['reinsertadas_dueno']++;
                    $res['cambios']++;
                    $res['detalles'][] = "$nombre_dueno: $id_canc reinsertada en árbol del dueño (desde compartido de $nombre_term)";
                }
                $actual = hd($actual, $nombres_term);
            }
        }
    }

    // ─── Paso 2: detectar huérfanas y reinsertarlas. ────────
    $con_hmi = [];
    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$con_hmi) {
        foreach ($nodo->adyacentes() as $enlace => $ady) {
            $enlace = (string)$enlace;
            if ($enlace !== 'hmi' && strpos($enlace, 'hmi_') !== 0) continue;
            $con_hmi[$ady->id()] = true;
        }
    }, null);

    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$res, $con_hmi) {
        $dato = (string)$nodo->dato();
        if (strpos($dato, 'cancelacion_') !== 0) return;
        $id = $nodo->id();
        if (isset($con_hmi[$id])) return;
        $res['huerfanas_detectadas']++;

        $nodo_dueno_str = $nodo->adyacente('dueno');
        $nodo_terminal = $nodo->adyacente('terminal');
        if (!$nodo_dueno_str || !$nodo_terminal) {
            $res['detalles'][] = "$dato: huérfana sin dueno o terminal";
            return;
        }
        $nombre_dueno = (string)$nodo_dueno_str->dato();
        $nombre_term = (string)$nodo_terminal->dato();

        $nodo_dueno_real = Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno_real) {
            $res['detalles'][] = "$dato: no existe us_$nombre_dueno";
            return;
        }

        $nodo_comp = null;
        foreach ($nodo_dueno_real->adyacentes() as $enlace => $cand) {
            $enlace = (string)$enlace;
            if ($enlace !== 'compartido_con_' . $nombre_term) continue;
            $nodo_comp = $cand;
            break;
        }
        if (!$nodo_comp) {
            $res['detalles'][] = "$dato: no existe compartido_con_$nombre_term en us_$nombre_dueno";
            return;
        }

        $nombres_term = [
            'p'   => 'p_'   . $nombre_term,
            'hd'  => 'hd_'  . $nombre_term,
            'hmi' => 'hmi_' . $nombre_term,
        ];

        $cont_comp = $nodo_comp->adyacente('cancelaciones');
        if (!$cont_comp) {
            $cont_comp = Nodo::crear_con_dato('');
            $nodo_comp->_adyacente_en($cont_comp, 'cancelaciones');
        }
        _hmi($cont_comp, $nodo, $nombres_term);

        $cont_dueno = $nodo_dueno_real->adyacente('cancelaciones');
        if (!$cont_dueno) {
            $cont_dueno = Nodo::crear_con_dato('');
            $nodo_dueno_real->_adyacente_en($cont_dueno, 'cancelaciones');
        }
        _hmi($cont_dueno, $nodo);

        $res['reinsertadas_ambos']++;
        $res['cambios'] += 2;
        $res['detalles'][] = "$dato: huérfana reinsertada en compartido de $nombre_term y en árbol de $nombre_dueno";
    }, null);

    return $res;
}

/**
 * Detecta ventas que tienen una cancelación asociada (por
 * id_venta) pero que NO están marcadas con el autoenlace
 * 'cancelada'. Es residuo del bug §4.6 (guardado parcial
 * fallido en hostings lentos).
 *
 * Para cada una: libera los asientos que quedaron vendidos
 * y marca la venta con 'cancelada'. Idempotente.
 *
 * @param string $token
 * @param array  $res  Por referencia.
 * @return void
 * @since 1.5piloto.81w
 */
function _reparar_ventas_canceladas_incompletas(string $token, array &$res): void {
    // Fase 1: recolectar.
    $cancelaciones_por_venta = [];
    $ventas_activas = [];

    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$cancelaciones_por_venta, &$ventas_activas) {
        $dato = (string)$nodo->dato();
        if (strpos($dato, 'cancelacion_') === 0) {
            $nodo_id_venta = $nodo->adyacente('id_venta');
            if ($nodo_id_venta) {
                $cancelaciones_por_venta[(string)$nodo_id_venta->dato()] = $dato;
            }
        } elseif (strpos($dato, 'venta_') === 0) {
            if (!$nodo->adyacente('cancelada')) {
                $ventas_activas[$dato] = $nodo;
            }
        }
    }, null);

    if (empty($cancelaciones_por_venta) || empty($ventas_activas)) return;

    // Fase 2: reparar.
    foreach ($cancelaciones_por_venta as $id_venta => $id_cancelacion) {
        if (!isset($ventas_activas[$id_venta])) continue;
        _reparar_venta_cancelada_incompleta($ventas_activas[$id_venta]);
        $res['ventas_reparadas']++;
        $res['cambios']++;
        $res['detalles'][] = "venta $id_venta: reparada (tenía $id_cancelacion pero no 'cancelada')";
    }
}

/**
 * Libera los asientos de una venta cancelada incompleta y
 * marca el nodo venta con el autoenlace 'cancelada'.
 *
 * @param Nodo $nodo_venta
 * @return void
 * @since 1.5piloto.81w
 */
function _reparar_venta_cancelada_incompleta(Nodo $nodo_venta): void {
    // 1. Liberar asientos.
    $cabeza = $nodo_venta->adyacente('asientos');
    if ($cabeza) {
        $av = $cabeza->adyacente('primer');
        $seg = 0;
        while ($av && $seg < 200) {
            $asiento = $av->adyacente('asiento');
            if ($asiento) {
                $estado = $asiento->adyacente('estado');
                if ($estado) $estado->_dato('libre');
                $asiento->eliminar_adyacente('pasajero');
                $asiento->eliminar_adyacente('venta');
            }
            $av = $av->adyacente('siguiente');
            $seg++;
        }
    }

    // 2. Marcar la venta con el autoenlace 'cancelada'.
    $nodo_venta->_adyacente_en($nodo_venta, 'cancelada');

    // 3. Actualizar contadores del micro y del viaje.
    $nodo_micro = $nodo_venta->adyacente('micro');
    $nodo_viaje = $nodo_venta->adyacente('viaje');
    if ($nodo_micro) actualizar_contadores_micro($nodo_micro);
    if ($nodo_viaje && $nodo_micro) {
        $nombre_viaje = (string)$nodo_viaje->dato();
        $nodo_dueno = $nodo_viaje->adyacente('dueno');
        $nombre_dueno = $nodo_dueno ? (string)$nodo_dueno->dato() : '';
        if ($nombre_dueno !== '') {
            actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);
        }
    }
}

/**
 * Repara ciclos en los árboles de cancelaciones.
 *
 * Recorre cada árbol de cancelaciones:
 *   - El del dueño (nombres default hmi/hd/p).
 *   - Los de cada compartido (parametrizados hmi_<term>/hd_<term>/p_<term>).
 *
 * Si detecta un ciclo (un nodo ya visitado), desenlaza TODAS
 * las aristas del árbol y lo reconstruye como cadena lineal,
 * preservando el orden de visita. Idempotente.
 *
 * La causa raíz de los ciclos es el bug de _hmi al reinsertar
 * nodos que ya estaban en la cadena (fix en v1.5piloto.81v).
 *
 * @param string $token
 * @return array
 * @since 1.5piloto.81v
 */
function reparar_cancelaciones_arboles(string $token): array {
    require_once __DIR__ . '/../../miscelaneas/Arbol.php';

    $res = [
        'cambios' => 0,
        'duenos' => 0,
        'compartidos' => 0,
        'arboles_revisados' => 0,
        'arboles_reconstruidos' => 0,
        'detalles' => [],
    ];

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        $res['detalles'][] = 'No existe la raíz usuarios.';
        return $res;
    }

    foreach ($raiz->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nombre_dueno = (string)$nombre_dueno;
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos']++;

        $nodo_dueno_real = Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno_real) continue;

        // Compartidos (parametrizados).
        foreach ($nodo_dueno_real->adyacentes() as $enlace => $nodo_comp) {
            $enlace = (string)$enlace;
            if (strpos($enlace, 'compartido_con_') !== 0) continue;
            if (!$nodo_comp->adyacente('_es_compartido')) continue;
            $res['compartidos']++;

            $nombre_term = _nombre_terminal_de_compartido($nodo_comp);
            if ($nombre_term === '') continue;

            $cont = $nodo_comp->adyacente('cancelaciones');
            if (!$cont) continue;

            $nombres = [
                'p'   => 'p_'   . $nombre_term,
                'hd'  => 'hd_'  . $nombre_term,
                'hmi' => 'hmi_' . $nombre_term,
            ];

            $res['arboles_revisados']++;
            $r = _reconstruir_arbol_cadena_si_ciclo($cont, $nombres);
            if ($r['reconstruido']) {
                $res['arboles_reconstruidos']++;
                $res['cambios'] += $r['cambios'];
                $res['detalles'][] = "$nombre_dueno/$nombre_term: " . $r['detalle'];
            }
        }

        // Árbol del dueño (default).
        $cont = $nodo_dueno_real->adyacente('cancelaciones');
        if ($cont) {
            $res['arboles_revisados']++;
            $r = _reconstruir_arbol_cadena_si_ciclo($cont, null);
            if ($r['reconstruido']) {
                $res['arboles_reconstruidos']++;
                $res['cambios'] += $r['cambios'];
                $res['detalles'][] = "$nombre_dueno/default: " . $r['detalle'];
            }
        }
    }

    return $res;
}

/**
 * Detecta ciclo en la cadena del contenedor. Si hay ciclo,
 * desenlaza y reconstruye como cadena lineal. Si no hay, no
 * toca nada.
 *
 * @param Nodo       $contenedor
 * @param array|null $nombres
 * @return array {reconstruido: bool, cambios: int, detalle: string}
 */
function _reconstruir_arbol_cadena_si_ciclo(Nodo $contenedor, ?array $nombres): array {
    $n = _arbol_nombres($nombres);
    $hmi_enl = $n['hmi'];
    $hd_enl  = $n['hd'];
    $p_enl   = $n['p'];

    // 1. Recorrer la cadena detectando ciclo.
    $nodos = [];
    $visto = [];
    $actual = $contenedor->adyacente($hmi_enl);
    $seg = 0;
    $hay_ciclo = false;
    while ($actual && $seg < 5000) {
        $id = (string)$actual->id();
        if (isset($visto[$id])) {
            $hay_ciclo = true;
            break;
        }
        $visto[$id] = true;
        $nodos[] = $actual;
        $actual = $actual->adyacente($hd_enl);
        $seg++;
    }

    if (empty($nodos)) {
        return ['reconstruido' => false, 'cambios' => 0, 'detalle' => ''];
    }
    if (!$hay_ciclo) {
        return ['reconstruido' => false, 'cambios' => 0, 'detalle' => ''];
    }

    // 2. Desenlazar todas las aristas del árbol.
    foreach ($nodos as $nodo) {
        $nodo->eliminar_adyacente($hd_enl);
        $nodo->eliminar_adyacente($p_enl);
    }
    $contenedor->eliminar_adyacente($hmi_enl);

    // 3. Reencadenar en el orden de visita.
    $contenedor->_adyacente_en($nodos[0], $hmi_enl, true);
    $nodos[0]->_adyacente_en($contenedor, $p_enl, true);
    $cant = count($nodos);
    for ($i = 1; $i < $cant; $i++) {
        $nodos[$i - 1]->_adyacente_en($nodos[$i], $hd_enl, true);
        $nodos[$i]->_adyacente_en($contenedor, $p_enl, true);
    }

    return [
        'reconstruido' => true,
        'cambios' => $cant + 1,
        'detalle' => "ciclo roto: $cant nodos reencadenados",
    ];
}