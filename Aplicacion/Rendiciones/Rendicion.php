<?php
/**
 * Gestión de rendiciones de dueños.
 *
 * Una rendición congela un momento del negocio: el retiro de dinero
 * de las terminales. Guarda qué cupones se rindieron, cuándo, cuánto
 * y en qué terminales. Es un hecho inmutable.
 *
 * El preview es inmutable: lo que se ve al abrir el modal es lo que
 * se va a rendir. Si entre abrir y confirmar algo cambió (un cupón
 * se rindió en paralelo, se canceló una venta, se pagó un cupón),
 * la confirmación aborta y se pide refrescar.
 *
 * @package   Iteradores
 * @since     1.5piloto.50
 * @version   1.5piloto.55
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/Ventas/Venta.php");

/**
 * Obtiene el contenedor de rendiciones de un dueño, creándolo si no existe.
 */
function obtener_contenedor_rendiciones_dueno(string $nombre_dueno): ?Nodo {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;
    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return null;
    $nodo_rendiciones = $nodo_dueno->adyacente('rendiciones');
    if (!$nodo_rendiciones) {
        $nodo_rendiciones = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_rendiciones, 'rendiciones');
    }
    return $nodo_rendiciones;
}

/**
 * Evalúa si una venta formateada pasa los filtros del panel de Vendidos.
 *
 * Filtros soportados:
 *  - viaje: 'todos' o nombre de viaje.
 *  - vendedor: 'Todos' o nombre de usuario de terminal.
 *  - estado: 'todos' | 'pagado' | 'cuotas_pendientes'.
 *  - codigo: string a buscar en id_venta (contains, case-insensitive).
 *  - comprador: string a buscar en nombre o DNI del comprador.
 *  - fecha_desde: 'YYYY-MM-DD' (o vacío).
 *  - fecha_hasta: 'YYYY-MM-DD' (o vacío).
 *
 * No incluye filtro `rendido`: la rendición siempre opera sobre
 * cupones sin rendir.
 *
 * @param array $venta
 * @param array $filtros
 * @return bool
 */
function _aplicar_filtros_a_venta(array $venta, array $filtros): bool {
    $f_viaje = $filtros['viaje'] ?? 'todos';
    if ($f_viaje !== 'todos' && $f_viaje !== '' && ($venta['viaje'] ?? '') !== $f_viaje) return false;

    $f_vendedor = $filtros['vendedor'] ?? 'Todos';
    if ($f_vendedor !== 'Todos' && $f_vendedor !== '' && ($venta['terminal'] ?? '') !== $f_vendedor) return false;

    $f_estado = $filtros['estado'] ?? 'todos';
    if ($f_estado !== 'todos' && $f_estado !== '' && ($venta['estado_pago'] ?? '') !== $f_estado) return false;

    $f_codigo = trim((string)($filtros['codigo'] ?? ''));
    if ($f_codigo !== '' && stripos((string)($venta['id_venta'] ?? ''), $f_codigo) === false) return false;

    $f_comprador = trim((string)($filtros['comprador'] ?? ''));
    if ($f_comprador !== '') {
        $c = $venta['comprador'] ?? null;
        if (!$c) return false;
        $t = mb_strtolower($f_comprador);
        $t_dni = preg_replace('/\D+/', '', $f_comprador);
        $nombre = mb_strtolower(trim(
            ($c['nombre_completo'] ?? '') . ' ' .
            ($c['apellido'] ?? '') . ' ' .
            ($c['nombres'] ?? '')
        ));
        $match = ($nombre !== '' && mb_strpos($nombre, $t) !== false);
        if (!$match && $t_dni !== '') {
            $dni = preg_replace('/\D+/', '', (string)($c['dni'] ?? ''));
            if ($dni !== '' && strpos($dni, $t_dni) !== false) $match = true;
        }
        if (!$match) return false;
    }

    $f_desde = trim((string)($filtros['fecha_desde'] ?? ''));
    if ($f_desde !== '') {
        $fi = $venta['fecha_iso'] ?? '';
        if ($fi === '' || $fi < $f_desde) return false;
    }

    $f_hasta = trim((string)($filtros['fecha_hasta'] ?? ''));
    if ($f_hasta !== '') {
        $fi = $venta['fecha_iso'] ?? '';
        if ($fi === '' || $fi > $f_hasta) return false;
    }

    return true;
}

