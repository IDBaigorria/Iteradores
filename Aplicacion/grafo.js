/**
 * Pestaña Grafo — visualizador de la superestructura.
 *
 * Vista de solo lectura. Muestra totales, nodos alcanzables vs
 * huérfanos, y permite inspeccionar los enlaces de cada nodo.
 *
 * Es la base para la auditoría de la fuga de nodos (Fase 2).
 * Ver prompts/prompt_piloto.md §8.6.
 *
 * @version 1.5piloto.76g
 */

let grafo_offset_actual = 0;
let grafo_limite_actual = 50;
let grafo_total_actual = 0;

async function cargar_grafo() {
    // Cargar resumen.
    const resp_res = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/resumen",
            nombre_solicitante: usuario_actual.nombre_usuario
        })
    });
    const datos_res = await resp_res.json();
    if (!datos_res.exito) {
        mostrar_aviso(datos_res.error || "Error al cargar resumen", 'error');
        return;
    }
    _grafo_renderizar_resumen(datos_res.resumen);
    await _grafo_cargar_tabla(0);
}

function _grafo_renderizar_resumen(resumen) {
    const cont = document.getElementById('grafo_resumen');
    if (!cont) return;
    let top_html = '';
    const top = resumen.top_referencias || {};
    const ids_top = Object.keys(top).slice(0, 10);
    if (ids_top.length > 0) {
        top_html = '<div style="margin-top:8px;"><strong>Top nodos por referencias entrantes:</strong><ul style="margin:6px 0; padding-left:20px; font-family:monospace; font-size:12px;">';
        for (const id of ids_top) {
            top_html += `<li><code>${_grafo_escape(id)}</code> → ${top[id]} referencia(s)</li>`;
        }
        top_html += '</ul></div>';
    }
    const color_huerfanos = (resumen.huerfanos > 0) ? '#c00' : '#333';
    cont.innerHTML = `
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            <div><span class="muted">Total nodos:</span> <strong>${resumen.total}</strong></div>
            <div><span class="muted">Alcanzables:</span> <strong>${resumen.alcanzables}</strong></div>
            <div><span class="muted">Huérfanos:</span> <strong style="color:${color_huerfanos};">${resumen.huerfanos}</strong></div>
        </div>
        ${top_html}
    `;
}

async function _grafo_cargar_tabla(offset) {
    grafo_offset_actual = offset;
    const filtro = document.getElementById('grafo_filtro').value || 'todos';
    const enlace = document.getElementById('grafo_filtro_enlace').value.trim();
    const texto = document.getElementById('grafo_filtro_texto').value.trim();

    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/listar",
            nombre_solicitante: usuario_actual.nombre_usuario,
            filtro,
            enlace,
            texto,
            offset: String(offset),
            limite: String(grafo_limite_actual)
        })
    });
    const datos = await resp.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al listar", 'error');
        return;
    }
    grafo_total_actual = datos.lista.total;
    _grafo_renderizar_tabla(datos.lista.nodos);
    _grafo_renderizar_paginacion(datos.lista);
}

