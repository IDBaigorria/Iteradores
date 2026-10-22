<?php
/**
 * Aplicador — v81m: cierre del arco B3.3c + rediseño soportes
 * plural. Solo documentación. (v2: sin el bloque de index.php,
 * porque `auditar_soporte` no existe.)
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81m';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // prompt_seguridad.md §1: encabezado de estado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md: versión del estado al cierre',
        'buscar' => [
            '**Estado al cierre de v1.5piloto.81i:**',
        ],
        'reemplazar' => [
            '**Estado al cierre de v1.5piloto.81m:**',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md §3.4
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md §3.4: soportes contenedor plural',
        'buscar' => [
            '### 3.4 Enlaces de permiso',
            '',
            'Van directo en la raíz del usuario, no en un contenedor:',
            '',
            '- `dueno` en el terminal → apunta al `compartido_con_us_termX`',
            '  del dueño.',
            '- `soporte` en el dueño → apunta al `publico` del soporte.',
            '- `duenos` en el soporte → apunta al nodo real de cada dueño',
            '  asignado. **Este es el enlace que abre la puerta del soporte',
            '  hacia sus dueños.**',
            '',
            '**Nota asimétrica:** `soporte → dueno` apunta al nodo real (el',
            'soporte ve todo del dueño); `dueno → soporte` apunta al',
            '`publico` (el dueño solo ve datos públicos del soporte). La',
            'asimetría es intencional.',
        ],
        'reemplazar' => [
            '### 3.4 Enlaces de permiso',
            '',
            'Van directo en la raíz del usuario, no en un contenedor:',
            '',
            '- `dueno` en el terminal → apunta al `compartido_con_us_termX`',
            '  del dueño.',
            '- `soportes` en el dueño → contenedor (dato vacío) con los',
            '  `publico` de cada soporte asignado',
            '  (`soportes → publico_<soporte1>, publico_<soporte2>, ...`).',
            '  **Permite varios soportes por dueño.** Reemplaza al enlace',
            '  singular `soporte` desde v1.5piloto.81l. El nombre plural',
            '  evita la colisión con el nodo real del soporte.',
            '- `duenos` en el soporte → apunta al nodo real de cada dueño',
            '  asignado. **Este es el enlace que abre la puerta del soporte',
            '  hacia sus dueños.**',
            '',
            '**Nota asimétrica:** `soporte → duenos → us_duenoY` apunta al',
            'nodo real (el soporte ve todo del dueño);',
            '`dueno → soportes → publico_soporteX` apunta al `publico` (el',
            'dueño solo ve datos públicos del soporte). La asimetría es',
            'intencional.',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md §4.3
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md §4.3: regla del camino mínimo',
        'buscar' => [
            '**Mal:** `dueno → soporte → us_soporteX` (apunta al nodo real',
            'del soporte, expone `duenos` y desde ahí a otros dueños).',
            '',
            '**Bien:** `dueno → soporte → publico_soporteX` (apunta al',
            '`publico` que solo tiene `nivel`, `nombre_real`, `email`).',
        ],
        'reemplazar' => [
            '**Mal:** `dueno → soporte → us_soporteX` (apunta al nodo real',
            'del soporte, expone `duenos` y desde ahí a otros dueños).',
            '',
            '**Bien:** `dueno → soportes → publico_soporteX` (contenedor',
            '`soportes` con el `publico` de cada soporte como hijo; el',
            '`publico` solo tiene `nivel`, `nombre_real`, `email`).',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md §5.5
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md §5.5: enlaces de permiso explícitos',
        'buscar' => [
            '- Soporte → dueño: `soporte → duenos → us_duenoY` (apunta al',
            '  nodo real, porque el soporte ve todo del dueño).',
            '- Dueño → soporte: `dueno → soporte → publico_soporteX` (apunta',
            '  al público, porque el dueño solo ve datos públicos).',
        ],
        'reemplazar' => [
            '- Soporte → dueño: `soporte → duenos → us_duenoY` (apunta al',
            '  nodo real, porque el soporte ve todo del dueño).',
            '- Dueño → soporte(s): `dueno → soportes → publico_soporteX`',
            '  (contenedor, admite varios soportes; apunta al `publico`,',
            '  porque el dueño solo ve datos públicos).',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md §5.11: agregar lección 4
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md §5.11: lección 4 (orden de llamadas)',
        'buscar' => [
            '**3. Un bug en un helper sugiere el mismo bug en sus',
            'hermanos.** Cuando se detecta un patrón problemático',
            '(por ejemplo, un filtro mal escrito), auditar los',
            'demás helpers que usan el mismo patrón. En v79b, el',
            'fix del filtro en `Venta.php` reveló el mismo bug',
            'en `reparar_arboles.php`.',
        ],
        'reemplazar' => [
            '**3. Un bug en un helper sugiere el mismo bug en sus',
            'hermanos.** Cuando se detecta un patrón problemático',
            '(por ejemplo, un filtro mal escrito), auditar los',
            'demás helpers que usan el mismo patrón. En v79b, el',
            'fix del filtro en `Venta.php` reveló el mismo bug',
            'en `reparar_arboles.php`.',
            '',
            '**4. El orden de las llamadas importa cuando una crea',
            'contenedores que la otra consume.** Caso v81l:',
            '`_asignar_duenos_a_soporte` se llamaba antes de',
            '`_asegurar_niveles_usuario`. El `publico` del soporte',
            'todavía no existía, y el enlace `dueno → soporte` caía',
            'al fallback (nodo real del soporte), causando la fuga',
            'cruzada entre dueños. Fix: reordenar (niveles primero,',
            'después asignar). Regla: antes de encadenar dos',
            'operaciones sobre el mismo nodo, verificar cuál crea',
            'los nodos que la otra consume, y ordenarlas en',
            'consecuencia.',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md §8.6: cierre de B3.3c
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad.md §8.6: cierre de B3.3c',
        'buscar' => [
            '**Consecuencia:** con carga parcial, las 3 restantes',
            'devuelven null o vacío si el solicitante no tiene `usuarios`',
            'en memoria (dueño y soporte). Se resuelven cuando aparezca',
            'dolor real o cuando implementemos iteradores con ID especial.',
        ],
        'reemplazar' => [
            '**Consecuencia:** con carga parcial, las 3 restantes',
            'devuelven null o vacío si el solicitante no tiene `usuarios`',
            'en memoria (dueño y soporte). Se resuelven cuando aparezca',
            'dolor real o cuando implementemos iteradores con ID especial.',
            '',
            '**Cierre de B3.3c (v1.5piloto.81j-81l).** Las 3 funciones',
            'pendientes quedaron resueltas:',
            '',
            '- `obtener_cancelacion_por_id`: acepta `?string $nombre_dueno`',
            '  y `?string $nombre_terminal` (v81j).',
            '- `obtener_pasajero_por_dni`: la rama sin contexto navega',
            '  `us_<dueño>` directo (v81j). Fix de privacidad: antes',
            '  iteraba sobre todos los dueños y mezclaba ventas de',
            '  distintos dueños cuando dos pasajeros compartían DNI.',
            '- `_buscar_venta_por_id`: fallback defensivo, no aplica en la',
            '  práctica (todos los llamadores del Enrutador pasan dueño o',
            '  terminal). Pendiente anotado: `usuarios` como contenedor de',
            '  nombres, para eliminar el fallback.',
            '',
            'Con eso, la Fase B del modelo topológico quedó completa.',
            '',
            '**Bug del frontend de ventas.js (v81k).** `ventas.js` no',
            'mandaba `nombre_dueno` en 4 fetch (`ventas/obtener`,',
            '`ventas/pagar_cupon`, `ventas/info_cancelacion`,',
            '`ventas/cancelar`). Mismo patrón que el bug v81i de',
            'rendiciones/liquidaciones. Nuevo helper',
            '`_nombre_dueno_para_peticiones_vendidos()` en el frontend.',
            '',
            '**Rediseño `soportes` plural (v81l).** El enlace',
            '`dueno → soporte → publico` singular se reemplazó por un',
            'contenedor `dueno → soportes → publico_<soporte1>, ...`.',
            'Causa raíz del fallback al nodo real: en `agregar_usuario`,',
            '`_asignar_duenos_a_soporte` corría antes de',
            '`_asegurar_niveles_usuario`, y el `publico` no existía',
            'todavía. Ver §5.11 lección 4.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md §4.2: agregar `soportes`
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto.md §4.2: agregar soportes al nodo usuario',
        'buscar' => [
            '- `dueno` → enlace directo al Nodo Usuario dueño (solo terminal).',
            '- `terminales` → contenedor (solo dueño): enlaces por nombre de terminal.',
        ],
        'reemplazar' => [
            '- `dueno` → enlace directo al Nodo Usuario dueño (solo terminal).',
            '- `soportes` → contenedor (solo dueño): los `publico` de cada',
            '  soporte asignado. Permite varios soportes por dueño.',
            '  Rediseño v1.5piloto.81l. Reemplaza al enlace singular',
            '  `soporte`.',
            '- `terminales` → contenedor (solo dueño): enlaces por nombre de terminal.',
        ],
    ],

    // ============================================================
    // plan_actual.md §1: última actualización
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §1: última actualización a v81m',
        'buscar' => [
            '**Última actualización:** v1.5piloto.81i (cierre del arco',
            'de los 4 módulos menores).',
            '',
            'Empresa.php y Vehiculo.php ya estaban adaptados',
            '(v76u-v77v); Liquidacion.php y Rendicion.php se adaptaron',
            'en v81h. El fix v81i (el frontend no mandaba',
            '`nombre_dueno` en `rendiciones/obtener`,',
            '`rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`)',
            'dejó la verificación manual y las 3 pruebas del plugin',
            'en verde. B3.3c reducido a 3 helpers (antes el texto',
            'decía "7", era un typo: eran 6; se adaptaron 3).',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.81m (cierre de B3.3c',
            '+ rediseño soportes plural).',
            '',
            'En v81j se adaptaron los 3 helpers de B3.3c. En v81k se',
            'arregló el frontend de ventas.js (no mandaba `nombre_dueno`',
            'en 4 fetch, mismo patrón que el bug v81i de rendiciones).',
            'En v81l se rediseñó `soporte` singular → `soportes`',
            'contenedor plural, y se reordenó `agregar_usuario` para que',
            '`_asegurar_niveles_usuario` corra antes de',
            '`_asignar_duenos_a_soporte` (causa raíz del enlace al nodo',
            'real del soporte). En v81m se actualizaron los prompts.',
            '**Con B3.3c cerrado, la Fase B del modelo topológico quedó',
            'completa.**',
        ],
    ],

    // ============================================================
    // plan_actual.md §2: agregar arco cerrado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §2: nuevo arco cerrado B3.3c',
        'buscar' => [
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **Visualizador de grafo (v80n-v81g).** Nueva sub-pestaña',
        ],
        'reemplazar' => [
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **B3.3c — 3 helpers que iteraban `usuarios` (v81j-v81m).**',
            '  `obtener_cancelacion_por_id` acepta dueño/terminal.',
            '  `obtener_pasajero_por_dni` navega `us_<dueño>` directo',
            '  (fix de privacidad). Frontend de `ventas.js` manda',
            '  `nombre_dueno` en 4 fetch (v81k). Rediseño `soporte`',
            '  singular → `soportes` contenedor plural (v81l). **Fase B',
            '  del modelo topológico completa.** Detalle en',
            '  `prompt_seguridad.md` §8.6.',
            '- **Visualizador de grafo (v80n-v81g).** Nueva sub-pestaña',
        ],
    ],

    // ============================================================
    // plan_actual.md §3: decisión en vigor sobre soportes
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §3: decisión rediseño soportes',
        'buscar' => [
            '- **La seguridad emerge de la topología.** El bitmask lo',
            '  propaga el framework, no la app. La app nunca asigna bits',
            '  a mano.',
        ],
        'reemplazar' => [
            '- **La seguridad emerge de la topología.** El bitmask lo',
            '  propaga el framework, no la app. La app nunca asigna bits',
            '  a mano.',
            '- **Rediseño `soportes` plural (v1.5piloto.81l).** El dueño',
            '  tiene un contenedor `soportes` (dato vacío) con los',
            '  `publico` de cada soporte asignado como hijos. Reemplaza',
            '  al enlace singular `soporte`. Permite varios soportes por',
            '  dueño. El orden en `agregar_usuario` importa:',
            '  `_asegurar_niveles_usuario` antes de',
            '  `_asignar_duenos_a_soporte`.',
        ],
    ],

    // ============================================================
    // plan_actual.md §4.1: B3.3c cerrado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §4.1: cerrar B3.3c en pendientes',
        'buscar' => [
            '- **B3.3c restante (3 helpers).** `Venta.php`:',
            '  `_buscar_venta_por_id` (fallback) y',
            '  `obtener_cancelacion_por_id`. `Pasajero.php`:',
            '  `obtener_pasajero_por_dni` (rama del historial de',
            '  ventas). Postergado hasta que aparezca dolor real o',
            '  hasta que el framework tenga iteradores con ID especial.',
        ],
        'reemplazar' => [
            '- [x] **B3.3c — 3 helpers que iteraban `usuarios`** —',
            '  CERRADO (v81j + v81k + v81l, ver §2). `_buscar_venta_por_id`',
            '  queda como fallback defensivo (no aplica en la práctica);',
            '  pendiente anotado para eliminar cuando `usuarios` sea',
            '  contenedor de nombres. `obtener_cancelacion_por_id` y',
            '  `obtener_pasajero_por_dni` adaptados al patrón',
            '  `?string $nombre_dueno`. Rediseño `soporte` → `soportes`',
            '  contenedor plural en v81l.',
        ],
    ],

    // ============================================================
    // plan_actual.md §5.1: tabla de fases
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §5.1: marcar B3.3c cerrado en tabla',
        'buscar' => [
            '| **B3.3c** — 3 helpers que iteran `usuarios` | `_buscar_venta_por_id` (fallback), `obtener_cancelacion_por_id`, `obtener_pasajero_por_dni` (rama historial). | **Pendiente — próximo arco** |',
        ],
        'reemplazar' => [
            '| **B3.3c** — 3 helpers que iteran `usuarios` | `_buscar_venta_por_id` (fallback defensivo), `obtener_cancelacion_por_id`, `obtener_pasajero_por_dni` (rama historial). | ✅ v81j-v81l |',
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