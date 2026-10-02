<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74a:
 * - Fix del retroactivo de opciones de cobro. En v74, los helpers
 *   `_aplicar_retroactivo_a_ventas_del_viaje` y
 *   `_aplicar_retroactivo_a_ventas_de_terminal` leían la config
 *   nueva con `_config_pago_resuelta_para_venta`, que devuelve el
 *   `opciones_cobro` congelado de la venta. Resultado: si la venta
 *   ya tenía opciones_cobro, nunca veía el cambio. Fix: resolver
 *   con `_config_pago_resuelta_para_viaje_terminal`, que mira el
 *   viaje y el override del TerminalViaje en vivo.
 *
 * Asume v74 ya aplicado.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
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
    // Aplicacion/Ventas/Venta.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump de version 74 a 74a',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: fix _aplicar_retroactivo_a_ventas_del_viaje',
        'buscar' => [
            'function _aplicar_retroactivo_a_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_del_viaje($nombre_dueno, $nombre_viaje, function(Nodo $v) use ($snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            '                $nueva = _config_pago_resuelta_para_venta($v);',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $nueva = _config_pago_resuelta_para_venta($v);',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
        ],
        'reemplazar' => [
            'function _aplicar_retroactivo_a_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_del_viaje($nombre_dueno, $nombre_viaje, function(Nodo $v) use ($nombre_dueno, $nombre_viaje, $snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            '        // Resolver la config NUEVA del viaje + override del',
            '        // TerminalViaje en vivo, ignorando el opciones_cobro que',
            '        // ya tiene la venta.',
            "        \$nodo_terminal = \$v->adyacente('terminal');",
            "        \$nombre_terminal = \$nodo_terminal ? \$nodo_terminal->dato() : '';",
            '        $nueva = _config_pago_resuelta_para_viaje_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: fix _aplicar_retroactivo_a_ventas_de_terminal',
        'buscar' => [
            'function _aplicar_retroactivo_a_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, function(Nodo $v) use ($snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            '                $nueva = _config_pago_resuelta_para_venta($v);',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $nueva = _config_pago_resuelta_para_venta($v);',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
        ],
        'reemplazar' => [
            'function _aplicar_retroactivo_a_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, function(Nodo $v) use ($nombre_dueno, $nombre_viaje, $nombre_terminal, $snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            '        // Resolver la config NUEVA del viaje + override del',
            '        // TerminalViaje en vivo, ignorando el opciones_cobro que',
            '        // ya tiene la venta.',
            '        $nueva = _config_pago_resuelta_para_viaje_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74a',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74 (Bug 1:',
            'opciones de cobro congeladas al vender + retroactivo opcional al',
            'editar las condiciones de pago del viaje o el override de',
            'TerminalViaje).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74a (fix del',
            'retroactivo: la config nueva se resuelve en vivo, no desde el',
            '`opciones_cobro` ya congelado de la venta).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74a al historial',
        'buscar' => [
            '  check, con la nueva si se tildó. Backend y frontend.',
        ],
        'reemplazar' => [
            '  check, con la nueva si se tildó. Backend y frontend.',
            '- **v74a**: fix del retroactivo de opciones de cobro. En v74,',
            '  `_aplicar_retroactivo_a_ventas_del_viaje` y',
            '  `_aplicar_retroactivo_a_ventas_de_terminal` leían la config',
            '  nueva con `_config_pago_resuelta_para_venta`, que devuelve',
            '  el `opciones_cobro` congelado de la venta. Resultado: si la',
            '  venta ya tenía `opciones_cobro`, el retroactivo no hacía',
            '  nada (los `permite_*` no cambiaban). Fix: resolver con',
            '  `_config_pago_resuelta_para_viaje_terminal`, que mira el',
            '  viaje + override del TerminalViaje en vivo, ignorando el',
            '  `opciones_cobro` de la venta. El flujo de migración de',
            '  ventas viejas sin `opciones_cobro` no estaba afectado',
            '  (resolvía en vivo, que post-save ya devolvía la config',
            '  nueva). Solo backend.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74a',
        'buscar' => [
            '  decisión de aplicar retroactivo se toma al editar las',
            '  condiciones de pago (del viaje o del override de TerminalViaje).',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '  decisión de aplicar retroactivo se toma al editar las',
            '  condiciones de pago (del viaje o del override de TerminalViaje).',
            '- Cerramos en v74a el fix del retroactivo: la config nueva se',
            '  resuelve en vivo (viaje + terminal), ignorando el',
            '  `opciones_cobro` que ya tiene la venta. El flujo de migración',
            '  de ventas viejas no estaba afectado.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado del proyecto al cierre v74a',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74 (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74a (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes.',
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