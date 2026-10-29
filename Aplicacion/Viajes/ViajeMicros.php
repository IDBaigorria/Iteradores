<?php
/**
 * Funciones de gestión de micros dentro de viajes.
 *
 * @package   Iteradores
 * @since     1.5piloto.8
 * @version   1.5piloto.83n
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Configuracion/LogPersistencia.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/Compartidos.php");
// Tanda 1 (v1.5piloto.82e): migración de pasajeros entre
// micros. Venta.php arrastra ViajeAsientos.php (donde vive
// _buscar_asiento_en_venta_persistente) y FuncionesAuxiliares.php.
include_once("./Aplicacion/Ventas/Venta.php");

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
function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = '0', ?Nodo $nodo_contexto = null): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    // Fase 1c.2: si viene contexto (terminal → compartido),
    // navegar desde ahí. El compartido tiene un contenedor
    // `empresas` con las empresas referenciadas por los viajes
    // autorizados. Sin contexto (dueño, admin), navegar por la
    // raíz `us_<dueno>` como antes.
    if ($nodo_contexto) {
        $nodo_empresas = $nodo_contexto->adyacente('empresas');
    } else {
        $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
    }
    if (!$nodo_empresas) return ['exito' => false, 'error' => 'El dueño no tiene empresas'];

    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
    if (!$nodo_empresa) return ['exito' => false, 'error' => 'Empresa no encontrada'];

    $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
    if (!$nodo_vehiculos) return ['exito' => false, 'error' => 'La empresa no tiene vehículos'];

    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);
    if (!$nodo_vehiculo) return ['exito' => false, 'error' => 'Vehículo no encontrado'];

    // Validar monto antes de cualquier creación de nodos.
    $monto = (string) $monto;
    if (!is_numeric($monto) || (float)$monto < 0) {
        return ['exito' => false, 'error' => 'Monto inválido'];
    }

    // Validar que el vehículo tenga asientos configurados.
    // Un micro sin asientos no se puede usar: no se pueden
    // seleccionar ni vender asientos. El frontend ya filtra
    // los vehículos sin configurar del select, pero se
    // refuerza acá por si algo se saltea.
    $nodo_asientos_orig = $nodo_vehiculo->adyacente('asientos');
    $tiene_asientos = false;
    if ($nodo_asientos_orig) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos_orig->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if ($cabeza && $cabeza->adyacente('primer')) {
                $tiene_asientos = true;
                break;
            }
        }
    }
    if (!$tiene_asientos) {
        return ['exito' => false, 'error' => 'El vehículo "' . $nombre_vehiculo . '" no tiene asientos configurados'];
    }

    // Validar que el vehículo no esté ya agregado al viaje.
    // Los micros se diferencian por la patente del vehículo
    // original; dos copias del mismo vehículo son duplicados.
    // Comparación case-insensitive, igual que subir_foto_vehiculo.
    $nodo_micros = $nodo_viaje->adyacente('micros');
    if ($nodo_micros) {
        $micros_existentes = (array) $nodo_micros->adyacentes();
        foreach ($micros_existentes as $nodo_micro_existente) {
            $copia = $nodo_micro_existente->adyacente('vehiculo_copia');
            if ($copia && strcasecmp($copia->dato(), $nombre_vehiculo) === 0) {
                return ['exito' => false, 'error' => 'Ese vehículo ya está agregado a este viaje'];
            }
        }
    }

    // Clonar el vehículo.
    $nodo_copia = clonar_vehiculo($nodo_vehiculo);

    // Crear el nodo micro.
    $nodo_micro = Nodo::crear_con_dato('');
    $nodo_micro->_adyacente_en($nodo_empresa, 'empresa');
    $nodo_micro->_adyacente_en($nodo_copia, 'vehiculo_copia');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), 'monto');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'ocupacion');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'seleccionados');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'vendidos');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'reservados');
    $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), 'disponibles');
    $nodo_micro->_adyacente_en($nodo_viaje, 'viaje');

    // Nombre del micro: max(existentes) + 1.
    // No se usa count+1 porque si se elimina un micro del medio,
    // el próximo nombre calculado puede colisionar con uno ya
    // existente (ej: micro_1, micro_3 tras borrar micro_2;
    // count+1 daría micro_3, que ya existe).
    if (!$nodo_micros) {
        $nodo_micros = Nodo::crear_con_dato('');
        $nodo_viaje->_adyacente_en($nodo_micros, 'micros');
    }
    $adyacentes_micros = (array) $nodo_micros->adyacentes();
    $max_indice = 0;
    foreach (array_keys($adyacentes_micros) as $clave) {
        if (preg_match('/^micro_(\d+)$/', (string)$clave, $m)) {
            $n = (int)$m[1];
            if ($n > $max_indice) $max_indice = $n;
        }
    }
    $nombre_micro = 'micro_' . ($max_indice + 1);

    // Chequear el resultado del enlace. Con max+1 no debería
    // colisionar, pero por defensa: si _adyacente_en falla
    // silenciosamente, el nodo del micro queda huérfano y el
    // micro nunca aparece en el viaje.
    $nodo_micros->_adyacente_en($nodo_micro, $nombre_micro);
    if (!$nodo_micros->adyacente($nombre_micro)) {
        return ['exito' => false, 'error' => 'No se pudo asignar un nombre libre al micro (colisión con ' . $nombre_micro . ')'];
    }

    actualizar_contadores_micro($nodo_micro);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);

    // Fase B2.4.3 (v77q): si el viaje ya estaba autorizado
    // a alguna terminal, la empresa del micro nuevo tiene
    // que aparecer en el `empresas` del compartido. Se
    // sincroniza cada terminal autorizada del viaje.
    // Idempotente: si el viaje y la empresa ya estaban, no
    // hace nada.
    $nodo_tas = $nodo_viaje->adyacente('terminales_autorizadas');
    if ($nodo_tas) {
        foreach ((array)$nodo_tas->adyacentes() as $nombre_terminal => $nodo_tv) {
            $nombre_terminal = (string)$nombre_terminal;
            if ($nombre_terminal === '') continue;
            _sincronizar_terminal_en_compartido_del_viaje($nodo_viaje, $nombre_terminal, $nombre_dueno);
        }
    }

    // Fix 1.5piloto.83g: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro", 'error');
        Controlador::_error("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");
        return [
            'exito' => false,
            'error' => 'El alta del micro se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }
    return ['exito' => true, 'nombre_micro' => $nombre_micro];
}

/**
 * Elimina un micro de un viaje.
 *
 * A partir de v1.5piloto.74s (Fase 2 del plan de optimización
 * del grafo): destruye el micro completo (copia de vehículo,
 * pisos, asientos, campos) en lugar de solo desenlazarlo del
 * contenedor. Reutiliza `_destruir_micro` de `Viaje.php`
 * (agregada en v74r). Antes de esta versión, cada eliminación
 * de un micro dejaba ~100 nodos huérfanos.
 *
 * @param string $nombre_viaje
 * @param string $nombre_micro
 * @param string $nombre_dueno
 * @return array
 */
