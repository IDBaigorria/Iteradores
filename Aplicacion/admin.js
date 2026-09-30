/***
 * Funciones de administración de usuarios.
 * @since 1.5piloto.15
 * @version 1.5piloto.73e
 */

async function cargar_datos_admin() {
    const es_soporte = usuario_actual && usuario_actual.nivel === 'soporte';

    // Si es soporte, cargar el selector de dueños.
    if (es_soporte) {
        await _cargar_selector_dueno_admin();
    } else {
        // Si es admin, ocultar el selector.
        const panel_sel = document.getElementById('panel_selector_dueno_admin');
        if (panel_sel) panel_sel.style.display = 'none';
    }

    // Cargar usuarios
    let respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })
    });
    let datos = await respuesta.json();
    if (datos.exito) {
        const cuerpo_tabla = $("#tabla_usuarios_admin");
        cuerpo_tabla.innerHTML = "";
        // Mapa nombre_usuario → nombre_real con todos los usuarios cargados,
        // para poder mostrar el nombre real del dueño de cada terminal.
        const mapa_nombres_reales = {};
        datos.usuarios.forEach(u => {
            mapa_nombres_reales[u.nombre_usuario] = u.nombre_real || u.nombre_usuario;
        });

        datos.usuarios.forEach(usuario => {
            const fila = document.createElement("tr");
            const nombre_dueno_mostrar = usuario.dueno
                ? (mapa_nombres_reales[usuario.dueno] || usuario.dueno)
                : '—';
            fila.innerHTML = `
                <td>${usuario.nombre_usuario}</td>
                <td>${usuario.nombre_real || "—"}</td>
                <td>${usuario.email || "—"}</td>
                <td>${usuario.nivel}</td>
                <td>${usuario.codigo_asignado ? "•••••" : "—"}</td>
                <td>${usuario.efectivo || "0"}</td>
                <td>${usuario.bancarizado || "0"}</td>
                <td>${usuario.banco.nombre || "—"}</td>
                <td>${usuario.banco.cuenta || "—"}</td>
                <td>${nombre_dueno_mostrar}</td>
                <td>
                    <button class="btn_editar_usuario" data-usuario="${usuario.nombre_usuario}">✏️</button>
                    <button class="btn_eliminar_usuario" data-usuario="${usuario.nombre_usuario}">🗑️</button>
                </td>
            `;
            cuerpo_tabla.appendChild(fila);
        });

        cuerpo_tabla.querySelectorAll('.btn_editar_usuario').forEach(boton => {
            boton.addEventListener('click', () => abrir_modal_editar_usuario(boton.dataset.usuario));
        });
        cuerpo_tabla.querySelectorAll('.btn_eliminar_usuario').forEach(boton => {
            boton.addEventListener('click', () => eliminar_usuario_confirmado(boton.dataset.usuario));
        });
    }

    // Cargar sesiones
    respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_sesiones" })
    });
    datos = await respuesta.json();
    if (datos.exito) {
        const cuerpo_tabla = $("#tabla_sesiones_admin");
        cuerpo_tabla.innerHTML = "";
        datos.sesiones.forEach(sesion => {
            const fila = document.createElement("tr");
            const es_sesion_actual = usuario_actual && usuario_actual.token_sesion === sesion.token;
            fila.innerHTML = `
                <td>${sesion.token}</td>
                <td>${sesion.usuario}</td>
                <td>${sesion.creado_en}</td>
                <td>${es_sesion_actual ? '<span class="badge">Actual</span>' : '<button class="btn danger btn_cerrar_sesion" data-token="' + sesion.token + '">Salir</button>'}</td>
            `;
            cuerpo_tabla.appendChild(fila);
        });
        cuerpo_tabla.querySelectorAll('.btn_cerrar_sesion').forEach(boton => {
            boton.addEventListener('click', async (evento) => {
                evento.preventDefault();
                const token = boton.dataset.token;
                if (token) {
                    const respuesta_cierre = await fetch("index.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({ accion: "sesiones/cerrar", token })
                    });
                    const datos_cierre = await respuesta_cierre.json();
                    if (datos_cierre.exito) {
                        mostrar_aviso("Sesión cerrada correctamente", 'exito');
                        cargar_datos_admin();
                    } else {
                        mostrar_aviso(datos_cierre.error || "No se pudo cerrar la sesión", 'error');
                    }
                }
            });
        });
    }
}

async function obtener_datos_usuario(nombre_usuario) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        return datos.usuarios.find(u => u.nombre_usuario === nombre_usuario);
    }
    return null;
}

