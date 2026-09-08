/***
 * Núcleo de viajes: carga, listado, detalle y eliminación.
 * @version 1.5piloto.26
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
            ${usuario_actual.nivel !== 'terminal' ? `
                <button class="btn btn-editar-viaje" data-viaje="${viaje.nombre_viaje}">Editar viaje</button>
                <button class="btn btn-eliminar-viaje" data-viaje="${viaje.nombre_viaje}">Eliminar</button>
            ` : ''}
        `;
        lista.appendChild(div);

        div.querySelector('.btn-detalle-viaje').addEventListener('click', () => ver_detalle_viaje(viaje));
        const btnEditar = div.querySelector('.btn-editar-viaje');
        if (btnEditar) btnEditar.addEventListener('click', () => abrir_modal_viaje('editar', viaje));
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

    // Insertar botón "Editar viaje" para admin y dueño
    if (usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno') {
        const botonExistente = document.getElementById('boton_editar_viaje');
        if (botonExistente) botonExistente.remove();

        const botonEditar = document.createElement('button');
        botonEditar.className = 'btn';
        botonEditar.textContent = 'Editar viaje';
        botonEditar.id = 'boton_editar_viaje';
        botonEditar.addEventListener('click', () => abrir_modal_viaje('editar', viaje_seleccionado));

        const tituloDetalle = document.getElementById('detalle_viaje_titulo');
        tituloDetalle.after(botonEditar);
    }

    renderizar_micros_viaje(viaje.micros);
    if (usuario_actual.nivel !== 'terminal') {
        renderizar_terminales_viaje(viaje.terminales_autorizadas);
    } else {
        $("#lista_terminales_viaje").innerHTML = '';
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
        $("#detalle_viaje").classList.add("hidden");
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
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