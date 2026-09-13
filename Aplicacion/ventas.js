/***
 * Funciones de venta, confirmación, listado y cancelación.
 * @version 1.5piloto.34
 */

let ventas_actuales = [];
let ultima_venta_id = null;

// Configuración de pago resuelta para el viaje + terminal actual.
// Se llena al abrir el modal de confirmación de venta.
window.config_pago_actual = {
    permite_efectivo: '1',
    cuotas_efectivo_max: 3,
    permite_transferencia: '1',
    cuotas_transferencia_max: 1,
};

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

// Abrir formulario de venta
async function abrir_modal_confirmacion_venta() {
    if (!microSyncActual || !viaje_seleccionado) return;

    const asientos_seleccionados = estados_asientos_actuales.filter(a => a.estado === 'seleccionado' && a.seleccionado_por === usuario_actual.nombre_usuario);
    if (asientos_seleccionados.length === 0) {
        mostrar_aviso("No tiene asientos seleccionados", 'error');
        return;
    }

    // Resolver configuración de pago: override del terminal > viaje > default
    const config_pago = await resolver_config_pago();
    if (config_pago === null) {
        return; // error ya mostrado
    }
    window.config_pago_actual = config_pago;

    // Determinar si corresponde mostrar el selector de subida/bajada.
    // Solo para terminales que tienen la opción configurada y cuando el
    // viaje tiene un origen definido.
    const origen_viaje = (viaje_seleccionado && viaje_seleccionado.origen) ? String(viaje_seleccionado.origen).trim() : '';
    const punto_predeterminado = (config_pago.punto_subida_bajada || '').trim();
    const mostrar_selector_subida_bajada = (
        usuario_actual.nivel === 'terminal'
        && config_pago.cambiar_punto_predeterminado === '1'
        && punto_predeterminado !== ''
        && origen_viaje !== ''
    );
    window.mostrar_selector_subida_bajada = mostrar_selector_subida_bajada;
    window.punto_subida_bajada_predeterminado = punto_predeterminado;
    window.origen_viaje = origen_viaje;

    const metodos_permitidos = [];
    if (config_pago.permite_efectivo === '1') metodos_permitidos.push('efectivo');
    if (config_pago.permite_transferencia === '1') metodos_permitidos.push('transferencia');
    if (metodos_permitidos.length === 0) {
        mostrar_aviso("No hay métodos de pago habilitados para este viaje", 'error');
        return;
    }
    const metodo_default = metodos_permitidos[0];

    const micro = viaje_seleccionado.micros.find(m => m.nombre_micro === microSyncActual);
    const monto = micro ? parseFloat(micro.monto) : 0;
    const total = monto * asientos_seleccionados.length;
    window.total_venta = total;

    venta_form_abierto = true;
    $("#contenedor_boton_confirmar_venta").classList.add("hidden");
    $("#info_asiento_viaje").classList.add("hidden");
    $("#formulario_confirmacion_venta").classList.remove("hidden");

    // Armar las opciones del select de método
    const opciones_metodo = metodos_permitidos.map(m => {
        const texto = m === 'efectivo' ? 'Efectivo' : 'Transferencia';
        const sel = (m === metodo_default) ? ' selected' : '';
        return `<option value="${m}"${sel}>${texto}</option>`;
    }).join('');

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
                <select id="metodo_pago">${opciones_metodo}</select>
            </div>
            <div class="field" id="campo_cuotas">
                <label id="etiqueta_cuotas">Cantidad de cuotas</label>
                <select id="cuotas_venta"></select>
            </div>
            <div class="field" id="campo_monto_pagado">
                <label>Monto a pagar ahora *</label>
                <input type="number" id="monto_pagado" step="1000" min="0.01" value="">
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

    // Llenar el select de cuotas según el método por defecto
    regenerar_select_cuotas(metodo_default);

    $("#comprador_dni").value = '';
    $("#comprador_nombre").value = '';
    $("#comprador_email").value = '';
    $("#comprador_celular").value = '';

    generar_formularios_pasajeros(asientos_seleccionados);

    $("#metodo_pago").disabled = false;
    venta_form_abierto = true;
    $("#contenedor_boton_confirmar_venta").classList.add("hidden");

    actualizar_visibilidad_cuotas();

    $("#metodo_pago").addEventListener("change", function() {
        regenerar_select_cuotas(this.value);
        actualizar_visibilidad_cuotas();
    });
    $("#cuotas_venta").addEventListener("change", function() {
        actualizar_visibilidad_cuotas();
    });

    $("#usar_pasajero_como_comprador").addEventListener("click", () => {
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

    $("#confirmar_venta").addEventListener("click", confirmar_venta_modal);

    $("#cancelar_venta_modal").addEventListener("click", () => {
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        mostrar_boton_confirmar_venta();
    });
}

/**
 * Pide al backend las opciones por terminal y resuelve la configuración
 * efectiva de pago: override del terminal > config del viaje > default.
 * Devuelve null si hubo error (ya mostrado al usuario).
 */
async function resolver_config_pago() {
    const config_default = {
        permite_efectivo: '1',
        cuotas_efectivo_max: '3',
        permite_transferencia: '1',
        cuotas_transferencia_max: '1',
    };

    const opciones_viaje = (viaje_seleccionado && viaje_seleccionado.opciones_avanzadas) ? viaje_seleccionado.opciones_avanzadas : {};

    let opciones_terminal = {};
    try {
        const nombre_dueno = (usuario_actual.nivel === 'terminal')
            ? viaje_seleccionado.dueno
            : obtener_nombre_dueno_actual();
        const nombre_terminal = usuario_actual.nombre_usuario;

        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/obtener_opciones_terminal",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_terminal,
                nombre_dueno
            })
        });
        const datos = await resp.json();
        if (datos.exito && datos.opciones) {
            opciones_terminal = datos.opciones;
        }
    } catch (e) {
        console.error("Error al obtener opciones por terminal:", e);
        // No es bloqueante: cae a la config del viaje.
    }

    const resolver = (campo, def) => {
        if (opciones_terminal[campo] !== undefined && opciones_terminal[campo] !== '' && opciones_terminal[campo] !== null) {
            return String(opciones_terminal[campo]);
        }
        if (opciones_viaje[campo] !== undefined && opciones_viaje[campo] !== '' && opciones_viaje[campo] !== null) {
            return String(opciones_viaje[campo]);
        }
        return def;
    };

    // El punto de subida/bajada no es un override de "condiciones de pago",
    // sino que viene directamente del TerminalViaje. Si no está configurado,
    // no se muestra el selector al vender.
    const cambiar_punto = (opciones_terminal['cambiar_punto_predeterminado'] !== undefined)
        ? String(opciones_terminal['cambiar_punto_predeterminado'])
        : '0';
    const punto_subida_bajada = (opciones_terminal['punto_subida_bajada'] !== undefined)
        ? String(opciones_terminal['punto_subida_bajada'])
        : '';

    return {
        permite_efectivo: resolver('permite_efectivo', config_default.permite_efectivo),
        cuotas_efectivo_max: resolver('cuotas_efectivo_max', config_default.cuotas_efectivo_max),
        permite_transferencia: resolver('permite_transferencia', config_default.permite_transferencia),
        cuotas_transferencia_max: resolver('cuotas_transferencia_max', config_default.cuotas_transferencia_max),
        cambiar_punto_predeterminado: cambiar_punto,
        punto_subida_bajada: punto_subida_bajada,
    };
}

