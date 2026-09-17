/***
 * Asientos y pasaje del micro.
 * @version 1.5piloto.41
 */

// Modo actual del panel #info_asiento_viaje.
// Valores: null (oculto), 'propios' (grilla de propios), 'simple' (una sola tarjeta ajena/libre/vendida).
let info_asiento_modo = null;

// Polling adaptativo por inactividad.
const SYNC_INTERVALO_MS = 15000;
const SYNC_MAX_SIN_ACTIVIDAD = 10;
let sync_contador_sin_actividad = 0;
let sync_asientos_pausado = false;

function obtener_dueno_viaje_seleccionado() {
    if (viaje_seleccionado && viaje_seleccionado.dueno) {
        return viaje_seleccionado.dueno;
    }
    return obtener_nombre_dueno_actual();
}

function iniciar_sync_asientos() {
    detener_sync_asientos();
    if (!viaje_seleccionado || !micro_seleccionado) return;

    microSyncActual = micro_seleccionado;
    sync_contador_sin_actividad = 0;
    sync_asientos_pausado = false;
    ocultar_cartel_inactividad();
    solicitar_estado_asientos();
    intervaloSyncAsientos = setInterval(solicitar_estado_asientos, SYNC_INTERVALO_MS);
}

function detener_sync_asientos() {
    if (intervaloSyncAsientos) {
        clearInterval(intervaloSyncAsientos);
        intervaloSyncAsientos = null;
    }
    microSyncActual = null;
    sync_asientos_pausado = false;
    sync_contador_sin_actividad = 0;
    ocultar_cartel_inactividad();
}

async function solicitar_estado_asientos() {
    const nombre_micro_actual = microSyncActual || micro_seleccionado;
    if (!nombre_micro_actual || !viaje_seleccionado) return;

    let nombre_dueno = obtener_dueno_viaje_seleccionado();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/estado_asientos",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro: nombre_micro_actual,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        estados_asientos_actuales = datos.asientos;
        actualizar_colores_asientos(datos.asientos);
        refrescar_info_asientos_propios(info_asiento_modo === null);
    }

    sync_contador_sin_actividad++;
    if (sync_contador_sin_actividad >= SYNC_MAX_SIN_ACTIVIDAD && !sync_asientos_pausado) {
        pausar_sync_por_inactividad();
    }
}

function pausar_sync_por_inactividad() {
    if (intervaloSyncAsientos) {
        clearInterval(intervaloSyncAsientos);
        intervaloSyncAsientos = null;
    }
    sync_asientos_pausado = true;
    mostrar_cartel_inactividad();
}

function reanudar_sync_por_actividad() {
    if (!sync_asientos_pausado) return;
    if (!viaje_seleccionado || !micro_seleccionado) return;

    sync_asientos_pausado = false;
    sync_contador_sin_actividad = 0;
    ocultar_cartel_inactividad();
    solicitar_estado_asientos();
    intervaloSyncAsientos = setInterval(solicitar_estado_asientos, SYNC_INTERVALO_MS);
}

function registrar_actividad_usuario() {
    sync_contador_sin_actividad = 0;
    if (sync_asientos_pausado) {
        reanudar_sync_por_actividad();
    }
}

function mostrar_cartel_inactividad() {
    const cartel = document.getElementById('aviso_inactividad_pasaje');
    if (cartel) cartel.classList.remove('hidden');
}

function ocultar_cartel_inactividad() {
    const cartel = document.getElementById('aviso_inactividad_pasaje');
    if (cartel) cartel.classList.add('hidden');
}

function actualizar_colores_asientos(estados) {
    const asientosDOM = document.querySelectorAll('#croquis_pasaje_micro .seat');
    asientosDOM.forEach(seat => {
        const fila = seat.dataset.fila;
        const columna = seat.dataset.columna;
        const estadoObj = estados.find(e => e.fila === fila && e.columna === columna);
        if (estadoObj) {
            seat.classList.remove('seat-libre', 'seat-seleccionado', 'seat-seleccionado-propio', 'seat-vendido', 'seat-no-disponible', 'seat-reservado');
            if (estadoObj.estado === 'seleccionado') {
                if (estadoObj.seleccionado_por === usuario_actual.nombre_usuario) {
                    seat.classList.add('seat-seleccionado-propio');
                } else {
                    seat.classList.add('seat-seleccionado');
                }
            } else if (estadoObj.estado === 'reservado') {
                seat.classList.add('seat-reservado');
            } else {
                seat.classList.add(`seat-${estadoObj.estado}`);
            }
            seat.dataset.estado = estadoObj.estado;
            seat.dataset.seleccionado_por = estadoObj.seleccionado_por || '';
            seat.dataset.reservado_por = estadoObj.reservado_por || '';
        }
    });
    mostrar_boton_confirmar_venta();
}

