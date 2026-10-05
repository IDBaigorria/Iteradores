<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.76:
 *   - Fase 2, flujos 15, 16, 17, 18 y 19 arreglados:
 *     * eliminar_pasajero destruye el subárbol del pasajero.
 *     * limpiar_pasajeros_de_prueba destruye el subárbol.
 *     * subir_declaracion_jurada_pasajero destruye la DJ previa.
 *     * eliminar_declaracion_jurada_pasajero destruye la DJ.
 *     * actualizar_pasajero destruye las hojas al limpiar un
 *       campo (antes quedaban huérfanas).
 *   - Helpers nuevos: _destruir_declaracion_jurada_pasajero,
 *     _destruir_pasajero_completo.
 *
 * Uso:
 *   php aplicar_cambios.php
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
    // Pasajero.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Bump @version a 1.5piloto.76',
        'buscar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.74o',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.76',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Fix actualizar_pasajero: destruir hoja al borrar campo',
        'buscar' => [
            '            $hubo_cambios = true;',
            '            if ($nodo_campo) {',
            '                if ($valor === \'\') $nodo_pasajero->eliminar_adyacente($campo);',
            '                else $nodo_campo->_dato($valor);',
            '            } else {',
        ],
        'reemplazar' => [
            '            $hubo_cambios = true;',
            '            if ($nodo_campo) {',
            '                if ($valor === \'\') {',
            '                    // Fase 2, v76: destruir la hoja al borrar',
            '                    // el campo. Antes solo se desenlazaba.',
            '                    $nodo_pasajero->eliminar_adyacente($campo);',
            '                    Nodo::eliminar($nodo_campo);',
            '                } else {',
            '                    $nodo_campo->_dato($valor);',
            '                }',
            '            } else {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Fix eliminar_pasajero: destruir subárbol',
        'buscar' => [
            '/**',
            ' * Elimina un pasajero si no tiene pasajes comprados.',
            ' */',
            'function eliminar_pasajero(string $nombre_dueno, string $dni): array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_pasajero = $contenedor->adyacente($dni);',
            '    if (!$nodo_pasajero) return [\'exito\' => false, \'error\' => \'Pasajero no encontrado\'];',
            '',
            '    if (pasajero_tiene_pasajes($nombre_dueno, $dni)) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar: el pasajero tiene pasajes comprados.\'];',
            '    }',
            '',
            '    $contenedor->eliminar_adyacente($dni);',
            '    Nodo::eliminar($nodo_pasajero);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye la declaración jurada adjunta de un pasajero',
            ' * (nodo contenedor + sus 4 sub-hijos).',
            ' *',
            ' * El nodo DJ tiene: dato (ruta relativa), y los enlaces',
            ' * `nombre_original`, `tipo`, `tamano`, `fecha_subida`,',
            ' * todos hojas. Se destruyen con _destruir_campos_simples',
            ' * y después el nodo DJ.',
            ' *',
            ' * @param Nodo $nodo_dj',
            ' * @return void',
            ' */',
            'function _destruir_declaracion_jurada_pasajero(Nodo $nodo_dj): void {',
            '    _destruir_campos_simples($nodo_dj);',
            '    Nodo::eliminar($nodo_dj);',
            '}',
            '',
            '/**',
            ' * Destruye el subárbol completo de un pasajero: la',
            ' * declaración jurada adjunta (si existe) y todos sus',
            ' * campos simples.',
            ' *',
            ' * No desenlaza el pasajero del contenedor: de eso se',
            ' * encarga el llamador (eliminar_pasajero o',
            ' * limpiar_pasajeros_de_prueba).',
            ' *',
            ' * @param Nodo $nodo_pasajero',
            ' * @return void',
            ' */',
            'function _destruir_pasajero_completo(Nodo $nodo_pasajero): void {',
            '    // 1. Declaración jurada adjunta (contenedor con 4 hijos).',
            '    $nodo_dj = $nodo_pasajero->adyacente(\'declaracion_jurada\');',
            '    if ($nodo_dj) {',
            '        $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
            '        _destruir_declaracion_jurada_pasajero($nodo_dj);',
            '    }',
            '',
            '    // 2. Campos simples del pasajero (nombres, apellido,',
            '    //    email, celular, celular_emergencia, fecha_nacimiento,',
            '    //    localidad, direccion, fecha_ultima_modificacion).',
            '    _destruir_campos_simples($nodo_pasajero);',
            '',
            '    // 3. Destruir el nodo pasajero.',
            '    Nodo::eliminar($nodo_pasajero);',
            '}',
            '',
            '/**',
            ' * Elimina un pasajero si no tiene pasajes comprados.',
            ' *',
            ' * A partir de v1.5piloto.76 (Fase 2 del plan de optimización',
            ' * del grafo): destruye el subárbol completo del pasajero',
            ' * (campos personales, fecha de última modificación, y la',
            ' * declaración jurada adjunta con sus 4 sub-campos) en lugar',
            ' * de dejarlo huérfano. Antes quedaban ~12-15 nodos por',
            ' * pasajero.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $dni',
            ' * @return array',
            ' */',
            'function eliminar_pasajero(string $nombre_dueno, string $dni): array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_pasajero = $contenedor->adyacente($dni);',
            '    if (!$nodo_pasajero) return [\'exito\' => false, \'error\' => \'Pasajero no encontrado\'];',
            '',
            '    if (pasajero_tiene_pasajes($nombre_dueno, $dni)) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar: el pasajero tiene pasajes comprados.\'];',
            '    }',
            '',
            '    // Fase 2, v76: destruir el subárbol completo antes de',
            '    // desenlazar del contenedor.',
            '    $contenedor->eliminar_adyacente($dni);',
            '    _destruir_pasajero_completo($nodo_pasajero);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Fix limpiar_pasajeros_de_prueba: destruir subárbol',
        'buscar' => [
            '        // Desenlazar y eliminar. Si Nodo::eliminar falla',
            '        // (referencias residuales que se nos escaparon), el',
            '        // enlace ya está roto y el nodo queda huérfano.',
            '        $contenedor->eliminar_adyacente($dni);',
            '        Nodo::eliminar($nodo_pasajero);',
            '        $borrados[] = $dni;',
        ],
        'reemplazar' => [
            '        // Fase 2, v76: destruir el subárbol completo del',
            '        // pasajero antes de desenlazarlo.',
            '        $contenedor->eliminar_adyacente($dni);',
            '        _destruir_pasajero_completo($nodo_pasajero);',
            '        $borrados[] = $dni;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Fix subir_declaracion_jurada_pasajero: destruir DJ previa',
        'buscar' => [
            '    // Eliminar archivo anterior si existe (puede tener otra extensión).',
            '    $nodo_dj_previo = $nodo_pasajero->adyacente(\'declaracion_jurada\');',
            '    if ($nodo_dj_previo) {',
            '        $ruta_previa = __DIR__ . \'/../../\' . $nodo_dj_previo->dato();',
            '        if (file_exists($ruta_previa)) {',
            '            @unlink($ruta_previa);',
            '        }',
            '        $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
            '    }',
        ],
        'reemplazar' => [
            '    // Eliminar archivo anterior si existe (puede tener otra extensión).',
            '    $nodo_dj_previo = $nodo_pasajero->adyacente(\'declaracion_jurada\');',
            '    if ($nodo_dj_previo) {',
            '        $ruta_previa = __DIR__ . \'/../../\' . $nodo_dj_previo->dato();',
            '        if (file_exists($ruta_previa)) {',
            '            @unlink($ruta_previa);',
            '        }',
            '        // Fase 2, v76: destruir el nodo DJ previo con sus 4',
            '        // sub-hijos en lugar de solo desenlazarlo.',
            '        $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
            '        _destruir_declaracion_jurada_pasajero($nodo_dj_previo);',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Fix eliminar_declaracion_jurada_pasajero: destruir DJ',
        'buscar' => [
            '    $ruta = __DIR__ . \'/../../\' . $nodo_dj->dato();',
            '    if (file_exists($ruta)) {',
            '        @unlink($ruta);',
            '    }',
            '',
            '    $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '    $ruta = __DIR__ . \'/../../\' . $nodo_dj->dato();',
            '    if (file_exists($ruta)) {',
            '        @unlink($ruta);',
            '    }',
            '',
            '    // Fase 2, v76: destruir el nodo DJ con sus 4 sub-hijos',
            '    // en lugar de solo desenlazarlo.',
            '    $nodo_pasajero->eliminar_adyacente(\'declaracion_jurada\');',
            '    _destruir_declaracion_jurada_pasajero($nodo_dj);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v76',
        'buscar' => [
            '- **v75**: Fase 2, flujos 13 y 14 arreglados.',
        ],
        'reemplazar' => [
            '- **v76**: Fase 2, flujos 15 a 19 arreglados.',
            '  `eliminar_pasajero` y `limpiar_pasajeros_de_prueba`',
            '  destruyen el subárbol completo del pasajero (campos',
            '  personales, fecha_ultima_modificacion, y la',
            '  declaración jurada adjunta con sus 4 sub-campos).',
            '  `subir_declaracion_jurada_pasajero` y',
            '  `eliminar_declaracion_jurada_pasajero` destruyen el',
            '  nodo DJ con sus sub-hijos. `actualizar_pasajero`',
            '  destruye las hojas al limpiar un campo. Helpers',
            '  nuevos: `_destruir_declaracion_jurada_pasajero`,',
            '  `_destruir_pasajero_completo`.',
            '- **v75**: Fase 2, flujos 13 y 14 arreglados.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujos 15 a 19',
        'buscar' => [
            '**Decimotercer y decimocuarto flujo arreglados en v75:**',
        ],
        'reemplazar' => [
            '**Decimoquinto a decimonoveno flujos arreglados en v76:**',
            '`eliminar_pasajero` y `limpiar_pasajeros_de_prueba`',
            '(destruyen el subárbol del pasajero: campos personales,',
            'fecha de última modificación, y la declaración jurada',
            'adjunta con sus 4 sub-campos),',
            '`subir_declaracion_jurada_pasajero` (destruye la DJ',
            'previa al reemplazarla),',
            '`eliminar_declaracion_jurada_pasajero` (destruye la DJ),',
            'y `actualizar_pasajero` (destruye las hojas al limpiar',
            'un campo). Helpers nuevos:',
            '`_destruir_declaracion_jurada_pasajero`,',
            '`_destruir_pasajero_completo`.',
            '',
            '**Decimotercer y decimocuarto flujo arreglados en v75:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v76',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.75',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76',
            '(Fase 2, flujos 15 a 19: `eliminar_pasajero`,',
            '`limpiar_pasajeros_de_prueba`, `subir_declaracion_jurada_pasajero`,',
            '`eliminar_declaracion_jurada_pasajero`, `actualizar_pasajero`.',
            'Helpers nuevos: `_destruir_declaracion_jurada_pasajero`,',
            '`_destruir_pasajero_completo`.).',
            'Antes: v1.5piloto.75',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v76',
        'buscar' => [
            '- Cerramos en v75 los flujos 13 y 14 de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v76 los flujos 15 a 19 de Fase 2:',
            '  `eliminar_pasajero`, `limpiar_pasajeros_de_prueba`,',
            '  `subir_declaracion_jurada_pasajero`,',
            '  `eliminar_declaracion_jurada_pasajero`, y',
            '  `actualizar_pasajero`. Helpers nuevos:',
            '  `_destruir_declaracion_jurada_pasajero`,',
            '  `_destruir_pasajero_completo`.',
            '- Cerramos en v75 los flujos 13 y 14 de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v76',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.75 (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76 (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v76',
        'buscar' => [
            'v75: flujos 13 y 14 (`eliminar_usuario`,',
            '`actualizar_usuario`) y `Sesion.php`.',
        ],
        'reemplazar' => [
            'v75: flujos 13 y 14 (`eliminar_usuario`,',
            '`actualizar_usuario`) y `Sesion.php`.',
            'v76: flujos 15 a 19 (pasajeros y declaraciones',
            'juradas adjuntas).',
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