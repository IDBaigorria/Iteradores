/***
 * Funciones de la pestaña Liquidaciones.
 * @version 1.5piloto.54d
 */

let liquidaciones_actuales = [];
let dueno_liquidaciones_seleccionado = '';
window.liquidacion_saldos_actuales = null;

/**
 * Arma la URL del informe de liquidación. Si el que imprime es el
 * dueño, agrega ocultar_datos_generales=1 para no mostrarle su
 * propio nombre en el informe.
 *
 * @param {string} id_liquidacion
 * @returns {string}
 */
function _url_informe_liquidacion(id_liquidacion) {
    const base = 'index.php?imprimir=1&tipo=informe_liquidacion&id_liquidacion=' + encodeURIComponent(id_liquidacion);
    if (usuario_actual && usuario_actual.nivel === 'dueno') {
        return base + '&ocultar_datos_generales=1';
    }
    return base;
}

function obtener_nombre_dueno_liquidaciones() {
    if (!usuario_actual) return '';
    if (usuario_actual.nivel === 'admin') {
        const select = document.getElementById('selector_dueno_liquidaciones');
        if (select && select.value) return select.value;
        return dueno_liquidaciones_seleccionado || '';
    }
    if (usuario_actual.nivel === 'dueno') {
        return usuario_actual.nombre_usuario;
    }
    return '';
}

async function cargar_liquidaciones() {
    const panelSelector = document.getElementById('contenedor_filtro_dueno_liquidaciones');

    if (usuario_actual.nivel === 'admin') {
        if (panelSelector) panelSelector.style.display = 'block';
        const select = document.getElementById('selector_dueno_liquidaciones');
        if (select && select.options.length <= 1) {
            await cargar_duenos_en_select_liquidaciones();
        }
    } else {
        if (panelSelector) panelSelector.style.display = 'none';
    }

    const nombre_dueno = obtener_nombre_dueno_liquidaciones();
    if (!nombre_dueno) {
        liquidaciones_actuales = [];
        renderizar_tabla_liquidaciones([]);
        renderizar_resumen_liquidaciones(null);
        return;
    }
    await _cargar_liquidaciones_con_dueno(nombre_dueno);
}

async function cargar_duenos_en_select_liquidaciones() {
    const select = document.getElementById('selector_dueno_liquidaciones');
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
        dueno_liquidaciones_seleccionado = select.value;
        limpiar_filtros_liquidaciones_sin_render();
        if (select.value) {
            _cargar_liquidaciones_con_dueno(select.value);
        } else {
            liquidaciones_actuales = [];
            renderizar_tabla_liquidaciones([]);
            renderizar_resumen_liquidaciones(null);
        }
    };
}

async function _cargar_liquidaciones_con_dueno(nombre_dueno) {
    const filtros = _recolectar_filtros_liquidaciones();
    const params = {
        accion: "liquidaciones/listar",
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
        mostrar_aviso(datos.error || "Error al cargar liquidaciones", 'error');
        return;
    }

    liquidaciones_actuales = datos.liquidaciones || [];
    renderizar_tabla_liquidaciones(liquidaciones_actuales);
    renderizar_resumen_liquidaciones(datos.saldos_dueno || null);
    renderizar_chips_liquidaciones();
}

function _recolectar_filtros_liquidaciones() {
    return {
        codigo: (document.getElementById('liquidacion_filtro_codigo')?.value || '').trim(),
        fecha_desde: document.getElementById('liquidacion_filtro_desde')?.value || '',
        fecha_hasta: document.getElementById('liquidacion_filtro_hasta')?.value || ''
    };
}

function renderizar_resumen_liquidaciones(saldos) {
    const contenedor = document.getElementById('resumen_liquidaciones');
    if (!contenedor) return;
    contenedor.innerHTML = renderizar_resumen_saldos_dueno(saldos, 'Saldo actual del dueño');
}

