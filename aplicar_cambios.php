<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77h (Fase B2.3.5b.2 del modelo topológico).
 *   - ViajeAsientos.php: `cambiar_asiento_pasaje` acepta un
 *     `?Nodo $nodo_contexto = null` y lo pasa a
 *     `obtener_contenedor_viajes_dueno`. Resuelve el solicitante
 *     por ID especial `us_<nombre>` en vez de `nodo_por_id('usuarios')`.
 *   - Enrutador.php: `viajes/reservar_asiento`,
 *     `viajes/asignar_pasajero_reserva`, `viajes/liberar_reserva_asiento`
 *     y `viajes/cambiar_asiento` pasan el contexto del solicitante
 *     terminal.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Sin cambio de comportamiento hoy (los nodos son los mismos). Se
 * activa cuando el framework haga carga parcial por contextos.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // ViajeAsientos.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'ViajeAsientos.php: bump @version a 1.5piloto.77h',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77b',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77h',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — firma de cambiar_asiento_pasaje con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'cambiar_asiento_pasaje: firma con nodo_contexto',
        'buscar' => [
            'function cambiar_asiento_pasaje(',
            '    string $nombre_viaje,',
            '    string $nombre_micro,',
            '    string $fila_vieja,',
            '    string $columna_vieja,',
            '    string $fila_nueva,',
            '    string $columna_nueva,',
            '    string $nombre_dueno,',
            '    string $nombre_solicitante,',
            '    bool $dejar_reservado_viejo = false',
            '): array {',
        ],
        'reemplazar' => [
            'function cambiar_asiento_pasaje(',
            '    string $nombre_viaje,',
            '    string $nombre_micro,',
            '    string $fila_vieja,',
            '    string $columna_vieja,',
            '    string $fila_nueva,',
            '    string $columna_nueva,',
            '    string $nombre_dueno,',
            '    string $nombre_solicitante,',
            '    bool $dejar_reservado_viejo = false,',
            '    ?Nodo $nodo_contexto = null',
            '): array {',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — cambiar_asiento_pasaje usa el contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'cambiar_asiento_pasaje: usar nodo_contexto',
        'buscar' => [
            '    if ($fila_vieja === $fila_nueva && $columna_vieja === $columna_nueva) {',
            '        return [\'exito\' => false, \'error\' => \'El asiento nuevo es el mismo que el actual\'];',
            '    }',
            '',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        ],
        'reemplazar' => [
            '    if ($fila_vieja === $fila_nueva && $columna_vieja === $columna_nueva) {',
            '        return [\'exito\' => false, \'error\' => \'El asiento nuevo es el mismo que el actual\'];',
            '    }',
            '',
            '    // Fase B2.3.5b.2: contexto opcional. Si viene, el terminal',
            '    // navega por su subgrafo compartido.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php — resolver solicitante por ID especial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'cambiar_asiento_pasaje: solicitante por ID especial',
        'buscar' => [
            '    // Validar permisos del solicitante.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios\'];',
            '    $nodo_sol = $raiz_usuarios->adyacente($nombre_solicitante);',
            '    if (!$nodo_sol) return [\'exito\' => false, \'error\' => \'Solicitante no encontrado\'];',
            '    $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
            '    $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
        ],
        'reemplazar' => [
            '    // Validar permisos del solicitante.',
            '    // Fase B2.3.5b.2: ID especial `us_<nombre>`.',
            '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
            '    if (!$nodo_sol) return [\'exito\' => false, \'error\' => \'Solicitante no encontrado\'];',
            '    $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
            '    if (!$nodo_nivel_sol) {',
            '        $publico = $nodo_sol->adyacente(\'publico\');',
            '        if ($publico) $nodo_nivel_sol = $publico->adyacente(\'nivel\');',
            '    }',
            '    $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
        ],
    ],

    // ============================================================
    // Enrutador.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump @version a 1.5piloto.77h',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77g',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77h',
        ],
    ],

    // ============================================================
    // Enrutador.php — reservar_asiento con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: reservar_asiento con contexto',
        'buscar' => [
            '                    $resultado = reservar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.2: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = reservar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // Enrutador.php — asignar_pasajero_reserva con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: asignar_pasajero_reserva con contexto',
        'buscar' => [
            '                    $resultado = asignar_pasajero_a_reserva($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.2: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = asignar_pasajero_a_reserva($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // Enrutador.php — liberar_reserva_asiento con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: liberar_reserva_asiento con contexto',
        'buscar' => [
            '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.2: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nodo_contexto);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // Enrutador.php — cambiar_asiento con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: cambiar_asiento con contexto',
        'buscar' => [
            '                    $resultado = cambiar_asiento_pasaje(',
            '                        $nombre_viaje, $nombre_micro,',
            '                        $fila_vieja, $columna_vieja,',
            '                        $fila_nueva, $columna_nueva,',
            '                        $nombre_dueno, $nombre_solicitante,',
            '                        $dejar_reservado_viejo',
            '                    );',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.2: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = cambiar_asiento_pasaje(',
            '                        $nombre_viaje, $nombre_micro,',
            '                        $fila_vieja, $columna_vieja,',
            '                        $fila_nueva, $columna_nueva,',
            '                        $nombre_dueno, $nombre_solicitante,',
            '                        $dejar_reservado_viejo,',
            '                        $nodo_contexto',
            '                    );',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77h',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77g',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77h',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77h',
        'buscar' => [
            '**Tanda actual:** v77g (Fase B2.3.5b.1: el enrutador pasa',
            'contexto del solicitante terminal).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77h (Fase B2.3.5b.2: contexto en',
            'cambiar_asiento_pasaje y reservas).',
            '',
            '**v77h — Fase B2.3.5b.2.**',
            '',
            '- `ViajeAsientos.php`:',
            '  - `cambiar_asiento_pasaje` acepta `?Nodo $nodo_contexto = null`',
            '    y lo pasa a `obtener_contenedor_viajes_dueno`.',
            '  - `cambiar_asiento_pasaje` resuelve el solicitante por ID',
            '    especial `us_<nombre>` en vez de `nodo_por_id(\'usuarios\')`.',
            '- `Enrutador.php`:',
            '  - `viajes/reservar_asiento`, `viajes/asignar_pasajero_reserva`,',
            '    `viajes/liberar_reserva_asiento` y `viajes/cambiar_asiento`',
            '    pasan el contexto del solicitante (o null si no es terminal).',
            '',
            'Sin cambio de comportamiento hoy. Falta B2.3.5b.3 (empresas,',
            'vehículos, pasajeros) y B2.3.5b.4 (pruebas del plugin).',
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