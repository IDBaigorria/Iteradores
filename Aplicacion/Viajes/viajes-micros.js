/**
 * Micros y terminales dentro de viajes.
 * @version 1.5piloto.41
 */

function renderizar_micros_viaje(micros) {
    const contenedor = $("#lista_micros_viaje");
    contenedor.innerHTML = '';
    micros.forEach(micro => {
        const div = document.createElement('div');
        div.className = 'micro-item';
        const textoBoton = (micro_seleccionado === micro.nombre_micro && !document.getElementById('pasaje_micro_viaje')?.classList.contains('hidden')) ? 'Ocultar pasaje' : 'Ver pasaje';
        div.innerHTML = `
            <span>${micro.nombre_empresa || micro.empresa} - ${micro.nombre_vehiculo || micro.patente}</span>
            <span>Capacidad: ${micro.ocupacion}</span>
            <span>Vendidos: ${micro.vendidos}</span>
            ${usuario_actual.nivel === 'terminal' ? `<span>Vendidos aquí: ${micro.vendidos_aqui ?? '0'}</span>` : ''}
            <span>Disponibles: ${micro.disponibles ?? '0'}</span>
            ${usuario_actual.nivel !== 'terminal' ? `<span>Reservados para el equipo: ${micro.reservados ?? '0'}</span>` : ''}
            <span>Monto: $${micro.monto ?? '0'}</span>
            <button class="btn btn-ver-pasaje" data-micro="${micro.nombre_micro}">${textoBoton}</button>
            ${usuario_actual.nivel !== 'terminal' ? `
                <button class="btn btn-editar-monto" data-micro="${micro.nombre_micro}" data-monto="${micro.monto ?? '0'}">Editar monto</button>
                <button class="btn btn-eliminar-micro" data-micro="${micro.nombre_micro}">Quitar</button>
            ` : ''}
        `;
        contenedor.appendChild(div);

        div.querySelector('.btn-ver-pasaje').addEventListener('click', () => seleccionar_micro_viaje(micro.nombre_micro));
        const btnEditarMonto = div.querySelector('.btn-editar-monto');
        if (btnEditarMonto) btnEditarMonto.addEventListener('click', () => editar_monto_micro(micro.nombre_micro, micro.monto ?? '0'));
        const btnQuitar = div.querySelector('.btn-eliminar-micro');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_micro(micro.nombre_micro));
    });
}

function editar_monto_micro(nombre_micro, monto_actual) {
    const nuevo_monto = prompt("Nuevo monto del pasaje:", monto_actual);
    if (nuevo_monto === null) return;
    if (nuevo_monto.trim() === '' || isNaN(parseFloat(nuevo_monto)) || parseFloat(nuevo_monto) < 0) {
        mostrar_aviso("Monto inválido", 'error');
        return;
    }

    const nombre_dueno = obtener_nombre_dueno_actual();
    fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/actualizar_monto_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            monto: nuevo_monto.trim(),
            nombre_dueno
        })
    })
    .then(resp => resp.json())
    .then(resultado => {
        if (resultado.exito) {
            mostrar_aviso("Monto actualizado", 'exito');
            // Refrescar contadores y lista de micros sin reconstruir el modal.
            refrescar_contadores_viaje_actual();
        } else {
            mostrar_aviso(resultado.error || "Error al actualizar monto", 'error');
        }
    });
}

/**
 * Cierra el panel del pasaje de micro y resetea el estado.
 */
function cerrar_pasaje_micro() {
    detener_sync_asientos();
    micro_seleccionado = null;

    const panel = document.getElementById('pasaje_micro_viaje');
    if (panel) panel.classList.add('hidden');

    const croquis = document.getElementById('croquis_pasaje_micro');
    if (croquis) croquis.innerHTML = '';

    const foto = document.getElementById('foto_micro_viaje');
    if (foto) foto.innerHTML = '';

    const info = document.getElementById('info_asiento_viaje');
    if (info) {
        info.innerHTML = '';
        info.classList.add('hidden');
    }

    venta_form_abierto = false;
    const formulario = document.getElementById('formulario_confirmacion_venta');
    if (formulario) formulario.classList.add('hidden');

    const contenedorBoton = document.getElementById('contenedor_boton_confirmar_venta');
    if (contenedorBoton) contenedorBoton.classList.add('hidden');

    actualizar_textos_botones_pasaje();
}

/**
 * Actualiza el texto de todos los botones de micro según el micro activo.
 */
