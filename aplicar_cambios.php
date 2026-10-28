<?php
/**
 * Aplicador — Fase 3 del bug §4.7: chequeo de guardar_ambos
 * en confirmar_venta_actual y pagar_cupon_venta.
 *
 * Motivo: ambos flujos llaman `guardar_ambos` sin chequear el
 * resultado. Cuando el guardado parcial falla (por ejemplo, por
 * el blindaje de 1.5i.7t o por el bug real §4.7), el backend
 * devuelve `exito: true` igual. El frontend muestra "Venta
 * confirmada" con la DB intacta. El asiento sigue "seleccionado"
 * en el croquis (por el polling) e "Imprimir pasaje" devuelve
 * "venta no encontrada".
 *
 * Fix: patrón C.6 (aplicado en cancelar_venta en v81w).
 * Chequear `guardar_ambos`; si falla, devolver `exito: false`
 * con error específico. El caller (frontend) muestra el error
 * y NO muestra el modal post-venta.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83d';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. Venta.php — confirmar_venta_actual: chequear guardar_ambos
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'confirmar_venta_actual: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_venta\' => $id_venta,',
        '        \'total\' => (string)$total,',
        '        \'pagado\' => (string)$monto_pagado,',
        '        \'cuotas_restantes\' => (string)$cuotas_restantes,',
        '        \'asientos\' => count($asientos_seleccionados),',
        '    ];',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83d: chequear el resultado del guardado.',
        '    // Antes se ignoraba y el backend respondía "éxito" aunque',
        '    // la DB no se hubiera actualizado. El usuario veía "Venta',
        '    // confirmada" con el asiento todavía "seleccionado" en el',
        '    // croquis (por el polling) y "venta no encontrada" al',
        '    // intentar imprimir el pasaje. Patrón C.6 (aplicado en',
        '    // cancelar_venta en v81w).',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("confirmar_venta_actual: guardar_ambos falló para id_venta=$id_venta");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La venta se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'id_venta\' => $id_venta,',
        '        \'total\' => (string)$total,',
        '        \'pagado\' => (string)$monto_pagado,',
        '        \'cuotas_restantes\' => (string)$cuotas_restantes,',
        '        \'asientos\' => count($asientos_seleccionados),',
        '    ];',
    ],
],

// ============================================================
// 2. Venta.php — pagar_cupon_venta: chequear guardar_ambos
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'pagar_cupon_venta: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'venta\' => formatear_venta_completa($nodo_venta),',
        '    ];',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83d: chequear el resultado del guardado.',
        '    // Mismo patrón que confirmar_venta_actual y',
        '    // cancelar_venta. Si el guardado parcial falla, la DB no',
        '    // se actualiza pero el backend respondía "éxito" igual.',
        '    // El usuario veía "Cupón pagado" con el estado viejo.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("pagar_cupon_venta: guardar_ambos falló para id_venta=$id_venta numero_cupon=$numero_cupon");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El pago del cupón se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    return [',
        '        \'exito\' => true,',
        '        \'venta\' => formatear_venta_completa($nodo_venta),',
        '    ];',
    ],
],

// ============================================================
// 3. plan_actual.md: header a v83d
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83d',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83c (Fase 3 del',
        'bug §4.7: primer ciclo con blindaje detectó un falso',
        'positivo en la verificación de máscaras. El bug real NO',
        'se reprodujo. Fix del falso positivo en 1.5i.7u).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83d (Fase 3 del',
        'bug §4.7: fix del falso positivo en 1.5i.7u. Detectado',
        'que `confirmar_venta_actual` y `pagar_cupon_venta` no',
        'chequean `guardar_ambos`, reportando "éxito" con la DB',
        'intacta. Chequeos aplicados. Deuda: ~15 flujos más con',
        'el mismo patrón, tanda aparte).',
    ],
],

// ============================================================
// 4. plan_actual.md: nota en Fase 3
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: nota del segundo bug y fix',
    'buscar' => [
        '  ahora sí el log va a mostrar el punto exacto.',
    ],
    'reemplazar' => [
        '  ahora sí el log va a mostrar el punto exacto.',
        '- **Segundo bug detectado (1.5piloto.83d):**',
        '  `confirmar_venta_actual` y `pagar_cupon_venta`',
        '  llamaban `guardar_ambos` **sin chequear el',
        '  resultado**. Cuando el guardado parcial fallaba (por',
        '  el falso positivo del blindaje), el backend devolvía',
        '  `exito: true` igual. El frontend mostraba "Venta',
        '  confirmada" con la DB intacta. El asiento seguía',
        '  "seleccionado" en el croquis (por el polling) y',
        '  "Imprimir pasaje" devolvía "venta no encontrada".',
        '  **Fix:** patrón C.6. Chequear `guardar_ambos`; si',
        '  falla, devolver `exito: false` con error específico',
        '  ("La venta se procesó en memoria pero no se pudo',
        '  guardar en la base de datos. Revisá el log del',
        '  servidor o intentá de nuevo.").',
        '- **Deuda técnica (tanda aparte):** ~15 flujos más',
        '  llaman `guardar_ambos` sin chequear.',
        '  `reservar_asiento_micro`, `asignar_pasajero_a_reserva`,',
        '  `liberar_reserva_asiento_micro`, `seleccionar_asiento_micro`,',
        '  `deseleccionar_asiento_micro`, `cambiar_asiento_pasaje`,',
        '  `agregar_micro_a_viaje`, `eliminar_micro_de_viaje`,',
        '  `actualizar_monto_micro`, `eliminar_viaje`,',
        '  `crear_pasajero`, `actualizar_pasajero`,',
        '  `eliminar_pasajero`,',
        '  `eliminar_declaracion_jurada_pasajero`,',
        '  `subir_declaracion_jurada_pasajero`. Todos tienen el',
        '  mismo síntoma: "éxito" sin persistir. Hacer después',
        '  de cerrar §4.7.',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";