/***
 * Asientos y pasaje del micro.
 * @version 1.5piloto.35
 */

// Modo actual del panel #info_asiento_viaje.
// Valores: null (oculto), 'propios' (grilla de propios), 'simple' (una sola tarjeta ajena/libre/vendida).
let info_asiento_modo = null;

// Polling adaptativo por inactividad.
// SYNC_INTERVALO_MS: cada cuánto pedir el estado de asientos.
// SYNC_MAX_SIN_ACTIVIDAD: cuántos pedidos seguidos sin actividad del usuario
// se permiten antes de pausar el polling. Al llegar al límite, se detiene
// el intervalo y se muestra un cartel hasta que el usuario haga algo.
const SYNC_INTERVALO_MS = 15000;
const SYNC_MAX_SIN_ACTIVIDAD = 10;
let sync_contador_sin_actividad = 0;
let sync_asientos_pausado = false;

function obtener_dueno_viaje_seleccionado() {
    // Siempre que haya viaje seleccionado con dueño definido, esa es la fuente
    // de verdad. Así admin, dueño y terminal coinciden, y no dependemos de
    // que el selector de dueño esté cargado.
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
        // Refrescar el panel: si el modo es 'propios', actualizarlo; si es
        // null (primera sincronización tras abrir el micro), forzar el
        // renderizado para que aparezca la grilla si hay propios.
        refrescar_info_asientos_propios(info_asiento_modo === null);
    }

    // Contar pedido y evaluar pausa por inactividad.
    sync_contador_sin_actividad++;
    if (sync_contador_sin_actividad >= SYNC_MAX_SIN_ACTIVIDAD && !sync_asientos_pausado) {
        pausar_sync_por_inactividad();
    }
}

/**
 * Pausa el polling por inactividad. Detiene el intervalo pero mantiene
 * el estado del micro abierto (microSyncActual, panel). Muestra el cartel.
 */
function pausar_sync_por_inactividad() {
    if (intervaloSyncAsientos) {
        clearInterval(intervaloSyncAsientos);
        intervaloSyncAsientos = null;
    }
    sync_asientos_pausado = true;
    mostrar_cartel_inactividad();
}

/**
 * Reanuda el polling tras detectar actividad del usuario.
 * Se llama desde el listener global de actividad.
 */
function reanudar_sync_por_actividad() {
    if (!sync_asientos_pausado) return;
    if (!viaje_seleccionado || !micro_seleccionado) return;

    sync_asientos_pausado = false;
    sync_contador_sin_actividad = 0;
    ocultar_cartel_inactividad();
    solicitar_estado_asientos();
    intervaloSyncAsientos = setInterval(solicitar_estado_asientos, SYNC_INTERVALO_MS);
}

/**
 * Registra actividad del usuario. Se llama desde el listener global.
 * Resetea el contador y, si el polling estaba pausado, lo reanuda.
 */
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
    // El refresco del panel se maneja desde cada operación y desde
    // solicitar_estado_asientos, para no pisar el modo 'simple'.
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
 * - Para terminal: seleccionado por el propio usuario.
 * - Para dueño/admin: reservado por el propio dueño.
 */
function es_asiento_propio(asiento) {
    if (!asiento) return false;
    if (usuario_actual.nivel === 'terminal') {
        return asiento.estado === 'seleccionado' && asiento.seleccionado_por === usuario_actual.nombre_usuario;
    }
    if (usuario_actual.nivel === 'dueno' || usuario_actual.nivel === 'admin') {
        // El backend no devuelve reservado_por, pero solo el dueño del viaje
        // puede reservar asientos para el equipo. Cualquier asiento en estado
        // reservado es del equipo.
        return asiento.estado === 'reservado';
    }
    return false;
}

/**
 * Devuelve todos los asientos "propios" del usuario actual en el micro.
 */
function obtener_asientos_propios() {
    return estados_asientos_actuales.filter(es_asiento_propio);
}

/**
 * Deselecciona (o libera, según el rol) todos los asientos propios del usuario
 * actual en el micro.
 *
 * - Terminal: llama a `viajes/deseleccionar_asiento` por cada uno.
 * - Dueño/admin: llama a `viajes/liberar_reserva_asiento` por cada uno.
 *
 * Los errores se registran pero no abortan el bucle: se intenta deseleccionar
 * todos. Al final se llama a `actualizar_colores_asientos` para repintar.
 *
 * Se usa cuando el usuario hace click en un asiento ajeno teniendo propios
 * activos: hay que soltar los propios para no mezclar selecciones.
 */
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
 * No incluye el título general del panel; eso lo maneja renderizar_tarjetas_asientos.
 *
 * Muestra solo los campos clave:
 * - Número de asiento
 * - Estado
 * - Quién lo seleccionó / reservó (si aplica y no es el propio usuario)
 * - Datos del pasajero (nombre, DNI, celular) si está vendido
 * - Botones según el rol y el estado (dueño: reservar/liberar)
 */
