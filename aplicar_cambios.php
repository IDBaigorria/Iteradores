<?php
/**
 * Aplicador — Tanda C.2: endpoint ?eliminar_huerfanos=1.
 *
 * Agrega un bloque en index.php (antes del bloque de auditoría)
 * que:
 *   1. Valida nivel (admin/soporte) por nombre_solicitante.
 *   2. Fuerza carga total (para ver TODOS los nodos de la DB).
 *   3. Ejecuta grafo:eliminar_huerfanos.
 *   4. Devuelve JSON con { eliminados, total_huerfanos }.
 *
 * Motivo: la pestaña Grafo, operando en carga parcial, no ve
 * los huérfanos (contexto_mascara=0, no matchean con ningún bit).
 * El botón "Eliminar nodos basura" no los puede eliminar. Este
 * endpoint es el que sí puede.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81s';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'Endpoint ?eliminar_huerfanos=1',
    'buscar' => [
        '// ==== Auditoría topológica (v1.5piloto.81p) ====',
    ],
    'reemplazar' => [
        '// ==== Eliminar huérfanos (v1.5piloto.81s) ====',
        '// Solo admin/soporte. Fuerza carga total para ver los',
        '// nodos de la DB que tienen contexto_mascara=0 (no',
        '// alcanzables desde ninguna raíz). La pestaña Grafo,',
        '// en carga parcial, no los ve.',
        '//',
        '// Uso: index.php?eliminar_huerfanos=1&nombre_solicitante=<admin_o_soporte>',
        '// En local se puede omitir nombre_solicitante.',
        'if (isset($_GET[\'eliminar_huerfanos\'])) {',
        '    header(\'Content-Type: application/json; charset=utf-8\');',
        '',
        '    // Si el grafo quedó parcial, recargar total.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        $ok_recarga_h = Controlador::cargar($nombre_app);',
        '        if ($ok_recarga_h !== true) {',
        '            http_response_code(500);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => \'No se pudo recargar el grafo completo para eliminar huérfanos.\',',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    }',
        '',
        '    $nombre_sol_h = trim((string)($_GET[\'nombre_solicitante\'] ?? \'\'));',
        '    if ($nombre_sol_h === \'\') {',
        '        if (!Conf::LOCAL) {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => \'Falta nombre_solicitante.\',',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    } else {',
        '        $nodo_sol_h = Nodo::nodo_por_id(\'us_\' . $nombre_sol_h);',
        '        if (!$nodo_sol_h) {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => "Solicitante \'$nombre_sol_h\' no encontrado.",',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '        $nivel_nodo_h = $nodo_sol_h->adyacente(\'nivel\');',
        '        $nivel_h = $nivel_nodo_h ? (string)$nivel_nodo_h->dato() : \'\';',
        '        if ($nivel_h !== \'admin\' && $nivel_h !== \'soporte\') {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => "Permiso denegado. Nivel \'$nivel_h\'.",',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    }',
        '',
        '    $resultado_h = Controlador::ejecutar_comando(\'grafo:eliminar_huerfanos\');',
        '    if (!is_array($resultado_h)) {',
        '        http_response_code(500);',
        '        echo json_encode([',
        '            \'exito\' => false,',
        '            \'error\' => \'El comando grafo:eliminar_huerfanos no devolvió resultado.\',',
        '        ], JSON_UNESCAPED_UNICODE);',
        '        exit;',
        '    }',
        '    $eliminados_h = (int)($resultado_h[\'eliminados\'] ?? 0);',
        '    if ($eliminados_h > 0) {',
        '        guardar_ambos($nombre_app);',
        '    }',
        '    echo json_encode([',
        '        \'exito\' => true,',
        '        \'eliminados\' => $eliminados_h,',
        '        \'total_huerfanos\' => (int)($resultado_h[\'total_huerfanos\'] ?? 0),',
        '        \'guardado\' => $eliminados_h > 0,',
        '    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);',
        '    exit;',
        '}',
        '',
        '// ==== Auditoría topológica (v1.5piloto.81p) ====',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

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
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";