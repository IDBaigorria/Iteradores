/***
 * Funciones de viajes, pasajes, selección de asientos y sincronización.
 * @version 1.5piloto.15
 */

function obtener_nombre_dueno_actual() {
    return usuario_actual.nivel === 'admin' ? $("#selector_dueno_viajes").value : usuario_actual.nombre_usuario;
}

async function cargar_viajes() {
    const panel_dueno = $("#panel_selector_dueno_viajes");
    const boton_agregar = $("#boton_agregar_viaje");
    const lista = $("#lista_viajes");
    lista.innerHTML = '';
    ocultar_detalle_viaje();
    if (usuario_actual.nivel === 'admin') {
        panel_dueno.style.display = 'block';
        boton_agregar.style.display = 'none';
        await cargar_duenos_en_select_viajes();
    } else if (usuario_actual.nivel === 'dueno') {
        panel_dueno.style.display = 'none';
        boton_agregar.style.display = 'inline-block';
        await listar_viajes(usuario_actual.nombre_usuario, 'dueno');
    } else if (usuario_actual.nivel === 'terminal') {
        panel_dueno.style.display = 'none';
        boton_agregar.style.display = 'none';
        await listar_viajes(usuario_actual.nombre_usuario, 'terminal');
    }
}

async function cargar_duenos_en_select_viajes() {
    const select = $("#selector_dueno_viajes");
    select.innerHTML = '<option value="">Seleccione dueño...</option>';
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_duenos" })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        datos.duenos.forEach(dueno => {
            const opcion = document.createElement('option');
            opcion.value = dueno.nombre_usuario;
            opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;
            select.appendChild(opcion);
        });
        select.onchange = async () => {
            ocultar_detalle_viaje();
            if (select.value) {
                await listar_viajes(select.value, 'dueno');
                $("#boton_agregar_viaje").style.display = 'inline-block';
            } else {
                $("#lista_viajes").innerHTML = '';
                $("#boton_agregar_viaje").style.display = 'none';
            }
        };
    }
}

function ocultar_detalle_viaje() {
    $("#detalle_viaje").classList.add("hidden");
    $("#formulario_agregar_micro").classList.add("hidden");
    $("#formulario_agregar_terminal").classList.add("hidden");
    viaje_seleccionado = null;
    micro_seleccionado = null;
    $("#croquis_pasaje_micro").innerHTML = '';
    $("#foto_micro_viaje").innerHTML = '';
    $("#pasaje_micro_viaje").classList.add("hidden");
    $("#info_asiento_viaje").classList.add("hidden");
    $("#info_asiento_viaje").innerHTML = '';
    detener_sync_asientos();
}

async function listar_viajes(nombre, tipo) {
    let accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
    let param = tipo === 'dueno' ? { nombre_dueno: nombre } : { nombre_terminal: nombre };
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion, ...param })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        renderizar_viajes(datos.viajes);
    } else {
        mostrar_aviso(datos.error || "Error al cargar viajes", 'error');
    }
}

