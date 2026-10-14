<?php
/**
 * Aplicador — aplicacion.js: clases de rol en el body.
 *
 * Agrega las clases `rol-admin` y `modo-pruebas` al <body>
 * según el usuario logueado y la bandera window.entorno_es_pruebas.
 * El CSS (estilos-botones-admin.css) usa esas clases para mostrar
 * el botón "Limpiar viajes de prueba" solo al admin en modo pruebas.
 *
 * Sin esto, el botón quedaba visible al cambiar de sesión sin
 * recargar la página (bug reportado: aparece para terminal y dueño).
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$helper = [
    '',
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
];

$cambios = [
    // ============================================================
    // 1. Nuevo helper _aplicar_clases_de_rol, justo antes de
    //    _aplicar_login_exitoso.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Nuevo helper _aplicar_clases_de_rol',
        'buscar' => [
            '/**',
            ' * Aplica un login exitoso: guarda estado, muestra la app y configura pestañas.',
            ' * Se usa tanto en login por código como por usuario.',
            ' */',
            'function _aplicar_login_exitoso(usuario) {',
        ],
        'reemplazar' => array_merge(
            $helper,
            [
                '',
                '/**',
                ' * Aplica un login exitoso: guarda estado, muestra la app y configura pestañas.',
                ' * Se usa tanto en login por código como por usuario.',
                ' */',
                'function _aplicar_login_exitoso(usuario) {',
            ]
        ),
    ],

    // ============================================================
    // 2. Llamar al helper dentro de _aplicar_login_exitoso.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Aplicar clases de rol en login exitoso',
        'buscar' => [
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '}',
            '',
            '/**',
            ' * Muestra el bloque de login por usuario y oculta el de código.',
            ' */',
        ],
        'reemplazar' => [
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    _aplicar_clases_de_rol(usuario);',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '}',
            '',
            '/**',
            ' * Muestra el bloque de login por usuario y oculta el de código.',
            ' */',
        ],
    ],

    // ============================================================
    // 3. Aplicar clases en la restauración de sesión (DOMContentLoaded).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Aplicar clases de rol al restaurar sesión',
        'buscar' => [
            '                $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;',
            '                $("#nivel_usuario_actual").textContent = usuario_actual.nivel;',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
            '                ocultar_detalle_viaje();',
        ],
        'reemplazar' => [
            '                $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;',
            '                $("#nivel_usuario_actual").textContent = usuario_actual.nivel;',
            '                _aplicar_clases_de_rol(usuario_actual);',
            '                configurar_pestanas_segun_nivel(usuario_actual.nivel);',
            '                ocultar_detalle_viaje();',
        ],
    ],

    // ============================================================
    // 4. Quitar clases en _limpiar_contenido_dinamico.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Quitar clases de rol al limpiar',
        'buscar' => [
            'function _limpiar_contenido_dinamico() {',
            '    // Fase 8.5, v76b: cerrar todos los modales antes de limpiar',
            '    // el contenido. Antes solo se limpiaba el contenido interno',
            '    // (tablas, listas, selectores), pero los overlays de modal',
            '    // quedaban visibles al salir. Esto tapaba la pantalla de',
            '    // login con el modal abierto hasta el próximo clic.',
            '    //',
            '    // Se llama desde salir() y desde _aplicar_login_exitoso(),',
            '    // así que cubre tanto el logout manual como el cambio de',
            '    // sesión sin recargar la página.',
        ],
        'reemplazar' => [
            'function _limpiar_contenido_dinamico() {',
            '    // Fase 8.5, v76b: cerrar todos los modales antes de limpiar',
            '    // el contenido. Antes solo se limpiaba el contenido interno',
            '    // (tablas, listas, selectores), pero los overlays de modal',
            '    // quedaban visibles al salir. Esto tapaba la pantalla de',
            '    // login con el modal abierto hasta el próximo clic.',
            '    //',
            '    // Se llama desde salir() y desde _aplicar_login_exitoso(),',
            '    // así que cubre tanto el logout manual como el cambio de',
            '    // sesión sin recargar la página.',
            '',
            '    // v79j: quitar las clases de rol del body, así el CSS',
            '    // no muestra botones de admin en la próxima sesión.',
            '    _aplicar_clases_de_rol(null);',
        ],
    ],

    // ============================================================
    // 5. Bump aplicacion.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Bump aplicacion.js',
        'buscar' => [' * @version 1.5piloto.76b'],
        'reemplazar' => [' * @version 1.5piloto.79j'],
    ],

    // ============================================================
    // 6. Bump del ?v= en el HTML
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de aplicacion.js',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.76b"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.79j"></script>',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — aplicacion.js: clases de rol ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
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
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";