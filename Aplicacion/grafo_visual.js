/**
 * Vista visual del grafo (SVG, sin librerías).
 *
 * Fase 1: sub-pestañas, grilla, pan, zoom, nodos.
 * Fase 2: flechas curvas Bézier y colores por tipo.
 * Fase 3: panel lateral, doble click para expandir,
 *         buscador, reset zoom.
 *
 * Fase 4 agregará el force-directed.
 *
 * Se carga después de `grafo.js`. No hay imports:
 * se apoya en helpers globales (`usuario_actual`,
 * `mostrar_aviso`) definidos en `aplicacion.js`.
 *
 * @version 1.5piloto.80p
 * @since 1.5piloto.80n
 */

let _grafo_visual_inicializado = false;
let _grafo_visual_datos = null;
let _grafo_visual_vb = null;
let _grafo_visual_seleccionado = null;
let _grafo_visual_matches = [];
let _grafo_visual_match_actual = -1;

const GRAFO_VISUAL_NODO_W = 140;
const GRAFO_VISUAL_NODO_H = 44;
const GRAFO_VISUAL_MAX_NODOS = 1000;

// ============================================================
// API principal
// ============================================================

async function cargar_grafo_visual() {
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;

    cont.innerHTML = '<div class="grafo-visual-aviso">Cargando...</div>';

    let resp;
    try {
        resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "grafo/vecindario",
                nombre_solicitante: usuario_actual.nombre_usuario,
                contextos: JSON.stringify(['*']),
                niveles: "1",
                max_nodos: "500"
            })
        });
    } catch (e) {
        cont.innerHTML = '<div class="grafo-visual-aviso">Error de comunicación</div>';
        return;
    }

    const datos = await resp.json();
    if (!datos.exito) {
        cont.innerHTML = '<div class="grafo-visual-aviso">Error: '
            + _grafo_visual_escape(datos.error || '') + '</div>';
        return;
    }

    _grafo_visual_datos = datos.vecindario;
    _grafo_visual_seleccionado = null;
    _grafo_visual_matches = [];
    _grafo_visual_match_actual = -1;
    _renderizar_grafo_visual();
}

function _conectar_subtabs_grafo() {
    const subtabs = document.querySelectorAll('#grafo_subtabs .grafo-subtab');
    if (!subtabs || subtabs.length === 0) return;

    subtabs.forEach(btn => {
        btn.addEventListener('click', () => {
            subtabs.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const vista = btn.dataset.vista;
            const v_tabla = document.getElementById('grafo_vista_tabla');
            const v_visual = document.getElementById('grafo_vista_visual');
            if (!v_tabla || !v_visual) return;
            if (vista === 'tabla') {
                v_tabla.classList.remove('hidden');
                v_visual.classList.add('hidden');
            } else {
                v_tabla.classList.add('hidden');
                v_visual.classList.remove('hidden');
                if (!_grafo_visual_datos) cargar_grafo_visual();
            }
        });
    });
}

// ============================================================
// Utilidades de layout
// ============================================================

function _grafo_visual_clase_tipo(tipo) {
    const s = String(tipo || '?').toLowerCase();
    const normalizado = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return 'tipo-' + (normalizado || 'desconocido');
}

function _grafo_visual_punto_borde(cx, cy, dx, dy, w, h) {
    const hw = w / 2;
    const hh = h / 2;
    if (dx === 0 && dy === 0) return { x: cx, y: cy };
    const adx = Math.abs(dx);
    const ady = Math.abs(dy);
    const tx = adx > 0 ? hw / adx : Infinity;
    const ty = ady > 0 ? hh / ady : Infinity;
    const t = Math.min(tx, ty);
    return { x: cx + dx * t, y: cy + dy * t };
}

