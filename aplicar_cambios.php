<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77t (Fase B3.3a).
 *   - Liquidacion.php: 3 resoluciones de dueño → us_<nombre>.
 *   - Rendicion.php: 4 resoluciones de dueño/terminal → us_<nombre>.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Liquidacion.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'Liquidacion: obtener_contenedor_liquidaciones_dueno via us_',
        'buscar' => [
            'function obtener_contenedor_liquidaciones_dueno(string $nombre_dueno): ?Nodo {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '    $nodo_liquidaciones = $nodo_dueno->adyacente(\'liquidaciones\');',
        ],
        'reemplazar' => [
            'function obtener_contenedor_liquidaciones_dueno(string $nombre_dueno): ?Nodo {',
            '    // Fase B3.3a: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '    $nodo_liquidaciones = $nodo_dueno->adyacente(\'liquidaciones\');',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'Liquidacion: previsualizar_liquidacion via us_',
        'buscar' => [
            'function previsualizar_liquidacion(string $nombre_dueno): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function previsualizar_liquidacion(string $nombre_dueno): array {',
            '    // Fase B3.3a: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'Liquidacion: confirmar_liquidacion via us_',
        'buscar' => [
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_ef = $nodo_dueno->adyacente(\'efectivo\');',
        ],
        'reemplazar' => [
            '    // Fase B3.3a: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '',
            '    $nodo_ef = $nodo_dueno->adyacente(\'efectivo\');',
        ],
    ],

    // Bump de version en Liquidacion.php
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'Liquidacion: bump a 1.5piloto.77t',
        'buscar' => [
            ' * @since     1.5piloto.54',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.54',
            ' * @version   1.5piloto.77t',
        ],
    ],

    // ============================================================
    // Rendicion.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion: obtener_contenedor_rendiciones_dueno via us_',
        'buscar' => [
            'function obtener_contenedor_rendiciones_dueno(string $nombre_dueno): ?Nodo {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '    $nodo_rendiciones = $nodo_dueno->adyacente(\'rendiciones\');',
        ],
        'reemplazar' => [
            'function obtener_contenedor_rendiciones_dueno(string $nombre_dueno): ?Nodo {',
            '    // Fase B3.3a: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '    $nodo_rendiciones = $nodo_dueno->adyacente(\'rendiciones\');',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion: previsualizar_rendicion via us_',
        'buscar' => [
            'function previsualizar_rendicion(string $nombre_dueno, array $filtros): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
        'reemplazar' => [
            'function previsualizar_rendicion(string $nombre_dueno, array $filtros): array {',
            '    // Fase B3.3a: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion: confirmar_rendicion via us_ (dueño + terminales)',
        'buscar' => [
            '    $raiz_usuarios_mov = Nodo::nodo_por_id(\'usuarios\');',
            '    $nodo_dueno_mov = $raiz_usuarios_mov ? $raiz_usuarios_mov->adyacente($nombre_dueno) : null;',
            '',
            '    if ($nodo_dueno_mov) {',
            '        // Descontar de cada terminal involucrada.',
            '        foreach (array_values($por_terminal) as $t) {',
            '            $nodo_term = $raiz_usuarios_mov->adyacente($t[\'terminal\']);',
            '            if (!$nodo_term) continue;',
        ],
        'reemplazar' => [
            '    // Fase B3.3a: IDs especiales directos.',
            '    $nodo_dueno_mov = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '',
            '    if ($nodo_dueno_mov) {',
            '        // Descontar de cada terminal involucrada.',
            '        foreach (array_values($por_terminal) as $t) {',
            '            $nodo_term = Nodo::nodo_por_id(\'us_\' . $t[\'terminal\']);',
            '            if (!$nodo_term) continue;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion: bump a 1.5piloto.77t',
        'buscar' => [
            ' * @since     1.5piloto.50',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.50',
            ' * @version   1.5piloto.77t',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual + avance B3.3a
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77t (B3.3a)',
        'buscar' => [
            '**Tanda actual:** v77s (fix del `$raiz` en eliminar_usuario).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77t (B3.3a).',
            '',
            '**B3.3a (v77t).** `Liquidacion.php` y `Rendicion.php`:',
            '3 y 4 resoluciones de dueño/terminal pasadas a',
            '`us_<nombre>`. Quedan sin tocar los 3 helpers que iteran',
            'sobre todos los dueños buscando por id',
            '(`obtener_liquidacion_por_id`, `aceptar_ajuste_rendicion`,',
            '`obtener_rendicion_por_id`); requieren diseño y son',
            'B3.3c.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — agregar v77t
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77t',
        'buscar' => [
            '- **v77s**: fix del `$raiz` residual en `eliminar_usuario`',
        ],
        'reemplazar' => [
            '- **v77t** (Fase B3.3a): `Liquidacion.php` y `Rendicion.php`',
            '  pasan a `us_<nombre>` en las 7 resoluciones de dueño/terminal',
            '  que reciben el nombre por parámetro. Quedan sin tocar los',
            '  3 helpers que iteran sobre todos los dueños buscando por',
            '  id (B3.3c).',
            '- **v77s**: fix del `$raiz` residual en `eliminar_usuario`',
        ],
    ],

    // ============================================================
    // index.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77t',
        'buscar' => [
            ' * @version   1.5piloto.77s',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77t',
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