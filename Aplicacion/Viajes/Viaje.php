<?php
/**
 * Gestión de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.8
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Clona la estructura completa de un vehículo original para asociarla a un viaje.
 *
 * @param Nodo $nodo_vehiculo_original Nodo del vehículo original.
 * @return Nodo Nodo copia del vehículo.
 */
function clonar_vehiculo($nodo_vehiculo_original) {
    $patente = $nodo_vehiculo_original->dato();
    $nodo_copia = Nodo::crear_con_dato($patente);

    // Copiar nombre
    $nodo_nombre = $nodo_vehiculo_original->adyacente('nombre');
    if ($nodo_nombre) {
        $nodo_copia->_adyacente_en(Nodo::crear_con_dato($nodo_nombre->dato()), 'nombre');
    }

    // Copiar foto
    $nodo_foto = $nodo_vehiculo_original->adyacente('foto');
    if ($nodo_foto) {
        $nodo_copia->_adyacente_en(Nodo::crear_con_dato($nodo_foto->dato()), 'foto');
    }

    // Copiar asientos (estructura completa)
    $nodo_asientos_original = $nodo_vehiculo_original->adyacente('asientos');
    if ($nodo_asientos_original) {
        $nodo_asientos_copia = Nodo::crear_con_dato($nodo_asientos_original->dato());

        for ($i = 1; $i <= 2; $i++) {
            $piso_original = $nodo_asientos_original->adyacente("piso_$i");
            if (!$piso_original) continue;

            $nodo_piso_copia = Nodo::crear_con_dato('');

            // Copiar filas y columnas
            $filas = $piso_original->adyacente('filas');
            $columnas = $piso_original->adyacente('columnas');
            if ($filas) {
                $nodo_piso_copia->_adyacente_en(Nodo::crear_con_dato($filas->dato()), 'filas');
            }
            if ($columnas) {
                $nodo_piso_copia->_adyacente_en(Nodo::crear_con_dato($columnas->dato()), 'columnas');
            }

            // Copiar lista circular de asientos
            $cabeza_original = $piso_original->adyacente('asientos');
            if ($cabeza_original) {
                $cabeza_copia = Nodo::crear_con_dato('');
                $nodo_piso_copia->_adyacente_en($cabeza_copia, 'asientos');

                $actual = $cabeza_original->adyacente('primer');
                $primer_copia = null;
                $anterior_copia = null;
                // Usar comparación por ID para evitar problemas de instancia
                while ($actual && $actual->id() !== $cabeza_original->id()) {
                    $nodo_asiento_copia = Nodo::crear_con_dato($actual->dato()); // número de asiento

                    $fila = $actual->adyacente('fila');
                    $columna = $actual->adyacente('columna');
                    if ($fila) {
                        $nodo_asiento_copia->_adyacente_en(Nodo::crear_con_dato($fila->dato()), 'fila');
                    }
                    if ($columna) {
                        $nodo_asiento_copia->_adyacente_en(Nodo::crear_con_dato($columna->dato()), 'columna');
                    }

                    if ($anterior_copia) {
                        $anterior_copia->_adyacente_en($nodo_asiento_copia, 'siguiente');
                    } else {
                        $primer_copia = $nodo_asiento_copia;
                    }
                    $anterior_copia = $nodo_asiento_copia;

                    $actual = $actual->adyacente('siguiente');
                }

                if ($anterior_copia) {
                    $anterior_copia->_adyacente_en($cabeza_copia, 'siguiente');
                }
                if ($primer_copia) {
                    $cabeza_copia->_adyacente_en($primer_copia, 'primer');
                }
            }

            $nodo_asientos_copia->_adyacente_en($nodo_piso_copia, "piso_$i");
        }

        $nodo_copia->_adyacente_en($nodo_asientos_copia, 'asientos');
    }

    return $nodo_copia;
}

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

    $adyacentes = $nodo_viajes->adyacentes();
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
    // Obtener dueño de la terminal
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);
    if (!$nodo_terminal) return [];

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno) return [];

    $nombre_dueno = $nodo_dueno->dato();
    $viajes_dueno = listar_viajes_de_dueno($nombre_dueno);

    // Filtrar solo autorizados
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

function agregar_viaje(array $datos): array {
    $nombre_dueno = $datos['nombre_dueno'] ?? '';
    $nombre_viaje = $datos['nombre_viaje'] ?? '';
    if (empty($nombre_dueno) || empty($nombre_viaje)) {
        return ['exito' => false, 'error' => 'Dueño y nombre de viaje son obligatorios'];
    }

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) {
        return ['exito' => false, 'error' => 'Dueño no encontrado'];
    }

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
    // Se asume que el dueño es el que se pasa en $datos['nombre_dueno']
    $nombre_dueno = $datos['nombre_dueno'] ?? '';
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    foreach (['nombre', 'fecha', 'hora', 'origen', 'destino'] as $campo) {
        if (isset($datos[$campo])) {
            $nodo_campo = $nodo_viaje->adyacente($campo);
            if ($nodo_campo) {
                $nodo_campo->_dato($datos[$campo]);
            } else {
                $nodo_viaje->_adyacente_en(Nodo::crear_con_dato($datos[$campo]), $campo);
            }
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

    // TODO: eliminar nodos huérfanos (micros, copias, etc.)
    $nodo_viajes->eliminar_adyacente($nombre_viaje);
    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    // Buscar empresa y vehículo
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios'];

    $nodo_empresa_encontrada = null;
    $nodo_vehiculo_encontrado = null;
    // Solo buscar dentro del dueño
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

    // Clonar vehículo
    $nodo_copia = clonar_vehiculo($nodo_vehiculo);

    // Crear micro
    $nodo_micro = Nodo::crear_con_dato('');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($nombre_empresa), 'empresa');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($nombre_vehiculo), 'patente');
    $nodo_micro->_adyacente_en($nodo_copia, 'vehiculo_copia');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'ocupacion');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'seleccionados');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'vendidos');

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

    // TODO: eliminar copia del vehículo
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
        foreach ($nodo_micros->adyacentes() as $nodo_micro) {
            $total_ocupacion += (int)($nodo_micro->adyacente('ocupacion')->dato() ?? 0);
            $total_seleccionados += (int)($nodo_micro->adyacente('seleccionados')->dato() ?? 0);
            $total_vendidos += (int)($nodo_micro->adyacente('vendidos')->dato() ?? 0);
        }
    }

    $total_disponibles = $total_ocupacion - $total_vendidos - $total_seleccionados;

    $nodo_viaje->adyacente('ocupacion')->_dato((string)$total_ocupacion);
    $nodo_viaje->adyacente('seleccionados')->_dato((string)$total_seleccionados);
    $nodo_viaje->adyacente('vendidos')->_dato((string)$total_vendidos);
    $nodo_viaje->adyacente('disponibles')->_dato((string)$total_disponibles);
}