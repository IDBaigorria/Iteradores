/**
 * Vista visual del grafo (SVG, sin librerías).
 *
 * Fases implementadas:
 *   1. Sub-pestañas, grilla, pan, zoom, nodos.
 *   2. Flechas Bézier y colores por tipo.
 *   3. Panel lateral, expansión, buscador, reset.
 *   4. Force-directed con toggle.
 *   5. Nodos cuadrados, force más separado, selector
 *      de max_nodos, compartidos, refresco tras
 *      eliminar huérfanos, etiquetas de enlace,
 *      colores de enlace por origen, zoom preservado,
 *      centrar tras expandir, drag con física,
 *      click en enlace con pan animado.
 *
 * Se carga después de `grafo.js`. No hay imports:
 * se apoya en helpers globales (`usuario_actual`,
 * `mostrar_aviso`) definidos en `aplicacion.js`.
 *
 * @version 1.5piloto.81f
 * @since 1.5piloto.80n
 */

let _grafo_visual_inicializado = false;
let _grafo_visual_datos = null;
let _grafo_visual_vb = null;
let _grafo_visual_seleccionado = null;
let _grafo_visual_matches = [];
let _grafo_visual_match_actual = -1;
let _grafo_visual_layout = 'force'; // 'grilla' | 'force'
let _grafo_visual_max_nodos = 500;
let _grafo_visual_mostrar_etiquetas = true;

// Fuente de verdad de las posiciones después del primer
// cálculo o de un drag.
let _grafo_visual_posiciones = {};

// Referencias al DOM para actualizar rápido durante el drag.
let _grafo_visual_refs_nodos = {};
let _grafo_visual_refs_enlaces = [];
let _grafo_visual_svg = null;

// Estado del drag de nodos.
let _grafo_visual_drag = null;

// Rect de la grilla de fondo y observer para redimensionarlo
// cuando cambia el viewBox (pan/zoom/centrar/animaciones).
let _grafo_visual_grid_rect = null;
let _grafo_visual_grid_observer = null;

const GRAFO_VISUAL_NODO_W = 120;
const GRAFO_VISUAL_NODO_H = 64;
const GRAFO_VISUAL_MAX_NODOS = 2000;

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
                max_nodos: String(_grafo_visual_max_nodos)
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
    _grafo_visual_posiciones = {};
    _grafo_visual_recalcular_tipos(_grafo_visual_datos);
    _renderizar_grafo_visual(null, { recalcular: true });
}

/**
 * Devuelve true si el visualizador ya tiene datos cargados.
 * Lo usa grafo.js para decidir si hay que disparar la
 * carga al entrar a la pestaña Grafo (la sub-pestaña
 * Visual es la activa por defecto).
 */
function grafo_visual_esta_cargado() {
    return _grafo_visual_datos !== null;
}

/**
 * Recalcula los tipos de los nodos a partir de los enlaces
 * entrantes. Se aplica solo a los nodos cuyo tipo del
 * backend sea "?" o vacío.
 *
 * Reglas:
 *   - Un solo enlace entrante → ese nombre es el tipo.
 *   - Varios enlaces distintos → el más repetido.
 *   - Empate en la cantidad máxima → "?".
 */
