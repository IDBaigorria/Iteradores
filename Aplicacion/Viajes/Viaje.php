<?php
/**
 * Núcleo de gestión de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.31
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
    // NOTA v1.5piloto.31: las claves del contenedor siguen siendo los nombres de las terminales,
    // pero los nodos destino son ahora "Nodo TerminalViaje" (nodo intermedio), no el Nodo Usuario
    // terminal directamente. Acá solo devolvemos la lista de nombres, que es lo que consume el front.
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
 * A partir de v1.5piloto.31:
 * - Reutiliza los nodos parada existentes cuando el nombre coincide, para no
 *   romper el enlace `punto_subida_bajada` de los Nodos TerminalViaje.
 * - Rechaza la operación si alguna parada que está siendo referenciada por algún
 *   TerminalViaje (vía `punto_subida_bajada`) quedaría fuera de la nueva lista.
 *
 * @param Nodo $nodo_viaje Nodo del viaje.
 * @param array $paradas Lista de strings con las paradas.
 * @return array Resultado con 'exito' => true, o 'exito' => false + 'error'.
 */
function _guardar_paradas_intermedias(Nodo $nodo_viaje, array $paradas): array {
    // 1. Normalizar la lista nueva (trim, sin vacíos, sin duplicados)
    $paradas_nuevas = [];
    foreach ($paradas as $parada) {
        $parada = trim((string)$parada);
        if ($parada !== '') $paradas_nuevas[] = $parada;
    }
    $paradas_nuevas = array_values(array_unique($paradas_nuevas));
    $set_nuevas = array_flip($paradas_nuevas);

    // 2. Validar que ninguna parada en uso quede fuera del set nuevo
    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if ($nodo_terminales) {
        $adyacentes = (array) $nodo_terminales->adyacentes();
        foreach ($adyacentes as $nombre_terminal => $nodo_tv) {
            // Saltar nodos viejos sin migrar (formato anterior a v1.5piloto.31)
            if (!$nodo_tv->adyacente('terminal')) continue;

            $nodo_punto = $nodo_tv->adyacente('punto_subida_bajada');
            if (!$nodo_punto) continue;

            $nombre_parada = $nodo_punto->dato();
            if (!isset($set_nuevas[$nombre_parada])) {
                return [
                    'exito' => false,
                    'error' => "No se puede eliminar la parada \"$nombre_parada\" porque está siendo usada como punto de subida/bajada por la terminal \"$nombre_terminal\". Primero hay que liberar esa configuración."
                ];
            }
        }
    }

    // 3. Indexar los nodos parada existentes por su dato (para reutilizarlos)
    $nodos_existentes = [];
    $nodo_paradas = $nodo_viaje->adyacente('paradas_intermedias');
    if ($nodo_paradas) {
        $actual = hmi($nodo_paradas);
        $seg = 0;
        while ($actual && $seg < 500) {
            $nodos_existentes[$actual->dato()] = $actual;
            $actual = hd($actual);
            $seg++;
        }
        // Vaciar la lista (los nodos en sí quedan vivos hasta que se reutilicen o queden huérfanos)
        while ($hijo = hmi($nodo_paradas)) {
            eliminar_hmi($nodo_paradas);
        }
    } else {
        $nodo_paradas = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_paradas, 'paradas_intermedias');
    }

    // 4. Reinsertar en orden (usando _hmi, que agrega al inicio, por eso invertimos)
    $paradas_invertidas = array_reverse($paradas_nuevas);
    foreach ($paradas_invertidas as $parada) {
        if (isset($nodos_existentes[$parada])) {
            $nodo_parada = $nodos_existentes[$parada];
        } else {
            $nodo_parada = Nodo::crear_con_dato($parada);
        }
        _hmi($nodo_paradas, $nodo_parada);
    }

    return ['exito' => true];
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
        $resultado_paradas = _guardar_paradas_intermedias($nodo_viaje, $paradas);
        if (!$resultado_paradas['exito']) {
            return $resultado_paradas;
        }
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
        $resultado_paradas = _guardar_paradas_intermedias($nodo_viaje, is_array($paradas) ? $paradas : []);
        if (!$resultado_paradas['exito']) {
            return $resultado_paradas;
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
 * Autoriza una terminal (punto de venta) en un viaje.
 *
 * A partir de v1.5piloto.31, cada autorización crea un Nodo TerminalViaje
 * (nodo intermedio, dato vacío) que contiene el enlace `terminal` hacia el
 * Nodo Usuario terminal, y cuelga del contenedor `terminales_autorizadas`
 * con el nombre de la terminal como clave.
 *
 * @param string $nombre_viaje     Identificador del viaje.
 * @param string $nombre_terminal  Nombre de usuario de la terminal.
 * @param string $nombre_dueno     Nombre de usuario del dueño.
 * @return array Resultado.
 */
function agregar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    // Verificar que exista el Nodo Usuario terminal
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios'];

    $nodo_usuario_terminal = $raiz_usuarios->adyacente($nombre_terminal);
    if (!$nodo_usuario_terminal) {
        return ['exito' => false, 'error' => 'La terminal no existe'];
    }

    // Obtener o crear el contenedor
    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) {
        $nodo_terminales = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_terminales, 'terminales_autorizadas');
    }

    if ($nodo_terminales->adyacente($nombre_terminal)) {
        return ['exito' => false, 'error' => 'La terminal ya está autorizada'];
    }

    // Crear nodo intermedio TerminalViaje
    $nodo_terminal_viaje = Nodo::crear_con_dato('');
    $nodo_terminal_viaje->_adyacente_en($nodo_usuario_terminal, 'terminal');
    $nodo_terminal_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'cambiar_punto_predeterminado');

    $nodo_terminales->_adyacente_en($nodo_terminal_viaje, $nombre_terminal);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Quita una terminal autorizada de un viaje.
 *
 * Desenlaza el Nodo TerminalViaje del contenedor y limpia sus enlaces internos.
 * El nodo intermedio queda huérfano (no se destruye), siguiendo el mismo
 * criterio que eliminar_micro_de_viaje.
 *
 * @param string $nombre_viaje     Identificador del viaje.
 * @param string $nombre_terminal  Nombre de usuario de la terminal.
 * @param string $nombre_dueno     Nombre de usuario del dueño.
 * @return array Resultado.
 */