/**
 * Recolecta las ventas del dueño que tienen al menos un cupón pagado
 * sin rendir, aplica los filtros y devuelve la estructura interna.
 *
 * Cada venta devuelta tiene:
 *   - id_venta, nodo_venta
 *   - viaje_visible, micro_visible
 *   - terminal, terminal_nombre_real
 *   - fecha, fecha_iso
 *   - total_sin_rendir, efectivo_sin_rendir, banco_sin_rendir
 *   - cupones: [ { nodo_cupon, numero, monto, metodo_pago } ]
 *
 * @param string $nombre_dueno
 * @param array  $filtros
 * @return array
 */
function _recolectar_ventas_para_rendicion(string $nombre_dueno, array $filtros): array {
    $resultado = [];

    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor_ventas) return $resultado;

    $actual = hmi($contenedor_ventas);
    $seg = 0;
    while ($actual && $seg < 2000) {
        $seg++;
        $formateada = formatear_venta_completa($actual);

        if (!_aplicar_filtros_a_venta($formateada, $filtros)) {
            $actual = hd($actual);
            continue;
        }

        // Recolectar cupones pagados sin rendir de esta venta.
        $cupones_venta = [];
        $total_sin_rendir = 0.0;
        $efectivo_sin_rendir = 0.0;
        $banco_sin_rendir = 0.0;

        $contenedor_cupones = $actual->adyacente('cupones');
        if ($contenedor_cupones) {
            $cupon = hmi($contenedor_cupones);
            $seg_cup = 0;
            while ($cupon && $seg_cup < 200) {
                $seg_cup++;
                $estado = $cupon->adyacente('estado') ? $cupon->adyacente('estado')->dato() : '';
                $tiene_rendido = $cupon->adyacente('rendido') !== null;
                if ($estado === 'pagado' && !$tiene_rendido) {
                    $numero = $cupon->adyacente('numero') ? $cupon->adyacente('numero')->dato() : '';
                    $monto = (float)($cupon->adyacente('monto') ? $cupon->adyacente('monto')->dato() : '0');
                    $metodo_cupon = $cupon->adyacente('metodo_pago') ? $cupon->adyacente('metodo_pago')->dato() : '';
                    if ($metodo_cupon === '') {
                        $metodo_cupon = $formateada['metodo_pago'] ?? 'efectivo';
                    }
                    $cupones_venta[] = [
                        'nodo_cupon' => $cupon,
                        'numero' => $numero,
                        'monto' => number_format($monto, 2, '.', ''),
                        'metodo_pago' => $metodo_cupon,
                    ];
                    $total_sin_rendir += $monto;
                    if ($metodo_cupon === 'transferencia') $banco_sin_rendir += $monto;
                    else $efectivo_sin_rendir += $monto;
                }
                $cupon = hd($cupon);
            }
        }

        if (!empty($cupones_venta)) {
            $resultado[] = [
                'id_venta' => $formateada['id_venta'] ?? '',
                'nodo_venta' => $actual,
                'viaje_visible' => $formateada['viaje_visible'] ?? '',
                'micro_visible' => $formateada['micro_nombre_visible'] ?? '',
                'terminal' => $formateada['terminal'] ?? '',
                'terminal_nombre_real' => $formateada['terminal_nombre_real'] ?? '',
                'fecha' => $formateada['fecha'] ?? '',
                'fecha_iso' => $formateada['fecha_iso'] ?? '',
                'total_sin_rendir' => number_format($total_sin_rendir, 2, '.', ''),
                'efectivo_sin_rendir' => number_format($efectivo_sin_rendir, 2, '.', ''),
                'banco_sin_rendir' => number_format($banco_sin_rendir, 2, '.', ''),
                'cupones' => $cupones_venta,
            ];
        }

        $actual = hd($actual);
    }

    return $resultado;
}

/**
 * Previsualiza la rendición sin modificar nada del grafo.
 *
 * Devuelve todos los datos necesarios para el modal: totales, detalle
 * por terminal y lista de ventas con sus cupones sin rendir. También
 * propone un id_rendicion y una fecha_hora.
 *
 * @param string $nombre_dueno
 * @param array  $filtros
 * @return array
 */
