<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.55c: cartel de ubicación del dinero en el modal de cancelar
 * venta. Ahora contempla los 4 lugares posibles: efectivo/banco en la
 * terminal y efectivo/banco ya rendidos al dueño.
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
    // 1. ventas.js: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.55c',
        'buscar' => [
            ' * @version 1.5piloto.55',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.55c',
        ],
    ],

    // ============================================================
    // 2. ventas.js: reemplazar el bloque de ubicación
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: ubicación del dinero según dónde está cada peso',
        'buscar' => [
            '        // Ubicación: texto simple. Si hay un solo método, mencionarlo.',
            '        let ubicacion = \'\';',
            '        if (efvo > 0.001 && banco <= 0.001) {',
            '            ubicacion = `El dinero está en efectivo en la terminal <b>${info.terminal_visible}</b>.`;',
            '        } else if (banco > 0.001 && efvo <= 0.001) {',
            '            ubicacion = `El dinero está en el banco de la terminal <b>${info.terminal_visible}</b>.`;',
            '        } else {',
            '            ubicacion = `El dinero está en la terminal <b>${info.terminal_visible}</b>.`;',
            '        }',
        ],
        'reemplazar' => [
            '        // Ubicación: se arma una lista de fragmentos según dónde está',
            '        // cada peso. Puede haber plata en 4 lugares distintos: efectivo',
            '        // o banco, en la terminal o ya rendidos al dueño.',
            '        const en_term_ef = parseFloat(info.en_terminal_efectivo || \'0\');',
            '        const en_term_ba = parseFloat(info.en_terminal_banco || \'0\');',
            '        const en_dueno_ef = parseFloat(info.cubierto_dueno_efectivo || \'0\');',
            '        const en_dueno_ba = parseFloat(info.cubierto_dueno_banco || \'0\');',
            '        const no_cub_ef = parseFloat(info.no_cubierto_efectivo || \'0\');',
            '        const no_cub_ba = parseFloat(info.no_cubierto_banco || \'0\');',
            '',
            '        const partes_ubicacion = [];',
            '        if (en_term_ef > 0.001) partes_ubicacion.push(`efectivo en la terminal <b>${info.terminal_visible}</b>`);',
            '        if (en_term_ba > 0.001) partes_ubicacion.push(`banco en la terminal <b>${info.terminal_visible}</b>`);',
            '        if (en_dueno_ef > 0.001) partes_ubicacion.push(`efectivo ya rendido al dueño`);',
            '        if (en_dueno_ba > 0.001) partes_ubicacion.push(`banco ya rendido al dueño`);',
            '',
            '        let ubicacion = \'\';',
            '        if (partes_ubicacion.length === 0) {',
            '            ubicacion = \'No hay saldo para cubrir la devolución.\';',
            '        } else if (partes_ubicacion.length === 1) {',
            '            ubicacion = `El dinero está en ${partes_ubicacion[0]}.`;',
            '        } else {',
            '            ubicacion = `El dinero está distribuido así: ${partes_ubicacion.join(\' · \')}.`;',
            '        }',
            '',
            '        // Aviso si algo no se pudo cubrir (el dueño no tenía saldo).',
            '        if (no_cub_ef > 0.001 || no_cub_ba > 0.001) {',
            '            const partes_falta = [];',
            '            if (no_cub_ef > 0.001) partes_falta.push(`$${_formatear_monto_rendicion(no_cub_ef)} en efectivo`);',
            '            if (no_cub_ba > 0.001) partes_falta.push(`$${_formatear_monto_rendicion(no_cub_ba)} en banco`);',
            '            ubicacion += ` <b>Atención:</b> el dueño no tenía saldo suficiente para cubrir ${partes_falta.join(\' y \')}.`;',
            '        }',
        ],
    ],

    // ============================================================
    // 3. aplicacion_GET.html: bump de ventas.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de ventas.js a 55c',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.55"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.55c"></script>',
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