function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    // Fase 2: destruir el micro completo. Primero desenlazar
    // del contenedor del viaje, después destruir el subárbol
    // (copia de vehículo, pisos, asientos, campos).
    $nodo_micros->eliminar_adyacente($nombre_micro);
    _destruir_micro($nodo_micro);

    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);

    // Fix 1.5piloto.83g: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("eliminar_micro_de_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro", 'error');
        Controlador::_error("eliminar_micro_de_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");
        return [
            'exito' => false,
            'error' => 'La baja del micro se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }
    return ['exito' => true];
}

/**
 * Migra los pasajeros (asientos vendidos y reservados) del
 * micro origen al micro destino, dentro del mismo viaje.
 *
 * Reglas (cerradas en plan_migrar_micro.md §2):
 *  - Mapeo por número de asiento. Si un asiento ocupado del
 *    origen no existe en el destino (por número), falla.
 *  - Todo o nada: si un chequeo previo falla, no se toca el grafo.
 *  - Destino debe estar vacío: todos sus asientos en "libre" o
 *    "no disponible".
 *  - No venta en curso: si hay asientos "seleccionado" en algún
 *    micro del viaje, falla.
 *  - Estados migrados: "vendido" (conserva pasajero y venta) y
 *    "reservado" (conserva reservado_por y, si tiene, pasajero).
 *  - "no disponible" y "libre" del origen no se migran.
 *  - Si el asiento destino ya viene "no disponible" y el origen
 *    está ocupado en ese número, falla (conflicto real).
 *  - Actualiza venta->micro y asiento-en-venta->asiento.
 *  - Después de la migración, el origen queda sin asientos
 *    ocupados (solo puede conservar "no disponible" y "libre").
 *
 * @param string    $nombre_dueno
 * @param string    $nombre_viaje
 * @param string    $nombre_micro_origen
 * @param string    $nombre_micro_destino
 * @param Nodo|null $nodo_contexto
 * @return array
 * @since 1.5piloto.82e
 */
