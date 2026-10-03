<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74f — cerrar el modal del viaje al cambiar de pestaña.
 *
 * Corrige el path de `aplicacion.js` (raíz del proyecto, no en
 * Aplicacion/).
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

    // ============================================================
    // aplicacion.js (raíz del proyecto)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump a 1.5piloto.74f',
        'buscar' => [
            ' * @version 1.5piloto.73i',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.74f',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: cerrar modal del viaje al cambiar de pestaña',
        'buscar' => [
            'function activar_pestana(id_pestana) {',
            "    if (id_pestana !== 'viajes') {",
            '        ocultar_detalle_viaje();',
            '    }',
        ],
        'reemplazar' => [
            'function activar_pestana(id_pestana) {',
            "    if (id_pestana !== 'viajes') {",
            '        // Si hay un modal abierto con el detalle del viaje, cerrarlo',
            '        // antes de cambiar de pestaña, para no dejar el croquis',
            '        // congelado en pantalla con datos viejos.',
            "        const modal = document.getElementById('modal_generico');",
            "        const modal_abierto = modal && !modal.classList.contains('hidden');",
            "        const es_detalle_viaje = modal_abierto && document.getElementById('lista_micros_viaje');",
            '        if (es_detalle_viaje) {',
            '            cerrar_modal_generico();',
            '        }',
            '        ocultar_detalle_viaje();',
            '    }',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump del ?v= de aplicacion.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump del ?v= de aplicacion.js a 74f',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.73i"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.74f"></script>',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74f',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74e (fix de',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74f (cerrar',
            'el modal del viaje al cambiar de pestaña. Si el usuario o una',
            'prueba automática cambia de pestaña con el modal del detalle',
            'del viaje abierto, `ocultar_detalle_viaje` mataba el polling',
            'pero dejaba el modal con el croquis congelado. Fix: en',
            '`activar_pestana`, si la pestaña destino no es "viajes" y hay',
            'un modal con `#lista_micros_viaje`, cerrarlo antes de ocultar',
            'el detalle. Detectado por las pruebas del plugin).',
            'Antes: v1.5piloto.74e (fix de',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74f al historial',
        'buscar' => [
            '- **v74e**: fix de condición de carrera entre el polling de',
        ],
        'reemplazar' => [
            '- **v74f**: cerrar el modal del viaje al cambiar de pestaña.',
            '  Si el usuario o una prueba automática cambia de pestaña con',
            '  el modal del detalle del viaje abierto, `ocultar_detalle_viaje`',
            '  mataba el polling pero dejaba el modal con el croquis',
            '  congelado en pantalla con datos viejos. Fix: en',
            '  `activar_pestana`, si la pestaña destino no es "viajes" y',
            '  hay un modal con `#lista_micros_viaje`, cerrarlo antes de',
            '  ocultar el detalle. Detectado por las pruebas del plugin.',
            '- **v74e**: fix de condición de carrera entre el polling de',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74f',
        'buscar' => [
            '- Cerramos en v74e el fix de condición de carrera entre el',
            '  polling de asientos y el clic. También reportado por las',
            '  pruebas del plugin: un asiento recién seleccionado volvía a',
            '  verse libre por el polling en vuelo.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '- Cerramos en v74e el fix de condición de carrera entre el',
            '  polling de asientos y el clic. También reportado por las',
            '  pruebas del plugin: un asiento recién seleccionado volvía a',
            '  verse libre por el polling en vuelo.',
            '- Cerramos en v74f el fix de "modal del viaje abierto al',
            '  cambiar de pestaña". El croquis quedaba congelado en',
            '  pantalla tras cancelar una venta desde una prueba',
            '  automática. También reportado por las pruebas del plugin.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado al cierre v74f',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74e (framework 1.5i.7f).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74f (framework 1.5i.7f).',
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