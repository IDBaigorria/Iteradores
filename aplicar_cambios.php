<?php
/**
 * Aplicador de cambios — Proyecto iteradores (JS).
 *
 * Tanda V1.5piloto.77c (cambiar de asiento: frontend).
 *   - viajes-asientos.js: nueva función abrir_modal_cambiar_asiento,
 *     helpers _puede_cambiar_asiento y _es_destino_valido, y botón
 *     "Cambiar de asiento" en ver_pasaje_asiento.
 *   - pasajeros.js: botón "Cambiar de asiento" en
 *     ver_detalle_pasaje_individual.
 *   - estilos-viajes.css: estilos .seat-elegible, .seat-elegido,
 *     .seat-origen, .seat-deshabilitado.
 *   - aplicacion_GET.html: bumps ?v= de los 3 archivos afectados.
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
        'descripcion' => 'viajes-asientos.js: bump @version a 1.5piloto.77c',
        'buscar' => [
            ' * Asientos y pasaje del micro.',
            ' * @version 1.5piloto.76h',
        ],
        'reemplazar' => [
            ' * Asientos y pasaje del micro.',
            ' * @version 1.5piloto.77c',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — botón "Cambiar de asiento" en ver_pasaje_asiento
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'ver_pasaje_asiento: agregar botón Cambiar de asiento',
        'buscar' => [
            '    if (asiento.venta_id) {',
            '        html += `<div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="venta" data-venta-id="${asiento.venta_id}" data-dni="${p.dni || \'\'}">Imprimir pasaje</button>',
            '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
            '        </div>`;',
            '    } else {',
            '        html += `<div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="reserva" data-dueno="${nd}" data-viaje="${nv}" data-micro="${nm}" data-fila="${fila}" data-columna="${columna}">Imprimir pasaje</button>',
            '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
            '        </div>`;',
            '    }',
        ],
        'reemplazar' => [
            '    // v77c: botón "Cambiar de asiento" si el usuario tiene permiso.',
            '    const puede_cambiar = _puede_cambiar_asiento(asiento);',
            '    const btn_cambiar_html = puede_cambiar',
            '        ? `<button class="btn" id="btn_cambiar_asiento_pasaje">Cambiar de asiento</button>`',
            '        : \'\';',
            '    if (asiento.venta_id) {',
            '        html += `<div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="venta" data-venta-id="${asiento.venta_id}" data-dni="${p.dni || \'\'}">Imprimir pasaje</button>',
            '            ${btn_cambiar_html}',
            '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
            '        </div>`;',
            '    } else {',
            '        html += `<div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="reserva" data-dueno="${nd}" data-viaje="${nv}" data-micro="${nm}" data-fila="${fila}" data-columna="${columna}">Imprimir pasaje</button>',
            '            ${btn_cambiar_html}',
            '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
            '        </div>`;',
            '    }',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — listener del botón (después del de imprimir)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'ver_pasaje_asiento: listener del botón Cambiar de asiento',
        'buscar' => [
            '    const btnCerrar = document.getElementById(\'btn_cerrar_ver_pasaje\');',
            '    if (btnCerrar) {',
            '        btnCerrar.addEventListener(\'click\', cerrar_modal_apilado);',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '    // v77c: listener del botón "Cambiar de asiento".',
            '    const btnCambiar = document.getElementById(\'btn_cambiar_asiento_pasaje\');',
            '    if (btnCambiar) {',
            '        btnCambiar.addEventListener(\'click\', () => {',
            '            abrir_modal_cambiar_asiento(nd, nv, nm, fila, columna);',
            '        });',
            '    }',
            '',
            '    const btnCerrar = document.getElementById(\'btn_cerrar_ver_pasaje\');',
            '    if (btnCerrar) {',
            '        btnCerrar.addEventListener(\'click\', cerrar_modal_apilado);',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // viajes-asientos.js — nuevas funciones al final del archivo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: agregar funciones de cambiar asiento al final',
        'buscar' => [
            'window.actualizar_bloqueo_botones_asientos = actualizar_bloqueo_botones_asientos;',
        ],
        'reemplazar' => [
            'window.actualizar_bloqueo_botones_asientos = actualizar_bloqueo_botones_asientos;',
            '',
            '// ============================================================',
            '// v77c: cambiar de asiento de un pasaje.',
            '//',
            '// El modal pide los datos al backend (estado de asientos +',
            '// configuración del micro), arma el croquis, deja elegir un',
            '// asiento nuevo y hace el POST a `viajes/cambiar_asiento`.',
            '// Funciona desde el croquis (viajes-asientos.js) y desde la',
            '// pestaña Clientes (pasajeros.js).',
            '// ============================================================',
            '',
            '// Estado del modal de cambiar asiento.',
            'let cambiar_asiento_datos_modal = null;',
            '',
            '/**',
            ' * Determina si el usuario actual puede cambiar el asiento dado.',
            ' * Reglas:',
            ' *  - Dueño/admin/soporte: puede mover vendidos y reservados.',
            ' *  - Terminal: solo puede mover asientos vendidos por él mismo.',
            ' */',
            'function _puede_cambiar_asiento(asiento) {',
            '    if (!asiento) return false;',
            '    if (!asiento.tiene_pasajero) return false;',
            '    if (asiento.estado !== \'vendido\' && asiento.estado !== \'reservado\') return false;',
            '',
            '    if (usuario_actual.nivel === \'dueno\' || es_admin_o_soporte()) return true;',
            '',
            '    if (usuario_actual.nivel === \'terminal\') {',
            '        if (asiento.estado === \'reservado\') return false;',
            '        return asiento.venta_terminal === usuario_actual.nombre_usuario;',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Determina si un asiento es válido como destino del cambio.',
            ' *',
            ' * @param {object} a            Asiento candidato (estado + tiene_pasajero).',
            ' * @param {string} estado_origen \'vendido\' o \'reservado\'.',
            ' * @param {boolean} es_terminal  Si el usuario actual es terminal.',
            ' */',
            'function _es_destino_valido(a, estado_origen, es_terminal) {',
            '    if (!a) return false;',
            '    if (a.tiene_pasajero === true) return false;',
            '    if (a.estado === \'vendido\') return false;',
            '    if (a.estado === \'seleccionado\') return false;',
            '    if (a.estado === \'no disponible\') return false;',
            '',
            '    if (es_terminal) {',
            '        return a.estado === \'libre\';',
            '    }',
            '    return a.estado === \'libre\' || a.estado === \'reservado\';',
            '}',
            '',
            '/**',
            ' * Abre el modal de cambio de asiento.',
            ' *',
            ' * @param {string} nombre_dueno',
            ' * @param {string} nombre_viaje',
            ' * @param {string} nombre_micro',
            ' * @param {string} fila_origen',
            ' * @param {string} columna_origen',
            ' */',
            'async function abrir_modal_cambiar_asiento(nombre_dueno, nombre_viaje, nombre_micro, fila_origen, columna_origen) {',
            '    if (_venta_en_curso()) {',
            '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de cambiar un asiento.\', \'error\');',
            '        return;',
            '    }',
            '',
            '    // Fetch de estado de asientos.',
            '    let estados = [];',
            '    try {',
            '        const r = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "viajes/estado_asientos",',
            '                nombre_viaje,',
            '                nombre_micro,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const d = await r.json();',
            '        if (!d.exito || !Array.isArray(d.asientos)) {',
            '            mostrar_aviso(d.error || \'No se pudo cargar el estado de asientos\', \'error\');',
            '            return;',
            '        }',
            '        estados = d.asientos;',
            '    } catch (e) {',
            '        console.error(\'Error cargando asientos:\', e);',
            '        mostrar_aviso(\'Error de comunicación\', \'error\');',
            '        return;',
            '    }',
            '',
            '    // Fetch de configuración del micro.',
            '    let micro_data = null;',
            '    try {',
            '        const r = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "viajes/obtener_micro",',
            '                nombre_viaje,',
            '                nombre_micro,',
            '                nombre_dueno',
            '            })',
            '        });',
            '        const d = await r.json();',
            '        if (!d.exito || !d.micro) {',
            '            mostrar_aviso(d.error || \'No se pudo cargar el micro\', \'error\');',
            '            return;',
            '        }',
            '        micro_data = d.micro;',
            '    } catch (e) {',
            '        console.error(\'Error cargando micro:\', e);',
            '        mostrar_aviso(\'Error de comunicación\', \'error\');',
            '        return;',
            '    }',
            '',
            '    const origen = estados.find(a => String(a.fila) === String(fila_origen) && String(a.columna) === String(columna_origen));',
            '    if (!origen) {',
            '        mostrar_aviso(\'No se encontró el asiento actual\', \'error\');',
            '        return;',
            '    }',
            '    if (!origen.tiene_pasajero) {',
            '        mostrar_aviso(\'El asiento actual no tiene pasajero asignado\', \'error\');',
            '        return;',
            '    }',
            '',
            '    const es_terminal = usuario_actual.nivel === \'terminal\';',
            '',
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
            '',
            '    // Armar croquis.',
            '    let croquis_html = \'\';',
            '    const configuracion = micro_data.configuracion || {};',
            '    if (configuracion.pisos && configuracion.pisos.length > 0) {',
            '        configuracion.pisos.forEach((piso, index) => {',
            '            croquis_html += `<div class="section-title">Piso ${index + 1}</div>`;',
            '            croquis_html += \'<div class="bus"><div class="bus-front">FRENTE · CONDUCTOR</div>\';',
            '            for (let f = 1; f <= piso.filas; f++) {',
            '                croquis_html += \'<div class="seat-row">\';',
            '                for (let c = 1; c <= piso.columnas; c++) {',
            '                    const a = piso.asientos.find(x => parseInt(x.fila) === f && parseInt(x.columna) === c);',
            '                    if (a) {',
            '                        const a_estado = estados.find(e => String(e.fila) === String(a.fila) && String(e.columna) === String(a.columna));',
            '                        const a_estado_str = a_estado ? a_estado.estado : \'libre\';',
            '                        const a_tiene_pas = a_estado ? (a_estado.tiene_pasajero === true) : false;',
            '                        const es_origen = (String(a.fila) === String(fila_origen) && String(a.columna) === String(columna_origen));',
            '                        const valido = !es_origen && _es_destino_valido({ estado: a_estado_str, tiene_pasajero: a_tiene_pas }, origen.estado, es_terminal);',
            '                        const clases = [\'seat\', `seat-${a_estado_str}`];',
            '                        if (es_origen) clases.push(\'seat-origen\');',
            '                        if (!valido && !es_origen) clases.push(\'seat-deshabilitado\');',
            '                        if (valido) clases.push(\'seat-elegible\');',
            '                        croquis_html += `<div class="${clases.join(\' \')}" data-fila="${a.fila}" data-columna="${a.columna}" data-numero="${a.numero}" data-valido="${valido ? \'1\' : \'0\'}" data-origen="${es_origen ? \'1\' : \'0\'}">${String(a.numero).padStart(2, \'0\')}</div>`;',
            '                    } else {',
            '                        croquis_html += \'<div class="aisle"></div>\';',
            '                    }',
            '                }',
            '                croquis_html += \'</div>\';',
            '            }',
            '            croquis_html += \'<div class="bus-back">PARTE TRASERA</div></div>\';',
            '        });',
            '    } else {',
            '        croquis_html = \'<p class="muted">No hay configuración de asientos.</p>\';',
            '    }',
            '',
            '    const checkbox_html = origen.estado === \'reservado\'',
            '        ? `<div class="field" style="margin-top:15px;">',
            '            <label><input type="checkbox" id="cambiar_dejar_reservado" checked> Dejar el asiento viejo reservado (sin pasajero).</label>',
            '           </div>`',
            '        : \'\';',
            '',
            '    const pasajero_nombre = origen.pasajero ? (origen.pasajero.nombre_completo || origen.pasajero.dni_visible || \'\') : \'\';',
            '',
            '    const html = `',
            '        <h3>Cambiar de asiento</h3>',
            '        <p class="muted">Pasajero: <b>${pasajero_nombre}</b><br>Asiento actual: <b>${origen.numero}</b></p>',
            '        <p class="muted small">Hacé click en el asiento nuevo. Los asientos elegibles están marcados.</p>',
            '        <div style="max-height: 55vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #fafafa;">',
            '            ${croquis_html}',
            '        </div>',
            '        <div class="legend" style="margin-top:10px;">',
            '            <div class="legend-item"><span class="swatch sw-free"></span> Libre</div>',
            '            <div class="legend-item"><span class="swatch sw-reserved"></span> Reservado sin pasajero</div>',
            '            <div class="legend-item"><span class="swatch sw-sold"></span> Vendido</div>',
            '        </div>',
            '        ${checkbox_html}',
            '        <div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary" id="btn_confirmar_cambiar_asiento" disabled>Confirmar cambio</button>',
            '            <button class="btn" id="btn_cancelar_cambiar_asiento">Cancelar</button>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_apilado(\'Cambiar de asiento\', html);',
            '',
            '    const cont = document.getElementById(\'modal_apilado_contenido\');',
            '    if (!cont) return;',
            '',
            '    cont.querySelectorAll(\'.seat\').forEach(seat => {',
            '        seat.addEventListener(\'click\', () => {',
            '            if (seat.dataset.valido !== \'1\') return;',
            '            cont.querySelectorAll(\'.seat-elegido\').forEach(s => s.classList.remove(\'seat-elegido\'));',
            '            seat.classList.add(\'seat-elegido\');',
            '            if (cambiar_asiento_datos_modal) {',
            '                cambiar_asiento_datos_modal.destino = {',
            '                    fila: seat.dataset.fila,',
            '                    columna: seat.dataset.columna,',
            '                    numero: seat.dataset.numero',
            '                };',
            '            }',
            '            const btn = cont.querySelector(\'#btn_confirmar_cambiar_asiento\');',
            '            if (btn) btn.disabled = false;',
            '        });',
            '    });',
            '',
            '    const chk = cont.querySelector(\'#cambiar_dejar_reservado\');',
            '    if (chk) {',
            '        chk.addEventListener(\'change\', () => {',
            '            if (cambiar_asiento_datos_modal) {',
            '                cambiar_asiento_datos_modal.dejar_reservado = chk.checked;',
            '            }',
            '        });',
            '    }',
            '',
            '    cont.querySelector(\'#btn_cancelar_cambiar_asiento\').addEventListener(\'click\', () => {',
            '        cambiar_asiento_datos_modal = null;',
            '        cerrar_modal_apilado();',
            '    });',
            '    cont.querySelector(\'#btn_confirmar_cambiar_asiento\').addEventListener(\'click\', _confirmar_cambiar_asiento);',
            '}',
            '',
            '/**',
            ' * Confirma el cambio de asiento. Hace el POST y maneja el',
            ' * resultado: refresca el croquis si corresponde, ofrece',
            ' * reimprimir el pasaje.',
            ' */',
            'async function _confirmar_cambiar_asiento() {',
            '    const d = cambiar_asiento_datos_modal;',
            '    if (!d || !d.destino) return;',
            '',
            '    const btn = document.getElementById(\'btn_confirmar_cambiar_asiento\');',
            '    if (btn) btn.disabled = true;',
            '',
            '    try {',
            '        const resp = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({',
            '                accion: "viajes/cambiar_asiento",',
            '                nombre_viaje: d.viaje,',
            '                nombre_micro: d.micro,',
            '                fila_vieja: d.origen_fila,',
            '                columna_vieja: d.origen_columna,',
            '                fila_nueva: d.destino.fila,',
            '                columna_nueva: d.destino.columna,',
            '                nombre_dueno: d.dueno,',
            '                nombre_solicitante: usuario_actual.nombre_usuario,',
            '                dejar_reservado_viejo: d.dejar_reservado ? \'1\' : \'0\'',
            '            })',
            '        });',
            '        const resultado = await resp.json();',
            '',
            '        if (resultado.exito) {',
            '            mostrar_aviso("Asiento cambiado", \'exito\');',
            '',
            '            // Snapshot antes de limpiar el estado.',
            '            const snap = {',
            '                estado: d.origen_estado,',
            '                dueno: d.dueno,',
            '                viaje: d.viaje,',
            '                micro: d.micro,',
            '                destino_fila: d.destino.fila,',
            '                destino_columna: d.destino.columna,',
            '                destino_numero: d.destino.numero,',
            '                pasajero_dni: d.origen_pasajero_dni',
            '            };',
            '',
            '            cambiar_asiento_datos_modal = null;',
            '            cerrar_modal_apilado();',
            '',
            '            // Refrescar el croquis de atrás si estamos en el croquis.',
            '            if (typeof viaje_seleccionado !== \'undefined\' && viaje_seleccionado',
            '                && viaje_seleccionado.nombre_viaje === snap.viaje',
            '                && typeof micro_seleccionado !== \'undefined\' && micro_seleccionado === snap.micro) {',
            '                try { await solicitar_estado_asientos(); } catch (e) { console.error(e); }',
            '                try { await refrescar_contadores_viaje_actual(); } catch (e) { console.error(e); }',
            '                if (typeof refrescar_info_asientos_propios === \'function\') {',
            '                    refrescar_info_asientos_propios(true);',
            '                }',
            '            }',
            '',
            '            // Ofrecer reimprimir el pasaje.',
            '            if (snap.estado === \'vendido\' && snap.pasajero_dni) {',
            '                if (typeof mostrar_modal_chico_impresion_pasajero === \'function\') {',
            '                    mostrar_modal_chico_impresion_pasajero(snap.pasajero_dni, snap.dueno);',
            '                }',
            '            } else if (snap.estado === \'reservado\') {',
            '                if (typeof mostrar_modal_chico_impresion_reserva === \'function\') {',
            '                    mostrar_modal_chico_impresion_reserva(',
            '                        snap.dueno,',
            '                        snap.viaje,',
            '                        snap.micro,',
            '                        snap.destino_fila,',
            '                        snap.destino_columna,',
            '                        snap.destino_numero',
            '                    );',
            '                }',
            '            }',
            '        } else {',
            '            mostrar_aviso(resultado.error || "No se pudo cambiar el asiento", \'error\');',
            '            if (btn) btn.disabled = false;',
            '        }',
            '    } catch (e) {',
            '        console.error("Error al cambiar asiento:", e);',
            '        mostrar_aviso("Error de comunicación", \'error\');',
            '        if (btn) btn.disabled = false;',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // pasajeros.js — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: bump @version a 1.5piloto.77c',
        'buscar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.74o',
        ],
        'reemplazar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.77c',
        ],
    ],

    // ============================================================
    // pasajeros.js — botón "Cambiar de asiento" en ver_detalle_pasaje_individual
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'ver_detalle_pasaje_individual: agregar botón Cambiar de asiento',
        'buscar' => [
            '    const viaje = venta;',
            '    const micro_nombre = venta.micro_nombre_visible || venta.patente || \'\';',
            '',
            '    const contenido = `',
        ],
        'reemplazar' => [
            '    const viaje = venta;',
            '    const micro_nombre = venta.micro_nombre_visible || venta.patente || \'\';',
            '',
            '    // v77c: ¿el usuario puede cambiar el asiento de este pasaje?',
            '    // Dueño/admin/soporte pueden; terminal solo si la venta es suya.',
            '    let puede_cambiar_asiento = false;',
            '    if (usuario_actual && venta) {',
            '        if (es_admin_o_soporte() || usuario_actual.nivel === \'dueno\') {',
            '            puede_cambiar_asiento = true;',
            '        } else if (usuario_actual.nivel === \'terminal\') {',
            '            puede_cambiar_asiento = (venta.terminal === usuario_actual.nombre_usuario);',
            '        }',
            '    }',
            '    const btn_cambiar_asiento_html = puede_cambiar_asiento',
            '        ? `<button class="btn btn_cambiar_asiento_individual">Cambiar de asiento</button>`',
            '        : \'\';',
            '',
            '    const contenido = `',
        ],
    ],

    // ============================================================
    // pasajeros.js — insertar botón en el HTML de acciones
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'ver_detalle_pasaje_individual: agregar botón al HTML',
        'buscar' => [
            '        <div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary imprimir_pasaje_individual" data-id="${id_venta}" data-dni="${pasajero.dni}">Imprimir pasaje</button>',
            '            <button class="btn volver_listado_pasajes" data-dni="${pasajero.dni}">Volver</button>',
            '        </div>',
            '    `;',
        ],
        'reemplazar' => [
            '        <div class="actions" style="margin-top:15px;">',
            '            <button class="btn primary imprimir_pasaje_individual" data-id="${id_venta}" data-dni="${pasajero.dni}">Imprimir pasaje</button>',
            '            ${btn_cambiar_asiento_html}',
            '            <button class="btn volver_listado_pasajes" data-dni="${pasajero.dni}">Volver</button>',
            '        </div>',
            '    `;',
        ],
    ],

    // ============================================================
    // pasajeros.js — listener del botón
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'ver_detalle_pasaje_individual: listener del botón Cambiar de asiento',
        'buscar' => [
            '    document.querySelector(\'.volver_listado_pasajes\').addEventListener(\'click\', function() {',
            '        // Volver al listado de ventas del pasajero original',
            '        const dniOriginal = this.dataset.dni;',
            '        ver_pasajes_pasajero(dniOriginal);',
            '    });',
            '}',
        ],
        'reemplazar' => [
            '    document.querySelector(\'.volver_listado_pasajes\').addEventListener(\'click\', function() {',
            '        // Volver al listado de ventas del pasajero original',
            '        const dniOriginal = this.dataset.dni;',
            '        ver_pasajes_pasajero(dniOriginal);',
            '    });',
            '',
            '    // v77c: listener del botón "Cambiar de asiento".',
            '    const btn_cambiar_ind = document.querySelector(\'.btn_cambiar_asiento_individual\');',
            '    if (btn_cambiar_ind) {',
            '        btn_cambiar_ind.addEventListener(\'click\', () => {',
            '            // El backend (v77b) devuelve `micro_enlace` con el',
            '            // nombre del nodo del micro dentro del contenedor',
            '            // `micros` del viaje. Si por alguna razón no viene,',
            '            // cae a `venta.micro` como último recurso.',
            '            const nombre_micro_real = venta.micro_enlace || venta.micro;',
            '            abrir_modal_cambiar_asiento(',
            '                nombre_dueno,',
            '                venta.viaje,',
            '                nombre_micro_real,',
            '                asientoInfo.fila,',
            '                asientoInfo.columna',
            '            );',
            '        });',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // estilos-viajes.css — estilos nuevos al final
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-viajes.css',
        'descripcion' => 'estilos-viajes.css: estilos del modal de cambiar de asiento',
        'buscar' => [
            '.dj-editor-textarea {',
            '    width: 100%;',
            '    font-family: \'Courier New\', monospace;',
            '    font-size: 12px;',
            '    line-height: 1.5;',
            '    padding: 8px;',
            '    box-sizing: border-box;',
            '    border: 1px solid var(--border);',
            '    border-radius: 4px;',
            '    resize: vertical;',
            '    min-height: 400px;',
            '}',
        ],
        'reemplazar' => [
            '.dj-editor-textarea {',
            '    width: 100%;',
            '    font-family: \'Courier New\', monospace;',
            '    font-size: 12px;',
            '    line-height: 1.5;',
            '    padding: 8px;',
            '    box-sizing: border-box;',
            '    border: 1px solid var(--border);',
            '    border-radius: 4px;',
            '    resize: vertical;',
            '    min-height: 400px;',
            '}',
            '',
            '/* ===== v1.5piloto.77c: modal de cambiar de asiento ===== */',
            '',
            '/* Asiento elegible como destino del cambio. */',
            '.seat-elegible {',
            '    cursor: pointer;',
            '    outline: 2px dashed #2196f3;',
            '    outline-offset: -3px;',
            '}',
            '.seat-elegible:hover {',
            '    outline: 2px solid #1565c0;',
            '    transform: scale(1.05);',
            '}',
            '',
            '/* Asiento elegido como destino (selección actual). */',
            '.seat-elegido {',
            '    outline: 3px solid #1565c0;',
            '    outline-offset: -3px;',
            '    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.35);',
            '}',
            '',
            '/* Asiento origen (el que se está cambiando). No se puede elegir. */',
            '.seat-origen {',
            '    opacity: 0.55;',
            '    cursor: default;',
            '}',
            '',
            '/* Asiento no elegible (vendido, seleccionado, con pasajero, etc.). */',
            '.seat-deshabilitado {',
            '    opacity: 0.35;',
            '    cursor: not-allowed;',
            '}',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bumps
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de estilos-viajes.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-viajes.css?v=1.5piloto.65">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-viajes.css?v=1.5piloto.77c">',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de viajes-asientos.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.76h"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.77c"></script>',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de pasajeros.js',
        'buscar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.74o"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.77c"></script>',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77c',
        'buscar' => [
            '**Tanda actual:** v77b (nueva funcionalidad: cambiar de',
            'asiento). Backend.',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77c (nueva funcionalidad: cambiar de',
            'asiento). Frontend.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: frontend completado',
        'buscar' => [
            '**Frontend pendiente (v77c):** botón "Cambiar de asiento"',
            'en los dos modales de detalle del pasaje, modal con croquis',
            'y selector de asientos disponibles.',
        ],
        'reemplazar' => [
            '**Frontend v77c:** botón "Cambiar de asiento" en los dos',
            'modales de detalle del pasaje (`ver_pasaje_asiento` en',
            '`viajes-asientos.js`, `ver_detalle_pasaje_individual` en',
            '`pasajeros.js`). Función compartida',
            '`abrir_modal_cambiar_asiento` en `viajes-asientos.js`. El',
            'modal hace 2 fetches (estado de asientos + configuración del',
            'micro), arma el croquis con asientos elegibles marcados,',
            'checkbox "Dejar el asiento viejo reservado" (solo si el viejo',
            'es reservado, tildado por defecto) y confirmación por POST.',
            'Después del éxito: refresca el croquis si estamos ahí y',
            'ofrece reimprimir el pasaje (modal chico de pasajero para',
            'vendido, modal chico de reserva para reservado).',
            '',
            '**Con esto queda cerrada la funcionalidad "cambiar de asiento".**',
            'Para activarla: correr el backend (v77b) y el frontend (v77c)',
            'y probar desde el croquis y desde la pestaña Clientes.',
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