function _grafo_visual_recalcular_tipos(datos) {
    if (!datos || !datos.nodos) return;

    // Contar enlaces entrantes por (destino, enlace).
    const entrantes_por_id = {};
    (datos.enlaces || []).forEach(e => {
        if (!entrantes_por_id[e.destino]) entrantes_por_id[e.destino] = {};
        entrantes_por_id[e.destino][e.enlace] =
            (entrantes_por_id[e.destino][e.enlace] || 0) + 1;
    });

    (datos.nodos || []).forEach(n => {
        const tipo_backend = String(n.tipo || '').trim();
        if (tipo_backend !== '' && tipo_backend !== '?') return;

        const cuenta = entrantes_por_id[n.id] || {};
        const enlaces = Object.keys(cuenta);

        if (enlaces.length === 0) {
            n.tipo = '?';
            return;
        }
        if (enlaces.length === 1) {
            n.tipo = enlaces[0];
            return;
        }

        let max = 0;
        for (const k of enlaces) {
            if (cuenta[k] > max) max = cuenta[k];
        }
        const con_max = enlaces.filter(k => cuenta[k] === max);
        n.tipo = (con_max.length === 1) ? con_max[0] : '?';
    });
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
// Utilidades
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
// Layout
// ============================================================

function _grafo_visual_orden_bfs(datos) {
    const indice = {};
    datos.nodos.forEach(n => { indice[n.id] = n; });

    const ady_por_origen = {};
    datos.enlaces.forEach(e => {
        if (!ady_por_origen[e.origen]) ady_por_origen[e.origen] = [];
        ady_por_origen[e.origen].push(e.destino);
    });

    const visitados = {};
    const cola = [];
    (datos.raices || []).forEach(id => {
        if (indice[id] && !visitados[id]) { visitados[id] = true; cola.push(id); }
    });
    datos.nodos.forEach(n => {
        if (!visitados[n.id]) { visitados[n.id] = true; cola.push(n.id); }
    });

    const ordenados = [];
    while (cola.length > 0) {
        const id = cola.shift();
        ordenados.push(id);
        const vecinos = ady_por_origen[id] || [];
        for (const v of vecinos) {
            if (indice[v] && !visitados[v]) {
                visitados[v] = true;
                cola.push(v);
            }
        }
    }
    return ordenados;
}

function _grafo_visual_layout_grilla(datos) {
    const NODO_W = GRAFO_VISUAL_NODO_W;
    const NODO_H = GRAFO_VISUAL_NODO_H;
    const GAP_X = 60;
    const GAP_Y = 60;
    const COLUMNAS = Math.max(4, Math.ceil(Math.sqrt(datos.nodos.length * 1.5)));

    const ordenados = _grafo_visual_orden_bfs(datos);
    const posiciones = {};
    ordenados.forEach((id, i) => {
        const col = i % COLUMNAS;
        const fila = Math.floor(i / COLUMNAS);
        posiciones[id] = {
            x: col * (NODO_W + GAP_X),
            y: fila * (NODO_H + GAP_Y)
        };
    });
    return posiciones;
}

function _grafo_visual_layout_force(datos) {
    const nodos = datos.nodos;
    const enlaces = datos.enlaces;
    const n = nodos.length;
    if (n === 0) return {};

    const lado = 260;
    const area = n * lado * lado;
    const k = Math.sqrt(area / n);
    const k_repulsion = k * k;
    const k_atraccion = 0.06;
    const k_centro = 0.015;
    const iteraciones = 250;
    const iteraciones_relax = 50;
    const usar_muestreo = n > 300;
    const vecinos_muestreo = 50;

    const diag_nodo = Math.sqrt(
        GRAFO_VISUAL_NODO_W * GRAFO_VISUAL_NODO_W +
        GRAFO_VISUAL_NODO_H * GRAFO_VISUAL_NODO_H
    );
    const min_dist = diag_nodo + 20;

    const pos = {};
    const radio_inicial = Math.sqrt(area) / 2;
    nodos.forEach(nd => {
        const ang = Math.random() * Math.PI * 2;
        const r = Math.random() * radio_inicial;
        pos[nd.id] = {
            x: Math.cos(ang) * r,
            y: Math.sin(ang) * r
        };
    });

    let cx = 0, cy = 0;
    nodos.forEach(nd => { cx += pos[nd.id].x; cy += pos[nd.id].y; });
    cx /= n; cy /= n;

    for (let it = 0; it < iteraciones; it++) {
        const t = 1 - it / iteraciones;
        const max_disp = 40 * t + 5;

        const desp = {};
        nodos.forEach(nd => { desp[nd.id] = { x: 0, y: 0 }; });

        if (!usar_muestreo) {
            for (let i = 0; i < n; i++) {
                const a = nodos[i].id;
                for (let j = i + 1; j < n; j++) {
                    const b = nodos[j].id;
                    const dx = pos[a].x - pos[b].x;
                    const dy = pos[a].y - pos[b].y;
                    const d2 = dx * dx + dy * dy + 0.01;
                    const d = Math.sqrt(d2);
                    const f = k_repulsion / d2;
                    const fx = (dx / d) * f;
                    const fy = (dy / d) * f;
                    desp[a].x += fx;
                    desp[a].y += fy;
                    desp[b].x -= fx;
                    desp[b].y -= fy;
                }
            }
        } else {
            for (let i = 0; i < n; i++) {
                const a = nodos[i].id;
                const inicio = (i * 7) % n;
                for (let m = 0; m < vecinos_muestreo; m++) {
                    const j = (inicio + m) % n;
                    if (j === i) continue;
                    const b = nodos[j].id;
                    const dx = pos[a].x - pos[b].x;
                    const dy = pos[a].y - pos[b].y;
                    const d2 = dx * dx + dy * dy + 0.01;
                    const d = Math.sqrt(d2);
                    const f = k_repulsion / d2;
                    desp[a].x += (dx / d) * f;
                    desp[a].y += (dy / d) * f;
                }
            }
        }

        enlaces.forEach(e => {
            if (!pos[e.origen] || !pos[e.destino]) return;
            const dx = pos[e.destino].x - pos[e.origen].x;
            const dy = pos[e.destino].y - pos[e.origen].y;
            const d = Math.sqrt(dx * dx + dy * dy) + 0.01;
            const f = d * k_atraccion;
            const fx = (dx / d) * f;
            const fy = (dy / d) * f;
            desp[e.origen].x += fx;
            desp[e.origen].y += fy;
            desp[e.destino].x -= fx;
            desp[e.destino].y -= fy;
        });

        nodos.forEach(nd => {
            desp[nd.id].x += (cx - pos[nd.id].x) * k_centro;
            desp[nd.id].y += (cy - pos[nd.id].y) * k_centro;
        });

        nodos.forEach(nd => {
            const id = nd.id;
            const vx = desp[id].x;
            const vy = desp[id].y;
            const magn = Math.sqrt(vx * vx + vy * vy) + 0.001;
            const lim = Math.min(magn, max_disp);
            pos[id].x += (vx / magn) * lim;
            pos[id].y += (vy / magn) * lim;
        });
    }

    if (n <= 800) {
        for (let it = 0; it < iteraciones_relax; it++) {
            for (let i = 0; i < n; i++) {
                const a = nodos[i].id;
                for (let j = i + 1; j < n; j++) {
                    const b = nodos[j].id;
                    const dx = pos[a].x - pos[b].x;
                    const dy = pos[a].y - pos[b].y;
                    const d = Math.sqrt(dx * dx + dy * dy) + 0.01;
                    if (d >= min_dist) continue;
                    const empuje = (min_dist - d) * 0.5;
                    const ux = dx / d;
                    const uy = dy / d;
                    pos[a].x += ux * empuje;
                    pos[a].y += uy * empuje;
                    pos[b].x -= ux * empuje;
                    pos[b].y -= uy * empuje;
                }
            }
        }
    }

    let min_x = Infinity, min_y = Infinity;
    nodos.forEach(nd => {
        const p = pos[nd.id];
        if (p.x < min_x) min_x = p.x;
        if (p.y < min_y) min_y = p.y;
    });
    nodos.forEach(nd => {
        pos[nd.id].x -= min_x;
        pos[nd.id].y -= min_y;
    });

    return pos;
}

function _grafo_visual_calcular_posiciones(datos) {
    if (_grafo_visual_layout === 'force') {
        return _grafo_visual_layout_force(datos);
    }
    return _grafo_visual_layout_grilla(datos);
}

// ============================================================
// Render
// ============================================================

function _renderizar_grafo_visual(vb_preservado = null, opciones = {}) {
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    const datos = _grafo_visual_datos;
    if (!datos || !datos.nodos || datos.nodos.length === 0) {
        cont.innerHTML = '<div class="grafo-visual-aviso">No hay nodos para mostrar.</div>';
        return;
    }

    const NODO_W = GRAFO_VISUAL_NODO_W;
    const NODO_H = GRAFO_VISUAL_NODO_H;

    let posiciones;
    const hay_posiciones = Object.keys(_grafo_visual_posiciones).length > 0;
    if (opciones.recalcular || !hay_posiciones) {
        posiciones = _grafo_visual_calcular_posiciones(datos);
        _grafo_visual_posiciones = posiciones;
    } else {
        posiciones = _grafo_visual_posiciones;
        const padre_default = posiciones[Object.keys(posiciones)[0]] || { x: 0, y: 0 };
        datos.nodos.forEach(nd => {
            if (!posiciones[nd.id]) {
                const ang = Math.random() * Math.PI * 2;
                const r = 200 + Math.random() * 100;
                posiciones[nd.id] = {
                    x: padre_default.x + Math.cos(ang) * r,
                    y: padre_default.y + Math.sin(ang) * r
                };
            }
        });
    }

    const centros = {};
    Object.keys(posiciones).forEach(id => {
        centros[id] = {
            x: posiciones[id].x + NODO_W / 2,
            y: posiciones[id].y + NODO_H / 2
        };
    });

    let min_x = Infinity, min_y = Infinity, max_x = -Infinity, max_y = -Infinity;
    Object.values(posiciones).forEach(p => {
        if (p.x < min_x) min_x = p.x;
        if (p.y < min_y) min_y = p.y;
        if (p.x + NODO_W > max_x) max_x = p.x + NODO_W;
        if (p.y + NODO_H > max_y) max_y = p.y + NODO_H;
    });
    const padding = 40;
    let vb;
    if (vb_preservado) {
        vb = { x: vb_preservado.x, y: vb_preservado.y, w: vb_preservado.w, h: vb_preservado.h };
    } else {
        vb = {
            x: min_x - padding,
            y: min_y - padding,
            w: (max_x - min_x) + 2 * padding,
            h: (max_y - min_y) + 2 * padding
        };
    }
    _grafo_visual_vb = { x: vb.x, y: vb.y, w: vb.w, h: vb.h };

    let wrapper = document.getElementById('grafo_visual_wrapper');
    let panel = document.getElementById('grafo_visual_panel');
    if (!wrapper || !panel) {
        cont.innerHTML = '';
        const controles = document.createElement('div');
        controles.className = 'grafo-visual-controles';
        controles.innerHTML =
            '<div class="grafo-visual-layout-toggle" id="grafo_visual_toggle">' +
            '  <button type="button" data-layout="grilla">Grilla</button>' +
            '  <button type="button" data-layout="force">Forzar</button>' +
            '</div>' +
            '<label class="grafo-visual-max-label">Nodos:' +
            '  <select id="grafo_visual_max_nodos">' +
            '    <option value="100">100</option>' +
            '    <option value="250">250</option>' +
            '    <option value="500" selected>500</option>' +
            '    <option value="1000">1000</option>' +
            '    <option value="2000">2000</option>' +
            '  </select>' +
            '</label>' +
            '<label class="grafo-visual-etiquetas-label">' +
            '  <input type="checkbox" id="grafo_visual_mostrar_etiquetas" checked> Etiquetas' +
            '</label>' +
            '<input type="text" id="grafo_visual_buscar" placeholder="Buscar por ID o dato...">' +
            '<button type="button" class="btn" id="grafo_visual_btn_reacomodar">Reacomodar</button>' +
            '<button type="button" class="btn" id="grafo_visual_btn_centrar">Centrar</button>' +
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
        _grafo_visual_actualizar_toggle();
    }

    // Limpiar el observer de la grilla anterior.
    if (_grafo_visual_grid_observer) {
        _grafo_visual_grid_observer.disconnect();
        _grafo_visual_grid_observer = null;
    }
    _grafo_visual_grid_rect = null;

    wrapper.innerHTML = '';
    _grafo_visual_refs_nodos = {};
    _grafo_visual_refs_enlaces = [];

    const svg_ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svg_ns, 'svg');
    svg.setAttribute('class', 'grafo-visual-svg' + (_grafo_visual_mostrar_etiquetas ? '' : ' sin-etiquetas'));
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
    path_linea.setAttribute('stroke', '#d0d0d0');
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
    marker_path.setAttribute('fill', 'context-stroke');
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
    _grafo_visual_grid_rect = fondo;

    const tipo_por_id = {};
    datos.nodos.forEach(n => { tipo_por_id[n.id] = n.tipo || ''; });

    // Grupo de enlaces visibles.
    const g_enlaces = document.createElementNS(svg_ns, 'g');
    g_enlaces.setAttribute('class', 'grafo-enlaces');

    // Grupo de hit areas (encima de los visibles, debajo de los
    // nodos). Transparentes, gruesas.
    const g_enlaces_hit = document.createElementNS(svg_ns, 'g');
    g_enlaces_hit.setAttribute('class', 'grafo-enlaces-hit');

    datos.enlaces.forEach(e => {
        const co = centros[e.origen];
        const cd = centros[e.destino];
        if (!co || !cd) return;

        const geometria = _grafo_visual_geometria_enlace(co, cd, NODO_W, NODO_H);
        if (!geometria) return;

        const tipo_origen = tipo_por_id[e.origen] || '';
        const clase_tipo = tipo_origen ? (' enlace-' + _grafo_visual_clase_tipo(tipo_origen)) : '';

        const path = document.createElementNS(svg_ns, 'path');
        path.setAttribute('d', geometria.d);
        path.setAttribute('class',
            'grafo-enlace' + clase_tipo + (e.destino_es_compartido ? ' compartido' : ''));
        path.setAttribute('marker-end', 'url(#grafo-flecha)');
        path.dataset.origen = e.origen;
        path.dataset.destino = e.destino;
        path.dataset.enlace = e.enlace;
        g_enlaces.appendChild(path);

        const label = document.createElementNS(svg_ns, 'text');
        label.setAttribute('class', 'grafo-enlace-label');
        label.setAttribute('x', geometria.px_label);
        label.setAttribute('y', geometria.py_label);
        label.setAttribute('text-anchor', 'middle');
        label.setAttribute('dominant-baseline', 'middle');
        label.textContent = e.enlace;
        g_enlaces.appendChild(label);

        // Hit area invisible encima del trazo.
        const hit = document.createElementNS(svg_ns, 'path');
        hit.setAttribute('d', geometria.d);
        hit.setAttribute('class', 'grafo-enlace-hit');
        hit.dataset.origen = e.origen;
        hit.dataset.destino = e.destino;
        hit.dataset.enlace = e.enlace;
        hit.addEventListener('mouseenter', () => {
            path.classList.add('hover');
        });
        hit.addEventListener('mouseleave', () => {
            path.classList.remove('hover');
        });
        hit.addEventListener('click', (ev) => {
            ev.stopPropagation();
            _grafo_visual_animar_centrar_en(e.destino);
        });
        g_enlaces_hit.appendChild(hit);

        _grafo_visual_refs_enlaces.push({
            path, label, hit, origen: e.origen, destino: e.destino
        });
    });

    svg.appendChild(g_enlaces);
    svg.appendChild(g_enlaces_hit);

    const g_nodos = document.createElementNS(svg_ns, 'g');
    g_nodos.setAttribute('class', 'grafo-nodos');

    datos.nodos.forEach(n => {
        const p = posiciones[n.id];
        if (!p) return;
        const g = document.createElementNS(svg_ns, 'g');
        const clase_tipo = _grafo_visual_clase_tipo(n.tipo);
        const clase_sel = (_grafo_visual_seleccionado === n.id) ? ' seleccionado' : '';
        const clase_comp = n.es_compartido ? ' compartido' : '';
        g.setAttribute('class',
            'grafo-nodo ' + clase_tipo + (n.es_especial ? ' especial' : '') + clase_comp + clase_sel);
        g.dataset.idNodo = n.id;

        const rect = document.createElementNS(svg_ns, 'rect');
        rect.setAttribute('x', p.x);
        rect.setAttribute('y', p.y);
        rect.setAttribute('width', NODO_W);
        rect.setAttribute('height', NODO_H);
        rect.setAttribute('rx', '8');
        rect.setAttribute('ry', '8');
        g.appendChild(rect);

        // Texto del nodo: tipo arriba, dato abajo, ID (solo
        // si es especial) al pie. Usa foreignObject + HTML
        // para tener ellipsis real sin calcular el ancho
        // del texto a mano.
        const ns_xhtml = 'http://www.w3.org/1999/xhtml';
        const fo = document.createElementNS(svg_ns, 'foreignObject');
        fo.setAttribute('x', p.x);
        fo.setAttribute('y', p.y);
        fo.setAttribute('width', NODO_W);
        fo.setAttribute('height', NODO_H);
        fo.setAttribute('pointer-events', 'none');

        const div_texto = document.createElementNS(ns_xhtml, 'div');
        div_texto.setAttribute('class', 'grafo-nodo-texto');

        const div_tipo = document.createElementNS(ns_xhtml, 'div');
        div_tipo.setAttribute('class', 'grafo-nodo-tipo');
        div_tipo.textContent = n.tipo || '?';
        div_texto.appendChild(div_tipo);

        if (n.dato) {
            const div_dato = document.createElementNS(ns_xhtml, 'div');
            div_dato.setAttribute('class', 'grafo-nodo-dato');
            div_dato.textContent = n.dato;
            div_texto.appendChild(div_dato);
        }

        const div_id = document.createElementNS(ns_xhtml, 'div');
        div_id.setAttribute('class', 'grafo-nodo-id');
        div_id.textContent = '#' + n.id;
        div_texto.appendChild(div_id);

        fo.appendChild(div_texto);
        g.appendChild(fo);

        // Tooltip con el contenido completo.
        const titulo_g = document.createElementNS(svg_ns, 'title');
        const partes_titulo = [n.tipo || '?'];
        if (n.dato) partes_titulo.push(n.dato);
        partes_titulo.push('ID: ' + n.id);
        titulo_g.textContent = partes_titulo.join(' · ');
        g.appendChild(titulo_g);

        g.addEventListener('mousedown', (ev) => {
            if (ev.button !== 0) return;
            ev.stopPropagation();
            _grafo_visual_iniciar_drag(ev, n.id);
        });

        g.addEventListener('click', (ev) => {
            ev.stopPropagation();
            if (_grafo_visual_drag && _grafo_visual_drag.movido) return;
            _grafo_visual_seleccionar(n.id);
        });

        g.addEventListener('dblclick', (ev) => {
            ev.stopPropagation();
            _grafo_visual_expandir(n.id);
        });

        g_nodos.appendChild(g);
        _grafo_visual_refs_nodos[n.id] = { g, rect, fo };
    });

    svg.appendChild(g_nodos);
    wrapper.appendChild(svg);
    _grafo_visual_svg = svg;

    // Observar cambios del viewBox para expandir la grilla
    // de fondo al área visible (pan, zoom, centrar,
    // animaciones).
    _grafo_visual_grid_observer = new MutationObserver(_grafo_visual_actualizar_grid_rect);
    _grafo_visual_grid_observer.observe(svg, { attributes: true, attributeFilter: ['viewBox'] });
    // Aplicar el margen al rect desde el primer render. El
    // observer solo dispara cuando CAMBIA el viewBox; sin
    // esta llamada, el fondo inicial queda del tamaño exacto
    // del viewBox y recién se expande con el primer pan/zoom.
    _grafo_visual_actualizar_grid_rect();

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

    // Acciones flotantes (abajo a la derecha): ampliar +
    // teatro + fullscreen.
    const acciones = document.createElement('div');
    acciones.className = 'grafo-visual-acciones';

    const btn_ampliar = document.createElement('button');
    btn_ampliar.type = 'button';
    btn_ampliar.id = 'grafo_visual_btn_ampliar';
    btn_ampliar.className = 'grafo-visual-accion-btn';
    btn_ampliar.title = 'Ampliar';
    btn_ampliar.textContent = '\u25A1';
    btn_ampliar.addEventListener('click', _grafo_visual_toggle_ampliar);
    acciones.appendChild(btn_ampliar);

    const btn_teatro = document.createElement('button');
    btn_teatro.type = 'button';
    btn_teatro.id = 'grafo_visual_btn_teatro';
    btn_teatro.className = 'grafo-visual-accion-btn';
    btn_teatro.title = 'Modo teatro';
    btn_teatro.textContent = '\u26F6';
    btn_teatro.addEventListener('click', _grafo_visual_toggle_teatro);
    acciones.appendChild(btn_teatro);

    const btn_fs = document.createElement('button');
    btn_fs.type = 'button';
    btn_fs.id = 'grafo_visual_btn_fullscreen';
    btn_fs.className = 'grafo-visual-accion-btn';
    btn_fs.title = 'Pantalla completa';
    btn_fs.textContent = '\u2922';
    btn_fs.addEventListener('click', _grafo_visual_toggle_fullscreen);
    acciones.appendChild(btn_fs);

    wrapper.appendChild(acciones);

    _conectar_pan_zoom(svg);

    const input_buscar = document.getElementById('grafo_visual_buscar');
    if (input_buscar && input_buscar.value.trim() !== '') {
        _grafo_visual_aplicar_busqueda(input_buscar.value);
    }
}

