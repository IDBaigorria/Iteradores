/***
 * Funciones del panel de pasajeros/clientes.
 * @version 1.5piloto.39
 */
/**
 * Normaliza un DNI dejando solo dígitos.
 * Se usa como respaldo si el backend no envía dni_visible.
 */
function normalizar_dni_js(dni) {
    return String(dni || '').replace(/\D+/g, '');
}

let pasajeros_actuales = [];
let pasajero_seleccionado_dni = null;
let dueno_pasajeros_seleccionado = '';

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
        if (panelSelector) panelSelector.style.display = 'block';
        const select = document.getElementById('selector_dueno_pasajeros');
        if (select && select.options.length === 0) {
            await cargar_duenos_en_select_pasajeros();
        }
    } else {
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

    // La terminal no debe ver reservas del equipo. Dueño/admin sí.
    const es_admin_o_dueno = (usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno');

    let html = `<h3>Pasajes de ${p.nombre_completo || p.dni_visible || p.dni}</h3>`;
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
                    <span class="small">${pas.nombre_completo || pas.dni_visible || pas.dni}</span><br>
                    <span class="small muted">DNI: ${pas.dni_visible || normalizar_dni_js(pas.dni)}</span>
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
                                    <div class="detail-line"><span>Fecha:</span><strong>${v.pasaje.fecha_visible || v.pasaje.fecha} ${v.pasaje.hora}</strong></div>
                                    <div class="detail-line"><span>Vehículo:</span><strong>${v.pasaje.micro_nombre_visible}</strong></div>
                                </div>
                                ${tarjetasHtml}
                                ${botonImprimirTodos}
                            </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Reservas del equipo activas: solo se muestran a dueño/admin.
    if (es_admin_o_dueno && p.reservas && p.reservas.length) {
        html += `<h3 style="margin-top:20px;">Reservas del equipo</h3>`;
        html += p.reservas.map(r => {
            const viajeTitulo = r.origen && r.destino
                ? `${r.origen} → ${r.destino}`
                : (r.viaje_nombre_visible || r.viaje_id);
            const fechaTexto = r.fecha_visible && r.hora
                ? `${r.fecha_visible} ${r.hora}`
                : (r.fecha_visible || r.fecha || 'a confirmar');
            const puntoSb = r.punto_subida_bajada
                ? `${r.punto_subida_bajada}${r.hora_subida_bajada ? ' (' + r.hora_subida_bajada + ')' : ''}`
                : '';
            const urlImprimir = `index.php?imprimir=1&tipo=pasaje_reserva`
                + `&dueno=${encodeURIComponent(nombre_dueno)}`
                + `&viaje=${encodeURIComponent(r.viaje_id)}`
                + `&micro=${encodeURIComponent(r.micro_id)}`
                + `&fila=${encodeURIComponent(r.fila)}`
                + `&columna=${encodeURIComponent(r.columna)}`;
            return `
                <div class="sale-card" style="margin-bottom:15px; background:#eef4ff;">
                    <strong>${viajeTitulo}</strong>
                    <span class="badge">Reserva del equipo</span>
                    <div class="seccion" style="margin-top:10px;">
                        <h4>Datos de la reserva</h4>
                        <div class="detail-line"><span>Viaje:</span><strong>${r.viaje_nombre_visible || r.viaje_id}</strong></div>
                        <div class="detail-line"><span>Fecha:</span><strong>${fechaTexto}</strong></div>
                        <div class="detail-line"><span>Micro:</span><strong>${r.micro_nombre_visible || r.micro_id}</strong></div>
                        <div class="detail-line"><span>Asiento:</span><strong>${r.numero_asiento}</strong></div>
                        ${puntoSb ? `<div class="detail-line"><span>Sube/baja en:</span><strong>${puntoSb}</strong></div>` : ''}
                        ${r.reservado_por ? `<div class="detail-line"><span>Reservado por:</span><strong>${r.reservado_por}</strong></div>` : ''}
                        <div class="actions" style="margin-top:8px;">
                            <button class="btn primary imprimir_pasaje_reserva" data-url="${urlImprimir}">Imprimir pasaje de equipo</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Si no hay ni ventas ni reservas visibles, mostramos el mensaje vacío.
    const hay_ventas = (p.ventas && p.ventas.length > 0);
    const hay_reservas_visibles = es_admin_o_dueno && (p.reservas && p.reservas.length > 0);
    if (!hay_ventas && !hay_reservas_visibles) {
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

    // Evento botones "Imprimir pasaje de equipo" (reservas)
    document.querySelectorAll('.imprimir_pasaje_reserva').forEach(btn => {
        btn.addEventListener('click', function() {
            window.open(this.dataset.url, '_blank');
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

    const pasajero = asientoInfo.pasajero || { nombre_completo: 'Desconocido', dni: '', dni_visible: '' };
    const nombre_pasajero = pasajero.nombre_completo || pasajero.dni_visible || pasajero.dni || 'Desconocido';
    const dni_pasajero_mostrar = pasajero.dni_visible || normalizar_dni_js(pasajero.dni);
    const viaje = venta;
    const micro_nombre = venta.micro_nombre_visible || venta.patente || '';

    const contenido = `
        <h3>Detalle del pasaje</h3>
        <div class="seccion">
            <h4>Datos del pasajero</h4>
            <div class="detail-line"><span>Nombre:</span><strong>${nombre_pasajero}</strong></div>
            <div class="detail-line"><span>DNI:</span><strong>${dni_pasajero_mostrar}</strong></div>
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
    mostrar_aviso('Función en desarrollo', 'info');
}

function renderizar_tabla_pasajeros(pasajeros) {
    const tabla = document.getElementById('tabla_pasajeros');
    tabla.innerHTML = '';
    pasajeros.forEach(pasajero => {
        const tieneFicha = pasajero.ficha_salud !== null && pasajero.ficha_salud !== undefined;
        const direccionCompleta = [pasajero.direccion, pasajero.localidad].filter(v => v).join(', ') || '—';
        const nc = (pasajero.nombre_completo || '').trim();
        const nombreMostrar = (nc !== '' && nc !== ',' && nc !== ', ') ? nc : '(sin nombre)';
        const dni_mostrar = pasajero.dni_visible || normalizar_dni_js(pasajero.dni);
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${nombreMostrar}</td>
            <td>${dni_mostrar}</td>
            <td>${direccionCompleta}</td>
            <td>${pasajero.email || '—'}</td>
            <td>${pasajero.celular || '—'}</td>
            <td>${pasajero.celular_emergencia || '—'}</td>
            <td>
                <button class="btn ${tieneFicha ? 'ver-ficha' : 'anexar-ficha'}" data-dni="${pasajero.dni}">
                    ${tieneFicha ? 'Ver ficha salud' : 'Anexar ficha salud'}
                </button>
            </td>
            <td>
                ${(() => {
                    // Terminal: solo ve pasajes de ventas activas (no del equipo).
                    // Dueño/admin: ve pasajes de ventas activas o reservas activas del equipo.
                    const es_admin_o_dueno = (usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno');
                    const tiene_ventas = pasajero.tiene_ventas_activas === true;
                    const tiene_reservas = pasajero.tiene_reservas_activas === true;
                    const mostrar = tiene_ventas || (es_admin_o_dueno && tiene_reservas);
                    if (!mostrar) return '';
                    return `<button class="btn ver_pasajes_pasajero" data-dni="${pasajero.dni}">Ver pasajes</button>`;
                })()}
            </td>
            <td>
                <button class="btn editar_pasajero" data-dni="${pasajero.dni}" title="Editar">✏️</button>
                <button class="btn eliminar_pasajero" data-dni="${pasajero.dni}" title="Eliminar">🗑️</button>
            </td>
        `;
        tabla.appendChild(fila);

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

        const botonVerPasajes = fila.querySelector('.ver_pasajes_pasajero');
        if (botonVerPasajes) {
            botonVerPasajes.addEventListener('click', function() {
                ver_pasajes_pasajero(this.dataset.dni);
            });
        }

        const botonEditar = fila.querySelector('.editar_pasajero');
        if (botonEditar) {
            botonEditar.addEventListener('click', function() {
                cargar_detalle_pasajero(this.dataset.dni);
            });
        }

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
    const termino_dni = termino.replace(/\D+/g, '');
    const filtrados = termino === '' ? pasajeros_actuales : pasajeros_actuales.filter(p => {
        if ((p.nombres || '').toLowerCase().includes(termino)) return true;
        if ((p.apellido || '').toLowerCase().includes(termino)) return true;
        if (p.dni && p.dni.toLowerCase().includes(termino)) return true;
        if (termino_dni !== '' && p.dni && p.dni.replace(/\D+/g, '').includes(termino_dni)) return true;
        return false;
    });
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

        // Guardar los valores originales del formulario para poder detectar
        // si hubo cambios reales antes de llamar al backend (D2).
        const valores_originales = {
            apellido: p.apellido || '',
            nombres: p.nombres || '',
            email: p.email || '',
            celular: p.celular || '',
            celular_emergencia: p.celular_emergencia || '',
            direccion: p.direccion || '',
            localidad: p.localidad || ''
        };

        // Orden: DNI (no editable) primero, después Apellido, después Nombres.
        const contenido = `
            <div class="form-grid">
                <div class="field"><label>DNI (no editable)</label><input id="pasajero_dni_modal" disabled value="${p.dni_visible || normalizar_dni_js(p.dni)}"></div>
                <div class="field"><label>Apellido</label><input id="pasajero_apellido_modal" value="${p.apellido || ''}"></div>
                <div class="field full"><label>Nombres</label><input id="pasajero_nombres_modal" value="${p.nombres || ''}"></div>
                <div class="field"><label>Email (opcional)</label><input id="pasajero_email_modal" value="${p.email || ''}"></div>
                <div class="field"><label>Celular personal</label><input id="pasajero_celular_modal" value="${p.celular || ''}"></div>
                <div class="field"><label>Celular emergencias</label><input id="pasajero_emergencia_modal" value="${p.celular_emergencia || ''}"></div>
                <div class="field"><label>Dirección</label><input id="pasajero_direccion_modal" value="${p.direccion || ''}"></div>
                <div class="field"><label>Localidad</label><input id="pasajero_localidad_modal" value="${p.localidad || ''}"></div>
            </div>
            <button class="btn primary" style="margin-top:12px" id="boton_guardar_pasajero_modal">Guardar cambios</button>
        `;

        abrir_modal_generico('Datos del pasajero', contenido);

        document.getElementById('boton_guardar_pasajero_modal').addEventListener('click', async () => {
            const nombre_dueno = obtener_nombre_dueno_pasajeros();

            const valores_nuevos = {
                apellido: document.getElementById('pasajero_apellido_modal').value.trim(),
                nombres: document.getElementById('pasajero_nombres_modal').value.trim(),
                email: document.getElementById('pasajero_email_modal').value.trim(),
                celular: document.getElementById('pasajero_celular_modal').value.trim(),
                celular_emergencia: document.getElementById('pasajero_emergencia_modal').value.trim(),
                direccion: document.getElementById('pasajero_direccion_modal').value.trim(),
                localidad: document.getElementById('pasajero_localidad_modal').value.trim()
            };

            // Si no hubo cambios, no llamamos al backend (D2).
            const hay_cambios = Object.keys(valores_originales).some(
                campo => valores_originales[campo] !== valores_nuevos[campo]
            );
            if (!hay_cambios) {
                cerrar_modal_generico();
                return;
            }

            const datos = {
                accion: "pasajeros/actualizar",
                dni: dni,
                nombre_dueno,
                apellido: valores_nuevos.apellido,
                nombres: valores_nuevos.nombres,
                email: valores_nuevos.email,
                celular: valores_nuevos.celular,
                celular_emergencia: valores_nuevos.celular_emergencia,
                direccion: valores_nuevos.direccion,
                localidad: valores_nuevos.localidad
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

                // Si el pasajero tiene pasajes propios en viajes activos,
                // ofrecer imprimirlos actualizados.
                if (resultado.tiene_pasajes_activos === true) {
                    mostrar_modal_chico_impresion_pasajero(dni, nombre_dueno);
                }
            } else {
                mostrar_aviso(resultado.error || "Error al actualizar", 'error');
            }
        });
    } else {
        mostrar_aviso(datos.error || 'Pasajero no encontrado', 'error');
    }
}

/**
 * Muestra el modal chico flotante para imprimir los pasajes actualizados
 * de un pasajero. Se invoca después de editar los datos personales de un
 * pasajero que tiene pasajes propios en al menos un viaje activo.
 *
 * Al cerrar, el modal de edición ya está cerrado y el usuario ve la tabla
 * de pasajeros debajo.
 *
 * @param {string} dni DNI del pasajero (puede tener o no puntos).
 * @param {string} nombre_dueno Nombre de usuario del dueño del pasajero.
 */
function mostrar_modal_chico_impresion_pasajero(dni, nombre_dueno) {
    const contenedor = document.getElementById('modal_chico_impresion_pasajero');
    const titulo = document.getElementById('modal_chico_impresion_pasajero_titulo');
    const btnImprimir = document.getElementById('btn_modal_chico_imprimir_pasajero');
    const btnCerrar = document.getElementById('btn_modal_chico_cerrar_pasajero');
    if (!contenedor || !titulo || !btnImprimir || !btnCerrar) return;

    titulo.textContent = 'Pasajero actualizado';

    btnImprimir.onclick = () => {
        const url = `index.php?imprimir=1&tipo=pasajes_actualizados`
            + `&dueno=${encodeURIComponent(nombre_dueno)}`
            + `&dni=${encodeURIComponent(dni)}`;
        window.open(url, '_blank');
    };

    btnCerrar.onclick = () => {
        contenedor.classList.add('hidden');
    };

    contenedor.classList.remove('hidden');
}

function mostrar_ficha_salud_edicion(dni, fichaSalud) {
    if (!fichaSalud) {
        fichaSalud = {
            grupo_sanguineo: '',
            obra_social: '',
            alergias: '',
            enfermedades: '',
            medicamentos: '',
            impedimentos: '',
            regimenes_comida: '',
            observaciones: ''
        };
    }

    const opcionesGrupo = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'Desconocido'];
    const grupoActual = fichaSalud.grupo_sanguineo || 'Desconocido';

    const contenidoHTML = `
        <h3>Datos de salud</h3>
        <div class="seccion-salud">
            <label>Grupo sanguíneo</label>
            <select id="grupo_sanguineo">
                ${opcionesGrupo.map(op => `<option value="${op}" ${op === grupoActual ? 'selected' : ''}>${op}</option>`).join('')}
            </select>
        </div>
        <div class="seccion-salud">
            <label>Obra social o prepaga (incluya num de emergencias si corresponde)</label>
            <input type="text" id="obra_social" value="${fichaSalud.obra_social || ''}">
        </div>
        <div class="seccion-salud">
            <label>¿Tiene algún tipo de alergia?</label>
            <input type="checkbox" id="check_alergia" ${fichaSalud.alergias ? 'checked' : ''}>
            <input type="text" id="alergias" value="${fichaSalud.alergias || ''}" placeholder="Detalle" style="${fichaSalud.alergias ? '' : 'display:none;'}">
        </div>
        <div class="seccion-salud">
            <label>¿Padece alguna enfermedad crónica o tiene secuelas de alguna que ha tenido?</label>
            <input type="checkbox" id="check_enfermedad" ${fichaSalud.enfermedades ? 'checked' : ''}>
            <input type="text" id="enfermedades" value="${fichaSalud.enfermedades || ''}" placeholder="Detalle" style="${fichaSalud.enfermedades ? '' : 'display:none;'}">
        </div>
        <div class="seccion-salud">
            <label>¿Está tomando algún medicamento? ¿Cual/es? ¿En qué horarios?</label>
            <input type="checkbox" id="check_medicamento" ${fichaSalud.medicamentos ? 'checked' : ''}>
            <input type="text" id="medicamentos" value="${fichaSalud.medicamentos || ''}" placeholder="Detalle" style="${fichaSalud.medicamentos ? '' : 'display:none;'}">
        </div>
        <div class="seccion-salud">
            <label>¿Posee algún impedimento físico?</label>
            <input type="checkbox" id="check_impedimento" ${fichaSalud.impedimentos ? 'checked' : ''}>
            <input type="text" id="impedimentos" value="${fichaSalud.impedimentos || ''}" placeholder="Detalle" style="${fichaSalud.impedimentos ? '' : 'display:none;'}">
        </div>
        <div class="seccion-salud">
            <label>¿Sigue algún regimen especial de comida?</label>
            <input type="checkbox" id="check_regimen_comida" ${fichaSalud.regimenes_comida ? 'checked' : ''}>
            <input type="text" id="regimenes_comida" value="${fichaSalud.regimenes_comida || ''}" placeholder="Detalle" style="${fichaSalud.regimenes_comida ? '' : 'display:none;'}">
        </div>
        <div class="seccion-salud">
            <label>Algún otro dato que considere importante:</label>
            <textarea id="observaciones" rows="3">${fichaSalud.observaciones || ''}</textarea>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top:15px;">
            <button class="btn primary" id="guardar_ficha_salud">Guardar ficha</button>
            ${(usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno') ? `<button class="btn" id="imprimir_ficha_salud">Imprimir ficha</button>` : ''}
        </div>
    `;

    abrir_modal_generico('Ficha de salud', contenidoHTML);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    // Eventos para mostrar/ocultar inputs según checkbox
    const pares = [
        ['check_alergia', 'alergias'],
        ['check_enfermedad', 'enfermedades'],
        ['check_medicamento', 'medicamentos'],
        ['check_impedimento', 'impedimentos'],
        ['check_regimen_comida', 'regimenes_comida']   // <-- agregar
    ];
    pares.forEach(([checkId, inputId]) => {
        const check = contenedor.querySelector(`#${checkId}`);
        const input = contenedor.querySelector(`#${inputId}`);
        if (check && input) {
            check.addEventListener('change', () => {
                input.style.display = check.checked ? '' : 'none';
                if (!check.checked) input.value = '';
            });
        }
    });

    // Botón guardar
    contenedor.querySelector('#guardar_ficha_salud').addEventListener('click', async () => {
        const nombre_dueno = obtener_nombre_dueno_pasajeros();
        const ficha = {
            grupo_sanguineo: contenedor.querySelector('#grupo_sanguineo').value,
            obra_social: contenedor.querySelector('#obra_social').value.trim(),
            alergias: contenedor.querySelector('#alergias').value.trim(),
            enfermedades: contenedor.querySelector('#enfermedades').value.trim(),
            medicamentos: contenedor.querySelector('#medicamentos').value.trim(),
            impedimentos: contenedor.querySelector('#impedimentos').value.trim(),
            regimenes_comida: contenedor.querySelector('#regimenes_comida').value.trim(),
            observaciones: contenedor.querySelector('#observaciones').value.trim()
        };

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

    // Botón imprimir
    const botonImprimir = contenedor.querySelector('#imprimir_ficha_salud');
    if (botonImprimir) {
        botonImprimir.addEventListener('click', () => {
            const nombre_dueno = obtener_nombre_dueno_pasajeros();
            window.open(`index.php?imprimir=1&tipo=ficha_salud&id_venta=${nombre_dueno}&dni=${dni}`, '_blank');
        });
    }
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