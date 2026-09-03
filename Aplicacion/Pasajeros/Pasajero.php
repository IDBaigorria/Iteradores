<?php
/**
 * Gestión de pasajeros/clientes.
 *
 * @package   Iteradores
 * @since     1.5piloto.13
 * @version   1.5piloto.21
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
 * Obtiene el contenedor de pasajeros de un dueño, creándolo si no existe.
 *
 * @param string $nombre_dueno Nombre del usuario dueño.
 * @return Nodo|null Nodo contenedor o null si no se encuentra al dueño.
 */
function obtener_contenedor_pasajeros_dueno(string $nombre_dueno): ?Nodo {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return null;

    $contenedor = $nodo_dueno->adyacente('pasajeros');
    if (!$contenedor) {
        $contenedor = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($contenedor, 'pasajeros');
    }
    return $contenedor;
}


/**
 * Obtiene el nodo raíz de pasajeros (ahora del dueño por defecto).
 * Se mantiene el nombre por compatibilidad.
 */
function obtener_raiz_pasajeros(): ?Nodo {
    // En esta versión solo hay un dueño; se puede generalizar después.
    return obtener_contenedor_pasajeros_dueno('Parroquia_del_Carmen');
}

/**
 * Obtiene el nodo pasajero por DNI si existe, sin crearlo.
 */
function obtener_pasajero_nodo_por_dni(string $nombre_dueno, string $dni): ?Nodo {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return null;
    return $contenedor->adyacente($dni);
}

/**
 * Lista todos los pasajeros registrados de un dueño.
 */
function listar_pasajeros(string $nombre_dueno): array {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return [];

    $adyacentes = (array) $contenedor->adyacentes();
    if (!$adyacentes) return [];

    $pasajeros = [];
    foreach ($adyacentes as $dni => $nodo_pasajero) {
        $datos = formatear_pasajero($dni, $nodo_pasajero);
        $datos['tiene_pasajes'] = pasajero_tiene_pasajes($nombre_dueno, $dni);
        $pasajeros[] = $datos;
    }
    return $pasajeros;
}

/**
 * Busca pasajeros por término (nombre o DNI) dentro de un dueño.
 */
