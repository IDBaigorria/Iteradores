/***
 * Funciones de venta, confirmación, listado y cancelación.
 * @version 1.5piloto.16
 */

let ventas_actuales = [];
let ultima_venta_id = null; // añadido

// Mostrar botón "Vender" si hay asientos seleccionados propios
function mostrar_boton_confirmar_venta() {
    const contenedor = $("#contenedor_boton_confirmar_venta");
    if (!contenedor) return;
    if (usuario_actual && usuario_actual.nivel === 'terminal' && tiene_asientos_seleccionados_propios() && !venta_form_abierto) {
        contenedor.classList.remove("hidden");
    } else {
        contenedor.classList.add("hidden");
    }
}

function tiene_asientos_seleccionados_propios() {
    return estados_asientos_actuales.some(a => a.estado === 'seleccionado' && a.seleccionado_por === usuario_actual.nombre_usuario);
}

// Evento del botón "Vender"
$("#boton_confirmar_venta").addEventListener("click", abrir_modal_confirmacion_venta);

// Abrir formulario de venta
function abrir_modal_confirmacion_venta() {
    if (!microSyncActual || !viaje_seleccionado) return;

    const asientos_seleccionados = estados_asientos_actuales.filter(a => a.estado === 'seleccionado' && a.seleccionado_por === usuario_actual.nombre_usuario);
    if (asientos_seleccionados.length === 0) {
        mostrar_aviso("No tiene asientos seleccionados", 'error');
        return;
    }

    const micro = viaje_seleccionado.micros.find(m => m.nombre_micro === microSyncActual);
    const monto = micro ? parseFloat(micro.monto) : 0;
    const total = monto * asientos_seleccionados.length;
    window.total_venta = total; // almacenar para cálculos

    // Ocultar botón Vender y mostrar formulario
    venta_form_abierto = true;
    $("#contenedor_boton_confirmar_venta").classList.add("hidden");
    $("#info_asiento_viaje").classList.add("hidden");
    $("#formulario_confirmacion_venta").classList.remove("hidden");

    const formulario = $("#formulario_confirmacion_venta");
    formulario.innerHTML = `
        <h4>Confirmar venta</h4>
        <div id="resumen_venta">
            <p><strong>Asientos seleccionados:</strong> ${asientos_seleccionados.map(a => a.numero).join(', ')}</p>
            <p><strong>Monto por asiento:</strong> $${monto.toFixed(2)}</p>
            <p><strong>Total a pagar:</strong> $${total.toFixed(2)}</p>
        </div>
        <div class="form-grid" style="margin-top:15px;">
            <div class="field">
                <label>Método de pago</label>
                <select id="metodo_pago">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="field" id="campo_cuotas">
                <label>Cantidad de cuotas (1-3)</label>
                <select id="cuotas_venta">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </div>
            <div class="field" id="campo_monto_pagado">
                <label>Monto a pagar ahora *</label>
                <input type="number" id="monto_pagado" step="0.01" min="0.01" value="">
            </div>
        </div>
        <div id="datos_comprador" style="margin-top:15px;">
            <h4>Comprador</h4>
            <div class="form-grid">
                <div class="field"><label>DNI *</label><input id="comprador_dni"></div>
                <div class="field"><label>Nombre *</label><input id="comprador_nombre"></div>
                <div class="field"><label>Email</label><input id="comprador_email"></div>
                <div class="field"><label>Celular *</label><input id="comprador_celular"></div>
            </div>
            <button class="btn small" id="usar_pasajero_como_comprador">Usar primer pasajero</button>
        </div>
        <div id="pasajeros_venta" style="margin-top:20px;"></div>
        <div class="actions" style="margin-top:20px;">
            <button class="btn primary" id="confirmar_venta">Confirmar venta</button>
            <button class="btn" id="cancelar_venta_modal">Cancelar</button>
        </div>
    `;

    // Valores por defecto
    $("#metodo_pago").value = 'efectivo';
    $("#cuotas_venta").value = '1';
    actualizar_visibilidad_cuotas(); // Configura input de monto pagado

    // Limpiar campos del comprador
    $("#comprador_dni").value = '';
    $("#comprador_nombre").value = '';
    $("#comprador_email").value = '';
    $("#comprador_celular").value = '';

    generar_formularios_pasajeros(asientos_seleccionados);

    // Asegurar que el select de método de pago esté habilitado
    $("#metodo_pago").disabled = false;
    // Resetear cualquier estado previo
    venta_form_abierto = true;
    $("#contenedor_boton_confirmar_venta").classList.add("hidden");

    // Eventos para los nuevos elementos
    $("#metodo_pago").addEventListener("change", actualizar_visibilidad_cuotas);
    $("#cuotas_venta").addEventListener("change", function() {
        actualizar_visibilidad_cuotas();
    });

    // Evento para usar pasajero como comprador
    $("#usar_pasajero_como_comprador").addEventListener("click", () => {
        // Usamos el primer pasajero (índice 0)
        const primerDni = $("#pasajero_dni_0").value.trim();
        const primerNombre = $("#pasajero_nombre_0").value.trim();
        const primerEmail = $("#pasajero_email_0").value.trim();
        const primerCelular = $("#pasajero_celular_0").value.trim();
        if (primerDni) {
            $("#comprador_dni").value = primerDni;
            $("#comprador_nombre").value = primerNombre;
            $("#comprador_email").value = primerEmail;
            $("#comprador_celular").value = primerCelular;
        } else {
            mostrar_aviso("Complete al menos el DNI del primer pasajero", 'error');
        }
    });

    // Evento para confirmar
    $("#confirmar_venta").addEventListener("click", confirmar_venta_modal);

    // Evento para cancelar
    $("#cancelar_venta_modal").addEventListener("click", () => {
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        mostrar_boton_confirmar_venta();
    });
}

