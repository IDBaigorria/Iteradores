<?php
/**
 * Aplicador — v81k: arreglar el frontend de ventas.js para que
 * mande nombre_dueno en los fetch que operan sobre una venta.
 *
 * Causa raíz del bug "para soporte sigue fallando" en B3.3c:
 * el backend (v81j) ya navega por us_<dueño>, pero el frontend
 * no le pasa el nombre_dueno. Con carga parcial del soporte
 * (sin `usuarios` en memoria), la búsqueda cae al fallback y
 * devuelve "no encontrada".
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81k';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // Helper: nombre del dueño actual de la pestaña Vendidos.
    // Se inserta después de configurar_filtros_vendidos.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: agregar helper _nombre_dueno_para_peticiones_vendidos',
        'buscar' => [
            'function configurar_filtros_vendidos() {',
            '    const cont_dueno = document.getElementById(\'contenedor_filtro_dueno_vendidos\');',
            '    const es_admin = es_admin_o_soporte();',
            '    if (cont_dueno) cont_dueno.style.display = es_admin ? \'\' : \'none\';',
            '',
            '    // El botón "Cerrar rendición" es exclusivo del dueño.',
            '    const btn_rendir = document.getElementById(\'boton_cerrar_rendicion\');',
            '    if (btn_rendir) {',
            '        btn_rendir.style.display = (usuario_actual.nivel === \'dueno\') ? \'\' : \'none\';',
            '    }',
            '',
            '    // El botón "Ver cancelaciones" se muestra a dueño y terminal, y',
            '    // a admin solo cuando hay un dueño seleccionado.',
            '    actualizar_visibilidad_boton_cancelaciones();',
            '}',
        ],
        'reemplazar' => [
            'function configurar_filtros_vendidos() {',
            '    const cont_dueno = document.getElementById(\'contenedor_filtro_dueno_vendidos\');',
            '    const es_admin = es_admin_o_soporte();',
            '    if (cont_dueno) cont_dueno.style.display = es_admin ? \'\' : \'none\';',
            '',
            '    // El botón "Cerrar rendición" es exclusivo del dueño.',
            '    const btn_rendir = document.getElementById(\'boton_cerrar_rendicion\');',
            '    if (btn_rendir) {',
            '        btn_rendir.style.display = (usuario_actual.nivel === \'dueno\') ? \'\' : \'none\';',
            '    }',
            '',
            '    // El botón "Ver cancelaciones" se muestra a dueño y terminal, y',
            '    // a admin solo cuando hay un dueño seleccionado.',
            '    actualizar_visibilidad_boton_cancelaciones();',
            '}',
            '',
            '/**',
            ' * Devuelve el nombre del dueño cuyas ventas se están viendo en la',
            ' * pestaña Vendidos, según el rol del usuario actual.',
            ' *',
            ' * - Admin/soporte: el dueño elegido en el selector.',
            ' * - Dueño: él mismo.',
            ' * - Terminal: cadena vacía (el backend resuelve su contexto con',
            ' *   `_nombre_terminal_para_busqueda`).',
            ' *',
            ' * Se usa en los fetch que operan sobre una venta concreta (obtener,',
            ' * pagar cupón, cancelar, cancelaciones/obtener). Sin nombre_dueno,',
            ' * el backend con carga parcial del soporte (sin `usuarios` en',
            ' * memoria) no puede navegar `us_<dueño>` y devuelve "no encontrada".',
            ' *',
            ' * @returns {string}',
            ' * @since 1.5piloto.81k',
            ' */',
            'function _nombre_dueno_para_peticiones_vendidos() {',
            '    if (!usuario_actual) return \'\';',
            '    if (es_admin_o_soporte()) {',
            '        const sel = document.getElementById(\'selector_dueno_vendidos\');',
            '        return (sel && sel.value) ? sel.value : \'\';',
            '    }',
            '    if (usuario_actual.nivel === \'dueno\') return usuario_actual.nombre_usuario;',
            '    return \'\';',
            '}',
        ],
    ],

    // ============================================================
    // ver_cuponera_venta: mandar nombre_dueno
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: ver_cuponera_venta manda nombre_dueno',
        'buscar' => [
            'async function ver_cuponera_venta(id_venta) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "ventas/obtener", id_venta })',
            '    });',
        ],
        'reemplazar' => [
            'async function ver_cuponera_venta(id_venta) {',
            '    // Fix B3.3c (v81k): agregar nombre_dueno para admin/soporte.',
            '    // Sin esto, "ventas/obtener" no puede navegar us_<dueño> en',
            '    // carga parcial y devuelve "Venta no encontrada".',
            '    const params_cuponera = { accion: "ventas/obtener", id_venta };',
            '    const nombre_dueno_cuponera = _nombre_dueno_para_peticiones_vendidos();',
            '    if (nombre_dueno_cuponera) params_cuponera.nombre_dueno = nombre_dueno_cuponera;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params_cuponera)',
            '    });',
        ],
    ],

    // ============================================================
    // _abrir_modal_pagar_cupon: mandar nombre_dueno
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _abrir_modal_pagar_cupon manda nombre_dueno',
        'buscar' => [
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "ventas/pagar_cupon",',
            '                id_venta: venta.id_venta,',
            '                numero_cupon: cupon.numero,',
            '                monto: monto_num.toFixed(2),',
            '                metodo_pago: metodo',
            '            })',
            '        });',
        ],
        'reemplazar' => [
            '        // Fix B3.3c (v81k): agregar nombre_dueno para admin/soporte.',
            '        const params_pagar = {',
            '            accion: "ventas/pagar_cupon",',
            '            id_venta: venta.id_venta,',
            '            numero_cupon: cupon.numero,',
            '            monto: monto_num.toFixed(2),',
            '            metodo_pago: metodo',
            '        };',
            '        const nombre_dueno_pagar = _nombre_dueno_para_peticiones_vendidos();',
            '        if (nombre_dueno_pagar) params_pagar.nombre_dueno = nombre_dueno_pagar;',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams(params_pagar)',
            '        });',
        ],
    ],

    // ============================================================
    // cancelar_venta: fetch 1 (info_cancelacion) manda nombre_dueno
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: cancelar_venta info_cancelacion manda nombre_dueno',
        'buscar' => [
            'async function cancelar_venta(id_venta) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "ventas/info_cancelacion", id_venta })',
            '    });',
        ],
        'reemplazar' => [
            'async function cancelar_venta(id_venta) {',
            '    // Fix B3.3c (v81k): agregar nombre_dueno para admin/soporte.',
            '    const params_info_canc = { accion: "ventas/info_cancelacion", id_venta };',
            '    const nombre_dueno_info_canc = _nombre_dueno_para_peticiones_vendidos();',
            '    if (nombre_dueno_info_canc) params_info_canc.nombre_dueno = nombre_dueno_info_canc;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params_info_canc)',
            '    });',
        ],
    ],

    // ============================================================
    // cancelar_venta: fetch 2 (cancelar) manda nombre_dueno
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: cancelar_venta cancelar manda nombre_dueno',
        'buscar' => [
            '        const resp2 = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "ventas/cancelar", id_venta, motivo })',
            '        });',
        ],
        'reemplazar' => [
            '        // Fix B3.3c (v81k): agregar nombre_dueno para admin/soporte.',
            '        const params_cancelar = { accion: "ventas/cancelar", id_venta, motivo };',
            '        const nombre_dueno_cancelar = _nombre_dueno_para_peticiones_vendidos();',
            '        if (nombre_dueno_cancelar) params_cancelar.nombre_dueno = nombre_dueno_cancelar;',
            '        const resp2 = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams(params_cancelar)',
            '        });',
        ],
    ],

    // ============================================================
    // ver_detalle_cancelacion: mandar nombre_dueno (CRÍTICO)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: ver_detalle_cancelacion manda nombre_dueno (crítico)',
        'buscar' => [
            'async function ver_detalle_cancelacion(id_cancelacion) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "cancelaciones/obtener", id_cancelacion })',
            '    });',
        ],
        'reemplazar' => [
            'async function ver_detalle_cancelacion(id_cancelacion) {',
            '    // Fix B3.3c (v81k): el backend necesita nombre_dueno para',
            '    // navegar us_<dueño> en carga parcial. Sin esto, el soporte',
            '    // (carga parcial sin `usuarios` en memoria) recibe',
            '    // "Cancelación no encontrada". Mismo patrón que el bug v81i',
            '    // de rendiciones/liquidaciones.',
            '    const params_det_canc = { accion: "cancelaciones/obtener", id_cancelacion };',
            '    const nombre_dueno_det_canc = _nombre_dueno_para_peticiones_vendidos();',
            '    if (nombre_dueno_det_canc) params_det_canc.nombre_dueno = nombre_dueno_det_canc;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params_det_canc)',
            '    });',
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

// --- Fase 1: aplicar reemplazos ---
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

// --- Fase 2: bumps automáticos ---
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

// --- Fase 3: escribir ---
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