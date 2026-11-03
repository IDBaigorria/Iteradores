<?php
/**
 * Aplicador — Documentación de Fase 3 de seguridad (v84g).
 *
 * Solo toca prompts. Sin cambios de código, sin bumps.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // plan_actual.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: estado y version a v84g',
        'buscar' => [
            '**Última actualización:** v1.5piloto.84d + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fases 1 y 2 de seguridad aplicadas (v84a-v84d):**',
            'sesión PHP con cookie PHPSESSID + `$_SESSION`, y',
            'endpoint de impresión cerrado (sesión + permisos +',
            'inferencia de `dueno` desde la sesión). Próxima fase:',
            'validación en POST. Ver §2 y §8).',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.84g + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fases 1, 2 y 3 de seguridad aplicadas (v84a-v84g):**',
            'sesión PHP con cookie PHPSESSID + `$_SESSION`,',
            'impresión cerrada (sesión + permisos), POST valida',
            'sesión server-side, chequeo de token con TTL 30s',
            '(excluyendo polling), chequeo pre-escritura. Fase 4',
            '(tercer grafo de identidad) queda como deuda riesgosa.',
            'Ver §2 y §8).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: version piloto a v84g',
        'buscar' => [
            '- **Piloto:** `1.5piloto.84d` (validado en producción',
            '  hasta v83q; v84a-v84d agregan sesión PHP e impresión',
            '  cerrada, sin cambios en persistencia).',
        ],
        'reemplazar' => [
            '- **Piloto:** `1.5piloto.84g` (validado en producción',
            '  hasta v83q; v84a-v84g agregan sesión PHP, impresión',
            '  cerrada y validación server-side de sesión, sin',
            '  cambios en persistencia).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §2: arcos de Fases 3, 3b, 3c al inicio',
        'buscar' => [
            '- **Fase 2 de seguridad: impresión cerrada (v1.5piloto.84b-v84d).**',
            '  El bloque `?imprimir=1` valida `$_SESSION` y permisos',
        ],
        'reemplazar' => [
            '- **Fase 3c de seguridad: chequeo pre-escritura (v84g).**',
            '  Antes de procesar acciones de escritura (lista blanca',
            '  en `_es_accion_escritura`), verificar el token SIEMPRE,',
            '  sin importar el TTL. `guardar_ambos` también chequea',
            '  (defensa en profundidad para flujos que no pasan por',
            '  el enrutador). Cierra la ventana entre el cierre',
            '  remoto y la próxima detección para escrituras. Ver §8.4c.',
            '',
            '- **Fase 3b de seguridad: chequeo de token con TTL (v84f).**',
            '  Chequeo periódico del token con TTL 30s. El polling',
            '  (`viajes/estado_asientos`) no dispara chequeo: usuario',
            '  inactivo con la app abierta = 0 hits extra. La sesión',
            '  NO se cierra por inactividad. Ver §8.4b.',
            '',
            '- **Fase 3 de seguridad: POST valida sesión server-side (v84e).**',
            '  El enrutador toma `nombre_solicitante` de `$_SESSION`,',
            '  no del POST. Leak latente de `informe_ventas` cerrado',
            '  (terminal solo ve sus propias ventas). Endpoints',
            '  temporales `?diag_persistencia=1` y',
            '  `?diagnostico_cancelaciones=1` eliminados. Ver §8.4.',
            '',
            '- **Fase 2 de seguridad: impresión cerrada (v1.5piloto.84b-v84d).**',
            '  El bloque `?imprimir=1` valida `$_SESSION` y permisos',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8: header de estado',
        'buscar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84d. Las Fases 1 y 2 están',
            'aplicadas y funcionando. Las Fases 3 y 4 son el plan',
            'pendiente.',
        ],
        'reemplazar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84g. Las Fases 1, 2 y 3',
            'están aplicadas y funcionando. La Fase 4 (tercer grafo',
            'de identidad) queda como deuda riesgosa, diferida por',
            'decisión consciente.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8.4: marcar Fase 3 aplicada',
        'buscar' => [
            '### 8.4. Fase 3 — validación en POST (pendiente)',
            '',
            'Objetivo: cerrar el agujero de "el enrutador confía en',
            '`nombre_solicitante` del cliente".',
            '',
            '- **Enrutador**: derivar `nombre_solicitante` de',
            '  `$_SESSION`, no del POST. El POST puede seguir',
            '  mandándolo (compatibilidad), pero el backend lo ignora.',
            '- **Todos los endpoints POST** validan sesión y permisos',
            '  contra el `nombre_solicitante` de la sesión.',
            '- **Chequeo de cierre remoto en cada request** (opcional):',
            '  comparar el token de `$_SESSION` contra el grafo',
            '  (`sesiones/<token>`). Costo: una carga parcial de',
            '  `[\'sesiones\']` por request (~100 nodos). Cacheable',
            '  con un TTL corto.',
        ],
        'reemplazar' => [
            '### 8.4. Fase 3 — validación en POST (v84e, aplicada)',
            '',
            '**Resultado.** El enrutador toma `nombre_solicitante` de',
            '`$_SESSION`, no del POST. Las acciones no públicas sin',
            'sesión devuelven 401 antes de cargar el grafo. El leak',
            'latente de `informe_ventas` (terminal podía ver ventas',
            'del dueño armando la URL a mano) también se cerró:',
            'terminal solo puede `tipo_ventas=terminal` con `nombre`',
            '== terminal de sesión. Endpoints temporales',
            '`?diag_persistencia=1` y `?diagnostico_cancelaciones=1`',
            'eliminados. Detalle en `prompt_seguridad.md` §8.13.',
            '',
            '**Plan original (referencia histórica):**',
            '',
            '- **Enrutador**: derivar `nombre_solicitante` de',
            '  `$_SESSION`, no del POST. El POST puede seguir',
            '  mandándolo (compatibilidad), pero el backend lo ignora.',
            '- **Todos los endpoints POST** validan sesión y permisos',
            '  contra el `nombre_solicitante` de la sesión.',
            '- **Chequeo de cierre remoto en cada request** (opcional):',
            '  comparar el token de `$_SESSION` contra el grafo',
            '  (`sesiones/<token>`). Costo: una carga parcial de',
            '  `[\'sesiones\']` por request (~100 nodos). Cacheable',
            '  con un TTL corto.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §8.4b y §8.4c antes de §8.5',
        'buscar' => [
            '**Deuda asociada**: el login por código hoy es O(N) con',
            '`password_verify` por cada usuario. Solución: índice',
            'HMAC determinístico (`HMAC(secret, codigo) → us_<nombre>`).',
            '',
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente)',
        ],
        'reemplazar' => [
            '**Deuda asociada**: el login por código hoy es O(N) con',
            '`password_verify` por cada usuario. Solución: índice',
            'HMAC determinístico (`HMAC(secret, codigo) → us_<nombre>`).',
            'Ver §8.5 (Fase 4).',
            '',
            '### 8.4b. Fase 3b — chequeo de token con TTL (v84f, aplicada)',
            '',
            '**Resultado.** Chequeo periódico del token contra el grafo',
            'de credenciales. TTL de 30s: como máximo 1 chequeo cada',
            '30s por usuario activo. El polling del sistema',
            '(`viajes/estado_asientos`) no dispara chequeo: usuario',
            'inactivo con la app abierta = 0 hits extra al hosting.',
            '**La sesión NO se cierra por inactividad**: vive hasta',
            'logout local, cierre remoto, o si el token dejó de estar',
            'en el grafo. Detalle en `prompt_seguridad.md` §8.13.',
            '',
            '### 8.4c. Fase 3c — chequeo pre-escritura (v84g, aplicada)',
            '',
            '**Resultado.** Las acciones de escritura (lista blanca en',
            '`_es_accion_escritura`) chequean el token SIEMPRE, sin',
            'importar el TTL. Cierra la ventana entre el cierre remoto',
            'y la próxima detección para escrituras. Además,',
            '`guardar_ambos` chequea el token antes de guardar',
            '(defensa en profundidad para flujos que no pasan por el',
            'enrutador: comandos, migraciones). Sin sesión no chequea',
            '(bootstrap, migraciones CLI). Detalle en',
            '`prompt_seguridad.md` §8.13.',
            '',
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente, riesgosa)',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8.5: marcar como riesgosa',
        'buscar' => [
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente, riesgosa)',
            '',
            'Objetivo: sacar el esqueleto `usuarios` de la app y de',
            'credenciales. Hoy `usuarios → us_<nombre>` está duplicado',
            'en ambos grafos, y al admin/soporte la carga "parcial"',
            'le trae todo (desde `usuarios` se alcanza a cada',
            '`us_<nombre>` con su subgrafo).',
        ],
        'reemplazar' => [
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente, riesgosa)',
            '',
            '**Estado.** Diferida por decisión consciente. Requiere una',
            'migración riesgosa (mover datos entre grafos, agregar',
            '`indices/codigos_hmac`, tocar el login). Con la app en',
            'producción andando, no es momento. Se retoma cuando haya',
            'una ventana de mantenimiento.',
            '',
            'Objetivo: sacar el esqueleto `usuarios` de la app y de',
            'credenciales. Hoy `usuarios → us_<nombre>` está duplicado',
            'en ambos grafos, y al admin/soporte la carga "parcial"',
            'le trae todo (desde `usuarios` se alcanza a cada',
            '`us_<nombre>` con su subgrafo).',
        ],
    ],

    // ============================================================
    // prompt_piloto.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto §7: entradas v84e-v84g',
        'buscar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v84b-v84d** (Fase 2 de seguridad: impresión cerrada).',
        ],
        'reemplazar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v84e-v84g** (Fase 3 de seguridad: POST server-side,',
            '  chequeo de token, chequeo pre-escritura).',
            '  - **v84e**: el enrutador toma `nombre_solicitante` de',
            '    `$_SESSION`, no del POST. Pre-check 401 antes de',
            '    cargar el grafo. Leak latente de `informe_ventas`',
            '    cerrado. Endpoints temporales `?diag_persistencia=1`',
            '    y `?diagnostico_cancelaciones=1` eliminados.',
            '  - **v84f**: chequeo de token con TTL 30s. El polling',
            '    (`viajes/estado_asientos`) no dispara chequeo. La',
            '    sesión NO se cierra por inactividad.',
            '  - **v84g**: chequeo pre-escritura, siempre. `guardar_ambos`',
            '    también chequea.',
            '',
            '- **v84b-v84d** (Fase 2 de seguridad: impresión cerrada).',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md — agregar §8.13
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: agregar §8.13',
        'buscar' => [
            'Ver `prompts/plan_actual.md` §8.3 para el detalle completo.',
            '',
            '---',
            '',
            '## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN',
        ],
        'reemplazar' => [
            'Ver `prompts/plan_actual.md` §8.3 para el detalle completo.',
            '',
            '### 8.13 Chequeo server-side y pre-escritura (v84e-v84g)',
            '',
            '**Problema.** Hasta v84d, el enrutador confiaba en el',
            '`nombre_solicitante` del POST, y el cierre remoto de',
            'sesión (dueño cierra terminal) no se detectaba hasta el',
            'próximo page load. La ventana entre el cierre y la',
            'detección podía ser de horas si el terminal quedaba',
            'abierto.',
            '',
            '**Solución en tres capas.**',
            '',
            '**Capa 1 — POST valida sesión server-side (v84e).**',
            '- `_es_accion_publica(accion)`: `autenticar/verificar`,',
            '  `administrador/verificar`, `sesiones/validar`,',
            '  `entorno/info`. El resto requiere sesión.',
            '- `enrutar_peticion_post`: para acciones no públicas, el',
            '  `nombre_solicitante` se toma de `$_SESSION`, no del POST.',
            '- `index.php`: pre-check 401 antes de cargar el grafo. Sin',
            '  sesión → 401 sin cargar.',
            '- Leak latente cerrado: el terminal ya no puede imprimir',
            '  `informe_ventas` con `tipo_ventas=dueno`. Solo',
            '  `tipo_ventas=terminal` con `nombre` == terminal de sesión.',
            '',
            '**Capa 2 — chequeo de token con TTL 30s (v84f).**',
            '- `_es_accion_polling(accion)`: `viajes/estado_asientos`.',
            '  El polling del sistema no cuenta como actividad ni',
            '  dispara chequeo.',
            '- En `enrutar_peticion_post`, para acciones no públicas y',
            '  no polling, si `ultima_validacion_token > 30s`, verificar',
            '  el token contra el grafo. Si falla, 401.',
            '- **La sesión NO se cierra por inactividad.** Vive hasta',
            '  logout local, cierre remoto, o si el token dejó de estar',
            '  en el grafo.',
            '- Usuario inactivo con la app abierta (solo polling) = 0',
            '  hits extra al hosting.',
            '- Usuario activo: chequeo cada 30s como máximo.',
            '',
            '**Capa 3 — chequeo pre-escritura (v84g).**',
            '- `_es_accion_escritura(accion)`: lista blanca de acciones',
            '  que modifican el grafo. `sesiones/cerrar` queda fuera',
            '  (ya valida `_puede_cerrar_sesion`).',
            '- Para acciones de escritura, verificar el token SIEMPRE,',
            '  sin importar el TTL. Cierra la ventana entre el cierre',
            '  remoto y la próxima detección para escrituras.',
            '- `guardar_ambos` también chequea el token antes de',
            '  guardar (defensa en profundidad para flujos que no pasan',
            '  por el enrutador: comandos, migraciones). Sin sesión no',
            '  chequea (bootstrap, migraciones CLI).',
            '',
            '**Trade-offs aceptados.**',
            '- El polling no dispara chequeo: usuario inactivo con la',
            '  app abierta = 0 hits. Bien para hostings con límite de',
            '  hits.',
            '- Escrituras tienen ventana 0: cualquier click que',
            '  modifique el grafo detecta el cierre remoto.',
            '- Lecturas tienen ventana 30s: molesto pero tolerable',
            '  (leer sin token no es grave).',
            '',
            '**Lo que NO se cierra.**',
            '- Fase 4 (tercer grafo de identidad + HMAC): riesgosa,',
            '  diferida. La app en producción anda bien, no es momento',
            '  de migrar.',
            '',
            'Ver `prompts/plan_actual.md` §8.4-8.4c y §8.5 para el',
            'detalle completo.',
            '',
            '---',
            '',
            '## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN',
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