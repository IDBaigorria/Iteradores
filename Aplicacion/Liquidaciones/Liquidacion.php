<?php
/**
 * Gestión de liquidaciones de dueños.
 *
 * Una liquidación representa la salida del dinero del sistema: el
 * dueño retira de sus cuentas (efectivo y banco) un monto. Es un
 * hecho inmutable.
 *
 * @package   Iteradores
 * @since     1.5piloto.54
 * @version   1.5piloto.54
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/Usuarios/Usuario.php");

/**
 * Obtiene el contenedor de liquidaciones de un dueño, creándolo si no existe.
 */
function obtener_contenedor_liquidaciones_dueno(string $nombre_dueno): ?Nodo {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;
    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return null;
    $nodo_liquidaciones = $nodo_dueno->adyacente('liquidaciones');
    if (!$nodo_liquidaciones) {
        $nodo_liquidaciones = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_liquidaciones, 'liquidaciones');
    }
    return $nodo_liquidaciones;
}

/**
 * Previsualiza una liquidación: devuelve los saldos actuales del dueño.
 * No modifica nada del grafo.
 *
 * @param string $nombre_dueno
 * @return array
 */
function previsualizar_liquidacion(string $nombre_dueno): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    return [
        'exito' => true,
        'saldos_dueno' => obtener_saldos_dueno($nombre_dueno),
    ];
}

/**
 * Verifica si ya existe una liquidación con ese id en el contenedor.
 */
function _existe_liquidacion(Nodo $contenedor, string $id_liquidacion): bool {
    $actual = hmi($contenedor);
    $seg = 0;
    while ($actual && $seg < 500) {
        if ($actual->dato() === $id_liquidacion) return true;
        $actual = hd($actual);
        $seg++;
    }
    return false;
}

/**
 * Confirma la liquidación: valida saldos, descuenta del dueño y
 * crea el Nodo Liquidación.
 *
 * Se puede extraer cualquier monto de cada cuenta, siempre que no
 * supere el saldo actual. Debe extraerse al menos 1 peso entre las
 * dos cuentas.
 *
 * @param string $nombre_dueno
 * @param string $monto_efectivo_str
 * @param string $monto_banco_str
 * @param string $observaciones
 * @return array
 */
function confirmar_liquidacion(string $nombre_dueno, string $monto_efectivo_str, string $monto_banco_str, string $observaciones): array {
    $monto_efectivo = (float)$monto_efectivo_str;
    $monto_banco = (float)$monto_banco_str;

    if ($monto_efectivo < 0 || $monto_banco < 0) {
        return ['exito' => false, 'error' => 'Los montos no pueden ser negativos'];
    }
    if ($monto_efectivo + $monto_banco <= 0.001) {
        return ['exito' => false, 'error' => 'Debe extraerse al menos un monto mayor a cero'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_ef = $nodo_dueno->adyacente('efectivo');
    $nodo_ba = $nodo_dueno->adyacente('banco');
    $efectivo_actual = $nodo_ef ? (float)$nodo_ef->dato() : 0.0;
    $banco_actual = $nodo_ba ? (float)$nodo_ba->dato() : 0.0;

    if ($monto_efectivo > $efectivo_actual + 0.001) {
        return ['exito' => false, 'error' => 'Los saldos cambiaron. Refrescá y volvé a intentar.'];
    }
    if ($monto_banco > $banco_actual + 0.001) {
        return ['exito' => false, 'error' => 'Los saldos cambiaron. Refrescá y volvé a intentar.'];
    }

    $total = $monto_efectivo + $monto_banco;
    $efectivo_restante = max(0.0, $efectivo_actual - $monto_efectivo);
    $banco_restante = max(0.0, $banco_actual - $monto_banco);

    $contenedor = obtener_contenedor_liquidaciones_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'No se pudo obtener el contenedor de liquidaciones'];

    $id_liquidacion = 'liquidacion_' . time();
    $intentos = 0;
    while (_existe_liquidacion($contenedor, $id_liquidacion) && $intentos < 5) {
        $id_liquidacion = 'liquidacion_' . time() . '_' . rand(100, 999);
        $intentos++;
    }

    $nodo_liq = Nodo::crear_con_dato($id_liquidacion);
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'dueno');
    $fecha_hora = date('d/m/Y H:i');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato($fecha_hora), 'fecha_hora');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato(number_format($total, 2, '.', '')), 'total');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato(number_format($monto_efectivo, 2, '.', '')), 'monto_efectivo');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato(number_format($monto_banco, 2, '.', '')), 'monto_banco');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato(number_format($efectivo_restante, 2, '.', '')), 'efectivo_restante');
    $nodo_liq->_adyacente_en(Nodo::crear_con_dato(number_format($banco_restante, 2, '.', '')), 'banco_restante');
    if (trim($observaciones) !== '') {
        $nodo_liq->_adyacente_en(Nodo::crear_con_dato(trim($observaciones)), 'observaciones');
    }

    if ($nodo_ef) $nodo_ef->_dato((string)$efectivo_restante);
    if ($nodo_ba) $nodo_ba->_dato((string)$banco_restante);

    _hmi($contenedor, $nodo_liq);
    Controlador::guardar(Conf::NOMBRE_APP);

    return [
        'exito' => true,
        'id_liquidacion' => $id_liquidacion,
        'total' => number_format($total, 2, '.', ''),
        'efectivo_restante' => number_format($efectivo_restante, 2, '.', ''),
        'banco_restante' => number_format($banco_restante, 2, '.', ''),
    ];
}

