/***
 * Núcleo de viajes: carga, listado, detalle en modal y eliminación.
 * @version 1.5piloto.62d
 */

function obtener_nombre_dueno_actual() {
    return usuario_actual.nivel === 'admin' ? $("#selector_dueno_viajes").value : usuario_actual.nombre_usuario;
}

async function cargar_viajes() {
    const panel_dueno = $("#panel_selector_dueno_viajes");
    const boton_agregar = $("#boton_agregar_viaje");
    const lista = $("#lista_viajes");
    lista.innerHTML = '';
    ocultar_detalle_viaje();

    if (usuario_actual.nivel === 'admin') {
        panel_dueno.style.display = 'block';
        boton_agregar.style.display = 'none';
        await cargar_duenos_en_select_viajes();
    } else if (usuario_actual.nivel === 'dueno') {
        panel_dueno.style.display = 'none';
        boton_agregar.style.display = 'inline-block';
        await listar_viajes(usuario_actual.nombre_usuario, 'dueno');
    } else if (usuario_actual.nivel === 'terminal') {
        panel_dueno.style.display = 'none';
        boton_agregar.style.display = 'none';
        await listar_viajes(usuario_actual.nombre_usuario, 'terminal');
    }
}

async function cargar_duenos_en_select_viajes() {
    const select = $("#selector_dueno_viajes");
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
        select.onchange = async () => {
            ocultar_detalle_viaje();
            if (select.value) {
                await listar_viajes(select.value, 'dueno');
                $("#boton_agregar_viaje").style.display = 'inline-block';
            } else {
                $("#lista_viajes").innerHTML = '';
                $("#boton_agregar_viaje").style.display = 'none';
            }
        };
    }
}

function ocultar_detalle_viaje() {
    detener_sync_asientos();
    viaje_seleccionado = null;
    micro_seleccionado = null;
}

async function listar_viajes(nombre, tipo) {
    let accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
    let param = tipo === 'dueno' ? { nombre_dueno: nombre } : { nombre_terminal: nombre };
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion, ...param })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        renderizar_viajes(datos.viajes);
    } else {
        mostrar_aviso(datos.error || "Error al cargar viajes", 'error');
    }
}

function renderizar_viajes(viajes) {
    const lista = $("#lista_viajes");
    lista.innerHTML = '';
    viajes.forEach(viaje => {
        const div = document.createElement('div');
        div.className = 'viaje-card';
        if (viaje.activo === '0') {
            div.classList.add('viaje-inactivo');
        }
        div.innerHTML = `
            <div class="viaje-info">
                <strong>${viaje.nombre}</strong>
                <span>${viaje.fecha} ${viaje.hora}</span>
                <span>${viaje.origen} → ${viaje.destino}</span>
            </div>
            <div class="viaje-stats">
                <span>Capacidad total: ${viaje.ocupacion}</span>
                ${usuario_actual.nivel === 'dueno' ? `<span>Reservado para el equipo: ${viaje.reservados}</span>` : ''}
                <span>Disponibles: ${viaje.disponibles}</span>
                <span>Seleccionados: ${viaje.seleccionados}</span>
                <span>Vendidos: ${viaje.vendidos}</span>
            </div>
            <button class="btn btn-detalle-viaje" data-viaje="${viaje.nombre_viaje}">Ver detalle</button>
            ${usuario_actual.nivel !== 'terminal' ? `
                <button class="btn btn-editar-viaje ${viaje.activo === '0' ? 'btn-disabled' : ''}" data-viaje="${viaje.nombre_viaje}" ${viaje.activo === '0' ? 'disabled' : ''}>Editar viaje</button>
                <button class="btn btn-eliminar-viaje ${viaje.tiene_ventas === '1' ? 'btn-disabled' : ''}" data-viaje="${viaje.nombre_viaje}">Eliminar</button>
            ` : ''}
        `;
        lista.appendChild(div);

        div.querySelector('.btn-detalle-viaje').addEventListener('click', () => ver_detalle_viaje(viaje));
        const btnEditar = div.querySelector('.btn-editar-viaje');
        if (btnEditar && viaje.activo !== '0') {
            btnEditar.addEventListener('click', () => abrir_modal_viaje('editar', viaje));
        }
        const btnEliminar = div.querySelector('.btn-eliminar-viaje');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => {
                if (viaje.tiene_ventas === '1') {
                    mostrar_aviso("No se pueden eliminar viajes con ventas ya realizadas", 'error');
                    return;
                }
                eliminar_viaje(viaje.nombre_viaje);
            });
        }
    });
}

