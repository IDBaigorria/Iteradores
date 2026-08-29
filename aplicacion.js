/***
 * Aplicación principal.
 * @version 1.5piloto.4
 */

// Utilidades
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

const DEBUG_FETCH = true;
if (DEBUG_FETCH) {
    const fetch_original = window.fetch;
    window.fetch = async function (...args) {
        const [url, opciones] = args;
        console.group(`[fetch] ${url}`);
        console.log("Opciones:", opciones);
        try {
            const respuesta = await fetch_original(...args);
            const clon = respuesta.clone();
            const texto = await clon.text();
            console.log("Estado:", respuesta.status);
            console.log("Respuesta cruda:", texto);
            try {
                const json = JSON.parse(texto);
                console.log("JSON parseado:", json);
            } catch (e) {
                console.warn("La respuesta no es JSON válido:", texto);
            }
            console.groupEnd();
            return respuesta;
        } catch (error) {
            console.error("Error en fetch:", error);
            console.groupEnd();
            throw error;
        }
    };
}

let usuario_actual = null;
let vehiculo_seleccionado_micros = null; // Almacena {nombre_vehiculo, nombre, asientos}
let viaje_seleccionado = null;

function mostrar_aviso(mensaje) {
    const aviso = $("#toast");
    aviso.textContent = mensaje;
    aviso.classList.add("show");
    clearTimeout(window.temporizador_aviso);
    window.temporizador_aviso = setTimeout(() => aviso.classList.remove("show"), 1800);
}

function configurar_pestanas_segun_nivel(nivel) {
    const pestanas_permitidas = {
        admin: ['admin', 'micros', 'viajes', 'vendidos', 'pasajeros'],
        dueno: ['terminales', 'micros', 'viajes', 'vendidos', 'pasajeros'],
        terminal: ['viajes', 'vendidos', 'pasajeros']
    };
    const permitidas = pestanas_permitidas[nivel] || [];
    const nav = $("#pestanas");
    nav.innerHTML = '';
    const nombres_pestanas = {
        admin: 'Administrador',
        micros: 'Empresas/Micros',
        viajes: 'Viajes',
        vendidos: 'Vendidos',
        pasajeros: 'Pasajeros/Clientes',
        terminales: 'Puntos de venta'
    };
    permitidas.forEach(id_pestana => {
        const boton = document.createElement('button');
        boton.className = 'tab';
        boton.dataset.tab = id_pestana;
        boton.textContent = nombres_pestanas[id_pestana];
        boton.addEventListener('click', () => activar_pestana(id_pestana));
        nav.appendChild(boton);
    });
    if (permitidas.length > 0) activar_pestana(permitidas[0]);
}

function activar_pestana(id_pestana) {
    $$(".tab").forEach(boton => boton.classList.remove("active"));
    const boton_activo = document.querySelector(`.tab[data-tab="${id_pestana}"]`);
    if (boton_activo) boton_activo.classList.add("active");
    $$(".tab-content").forEach(seccion => seccion.classList.add("hidden"));
    const seccion_activa = document.getElementById(id_pestana);
    if (seccion_activa) seccion_activa.classList.remove("hidden");
    if (id_pestana === 'micros') {
        cargar_datos_micros();
        construir_asientos("filas_asientos_estaticas", false);
    }
    if (id_pestana === 'viajes') {
        cargar_viajes();
    }

    if (id_pestana === 'vendidos') renderizar_ventas($("#filtro_vendedor").value);
    if (id_pestana === 'pasajeros') renderizar_pasajeros();
    if (id_pestana === 'admin') cargar_datos_admin();
    if (id_pestana === 'terminales') cargar_datos_terminales();
}

async function ingresar_con_codigo() {
    const codigo = $("#codigo_acceso").value.trim();
    if (!codigo) { mostrar_aviso("Ingrese el código"); return; }
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "autenticar/verificar", codigo })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            usuario_actual = datos.usuario;
            localStorage.setItem('token_sesion', usuario_actual.token_sesion);
            localStorage.setItem('usuario_actual', JSON.stringify(usuario_actual));
            $("#pantalla_login").classList.add("hidden");
            $("#aplicacion").classList.remove("hidden");
            $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;
            $("#nivel_usuario_actual").textContent = usuario_actual.nivel;
            configurar_pestanas_segun_nivel(usuario_actual.nivel);
        } else {
            mostrar_aviso(datos.error || "Código incorrecto");
        }
    } catch (error) {
        console.error("Error en autenticación:", error);
        mostrar_aviso("Error de comunicación");
    }
}

async function salir() {
    if (usuario_actual && usuario_actual.token_sesion) {
        try {
            await fetch("index.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ accion: "sesiones/cerrar", token: usuario_actual.token_sesion })
            });
        } catch (e) { console.error("Error al cerrar sesión", e); }
    }
    localStorage.removeItem('token_sesion');
    localStorage.removeItem('usuario_actual');
    usuario_actual = null;
    $("#aplicacion").classList.add("hidden");
    $("#pantalla_login").classList.remove("hidden");
    $("#codigo_acceso").value = "";
}

document.addEventListener('DOMContentLoaded', async () => {
    const token = localStorage.getItem('token_sesion');
    if (token) {
        try {
            const respuesta = await fetch("index.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ accion: "sesiones/validar", token })
            });
            const datos = await respuesta.json();
            if (datos.exito) {
                usuario_actual = datos.usuario;
                usuario_actual.token_sesion = token;
                $("#pantalla_login").classList.add("hidden");
                $("#aplicacion").classList.remove("hidden");
                $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;
                $("#nivel_usuario_actual").textContent = usuario_actual.nivel;
                configurar_pestanas_segun_nivel(usuario_actual.nivel);
            } else {
                localStorage.removeItem('token_sesion');
                localStorage.removeItem('usuario_actual');
                mostrar_aviso('Sesión expirada, ingrese nuevamente');
            }
        } catch (e) {
            console.error('Error al validar sesión', e);
            localStorage.removeItem('token_sesion');
            localStorage.removeItem('usuario_actual');
        }
    }
});

$("#boton_ingresar").addEventListener("click", ingresar_con_codigo);
$("#codigo_acceso").addEventListener("keypress", (e) => { if (e.key === "Enter") ingresar_con_codigo(); });
$("#boton_salir").addEventListener("click", salir);

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
                    <button class="btn_editar_usuario" data-usuario="${usuario.nombre_usuario}" title="Editar">✏️</button>
                    <button class="btn_eliminar_usuario" data-usuario="${usuario.nombre_usuario}" title="Eliminar">🗑️</button>
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
                        mostrar_aviso("Sesión cerrada correctamente");
                        cargar_datos_admin();
                    } else {
                        mostrar_aviso(datos_cierre.error || "No se pudo cerrar la sesión");
                    }
                }
            });
        });
    }
}