function _grafo_visual_escape(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ============================================================
// Render principal
// ============================================================

function _renderizar_grafo_visual() {
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    const datos = _grafo_visual_datos;
    if (!datos || !datos.nodos || datos.nodos.length === 0) {
        cont.innerHTML = '<div class="grafo-visual-aviso">No hay nodos para mostrar.</div>';
        return;
    }

    const NODO_W = GRAFO_VISUAL_NODO_W;
    const NODO_H = GRAFO_VISUAL_NODO_H;
    const GAP_X = 60;
    const GAP_Y = 60;
    const COLUMNAS = Math.max(4, Math.ceil(Math.sqrt(datos.nodos.length * 1.5)));

    const indice = {};
    datos.nodos.forEach(n => { indice[n.id] = n; });

    const ady_por_origen = {};
    datos.enlaces.forEach(e => {
        if (!ady_por_origen[e.origen]) ady_por_origen[e.origen] = [];
        ady_por_origen[e.origen].push(e.destino);
    });

    const visitados_bfs = {};
    const cola_bfs = [];
    (datos.raices || []).forEach(id => {
        if (indice[id] && !visitados_bfs[id]) { visitados_bfs[id] = true; cola_bfs.push(id); }
    });
    datos.nodos.forEach(n => {
        if (!visitados_bfs[n.id]) { visitados_bfs[n.id] = true; cola_bfs.push(n.id); }
    });

    const ordenados = [];
    while (cola_bfs.length > 0) {
        const id = cola_bfs.shift();
        ordenados.push(id);
        const vecinos = ady_por_origen[id] || [];
        for (const v of vecinos) {
            if (indice[v] && !visitados_bfs[v]) {
                visitados_bfs[v] = true;
                cola_bfs.push(v);
            }
        }
    }

    const posiciones = {};
    const centros = {};
    ordenados.forEach((id, i) => {
        const col = i % COLUMNAS;
        const fila = Math.floor(i / COLUMNAS);
        const x = col * (NODO_W + GAP_X);
        const y = fila * (NODO_H + GAP_Y);
        posiciones[id] = { x, y };
        centros[id] = { x: x + NODO_W / 2, y: y + NODO_H / 2 };
    });

    let min_x = Infinity, min_y = Infinity, max_x = -Infinity, max_y = -Infinity;
    Object.values(posiciones).forEach(p => {
        if (p.x < min_x) min_x = p.x;
        if (p.y < min_y) min_y = p.y;
        if (p.x + NODO_W > max_x) max_x = p.x + NODO_W;
        if (p.y + NODO_H > max_y) max_y = p.y + NODO_H;
    });
    const padding = 40;
    const vb = {
        x: min_x - padding,
        y: min_y - padding,
        w: (max_x - min_x) + 2 * padding,
        h: (max_y - min_y) + 2 * padding
    };
    _grafo_visual_vb = { x: vb.x, y: vb.y, w: vb.w, h: vb.h };

    // Estructura del contenedor: se crea una sola vez, y en
    // los re-renders solo se reemplaza el SVG y se actualiza
    // el panel y las clases.
    let wrapper = document.getElementById('grafo_visual_wrapper');
    let panel = document.getElementById('grafo_visual_panel');
    if (!wrapper || !panel) {
        cont.innerHTML = '';
        const controles = document.createElement('div');
        controles.className = 'grafo-visual-controles';
        controles.innerHTML =
            '<input type="text" id="grafo_visual_buscar" placeholder="Buscar por ID o dato...">' +
            '<button type="button" class="btn" id="grafo_visual_btn_reset">Reset zoom</button>' +
            '<span class="grafo-visual-buscar-info" id="grafo_visual_buscar_info"></span>';
        cont.appendChild(controles);

        const layout = document.createElement('div');
        layout.className = 'grafo-visual-layout';

        wrapper = document.createElement('div');
        wrapper.className = 'grafo-visual-wrapper';
        wrapper.id = 'grafo_visual_wrapper';

        panel = document.createElement('aside');
        panel.className = 'grafo-visual-panel';
        panel.id = 'grafo_visual_panel';
        panel.style.display = 'none';

        layout.appendChild(wrapper);
        layout.appendChild(panel);
        cont.appendChild(layout);

        _conectar_controles_grafo_visual();
    }

    // SVG (se recrea).
    wrapper.innerHTML = '';
    const svg_ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svg_ns, 'svg');
    svg.setAttribute('class', 'grafo-visual-svg');
    svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    const defs = document.createElementNS(svg_ns, 'defs');

    const pattern = document.createElementNS(svg_ns, 'pattern');
    pattern.setAttribute('id', 'grafo-grid');
    pattern.setAttribute('width', '40');
    pattern.setAttribute('height', '40');
    pattern.setAttribute('patternUnits', 'userSpaceOnUse');
    const rect_bg = document.createElementNS(svg_ns, 'rect');
    rect_bg.setAttribute('width', '40');
    rect_bg.setAttribute('height', '40');
    rect_bg.setAttribute('fill', '#fafafa');
    const path_linea = document.createElementNS(svg_ns, 'path');
    path_linea.setAttribute('d', 'M 40 0 L 0 0 0 40');
    path_linea.setAttribute('fill', 'none');
    path_linea.setAttribute('stroke', '#e8e8e8');
    path_linea.setAttribute('stroke-width', '1');
    pattern.appendChild(rect_bg);
    pattern.appendChild(path_linea);
    defs.appendChild(pattern);

    const marker = document.createElementNS(svg_ns, 'marker');
    marker.setAttribute('id', 'grafo-flecha');
    marker.setAttribute('viewBox', '0 0 10 10');
    marker.setAttribute('refX', '9');
    marker.setAttribute('refY', '5');
    marker.setAttribute('markerWidth', '6');
    marker.setAttribute('markerHeight', '6');
    marker.setAttribute('orient', 'auto-start-reverse');
    const marker_path = document.createElementNS(svg_ns, 'path');
    marker_path.setAttribute('d', 'M 0 0 L 10 5 L 0 10 z');
    marker_path.setAttribute('fill', '#888');
    marker.appendChild(marker_path);
    defs.appendChild(marker);

    svg.appendChild(defs);

    const fondo = document.createElementNS(svg_ns, 'rect');
    fondo.setAttribute('x', vb.x);
    fondo.setAttribute('y', vb.y);
    fondo.setAttribute('width', vb.w);
    fondo.setAttribute('height', vb.h);
    fondo.setAttribute('fill', 'url(#grafo-grid)');
    svg.appendChild(fondo);

    const g_enlaces = document.createElementNS(svg_ns, 'g');
    g_enlaces.setAttribute('class', 'grafo-enlaces');

    datos.enlaces.forEach(e => {
        const co = centros[e.origen];
        const cd = centros[e.destino];
        if (!co || !cd) return;
        const dx = cd.x - co.x;
        const dy = cd.y - co.y;
        if (dx === 0 && dy === 0) return;

        const p_salida = _grafo_visual_punto_borde(co.x, co.y, dx, dy, NODO_W, NODO_H);
        const p_entrada = _grafo_visual_punto_borde(cd.x, cd.y, -dx, -dy, NODO_W, NODO_H);

        const mx = (p_salida.x + p_entrada.x) / 2;
        const my = (p_salida.y + p_entrada.y) / 2;
        const dist = Math.sqrt(dx * dx + dy * dy) || 1;
        const offset = Math.min(dist * 0.15, 40);
        const nx = -dy / dist;
        const ny = dx / dist;
        const cx_control = mx + nx * offset;
        const cy_control = my + ny * offset;

        const path = document.createElementNS(svg_ns, 'path');
        path.setAttribute('d',
            `M ${p_salida.x} ${p_salida.y} Q ${cx_control} ${cy_control} ${p_entrada.x} ${p_entrada.y}`);
        path.setAttribute('class', 'grafo-enlace' + (e.destino_es_compartido ? ' compartido' : ''));
        path.setAttribute('marker-end', 'url(#grafo-flecha)');
        path.dataset.origen = e.origen;
        path.dataset.destino = e.destino;
        path.dataset.enlace = e.enlace;
        g_enlaces.appendChild(path);
    });

    svg.appendChild(g_enlaces);

    const g_nodos = document.createElementNS(svg_ns, 'g');
    g_nodos.setAttribute('class', 'grafo-nodos');

    datos.nodos.forEach(n => {
        const p = posiciones[n.id];
        if (!p) return;
        const g = document.createElementNS(svg_ns, 'g');
        const clase_tipo = _grafo_visual_clase_tipo(n.tipo);
        const clase_sel = (_grafo_visual_seleccionado === n.id) ? ' seleccionado' : '';
        g.setAttribute('class',
            'grafo-nodo ' + clase_tipo + (n.es_especial ? ' especial' : '') + clase_sel);
        g.dataset.idNodo = n.id;

        const rect = document.createElementNS(svg_ns, 'rect');
        rect.setAttribute('x', p.x);
        rect.setAttribute('y', p.y);
        rect.setAttribute('width', NODO_W);
        rect.setAttribute('height', NODO_H);
        rect.setAttribute('rx', '8');
        rect.setAttribute('ry', '8');
        g.appendChild(rect);

        const texto_tipo = document.createElementNS(svg_ns, 'text');
        texto_tipo.setAttribute('x', p.x + 8);
        texto_tipo.setAttribute('y', p.y + 16);
        texto_tipo.setAttribute('font-weight', '600');
        texto_tipo.textContent = n.tipo || '?';
        g.appendChild(texto_tipo);

        const texto_id = document.createElementNS(svg_ns, 'text');
        texto_id.setAttribute('x', p.x + 8);
        texto_id.setAttribute('y', p.y + 32);
        texto_id.setAttribute('fill', '#888');
        texto_id.textContent = n.es_especial ? n.id : ('#' + n.id);
        g.appendChild(texto_id);

        g.addEventListener('click', (ev) => {
            ev.stopPropagation();
            _grafo_visual_seleccionar(n.id);
        });
        g.addEventListener('dblclick', (ev) => {
            ev.stopPropagation();
            _grafo_visual_expandir(n.id);
        });

        g_nodos.appendChild(g);
    });

    svg.appendChild(g_nodos);
    wrapper.appendChild(svg);

    // Info flotante.
    const info = document.createElement('div');
    info.className = 'grafo-visual-info';
    info.textContent = datos.nodos.length + ' nodos · '
        + datos.enlaces.length + ' enlaces · total del grafo: '
        + datos.total;
    if (datos.truncado) {
        const aviso = document.createElement('span');
        aviso.className = 'truncado';
        aviso.textContent = ' (truncado a ' + datos.max_nodos + ')';
        info.appendChild(aviso);
    }
    wrapper.appendChild(info);

    _conectar_pan_zoom(svg);

    // Si hay búsqueda activa, reaplicar.
    const input_buscar = document.getElementById('grafo_visual_buscar');
    if (input_buscar && input_buscar.value.trim() !== '') {
        _grafo_visual_aplicar_busqueda(input_buscar.value);
    }
}

