/***
 * Aplicación principal.
 * @version 1.5piloto.14
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
let vehiculo_seleccionado_micros = null;
let viaje_seleccionado = null;
let micro_seleccionado = null;
let intervaloSyncAsientos = null;
let microSyncActual = null;
let estados_asientos_actuales = [];
let venta_form_abierto = false;
let tipo_aviso_actual = 'info';

function mostrar_aviso(mensaje, tipo = 'info') {
    // Si el aviso actual es de error y el nuevo no es error, no lo reemplaza
    if (tipo_aviso_actual === 'error' && tipo !== 'error') {
        return;
    }
    // Si el nuevo es error, se actualiza siempre
    tipo_aviso_actual = tipo;
    const aviso = $("#toast");
    aviso.textContent = mensaje;
    aviso.className = 'toast show';
    aviso.classList.add(`toast-${tipo}`);
    clearTimeout(window.temporizador_aviso);
    window.temporizador_aviso = setTimeout(() => {
        aviso.classList.remove("show");
        tipo_aviso_actual = 'info';
    }, 3000);
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
    if (id_pestana !== 'viajes') {
        ocultar_detalle_viaje();
    }
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

    if (id_pestana === 'vendidos') cargar_ventas();
    if (id_pestana === 'pasajeros') cargar_pasajeros();
    if (id_pestana === 'admin') cargar_datos_admin();
    if (id_pestana === 'terminales') cargar_datos_terminales();
}

async function ingresar_con_codigo() {
    const codigo = $("#codigo_acceso").value.trim();
    if (!codigo) { mostrar_aviso("Ingrese el código", 'error'); return; }
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
            mostrar_aviso("Bienvenido", 'exito');
        } else {
            mostrar_aviso(datos.error || "Código incorrecto", 'error');
        }
    } catch (error) {
        console.error("Error en autenticación:", error);
        mostrar_aviso("Error de comunicación", 'error');
    }
}

async function salir() {
    ocultar_detalle_viaje();
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
                ocultar_detalle_viaje();
            } else {
                localStorage.removeItem('token_sesion');
                localStorage.removeItem('usuario_actual');
                mostrar_aviso('Sesión expirada, ingrese nuevamente', 'error');
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

// ============================================================
// ====== FUNCIONES ADMINISTRACIÓN (se mantienen originales) ===
// ============================================================

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

// ============================================================
// ====== FUNCIONES PUNTOS DE VENTA (dueño) ===================
// ============================================================

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
        mostrar_aviso("Nombre de usuario y código de acceso son obligatorios", 'error');
        return;
    }
    if (!datos_terminal.banco_nombre || !datos_terminal.banco_cuenta) {
        mostrar_aviso("Banco y cuenta son obligatorios", 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos_terminal)
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        mostrar_aviso("Punto de venta agregado correctamente", 'exito');
        $("#formulario_nueva_terminal").classList.add("hidden");
        // Limpiar campos
        ["nuevo_terminal_nombre_usuario","nuevo_terminal_contrasena","nuevo_terminal_nombre_real","nuevo_terminal_email","nuevo_terminal_codigo_acceso","nuevo_terminal_banco_nombre","nuevo_terminal_banco_cuenta"].forEach(id => $("#"+id).value="");
        cargar_datos_terminales();
    } else {
        mostrar_aviso(datos.error || "Error al agregar punto de venta", 'error');
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
        mostrar_aviso("Banco y cuenta son obligatorios", 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Punto de venta actualizado correctamente", 'exito');
        cargar_datos_terminales();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar", 'error');
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
        mostrar_aviso("Punto de venta eliminado correctamente", 'exito');
        cargar_datos_terminales();
    } else {
        mostrar_aviso(datos.error || "Error al eliminar", 'error');
    }
}

// ============================================================
// ====== FUNCIONES EMPRESAS / MICROS =========================
// ============================================================

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
        mostrar_aviso("Seleccione un dueño", 'error');
        return;
    }
    const datos = {
        accion: "empresas/agregar",
        nombre_dueno,
        nombre_empresa: $("#nueva_empresa_nombre").value.trim(),
        nombre_real: $("#nueva_empresa_nombre_real").value.trim()
    };
    if (!datos.nombre_empresa) {
        mostrar_aviso("Nombre de empresa obligatorio", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa agregada", 'exito');
        $("#formulario_nueva_empresa").classList.add("hidden");
        $("#nueva_empresa_nombre").value = "";
        $("#nueva_empresa_nombre_real").value = "";
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al agregar empresa", 'error');
    }
});

// Botón agregar vehículo
$("#boton_agregar_vehiculo_micros").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.remove("hidden");
});

$("#boton_cancelar_vehiculo").addEventListener("click", () => {
    $("#formulario_nuevo_vehiculo").classList.add("hidden");
});

$("#boton_guardar_vehiculo").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa", 'error');
        return;
    }
    const datos = {
        accion: "vehiculos/agregar",
        nombre_empresa,
        nombre_vehiculo: $("#nuevo_vehiculo_nombre").value.trim(),
        nombre_real: $("#nuevo_vehiculo_nombre_real").value.trim()
    };
    if (!datos.nombre_vehiculo) {
        mostrar_aviso("Patente obligatoria", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Vehículo agregado. Configure los asientos.", 'exito');
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
        mostrar_aviso(resultado.error || "Error al agregar vehículo", 'error');
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
    mostrar_aviso("Asientos reiniciados", 'info');
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
        mostrar_aviso("Seleccione un vehículo primero", 'error');
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
        mostrar_aviso("Foto actualizada", 'exito');
    } else {
        mostrar_aviso(resultado.error || "Error al subir foto", 'error');
    }
});

// ===== Editor de configuración de asientos =====
let editor_pisos_actuales = []; // Arreglo con datos temporales de cada piso

function iniciar_editor_vehiculo() {
    if (!vehiculo_seleccionado_micros) {
        mostrar_aviso("Seleccione un vehículo primero", 'error');
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
        // Agregar asiento con número provisional
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
        mostrar_aviso("Configuración actualizada", 'exito');
    } else {
        mostrar_aviso(resultado.error || "Error al guardar configuración", 'error');
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
$("#boton_guardar_configuracion").addEventListener("click", guardar_configuracion);
$("#boton_cancelar_configuracion").addEventListener("click", cancelar_editor);

$("#boton_editar_empresa_micros").addEventListener("click", () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa", 'error');
        return;
    }
    // Obtener nombre visible actual
    const nombre_actual = $("#selector_empresa_micros").selectedOptions[0].textContent;
    $("#editar_empresa_nombre").value = nombre_actual;
    $("#formulario_editar_empresa").classList.remove("hidden");
    $("#formulario_nueva_empresa").classList.add("hidden");
});

$("#boton_guardar_edicion_empresa").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    const nuevo_nombre = $("#editar_empresa_nombre").value.trim();

    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa", 'error');
        return;
    }
    if (!nuevo_nombre) {
        mostrar_aviso("El nombre no puede estar vacío", 'error');
        return;
    }

    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "empresas/editar", nombre_dueno, nombre_empresa, nuevo_nombre })
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Empresa actualizada", 'exito');
        $("#formulario_editar_empresa").classList.add("hidden");
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al editar", 'error');
    }
});

$("#boton_cancelar_edicion_empresa").addEventListener("click", () => {
    $("#formulario_editar_empresa").classList.add("hidden");
});

$("#boton_eliminar_empresa_micros").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_dueno = usuario_actual.nivel === 'admin' ? $("#selector_dueno_micros").value : usuario_actual.nombre_usuario;
    if (!nombre_empresa) {
        mostrar_aviso("Seleccione una empresa", 'error');
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
        mostrar_aviso("Empresa eliminada", 'exito');
        await cargar_empresas_de_dueno(nombre_dueno);
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
});

$("#boton_eliminar_vehiculo_micros").addEventListener("click", async () => {
    const nombre_empresa = $("#selector_empresa_micros").value;
    const nombre_vehiculo = $("#selector_vehiculo_micros").value;
    if (!nombre_vehiculo) {
        mostrar_aviso("Seleccione un vehículo", 'error');
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
        mostrar_aviso("Vehículo eliminado", 'exito');
        await cargar_vehiculos_de_empresa(nombre_empresa);
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
    }
});

// ============================================================
// ====== FUNCIONES AUXILIARES =================================
// ============================================================
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
    $("#detalle_viaje").classList.add("hidden");
    $("#formulario_agregar_micro").classList.add("hidden");
    $("#formulario_agregar_terminal").classList.add("hidden");
    viaje_seleccionado = null;
    micro_seleccionado = null;
    $("#croquis_pasaje_micro").innerHTML = '';
    $("#foto_micro_viaje").innerHTML = '';
    $("#pasaje_micro_viaje").classList.add("hidden");
    $("#info_asiento_viaje").classList.add("hidden");
    $("#info_asiento_viaje").innerHTML = '';
    detener_sync_asientos();
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
    ocultar_detalle_viaje();

    viaje_seleccionado = viaje;
    $("#detalle_viaje_titulo").textContent = viaje.nombre;
    $("#detalle_viaje").classList.remove('hidden');

    // Restricciones para terminal
    if (usuario_actual.nivel === 'terminal') {
        $("#boton_agregar_micro_viaje").style.display = 'none';
        $("#boton_agregar_terminal_viaje").style.display = 'none';
        $("#seccion_terminales_viaje").style.display = 'none';
    } else {
        $("#boton_agregar_micro_viaje").style.display = '';
        $("#boton_agregar_terminal_viaje").style.display = '';
        $("#seccion_terminales_viaje").style.display = '';
    }

    renderizar_micros_viaje(viaje.micros);
    if (usuario_actual.nivel !== 'terminal') {
        renderizar_terminales_viaje(viaje.terminales_autorizadas);
    } else {
        $("#lista_terminales_viaje").innerHTML = '';
    }
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
            <span>Monto: $${micro.monto ?? '0'}</span>
            <button class="btn btn-ver-pasaje" data-micro="${micro.nombre_micro}">Ver pasaje</button>
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
            actualizar_detalle_viaje_actual();
        } else {
            mostrar_aviso(resultado.error || "Error al actualizar monto", 'error');
        }
    });
}

async function seleccionar_micro_viaje(nombre_micro) {
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
    } else {
        mostrar_aviso(datos.error || "Error al obtener micro", 'error');
    }
}

function obtener_dueno_viaje_seleccionado() {
    if (usuario_actual.nivel === 'terminal' && viaje_seleccionado && viaje_seleccionado.dueno) {
        return viaje_seleccionado.dueno;
    }
    return obtener_nombre_dueno_actual();
}

function iniciar_sync_asientos() {
    detener_sync_asientos();
    if (!viaje_seleccionado || !micro_seleccionado) return;

    microSyncActual = micro_seleccionado; // aseguramos valor
    solicitar_estado_asientos();
    intervaloSyncAsientos = setInterval(solicitar_estado_asientos, 10000);
}

function detener_sync_asientos() {
    if (intervaloSyncAsientos) {
        clearInterval(intervaloSyncAsientos);
        intervaloSyncAsientos = null;
    }
    microSyncActual = null;
}

async function solicitar_estado_asientos() {
    const nombre_micro_actual = microSyncActual || micro_seleccionado; // fallback
    if (!nombre_micro_actual || !viaje_seleccionado) return;

    let nombre_dueno = obtener_dueno_viaje_seleccionado();
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            accion: "viajes/estado_asientos",
            nombre_viaje: viaje_seleccionado.nombre_viaje,
            nombre_micro: nombre_micro_actual,
            nombre_dueno
        })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        estados_asientos_actuales = datos.asientos;
        actualizar_colores_asientos(datos.asientos);
    }
}

function actualizar_colores_asientos(estados) {
    const asientosDOM = document.querySelectorAll('#croquis_pasaje_micro .seat');
    asientosDOM.forEach(seat => {
        const fila = seat.dataset.fila;
        const columna = seat.dataset.columna;
        const estadoObj = estados.find(e => e.fila === fila && e.columna === columna);
        if (estadoObj) {
            seat.classList.remove('seat-libre', 'seat-seleccionado', 'seat-seleccionado-propio', 'seat-vendido', 'seat-no-disponible');
            if (estadoObj.estado === 'seleccionado') {
                if (estadoObj.seleccionado_por === usuario_actual.nombre_usuario) {
                    seat.classList.add('seat-seleccionado-propio');
                } else {
                    seat.classList.add('seat-seleccionado');
                }
            } else {
                seat.classList.add(`seat-${estadoObj.estado}`);
            }
            seat.dataset.estado = estadoObj.estado;
            seat.dataset.seleccionado_por = estadoObj.seleccionado_por || '';
        }
    });
    mostrar_boton_confirmar_venta();
}

async function seleccionar_asiento_pasaje(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return; // usar micro_seleccionado en lugar de microSyncActual

    const nombre_dueno = obtener_dueno_viaje_seleccionado();
    const nombre_terminal = usuario_actual.nombre_usuario;
    const nombre_micro = micro_seleccionado; // guardar localmente

    detener_sync_asientos(); // detener polling para evitar interferencias

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/seleccionar_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno,
                nombre_terminal
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            await solicitar_estado_asientos(); // actualizar estados
            mostrar_aviso("Asiento seleccionado", 'exito');
        } else {
            await solicitar_estado_asientos();
            mostrar_aviso(resultado.error || "No se pudo seleccionar", 'error');
        }
    } catch (error) {
        console.error("Error en selección:", error);
        mostrar_aviso("Error de comunicación", 'error');
    } finally {
        // Reanudar polling solo si seguimos en el mismo micro
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

async function deseleccionar_asiento_pasaje(fila, columna) {
    if (!micro_seleccionado || !viaje_seleccionado) return;

    const nombre_dueno = obtener_dueno_viaje_seleccionado();
    const nombre_terminal = usuario_actual.nombre_usuario;
    const nombre_micro = micro_seleccionado;

    detener_sync_asientos();

    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "viajes/deseleccionar_asiento",
                nombre_viaje: viaje_seleccionado.nombre_viaje,
                nombre_micro: nombre_micro,
                fila,
                columna,
                nombre_dueno,
                nombre_terminal
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            await solicitar_estado_asientos();
            mostrar_aviso("Asiento liberado", 'exito');
        } else {
            await solicitar_estado_asientos();
            mostrar_aviso(resultado.error || "No se pudo liberar", 'error');
        }
    } catch (error) {
        console.error("Error en deselección:", error);
        mostrar_aviso("Error de comunicación", 'error');
    } finally {
        if (viaje_seleccionado && micro_seleccionado) {
            iniciar_sync_asientos();
        }
    }
}

function mostrar_info_asiento(asiento) {
    const panel = $("#info_asiento_viaje");
    let html = `
        <div class="section-title">Información de asiento</div>
        <div class="detail-line"><span>Número:</span><strong>${asiento.numero}</strong></div>
        <div class="detail-line"><span>Fila/Col:</span><strong>${asiento.fila} - ${asiento.columna}</strong></div>
        <div class="detail-line"><span>Estado:</span><strong class="status-${asiento.estado}">${asiento.estado.toUpperCase()}</strong></div>
    `;
    if (asiento.seleccionado_por) {
        html += `<div class="detail-line"><span>Seleccionado por:</span><strong>${asiento.seleccionado_por}</strong></div>`;
    }
    if (asiento.pasajero) {
        html += `<h4 style="margin-top:10px;">Pasajero</h4>`;
        Object.entries(asiento.pasajero).forEach(([campo, valor]) => {
            html += `<div class="detail-line"><span>${campo}:</span><strong>${valor}</strong></div>`;
        });
    }

    if (asiento.venta) {
        html += `<div class="detail-line" style="margin-top:10px;"><span>Venta:</span><strong>Registrada (detalles próximamente)</strong></div>`;
    }

    panel.innerHTML = html;
    panel.classList.remove('hidden');
}

function renderizar_pasaje_micro(micro) {
    const contenedorCroquis = $("#croquis_pasaje_micro");
    const contenedorFoto = $("#foto_micro_viaje");
    contenedorCroquis.innerHTML = '';
    contenedorFoto.innerHTML = '';

    window.micro_actual = micro;

    if (micro.foto) {
        contenedorFoto.innerHTML = `<img src="${micro.foto}" alt="Foto del micro" style="max-width:200px; max-height:200px; border-radius:8px;">`;
    }

    const configuracion = micro.configuracion;
    if (configuracion && configuracion.pisos && configuracion.pisos.length > 0) {
        configuracion.pisos.forEach((piso, index) => {
            const titulo = document.createElement('div');
            titulo.className = 'section-title';
            titulo.textContent = `Piso ${index + 1}`;
            contenedorCroquis.appendChild(titulo);

            const busDiv = document.createElement('div');
            busDiv.className = 'bus';
            busDiv.innerHTML = `<div class="bus-front">FRENTE · CONDUCTOR</div>`;

            for (let fila = 1; fila <= piso.filas; fila++) {
                const filaDiv = document.createElement('div');
                filaDiv.className = 'seat-row';
                for (let col = 1; col <= piso.columnas; col++) {
                    const asiento = piso.asientos.find(a => parseInt(a.fila) === fila && parseInt(a.columna) === col);
                    if (asiento) {
                        const seat = document.createElement('div');
                        seat.className = `seat seat-${asiento.estado}`;
                        seat.textContent = String(asiento.numero).padStart(2, '0');
                        seat.dataset.fila = asiento.fila;
                        seat.dataset.columna = asiento.columna;
                        seat.dataset.numero = asiento.numero;
                        seat.dataset.estado = asiento.estado;
                        filaDiv.appendChild(seat);
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'aisle';
                        filaDiv.appendChild(empty);
                    }
                }
                busDiv.appendChild(filaDiv);
            }
            busDiv.innerHTML += `<div class="bus-back">PARTE TRASERA</div>`;
            contenedorCroquis.appendChild(busDiv);
        });

        // Delegación de eventos
        contenedorCroquis.addEventListener('click', (event) => {
            if (venta_form_abierto) return;
            const seat = event.target.closest('.seat');
            if (!seat) return;
            const fila = seat.dataset.fila;
            const columna = seat.dataset.columna;

            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);
            if (!asiento) return;

            mostrar_info_asiento(asiento);

            if (usuario_actual.nivel === 'terminal') {
                if (asiento.estado === 'libre') {
                    seleccionar_asiento_pasaje(fila, columna);
                } else if (asiento.estado === 'seleccionado' && asiento.seleccionado_por === usuario_actual.nombre_usuario) {
                    deseleccionar_asiento_pasaje(fila, columna);
                }
            }
        });
    } else {
        contenedorCroquis.innerHTML = '<p>No hay configuración de asientos.</p>';
    }

    $("#pasaje_micro_viaje").classList.remove("hidden");

    micro_seleccionado = micro.nombre_micro;
    iniciar_sync_asientos();
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
        mostrar_aviso("El nombre del viaje es obligatorio", 'error');
        return;
    }
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Viaje creado", 'exito');
        $("#formulario_nuevo_viaje").classList.add("hidden");
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al crear viaje", 'error');
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
        mostrar_aviso("Viaje eliminado", 'exito');
        $("#detalle_viaje").classList.add("hidden");
        await cargar_viajes();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar", 'error');
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
    const monto = $("#monto_micro_viaje").value.trim();
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
        $("#formulario_agregar_micro").classList.add("hidden");
        $("#monto_micro_viaje").value = "";
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al agregar micro", 'error');
    }
});

$("#boton_cancelar_micro").addEventListener("click", () => {
    $("#formulario_agregar_micro").classList.add("hidden");
});

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
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar micro", 'error');
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
        $("#formulario_agregar_terminal").classList.add("hidden");
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al autorizar punto de venta", 'error');
    }
});

$("#boton_cancelar_terminal_viaje").addEventListener("click", () => {
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
        mostrar_aviso("Punto de venta eliminado", 'exito');
        await actualizar_detalle_viaje_actual();
    } else {
        mostrar_aviso(resultado.error || "Error al eliminar punto de venta", 'error');
    }
}

// Función para actualizar el detalle del viaje sin recargar la lista completa
async function actualizar_detalle_viaje_actual() {
    if (!viaje_seleccionado) return;

    const nombre_viaje_actual = viaje_seleccionado.nombre_viaje;
    ocultar_detalle_viaje();

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
        }
    }
}

// ============================================================
// ====== NUEVAS FUNCIONES PARA CONFIRMACIÓN DE VENTA ==========
// ============================================================

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

$("#boton_confirmar_venta").addEventListener("click", abrir_modal_confirmacion_venta);
$("#cerrar_modal_venta").addEventListener("click", () => $("#modal_confirmar_venta").classList.add("hidden"));
$("#cancelar_venta_modal").addEventListener("click", () => $("#modal_confirmar_venta").classList.add("hidden"));

function abrir_modal_confirmacion_venta() {
    if (!microSyncActual || !viaje_seleccionado) return;

    const asientos_seleccionados = estados_asientos_actuales.filter(a => a.estado === 'seleccionado' && a.seleccionado_por === usuario_actual.nombre_usuario);
    if (asientos_seleccionados.length === 0) {
        mostrar_aviso("No tiene asientos seleccionados", 'error');
        return;
    }

    const micro = viaje_seleccionado.micros.find(m => m.nombre_micro === microSyncActual);
    const monto = micro ? parseFloat(micro.monto) : 0;
    const total = monto * asientos_seleccionados.length;

    // Ocultar botón Vender y mostrar formulario
    venta_form_abierto = true;
    $("#contenedor_boton_confirmar_venta").classList.add("hidden");
    $("#info_asiento_viaje").classList.add("hidden");
    $("#formulario_confirmacion_venta").classList.remove("hidden");

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
                <select id="metodo_pago">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="field" id="campo_cuotas">
                <label>Cantidad de cuotas (1-3)</label>
                <select id="cuotas_venta">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </div>
            <div class="field" id="campo_pago_inicial">
                <label>Cuotas pagadas ahora</label>
                <select id="pago_inicial">
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </div>
        </div>
        <div id="datos_comprador" style="margin-top:15px;">
            <h4>Comprador</h4>
            <div class="form-grid">
                <div class="field"><label>DNI *</label><input id="comprador_dni"></div>
                <div class="field"><label>Nombre *</label><input id="comprador_nombre"></div>
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

    // Valores por defecto
    $("#metodo_pago").value = 'efectivo';
    $("#cuotas_venta").value = '1';
    $("#pago_inicial").value = '1';
    actualizar_visibilidad_cuotas();

    // Limpiar campos del comprador
    $("#comprador_dni").value = '';
    $("#comprador_nombre").value = '';
    $("#comprador_email").value = '';
    $("#comprador_celular").value = '';
    $("#comprador_emergencia").value = '';
    $("#comprador_fecha_nacimiento").value = '';

    generar_formularios_pasajeros(asientos_seleccionados);

    // Eventos para los nuevos elementos
    $("#metodo_pago").addEventListener("change", actualizar_visibilidad_cuotas);
    $("#cuotas_venta").addEventListener("change", function() {
        const cuotas = parseInt(this.value);
        const selectPago = $("#pago_inicial");
        selectPago.innerHTML = '';
        for (let i = 0; i <= cuotas; i++) {
            const opcion = document.createElement('option');
            opcion.value = i;
            opcion.textContent = i;
            selectPago.appendChild(opcion);
        }
    });
    $("#cuotas_venta").dispatchEvent(new Event('change'));

    // Evento para usar pasajero como comprador
    $("#usar_pasajero_como_comprador").addEventListener("click", () => {
        const primerDni = document.querySelector('.pasajero_dni').value;
        const primerNombre = document.querySelector('.pasajero_nombre').value;
        const primerEmail = document.querySelector('.pasajero_email').value;
        const primerCelular = document.querySelector('.pasajero_celular').value;
        if (primerDni) {
            $("#comprador_dni").value = primerDni;
            $("#comprador_nombre").value = primerNombre;
            $("#comprador_email").value = primerEmail;
            $("#comprador_celular").value = primerCelular;
        } else {
            mostrar_aviso("Complete al menos el DNI del primer pasajero", 'error');
        }
    });

    // Evento para confirmar
    $("#confirmar_venta").addEventListener("click", confirmar_venta_modal);

    // Evento para cancelar
    $("#cancelar_venta_modal").addEventListener("click", () => {
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        mostrar_boton_confirmar_venta();
    });
}



function actualizar_visibilidad_cuotas() {
    const metodo = $("#metodo_pago").value;
    if (metodo === 'transferencia') {
        $("#campo_cuotas").style.display = 'none';
        $("#campo_pago_inicial").style.display = 'none';
        $("#cuotas_venta").value = '1';
        $("#pago_inicial").value = '1';
    } else {
        $("#campo_cuotas").style.display = '';
        $("#campo_pago_inicial").style.display = '';
    }
}

$("#metodo_pago").addEventListener("change", actualizar_visibilidad_cuotas);
$("#cuotas_venta").addEventListener("change", function() {
    const cuotas = parseInt(this.value);
    const selectPago = $("#pago_inicial");
    selectPago.innerHTML = '';
    for (let i = 0; i <= cuotas; i++) {
        const opcion = document.createElement('option');
        opcion.value = i;
        opcion.textContent = i;
        selectPago.appendChild(opcion);
    }
});
$("#cuotas_venta").dispatchEvent(new Event('change'));

function generar_formularios_pasajeros(asientos) {
    const contenedor = $("#pasajeros_venta");
    contenedor.innerHTML = '<h4>Pasajeros por asiento</h4>';
    asientos.forEach((asiento, index) => {
        const div = document.createElement('div');
        div.className = 'panel';
        div.style.marginTop = '10px';
        div.innerHTML = `
            <h5>Asiento ${asiento.numero} (F${asiento.fila}, C${asiento.columna})</h5>
            <div class="form-grid">
                <div class="field"><label>DNI *</label><input class="pasajero_dni" data-index="${index}" value=""></div>
                <div class="field"><label>Nombre *</label><input class="pasajero_nombre" data-index="${index}" value=""></div>
                <div class="field"><label>Email</label><input class="pasajero_email" data-index="${index}" value=""></div>
                <div class="field"><label>Celular *</label><input class="pasajero_celular" data-index="${index}" value=""></div>
                <div class="field"><label>Celular Emergencia *</label><input class="pasajero_emergencia" data-index="${index}" value=""></div>
                <div class="field"><label>Fecha de nacimiento *</label><input type="date" class="pasajero_fecha_nacimiento" data-index="${index}" value=""></div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

$("#usar_pasajero_como_comprador").addEventListener("click", () => {
    const primerDni = document.querySelector('.pasajero_dni').value;
    const primerNombre = document.querySelector('.pasajero_nombre').value;
    const primerEmail = document.querySelector('.pasajero_email').value;
    const primerCelular = document.querySelector('.pasajero_celular').value;
    const primerEmergencia = document.querySelector('.pasajero_emergencia').value;
    if (primerDni) {
        $("#comprador_dni").value = primerDni;
        $("#comprador_nombre").value = primerNombre;
        $("#comprador_email").value = primerEmail;
        $("#comprador_celular").value = primerCelular;
        $("#comprador_emergencia").value = primerEmergencia;
    } else {
        mostrar_aviso("Complete al menos el DNI del primer pasajero", 'error');
    }
});

$("#confirmar_venta").addEventListener("click", confirmar_venta_modal);

async function confirmar_venta_modal() {
    // Obtener datos del comprador
    const comprador_dni = $("#comprador_dni").value.trim();
    const comprador_nombre = $("#comprador_nombre").value.trim();
    const comprador_email = $("#comprador_email").value.trim();
    const comprador_celular = $("#comprador_celular").value.trim();

    // Validar comprador
    if (!comprador_dni || !comprador_nombre || !comprador_celular) {
        mostrar_aviso("Complete DNI, nombre y celular del comprador", 'error');
        return;
    }

    // Obtener método de pago y cuotas
    const metodo_pago = $("#metodo_pago").value;
    const cuotas = parseInt($("#cuotas_venta").value);
    const pago_inicial = parseInt($("#pago_inicial").value);

    // Recopilar pasajeros
    const pasajeros = [];
    const dniInputs = document.querySelectorAll('.pasajero_dni');
    for (let i = 0; i < dniInputs.length; i++) {
        const dni = dniInputs[i].value.trim();
        const nombre = document.querySelector(`.pasajero_nombre[data-index="${i}"]`).value.trim();
        const fecha_nacimiento = document.querySelector(`.pasajero_fecha_nacimiento[data-index="${i}"]`).value.trim();
        const celular = document.querySelector(`.pasajero_celular[data-index="${i}"]`).value.trim();
        const celular_emergencia = document.querySelector(`.pasajero_emergencia[data-index="${i}"]`).value.trim();
        const email = document.querySelector(`.pasajero_email[data-index="${i}"]`).value.trim();

        // Validar obligatorios
        if (!dni || !nombre || !fecha_nacimiento || !celular || !celular_emergencia) {
            mostrar_aviso(`Complete todos los datos obligatorios del pasajero ${i+1}`, 'error');
            return;
        }

        pasajeros.push({
            dni,
            nombre,
            email,
            celular,
            celular_emergencia,
            fecha_nacimiento
        });
    }

    // Crear objeto de datos para enviar
    const datos = {
        accion: "ventas/confirmar",
        nombre_terminal: usuario_actual.nombre_usuario,
        metodo_pago,
        cuotas,
        pago_inicial,
        comprador_dni,
        comprador_nombre,
        comprador_email,
        comprador_celular,
        pasajeros: JSON.stringify(pasajeros)
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
        } else {
            mostrar_aviso(resultado.error || "Error al confirmar venta", 'error');
        }
    } catch (error) {
        console.error("Error:", error);
        mostrar_aviso("Error de comunicación", 'error');
    } finally {
        // Ocultar formulario y limpiar
        $("#formulario_confirmacion_venta").classList.add("hidden");
        $("#info_asiento_viaje").classList.remove("hidden");
        venta_form_abierto = false;
        // Actualizar estados
        await solicitar_estado_asientos();
        // Mostrar botón Vender si corresponde
        mostrar_boton_confirmar_venta();
        // Actualizar detalle del viaje para reflejar contadores
        await actualizar_detalle_viaje_actual();
    }
}
//alert("hola");
// ============================================================
// ====== PANEL VENDIDOS (REEMPLAZO) ===========================
// ============================================================

let ventas_actuales = [];

async function cargar_ventas() {
    if (!usuario_actual) return;

    let tipo, nombre;
    if (usuario_actual.nivel === 'dueno') {
        tipo = 'dueno';
        nombre = usuario_actual.nombre_usuario;
    } else if (usuario_actual.nivel === 'terminal') {
        tipo = 'terminal';
        nombre = usuario_actual.nombre_usuario;
    } else {
        // admin: se necesita seleccionar dueño, por ahora no implementado
        mostrar_aviso("Seleccione un dueño para ver ventas (no implementado)", 'info');
        return;
    }

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
    const vendedoresUnicos = [...new Set(ventas.map(v => v.terminal))];
    vendedoresUnicos.forEach(vendedor => {
        const opcion = document.createElement('option');
        opcion.value = vendedor;
        opcion.textContent = vendedor;
        select.appendChild(opcion);
    });
    select.onchange = () => renderizar_ventas();
}

function renderizar_ventas() {
    const filtroViaje = $("#selector_viaje_vendido").value;
    const filtroVendedor = $("#filtro_vendedor").value;
    let ventas_filtradas = ventas_actuales;
    if (filtroViaje !== 'todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.viaje === filtroViaje);
    }
    if (filtroVendedor !== 'Todos') {
        ventas_filtradas = ventas_filtradas.filter(v => v.terminal === filtroVendedor);
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
        tarjeta.innerHTML = `
            <div class="sale-header"><div class="sale-id">Venta ${venta.id_venta}</div><span class="badge">${venta.terminal}</span></div>
            <div class="sale-grid">
                <div class="sale-metric"><span>Cantidad de asientos</span><b>${venta.cantidad_asientos}</b></div>
                <div class="sale-metric"><span>Monto total</span><b>$${venta.total}</b></div>
                <div class="sale-metric"><span>Fecha</span><b>${venta.fecha}</b></div>
            </div>
            <div class="actions">
                <button class="btn ver_detalle_venta" data-id="${venta.id_venta}">Ver detalle</button>
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
        // Mostrar en un alert simple por ahora
        alert(`Detalle de venta ${venta.id_venta}\nTotal: $${venta.total}\nMétodo: ${venta.metodo_pago}\nCuotas: ${venta.cuotas}\nPagado: $${venta.pagado}\nPasajeros: ${venta.asientos.length}`);
    } else {
        mostrar_aviso(datos.error || "Error al obtener detalle", 'error');
    }
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

// ============================================================
// ====== PANEL PASAJEROS (REEMPLAZO) ==========================
// ============================================================

let pasajeros_actuales = [];
let pasajero_seleccionado_dni = null;

async function cargar_pasajeros() {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/listar" })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        pasajeros_actuales = datos.pasajeros;
        renderizar_tabla_pasajeros(pasajeros_actuales);
    } else {
        mostrar_aviso(datos.error || "Error al cargar pasajeros", 'error');
    }
}

function renderizar_tabla_pasajeros(pasajeros) {
    const tabla = $("#tabla_pasajeros");
    tabla.innerHTML = '';
    pasajeros.forEach(pasajero => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${pasajero.nombre}</td>
            <td>${pasajero.dni}</td>
            <td>${pasajero.email || '—'}</td>
            <td>${pasajero.celular || '—'}</td>
            <td>${pasajero.celular_emergencia || '—'}</td>
            <td><button class="btn editar_pasajero" data-dni="${pasajero.dni}">Editar</button></td>
        `;
        tabla.appendChild(fila);
        fila.querySelector('.editar_pasajero').addEventListener('click', () => cargar_detalle_pasajero(pasajero.dni));
    });
}

$("#buscar_pasajero").addEventListener('input', function() {
    const termino = this.value.trim().toLowerCase();
    if (termino === '') {
        renderizar_tabla_pasajeros(pasajeros_actuales);
    } else {
        const filtrados = pasajeros_actuales.filter(p => 
            p.nombre.toLowerCase().includes(termino) || p.dni.includes(termino)
        );
        renderizar_tabla_pasajeros(filtrados);
    }
});

async function cargar_detalle_pasajero(dni) {
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "pasajeros/obtener", dni })
    });
    const datos = await respuesta.json();
    if (datos.exito) {
        pasajero_seleccionado_dni = dni;
        const p = datos.pasajero;
        $("#pasajero_nombre").value = p.nombre;
        $("#pasajero_dni").value = p.dni;
        $("#pasajero_email").value = p.email || '';
        $("#pasajero_celular").value = p.celular || '';
        $("#pasajero_emergencia").value = p.celular_emergencia || '';
        const contenedor = $("#pasajes_pasajero");
        contenedor.innerHTML = '';
        if (p.ventas && p.ventas.length > 0) {
            p.ventas.forEach(venta => {
                const div = document.createElement('div');
                div.className = 'sale-card';
                div.innerHTML = `<strong>${venta.viaje}</strong><br><span class="small">Venta ${venta.id_venta} · ${venta.fecha} · Total: $${venta.total}</span>`;
                contenedor.appendChild(div);
            });
        } else {
            contenedor.innerHTML = '<p>Sin pasajes registrados</p>';
        }
        $("#detalle_pasajero").classList.remove("hidden");
    } else {
        mostrar_aviso(datos.error || "Pasajero no encontrado", 'error');
    }
}

$("#boton_guardar_pasajero").addEventListener('click', async () => {
    if (!pasajero_seleccionado_dni) {
        mostrar_aviso("Seleccione un pasajero", 'error');
        return;
    }
    const datos = {
        accion: "pasajeros/actualizar",
        dni: pasajero_seleccionado_dni,
        nombre: $("#pasajero_nombre").value.trim(),
        email: $("#pasajero_email").value.trim(),
        celular: $("#pasajero_celular").value.trim(),
        celular_emergencia: $("#pasajero_emergencia").value.trim(),
    };
    const respuesta = await fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(datos)
    });
    const resultado = await respuesta.json();
    if (resultado.exito) {
        mostrar_aviso("Pasajero actualizado", 'exito');
        cargar_pasajeros();
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar", 'error');
    }
});

// ============================================================
// ====== FUNCIONES DE CONSTRUCCIÓN DE ASIENTOS (EXISTENTE) ====
// ============================================================

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

function vincular_placeholders(raiz = document) {
    raiz.querySelectorAll("[data-placeholder]").forEach(boton => {
        if (!boton.dataset.vinculado) {
            boton.dataset.vinculado = "1";
            boton.addEventListener("click", evento => {
                evento.preventDefault();
                mostrar_aviso("Función en desarrollo", 'info');
            });
        }
    });
}

// ============================================================
// ====== INICIALIZACIÓN FINAL =================================
// ============================================================

$("#aplicacion").classList.add("hidden");
$("#pantalla_login").classList.remove("hidden");