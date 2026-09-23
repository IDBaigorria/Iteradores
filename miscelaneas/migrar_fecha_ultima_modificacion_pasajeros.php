<?php
/**
 * Migración v1.5piloto.58: sembrar el enlace `fecha_ultima_modificacion`
 * en los nodos pasajero existentes.
 *
 * Reglas:
 *  - Si el pasajero ya tiene el enlace, se saltea.
 *  - Si el pasajero aparece en alguna venta (como comprador o como
 *    pasajero en algún asiento), se le pone la fecha de la venta más
 *    reciente, convertida a ISO (YYYY-MM-DD).
 *  - Si no aparece en ninguna venta, se le pone "2000-01-01".
 *
 * Es idempotente: si se corre dos veces, la segunda no cambia nada.
 *
 * @package   Iteradores
 * @since     1.5piloto.58
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");

/**
 * Convierte "DD/MM/YYYY HH:MM" a "YYYY-MM-DD".
 * Devuelve cadena vacía si no matchea el formato.
 */
function _fecha_venta_a_iso(string $fecha_visible): string {
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $fecha_visible, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
}

/**
 * Recorre todas las ventas de un dueño y devuelve un mapa
 * DNI (normalizado) => fecha_iso, con la fecha más reciente
 * encontrada para cada DNI, ya sea como comprador o como
 * pasajero en algún asiento.
 *
 * @param Nodo $nodo_dueno
 * @return array<string, string>
 */
function _recolectar_fechas_por_dni_de_dueno(Nodo $nodo_dueno): array {
    $mapa = [];
    $contenedor_ventas = $nodo_dueno->adyacente('ventas');
    if (!$contenedor_ventas) return $mapa;

    $venta = hmi($contenedor_ventas);
    $seg = 0;
    while ($venta && $seg < 5000) {
        $seg++;

        // Fecha de la venta.
        $nodo_fecha = $venta->adyacente('fecha_hora');
        $fecha_iso = $nodo_fecha ? _fecha_venta_a_iso($nodo_fecha->dato()) : '';

        if ($fecha_iso !== '') {
            // Comprador.
            $nodo_comprador = $venta->adyacente('comprador');
            if ($nodo_comprador) {
                $dni = normalizar_dni($nodo_comprador->dato());
                if ($dni !== '') {
                    if (!isset($mapa[$dni]) || $fecha_iso > $mapa[$dni]) {
                        $mapa[$dni] = $fecha_iso;
                    }
                }
            }

            // Pasajeros de cada asiento-en-venta.
            $cabeza_asientos = $venta->adyacente('asientos');
            if ($cabeza_asientos) {
                $asiento_venta = $cabeza_asientos->adyacente('primer');
                $seg2 = 0;
                while ($asiento_venta && $seg2 < 500) {
                    $seg2++;
                    $nodo_pasajero = $asiento_venta->adyacente('pasajero');
                    if ($nodo_pasajero) {
                        $dni = normalizar_dni($nodo_pasajero->dato());
                        if ($dni !== '') {
                            if (!isset($mapa[$dni]) || $fecha_iso > $mapa[$dni]) {
                                $mapa[$dni] = $fecha_iso;
                            }
                        }
                    }
                    $asiento_venta = $asiento_venta->adyacente('siguiente');
                }
            }
        }

        $venta = hd($venta);
    }

    return $mapa;
}

/**
 * Ejecuta la migración. Devuelve contadores.
 *
 * @return array
 */
function migrar_fecha_ultima_modificacion_pasajeros(): array {
    $res = [
        'duenos_procesados' => 0,
        'pasajeros_procesados' => 0,
        'migrados_con_venta' => 0,
        'migrados_con_fecha_vieja' => 0,
        'ya_migrados' => 0,
    ];

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return $res;

    $fecha_vieja = '2000-01-01';

    foreach ($raiz->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos_procesados']++;

        $mapa = _recolectar_fechas_por_dni_de_dueno($nodo_dueno);

        $contenedor_pasajeros = $nodo_dueno->adyacente('pasajeros');
        if (!$contenedor_pasajeros) continue;

        foreach ($contenedor_pasajeros->adyacentes() as $clave_dni => $nodo_pasajero) {
            $res['pasajeros_procesados']++;
            $clave_dni = (string)$clave_dni;

            // Ya tiene enlace?
            if ($nodo_pasajero->adyacente('fecha_ultima_modificacion')) {
                $res['ya_migrados']++;
                continue;
            }

            $dni_norm = normalizar_dni($clave_dni);
            if ($dni_norm !== '' && isset($mapa[$dni_norm])) {
                $nodo_pasajero->_adyacente_en(
                    Nodo::crear_con_dato($mapa[$dni_norm]),
                    'fecha_ultima_modificacion'
                );
                $res['migrados_con_venta']++;
            } else {
                $nodo_pasajero->_adyacente_en(
                    Nodo::crear_con_dato($fecha_vieja),
                    'fecha_ultima_modificacion'
                );
                $res['migrados_con_fecha_vieja']++;
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $res;
}