// Funciones para editar y eliminar usuarios
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

    // Cargar dueños para el select
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

    // Construir opciones para el select de dueños
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

    const html = `
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
    fila_objetivo.innerHTML = html;

    // Lógica de visibilidad de campos según nivel
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
        contrasena: '' // No se cambia en edición rápida
    };

    if (nivel === 'terminal') {
        if (!datos.dueno) {
            mostrar_aviso("Debe seleccionar un dueño");
            return;
        }
        if (!datos.banco_nombre || !datos.banco_cuenta) {
            mostrar_aviso("Banco y cuenta son obligatorios para terminales");
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
        mostrar_aviso("Usuario actualizado correctamente");
        cargar_datos_admin();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar");
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
        mostrar_aviso("Usuario eliminado correctamente");
        cargar_datos_admin();
    } else {
        mostrar_aviso(datos.error || "Error al eliminar");
    }
}

// Manejo del formulario de nuevo usuario
$("#nuevo_nivel").addEventListener("change", function() {
  const nivel = this.value;
  const es_terminal = nivel === "terminal";
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
      mostrar_aviso("No se pudieron cargar los dueños");
    }
  } catch (e) {
    console.error("Error al cargar dueños", e);
    mostrar_aviso("Error al cargar dueños");
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
    mostrar_aviso("Nombre de usuario y código de acceso son obligatorios");
    return;
  }
  if (nivel === "terminal") {
    if (!datos_usuario.dueno) {
      mostrar_aviso("Debe seleccionar un dueño");
      return;
    }
    if (!datos_usuario.banco_nombre || !datos_usuario.banco_cuenta) {
      mostrar_aviso("Banco y cuenta son obligatorios para terminales");
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
    mostrar_aviso("Usuario agregado correctamente");
    $("#formulario_nuevo_usuario").classList.add("hidden");
    ["nuevo_nombre_usuario","nuevo_contrasena","nuevo_nombre_real","nuevo_email","nuevo_codigo_acceso","nuevo_dueno_select","nuevo_banco_nombre","nuevo_banco_cuenta"].forEach(id => $("#"+id).value="");
    cargar_datos_admin();
  } else {
    mostrar_aviso(datos.error || "Error al agregar usuario");
  }
});
/*async function cargar_datos_terminales() {
    if (!usuario_actual || usuario_actual.nivel !== 'dueno') return;

    // Obtener terminales del dueño actual
    let respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno: usuario_actual.nombre_usuario })
    });
    let datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar terminales");
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
            <td>${terminal.codigo_acceso || "—"}</td>
            <td>${terminal.efectivo || "0"}</td>
            <td>${terminal.bancarizado || "0"}</td>
            <td>${terminal.banco.nombre || "—"}</td>
            <td>${terminal.banco.cuenta || "—"}</td>
            <td>${terminal.pasajes || "0"}</td>
        `;
        cuerpo_tabla.appendChild(fila);
    });

    // Obtener sesiones de esas terminales
    const nombres_terminales = terminales.map(t => t.nombre_usuario);
    respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_sesiones_terminales", terminales: JSON.stringify(nombres_terminales) })
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
                        mostrar_aviso("Sesión cerrada correctamente");
                        cargar_datos_terminales();
                    } else {
                        mostrar_aviso(datos_cierre.error || "No se pudo cerrar la sesión");
                    }
                }
            });
        });
    }
}*/