function buscar_pasajeros(string $nombre_dueno, string $termino): array {
    $termino = strtolower(trim($termino));
    $todos = listar_pasajeros($nombre_dueno);
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
function obtener_pasajero_por_dni(string $nombre_dueno, string $dni): ?array {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return null;

    $nodo_pasajero = $contenedor->adyacente($dni);
    if (!$nodo_pasajero) return null;

    $datos = formatear_pasajero($dni, $nodo_pasajero);

    // Obtener ventas relacionadas
    $ventas = [];
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if ($raiz_usuarios) {
        foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno_iter => $nodo_dueno) {
            $nodo_nivel = $nodo_dueno->adyacente('nivel');
            if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

            $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno_iter);
            if (!$contenedor_ventas) continue;

            $actual = hmi($contenedor_ventas);
            while ($actual) {
                $venta_formateada = formatear_venta_para_pasajero($actual, $dni);
                if ($venta_formateada) {
                    // Agregar rol para compatibilidad con frontend actual
                    if ($venta_formateada['es_comprador'] && $venta_formateada['es_pasajero']) {
                        $venta_formateada['rol'] = 'ambos';
                    } elseif ($venta_formateada['es_comprador']) {
                        $venta_formateada['rol'] = 'comprador';
                    } else {
                        $venta_formateada['rol'] = 'pasajero';
                    }
                    $ventas[] = $venta_formateada;
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
function actualizar_pasajero(string $nombre_dueno, string $dni, array $datos): array {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'No hay pasajeros registrados'];

    $nodo_pasajero = $contenedor->adyacente($dni);
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
function guardar_ficha_salud(string $nombre_dueno, string $dni, array $salud): void {
    $nodo_pasajero = obtener_pasajero_nodo_por_dni($nombre_dueno, $dni);
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

/**
 * Verifica si un pasajero tiene pasajes comprados en los viajes de un dueño.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @param string $dni DNI del pasajero.
 * @return bool True si tiene al menos un pasaje, false en caso contrario.
 */
function pasajero_tiene_pasajes(string $nombre_dueno, string $dni): bool {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return false;

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return false;

    $contenedor_ventas = $nodo_dueno->adyacente('ventas');
    if (!$contenedor_ventas) return false;

    $actual = hmi($contenedor_ventas);
    while ($actual) {
        // Verificar comprador
        $comprador = $actual->adyacente('comprador');
        if ($comprador && $comprador->dato() === $dni) {
            return true;
        }

        // Verificar pasajero en asientos
        $cabeza_asientos = $actual->adyacente('asientos');
        if ($cabeza_asientos) {
            $asiento_venta = $cabeza_asientos->adyacente('primer');
            $seguridad = 0;
            while ($asiento_venta && $seguridad < 100) {
                $nodo_pasajero = $asiento_venta->adyacente('pasajero');
                if ($nodo_pasajero && $nodo_pasajero->dato() === $dni) {
                    return true;
                }
                $asiento_venta = $asiento_venta->adyacente('siguiente');
                $seguridad++;
            }
        }

        $actual = hd($actual);
    }

    return false;
}
/**
 * Formatea los datos de una venta para el modal de pasajes de un pasajero.
 *
 * @param Nodo $nodo_venta Nodo de la venta persistente.
 * @param string $dni DNI del pasajero a evaluar.
 * @return array|null Datos estructurados o null si no hay relación.
 */
function formatear_venta_para_pasajero(Nodo $nodo_venta, string $dni): ?array {
    $es_comprador = false;
    $es_pasajero = false;

    // Determinar rol
    $comprador = $nodo_venta->adyacente('comprador');
    if ($comprador && $comprador->dato() === $dni) {
        $es_comprador = true;
    }

    $asientos_pasajero = [];
    $cabeza_asientos = $nodo_venta->adyacente('asientos');
    if ($cabeza_asientos) {
        $actual = $cabeza_asientos->adyacente('primer');
        $seguridad = 0;
        while ($actual && $seguridad < 100) {
            $nodo_pasajero = $actual->adyacente('pasajero');
            $nodo_asiento_real = $actual->adyacente('asiento');
            if ($nodo_pasajero && $nodo_pasajero->dato() === $dni) {
                $es_pasajero = true;
                if ($nodo_asiento_real) {
                    $asientos_pasajero[] = $nodo_asiento_real->dato(); // número de asiento
                }
            }
            $actual = $actual->adyacente('siguiente');
            $seguridad++;
        }
    }

    if (!$es_comprador && !$es_pasajero) {
        return null; // sin relación
    }

    // Datos de compra
    $id_venta = $nodo_venta->dato();
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    $nodo_total = $nodo_venta->adyacente('total');
    $nodo_pagado = $nodo_venta->adyacente('pagado');
    $nodo_cuotas_restantes = $nodo_venta->adyacente('cuotas_restantes');
    $nodo_metodo_pago = $nodo_venta->adyacente('metodo_pago');
    $nodo_fecha = $nodo_venta->adyacente('fecha_hora');

    $total = $nodo_total ? $nodo_total->dato() : '0';
    $pagado = $nodo_pagado ? $nodo_pagado->dato() : '0';
    $cuotas_restantes = $nodo_cuotas_restantes ? $nodo_cuotas_restantes->dato() : '0';
    $estado_pago = ((int)$cuotas_restantes > 0) ? 'Cuotas pendientes' : 'Pagado';

    $compra = [
        'id_venta' => $id_venta,
        'terminal' => $nodo_terminal ? $nodo_terminal->dato() : '',
        'fecha' => $nodo_fecha ? $nodo_fecha->dato() : '',
        'total' => $total,
        'pagado' => $pagado,
        'cuotas_restantes' => $cuotas_restantes,
        'metodo_pago' => $nodo_metodo_pago ? $nodo_metodo_pago->dato() : '',
        'estado_pago' => $estado_pago,
    ];

    // Datos del pasaje (solo si es pasajero)
    $pasaje = null;
    if ($es_pasajero) {
        $nodo_viaje = $nodo_venta->adyacente('viaje');
        $nodo_micro = $nodo_venta->adyacente('micro');

        $origen = $nodo_viaje && $nodo_viaje->adyacente('origen') ? $nodo_viaje->adyacente('origen')->dato() : '';
        $destino = $nodo_viaje && $nodo_viaje->adyacente('destino') ? $nodo_viaje->adyacente('destino')->dato() : '';
        $fecha_viaje = $nodo_viaje && $nodo_viaje->adyacente('fecha') ? $nodo_viaje->adyacente('fecha')->dato() : '';
        $hora = $nodo_viaje && $nodo_viaje->adyacente('hora') ? $nodo_viaje->adyacente('hora')->dato() : '';
        $micro_nombre_visible = '';
        if ($nodo_micro) {
            $copia = $nodo_micro->adyacente('vehiculo_copia');
            if ($copia && $copia->adyacente('nombre')) {
                $micro_nombre_visible = $copia->adyacente('nombre')->dato();
            } else {
                $micro_nombre_visible = $nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '';
            }
        }

        $pasaje = [
            'origen' => $origen,
            'destino' => $destino,
            'fecha' => $fecha_viaje,
            'hora' => $hora,
            'micro_nombre_visible' => $micro_nombre_visible,
            'asientos' => $asientos_pasajero,
        ];
    }

    return [
        'es_comprador' => $es_comprador,
        'es_pasajero' => $es_pasajero,
        'compra' => $compra,
        'pasaje' => $pasaje,
    ];
}
/**
 * Elimina un pasajero si no tiene pasajes comprados.
 * Si tiene referencias huérfanas, al menos se quita del listado.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @param string $dni DNI del pasajero.
 * @return array Resultado con éxito o error.
 */
function eliminar_pasajero(string $nombre_dueno, string $dni): array {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_pasajero = $contenedor->adyacente($dni);
    if (!$nodo_pasajero) return ['exito' => false, 'error' => 'Pasajero no encontrado'];

    // Verificar si tiene pasajes comprados en los viajes del dueño
    if (pasajero_tiene_pasajes($nombre_dueno, $dni)) {
        return ['exito' => false, 'error' => 'No se puede eliminar: el pasajero tiene pasajes comprados.'];
    }

    // Eliminar el enlace del contenedor (esto lo quita de la lista)
    $contenedor->eliminar_adyacente($dni);

    // Intentar eliminar el nodo; si falla por referencias huérfanas, no es crítico
    Nodo::eliminar($nodo_pasajero);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}