/**
 * Abre el modal para editar un usuario existente.
 *
 * El formulario incluye nivel, nombre real, email, código (opcional),
 * banco y cuenta (si es terminal o dueño), el dueño (si es terminal)
 * y la lista de dueños asignados (si es soporte).
 *
 * Al guardar, hace el POST y recarga la tabla. Al cancelar, cierra el
 * modal sin tocar nada.
 *
 * @param {string} nombre_usuario
 */
async function abrir_modal_editar_usuario(nombre_usuario) {
    const usuario = await obtener_datos_usuario(nombre_usuario);
    if (!usuario) return;

    let duenos = [];
    try {
        const resp_duenos = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "administrador/listar_duenos" })
        });
        const datos_duenos = await resp_duenos.json();
        if (datos_duenos.exito) {
            duenos = datos_duenos.duenos;
        }
    } catch (e) {
        console.error("Error al cargar dueños", e);
    }

    const valor_nombre_real = usuario.nombre_real || '';
    const valor_email = usuario.email || '';
    const valor_nivel = usuario.nivel;
    const valor_banco_nombre = usuario.banco?.nombre || '';
    const valor_banco_cuenta = usuario.banco?.cuenta || '';
    const valor_dueno = usuario.dueno || '';

    // Opciones del select de dueño (solo para terminal).
    let opciones_dueno = '<option value="">Seleccione dueño...</option>';
    duenos.forEach(dueno => {
        const seleccionado = dueno.nombre_usuario === valor_dueno ? 'selected' : '';
        const texto = dueno.nombre_real
            ? `${dueno.nombre_real} (${dueno.nombre_usuario})`
            : dueno.nombre_usuario;
        opciones_dueno += `<option value="${dueno.nombre_usuario}" ${seleccionado}>${texto}</option>`;
    });

    // Checkboxes de dueños asignados (solo para soporte).
    const asignados = usuario.duenos || [];
    let checkboxes_html = '';
    if (duenos.length === 0) {
        checkboxes_html = '<em>Sin dueños disponibles</em>';
    } else {
        duenos.forEach(dueno => {
            const marcado = asignados.indexOf(dueno.nombre_usuario) !== -1 ? 'checked' : '';
            const texto = dueno.nombre_real
                ? `${dueno.nombre_real} (${dueno.nombre_usuario})`
                : dueno.nombre_usuario;
            checkboxes_html += `<label style="display:block;"><input type="checkbox" class="chk_modal_editar_dueno_soporte" value="${dueno.nombre_usuario}" ${marcado}> ${texto}</label>`;
        });
    }

    const html = `
        <div class="form-grid">
            <div class="field">
                <label>Nombre de usuario</label>
                <input type="text" value="${nombre_usuario}" disabled>
            </div>
            <div class="field">
                <label>Nivel</label>
                <select id="modal_editar_nivel" ${valor_nivel === 'soporte' ? 'disabled' : ''}>
                    <option value="terminal" ${valor_nivel === 'terminal' ? 'selected' : ''}>Terminal</option>
                    <option value="dueno" ${valor_nivel === 'dueno' ? 'selected' : ''}>Dueño</option>
                    <option value="admin" ${valor_nivel === 'admin' ? 'selected' : ''}>Administrador</option>
                    <option value="soporte" ${valor_nivel === 'soporte' ? 'selected' : ''}>Soporte</option>
                </select>
            </div>
            <div class="field">
                <label>Nombre real</label>
                <input type="text" id="modal_editar_nombre_real" value="${valor_nombre_real}">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" id="modal_editar_email" value="${valor_email}">
            </div>
            <div class="field">
                <label>Código de acceso</label>
                <input type="text" id="modal_editar_codigo" value="" placeholder="Dejar vacío para no cambiar">
            </div>
            <div class="field" id="modal_campo_dueno" style="display:none">
                <label>Dueño (para terminales)</label>
                <select id="modal_editar_dueno">${opciones_dueno}</select>
            </div>
            <div class="field" id="modal_campo_banco_nombre" style="display:none">
                <label>Banco (nombre)</label>
                <input type="text" id="modal_editar_banco_nombre" value="${valor_banco_nombre}">
            </div>
            <div class="field" id="modal_campo_banco_cuenta" style="display:none">
                <label>Cuenta bancaria</label>
                <input type="text" id="modal_editar_banco_cuenta" value="${valor_banco_cuenta}">
            </div>
            <div class="field full" id="modal_campo_duenos_soporte" style="display:none">
                <label>Dueños asignados</label>
                <div id="modal_editar_duenos_soporte_lista" style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:6px; border-radius:4px;">
                    ${checkboxes_html}
                </div>
            </div>
        </div>
        <div class="actions" style="margin-top:15px">
            <button class="btn primary" id="modal_btn_guardar_edicion">Guardar</button>
            <button class="btn" id="modal_btn_cancelar_edicion">Cancelar</button>
        </div>
    `;

    abrir_modal_generico('Editar usuario: ' + nombre_usuario, html);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    const select_nivel = contenedor.querySelector('#modal_editar_nivel');
    const campo_dueno = contenedor.querySelector('#modal_campo_dueno');
    const campo_banco_nombre = contenedor.querySelector('#modal_campo_banco_nombre');
    const campo_banco_cuenta = contenedor.querySelector('#modal_campo_banco_cuenta');
    const campo_duenos_soporte = contenedor.querySelector('#modal_campo_duenos_soporte');

    function actualizar_visibilidad() {
        const nivel = select_nivel.value;
        const es_terminal = nivel === 'terminal';
        const es_dueno = nivel === 'dueno';
        const es_soporte = nivel === 'soporte';
        const tiene_banco = es_terminal || es_dueno;
        campo_banco_nombre.style.display = tiene_banco ? '' : 'none';
        campo_banco_cuenta.style.display = tiene_banco ? '' : 'none';
        campo_dueno.style.display = es_terminal ? '' : 'none';
        campo_duenos_soporte.style.display = es_soporte ? '' : 'none';
    }
    select_nivel.addEventListener('change', actualizar_visibilidad);
    actualizar_visibilidad();

    contenedor.querySelector('#modal_btn_cancelar_edicion').addEventListener('click', cerrar_modal_generico);
    contenedor.querySelector('#modal_btn_guardar_edicion').addEventListener('click', () => {
        guardar_edicion_usuario_modal(nombre_usuario, contenedor);
    });
}