// ============================================================
// Pan y zoom
// ============================================================

function _conectar_pan_zoom(svg) {
    svg.addEventListener('wheel', (e) => {
        e.preventDefault();
        const vb = _grafo_visual_vb;
        if (!vb) return;
        const factor = (e.deltaY < 0) ? 0.9 : 1.1;
        const rect = svg.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width;
        const py = (e.clientY - rect.top) / rect.height;
        const vx = vb.x + px * vb.w;
        const vy = vb.y + py * vb.h;
        const nw = vb.w * factor;
        const nh = vb.h * factor;
        vb.x = vx - px * nw;
        vb.y = vy - py * nh;
        vb.w = nw;
        vb.h = nh;
        svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
    }, { passive: false });

    let arrastrando = false;
    let start_x = 0, start_y = 0;
    let vb_inicial = null;

    svg.addEventListener('mousedown', (e) => {
        if (e.target.closest && e.target.closest('.grafo-nodo')) return;
        arrastrando = true;
        svg.classList.add('arrastrando');
        start_x = e.clientX;
        start_y = e.clientY;
        vb_inicial = { x: _grafo_visual_vb.x, y: _grafo_visual_vb.y,
                        w: _grafo_visual_vb.w, h: _grafo_visual_vb.h };
    });

    window.addEventListener('mousemove', (e) => {
        if (!arrastrando) return;
        const dx = e.clientX - start_x;
        const dy = e.clientY - start_y;
        const rect = svg.getBoundingClientRect();
        const escala_x = vb_inicial.w / rect.width;
        const escala_y = vb_inicial.h / rect.height;
        const vb = _grafo_visual_vb;
        vb.x = vb_inicial.x - dx * escala_x;
        vb.y = vb_inicial.y - dy * escala_y;
        svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
    });

    window.addEventListener('mouseup', () => {
        if (arrastrando) {
            arrastrando = false;
            svg.classList.remove('arrastrando');
        }
    });
}