function renderizar_tabla_liquidaciones(liquidaciones) {
    const contenedor = document.getElementById('tabla_liquidaciones_container');
    if (!contenedor) return;

    if (!liquidaciones || liquidaciones.length === 0) {
        contenedor.innerHTML = '<p style="color:#888; margin:20px;">Sin liquidaciones registradas para los filtros seleccionados.</p>';
        return;
    }

    let html = '<div class="table-wrap"><table class="data-table rendiciones-tabla">';
    html += '<thead><tr>';
    html += '<th>Código</th>';
    html += '<th>Fecha</th>';
    html += '<th>Efectivo</th>';
    html += '<th>Banco</th>';
    html += '<th>Total</th>';
    html += '<th></th>';
    html += '</tr></thead><tbody>';

    liquidaciones.forEach(l => {
        html += '<tr>';
        html += `<td><strong>${l.id_liquidacion}</strong></td>`;
        html += `<td>${l.fecha_hora}</td>`;
        html += `<td class="num">$${_formatear_monto_rendiciones(l.monto_efectivo)}</td>`;
        html += `<td class="num">$${_formatear_monto_rendiciones(l.monto_banco)}</td>`;
        html += `<td class="num"><b>$${_formatear_monto_rendiciones(l.total)}</b></td>`;
        html += `<td><button class="btn ver_detalle_liquidacion" data-id="${l.id_liquidacion}">Ver detalle</button></td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    contenedor.innerHTML = html;

    contenedor.querySelectorAll('.ver_detalle_liquidacion').forEach(btn => {
        btn.addEventListener('click', () => ver_detalle_liquidacion(btn.dataset.id));
    });
}

async function ver_detalle_liquidacion(id_liquidacion) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "liquidaciones/obtener", id_liquidacion })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener la liquidación", 'error');
        return;
    }

    const l = datos.liquidacion;
    const obs = (l.observaciones || '').trim();
    const obs_html = obs
        ? `<div class="rendicion-seccion"><div class="rendicion-seccion-titulo">Observaciones</div><p style="font-size:13px;line-height:1.6;white-space:pre-wrap;">${obs}</p></div>`
        : '';

    const html = `
        <div class="rendicion-detalle">
            <div class="rendicion-detalle-header">
                <div class="rendicion-detalle-titulo">Liquidación ${l.id_liquidacion}</div>
                <div class="rendicion-detalle-fecha">Registrada el ${l.fecha_hora}</div>
            </div>

            <div class="rendicion-resumen">
                <div class="rendicion-resumen-titulo">Monto liquidado</div>
                <div class="rendicion-resumen-grid">
                    <div class="rendicion-resumen-item"><span>Total</span><b>$${_formatear_monto_rendiciones(l.total)}</b></div>
                    <div class="rendicion-resumen-item"><span>De efectivo</span><b>$${_formatear_monto_rendiciones(l.monto_efectivo)}</b></div>
                    <div class="rendicion-resumen-item"><span>Del banco</span><b>$${_formatear_monto_rendiciones(l.monto_banco)}</b></div>
                </div>
            </div>

            <div class="rendicion-resumen">
                <div class="rendicion-resumen-titulo">Saldos posteriores</div>
                <div class="rendicion-resumen-grid">
                    <div class="rendicion-resumen-item"><span>Efectivo restante</span><b>$${_formatear_monto_rendiciones(l.efectivo_restante)}</b></div>
                    <div class="rendicion-resumen-item"><span>Banco restante</span><b>$${_formatear_monto_rendiciones(l.banco_restante)}</b></div>
                </div>
            </div>

            ${obs_html}

            <div class="rendicion-acciones">
                <button class="btn primary" id="btn_imprimir_detalle_liquidacion">Imprimir informe de liquidación</button>
                <button class="btn" id="btn_cerrar_detalle_liquidacion">Cerrar</button>
            </div>
        </div>
    `;

    abrir_modal_generico('Detalle de liquidación', html);

    // El detalle de liquidación tiene menos contenido que otros modales,
    // así que se achica el ancho.
    const contentEl = document.querySelector('#modal_generico .modal-content');
    if (contentEl) {
        contentEl.style.maxWidth = '560px';
        contentEl.style.width = '560px';
    }

    const cont = document.getElementById('modal_generico_contenido');
    cont.querySelector('#btn_cerrar_detalle_liquidacion').addEventListener('click', cerrar_modal_generico);
    cont.querySelector('#btn_imprimir_detalle_liquidacion').addEventListener('click', () => {
        window.open(_url_informe_liquidacion(l.id_liquidacion), '_blank');
    });
}

/**
 * Abre el modal de liquidación: pide los saldos actuales, muestra dos
 * inputs (efectivo, banco) con tope dinámico, un textarea de
 * observaciones y el total recalculado en vivo.
 */
async function abrir_modal_liquidar() {
    if (!usuario_actual) return;
    if (usuario_actual.nivel !== 'dueno') {
        mostrar_aviso('Solo el dueño puede liquidar', 'error');
        return;
    }

    const nombre_dueno = usuario_actual.nombre_usuario;

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "liquidaciones/previsualizar",
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || 'Error al obtener saldos', 'error');
        return;
    }

    window.liquidacion_saldos_actuales = datos.saldos_dueno || { efectivo: '0.00', banco: '0.00', total: '0.00' };
    _renderizar_modal_liquidar();
}

function _renderizar_modal_liquidar() {
    const saldos = window.liquidacion_saldos_actuales;
    if (!saldos) return;

    const ef = parseFloat(saldos.efectivo) || 0;
    const ba = parseFloat(saldos.banco) || 0;

    const html = `
        <div class="liquidacion-form">
            <div class="liquidacion-saldos-caja">
                <div class="liquidacion-saldos-titulo">Saldo actual del dueño</div>
                <div class="liquidacion-saldos-grid">
                    <div class="liquidacion-saldos-item"><span>Efectivo</span><b>$${_formatear_monto_rendiciones(ef)}</b></div>
                    <div class="liquidacion-saldos-item"><span>Banco</span><b>$${_formatear_monto_rendiciones(ba)}</b></div>
                    <div class="liquidacion-saldos-item"><span>Total</span><b>$${_formatear_monto_rendiciones(ef + ba)}</b></div>
                </div>
            </div>

            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo" style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <span>Monto a extraer</span>
                    <button type="button" class="btn" id="liquidacion_usar_maximo" style="font-size:12px; padding:4px 10px;">Usar máximo</button>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>De efectivo (máx $${_formatear_monto_rendiciones(ef)})</label>
                        <input type="number" id="liquidacion_monto_efectivo" step="1000" min="0" max="${ef.toFixed(2)}" value="" placeholder="0.00">
                    </div>
                    <div class="field">
                        <label>Del banco (máx $${_formatear_monto_rendiciones(ba)})</label>
                        <input type="number" id="liquidacion_monto_banco" step="1000" min="0" max="${ba.toFixed(2)}" value="" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="liquidacion-totales-linea">
                <span>Total a extraer: <b id="liquidacion_total_extraer">$0,00</b></span>
                <span class="rendicion-sep">·</span>
                <span>Quedará en efectivo: <b id="liquidacion_restante_efectivo">$${_formatear_monto_rendiciones(ef)}</b></span>
                <span class="rendicion-sep">·</span>
                <span>Quedará en banco: <b id="liquidacion_restante_banco">$${_formatear_monto_rendiciones(ba)}</b></span>
            </div>

            <div class="field">
                <label>Observaciones (opcional)</label>
                <textarea id="liquidacion_observaciones" rows="3" placeholder="Ej.: pago del alquiler de los micros"></textarea>
            </div>

            <div class="rendicion-acciones">
                <button class="btn primary" id="btn_confirmar_liquidacion" disabled>Confirmar liquidación y guardar</button>
                <button class="btn" id="btn_cerrar_liquidacion_modal">Cerrar</button>
            </div>
        </div>
    `;

    abrir_modal_generico('Liquidar', html);

    // El modal de liquidación es más angosto que el modal genérico,
    // porque tiene menos contenido. Se ajusta solo para este flujo.
    const contentEl = document.querySelector('#modal_generico .modal-content');
    if (contentEl) {
        contentEl.style.maxWidth = '560px';
        contentEl.style.width = '560px';
    }

    const cont = document.getElementById('modal_generico_contenido');
    const input_ef = cont.querySelector('#liquidacion_monto_efectivo');
    const input_ba = cont.querySelector('#liquidacion_monto_banco');

    const actualizar = () => _actualizar_totales_liquidacion();
    input_ef.addEventListener('input', actualizar);
    input_ba.addEventListener('input', actualizar);

    // Botón "Usar máximo": llena cada input con su tope respectivo.
    const btn_max = cont.querySelector('#liquidacion_usar_maximo');
    if (btn_max) {
        btn_max.addEventListener('click', () => {
            input_ef.value = ef.toFixed(2);
            input_ba.value = ba.toFixed(2);
            _actualizar_totales_liquidacion();
        });
    }

    cont.querySelector('#btn_cerrar_liquidacion_modal').addEventListener('click', cerrar_modal_generico);
    cont.querySelector('#btn_confirmar_liquidacion').addEventListener('click', confirmar_liquidacion_modal);
}

function _actualizar_totales_liquidacion() {
    const saldos = window.liquidacion_saldos_actuales;
    if (!saldos) return;
    const ef = parseFloat(saldos.efectivo) || 0;
    const ba = parseFloat(saldos.banco) || 0;

    const cont = document.getElementById('modal_generico_contenido');
    if (!cont) return;
    const input_ef = cont.querySelector('#liquidacion_monto_efectivo');
    const input_ba = cont.querySelector('#liquidacion_monto_banco');
    if (!input_ef || !input_ba) return;

    let m_ef = parseFloat(input_ef.value) || 0;
    let m_ba = parseFloat(input_ba.value) || 0;
    if (m_ef < 0) m_ef = 0;
    if (m_ba < 0) m_ba = 0;
    if (m_ef > ef) m_ef = ef;
    if (m_ba > ba) m_ba = ba;

    const total = m_ef + m_ba;
    const restante_ef = ef - m_ef;
    const restante_ba = ba - m_ba;

    const el_total = cont.querySelector('#liquidacion_total_extraer');
    if (el_total) el_total.textContent = '$' + _formatear_monto_rendiciones(total);
    const el_ref = cont.querySelector('#liquidacion_restante_efectivo');
    if (el_ref) el_ref.textContent = '$' + _formatear_monto_rendiciones(restante_ef);
    const el_rba = cont.querySelector('#liquidacion_restante_banco');
    if (el_rba) el_rba.textContent = '$' + _formatear_monto_rendiciones(restante_ba);

    const btn = cont.querySelector('#btn_confirmar_liquidacion');
    if (btn) btn.disabled = (total <= 0.001);
}

async function confirmar_liquidacion_modal() {
    const saldos = window.liquidacion_saldos_actuales;
    if (!saldos) return;

    const cont = document.getElementById('modal_generico_contenido');
    const m_ef = parseFloat(cont.querySelector('#liquidacion_monto_efectivo').value) || 0;
    const m_ba = parseFloat(cont.querySelector('#liquidacion_monto_banco').value) || 0;
    const obs = (cont.querySelector('#liquidacion_observaciones').value || '').trim();

    if (m_ef + m_ba <= 0.001) {
        mostrar_aviso('Debe extraerse al menos un monto mayor a cero', 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "liquidaciones/confirmar",
            nombre_dueno: usuario_actual.nombre_usuario,
            monto_efectivo: m_ef.toFixed(2),
            monto_banco: m_ba.toFixed(2),
            observaciones: obs
        })
    });
    const resultado = await respuesta.json();

    if (!resultado.exito) {
        mostrar_aviso(resultado.error || 'No se pudo registrar la liquidación', 'error');
        return;
    }

    mostrar_aviso(`Liquidación ${resultado.id_liquidacion} guardada ($${_formatear_monto_rendiciones(resultado.total)})`, 'exito');

    window.liquidacion_saldos_actuales = null;
    cerrar_modal_generico();

    // Refrescar la pestaña de Rendiciones (para que el saldo del dueño
    // baje al toque) y la pestaña de Liquidaciones.
    if (typeof cargar_rendiciones === 'function') cargar_rendiciones();
    if (typeof cargar_liquidaciones === 'function') cargar_liquidaciones();

    mostrar_modal_chico_impresion_liquidacion(resultado.id_liquidacion);
}

function mostrar_modal_chico_impresion_liquidacion(id_liquidacion) {
    const contenedor = document.getElementById('modal_chico_impresion_liquidacion');
    const titulo = document.getElementById('modal_chico_impresion_liquidacion_titulo');
    const btnImprimir = document.getElementById('btn_modal_chico_imprimir_liquidacion');
    const btnCerrar = document.getElementById('btn_modal_chico_cerrar_liquidacion');
    if (!contenedor || !titulo || !btnImprimir || !btnCerrar) return;

    // Este modal chico es más ancho que los otros porque el botón
    // de impresión tiene un texto largo.
    contenedor.style.maxWidth = '420px';

    titulo.textContent = `Liquidación ${id_liquidacion} registrada`;

    btnImprimir.onclick = () => {
        window.open(_url_informe_liquidacion(id_liquidacion), '_blank');
    };

    btnCerrar.onclick = () => {
        contenedor.classList.add('hidden');
    };

    contenedor.classList.remove('hidden');
}

function renderizar_chips_liquidaciones() {
    const contenedor = document.getElementById('chips_filtros_activos_liquidaciones');
    if (!contenedor) return;

    const chips = [];

    const codigo = (document.getElementById('liquidacion_filtro_codigo')?.value || '').trim();
    if (codigo) chips.push({ id: 'codigo', texto: `Código: ${codigo}` });

    const desde = document.getElementById('liquidacion_filtro_desde')?.value || '';
    if (desde) {
        const partes = desde.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : desde;
        chips.push({ id: 'fecha_desde', texto: `Desde: ${visible}` });
    }

    const hasta = document.getElementById('liquidacion_filtro_hasta')?.value || '';
    if (hasta) {
        const partes = hasta.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : hasta;
        chips.push({ id: 'fecha_hasta', texto: `Hasta: ${visible}` });
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
            limpiar_filtro_liquidacion_individual(btn.dataset.chip);
            _cargar_liquidaciones_con_dueno(obtener_nombre_dueno_liquidaciones());
        });
    });
}

function limpiar_filtro_liquidacion_individual(id) {
    switch (id) {
        case 'codigo': {
            const el = document.getElementById('liquidacion_filtro_codigo');
            if (el) el.value = '';
            break;
        }
        case 'fecha_desde': {
            const el = document.getElementById('liquidacion_filtro_desde');
            if (el) el.value = '';
            break;
        }
        case 'fecha_hasta': {
            const el = document.getElementById('liquidacion_filtro_hasta');
            if (el) el.value = '';
            break;
        }
    }
}

function limpiar_filtros_liquidaciones_sin_render() {
    limpiar_filtro_liquidacion_individual('codigo');
    limpiar_filtro_liquidacion_individual('fecha_desde');
    limpiar_filtro_liquidacion_individual('fecha_hasta');
    renderizar_chips_liquidaciones();
}

function limpiar_filtros_liquidaciones() {
    limpiar_filtros_liquidaciones_sin_render();
    const nombre_dueno = obtener_nombre_dueno_liquidaciones();
    if (nombre_dueno) {
        _cargar_liquidaciones_con_dueno(nombre_dueno);
    }
}

// ===== Inicialización de listeners =====
(function() {
    const input_codigo = document.getElementById('liquidacion_filtro_codigo');
    if (input_codigo) input_codigo.addEventListener('input', () => {
        const nd = obtener_nombre_dueno_liquidaciones();
        if (nd) _cargar_liquidaciones_con_dueno(nd);
    });
    const input_desde = document.getElementById('liquidacion_filtro_desde');
    if (input_desde) input_desde.addEventListener('change', () => {
        const nd = obtener_nombre_dueno_liquidaciones();
        if (nd) _cargar_liquidaciones_con_dueno(nd);
    });
    const input_hasta = document.getElementById('liquidacion_filtro_hasta');
    if (input_hasta) input_hasta.addEventListener('change', () => {
        const nd = obtener_nombre_dueno_liquidaciones();
        if (nd) _cargar_liquidaciones_con_dueno(nd);
    });
    const btn_limpiar = document.getElementById('boton_limpiar_filtros_liquidaciones');
    if (btn_limpiar) btn_limpiar.addEventListener('click', limpiar_filtros_liquidaciones);
})();
