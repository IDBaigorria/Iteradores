/**
 * Micros y terminales dentro de viajes.
 * @version 1.5piloto.31
 */

function renderizar_micros_viaje(micros) {
    const contenedor = $("#lista_micros_viaje");
    contenedor.innerHTML = '';
    micros.forEach(micro => {
        const div = document.createElement('div');
        div.className = 'micro-item';
        const textoBoton = (micro_seleccionado === micro.nombre_micro && !document.getElementById('pasaje_micro_viaje')?.classList.contains('hidden')) ? 'Ocultar pasaje' : 'Ver pasaje';
        div.innerHTML = `
            <span>${micro.nombre_empresa || micro.empresa} - ${micro.nombre_vehiculo || micro.patente}</span>
            <span>Capacidad: ${micro.ocupacion}</span>
            <span>Vendidos: ${micro.vendidos}</span>
            ${usuario_actual.nivel === 'terminal' ? `<span>Vendidos aquí: ${micro.vendidos_aqui ?? '0'}</span>` : ''}
            <span>Disponibles: ${micro.disponibles ?? '0'}</span>
            ${usuario_actual.nivel !== 'terminal' ? `<span>Reservados para el equipo: ${micro.reservados ?? '0'}</span>` : ''}
            <span>Monto: $${micro.monto ?? '0'}</span>
            <button class="btn btn-ver-pasaje" data-micro="${micro.nombre_micro}">${textoBoton}</button>
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

/**
 * Cierra el panel del pasaje de micro y resetea el estado.
 */
function cerrar_pasaje_micro() {
    detener_sync_asientos();
    micro_seleccionado = null;

    const panel = document.getElementById('pasaje_micro_viaje');
    if (panel) panel.classList.add('hidden');

    const croquis = document.getElementById('croquis_pasaje_micro');
    if (croquis) croquis.innerHTML = '';

    const foto = document.getElementById('foto_micro_viaje');
    if (foto) foto.innerHTML = '';

    const info = document.getElementById('info_asiento_viaje');
    if (info) {
        info.innerHTML = '';
        info.classList.add('hidden');
    }

    venta_form_abierto = false;
    const formulario = document.getElementById('formulario_confirmacion_venta');
    if (formulario) formulario.classList.add('hidden');

    const contenedorBoton = document.getElementById('contenedor_boton_confirmar_venta');
    if (contenedorBoton) contenedorBoton.classList.add('hidden');

    actualizar_textos_botones_pasaje();
}

/**
 * Actualiza el texto de todos los botones de micro según el micro activo.
 */
function actualizar_textos_botones_pasaje() {
    document.querySelectorAll('.btn-ver-pasaje').forEach(btn => {
        const nombre = btn.dataset.micro;
        const panelVisible = !document.getElementById('pasaje_micro_viaje')?.classList.contains('hidden');
        btn.textContent = (nombre === micro_seleccionado && panelVisible) ? 'Ocultar pasaje' : 'Ver pasaje';
    });
}

async function seleccionar_micro_viaje(nombre_micro) {
    // Si ya está abierto ese micro y el panel visible, ocultamos
    if (micro_seleccionado === nombre_micro && !document.getElementById('pasaje_micro_viaje').classList.contains('hidden')) {
        cerrar_pasaje_micro();
        return;
    }

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
        actualizar_textos_botones_pasaje();
    } else {
        mostrar_aviso(datos.error || "Error al obtener micro", 'error');
    }
}

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

function renderizar_terminales_viaje(terminales) {
    const contenedor = $("#lista_terminales_viaje");
    contenedor.innerHTML = '';

    // ¿Mostrar botón "Opciones"? Solo dueño/admin, y solo si hay paradas intermedias.
    const esDuenoOAdmin = usuario_actual.nivel !== 'terminal';
    const paradas = (viaje_seleccionado && Array.isArray(viaje_seleccionado.paradas_intermedias))
        ? viaje_seleccionado.paradas_intermedias
        : [];
    const mostrarBotonOpciones = esDuenoOAdmin && paradas.length > 0;

    terminales.forEach(terminal => {
        const div = document.createElement('div');
        div.className = 'terminal-item';
        div.innerHTML = `
            <span>${terminal}</span>
            ${mostrarBotonOpciones ? `<button class="btn btn-opciones-terminal" data-terminal="${terminal}">Opciones</button>` : ''}
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-terminal" data-terminal="${terminal}">Quitar</button>` : ''}
        `;
        contenedor.appendChild(div);

        const btnOpciones = div.querySelector('.btn-opciones-terminal');
        if (btnOpciones) btnOpciones.addEventListener('click', () => abrir_modal_opciones_terminal(terminal));

        const btnQuitar = div.querySelector('.btn-eliminar-terminal');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_terminal_autorizada(terminal));
    });
}

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

// Funciones para abrir formularios y confirmar (llamadas desde viajes-nucleo.js)
async function abrir_formulario_agregar_micro() {
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
}

async function confirmar_agregar_micro() {
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
}

async function abrir_formulario_agregar_terminal() {
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
}

async function confirmar_agregar_terminal() {
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
}

/**
 * Abre el modal de opciones específicas para la combinación viaje + terminal.
 * Solo aplicable a dueño/admin (el botón no se muestra a terminales).
 *
 * @param {string} nombre_terminal Nombre de usuario de la terminal.
 */
async function abrir_modal_opciones_terminal(nombre_terminal) {
    const nombre_dueno = obtener_nombre_dueno_actual();

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/obtener_opciones_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener opciones", 'error');
        return;
    }

    const opciones = datos.opciones || { cambiar_punto_predeterminado: '0', punto_subida_bajada: '' };
    const paradas = (viaje_seleccionado && Array.isArray(viaje_seleccionado.paradas_intermedias))
        ? viaje_seleccionado.paradas_intermedias
        : [];

    // Defensivo: no debería llegarse acá sin paradas (el botón no se muestra en ese caso).
    if (paradas.length === 0) {
        mostrar_aviso("Este viaje no tiene paradas intermedias configuradas", 'error');
        return;
    }

    const cambiar_marcado = opciones.cambiar_punto_predeterminado === '1';
    const punto_actual = opciones.punto_subida_bajada || '';

    const opciones_html = paradas.map(p => {
        const sel = (p === punto_actual) ? ' selected' : '';
        const valor = String(p).replace(/"/g, '&quot;');
        return `<option value="${valor}"${sel}>${p}</option>`;
    }).join('');

    const html = `
        <h3>Opciones para "${nombre_terminal}"</h3>
        <div class="field">
            <label><input type="checkbox" id="opciones_terminal_checkbox" ${cambiar_marcado ? 'checked' : ''}> ¿Cambiar punto de subida/bajada predeterminado?</label>
        </div>
        <div class="field" id="opciones_terminal_punto_container" style="${cambiar_marcado ? '' : 'display:none;'}">
            <label>Punto de subida/bajada</label>
            <select id="opciones_terminal_select_punto">
                <option value="">Sin selección</option>
                ${opciones_html}
            </select>
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="opciones_terminal_guardar">Guardar</button>
            <button class="btn" id="opciones_terminal_cancelar">Cancelar</button>
        </div>
    `;

    abrir_modal_generico('Opciones de terminal', html);

    const checkbox = document.getElementById('opciones_terminal_checkbox');
    const contenedorPunto = document.getElementById('opciones_terminal_punto_container');
    checkbox.addEventListener('change', () => {
        contenedorPunto.style.display = checkbox.checked ? '' : 'none';
    });

    document.getElementById('opciones_terminal_cancelar').addEventListener('click', () => {
        cerrar_modal_generico();
    });

    document.getElementById('opciones_terminal_guardar').addEventListener('click', async () => {
        const cambiar = checkbox.checked ? '1' : '0';
        const punto = document.getElementById('opciones_terminal_select_punto').value;

        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/guardar_opciones_terminal",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_terminal,
                nombre_dueno,
                cambiar_punto_predeterminado: cambiar,
                punto_subida_bajada: punto
            })
        });
        const resultado = await resp.json();
        if (resultado.exito) {
            mostrar_aviso("Opciones guardadas", 'exito');
            cerrar_modal_generico();
        } else {
            mostrar_aviso(resultado.error || "Error al guardar opciones", 'error');
        }
    });
}