<?php
/**
 * Aplicador — Fase 3c: chequeo pre-escritura.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84g';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Enrutador.php — helper _es_accion_escritura
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: helper _es_accion_escritura',
        'buscar' => [
            '    return in_array($accion, $pollings, true);',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
        'reemplazar' => [
            '    return in_array($accion, $pollings, true);',
            '}',
            '',
            '/**',
            ' * Devuelve true si la acción modifica el grafo (escritura).',
            ' *',
            ' * Fase 3c (v1.5piloto.84g). Para estas acciones, el',
            ' * chequeo pre-escritura verifica el token SIEMPRE (sin',
            ' * importar el TTL de 30s). Cierra la ventana entre el',
            ' * cierre remoto y la próxima detección: leer sin token',
            ' * es molesto, escribir sin token es grave.',
            ' *',
            ' * `sesiones/cerrar` queda fuera a propósito: ya valida',
            ' * `_puede_cerrar_sesion` y sumar un chequeo extra en',
            ' * cada logout es costo sin beneficio.',
            ' *',
            ' * @param string $accion Acción en formato "modulo/subaccion".',
            ' * @return bool',
            ' * @since 1.5piloto.84g',
            ' */',
            'function _es_accion_escritura(string $accion): bool {',
            '    static $escrituras = [',
            '        // administrador',
            '        \'administrador/agregar_usuario\',',
            '        \'administrador/actualizar_usuario\',',
            '        \'administrador/eliminar_usuario\',',
            '        \'administrador/limpiar_usuarios_prueba\',',
            '        // dueno (terminales)',
            '        \'dueno/agregar_terminal\',',
            '        \'dueno/actualizar_terminal\',',
            '        \'dueno/eliminar_terminal\',',
            '        \'dueno/limpiar_terminales_prueba\',',
            '        // empresas',
            '        \'empresas/agregar\',',
            '        \'empresas/editar\',',
            '        \'empresas/eliminar\',',
            '        \'empresas/limpiar_prueba\',',
            '        // vehiculos',
            '        \'vehiculos/agregar\',',
            '        \'vehiculos/actualizar\',',
            '        \'vehiculos/actualizar_configuracion\',',
            '        \'vehiculos/eliminar\',',
            '        \'vehiculos/subir_foto\',',
            '        // viajes',
            '        \'viajes/limpiar_prueba\',',
            '        \'viajes/guardar\',',
            '        \'viajes/guardar_declaracion\',',
            '        \'viajes/eliminar\',',
            '        \'viajes/agregar_micro\',',
            '        \'viajes/eliminar_micro\',',
            '        \'viajes/migrar_pasajeros\',',
            '        \'viajes/agregar_terminal\',',
            '        \'viajes/eliminar_terminal\',',
            '        \'viajes/actualizar_monto_micro\',',
            '        \'viajes/seleccionar_asiento\',',
            '        \'viajes/deseleccionar_asiento\',',
            '        \'viajes/reiniciar_seleccion_completa\',',
            '        \'viajes/reservar_asiento\',',
            '        \'viajes/asignar_pasajero_reserva\',',
            '        \'viajes/liberar_reserva_asiento\',',
            '        \'viajes/cambiar_asiento\',',
            '        \'viajes/guardar_opciones_avanzadas\',',
            '        \'viajes/guardar_opciones_terminal\',',
            '        // ventas',
            '        \'ventas/confirmar\',',
            '        \'ventas/cancelar\',',
            '        \'ventas/pagar_cupon\',',
            '        \'ventas/guardar\',',
            '        \'ventas/limpiar_prueba\',',
            '        // pasajeros',
            '        \'pasajeros/crear\',',
            '        \'pasajeros/subir_declaracion\',',
            '        \'pasajeros/eliminar_declaracion\',',
            '        \'pasajeros/actualizar\',',
            '        \'pasajeros/eliminar\',',
            '        \'pasajeros/limpiar_prueba\',',
            '        // rendiciones',
            '        \'rendiciones/confirmar\',',
            '        \'rendiciones/aceptar_ajuste\',',
            '        // liquidaciones',
            '        \'liquidaciones/confirmar\',',
            '        // cancelaciones',
            '        \'cancelaciones/limpiar_prueba\',',
            '        // grafo',
            '        \'grafo/eliminar_huerfanos\',',
            '        \'grafo/migracion_limpiar\',',
            '        \'grafo/migraciones_aplicar\',',
            '    ];',
            '    return in_array($accion, $escrituras, true);',
            '}',
            '',
            '/**',
            ' * Devuelve el nodo del compartido del solicitante si es',
            ' * terminal, o null en otros casos.',
        ],
    ],

    // ============================================================
    // Enrutador.php — chequeo pre-escritura
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: chequeo pre-escritura',
        'buscar' => [
            '        $post[\'nombre_solicitante\'] = (string)($sesion_f3[\'nombre_usuario\'] ?? \'\');',
            '    }',
            '',
            '    // Fase 3b (v1.5piloto.84f): chequeo periódico del token.',
        ],
        'reemplazar' => [
            '        $post[\'nombre_solicitante\'] = (string)($sesion_f3[\'nombre_usuario\'] ?? \'\');',
            '    }',
            '',
            '    // Fase 3c (v1.5piloto.84g): chequeo pre-escritura.',
            '    // Si la acción modifica el grafo, verificar el token',
            '    // SIEMPRE, sin importar el TTL de 30s. Cierra la',
            '    // ventana entre el cierre remoto y la próxima',
            '    // detección (en lecturas puede ser de hasta 30s, en',
            '    // escrituras es 0).',
            '    if (!_es_accion_publica($accion) && _es_accion_escritura($accion)) {',
            '        $prefijo_esc = Entorno::prefijo_sesion();',
            '        $token_esc = token_sesion_actual();',
            '        if ($token_esc !== null) {',
            '            $res_esc = validar_token_sesion($token_esc);',
            '            if ($res_esc === null) {',
            '                limpiar_sesion_php();',
            '                responder_json([\'exito\' => false, \'error\' => \'Sesión cerrada o expirada. Volvé a iniciar sesión.\']);',
            '            }',
            '            $_SESSION[$prefijo_esc . \'ultima_validacion_token\'] = time();',
            '        }',
            '    }',
            '',
            '    // Fase 3b (v1.5piloto.84f): chequeo periódico del token.',
        ],
    ],

    // ============================================================
    // FuncionesAuxiliares.php — chequeo pre-guardado en guardar_ambos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'FuncionesAuxiliares: chequeo pre-guardado en guardar_ambos',
        'buscar' => [
            '    if (!Nodo::hay_nodos_en_superestructura()) {',
            '        Controlador::_error("guardar_ambos: superestructura vacia para \\"$nombre\\". Se aborta para no pisar el grafo.");',
            '        return false;',
            '    }',
            '',
            '    // Fase de carga parcial (v1.5piloto.78c): si el grafo en',
        ],
        'reemplazar' => [
            '    if (!Nodo::hay_nodos_en_superestructura()) {',
            '        Controlador::_error("guardar_ambos: superestructura vacia para \\"$nombre\\". Se aborta para no pisar el grafo.");',
            '        return false;',
            '    }',
            '',
            '    // Fase 3c (v1.5piloto.84g): chequeo pre-guardado.',
            '    // Defensa en profundidad. Si hay sesión activa y su',
            '    // token dejó de estar en el grafo (cierre remoto),',
            '    // abortar. El enrutador ya chequea antes de procesar',
            '    // escrituras, pero este chequeo cubre flujos que no',
            '    // pasan por el enrutador (comandos, migraciones). Si',
            '    // no hay sesión, no chequear (bootstrap, migraciones',
            '    // CLI, comandos).',
            '    if (session_status() === PHP_SESSION_ACTIVE',
            '        && function_exists(\'usuario_sesion_actual\')',
            '        && function_exists(\'validar_token_sesion\')',
            '        && function_exists(\'token_sesion_actual\')',
            '        && class_exists(\'\\Iteradores\\Configuracion\\Entorno\')) {',
            '        $sesion_ga = usuario_sesion_actual();',
            '        if ($sesion_ga !== null) {',
            '            $token_ga = token_sesion_actual();',
            '            if ($token_ga !== null) {',
            '                $prefijo_ga = \\Iteradores\\Configuracion\\Entorno::prefijo_sesion();',
            '                $ultima_val_ga = (int)($_SESSION[$prefijo_ga . \'ultima_validacion_token\'] ?? 0);',
            '                if (time() - $ultima_val_ga > 30) {',
            '                    $res_ga = validar_token_sesion($token_ga);',
            '                    if ($res_ga === null) {',
            '                        Controlador::_error("guardar_ambos: token de sesion invalido (cierre remoto?). Se aborta el guardado de \\"$nombre\\".");',
            '                        return false;',
            '                    }',
            '                    $_SESSION[$prefijo_ga . \'ultima_validacion_token\'] = time();',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    // Fase de carga parcial (v1.5piloto.78c): si el grafo en',
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