function eliminar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) return ['exito' => false, 'error' => 'No hay terminales'];

    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);
    if (!$nodo_terminal_viaje) {
        return ['exito' => false, 'error' => 'La terminal no está autorizada'];
    }

    // Limpiar enlaces internos antes de desenlazar (evita dejar referencias colgando)
    $nodo_terminal_viaje->eliminar_adyacente('punto_subida_bajada');

    $nodo_terminales->eliminar_adyacente($nombre_terminal);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Obtiene las opciones configuradas para la combinación viaje + terminal.
 *
 * A partir de v1.5piloto.31. Devuelve un array con valores por defecto si
 * no existe el nodo intermedio o no tiene las opciones seteadas.
 *
 * @param string $nombre_dueno     Nombre de usuario del dueño.
 * @param string $nombre_viaje     Identificador del viaje.
 * @param string $nombre_terminal  Nombre de usuario de la terminal.
 * @return array Claves: cambiar_punto_predeterminado ('0'/'1'), punto_subida_bajada (string).
 */
function obtener_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {
    $defaults = [
        'cambiar_punto_predeterminado' => '0',
        'punto_subida_bajada' => '',
    ];

    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return $defaults;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return $defaults;

    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) return $defaults;

    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);
    if (!$nodo_terminal_viaje) return $defaults;

    $opciones = $defaults;

    $nodo_cambiar = $nodo_terminal_viaje->adyacente('cambiar_punto_predeterminado');
    if ($nodo_cambiar) $opciones['cambiar_punto_predeterminado'] = $nodo_cambiar->dato();

    $nodo_punto = $nodo_terminal_viaje->adyacente('punto_subida_bajada');
    if ($nodo_punto) $opciones['punto_subida_bajada'] = $nodo_punto->dato();

    return $opciones;
}

/**
 * Guarda las opciones configuradas para la combinación viaje + terminal.
 *
 * A partir de v1.5piloto.31.
 *
 * Reglas:
 * - `cambiar_punto_predeterminado` se sanitiza a "0" o "1".
 * - Si es "1" y `punto_subida_bajada` trae un nombre, se busca la parada
 *   existente en `paradas_intermedias` del viaje y se enlaza a ese nodo
 *   (nunca se crea un nodo nuevo para la parada).
 * - Si es "0" o `punto_subida_bajada` viene vacío, se elimina el enlace
 *   `punto_subida_bajada` si existía.
 *
 * @param string $nombre_dueno     Nombre de usuario del dueño.
 * @param string $nombre_viaje     Identificador del viaje.
 * @param string $nombre_terminal  Nombre de usuario de la terminal.
 * @param array  $opciones         Datos a guardar.
 * @return array Resultado.
 */
function guardar_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $opciones): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$nodo_terminales) return ['exito' => false, 'error' => 'El viaje no tiene terminales autorizadas'];

    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);
    if (!$nodo_terminal_viaje) {
        return ['exito' => false, 'error' => 'La terminal no está autorizada en este viaje'];
    }

    // Sanitizar entrada
    $cambiar = ($opciones['cambiar_punto_predeterminado'] ?? '0') === '1' ? '1' : '0';
    $punto = trim((string)($opciones['punto_subida_bajada'] ?? ''));

    // Actualizar cambiar_punto_predeterminado
    $nodo_cambiar = $nodo_terminal_viaje->adyacente('cambiar_punto_predeterminado');
    if ($nodo_cambiar) $nodo_cambiar->_dato($cambiar);
    else $nodo_terminal_viaje->_adyacente_en(Nodo::crear_con_dato($cambiar), 'cambiar_punto_predeterminado');

    // Gestionar punto_subida_bajada
    if ($cambiar === '1' && $punto !== '') {
        // Buscar el nodo parada existente en paradas_intermedias
        $nodo_parada_encontrado = null;
        $nodo_paradas = $nodo_viaje->adyacente('paradas_intermedias');
        if ($nodo_paradas) {
            $actual = hmi($nodo_paradas);
            $seg = 0;
            while ($actual && $seg < 100) {
                if ($actual->dato() === $punto) {
                    $nodo_parada_encontrado = $actual;
                    break;
                }
                $actual = hd($actual);
                $seg++;
            }
        }

        if (!$nodo_parada_encontrado) {
            return ['exito' => false, 'error' => 'La parada seleccionada no existe en el viaje'];
        }

        // Enlazar o reemplazar el enlace apuntando al nodo parada
        $nodo_punto = $nodo_terminal_viaje->adyacente('punto_subida_bajada');
        if ($nodo_punto) {
            $nodo_terminal_viaje->_adyacente_en($nodo_parada_encontrado, 'punto_subida_bajada', true);
        } else {
            $nodo_terminal_viaje->_adyacente_en($nodo_parada_encontrado, 'punto_subida_bajada');
        }
    } else {
        // Cambiar es '0' o el punto está vacío: eliminar enlace si existe
        $nodo_terminal_viaje->eliminar_adyacente('punto_subida_bajada');
    }

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
    $resultado_paradas = _guardar_paradas_intermedias($nodo_viaje, $paradas);
    if (!$resultado_paradas['exito']) {
        return $resultado_paradas;
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