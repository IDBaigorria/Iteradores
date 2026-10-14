<?php
/**
 * Aplicador — Fix bug "limpiar viajes de prueba" + reparación de compartidos.
 *
 * El botón "Limpiar viajes de prueba" destruía los viajes sin
 * desincronizarlos de los compartidos. Las aristas del compartido
 * quedaban colgadas, el BFS del compartido se rompía en la próxima
 * carga del terminal, y el terminal dejaba de ver sus viajes.
 *
 * Fix:
 *  1. limpiar_viajes_de_prueba llama a _desincronizar_viaje_de_compartidos
 *     antes de destruir cada viaje.
 *  2. Ampliar prefijos: viajeventgraf, viajedeselgraf, viajecambiomicro.
 *  3. Nuevo helper reparar_compartidos_viajes_dueno que asegura los
 *     compartidos y sincroniza sus viajes. Se llama desde ?reparar_topologia=1.
 *  4. CSS defensivo para el botón: solo visible con body.rol-admin.modo-pruebas.
 *  5. cargar_viajes() fuerza el chequeo del botón por nivel.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$helper_nuevo = [
    '',
    '/**',
    ' * Repara los compartidos del dueño asegurando que cada uno',
    ' * tenga sincronizados los viajes de sus terminales autorizadas.',
    ' * Idempotente.',
    ' *',
    ' * Se usa desde ?reparar_topologia=1 para recuperar el estado',
    ' * tras el bug del botón "Limpiar viajes de prueba" (v79j):',
    ' * al destruir los viajes sin desincronizarlos, las aristas',
    ' * del compartido quedaron colgadas y el BFS del compartido',
    ' * se rompió. La próxima carga del terminal no encontró sus',
    ' * viajes.',
    ' *',
    ' * Por cada (dueño, viaje, terminal autorizada), llama a',
    ' * _sincronizar_terminal_en_compartido_del_viaje, que ya',
    ' * hace el trabajo completo: asegura el compartido (lo crea',
    ' * si falta), repunta el enlace `dueno` del terminal, agrega',
    ' * el viaje al contenedor `viajes` del compartido y agrega',
    ' * las empresas referenciadas por sus micros.',
    ' *',
    ' * @return array{duenos: int, compartidos: int, viajes: int, detalles: array}',
    ' * @since 1.5piloto.79j',
    ' */',
    'function reparar_compartidos_viajes_dueno(): array {',
    '    $res = [\'duenos\' => 0, \'compartidos\' => 0, \'viajes\' => 0, \'detalles\' => []];',
    '',
    '    $nodo_usuarios = Nodo::nodo_por_id(\'usuarios\');',
    '    if (!$nodo_usuarios) return $res;',
    '',
    '    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
    '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
    '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
    '        $res[\'duenos\']++;',
    '        $nombre_dueno_str = (string)$nombre_dueno;',
    '',
    '        $cont_viajes = $nodo_dueno->adyacente(\'viajes\');',
    '        if (!$cont_viajes) continue;',
    '',
    '        $term_sincronizados = [];',
    '        $viajes_sincronizados = 0;',
    '        foreach ((array)$cont_viajes->adyacentes() as $nombre_v => $nodo_v) {',
    '            $terminales = $nodo_v->adyacente(\'terminales_autorizadas\');',
    '            if (!$terminales) continue;',
    '            foreach ((array)$terminales->adyacentes() as $nombre_term => $nodo_tv) {',
    '                $nombre_term = (string)$nombre_term;',
    '                if ($nombre_term === \'\') continue;',
    '                // Sincronizar: crea el compartido si falta,',
    '                // repunta el `dueno` del terminal, agrega',
    '                // el viaje y sus empresas. Idempotente.',
    '                _sincronizar_terminal_en_compartido_del_viaje(',
    '                    $nodo_v, $nombre_term, $nombre_dueno_str',
    '                );',
    '                $term_sincronizados[$nombre_term] = true;',
    '                $viajes_sincronizados++;',
    '            }',
    '        }',
    '',
    '        if (!empty($term_sincronizados)) {',
    '            $res[\'compartidos\'] += count($term_sincronizados);',
    '            $res[\'viajes\'] += $viajes_sincronizados;',
    '            $res[\'detalles\'][] = [',
    '                \'dueno\' => $nombre_dueno_str,',
    '                \'terminales\' => array_keys($term_sincronizados),',
    '                \'viajes_sincronizados\' => $viajes_sincronizados,',
    '            ];',
    '        }',
    '    }',
    '    return $res;',
    '}',
];

