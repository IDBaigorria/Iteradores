<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74k: fixes de validación en el alta de micro.
 *
 * Corrección del prompt_piloto.md: la tanda v74j nunca actualizó
 * el prompt, así que faltan v74j y v74k en el historial y en la
 * cabecera de §12. Este script las agrega con las anclas reales.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // ViajeMicros.php — reescribir agregar_micro_a_viaje
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'ViajeMicros: validaciones y nombre con max+1',
        'buscar' => [
            'function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = \'0\'): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios\'];',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '',
            '    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '    if (!$nodo_vehiculo) return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '',
            '    $nodo_copia = clonar_vehiculo($nodo_vehiculo);',
            '',
            '    $monto = (string) $monto;',
            '    if (!is_numeric($monto) || (float)$monto < 0) {',
            '        return [\'exito\' => false, \'error\' => \'Monto inválido\'];',
            '    }',
            '',
            '    $nodo_micro = Nodo::crear_con_dato(\'\');',
            '    $nodo_micro->_adyacente_en($nodo_empresa, \'empresa\');',
            '    $nodo_micro->_adyacente_en($nodo_copia, \'vehiculo_copia\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), \'monto\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'ocupacion\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'seleccionados\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'vendidos\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'reservados\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'disponibles\');',
            '    $nodo_micro->_adyacente_en($nodo_viaje, \'viaje\');',
            '',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if (!$nodo_micros) {',
            '        $nodo_micros = Nodo::crear_con_dato(\'\');',
            '        $nodo_viaje->_adyacente_en($nodo_micros, \'micros\');',
            '    }',
            '',
            '    $adyacentes_micros = (array) $nodo_micros->adyacentes();',
            '    $indice = count($adyacentes_micros) + 1;',
            '    $nombre_micro = \'micro_\' . $indice;',
            '    $nodo_micros->_adyacente_en($nodo_micro, $nombre_micro);',
            '',
            '    actualizar_contadores_micro($nodo_micro);',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
            '}',
        ],
        'reemplazar' => [
            'function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = \'0\'): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios\'];',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '',
            '    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '    if (!$nodo_vehiculo) return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '',
            '    // Validar monto antes de cualquier creación de nodos.',
            '    $monto = (string) $monto;',
            '    if (!is_numeric($monto) || (float)$monto < 0) {',
            '        return [\'exito\' => false, \'error\' => \'Monto inválido\'];',
            '    }',
            '',
            '    // Validar que el vehículo tenga asientos configurados.',
            '    // Un micro sin asientos no se puede usar: no se pueden',
            '    // seleccionar ni vender asientos. El frontend ya filtra',
            '    // los vehículos sin configurar del select, pero se',
            '    // refuerza acá por si algo se saltea.',
            '    $nodo_asientos_orig = $nodo_vehiculo->adyacente(\'asientos\');',
            '    $tiene_asientos = false;',
            '    if ($nodo_asientos_orig) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos_orig->adyacente("piso_$i");',
            '            if (!$piso) continue;',
            '            $cabeza = $piso->adyacente(\'asientos\');',
            '            if ($cabeza && $cabeza->adyacente(\'primer\')) {',
            '                $tiene_asientos = true;',
            '                break;',
            '            }',
            '        }',
            '    }',
            '    if (!$tiene_asientos) {',
            '        return [\'exito\' => false, \'error\' => \'El vehículo "\' . $nombre_vehiculo . \'" no tiene asientos configurados\'];',
            '    }',
            '',
            '    // Validar que el vehículo no esté ya agregado al viaje.',
            '    // Los micros se diferencian por la patente del vehículo',
            '    // original; dos copias del mismo vehículo son duplicados.',
            '    // Comparación case-insensitive, igual que subir_foto_vehiculo.',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if ($nodo_micros) {',
            '        $micros_existentes = (array) $nodo_micros->adyacentes();',
            '        foreach ($micros_existentes as $nodo_micro_existente) {',
            '            $copia = $nodo_micro_existente->adyacente(\'vehiculo_copia\');',
            '            if ($copia && strcasecmp($copia->dato(), $nombre_vehiculo) === 0) {',
            '                return [\'exito\' => false, \'error\' => \'Ese vehículo ya está agregado a este viaje\'];',
            '            }',
            '        }',
            '    }',
            '',
            '    // Clonar el vehículo.',
            '    $nodo_copia = clonar_vehiculo($nodo_vehiculo);',
            '',
            '    // Crear el nodo micro.',
            '    $nodo_micro = Nodo::crear_con_dato(\'\');',
            '    $nodo_micro->_adyacente_en($nodo_empresa, \'empresa\');',
            '    $nodo_micro->_adyacente_en($nodo_copia, \'vehiculo_copia\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato($monto), \'monto\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'ocupacion\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'seleccionados\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'vendidos\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'reservados\');',
            '    $nodo_micro->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'disponibles\');',
            '    $nodo_micro->_adyacente_en($nodo_viaje, \'viaje\');',
            '',
            '    // Nombre del micro: max(existentes) + 1.',
            '    // No se usa count+1 porque si se elimina un micro del medio,',
            '    // el próximo nombre calculado puede colisionar con uno ya',
            '    // existente (ej: micro_1, micro_3 tras borrar micro_2;',
            '    // count+1 daría micro_3, que ya existe).',
            '    if (!$nodo_micros) {',
            '        $nodo_micros = Nodo::crear_con_dato(\'\');',
            '        $nodo_viaje->_adyacente_en($nodo_micros, \'micros\');',
            '    }',
            '    $adyacentes_micros = (array) $nodo_micros->adyacentes();',
            '    $max_indice = 0;',
            '    foreach (array_keys($adyacentes_micros) as $clave) {',
            '        if (preg_match(\'/^micro_(\\d+)$/\', (string)$clave, $m)) {',
            '            $n = (int)$m[1];',
            '            if ($n > $max_indice) $max_indice = $n;',
            '        }',
            '    }',
            '    $nombre_micro = \'micro_\' . ($max_indice + 1);',
            '',
            '    // Chequear el resultado del enlace. Con max+1 no debería',
            '    // colisionar, pero por defensa: si _adyacente_en falla',
            '    // silenciosamente, el nodo del micro queda huérfano y el',
            '    // micro nunca aparece en el viaje.',
            '    $nodo_micros->_adyacente_en($nodo_micro, $nombre_micro);',
            '    if (!$nodo_micros->adyacente($nombre_micro)) {',
            '        return [\'exito\' => false, \'error\' => \'No se pudo asignar un nombre libre al micro (colisión con \' . $nombre_micro . \')\'];',
            '    }',
            '',
            '    actualizar_contadores_micro($nodo_micro);',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
            '}',
        ],
    ],

    // ========================================================
    // ViajeMicros.php — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'ViajeMicros: bump @version a 1.5piloto.74k',
        'buscar' => [
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74k',
        ],
    ],

    // ========================================================
    // viajes-micros.js — filtrar vehículos sin asientos
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros: filtrar vehículos sin asientos',
        'buscar' => [
            '            const datosV = await respV.json();',
            '            if (datosV.exito) {',
            '                datosV.vehiculos.forEach(vehiculo => {',
            '                    const opcion = document.createElement(\'option\');',
            '                    opcion.value = vehiculo.nombre_vehiculo;',
            '                    opcion.textContent = vehiculo.nombre;',
            '                    selectVehiculo.appendChild(opcion);',
            '                });',
            '            }',
        ],
        'reemplazar' => [
            '            const datosV = await respV.json();',
            '            if (datosV.exito) {',
            '                datosV.vehiculos.forEach(vehiculo => {',
            '                    const opcion = document.createElement(\'option\');',
            '                    opcion.value = vehiculo.nombre_vehiculo;',
            '                    // Los vehículos sin asientos configurados se',
            '                    // muestran deshabilitados: si se agregaran al',
            '                    // viaje, el micro quedaría inutilizable. El',
            '                    // backend igual los rechaza (defensa en',
            '                    // profundidad).',
            '                    const asientos = parseInt(vehiculo.asientos, 10) || 0;',
            '                    if (asientos <= 0) {',
            '                        opcion.textContent = vehiculo.nombre + \' — Sin asientos configurados\';',
            '                        opcion.disabled = true;',
            '                    } else {',
            '                        opcion.textContent = vehiculo.nombre;',
            '                    }',
            '                    selectVehiculo.appendChild(opcion);',
            '                });',
            '            }',
        ],
    ],

    // ========================================================
    // viajes-micros.js — validar opción disabled en confirmar
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros: validar que no se elija un disabled',
        'buscar' => [
            '    if (!nombre_empresa || !nombre_vehiculo) {',
            '        mostrar_aviso("Seleccione empresa y vehículo", \'error\');',
            '        return;',
            '    }',
            '    if (monto === \'\' || isNaN(parseFloat(monto)) || parseFloat(monto) < 0) {',
            '        mostrar_aviso("Ingrese un monto válido", \'error\');',
            '        return;',
            '    }',
        ],
        'reemplazar' => [
            '    if (!nombre_empresa || !nombre_vehiculo) {',
            '        mostrar_aviso("Seleccione empresa y vehículo", \'error\');',
            '        return;',
            '    }',
            '    if (monto === \'\' || isNaN(parseFloat(monto)) || parseFloat(monto) < 0) {',
            '        mostrar_aviso("Ingrese un monto válido", \'error\');',
            '        return;',
            '    }',
            '    // Defensa: verificar que la opción elegida no esté disabled',
            '    // (por ejemplo, vehículo sin asientos configurados).',
            '    const sel_veh = document.getElementById(\'selector_vehiculo_micro_viaje\');',
            '    if (sel_veh && sel_veh.selectedOptions.length > 0 && sel_veh.selectedOptions[0].disabled) {',
            '        mostrar_aviso("Ese vehículo no tiene asientos configurados", \'error\');',
            '        return;',
            '    }',
        ],
    ],

    // ========================================================
    // viajes-micros.js — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros: bump @version a 1.5piloto.74k',
        'buscar' => [
            ' * Micros y terminales dentro de viajes.',
            ' * @version 1.5piloto.74',
        ],
        'reemplazar' => [
            ' * Micros y terminales dentro de viajes.',
            ' * @version 1.5piloto.74k',
        ],
    ],

    // ========================================================
    // aplicacion_GET.html — bump ?v= de viajes-micros.js
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de viajes-micros.js a 1.5piloto.74k',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.74"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.74k"></script>',
        ],
    ],

    // ========================================================
    // prompts/prompt_piloto.md — historial: agregar v74k y v74j
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: agregar v74k y v74j al historial',
        'buscar' => [
            '- **v74h**: cierre de auditoría de pendientes. Fix del',
        ],
        'reemplazar' => [
            '- **v74k**: fixes de validación en el alta de micro.',
            '  `agregar_micro_a_viaje` (en `ViajeMicros.php`) rechaza',
            '  vehículos sin asientos configurados y vehículos ya',
            '  agregados al mismo viaje (comparación case-insensitive',
            '  con `strcasecmp`). El nombre del micro se calcula con',
            '  `max(existentes) + 1` en vez de `count + 1`, para evitar',
            '  colisiones cuando se borra un micro del medio; y se',
            '  chequea el resultado de `_adyacente_en`. En el frontend',
            '  (`viajes-micros.js`), los vehículos sin asientos',
            '  aparecen deshabilitados en el select con sufijo',
            '  "— Sin asientos configurados"; el botón Confirmar',
            '  también valida que no se haya elegido uno disabled.',
            '- **v74j**: modo prueba para alertas críticas. Nuevo helper',
            '  `_mostrar_alerta_critica()` en `aplicacion.js`: solo',
            '  dispara `alert()` si `window.__iteradores_modo_prueba`',
            '  NO está en `true`. Los dos `alert("Código de acceso: ...")`',
            '  (alta y edición de usuarios/terminales) usan el helper.',
            '  El plugin `iteradoresJS/` activa el modo prueba antes de',
            '  ejecutar flujos que disparan el alert, para no bloquear',
            '  el page context durante las pruebas.',
            '- **v74h**: cierre de auditoría de pendientes. Fix del',
        ],
    ],

    // ========================================================
    // prompts/prompt_piloto.md — §12 cabecera a v74k
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 cabecera a v74k',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74h (fix del',
            'autocompletado por DNI cuando el usuario es terminal y no hay viaje',
            'seleccionado; el dueño se resuelve desde `usuario_actual.dueno`.',
            'Bump de `?v=` de `ventas.js` en `aplicacion_GET.html`. Corrección',
            'de documentación: rehash automático y limpieza de migraciones',
            'ya están implementados, `boton_reiniciar_numeracion` y',
            '`ver_compra_asiento` también; `migrar_pasajeros.php` ya no existe;',
            '`GuardarAmbos.php` fue eliminado en v73k).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74k (fixes',
            'de validación en el alta de micro. `agregar_micro_a_viaje`',
            'rechaza vehículos sin asientos y vehículos duplicados en el',
            'mismo viaje; nombre del micro con `max+1` para evitar',
            'colisiones. Frontend: vehículos sin asientos aparecen',
            'deshabilitados en el select).',
            'Antes: v1.5piloto.74j (modo',
            'prueba para alertas críticas: nuevo helper',
            '`_mostrar_alerta_critica()` en `aplicacion.js` que solo',
            'dispara `alert()` si `window.__iteradores_modo_prueba` no',
            'está activo. Los dos `alert("Código de acceso: ...")` (alta',
            'y edición de usuarios/terminales) usan el helper. El plugin',
            'de `iteradoresJS/` activa el modo prueba antes de los flujos',
            'que disparan el alert, para no bloquear el page context).',
            'Antes: v1.5piloto.74h (fix del',
            'autocompletado por DNI cuando el usuario es terminal y no hay viaje',
            'seleccionado; el dueño se resuelve desde `usuario_actual.dueno`.',
            'Bump de `?v=` de `ventas.js` en `aplicacion_GET.html`. Corrección',
            'de documentación: rehash automático y limpieza de migraciones',
            'ya están implementados, `boton_reiniciar_numeracion` y',
            '`ver_compra_asiento` también; `migrar_pasajeros.php` ya no existe;',
            '`GuardarAmbos.php` fue eliminado en v73k).',
        ],
    ],

    // ========================================================
    // prompts/prompt_piloto.md — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar v74k y v74j al estado',
        'buscar' => [
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
            '  por DNI para terminal sin viaje seleccionado, bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones',
            '  en este prompt (rehash, migraciones, botones, autocompletado,',
            '  `GuardarAmbos.php`, `migrar_pasajeros.php`).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '- Cerramos en v74k los fixes de validación del alta de micro:',
            '  el backend rechaza vehículos sin asientos y duplicados en el',
            '  mismo viaje, el nombre del micro se calcula con `max+1` para',
            '  evitar colisiones cuando se quita uno del medio, y el',
            '  frontend filtra los vehículos sin asientos del select.',
            '  Estos fixes surgieron de la batería de pruebas del plugin',
            '  (v1.5plugin.4y): `micro_mismo_vehiculo_dos_veces` (verifica',
            '  rechazo de duplicados), `micro_vehiculo_sin_asientos`',
            '  (verifica filtro del select) y `micro_colision_numeracion`',
            '  (reproduce el bug de colisión).',
            '- Cerramos en v74j el modo prueba para alertas críticas: el',
            '  helper `_mostrar_alerta_critica()` respeta la bandera',
            '  `window.__iteradores_modo_prueba` que el plugin setea antes',
            '  de los flujos que disparan `alert()`.',
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
            '  por DNI para terminal sin viaje seleccionado, bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones',
            '  en este prompt (rehash, migraciones, botones, autocompletado,',
            '  `GuardarAmbos.php`, `migrar_pasajeros.php`).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    // ========================================================
    // prompts/prompt_piloto.md — §13 estado del proyecto
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §13 estado del proyecto a v74k',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74h (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Auditoría de pendientes',
            'cerrada: los ítems listados como "abiertos" en prompts anteriores',
            '(rehash automático, limpieza de migraciones,',
            '`boton_reiniciar_numeracion`, `ver_compra_asiento`, autocompletado',
            'por DNI desde Clientes) ya estaban implementados o fueron',
            'corregidos. Fix v74h: el autocompletado por DNI usa',
            '`usuario_actual.dueno` como fallback cuando el usuario es terminal',
            'y no hay viaje seleccionado. El segundo piloto (plugin de Chrome',
            'sobre el framework Iteradores JS) tiene el esqueleto armado y',
            'funcional, con 17 pruebas corriendo.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74k (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Los fixes de v74k',
            'endurecen el alta de micro: rechaza vehículos sin asientos y',
            'duplicados en el mismo viaje, y evita colisiones de numeración',
            'al quitar un micro del medio. El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.4z) tiene 29 pruebas corriendo,',
            'incluidas las tres que verifiquen estos fixes.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";