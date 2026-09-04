/***
 * Funciones del panel de pasajeros/clientes.
 * @version 1.5piloto.34
 */

let pasajeros_actuales = [];
let pasajero_seleccionado_dni = null;
let dueno_pasajeros_seleccionado = '';  // Para admin, guarda el dueño elegido

/**
 * Devuelve el nombre del dueño actual para las peticiones de pasajeros.
 * - Si es admin: usa el selector `selector_dueno_pasajeros` (si existe y tiene valor).
 * - Si es dueño: usa su propio nombre de usuario.
 * - Si es terminal: usa el dueño asociado.
 */
function obtener_nombre_dueno_pasajeros() {
    if (usuario_actual.nivel === 'admin') {
        const select = document.getElementById('selector_dueno_pasajeros');
        if (select && select.value) {
            return select.value;
        }
        return dueno_pasajeros_seleccionado || '';
    } else if (usuario_actual.nivel === 'dueno') {
        return usuario_actual.nombre_usuario;
    } else if (usuario_actual.nivel === 'terminal') {
        return usuario_actual.dueno || '';
    }
    return '';
}

async function cargar_pasajeros() {
    const panelSelector = document.getElementById('panel_selector_dueno_pasajeros');

    if (usuario_actual.nivel === 'admin') {
        // Mostrar panel y cargar dueños
        if (panelSelector) panelSelector.style.display = 'block';
        const select = document.getElementById('selector_dueno_pasajeros');
        if (select && select.options.length === 0) {
            await cargar_duenos_en_select_pasajeros();
        }
    } else {
        // Ocultar panel para dueño y terminal
        if (panelSelector) panelSelector.style.display = 'none';
    }

    const nombre_dueno = obtener_nombre_dueno_pasajeros();
    if (!nombre_dueno) {
        mostrar_aviso('Seleccione un dueño para ver pasajeros', 'info');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/listar", nombre_dueno })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        pasajeros_actuales = datos.pasajeros;
        renderizar_tabla_pasajeros(pasajeros_actuales);
    } else {
        mostrar_aviso(datos.error || "Error al cargar pasajeros", 'error');
    }
}

async function cargar_duenos_en_select_pasajeros() {
    const select = document.getElementById('selector_dueno_pasajeros');
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

        select.onchange = () => {
            dueno_pasajeros_seleccionado = select.value;
            cargar_pasajeros();
        };

        // Seleccionar el primer dueño por defecto
        if (select.options.length > 1) {
            select.selectedIndex = 1;
            dueno_pasajeros_seleccionado = select.value;
        }
    }
}

