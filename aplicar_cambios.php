<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74u:
 *   - Fase 2, tercer y cuarto flujo arreglados:
 *     * eliminar_terminal_autorizada destruye el TerminalViaje.
 *     * _guardar_paradas_intermedias destruye las paradas viejas
 *       que no se reutilizan al editar el viaje.
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
    // Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.74u',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74t',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74u',
        ],
    ],

    // ---- Fix 1: destruir las paradas viejas que no se reutilizan ----

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Destruir paradas viejas no reutilizadas',
        'buscar' => [
            '    // 4. Reinsertar en orden (usando _hmi, que agrega al inicio, por eso invertimos)',
            '    $nombres_invertidos = array_reverse($nombres_nuevos);',
            '    foreach ($nombres_invertidos as $nombre_parada) {',
            '        $hora = $paradas_normalizadas[$nombre_parada];',
            '',
            '        if (isset($nodos_existentes[$nombre_parada])) {',
            '            $nodo_parada = $nodos_existentes[$nombre_parada];',
            '            // Actualizar la hora estimada del nodo reutilizado',
            '            $nodo_hora = $nodo_parada->adyacente(\'hora_estimada\');',
            '            if ($hora === \'\') {',
            '                if ($nodo_hora) $nodo_parada->eliminar_adyacente(\'hora_estimada\');',
            '            } else {',
            '                if ($nodo_hora) $nodo_hora->_dato($hora);',
            '                else $nodo_parada->_adyacente_en(Nodo::crear_con_dato($hora), \'hora_estimada\');',
            '            }',
            '        } else {',
            '            $nodo_parada = Nodo::crear_con_dato($nombre_parada);',
            '            if ($hora !== \'\') {',
            '                $nodo_parada->_adyacente_en(Nodo::crear_con_dato($hora), \'hora_estimada\');',
            '            }',
            '        }',
            '        _hmi($nodo_paradas, $nodo_parada);',
            '    }',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '    // 4. Reinsertar en orden (usando _hmi, que agrega al inicio, por eso invertimos)',
            '    $nombres_invertidos = array_reverse($nombres_nuevos);',
            '    foreach ($nombres_invertidos as $nombre_parada) {',
            '        $hora = $paradas_normalizadas[$nombre_parada];',
            '',
            '        if (isset($nodos_existentes[$nombre_parada])) {',
            '            $nodo_parada = $nodos_existentes[$nombre_parada];',
            '            // Actualizar la hora estimada del nodo reutilizado',
            '            $nodo_hora = $nodo_parada->adyacente(\'hora_estimada\');',
            '            if ($hora === \'\') {',
            '                if ($nodo_hora) $nodo_parada->eliminar_adyacente(\'hora_estimada\');',
            '            } else {',
            '                if ($nodo_hora) $nodo_hora->_dato($hora);',
            '                else $nodo_parada->_adyacente_en(Nodo::crear_con_dato($hora), \'hora_estimada\');',
            '            }',
            '        } else {',
            '            $nodo_parada = Nodo::crear_con_dato($nombre_parada);',
            '            if ($hora !== \'\') {',
            '                $nodo_parada->_adyacente_en(Nodo::crear_con_dato($hora), \'hora_estimada\');',
            '            }',
            '        }',
            '        _hmi($nodo_paradas, $nodo_parada);',
            '    }',
            '',
            '    // 5. Destruir las paradas viejas que NO se reutilizaron.',
            '    //    Las que se reutilizaron conservan su identidad (id()),',
            '    //    que es lo que importa para que los TerminalViaje que',
            '    //    apuntan a ellas sigan funcionando. Las que no se',
            '    //    reutilizaron quedaron fuera de la lista nueva y, si',
            '    //    no se destruyen, quedan huérfanas. Fase 2, v74u.',
            '    foreach ($nodos_existentes as $nombre_viejo => $nodo_viejo) {',
            '        if (!isset($paradas_normalizadas[$nombre_viejo])) {',
            '            _destruir_parada($nodo_viejo);',
            '        }',
            '    }',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ---- Fix 2: destruir el TerminalViaje al desautorizar ----

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reescribir eliminar_terminal_autorizada',
        'buscar' => [
            ' * Desenlaza el Nodo TerminalViaje del contenedor y limpia sus enlaces internos.',
            ' * El nodo intermedio queda huérfano (no se destruye), siguiendo el mismo',
            ' * criterio que eliminar_micro_de_viaje.',
            ' *',
            ' * @param string $nombre_viaje     Identificador del viaje.',
            ' * @param string $nombre_terminal  Nombre de usuario de la terminal.',
            ' * @param string $nombre_dueno     Nombre de usuario del dueño.',
            ' * @return array Resultado.',
            ' */',
            'function eliminar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $nodo_terminales = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '    if (!$nodo_terminales) return [\'exito\' => false, \'error\' => \'No hay terminales\'];',
            '',
            '    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal_viaje) {',
            '        return [\'exito\' => false, \'error\' => \'La terminal no está autorizada\'];',
            '    }',
            '',
            '    // Limpiar enlaces internos antes de desenlazar (evita dejar referencias colgando)',
            '    $nodo_terminal_viaje->eliminar_adyacente(\'punto_subida_bajada\');',
            '',
            '    $nodo_terminales->eliminar_adyacente($nombre_terminal);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            ' * A partir de v1.5piloto.74u (Fase 2 del plan de optimización',
            ' * del grafo): destruye el TerminalViaje completo (con sus',
            ' * campos: terminal, cambiar_punto_predeterminado, overrides',
            ' * de pago) en lugar de dejarlo huérfano. Reutiliza',
            ' * `_destruir_terminal_viaje` de Viaje.php (v74r).',
            ' *',
            ' * @param string $nombre_viaje     Identificador del viaje.',
            ' * @param string $nombre_terminal  Nombre de usuario de la terminal.',
            ' * @param string $nombre_dueno     Nombre de usuario del dueño.',
            ' * @return array Resultado.',
            ' */',
            'function eliminar_terminal_autorizada(string $nombre_viaje, string $nombre_terminal, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $nodo_terminales = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '    if (!$nodo_terminales) return [\'exito\' => false, \'error\' => \'No hay terminales\'];',
            '',
            '    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal_viaje) {',
            '        return [\'exito\' => false, \'error\' => \'La terminal no está autorizada\'];',
            '    }',
            '',
            '    // Fase 2: desenlazar del contenedor y destruir el TerminalViaje',
            '    // completo. _destruir_terminal_viaje se encarga de desenlazar',
            '    // `terminal` (referencia externa) y `punto_subida_bajada`',
            '    // (referencia a un nodo parada del viaje, que sigue vivo).',
            '    $nodo_terminales->eliminar_adyacente($nombre_terminal);',
            '    _destruir_terminal_viaje($nodo_terminal_viaje);',
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
        'descripcion' => 'Historial: agregar v74u',
        'buscar' => [
            '- **v74t**: fix del orden de destrucción en los helpers',
        ],
        'reemplazar' => [
            '- **v74u**: Fase 2, tercer y cuarto flujo arreglados.',
            '  `eliminar_terminal_autorizada` ahora destruye el',
            '  TerminalViaje (con sus campos) en lugar de dejarlo',
            '  huérfano. `_guardar_paradas_intermedias` ahora destruye',
            '  las paradas viejas que no se reutilizan al editar el',
            '  viaje. Ambos reutilizan helpers `_destruir_*` de v74r.',
            '- **v74t**: fix del orden de destrucción en los helpers',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujos 3 y 4',
        'buscar' => [
            '**Segundo flujo arreglado en v74s:** `eliminar_micro_de_viaje`.',
        ],
        'reemplazar' => [
            '**Tercer y cuarto flujo arreglados en v74u:**',
            '`eliminar_terminal_autorizada` ahora llama a',
            '`_destruir_terminal_viaje` después de desenlazar del',
            'contenedor. `_guardar_paradas_intermedias` ahora destruye',
            'las paradas viejas que no se reutilizan al editar el viaje',
            '(las que sí se reutilizan conservan su identidad, para no',
            'romper las referencias de los TerminalViaje que apuntan a',
            'ellas).',
            '',
            '**Segundo flujo arreglado en v74s:** `eliminar_micro_de_viaje`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74u',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74t',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74u',
            '(Fase 2, tercer y cuarto flujo arreglados:',
            '`eliminar_terminal_autorizada` y `_guardar_paradas_intermedias`).',
            'Antes: v1.5piloto.74t',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74u',
        'buscar' => [
            '- Cerramos en v74t el fix del orden de destrucción en',
        ],
        'reemplazar' => [
            '- Cerramos en v74u los flujos 3 y 4 de la Fase 2:',
            '  `eliminar_terminal_autorizada` ahora destruye el',
            '  TerminalViaje; `_guardar_paradas_intermedias` ahora',
            '  destruye las paradas viejas no reutilizadas.',
            '- Cerramos en v74t el fix del orden de destrucción en',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74u',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74t (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74u (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74u',
        'buscar' => [
            'v74t: fix del orden de destrucción en los helpers',
            '`_destruir_*` (desenlazar siempre antes de destruir).',
        ],
        'reemplazar' => [
            'v74t: fix del orden de destrucción en los helpers',
            '`_destruir_*` (desenlazar siempre antes de destruir).',
            'v74u: flujos 3 y 4 de Fase 2 (`eliminar_terminal_autorizada`',
            'y `_guardar_paradas_intermedias`).',
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