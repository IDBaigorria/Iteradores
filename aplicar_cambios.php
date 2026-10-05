<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda v1.5piloto.74r:
 *   - Fase 2 del plan de optimización del grafo, primer flujo:
 *     eliminar_viaje destruye el subárbol completo del viaje.
 *   - Helpers _destruir_* en Viaje.php.
 *   - Actualización de prompts/prompt_piloto.md.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

    // --------------------------------------------------------
    // Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.74r',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74n',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74r',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reescribir eliminar_viaje para destruir el subárbol',
        'buscar' => [
            '/**',
            ' * Elimina un viaje.',
            ' */',
            'function eliminar_viaje(string $nombre_viaje, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    // Verificar si tiene ventas',
            '    if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {',
            '        return [\'exito\' => false, \'error\' => \'No se pueden eliminar viajes con ventas ya realizadas\'];',
            '    }',
            '',
            '    // TODO: eliminar nodos huérfanos',
            '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Elimina un viaje.',
            ' *',
            ' * A partir de v1.5piloto.74r (Fase 2 del plan de optimización',
            ' * del grafo): destruye el subárbol completo del viaje (micros,',
            ' * copias de vehículo, asientos, TerminalViaje, paradas, DJs,',
            ' * opciones avanzadas) en lugar de dejarlo huérfano. Antes de',
            ' * esta versión solo se desenlazaba el viaje del contenedor',
            ' * del dueño, dejando huérfanos ~250 nodos por micro.',
            ' *',
            ' * No elimina viajes con ventas: eso está chequeado antes.',
            ' *',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' */',
            'function eliminar_viaje(string $nombre_viaje, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    // Verificar si tiene ventas',
            '    if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {',
            '        return [\'exito\' => false, \'error\' => \'No se pueden eliminar viajes con ventas ya realizadas\'];',
            '    }',
            '',
            '    // Fase 2: destruir el subárbol completo del viaje.',
            '    // Ver §8.6 del prompt del piloto para el contexto y las',
            '    // reglas de destrucción.',
            '    _destruir_viaje_completo($nodo_viaje);',
            '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '    Nodo::eliminar($nodo_viaje);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Agregar helpers _destruir_* antes de los require_once',
        'buscar' => [
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
            'require_once __DIR__ . \'/ViajeAsientos.php\';',
            'require_once __DIR__ . \'/ViajeOpciones.php\';',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Destrucción progresiva del subárbol de un viaje',
            '//',
            '// Fase 2 del plan de optimización del grafo (§8.6 del prompt',
            '// del piloto). El framework no recolecta nodos huérfanos: si',
            '// se desenlaza un nodo sin destruirlo, queda ocupando memoria',
            '// y disco. Estos helpers destruyen el subárbol completo de un',
            '// viaje respetando las referencias externas (empresa, terminal).',
            '//',
            '// Reglas:',
            '//  - Todo nodo "campo" (dato string sin adyacentes propios) se',
            '//    destruye al destruir su padre.',
            '//  - Toda referencia a un nodo externo (empresa, terminal) se',
            '//    desenlaza pero NO se destruye.',
            '//  - Toda referencia circular interna se desenlaza; los nodos',
            '//    destino los destruye quien corresponda.',
            '//  - El orden de destrucción va de hojas a raíz, para que',
            '//    Nodo::eliminar no falle por referencias entrantes.',
            '// ============================================================',
            '',
            '/**',
            ' * Desenlaza y destruye los adyacentes de $padre que sean',
            ' * "campos simples": nodos sin adyacentes propios. No toca a',
            ' * los que sí tienen adyacentes (estructuras).',
            ' *',
            ' * Se llama al final de cada _destruir_*, después de procesar',
            ' * los hijos estructurales. Destruye los campos string (nombre,',
            ' * fecha, hora, monto, etc.) para no dejarlos huérfanos.',
            ' *',
            ' * @param Nodo  $padre',
            ' * @param array $excluir_enlaces Enlaces a NO tocar (referencias',
            ' *                               externas o circulares).',
            ' */',
            'function _destruir_campos_simples(Nodo $padre, array $excluir_enlaces = []): void {',
            '    $adyacentes = (array) $padre->adyacentes();',
            '    foreach ($adyacentes as $enlace => $nodo_hijo) {',
            '        $enlace = (string)$enlace;',
            '        if (in_array($enlace, $excluir_enlaces, true)) continue;',
            '        // Solo destruir si el hijo no tiene adyacentes propios.',
            '        $hijos_del_hijo = (array) $nodo_hijo->adyacentes();',
            '        if (!empty($hijos_del_hijo)) continue;',
            '',
            '        $padre->eliminar_adyacente($enlace);',
            '        Nodo::eliminar($nodo_hijo);',
            '    }',
            '}',
            '',
            '/**',
            ' * Destruye la lista circular de asientos de un piso.',
            ' *',
            ' * Recolecta los asientos (todos menos la cabeza), rompe el',
            ' * círculo desenlazando el `siguiente` del último, desenlaza',
            ' * el `primer` de la cabeza, y destruye cada asiento con sus',
            ' * campos. Al final destruye la cabeza.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {',
            '    $cabeza = $nodo_piso->adyacente(\'asientos\');',
            '    if (!$cabeza) return;',
            '',
            '    // Recolectar todos los asientos menos la cabeza.',
            '    $asientos = [];',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {',
            '        $asientos[] = $actual;',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '',
            '    // Romper el círculo: desenlazar el `siguiente` del último.',
            '    if (!empty($asientos)) {',
            '        $ultimo = $asientos[count($asientos) - 1];',
            '        $ultimo->eliminar_adyacente(\'siguiente\');',
            '    }',
            '',
            '    // Desenlazar el `primer` de la cabeza antes de destruir asientos.',
            '    $cabeza->eliminar_adyacente(\'primer\');',
            '',
            '    // Destruir cada asiento con sus campos. Las referencias',
            '    // externas (pasajero, venta) solo se desenlazan, no se',
            '    // destruyen. En un viaje sin ventas no deberían existir,',
            '    // pero se cubre por defensa.',
            '    foreach ($asientos as $asiento) {',
            '        _destruir_campos_simples($asiento, [\'pasajero\', \'venta\']);',
            '        $asiento->eliminar_adyacente(\'pasajero\');',
            '        $asiento->eliminar_adyacente(\'venta\');',
            '        Nodo::eliminar($asiento);',
            '    }',
            '',
            '    // Destruir la cabeza.',
            '    _destruir_campos_simples($cabeza);',
            '    Nodo::eliminar($cabeza);',
            '}',
            '',
            '/**',
            ' * Destruye un piso: la lista circular de asientos, el nodo',
            ' * cabeza, y los campos filas/columnas.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_piso(Nodo $nodo_piso): void {',
            '    _destruir_lista_circular_asientos($nodo_piso);',
            '    _destruir_campos_simples($nodo_piso);',
            '    Nodo::eliminar($nodo_piso);',
            '}',
            '',
            '/**',
            ' * Destruye una copia de vehículo: pisos, contenedor de',
            ' * asientos y campos.',
            ' *',
            ' * @param Nodo $nodo_copia',
            ' */',
            'function _destruir_copia_vehiculo(Nodo $nodo_copia): void {',
            '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso) {',
            '                _destruir_piso($piso);',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '            }',
            '        }',
            '        _destruir_campos_simples($nodo_asientos);',
            '        Nodo::eliminar($nodo_asientos);',
            '    }',
            '    _destruir_campos_simples($nodo_copia);',
            '    Nodo::eliminar($nodo_copia);',
            '}',
            '',
            '/**',
            ' * Destruye un micro: copia de vehículo, campos, y desenlaza',
            ' * las referencias externas (empresa) y circulares (viaje).',
            ' *',
            ' * Debe llamarse después de desenlazar el micro del contenedor',
            ' * `micros` del viaje, para que Nodo::eliminar no falle.',
            ' *',
            ' * @param Nodo $nodo_micro',
            ' */',
            'function _destruir_micro(Nodo $nodo_micro): void {',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '    if ($nodo_copia) {',
            '        _destruir_copia_vehiculo($nodo_copia);',
            '        $nodo_micro->eliminar_adyacente(\'vehiculo_copia\');',
            '    }',
            '    // Desenlazar referencias externas/circulares antes de destruir.',
            '    $nodo_micro->eliminar_adyacente(\'empresa\');',
            '    $nodo_micro->eliminar_adyacente(\'viaje\');',
            '    _destruir_campos_simples($nodo_micro);',
            '    Nodo::eliminar($nodo_micro);',
            '}',
            '',
            '/**',
            ' * Destruye un TerminalViaje: desenlaza las referencias a',
            ' * terminal y a parada, y destruye sus campos override.',
            ' *',
            ' * @param Nodo $nodo_tv',
            ' */',
            'function _destruir_terminal_viaje(Nodo $nodo_tv): void {',
            '    $nodo_tv->eliminar_adyacente(\'terminal\');',
            '    $nodo_tv->eliminar_adyacente(\'punto_subida_bajada\');',
            '    _destruir_campos_simples($nodo_tv);',
            '    Nodo::eliminar($nodo_tv);',
            '}',
            '',
            '/**',
            ' * Destruye una parada intermedia.',
            ' *',
            ' * @param Nodo $nodo_parada',
            ' */',
            'function _destruir_parada(Nodo $nodo_parada): void {',
            '    _destruir_campos_simples($nodo_parada);',
            '    Nodo::eliminar($nodo_parada);',
            '}',
            '',
            '/**',
            ' * Destruye el subárbol completo de un viaje.',
            ' *',
            ' * Orden: micros (uno por uno, después el contenedor),',
            ' * TerminalViaje (uno por uno, después el contenedor),',
            ' * paradas (una por una, después el contenedor), DJs,',
            ' * opciones avanzadas, y al final los campos simples del',
            ' * propio viaje.',
            ' *',
            ' * NO destruye el nodo viaje en sí: de eso se encarga',
            ' * eliminar_viaje, después de desenlazarlo del contenedor',
            ' * del dueño.',
            ' *',
            ' * @param Nodo $nodo_viaje',
            ' */',
            'function _destruir_viaje_completo(Nodo $nodo_viaje): void {',
            '    // 1. Micros. Primero desenlazar del contenedor, después',
            '    //    destruir el micro (que ya no tiene entrantes).',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if ($nodo_micros) {',
            '        $adyacentes_micros = (array) $nodo_micros->adyacentes();',
            '        foreach ($adyacentes_micros as $nombre_micro => $nodo_micro) {',
            '            $nodo_micros->eliminar_adyacente((string)$nombre_micro);',
            '            _destruir_micro($nodo_micro);',
            '        }',
            '        _destruir_campos_simples($nodo_micros);',
            '        $nodo_viaje->eliminar_adyacente(\'micros\');',
            '        Nodo::eliminar($nodo_micros);',
            '    }',
            '',
            '    // 2. Terminales autorizadas (TerminalViaje).',
            '    $nodo_terminales = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '    if ($nodo_terminales) {',
            '        $adyacentes_tv = (array) $nodo_terminales->adyacentes();',
            '        foreach ($adyacentes_tv as $nombre_terminal => $nodo_tv) {',
            '            $nodo_terminales->eliminar_adyacente((string)$nombre_terminal);',
            '            _destruir_terminal_viaje($nodo_tv);',
            '        }',
            '        _destruir_campos_simples($nodo_terminales);',
            '        $nodo_viaje->eliminar_adyacente(\'terminales_autorizadas\');',
            '        Nodo::eliminar($nodo_terminales);',
            '    }',
            '',
            '    // 3. Paradas intermedias (lista árbol hmi/hd/p).',
            '    $nodo_paradas = $nodo_viaje->adyacente(\'paradas_intermedias\');',
            '    if ($nodo_paradas) {',
            '        while ($parada = eliminar_hmi($nodo_paradas)) {',
            '            _destruir_parada($parada);',
            '        }',
            '        _destruir_campos_simples($nodo_paradas);',
            '        $nodo_viaje->eliminar_adyacente(\'paradas_intermedias\');',
            '        Nodo::eliminar($nodo_paradas);',
            '    }',
            '',
            '    // 4. Declaraciones juradas (nodos hoja con dato HTML).',
            '    foreach ([\'declaracion_jurada_mayor\', \'declaracion_jurada_menor\'] as $enlace_dj) {',
            '        $nodo_dj = $nodo_viaje->adyacente($enlace_dj);',
            '        if ($nodo_dj) {',
            '            _destruir_campos_simples($nodo_dj);',
            '            $nodo_viaje->eliminar_adyacente($enlace_dj);',
            '            Nodo::eliminar($nodo_dj);',
            '        }',
            '    }',
            '',
            '    // 5. Opciones avanzadas (contenedor de campos string).',
            '    $nodo_opciones = $nodo_viaje->adyacente(\'opciones_avanzadas\');',
            '    if ($nodo_opciones) {',
            '        _destruir_campos_simples($nodo_opciones);',
            '        $nodo_viaje->eliminar_adyacente(\'opciones_avanzadas\');',
            '        Nodo::eliminar($nodo_opciones);',
            '    }',
            '',
            '    // 6. Campos simples del propio viaje (nombre, fecha, hora,',
            '    //    origen, destino, dueno, contadores).',
            '    _destruir_campos_simples($nodo_viaje);',
            '}',
            '',
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
            'require_once __DIR__ . \'/ViajeAsientos.php\';',
            'require_once __DIR__ . \'/ViajeOpciones.php\';',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74r antes de v74q',
        'buscar' => [
            '- **v74q**: solo documentación. Se agregaron dos pendientes',
            '  al backlog (§8.5): cerrar todos los modales al cerrar',
            '  sesión (prioridad media) y convertir en modal la carga de',
            '  empresas y micros (prioridad media). El prompt del',
        ],
        'reemplazar' => [
            '- **v74r**: Fase 2 del plan de optimización del grafo,',
            '  primer flujo arreglado. `eliminar_viaje` ahora destruye',
            '  el subárbol completo del viaje (micros con copias de',
            '  vehículo y asientos, TerminalViaje, paradas, DJs,',
            '  opciones avanzadas) en lugar de dejarlo huérfano.',
            '  Nuevos helpers `_destruir_*` en `Viaje.php`. Se agrega',
            '  también el pendiente de los formularios embebidos',
            '  muertos a §8.5.',
            '- **v74q**: solo documentación. Se agregaron dos pendientes',
            '  al backlog (§8.5): cerrar todos los modales al cerrar',
            '  sesión (prioridad media) y convertir en modal la carga de',
            '  empresas y micros (prioridad media). El prompt del',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.5: agregar pendiente de formularios embebidos muertos',
        'buscar' => [
            '- **Convertir en modal la carga de nuevas empresas y micros**',
            '  (prioridad media). Hoy son formularios inline',
            '  (`#formulario_nueva_empresa`, `#formulario_nuevo_vehiculo`).',
            '  Migrar al patrón de modal genérico como se hizo con usuarios',
            '  y terminales (v73e-v73g).',
        ],
        'reemplazar' => [
            '- **Convertir en modal la carga de nuevas empresas y micros**',
            '  (prioridad media). Hoy son formularios inline',
            '  (`#formulario_nueva_empresa`, `#formulario_nuevo_vehiculo`).',
            '  Migrar al patrón de modal genérico como se hizo con usuarios',
            '  y terminales (v73e-v73g).',
            '  - **Recordatorio:** al migrar a modal, eliminar los',
            '    formularios embebidos de `aplicacion_GET.html` que',
            '    queden muertos: `#formulario_nueva_empresa`,',
            '    `#formulario_nuevo_vehiculo`, y también los ya muertos',
            '    desde v73g (`#formulario_nuevo_usuario`,',
            '    `#formulario_nueva_terminal`). Es código muerto que',
            '    sigue en el HTML y puede inducir a error al escribir',
            '    pruebas del plugin (ver aprendizaje 30 en el prompt',
            '    del plugin).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: actualizar Fase 2 con el primer flujo arreglado',
        'buscar' => [
            '**Fase 2 — Auditoría de la fuga de nodos (pendiente, prioridad alta).**',
            '',
            'Con la pestaña Grafo como herramienta:',
        ],
        'reemplazar' => [
            '**Fase 2 — Auditoría de la fuga de nodos (en curso, prioridad alta).**',
            '',
            '**Primer flujo arreglado en v74r:** `eliminar_viaje`. Antes',
            'solo desenlazaba el viaje del contenedor del dueño y dejaba',
            'huérfanos el nodo viaje, todos sus micros con copias de',
            'vehículo y asientos, los TerminalViaje, las paradas',
            'intermedias, las DJs y las opciones avanzadas. Ahora llama',
            'a `_destruir_viaje_completo`, que recorre el subárbol en',
            'orden (micros → TerminalViaje → paradas → DJs → opciones',
            '→ campos del viaje) y destruye cada nodo. Los helpers',
            '`_destruir_*` en `Viaje.php` son reutilizables para los',
            'próximos flujos.',
            '',
            '**Hallazgo del detector de `_adyacente_en(..., true)`:** los',
            '3 usos en `Venta.php:1279/1286` (en `cancelar_venta`, sección',
            '7: desenlazar la venta del árbol del dueño, seguido de',
            '`Nodo::eliminar($nodo_venta)`) y `Viaje.php:900` (en',
            '`guardar_opciones_terminal_viaje`: reemplazar el enlace',
            '`punto_subida_bajada` del TerminalViaje, donde la parada',
            'vieja sigue viva en `paradas_intermedias`) son correctos',
            'por diseño. No son fugas.',
            '',
            '**Otros flujos con fuga pendientes de arreglar** (mismo',
            'patrón de "desenlazar sin destruir"):',
            '',
            '- `eliminar_micro_de_viaje` (en `ViajeMicros.php`):',
            '  desenlaza el micro del contenedor pero no destruye',
            '  el micro, su copia de vehículo, ni sus asientos.',
            '- `eliminar_terminal_autorizada` (en `Viaje.php`):',
            '  desenlaza el TerminalViaje pero no lo destruye.',
            '- `_guardar_paradas_intermedias` (en `Viaje.php`): al',
            '  editar las paradas, las que no se reutilizan quedan',
            '  huérfanas.',
            '- `eliminar_pasajero` (en `Pasajero.php`): a auditar.',
            '- `cancelar_venta` (en `Venta.php`): auditar el uso',
            '  de `Nodo::eliminar($nodo_venta)` sin chequear el',
            '  resultado.',
            '',
            'Con la pestaña Grafo como herramienta:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: actualizar la Última actualización',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74q',
            '(solo documentación. Se agregaron dos pendientes al backlog:',
            'cerrar todos los modales al cerrar sesión y convertir en modal',
            'la carga de empresas y micros, ambos de prioridad media).',
            'Antes: v1.5piloto.74p',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74r',
            '(Fase 2 del plan de optimización del grafo: primer flujo',
            'arreglado, `eliminar_viaje`. Ahora destruye el subárbol',
            'completo del viaje en lugar de dejarlo huérfano. Nuevos',
            'helpers `_destruir_*` en `Viaje.php`, reutilizables para',
            'los próximos flujos. Se agrega también el pendiente de',
            'los formularios embebidos muertos a §8.5).',
            'Antes: v1.5piloto.74q',
            '(solo documentación. Se agregaron dos pendientes al backlog:',
            'cerrar todos los modales al cerrar sesión y convertir en modal',
            'la carga de empresas y micros, ambos de prioridad media).',
            'Antes: v1.5piloto.74p',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre de v74r',
        'buscar' => [
            '- Cerramos en v74p la Fase 1 del plan de optimización del',
            '  grafo: pestaña "Grafo" (solo admin y soporte). Vista de',
            '  solo lectura con totales, alcanzables vs huérfanos, top',
            '  de referencias, tabla filtrable y modal de detalle por',
            '  nodo. Backend: 3 comandos en el `Controlador`',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`).',
            '  Frontend: `Aplicacion/grafo.js`.',
        ],
        'reemplazar' => [
            '- Cerramos en v74p la Fase 1 del plan de optimización del',
            '  grafo: pestaña "Grafo" (solo admin y soporte). Vista de',
            '  solo lectura con totales, alcanzables vs huérfanos, top',
            '  de referencias, tabla filtrable y modal de detalle por',
            '  nodo. Backend: 3 comandos en el `Controlador`',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`).',
            '  Frontend: `Aplicacion/grafo.js`.',
            '- Cerramos en v74r el primer flujo de Fase 2: `eliminar_viaje`',
            '  ahora destruye el subárbol completo del viaje, no solo',
            '  desenlaza del contenedor. Nuevos helpers `_destruir_*`',
            '  en `Viaje.php`. Con esto, eliminar un viaje con N micros',
            '  libera ~250 × N nodos. Otros flujos con el mismo patrón',
            '  pendientes: `eliminar_micro_de_viaje` (en `ViajeMicros.php`),',
            '  `eliminar_terminal_autorizada` (en `Viaje.php`),',
            '  `_guardar_paradas_intermedias` (en `Viaje.php`),',
            '  `eliminar_pasajero` (en `Pasajero.php`, a auditar).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: actualizar estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74q (framework 1.5i.7g).',
            'Todo funcional. Fixes de v74k a v74o acumulados. Fix de',
            'v74p: pestaña "Grafo" (Fase 1 del plan de optimización).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5d)',
            'tiene 29 pruebas corriendo.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74r (framework 1.5i.7g).',
            'Todo funcional. Fixes de v74k a v74o acumulados. Fix de',
            'v74p: pestaña "Grafo" (Fase 1 del plan de optimización).',
            'v74r: `eliminar_viaje` destruye el subárbol completo',
            '(Fase 2, primer flujo).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5e)',
            'tiene 29 pruebas corriendo; la prueba espejo de v74r',
            'se agrega en la próxima tanda, cuando se pasen los',
            'archivos del plugin.',
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