async function eliminar_pasajero(dni) {
    const nombre_dueno = obtener_nombre_dueno_pasajeros();
    if (!nombre_dueno) {
        mostrar_aviso('Seleccione un dueño', 'info');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/eliminar", dni, nombre_dueno })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Pasajero eliminado", 'exito');
        cargar_pasajeros();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
}
async function ver_pasajes_pasajero(dni) {
    const nombre_dueno = obtener_nombre_dueno_pasajeros();
    if (!nombre_dueno) {
        mostrar_aviso('Seleccione un dueño', 'info');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/obtener", dni, nombre_dueno })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener pasajes", 'error');
        return;
    }

    const p = datos.pasajero;
    let html = `<h3>Pasajes de ${p.nombre}</h3>`;
    if (p.ventas && p.ventas.length) {
        html += p.ventas.map(v => {
            // Etiqueta de rol
            let etiquetaRol = '';
            let claseRol = '';
            if (v.rol === 'ambos') {
                etiquetaRol = 'Comprador y pasajero';
                claseRol = 'badge paid';
            } else if (v.rol === 'comprador') {
                etiquetaRol = 'Comprador';
                claseRol = 'badge paid';
            } else {
                etiquetaRol = 'Pasajero';
                claseRol = 'badge pending';
            }

            // Título: nombre del viaje (usa compra.id_venta como fallback)
            const nombreViaje = v.pasaje && v.pasaje.origen ? `${v.pasaje.origen} → ${v.pasaje.destino}` : v.compra.id_venta;

            // Sección compra (sin total)
            const compra = v.compra;
            let compraHtml = `
                <div class="seccion" style="margin-top:10px;">
                    <h4>Información de la compra</h4>
                    <div class="detail-line"><span>Código:</span><strong>${compra.id_venta}</strong></div>
                    <div class="detail-line"><span>Punto de venta:</span><strong>${compra.terminal_nombre}</strong></div>
                    <div class="detail-line"><span>Fecha:</span><strong>${compra.fecha}</strong></div>
                    <div class="detail-line"><span>Estado:</span><strong>${compra.estado_pago}</strong></div>
                    <div class="actions" style="margin-top:8px;">
                        <button class="btn ver_compra" data-id="${compra.id_venta}">Ver compra</button>
                    </div>
                </div>
            `;

            // Tarjetas de pasajes (filtradas según rol)
            let pasajesFiltrados = v.pasajes_venta;
            if (!v.es_comprador && v.es_pasajero) {
                pasajesFiltrados = v.pasajes_venta.filter(pas => pas.dni === p.dni);
            }

            let tarjetasHtml = '';
            if (pasajesFiltrados && pasajesFiltrados.length > 0) {
                tarjetasHtml = `<div class="pasajes-lista" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;">`;
                tarjetasHtml += pasajesFiltrados.map(pas => `<div class="pasaje-card" data-id="${v.compra.id_venta}" data-dni="${pas.dni}" data-asiento="${pas.asiento}">
                    <strong>Asiento ${pas.asiento}</strong><br>
                    <span class="small">${pas.nombre}</span>
                </div>`).join('');
                tarjetasHtml += `</div>`;
            }

            // Botón imprimir todos (siempre visible)
            const urlImprimirTodos = v.es_comprador
                ? `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}`
                : `index.php?imprimir=1&tipo=pasajes&id_venta=${v.compra.id_venta}&dni=${p.dni}`;
            const botonImprimirTodos = `
                <div class="actions" style="margin-top:8px;">
                    <button class="btn imprimir_todos_pasajes" data-url="${urlImprimirTodos}">Imprimir todos los pasajes</button>
                </div>
            `;
            return `
                <div class="sale-card" style="margin-bottom:15px;">
                    <strong>${nombreViaje}</strong>
                    <span class="badge ${claseRol}">${etiquetaRol}</span>
                    <div style="display:flex; flex-wrap:wrap; gap:40px; align-items:flex-start; margin-top:10px;">
                        <div style="flex:0 0 40%; min-width:200px;">
                            ${compraHtml}
                        </div>
                            <div style="flex:1; min-width:200px;">
                                <div class="seccion" style="margin-bottom:10px;">
                                    <h4>Pasajes</h4>
                                    <div class="detail-line"><span>Fecha:</span><strong>${v.pasaje.fecha} ${v.pasaje.hora}</strong></div>
                                    <div class="detail-line"><span>Vehículo:</span><strong>${v.pasaje.micro_nombre_visible}</strong></div>
                                </div>
                                ${tarjetasHtml}
                                ${botonImprimirTodos}
                            </div>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        html += '<p>Sin pasajes registrados</p>';
    }

    abrir_modal_generico('Pasajes del pasajero', html);

    // Evento botones "Ver compra" (placeholder)
    document.querySelectorAll('.ver_compra').forEach(btn => {
        btn.addEventListener('click', function() {
            ver_compra_desde_pasajero(this.dataset.id);
        });
    });

    // Evento botones "Imprimir todos los pasajes"
    document.querySelectorAll('.imprimir_todos_pasajes').forEach(btn => {
        btn.addEventListener('click', function() {
            window.open(this.dataset.url, '_blank');
        });
    });

    // Evento clic en tarjetas de pasaje
    document.querySelectorAll('.pasaje-card').forEach(card => {
        card.addEventListener('click', function() {
            const id_venta = this.dataset.id;
            const dni_pas = this.dataset.dni;
            const asiento = this.dataset.asiento;
            ver_detalle_pasaje_individual(id_venta, dni_pas, asiento);
        });
    });
}

/**
 * Muestra el detalle completo de un pasaje individual en un modal secundario.
 */
async function ver_detalle_pasaje_individual(id_venta, dni_pasajero, asiento) {
    const nombre_dueno = obtener_nombre_dueno_pasajeros();
    if (!nombre_dueno) {
        mostrar_aviso('Seleccione un dueño', 'info');
        return;
    }

    // Obtener venta completa para extraer datos del viaje
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/obtener", id_venta })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener venta", 'error');
        return;
    }

    const venta = datos.venta;
    // Buscar el asiento en la venta
    const asientoInfo = venta.asientos.find(a => a.numero === asiento);
    if (!asientoInfo) {
        mostrar_aviso("No se encontró el asiento", 'error');
        return;
    }

    const pasajero = asientoInfo.pasajero || { nombre: 'Desconocido', dni: '' };
    const viaje = venta;
    const micro_nombre = venta.micro_nombre_visible || venta.patente || '';

    const contenido = `
        <h3>Detalle del pasaje</h3>
        <div class="seccion">
            <h4>Datos del pasajero</h4>
            <div class="detail-line"><span>Nombre:</span><strong>${pasajero.nombre}</strong></div>
            <div class="detail-line"><span>DNI:</span><strong>${pasajero.dni}</strong></div>
        </div>
        <div class="seccion">
            <h4>Datos del viaje</h4>
            <div class="detail-line"><span>Viaje:</span><strong>${viaje.viaje_visible || viaje.viaje}</strong></div>
            <div class="detail-line"><span>Origen:</span><strong>${viaje.origen}</strong></div>
            <div class="detail-line"><span>Destino:</span><strong>${viaje.destino}</strong></div>
            <div class="detail-line"><span>Fecha:</span><strong>${viaje.fecha} ${viaje.hora}</strong></div>
            <div class="detail-line"><span>Micro:</span><strong>${micro_nombre}</strong></div>
            <div class="detail-line"><span>Asiento:</span><strong>${asientoInfo.numero}</strong></div>
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn primary imprimir_pasaje_individual" data-id="${id_venta}" data-dni="${pasajero.dni}">Imprimir pasaje</button>
            <button class="btn volver_listado_pasajes" data-dni="${pasajero.dni}">Volver</button>
        </div>
    `;

    abrir_modal_generico('Pasaje individual', contenido);

    document.querySelector('.imprimir_pasaje_individual').addEventListener('click', function() {
        const id = this.dataset.id;
        const dni = this.dataset.dni;
        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id}&dni=${dni}`, '_blank');
    });

    document.querySelector('.volver_listado_pasajes').addEventListener('click', function() {
        // Volver al listado de ventas del pasajero original
        const dniOriginal = this.dataset.dni;
        ver_pasajes_pasajero(dniOriginal);
    });
}

