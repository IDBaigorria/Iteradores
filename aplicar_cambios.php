<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.49d: etiquetas en el bloque compacto del comprador.
 * - Cada dato (Nombre, Celular, Email, Dirección) con su etiqueta.
 * - Se mantiene el formato horizontal con separadores "·".
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
    // ventas.js: bump de versión
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.49d',
        'buscar' => [
            ' * @version 1.5piloto.49c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.49d',
        ],
    ],

    // --------------------------------------------------------
    // ventas.js: cada dato con su etiqueta
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: etiquetas en cada dato del comprador',
        'buscar' => [
            '        // Comprador: se arma solo si hay datos. Formato compacto:',
            '        // "COMPRADOR  Pérez, Juan · 11 2233 4455 · juan@mail.com · Dirección"',
            '        // en un solo renglón (con flex-wrap por si el contenido es muy largo).',
            '        let comprador_html = \'\';',
            '        const c = venta.comprador;',
            '        if (c) {',
            '            const nombre = c.nombre_completo || \'\';',
            '            const celular = c.celular || \'\';',
            '            const email = c.email || \'\';',
            '            const direccion_completa = [c.direccion, c.localidad].filter(v => v).join(\', \');',
            '',
            '            const datos = [];',
            '            if (nombre) datos.push(`<b class="sale-comprador-nombre">${nombre}</b>`);',
            '            if (celular) datos.push(`<span>${celular}</span>`);',
            '            if (email) datos.push(`<span>${email}</span>`);',
            '            if (direccion_completa) datos.push(`<span>${direccion_completa}</span>`);',
            '',
            '            if (datos.length > 0) {',
            '                const sep = \'<span class="sale-sep">·</span>\';',
            '                comprador_html = `',
            '                    <div class="sale-comprador">',
            '                        <span class="sale-comprador-titulo">Comprador:</span>',
            '                        <span class="sale-comprador-datos">${datos.join(sep)}</span>',
            '                    </div>',
            '                `;',
            '            }',
            '        }',
        ],
        'reemplazar' => [
            '        // Comprador: se arma solo si hay datos. Formato horizontal con',
            '        // etiqueta por dato, separados por "·". Puede ocupar uno o dos',
            '        // renglones según el ancho disponible (flex-wrap).',
            '        let comprador_html = \'\';',
            '        const c = venta.comprador;',
            '        if (c) {',
            '            const nombre = c.nombre_completo || \'\';',
            '            const celular = c.celular || \'\';',
            '            const email = c.email || \'\';',
            '            const direccion_completa = [c.direccion, c.localidad].filter(v => v).join(\', \');',
            '',
            '            const datos = [];',
            '            if (nombre) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Nombre:</span> <b class="sale-comprador-nombre">${nombre}</b></span>`);',
            '            if (celular) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Celular:</span> <b>${celular}</b></span>`);',
            '            if (email) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Email:</span> <b>${email}</b></span>`);',
            '            if (direccion_completa) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Dirección:</span> <b>${direccion_completa}</b></span>`);',
            '',
            '            if (datos.length > 0) {',
            '                const sep = \'<span class="sale-sep">·</span>\';',
            '                comprador_html = `',
            '                    <div class="sale-comprador">',
            '                        <span class="sale-comprador-titulo">Comprador:</span>',
            '                        <span class="sale-comprador-datos">${datos.join(sep)}</span>',
            '                    </div>',
            '                `;',
            '            }',
            '        }',
        ],
    ],

    // --------------------------------------------------------
    // estilos-ventas.css: ajustar estilos del comprador
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-ventas.css',
        'descripcion' => 'estilos-ventas.css: estilos para etiquetas del comprador',
        'buscar' => [
            '.sale-comprador-datos {',
            '    display: flex;',
            '    flex-wrap: wrap;',
            '    align-items: baseline;',
            '    gap: 2px 6px;',
            '    color: var(--text);',
            '    word-break: break-word;',
            '}',
            '',
            '.sale-comprador-nombre {',
            '    color: var(--primary-dark);',
            '    font-weight: 700;',
            '}',
        ],
        'reemplazar' => [
            '.sale-comprador-datos {',
            '    display: flex;',
            '    flex-wrap: wrap;',
            '    align-items: baseline;',
            '    gap: 2px 8px;',
            '    color: var(--text);',
            '    word-break: break-word;',
            '}',
            '',
            '/* Cada dato con su etiqueta. La etiqueta va en tono muted y el valor',
            '   en bold, manteniendo el flujo horizontal. */',
            '.sale-comprador-dato {',
            '    display: inline-flex;',
            '    align-items: baseline;',
            '    gap: 4px;',
            '    white-space: nowrap;',
            '}',
            '',
            '.sale-comprador-etiqueta {',
            '    color: var(--muted);',
            '    font-size: 12px;',
            '    flex-shrink: 0;',
            '}',
            '',
            '.sale-comprador-dato b {',
            '    color: var(--text);',
            '    font-weight: 600;',
            '}',
            '',
            '.sale-comprador-nombre {',
            '    color: var(--primary-dark);',
            '    font-weight: 700;',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html: bump de ventas.js a 49d
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de ventas.js a ?v=1.5piloto.49d',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.49c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.49d"></script>',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html: bump de estilos-ventas.css a 49e
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de estilos-ventas.css a ?v=1.5piloto.49e',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.49d">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.49e">',
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