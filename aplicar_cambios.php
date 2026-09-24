<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores.
 *
 * Tanda v1.5piloto.62d: destino autocompletado y leyenda en Anexo II.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

    // --------------------------------------------------------
    // Aplicacion/Viajes/Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump de version a 1.5piloto.62d',
        'buscar' => [
            ' * @version   1.5piloto.62c',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.62d',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Anexo I: usar placeholder de destino en "a realizarse en"',
        'buscar' => [
            '{{NOMBRE_VIAJE}}',
            '',
            'a realizarse en ....................................................................................................................................................................................,',
            '',
            'el/los día/días {{FECHA_VIAJE}}',
            '',
            'Declaro que he sido informado/a de las características del viaje, las actividades previstas, el medio de transporte, los lugares de alojamiento y demás servicios comprendidos.',
        ],
        'reemplazar' => [
            '{{NOMBRE_VIAJE}}',
            '',
            'a realizarse en {{DESTINO_VIAJE}},',
            '',
            'el/los día/días {{FECHA_VIAJE}}',
            '',
            'Declaro que he sido informado/a de las características del viaje, las actividades previstas, el medio de transporte, los lugares de alojamiento y demás servicios comprendidos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Anexo II: usar placeholder de destino en "a realizarse en" y agregar leyenda al bloque padre/madre/tutor',
        'buscar' => [
            'DNI ..........................................................., en carácter de:',
            '',
            '☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable',
            '',
            'autorizo al/la menor ....................................................................................................................,',
            '',
            'DNI ..........................................................., a participar del viaje turístico/religioso denominado',
            '',
            '{{NOMBRE_VIAJE}}',
            '',
            'a realizarse en ....................................................................................................................................................................................,',
            '',
            'el/los día/días {{FECHA_VIAJE}}',
        ],
        'reemplazar' => [
            'DNI ..........................................................., en carácter de:',
            '',
            '☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable',
            '   (tildar o marcar con una X)',
            '',
            'autorizo al/la menor ....................................................................................................................,',
            '',
            'DNI ..........................................................., a participar del viaje turístico/religioso denominado',
            '',
            '{{NOMBRE_VIAJE}}',
            '',
            'a realizarse en {{DESTINO_VIAJE}},',
            '',
            'el/los día/días {{FECHA_VIAJE}}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Agregar sustitucion de {{DESTINO_VIAJE}} en _sustituir_placeholders_dj',
        'buscar' => [
            'function _sustituir_placeholders_dj(string $contenido, Nodo $nodo_viaje): string {',
            '    $nombre_visible = $nodo_viaje->adyacente(\'nombre\')',
            '        ? $nodo_viaje->adyacente(\'nombre\')->dato()',
            '        : $nodo_viaje->dato();',
            '',
            '    $fecha_iso = $nodo_viaje->adyacente(\'fecha\')',
            '        ? trim($nodo_viaje->adyacente(\'fecha\')->dato())',
            '        : \'\';',
            '',
            '    if ($fecha_iso === \'\' || $fecha_iso === \'a confirmar\') {',
            '        $fecha_mostrar = \'............................................................................................................................\';',
            '    } else {',
            '        $fecha_mostrar = formatear_fecha_visible($fecha_iso);',
            '    }',
            '',
            '    return str_replace(',
            '        [\'{{NOMBRE_VIAJE}}\', \'{{FECHA_VIAJE}}\'],',
            '        [$nombre_visible, $fecha_mostrar],',
            '        $contenido',
            '    );',
            '}',
        ],
        'reemplazar' => [
            'function _sustituir_placeholders_dj(string $contenido, Nodo $nodo_viaje): string {',
            '    $nombre_visible = $nodo_viaje->adyacente(\'nombre\')',
            '        ? $nodo_viaje->adyacente(\'nombre\')->dato()',
            '        : $nodo_viaje->dato();',
            '',
            '    $destino = $nodo_viaje->adyacente(\'destino\')',
            '        ? trim($nodo_viaje->adyacente(\'destino\')->dato())',
            '        : \'\';',
            '    if ($destino === \'\') {',
            '        $destino = \'............................................................................................................................\';',
            '    }',
            '',
            '    $fecha_iso = $nodo_viaje->adyacente(\'fecha\')',
            '        ? trim($nodo_viaje->adyacente(\'fecha\')->dato())',
            '        : \'\';',
            '',
            '    if ($fecha_iso === \'\' || $fecha_iso === \'a confirmar\') {',
            '        $fecha_mostrar = \'............................................................................................................................\';',
            '    } else {',
            '        $fecha_mostrar = formatear_fecha_visible($fecha_iso);',
            '    }',
            '',
            '    return str_replace(',
            '        [\'{{NOMBRE_VIAJE}}\', \'{{DESTINO_VIAJE}}\', \'{{FECHA_VIAJE}}\'],',
            '        [$nombre_visible, $destino, $fecha_mostrar],',
            '        $contenido',
            '    );',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // index.php — bloque de migración v3
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Bump de version a 1.5piloto.62d',
        'buscar' => [
            ' * @version   1.5piloto.62b',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.62d',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Agregar bloque temporal de migracion v3',
        'buscar' => [
            '// Crear usuario administrador si no existe',
            'if (!buscar_usuario_por_codigo(Conf::CODIGO_ADMIN)) {',
        ],
        'reemplazar' => [
            '// ==== Bloque temporal para migración de declaraciones juradas v3 (v1.5piloto.62d) ====',
            '// Reemplaza "a realizarse en [puntos]," por "a realizarse en {{DESTINO_VIAJE}},"',
            '// en los textos guardados, y agrega la leyenda "(tildar o marcar con una X)"',
            '// al bloque de checkboxes padre/madre/tutor del Anexo II.',
            '// Solo actúa si encuentra el patrón exacto. Si el dueño personalizó esas',
            '// partes, no las toca. Es idempotente.',
            'if (isset($_GET[\'migrar_declaraciones_juradas_v3\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_declaraciones_juradas_v3.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_declaraciones_juradas_v3();',
            '    echo "Migración de declaraciones juradas v3 completada.\\n";',
            '    echo "Dueños procesados:         {$res[\'duenos_procesados\']}\\n";',
            '    echo "Viajes procesados:         {$res[\'viajes_procesados\']}\\n";',
            '    echo "--- Anexo I (mayor) ---\\n";',
            '    echo "Migrados:                  {$res[\'mayor_migrados\']}\\n";',
            '    echo "Sin cambio:                {$res[\'mayor_sin_cambio\']}\\n";',
            '    echo "--- Anexo II (menor) ---\\n";',
            '    echo "Migrados:                  {$res[\'menor_migrados\']}\\n";',
            '    echo "Sin cambio:                {$res[\'menor_sin_cambio\']}\\n";',
            '    exit;',
            '}',
            '',
            '// Crear usuario administrador si no existe',
            'if (!buscar_usuario_por_codigo(Conf::CODIGO_ADMIN)) {',
        ],
    ],

    // --------------------------------------------------------
    // Crear script de migración v3
    // --------------------------------------------------------

    [
        'tipo' => 'crear',
        'archivo' => 'miscelaneas/migrar_declaraciones_juradas_v3.php',
        'descripcion' => 'Nuevo script de migracion v3',
        'contenido' => [
            '<?php',
            '/**',
            ' * Migración v1.5piloto.62d — Destino autocompletado y leyenda en Anexo II.',
            ' *',
            ' * Reemplaza en los textos guardados de las declaraciones juradas:',
            ' *   - "a realizarse en [puntos]," por "a realizarse en {{DESTINO_VIAJE}},"',
            ' *   - Agrega la leyenda "(tildar o marcar con una X)" al bloque de',
            ' *     checkboxes padre/madre/tutor del Anexo II, si no la tiene.',
            ' *',
            ' * Solo actúa si encuentra el patrón exacto. Si el dueño personalizó',
            ' * esas partes, no las toca. Es idempotente.',
            ' *',
            ' * @package   Iteradores',
            ' * @version   1.5piloto.62d',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Configuracion\\Conf;',
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            'function migrar_declaraciones_juradas_v3(): array {',
            '    $res = [',
            '        \'duenos_procesados\' => 0,',
            '        \'viajes_procesados\' => 0,',
            '        \'mayor_migrados\' => 0,',
            '        \'mayor_sin_cambio\' => 0,',
            '        \'menor_migrados\' => 0,',
            '        \'menor_sin_cambio\' => 0,',
            '    ];',
            '',
            '    // Fragmento de "a realizarse en" con los puntos del default.',
            '    $buscar_destino_puntos = "a realizarse en ....................................................................................................................................................................................,";',
            '    $reemplazo_destino_placeholder = "a realizarse en {{DESTINO_VIAJE}},";',
            '',
            '    // Bloque de checkboxes padre/madre/tutor en el Anexo II.',
            '    $buscar_checkboxes_menor = "☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable\\n\\nautorizo al/la menor";',
            '    $reemplazo_checkboxes_menor = "☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable\\n   (tildar o marcar con una X)\\n\\nautorizo al/la menor";',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return $res;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $res[\'duenos_procesados\']++;',
            '',
            '        $nodo_viajes = $nodo_dueno->adyacente(\'viajes\');',
            '        if (!$nodo_viajes) continue;',
            '',
            '        foreach ($nodo_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {',
            '            $res[\'viajes_procesados\']++;',
            '',
            '            // --- Anexo I (mayor) ---',
            '            $nodo_mayor = $nodo_viaje->adyacente(\'declaracion_jurada_mayor\');',
            '            if ($nodo_mayor) {',
            '                $contenido = $nodo_mayor->dato();',
            '                if (strpos($contenido, $buscar_destino_puntos) !== false) {',
            '                    $contenido = str_replace($buscar_destino_puntos, $reemplazo_destino_placeholder, $contenido);',
            '                    $nodo_mayor->_dato($contenido);',
            '                    $res[\'mayor_migrados\']++;',
            '                } else {',
            '                    $res[\'mayor_sin_cambio\']++;',
            '                }',
            '            }',
            '',
            '            // --- Anexo II (menor) ---',
            '            $nodo_menor = $nodo_viaje->adyacente(\'declaracion_jurada_menor\');',
            '            if ($nodo_menor) {',
            '                $contenido = $nodo_menor->dato();',
            '                $cambio = false;',
            '',
            '                if (strpos($contenido, $buscar_destino_puntos) !== false) {',
            '                    $contenido = str_replace($buscar_destino_puntos, $reemplazo_destino_placeholder, $contenido);',
            '                    $cambio = true;',
            '                }',
            '                if (strpos($contenido, $buscar_checkboxes_menor) !== false) {',
            '                    $contenido = str_replace($buscar_checkboxes_menor, $reemplazo_checkboxes_menor, $contenido);',
            '                    $cambio = true;',
            '                }',
            '',
            '                if ($cambio) {',
            '                    $nodo_menor->_dato($contenido);',
            '                    $res[\'menor_migrados\']++;',
            '                } else {',
            '                    $res[\'menor_sin_cambio\']++;',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    Controlador::guardar(Conf::NOMBRE_APP);',
            '    return $res;',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html — bump version
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de version de viajes-nucleo.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.62c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.62d"></script>',
        ],
    ],

    // --------------------------------------------------------
    // Aplicacion/Viajes/viajes-nucleo.js — bump version
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Bump de version a 1.5piloto.62d',
        'buscar' => [
            ' * @version 1.5piloto.62c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.62d',
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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";