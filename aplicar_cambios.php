<?php
/**
 * Aplicador — Eliminar botones de limpieza obsoletos del piloto.
 *
 * Los botones "Limpiar viajes de prueba" y "Limpiar pasajeros de
 * prueba" del piloto se reemplazan por el boton del plugin
 * (v1.5plugin.7w en adelante). Se eliminan:
 *   - Los 2 botones del HTML.
 *   - El link y el archivo estilos-botones-admin.css.
 *   - Los listeners y helpers JS en viajes-nucleo.js y
 *     pasajeros.js.
 *   - _aplicar_clases_de_rol en aplicacion.js (solo lo consumian
 *     los botones y el CSS eliminados).
 *   - Las llamadas a _actualizar_visibilidad_boton_limpiar_*.
 *
 * Los endpoints backend (viajes/limpiar_prueba y
 * pasajeros/limpiar_prueba) se conservan: los usa el plugin.
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80k';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // 1. HTML: eliminar el link al CSS
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: sacar link a estilos-botones-admin.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-rendiciones.css?v=1.5piloto.65">',
            '<link rel="stylesheet" href="estilos-botones-admin.css?v=1.5piloto.79j">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-rendiciones.css?v=1.5piloto.65">',
        ],
    ],

    // ============================================================
    // 2. HTML: eliminar boton de viajes
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: sacar boton limpiar viajes',
        'buscar' => [
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_viaje" style="display:none;">Agregar viaje</button>',
            '            <button class="btn danger" id="boton_limpiar_viajes_prueba" style="display:none;">Limpiar viajes de prueba</button>',
            '          </div>',
        ],
        'reemplazar' => [
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_viaje" style="display:none;">Agregar viaje</button>',
            '          </div>',
        ],
    ],

    // ============================================================
    // 3. HTML: eliminar boton de pasajeros
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: sacar boton limpiar pasajeros',
        'buscar' => [
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_pasajero">Agregar pasajero/cliente</button>',
            '            <button class="btn danger" id="boton_limpiar_pasajeros_prueba" style="display:none;">Limpiar pasajeros de prueba</button>',
            '          </div>',
        ],
        'reemplazar' => [
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_pasajero">Agregar pasajero/cliente</button>',
            '          </div>',
        ],
    ],

    // ============================================================
    // 4. aplicacion.js: eliminar la funcion _aplicar_clases_de_rol
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: sacar _aplicar_clases_de_rol',
        'buscar' => [
            '/**',
            ' * Aplica las clases de rol y modo al <body>. Se usa para que',
            ' * el CSS condicione la visibilidad de botones solo-admin',
            ' * (por ejemplo, "Limpiar viajes de prueba"). Las clases se',
            ' * limpian en _limpiar_contenido_dinamico, así que un cambio',
            ' * de sesión sin recargar la página las quita antes de',
            ' * configurar la nueva UI.',
            ' *',
            ' * Pasar usuario = null quita las dos clases.',
            ' *',
            ' * @param {object|null} usuario',
            ' */',
            'function _aplicar_clases_de_rol(usuario) {',
            '    const body = document.body;',
            '    if (!body) return;',
            '    body.classList.toggle(\'rol-admin\', !!(usuario && usuario.nivel === \'admin\'));',
            '    body.classList.toggle(\'modo-pruebas\', window.entorno_es_pruebas === true);',
            '}',
            '',
            '/**',
            ' * Aplica un login exitoso: guarda estado, muestra la app y configura pestañas.',
        ],
        'reemplazar' => [
            '/**',
            ' * Aplica un login exitoso: guarda estado, muestra la app y configura pestañas.',
        ],
    ],

    // ============================================================
    // 5. aplicacion.js: sacar la llamada en _aplicar_login_exitoso
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: sacar _aplicar_clases_de_rol (login)',
        'buscar' => [
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    _aplicar_clases_de_rol(usuario);',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
        ],
        'reemplazar' => [
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
        ],
    ],

    // ============================================================
    // 6. aplicacion.js: sacar la llamada en DOMContentLoaded
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: sacar _aplicar_clases_de_rol (token)',
        'buscar' => [
            '                $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;',
            '                $("#nivel_usuario_actual").textContent = usuario_actual.nivel;',
            '                _aplicar_clases_de_rol(usuario_actual);',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
        ],
        'reemplazar' => [
            '                $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;',
            '                $("#nivel_usuario_actual").textContent = usuario_actual.nivel;',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
        ],
    ],

    // ============================================================
    // 7. aplicacion.js: sacar la llamada en _limpiar_contenido_dinamico
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: sacar _aplicar_clases_de_rol (limpieza)',
        'buscar' => [
            '    // v79j: quitar las clases de rol del body, así el CSS',
            '    // no muestra botones de admin en la próxima sesión.',
            '    _aplicar_clases_de_rol(null);',
            '',
            '    // 1. Modal apilado (está encima).',
        ],
        'reemplazar' => [
            '    // 1. Modal apilado (está encima).',
        ],
    ],

    // ============================================================
    // 8. viajes-nucleo.js: eliminar las funciones de limpieza y el listener
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo.js: sacar funciones de limpieza',
        'buscar' => [
            '/**',
            ' * Muestra u oculta el botón "Limpiar viajes de prueba".',
            ' * Solo visible para admin, y solo cuando hay un dueño',
            ' * seleccionado en el selector.',
            ' */',
            'function _actualizar_visibilidad_boton_limpiar_viajes(visible) {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    // Solo en modo pruebas (bandera inyectada por index.php).',
            '    if (window.entorno_es_pruebas !== true) return;',
            '    const btn = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (!btn) return;',
            '    btn.style.display = visible ? \'inline-block\' : \'none\';',
            '}',
            '',
            '/**',
            ' * Ejecuta la limpieza de viajes de prueba del dueño',
            ' * seleccionado. El backend se encarga de preservar el viaje',
            ' * principal y los que no tengan prefijo de prueba.',
            ' */',
            'async function limpiar_viajes_de_prueba_ui() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') {',
            '        mostrar_aviso("Solo el admin puede ejecutar esta acción", \'error\');',
            '        return;',
            '    }',
            '    const select = document.getElementById("selector_dueno_viajes");',
            '    const nombre_dueno = select ? select.value : \'\';',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso("Seleccione un dueño primero", \'error\');',
            '        return;',
            '    }',
            '    const ok = confirm(',
            '        "¿Eliminar todos los viajes de prueba del dueño \\"" + nombre_dueno + "\\"?\\n\\n"',
            '        + "Se conserva el viaje principal y cualquier viaje que no tenga prefijo de prueba. Los viajes con ventas no se eliminan.\\n\\n"',
            '        + "Esta acción no se puede deshacer."',
            '    );',
            '    if (!ok) return;',
            '',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "viajes/limpiar_prueba",',
            '            nombre_dueno,',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al limpiar viajes", \'error\');',
            '        return;',
            '    }',
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " viaje(s) de prueba.";',
            '    if (datos.con_ventas && datos.con_ventas.length > 0) {',
            '        msg += " " + datos.con_ventas.length + " no se pudieron eliminar (tienen ventas).";',
            '    }',
            '    // v79k: reportar también los conservados por no tener',
            '    // prefijo de prueba ni nombre principal. Antes quedaban',
            '    // silenciosos y el admin no entendía por qué el conteo',
            '    // total no bajaba.',
            '    const conservados_sin_prefijo = (datos.conservados || []).filter(',
            '        function (n) { return n !== null; }',
            '    );',
            '    if (conservados_sin_prefijo.length > 0) {',
            '        msg += " " + conservados_sin_prefijo.length + " conservado(s) sin prefijo de prueba.";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
            '    await listar_viajes(nombre_dueno, \'dueno\');',
            '}',
            '',
            'document.getElementById(\'boton_limpiar_viajes_prueba\')?.addEventListener(\'click\', limpiar_viajes_de_prueba_ui);',
            '',
            'async function cargar_viajes() {',
        ],
        'reemplazar' => [
            'async function cargar_viajes() {',
        ],
    ],

    // ============================================================
    // 9. viajes-nucleo.js: sacar bloque defensivo en cargar_viajes
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo.js: sacar bloque defensivo',
        'buscar' => [
            '    // v79j: defensa del botón "Limpiar viajes de prueba".',
            '    // Aunque el HTML lo muestre, forzamos el chequeo por',
            '    // nivel y modo pruebas. Cubre el caso de un cambio de',
            '    // sesión sin recargar la página (bug de _limpiar_',
            '    // contenido_dinamico).',
            '    const btn_limpiar_viajes = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (btn_limpiar_viajes) {',
            '        const puede_limpiar = usuario_actual',
            '            && usuario_actual.nivel === \'admin\'',
            '            && window.entorno_es_pruebas === true;',
            '        btn_limpiar_viajes.style.display = puede_limpiar ? \'inline-block\' : \'none\';',
            '    }',
            '',
            '    if (es_admin_o_soporte()) {',
        ],
        'reemplazar' => [
            '    if (es_admin_o_soporte()) {',
        ],
    ],

    // ============================================================
    // 10. viajes-nucleo.js: sacar llamadas en select.onchange
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo.js: sacar llamadas en select.onchange',
        'buscar' => [
            '        select.onchange = async () => {',
            '            ocultar_detalle_viaje();',
            '            if (select.value) {',
            '                await listar_viajes(select.value, \'dueno\');',
            '                $("#boton_agregar_viaje").style.display = \'inline-block\';',
            '                _actualizar_visibilidad_boton_limpiar_viajes(true);',
            '            } else {',
            '                $("#lista_viajes").innerHTML = \'\';',
            '                $("#boton_agregar_viaje").style.display = \'none\';',
            '                _actualizar_visibilidad_boton_limpiar_viajes(false);',
            '            }',
            '        };',
        ],
        'reemplazar' => [
            '        select.onchange = async () => {',
            '            ocultar_detalle_viaje();',
            '            if (select.value) {',
            '                await listar_viajes(select.value, \'dueno\');',
            '                $("#boton_agregar_viaje").style.display = \'inline-block\';',
            '            } else {',
            '                $("#lista_viajes").innerHTML = \'\';',
            '                $("#boton_agregar_viaje").style.display = \'none\';',
            '            }',
            '        };',
        ],
    ],

    // ============================================================
    // 11. pasajeros.js: eliminar las funciones de limpieza y el listener
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: sacar funciones de limpieza',
        'buscar' => [
            '// ============================================================',
            '// Limpieza de pasajeros de prueba (solo admin, modo pruebas).',
            '// ============================================================',
            '',
            'function _actualizar_visibilidad_boton_limpiar_pasajeros() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    // Solo en modo pruebas (bandera inyectada por index.php).',
            '    if (window.entorno_es_pruebas !== true) return;',
            '    const btn = document.getElementById(\'boton_limpiar_pasajeros_prueba\');',
            '    if (!btn) return;',
            '    // Solo cuando hay un dueño seleccionado.',
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    btn.style.display = nombre_dueno ? \'inline-block\' : \'none\';',
            '}',
            '',
            'async function limpiar_pasajeros_de_prueba_ui() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') {',
            '        mostrar_aviso("Solo el admin puede ejecutar esta acción", \'error\');',
            '        return;',
            '    }',
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso("Seleccione un dueño primero", \'error\');',
            '        return;',
            '    }',
            '    const ok = confirm(',
            '        "¿Eliminar todos los pasajeros de prueba del dueño \\"" + nombre_dueno + "\\"?\\n\\n"',
            '        + "Se consideran de prueba los que tienen email @test.local.\\n"',
            '        + "Se conservan los que tengan ventas o reservas asociadas.\\n\\n"',
            '        + "Esta acción no se puede deshacer."',
            '    );',
            '    if (!ok) return;',
            '',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "pasajeros/limpiar_prueba",',
            '            nombre_dueno,',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al limpiar pasajeros", \'error\');',
            '        return;',
            '    }',
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " pasajero(s) de prueba.";',
            '    const cons = (datos.conservados_con_referencias || []).length;',
            '    if (cons > 0) {',
            '        msg += " " + cons + " se conservaron por tener ventas o reservas.";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
            '    await cargar_pasajeros();',
            '}',
            '',
            '// ===== Inicializacion de listeners del panel Pasajeros =====',
            '(function() {',
            '    const btn = document.getElementById(\'boton_agregar_pasajero\');',
            '    if (btn) btn.addEventListener(\'click\', abrir_modal_agregar_pasajero);',
            '',
            '    const btn_limpiar = document.getElementById(\'boton_limpiar_pasajeros_prueba\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', limpiar_pasajeros_de_prueba_ui);',
            '})();',
        ],
        'reemplazar' => [
            '// ===== Inicializacion de listeners del panel Pasajeros =====',
            '(function() {',
            '    const btn = document.getElementById(\'boton_agregar_pasajero\');',
            '    if (btn) btn.addEventListener(\'click\', abrir_modal_agregar_pasajero);',
            '})();',
        ],
    ],

    // ============================================================
    // 12. pasajeros.js: sacar llamadas en cargar_pasajeros
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: sacar llamadas a visibilidad',
        'buscar' => [
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        _actualizar_visibilidad_boton_limpiar_pasajeros();',
            '        mostrar_aviso(\'Seleccione un dueño para ver pasajeros\', \'info\');',
            '        return;',
            '    }',
            '',
            '    // Actualizar visibilidad del botón de limpieza (solo admin + modo pruebas).',
            '    _actualizar_visibilidad_boton_limpiar_pasajeros();',
            '',
            '    const respuesta = await fetch("index.php", {',
        ],
        'reemplazar' => [
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso(\'Seleccione un dueño para ver pasajeros\', \'info\');',
            '        return;',
            '    }',
            '',
            '    const respuesta = await fetch("index.php", {',
        ],
    ],

    // ============================================================
    // 13. Eliminar el archivo estilos-botones-admin.css
    // ============================================================
    [
        'tipo' => 'eliminar',
        'archivo' => 'estilos-botones-admin.css',
        'descripcion' => 'CSS de botones admin (ya sin consumidores)',
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
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
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
        foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
            $base = basename($archivo_rel);
            if ($base === 'estilos-botones-admin.css') continue;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existia.\n"; continue; }
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