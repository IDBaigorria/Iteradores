<?php
/**
 * Aplicador — Fase 2: cerrar impresión (sesión + permisos).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84b';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Enrutador.php — helper _puede_imprimir
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: helper _puede_imprimir antes de _contextos_para_operacion',
        'buscar' => [
            '/**',
            ' * Devuelve los contextos que hay que cargar para una',
            ' * operación dada, según el solicitante.',
            ' *',
            ' * Etapa 0 del plan de carga parcial (v1.5piloto.78b):',
        ],
        'reemplazar' => [
            '/**',
            ' * Devuelve true si el usuario de la sesión puede imprimir el tipo.',
            ' *',
            ' * Fase 2 (v1.5piloto.84b). Valida `tipo` contra el `nivel` del',
            ' * usuario de la sesión. No navega el grafo: es un chequeo de',
            ' * defensa en profundidad, la restricción real la hace el contexto',
            ' * (si el recurso no está en el contexto del usuario, no se',
            ' * encuentra).',
            ' *',
            ' * Reglas:',
            ' *   - Admin / soporte: cualquier tipo.',
            ' *   - Dueño: los tipos de su subgrafo (informe_*, pasajes, cupon,',
            ' *     pasaje_reserva, pasajes_actualizados, croquis_micro,',
            ' *     planilla_pasajeros_micro, declaracion_jurada).',
            ' *   - Terminal: los tipos de su compartido (pasajes, cupon,',
            ' *     pasaje_reserva, pasajes_actualizados, informe_cancelacion,',
            ' *     croquis_micro, planilla_pasajeros_micro).',
            ' *   - Cualquier otro: false.',
            ' *',
            ' * Nota sobre pasajes_actualizados e informe_cancelacion para',
            ' * terminal: requieren que el URL pase `dueno` para que el',
            ' * contexto cargue el árbol `ventas`/`cancelaciones` del dueño',
            ' * con nombres default. Si no pasa `dueno`, el terminal recibe',
            ' * una lista vacía (o "no encontrada"), no un 403. Es el',
            ' * comportamiento actual, no empeora.',
            ' *',
            ' * @param array  $usuario_sesion Array de `usuario_sesion_actual()`.',
            ' * @param string $tipo           Tipo de impresión.',
            ' * @return bool',
            ' * @since 1.5piloto.84b',
            ' */',
            'function _puede_imprimir(array $usuario_sesion, string $tipo): bool {',
            '    $nivel = (string)($usuario_sesion[\'nivel\'] ?? \'\');',
            '    if ($nivel === \'admin\' || $nivel === \'soporte\') return true;',
            '',
            '    $tipos_dueno = [',
            '        \'pasajes\', \'cupon\', \'pasaje_reserva\', \'pasajes_actualizados\',',
            '        \'informe_ventas\', \'informe_rendicion\', \'informe_liquidacion\',',
            '        \'informe_cancelacion\', \'croquis_micro\', \'planilla_pasajeros_micro\',',
            '        \'declaracion_jurada\',',
            '    ];',
            '    $tipos_terminal = [',
            '        \'pasajes\', \'cupon\', \'pasaje_reserva\', \'pasajes_actualizados\',',
            '        \'informe_cancelacion\', \'croquis_micro\', \'planilla_pasajeros_micro\',',
            '    ];',
            '',
            '    if ($nivel === \'dueno\') return in_array($tipo, $tipos_dueno, true);',
            '    if ($nivel === \'terminal\') return in_array($tipo, $tipos_terminal, true);',
            '',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Devuelve los contextos que hay que cargar para una',
            ' * operación dada, según el solicitante.',
            ' *',
            ' * Etapa 0 del plan de carga parcial (v1.5piloto.78b):',
        ],
    ],

    // ============================================================
    // Enrutador.php — Caso 2 de _contextos_para_operacion
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: Caso 2 impresión usa sesión, no carga total',
        'buscar' => [
            '    // Caso 2: GET de impresión. Los flujos imprimibles pasan',
            '    // el dueño por query string. Si está, carga parcial de',
            '    // ese dueño. Si no, carga total (los 6 helpers de B3.3c',
            '    // que iteran sobre todos los dueños todavía no están',
            '    // reescritos; ver `plan_actual.md` §1.10).',
            '    if (isset($get[\'imprimir\']) && $get[\'imprimir\'] === \'1\') {',
            '        $dueno = $get[\'dueno\'] ?? \'\';',
            '        if ($dueno === \'\') return null;',
            '',
            '        // Fase B3.3c (v1.5piloto.81j): los informes de',
            '        // cancelación, liquidación y rendición ahora navegan',
            '        // directo por us_<dueño>. Se eliminó la excepción de',
            '        // carga total que existía acá.',
            '        return [\'us_\' . $dueno];',
            '    }',
        ],
        'reemplazar' => [
            '    // Caso 2: GET de impresión. Fase 2 (v1.5piloto.84b):',
            '    // el pre-check de index.php ya validó sesión y permisos',
            '    // antes de llegar acá. El contexto se arma con:',
            '    //   - el `dueno` del GET si viene (backward compat con',
            '    //     las URLs existentes que lo pasan);',
            '    //   - si no, el contexto natural del usuario de la sesión',
            '    //     (us_<nombre>).',
            '    // En ningún caso se hace carga total: siempre parcial.',
            '    if (isset($get[\'imprimir\']) && $get[\'imprimir\'] === \'1\') {',
            '        $usuario_imp = usuario_sesion_actual();',
            '        if ($usuario_imp === null) return null; // defensivo (pre-check ya devolvió 403)',
            '        $nombre_imp = (string)($usuario_imp[\'nombre_usuario\'] ?? \'\');',
            '        if ($nombre_imp === \'\') return null;',
            '',
            '        $dueno_imp = trim((string)($get[\'dueno\'] ?? \'\'));',
            '        if ($dueno_imp !== \'\') return [\'us_\' . $dueno_imp];',
            '',
            '        return [\'us_\' . $nombre_imp];',
            '    }',
        ],
    ],

    // ============================================================
    // index.php — pre-check de impresión
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index: pre-check de impresión antes de cargar el grafo',
        'buscar' => [
            '// Alinear el prefijo de sesión del framework con el del piloto',
            '// (el framework no conoce el nombre de la app).',
            'Entorno::establecer_prefijo_sesion(ConfiguracionApli::PREFIJO_SESSION);',
            '',
            '// ==== Migración a SQL64 (v1.5piloto.78e) ====',
        ],
        'reemplazar' => [
            '// Alinear el prefijo de sesión del framework con el del piloto',
            '// (el framework no conoce el nombre de la app).',
            'Entorno::establecer_prefijo_sesion(ConfiguracionApli::PREFIJO_SESSION);',
            '',
            '// ==== Fase 2 (v1.5piloto.84b): pre-check de impresión ====',
            '// Valida sesión y permisos ANTES de cargar el grafo. Sin',
            '// sesión o sin permiso → 403 sin cargar nada. Con esto,',
            '// ?imprimir=1 deja de ser un endpoint público.',
            '//',
            '// Trade-off documentado en `plan_actual.md` §8.2: si la',
            '// sesión fue cerrada remotamente pero el navegador no',
            '// recargó, el token sigue en $_SESSION y el pre-check',
            '// pasa. Se endurece en Fase 3 con chequeo periódico del',
            '// token contra el grafo de credenciales.',
            'if (isset($_GET[\'imprimir\']) && $_GET[\'imprimir\'] === \'1\') {',
            '    $usuario_sesion_imp = usuario_sesion_actual();',
            '    if (!$usuario_sesion_imp) {',
            '        http_response_code(403);',
            '        echo \'Sesión no válida. Iniciá sesión para ver esta página.\';',
            '        exit;',
            '    }',
            '    $tipo_imp = $_GET[\'tipo\'] ?? \'\';',
            '    if (!_puede_imprimir($usuario_sesion_imp, $tipo_imp)) {',
            '        http_response_code(403);',
            '        echo \'No tenés permiso para ver este contenido.\';',
            '        exit;',
            '    }',
            '}',
            '',
            '// ==== Migración a SQL64 (v1.5piloto.78e) ====',
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