async function seleccionar_asiento_pasaje(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;
    if (operacion_asiento_en_curso) return;

    operacion_asiento_en_curso = true;

    const nombre_dueno = obtener_dueno_viaje_seleccionado();
    const nombre_terminal = usuario_actual.nombre_usuario;
    const nombre_micro = micro_seleccionado;

    detener_sync_asientos();

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/seleccionar_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno,
                nombre_terminal
            })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asiento) {
                asiento.estado = 'seleccionado';
                asiento.seleccionado_por = nombre_terminal;
            }
            actualizar_colores_asientos(estados_asientos_actuales);
            refrescar_info_asientos_propios(true);
            mostrar_aviso("Asiento seleccionado", 'exito');
        } else {
            const estado_actual = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (estado_actual && estado_actual.estado === 'seleccionado' && estado_actual.seleccionado_por === nombre_terminal) {
                mostrar_aviso("Asiento seleccionado", 'exito');
            } else {
                mostrar_aviso(resultado.error || "No se pudo seleccionar", 'error');
                await solicitar_estado_asientos();
            }
        }
    } catch (error) {
        console.error("Error en selección:", error);
        mostrar_aviso("Error de comunicación", 'error');
        await solicitar_estado_asientos();
    } finally {
        operacion_asiento_en_curso = false;
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

async function deseleccionar_asiento_pasaje(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;
    if (operacion_asiento_en_curso) return;

    operacion_asiento_en_curso = true;

    const nombre_dueno = obtener_dueno_viaje_seleccionado();
    const nombre_terminal = usuario_actual.nombre_usuario;
    const nombre_micro = micro_seleccionado;

    detener_sync_asientos();

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/deseleccionar_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno,
                nombre_terminal
            })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asiento) {
                asiento.estado = 'libre';
                asiento.seleccionado_por = null;
            }
            actualizar_colores_asientos(estados_asientos_actuales);
            refrescar_info_asientos_propios(true);
            mostrar_aviso("Asiento liberado", 'exito');
        } else {
            mostrar_aviso(resultado.error || "No se pudo liberar", 'error');
            await solicitar_estado_asientos();
        }
    } catch (error) {
        console.error("Error en deselección:", error);
        mostrar_aviso("Error de comunicación", 'error');
        await solicitar_estado_asientos();
    } finally {
        operacion_asiento_en_curso = false;
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

/**
 * Determina si un asiento es "propio" del usuario actual:
 * - Terminal: seleccionado por el propio usuario.
 * - Dueño/admin: reservado por el equipo.
 */
function es_asiento_propio(asiento) {
    if (!asiento) return false;
    if (usuario_actual.nivel === 'terminal') {
        return asiento.estado === 'seleccionado' && asiento.seleccionado_por === usuario_actual.nombre_usuario;
    }
    if (usuario_actual.nivel === 'dueno' || usuario_actual.nivel === 'admin') {
        return asiento.estado === 'reservado';
    }
    return false;
}

function obtener_asientos_propios() {
    return estados_asientos_actuales.filter(es_asiento_propio);
}

let deseleccion_masiva_en_curso = false;

async function deseleccionar_todos_los_propios() {
    if (deseleccion_masiva_en_curso) return;
    deseleccion_masiva_en_curso = true;
    try {
        await _deseleccionar_todos_los_propios_interno();
    } finally {
        deseleccion_masiva_en_curso = false;
    }
}

async function _deseleccionar_todos_los_propios_interno() {
    const propios = obtener_asientos_propios();
    if (propios.length === 0) return;

    const es_terminal = usuario_actual.nivel === 'terminal';
    const nombre_dueno = obtener_dueno_viaje_seleccionado();
    const nombre_micro = micro_seleccionado;

    for (const asiento of propios) {
        try {
            if (es_terminal) {
                await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        accion: "viajes/deseleccionar_asiento",
                        nombre_viaje: viaje_seleccionado.nombre_viaje,
                        nombre_micro: nombre_micro,
                        fila: asiento.fila,
                        columna: asiento.columna,
                        nombre_dueno: nombre_dueno,
                        nombre_terminal: usuario_actual.nombre_usuario
                    })
                });
                const local = estados_asientos_actuales.find(e => e.fila === asiento.fila && e.columna === asiento.columna);
                if (local) {
                    local.estado = 'libre';
                    local.seleccionado_por = null;
                }
            } else {
                await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        accion: "viajes/liberar_reserva_asiento",
                        nombre_viaje: viaje_seleccionado.nombre_viaje,
                        nombre_micro: nombre_micro,
                        fila: asiento.fila,
                        columna: asiento.columna,
                        nombre_dueno: nombre_dueno
                    })
                });
                const local = estados_asientos_actuales.find(e => e.fila === asiento.fila && e.columna === asiento.columna);
                if (local) {
                    local.estado = 'libre';
                    local.reservado_por = null;
                    local.pasajero = null;
                    local.tiene_pasajero = false;
                }
            }
        } catch (e) {
            console.error("Error al deseleccionar asiento propio:", e);
        }
    }

    actualizar_colores_asientos(estados_asientos_actuales);
}

