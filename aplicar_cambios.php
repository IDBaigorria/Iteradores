<?php
/**
 * Aplicador — Grupo A: limpieza de deuda técnica del piloto
 * (v83q).
 *
 *   A1. Indentación cosmética en limpiar_cancelaciones_de_prueba.
 *   A2. log_persistencia en 4 flujos de buzón.
 *   A3. log_persistencia en reiniciar_seleccion_completa.
 *   A4. Chequear guardar_ambos en 3 comandos de reparación.
 *   A5. Documentar GET_LOCK en JS como "no aplica".
 *   A6. Quitar error_log("[CREAR_CONEXION]") de SQL base.
 *   A7. VERSION_APP en ConfiguracionApli (de "0.0.0" a la
 *       versión actual del piloto).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83q';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// A1. Indentación cosmética en limpiar_cancelaciones_de_prueba
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'A1: alinear Controlador::_error',
    'buscar' => [
        '            log_persistencia("limpiar_cancelaciones_de_prueba: guardar_ambos falló", \'error\');',
        '        Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");',
        '            return [',
    ],
    'reemplazar' => [
        '            log_persistencia("limpiar_cancelaciones_de_prueba: guardar_ambos falló", \'error\');',
        '            Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");',
        '            return [',
    ],
],

// ============================================================
// A2. log_persistencia en 4 flujos de buzón
// ============================================================

// A2.1 - listar_ventas_por_dueno, sin contenedor (Venta.php:696)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'A2.1: log en listar_ventas_por_dueno (sin contenedor)',
    'buscar' => [
        '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
        '    if (!$contenedor) {',
        '        if ($hubo_cambios) {',
        '            guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '        }',
        '        return [];',
        '    }',
    ],
    'reemplazar' => [
        '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
        '    if (!$contenedor) {',
        '        if ($hubo_cambios) {',
        '            $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            if (!$ok_guardar) {',
        '                log_persistencia("listar_ventas_por_dueno: guardar_ambos falló al drenar buzón (sin contenedor)", \'error\');',
        '                Controlador::_error("listar_ventas_por_dueno: guardar_ambos falló al drenar buzón (sin contenedor)");',
        '            }',
        '        }',
        '        return [];',
        '    }',
    ],
],

// A2.2 - listar_ventas_por_dueno, al final del barrido (Venta.php:716)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'A2.2: log en listar_ventas_por_dueno (fin del barrido)',
    'buscar' => [
        '    if ($hubo_cambios) {',
        '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    }',
        '    return $ventas;',
        '}',
    ],
    'reemplazar' => [
        '    if ($hubo_cambios) {',
        '        $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '        if (!$ok_guardar) {',
        '            log_persistencia("listar_ventas_por_dueno: guardar_ambos falló al cerrar el barrido de buzón", \'error\');',
        '            Controlador::_error("listar_ventas_por_dueno: guardar_ambos falló al cerrar el barrido de buzón");',
        '        }',
        '    }',
        '    return $ventas;',
        '}',
    ],
],

// A2.3 - listar_cancelaciones_de_dueno (Venta.php:3026)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'A2.3: log en listar_cancelaciones_de_dueno',
    'buscar' => [
        '    if ($nodo_contexto === null) {',
        '        $hubo_cambios = _procesar_pendientes_del_dueno($nombre_dueno);',
        '        if ($hubo_cambios) {',
        '            guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    if ($nodo_contexto === null) {',
        '        $hubo_cambios = _procesar_pendientes_del_dueno($nombre_dueno);',
        '        if ($hubo_cambios) {',
        '            $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            if (!$ok_guardar) {',
        '                log_persistencia("listar_cancelaciones_de_dueno: guardar_ambos falló al drenar buzón", \'error\');',
        '                Controlador::_error("listar_cancelaciones_de_dueno: guardar_ambos falló al drenar buzón");',
        '            }',
        '        }',
        '    }',
    ],
],

// A2.4 - listar_viajes_de_dueno (Viaje.php:249)
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/Viaje.php',
    'descripcion' => 'A2.4: log en listar_viajes_de_dueno',
    'buscar' => [
        '    if (function_exists(\'_procesar_pendientes_del_dueno\')) {',
        '        $hubo_drenado = _procesar_pendientes_del_dueno($nombre_dueno);',
        '        if ($hubo_drenado) {',
        '            guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    if (function_exists(\'_procesar_pendientes_del_dueno\')) {',
        '        $hubo_drenado = _procesar_pendientes_del_dueno($nombre_dueno);',
        '        if ($hubo_drenado) {',
        '            $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            if (!$ok_guardar) {',
        '                log_persistencia("listar_viajes_de_dueno: guardar_ambos falló al drenar buzón", \'error\');',
        '                Controlador::_error("listar_viajes_de_dueno: guardar_ambos falló al drenar buzón");',
        '            }',
        '        }',
        '    }',
    ],
],

// ============================================================
// A3. log_persistencia en reiniciar_seleccion_completa
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'A3: log en reiniciar_seleccion_completa',
    'buscar' => [
        '    $nodo_terminal->eliminar_adyacente(\'venta_actual\');',
        '    _destruir_venta_actual($venta_actual);',
        '',
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    return [\'exito\' => true, \'habia_venta\' => true];',
    ],
    'reemplazar' => [
        '    $nodo_terminal->eliminar_adyacente(\'venta_actual\');',
        '    _destruir_venta_actual($venta_actual);',
        '',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("reiniciar_seleccion_completa: guardar_ambos falló", \'error\');',
        '        Controlador::_error("reiniciar_seleccion_completa: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La selección se reinició en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '    return [\'exito\' => true, \'habia_venta\' => true];',
    ],
],

// ============================================================
// A4. guardar_ambos en 3 comandos de reparación
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/Comandos.php',
    'descripcion' => 'A4.1: check en app:reparar_arboles',
    'buscar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararArboles.php\';',
        '            $res = reparar_arboles($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            }',
        '            return $res;',
        '        },',
    ],
    'reemplazar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararArboles.php\';',
        '            $res = reparar_arboles($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                if (!$ok_guardar) {',
        '                    log_persistencia("app:reparar_arboles: guardar_ambos falló", \'error\');',
        '                    Controlador::_error("app:reparar_arboles: guardar_ambos falló");',
        '                    $res[\'exito\'] = false;',
        '                    $res[\'error\'] = \'Los cambios se procesaron en memoria pero no se pudieron guardar en la base de datos.\';',
        '                }',
        '            }',
        '            return $res;',
        '        },',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/Comandos.php',
    'descripcion' => 'A4.2: check en app:reparar_cancelaciones_compartidos',
    'buscar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararCancelaciones.php\';',
        '            $res = reparar_cancelaciones_compartidos($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            }',
        '            return $res;',
        '        },',
    ],
    'reemplazar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararCancelaciones.php\';',
        '            $res = reparar_cancelaciones_compartidos($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                if (!$ok_guardar) {',
        '                    log_persistencia("app:reparar_cancelaciones_compartidos: guardar_ambos falló", \'error\');',
        '                    Controlador::_error("app:reparar_cancelaciones_compartidos: guardar_ambos falló");',
        '                    $res[\'exito\'] = false;',
        '                    $res[\'error\'] = \'Los cambios se procesaron en memoria pero no se pudieron guardar en la base de datos.\';',
        '                }',
        '            }',
        '            return $res;',
        '        },',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/Comandos.php',
    'descripcion' => 'A4.3: check en app:reparar_cancelaciones_arboles',
    'buscar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararCancelaciones.php\';',
        '            $res = reparar_cancelaciones_arboles($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '            }',
        '            return $res;',
        '        },',
    ],
    'reemplazar' => [
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/RepararCancelaciones.php\';',
        '            $res = reparar_cancelaciones_arboles($token);',
        '            if (!empty($res[\'cambios\'])) {',
        '                $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '                if (!$ok_guardar) {',
        '                    log_persistencia("app:reparar_cancelaciones_arboles: guardar_ambos falló", \'error\');',
        '                    Controlador::_error("app:reparar_cancelaciones_arboles: guardar_ambos falló");',
        '                    $res[\'exito\'] = false;',
        '                    $res[\'error\'] = \'Los cambios se procesaron en memoria pero no se pudieron guardar en la base de datos.\';',
        '                }',
        '            }',
        '            return $res;',
        '        },',
    ],
],

// ============================================================
// A5. GET_LOCK en JS: documentar como "no aplica" (§12)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'A5: documentar GET_LOCK en JS',
    'buscar' => [
        'Cualquier cambio al framework PHP que toque la API compartida debe',
        'reflejarse también en el espejo JS.',
    ],
    'reemplazar' => [
        '**Diferencia de plataforma: `GET_LOCK` en JS.** El espejo JS',
        '(`PerdurarSuperestructuraStringIndexedDB64.js`) **no tiene**',
        'equivalente al `GET_LOCK(\'grafo_<nombre>\', 5)` del PHP. No',
        'aplica: IndexedDB es single-thread por origen. Dos pestañas',
        'del mismo origen con la misma DB coordinan a través de',
        'transacciones atómicas (`tx.oncomplete`/`tx.onerror`); no',
        'hay concurrencia real entre transacciones. La transacción',
        'atómica de IndexedDB (DELETE + INSERT en la misma',
        '`db.transaction`) cumple el mismo rol que el lock +',
        '`begin_transaction` del PHP: o se aplica todo, o no se',
        'aplica nada. Esta es una diferencia de plataforma, no una',
        'deuda pendiente. Documentado en framework 1.5i.7x.',
        '',
        'Cualquier cambio al framework PHP que toque la API compartida debe',
        'reflejarse también en el espejo JS.',
    ],
],

// ============================================================
// A6. Quitar error_log("[CREAR_CONEXION]") de SQL base
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
    'descripcion' => 'A6: quitar error_log de diagnóstico en SQL base',
    'buscar' => [
        '        mysqli_report(MYSQLI_REPORT_OFF);',
        '',
        '        // Log de versión para diagnóstico (aparece en error_log).',
        '        error_log("[CREAR_CONEXION] version=82c, singleton=" . (self::$_sql_singleton ? "vivo" : "nulo"));',
        '',
        '        // Reusar la conexión si sigue viva.',
    ],
    'reemplazar' => [
        '        mysqli_report(MYSQLI_REPORT_OFF);',
        '',
        '        // Fix 1.5piloto.83q: se quitó el error_log("[CREAR_CONEXION]")',
        '        // de diagnóstico. Era ruido en el log de Apache y no aportaba',
        '        // (la versión era fija, el singleton se ve por otros logs).',
        '',
        '        // Reusar la conexión si sigue viva.',
    ],
],

// ============================================================
// A7. VERSION_APP en ConfiguracionApli
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/ConfiguracionApli.php',
    'descripcion' => 'A7: VERSION_APP con valor real',
    'buscar' => [
        '    /**',
        '     * Versión de la aplicación piloto.',
        '     *',
        '     * @var string',
        '     */',
        '    public const VERSION_APP = "0.0.0";',
    ],
    'reemplazar' => [
        '    /**',
        '     * Versión de la aplicación piloto.',
        '     *',
        '     * Fix 1.5piloto.83q: antes era "0.0.0" (nunca se actualizó).',
        '     * Se usa en el endpoint de diagnóstico ?diag_persistencia=1.',
        '     * **Actualización manual:** se sincroniza a mano con el',
        '     * @version del index.php en cada tanda grande (Tanda, no',
        '     * letra). Si se olvida, el diag muestra una versión vieja,',
        '     * pero no rompe nada. Deuda anotada en plan_actual.md §4.8:',
        '     * convertir en método que lea el @version del index.php.',
        '     *',
        '     * @var string',
        '     */',
        '    public const VERSION_APP = "1.5piloto.83q";',
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

$bumps_atversion = 0;
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
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";