<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76t (Fase B2.2.3 del modelo topológico).
 *   - ViajeAsientos.php: contexto opcional en las funciones que
 *     navegan por el contenedor de viajes del dueño.
 *   - index.php: bump.
 *   - prompts/prompt_piloto.md: registro.
 *
 * Refactor sin cambio de comportamiento. El enrutador todavía no
 * pasa el contexto. Prepara B2.3.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // ViajeAsientos.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos.php: bump @version a 1.5piloto.76t',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74x',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76t',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — _dni_asignado_en_viaje con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: _dni_asignado_en_viaje con contexto',
        'buscar' => [
            'function _dni_asignado_en_viaje(string $nombre_dueno, string $nombre_viaje, string $dni): bool {',
            '    $dni_norm = normalizar_dni($dni);',
            '    if ($dni_norm === \'\') return false;',
            '',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return false;',
        ],
        'reemplazar' => [
            'function _dni_asignado_en_viaje(string $nombre_dueno, string $nombre_viaje, string $dni, ?Nodo $nodo_contexto = null): bool {',
            '    $dni_norm = normalizar_dni($dni);',
            '    if ($dni_norm === \'\') return false;',
            '',
            '    // Fase B2.2.3 (v76t): contexto opcional. Si viene, navega por',
            '    // ahí en lugar de resolver `usuarios → dueño`.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return false;',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — reservar_asiento_micro con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: reservar_asiento_micro con contexto',
        'buscar' => [
            'function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero = []): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero = [], ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.2.3 (v76t): contexto opcional.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: reservar_asiento_micro usa contexto en _dni_asignado',
        'buscar' => [
            '        if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero[\'dni\'])) {',
            '            return [\'exito\' => false, \'error\' => \'El DNI ya está asignado a otro asiento de este viaje\'];',
            '        }',
            '    }',
            '',
            '    // === Reservar ===',
        ],
        'reemplazar' => [
            '        if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero[\'dni\'], $nodo_contexto)) {',
            '            return [\'exito\' => false, \'error\' => \'El DNI ya está asignado a otro asiento de este viaje\'];',
            '        }',
            '    }',
            '',
            '    // === Reservar ===',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — asignar_pasajero_a_reserva con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: asignar_pasajero_a_reserva con contexto',
        'buscar' => [
            'function asignar_pasajero_a_reserva(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function asignar_pasajero_a_reserva(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero, ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.2.3 (v76t): contexto opcional.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: asignar_pasajero usa contexto en _dni_asignado',
        'buscar' => [
            '    if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero[\'dni\'])) {',
            '        return [\'exito\' => false, \'error\' => \'El DNI ya está asignado a otro asiento de este viaje\'];',
            '    }',
            '',
            '    // === Crear/reutilizar pasajero y enlazar ===',
        ],
        'reemplazar' => [
            '    if (_dni_asignado_en_viaje($nombre_dueno, $nombre_viaje, $datos_pasajero[\'dni\'], $nodo_contexto)) {',
            '        return [\'exito\' => false, \'error\' => \'El DNI ya está asignado a otro asiento de este viaje\'];',
            '    }',
            '',
            '    // === Crear/reutilizar pasajero y enlazar ===',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — liberar_reserva_asiento_micro con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: liberar_reserva_asiento_micro con contexto',
        'buscar' => [
            'function liberar_reserva_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function liberar_reserva_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.2.3 (v76t): contexto opcional.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — obtener_estados_asientos_micro con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: obtener_estados_asientos_micro con contexto',
        'buscar' => [
            'function obtener_estados_asientos_micro(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function obtener_estados_asientos_micro(string $nombre_viaje, string $nombre_micro, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.2.3 (v76t): contexto opcional.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — seleccionar_asiento_micro navega por contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: seleccionar_asiento_micro navega por contexto',
        'buscar' => [
            'function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
            '    // Fase B2.2.3 (v76t): el terminal navega por su contexto',
            '    // (hoy el nodo del dueño, tras B2.3 el compartido).',
            '    $nodo_contexto = _contexto_terminal($nombre_terminal);',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — deseleccionar_asiento_micro navega por contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos: deseleccionar_asiento_micro navega por contexto',
        'buscar' => [
            'function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
            '    // Fase B2.2.3 (v76t): el terminal navega por su contexto.',
            '    $nodo_contexto = _contexto_terminal($nombre_terminal);',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76t',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76s',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76t',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76s',
            '(Fase B2.2.2 del modelo topológico: Venta.php. Contexto',
            'opcional en `obtener_contenedor_ventas_dueno`; filtro opcional',
            'por terminal en `_buscar_venta_por_id`, `obtener_venta_por_id`,',
            '`pagar_cupon_venta`, `cancelar_venta` y `obtener_info_cancelacion`;',
            '`listar_ventas_por_terminal` y `confirmar_venta_actual` navegan',
            'por el contexto del terminal. Refactor sin cambio de',
            'comportamiento: el enrutador todavía no pasa el `$nombre_terminal`,',
            'así que la búsqueda sigue siendo global. La preparación de B2.3',
            'está completa.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76t',
            '(Fase B2.2.3 del modelo topológico: ViajeAsientos.php.',
            'Contexto opcional (`?Nodo $nodo_contexto`) en las funciones',
            'que navegan por el contenedor de viajes del dueño:',
            '`_dni_asignado_en_viaje`, `reservar_asiento_micro`,',
            '`asignar_pasajero_a_reserva`, `liberar_reserva_asiento_micro`,',
            '`obtener_estados_asientos_micro`. `seleccionar_asiento_micro`',
            'y `deseleccionar_asiento_micro` resuelven su contexto vía',
            '`_contexto_terminal($nombre_terminal)`. Refactor sin cambio',
            'de comportamiento: el enrutador todavía no pasa el contexto.).',
            'Antes: v1.5piloto.76s',
            '(Fase B2.2.2 del modelo topológico: Venta.php. Contexto',
            'opcional en `obtener_contenedor_ventas_dueno`; filtro opcional',
            'por terminal en `_buscar_venta_por_id`, `obtener_venta_por_id`,',
            '`pagar_cupon_venta`, `cancelar_venta` y `obtener_info_cancelacion`;',
            '`listar_ventas_por_terminal` y `confirmar_venta_actual` navegan',
            'por el contexto del terminal. Refactor sin cambio de',
            'comportamiento: el enrutador todavía no pasa el `$nombre_terminal`,',
            'así que la búsqueda sigue siendo global. La preparación de B2.3',
            'está completa.).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76t',
        'buscar' => [
            '- **v76s**: Fase B2.2.2 del modelo topológico (Venta.php).',
        ],
        'reemplazar' => [
            '- **v76t**: Fase B2.2.3 del modelo topológico',
            '  (ViajeAsientos.php). Contexto opcional (`?Nodo $nodo_contexto`)',
            '  en las funciones que navegan por el contenedor de viajes del',
            '  dueño: `_dni_asignado_en_viaje`, `reservar_asiento_micro`,',
            '  `asignar_pasajero_a_reserva`, `liberar_reserva_asiento_micro`,',
            '  `obtener_estados_asientos_micro`. `seleccionar_asiento_micro`',
            '  y `deseleccionar_asiento_micro` resuelven el contexto interno',
            '  con `_contexto_terminal($nombre_terminal)`. Refactor sin cambio',
            '  de comportamiento: el enrutador todavía no pasa el contexto a',
            '  las funciones de reserva; la preparación para B2.3 queda',
            '  avanzada.',
            '- **v76s**: Fase B2.2.2 del modelo topológico (Venta.php).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 bullet
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet v76t',
        'buscar' => [
            '- Cerramos en v76s la Fase B2.2.2 del modelo topológico',
        ],
        'reemplazar' => [
            '- Cerramos en v76t la Fase B2.2.3 del modelo topológico',
            '  (ViajeAsientos.php). Contexto opcional (`?Nodo $nodo_contexto`)',
            '  en `_dni_asignado_en_viaje`, `reservar_asiento_micro`,',
            '  `asignar_pasajero_a_reserva`, `liberar_reserva_asiento_micro`',
            '  y `obtener_estados_asientos_micro`. `seleccionar_asiento_micro`',
            '  y `deseleccionar_asiento_micro` resuelven el contexto vía',
            '  `_contexto_terminal($nombre_terminal)`. Refactor sin cambio',
            '  de comportamiento. Pendiente: B2.2.4 (Empresa.php). Después:',
            '  B2.3 (repuntar `us_termX → dueno` al compartido).',
            '- Cerramos en v76s la Fase B2.2.2 del modelo topológico',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §13 estado
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado al cierre a v76t',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76s (framework 1.5i.7l).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76t (framework 1.5i.7l).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76t al bloque de estado',
        'buscar' => [
            'v76s: Fase B2.2.2 (Venta.php). Contexto opcional en',
        ],
        'reemplazar' => [
            'v76t: Fase B2.2.3 (ViajeAsientos.php). Contexto opcional en',
            'las funciones que navegan por el contenedor de viajes del',
            'dueño. `seleccionar_asiento_micro` y `deseleccionar_asiento_micro`',
            'resuelven su contexto vía `_contexto_terminal`. Refactor sin',
            'cambio de comportamiento.',
            'v76s: Fase B2.2.2 (Venta.php). Contexto opcional en',
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