/**
 * Guarda los cambios de un usuario editado desde el modal.
 *
 * @param {string} nombre_usuario
 * @param {HTMLElement} contenedor El contenedor del modal con el formulario.
 */
async function guardar_edicion_usuario_modal(nombre_usuario, contenedor) {
    const nivel = contenedor.querySelector('#modal_editar_nivel').value;
    const select_dueno = contenedor.querySelector('#modal_editar_dueno');
    const dueno = select_dueno ? select_dueno.value : '';

    let duenos_asignados = [];
    if (nivel === 'soporte') {
        contenedor.querySelectorAll('.chk_modal_editar_dueno_soporte:checked').forEach(chk => {
            duenos_asignados.push(chk.value);
        });
    }

    const datos = {
        accion: "administrador/actualizar_usuario",
        nombre_usuario: nombre_usuario,
        nombre_real: contenedor.querySelector('#modal_editar_nombre_real').value.trim(),
        email: contenedor.querySelector('#modal_editar_email').value.trim(),
        nivel: nivel,
        codigo_acceso: contenedor.querySelector('#modal_editar_codigo').value.trim(),
        banco_nombre: contenedor.querySelector('#modal_editar_banco_nombre').value.trim(),
        banco_cuenta: contenedor.querySelector('#modal_editar_banco_cuenta').value.trim(),
        dueno: nivel === 'terminal' ? dueno : '',
        contrasena: '',
        duenos_asignados: nivel === 'soporte' ? JSON.stringify(duenos_asignados) : ''
    };

    if (nivel === 'terminal') {
        if (!datos.dueno) {
            mostrar_aviso("Debe seleccionar un dueño", 'error');
            return;
        }
        if (!datos.banco_nombre || !datos.banco_cuenta) {
            mostrar_aviso("Banco y cuenta son obligatorios para terminales", 'error');
            return;
        }
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        if (resultado.codigo_asignado) {
            alert("Nuevo código de acceso: " + resultado.codigo_asignado + "\n\nGuardalo, no se mostrará de nuevo.");
        }
        mostrar_aviso("Usuario actualizado correctamente", 'exito');
        cerrar_modal_generico();
        cargar_datos_admin();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar", 'error');
    }
}

async function eliminar_usuario_confirmado(nombre_usuario) {
    if (!confirm(`¿Está seguro de eliminar al usuario "${nombre_usuario}"?`)) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/eliminar_usuario", nombre_usuario })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        mostrar_aviso("Usuario eliminado correctamente", 'exito');
        cargar_datos_admin();
    } else {
        mostrar_aviso(datos.error || "Error al eliminar", 'error');
    }
}

