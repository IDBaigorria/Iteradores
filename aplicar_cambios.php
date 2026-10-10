<?php
/**
 * Aplicador de cambios — Proyecto iteradores (JS).
 *
 * Tanda V1.5piloto.77d (cambiar de asiento: refrescar modal de pasajero).
 *   - viajes-asientos.js: abrir_modal_cambiar_asiento acepta un 6to
 *     parámetro opcional `on_exito`, que se ejecuta tras el éxito.
 *   - pasajeros.js: al cambiar desde el detalle de un pasaje, el
 *     callback re-abre ver_detalle_pasaje_individual con el nuevo
 *     número de asiento.
 *   - aplicacion_GET.html: bumps ?v= a 1.5piloto.77d.
 *   - prompts/plan_actual.md: registro de la tanda.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // viajes-asientos.js — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: bump @version a 1.5piloto.77d',
        'buscar' => [
            ' * Asientos y pasaje del micro.',
            ' * @version 1.5piloto.77c',
        ],
        'reemplazar' => [
            ' * Asientos y pasaje del micro.',
            ' * @version 1.5piloto.77d',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — firma con parámetro on_exito opcional
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'abrir_modal_cambiar_asiento: aceptar on_exito opcional',
        'buscar' => [
            'async function abrir_modal_cambiar_asiento(nombre_dueno, nombre_viaje, nombre_micro, fila_origen, columna_origen) {',
        ],
        'reemplazar' => [
            'async function abrir_modal_cambiar_asiento(nombre_dueno, nombre_viaje, nombre_micro, fila_origen, columna_origen, on_exito) {',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — guardar on_exito en el estado del modal
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'abrir_modal_cambiar_asiento: guardar on_exito en el estado',
        'buscar' => [
            '    // Guardar estado del modal.',
            '    cambiar_asiento_datos_modal = {',
            '        dueno: nombre_dueno,',
            '        viaje: nombre_viaje,',
            '        micro: nombre_micro,',
            '        origen_fila: String(fila_origen),',
            '        origen_columna: String(columna_origen),',
            '        origen_numero: origen.numero,',
            '        origen_estado: origen.estado,',
            '        origen_venta_id: origen.venta_id || null,',
            '        origen_pasajero_dni: origen.pasajero ? origen.pasajero.dni : null,',
            '        micro_config: micro_data.configuracion,',
            '        destino: null,',
            '        dejar_reservado: origen.estado === \'reservado\'',
            '    };',
        ],
        'reemplazar' => [
            '    // Guardar estado del modal.',
            '    cambiar_asiento_datos_modal = {',
            '        dueno: nombre_dueno,',
            '        viaje: nombre_viaje,',
            '        micro: nombre_micro,',
            '        origen_fila: String(fila_origen),',
            '        origen_columna: String(columna_origen),',
            '        origen_numero: origen.numero,',
            '        origen_estado: origen.estado,',
            '        origen_venta_id: origen.venta_id || null,',
            '        origen_pasajero_dni: origen.pasajero ? origen.pasajero.dni : null,',
            '        micro_config: micro_data.configuracion,',
            '        destino: null,',
            '        dejar_reservado: origen.estado === \'reservado\',',
            '        // v77d: callback opcional que el llamador puede pasar',
            '        // para refrescar su propia UI tras el cambio (por',
            '        // ejemplo, re-abrir el detalle del pasajero con el',
            '        // asiento nuevo).',
            '        on_exito: (typeof on_exito === \'function\') ? on_exito : null',
            '    };',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — ejecutar on_exito tras el éxito
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'confirmar_cambiar_asiento: ejecutar on_exito tras el éxito',
        'buscar' => [
            '            cambiar_asiento_datos_modal = null;',
            '            cerrar_modal_apilado();',
            '',
            '            // Refrescar el croquis de atrás si estamos en el croquis.',
            '            if (typeof viaje_seleccionado !== \'undefined\' && viaje_seleccionado',
        ],
        'reemplazar' => [
            '            // v77d: guardar el callback antes de limpiar el',
            '            // estado, y ejecutarlo después de cerrar el modal',
            '            // apilado. Se usa para que el llamador pueda',
            '            // refrescar su UI (por ejemplo, el modal de detalle',
            '            // del pasajero en la pestaña Clientes).',
            '            const on_exito_snap = d.on_exito;',
            '            cambiar_asiento_datos_modal = null;',
            '            cerrar_modal_apilado();',
            '',
            '            if (typeof on_exito_snap === \'function\') {',
            '                try { on_exito_snap(snap); } catch (e) { console.error(e); }',
            '            }',
            '',
            '            // Refrescar el croquis de atrás si estamos en el croquis.',
            '            if (typeof viaje_seleccionado !== \'undefined\' && viaje_seleccionado',
        ],
    ],

    // ============================================================
    // pasajeros.js — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: bump @version a 1.5piloto.77d',
        'buscar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.77c',
        ],
        'reemplazar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.77d',
        ],
    ],

    // ============================================================
    // pasajeros.js — callback al cambiar de asiento
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'ver_detalle_pasaje_individual: pasar callback on_exito',
        'buscar' => [
            '            const nombre_micro_real = venta.micro_enlace || venta.micro;',
            '            abrir_modal_cambiar_asiento(',
            '                nombre_dueno,',
            '                venta.viaje,',
            '                nombre_micro_real,',
            '                asientoInfo.fila,',
            '                asientoInfo.columna',
            '            );',
        ],
        'reemplazar' => [
            '            const nombre_micro_real = venta.micro_enlace || venta.micro;',
            '            abrir_modal_cambiar_asiento(',
            '                nombre_dueno,',
            '                venta.viaje,',
            '                nombre_micro_real,',
            '                asientoInfo.fila,',
            '                asientoInfo.columna,',
            '                (info) => {',
            '                    // v77d: re-abrir el detalle con el asiento',
            '                    // nuevo. El modal apilado ya está cerrado,',
            '                    // así que el modal genérico se actualiza',
            '                    // con los datos frescos del backend.',
            '                    if (info && info.destino_numero) {',
            '                        ver_detalle_pasaje_individual(',
            '                            venta.id_venta,',
            '                            pasajero.dni,',
            '                            info.destino_numero',
            '                        );',
            '                    }',
            '                }',
            '            );',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bumps
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de viajes-asientos.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.77c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.77d"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de pasajeros.js',
        'buscar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.77c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.77d"></script>',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77d',
        'buscar' => [
            '**Tanda actual:** v77c (nueva funcionalidad: cambiar de',
            'asiento). Frontend.',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77d (cambiar de asiento: refrescar modal',
            'de pasajero).',
            '',
            '**v77d — refresco del modal de pasajero.** Al cambiar el',
            'asiento desde el detalle de un pasaje (pestaña Clientes), el',
            'modal genérico quedaba con el asiento viejo. `abrir_modal_cambiar_asiento`',
            'ahora acepta un 6to parámetro opcional `on_exito`, que se',
            'ejecuta tras confirmar el cambio. Desde `pasajeros.js` se',
            'pasa un callback que re-abre `ver_detalle_pasaje_individual`',
            'con el nuevo número de asiento. Desde el croquis no se pasa',
            'nada: sigue funcionando igual.',
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