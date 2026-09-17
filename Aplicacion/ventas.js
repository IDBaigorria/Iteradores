/***
 * Funciones de venta, confirmación, listado y cancelación.
 * @version 1.5piloto.43
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
                <div class="field"><label>Apellido *</label><input id="comprador_apellido"></div>
                <div class="field full"><label>Nombres *</label><input id="comprador_nombres"></div>
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
    $("#comprador_apellido").value = '';
    $("#comprador_nombres").value = '';
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
        const primerApellido = $("#pasajero_apellido_0").value.trim();
        const primerNombres = $("#pasajero_nombres_0").value.trim();
        const primerEmail = $("#pasajero_email_0").value.trim();
        const primerCelular = $("#pasajero_celular_0").value.trim();
        if (primerDni) {
            $("#comprador_dni").value = primerDni;
            $("#comprador_apellido").value = primerApellido;
            $("#comprador_nombres").value = primerNombres;
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

/**
 * Construye el HTML de los campos de un formulario de pasajero.
 *
 * Reutilizable desde el flujo de venta y desde el flujo de asignación
 * de pasajero a una reserva. Devuelve solo los campos (form-grid,
 * selector de subida/bajada y bloque de ficha médica), sin envoltorio
 * ni título. El llamador decide cómo envolverlo.
 *
 * @param {number} index Índice del pasajero (para los IDs únicos).
 * @param {object} opciones Opciones:
 *   - incluir_selector_sb: bool, incluir el select de punto de subida/bajada.
 *   - incluir_ficha: bool, incluir el bloque de ficha médica.
 * @returns {string} HTML del formulario.
 */
function construir_html_formulario_pasajero(index, opciones = {}) {
    const incluir_selector_sb = opciones.incluir_selector_sb === true;
    const incluir_ficha = opciones.incluir_ficha === true;

    let html = `
        <div class="form-grid">
            <div class="field"><label>DNI *</label><input id="pasajero_dni_${index}" value=""></div>
            <div class="field"><label>Apellido *</label><input id="pasajero_apellido_${index}" value=""></div>
            <div class="field full"><label>Nombres *</label><input id="pasajero_nombres_${index}" value=""></div>
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
    if (incluir_selector_sb && window.mostrar_selector_subida_bajada) {
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
            const opciones_select = `
                <option value="${predeterminado}" selected>${texto_predeterminado}</option>
                <option value="${origen}">${origen}</option>
            `;
            html += `
                <div class="field" style="margin-top:10px;">
                    <label>Sube/baja en:</label>
                    <select id="pasajero_punto_subida_bajada_${index}">${opciones_select}</select>
                </div>
            `;
        }
    }

    // Bloque de ficha médica (opcional)
    if (incluir_ficha) {
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

    return html;
}

/**
 * Conecta los listeners internos de un formulario de pasajero (ficha médica).
 * Se debe llamar una vez que el HTML ya está insertado en el DOM.
 *
 * @param {HTMLElement} contenedor El elemento que contiene el formulario del pasajero.
 * @param {number} index Índice del pasajero.
 */
function conectar_listeners_formulario_pasajero(contenedor, index) {
    // Toggle de la ficha médica
    const btnFicha = contenedor.querySelector(`#btn_ficha_salud_${index}`);
    if (btnFicha) {
        btnFicha.addEventListener('click', () => {
            const contenedorFicha = contenedor.querySelector(`#ficha_salud_${index}`);
            if (contenedorFicha) {
                contenedorFicha.style.display = contenedorFicha.style.display === 'none' ? 'block' : 'none';
            }
        });
    }

    // Checkboxes de salud: mostrar/ocultar input asociado
    const pares = [
        ['check_alergia', 'alergias'],
        ['check_enfermedad', 'enfermedades'],
        ['check_medicamento', 'medicamentos'],
        ['check_impedimento', 'impedimentos'],
        ['check_regimen_comida', 'regimenes_comida']
    ];
    pares.forEach(([checkClass, campo]) => {
        contenedor.querySelectorAll(`.${checkClass}`).forEach(check => {
            check.addEventListener('change', function() {
                const input = contenedor.querySelector(`#pasajero_${campo}_${index}`);
                if (input) {
                    input.style.display = this.checked ? '' : 'none';
                    if (!this.checked) input.value = '';
                }
            });
        });
    });

    // Listener para marcar fecha completada al cambiar
    const fechaInput = contenedor.querySelector(`#pasajero_fecha_nacimiento_${index}`);
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            this.dataset.completado = 'true';
        });
    }
}

