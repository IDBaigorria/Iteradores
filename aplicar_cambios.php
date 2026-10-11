<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77y (Fase B3.3e).
 *   - Migraciones/Funciones.php: detectar_usuarios_especiales y
 *     detectar_niveles_usuario reescritos con por_cada_nodo_ejecutar.
 *   - Migraciones/Comandos.php: app:crear_compartidos_terminal usa
 *     us_<nombre_terminal>.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Funciones.php — detectar_usuarios_especiales
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Funciones.php',
        'descripcion' => 'Funciones: detectar_usuarios_especiales con por_cada_nodo',
        'buscar' => [
            'function detectar_usuarios_especiales(string $token): bool {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return true; // sin usuarios: trivialmente aplicada',
            '    $adyacentes = $raiz->adyacentes();',
            '    if (!$adyacentes) return true;',
            '    foreach ($adyacentes as $nombre => $nodo) {',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return false;',
            '    }',
            '    return true;',
            '}',
        ],
        'reemplazar' => [
            'function detectar_usuarios_especiales(string $token): bool {',
            '    // Fase B3.3e: reescrito con por_cada_nodo_ejecutar,',
            '    // alineado con los demás detectores del archivo. Un',
            '    // "usuario" es cualquier nodo con adyacente `nivel` (o',
            '    // `publico.nivel`). Si algún usuario no tiene prefijo',
            '    // `us_` en su id, la migración no está aplicada.',
            '    $falta_alguno = false;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {',
            '        if ($falta_alguno) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') === 0) return null; // ya está OK',
            '        // No tiene prefijo: ¿es un usuario?',
            '        $nivel = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel = $publico->adyacente(\'nivel\');',
            '        }',
            '        if ($nivel) $falta_alguno = true;',
            '        return null;',
            '    }, null);',
            '    return !$falta_alguno;',
            '}',
        ],
    ],

    // ============================================================
    // Funciones.php — detectar_niveles_usuario
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Funciones.php',
        'descripcion' => 'Funciones: detectar_niveles_usuario con por_cada_nodo',
        'buscar' => [
            'function detectar_niveles_usuario(string $token): bool {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return true;',
            '    $adyacentes = $raiz->adyacentes();',
            '    if (!$adyacentes) return true;',
            '    foreach ($adyacentes as $nombre => $nodo) {',
            '        if (!$nodo->adyacente(\'publico\')) return false;',
            '    }',
            '    return true;',
            '}',
        ],
        'reemplazar' => [
            'function detectar_niveles_usuario(string $token): bool {',
            '    // Fase B3.3e: reescrito con por_cada_nodo_ejecutar,',
            '    // alineado con los demás detectores. Si algún nodo con',
            '    // prefijo `us_` no tiene contenedor `publico`, la',
            '    // migración no está aplicada.',
            '    $falta_alguno = false;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {',
            '        if ($falta_alguno) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null; // no es usuario',
            '        if (!$nodo->adyacente(\'publico\')) $falta_alguno = true;',
            '        return null;',
            '    }, null);',
            '    return !$falta_alguno;',
            '}',
        ],
    ],

    // ============================================================
    // Comandos.php — app:crear_compartidos_terminal
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comandos: crear_compartidos_terminal usa us_',
        'buscar' => [
            '                // Llenar terminales: solo el propio.',
            '                $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '                $nodo_term = $raiz ? $raiz->adyacente($nombre_terminal) : null;',
            '                if ($nodo_term) {',
            '                    $c_terminales->_adyacente_en($nodo_term, $nombre_terminal);',
            '                }',
        ],
        'reemplazar' => [
            '                // Llenar terminales: solo el propio.',
            '                // Fase B3.3e: ID especial directo.',
            '                $nodo_term = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '                if ($nodo_term) {',
            '                    $c_terminales->_adyacente_en($nodo_term, $nombre_terminal);',
            '                }',
        ],
    ],

    // ============================================================
    // Comandos.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comandos: bump a 1.5piloto.77y',
        'buscar' => [
            ' * @since 1.5piloto.76n',
            ' * @version 1.5piloto.77k',
        ],
        'reemplazar' => [
            ' * @since 1.5piloto.76n',
            ' * @version 1.5piloto.77y',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual a v77y
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77y',
        'buscar' => [
            '**Tanda actual:** v77x (fix de `viajes-micros.js` tras B3.3d).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77y (B3.3e).',
            '',
            '**B3.3e (v77y).** `Migraciones/Funciones.php`:',
            '`detectar_usuarios_especiales` y `detectar_niveles_usuario`',
            'reescritos con `Nodo::por_cada_nodo_ejecutar`, alineados',
            'con los otros detectores del archivo. `Migraciones/Comandos.php`:',
            '`app:crear_compartidos_terminal` resuelve el terminal por',
            '`us_<nombre>` en lugar de `usuarios`.',
            '',
            '**B3.3c sigue pendiente.** Los helpers que iteran sobre',
            'todos los dueños buscando por id requieren rediseño:',
            '`Venta.php` (`_buscar_venta_por_id` sin filtro,',
            '`obtener_cancelacion_por_id`), `Impresion.php` (2),',
            '`Liquidacion.php` (`obtener_liquidacion_por_id`),',
            '`Rendicion.php` (`aceptar_ajuste_rendicion`,',
            '`obtener_rendicion_por_id`), `Pasajero.php`',
            '(`obtener_pasajero_por_dni`).',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — agregar v77y
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77y',
        'buscar' => [
            '- **v77x**: fix de `viajes-micros.js`. El POST a',
        ],
        'reemplazar' => [
            '- **v77y** (Fase B3.3e): `Migraciones/Funciones.php`',
            '  alinea `detectar_usuarios_especiales` y',
            '  `detectar_niveles_usuario` con `por_cada_nodo_ejecutar`.',
            '  `Migraciones/Comandos.php` usa `us_<nombre>` en',
            '  `app:crear_compartidos_terminal`.',
            '- **v77x**: fix de `viajes-micros.js`. El POST a',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77y',
        'buscar' => [
            ' * @version   1.5piloto.77x',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77y',
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