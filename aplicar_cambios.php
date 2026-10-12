<?php
/**
 * Aplicador de cambios — v78p: listar_duenos para soporte con carga parcial.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: bump a 1.5piloto.78p',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78o',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78p',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: listar_duenos navega duenos directo para soporte',
    'buscar' => [
        'function listar_duenos(string $nombre_solicitante = \'\'): array {',
        '    $todos = listar_usuarios();',
        '    $duenos = [];',
        '    foreach ($todos as $usuario) {',
        '        if ($usuario[\'nivel\'] === \'dueno\') {',
        '            $duenos[] = [',
        '                \'nombre_usuario\' => $usuario[\'nombre_usuario\'],',
        '                \'nombre_real\' => $usuario[\'nombre_real\'],',
        '            ];',
        '        }',
        '    }',
        '',
        '    // Si el solicitante es soporte, filtrar a sus dueños asignados.',
        '    if ($nombre_solicitante !== \'\' && $nombre_solicitante !== ConfiguracionApli::NOMBRE_ADMIN) {',
        '        $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '        if ($raiz) {',
        '            $nodo_sol = $raiz->adyacente($nombre_solicitante);',
        '            if ($nodo_sol) {',
        '                $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
        '                $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
        '                if ($nivel_sol === \'soporte\') {',
        '                    $nodo_duenos_sol = $nodo_sol->adyacente(\'duenos\');',
        '                    $permitidos = [];',
        '                    if ($nodo_duenos_sol) {',
        '                        foreach ($nodo_duenos_sol->adyacentes() as $nombre_d => $nodo_d) {',
        '                            $permitidos[(string)$nombre_d] = true;',
        '                        }',
        '                    }',
        '                    $duenos = array_values(array_filter($duenos, function($d) use ($permitidos) {',
        '                        return isset($permitidos[(string)$d[\'nombre_usuario\']]);',
        '                    }));',
        '                }',
        '            }',
        '        }',
        '    }',
        '',
        '    return $duenos;',
        '}',
    ],
    'reemplazar' => [
        'function listar_duenos(string $nombre_solicitante = \'\'): array {',
        '    // Fase 1c.2: si el solicitante es soporte, navegar directo por',
        '    // `us_<soporte> → duenos`. Con carga parcial, `usuarios` no',
        '    // está en memoria (solo el admin tiene el enlace) y',
        '    // `listar_usuarios` devolvería vacío.',
        '    if ($nombre_solicitante !== \'\' && $nombre_solicitante !== ConfiguracionApli::NOMBRE_ADMIN) {',
        '        $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
        '        if ($nodo_sol) {',
        '            $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
        '            $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
        '            if ($nivel_sol === \'soporte\') {',
        '                $nodo_duenos_sol = $nodo_sol->adyacente(\'duenos\');',
        '                $duenos = [];',
        '                if ($nodo_duenos_sol) {',
        '                    foreach ($nodo_duenos_sol->adyacentes() as $nombre_d => $nodo_d) {',
        '                        $nodo_nombre_real = $nodo_d->adyacente(\'nombre_real\');',
        '                        $duenos[] = [',
        '                            \'nombre_usuario\' => (string)$nombre_d,',
        '                            \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : \'\',',
        '                        ];',
        '                    }',
        '                }',
        '                return $duenos;',
        '            }',
        '        }',
        '    }',
        '',
        '    // Admin (o cualquier otro caso): listar todos los dueños.',
        '    $todos = listar_usuarios();',
        '    $duenos = [];',
        '    foreach ($todos as $usuario) {',
        '        if ($usuario[\'nivel\'] === \'dueno\') {',
        '            $duenos[] = [',
        '                \'nombre_usuario\' => $usuario[\'nombre_usuario\'],',
        '                \'nombre_real\' => $usuario[\'nombre_real\'],',
        '            ];',
        '        }',
        '    }',
        '',
        '    return $duenos;',
        '}',
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