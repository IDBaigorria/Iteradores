bien, ahora sí, dió verde la prueba. le di correr a la siguiente, seria la 59 y me dio esto: Cambiar de asiento: desde el croquis
Vende un asiento con un terminal, lo abre con Ver pasaje desde el croquis, aprieta Cambiar de asiento, elige un asiento libre, confirma y verifica que la venta apunta al asiento nuevo.
▶
Fallo: No se confirmo la venta. Aviso: Con 1 sola cuota, el monto a pagar debe cubrir el total
La prueba 59 tiene el mismo bug que tenía la 58: setea setear_monto_pagado(1000) cuando cuotas=1. Es un copy-paste de mi parte.

También hay otra cosa: abrir_primer_micro_con_libres devuelve un objeto {micro, libres}, no el array de asientos. En la prueba 59 yo hago:

js
const asientos_sel = await seleccionar_n_asientos(ctx, 1);
const numero_viejo = asientos_sel[0];
Pero seleccionar_n_asientos sí devuelve el array de asientos elegidos, no abrir_primer_micro_con_libres. Está bien.

Pero veo también que en la versión anterior de la prueba 59 yo puse await abrir_primer_micro_con_libres(ctx); sin capturar el resultado. Está bien, no lo uso.

Lo que hay que corregir: quitar setear_monto_pagado(1000).

Además hay otro problema potencial: después de confirmar la venta, el modal del viaje sigue abierto con el croquis, pero el modal de confirmación (formulario_confirmacion_venta) se oculta. El test hace:

js
await ctx.clic(`.seat[data-numero="${numero_viejo}"]`);
Pero el asiento numero_viejo está vendido ahora. ¿Se puede clickear? Sí, porque renderizar_pasaje_micro tiene un handler que llama a mostrar_info_asiento cuando se clickea cualquier asiento (no solo libre). Pero espera — en la versión actual de renderizar_pasaje_micro:

js
contenedorCroquis.onclick = async (event) => {
    if (venta_form_abierto || operacion_asiento_en_curso) return;
    const seat = event.target.closest('.seat');
    if (!seat) return;
    ...
    mostrar_info_asiento(asiento);
    if (es_terminal) {
        if (asiento.estado === 'reservado') { ... }
        if (asiento.estado === 'libre') { ... }
        else if (asiento.estado === 'seleccionado' && ...) { ... }
    }
};
Sí, clickear un asiento vendido muestra la tarjeta del asiento (mostrar_info_asiento), y desde ahí aparece "Ver pasaje".

El test hace:

click en el asiento vendido → aparece la tarjeta

esperar .btn-ver-pasaje-asiento

click → abre modal apilado

esperar #btn_cambiar_asiento_pasaje

click → abre el croquis en el modal apilado

esperar .seat.seat-elegible → elegir

click en asiento destino

click en #btn_confirmar_cambiar_asiento

esperar que se oculte el modal apilado

verificar por backend

Ok, tiene sentido.

Corrijo solo el setear_monto_pagado. Y de paso, miro si hay otro problema con el flujo de cancelar al final:

js
await cerrar_modales_si_abiertos(ctx);
await cancelar_venta(ctx, id_venta, "...");
cerrar_modales_si_abiertos cierra el modal apilado y el genérico. Después cancelar_venta. Pero cancelar_venta necesita que la sesión sea del terminal (que lo es). OK.

Aviso: la sesión sigue siendo del terminal, así que cancelar_venta debería andar.

Script corto: reemplazar la línea await setear_monto_pagado(ctx, 1000);.

Mismo bug que ya arreglamos en la 58: yo copié el setear_monto_pagado(1000) en la 59. Con cuotas = 1, el backend (v77m) lo rechaza.