function migrar_pasajeros_de_micro(
    string $nombre_dueno,
    string $nombre_viaje,
    string $nombre_micro_origen,
    string $nombre_micro_destino,
    ?Nodo $nodo_contexto = null
): array {
    // 1. Resolver viaje.
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros en el viaje'];

    // 2. Micro origen y destino.
    if ($nombre_micro_origen === $nombre_micro_destino) {
        return ['exito' => false, 'error' => 'El micro origen y destino son el mismo'];
    }
    $nodo_micro_origen = $nodo_micros->adyacente($nombre_micro_origen);
    if (!$nodo_micro_origen) return ['exito' => false, 'error' => 'Micro origen no encontrado'];
    $nodo_micro_destino = $nodo_micros->adyacente($nombre_micro_destino);
    if (!$nodo_micro_destino) return ['exito' => false, 'error' => 'Micro destino no encontrado'];

    // 3. Chequeos previos (todos antes de tocar el grafo).
    // 3.1. No venta en curso.
    if (_hay_asientos_seleccionados_en_viaje($nodo_micros)) {
        return [
            'exito' => false,
            'error' => 'Hay una venta en curso (asientos seleccionados). Reiniciá la selección antes de migrar.',
        ];
    }

    // 3.2. Recolectar asientos por número.
    $asientos_origen = _recolectar_asientos_por_numero($nodo_micro_origen);
    $asientos_destino = _recolectar_asientos_por_numero($nodo_micro_destino);

    // 3.3. Destino vacío.
    foreach ($asientos_destino as $num => $asiento) {
        $estado = $asiento->adyacente('estado');
        $estado_str = $estado ? $estado->dato() : 'libre';
        if ($estado_str !== 'libre' && $estado_str !== 'no disponible') {
            return [
                'exito' => false,
                'error' => 'El micro destino ya tiene asientos ocupados. Migrá a un micro vacío.',
            ];
        }
    }

    // 3.4. Recolectar asientos ocupados del origen.
    $migraciones = [];
    foreach ($asientos_origen as $num => $asiento) {
        $estado = $asiento->adyacente('estado');
        $estado_str = $estado ? $estado->dato() : 'libre';
        if ($estado_str !== 'vendido' && $estado_str !== 'reservado') continue;
        $migraciones[] = [
            'numero'          => (string)$num,
            'asiento_origen'  => $asiento,
            'estado'          => $estado_str,
            'pasajero'        => $asiento->adyacente('pasajero'),
            'venta'           => $asiento->adyacente('venta'),
            'reservado_por'   => $asiento->adyacente('reservado_por'),
        ];
    }

    // 3.5. Capacidad suficiente.
    $cant_ocupados_origen = count($migraciones);
    $cant_destino = count($asientos_destino);
    if ($cant_ocupados_origen > $cant_destino) {
        return [
            'exito' => false,
            'error' => "El micro destino no tiene capacidad suficiente ($cant_destino asientos vs $cant_ocupados_origen ocupados)",
        ];
    }

    // 3.6. Todos los números existen en destino Y no están "no disponible".
    foreach ($migraciones as $m) {
        if (!isset($asientos_destino[$m['numero']])) {
            return [
                'exito' => false,
                'error' => "El micro destino no tiene el asiento {$m['numero']}. No se puede migrar.",
            ];
        }
        $estado_destino = $asientos_destino[$m['numero']]->adyacente('estado');
        $estado_destino_str = $estado_destino ? $estado_destino->dato() : 'libre';
        if ($estado_destino_str === 'no disponible') {
            return [
                'exito' => false,
                'error' => "El asiento {$m['numero']} del micro destino está marcado como no disponible. No se puede migrar.",
            ];
        }
    }

    // 4. Aplicación. try/catch por defensa: si algo falla acá,
    //    el grafo en memoria queda modificado pero NO se persiste.
    $vendidos = 0;
    $reservados = 0;
    $detalles = [];
    $ventas_migradas = [];

    try {
        foreach ($migraciones as $m) {
            $nodo_asiento_destino = $asientos_destino[$m['numero']];

            // 4.1. Cambiar estado del destino.
            $estado_destino = $nodo_asiento_destino->adyacente('estado');
            if ($estado_destino) {
                $estado_destino->_dato($m['estado']);
            } else {
                $nodo_asiento_destino->_adyacente_en(Nodo::crear_con_dato($m['estado']), 'estado');
            }

            // 4.2. Asignar pasajero si lo hay.
            if ($m['pasajero']) {
                $nodo_asiento_destino->_adyacente_en($m['pasajero'], 'pasajero', true);
            }

            // 4.3. Asignar venta o reservado_por según estado.
            if ($m['estado'] === 'vendido') {
                if ($m['venta']) {
                    $nodo_asiento_destino->_adyacente_en($m['venta'], 'venta', true);

                    // Actualizar venta->micro una sola vez por venta.
                    $id_venta = (string)$m['venta']->dato();
                    if ($id_venta !== '' && !isset($ventas_migradas[$id_venta])) {
                        $m['venta']->_adyacente_en($nodo_micro_destino, 'micro', true);
                        $ventas_migradas[$id_venta] = true;
                    }

                    // Actualizar asiento-en-venta persistente.
                    $av_persistente = _buscar_asiento_en_venta_persistente($m['venta'], $m['asiento_origen']);
                    if ($av_persistente) {
                        $av_persistente->_adyacente_en($nodo_asiento_destino, 'asiento', true);
                    }
                }
                $vendidos++;
                $detalles[] = "Asiento {$m['numero']}: vendido -> vendido";
            } else {
                // Reservado.
                if ($m['reservado_por']) {
                    $nodo_asiento_destino->_adyacente_en($m['reservado_por'], 'reservado_por', true);
                }
                $reservados++;
                $con_pasajero = $m['pasajero'] ? ' (con pasajero)' : ' (sin pasajero)';
                $detalles[] = "Asiento {$m['numero']}: reservado{$con_pasajero} -> reservado";
            }

            // 4.4. Limpiar asiento origen.
            $estado_origen = $m['asiento_origen']->adyacente('estado');
            if ($estado_origen) $estado_origen->_dato('libre');
            $m['asiento_origen']->eliminar_adyacente('pasajero');
            $m['asiento_origen']->eliminar_adyacente('venta');
            $m['asiento_origen']->eliminar_adyacente('reservado_por');
            $m['asiento_origen']->eliminar_adyacente('seleccionado_por');
        }
    } catch (\Throwable $e) {
        return [
            'exito' => false,
            'error' => 'Error durante la migración: ' . $e->getMessage() . '. No se guardó nada.',
        ];
    }

    // 5. Post-migración: contadores.
    actualizar_contadores_micro($nodo_micro_origen);
    actualizar_contadores_micro($nodo_micro_destino);
    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);

    // 6. ¿Origen vacío? (sin vendidos ni reservados).
    $origen_vacio = true;
    foreach ($asientos_origen as $asiento) {
        $estado = $asiento->adyacente('estado');
        $estado_str = $estado ? $estado->dato() : 'libre';
        if ($estado_str === 'vendido' || $estado_str === 'reservado') {
            $origen_vacio = false;
            break;
        }
    }

    // 7. Guardar.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino", 'error');
        Controlador::_error("migrar_pasajeros_de_micro: guardar_ambos falló para viaje=$nombre_viaje origen=$nombre_micro_origen destino=$nombre_micro_destino");
        return [
            'exito' => false,
            'error' => 'La migración se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }

    return [
        'exito' => true,
        'migrados' => $vendidos + $reservados,
        'vendidos_migrados' => $vendidos,
        'reservados_migrados' => $reservados,
        'detalles' => $detalles,
        'origen_vacio' => $origen_vacio,
    ];
}

