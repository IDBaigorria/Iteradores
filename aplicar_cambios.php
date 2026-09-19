<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.54d: ajustes del flujo de liquidación.
 * - Modal de detalle de liquidación más angosto (560px).
 * - Botón "Usar máximo" en el modal de liquidar.
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
    // 1. liquidaciones.js: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'liquidaciones.js: bump @version a 1.5piloto.54d',
        'buscar' => [
            ' * @version 1.5piloto.54c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.54d',
        ],
    ],

    // ============================================================
    // 2. liquidaciones.js: modal de detalle más angosto
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'liquidaciones.js: achicar modal de detalle de liquidación',
        'buscar' => [
            '    abrir_modal_generico(\'Detalle de liquidación\', html);',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    cont.querySelector(\'#btn_cerrar_detalle_liquidacion\').addEventListener(\'click\', cerrar_modal_generico);',
        ],
        'reemplazar' => [
            '    abrir_modal_generico(\'Detalle de liquidación\', html);',
            '',
            '    // El detalle de liquidación tiene menos contenido que otros modales,',
            '    // así que se achica el ancho.',
            '    const contentEl = document.querySelector(\'#modal_generico .modal-content\');',
            '    if (contentEl) {',
            '        contentEl.style.maxWidth = \'560px\';',
            '        contentEl.style.width = \'560px\';',
            '    }',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    cont.querySelector(\'#btn_cerrar_detalle_liquidacion\').addEventListener(\'click\', cerrar_modal_generico);',
        ],
    ],

    // ============================================================
    // 3. liquidaciones.js: botón "Usar máximo" en el modal de liquidar
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'liquidaciones.js: agregar botón Usar máximo',
        'buscar' => [
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo">Monto a extraer</div>',
            '                <div class="form-grid">',
            '                    <div class="field">',
            '                        <label>De efectivo (máx $${_formatear_monto_rendiciones(ef)})</label>',
            '                        <input type="number" id="liquidacion_monto_efectivo" step="1000" min="0" max="${ef.toFixed(2)}" value="" placeholder="0.00">',
            '                    </div>',
            '                    <div class="field">',
            '                        <label>Del banco (máx $${_formatear_monto_rendiciones(ba)})</label>',
            '                        <input type="number" id="liquidacion_monto_banco" step="1000" min="0" max="${ba.toFixed(2)}" value="" placeholder="0.00">',
            '                    </div>',
            '                </div>',
            '            </div>',
        ],
        'reemplazar' => [
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo" style="display:flex; justify-content:space-between; align-items:center; gap:10px;">',
            '                    <span>Monto a extraer</span>',
            '                    <button type="button" class="btn" id="liquidacion_usar_maximo" style="font-size:12px; padding:4px 10px;">Usar máximo</button>',
            '                </div>',
            '                <div class="form-grid">',
            '                    <div class="field">',
            '                        <label>De efectivo (máx $${_formatear_monto_rendiciones(ef)})</label>',
            '                        <input type="number" id="liquidacion_monto_efectivo" step="1000" min="0" max="${ef.toFixed(2)}" value="" placeholder="0.00">',
            '                    </div>',
            '                    <div class="field">',
            '                        <label>Del banco (máx $${_formatear_monto_rendiciones(ba)})</label>',
            '                        <input type="number" id="liquidacion_monto_banco" step="1000" min="0" max="${ba.toFixed(2)}" value="" placeholder="0.00">',
            '                    </div>',
            '                </div>',
            '            </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'liquidaciones.js: listener del botón Usar máximo',
        'buscar' => [
            '    const actualizar = () => _actualizar_totales_liquidacion();',
            '    input_ef.addEventListener(\'input\', actualizar);',
            '    input_ba.addEventListener(\'input\', actualizar);',
            '',
            '    cont.querySelector(\'#btn_cerrar_liquidacion_modal\').addEventListener(\'click\', cerrar_modal_generico);',
        ],
        'reemplazar' => [
            '    const actualizar = () => _actualizar_totales_liquidacion();',
            '    input_ef.addEventListener(\'input\', actualizar);',
            '    input_ba.addEventListener(\'input\', actualizar);',
            '',
            '    // Botón "Usar máximo": llena cada input con su tope respectivo.',
            '    const btn_max = cont.querySelector(\'#liquidacion_usar_maximo\');',
            '    if (btn_max) {',
            '        btn_max.addEventListener(\'click\', () => {',
            '            input_ef.value = ef.toFixed(2);',
            '            input_ba.value = ba.toFixed(2);',
            '            _actualizar_totales_liquidacion();',
            '        });',
            '    }',
            '',
            '    cont.querySelector(\'#btn_cerrar_liquidacion_modal\').addEventListener(\'click\', cerrar_modal_generico);',
        ],
    ],

    // ============================================================
    // 4. aplicacion_GET.html: bump de liquidaciones.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de liquidaciones.js a 54d',
        'buscar' => [
            '<script src="Aplicacion/liquidaciones.js?v=1.5piloto.54c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/liquidaciones.js?v=1.5piloto.54d"></script>',
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