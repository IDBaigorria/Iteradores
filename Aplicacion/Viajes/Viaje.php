<?php
/**
 * Núcleo de gestión de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.26
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

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
    $viajes_dueno = listar_viajes_de_dueno($nombre_dueno);

    $viajes_autorizados = [];
    foreach ($viajes_dueno as $viaje) {
        if (in_array($nombre_terminal, $viaje['terminales_autorizadas'])) {
            $viajes_autorizados[] = $viaje;
        }
    }
    return $viajes_autorizados;
}

/**
 * Formatea los datos de un viaje, incluyendo micros, terminales y opciones.
 */
function formatear_viaje(string $nombre_viaje, $nodo_viaje): array {
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

    // Micros
    $micros = [];
    $nodo_micros = $nodo_viaje->adyacente('micros');
    if ($nodo_micros) {
        $adyacentes_micros = (array) $nodo_micros->adyacentes();
        foreach ($adyacentes_micros as $nombre_micro => $nodo_micro) {
            $micros[] = [
                'nombre_micro' => $nombre_micro,
                'empresa' => $nodo_micro->adyacente('empresa') ? $nodo_micro->adyacente('empresa')->dato() : '',
                'patente' => $nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '',
                'monto' => $nodo_micro->adyacente('monto') ? $nodo_micro->adyacente('monto')->dato() : '0',
                'ocupacion' => $nodo_micro->adyacente('ocupacion') ? $nodo_micro->adyacente('ocupacion')->dato() : '0',
                'seleccionados' => $nodo_micro->adyacente('seleccionados') ? $nodo_micro->adyacente('seleccionados')->dato() : '0',
                'vendidos' => $nodo_micro->adyacente('vendidos') ? $nodo_micro->adyacente('vendidos')->dato() : '0',
            ];
        }
    }
    $datos['micros'] = $micros;

    // Terminales autorizadas
    $terminales = [];
    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if ($nodo_terminales) {
        $adyacentes_terminales = (array) $nodo_terminales->adyacentes();
        foreach ($adyacentes_terminales as $nombre_terminal => $nodo_terminal) {
            $terminales[] = $nombre_terminal;
        }
    }
    $datos['terminales_autorizadas'] = $terminales;

    // Opciones avanzadas (definida en ViajeOpciones.php)
    $datos['opciones_avanzadas'] = obtener_opciones_avanzadas_viaje($datos['dueno'], $nombre_viaje);
    return $datos;
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
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'micros');
    $nodo_viaje->_adyacente_en(Nodo::crear_con_dato(''), 'terminales_autorizadas');

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