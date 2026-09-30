/***
 * Funciones de administración de usuarios.
 * @since 1.5piloto.15
 * @version 1.5piloto.73g
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
        _renderizar_tabla_usuarios(datos.usuarios);
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

async function _listar_duenos_admin() {
    const resp = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "administrador/listar_duenos" })
    });
    const datos = await resp.json();
    return datos.exito ? (datos.duenos || []) : [];
}

/**
 * Abre el modal para editar un usuario (envuelve la función común).
 * El admin puede editar usuarios de cualquier nivel. El nivel
 * "soporte" no se puede cambiar una vez creado.
 *
 * @param {string} nombre_usuario
 */
async function abrir_modal_editar_usuario(nombre_usuario) {
    return abrir_modal_editar_usuario_generico(nombre_usuario, {
        obtener_datos: obtener_datos_usuario,
        accion_guardar: 'administrador/actualizar_usuario',
        mostrar_nivel: true,
        niveles_disponibles: [
            { valor: 'terminal', etiqueta: 'Terminal' },
            { valor: 'dueno', etiqueta: 'Dueño' },
            { valor: 'admin', etiqueta: 'Administrador' },
            { valor: 'soporte', etiqueta: 'Soporte' }
        ],
        mostrar_banco: true,
        mostrar_dueno: true,
        mostrar_duenos_soporte: true,
        listar_duenos: _listar_duenos_admin,
        al_guardar_exito: cargar_datos_admin
    });
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

// El listener del formulario embebido de nuevo usuario (nuevo_nivel)
// se eliminó al pasar al modal. El HTML embebido se limpia en una
// tanda aparte.

/**
 * Abre el modal para crear un usuario. Envuelve la función
 * común con las opciones de admin: cualquier nivel, selector
 * de dueño, banco y checkboxes de soporte. Si el usuario actual
 * es soporte, restringe el nivel a terminal.
 */
async function abrir_modal_agregar_usuario() {
    const es_soporte = usuario_actual && usuario_actual.nivel === 'soporte';

    const niveles_disponibles = es_soporte
        ? [{ valor: 'terminal', etiqueta: 'Terminal' }]
        : [
            { valor: 'terminal', etiqueta: 'Terminal' },
            { valor: 'dueno', etiqueta: 'Dueño' },
            { valor: 'admin', etiqueta: 'Administrador' },
            { valor: 'soporte', etiqueta: 'Soporte' }
        ];

    return abrir_modal_agregar_usuario_generico({
        accion_guardar: 'administrador/agregar_usuario',
        titulo: 'Nuevo usuario',
        mostrar_nivel: true,
        niveles_disponibles: niveles_disponibles,
        mostrar_banco: true,
        mostrar_dueno: true,
        mostrar_duenos_soporte: !es_soporte,
        listar_duenos: _listar_duenos_admin,
        al_guardar_exito: cargar_datos_admin
    });
}

$("#boton_agregar_usuario").addEventListener("click", abrir_modal_agregar_usuario);


/**
 * Carga el selector de dueños en la pestaña admin para un soporte.
 */
async function _cargar_selector_dueno_admin() {
    const panel = document.getElementById('panel_selector_dueno_admin');
    const select = document.getElementById('selector_dueno_admin');
    if (!panel || !select) return;

    const duenos = await _listar_duenos_admin();

    select.innerHTML = '';
    if (duenos.length > 0) {
        duenos.forEach(d => {
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

