<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2d (Grupo 4): contexto del solicitante en Venta.php.
 *
 * Uso:
 *   php aplicar_cambios.php
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

// ------------------------------------------------------------
// Venta.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: bump @version a 1.5piloto.78k',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.14',
        ' * @version   1.5piloto.77m',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.14',
        ' * @version   1.5piloto.78k',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: _dni_asignado_en_viaje_venta acepta contexto',
    'buscar' => [
        'function _dni_asignado_en_viaje_venta(string $nombre_dueno, string $nombre_viaje, string $dni): bool {',
        '    $dni_norm = normalizar_dni($dni);',
        '    if ($dni_norm === \'\') return false;',
        '',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return false;',
    ],
    'reemplazar' => [
        'function _dni_asignado_en_viaje_venta(string $nombre_dueno, string $nombre_viaje, string $dni, ?Nodo $nodo_contexto = null): bool {',
        '    $dni_norm = normalizar_dni($dni);',
        '    if ($dni_norm === \'\') return false;',
        '',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí.',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return false;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: obtener_o_crear_pasajero acepta contexto',
    'buscar' => [
        'function obtener_o_crear_pasajero(string $nombre_dueno, string $dni, array $datos_pasajero): ?Nodo {',
        '    $dni = trim($dni);',
        '    if (empty($dni)) return null;',
        '',
        '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
        '    if (!$contenedor) return null;',
    ],
    'reemplazar' => [
        'function obtener_o_crear_pasajero(string $nombre_dueno, string $dni, array $datos_pasajero, ?Nodo $nodo_contexto = null): ?Nodo {',
        '    $dni = trim($dni);',
        '    if (empty($dni)) return null;',
        '',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí. El compartido tiene un alias al',
        '    // contenedor de pasajeros del dueño.',
        '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$contenedor) return null;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: confirmar_venta_actual propaga contexto a opciones',
    'buscar' => [
        '    // Obtener opciones avanzadas del viaje',
        '    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
        '    $restriccion_edad = $opciones[\'restriccion_edad\'] === \'1\';',
        '    $edad_min = (int)$opciones[\'edad_minima\'];',
        '    $edad_max = (int)$opciones[\'edad_maxima\'];',
        '',
        '    // Resolver configuración de pago: override del TerminalViaje > viaje > defaults.',
        '    $opciones_viaje_pago = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
        '    $opciones_terminal_pago = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
    ],
    'reemplazar' => [
        '    // Obtener opciones avanzadas del viaje.',
        '    // Fase 1c.2: pasar el contexto (compartido) para que el',
        '    // terminal vea las opciones del viaje desde su subgrafo.',
        '    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $nodo_dueno);',
        '    $restriccion_edad = $opciones[\'restriccion_edad\'] === \'1\';',
        '    $edad_min = (int)$opciones[\'edad_minima\'];',
        '    $edad_max = (int)$opciones[\'edad_maxima\'];',
        '',
        '    // Resolver configuración de pago: override del TerminalViaje > viaje > defaults.',
        '    $opciones_viaje_pago = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $nodo_dueno);',
        '    $opciones_terminal_pago = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal, $nodo_dueno);',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: confirmar_venta_actual propaga contexto a comprador',
    'buscar' => [
        '    if (!empty($comprador_dni)) {',
        '        $nodo_comprador = obtener_o_crear_pasajero($nombre_dueno, $comprador_dni, $comprador_datos);',
        '        if ($nodo_comprador) $nodo_venta->_adyacente_en($nodo_comprador, \'comprador\');',
        '    }',
    ],
    'reemplazar' => [
        '    if (!empty($comprador_dni)) {',
        '        $nodo_comprador = obtener_o_crear_pasajero($nombre_dueno, $comprador_dni, $comprador_datos, $nodo_dueno);',
        '        if ($nodo_comprador) $nodo_venta->_adyacente_en($nodo_comprador, \'comprador\');',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: confirmar_venta_actual propaga contexto a _dni_asignado',
    'buscar' => [
        '            if (_dni_asignado_en_viaje_venta($nombre_dueno, $nombre_viaje, $dni_pasajero)) {',
        '                return [',
        '                    \'exito\' => false,',
        '                    \'error\' => "El DNI $dni_norm_actual ya esta asignado a otro asiento de este viaje"',
        '                ];',
        '            }',
    ],
    'reemplazar' => [
        '            if (_dni_asignado_en_viaje_venta($nombre_dueno, $nombre_viaje, $dni_pasajero, $nodo_dueno)) {',
        '                return [',
        '                    \'exito\' => false,',
        '                    \'error\' => "El DNI $dni_norm_actual ya esta asignado a otro asiento de este viaje"',
        '                ];',
        '            }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: confirmar_venta_actual propaga contexto a crear pasajero',
    'buscar' => [
        '        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $dni_pasajero, $datos_pasajero);',
        '        if (!$nodo_pasajero) {',
        '            return [\'exito\' => false, \'error\' => \'No se pudo crear el pasajero para el asiento \' . ($indice_asiento + 1)];',
        '        }',
    ],
    'reemplazar' => [
        '        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $dni_pasajero, $datos_pasajero, $nodo_dueno);',
        '        if (!$nodo_pasajero) {',
        '            return [\'exito\' => false, \'error\' => \'No se pudo crear el pasajero para el asiento \' . ($indice_asiento + 1)];',
        '        }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: _calcular_cobertura_dueno acepta contexto',
    'buscar' => [
        'function _calcular_cobertura_dueno(string $nombre_dueno, float $monto_ef, float $monto_ba): array {',
        '    // Fase B2.3.4: ID especial.',
        '    $nodo = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo) return [\'cubierto_ef\' => 0.0, \'cubierto_ba\' => 0.0, \'no_cubierto_ef\' => $monto_ef, \'no_cubierto_ba\' => $monto_ba];',
    ],
    'reemplazar' => [
        'function _calcular_cobertura_dueno(string $nombre_dueno, float $monto_ef, float $monto_ba, ?Nodo $nodo_contexto = null): array {',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí. Nota: el compartido no tiene los',
        '    // nodos `efectivo`/`banco` (viven en el `privado` del',
        '    // dueño real), así que en carga parcial desde terminal',
        '    // la cobertura devuelve 0. Es un comportamiento conocido:',
        '    // la cancelación desde terminal con cupones rendidos no',
        '    // cubre la parte del dueño. Ver nota en plan_actual.md.',
        '    $nodo = $nodo_contexto ?: Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo) return [\'cubierto_ef\' => 0.0, \'cubierto_ba\' => 0.0, \'no_cubierto_ef\' => $monto_ef, \'no_cubierto_ba\' => $monto_ba];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: _crear_nodo_cancelacion acepta contexto',
    'buscar' => [
        'function _crear_nodo_cancelacion(string $nombre_dueno, string $id_venta, string $motivo, Nodo $nodo_venta, array $desglose, array $cubierto, int $asientos_liberados): string {',
        '    // Fase B2.3.4: ID especial.',
        '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return \'\';',
    ],
    'reemplazar' => [
        'function _crear_nodo_cancelacion(string $nombre_dueno, string $id_venta, string $motivo, Nodo $nodo_venta, array $desglose, array $cubierto, int $asientos_liberados, ?Nodo $nodo_contexto = null): string {',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // agregar la cancelación en el contenedor `cancelaciones`',
        '    // del compartido. Si no, en el del dueño real.',
        '    // Nota: en producción convendría insertar en AMBOS árboles',
        '    // (paralelos) cuando la cancelación la hace un terminal.',
        '    // Se anota para una tanda futura.',
        '    $nodo_dueno = $nodo_contexto ?: Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return \'\';',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: cancelar_venta deriva contexto',
    'buscar' => [
        'function cancelar_venta(string $id_venta, string $motivo = \'\', ?string $nombre_terminal = null): array {',
        '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
        '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        '',
        '    // Desglose: parte en terminal vs parte ya rendida.',
        '    $desglose = _calcular_devolucion_desglosada($nodo_venta);',
        '',
        '    // Cuánto del rendido puede cubrir el dueño con su saldo actual.',
        '    $cubierto = _calcular_cobertura_dueno(',
        '        $nombre_dueno,',
        '        $desglose[\'rendido_efectivo\'],',
        '        $desglose[\'rendido_banco\']',
        '    );',
    ],
    'reemplazar' => [
        'function cancelar_venta(string $id_venta, string $motivo = \'\', ?string $nombre_terminal = null): array {',
        '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
        '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        '',
        '    // Fase 1c.2: derivar el contexto desde el terminal solicitante.',
        '    $nodo_contexto = null;',
        '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
        '        $nodo_contexto = _contexto_terminal($nombre_terminal);',
        '    }',
        '',
        '    // Desglose: parte en terminal vs parte ya rendida.',
        '    $desglose = _calcular_devolucion_desglosada($nodo_venta);',
        '',
        '    // Cuánto del rendido puede cubrir el dueño con su saldo actual.',
        '    $cubierto = _calcular_cobertura_dueno(',
        '        $nombre_dueno,',
        '        $desglose[\'rendido_efectivo\'],',
        '        $desglose[\'rendido_banco\'],',
        '        $nodo_contexto',
        '    );',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: cancelar_venta pasa contexto a _crear_nodo_cancelacion',
    'buscar' => [
        '    $id_cancelacion = _crear_nodo_cancelacion(',
        '        $nombre_dueno,',
        '        $id_venta,',
        '        $motivo,',
        '        $nodo_venta,',
        '        $desglose,',
        '        $cubierto,',
        '        $asientos_liberados',
        '    );',
    ],
    'reemplazar' => [
        '    $id_cancelacion = _crear_nodo_cancelacion(',
        '        $nombre_dueno,',
        '        $id_venta,',
        '        $motivo,',
        '        $nodo_venta,',
        '        $desglose,',
        '        $cubierto,',
        '        $asientos_liberados,',
        '        $nodo_contexto',
        '    );',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: obtener_info_cancelacion deriva contexto',
    'buscar' => [
        'function obtener_info_cancelacion(string $id_venta, ?string $nombre_terminal = null): array {',
        '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
        '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        '',
        '    // Datos de viaje y micro.',
    ],
    'reemplazar' => [
        'function obtener_info_cancelacion(string $id_venta, ?string $nombre_terminal = null): array {',
        '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
        '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        '',
        '    // Fase 1c.2: derivar el contexto desde el terminal solicitante.',
        '    $nodo_contexto = null;',
        '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
        '        $nodo_contexto = _contexto_terminal($nombre_terminal);',
        '    }',
        '',
        '    // Datos de viaje y micro.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: obtener_info_cancelacion pasa contexto a cobertura',
    'buscar' => [
        '    // Desglose: parte en terminal vs parte ya rendida.',
        '    $desglose_info = _calcular_devolucion_desglosada($nodo_venta);',
        '    $cubierto_info = _calcular_cobertura_dueno(',
        '        $nombre_dueno,',
        '        $desglose_info[\'rendido_efectivo\'],',
        '        $desglose_info[\'rendido_banco\']',
        '    );',
    ],
    'reemplazar' => [
        '    // Desglose: parte en terminal vs parte ya rendida.',
        '    $desglose_info = _calcular_devolucion_desglosada($nodo_venta);',
        '    $cubierto_info = _calcular_cobertura_dueno(',
        '        $nombre_dueno,',
        '        $desglose_info[\'rendido_efectivo\'],',
        '        $desglose_info[\'rendido_banco\'],',
        '        $nodo_contexto',
        '    );',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: listar_cancelaciones_de_dueno acepta contexto',
    'buscar' => [
        'function listar_cancelaciones_de_dueno(string $nombre_dueno, array $filtros = []): array {',
        '    // Fase B3.1: ID especial directo.',
        '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return [];',
        '    $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
        '    if (!$cont) return [];',
    ],
    'reemplazar' => [
        'function listar_cancelaciones_de_dueno(string $nombre_dueno, array $filtros = [], ?Nodo $nodo_contexto = null): array {',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí.',
        '    $nodo_dueno = $nodo_contexto ?: Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return [];',
        '    $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
        '    if (!$cont) return [];',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78k',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78j',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78k',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: cancelaciones/listar pasa contexto',
    'buscar' => [
        '        case \'cancelaciones\':',
        '            switch ($subaccion) {',
        '                case \'listar\':',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
        '                    }',
        '                    $filtros = [',
        '                        \'terminal\' => $post[\'terminal\'] ?? \'\',',
        '                    ];',
        '                    $cancelaciones = listar_cancelaciones_de_dueno($nombre_dueno, $filtros);',
        '                    responder_json([\'exito\' => true, \'cancelaciones\' => $cancelaciones]);',
        '                    break;',
    ],
    'reemplazar' => [
        '        case \'cancelaciones\':',
        '            switch ($subaccion) {',
        '                case \'listar\':',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
        '                    }',
        '                    $filtros = [',
        '                        \'terminal\' => $post[\'terminal\'] ?? \'\',',
        '                    ];',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $cancelaciones = listar_cancelaciones_de_dueno($nombre_dueno, $filtros, $nodo_contexto);',
        '                    responder_json([\'exito\' => true, \'cancelaciones\' => $cancelaciones]);',
        '                    break;',
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