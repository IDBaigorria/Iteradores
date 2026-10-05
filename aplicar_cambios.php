<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.75a:
 *   - Fase 2, cierre de los "campos huérfanos" del Grupo B.
 *     Cinco fixes chicos: hojas que se desenlazaban sin
 *     destruirlas.
 *     * Autenticacion.php: bloqueado_hasta al expirar el bloqueo
 *       y al registrar login exitoso.
 *     * Venta.php: metodo_pago del cupón al coincidir con el de
 *       la venta.
 *     * Vehiculo.php: foto al reemplazarla.
 *     * Viaje.php: hora_estimada al quitar la hora de una parada.
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
    // Autenticacion.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Bump @version a 1.5piloto.75a',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.73m',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.75a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Fix bloqueo expirado: destruir bloqueado_hasta',
        'buscar' => [
            '    // El bloqueo expiró: limpiar y resetear intentos.',
            '    $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
        ],
        'reemplazar' => [
            '    // El bloqueo expiró: limpiar y resetear intentos.',
            '    // Fase 2, v75a: destruir la hoja `bloqueado_hasta` en',
            '    // lugar de solo desenlazarla.',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if ($nodo_bloqueo) {',
            '        $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '        Nodo::eliminar($nodo_bloqueo);',
            '    }',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Fix login exitoso: destruir bloqueado_hasta',
        'buscar' => [
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '    $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '',
            '    // Auditoría.',
        ],
        'reemplazar' => [
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '    // Fase 2, v75a: destruir la hoja `bloqueado_hasta` en',
            '    // lugar de solo desenlazarla.',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if ($nodo_bloqueo) {',
            '        $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '        Nodo::eliminar($nodo_bloqueo);',
            '    }',
            '',
            '    // Auditoría.',
        ],
    ],

    // --------------------------------------------------------
    // Venta.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Bump @version a 1.5piloto.75a',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74x',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.75a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Fix pagar_cupon: destruir metodo_pago del cupón',
        'buscar' => [
            '    } else {',
            '        $cupon_objetivo->eliminar_adyacente(\'metodo_pago\');',
            '    }',
            '',
            '    // Actualizar pagado de la venta.',
        ],
        'reemplazar' => [
            '    } else {',
            '        // Fase 2, v75a: destruir la hoja `metodo_pago` del',
            '        // cupón en lugar de solo desenlazarla. Se ejecuta',
            '        // cuando el método del pago coincide con el de la',
            '        // venta y ya no hace falta guardar el override.',
            '        $nodo_metodo_viejo = $cupon_objetivo->adyacente(\'metodo_pago\');',
            '        if ($nodo_metodo_viejo) {',
            '            $cupon_objetivo->eliminar_adyacente(\'metodo_pago\');',
            '            Nodo::eliminar($nodo_metodo_viejo);',
            '        }',
            '    }',
            '',
            '    // Actualizar pagado de la venta.',
        ],
    ],

    // --------------------------------------------------------
    // Vehiculo.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Bump @version a 1.5piloto.75a',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.74y',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.75a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Fix subir_foto: destruir hoja foto al reemplazar',
        'buscar' => [
            '        // Eliminar foto anterior si existe',
            '        $foto_anterior = $vehiculo_encontrado->adyacente(\'foto\');',
            '        if ($foto_anterior) {',
            '            $ruta_anterior = __DIR__ . \'/../../\' . $foto_anterior->dato();',
            '            if (file_exists($ruta_anterior)) {',
            '                unlink($ruta_anterior);',
            '            }',
            '            $vehiculo_encontrado->eliminar_adyacente(\'foto\');',
            '        }',
        ],
        'reemplazar' => [
            '        // Eliminar foto anterior si existe',
            '        $foto_anterior = $vehiculo_encontrado->adyacente(\'foto\');',
            '        if ($foto_anterior) {',
            '            $ruta_anterior = __DIR__ . \'/../../\' . $foto_anterior->dato();',
            '            if (file_exists($ruta_anterior)) {',
            '                unlink($ruta_anterior);',
            '            }',
            '            // Fase 2, v75a: destruir la hoja `foto` en lugar',
            '            // de solo desenlazarla.',
            '            $vehiculo_encontrado->eliminar_adyacente(\'foto\');',
            '            Nodo::eliminar($foto_anterior);',
            '        }',
        ],
    ],

    // --------------------------------------------------------
    // Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.75a',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74z',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.75a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Fix paradas: destruir hoja hora_estimada al quitarla',
        'buscar' => [
            '            // Actualizar la hora estimada del nodo reutilizado',
            '            $nodo_hora = $nodo_parada->adyacente(\'hora_estimada\');',
            '            if ($hora === \'\') {',
            '                if ($nodo_hora) $nodo_parada->eliminar_adyacente(\'hora_estimada\');',
            '            } else {',
        ],
        'reemplazar' => [
            '            // Actualizar la hora estimada del nodo reutilizado',
            '            $nodo_hora = $nodo_parada->adyacente(\'hora_estimada\');',
            '            if ($hora === \'\') {',
            '                // Fase 2, v75a: destruir la hoja `hora_estimada`',
            '                // en lugar de solo desenlazarla.',
            '                if ($nodo_hora) {',
            '                    $nodo_parada->eliminar_adyacente(\'hora_estimada\');',
            '                    Nodo::eliminar($nodo_hora);',
            '                }',
            '            } else {',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v75a antes de v75',
        'buscar' => [
            '- **v75**: Fase 2, flujos 13 y 14 arreglados.',
        ],
        'reemplazar' => [
            '- **v75a**: Fase 2, cierre de los "campos huérfanos" del',
            '  Grupo B. Cinco fixes chicos: hojas que se desenlazaban',
            '  sin destruirlas. `Autenticacion.php`: `bloqueado_hasta`',
            '  al expirar el bloqueo y al registrar login exitoso.',
            '  `Venta.php`: `metodo_pago` del cupón al coincidir con el',
            '  de la venta. `Vehiculo.php`: `foto` al reemplazarla.',
            '  `Viaje.php`: `hora_estimada` al quitar la hora de una',
            '  parada. El `punto_subida_bajada` del TerminalViaje',
            '  (Viaje.php:940) queda descartado: es una referencia',
            '  externa al nodo parada del viaje, correcto por diseño.',
            '- **v75**: Fase 2, flujos 13 y 14 arreglados.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar cierre del Grupo B',
        'buscar' => [
            '**Decimotercer y decimocuarto flujo arreglados en v75:**',
        ],
        'reemplazar' => [
            '**Cierre de campos huérfanos (Grupo B) en v75a:**',
            'cinco fixes chicos, hojas que se desenlazaban sin',
            'destruirlas:',
            '',
            '- `Autenticacion.php` (`_esta_bloqueado` y',
            '  `_registrar_login_exitoso`): `bloqueado_hasta`.',
            '- `Venta.php` (`pagar_cupon_venta`): `metodo_pago` del',
            '  cupón, cuando coincide con el de la venta.',
            '- `Vehiculo.php` (`subir_foto_vehiculo`): `foto`, al',
            '  reemplazar la foto anterior.',
            '- `Viaje.php` (`_guardar_paradas_intermedias`):',
            '  `hora_estimada`, al quitar la hora de una parada.',
            '',
            'El `punto_subida_bajada` del TerminalViaje',
            '(`Viaje.php:940`) queda **descartado**: es una referencia',
            'externa al nodo parada del viaje, que sigue vivo en',
            '`paradas_intermedias`. Correcto por diseño.',
            '',
            '**Decimotercer y decimocuarto flujo arreglados en v75:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v75a',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76',
            '(Fase 2, flujos 15 a 19: `eliminar_pasajero`,',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.75a',
            '(cierre de los "campos huérfanos" del Grupo B: cinco',
            'fixes chicos en `Autenticacion.php`, `Venta.php`,',
            '`Vehiculo.php` y `Viaje.php`. El `punto_subida_bajada`',
            'del TerminalViaje queda descartado por diseño.).',
            'Antes: v1.5piloto.76',
            '(Fase 2, flujos 15 a 19: `eliminar_pasajero`,',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v75a',
        'buscar' => [
            '- Cerramos en v75 los flujos 13 y 14 de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v75a el Grupo B de Fase 2 (campos',
            '  huérfanos): `bloqueado_hasta` (Autenticacion.php, 2',
            '  usos), `metodo_pago` del cupón (Venta.php), `foto`',
            '  del vehículo (Vehiculo.php), `hora_estimada` de la',
            '  parada (Viaje.php). El `punto_subida_bajada` del',
            '  TerminalViaje queda descartado (referencia externa).',
            '- Cerramos en v75 los flujos 13 y 14 de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v75a',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76 (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.75a (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v75a entre v75 y v76',
        'buscar' => [
            'v75: flujos 13 y 14 (`eliminar_usuario`,',
            '`actualizar_usuario`) y `Sesion.php`.',
            'v76: flujos 15 a 19 (pasajeros y declaraciones',
            'juradas adjuntas).',
        ],
        'reemplazar' => [
            'v75: flujos 13 y 14 (`eliminar_usuario`,',
            '`actualizar_usuario`) y `Sesion.php`.',
            'v75a: cierre del Grupo B (campos huérfanos).',
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