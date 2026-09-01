<?php
/**
 * Gestión de pasajeros/clientes.
 *
 * @package   Iteradores
 * @since     1.5piloto.13
 * @version   1.5piloto.19
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
 * Obtiene el nodo pasajero por DNI si existe, sin crearlo.
 */
function obtener_pasajero_nodo_por_dni(string $dni): ?Nodo {
    $raiz = Nodo::nodo_por_id('pasajeros');
    if (!$raiz) return null;
    return $raiz->adyacente($dni);
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
    $datos = [
        'dni' => $dni,
        'nombre' => $nodo_pasajero->adyacente('nombre') ? $nodo_pasajero->adyacente('nombre')->dato() : '',
        'email' => $nodo_pasajero->adyacente('email') ? $nodo_pasajero->adyacente('email')->dato() : '',
        'celular' => $nodo_pasajero->adyacente('celular') ? $nodo_pasajero->adyacente('celular')->dato() : '',
        'celular_emergencia' => $nodo_pasajero->adyacente('celular_emergencia') ? $nodo_pasajero->adyacente('celular_emergencia')->dato() : '',
        'fecha_nacimiento' => $nodo_pasajero->adyacente('fecha_nacimiento') ? $nodo_pasajero->adyacente('fecha_nacimiento')->dato() : '',
    ];

    // Ficha de salud
    $ficha = $nodo_pasajero->adyacente('ficha_salud');
    $datos['ficha_salud'] = null;
    if ($ficha) {
        $ficha_salud = [];
        foreach (['enfermedades', 'medicamentos', 'impedimentos'] as $cat) {
            $raiz_cat = $ficha->adyacente($cat);
            $items = [];
            if ($raiz_cat) {
                $actual_item = hmi($raiz_cat);
                while ($actual_item) {
                    $items[] = $actual_item->dato();
                    $actual_item = hd($actual_item);
                }
            }
            $ficha_salud[$cat] = $items;
        }
        $datos['ficha_salud'] = $ficha_salud;
    }

    return $datos;
}

/**
 * Obtiene un pasajero por DNI con sus ventas.
 */
function obtener_pasajero_por_dni(string $dni): ?array {
    $raiz = obtener_raiz_pasajeros();
    if (!$raiz) return null;

    $nodo_pasajero = $raiz->adyacente($dni);
    if (!$nodo_pasajero) return null;

    $datos = formatear_pasajero($dni, $nodo_pasajero);

    // Obtener sus pasajes vendidos
    $ventas = [];
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if ($raiz_usuarios) {
        foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
            $nodo_nivel = $nodo_dueno->adyacente('nivel');
            if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

            $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
            if (!$contenedor_ventas) continue;

            $actual = hmi($contenedor_ventas);
            while ($actual) {
                $cabeza_asientos = $actual->adyacente('asientos');
                if ($cabeza_asientos) {
                    $asiento_venta = $cabeza_asientos->adyacente('primer');
                    $seguridad = 0;
                    while ($asiento_venta && $seguridad < 100) {
                        $nodo_pasajero_venta = $asiento_venta->adyacente('pasajero');
                        if ($nodo_pasajero_venta && $nodo_pasajero_venta->dato() === $dni) {
                            $ventas[] = formatear_venta_resumida($actual);
                            break 2;
                        }
                        $asiento_venta = $asiento_venta->adyacente('siguiente');
                        $seguridad++;
                    }
                }
                $actual = hd($actual);
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

    $campos = ['nombre', 'email', 'celular', 'celular_emergencia', 'fecha_nacimiento'];
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

/**
 * Guarda la ficha de salud de un pasajero.
 */
function guardar_ficha_salud(string $dni, array $salud): void {
    $nodo_pasajero = obtener_pasajero_nodo_por_dni($dni);
    if (!$nodo_pasajero) return;

    $ficha = $nodo_pasajero->adyacente('ficha_salud');
    if (!$ficha) {
        $ficha = Nodo::crear_con_dato('');
        $nodo_pasajero->_adyacente_en($ficha, 'ficha_salud');
    }

    $categorias = ['enfermedades', 'medicamentos', 'impedimentos'];
    foreach ($categorias as $cat) {
        $raiz = $ficha->adyacente($cat);
        if (!$raiz) {
            $raiz = Nodo::crear_con_dato('');
            $ficha->_adyacente_en($raiz, $cat);
        }

        while ($hijo = hmi($raiz)) {
            eliminar_hmi($raiz);
        }

        $items = $salud[$cat] ?? [];
        $items = array_reverse($items);
        foreach ($items as $item) {
            $nodo_item = Nodo::crear_con_dato($item);
            _hmi($raiz, $nodo_item);
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
}