/**
 * Devuelve true si algún micro del viaje tiene asientos en estado
 * "seleccionado". Sirve para bloquear la migración cuando hay
 * una venta en curso.
 *
 * @param Nodo $nodo_micros Contenedor `micros` del viaje.
 * @return bool
 * @since 1.5piloto.82e
 */
function _hay_asientos_seleccionados_en_viaje(Nodo $nodo_micros): bool {
    foreach ((array)$nodo_micros->adyacentes() as $nodo_micro) {
        $copia = $nodo_micro->adyacente('vehiculo_copia');
        if (!$copia) continue;
        $asientos = $copia->adyacente('asientos');
        if (!$asientos) continue;
        for ($i = 1; $i <= 2; $i++) {
            $piso = $asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            $seg = 0;
            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {
                $estado = $actual->adyacente('estado');
                if ($estado && $estado->dato() === 'seleccionado') return true;
                $actual = $actual->adyacente('siguiente');
                $seg++;
            }
        }
    }
    return false;
}

/**
 * Recolecta los asientos de un micro indexados por su número.
 * Recorre los pisos 1 y 2 del vehiculo_copia. Si un número se
 * repite entre pisos (no debería pasar por el chequeo de
 * duplicados del frontend), el último gana.
 *
 * @param Nodo $nodo_micro
 * @return array<string, Nodo>
 * @since 1.5piloto.82e
 */
