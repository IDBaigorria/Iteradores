<?php
/**
 * Gestión de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.12
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

    return $datos;
}

/**
 * Clona la estructura completa de un vehículo original para asociarla a un viaje.
 */
function clonar_vehiculo($nodo_vehiculo_original) {
    $patente = $nodo_vehiculo_original->dato();
    $nodo_copia = Nodo::crear_con_dato($patente);

    $nodo_nombre = $nodo_vehiculo_original->adyacente('nombre');
    if ($nodo_nombre) {
        $nodo_copia->_adyacente_en(Nodo::crear_con_dato($nodo_nombre->dato()), 'nombre');
    }

    $nodo_foto = $nodo_vehiculo_original->adyacente('foto');
    if ($nodo_foto) {
        $nodo_copia->_adyacente_en(Nodo::crear_con_dato($nodo_foto->dato()), 'foto');
    }

    $nodo_asientos_original = $nodo_vehiculo_original->adyacente('asientos');
    if ($nodo_asientos_original) {
        $nodo_asientos_copia = Nodo::crear_con_dato($nodo_asientos_original->dato());

        for ($i = 1; $i <= 2; $i++) {
            $piso_original = $nodo_asientos_original->adyacente("piso_$i");
            if (!$piso_original) continue;

            $nodo_piso_copia = Nodo::crear_con_dato('');

            $filas = $piso_original->adyacente('filas');
            $columnas = $piso_original->adyacente('columnas');
            if ($filas) $nodo_piso_copia->_adyacente_en(Nodo::crear_con_dato($filas->dato()), 'filas');
            if ($columnas) $nodo_piso_copia->_adyacente_en(Nodo::crear_con_dato($columnas->dato()), 'columnas');

            $cabeza_original = $piso_original->adyacente('asientos');
            if ($cabeza_original) {
                $cabeza_copia = Nodo::crear_con_dato('');
                $nodo_piso_copia->_adyacente_en($cabeza_copia, 'asientos');

                $actual = $cabeza_original->adyacente('primer');
                $primer_copia = null;
                $anterior_copia = null;
                while ($actual && $actual->id() !== $cabeza_original->id()) {
                    $nodo_asiento_copia = Nodo::crear_con_dato($actual->dato());

                    $fila = $actual->adyacente('fila');
                    $columna = $actual->adyacente('columna');
                    if ($fila) $nodo_asiento_copia->_adyacente_en(Nodo::crear_con_dato($fila->dato()), 'fila');
                    if ($columna) $nodo_asiento_copia->_adyacente_en(Nodo::crear_con_dato($columna->dato()), 'columna');

                    // Inicializar estado libre
                    $nodo_asiento_copia->_adyacente_en(Nodo::crear_con_dato('libre'), 'estado');

                    if ($anterior_copia) {
                        $anterior_copia->_adyacente_en($nodo_asiento_copia, 'siguiente');
                    } else {
                        $primer_copia = $nodo_asiento_copia;
                    }
                    $anterior_copia = $nodo_asiento_copia;

                    $actual = $actual->adyacente('siguiente');
                }

                if ($anterior_copia) $anterior_copia->_adyacente_en($cabeza_copia, 'siguiente');
                if ($primer_copia) $cabeza_copia->_adyacente_en($primer_copia, 'primer');
            }

            $nodo_asientos_copia->_adyacente_en($nodo_piso_copia, "piso_$i");
        }

        $nodo_copia->_adyacente_en($nodo_asientos_copia, 'asientos');
    }

    return $nodo_copia;
}

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

function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = '0'): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios'];

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) return ['exito' => false, 'error' => 'El dueño no tiene empresas'];

    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
    if (!$nodo_empresa) return ['exito' => false, 'error' => 'Empresa no encontrada'];

    $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
    if (!$nodo_vehiculos) return ['exito' => false, 'error' => 'La empresa no tiene vehículos'];

    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);
    if (!$nodo_vehiculo) return ['exito' => false, 'error' => 'Vehículo no encontrado'];

    $nodo_copia = clonar_vehiculo($nodo_vehiculo);

    $monto = (string) $monto;
    if (!is_numeric($monto) || (float)$monto < 0) {
        return ['exito' => false, 'error' => 'Monto inválido'];
    }

    $nodo_micro = Nodo::crear_con_dato('');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($nombre_empresa), 'empresa');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($nombre_vehiculo), 'patente');
    $nodo_micro->_adyacente_en($nodo_copia, 'vehiculo_copia');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), 'monto');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'ocupacion');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'seleccionados');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'vendidos');
    // Enlace al viaje
    $nodo_micro->_adyacente_en($nodo_viaje, 'viaje');

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) {
        $nodo_micros = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_micros, 'micros');
    }

    $adyacentes_micros = (array) $nodo_micros->adyacentes();
    $indice = count($adyacentes_micros) + 1;
    $nombre_micro = 'micro_' . $indice;
    $nodo_micros->_adyacente_en($nodo_micro, $nombre_micro);

    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true, 'nombre_micro' => $nombre_micro];
}