// Actualizar visibilidad de cuotas y monto a pagar según método de pago
function actualizar_visibilidad_cuotas() {
    const metodo = $("#metodo_pago").value;
    const cuotas = parseInt($("#cuotas_venta").value);
    const total = parseFloat(window.total_venta || 0);

    if (metodo === 'transferencia') {
        $("#campo_cuotas").style.display = 'none';
        $("#cuotas_venta").value = '1';
        $("#monto_pagado").value = total.toFixed(2);
        $("#monto_pagado").disabled = true;
    } else {
        $("#campo_cuotas").style.display = '';
        if (cuotas === 1) {
            $("#monto_pagado").value = total.toFixed(2);
            $("#monto_pagado").disabled = true;
        } else {
            const valorCuota = total / cuotas;
            $("#monto_pagado").value = valorCuota.toFixed(2);
            $("#monto_pagado").disabled = false;
        }
    }
}

// Generar formularios para cada pasajero
function generar_formularios_pasajeros(asientos) {
    const contenedor = $("#pasajeros_venta");
    contenedor.innerHTML = '<h4>Pasajeros por asiento</h4>';
    asientos.forEach((asiento, index) => {
        const div = document.createElement('div');
        div.className = 'panel';
        div.style.marginTop = '10px';
        div.innerHTML = `
            <h5>Asiento ${asiento.numero} (F${asiento.fila}, C${asiento.columna})</h5>
            <div class="form-grid">
                <div class="field"><label>DNI *</label><input id="pasajero_dni_${index}" value=""></div>
                <div class="field"><label>Nombre *</label><input id="pasajero_nombre_${index}" value=""></div>
                <div class="field"><label>Email</label><input id="pasajero_email_${index}" value=""></div>
                <div class="field"><label>Celular *</label><input id="pasajero_celular_${index}" value=""></div>
                <div class="field"><label>Celular Emergencia *</label><input id="pasajero_emergencia_${index}" value=""></div>
                <div class="field"><label>Fecha de nacimiento *</label><input type="date" id="pasajero_fecha_nacimiento_${index}" value=""></div>
            </div>
            <div style="margin-top:10px; display:flex; align-items:center; gap:10px;">
                <label style="margin:0;">¿Padece algún problema de salud?</label>
                <button type="button" class="btn" id="btn_ficha_salud_${index}" data-index="${index}">Anexar ficha de salud</button>
            </div>
            <div id="ficha_salud_${index}" style="display:none; margin-top:10px;">
                <h5>Datos de salud</h5>
                <div>
                    <label>¿Padece alguna enfermedad crónica o tiene secuelas de alguna que ha tenido?</label>
                    <input type="checkbox" class="check_enfermedad" data-index="${index}">
                    <div class="enfermedades_container" data-index="${index}" style="display:none;"></div>
                </div>
                <div>
                    <label>¿Está medicado en tratamiento médico o psiquiátrico?</label>
                    <input type="checkbox" class="check_medicamento" data-index="${index}">
                    <div class="medicamentos_container" data-index="${index}" style="display:none;"></div>
                </div>
                <div>
                    <label>¿Posee algún impedimento físico?</label>
                    <input type="checkbox" class="check_impedimento" data-index="${index}">
                    <div class="impedimentos_container" data-index="${index}" style="display:none;"></div>
                </div>
            </div>
        `;
        contenedor.appendChild(div);

        // Listener para marcar fecha completada al cambiar
        const fechaInput = div.querySelector(`#pasajero_fecha_nacimiento_${index}`);
        if (fechaInput) {
            fechaInput.addEventListener('change', function() {
                this.dataset.completado = 'true';
            });
        }
    });

    // Eventos para fichas de salud
    document.querySelectorAll('[id^="btn_ficha_salud_"]').forEach(boton => {
        boton.addEventListener('click', () => {
            const index = boton.dataset.index;
            const contenedorFicha = document.getElementById(`ficha_salud_${index}`);
            contenedorFicha.style.display = contenedorFicha.style.display === 'none' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.check_enfermedad').forEach(check => {
        check.addEventListener('change', function() {
            const index = this.dataset.index;
            const contenedor = document.querySelector(`.enfermedades_container[data-index="${index}"]`);
            contenedor.style.display = this.checked ? 'block' : 'none';
            if (this.checked) agregarInputSalud(contenedor, 'enfermedad', index);
        });
    });

    document.querySelectorAll('.check_medicamento').forEach(check => {
        check.addEventListener('change', function() {
            const index = this.dataset.index;
            const contenedor = document.querySelector(`.medicamentos_container[data-index="${index}"]`);
            contenedor.style.display = this.checked ? 'block' : 'none';
            if (this.checked) agregarInputSalud(contenedor, 'medicamento', index);
        });
    });

    document.querySelectorAll('.check_impedimento').forEach(check => {
        check.addEventListener('change', function() {
            const index = this.dataset.index;
            const contenedor = document.querySelector(`.impedimentos_container[data-index="${index}"]`);
            contenedor.style.display = this.checked ? 'block' : 'none';
            if (this.checked) agregarInputSalud(contenedor, 'impedimento', index);
        });
    });
}

function agregarInputSalud(contenedor, tipo, index) {
    const div = document.createElement('div');
    div.className = 'input_salud';
    div.style.marginBottom = '5px';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = `salud_${tipo}`;
    input.dataset.index = index;
    input.placeholder = tipo === 'enfermedad' ? '¿Cuál?' : (tipo === 'medicamento' ? 'Nombre del medicamento' : '¿Cuál?');

    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'btn small';
    boton.textContent = 'Agregar otra';
    boton.dataset.index = index;
    boton.dataset.tipo = tipo;

    let esAgregar = true;

    boton.addEventListener('click', () => {
        if (esAgregar) {
            // Agregar un nuevo ítem
            agregarInputSalud(contenedor, tipo, index);
            // Cambiar a modo quitar
            boton.textContent = 'Quitar';
            boton.className = 'btn small danger';
            esAgregar = false;
        } else {
            // Quitar este ítem
            div.remove();
        }
    });

    div.appendChild(input);
    div.appendChild(boton);
    contenedor.appendChild(div);
}

// Confirmar la venta
async function confirmar_venta_modal() {
    // Forzar actualización de todos los inputs de fecha
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.blur();
        input.dispatchEvent(new Event('change'));
    });
    if (document.activeElement && document.activeElement.blur) {
        document.activeElement.blur();
    }

    // Obtener datos del comprador
    const comprador_dni = $("#comprador_dni").value.trim();
    const comprador_nombre = $("#comprador_nombre").value.trim();
    const comprador_email = $("#comprador_email").value.trim();
    const comprador_celular = $("#comprador_celular").value.trim();

    // Validar comprador
    if (!comprador_dni || !comprador_nombre || !comprador_celular) {
        mostrar_aviso("Complete DNI, nombre y celular del comprador", 'error');
        return;
    }

    // Obtener método de pago y cuotas
    const metodo_pago = $("#metodo_pago").value;
    const cuotas = parseInt($("#cuotas_venta").value);
    const monto_pagado = parseFloat($("#monto_pagado").value);
    const total = parseFloat(window.total_venta || 0);

    if (isNaN(monto_pagado) || monto_pagado <= 0) {
        mostrar_aviso("Ingrese un monto a pagar válido", 'error');
        return;
    }
    if (monto_pagado > total) {
        mostrar_aviso("El monto a pagar no puede superar el total", 'error');
        return;
    }

    // Recopilar pasajeros (usando IDs únicos)
    const pasajeros = [];
    const cantidadPasajeros = document.querySelectorAll('[id^="pasajero_dni_"]').length;

    for (let i = 0; i < cantidadPasajeros; i++) {
        const dni = document.getElementById(`pasajero_dni_${i}`).value.trim();
        const nombre = document.getElementById(`pasajero_nombre_${i}`).value.trim();
        const email = document.getElementById(`pasajero_email_${i}`).value.trim();
        const celular = document.getElementById(`pasajero_celular_${i}`).value.trim();
        const celular_emergencia = document.getElementById(`pasajero_emergencia_${i}`).value.trim();
        const fechaInput = document.getElementById(`pasajero_fecha_nacimiento_${i}`);
        let fecha_nacimiento = document.getElementById(`pasajero_fecha_nacimiento_${i}`).value;
        if (!fecha_nacimiento) {
            const fechaInput = document.getElementById(`pasajero_fecha_nacimiento_${i}`);
            if (fechaInput && fechaInput.valueAsDate) {
                const d = fechaInput.valueAsDate;
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                fecha_nacimiento = `${year}-${month}-${day}`;
            }
        }

        if (!dni) {
            mostrar_aviso(`Complete el DNI del pasajero ${i+1}`, 'error');
            return;
        }
        if (!nombre) {
            mostrar_aviso(`Complete el nombre del pasajero ${i+1}`, 'error');
            return;
        }
        if (!fecha_nacimiento) {
            mostrar_aviso(`Complete la fecha de nacimiento del pasajero ${i+1}`, 'error');
            return;
        }
        if (!celular) {
            mostrar_aviso(`Complete el celular del pasajero ${i+1}`, 'error');
            return;
        }
        if (!celular_emergencia) {
            mostrar_aviso(`Complete el celular de emergencia del pasajero ${i+1}`, 'error');
            return;
        }

        pasajeros.push({
            dni,
            nombre,
            email,
            celular,
            celular_emergencia,
            fecha_nacimiento
        });
    }

    // Crear objeto de datos para enviar
    const datos = {
        accion: "ventas/confirmar",
        nombre_terminal: usuario_actual.nombre_usuario,
        metodo_pago,
        cuotas,
        monto_pagado: monto_pagado.toFixed(2),
        comprador_dni,
        comprador_nombre,
        comprador_email,
        comprador_celular,
        pasajeros: JSON.stringify(pasajeros)
    };

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(datos)
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            mostrar_aviso("Venta confirmada correctamente", 'exito');
            ultima_venta_id = resultado.id_venta;
        } else {
            mostrar_aviso(resultado.error || "Error al confirmar venta", 'error');
        }
    } catch (error) {
        console.error("Error:", error);
        mostrar_aviso("Error de comunicación", 'error');
    } finally {
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        await solicitar_estado_asientos();
        mostrar_boton_confirmar_venta();
        await actualizar_detalle_viaje_actual();
        if (ultima_venta_id) {
            mostrar_opciones_impresion(ultima_venta_id);
        }
    }
}

