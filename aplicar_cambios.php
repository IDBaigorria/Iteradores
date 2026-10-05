<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74s:
 *   - Fase 2 del plan de optimización del grafo, segundo flujo:
 *     eliminar_micro_de_viaje destruye el micro completo
 *     reutilizando _destruir_micro.
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
    // ViajeMicros.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'Bump @version a 1.5piloto.74s',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74k',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74s',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'Reescribir eliminar_micro_de_viaje',
        'buscar' => [
            '/**',
            ' * Elimina un micro de un viaje.',
            ' */',
            'function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if (!$nodo_micros) return [\'exito\' => false, \'error\' => \'No hay micros\'];',
            '',
            '    $nodo_micro = $nodo_micros->adyacente($nombre_micro);',
            '    if (!$nodo_micro) return [\'exito\' => false, \'error\' => \'Micro no encontrado\'];',
            '',
            '    $nodo_micros->eliminar_adyacente($nombre_micro);',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Elimina un micro de un viaje.',
            ' *',
            ' * A partir de v1.5piloto.74s (Fase 2 del plan de optimización',
            ' * del grafo): destruye el micro completo (copia de vehículo,',
            ' * pisos, asientos, campos) en lugar de solo desenlazarlo del',
            ' * contenedor. Reutiliza `_destruir_micro` de `Viaje.php`',
            ' * (agregada en v74r). Antes de esta versión, cada eliminación',
            ' * de un micro dejaba ~100 nodos huérfanos.',
            ' *',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_micro',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' */',
            'function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
            '',
            '    $nodo_micros = $nodo_viaje->adyacente(\'micros\');',
            '    if (!$nodo_micros) return [\'exito\' => false, \'error\' => \'No hay micros\'];',
            '',
            '    $nodo_micro = $nodo_micros->adyacente($nombre_micro);',
            '    if (!$nodo_micro) return [\'exito\' => false, \'error\' => \'Micro no encontrado\'];',
            '',
            '    // Fase 2: destruir el micro completo. Primero desenlazar',
            '    // del contenedor del viaje, después destruir el subárbol',
            '    // (copia de vehículo, pisos, asientos, campos).',
            '    $nodo_micros->eliminar_adyacente($nombre_micro);',
            '    _destruir_micro($nodo_micro);',
            '',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
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
        'descripcion' => 'Historial: agregar v74s antes de v74r',
        'buscar' => [
            '- **v74r**: Fase 2 del plan de optimización del grafo,',
            '  primer flujo arreglado. `eliminar_viaje` ahora destruye',
        ],
        'reemplazar' => [
            '- **v74s**: Fase 2, segundo flujo arreglado.',
            '  `eliminar_micro_de_viaje` ahora destruye el micro',
            '  completo (copia de vehículo, pisos, asientos, campos)',
            '  en lugar de solo desenlazarlo del contenedor del viaje.',
            '  Reutiliza `_destruir_micro` de `Viaje.php` (agregada en',
            '  v74r). Libera ~100 nodos por micro.',
            '- **v74r**: Fase 2 del plan de optimización del grafo,',
            '  primer flujo arreglado. `eliminar_viaje` ahora destruye',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar segundo flujo arreglado',
        'buscar' => [
            '**Fase 2 — Auditoría de la fuga de nodos (en curso, prioridad alta).**',
            '',
            '**Primer flujo arreglado en v74r:** `eliminar_viaje`. Antes',
        ],
        'reemplazar' => [
            '**Fase 2 — Auditoría de la fuga de nodos (en curso, prioridad alta).**',
            '',
            '**Segundo flujo arreglado en v74s:** `eliminar_micro_de_viaje`.',
            'Antes solo desenlazaba el micro del contenedor `micros` del',
            'viaje. Ahora llama a `_destruir_micro` (helper de `Viaje.php`',
            'agregado en v74r), que destruye la copia del vehículo (con',
            'sus pisos y asientos), los campos del micro, y desenlaza las',
            'referencias externas (empresa) y circular (viaje). Libera',
            '~100 nodos por micro.',
            '',
            '**Primer flujo arreglado en v74r:** `eliminar_viaje`. Antes',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74s',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74r',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74s',
            '(Fase 2, segundo flujo arreglado: `eliminar_micro_de_viaje`.',
            'Ahora destruye el micro completo (copia de vehículo, pisos,',
            'asientos, campos) en lugar de solo desenlazarlo del contenedor',
            'del viaje. Reutiliza `_destruir_micro` de `Viaje.php`. Libera',
            '~100 nodos por micro).',
            'Antes: v1.5piloto.74r',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74s',
        'buscar' => [
            '- Cerramos en v74r el primer flujo de Fase 2: `eliminar_viaje`',
        ],
        'reemplazar' => [
            '- Cerramos en v74s el segundo flujo de Fase 2:',
            '  `eliminar_micro_de_viaje`. Ahora destruye el micro',
            '  completo (copia de vehículo, pisos, asientos, campos)',
            '  en lugar de solo desenlazarlo del contenedor. Reutiliza',
            '  `_destruir_micro` de `Viaje.php`. Libera ~100 nodos',
            '  por micro.',
            '- Cerramos en v74r el primer flujo de Fase 2: `eliminar_viaje`',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74s',
        'buscar' => [
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
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74s (framework 1.5i.7g).',
            'Todo funcional. Fixes de v74k a v74o acumulados. Fix de',
            'v74p: pestaña "Grafo" (Fase 1 del plan de optimización).',
            'v74r: `eliminar_viaje` destruye el subárbol completo',
            '(Fase 2, primer flujo). v74s: `eliminar_micro_de_viaje`',
            'destruye el micro completo (Fase 2, segundo flujo).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5g)',
            'tiene 31 pruebas corriendo.',
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