function previsualizar_rendicion(string $nombre_dueno, array $filtros): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $ventas = _recolectar_ventas_para_rendicion($nombre_dueno, $filtros);

    $total = 0.0;
    $total_efectivo = 0.0;
    $total_banco = 0.0;
    $cantidad_cupones = 0;

    $por_terminal = [];
    foreach ($ventas as $v) {
        $total += (float)$v['total_sin_rendir'];
        $total_efectivo += (float)$v['efectivo_sin_rendir'];
        $total_banco += (float)$v['banco_sin_rendir'];
        $cantidad_cupones += count($v['cupones']);

        $t = $v['terminal'];
        if (!isset($por_terminal[$t])) {
            $por_terminal[$t] = [
                'terminal' => $t,
                'terminal_nombre_real' => $v['terminal_nombre_real'],
                'total' => 0.0,
                'efectivo' => 0.0,
                'banco' => 0.0,
                'cantidad_cupones' => 0,
            ];
        }
        $por_terminal[$t]['total'] += (float)$v['total_sin_rendir'];
        $por_terminal[$t]['efectivo'] += (float)$v['efectivo_sin_rendir'];
        $por_terminal[$t]['banco'] += (float)$v['banco_sin_rendir'];
        $por_terminal[$t]['cantidad_cupones'] += count($v['cupones']);
    }
    foreach ($por_terminal as &$t) {
        $t['total'] = number_format($t['total'], 2, '.', '');
        $t['efectivo'] = number_format($t['efectivo'], 2, '.', '');
        $t['banco'] = number_format($t['banco'], 2, '.', '');
    }
    unset($t);

    // Serializar ventas sin nodos (solo datos).
    $ventas_serializables = [];
    foreach ($ventas as $v) {
        $cupones_serializables = [];
        foreach ($v['cupones'] as $c) {
            $cupones_serializables[] = [
                'numero' => $c['numero'],
                'monto' => $c['monto'],
                'metodo_pago' => $c['metodo_pago'],
            ];
        }
        $ventas_serializables[] = [
            'id_venta' => $v['id_venta'],
            'viaje_visible' => $v['viaje_visible'],
            'micro_visible' => $v['micro_visible'],
            'terminal' => $v['terminal'],
            'terminal_nombre_real' => $v['terminal_nombre_real'],
            'fecha' => $v['fecha'],
            'total_sin_rendir' => $v['total_sin_rendir'],
            'cupones' => $cupones_serializables,
        ];
    }

    return [
        'exito' => true,
        'preview' => [
            'id_rendicion_propuesto' => 'rendicion_' . time(),
            'fecha_hora' => date('d/m/Y H:i'),
            'total' => number_format($total, 2, '.', ''),
            'total_efectivo' => number_format($total_efectivo, 2, '.', ''),
            'total_banco' => number_format($total_banco, 2, '.', ''),
            'cantidad_cupones' => $cantidad_cupones,
            'cantidad_ventas' => count($ventas),
            'por_terminal' => array_values($por_terminal),
            'ventas' => $ventas_serializables,
        ],
    ];
}

/**
 * Verifica si ya existe una rendición con ese id en el contenedor.
 */
function _existe_rendicion(Nodo $contenedor, string $id_rendicion): bool {
    $actual = hmi($contenedor);
    $seg = 0;
    while ($actual && $seg < 500) {
        if ($actual->dato() === $id_rendicion) return true;
        $actual = hd($actual);
        $seg++;
    }
    return false;
}

/**
 * Confirma la rendición: revalida, crea el nodo y marca los cupones.
 *
 * El parámetro $ventas_seleccionadas es un array de elementos:
 *   [ { id_venta: 'venta_123', cupones: ['1', '3'] }, ... ]
 *
 * Se rinden exactamente los cupones indicados. Si algo cambió desde
 * el preview (un cupón se rindió, se canceló la venta, se pagó un
 * cupón nuevo fuera de la lista), aborta sin escribir nada.
 *
 * @param string $nombre_dueno
 * @param array  $ventas_seleccionadas
 * @param array  $filtros
 * @return array
 */