function actualizar_textos_botones_pasaje() {
    document.querySelectorAll('.btn-ver-pasaje').forEach(btn => {
        const nombre = btn.dataset.micro;
        const panelVisible = !document.getElementById('pasaje_micro_viaje')?.classList.contains('hidden');
        btn.textContent = (nombre === micro_seleccionado && panelVisible) ? 'Ocultar pasaje' : 'Ver pasaje';
    });
}

async function seleccionar_micro_viaje(nombre_micro) {
    if (micro_seleccionado === nombre_micro && !document.getElementById('pasaje_micro_viaje').classList.contains('hidden')) {
        cerrar_pasaje_micro();
        return;
    }

    micro_seleccionado = nombre_micro;

    let nombre_dueno;
    if (usuario_actual.nivel === 'terminal') {
        nombre_dueno = viaje_seleccionado.dueno;
    } else {
        nombre_dueno = obtener_nombre_dueno_actual();
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/obtener_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (datos.exito && datos.micro) {
        renderizar_pasaje_micro(datos.micro);
        micro_seleccionado = nombre_micro;
        iniciar_sync_asientos();
        actualizar_textos_botones_pasaje();
    } else {
        mostrar_aviso(datos.error || "Error al obtener micro", 'error');
    }
}

async function eliminar_micro(nombre_micro) {
    if (!confirm(`¿Quitar el micro "${nombre_micro}" del viaje?`)) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/eliminar_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Micro eliminado", 'exito');
        // Si el micro eliminado era el que estaba abierto en el panel de
        // pasaje, cerrar el panel antes del refresh para no dejar el croquis
        // apuntando a un micro que ya no existe.
        if (micro_seleccionado === nombre_micro) {
            cerrar_pasaje_micro();
        }
        // Refrescar contadores y lista de micros sin reconstruir el modal.
        await refrescar_contadores_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar micro", 'error');
    }
}

function renderizar_terminales_viaje(terminales) {
    const contenedor = $("#lista_terminales_viaje");
    contenedor.innerHTML = '';

    // El botón "Opciones" ahora se muestra siempre para dueño/admin.
    // Desde v1.5piloto.32 el modal incluye las condiciones de pago,
    // que son configurables incluso sin paradas intermedias.
    const mostrarBotonOpciones = usuario_actual.nivel !== 'terminal';

    terminales.forEach(terminal => {
        const div = document.createElement('div');
        div.className = 'terminal-item';
        div.innerHTML = `
            <span>${terminal}</span>
            ${mostrarBotonOpciones ? `<button class="btn btn-opciones-terminal" data-terminal="${terminal}">Opciones</button>` : ''}
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-terminal" data-terminal="${terminal}">Quitar</button>` : ''}
        `;
        contenedor.appendChild(div);

        const btnOpciones = div.querySelector('.btn-opciones-terminal');
        if (btnOpciones) btnOpciones.addEventListener('click', () => {
            abrir_modal_opciones_terminal(terminal);
        });

        const btnQuitar = div.querySelector('.btn-eliminar-terminal');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_terminal_autorizada(terminal));
    });
}

async function eliminar_terminal_autorizada(nombre_terminal) {
    if (!confirm(`¿Quitar el punto de venta "${nombre_terminal}" del viaje?`)) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/eliminar_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta eliminado", 'exito');
        // Refrescar contadores y lista de terminales sin reconstruir el modal.
        await refrescar_contadores_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar punto de venta", 'error');
    }
}

// Funciones para abrir formularios y confirmar (llamadas desde viajes-nucleo.js)
async function abrir_formulario_agregar_micro() {
    const html = `
        <div class="form-grid">
            <div class="field">
                <label>Empresa</label>
                <select id="selector_empresa_micro_viaje"></select>
            </div>
            <div class="field">
                <label>Vehículo</label>
                <select id="selector_vehiculo_micro_viaje"></select>
            </div>
            <div class="field">
                <label>Monto del pasaje *</label>
                <input type="number" id="monto_micro_viaje" min="0" step="0.01" placeholder="0.00">
            </div>
        </div>
        <div class="actions" style="margin-top:12px;">
            <button class="btn primary" id="boton_confirmar_micro">Confirmar</button>
            <button class="btn" id="boton_cancelar_micro">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado('Agregar micro', html);

    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectEmpresa = document.getElementById('selector_empresa_micro_viaje');
    selectEmpresa.innerHTML = '<option value="">Seleccione empresa...</option>';

    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "empresas/listar", nombre_dueno })
    });
    const datos = await resp.json();
    if (datos.exito) {
        datos.empresas.forEach(empresa => {
            const opcion = document.createElement('option');
            opcion.value = empresa.nombre_empresa;
            opcion.textContent = empresa.nombre;
            selectEmpresa.appendChild(opcion);
        });
    }

    selectEmpresa.onchange = async () => {
        const selectVehiculo = document.getElementById('selector_vehiculo_micro_viaje');
        selectVehiculo.innerHTML = '<option value="">Seleccione vehículo...</option>';
        if (selectEmpresa.value) {
            const respV = await fetch("index.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa: selectEmpresa.value })
            });
            const datosV = await respV.json();
            if (datosV.exito) {
                datosV.vehiculos.forEach(vehiculo => {
                    const opcion = document.createElement('option');
                    opcion.value = vehiculo.nombre_vehiculo;
                    opcion.textContent = vehiculo.nombre;
                    selectVehiculo.appendChild(opcion);
                });
            }
        }
    };

    document.getElementById('boton_cancelar_micro').addEventListener('click', cerrar_modal_apilado);
    document.getElementById('boton_confirmar_micro').addEventListener('click', confirmar_agregar_micro);
}

