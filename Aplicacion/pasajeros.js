/***
 * Funciones del panel de pasajeros/clientes.
 * @version 1.5piloto.15
 */

let pasajeros_actuales = [];
let pasajero_seleccionado_dni = null;

async function cargar_pasajeros() {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/listar" })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        pasajeros_actuales = datos.pasajeros;
        renderizar_tabla_pasajeros(pasajeros_actuales);
    } else {
        mostrar_aviso(datos.error || "Error al cargar pasajeros", 'error');
    }
}

function renderizar_tabla_pasajeros(pasajeros) {
    const tabla = $("#tabla_pasajeros");
    tabla.innerHTML = '';
    pasajeros.forEach(pasajero => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${pasajero.nombre}</td>
            <td>${pasajero.dni}</td>
            <td>${pasajero.email || '—'}</td>
            <td>${pasajero.celular || '—'}</td>
            <td>${pasajero.celular_emergencia || '—'}</td>
            <td><button class="btn editar_pasajero" data-dni="${pasajero.dni}">Editar</button></td>
        `;
        tabla.appendChild(fila);
        fila.querySelector('.editar_pasajero').addEventListener('click', () => cargar_detalle_pasajero(pasajero.dni));
    });
}

$("#buscar_pasajero").addEventListener('input', function() {
    const termino = this.value.trim().toLowerCase();
    if (termino === '') {
        renderizar_tabla_pasajeros(pasajeros_actuales);
    } else {
        const filtrados = pasajeros_actuales.filter(p => 
            p.nombre.toLowerCase().includes(termino) || p.dni.includes(termino)
        );
        renderizar_tabla_pasajeros(filtrados);
    }
});

async function cargar_detalle_pasajero(dni) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/obtener", dni })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        pasajero_seleccionado_dni = dni;
        const p = datos.pasajero;
        $("#pasajero_nombre").value = p.nombre;
        $("#pasajero_dni").value = p.dni;
        $("#pasajero_email").value = p.email || '';
        $("#pasajero_celular").value = p.celular || '';
        $("#pasajero_emergencia").value = p.celular_emergencia || '';
        const contenedor = $("#pasajes_pasajero");
        contenedor.innerHTML = '';
        if (p.ventas && p.ventas.length > 0) {
            p.ventas.forEach(venta => {
                const div = document.createElement('div');
                div.className = 'sale-card';
                div.innerHTML = `<strong>${venta.viaje}</strong><br><span class="small">Venta ${venta.id_venta} · ${venta.fecha} · Total: $${venta.total}</span>`;
                contenedor.appendChild(div);
            });
        } else {
            contenedor.innerHTML = '<p>Sin pasajes registrados</p>';
        }
        $("#detalle_pasajero").classList.remove("hidden");
    } else {
        mostrar_aviso(datos.error || "Pasajero no encontrado", 'error');
    }
}

$("#boton_guardar_pasajero").addEventListener('click', async () => {
    if (!pasajero_seleccionado_dni) {
        mostrar_aviso("Seleccione un pasajero", 'error');
        return;
    }
    const datos = {
        accion: "pasajeros/actualizar",
        dni: pasajero_seleccionado_dni,
        nombre: $("#pasajero_nombre").value.trim(),
        email: $("#pasajero_email").value.trim(),
        celular: $("#pasajero_celular").value.trim(),
        celular_emergencia: $("#pasajero_emergencia").value.trim(),
    };
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Pasajero actualizado", 'exito');
        cargar_pasajeros();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar", 'error');
    }
});