function confirmar_rendicion(string $nombre_dueno, array $ventas_seleccionadas, array $filtros): array {
    if (empty($ventas_seleccionadas)) {
        return ['exito' => false, 'error' => 'No hay ventas seleccionadas para rendir'];
    }

    // Revalidar contra el estado actual.
    $recolectadas = _recolectar_ventas_para_rendicion($nombre_dueno, $filtros);
    $por_id = [];
    foreach ($recolectadas as $v) {
        $por_id[$v['id_venta']] = $v;
    }

    // Verificar cada venta seleccionada y cada cupón indicado.
    $cupones_a_rendir = [];
    foreach ($ventas_seleccionadas as $sel) {
        $id_v = $sel['id_venta'] ?? '';
        $numeros = $sel['cupones'] ?? [];
        if ($id_v === '' || !is_array($numeros) || empty($numeros)) {
            return ['exito' => false, 'error' => 'El estado cambió. Refrescá y volvé a intentar.'];
        }
        if (!isset($por_id[$id_v])) {
            return ['exito' => false, 'error' => 'El estado cambió. Refrescá y volvé a intentar.'];
        }
        $v = $por_id[$id_v];
        $cupones_indexados = [];
        foreach ($v['cupones'] as $c) {
            $cupones_indexados[(string)$c['numero']] = $c;
        }
        foreach ($numeros as $num) {
            $num = (string)$num;
            if (!isset($cupones_indexados[$num])) {
                return ['exito' => false, 'error' => 'El estado cambió. Refrescá y volvé a intentar.'];
            }
            $c = $cupones_indexados[$num];
            $cupones_a_rendir[] = [
                'nodo_cupon' => $c['nodo_cupon'],
                'monto' => $c['monto'],
                'metodo_pago' => $c['metodo_pago'],
                'terminal' => $v['terminal'],
                'terminal_nombre_real' => $v['terminal_nombre_real'],
                'id_venta' => $id_v,
                'numero' => $num,
            ];
        }
    }

    if (empty($cupones_a_rendir)) {
        return ['exito' => false, 'error' => 'No hay cupones para rendir'];
    }

    // Obtener contenedor y generar id único.
    $contenedor = obtener_contenedor_rendiciones_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'No se pudo obtener el contenedor de rendiciones'];

    $id_rendicion = 'rendicion_' . time();
    $intentos = 0;
    while (_existe_rendicion($contenedor, $id_rendicion) && $intentos < 5) {
        $id_rendicion = 'rendicion_' . time() . '_' . rand(100, 999);
        $intentos++;
    }

    // Crear nodo rendición.
    $nodo_rendicion = Nodo::crear_con_dato($id_rendicion);
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'dueno');
    $fecha_hora = date('d/m/Y H:i');
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato($fecha_hora), 'fecha_hora');

    // Totales y agrupación por terminal.
    $total = 0.0;
    $total_efectivo = 0.0;
    $total_banco = 0.0;
    $por_terminal = [];
    foreach ($cupones_a_rendir as $c) {
        $monto = (float)$c['monto'];
        $total += $monto;
        if ($c['metodo_pago'] === 'transferencia') $total_banco += $monto;
        else $total_efectivo += $monto;

        $t = $c['terminal'];
        if (!isset($por_terminal[$t])) {
            $por_terminal[$t] = [
                'terminal' => $t,
                'terminal_nombre_real' => $c['terminal_nombre_real'],
                'total' => 0.0,
                'efectivo' => 0.0,
                'banco' => 0.0,
                'cantidad_cupones' => 0,
            ];
        }
        $por_terminal[$t]['total'] += $monto;
        if ($c['metodo_pago'] === 'transferencia') $por_terminal[$t]['banco'] += $monto;
        else $por_terminal[$t]['efectivo'] += $monto;
        $por_terminal[$t]['cantidad_cupones']++;
    }

    $ventas_distintas = [];
    foreach ($cupones_a_rendir as $c) $ventas_distintas[$c['id_venta']] = true;

    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato(number_format($total, 2, '.', '')), 'total');
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato(number_format($total_efectivo, 2, '.', '')), 'total_efectivo');
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato(number_format($total_banco, 2, '.', '')), 'total_banco');
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato((string)count($cupones_a_rendir)), 'cantidad_cupones');
    $nodo_rendicion->_adyacente_en(Nodo::crear_con_dato((string)count($ventas_distintas)), 'cantidad_ventas');

    // Detalle por terminal (lista árbol).
    $contenedor_terminales = Nodo::crear_con_dato('');
    $nodo_rendicion->_adyacente_en($contenedor_terminales, 'detalle_terminales');
    $anterior_t = null;
    foreach (array_values($por_terminal) as $idx => $t) {
        $nodo_t = Nodo::crear_con_dato('');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato($t['terminal']), 'terminal');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato($t['terminal_nombre_real']), 'terminal_nombre_real');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato(number_format($t['total'], 2, '.', '')), 'total');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato(number_format($t['efectivo'], 2, '.', '')), 'efectivo');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato(number_format($t['banco'], 2, '.', '')), 'banco');
        $nodo_t->_adyacente_en(Nodo::crear_con_dato((string)$t['cantidad_cupones']), 'cantidad_cupones');
        if ($idx === 0) {
            _hmi($contenedor_terminales, $nodo_t);
        } else {
            _hd($anterior_t, $nodo_t);
        }
        $anterior_t = $nodo_t;
    }

    // Detalle de cupones (lista árbol).
    $contenedor_detalle = Nodo::crear_con_dato('');
    $nodo_rendicion->_adyacente_en($contenedor_detalle, 'detalle_cupones');
    $anterior_c = null;
    foreach ($cupones_a_rendir as $idx => $c) {
        $nodo_det = Nodo::crear_con_dato('');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['id_venta']), 'venta_id');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['numero']), 'numero_cupon');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['monto']), 'monto');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['metodo_pago']), 'metodo_pago');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['terminal']), 'terminal');
        $nodo_det->_adyacente_en(Nodo::crear_con_dato($c['terminal_nombre_real']), 'terminal_nombre_real');
        if ($idx === 0) {
            _hmi($contenedor_detalle, $nodo_det);
        } else {
            _hd($anterior_c, $nodo_det);
        }
        $anterior_c = $nodo_det;
    }

    // Marcar cada cupón con `rendido` -> nodo rendición.
    foreach ($cupones_a_rendir as $c) {
        $nodo_cupon = $c['nodo_cupon'];
        $nodo_cupon->_adyacente_en($nodo_rendicion, 'rendido');
    }

    // Mover el dinero: se descuenta del saldo de cada terminal y se
    // suma a las cuentas del dueño. Al descontar de la terminal se usa
    // max(0, ...) para no dejar saldos negativos si algo quedó desfasado.
    $raiz_usuarios_mov = Nodo::nodo_por_id('usuarios');
    $nodo_dueno_mov = $raiz_usuarios_mov ? $raiz_usuarios_mov->adyacente($nombre_dueno) : null;

    if ($nodo_dueno_mov) {
        // Descontar de cada terminal involucrada.
        foreach (array_values($por_terminal) as $t) {
            $nodo_term = $raiz_usuarios_mov->adyacente($t['terminal']);
            if (!$nodo_term) continue;

            if ($t['efectivo'] > 0.001) {
                $nodo_ef_t = $nodo_term->adyacente('efectivo');
                if ($nodo_ef_t) {
                    $nuevo = max(0, (float)$nodo_ef_t->dato() - $t['efectivo']);
                    $nodo_ef_t->_dato((string)$nuevo);
                }
            }
            if ($t['banco'] > 0.001) {
                $nodo_ba_t = $nodo_term->adyacente('banco');
                if ($nodo_ba_t) {
                    $nuevo = max(0, (float)$nodo_ba_t->dato() - $t['banco']);
                    $nodo_ba_t->_dato((string)$nuevo);
                }
            }
        }

        // Sumar a las cuentas del dueño.
        $nodo_ef_d = $nodo_dueno_mov->adyacente('efectivo');
        if (!$nodo_ef_d) {
            $nodo_ef_d = Nodo::crear_con_dato('0');
            $nodo_dueno_mov->_adyacente_en($nodo_ef_d, 'efectivo');
        }
        $nuevo_ef_d = (float)$nodo_ef_d->dato() + $total_efectivo;
        $nodo_ef_d->_dato((string)$nuevo_ef_d);

        $nodo_ba_d = $nodo_dueno_mov->adyacente('banco');
        if (!$nodo_ba_d) {
            $nodo_ba_d = Nodo::crear_con_dato('0');
            $nodo_dueno_mov->_adyacente_en($nodo_ba_d, 'banco');
        }
        $nuevo_ba_d = (float)$nodo_ba_d->dato() + $total_banco;
        $nodo_ba_d->_dato((string)$nuevo_ba_d);
    }

    // Insertar la rendición al inicio del contenedor del dueño.
    _hmi($contenedor, $nodo_rendicion);

    Controlador::guardar(Conf::NOMBRE_APP);

    return [
        'exito' => true,
        'id_rendicion' => $id_rendicion,
        'total' => number_format($total, 2, '.', ''),
        'cantidad_cupones' => count($cupones_a_rendir),
    ];
}