function _recolectar_asientos_por_numero(Nodo $nodo_micro): array {
    $asientos = [];
    $copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$copia) return $asientos;
    $nodo_asientos = $copia->adyacente('asientos');
    if (!$nodo_asientos) return $asientos;
    for ($i = 1; $i <= 2; $i++) {
        $piso = $nodo_asientos->adyacente("piso_$i");
        if (!$piso) continue;
        $cabeza = $piso->adyacente('asientos');
        if (!$cabeza) continue;
        $actual = $cabeza->adyacente('primer');
        $seg = 0;
        while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {
            $num = (string)$actual->dato();
            if ($num !== '') {
                $asientos[$num] = $actual;
            }
            $actual = $actual->adyacente('siguiente');
            $seg++;
        }
    }
    return $asientos;
}

/**
 * Actualiza el monto de un micro.
 */
function actualizar_monto_micro(string $nombre_viaje, string $nombre_micro, string $monto, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
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

    // Fix 1.5piloto.83g: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("actualizar_monto_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro", 'error');
        Controlador::_error("actualizar_monto_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");
        return [
            'exito' => false,
            'error' => 'La actualización del monto se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }
    return ['exito' => true];
}

/**
 * Obtiene la información completa de un micro para mostrarla en el pasaje.
 */
function obtener_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'No hay micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado'];

    $empresa = $nodo_micro->adyacente('empresa') ? $nodo_micro->adyacente('empresa')->dato() : '';
    $nodo_copia_tmp = $nodo_micro->adyacente('vehiculo_copia');
    $patente = $nodo_copia_tmp ? $nodo_copia_tmp->dato() : ($nodo_micro->adyacente('patente') ? $nodo_micro->adyacente('patente')->dato() : '');    
    $monto = $nodo_micro->adyacente('monto') ? $nodo_micro->adyacente('monto')->dato() : '0';

    // Contadores calculados al vuelo, para no depender de que los nodos del
    // micro estén al día. Misma fuente que usa el listado de viajes.
    $contadores_micro = contar_contadores_micro($nodo_micro);
    $ocupacion = (string)$contadores_micro['ocupacion'];
    $seleccionados = (string)$contadores_micro['seleccionados'];
    $vendidos = (string)$contadores_micro['vendidos'];
    $reservados = (string)$contadores_micro['reservados'];

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
            'reservados' => $reservados,
            'nombre' => $nombre,
            'foto' => $foto,
            'configuracion' => $configuracion
        ]
    ];
}

/**
 * Actualiza los contadores totales del viaje (ocupación, disponibles, seleccionados, vendidos, reservados).
 */
