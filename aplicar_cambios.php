<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.50 Tanda B: frontend del flujo de rendición.
 * - Botón "Cerrar rendición" en el panel de Vendidos (solo dueño).
 * - Modal con preview inmutable: totales, tabla por terminal, lista de ventas.
 * - Checkboxes para destildar ventas; totales recalculados al toque.
 * - Confirmación contra el endpoint rendiciones/confirmar.
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
    // --------------------------------------------------------
    // ventas.js: bump de versión
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.50',
        'buscar' => [
            ' * @version 1.5piloto.49d',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.50',
        ],
    ],

    // --------------------------------------------------------
    // ventas.js: mostrar/ocultar botón según rol
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: mostrar botón Cerrar rendición solo a dueño',
        'buscar' => [
            'function configurar_filtros_vendidos() {',
            '    const cont_dueno = document.getElementById(\'contenedor_filtro_dueno_vendidos\');',
            '    const es_admin = usuario_actual.nivel === \'admin\';',
            '    if (cont_dueno) cont_dueno.style.display = es_admin ? \'\' : \'none\';',
            '}',
        ],
        'reemplazar' => [
            'function configurar_filtros_vendidos() {',
            '    const cont_dueno = document.getElementById(\'contenedor_filtro_dueno_vendidos\');',
            '    const es_admin = usuario_actual.nivel === \'admin\';',
            '    if (cont_dueno) cont_dueno.style.display = es_admin ? \'\' : \'none\';',
            '',
            '    // El botón "Cerrar rendición" es exclusivo del dueño.',
            '    const btn_rendir = document.getElementById(\'boton_cerrar_rendicion\');',
            '    if (btn_rendir) {',
            '        btn_rendir.style.display = (usuario_actual.nivel === \'dueno\') ? \'\' : \'none\';',
            '    }',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // ventas.js: funciones del flujo de rendición
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: funciones del flujo de rendición',
        'buscar' => [
            '// ===== Inicialización de listeners del panel Vendidos =====',
            '// El select de Estado es estático (no se regenera), así que el listener',
            '// se conecta una sola vez al cargar el script.',
            '(function() {',
        ],
        'reemplazar' => [
            '/**',
            ' * Formatea un monto numérico al estilo argentino (miles con punto,',
            ' * decimales con coma).',
            ' *',
            ' * @param {number|string} monto',
            ' * @returns {string}',
            ' */',
            'function _formatear_monto_rendicion(monto) {',
            '    const n = parseFloat(monto) || 0;',
            '    return n.toLocaleString(\'es-AR\', { minimumFractionDigits: 2, maximumFractionDigits: 2 });',
            '}',
            '',
            '/**',
            ' * Recolecta los filtros actuales del panel Vendidos y los devuelve',
            ' * como objeto listo para mandar al backend. NO incluye `rendido`',
            ' * (la rendición siempre opera sobre cupones sin rendir).',
            ' *',
            ' * @returns {object}',
            ' */',
            'function _recolectar_filtros_vendidos() {',
            '    return {',
            '        viaje: document.getElementById(\'selector_viaje_vendido\')?.value || \'todos\',',
            '        vendedor: document.getElementById(\'filtro_vendedor\')?.value || \'Todos\',',
            '        estado: document.getElementById(\'filtro_estado\')?.value || \'todos\',',
            '        codigo: (document.getElementById(\'filtro_codigo_venta\')?.value || \'\').trim(),',
            '        comprador: (document.getElementById(\'filtro_comprador\')?.value || \'\').trim(),',
            '        fecha_desde: document.getElementById(\'filtro_fecha_desde\')?.value || \'\',',
            '        fecha_hasta: document.getElementById(\'filtro_fecha_hasta\')?.value || \'\'',
            '    };',
            '}',
            '',
            '/**',
            ' * Abre el modal de cierre de rendición. Pide el preview al backend,',
            ' * guarda el estado en window y renderiza el modal. Si no hay nada',
            ' * para rendir, muestra el modal con un mensaje vacío.',
            ' */',
            'async function cerrar_rendicion() {',
            '    if (!usuario_actual) return;',
            '    if (usuario_actual.nivel !== \'dueno\') {',
            '        mostrar_aviso(\'Solo el dueño puede cerrar rendiciones\', \'error\');',
            '        return;',
            '    }',
            '',
            '    const nombre_dueno = usuario_actual.nombre_usuario;',
            '    const filtros = _recolectar_filtros_vendidos();',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "rendiciones/previsualizar",',
            '            nombre_dueno,',
            '            ...filtros',
            '        })',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || \'Error al previsualizar rendición\', \'error\');',
            '        return;',
            '    }',
            '',
            '    const preview = datos.preview;',
            '',
            '    // Guardar estado global para el confirm y para el recálculo.',
            '    window.rendicion_preview_actual = preview;',
            '    window.rendicion_filtros_actual = filtros;',
            '    window.rendicion_seleccion = (preview.ventas || []).map(v => v.id_venta);',
            '',
            '    abrir_modal_generico(\'Cerrar rendición\', \'\');',
            '    _renderizar_modal_rendicion();',
            '}',
            '',
            '/**',
            ' * Renderiza (o re-renderiza) el contenido del modal de rendición a',
            ' * partir del preview guardado en window. Se llama al abrir el modal.',
            ' */',
            'function _renderizar_modal_rendicion() {',
            '    const preview = window.rendicion_preview_actual;',
            '    if (!preview) return;',
            '',
            '    const ventas = Array.isArray(preview.ventas) ? preview.ventas : [];',
            '',
            '    if (ventas.length === 0) {',
            '        const html_vacio = `',
            '            <div class="rendicion-aviso">',
            '                <div class="rendicion-datos">',
            '                    <div class="rendicion-dato-linea"><span>Código propuesto:</span><b>${preview.id_rendicion_propuesto}</b></div>',
            '                    <div class="rendicion-dato-linea"><span>Fecha:</span><b>${preview.fecha_hora}</b></div>',
            '                </div>',
            '                <div class="rendicion-sin-datos">',
            '                    No hay cupones pagados sin rendir que coincidan con los filtros aplicados.',
            '                </div>',
            '                <div class="rendicion-acciones">',
            '                    <button class="btn" id="btn_cerrar_rendicion_modal">Cerrar</button>',
            '                </div>',
            '            </div>',
            '        `;',
            '        const cont = document.getElementById(\'modal_generico_contenido\');',
            '        if (cont) cont.innerHTML = html_vacio;',
            '        document.getElementById(\'btn_cerrar_rendicion_modal\')?.addEventListener(\'click\', cerrar_modal_generico);',
            '        return;',
            '    }',
            '',
            '    // Lista de ventas con checkbox.',
            '    let ventas_html = \'\';',
            '    ventas.forEach(v => {',
            '        const cupones_nums = v.cupones.map(c => c.numero).join(\', \');',
            '        const viaje_micro = [v.viaje_visible, v.micro_visible].filter(x => x).join(\' · \');',
            '        ventas_html += `',
            '            <label class="rendicion-venta-item">',
            '                <input type="checkbox" class="rendicion-venta-check" data-id-venta="${v.id_venta}" checked>',
            '                <div class="rendicion-venta-info">',
            '                    <div class="rendicion-venta-id">Venta ${v.id_venta}</div>',
            '                    ${viaje_micro ? `<div class="rendicion-venta-detalle">${viaje_micro}</div>` : \'\'}',
            '                    <div class="rendicion-venta-detalle">${v.terminal_nombre_real || v.terminal} · Cupones ${cupones_nums}</div>',
            '                </div>',
            '                <div class="rendicion-venta-monto">$${_formatear_monto_rendicion(v.total_sin_rendir)}</div>',
            '            </label>',
            '        `;',
            '    });',
            '',
            '    const html = `',
            '        <div class="rendicion-aviso">',
            '            <div class="rendicion-datos">',
            '                <div class="rendicion-dato-linea"><span>Código propuesto:</span><b>${preview.id_rendicion_propuesto}</b></div>',
            '                <div class="rendicion-dato-linea"><span>Fecha:</span><b>${preview.fecha_hora}</b></div>',
            '            </div>',
            '',
            '            <div class="rendicion-resumen">',
            '                <div class="rendicion-resumen-titulo">Resumen a rendir</div>',
            '                <div class="rendicion-resumen-grid">',
            '                    <div class="rendicion-resumen-item">',
            '                        <span>Total</span>',
            '                        <b id="rendicion_total">$0,00</b>',
            '                    </div>',
            '                    <div class="rendicion-resumen-item">',
            '                        <span>En efectivo</span>',
            '                        <b id="rendicion_efectivo">$0,00</b>',
            '                    </div>',
            '                    <div class="rendicion-resumen-item">',
            '                        <span>En banco</span>',
            '                        <b id="rendicion_banco">$0,00</b>',
            '                    </div>',
            '                    <div class="rendicion-resumen-item">',
            '                        <span>Cupones</span>',
            '                        <b id="rendicion_cupones">0</b>',
            '                    </div>',
            '                    <div class="rendicion-resumen-item">',
            '                        <span>Ventas</span>',
            '                        <b id="rendicion_ventas">0</b>',
            '                    </div>',
            '                </div>',
            '            </div>',
            '',
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo">Por punto de venta</div>',
            '                <table class="rendicion-tabla">',
            '                    <thead>',
            '                        <tr>',
            '                            <th>Terminal</th>',
            '                            <th>Cupones</th>',
            '                            <th>Efectivo</th>',
            '                            <th>Banco</th>',
            '                            <th>Total</th>',
            '                        </tr>',
            '                    </thead>',
            '                    <tbody id="rendicion_tabla_terminales_body"></tbody>',
            '                </table>',
            '            </div>',
            '',
            '            <div class="rendicion-seccion">',
            '                <div class="rendicion-seccion-titulo">Ventas incluidas (destildá las que no quieras rendir)</div>',
            '                <div class="rendicion-ventas-lista">',
            '                    ${ventas_html}',
            '                </div>',
            '            </div>',
            '',
            '            <div class="rendicion-acciones">',
            '                <button class="btn primary" id="btn_confirmar_rendicion">Confirmar rendición y guardar</button>',
            '                <button class="btn" id="btn_cerrar_rendicion_modal">Cerrar</button>',
            '            </div>',
            '        </div>',
            '    `;',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    if (cont) cont.innerHTML = html;',
            '',
            '    // Listeners de checkboxes.',
            '    cont.querySelectorAll(\'.rendicion-venta-check\').forEach(chk => {',
            '        chk.addEventListener(\'change\', function() {',
            '            const id = this.dataset.idVenta;',
            '            if (this.checked) {',
            '                if (!window.rendicion_seleccion.includes(id)) window.rendicion_seleccion.push(id);',
            '            } else {',
            '                window.rendicion_seleccion = window.rendicion_seleccion.filter(x => x !== id);',
            '            }',
            '            _recalcular_totales_rendicion();',
            '        });',
            '    });',
            '',
            '    document.getElementById(\'btn_cerrar_rendicion_modal\').addEventListener(\'click\', cerrar_modal_generico);',
            '    document.getElementById(\'btn_confirmar_rendicion\').addEventListener(\'click\', confirmar_rendicion_modal);',
            '',
            '    // Primer cálculo.',
            '    _recalcular_totales_rendicion();',
            '}',
            '',
            '/**',
            ' * Recalcula totales y tabla por terminal según las ventas tildadas.',
            ' * Actualiza solo los nodos del DOM (no re-renderiza el modal entero).',
            ' */',
            'function _recalcular_totales_rendicion() {',
            '    const preview = window.rendicion_preview_actual;',
            '    if (!preview) return;',
            '',
            '    const seleccion = new Set(window.rendicion_seleccion || []);',
            '',
            '    let total = 0, efvo = 0, banco = 0, cupones = 0, cant_ventas = 0;',
            '    const por_terminal = {};',
            '',
            '    (preview.ventas || []).forEach(v => {',
            '        if (!seleccion.has(v.id_venta)) return;',
            '        cant_ventas++;',
            '        total += parseFloat(v.total_sin_rendir) || 0;',
            '',
            '        const t = v.terminal;',
            '        if (!por_terminal[t]) {',
            '            por_terminal[t] = {',
            '                nombre: v.terminal_nombre_real || v.terminal,',
            '                cupones: 0, efvo: 0, banco: 0, total: 0',
            '            };',
            '        }',
            '',
            '        v.cupones.forEach(c => {',
            '            const m = parseFloat(c.monto) || 0;',
            '            cupones++;',
            '            por_terminal[t].cupones++;',
            '            por_terminal[t].total += m;',
            '            if (c.metodo_pago === \'transferencia\') {',
            '                banco += m;',
            '                por_terminal[t].banco += m;',
            '            } else {',
            '                efvo += m;',
            '                por_terminal[t].efvo += m;',
            '            }',
            '        });',
            '    });',
            '',
            '    // Actualizar resumen.',
            '    const el_total = document.getElementById(\'rendicion_total\');',
            '    const el_efvo = document.getElementById(\'rendicion_efectivo\');',
            '    const el_banco = document.getElementById(\'rendicion_banco\');',
            '    const el_cup = document.getElementById(\'rendicion_cupones\');',
            '    const el_ventas = document.getElementById(\'rendicion_ventas\');',
            '    if (el_total) el_total.textContent = \'$\' + _formatear_monto_rendicion(total);',
            '    if (el_efvo) el_efvo.textContent = \'$\' + _formatear_monto_rendicion(efvo);',
            '    if (el_banco) el_banco.textContent = \'$\' + _formatear_monto_rendicion(banco);',
            '    if (el_cup) el_cup.textContent = String(cupones);',
            '    if (el_ventas) el_ventas.textContent = String(cant_ventas);',
            '',
            '    // Actualizar tabla por terminal.',
            '    const tbody = document.getElementById(\'rendicion_tabla_terminales_body\');',
            '    if (tbody) {',
            '        const filas = Object.values(por_terminal).sort((a, b) => a.nombre.localeCompare(b.nombre));',
            '        if (filas.length === 0) {',
            '            tbody.innerHTML = \'<tr><td colspan="5" style="text-align:center;color:#888;">Sin ventas seleccionadas</td></tr>\';',
            '        } else {',
            '            tbody.innerHTML = filas.map(f => `',
            '                <tr>',
            '                    <td>${f.nombre}</td>',
            '                    <td class="num">${f.cupones}</td>',
            '                    <td class="num">$${_formatear_monto_rendicion(f.efvo)}</td>',
            '                    <td class="num">$${_formatear_monto_rendicion(f.banco)}</td>',
            '                    <td class="num">$${_formatear_monto_rendicion(f.total)}</td>',
            '                </tr>',
            '            `).join(\'\');',
            '        }',
            '    }',
            '',
            '    // Botón confirmar: deshabilitado si no hay ventas seleccionadas.',
            '    const btn = document.getElementById(\'btn_confirmar_rendicion\');',
            '    if (btn) btn.disabled = (cant_ventas === 0);',
            '}',
            '',
            '/**',
            ' * Confirma la rendición: manda al backend la lista exacta de cupones',
            ' * que el usuario vio y dejó tildados. Si el backend detecta que el',
            ' * estado cambió, muestra error y no cierra el modal.',
            ' */',
            'async function confirmar_rendicion_modal() {',
            '    const preview = window.rendicion_preview_actual;',
            '    if (!preview) return;',
            '',
            '    const seleccion = new Set(window.rendicion_seleccion || []);',
            '    const ventas_seleccionadas = (preview.ventas || [])',
            '        .filter(v => seleccion.has(v.id_venta))',
            '        .map(v => ({',
            '            id_venta: v.id_venta,',
            '            cupones: v.cupones.map(c => String(c.numero))',
            '        }));',
            '',
            '    if (ventas_seleccionadas.length === 0) {',
            '        mostrar_aviso(\'No hay ventas seleccionadas para rendir\', \'error\');',
            '        return;',
            '    }',
            '',
            '    const filtros = window.rendicion_filtros_actual || {};',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "rendiciones/confirmar",',
            '            nombre_dueno: usuario_actual.nombre_usuario,',
            '            ventas_seleccionadas: JSON.stringify(ventas_seleccionadas),',
            '            ...filtros',
            '        })',
            '    });',
            '    const resultado = await respuesta.json();',
            '',
            '    if (!resultado.exito) {',
            '        mostrar_aviso(resultado.error || \'No se pudo cerrar la rendición\', \'error\');',
            '        return;',
            '    }',
            '',
            '    mostrar_aviso(`Rendición ${resultado.id_rendicion} guardada ($${_formatear_monto_rendicion(resultado.total)})`, \'exito\');',
            '',
            '    // Limpiar estado y cerrar modal.',
            '    window.rendicion_preview_actual = null;',
            '    window.rendicion_filtros_actual = null;',
            '    window.rendicion_seleccion = null;',
            '    cerrar_modal_generico();',
            '',
            '    // Refrescar la lista de ventas para que las tarjetas reflejen',
            '    // el nuevo estado (a_rendir actualizado).',
            '    if (typeof cargar_ventas === \'function\') cargar_ventas();',
            '}',
            '',
            '// ===== Inicialización de listeners del panel Vendidos =====',
            '// El select de Estado es estático (no se regenera), así que el listener',
            '// se conecta una sola vez al cargar el script.',
            '(function() {',
        ],
    ],

    // --------------------------------------------------------
    // ventas.js: listener del botón Cerrar rendición
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: listener del botón Cerrar rendición',
        'buscar' => [
            '    const btn_limpiar = document.getElementById(\'boton_limpiar_filtros_vendidos\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', limpiar_filtros_vendidos);',
            '})();',
        ],
        'reemplazar' => [
            '    const btn_limpiar = document.getElementById(\'boton_limpiar_filtros_vendidos\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', limpiar_filtros_vendidos);',
            '    const btn_rendir = document.getElementById(\'boton_cerrar_rendicion\');',
            '    if (btn_rendir) btn_rendir.addEventListener(\'click\', cerrar_rendicion);',
            '})();',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html: bump de ventas.js a 50
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de ventas.js a ?v=1.5piloto.50',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.49d"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.50"></script>',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html: bump de estilos-ventas.css a 50
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de estilos-ventas.css a ?v=1.5piloto.50',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.49e">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-ventas.css?v=1.5piloto.50">',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html: botón Cerrar rendición
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: botón Cerrar rendición en el header de Vendidos',
        'buscar' => [
            '          <h2 style="margin:0;">Ventas realizadas</h2>',
            '          <button class="btn primary" id="boton_imprimir_informe_ventas">Imprimir informe</button>',
            '        </div>',
        ],
        'reemplazar' => [
            '          <h2 style="margin:0;">Ventas realizadas</h2>',
            '          <div style="display:flex; gap:8px; flex-wrap:wrap;">',
            '            <button class="btn" id="boton_cerrar_rendicion" style="display:none;">Cerrar rendición</button>',
            '            <button class="btn primary" id="boton_imprimir_informe_ventas">Imprimir informe</button>',
            '          </div>',
            '        </div>',
        ],
    ],

    // --------------------------------------------------------
    // estilos-ventas.css: estilos del modal de rendición
    // --------------------------------------------------------
    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-ventas.css',
        'descripcion' => 'estilos-ventas.css: estilos del modal de rendición',
        'buscar' => [
            '/* Responsive: en pantallas chicas el sidebar pasa arriba, full width. */',
            '@media (max-width: 950px) {',
            '    .vendidos-layout {',
            '        grid-template-columns: 1fr;',
            '    }',
            '    .vendidos-sidebar {',
            '        position: static;',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '/* Responsive: en pantallas chicas el sidebar pasa arriba, full width. */',
            '@media (max-width: 950px) {',
            '    .vendidos-layout {',
            '        grid-template-columns: 1fr;',
            '    }',
            '    .vendidos-sidebar {',
            '        position: static;',
            '    }',
            '}',
            '',
            '/* ===== v1.5piloto.50: modal de cierre de rendición ===== */',
            '',
            '.rendicion-aviso {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 16px;',
            '}',
            '',
            '.rendicion-datos {',
            '    display: flex;',
            '    flex-wrap: wrap;',
            '    gap: 6px 20px;',
            '    background: var(--panel-claro);',
            '    border: 1px solid var(--border);',
            '    border-radius: 6px;',
            '    padding: 10px 14px;',
            '    font-size: 13px;',
            '}',
            '.rendicion-dato-linea {',
            '    display: flex;',
            '    gap: 6px;',
            '    align-items: baseline;',
            '}',
            '.rendicion-dato-linea span { color: var(--muted); }',
            '.rendicion-dato-linea b { color: var(--text); font-weight: 700; }',
            '',
            '.rendicion-resumen {',
            '    background: #eef4ff;',
            '    border: 2px solid #4a7ab8;',
            '    border-radius: 8px;',
            '    padding: 14px;',
            '}',
            '.rendicion-resumen-titulo {',
            '    font-size: 12px;',
            '    font-weight: 700;',
            '    text-transform: uppercase;',
            '    letter-spacing: 0.5px;',
            '    color: #2a4a78;',
            '    margin-bottom: 10px;',
            '}',
            '.rendicion-resumen-grid {',
            '    display: grid;',
            '    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));',
            '    gap: 10px;',
            '}',
            '.rendicion-resumen-item {',
            '    background: #fff;',
            '    border: 1px solid #c8daf0;',
            '    border-radius: 6px;',
            '    padding: 10px 12px;',
            '    text-align: center;',
            '}',
            '.rendicion-resumen-item span {',
            '    display: block;',
            '    font-size: 11px;',
            '    color: #4a6a9a;',
            '    text-transform: uppercase;',
            '    letter-spacing: 0.5px;',
            '    margin-bottom: 4px;',
            '}',
            '.rendicion-resumen-item b {',
            '    display: block;',
            '    font-size: 18px;',
            '    color: #1a3a68;',
            '    font-weight: 700;',
            '}',
            '',
            '.rendicion-seccion {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 8px;',
            '}',
            '.rendicion-seccion-titulo {',
            '    font-size: 12px;',
            '    font-weight: 700;',
            '    color: var(--primary-dark);',
            '    text-transform: uppercase;',
            '    letter-spacing: 0.5px;',
            '    padding-bottom: 6px;',
            '    border-bottom: 1px solid var(--border);',
            '}',
            '',
            '.rendicion-tabla {',
            '    width: 100%;',
            '    border-collapse: collapse;',
            '    font-size: 13px;',
            '}',
            '.rendicion-tabla th, .rendicion-tabla td {',
            '    padding: 8px 10px;',
            '    border-bottom: 1px solid var(--border);',
            '    text-align: left;',
            '}',
            '.rendicion-tabla th {',
            '    background: #f9e08a;',
            '    font-size: 12px;',
            '    color: var(--primary-dark);',
            '}',
            '.rendicion-tabla td.num {',
            '    text-align: right;',
            '    font-variant-numeric: tabular-nums;',
            '}',
            '',
            '.rendicion-ventas-lista {',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 6px;',
            '    max-height: 320px;',
            '    overflow-y: auto;',
            '    padding-right: 4px;',
            '}',
            '',
            '.rendicion-venta-item {',
            '    display: flex;',
            '    align-items: center;',
            '    gap: 12px;',
            '    padding: 8px 12px;',
            '    border: 1px solid var(--border);',
            '    border-radius: 6px;',
            '    background: #fff;',
            '    cursor: pointer;',
            '    font-size: 13px;',
            '    transition: background 0.15s;',
            '}',
            '.rendicion-venta-item:hover {',
            '    background: var(--panel-claro);',
            '}',
            '.rendicion-venta-check {',
            '    flex-shrink: 0;',
            '    width: 18px;',
            '    height: 18px;',
            '    cursor: pointer;',
            '}',
            '.rendicion-venta-info {',
            '    flex: 1;',
            '    display: flex;',
            '    flex-direction: column;',
            '    gap: 2px;',
            '    min-width: 0;',
            '}',
            '.rendicion-venta-id {',
            '    font-weight: 700;',
            '    color: var(--primary-dark);',
            '    font-size: 13px;',
            '}',
            '.rendicion-venta-detalle {',
            '    font-size: 12px;',
            '    color: var(--muted);',
            '    word-break: break-word;',
            '}',
            '.rendicion-venta-monto {',
            '    font-weight: 700;',
            '    color: var(--primary-dark);',
            '    white-space: nowrap;',
            '    font-size: 14px;',
            '}',
            '',
            '.rendicion-acciones {',
            '    display: flex;',
            '    justify-content: space-between;',
            '    align-items: center;',
            '    gap: 10px;',
            '    flex-wrap: wrap;',
            '    margin-top: 4px;',
            '}',
            '',
            '.rendicion-sin-datos {',
            '    border: 1px solid var(--border);',
            '    background: var(--panel-claro);',
            '    border-radius: 6px;',
            '    padding: 20px;',
            '    text-align: center;',
            '    color: var(--muted);',
            '    font-size: 13px;',
            '}',
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