/**
 * Regenera las opciones del select de cuotas según el método elegido.
 * También ajusta la etiqueta del campo para que se vea el rango.
 */
function regenerar_select_cuotas(metodo) {
    const select = $("#cuotas_venta");
    const etiqueta = $("#etiqueta_cuotas");
    if (!select) return;

    const config = window.config_pago_actual;
    let max = 1;
    if (metodo === 'efectivo') {
        max = parseInt(config.cuotas_efectivo_max) || 1;
    } else if (metodo === 'transferencia') {
        max = parseInt(config.cuotas_transferencia_max) || 1;
    }
    if (max < 1) max = 1;

    let html = '';
    for (let i = 1; i <= max; i++) {
        html += `<option value="${i}">${i}</option>`;
    }
    select.innerHTML = html;
    select.value = '1';

    if (etiqueta) {
        etiqueta.textContent = `Cantidad de cuotas (1-${max})`;
    }
}

/**
 * Actualiza la visibilidad del campo de cuotas y el valor/habilitación
 * del monto a pagar según el método de pago y la cantidad de cuotas.
 */
function actualizar_visibilidad_cuotas() {
    const metodo = $("#metodo_pago").value;
    const cuotas = parseInt($("#cuotas_venta").value);
    const total = parseFloat(window.total_venta || 0);

    if (metodo === 'transferencia' && window.config_pago_actual.cuotas_transferencia_max === 1) {
        // Transferencia con un solo pago: monto bloqueado al total.
        $("#campo_cuotas").style.display = 'none';
        $("#monto_pagado").value = total.toFixed(2);
        $("#monto_pagado").disabled = true;
    } else if (metodo === 'efectivo' && window.config_pago_actual.cuotas_efectivo_max === 1) {
        // Efectivo de un solo pago: monto bloqueado al total.
        $("#campo_cuotas").style.display = 'none';
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

// Generar formularios para cada pasajero (incluye nuevos campos y ficha ampliada)
function generar_formularios_pasajeros(asientos) {
    const contenedor = $("#pasajeros_venta");
    contenedor.innerHTML = '<h4>Pasajeros por asiento</h4>';

    // Obtener opción de mostrar ficha médica desde opciones avanzadas del viaje
    const opciones = viaje_seleccionado?.opciones_avanzadas;
    const mostrarFichaMedica = opciones && opciones.mostrar_ficha_medica === '1';

    asientos.forEach((asiento, index) => {
        const div = document.createElement('div');
        div.className = 'panel';
        div.style.marginTop = '10px';

        // Construir HTML base del pasajero
        let html = `
            <h5>Asiento ${asiento.numero} (F${asiento.fila}, C${asiento.columna})</h5>
            <div class="form-grid">
                <div class="field"><label>DNI *</label><input id="pasajero_dni_${index}" value=""></div>
                <div class="field"><label>Nombre *</label><input id="pasajero_nombre_${index}" value=""></div>
                <div class="field"><label>Email</label><input id="pasajero_email_${index}" value=""></div>
                <div class="field"><label>Celular *</label><input id="pasajero_celular_${index}" value=""></div>
                <div class="field"><label>Celular Emergencia *</label><input id="pasajero_emergencia_${index}" value=""></div>
                <div class="field"><label>Fecha de nacimiento *</label><input type="date" id="pasajero_fecha_nacimiento_${index}" value=""></div>
                <div class="field"><label>Dirección *</label><input id="pasajero_direccion_${index}" value=""></div>
                <div class="field"><label>Localidad *</label><input id="pasajero_localidad_${index}" value=""></div>
            </div>
        `;

        // Selector de punto de subida/bajada (solo para terminales autorizadas
        // con la opción configurada). Muestra dos opciones: la parada
        // predeterminada (preseleccionada, con su hora estimada si existe) y
        // el origen del viaje.
        if (window.mostrar_selector_subida_bajada) {
            const predeterminado = window.punto_subida_bajada_predeterminado || '';
            const origen = window.origen_viaje || '';

            // Resolver la hora estimada de la parada predeterminada
            let hora_predeterminada = '';
            const paradas_viaje = (viaje_seleccionado && Array.isArray(viaje_seleccionado.paradas_intermedias))
                ? viaje_seleccionado.paradas_intermedias
                : [];
            for (const p of paradas_viaje) {
                const nombre_p = (typeof p === 'string') ? p : (p.nombre || '');
                if (nombre_p === predeterminado) {
                    hora_predeterminada = (typeof p === 'string') ? '' : (p.hora_estimada || '');
                    break;
                }
            }

            if (predeterminado === origen) {
                // Caso borde: la parada predeterminada coincide con el origen.
                // Se muestra como texto fijo, sin select.
                html += `
                    <div class="field" style="margin-top:10px;">
                        <label>Sube/baja en:</label>
                        <div class="small muted">${predeterminado}</div>
                    </div>
                `;
            } else {
                // Parada predeterminada primero (preseleccionada), origen después.
                const texto_predeterminado = hora_predeterminada
                    ? `${predeterminado} (${hora_predeterminada})`
                    : `${predeterminado} (a confirmar)`;
                const opciones = `
                    <option value="${predeterminado}" selected>${texto_predeterminado}</option>
                    <option value="${origen}">${origen}</option>
                `;
                html += `
                    <div class="field" style="margin-top:10px;">
                        <label>Sube/baja en:</label>
                        <select id="pasajero_punto_subida_bajada_${index}">${opciones}</select>
                    </div>
                `;
            }
        }

        // Agregar sección de ficha médica solo si está habilitada
        if (mostrarFichaMedica) {
            html += `
                <div style="margin-top:10px; display:flex; align-items:center; gap:10px;">
                    <label style="margin:0;">¿Padece algún problema de salud?</label>
                    <button type="button" class="btn" id="btn_ficha_salud_${index}" data-index="${index}">Anexar ficha de salud</button>
                </div>
                <div id="ficha_salud_${index}" style="display:none; margin-top:10px;">
                    <h5>Datos de salud</h5>
                    <div class="seccion-salud">
                        <label>Grupo sanguíneo</label>
                        <select id="pasajero_grupo_sanguineo_${index}">
                            <option value="">Seleccione...</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="Desconocido" selected>Desconocido</option>
                        </select>
                    </div>
                    <div class="seccion-salud">
                        <label>Obra social o prepaga (incluya numero de emergencias si corresponde)</label>
                        <input type="text" id="pasajero_obra_social_${index}" value="">
                    </div>
                    <div class="seccion-salud">
                        <label>¿Tiene algún tipo de alergia?</label>
                        <input type="checkbox" class="check_alergia" data-index="${index}">
                        <input type="text" id="pasajero_alergias_${index}" placeholder="Detalle" style="display:none;">
                    </div>
                    <div class="seccion-salud">
                        <label>¿Padece alguna enfermedad crónica o tiene secuelas de alguna que ha tenido?</label>
                        <input type="checkbox" class="check_enfermedad" data-index="${index}">
                        <input type="text" id="pasajero_enfermedades_${index}" placeholder="Detalle" style="display:none;">
                    </div>
                    <div class="seccion-salud">
                        <label>¿Está tomando algún medicamento? ¿Cual/es? ¿En qué horarios?</label>
                        <input type="checkbox" class="check_medicamento" data-index="${index}">
                        <input type="text" id="pasajero_medicamentos_${index}" placeholder="Detalle" style="display:none;">
                    </div>
                    <div class="seccion-salud">
                        <label>¿Posee algún impedimento físico?</label>
                        <input type="checkbox" class="check_impedimento" data-index="${index}">
                        <input type="text" id="pasajero_impedimentos_${index}" placeholder="Detalle" style="display:none;">
                    </div>
                    <div class="seccion-salud">
                        <label>¿Sigue algún regimen especial de comida?</label>
                        <input type="checkbox" class="check_regimen_comida" data-index="${index}">
                        <input type="text" id="pasajero_regimenes_comida_${index}" placeholder="Detalle" style="display:none;">
                    </div>
                    <div class="seccion-salud">
                        <label>Algún otro dato que considere importante:</label>
                        <textarea id="pasajero_observaciones_${index}" rows="2"></textarea>
                    </div>
                </div>
            `;
        }

        div.innerHTML = html;
        contenedor.appendChild(div);

        // Listener para marcar fecha completada al cambiar
        const fechaInput = div.querySelector(`#pasajero_fecha_nacimiento_${index}`);
        if (fechaInput) {
            fechaInput.addEventListener('change', function() {
                this.dataset.completado = 'true';
            });
        }
    });

    // Eventos para mostrar/ocultar ficha de salud (solo si existen)
    document.querySelectorAll('[id^="btn_ficha_salud_"]').forEach(boton => {
        boton.addEventListener('click', () => {
            const index = boton.dataset.index;
            const contenedorFicha = document.getElementById(`ficha_salud_${index}`);
            contenedorFicha.style.display = contenedorFicha.style.display === 'none' ? 'block' : 'none';
        });
    });

    // Eventos para mostrar/ocultar inputs según checkbox
    const checkboxes = [
        ['check_alergia', 'alergias'],
        ['check_enfermedad', 'enfermedades'],
        ['check_medicamento', 'medicamentos'],
        ['check_impedimento', 'impedimentos'],
        ['check_regimen_comida', 'regimenes_comida']
    ];

    checkboxes.forEach(([checkClass, campo]) => {
        document.querySelectorAll(`.${checkClass}`).forEach(check => {
            check.addEventListener('change', function() {
                const index = this.dataset.index;
                const input = document.getElementById(`pasajero_${campo}_${index}`);
                if (input) {
                    input.style.display = this.checked ? '' : 'none';
                    if (!this.checked) input.value = '';
                }
            });
        });
    });
}

// Función para agregar inputs de listas (enfermedad, medicamento, impedimento, alergia)
function agregarInputSalud(contenedor, tipo, index) {
    const div = document.createElement('div');
    div.className = 'input_salud';
    div.style.marginBottom = '5px';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = `salud_${tipo}`;
    input.dataset.index = index;
    input.placeholder = tipo === 'enfermedad' ? '¿Cuál?' : (tipo === 'medicamento' ? 'Nombre del medicamento' : (tipo === 'impedimento' ? '¿Cuál?' : '¿Cuál?'));

    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'btn small';
    boton.textContent = 'Agregar otra';
    boton.dataset.index = index;
    boton.dataset.tipo = tipo;

    let esAgregar = true;

    boton.addEventListener('click', () => {
        if (esAgregar) {
            agregarInputSalud(contenedor, tipo, index);
            boton.textContent = 'Quitar';
            boton.className = 'btn small danger';
            esAgregar = false;
        } else {
            div.remove();
        }
    });

    div.appendChild(input);
    div.appendChild(boton);
    contenedor.appendChild(div);
}

// Confirmar la venta (recopila datos del comprador y de cada pasajero, incluyendo ficha de salud)
async function confirmar_venta_modal() {
    const comprador_dni = $("#comprador_dni").value.trim();
    const comprador_nombre = $("#comprador_nombre").value.trim();
    const comprador_email = $("#comprador_email").value.trim();
    const comprador_celular = $("#comprador_celular").value.trim();

    if (!comprador_dni || !comprador_nombre || !comprador_celular) {
        mostrar_aviso("Complete DNI, nombre y celular del comprador", 'error');
        return;
    }

    const metodo_pago = $("#metodo_pago").value;
    const cuotas = parseInt($("#cuotas_venta").value);
    const monto_pagado = parseFloat($("#monto_pagado").value);
    const total = parseFloat(window.total_venta || 0);

    // Validación rápida contra la config resuelta (el backend igual valida)
    const config = window.config_pago_actual;
    if (metodo_pago === 'efectivo' && config.permite_efectivo !== '1') {
        mostrar_aviso("El pago en efectivo no está permitido", 'error');
        return;
    }
    if (metodo_pago === 'transferencia' && config.permite_transferencia !== '1') {
        mostrar_aviso("La transferencia no está permitida", 'error');
        return;
    }
    const max_cuotas = (metodo_pago === 'efectivo')
        ? parseInt(config.cuotas_efectivo_max)
        : parseInt(config.cuotas_transferencia_max);
    if (isNaN(cuotas) || cuotas < 1 || cuotas > max_cuotas) {
        mostrar_aviso(`Cantidad de cuotas inválida (1-${max_cuotas})`, 'error');
        return;
    }

    if (isNaN(monto_pagado) || monto_pagado <= 0) {
        mostrar_aviso("Ingrese un monto a pagar válido", 'error');
        return;
    }
    if (monto_pagado > total) {
        mostrar_aviso("El monto a pagar no puede superar el total", 'error');
        return;
    }

    const pasajeros = [];
    const cantidadPasajeros = document.querySelectorAll('[id^="pasajero_dni_"]').length;

    for (let i = 0; i < cantidadPasajeros; i++) {
        const dni = document.getElementById(`pasajero_dni_${i}`).value.trim();
        const nombre = document.getElementById(`pasajero_nombre_${i}`).value.trim();
        const email = document.getElementById(`pasajero_email_${i}`).value.trim();
        const celular = document.getElementById(`pasajero_celular_${i}`).value.trim();
        const celular_emergencia = document.getElementById(`pasajero_emergencia_${i}`).value.trim();
        const direccion = document.getElementById(`pasajero_direccion_${i}`).value.trim();
        const localidad = document.getElementById(`pasajero_localidad_${i}`).value.trim();
        const fechaInput = document.getElementById(`pasajero_fecha_nacimiento_${i}`);

        let fecha_nacimiento = '';
        if (fechaInput) {
            fecha_nacimiento = fechaInput.value;
            if (!fecha_nacimiento && fechaInput.valueAsDate) {
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
        if (!direccion || !localidad) {
            mostrar_aviso(`Complete dirección y localidad del pasajero ${i+1}`, 'error');
            return;
        }

        const pasajeroData = {
            dni,
            nombre,
            email,
            celular,
            celular_emergencia,
            fecha_nacimiento,
            direccion,
            localidad
        };

        // Punto de subida/bajada (opcional según la terminal)
        if (window.mostrar_selector_subida_bajada) {
            const select_sb = document.getElementById(`pasajero_punto_subida_bajada_${i}`);
            if (select_sb) {
                pasajeroData.punto_subida_bajada = select_sb.value;
            } else {
                // Caso borde: parada === origen. Se usa la parada predeterminada.
                pasajeroData.punto_subida_bajada = window.punto_subida_bajada_predeterminado || '';
            }
        }

        // Incluir ficha de salud solo si existe el contenedor
        const fichaSaludDiv = document.getElementById(`ficha_salud_${i}`);
        if (fichaSaludDiv) {
            pasajeroData.salud = {
                grupo_sanguineo: document.getElementById(`pasajero_grupo_sanguineo_${i}`)?.value || '',
                obra_social: document.getElementById(`pasajero_obra_social_${i}`)?.value.trim() || '',
                alergias: document.getElementById(`pasajero_alergias_${i}`)?.value.trim() || '',
                enfermedades: document.getElementById(`pasajero_enfermedades_${i}`)?.value.trim() || '',
                medicamentos: document.getElementById(`pasajero_medicamentos_${i}`)?.value.trim() || '',
                impedimentos: document.getElementById(`pasajero_impedimentos_${i}`)?.value.trim() || '',
                regimenes_comida: document.getElementById(`pasajero_regimenes_comida_${i}`)?.value.trim() || '',
                observaciones: document.getElementById(`pasajero_observaciones_${i}`)?.value.trim() || ''
            };
        }

        pasajeros.push(pasajeroData);
    }

    const fecha_actual = formatear_fecha_hora_actual();

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
        pasajeros: JSON.stringify(pasajeros),
        fecha_hora: fecha_actual
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

            // Cerrar formulario y limpiar
            $("#formulario_confirmacion_venta").classList.add("hidden");
            $("#info_asiento_viaje").classList.remove("hidden");
            venta_form_abierto = false;
            await solicitar_estado_asientos();
            mostrar_boton_confirmar_venta();
            await actualizar_detalle_viaje_actual();

            if (ultima_venta_id) {
                mostrar_opciones_impresion(ultima_venta_id);
            }
        } else {
            // Mostrar error sin cerrar formulario
            mostrar_aviso(resultado.error || "Error al confirmar venta", 'error');
        }
    } catch (error) {
        console.error("Error:", error);
        mostrar_aviso("Error de comunicación", 'error');
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

function formatear_fecha_hora_actual() {
    const ahora = new Date();
    const dia = String(ahora.getDate()).padStart(2, '0');
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const anio = ahora.getFullYear();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
}