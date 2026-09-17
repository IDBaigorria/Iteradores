<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores.
 *
 * Fix v1.5piloto.45e: los cupones creados al confirmar una venta reflejan
 * el monto real pagado en el cupón 1, y el saldo pendiente repartido entre
 * los cupones restantes.
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // =========================================================
    // 1. Venta.php — Bump de versión
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.45e',
        'buscar' => [
            ' * @version   1.5piloto.45',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.45e',
        ],
    ],

    // =========================================================
    // 2. Venta.php — Llamada a _crear_lista_cupones_venta con monto pagado
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: llamada con monto_pagado real',
        'buscar' => [
            '    // Crear la lista de cupones. El contenedor `cupones` es el padre,',
            '    // y cada cupón es un hijo enlazado con la estructura de árbol',
            '    // (hmi/hd/p) de miscelaneas/Arbol.php.',
            '    $cupones_pagados = max(0, $cuotas - $cuotas_restantes);',
            '    _crear_lista_cupones_venta($nodo_venta, $cuotas, (string)$total, $cupones_pagados, $fecha_pago);',
        ],
        'reemplazar' => [
            '    // Crear la lista de cupones. El contenedor `cupones` es el padre,',
            '    // y cada cupón es un hijo enlazado con la estructura de árbol',
            '    // (hmi/hd/p) de miscelaneas/Arbol.php. El cupón 1 refleja el',
            '    // monto real abonado al momento de la venta; los cupones',
            '    // pendientes reparten el saldo restante.',
            '    _crear_lista_cupones_venta($nodo_venta, $cuotas, (string)$total, (string)$monto_pagado, $fecha_pago);',
        ],
    ],

    // =========================================================
    // 3. Venta.php — Reescribir _crear_lista_cupones_venta
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: reescribir _crear_lista_cupones_venta',
        'buscar' => [
            '/**',
            ' * Crea la lista de cupones de una venta como hijos del contenedor',
            ' * `cupones`. Usa la estructura de árbol (hmi/hd/p) de Arbol.php.',
            ' *',
            ' * Los primeros $cupones_pagados cupones quedan en estado `pagado` con',
            ' * la fecha indicada. El resto en estado `pendiente`.',
            ' *',
            ' * @param Nodo   $nodo_venta',
            ' * @param int    $cuotas',
            ' * @param string $total',
            ' * @param int    $cupones_pagados',
            ' * @param string $fecha_pago Fecha a usar para los cupones pagados.',
            ' * @return void',
            ' */',
            'function _crear_lista_cupones_venta(Nodo $nodo_venta, int $cuotas, string $total, int $cupones_pagados, string $fecha_pago): void {',
            '    if ($cuotas <= 0) return;',
            '',
            '    $contenedor_cupones = Nodo::crear_con_dato(\'\');',
            '    $nodo_venta->_adyacente_en($contenedor_cupones, \'cupones\');',
            '',
            '    [$monto_cuota, $monto_ultima] = _calcular_montos_cuotas($total, $cuotas);',
            '',
            '    $anterior = null;',
            '    for ($i = 1; $i <= $cuotas; $i++) {',
            '        $cupon = Nodo::crear_con_dato(\'\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato((string)$i), \'numero\');',
            '',
            '        $monto = ($i === $cuotas) ? $monto_ultima : $monto_cuota;',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato($monto), \'monto\');',
            '',
            '        $es_pagado = ($i <= $cupones_pagados);',
            '        if ($es_pagado) {',
            '            $cupon->_adyacente_en(Nodo::crear_con_dato(\'pagado\'), \'estado\');',
            '            if ($fecha_pago !== \'\') {',
            '                $cupon->_adyacente_en(Nodo::crear_con_dato($fecha_pago), \'fecha_pago\');',
            '            }',
            '        } else {',
            '            $cupon->_adyacente_en(Nodo::crear_con_dato(\'pendiente\'), \'estado\');',
            '        }',
            '',
            '        if ($anterior === null) {',
            '            _hmi($contenedor_cupones, $cupon);',
            '        } else {',
            '            _hd($anterior, $cupon);',
            '        }',
            '        $anterior = $cupon;',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Crea la lista de cupones de una venta como hijos del contenedor',
            ' * `cupones`. Usa la estructura de árbol (hmi/hd/p) de Arbol.php.',
            ' *',
            ' * El cupón 1 refleja el monto real abonado al momento de la venta',
            ' * (monto_pagado). Los cupones pendientes (2..N) reparten el saldo',
            ' * restante en partes iguales; el último absorbe el remanente de',
            ' * centavos para que la suma sea exactamente igual al total.',
            ' *',
            ' * Si el comprador pagó todo al momento de la venta, se crea un solo',
            ' * cupón (el 1) con el monto total y no quedan oportunidades.',
            ' *',
            ' * @param Nodo   $nodo_venta',
            ' * @param int    $cuotas',
            ' * @param string $total',
            ' * @param string $monto_pagado Monto real abonado al momento de la venta.',
            ' * @param string $fecha_pago Fecha a usar para el cupón pagado.',
            ' * @return void',
            ' */',
            'function _crear_lista_cupones_venta(Nodo $nodo_venta, int $cuotas, string $total, string $monto_pagado, string $fecha_pago): void {',
            '    if ($cuotas <= 0) return;',
            '',
            '    $total_num = (float)$total;',
            '    $monto_pagado_num = (float)$monto_pagado;',
            '    $saldo = max(0, $total_num - $monto_pagado_num);',
            '',
            '    $contenedor_cupones = Nodo::crear_con_dato(\'\');',
            '    $nodo_venta->_adyacente_en($contenedor_cupones, \'cupones\');',
            '',
            '    // Caso A: pagó todo al momento de la venta. Un solo cupón pagado',
            '    // con el monto total. No quedan más oportunidades.',
            '    if ($saldo <= 0.001) {',
            '        $cupon = Nodo::crear_con_dato(\'\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato(\'1\'), \'numero\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato(number_format($total_num, 2, \'.\', \'\')), \'monto\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato(\'pagado\'), \'estado\');',
            '        if ($fecha_pago !== \'\') {',
            '            $cupon->_adyacente_en(Nodo::crear_con_dato($fecha_pago), \'fecha_pago\');',
            '        }',
            '        _hmi($contenedor_cupones, $cupon);',
            '        return;',
            '    }',
            '',
            '    // Caso B: pagó parte. Cupón 1 pagado con el monto real. Cupones',
            '    // 2..N pendientes con el saldo repartido.',
            '    $cuotas_restantes = $cuotas - 1;',
            '',
            '    // Cupón 1 (pagado).',
            '    $cupon_1 = Nodo::crear_con_dato(\'\');',
            '    $cupon_1->_adyacente_en(Nodo::crear_con_dato(\'1\'), \'numero\');',
            '    $cupon_1->_adyacente_en(Nodo::crear_con_dato(number_format($monto_pagado_num, 2, \'.\', \'\')), \'monto\');',
            '    $cupon_1->_adyacente_en(Nodo::crear_con_dato(\'pagado\'), \'estado\');',
            '    if ($fecha_pago !== \'\') {',
            '        $cupon_1->_adyacente_en(Nodo::crear_con_dato($fecha_pago), \'fecha_pago\');',
            '    }',
            '    _hmi($contenedor_cupones, $cupon_1);',
            '    $anterior = $cupon_1;',
            '',
            '    // Cupones 2..N (pendientes).',
            '    $teorico_pendiente = round($saldo / $cuotas_restantes, 2);',
            '    for ($i = 2; $i <= $cuotas; $i++) {',
            '        $es_ultimo = ($i === $cuotas);',
            '        $monto_pendiente = $es_ultimo',
            '            ? round($saldo - $teorico_pendiente * ($cuotas_restantes - 1), 2)',
            '            : $teorico_pendiente;',
            '',
            '        $cupon = Nodo::crear_con_dato(\'\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato((string)$i), \'numero\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato(number_format($monto_pendiente, 2, \'.\', \'\')), \'monto\');',
            '        $cupon->_adyacente_en(Nodo::crear_con_dato(\'pendiente\'), \'estado\');',
            '',
            '        _hd($anterior, $cupon);',
            '        $anterior = $cupon;',
            '    }',
            '}',
        ],
    ],

    // =========================================================
    // 4. migrar_cupones.php — Usar monto_pagado
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/migrar_cupones.php',
        'descripcion' => 'migrar_cupones.php: usar monto_pagado real',
        'buscar' => [
            '            $cuotas = (int)($actual->adyacente(\'cuotas\') ? $actual->adyacente(\'cuotas\')->dato() : \'0\');',
            '            $total = $actual->adyacente(\'total\') ? $actual->adyacente(\'total\')->dato() : \'\';',
            '            $cuotas_restantes = (int)($actual->adyacente(\'cuotas_restantes\') ? $actual->adyacente(\'cuotas_restantes\')->dato() : \'0\');',
            '            $fecha_pago = $actual->adyacente(\'fecha_ultimo_pago\') ? $actual->adyacente(\'fecha_ultimo_pago\')->dato() : \'\';',
            '',
            '            if ($cuotas <= 0 || $total === \'\') {',
            '                $res[\'ventas_sin_datos\']++;',
            '                $actual = $siguiente;',
            '                continue;',
            '            }',
            '',
            '            $cupones_pagados = max(0, $cuotas - $cuotas_restantes);',
            '            _crear_lista_cupones_venta($actual, $cuotas, $total, $cupones_pagados, $fecha_pago);',
            '            $res[\'ventas_migradas\']++;',
        ],
        'reemplazar' => [
            '            $cuotas = (int)($actual->adyacente(\'cuotas\') ? $actual->adyacente(\'cuotas\')->dato() : \'0\');',
            '            $total = $actual->adyacente(\'total\') ? $actual->adyacente(\'total\')->dato() : \'\';',
            '            $monto_pagado = $actual->adyacente(\'pagado\') ? $actual->adyacente(\'pagado\')->dato() : \'0\';',
            '            $fecha_pago = $actual->adyacente(\'fecha_ultimo_pago\') ? $actual->adyacente(\'fecha_ultimo_pago\')->dato() : \'\';',
            '',
            '            if ($cuotas <= 0 || $total === \'\') {',
            '                $res[\'ventas_sin_datos\']++;',
            '                $actual = $siguiente;',
            '                continue;',
            '            }',
            '',
            '            _crear_lista_cupones_venta($actual, $cuotas, $total, $monto_pagado, $fecha_pago);',
            '            $res[\'ventas_migradas\']++;',
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