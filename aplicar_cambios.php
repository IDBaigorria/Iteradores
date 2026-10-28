<?php
/**
 * Aplicador — Fase 0 del plan de diagnóstico del bug del guardado
 * parcial (§4.7). Solo documentación. No toca código.
 *
 * Cambios:
 * 1. plan_actual.md: header §1, congelar §4.5, agregar §4.7
 *    (bug crítico + plan de 6 fases), renombrar deuda técnica
 *    a §4.8, actualizar "Estado del proyecto al cierre".
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.83a';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Header de §1
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'Header §1: versión 83a + bug §4.7',
    'buscar' => [
        '**Última actualización:** v1.5piloto.82i (piso en los',
        'flujos que faltaban: cambiar de asiento desde Clientes,',
        'impresión de pasaje_reserva y croquis impreso del micro.',
        'Ver §2 y §4.5).',
        '',
        '**Antes (v1.5piloto.82d):** cierre del arco de cancelaciones en',
        'compartidos + bug §4.6 de persistencia silenciosa en hostings',
        'compartidos.',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83a (Fase 0 del',
        'diagnóstico del bug crítico §4.7: corrupción del grafo',
        'por guardado parcial. Solo documentación. No toca código).',
        '',
        '**Antes (v1.5piloto.82i):** piso en los flujos que faltaban',
        '(cambiar de asiento desde Clientes, impresión de',
        'pasaje_reserva y croquis impreso del micro). Ver §2.',
        '',
        '**Antes (v1.5piloto.82d):** cierre del arco de cancelaciones en',
        'compartidos + bug §4.6 de persistencia silenciosa en hostings',
        'compartidos.',
    ],
],

// ============================================================
// 2. §4.5: congelar el arco de migración
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => '§4.5: pausar el arco de migración por el bug §4.7',
    'buscar' => [
        '**Arco en curso: migración de pasajeros entre micros.**',
        '',
        'Plan completo en `prompts/plan_migrar_micro.md`.',
        '',
        '- **Tanda 1 (backend): cerrada en `1.5piloto.82e`.**',
        '  `migrar_pasajeros_de_micro` en',
        '  `Aplicacion/Viajes/ViajeMicros.php` + subacción',
        '  `viajes/migrar_pasajeros` en `Aplicacion/Enrutador.php`.',
        '- **Tanda 2 (frontend): cerrada en `1.5piloto.82f`.**',
        '  Botón "Migrar pasajeros a..." en la tarjeta del micro (solo',
        '  visible si el micro tiene asientos ocupados y el usuario',
        '  no es terminal) + modal apilado con select de micros',
        '  vacíos, aviso de layouts distintos, confirm() nativo y',
        '  refresco post-migración (cierra o refresca el panel de',
        '  pasaje según qué micro estaba abierto).',
        '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
        '  + actualización de `aplicacion_POST.php`,',
        '  `prompt_piloto.md` §5.8 y §4.9, y este archivo.',
    ],
    'reemplazar' => [
        '**Arco en curso: migración de pasajeros entre micros.**',
        '**ESTADO: EN PAUSA** hasta que el bug del guardado',
        'parcial (§4.7) esté diagnosticado y blindado.',
        '',
        'Plan completo en `prompts/plan_migrar_micro.md`.',
        '',
        '- **Tanda 1 (backend): cerrada en `1.5piloto.82e`.**',
        '  `migrar_pasajeros_de_micro` en',
        '  `Aplicacion/Viajes/ViajeMicros.php` + subacción',
        '  `viajes/migrar_pasajeros` en `Aplicacion/Enrutador.php`.',
        '  **Aplicada en producción antes de conocer el bug',
        '  §4.7.** No fue la causa del bug, pero la secuencia',
        '  agregar micro → migrar → eliminar micro viejo → pagar',
        '  cupón lo disparó. La base se restauró desde backup y',
        '  se aplicaron las reparaciones idempotentes.',
        '- **Tanda 2 (frontend): cerrada en `1.5piloto.82f`.**',
        '  Botón "Migrar pasajeros a..." en la tarjeta del micro (solo',
        '  visible si el micro tiene asientos ocupados y el usuario',
        '  no es terminal) + modal apilado con select de micros',
        '  vacíos, aviso de layouts distintos, confirm() nativo y',
        '  refresco post-migración (cierra o refresca el panel de',
        '  pasaje según qué micro estaba abierto).',
        '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
        '  + actualización de `aplicacion_POST.php`,',
        '  `prompt_piloto.md` §5.8 y §4.9, y este archivo.',
        '',
        '**La migración NO vuelve a producción** hasta que el',
        'guardado parcial esté blindado (Fases 4 y 6 de §4.7).',
    ],
],

// ============================================================
// 3. §4.7: bug crítico + plan de 6 fases.
//    (La deuda técnica del arco pasa a §4.8.)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => '§4.7: bug crítico del guardado parcial + plan de 6 fases',
    'buscar' => [
        '### 4.7. Deuda técnica del arco (baja)',
        '',
        'Pendientes generados por la línea v81p-v82d. Ninguno es',
        'bloqueante.',
    ],
    'reemplazar' => [
        '### 4.7. Bug crítico abierto: corrupción del grafo por guardado parcial',
        '',
        '**Estado:** abierto. Fase 0 cerrada (este documento).',
        '',
        '**Síntoma.** Después de una secuencia de operaciones',
        '(agregar micro de 2 pisos → migrar pasajeros del micro',
        'viejo al nuevo → eliminar micro viejo → pagar cupón), el',
        'viaje desaparece de la lista del dueño. La terminal',
        'también deja de verlo. El header del dueño muestra',
        '"terminal" en lugar de "dueño".',
        '',
        '**Causa raíz (parcial, en investigación).**',
        '`_guardar_con_filtro` (en',
        '`PerdurarSuperestructuraStringSQL64.php`) es un',
        'DELETE+INSERT que filtra por máscara. Cuando el grafo',
        'tiene aristas que cruzan el borde (origen en el filtro,',
        'destino fuera), las aristas pueden quedar huérfanas y los',
        'nodos con máscara 0 no se persisten. La Fase 4 del',
        'framework (§11.4 del prompt del framework) documenta que',
        'el guardado parcial real por delta no está implementado.',
        'El bug estaba latente desde antes de C.10 (el parche de',
        '`IN (...)` no es la causa); la secuencia específica lo',
        'disparó.',
        '',
        '**Evidencia en la DB (antes de restaurar):**',
        '- `us_Parroquia_del_Carmen` con máscara `16` (solo bit 4,',
        '  el dueño). Le faltaban los bits 0-3 (aplicacion,',
        '  admin, usuarios, sesiones).',
        '- El viaje `166694` huérfano: nadie lo apuntaba desde el',
        '  contenedor `viajes` del dueño.',
        '- `contexto_mascara = 0` en 1817 nodos.',
        '- Header del dueño con "terminal": perdió el enlace',
        '  `nivel`.',
        '- `id_fila` del dueño cambiados entre requests',
        '  (`165810` → `190041`): duplicados por guardado parcial.',
        '',
        '**Estado post-restauración (v1.5piloto.82i):**',
        '- Backup SQL viejo (`nodo` + `adyacente`) restaurado.',
        '- `?migrar_a_sql64=1` corrido.',
        '- `?reparar_topologia=1`, `?reparar_arboles=1`,',
        '  `?reparar_cancelaciones=1`,',
        '  `?reparar_cancelaciones_arboles=1` corridos.',
        '- Verificación: dueño con máscara `116` (bits 2, 4, 5,',
        '  6). Solo 3 nodos huérfanos (residuos históricos, no',
        '  miles). App funcionando.',
        '',
        '**Plan de diagnóstico y blindaje (6 fases).**',
        '',
        '**Fase 0 — Anotar el plan y congelar producción.**',
        '- [x] Registrar este bug en `plan_actual.md`.',
        '- Reglas operativas mientras esté abierto:',
        '  - No aplicar migración de pasajeros.',
        '  - No aplicar Tanda 4 del arco de piso (v82i).',
        '  - Operación normal (vender, cancelar, pagar cupones)',
        '    permitida. Si una operación deja el viaje huérfano,',
        '    NO repetirla; anotar la secuencia exacta.',
        '',
        '**Fase 1 — Blindaje (red de seguridad).**',
        '- Modificar `_guardar_con_filtro` para que falle con',
        '  `rollback` si:',
        '  - Un nodo del grafo no queda persistido.',
        '  - Un nodo pierde bits de máscara respecto del estado',
        '    anterior en DB.',
        '  - El conteo de aristas en DB < conteo en grafo.',
        '- Costo: 2-3 SELECT extra por guardado parcial. Con',
        '  ~10k nodos, +1-2 segundos.',
        '- Beneficio: si el bug vuelve a pasar, falla con error',
        '  en lugar de corromper en silencio.',
        '',
        '**Fase 2 — Logging de diagnóstico.**',
        '- Flag `Conf::DEBUG_PERSISTENCIA`.',
        '- Log en `error_log`:',
        '  - Cuántos nodos en memoria, en `$mapa_previo`, nuevos.',
        '  - Nodos nuevos y su máscara calculada.',
        '  - Nodos con máscara 0 (los que se saltan del INSERT).',
        '  - Aristas a insertar, cuántas se saltan por destino',
        '    fuera de máscara.',
        '  - Aristas preexistentes que cruzan el borde y no se',
        '    reinsertan.',
        '- Se activa con `Conf::DEBUG_PERSISTENCIA = true` o',
        '  `?debug_persistencia=1`.',
        '',
        '**Fase 3 — Reproducir el bug en local.**',
        '- Con el backup restaurado + migración + log activo.',
        '- Secuencia: agregar micro → migrar → eliminar micro',
        '  viejo → pagar cupón.',
        '- Leer el log y aislar el punto exacto de corrupción.',
        '',
        '**Fase 4 — Fix real.**',
        '- Depende del diagnóstico:',
        '  - Si es "nodo nuevo con máscara 0 no se inserta": fix',
        '    es calcular la máscara en el INSERT, no confiar en',
        '    el BFS previo. O no saltar nodos con máscara 0 si',
        '    son alcanzables desde un nodo insertado.',
        '  - Si es "arista que cruza el borde no se reinserta":',
        '    fix es reinsertar aristas aunque el destino no esté',
        '    en `$mapa_final`, apuntando al `id_fila` previo.',
        '  - Si es otro: depende.',
        '- Aplicar el fix en PHP y espejar a JS en la misma',
        '  tanda (dos scripts, dos commits).',
        '',
        '**Fase 5 — Tanda 4 del arco de piso (v82i).**',
        '- Después del blindaje y el fix. Independiente del bug',
        '  §4.7. Se aplica cuando el guardado parcial esté',
        '  blindado.',
        '',
        '**Fase 6 — Migración de pasajeros a producción.**',
        '- Después de la Fase 4. Con el blindaje activo y el fix',
        '  aplicado.',
        '- Probarla primero en un viaje de prueba, no en',
        '  producción.',
        '',
        '### 4.8. Deuda técnica del arco (baja)',
        '',
        'Pendientes generados por la línea v81p-v82d. Ninguno es',
        'bloqueante.',
    ],
],

// ============================================================
// 4. Estado del proyecto al cierre (al final del archivo)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'Estado del proyecto al cierre: v83a + bug §4.7',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.82d',
        '(framework 1.5i.8f). El arco de cancelaciones en',
        'compartidos y el bug §4.6 (persistencia silenciosa en',
        'hostings compartidos) quedaron cerrados. SQL64 activo,',
        'topología admin/soporte, auditoría topológica disponible',
        '(`?auditar_topologia=1`, comando `app:auditar_topologia`,',
        'botón en el plugin). Todos los comandos de reparación',
        'idempotentes. Sin bugs abiertos de prioridad alta.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83a',
        '(framework 1.5i.8f). **Bug crítico abierto:** corrupción',
        'del grafo por guardado parcial (§4.7). Base restaurada',
        'desde backup + reparaciones idempotentes. Plan de',
        'diagnóstico y blindaje de 6 fases en curso. SQL64',
        'activo, topología admin/soporte, auditoría topológica',
        'disponible. **La migración de pasajeros queda en pausa**',
        'hasta que el bug §4.7 esté resuelto.',
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