function _grafo_visual_geometria_enlace(co, cd, NODO_W, NODO_H) {
    const dx = cd.x - co.x;
    const dy = cd.y - co.y;
    if (dx === 0 && dy === 0) return null;

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

    const px_label = 0.25 * p_salida.x + 0.5 * cx_control + 0.25 * p_entrada.x;
    const py_label = 0.25 * p_salida.y + 0.5 * cy_control + 0.25 * p_entrada.y;

    return {
        d: `M ${p_salida.x} ${p_salida.y} Q ${cx_control} ${cy_control} ${p_entrada.x} ${p_entrada.y}`,
        px_label, py_label
    };
}

/**
 * Expande el rect de la grilla de fondo para que cubra el
 * viewBox actual con margen. Se llama desde un
 * MutationObserver cada vez que cambia el viewBox (pan,
 * zoom, centrar, animaciones). El patrón es
 * userSpaceOnUse, así que las líneas quedan ancladas al
 * origen del grafo y la grilla "se reproduce" hacia los
 * costados cuando el viewBox crece.
 */
function _grafo_visual_actualizar_grid_rect() {
    const grid = _grafo_visual_grid_rect;
    const vb = _grafo_visual_vb;
    if (!grid || !vb) return;
    const margen_x = vb.w * 0.5;
    const margen_y = vb.h * 0.5;
    grid.setAttribute('x', vb.x - margen_x);
    grid.setAttribute('y', vb.y - margen_y);
    grid.setAttribute('width', vb.w + 2 * margen_x);
    grid.setAttribute('height', vb.h + 2 * margen_y);
}

