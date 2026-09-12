<?php
/**
 * Núcleo de gestión de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.29
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");

/**
 * Obtiene el contenedor de viajes de un dueño, creándolo si no existe.
 */
function obtener_contenedor_viajes_dueno(string $nombre_dueno) {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return null;

    $nodo_viajes = $nodo_dueno->adyacente('viajes');
    if (!$nodo_viajes) {
        $nodo_viajes = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_viajes, 'viajes');
    }
    return $nodo_viajes;
}

/**
 * Lista viajes de un dueño.
 */
function listar_viajes_de_dueno(string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return [];

    $adyacentes = (array) $nodo_viajes->adyacentes();
    if (!$adyacentes) return [];

    $viajes = [];
    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {
        $viajes[] = formatear_viaje($nombre_viaje, $nodo_viaje);
    }
    return $viajes;
}

/**
 * Lista viajes autorizados para una terminal.
 */
function listar_viajes_de_terminal(string $nombre_terminal): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);
    if (!$nodo_terminal) return [];

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno) return [];

    $nombre_dueno = $nodo_dueno->dato();
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return [];

    $viajes_autorizados = [];
    $adyacentes = (array) $nodo_viajes->adyacentes();
    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {
        $viaje = formatear_viaje($nombre_viaje, $nodo_viaje, $nombre_terminal); 
        if (in_array($nombre_terminal, $viaje['terminales_autorizadas'])) {
            $viajes_autorizados[] = $viaje;
        }
    }
    return $viajes_autorizados;
}

/**
 * Formatea los datos de un viaje, incluyendo micros, terminales y opciones.
 * Si se pasa $nombre_terminal, se calcula 'vendidos_aqui' por micro para esa terminal.
 */
