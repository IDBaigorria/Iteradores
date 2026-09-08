/***
 * Micros y terminales dentro de viajes.
 * @version 1.5piloto.26
 */

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

// Eventos para agregar micro
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

// Eventos para agregar terminal
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