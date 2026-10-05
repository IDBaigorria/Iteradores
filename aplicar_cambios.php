<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74w:
 *   - Fase 2, sexto flujo arreglado: confirmar_venta_actual
 *     destruye el subárbol de la venta_actual (nodo venta_actual,
 *     cabeza de asientos-en-venta, cada asiento-en-venta, y los
 *     campos viaje y micro). Antes quedaban ~5 nodos huérfanos
 *     por cada venta confirmada.
 *   - Nuevo helper _destruir_venta_actual en Venta.php.
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
    // Venta.php: bump + reemplazo + helper
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Bump @version a 1.5piloto.74w',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74v',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74w',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Reemplazar desenlace de venta_actual por destrucción',
        'buscar' => [
            '    // Eliminar venta actual de la terminal',
            '    $nodo_terminal->eliminar_adyacente(\'venta_actual\');',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'id_venta\' => $id_venta,',
        ],
        'reemplazar' => [
            '    // Desenlazar la venta actual de la terminal y destruir',
            '    // su subárbol completo. Fase 2, v74w: antes solo se',
            '    // desenlazaba, dejando huérfanos el nodo venta_actual,',
            '    // la cabeza de la lista de asientos-en-venta, cada',
            '    // asiento-en-venta creado durante la selección, y los',
            '    // campos `viaje` y `micro` (~5 nodos por venta).',
            '    $nodo_terminal->eliminar_adyacente(\'venta_actual\');',
            '    _destruir_venta_actual($venta_actual);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'id_venta\' => $id_venta,',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Agregar helper _destruir_venta_actual antes de cancelar_venta',
        'buscar' => [
            '/**',
            ' * Cancela una venta, libera asientos, elimina cupones y',
            ' * asientos-en-venta persistentes, y revierte los montos en la',
            ' * terminal según el método de cada cupón pagado.',
            ' *',
            ' * @param string $id_venta',
            ' * @return array',
            ' */',
            'function cancelar_venta(string $id_venta, string $motivo = \'\'): array {',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye el subárbol de una venta actual (la que se arma',
            ' * en memoria mientras la terminal selecciona asientos).',
            ' *',
            ' * Estructura del nodo venta_actual:',
            ' *  - `terminal` → referencia externa al Nodo Usuario terminal.',
            ' *  - `viaje`, `micro` → campos string.',
            ' *  - `asientos` → cabeza de lista con `primer` → asiento-en-venta.',
            ' *  - (fallback) `primer` directo, si algún flujo antiguo lo usó.',
            ' *',
            ' * Cada asiento-en-venta tiene `asiento` (referencia al asiento',
            ' * real del micro) y `siguiente` (para el próximo nodo de la lista).',
            ' * El asiento real no se destruye: es del micro.',
            ' *',
            ' * Aplica Fase 2 del plan de optimización del grafo. Se llama',
            ' * desde confirmar_venta_actual después de desenlazar la',
            ' * venta_actual de la terminal.',
            ' *',
            ' * @param Nodo $nodo_venta_actual',
            ' * @return void',
            ' */',
            'function _destruir_venta_actual(Nodo $nodo_venta_actual): void {',
            '    // 1. Asientos-en-venta colgando de `asientos` (cabeza).',
            '    $cabeza = $nodo_venta_actual->adyacente(\'asientos\');',
            '    if ($cabeza) {',
            '        $asientos_venta = [];',
            '        $actual = $cabeza->adyacente(\'primer\');',
            '        $seg = 0;',
            '        while ($actual && $seg < 200) {',
            '            $asientos_venta[] = $actual;',
            '            $actual = $actual->adyacente(\'siguiente\');',
            '            $seg++;',
            '        }',
            '        // Desenlazar la lista.',
            '        foreach ($asientos_venta as $av) {',
            '            $av->eliminar_adyacente(\'siguiente\');',
            '        }',
            '        $cabeza->eliminar_adyacente(\'primer\');',
            '        $nodo_venta_actual->eliminar_adyacente(\'asientos\');',
            '',
            '        // Destruir cada asiento-en-venta con sus campos. El',
            '        // `asiento` es una referencia al asiento real del micro:',
            '        // solo se desenlaza.',
            '        foreach ($asientos_venta as $av) {',
            '            _destruir_campos_simples($av, [\'asiento\']);',
            '            $av->eliminar_adyacente(\'asiento\');',
            '            Nodo::eliminar($av);',
            '        }',
            '',
            '        // Destruir la cabeza.',
            '        _destruir_campos_simples($cabeza);',
            '        Nodo::eliminar($cabeza);',
            '    }',
            '',
            '    // 2. Fallback: `primer` directo en el venta_actual (por si',
            '    //    algún flujo viejo lo creó así).',
            '    $primer_directo = $nodo_venta_actual->adyacente(\'primer\');',
            '    if ($primer_directo) {',
            '        $nodo_venta_actual->eliminar_adyacente(\'primer\');',
            '        _destruir_campos_simples($primer_directo, [\'asiento\']);',
            '        $primer_directo->eliminar_adyacente(\'asiento\');',
            '        Nodo::eliminar($primer_directo);',
            '    }',
            '',
            '    // 3. Desenlazar referencias externas.',
            '    $nodo_venta_actual->eliminar_adyacente(\'terminal\');',
            '',
            '    // 4. Destruir los campos simples del propio venta_actual',
            '    //    (viaje, micro).',
            '    _destruir_campos_simples($nodo_venta_actual);',
            '',
            '    // 5. Destruir el nodo venta_actual.',
            '    Nodo::eliminar($nodo_venta_actual);',
            '}',
            '',
            '/**',
            ' * Cancela una venta, libera asientos, elimina cupones y',
            ' * asientos-en-venta persistentes, y revierte los montos en la',
            ' * terminal según el método de cada cupón pagado.',
            ' *',
            ' * @param string $id_venta',
            ' * @return array',
            ' */',
            'function cancelar_venta(string $id_venta, string $motivo = \'\'): array {',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74w',
        'buscar' => [
            '- **v74v**: Fase 2, quinto flujo arreglado:',
        ],
        'reemplazar' => [
            '- **v74w**: Fase 2, sexto flujo arreglado:',
            '  `confirmar_venta_actual`. Antes desenlazaba la venta',
            '  actual de la terminal pero no destruía su subárbol,',
            '  dejando ~5 nodos huérfanos por venta confirmada (nodo',
            '  venta_actual, cabeza de asientos-en-venta, cada',
            '  asiento-en-venta, y los campos viaje y micro). Nuevo',
            '  helper `_destruir_venta_actual` en `Venta.php`. Bug',
            '  detectado por la prueba espejo',
            '  `cancelar_venta_limpia_nodos` del plugin: crear la',
            '  venta incrementaba el contador de huérfanos en 5.',
            '- **v74v**: Fase 2, quinto flujo arreglado:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar sexto flujo + nota de otros dos pendientes',
        'buscar' => [
            '**Quinto flujo arreglado en v74v:** `cancelar_venta`.',
        ],
        'reemplazar' => [
            '**Sexto flujo arreglado en v74w:** `confirmar_venta_actual`.',
            'Antes desenlazaba la venta_actual de la terminal pero no',
            'la destruía: quedaban huérfanos el propio nodo, la cabeza',
            'de la lista de asientos-en-venta, cada asiento-en-venta',
            'creado durante la selección, y los campos `viaje` y',
            '`micro` (~5 nodos por venta). Nuevo helper',
            '`_destruir_venta_actual`. Bug detectado por la prueba',
            '`cancelar_venta_limpia_nodos` del plugin, que al crear',
            'la venta vio un incremento de huérfanos de 5.',
            '',
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
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74w',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74v',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74w',
            '(Fase 2, sexto flujo arreglado: `confirmar_venta_actual`.',
            'Antes desenlazaba la venta_actual sin destruir su subárbol,',
            'dejando ~5 nodos huérfanos por venta. Nuevo helper',
            '`_destruir_venta_actual`. Bug detectado por la prueba',
            '`cancelar_venta_limpia_nodos` del plugin.).',
            'Antes: v1.5piloto.74v',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74w',
        'buscar' => [
            '- Cerramos en v74v el quinto flujo de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74w el sexto flujo de Fase 2:',
            '  `confirmar_venta_actual`. Ahora destruye el subárbol',
            '  de la venta_actual después de desenlazarla de la',
            '  terminal. Bug detectado por la prueba espejo',
            '  `cancelar_venta_limpia_nodos` (crear la venta',
            '  incrementaba el contador de huérfanos en 5).',
            '  Pendientes dos fugas menores en `deseleccionar_',
            '  asiento_micro` y `seleccionar_asiento_micro`.',
            '- Cerramos en v74v el quinto flujo de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74w',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74v (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74w (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74w',
        'buscar' => [
            'v74v: flujo 5',
            '(`cancelar_venta`).',
        ],
        'reemplazar' => [
            'v74v: flujo 5',
            '(`cancelar_venta`). v74w: flujo 6',
            '(`confirmar_venta_actual`).',
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