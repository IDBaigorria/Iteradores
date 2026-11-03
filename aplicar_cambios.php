<?php
/**
 * Aplicador — Fase 3: sesión server-side + endurecer informe_ventas.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84e';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Enrutador.php — helper _es_accion_publica
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: helper _es_accion_publica',
        'buscar' => [
            'function responder_json(array $datos): void {',
            '    header(\'Content-Type: application/json; charset=utf-8\');',
            '    echo json_encode($datos, JSON_UNESCAPED_UNICODE);',
            '    exit;',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
        'reemplazar' => [
            'function responder_json(array $datos): void {',
            '    header(\'Content-Type: application/json; charset=utf-8\');',
            '    echo json_encode($datos, JSON_UNESCAPED_UNICODE);',
            '    exit;',
            '}',
            '',
            '/**',
            ' * Devuelve true si la acción no requiere sesión.',
            ' *',
            ' * Fase 3 (v1.5piloto.84e). Las acciones públicas son',
            ' * las que se llaman antes de tener sesión:',
            ' *   - autenticar/verificar: login.',
            ' *   - administrador/verificar: legacy, chequeo de código',
            ' *     del admin sin sesión (sigue usado por algunas',
            ' *     pantallas).',
            ' *   - sesiones/validar: rehidratación de $_SESSION al',
            ' *     recargar la página.',
            ' *   - entorno/info: información del entorno, no',
            ' *     sensible.',
            ' *',
            ' * @param string $accion Acción en formato "modulo/subaccion".',
            ' * @return bool',
            ' * @since 1.5piloto.84e',
            ' */',
            'function _es_accion_publica(string $accion): bool {',
            '    static $publicas = [',
            '        \'autenticar/verificar\',',
            '        \'administrador/verificar\',',
            '        \'sesiones/validar\',',
            '        \'entorno/info\',',
            '    ];',
            '    return in_array($accion, $publicas, true);',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
    ],

    // ============================================================
    // Enrutador.php — override de nombre_solicitante por sesión
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: override de nombre_solicitante por sesion',
        'buscar' => [
            'function enrutar_peticion_post(string $accion, array $post): void {',
            '    $partes = explode(\'/\', $accion);',
            '    $modulo = $partes[0] ?? \'\';',
            '    $subaccion = $partes[1] ?? \'\';',
            '',
            '    // Chequeo global: si viene nombre_solicitante y nombre_dueno,',
            '    // verificar que el solicitante tenga permiso sobre ese dueño.',
            '    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
        ],
        'reemplazar' => [
            'function enrutar_peticion_post(string $accion, array $post): void {',
            '    $partes = explode(\'/\', $accion);',
            '    $modulo = $partes[0] ?? \'\';',
            '    $subaccion = $partes[1] ?? \'\';',
            '',
            '    // Fase 3 (v1.5piloto.84e): sesión server-side.',
            '    // Para acciones no públicas, el nombre_solicitante sale',
            '    // de $_SESSION, no del POST. El POST puede seguir',
            '    // mandándolo (el interceptor del frontend lo hace),',
            '    // pero el backend lo ignora.',
            '    //',
            '    // index.php ya validó que la sesión existe antes de',
            '    // llegar acá. Este chequeo es defensa en profundidad',
            '    // (por si alguien llama a enrutar_peticion_post desde',
            '    // otro lugar en el futuro).',
            '    if (!_es_accion_publica($accion)) {',
            '        $sesion_f3 = usuario_sesion_actual();',
            '        if ($sesion_f3 === null) {',
            '            responder_json([\'exito\' => false, \'error\' => \'Sesión expirada o no iniciada. Volvé a iniciar sesión.\']);',
            '        }',
            '        $post[\'nombre_solicitante\'] = (string)($sesion_f3[\'nombre_usuario\'] ?? \'\');',
            '    }',
            '',
            '    // Chequeo global: si viene nombre_solicitante y nombre_dueno,',
            '    // verificar que el solicitante tenga permiso sobre ese dueño.',
            '    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
        ],
    ],

    // ============================================================
    // Enrutador.php — endurecer informe_ventas para terminal
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: terminal solo ve sus propias ventas en informe_ventas',
        'buscar' => [
            '        if ($tipo === \'informe_ventas\') {',
            '            $tipo_ventas = (string)($get[\'tipo_ventas\'] ?? \'dueno\');',
            '            $nombre_v = (string)($get[\'nombre\'] ?? \'\');',
            '            if ($tipo_ventas === \'terminal\') return $nombre_v === $nombre_sesion;',
            '            if ($tipo_ventas === \'dueno\') return $nombre_v === $dueno_sesion;',
            '            return false;',
            '        }',
            '        return true;',
            '    }',
            '',
            '    return false;',
            '}',
        ],
        'reemplazar' => [
            '        if ($tipo === \'informe_ventas\') {',
            '            $tipo_ventas = (string)($get[\'tipo_ventas\'] ?? \'dueno\');',
            '            $nombre_v = (string)($get[\'nombre\'] ?? \'\');',
            '            // Fase 3 (v1.5piloto.84e): el terminal solo puede',
            '            // ver SUS propias ventas. Se exige',
            '            // tipo_ventas=terminal y nombre=terminal de sesión.',
            '            // Cierra el leak del camino antiguo (tipo_ventas=dueno',
            '            // + nombre del dueño → veía las ventas de otros',
            '            // terminales del mismo dueño).',
            '            return $tipo_ventas === \'terminal\' && $nombre_v === $nombre_sesion;',
            '        }',
            '        return true;',
            '    }',
            '',
            '    return false;',
            '}',
        ],
    ],

    // ============================================================
    // index.php — pre-check 401 + sesión para contextos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index: pre-check 401 y contextos por sesion',
        'buscar' => [
            'Controlador::establecer_metodo(\'SQL64\');',
            '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
            '$contextos_carga = _contextos_para_operacion(',
            '    $_POST[\'accion\'] ?? \'\',',
            '    $_POST[\'nombre_solicitante\'] ?? \'\',',
            '    $_GET',
            ');',
            '$modo_parcial = ($contextos_carga !== null);',
        ],
        'reemplazar' => [
            'Controlador::establecer_metodo(\'SQL64\');',
            '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
            '',
            '// Fase 3 (v1.5piloto.84e): sesión server-side.',
            '// Pre-check: si la acción no es pública y no hay sesión,',
            '// 401 antes de cargar el grafo (ahorra la carga).',
            '// Para acciones públicas (login, validar), sigue el',
            '// comportamiento anterior: carga total (necesaria para',
            '// el bootstrap del admin y para verificar credenciales).',
            '$accion_pre_ctx = $_POST[\'accion\'] ?? \'\';',
            '$es_publica_ctx = _es_accion_publica($accion_pre_ctx);',
            '$sesion_pre_ctx = usuario_sesion_actual();',
            'if ($accion_pre_ctx !== \'\' && !$es_publica_ctx && $sesion_pre_ctx === null) {',
            '    http_response_code(401);',
            '    header(\'Content-Type: application/json; charset=utf-8\');',
            '    echo json_encode([\'exito\' => false, \'error\' => \'Sesión expirada o no iniciada. Volvé a iniciar sesión.\'], JSON_UNESCAPED_UNICODE);',
            '    exit;',
            '}',
            '// Nombre del solicitante para el contexto:',
            '//   - acciones no públicas → el de la sesión.',
            '//   - acciones públicas → el del POST (compat) o vacío.',
            'if ($accion_pre_ctx !== \'\' && !$es_publica_ctx && $sesion_pre_ctx !== null) {',
            '    $nombre_sol_ctx = (string)($sesion_pre_ctx[\'nombre_usuario\'] ?? \'\');',
            '} else {',
            '    $nombre_sol_ctx = $_POST[\'nombre_solicitante\'] ?? \'\';',
            '}',
            '$contextos_carga = _contextos_para_operacion(',
            '    $accion_pre_ctx,',
            '    $nombre_sol_ctx,',
            '    $_GET',
            ');',
            '$modo_parcial = ($contextos_carga !== null);',
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