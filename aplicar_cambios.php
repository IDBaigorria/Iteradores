<?php
/**
 * Aplicador — Fix de obtener_pasajero_por_dni con contexto.
 *
 * Con carga parcial del terminal, `usuarios` no está en
 * memoria. `obtener_pasajero_por_dni` iteraba sobre él para
 * buscar las ventas del pasajero, y devolvía lista vacía.
 * El modal del pasajero decía "Sin pasajes registrados".
 *
 * Fix: cuando hay contexto, navegar el árbol del contexto
 * (parametrizado si es compartido). Sin contexto, mantener
 * el comportamiento actual.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. Pasajero.php: fix del bloque de ventas en obtener_pasajero_por_dni
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'obtener_pasajero_por_dni: navegar por contexto',
        'buscar' => [
            '    // Obtener ventas relacionadas',
            '    $ventas = [];',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if ($raiz_usuarios) {',
            '        foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno_iter => $nodo_dueno) {',
            '            $nodo_nivel = $nodo_dueno->adyacente(\'nivel\');',
            '            if (!$nodo_nivel || $nodo_nivel->dato() !== \'dueno\') continue;',
            '',
            '            $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno_iter);',
            '            if (!$contenedor_ventas) continue;',
            '',
            '            $actual = hmi($contenedor_ventas);',
            '            while ($actual) {',
            '                $venta_formateada = formatear_venta_para_pasajero($actual, $dni);',
            '                if ($venta_formateada) {',
            '                    if ($venta_formateada[\'es_comprador\'] && $venta_formateada[\'es_pasajero\']) {',
            '                        $venta_formateada[\'rol\'] = \'ambos\';',
            '                    } elseif ($venta_formateada[\'es_comprador\']) {',
            '                        $venta_formateada[\'rol\'] = \'comprador\';',
            '                    } else {',
            '                        $venta_formateada[\'rol\'] = \'pasajero\';',
            '                    }',
            '                    $ventas[] = $venta_formateada;',
            '                }',
            '                $actual = hd($actual);',
            '            }',
            '        }',
            '    }',
            '    $datos[\'ventas\'] = $ventas;',
        ],
        'reemplazar' => [
            '    // Obtener ventas relacionadas.',
            '    //',
            '    // Con contexto (terminal → compartido), navegar el',
            '    // árbol del contexto con nombres parametrizados.',
            '    // Sin contexto (dueño, admin, impresión), iterar sobre',
            '    // todos los dueños desde `usuarios` (comportamiento',
            '    // actual). Bug §1.5.12: con carga parcial del',
            '    // terminal, `usuarios` no está en memoria y el bloque',
            '    // devolvía lista vacía.',
            '    $ventas = [];',
            '    $nombres_arbol = _nombres_arbol_para_contexto($nodo_contexto, \'\');',
            '',
            '    if ($nodo_contexto !== null) {',
            '        // Contexto: buscar solo en el árbol del contexto.',
            '        $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
            '        if ($contenedor_ventas) {',
            '            $actual = hmi($contenedor_ventas, $nombres_arbol);',
            '            $seg = 0;',
            '            while ($actual && $seg < 2000) {',
            '                $seg++;',
            '                $venta_formateada = formatear_venta_para_pasajero($actual, $dni);',
            '                if ($venta_formateada) {',
            '                    if ($venta_formateada[\'es_comprador\'] && $venta_formateada[\'es_pasajero\']) {',
            '                        $venta_formateada[\'rol\'] = \'ambos\';',
            '                    } elseif ($venta_formateada[\'es_comprador\']) {',
            '                        $venta_formateada[\'rol\'] = \'comprador\';',
            '                    } else {',
            '                        $venta_formateada[\'rol\'] = \'pasajero\';',
            '                    }',
            '                    $ventas[] = $venta_formateada;',
            '                }',
            '                $actual = hd($actual, $nombres_arbol);',
            '            }',
            '        }',
            '    } else {',
            '        // Sin contexto: iterar sobre todos los dueños.',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if ($raiz_usuarios) {',
            '            foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno_iter => $nodo_dueno) {',
            '                $nodo_nivel = $nodo_dueno->adyacente(\'nivel\');',
            '                if (!$nodo_nivel || $nodo_nivel->dato() !== \'dueno\') continue;',
            '',
            '                $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno_iter);',
            '                if (!$contenedor_ventas) continue;',
            '',
            '                $actual = hmi($contenedor_ventas);',
            '                while ($actual) {',
            '                    $venta_formateada = formatear_venta_para_pasajero($actual, $dni);',
            '                    if ($venta_formateada) {',
            '                        if ($venta_formateada[\'es_comprador\'] && $venta_formateada[\'es_pasajero\']) {',
            '                            $venta_formateada[\'rol\'] = \'ambos\';',
            '                        } elseif ($venta_formateada[\'es_comprador\']) {',
            '                            $venta_formateada[\'rol\'] = \'comprador\';',
            '                        } else {',
            '                            $venta_formateada[\'rol\'] = \'pasajero\';',
            '                        }',
            '                        $ventas[] = $venta_formateada;',
            '                    }',
            '                    $actual = hd($actual);',
            '                }',
            '            }',
            '        }',
            '    }',
            '    $datos[\'ventas\'] = $ventas;',
        ],
    ],

    // ============================================================
    // 2. Pasajero.php: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Bump Pasajero.php',
        'buscar' => [
            ' * @version   1.5piloto.78l',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79d',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md: agregar §1.5.12
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.5.12',
        'buscar' => [
            '### 1.6 Pendientes de la línea',
        ],
        'reemplazar' => [
            '#### 1.5.12 obtener_pasajero_por_dni con contexto (v79d)',
            '',
            '**Síntoma:** logueado como terminal, en Clientes, el',
            'botón "Ver pasajes" aparecía (fix v79c), pero al',
            'abrirlo el modal decía "Sin pasajes registrados"',
            'aunque el pasajero sí tuviera ventas.',
            '',
            '**Causa:** `obtener_pasajero_por_dni` iteraba sobre la',
            'raíz `usuarios` para buscar las ventas del pasajero.',
            'Con carga parcial del terminal, `usuarios` no está en',
            'memoria: `$raiz_usuarios` es null, el bloque no corre,',
            'y `$ventas` queda vacío.',
            '',
            'Es uno de los 7 helpers B3.3c (ver §1.10) que iteran',
            'sobre todos los dueños. La lista completa:',
            '',
            '- `Venta.php`: `_buscar_venta_por_id` (fallback),',
            '  `obtener_cancelacion_por_id`.',
            '- `Liquidacion.php`: `obtener_liquidacion_por_id`.',
            '- `Rendicion.php`: `aceptar_ajuste_rendicion`,',
            '  `obtener_rendicion_por_id`.',
            '- `Pasajero.php`: `obtener_pasajero_por_dni`',
            '  (rama del historial de ventas).',
            '',
            '**Solución parcial:** `obtener_pasajero_por_dni` ahora',
            'recibe `$nodo_contexto`. Si viene, navega el árbol del',
            'contexto (parametrizado si es compartido). Si no,',
            'mantiene el comportamiento actual (iterar `usuarios`).',
            '',
            '**Pendiente:** los otros 5 helpers siguen igual. Se',
            'resuelven cuando aparezca dolor real (por ejemplo,',
            'que el terminal intente cancelar desde el plugin y',
            'el helper no encuentre la venta), o cuando lleguen',
            'los iteradores con ID especial (§1.10).',
            '',
            '**Nota secundaria:** `obtener_reservas_de_pasajero`',
            'sigue llamándose sin contexto desde',
            '`obtener_pasajero_por_dni`. Con carga parcial,',
            'devuelve vacío para el terminal. **Es correcto**:',
            'el terminal no debe ver las reservas del equipo.',
            '',
            '### 1.6 Pendientes de la línea',
        ],
    ],

    // ============================================================
    // 4. plan_actual.md: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: bump a 79d',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79c.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79d.',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Fix obtener_pasajero_por_dni ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
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
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";