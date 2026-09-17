<?php
/**
 * Migración v1.5piloto.44: crear el contenedor `cupones` en cada venta
 * existente que todavía no lo tenga.
 *
 * Es idempotente: si una venta ya tiene `cupones`, la saltea.
 *
 * @package   Iteradores
 * @since     1.5piloto.44
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
 * Recorre todas las ventas de todos los dueños y crea el contenedor
 * `cupones` en aquellas que no lo tengan.
 *
 * @return array Contadores de la migración.
 */
function migrar_cupones(): array {
    $res = [
        'duenos_procesados' => 0,
        'ventas_procesadas' => 0,
        'ventas_migradas' => 0,
        'ventas_ya_migradas' => 0,
        'ventas_sin_datos' => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return $res;

    foreach ($raiz_usuarios->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $nodo_nivel = $nodo_usuario->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;
        $res['duenos_procesados']++;

        $contenedor_ventas = $nodo_usuario->adyacente('ventas');
        if (!$contenedor_ventas) continue;

        $actual = hmi($contenedor_ventas);
        while ($actual) {
            $res['ventas_procesadas']++;
            $siguiente = hd($actual);

            if ($actual->adyacente('cupones')) {
                $res['ventas_ya_migradas']++;
                $actual = $siguiente;
                continue;
            }

            $cuotas = (int)($actual->adyacente('cuotas') ? $actual->adyacente('cuotas')->dato() : '0');
            $total = $actual->adyacente('total') ? $actual->adyacente('total')->dato() : '';
            $cuotas_restantes = (int)($actual->adyacente('cuotas_restantes') ? $actual->adyacente('cuotas_restantes')->dato() : '0');
            $fecha_pago = $actual->adyacente('fecha_ultimo_pago') ? $actual->adyacente('fecha_ultimo_pago')->dato() : '';

            if ($cuotas <= 0 || $total === '') {
                $res['ventas_sin_datos']++;
                $actual = $siguiente;
                continue;
            }

            $cupones_pagados = max(0, $cuotas - $cuotas_restantes);
            _crear_lista_cupones_venta($actual, $cuotas, $total, $cupones_pagados, $fecha_pago);
            $res['ventas_migradas']++;

            $actual = $siguiente;
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $res;
}