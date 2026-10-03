<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74d — fix de refresco del croquis tras cancelar venta.
 *
 * Problema: al cancelar una venta, el backend libera los asientos en
 * el grafo, pero el frontend se queda con el croquis viejo. Sin esto,
 * el usuario sigue viendo los asientos como "vendidos" hasta el
 * proximo polling (hasta 15s, y pausado si hubo inactividad).
 *
 * Fix: en `cancelar_venta`, capturar el micro y el viaje abiertos
 * ANTES de cerrar el modal, y despues del exito forzar un fetch a
 * `viajes/estado_asientos` para actualizar `estados_asientos_actuales`.
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
    // Aplicacion/ventas.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump a 1.5piloto.74d',
        'buscar' => [
            '/***',
            ' * Funciones de venta, confirmación, listado y cancelación.',
            ' * @version 1.5piloto.73x',
            ' */',
        ],
        'reemplazar' => [
            '/***',
            ' * Funciones de venta, confirmación, listado y cancelación.',
            ' * @version 1.5piloto.74d',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: refrescar croquis al cancelar venta',
        'buscar' => [
            '        const resp2 = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "ventas/cancelar", id_venta, motivo })',
            '        });',
            '        const resultado = await resp2.json();',
            '        if (resultado.exito) {',
            '            mostrar_aviso("Venta cancelada", \'exito\');',
            '            cerrar_modal_generico();',
            '            if (typeof cargar_ventas === \'function\') cargar_ventas();',
            '',
            '            // Modal chico para ofrecer la impresión del informe.',
            '            if (resultado.id_cancelacion) {',
            '                mostrar_modal_chico_impresion_cancelacion(resultado.id_cancelacion);',
            '            }',
            '        } else {',
            '            mostrar_aviso(resultado.error || "Error al cancelar", \'error\');',
            '        }',
        ],
        'reemplazar' => [
            '        // Capturar el micro/viaje abiertos ANTES de cerrar el modal,',
            '        // porque `cerrar_modal_generico()` los resetea. Se usan',
            '        // despues para forzar un refresh del estado de asientos.',
            '        const micro_previo = (typeof micro_seleccionado !== \'undefined\') ? micro_seleccionado : null;',
            '        const viaje_previo = (typeof viaje_seleccionado !== \'undefined\' && viaje_seleccionado) ? viaje_seleccionado : null;',
            '',
            '        const resp2 = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "ventas/cancelar", id_venta, motivo })',
            '        });',
            '        const resultado = await resp2.json();',
            '        if (resultado.exito) {',
            '            mostrar_aviso("Venta cancelada", \'exito\');',
            '            cerrar_modal_generico();',
            '            if (typeof cargar_ventas === \'function\') cargar_ventas();',
            '',
            '            // Refrescar el estado global de los asientos del micro',
            '            // abierto. El backend ya liberó los asientos en el',
            '            // grafo, pero el frontend los tenía cacheados en',
            '            // `estados_asientos_actuales`. Sin esto, el croquis',
            '            // puede mostrar asientos vendidos que ya no lo están',
            '            // hasta el próximo polling (hasta 15s, pausado si',
            '            // hubo inactividad).',
            '            if (micro_previo && viaje_previo) {',
            '                try {',
            '                    const resp_estados = await fetch("index.php", {',
            '                        method: "POST",',
            '                        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '                        body: new URLSearchParams({',
            '                            accion: "viajes/estado_asientos",',
            '                            nombre_viaje: viaje_previo.nombre_viaje,',
            '                            nombre_micro: micro_previo,',
            '                            nombre_dueno: viaje_previo.dueno',
            '                        })',
            '                    });',
            '                    const datos_estados = await resp_estados.json();',
            '                    if (datos_estados.exito && Array.isArray(datos_estados.asientos)) {',
            '                        estados_asientos_actuales = datos_estados.asientos;',
            '                    }',
            '                } catch (e) {',
            '                    console.error("Error refrescando asientos tras cancelar:", e);',
            '                }',
            '            }',
            '',
            '            // Modal chico para ofrecer la impresión del informe.',
            '            if (resultado.id_cancelacion) {',
            '                mostrar_modal_chico_impresion_cancelacion(resultado.id_cancelacion);',
            '            }',
            '        } else {',
            '            mostrar_aviso(resultado.error || "Error al cancelar", \'error\');',
            '        }',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74d',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74c. Se',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74d (fix de',
            'refresco del croquis tras cancelar venta: al cancelar una venta,',
            'el backend libera los asientos pero el frontend seguía mostrando',
            'el croquis viejo hasta el próximo polling. Ahora se captura el',
            'micro y el viaje abiertos antes de cerrar el modal, y después',
            'del éxito se fuerza un fetch a `viajes/estado_asientos` para',
            'actualizar `estados_asientos_actuales`). Antes: v1.5piloto.74c. Se',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74d al historial',
        'buscar' => [
            '- **v74c**: solo documentación. Se formalizó el arranque del',
        ],
        'reemplazar' => [
            '- **v74d**: fix de refresco del croquis tras cancelar venta.',
            '  Al cancelar, el backend liberaba los asientos pero el',
            '  frontend seguía mostrando el croquis viejo hasta el próximo',
            '  polling (`SYNC_INTERVALO_MS`, hasta 15s, pausado si hubo',
            '  inactividad). Ahora `cancelar_venta` captura el micro y el',
            '  viaje abiertos antes de cerrar el modal, y después del',
            '  éxito fuerza un fetch a `viajes/estado_asientos` para',
            '  actualizar `estados_asientos_actuales`. Detectado por las',
            '  pruebas automáticas del plugin.',
            '- **v74c**: solo documentación. Se formalizó el arranque del',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74d',
        'buscar' => [
            '  y se creó en esta misma tanda.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '  y se creó en esta misma tanda.',
            '- Cerramos en v74d el fix del refresco del croquis tras',
            '  cancelar venta. Reportado por las pruebas del plugin:',
            '  los asientos cancelados seguían viéndose como vendidos',
            '  hasta el próximo polling.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado al cierre v74d',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74c (framework 1.5i.7f).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74d (framework 1.5i.7f).',
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