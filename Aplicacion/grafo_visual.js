/**
 * Vista visual del grafo (SVG, sin librerías).
 *
 * Fase 1: sub-pestañas, grilla, pan, zoom, nodos.
 * Fase 2: flechas curvas Bézier y colores por tipo.
 *
 * Fase 3 agregará el panel lateral y el buscador.
 * Fase 4 agregará el force-directed.
 *
 * Se carga después de `grafo.js`. No hay imports:
 * se apoya en helpers globales (`usuario_actual`)
 * definidos en `aplicacion.js`.
 *
 * @version 1.5piloto.80o
 * @since 1.5piloto.80n
 */

let _grafo_visual_inicializado = false;
let _grafo_visual_datos = null;
let _grafo_visual_vb = null;

const GRAFO_VISUAL_NODO_W = 140;
const GRAFO_VISUAL_NODO_H = 44;

/**
 * Carga el vecindario desde el backend y renderiza el SVG.
 * Se llama la primera vez que se activa la sub-pestaña Visual.
 */
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
    _renderizar_grafo_visual();
}

/**
 * Conecta los listeners de las sub-pestañas Tabla / Visual.
 * Idempotente.
 */
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

/**
 * Convierte un tipo legible ("Pasajero", "Contenedor raíz: usuarios")
 * en una clase CSS ("tipo-pasajero", "tipo-contenedor-raiz-usuarios").
 */
function _grafo_visual_clase_tipo(tipo) {
    const s = String(tipo || '?').toLowerCase();
    const normalizado = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return 'tipo-' + (normalizado || 'desconocido');
}

/**
 * Devuelve el punto donde el rayo desde el centro de un rect
 * en dirección (dx, dy) cruza el borde del rect.
 */
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

/**
 * Renderiza el SVG con el vecindario actual.
 */
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

    // BFS desde las raíces; lo no alcanzable va al final.
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

    // Posiciones en grilla. Centro del rect guardado aparte.
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

    const svg_ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svg_ns, 'svg');
    svg.setAttribute('class', 'grafo-visual-svg');
    svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    // Defs: pattern de grilla + marker de flecha.
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

    // Fondo con grilla.
    const fondo = document.createElementNS(svg_ns, 'rect');
    fondo.setAttribute('x', vb.x);
    fondo.setAttribute('y', vb.y);
    fondo.setAttribute('width', vb.w);
    fondo.setAttribute('height', vb.h);
    fondo.setAttribute('fill', 'url(#grafo-grid)');
    svg.appendChild(fondo);

    // Grupo de enlaces (antes que los nodos, para que queden detrás).
    const g_enlaces = document.createElementNS(svg_ns, 'g');
    g_enlaces.setAttribute('class', 'grafo-enlaces');

    datos.enlaces.forEach(e => {
        const co = centros[e.origen];
        const cd = centros[e.destino];
        if (!co || !cd) return;
        const dx = cd.x - co.x;
        const dy = cd.y - co.y;
        if (dx === 0 && dy === 0) return;

        // Punto de salida: borde del rect origen en dirección al destino.
        const p_salida = _grafo_visual_punto_borde(co.x, co.y, dx, dy, NODO_W, NODO_H);
        // Punto de entrada: borde del rect destino en dirección opuesta.
        const p_entrada = _grafo_visual_punto_borde(cd.x, cd.y, -dx, -dy, NODO_W, NODO_H);

        // Punto de control Bézier: desplazado perpendicularmente.
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

    // Grupo de nodos.
    const g_nodos = document.createElementNS(svg_ns, 'g');
    g_nodos.setAttribute('class', 'grafo-nodos');

    datos.nodos.forEach(n => {
        const p = posiciones[n.id];
        if (!p) return;
        const g = document.createElementNS(svg_ns, 'g');
        const clase_tipo = _grafo_visual_clase_tipo(n.tipo);
        g.setAttribute('class',
            'grafo-nodo ' + clase_tipo + (n.es_especial ? ' especial' : ''));
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

        g_nodos.appendChild(g);
    });

    svg.appendChild(g_nodos);
    cont.innerHTML = '';

    const wrapper = document.createElement('div');
    wrapper.className = 'grafo-visual-wrapper';

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

    wrapper.appendChild(svg);
    wrapper.appendChild(info);
    cont.appendChild(wrapper);

    _conectar_pan_zoom(svg);
}

/**
 * Conecta pan (arrastrar fondo) y zoom (rueda).
 * @param {SVGElement} svg
 */
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

function _grafo_visual_escape(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Conectar las sub-pestañas al cargar el DOM. Idempotente.
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