// ============================================================
// Selección y panel lateral
// ============================================================

async function _grafo_visual_seleccionar(id) {
    if (!id) return;
    _grafo_visual_seleccionado = id;

    // Marcar el nodo seleccionado visualmente.
    document.querySelectorAll('.grafo-nodo.seleccionado').forEach(g => {
        g.classList.remove('seleccionado');
    });
    const g_nodo = document.querySelector('.grafo-nodo[data-id-nodo="' + CSS.escape(id) + '"]');
    if (g_nodo) g_nodo.classList.add('seleccionado');

    // Cargar detalle y pintar el panel.
    const panel = document.getElementById('grafo_visual_panel');
    if (!panel) return;
    panel.style.display = 'block';
    panel.innerHTML = '<div class="grafo-panel-cargando">Cargando...</div>';

    let detalle;
    try {
        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "grafo/nodo",
                nombre_solicitante: usuario_actual.nombre_usuario,
                id: id
            })
        });
        const datos = await resp.json();
        if (!datos.exito) {
            panel.innerHTML = '<div class="grafo-panel-error">Error: '
                + _grafo_visual_escape(datos.error || '') + '</div>';
            return;
        }
        detalle = datos.nodo;
    } catch (e) {
        panel.innerHTML = '<div class="grafo-panel-error">Error de comunicación</div>';
        return;
    }

    _grafo_visual_renderizar_panel(detalle);
}