$cambios = [
    // ============================================================
    // 1. Fix del bug en limpiar_viajes_de_prueba
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Fix: desincronizar compartidos antes de destruir el viaje',
        'buscar' => [
            '        // Fase 2, v74z: destruir el subárbol completo del viaje',
            '        // antes de desenlazarlo. Antes solo se desenlazaba,',
            '        // dejando el mismo subárbol huérfano que eliminar_viaje',
            '        // antes de v74r (~250 nodos por micro arrastrado).',
            '        _destruir_viaje_completo($nodo_viaje);',
            '        $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '        Nodo::eliminar($nodo_viaje);',
            '        $borrados[] = $nombre_viaje;',
        ],
        'reemplazar' => [
            '        // Fase 2, v74z: destruir el subárbol completo del viaje',
            '        // antes de desenlazarlo. Antes solo se desenlazaba,',
            '        // dejando el mismo subárbol huérfano que eliminar_viaje',
            '        // antes de v74r (~250 nodos por micro arrastrado).',
            '        //',
            '        // Fase B2.4.2 (v79j): desincronizar el viaje de los',
            '        // compartidos de sus terminales autorizadas ANTES de',
            '        // destruirlo. Sin esto, las aristas del compartido',
            '        // quedan colgadas, el BFS del compartido se rompe en',
            '        // la próxima carga del terminal, y la terminal deja',
            '        // de ver sus viajes. Mismo fix que tenía eliminar_viaje',
            '        // desde v77q, pero faltaba acá.',
            '        _desincronizar_viaje_de_compartidos($nodo_viaje);',
            '',
            '        _destruir_viaje_completo($nodo_viaje);',
            '        $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '        Nodo::eliminar($nodo_viaje);',
            '        $borrados[] = $nombre_viaje;',
        ],
    ],

    // ============================================================
    // 2. Ampliar prefijos de prueba
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Ampliar prefijos de viajes de prueba',
        'buscar' => [
            '    $prefijos = [\'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\'];',
        ],
        'reemplazar' => [
            '    $prefijos = [',
            '        \'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\',',
            '        // Prefijos de las pruebas del plugin (agregados en v79j).',
            '        \'viajeventgraf\',    // prueba 34: cancelar venta',
            '        \'viajedeselgraf\',   // prueba 35: deseleccionar asiento',
            '        \'viajecambiomicro\', // prueba 36: cambiar micro',
            '    ];',
        ],
    ],

    // ============================================================
    // 3. Bump Viaje.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump Viaje.php',
        'buscar' => [' * @version   1.5piloto.79g'],
        'reemplazar' => [' * @version   1.5piloto.79j'],
    ],

    // ============================================================
    // 4. Nuevo helper en migrar_topologia.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/migrar_topologia.php',
        'descripcion' => 'Nuevo helper reparar_compartidos_viajes_dueno',
        'buscar' => [
            '        $nodo_dueno->_adyacente_en($encontrado, \'pasajeros\');',
            '        $res[\'reparados\']++;',
            '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno];',
            '    }',
            '    return $res;',
            '}',
            '',
            '?>',
        ],
        'reemplazar' => array_merge(
            [
                '        $nodo_dueno->_adyacente_en($encontrado, \'pasajeros\');',
                '        $res[\'reparados\']++;',
                '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno];',
                '    }',
                '    return $res;',
                '}',
            ],
            $helper_nuevo,
            [
                '',
                '?>',
            ]
        ),
    ],

    // ============================================================
    // 5. Bump migrar_topologia.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/migrar_topologia.php',
        'descripcion' => 'Bump migrar_topologia.php',
        'buscar' => [' * @version 1.5piloto.78r'],
        'reemplazar' => [' * @version 1.5piloto.79j'],
    ],

    // ============================================================
    // 6. Llamar al helper desde index.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Llamar a reparar_compartidos_viajes_dueno',
        'buscar' => [
            '    $r_comp = reparar_compartidos_dueno();',
            '    $r_viajes = reparar_viajes_dueno();',
            '    $r_emp = reparar_empresas_dueno();',
            '    $r_pas = reparar_pasajeros_dueno();',
        ],
        'reemplazar' => [
            '    $r_comp = reparar_compartidos_dueno();',
            '    $r_viajes = reparar_viajes_dueno();',
            '    $r_emp = reparar_empresas_dueno();',
            '    $r_pas = reparar_pasajeros_dueno();',
            '    // v79j: recuperar compartidos tras el bug del botón',
            '    // "Limpiar viajes de prueba". Asegura el compartido',
            '    // de cada terminal del dueño y sincroniza los viajes',
            '    // autorizados. Idempotente.',
            '    $r_compartidos_viajes = reparar_compartidos_viajes_dueno();',
        ],
    ],

    // ============================================================
    // 7. Reportar el nuevo helper en el output de index.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Output del helper en index.php',
        'buscar' => [
            '    echo "\n-- Contenedor `pasajeros` faltante --\n";',
            '    echo "   dueños procesados: " . $r_pas[\'duenos\'] . "\n";',
            '    echo "   recuperados:       " . $r_pas[\'reparados\'] . "\n";',
            '    foreach ($r_pas[\'detalles\'] as $d) {',
            '        echo "     " . $d[\'dueno\'] . "\n";',
            '    }',
        ],
        'reemplazar' => [
            '    echo "\n-- Contenedor `pasajeros` faltante --\n";',
            '    echo "   dueños procesados: " . $r_pas[\'duenos\'] . "\n";',
            '    echo "   recuperados:       " . $r_pas[\'reparados\'] . "\n";',
            '    foreach ($r_pas[\'detalles\'] as $d) {',
            '        echo "     " . $d[\'dueno\'] . "\n";',
            '    }',
            '',
            '    echo "\n-- Compartidos por terminal (v79j) --\n";',
            '    echo "   dueños procesados:     " . $r_compartidos_viajes[\'duenos\'] . "\n";',
            '    echo "   compartidos asegurados: " . $r_compartidos_viajes[\'compartidos\'] . "\n";',
            '    echo "   viajes sincronizados:   " . $r_compartidos_viajes[\'viajes\'] . "\n";',
            '    foreach ($r_compartidos_viajes[\'detalles\'] as $d) {',
            '        echo "     " . $d[\'dueno\'] . ": " . implode(\', \', $d[\'terminales\'])',
            '            . " (" . $d[\'viajes_sincronizados\'] . " viajes)\n";',
            '    }',
        ],
    ],

    // ============================================================
    // 8. Incluir el nuevo helper en total_reparados
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Sumar el nuevo helper a total_reparados',
        'buscar' => [
            '    $total_reparados = ($r_admin[\'creado\'] ? 1 : 0)',
            '        + $r_comp[\'reparados\']',
            '        + $r_viajes[\'reparados\']',
            '        + $r_emp[\'reparados\']',
            '        + $r_pas[\'reparados\'];',
        ],
        'reemplazar' => [
            '    $total_reparados = ($r_admin[\'creado\'] ? 1 : 0)',
            '        + $r_comp[\'reparados\']',
            '        + $r_viajes[\'reparados\']',
            '        + $r_emp[\'reparados\']',
            '        + $r_pas[\'reparados\']',
            '        + $r_compartidos_viajes[\'compartidos\'];',
        ],
    ],

    // ============================================================
    // 9. Bump index.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Bump index.php',
        'buscar' => [' * @version   1.5piloto.79f'],
        'reemplazar' => [' * @version   1.5piloto.79j'],
    ],

    // ============================================================
    // 10. Defensa en cargar_viajes() de viajes-nucleo.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Defensa del botón limpiar viajes en cargar_viajes',
        'buscar' => [
            'async function cargar_viajes() {',
            '    const panel_dueno = $("#panel_selector_dueno_viajes");',
            '    const boton_agregar = $("#boton_agregar_viaje");',
            '    const lista = $("#lista_viajes");',
            '    lista.innerHTML = \'\';',
            '    ocultar_detalle_viaje();',
        ],
        'reemplazar' => [
            'async function cargar_viajes() {',
            '    const panel_dueno = $("#panel_selector_dueno_viajes");',
            '    const boton_agregar = $("#boton_agregar_viaje");',
            '    const lista = $("#lista_viajes");',
            '    lista.innerHTML = \'\';',
            '    ocultar_detalle_viaje();',
            '',
            '    // v79j: defensa del botón "Limpiar viajes de prueba".',
            '    // Aunque el HTML lo muestre, forzamos el chequeo por',
            '    // nivel y modo pruebas. Cubre el caso de un cambio de',
            '    // sesión sin recargar la página (bug de _limpiar_',
            '    // contenido_dinamico).',
            '    const btn_limpiar_viajes = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (btn_limpiar_viajes) {',
            '        const puede_limpiar = usuario_actual',
            '            && usuario_actual.nivel === \'admin\'',
            '            && window.entorno_es_pruebas === true;',
            '        btn_limpiar_viajes.style.display = puede_limpiar ? \'inline-block\' : \'none\';',
            '    }',
        ],
    ],

    // ============================================================
    // 11. Bump viajes-nucleo.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Bump viajes-nucleo.js',
        'buscar' => [' * @version 1.5piloto.74o'],
        'reemplazar' => [' * @version 1.5piloto.79j'],
    ],

    // ============================================================
    // 12. Nuevo archivo CSS para el botón (evita pisar estilos.css)
    // ============================================================
    [
        'tipo' => 'crear',
        'archivo' => 'estilos-botones-admin.css',
        'descripcion' => 'CSS defensivo del botón de admin',
        'contenido' => [
            '/*',
            ' * Reglas defensivas para botones que solo debe ver el admin',
            ' * en modo pruebas. Se aplican aunque falle la limpieza de UI',
            ' * al cambiar de sesión. Requieren que aplicacion.js agregue',
            ' * la clase `rol-admin` al body al loguear como admin, y',
            ' * `modo-pruebas` cuando window.entorno_es_pruebas === true.',
            ' *',
            ' * Mientras aplicacion.js no agregue las clases, el botón',
            ' * queda oculto para todos. Es preferible a que un terminal',
            ' * lo toque y rompa los compartidos (bug v79j).',
            ' */',
            '#boton_limpiar_viajes_prueba { display: none !important; }',
            'body.rol-admin.modo-pruebas #boton_limpiar_viajes_prueba { display: inline-block !important; }',
        ],
    ],

    // ============================================================
    // 13. Link al nuevo CSS en el HTML
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Link al nuevo CSS',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-rendiciones.css?v=1.5piloto.65">',
            '</head>',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-rendiciones.css?v=1.5piloto.65">',
            '<link rel="stylesheet" href="estilos-botones-admin.css?v=1.5piloto.79j">',
            '</head>',
        ],
    ],

    // ============================================================
    // 14. Bump del ?v= de viajes-nucleo.js en el HTML
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de viajes-nucleo.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.74o"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.79j"></script>',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Fix limpiar_viajes + reparar compartidos ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$creaciones = [];
$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
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

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";