<?php
/**
 * Gestión de pasajeros/clientes.
 *
 * @package   Iteradores
 * @since     1.5piloto.13
 * @version   1.5piloto.39
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
 * Devuelve el nombre completo del pasajero con el formato [apellido],[nombres].
 * Si el apellido está vacío, devuelve ",nombres". Si ambos están vacíos, "".
 *
 * @param string $apellido
 * @param string $nombres
 * @return string
 */
function formatear_nombre_completo(string $apellido, string $nombres): string {
    $apellido = trim($apellido);
    $nombres = trim($nombres);
    if ($apellido === '' && $nombres === '') return '';
    return $apellido . ', ' . $nombres;
}

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
        $datos['tiene_ventas_activas'] = pasajero_tiene_ventas_activas($nombre_dueno, $dni);
        $datos['tiene_reservas_activas'] = pasajero_tiene_reservas_activas($nombre_dueno, $dni);
        $pasajeros[] = $datos;
    }
    return $pasajeros;
}

/**
 * Busca pasajeros por término (nombres, apellido o DNI) dentro de un dueño.
 */
function buscar_pasajeros(string $nombre_dueno, string $termino): array {
    $termino = strtolower(trim($termino));
    $todos = listar_pasajeros($nombre_dueno);
    if (empty($termino)) return $todos;

    $dni_norm_buscado = normalizar_dni($termino);

    return array_filter($todos, function($pasajero) use ($termino, $dni_norm_buscado) {
        if (strpos(strtolower($pasajero['nombres']), $termino) !== false) return true;
        if (strpos(strtolower($pasajero['apellido']), $termino) !== false) return true;
        if (strpos($pasajero['dni'], $termino) !== false) return true;
        if ($dni_norm_buscado !== '' && strpos(normalizar_dni($pasajero['dni']), $dni_norm_buscado) !== false) return true;
        return false;
    });
}

/**
 * Formatea los datos de un pasajero.
 * Incluye localidad, dirección y ficha de salud simplificada.
 *
 * Devuelve `nombres`, `apellido` y `nombre_completo` (formato [apellido],[nombres]).
 */