function _grafo_visual_renderizar_panel(n) {
    const panel = document.getElementById('grafo_visual_panel');
    if (!panel) return;

    const tipo_nodo = n.tipo || '?';
    const clase_tipo = _grafo_visual_clase_tipo(tipo_nodo);

    const ady_items = (n.adyacentes || []).map(a => {
        const tipo_d = a.tipo_destino || '?';
        const dato = a.dato_destino ? '<span class="muted"> (' + _grafo_visual_escape(a.dato_destino) + ')</span>' : '';
        const compartido = a.destino_es_compartido ? ' <span class="tag-compartido">compartido</span>' : '';
        return '<li>' +
            '<code>' + _grafo_visual_escape(a.enlace) + '</code> → ' +
            '<a href="#" data-ir-nodo="' + _grafo_visual_escape(a.id_destino) + '">' +
            '<span class="tipo-mini ' + _grafo_visual_clase_tipo(tipo_d) + '">' + _grafo_visual_escape(tipo_d) + '</span> ' +
            _grafo_visual_escape(a.id_destino) + '</a>' + dato + compartido +
            '</li>';
    }).join('');

    const ref_items = (n.referencias || []).map(r => {
        const tipo_o = r.tipo_origen || '?';
        const dato = r.dato_origen ? '<span class="muted"> (' + _grafo_visual_escape(r.dato_origen) + ')</span>' : '';
        return '<li>' +
            '<a href="#" data-ir-nodo="' + _grafo_visual_escape(r.id_origen) + '">' +
            '<span class="tipo-mini ' + _grafo_visual_clase_tipo(tipo_o) + '">' + _grafo_visual_escape(tipo_o) + '</span> ' +
            _grafo_visual_escape(r.id_origen) + '</a>' + dato + ' → ' +
            '<code>' + _grafo_visual_escape(r.enlace) + '</code>' +
            '</li>';
    }).join('');

    const html =
        '<header class="grafo-panel-header">' +
        '  <div>' +
        '    <span class="grafo-panel-tipo ' + clase_tipo + '">' + _grafo_visual_escape(tipo_nodo) + '</span>' +
        '    <h3>' + _grafo_visual_escape(n.id) + '</h3>' +
        '  </div>' +
        '  <button type="button" class="btn grafo-panel-cerrar" id="grafo_visual_panel_cerrar">✕</button>' +
        '</header>' +
        '<div class="grafo-panel-cuerpo">' +
        '  <div class="grafo-panel-campo"><label>ID</label><code>' + _grafo_visual_escape(n.id) + '</code></div>' +
        '  <div class="grafo-panel-campo"><label>Dato</label><span>' + _grafo_visual_escape(n.dato || '(vacío)') + '</span></div>' +
        '  <div class="grafo-panel-campo"><label>Especial</label><span>' + (n.es_especial ? 'Sí' : 'No') + '</span></div>' +
        '</div>' +
        '<div class="grafo-panel-seccion">' +
        '  <h4>Salientes (' + (n.adyacentes || []).length + ')</h4>' +
        '  <ul>' + (ady_items || '<li class="muted">Sin enlaces salientes.</li>') + '</ul>' +
        '</div>' +
        '<div class="grafo-panel-seccion">' +
        '  <h4>Entrantes (' + (n.referencias || []).length + ')</h4>' +
        '  <ul>' + (ref_items || '<li class="muted">Sin referencias entrantes.</li>') + '</ul>' +
        '</div>';

    panel.innerHTML = html;

    panel.querySelector('#grafo_visual_panel_cerrar').addEventListener('click', () => {
        panel.style.display = 'none';
        _grafo_visual_seleccionado = null;
        document.querySelectorAll('.grafo-nodo.seleccionado').forEach(g => {
            g.classList.remove('seleccionado');
        });
    });

    panel.querySelectorAll('a[data-ir-nodo]').forEach(a => {
        a.addEventListener('click', (ev) => {
            ev.preventDefault();
            const destino = a.dataset.irNodo;
            _grafo_visual_seleccionar(destino);
            _grafo_visual_centrar_en(destino);
        });
    });
}