function actualizar_contadores_viaje(string $nombre_viaje, string $nombre_dueno, ?Nodo $nodo_contexto = null): void {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);
    if (!$nodo_viajes) return;

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return;

    $total_ocupacion = 0;
    $total_seleccionados = 0;
    $total_vendidos = 0;
    $total_reservados = 0;
    $total_disponibles = 0;

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if ($nodo_micros) {
        $adyacentes_micros = (array) $nodo_micros->adyacentes();
        foreach ($adyacentes_micros as $nodo_micro) {
            $total_ocupacion += (int)($nodo_micro->adyacente('ocupacion') ? $nodo_micro->adyacente('ocupacion')->dato() : 0);
            $total_seleccionados += (int)($nodo_micro->adyacente('seleccionados') ? $nodo_micro->adyacente('seleccionados')->dato() : 0);
            $total_vendidos += (int)($nodo_micro->adyacente('vendidos') ? $nodo_micro->adyacente('vendidos')->dato() : 0);
            $total_reservados += (int)($nodo_micro->adyacente('reservados') ? $nodo_micro->adyacente('reservados')->dato() : 0);
        }
    }

    $total_disponibles = $total_ocupacion - $total_vendidos - $total_seleccionados - $total_reservados;

    $nodo_viaje->adyacente('ocupacion')?->_dato((string)$total_ocupacion);
    $nodo_viaje->adyacente('seleccionados')?->_dato((string)$total_seleccionados);
    $nodo_viaje->adyacente('vendidos')?->_dato((string)$total_vendidos);
    $nodo_viaje->adyacente('reservados')?->_dato((string)$total_reservados);
    $nodo_viaje->adyacente('disponibles')?->_dato((string)$total_disponibles);
}


/**
 * Cuenta los contadores de un micro recorriendo los asientos del vehículo copia.
 *
 * Devuelve un array con los 5 contadores:
 *   ocupacion, seleccionados, vendidos, reservados, disponibles.
 *
 * No escribe nada en los nodos. Se usa desde:
 *  - actualizar_contadores_micro (para persistir los valores)
 *  - obtener_micro_de_viaje (para devolver los valores al frontend sin depender
 *    de que los nodos del micro estén al día)
 *
 * @param Nodo $nodo_micro
 * @return array
 */
function contar_contadores_micro(Nodo $nodo_micro): array {
    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) {
        return [
            'ocupacion' => 0,
            'seleccionados' => 0,
            'vendidos' => 0,
            'reservados' => 0,
            'disponibles' => 0,
        ];
    }

    $total = 0;
    $seleccionados = 0;
    $vendidos = 0;
    $reservados = 0;

    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            $contador_seguridad = 0;
            while ($actual && $actual->id() !== $cabeza->id() && $contador_seguridad < 1000) {
                $estado = $actual->adyacente('estado');
                if ($estado) {
                    $estado_str = $estado->dato();
                    if ($estado_str === 'seleccionado') {
                        $seleccionados++;
                    } elseif ($estado_str === 'vendido' || $estado_str === 'no disponible') {
                        $vendidos++;
                    } elseif ($estado_str === 'reservado') {
                        $reservados++;
                    }
                    $total++;
                }
                $actual = $actual->adyacente('siguiente');
                $contador_seguridad++;
            }
        }
    }

    $disponibles = max(0, $total - $vendidos - $seleccionados - $reservados);

    return [
        'ocupacion' => $total,
        'seleccionados' => $seleccionados,
        'vendidos' => $vendidos,
        'reservados' => $reservados,
        'disponibles' => $disponibles,
    ];
}

/**
 * Actualiza los contadores de un micro (ocupación, seleccionados, vendidos, reservados).
 */
function actualizar_contadores_micro(Nodo $nodo_micro): void {
    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return;

    // Asegurar que existan los nodos de contadores
    foreach (['ocupacion', 'seleccionados', 'vendidos', 'reservados', 'disponibles'] as $campo) {
        if (!$nodo_micro->adyacente($campo)) {
            $nodo_micro->_adyacente_en(Nodo::crear_con_dato('0'), $campo);
        }
    }

    $c = contar_contadores_micro($nodo_micro);

    $nodo_micro->adyacente('ocupacion')?->_dato((string)$c['ocupacion']);
    $nodo_micro->adyacente('seleccionados')?->_dato((string)$c['seleccionados']);
    $nodo_micro->adyacente('vendidos')?->_dato((string)$c['vendidos']);
    $nodo_micro->adyacente('reservados')?->_dato((string)$c['reservados']);
    $nodo_micro->adyacente('disponibles')?->_dato((string)$c['disponibles']);
}