/**
 * Convierte una fecha visible "DD/MM/YYYY HH:MM" a ISO (YYYY-MM-DD).
 * Devuelve cadena vacía si no matchea el formato.
 */
function _fecha_rendicion_a_iso(string $fecha_visible): string {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $fecha_visible, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
}

/**
 * Formatea una rendición con los datos mínimos para la tabla.
 *
 * Devuelve: id_rendicion, fecha_hora, fecha_iso, total,
 * total_efectivo, total_banco, cantidad_cupones, cantidad_ventas,
 * terminales (array {terminal, terminal_nombre_real}),
 * desactualizada (bool) y motivo_desactualizada (string).
 *
 * @param Nodo $nodo_rendicion
 * @return array
 */
/**
 * Lee los ajustes de una rendición desde su enlace `desactualizada`.
 *
 * Acepta la estructura nueva (contenedor con hijos en lista árbol)
 * y un fallback para la estructura vieja (string suelto).
 *
 * @param Nodo $nodo_rendicion
 * @return array
 */
function _leer_ajustes_rendicion(Nodo $nodo_rendicion): array {
    $ajustes = [];
    $contenedor = $nodo_rendicion->adyacente('desactualizada');
    if (!$contenedor) return $ajustes;

    $hijo = hmi($contenedor);
    if (!$hijo) {
        // Fallback: string viejo. Devolver un ajuste sin id_cancelacion.
        $motivo = $contenedor->dato();
        if ($motivo !== '') {
            $ajustes[] = [
                'id_cancelacion' => '',
                'id_venta' => '',
                'fecha_hora' => '',
                'motivo' => $motivo,
                'terminal' => '',
                'terminal_nombre_real' => '',
                'monto_terminal_efectivo' => '0.00',
                'monto_terminal_banco' => '0.00',
                'monto_dueno_efectivo' => '0.00',
                'monto_dueno_banco' => '0.00',
                'monto_no_cubierto_efectivo' => '0.00',
                'monto_no_cubierto_banco' => '0.00',
                'aceptada_en' => '',
                'aceptada' => false,
            ];
        }
        return $ajustes;
    }

    $actual = $hijo;
    $seg = 0;
    while ($actual && $seg < 500) {
        $seg++;
        $ac = $actual->adyacente('aceptada_en');
        $ajustes[] = [
            'id_cancelacion' => $actual->adyacente('id_cancelacion') ? $actual->adyacente('id_cancelacion')->dato() : '',
            'id_venta' => $actual->adyacente('id_venta') ? $actual->adyacente('id_venta')->dato() : '',
            'fecha_hora' => $actual->adyacente('fecha_hora') ? $actual->adyacente('fecha_hora')->dato() : '',
            'motivo' => $actual->adyacente('motivo') ? $actual->adyacente('motivo')->dato() : '',
            'terminal' => $actual->adyacente('terminal') ? $actual->adyacente('terminal')->dato() : '',
            'terminal_nombre_real' => $actual->adyacente('terminal_nombre_real') ? $actual->adyacente('terminal_nombre_real')->dato() : '',
            'monto_terminal_efectivo' => $actual->adyacente('monto_terminal_efectivo') ? $actual->adyacente('monto_terminal_efectivo')->dato() : '0.00',
            'monto_terminal_banco' => $actual->adyacente('monto_terminal_banco') ? $actual->adyacente('monto_terminal_banco')->dato() : '0.00',
            'monto_dueno_efectivo' => $actual->adyacente('monto_dueno_efectivo') ? $actual->adyacente('monto_dueno_efectivo')->dato() : '0.00',
            'monto_dueno_banco' => $actual->adyacente('monto_dueno_banco') ? $actual->adyacente('monto_dueno_banco')->dato() : '0.00',
            'monto_no_cubierto_efectivo' => $actual->adyacente('monto_no_cubierto_efectivo') ? $actual->adyacente('monto_no_cubierto_efectivo')->dato() : '0.00',
            'monto_no_cubierto_banco' => $actual->adyacente('monto_no_cubierto_banco') ? $actual->adyacente('monto_no_cubierto_banco')->dato() : '0.00',
            'aceptada_en' => $ac ? $ac->dato() : '',
            'aceptada' => ($ac !== null),
        ];
        $actual = hd($actual);
    }
    return $ajustes;
}

