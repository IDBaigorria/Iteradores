<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74n: limpieza de viajes de prueba.
 *
 * - Viaje.php: nueva función limpiar_viajes_de_prueba($nombre_dueno).
 * - Enrutador.php: nueva subacción viajes/limpiar_prueba (solo admin).
 * - aplicacion_GET.html: nuevo botón "Limpiar viajes de prueba".
 * - viajes-nucleo.js: listener + lógica de visibilidad del botón.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // Viaje.php — nueva función limpiar_viajes_de_prueba
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: agregar limpiar_viajes_de_prueba',
        'buscar' => [
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
            'require_once __DIR__ . \'/ViajeAsientos.php\';',
            'require_once __DIR__ . \'/ViajeOpciones.php\';',
        ],
        'reemplazar' => [
            '/**',
            ' * Elimina los viajes "de prueba" de un dueño.',
            ' *',
            ' * Conserva el viaje principal (por nombre visible:',
            ' * "Peregrinación a la Visita del Papa León XIV a Luján")',
            ' * y cualquier viaje que no tenga un prefijo conocido de',
            ' * prueba en su identificador (viajeprueba, viajemicro,',
            ' * viajeval, viajedup, viajecol, viajesin).',
            ' *',
            ' * No elimina viajes con ventas registradas.',
            ' *',
            ' * Pensado para el botón de limpieza del admin. Se usa',
            ' * cuando las pruebas automáticas acumulan viajes que',
            ' * ralentizan los listados.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' */',
            'function limpiar_viajes_de_prueba(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado o sin viajes\'];',
            '    }',
            '',
            '    $prefijos = [\'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\'];',
            '    $nombre_principal = \'Peregrinación a la Visita del Papa León XIV a Luján\';',
            '',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    $borrados = [];',
            '    $conservados = [];',
            '    $con_ventas = [];',
            '',
            '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
            '        $nombre_viaje = (string)$nombre_viaje;',
            '',
            '        // Conservar el viaje principal (por nombre visible).',
            '        $nombre_visible = $nodo_viaje->adyacente(\'nombre\')',
            '            ? $nodo_viaje->adyacente(\'nombre\')->dato()',
            '            : \'\';',
            '        if ($nombre_visible === $nombre_principal) {',
            '            $conservados[] = $nombre_viaje;',
            '            continue;',
            '        }',
            '',
            '        // Conservar cualquier viaje que no tenga prefijo de prueba.',
            '        $es_de_prueba = false;',
            '        foreach ($prefijos as $p) {',
            '            if (strpos($nombre_viaje, $p) === 0) {',
            '                $es_de_prueba = true;',
            '                break;',
            '            }',
            '        }',
            '        if (!$es_de_prueba) {',
            '            $conservados[] = $nombre_viaje;',
            '            continue;',
            '        }',
            '',
            '        // No eliminar si tiene ventas registradas.',
            '        if (viaje_tiene_ventas($nombre_dueno, $nombre_viaje)) {',
            '            $con_ventas[] = $nombre_viaje;',
            '            continue;',
            '        }',
            '',
            '        $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '        $borrados[] = $nombre_viaje;',
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'conservados\' => $conservados,',
            '        \'con_ventas\' => $con_ventas,',
            '    ];',
            '}',
            '',
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
            'require_once __DIR__ . \'/ViajeAsientos.php\';',
            'require_once __DIR__ . \'/ViajeOpciones.php\';',
        ],
    ],

    // ========================================================
    // Viaje.php — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: bump @version a 1.5piloto.74n',
        'buscar' => [
            ' * @version   1.5piloto.74',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74n',
        ],
    ],

    // ========================================================
    // Enrutador.php — nueva subacción viajes/limpiar_prueba
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar viajes/limpiar_prueba',
        'buscar' => [
            '                case \'listar_por_terminal\':',
            '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
            '                    if (empty($nombre_terminal)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Terminal no especificada\']);',
            '                    }',
            '                    $viajes = listar_viajes_de_terminal($nombre_terminal);',
            '                    responder_json([\'exito\' => true, \'viajes\' => $viajes]);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'listar_por_terminal\':',
            '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
            '                    if (empty($nombre_terminal)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Terminal no especificada\']);',
            '                    }',
            '                    $viajes = listar_viajes_de_terminal($nombre_terminal);',
            '                    responder_json([\'exito\' => true, \'viajes\' => $viajes]);',
            '                    break;',
            '',
            '                case \'limpiar_prueba\':',
            '                    // Solo admin.',
            '                    $nombre_sol_lp = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $raiz_sol_lp = Nodo::nodo_por_id(\'usuarios\');',
            '                    $nodo_sol_lp = ($raiz_sol_lp && $nombre_sol_lp !== \'\') ? $raiz_sol_lp->adyacente($nombre_sol_lp) : null;',
            '                    $nodo_nivel_lp = $nodo_sol_lp ? $nodo_sol_lp->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_lp = $nodo_nivel_lp ? $nodo_nivel_lp->dato() : \'\';',
            '                    if ($nivel_sol_lp !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_lp = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_lp)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_lp = limpiar_viajes_de_prueba($nombre_dueno_lp);',
            '                    responder_json($resultado_lp);',
            '                    break;',
        ],
    ],

    // ========================================================
    // Enrutador.php — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: bump @version a 1.5piloto.74n',
        'buscar' => [
            ' * @version   1.5piloto.74',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74n',
        ],
    ],

    // ========================================================
    // aplicacion_GET.html — nuevo botón
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: agregar boton limpiar viajes de prueba',
        'buscar' => [
            '      <div class="panel" id="panel_viajes">',
            '        <div class="row" style="justify-content:space-between;">',
            '          <h2>Viajes</h2>',
            '          <button class="btn primary" id="boton_agregar_viaje" style="display:none;">Agregar viaje</button>',
            '        </div>',
            '        <div id="lista_viajes"></div>',
            '      </div>',
        ],
        'reemplazar' => [
            '      <div class="panel" id="panel_viajes">',
            '        <div class="row" style="justify-content:space-between;">',
            '          <h2>Viajes</h2>',
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_viaje" style="display:none;">Agregar viaje</button>',
            '            <button class="btn danger" id="boton_limpiar_viajes_prueba" style="display:none;">Limpiar viajes de prueba</button>',
            '          </div>',
            '        </div>',
            '        <div id="lista_viajes"></div>',
            '      </div>',
        ],
    ],

    // ========================================================
    // aplicacion_GET.html — bump ?v= de viajes-nucleo.js
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de viajes-nucleo.js a 1.5piloto.74n',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.73d"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.74n"></script>',
        ],
    ],

    // ========================================================
    // viajes-nucleo.js — visibilidad del botón + función + listener
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: mostrar boton limpiar si hay dueno seleccionado',
        'buscar' => [
            '        select.onchange = async () => {',
            '            ocultar_detalle_viaje();',
            '            if (select.value) {',
            '                await listar_viajes(select.value, \'dueno\');',
            '                $("#boton_agregar_viaje").style.display = \'inline-block\';',
            '            } else {',
            '                $("#lista_viajes").innerHTML = \'\';',
            '                $("#boton_agregar_viaje").style.display = \'none\';',
            '            }',
            '        };',
        ],
        'reemplazar' => [
            '        select.onchange = async () => {',
            '            ocultar_detalle_viaje();',
            '            if (select.value) {',
            '                await listar_viajes(select.value, \'dueno\');',
            '                $("#boton_agregar_viaje").style.display = \'inline-block\';',
            '                _actualizar_visibilidad_boton_limpiar_viajes(true);',
            '            } else {',
            '                $("#lista_viajes").innerHTML = \'\';',
            '                $("#boton_agregar_viaje").style.display = \'none\';',
            '                _actualizar_visibilidad_boton_limpiar_viajes(false);',
            '            }',
            '        };',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: agregar funciones de limpieza',
        'buscar' => [
            'function obtener_nombre_dueno_actual() {',
            '    return es_admin_o_soporte() ? $("#selector_dueno_viajes").value : usuario_actual.nombre_usuario;',
            '}',
        ],
        'reemplazar' => [
            'function obtener_nombre_dueno_actual() {',
            '    return es_admin_o_soporte() ? $("#selector_dueno_viajes").value : usuario_actual.nombre_usuario;',
            '}',
            '',
            '/**',
            ' * Muestra u oculta el botón "Limpiar viajes de prueba".',
            ' * Solo visible para admin, y solo cuando hay un dueño',
            ' * seleccionado en el selector.',
            ' */',
            'function _actualizar_visibilidad_boton_limpiar_viajes(visible) {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    const btn = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (!btn) return;',
            '    btn.style.display = visible ? \'inline-block\' : \'none\';',
            '}',
            '',
            '/**',
            ' * Ejecuta la limpieza de viajes de prueba del dueño',
            ' * seleccionado. El backend se encarga de preservar el viaje',
            ' * principal y los que no tengan prefijo de prueba.',
            ' */',
            'async function limpiar_viajes_de_prueba_ui() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') {',
            '        mostrar_aviso("Solo el admin puede ejecutar esta acción", \'error\');',
            '        return;',
            '    }',
            '    const select = document.getElementById("selector_dueno_viajes");',
            '    const nombre_dueno = select ? select.value : \'\';',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso("Seleccione un dueño primero", \'error\');',
            '        return;',
            '    }',
            '    const ok = confirm(',
            '        "¿Eliminar todos los viajes de prueba del dueño \\"" + nombre_dueno + "\\"?\\n\\n"',
            '        + "Se conserva el viaje principal y cualquier viaje que no tenga prefijo de prueba. Los viajes con ventas no se eliminan.\\n\\n"',
            '        + "Esta acción no se puede deshacer."',
            '    );',
            '    if (!ok) return;',
            '',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "viajes/limpiar_prueba",',
            '            nombre_dueno,',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al limpiar viajes", \'error\');',
            '        return;',
            '    }',
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " viaje(s) de prueba.";',
            '    if (datos.con_ventas && datos.con_ventas.length > 0) {',
            '        msg += " " + datos.con_ventas.length + " no se pudieron eliminar (tienen ventas).";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
            '    await listar_viajes(nombre_dueno, \'dueno\');',
            '}',
            '',
            'document.getElementById(\'boton_limpiar_viajes_prueba\')?.addEventListener(\'click\', limpiar_viajes_de_prueba_ui);',
        ],
    ],

    // ========================================================
    // viajes-nucleo.js — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: bump @version a 1.5piloto.74n',
        'buscar' => [
            ' * Núcleo de viajes: carga, listado, detalle en modal y eliminación.',
            ' * @version 1.5piloto.65',
        ],
        'reemplazar' => [
            ' * Núcleo de viajes: carga, listado, detalle en modal y eliminación.',
            ' * @version 1.5piloto.74n',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.7 Viaje.php
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.7 agregar limpiar_viajes_de_prueba',
        'buscar' => [
            '- `obtener_declaracion_jurada`, `guardar_declaracion_jurada`.',
            '- `_sustituir_placeholders_dj`.',
            '- Constantes `TEXTO_DJ_MAYOR_DEFAULT`, `TEXTO_DJ_MENOR_DEFAULT`.',
        ],
        'reemplazar' => [
            '- `obtener_declaracion_jurada`, `guardar_declaracion_jurada`.',
            '- `_sustituir_placeholders_dj`.',
            '- `limpiar_viajes_de_prueba($nombre_dueno)`: elimina los viajes',
            '  de prueba de un dueño, conservando el viaje principal (por',
            '  nombre visible) y los que no tengan prefijo de prueba',
            '  (`viajeprueba`, `viajemicro`, `viajeval`, `viajedup`,',
            '  `viajecol`, `viajesin`). No elimina viajes con ventas.',
            '  Pensada para el botón de limpieza del admin.',
            '- Constantes `TEXTO_DJ_MAYOR_DEFAULT`, `TEXTO_DJ_MENOR_DEFAULT`.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.14 Enrutador
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.14 agregar subaccion limpiar_prueba',
        'buscar' => [
            'Módulos: `autenticar`, `administrador`, `dueno`, `sesiones`,',
            '`empresas`, `vehiculos`, `viajes`, `ventas`, `pasajeros`,',
            '`rendiciones`, `liquidaciones`, `cancelaciones`.',
        ],
        'reemplazar' => [
            'Módulos: `autenticar`, `administrador`, `dueno`, `sesiones`,',
            '`empresas`, `vehiculos`, `viajes`, `ventas`, `pasajeros`,',
            '`rendiciones`, `liquidaciones`, `cancelaciones`.',
            '',
            'Subacción especial: `viajes/limpiar_prueba` (solo admin).',
            'Elimina los viajes de prueba del dueño seleccionado.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — historial v74n
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: agregar v74n al historial',
        'buscar' => [
            '- **v74m**: eliminado el respaldo JSON automático de',
        ],
        'reemplazar' => [
            '- **v74n**: botón "Limpiar viajes de prueba" en la pestaña',
            '  Viajes (solo admin). Nueva función',
            '  `limpiar_viajes_de_prueba($nombre_dueno)` en `Viaje.php` y',
            '  subacción `viajes/limpiar_prueba` en el enrutador.',
            '  Conserva el viaje principal "Peregrinación a la Visita',
            '  del Papa León XIV a Luján" (por nombre visible) y los',
            '  viajes que no tengan prefijo de prueba. No elimina',
            '  viajes con ventas. Motivo: las pruebas automáticas del',
            '  plugin acumulan viajes (20 con el grafo actual) que',
            '  ralentizan los listados: `formatear_viaje` recorre todas',
            '  las ventas del dueño por cada viaje, así que el costo de',
            '  `cargar_viajes` escala con V × W.',
            '- **v74m**: eliminado el respaldo JSON automático de',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 cabecera
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 cabecera a v74n',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74m',
            '(eliminado el respaldo JSON automático de `guardar_ambos`.',
            'El `json_encode` de todo el grafo se volvió el cuello de',
            'botella cuando el grafo creció con terminales, vehículos y',
            'ventas: cada operación de guardado tardaba segundos y',
            'rompía los timeouts de las pruebas del plugin. Ahora',
            '`guardar_ambos` solo guarda SQL. El respaldo en otros',
            'formatos pasa a ser una acción manual del admin, a',
            'implementar en el rediseño del panel).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74n',
            '(botón "Limpiar viajes de prueba" en la pestaña Viajes para',
            'el admin. Nueva función `limpiar_viajes_de_prueba` en',
            '`Viaje.php` y subacción `viajes/limpiar_prueba` en el',
            'enrutador. Conserva el viaje principal y los que no tengan',
            'prefijo de prueba. Motivo: las pruebas del plugin acumulan',
            'viajes que ralentizan `cargar_viajes`).',
            'Antes: v1.5piloto.74m',
            '(eliminado el respaldo JSON automático de `guardar_ambos`.',
            'El `json_encode` de todo el grafo se volvió el cuello de',
            'botella cuando el grafo creció con terminales, vehículos y',
            'ventas: cada operación de guardado tardaba segundos y',
            'rompía los timeouts de las pruebas del plugin. Ahora',
            '`guardar_ambos` solo guarda SQL. El respaldo en otros',
            'formatos pasa a ser una acción manual del admin, a',
            'implementar en el rediseño del panel).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar v74n al estado',
        'buscar' => [
            '- Cerramos en v74m el fix de performance: `guardar_ambos` ya',
            '  no guarda el JSON de respaldo automático. El `json_encode`',
            '  de todo el grafo se volvió el cuello de botella cuando',
            '  creció (3 terminales, varios vehículos, ventas). El',
            '  respaldo JSON pasa a ser acción manual del admin (a',
            '  implementar en el rediseño del panel).',
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
        ],
        'reemplazar' => [
            '- Cerramos en v74n el botón "Limpiar viajes de prueba" para',
            '  el admin. El grafo del dueño `carmen1` tenía 21 viajes',
            '  (20 de ellos de pruebas anteriores), y `formatear_viaje`',
            '  escala con V × W (viajes × ventas). El botón borra los',
            '  viajes de prueba conservando el viaje principal',
            '  "Peregrinación a la Visita del Papa León XIV a Luján" y',
            '  los que no tengan prefijo de prueba.',
            '- Cerramos en v74m el fix de performance: `guardar_ambos` ya',
            '  no guarda el JSON de respaldo automático. El `json_encode`',
            '  de todo el grafo se volvió el cuello de botella cuando',
            '  creció (3 terminales, varios vehículos, ventas). El',
            '  respaldo JSON pasa a ser acción manual del admin (a',
            '  implementar en el rediseño del panel).',
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §13 estado al cierre
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §13 estado a v74n',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74m (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro: rechaza vehículos sin asientos y',
            'duplicados en el mismo viaje, y evita colisiones de numeración',
            'al quitar un micro del medio. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático, que se había',
            'vuelto el cuello de botella del guardado cuando el grafo',
            'creció. El plugin de pruebas (`iteradoresJS/`,',
            'v1.5plugin.5c) tiene 29 pruebas corriendo.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74n (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático. Fix de',
            'v74n: botón "Limpiar viajes de prueba" para el admin, que',
            'borra los viajes acumulados por las pruebas del plugin',
            '(conserva el viaje principal). El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.5d) tiene 29 pruebas',
            'corriendo.',
            '',
            '**Deuda técnica pendiente:** `formatear_viaje` en `Viaje.php`',
            'escala como O(V × W): por cada viaje, recorre todas las ventas',
            'del dueño para calcular `viaje_tiene_ventas` y',
            '`vendidos_por_micró`. Con muchos viajes y ventas, el costo',
            'crece. La limpieza de viajes mitiga el problema pero no lo',
            'elimina. La optimización real (índice de ventas por viaje +',
            'cacheo de contadores) queda para una tanda dedicada.',
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