async function ver_detalle_viaje(viaje) {
    detener_sync_asientos();
    viaje_seleccionado = viaje;
    micro_seleccionado = null;
    venta_form_abierto = false;
    operacion_asiento_en_curso = false;

    // Obtener datos frescos del viaje para asegurar que todos los micros estén presentes
    try {
        const nombre_dueno = obtener_nombre_dueno_actual();
        const tipo = usuario_actual.nivel === 'terminal' ? 'terminal' : 'dueno';
        const accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
        const param = tipo === 'dueno' ? { nombre_dueno } : { nombre_terminal: usuario_actual.nombre_usuario };

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion, ...param })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === viaje.nombre_viaje);
            if (viajeActualizado) {
                viaje = viajeActualizado;
                viaje_seleccionado = viaje;
            }
        }
    } catch (error) {
        console.error("Error al refrescar datos del viaje:", error);
    }

    // Normalizar paradas intermedias (siempre array)
    const paradas = Array.isArray(viaje.paradas_intermedias) ? viaje.paradas_intermedias : [];
    const tieneParadas = paradas.length > 0;
    const paradas_formateadas = formatear_paradas_con_hora(paradas);

    // Fecha y hora formateadas.
    // El backend expone 'fecha_visible' ya en DD/MM/YYYY; 'fecha' queda por compat.
    const fecha_para_mostrar = viaje.fecha_visible || viaje.fecha;
    const fechaTexto = (fecha_para_mostrar === 'a confirmar' || fecha_para_mostrar === '') ? 'A confirmar' : fecha_para_mostrar;
    const horaTexto = viaje.hora === 'a confirmar' || viaje.hora === '' ? 'A confirmar' : viaje.hora;
    const fechaPendiente = viaje.fecha === 'a confirmar' || viaje.fecha === '';
    const horaPendiente = viaje.hora === 'a confirmar' || viaje.hora === '';

    const html = `
        <h3>${viaje.nombre} <span class="badge-viaje ${viaje.activo === '1' ? 'badge-viaje-activo' : 'badge-viaje-inactivo'}">${viaje.activo === '1' ? 'Activo' : 'Inactivo'}</span></h3>

        <div class="viaje-detalle-datos">
            <div class="viaje-detalle-seccion">
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">📅 Fecha:</span>
                    <span class="${fechaPendiente ? 'dato-pendiente' : ''}">${fechaTexto}</span>
                </div>
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🕐 Hora:</span>
                    <span class="${horaPendiente ? 'dato-pendiente' : ''}">${horaTexto}</span>
                </div>
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🛣️ Ruta:</span>
                    <span>${viaje.origen} → ${viaje.destino}</span>
                </div>
                ${tieneParadas ? `
                <div class="dato-viaje-linea">
                    <span class="dato-etiqueta">🚏 Paradas intermedias:</span>
                    <span>${paradas_formateadas.join(' · ')}</span>
                </div>
                ` : ''}
            </div>

            <div class="viaje-detalle-seccion">
                <div class="viaje-detalle-contadores">
                    <div class="contador-item"><span>Capacidad total:</span><b data-contador="ocupacion">${viaje.ocupacion}</b></div>
                    <div class="contador-item"><span>Disponibles:</span><b data-contador="disponibles">${viaje.disponibles}</b></div>
                    <div class="contador-item"><span>Seleccionados:</span><b data-contador="seleccionados">${viaje.seleccionados}</b></div>
                    <div class="contador-item"><span>Vendidos:</span><b data-contador="vendidos">${viaje.vendidos}</b></div>
                    ${usuario_actual.nivel !== 'terminal' ? `<div class="contador-item"><span>Reservados para el equipo:</span><b data-contador="reservados">${viaje.reservados}</b></div>` : ''}
                </div>
            </div>
        </div>

        ${usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'dueno' ? `
            <div style="margin-bottom:15px; display:flex; gap:8px; flex-wrap:wrap;">
                <button class="btn" id="modal_btn_editar_viaje" ${viaje.activo === '0' ? 'disabled' : ''}>Editar viaje</button>
                <button class="btn" id="modal_btn_editar_dj_mayor">Editar declaración mayor</button>
                <button class="btn" id="modal_btn_editar_dj_menor">Editar declaración menor</button>
            </div>
        ` : ''}
        <div style="margin-bottom:15px; display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn" id="modal_btn_imprimir_dj_mayor">Imprimir declaración mayor</button>
            <button class="btn" id="modal_btn_imprimir_dj_menor">Imprimir declaración menor</button>
        </div>
        <div class="row">
            <label>Micros:</label>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn" id="boton_agregar_micro_viaje">Agregar micro</button>` : ''}
        </div>
        <div id="lista_micros_viaje"></div>

        <div id="pasaje_micro_viaje" class="panel hidden" style="margin-top:15px;">
            <h4>Pasaje del micro</h4>
            <div id="aviso_inactividad_pasaje" class="hidden">
                <span class="aviso-inactividad-icono">⏸</span>
                <span>Actualización en pausa por inactividad. Movete o hacé clic para reanudar.</span>
            </div>
            <div id="foto_micro_viaje" style="margin-bottom:10px;"></div>
            <div class="pasaje-layout" style="display:flex; flex-wrap:wrap; justify-content:center; gap:15px; align-items:flex-start; max-width:1200px; margin:0 auto;">
                <div id="croquis_pasaje_micro" style="flex:1 1 300px; min-width:300px; display:flex; flex-direction:column; align-items:center;"></div>
                <div class="columna-derecha" style="flex:1 1 450px; min-width:400px; display:flex; flex-direction:column; gap:10px;">
                    <div id="info_asiento_viaje" class="panel hidden" style="padding:10px; border:1px solid #ddd; border-radius:6px;"></div>
                    <div id="contenedor_boton_confirmar_venta" class="hidden" style="text-align:left; display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn primary" id="boton_confirmar_venta">Vender</button>
                        <button class="btn" id="boton_reiniciar_seleccion">Reiniciar selección</button>
                    </div>
                    <div id="formulario_confirmacion_venta" class="panel hidden" style="padding:15px; border:1px solid #ddd; border-radius:6px;"></div>
                </div>
            </div>
        </div>

        <div id="seccion_terminales_viaje" style="${usuario_actual.nivel === 'terminal' ? 'display:none;' : ''}">
            <div class="row" style="margin-top:15px;">
                <label>Terminales autorizadas:</label>
                ${usuario_actual.nivel !== 'terminal' ? `<button class="btn" id="boton_agregar_terminal_viaje">Agregar punto de venta</button>` : ''}
            </div>
            <div id="lista_terminales_viaje"></div>
        </div>

    `;

    abrir_modal_generico('Detalle del viaje', html);

    // Asignar listeners a botones generados
    const btnEditar = document.getElementById('modal_btn_editar_viaje');
    if (btnEditar && viaje.activo !== '0') {
        btnEditar.addEventListener('click', () => {
            abrir_modal_viaje('editar', viaje, () => ver_detalle_viaje(viaje));
        });
    }

    const btnEditDjMayor = document.getElementById('modal_btn_editar_dj_mayor');
    if (btnEditDjMayor) {
        btnEditDjMayor.addEventListener('click', () => abrir_modal_editar_declaracion('mayor'));
    }
    const btnEditDjMenor = document.getElementById('modal_btn_editar_dj_menor');
    if (btnEditDjMenor) {
        btnEditDjMenor.addEventListener('click', () => abrir_modal_editar_declaracion('menor'));
    }
    const btnImpDjMayor = document.getElementById('modal_btn_imprimir_dj_mayor');
    if (btnImpDjMayor) {
        btnImpDjMayor.addEventListener('click', () => imprimir_declaracion_jurada_ui('mayor'));
    }
    const btnImpDjMenor = document.getElementById('modal_btn_imprimir_dj_menor');
    if (btnImpDjMenor) {
        btnImpDjMenor.addEventListener('click', () => imprimir_declaracion_jurada_ui('menor'));
    }

    const btnVender = document.getElementById('boton_confirmar_venta');
    if (btnVender) {
        btnVender.addEventListener('click', abrir_modal_confirmacion_venta);
    }

    const btnReiniciar = document.getElementById('boton_reiniciar_seleccion');
    if (btnReiniciar) {
        btnReiniciar.addEventListener('click', reiniciar_seleccion_propia);
    }

    document.getElementById('boton_agregar_micro_viaje')?.addEventListener('click', abrir_formulario_agregar_micro);
    document.getElementById('boton_agregar_terminal_viaje')?.addEventListener('click', abrir_formulario_agregar_terminal);

    // Renderizar contenido
    renderizar_micros_viaje(viaje.micros);
    if (usuario_actual.nivel !== 'terminal') {
        renderizar_terminales_viaje(viaje.terminales_autorizadas);
    } else {
        const listaT = document.getElementById('lista_terminales_viaje');
        if (listaT) listaT.innerHTML = '';
    }
}

async function eliminar_viaje(nombre_viaje) {
    if (!confirm(`¿Eliminar viaje ${nombre_viaje}?`)) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "viajes/eliminar", nombre_viaje, nombre_dueno })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Viaje eliminado", 'exito');
        cerrar_modal_generico();
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
}

async function actualizar_detalle_viaje_actual() {
    if (!viaje_seleccionado) return;

    const nombre_viaje_actual = viaje_seleccionado.nombre_viaje;
    const micro_previo = micro_seleccionado;

    detener_sync_asientos();

    let nombre_dueno = obtener_nombre_dueno_actual();
    let tipo = usuario_actual.nivel === 'terminal' ? 'terminal' : 'dueno';
    let accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
    let param = tipo === 'dueno' ? { nombre_dueno } : { nombre_terminal: usuario_actual.nombre_usuario };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion, ...param })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === nombre_viaje_actual);
        if (viajeActualizado) {
            ver_detalle_viaje(viajeActualizado);

            if (micro_previo) {
                const microAunExiste = viajeActualizado.micros.some(m => m.nombre_micro === micro_previo);
                if (microAunExiste) {
                    await seleccionar_micro_viaje(micro_previo);
                }
            }
        }
    }
}

/**
 * Refresca los contadores del viaje y la lista de micros en el modal abierto,
 * sin reconstruir el modal ni redibujar el croquis.
 *
 * A diferencia de actualizar_detalle_viaje_actual, que hace un fetch y luego
 * reabre el modal completo (reconstruyendo croquis, panel de asiento, etc.),
 * esta función hace un solo fetch, actualiza en el lugar los 5 contadores del
 * viaje y vuelve a dibujar la lista de micros (que es liviana). No toca el
 * croquis ni el panel de asiento: esos se actualizan aparte con
 * solicitar_estado_asientos() y refrescar_info_asientos_propios().
 *
 * Pensada para invocarse después de operaciones que no cambian la estructura
 * del viaje (reservar, asignar pasajero, vender): así se evita el parpadeo
 * del modal grande.
 */
async function refrescar_contadores_viaje_actual() {
    if (!viaje_seleccionado) return;

    const nombre_viaje_actual = viaje_seleccionado.nombre_viaje;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const tipo = usuario_actual.nivel === 'terminal' ? 'terminal' : 'dueno';
    const accion = tipo === 'dueno' ? 'viajes/listar_por_dueno' : 'viajes/listar_por_terminal';
    const param = tipo === 'dueno' ? { nombre_dueno } : { nombre_terminal: usuario_actual.nombre_usuario };

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion, ...param })
        });
        const datos = await respuesta.json();
        if (!datos.exito) return;

        const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === nombre_viaje_actual);
        if (!viajeActualizado) return;

        // Actualizar el viaje en memoria para que la próxima operación
        // trabaje con datos frescos.
        viaje_seleccionado = viajeActualizado;

        // Actualizar los 5 contadores en el DOM, sin redibujar el modal.
        const contenedor = document.getElementById('modal_generico_contenido');
        if (contenedor) {
            const valores = {
                ocupacion: viajeActualizado.ocupacion,
                disponibles: viajeActualizado.disponibles,
                seleccionados: viajeActualizado.seleccionados,
                vendidos: viajeActualizado.vendidos,
                reservados: viajeActualizado.reservados
            };
            Object.keys(valores).forEach(clave => {
                const el = contenedor.querySelector(`[data-contador="${clave}"]`);
                if (el) el.textContent = valores[clave];
            });
        }

        // Volver a dibujar la lista de micros (liviana: no toca el croquis).
        // Solo si el contenedor existe en el DOM (puede no estar si el
        // modal grande ya fue reemplazado por un sub-modal).
        if (document.getElementById('lista_micros_viaje')) {
            renderizar_micros_viaje(viajeActualizado.micros);
        }

        // Volver a dibujar la lista de terminales autorizadas (liviana:
        // solo reescribe la lista de botones). Solo para dueño/admin,
        // porque la terminal no ve esta sección.
        if (usuario_actual.nivel !== 'terminal'
            && document.getElementById('lista_terminales_viaje')
            && Array.isArray(viajeActualizado.terminales_autorizadas)) {
            renderizar_terminales_viaje(viajeActualizado.terminales_autorizadas);
        }
    } catch (error) {
        console.error("Error al refrescar contadores del viaje:", error);
    }
}
/**
 * Formatea un array de paradas intermedias para mostrarlas en el detalle.
 * Ordena por hora ascendente, dejando las paradas sin hora al final
 * (manteniendo entre ellas el orden original).
 *
 * Acepta tanto strings (formato viejo) como objetos {nombre, hora_estimada}.
 *
 * @param {Array} paradas
 * @returns {Array<string>} Array de textos listos para unir con " · ".
 */
function formatear_paradas_con_hora(paradas) {
    if (!Array.isArray(paradas)) return [];

    const items = paradas.map(p => {
        if (typeof p === 'string') {
            return { nombre: p, hora_estimada: '' };
        }
        if (p && typeof p === 'object') {
            return {
                nombre: String(p.nombre || ''),
                hora_estimada: String(p.hora_estimada || '')
            };
        }
        return null;
    }).filter(p => p && p.nombre);

    items.sort((a, b) => {
        const ah = a.hora_estimada;
        const bh = b.hora_estimada;
        if (ah && bh) return ah.localeCompare(bh);
        if (ah && !bh) return -1;
        if (!ah && bh) return 1;
        return 0;
    });

    return items.map(p => {
        if (p.hora_estimada) return `${p.nombre} (${p.hora_estimada})`;
        return `${p.nombre} (hora a confirmar)`;
    });
}

/**
 * Abre el modal apilado para editar una declaración jurada del viaje.
 * Reutiliza la infraestructura del modal apilado: se muestra encima
 * del modal grande de detalle del viaje, que queda vivo detrás.
 *
 * @param {string} tipo  "mayor" o "menor"
 */
async function abrir_modal_editar_declaracion(tipo) {
    if (!viaje_seleccionado) return;

    const nombre_dueno = obtener_nombre_dueno_actual();
    const nombre_viaje = viaje_seleccionado.nombre_viaje;
    const tipo_norm = (tipo === 'menor') ? 'menor' : 'mayor';

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/obtener_declaracion",
            nombre_dueno,
            nombre_viaje,
            tipo_dj: tipo_norm
        })
    });
    const datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al obtener la declaración", 'error');
        return;
    }

    const etiqueta = tipo_norm === 'mayor' ? 'Pasajero mayor de 18' : 'Pasajero menor de 18';
    const aviso_default = datos.es_default
        ? '<div class="aviso-autocompletado gris">Estás viendo el texto por defecto. Si guardás, queda fijo para este viaje.</div>'
        : '';

    const html = `
        <h3>Declaración jurada - ${etiqueta}</h3>
        <p class="muted" style="margin-top:0;">Editá el contenido de la declaración. Se imprime tal cual se guarde.</p>
        ${aviso_default}
        <textarea id="dj_contenido_editor" class="dj-editor-textarea" rows="25"></textarea>
        <div class="actions" style="margin-top:12px;">
            <button class="btn" id="dj_default_btn">Texto por defecto</button>
            <button class="btn primary" id="dj_guardar_btn">Guardar</button>
            <button class="btn" id="dj_cancelar_btn">Cancelar</button>
        </div>
    `;

    abrir_modal_apilado('Editar declaración jurada', html);

    const contenedor = document.getElementById('modal_apilado_contenido');
    if (!contenedor) return;

    contenedor.querySelector('#dj_contenido_editor').value = datos.contenido;

    contenedor.querySelector('#dj_cancelar_btn').addEventListener('click', cerrar_modal_apilado);

    contenedor.querySelector('#dj_default_btn').addEventListener('click', async () => {
        const confirmado = confirm(
            '¿Reemplazar el contenido actual por el texto por defecto?\n\n'
            + 'Se perderán los cambios no guardados.'
        );
        if (!confirmado) return;

        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/obtener_declaracion",
                nombre_dueno,
                nombre_viaje,
                tipo_dj: tipo_norm,
                forzar_default: '1'
            })
        });
        const datos_default = await resp.json();
        if (datos_default.exito) {
            contenedor.querySelector('#dj_contenido_editor').value = datos_default.contenido;
            mostrar_aviso('Texto por defecto restaurado. Recordá guardar para aplicarlo.', 'info');
        } else {
            mostrar_aviso(datos_default.error || 'Error al obtener el texto por defecto', 'error');
        }
    });

    contenedor.querySelector('#dj_guardar_btn').addEventListener('click', async () => {
        const contenido = contenedor.querySelector('#dj_contenido_editor').value;
        const resp2 = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/guardar_declaracion",
                nombre_dueno,
                nombre_viaje,
                tipo_dj: tipo_norm,
                contenido
            })
        });
        const resultado = await resp2.json();
        if (resultado.exito) {
            mostrar_aviso('Declaración guardada', 'exito');
            cerrar_modal_apilado();
        } else {
            mostrar_aviso(resultado.error || 'Error al guardar', 'error');
        }
    });
}

/**
 * Abre la impresión de una declaración jurada del viaje en una
 * pestaña nueva. Visible para todos los roles (dueño, admin, terminal).
 *
 * @param {string} tipo  "mayor" o "menor"
 */
function imprimir_declaracion_jurada_ui(tipo) {
    if (!viaje_seleccionado) return;
    const nombre_dueno = obtener_nombre_dueno_actual();
    const nombre_viaje = viaje_seleccionado.nombre_viaje;
    const tipo_norm = (tipo === 'menor') ? 'menor' : 'mayor';
    const url = `index.php?imprimir=1&tipo=declaracion_jurada`
        + `&dueno=${encodeURIComponent(nombre_dueno)}`
        + `&viaje=${encodeURIComponent(nombre_viaje)}`
        + `&tipo_dj=${tipo_norm}`;
    window.open(url, '_blank');
}