function formatear_pasajero(string $dni, Nodo $nodo_pasajero): array {
    $nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';
    $apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';

    $datos = [
        'dni' => $dni,
        'dni_visible' => normalizar_dni($dni),
        'nombres' => $nombres,
        'apellido' => $apellido,
        'nombre_completo' => formatear_nombre_completo($apellido, $nombres),
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
    $datos['reservas'] = obtener_reservas_de_pasajero($nombre_dueno, $dni);

    return $datos;
}

/**
 * Actualiza los datos de un pasajero (excepto DNI que es inmutable).
 */
function actualizar_pasajero(string $nombre_dueno, string $dni, array $datos): array {
    // Validaciones (solo si vienen campos)
    if (isset($datos['apellido'])) {
        $err = validar_nombre_o_apellido($datos['apellido']);
        if ($err !== null) return ['exito' => false, 'error' => 'Apellido: ' . $err];
    }
    if (isset($datos['nombres'])) {
        $err = validar_nombre_o_apellido($datos['nombres']);
        if ($err !== null) return ['exito' => false, 'error' => 'Nombres: ' . $err];
    }
    if (isset($datos['email'])) {
        $err = validar_email($datos['email']);
        if ($err !== null) return ['exito' => false, 'error' => $err];
    }
    if (isset($datos['celular'])) {
        $err = validar_telefono($datos['celular']);
        if ($err !== null) return ['exito' => false, 'error' => 'Celular: ' . $err];
    }
    if (isset($datos['celular_emergencia'])) {
        $err = validar_telefono($datos['celular_emergencia']);
        if ($err !== null) return ['exito' => false, 'error' => 'Celular de emergencia: ' . $err];
    }
    if (isset($datos['localidad'])) {
        $err = validar_localidad($datos['localidad']);
        if ($err !== null) return ['exito' => false, 'error' => 'Localidad: ' . $err];
    }
    if (isset($datos['direccion'])) {
        $err = validar_direccion($datos['direccion']);
        if ($err !== null) return ['exito' => false, 'error' => 'Dirección: ' . $err];
    }

    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);
    if (!$contenedor) return ['exito' => false, 'error' => 'No hay pasajeros registrados'];

    $nodo_pasajero = $contenedor->adyacente($dni);
    if (!$nodo_pasajero) return ['exito' => false, 'error' => 'Pasajero no encontrado'];

    $campos = ['nombres', 'apellido', 'email', 'celular', 'celular_emergencia', 'fecha_nacimiento', 'localidad', 'direccion'];
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

    $activos = pasajero_tiene_pasajes_activos($nombre_dueno, $dni);
    return [
        'exito' => true,
        'tiene_pasajes_activos' => $activos['tiene_activos'],
        'ventas_activas' => $activos['ventas'],
    ];
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
                $pas_nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';
                $pas_apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';
                $dni_pasajero = $nodo_pasajero->dato();

                $pasajes_venta[] = [
                    'asiento' => $numero_asiento,
                    'nombres' => $pas_nombres,
                    'apellido' => $pas_apellido,
                    'nombre_completo' => formatear_nombre_completo($pas_apellido, $pas_nombres),
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
        'fecha_visible' => formatear_fecha_visible($fecha_viaje),
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

/**
 * Determina si la fecha de un viaje corresponde a un viaje activo.
 *
 * Un viaje activo es aquel cuya fecha de salida es posterior a la fecha
 * actual. La fecha "a confirmar" se considera activa (todavía no se sabe
 * cuándo sale, pero el viaje sigue vigente). Una fecha vacía no se considera
 * activa.
 *
 * @param string $fecha_viaje Fecha en formato ISO (YYYY-MM-DD), "a confirmar" o "".
 * @return bool
 */
function _fecha_viaje_es_activa(string $fecha_viaje): bool {
    $fecha_viaje = trim($fecha_viaje);
    if ($fecha_viaje === '') return false;
    if ($fecha_viaje === 'a confirmar') return true;
    $ts = strtotime($fecha_viaje);
    if ($ts === false) return false;
    $hoy_ts = strtotime(date('Y-m-d'));
    return $ts > $hoy_ts;
}

/**
 * Determina si un pasajero tiene pasajes propios en viajes activos.
 *
 * Un "pasaje propio" es un asiento-en-venta persistente cuyo nodo `pasajero`
 * apunta a este DNI. No cuenta si el DNI es solo el comprador de la venta
 * (puede ser el comprador y no viajar). Un "viaje activo" es aquel cuya
 * fecha de salida es posterior a la fecha actual (o "a confirmar").
 *
 * Devuelve la lista de ventas activas donde el pasajero viaja, para que el
 * frontend pueda mostrar el aviso de "imprimir pasajes actualizados".
 * La comparación de DNI se hace con normalizar_dni para tolerar DNIs
 * históricos con puntos.
 *
 * @param string $nombre_dueno
 * @param string $dni
 * @return array{tiene_activos: bool, ventas: array<int, array{id_venta: string, fecha_viaje: string, nombre_viaje: string}>}
 */
function pasajero_tiene_pasajes_activos(string $nombre_dueno, string $dni): array {
    $resultado = ['tiene_activos' => false, 'ventas' => []];

    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') return $resultado;

    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor_ventas) return $resultado;

    $actual = hmi($contenedor_ventas);
    while ($actual) {
        // ¿El DNI es pasajero en algún asiento de esta venta?
        $es_pasajero = false;
        $cabeza_asientos = $actual->adyacente('asientos');
        if ($cabeza_asientos) {
            $asiento_venta = $cabeza_asientos->adyacente('primer');
            $seguridad = 0;
            while ($asiento_venta && $seguridad < 200) {
                $nodo_pasajero = $asiento_venta->adyacente('pasajero');
                if ($nodo_pasajero && normalizar_dni($nodo_pasajero->dato()) === $dni_norm) {
                    $es_pasajero = true;
                    break;
                }
                $asiento_venta = $asiento_venta->adyacente('siguiente');
                $seguridad++;
            }
        }

        if ($es_pasajero) {
            $nodo_viaje = $actual->adyacente('viaje');
            $fecha_viaje = ($nodo_viaje && $nodo_viaje->adyacente('fecha'))
                ? $nodo_viaje->adyacente('fecha')->dato()
                : '';
            if (_fecha_viaje_es_activa($fecha_viaje)) {
                $resultado['tiene_activos'] = true;
                $resultado['ventas'][] = [
                    'id_venta' => $actual->dato(),
                    'fecha_viaje' => $fecha_viaje,
                    'nombre_viaje' => $nodo_viaje ? $nodo_viaje->dato() : '',
                ];
            }
        }

        $actual = hd($actual);
    }

    return $resultado;
}

/**
 * Recorre los viajes del dueño y llama a $callback por cada asiento reservado
 * para el equipo cuyo pasajero sea el DNI buscado, y cuyo viaje sea activo.
 *
 * El callback recibe: ($nodo_viaje, $nodo_micro, $nodo_asiento).
 *
 * @param string   $nombre_dueno
 * @param string   $dni_normalizado
 * @param callable $callback
 * @return void
 */
function _recorrer_reservas_de_pasajero(string $nombre_dueno, string $dni_normalizado, callable $callback): void {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return;

    $adyacentes_viajes = (array) $nodo_viajes->adyacentes();
    foreach ($adyacentes_viajes as $nombre_viaje => $nodo_viaje) {
        // Solo viajes activos
        $nodo_fecha = $nodo_viaje->adyacente('fecha');
        $fecha_viaje = $nodo_fecha ? $nodo_fecha->dato() : '';
        if (!_fecha_viaje_es_activa($fecha_viaje)) continue;

        $nodo_micros = $nodo_viaje->adyacente('micros');
        if (!$nodo_micros) continue;

        $adyacentes_micros = (array) $nodo_micros->adyacentes();
        foreach ($adyacentes_micros as $nodo_micro) {
            $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
            if (!$nodo_copia) continue;
            $nodo_asientos = $nodo_copia->adyacente('asientos');
            if (!$nodo_asientos) continue;

            for ($i = 1; $i <= 2; $i++) {
                $piso = $nodo_asientos->adyacente("piso_$i");
                if (!$piso) continue;
                $cabeza = $piso->adyacente('asientos');
                if (!$cabeza) continue;
                $actual = $cabeza->adyacente('primer');
                $seguridad = 0;
                while ($actual && $actual->id() !== $cabeza->id() && $seguridad < 200) {
                    $estado = $actual->adyacente('estado');
                    $pasajero = $actual->adyacente('pasajero');
                    if ($estado && $estado->dato() === 'reservado'
                        && $pasajero && normalizar_dni($pasajero->dato()) === $dni_normalizado) {
                        $callback($nodo_viaje, $nodo_micro, $actual);
                    }
                    $actual = $actual->adyacente('siguiente');
                    $seguridad++;
                }
            }
        }
    }
}

/**
 * Determina si un pasajero tiene ventas activas (comprador o pasajero).
 *
 * No cuenta reservas del equipo. Se usa para decidir si mostrar el botón
 * "Ver pasajes" a una terminal: la terminal ve los pasajes de ventas,
 * pero no los del equipo.
 *
 * @param string $nombre_dueno
 * @param string $dni
 * @return bool
 */
function pasajero_tiene_ventas_activas(string $nombre_dueno, string $dni): bool {
    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') return false;

    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor_ventas) return false;

    $actual = hmi($contenedor_ventas);
    while ($actual) {
        $nodo_viaje = $actual->adyacente('viaje');
        $fecha_viaje = ($nodo_viaje && $nodo_viaje->adyacente('fecha'))
            ? $nodo_viaje->adyacente('fecha')->dato()
            : '';
        if (_fecha_viaje_es_activa($fecha_viaje)) {
            $comprador = $actual->adyacente('comprador');
            if ($comprador && normalizar_dni($comprador->dato()) === $dni_norm) {
                return true;
            }
            $cabeza_asientos = $actual->adyacente('asientos');
            if ($cabeza_asientos) {
                $asiento_venta = $cabeza_asientos->adyacente('primer');
                $seguridad = 0;
                while ($asiento_venta && $seguridad < 200) {
                    $nodo_pasajero = $asiento_venta->adyacente('pasajero');
                    if ($nodo_pasajero && normalizar_dni($nodo_pasajero->dato()) === $dni_norm) {
                        return true;
                    }
                    $asiento_venta = $asiento_venta->adyacente('siguiente');
                    $seguridad++;
                }
            }
        }
        $actual = hd($actual);
    }

    return false;
}

