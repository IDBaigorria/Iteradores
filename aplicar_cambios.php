<?php
/**
 * Aplicador — Impresión de ventas para terminal (v1.5piloto.83e).
 *
 * Motivo: las URLs de impresión (tipo=pasajes, tipo=cupon) llevan
 * `id_venta` y `nombre_dueno`, pero NO `nombre_terminal`. En el
 * backend, `generar_impresion` → `obtener_venta_por_id($id, null,
 * $nombre_dueno)` busca la venta en el árbol default del dueño.
 * Una venta hecha por un terminal vive en el árbol parametrizado
 * del compartido (`hmi_<term>`, etc.), no en el default del
 * dueño. Resultado: "Venta no encontrada" para terminal, funciona
 * para dueño (porque su árbol es el default).
 *
 * Fix:
 * - `Impresion.php`: `generar_impresion` acepta `?string
 *   $nombre_terminal` y lo pasa a `obtener_venta_por_id`.
 * - `index.php`: rama `imprimir=1` lee `$_GET['nombre_terminal']`
 *   y lo pasa.
 * - `ventas.js`: 3 URLs (post-venta pasajes, post-venta cupón,
 *   cuponera cupón imprimir) agregan `nombre_terminal` si el
 *   usuario es terminal.
 * - `pasajeros.js`: 2 URLs (imprimir todos los pasajes, imprimir
 *   pasaje individual) agregan `nombre_terminal` si el usuario es
 *   terminal.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83e';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. Impresion.php — generar_impresion acepta nombre_terminal
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'generar_impresion: aceptar nombre_terminal',
    'buscar' => [
        'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\', ?string $nombre_dueno = null): void {',
        '    // Fase 1c.2: pasar el dueño si viene, para buscar la venta en',
        '    // su contexto (carga parcial desde el flujo de impresión).',
        '    $venta = obtener_venta_por_id($id_venta, null, $nombre_dueno);',
    ],
    'reemplazar' => [
        'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\', ?string $nombre_dueno = null, ?string $nombre_terminal = null): void {',
        '    // Fase 1c.2: pasar el dueño si viene, para buscar la venta en',
        '    // su contexto (carga parcial desde el flujo de impresión).',
        '    // Fix v1.5piloto.83e: pasar también nombre_terminal. Sin esto,',
        '    // un terminal no puede imprimir sus propias ventas (que viven',
        '    // en el árbol del compartido, no en el default del dueño).',
        '    // Con nombre_terminal, obtener_venta_por_id navega el contexto',
        '    // del terminal vía _contexto_terminal + _nombres_arbol_para_contexto.',
        '    $venta = obtener_venta_por_id($id_venta, $nombre_terminal, $nombre_dueno);',
    ],
],

// ============================================================
// 2. index.php — rama imprimir=1 pasa nombre_terminal
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: pasar nombre_terminal a generar_impresion',
    'buscar' => [
        '    $dni_filtro = $_GET[\'dni\'] ?? \'\';',
        '    $numero_cupon = $_GET[\'numero_cupon\'] ?? \'\';',
        '    // Fase 1c.2: pasar el dueño si viene en el GET, para que',
        '    // la búsqueda de la venta use su contexto en carga parcial.',
        '    generar_impresion($tipo, $_GET[\'id_venta\'] ?? \'\', $dni_filtro, $numero_cupon, $_GET[\'dueno\'] ?? \'\');',
        '    exit;',
    ],
    'reemplazar' => [
        '    $dni_filtro = $_GET[\'dni\'] ?? \'\';',
        '    $numero_cupon = $_GET[\'numero_cupon\'] ?? \'\';',
        '    // Fase 1c.2: pasar el dueño si viene en el GET, para que',
        '    // la búsqueda de la venta use su contexto en carga parcial.',
        '    // Fix v1.5piloto.83e: pasar también nombre_terminal. Sin esto,',
        '    // el terminal no puede imprimir sus propias ventas (viven en',
        '    // el árbol del compartido, no en el default del dueño).',
        '    generar_impresion(',
        '        $tipo,',
        '        $_GET[\'id_venta\'] ?? \'\',',
        '        $dni_filtro,',
        '        $numero_cupon,',
        '        $_GET[\'dueno\'] ?? \'\',',
        '        $_GET[\'nombre_terminal\'] ?? null',
        '    );',
        '    exit;',
    ],
],

// ============================================================
// 3. ventas.js — mostrar_opciones_impresion (post-venta)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/ventas.js',
    'descripcion' => 'mostrar_opciones_impresion: nombre_terminal en URLs',
    'buscar' => [
        'function mostrar_opciones_impresion(id_venta) {',
        '    const contenedor = $("#opciones_impresion");',
        '    if (!contenedor) return;',
        '    contenedor.classList.remove("hidden");',
        '    $("#btn_imprimir_pasajes").onclick = () => {',
        '        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id_venta}`, \'_blank\');',
        '    };',
        '    // El cupón post-venta corresponde siempre al primer pago de la venta,',
        '    // que es el cupón 1.',
        '    $("#btn_imprimir_cupon").onclick = () => {',
        '        window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${id_venta}&numero_cupon=1`, \'_blank\');',
        '    };',
    ],
    'reemplazar' => [
        'function mostrar_opciones_impresion(id_venta) {',
        '    const contenedor = $("#opciones_impresion");',
        '    if (!contenedor) return;',
        '    contenedor.classList.remove("hidden");',
        '    // Fix v1.5piloto.83e: si el usuario es terminal, agregar',
        '    // nombre_terminal a las URLs de impresión. Sin esto, el',
        '    // backend busca la venta en el árbol default del dueño y',
        '    // no la encuentra (la venta del terminal vive en el árbol',
        '    // del compartido).',
        '    const terminal_qs = (usuario_actual && usuario_actual.nivel === \'terminal\')',
        '        ? `&nombre_terminal=${encodeURIComponent(usuario_actual.nombre_usuario)}`',
        '        : \'\';',
        '    $("#btn_imprimir_pasajes").onclick = () => {',
        '        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id_venta}${terminal_qs}`, \'_blank\');',
        '    };',
        '    // El cupón post-venta corresponde siempre al primer pago de la venta,',
        '    // que es el cupón 1.',
        '    $("#btn_imprimir_cupon").onclick = () => {',
        '        window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${id_venta}&numero_cupon=1${terminal_qs}`, \'_blank\');',
        '    };',
    ],
],

// ============================================================
// 4. ventas.js — _renderizar_cuponera_en_modal (botón imprimir cupón)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/ventas.js',
    'descripcion' => '_renderizar_cuponera_en_modal: nombre_terminal en URL',
    'buscar' => [
        '            if (accion === \'imprimir\') {',
        '                window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${venta.id_venta}&numero_cupon=${numero}`, \'_blank\');',
        '            } else if (accion === \'pagar\') {',
    ],
    'reemplazar' => [
        '            if (accion === \'imprimir\') {',
        '                // Fix v1.5piloto.83e: nombre_terminal si el usuario es',
        '                // terminal (la venta vive en el árbol del compartido).',
        '                const terminal_qs = (usuario_actual && usuario_actual.nivel === \'terminal\')',
        '                    ? `&nombre_terminal=${encodeURIComponent(usuario_actual.nombre_usuario)}`',
        '                    : \'\';',
        '                window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${venta.id_venta}&numero_cupon=${numero}${terminal_qs}`, \'_blank\');',
        '            } else if (accion === \'pagar\') {',
    ],
],

// ============================================================
// 5. pasajeros.js — ver_pasajes_pasajero (imprimir todos)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/pasajeros.js',
    'descripcion' => 'ver_pasajes_pasajero: nombre_terminal en urlImprimirTodos',
    'buscar' => [
        '            // Botón imprimir todos (siempre visible)',
        '            const urlImprimirTodos = v.es_comprador',
        '                ? `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}`',
        '                : `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}&dni=${p.dni}`;',
    ],
    'reemplazar' => [
        '            // Botón imprimir todos (siempre visible)',
        '            // Fix v1.5piloto.83e: nombre_terminal si el usuario es',
        '            // terminal (la venta vive en el árbol del compartido).',
        '            const terminal_qs = (usuario_actual && usuario_actual.nivel === \'terminal\')',
        '                ? `&nombre_terminal=${encodeURIComponent(usuario_actual.nombre_usuario)}`',
        '                : \'\';',
        '            const urlImprimirTodos = v.es_comprador',
        '                ? `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}${terminal_qs}`',
        '                : `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}&dni=${p.dni}${terminal_qs}`;',
    ],
],

// ============================================================
// 6. pasajeros.js — ver_detalle_pasaje_individual (imprimir individual)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/pasajeros.js',
    'descripcion' => 'ver_detalle_pasaje_individual: nombre_terminal en URL',
    'buscar' => [
        '    document.querySelector(\'.imprimir_pasaje_individual\').addEventListener(\'click\', function() {',
        '        const id = this.dataset.id;',
        '        const dni = this.dataset.dni;',
        '        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id}&dni=${dni}`, \'_blank\');',
        '    });',
    ],
    'reemplazar' => [
        '    document.querySelector(\'.imprimir_pasaje_individual\').addEventListener(\'click\', function() {',
        '        const id = this.dataset.id;',
        '        const dni = this.dataset.dni;',
        '        // Fix v1.5piloto.83e: nombre_terminal si el usuario es terminal.',
        '        const terminal_qs = (usuario_actual && usuario_actual.nivel === \'terminal\')',
        '            ? `&nombre_terminal=${encodeURIComponent(usuario_actual.nombre_usuario)}`',
        '            : \'\';',
        '        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id}&dni=${dni}${terminal_qs}`, \'_blank\');',
        '    });',
    ],
],

// ============================================================
// 7. plan_actual.md — header y Fase 3
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83e',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83d (Fase 3 del',
        'bug §4.7: fix del falso positivo en 1.5i.7u. Detectado',
        'que `confirmar_venta_actual` y `pagar_cupon_venta` no',
        'chequean `guardar_ambos`, reportando "éxito" con la DB',
        'intacta. Chequeos aplicados. Deuda: ~15 flujos más con',
        'el mismo patrón, tanda aparte).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83e (Fase 3 del',
        'bug §4.7, cierre del flujo de venta en micro de 2 pisos.',
        'Fix de impresión de ventas para terminal: las URLs no',
        'llevaban `nombre_terminal`, así que `obtener_venta_por_id`',
        'buscaba en el árbol default del dueño y no encontraba la',
        'venta del compartido. Aplicado en las 5 URLs del frontend',
        'y en `generar_impresion` / `index.php`).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: Fase 3 con bug de impresión',
    'buscar' => [
        '  `subir_declaracion_jurada_pasajero`. Todos tienen el',
        '  mismo síntoma: "éxito" sin persistir. Hacer después',
        '  de cerrar §4.7.',
    ],
    'reemplazar' => [
        '  `subir_declaracion_jurada_pasajero`. Todos tienen el',
        '  mismo síntoma: "éxito" sin persistir. Hacer después',
        '  de cerrar §4.7.',
        '- **Tercer bug detectado (1.5piloto.83e):** impresión de',
        '  ventas para terminal. Las URLs `?imprimir=1&tipo=pasajes`',
        '  y `tipo=cupon` llevaban `id_venta` y `nombre_dueno` pero',
        '  NO `nombre_terminal`. `generar_impresion` llamaba',
        '  `obtener_venta_por_id($id, null, $nombre_dueno)`, que',
        '  busca en el árbol default del dueño. La venta de un',
        '  terminal vive en el árbol parametrizado del compartido',
        '  (`hmi_<term>`), así que no se encontraba → "Venta no',
        '  encontrada". Dueño funcionaba porque su árbol es el',
        '  default. **Fix:** `generar_impresion` acepta',
        '  `?string $nombre_terminal`; `index.php` lee',
        '  `$_GET[\'nombre_terminal\']`; las 5 URLs del frontend',
        '  (`mostrar_opciones_impresion` × 2, cuponera × 1,',
        '  `ver_pasajes_pasajero` × 1,',
        '  `ver_detalle_pasaje_individual` × 1) agregan',
        '  `nombre_terminal` si el usuario es terminal.',
        '- **Estado del bug §4.7:** el ciclo completo (venta en',
        '  micro de 2 pisos + impresión de pasaje/cupón como',
        '  terminal y como dueño) funciona post-83e. El blindaje',
        '  de 1.5i.7t/7u no se dispara. La DB no se corrompe. Los',
        '  4 bugs encadenados de Fase 3 (falso positivo del',
        '  blindaje, venta sin chequear `guardar_ambos`, cupón',
        '  sin chequear, impresión de terminal sin',
        '  `nombre_terminal`) quedaron cerrados. La migración de',
        '  pasajeros **sigue en pausa** hasta que el usuario',
        '  valide un ciclo completo post-83e.',
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