Script
php
<?php
/**
 * Aplicador de cambios — Proyecto iteradoresJS (plugin de pruebas).
 *
 * Tanda V1.5plugin.6b (fix de la prueba 59).
 *   - Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js:
 *     quitar el setear_monto_pagado(1000). Con cuotas = 1 el input
 *     ya viene con el total (y disabled); forzarlo dispara el
 *     rechazo del backend introducido en v1.5piloto.77m.
 *
 * Uso (parado en iteradoresJS/):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: quitar setear_monto_pagado',
        'buscar' => [
            '        await setear_metodo_y_cuotas(ctx, "efectivo", 1);',
            '        await setear_monto_pagado(ctx, 1000);',
            '        await confirmar_venta(ctx);',
        ],
        'reemplazar' => [
            '        await setear_metodo_y_cuotas(ctx, "efectivo", 1);',
            '        // NO setear el monto manualmente: con cuotas = 1 el',
            '        // input ya viene con el total (y disabled). Forzarlo a',
            '        // un valor menor dispara el rechazo del backend (v77m).',
            '        await confirmar_venta(ctx);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: bump @version',
        'buscar' => [
            ' * @version 1.5plugin.5x',
        ],
        'reemplazar' => [
            ' * @version 1.5plugin.6b',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 2 reemplazo(s) en 1 archivo(s), 0 a crear.

[OK] Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js

=== Resumen ===
Bloques aplicados: 2
Archivos nuevos:   0

Listo.
Nota: el @version de la prueba 59 originalmente decía 1.5plugin.5x (copié el mismo valor en varias). Si tu archivo tiene otro número, avisame y ajusto el bloque.

Corré y después la 59 de nuevo. Debería pasar. Si falla con otro error, pegame el log de la consola (los [fetch] y la Respuesta cruda).

Cambiar de asiento: desde el croquis
Vende un asiento con un terminal, lo abre con Ver pasaje desde el croquis, aprieta Cambiar de asiento, elige un asiento libre, confirma y verifica que la venta apunta al asiento nuevo.
▶
Fallo: No hay asientos elegibles para el cambio. ¿La migración de repuntado está aplicada?

si habia, de hecho se ven visualmente en el modal. talves se apuro
El test falla porque busca .seat.seat-elegible y no encuentra nada. El usuario dice que visualmente ve los asientos elegibles, entonces el modal está bien, solo que el test corrió la consulta demasiado rápido (antes de que el modal termine de renderizar los asientos con las clases).

Mirando la prueba 59 que armé:

js
await ctx.clic("#btn_cambiar_asiento_pasaje");
await ctx.pausa(1500);

const elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");
Una pausa fija de 1500ms. Pero abrir_modal_cambiar_asiento hace DOS fetches en serie (estado_asientos + obtener_micro), y en un grafo grande eso puede tardar más. Cuando la pausa termina y consultamos, el modal todavía no terminó de armar el croquis.

Fix: en lugar de pausa fija, esperar activamente a que aparezca al menos un .seat.seat-elegible. Polling con timeout largo.

También hay otro riesgo: el modal apilado reemplaza su contenido. Cuando el modal "Ver pasaje" está abierto, clickear "Cambiar de asiento" hace DOS fetches y después llama abrir_modal_apilado(...), que reemplaza el HTML. El test debería esperar a que el croquis del modal apunte al nuevo contenido. La espera activa sirve para eso.

Script:

php
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
    'descripcion' => 'Prueba 59: esperar activamente por asientos elegibles',
    'buscar' => [
        '        // 3. Click en "Cambiar de asiento".',
        '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
        '        await ctx.pausa(1500);',
        '',
        '        const elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");',
        '        ctx.assert(elegibles.length > 0,',
        '            "No hay asientos elegibles para el cambio. ¿La migración de repuntado está aplicada?");',
    ],
    'reemplazar' => [
        '        // 3. Click en "Cambiar de asiento".',
        '        // El modal hace 2 fetches en serie (estado_asientos + obtener_micro)',
        '        // antes de armar el croquis. En un grafo grande, una pausa fija',
        '        // no alcanza. Se hace polling activo hasta que aparezca al menos',
        '        // un asiento elegible.',
        '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
        '',
        '        const inicio_espera = Date.now();',
        '        let elegibles = [];',
        '        while (Date.now() - inicio_espera < 20000) {',
        '            elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");',
        '            if (elegibles.length > 0) break;',
        '            await ctx.pausa(300);',
        '        }',
        '        ctx.assert(elegibles.length > 0,',
        '            "No hay asientos elegibles para el cambio tras 20s. "',
        '            + "¿La migración de repuntado está aplicada?");',
    ],
],
Y también podría subir la espera por #btn_confirmar_cambiar_asiento... pero está bien, el obtener_atributos no falla si no encuentra; devuelve array vacío. Espera 20s máximo.

También en la línea donde espera al modal apilado (#modal_apilado) podría agregarse esperar por .seat-elegible directamente. Pero la espera activa sirve.

Un detalle: abrir_modal_cambiar_asiento reemplaza el contenido del modal apilado. Si el test hace clic en "Cambiar de asiento" y de inmediato intenta obtener_atributos, puede ver los asientos del modal anterior ("Ver pasaje" no tiene asientos, es HTML con texto). Entonces es normal que la primera iteración devuelva array vacío. La espera activa resuelve esto.

Además, hay otro factor: el test busca .seat.seat-elegible sin estar scopeado a #modal_apilado_contenido. En el croquis de atrás también hay .seat (del modal del viaje). Pero solo el croquis del modal de cambiar asiento tiene .seat-elegible, así que no colisiona.

Bien, armo el script con esa corrección. También subo el @version.

Es un problema de timing: el modal hace dos fetches en serie (estado_asientos + obtener_micro) antes de armar el croquis. Mi ctx.pausa(1500) fue optimista. Con un grafo grande, esos dos fetches pueden tardar bastante más. Cuando el test consulta .seat.seat-elegible, el croquis todavía no se armó.

Script
php
<?php
/**
 * Aplicador de cambios — Proyecto iteradoresJS (plugin de pruebas).
 *
 * Tanda V1.5plugin.6c (fix de la prueba 59).
 *   - Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js:
 *     esperar activamente a que aparezcan asientos elegibles en el
 *     modal, en lugar de una pausa fija. El modal hace 2 fetches en
 *     serie (estado_asientos + obtener_micro) antes de armar el
 *     croquis; en un grafo grande eso puede tardar más que la pausa
 *     fija de 1.5s, y el test consultaba antes de que estuviera listo.
 *
 * Uso (parado en iteradoresJS/):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: esperar activamente por asientos elegibles',
        'buscar' => [
            '        // 3. Click en "Cambiar de asiento".',
            '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
            '        await ctx.pausa(1500);',
            '',
            '        const elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");',
            '        ctx.assert(elegibles.length > 0,',
            '            "No hay asientos elegibles para el cambio. ¿La migración de repuntado está aplicada?");',
        ],
        'reemplazar' => [
            '        // 3. Click en "Cambiar de asiento".',
            '        // El modal hace 2 fetches en serie (estado_asientos +',
            '        // obtener_micro) antes de armar el croquis. En un grafo',
            '        // grande eso puede tardar varios segundos. Se hace polling',
            '        // activo hasta que aparezca al menos un asiento elegible.',
            '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
            '',
            '        const inicio_espera = Date.now();',
            '        let elegibles = [];',
            '        while (Date.now() - inicio_espera < 25000) {',
            '            elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");',
            '            if (elegibles.length > 0) break;',
            '            await ctx.pausa(300);',
            '        }',
            '        ctx.assert(elegibles.length > 0,',
            '            "No hay asientos elegibles para el cambio tras 25s. "',
            '            + "¿La migración de repuntado está aplicada?");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: bump @version',
        'buscar' => [
            ' * @version 1.5plugin.6b',
        ],
        'reemplazar' => [
            ' * @version 1.5plugin.6c',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 2 reemplazo(s) en 1 archivo(s), 0 a crear.

[OK] Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js

=== Resumen ===
Bloques aplicados: 2
Archivos nuevos:   0

Listo.
Corré y después la 59 de nuevo. Si falla con "no hay asientos elegibles tras 25s", entonces sí hay un problema real (no timing): puede ser que abrir_modal_cambiar_asiento esté fallando silenciosamente en algún fetch. En ese caso pegame el log de la consola con los [fetch] y la respuesta del POST viajes/estado_asientos.

Cambiar de asiento: desde el croquis
Vende un asiento con un terminal, lo abre con Ver pasaje desde el croquis, aprieta Cambiar de asiento, elige un asiento libre, confirma y verifica que la venta apunta al asiento nuevo.
▶
Fallo: El modal de cambio de asiento no se cerró. Aviso: Asiento seleccionado
El test llega al paso 4 (confirmar). El problema es que después de clic("#btn_confirmar_cambiar_asiento") el modal no se cierra y el aviso dice "Asiento seleccionado". Esto significa que el click cayó en el croquis de atrás, no en el botón del modal apilado. O que el click en el botón no disparó el POST de cambiar_asiento.

"Asiento seleccionado" es el toast de seleccionar_asiento_pasaje (el croquis del micro), no de cambiar_asiento. Eso significa que el click con .seat[data-numero=...] está siendo interceptado por el croquis de atrás (viajes-asientos.js), no por el croquis del modal apilado.

Hay 2 elementos .seat[data-numero="N"] en el DOM:

El del modal apilado (que abre abrir_modal_cambiar_asiento).

El del croquis del micro de atrás (abierto en el modal genérico del viaje).

ctx.clic usa document.querySelector que devuelve el primero. Y el croquis de atrás está primero en el DOM (el modal apilado se agrega después).

Además, después de elegir el asiento en el modal, hay que clickear el botón "Confirmar cambio" que está dentro del modal apilado. Pero el problema es que cuando selecciona el asiento, el click cae en el croquis de atrás y dispara seleccionar_asiento_pasaje → "Asiento seleccionado".

Espera, pero el test dice "el modal no se cerró" — eso significa que llegó a hacer click en #btn_confirmar_cambiar_asiento (sin error). Entonces el click en el asiento del modal sí llegó al modal (o al menos no falló). El problema es que el botón "Confirmar cambio" no está disparando el POST.

Hmm, pero si el click en el asiento cayó en el croquis de atrás, el botón del modal sigue disabled (porque cambiar_asiento_datos_modal.destino sigue en null). Un click en un botón disabled no dispara nada. Entonces el ctx.clic("#btn_confirmar_cambiar_asiento") no hace nada, el modal no se cierra, y esperar_oculto timeout con el aviso "Asiento seleccionado" (que es del click previo).

Ese es el diagnóstico. La prueba tiene que:

Clic en el asiento dentro del modal apilado — necesito un selector más específico: #modal_apilado_contenido .seat[data-numero="N"].

Clic en el botón dentro del modal apilado: #modal_apilado_contenido #btn_confirmar_cambiar_asiento (aunque el ID es único, no hace daño ser específico).

Pero espera, ¿el orden en el DOM? El modal apilado está después del modal genérico. document.querySelector('.seat[data-numero="N"]') devuelve el primero. Si el modal genérico (con el croquis de atrás) está primero, entonces devuelve el asiento del croquis de atrás.

Además: cuando el usuario clickea un asiento en el croquis del modal apilado, el modal apilado debería estar encima del genérico. Pero visualmente uno puede estar encima del otro. Sin embargo, elementFromPoint en la posición podría devolver el del modal apilado. Pero ctx.clic no usa elementFromPoint, usa querySelector.

Solución: usar selectores específicos #modal_apilado_contenido .seat[...].

También el #btn_confirmar_cambiar_asiento es único (nadie más lo tiene), así que ctx.clic("#btn_confirmar_cambiar_asiento") debería funcionar. El problema es que está disabled porque el asiento no se seleccionó en el modal.

Voy a hacer el fix: cambiar el selector del click del asiento a #modal_apilado_contenido .seat[data-numero="..."]. Y también para el elegible: #modal_apilado_contenido .seat.seat-elegible.

Puede ser que también #btn_confirmar_cambiar_asiento no esté en el modal apilado por algo. Pero no, está en el HTML que genera abrir_modal_cambiar_asiento, y ese HTML se inyecta en #modal_apilado_contenido.

Voy con el fix.

También hay que chequear: después de elegir el asiento en el modal, el croquis de atrás puede estar recibiendo el click si el modal apilado no está encima. Pero mirando el z-index del CSS, el modal apilado está a z-index más alto. Igual, querySelector no mira z-index, devuelve el primero en el DOM.

OK, fix:

Buscar elegibles: #modal_apilado_contenido .seat.seat-elegible (data-numero).

Clic en el elegido: #modal_apilado_contenido .seat[data-numero="N"].

Confirmar: #modal_apilado_contenido #btn_confirmar_cambiar_asiento.

También hay que asegurarse de que el botón de confirmar ya esté habilitado antes de clickearlo (o esperar a que se habilite). Después de clickear el asiento, el listener hace btn.disabled = false. Pero el test puede clickear el asiento y después clickear confirmar inmediatamente, y si por alguna razón el click en el asiento no llegó, el confirmar sigue disabled.

Mejor: agregar un polling que espere a que el botón confirmar esté habilitado, después clickearlo.

Voy con eso.

El diagnóstico: hay dos croquis .seat en el DOM — el del modal apilado (que abre abrir_modal_cambiar_asiento) y el del micro de atrás (modal genérico del viaje). document.querySelector devuelve el primero en el DOM, que es el de atrás. Cuando el test hace clic en el asiento elegido, el clic aterriza en el croquis de atrás y dispara seleccionar_asiento_pasaje → el toast "Asiento seleccionado".

Y como el clic nunca llegó al modal, cambiar_asiento_datos_modal.destino sigue null y el botón "Confirmar cambio" sigue deshabilitado. Un clic en un botón disabled no hace nada → el modal no se cierra.

Fix
Usar selectores específicos dentro de #modal_apilado_contenido para el croquis del modal, y esperar a que el botón "Confirmar cambio" esté habilitado antes de clickearlo.

php
<?php
/**
 * Aplicador de cambios — Proyecto iteradoresJS (plugin de pruebas).
 *
 * Tanda V1.5plugin.6d (fix de la prueba 59).
 *   - Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js:
 *     acotar los selectores del croquis del modal apilado a
 *     `#modal_apilado_contenido ...`. Hay DOS croquis `.seat` en el
 *     DOM (el del modal y el del viaje de atrás); sin acotar, el
 *     click cae en el de atrás y no activa el botón de confirmar.
 *     Además, esperar a que el botón de confirmar esté habilitado
 *     antes de clickearlo.
 *
 * Uso (parado en iteradoresJS/):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: selectores acotados al modal apilado',
        'buscar' => [
            '        // 3. Click en "Cambiar de asiento".',
            '        // El modal hace 2 fetches en serie (estado_asientos +',
            '        // obtener_micro) antes de armar el croquis. En un grafo',
            '        // grande eso puede tardar varios segundos. Se hace polling',
            '        // activo hasta que aparezca al menos un asiento elegible.',
            '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
            '',
            '        const inicio_espera = Date.now();',
            '        let elegibles = [];',
            '        while (Date.now() - inicio_espera < 25000) {',
            '            elegibles = await ctx.obtener_atributos(".seat.seat-elegible", "data-numero");',
            '            if (elegibles.length > 0) break;',
            '            await ctx.pausa(300);',
            '        }',
            '        ctx.assert(elegibles.length > 0,',
            '            "No hay asientos elegibles para el cambio tras 25s. "',
            '            + "¿La migración de repuntado está aplicada?");',
            '        const numero_nuevo = elegibles[0];',
            '',
            '        await ctx.clic(`.seat[data-numero="${numero_nuevo}"]`);',
            '        await ctx.pausa(300);',
            '',
            '        // 4. Confirmar el cambio.',
            '        await ctx.clic("#btn_confirmar_cambiar_asiento");',
        ],
        'reemplazar' => [
            '        // 3. Click en "Cambiar de asiento".',
            '        // El modal hace 2 fetches en serie (estado_asientos +',
            '        // obtener_micro) antes de armar el croquis. En un grafo',
            '        // grande eso puede tardar varios segundos. Se hace polling',
            '        // activo hasta que aparezca al menos un asiento elegible.',
            '        // IMPORTANTE: hay DOS croquis `.seat` en el DOM (el del',
            '        // modal apilado y el del viaje de atrás). Los selectores',
            '        // se acotan a `#modal_apilado_contenido` para no clickear',
            '        // el croquis de atrás.',
            '        await ctx.clic("#btn_cambiar_asiento_pasaje");',
            '',
            '        const inicio_espera = Date.now();',
            '        let elegibles = [];',
            '        while (Date.now() - inicio_espera < 25000) {',
            '            elegibles = await ctx.obtener_atributos(',
            '                "#modal_apilado_contenido .seat.seat-elegible",',
            '                "data-numero"',
            '            );',
            '            if (elegibles.length > 0) break;',
            '            await ctx.pausa(300);',
            '        }',
            '        ctx.assert(elegibles.length > 0,',
            '            "No hay asientos elegibles para el cambio tras 25s. "',
            '            + "¿La migración de repuntado está aplicada?");',
            '        const numero_nuevo = elegibles[0];',
            '',
            '        await ctx.clic(`#modal_apilado_contenido .seat[data-numero="${numero_nuevo}"]`);',
            '',
            '        // El botón Confirmar arranca disabled y se habilita cuando',
            '        // el listener del asiento registra el destino. Esperar a',
            '        // que esté habilitado antes de clickearlo.',
            '        const inicio_confirmar = Date.now();',
            '        let confirmar_listo = false;',
            '        while (Date.now() - inicio_confirmar < 5000) {',
            '            const deshabilitados = await ctx.obtener_atributos(',
            '                "#modal_apilado_contenido #btn_confirmar_cambiar_asiento",',
            '                "disabled"',
            '            );',
            '            // Si no tiene el atributo disabled, está habilitado.',
            '            if (deshabilitados.length === 0 || deshabilitados[0] === null) {',
            '                confirmar_listo = true;',
            '                break;',
            '            }',
            '            await ctx.pausa(150);',
            '        }',
            '        ctx.assert(confirmar_listo,',
            '            "El botón Confirmar cambio no se habilitó tras elegir el asiento. "',
            '            + "Puede que el click haya caído en el croquis de atrás.");',
            '',
            '        // 4. Confirmar el cambio.',
            '        await ctx.clic("#modal_apilado_contenido #btn_confirmar_cambiar_asiento");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: bump @version',
        'buscar' => [
            ' * @version 1.5plugin.6c',
        ],
        'reemplazar' => [
            ' * @version 1.5plugin.6d',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 2 reemplazo(s) en 1 archivo(s), 0 a crear.

[OK] Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js

=== Resumen ===
Bloques aplicados: 2
Archivos nuevos:   0

Listo.
Puntos del cambio:

Selector del elegible: #modal_apilado_contenido .seat.seat-elegible. Ya no agarra el croquis de atrás.

Selector del asiento elegido: #modal_apilado_contenido .seat[data-numero="N"]. Fuerza que el click caiga en el del modal.

Selector del botón de confirmar: #modal_apilado_contenido #btn_confirmar_cambiar_asiento (el ID ya era único, pero así queda blindado).

Espera activa del botón habilitado: antes de clickear, verifica que ya no tenga el atributo disabled. Si el click en el asiento no llegó al modal, el botón sigue disabled y el assert tira un mensaje claro.

Nota sobre ctx.obtener_atributos
Un detalle: ctx.obtener_atributos devuelve [null] si el atributo no existe (como bien dice el prompt del plugin, aprendizaje 27). Por eso el chequeo deshabilitados[0] === null — si el botón no tiene el atributo disabled, el array viene [null] y eso significa "habilitado". Si viene [""] o ["disabled"], está deshabilitado.

Corré y después la prueba 59 de nuevo. Si pasa, cerramos B2.3.5 (la tanda de pruebas) y quedan como pendientes:

B2.4 — mantener vivos los compartidos en cada operación de escritura.

B3 — limpieza de los 22 nodo_por_id('usuarios').

si, se habilito, lo veo: Cambiar de asiento: desde el croquis
Vende un asiento con un terminal, lo abre con Ver pasaje desde el croquis, aprieta Cambiar de asiento, elige un asiento libre, confirma y verifica que la venta apunta al asiento nuevo.
▶
Fallo: El botón Confirmar cambio no se habilitó tras elegir el asiento. Puede que el click haya caído en el croquis de atrás.
El test dice que el botón no se habilitó. Pero el usuario dice que visualmente sí se habilitó. Entonces el problema es que ctx.obtener_atributos con disabled puede devolver algo distinto de lo que espero.

Mirando el aprendizaje 27 del prompt del plugin:

ctx.obtener_atributos devuelve [null] si el atributo no existe, no []

Entonces si el botón no tiene el atributo disabled, devuelve [null]. Si lo tiene, devuelve [""] o ["disabled"]. Mi condición:

js
if (deshabilitados.length === 0 || deshabilitados[0] === null) {
Debería funcionar. Pero quizás el botón tiene disabled="" (que sería [""], no null) incluso cuando está habilitado en el DOM — o al revés, no tiene el atributo pero por algún motivo obtener_atributos devuelve otra cosa.

Mejor cambio de estrategia: en lugar de chequear el atributo disabled, chequear el estado real del DOM. ctx.valor no sirve para botones. Podría hacer un executeScript directo o usar ctx.html o ctx.texto.

En realidad, la forma más limpia es usar un helper que no tengo pero puedo agregar: verificar con chrome.scripting.executeScript el .disabled real del botón.

Espera, ¿tenemos ctx.contar? Sí. Pero no sirve para disabled.

Mejor uso directamente el valor de un atributo que sí funciona con obtener_atributos. Hmm.

Alternativa: en lugar de chequear el botón, chequear que el asiento elegido esté marcado en el DOM del modal. Cuando se hace clic en un asiento elegible, el JS del piloto agrega la clase seat-elegido. Entonces puedo chequear que exista #modal_apilado_contenido .seat.seat-elegido.

Esto es más robusto porque no depende de cómo el atributo disabled se serializa.

Modifico el script.

También: el usuario dice que vio el botón habilitarse. ¿Pero entonces por qué el test dice que no se habilitó?

Posibilidades:

El usuario ve el botón habilitado en la corrida manual del test, no en la corrida automática. Puede estar viendo la prueba corriendo en el browser pero el obtener_atributos devuelve mal.

El botón sí se habilita pero obtener_atributos con atributo disabled devuelve algo raro.

Probablemente (2). Cambio a chequear .seat-elegido.

Otra opción: el obtener_atributos es el helper que mira getAttribute(attr). Si el atributo no existe, getAttribute devuelve null. Si existe con valor vacío, devuelve "". En HTML, disabled puede estar presente sin valor (solo el nombre). Entonces getAttribute("disabled") devuelve "".

Cuando el JS del piloto hace btn.disabled = false, el atributo disabled se elimina del DOM. Entonces getAttribute("disabled") devuelve null. Entonces mi condición debería funcionar.

A menos que el helper haga otra cosa. Voy a mirar. Según el service worker:

text
obtener_atributos: async (sel, attr) => {
    const r = await enviar("obtener_atributos", { selector: sel, atributo: attr });
    return r && r.exito ? r.valores : [];
},
Y en el content script (que no tengo) hay que ver qué hace. Según el aprendizaje 27, devuelve [null] si el atributo no existe.

Voy a cambiar la estrategia. Chequear .seat-elegido es más directo y menos frágil.

Nueva lógica:

text
1. Después de hacer clic en el asiento, esperar a que exista `.seat-elegido` en `#modal_apilado_contenido`.
2. Solo después de eso, clickear Confirmar.
Además, quizás el click se hace pero el seat.dataset.valido no es '1'? No, ya verificamos que elegibles.length > 0, así que hay al menos un asiento con data-valido="1".

Espera, ¿podría ser que el click del ctx.clic esté usando el selector como global? El selector #modal_apilado_contenido .seat[data-numero="..."] debería funcionar. Pero tal vez el content script tiene un problema.

Hmm. Voy con el cambio a chequear .seat-elegido. Es más robusto y captura el efecto visible del clic.

El botón está bien: el problema es mi chequeo. ctx.obtener_atributos para atributos booleanos como disabled es frágil (aprendizaje 27 del prompt del plugin). Cambio la estrategia: chequear directamente que el asiento haya quedado marcado como elegido (clase .seat-elegido), que es el efecto visual del clic.

php
<?php
/**
 * Aplicador de cambios — Proyecto iteradoresJS (plugin de pruebas).
 *
 * Tanda V1.5plugin.6e (fix de la prueba 59, tercera iteración).
 *   - Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js:
 *     en lugar de chequear el atributo `disabled` del botón de
 *     confirmar (frágil por cómo se serializa el atributo booleano
 *     en `getAttribute`), chequear que el asiento elegido haya
 *     quedado marcado con la clase `.seat-elegido` dentro del modal
 *     apilado. Es el efecto visible del clic y no depende de cómo
 *     el navegador serializa `disabled`.
 *
 * Uso (parado en iteradoresJS/):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: chequear .seat-elegido en vez de disabled',
        'buscar' => [
            '        await ctx.clic(`#modal_apilado_contenido .seat[data-numero="${numero_nuevo}"]`);',
            '',
            '        // El botón Confirmar arranca disabled y se habilita cuando',
            '        // el listener del asiento registra el destino. Esperar a',
            '        // que esté habilitado antes de clickearlo.',
            '        const inicio_confirmar = Date.now();',
            '        let confirmar_listo = false;',
            '        while (Date.now() - inicio_confirmar < 5000) {',
            '            const deshabilitados = await ctx.obtener_atributos(',
            '                "#modal_apilado_contenido #btn_confirmar_cambiar_asiento",',
            '                "disabled"',
            '            );',
            '            // Si no tiene el atributo disabled, está habilitado.',
            '            if (deshabilitados.length === 0 || deshabilitados[0] === null) {',
            '                confirmar_listo = true;',
            '                break;',
            '            }',
            '            await ctx.pausa(150);',
            '        }',
            '        ctx.assert(confirmar_listo,',
            '            "El botón Confirmar cambio no se habilitó tras elegir el asiento. "',
            '            + "Puede que el click haya caído en el croquis de atrás.");',
            '',
            '        // 4. Confirmar el cambio.',
            '        await ctx.clic("#modal_apilado_contenido #btn_confirmar_cambiar_asiento");',
        ],
        'reemplazar' => [
            '        await ctx.clic(`#modal_apilado_contenido .seat[data-numero="${numero_nuevo}"]`);',
            '',
            '        // Cuando el listener del asiento registra el destino, el',
            '        // piloto marca el asiento con la clase `.seat-elegido`.',
            '        // Eso es el efecto visible del clic y no depende de cómo',
            '        // el navegador serialice el atributo booleano `disabled`',
            '        // (que puede ser `[""]`, `["disabled"]` o `[null]`, según',
            '        // cómo se haya seteado).',
            '        const inicio_confirmar = Date.now();',
            '        let asiento_marcado = false;',
            '        while (Date.now() - inicio_confirmar < 5000) {',
            '            const marcados = await ctx.obtener_atributos(',
            '                `#modal_apilado_contenido .seat.seat-elegido[data-numero="${numero_nuevo}"]`,',
            '                "data-numero"',
            '            );',
            '            if (marcados.length > 0) {',
            '                asiento_marcado = true;',
            '                break;',
            '            }',
            '            await ctx.pausa(150);',
            '        }',
            '        ctx.assert(asiento_marcado,',
            '            "El asiento elegido no quedó marcado con `.seat-elegido` en el modal. "',
            '            + "El click en el asiento no llegó al modal del cambio.");',
            '',
            '        // 4. Confirmar el cambio.',
            '        await ctx.clic("#modal_apilado_contenido #btn_confirmar_cambiar_asiento");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js',
        'descripcion' => 'Prueba 59: bump @version',
        'buscar' => [
            ' * @version 1.5plugin.6d',
        ],
        'reemplazar' => [
            ' * @version 1.5plugin.6e',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 2 reemplazo(s) en 1 archivo(s), 0 a crear.

[OK] Aplicacion/pruebas/prueba_59_cambiar_asiento_desde_croquis.js

=== Resumen ===
Bloques aplicados: 2
Archivos nuevos:   0

Listo.
Cambio: en lugar de chequear disabled (que se serializa de forma rara en HTML: puede ser ausente, "", o "disabled"), espero a que el asiento elegido quede marcado con la clase .seat-elegido. Eso es el efecto directo del clic y captura exactamente lo que queremos verificar.

Corré y después la 59 de nuevo. Si ahora falla con "El asiento elegido no quedó marcado", entonces sí el clic no está llegando al modal y hay que investigar más a fondo (por ejemplo, ver si el modal_apilado_contenido tiene el croquis del modal o si el click se pierde por algún motivo).

bien, dio verde. como esta conversacion ya es bastante larga, aseguremonos de tener los prompts actualizados para poder retomarlos en cualquier momento a partir del punto y plan actual. agrega tambien las lecciones que has aprendido.
El usuario quiere cerrar esta conversación larga con los prompts actualizados. Necesito:

Actualizar plan_actual.md con el estado final (v1.5piloto.77n + v1.5plugin.6e).

Actualizar prompt_plugin_piloto.md con las nuevas pruebas (57, 58, 59) y las lecciones aprendidas.

Actualizar prompt_piloto.md si aplica (probablemente solo el historial).

Actualizar prompt_framework_iteradores.md con el bump de 1.5i.7m (destino_es_compartido).

Lecciones aprendidas en esta sesión:

Framework:

grafo:nodo devuelve IDs numéricos no estables entre cargas. No sirve para "seguir" un enlace entre dos consultas.

El campo destino_es_compartido agregado a grafo:nodo permite verificar topología sin segunda consulta.

Los IDs numéricos son referencias de memoria. Solo los IDs especiales son estables entre cargas.

Un comando que busca por dato es frágil (datos pueden repetirse). Todo recorrido debe partir de una raíz.

Piloto:

DivisionByZeroError en _crear_lista_cupones_venta cuando cuotas=1 y monto_pagado < total. Fix en v77m.

_asegurar_compartido_y_repuntar: al crear/editar terminal, se asegura el compartido y se repunta. Antes solo lo hacía el comando manual, y no cubría terminales nuevos.

app:repuntar_terminales_compartido reescrito para iterar sobre terminales, no sobre compartidos existentes.

Timezone: date_default_timezone_set('America/Argentina/Buenos_Aires') en index.php. Bug latente desde v58.

Plugin:

Los IDs numéricos no son estables entre POSTs. Toda verificación debe ser navegación desde una raíz dentro de una sola carga.

ventas/listar con tipo=dueno y tipo=terminal navega los dos árboles paralelos.

Selectores en modal apilado: usar #modal_apilado_contenido ... cuando hay croquis duplicado en el DOM (modal + atrás).

Chequear efecto visual (.seat-elegido) en vez de atributo booleano disabled — getAttribute('disabled') puede devolver [null], [""] o ["disabled"].

setear_monto_pagado con cuotas=1 rompe el flujo del piloto (ahora rechazado por v77m).

Pausas fijas vs polling activo: los modales hacen 2 fetches en serie, pueden tardar.

Ahora armo los scripts. Voy a hacer un solo script grande que actualice los 4 prompts. Mejor separo en 2 scripts: uno para PHP (plan_actual, prompt_piloto, prompt_framework) y otro para el plugin JS (prompt_plugin_piloto).

En realidad, el plan_actual vive en PHP. El prompt_plugin vive en iteradoresJS. Y el prompt_framework en PHP.

Voy a hacer:

Script A — Proyecto iteradores (PHP):

prompts/plan_actual.md: actualizar última actualización, tanda actual (v77m, v77n aplicado), agregar nueva sección con lecciones aprendidas.

prompts/prompt_framework_iteradores.md: agregar grafo:nodo con destino_es_compartido al historial 1.5i.7m, y agregar aprendizajes nuevos a §13.

Script B — Proyecto iteradoresJS (plugin):

prompts/prompt_plugin_piloto.md: agregar las pruebas 57/58/59, lecciones nuevas, actualizar estado.

Pero espera, en el prompt del plugin hay que ver si las secciones son fáciles de anclar. Mirando el prompt, tiene secciones como:

§7 Estado actual

§8 Lecciones aprendidas a la fuerza

§9 Discusión actual

Voy a agregar lecciones en §8 y actualizar el estado en §7 y §9.

Voy a hacerlo con cuidado pero sin sobreestimar la longitud. Dado que la conversación es larga, quiero un script que sea preciso pero no gigantesco.

Voy a hacer el script A primero (PHP), más largo, y el B después (JS).

Realmente, para el prompt del plugin, no tengo su contenido textual exacto. Sé que existe en iteradoresJS/prompts/prompt_plugin_piloto.md y me fue pasado al principio de la conversación. Pero está muy largo.

Mejor: hago dos scripts separados.

Script A — solo plan_actual.md. Es el más importante. Le agrego toda la actualización final. Y en prompt_framework_iteradores.md agrego el bump 1.5i.7m.

Script B — prompt_plugin_piloto.md, actualizo estado + lecciones nuevas.

Voy con esto.

Para el script A, bloques:

plan_actual.md: §1.1 última actualización.

plan_actual.md: tanda actual.

plan_actual.md: nueva sección §1.8 "Lecciones aprendidas en esta sesión" (o similar).

prompt_framework_iteradores.md: historial 1.5i.7m.

prompt_framework_iteradores.md: nueva lección 28 en §13.

Y después script B.

Necesito ser cuidadoso con las anclas del plan_actual. El plan_actual actual (que me pasó el usuario al principio) tiene:

text
**Última actualización de este archivo:** v1.5piloto.77d
(fix del modal de pasajero tras cambiar de asiento).
...
Pero después le hicimos muchas actualizaciones. La última que sé que se aplicó fue la de "v77k (Fase B2.3.5c.1: grafo:nodo con destino_es_compartido)".

Hmm, en realidad después aplicamos varias actualizaciones:

v77k (destino_es_compartido)

v77l (fix del flujo de alta)

v77m (DivisionByZeroError)

v77n (grafo:nodo_por_dato) — pero descartamos no correrlo

Y luego las pruebas 58 y 59 corregidas.

Entonces la última actualización del plan_actual que aplicamos fue... En el script de v77m hicimos "Tanda actual: v77m (fix del DivisionByZeroError)". Después el usuario descartó el script A de v77n. Entonces el plan_actual dice "Tanda actual: v77m (fix del DivisionByZeroError)".

Necesito actualizar eso. Voy a hacer las anclas sobre "Tanda actual: v77m (fix del DivisionByZeroError)".

Para las lecciones, voy a agregar una sección nueva. Pero el archivo está abierto en v1.7 con secciones 1.1 a 1.7. Agrego 1.8.

Voy con el script.

Para el script B, prompt_plugin_piloto.md. El archivo tiene una sección §7 "Estado actual" y §8 "Lecciones aprendidas" y §9 "Discusión actual". Necesito verificar los anclajes. Del prompt que tengo:

text
## 7. ESTADO ACTUAL

**Proyecto en v1.5plugin.5w.** El esqueleto del plugin está
armado y funcional, tiene 56 pruebas...
Tiene todo el listado. Voy a actualizar el conteo a 59 pruebas.

Y en §8, las lecciones van numeradas. Ya hay hasta 46. Agrego 47-52.

Y en §9, la Discusión actual está al final del prompt.

Hmm, esto es mucho. Voy a ser estratégico: actualizo solo lo más importante del prompt del plugin (conteo de pruebas y sección "discusión actual"), y agrego 4-6 lecciones nuevas a §8.

Voy con el código.

Para el prompt del plugin, voy a agregar un bloque al final de las lecciones (después de 46) y actualizar el estado + discusión actual.

Ok, me pongo.

Voy a asumir que el prompt del plugin tiene exactamente la sección 8 con el último punto 46. Busco eso para anclar.

Del prompt que el usuario me pasó:

text
46. **Verificar el nombre real de la acción antes de
    escribir un POST.** `viajes/agregar` no existe en el
    enrutador: la acción real es `viajes/guardar` (alta o
    edición unificada, llama a `guardar_viaje_completo`).
    Antes de escribir un test que toque el backend, leer
    el `switch ($subaccion)` del módulo correspondiente
    en `Aplicacion/Enrutador.php`. La sección 6 de este
    prompt tiene los códigos de acceso, pero no las
    acciones: para acciones, siempre leer el Enrutador.
Ese es el último punto de §8. Voy a hacer un bloque buscar sobre "46." y reemplazar por "46." + nuevos.

Ahora sí, escribo los dos scripts.

Aunque es tentador hacer un solo script grande, mejor dos por proyecto (PHP y JS). El usuario corre cada uno en su repo.

Ok, me pongo.

Ah, y por último, sobre el historial del prompt del framework. Necesito ver las últimas entradas. Del prompt:

text
- **1.5i.7k**: fase 1 (PHP) y fase 2 (JS) de contextos.
  ...
- **1.5i.7l**: comando `grafo:raices` agregado al framework
  PHP (tanda 76n) y espejado en JS. Lista los nodos raíz
  ...
  - **Espejar `grafo:reemplazar_referencias` en JS.**
    ...
  - **Mover `grafo:crear_niveles_usuario` fuera del
    framework.** ...
  - **No se pudo verificar `grafo:raices` desde la consola
    del service worker del plugin.** ...
Y después viene el texto "El espejo JS también recibió mejoras en paralelo...". Ahí voy a agregar 1.5i.7m y 1.5i.7n... espera, 1.5i.7n no se aplicó (descartamos).

Entonces solo 1.5i.7m.

Ok, voy.

Voy con dos scripts: uno para el proyecto PHP (iteradores/) que actualiza plan_actual.md y prompt_framework_iteradores.md, y otro para el proyecto JS (iteradoresJS/) que actualiza prompt_plugin_piloto.md.

Script A — Proyecto iteradores/
php
<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77o (documentación de cierre).
 *   - prompts/plan_actual.md: actualizar última versión, tanda
 *     actual, y agregar §1.8 con las lecciones aprendidas en
 *     esta sesión.
 *   - prompts/prompt_framework_iteradores.md: agregar 1.5i.7m
 *     al historial y dos lecciones nuevas en §13.
 *   - index.php: bump.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // plan_actual.md — §1.1 última actualización
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: §1.1 última actualización a v77o',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.77d',
            '(fix del modal de pasajero tras cambiar de asiento).',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.77o',
            '(cierre de la fase B2.3.5: pruebas del plugin + fixes).',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77o',
        'buscar' => [
            '**Tanda actual:** v77m (fix del DivisionByZeroError).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77o (cierre de B2.3.5 + documentación).',
            '',
            '**Estado al cierre.** La fase B2.3.5 quedó completa:',
            'el repuntado de terminales al compartido está aplicado en',
            'producción, los terminales nuevos se repuntan al vuelo, y',
            'las 3 pruebas del plugin (57, 58, 59) están verdes.',
            '',
            '**Cadena de cierres desde v77f hasta v77o:**',
            '',
            '- **v77f** (B2.3.5a): comando `app:repuntar_terminales_compartido`.',
            '  Cambia el enlace `dueno` de cada terminal para que apunte',
            '  a su compartido. Idempotente. Aplicado en producción.',
            '- **v77g** (B2.3.5b.1): el enrutador pasa contexto del',
            '  solicitante terminal en `estado_asientos`, `ventas/obtener`,',
            '  `ventas/cancelar`, `ventas/pagar_cupon`, `ventas/info_cancelacion`.',
            '- **v77h** (B2.3.5b.2): contexto en `cambiar_asiento_pasaje`',
            '  y en reservas.',
            '- **v77i** (B2.3.5b.3): contexto en empresas, vehículos,',
            '  pasajeros.',
            '- **v77j**: fix de zona horaria (`date_default_timezone_set(`',
            '  \'America/Argentina/Buenos_Aires\')`). Bug latente desde v58,',
            '  hacía que el autocompletado por DNI dijera "Fecha de',
            '  actualización inválida" entre las 21 y 24 hora argentina.',
            '- **v77k** (B2.3.5c.1, framework 1.5i.7m): `grafo:nodo`',
            '  agrega `destino_es_compartido` a cada adyacente. Permite',
            '  verificar topología con una sola consulta.',
            '- **v77l** (B2.3.5c.2 + c.3): fix del flujo de alta de',
            '  terminal. Nuevo helper `_asegurar_compartido_y_repuntar`',
            '  en `Usuario.php` que crea el compartido y repunta el',
            '  enlace `dueno` al vuelo. `app:repuntar_terminales_compartido`',
            '  reescrito para iterar sobre terminales (no sobre',
            '  compartidos existentes).',
            '- **v77m**: fix del `DivisionByZeroError` en',
            '  `_crear_lista_cupones_venta` cuando `cuotas = 1` y',
            '  `monto_pagado < total`. Validación temprana en',
            '  `confirmar_venta_actual` + defensivo en el helper.',
            '- **v77n**: descartado sin aplicar. Se había propuesto',
            '  `grafo:nodo_por_dato` para que las pruebas encontraran',
            '  nodos por dato. Descartado por el usuario: los datos',
            '  pueden repetirse, y todo recorrido debe partir de una',
            '  raíz.',
            '',
            '**Pruebas del plugin (B2.3.5b.4):**',
            '',
            '- **Prueba 57** (`enlace_dueno_apunta_al_compartido`):',
            '  verifica que el enlace `dueno` del terminal apunta a',
            '  un compartido. Usa `grafo:nodo` con el campo',
            '  `destino_es_compartido`.',
            '- **Prueba 58** (`venta_terminal_arbol_dual`): verifica',
            '  que una venta hecha por un terminal aparece en los',
            '  dos listados (`ventas/listar` tipo=dueno y tipo=terminal),',
            '  que navegan por los árboles paralelos.',
            '- **Prueba 59** (`cambiar_asiento_desde_croquis`): flujo',
            '  completo de cambiar de asiento desde el croquis.',
            '',
            '**Pendientes de la línea B:**',
            '',
            '- **B2.4** (próxima): mantener vivos los compartidos en',
            '  cada operación de escritura. Tanda grande, transversal.',
            '  Se puede partir por operación: alta/edición/baja de viaje,',
            '  micro, empresa, confirmar venta, cancelar venta, cambiar',
            '  asiento, reservar/liberar reserva, asignar pasajero.',
            '- **B3**: eliminar los 22 usos de `Nodo::nodo_por_id(\'usuarios\')`',
            '  que quedan. Los 6 del enrutador son los más delicados:',
            '  requieren que el enrutador empiece a pasar contexto',
            '  (B2.3.5. ya lo hace en las acciones del terminal, falta',
            '  en los chequeos de nivel).',
            '- **C** (opcional): tipos como IDs especiales.',
            '- **D**: carga parcial como optimización. Requiere Fase 4',
            '  del framework (`guardar_parcial` real con merge).',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.8 lecciones aprendidas
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.8 lecciones de la sesión',
        'buscar' => [
            '### 1.7 Deuda técnica',
        ],
        'reemplazar' => [
            '### 1.8 Lecciones aprendidas en esta sesión (v77f a v77o)',
            '',
            '**Sobre el framework y los IDs.**',
            '',
            '1. **Los IDs numéricos son referencias de memoria.**',
            '   Cambian entre cada carga del grafo. Solo los IDs',
            '   especiales (strings no numéricos) son estables entre',
            '   cargas. Nunca usar un ID numérico para "seguir" un',
            '   enlace entre dos llamadas POST distintas.',
            '2. **`grafo:nodo` devuelve IDs numéricos que solo son',
            '   válidos dentro de una misma carga.** Cada POST a',
            '   `grafo/nodo` recarga el grafo y regenera todos los',
            '   IDs numéricos. La tabla de equivalencias del framework',
            '   vive solo dentro de una carga.',
            '3. **Buscar nodos = recorrer desde una raíz. Siempre.**',
            '   No buscar por dato (los datos pueden repetirse),',
            '   no buscar por ID numérico. La única forma robusta',
            '   es navegar desde una raíz dentro de una sola carga.',
            '4. **Un comando del estilo `nodo_por_dato` es frágil.**',
            '   Aunque hoy los datos sean únicos (por ejemplo, los',
            '   `id_venta` tienen timestamp), mañana pueden repetirse.',
            '   Invita a construir cosas frágiles encima. Descartado.',
            '',
            '**Sobre el flujo de alta y los compartidos.**',
            '',
            '5. **Las migraciones que iteran sobre "lo que ya existe"',
            '   no cubren los elementos creados después.**',
            '   `app:repuntar_terminales_compartido` (v77f) iteraba',
            '   sobre los compartidos ya existentes del dueño, no',
            '   sobre los terminales. Un terminal creado después',
            '   quedaba sin compartido y sin repuntar, silenciosamente.',
            '   Fix (v77l): iterar sobre la entidad fuente (terminales),',
            '   no sobre el derivado (compartidos).',
            '6. **Los flujos de alta deben mantener la topología al',
            '   vuelo.** Si la topología se mantiene solo con comandos',
            '   manuales, cada alta que no los corre deja el grafo',
            '   inconsistente. Fix (v77l): `_asegurar_compartido_y_repuntar`',
            '   se llama desde `agregar_usuario` y `actualizar_usuario`.',
            '',
            '**Sobre validaciones en el backend.**',
            '',
            '7. **Los flujos sin sentido de negocio deben rechazarse',
            '   temprano.** El `DivisionByZeroError` en',
            '   `_crear_lista_cupones_venta` con `cuotas=1` y',
            '   `monto_pagado < total` era un caso imposible para el',
            '   usuario (el frontend bloquea el input), pero el',
            '   backend no lo chequeaba. Fix (v77m): validación',
            '   temprana + defensivo en el helper.',
            '',
            '**Sobre zonas horarias.**',
            '',
            '8. **El servidor y el navegador deben compartir zona',
            '   horaria.** Sin `date_default_timezone_set`, PHP usa',
            '   UTC. Entre las 21 y las 24 hora argentina, `date(\'Y-m-d\')`',
            '   queda un día adelantado respecto del navegador y rompe',
            '   comparaciones de fechas del lado del frontend.',
            '   Fix (v77j): `date_default_timezone_set(`',
            '   \'America/Argentina/Buenos_Aires\')` en `index.php`.',
            '',
            '**Sobre pruebas del plugin (JS).**',
            '',
            '9. **Verificar topología entre POSTs requiere navegación,',
            '   no seguimiento de IDs.** Las pruebas 57 y 58 fallaron',
            '   al principio porque seguían IDs numéricos entre',
            '   consultas. La verificación debe ser: una sola llamada',
            '   que navegue desde la raíz y devuelva todo lo necesario',
            '   para comparar.',
            '10. **`ventas/listar` ya navega los árboles paralelos.**',
            '    Con `tipo=dueno` recorre `privado/ventas` con',
            '    `hmi`/`hd`/`p`. Con `tipo=terminal` recorre el',
            '    compartido con `hmi_<term>`/`hd_<term>`/`p_<term>`.',
            '    Verificar pertenencia = buscar el `id_venta` en los',
            '    dos listados.',
            '11. **En modales apilados, acotar selectores a',
            '    `#modal_apilado_contenido`.** El croquis del modal y',
            '    el del viaje de atrás comparten clase `.seat`. Sin',
            '    acotar, `querySelector` devuelve el primero en el DOM',
            '    (el de atrás) y el click no hace nada útil.',
            '12. **Chequear efecto visual (`.seat-elegido`) antes que',
            '    atributo booleano `disabled`.** `getAttribute(\'disabled\')`',
            '    puede devolver `[null]`, `[""]` o `["disabled"]` según',
            '    cómo se serialice. La presencia o ausencia de una',
            '    clase es más confiable.',
            '13. **No pisar el monto pagado con cuotas = 1.** El',
            '    frontend autocompleta el input con el total y lo deja',
            '    `disabled`. Forzarlo a un valor menor dispara el',
            '    rechazo del backend introducido en v77m.',
            '14. **Polling activo en lugar de pausas fijas.** El',
            '    modal de cambiar asiento hace 2 fetches en serie',
            '    (estado + configuración) antes de armar el croquis.',
            '    En un grafo grande puede tardar varios segundos.',
            '',
            '### 1.7 Deuda técnica',
        ],
    ],

    // ============================================================
    // prompt_framework_iteradores.md — historial 1.5i.7m
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar 1.5i.7m al historial',
        'buscar' => [
            '- **1.5i.7l**: comando `grafo:raices` agregado al framework',
        ],
        'reemplazar' => [
            '- **1.5i.7m**: `grafo:nodo` agrega el campo',
            '  `destino_es_compartido` a cada adyacente. Se calcula',
            '  con `$ady->adyacente(\'_es_compartido\') !== null`, que',
            '  es O(1) porque el nodo destino ya está en memoria.',
            '  Permite verificar topología por topología (por ejemplo,',
            '  "el enlace `dueno` del terminal apunta al compartido")',
            '  con una sola consulta, sin seguir IDs numéricos (que',
            '  no son estables entre cargas).',
            '- **1.5i.7l**: comando `grafo:raices` agregado al framework',
        ],
    ],

    // ============================================================
    // prompt_framework_iteradores.md — lecciones §13
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar lecciones 28-29',
        'buscar' => [
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
        'reemplazar' => [
            '**Nodos, IDs y navegación:**',
            '',
            '28. **Los IDs numéricos son referencias de memoria.**',
            '    Cambian entre cada carga del grafo. Solo los IDs',
            '    especiales (strings no numéricos) son estables entre',
            '    cargas. La tabla de equivalencias del framework vive',
            '    solo dentro de una carga.',
            '29. **Buscar nodos = navegar desde una raíz. Siempre.**',
            '    No buscar por dato (los datos pueden repetirse),',
            '    no buscar por ID numérico entre consultas. La única',
            '    forma robusta es recorrer desde una raíz dentro de',
            '    una sola carga del grafo. `grafo:nodo_por_dato` fue',
            '    propuesto y descartado por esta razón.',
            '',
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77o',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77n',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77o',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 7 reemplazo(s) en 3 archivo(s), 0 a crear.

[OK] prompts/plan_actual.md
[OK] prompts/prompt_framework_iteradores.md
[OK] index.php

=== Resumen ===
Bloques aplicados: 7
Archivos nuevos:   0

Listo.
Anotación: el bloque de §1.1 del plan_actual.md asume que la línea dice v1.5piloto.77d. En realidad esa sección se actualizó en varios momentos. Si el script falla en ese bloque, avisame y me fijamos qué dice actualmente.

Script B — Proyecto iteradoresJS/
php
<?php
/**
 * Aplicador de cambios — Proyecto iteradoresJS (plugin de pruebas).
 *
 * Tanda V1.5plugin.6f (cierre de documentación).
 *   - prompts/prompt_plugin_piloto.md: actualizar estado (59 pruebas),
 *     agregar 4 lecciones nuevas en §8, actualizar Discusión actual.
 *
 * Uso (parado en iteradoresJS/):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // prompt_plugin_piloto.md — §7 estado actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_plugin_piloto.md',
        'descripcion' => 'plugin: estado actual a 59 pruebas',
        'buscar' => [
            '**Proyecto en v1.5plugin.5w.** El esqueleto del plugin está',
            'armado y funcional, tiene 56 pruebas (base + autocompletado',
            '+ autenticación + puntos de venta + viajes + micros +',
            'ventas + empresas + vehículos + declaraciones juradas +',
            'grafo) y las agrupa en secciones.',
        ],
        'reemplazar' => [
            '**Proyecto en v1.5plugin.6f.** El esqueleto del plugin está',
            'armado y funcional, tiene 59 pruebas (base + autocompletado',
            '+ autenticación + puntos de venta + viajes + micros +',
            'ventas + empresas + vehículos + declaraciones juradas +',
            'grafo) y las agrupa en secciones.',
        ],
    ],

    // ============================================================
    // prompt_plugin_piloto.md — §8 lecciones nuevas
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_plugin_piloto.md',
        'descripcion' => 'plugin: agregar lecciones 47-52',
        'buscar' => [
            '46. **Verificar el nombre real de la acción antes de',
            '    escribir un POST.** `viajes/agregar` no existe en el',
            '    enrutador: la acción real es `viajes/guardar` (alta o',
            '    edición unificada, llama a `guardar_viaje_completo`).',
            '    Antes de escribir un test que toque el backend, leer',
            '    el `switch ($subaccion)` del módulo correspondiente',
            '    en `Aplicacion/Enrutador.php`. La sección 6 de este',
            '    prompt tiene los códigos de acceso, pero no las',
            '    acciones: para acciones, siempre leer el Enrutador.',
        ],
        'reemplazar' => [
            '46. **Verificar el nombre real de la acción antes de',
            '    escribir un POST.** `viajes/agregar` no existe en el',
            '    enrutador: la acción real es `viajes/guardar` (alta o',
            '    edición unificada, llama a `guardar_viaje_completo`).',
            '    Antes de escribir un test que toque el backend, leer',
            '    el `switch ($subaccion)` del módulo correspondiente',
            '    en `Aplicacion/Enrutador.php`. La sección 6 de este',
            '    prompt tiene los códigos de acceso, pero no las',
            '    acciones: para acciones, siempre leer el Enrutador.',
            '',
            '47. **Los IDs numéricos del piloto no son estables entre',
            '    POSTs.** El framework mantiene una tabla de equivalencias',
            '    solo dentro de una carga. Si un test hace dos POSTs',
            '    distintos (por ejemplo, dos `grafo/nodo`), el ID',
            '    numérico que devuelve el primero puede no existir en el',
            '    segundo, o apuntar a otro nodo. **Regla: verificar',
            '    topología entre llamadas siempre con navegación desde',
            '    una raíz en una sola consulta.** No seguir IDs, no',
            '    buscar por dato. Las pruebas 57 y 58 fallaron por',
            '    esto y se reescribieron.',
            '',
            '48. **`ventas/listar` ya navega los dos árboles paralelos',
            '    del piloto.** Con `tipo=dueno` y `nombre=<dueño>`,',
            '    recorre `usuarios → dueño → privado/ventas` con',
            '    `hmi`/`hd`/`p`. Con `tipo=terminal` y',
            '    `nombre=<terminal>`, resuelve el contexto del terminal',
            '    (post repuntado, el compartido) y recorre su árbol con',
            '    `hmi_<term>`/`hd_<term>`/`p_<term>`. La prueba 58',
            '    verifica pertenencia buscando el `id_venta` en los dos',
            '    listados.',
            '',
            '49. **En modales apilados, acotar selectores a',
            '    `#modal_apilado_contenido ...`.** El modal apilado',
            '    (por ejemplo, "Ver pasaje" → "Cambiar de asiento")',
            '    convive en el DOM con el modal genérico de atrás, que',
            '    también tiene su croquis con clase `.seat`. Sin',
            '    acotar, `querySelector(\'.seat\')` devuelve el primero',
            '    en el DOM (el de atrás) y el click dispara el flujo',
            '    de atrás (seleccionar asiento en el croquis del',
            '    viaje), no el del modal del cambio.',
            '',
            '50. **Chequear efecto visual antes que atributo booleano.**',
            '    `getAttribute(\'disabled\')` devuelve `[null]`, `[""]` o',
            '    `["disabled"]` según cómo el navegador haya serializado',
            '    el atributo — no hay un formato único. Es más robusto',
            '    chequear el efecto visible del cambio (por ejemplo,',
            '    que un asiento haya quedado con la clase',
            '    `.seat-elegido`, o que un botón haya cambiado su',
            '    `classList`), que un atributo booleano.',
            '',
            '51. **No pisar el monto pagado con `cuotas = 1`.** El',
            '    frontend del piloto autocompleta el input del monto',
            '    con el total y lo deja `disabled` cuando `cuotas = 1`.',
            '    Llamar a `setear_monto_pagado(1000)` con cuotas = 1',
            '    fuerza un valor menor al total y dispara el rechazo',
            '    del backend introducido en v1.5piloto.77m ("Con 1',
            '    sola cuota, el monto a pagar debe cubrir el total").',
            '    Regla: con `cuotas = 1`, no tocar el monto.',
            '',
            '52. **Polling activo en lugar de pausas fijas.** Los',
            '    modales del piloto hacen varios fetches en serie',
            '    antes de renderizar. Una pausa fija de 1.5s o 3s',
            '    puede quedarse corta con un grafo grande. Regla:',
            '    esperar activamente por el efecto deseado (que',
            '    aparezca el elemento, que se habilite el botón, que',
            '    la clase esperada esté presente) con un timeout',
            '    holgado (15-25s), y fallar con un mensaje claro si',
            '    no llega.',
        ],
    ],

    // ============================================================
    // prompt_plugin_piloto.md — Discusión actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_plugin_piloto.md',
        'descripcion' => 'plugin: discusión actual a 6f',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5plugin.5w',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5plugin.6f',
            '(cierre de la fase de pruebas del aislamiento por topología).',
            '',
            '**Estado actual.** El plugin tiene **59 pruebas**, agrupadas',
            'por sección. Las nuevas (57, 58, 59) verifican la fase',
            'B2.3.5 del piloto:',
            '',
            '- **57** (`enlace_dueno_apunta_al_compartido`): después del',
            '  repuntado, el enlace `dueno` de un terminal apunta a un',
            '  nodo con `_es_compartido`. Usa el campo `destino_es_compartido`',
            '  que expone `grafo:nodo` desde v1.5piloto.77k.',
            '- **58** (`venta_terminal_arbol_dual`): una venta hecha por',
            '  un terminal aparece tanto en `ventas/listar` tipo=dueno',
            '  (árbol del dueño) como tipo=terminal (árbol del compartido).',
            '- **59** (`cambiar_asiento_desde_croquis`): flujo completo',
            '  de cambiar de asiento desde el croquis, incluyendo el',
            '  modal apilado y la verificación por `ventas/obtener`.',
            '',
            '**Pendientes del plugin:**',
            '',
            '- Prueba espejo de "cambiar de asiento" desde la pestaña',
            '  Clientes (el flujo del `ver_detalle_pasaje_individual`',
            '  en `pasajeros.js`, v77c/d).',
            '- A medida que avance B2.4 y B3, agregar pruebas espejo',
            '  para las operaciones que mantienen vivos los compartidos.',
            '',
            '**Lecciones de esta sesión:** ver las 6 nuevas (47-52)',
            'agregadas en §8. Destacan: navegar siempre desde una raíz,',
            'no seguir IDs numéricos entre POSTs, acotar selectores a',
            '`#modal_apilado_contenido`, chequear efectos visuales en',
            'vez de atributos booleanos, no tocar el monto con cuotas=1,',
            'y usar polling activo en vez de pausas fijas.',
            '',
            'Antes: v1.5plugin.5w',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";
Log esperado:

text
=== Aplicador de cambios ===

[INFO] 3 reemplazo(s) en 1 archivo(s), 0 a crear.

[OK] prompts/prompt_plugin_piloto.md

=== Resumen ===
Bloques aplicados: 3
Archivos nuevos:   0

Listo.
Anotaciones sobre el script B:

El bloque de §7 asume que el texto dice v1.5plugin.5w con la descripción original. Si ya se actualizó a otra versión, avisame y ajusto.

El bloque de §8 asume que el último punto de las lecciones es el 46, con el texto exacto que me pasaste. Si cambió, ajusto.

El bloque de "Discusión actual" busca el encabezado **Última actualización de este prompt:** v1.5plugin.5w y agrega un párrafo de cierre arriba. Es el bloque más propenso a fallar porque el formato del prompt al final es largo. Si falla, avisame.

Commits sugeridos
Proyecto iteradores/:

text
V1.5piloto.77o: cierre de B2.3.5 y documentación de sesión

Framework:
- Controlador/Controlador.php: 1.5i.7m — grafo:nodo agrega el
  campo destino_es_compartido a cada adyacente.

Documentación:
- prompts/plan_actual.md: actualizar §1.1, tanda actual (v77o),
  agregar §1.8 con las lecciones aprendidas en esta sesión
  (IDs no estables, navegar desde raíz, migraciones que iteran
  sobre lo existente, validaciones tempranas, zonas horarias).
- prompts/prompt_framework_iteradores.md: agregar 1.5i.7m al
  historial y las lecciones 28-29 (IDs numéricos son
  referencias de memoria, buscar = navegar).
- index.php: bump.
Proyecto iteradoresJS/:

text
V1.5plugin.6f: cierre de la fase de pruebas de topología

Plugin:
- prompts/prompt_plugin_piloto.md: actualizar §7 (59 pruebas),
  agregar 6 lecciones nuevas (47-52) y actualizar la Discusión
  actual. Las lecciones nuevas cubren: IDs numéricos no
  estables entre POSTs, ventas/listar navega los árboles
  paralelos, acotar selectores en modales apilados, chequear
  efectos visuales en vez de atributos booleanos, no tocar el
  monto con cuotas=1, y polling activo en vez de pausas fijas.
Corré los dos scripts. Con esto el estado queda documentado y podés retomar desde plan_actual.md en cualquier momento.

