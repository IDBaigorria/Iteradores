<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.51 Tanda B: frontend de la pestaña Rendiciones.
 * - Sección nueva en el HTML, con sidebar de filtros y tabla.
 * - Archivo nuevo Aplicacion/rendiciones.js.
 * - Archivo nuevo estilos-rendiciones.css.
 * - Pestaña "Rendiciones" en el menú para dueño y admin.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [
    // ============================================================
    // 1. Crear Aplicacion/rendiciones.js
    // ============================================================
    [
        'tipo' => 'crear',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'Nuevo módulo de rendiciones (frontend)',
        'contenido' => [
            '/***',
            ' * Funciones de la pestaña Rendiciones.',
            ' * @version 1.5piloto.51',
            ' */',
            '',
            'let rendiciones_actuales = [];',
            'let dueno_rendiciones_seleccionado = \'\';',
            '',
            '/**',
            ' * Devuelve el nombre del dueño cuyas rendiciones se están viendo,',
            ' * según el rol del usuario actual.',
            ' */',
            'function obtener_nombre_dueno_rendiciones() {',
            '    if (!usuario_actual) return \'\';',
            '    if (usuario_actual.nivel === \'admin\') {',
            '        const select = document.getElementById(\'selector_dueno_rendiciones\');',
            '        if (select && select.value) return select.value;',
            '        return dueno_rendiciones_seleccionado || \'\';',
            '    }',
            '    if (usuario_actual.nivel === \'dueno\') {',
            '        return usuario_actual.nombre_usuario;',
            '    }',
            '    return \'\';',
            '}',
            '',
            '/**',
            ' * Formatea un monto al estilo argentino.',
            ' */',
            'function _formatear_monto_rendiciones(monto) {',
            '    const n = parseFloat(monto) || 0;',
            '    return n.toLocaleString(\'es-AR\', { minimumFractionDigits: 2, maximumFractionDigits: 2 });',
            '}',
            '',
            '/**',
            ' * Carga la pestaña Rendiciones. Si el usuario es admin, muestra el',
            ' * selector de dueño y espera a que elija uno.',
            ' */',
            'async function cargar_rendiciones() {',
            '    const panelSelector = document.getElementById(\'contenedor_filtro_dueno_rendiciones\');',
            '',
            '    if (usuario_actual.nivel === \'admin\') {',
            '        if (panelSelector) panelSelector.style.display = \'block\';',
            '        const select = document.getElementById(\'selector_dueno_rendiciones\');',
            '        if (select && select.options.length <= 1) {',
            '            await cargar_duenos_en_select_rendiciones();',
            '        }',
            '    } else {',
            '        if (panelSelector) panelSelector.style.display = \'none\';',
            '    }',
            '',
            '    const nombre_dueno = obtener_nombre_dueno_rendiciones();',
            '    if (!nombre_dueno) {',
            '        rendiciones_actuales = [];',
            '        renderizar_tabla_rendiciones([]);',
            '        renderizar_resumen_rendiciones([]);',
            '        return;',
            '    }',
            '',
            '    await _cargar_rendiciones_con_dueno(nombre_dueno);',
            '}',
            '',
            '/**',
            ' * Carga los dueños en el select del filtro. Solo admin.',
            ' */',
            'async function cargar_duenos_en_select_rendiciones() {',
            '    const select = document.getElementById(\'selector_dueno_rendiciones\');',
            '    if (!select) return;',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al cargar dueños", \'error\');',
            '        return;',
            '    }',
            '',
            '    select.innerHTML = \'<option value="">Seleccione dueño...</option>\';',
            '    datos.duenos.forEach(dueno => {',
            '        const opcion = document.createElement(\'option\');',
            '        opcion.value = dueno.nombre_usuario;',
            '        opcion.textContent = dueno.nombre_real',
            '            ? `${dueno.nombre_real} (${dueno.nombre_usuario})`',
            '            : dueno.nombre_usuario;',
            '        select.appendChild(opcion);',
            '    });',
            '',
            '    select.onchange = () => {',
            '        dueno_rendiciones_seleccionado = select.value;',
            '        limpiar_filtros_rendiciones_sin_render();',
            '        if (select.value) {',
            '            _cargar_rendiciones_con_dueno(select.value);',
            '        } else {',
            '            rendiciones_actuales = [];',
            '            renderizar_tabla_rendiciones([]);',
            '            renderizar_resumen_rendiciones([]);',
            '        }',
            '    };',
            '}',
            '',
            '/**',
            ' * Hace el fetch de rendiciones con los filtros actuales y renderiza.',
            ' */',
            'async function _cargar_rendiciones_con_dueno(nombre_dueno) {',
            '    const filtros = _recolectar_filtros_rendiciones();',
            '    const params = {',
            '        accion: "rendiciones/listar",',
            '        nombre_dueno,',
            '        ...filtros',
            '    };',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(params)',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al cargar rendiciones", \'error\');',
            '        return;',
            '    }',
            '',
            '    rendiciones_actuales = datos.rendiciones || [];',
            '    await cargar_terminales_en_filtro_rendiciones(nombre_dueno);',
            '    renderizar_tabla_rendiciones(rendiciones_actuales);',
            '    renderizar_resumen_rendiciones(rendiciones_actuales);',
            '    renderizar_chips_rendiciones();',
            '}',
            '',
            '/**',
            ' * Carga las terminales del dueño en el select del filtro. Lo hace',
            ' * solo la primera vez (o si el select está vacío).',
            ' */',
            'async function cargar_terminales_en_filtro_rendiciones(nombre_dueno) {',
            '    const select = document.getElementById(\'rendicion_filtro_terminal\');',
            '    if (!select) return;',
            '    if (select.options.length > 1) return;',
            '',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno })',
            '        });',
            '        const datos = await respuesta.json();',
            '        if (!datos.exito) return;',
            '        datos.terminales.forEach(t => {',
            '            const opcion = document.createElement(\'option\');',
            '            opcion.value = t.nombre_usuario;',
            '            opcion.textContent = t.nombre_real',
            '                ? `${t.nombre_real} (${t.nombre_usuario})`',
            '                : t.nombre_usuario;',
            '            select.appendChild(opcion);',
            '        });',
            '    } catch (e) {',
            '        console.error(\'Error al cargar terminales:\', e);',
            '    }',
            '}',
            '',
            '/**',
            ' * Recolecta los filtros del panel de Rendiciones.',
            ' */',
            'function _recolectar_filtros_rendiciones() {',
            '    return {',
            '        codigo: (document.getElementById(\'rendicion_filtro_codigo\')?.value || \'\').trim(),',
            '        fecha_desde: document.getElementById(\'rendicion_filtro_desde\')?.value || \'\',',
            '        fecha_hasta: document.getElementById(\'rendicion_filtro_hasta\')?.value || \'\',',
            '        terminal: document.getElementById(\'rendicion_filtro_terminal\')?.value || \'\'',
            '    };',
            '}',
            '',
            '/**',
            ' * Renderiza el resumen arriba de la tabla: total rendido y cantidad.',
            ' */',
            'function renderizar_resumen_rendiciones(rendiciones) {',
            '    const contenedor = document.getElementById(\'resumen_rendiciones\');',
            '    if (!contenedor) return;',
            '',
            '    let total = 0;',
            '    rendiciones.forEach(r => { total += parseFloat(r.total) || 0; });',
            '',
            '    const cant = rendiciones.length;',
            '    const txt_cant = cant === 1 ? \'1 rendición\' : `${cant} rendiciones`;',
            '',
            '    contenedor.innerHTML = `',
            '        <span class="resumen-rendiciones-item"><b>Total rendido:</b> $${_formatear_monto_rendiciones(total)}</span>',
            '        <span class="resumen-rendiciones-sep">·</span>',
            '        <span class="resumen-rendiciones-item">${txt_cant}</span>',
            '    `;',
            '}',
            '',
            '/**',
            ' * Renderiza la tabla de rendiciones.',
            ' */',
            'function renderizar_tabla_rendiciones(rendiciones) {',
            '    const contenedor = document.getElementById(\'tabla_rendiciones_container\');',
            '    if (!contenedor) return;',
            '',
            '    if (!rendiciones || rendiciones.length === 0) {',
            '        contenedor.innerHTML = \'<p style="color:#888; margin:20px;">Sin rendiciones registradas para los filtros seleccionados.</p>\';',
            '        return;',
            '    }',
            '',
            '    let html = \'<div class="table-wrap"><table class="data-table rendiciones-tabla">\';',
            '    html += \'<thead><tr>\';',
            '    html += \'<th>Código</th>\';',
            '    html += \'<th>Fecha</th>\';',
            '    html += \'<th>Terminales</th>\';',
            '    html += \'<th>Cupones</th>\';',
            '    html += \'<th>Efectivo</th>\';',
            '    html += \'<th>Banco</th>\';',
            '    html += \'<th>Total</th>\';',
            '    html += \'<th>Estado</th>\';',
            '    html += \'<th></th>\';',
            '    html += \'</tr></thead><tbody>\';',
            '',
            '    rendiciones.forEach(r => {',
            '        const badge = r.desactualizada',
            '            ? \'<span class="badge-desactualizada" title="\' + (r.motivo_desactualizada || \'\') + \'">Desactualizada</span>\'',
            '            : \'<span class="badge-vigente">Vigente</span>\';',
            '',
            '        const terminales = (r.terminales || []).map(t => t.terminal_nombre_real || t.terminal);',
            '        let terminales_txt = \'\';',
            '        if (terminales.length === 0) {',
            '            terminales_txt = \'—\';',
            '        } else if (terminales.length <= 2) {',
            '            terminales_txt = terminales.join(\', \');',
            '        } else {',
            '            terminales_txt = terminales.slice(0, 2).join(\', \') + ` y ${terminales.length - 2} más`;',
            '        }',
            '',
            '        html += \'<tr>\';',
            '        html += `<td><strong>${r.id_rendicion}</strong></td>`;',
            '        html += `<td>${r.fecha_hora}</td>`;',
            '        html += `<td>${terminales_txt}</td>`;',
            '        html += `<td class="num">${r.cantidad_cupones}</td>`;',
            '        html += `<td class="num">$${_formatear_monto_rendiciones(r.total_efectivo)}</td>`;',
            '        html += `<td class="num">$${_formatear_monto_rendiciones(r.total_banco)}</td>`;',
            '        html += `<td class="num"><b>$${_formatear_monto_rendiciones(r.total)}</b></td>`;',
            '        html += `<td>${badge}</td>`;',
            '        html += `<td><button class="btn ver_detalle_rendicion" data-id="${r.id_rendicion}">Ver detalle</button></td>`;',
            '        html += \'</tr>\';',
            '    });',
            '',
            '    html += \'</tbody></table></div>\';',
            '    contenedor.innerHTML = html;',
            '',
            '    contenedor.querySelectorAll(\'.ver_detalle_rendicion\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => ver_detalle_rendicion(btn.dataset.id));',
            '    });',
            '}',
            '',
            '/**',
            ' * Abre el modal con el detalle completo de una rendición.',
            ' */',
            'async function ver_detalle_rendicion(id_rendicion) {',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "rendiciones/obtener", id_rendicion })',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al obtener la rendición", \'error\');',
            '        return;',
            '    }',
            '',
            '    const r = datos.rendicion;',
            '',
            '    // Cartel de desactualizada.',
            '    let cartel_desact = \'\';',
            '    if (r.desactualizada) {',
            '        cartel_desact = `',
            '            <div class="rendicion-desactualizada-aviso">',
            '                <strong>Rendición desactualizada.</strong>',
            '                <div>${r.motivo_desactualizada}</div>',
            '            </div>',
            '        `;',
            '    }',
            '',
            '    // Tabla por terminal.',
            '    let terminales_html = \'\';',
            '    if (r.detalle_terminales && r.detalle_terminales.length > 0) {',
            '        terminales_html = r.detalle_terminales.map(t => `',
            '            <tr>',
            '                <td>${t.terminal_nombre_real || t.terminal}</td>',
            '                <td class="num">${t.cantidad_cupones}</td>',
            '                <td class="num">$${_formatear_monto_rendiciones(t.efectivo)}</td>',
            '                <td class="num">$${_formatear_monto_rendiciones(t.banco)}</td>',
            '                <td class="num"><b>$${_formatear_monto_rendiciones(t.total)}</b></td>',
            '            </tr>',
            '        `).join(\'\');',
            '    } else {',
            '        terminales_html = \'<tr><td colspan="5" style="text-align:center;color:#888;">Sin datos</td></tr>\';',
            '    }',
            '',
            '    // Tabla de cupones.',
            '    let cupones_html = \'\';',
            '    if (r.detalle_cupones && r.detalle_cupones.length > 0) {',
            '        cupones_html = r.detalle_cupones.map(c => {',
            '            const metodo = c.metodo_pago === \'transferencia\' ? \'Banco\' : \'Efectivo\';',
            '            const nombre_terminal = c.terminal_nombre_real || c.terminal;',
            '            return `',
            '                <tr>',
            '                    <td>${c.venta_id}</td>',
            '                    <td class="num">${c.numero_cupon}</td>',
            '                    <td class="num">$${_formatear_monto_rendiciones(c.monto)}</td>',
            '                    <td>${metodo}</td>',
            '                    <td>${nombre_terminal}</td>',
            '                    <td><button class="btn ver_compra_desde_rendicion" data-id="${c.venta_id}">Ver compra</button></td>',
            '                </tr>',
            '            `;',
            '        }).join(\'\');',
            '    } else {',
            '        cupones_html = \'<tr><td colspan="6" style="text-align:center;color:#888;">Sin datos</td></tr>\';',
            '    }',
            '',
            '    const html = `',
            '        <div class="rendicion-detalle">',
            '            <div class="rendicion-detalle-header">',
            '                <div class="rendicion-detalle-titulo">Rendición ${r.id_rendicion}</div>',
            '                <div class="rendicion-detalle-fecha">Cerrada el ${r.fecha_hora}</div>',
            '            </div>',
            '',
            '            ${cartel_desact}',
            '',
            '            <div class="rendicion-resumen">',
            '                <div class="rendicion-resumen-titulo">Resumen</div>',
            '                <div class="rendicion-resumen-grid">',
            '                    <div class="rendicion-resumen-item"><span>Total</span><b>$${_formatear_monto_rendiciones(r.total)}</b></div>',
            '                    <div class="rendicion-resumen-item"><span>Efectivo</span><b>$${_formatear_monto_rendiciones(r.total_efectivo)}</b></div>',
            '                    <div class="rendicion-resumen-item"><span>Banco</span><b>$${_formatear_monto_rendiciones(r.total_banco)}</b></div>',
            '                    <div class="rendicion-resumen-item"><span>Cupones</span><b>${r.cantidad_cupones}</b></div>',
            '                    <div class="rendicion-resumen-item"><span>Ventas</span><b>${r.cantidad_ventas}</b></div>',
            '                </div>',
            '            </div>',
            '',
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo">Por punto de venta</div>',
            '                <div class="table-wrap"><table class="rendicion-tabla">',
            '                    <thead><tr>',
            '                        <th>Terminal</th><th>Cupones</th><th>Efectivo</th><th>Banco</th><th>Total</th>',
            '                    </tr></thead>',
            '                    <tbody>${terminales_html}</tbody>',
            '                </table></div>',
            '            </div>',
            '',
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo">Cupones rendidos</div>',
            '                <div class="table-wrap"><table class="rendicion-tabla">',
            '                    <thead><tr>',
            '                        <th>Venta</th><th>Cupón</th><th>Monto</th><th>Método</th><th>Terminal</th><th></th>',
            '                    </tr></thead>',
            '                    <tbody>${cupones_html}</tbody>',
            '                </table></div>',
            '            </div>',
            '',
            '            <div class="rendicion-acciones">',
            '                <button class="btn" id="btn_cerrar_detalle_rendicion">Cerrar</button>',
            '            </div>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(\'Detalle de rendición\', html);',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    cont.querySelector(\'#btn_cerrar_detalle_rendicion\').addEventListener(\'click\', cerrar_modal_generico);',
            '',
            '    cont.querySelectorAll(\'.ver_compra_desde_rendicion\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => {',
            '            const id_venta = btn.dataset.id;',
            '            const nombre_dueno = obtener_nombre_dueno_rendiciones();',
            '            ir_a_venta_en_vendidos(id_venta, nombre_dueno);',
            '        });',
            '    });',
            '}',
            '',
            '/**',
            ' * Arma la tira de chips con los filtros activos de Rendiciones.',
            ' */',
            'function renderizar_chips_rendiciones() {',
            '    const contenedor = document.getElementById(\'chips_filtros_activos_rendiciones\');',
            '    if (!contenedor) return;',
            '',
            '    const chips = [];',
            '',
            '    const codigo = (document.getElementById(\'rendicion_filtro_codigo\')?.value || \'\').trim();',
            '    if (codigo) chips.push({ id: \'codigo\', texto: `Código: ${codigo}` });',
            '',
            '    const desde = document.getElementById(\'rendicion_filtro_desde\')?.value || \'\';',
            '    if (desde) {',
            '        const partes = desde.split(\'-\');',
            '        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : desde;',
            '        chips.push({ id: \'fecha_desde\', texto: `Desde: ${visible}` });',
            '    }',
            '',
            '    const hasta = document.getElementById(\'rendicion_filtro_hasta\')?.value || \'\';',
            '    if (hasta) {',
            '        const partes = hasta.split(\'-\');',
            '        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : hasta;',
            '        chips.push({ id: \'fecha_hasta\', texto: `Hasta: ${visible}` });',
            '    }',
            '',
            '    const sel_terminal = document.getElementById(\'rendicion_filtro_terminal\');',
            '    if (sel_terminal && sel_terminal.value) {',
            '        const txt = sel_terminal.options[sel_terminal.selectedIndex]?.textContent || sel_terminal.value;',
            '        chips.push({ id: \'terminal\', texto: `Terminal: ${txt}` });',
            '    }',
            '',
            '    if (chips.length === 0) {',
            '        contenedor.innerHTML = \'\';',
            '        return;',
            '    }',
            '',
            '    contenedor.innerHTML = chips.map(c => (',
            '        `<span class="chip-filtro">${c.texto}<button type="button" class="chip-cerrar" data-chip="${c.id}" aria-label="Quitar filtro">✕</button></span>`',
            '    )).join(\'\');',
            '',
            '    contenedor.querySelectorAll(\'.chip-cerrar\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => {',
            '            limpiar_filtro_rendicion_individual(btn.dataset.chip);',
            '            _cargar_rendiciones_con_dueno(obtener_nombre_dueno_rendiciones());',
            '        });',
            '    });',
            '}',
            '',
            '/**',
            ' * Resetea un filtro individual de Rendiciones.',
            ' */',
            'function limpiar_filtro_rendicion_individual(id) {',
            '    switch (id) {',
            '        case \'codigo\': {',
            '            const el = document.getElementById(\'rendicion_filtro_codigo\');',
            '            if (el) el.value = \'\';',
            '            break;',
            '        }',
            '        case \'fecha_desde\': {',
            '            const el = document.getElementById(\'rendicion_filtro_desde\');',
            '            if (el) el.value = \'\';',
            '            break;',
            '        }',
            '        case \'fecha_hasta\': {',
            '            const el = document.getElementById(\'rendicion_filtro_hasta\');',
            '            if (el) el.value = \'\';',
            '            break;',
            '        }',
            '        case \'terminal\': {',
            '            const el = document.getElementById(\'rendicion_filtro_terminal\');',
            '            if (el) el.value = \'\';',
            '            break;',
            '        }',
            '    }',
            '}',
            '',
            '/**',
            ' * Limpia los filtros de Rendiciones sin disparar render. Se usa',
            ' * cuando cambia el dueño seleccionado.',
            ' */',
            'function limpiar_filtros_rendiciones_sin_render() {',
            '    limpiar_filtro_rendicion_individual(\'codigo\');',
            '    limpiar_filtro_rendicion_individual(\'fecha_desde\');',
            '    limpiar_filtro_rendicion_individual(\'fecha_hasta\');',
            '    limpiar_filtro_rendicion_individual(\'terminal\');',
            '    const sel_term = document.getElementById(\'rendicion_filtro_terminal\');',
            '    if (sel_term) {',
            '        sel_term.innerHTML = \'<option value="">Todas</option>\';',
            '    }',
            '    renderizar_chips_rendiciones();',
            '}',
            '',
            '/**',
            ' * Limpia todos los filtros de Rendiciones y recarga.',
            ' */',
            'function limpiar_filtros_rendiciones() {',
            '    limpiar_filtros_rendiciones_sin_render();',
            '    const nombre_dueno = obtener_nombre_dueno_rendiciones();',
            '    if (nombre_dueno) {',
            '        _cargar_rendiciones_con_dueno(nombre_dueno);',
            '    }',
            '}',
            '',
            '// ===== Inicialización de listeners =====',
            '(function() {',
            '    const input_codigo = document.getElementById(\'rendicion_filtro_codigo\');',
            '    if (input_codigo) input_codigo.addEventListener(\'input\', () => {',
            '        const nd = obtener_nombre_dueno_rendiciones();',
            '        if (nd) _cargar_rendiciones_con_dueno(nd);',
            '    });',
            '    const input_desde = document.getElementById(\'rendicion_filtro_desde\');',
            '    if (input_desde) input_desde.addEventListener(\'change\', () => {',
            '        const nd = obtener_nombre_dueno_rendiciones();',
            '        if (nd) _cargar_rendiciones_con_dueno(nd);',
            '    });',
            '    const input_hasta = document.getElementById(\'rendicion_filtro_hasta\');',
            '    if (input_hasta) input_hasta.addEventListener(\'change\', () => {',
            '        const nd = obtener_nombre_dueno_rendiciones();',
            '        if (nd) _cargar_rendiciones_con_dueno(nd);',
            '    });',
            '    const sel_terminal = document.getElementById(\'rendicion_filtro_terminal\');',
            '    if (sel_terminal) sel_terminal.addEventListener(\'change\', () => {',
            '        const nd = obtener_nombre_dueno_rendiciones();',
            '        if (nd) _cargar_rendiciones_con_dueno(nd);',
            '    });',
            '    const btn_limpiar = document.getElementById(\'boton_limpiar_filtros_rendiciones\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', limpiar_filtros_rendiciones);',
            '})();',
            '',
        ],
    ],

    // ============================================================
    // 2. Crear estilos-rendiciones.css
    // ============================================================
    [
        'tipo' => 'crear',
        'archivo' => 'estilos-rendiciones.css',
        'descripcion' => 'Nuevo archivo de estilos para rendiciones',
        'contenido' => [
            '/* ===== Rendiciones: layout, tabla, badge de desactualizada, modal ===== */',
            '/* Los estilos base (panel, field, btn, etc.) están en estilos.css. */',
            '/* Los estilos de sidebar y chips se reutilizan de estilos-ventas.css. */',
            '',
            '/* Grilla principal: sidebar 260px + contenido. */',
            '.rendiciones-layout {',
            '    display: grid;',
            '    grid-template-columns: 260px 1fr;',
            '    gap: 20px;',
            '    align-items: start;',
            '}',
            '',
            '/* Sidebar: mismo look que el de Vendidos. */',
            '.rendiciones-sidebar {',
            '    position: sticky;',
            '    top: 125px;',
            '    max-height: calc(100vh - 140px);',
            '    overflow-y: auto;',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 10px;',
            '    background: #fffbe6;',
            '    border: 1px solid var(--border);',
            '    border-radius: 8px;',
            '    padding: 10px;',
            '}',
            '',
            '/* Contenido principal. */',
            '.rendiciones-contenido {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 14px;',
            '    min-width: 0;',
            '}',
            '',
            '/* Resumen arriba de la tabla. */',
            '.resumen-rendiciones {',
            '    display: flex;',
            '    flex-wrap: wrap;',
            '    gap: 8px 14px;',
            '    align-items: baseline;',
            '    background: var(--panel-claro);',
            '    border: 1px solid var(--border);',
            '    border-radius: 6px;',
            '    padding: 10px 14px;',
            '    font-size: 14px;',
            '}',
            '.resumen-rendiciones-item { color: var(--text); }',
            '.resumen-rendiciones-item b { color: var(--primary-dark); }',
            '.resumen-rendiciones-sep { color: var(--muted); opacity: 0.6; }',
            '',
            '/* Tabla de rendiciones. */',
            '.rendiciones-tabla th,',
            '.rendiciones-tabla td {',
            '    vertical-align: middle;',
            '}',
            '.rendiciones-tabla td.num {',
            '    text-align: right;',
            '    font-variant-numeric: tabular-nums;',
            '}',
            '',
            '/* Badge de estado. */',
            '.badge-vigente {',
            '    display: inline-block;',
            '    padding: 3px 10px;',
            '    border-radius: 12px;',
            '    font-size: 12px;',
            '    font-weight: 700;',
            '    background: #e7f4ea;',
            '    color: #28713b;',
            '}',
            '.badge-desactualizada {',
            '    display: inline-block;',
            '    padding: 3px 10px;',
            '    border-radius: 12px;',
            '    font-size: 12px;',
            '    font-weight: 700;',
            '    background: #fff2d7;',
            '    color: #8a6500;',
            '    cursor: help;',
            '}',
            '',
            '/* Modal de detalle. */',
            '.rendicion-detalle {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 16px;',
            '}',
            '',
            '.rendicion-detalle-header {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 4px;',
            '    padding-bottom: 10px;',
            '    border-bottom: 1px solid var(--border);',
            '}',
            '.rendicion-detalle-titulo {',
            '    font-size: 18px;',
            '    font-weight: 700;',
            '    color: var(--primary-dark);',
            '}',
            '.rendicion-detalle-fecha {',
            '    font-size: 13px;',
            '    color: var(--muted);',
            '}',
            '',
            '/* Cartel de desactualizada. */',
            '.rendicion-desactualizada-aviso {',
            '    background: #fff3cd;',
            '    border: 1px solid #ffc107;',
            '    color: #856404;',
            '    padding: 10px 14px;',
            '    border-radius: 6px;',
            '    font-size: 13px;',
            '}',
            '.rendicion-desactualizada-aviso strong {',
            '    display: block;',
            '    margin-bottom: 4px;',
            '}',
            '',
            '/* Responsive: en pantallas chicas el sidebar pasa arriba. */',
            '@media (max-width: 950px) {',
            '    .rendiciones-layout {',
            '        grid-template-columns: 1fr;',
            '    }',
            '    .rendiciones-sidebar {',
            '        position: static;',
            '        max-height: none;',
            '    }',
            '}',
            '',
        ],
    ],

    // ============================================================
    // 3. aplicacion.js: bump + pestañas + carga
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump @version a 1.5piloto.51',
        'buscar' => [
            ' * @version 1.5piloto.45',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.51',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar rendiciones a las pestañas permitidas',
        'buscar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
        'reemplazar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'rendiciones\', \'pasajeros\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'rendiciones\', \'pasajeros\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar rendiciones a los nombres de pestañas',
        'buscar' => [
            '    const nombres_pestanas = {',
            '        admin: \'Administrador\',',
            '        micros: \'Empresas/Micros\',',
            '        viajes: \'Viajes\',',
            '        vendidos: \'Vendidos\',',
            '        pasajeros: \'Pasajeros/Clientes\',',
            '        terminales: \'Puntos de venta\'',
            '    };',
        ],
        'reemplazar' => [
            '    const nombres_pestanas = {',
            '        admin: \'Administrador\',',
            '        micros: \'Empresas/Micros\',',
            '        viajes: \'Viajes\',',
            '        vendidos: \'Vendidos\',',
            '        rendiciones: \'Rendiciones\',',
            '        pasajeros: \'Pasajeros/Clientes\',',
            '        terminales: \'Puntos de venta\'',
            '    };',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: activar_pestana carga rendiciones',
        'buscar' => [
            '    if (id_pestana === \'vendidos\') return cargar_ventas();',
            '    if (id_pestana === \'pasajeros\') return cargar_pasajeros();',
        ],
        'reemplazar' => [
            '    if (id_pestana === \'vendidos\') return cargar_ventas();',
            '    if (id_pestana === \'rendiciones\') return cargar_rendiciones();',
            '    if (id_pestana === \'pasajeros\') return cargar_pasajeros();',
        ],
    ],

    // ============================================================
    // 4. aplicacion_GET.html: link CSS, script JS, sección, bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: agregar link de estilos-rendiciones.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.50">',
            '</head>',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.50">',
            '<link rel="stylesheet" href="estilos-rendiciones.css?v=1.5piloto.51">',
            '</head>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: agregar script de rendiciones.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.50"></script>',
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.42"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.50"></script>',
            '<script src="Aplicacion/rendiciones.js?v=1.5piloto.51"></script>',
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.42"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de aplicacion.js a ?v=1.5piloto.51',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.45"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.51"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: sección nueva de Rendiciones',
        'buscar' => [
            '    <!-- Sección Pasajeros -->',
            '    <section id="pasajeros" class="tab-content hidden">',
        ],
        'reemplazar' => [
            '    <!-- Sección Rendiciones -->',
            '    <section id="rendiciones" class="tab-content hidden">',
            '      <div class="panel">',
            '        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">',
            '          <h2 style="margin:0;">Rendiciones</h2>',
            '        </div>',
            '',
            '        <!-- Selector de dueño (solo admin) -->',
            '        <div id="contenedor_filtro_dueno_rendiciones" style="display:none; margin-bottom:14px;">',
            '          <label for="selector_dueno_rendiciones" style="font-weight:600;">Dueño:</label>',
            '          <select id="selector_dueno_rendiciones"><option value="">Seleccione dueño...</option></select>',
            '        </div>',
            '',
            '        <div class="rendiciones-layout">',
            '          <aside class="rendiciones-sidebar">',
            '            <div class="filtros-grupo">',
            '              <div class="filtros-grupo-titulo">Buscar</div>',
            '              <div class="field">',
            '                <label for="rendicion_filtro_codigo">Código</label>',
            '                <input type="text" id="rendicion_filtro_codigo" placeholder="Buscar código...">',
            '              </div>',
            '            </div>',
            '',
            '            <div class="filtros-grupo">',
            '              <div class="filtros-grupo-titulo">Fecha</div>',
            '              <div class="field">',
            '                <label for="rendicion_filtro_desde">Desde</label>',
            '                <input type="date" id="rendicion_filtro_desde">',
            '              </div>',
            '              <div class="field">',
            '                <label for="rendicion_filtro_hasta">Hasta</label>',
            '                <input type="date" id="rendicion_filtro_hasta">',
            '              </div>',
            '            </div>',
            '',
            '            <div class="filtros-grupo">',
            '              <div class="filtros-grupo-titulo">Terminal</div>',
            '              <div class="field">',
            '                <label for="rendicion_filtro_terminal">Terminal</label>',
            '                <select id="rendicion_filtro_terminal"><option value="">Todas</option></select>',
            '              </div>',
            '            </div>',
            '',
            '            <button class="btn" id="boton_limpiar_filtros_rendiciones" style="width:100%;">Limpiar filtros</button>',
            '          </aside>',
            '',
            '          <div class="rendiciones-contenido">',
            '            <div id="chips_filtros_activos_rendiciones" class="chips-filtros"></div>',
            '            <div id="resumen_rendiciones" class="resumen-rendiciones"></div>',
            '            <div id="tabla_rendiciones_container"></div>',
            '          </div>',
            '        </div>',
            '      </div>',
            '    </section>',
            '',
            '    <!-- Sección Pasajeros -->',
            '    <section id="pasajeros" class="tab-content hidden">',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$creaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";