/**
 * Determina si un pasajero tiene reservas activas del equipo.
 *
 * Se usa para decidir si mostrar el botón "Ver pasajes" a un dueño/admin
 * cuando el pasajero no tiene ventas activas pero sí reservas.
 *
 * @param string $nombre_dueno
 * @param string $dni
 * @return bool
 */
function pasajero_tiene_reservas_activas(string $nombre_dueno, string $dni): bool {
    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') return false;

    $encontrado = false;
    _recorrer_reservas_de_pasajero($nombre_dueno, $dni_norm, function() use (&$encontrado) {
        $encontrado = true;
    });
    return $encontrado;
}

/**
 * Devuelve las reservas del equipo activas donde el DNI es pasajero.
 *
 * Cada reserva es un array con:
 *   - viaje_id, viaje_nombre_visible
 *   - fecha (ISO), fecha_visible (DD/MM/YYYY), hora
 *   - origen, destino
 *   - micro_id, micro_nombre_visible
 *   - numero_asiento, fila, columna
 *   - punto_subida_bajada (o null), hora_subida_bajada (o null)
 *   - reservado_por (nombre del dueño que reservó, o '')
 *
 * @param string $nombre_dueno
 * @param string $dni
 * @return array
 */
function obtener_reservas_de_pasajero(string $nombre_dueno, string $dni): array {
    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') return [];

    $reservas = [];

    _recorrer_reservas_de_pasajero($nombre_dueno, $dni_norm, function($nodo_viaje, $nodo_micro, $nodo_asiento) use (&$reservas) {
        $viaje_id = $nodo_viaje->dato();
        $viaje_nombre_visible = $nodo_viaje->adyacente('nombre')
            ? $nodo_viaje->adyacente('nombre')->dato()
            : $viaje_id;
        $fecha_iso = $nodo_viaje->adyacente('fecha')
            ? $nodo_viaje->adyacente('fecha')->dato()
            : '';
        $hora = $nodo_viaje->adyacente('hora')
            ? $nodo_viaje->adyacente('hora')->dato()
            : '';
        $origen = $nodo_viaje->adyacente('origen')
            ? $nodo_viaje->adyacente('origen')->dato()
            : '';
        $destino = $nodo_viaje->adyacente('destino')
            ? $nodo_viaje->adyacente('destino')->dato()
            : '';

        $micro_id = $nodo_micro->dato();
        $micro_nombre_visible = '';
        $copia = $nodo_micro->adyacente('vehiculo_copia');
        if ($copia && $copia->adyacente('nombre')) {
            $micro_nombre_visible = $copia->adyacente('nombre')->dato();
        }

        $fila = $nodo_asiento->adyacente('fila')
            ? $nodo_asiento->adyacente('fila')->dato()
            : '';
        $columna = $nodo_asiento->adyacente('columna')
            ? $nodo_asiento->adyacente('columna')->dato()
            : '';
        $numero_asiento = $nodo_asiento->dato();

        $nodo_punto_sb = $nodo_asiento->adyacente('punto_subida_bajada');
        $nodo_hora_sb = $nodo_asiento->adyacente('hora_subida_bajada');
        $reservado_por = $nodo_asiento->adyacente('reservado_por')
            ? $nodo_asiento->adyacente('reservado_por')->dato()
            : '';

        $reservas[] = [
            'viaje_id' => $viaje_id,
            'viaje_nombre_visible' => $viaje_nombre_visible,
            'fecha' => $fecha_iso,
            'fecha_visible' => formatear_fecha_visible($fecha_iso),
            'hora' => $hora,
            'origen' => $origen,
            'destino' => $destino,
            'micro_id' => $micro_id,
            'micro_nombre_visible' => $micro_nombre_visible,
            'numero_asiento' => $numero_asiento,
            'fila' => $fila,
            'columna' => $columna,
            'punto_subida_bajada' => $nodo_punto_sb ? $nodo_punto_sb->dato() : null,
            'hora_subida_bajada' => $nodo_hora_sb ? $nodo_hora_sb->dato() : null,
            'reservado_por' => $reservado_por,
        ];
    });

    return $reservas;
}