/**
 * Convierte una fecha visible "DD/MM/YYYY HH:MM" a ISO (YYYY-MM-DD).
 */
function _fecha_liquidacion_a_iso(string $fecha_visible): string {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $fecha_visible, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
}

/**
 * Formatea una liquidación con los datos mínimos para la tabla.
 */
function formatear_liquidacion_resumida(Nodo $nodo_liq): array {
    $id_liquidacion = $nodo_liq->dato();
    $nodo_fecha = $nodo_liq->adyacente('fecha_hora');
    $fecha_hora = $nodo_fecha ? $nodo_fecha->dato() : '';
    $nodo_dueno = $nodo_liq->adyacente('dueno');

    return [
        'id_liquidacion' => $id_liquidacion,
        'dueno' => $nodo_dueno ? $nodo_dueno->dato() : '',
        'fecha_hora' => $fecha_hora,
        'fecha_iso' => _fecha_liquidacion_a_iso($fecha_hora),
        'total' => $nodo_liq->adyacente('total') ? $nodo_liq->adyacente('total')->dato() : '0.00',
        'monto_efectivo' => $nodo_liq->adyacente('monto_efectivo') ? $nodo_liq->adyacente('monto_efectivo')->dato() : '0.00',
        'monto_banco' => $nodo_liq->adyacente('monto_banco') ? $nodo_liq->adyacente('monto_banco')->dato() : '0.00',
        'efectivo_restante' => $nodo_liq->adyacente('efectivo_restante') ? $nodo_liq->adyacente('efectivo_restante')->dato() : '0.00',
        'banco_restante' => $nodo_liq->adyacente('banco_restante') ? $nodo_liq->adyacente('banco_restante')->dato() : '0.00',
        'observaciones' => $nodo_liq->adyacente('observaciones') ? $nodo_liq->adyacente('observaciones')->dato() : '',
    ];
}

/**
 * Formatea una liquidación con todos sus datos.
 */
function formatear_liquidacion_completa(Nodo $nodo_liq): array {
    return formatear_liquidacion_resumida($nodo_liq);
}

/**
 * Evalúa si una liquidación formateada pasa los filtros.
 */
function _liquidacion_pasa_filtros(array $liq, array $filtros): bool {
    $f_codigo = trim((string)($filtros['codigo'] ?? ''));
    if ($f_codigo !== '' && stripos((string)($liq['id_liquidacion'] ?? ''), $f_codigo) === false) return false;

    $f_desde = trim((string)($filtros['fecha_desde'] ?? ''));
    if ($f_desde !== '') {
        $fi = $liq['fecha_iso'] ?? '';
        if ($fi === '' || $fi < $f_desde) return false;
    }

    $f_hasta = trim((string)($filtros['fecha_hasta'] ?? ''));
    if ($f_hasta !== '') {
        $fi = $liq['fecha_iso'] ?? '';
        if ($fi === '' || $fi > $f_hasta) return false;
    }

    return true;
}

/**
 * Lista las liquidaciones de un dueño, aplicando filtros.
 * Orden: más reciente primero.
 */
function listar_liquidaciones_de_dueno(string $nombre_dueno, array $filtros = []): array {
    $contenedor = obtener_contenedor_liquidaciones_dueno($nombre_dueno);
    if (!$contenedor) return [];

    $liquidaciones = [];
    $actual = hmi($contenedor);
    $seg = 0;
    while ($actual && $seg < 2000) {
        $seg++;
        $resumen = formatear_liquidacion_resumida($actual);
        if (_liquidacion_pasa_filtros($resumen, $filtros)) {
            $liquidaciones[] = $resumen;
        }
        $actual = hd($actual);
    }
    return $liquidaciones;
}

/**
 * Busca una liquidación por su id en todos los dueños.
 */
function obtener_liquidacion_por_id(string $id_liquidacion): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $contenedor = $nodo_dueno->adyacente('liquidaciones');
        if (!$contenedor) continue;
        $actual = hmi($contenedor);
        $seg = 0;
        while ($actual && $seg < 2000) {
            $seg++;
            if ($actual->dato() === $id_liquidacion) {
                return formatear_liquidacion_completa($actual);
            }
            $actual = hd($actual);
        }
    }
    return null;
}
