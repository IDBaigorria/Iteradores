<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.57k: croquis del micro.
 * - Asientos más bajos (min-height 44px).
 * - Salto de página entre pisos cuando el micro es de dos pisos.
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
    // 1. Impresion.php: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: bump @version a 1.5piloto.57k',
        'buscar' => [
            ' * @version   1.5piloto.57j',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.57k',
        ],
    ],

    // ============================================================
    // 2. Impresion.php: asiento más bajo + reglas de salto de piso
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: asiento más bajo y reglas de salto de piso',
        'buscar' => [
            '        .seat { border: 1px solid black; border-radius: 4px; padding: 3px 2px; min-height: 68px; text-align: center; font-size: 10px; display: flex; flex-direction: column; justify-content: flex-start; overflow: hidden; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
            '        .seat .num { font-size: 14px; font-weight: 700; line-height: 1.1; margin-bottom: 1px; }',
            '        .seat .nombre { font-size: 8px; font-weight: 700; line-height: 1.1; margin-top: 1px; word-break: break-word; }',
            '        .seat .dato { font-size: 7.5px; line-height: 1.1; margin-top: 1px; word-break: break-word; }',
            '        .seat.vendido { background: #e8e8e8; border: 2px solid black; }',
            '        .seat.reservado { background: white; border: 2px solid black; }',
            '        .seat.no-disponible { background: #d0d0d0; border: 2px solid black; }',
            '        .seat.libre { border: 1px solid black; }',
            '        .leyenda { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; font-size: 11px; margin-top: 14px; }',
        ],
        'reemplazar' => [
            '        .seat { border: 1px solid black; border-radius: 4px; padding: 2px 2px; min-height: 44px; text-align: center; font-size: 10px; display: flex; flex-direction: column; justify-content: flex-start; overflow: hidden; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
            '        .seat .num { font-size: 14px; font-weight: 700; line-height: 1.1; margin-bottom: 1px; }',
            '        .seat .nombre { font-size: 8px; font-weight: 700; line-height: 1.1; margin-top: 1px; word-break: break-word; }',
            '        .seat .dato { font-size: 7.5px; line-height: 1.1; margin-top: 1px; word-break: break-word; }',
            '        .seat.vendido { background: #e8e8e8; border: 2px solid black; }',
            '        .seat.reservado { background: white; border: 2px solid black; }',
            '        .seat.no-disponible { background: #d0d0d0; border: 2px solid black; }',
            '        .seat.libre { border: 1px solid black; }',
            '        .piso-hoja { page-break-inside: avoid; }',
            '        .piso-hoja + .piso-hoja { page-break-before: always; }',
            '        .leyenda { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; font-size: 11px; margin-top: 14px; }',
        ],
    ],

    // ============================================================
    // 3. Impresion.php: abrir .piso-hoja al inicio de cada piso
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: abrir piso-hoja al inicio de cada piso',
        'buscar' => [
            '        $hay_piso_2 = $nodo_asientos_copia->adyacente(\'piso_2\') !== null;',
            '        if ($hay_piso_2) {',
            '            echo \'<div class="piso-titulo">Piso \' . $i . \'</div>\';',
            '        }',
            '',
            '        echo \'<div class="bus">\';',
            '        echo \'<div class="bus-front">FRENTE · CONDUCTOR</div>\';',
        ],
        'reemplazar' => [
            '        // Cada piso va envuelto en un contenedor que fuerza salto de',
            '        // página a partir del segundo piso, para que no se imprima todo',
            '        // amontonado en la misma hoja.',
            '        echo \'<div class="piso-hoja">\';',
            '',
            '        $hay_piso_2 = $nodo_asientos_copia->adyacente(\'piso_2\') !== null;',
            '        if ($hay_piso_2) {',
            '            echo \'<div class="piso-titulo">Piso \' . $i . \'</div>\';',
            '        }',
            '',
            '        echo \'<div class="bus">\';',
            '        echo \'<div class="bus-front">FRENTE · CONDUCTOR</div>\';',
        ],
    ],

    // ============================================================
    // 4. Impresion.php: cerrar .piso-hoja al final de cada piso
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: cerrar piso-hoja al final de cada piso',
        'buscar' => [
            '        echo \'<div class="bus-back">PARTE TRASERA</div>\';',
            '        echo \'</div>\';',
            '    }',
            '',
            '    echo \'<div class="leyenda">\';',
        ],
        'reemplazar' => [
            '        echo \'<div class="bus-back">PARTE TRASERA</div>\';',
            '        echo \'</div>\'; // cierre .bus',
            '        echo \'</div>\'; // cierre .piso-hoja',
            '    }',
            '',
            '    echo \'<div class="leyenda">\';',
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