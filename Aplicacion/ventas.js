/***
 * Funciones de venta, confirmación, listado y cancelación.
 * @version 1.5piloto.15
 */

let ventas_actuales = [];

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
            <div class="field" id="campo_pago_inicial">
                <label>Cuotas pagadas ahora</label>
                <select id="pago_inicial">
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
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
    $("#pago_inicial").value = '1';
    actualizar_visibilidad_cuotas();

    // Limpiar campos del comprador
    $("#comprador_dni").value = '';
    $("#comprador_nombre").value = '';
    $("#comprador_email").value = '';
    $("#comprador_celular").value = '';

    generar_formularios_pasajeros(asientos_seleccionados);

    // Eventos para los nuevos elementos
    $("#metodo_pago").addEventListener("change", actualizar_visibilidad_cuotas);
    $("#cuotas_venta").addEventListener("change", function() {
        const cuotas = parseInt(this.value);
        const selectPago = $("#pago_inicial");
        selectPago.innerHTML = '';
        for (let i = 0; i <= cuotas; i++) {
            const opcion = document.createElement('option');
            opcion.value = i;
            opcion.textContent = i;
            selectPago.appendChild(opcion);
        }
    });
    $("#cuotas_venta").dispatchEvent(new Event('change'));

    // Evento para usar pasajero como comprador
    $("#usar_pasajero_como_comprador").addEventListener("click", () => {
        const primerDni = document.querySelector('.pasajero_dni').value;
        const primerNombre = document.querySelector('.pasajero_nombre').value;
        const primerEmail = document.querySelector('.pasajero_email').value;
        const primerCelular = document.querySelector('.pasajero_celular').value;
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

// Actualizar visibilidad de cuotas según método de pago
function actualizar_visibilidad_cuotas() {
    const metodo = $("#metodo_pago").value;
    if (metodo === 'transferencia') {
        $("#campo_cuotas").style.display = 'none';
        $("#campo_pago_inicial").style.display = 'none';
        $("#cuotas_venta").value = '1';
        $("#pago_inicial").value = '1';
    } else {
        $("#campo_cuotas").style.display = '';
        $("#campo_pago_inicial").style.display = '';
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
                <div class="field"><label>DNI *</label><input class="pasajero_dni" data-index="${index}" value=""></div>
                <div class="field"><label>Nombre *</label><input class="pasajero_nombre" data-index="${index}" value=""></div>
                <div class="field"><label>Email</label><input class="pasajero_email" data-index="${index}" value=""></div>
                <div class="field"><label>Celular *</label><input class="pasajero_celular" data-index="${index}" value=""></div>
                <div class="field"><label>Celular Emergencia *</label><input class="pasajero_emergencia" data-index="${index}" value=""></div>
                <div class="field"><label>Fecha de nacimiento *</label><input type="date" class="pasajero_fecha_nacimiento" data-index="${index}" value=""></div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// Confirmar la venta
async function confirmar_venta_modal() {
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
    const pago_inicial = parseInt($("#pago_inicial").value);

    // Recopilar pasajeros
    const pasajeros = [];
    const dniInputs = document.querySelectorAll('.pasajero_dni');
    for (let i = 0; i < dniInputs.length; i++) {
        const dni = dniInputs[i].value.trim();
        const nombre = document.querySelector(`.pasajero_nombre[data-index="${i}"]`).value.trim();
        const fecha_nacimiento = document.querySelector(`.pasajero_fecha_nacimiento[data-index="${i}"]`).value.trim();
        const celular = document.querySelector(`.pasajero_celular[data-index="${i}"]`).value.trim();
        const celular_emergencia = document.querySelector(`.pasajero_emergencia[data-index="${i}"]`).value.trim();
        const email = document.querySelector(`.pasajero_email[data-index="${i}"]`).value.trim();

        // Validar obligatorios
        if (!dni || !nombre || !fecha_nacimiento || !celular || !celular_emergencia) {
            mostrar_aviso(`Complete todos los datos obligatorios del pasajero ${i+1}`, 'error');
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
        pago_inicial,
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
        } else {
            mostrar_aviso(resultado.error || "Error al confirmar venta", 'error');
        }
    } catch (error) {
        console.error("Error:", error);
        mostrar_aviso("Error de comunicación", 'error');
    } finally {
        // Ocultar formulario y limpiar
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        // Actualizar estados
        await solicitar_estado_asientos();
        // Mostrar botón Vender si corresponde
        mostrar_boton_confirmar_venta();
        // Actualizar detalle del viaje para reflejar contadores
        await actualizar_detalle_viaje_actual();
    }
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