/**
 * Construye el HTML de una tarjeta de asiento individual.
 *
 * Muestra:
 * - Número de asiento
 * - Estado
 * - Si fue seleccionado por otra terminal (no el propio usuario): quién
 * - Si tiene venta asociada: código de venta y terminal vendedora
 * - Si tiene pasajero: nombre completo, DNI (sin puntos) y celular
 * - Botones según rol y estado
 *
 * Botones:
 * - "Reservar para equipo" (dueño/admin, asiento libre)
 * - "Liberar" (terminal, seleccionado por él mismo)
 * - "Asignar pasajero" (dueño/admin, reservado sin pasajero)
 * - "Liberar reserva" (dueño/admin, reservado)
 * - "Ver pasaje" (todos, reservado con pasajero o vendido)
 * - "Ver compra" (solo si el usuario actual fue quien realizó la venta)
 */
function construir_html_tarjeta_asiento(asiento) {
    const es_propio = es_asiento_propio(asiento);
    const es_terminal = usuario_actual.nivel === 'terminal';
    const es_dueno_o_admin = usuario_actual.nivel === 'dueno' || usuario_actual.nivel === 'admin';

    let html = `<div class="asiento-card">`;
    html += `<div class="asiento-card-header">Asiento ${asiento.numero}</div>`;

    // Texto y color del estado
    if (es_propio && es_terminal) {
        html += `<div class="asiento-card-estado status-propio">SELECCIONADO POR TI</div>`;
    } else {
        html += `<div class="asiento-card-estado status-${asiento.estado}">${String(asiento.estado).toUpperCase()}</div>`;
    }

    // Seleccionado por otra terminal.
    // La comparación sigue siendo contra `seleccionado_por` (nombre de usuario)
    // porque es lo que identifica al propio usuario; solo cambia el texto que
    // se muestra, que prefiere el nombre real.
    if (asiento.seleccionado_por && asiento.seleccionado_por !== usuario_actual.nombre_usuario) {
        const nombre_mostrar = asiento.seleccionado_por_nombre_real || asiento.seleccionado_por;
        html += `<div class="asiento-card-linea"><span>Seleccionado por:</span><b>${nombre_mostrar}</b></div>`;
    }

    // Código de venta (si el asiento tiene venta asociada)
    if (asiento.venta_id) {
        html += `<div class="asiento-card-linea"><span>Cód. de venta:</span><b>${asiento.venta_id}</b></div>`;
    }

    // Terminal vendedora (solo en asientos vendidos)
    if (asiento.estado === 'vendido' && asiento.venta_terminal) {
        const vendedor_mostrar = asiento.venta_terminal_nombre_real || asiento.venta_terminal;
        html += `<div class="asiento-card-linea"><span>Vendido por:</span><b>${vendedor_mostrar}</b></div>`;
    }

    // Datos del pasajero (si hay)
    if (asiento.tiene_pasajero && asiento.pasajero) {
        const p = asiento.pasajero;
        const nombre = p.nombre_completo || '';
        const dni_visible = p.dni_visible || normalizar_dni_js(p.dni || '');
        const celular = p.celular || '';
        if (nombre) html += `<div class="asiento-card-linea"><span>Pasajero:</span><b>${nombre}</b></div>`;
        if (dni_visible) html += `<div class="asiento-card-linea"><span>DNI:</span><b>${dni_visible}</b></div>`;
        if (celular) html += `<div class="asiento-card-linea"><span>Celular:</span><b>${celular}</b></div>`;
    }

    // Botones
    const botones = [];

    if (es_dueno_o_admin && asiento.estado === 'libre') {
        botones.push(`<button class="btn primary btn-reservar-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Reservar para equipo</button>`);
    }

    if (es_terminal && es_propio) {
        botones.push(`<button class="btn danger btn-liberar-seleccion" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar</button>`);
    }

    if (es_dueno_o_admin && asiento.estado === 'reservado' && !asiento.tiene_pasajero) {
        botones.push(`<button class="btn primary btn-asignar-pasajero" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Asignar pasajero</button>`);
    }

    if (es_dueno_o_admin && asiento.estado === 'reservado') {
        botones.push(`<button class="btn danger btn-liberar-reserva" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar reserva</button>`);
    }

    // Ver pasaje: disponible en reservado con pasajero o vendido
    if ((asiento.estado === 'reservado' && asiento.tiene_pasajero) || asiento.estado === 'vendido') {
        botones.push(`<button class="btn btn-ver-pasaje-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Ver pasaje</button>`);
    }

    // Ver compra: dueño y admin lo ven siempre; la terminal solo si ella
    // hizo la venta. La comparación es contra `venta_terminal`, que guarda
    // el nombre de usuario (identificador). El texto que se muestra en la
    // tarjeta usa `venta_terminal_nombre_real` como fallback.
    if (asiento.estado === 'vendido' && asiento.venta_id) {
        const puede_ver_compra = es_dueno_o_admin
            || (es_terminal && asiento.venta_terminal === usuario_actual.nombre_usuario);
        if (puede_ver_compra) {
            botones.push(`<button class="btn btn-ver-compra-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}" data-venta-id="${asiento.venta_id}">Ver compra</button>`);
        }
    }

    if (botones.length > 0) {
        html += `<div class="asiento-card-acciones">${botones.join('')}</div>`;
    }

    html += `</div>`;
    return html;
}