/**
 * Recopila y valida los datos de un pasajero a partir del formulario en el DOM.
 *
 * Devuelve un objeto { ok, error, datos }:
 *  - ok: true si todo está válido.
 *  - error: mensaje de error si ok es false.
 *  - datos: objeto con los campos del pasajero listos para enviar al backend.
 *
 * @param {number} index Índice del pasajero.
 * @param {object} opciones Opciones:
 *   - incluir_selector_sb: bool, leer el select de punto de subida/bajada.
 *   - incluir_ficha: bool, leer el bloque de ficha médica.
 * @returns {object}
 */
function recolectar_datos_pasajero(index, opciones = {}) {
    const incluir_selector_sb = opciones.incluir_selector_sb === true;
    const incluir_ficha = opciones.incluir_ficha === true;
    const num_pas = index + 1;

    const get_val = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    };

    const dni = get_val(`pasajero_dni_${index}`);
    const apellido = get_val(`pasajero_apellido_${index}`);
    const nombres = get_val(`pasajero_nombres_${index}`);
    const email = get_val(`pasajero_email_${index}`);
    const celular = get_val(`pasajero_celular_${index}`);
    const celular_emergencia = get_val(`pasajero_emergencia_${index}`);
    const direccion = get_val(`pasajero_direccion_${index}`);
    const localidad = get_val(`pasajero_localidad_${index}`);

    const fechaInput = document.getElementById(`pasajero_fecha_nacimiento_${index}`);
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

    let err;
    err = validar_dni_js(dni);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - ${err}`, datos: null };
    err = validar_nombre_o_apellido_js(apellido);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Apellido: ${err}`, datos: null };
    err = validar_nombre_o_apellido_js(nombres);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Nombres: ${err}`, datos: null };
    err = validar_email_js(email);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - ${err}`, datos: null };
    err = validar_telefono_js(celular);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Celular: ${err}`, datos: null };
    err = validar_telefono_js(celular_emergencia);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Celular de emergencia: ${err}`, datos: null };
    err = validar_fecha_nacimiento_js(fecha_nacimiento);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - ${err}`, datos: null };
    err = validar_localidad_js(localidad);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Localidad: ${err}`, datos: null };
    err = validar_direccion_js(direccion);
    if (err) return { ok: false, error: `Pasajero ${num_pas} - Dirección: ${err}`, datos: null };

    const datos = {
        dni,
        apellido,
        nombres,
        email,
        celular,
        celular_emergencia,
        fecha_nacimiento,
        direccion,
        localidad
    };

    // Punto de subida/bajada (opcional)
    if (incluir_selector_sb && window.mostrar_selector_subida_bajada) {
        const select_sb = document.getElementById(`pasajero_punto_subida_bajada_${index}`);
        if (select_sb) {
            datos.punto_subida_bajada = select_sb.value;
        } else {
            // Caso borde: parada === origen. Se usa la parada predeterminada.
            datos.punto_subida_bajada = window.punto_subida_bajada_predeterminado || '';
        }
    }

    // Ficha de salud (opcional)
    if (incluir_ficha) {
        const fichaSaludDiv = document.getElementById(`ficha_salud_${index}`);
        if (fichaSaludDiv) {
            datos.salud = {
                grupo_sanguineo: document.getElementById(`pasajero_grupo_sanguineo_${index}`)?.value || '',
                obra_social: document.getElementById(`pasajero_obra_social_${index}`)?.value.trim() || '',
                alergias: document.getElementById(`pasajero_alergias_${index}`)?.value.trim() || '',
                enfermedades: document.getElementById(`pasajero_enfermedades_${index}`)?.value.trim() || '',
                medicamentos: document.getElementById(`pasajero_medicamentos_${index}`)?.value.trim() || '',
                impedimentos: document.getElementById(`pasajero_impedimentos_${index}`)?.value.trim() || '',
                regimenes_comida: document.getElementById(`pasajero_regimenes_comida_${index}`)?.value.trim() || '',
                observaciones: document.getElementById(`pasajero_observaciones_${index}`)?.value.trim() || ''
            };
        }
    }

    return { ok: true, error: null, datos };
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

        const titulo = `<h5>Asiento ${asiento.numero} (F${asiento.fila}, C${asiento.columna})</h5>`;
        const html_campos = construir_html_formulario_pasajero(index, {
            incluir_selector_sb: true,
            incluir_ficha: mostrarFichaMedica
        });

        div.innerHTML = titulo + html_campos;
        contenedor.appendChild(div);

        conectar_listeners_formulario_pasajero(div, index);
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
    const comprador_apellido = $("#comprador_apellido").value.trim();
    const comprador_nombres = $("#comprador_nombres").value.trim();
    const comprador_email = $("#comprador_email").value.trim();
    const comprador_celular = $("#comprador_celular").value.trim();

    // Validaciones del comprador
    let err;
    err = validar_dni_js(comprador_dni);
    if (err) { mostrar_aviso('Comprador - ' + err, 'error'); return; }
    err = validar_nombre_o_apellido_js(comprador_apellido);
    if (err) { mostrar_aviso('Comprador - Apellido: ' + err, 'error'); return; }
    err = validar_nombre_o_apellido_js(comprador_nombres);
    if (err) { mostrar_aviso('Comprador - Nombres: ' + err, 'error'); return; }
    err = validar_email_js(comprador_email);
    if (err) { mostrar_aviso('Comprador - ' + err, 'error'); return; }
    err = validar_telefono_js(comprador_celular);
    if (err) { mostrar_aviso('Comprador - Celular: ' + err, 'error'); return; }

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
        const r = recolectar_datos_pasajero(i, {
            incluir_selector_sb: true,
            incluir_ficha: true
        });
        if (!r.ok) {
            mostrar_aviso(r.error, 'error');
            return;
        }
        pasajeros.push(r.datos);
    }

    const fecha_actual = formatear_fecha_hora_actual();

    const datos = {
        accion: "ventas/confirmar",
        nombre_terminal: usuario_actual.nombre_usuario,
        metodo_pago,
        cuotas,
        monto_pagado: monto_pagado.toFixed(2),
        comprador_dni,
        comprador_apellido,
        comprador_nombres,
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
            // Refrescar contadores sin reconstruir el modal: así no parpadea
            // el croquis ni el panel de asiento.
            await refrescar_contadores_viaje_actual();

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

/**
 * Configura la visibilidad de los filtros del panel Vendidos según el rol.
 *
 * - Admin: solo el filtro Dueño al principio. Los demás aparecen al elegir dueño.
 * - Dueño: Viaje, Vendedor y Estado visibles.
 * - Terminal: Viaje y Estado visibles (Vendedor oculto).
 */
function configurar_filtros_vendidos() {
    const cont_dueno = document.getElementById('contenedor_filtro_dueno_vendidos');
    const es_admin = usuario_actual.nivel === 'admin';
    if (cont_dueno) cont_dueno.style.display = es_admin ? '' : 'none';
}

/**
 * Muestra u oculta los filtros de Viaje, Vendedor y Estado. Se usa para el
 * admin, que no los ve hasta que elige un dueño.
 *
 * @param {boolean} visibles
 */
function mostrar_filtros_secundarios_vendidos(visibles) {
    const cont_viaje = document.getElementById('contenedor_filtro_viaje_vendidos');
    const cont_vendedor = document.getElementById('contenedor_filtro_vendedor_vendidos');
    const cont_estado = document.getElementById('contenedor_filtro_estado_vendidos');
    const es_dueno = usuario_actual.nivel === 'dueno';
    const es_admin = usuario_actual.nivel === 'admin';

    if (cont_viaje) cont_viaje.style.display = visibles ? '' : 'none';
    // Vendedor: visible para admin y dueño, no para terminal.
    if (cont_vendedor) cont_vendedor.style.display = (visibles && (es_admin || es_dueno)) ? '' : 'none';
    if (cont_estado) cont_estado.style.display = visibles ? '' : 'none';
}

/**
 * Carga los dueños en el select del filtro Dueño. Solo se llena la primera
 * vez. Al cambiar de dueño, resetea Viaje y Vendedor; el filtro Estado queda
 * como está para que el usuario lo pueda aplicar sobre los datos recargados.
 */
async function cargar_duenos_en_select_vendidos() {
    const select = document.getElementById('selector_dueno_vendidos');
    if (!select) return;

    if (select.options.length > 1) return; // ya está lleno

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_duenos" })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar dueños", 'error');
        return;
    }

    select.innerHTML = '<option value="">Seleccione dueño...</option>';
    datos.duenos.forEach(dueno => {
        const opcion = document.createElement('option');
        opcion.value = dueno.nombre_usuario;
        opcion.textContent = dueno.nombre_real
            ? `${dueno.nombre_real} (${dueno.nombre_usuario})`
            : dueno.nombre_usuario;
        select.appendChild(opcion);
    });

    select.onchange = () => {
        // Resetear Viaje y Vendedor. Estado queda como está.
        const sel_viaje = document.getElementById('selector_viaje_vendido');
        const sel_vendedor = document.getElementById('filtro_vendedor');
        if (sel_viaje) sel_viaje.value = 'todos';
        if (sel_vendedor) sel_vendedor.value = 'Todos';

        if (!select.value) {
            ventas_actuales = [];
            mostrar_filtros_secundarios_vendidos(false);
            const lista = document.getElementById('lista_ventas');
            if (lista) lista.innerHTML = '<p style="color:#888; margin:20px;">Seleccione un dueño para ver las ventas.</p>';
            return;
        }

        mostrar_filtros_secundarios_vendidos(true);
        _cargar_ventas_con_parametros('dueno', select.value);
    };
}

