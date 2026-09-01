/***
 * Funciones del panel de pasajeros/clientes.
 * @version 1.5piloto.32
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
    const tabla = document.getElementById('tabla_pasajeros');
    tabla.innerHTML = '';
    pasajeros.forEach(pasajero => {
        const tieneFicha = pasajero.ficha_salud !== null && pasajero.ficha_salud !== undefined;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${pasajero.nombre}</td>
            <td>${pasajero.dni}</td>
            <td>${pasajero.email || '—'}</td>
            <td>${pasajero.celular || '—'}</td>
            <td>${pasajero.celular_emergencia || '—'}</td>
            <td>
                <button class="btn ${tieneFicha ? 'ver-ficha' : 'anexar-ficha'}" data-dni="${pasajero.dni}">
                    ${tieneFicha ? 'Ver ficha salud' : 'Anexar ficha salud'}
                </button>
            </td>
            <td><button class="btn editar_pasajero" data-dni="${pasajero.dni}">Editar</button></td>
        `;
        tabla.appendChild(fila);

        // Listener para botón ficha (obtiene datos frescos desde el backend)
        const botonFicha = fila.querySelector('.ver-ficha, .anexar-ficha');
        if (botonFicha) {
            botonFicha.addEventListener('click', async function() {
                const dni = this.dataset.dni;
                // Obtener ficha actualizada
                const resp = await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ accion: "pasajeros/obtener", dni })
                });
                const datos = await resp.json();
                if (datos.exito) {
                    mostrar_ficha_salud_edicion(dni, datos.pasajero.ficha_salud);
                } else {
                    mostrar_aviso("Error al obtener ficha", 'error');
                }
            });
        }

        // Listener para botón editar
        const botonEditar = fila.querySelector('.editar_pasajero');
        if (botonEditar) {
            botonEditar.addEventListener('click', function() {
                cargar_detalle_pasajero(this.dataset.dni);
            });
        }
    });
}

document.getElementById('buscar_pasajero').addEventListener('input', function() {
    const termino = this.value.trim().toLowerCase();
    const filtrados = termino === '' ? pasajeros_actuales : pasajeros_actuales.filter(p => 
        p.nombre.toLowerCase().includes(termino) || p.dni.includes(termino)
    );
    renderizar_tabla_pasajeros(filtrados);
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

        const contenido = `
            <div class="form-grid">
                <div class="field full"><label>Nombre</label><input id="pasajero_nombre_modal" value="${p.nombre}"></div>
                <div class="field"><label>DNI (no editable)</label><input id="pasajero_dni_modal" disabled value="${p.dni}"></div>
                <div class="field"><label>Email (opcional)</label><input id="pasajero_email_modal" value="${p.email || ''}"></div>
                <div class="field"><label>Celular personal</label><input id="pasajero_celular_modal" value="${p.celular || ''}"></div>
                <div class="field"><label>Celular emergencias</label><input id="pasajero_emergencia_modal" value="${p.celular_emergencia || ''}"></div>
            </div>
            <button class="btn primary" style="margin-top:12px" id="boton_guardar_pasajero_modal">Guardar cambios</button>

            <h3 style="margin-top:20px">Pasajes vendidos</h3>
            <div id="pasajes_pasajero_modal">${p.ventas && p.ventas.length ? p.ventas.map(v => `<div class="sale-card"><strong>${v.viaje}</strong><br><span class="small">Venta ${v.id_venta} · ${v.fecha} · Total: $${v.total}</span></div>`).join('') : '<p>Sin pasajes registrados</p>'}</div>
        `;

        abrir_modal_generico('Datos del pasajero', contenido);

        document.getElementById('boton_guardar_pasajero_modal').addEventListener('click', async () => {
            const datos = {
                accion: "pasajeros/actualizar",
                dni: dni,
                nombre: document.getElementById('pasajero_nombre_modal').value.trim(),
                email: document.getElementById('pasajero_email_modal').value.trim(),
                celular: document.getElementById('pasajero_celular_modal').value.trim(),
                celular_emergencia: document.getElementById('pasajero_emergencia_modal').value.trim(),
            };
            const respuesta = await fetch("index.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(datos)
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                mostrar_aviso("Pasajero actualizado", 'exito');
                cerrar_modal_generico();
                cargar_pasajeros();
            } else {
                mostrar_aviso(resultado.error || "Error al actualizar", 'error');
            }
        });
    } else {
        mostrar_aviso(datos.error || 'Pasajero no encontrado', 'error');
    }
}

function mostrar_ficha_salud_edicion(dni, fichaSalud) {
    if (!fichaSalud) {
        fichaSalud = { enfermedades: [], medicamentos: [], impedimentos: [] };
    }

    // Construir HTML del contenido
    const contenidoHTML = `
        <h3>Datos de salud</h3>
        <div class="seccion-salud">
            <label>¿Padece alguna enfermedad crónica o tiene secuelas de alguna que ha tenido?</label>
            <input type="checkbox" class="check_enfermedad" ${fichaSalud.enfermedades.length ? 'checked' : ''}>
            <div class="enfermedades_container" style="${fichaSalud.enfermedades.length ? '' : 'display:none;'}"></div>
        </div>
        <div class="seccion-salud">
            <label>¿Está medicado en tratamiento médico o psiquiátrico?</label>
            <input type="checkbox" class="check_medicamento" ${fichaSalud.medicamentos.length ? 'checked' : ''}>
            <div class="medicamentos_container" style="${fichaSalud.medicamentos.length ? '' : 'display:none;'}"></div>
        </div>
        <div class="seccion-salud">
            <label>¿Posee algún impedimento físico?</label>
            <input type="checkbox" class="check_impedimento" ${fichaSalud.impedimentos.length ? 'checked' : ''}>
            <div class="impedimentos_container" style="${fichaSalud.impedimentos.length ? '' : 'display:none;'}"></div>
        </div>
        <button class="btn primary" id="guardar_ficha_salud">Guardar ficha</button>
    `;

    // Abrir modal genérico con el contenido
    abrir_modal_generico('Ficha de salud', contenidoHTML);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    // Llenar inputs existentes
    function cargarItemsEnContenedor(contenedor, tipo, dni, items) {
        items.forEach((item, idx) => {
            const esUltimo = idx === items.length - 1;
            agregarInputSalud(contenedor, tipo, dni, item, esUltimo);
        });
    }

    cargarItemsEnContenedor(contenedor.querySelector('.enfermedades_container'), 'enfermedad', dni, fichaSalud.enfermedades);
    cargarItemsEnContenedor(contenedor.querySelector('.medicamentos_container'), 'medicamento', dni, fichaSalud.medicamentos);
    cargarItemsEnContenedor(contenedor.querySelector('.impedimentos_container'), 'impedimento', dni, fichaSalud.impedimentos);

    // Eventos de checkboxes con selectores corregidos
    contenedor.querySelectorAll('.check_enfermedad, .check_medicamento, .check_impedimento').forEach(check => {
        check.addEventListener('change', function() {
            const tipo = this.classList.contains('check_enfermedad') ? 'enfermedad' :
                          this.classList.contains('check_medicamento') ? 'medicamento' : 'impedimento';
            const cont = contenedor.querySelector(`.${tipo === 'enfermedad' ? 'enfermedades' : tipo === 'medicamento' ? 'medicamentos' : 'impedimentos'}_container`);
            if (cont) {
                cont.style.display = this.checked ? 'block' : 'none';
                if (this.checked && cont.children.length === 0) {
                    agregarInputSalud(cont, tipo, dni);
                }
            }
        });
    });

    // Evento guardar ficha
    contenedor.querySelector('#guardar_ficha_salud').addEventListener('click', async () => {
        const ficha = {
            enfermedades: [],
            medicamentos: [],
            impedimentos: []
        };
        contenedor.querySelectorAll('.salud_enfermedad').forEach(input => ficha.enfermedades.push(input.value.trim()));
        contenedor.querySelectorAll('.salud_medicamento').forEach(input => ficha.medicamentos.push(input.value.trim()));
        contenedor.querySelectorAll('.salud_impedimento').forEach(input => ficha.impedimentos.push(input.value.trim()));

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "pasajeros/guardar_ficha",
                dni: dni,
                ficha: JSON.stringify(ficha)
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            mostrar_aviso("Ficha de salud guardada", 'exito');
            cerrar_modal_generico();
            cargar_pasajeros();
        } else {
            mostrar_aviso(resultado.error || "Error al guardar ficha", 'error');
        }
    });
}

function agregarInputSalud(contenedor, tipo, index, valorInicial = '', esUltimo = true) {
    const div = document.createElement('div');
    div.className = 'input_salud';
    div.style.marginBottom = '5px';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = `salud_${tipo}`;
    input.dataset.index = index;
    input.placeholder = tipo === 'enfermedad' ? '¿Cuál?' : (tipo === 'medicamento' ? 'Nombre del medicamento' : '¿Cuál?');
    input.value = valorInicial;

    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'btn small';
    boton.dataset.index = index;
    boton.dataset.tipo = tipo;

    let esAgregar = esUltimo;
    boton.textContent = esAgregar ? 'Agregar otra' : 'Quitar';
    boton.className = esAgregar ? 'btn small' : 'btn small danger';

    boton.addEventListener('click', () => {
        if (esAgregar) {
            // Cambiar botón actual a "Quitar"
            boton.textContent = 'Quitar';
            boton.className = 'btn small danger';
            esAgregar = false;

            // Agregar nueva fila con botón "Agregar otra"
            agregarInputSalud(contenedor, tipo, index, '', true);
        } else {
            div.remove();
        }
    });

    div.appendChild(input);
    div.appendChild(boton);
    contenedor.appendChild(div);
}