function _grafo_visual_actualizar_toggle() {
    const cont = document.getElementById('grafo_visual_toggle');
    if (!cont) return;
    cont.querySelectorAll('button').forEach(b => {
        b.classList.toggle('active', b.dataset.layout === _grafo_visual_layout);
    });
}

// ============================================================
// Drag con física
// ============================================================

function _grafo_visual_iniciar_drag(ev, id) {
    const svg = _grafo_visual_svg;
    if (!svg) return;

    const rect = svg.getBoundingClientRect();
    const vb = _grafo_visual_vb;
    const escala_x = vb.w / rect.width;
    const escala_y = vb.h / rect.height;

    const cursor_x = vb.x + (ev.clientX - rect.left) * escala_x;
    const cursor_y = vb.y + (ev.clientY - rect.top) * escala_y;

    const pos = _grafo_visual_posiciones[id];
    if (!pos) return;

    _grafo_visual_drag = {
        id,
        offset_x: cursor_x - pos.x,
        offset_y: cursor_y - pos.y,
        movido: false,
        start_x: ev.clientX,
        start_y: ev.clientY
    };

    const ref = _grafo_visual_refs_nodos[id];
    if (ref) ref.g.classList.add('arrastrando');

    document.addEventListener('mousemove', _grafo_visual_drag_move);
    document.addEventListener('mouseup', _grafo_visual_drag_end);
}

