/***
 * Funciones de la pestaña Rendiciones.
 * @version 1.5piloto.57b
 */

let rendiciones_actuales = [];
let dueno_rendiciones_seleccionado = '';

/**
 * Devuelve el nombre del dueño cuyas rendiciones se están viendo,
 * según el rol del usuario actual.
 */
function obtener_nombre_dueno_rendiciones() {
    if (!usuario_actual) return '';
    if (usuario_actual.nivel === 'admin') {
        const select = document.getElementById('selector_dueno_rendiciones');
        if (select && select.value) return select.value;
        return dueno_rendiciones_seleccionado || '';
    }
    if (usuario_actual.nivel === 'dueno') {
        return usuario_actual.nombre_usuario;
    }
    return '';
}

/**
 * Formatea un monto al estilo argentino.
 */
function _formatear_monto_rendiciones(monto) {
    const n = parseFloat(monto) || 0;
    return n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Carga la pestaña Rendiciones. Si el usuario es admin, muestra el
 * selector de dueño y espera a que elija uno.
 */
async function cargar_rendiciones() {
    const panelSelector = document.getElementById('contenedor_filtro_dueno_rendiciones');

    if (usuario_actual.nivel === 'admin') {
        if (panelSelector) panelSelector.style.display = 'block';
        const select = document.getElementById('selector_dueno_rendiciones');
        if (select && select.options.length <= 1) {
            await cargar_duenos_en_select_rendiciones();
        }
    } else {
        if (panelSelector) panelSelector.style.display = 'none';
    }

    const nombre_dueno = obtener_nombre_dueno_rendiciones();
    if (!nombre_dueno) {
        rendiciones_actuales = [];
        renderizar_tabla_rendiciones([]);
        renderizar_resumen_rendiciones([]);
        return;
    }

    await _cargar_rendiciones_con_dueno(nombre_dueno);
}

/**
 * Carga los dueños en el select del filtro. Solo admin.
 */
async function cargar_duenos_en_select_rendiciones() {
    const select = document.getElementById('selector_dueno_rendiciones');
    if (!select) return;

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_duenos" })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar dueños", 'error');
        return;
    }

    select.innerHTML = '<option value="">Seleccione dueño...</option>';
    datos.duenos.forEach(dueno => {
        const opcion = document.createElement('option');
        opcion.value = dueno.nombre_usuario;
        opcion.textContent = dueno.nombre_real
            ? `${dueno.nombre_real} (${dueno.nombre_usuario})`
            : dueno.nombre_usuario;
        select.appendChild(opcion);
    });

    select.onchange = () => {
        dueno_rendiciones_seleccionado = select.value;
        limpiar_filtros_rendiciones_sin_render();
        if (select.value) {
            _cargar_rendiciones_con_dueno(select.value);
        } else {
            rendiciones_actuales = [];
            renderizar_tabla_rendiciones([]);
            renderizar_resumen_rendiciones([]);
        }
    };
}

/**
 * Hace el fetch de rendiciones con los filtros actuales y renderiza.
 */
async function _cargar_rendiciones_con_dueno(nombre_dueno) {
    const filtros = _recolectar_filtros_rendiciones();
    const params = {
        accion: "rendiciones/listar",
        nombre_dueno,
        ...filtros
    };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(params)
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar rendiciones", 'error');
        return;
    }

    rendiciones_actuales = datos.rendiciones || [];
    await cargar_terminales_en_filtro_rendiciones(nombre_dueno);
    renderizar_tabla_rendiciones(rendiciones_actuales);
    renderizar_resumen_rendiciones(datos.saldos_dueno || null);
    renderizar_chips_rendiciones();
    renderizar_aviso_rendiciones_desactualizadas();

    // Mostrar el botón Liquidar solo al dueño.
    const btn_liquidar = document.getElementById('boton_liquidar');
    if (btn_liquidar) {
        btn_liquidar.style.display = (usuario_actual && usuario_actual.nivel === 'dueno') ? '' : 'none';
    }
}

