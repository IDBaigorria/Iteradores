<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73b: listar_usuarios devuelve los duenos del soporte.
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

    // ==========================================================
    // Usuario.php — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.73',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73b',
        ],
    ],

    // ==========================================================
    // Usuario.php — listar_usuarios devuelve duenos si es soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: listar_usuarios agrega duenos de soporte',
        'buscar' => [
            '        if ($usuario[\'nivel\'] === \'dueno\') {',
            '            $terminales = [];',
            '            $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '            if ($nodo_terminales) {',
            '                foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {',
            '                    $terminales[] = $nombre_terminal;',
            '                }',
            '            }',
            '            $usuario[\'terminales\'] = $terminales;',
            '        }',
            '',
            '        $usuarios[] = $usuario;',
        ],
        'reemplazar' => [
            '        if ($usuario[\'nivel\'] === \'dueno\') {',
            '            $terminales = [];',
            '            $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '            if ($nodo_terminales) {',
            '                foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {',
            '                    $terminales[] = $nombre_terminal;',
            '                }',
            '            }',
            '            $usuario[\'terminales\'] = $terminales;',
            '        }',
            '',
            '        if ($usuario[\'nivel\'] === \'soporte\') {',
            '            $duenos = [];',
            '            $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '            if ($nodo_duenos) {',
            '                foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                    $duenos[] = (string)$nombre_d;',
            '                }',
            '            }',
            '            $usuario[\'duenos\'] = $duenos;',
            '        }',
            '',
            '        $usuarios[] = $usuario;',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73a (alta de',
            'usuarios `soporte` con asignación de dueños).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73b (fix:',
            'listar_usuarios devuelve los dueños asignados de cada soporte).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73b al historial',
        'buscar' => [
            '- **v73a**: alta de usuarios `soporte` desde el panel admin con',
            '  asignación de dueños (checkboxes). Edición de los dueños asignados',
            '  de un soporte existente.',
        ],
        'reemplazar' => [
            '- **v73a**: alta de usuarios `soporte` desde el panel admin con',
            '  asignación de dueños (checkboxes). Edición de los dueños asignados',
            '  de un soporte existente.',
            '- **v73b**: `listar_usuarios` devuelve el campo `duenos` para los',
            '  usuarios de nivel `soporte`, para que el panel admin pueda mostrar',
            '  los checkboxes marcados al editar.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (fix de duenos del soporte) ===\n\n";

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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";