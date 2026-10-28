<?php
/**
 * Aplicador — Fix del falso positivo en la verificación de máscaras
 * (bug §4.7, Fase 1).
 *
 * La verificación de máscaras de `_guardar_con_filtro` (v1.5i.7t)
 * marcaba como "PERDIDA" a los nodos que se destruyen
 * legítimamente durante la request (por ejemplo, la venta_actual
 * al confirmar la venta). Esos nodos ya no están en `$mascaras`
 * (porque se borraron del grafo), y `$mascaras[$id] ?? 0` da 0.
 *
 * Fix: distinguir "nodo borrado del grafo" (correcto) de
 * "nodo que sigue en el grafo pero perdió bits" (bug real).
 * Agregar `if (!Nodo::existe($id_nodo_prev)) continue;`.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5i.7u';

$htmls_a_bumpear = [];

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: distinguir nodo borrado de nodo que perdió bits',
    'buscar' => [
        '            if ($mf_parcial !== 0 && !empty($mascaras_viejas)) {',
        '                $perdidos = [];',
        '                foreach ($mascaras_viejas as $id_fila_viejo => $mascara_vieja) {',
        '                    if ($mascara_vieja === 0) continue;',
        '                    $id_nodo_prev = $mapa_fila_a_id_previo[$id_fila_viejo] ?? null;',
        '                    if ($id_nodo_prev === null) continue; // nodo borrado, válido',
        '                    $mascara_nueva = (int)($mascaras[$id_nodo_prev] ?? 0);',
        '                    if (($mascara_nueva & $mascara_vieja) !== $mascara_vieja) {',
        '                        $perdidos[] = ["id_nodo" => $id_nodo_prev, "id_fila" => $id_fila_viejo, "vieja" => $mascara_vieja, "nueva" => $mascara_nueva];',
        '                    }',
        '                }',
    ],
    'reemplazar' => [
        '            if ($mf_parcial !== 0 && !empty($mascaras_viejas)) {',
        '                $perdidos = [];',
        '                foreach ($mascaras_viejas as $id_fila_viejo => $mascara_vieja) {',
        '                    if ($mascara_vieja === 0) continue;',
        '                    $id_nodo_prev = $mapa_fila_a_id_previo[$id_fila_viejo] ?? null;',
        '                    if ($id_nodo_prev === null) continue; // nodo borrado, válido',
        '                    // Fix 1.5i.7u: si el nodo ya no está en el grafo,',
        '                    // se destruyó legítimamente durante la request',
        '                    // (por ejemplo, la venta_actual al confirmar).',
        '                    // No es pérdida de bits, es borrado. Válido.',
        '                    if (!Nodo::existe($id_nodo_prev)) continue;',
        '                    $mascara_nueva = (int)($mascaras[$id_nodo_prev] ?? 0);',
        '                    if (($mascara_nueva & $mascara_vieja) !== $mascara_vieja) {',
        '                        $perdidos[] = ["id_nodo" => $id_nodo_prev, "id_fila" => $id_fila_viejo, "vieja" => $mascara_vieja, "nueva" => $mascara_nueva];',
        '                    }',
        '                }',
    ],
],

// ============================================================
// plan_actual.md: documentar el falso positivo y el fix
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83c',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83b (Fases 1 y 2 del',
        'bug crítico §4.7: blindaje del guardado parcial + logging',
        'de diagnóstico. Framework 1.5i.7t).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83c (Fase 3 del',
        'bug §4.7: primer ciclo con blindaje detectó un falso',
        'positivo en la verificación de máscaras. El bug real NO',
        'se reprodujo. Fix del falso positivo en 1.5i.7u).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: Fase 3 con falso positivo y fix',
    'buscar' => [
        '**Fase 3 — Reproducir el bug en local.**',
        '- Con el backup restaurado + migración + log activo.',
        '- Secuencia: agregar micro → migrar → eliminar micro',
        '  viejo → pagar cupón.',
        '- Leer el log y aislar el punto exacto de corrupción.',
    ],
    'reemplazar' => [
        '**Fase 3 — Reproducir el bug en local. EN CURSO.**',
        '- Con el backup restaurado + migración + log activo.',
        '- Secuencia probada: vender un pasaje en el micro de',
        '  2 pisos. El blindaje abortó con rollback.',
        '- **Diagnóstico del aborto:** falso positivo. La',
        '  verificación de máscaras marcó como "PERDIDA" a los',
        '  5 nodos que `_destruir_venta_actual` destruye',
        '  legítimamente al confirmar la venta (`venta_actual`,',
        '  cabeza de asientos, `asiento_venta`, `viaje`, `micro`).',
        '  Esos nodos ya no están en `$mascaras` porque se',
        '  borraron del grafo, entonces `$mascaras[$id] ?? 0` = 0.',
        '  La verificación no distinguía "borrado" de "perdió',
        '  bits".',
        '- **Fix (1.5i.7u):** agregar',
        '  `if (!Nodo::existe($id_nodo_prev)) continue;` antes de',
        '  comparar máscaras. `Nodo::existe()` es O(1)',
        '  (`isset(self::$superestructura[$id])`).',
        '- **Importante:** el bug real §4.7 (nodo pierde bits en',
        '  el BFS) **NO se reprodujo** en este flujo. Los guardar',
        '  total del bootstrap (login, recarga) pasaron todos con',
        '  `verif_nodos ok=1` y `verif_aristas ok=1`. La DB creció',
        '  legítimamente por los nodos de sesión, no por',
        '  corrupción. **Próximo paso:** volver a probar con el',
        '  fix. Si el ciclo completo (migración + venta +',
        '  cupón) no rompe, evaluar marcar §4.7 como "no',
        '  reproducido post-restauración + blindaje". Si rompe,',
        '  ahora sí el log va a mostrar el punto exacto.',
    ],
],

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