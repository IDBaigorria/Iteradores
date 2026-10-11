<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77m (fix del DivisionByZeroError).
 *   - Venta.php:
 *     - Validación temprana: con 1 sola cuota, el monto pagado
 *       debe cubrir el total. Antes esto no se chequeaba y el
 *       flujo llegaba hasta _crear_lista_cupones_venta con
 *       cuotas_restantes = 0, donde reventaba con division by zero.
 *     - Defensivo en _crear_lista_cupones_venta: si por algún
 *       flujo viejo llega con cuotas_restantes <= 0 y saldo > 0,
 *       no divide por cero.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Venta.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.77m',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.77e',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.77m',
        ],
    ],

    // ============================================================
    // Venta.php — validación temprana
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'confirmar_venta_actual: validar 1 cuota sin cubrir',
        'buscar' => [
            '    $cuotas_restantes = 0;',
            '    if ($monto_pagado >= $total) {',
            '        $cuotas_restantes = 0;',
            '        // Ajustar el monto pagado al total (no se puede cobrar más de lo que vale)',
            '        $monto_pagado = $total;',
            '    } else {',
            '        $cuotas_restantes = max(0, $cuotas - 1);',
            '    }',
        ],
        'reemplazar' => [
            '    $cuotas_restantes = 0;',
            '    if ($monto_pagado >= $total) {',
            '        $cuotas_restantes = 0;',
            '        // Ajustar el monto pagado al total (no se puede cobrar más de lo que vale)',
            '        $monto_pagado = $total;',
            '    } else {',
            '        $cuotas_restantes = max(0, $cuotas - 1);',
            '    }',
            '',
            '    // Fix v77m: con 1 sola cuota, si el monto pagado no cubre',
            '    // el total, la venta no tiene forma de saldarse (no hay',
            '    // cuotas siguientes donde cobrar la diferencia). Antes',
            '    // esto llegaba hasta _crear_lista_cupones_venta y',
            '    // reventaba con division by zero.',
            '    if ($cuotas === 1 && $monto_pagado < $total) {',
            '        return [\'exito\' => false, \'error\' => \'Con 1 sola cuota, el monto a pagar debe cubrir el total\'];',
            '    }',
        ],
    ],

    // ============================================================
    // Venta.php — defensivo en _crear_lista_cupones_venta
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => '_crear_lista_cupones_venta: defensivo contra division por cero',
        'buscar' => [
            '    // Cupones 2..N (pendientes).',
            '    $teorico_pendiente = round($saldo / $cuotas_restantes, 2);',
        ],
        'reemplazar' => [
            '    // Cupones 2..N (pendientes).',
            '    if ($cuotas_restantes <= 0) {',
            '        // Defensivo (fix v77m): la validación temprana en',
            '        // confirmar_venta_actual previene este caso. Si por',
            '        // algún flujo viejo llega acá con cuotas_restantes',
            '        // <= 0 y saldo > 0, no creamos cupones pendientes',
            '        // ni dividimos por cero.',
            '        return;',
            '    }',
            '    $teorico_pendiente = round($saldo / $cuotas_restantes, 2);',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77m',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77l',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77m',
        ],
    ],

    // ============================================================
    // plan_actual.md — registro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: fix del DivisionByZeroError',
        'buscar' => [
            '**Tanda actual:** v77l (Fase B2.3.5c.2 + c.3: fix del',
            'flujo de alta de terminal).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77m (fix del DivisionByZeroError).',
            '',
            '**Bug detectado al correr la prueba 58 del plugin.**',
            '',
            'Cuando se confirma una venta con `cuotas = 1` y',
            '`monto_pagado < total`, el flujo de confirmar_venta_actual',
            'no lo valida. Calcula `cuotas_restantes = max(0, 1-1) = 0` y',
            'lo pasa a `_crear_lista_cupones_venta`, que en el caso B',
            'hace `$saldo / $cuotas_restantes` → division by zero →',
            'fatal error de PHP → respuesta HTML (no JSON) → el',
            'frontend muestra "Error de comunicación".',
            '',
            'Ningún usuario real llega a este estado: el frontend',
            'autocompleta `monto_pagado = total` y lo deja `disabled`',
            'cuando `cuotas = 1`. La prueba 58 lo forzó con',
            '`setear_monto_pagado(1000)` pisando el valor correcto.',
            '',
            '**Fix v77m (Venta.php):**',
            '',
            '- Validación temprana en `confirmar_venta_actual`: si',
            '  `cuotas === 1` y `monto_pagado < total`, devolver',
            '  error claro ("Con 1 sola cuota, el monto a pagar debe',
            '  cubrir el total").',
            '- Defensivo en `_crear_lista_cupones_venta`: si llega',
            '  con `cuotas_restantes <= 0` y saldo > 0, no dividir',
            '  (ni crear cupones pendientes). Cinturón y tiradores.',
            '',
            '**Pendiente (test del plugin):** reescribir la prueba 58',
            'para no pisar el monto pagado cuando `cuotas = 1`.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";