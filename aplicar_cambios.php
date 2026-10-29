<?php
/**
 * Aplicador — Piloto 1.5piloto.83o: returns tempranos en los 3
 * flujos de Usuario.php + optimización del login.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83o';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Usuario.php: agregar_usuario con return temprano
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'agregar_usuario: return temprano',
    'buscar' => [
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales, y un return acá dejaría el usuario sin credenciales.',
        '    // El return temprano correcto requiere revisar el formato exacto',
        '    // de la respuesta; queda como deuda técnica en plan_actual.md.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_usuario: guardar_ambos falló");',
        '    }',
        '',
        '    // Escribir credenciales en su grafo aparte.',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83o: return temprano si el guardado de la app falla.',
        '    // Antes solo se logueaba y el flujo continuaba con el guardado de',
        '    // credenciales, dejando al usuario sin datos visibles pero con',
        '    // credenciales válidas (login funcionaba pero',
        '    // _construir_respuesta_login fallaba). Ahora si la app falla, no',
        '    // se escriben credenciales y el admin ve el error y puede reintentar.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("agregar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("agregar_usuario: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'El usuario se creó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    // Escribir credenciales en su grafo aparte.',
    ],
],

// ============================================================
// 2. Usuario.php: actualizar_usuario con return temprano
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'actualizar_usuario: return temprano',
    'buscar' => [
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales. Ver nota en agregar_usuario.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_usuario: guardar_ambos falló");',
        '    }',
        '',
        '    if ($codigo_acceso !== \'\' || $contrasena !== \'\') {',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83o: return temprano si el guardado de la app falla.',
        '    // Mismo patrón que agregar_usuario. Ver nota allá.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("actualizar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("actualizar_usuario: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La actualización se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    if ($codigo_acceso !== \'\' || $contrasena !== \'\') {',
    ],
],

// ============================================================
// 3. Usuario.php: eliminar_usuario con return temprano
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'eliminar_usuario: return temprano',
    'buscar' => [
        '    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace',
        '    // return temprano porque este flujo continúa con el guardado de',
        '    // credenciales. Ver nota en agregar_usuario.',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("eliminar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("eliminar_usuario: guardar_ambos falló");',
        '    }',
        '',
        '    // === 7. Destruir en credenciales: sesiones + nodo + campos ===',
    ],
    'reemplazar' => [
        '    // Fix 1.5piloto.83o: return temprano si el guardado de la app falla.',
        '    // Si la app falla y credenciales se guardaba, el usuario quedaba',
        '    // eliminado de credenciales pero presente en la app, con todas sus',
        '    // ventas/pasajeros/viajes colgando. Ahora si la app falla, el usuario',
        '    // sigue existiendo en ambos grafos (consistente).',
        '    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        '    if (!$ok_guardar) {',
        '        log_persistencia("eliminar_usuario: guardar_ambos falló", \'error\');',
        '        Controlador::_error("eliminar_usuario: guardar_ambos falló");',
        '        return [',
        '            \'exito\' => false,',
        '            \'error\' => \'La eliminación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.\',',
        '        ];',
        '    }',
        '',
        '    // === 7. Destruir en credenciales: sesiones + nodo + campos ===',
    ],
],

// ============================================================
// 4. Autenticacion.php: autenticar_por_codigo, no guardar app
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'autenticar_por_codigo: no guardar app',
    'buscar' => [
        '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente, \'codigo_hash\', $codigo);',
        '        $nombre_usuario = $encontrado;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario((string)$encontrado);',
        '    });',
    ],
    'reemplazar' => [
        '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente, \'codigo_hash\', $codigo);',
        '        $nombre_usuario = $encontrado;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario((string)$encontrado);',
        '    }, false);  // Fix 1.5piloto.83o: no guardar la app (solo credenciales).',
    ],
],

// ============================================================
// 5. Autenticacion.php: autenticar_por_usuario, no guardar app
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'autenticar_por_usuario: no guardar app',
    'buscar' => [
        '        _registrar_login_exitoso($nodo_usuario, $ip_cliente, \'contrasena\', $contrasena);',
        '        $verificado = true;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario($nombre_usuario);',
        '    });',
    ],
    'reemplazar' => [
        '        _registrar_login_exitoso($nodo_usuario, $ip_cliente, \'contrasena\', $contrasena);',
        '        $verificado = true;',
        '        // Fix 1.5piloto.83m: setear el usuario en el log dentro del',
        '        // callback, para que el guardar_ambos(credenciales) del',
        '        // finally ya tenga el tag [user=X].',
        '        log_persistencia_establecer_usuario($nombre_usuario);',
        '    }, false);  // Fix 1.5piloto.83o: no guardar la app (solo credenciales).',
    ],
],

// ============================================================
// 6. Sesion.php: crear_sesion, no guardar app
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Sesiones/Sesion.php',
    'descripcion' => 'crear_sesion: no guardar app',
    'buscar' => [
        'function crear_sesion(string $nombre_usuario): string {',
        '    return en_grafo_credenciales(function() use ($nombre_usuario) {',
        '        eliminar_sesiones_de_usuario($nombre_usuario);',
        '',
        '        $raiz_sesiones = Nodo::nodo_por_id(\'sesiones\');',
        '        if (!$raiz_sesiones) {',
        '            $raiz_sesiones = Nodo::crear_con_id(\'sesiones\');',
        '        }',
        '',
        '        $token = bin2hex(random_bytes(16));',
        '        $nodo_sesion = Nodo::crear_con_dato(\'\');',
        '        $nodo_sesion->_adyacente_en(Nodo::crear_con_dato($nombre_usuario), \'usuario\');',
        '        $nodo_sesion->_adyacente_en(Nodo::crear_con_dato((string)time()), \'creado_en\');',
        '',
        '        $raiz_sesiones->_adyacente_en($nodo_sesion, $token);',
        '',
        '        return $token;',
        '    });',
        '}',
    ],
    'reemplazar' => [
        'function crear_sesion(string $nombre_usuario): string {',
        '    // Fix 1.5piloto.83o: no guardar la app (solo credenciales). La app',
        '    // no cambia al crear una sesión; el guardado total de la app era',
        '    // innecesario y duplicaba el trabajo en cada login.',
        '    return en_grafo_credenciales(function() use ($nombre_usuario) {',
        '        eliminar_sesiones_de_usuario($nombre_usuario);',
        '',
        '        $raiz_sesiones = Nodo::nodo_por_id(\'sesiones\');',
        '        if (!$raiz_sesiones) {',
        '            $raiz_sesiones = Nodo::crear_con_id(\'sesiones\');',
        '        }',
        '',
        '        $token = bin2hex(random_bytes(16));',
        '        $nodo_sesion = Nodo::crear_con_dato(\'\');',
        '        $nodo_sesion->_adyacente_en(Nodo::crear_con_dato($nombre_usuario), \'usuario\');',
        '        $nodo_sesion->_adyacente_en(Nodo::crear_con_dato((string)time()), \'creado_en\');',
        '',
        '        $raiz_sesiones->_adyacente_en($nodo_sesion, $token);',
        '',
        '        return $token;',
        '    }, false);',
        '}',
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