<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2b (Grupo 2): contexto del solicitante en ViajeOpciones.
 *
 *  - ViajeOpciones.php: obtener_opciones_avanzadas_viaje y
 *    guardar_opciones_avanzadas_viaje aceptan ?Nodo $nodo_contexto.
 *  - Enrutador.php: las 2 acciones viajes/*_opciones_avanzadas
 *    pasan _contexto_solicitante($nombre_solicitante).
 *
 * Nota: las funciones obtener_opciones_terminal_viaje y
 * guardar_opciones_terminal_viaje NO están en ViajeOpciones.php.
 * Quedan para una sub-tanda aparte cuando se identifique el archivo.
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
// ViajeOpciones.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
    'descripcion' => 'ViajeOpciones: bump @version a 1.5piloto.78h',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.12',
        ' * @version   1.5piloto.74',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.12',
        ' * @version   1.5piloto.78h',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
    'descripcion' => 'ViajeOpciones: obtener_opciones_avanzadas_viaje acepta contexto',
    'buscar' => [
        'function obtener_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje): array {',
    ],
    'reemplazar' => [
        'function obtener_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, ?Nodo $nodo_contexto = null): array {',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
    'descripcion' => 'ViajeOpciones: obtener_opciones_avanzadas_viaje usa contexto',
    'buscar' => [
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        '    if (!$nodo_viajes) return $defaults;',
        '',
        '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
        '    if (!$nodo_viaje) return $defaults;',
    ],
    'reemplazar' => [
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
        '    if (!$nodo_viajes) return $defaults;',
        '',
        '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
        '    if (!$nodo_viaje) return $defaults;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
    'descripcion' => 'ViajeOpciones: guardar_opciones_avanzadas_viaje acepta contexto',
    'buscar' => [
        'function guardar_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, array $opciones, bool $aplicar_retroactivo = false): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
    ],
    'reemplazar' => [
        'function guardar_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, array $opciones, bool $aplicar_retroactivo = false, ?Nodo $nodo_contexto = null): array {',
        '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_contexto);',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78i',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78h',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78i',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/obtener_opciones_avanzadas pasa contexto',
    'buscar' => [
        '                case \'obtener_opciones_avanzadas\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
        '                    responder_json([\'exito\' => true, \'opciones\' => $opciones]);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'obtener_opciones_avanzadas\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    if (empty($nombre_viaje) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $nodo_contexto);',
        '                    responder_json([\'exito\' => true, \'opciones\' => $opciones]);',
        '                    break;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: viajes/guardar_opciones_avanzadas pasa contexto',
    'buscar' => [
        '                case \'guardar_opciones_avanzadas\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $opciones = [',
        '                        \'restriccion_edad\' => $post[\'restriccion_edad\'] ?? \'0\',',
        '                        \'edad_minima\' => $post[\'edad_minima\'] ?? \'18\',',
        '                        \'edad_maxima\' => $post[\'edad_maxima\'] ?? \'80\',',
        '                        \'mostrar_dj_en_terminales\' => $post[\'mostrar_dj_en_terminales\'] ?? \'0\',',
        '                    ];',
        '                    if (empty($nombre_viaje) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    $resultado = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                case \'guardar_opciones_avanzadas\':',
        '                    $nombre_viaje = $post[\'nombre_viaje\'] ?? \'\';',
        '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
        '                    $opciones = [',
        '                        \'restriccion_edad\' => $post[\'restriccion_edad\'] ?? \'0\',',
        '                        \'edad_minima\' => $post[\'edad_minima\'] ?? \'18\',',
        '                        \'edad_maxima\' => $post[\'edad_maxima\'] ?? \'80\',',
        '                        \'mostrar_dj_en_terminales\' => $post[\'mostrar_dj_en_terminales\'] ?? \'0\',',
        '                    ];',
        '                    if (empty($nombre_viaje) || empty($nombre_dueno)) {',
        '                        responder_json([\'exito\' => false, \'error\' => \'Parámetros incompletos\']);',
        '                    }',
        '                    // Fase 1c.2: contexto del solicitante.',
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones, false, $nodo_contexto);',
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