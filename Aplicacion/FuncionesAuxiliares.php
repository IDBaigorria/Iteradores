<?php
/**
 * Aplicador — Tanda C.6: fix de persistencia para guardados lentos
 * + reparación de ventas canceladas incompletas.
 *
 * Motivo: en hostings lentos (wait_timeout = 20s), el guardado
 * parcial de un grafo grande pierde la conexión antes del commit.
 * El rollback implícito deja la DB intacta, pero el backend
 * responde "éxito" porque no chequea guardar_ambos. El usuario
 * ve "cancelada" pero la venta sigue activa.
 *
 * Cambios:
 * 1. SQL base: SET SESSION wait_timeout = 300.
 * 2. SQL64: ping() + chequeo de commit() antes de retornar true.
 * 3. cancelar_venta: chequea guardar_ambos; si falla, exito: false.
 * 4. reparar_cancelaciones_compartidos: detecta y repara ventas
 *    canceladas incompletas (tienen cancelación pero no 'cancelada').
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81w';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. SQL base: SET SESSION wait_timeout
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
    'descripcion' => 'SQL base: extender wait_timeout a 300s',
    'buscar' => [
        '		$charset = $sql->character_set_name();',
        '		if ($charset != "utf8mb4") {',
        '			$sql->set_charset("utf8mb4");',
        '		}',
        '		//echo $charset;',
        '		return $sql;',
    ],
    'reemplazar' => [
        '		$charset = $sql->character_set_name();',
        '		if ($charset != "utf8mb4") {',
        '			$sql->set_charset("utf8mb4");',
        '		}',
        '		// Fix 1.5i.8d: extender wait_timeout a 300s. InfinityFree',
        '		// y otros hostings tienen 20s por defecto, lo que mata',
        '		// los guardados parciales largos. El rollback implícito',
        '		// deja la DB intacta pero el backend responde "éxito"',
        '		// sin chequear guardar_ambos.',
        '		@$sql->query("SET SESSION wait_timeout = 300");',
        '		return $sql;',
    ],
],

// ============================================================
// 2. SQL64: ping + chequeo de commit
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verificar conexión antes del commit',
    'buscar' => [
        '            $sql->commit();',
        '            $sql->close();',
        '            return true;',
        '        } catch (\Throwable $e) {',
        '            $sql->rollback();',
        '            self::_error($e->getMessage());',
        '            $sql->close();',
        '            return false;',
        '        }',
    ],
    'reemplazar' => [
        '            // Fix 1.5i.8d: verificar que la conexión siga viva',
        '            // antes del commit. Si MySQL cerró la sesión (timeout,',
        '            // hostil, red), el commit falla silenciosamente y',
        '            // algunos nodos se persisten, otros no. Mejor abortar',
        '            // limpio.',
        '            if (!$sql->ping()) {',
        '                throw new \RuntimeException("SQL64::guardar: conexión perdida antes del commit. Se revierte la operación.");',
        '            }',
        '            if (!$sql->commit()) {',
        '                throw new \RuntimeException("SQL64::guardar: commit falló: " . $sql->error);',
        '            }',
        '            $sql->close();',
        '            return true;',
        '        } catch (\Throwable $e) {',
        '            $sql->rollback();',
        '            self::_error($e->getMessage());',
        '            $sql->close();',
        '            return false;',
        '        }',
    ],
],

// ============================================================
// 3. cancelar_venta: chequear guardar_ambos
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'cancelar_venta: chequear guardar_ambos',
    'buscar' => [
        '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '',
        '    $dev_ef_total = $desglose[\'en_terminal_efectivo\'] + $desglose[\'rendido_efectivo\'];',
        '    $dev_ba_total = $desglose[\'en_terminal_banco\'] + $desglose[\'rendido_banco\'];',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.81w: chequear el resultado del guardado.',
        '    // Antes se ignoraba y el backend respondía "éxito" aunque',
        '    // la DB no se hubiera actualizado (bug §4.6 en hostings',
        '    // lentos con wait_timeout corto).',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        Controlador::_error("cancelar_venta: guardar_ambos falló para id_venta=$id_venta");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La cancelación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor (wait_timeout, conexión) o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    $dev_ef_total = $desglose[\'en_terminal_efectivo\'] + $desglose[\'rendido_efectivo\'];',
        '    $dev_ba_total = $desglose[\'en_terminal_banco\'] + $desglose[\'rendido_banco\'];',
    ],
],

// ============================================================
// 4. RepararCancelaciones.php: detectar ventas incompletas
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/RepararCancelaciones.php',
    'descripcion' => 'RepararCancelaciones: reparar ventas canceladas incompletas',
    'buscar' => [
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) {',
        '        $res[\'detalles\'][] = \'No existe la raíz usuarios.\';',
        '        return $res;',
        '    }',
        '',
        '    // ─── Paso 1: para cada compartido, agregar al árbol del',
        '    // dueño las cancelaciones que falten. ───────────────────',
    ],
    'reemplazar' => [
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) {',
        '        $res[\'detalles\'][] = \'No existe la raíz usuarios.\';',
        '        return $res;',
        '    }',
        '',
        '    // ─── Paso 0: reparar ventas canceladas incompletas. ────',
        '    // Residuo del bug §4.6: la venta tiene una cancelación',
        '    // asociada pero no el autoenlace \'cancelada\' y los',
        '    // asientos siguen vendidos. Fix 1.5piloto.81w.',
        '    $res[\'ventas_reparadas\'] = 0;',
        '    _reparar_ventas_canceladas_incompletas($token, $res);',
        '',
        '    // ─── Paso 1: para cada compartido, agregar al árbol del',
        '    // dueño las cancelaciones que falten. ───────────────────',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/RepararCancelaciones.php',
    'descripcion' => 'RepararCancelaciones: helper _reparar_ventas_canceladas_incompletas',
    'buscar' => [
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Repara ciclos en los árboles de cancelaciones.',
    ],
    'reemplazar' => [
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Detecta ventas que tienen una cancelación asociada (por',
        ' * id_venta) pero que NO están marcadas con el autoenlace',
        ' * \'cancelada\'. Es residuo del bug §4.6 (guardado parcial',
        ' * fallido en hostings lentos).',
        ' *',
        ' * Para cada una: libera los asientos que quedaron vendidos',
        ' * y marca la venta con \'cancelada\'. Idempotente.',
        ' *',
        ' * @param string $token',
        ' * @param array  $res  Por referencia.',
        ' * @return void',
        ' * @since 1.5piloto.81w',
        ' */',
        'function _reparar_ventas_canceladas_incompletas(string $token, array &$res): void {',
        '    // Fase 1: recolectar.',
        '    $cancelaciones_por_venta = [];',
        '    $ventas_activas = [];',
        '',
        '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$cancelaciones_por_venta, &$ventas_activas) {',
        '        $dato = (string)$nodo->dato();',
        '        if (strpos($dato, \'cancelacion_\') === 0) {',
        '            $nodo_id_venta = $nodo->adyacente(\'id_venta\');',
        '            if ($nodo_id_venta) {',
        '                $cancelaciones_por_venta[(string)$nodo_id_venta->dato()] = $dato;',
        '            }',
        '        } elseif (strpos($dato, \'venta_\') === 0) {',
        '            if (!$nodo->adyacente(\'cancelada\')) {',
        '                $ventas_activas[$dato] = $nodo;',
        '            }',
        '        }',
        '    }, null);',
        '',
        '    if (empty($cancelaciones_por_venta) || empty($ventas_activas)) return;',
        '',
        '    // Fase 2: reparar.',
        '    foreach ($cancelaciones_por_venta as $id_venta => $id_cancelacion) {',
        '        if (!isset($ventas_activas[$id_venta])) continue;',
        '        _reparar_venta_cancelada_incompleta($ventas_activas[$id_venta]);',
        '        $res[\'ventas_reparadas\']++;',
        '        $res[\'cambios\']++;',
        '        $res[\'detalles\'][] = "venta $id_venta: reparada (tenía $id_cancelacion pero no \'cancelada\')";',
        '    }',
        '}',
        '',
        '/**',
        ' * Libera los asientos de una venta cancelada incompleta y',
        ' * marca el nodo venta con el autoenlace \'cancelada\'.',
        ' *',
        ' * @param Nodo $nodo_venta',
        ' * @return void',
        ' * @since 1.5piloto.81w',
        ' */',
        'function _reparar_venta_cancelada_incompleta(Nodo $nodo_venta): void {',
        '    // 1. Liberar asientos.',
        '    $cabeza = $nodo_venta->adyacente(\'asientos\');',
        '    if ($cabeza) {',
        '        $av = $cabeza->adyacente(\'primer\');',
        '        $seg = 0;',
        '        while ($av && $seg < 200) {',
        '            $asiento = $av->adyacente(\'asiento\');',
        '            if ($asiento) {',
        '                $estado = $asiento->adyacente(\'estado\');',
        '                if ($estado) $estado->_dato(\'libre\');',
        '                $asiento->eliminar_adyacente(\'pasajero\');',
        '                $asiento->eliminar_adyacente(\'venta\');',
        '            }',
        '            $av = $av->adyacente(\'siguiente\');',
        '            $seg++;',
        '        }',
        '    }',
        '',
        '    // 2. Marcar la venta con el autoenlace \'cancelada\'.',
        '    $nodo_venta->_adyacente_en($nodo_venta, \'cancelada\');',
        '',
        '    // 3. Actualizar contadores del micro y del viaje.',
        '    $nodo_micro = $nodo_venta->adyacente(\'micro\');',
        '    $nodo_viaje = $nodo_venta->adyacente(\'viaje\');',
        '    if ($nodo_micro) actualizar_contadores_micro($nodo_micro);',
        '    if ($nodo_viaje && $nodo_micro) {',
        '        $nombre_viaje = (string)$nodo_viaje->dato();',
        '        $nodo_dueno = $nodo_viaje->adyacente(\'dueno\');',
        '        $nombre_dueno = $nodo_dueno ? (string)$nodo_dueno->dato() : \'\';',
        '        if ($nombre_dueno !== \'\') {',
        '            actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
        '        }',
        '    }',
        '}',
        '',
        '/**',
        ' * Repara ciclos en los árboles de cancelaciones.',
    ],
],