function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_micros->eliminar_adyacente($nombre_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

function agregar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) {
        $nodo_terminales = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_terminales, 'terminales_autorizadas');
    }

    if ($nodo_terminales->adyacente($nombre_terminal)) {
        return ['exito' => false, 'error' => 'La terminal ya está autorizada'];
    }

    $nodo_terminales->_adyacente_en(Nodo::crear_con_dato($nombre_terminal), $nombre_terminal);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

function eliminar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) return ['exito' => false, 'error' => 'No hay terminales'];

    $nodo_terminales->eliminar_adyacente($nombre_terminal);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

function actualizar_contadores_viaje(string $nombre_viaje, string $nombre_dueno): void {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return;

    $total_ocupacion = 0;
    $total_seleccionados = 0;
    $total_vendidos = 0;
    $total_disponibles = 0;

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if ($nodo_micros) {
        $adyacentes_micros = (array) $nodo_micros->adyacentes();
        foreach ($adyacentes_micros as $nodo_micro) {
            $total_ocupacion += (int)($nodo_micro->adyacente('ocupacion') ? $nodo_micro->adyacente('ocupacion')->dato() : 0);
            $total_seleccionados += (int)($nodo_micro->adyacente('seleccionados') ? $nodo_micro->adyacente('seleccionados')->dato() : 0);
            $total_vendidos += (int)($nodo_micro->adyacente('vendidos') ? $nodo_micro->adyacente('vendidos')->dato() : 0);
        }
    }

    $total_disponibles = $total_ocupacion - $total_vendidos - $total_seleccionados;

    $nodo_viaje->adyacente('ocupacion')->_dato((string)$total_ocupacion);
    $nodo_viaje->adyacente('seleccionados')->_dato((string)$total_seleccionados);
    $nodo_viaje->adyacente('vendidos')->_dato((string)$total_vendidos);
    $nodo_viaje->adyacente('disponibles')->_dato((string)$total_disponibles);
}

function obtener_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $empresa = $nodo_micro->adyacente('empresa') ? $nodo_micro->adyacente('empresa')->dato() : '';
    $patente = $nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '';
    $monto = $nodo_micro->adyacente('monto') ? $nodo_micro->adyacente('monto')->dato() : '0';
    $ocupacion = $nodo_micro->adyacente('ocupacion') ? $nodo_micro->adyacente('ocupacion')->dato() : '0';
    $seleccionados = $nodo_micro->adyacente('seleccionados') ? $nodo_micro->adyacente('seleccionados')->dato() : '0';
    $vendidos = $nodo_micro->adyacente('vendidos') ? $nodo_micro->adyacente('vendidos')->dato() : '0';

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $nombre = $nodo_copia->adyacente('nombre') ? $nodo_copia->adyacente('nombre')->dato() : '';
    $foto = $nodo_copia->adyacente('foto') ? $nodo_copia->adyacente('foto')->dato() : '';

    $configuracion = ['pisos' => []];
    $nodo_asientos_copia = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos_copia) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos_copia->adyacente("piso_$i");
            if ($piso) {
                $config_piso = obtener_configuracion_piso_con_estado($piso);
                if ($config_piso) {
                    $configuracion['pisos'][] = $config_piso;
                }
            }
        }
    }

    return [
        'exito' => true,
        'micro' => [
            'nombre_micro' => $nombre_micro,
            'empresa' => $empresa,
            'patente' => $patente,
            'monto' => $monto,
            'ocupacion' => $ocupacion,
            'seleccionados' => $seleccionados,
            'vendidos' => $vendidos,
            'nombre' => $nombre,
            'foto' => $foto,
            'configuracion' => $configuracion
        ]
    ];
}