// ====== Funciones para puntos de venta (dueño) ======
async function cargar_datos_terminales() {
    if (!usuario_actual || usuario_actual.nivel !== 'dueno') return;

    // Obtener terminales del dueño actual
    let respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_terminales", nombre_dueno: usuario_actual.nombre_usuario })
    });
    let datos = await respuesta.json();
    if (!datos.exito) {
        mostrar_aviso(datos.error || "Error al cargar terminales");
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
            <td>${terminal.codigo_acceso || "—"}</td>
            <td>${terminal.efectivo || "0"}</td>
            <td>${terminal.bancarizado || "0"}</td>
            <td>${terminal.banco.nombre || "—"}</td>
            <td>${terminal.banco.cuenta || "—"}</td>
            <td>${terminal.pasajes || "0"}</td>
            <td>
                <button class="btn_editar_terminal" data-usuario="${terminal.nombre_usuario}" title="Editar">✏️</button>
                <button class="btn_eliminar_terminal" data-usuario="${terminal.nombre_usuario}" title="Eliminar">🗑️</button>
            </td>
        `;
        cuerpo_tabla.appendChild(fila);
    });

    // Agregar eventos
    cuerpo_tabla.querySelectorAll('.btn_editar_terminal').forEach(boton => {
        boton.addEventListener('click', () => iniciar_edicion_terminal(boton.dataset.usuario));
    });
    cuerpo_tabla.querySelectorAll('.btn_eliminar_terminal').forEach(boton => {
        boton.addEventListener('click', () => eliminar_terminal_confirmado(boton.dataset.usuario));
    });

    // Obtener sesiones de esas terminales
    const nombres_terminales = terminales.map(t => t.nombre_usuario);
    respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "dueno/listar_sesiones_terminales", terminales: JSON.stringify(nombres_terminales) })
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
                        mostrar_aviso("Sesión cerrada correctamente");
                        cargar_datos_terminales();
                    } else {
                        mostrar_aviso(datos_cierre.error || "No se pudo cerrar la sesión");
                    }
                }
            });
        });
    }
}

// Mostrar/ocultar formulario nuevo punto de venta
$("#boton_agregar_terminal").addEventListener("click", () => {
    $("#formulario_nueva_terminal").classList.remove("hidden");
});

$("#boton_cancelar_terminal").addEventListener("click", () => {
    $("#formulario_nueva_terminal").classList.add("hidden");
});

$("#boton_guardar_terminal").addEventListener("click", async () => {
    const datos_terminal = {
        accion: "dueno/agregar_terminal",
        nombre_dueno: usuario_actual.nombre_usuario,
        nombre_usuario: $("#nuevo_terminal_nombre_usuario").value.trim(),
        contrasena: $("#nuevo_terminal_contrasena").value,
        nombre_real: $("#nuevo_terminal_nombre_real").value.trim(),
        email: $("#nuevo_terminal_email").value.trim(),
        codigo_acceso: $("#nuevo_terminal_codigo_acceso").value.trim(),
        banco_nombre: $("#nuevo_terminal_banco_nombre").value.trim(),
        banco_cuenta: $("#nuevo_terminal_banco_cuenta").value.trim()
    };

    if (!datos_terminal.nombre_usuario || !datos_terminal.codigo_acceso) {
        mostrar_aviso("Nombre de usuario y código de acceso son obligatorios");
        return;
    }
    if (!datos_terminal.banco_nombre || !datos_terminal.banco_cuenta) {
        mostrar_aviso("Banco y cuenta son obligatorios");
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos_terminal)
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        mostrar_aviso("Punto de venta agregado correctamente");
        $("#formulario_nueva_terminal").classList.add("hidden");
        // Limpiar campos
        ["nuevo_terminal_nombre_usuario","nuevo_terminal_contrasena","nuevo_terminal_nombre_real","nuevo_terminal_email","nuevo_terminal_codigo_acceso","nuevo_terminal_banco_nombre","nuevo_terminal_banco_cuenta"].forEach(id => $("#"+id).value="");
        cargar_datos_terminales();
    } else {
        mostrar_aviso(datos.error || "Error al agregar punto de venta");
    }
});

// Edición inline de terminal
async function iniciar_edicion_terminal(nombre_usuario) {
    const terminal = await obtener_datos_terminal(nombre_usuario);
    if (!terminal) return;

    const cuerpo_tabla = $("#tabla_terminales_dueno");
    const filas = cuerpo_tabla.querySelectorAll('tr');
    let fila_objetivo = null;
    for (let fila of filas) {
        const boton_editar = fila.querySelector('.btn_editar_terminal');
        if (boton_editar && boton_editar.dataset.usuario === nombre_usuario) {
            fila_objetivo = fila;
            break;
        }
    }
    if (!fila_objetivo) return;

    const valor_nombre_real = terminal.nombre_real || '';
    const valor_email = terminal.email || '';
    const valor_codigo = terminal.codigo_acceso || '';
    const valor_banco_nombre = terminal.banco.nombre || '';
    const valor_banco_cuenta = terminal.banco.cuenta || '';

    fila_objetivo.innerHTML = `
        <td>${nombre_usuario}</td>
        <td><input type="text" id="editar_terminal_nombre_real" value="${valor_nombre_real}"></td>
        <td><input type="email" id="editar_terminal_email" value="${valor_email}"></td>
        <td><input type="text" id="editar_terminal_codigo" value="${valor_codigo}"></td>
        <td>${terminal.efectivo || '0'}</td>
        <td>${terminal.bancarizado || '0'}</td>
        <td><input type="text" id="editar_terminal_banco_nombre" value="${valor_banco_nombre}"></td>
        <td><input type="text" id="editar_terminal_banco_cuenta" value="${valor_banco_cuenta}"></td>
        <td>${terminal.pasajes || '0'}</td>
        <td>
            <button class="btn_guardar_edicion_terminal" data-usuario="${nombre_usuario}">💾</button>
            <button class="btn_cancelar_edicion_terminal">❌</button>
        </td>
    `;

    fila_objetivo.querySelector('.btn_guardar_edicion_terminal').addEventListener('click', async () => {
        await guardar_edicion_terminal(nombre_usuario, fila_objetivo);
    });
    fila_objetivo.querySelector('.btn_cancelar_edicion_terminal').addEventListener('click', () => {
        cargar_datos_terminales();
    });
}

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

async function guardar_edicion_terminal(nombre_usuario, fila) {
    const datos = {
        accion: "dueno/actualizar_terminal",
        nombre_dueno: usuario_actual.nombre_usuario,
        nombre_usuario: nombre_usuario,
        nombre_real: fila.querySelector('#editar_terminal_nombre_real').value.trim(),
        email: fila.querySelector('#editar_terminal_email').value.trim(),
        codigo_acceso: fila.querySelector('#editar_terminal_codigo').value.trim(),
        banco_nombre: fila.querySelector('#editar_terminal_banco_nombre').value.trim(),
        banco_cuenta: fila.querySelector('#editar_terminal_banco_cuenta').value.trim(),
        contrasena: ''
    };

    if (!datos.banco_nombre || !datos.banco_cuenta) {
        mostrar_aviso("Banco y cuenta son obligatorios");
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta actualizado correctamente");
        cargar_datos_terminales();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar");
    }
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
        mostrar_aviso("Punto de venta eliminado correctamente");
        cargar_datos_terminales();
    } else {
        mostrar_aviso(datos.error || "Error al eliminar");
    }
}

async function cargar_datos_micros() {
    // Limpiar selects
    $("#selector_dueno_micros").innerHTML = '';
    $("#selector_empresa_micros").innerHTML = '';
    $("#selector_vehiculo_micros").innerHTML = '';
    $("#croquis_pisos_micros").innerHTML = '';

    // Ocultar paneles por defecto
    $("#panel_selector_dueno_micros").style.display = 'none';
    $("#panel_empresas_micros").style.display = 'none';
    $("#panel_vehiculos_micros").style.display = 'none';
    $("#panel_croquis_micros").style.display = 'none';

    if (!usuario_actual) return;

    if (usuario_actual.nivel === 'admin') {
        // Mostrar selector de dueños
        $("#panel_selector_dueno_micros").style.display = 'block';
        await cargar_duenos_en_select_micros();
    } else if (usuario_actual.nivel === 'dueno') {
        // Dueño directo
        await cargar_empresas_de_dueno(usuario_actual.nombre_usuario);
        $("#panel_empresas_micros").style.display = 'block';
    }
}

async function cargar_duenos_en_select_micros() {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "administrador/listar_duenos" })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_dueno_micros");
            select.innerHTML = '<option value="">Seleccione dueño...</option>';
            datos.duenos.forEach(dueno => {
                const opcion = document.createElement('option');
                opcion.value = dueno.nombre_usuario;
                opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;
                select.appendChild(opcion);
            });
            select.onchange = async () => {
                const nombre_dueno = select.value;
                if (nombre_dueno) {
                    await cargar_empresas_de_dueno(nombre_dueno);
                    $("#panel_empresas_micros").style.display = 'block';
                } else {
                    $("#panel_empresas_micros").style.display = 'none';
                }
            };
        }
    } catch (e) { console.error(e); }
}

async function cargar_empresas_de_dueno(nombre_dueno) {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "empresas/listar", nombre_dueno })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_empresa_micros");
            select.innerHTML = '<option value="">Seleccione empresa...</option>';
            datos.empresas.forEach(empresa => {
                const opcion = document.createElement('option');
                opcion.value = empresa.nombre_empresa;
                opcion.textContent = empresa.nombre;
                select.appendChild(opcion);
            });
            select.onchange = async () => {
                const nombre_empresa = select.value;
                if (nombre_empresa) {
                    await cargar_vehiculos_de_empresa(nombre_empresa);
                    $("#panel_vehiculos_micros").style.display = 'block';
                } else {
                    $("#panel_vehiculos_micros").style.display = 'none';
                    $("#panel_croquis_micros").style.display = 'none';
                }
            };
        }
    } catch (e) { console.error(e); }
}

async function cargar_vehiculos_de_empresa(nombre_empresa) {
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            const select = $("#selector_vehiculo_micros");
            select.innerHTML = '<option value="">Seleccione vehículo...</option>';
            datos.vehiculos.forEach(vehiculo => {
                const opcion = document.createElement('option');
                opcion.value = vehiculo.nombre_vehiculo;
                opcion.textContent = vehiculo.nombre;
                select.appendChild(opcion);
            });

            if (datos.vehiculos.length > 0) {
                select.selectedIndex = 1; // primer vehículo
                vehiculo_seleccionado_micros = datos.vehiculos[0];
                mostrar_croquis_vehiculo(vehiculo_seleccionado_micros);
                // Mostrar la foto estática
                actualizar_foto_vehiculo(vehiculo_seleccionado_micros.foto, false);
                $("#panel_croquis_micros").style.display = 'block';
                $("#area_croquis_estatico_micros").classList.remove("hidden");
                $("#panel_edicion_vehiculo_micros").classList.add("hidden");
            } else {
                vehiculo_seleccionado_micros = null;
                $("#panel_croquis_micros").style.display = 'none';
            }

            select.onchange = async () => {
                const vehiculo = datos.vehiculos.find(v => v.nombre_vehiculo === select.value);
                if (vehiculo) {
                    vehiculo_seleccionado_micros = vehiculo;
                    mostrar_croquis_vehiculo(vehiculo);
                    // Mostrar la foto estática
                    actualizar_foto_vehiculo(vehiculo.foto, false);
                    $("#panel_croquis_micros").style.display = 'block';
                    $("#area_croquis_estatico_micros").classList.remove("hidden");
                    $("#panel_edicion_vehiculo_micros").classList.add("hidden");
                } else {
                    vehiculo_seleccionado_micros = null;
                    $("#panel_croquis_micros").style.display = 'none';
                }
            };
        }
    } catch (e) {
        console.error(e);
    }
}
function actualizar_foto_vehiculo(ruta, es_editor = false) {
    if (es_editor) {
        const img = $("#foto_vehiculo_editor_img");
        const placeholder = $("#foto_vehiculo_editor_placeholder");
        const boton_subir = $("#boton_subir_foto");
        const boton_cambiar = $("#boton_cambiar_foto");
        if (ruta) {
            img.src = ruta;
            img.style.display = 'block';
            placeholder.style.display = 'none';
            boton_subir.style.display = 'none';
            boton_cambiar.style.display = 'inline-block';
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'block';
            boton_subir.style.display = 'inline-block';
            boton_cambiar.style.display = 'none';
        }
    } else {
        const img = $("#foto_vehiculo_estatica");
        const placeholder = $("#foto_vehiculo_estatica_placeholder");
        if (ruta) {
            img.src = ruta;
            img.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            img.style.display = 'none';
            placeholder.style.display = 'block';
        }
    }
}


function mostrar_croquis_vehiculo(vehiculo) {
    const contenedor = $("#croquis_pisos_micros");
    contenedor.innerHTML = '';

    const configuracion = vehiculo.configuracion;
    if (!configuracion || !configuracion.pisos || configuracion.pisos.length === 0) {
        // Si no hay configuración, mostrar croquis simple basado en cantidad total (como antes)
        const num_asientos = parseInt(vehiculo.asientos) || 44;
        const filas = Math.ceil(num_asientos / 4);
        const pisoDiv = document.createElement('div');
        pisoDiv.innerHTML = `<div class="bus-area"><div class="section-title">Piso único</div><div class="bus">`;
        // ... construir igual que antes pero dentro de este div ...
        // Para simplificar, copiamos la lógica anterior generando filas con 4 asientos.
        // (Se muestra solo un piso)
        // Código similar al anterior pero ahora dentro de la estructura.
        // Por brevedad, asumimos que se puede reutilizar el código anterior.
    } else {
        configuracion.pisos.forEach((piso, index) => {
            const titulo = document.createElement('div');
            titulo.className = 'section-title';
            titulo.textContent = `Piso ${index + 1}`;
            contenedor.appendChild(titulo);

            const busDiv = document.createElement('div');
            busDiv.className = 'bus';
            busDiv.innerHTML = `<div class="bus-front">FRENTE · CONDUCTOR</div>`;

            for (let fila = 1; fila <= piso.filas; fila++) {
                const filaDiv = document.createElement('div');
                filaDiv.className = 'seat-row';
                for (let col = 1; col <= piso.columnas; col++) {
                    // Buscar si hay un asiento en esta fila/columna
                    const asiento = piso.asientos.find(a => parseInt(a.fila) === fila && parseInt(a.columna) === col);
                    if (asiento) {
                        const seat = document.createElement('div');
                        seat.className = 'seat';
                        seat.textContent = asiento.numero.padStart(2, '0');
                        filaDiv.appendChild(seat);
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'aisle'; // o 'empty-cell'
                        filaDiv.appendChild(empty);
                    }
                }
                busDiv.appendChild(filaDiv);
            }

            busDiv.innerHTML += `<div class="bus-back">PARTE TRASERA</div>`;
            contenedor.appendChild(busDiv);
        });
    }
}

/*function iniciar_edicion_vehiculo() {
    if (!vehiculo_seleccionado_micros) {
        mostrar_aviso("Seleccione un vehículo primero");
        return;
    }

    // Llenar el formulario con los datos actuales
    $("#editar_vehiculo_patente").value = vehiculo_seleccionado_micros.nombre_vehiculo;
    $("#editar_vehiculo_nombre_real").value = vehiculo_seleccionado_micros.nombre;
    $("#editar_vehiculo_asientos").value = vehiculo_seleccionado_micros.asientos;

    // Mostrar formulario y ocultar croquis
    $("#area_croquis_estatico_micros").classList.add("hidden");
    $("#panel_edicion_vehiculo_micros").classList.remove("hidden");
}
*//*
function cancelar_edicion_vehiculo() {
    // Ocultar formulario y volver a mostrar el croquis
    $("#panel_edicion_vehiculo_micros").classList.add("hidden");
    $("#area_croquis_estatico_micros").classList.remove("hidden");
}

async function guardar_edicion_vehiculo() {
    if (!vehiculo_seleccionado_micros) {
        mostrar_aviso("No hay vehículo seleccionado");
        return;
    }

    const nombre_empresa = $("#selector_empresa_micros").value;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa");
        return;
    }

    const nombre_real = $("#editar_vehiculo_nombre_real").value.trim();
    const asientos = $("#editar_vehiculo_asientos").value;

    if (!nombre_real) {
        mostrar_aviso("El nombre visible no puede estar vacío");
        return;
    }
    if (!asientos || parseInt(asientos) <= 0) {
        mostrar_aviso("La cantidad de asientos debe ser mayor que cero");
        return;
    }

    const datos = {
        accion: "vehiculos/actualizar",
        nombre_empresa: nombre_empresa,
        nombre_vehiculo: vehiculo_seleccionado_micros.nombre_vehiculo,
        nombre_real: nombre_real,
        asientos: asientos
    };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Vehículo actualizado correctamente");
        cancelar_edicion_vehiculo();
        // Recargar la lista para reflejar cambios
        await cargar_vehiculos_de_empresa(nombre_empresa);
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar");
    }
}
*/
// Botón agregar empresa
$("#boton_agregar_empresa_micros").addEventListener("click", () => {
    $("#formulario_nueva_empresa").classList.remove("hidden");
});

$("#boton_cancelar_empresa").addEventListener("click", () => {
    $("#formulario_nueva_empresa").classList.add("hidden");
});

$("#boton_guardar_empresa").addEventListener("click", async () => {
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_dueno) {
        mostrar_aviso("Seleccione un dueño");
        return;
    }
    const datos = {
        accion: "empresas/agregar",
        nombre_dueno,
        nombre_empresa: $("#nueva_empresa_nombre").value.trim(),
        nombre_real: $("#nueva_empresa_nombre_real").value.trim()
    };
    if (!datos.nombre_empresa) {
        mostrar_aviso("Nombre de empresa obligatorio");
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa agregada");
        $("#formulario_nueva_empresa").classList.add("hidden");
        $("#nueva_empresa_nombre").value = "";
        $("#nueva_empresa_nombre_real").value = "";
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al agregar empresa");
    }
});

// Botón agregar vehículo (similar)
$("#boton_agregar_vehiculo_micros").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.remove("hidden");
});

$("#boton_cancelar_vehiculo").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.add("hidden");
});

$("#boton_guardar_vehiculo").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa");
        return;
    }
    const datos = {
        accion: "vehiculos/agregar",
        nombre_empresa,
        nombre_vehiculo: $("#nuevo_vehiculo_nombre").value.trim(),
        nombre_real: $("#nuevo_vehiculo_nombre_real").value.trim()
    };
    if (!datos.nombre_vehiculo) {
        mostrar_aviso("Patente obligatoria");
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Vehículo agregado. Configure los asientos.");
        $("#formulario_nuevo_vehiculo").classList.add("hidden");
        await cargar_vehiculos_de_empresa(nombre_empresa);
        // Seleccionar el vehículo recién agregado y abrir editor
        const select = $("#selector_vehiculo_micros");
        select.value = datos.nombre_vehiculo;
        // Disparar evento change para cargar vehículo
        select.dispatchEvent(new Event('change'));
        // Esperar un pequeño tiempo para que se cargue y luego abrir editor
        setTimeout(() => {
            if (vehiculo_seleccionado_micros && vehiculo_seleccionado_micros.nombre_vehiculo === datos.nombre_vehiculo) {
                iniciar_editor_vehiculo();
            }
        }, 300);
    } else {
        mostrar_aviso(resultado.error || "Error al agregar vehículo");
    }
});

$("#boton_reiniciar_asientos").addEventListener("click", () => {
    editor_pisos_actuales.forEach(piso => {
        piso.asientos = [];
    });
    // Redibujar cuadrículas
    document.querySelectorAll('.cuadricula-editor').forEach((cuadricula, idx) => {
        renderizar_cuadricula_editor(cuadricula.parentElement, idx);
    });
    mostrar_aviso("Asientos reiniciados");
});
$("#boton_subir_foto").addEventListener("click", () => {
    $("#input_foto_vehiculo").click();
});

$("#boton_cambiar_foto").addEventListener("click", () => {
    $("#input_foto_vehiculo").click();
});

$("#input_foto_vehiculo").addEventListener("change", async (e) => {
    const archivo = e.target.files[0];
    if (!archivo) return;
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = vehiculo_seleccionado_micros?.nombre_vehiculo;
    if (!nombre_vehiculo) {
        mostrar_aviso("Seleccione un vehículo primero");
        return;
    }
    const formData = new FormData();
    formData.append('accion', 'vehiculos/subir_foto');
    formData.append('nombre_empresa', nombre_empresa);
    formData.append('nombre_vehiculo', nombre_vehiculo);
    formData.append('foto', archivo);

    const respuesta = await fetch("index.php", {
        method: "POST",
        body: formData
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        // Actualizar variable global
        if (vehiculo_seleccionado_micros) {
            vehiculo_seleccionado_micros.foto = resultado.foto;
        }
        // Actualizar vistas
        actualizar_foto_vehiculo(resultado.foto, false);
        actualizar_foto_vehiculo(resultado.foto, true);
        mostrar_aviso("Foto actualizada");
    } else {
        mostrar_aviso(resultado.error || "Error al subir foto");
    }
});
// ===== Editor de configuración de asientos =====

let editor_pisos_actuales = []; // Arreglo con datos temporales de cada piso

function iniciar_editor_vehiculo() {
    if (!vehiculo_seleccionado_micros) {
        mostrar_aviso("Seleccione un vehículo primero");
        return;
    }

    // Ocultar área estática y mostrar editor
    $("#area_croquis_estatico_micros").classList.add("hidden");
    $("#panel_edicion_vehiculo_micros").classList.remove("hidden");

    // Cargar número de pisos
    const configuracion = vehiculo_seleccionado_micros.configuracion;
    let numPisos = 1;
    if (configuracion && Array.isArray(configuracion.pisos) && configuracion.pisos.length > 0) {
        numPisos = configuracion.pisos.length;
    }
    $("#editor_num_pisos").value = String(numPisos);

    // Inicializar editor_pisos_actuales
    editor_pisos_actuales = [];
    if (configuracion && configuracion.pisos) {
        for (let i = 0; i < configuracion.pisos.length; i++) {
            editor_pisos_actuales.push({
                filas: configuracion.pisos[i].filas,
                columnas: configuracion.pisos[i].columnas,
                asientos: configuracion.pisos[i].asientos.map(a => ({
                    fila: parseInt(a.fila),
                    columna: parseInt(a.columna),
                    numero: a.numero
                }))
            });
        }
    } else {
        // Si no hay configuración previa, crear un piso por defecto 12x5 sin asientos
        editor_pisos_actuales.push({ filas: 12, columnas: 5, asientos: [] });
    }

    generar_editor_pisos();
    actualizar_foto_vehiculo(vehiculo_seleccionado_micros.foto, true);
}

function generar_editor_pisos() {
    const select = $("#editor_num_pisos");
    if (!select) return;
    const numPisos = Math.max(1, parseInt(select.value, 10) || 1);

    // Ajustar editor_pisos_actuales al número de pisos
    while (editor_pisos_actuales.length < numPisos) {
        editor_pisos_actuales.push({ filas: 12, columnas: 5, asientos: [] });
    }
    editor_pisos_actuales.length = numPisos;

    const contenedor = $("#editor_pisos_container");
    contenedor.innerHTML = '';

    editor_pisos_actuales.forEach((piso, idxPiso) => {
        const pisoDiv = document.createElement('div');
        pisoDiv.className = 'editor-piso';
        pisoDiv.innerHTML = `<h4>Piso ${idxPiso + 1}</h4>
            <div class="form-grid" style="margin-bottom:8px;">
                <div class="field"><label>Filas</label><input type="number" class="input-filas" value="${piso.filas}" min="1"></div>
                <div class="field"><label>Columnas</label><input type="number" class="input-columnas" value="${piso.columnas}" min="1"></div>
            </div>
            <div class="cuadricula-editor" data-piso="${idxPiso}"></div>`;

        contenedor.appendChild(pisoDiv);

        const inputFilas = pisoDiv.querySelector('.input-filas');
        const inputColumnas = pisoDiv.querySelector('.input-columnas');
        inputFilas.addEventListener('change', () => {
            piso.filas = parseInt(inputFilas.value, 10) || 1;
            renderizar_cuadricula_editor(pisoDiv, idxPiso);
        });
        inputColumnas.addEventListener('change', () => {
            piso.columnas = parseInt(inputColumnas.value, 10) || 1;
            renderizar_cuadricula_editor(pisoDiv, idxPiso);
        });

        renderizar_cuadricula_editor(pisoDiv, idxPiso);
    });
}

function renderizar_cuadricula_editor(contenedorPiso, idxPiso) {
    const piso = editor_pisos_actuales[idxPiso];
    const cuadricula = contenedorPiso.querySelector('.cuadricula-editor');
    cuadricula.innerHTML = '';

    for (let f = 1; f <= piso.filas; f++) {
        const filaDiv = document.createElement('div');
        filaDiv.className = 'seat-row';
        for (let c = 1; c <= piso.columnas; c++) {
            const celda = document.createElement('div');
            celda.className = 'celda-editor';
            celda.dataset.fila = f;
            celda.dataset.columna = c;
            celda.dataset.piso = idxPiso;

            // Buscar si existe asiento en esa posición
            const asiento = piso.asientos.find(a => a.fila === f && a.columna === c);
            if (asiento) {
                celda.classList.add('asiento');
                celda.textContent = asiento.numero;
            } else {
                celda.classList.add('vacio');
            }

            celda.addEventListener('click', () => alternar_asiento(celda, idxPiso, f, c));
            celda.addEventListener('dblclick', () => editar_numero_asiento(celda, idxPiso, f, c));

            filaDiv.appendChild(celda);
        }
        cuadricula.appendChild(filaDiv);
    }
}

function alternar_asiento(celda, idxPiso, fila, columna) {
    const piso = editor_pisos_actuales[idxPiso];
    const idx = piso.asientos.findIndex(a => a.fila === fila && a.columna === columna);
    if (idx >= 0) {
        // Quitar asiento
        piso.asientos.splice(idx, 1);
        celda.classList.remove('asiento');
        celda.classList.add('vacio');
        celda.textContent = '';
    } else {
        // Agregar asiento con número provisional (podría ser vacío o autogenerado)
        const nuevoNumero = generar_numero_provisional(piso);
        piso.asientos.push({ fila, columna, numero: nuevoNumero });
        celda.classList.add('asiento');
        celda.classList.remove('vacio');
        celda.textContent = nuevoNumero;
    }
}

function editar_numero_asiento(celda, idxPiso, fila, columna) {
    const piso = editor_pisos_actuales[idxPiso];
    const asiento = piso.asientos.find(a => a.fila === fila && a.columna === columna);
    if (!asiento) return;

    const nuevoNumero = prompt("Número de asiento:", asiento.numero);
    if (nuevoNumero !== null && nuevoNumero.trim() !== '') {
        asiento.numero = nuevoNumero.trim();
        celda.textContent = nuevoNumero.trim();
    }
}

function generar_numero_provisional(piso) {
    // Buscar el mayor número actual y sumar 1
    let max = 0;
    piso.asientos.forEach(a => {
        const num = parseInt(a.numero);
        if (!isNaN(num) && num > max) max = num;
    });
    return String(max + 1);
}

/*function numerar_automaticamente() {
    let contador = 0;
    editor_pisos_actuales.forEach(piso => {
        // Ordenar asientos por fila, columna
        piso.asientos.sort((a, b) => a.fila - b.fila || a.columna - b.columna);
        piso.asientos.forEach(a => {
            contador++;
            a.numero = String(contador);
        });
    });
    // Redibujar las cuadrículas
    document.querySelectorAll('.cuadricula-editor').forEach((cuadricula, idx) => {
        renderizar_cuadricula_editor(cuadricula.parentElement, idx);
    });
}*/

async function guardar_configuracion() {
    // Recopilar configuración
    const numPisos = parseInt($("#editor_num_pisos").value);
    const configuracion = {
        pisos: []
    };
    for (let i = 0; i < numPisos; i++) {
        const piso = editor_pisos_actuales[i];
        configuracion.pisos.push({
            filas: piso.filas,
            columnas: piso.columnas,
            asientos: piso.asientos.map(a => ({
                fila: String(a.fila),
                columna: String(a.columna),
                numero: String(a.numero)
            }))
        });
    }

    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = vehiculo_seleccionado_micros.nombre_vehiculo;

    const datos = {
        accion: "vehiculos/actualizar_configuracion",
        nombre_empresa,
        nombre_vehiculo,
        configuracion: JSON.stringify(configuracion)
    };

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        // Actualizar los datos locales del vehículo seleccionado
        if (vehiculo_seleccionado_micros) {
            vehiculo_seleccionado_micros.configuracion = configuracion;
            // Calcular total de asientos
            const total_asientos = configuracion.pisos.reduce((total, piso) => total + piso.asientos.length, 0);
            vehiculo_seleccionado_micros.asientos = String(total_asientos);
        }

        // Salir del editor y mostrar el croquis actualizado
        cancelar_editor();
        mostrar_aviso("Configuración actualizada");
    } else {
        mostrar_aviso(resultado.error || "Error al guardar configuración");
    }
}

function cancelar_editor() {
    $("#panel_edicion_vehiculo_micros").classList.add("hidden");
    $("#area_croquis_estatico_micros").classList.remove("hidden");
    // Mostrar croquis actual (sin cambios)
    if (vehiculo_seleccionado_micros) {
        mostrar_croquis_vehiculo(vehiculo_seleccionado_micros);
    }
}


// Eventos
$("#boton_modificar_vehiculo_micros").addEventListener("click", iniciar_editor_vehiculo);
$("#editor_num_pisos").addEventListener("change", generar_editor_pisos);
//$("#boton_numerar_automatico").addEventListener("click", numerar_automaticamente);
$("#boton_guardar_configuracion").addEventListener("click", guardar_configuracion);
$("#boton_cancelar_configuracion").addEventListener("click", cancelar_editor);

$("#boton_editar_empresa_micros").addEventListener("click", () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa");
        return;
    }
    // Obtener nombre visible actual
    const nombre_actual = $("#selector_empresa_micros").selectedOptions[0].textContent;
    $("#editar_empresa_nombre").value = nombre_actual;
    $("#formulario_editar_empresa").classList.remove("hidden");
    $("#formulario_nueva_empresa").classList.add("hidden");
});

/*$("#boton_guardar_edicion_empresa").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    const nuevo_nombre = $("#editar_empresa_nombre").value.trim();
    if (!nuevo_nombre) {
        mostrar_aviso("El nombre no puede estar vacío");
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "empresas/editar", nombre_dueno, nombre_empresa, nuevo_nombre })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa actualizada");
        $("#formulario_editar_empresa").classList.add("hidden");
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al editar");
    }
});*/

/*$("#boton_cancelar_edicion_empresa").addEventListener("click", () => {
    $("#formulario_editar_empresa").classList.add("hidden");
});*/

$("#boton_eliminar_empresa_micros").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa");
        return;
    }
    if (!confirm(`¿Eliminar la empresa "${nombre_empresa}" y todos sus vehículos?`)) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "empresas/eliminar", nombre_dueno, nombre_empresa })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa eliminada");
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar");
    }
});
$("#boton_eliminar_vehiculo_micros").addEventListener("click", async () => {
    alert ("hola");
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = $("#selector_vehiculo_micros").value;
    if (!nombre_vehiculo) {
        mostrar_aviso("Seleccione un vehículo");
        return;
    }
    if (!confirm(`¿Eliminar el vehículo "${nombre_vehiculo}"?`)) return;
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "vehiculos/eliminar", nombre_empresa, nombre_vehiculo })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Vehículo eliminado");
        await cargar_vehiculos_de_empresa(nombre_empresa);
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar");
    }
});
// ====== Funciones auxiliares ======
function obtener_nombre_dueno_actual() {
    return usuario_actual.nivel === 'admin' ? $("#selector_dueno_viajes").value : usuario_actual.nombre_usuario;
}

async function cargar_viajes() {
    const panel_dueno = $("#panel_selector_dueno_viajes");
    const boton_agregar = $("#boton_agregar_viaje");
    const lista = $("#lista_viajes");
    lista.innerHTML = '';

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
        mostrar_aviso(datos.error || "Error al cargar viajes");
    }
}

function renderizar_viajes(viajes) {
    const lista = $("#lista_viajes");
    lista.innerHTML = '';
    viajes.forEach(viaje => {
        const div = document.createElement('div');
        div.className = 'viaje-card';
        div.innerHTML = `
            <div class="viaje-info">
                <strong>${viaje.nombre}</strong>
                <span>${viaje.fecha} ${viaje.hora}</span>
                <span>${viaje.origen} → ${viaje.destino}</span>
            </div>
            <div class="viaje-stats">
                <span>Ocupación: ${viaje.ocupacion}</span>
                <span>Disponibles: ${viaje.disponibles}</span>
                <span>Seleccionados: ${viaje.seleccionados}</span>
                <span>Vendidos: ${viaje.vendidos}</span>
            </div>
            <button class="btn btn-detalle-viaje" data-viaje="${viaje.nombre_viaje}">Ver detalle</button>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-viaje" data-viaje="${viaje.nombre_viaje}">Eliminar</button>` : ''}
        `;
        lista.appendChild(div);

        div.querySelector('.btn-detalle-viaje').addEventListener('click', () => ver_detalle_viaje(viaje));
        const btnEliminar = div.querySelector('.btn-eliminar-viaje');
        if (btnEliminar) btnEliminar.addEventListener('click', () => eliminar_viaje(viaje.nombre_viaje));
    });
}

function ver_detalle_viaje(viaje) {
    viaje_seleccionado = viaje;
    $("#detalle_viaje_titulo").textContent = viaje.nombre;
    $("#detalle_viaje").classList.remove('hidden');
    renderizar_micros_viaje(viaje.micros);
    renderizar_terminales_viaje(viaje.terminales_autorizadas);
}

function renderizar_micros_viaje(micros) {
    const contenedor = $("#lista_micros_viaje");
    contenedor.innerHTML = '';
    micros.forEach(micro => {
        const div = document.createElement('div');
        div.className = 'micro-item';
        div.innerHTML = `
            <span>${micro.empresa} - ${micro.patente}</span>
            <span>Ocupación: ${micro.ocupacion}</span>
            <span>Vendidos: ${micro.vendidos}</span>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-micro" data-micro="${micro.nombre_micro}">Quitar</button>` : ''}
        `;
        contenedor.appendChild(div);

        const btnQuitar = div.querySelector('.btn-eliminar-micro');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_micro(micro.nombre_micro));
    });
}

function renderizar_terminales_viaje(terminales) {
    const contenedor = $("#lista_terminales_viaje");
    contenedor.innerHTML = '';
    terminales.forEach(terminal => {
        const div = document.createElement('div');
        div.className = 'terminal-item';
        div.innerHTML = `
            <span>${terminal}</span>
            ${usuario_actual.nivel !== 'terminal' ? `<button class="btn btn-eliminar-terminal" data-terminal="${terminal}">Quitar</button>` : ''}
        `;
        contenedor.appendChild(div);

        const btnQuitar = div.querySelector('.btn-eliminar-terminal');
        if (btnQuitar) btnQuitar.addEventListener('click', () => eliminar_terminal_autorizada(terminal));
    });
}

// Eventos de formularios (agregar viaje, micro, terminal)
$("#boton_agregar_viaje").addEventListener("click", () => {
    $("#formulario_nuevo_viaje").classList.remove("hidden");
});

$("#boton_guardar_viaje").addEventListener("click", async () => {
    const nombre_dueno = obtener_nombre_dueno_actual();
    const datos = {
        accion: "viajes/agregar",
        nombre_dueno,
        nombre_viaje: $("#nuevo_viaje_nombre").value.trim(),
        nombre: $("#nuevo_viaje_nombre").value.trim(),
        fecha: $("#nuevo_viaje_fecha").value,
        hora: $("#nuevo_viaje_hora").value,
        origen: $("#nuevo_viaje_origen").value,
        destino: $("#nuevo_viaje_destino").value
    };
    if (!datos.nombre_viaje) {
        mostrar_aviso("El nombre del viaje es obligatorio");
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Viaje creado");
        $("#formulario_nuevo_viaje").classList.add("hidden");
        await cargar_viajes(); // Recargar lista principal
    } else {
        mostrar_aviso(resultado.error || "Error al crear viaje");
    }
});

$("#boton_cancelar_viaje").addEventListener("click", () => {
    $("#formulario_nuevo_viaje").classList.add("hidden");
});

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
        mostrar_aviso("Viaje eliminado");
        $("#detalle_viaje").classList.add("hidden");
        await cargar_viajes(); // Recargar lista principal
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar");
    }
}

// Agregar micro
$("#boton_agregar_micro_viaje").addEventListener("click", async () => {
    $("#formulario_agregar_micro").classList.remove("hidden");
    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectEmpresa = $("#selector_empresa_micro_viaje");
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
        selectEmpresa.onchange = async () => {
            const selectVehiculo = $("#selector_vehiculo_micro_viaje");
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
    }
});

$("#boton_confirmar_micro").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micro_viaje").value;
    const nombre_vehiculo = $("#selector_vehiculo_micro_viaje").value;
    const nombre_dueno = obtener_nombre_dueno_actual();
    if (!nombre_empresa || !nombre_vehiculo) {
        mostrar_aviso("Seleccione empresa y vehículo");
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
            nombre_dueno
        })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Micro agregado");
        $("#formulario_agregar_micro").classList.add("hidden");
        await actualizar_detalle_viaje_actual(); // Actualizar detalle
    } else {
        mostrar_aviso(resultado.error || "Error al agregar micro");
    }
});

$("#boton_cancelar_micro").addEventListener("click", () => {
    $("#formulario_agregar_micro").classList.add("hidden");
});

async function eliminar_micro(nombre_micro) {
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
        mostrar_aviso("Micro eliminado");
        await actualizar_detalle_viaje_actual(); // Actualizar detalle
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar micro");
    }
}

// Terminales autorizadas
$("#boton_agregar_terminal_viaje").addEventListener("click", async () => {
    $("#formulario_agregar_terminal").classList.remove("hidden");
    const nombre_dueno = obtener_nombre_dueno_actual();
    const selectTerminal = $("#selector_terminal_autorizada");
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
});

$("#boton_confirmar_terminal").addEventListener("click", async () => {
    const nombre_terminal = $("#selector_terminal_autorizada").value;
    const nombre_dueno = obtener_nombre_dueno_actual();
    if (!nombre_terminal) {
        mostrar_aviso("Seleccione un punto de venta");
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
        mostrar_aviso("Punto de venta autorizado");
        $("#formulario_agregar_terminal").classList.add("hidden");
        await actualizar_detalle_viaje_actual(); // Actualizar detalle
    } else {
        mostrar_aviso(resultado.error || "Error al autorizar punto de venta");
    }
});

$("#boton_cancelar_terminal").addEventListener("click", () => {
    $("#formulario_agregar_terminal").classList.add("hidden");
});

async function eliminar_terminal_autorizada(nombre_terminal) {
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
        mostrar_aviso("Punto de venta eliminado");
        await actualizar_detalle_viaje_actual(); // Actualizar detalle
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar punto de venta");
    }
}

// Función para actualizar el detalle del viaje sin recargar la lista completa
async function actualizar_detalle_viaje_actual() {
    if (!viaje_seleccionado) return;

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
        const viajeActualizado = datos.viajes.find(v => v.nombre_viaje === viaje_seleccionado.nombre_viaje);
        if (viajeActualizado) {
            ver_detalle_viaje(viajeActualizado);
        }
    }
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


// Inicialización de la interfaz: ocultar aplicación y mostrar login
$("#aplicacion").classList.add("hidden");
$("#pantalla_login").classList.remove("hidden");

// --- Funciones de construcción de asientos y manejo de ventas/pasajeros (adaptadas) ---

function construir_asientos(contenedor_id, interactivo = false) {
  const contenedor = $("#" + contenedor_id);
  if (!contenedor) return;
  contenedor.innerHTML = "";
  for (let fila = 0; fila < 11; fila++) {
    const fila_div = document.createElement("div");
    fila_div.className = "seat-row";
    for (let columna = 0; columna < 5; columna++) {
      if (columna === 2) {
        const pasillo = document.createElement("div");
        pasillo.className = "aisle";
        fila_div.appendChild(pasillo);
        continue;
      }
      const numero = fila * 4 + (columna < 2 ? columna + 1 : columna - 1);
      const asiento = document.createElement("button");
      asiento.className = "seat";
      asiento.textContent = String(numero).padStart(2, "0");
      asiento.dataset.asiento = numero;
      if (interactivo) {
        if ([4, 11, 22, 31, 42, 7].includes(numero)) asiento.classList.add("sold");
        else if ([9, 18].includes(numero)) asiento.classList.add("selected");
        asiento.addEventListener("click", () => seleccionar_asiento(asiento));
      }
      fila_div.appendChild(asiento);
    }
    contenedor.appendChild(fila_div);
  }
}

function estado_asiento(asiento) {
  if (asiento.classList.contains("sold")) return "VENDIDO";
  if (asiento.classList.contains("selected")) return "SELECCIONADO";
  return "LIBRE";
}

let asiento_actual = null;

function seleccionar_asiento(asiento) {
  asiento_actual = asiento;
  const numero = asiento.dataset.asiento;
  const estado = estado_asiento(asiento);
  let contenido = `<div class="detail-line"><span>Número de asiento:</span><strong>${numero}</strong></div>
                  <div class="detail-line"><span>Estado:</span><strong class="status ${estado.toLowerCase()}">${estado}</strong></div>`;
  if (estado === "LIBRE") {
    contenido += `<div class="actions"><button class="btn primary" id="boton_vender">Vender</button></div>
                 <div id="formulario_venta" class="sale-form hidden">
                   <h3>Nueva venta</h3>
                   <button class="btn" data-placeholder>Agregar otro asiento</button>
                   <div class="form-grid" style="margin-top:12px">
                     <div class="field"><label>Cantidad de cuotas</label><select><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option></select></div>
                     <div class="field"><label>Modo de pago</label><select><option>Efectivo</option><option>Transferencia</option></select></div>
                   </div>
                   <div class="actions"><button class="btn primary" data-placeholder>Confirmar venta</button></div>
                 </div>`;
  } else if (estado === "VENDIDO") {
    contenido += `<div class="actions"><button class="btn" id="boton_ver_venta">Ver venta</button></div>`;
  }
  $("#detalle_asiento").innerHTML = contenido;
  $("#boton_vender")?.addEventListener("click", () => $("#formulario_venta").classList.remove("hidden"));
  $("#boton_ver_venta")?.addEventListener("click", () => mostrar_detalle_venta(numero));
  vincular_placeholders($("#detalle_asiento"));
}

function mostrar_detalle_venta(numero) {
  $("#detalle_asiento").innerHTML = `<div class="detail-box" style="padding:0;border:0">
    <h3>Venta #00012${numero}</h3>
    <div class="detail-line"><span>Número de pasajes:</span><strong>2</strong></div>
    <div class="detail-line"><span>Monto total:</span><strong>$120.000</strong></div>
    <div class="detail-line"><span>Cuotas:</span><strong>3</strong></div>
    <div class="detail-line"><span>Cantidad abonada:</span><strong>$80.000</strong></div>
    <div class="detail-line"><span>Cantidad adeudada:</span><strong>$40.000</strong></div>
    <h3 style="margin-top:15px">Pasajero 1</h3>
    <div class="small">Nombre: Juan Pérez<br>DNI: 12.345.678<br>Email: juan@email.com<br>Celular personal: 2983-555555<br>Celular emergencias: 2983-444444</div>
    <h3 style="margin-top:15px">Pasajero 2</h3>
    <div class="small">Nombre: María López<br>DNI: 23.456.789<br>Email: opcional<br>Celular personal: 2983-666666<br>Celular emergencias: 2983-777777</div>
    <div class="actions"><button class="btn" data-placeholder>Imprimir pasajes</button></div>
  </div>`;
  vincular_placeholders($("#detalle_asiento"));
}

function actualizar_contadores_asientos() {
  const asientos = $$(".seat");
  let vendidos = 0, seleccionados = 0, libres = 0;
  asientos.forEach(asiento => {
    const estado = estado_asiento(asiento);
    if (estado === "VENDIDO") vendidos++;
    else if (estado === "SELECCIONADO") seleccionados++;
    else libres++;
  });
  $("#contador_vendidos").textContent = vendidos;
  $("#contador_seleccionados").textContent = seleccionados;
  $("#contador_disponibles").textContent = libres;
}

// Datos de ejemplo de ventas
const lista_ventas_ejemplo = [
  { id: "000121", asientos: 2, total: "$120.000", cuotas: 3, abonado: "$80.000", deuda: "$40.000", vendedor: "Secretaria" },
  { id: "000122", asientos: 1, total: "$60.000", cuotas: 1, abonado: "$60.000", deuda: "$0", vendedor: "Gaspar" },
  { id: "000123", asientos: 3, total: "$180.000", cuotas: 3, abonado: "$60.000", deuda: "$120.000", vendedor: "Secretaria" },
  { id: "000124", asientos: 2, total: "$120.000", cuotas: 2, abonado: "$120.000", deuda: "$0", vendedor: "Gaspar" }
];

function renderizar_ventas(filtro = "Todos") {
  const lista = $("#lista_ventas");
  if (!lista) return;
  lista.innerHTML = "";
  lista_ventas_ejemplo.filter(venta => filtro === "Todos" || venta.vendedor === filtro).forEach(venta => {
    const tarjeta = document.createElement("div");
    tarjeta.className = "sale-card";
    tarjeta.dataset.vendedor = venta.vendedor;
    tarjeta.innerHTML = `<div class="sale-header"><div class="sale-id">Venta #${venta.id}</div><span class="badge">${venta.vendedor}</span></div>
      <div class="sale-grid">
       <div class="sale-metric"><span>Cantidad de asientos</span><b>${venta.asientos}</b></div>
       <div class="sale-metric"><span>Monto total</span><b>${venta.total}</b></div>
       <div class="sale-metric"><span>Cuotas</span><b>${venta.cuotas}</b></div>
       <div class="sale-metric"><span>Cantidad abonada</span><b>${venta.abonado}</b></div>
       <div class="sale-metric"><span>Cantidad adeudada</span><b>${venta.deuda}</b></div>
      </div>
      <div class="actions"><button class="btn" data-placeholder>Ver más detalles</button><button class="btn danger" data-placeholder>Cancelar venta</button></div>`;
    lista.appendChild(tarjeta);
  });
  vincular_placeholders(lista);
}

// Datos de ejemplo de pasajeros
const lista_pasajeros_ejemplo = [
  { nombre: "Juan Pérez", dni: "12345678", email: "juan@email.com", celular: "2983-555555", emergencia: "2983-444444", asiento: "17" },
  { nombre: "María López", dni: "23456789", email: "", celular: "2983-666666", emergencia: "2983-777777", asiento: "18" },
  { nombre: "Carlos Gómez", dni: "34567890", email: "carlos@email.com", celular: "2983-888888", emergencia: "2983-999999", asiento: "25" },
  { nombre: "Ana Fernández", dni: "45678901", email: "ana@email.com", celular: "2983-111111", emergencia: "2983-222222", asiento: "26" }
];

function renderizar_pasajeros() {
  const tabla = $("#tabla_pasajeros");
  if (!tabla) return;
  tabla.innerHTML = lista_pasajeros_ejemplo.map((pasajero, indice) => `<tr>
   <td>${pasajero.nombre}</td><td>${pasajero.dni}</td><td>${pasajero.email || "—"}</td><td>${pasajero.celular}</td><td>${pasajero.emergencia}</td>
   <td><button class="btn" onclick="mostrar_pasajero(${indice})">Editar</button></td></tr>`).join("");
}

function mostrar_pasajero(indice) {
  const pasajero = lista_pasajeros_ejemplo[indice];
  $("#detalle_pasajero").classList.add("visible");
  $("#pasajero_nombre").value = pasajero.nombre;
  $("#pasajero_dni").value = pasajero.dni;
  $("#pasajero_email").value = pasajero.email;
  $("#pasajero_celular").value = pasajero.celular;
  $("#pasajero_emergencia").value = pasajero.emergencia;
  $("#pasajes_pasajero").innerHTML = `<div class="sale-card"><strong>Leon XIV en Lujan</strong><br><span class="small">Asiento ${pasajero.asiento} · XX/XX/XXXX · <span class="badge paid">Vendido</span></span></div>`;
}

function vincular_placeholders(raiz = document) {
  raiz.querySelectorAll("[data-placeholder]").forEach(boton => {
    if (!boton.dataset.vinculado) {
      boton.dataset.vinculado = "1";
      boton.addEventListener("click", evento => {
        evento.preventDefault();
        mostrar_aviso("Función en desarrollo");
      });
    }
  });
}

// Eventos de pestañas de filtros
$("#filtro_vendedor").addEventListener("change", e => renderizar_ventas(e.target.value));
/*$("#selector_terminal").addEventListener("change", e => {
  const ventas_por_terminal = { Secretaria: 37, Gaspar: 24 };
  $("#ventas_terminal").textContent = ventas_por_terminal[e.target.value] || 0;
});*/