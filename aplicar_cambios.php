<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77u (Fase B3.3b).
 *   - Pasajero.php: rama sin contexto de
 *     obtener_contenedor_pasajeros_dueno → us_<nombre>.
 *   - ViajeAsientos.php: 3 resoluciones de terminal → us_<nombre>.
 *   - Anotar los pendientes de rediseño (B3.3c) en plan_actual.md.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // 1. Pasajero.php — rama sin contexto
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero: contenedor sin contexto via us_',
        'buscar' => [
            '    if ($nodo_contexto !== null) {',
            '        $nodo_dueno = $nodo_contexto;',
            '    } else {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) return null;',
            '        $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    }',
            '    if (!$nodo_dueno) return null;',
        ],
        'reemplazar' => [
            '    if ($nodo_contexto !== null) {',
            '        $nodo_dueno = $nodo_contexto;',
            '    } else {',
            '        // Fase B3.3b: ID especial directo (sin la raíz `usuarios`).',
            '        $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    }',
            '    if (!$nodo_dueno) return null;',
        ],
    ],

    // Bump de version en Pasajero.php
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero: bump a 1.5piloto.77u',
        'buscar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.77i',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.77u',
        ],
    ],

    // ============================================================
    // 2. ViajeAsientos.php — seleccionar_asiento_micro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: seleccionar_asiento_micro via us_',
        'buscar' => [
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;',
            '    if (!$nodo_terminal) return [\'exito\' => false, \'error\' => \'Terminal no encontrada\'];',
            '',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
        ],
        'reemplazar' => [
            '    // Fase B3.3b: ID especial directo.',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if (!$nodo_terminal) return [\'exito\' => false, \'error\' => \'Terminal no encontrada\'];',
            '',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
        ],
    ],

    // ============================================================
    // 3. ViajeAsientos.php — deseleccionar_asiento_micro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: deseleccionar_asiento_micro via us_',
        'buscar' => [
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    $nodo_terminal = $raiz_usuarios ? $raiz_usuarios->adyacente($nombre_terminal) : null;',
            '    if ($nodo_terminal) {',
            '        $venta_actual = $nodo_terminal->adyacente(\'venta_actual\');',
        ],
        'reemplazar' => [
            '    // Fase B3.3b: ID especial directo.',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if ($nodo_terminal) {',
            '        $venta_actual = $nodo_terminal->adyacente(\'venta_actual\');',
        ],
    ],

    // ============================================================
    // 4. ViajeAsientos.php — obtener_estados_asientos_micro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: obtener_estados_asientos_micro via us_',
        'buscar' => [
            '                if ($seleccionado_por_usuario !== null) {',
            '                    $raiz_usuarios_sel = Nodo::nodo_por_id(\'usuarios\');',
            '                    if ($raiz_usuarios_sel) {',
            '                        $nodo_sel_usuario = $raiz_usuarios_sel->adyacente($seleccionado_por_usuario);',
            '                        if ($nodo_sel_usuario && $nodo_sel_usuario->adyacente(\'nombre_real\')) {',
            '                            $seleccionado_por_real = $nodo_sel_usuario->adyacente(\'nombre_real\')->dato();',
            '                        }',
            '                    }',
            '                }',
        ],
        'reemplazar' => [
            '                if ($seleccionado_por_usuario !== null) {',
            '                    // Fase B3.3b: ID especial directo.',
            '                    $nodo_sel_usuario = Nodo::nodo_por_id(\'us_\' . $seleccionado_por_usuario);',
            '                    if ($nodo_sel_usuario && $nodo_sel_usuario->adyacente(\'nombre_real\')) {',
            '                        $seleccionado_por_real = $nodo_sel_usuario->adyacente(\'nombre_real\')->dato();',
            '                    }',
            '                }',
        ],
    ],

    // Bump de version en ViajeAsientos.php
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: bump a 1.5piloto.77u',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77h',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77u',
        ],
    ],

    // ============================================================
    // 5. plan_actual.md — tanda actual + pendientes B3.3c
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77u + pendientes B3.3c',
        'buscar' => [
            '**Tanda actual:** v77t (B3.3a).',
            '',
            '**B3.3a (v77t).** `Liquidacion.php` y `Rendicion.php`:',
            '3 y 4 resoluciones de dueño/terminal pasadas a',
            '`us_<nombre>`. Quedan sin tocar los 3 helpers que iteran',
            'sobre todos los dueños buscando por id',
            '(`obtener_liquidacion_por_id`, `aceptar_ajuste_rendicion`,',
            '`obtener_rendicion_por_id`); requieren diseño y son',
            'B3.3c.',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77u (B3.3b).',
            '',
            '**B3.3a (v77t).** `Liquidacion.php` y `Rendicion.php`:',
            '3 y 4 resoluciones de dueño/terminal pasadas a',
            '`us_<nombre>`.',
            '',
            '**B3.3b (v77u).** `Pasajero.php`: rama sin contexto de',
            '`obtener_contenedor_pasajeros_dueno` → `us_<nombre>`.',
            '`ViajeAsientos.php`: 3 resoluciones de terminal',
            '(`seleccionar_asiento_micro`, `deseleccionar_asiento_micro`,',
            '`obtener_estados_asientos_micro`) → `us_<nombre>`.',
            '',
            '**B3.3c — pendientes de rediseño.** Los siguientes usos',
            'iteran sobre TODOS los dueños porque no reciben contexto.',
            'Requieren decisión de diseño (¿pasar contexto desde el',
            'enrutador? ¿crear índices auxiliares por id?). No se',
            'resuelven con `us_<nombre>` directo:',
            '',
            '- `Venta.php`: `_buscar_venta_por_id` (rama sin filtro)',
            '  y `obtener_cancelacion_por_id`.',
            '- `Impresion.php` (2): resuelven dueño/viaje para armar',
            '  la impresión.',
            '- `Liquidacion.php`: `obtener_liquidacion_por_id`.',
            '- `Rendicion.php`: `aceptar_ajuste_rendicion` y',
            '  `obtener_rendicion_por_id`.',
            '- `Pasajero.php`: `obtener_pasajero_por_dni` (arma',
            '  historial de ventas iterando todos los dueños).',
            '- `Vehiculo.php` (6): las 6 funciones buscan la empresa',
            '  entre todos los dueños.',
            '- `Migraciones/Funciones.php` (2) y `Comandos.php` (1):',
            '  pueden reescribirse con `Nodo::por_cada_nodo_ejecutar`',
            '  (B3.3e, cosmético).',
            '',
            '**B3.3d — Vehiculo.php.** El más grande. Requiere pasar',
            'el dueño desde el Enrutador a cada una de las 6 funciones',
            '(`listar_vehiculos_de_empresa`, `agregar_vehiculo`,',
            '`actualizar_vehiculo`, `actualizar_configuracion_vehiculo`,',
            '`eliminar_vehiculo`, `subir_foto_vehiculo`).',
        ],
    ],

    // ============================================================
    // 6. prompt_piloto.md — agregar v77u
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77u',
        'buscar' => [
            '- **v77t** (Fase B3.3a): `Liquidacion.php` y `Rendicion.php`',
        ],
        'reemplazar' => [
            '- **v77u** (Fase B3.3b): `Pasajero.php` rama sin contexto',
            '  de `obtener_contenedor_pasajeros_dueno` → `us_<nombre>`.',
            '  `ViajeAsientos.php`: 3 resoluciones de terminal →',
            '  `us_<nombre>`. Se anotan en `plan_actual.md` los',
            '  pendientes de rediseño (B3.3c) que iteran sobre todos',
            '  los dueños.',
            '- **v77t** (Fase B3.3a): `Liquidacion.php` y `Rendicion.php`',
        ],
    ],

    // ============================================================
    // 7. index.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77u',
        'buscar' => [
            ' * @version   1.5piloto.77t',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77u',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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