function formatear_rendicion_resumida(Nodo $nodo_rendicion): array {
    $id_rendicion = $nodo_rendicion->dato();
    $nodo_fecha = $nodo_rendicion->adyacente('fecha_hora');
    $fecha_hora = $nodo_fecha ? $nodo_fecha->dato() : '';

    $terminales = [];
    $cont_term = $nodo_rendicion->adyacente('detalle_terminales');
    if ($cont_term) {
        $t = hmi($cont_term);
        $seg_t = 0;
        while ($t && $seg_t < 200) {
            $seg_t++;
            $terminales[] = [
                'terminal' => $t->adyacente('terminal') ? $t->adyacente('terminal')->dato() : '',
                'terminal_nombre_real' => $t->adyacente('terminal_nombre_real') ? $t->adyacente('terminal_nombre_real')->dato() : '',
            ];
            $t = hd($t);
        }
    }

    $ajustes = _leer_ajustes_rendicion($nodo_rendicion);
    $desactualizada = !empty($ajustes);
    $pendiente_aceptar = false;
    $motivos = [];
    foreach ($ajustes as $aj) {
        if (!$aj['aceptada']) $pendiente_aceptar = true;
        if ($aj['motivo'] !== '') $motivos[] = $aj['motivo'];
    }
    $motivo = implode('; ', $motivos);

    $nodo_dueno = $nodo_rendicion->adyacente('dueno');
    $nombre_dueno = $nodo_dueno ? $nodo_dueno->dato() : '';

    return [
        'id_rendicion' => $id_rendicion,
        'dueno' => $nombre_dueno,
        'fecha_hora' => $fecha_hora,
        'fecha_iso' => _fecha_rendicion_a_iso($fecha_hora),
        'total' => $nodo_rendicion->adyacente('total') ? $nodo_rendicion->adyacente('total')->dato() : '0',
        'total_efectivo' => $nodo_rendicion->adyacente('total_efectivo') ? $nodo_rendicion->adyacente('total_efectivo')->dato() : '0',
        'total_banco' => $nodo_rendicion->adyacente('total_banco') ? $nodo_rendicion->adyacente('total_banco')->dato() : '0',
        'cantidad_cupones' => $nodo_rendicion->adyacente('cantidad_cupones') ? $nodo_rendicion->adyacente('cantidad_cupones')->dato() : '0',
        'cantidad_ventas' => $nodo_rendicion->adyacente('cantidad_ventas') ? $nodo_rendicion->adyacente('cantidad_ventas')->dato() : '0',
        'terminales' => $terminales,
        'desactualizada' => $desactualizada,
        'motivo_desactualizada' => $motivo,
        'ajustes' => $ajustes,
        'pendiente_aceptar' => $pendiente_aceptar,
    ];
}

