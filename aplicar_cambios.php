<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76w (Fase B2.3.2 del modelo topológico).
 *   - Arbol.php: nuevo helper _nombres_arbol_para_contexto.
 *   - Venta.php: las funciones de lectura de ventas calculan los
 *     nombres de enlace a partir del contexto y los pasan a las
 *     funciones de Arbol.php. Como todavía no hay compartidos
 *     marcados (_es_compartido), el helper devuelve null y los
 *     nombres son default. Refactor sin cambio de comportamiento.
 *   - Viaje.php: idem para _construir_indice_ventas_por_viaje.
 *   - index.php: bump.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Arbol.php — helper _nombres_arbol_para_contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'miscelaneas/Arbol.php',
        'descripcion' => 'Arbol.php: helper _nombres_arbol_para_contexto',
        'buscar' => [
            'function _arbol_nombres(?array $nombres): array {',
        ],
        'reemplazar' => [
            '/**',
            ' * Devuelve el array de nombres de enlace que un terminal',
            ' * debe usar para recorrer el árbol de un contenedor, según',
            ' * el contexto.',
            ' *',
            ' * Fase B2.3.2 del modelo topológico (v76w). Reglas:',
            ' * - Si no hay contexto (dueño o admin), devuelve null',
            ' *   → las funciones de árbol usan los nombres default.',
            ' * - Si el contexto tiene el marcador `_es_compartido`,',
            ' *   devuelve los nombres parametrizados con el sufijo del',
            ' *   terminal (p_<terminal>, hd_<terminal>, hmi_<terminal>).',
            ' * - Si el contexto no es un compartido (todavía), null.',
            ' *',
            ' * Mientras no existan compartidos marcados, esta función',
            ' * siempre devuelve null y el comportamiento es idéntico al',
            ' * actual. Los marcadores los agrega la migración de B2.3.3.',
            ' *',
            ' * @param Nodo|null $nodo_contexto',
            ' * @param string    $nombre_terminal',
            ' * @return array|null',
            ' */',
            'function _nombres_arbol_para_contexto(?Nodo $nodo_contexto, string $nombre_terminal): ?array {',
            '    if ($nodo_contexto === null || $nombre_terminal === \'\') return null;',
            '    if (!$nodo_contexto->adyacente(\'_es_compartido\')) return null;',
            '    return [',
            '        \'p\'   => \'p_\'   . $nombre_terminal,',
            '        \'hd\'  => \'hd_\'  . $nombre_terminal,',
            '        \'hmi\' => \'hmi_\' . $nombre_terminal,',
            '    ];',
            '}',
            '',
            'function _arbol_nombres(?array $nombres): array {',
        ],
    ],

    // ============================================================
    // Venta.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.76w',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.76s',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.76w',
        ],
    ],

    // ============================================================
    // Venta.php — listar_ventas_por_terminal con nombres
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: listar_ventas_por_terminal con nombres',
        'buscar' => [
            '    $contenedor = obtener_contenedor_ventas_dueno((string)$contexto->dato(), $contexto);',
            '    if (!$contenedor) return [];',
            '',
            '    $ventas = [];',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $seg++;',
            '        $nodo_terminal = $actual->adyacente(\'terminal\');',
            '        if (!$nodo_terminal || $nodo_terminal->dato() !== $nombre_terminal) {',
            '            $actual = hd($actual);',
            '            continue;',
            '        }',
            '        $ventas[] = formatear_venta_resumida($actual);',
            '        $actual = hd($actual);',
            '    }',
            '    return $ventas;',
        ],
        'reemplazar' => [
            '    $contenedor = obtener_contenedor_ventas_dueno((string)$contexto->dato(), $contexto);',
            '    if (!$contenedor) return [];',
            '',
            '    // Fase B2.3.2 (v76w): calcular los nombres de enlace a',
            '    // partir del contexto. Mientras el contexto no sea un',
            '    // compartido marcado, devuelve null y usa los default.',
            '    $nombres = _nombres_arbol_para_contexto($contexto, $nombre_terminal);',
            '',
            '    $ventas = [];',
            '    $actual = hmi($contenedor, $nombres);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $seg++;',
            '        $nodo_terminal = $actual->adyacente(\'terminal\');',
            '        if (!$nodo_terminal || $nodo_terminal->dato() !== $nombre_terminal) {',
            '            $actual = hd($actual, $nombres);',
            '            continue;',
            '        }',
            '        $ventas[] = formatear_venta_resumida($actual);',
            '        $actual = hd($actual, $nombres);',
            '    }',
            '    return $ventas;',
        ],
    ],

    // ============================================================
    // Venta.php — _buscar_venta_por_id (rama con contexto) con nombres
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: _buscar_venta_por_id con nombres',
        'buscar' => [
            '        $contexto = _contexto_terminal($nombre_terminal);',
            '        if (!$contexto) return [null, \'\'];',
            '        $nombre_dueno = (string)$contexto->dato();',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno, $contexto);',
            '        if (!$cont) return [null, \'\'];',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return [$actual, $nombre_dueno];',
            '            }',
            '            $actual = hd($actual);',
            '            $seg++;',
            '        }',
            '        return [null, \'\'];',
        ],
        'reemplazar' => [
            '        $contexto = _contexto_terminal($nombre_terminal);',
            '        if (!$contexto) return [null, \'\'];',
            '        $nombre_dueno = (string)$contexto->dato();',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno, $contexto);',
            '        if (!$cont) return [null, \'\'];',
            '        $nombres = _nombres_arbol_para_contexto($contexto, $nombre_terminal);',
            '        $actual = hmi($cont, $nombres);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return [$actual, $nombre_dueno];',
            '            }',
            '            $actual = hd($actual, $nombres);',
            '            $seg++;',
            '        }',
            '        return [null, \'\'];',
        ],
    ],

    // ============================================================
    // Venta.php — _recorrer_ventas_del_viaje con contexto opcional
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: _recorrer_ventas_del_viaje con contexto',
        'buscar' => [
            'function _recorrer_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, callable $callback): void {',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor) return;',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $nodo_viaje = $actual->adyacente(\'viaje\');',
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje) {',
            '            $callback($actual);',
            '        }',
            '        $actual = hd($actual);',
            '        $seg++;',
            '    }',
            '}',
        ],
        'reemplazar' => [
            'function _recorrer_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, callable $callback, ?Nodo $nodo_contexto = null, ?string $nombre_terminal = null): void {',
            '    // Fase B2.3.2 (v76w): contexto opcional + nombres',
            '    // parametrizados. Si no hay contexto, comportamiento',
            '    // idéntico al actual.',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return;',
            '    $nombres = _nombres_arbol_para_contexto($nodo_contexto, $nombre_terminal ?? \'\');',
            '    $actual = hmi($contenedor, $nombres);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $nodo_viaje = $actual->adyacente(\'viaje\');',
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje) {',
            '            $callback($actual);',
            '        }',
            '        $actual = hd($actual, $nombres);',
            '        $seg++;',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // Venta.php — _recorrer_ventas_de_terminal con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: _recorrer_ventas_de_terminal con contexto',
        'buscar' => [
            'function _recorrer_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, callable $callback): void {',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor) return;',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $nodo_viaje = $actual->adyacente(\'viaje\');',
            '        $nodo_terminal = $actual->adyacente(\'terminal\');',
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje',
            '            && $nodo_terminal && $nodo_terminal->dato() === $nombre_terminal) {',
            '            $callback($actual);',
            '        }',
            '        $actual = hd($actual);',
            '        $seg++;',
            '    }',
            '}',
        ],
        'reemplazar' => [
            'function _recorrer_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, callable $callback, ?Nodo $nodo_contexto = null): void {',
            '    // Fase B2.3.2 (v76w): contexto opcional + nombres',
            '    // parametrizados.',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return;',
            '    $nombres = _nombres_arbol_para_contexto($nodo_contexto, $nombre_terminal);',
            '    $actual = hmi($contenedor, $nombres);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $nodo_viaje = $actual->adyacente(\'viaje\');',
            '        $nodo_terminal = $actual->adyacente(\'terminal\');',
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje',
            '            && $nodo_terminal && $nodo_terminal->dato() === $nombre_terminal) {',
            '            $callback($actual);',
            '        }',
            '        $actual = hd($actual, $nombres);',
            '        $seg++;',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // Viaje.php — _construir_indice_ventas_por_viaje con nombres
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: _construir_indice_ventas_por_viaje con nombres',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76r',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76w',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: indice ventas con nombres parametrizados',
        'buscar' => [
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor_ventas) return $indice;',
            '',
            '    $venta_iter = hmi($contenedor_ventas);',
            '    $seg = 0;',
            '    while ($venta_iter && $seg < 2000) {',
            '        $nodo_viaje_venta = $venta_iter->adyacente(\'viaje\');',
            '        if ($nodo_viaje_venta) {',
            '            $nombre_viaje = $nodo_viaje_venta->dato();',
            '            $indice[\'tiene_ventas\'][$nombre_viaje] = true;',
            '',
            '            if ($nombre_terminal !== null) {',
            '                $nodo_terminal_venta = $venta_iter->adyacente(\'terminal\');',
            '                $nodo_micro_venta = $venta_iter->adyacente(\'micro\');',
            '                if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal',
            '                    && $nodo_micro_venta) {',
            '                    $micro_id = $nodo_micro_venta->id();',
            '                    $cabeza = $venta_iter->adyacente(\'asientos\');',
            '                    $cantidad = 0;',
            '                    if ($cabeza) {',
            '                        $asiento = $cabeza->adyacente(\'primer\');',
            '                        $seg2 = 0;',
            '                        while ($asiento && $seg2 < 100) {',
            '                            $cantidad++;',
            '                            $asiento = $asiento->adyacente(\'siguiente\');',
            '                            $seg2++;',
            '                        }',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje] = [];',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] = [];',
            '                    }',
            '                    $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] =',
            '                        ($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] ?? 0) + $cantidad;',
            '                }',
            '            }',
            '        }',
            '        $venta_iter = hd($venta_iter);',
            '        $seg++;',
            '    }',
            '    return $indice;',
        ],
        'reemplazar' => [
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor_ventas) return $indice;',
            '',
            '    // Fase B2.3.2 (v76w): nombres de enlace calculados a partir',
            '    // del contexto. Si el contexto no es un compartido marcado,',
            '    // devuelve null y usa los default.',
            '    $nombres = _nombres_arbol_para_contexto($nodo_contexto, $nombre_terminal ?? \'\');',
            '',
            '    $venta_iter = hmi($contenedor_ventas, $nombres);',
            '    $seg = 0;',
            '    while ($venta_iter && $seg < 2000) {',
            '        $nodo_viaje_venta = $venta_iter->adyacente(\'viaje\');',
            '        if ($nodo_viaje_venta) {',
            '            $nombre_viaje = $nodo_viaje_venta->dato();',
            '            $indice[\'tiene_ventas\'][$nombre_viaje] = true;',
            '',
            '            if ($nombre_terminal !== null) {',
            '                $nodo_terminal_venta = $venta_iter->adyacente(\'terminal\');',
            '                $nodo_micro_venta = $venta_iter->adyacente(\'micro\');',
            '                if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal',
            '                    && $nodo_micro_venta) {',
            '                    $micro_id = $nodo_micro_venta->id();',
            '                    $cabeza = $venta_iter->adyacente(\'asientos\');',
            '                    $cantidad = 0;',
            '                    if ($cabeza) {',
            '                        $asiento = $cabeza->adyacente(\'primer\');',
            '                        $seg2 = 0;',
            '                        while ($asiento && $seg2 < 100) {',
            '                            $cantidad++;',
            '                            $asiento = $asiento->adyacente(\'siguiente\');',
            '                            $seg2++;',
            '                        }',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje] = [];',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] = [];',
            '                    }',
            '                    $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] =',
            '                        ($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] ?? 0) + $cantidad;',
            '                }',
            '            }',
            '        }',
            '        $venta_iter = hd($venta_iter, $nombres);',
            '        $seg++;',
            '    }',
            '    return $indice;',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76w',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76v',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76w',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — actualizar
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: última actualización a v76w',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.76v',
            '(reorganización de prompts + inicio de la Fase B2.3).',
            'Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,',
            'ViajeAsientos, Empresa). Próximo paso: **Fase B2.3**, con un',
            'cambio de enfoque respecto del plan original. En lugar del',
            '"compartido plano" (opción A original), se va con la **opción D**:',
            'árboles paralelos con nombres de enlace parametrizados en',
            '`miscelaneas/Arbol.php`. Detalle en §2.3.)',
        ],
        'reemplazar_mal' => [],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: v76w en el estado',
        'buscar' => [
            '**Tanda actual:** v76v (reorganización de prompts).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v76w (Fase B2.3.2, refactor de funciones',
            'para árboles parametrizados).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: B2.3.1 y B2.3.2 completadas',
        'buscar' => [
            '- **B2.3.1** — Parametrizar `miscelaneas/Arbol.php`. Las',
            '  funciones `_hmi`, `_hd`, `hmi`, `hd`, `p`, `eliminar_hmi` y',
            '  `eliminar_hd` aceptan un `?array $nombres = null`. Si es',
            '  null, usan los nombres default (`hmi`/`hd`/`p`). Refactor',
            '  sin cambio de comportamiento.',
            '- **B2.3.2** — Ajustar las funciones del piloto para que el',
            '  terminal use los nombres parametrizados. Sin repuntar.',
        ],
        'reemplazar' => [
            '- **B2.3.1** — Parametrizar `miscelaneas/Arbol.php`.',
            '  **Completada** (v76v).',
            '- **B2.3.2** — Ajustar las funciones del piloto para que el',
            '  terminal use los nombres parametrizados. **Completada**',
            '  (v76w): helper `_nombres_arbol_para_contexto`, aplicado en',
            '  `listar_ventas_por_terminal`, `_buscar_venta_por_id` (rama',
            '  con contexto), `_recorrer_ventas_del_viaje` y',
            '  `_recorrer_ventas_de_terminal` (Venta.php) y',
            '  `_construir_indice_ventas_por_viaje` (Viaje.php). Como',
            '  todavía no hay compartidos marcados, el helper devuelve',
            '  null y los nombres son default. Sin cambio de comportamiento.',
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