function formatear_viaje(string $nombre_viaje, $nodo_viaje, ?string $nombre_terminal = null): array {
    $datos = [];
    $datos['nombre_viaje'] = $nombre_viaje;
    $datos['dueno'] = $nodo_viaje->adyacente('dueno') ? $nodo_viaje->adyacente('dueno')->dato() : '';
    $datos['nombre'] = $nodo_viaje->adyacente('nombre') ? $nodo_viaje->adyacente('nombre')->dato() : '';
    $datos['fecha'] = $nodo_viaje->adyacente('fecha') ? $nodo_viaje->adyacente('fecha')->dato() : '';
    $datos['hora'] = $nodo_viaje->adyacente('hora') ? $nodo_viaje->adyacente('hora')->dato() : '';
    $datos['origen'] = $nodo_viaje->adyacente('origen') ? $nodo_viaje->adyacente('origen')->dato() : '';
    $datos['destino'] = $nodo_viaje->adyacente('destino') ? $nodo_viaje->adyacente('destino')->dato() : '';
    $datos['ocupacion'] = $nodo_viaje->adyacente('ocupacion') ? $nodo_viaje->adyacente('ocupacion')->dato() : '0';
    $datos['disponibles'] = $nodo_viaje->adyacente('disponibles') ? $nodo_viaje->adyacente('disponibles')->dato() : '0';
    $datos['seleccionados'] = $nodo_viaje->adyacente('seleccionados') ? $nodo_viaje->adyacente('seleccionados')->dato() : '0';
    $datos['vendidos'] = $nodo_viaje->adyacente('vendidos') ? $nodo_viaje->adyacente('vendidos')->dato() : '0';
    $datos['reservados'] = $nodo_viaje->adyacente('reservados') ? $nodo_viaje->adyacente('reservados')->dato() : '0';

    // Paradas intermedias (lista tipo árbol hmi/hd)
    $paradas = [];
    $nodo_paradas = $nodo_viaje->adyacente('paradas_intermedias');
    if ($nodo_paradas) {
        $actual_parada = hmi($nodo_paradas);
        $seg = 0;
        while ($actual_parada && $seg < 100) {
            $paradas[] = $actual_parada->dato();
            $actual_parada = hd($actual_parada);
            $seg++;
        }
    }
    $datos['paradas_intermedias'] = $paradas;

    // Determinar si el viaje está activo
    $fecha = $datos['fecha'];
    if ($fecha === 'a confirmar' || $fecha === '') {
        $datos['activo'] = '1';
    } else {
        $hoy = date('Y-m-d');
        $datos['activo'] = (strtotime($fecha) >= strtotime($hoy)) ? '1' : '0';
    }

    // Verificar si tiene ventas
    $datos['tiene_ventas'] = viaje_tiene_ventas($datos['dueno'], $nombre_viaje) ? '1' : '0';

    // Calcular ventas por micro de la terminal actual (si corresponde)
    $vendidos_por_micro = [];
    if ($nombre_terminal !== null) {
        $contenedor_ventas = obtener_contenedor_ventas_dueno($datos['dueno']);
        if ($contenedor_ventas) {
            $venta_iter = hmi($contenedor_ventas);
            while ($venta_iter) {
                $nodo_terminal_venta = $venta_iter->adyacente('terminal');
                $nodo_micro_venta = $venta_iter->adyacente('micro');
                $nodo_viaje_venta = $venta_iter->adyacente('viaje');

                if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal
                    && $nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje
                    && $nodo_micro_venta) {
                    $micro_id = $nodo_micro_venta->id();
                    $cabeza = $venta_iter->adyacente('asientos');
                    $cantidad = 0;
                    if ($cabeza) {
                        $asiento = $cabeza->adyacente('primer');
                        $seg = 0;
                        while ($asiento && $seg < 100) {
                            $cantidad++;
                            $asiento = $asiento->adyacente('siguiente');
                            $seg++;
                        }
                    }
                    $vendidos_por_micro[$micro_id] = ($vendidos_por_micro[$micro_id] ?? 0) + $cantidad;
                }
                $venta_iter = hd($venta_iter);
            }
        }
    }

    // Pre-cargar el contenedor de empresas del dueño (para resolver nombres visibles)
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_dueno_iter = $raiz_usuarios ? $raiz_usuarios->adyacente($datos['dueno']) : null;
    $nodo_empresas = $nodo_dueno_iter ? $nodo_dueno_iter->adyacente('empresas') : null;

    // Micros
    $micros = [];
    $nodo_micros = $nodo_viaje->adyacente('micros');
    if ($nodo_micros) {
        $adyacentes_micros = (array) $nodo_micros->adyacentes();
        foreach ($adyacentes_micros as $nombre_micro => $nodo_micro) {
            // Identificador de empresa: puede venir como nodo enlazado (micros nuevos)
            // o como nodo suelto cuyo dato es el string (micros viejos).
            $identificador_empresa = '';
            $nodo_empresa_enlazada = $nodo_micro->adyacente('empresa');
            if ($nodo_empresa_enlazada) {
                $identificador_empresa = $nodo_empresa_enlazada->dato();
            }

            // Nombre visible: buscar siempre la empresa real en el contenedor del dueño
            // usando el identificador. Esto funciona para micros nuevos y viejos.
            $nombre_empresa = $identificador_empresa;
            if ($identificador_empresa !== '' && $nodo_empresas) {
                $nodo_empresa_real = $nodo_empresas->adyacente($identificador_empresa);
                if ($nodo_empresa_real && $nodo_empresa_real->adyacente('nombre')) {
                    $nombre_empresa = $nodo_empresa_real->adyacente('nombre')->dato();
                }
            }

            // Patente: preferentemente desde vehiculo_copia (config nueva), fallback al enlace viejo
            $patente = '';
            $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
            if ($nodo_copia) {
                $patente = $nodo_copia->dato();
            } else {
                $nodo_patente = $nodo_micro->adyacente('patente');
                if ($nodo_patente) $patente = $nodo_patente->dato();
            }

            // Nombre visible del vehículo (desde la copia clonada)
            $nombre_vehiculo = $patente;
            if ($nodo_copia && $nodo_copia->adyacente('nombre')) {
                $nombre_vehiculo = $nodo_copia->adyacente('nombre')->dato();
            }

            $micros[] = [
                'nombre_micro' => $nombre_micro,
                'empresa' => $identificador_empresa,
                'patente' => $patente,
                'nombre_empresa' => $nombre_empresa,
                'nombre_vehiculo' => $nombre_vehiculo,
                'monto' => $nodo_micro->adyacente('monto') ? $nodo_micro->adyacente('monto')->dato() : '0',
                'ocupacion' => $nodo_micro->adyacente('ocupacion') ? $nodo_micro->adyacente('ocupacion')->dato() : '0',
                'seleccionados' => $nodo_micro->adyacente('seleccionados') ? $nodo_micro->adyacente('seleccionados')->dato() : '0',
                'vendidos' => $nodo_micro->adyacente('vendidos') ? $nodo_micro->adyacente('vendidos')->dato() : '0',
                'reservados' => $nodo_micro->adyacente('reservados') ? $nodo_micro->adyacente('reservados')->dato() : '0',
                'disponibles' => $nodo_micro->adyacente('disponibles') ? $nodo_micro->adyacente('disponibles')->dato() : '0',
                'vendidos_aqui' => ($nombre_terminal !== null) ? (string)($vendidos_por_micro[$nodo_micro->id()] ?? 0) : null,
            ];
        }
    }
    $datos['micros'] = $micros;

    // Terminales autorizadas
    $terminales = [];
    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if ($nodo_terminales) {
        $adyacentes_terminales = (array) $nodo_terminales->adyacentes();
        foreach ($adyacentes_terminales as $nombre_terminal_iter => $nodo_terminal) {
            $terminales[] = $nombre_terminal_iter;
        }
    }
    $datos['terminales_autorizadas'] = $terminales;

    // Opciones avanzadas (definida en ViajeOpciones.php)
    $datos['opciones_avanzadas'] = obtener_opciones_avanzadas_viaje($datos['dueno'], $nombre_viaje);
    return $datos;
}