/**
 * Renderiza el panel de info con una lista de tarjetas en grilla de 2 columnas.
 */
function renderizar_tarjetas_asientos(asientos, titulo, modo) {
    const panel = $("#info_asiento_viaje");
    if (!panel) return;

    if (!asientos || asientos.length === 0) {
        panel.innerHTML = '';
        panel.classList.add('hidden');
        info_asiento_modo = null;
        return;
    }

    let html = `<div class="section-title">${titulo}</div>`;
    html += `<div class="asientos-grid">`;
    for (const asiento of asientos) {
        html += construir_html_tarjeta_asiento(asiento);
    }
    html += `</div>`;

    panel.innerHTML = html;
    panel.classList.remove('hidden');
    info_asiento_modo = modo;

    // Listeners de botones
    panel.querySelectorAll('.btn-reservar-asiento').forEach(btn => {
        btn.addEventListener('click', () => abrir_modal_reservar_asiento(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-liberar-seleccion').forEach(btn => {
        btn.addEventListener('click', () => deseleccionar_asiento_pasaje(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-asignar-pasajero').forEach(btn => {
        btn.addEventListener('click', () => abrir_modal_asignar_pasajero(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-liberar-reserva').forEach(btn => {
        btn.addEventListener('click', () => liberar_reserva_equipo(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-ver-pasaje-asiento').forEach(btn => {
        btn.addEventListener('click', () => ver_pasaje_asiento(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-ver-compra-asiento').forEach(btn => {
        btn.addEventListener('click', () => ver_compra_asiento(btn.dataset.ventaId));
    });
}

function refrescar_info_asientos_propios(forzar = false) {
    if (!forzar && (operacion_asiento_en_curso || deseleccion_masiva_en_curso)) return;
    if (!forzar && info_asiento_modo !== 'propios') return;

    const propios = obtener_asientos_propios();
    if (propios.length === 0) {
        const panel = $("#info_asiento_viaje");
        if (panel) {
            panel.innerHTML = '';
            panel.classList.add('hidden');
        }
        info_asiento_modo = null;
        return;
    }

    const titulo = (usuario_actual.nivel === 'terminal')
        ? `Asientos seleccionados (${propios.length})`
        : `Asientos reservados (${propios.length})`;
    renderizar_tarjetas_asientos(propios, titulo, 'propios');
}

function mostrar_info_asiento(asiento) {
    if (!asiento) return;

    if (es_asiento_propio(asiento)) {
        const propios = obtener_asientos_propios();
        const titulo = (usuario_actual.nivel === 'terminal')
            ? `Asientos seleccionados (${propios.length})`
            : `Asientos reservados (${propios.length})`;
        renderizar_tarjetas_asientos(propios, titulo, 'propios');
    } else {
        renderizar_tarjetas_asientos([asiento], 'Información de asiento', 'simple');
    }
}

/**
 * Abre el modal de reserva de un asiento libre para dueño/admin.
 * Incluye un checkbox para asignar los datos del pasajero en el mismo paso.
 */
function abrir_modal_reservar_asiento(fila, columna) {
    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
    if (!asiento) {
        mostrar_aviso("Asiento no encontrado", 'error');
        return;
    }
    if (asiento.estado !== 'libre') {
        mostrar_aviso("El asiento ya no está libre", 'error');
        actualizar_detalle_viaje_actual();
        return;
    }

    const html = `
        <h3>Reservar asiento ${asiento.numero}</h3>
        <div class="field">
            <label><input type="checkbox" id="reservar_asignar_pasajero_check"> ¿Asignar datos del pasajero ahora?</label>
        </div>
        <div id="reservar_form_pasajero_container" style="display:none; margin-top:15px;">
            ${construir_html_formulario_pasajero(0, { incluir_selector_sb: false, incluir_ficha: false })}
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="btn_confirmar_reserva">Reservar</button>
            <button class="btn" id="btn_cancelar_reserva">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado('Reservar asiento', html);

    const contenedorModal = document.getElementById('modal_apilado_contenido');
    const checkbox = contenedorModal.querySelector('#reservar_asignar_pasajero_check');
    const contenedorPasajero = contenedorModal.querySelector('#reservar_form_pasajero_container');

    conectar_listeners_formulario_pasajero(contenedorPasajero, 0);

    checkbox.addEventListener('change', () => {
        contenedorPasajero.style.display = checkbox.checked ? '' : 'none';
    });

    contenedorModal.querySelector('#btn_cancelar_reserva').addEventListener('click', () => {
        cerrar_modal_apilado();
    });

    contenedorModal.querySelector('#btn_confirmar_reserva').addEventListener('click', async () => {
        let datos_pasajero = null;

        if (checkbox.checked) {
            const r = recolectar_datos_pasajero(0, { incluir_selector_sb: false, incluir_ficha: false });
            if (!r.ok) {
                mostrar_aviso(r.error, 'error');
                return;
            }
            datos_pasajero = r.datos;
        }

        const nombre_dueno = obtener_dueno_viaje_seleccionado();
        const params = {
            accion: "viajes/reservar_asiento",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro: micro_seleccionado,
            fila,
            columna,
            nombre_dueno
        };
        if (datos_pasajero) {
            params.datos_pasajero = JSON.stringify(datos_pasajero);
        }

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(params)
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            mostrar_aviso("Asiento reservado", 'exito');

            const nombre_dueno_reserva = nombre_dueno;
            const nombre_viaje_reserva = viaje_seleccionado ? viaje_seleccionado.nombre_viaje : '';
            const nombre_micro_reserva = micro_seleccionado || '';

            cerrar_modal_apilado();

            // Refrescar el modal grande que quedó vivo detrás, sin reconstruirlo.
            await refrescar_contadores_viaje_actual();
            await solicitar_estado_asientos();
            refrescar_info_asientos_propios(true);

            if (datos_pasajero) {
                mostrar_modal_chico_impresion_reserva(
                    nombre_dueno_reserva,
                    nombre_viaje_reserva,
                    nombre_micro_reserva,
                    fila,
                    columna,
                    asiento.numero
                );
            }
        } else {
            mostrar_aviso(resultado.error || "No se pudo reservar", 'error');
        }
    });
}
/**
 * Abre el modal de asignación de pasajero a un asiento reservado sin pasajero.
 *
 * Usa el patrón on_volver para que:
 *  - El header muestre el botón "← Volver".
 *  - "Cancelar" ejecute el callback (vuelve al detalle del viaje) en vez de
 *    cerrar todo.
 *  - Tras el éxito, se vuelva al detalle del viaje y se muestre el modal
 *    chico de impresión por encima.
 */
function abrir_modal_asignar_pasajero(fila, columna) {
    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
    if (!asiento) {
        mostrar_aviso("Asiento no encontrado", 'error');
        return;
    }
    if (asiento.estado !== 'reservado') {
        mostrar_aviso("El asiento ya no está reservado", 'error');
        actualizar_detalle_viaje_actual();
        return;
    }
    if (asiento.tiene_pasajero) {
        mostrar_aviso("El asiento ya tiene un pasajero asignado", 'error');
        return;
    }

    const html = `
        <h3>Asignar pasajero al asiento ${asiento.numero}</h3>
        ${construir_html_formulario_pasajero(0, { incluir_selector_sb: false, incluir_ficha: false })}
        <div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="btn_confirmar_asignar">Asignar</button>
            <button class="btn" id="btn_cancelar_asignar">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado('Asignar pasajero', html);

    const contenedorModal = document.getElementById('modal_apilado_contenido');
    conectar_listeners_formulario_pasajero(contenedorModal, 0);

    contenedorModal.querySelector('#btn_cancelar_asignar').addEventListener('click', () => {
        cerrar_modal_apilado();
    });

    contenedorModal.querySelector('#btn_confirmar_asignar').addEventListener('click', async () => {
        const r = recolectar_datos_pasajero(0, { incluir_selector_sb: false, incluir_ficha: false });
        if (!r.ok) {
            mostrar_aviso(r.error, 'error');
            return;
        }

        const nombre_dueno = obtener_dueno_viaje_seleccionado();
        const nombre_viaje_asig = viaje_seleccionado ? viaje_seleccionado.nombre_viaje : '';
        const nombre_micro_asig = micro_seleccionado || '';

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/asignar_pasajero_reserva",
                nombre_viaje: nombre_viaje_asig,
                nombre_micro: nombre_micro_asig,
                fila,
                columna,
                nombre_dueno,
                datos_pasajero: JSON.stringify(r.datos)
            })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            mostrar_aviso("Pasajero asignado", 'exito');

            cerrar_modal_apilado();

            // Refrescar el modal grande que quedó vivo detrás.
            await refrescar_contadores_viaje_actual();
            await solicitar_estado_asientos();
            refrescar_info_asientos_propios(true);

            mostrar_modal_chico_impresion_reserva(
                nombre_dueno,
                nombre_viaje_asig,
                nombre_micro_asig,
                fila,
                columna,
                asiento.numero
            );
        } else {
            mostrar_aviso(resultado.error || "No se pudo asignar", 'error');
        }
    });
}

/**
 * Muestra el modal con los datos del pasajero de un asiento (reservado con
 * pasajero o vendido). No muestra ficha de salud ni opción de imprimir.
 */
function ver_pasaje_asiento(fila, columna) {
    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
    if (!asiento) {
        mostrar_aviso("Asiento no encontrado", 'error');
        return;
    }
    if (!asiento.tiene_pasajero || !asiento.pasajero) {
        mostrar_aviso("Este asiento no tiene pasajero", 'error');
        return;
    }

    const p = asiento.pasajero;
    const nombre = p.nombre_completo || '';
    const dni_visible = p.dni_visible || normalizar_dni_js(p.dni || '');
    const celular = p.celular || '';
    const celular_emergencia = p.celular_emergencia || '';
    const email = p.email || '';
    const fecha_nacimiento = p.fecha_nacimiento_visible || p.fecha_nacimiento || '';
    const direccion = p.direccion || '';
    const localidad = p.localidad || '';

    let html = `<h3>Pasaje del asiento ${asiento.numero}</h3>`;
    html += `<div class="seccion">`;
    html += `<h4>Datos del pasajero</h4>`;
    if (nombre) html += `<div class="detail-line"><span>Nombre:</span><strong>${nombre}</strong></div>`;
    if (dni_visible) html += `<div class="detail-line"><span>DNI:</span><strong>${dni_visible}</strong></div>`;
    if (celular) html += `<div class="detail-line"><span>Celular:</span><strong>${celular}</strong></div>`;
    if (celular_emergencia) html += `<div class="detail-line"><span>Celular de emergencia:</span><strong>${celular_emergencia}</strong></div>`;
    if (email) html += `<div class="detail-line"><span>Email:</span><strong>${email}</strong></div>`;
    if (fecha_nacimiento) html += `<div class="detail-line"><span>Fecha de nacimiento:</span><strong>${fecha_nacimiento}</strong></div>`;
    if (direccion || localidad) {
        const dir = [direccion, localidad].filter(v => v).join(', ');
        html += `<div class="detail-line"><span>Dirección:</span><strong>${dir}</strong></div>`;
    }
    html += `</div>`;

    // El botón de imprimir aparece siempre que haya pasajero. Según si hay
    // venta asociada o no, la URL cambia:
    //  - Con venta: usa el flujo de impresión de venta (con filtro por DNI).
    //  - Sin venta (reserva del equipo): usa el flujo de impresión de reserva,
    //    que muestra "EQUIPO" en lugar del código de venta.
    const nv = viaje_seleccionado ? viaje_seleccionado.nombre_viaje : '';
    const nm = micro_seleccionado || '';
    const nd = obtener_dueno_viaje_seleccionado();
    if (asiento.venta_id) {
        html += `<div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="venta" data-venta-id="${asiento.venta_id}" data-dni="${p.dni || ''}">Imprimir pasaje</button>
            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>
        </div>`;
    } else {
        html += `<div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="reserva" data-dueno="${nd}" data-viaje="${nv}" data-micro="${nm}" data-fila="${fila}" data-columna="${columna}">Imprimir pasaje</button>
            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>
        </div>`;
    }

    abrir_modal_apilado('Ver pasaje', html);

    const btnImprimir = document.getElementById('btn_imprimir_pasaje_asiento');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', function() {
            const modo = this.dataset.modo;
            if (modo === 'venta') {
                const id = this.dataset.ventaId;
                const dni = this.dataset.dni;
                window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id}&dni=${dni}`, '_blank');
            } else {
                const nd = this.dataset.dueno;
                const nv = this.dataset.viaje;
                const nm = this.dataset.micro;
                const fila = this.dataset.fila;
                const columna = this.dataset.columna;
                window.open(
                    `index.php?imprimir=1&tipo=pasaje_reserva`
                    + `&dueno=${encodeURIComponent(nd)}`
                    + `&viaje=${encodeURIComponent(nv)}`
                    + `&micro=${encodeURIComponent(nm)}`
                    + `&fila=${fila}&columna=${columna}`,
                    '_blank'
                );
            }
        });
    }

    const btnCerrar = document.getElementById('btn_cerrar_ver_pasaje');
    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrar_modal_apilado);
    }
}

/**
 * "Ver compra" desde la tarjeta de asiento: navega a la pestaña Vendidos
 * y resalta la tarjeta de la venta indicada. La lógica de la navegación
 * vive en ventas.js (función ir_a_venta_en_vendidos).
 */
function ver_compra_asiento(venta_id) {
    ir_a_venta_en_vendidos(venta_id);
}

/**
 * Muestra el modal chico flotante para imprimir el pasaje de un asiento
 * reservado para el equipo. Se invoca después de reservar un asiento con
 * datos del pasajero, o después de asignar un pasajero a una reserva
 * existente. Al cerrar, el modal grande ya está cerrado y el usuario ve
 * el detalle del viaje debajo.
 *
 * Recibe todos los datos como parámetros explícitos porque el cierre del
 * modal grande (cerrar_modal_generico) limpia micro_seleccionado y
 * microSyncActual; si leyéramos del estado global acá, la URL saldría sin
 * el micro y la impresión fallaría con "Viaje no encontrado".
 *
 * @param {string} nombre_dueno
 * @param {string} nombre_viaje
 * @param {string} nombre_micro
 * @param {string} fila
 * @param {string} columna
 * @param {string|number} numero_asiento
 */
function mostrar_modal_chico_impresion_reserva(nombre_dueno, nombre_viaje, nombre_micro, fila, columna, numero_asiento) {
    const contenedor = document.getElementById('modal_chico_impresion_reserva');
    const titulo = document.getElementById('modal_chico_impresion_reserva_titulo');
    const btnImprimir = document.getElementById('btn_modal_chico_imprimir_reserva');
    const btnCerrar = document.getElementById('btn_modal_chico_cerrar_reserva');
    if (!contenedor || !titulo || !btnImprimir || !btnCerrar) return;

    titulo.textContent = `Asiento ${numero_asiento} reservado para el equipo`;

    btnImprimir.onclick = () => {
        const url = `index.php?imprimir=1&tipo=pasaje_reserva`
            + `&dueno=${encodeURIComponent(nombre_dueno)}`
            + `&viaje=${encodeURIComponent(nombre_viaje)}`
            + `&micro=${encodeURIComponent(nombre_micro)}`
            + `&fila=${encodeURIComponent(fila)}`
            + `&columna=${encodeURIComponent(columna)}`;
        window.open(url, '_blank');
    };

    btnCerrar.onclick = () => {
        contenedor.classList.add('hidden');
    };

    contenedor.classList.remove('hidden');
}

/**
 * Liberar reserva de un asiento. Si tiene pasajero asignado, avisa que
 * se va a perder la asignación antes de continuar.
 */
async function liberar_reserva_equipo(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;
    if (operacion_asiento_en_curso) return;

    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
    if (!asiento) {
        mostrar_aviso("Asiento no encontrado", 'error');
        return;
    }

    let mensaje = `¿Liberar la reserva del asiento ${asiento.numero}?`;
    if (asiento.tiene_pasajero) {
        mensaje = `El asiento ${asiento.numero} tiene un pasajero asignado. Si liberás la reserva, también se pierde la asignación del pasajero.\n\n¿Continuar?`;
    }
    if (!confirm(mensaje)) return;

    operacion_asiento_en_curso = true;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const nombre_micro = micro_seleccionado;
    detener_sync_asientos();

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/liberar_reserva_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            mostrar_aviso("Reserva liberada correctamente", 'exito');
            const asientoLocal = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asientoLocal) {
                asientoLocal.estado = 'libre';
                asientoLocal.reservado_por = null;
                asientoLocal.pasajero = null;
                asientoLocal.tiene_pasajero = false;
            }
            actualizar_colores_asientos(estados_asientos_actuales);
            refrescar_info_asientos_propios(true);
        } else {
            mostrar_aviso(resultado.error || "No se pudo liberar la reserva", 'error');
            await solicitar_estado_asientos();
        }
    } catch (error) {
        console.error("Error al liberar reserva:", error);
        mostrar_aviso("Error de comunicación", 'error');
        await solicitar_estado_asientos();
    } finally {
        operacion_asiento_en_curso = false;
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

/**
 * Reserva un asiento para el equipo (dueño). Sin pasajero asociado. Se
 * mantiene por compatibilidad interna por si se usa programáticamente.
 * El flujo interactivo ahora pasa por abrir_modal_reservar_asiento.
 */
async function reservar_asiento_equipo(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;
    if (operacion_asiento_en_curso) return;
    operacion_asiento_en_curso = true;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const nombre_micro = micro_seleccionado;
    detener_sync_asientos();

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/reservar_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            mostrar_aviso("Asiento reservado para el equipo", 'exito');
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asiento) {
                asiento.estado = 'reservado';
                asiento.reservado_por = nombre_dueno;
                asiento.seleccionado_por = null;
            }
            actualizar_colores_asientos(estados_asientos_actuales);
            refrescar_info_asientos_propios(true);
        } else {
            mostrar_aviso(resultado.error || "No se pudo reservar el asiento", 'error');
            await solicitar_estado_asientos();
        }
    } catch (error) {
        console.error("Error al reservar:", error);
        mostrar_aviso("Error de comunicación", 'error');
        await solicitar_estado_asientos();
    } finally {
        operacion_asiento_en_curso = false;
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

function renderizar_pasaje_micro(micro) {
    const contenedorCroquis = $("#croquis_pasaje_micro");
    const contenedorFoto = $("#foto_micro_viaje");
    contenedorCroquis.innerHTML = '';
    contenedorFoto.innerHTML = '';

    window.micro_actual = micro;

    // Resetear el panel de info al abrir otro micro
    info_asiento_modo = null;
    const panelInfo = $("#info_asiento_viaje");
    if (panelInfo) {
        panelInfo.innerHTML = '';
        panelInfo.classList.add('hidden');
    }

    if (micro.foto) {
        contenedorFoto.innerHTML = `<img src="${micro.foto}" alt="Foto del micro" style="max-width:200px; max-height:200px; border-radius:8px;">`;
    }

    const configuracion = micro.configuracion;
    if (configuracion && configuracion.pisos && configuracion.pisos.length > 0) {
        configuracion.pisos.forEach((piso, index) => {
            const titulo = document.createElement('div');
            titulo.className = 'section-title';
            titulo.textContent = `Piso ${index + 1}`;
            contenedorCroquis.appendChild(titulo);

            const busDiv = document.createElement('div');
            busDiv.className = 'bus';
            busDiv.innerHTML = `<div class="bus-front">FRENTE · CONDUCTOR</div>`;

            for (let fila = 1; fila <= piso.filas; fila++) {
                const filaDiv = document.createElement('div');
                filaDiv.className = 'seat-row';
                for (let col = 1; col <= piso.columnas; col++) {
                    const asiento = piso.asientos.find(a => parseInt(a.fila) === fila && parseInt(a.columna) === col);
                    if (asiento) {
                        const seat = document.createElement('div');
                        seat.className = `seat seat-${asiento.estado}`;
                        seat.textContent = String(asiento.numero).padStart(2, '0');
                        seat.dataset.fila = asiento.fila;
                        seat.dataset.columna = asiento.columna;
                        seat.dataset.numero = asiento.numero;
                        seat.dataset.estado = asiento.estado;
                        seat.dataset.reservado_por = asiento.reservado_por || '';
                        filaDiv.appendChild(seat);
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'aisle';
                        filaDiv.appendChild(empty);
                    }
                }
                busDiv.appendChild(filaDiv);
            }
            busDiv.innerHTML += `<div class="bus-back">PARTE TRASERA</div>`;
            contenedorCroquis.appendChild(busDiv);
        });

        contenedorCroquis.onclick = async (event) => {
            if (venta_form_abierto || operacion_asiento_en_curso) return;
            const seat = event.target.closest('.seat');
            if (!seat) return;
            const fila = seat.dataset.fila;
            const columna = seat.dataset.columna;
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (!asiento) return;

            const es_terminal = usuario_actual.nivel === 'terminal';

            if (es_terminal) {
                const es_propio_clic = es_asiento_propio(asiento);
                const es_libre_clic = asiento.estado === 'libre';
                const propios = obtener_asientos_propios();
                if (!es_propio_clic && !es_libre_clic && propios.length > 0) {
                    await deseleccionar_todos_los_propios();
                }
            }

            mostrar_info_asiento(asiento);

            if (es_terminal) {
                if (asiento.estado === 'reservado') {
                    mostrar_aviso("Asiento reservado para el equipo", 'info');
                    return;
                }
                if (asiento.estado === 'libre') {
                    seleccionar_asiento_pasaje(fila, columna);
                } else if (asiento.estado === 'seleccionado' && asiento.seleccionado_por === usuario_actual.nombre_usuario) {
                    deseleccionar_asiento_pasaje(fila, columna);
                }
            }
        };
    } else {
        contenedorCroquis.innerHTML = '<p>No hay configuración de asientos.</p>';
    }

    $("#pasaje_micro_viaje").classList.remove("hidden");
    micro_seleccionado = micro.nombre_micro;
    iniciar_sync_asientos();

    const leyendaHTML = `
        <div class="legend" style="margin-top:15px;">
            <div class="legend-item"><span class="swatch sw-free"></span> Libre</div>
            <div class="legend-item"><span class="swatch sw-selected"></span> Seleccionado</div>
            <div class="legend-item"><span class="swatch sw-sold"></span> Vendido</div>
            <div class="legend-item"><span class="swatch sw-reserved"></span> Reservado</div>
        </div>
    `;
    contenedorCroquis.insertAdjacentHTML('beforeend', leyendaHTML);
}

/**
 * Manejador del botón "Reiniciar selección" del panel de pasaje.
 */
async function reiniciar_seleccion_propia() {
    if (operacion_asiento_en_curso) return;

    const propios = obtener_asientos_propios();
    if (propios.length === 0) {
        mostrar_aviso("No hay asientos seleccionados", 'info');
        return;
    }

    const texto = (usuario_actual.nivel === 'terminal')
        ? "¿Liberar todos los asientos seleccionados?"
        : "¿Liberar todas las reservas del equipo?";
    if (!confirm(texto)) return;

    operacion_asiento_en_curso = true;
    detener_sync_asientos();

    try {
        await deseleccionar_todos_los_propios();
        const panel = $("#info_asiento_viaje");
        if (panel) {
            panel.innerHTML = '';
            panel.classList.add('hidden');
        }
        info_asiento_modo = null;
        mostrar_aviso("Selección liberada", 'exito');
    } catch (e) {
        console.error("Error al reiniciar selección:", e);
        mostrar_aviso("Error al liberar los asientos", 'error');
    } finally {
        operacion_asiento_en_curso = false;
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

/**
 * Normaliza un DNI dejando solo dígitos. Respaldo por si el backend no envía
 * dni_visible. Se define también en pasajeros.js; como ambos archivos cargan
 * siempre juntos, el nombre de la función debe coincidir. Si ya está definida,
 * no se redefine.
 */
if (typeof normalizar_dni_js !== 'function') {
    window.normalizar_dni_js = function(dni) {
        return String(dni || '').replace(/\D+/g, '');
    };
}

// Listeners globales de actividad
document.addEventListener('mousemove', registrar_actividad_usuario);
document.addEventListener('keydown', registrar_actividad_usuario);
document.addEventListener('click', registrar_actividad_usuario);
document.addEventListener('touchstart', registrar_actividad_usuario);