<?php
/**
 * Gestión de pasajeros/clientes.
 *
 * @package   Iteradores
 * @since     1.5piloto.13
 * @version   1.5piloto.25
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
 * Obtiene el nodo raíz de pasajeros (por compatibilidad).
 */
function obtener_raiz_pasajeros(): ?Nodo {
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
 * Incluye localidad, dirección y ficha de salud simplificada.
 */
function formatear_pasajero(string $dni, Nodo $nodo_pasajero): array {
    $datos = [
        'dni' => $dni,
        'nombre' => $nodo_pasajero->adyacente('nombre') ? $nodo_pasajero->adyacente('nombre')->dato() : '',
        'email' => $nodo_pasajero->adyacente('email') ? $nodo_pasajero->adyacente('email')->dato() : '',
        'celular' => $nodo_pasajero->adyacente('celular') ? $nodo_pasajero->adyacente('celular')->dato() : '',
        'celular_emergencia' => $nodo_pasajero->adyacente('celular_emergencia') ? $nodo_pasajero->adyacente('celular_emergencia')->dato() : '',
        'fecha_nacimiento' => $nodo_pasajero->adyacente('fecha_nacimiento') ? $nodo_pasajero->adyacente('fecha_nacimiento')->dato() : '',
        'localidad' => $nodo_pasajero->adyacente('localidad') ? $nodo_pasajero->adyacente('localidad')->dato() : '',
        'direccion' => $nodo_pasajero->adyacente('direccion') ? $nodo_pasajero->adyacente('direccion')->dato() : '',
    ];

    $ficha = $nodo_pasajero->adyacente('ficha_salud');
    $datos['ficha_salud'] = null;
    if ($ficha) {
        $ficha_salud = [];

        // Campos simples
        $ficha_salud['grupo_sanguineo'] = $ficha->adyacente('grupo_sanguineo') ? $ficha->adyacente('grupo_sanguineo')->dato() : '';
        $ficha_salud['obra_social'] = $ficha->adyacente('obra_social') ? $ficha->adyacente('obra_social')->dato() : '';
        $ficha_salud['regimenes_comida'] = $ficha->adyacente('regimenes_comida') ? $ficha->adyacente('regimenes_comida')->dato() : '';
        $ficha_salud['observaciones'] = $ficha->adyacente('observaciones') ? $ficha->adyacente('observaciones')->dato() : '';

        // Categorías que antes eran listas, ahora string
        foreach (['enfermedades', 'medicamentos', 'impedimentos', 'alergias'] as $cat) {
            $raiz_cat = $ficha->adyacente($cat);
            $items = [];
            if ($raiz_cat) {
                $actual_item = hmi($raiz_cat);
                while ($actual_item) {
                    $items[] = $actual_item->dato();
                    $actual_item = hd($actual_item);
                }
                if (!empty($items)) {
                    // Si había lista, la concatenamos en un string
                    $ficha_salud[$cat] = implode('; ', $items);
                } else {
                    // Si no tiene hijos, puede que ya sea string en el dato del nodo
                    $ficha_salud[$cat] = $raiz_cat->dato() ?? '';
                }
            } else {
                $ficha_salud[$cat] = '';
            }
        }

        $datos['ficha_salud'] = $ficha_salud;
    }

    return $datos;
}

/**
 * Guarda la ficha de salud de un pasajero.
 * Ahora todos los campos son strings simples (excepto grupo sanguíneo que también es string).
 * Las antiguas listas se convierten a string si se recibe un array.
 */
function guardar_ficha_salud(string $nombre_dueno, string $dni, array $salud): void {
    $nodo_pasajero = obtener_pasajero_nodo_por_dni($nombre_dueno, $dni);
    if (!$nodo_pasajero) return;

    $ficha = $nodo_pasajero->adyacente('ficha_salud');
    if (!$ficha) {
        $ficha = Nodo::crear_con_dato('');
        $nodo_pasajero->_adyacente_en($ficha, 'ficha_salud');
    }

    // Campos simples
    $campos_simples = ['grupo_sanguineo', 'obra_social', 'regimenes_comida', 'observaciones'];
    foreach ($campos_simples as $campo) {
        if (isset($salud[$campo])) {
            $valor = trim($salud[$campo]);
            $nodo_campo = $ficha->adyacente($campo);
            if ($nodo_campo) {
                if ($valor === '') $ficha->eliminar_adyacente($campo);
                else $nodo_campo->_dato($valor);
            } else {
                if ($valor !== '') $ficha->_adyacente_en(Nodo::crear_con_dato($valor), $campo);
            }
        }
    }

    // Categorías de lista convertidas a string
    $categorias = ['enfermedades', 'medicamentos', 'impedimentos', 'alergias'];
    foreach ($categorias as $cat) {
        $raiz = $ficha->adyacente($cat);
        if (!$raiz) {
            $raiz = Nodo::crear_con_dato('');
            $ficha->_adyacente_en($raiz, $cat);
        }

        // Limpiar posibles hijos antiguos
        while ($hijo = hmi($raiz)) {
            eliminar_hmi($raiz);
        }

        // Obtener valor (string o array)
        $valor = '';
        if (isset($salud[$cat])) {
            if (is_array($salud[$cat])) {
                $valor = implode('; ', array_map('trim', $salud[$cat]));
            } else {
                $valor = trim($salud[$cat]);
            }
        }
        $raiz->_dato($valor);
    }

    Controlador::guardar(Conf::NOMBRE_APP);
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

    $campos = ['nombre', 'email', 'celular', 'celular_emergencia', 'fecha_nacimiento', 'localidad', 'direccion'];
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
 * Verifica si un pasajero tiene pasajes comprados en los viajes de un dueño.
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
        $comprador = $actual->adyacente('comprador');
        if ($comprador && $comprador->dato() === $dni) {
            return true;
        }

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
 */
function formatear_venta_para_pasajero(Nodo $nodo_venta, string $dni): ?array {
    $es_comprador = false;
    $es_pasajero = false;

    $comprador = $nodo_venta->adyacente('comprador');
    if ($comprador && $comprador->dato() === $dni) {
        $es_comprador = true;
    }

    $asientos_pasajero = [];
    $pasajes_venta = [];
    $cabeza_asientos = $nodo_venta->adyacente('asientos');
    if ($cabeza_asientos) {
        $actual = $cabeza_asientos->adyacente('primer');
        $seguridad = 0;
        while ($actual && $seguridad < 100) {
            $nodo_pasajero = $actual->adyacente('pasajero');
            $nodo_asiento_real = $actual->adyacente('asiento');

            if ($nodo_asiento_real && $nodo_pasajero) {
                $numero_asiento = $nodo_asiento_real->dato();
                $nombre_pasajero = $nodo_pasajero->adyacente('nombre') ? $nodo_pasajero->adyacente('nombre')->dato() : '';
                $dni_pasajero = $nodo_pasajero->dato();

                $pasajes_venta[] = [
                    'asiento' => $numero_asiento,
                    'nombre' => $nombre_pasajero,
                    'dni' => $dni_pasajero,
                ];

                if ($dni_pasajero === $dni) {
                    $es_pasajero = true;
                    $asientos_pasajero[] = $numero_asiento;
                }
            }

            $actual = $actual->adyacente('siguiente');
            $seguridad++;
        }
    }

    if (!$es_comprador && !$es_pasajero) {
        return null;
    }

    $id_venta = $nodo_venta->dato();
    $nodo_terminal = $nodo_venta->adyacente('terminal');
    $nodo_fecha = $nodo_venta->adyacente('fecha_hora');
    $nodo_cuotas_restantes = $nodo_venta->adyacente('cuotas_restantes');

    $terminal_nombre = '';
    if ($nodo_terminal) {
        $terminal_nombre = $nodo_terminal->adyacente('nombre_real') ? $nodo_terminal->adyacente('nombre_real')->dato() : $nodo_terminal->dato();
    }

    $cuotas_restantes = $nodo_cuotas_restantes ? $nodo_cuotas_restantes->dato() : '0';
    $estado_pago = ((int)$cuotas_restantes > 0) ? 'Cuotas pendientes' : 'Pagado';

    $compra = [
        'id_venta' => $id_venta,
        'terminal_nombre' => $terminal_nombre,
        'fecha' => $nodo_fecha ? $nodo_fecha->dato() : '',
        'estado_pago' => $estado_pago,
    ];

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

    return [
        'es_comprador' => $es_comprador,
        'es_pasajero' => $es_pasajero,
        'compra' => $compra,
        'pasaje' => $pasaje,
        'pasajes_venta' => $pasajes_venta,
    ];
}

/**
 * Elimina un pasajero si no tiene pasajes comprados.
 */
function eliminar_pasajero(string $nombre_dueno, string $dni): array {
    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_pasajero = $contenedor->adyacente($dni);
    if (!$nodo_pasajero) return ['exito' => false, 'error' => 'Pasajero no encontrado'];

    if (pasajero_tiene_pasajes($nombre_dueno, $dni)) {
        return ['exito' => false, 'error' => 'No se puede eliminar: el pasajero tiene pasajes comprados.'];
    }

    $contenedor->eliminar_adyacente($dni);
    Nodo::eliminar($nodo_pasajero);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}