// Eventos del formulario de nuevo usuario
$("#nuevo_nivel").addEventListener("change", function() {
    const es_terminal = this.value === "terminal";
    const es_dueno = this.value === "dueno";
    const es_soporte = this.value === "soporte";
    const tiene_banco = es_terminal || es_dueno;
    // Se usa "" en vez de "block" para no pisar el display: flex del CSS.
    document.getElementById("campo_dueno").style.display = es_terminal ? "" : "none";
    document.getElementById("campo_banco_nombre").style.display = tiene_banco ? "" : "none";
    document.getElementById("campo_banco_cuenta").style.display = tiene_banco ? "" : "none";
    document.getElementById("campo_duenos_soporte").style.display = es_soporte ? "" : "none";
});

$("#boton_agregar_usuario").addEventListener("click", async () => {
    const es_soporte = usuario_actual && usuario_actual.nivel === 'soporte';
    // Si es soporte, restringir el selector de nivel a "terminal".
    if (es_soporte) {
        const select_nivel = $("#nuevo_nivel");
        select_nivel.innerHTML = '<option value="terminal">Terminal</option>';
        select_nivel.value = 'terminal';
        select_nivel.disabled = true;
    } else {
        const select_nivel = $("#nuevo_nivel");
        select_nivel.disabled = false;
    }
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "administrador/listar_duenos" })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select_dueno = $("#nuevo_dueno_select");
            select_dueno.innerHTML = '<option value="">Seleccione dueño...</option>';
            datos.duenos.forEach(dueno => {
                const opcion = document.createElement("option");
                opcion.value = dueno.nombre_usuario;
                opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;
                select_dueno.appendChild(opcion);
            });

            // Cargar la lista de dueños como checkboxes para el campo de soporte.
            const contenedor = $("#nuevo_soporte_duenos_lista");
            if (contenedor) {
                contenedor.innerHTML = '';
                if (datos.duenos.length === 0) {
                    contenedor.innerHTML = '<em>No hay dueños disponibles</em>';
                } else {
                    datos.duenos.forEach(dueno => {
                        const label = document.createElement('label');
                        label.style.display = 'block';
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = dueno.nombre_usuario;
                        checkbox.className = 'chk_dueno_soporte';
                        label.appendChild(checkbox);
                        const txt = document.createTextNode(' ' + (dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario));
                        label.appendChild(txt);
                        contenedor.appendChild(label);
                    });
                }
            }
        } else {
            mostrar_aviso("No se pudieron cargar los dueños", 'error');
        }
    } catch (e) {
        console.error("Error al cargar dueños", e);
        mostrar_aviso("Error al cargar dueños", 'error');
    }
    $("#formulario_nuevo_usuario").classList.remove("hidden");
    $("#nuevo_nivel").dispatchEvent(new Event("change"));
});

$("#boton_cancelar_nuevo_usuario").addEventListener("click", () => {
    $("#formulario_nuevo_usuario").classList.add("hidden");
});

/**
 * Carga el selector de dueños en la pestaña admin para un soporte.
 */
async function _cargar_selector_dueno_admin() {
    const panel = document.getElementById('panel_selector_dueno_admin');
    const select = document.getElementById('selector_dueno_admin');
    if (!panel || !select) return;

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_duenos" })
    });
    const datos = await respuesta.json();

    select.innerHTML = '';
    if (datos.exito && datos.duenos.length > 0) {
        datos.duenos.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.nombre_usuario;
            opt.textContent = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;
            select.appendChild(opt);
        });
        panel.style.display = '';
    } else {
        select.innerHTML = '<option value="">Sin dueños asignados</option>';
        panel.style.display = '';
    }

    // Listener único (usando onclick para no acumular).
    select.onchange = async () => {
        await _cargar_usuarios_filtrados_por_dueno(select.value);
    };
    // Cargar la primera vez.
    if (select.value) {
        await _cargar_usuarios_filtrados_por_dueno(select.value);
    }
}

/**
 * Carga usuarios del dueño seleccionado (él mismo y sus terminales).
 * Solo se usa cuando el usuario es soporte.
 */
async function _cargar_usuarios_filtrados_por_dueno(nombre_dueno) {
    if (!nombre_dueno) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })
    });
    const datos = await respuesta.json();
    if (!datos.exito) return;

    // Filtrar a los usuarios visibles del soporte.
    const visibles = datos.usuarios.filter(u => {
        if (u.nivel === 'dueno' && u.nombre_usuario === nombre_dueno) return true;
        if (u.nivel === 'terminal' && u.dueno === nombre_dueno) return true;
        return false;
    });
    _renderizar_tabla_usuarios(visibles);
}

