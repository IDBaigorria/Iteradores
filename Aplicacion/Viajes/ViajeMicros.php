<?php
/**
 * Funciones de gestión de micros dentro de viajes.
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

/**
 * Agrega un micro a un viaje, clonando el vehículo original.
 */
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

/**
 * Elimina un micro de un viaje.
 */
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

/**
 * Actualiza el monto de un micro.
 */
function actualizar_monto_micro(string $nombre_viaje, string $nombre_micro, string $monto, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $nodo_monto = $nodo_micro->adyacente('monto');
    if ($nodo_monto) {
        $nodo_monto->_dato($monto);
    } else {
        $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), 'monto');
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Obtiene la información completa de un micro para mostrarla en el pasaje.
 */
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

/**
 * Actualiza los contadores totales del viaje (ocupación, disponibles, seleccionados, vendidos).
 */
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