function mostrar_opciones_impresion(id_venta) {
    const contenedor = $("#opciones_impresion");
    if (!contenedor) return;
    contenedor.classList.remove("hidden");
    $("#btn_imprimir_pasajes").onclick = () => {
        window.open(`index.php?imprimir=1&tipo=pasajes&id_venta=${id_venta}`, '_blank');
    };
    $("#btn_imprimir_cupon").onclick = () => {
        window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${id_venta}`, '_blank');
    };
    $("#btn_cerrar_opciones").onclick = () => {
        contenedor.classList.add("hidden");
    };
}

// ====== Panel Vendidos ======
async function cargar_ventas() {
    if (!usuario_actual) return;

    let tipo, nombre;
    if (usuario_actual.nivel === 'dueno') {
        tipo = 'dueno';
        nombre = usuario_actual.nombre_usuario;
    } else if (usuario_actual.nivel === 'terminal') {
        tipo = 'terminal';
        nombre = usuario_actual.nombre_usuario;
    } else {
        mostrar_aviso("Seleccione un dueño para ver ventas (no implementado)", 'info');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/listar", tipo, nombre })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        llenar_filtro_viajes(datos.ventas);
        llenar_filtro_vendedores(datos.ventas);
        ventas_actuales = datos.ventas;
        renderizar_ventas();
    } else {
        mostrar_aviso(datos.error || "Error al cargar ventas", 'error');
    }
}

function llenar_filtro_viajes(ventas) {
    const select = $("#selector_viaje_vendido");
    select.innerHTML = '<option value="todos">Todos</option>';
    const viajesUnicos = [...new Set(ventas.map(v => v.viaje))];
    viajesUnicos.forEach(viaje => {
        const opcion = document.createElement('option');
        opcion.value = viaje;
        opcion.textContent = viaje;
        select.appendChild(opcion);
    });
    select.onchange = () => renderizar_ventas();
}

function llenar_filtro_vendedores(ventas) {
    const select = $("#filtro_vendedor");
    select.innerHTML = '<option value="Todos">Todos</option>';
    const vendedoresUnicos = [...new Set(ventas.map(v => v.terminal))];
    vendedoresUnicos.forEach(vendedor => {
        const opcion = document.createElement('option');
        opcion.value = vendedor;
        opcion.textContent = vendedor;
        select.appendChild(opcion);
    });
    select.onchange = () => renderizar_ventas();
}

function renderizar_ventas() {
    const filtroViaje = $("#selector_viaje_vendido").value;
    const filtroVendedor = $("#filtro_vendedor").value;
    let ventas_filtradas = ventas_actuales;
    if (filtroViaje !== 'todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.viaje === filtroViaje);
    }
    if (filtroVendedor !== 'Todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.terminal === filtroVendedor);
    }
    const lista = $("#lista_ventas");
    lista.innerHTML = '';
    if (ventas_filtradas.length === 0) {
        lista.innerHTML = '<p style="color:#888; margin:20px;">No hay ventas registradas para los filtros seleccionados.</p>';
        return;
    }
    ventas_filtradas.forEach(venta => {
        const tarjeta = document.createElement('div');
        tarjeta.className = 'sale-card';
        tarjeta.innerHTML = `
            <div class="sale-header"><div class="sale-id">Venta ${venta.id_venta}</div><span class="badge">${venta.terminal}</span></div>
            <div class="sale-grid">
                <div class="sale-metric"><span>Cantidad de asientos</span><b>${venta.cantidad_asientos}</b></div>
                <div class="sale-metric"><span>Monto total</span><b>$${venta.total}</b></div>
                <div class="sale-metric"><span>Fecha</span><b>${venta.fecha}</b></div>
            </div>
            <div class="actions">
                <button class="btn ver_detalle_venta" data-id="${venta.id_venta}">Ver detalle</button>
                <button class="btn danger cancelar_venta" data-id="${venta.id_venta}">Cancelar venta</button>
            </div>`;
        lista.appendChild(tarjeta);
        tarjeta.querySelector('.ver_detalle_venta').addEventListener('click', () => ver_detalle_venta(venta.id_venta));
        tarjeta.querySelector('.cancelar_venta').addEventListener('click', () => cancelar_venta(venta.id_venta));
    });
}

async function ver_detalle_venta(id_venta) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/obtener", id_venta })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        const venta = datos.venta;
        alert(`Detalle de venta ${venta.id_venta}\nTotal: $${venta.total}\nMétodo: ${venta.metodo_pago}\nCuotas: ${venta.cuotas}\nPagado: $${venta.pagado}\nPasajeros: ${venta.asientos.length}`);
    } else {
        mostrar_aviso(datos.error || "Error al obtener detalle", 'error');
    }
}

async function cancelar_venta(id_venta) {
    if (!confirm(`¿Cancelar venta ${id_venta}?`)) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/cancelar", id_venta })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Venta cancelada", 'exito');
        cargar_ventas();
    } else {
        mostrar_aviso(resultado.error || "Error al cancelar", 'error');
    }
}