/**
 * Verifica si un viaje tiene ventas registradas.
 */
function viaje_tiene_ventas(string $nombre_dueno, string $nombre_viaje): bool {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return false;

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return false;

    $contenedor_ventas = $nodo_dueno->adyacente('ventas');
    if (!$contenedor_ventas) return false;

    $actual = hmi($contenedor_ventas);
    while ($actual) {
        $nodo_viaje_venta = $actual->adyacente('viaje');
        if ($nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje) {
            return true;
        }
        $actual = hd($actual);
    }
    return false;
}

/**
 * Guarda la lista de paradas intermedias en el nodo viaje.
 * Reemplaza por completo la lista actual.
 *
 * @param Nodo $nodo_viaje Nodo del viaje.
 * @param array $paradas Lista de strings con las paradas.
 * @return void
 */
function _guardar_paradas_intermedias(Nodo $nodo_viaje, array $paradas): void {
    $nodo_paradas = $nodo_viaje->adyacente('paradas_intermedias');
    if (!$nodo_paradas) {
        $nodo_paradas = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_paradas, 'paradas_intermedias');
    }

    // Limpiar la lista actual
    while ($hijo = hmi($nodo_paradas)) {
        eliminar_hmi($nodo_paradas);
    }

    // Insertar en orden inverso usando _hmi (que agrega al inicio)
    $paradas_limpias = [];
    foreach ($paradas as $parada) {
        $parada = trim((string)$parada);
        if ($parada !== '') $paradas_limpias[] = $parada;
    }
    $paradas_limpias = array_reverse($paradas_limpias);
    foreach ($paradas_limpias as $parada) {
        $nodo_parada = Nodo::crear_con_dato($parada);
        _hmi($nodo_paradas, $nodo_parada);
    }
}

/**
 * Agrega un nuevo viaje.
 */
function agregar_viaje(array $datos): array {
    $nombre_dueno = $datos['nombre_dueno'] ?? '';
    $nombre_viaje = $datos['nombre_viaje'] ?? '';
    if (empty($nombre_dueno) || empty($nombre_viaje)) {
        return ['exito' => false, 'error' => 'Dueño y nombre de viaje son obligatorios'];
    }

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    if ($nodo_viajes->adyacente($nombre_viaje)) {
        return ['exito' => false, 'error' => 'Ya existe un viaje con ese nombre'];
    }

    $nodo_viaje = Nodo::crear_con_dato($nombre_viaje);
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'dueno');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['nombre'] ?? ''), 'nombre');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['fecha'] ?? ''), 'fecha');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['hora'] ?? ''), 'hora');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['origen'] ?? ''), 'origen');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['destino'] ?? ''), 'destino');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'ocupacion');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'disponibles');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'seleccionados');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'vendidos');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'reservados');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'micros');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'terminales_autorizadas');

    // Guardar paradas intermedias si las hay
    $paradas = [];
    if (isset($datos['paradas_intermedias'])) {
        if (is_array($datos['paradas_intermedias'])) {
            $paradas = $datos['paradas_intermedias'];
        } elseif (is_string($datos['paradas_intermedias'])) {
            $paradas = json_decode($datos['paradas_intermedias'], true) ?: [];
        }
    }
    if (!empty($paradas)) {
        _guardar_paradas_intermedias($nodo_viaje, $paradas);
    }

    $nodo_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Edita los datos básicos de un viaje.
 */
