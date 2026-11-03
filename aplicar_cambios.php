<?php
/**
 * Aplicador — Fase 1 de seguridad: sesión PHP en login + logout.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84a';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Autenticacion.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion: agregar use Entorno',
        'buscar' => [
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'include_once("./Configuracion/Configuracion.php");',
        ],
        'reemplazar' => [
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Configuracion\\Entorno;',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'include_once("./Configuracion/Configuracion.php");',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion: _construir_respuesta_login guarda sesion PHP',
        'buscar' => [
            '    if ($usuario[\'nivel\'] === \'soporte\') {',
            '        $duenos = [];',
            '        $nodo_duenos = $nodo_app->adyacente(\'duenos\');',
            '        if ($nodo_duenos) {',
            '            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                $duenos[] = (string)$nombre_d;',
            '            }',
            '        }',
            '        $usuario[\'duenos\'] = $duenos;',
            '    }',
            '',
            '    $token = crear_sesion($nombre_usuario);',
            '    $usuario[\'token_sesion\'] = $token;',
            '',
            '    return $usuario;',
            '}',
        ],
        'reemplazar' => [
            '    if ($usuario[\'nivel\'] === \'soporte\') {',
            '        $duenos = [];',
            '        $nodo_duenos = $nodo_app->adyacente(\'duenos\');',
            '        if ($nodo_duenos) {',
            '            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                $duenos[] = (string)$nombre_d;',
            '            }',
            '        }',
            '        $usuario[\'duenos\'] = $duenos;',
            '    }',
            '',
            '    // Fase 1 de seguridad (1.5piloto.84a): agregar el dueño',
            '    // acá adentro, para que el array esté completo ANTES de',
            '    // guardar la sesión PHP. Antes lo hacía el Enrutador',
            '    // después de _construir_respuesta_login, y la sesión PHP',
            '    // se guardaba sin el dueño.',
            '    if ($usuario[\'nivel\'] === \'terminal\') {',
            '        $nodo_dueno = $nodo_app->adyacente(\'dueno\');',
            '        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '    }',
            '',
            '    $token = crear_sesion($nombre_usuario);',
            '    $usuario[\'token_sesion\'] = $token;',
            '',
            '    // Fase 1 de seguridad: guardar la identidad en $_SESSION',
            '    // para que las próximas requests del mismo navegador tengan',
            '    // un punto de verdad server-side. La cookie PHPSESSID ya',
            '    // existe (session_start() en index.php). A partir de acá,',
            '    // ?imprimir=1 y los chequeos de permisos pueden confiar en',
            '    // esto.',
            '    guardar_sesion_php($usuario);',
            '',
            '    return $usuario;',
            '}',
        ],
    ],

    // ============================================================
    // Sesion.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Sesion: agregar use Entorno',
        'buscar' => [
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Configuracion\\Conf;',
            'include_once("./Configuracion/Configuracion.php");',
        ],
        'reemplazar' => [
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Configuracion\\Entorno;',
            'include_once("./Configuracion/Configuracion.php");',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Sesion: agregar helpers de sesion PHP',
        'buscar' => [
            '            if (in_array($nombre_usuario, $nombres_usuarios, true)) {',
            '                $nodo_creado = $nodo_sesion->adyacente(\'creado_en\');',
            '                $timestamp = $nodo_creado ? (int)$nodo_creado->dato() : 0;',
            '                $sesiones[] = [',
            '                    \'token\' => $token,',
            '                    \'usuario\' => $nombre_usuario,',
            '                    \'creado_en\' => $timestamp > 0 ? date(\'Y-m-d H:i:s\', $timestamp) : \'\',',
            '                ];',
            '            }',
            '        }',
            '        return $sesiones;',
            '    });',
            '}',
        ],
        'reemplazar' => [
            '            if (in_array($nombre_usuario, $nombres_usuarios, true)) {',
            '                $nodo_creado = $nodo_sesion->adyacente(\'creado_en\');',
            '                $timestamp = $nodo_creado ? (int)$nodo_creado->dato() : 0;',
            '                $sesiones[] = [',
            '                    \'token\' => $token,',
            '                    \'usuario\' => $nombre_usuario,',
            '                    \'creado_en\' => $timestamp > 0 ? date(\'Y-m-d H:i:s\', $timestamp) : \'\',',
            '                ];',
            '            }',
            '        }',
            '        return $sesiones;',
            '    });',
            '}',
            '',
            '// ============================================================',
            '// Fase 1 de seguridad (1.5piloto.84a): sesion PHP',
            '//',
            '// Guarda la identidad del usuario en $_SESSION, sobre la cookie',
            '// PHPSESSID que ya crea session_start() en index.php. Sirve como',
            '// punto de verdad server-side para las requests del mismo',
            '// navegador. El token en el grafo de credenciales (sesiones/',
            '// <token>) sigue siendo la fuente de verdad para invalidar',
            '// sesiones remotamente: si el token se borra, la proxima',
            '// sesiones/validar falla y limpia $_SESSION.',
            '// ============================================================',
            '',
            '/**',
            ' * Guarda la identidad del usuario en $_SESSION.',
            ' *',
            ' * Se llama desde:',
            ' *   - `_construir_respuesta_login` (login por codigo o',
            ' *     usuario+contrasena), despues de crear el token en el',
            ' *     grafo de credenciales.',
            ' *   - `sesiones/validar` (rehidratacion al recargar la pagina',
            ' *     con un token del localStorage).',
            ' *',
            ' * La cookie PHPSESSID viaja en cada request del navegador,',
            ' * asi que a partir de aca las requests del mismo navegador',
            ' * tienen identidad server-side.',
            ' *',
            ' * @param array $usuario Array con al menos nombre_usuario y',
            ' *                       nivel. Opcionalmente dueno (terminal),',
            ' *                       duenos (soporte), token_sesion.',
            ' * @return void',
            ' */',
            'function guardar_sesion_php(array $usuario): void {',
            '    if (session_status() !== PHP_SESSION_ACTIVE) return;',
            '    $prefijo = Entorno::prefijo_sesion();',
            '    $_SESSION[$prefijo . \'usuario\'] = [',
            '        \'nombre_usuario\' => (string)($usuario[\'nombre_usuario\'] ?? \'\'),',
            '        \'nivel\' => (string)($usuario[\'nivel\'] ?? \'\'),',
            '        \'dueno\' => (string)($usuario[\'dueno\'] ?? \'\'),',
            '        \'duenos\' => is_array($usuario[\'duenos\'] ?? null) ? $usuario[\'duenos\'] : [],',
            '        \'token_sesion\' => (string)($usuario[\'token_sesion\'] ?? \'\'),',
            '    ];',
            '    $_SESSION[$prefijo . \'ultima_actividad\'] = time();',
            '}',
            '',
            '/**',
            ' * Devuelve el usuario guardado en $_SESSION, o null si no hay.',
            ' *',
            ' * @return array|null',
            ' */',
            'function usuario_sesion_actual(): ?array {',
            '    if (session_status() !== PHP_SESSION_ACTIVE) return null;',
            '    $prefijo = Entorno::prefijo_sesion();',
            '    $u = $_SESSION[$prefijo . \'usuario\'] ?? null;',
            '    return is_array($u) ? $u : null;',
            '}',
            '',
            '/**',
            ' * Limpia la identidad del usuario en $_SESSION.',
            ' *',
            ' * Se llama desde:',
            ' *   - `sesiones/cerrar` cuando el token cerrado coincide con',
            ' *     el de la sesion PHP actual (logout local).',
            ' *   - `sesiones/validar` cuando el token enviado ya no es',
            ' *     valido (expirado o cerrado remotamente).',
            ' *',
            ' * No destruye la cookie PHPSESSID; solo limpia las claves',
            ' * propias de la app. La proxima request del mismo navegador',
            ' * arranca sin identidad y el JS pide login.',
            ' *',
            ' * @return void',
            ' */',
            'function limpiar_sesion_php(): void {',
            '    if (session_status() !== PHP_SESSION_ACTIVE) return;',
            '    $prefijo = Entorno::prefijo_sesion();',
            '    unset($_SESSION[$prefijo . \'usuario\']);',
            '    unset($_SESSION[$prefijo . \'ultima_actividad\']);',
            '}',
            '',
            '/**',
            ' * Devuelve el token de sesion guardado en $_SESSION, o null.',
            ' *',
            ' * Se usa en `sesiones/cerrar` para distinguir:',
            ' *   - logout local (el token cerrado es el propio);',
            ' *   - cierre remoto (dueno/admin cerrando el de otro, no',
            ' *     tocar la sesion PHP del solicitante).',
            ' *',
            ' * @return string|null',
            ' */',
            'function token_sesion_actual(): ?string {',
            '    $u = usuario_sesion_actual();',
            '    if (!$u) return null;',
            '    $t = (string)($u[\'token_sesion\'] ?? \'\');',
            '    return $t !== \'\' ? $t : null;',
            '}',
        ],
    ],

    // ============================================================
    // Enrutador.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: autenticar/verificar sin logica del dueno (ahora en _construir_respuesta_login)',
        'buscar' => [
            '                    if ($usuario) {',
            '                        // Si es terminal, incluir el nombre del dueño.',
            '                        // Fase B3.1: ID especial directo.',
            '                        if ($usuario[\'nivel\'] === \'terminal\') {',
            '                            $nodo_usuario = Nodo::nodo_por_id(\'us_\' . $usuario[\'nombre_usuario\']);',
            '                            $nodo_dueno = $nodo_usuario ? $nodo_usuario->adyacente(\'dueno\') : null;',
            '                            $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '                        }',
            '                        responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
            '                    } else {',
        ],
        'reemplazar' => [
            '                    if ($usuario) {',
            '                        // Fase 1 de seguridad (1.5piloto.84a): la',
            '                        // logica del dueno de terminal vive ahora',
            '                        // dentro de _construir_respuesta_login, para',
            '                        // que la sesion PHP se guarde con el array',
            '                        // completo.',
            '                        responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
            '                    } else {',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: sesiones/cerrar limpia sesion PHP en logout local',
        'buscar' => [
            '                if (cerrar_sesion($token)) {',
            '                    responder_json([\'exito\' => true]);',
            '                } else {',
            '                    responder_json([\'exito\' => false, \'error\' => \'Token no válido\']);',
            '                }',
        ],
        'reemplazar' => [
            '                // Fase 1 de seguridad (1.5piloto.84a): si el token',
            '                // cerrado es el de la sesion PHP actual, limpiar',
            '                // $_SESSION (logout local). Si es de otro (dueno',
            '                // cerrando terminal, admin cerrando un usuario),',
            '                // NO tocar la sesion del solicitante.',
            '                $token_propio = token_sesion_actual();',
            '                $es_logout_local = ($token_propio !== null && $token_propio === $token);',
            '',
            '                if (cerrar_sesion($token)) {',
            '                    if ($es_logout_local) {',
            '                        limpiar_sesion_php();',
            '                    }',
            '                    responder_json([\'exito\' => true]);',
            '                } else {',
            '                    responder_json([\'exito\' => false, \'error\' => \'Token no válido\']);',
            '                }',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: sesiones/validar rehidrata sesion PHP',
        'buscar' => [
            '            } elseif ($subaccion === \'validar\') {',
            '                $token = $post[\'token\'] ?? \'\';',
            '                $resultado = validar_token_sesion($token);',
            '                if ($resultado) {',
            '                    $nodo_usuario = $resultado[\'nodo\'];',
            '                    $nombre_usuario = $resultado[\'nombre_usuario\'];',
            '                    $nodo_nivel = $nodo_usuario->adyacente(\'nivel\');',
            '                    $nodo_nombre_real = $nodo_usuario->adyacente(\'nombre_real\');',
            '                    $usuario = [',
            '                        \'nombre_usuario\' => $nombre_usuario,',
            '                        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,',
            '                        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '                    ];',
            '                    // Si es terminal, incluir dueño',
            '                    if ($usuario[\'nivel\'] === \'terminal\') {',
            '                        $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '                        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '                    }',
            '                    // Si es soporte, incluir la lista de dueños asignados.',
            '                    if ($usuario[\'nivel\'] === \'soporte\') {',
            '                        $duenos = [];',
            '                        $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '                        if ($nodo_duenos) {',
            '                            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                                $duenos[] = (string)$nombre_d;',
            '                            }',
            '                        }',
            '                        $usuario[\'duenos\'] = $duenos;',
            '                    }',
            '                    responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
            '                } else {',
            '                    responder_json([\'exito\' => false, \'error\' => \'Sesión no válida\']);',
            '                }',
            '            }else {',
        ],
        'reemplazar' => [
            '            } elseif ($subaccion === \'validar\') {',
            '                $token = $post[\'token\'] ?? \'\';',
            '                $resultado = validar_token_sesion($token);',
            '                if ($resultado) {',
            '                    $nodo_usuario = $resultado[\'nodo\'];',
            '                    $nombre_usuario = $resultado[\'nombre_usuario\'];',
            '                    $nodo_nivel = $nodo_usuario->adyacente(\'nivel\');',
            '                    $nodo_nombre_real = $nodo_usuario->adyacente(\'nombre_real\');',
            '                    $usuario = [',
            '                        \'nombre_usuario\' => $nombre_usuario,',
            '                        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,',
            '                        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '                    ];',
            '                    // Si es terminal, incluir dueño',
            '                    if ($usuario[\'nivel\'] === \'terminal\') {',
            '                        $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '                        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '                    }',
            '                    // Si es soporte, incluir la lista de dueños asignados.',
            '                    if ($usuario[\'nivel\'] === \'soporte\') {',
            '                        $duenos = [];',
            '                        $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '                        if ($nodo_duenos) {',
            '                            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                                $duenos[] = (string)$nombre_d;',
            '                            }',
            '                        }',
            '                        $usuario[\'duenos\'] = $duenos;',
            '                    }',
            '                    // Fase 1 de seguridad (1.5piloto.84a): rehidratar',
            '                    // $_SESSION con el token validado. El usuario',
            '                    // recarga la pagina, el JS lee el token del',
            '                    // localStorage y manda este POST. Aca el backend',
            '                    // conecta la cookie PHPSESSID con la sesion del',
            '                    // grafo de credenciales. A partir de aca, las',
            '                    // proximas requests (impresion incluida) tienen',
            '                    // identidad server-side.',
            '                    $usuario[\'token_sesion\'] = $token;',
            '                    guardar_sesion_php($usuario);',
            '                    responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
            '                } else {',
            '                    // Token invalido o expirado: limpiar la sesion',
            '                    // PHP para que no quede una identidad vieja',
            '                    // colgada.',
            '                    limpiar_sesion_php();',
            '                    responder_json([\'exito\' => false, \'error\' => \'Sesión no válida\']);',
            '                }',
            '            }else {',
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