function _grafo_visual_drag_move(ev) {
    const drag = _grafo_visual_drag;
    if (!drag) return;

    if (Math.abs(ev.clientX - drag.start_x) > 5 || Math.abs(ev.clientY - drag.start_y) > 5) {
        drag.movido = true;
    }

    const svg = _grafo_visual_svg;
    if (!svg) return;

    const rect = svg.getBoundingClientRect();
    const vb = _grafo_visual_vb;
    const escala_x = vb.w / rect.width;
    const escala_y = vb.h / rect.height;

    const cursor_x = vb.x + (ev.clientX - rect.left) * escala_x;
    const cursor_y = vb.y + (ev.clientY - rect.top) * escala_y;

    const pos_antes = _grafo_visual_posiciones[drag.id];
    const dx_nodo = (cursor_x - drag.offset_x) - pos_antes.x;
    const dy_nodo = (cursor_y - drag.offset_y) - pos_antes.y;
    pos_antes.x = cursor_x - drag.offset_x;
    pos_antes.y = cursor_y - drag.offset_y;

    _grafo_visual_aplicar_fisica_drag(drag.id, dx_nodo, dy_nodo);
    _grafo_visual_actualizar_dom_drag();
}

function _grafo_visual_drag_end() {
    const drag = _grafo_visual_drag;
    if (!drag) return;

    const ref = _grafo_visual_refs_nodos[drag.id];
    if (ref) ref.g.classList.remove('arrastrando');

    document.removeEventListener('mousemove', _grafo_visual_drag_move);
    document.removeEventListener('mouseup', _grafo_visual_drag_end);

    _grafo_visual_asentar_vecinos(drag.id);
    _grafo_visual_actualizar_dom_drag();

    _grafo_visual_drag = null;
}

