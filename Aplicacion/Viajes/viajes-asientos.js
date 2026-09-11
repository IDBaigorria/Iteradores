/***
 * Asientos y pasaje del micro.
 * @version 1.5piloto.27
 */

function obtener_dueno_viaje_seleccionado() {
    if (usuario_actual.nivel === 'terminal' && viaje_seleccionado && viaje_seleccionado.dueno) {
        return viaje_seleccionado.dueno;
    }
    return obtener_nombre_dueno_actual();
}

function iniciar_sync_asientos() {
    detener_sync_asientos();
    if (!viaje_seleccionado || !micro_seleccionado) return;

    microSyncActual = micro_seleccionado;
    solicitar_estado_asientos();
    intervaloSyncAsientos = setInterval(solicitar_estado_asientos, 10000);
}

function detener_sync_asientos() {
    if (intervaloSyncAsientos) {
        clearInterval(intervaloSyncAsientos);
        intervaloSyncAsientos = null;
    }
    microSyncActual = null;
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
    }
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

function mostrar_info_asiento(asiento) {
    const panel = $("#info_asiento_viaje");
    let html = `
        <div class="section-title">Información de asiento</div>
        <div class="detail-line"><span>Número:</span><strong>${asiento.numero}</strong></div>
        <div class="detail-line"><span>Fila/Col:</span><strong>${asiento.fila} - ${asiento.columna}</strong></div>
        <div class="detail-line"><span>Estado:</span><strong class="status-${asiento.estado}">${asiento.estado.toUpperCase()}</strong></div>
    `;
    if (asiento.seleccionado_por) {
        html += `<div class="detail-line"><span>Seleccionado por:</span><strong>${asiento.seleccionado_por}</strong></div>`;
    }
    if (asiento.reservado_por) {
        html += `<div class="detail-line"><span>Reservado por:</span><strong>${asiento.reservado_por}</strong></div>`;
    }
    if (asiento.pasajero) {
        html += `<h4 style="margin-top:10px;">Pasajero</h4>`;
        Object.entries(asiento.pasajero).forEach(([campo, valor]) => {
            html += `<div class="detail-line"><span>${campo}:</span><strong>${valor}</strong></div>`;
        });
    }
    if (asiento.venta) {
        html += `<div class="detail-line" style="margin-top:10px;"><span>Venta:</span><strong>Registrada (detalles próximamente)</strong></div>`;
    }

    if (usuario_actual.nivel === 'dueno') {
        if (asiento.estado === 'libre') {
            html += `<div class="actions" style="margin-top:15px;"><button class="btn primary" id="btn_reservar_equipo">Reservar para equipo</button></div>`;
        } else if (asiento.estado === 'reservado') {
            html += `<div class="actions" style="margin-top:15px;"><button class="btn danger" id="btn_liberar_reserva">Liberar reserva</button></div>`;
        }
    }

    panel.innerHTML = html;
    panel.classList.remove('hidden');

    const botonReservar = $("#btn_reservar_equipo");
    if (botonReservar) {
        botonReservar.addEventListener('click', () => reservar_asiento_equipo(asiento.fila, asiento.columna));
    }
    const botonLiberar = $("#btn_liberar_reserva");
    if (botonLiberar) {
        botonLiberar.addEventListener('click', () => liberar_reserva_equipo(asiento.fila, asiento.columna));
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

        contenedorCroquis.addEventListener('click', (event) => {
            if (venta_form_abierto || operacion_asiento_en_curso) return;
            const seat = event.target.closest('.seat');
            if (!seat) return;
            const fila = seat.dataset.fila;
            const columna = seat.dataset.columna;
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (!asiento) return;

            mostrar_info_asiento(asiento);

            if (usuario_actual.nivel === 'terminal') {
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
        });
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