function obtener_configuracion_piso_con_estado($nodo_piso): ?array {
    $nodo_filas = $nodo_piso->adyacente('filas');
    $nodo_columnas = $nodo_piso->adyacente('columnas');
    if (!$nodo_filas || !$nodo_columnas) return null;

    $filas = (int)$nodo_filas->dato();
    $columnas = (int)$nodo_columnas->dato();

    $asientos = [];
    $nodo_cabeza = $nodo_piso->adyacente('asientos');
    if ($nodo_cabeza) {
        $actual = $nodo_cabeza->adyacente('primer');
        while ($actual && $actual->id() !== $nodo_cabeza->id()) {
            $fila = $actual->adyacente('fila');
            $columna = $actual->adyacente('columna');
            $estado = $actual->adyacente('estado');
            $seleccionado_por = $actual->adyacente('seleccionado_por');

            $asiento_info = [
                'fila' => $fila ? $fila->dato() : '',
                'columna' => $columna ? $columna->dato() : '',
                'numero' => $actual->dato(),
                'estado' => $estado ? $estado->dato() : 'libre',
                'seleccionado_por' => $seleccionado_por ? $seleccionado_por->dato() : null,
                'pasajero' => null,
                'venta' => null
            ];

            if (in_array($asiento_info['estado'], ['vendido', 'no disponible'])) {
                $nodo_pasajero = $actual->adyacente('pasajero');
                if ($nodo_pasajero) {
                    $pasajero = [];
                    $adyacentes_pasajero = (array) $nodo_pasajero->adyacentes();
                    foreach ($adyacentes_pasajero as $campo => $nodo_campo) {
                        $pasajero[$campo] = $nodo_campo->dato();
                    }
                    $asiento_info['pasajero'] = $pasajero;
                }
            }

            if ($asiento_info['estado'] === 'vendido') {
                $nodo_venta = $actual->adyacente('venta');
                if ($nodo_venta) {
                    $asiento_info['venta'] = true;
                }
            }

            $asientos[] = $asiento_info;
            $actual = $actual->adyacente('siguiente');
        }
    }

    return [
        'filas' => $filas,
        'columnas' => $columnas,
        'asientos' => $asientos
    ];
}

function obtener_estados_asientos_micro(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $asientos_estados = [];
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $fila = $actual->adyacente('fila');
                $columna = $actual->adyacente('columna');
                $estado = $actual->adyacente('estado');
                $seleccionado_por = $actual->adyacente('seleccionado_por');
                $asientos_estados[] = [
                    'fila' => $fila ? $fila->dato() : '',
                    'columna' => $columna ? $columna->dato() : '',
                    'numero' => $actual->dato(),
                    'estado' => $estado ? $estado->dato() : 'libre',
                    'seleccionado_por' => $seleccionado_por ? $seleccionado_por->dato() : null
                ];
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    return ['exito' => true, 'asientos' => $asientos_estados];
}

