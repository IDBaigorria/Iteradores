<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2a (Grupo 1): contexto del solicitante en ViajeMicros.
 *
 *  - ViajeMicros.php: 4 funciones aceptan ?Nodo $nodo_contexto.
 *    agregar_micro_a_viaje también resuelve `empresas` por contexto.
 *    actualizar_contadores_viaje propaga el contexto.
 *  - Enrutador.php: las 4 acciones de viajes/*_micro pasan
 *    _contexto_solicitante($nombre_solicitante).
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
// ViajeMicros.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: bump @version a 1.5piloto.78h',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.8',
        ' * @version   1.5piloto.74s',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.8',
        ' * @version   1.5piloto.78h',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: agregar_micro_a_viaje acepta contexto',
    'buscar' => [
        'function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = \'0\'): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        '',
        '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
        '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
        '',
        '    // Fase B3.1: ID especial directo.',
        '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        '',
        '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
        '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
    ],
    'reemplazar' => [
        'function agregar_micro_a_viaje(string $nombre_viaje, string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $monto = \'0\', ?Nodo $nodo_contexto = null): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        '',
        '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
        '    if (!$nodo_viaje) return [\'exito\' => false, \'error\' => \'Viaje no encontrado\'];',
        '',
        '    // Fase 1c.2: si viene contexto (terminal → compartido),',
        '    // navegar desde ahí. El compartido tiene un contenedor',
        '    // `empresas` con las empresas referenciadas por los viajes',
        '    // autorizados. Sin contexto (dueño, admin), navegar por la',
        '    // raíz `us_<dueno>` como antes.',
        '    if ($nodo_contexto) {',
        '        $nodo_empresas = $nodo_contexto->adyacente(\'empresas\');',
        '    } else {',
        '        $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '        if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
        '    }',
        '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: eliminar_micro_de_viaje acepta contexto',
    'buscar' => [
        'function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
    'reemplazar' => [
        'function eliminar_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: eliminar_micro_de_viaje propaga contexto',
    'buscar' => [
        '    // Fase 2: destruir el micro completo. Primero desenlazar',
        '    // del contenedor del viaje, después destruir el subárbol',
        '    // (copia de vehículo, pisos, asientos, campos).',
        '    $nodo_micros->eliminar_adyacente($nombre_micro);',
        '    _destruir_micro($nodo_micro);',
        '',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
    'reemplazar' => [
        '    // Fase 2: destruir el micro completo. Primero desenlazar',
        '    // del contenedor del viaje, después destruir el subárbol',
        '    // (copia de vehículo, pisos, asientos, campos).',
        '    $nodo_micros->eliminar_adyacente($nombre_micro);',
        '    _destruir_micro($nodo_micro);',
        '',
        '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno, $nodo_contexto);',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true];',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: actualizar_monto_micro acepta contexto',
    'buscar' => [
        'function actualizar_monto_micro(string $nombre_viaje, string $nombre_micro, string $monto, string $nombre_dueno): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
    'reemplazar' => [
        'function actualizar_monto_micro(string $nombre_viaje, string $nombre_micro, string $monto, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: obtener_micro_de_viaje acepta contexto',
    'buscar' => [
        'function obtener_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
    'reemplazar' => [
        'function obtener_micro_de_viaje(string $nombre_viaje, string $nombre_micro, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
    'descripcion' => 'ViajeMicros: actualizar_contadores_viaje acepta contexto',
    'buscar' => [
        'function actualizar_contadores_viaje(string $nombre_viaje, string $nombre_dueno): void {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return;',
    ],
    'reemplazar' => [
        'function actualizar_contadores_viaje(string $nombre_viaje, string $nombre_dueno, ?Nodo $nodo_contexto = null): void {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return;',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78h',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78g',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78h',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/agregar_micro pasa contexto',
    'buscar' => [
        '                case \'agregar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
        '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $monto = $post[\'monto\'] ?? \'0\';',
        '                    $resultado = agregar_micro_a_viaje($nombre_viaje, $nombre_empresa, $nombre_vehiculo, $nombre_dueno, $monto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'agregar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
        '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $monto = $post[\'monto\'] ?? \'0\';',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = agregar_micro_a_viaje($nombre_viaje, $nombre_empresa, $nombre_vehiculo, $nombre_dueno, $monto, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/eliminar_micro pasa contexto',
    'buscar' => [
        '                case \'eliminar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'eliminar_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/obtener_micro pasa contexto',
    'buscar' => [
        '                case \'obtener_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $resultado = obtener_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'obtener_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante (terminal → compartido).',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = obtener_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/actualizar_monto_micro pasa contexto',
    'buscar' => [
        '                case \'actualizar_monto_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $monto = $post[\'monto\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $resultado = actualizar_monto_micro($nombre_viaje, $nombre_micro, $monto, $nombre_dueno);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'actualizar_monto_micro\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_micro = $post[\'nombre_micro\'] ?? \'\';',
        '                    $monto = $post[\'monto\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = actualizar_monto_micro($nombre_viaje, $nombre_micro, $monto, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
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