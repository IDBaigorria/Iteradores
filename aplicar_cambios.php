<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74x:
 *   - Fase 2, séptimo y octavo flujo arreglados:
 *     * seleccionar_asiento_micro destruye los asientos-en-venta
 *       viejos cuando cambia de micro a mitad de selección
 *       (limpiar_lista = true).
 *     * deseleccionar_asiento_micro destruye el asiento-en-venta
 *       del asiento que se deselecciona (que antes quedaba huérfano
 *       al filtrarse fuera de la lista).
 *   - Nuevo helper _destruir_asiento_en_venta en Venta.php.
 *     _destruir_venta_actual lo reutiliza.
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
    // Venta.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Bump @version a 1.5piloto.74x',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74w',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74x',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Agregar helper _destruir_asiento_en_venta antes de _destruir_venta_actual',
        'buscar' => [
            '/**',
            ' * Destruye el subárbol de una venta actual (la que se arma',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye un nodo asiento-en-venta y sus campos hoja.',
            ' *',
            ' * Un nodo asiento-en-venta tiene:',
            ' *  - `asiento` → referencia externa al asiento real del micro.',
            ' *  - `punto_subida_bajada`, `hora_subida_bajada` → campos string',
            ' *    opcionales.',
            ' *  - `siguiente` → próximo nodo de la lista (debe estar ya',
            ' *    desenlazado por el llamador).',
            ' *',
            ' * La referencia a `asiento` solo se desenlaza (el asiento real',
            ' * es del micro). Los campos hoja se destruyen con',
            ' * _destruir_campos_simples.',
            ' *',
            ' * Se usa desde tres lugares:',
            ' *  - _destruir_venta_actual (Venta.php), al destruir el',
            ' *    subárbol de la venta_actual tras confirmarla.',
            ' *  - seleccionar_asiento_micro (ViajeAsientos.php), al',
            ' *    limpiar la lista al cambiar de micro.',
            ' *  - deseleccionar_asiento_micro (ViajeAsientos.php), al',
            ' *    filtrar el nodo del asiento deseleccionado.',
            ' *',
            ' * @param Nodo $nodo_asiento_venta',
            ' * @return void',
            ' */',
            'function _destruir_asiento_en_venta(Nodo $nodo_asiento_venta): void {',
            '    _destruir_campos_simples($nodo_asiento_venta, [\'asiento\']);',
            '    $nodo_asiento_venta->eliminar_adyacente(\'asiento\');',
            '    Nodo::eliminar($nodo_asiento_venta);',
            '}',
            '',
            '/**',
            ' * Destruye el subárbol de una venta actual (la que se arma',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Refactorizar _destruir_venta_actual para usar el helper',
        'buscar' => [
            '        // Destruir cada asiento-en-venta con sus campos. El',
            '        // `asiento` es una referencia al asiento real del micro:',
            '        // solo se desenlaza.',
            '        foreach ($asientos_venta as $av) {',
            '            _destruir_campos_simples($av, [\'asiento\']);',
            '            $av->eliminar_adyacente(\'asiento\');',
            '            Nodo::eliminar($av);',
            '        }',
        ],
        'reemplazar' => [
            '        // Destruir cada asiento-en-venta con sus campos.',
            '        foreach ($asientos_venta as $av) {',
            '            _destruir_asiento_en_venta($av);',
            '        }',
        ],
    ],

    // --------------------------------------------------------
    // ViajeAsientos.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Bump @version a 1.5piloto.74x',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74x',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Fix limpiar_lista en seleccionar_asiento_micro',
        'buscar' => [
            '    $nodos_venta = [];',
            '    if (!$limpiar_lista && $cabeza_venta) {',
            '        $actual_venta = $cabeza_venta->adyacente(\'primer\');',
            '        while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {',
            '            $nodos_venta[] = $actual_venta;',
            '            $actual_venta = $actual_venta->adyacente(\'siguiente\');',
            '        }',
            '    } else {',
            '        if ($cabeza_venta) {',
            '            $cabeza_venta->eliminar_adyacente(\'primer\');',
            '        }',
            '    }',
            '',
            '    foreach ($nodos_venta as $nodo_venta) {',
            '        $nodo_venta->eliminar_adyacente(\'siguiente\');',
            '    }',
        ],
        'reemplazar' => [
            '    $nodos_venta = [];',
            '    if (!$limpiar_lista && $cabeza_venta) {',
            '        $actual_venta = $cabeza_venta->adyacente(\'primer\');',
            '        while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id()) {',
            '            $nodos_venta[] = $actual_venta;',
            '            $actual_venta = $actual_venta->adyacente(\'siguiente\');',
            '        }',
            '    } else {',
            '        // Cambio de micro a mitad de selección: hay que',
            '        // destruir los asientos-en-venta viejos. Fase 2, v74x:',
            '        // antes solo se desenlazaba el `primer` y los nodos',
            '        // quedaban huérfanos con sus campos.',
            '        if ($cabeza_venta) {',
            '            $viejos = [];',
            '            $actual_venta = $cabeza_venta->adyacente(\'primer\');',
            '            $seg_viejos = 0;',
            '            while ($actual_venta && $actual_venta->id() !== $cabeza_venta->id() && $seg_viejos < 200) {',
            '                $viejos[] = $actual_venta;',
            '                $actual_venta = $actual_venta->adyacente(\'siguiente\');',
            '                $seg_viejos++;',
            '            }',
            '            $cabeza_venta->eliminar_adyacente(\'primer\');',
            '            foreach ($viejos as $av_viejo) {',
            '                $av_viejo->eliminar_adyacente(\'siguiente\');',
            '                _destruir_asiento_en_venta($av_viejo);',
            '            }',
            '        }',
            '    }',
            '',
            '    foreach ($nodos_venta as $nodo_venta) {',
            '        $nodo_venta->eliminar_adyacente(\'siguiente\');',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Fix filtrado en deseleccionar_asiento_micro',
        'buscar' => [
            '                $nodos_filtrados = [];',
            '                foreach ($nodos_venta as $nodo_venta) {',
            '                    $asiento_ref = $nodo_venta->adyacente(\'asiento\');',
            '                    if ($asiento_ref && $asiento_ref->id() !== $nodo_asiento->id()) {',
            '                        $nodos_filtrados[] = $nodo_venta;',
            '                    }',
            '                }',
        ],
        'reemplazar' => [
            '                $nodos_filtrados = [];',
            '                $nodos_descartados = [];',
            '                foreach ($nodos_venta as $nodo_venta) {',
            '                    $asiento_ref = $nodo_venta->adyacente(\'asiento\');',
            '                    if ($asiento_ref && $asiento_ref->id() !== $nodo_asiento->id()) {',
            '                        $nodos_filtrados[] = $nodo_venta;',
            '                    } else {',
            '                        $nodos_descartados[] = $nodo_venta;',
            '                    }',
            '                }',
            '',
            '                // Destruir los asientos-en-venta que se descartan',
            '                // (el que corresponde al asiento deseleccionado).',
            '                // Fase 2, v74x: antes se filtraba de la lista sin',
            '                // destruirlo, dejando el nodo huérfano con sus',
            '                // campos (punto_subida_bajada, hora_subida_bajada).',
            '                foreach ($nodos_descartados as $av_desc) {',
            '                    _destruir_asiento_en_venta($av_desc);',
            '                }',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74x',
        'buscar' => [
            '- **v74w**: Fase 2, sexto flujo arreglado:',
        ],
        'reemplazar' => [
            '- **v74x**: Fase 2, séptimo y octavo flujo arreglados:',
            '  `seleccionar_asiento_micro` (destruye los asientos-en-venta',
            '  viejos al cambiar de micro a mitad de selección, cuando',
            '  `limpiar_lista = true`) y `deseleccionar_asiento_micro`',
            '  (destruye el asiento-en-venta del asiento que se',
            '  deselecciona, que antes quedaba huérfano al filtrarse',
            '  fuera de la lista). Nuevo helper',
            '  `_destruir_asiento_en_venta` en `Venta.php`, reutilizado',
            '  por `_destruir_venta_actual` y por los dos flujos de',
            '  `ViajeAsientos.php`.',
            '- **v74w**: Fase 2, sexto flujo arreglado:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujos 7 y 8, retirar la nota de pendientes',
        'buscar' => [
            '**Otras dos fugas detectadas en la misma auditoría**',
            '(pendientes de tanda):',
            '',
            '- `deseleccionar_asiento_micro` (en `ViajeAsientos.php`):',
            '  al filtrar la lista de asientos-en-venta de la venta',
            '  actual, el nodo asiento-en-venta deseleccionado queda',
            '  huérfano. Fuga: 1 nodo por deselección.',
            '- `seleccionar_asiento_micro` (en `ViajeAsientos.php`):',
            '  cuando `limpiar_lista` es true (cambio de micro a',
            '  mitad de selección), los asientos-en-venta viejos',
            '  quedan huérfanos. Fuga: N nodos por cambio de micro',
            '  a mitad de selección.',
            '',
            '**Quinto flujo arreglado en v74v:** `cancelar_venta`.',
        ],
        'reemplazar' => [
            '**Séptimo y octavo flujo arreglados en v74x:**',
            '`seleccionar_asiento_micro` (bloque `limpiar_lista = true`:',
            'antes solo desenlazaba el `primer` de la cabeza, dejando',
            'huérfanos los asientos-en-venta viejos con sus campos) y',
            '`deseleccionar_asiento_micro` (antes filtraba el nodo',
            'asiento-en-venta del asiento deseleccionado sin',
            'destruirlo). Ambos reutilizan el helper nuevo',
            '`_destruir_asiento_en_venta` de `Venta.php`.',
            '',
            '**Quinto flujo arreglado en v74v:** `cancelar_venta`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74x',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74w',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74x',
            '(Fase 2, séptimo y octavo flujo arreglados:',
            '`seleccionar_asiento_micro` (cambio de micro a mitad de',
            'selección) y `deseleccionar_asiento_micro`. Nuevo helper',
            '`_destruir_asiento_en_venta` en `Venta.php`.).',
            'Antes: v1.5piloto.74w',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74x',
        'buscar' => [
            '- Cerramos en v74w el sexto flujo de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74x los flujos 7 y 8 de Fase 2:',
            '  `seleccionar_asiento_micro` (bloque de cambio de micro)',
            '  y `deseleccionar_asiento_micro`. Nuevo helper',
            '  `_destruir_asiento_en_venta` en `Venta.php`,',
            '  reutilizado por `_destruir_venta_actual` y por los',
            '  dos flujos de `ViajeAsientos.php`.',
            '- Cerramos en v74w el sexto flujo de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74x',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74w (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74x (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74x',
        'buscar' => [
            'v74w: flujo 6',
            '(`confirmar_venta_actual`).',
        ],
        'reemplazar' => [
            'v74w: flujo 6',
            '(`confirmar_venta_actual`). v74x: flujos 7 y 8',
            '(`seleccionar_asiento_micro` con `limpiar_lista`,',
            'y `deseleccionar_asiento_micro`).',
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