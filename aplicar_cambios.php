<?php
/**
 * Aplicador de cambios — v78q: excepciones de carga total
 * para acciones de gestión de usuarios.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump a 1.5piloto.78q',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78q',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: excepciones de carga total para gestión de usuarios',
    'buscar' => [
        '    if ($accion !== \'\') {',
        '        if ($nombre_solicitante === \'\') return null;',
        '        return [\'us_\' . $nombre_solicitante];',
        '    }',
    ],
    'reemplazar' => [
        '    if ($accion !== \'\') {',
        '        if ($nombre_solicitante === \'\') return null;',
        '',
        '        // Fase 1c.2: acciones que crean/editan/eliminan usuarios',
        '        // necesitan la raíz `usuarios` en memoria (para colgar o',
        '        // descolgar el usuario nuevo y que `listar_usuarios` lo',
        '        // vea). Con carga parcial del dueño, `usuarios` no está.',
        '        // Carga total para estas acciones puntuales.',
        '        $acciones_carga_total = [',
        '            \'dueno/agregar_terminal\',',
        '            \'dueno/actualizar_terminal\',',
        '            \'dueno/eliminar_terminal\',',
        '            \'administrador/agregar_usuario\',',
        '            \'administrador/actualizar_usuario\',',
        '            \'administrador/eliminar_usuario\',',
        '        ];',
        '        if (in_array($accion, $acciones_carga_total, true)) {',
        '            return null;',
        '        }',
        '',
        '        return [\'us_\' . $nombre_solicitante];',
        '    }',
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

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($cambios as $cambio) {
    $ruta_abs = $raiz_proyecto . '/' . $cambio['archivo'];
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: {$cambio['archivo']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $buscar_str = implode("\n", $cambio['buscar']);
    $reemplazar_str = implode("\n", $cambio['reemplazar']);
    $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
    if ($ocurrencias === 0) {
        $bloques_fallidos[] = "{$cambio['archivo']}: bloque no encontrado - {$cambio['descripcion']}";
        continue;
    }
    if ($ocurrencias > 1) {
        $bloques_fallidos[] = "{$cambio['archivo']}: bloque ambiguo - {$cambio['descripcion']}";
        continue;
    }
    $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
    $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    $bloques_ok++;
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "\nListo.\n";