<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77w (fix del continue huérfano en subir_foto).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: fix subir_foto (continue huérfano)',
        'buscar' => [
            '    // Buscar vehículo y guardar enlace (insensible a mayúsculas/minúsculas)',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if ($nodo_empresas) {',
            '        // Buscar empresa comparando sin mayúsculas',
            '        $empresa_encontrada = null;',
            '        foreach ($nodo_empresas->adyacentes() as $nombre_empresa_actual => $nodo_empresa_actual) {',
            '            if (strcasecmp($nombre_empresa_actual, $nombre_empresa) === 0) {',
            '                $empresa_encontrada = $nodo_empresa_actual;',
            '                $nombre_empresa = $nombre_empresa_actual; // usar el original',
            '                break;',
            '            }',
            '        }',
            '',
            '        $nodo_vehiculos = $empresa_encontrada ? $empresa_encontrada->adyacente(\'vehiculos\') : null;',
            '',
            '        // Buscar vehículo comparando sin mayúsculas',
            '        $vehiculo_encontrado = null;',
            '        foreach ($nodo_vehiculos->adyacentes() as $nombre_vehiculo_actual => $nodo_vehiculo_actual) {',
            '            if (strcasecmp($nombre_vehiculo_actual, $nombre_vehiculo) === 0) {',
            '                $vehiculo_encontrado = $nodo_vehiculo_actual;',
            '                $nombre_vehiculo = $nombre_vehiculo_actual; // usar el original',
            '                break;',
            '            }',
            '        }',
            '        if (!$vehiculo_encontrado) continue;',
            '',
            '        // Eliminar foto anterior si existe',
            '        $foto_anterior = $vehiculo_encontrado->adyacente(\'foto\');',
            '        if ($foto_anterior) {',
            '            $ruta_anterior = __DIR__ . \'/../../\' . $foto_anterior->dato();',
            '            if (file_exists($ruta_anterior)) {',
            '                unlink($ruta_anterior);',
            '            }',
            '            // Fase 2, v75a: destruir la hoja `foto` en lugar',
            '            // de solo desenlazarla.',
            '            $vehiculo_encontrado->eliminar_adyacente(\'foto\');',
            '            Nodo::eliminar($foto_anterior);',
            '        }',
            '',
            '        // Crear enlace foto',
            '        $vehiculo_encontrado->_adyacente_en(Nodo::crear_con_dato($ruta_relativa), \'foto\');',
            '',
            '        // Guardar persistencia',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true, \'foto\' => $ruta_relativa];',
            '    }',
            '',
            '    // Si no encontró vehículo, eliminar archivo',
            '    unlink($ruta_destino);',
            '    return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '}',
        ],
        'reemplazar' => [
            '    // Buscar vehículo y guardar enlace (insensible a mayúsculas/minúsculas).',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '    }',
            '',
            '    // Buscar empresa comparando sin mayúsculas.',
            '    $empresa_encontrada = null;',
            '    foreach ($nodo_empresas->adyacentes() as $nombre_empresa_actual => $nodo_empresa_actual) {',
            '        if (strcasecmp($nombre_empresa_actual, $nombre_empresa) === 0) {',
            '            $empresa_encontrada = $nodo_empresa_actual;',
            '            $nombre_empresa = $nombre_empresa_actual; // usar el original',
            '            break;',
            '        }',
            '    }',
            '    if (!$empresa_encontrada) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '    }',
            '',
            '    $nodo_vehiculos = $empresa_encontrada->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '    }',
            '',
            '    // Buscar vehículo comparando sin mayúsculas.',
            '    $vehiculo_encontrado = null;',
            '    foreach ($nodo_vehiculos->adyacentes() as $nombre_vehiculo_actual => $nodo_vehiculo_actual) {',
            '        if (strcasecmp($nombre_vehiculo_actual, $nombre_vehiculo) === 0) {',
            '            $vehiculo_encontrado = $nodo_vehiculo_actual;',
            '            $nombre_vehiculo = $nombre_vehiculo_actual; // usar el original',
            '            break;',
            '        }',
            '    }',
            '    if (!$vehiculo_encontrado) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '    }',
            '',
            '    // Eliminar foto anterior si existe',
            '    $foto_anterior = $vehiculo_encontrado->adyacente(\'foto\');',
            '    if ($foto_anterior) {',
            '        $ruta_anterior = __DIR__ . \'/../../\' . $foto_anterior->dato();',
            '        if (file_exists($ruta_anterior)) {',
            '            unlink($ruta_anterior);',
            '        }',
            '        // Fase 2, v75a: destruir la hoja `foto` en lugar',
            '        // de solo desenlazarla.',
            '        $vehiculo_encontrado->eliminar_adyacente(\'foto\');',
            '        Nodo::eliminar($foto_anterior);',
            '    }',
            '',
            '    // Crear enlace foto',
            '    $vehiculo_encontrado->_adyacente_en(Nodo::crear_con_dato($ruta_relativa), \'foto\');',
            '',
            '    // Guardar persistencia',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '    return [\'exito\' => true, \'foto\' => $ruta_relativa];',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: bump a 1.5piloto.77w',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77v',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77w',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77w',
        'buscar' => [
            ' * @version   1.5piloto.77v',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77w',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77w',
        'buscar' => [
            '**Tanda actual:** v77v (B3.3d).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77w (fix de `subir_foto_vehiculo`).',
            '',
            '**Fix v77w.** En v77v, el reemplazo del bloque de',
            '`subir_foto_vehiculo` dejó un `continue` huérfano (fuera',
            'de loop/switch) porque solo se reemplazó la primera mitad',
            'del bucle. Eso rompía el parseo de `Vehiculo.php` y',
            'tiraba error fatal en todas las requests. Fix:',
            'reescritura completa del bloque de búsqueda de empresa +',
            'vehículo, con returns tempranos en lugar de `continue`.',
            'También se chequea `$nodo_empresas` y `$nodo_vehiculos`',
            'antes de usarlos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77w',
        'buscar' => [
            '- **v77v** (Fase B3.3d): `Vehiculo.php` pasa a `us_<nombre>`',
        ],
        'reemplazar' => [
            '- **v77w**: fix del `continue` huérfano en',
            '  `subir_foto_vehiculo` (Vehiculo.php) introducido por',
            '  v77v. El reemplazo del bucle dejó un `continue` fuera',
            '  de contexto, lo que rompía el parseo del archivo entero.',
            '  Se reescribió el bloque con returns tempranos.',
            '- **v77v** (Fase B3.3d): `Vehiculo.php` pasa a `us_<nombre>`',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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