function _grafo_visual_centrar_en(id) {
    const datos = _grafo_visual_datos;
    if (!datos || !_grafo_visual_vb) return;
    const nodo = (datos.nodos || []).find(n => n.id === id);
    if (!nodo) return;

    // Buscar su posición en la última renderización.
    const g = document.querySelector('.grafo-nodo[data-id-nodo="' + CSS.escape(id) + '"]');
    if (!g) return;
    const rect = g.querySelector('rect');
    if (!rect) return;
    const x = parseFloat(rect.getAttribute('x'));
    const y = parseFloat(rect.getAttribute('y'));
    const cx = x + GRAFO_VISUAL_NODO_W / 2;
    const cy = y + GRAFO_VISUAL_NODO_H / 2;

    const vb = _grafo_visual_vb;
    const nw = vb.w;
    const nh = vb.h;
    vb.x = cx - nw / 2;
    vb.y = cy - nh / 2;
    const svg = document.querySelector('.grafo-visual-svg');
    if (svg) svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
}

// ============================================================
// Expansión de vecinos (doble click)
// ============================================================

async function _grafo_visual_expandir(id) {
    if (!id) return;
    const datos = _grafo_visual_datos;
    if (!datos) return;

    if (datos.nodos.length >= GRAFO_VISUAL_MAX_NODOS) {
        mostrar_aviso('Límite de ' + GRAFO_VISUAL_MAX_NODOS + ' nodos alcanzado', 'error');
        return;
    }

    let detalle;
    try {
        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "grafo/nodo",
                nombre_solicitante: usuario_actual.nombre_usuario,
                id: id
            })
        });
        const d = await resp.json();
        if (!d.exito) return;
        detalle = d.nodo;
    } catch (e) { return; }

    const ids_actuales = {};
    datos.nodos.forEach(n => { ids_actuales[n.id] = true; });

    const enlaces_actuales = {};
    datos.enlaces.forEach(e => {
        enlaces_actuales[e.origen + '|' + e.enlace + '|' + e.destino] = true;
    });

    let agregados = 0;

    (detalle.adyacentes || []).forEach(a => {
        if (!ids_actuales[a.id_destino]) {
            datos.nodos.push({
                id: a.id_destino,
                dato: a.dato_destino || '',
                es_especial: !/^\d+$/.test(a.id_destino),
                es_raiz: false,
                tipo: a.tipo_destino || '?',
                n_adyacentes: 0,
                n_referencias: 0
            });
            ids_actuales[a.id_destino] = true;
            agregados++;
        }
        const clave = id + '|' + a.enlace + '|' + a.id_destino;
        if (!enlaces_actuales[clave]) {
            datos.enlaces.push({
                origen: id,
                destino: a.id_destino,
                enlace: a.enlace,
                destino_es_compartido: a.destino_es_compartido === true
            });
            enlaces_actuales[clave] = true;
        }
    });

    // También agrego los orígenes de las referencias entrantes como nodos.
    (detalle.referencias || []).forEach(r => {
        if (!ids_actuales[r.id_origen]) {
            datos.nodos.push({
                id: r.id_origen,
                dato: r.dato_origen || '',
                es_especial: !/^\d+$/.test(r.id_origen),
                es_raiz: false,
                tipo: r.tipo_origen || '?',
                n_adyacentes: 0,
                n_referencias: 0
            });
            ids_actuales[r.id_origen] = true;
            agregados++;
        }
        const clave = r.id_origen + '|' + r.enlace + '|' + id;
        if (!enlaces_actuales[clave]) {
            datos.enlaces.push({
                origen: r.id_origen,
                destino: id,
                enlace: r.enlace,
                destino_es_compartido: false
            });
            enlaces_actuales[clave] = true;
        }
    });

    if (agregados > 0) {
        mostrar_aviso('Agregados ' + agregados + ' nodo(s)', 'exito');
        _renderizar_grafo_visual();
    } else {
        mostrar_aviso('Sin vecinos nuevos', 'info');
    }
}

