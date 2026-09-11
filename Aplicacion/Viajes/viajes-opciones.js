/***
 * Modal de alta/edición de viaje y opciones avanzadas.
 * @version 1.5piloto.27
 */

async function abrir_modal_viaje(modo, viaje = null) {
    const esAlta = modo === 'alta';
    const nombre_dueno = obtener_nombre_dueno_actual();

    let datos = {
        nombre_viaje: '',
        nombre: '',
        fecha: '',
        hora: '',
        origen: '',
        destino: '',
        mostrar_ficha_medica: '0',
        restriccion_edad: '0',
        edad_minima: '18',
        edad_maxima: '80'
    };

    if (!esAlta && viaje) {
        const resp = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/obtener_opciones_avanzadas",
                nombre_viaje: viaje.nombre_viaje,
                nombre_dueno
            })
        });
        const datosOpciones = await resp.json();
        if (datosOpciones.exito) {
            datos = { ...datos, ...datosOpciones.opciones, ...viaje };
        } else {
            datos = { ...datos, ...viaje };
        }
    }

    const html = `
        <h3>${esAlta ? 'Nuevo viaje' : 'Editar viaje'}</h3>
        <div class="form-grid">
            <div class="field"><label>Nombre de viaje *</label><input id="modal_viaje_nombre" value="${datos.nombre_viaje}" ${!esAlta ? 'disabled' : ''}></div>
            <div class="field">
                <label>Estado de fecha</label>
                <select id="modal_viaje_fecha_estado">
                    <option value="confirmada" ${datos.fecha !== 'a confirmar' ? 'selected' : ''}>Fecha confirmada</option>
                    <option value="a_confirmar" ${datos.fecha === 'a confirmar' ? 'selected' : ''}>Fecha a confirmar</option>
                </select>
            </div>
            <div class="field" id="campo_modal_viaje_fecha">
                <label>Fecha</label><input type="date" id="modal_viaje_fecha" value="${datos.fecha !== 'a confirmar' ? datos.fecha : ''}">
            </div>
            <div class="field">
                <label>Estado de hora</label>
                <select id="modal_viaje_hora_estado">
                    <option value="confirmada" ${datos.hora !== 'a confirmar' ? 'selected' : ''}>Hora confirmada</option>
                    <option value="a_confirmar" ${datos.hora === 'a confirmar' ? 'selected' : ''}>Hora a confirmar</option>
                </select>
            </div>
            <div class="field" id="campo_modal_viaje_hora">
                <label>Hora</label><input type="time" id="modal_viaje_hora" value="${datos.hora !== 'a confirmar' ? datos.hora : ''}">
            </div>
            <div class="field"><label>Origen</label><input id="modal_viaje_origen" value="${datos.origen}"></div>
            <div class="field"><label>Destino</label><input id="modal_viaje_destino" value="${datos.destino}"></div>
        </div>
        <div class="seccion-avanzada" style="margin-top:20px; border-top:1px solid #ccc; padding-top:15px;">
            <h4>Opciones avanzadas</h4>
            <div>
                <label><input type="checkbox" id="modal_viaje_mostrar_ficha" ${datos.mostrar_ficha_medica === '1' ? 'checked' : ''}> Mostrar opción de agregar ficha médica al momento de la venta</label>
            </div>
            <div style="margin-top:10px;">
                <label><input type="checkbox" id="modal_viaje_restriccion_edad" ${datos.restriccion_edad === '1' ? 'checked' : ''}> Aplicar restricción de edad</label>
                <div id="modal_campos_edad" style="margin-left:20px; margin-top:10px; display:${datos.restriccion_edad === '1' ? 'block' : 'none'};">
                    <div class="field"><label>Edad mínima:</label><input type="number" id="modal_edad_minima" value="${datos.edad_minima}" min="0" max="120"></div>
                    <div class="field"><label>Edad máxima:</label><input type="number" id="modal_edad_maxima" value="${datos.edad_maxima}" min="0" max="120"></div>
                </div>
            </div>
        </div>
        <div class="actions" style="margin-top:20px;">
            <button class="btn primary" id="guardar_modal_viaje">Guardar</button>
            <button class="btn" id="cancelar_modal_viaje">Cancelar</button>
        </div>
    `;

    abrir_modal_generico(esAlta ? 'Nuevo viaje' : 'Editar viaje', html);

    const actualizarVisibilidadFechaHora = () => {
        document.getElementById('campo_modal_viaje_fecha').style.display = document.getElementById('modal_viaje_fecha_estado').value === 'confirmada' ? '' : 'none';
        document.getElementById('campo_modal_viaje_hora').style.display = document.getElementById('modal_viaje_hora_estado').value === 'confirmada' ? '' : 'none';
    };
    document.getElementById('modal_viaje_fecha_estado').addEventListener('change', actualizarVisibilidadFechaHora);
    document.getElementById('modal_viaje_hora_estado').addEventListener('change', actualizarVisibilidadFechaHora);
    actualizarVisibilidadFechaHora();

    document.getElementById('modal_viaje_restriccion_edad').addEventListener('change', function() {
        document.getElementById('modal_campos_edad').style.display = this.checked ? 'block' : 'none';
    });

    document.getElementById('guardar_modal_viaje').addEventListener('click', async () => {
        const datosGuardar = {
            accion: "viajes/guardar",
            nombre_dueno,
            nombre_viaje: document.getElementById('modal_viaje_nombre').value.trim(),
            nombre: document.getElementById('modal_viaje_nombre').value.trim(),
            fecha: document.getElementById('modal_viaje_fecha_estado').value === 'a_confirmar' ? 'a confirmar' : document.getElementById('modal_viaje_fecha').value,
            hora: document.getElementById('modal_viaje_hora_estado').value === 'a_confirmar' ? 'a confirmar' : document.getElementById('modal_viaje_hora').value,
            origen: document.getElementById('modal_viaje_origen').value,
            destino: document.getElementById('modal_viaje_destino').value,
            mostrar_ficha_medica: document.getElementById('modal_viaje_mostrar_ficha').checked ? '1' : '0',
            restriccion_edad: document.getElementById('modal_viaje_restriccion_edad').checked ? '1' : '0',
            edad_minima: document.getElementById('modal_edad_minima').value,
            edad_maxima: document.getElementById('modal_edad_maxima').value
        };

        if (!datosGuardar.nombre_viaje) {
            mostrar_aviso('El nombre del viaje es obligatorio', 'error');
            return;
        }

        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(datosGuardar)
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            mostrar_aviso(esAlta ? 'Viaje creado' : 'Viaje actualizado', 'exito');
            cerrar_modal_generico();
            if (!esAlta && viaje_seleccionado) {
                // Recargar detalle si estábamos en detalle
                await cargar_viajes(); // actualiza lista
                const viajeActualizado = (await obtener_viajes_actualizados()).find(v => v.nombre_viaje === viaje_seleccionado.nombre_viaje);
                if (viajeActualizado) {
                    ver_detalle_viaje(viajeActualizado);
                }
            } else {
                await cargar_viajes();
            }
        } else {
            mostrar_aviso(resultado.error || 'Error al guardar viaje', 'error');
        }
    });

    document.getElementById('cancelar_modal_viaje').addEventListener('click', () => {
        cerrar_modal_generico();
        // Si estábamos en detalle, no hacemos nada; el modal de detalle quedó atrás
    });
}

// Función auxiliar para obtener lista de viajes actualizados
async function obtener_viajes_actualizados() {
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
    return datos.exito ? datos.viajes : [];
}

// Evento botón "Agregar viaje"
$("#boton_agregar_viaje").addEventListener("click", () => {
    abrir_modal_viaje('alta');
});