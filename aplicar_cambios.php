<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77g (Fase B2.3.5b.1 del modelo topológico).
 *   - Enrutador.php:
 *     - Nuevo helper `_contexto_solicitante($nombre_solicitante)`.
 *       Devuelve el nodo compartido si el solicitante es terminal,
 *       o null en otros casos.
 *     - `viajes/estado_asientos`: pasa el contexto del solicitante
 *       a `obtener_estados_asientos_micro`.
 *     - `ventas/obtener`, `ventas/cancelar`, `ventas/pagar_cupon`,
 *       `ventas/info_cancelacion`: si el solicitante es terminal,
 *       pasan su nombre como filtro a la función correspondiente.
 *       Así la búsqueda queda restringida a su contexto.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Sin cambio de comportamiento hoy (los datos son los mismos). Se
 * activa cuando el framework haga carga parcial por contextos.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Enrutador.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump @version a 1.5piloto.77g',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77b',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77g',
        ],
    ],

    // ============================================================
    // Enrutador.php — helper _contexto_solicitante
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: helper _contexto_solicitante',
        'buscar' => [
            '/**',
            ' * Enruta una petición POST según la acción indicada.',
            ' *',
            ' * @param string $accion Acción en formato "modulo/subaccion".',
            ' * @param array $post Datos recibidos por POST.',
            ' * @return void',
            ' */',
            'function enrutar_peticion_post(string $accion, array $post): void {',
        ],
        'reemplazar' => [
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
            ' *',
            ' * Fase B2.3.5b.1. Después del repuntado (B2.3.5a), el enlace',
            ' * `dueno` del terminal apunta al compartido. Las funciones',
            ' * de navegación que aceptan un `?Nodo $nodo_contexto` lo',
            ' * usan para navegar por el subgrafo del terminal.',
            ' *',
            ' * Dueño, admin y soporte devuelven null: navegan por el',
            ' * camino default (`usuarios → dueño`).',
            ' *',
            ' * @param string $nombre_solicitante',
            ' * @return Nodo|null',
            ' */',
            'function _contexto_solicitante(string $nombre_solicitante): ?Nodo {',
            '    if ($nombre_solicitante === \'\') return null;',
            '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
            '    if (!$nodo_sol) return null;',
            '    $nivel = $nodo_sol->adyacente(\'nivel\');',
            '    if (!$nivel) {',
            '        $publico = $nodo_sol->adyacente(\'publico\');',
            '        if ($publico) $nivel = $publico->adyacente(\'nivel\');',
            '    }',
            '    if (!$nivel || $nivel->dato() !== \'terminal\') return null;',
            '    return $nodo_sol->adyacente(\'dueno\');',
            '}',
            '',
            '/**',
            ' * Devuelve el nombre del solicitante si es terminal, o null',
            ' * en otros casos. Se usa como filtro opcional en las',
            ' * funciones que reciben `?string $nombre_terminal`.',
            ' *',
            ' * Fase B2.3.5b.1.',
            ' *',
            ' * @param string $nombre_solicitante',
            ' * @return string|null',
            ' */',
            'function _nombre_terminal_solicitante(string $nombre_solicitante): ?string {',
            '    if ($nombre_solicitante === \'\') return null;',
            '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
            '    if (!$nodo_sol) return null;',
            '    $nivel = $nodo_sol->adyacente(\'nivel\');',
            '    if (!$nivel) {',
            '        $publico = $nodo_sol->adyacente(\'publico\');',
            '        if ($publico) $nivel = $publico->adyacente(\'nivel\');',
            '    }',
            '    if (!$nivel || $nivel->dato() !== \'terminal\') return null;',
            '    return $nombre_solicitante;',
            '}',
            '',
            '/**',
            ' * Enruta una petición POST según la acción indicada.',
            ' *',
            ' * @param string $accion Acción en formato "modulo/subaccion".',
            ' * @param array $post Datos recibidos por POST.',
            ' * @return void',
            ' */',
            'function enrutar_peticion_post(string $accion, array $post): void {',
        ],
    ],

    // ============================================================
    // Enrutador.php — viajes/estado_asientos con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: estado_asientos con contexto del solicitante',
        'buscar' => [
            '                case \'estado_asientos\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = obtener_estados_asientos_micro($nombre_viaje, $nombre_micro, $nombre_dueno);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'estado_asientos\':',
            '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
            '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: si el solicitante es terminal,',
            '                    // navegar por su subgrafo compartido.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = obtener_estados_asientos_micro($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // Enrutador.php — ventas/obtener con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: ventas/obtener con contexto del solicitante',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    $venta = obtener_venta_por_id($id_venta);',
            '                    if ($venta) {',
            '                        responder_json([\'exito\' => true, \'venta\' => $venta]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Venta no encontrada\']);',
            '                    }',
            '                    break;',
            '',
            '                case \'cancelar\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $motivo = $post[\'motivo\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    $resultado = cancelar_venta($id_venta, $motivo);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'pagar_cupon\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $numero_cupon = $post[\'numero_cupon\'] ?? \'\';',
            '                    $monto = $post[\'monto\'] ?? \'\';',
            '                    $metodo_pago = $post[\'metodo_pago\'] ?? \'\';',
            '                    if (empty($id_venta) || empty($numero_cupon) || empty($monto) || empty($metodo_pago)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    $resultado = pagar_cupon_venta($id_venta, $numero_cupon, $monto, $metodo_pago);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'info_cancelacion\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    $resultado = obtener_info_cancelacion($id_venta);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: si el solicitante es terminal,',
            '                    // restringir la búsqueda a su contexto.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $venta = obtener_venta_por_id($id_venta, $nombre_terminal_sol);',
            '                    if ($venta) {',
            '                        responder_json([\'exito\' => true, \'venta\' => $venta]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Venta no encontrada\']);',
            '                    }',
            '                    break;',
            '',
            '                case \'cancelar\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $motivo = $post[\'motivo\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $resultado = cancelar_venta($id_venta, $motivo, $nombre_terminal_sol);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'pagar_cupon\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $numero_cupon = $post[\'numero_cupon\'] ?? \'\';',
            '                    $monto = $post[\'monto\'] ?? \'\';',
            '                    $metodo_pago = $post[\'metodo_pago\'] ?? \'\';',
            '                    if (empty($id_venta) || empty($numero_cupon) || empty($monto) || empty($metodo_pago)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $resultado = pagar_cupon_venta($id_venta, $numero_cupon, $monto, $metodo_pago, $nombre_terminal_sol);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'info_cancelacion\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $resultado = obtener_info_cancelacion($id_venta, $nombre_terminal_sol);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77g',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77f',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77g',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77g',
        'buscar' => [
            '**Tanda actual:** v77f (Fase B2.3.5a: migración de repuntado',
            'de terminales al compartido).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77g (Fase B2.3.5b.1: el enrutador pasa',
            'contexto del solicitante terminal).',
            '',
            '**v77g — Fase B2.3.5b.1.**',
            '',
            'Enrutador:',
            '',
            '- Nuevo helper `_contexto_solicitante($nombre_solicitante)`',
            '  que devuelve el nodo compartido si el solicitante es',
            '  terminal, o null en otros casos.',
            '- Nuevo helper `_nombre_terminal_solicitante($nombre_solicitante)`',
            '  que devuelve el nombre si es terminal, o null.',
            '- `viajes/estado_asientos`: pasa `$nodo_contexto` a',
            '  `obtener_estados_asientos_micro`.',
            '- `ventas/obtener`, `ventas/cancelar`, `ventas/pagar_cupon`,',
            '  `ventas/info_cancelacion`: pasan `$nombre_terminal_sol` como',
            '  filtro a la función correspondiente. Restringe la búsqueda',
            '  al contexto del terminal solicitante.',
            '',
            'Sin cambio de comportamiento visible hoy (los nodos son',
            'los mismos). Se activa cuando el framework haga carga',
            'parcial por contextos. Falta B2.3.5b.2 (contexto en',
            '`cambiar_asiento_pasaje` y reservas), B2.3.5b.3 (empresas,',
            'vehículos, pasajeros), B2.3.5b.4 (pruebas del plugin).',
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