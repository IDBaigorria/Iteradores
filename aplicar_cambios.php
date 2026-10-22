<?php
/**
 * Aplicador — v81n: fix urgente de reparar_arboles.
 *
 * Bug: _reparar_arboles_precalcular indexaba por `p` y `hd`
 * default, ignorando los enlaces parametrizados (p_<term>,
 * hd_<term>). Para un compartido (con árbol parametrizado),
 * $por_padre[$id_cont] daba vacío, y _reparar_arbol
 * interpretaba "contenedor sin hijos" y borraba el
 * hmi_<term>. Eso rompía el árbol del compartido: las ventas
 * viejas quedaban huérfanas y el terminal solo veía las
 * nuevas.
 *
 * Fix: indexar por nombre de enlace (no por nombre fijo),
 * y consultar por el enlace correcto según $nombres.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81n';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // 1. _reparar_arboles_precalcular: indexar por enlace
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Reparacion/RepararArboles.php',
        'descripcion' => 'RepararArboles.php: precalcular indexa por enlace',
        'buscar' => [
            'function _reparar_arboles_precalcular(string $token): array {',
            '    $por_padre = [];',
            '    $es_dest_hd = [];',
            '    Nodo::por_cada_nodo_ejecutar(',
            '        $token,',
            '        function ($nodo) use (&$por_padre, &$es_dest_hd) {',
            '            $ady = (array)$nodo->adyacentes();',
            '            if (isset($ady[\'p\'])) {',
            '                $id_padre = $ady[\'p\']->id();',
            '                $por_padre[$id_padre][] = $nodo;',
            '            }',
            '            if (isset($ady[\'hd\'])) {',
            '                $es_dest_hd[$ady[\'hd\']->id()] = true;',
            '            }',
            '        },',
            '        null',
            '    );',
            '    return [\'por_padre\' => $por_padre, \'es_dest_hd\' => $es_dest_hd];',
            '}',
        ],
        'reemplazar' => [
            'function _reparar_arboles_precalcular(string $token): array {',
            '    // v1.5piloto.81n: indexar por nombre de enlace, no por',
            '    // nombre fijo. Los compartidos usan p_<term> y hd_<term>;',
            '    // con el indexado viejo, $por_padre[$id_cont] daba vacío',
            '    // para esos contenedores y _reparar_arbol los trataba',
            '    // como "sin hijos", borrando el hmi_<term> del compartido.',
            '    //',
            '    // Estructura:',
            '    //   $por_padre[$enlace_p][$id_padre] = [Nodo, ...]',
            '    //   $es_dest_hd[$enlace_hd][$id_nodo] = true',
            '    // donde $enlace_p / $enlace_hd pueden ser "p"/"hd"',
            '    // o "p_<term>"/"hd_<term>".',
            '    $por_padre = [];',
            '    $es_dest_hd = [];',
            '    Nodo::por_cada_nodo_ejecutar(',
            '        $token,',
            '        function ($nodo) use (&$por_padre, &$es_dest_hd) {',
            '            $ady = (array)$nodo->adyacentes();',
            '            foreach ($ady as $enlace => $destino) {',
            '                $enlace = (string)$enlace;',
            '                if ($enlace === \'p\' || strpos($enlace, \'p_\') === 0) {',
            '                    $por_padre[$enlace][$destino->id()][] = $nodo;',
            '                }',
            '                if ($enlace === \'hd\' || strpos($enlace, \'hd_\') === 0) {',
            '                    $es_dest_hd[$enlace][$destino->id()] = true;',
            '                }',
            '            }',
            '        },',
            '        null',
            '    );',
            '    return [\'por_padre\' => $por_padre, \'es_dest_hd\' => $es_dest_hd];',
            '}',
        ],
    ],

    // ============================================================
    // 2. _reparar_arbol: consultar con $n['p'] y $n['hd']
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Reparacion/RepararArboles.php',
        'descripcion' => 'RepararArboles.php: _reparar_arbol consulta por enlace',
        'buscar' => [
            'function _reparar_arbol(Nodo $contenedor, ?array $nombres, array $por_padre, array $es_dest_hd): array {',
            '    $n = _arbol_nombres($nombres);',
            '    $id_cont = $contenedor->id();',
            '    $cambios = 0;',
            '    $detalles = [];',
            '',
            '    $hijos = $por_padre[$id_cont] ?? [];',
            '',
            '    $primer = null;',
            '    $candidatos = 0;',
            '    foreach ($hijos as $hijo) {',
            '        $id_h = $hijo->id();',
            '        if (!isset($es_dest_hd[$id_h])) {',
            '            $primer = $hijo;',
            '            $candidatos++;',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            'function _reparar_arbol(Nodo $contenedor, ?array $nombres, array $por_padre, array $es_dest_hd): array {',
            '    $n = _arbol_nombres($nombres);',
            '    $id_cont = $contenedor->id();',
            '    $cambios = 0;',
            '    $detalles = [];',
            '',
            '    // v1.5piloto.81n: consultar por el enlace concreto del',
            '    // árbol (default o parametrizado), no por nombre fijo.',
            '    $hijos = $por_padre[$n[\'p\']][$id_cont] ?? [];',
            '    $es_dest_hd_del_arbol = $es_dest_hd[$n[\'hd\']] ?? [];',
            '',
            '    $primer = null;',
            '    $candidatos = 0;',
            '    foreach ($hijos as $hijo) {',
            '        $id_h = $hijo->id();',
            '        if (!isset($es_dest_hd_del_arbol[$id_h])) {',
            '            $primer = $hijo;',
            '            $candidatos++;',
            '        }',
            '    }',
        ],
    ],

    // ============================================================
    // 3. Guard extra: no borrar hmi si hay hijos reales en el grafo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Reparacion/RepararArboles.php',
        'descripcion' => 'RepararArboles.php: guard defensivo en "sin hijos"',
        'buscar' => [
            '    if (empty($hijos)) {',
            '        if ($contenedor->adyacente($n[\'hmi\'])) {',
            '            $contenedor->eliminar_adyacente($n[\'hmi\']);',
            '            $cambios++;',
            '            $detalles[] = "contenedor $id_cont: hmi borrado (sin hijos)";',
            '        }',
            '    } elseif ($candidatos === 0) {',
        ],
        'reemplazar' => [
            '    if (empty($hijos)) {',
            '        // Guard defensivo (v1.5piloto.81n): antes de borrar',
            '        // el hmi, verificar que el contenedor realmente no',
            '        // tenga hijos navegables. Si el precalcular falló',
            '        // (mapa incompleto), no queremos borrar un hmi',
            '        // que sí es válido. Se navega el hmi y se chequea',
            '        // que el nodo destino exista y tenga p al contenedor.',
            '        $hmi_actual = $contenedor->adyacente($n[\'hmi\']);',
            '        $hmi_es_valido = false;',
            '        if ($hmi_actual) {',
            '            $p_del_hmi = $hmi_actual->adyacente($n[\'p\']);',
            '            if ($p_del_hmi && $p_del_hmi->id() === $id_cont) {',
            '                $hmi_es_valido = true;',
            '            }',
            '        }',
            '        if ($hmi_es_valido) {',
            '            $detalles[] = "contenedor $id_cont: hmi apunta a hijo con p correcto, no se borra (revisar precalcular)";',
            '        } elseif ($hmi_actual) {',
            '            $contenedor->eliminar_adyacente($n[\'hmi\']);',
            '            $cambios++;',
            '            $detalles[] = "contenedor $id_cont: hmi borrado (sin hijos, hmi apuntaba a nodo sin p)";',
            '        }',
            '    } elseif ($candidatos === 0) {',
        ],
    ],

    // ============================================================
    // 4. Bump del módulo (solo @version, no toco código)
    // ============================================================
    // (el runner hace el bump automático por $version_nueva)

];

// ============================================================
// Runner v2
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
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
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
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
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