/**
 * Carga las terminales del dueño en el select del filtro. Lo hace
 * solo la primera vez (o si el select está vacío).
 */
async function cargar_terminales_en_filtro_rendiciones(nombre_dueno) {
    const select = document.getElementById('rendicion_filtro_terminal');
    if (!select) return;
    if (select.options.length > 1) return;

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno })
        });
        const datos = await respuesta.json();
        if (!datos.exito) return;
        datos.terminales.forEach(t => {
            const opcion = document.createElement('option');
            opcion.value = t.nombre_usuario;
            opcion.textContent = t.nombre_real
                ? `${t.nombre_real} (${t.nombre_usuario})`
                : t.nombre_usuario;
            select.appendChild(opcion);
        });
    } catch (e) {
        console.error('Error al cargar terminales:', e);
    }
}

/**
 * Recolecta los filtros del panel de Rendiciones.
 */
function _recolectar_filtros_rendiciones() {
    return {
        codigo: (document.getElementById('rendicion_filtro_codigo')?.value || '').trim(),
        fecha_desde: document.getElementById('rendicion_filtro_desde')?.value || '',
        fecha_hasta: document.getElementById('rendicion_filtro_hasta')?.value || '',
        terminal: document.getElementById('rendicion_filtro_terminal')?.value || ''
    };
}

/**
 * Renderiza el header de la pestaña con los saldos actuales del
 * dueño (efectivo, banco, total). Se usa tanto en Rendiciones
 * como en Liquidaciones.
 *
 * @param {object} saldos {efectivo, banco, total}
 * @param {string} etiqueta Texto que precede a los montos (ej. "Saldo actual del dueño")
 */
function renderizar_resumen_saldos_dueno(saldos, etiqueta) {
    if (!saldos) saldos = { efectivo: '0.00', banco: '0.00', total: '0.00' };
    const etq = etiqueta || 'Saldo actual del dueño';
    return `
        <span class="resumen-rendiciones-item"><b>${etq}:</b></span>
        <span class="resumen-rendiciones-item">Efectivo $${_formatear_monto_rendiciones(saldos.efectivo)}</span>
        <span class="resumen-rendiciones-sep">·</span>
        <span class="resumen-rendiciones-item">Banco $${_formatear_monto_rendiciones(saldos.banco)}</span>
        <span class="resumen-rendiciones-sep">·</span>
        <span class="resumen-rendiciones-item"><b>Total $${_formatear_monto_rendiciones(saldos.total)}</b></span>
    `;
}

/**
 * Actualiza el resumen de la pestaña Rendiciones con los saldos
 * actuales del dueño. Recibe los saldos por parámetro.
 */
function renderizar_resumen_rendiciones(saldos) {
    const contenedor = document.getElementById('resumen_rendiciones');
    if (!contenedor) return;
    contenedor.innerHTML = renderizar_resumen_saldos_dueno(saldos, 'Saldo actual del dueño');
}

/**
 * Renderiza la tabla de rendiciones.
 */
