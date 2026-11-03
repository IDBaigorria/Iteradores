<?php
/**
 * Aplicador — Documentación Fase 2 de seguridad (impresión cerrada).
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
        'descripcion' => 'plan_actual §1: version y estado a v84d',
        'buscar' => [
            '**Última actualización:** v1.5piloto.84a + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fase 1 de seguridad aplicada (v84a):** sesión PHP con',
            'cookie PHPSESSID + `$_SESSION`; login, logout local y',
            'cierre remoto funcionando. Próxima fase: impresión',
            'cerrada. Ver §2 y §8).',
        ],
        'reemplazar' => [
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
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: version del piloto a 84d',
        'buscar' => [
            '- **Piloto:** `1.5piloto.84a` (validado en producción',
            '  hasta v83q; v84a agrega sesión PHP, sin cambios en',
            '  persistencia).',
        ],
        'reemplazar' => [
            '- **Piloto:** `1.5piloto.84d` (validado en producción',
            '  hasta v83q; v84a-v84d agregan sesión PHP e impresión',
            '  cerrada, sin cambios en persistencia).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §2: arco de Fase 2 al inicio',
        'buscar' => [
            '- **Fase 1 de seguridad: sesión PHP (v1.5piloto.84a).**',
            '  Login, logout local y cierre remoto funcionando con',
            '  `$_SESSION` sobre la cookie PHPSESSID. Nuevos helpers en',
            '  `Sesion.php`. `_construir_respuesta_login` guarda la',
            '  identidad completa (con `dueno` resuelto) en `$_SESSION`.',
            '  Sin cambios en `aplicacion.js`. Sin carga ni guardado',
            '  total nuevo. Ver §8.',
        ],
        'reemplazar' => [
            '- **Fase 2 de seguridad: impresión cerrada (v1.5piloto.84b-v84d).**',
            '  El bloque `?imprimir=1` valida `$_SESSION` y permisos',
            '  (`_puede_imprimir`) antes de cargar el grafo. Sin sesión o',
            '  sin permiso → 403 sin cargar. Carga siempre parcial (nunca',
            '  total): contextos de la sesión, con override por `dueno`',
            '  del GET. `dueno` se infiere desde la sesión si no viene',
            '  (dueño → él mismo; terminal → su dueño). `informe_ventas`',
            '  habilitado para terminal, con validación de',
            '  `tipo_ventas` + `nombre`. Sin cambios en `Impresion.php`,',
            '  ni en el frontend. Ver §8.3.',
            '',
            '- **Fase 1 de seguridad: sesión PHP (v1.5piloto.84a).**',
            '  Login, logout local y cierre remoto funcionando con',
            '  `$_SESSION` sobre la cookie PHPSESSID. Nuevos helpers en',
            '  `Sesion.php`. `_construir_respuesta_login` guarda la',
            '  identidad completa (con `dueno` resuelto) en `$_SESSION`.',
            '  Sin cambios en `aplicacion.js`. Sin carga ni guardado',
            '  total nuevo. Ver §8.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8: header de estado a v84d',
        'buscar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84a. La Fase 1 está aplicada',
            'y funcionando. Las Fases 2, 3 y 4 son el plan pendiente.',
        ],
        'reemplazar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84d. Las Fases 1 y 2 están',
            'aplicadas y funcionando. Las Fases 3 y 4 son el plan',
            'pendiente.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8.3: Fase 2 aplicada',
        'buscar' => [
            '### 8.3. Fase 2 — impresión cerrada (pendiente)',
            '',
            'Objetivo: cerrar el agujero de `?imprimir=1&tipo=...`.',
            'Hoy cualquiera con la URL ve datos sin sesión.',
            '',
            'Plan:',
            '',
            '- **`index.php`, bloque `?imprimir=1`**:',
            '  - Validar `$_SESSION`. Sin sesión → 403 sin cargar grafo.',
            '  - Validar permisos por `tipo`. Sin permiso → 403 sin cargar.',
            '  - Exigir `dueno` en el GET para los `tipo` que hoy caen',
            '    a carga total (los 6-7 helpers de B3.3c). Sin `dueno`',
            '    → 400. Esto **elimina** la carga total en impresión.',
            '- **`_contextos_para_operacion` Caso 2**: usar los',
            '  contextos del usuario de la sesión (no del `dueno` del',
            '  GET). Validar que el `dueno` del GET sea accesible',
            '  desde ese contexto.',
            '- **`Impresion.php`**: sin cambios. Solo que ahora el',
            '  bloque `?imprimir=1` ya validó sesión y permisos antes',
            '  de llamarlo.',
            '',
            '**Sin carga total nueva, sin guardado nuevo.**',
        ],
        'reemplazar' => [
            '### 8.3. Fase 2 — impresión cerrada (v1.5piloto.84b-v84d, aplicada)',
            '',
            'La fase se aplicó en tres iteraciones:',
            '',
            '- **v84b** (base):',
            '  - `index.php`: pre-check de `?imprimir=1` antes de cargar',
            '    el grafo. Sin sesión → 403. Sin permiso → 403.',
            '  - `Enrutador.php`: `_puede_imprimir($usuario_sesion, $tipo)`',
            '    valida tipo vs nivel. Admin/soporte todo; dueño los',
            '    tipos de su subgrafo; terminal los de su compartido.',
            '  - `Enrutador.php`: `_contextos_para_operacion` Caso 2',
            '    deja de devolver `null` (carga total) y pasa a',
            '    devolver contextos parciales según la sesión y el',
            '    `dueno` del GET.',
            '- **v84c** (fix inferencia de `dueno`): las URLs de',
            '  `ventas.js` y `pasajeros.js` no pasan `dueno`, así que',
            '  el dueño no podía imprimir sus pasajes (fallaba',
            '  `obtener_venta_por_id` con "Venta no encontrada" en',
            '  carga parcial). Fix: `index.php` infiere `dueno` desde',
            '  la sesión si no viene en el GET (dueño → él mismo;',
            '  terminal → su dueño). Admin/soporte sin inferencia',
            '  (deben pasarlo explícito). También en v84c: agregar',
            '  `declaracion_jurada` a `$tipos_terminal` (el frontend',
            '  le muestra el botón al terminal cuando el dueño lo',
            '  autoriza, pero el pre-check lo rechazaba).',
            '- **v84d** (informe_ventas para terminal): el botón',
            '  "Informe" del terminal quedaba bloqueado. Fix:',
            '  `_puede_imprimir` recibe `$get` y agrega `informe_ventas`',
            '  a `$tipos_terminal`, con validación específica:',
            '  - Terminal: `tipo_ventas=terminal` → `nombre` == terminal',
            '    de sesión. `tipo_ventas=dueno` → `nombre` == dueño del',
            '    terminal. Otros → 403.',
            '  - Dueño: `tipo_ventas=dueno` → `nombre` == dueño de',
            '    sesión. `tipo_ventas=terminal` → permitido (el contexto',
            '    filtra si la terminal no es suya).',
            '',
            '**Resultado:** el bloque `?imprimir=1` ya no es un endpoint',
            'público. Sin sesión → 403 sin cargar el grafo. Sin permiso',
            'sobre el tipo → 403 sin cargar. Carga siempre parcial',
            '(nunca total).',
            '',
            '**Comportamiento del botón "Informe" del terminal:** en la',
            'práctica el terminal solo ve sus propias ventas. Eso',
            'sucede porque el frontend manda `tipo_ventas=terminal`',
            '(y `_puede_imprimir` valida `nombre == terminal de',
            'sesión`). **Deuda latente**: si alguien construye a mano',
            'la URL `?imprimir=1&tipo=informe_ventas&tipo_ventas=dueno&nombre=<su_dueno>`',
            'desde el terminal, el backend lo permite y el informe',
            'muestra TODAS las ventas del dueño. Nadie lo explota hoy',
            'porque la UI no lo manda, pero es un leak. Endurecer en',
            'próxima iteración (restringir `informe_ventas` a',
            '`tipo_ventas=terminal` para terminal, o filtrar en',
            '`imprimir_informe_ventas`).',
            '',
            '**Trade-off del cierre remoto + impresión.** Si el dueño',
            'cierra la sesión de un terminal y el terminal intenta',
            'imprimir sin recargar la página, el pre-check pasa (el',
            'token sigue en `$_SESSION`) y la impresión funciona. Es',
            'el mismo trade-off de Fase 1 (el terminal se entera del',
            'cierre recién al recargar). Se endurece en Fase 3 con',
            'chequeo del token contra el grafo.',
            '',
            '**Sin cambios en `Impresion.php`, `aplicacion.js`, ni en',
            'las URLs del frontend.**',
            '',
            '**Sin carga total nueva, sin guardado nuevo.**',
        ],
    ],

    // ============================================================
    // prompt_piloto.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto §7: entrada v84b-v84d',
        'buscar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v84a** (Fase 1 de seguridad: sesión PHP). Login,',
        ],
        'reemplazar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v84b-v84d** (Fase 2 de seguridad: impresión cerrada).',
            '  El bloque `?imprimir=1` valida `$_SESSION` y permisos',
            '  (`_puede_imprimir`) antes de cargar el grafo. Carga',
            '  siempre parcial (nunca total). `dueno` se infiere desde',
            '  la sesión si no viene (v84c). `informe_ventas` habilitado',
            '  para terminal con validación de `tipo_ventas` + `nombre`',
            '  (v84d). `declaracion_jurada` agregado a `$tipos_terminal`',
            '  (v84c). Sin cambios en `Impresion.php`, ni en el',
            '  frontend. Deuda latente anotada en `plan_actual.md` §8.3',
            '  (URL armada a mano con `tipo_ventas=dueno` desde un',
            '  terminal).',
            '',
            '- **v84a** (Fase 1 de seguridad: sesión PHP). Login,',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: agregar §8.12 impresion cerrada',
        'buscar' => [
            'Ver `prompts/plan_actual.md` §8 para el plan completo',
            '(Fases 2, 3 y 4).',
            '',
            '---',
            '',
            '## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN',
        ],
        'reemplazar' => [
            'Ver `prompts/plan_actual.md` §8 para el plan completo',
            '(Fases 2, 3 y 4).',
            '',
            '### 8.12 Impresión cerrada (v1.5piloto.84b-v84d)',
            '',
            '**Problema.** Antes de v84b, las URLs `?imprimir=1&tipo=...`',
            'no validaban sesión ni permisos. Cualquiera con la URL veía',
            'datos de pasajeros (DNI, teléfonos, domicilios), montos,',
            'cancelaciones, etc. El plan de impresión era "la punta del',
            'iceberg" de un agujero mayor (POST confía en',
            '`nombre_solicitante` del cliente). Fase 2 cierra la punta;',
            'Fase 3 cierra la base.',
            '',
            '**Solución (v84b-v84d).** El bloque `?imprimir=1` corre un',
            'pre-check antes de cargar el grafo:',
            '',
            '1. Lee `usuario_sesion_actual()`. Sin sesión → 403.',
            '2. `_puede_imprimir($usuario, $tipo, $get)` valida tipo vs',
            '   nivel. Sin permiso → 403.',
            '3. Infiere `$_GET[\'dueno\']` desde la sesión si no viene',
            '   (dueño → él mismo; terminal → su dueño).',
            '4. Recién ahí `_contextos_para_operacion` arma el contexto',
            '   parcial y se carga el grafo.',
            '',
            '**`_contextos_para_operacion` Caso 2.** Ya no devuelve',
            '`null` (carga total). Devuelve `[\'us_<dueno>\']` (o',
            '`[\'us_<sesion>\']` si no hay `dueno`). Carga siempre',
            'parcial.',
            '',
            '**Reglas de `_puede_imprimir`:**',
            '- Admin / soporte: cualquier tipo.',
            '- Dueño: los tipos de su subgrafo (`pasajes`, `cupon`,',
            '  `pasaje_reserva`, `pasajes_actualizados`,',
            '  `informe_ventas`, `informe_rendicion`,',
            '  `informe_liquidacion`, `informe_cancelacion`,',
            '  `croquis_micro`, `planilla_pasajeros_micro`,',
            '  `declaracion_jurada`).',
            '- Terminal: los tipos de su compartido (`pasajes`, `cupon`,',
            '  `pasaje_reserva`, `pasajes_actualizados`,',
            '  `informe_cancelacion`, `informe_ventas`,',
            '  `croquis_micro`, `planilla_pasajeros_micro`,',
            '  `declaracion_jurada`).',
            '',
            '**Caso especial `informe_ventas`.** El usuario puede pedir',
            'dos cosas distintas (`tipo_ventas=dueno` para ver todas las',
            'ventas del dueño, o `tipo_ventas=terminal` para ver solo',
            'las de una terminal). Se restringe el `nombre`:',
            '- Dueño: `tipo_ventas=dueno` → `nombre == dueño_sesion`.',
            '  `tipo_ventas=terminal` → permitido (el contexto filtra',
            '  si la terminal no es suya).',
            '- Terminal: `tipo_ventas=terminal` → `nombre == terminal_sesion`.',
            '  `tipo_ventas=dueno` → `nombre == dueno_sesion`.',
            '  Otros → 403.',
            '',
            '**Comportamiento del botón "Informe" del terminal.** En la',
            'práctica el terminal solo ve sus propias ventas porque el',
            'frontend manda `tipo_ventas=terminal`. **Deuda latente**:',
            'si alguien construye la URL a mano con',
            '`tipo_ventas=dueno&nombre=<su_dueno>`, el backend lo',
            'permite y el informe muestra TODAS las ventas del dueño.',
            'Nadie lo explota hoy porque la UI no lo manda. Endurecer en',
            'próxima iteración (restringir `informe_ventas` a',
            '`tipo_ventas=terminal` para terminal, o filtrar en',
            '`imprimir_informe_ventas`).',
            '',
            '**Trade-off del cierre remoto + impresión.** Si el dueño',
            'cierra la sesión de un terminal y el terminal intenta',
            'imprimir sin recargar la página, el pre-check pasa (el',
            'token sigue en `$_SESSION`) y la impresión funciona. Es el',
            'mismo trade-off de Fase 1 (el terminal se entera del',
            'cierre recién al recargar). Se endurece en Fase 3 con',
            'chequeo del token contra el grafo.',
            '',
            '**Sin cambios en `Impresion.php`, `aplicacion.js`, ni en',
            'las URLs del frontend.**',
            '',
            'Ver `prompts/plan_actual.md` §8.3 para el detalle completo.',
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