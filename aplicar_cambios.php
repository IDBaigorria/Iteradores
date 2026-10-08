<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76m (fix de la migración de niveles).
 *   - Controlador.php: el comando grafo:crear_niveles_usuario
 *     valida que el usuario objetivo exista antes de recorrer.
 *   - prompts/prompt_piloto.md: registro del fix en el historial.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Controlador/Controlador.php — validación de existencia
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: validar usuario objetivo en crear_niveles_usuario',
        'buscar' => [
            '            $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '            if (!$raiz) {',
            '                return [\'migrados\' => 0, \'saltados\' => 0, \'errores\' => [\'No existe el nodo usuarios.\']];',
            '            }',
            '            $adyacentes = $raiz->adyacentes();',
        ],
        'reemplazar' => [
            '            $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '            if (!$raiz) {',
            '                return [\'migrados\' => 0, \'saltados\' => 0, \'errores\' => [\'No existe el nodo usuarios.\']];',
            '            }',
            '            if ($objetivo !== \'todos\' && !$raiz->adyacente($objetivo)) {',
            '                return [',
            '                    \'migrados\' => 0,',
            '                    \'saltados\' => 0,',
            '                    \'errores\' => ["No existe un usuario con el nombre \'$objetivo\'."],',
            '                ];',
            '            }',
            '            $adyacentes = $raiz->adyacentes();',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — registro del fix
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: registrar fix en el historial v76m',
        'buscar' => [
            '  (opcional `&usuario=carmen1`). Solo grafo de la app: el',
            '  de credenciales queda plano por ahora.',
        ],
        'reemplazar' => [
            '  (opcional `&usuario=carmen1`). Solo grafo de la app: el',
            '  de credenciales queda plano por ahora. Fix posterior:',
            '  si el `&usuario=X` no existe, el comando devuelve error',
            '  y no migra nada (antes devolvía `migrados: 0` sin avisar).',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
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