function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    // Obtener nodo terminal
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;
    if (!$nodo_terminal) return ['exito' => false, 'error' => 'Terminal no encontrada'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    // Buscar asiento
    $nodo_asiento_encontrado = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento_encontrado = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento_encontrado) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento_encontrado->adyacente('estado');
    if ($estado && $estado->dato() !== 'libre') {
        return ['exito' => false, 'error' => 'El asiento ya no está libre'];
    }

    // Actualizar estado y seleccionado_por
    if ($estado) $estado->_dato('seleccionado');
    else $nodo_asiento_encontrado->_adyacente_en(Nodo::crear_con_dato('seleccionado'), 'estado');

    $sel_por = $nodo_asiento_encontrado->adyacente('seleccionado_por');
    if ($sel_por) $sel_por->_dato($nombre_terminal);
    else $nodo_asiento_encontrado->_adyacente_en($nodo_terminal, 'seleccionado_por');

    // Obtener o crear venta actual de la terminal
    $venta_actual = $nodo_terminal->adyacente('venta_actual');
    if (!$venta_actual) {
        $venta_actual = Nodo::crear_con_dato('');
        $nodo_terminal->_adyacente_en($venta_actual, 'venta_actual');
        $venta_actual->_adyacente_en($nodo_terminal, 'terminal');
        $venta_actual->_adyacente_en(Nodo::crear_con_dato(''), 'asientos');
    }

    // Asegurar que el micro de la venta actual coincida
    $micro_venta = $venta_actual->adyacente('micro');
    if ($micro_venta) {
        if ($micro_venta->dato() !== $nombre_micro) {
            // Si cambia de micro, se limpiará la lista más adelante
            $micro_venta->_dato($nombre_micro);
            $limpiar_lista = true;
        } else {
            $limpiar_lista = false;
        }
    } else {
        $venta_actual->_adyacente_en(Nodo::crear_con_dato($nombre_micro), 'micro');
        $limpiar_lista = false;
    }

    // Reconstruir lista circular de asientos de la venta
    $cabeza_venta = $venta_actual->adyacente('asientos');

    // Recolectar nodos actuales de la lista (si no hay que limpiar)
    $nodos_venta = [];
    if (!$limpiar_lista && $cabeza_venta) {
        $actual_venta = $cabeza_venta->adyacente('primer');
        while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {
            $nodos_venta[] = $actual_venta;
            $actual_venta = $actual_venta->adyacente('siguiente');
        }
    } else {
        // Si hay que limpiar, se descartan los nodos antiguos (quedarán huérfanos)
        if ($cabeza_venta) {
            $cabeza_venta->eliminar_adyacente('primer');
            // Opcional: también eliminar enlaces 'siguiente' de los nodos para romper ciclos
            // No es crítico para la persistencia porque al reconstruir se sobreescriben
        }
    }

    // Eliminar enlaces 'siguiente' de los nodos recolectados (para evitar duplicados)
    foreach ($nodos_venta as $nodo_venta) {
        $nodo_venta->eliminar_adyacente('siguiente');
    }

    // Crear nuevo nodo para el asiento seleccionado
    $nodo_asiento_venta = Nodo::crear_con_dato('');
    $nodo_asiento_venta->_adyacente_en($nodo_asiento_encontrado, 'asiento');

    // Agregar a la lista de nodos a reconstruir
    $nodos_venta[] = $nodo_asiento_venta;

    // Reconstruir lista circular desde cero
    if (!empty($nodos_venta)) {
        $primer = $nodos_venta[0];
        $cabeza_venta->_adyacente_en($primer, 'primer');
        $anterior = null;
        foreach ($nodos_venta as $nodo_venta) {
            if ($anterior) {
                $anterior->_adyacente_en($nodo_venta, 'siguiente');
            }
            $anterior = $nodo_venta;
        }
        if ($anterior) {
            $anterior->_adyacente_en($cabeza_venta, 'siguiente');
        }
    } else {
        // Si la lista queda vacía, asegurarse de que no haya enlace primer residual
        $cabeza_venta->eliminar_adyacente('primer');
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    // Buscar asiento
    $nodo_asiento = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento) return ['exito' => false, 'error' => 'Asiento no encontrado'];

    $estado = $nodo_asiento->adyacente('estado');
    if (!$estado || $estado->dato() !== 'seleccionado') {
        return ['exito' => false, 'error' => 'El asiento no está seleccionado'];
    }

    $sel_por = $nodo_asiento->adyacente('seleccionado_por');
    if (!$sel_por || $sel_por->dato() !== $nombre_terminal) {
        return ['exito' => false, 'error' => 'No puedes deseleccionar un asiento de otra terminal'];
    }

    // Cambiar a libre y eliminar enlace seleccionado_por
    $estado->_dato('libre');
    $nodo_asiento->eliminar_adyacente('seleccionado_por');

    // Eliminar de la venta actual si existe
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;
    if ($nodo_terminal) {
        $venta_actual = $nodo_terminal->adyacente('venta_actual');
        if ($venta_actual) {
            $cabeza_venta = $venta_actual->adyacente('asientos');
            if ($cabeza_venta) {
                // Recolectar todos los nodos de la lista actual
                $nodos_venta = [];
                $actual_venta = $cabeza_venta->adyacente('primer');
                while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {
                    $nodos_venta[] = $actual_venta;
                    $actual_venta = $actual_venta->adyacente('siguiente');
                }

                // Eliminar lista actual
                $cabeza_venta->eliminar_adyacente('primer');
                foreach ($nodos_venta as $nodo_venta) {
                    $nodo_venta->eliminar_adyacente('siguiente');
                }

                // Filtrar el asiento a eliminar
                $nodos_filtrados = [];
                foreach ($nodos_venta as $nodo_venta) {
                    $asiento_ref = $nodo_venta->adyacente('asiento');
                    if ($asiento_ref && $asiento_ref->id() !== $nodo_asiento->id()) {
                        $nodos_filtrados[] = $nodo_venta;
                    }
                }

                // Reconstruir lista con los restantes
                if (!empty($nodos_filtrados)) {
                    $primer = $nodos_filtrados[0];
                    $cabeza_venta->_adyacente_en($primer, 'primer');
                    $anterior = null;
                    foreach ($nodos_filtrados as $nodo_venta) {
                        if ($anterior) {
                            $anterior->_adyacente_en($nodo_venta, 'siguiente');
                        }
                        $anterior = $nodo_venta;
                    }
                    if ($anterior) {
                        $anterior->_adyacente_en($cabeza_venta, 'siguiente');
                    }
                }
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}