/**
 * Formatea una rendición con el detalle completo (terminales y cupones).
 *
 * @param Nodo $nodo_rendicion
 * @return array
 */
function formatear_rendicion_completa(Nodo $nodo_rendicion): array {
    $datos = formatear_rendicion_resumida($nodo_rendicion);

    // Detalle por terminal
    $detalle_terminales = [];
    $cont_term = $nodo_rendicion->adyacente('detalle_terminales');
    if ($cont_term) {
        $t = hmi($cont_term);
        $seg_t = 0;
        while ($t && $seg_t < 200) {
            $seg_t++;
            $detalle_terminales[] = [
                'terminal' => $t->adyacente('terminal') ? $t->adyacente('terminal')->dato() : '',
                'terminal_nombre_real' => $t->adyacente('terminal_nombre_real') ? $t->adyacente('terminal_nombre_real')->dato() : '',
                'total' => $t->adyacente('total') ? $t->adyacente('total')->dato() : '0',
                'efectivo' => $t->adyacente('efectivo') ? $t->adyacente('efectivo')->dato() : '0',
                'banco' => $t->adyacente('banco') ? $t->adyacente('banco')->dato() : '0',
                'cantidad_cupones' => $t->adyacente('cantidad_cupones') ? $t->adyacente('cantidad_cupones')->dato() : '0',
            ];
            $t = hd($t);
        }
    }

    // Detalle de cupones
    $detalle_cupones = [];
    $cont_cup = $nodo_rendicion->adyacente('detalle_cupones');
    if ($cont_cup) {
        $c = hmi($cont_cup);
        $seg_c = 0;
        while ($c && $seg_c < 1000) {
            $seg_c++;
            $detalle_cupones[] = [
                'venta_id' => $c->adyacente('venta_id') ? $c->adyacente('venta_id')->dato() : '',
                'numero_cupon' => $c->adyacente('numero_cupon') ? $c->adyacente('numero_cupon')->dato() : '',
                'monto' => $c->adyacente('monto') ? $c->adyacente('monto')->dato() : '0',
                'metodo_pago' => $c->adyacente('metodo_pago') ? $c->adyacente('metodo_pago')->dato() : '',
                'terminal' => $c->adyacente('terminal') ? $c->adyacente('terminal')->dato() : '',
                'terminal_nombre_real' => $c->adyacente('terminal_nombre_real') ? $c->adyacente('terminal_nombre_real')->dato() : '',
            ];
            $c = hd($c);
        }
    }

    $datos['detalle_terminales'] = $detalle_terminales;
    $datos['detalle_cupones'] = $detalle_cupones;
    return $datos;
}

/**
 * Marca un ajuste como aceptado por el dueño. No modifica saldos
 * (ya se ajustaron al cancelar la venta). Solo agrega el enlace
 * `aceptada_en` en el nodo del ajuste.
 *
 * @param string $id_rendicion
 * @param string $id_cancelacion
 * @return array
 */
