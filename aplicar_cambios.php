<?php
/**
 * Aplicador de cambios — Framework PHP (Iteradores).
 *
 * Tanda V1.5i.7i:
 *   - Nuevo comando `grafo:eliminar_huerfanos` en
 *     registrar_comandos_grafo(). Elimina todos los nodos no
 *     alcanzables desde las raíces del grafo.
 *   - Bump de la sección a 1.5i.7i.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: bump comentario sección a v1.5i.7i',
        'buscar' => [
            '    // COMANDOS DEL VISUALIZADOR DE GRAFO (v1.5piloto.74p)',
        ],
        'reemplazar' => [
            '    // COMANDOS DEL VISUALIZADOR DE GRAFO (v1.5i.7i)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: agregar grafo:eliminar_huerfanos',
        'buscar' => [
            '            return [',
            '                \'id\' => $id,',
            '                \'dato\' => (string)$nodo->dato(),',
            '                \'es_especial\' => !is_numeric($id),',
            '                \'adyacentes\' => $adyacentes,',
            '                \'referencias\' => $referencias,',
            '            ];',
            '        }, null, false);',
            '    }',
        ],
        'reemplazar' => [
            '            return [',
            '                \'id\' => $id,',
            '                \'dato\' => (string)$nodo->dato(),',
            '                \'es_especial\' => !is_numeric($id),',
            '                \'adyacentes\' => $adyacentes,',
            '                \'referencias\' => $referencias,',
            '            ];',
            '        }, null, false);',
            '',
            '        // ─── grafo:eliminar_huerfanos ─────────────────────',
            '        //',
            '        // Elimina todos los nodos no alcanzables desde las',
            '        // raíces (IDs especiales) del grafo. Solo los llama',
            '        // el enrutador del piloto (admin o soporte).',
            '        //',
            '        // No lleva chequeo de es_pruebas: se usa en producción',
            '        // para limpiar la acumulación de nodos basura. El',
            '        // enrutador es quien decide cuándo exponerlo.',
            '        //',
            '        // Algoritmo:',
            '        //   1. Cargar estructura y hacer BFS desde las raíces.',
            '        //   2. Huérfanos = todos los que no son alcanzables.',
            '        //   3. Desenlazar las salientes de cada huérfano que',
            '        //      apunten a otro huérfano (para que no queden',
            '        //      referencias entrantes entre ellos).',
            '        //   4. Nodo::eliminar cada huérfano.',
            '        //   5. El enrutador guarda el grafo después.',
            '        //',
            '        // Devuelve { eliminados, total_huerfanos }.',
            '        self::registrar_comando(\'grafo:eliminar_huerfanos\', function(string $token, array $args) {',
            '            $nodos = self::_grafo_cargar_estructura($token);',
            '            $alcanzables = self::_grafo_bfs_desde_raices($nodos);',
            '',
            '            $huerfanos = [];',
            '            foreach ($nodos as $id => $info) {',
            '                if (!isset($alcanzables[$id])) {',
            '                    $huerfanos[$id] = true;',
            '                }',
            '            }',
            '',
            '            $total_huerfanos = count($huerfanos);',
            '            if ($total_huerfanos === 0) {',
            '                return [\'eliminados\' => 0, \'total_huerfanos\' => 0];',
            '            }',
            '',
            '            foreach ($huerfanos as $id => $_) {',
            '                $nodo = Nodo::nodo_por_id($id);',
            '                if (!$nodo) continue;',
            '                $adyacentes = $nodo->adyacentes();',
            '                if (!$adyacentes) continue;',
            '                foreach ($adyacentes as $enlace => $destino) {',
            '                    if (isset($huerfanos[$destino->id()])) {',
            '                        $nodo->eliminar_adyacente((string)$enlace);',
            '                    }',
            '                }',
            '            }',
            '',
            '            $eliminados = 0;',
            '            foreach ($huerfanos as $id => $_) {',
            '                $nodo = Nodo::nodo_por_id($id);',
            '                if ($nodo && Nodo::eliminar($nodo)) {',
            '                    $eliminados++;',
            '                }',
            '            }',
            '',
            '            return [',
            '                \'eliminados\' => $eliminados,',
            '                \'total_huerfanos\' => $total_huerfanos,',
            '            ];',
            '        }, null, false);',
            '    }',
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
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] " . count($creaciones) . " crear, " . $total_reemplazos . " reemplazos en " . count($reemplazos_por_archivo) . " archivos.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No legible: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1);
}
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
foreach ($eliminaciones as $e) { $r = $raiz_proyecto.'/'.$e['archivo']; if (!file_exists($r)){echo "[INFO] {$e['archivo']} no existía\n";continue;} if (unlink($r)) echo "[OK] {$e['archivo']} (eliminado)\n"; else echo "[FALLO] Eliminar: {$e['archivo']}\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";