function ver_compra_desde_pasajero(id_venta) {
    // TODO: implementar
    mostrar_aviso('Función en desarrollo', 'info');
}


/**
 * Placeholder para ver la compra completa desde el modal de pasajes.
 * Se implementará en una próxima versión.
 */
function ver_compra_desde_pasajero(id_venta) {
    // TODO: implementar apertura de detalle de venta
    mostrar_aviso('Función en desarrollo', 'info');
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
            <td>
                ${pasajero.tiene_pasajes 
                    ? `<button class="btn ver_pasajes_pasajero" data-dni="${pasajero.dni}">Ver pasajes</button>` 
                    : '<span class="muted">Sin pasajes</span>'}
            </td>
            <td>
                <button class="btn editar_pasajero" data-dni="${pasajero.dni}" title="Editar">✏️</button>
                <button class="btn eliminar_pasajero" data-dni="${pasajero.dni}" title="Eliminar">🗑️</button>
            </td>
        `;
        tabla.appendChild(fila);

        // Listener para botón ficha (columna Ficha salud)
        const botonFicha = fila.querySelector('.ver-ficha, .anexar-ficha');
        if (botonFicha) {
            botonFicha.addEventListener('click', async function() {
                const dni = this.dataset.dni;
                const nombre_dueno = obtener_nombre_dueno_pasajeros();
                const resp = await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ accion: "pasajeros/obtener", dni, nombre_dueno })
                });
                const datos = await resp.json();
                if (datos.exito) {
                    mostrar_ficha_salud_edicion(dni, datos.pasajero.ficha_salud);
                } else {
                    mostrar_aviso("Error al obtener ficha", 'error');
                }
            });
        }

        // Listener para botón "Ver pasajes"
        const botonVerPasajes = fila.querySelector('.ver_pasajes_pasajero');
        if (botonVerPasajes) {
            botonVerPasajes.addEventListener('click', function() {
                ver_pasajes_pasajero(this.dataset.dni);
            });
        }

        // Listener para botón editar
        const botonEditar = fila.querySelector('.editar_pasajero');
        if (botonEditar) {
            botonEditar.addEventListener('click', function() {
                cargar_detalle_pasajero(this.dataset.dni);
            });
        }

        // Listener para botón eliminar
        const botonEliminar = fila.querySelector('.eliminar_pasajero');
        if (botonEliminar) {
            botonEliminar.addEventListener('click', function() {
                const dni = this.dataset.dni;
                if (confirm(`¿Eliminar al pasajero ${dni}?`)) {
                    eliminar_pasajero(dni);
                }
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
    const nombre_dueno = obtener_nombre_dueno_pasajeros();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/obtener", dni, nombre_dueno })
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
        `;

        abrir_modal_generico('Datos del pasajero', contenido);

        document.getElementById('boton_guardar_pasajero_modal').addEventListener('click', async () => {
            const nombre_dueno = obtener_nombre_dueno_pasajeros();
            const datos = {
                accion: "pasajeros/actualizar",
                dni: dni,
                nombre_dueno,
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

    abrir_modal_generico('Ficha de salud', contenidoHTML);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    function cargarItemsEnContenedor(contenedor, tipo, dni, items) {
        items.forEach((item, idx) => {
            const esUltimo = idx === items.length - 1;
            agregarInputSalud(contenedor, tipo, dni, item, esUltimo);
        });
    }

    cargarItemsEnContenedor(contenedor.querySelector('.enfermedades_container'), 'enfermedad', dni, fichaSalud.enfermedades);
    cargarItemsEnContenedor(contenedor.querySelector('.medicamentos_container'), 'medicamento', dni, fichaSalud.medicamentos);
    cargarItemsEnContenedor(contenedor.querySelector('.impedimentos_container'), 'impedimento', dni, fichaSalud.impedimentos);

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

    contenedor.querySelector('#guardar_ficha_salud').addEventListener('click', async () => {
        const nombre_dueno = obtener_nombre_dueno_pasajeros();
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
                dni,
                nombre_dueno,
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
            boton.textContent = 'Quitar';
            boton.className = 'btn small danger';
            esAgregar = false;
            agregarInputSalud(contenedor, tipo, index, '', true);
        } else {
            div.remove();
        }
    });

    div.appendChild(input);
    div.appendChild(boton);
    contenedor.appendChild(div);
}