function aceptar_ajuste_rendicion(string $id_rendicion, string $id_cancelacion): array {
    if ($id_rendicion === '' || $id_cancelacion === '') {
        return ['exito' => false, 'error' => 'Parámetros incompletos'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios'];

    $nodo_rendicion = null;
    foreach ($raiz->adyacentes() as $nd => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $cont = $nodo_dueno->adyacente('rendiciones');
        if (!$cont) continue;
        $actual = hmi($cont);
        $seg = 0;
        while ($actual && $seg < 2000) {
            $seg++;
            if ($actual->dato() === $id_rendicion) { $nodo_rendicion = $actual; break 2; }
            $actual = hd($actual);
        }
    }
    if (!$nodo_rendicion) return ['exito' => false, 'error' => 'Rendición no encontrada'];

    $contenedor = $nodo_rendicion->adyacente('desactualizada');
    if (!$contenedor) return ['exito' => false, 'error' => 'La rendición no tiene ajustes'];

    $ajuste = null;
    $actual = hmi($contenedor);
    $seg = 0;
    while ($actual && $seg < 500) {
        $seg++;
        $id_c = $actual->adyacente('id_cancelacion');
        if ($id_c && $id_c->dato() === $id_cancelacion) { $ajuste = $actual; break; }
        $actual = hd($actual);
    }
    if (!$ajuste) return ['exito' => false, 'error' => 'Ajuste no encontrado'];

    $acept = $ajuste->adyacente('aceptada_en');
    if ($acept) return ['exito' => false, 'error' => 'Este ajuste ya fue aceptado'];
    $ajuste->_adyacente_en(Nodo::crear_con_dato(date('d/m/Y H:i')), 'aceptada_en');

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Evalúa si una rendición formateada pasa los filtros del panel.
 *
 * Filtros soportados:
 *  - codigo: string a buscar en id_rendicion (contains, case-insensitive).
 *  - fecha_desde: 'YYYY-MM-DD' (o vacío).
 *  - fecha_hasta: 'YYYY-MM-DD' (o vacío).
 *  - terminal: nombre de usuario de terminal. Filtra las rendiciones
 *    que la incluyen en su detalle.
 *
 * @param array $rendicion
 * @param array $filtros
 * @return bool
 */
function _rendicion_pasa_filtros(array $rendicion, array $filtros): bool {
    $f_codigo = trim((string)($filtros['codigo'] ?? ''));
    if ($f_codigo !== '' && stripos((string)($rendicion['id_rendicion'] ?? ''), $f_codigo) === false) return false;

    $f_desde = trim((string)($filtros['fecha_desde'] ?? ''));
    if ($f_desde !== '') {
        $fi = $rendicion['fecha_iso'] ?? '';
        if ($fi === '' || $fi < $f_desde) return false;
    }

    $f_hasta = trim((string)($filtros['fecha_hasta'] ?? ''));
    if ($f_hasta !== '') {
        $fi = $rendicion['fecha_iso'] ?? '';
        if ($fi === '' || $fi > $f_hasta) return false;
    }

    $f_terminal = trim((string)($filtros['terminal'] ?? ''));
    if ($f_terminal !== '') {
        $encontrada = false;
        foreach (($rendicion['terminales'] ?? []) as $t) {
            if (($t['terminal'] ?? '') === $f_terminal) {
                $encontrada = true;
                break;
            }
        }
        if (!$encontrada) return false;
    }

    return true;
}

/**
 * Lista las rendiciones de un dueño, aplicando filtros.
 *
 * Devuelve las rendiciones ordenadas por más reciente primero (el
 * contenedor usa _hmi, que inserta al inicio).
 *
 * @param string $nombre_dueno
 * @param array  $filtros
 * @return array
 */
function listar_rendiciones_de_dueno(string $nombre_dueno, array $filtros = []): array {
    $contenedor = obtener_contenedor_rendiciones_dueno($nombre_dueno);
    if (!$contenedor) return [];

    $rendiciones = [];
    $actual = hmi($contenedor);
    $seg = 0;
    while ($actual && $seg < 2000) {
        $seg++;
        $resumen = formatear_rendicion_resumida($actual);
        if (_rendicion_pasa_filtros($resumen, $filtros)) {
            $rendiciones[] = $resumen;
        }
        $actual = hd($actual);
    }
    return $rendiciones;
}

/**
 * Busca una rendición por su id en todos los dueños.
 *
 * @param string $id_rendicion
 * @return array|null
 */
function obtener_rendicion_por_id(string $id_rendicion): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $contenedor = $nodo_dueno->adyacente('rendiciones');
        if (!$contenedor) continue;
        $actual = hmi($contenedor);
        $seg = 0;
        while ($actual && $seg < 2000) {
            $seg++;
            if ($actual->dato() === $id_rendicion) {
                return formatear_rendicion_completa($actual);
            }
            $actual = hd($actual);
        }
    }
    return null;
}