function renderizar_viajes(viajes) {
    const lista = $("#lista_viajes");
    lista.innerHTML = '';
    viajes.forEach(viaje => {
        const div = document.createElement('div');
        div.className = 'viaje-card';
        div.innerHTML = `
            <div class="viaje-info">
                <strong>${viaje.nombre}</strong>
                <span>${viaje.fecha} ${viaje.hora}</span>
                <span>${viaje.origen} → ${viaje.destino}</span>
            </div>
            <div class="viaje-stats">
                <span>Ocupación: ${viaje.ocupacion}</span>
                <span>Disponibles: ${viaje.disponibles}</span>
                <span>Seleccionados: ${viaje.seleccionados}</span>
                <span>Vendidos: ${viaje.vendidos}</span>
            </div>
            <button class="btn btn-detalle-viaje" data-viaje="${viaje.nombre_viaje}">Ver detalle</button>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-viaje" data-viaje="${viaje.nombre_viaje}">Eliminar</button>` : ''}
        `;
        lista.appendChild(div);

        div.querySelector('.btn-detalle-viaje').addEventListener('click', () => ver_detalle_viaje(viaje));
        const btnEliminar = div.querySelector('.btn-eliminar-viaje');
        if (btnEliminar) btnEliminar.addEventListener('click', () => eliminar_viaje(viaje.nombre_viaje));
    });
}

function ver_detalle_viaje(viaje) {
    ocultar_detalle_viaje();

    viaje_seleccionado = viaje;
    $("#detalle_viaje_titulo").textContent = viaje.nombre;
    $("#detalle_viaje").classList.remove('hidden');

    if (usuario_actual.nivel === 'terminal') {
        $("#boton_agregar_micro_viaje").style.display = 'none';
        $("#boton_agregar_terminal_viaje").style.display = 'none';
        $("#seccion_terminales_viaje").style.display = 'none';
    } else {
        $("#boton_agregar_micro_viaje").style.display = '';
        $("#boton_agregar_terminal_viaje").style.display = '';
        $("#seccion_terminales_viaje").style.display = '';
    }

    renderizar_micros_viaje(viaje.micros);
    if (usuario_actual.nivel !== 'terminal') {
        renderizar_terminales_viaje(viaje.terminales_autorizadas);
    } else {
        $("#lista_terminales_viaje").innerHTML = '';
    }
}

function renderizar_micros_viaje(micros) {
    const contenedor = $("#lista_micros_viaje");
    contenedor.innerHTML = '';
    micros.forEach(micro => {
        const div = document.createElement('div');
        div.className = 'micro-item';
        div.innerHTML = `
            <span>${micro.empresa} - ${micro.patente}</span>
            <span>Ocupación: ${micro.ocupacion}</span>
            <span>Vendidos: ${micro.vendidos}</span>
            <span>Monto: $${micro.monto ?? '0'}</span>
            <button class="btn btn-ver-pasaje" data-micro="${micro.nombre_micro}">Ver pasaje</button>
            ${usuario_actual.nivel !== 'terminal' ? `
                <button class="btn btn-editar-monto" data-micro="${micro.nombre_micro}" data-monto="${micro.monto ?? '0'}">Editar monto</button>
                <button class="btn btn-eliminar-micro" data-micro="${micro.nombre_micro}">Quitar</button>
            ` : ''}
        `;
        contenedor.appendChild(div);

        div.querySelector('.btn-ver-pasaje').addEventListener('click', () => seleccionar_micro_viaje(micro.nombre_micro));
        const btnEditarMonto = div.querySelector('.btn-editar-monto');
        if (btnEditarMonto) btnEditarMonto.addEventListener('click', () => editar_monto_micro(micro.nombre_micro, micro.monto ?? '0'));
        const btnQuitar = div.querySelector('.btn-eliminar-micro');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_micro(micro.nombre_micro));
    });
}

function editar_monto_micro(nombre_micro, monto_actual) {
    const nuevo_monto = prompt("Nuevo monto del pasaje:", monto_actual);
    if (nuevo_monto === null) return;
    if (nuevo_monto.trim() === '' || isNaN(parseFloat(nuevo_monto)) || parseFloat(nuevo_monto) < 0) {
        mostrar_aviso("Monto inválido", 'error');
        return;
    }

    const nombre_dueno = obtener_nombre_dueno_actual();
    fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/actualizar_monto_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            monto: nuevo_monto.trim(),
            nombre_dueno
        })
    })
    .then(resp => resp.json())
    .then(resultado => {
        if (resultado.exito) {
            mostrar_aviso("Monto actualizado", 'exito');
            actualizar_detalle_viaje_actual();
        } else {
            mostrar_aviso(resultado.error || "Error al actualizar monto", 'error');
        }
    });
}

async function seleccionar_micro_viaje(nombre_micro) {
    micro_seleccionado = nombre_micro;

    let nombre_dueno;
    if (usuario_actual.nivel === 'terminal') {
        nombre_dueno = viaje_seleccionado.dueno;
    } else {
        nombre_dueno = obtener_nombre_dueno_actual();
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/obtener_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (datos.exito && datos.micro) {
        renderizar_pasaje_micro(datos.micro);
        micro_seleccionado = nombre_micro;
        iniciar_sync_asientos();
    } else {
        mostrar_aviso(datos.error || "Error al obtener micro", 'error');
    }
}

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
            seat.classList.remove('seat-libre', 'seat-seleccionado', 'seat-seleccionado-propio', 'seat-vendido', 'seat-no-disponible');
            if (estadoObj.estado === 'seleccionado') {
                if (estadoObj.seleccionado_por === usuario_actual.nombre_usuario) {
                    seat.classList.add('seat-seleccionado-propio');
                } else {
                    seat.classList.add('seat-seleccionado');
                }
            } else {
                seat.classList.add(`seat-${estadoObj.estado}`);
            }
            seat.dataset.estado = estadoObj.estado;
            seat.dataset.seleccionado_por = estadoObj.seleccionado_por || '';
        }
    });
    mostrar_boton_confirmar_venta();
}

async function seleccionar_asiento_pasaje(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;
    if (operacion_asiento_en_curso) return; // evitar doble clic

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
            // Actualizar estado local directamente sin esperar polling
            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (asiento) {
                asiento.estado = 'seleccionado';
                asiento.seleccionado_por = nombre_terminal;
            }
            actualizar_colores_asientos(estados_asientos_actuales);
            mostrar_aviso("Asiento seleccionado", 'exito');
        } else {
            // Verificar si el asiento quedó seleccionado por nosotros (posible sincronización)
            const estado_actual = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (estado_actual && estado_actual.estado === 'seleccionado' && estado_actual.seleccionado_por === nombre_terminal) {
                // Realmente se seleccionó pero el servidor respondió error por carrera
                mostrar_aviso("Asiento seleccionado", 'exito');
            } else {
                mostrar_aviso(resultado.error || "No se pudo seleccionar", 'error');
                // Actualizar estados para reflejar el estado real
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
    if (asiento.pasajero) {
        html += `<h4 style="margin-top:10px;">Pasajero</h4>`;
        Object.entries(asiento.pasajero).forEach(([campo, valor]) => {
            html += `<div class="detail-line"><span>${campo}:</span><strong>${valor}</strong></div>`;
        });
    }
    if (asiento.venta) {
        html += `<div class="detail-line" style="margin-top:10px;"><span>Venta:</span><strong>Registrada (detalles próximamente)</strong></div>`;
    }

    panel.innerHTML = html;
    panel.classList.remove('hidden');
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
}

function renderizar_terminales_viaje(terminales) {
    const contenedor = $("#lista_terminales_viaje");
    contenedor.innerHTML = '';
    terminales.forEach(terminal => {
        const div = document.createElement('div');
        div.className = 'terminal-item';
        div.innerHTML = `
            <span>${terminal}</span>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-terminal" data-terminal="${terminal}">Quitar</button>` : ''}
        `;
        contenedor.appendChild(div);

        const btnQuitar = div.querySelector('.btn-eliminar-terminal');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_terminal_autorizada(terminal));
    });
}

// Eventos de formularios de viaje
$("#boton_agregar_viaje").addEventListener("click", () => {
    $("#formulario_nuevo_viaje").classList.remove("hidden");
});

$("#boton_guardar_viaje").addEventListener("click", async () => {
    const nombre_dueno = obtener_nombre_dueno_actual();
    const datos = {
        accion: "viajes/agregar",
        nombre_dueno,
        nombre_viaje: $("#nuevo_viaje_nombre").value.trim(),
        nombre: $("#nuevo_viaje_nombre").value.trim(),
        fecha: $("#nuevo_viaje_fecha").value,
        hora: $("#nuevo_viaje_hora").value,
        origen: $("#nuevo_viaje_origen").value,
        destino: $("#nuevo_viaje_destino").value
    };
    if (!datos.nombre_viaje) {
        mostrar_aviso("El nombre del viaje es obligatorio", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Viaje creado", 'exito');
        $("#formulario_nuevo_viaje").classList.add("hidden");
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al crear viaje", 'error');
    }
});

$("#boton_cancelar_viaje").addEventListener("click", () => {
    $("#formulario_nuevo_viaje").classList.add("hidden");
});

async function eliminar_viaje(nombre_viaje) {
    if (!confirm(`¿Eliminar viaje ${nombre_viaje}?`)) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "viajes/eliminar", nombre_viaje, nombre_dueno })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Viaje eliminado", 'exito');
        $("#detalle_viaje").classList.add("hidden");
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
}

$("#boton_agregar_micro_viaje").addEventListener("click", async () => {
    $("#formulario_agregar_micro").classList.remove("hidden");
    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectEmpresa = $("#selector_empresa_micro_viaje");
    selectEmpresa.innerHTML = '<option value="">Seleccione empresa...</option>';
    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "empresas/listar", nombre_dueno })
    });
    const datos = await resp.json();
    if (datos.exito) {
        datos.empresas.forEach(empresa => {
            const opcion = document.createElement('option');
            opcion.value = empresa.nombre_empresa;
            opcion.textContent = empresa.nombre;
            selectEmpresa.appendChild(opcion);
        });
        selectEmpresa.onchange = async () => {
            const selectVehiculo = $("#selector_vehiculo_micro_viaje");
            selectVehiculo.innerHTML = '<option value="">Seleccione vehículo...</option>';
            if (selectEmpresa.value) {
                const respV = await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa: selectEmpresa.value })
                });
                const datosV = await respV.json();
                if (datosV.exito) {
                    datosV.vehiculos.forEach(vehiculo => {
                        const opcion = document.createElement('option');
                        opcion.value = vehiculo.nombre_vehiculo;
                        opcion.textContent = vehiculo.nombre;
                        selectVehiculo.appendChild(opcion);
                    });
                }
            }
        };
    }
});

$("#boton_confirmar_micro").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micro_viaje").value;
    const nombre_vehiculo = $("#selector_vehiculo_micro_viaje").value;
    const monto = $("#monto_micro_viaje").value.trim();
    const nombre_dueno = obtener_nombre_dueno_actual();

    if (!nombre_empresa || !nombre_vehiculo) {
        mostrar_aviso("Seleccione empresa y vehículo", 'error');
        return;
    }
    if (monto === '' || isNaN(parseFloat(monto)) || parseFloat(monto) < 0) {
        mostrar_aviso("Ingrese un monto válido", 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/agregar_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_empresa,
            nombre_vehiculo,
            nombre_dueno,
            monto
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Micro agregado", 'exito');
        $("#formulario_agregar_micro").classList.add("hidden");
        $("#monto_micro_viaje").value = "";
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al agregar micro", 'error');
    }
});

$("#boton_cancelar_micro").addEventListener("click", () => {
    $("#formulario_agregar_micro").classList.add("hidden");
});

async function eliminar_micro(nombre_micro) {
    if (!confirm(`¿Quitar el micro "${nombre_micro}" del viaje?`)) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/eliminar_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Micro eliminado", 'exito');
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar micro", 'error');
    }
}

$("#boton_agregar_terminal_viaje").addEventListener("click", async () => {
    $("#formulario_agregar_terminal").classList.remove("hidden");
    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectTerminal = $("#selector_terminal_autorizada");
    selectTerminal.innerHTML = '<option value="">Seleccione punto de venta...</option>';
    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno })
    });
    const datos = await resp.json();
    if (datos.exito) {
        datos.terminales.forEach(terminal => {
            const opcion = document.createElement('option');
            opcion.value = terminal.nombre_usuario;
            opcion.textContent = terminal.nombre_real ? `${terminal.nombre_real} (${terminal.nombre_usuario})` : terminal.nombre_usuario;
            selectTerminal.appendChild(opcion);
        });
    }
});

$("#boton_confirmar_terminal").addEventListener("click", async () => {
    const nombre_terminal = $("#selector_terminal_autorizada").value;
    const nombre_dueno = obtener_nombre_dueno_actual();
    if (!nombre_terminal) {
        mostrar_aviso("Seleccione un punto de venta", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/agregar_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta autorizado", 'exito');
        $("#formulario_agregar_terminal").classList.add("hidden");
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al autorizar punto de venta", 'error');
    }
});

$("#boton_cancelar_terminal_viaje").addEventListener("click", () => {
    $("#formulario_agregar_terminal").classList.add("hidden");
});

async function eliminar_terminal_autorizada(nombre_terminal) {
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/eliminar_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta eliminado", 'exito');
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar punto de venta", 'error');
    }
}

async function actualizar_detalle_viaje_actual() {
    if (!viaje_seleccionado) return;

    const nombre_viaje_actual = viaje_seleccionado.nombre_viaje;
    ocultar_detalle_viaje();

    let nombre_dueno = obtener_nombre_dueno_actual();
    let tipo = usuario_actual.nivel === 'terminal' ? 'terminal' : 'dueno';
    let accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
    let param = tipo === 'dueno' ? { nombre_dueno } : { nombre_terminal: usuario_actual.nombre_usuario };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion, ...param })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === nombre_viaje_actual);
        if (viajeActualizado) {
            ver_detalle_viaje(viajeActualizado);
        }
    }
}