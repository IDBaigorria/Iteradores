<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76z (fixes urgentes: atadura y modal post venta).
 *   - Aplicacion/ventas.js:
 *     - confirmar_venta_modal: mostrar el modal post venta ANTES
 *       de los refrescos. Envolver refrescos en try/catch.
 *     - _buscar_comprador_por_dni: chequear DNI stale después del
 *       fetch. Sin esto, un fetch viejo pisaba el estado nuevo.
 *     - _buscar_pasajero_por_dni: idem.
 *     - Listeners de DNI: activar la atadura antes del fetch.
 *   - aplicacion_GET.html: bump ?v= de ventas.js.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // ventas.js — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.76z',
        'buscar' => [
            ' * @version 1.5piloto.74e',
            ' */',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.76z',
            ' */',
        ],
    ],

    // ============================================================
    // ventas.js — listener del DNI del comprador (atadura síncrona)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: listener DNI comprador con atadura síncrona',
        'buscar' => [
            '        input_dni_comp_el.addEventListener(\'input\', function() {',
            '            const dni_norm = _normalizar_dni_input(this.value);',
            '            if (dni_norm.length >= 7 && dni_norm.length <= 8) {',
            '                if (window.comprador_autocompletado_dni !== dni_norm) {',
            '                    _buscar_comprador_por_dni();',
            '                } else {',
            '                    _verificar_atadura_por_dni();',
            '                }',
            '            } else {',
            '                window.comprador_autocompletado_dni = null;',
            '                _limpiar_aviso_comprador();',
            '                _verificar_atadura_por_dni();',
            '            }',
            '        });',
        ],
        'reemplazar' => [
            '        input_dni_comp_el.addEventListener(\'input\', function() {',
            '            const dni_norm = _normalizar_dni_input(this.value);',
            '            // Fix v76z: intentar la atadura ANTES del fetch.',
            '            // Así el badge aparece apenas el DNI coincide, sin',
            '            // esperar a la respuesta del servidor. Después el',
            '            // fetch puede confirmar o ajustar, pero no rompe',
            '            // lo que ya está activo.',
            '            _verificar_atadura_por_dni();',
            '            if (dni_norm.length >= 7 && dni_norm.length <= 8) {',
            '                if (window.comprador_autocompletado_dni !== dni_norm) {',
            '                    _buscar_comprador_por_dni();',
            '                }',
            '            } else {',
            '                window.comprador_autocompletado_dni = null;',
            '                _limpiar_aviso_comprador();',
            '            }',
            '        });',
        ],
    ],

    // ============================================================
    // ventas.js — _disparar_busqueda_por_dni con atadura síncrona
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _disparar_busqueda_por_dni con atadura síncrona',
        'buscar' => [
            'function _disparar_busqueda_por_dni(valor, index) {',
            '    const dni_norm = _normalizar_dni_input(valor);',
            '    const estado = window.pasajeros_autocompletado_estado[index];',
            '',
            '    if (dni_norm.length < 7 || dni_norm.length > 8) {',
            '        if (estado && estado.dni_buscado && estado.dni_buscado !== dni_norm) {',
            '            _resetear_autocompletado_pasajero(index);',
            '        }',
            '        _verificar_atadura_por_dni();',
            '        return;',
            '    }',
            '',
            '    if (estado && estado.dni_buscado === dni_norm) return;',
            '',
            '    _buscar_pasajero_por_dni(index);',
            '}',
        ],
        'reemplazar' => [
            'function _disparar_busqueda_por_dni(valor, index) {',
            '    const dni_norm = _normalizar_dni_input(valor);',
            '    const estado = window.pasajeros_autocompletado_estado[index];',
            '',
            '    // Fix v76z: intentar la atadura ANTES del fetch. Así el',
            '    // badge aparece apenas el DNI coincide, sin esperar al',
            '    // servidor.',
            '    _verificar_atadura_por_dni();',
            '',
            '    if (dni_norm.length < 7 || dni_norm.length > 8) {',
            '        if (estado && estado.dni_buscado && estado.dni_buscado !== dni_norm) {',
            '            _resetear_autocompletado_pasajero(index);',
            '        }',
            '        return;',
            '    }',
            '',
            '    if (estado && estado.dni_buscado === dni_norm) return;',
            '',
            '    _buscar_pasajero_por_dni(index);',
            '}',
        ],
    ],

    // ============================================================
    // ventas.js — _buscar_comprador_por_dni con chequeo stale
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _buscar_comprador_por_dni con chequeo stale',
        'buscar' => [
            '    _mostrar_aviso_comprador(\'Buscando...\', \'gris\');',
            '',
            '    try {',
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "pasajeros/obtener",',
            '                dni: dni_norm,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '',
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_comprador(datos.pasajero);',
            '            window.comprador_dni_con_datos = dni_norm;',
            '            const antiguedad = _calcular_antiguedad_datos(datos.pasajero.fecha_ultima_modificacion);',
            '            _mostrar_aviso_comprador(antiguedad.texto, antiguedad.clase);',
            '        } else {',
            '            // Si antes se habian autocompletado datos para otro DNI',
            '            // y ahora el DNI cambio por uno no registrado, limpiar',
            '            // los campos del comprador para no arrastrar los datos',
            '            // del DNI anterior.',
            '            if (window.comprador_dni_con_datos && window.comprador_dni_con_datos !== dni_norm) {',
            '                _limpiar_campos_comprador();',
            '                window.comprador_dni_con_datos = null;',
            '            }',
            '            _mostrar_aviso_comprador(\'DNI no registrado. Complete los datos.\', \'gris\');',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando comprador:", e);',
            '        _limpiar_aviso_comprador();',
            '    }',
            '',
            '    _verificar_atadura_por_dni();',
            '}',
        ],
        'reemplazar' => [
            '    _mostrar_aviso_comprador(\'Buscando...\', \'gris\');',
            '',
            '    try {',
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "pasajeros/obtener",',
            '                dni: dni_norm,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '',
            '        // Fix v76z: si el DNI del input ya cambió mientras el',
            '        // fetch estaba en vuelo, descartar la respuesta. Sin',
            '        // esto, un fetch viejo pisaba el estado nuevo y rompía',
            '        // la atadura recién activada.',
            '        if (_normalizar_dni_input(input.value) !== dni_norm) return;',
            '',
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_comprador(datos.pasajero);',
            '            window.comprador_dni_con_datos = dni_norm;',
            '            const antiguedad = _calcular_antiguedad_datos(datos.pasajero.fecha_ultima_modificacion);',
            '            _mostrar_aviso_comprador(antiguedad.texto, antiguedad.clase);',
            '        } else {',
            '            // Si antes se habian autocompletado datos para otro DNI',
            '            // y ahora el DNI cambio por uno no registrado, limpiar',
            '            // los campos del comprador para no arrastrar los datos',
            '            // del DNI anterior.',
            '            if (window.comprador_dni_con_datos && window.comprador_dni_con_datos !== dni_norm) {',
            '                _limpiar_campos_comprador();',
            '                window.comprador_dni_con_datos = null;',
            '            }',
            '            _mostrar_aviso_comprador(\'DNI no registrado. Complete los datos.\', \'gris\');',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando comprador:", e);',
            '        _limpiar_aviso_comprador();',
            '    }',
            '',
            '    // Idem antes de verificar la atadura.',
            '    if (_normalizar_dni_input(input.value) !== dni_norm) return;',
            '',
            '    _verificar_atadura_por_dni();',
            '}',
        ],
    ],

    // ============================================================
    // ventas.js — _buscar_pasajero_por_dni con chequeo stale
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _buscar_pasajero_por_dni con chequeo stale',
        'buscar' => [
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "pasajeros/obtener",',
            '                dni: dni_norm,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '',
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_pasajero(index, datos.pasajero);',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: true,',
            '                fecha_modificacion: datos.pasajero.fecha_ultima_modificacion || \'\'',
            '            };',
            '        } else {',
            '            // Limpiar los campos no-DNI antes de habilitar. Si el',
            '            // usuario habia autocompletado un DNI previo y ahora',
            '            // corrige por uno no registrado, los datos viejos no',
            '            // deben quedar ni propagarse por ligadura.',
            '            _limpiar_campos_pasajero(index);',
            '            _habilitar_campos_pasajero(index, true);',
            '            _mostrar_aviso_en_formulario(index, \'DNI no registrado. Complete los datos.\', \'gris\');',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            '                fecha_modificacion: \'\'',
            '            };',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando pasajero:", e);',
            '        _limpiar_aviso_en_formulario(index);',
            '    }',
            '',
            '    _verificar_atadura_por_dni();',
            '}',
        ],
        'reemplazar' => [
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "pasajeros/obtener",',
            '                dni: dni_norm,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const datos = await resp.json();',
            '',
            '        // Fix v76z: descartar respuestas viejas si el DNI del',
            '        // input ya cambió mientras el fetch estaba en vuelo.',
            '        if (_normalizar_dni_input(inputDni.value) !== dni_norm) return;',
            '',
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_pasajero(index, datos.pasajero);',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: true,',
            '                fecha_modificacion: datos.pasajero.fecha_ultima_modificacion || \'\'',
            '            };',
            '        } else {',
            '            // Limpiar los campos no-DNI antes de habilitar. Si el',
            '            // usuario habia autocompletado un DNI previo y ahora',
            '            // corrige por uno no registrado, los datos viejos no',
            '            // deben quedar ni propagarse por ligadura.',
            '            _limpiar_campos_pasajero(index);',
            '            _habilitar_campos_pasajero(index, true);',
            '            _mostrar_aviso_en_formulario(index, \'DNI no registrado. Complete los datos.\', \'gris\');',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            '                fecha_modificacion: \'\'',
            '            };',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando pasajero:", e);',
            '        _limpiar_aviso_en_formulario(index);',
            '    }',
            '',
            '    // Idem antes de verificar la atadura.',
            '    if (_normalizar_dni_input(inputDni.value) !== dni_norm) return;',
            '',
            '    _verificar_atadura_por_dni();',
            '}',
        ],
    ],

    // ============================================================
    // ventas.js — confirmar_venta_modal: modal post venta primero
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: confirmar_venta_modal modal primero',
        'buscar' => [
            '        if (resultado.exito) {',
            '            mostrar_aviso("Venta confirmada correctamente", \'exito\');',
            '            ultima_venta_id = resultado.id_venta;',
            '',
            '            // Cerrar formulario y limpiar',
            '            $("#formulario_confirmacion_venta").classList.add("hidden");',
            '            $("#info_asiento_viaje").classList.remove("hidden");',
            '            venta_form_abierto = false;',
            '            _notificar_cambio_venta_en_curso();',
            '            await solicitar_estado_asientos();',
            '            mostrar_boton_confirmar_venta();',
            '            // Refrescar contadores sin reconstruir el modal: así no parpadea',
            '            // el croquis ni el panel de asiento.',
            '            await refrescar_contadores_viaje_actual();',
            '',
            '            if (ultima_venta_id) {',
            '                mostrar_opciones_impresion(ultima_venta_id);',
            '            }',
            '        } else {',
        ],
        'reemplazar' => [
            '        if (resultado.exito) {',
            '            mostrar_aviso("Venta confirmada correctamente", \'exito\');',
            '            ultima_venta_id = resultado.id_venta;',
            '',
            '            // Cerrar formulario y limpiar',
            '            $("#formulario_confirmacion_venta").classList.add("hidden");',
            '            $("#info_asiento_viaje").classList.remove("hidden");',
            '            venta_form_abierto = false;',
            '            _notificar_cambio_venta_en_curso();',
            '',
            '            // Fix v76z: mostrar el modal post venta PRIMERO,',
            '            // antes de los refrescos. Si los refrescos fallan,',
            '            // el modal ya está en pantalla. Antes, un fallo',
            '            // en solicitar_estado_asientos o en',
            '            // refrescar_contadores_viaje_actual hacía saltar al',
            '            // catch de afuera y el modal nunca se mostraba.',
            '            if (ultima_venta_id) {',
            '                mostrar_opciones_impresion(ultima_venta_id);',
            '            }',
            '',
            '            // Refrescos, cada uno en su try/catch para que un',
            '            // fallo no impida el flujo.',
            '            try {',
            '                await solicitar_estado_asientos();',
            '            } catch (e) {',
            '                console.error(\'Error refrescando asientos tras vender:\', e);',
            '            }',
            '            mostrar_boton_confirmar_venta();',
            '            try {',
            '                // Refrescar contadores sin reconstruir el modal:',
            '                // así no parpadea el croquis ni el panel de asiento.',
            '                await refrescar_contadores_viaje_actual();',
            '            } catch (e) {',
            '                console.error(\'Error refrescando contadores tras vender:\', e);',
            '            }',
            '        } else {',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump ventas.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET: bump ?v= de ventas.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.74e"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.76z"></script>',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76z',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76y',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76z',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v76z',
        'buscar' => [
            '**Tanda actual:** v76y (fix de auto-detección de migraciones).',
            '',
            '**Bug detectado en v76x:** las funciones `detectar_*` devolvían',
            '`true` por vacío (cuando no había nada que migrar). Como el',
            'listado de migraciones auto-crea el testigo cuando la',
            'detección da true, las migraciones quedaban marcadas como',
            '"Aplicada" aunque no se hubieran corrido. Se arregla en v76y:',
            'las funciones devuelven `false` si hay algo que migrar y falta.',
            'Nuevo comando `app:migracion_limpiar_marcadores` y botón',
            '"Re-detectar" en la pestaña Grafo para limpiar testigos mal',
            'puestos. Pendiente de aplicar B2.3.3 en serio.',
        ],
        'reemplazar' => [
            '**Tanda actual:** v76z (fixes urgentes en el frontend del',
            'formulario de venta). Dos bugs detectados en producción:',
            '',
            '1. **Atadura comprador-pasajero.** El badge "Vinculado"',
            '   aparecía un instante y desaparecía. Causa: el listener',
            '   del DNI disparaba varios fetches en paralelo, y uno',
            '   viejo pisaba el estado nuevo. Fix: chequear que el DNI',
            '   del input no haya cambiado cuando vuelve el fetch; si',
            '   cambió, descartar la respuesta. Además, activar la',
            '   atadura síncronamente antes del fetch.',
            '2. **Modal post venta.** No aparecía. Causa:',
            '   `confirmar_venta_modal` hacía dos `await` antes de',
            '   llamar `mostrar_opciones_impresion`. Si cualquiera',
            '   fallaba, el flujo saltaba al `catch` y el modal nunca',
            '   se mostraba. Fix: mostrar el modal primero y envolver',
            '   los refrescos en try/catch individual.',
            '',
            '**Bug detectado en v76x (sigue pendiente):** las funciones',
            '`detectar_*` devolvían `true` por vacío. Se arregla en v76y.',
            'Testigos mal puestos se limpian con "Re-detectar".',
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