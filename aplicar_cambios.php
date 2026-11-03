<?php
/**
 * Aplicador — Fase 3b: chequeo de token con TTL + limpieza de endpoints.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84f';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Enrutador.php — use Entorno (fix: buscar solo lo que ya está)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar use Entorno',
        'buscar' => [
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Configuracion\\Conf;',
        ],
        'reemplazar' => [
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Configuracion\\Entorno;',
        ],
    ],

    // ============================================================
    // Enrutador.php — helper _es_accion_polling
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: helper _es_accion_polling',
        'buscar' => [
            '    return in_array($accion, $publicas, true);',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
        'reemplazar' => [
            '    return in_array($accion, $publicas, true);',
            '}',
            '',
            '/**',
            ' * Devuelve true si la acción es un polling del sistema.',
            ' *',
            ' * Fase 3b (v1.5piloto.84f). El polling del sistema',
            ' * (estado de asientos cada 15s) no cuenta como',
            ' * "actividad del usuario" y no dispara el chequeo',
            ' * periódico del token. Eso evita hits innecesarios',
            ' * al hosting cuando el usuario deja la app abierta',
            ' * sin hacer nada.',
            ' *',
            ' * @param string $accion Acción en formato "modulo/subaccion".',
            ' * @return bool',
            ' * @since 1.5piloto.84f',
            ' */',
            'function _es_accion_polling(string $accion): bool {',
            '    static $pollings = [',
            '        \'viajes/estado_asientos\',',
            '    ];',
            '    return in_array($accion, $pollings, true);',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
    ],

    // ============================================================
    // Enrutador.php — chequeo del token al inicio de enrutar_peticion_post
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: chequeo de token con TTL',
        'buscar' => [
            '        $post[\'nombre_solicitante\'] = (string)($sesion_f3[\'nombre_usuario\'] ?? \'\');',
            '    }',
            '',
            '    // Chequeo global: si viene nombre_solicitante y nombre_dueno,',
            '    // verificar que el solicitante tenga permiso sobre ese dueño.',
            '    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
        ],
        'reemplazar' => [
            '        $post[\'nombre_solicitante\'] = (string)($sesion_f3[\'nombre_usuario\'] ?? \'\');',
            '    }',
            '',
            '    // Fase 3b (v1.5piloto.84f): chequeo periódico del token.',
            '    // Endurece el cierre remoto (dueño cierra la sesión de un',
            '    // terminal). TTL de 30s: como máximo 1 chequeo cada 30s',
            '    // por usuario activo. El polling del sistema',
            '    // (viajes/estado_asientos) no dispara chequeo, para no',
            '    // sumar hits al hosting cuando el usuario está inactivo',
            '    // con la app abierta. La sesión NO se cierra por',
            '    // inactividad: solo por logout, cierre remoto, o si el',
            '    // token dejó de estar en el grafo.',
            '    if (!_es_accion_publica($accion) && !_es_accion_polling($accion)) {',
            '        $prefijo_tok = Entorno::prefijo_sesion();',
            '        $ultima_val = (int)($_SESSION[$prefijo_tok . \'ultima_validacion_token\'] ?? 0);',
            '        if (time() - $ultima_val > 30) {',
            '            $token_actual = token_sesion_actual();',
            '            if ($token_actual !== null) {',
            '                $res_tok = validar_token_sesion($token_actual);',
            '                if ($res_tok === null) {',
            '                    limpiar_sesion_php();',
            '                    responder_json([\'exito\' => false, \'error\' => \'Sesión cerrada o expirada. Volvé a iniciar sesión.\']);',
            '                }',
            '                $_SESSION[$prefijo_tok . \'ultima_validacion_token\'] = time();',
            '            }',
            '        }',
            '    }',
            '',
            '    // Chequeo global: si viene nombre_solicitante y nombre_dueno,',
            '    // verificar que el solicitante tenga permiso sobre ese dueño.',
            '    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
        ],
    ],

    // ============================================================
    // Sesion.php — guardar_sesion_php setea ultima_validacion_token
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Sesion: guardar_sesion_php setea ultima_validacion_token',
        'buscar' => [
            '    $_SESSION[$prefijo . \'ultima_actividad\'] = time();',
            '}',
            '',
            '/**',
            ' * Devuelve el usuario guardado en $_SESSION, o null si no hay.',
        ],
        'reemplazar' => [
            '    $_SESSION[$prefijo . \'ultima_actividad\'] = time();',
            '    // Fase 3b (v1.5piloto.84f): inicializar la marca de',
            '    // validación del token al loguear, para no chequear en',
            '    // el primer request post-login.',
            '    $_SESSION[$prefijo . \'ultima_validacion_token\'] = time();',
            '}',
            '',
            '/**',
            ' * Devuelve el usuario guardado en $_SESSION, o null si no hay.',
        ],
    ],

    // ============================================================
    // Sesion.php — limpiar_sesion_php limpia ultima_validacion_token
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Sesion: limpiar_sesion_php limpia ultima_validacion_token',
        'buscar' => [
            '    unset($_SESSION[$prefijo . \'usuario\']);',
            '    unset($_SESSION[$prefijo . \'ultima_actividad\']);',
            '}',
        ],
        'reemplazar' => [
            '    unset($_SESSION[$prefijo . \'usuario\']);',
            '    unset($_SESSION[$prefijo . \'ultima_actividad\']);',
            '    unset($_SESSION[$prefijo . \'ultima_validacion_token\']);',
            '}',
        ],
    ],

    // ============================================================
    // index.php — eliminar ?diag_persistencia=1 y _diag_wait_timeout
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index: eliminar diag_persistencia y _diag_wait_timeout',
        'buscar' => [
            '// ==== Diagnóstico de persistencia (v1.5piloto.81x, temporal) ====',
            '// Mide wait_timeout, prueba guardar (total y parcial), y',
            '// devuelve el error completo si falla. Solo admin/soporte.',
            '// Borrar después de diagnosticar.',
            'if (isset($_GET[\'diag_persistencia\'])) {',
            '    header(\'Content-Type: application/json; charset=utf-8\');',
            '',
            '    $nombre_sol_d = trim((string)($_GET[\'nombre_solicitante\'] ?? \'\'));',
            '    if ($nombre_sol_d !== \'\' && !Conf::LOCAL) {',
            '        $nodo_sol_d = Nodo::nodo_por_id(\'us_\' . $nombre_sol_d);',
            '        $nivel_d = $nodo_sol_d && $nodo_sol_d->adyacente(\'nivel\') ? (string)$nodo_sol_d->adyacente(\'nivel\')->dato() : \'\';',
            '        if ($nivel_d !== \'admin\' && $nivel_d !== \'soporte\') {',
            '            http_response_code(403);',
            '            echo json_encode([\'exito\' => false, \'error\' => "Nivel \'$nivel_d\' sin permiso."], JSON_UNESCAPED_UNICODE);',
            '            exit;',
            '        }',
            '    }',
            '',
            '    // Conexión de solo lectura para ver el wait_timeout global.',
            '    $diag = [];',
            '    try {',
            '        $sql_diag = new \\mysqli(Conf::SUPERESTRUCTURA_HOST_SQL, Conf::SUPERESTRUCTURA_USUARIO_SQL, Conf::SUPERESTRUCTURA_CONTRASENA_SQL, Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL);',
            '        if ($sql_diag->connect_errno) {',
            '            $diag[\'conexion_directa\'] = [\'ok\' => false, \'error\' => $sql_diag->connect_error];',
            '        } else {',
            '            $diag[\'conexion_directa\'] = [\'ok\' => true];',
            '            $sql_diag->set_charset(\'utf8mb4\');',
            '',
            '            $diag[\'wait_timeout_antes\'] = _diag_wait_timeout($sql_diag);',
            '            $ok_set = @$sql_diag->query("SET SESSION wait_timeout = 300");',
            '            $diag[\'set_wait_timeout_ok\'] = (bool)$ok_set;',
            '            $diag[\'set_wait_timeout_error\'] = $ok_set ? null : $sql_diag->error;',
            '            $diag[\'wait_timeout_despues\'] = _diag_wait_timeout($sql_diag);',
            '',
            '            // Info general de la sesión.',
            '            $r = $sql_diag->query("SELECT @@max_allowed_packet AS mp, @@version AS v, @@sql_mode AS sm");',
            '            if ($r) {',
            '                $f = $r->fetch_assoc();',
            '                $diag[\'max_allowed_packet\'] = $f[\'mp\'];',
            '                $diag[\'mysql_version\'] = $f[\'v\'];',
            '                $diag[\'sql_mode\'] = $f[\'sm\'];',
            '            }',
            '',
            '            $sql_diag->close();',
            '        }',
            '    } catch (Throwable $e) {',
            '        $diag[\'conexion_directa\'] = [\'ok\' => false, \'error\' => $e->getMessage()];',
            '    }',
            '',
            '    // Forzar carga total para probar el guardado.',
            '    if (Controlador::es_grafo_parcial()) {',
            '        Controlador::cargar($nombre_app);',
            '    }',
            '',
            '    // Snapshot de nodos en memoria.',
            '    // Fix 1.5piloto.81y: cantidad_de_nodos() es el único',
            '    // método público que no requiere token. por_cada_nodo_ejecutar',
            '    // necesita el token, que es protected en Controlador.',
            '    $nodos_total = Nodo::cantidad_de_nodos();',
            '    $diag[\'nodos_en_memoria\'] = $nodos_total;',
            '    $diag[\'aristas_en_memoria\'] = null; // no medido',
            '',
            '    // Probar guardar total (midiendo).',
            '    $inicio = microtime(true);',
            '    $ok_total = false;',
            '    $err_total = null;',
            '    try {',
            '        $ok_total = Controlador::guardar($nombre_app);',
            '        if (!$ok_total) {',
            '            // Fix 1.5piloto.81y: no existe Controlador::errores().',
            '            // Capturar el output de imprimir_errores() con ob_start.',
            '            ob_start();',
            '            Controlador::imprimir_errores();',
            '            $err_total = trim(ob_get_clean());',
            '            if ($err_total === \'\') $err_total = \'guardar() devolvió false (sin detalle)\';',
            '        }',
            '    } catch (Throwable $e) {',
            '        $err_total = $e->getMessage();',
            '    }',
            '    $diag[\'guardar_total\'] = [',
            '        \'ok\' => $ok_total,',
            '        \'duracion_ms\' => (int)((microtime(true) - $inicio) * 1000),',
            '        \'error\' => $err_total,',
            '    ];',
            '',
            '    // Probar guardar_parcial con [us_<admin>] (midiendo).',
            '    $admin = \\ConfiguracionApli::NOMBRE_ADMIN;',
            '    $ok_parcial = false;',
            '    $err_parcial = null;',
            '    if ($nodos_total > 0) {',
            '        try {',
            '            Controlador::cargar($nombre_app);',
            '            Controlador::cargar_parcial($nombre_app, [\'us_\' . $admin]);',
            '            $inicio = microtime(true);',
            '            $ok_parcial = Controlador::guardar_parcial($nombre_app, [\'us_\' . $admin]);',
            '            if (!$ok_parcial) {',
            '                ob_start();',
            '                Controlador::imprimir_errores();',
            '                $err_parcial = trim(ob_get_clean());',
            '                if ($err_parcial === \'\') $err_parcial = \'guardar_parcial() devolvió false (sin detalle)\';',
            '            }',
            '            $diag[\'guardar_parcial\'] = [',
            '                \'ok\' => $ok_parcial,',
            '                \'contextos\' => [\'us_\' . $admin],',
            '                \'duracion_ms\' => (int)((microtime(true) - $inicio) * 1000),',
            '                \'error\' => $err_parcial,',
            '            ];',
            '        } catch (Throwable $e) {',
            '            $diag[\'guardar_parcial\'] = [\'ok\' => false, \'error\' => $e->getMessage()];',
            '        }',
            '    }',
            '',
            '    // Recargar total para dejar el grafo en estado consistente.',
            '    Controlador::cargar($nombre_app);',
            '',
            '    $diag[\'version_app\'] = \\ConfiguracionApli::VERSION_APP;',
            '    $diag[\'es_local\'] = Conf::LOCAL;',
            '    echo json_encode([\'exito\' => true, \'diag\' => $diag], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);',
            '    exit;',
            '}',
            '',
            '/**',
            ' * Lee wait_timeout de la sesión actual.',
            ' */',
            'function _diag_wait_timeout(\\mysqli $sql): ?int {',
            '    $r = $sql->query("SHOW SESSION VARIABLES LIKE \'wait_timeout\'");',
            '    if (!$r) return null;',
            '    $f = $r->fetch_assoc();',
            '    return $f ? (int)$f[\'Value\'] : null;',
            '}',
            '',
            '// ==== Auditoría topológica (v1.5piloto.81p) ====',
        ],
        'reemplazar' => [
            '// (bloque ?diag_persistencia eliminado en v1.5piloto.84f)',
            '// Era diagnóstico temporal del bug §4.6/§4.7 (ya cerrado).',
            '',
            '// ==== Auditoría topológica (v1.5piloto.81p) ====',
        ],
    ],

    // ============================================================
    // index.php — eliminar ?diagnostico_cancelaciones=1
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index: eliminar diagnostico_cancelaciones',
        'buscar' => [
            '// ==== Diagnóstico de cancelaciones (temporal, v1.5piloto.81u) ====',
            'if (isset($_GET[\'diagnostico_cancelaciones\'])) {',
            '    header(\'Content-Type: application/json; charset=utf-8\');',
            '',
            '    if (Controlador::es_grafo_parcial()) {',
            '        Controlador::cargar($nombre_app);',
            '    }',
            '',
            '    $out = [];',
            '',
            '    // Dueño',
            '    $nombre_dueno = \'Parroquia_del_Carmen\';',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if ($nodo_dueno) {',
            '        $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
            '        if ($cont) {',
            '            $lista = [];',
            '            $actual = hmi($cont);',
            '            $seg = 0;',
            '            $visitados = [];',
            '            while ($actual && $seg < 50) {',
            '                $id = (string)$actual->id();',
            '                $lista[] = [',
            '                    \'id_framework\' => $id,',
            '                    \'dato\' => (string)$actual->dato(),',
            '                    \'repetido\' => isset($visitados[$id]),',
            '                ];',
            '                $visitados[$id] = true;',
            '                $actual = hd($actual);',
            '                $seg++;',
            '            }',
            '            $out[\'dueno_\' . $nombre_dueno] = [',
            '                \'id_contenedor\' => (string)$cont->id(),',
            '                \'total_iteraciones\' => $seg,',
            '                \'lista\' => $lista,',
            '            ];',
            '        }',
            '    }',
            '',
            '    // Terminal Lujan',
            '    $nombre_term = \'Secretaria_Lujan\';',
            '    $nodo_term = Nodo::nodo_por_id(\'us_\' . $nombre_term);',
            '    if ($nodo_term) {',
            '        $nodo_comp = $nodo_term->adyacente(\'dueno\');',
            '        if ($nodo_comp && $nodo_comp->adyacente(\'_es_compartido\')) {',
            '            $cont = $nodo_comp->adyacente(\'cancelaciones\');',
            '            if ($cont) {',
            '                $lista = [];',
            '                $actual = hmi($cont, [\'hmi\' => \'hmi_\' . $nombre_term, \'hd\' => \'hd_\' . $nombre_term, \'p\' => \'p_\' . $nombre_term]);',
            '                $seg = 0;',
            '                $visitados = [];',
            '                while ($actual && $seg < 50) {',
            '                    $id = (string)$actual->id();',
            '                    $lista[] = [',
            '                        \'id_framework\' => $id,',
            '                        \'dato\' => (string)$actual->dato(),',
            '                        \'repetido\' => isset($visitados[$id]),',
            '                    ];',
            '                    $visitados[$id] = true;',
            '                    $actual = hd($actual, [\'hmi\' => \'hmi_\' . $nombre_term, \'hd\' => \'hd_\' . $nombre_term, \'p\' => \'p_\' . $nombre_term]);',
            '                    $seg++;',
            '                }',
            '                $out[\'terminal_\' . $nombre_term] = [',
            '                    \'id_contenedor\' => (string)$cont->id(),',
            '                    \'total_iteraciones\' => $seg,',
            '                    \'lista\' => $lista,',
            '                ];',
            '            }',
            '        }',
            '    }',
            '',
            '    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);',
            '    exit;',
            '}',
            '// Manejo de impresión',
        ],
        'reemplazar' => [
            '// (bloque ?diagnostico_cancelaciones eliminado en v1.5piloto.84f)',
            '// Era diagnóstico temporal del arco de cancelaciones (ya cerrado).',
            '',
            '// Manejo de impresión',
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