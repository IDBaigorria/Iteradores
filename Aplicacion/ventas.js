/***
 * Funciones de venta, confirmación, listado y cancelación.
 * @version 1.5piloto.50
 */

// (aplicar_cambios.php funcionó)
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
    // El cupón post-venta corresponde siempre al primer pago de la venta,
    // que es el cupón 1.
    $("#btn_imprimir_cupon").onclick = () => {
        window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${id_venta}&numero_cupon=1`, '_blank');
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

    // El botón "Cerrar rendición" es exclusivo del dueño.
    const btn_rendir = document.getElementById('boton_cerrar_rendicion');
    if (btn_rendir) {
        btn_rendir.style.display = (usuario_actual.nivel === 'dueno') ? '' : 'none';
    }
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
    const filtroCodigo = (document.getElementById('filtro_codigo_venta')?.value || '').trim();
    const filtroComprador = (document.getElementById('filtro_comprador')?.value || '').trim();
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';
    const filtroRendido = document.getElementById('filtro_rendido')?.value || 'todos';

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
    if (filtroCodigo !== '') {
        const t = filtroCodigo.toLowerCase();
        ventas_filtradas = ventas_filtradas.filter(v => String(v.id_venta || '').toLowerCase().includes(t));
    }
    if (filtroComprador !== '') {
        const t = filtroComprador.toLowerCase();
        const t_dni = filtroComprador.replace(/\D+/g, '');
        ventas_filtradas = ventas_filtradas.filter(v => {
            const c = v.comprador;
            if (!c) return false;
            const nombre = ((c.nombre_completo || '') + ' ' + (c.apellido || '') + ' ' + (c.nombres || '')).toLowerCase();
            if (nombre.trim() !== '' && nombre.includes(t)) return true;
            if (t_dni !== '') {
                const dni = String(c.dni || '').replace(/\D+/g, '');
                if (dni !== '' && dni.includes(t_dni)) return true;
            }
            return false;
        });
    }
    if (filtroFechaDesde !== '') {
        ventas_filtradas = ventas_filtradas.filter(v => {
            const fi = v.fecha_iso || '';
            return fi !== '' && fi >= filtroFechaDesde;
        });
    }
    if (filtroFechaHasta !== '') {
        ventas_filtradas = ventas_filtradas.filter(v => {
            const fi = v.fecha_iso || '';
            return fi !== '' && fi <= filtroFechaHasta;
        });
    }
    if (filtroRendido === 'rendido') {
        ventas_filtradas = ventas_filtradas.filter(v => parseInt(v.cupones_sin_rendir || '0', 10) === 0);
    } else if (filtroRendido === 'falta_rendir') {
        ventas_filtradas = ventas_filtradas.filter(v => parseInt(v.cupones_sin_rendir || '0', 10) > 0);
    }

    const lista = $("#lista_ventas");
    lista.innerHTML = '';
    if (ventas_filtradas.length === 0) {
        lista.innerHTML = '<p style="color:#888; margin:20px;">No hay ventas registradas para los filtros seleccionados.</p>';
        renderizar_saldos_vendidos(ventas_filtradas);
        renderizar_chips_filtros_activos();
        actualizar_contador_ventas(0);
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

        // Comprador: se arma solo si hay datos. Formato horizontal con
        // etiqueta por dato, separados por "·". Puede ocupar uno o dos
        // renglones según el ancho disponible (flex-wrap).
        let comprador_html = '';
        const c = venta.comprador;
        if (c) {
            const nombre = c.nombre_completo || '';
            const celular = c.celular || '';
            const email = c.email || '';
            const direccion_completa = [c.direccion, c.localidad].filter(v => v).join(', ');

            const datos = [];
            if (nombre) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Nombre:</span> <b class="sale-comprador-nombre">${nombre}</b></span>`);
            if (celular) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Celular:</span> <b>${celular}</b></span>`);
            if (email) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Email:</span> <b>${email}</b></span>`);
            if (direccion_completa) datos.push(`<span class="sale-comprador-dato"><span class="sale-comprador-etiqueta">Dirección:</span> <b>${direccion_completa}</b></span>`);

            if (datos.length > 0) {
                const sep = '<span class="sale-sep">·</span>';
                comprador_html = `
                    <div class="sale-comprador">
                        <span class="sale-comprador-titulo">Comprador:</span>
                        <span class="sale-comprador-datos">${datos.join(sep)}</span>
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
        tarjeta.querySelector('.ver_detalle_venta').addEventListener('click', () => ver_cuponera_venta(venta.id_venta));
        tarjeta.querySelector('.cancelar_venta').addEventListener('click', () => cancelar_venta(venta.id_venta));
    });
    renderizar_saldos_vendidos(ventas_filtradas);
    renderizar_chips_filtros_activos();
    actualizar_contador_ventas(ventas_filtradas.length);
}

/**
 * Abre el modal de la cuponera de una venta.
 *
 * Muestra los datos de la compra (viaje, micro, comprador, totales)
 * y una grilla con los cupones (uno por cuota). Cada cupón tiene su
 * número, monto, estado (pagado/pendiente) y un botón de acción
 * (placeholder por ahora).
 *
 * @param {string} id_venta
 */
/**
 * Renderiza la sección "Saldos" en la pestaña Vendidos. Agrupa las
 * ventas filtradas por terminal y suma los montos por método
 * (efectivo y banco) según los cupones pagados de cada venta.
 *
 * Rol terminal: ve solo su fila (sin fila de total).
 * Rol dueño/admin: una fila por terminal + fila de total.
 *
 * @param {Array} ventas_filtradas
 */
function renderizar_saldos_vendidos(ventas_filtradas) {
    const contenedor = document.getElementById('saldos_vendidos');
    if (!contenedor) return;

    // Agrupar por terminal. Además de efectivo y banco, acumulamos:
    //  - cantidad: cantidad de ventas.
    //  - valor: suma de los totales de las ventas.
    //  - adeudan: suma de los pendientes (lo que falta cobrar).
    //  - a_rendir: suma de los montos pagados sin marcar como rendidos.
    const por_terminal = {};
    (ventas_filtradas || []).forEach(v => {
        const t = v.terminal || '';
        if (!por_terminal[t]) {
            por_terminal[t] = {
                terminal: t,
                nombre: v.terminal_nombre_real || t,
                cantidad: 0,
                valor: 0,
                efectivo: 0,
                banco: 0,
                adeudan: 0,
                a_rendir: 0,
            };
        }
        const f = por_terminal[t];
        f.cantidad += 1;
        f.valor += parseFloat(v.total || '0');
        f.efectivo += parseFloat(v.pagado_efectivo || '0');
        f.banco += parseFloat(v.pagado_banco || '0');
        f.adeudan += parseFloat(v.pendiente || '0');
        f.a_rendir += parseFloat(v.a_rendir || '0');
    });

    const filas = Object.values(por_terminal);
    filas.sort((a, b) => a.nombre.localeCompare(b.nombre));

    let html = '<h4 class="saldos-titulo">Saldos (Montos según los filtros aplicados)</h4>';

    if (filas.length === 0) {
        html += '<p style="color:#888;">Sin ventas para los filtros seleccionados.</p>';
        contenedor.innerHTML = html;
        return;
    }

    html += '<div class="table-wrap"><table class="data-table">';
    html += '<thead><tr>';
    html += '<th>Terminal</th>';
    html += '<th>Ventas</th>';
    html += '<th>Valor</th>';
    html += '<th>Efectivo</th>';
    html += '<th>Banco</th>';
    html += '<th>Le adeudan</th>';
    html += '<th>A rendir</th>';
    html += '<th>Total</th>';
    html += '</tr></thead><tbody>';

    const totales = { cantidad: 0, valor: 0, efectivo: 0, banco: 0, adeudan: 0, a_rendir: 0 };
    filas.forEach(f => {
        const total = f.efectivo + f.banco;
        totales.cantidad += f.cantidad;
        totales.valor += f.valor;
        totales.efectivo += f.efectivo;
        totales.banco += f.banco;
        totales.adeudan += f.adeudan;
        totales.a_rendir += f.a_rendir;
        html += '<tr>';
        html += `<td>${f.nombre}</td>`;
        html += `<td>${f.cantidad}</td>`;
        html += `<td>$${f.valor.toFixed(2)}</td>`;
        html += `<td>$${f.efectivo.toFixed(2)}</td>`;
        html += `<td>$${f.banco.toFixed(2)}</td>`;
        html += `<td>$${f.adeudan.toFixed(2)}</td>`;
        html += `<td>$${f.a_rendir.toFixed(2)}</td>`;
        html += `<td>$${total.toFixed(2)}</td>`;
        html += '</tr>';
    });

    // Fila de total: solo para dueño/admin.
    if (usuario_actual.nivel !== 'terminal') {
        const total_general = totales.efectivo + totales.banco;
        html += '<tr class="saldos-total-fila">';
        html += '<td><b>TOTAL</b></td>';
        html += `<td><b>${totales.cantidad}</b></td>`;
        html += `<td><b>$${totales.valor.toFixed(2)}</b></td>`;
        html += `<td><b>$${totales.efectivo.toFixed(2)}</b></td>`;
        html += `<td><b>$${totales.banco.toFixed(2)}</b></td>`;
        html += `<td><b>$${totales.adeudan.toFixed(2)}</b></td>`;
        html += `<td><b>$${totales.a_rendir.toFixed(2)}</b></td>`;
        html += `<td><b>$${total_general.toFixed(2)}</b></td>`;
        html += '</tr>';
    }

    html += '</tbody></table></div>';
    contenedor.innerHTML = html;
}

/**
 * Abre el modal de la cuponera de una venta.
 *
 * Muestra los datos de la compra y la grilla de cupones. Los cupones
 * pagados ofrecen "Imprimir", los pendientes "Pagar".
 *
 * @param {string} id_venta
 */
async function ver_cuponera_venta(id_venta) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/obtener", id_venta })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener la venta", 'error');
        return;
    }

    window.venta_cuponera_actual = datos.venta;

    // Refrescar la lista de Vendidos al cerrar la cuponera, sin importar
    // cómo se cierre (X, backdrop o botón Cerrar).
    window.on_cerrar_modal_generico = () => {
        window.venta_cuponera_actual = null;
        if (typeof cargar_ventas === 'function') cargar_ventas();
    };

    abrir_modal_generico('Cuponera de la venta', '');
    _renderizar_cuponera_en_modal();
}

/**
 * Vuelve a armar el contenido del modal de la cuponera con la venta
 * actual. Se usa después de un pago para reflejar los cambios.
 */
function _renderizar_cuponera_en_modal() {
    const venta = window.venta_cuponera_actual;
    if (!venta) return;

    const cupones = Array.isArray(venta.cupones) ? venta.cupones : [];

    const nombre_vendedor = venta.terminal_nombre_real || venta.terminal || '';
    const viaje_visible = venta.viaje_visible || venta.viaje || '';
    const micro_visible = venta.micro_nombre_visible || venta.micro || '';

    const fecha_compra = venta.fecha || '';
    const fecha_pago = venta.fecha_pago || '';
    const mostrar_fecha_pago = fecha_pago && fecha_pago !== fecha_compra;

    const c = venta.comprador || null;
    let comprador_html = '';
    if (c) {
        const lineas = [];
        if (c.nombre_completo) lineas.push(`<div class="cuponera-dato-linea"><span>Nombre:</span><b>${c.nombre_completo}</b></div>`);
        if (c.celular) lineas.push(`<div class="cuponera-dato-linea"><span>Celular:</span><b>${c.celular}</b></div>`);
        if (c.email) lineas.push(`<div class="cuponera-dato-linea"><span>Email:</span><b>${c.email}</b></div>`);
        const dir = [c.direccion, c.localidad].filter(v => v).join(', ');
        if (dir) lineas.push(`<div class="cuponera-dato-linea"><span>Dirección:</span><b>${dir}</b></div>`);
        if (lineas.length > 0) {
            comprador_html = `<div class="cuponera-dato-bloque">${lineas.join('')}</div>`;
        }
    }

    // Grilla de cupones.
    let cupones_html = '';
    if (cupones.length === 0) {
        cupones_html = '<p class="muted">Esta venta no tiene cupones registrados.</p>';
    } else {
        cupones_html = cupones.map(cup => {
            const es_pagado = cup.estado === 'pagado';
            const clase_estado = es_pagado ? 'pagado' : 'pendiente';
            const texto_estado = es_pagado ? 'Pagado' : 'Pendiente';
            const texto_boton = es_pagado ? 'Imprimir' : 'Pagar';
            const accion_boton = es_pagado ? 'imprimir' : 'pagar';
            const linea_fecha = (es_pagado && cup.fecha_pago)
                ? `<div class="cupon-fecha">${cup.fecha_pago}</div>`
                : '';
            const linea_metodo = (es_pagado && cup.metodo_pago)
                ? `<div class="cupon-metodo">${cup.metodo_pago === 'efectivo' ? 'Efectivo' : 'Transferencia'}</div>`
                : '';
            return `
                <div class="cupon ${clase_estado}">
                    <div class="cupon-numero">Cupón ${cup.numero}</div>
                    <div class="cupon-monto">$${cup.monto}</div>
                    <div class="cupon-estado">${texto_estado}</div>
                    ${linea_fecha}
                    ${linea_metodo}
                    <button class="btn cupon-accion cupon-accion-${accion_boton}" data-cupon="${cup.numero}" data-accion="${accion_boton}">${texto_boton}</button>
                </div>
            `;
        }).join('');
    }

    const info_partes = [];
    if (viaje_visible) info_partes.push(`<span><b>Viaje:</b> ${viaje_visible}</span>`);
    if (micro_visible) info_partes.push(`<span><b>Micro:</b> ${micro_visible}</span>`);
    const info_html = info_partes.length > 0
        ? `<div class="cuponera-info-linea">${info_partes.join('<span class="cuponera-sep">·</span>')}</div>`
        : '';

    const html = `
        <div class="cuponera">
            <div class="cuponera-header">
                <div class="cuponera-header-izq">
                    <div class="cuponera-titulo-venta">Venta ${venta.id_venta}</div>
                    <div class="cuponera-fecha-compra"><b>Fecha de compra:</b> ${fecha_compra}</div>
                    ${mostrar_fecha_pago ? `<div class="cuponera-fecha-compra"><b>Último pago:</b> ${fecha_pago}</div>` : ''}
                </div>
                <span class="badge">${nombre_vendedor}</span>
            </div>

            <div class="cuponera-datos">
                ${info_html}
                ${comprador_html}
            </div>

            <div class="cuponera-totales-linea">
                <span><b>Total:</b> $${venta.total}</span>
                <span class="cuponera-sep">·</span>
                <span><b>Abonado:</b> $${venta.pagado}</span>
                <span class="cuponera-sep">·</span>
                <span><b>Pendiente:</b> <span class="cuponera-total-pendiente-texto">$${venta.pendiente}</span></span>
            </div>

            <div class="cuponera-seccion-titulo">Cupones</div>
            <div class="cuponera-grid">
                ${cupones_html}
            </div>

            <div class="actions" style="margin-top:20px;">
                <button class="btn" id="cuponera_cerrar">Cerrar</button>
            </div>
        </div>
    `;

    const contenedor = document.getElementById('modal_generico_contenido');
    if (contenedor) contenedor.innerHTML = html;

    // Listener para el botón Cerrar: cierra el modal y refresca la lista
    // de ventas para que la tarjeta del panel refleje el nuevo estado.
    const btn_cerrar = document.getElementById('cuponera_cerrar');
    if (btn_cerrar) {
        btn_cerrar.addEventListener('click', cerrar_modal_generico);
    }

    // Listeners de los botones de cupón.
    document.querySelectorAll('.cupon-accion').forEach(btn => {
        btn.addEventListener('click', function() {
            const accion = this.dataset.accion;
            const numero = this.dataset.cupon;
            if (accion === 'imprimir') {
                window.open(`index.php?imprimir=1&tipo=cupon&id_venta=${venta.id_venta}&numero_cupon=${numero}`, '_blank');
            } else if (accion === 'pagar') {
                _abrir_modal_pagar_cupon(numero);
            }
        });
    });
}

/**
 * Abre el modal apilado para pagar un cupón pendiente.
 *
 * El input de monto viene precargado con el monto actual del cupón.
 * Si es el último pendiente, se bloquea con el saldo exacto de la venta.
 *
 * @param {string|number} numero_cupon
 */
function _abrir_modal_pagar_cupon(numero_cupon) {
    const venta = window.venta_cuponera_actual;
    if (!venta) return;

    const cupones = Array.isArray(venta.cupones) ? venta.cupones : [];
    const cupon = cupones.find(c => String(c.numero) === String(numero_cupon));
    if (!cupon) {
        mostrar_aviso('Cupón no encontrado', 'error');
        return;
    }

    const pendientes = cupones.filter(c => c.estado === 'pendiente');
    const es_ultimo_pendiente = (pendientes.length === 1 && String(pendientes[0].numero) === String(numero_cupon));
    const saldo = parseFloat(venta.pendiente || '0');
    const metodos = Array.isArray(venta.metodos_permitidos) && venta.metodos_permitidos.length > 0
        ? venta.metodos_permitidos
        : ['efectivo'];

    const valor_input = es_ultimo_pendiente ? saldo.toFixed(2) : cupon.monto;
    const opciones_metodo = metodos.map(m => {
        const texto = m === 'efectivo' ? 'Efectivo' : 'Transferencia';
        return `<option value="${m}">${texto}</option>`;
    }).join('');

    const html = `
        <h3>Pagar cupón ${cupon.numero}</h3>
        <div class="seccion" style="margin-bottom:15px;">
            <div class="detail-line"><span>Monto del cupón:</span><strong>$${cupon.monto}</strong></div>
            <div class="detail-line"><span>Saldo pendiente de la venta:</span><strong>$${venta.pendiente}</strong></div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label>Monto a cobrar *</label>
                <input type="number" id="pagar_cupon_monto" step="1000" min="1000" value="${valor_input}" ${es_ultimo_pendiente ? 'disabled' : ''}>
            </div>
            <div class="field">
                <label>Método de pago *</label>
                <select id="pagar_cupon_metodo">${opciones_metodo}</select>
            </div>
        </div>
        <div class="actions" style="margin-top:15px;">
            <button class="btn primary" id="btn_confirmar_pagar_cupon">Confirmar pago</button>
            <button class="btn" id="btn_cancelar_pagar_cupon">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado(`Pagar cupón ${cupon.numero}`, html);

    const contenedor = document.getElementById('modal_apilado_contenido');

    contenedor.querySelector('#btn_cancelar_pagar_cupon').addEventListener('click', cerrar_modal_apilado);

    contenedor.querySelector('#btn_confirmar_pagar_cupon').addEventListener('click', async () => {
        const input_monto = contenedor.querySelector('#pagar_cupon_monto');
        const select_metodo = contenedor.querySelector('#pagar_cupon_metodo');
        const monto_str = input_monto.value.trim();
        const metodo = select_metodo.value;

        const monto_num = parseFloat(monto_str);
        if (isNaN(monto_num) || monto_num <= 0) {
            mostrar_aviso('Ingrese un monto válido', 'error');
            return;
        }
        if (monto_num > saldo + 0.001) {
            mostrar_aviso('El monto no puede superar el saldo pendiente', 'error');
            return;
        }
        if (es_ultimo_pendiente && Math.abs(monto_num - saldo) > 0.001) {
            mostrar_aviso('En el último cupón pendiente debe cobrarse el saldo exacto', 'error');
            return;
        }

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "ventas/pagar_cupon",
                id_venta: venta.id_venta,
                numero_cupon: cupon.numero,
                monto: monto_num.toFixed(2),
                metodo_pago: metodo
            })
        });
        const resultado = await respuesta.json();
        if (!resultado.exito) {
            mostrar_aviso(resultado.error || 'Error al registrar el pago', 'error');
            return;
        }

        mostrar_aviso('Pago registrado', 'exito');
        cerrar_modal_apilado();

        // Actualizar la venta en memoria con la versión devuelta por el
        // backend y volver a renderizar el modal de la cuponera.
        window.venta_cuponera_actual = resultado.venta;
        _renderizar_cuponera_en_modal();
    });
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
        // Filtros extendidos (v1.5piloto.49)
        const input_codigo = document.getElementById('filtro_codigo_venta');
        const input_comprador = document.getElementById('filtro_comprador');
        const input_fecha_desde = document.getElementById('filtro_fecha_desde');
        const input_fecha_hasta = document.getElementById('filtro_fecha_hasta');
        const sel_rendido = document.getElementById('filtro_rendido');
        if (input_codigo) input_codigo.value = '';
        if (input_comprador) input_comprador.value = '';
        if (input_fecha_desde) input_fecha_desde.value = '';
        if (input_fecha_hasta) input_fecha_hasta.value = '';
        if (sel_rendido) sel_rendido.value = 'todos';
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

