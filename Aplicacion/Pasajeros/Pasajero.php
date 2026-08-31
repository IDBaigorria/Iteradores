<?php
/**
 * Gestión de pasajeros/clientes.
 *
 * @package   Iteradores
 * @since     1.5piloto.13
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Obtiene el nodo raíz de pasajeros, creándolo si no existe.
 */
function obtener_raiz_pasajeros(): ?Nodo {
    $raiz = Nodo::nodo_por_id('pasajeros');
    if (!$raiz) {
        $raiz = Nodo::crear_con_id('pasajeros');
    }
    return $raiz;
}

/**
 * Lista todos los pasajeros registrados.
 */
function listar_pasajeros(): array {
    $raiz = obtener_raiz_pasajeros();
    if (!$raiz) return [];

    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return [];

    $pasajeros = [];
    foreach ($adyacentes as $dni => $nodo_pasajero) {
        $pasajeros[] = formatear_pasajero($dni, $nodo_pasajero);
    }
    return $pasajeros;
}

/**
 * Busca pasajeros por término (nombre o DNI).
 */
function buscar_pasajeros(string $termino): array {
    $termino = strtolower(trim($termino));
    $todos = listar_pasajeros();
    if (empty($termino)) return $todos;

    return array_filter($todos, function($pasajero) use ($termino) {
        return strpos(strtolower($pasajero['nombre']), $termino) !== false ||
               strpos($pasajero['dni'], $termino) !== false;
    });
}

/**
 * Formatea los datos de un pasajero.
 */
function formatear_pasajero(string $dni, Nodo $nodo_pasajero): array {
    return [
        'dni' => $dni,
        'nombre' => $nodo_pasajero->adyacente('nombre') ? $nodo_pasajero->adyacente('nombre')->dato() : '',
        'email' => $nodo_pasajero->adyacente('email') ? $nodo_pasajero->adyacente('email')->dato() : '',
        'celular' => $nodo_pasajero->adyacente('celular') ? $nodo_pasajero->adyacente('celular')->dato() : '',
        'celular_emergencia' => $nodo_pasajero->adyacente('celular_emergencia') ? $nodo_pasajero->adyacente('celular_emergencia')->dato() : '',
    ];
}

/**
 * Obtiene un pasajero por DNI.
 */
function obtener_pasajero_por_dni(string $dni): ?array {
    $raiz = obtener_raiz_pasajeros();
    if (!$raiz) return null;

    $nodo_pasajero = $raiz->adyacente($dni);
    if (!$nodo_pasajero) return null;

    $datos = formatear_pasajero($dni, $nodo_pasajero);
    // Obtener sus pasajes vendidos (ventas donde aparece)
    $ventas = [];
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if ($raiz_usuarios) {
        foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
            $nodo_nivel = $nodo_dueno->adyacente('nivel');
            if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;
            $cabeza_ventas = obtener_cabeza_ventas_dueno($nombre_dueno);
            if (!$cabeza_ventas) continue;
            $actual = $cabeza_ventas->adyacente('primer');
            $seguridad = 0;
            while ($actual && $actual->id() !== $cabeza_ventas->id() && $seguridad < 1000) {
                // Ver si este pasajero está en algún asiento de esta venta
                $cabeza_asientos = $actual->adyacente('asientos');
                if ($cabeza_asientos) {
                    $asiento_venta_actual = $cabeza_asientos->adyacente('primer');
                    $seguridad_asientos = 0;
                    while ($asiento_venta_actual && $asiento_venta_actual->id() !== $cabeza_asientos->id() && $seguridad_asientos < 100) {
                        $nodo_pasajero_venta = $asiento_venta_actual->adyacente('pasajero');
                        if ($nodo_pasajero_venta && $nodo_pasajero_venta->dato() === $dni) {
                            // Agregar resumen de la venta
                            $ventas[] = formatear_venta_resumida($actual);
                            break 2; // salir del bucle de asientos
                        }
                        $asiento_venta_actual = $asiento_venta_actual->adyacente('siguiente');
                        $seguridad_asientos++;
                    }
                }
                $actual = $actual->adyacente('siguiente');
                $seguridad++;
            }
        }
    }
    $datos['ventas'] = $ventas;
    return $datos;
}

/**
 * Actualiza los datos de un pasajero (excepto DNI que es inmutable).
 */
function actualizar_pasajero(string $dni, array $datos): array {
    $raiz = obtener_raiz_pasajeros();
    if (!$raiz) return ['exito' => false, 'error' => 'No hay pasajeros registrados'];

    $nodo_pasajero = $raiz->adyacente($dni);
    if (!$nodo_pasajero) return ['exito' => false, 'error' => 'Pasajero no encontrado'];

    $campos = ['nombre', 'email', 'celular', 'celular_emergencia'];
    foreach ($campos as $campo) {
        if (isset($datos[$campo])) {
            $valor = trim($datos[$campo]);
            $nodo_campo = $nodo_pasajero->adyacente($campo);
            if ($nodo_campo) {
                if ($valor === '') $nodo_pasajero->eliminar_adyacente($campo);
                else $nodo_campo->_dato($valor);
            } else {
                if ($valor !== '') $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($valor), $campo);
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}