/**
 * Renderiza la tabla de usuarios con los datos dados.
 */
function _renderizar_tabla_usuarios(usuarios) {
    const cuerpo_tabla = $("#tabla_usuarios_admin");
    cuerpo_tabla.innerHTML = "";

    const mapa_nombres_reales = {};
    usuarios.forEach(u => {
        mapa_nombres_reales[u.nombre_usuario] = u.nombre_real || u.nombre_usuario;
    });

    usuarios.forEach(usuario => {
        const fila = document.createElement("tr");
        const nombre_dueno_mostrar = usuario.dueno
            ? (mapa_nombres_reales[usuario.dueno] || usuario.dueno)
            : '—';
        fila.innerHTML = `
            <td>${usuario.nombre_usuario}</td>
            <td>${usuario.nombre_real || "—"}</td>
            <td>${usuario.email || "—"}</td>
            <td>${usuario.nivel}</td>
            <td>${usuario.codigo_asignado ? "•••••" : "—"}</td>
            <td>${usuario.efectivo || "0"}</td>
            <td>${usuario.bancarizado || "0"}</td>
            <td>${usuario.banco.nombre || "—"}</td>
            <td>${usuario.banco.cuenta || "—"}</td>
            <td>${nombre_dueno_mostrar}</td>
            <td>
                <button class="btn_editar_usuario" data-usuario="${usuario.nombre_usuario}">✏️</button>
                <button class="btn_eliminar_usuario" data-usuario="${usuario.nombre_usuario}">🗑️</button>
            </td>
        `;
        cuerpo_tabla.appendChild(fila);
    });

    cuerpo_tabla.querySelectorAll('.btn_editar_usuario').forEach(boton => {
        boton.addEventListener('click', () => abrir_modal_editar_usuario(boton.dataset.usuario));
    });
    cuerpo_tabla.querySelectorAll('.btn_eliminar_usuario').forEach(boton => {
        boton.addEventListener('click', () => eliminar_usuario_confirmado(boton.dataset.usuario));
    });
}

$("#boton_guardar_usuario").addEventListener("click", async () => {
    const nivel = $("#nuevo_nivel").value;

    // Recolectar dueños asignados si es soporte.
    let duenos_asignados = [];
    if (nivel === "soporte") {
        document.querySelectorAll('.chk_dueno_soporte:checked').forEach(chk => {
            duenos_asignados.push(chk.value);
        });
    }

    const datos_usuario = {
        accion: "administrador/agregar_usuario",
        nombre_usuario: $("#nuevo_nombre_usuario").value.trim(),
        contrasena: $("#nuevo_contrasena").value,
        nombre_real: $("#nuevo_nombre_real").value.trim(),
        email: $("#nuevo_email").value.trim(),
        codigo_acceso: $("#nuevo_codigo_acceso").value.trim(),
        nivel: nivel,
        dueno: nivel === "terminal" ? $("#nuevo_dueno_select").value : "",
        banco_nombre: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_nombre").value.trim() : "",
        banco_cuenta: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_cuenta").value.trim() : "",
        duenos_asignados: nivel === "soporte" ? JSON.stringify(duenos_asignados) : ""
    };

    if (!datos_usuario.nombre_usuario) {
        mostrar_aviso("El nombre de usuario es obligatorio", 'error');
        return;
    }
    if (!datos_usuario.codigo_acceso && !datos_usuario.contrasena) {
        mostrar_aviso("Debe asignar al menos un código de acceso o una contraseña", 'error');
        return;
    }
    if (nivel === "terminal") {
        if (!datos_usuario.dueno) {
            mostrar_aviso("Debe seleccionar un dueño", 'error');
            return;
        }
        if (!datos_usuario.banco_nombre || !datos_usuario.banco_cuenta) {
            mostrar_aviso("Banco y cuenta son obligatorios para terminales", 'error');
            return;
        }
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos_usuario)
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        if (datos.codigo_asignado) {
            alert("Código de acceso: " + datos.codigo_asignado + "\n\nGuardalo, no se mostrará de nuevo.");
        }
        mostrar_aviso("Usuario agregado correctamente", 'exito');
        $("#formulario_nuevo_usuario").classList.add("hidden");
        ["nuevo_nombre_usuario","nuevo_contrasena","nuevo_nombre_real","nuevo_email","nuevo_codigo_acceso","nuevo_dueno_select","nuevo_banco_nombre","nuevo_banco_cuenta"].forEach(id => $("#"+id).value="");
        cargar_datos_admin();
    } else {
        mostrar_aviso(datos.error || "Error al agregar usuario", 'error');
    }
});