// ============================================================
// 5. RepararCancelaciones.php: mostrar ventas_reparadas en el output
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/RepararCancelaciones.php',
    'descripcion' => 'RepararCancelaciones: sumar ventas_reparadas al resumen',
    'buscar' => [
        '    $res = [',
        '        \'cambios\' => 0,',
        '        \'duenos\' => 0,',
        '        \'compartidos\' => 0,',
        '        \'reinsertadas_dueno\' => 0,',
        '        \'huerfanas_detectadas\' => 0,',
        '        \'reinsertadas_ambos\' => 0,',
        '        \'detalles\' => [],',
        '    ];',
    ],
    'reemplazar' => [
        '    $res = [',
        '        \'cambios\' => 0,',
        '        \'duenos\' => 0,',
        '        \'compartidos\' => 0,',
        '        \'reinsertadas_dueno\' => 0,',
        '        \'huerfanas_detectadas\' => 0,',
        '        \'reinsertadas_ambos\' => 0,',
        '        \'ventas_reparadas\' => 0,',
        '        \'detalles\' => [],',
        '    ];',
    ],
],

// ============================================================
// 6. index.php: mostrar ventas_reparadas en el endpoint
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'Endpoint reparar_cancelaciones: mostrar ventas_reparadas',
    'buscar' => [
        '    echo "Reinsertadas en ambos:  " . $res[\'reinsertadas_ambos\'] . "\\n";',
        '    echo "Cambios totales:        " . $res[\'cambios\'] . "\\n";',
    ],
    'reemplazar' => [
        '    echo "Reinsertadas en ambos:  " . $res[\'reinsertadas_ambos\'] . "\\n";',
        '    echo "Ventas reparadas:       " . ($res[\'ventas_reparadas\'] ?? 0) . "\\n";',
        '    echo "Cambios totales:        " . $res[\'cambios\'] . "\\n";',
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