function renderizar_tabla_rendiciones(rendiciones) {
    const contenedor = document.getElementById('tabla_rendiciones_container');
    if (!contenedor) return;

    if (!rendiciones || rendiciones.length === 0) {
        contenedor.innerHTML = '<p style="color:#888; margin:20px;">Sin rendiciones registradas para los filtros seleccionados.</p>';
        return;
    }

    let html = '<div class="table-wrap"><table class="data-table rendiciones-tabla">';
    html += '<thead><tr>';
    html += '<th>Código</th>';
    html += '<th>Fecha</th>';
    html += '<th>Terminales</th>';
    html += '<th>Cupones</th>';
    html += '<th>Efectivo</th>';
    html += '<th>Banco</th>';
    html += '<th>Total</th>';
    html += '<th>Estado</th>';
    html += '<th></th>';
    html += '</tr></thead><tbody>';

    rendiciones.forEach(r => {
        // El badge refleja tres estados:
        //  - Vigente: sin ajustes.
        //  - Desactualizada: con ajustes pendientes de aceptar.
        //  - Ajustada: con ajustes, pero todos ya aceptados.
        let badge;
        if (!r.desactualizada) {
            badge = '<span class="badge-vigente">Vigente</span>';
        } else if (r.pendiente_aceptar) {
            badge = '<span class="badge-desactualizada" title="' + (r.motivo_desactualizada || '') + '">Desactualizada</span>';
        } else {
            badge = '<span class="badge-ajustada" title="' + (r.motivo_desactualizada || '') + '">Ajustada</span>';
        }

        const terminales = (r.terminales || []).map(t => t.terminal_nombre_real || t.terminal);
        let terminales_txt = '';
        if (terminales.length === 0) {
            terminales_txt = '—';
        } else if (terminales.length <= 2) {
            terminales_txt = terminales.join(', ');
        } else {
            terminales_txt = terminales.slice(0, 2).join(', ') + ` y ${terminales.length - 2} más`;
        }

        html += '<tr>';
        html += `<td><strong>${r.id_rendicion}</strong></td>`;
        html += `<td>${r.fecha_hora}</td>`;
        html += `<td>${terminales_txt}</td>`;
        html += `<td class="num">${r.cantidad_cupones}</td>`;
        html += `<td class="num">$${_formatear_monto_rendiciones(r.total_efectivo)}</td>`;
        html += `<td class="num">$${_formatear_monto_rendiciones(r.total_banco)}</td>`;
        html += `<td class="num"><b>$${_formatear_monto_rendiciones(r.total)}</b></td>`;
        html += `<td>${badge}</td>`;
        html += `<td><button class="btn ver_detalle_rendicion" data-id="${r.id_rendicion}">Ver detalle</button></td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    contenedor.innerHTML = html;

    contenedor.querySelectorAll('.ver_detalle_rendicion').forEach(btn => {
        btn.addEventListener('click', () => ver_detalle_rendicion(btn.dataset.id));
    });
}

/**
 * Abre el modal con el detalle completo de una rendición.
 */
async function ver_detalle_rendicion(id_rendicion) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "rendiciones/obtener", id_rendicion })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener la rendición", 'error');
        return;
    }

    const r = datos.rendicion;

    // Cartel de desactualizada + sección de ajustes.
    const ajustes = Array.isArray(r.ajustes) ? r.ajustes : [];
    const pendientes = ajustes.filter(a => !a.aceptada);

    let cartel_desact = '';
    if (r.desactualizada) {
        const texto_principal = pendientes.length > 0
            ? 'Rendición con ajustes pendientes de aceptar.'
            : 'Rendición ajustada (todos los ajustes fueron aceptados).';
        cartel_desact = `
            <div class="rendicion-desactualizada-aviso">
                <strong>${texto_principal}</strong>
                <div>${r.motivo_desactualizada || ''}</div>
            </div>
        `;
    }

    // Sección de ajustes (uno por cancelación que afectó a esta rendición).
    let ajustes_html = '';
    if (ajustes.length > 0) {
        ajustes_html = ajustes.map(a => {
            const terminal_aj = a.terminal_nombre_real || a.terminal || '—';
            const monto_dueno = (parseFloat(a.monto_dueno_efectivo) || 0) + (parseFloat(a.monto_dueno_banco) || 0);
            const monto_terminal = (parseFloat(a.monto_terminal_efectivo) || 0) + (parseFloat(a.monto_terminal_banco) || 0);
            const no_cubierto = (parseFloat(a.monto_no_cubierto_efectivo) || 0) + (parseFloat(a.monto_no_cubierto_banco) || 0);

            const aceptado = !!a.aceptada;
            const boton_aceptar = (!aceptado && a.id_cancelacion)
                ? `<button class="btn primary btn_aceptar_ajuste" data-id-cancelacion="${a.id_cancelacion}" data-id-rendicion="${r.id_rendicion}">Aceptar ajuste</button>`
                : '';
            const boton_ver_informe = a.id_cancelacion
                ? `<button class="btn btn_ver_informe_cancelacion" data-id-cancelacion="${a.id_cancelacion}">Ver informe de cancelación</button>`
                : '';
            const badge = aceptado
                ? '<span class="badge-ajustada">Aceptado</span>'
                : '<span class="badge-desactualizada">Pendiente</span>';
            const motivo_txt = a.motivo ? `<div class="ajuste-motivo">Motivo: ${a.motivo}</div>` : '';

            return `
                <div class="ajuste-rendicion-bloque">
                    <div class="ajuste-header">
                        <div>
                            <strong>Venta ${a.id_venta}</strong>
                            <span class="ajuste-fecha">${a.fecha_hora}</span>
                        </div>
                        ${badge}
                    </div>
                    ${motivo_txt}
                    <div class="ajuste-lineas">
                        <div class="ajuste-linea"><span>Terminal:</span><b>${terminal_aj}</b></div>
                        <div class="ajuste-linea"><span>Devuelto desde la terminal:</span><b>$${_formatear_monto_rendiciones(monto_terminal)}</b></div>
                        <div class="ajuste-linea"><span>Devuelto desde el dueño:</span><b>$${_formatear_monto_rendiciones(monto_dueno)}</b></div>
                        ${no_cubierto > 0.001 ? `<div class="ajuste-linea ajuste-linea-alerta"><span>No cubierto:</span><b>$${_formatear_monto_rendiciones(no_cubierto)}</b></div>` : ''}
                        ${aceptado ? `<div class="ajuste-linea"><span>Aceptado el:</span><b>${a.aceptada_en}</b></div>` : ''}
                    </div>
                    <div class="ajuste-acciones">
                        ${boton_aceptar}
                        ${boton_ver_informe}
                    </div>
                </div>
            `;
        }).join('');

        ajustes_html = `
            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo">Ajustes por cancelaciones</div>
                <div class="ajustes-lista">${ajustes_html}</div>
            </div>
        `;
    }

    // Tabla por terminal.
    let terminales_html = '';
    if (r.detalle_terminales && r.detalle_terminales.length > 0) {
        terminales_html = r.detalle_terminales.map(t => `
            <tr>
                <td>${t.terminal_nombre_real || t.terminal}</td>
                <td class="num">${t.cantidad_cupones}</td>
                <td class="num">$${_formatear_monto_rendiciones(t.efectivo)}</td>
                <td class="num">$${_formatear_monto_rendiciones(t.banco)}</td>
                <td class="num"><b>$${_formatear_monto_rendiciones(t.total)}</b></td>
            </tr>
        `).join('');
    } else {
        terminales_html = '<tr><td colspan="5" style="text-align:center;color:#888;">Sin datos</td></tr>';
    }

    // Tabla de cupones.
    let cupones_html = '';
    if (r.detalle_cupones && r.detalle_cupones.length > 0) {
        cupones_html = r.detalle_cupones.map(c => {
            const metodo = c.metodo_pago === 'transferencia' ? 'Banco' : 'Efectivo';
            const nombre_terminal = c.terminal_nombre_real || c.terminal;
            return `
                <tr>
                    <td>${c.venta_id}</td>
                    <td class="num">${c.numero_cupon}</td>
                    <td class="num">$${_formatear_monto_rendiciones(c.monto)}</td>
                    <td>${metodo}</td>
                    <td>${nombre_terminal}</td>
                    <td><button class="btn ver_compra_desde_rendicion" data-id="${c.venta_id}">Ver compra</button></td>
                </tr>
            `;
        }).join('');
    } else {
        cupones_html = '<tr><td colspan="6" style="text-align:center;color:#888;">Sin datos</td></tr>';
    }

    const html = `
        <div class="rendicion-detalle">
            <div class="rendicion-detalle-header">
                <div class="rendicion-detalle-titulo">Rendición ${r.id_rendicion}</div>
                <div class="rendicion-detalle-fecha">Cerrada el ${r.fecha_hora}</div>
            </div>

            ${cartel_desact}

            ${ajustes_html}

            <div class="rendicion-resumen">
                <div class="rendicion-resumen-titulo">Resumen</div>
                <div class="rendicion-resumen-grid">
                    <div class="rendicion-resumen-item"><span>Total</span><b>$${_formatear_monto_rendiciones(r.total)}</b></div>
                    <div class="rendicion-resumen-item"><span>Efectivo</span><b>$${_formatear_monto_rendiciones(r.total_efectivo)}</b></div>
                    <div class="rendicion-resumen-item"><span>Banco</span><b>$${_formatear_monto_rendiciones(r.total_banco)}</b></div>
                    <div class="rendicion-resumen-item"><span>Cupones</span><b>${r.cantidad_cupones}</b></div>
                    <div class="rendicion-resumen-item"><span>Ventas</span><b>${r.cantidad_ventas}</b></div>
                </div>
            </div>

            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo">Por punto de venta</div>
                <div class="table-wrap"><table class="rendicion-tabla">
                    <thead><tr>
                        <th>Terminal</th><th>Cupones</th><th>Efectivo</th><th>Banco</th><th>Total</th>
                    </tr></thead>
                    <tbody>${terminales_html}</tbody>
                </table></div>
            </div>

            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo">Cupones rendidos</div>
                <div class="table-wrap"><table class="rendicion-tabla">
                    <thead><tr>
                        <th>Venta</th><th>Cupón</th><th>Monto</th><th>Método</th><th>Terminal</th><th></th>
                    </tr></thead>
                    <tbody>${cupones_html}</tbody>
                </table></div>
            </div>

            <div class="rendicion-acciones">
                <button class="btn primary" id="btn_imprimir_detalle_rendicion">Imprimir informe de rendición</button>
                <button class="btn" id="btn_cerrar_detalle_rendicion">Cerrar</button>
            </div>
        </div>
    `;

    abrir_modal_generico('Detalle de rendición', html);

    const cont = document.getElementById('modal_generico_contenido');
    cont.querySelector('#btn_cerrar_detalle_rendicion').addEventListener('click', cerrar_modal_generico);
    cont.querySelector('#btn_imprimir_detalle_rendicion').addEventListener('click', () => {
        const url = `index.php?imprimir=1&tipo=informe_rendicion&id_rendicion=${encodeURIComponent(r.id_rendicion)}`;
        window.open(url, '_blank');
    });

    cont.querySelectorAll('.ver_compra_desde_rendicion').forEach(btn => {
        btn.addEventListener('click', () => {
            const id_venta = btn.dataset.id;
            const nombre_dueno = obtener_nombre_dueno_rendiciones();
            ir_a_venta_en_vendidos(id_venta, nombre_dueno);
        });
    });

    cont.querySelectorAll('.btn_aceptar_ajuste').forEach(btn => {
        btn.addEventListener('click', () => {
            aceptar_ajuste_rendicion_ui(btn.dataset.idRendicion, btn.dataset.idCancelacion);
        });
    });

    cont.querySelectorAll('.btn_ver_informe_cancelacion').forEach(btn => {
        btn.addEventListener('click', () => {
            // Abre el modal apilado de detalle de cancelación que ya existe
            // en ventas.js. Adentro de ese modal está el botón "Imprimir informe".
            if (typeof ver_detalle_cancelacion === 'function') {
                ver_detalle_cancelacion(btn.dataset.idCancelacion);
            }
        });
    });
}

/**
 * Acepta un ajuste de rendición: manda al backend y refresca el
 * detalle de la rendición para reflejar el cambio.
 *
 * @param {string} id_rendicion
 * @param {string} id_cancelacion
 */
async function aceptar_ajuste_rendicion_ui(id_rendicion, id_cancelacion) {
    const confirmacion = confirm(
        'Vas a marcar este ajuste como aceptado.\n\n' +
        'Los saldos ya fueron ajustados al cancelar la venta; aceptar es solo dejar registro de que lo revisaste.\n\n' +
        '¿Confirmás?'
    );
    if (!confirmacion) return;

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "rendiciones/aceptar_ajuste",
            id_rendicion,
            id_cancelacion
        })
    });
    const resultado = await respuesta.json();
    if (!resultado.exito) {
        mostrar_aviso(resultado.error || 'No se pudo aceptar el ajuste', 'error');
        return;
    }

    mostrar_aviso('Ajuste aceptado', 'exito');

    // Refrescar el listado y reabrir el detalle para que se vea actualizado.
    const nombre_dueno = obtener_nombre_dueno_rendiciones();
    if (nombre_dueno) {
        await _cargar_rendiciones_con_dueno(nombre_dueno);
        ver_detalle_rendicion(id_rendicion);
    }
}

/**
 * Arma la tira de chips con los filtros activos de Rendiciones.
 */
function renderizar_chips_rendiciones() {
    const contenedor = document.getElementById('chips_filtros_activos_rendiciones');
    if (!contenedor) return;

    const chips = [];

    const codigo = (document.getElementById('rendicion_filtro_codigo')?.value || '').trim();
    if (codigo) chips.push({ id: 'codigo', texto: `Código: ${codigo}` });

    const desde = document.getElementById('rendicion_filtro_desde')?.value || '';
    if (desde) {
        const partes = desde.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : desde;
        chips.push({ id: 'fecha_desde', texto: `Desde: ${visible}` });
    }

    const hasta = document.getElementById('rendicion_filtro_hasta')?.value || '';
    if (hasta) {
        const partes = hasta.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : hasta;
        chips.push({ id: 'fecha_hasta', texto: `Hasta: ${visible}` });
    }

    const sel_terminal = document.getElementById('rendicion_filtro_terminal');
    if (sel_terminal && sel_terminal.value) {
        const txt = sel_terminal.options[sel_terminal.selectedIndex]?.textContent || sel_terminal.value;
        chips.push({ id: 'terminal', texto: `Terminal: ${txt}` });
    }

    if (chips.length === 0) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = chips.map(c => (
        `<span class="chip-filtro">${c.texto}<button type="button" class="chip-cerrar" data-chip="${c.id}" aria-label="Quitar filtro">✕</button></span>`
    )).join('');

    contenedor.querySelectorAll('.chip-cerrar').forEach(btn => {
        btn.addEventListener('click', () => {
            limpiar_filtro_rendicion_individual(btn.dataset.chip);
            _cargar_rendiciones_con_dueno(obtener_nombre_dueno_rendiciones());
        });
    });
}

/**
 * Resetea un filtro individual de Rendiciones.
 */
function limpiar_filtro_rendicion_individual(id) {
    switch (id) {
        case 'codigo': {
            const el = document.getElementById('rendicion_filtro_codigo');
            if (el) el.value = '';
            break;
        }
        case 'fecha_desde': {
            const el = document.getElementById('rendicion_filtro_desde');
            if (el) el.value = '';
            break;
        }
        case 'fecha_hasta': {
            const el = document.getElementById('rendicion_filtro_hasta');
            if (el) el.value = '';
            break;
        }
        case 'terminal': {
            const el = document.getElementById('rendicion_filtro_terminal');
            if (el) el.value = '';
            break;
        }
    }
}

/**
 * Limpia los filtros de Rendiciones sin disparar render. Se usa
 * cuando cambia el dueño seleccionado.
 */
function limpiar_filtros_rendiciones_sin_render() {
    limpiar_filtro_rendicion_individual('codigo');
    limpiar_filtro_rendicion_individual('fecha_desde');
    limpiar_filtro_rendicion_individual('fecha_hasta');
    limpiar_filtro_rendicion_individual('terminal');
    const sel_term = document.getElementById('rendicion_filtro_terminal');
    if (sel_term) {
        sel_term.innerHTML = '<option value="">Todas</option>';
    }
    renderizar_chips_rendiciones();
}

/**
 * Limpia todos los filtros de Rendiciones y recarga.
 */
function limpiar_filtros_rendiciones() {
    limpiar_filtros_rendiciones_sin_render();
    const nombre_dueno = obtener_nombre_dueno_rendiciones();
    if (nombre_dueno) {
        _cargar_rendiciones_con_dueno(nombre_dueno);
    }
}

/**
 * Arma la URL del informe de cancelación.
 *
 * @param {string} id_cancelacion
 * @returns {string}
 */
function _url_informe_cancelacion(id_cancelacion) {
    return `index.php?imprimir=1&tipo=informe_cancelacion&id_cancelacion=${encodeURIComponent(id_cancelacion)}`;
}

/**
 * Renderiza el cartel de notificación arriba de la tabla si hay
 * rendiciones con ajustes pendientes de aceptar.
 */
function renderizar_aviso_rendiciones_desactualizadas() {
    const contenedor = document.getElementById('aviso_rendiciones_desactualizadas');
    if (!contenedor) return;

    const pendientes = (rendiciones_actuales || []).filter(r => r.pendiente_aceptar);
    if (pendientes.length === 0) {
        contenedor.innerHTML = '';
        return;
    }

    const texto = pendientes.length === 1
        ? 'Tenés 1 rendición con ajustes pendientes de aceptar.'
        : `Tenés ${pendientes.length} rendiciones con ajustes pendientes de aceptar.`;

    contenedor.innerHTML = `
        <div class="aviso-rendiciones-desactualizadas">
            <div class="aviso-rendiciones-texto">
                <strong>Aviso:</strong> ${texto}
                <div class="aviso-rendiciones-detalle">Se canceló una o más ventas cuyos cupones ya habían sido rendidos. Entrá al detalle de cada rendición para aceptar el ajuste.</div>
            </div>
        </div>
    `;
}

// ===== Inicialización de listeners =====
(function() {
    const input_codigo = document.getElementById('rendicion_filtro_codigo');
    if (input_codigo) input_codigo.addEventListener('input', () => {
        const nd = obtener_nombre_dueno_rendiciones();
        if (nd) _cargar_rendiciones_con_dueno(nd);
    });
    const input_desde = document.getElementById('rendicion_filtro_desde');
    if (input_desde) input_desde.addEventListener('change', () => {
        const nd = obtener_nombre_dueno_rendiciones();
        if (nd) _cargar_rendiciones_con_dueno(nd);
    });
    const input_hasta = document.getElementById('rendicion_filtro_hasta');
    if (input_hasta) input_hasta.addEventListener('change', () => {
        const nd = obtener_nombre_dueno_rendiciones();
        if (nd) _cargar_rendiciones_con_dueno(nd);
    });
    const sel_terminal = document.getElementById('rendicion_filtro_terminal');
    if (sel_terminal) sel_terminal.addEventListener('change', () => {
        const nd = obtener_nombre_dueno_rendiciones();
        if (nd) _cargar_rendiciones_con_dueno(nd);
    });
    const btn_limpiar = document.getElementById('boton_limpiar_filtros_rendiciones');
    if (btn_limpiar) btn_limpiar.addEventListener('click', limpiar_filtros_rendiciones);
    const btn_liquidar = document.getElementById('boton_liquidar');
    if (btn_liquidar) {
        // La función abrir_modal_liquidar vive en liquidaciones.js, que
        // se carga después. Se resuelve al hacer click.
        btn_liquidar.addEventListener('click', () => {
            if (typeof abrir_modal_liquidar === 'function') abrir_modal_liquidar();
        });
    }
})();