/**
 * Abre el modal de cancelación de una venta. Primero pide la info
 * de devolución al backend y muestra el detalle antes de confirmar.
 *
 * @param {string} id_venta
 */
async function cancelar_venta(id_venta) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "ventas/info_cancelacion", id_venta })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener información de cancelación", 'error');
        return;
    }

    const info = datos.info;
    const total_devolver = parseFloat(info.total_a_devolver || '0');
    const efvo = parseFloat(info.efectivo_a_devolver || '0');
    const banco = parseFloat(info.banco_a_devolver || '0');

    // Bloque de datos generales.
    const lineas_datos = [];
    if (info.viaje_visible) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Viaje:</span><b>${info.viaje_visible}</b></div>`);
    if (info.micro_visible) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Micro:</span><b>${info.micro_visible}</b></div>`);
    if (info.fecha_compra) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Fecha de compra:</span><b>${info.fecha_compra}</b></div>`);
    if (info.comprador && info.comprador.nombre_completo) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Comprador:</span><b>${info.comprador.nombre_completo}</b></div>`);
    if (info.comprador && info.comprador.celular) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Celular:</span><b>${info.comprador.celular}</b></div>`);
    if (info.terminal_visible) lineas_datos.push(`<div class="cancelacion-dato-linea"><span>Vendida por:</span><b>${info.terminal_visible}</b></div>`);

    // Bloque de devolución.
    let devolucion_html = '';
    if (total_devolver > 0.001) {
        // Desglose: solo mostrar métodos con monto > 0.
        const partes = [];
        if (efvo > 0.001) partes.push(`<span>Efectivo: <b>$${info.efectivo_a_devolver}</b></span>`);
        if (banco > 0.001) partes.push(`<span>Banco: <b>$${info.banco_a_devolver}</b></span>`);
        const desglose_html = partes.length > 0
            ? `<div class="cancelacion-devolucion-desglose">${partes.join('<span class="cancelacion-sep">·</span>')}</div>`
            : '';

        // Ubicación: texto simple. Si hay un solo método, mencionarlo.
        let ubicacion = '';
        if (efvo > 0.001 && banco <= 0.001) {
            ubicacion = `El dinero está en efectivo en la terminal <b>${info.terminal_visible}</b>.`;
        } else if (banco > 0.001 && efvo <= 0.001) {
            ubicacion = `El dinero está en el banco de la terminal <b>${info.terminal_visible}</b>.`;
        } else {
            ubicacion = `El dinero está en la terminal <b>${info.terminal_visible}</b>.`;
        }

        devolucion_html = `
            <div class="cancelacion-devolucion">
                <div class="cancelacion-devolucion-titulo">Debe devolver al comprador</div>
                <div class="cancelacion-devolucion-monto">$${info.total_a_devolver}</div>
                ${desglose_html}
                <div class="cancelacion-devolucion-ubicacion">${ubicacion}</div>
            </div>
        `;
    } else {
        devolucion_html = `
            <div class="cancelacion-sin-pagos">
                Esta venta no tiene pagos registrados. Se cancelará sin devolución de dinero.
            </div>
        `;
    }

    // Advertencia si no alcanza el saldo.
    const advertencia_html = (!info.puede_cancelar)
        ? `<div class="cancelacion-advertencia">Atención: el saldo actual de la terminal es menor al monto a devolver.</div>`
        : '';

    const html = `
        <div class="cancelacion-aviso">
            <h4>Vas a cancelar la venta <strong>${info.id_venta}</strong></h4>
            <p>Esta acción libera los asientos, elimina los cupones y quita la venta del listado. No se puede deshacer.</p>

            <div class="cancelacion-datos">
                ${lineas_datos.join('')}
            </div>

            ${devolucion_html}
            ${advertencia_html}

            <div class="cancelacion-acciones">
                <button class="btn danger" id="btn_confirmar_cancelacion">Cancelar venta</button>
                <button class="btn" id="btn_volver_cancelacion">Volver</button>
            </div>
        </div>
    `;

    abrir_modal_generico('Cancelar venta', html);

    const contenedor = document.getElementById('modal_generico_contenido');

    contenedor.querySelector('#btn_volver_cancelacion').addEventListener('click', cerrar_modal_generico);

    contenedor.querySelector('#btn_confirmar_cancelacion').addEventListener('click', async () => {
        // Confirm final, como último freno para el usuario despistado.
        const monto_txt = total_devolver > 0.001
            ? `Se deben devolver $${info.total_a_devolver} al comprador.\n\n`
            : 'Esta venta no tiene pagos registrados, no hay devolución.\n\n';
        const confirmacion = confirm(
            `¿Confirmás la cancelación de la venta ${info.id_venta}?\n\n`
            + monto_txt
            + 'Esta acción no se puede deshacer.'
        );
        if (!confirmacion) return;

        const resp2 = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "ventas/cancelar", id_venta })
        });
        const resultado = await resp2.json();
        if (resultado.exito) {
            mostrar_aviso("Venta cancelada", 'exito');
            cerrar_modal_generico();
            if (typeof cargar_ventas === 'function') cargar_ventas();
        } else {
            mostrar_aviso(resultado.error || "Error al cancelar", 'error');
        }
    });
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

/**
 * Arma la tira de chips con los filtros activos. Cada chip se puede
 * cerrar con la X para desactivar ese filtro individual. Si no hay
 * ningún filtro activo, el contenedor queda vacío.
 */
function renderizar_chips_filtros_activos() {
    const contenedor = document.getElementById('chips_filtros_activos_vendidos');
    if (!contenedor) return;

    const chips = [];

    const sel_dueno = document.getElementById('selector_dueno_vendidos');
    if (sel_dueno && sel_dueno.value) {
        const txt = sel_dueno.options[sel_dueno.selectedIndex]?.textContent || sel_dueno.value;
        chips.push({ id: 'dueno', texto: `Dueño: ${txt}` });
    }

    const codigo = (document.getElementById('filtro_codigo_venta')?.value || '').trim();
    if (codigo) chips.push({ id: 'codigo', texto: `Código: ${codigo}` });

    const comprador = (document.getElementById('filtro_comprador')?.value || '').trim();
    if (comprador) chips.push({ id: 'comprador', texto: `Comprador: ${comprador}` });

    const sel_viaje = document.getElementById('selector_viaje_vendido');
    if (sel_viaje && sel_viaje.value !== 'todos' && sel_viaje.value !== '') {
        chips.push({ id: 'viaje', texto: `Viaje: ${sel_viaje.value}` });
    }

    const sel_vendedor = document.getElementById('filtro_vendedor');
    if (sel_vendedor && sel_vendedor.value !== 'Todos' && sel_vendedor.value !== '') {
        const txt = sel_vendedor.options[sel_vendedor.selectedIndex]?.textContent || sel_vendedor.value;
        chips.push({ id: 'vendedor', texto: `Vendedor: ${txt}` });
    }

    const sel_estado = document.getElementById('filtro_estado');
    if (sel_estado && sel_estado.value !== 'todos' && sel_estado.value !== '') {
        const txt = sel_estado.options[sel_estado.selectedIndex]?.textContent || sel_estado.value;
        chips.push({ id: 'estado', texto: `Estado: ${txt}` });
    }

    const sel_rendido = document.getElementById('filtro_rendido');
    if (sel_rendido && sel_rendido.value !== 'todos' && sel_rendido.value !== '') {
        const txt = sel_rendido.options[sel_rendido.selectedIndex]?.textContent || sel_rendido.value;
        chips.push({ id: 'rendido', texto: `Rendición: ${txt}` });
    }

    const desde = document.getElementById('filtro_fecha_desde')?.value || '';
    if (desde) {
        const partes = desde.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : desde;
        chips.push({ id: 'fecha_desde', texto: `Desde: ${visible}` });
    }

    const hasta = document.getElementById('filtro_fecha_hasta')?.value || '';
    if (hasta) {
        const partes = hasta.split('-');
        const visible = partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : hasta;
        chips.push({ id: 'fecha_hasta', texto: `Hasta: ${visible}` });
    }

    if (chips.length === 0) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = chips.map(c => (
        `<span class="chip-filtro">${c.texto}<button type="button" class="chip-cerrar" data-chip="${c.id}" aria-label="Quitar filtro">✕</button></span>`
    )).join('');

    contenedor.querySelectorAll('.chip-cerrar').forEach(btn => {
        btn.addEventListener('click', () => {
            limpiar_filtro_individual(btn.dataset.chip);
            renderizar_ventas();
        });
    });
}

/**
 * Resetea un filtro individual por su id lógico.
 *
 * @param {string} id Identificador del filtro (dueno, codigo, comprador,
 *                    viaje, vendedor, estado, rendido, fecha_desde, fecha_hasta).
 */
function limpiar_filtro_individual(id) {
    switch (id) {
        case 'dueno': {
            const el = document.getElementById('selector_dueno_vendidos');
            if (el) el.value = '';
            break;
        }
        case 'codigo': {
            const el = document.getElementById('filtro_codigo_venta');
            if (el) el.value = '';
            break;
        }
        case 'comprador': {
            const el = document.getElementById('filtro_comprador');
            if (el) el.value = '';
            break;
        }
        case 'viaje': {
            const el = document.getElementById('selector_viaje_vendido');
            if (el) el.value = 'todos';
            break;
        }
        case 'vendedor': {
            const el = document.getElementById('filtro_vendedor');
            if (el) el.value = 'Todos';
            break;
        }
        case 'estado': {
            const el = document.getElementById('filtro_estado');
            if (el) el.value = 'todos';
            break;
        }
        case 'rendido': {
            const el = document.getElementById('filtro_rendido');
            if (el) el.value = 'todos';
            break;
        }
        case 'fecha_desde': {
            const el = document.getElementById('filtro_fecha_desde');
            if (el) el.value = '';
            break;
        }
        case 'fecha_hasta': {
            const el = document.getElementById('filtro_fecha_hasta');
            if (el) el.value = '';
            break;
        }
    }
}

/**
 * Actualiza el contador de ventas visibles debajo de los chips.
 *
 * @param {number} cantidad
 */
function actualizar_contador_ventas(cantidad) {
    const el = document.getElementById('contador_ventas_vendidos');
    if (!el) return;
    if (cantidad === 0) {
        el.textContent = 'Sin ventas para los filtros seleccionados';
    } else if (cantidad === 1) {
        el.textContent = '1 venta';
    } else {
        el.textContent = `${cantidad} ventas`;
    }
}

/**
 * Limpia todos los filtros de la pestaña Vendidos (salvo el Dueño,
 * que es selector de contexto, no filtro) y vuelve a renderizar.
 */
function limpiar_filtros_vendidos() {
    limpiar_filtro_individual('codigo');
    limpiar_filtro_individual('comprador');
    limpiar_filtro_individual('viaje');
    limpiar_filtro_individual('vendedor');
    limpiar_filtro_individual('estado');
    limpiar_filtro_individual('rendido');
    limpiar_filtro_individual('fecha_desde');
    limpiar_filtro_individual('fecha_hasta');
    renderizar_ventas();
}

/**
 * Formatea un monto numérico al estilo argentino (miles con punto,
 * decimales con coma).
 *
 * @param {number|string} monto
 * @returns {string}
 */
function _formatear_monto_rendicion(monto) {
    const n = parseFloat(monto) || 0;
    return n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Recolecta los filtros actuales del panel Vendidos y los devuelve
 * como objeto listo para mandar al backend. NO incluye `rendido`
 * (la rendición siempre opera sobre cupones sin rendir).
 *
 * @returns {object}
 */
function _recolectar_filtros_vendidos() {
    return {
        viaje: document.getElementById('selector_viaje_vendido')?.value || 'todos',
        vendedor: document.getElementById('filtro_vendedor')?.value || 'Todos',
        estado: document.getElementById('filtro_estado')?.value || 'todos',
        codigo: (document.getElementById('filtro_codigo_venta')?.value || '').trim(),
        comprador: (document.getElementById('filtro_comprador')?.value || '').trim(),
        fecha_desde: document.getElementById('filtro_fecha_desde')?.value || '',
        fecha_hasta: document.getElementById('filtro_fecha_hasta')?.value || ''
    };
}

/**
 * Abre el modal de cierre de rendición. Pide el preview al backend,
 * guarda el estado en window y renderiza el modal. Si no hay nada
 * para rendir, muestra el modal con un mensaje vacío.
 */
async function cerrar_rendicion() {
    if (!usuario_actual) return;
    if (usuario_actual.nivel !== 'dueno') {
        mostrar_aviso('Solo el dueño puede cerrar rendiciones', 'error');
        return;
    }

    const nombre_dueno = usuario_actual.nombre_usuario;
    const filtros = _recolectar_filtros_vendidos();

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "rendiciones/previsualizar",
            nombre_dueno,
            ...filtros
        })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || 'Error al previsualizar rendición', 'error');
        return;
    }

    const preview = datos.preview;

    // Guardar estado global para el confirm y para el recálculo.
    window.rendicion_preview_actual = preview;
    window.rendicion_filtros_actual = filtros;
    window.rendicion_seleccion = (preview.ventas || []).map(v => v.id_venta);

    abrir_modal_generico('Cerrar rendición', '');
    _renderizar_modal_rendicion();
}

/**
 * Renderiza (o re-renderiza) el contenido del modal de rendición a
 * partir del preview guardado en window. Se llama al abrir el modal.
 */
function _renderizar_modal_rendicion() {
    const preview = window.rendicion_preview_actual;
    if (!preview) return;

    const ventas = Array.isArray(preview.ventas) ? preview.ventas : [];

    if (ventas.length === 0) {
        const html_vacio = `
            <div class="rendicion-aviso">
                <div class="rendicion-datos">
                    <div class="rendicion-dato-linea"><span>Código propuesto:</span><b>${preview.id_rendicion_propuesto}</b></div>
                    <div class="rendicion-dato-linea"><span>Fecha:</span><b>${preview.fecha_hora}</b></div>
                </div>
                <div class="rendicion-sin-datos">
                    No hay cupones pagados sin rendir que coincidan con los filtros aplicados.
                </div>
                <div class="rendicion-acciones">
                    <button class="btn" id="btn_cerrar_rendicion_modal">Cerrar</button>
                </div>
            </div>
        `;
        const cont = document.getElementById('modal_generico_contenido');
        if (cont) cont.innerHTML = html_vacio;
        document.getElementById('btn_cerrar_rendicion_modal')?.addEventListener('click', cerrar_modal_generico);
        return;
    }

    // Lista de ventas con checkbox.
    let ventas_html = '';
    ventas.forEach(v => {
        const cupones_nums = v.cupones.map(c => c.numero).join(', ');
        const viaje_micro = [v.viaje_visible, v.micro_visible].filter(x => x).join(' · ');
        ventas_html += `
            <label class="rendicion-venta-item">
                <input type="checkbox" class="rendicion-venta-check" data-id-venta="${v.id_venta}" checked>
                <div class="rendicion-venta-info">
                    <div class="rendicion-venta-id">Venta ${v.id_venta}</div>
                    ${viaje_micro ? `<div class="rendicion-venta-detalle">${viaje_micro}</div>` : ''}
                    <div class="rendicion-venta-detalle">${v.terminal_nombre_real || v.terminal} · Cupones ${cupones_nums}</div>
                </div>
                <div class="rendicion-venta-monto">$${_formatear_monto_rendicion(v.total_sin_rendir)}</div>
            </label>
        `;
    });

    const html = `
        <div class="rendicion-aviso">
            <div class="rendicion-datos">
                <div class="rendicion-dato-linea"><span>Código propuesto:</span><b>${preview.id_rendicion_propuesto}</b></div>
                <div class="rendicion-dato-linea"><span>Fecha:</span><b>${preview.fecha_hora}</b></div>
            </div>

            <div class="rendicion-resumen">
                <div class="rendicion-resumen-titulo">Resumen a rendir</div>
                <div class="rendicion-resumen-grid">
                    <div class="rendicion-resumen-item">
                        <span>Total</span>
                        <b id="rendicion_total">$0,00</b>
                    </div>
                    <div class="rendicion-resumen-item">
                        <span>En efectivo</span>
                        <b id="rendicion_efectivo">$0,00</b>
                    </div>
                    <div class="rendicion-resumen-item">
                        <span>En banco</span>
                        <b id="rendicion_banco">$0,00</b>
                    </div>
                    <div class="rendicion-resumen-item">
                        <span>Cupones</span>
                        <b id="rendicion_cupones">0</b>
                    </div>
                    <div class="rendicion-resumen-item">
                        <span>Ventas</span>
                        <b id="rendicion_ventas">0</b>
                    </div>
                </div>
            </div>

            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo">Por punto de venta</div>
                <table class="rendicion-tabla">
                    <thead>
                        <tr>
                            <th>Terminal</th>
                            <th>Cupones</th>
                            <th>Efectivo</th>
                            <th>Banco</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="rendicion_tabla_terminales_body"></tbody>
                </table>
            </div>

            <div class="rendicion-seccion">
                <div class="rendicion-seccion-titulo">Ventas incluidas (destildá las que no quieras rendir)</div>
                <div class="rendicion-ventas-lista">
                    ${ventas_html}
                </div>
            </div>

            <div class="rendicion-acciones">
                <button class="btn primary" id="btn_confirmar_rendicion">Confirmar rendición y guardar</button>
                <button class="btn" id="btn_cerrar_rendicion_modal">Cerrar</button>
            </div>
        </div>
    `;

    const cont = document.getElementById('modal_generico_contenido');
    if (cont) cont.innerHTML = html;

    // Listeners de checkboxes.
    cont.querySelectorAll('.rendicion-venta-check').forEach(chk => {
        chk.addEventListener('change', function() {
            const id = this.dataset.idVenta;
            if (this.checked) {
                if (!window.rendicion_seleccion.includes(id)) window.rendicion_seleccion.push(id);
            } else {
                window.rendicion_seleccion = window.rendicion_seleccion.filter(x => x !== id);
            }
            _recalcular_totales_rendicion();
        });
    });

    document.getElementById('btn_cerrar_rendicion_modal').addEventListener('click', cerrar_modal_generico);
    document.getElementById('btn_confirmar_rendicion').addEventListener('click', confirmar_rendicion_modal);

    // Primer cálculo.
    _recalcular_totales_rendicion();
}

/**
 * Recalcula totales y tabla por terminal según las ventas tildadas.
 * Actualiza solo los nodos del DOM (no re-renderiza el modal entero).
 */
function _recalcular_totales_rendicion() {
    const preview = window.rendicion_preview_actual;
    if (!preview) return;

    const seleccion = new Set(window.rendicion_seleccion || []);

    let total = 0, efvo = 0, banco = 0, cupones = 0, cant_ventas = 0;
    const por_terminal = {};

    (preview.ventas || []).forEach(v => {
        if (!seleccion.has(v.id_venta)) return;
        cant_ventas++;
        total += parseFloat(v.total_sin_rendir) || 0;

        const t = v.terminal;
        if (!por_terminal[t]) {
            por_terminal[t] = {
                nombre: v.terminal_nombre_real || v.terminal,
                cupones: 0, efvo: 0, banco: 0, total: 0
            };
        }

        v.cupones.forEach(c => {
            const m = parseFloat(c.monto) || 0;
            cupones++;
            por_terminal[t].cupones++;
            por_terminal[t].total += m;
            if (c.metodo_pago === 'transferencia') {
                banco += m;
                por_terminal[t].banco += m;
            } else {
                efvo += m;
                por_terminal[t].efvo += m;
            }
        });
    });

    // Actualizar resumen.
    const el_total = document.getElementById('rendicion_total');
    const el_efvo = document.getElementById('rendicion_efectivo');
    const el_banco = document.getElementById('rendicion_banco');
    const el_cup = document.getElementById('rendicion_cupones');
    const el_ventas = document.getElementById('rendicion_ventas');
    if (el_total) el_total.textContent = '$' + _formatear_monto_rendicion(total);
    if (el_efvo) el_efvo.textContent = '$' + _formatear_monto_rendicion(efvo);
    if (el_banco) el_banco.textContent = '$' + _formatear_monto_rendicion(banco);
    if (el_cup) el_cup.textContent = String(cupones);
    if (el_ventas) el_ventas.textContent = String(cant_ventas);

    // Actualizar tabla por terminal.
    const tbody = document.getElementById('rendicion_tabla_terminales_body');
    if (tbody) {
        const filas = Object.values(por_terminal).sort((a, b) => a.nombre.localeCompare(b.nombre));
        if (filas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;">Sin ventas seleccionadas</td></tr>';
        } else {
            tbody.innerHTML = filas.map(f => `
                <tr>
                    <td>${f.nombre}</td>
                    <td class="num">${f.cupones}</td>
                    <td class="num">$${_formatear_monto_rendicion(f.efvo)}</td>
                    <td class="num">$${_formatear_monto_rendicion(f.banco)}</td>
                    <td class="num">$${_formatear_monto_rendicion(f.total)}</td>
                </tr>
            `).join('');
        }
    }

    // Botón confirmar: deshabilitado si no hay ventas seleccionadas.
    const btn = document.getElementById('btn_confirmar_rendicion');
    if (btn) btn.disabled = (cant_ventas === 0);
}

/**
 * Confirma la rendición: manda al backend la lista exacta de cupones
 * que el usuario vio y dejó tildados. Si el backend detecta que el
 * estado cambió, muestra error y no cierra el modal.
 */
async function confirmar_rendicion_modal() {
    const preview = window.rendicion_preview_actual;
    if (!preview) return;

    const seleccion = new Set(window.rendicion_seleccion || []);
    const ventas_seleccionadas = (preview.ventas || [])
        .filter(v => seleccion.has(v.id_venta))
        .map(v => ({
            id_venta: v.id_venta,
            cupones: v.cupones.map(c => String(c.numero))
        }));

    if (ventas_seleccionadas.length === 0) {
        mostrar_aviso('No hay ventas seleccionadas para rendir', 'error');
        return;
    }

    const filtros = window.rendicion_filtros_actual || {};

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "rendiciones/confirmar",
            nombre_dueno: usuario_actual.nombre_usuario,
            ventas_seleccionadas: JSON.stringify(ventas_seleccionadas),
            ...filtros
        })
    });
    const resultado = await respuesta.json();

    if (!resultado.exito) {
        mostrar_aviso(resultado.error || 'No se pudo cerrar la rendición', 'error');
        return;
    }

    mostrar_aviso(`Rendición ${resultado.id_rendicion} guardada ($${_formatear_monto_rendicion(resultado.total)})`, 'exito');

    // Limpiar estado y cerrar modal.
    window.rendicion_preview_actual = null;
    window.rendicion_filtros_actual = null;
    window.rendicion_seleccion = null;
    cerrar_modal_generico();

    // Refrescar la lista de ventas para que las tarjetas reflejen
    // el nuevo estado (a_rendir actualizado).
    if (typeof cargar_ventas === 'function') cargar_ventas();
}

// ===== Inicialización de listeners del panel Vendidos =====
// El select de Estado es estático (no se regenera), así que el listener
// se conecta una sola vez al cargar el script.
(function() {
    const sel_estado = document.getElementById('filtro_estado');
    if (sel_estado) {
        sel_estado.addEventListener('change', () => renderizar_ventas());
    }
    const btn_informe = document.getElementById('boton_imprimir_informe_ventas');
    if (btn_informe) {
        btn_informe.addEventListener('click', imprimir_informe_ventas);
    }
    // Filtros extendidos (v1.5piloto.49)
    const input_codigo = document.getElementById('filtro_codigo_venta');
    if (input_codigo) input_codigo.addEventListener('input', renderizar_ventas);
    const input_comprador = document.getElementById('filtro_comprador');
    if (input_comprador) input_comprador.addEventListener('input', renderizar_ventas);
    const input_fecha_desde = document.getElementById('filtro_fecha_desde');
    if (input_fecha_desde) input_fecha_desde.addEventListener('change', renderizar_ventas);
    const input_fecha_hasta = document.getElementById('filtro_fecha_hasta');
    if (input_fecha_hasta) input_fecha_hasta.addEventListener('change', renderizar_ventas);
    const sel_rendido = document.getElementById('filtro_rendido');
    if (sel_rendido) sel_rendido.addEventListener('change', renderizar_ventas);
    const btn_limpiar = document.getElementById('boton_limpiar_filtros_vendidos');
    if (btn_limpiar) btn_limpiar.addEventListener('click', limpiar_filtros_vendidos);
    const btn_rendir = document.getElementById('boton_cerrar_rendicion');
    if (btn_rendir) btn_rendir.addEventListener('click', cerrar_rendicion);
})();

/**
 * Abre el informe imprimible de la pestaña Vendidos en una pestaña
 * nueva. Arma la URL con los filtros aplicados y los datos del
 * usuario que lo solicita.
 */
function imprimir_informe_ventas() {
    if (!usuario_actual) return;

    // Determinar tipo y nombre según el rol.
    let tipo_ventas, nombre;
    if (usuario_actual.nivel === 'admin') {
        tipo_ventas = 'dueno';
        const sel = document.getElementById('selector_dueno_vendidos');
        nombre = sel ? sel.value : '';
        if (!nombre) {
            mostrar_aviso('Seleccione un dueño primero', 'error');
            return;
        }
    } else if (usuario_actual.nivel === 'dueno') {
        tipo_ventas = 'dueno';
        nombre = usuario_actual.nombre_usuario;
    } else {
        tipo_ventas = 'terminal';
        nombre = usuario_actual.nombre_usuario;
    }

    const filtro_viaje = document.getElementById('selector_viaje_vendido').value;
    const filtro_vendedor = document.getElementById('filtro_vendedor').value;
    const filtro_estado = document.getElementById('filtro_estado').value;
    const filtro_codigo = (document.getElementById('filtro_codigo_venta')?.value || '').trim();
    const filtro_comprador = (document.getElementById('filtro_comprador')?.value || '').trim();
    const filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
    const filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';
    const filtro_rendido = document.getElementById('filtro_rendido')?.value || 'todos';

    const solicitante_nombre = usuario_actual.nombre_real || usuario_actual.nombre_usuario;

    const params = new URLSearchParams({
        imprimir: '1',
        tipo: 'informe_ventas',
        tipo_ventas: tipo_ventas,
        nombre: nombre,
        viaje: filtro_viaje,
        vendedor: filtro_vendedor,
        estado: filtro_estado,
        codigo: filtro_codigo,
        comprador: filtro_comprador,
        fecha_desde: filtro_fecha_desde,
        fecha_hasta: filtro_fecha_hasta,
        rendido: filtro_rendido,
        solicitante: usuario_actual.nombre_usuario,
        solicitante_nombre: solicitante_nombre
    });

    window.open(`index.php?${params.toString()}`, '_blank');
}