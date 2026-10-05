<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74z:
 *   - Fase 2, duodécimo flujo arreglado: limpiar_viajes_de_prueba
 *     destruye el subárbol completo de cada viaje antes de
 *     desenlazarlo. Antes solo desenlazaba, dejando ~250 nodos
 *     huérfanos por viaje con micros. La herramienta de limpieza
 *     era en sí misma una fuente de fuga.
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
        'descripcion' => 'Bump @version a 1.5piloto.74z',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74y',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74z',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Fix limpiar_viajes_de_prueba: destruir subárbol antes',
        'buscar' => [
            '        $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '        $borrados[] = $nombre_viaje;',
            '    }',
        ],
        'reemplazar' => [
            '        // Fase 2, v74z: destruir el subárbol completo del viaje',
            '        // antes de desenlazarlo. Antes solo se desenlazaba,',
            '        // dejando el mismo subárbol huérfano que eliminar_viaje',
            '        // antes de v74r (~250 nodos por micro arrastrado).',
            '        _destruir_viaje_completo($nodo_viaje);',
            '        $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '        Nodo::eliminar($nodo_viaje);',
            '        $borrados[] = $nombre_viaje;',
            '    }',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74z',
        'buscar' => [
            '- **v74y**: Fase 2, noveno, décimo y undécimo flujo',
        ],
        'reemplazar' => [
            '- **v74z**: Fase 2, duodécimo flujo arreglado:',
            '  `limpiar_viajes_de_prueba`. Antes desenlazaba los',
            '  viajes de prueba del contenedor del dueño sin',
            '  destruirlos, dejando el mismo subárbol huérfano que',
            '  `eliminar_viaje` antes de v74r (~250 nodos por',
            '  micro). Ahora llama a `_destruir_viaje_completo`',
            '  antes de desenlazarlos. Cierra la deuda de que la',
            '  herramienta de limpieza era en sí misma una fuente',
            '  de fuga. Detectado por el detector ampliado de fugas.',
            '- **v74y**: Fase 2, noveno, décimo y undécimo flujo',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujo 12',
        'buscar' => [
            '**Noveno, décimo y undécimo flujo arreglados en v74y:**',
        ],
        'reemplazar' => [
            '**Duodécimo flujo arreglado en v74z:**',
            '`limpiar_viajes_de_prueba`. Antes desenlazaba los viajes',
            'de prueba del contenedor del dueño sin destruirlos:',
            'quedaba el mismo subárbol huérfano que `eliminar_viaje`',
            'antes de v74r (~250 nodos por micro arrastrado). Ahora',
            'llama a `_destruir_viaje_completo` antes de desenlazar',
            'cada viaje. La herramienta de limpieza era en sí misma',
            'una fuente de fuga.',
            '',
            '**Noveno, décimo y undécimo flujo arreglados en v74y:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74z',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74y',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74z',
            '(Fase 2, flujo 12: `limpiar_viajes_de_prueba`. Ahora',
            'destruye el subárbol completo de cada viaje antes de',
            'desenlazarlo. La herramienta de limpieza era en sí misma',
            'una fuente de fuga.).',
            'Antes: v1.5piloto.74y',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74z',
        'buscar' => [
            '- Cerramos en v74y los flujos 9, 10 y 11 de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74z el flujo 12 de Fase 2:',
            '  `limpiar_viajes_de_prueba`. Ahora destruye el subárbol',
            '  completo de cada viaje antes de desenlazarlo.',
            '  Reutiliza `_destruir_viaje_completo` de v74r.',
            '- Cerramos en v74y los flujos 9, 10 y 11 de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74z',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74y (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74z (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74z',
        'buscar' => [
            'v74y: flujos 9, 10',
            'y 11 (`actualizar_configuracion_vehiculo`,',
            '`eliminar_vehiculo`, `eliminar_empresa`).',
        ],
        'reemplazar' => [
            'v74y: flujos 9, 10',
            'y 11 (`actualizar_configuracion_vehiculo`,',
            '`eliminar_vehiculo`, `eliminar_empresa`).',
            'v74z: flujo 12 (`limpiar_viajes_de_prueba`).',
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