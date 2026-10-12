<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2c (Grupo 3): contexto del solicitante en Viaje.php.
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
// Viaje.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: bump @version a 1.5piloto.78j',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.8',
        ' * @version   1.5piloto.77e',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.8',
        ' * @version   1.5piloto.78j',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: formatear_viaje acepta contexto',
    'buscar' => [
        'function formatear_viaje(string $nombre_viaje, $nodo_viaje, ?string $nombre_terminal = null, ?array $indice_ventas = null): array {',
    ],
    'reemplazar' => [
        'function formatear_viaje(string $nombre_viaje, $nodo_viaje, ?string $nombre_terminal = null, ?array $indice_ventas = null, ?Nodo $nodo_contexto = null): array {',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: viaje_tiene_ventas acepta contexto',
    'buscar' => [
        'function viaje_tiene_ventas(string $nombre_dueno, string $nombre_viaje): bool {',
        '    // Fase B3.1: ID especial directo.',
        '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return false;',
        '',
        '    $contenedor_ventas = $nodo_dueno->adyacente(\'ventas\');',
        '    if (!$contenedor_ventas) return false;',
    ],
    'reemplazar' => [
        'function viaje_tiene_ventas(string $nombre_dueno, string $nombre_viaje, ?Nodo $nodo_contexto = null): bool {',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí. Si no, resolver us_<dueno>.',
        '    $nodo_dueno = $nodo_contexto ?: Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return false;',
        '',
        '    $contenedor_ventas = $nodo_dueno->adyacente(\'ventas\');',
        '    if (!$contenedor_ventas) return false;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: formatear_viaje propaga contexto a viaje_tiene_ventas',
    'buscar' => [
        '    if ($indice_ventas !== null) {',
        '        $tiene_ventas_bool = !empty($indice_ventas[\'tiene_ventas\'][$nombre_viaje]);',
        '    } else {',
        '        $tiene_ventas_bool = viaje_tiene_ventas($datos[\'dueno\'], $nombre_viaje);',
        '    }',
    ],
    'reemplazar' => [
        '    if ($indice_ventas !== null) {',
        '        $tiene_ventas_bool = !empty($indice_ventas[\'tiene_ventas\'][$nombre_viaje]);',
        '    } else {',
        '        $tiene_ventas_bool = viaje_tiene_ventas($datos[\'dueno\'], $nombre_viaje, $nodo_contexto);',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: _calcular_vendidos_por_micro_de_viaje acepta contexto',
    'buscar' => [
        'function _calcular_vendidos_por_micro_de_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {',
        '    $vendidos_por_micro = [];',
        '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
    ],
    'reemplazar' => [
        'function _calcular_vendidos_por_micro_de_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, ?Nodo $nodo_contexto = null): array {',
        '    $vendidos_por_micro = [];',
        '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: formatear_viaje propaga contexto a _calcular_vendidos_por_micro_de_viaje',
    'buscar' => [
        '        if ($indice_ventas !== null) {',
        '            $vendidos_por_micro = $indice_ventas[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] ?? [];',
        '        } else {',
        '            $vendidos_por_micro = _calcular_vendidos_por_micro_de_viaje($datos[\'dueno\'], $nombre_viaje, $nombre_terminal);',
        '        }',
    ],
    'reemplazar' => [
        '        if ($indice_ventas !== null) {',
        '            $vendidos_por_micro = $indice_ventas[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] ?? [];',
        '        } else {',
        '            $vendidos_por_micro = _calcular_vendidos_por_micro_de_viaje($datos[\'dueno\'], $nombre_viaje, $nombre_terminal, $nodo_contexto);',
        '        }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: formatear_viaje usa contexto para empresas',
    'buscar' => [
        '    // Pre-cargar el contenedor de empresas del dueño (para',
        '    // resolver nombres visibles). Fase B3.1: ID especial.',
        '    $nodo_dueno_iter = Nodo::nodo_por_id(\'us_\' . $datos[\'dueno\']);',
        '    $nodo_empresas = $nodo_dueno_iter ? $nodo_dueno_iter->adyacente(\'empresas\') : null;',
    ],
    'reemplazar' => [
        '    // Pre-cargar el contenedor de empresas del dueño (para',
        '    // resolver nombres visibles). Fase 1c.2: si viene contexto',
        '    // (terminal → compartido), resolver desde ahí.',
        '    $nodo_dueno_iter = $nodo_contexto ?: Nodo::nodo_por_id(\'us_\' . $datos[\'dueno\']);',
        '    $nodo_empresas = $nodo_dueno_iter ? $nodo_dueno_iter->adyacente(\'empresas\') : null;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: formatear_viaje usa contexto para opciones_avanzadas',
    'buscar' => [
        '    // Opciones avanzadas (definida en ViajeOpciones.php)',
        '    $datos[\'opciones_avanzadas\'] = obtener_opciones_avanzadas_viaje($datos[\'dueno\'], $nombre_viaje);',
        '    return $datos;',
        '}',
    ],
    'reemplazar' => [
        '    // Opciones avanzadas (definida en ViajeOpciones.php).',
        '    // Fase 1c.2: pasar el contexto para que el terminal vea las',
        '    // opciones del viaje desde su compartido.',
        '    $datos[\'opciones_avanzadas\'] = obtener_opciones_avanzadas_viaje($datos[\'dueno\'], $nombre_viaje, $nodo_contexto);',
        '    return $datos;',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: listar_viajes_de_terminal pasa contexto a formatear_viaje',
    'buscar' => [
        '    $viajes_autorizados = [];',
        '    $adyacentes = (array) $nodo_viajes->adyacentes();',
        '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
        '        $viaje = formatear_viaje($nombre_viaje, $nodo_viaje, $nombre_terminal, $indice_ventas);',
        '        if (in_array($nombre_terminal, $viaje[\'terminales_autorizadas\'])) {',
        '            $viajes_autorizados[] = $viaje;',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    $viajes_autorizados = [];',
        '    $adyacentes = (array) $nodo_viajes->adyacentes();',
        '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
        '        // Fase 1c.2: pasar el nodo del dueño (compartido, tras',
        '        // B2.3.5) como contexto a formatear_viaje.',
        '        $viaje = formatear_viaje($nombre_viaje, $nodo_viaje, $nombre_terminal, $indice_ventas, $nodo_dueno);',
        '        if (in_array($nombre_terminal, $viaje[\'terminales_autorizadas\'])) {',
        '            $viajes_autorizados[] = $viaje;',
        '        }',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: obtener_opciones_terminal_viaje acepta contexto',
    'buscar' => [
        'function obtener_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {',
        '    $defaults = [',
        '        \'cambiar_punto_predeterminado\' => \'0\',',
        '        \'punto_subida_bajada\' => \'\',',
        '        // Condiciones de pago: vacío = sin override (usar config del viaje)',
        '        \'permite_efectivo\' => \'\',',
        '        \'cuotas_efectivo_max\' => \'\',',
        '        \'permite_transferencia\' => \'\',',
        '        \'cuotas_transferencia_max\' => \'\',',
        '    ];',
        '',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
    ],
    'reemplazar' => [
        'function obtener_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, ?Nodo $nodo_contexto = null): array {',
        '    $defaults = [',
        '        \'cambiar_punto_predeterminado\' => \'0\',',
        '        \'punto_subida_bajada\' => \'\',',
        '        // Condiciones de pago: vacío = sin override (usar config del viaje)',
        '        \'permite_efectivo\' => \'\',',
        '        \'cuotas_efectivo_max\' => \'\',',
        '        \'permite_transferencia\' => \'\',',
        '        \'cuotas_transferencia_max\' => \'\',',
        '    ];',
        '',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí.',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'Viaje: obtener_declaracion_jurada acepta contexto',
    'buscar' => [
        'function obtener_declaracion_jurada(string $nombre_dueno, string $nombre_viaje, string $tipo, bool $forzar_default = false): array {',
        '    $tipo = ($tipo === \'menor\') ? \'menor\' : \'mayor\';',
        '    $enlace = \'declaracion_jurada_\' . $tipo;',
        '',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
    ],
    'reemplazar' => [
        'function obtener_declaracion_jurada(string $nombre_dueno, string $nombre_viaje, string $tipo, bool $forzar_default = false, ?Nodo $nodo_contexto = null): array {',
        '    $tipo = ($tipo === \'menor\') ? \'menor\' : \'mayor\';',
        '    $enlace = \'declaracion_jurada_\' . $tipo;',
        '',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí.',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78j',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78i',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78j',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/obtener_declaracion pasa contexto',
    'buscar' => [
        '                case \'obtener_declaracion\':',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $tipo_dj = $post[\'tipo_dj\'] ?? \'mayor\';',
        '                    $forzar_default = ($post[\'forzar_default\'] ?? \'0\') === \'1\';',
        '                    if (empty($nombre_dueno) || empty($nombre_viaje)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $resultado = obtener_declaracion_jurada($nombre_dueno, $nombre_viaje, $tipo_dj, $forzar_default);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'obtener_declaracion\':',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $tipo_dj = $post[\'tipo_dj\'] ?? \'mayor\';',
        '                    $forzar_default = ($post[\'forzar_default\'] ?? \'0\') === \'1\';',
        '                    if (empty($nombre_dueno) || empty($nombre_viaje)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = obtener_declaracion_jurada($nombre_dueno, $nombre_viaje, $tipo_dj, $forzar_default, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/obtener_opciones_terminal pasa contexto',
    'buscar' => [
        '                case \'obtener_opciones_terminal\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $opciones = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
        '                    responder_json([\'exito\' => true, \'opciones\' => $opciones]);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'obtener_opciones_terminal\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_terminal = $post[\'nombre_terminal\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $opciones = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal, $nodo_contexto);',
        '                    responder_json([\'exito\' => true, \'opciones\' => $opciones]);',
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