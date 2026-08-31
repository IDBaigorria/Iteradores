/***
 * Funciones de administración de usuarios.
 * @version 1.5piloto.15
 */

async function cargar_datos_admin() {
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
        datos.usuarios.forEach(usuario => {
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.nombre_usuario}</td>
                <td>${usuario.nombre_real || "—"}</td>
                <td>${usuario.email || "—"}</td>
                <td>${usuario.nivel}</td>
                <td>${usuario.codigo_acceso || "—"}</td>
                <td>${usuario.efectivo || "0"}</td>
                <td>${usuario.bancarizado || "0"}</td>
                <td>${usuario.banco.nombre || "—"}</td>
                <td>${usuario.banco.cuenta || "—"}</td>
                <td>${usuario.dueno || "—"}</td>
                <td>
                    <button class="btn_editar_usuario" data-usuario="${usuario.nombre_usuario}">✏️</button>
                    <button class="btn_eliminar_usuario" data-usuario="${usuario.nombre_usuario}">🗑️</button>
                </td>
            `;
            cuerpo_tabla.appendChild(fila);
        });

        cuerpo_tabla.querySelectorAll('.btn_editar_usuario').forEach(boton => {
            boton.addEventListener('click', () => iniciar_edicion_usuario(boton.dataset.usuario));
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

async function iniciar_edicion_usuario(nombre_usuario) {
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

    const cuerpo_tabla = $("#tabla_usuarios_admin");
    const filas = cuerpo_tabla.querySelectorAll('tr');
    let fila_objetivo = null;
    for (let fila of filas) {
        const boton_editar = fila.querySelector('.btn_editar_usuario');
        if (boton_editar && boton_editar.dataset.usuario === nombre_usuario) {
            fila_objetivo = fila;
            break;
        }
    }
    if (!fila_objetivo) return;

    const valor_nombre_real = usuario.nombre_real || '';
    const valor_email = usuario.email || '';
    const valor_nivel = usuario.nivel;
    const valor_codigo = usuario.codigo_acceso || '';
    const valor_banco_nombre = usuario.banco?.nombre || '';
    const valor_banco_cuenta = usuario.banco?.cuenta || '';
    const valor_dueno = usuario.dueno || '';

    let opciones_dueno = '<option value="">Seleccione dueño...</option>';
    duenos.forEach(dueno => {
        const seleccionado = dueno.nombre_usuario === valor_dueno ? 'selected' : '';
        opciones_dueno += `<option value="${dueno.nombre_usuario}" ${seleccionado}>${dueno.nombre_real ? dueno.nombre_real + ' (' + dueno.nombre_usuario + ')' : dueno.nombre_usuario}</option>`;
    });

    let celda_dueno;
    if (valor_nivel === 'terminal') {
        celda_dueno = `<td><select id="editar_dueno">${opciones_dueno}</select></td>`;
    } else {
        celda_dueno = `<td>—</td>`;
    }

    fila_objetivo.innerHTML = `
        <td>${nombre_usuario}</td>
        <td><input type="text" id="editar_nombre_real" value="${valor_nombre_real}"></td>
        <td><input type="email" id="editar_email" value="${valor_email}"></td>
        <td>
            <select id="editar_nivel">
                <option value="terminal" ${valor_nivel === 'terminal' ? 'selected' : ''}>Terminal</option>
                <option value="dueno" ${valor_nivel === 'dueno' ? 'selected' : ''}>Dueño</option>
                <option value="admin" ${valor_nivel === 'admin' ? 'selected' : ''}>Administrador</option>
            </select>
        </td>
        <td><input type="text" id="editar_codigo" value="${valor_codigo}"></td>
        <td>${usuario.efectivo || '0'}</td>
        <td>${usuario.bancarizado || '0'}</td>
        <td><input type="text" id="editar_banco_nombre" value="${valor_banco_nombre}"></td>
        <td><input type="text" id="editar_banco_cuenta" value="${valor_banco_cuenta}"></td>
        ${celda_dueno}
        <td>
            <button class="btn_guardar_edicion" data-usuario="${nombre_usuario}">💾</button>
            <button class="btn_cancelar_edicion">❌</button>
        </td>
    `;

    const select_nivel = fila_objetivo.querySelector('#editar_nivel');
    const campo_banco_nombre = fila_objetivo.querySelector('#editar_banco_nombre').parentElement;
    const campo_banco_cuenta = fila_objetivo.querySelector('#editar_banco_cuenta').parentElement;
    const campo_dueno = fila_objetivo.querySelector('#editar_dueno')?.parentElement;

    function actualizar_visibilidad() {
        const es_terminal = select_nivel.value === 'terminal';
        campo_banco_nombre.style.display = es_terminal ? '' : 'none';
        campo_banco_cuenta.style.display = es_terminal ? '' : 'none';
        if (campo_dueno) {
            campo_dueno.style.display = es_terminal ? '' : 'none';
        }
    }
    select_nivel.addEventListener('change', actualizar_visibilidad);
    actualizar_visibilidad();

    fila_objetivo.querySelector('.btn_guardar_edicion').addEventListener('click', async () => {
        await guardar_edicion_usuario(nombre_usuario, fila_objetivo);
    });
    fila_objetivo.querySelector('.btn_cancelar_edicion').addEventListener('click', () => {
        cargar_datos_admin();
    });
}

async function guardar_edicion_usuario(nombre_usuario, fila) {
    const nivel = fila.querySelector('#editar_nivel').value;
    const select_dueno = fila.querySelector('#editar_dueno');
    const dueno = select_dueno ? select_dueno.value : '';

    const datos = {
        accion: "administrador/actualizar_usuario",
        nombre_usuario: nombre_usuario,
        nombre_real: fila.querySelector('#editar_nombre_real').value.trim(),
        email: fila.querySelector('#editar_email').value.trim(),
        nivel: nivel,
        codigo_acceso: fila.querySelector('#editar_codigo').value.trim(),
        banco_nombre: fila.querySelector('#editar_banco_nombre').value.trim(),
        banco_cuenta: fila.querySelector('#editar_banco_cuenta').value.trim(),
        dueno: nivel === 'terminal' ? dueno : '',
        contrasena: ''
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
        mostrar_aviso("Usuario actualizado correctamente", 'exito');
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
    document.getElementById("campo_dueno").style.display = es_terminal ? "block" : "none";
    document.getElementById("campo_banco_nombre").style.display = es_terminal ? "block" : "none";
    document.getElementById("campo_banco_cuenta").style.display = es_terminal ? "block" : "none";
});

$("#boton_agregar_usuario").addEventListener("click", async () => {
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
        } else {
            mostrar_aviso("No se pudieron cargar los dueños", 'error');
        }
    } catch (e) {
        console.error("Error al cargar dueños", e);
        mostrar_aviso("Error al cargar dueños", 'error');
    }
    $("#formulario_nuevo_usuario").classList.remove("hidden");
});

$("#boton_cancelar_nuevo_usuario").addEventListener("click", () => {
    $("#formulario_nuevo_usuario").classList.add("hidden");
});

$("#boton_guardar_usuario").addEventListener("click", async () => {
    const nivel = $("#nuevo_nivel").value;
    const datos_usuario = {
        accion: "administrador/agregar_usuario",
        nombre_usuario: $("#nuevo_nombre_usuario").value.trim(),
        contrasena: $("#nuevo_contrasena").value,
        nombre_real: $("#nuevo_nombre_real").value.trim(),
        email: $("#nuevo_email").value.trim(),
        codigo_acceso: $("#nuevo_codigo_acceso").value.trim(),
        nivel: nivel,
        dueno: nivel === "terminal" ? $("#nuevo_dueno_select").value : "",
        banco_nombre: nivel === "terminal" ? $("#nuevo_banco_nombre").value.trim() : "",
        banco_cuenta: nivel === "terminal" ? $("#nuevo_banco_cuenta").value.trim() : ""
    };

    if (!datos_usuario.nombre_usuario || !datos_usuario.codigo_acceso) {
        mostrar_aviso("Nombre de usuario y código de acceso son obligatorios", 'error');
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
        mostrar_aviso("Usuario agregado correctamente", 'exito');
        $("#formulario_nuevo_usuario").classList.add("hidden");
        ["nuevo_nombre_usuario","nuevo_contrasena","nuevo_nombre_real","nuevo_email","nuevo_codigo_acceso","nuevo_dueno_select","nuevo_banco_nombre","nuevo_banco_cuenta"].forEach(id => $("#"+id).value="");
        cargar_datos_admin();
    } else {
        mostrar_aviso(datos.error || "Error al agregar usuario", 'error');
    }
});