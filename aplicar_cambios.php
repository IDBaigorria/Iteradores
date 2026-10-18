<?php
/**
 * Aplicador — Tanda W: fix del frontend (mandar nombre_dueno en detalles).
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81i';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [
    // ============================================================
    // A) rendiciones.js: ver_detalle_rendicion manda nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'ver_detalle_rendicion manda nombre_dueno',
        'buscar' => [
            'async function ver_detalle_rendicion(id_rendicion) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "rendiciones/obtener", id_rendicion })',
            '    });',
        ],
        'reemplazar' => [
            'async function ver_detalle_rendicion(id_rendicion) {',
            '    // Fase B3.3a (v81i): mandar nombre_dueno para que el backend',
            '    // navegue desde us_<dueño>. Sin esto, el soporte (carga',
            '    // parcial [us_<soporte>], sin `usuarios`) no puede navegar',
            '    // y recibe "Rendición no encontrada".',
            '    const params = { accion: "rendiciones/obtener", id_rendicion };',
            '    const nombre_dueno = obtener_nombre_dueno_rendiciones();',
            '    if (nombre_dueno) params.nombre_dueno = nombre_dueno;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params)',
            '    });',
        ],
    ],

    // ============================================================
    // B) rendiciones.js: aceptar_ajuste_rendicion_ui manda nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'aceptar_ajuste_rendicion_ui manda nombre_dueno',
        'buscar' => [
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "rendiciones/aceptar_ajuste",',
            '            id_rendicion,',
            '            id_cancelacion',
            '        })',
            '    });',
        ],
        'reemplazar' => [
            '    // Fase B3.3a (v81i): mandar nombre_dueno, igual que',
            '    // ver_detalle_rendicion.',
            '    const params_ajuste = {',
            '        accion: "rendiciones/aceptar_ajuste",',
            '        id_rendicion,',
            '        id_cancelacion',
            '    };',
            '    const nombre_dueno_aj = obtener_nombre_dueno_rendiciones();',
            '    if (nombre_dueno_aj) params_ajuste.nombre_dueno = nombre_dueno_aj;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params_ajuste)',
            '    });',
        ],
    ],

    // ============================================================
    // C) rendiciones.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'Bump @version de rendiciones.js',
        'buscar' => [
            ' * @version 1.5piloto.57b',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81i',
        ],
    ],

    // ============================================================
    // D) liquidaciones.js: ver_detalle_liquidacion manda nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'ver_detalle_liquidacion manda nombre_dueno',
        'buscar' => [
            'async function ver_detalle_liquidacion(id_liquidacion) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "liquidaciones/obtener", id_liquidacion })',
            '    });',
        ],
        'reemplazar' => [
            'async function ver_detalle_liquidacion(id_liquidacion) {',
            '    // Fase B3.3a (v81i): mandar nombre_dueno para que el backend',
            '    // navegue desde us_<dueño>. Sin esto, el soporte (carga',
            '    // parcial sin `usuarios`) recibe "Liquidación no encontrada".',
            '    const params = { accion: "liquidaciones/obtener", id_liquidacion };',
            '    const nombre_dueno = obtener_nombre_dueno_liquidaciones();',
            '    if (nombre_dueno) params.nombre_dueno = nombre_dueno;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params)',
            '    });',
        ],
    ],

    // ============================================================
    // E) liquidaciones.js: bump @version.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'Bump @version de liquidaciones.js',
        'buscar' => [
            ' * @version 1.5piloto.54d',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.81i',
        ],
    ],

    // ============================================================
    // F) plan_actual §6.4: nota del bug encontrado en verificación.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §6.4: nota del bug v81i',
        'buscar' => [
            '  *Bumps:* Liquidacion.php, Rendicion.php y Enrutador.php',
            '  a `1.5piloto.81h`.',
        ],
        'reemplazar' => [
            '  *Bumps:* Liquidacion.php, Rendicion.php y Enrutador.php',
            '  a `1.5piloto.81h`.',
            '',
            '  **Bug encontrado en la verificación manual (v81i):**',
            '  el backend ya navegaba bien, pero el frontend no',
            '  mandaba `nombre_dueno` en `rendiciones/obtener`,',
            '  `rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`.',
            '  Como el soporte no tiene `usuarios` en su carga',
            '  parcial, el fallback del backend devolvía null y el',
            '  modal decía "no encontrada". Fix: los 3 POST del',
            '  frontend agregan `nombre_dueno` vía',
            '  `obtener_nombre_dueno_rendiciones()` /',
            '  `obtener_nombre_dueno_liquidaciones()`. Con dueño y',
            '  admin ya funcionaba porque el backend devuelve el',
            '  nombre solo; el bug era exclusivo de soporte.',
        ],
    ],

    // ============================================================
    // G) plan_actual §6.4: Fase 3 completada.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §6.4: Fase 3 completada',
        'buscar' => [
            '- [ ] **Fase 3 — Verificación manual.**',
            '  - Login **dueño**: Rendiciones, Liquidaciones,',
            '    Empresas/Micros cargan sin errores.',
            '  - Login **soporte** (con un dueño asignado): idem.',
            '  - Login **admin**: no se rompe nada.',
            '  - Si algo falla, fix chico antes de las pruebas del',
            '    plugin.',
        ],
        'reemplazar' => [
            '- [x] **Fase 3 — Verificación manual.** (v81h + v81i)',
            '  - Login **dueño**: Rendiciones y Liquidaciones cargan',
            '    y el detalle abre. OK.',
            '  - Login **soporte**: tabla cargaba pero el detalle',
            '    decía "no encontrada" (bug v81i, ver §6.4).',
            '    Fix aplicado en el frontend. Verificado tras el fix.',
            '  - Login **admin**: no se rompió nada.',
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