async function confirmar_agregar_micro() {
    const nombre_empresa = document.getElementById('selector_empresa_micro_viaje').value;
    const nombre_vehiculo = document.getElementById('selector_vehiculo_micro_viaje').value;
    const monto = document.getElementById('monto_micro_viaje').value.trim();
    const nombre_dueno = obtener_nombre_dueno_actual();

    if (!nombre_empresa || !nombre_vehiculo) {
        mostrar_aviso("Seleccione empresa y vehículo", 'error');
        return;
    }
    if (monto === '' || isNaN(parseFloat(monto)) || parseFloat(monto) < 0) {
        mostrar_aviso("Ingrese un monto válido", 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/agregar_micro",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_empresa,
            nombre_vehiculo,
            nombre_dueno,
            monto
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Micro agregado", 'exito');
        cerrar_modal_apilado();
        // Refrescar contadores y lista de micros sin reconstruir el modal.
        await refrescar_contadores_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al agregar micro", 'error');
    }
}

async function abrir_formulario_agregar_terminal() {
    const html = `
        <div class="field">
            <label>Terminal</label>
            <select id="selector_terminal_autorizada"></select>
        </div>
        <div class="actions" style="margin-top:12px;">
            <button class="btn primary" id="boton_confirmar_terminal">Confirmar</button>
            <button class="btn" id="boton_cancelar_terminal_viaje">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado('Agregar punto de venta autorizado', html);

    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectTerminal = document.getElementById('selector_terminal_autorizada');
    selectTerminal.innerHTML = '<option value="">Seleccione punto de venta...</option>';

    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno })
    });
    const datos = await resp.json();
    if (datos.exito) {
        datos.terminales.forEach(terminal => {
            const opcion = document.createElement('option');
            opcion.value = terminal.nombre_usuario;
            opcion.textContent = terminal.nombre_real ? `${terminal.nombre_real} (${terminal.nombre_usuario})` : terminal.nombre_usuario;
            selectTerminal.appendChild(opcion);
        });
    }

    document.getElementById('boton_cancelar_terminal_viaje').addEventListener('click', cerrar_modal_apilado);
    document.getElementById('boton_confirmar_terminal').addEventListener('click', confirmar_agregar_terminal);
}

async function confirmar_agregar_terminal() {
    const nombre_terminal = document.getElementById('selector_terminal_autorizada').value;
    const nombre_dueno = obtener_nombre_dueno_actual();
    if (!nombre_terminal) {
        mostrar_aviso("Seleccione un punto de venta", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/agregar_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta autorizado", 'exito');
        cerrar_modal_apilado();
        // Refrescar contadores y lista de terminales sin reconstruir el modal.
        await refrescar_contadores_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al autorizar punto de venta", 'error');
    }
}

/**
 * Abre el modal de opciones específicas para la combinación viaje + terminal.
 * Solo aplicable a dueño/admin (el botón no se muestra a terminales).
 *
 * Desde v1.5piloto.31 incluye el punto de subida/bajada (si hay paradas).
 * Desde v1.5piloto.32 incluye las condiciones de pago (override opcional).
 *
 * @param {string} nombre_terminal Nombre de usuario de la terminal.
 */
async function abrir_modal_opciones_terminal(nombre_terminal) {
    const nombre_dueno = obtener_nombre_dueno_actual();

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/obtener_opciones_terminal",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_terminal,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener opciones", 'error');
        return;
    }

    const opciones = datos.opciones || {};
    const paradas = (viaje_seleccionado && Array.isArray(viaje_seleccionado.paradas_intermedias))
        ? viaje_seleccionado.paradas_intermedias
        : [];
    const tieneParadas = paradas.length > 0;

    // Punto de subida/bajada
    const cambiar_marcado = opciones.cambiar_punto_predeterminado === '1';
    const punto_actual = opciones.punto_subida_bajada || '';

    // Condiciones de pago (override)
    const override_efectivo = opciones.permite_efectivo !== undefined && opciones.permite_efectivo !== '';
    const override_transferencia = opciones.permite_transferencia !== undefined && opciones.permite_transferencia !== '';
    const tiene_override_pago = override_efectivo || override_transferencia;

    // Valores del viaje, para precargar como referencia si no hay override
    const opciones_viaje = viaje_seleccionado?.opciones_avanzadas || {};
    const permite_efectivo_val = override_efectivo ? opciones.permite_efectivo : (opciones_viaje.permite_efectivo || '1');
    const cuotas_efectivo_max_val = (opciones.cuotas_efectivo_max && opciones.cuotas_efectivo_max !== '')
        ? opciones.cuotas_efectivo_max
        : (opciones_viaje.cuotas_efectivo_max || '3');
    const permite_transferencia_val = override_transferencia ? opciones.permite_transferencia : (opciones_viaje.permite_transferencia || '1');
    const cuotas_transferencia_max_val = (opciones.cuotas_transferencia_max && opciones.cuotas_transferencia_max !== '')
        ? opciones.cuotas_transferencia_max
        : (opciones_viaje.cuotas_transferencia_max || '1');

    // HTML de las opciones de paradas.
    // `paradas` es un array de objetos {nombre, hora_estimada}, pero aceptamos
    // strings por compatibilidad. Se muestra la hora entre paréntesis; si no
    // hay hora, se indica "a confirmar".
    const opciones_html_paradas = paradas.map(p => {
        let nombre = '';
        let hora = '';
        if (typeof p === 'string') {
            nombre = p;
        } else if (p && typeof p === 'object') {
            nombre = String(p.nombre || '');
            hora = String(p.hora_estimada || '');
        }
        const sel = (nombre === punto_actual) ? ' selected' : '';
        const valor = String(nombre).replace(/"/g, '&quot;');
        const texto = hora ? `${nombre} (${hora})` : `${nombre} (a confirmar)`;
        return `<option value="${valor}"${sel}>${texto}</option>`;
    }).join('');

    const seccion_paradas = tieneParadas ? `
        <div class="seccion-opciones">
            <h4>Punto de subida/bajada</h4>
            <div class="field">
                <label><input type="checkbox" id="opciones_terminal_checkbox" ${cambiar_marcado ? 'checked' : ''}> ¿Cambiar punto de subida/bajada predeterminado?</label>
            </div>
            <div class="field" id="opciones_terminal_punto_container" style="${cambiar_marcado ? '' : 'display:none;'}">
                <label>Punto de subida/bajada</label>
                <select id="opciones_terminal_select_punto">
                    <option value="">Sin selección</option>
                    ${opciones_html_paradas}
                </select>
            </div>
        </div>
    ` : '';

    const html = `
        <h3>Opciones para "${nombre_terminal}"</h3>

        ${seccion_paradas}

        <div class="seccion-opciones" style="margin-top:20px; border-top:1px solid #ccc; padding-top:15px;">
            <h4>Condiciones de pago</h4>
            <div class="field">
                <label><input type="checkbox" id="opciones_terminal_usar_override" ${tiene_override_pago ? 'checked' : ''}> Usar configuración propia para esta terminal</label>
                <div class="small muted" style="margin-top:4px;">Si no se activa, esta terminal usa las condiciones de pago del viaje.</div>
            </div>

            <div id="opciones_terminal_pago_container" style="${tiene_override_pago ? '' : 'display:none;'} margin-top:12px;">
                <div>
                    <label><input type="checkbox" id="opciones_terminal_permite_efectivo" ${permite_efectivo_val === '1' ? 'checked' : ''}> Permitir pago en efectivo</label>
                    <div class="field" id="opciones_terminal_cuotas_efectivo_container" style="margin-left:20px; margin-top:6px; ${permite_efectivo_val === '1' ? '' : 'display:none;'}">
                        <label>Máximo de cuotas (efectivo):</label>
                        <input type="number" id="opciones_terminal_cuotas_efectivo_max" value="${cuotas_efectivo_max_val}" min="1" max="12" style="max-width:100px;">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label><input type="checkbox" id="opciones_terminal_permite_transferencia" ${permite_transferencia_val === '1' ? 'checked' : ''}> Permitir transferencia bancaria</label>
                    <div class="field" id="opciones_terminal_cuotas_transferencia_container" style="margin-left:20px; margin-top:6px; ${permite_transferencia_val === '1' ? '' : 'display:none;'}">
                        <label>Máximo de cuotas (transferencia):</label>
                        <input type="number" id="opciones_terminal_cuotas_transferencia_max" value="${cuotas_transferencia_max_val}" min="1" max="12" style="max-width:100px;">
                    </div>
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top:20px;">
            <button class="btn primary" id="opciones_terminal_guardar">Guardar</button>
            <button class="btn" id="opciones_terminal_cancelar">Cerrar</button>
        </div>
    `;

    abrir_modal_apilado('Opciones de terminal', html);

    // Punto de subida/bajada (solo si hay paradas)
    if (tieneParadas) {
        const checkbox = document.getElementById('opciones_terminal_checkbox');
        const contenedorPunto = document.getElementById('opciones_terminal_punto_container');
        checkbox.addEventListener('change', () => {
            contenedorPunto.style.display = checkbox.checked ? '' : 'none';
        });
    }

    // Condiciones de pago
    const checkOverride = document.getElementById('opciones_terminal_usar_override');
    const contenedorPago = document.getElementById('opciones_terminal_pago_container');
    checkOverride.addEventListener('change', () => {
        contenedorPago.style.display = checkOverride.checked ? '' : 'none';
    });

    document.getElementById('opciones_terminal_permite_efectivo').addEventListener('change', function() {
        document.getElementById('opciones_terminal_cuotas_efectivo_container').style.display = this.checked ? '' : 'none';
    });
    document.getElementById('opciones_terminal_permite_transferencia').addEventListener('change', function() {
        document.getElementById('opciones_terminal_cuotas_transferencia_container').style.display = this.checked ? '' : 'none';
    });

    document.getElementById('opciones_terminal_cancelar').addEventListener('click', () => {
        cerrar_modal_apilado();
    });

    document.getElementById('opciones_terminal_guardar').addEventListener('click', async () => {
        // Punto de subida/bajada
        let cambiar = '0';
        let punto = '';
        if (tieneParadas) {
            cambiar = document.getElementById('opciones_terminal_checkbox').checked ? '1' : '0';
            punto = document.getElementById('opciones_terminal_select_punto').value;
        }

        // Condiciones de pago
        let permite_efectivo = '';
        let cuotas_efectivo_max = '';
        let permite_transferencia = '';
        let cuotas_transferencia_max = '';

        if (checkOverride.checked) {
            permite_efectivo = document.getElementById('opciones_terminal_permite_efectivo').checked ? '1' : '0';
            permite_transferencia = document.getElementById('opciones_terminal_permite_transferencia').checked ? '1' : '0';
            if (permite_efectivo === '0' && permite_transferencia === '0') {
                mostrar_aviso('Debe permitirse al menos un método de pago', 'error');
                return;
            }
            cuotas_efectivo_max = document.getElementById('opciones_terminal_cuotas_efectivo_max').value;
            cuotas_transferencia_max = document.getElementById('opciones_terminal_cuotas_transferencia_max').value;
        }

        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/guardar_opciones_terminal",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_terminal,
                nombre_dueno,
                cambiar_punto_predeterminado: cambiar,
                punto_subida_bajada: punto,
                permite_efectivo,
                cuotas_efectivo_max,
                permite_transferencia,
                cuotas_transferencia_max
            })
        });
        const resultado = await resp.json();
        if (resultado.exito) {
            mostrar_aviso("Opciones guardadas", 'exito');
            cerrar_modal_apilado();
        } else {
            mostrar_aviso(resultado.error || "Error al guardar opciones", 'error');
        }
    });
}