/**
 * Hace el fetch de ventas, llena los filtros de viaje y vendedor, y
 * renderiza. Función reutilizable para dueño, terminal y admin.
 */
async function _cargar_ventas_con_parametros(tipo, nombre) {
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

async function cargar_ventas() {
    if (!usuario_actual) return;

    configurar_filtros_vendidos();

    if (usuario_actual.nivel === 'admin') {
        // Para admin, los filtros secundarios solo se muestran cuando hay un
        // dueño seleccionado. Si ya había uno elegido antes, lo respetamos.
        await cargar_duenos_en_select_vendidos();

        const select = document.getElementById('selector_dueno_vendidos');
        const dueno = select ? select.value : '';

        if (!dueno) {
            mostrar_filtros_secundarios_vendidos(false);
            ventas_actuales = [];
            const lista = document.getElementById('lista_ventas');
            if (lista) lista.innerHTML = '<p style="color:#888; margin:20px;">Seleccione un dueño para ver las ventas.</p>';
            return;
        }
        mostrar_filtros_secundarios_vendidos(true);
        await _cargar_ventas_con_parametros('dueno', dueno);
        return;
    }

    // Dueño y terminal: los filtros secundarios siempre visibles. El chequeo
    // interno de mostrar_filtros_secundarios_vendidos oculta Vendedor para
    // la terminal.
    mostrar_filtros_secundarios_vendidos(true);

    if (usuario_actual.nivel === 'dueno') {
        await _cargar_ventas_con_parametros('dueno', usuario_actual.nombre_usuario);
        return;
    }

    if (usuario_actual.nivel === 'terminal') {
        await _cargar_ventas_con_parametros('terminal', usuario_actual.nombre_usuario);
        return;
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
    // El `value` sigue siendo el nombre de usuario porque es el identificador
    // que usa el filtro. Solo cambia el texto de la opción.
    const mapa_nombres = {};
    ventas.forEach(v => {
        if (v.terminal) {
            mapa_nombres[v.terminal] = v.terminal_nombre_real || v.terminal;
        }
    });
    const vendedoresUnicos = [...new Set(ventas.map(v => v.terminal))];
    vendedoresUnicos.forEach(vendedor => {
        const opcion = document.createElement('option');
        opcion.value = vendedor;
        opcion.textContent = mapa_nombres[vendedor] || vendedor;
        select.appendChild(opcion);
    });
    select.onchange = () => renderizar_ventas();
}

function renderizar_ventas() {
    const filtroViaje = document.getElementById('selector_viaje_vendido').value;
    const filtroVendedor = document.getElementById('filtro_vendedor').value;
    const filtroEstado = document.getElementById('filtro_estado').value;

    let ventas_filtradas = ventas_actuales;
    if (filtroViaje !== 'todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.viaje === filtroViaje);
    }
    if (filtroVendedor !== 'Todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.terminal === filtroVendedor);
    }
    if (filtroEstado !== 'todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.estado_pago === filtroEstado);
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
        // Identificador único para poder ubicar esta tarjeta desde otros flujos
        // (por ejemplo, el botón "Ver compra" de un asiento o del modal de pasajes).
        tarjeta.dataset.idVenta = venta.id_venta;

        // Título de viaje y micro.
        const viaje_visible = venta.viaje_visible || venta.viaje || '';
        const micro_visible = venta.micro_nombre_visible || venta.micro || '';

        // Método de pago: capitalizado.
        const metodo = venta.metodo_pago
            ? (venta.metodo_pago.charAt(0).toUpperCase() + venta.metodo_pago.slice(1))
            : '';
        const cuotas = parseInt(venta.cuotas || '1');
        const cuotas_restantes = parseInt(venta.cuotas_restantes || '0');
        let texto_cuotas = '';
        if (cuotas > 1) {
            texto_cuotas = `${cuotas}`;
            if (cuotas_restantes > 0) {
                texto_cuotas += ` (${cuotas_restantes} pendiente${cuotas_restantes === 1 ? '' : 's'})`;
            } else {
                texto_cuotas += ` (pagadas)`;
            }
        }

        // Comprador: se arma solo si hay datos.
        let comprador_html = '';
        const c = venta.comprador;
        if (c) {
            const nombre = c.nombre_completo || '';
            const celular = c.celular || '';
            const email = c.email || '';
            const direccion_completa = [c.direccion, c.localidad].filter(v => v).join(', ');

            const lineas = [];
            if (nombre) lineas.push(`<div class="sale-comprador-linea"><span>Nombre:</span><b>${nombre}</b></div>`);
            if (celular) lineas.push(`<div class="sale-comprador-linea"><span>Celular:</span><b>${celular}</b></div>`);
            if (email) lineas.push(`<div class="sale-comprador-linea"><span>Email:</span><b>${email}</b></div>`);
            if (direccion_completa) lineas.push(`<div class="sale-comprador-linea"><span>Dirección:</span><b>${direccion_completa}</b></div>`);

            if (lineas.length > 0) {
                comprador_html = `
                    <div class="sale-comprador">
                        <div class="sale-comprador-titulo">Comprador</div>
                        ${lineas.join('')}
                    </div>
                `;
            }
        }

        // Clase extra en la celda Pendiente si hay deuda.
        const pendiente_num = parseFloat(venta.pendiente || '0');
        const clase_pendiente = pendiente_num > 0 ? ' sale-metric-pendiente' : '';

        // Línea de pago: método y cuotas.
        const lineas_pago = [];
        if (metodo) lineas_pago.push(`<span>Método: <b>${metodo}</b></span>`);
        if (texto_cuotas) lineas_pago.push(`<span>Cuotas: <b>${texto_cuotas}</b></span>`);
        const pago_html = lineas_pago.length > 0
            ? `<div class="sale-pago">${lineas_pago.join('')}</div>`
            : '';

        // Bloque de datos del viaje y la fecha, junto al ID.
        const info_partes = [];
        if (viaje_visible) info_partes.push(`<span>Viaje: <b>${viaje_visible}</b></span>`);
        if (micro_visible) info_partes.push(`<span>Micro: <b>${micro_visible}</b></span>`);
        if (venta.fecha) info_partes.push(`<span>Fecha de compra: <b>${venta.fecha}</b></span>`);
            const info_html = info_partes.length > 0
            ? `<div class="sale-header-info">${info_partes.join('<span class="sale-sep">·</span>')}</div>`
            : '';

        tarjeta.innerHTML = `
            <div class="sale-header">
                <div class="sale-header-izq">
                    <div class="sale-id">Venta ${venta.id_venta}</div>
                    ${info_html}
                </div>
                <span class="badge">${venta.terminal_nombre_real || venta.terminal}</span>
            </div>
            ${comprador_html}
            <div class="sale-grid">
                <div class="sale-metric"><span>Asientos</span><b>${venta.cantidad_asientos}</b></div>
                <div class="sale-metric"><span>Monto total</span><b>$${venta.total}</b></div>
                <div class="sale-metric"><span>Abonado</span><b>$${venta.pagado}</b></div>
                <div class="sale-metric${clase_pendiente}"><span>Pendiente</span><b>$${venta.pendiente}</b></div>
            </div>
            ${pago_html}
            <div class="actions sale-acciones">
                <button class="btn ver_detalle_venta" data-id="${venta.id_venta}">Ver cupones</button>
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

/**
 * Navega a la pestaña "Vendidos" y resalta la tarjeta de la venta indicada.
 *
 * Cierra los modales abiertos (genérico, apilado y los chicos flotantes),
 * activa la pestaña, espera a que se carguen las ventas, hace scroll hasta
 * la tarjeta correspondiente y le aplica una animación de resaltado que
 * se desvanece sola.
 *
 * Si la tarjeta no está visible por los filtros aplicados, los resetea
 * (a "Todos") y vuelve a intentar.
 *
 * Pensada para ser llamada desde el botón "Ver compra" de la tarjeta de
 * asiento y del modal de pasajes del pasajero.
 *
 * @param {string} id_venta
 */
/**
 * Navega a la pestaña "Vendidos" y resalta la tarjeta de la venta indicada.
 *
 * Cierra los modales abiertos, activa la pestaña, espera a que se carguen
 * las ventas, aplica los filtros necesarios (Dueño para admin, Viaje si
 * viene), hace scroll hasta la tarjeta y le aplica una animación de
 * resaltado que se desvanece sola.
 *
 * @param {string} id_venta
 * @param {string} nombre_dueno Nombre de usuario del dueño de la venta.
 *                              Necesario para admin.
 * @param {string} nombre_viaje Nombre del viaje (opcional). Si viene, se
 *                              aplica el filtro Viaje para acotar el listado.
 */
async function ir_a_venta_en_vendidos(id_venta, nombre_dueno = '', nombre_viaje = '') {
    if (!id_venta) return;

    // Cerrar modales abiertos (si están abiertos; si no, no pasa nada)
    if (typeof cerrar_modal_generico === 'function') cerrar_modal_generico();
    if (typeof cerrar_modal_apilado === 'function') cerrar_modal_apilado();
    const chico_reserva = document.getElementById('modal_chico_impresion_reserva');
    if (chico_reserva) chico_reserva.classList.add('hidden');
    const chico_pasajero = document.getElementById('modal_chico_impresion_pasajero');
    if (chico_pasajero) chico_pasajero.classList.add('hidden');

    // Si es admin, seleccionar el dueño ANTES de activar la pestaña.
    // La pestaña Vendidos depende de esa selección para cargar las ventas.
    if (usuario_actual.nivel === 'admin' && nombre_dueno) {
        await cargar_duenos_en_select_vendidos();
        const select_dueno = document.getElementById('selector_dueno_vendidos');
        if (select_dueno) select_dueno.value = nombre_dueno;
        mostrar_filtros_secundarios_vendidos(true);
    }

    // Activar la pestaña Vendidos y esperar a que las ventas estén cargadas.
    await activar_pestana('vendidos');

    // Aplicar filtro Viaje si vino, para reducir el listado.
    if (nombre_viaje) {
        const select_viaje = document.getElementById('selector_viaje_vendido');
        if (select_viaje) {
            const existe = Array.from(select_viaje.options).some(o => o.value === nombre_viaje);
            if (existe) select_viaje.value = nombre_viaje;
            renderizar_ventas();
        }
    }

    let panel = document.querySelector(`.sale-card[data-id-venta="${id_venta}"]`);

    // Si no aparece, probablemente sea por los filtros (incluido Estado).
    // Los reseteamos todos y volvemos a renderizar.
    if (!panel) {
        const select_viaje = document.getElementById('selector_viaje_vendido');
        const select_vendedor = document.getElementById('filtro_vendedor');
        const select_estado = document.getElementById('filtro_estado');
        if (select_viaje) select_viaje.value = 'todos';
        if (select_vendedor) select_vendedor.value = 'Todos';
        if (select_estado) select_estado.value = 'todos';
        renderizar_ventas();
        panel = document.querySelector(`.sale-card[data-id-venta="${id_venta}"]`);
    }

    if (!panel) {
        mostrar_aviso('No se encontró la venta en el listado', 'error');
        return;
    }

    // Scroll suave hasta la tarjeta (centrada si se puede)
    panel.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Aplicar el resaltado. Quitamos la clase primero y forzamos un reflow
    // para que la animación arranque aunque ya estuviera aplicada.
    panel.classList.remove('venta-resaltada');
    void panel.offsetWidth;
    panel.classList.add('venta-resaltada');

    // Limpiar la clase después de que termine la animación.
    setTimeout(() => {
        panel.classList.remove('venta-resaltada');
    }, 2600);
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

// ============================================================
// Validaciones locales del formulario de venta.
// Todas devuelven null si OK, o un string con el error.
// ============================================================

function validar_dni_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'El DNI es obligatorio';
    if (!/^\d+$/.test(v)) return 'El DNI solo puede tener números';
    if (v.length < 6 || v.length > 8) return 'El DNI debe tener entre 6 y 8 números';
    return null;
}

function validar_telefono_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'El teléfono es obligatorio';
    const cuerpo = v.startsWith('+') ? v.substring(1) : v;
    if (!/^\d+$/.test(cuerpo)) return 'El teléfono solo puede tener números, o empezar con +';
    if (cuerpo.length < 10 || cuerpo.length > 15) return 'El teléfono debe tener entre 10 y 15 números';
    return null;
}

function validar_email_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return null;
    if (v.length > 100) return 'El email no puede tener más de 100 caracteres';
    if (!/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test(v)) return 'El email no parece válido';
    return null;
}

function validar_nombre_o_apellido_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'Este campo es obligatorio';
    if (v.length > 60) return 'No puede tener más de 60 caracteres';
    if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü'\- \t]+$/.test(v)) return 'Solo puede tener letras, espacios, apóstrofes y guiones';
    return null;
}

function validar_fecha_nacimiento_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'La fecha de nacimiento es obligatoria';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return 'La fecha de nacimiento no es válida';
    const [anio, mes, dia] = v.split('-').map(Number);
    const fecha = new Date(anio, mes - 1, dia);
    if (fecha.getFullYear() !== anio || fecha.getMonth() !== mes - 1 || fecha.getDate() !== dia) {
        return 'La fecha de nacimiento no es válida';
    }
    const hoy = new Date();
    if (fecha > hoy) return 'La fecha de nacimiento no puede ser futura';
    let edad = hoy.getFullYear() - anio;
    const m = hoy.getMonth() - (mes - 1);
    if (m < 0 || (m === 0 && hoy.getDate() < dia)) edad--;
    if (edad > 120) return 'La fecha de nacimiento no parece válida';
    return null;
}

function validar_localidad_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'La localidad es obligatoria';
    if (v.length > 80) return 'La localidad no puede tener más de 80 caracteres';
    if (!/^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü'\- \t]+$/.test(v)) return 'La localidad solo puede tener letras, números, espacios, apóstrofes y guiones';
    return null;
}

function validar_direccion_js(valor) {
    const v = (valor || '').trim();
    if (v === '') return 'La dirección es obligatoria';
    if (v.length > 120) return 'La dirección no puede tener más de 120 caracteres';
    if (!/^[A-Za-z0-9ÁÉÍÓÚáéíóúÑÑÜü'\- \t,.°º]+$/.test(v)) return 'La dirección tiene caracteres no permitidos';
    return null;
}

// ===== Inicialización de listeners del panel Vendidos =====
// El select de Estado es estático (no se regenera), así que el listener
// se conecta una sola vez al cargar el script.
(function() {
    const sel_estado = document.getElementById('filtro_estado');
    if (sel_estado) {
        sel_estado.addEventListener('change', () => renderizar_ventas());
    }
})();