function _grafo_renderizar_tabla(nodos) {
    const tbody = document.getElementById('grafo_tabla');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (nodos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;">Sin resultados</td></tr>';
        return;
    }
    nodos.forEach(n => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><code>${_grafo_escape(n.id)}</code></td>
            <td>${_grafo_escape(n.tipo || '?')}</td>
            <td style="word-break:break-all;">${_grafo_escape(n.dato || '')}</td>
            <td>${n.n_adyacentes}</td>
            <td>${n.n_referencias}</td>
            <td><button class="btn" data-id="${_grafo_escape(n.id)}">Ver</button></td>
        `;
        tr.querySelector('button').addEventListener('click', () => ver_nodo_grafo(n.id));
        tbody.appendChild(tr);
    });
}

function _grafo_renderizar_paginacion(lista) {
    const cont = document.getElementById('grafo_paginacion');
    if (!cont) return;
    const total = lista.total;
    const offset = lista.offset;
    const limite = lista.limite;
    const desde = total > 0 ? offset + 1 : 0;
    const hasta = Math.min(offset + limite, total);
    const boton_prev = offset > 0 ? `<button class="btn" id="grafo_prev">← Anterior</button>` : '';
    const boton_next = (offset + limite < total) ? `<button class="btn" id="grafo_next">Siguiente →</button>` : '';
    cont.innerHTML = `<span class="muted">${desde}-${hasta} de ${total}</span> ${boton_prev} ${boton_next}`;
    const btn_prev = document.getElementById('grafo_prev');
    if (btn_prev) btn_prev.addEventListener('click', () => _grafo_cargar_tabla(Math.max(0, offset - limite)));
    const btn_next = document.getElementById('grafo_next');
    if (btn_next) btn_next.addEventListener('click', () => _grafo_cargar_tabla(offset + limite));
}

async function ver_nodo_grafo(id) {
    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/nodo",
            nombre_solicitante: usuario_actual.nombre_usuario,
            id
        })
    });
    const datos = await resp.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener nodo", 'error');
        return;
    }
    _grafo_renderizar_modal_nodo(datos.nodo);
}

function _grafo_renderizar_modal_nodo(n) {
    let ady_html = '';
    if (n.adyacentes && n.adyacentes.length > 0) {
        ady_html = '<ul style="margin:4px 0; padding-left:20px; font-family:monospace; font-size:12px;">';
        n.adyacentes.forEach(a => {
            const dato = a.dato_destino ? ' (' + _grafo_escape(a.dato_destino) + ')' : '';
            ady_html += `<li><code>${_grafo_escape(a.enlace)}</code> → <code>${_grafo_escape(a.id_destino)}</code>${dato} <button class="btn" data-nav="${_grafo_escape(a.id_destino)}" style="padding:0 6px; font-size:11px;">→</button></li>`;
        });
        ady_html += '</ul>';
    } else {
        ady_html = '<p class="muted">Sin enlaces salientes.</p>';
    }

    let refs_html = '';
    if (n.referencias && n.referencias.length > 0) {
        refs_html = '<ul style="margin:4px 0; padding-left:20px; font-family:monospace; font-size:12px;">';
        n.referencias.forEach(r => {
            const dato = r.dato_origen ? ' (' + _grafo_escape(r.dato_origen) + ')' : '';
            refs_html += `<li><code>${_grafo_escape(r.id_origen)}</code> → <code>${_grafo_escape(r.enlace)}</code>${dato} <button class="btn" data-nav="${_grafo_escape(r.id_origen)}" style="padding:0 6px; font-size:11px;">→</button></li>`;
        });
        refs_html += '</ul>';
    } else {
        refs_html = '<p class="muted">Sin referencias entrantes. Es una raíz o un nodo huérfano.</p>';
    }

    const html = `
        <div class="seccion">
            <div class="detail-line"><span>ID:</span><strong><code>${_grafo_escape(n.id)}</code></strong></div>
            <div class="detail-line"><span>Es especial:</span><strong>${n.es_especial ? 'Sí' : 'No'}</strong></div>
            <div class="detail-line"><span>Dato:</span><strong style="word-break:break-all;">${_grafo_escape(n.dato || '(vacío)')}</strong></div>
        </div>
        <div class="seccion">
            <h4>Enlaces salientes (${(n.adyacentes || []).length})</h4>
            ${ady_html}
        </div>
        <div class="seccion">
            <h4>Referencias entrantes (${(n.referencias || []).length})</h4>
            ${refs_html}
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn" id="grafo_cerrar_modal">Cerrar</button>
        </div>
    `;

    abrir_modal_apilado('Nodo: ' + n.id, html);

    const cont = document.getElementById('modal_apilado_contenido');
    if (!cont) return;
    cont.querySelector('#grafo_cerrar_modal').addEventListener('click', cerrar_modal_apilado);
    cont.querySelectorAll('button[data-nav]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id_destino = btn.dataset.nav;
            cerrar_modal_apilado();
            ver_nodo_grafo(id_destino);
        });
    });
}

function _grafo_escape(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ============================================================
// ELIMINAR NODOS HUÉRFANOS (v1.5piloto.76g)
// ============================================================

/**
 * Abre el modal de confirmación para eliminar los nodos
 * huérfanos del grafo. Muestra la cantidad total y una
 * vista previa de los primeros 20.
 *
 * No hay chequeo de modo pruebas en este flujo: la limpieza
 * es útil en producción. El backend valida admin/soporte.
 */
async function eliminar_huerfanos_grafo() {
    const resp_res = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/resumen",
            nombre_solicitante: usuario_actual.nombre_usuario
        })
    });
    const datos_res = await resp_res.json();
    if (!datos_res.exito) {
        mostrar_aviso(datos_res.error || "Error al cargar el resumen", 'error');
        return;
    }

    const total_huerfanos = datos_res.resumen.huerfanos;
    if (total_huerfanos === 0) {
        mostrar_aviso("No hay nodos basura para eliminar.", 'info');
        return;
    }

    // Vista previa: los primeros 20.
    const resp_lista = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/listar",
            nombre_solicitante: usuario_actual.nombre_usuario,
            filtro: "huerfanos",
            enlace: "",
            texto: "",
            offset: "0",
            limite: "20"
        })
    });
    const datos_lista = await resp_lista.json();
    if (!datos_lista.exito) {
        mostrar_aviso(datos_lista.error || "Error al listar huérfanos", 'error');
        return;
    }

    let filas = '';
    datos_lista.lista.nodos.forEach(n => {
        filas += `<tr>`
            + `<td><code>${_grafo_escape(n.id)}</code></td>`
            + `<td>${_grafo_escape(n.tipo || '?')}</td>`
            + `<td style="word-break:break-all;">${_grafo_escape(n.dato || '')}</td>`
            + `</tr>`;
    });

    const aviso_extra = total_huerfanos > 20
        ? `<p class="muted small">Se muestran los primeros 20 de ${total_huerfanos}.</p>`
        : '';

    const html = `
        <p>Se eliminarán <strong>${total_huerfanos}</strong> nodos no alcanzables desde las raíces del grafo.</p>
        <p class="muted small">Son nodos que ya no referencia nadie vivo. Igual, si no tenés backup reciente, conviene cancelar y hacer uno antes.</p>
        <div style="max-height:300px; overflow-y:auto; border:1px solid #ccc; border-radius:4px; margin:10px 0;">
            <table class="data-table" style="margin:0;">
                <thead><tr><th>ID</th><th>Tipo</th><th>Dato</th></tr></thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
        ${aviso_extra}
        <div class="field" style="margin-top:10px;">
            <label><input type="checkbox" id="grafo_eliminar_confirmo"> Confirmo que quiero eliminar estos nodos.</label>
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn danger" id="grafo_eliminar_ejecutar" disabled>Eliminar ${total_huerfanos} nodos</button>
            <button class="btn" id="grafo_eliminar_cancelar">Cancelar</button>
        </div>
    `;

    abrir_modal_generico('Eliminar nodos basura', html);

    const cont = document.getElementById('modal_generico_contenido');
    if (!cont) return;
    const chk = cont.querySelector('#grafo_eliminar_confirmo');
    const btn = cont.querySelector('#grafo_eliminar_ejecutar');
    chk.addEventListener('change', () => { btn.disabled = !chk.checked; });
    cont.querySelector('#grafo_eliminar_cancelar').addEventListener('click', cerrar_modal_generico);
    btn.addEventListener('click', () => _grafo_ejecutar_eliminacion());
}

/**
 * Ejecuta la eliminación y refresca el panel.
 */
async function _grafo_ejecutar_eliminacion() {
    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "grafo/eliminar_huerfanos",
            nombre_solicitante: usuario_actual.nombre_usuario
        })
    });
    const datos = await resp.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al eliminar", 'error');
        return;
    }
    mostrar_aviso(`Eliminados ${datos.eliminados} nodos basura.`, 'exito');
    cerrar_modal_generico();
    await cargar_grafo();
}

// Inicialización de listeners del panel Grafo.
(function() {
    const btn_filtrar = document.getElementById('grafo_boton_filtrar');
    if (btn_filtrar) btn_filtrar.addEventListener('click', () => _grafo_cargar_tabla(0));
    const btn_limpiar = document.getElementById('grafo_boton_limpiar');
    if (btn_limpiar) btn_limpiar.addEventListener('click', () => {
        document.getElementById('grafo_filtro').value = 'todos';
        document.getElementById('grafo_filtro_enlace').value = '';
        document.getElementById('grafo_filtro_texto').value = '';
        _grafo_cargar_tabla(0);
    });
    const btn_eliminar_huerfanos = document.getElementById('grafo_boton_eliminar_huerfanos');
    if (btn_eliminar_huerfanos) btn_eliminar_huerfanos.addEventListener('click', eliminar_huerfanos_grafo);
})();