function _grafo_visual_aplicar_fisica_drag(id_movido, dx_nodo, dy_nodo) {
    const datos = _grafo_visual_datos;
    if (!datos) return;

    const pos_movido = _grafo_visual_posiciones[id_movido];
    if (!pos_movido) return;

    const cx_m = pos_movido.x + GRAFO_VISUAL_NODO_W / 2;
    const cy_m = pos_movido.y + GRAFO_VISUAL_NODO_H / 2;

    const magn_mov = Math.sqrt(dx_nodo * dx_nodo + dy_nodo * dy_nodo);
    const direccion_x = magn_mov > 0 ? dx_nodo / magn_mov : 0;
    const direccion_y = magn_mov > 0 ? dy_nodo / magn_mov : 0;

    const radio_influencia = 260;
    const fuerza = Math.min(magn_mov * 0.35, 30);

    const vecinos_directos = {};
    datos.enlaces.forEach(e => {
        if (e.origen === id_movido) vecinos_directos[e.destino] = true;
        if (e.destino === id_movido) vecinos_directos[e.origen] = true;
    });

    datos.nodos.forEach(nd => {
        if (nd.id === id_movido) return;
        const pos = _grafo_visual_posiciones[nd.id];
        if (!pos) return;

        const cx = pos.x + GRAFO_VISUAL_NODO_W / 2;
        const cy = pos.y + GRAFO_VISUAL_NODO_H / 2;
        const dx = cx - cx_m;
        const dy = cy - cy_m;
        const dist = Math.sqrt(dx * dx + dy * dy) + 0.01;

        if (vecinos_directos[nd.id]) {
            const k_atr = 0.05;
            pos.x += dx_nodo * k_atr * -1;
            pos.y += dy_nodo * k_atr * -1;
        }

        if (dist < radio_influencia) {
            const factor = (1 - dist / radio_influencia);
            const empuje = fuerza * factor * factor;
            const ux = dx / dist;
            const uy = dy / dist;
            const k_radial = 0.5;
            pos.x += (direccion_x + ux * k_radial) * empuje;
            pos.y += (direccion_y + uy * k_radial) * empuje;
        }
    });
}

function _grafo_visual_asentar_vecinos(id_movido) {
    const datos = _grafo_visual_datos;
    if (!datos) return;

    const pos_movido = _grafo_visual_posiciones[id_movido];
    if (!pos_movido) return;

    const diag_nodo = Math.sqrt(
        GRAFO_VISUAL_NODO_W * GRAFO_VISUAL_NODO_W +
        GRAFO_VISUAL_NODO_H * GRAFO_VISUAL_NODO_H
    );
    const min_dist = diag_nodo + 15;

    for (let it = 0; it < 30; it++) {
        for (let i = 0; i < datos.nodos.length; i++) {
            const a_id = datos.nodos[i].id;
            if (a_id === id_movido) continue;
            const pa = _grafo_visual_posiciones[a_id];
            if (!pa) continue;
            for (let j = i + 1; j < datos.nodos.length; j++) {
                const b_id = datos.nodos[j].id;
                if (b_id === id_movido) continue;
                const pb = _grafo_visual_posiciones[b_id];
                if (!pb) continue;
                const dx = pa.x - pb.x;
                const dy = pa.y - pb.y;
                const d = Math.sqrt(dx * dx + dy * dy) + 0.01;
                if (d >= min_dist) continue;
                const empuje = (min_dist - d) * 0.5;
                const ux = dx / d;
                const uy = dy / d;
                pa.x += ux * empuje;
                pa.y += uy * empuje;
                pb.x -= ux * empuje;
                pb.y -= uy * empuje;
            }
        }
    }
}

function _grafo_visual_actualizar_dom_drag() {
    const posiciones = _grafo_visual_posiciones;

    Object.keys(_grafo_visual_refs_nodos).forEach(id => {
        const ref = _grafo_visual_refs_nodos[id];
        const p = posiciones[id];
        if (!ref || !p) return;
        ref.rect.setAttribute('x', p.x);
        ref.rect.setAttribute('y', p.y);
        if (ref.fo) {
            ref.fo.setAttribute('x', p.x);
            ref.fo.setAttribute('y', p.y);
        }
    });

    const NODO_W = GRAFO_VISUAL_NODO_W;
    const NODO_H = GRAFO_VISUAL_NODO_H;
    _grafo_visual_refs_enlaces.forEach(ref => {
        const p_o = posiciones[ref.origen];
        const p_d = posiciones[ref.destino];
        if (!p_o || !p_d) return;
        const co = { x: p_o.x + NODO_W / 2, y: p_o.y + NODO_H / 2 };
        const cd = { x: p_d.x + NODO_W / 2, y: p_d.y + NODO_H / 2 };
        const geom = _grafo_visual_geometria_enlace(co, cd, NODO_W, NODO_H);
        if (!geom) return;
        ref.path.setAttribute('d', geom.d);
        if (ref.label) {
            ref.label.setAttribute('x', geom.px_label);
            ref.label.setAttribute('y', geom.py_label);
        }
        if (ref.hit) ref.hit.setAttribute('d', geom.d);
    });
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

    let arrastrando_fondo = false;
    let start_x = 0, start_y = 0;
    let vb_inicial = null;

    svg.addEventListener('mousedown', (e) => {
        if (e.target.closest && e.target.closest('.grafo-nodo')) return;
        if (e.target.closest && e.target.closest('.grafo-enlace-hit')) return;
        arrastrando_fondo = true;
        svg.classList.add('arrastrando');
        start_x = e.clientX;
        start_y = e.clientY;
        vb_inicial = { x: _grafo_visual_vb.x, y: _grafo_visual_vb.y,
                        w: _grafo_visual_vb.w, h: _grafo_visual_vb.h };
    });

    window.addEventListener('mousemove', (e) => {
        if (!arrastrando_fondo) return;
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
        if (arrastrando_fondo) {
            arrastrando_fondo = false;
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

    document.querySelectorAll('.grafo-nodo.seleccionado').forEach(g => {
        g.classList.remove('seleccionado');
    });
    const g_nodo = document.querySelector('.grafo-nodo[data-id-nodo="' + CSS.escape(id) + '"]');
    if (g_nodo) g_nodo.classList.add('seleccionado');

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

    // Usar el tipo local (inferido) si lo tenemos.
    const nodo_local = (_grafo_visual_datos && _grafo_visual_datos.nodos || [])
        .find(x => x.id === id);
    if (nodo_local && nodo_local.tipo) {
        detalle.tipo = nodo_local.tipo;
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
            _grafo_visual_animar_centrar_en(destino);
        });
    });
}