function editar_viaje(string $nombre_viaje, array $datos): array {
    $nombre_dueno = $datos['nombre_dueno'] ?? '';
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    foreach (['nombre', 'fecha', 'hora', 'origen', 'destino'] as $campo) {
        if (isset($datos[$campo])) {
            $nodo_campo = $nodo_viaje->adyacente($campo);
            if ($nodo_campo) $nodo_campo->_dato($datos[$campo]);
            else $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos[$campo]), $campo);
        }
    }

    // Guardar paradas intermedias si se enviaron
    if (isset($datos['paradas_intermedias'])) {
        $paradas = $datos['paradas_intermedias'];
        if (is_string($paradas)) {
            $paradas = json_decode($paradas, true) ?: [];
        }
        _guardar_paradas_intermedias($nodo_viaje, is_array($paradas) ? $paradas : []);
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Elimina un viaje.
 */
function eliminar_viaje(string $nombre_viaje, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    // Verificar si tiene ventas
    if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {
        return ['exito' => false, 'error' => 'No se pueden eliminar viajes con ventas ya realizadas'];
    }

    // TODO: eliminar nodos huérfanos
    $nodo_viajes->eliminar_adyacente($nombre_viaje);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Guarda un viaje (alta o edición) junto con sus opciones avanzadas.
 */
function guardar_viaje_completo(array $datos): array {
    $nombre_dueno = $datos['nombre_dueno'] ?? '';
    $nombre_viaje = $datos['nombre_viaje'] ?? '';
    if (empty($nombre_dueno) || empty($nombre_viaje)) {
        return ['exito' => false, 'error' => 'Dueño y nombre de viaje son obligatorios'];
    }

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $es_alta = !$nodo_viajes->adyacente($nombre_viaje);
    if ($es_alta) {
        // Crear viaje nuevo
        $nodo_viaje = Nodo::crear_con_dato($nombre_viaje);
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($nombre_dueno), 'dueno');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['nombre'] ?? ''), 'nombre');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['fecha'] ?? ''), 'fecha');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['hora'] ?? ''), 'hora');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['origen'] ?? ''), 'origen');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos['destino'] ?? ''), 'destino');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'ocupacion');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'disponibles');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'seleccionados');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'vendidos');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'reservados');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'micros');
        $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'terminales_autorizadas');
        $nodo_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);
    } else {
        // Editar viaje existente
        $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
        foreach (['nombre', 'fecha', 'hora', 'origen', 'destino'] as $campo) {
            if (isset($datos[$campo])) {
                $nodo_campo = $nodo_viaje->adyacente($campo);
                if ($nodo_campo) $nodo_campo->_dato($datos[$campo]);
                else $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos[$campo]), $campo);
            }
        }
    }

    // Guardar paradas intermedias
    $paradas = [];
    if (isset($datos['paradas_intermedias'])) {
        if (is_array($datos['paradas_intermedias'])) {
            $paradas = $datos['paradas_intermedias'];
        } elseif (is_string($datos['paradas_intermedias'])) {
            $paradas = json_decode($datos['paradas_intermedias'], true) ?: [];
        }
    }
    _guardar_paradas_intermedias($nodo_viaje, $paradas);

    // Guardar opciones avanzadas
    $opciones = [
        'mostrar_ficha_medica' => $datos['mostrar_ficha_medica'] ?? '0',
        'restriccion_edad' => $datos['restriccion_edad'] ?? '0',
        'edad_minima' => $datos['edad_minima'] ?? '18',
        'edad_maxima' => $datos['edad_maxima'] ?? '80',
    ];
    $resultado_opciones = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones);
    if (!$resultado_opciones['exito']) {
        return $resultado_opciones;
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

// Incluir submódulos de viajes
require_once __DIR__ . '/ViajeMicros.php';
require_once __DIR__ . '/ViajeAsientos.php';
require_once __DIR__ . '/ViajeOpciones.php';