function construir_html_tarjeta_asiento(asiento) {
    const es_propio = es_asiento_propio(asiento);
    const es_terminal = usuario_actual.nivel === 'terminal';

    let html = `<div class="asiento-card">`;
    html += `<div class="asiento-card-header">Asiento ${asiento.numero}</div>`;

    // Texto y color del estado.
    // Para asientos propios de terminal: "SELECCIONADO POR TI" en verde.
    // Para el resto: el estado tal cual.
    if (es_propio && es_terminal) {
        html += `<div class="asiento-card-estado status-propio">SELECCIONADO POR TI</div>`;
    } else {
        html += `<div class="asiento-card-estado status-${asiento.estado}">${String(asiento.estado).toUpperCase()}</div>`;
    }

    if (asiento.seleccionado_por && asiento.seleccionado_por !== usuario_actual.nombre_usuario) {
        html += `<div class="asiento-card-linea"><span>Seleccionado por:</span><b>${asiento.seleccionado_por}</b></div>`;
    }
    // El backend no devuelve reservado_por, así que esta línea casi nunca
    // aparece. Se conserva por si en el futuro se agrega el campo al estado.
    if (asiento.reservado_por) {
        html += `<div class="asiento-card-linea"><span>Reservado por:</span><b>${asiento.reservado_por}</b></div>`;
    }

    if (asiento.pasajero && typeof asiento.pasajero === 'object') {
        const nombre = asiento.pasajero.nombre || '';
        const dni = asiento.pasajero.dni || '';
        const celular = asiento.pasajero.celular || '';
        if (nombre) html += `<div class="asiento-card-linea"><span>Pasajero:</span><b>${nombre}</b></div>`;
        if (dni) html += `<div class="asiento-card-linea"><span>DNI:</span><b>${dni}</b></div>`;
        if (celular) html += `<div class="asiento-card-linea"><span>Celular:</span><b>${celular}</b></div>`;
    }

    // Botones según rol y estado (solo dueño/admin puede reservar/liberar).
    if (usuario_actual.nivel === 'dueno' || usuario_actual.nivel === 'admin') {
        if (asiento.estado === 'libre') {
            html += `<div class="asiento-card-acciones"><button class="btn primary btn-reservar-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Reservar para equipo</button></div>`;
        } else if (asiento.estado === 'reservado') {
            html += `<div class="asiento-card-acciones"><button class="btn danger btn-liberar-reserva" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar reserva</button></div>`;
        }
    }

    html += `</div>`;
    return html;
}

/**
 * Renderiza el panel de info con una lista de tarjetas en grilla de 2 columnas.
 *
 * @param {Array} asientos Lista de asientos a mostrar.
 * @param {string} titulo Título del panel.
 * @param {string} modo Modo del panel: 'propios' o 'simple'. Se guarda para
 *                      decidir si se refresca automáticamente en cada sync.
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

    // Listeners de botones (dentro del panel).
    panel.querySelectorAll('.btn-reservar-asiento').forEach(btn => {
        btn.addEventListener('click', () => reservar_asiento_equipo(btn.dataset.fila, btn.dataset.columna));
    });
    panel.querySelectorAll('.btn-liberar-reserva').forEach(btn => {
        btn.addEventListener('click', () => liberar_reserva_equipo(btn.dataset.fila, btn.dataset.columna));
    });
}

/**
 * Refresca el panel si está mostrando la grilla de propios.
 * Se llama después de cada operación y en cada sync de asientos.
 * Si el panel está en modo 'simple' (mostrando un asiento ajeno/libre),
 * no lo pisa.
 */
function refrescar_info_asientos_propios(forzar = false) {
    // El candado solo aplica a los refrescos automáticos (sync periódico).
    // Cuando el llamador es una operación en curso y pide refresco forzado,
    // hay que dejarlo pasar: es la propia operación la que quiere actualizar
    // el panel con el resultado de lo que acaba de hacer.
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

/**
 * Muestra la info de un asiento.
 *
 * - Si el asiento es propio (seleccionado/reservado por el usuario actual),
 *   muestra TODOS los asientos propios en grilla.
 * - Si el asiento es ajeno (otra terminal, vendido, reservado por otro, libre),
 *   muestra solo ese asiento.
 */
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

async function liberar_reserva_equipo(fila, columna) {
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
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asiento) {
                asiento.estado = 'libre';
                asiento.reservado_por = null;
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

        // Se asigna con .onclick (no addEventListener) para que cada
        // rerenderizado del micro REEMPLACE el handler anterior. Con
        // addEventListener se acumulaban listeners sobre el mismo
        // contenedor y un solo click disparaba el handler varias veces.
        contenedorCroquis.onclick = async (event) => {
            if (venta_form_abierto || operacion_asiento_en_curso) return;
            const seat = event.target.closest('.seat');
            if (!seat) return;
            const fila = seat.dataset.fila;
            const columna = seat.dataset.columna;
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (!asiento) return;

            const es_terminal = usuario_actual.nivel === 'terminal';

            // Terminal: si tiene asientos propios y toca uno ajeno
            // (reservado por el equipo, seleccionado por otra terminal,
            // vendido o no disponible), primero suelta los propios.
            // Un asiento libre no dispara la limpieza: se agrega a la
            // selección actual.
            // Dueño/admin: no se limpia nada. Los reservados se liberan
            // solo uno a uno desde la tarjeta del asiento.
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
 * Pide confirmación, deselecciona todos los propios y actualiza la UI.
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
        // Vaciar el panel de info (ya no hay propios).
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

// Listeners globales de actividad del usuario. Se registran una sola vez
// al cargar el archivo. Cualquier movimiento/click/tecla reanuda el polling
// si estaba pausado por inactividad.
document.addEventListener('mousemove', registrar_actividad_usuario);
document.addEventListener('keydown', registrar_actividad_usuario);
document.addEventListener('click', registrar_actividad_usuario);
document.addEventListener('touchstart', registrar_actividad_usuario);