/**
 * Centra la vista en el nodo con una animación suave.
 * Solo cambia x/y del viewBox; el zoom (w/h) queda igual.
 */
function _grafo_visual_animar_centrar_en(id, duracion_ms = 400) {
    const pos = _grafo_visual_posiciones[id];
    if (!pos || !_grafo_visual_vb) return;
    const svg = _grafo_visual_svg;
    if (!svg) return;

    const vb = _grafo_visual_vb;
    const cx = pos.x + GRAFO_VISUAL_NODO_W / 2;
    const cy = pos.y + GRAFO_VISUAL_NODO_H / 2;
    const target_x = cx - vb.w / 2;
    const target_y = cy - vb.h / 2;

    if (Math.abs(target_x - vb.x) < 0.5 && Math.abs(target_y - vb.y) < 0.5) return;

    const inicio_x = vb.x;
    const inicio_y = vb.y;
    const t0 = performance.now();

    function paso(t) {
        const u = Math.min((t - t0) / duracion_ms, 1);
        const e = 1 - Math.pow(1 - u, 3); // ease-out cubic
        vb.x = inicio_x + (target_x - inicio_x) * e;
        vb.y = inicio_y + (target_y - inicio_y) * e;
        svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
        if (u < 1) requestAnimationFrame(paso);
    }
    requestAnimationFrame(paso);
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

    const vb_antes = _grafo_visual_vb
        ? { x: _grafo_visual_vb.x, y: _grafo_visual_vb.y,
            w: _grafo_visual_vb.w, h: _grafo_visual_vb.h }
        : null;

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

    const pos_padre = _grafo_visual_posiciones[id] || { x: 0, y: 0 };
    let agregados = 0;

    (detalle.adyacentes || []).forEach(a => {
        if (!ids_actuales[a.id_destino]) {
            datos.nodos.push({
                id: a.id_destino,
                dato: a.dato_destino || '',
                es_especial: !/^\d+$/.test(a.id_destino),
                es_raiz: false,
                es_compartido: a.destino_es_compartido === true,
                tipo: a.tipo_destino || '?',
                n_adyacentes: 0,
                n_referencias: 0
            });
            const ang = Math.random() * Math.PI * 2;
            const r = 200 + Math.random() * 80;
            _grafo_visual_posiciones[a.id_destino] = {
                x: pos_padre.x + Math.cos(ang) * r,
                y: pos_padre.y + Math.sin(ang) * r
            };
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

    (detalle.referencias || []).forEach(r => {
        if (!ids_actuales[r.id_origen]) {
            datos.nodos.push({
                id: r.id_origen,
                dato: r.dato_origen || '',
                es_especial: !/^\d+$/.test(r.id_origen),
                es_raiz: false,
                es_compartido: false,
                tipo: r.tipo_origen || '?',
                n_adyacentes: 0,
                n_referencias: 0
            });
            const ang = Math.random() * Math.PI * 2;
            const r_ = 200 + Math.random() * 80;
            _grafo_visual_posiciones[r.id_origen] = {
                x: pos_padre.x + Math.cos(ang) * r_,
                y: pos_padre.y + Math.sin(ang) * r_
            };
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
        _grafo_visual_recalcular_tipos(datos);
        _grafo_visual_asentar_vecinos(id);
        _renderizar_grafo_visual(vb_antes);
        _grafo_visual_animar_centrar_en(id);
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
        _grafo_visual_animar_centrar_en(matches[0]);
    }
}

function _grafo_visual_ir_a_match(delta) {
    if (_grafo_visual_matches.length === 0) return;
    const n = _grafo_visual_matches.length;
    _grafo_visual_match_actual = (_grafo_visual_match_actual + delta + n) % n;
    const id = _grafo_visual_matches[_grafo_visual_match_actual];
    _grafo_visual_animar_centrar_en(id);
    const info = document.getElementById('grafo_visual_buscar_info');
    if (info) info.textContent = (_grafo_visual_match_actual + 1) + '/' + n;
}

// ============================================================
// Controles
// ============================================================

function _conectar_controles_grafo_visual() {
    const toggle = document.getElementById('grafo_visual_toggle');
    if (toggle) {
        toggle.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const layout = btn.dataset.layout;
                if (layout === _grafo_visual_layout) return;
                _grafo_visual_layout = layout;
                _grafo_visual_actualizar_toggle();
                _grafo_visual_posiciones = {};
                _renderizar_grafo_visual(null, { recalcular: true });
            });
        });
    }

    const sel_max = document.getElementById('grafo_visual_max_nodos');
    if (sel_max) {
        sel_max.addEventListener('change', () => {
            const val = parseInt(sel_max.value, 10);
            if (!isNaN(val) && val > 0) {
                _grafo_visual_max_nodos = val;
                cargar_grafo_visual();
            }
        });
    }

    const chk_etiquetas = document.getElementById('grafo_visual_mostrar_etiquetas');
    if (chk_etiquetas) {
        chk_etiquetas.checked = _grafo_visual_mostrar_etiquetas;
        chk_etiquetas.addEventListener('change', () => {
            _grafo_visual_mostrar_etiquetas = chk_etiquetas.checked;
            const svg = document.querySelector('.grafo-visual-svg');
            if (svg) svg.classList.toggle('sin-etiquetas', !_grafo_visual_mostrar_etiquetas);
        });
    }

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

    const btn_reacomodar = document.getElementById('grafo_visual_btn_reacomodar');
    if (btn_reacomodar) {
        btn_reacomodar.addEventListener('click', () => {
            _grafo_visual_posiciones = {};
            _renderizar_grafo_visual(null, { recalcular: true });
        });
    }

    const btn_centrar = document.getElementById('grafo_visual_btn_centrar');
    if (btn_centrar) {
        btn_centrar.addEventListener('click', () => {
            if (!_grafo_visual_datos || !_grafo_visual_vb) return;
            const gs = document.querySelectorAll('.grafo-nodo rect');
            if (gs.length === 0) return;
            let min_x = Infinity, min_y = Infinity, max_x = -Infinity, max_y = -Infinity;
            gs.forEach(r => {
                const x = parseFloat(r.getAttribute('x'));
                const y = parseFloat(r.getAttribute('y'));
                const w = parseFloat(r.getAttribute('width'));
                const h = parseFloat(r.getAttribute('height'));
                if (x < min_x) min_x = x;
                if (y < min_y) min_y = y;
                if (x + w > max_x) max_x = x + w;
                if (y + h > max_y) max_y = y + h;
            });
            const padding = 40;
            const vb = _grafo_visual_vb;
            const w = (max_x - min_x) + 2 * padding;
            const h = (max_y - min_y) + 2 * padding;
            const cx = (min_x + max_x) / 2;
            const cy = (min_y + max_y) / 2;
            vb.w = w;
            vb.h = h;
            vb.x = cx - w / 2;
            vb.y = cy - h / 2;
            const svg = _grafo_visual_svg;
            if (svg) svg.setAttribute('viewBox', `${vb.x} ${vb.y} ${vb.w} ${vb.h}`);
        });
    }

    const btn_reset = document.getElementById('grafo_visual_btn_reset');
    if (btn_reset) {
        btn_reset.addEventListener('click', () => {
            _renderizar_grafo_visual(null, { recalcular: false });
        });
    }
}

// ============================================================
// Pantalla completa y modo teatro
// ============================================================

function _grafo_visual_toggle_fullscreen() {
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    if (document.fullscreenElement === cont) {
        if (document.exitFullscreen) document.exitFullscreen();
    } else {
        if (cont.requestFullscreen) {
            const p = cont.requestFullscreen();
            if (p && typeof p.catch === 'function') p.catch(() => {});
        }
    }
}

function _grafo_visual_toggle_ampliar() {
    if (document.fullscreenElement) return;
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    // Excluyente con teatro.
    cont.classList.remove('grafo-visual-teatro');
    cont.classList.toggle('grafo-visual-ampliar');
    _grafo_visual_actualizar_body_expandido();
    _grafo_visual_actualizar_botones();
}

function _grafo_visual_toggle_teatro() {
    // Si está en fullscreen, no hacer nada: el fullscreen manda.
    if (document.fullscreenElement) return;
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    // Excluyente con ampliar.
    cont.classList.remove('grafo-visual-ampliar');
    cont.classList.toggle('grafo-visual-teatro');
    _grafo_visual_actualizar_body_expandido();
    _grafo_visual_actualizar_botones();
}

function _grafo_visual_actualizar_body_expandido() {
    const cont = document.getElementById('grafo_visual_contenedor');
    if (!cont) return;
    const expandido = cont.classList.contains('grafo-visual-ampliar')
        || cont.classList.contains('grafo-visual-teatro');
    document.body.classList.toggle('grafo-visual-expandido', expandido);
}

function _grafo_visual_actualizar_botones() {
    const cont = document.getElementById('grafo_visual_contenedor');

    const btn_ampliar = document.getElementById('grafo_visual_btn_ampliar');
    if (btn_ampliar && cont) {
        const en_ampliar = cont.classList.contains('grafo-visual-ampliar');
        btn_ampliar.textContent = en_ampliar ? '\u25A0' : '\u25A1';
        btn_ampliar.title = en_ampliar ? 'Restaurar tamaño' : 'Ampliar';
    }

    const btn_teatro = document.getElementById('grafo_visual_btn_teatro');
    if (btn_teatro && cont) {
        const en_teatro = cont.classList.contains('grafo-visual-teatro');
        btn_teatro.textContent = en_teatro ? '\u25AD' : '\u26F6';
        btn_teatro.title = en_teatro ? 'Salir del modo teatro' : 'Modo teatro';
    }

    const btn_fs = document.getElementById('grafo_visual_btn_fullscreen');
    if (btn_fs) {
        const en_fs = (document.fullscreenElement === cont);
        btn_fs.textContent = en_fs ? '\u2921' : '\u2922';
        btn_fs.title = en_fs ? 'Salir de pantalla completa' : 'Pantalla completa';
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
        // Actualizar íconos al entrar/salir de fullscreen.
        document.addEventListener('fullscreenchange', _grafo_visual_actualizar_botones);
        // Esc cierra el modo ampliado o el teatro (si no está
        // en fullscreen).
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const cont = document.getElementById('grafo_visual_contenedor');
            if (!cont) return;
            if (document.fullscreenElement) return;
            const tiene_ampliar = cont.classList.contains('grafo-visual-ampliar');
            const tiene_teatro = cont.classList.contains('grafo-visual-teatro');
            if (!tiene_ampliar && !tiene_teatro) return;
            cont.classList.remove('grafo-visual-ampliar', 'grafo-visual-teatro');
            document.body.classList.remove('grafo-visual-expandido');
            _grafo_visual_actualizar_botones();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _setup);
    } else {
        _setup();
    }
})();