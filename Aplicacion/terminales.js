/***
 * Funciones de puntos de venta (dueño).
 * @version 1.5piloto.73j
 */

async function cargar_datos_terminales() {
    if (!usuario_actual || usuario_actual.nivel !== 'dueno') return;

    let respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno: usuario_actual.nombre_usuario })
    });
    let datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar terminales", 'error');
        return;
    }

    const terminales = datos.terminales;
    const cuerpo_tabla = $("#tabla_terminales_dueno");
    cuerpo_tabla.innerHTML = "";
    terminales.forEach(terminal => {
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${terminal.nombre_usuario}</td>
            <td>${terminal.nombre_real || "—"}</td>
            <td>${terminal.email || "—"}</td>
            <td>${terminal.codigo_asignado ? "•••••" : "—"}</td>
            <td>${terminal.efectivo || "0"}</td>
            <td>${terminal.bancarizado || "0"}</td>
            <td>${terminal.banco.nombre || "—"}</td>
            <td>${terminal.banco.cuenta || "—"}</td>
            <td>${terminal.pasajes || "0"}</td>
            <td>
                <button class="btn_editar_terminal" data-usuario="${terminal.nombre_usuario}">✏️</button>
                <button class="btn_eliminar_terminal" data-usuario="${terminal.nombre_usuario}">🗑️</button>
            </td>
        `;
        cuerpo_tabla.appendChild(fila);
    });

    cuerpo_tabla.querySelectorAll('.btn_editar_terminal').forEach(boton => {
        boton.addEventListener('click', () => abrir_modal_editar_terminal(boton.dataset.usuario));
    });
    cuerpo_tabla.querySelectorAll('.btn_eliminar_terminal').forEach(boton => {
        boton.addEventListener('click', () => eliminar_terminal_confirmado(boton.dataset.usuario));
    });

    // El backend lee las terminales reales del dueño desde el grafo.
    // El cliente solo manda `nombre_dueno`; el solicitante va agregado
    // por el interceptor de fetch.
    respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_sesiones_terminales", nombre_dueno: usuario_actual.nombre_usuario })
    });
    datos = await respuesta.json();
    if (datos.exito) {
        const cuerpo_sesiones = $("#tabla_sesiones_terminales");
        cuerpo_sesiones.innerHTML = "";
        datos.sesiones.forEach(sesion => {
            const fila = document.createElement("tr");
            const es_sesion_actual = usuario_actual && usuario_actual.token_sesion === sesion.token;
            fila.innerHTML = `
                <td>${sesion.token}</td>
                <td>${sesion.usuario}</td>
                <td>${sesion.creado_en}</td>
                <td>${es_sesion_actual ? '<span class="badge">Actual</span>' : '<button class="btn danger btn_cerrar_sesion" data-token="' + sesion.token + '">Salir</button>'}</td>
            `;
            cuerpo_sesiones.appendChild(fila);
        });

        cuerpo_sesiones.querySelectorAll('.btn_cerrar_sesion').forEach(boton => {
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
                        cargar_datos_terminales();
                    } else {
                        mostrar_aviso(datos_cierre.error || "No se pudo cerrar la sesión", 'error');
                    }
                }
            });
        });
    }
}

/**
 * Abre el modal para crear una terminal del dueño.
 * Envuelve la función común con las opciones del dueño:
 * nivel forzado a terminal, sin selector de dueño, con banco.
 */
async function abrir_modal_agregar_terminal() {
    return abrir_modal_agregar_usuario_generico({
        accion_guardar: 'dueno/agregar_terminal',
        titulo: 'Nuevo punto de venta',
        mostrar_nivel: false,
        nivel_forzado: 'terminal',
        mostrar_banco: true,
        mostrar_dueno: false,
        mostrar_duenos_soporte: false,
        datos_extra: { nombre_dueno: usuario_actual.nombre_usuario },
        al_guardar_exito: cargar_datos_terminales
    });
}

$("#boton_agregar_terminal").addEventListener("click", abrir_modal_agregar_terminal);

async function obtener_datos_terminal(nombre_usuario) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno: usuario_actual.nombre_usuario })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        return datos.terminales.find(t => t.nombre_usuario === nombre_usuario);
    }
    return null;
}

/**
 * Abre el modal para editar una terminal del dueño.
 * Envuelve la función común con las opciones apropiadas para el
 * dueño: sin selector de nivel, sin selector de dueño, con banco.
 *
 * @param {string} nombre_usuario
 */
async function abrir_modal_editar_terminal(nombre_usuario) {
    return abrir_modal_editar_usuario_generico(nombre_usuario, {
        obtener_datos: obtener_datos_terminal,
        accion_guardar: 'dueno/actualizar_terminal',
        titulo: 'Editar punto de venta: ' + nombre_usuario,
        mostrar_nivel: false,
        nivel_forzado: 'terminal',
        mostrar_banco: true,
        mostrar_dueno: false,
        mostrar_duenos_soporte: false,
        datos_extra: { nombre_dueno: usuario_actual.nombre_usuario },
        al_guardar_exito: cargar_datos_terminales
    });
}

async function eliminar_terminal_confirmado(nombre_usuario) {
    if (!confirm(`¿Está seguro de eliminar el punto de venta "${nombre_usuario}"?`)) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/eliminar_terminal", nombre_usuario, nombre_dueno: usuario_actual.nombre_usuario })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        mostrar_aviso("Punto de venta eliminado correctamente", 'exito');
        cargar_datos_terminales();
    } else {
        mostrar_aviso(datos.error || "Error al eliminar", 'error');
    }
}