// ============================================================
// Buscador
// ============================================================

function _grafo_visual_aplicar_busqueda(texto) {
    const q = String(texto || '').trim().toLowerCase();
    const datos = _grafo_visual_datos;
    const info = document.getElementById('grafo_visual_buscar_info');

    document.querySelectorAll('.grafo-nodo.atenuado').forEach(g => g.classList.remove('atenuado'));
    document.querySelectorAll('.grafo-enlace.atenuado').forEach(p => p.classList.remove('atenuado'));

    if (!datos || q === '') {
        _grafo_visual_matches = [];
        _grafo_visual_match_actual = -1;
        if (info) info.textContent = '';
        return;
    }

    const matches = [];
    const ids_match = {};
    datos.nodos.forEach(n => {
        const en_id = n.id.toLowerCase().indexOf(q) !== -1;
        const en_dato = (n.dato || '').toLowerCase().indexOf(q) !== -1;
        if (en_id || en_dato) {
            matches.push(n.id);
            ids_match[n.id] = true;
        }
    });

    datos.nodos.forEach(n => {
        if (!ids_match[n.id]) {
            const g = document.querySelector('.grafo-nodo[data-id-nodo="' + CSS.escape(n.id) + '"]');
            if (g) g.classList.add('atenuado');
        }
    });

    // Atenuar enlaces que no toquen un match (opcional).
    datos.enlaces.forEach(e => {
        if (!ids_match[e.origen] && !ids_match[e.destino]) {
            document.querySelectorAll('.grafo-enlace').forEach(p => {
                if (p.dataset.origen === e.origen && p.dataset.destino === e.destino
                    && p.dataset.enlace === e.enlace) {
                    p.classList.add('atenuado');
                }
            });
        }
    });

    _grafo_visual_matches = matches;
    _grafo_visual_match_actual = matches.length > 0 ? 0 : -1;

    if (info) {
        info.textContent = matches.length > 0
            ? (matches.length + ' coincidencia(s)')
            : 'Sin coincidencias';
    }

    if (matches.length > 0) {
        _grafo_visual_centrar_en(matches[0]);
    }
}

function _grafo_visual_ir_a_match(delta) {
    if (_grafo_visual_matches.length === 0) return;
    const n = _grafo_visual_matches.length;
    _grafo_visual_match_actual = (_grafo_visual_match_actual + delta + n) % n;
    const id = _grafo_visual_matches[_grafo_visual_match_actual];
    _grafo_visual_centrar_en(id);
    const info = document.getElementById('grafo_visual_buscar_info');
    if (info) info.textContent = (_grafo_visual_match_actual + 1) + '/' + n;
}

// ============================================================
// Controles (buscador, reset)
// ============================================================

function _conectar_controles_grafo_visual() {
    const input = document.getElementById('grafo_visual_buscar');
    if (input) {
        let timeout = null;
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                _grafo_visual_aplicar_busqueda(input.value);
            }, 150);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                _grafo_visual_ir_a_match(e.shiftKey ? -1 : 1);
            }
        });
    }

    const btn_reset = document.getElementById('grafo_visual_btn_reset');
    if (btn_reset) {
        btn_reset.addEventListener('click', () => {
            _renderizar_grafo_visual();
        });
    }
}

// ============================================================
// Init
// ============================================================

(function _init_grafo_visual() {
    function _setup() {
        if (_grafo_visual_inicializado) return;
        _grafo_visual_inicializado = true;
        _conectar_subtabs_grafo();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _setup);
    } else {
        _setup();
    }
})();