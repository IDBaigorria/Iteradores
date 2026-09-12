/***
 * Núcleo de viajes: carga, listado, detalle en modal y eliminación.
 * @version 1.5piloto.30
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
    detener_sync_asientos();
    viaje_seleccionado = null;
    micro_seleccionado = null;
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
        if (viaje.activo === '0') {
            div.classList.add('viaje-inactivo');
        }
        div.innerHTML = `
            <div class="viaje-info">
                <strong>${viaje.nombre}</strong>
                <span>${viaje.fecha} ${viaje.hora}</span>
                <span>${viaje.origen} → ${viaje.destino}</span>
            </div>
            <div class="viaje-stats">
                <span>Capacidad total: ${viaje.ocupacion}</span>
                ${usuario_actual.nivel === 'dueno' ? `<span>Reservado para el equipo: ${viaje.reservados}</span>` : ''}
                <span>Disponibles: ${viaje.disponibles}</span>
                <span>Seleccionados: ${viaje.seleccionados}</span>
                <span>Vendidos: ${viaje.vendidos}</span>
            </div>
            <button class="btn btn-detalle-viaje" data-viaje="${viaje.nombre_viaje}">Ver detalle</button>
            ${usuario_actual.nivel !== 'terminal' ? `
                <button class="btn btn-editar-viaje ${viaje.activo === '0' ? 'btn-disabled' : ''}" data-viaje="${viaje.nombre_viaje}" ${viaje.activo === '0' ? 'disabled' : ''}>Editar viaje</button>
                <button class="btn btn-eliminar-viaje ${viaje.tiene_ventas === '1' ? 'btn-disabled' : ''}" data-viaje="${viaje.nombre_viaje}">Eliminar</button>
            ` : ''}
        `;
        lista.appendChild(div);

        div.querySelector('.btn-detalle-viaje').addEventListener('click', () => ver_detalle_viaje(viaje));
        const btnEditar = div.querySelector('.btn-editar-viaje');
        if (btnEditar && viaje.activo !== '0') {
            btnEditar.addEventListener('click', () => abrir_modal_viaje('editar', viaje));
        }
        const btnEliminar = div.querySelector('.btn-eliminar-viaje');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => {
                if (viaje.tiene_ventas === '1') {
                    mostrar_aviso("No se pueden eliminar viajes con ventas ya realizadas", 'error');
                    return;
                }
                eliminar_viaje(viaje.nombre_viaje);
            });
        }
    });
}

async function ver_detalle_viaje(viaje) {
    detener_sync_asientos();
    viaje_seleccionado = viaje;
    micro_seleccionado = null;
    venta_form_abierto = false;
    operacion_asiento_en_curso = false;

    // Obtener datos frescos del viaje para asegurar que todos los micros estén presentes
    try {
        const nombre_dueno = obtener_nombre_dueno_actual();
        const tipo = usuario_actual.nivel === 'terminal' ? 'terminal' : 'dueno';
        const accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
        const param = tipo === 'dueno' ? { nombre_dueno } : { nombre_terminal: usuario_actual.nombre_usuario };

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion, ...param })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === viaje.nombre_viaje);
            if (viajeActualizado) {
                viaje = viajeActualizado;
                viaje_seleccionado = viaje;
            }
        }
    } catch (error) {
        console.error("Error al refrescar datos del viaje:", error);
    }

    // Normalizar paradas intermedias (siempre array)
    const paradas = Array.isArray(viaje.paradas_intermedias) ? viaje.paradas_intermedias : [];
    const tieneParadas = paradas.length > 0;

    // Fecha y hora formateadas
    const fechaTexto = viaje.fecha === 'a confirmar' || viaje.fecha === '' ? 'A confirmar' : viaje.fecha;
    const horaTexto = viaje.hora === 'a confirmar' || viaje.hora === '' ? 'A confirmar' : viaje.hora;
    const fechaPendiente = viaje.fecha === 'a confirmar' || viaje.fecha === '';
    const horaPendiente = viaje.hora === 'a confirmar' || viaje.hora === '';

    const html = `
        <h3>${viaje.nombre} <span class="badge-viaje ${viaje.activo === '1' ? 'badge-viaje-activo' : 'badge-viaje-inactivo'}">${viaje.activo === '1' ? 'Activo' : 'Inactivo'}</span></h3>

        <div class="viaje-detalle-datos">
            <div class="viaje-detalle-seccion">
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">📅 Fecha:</span>
                    <span class="${fechaPendiente ? 'dato-pendiente' : ''}">${fechaTexto}</span>
                </div>
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🕐 Hora:</span>
                    <span class="${horaPendiente ? 'dato-pendiente' : ''}">${horaTexto}</span>
                </div>
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🛣️ Ruta:</span>
                    <span>${viaje.origen} → ${viaje.destino}</span>
                </div>
                ${tieneParadas ? `
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🚏 Paradas intermedias:</span>
                    <span>${paradas.join(' · ')}</span>
                </div>
                ` : ''}
            </div>

            <div class="viaje-detalle-seccion">
                <div class="viaje-detalle-contadores">
                    <div class="contador-item"><span>Capacidad total:</span><b>${viaje.ocupacion}</b></div>
                    <div class="contador-item"><span>Disponibles:</span><b>${viaje.disponibles}</b></div>
                    <div class="contador-item"><span>Seleccionados:</span><b>${viaje.seleccionados}</b></div>
                    <div class="contador-item"><span>Vendidos:</span><b>${viaje.vendidos}</b></div>
                    ${usuario_actual.nivel !== 'terminal' ? `<div class="contador-item"><span>Reservados para el equipo:</span><b>${viaje.reservados}</b></div>` : ''}
                </div>
            </div>
        </div>

        ${usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno' ? `
            <div style="margin-bottom:15px;">
                <button class="btn" id="modal_btn_editar_viaje" ${viaje.activo === '0' ? 'disabled' : ''}>Editar viaje</button>
            </div>
        ` : ''}
        <div class="row">
            <label>Micros:</label>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn" id="boton_agregar_micro_viaje">Agregar micro</button>` : ''}
        </div>
        <div id="lista_micros_viaje"></div>

        <div id="pasaje_micro_viaje" class="panel hidden" style="margin-top:15px;">
            <h4>Pasaje del micro</h4>
            <div id="foto_micro_viaje" style="margin-bottom:10px;"></div>
            <div class="pasaje-layout" style="display:flex; flex-wrap:wrap; justify-content:center; gap:15px; align-items:flex-start; max-width:1200px; margin:0 auto;">
                <div id="croquis_pasaje_micro" style="flex:1 1 300px; min-width:300px; display:flex; flex-direction:column; align-items:center;"></div>
                <div class="columna-derecha" style="flex:1 1 250px; min-width:250px; display:flex; flex-direction:column; gap:10px;">
                    <div id="info_asiento_viaje" class="panel hidden" style="padding:10px; border:1px solid #ddd; border-radius:6px;"></div>
                    <div id="contenedor_boton_confirmar_venta" class="hidden" style="text-align:left;">
                        <button class="btn primary" id="boton_confirmar_venta">Vender</button>
                    </div>
                    <div id="formulario_confirmacion_venta" class="panel hidden" style="padding:15px; border:1px solid #ddd; border-radius:6px;"></div>
                </div>
            </div>
        </div>

        <div id="seccion_terminales_viaje" style="${usuario_actual.nivel === 'terminal' ? 'display:none;' : ''}">
            <div class="row" style="margin-top:15px;">
                <label>Terminales autorizadas:</label>
                ${usuario_actual.nivel !== 'terminal' ? `<button class="btn" id="boton_agregar_terminal_viaje">Agregar punto de venta</button>` : ''}
            </div>
            <div id="lista_terminales_viaje"></div>
        </div>

        <div id="formulario_agregar_micro" class="panel hidden" style="margin-top:15px;">
            <h3>Agregar micro</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Empresa</label>
                    <select id="selector_empresa_micro_viaje"></select>
                </div>
                <div class="field">
                    <label>Vehículo</label>
                    <select id="selector_vehiculo_micro_viaje"></select>
                </div>
                <div class="field">
                    <label>Monto del pasaje *</label>
                    <input type="number" id="monto_micro_viaje" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>
            <div class="actions" style="margin-top:12px;">
                <button class="btn primary" id="boton_confirmar_micro">Confirmar</button>
                <button class="btn" id="boton_cancelar_micro">Cancelar</button>
            </div>
        </div>

        <div id="formulario_agregar_terminal" class="panel hidden" style="margin-top:15px;">
            <h3>Agregar punto de venta autorizado</h3>
            <div class="field">
                <label>Terminal</label>
                <select id="selector_terminal_autorizada"></select>
            </div>
            <div class="actions" style="margin-top:12px;">
                <button class="btn primary" id="boton_confirmar_terminal">Confirmar</button>
                <button class="btn" id="boton_cancelar_terminal_viaje">Cancelar</button>
            </div>
        </div>
    `;

    abrir_modal_generico('Detalle del viaje', html);

    // Asignar listeners a botones generados
    const btnEditar = document.getElementById('modal_btn_editar_viaje');
    if (btnEditar && viaje.activo !== '0') {
        btnEditar.addEventListener('click', () => abrir_modal_viaje('editar', viaje));
    }

    const btnVender = document.getElementById('boton_confirmar_venta');
    if (btnVender) {
        btnVender.addEventListener('click', abrir_modal_confirmacion_venta);
    }

    document.getElementById('boton_agregar_micro_viaje')?.addEventListener('click', abrir_formulario_agregar_micro);
    document.getElementById('boton_agregar_terminal_viaje')?.addEventListener('click', abrir_formulario_agregar_terminal);
    document.getElementById('boton_confirmar_micro')?.addEventListener('click', confirmar_agregar_micro);
    document.getElementById('boton_cancelar_micro')?.addEventListener('click', () => document.getElementById('formulario_agregar_micro').classList.add('hidden'));
    document.getElementById('boton_confirmar_terminal')?.addEventListener('click', confirmar_agregar_terminal);
    document.getElementById('boton_cancelar_terminal_viaje')?.addEventListener('click', () => document.getElementById('formulario_agregar_terminal').classList.add('hidden'));

    // Renderizar contenido
    renderizar_micros_viaje(viaje.micros);
    if (usuario_actual.nivel !== 'terminal') {
        renderizar_terminales_viaje(viaje.terminales_autorizadas);
    } else {
        const listaT = document.getElementById('lista_terminales_viaje');
        if (listaT) listaT.innerHTML = '';
    }
}

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
        cerrar_modal_generico();
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
}

async function actualizar_detalle_viaje_actual() {
    if (!viaje_seleccionado) return;

    const nombre_viaje_actual = viaje_seleccionado.nombre_viaje;
    const micro_previo = micro_seleccionado;

    detener_sync_asientos();

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

            if (micro_previo) {
                const microAunExiste = viajeActualizado.micros.some(m => m.nombre_micro === micro_previo);
                if (microAunExiste) {
                    await seleccionar_micro_viaje(micro_previo);
                }
            }
        }
    }
}