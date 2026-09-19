/***
 * Aplicación principal.
 * Contiene utilidades, estado global, autenticación y manejo de pestañas.
 * @version 1.5piloto.54b
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

// Variables globales
let usuario_actual = null;
let vehiculo_seleccionado_micros = null;
let viaje_seleccionado = null;
let micro_seleccionado = null;
let intervaloSyncAsientos = null;
let microSyncActual = null;
let estados_asientos_actuales = [];
let venta_form_abierto = false;
let tipo_aviso_actual = 'info';
let operacion_asiento_en_curso = false;
//let ultima_venta_id = null;

// Función de avisos con tipos visuales
function mostrar_aviso(mensaje, tipo = 'info') {
    if (tipo_aviso_actual === 'error' && tipo !== 'error') {
        return;
    }
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

// Configuración de pestañas según nivel de usuario
function configurar_pestanas_segun_nivel(nivel) {
    const pestanas_permitidas = {
        admin: ['admin', 'micros', 'viajes', 'vendidos', 'pasajeros', 'rendiciones', 'liquidaciones'],
        dueno: ['terminales', 'micros', 'viajes', 'vendidos', 'pasajeros', 'rendiciones', 'liquidaciones'],
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
        rendiciones: 'Rendiciones',
        liquidaciones: 'Liquidaciones',
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

// Activación de pestañas
/**
 * Activa la pestaña indicada y devuelve una promesa que se resuelve
 * cuando terminó la carga de datos asociada a esa pestaña (si la hay).
 *
 * Los llamadores que no esperan la promesa siguen funcionando igual.
 * Los que sí esperan (por ejemplo, ir_a_venta_en_vendidos) pueden
 * confiar en que el DOM ya está actualizado al resolver.
 */
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

    if (id_pestana === 'micros') return cargar_datos_micros();
    if (id_pestana === 'viajes') return cargar_viajes();
    if (id_pestana === 'vendidos') return cargar_ventas();
    if (id_pestana === 'rendiciones') return cargar_rendiciones();
    if (id_pestana === 'liquidaciones') return cargar_liquidaciones();
    if (id_pestana === 'pasajeros') return cargar_pasajeros();
    if (id_pestana === 'admin') return cargar_datos_admin();
    if (id_pestana === 'terminales') return cargar_datos_terminales();
    return Promise.resolve();
}

// Autenticación
async function ingresar_con_codigo() {
    const codigo = $("#codigo_acceso").value.trim();
    if (!codigo) {
        mostrar_aviso("Ingrese el código", 'error');
        return;
    }
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
        } catch (e) {
            console.error("Error al cerrar sesión", e);
        }
    }
    localStorage.removeItem('token_sesion');
    localStorage.removeItem('usuario_actual');
    usuario_actual = null;
    $("#aplicacion").classList.add("hidden");
    $("#pantalla_login").classList.remove("hidden");
    $("#codigo_acceso").value = "";
}

// Inicialización al cargar la página
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

// Eventos de login
$("#boton_ingresar").addEventListener("click", ingresar_con_codigo);
$("#codigo_acceso").addEventListener("keypress", (e) => {
    if (e.key === "Enter") ingresar_con_codigo();
});
$("#boton_salir").addEventListener("click", salir);

// Ocultar aplicación y mostrar login al inicio
$("#aplicacion").classList.add("hidden");
$("#pantalla_login").classList.remove("hidden");

// ============================================================
// ====== FUNCIONES GLOBALES PARA MODALES =====================
// ============================================================

/**
 * Callback actual para el botón "Volver" del modal genérico.
 * Si es null, el botón se oculta. Solo hay un nivel de callback
 * (no hay pila) porque en la app los flujos son de a 2 niveles
 * (modal raíz → sub-modal). Si en el futuro hace falta más
 * profundidad, se puede convertir en pila sin romper la API.
 */
let on_volver_modal = null;

/**
 * Abre el modal genérico con un contenido.
 *
 * @param {string} titulo Título del modal.
 * @param {string} contenido_html HTML del cuerpo.
 * @param {Function|null} on_volver Callback opcional. Si se pasa, se
 *   muestra el botón "Volver" en el header y al pulsarlo se ejecuta
 *   este callback. Si no se pasa, el botón se oculta.
 */
function abrir_modal_generico(titulo, contenido_html, on_volver = null) {
    const tituloEl = document.getElementById('modal_generico_titulo');
    const contenidoEl = document.getElementById('modal_generico_contenido');
    const modalEl = document.getElementById('modal_generico');
    const botonVolver = document.getElementById('volver_modal_generico');

    if (!tituloEl || !contenidoEl || !modalEl) return;

    // Resetear el ancho del modal, por si un flujo anterior lo modificó
    // (ej. el modal de liquidación lo achica). Cada apertura arranca
    // con el ancho por defecto del CSS.
    const contentReset = modalEl.querySelector('.modal-content');
    if (contentReset) {
        contentReset.style.maxWidth = '';
        contentReset.style.width = '';
    }

    on_volver_modal = (typeof on_volver === 'function') ? on_volver : null;

    tituloEl.textContent = titulo;
    contenidoEl.innerHTML = contenido_html;
    modalEl.classList.remove('hidden');
    modalEl.style.display = 'flex'; // Asegura que se muestre centrado

    if (botonVolver) {
        if (on_volver_modal) {
            botonVolver.classList.remove('hidden');
        } else {
            botonVolver.classList.add('hidden');
        }
    }
}

/**
 * Ejecuta el callback de "Volver" si existe. NO cierra el modal:
 * el callback es responsable de reabrir el modal anterior (por
 * ejemplo, volviendo a llamar a abrir_modal_generico).
 */
function volver_modal_generico() {
    const cb = on_volver_modal;
    on_volver_modal = null;
    if (typeof cb === 'function') {
        cb();
    }
}

function cerrar_modal_generico() {
    // Hook opcional: algunos flujos quieren ejecutar algo al cerrar el
    // modal genérico (por ejemplo, refrescar la lista de ventas después
    // de la cuponera), sin importar cómo se cierre (X, backdrop o botón).
    if (typeof window.on_cerrar_modal_generico === 'function') {
        const cb = window.on_cerrar_modal_generico;
        window.on_cerrar_modal_generico = null;
        try { cb(); } catch (e) { console.error('on_cerrar_modal_generico:', e); }
    }

    const modalEl = document.getElementById('modal_generico');
    if (modalEl) {
        modalEl.classList.add('hidden');
        modalEl.style.display = 'none';
    }
    const contenidoEl = document.getElementById('modal_generico_contenido');
    if (contenidoEl) contenidoEl.innerHTML = '';

    // Limpiar el callback de volver
    on_volver_modal = null;

    // Limpiar estado de viajes
    if (typeof detener_sync_asientos === 'function') {
        detener_sync_asientos();
    }
    if (typeof venta_form_abierto !== 'undefined') {
        venta_form_abierto = false;
    }
    if (typeof operacion_asiento_en_curso !== 'undefined') {
        operacion_asiento_en_curso = false;
    }
    if (typeof micro_seleccionado !== 'undefined') {
        micro_seleccionado = null;
    }
}

$("#cerrar_modal_generico").addEventListener("click", cerrar_modal_generico);
$("#volver_modal_generico").addEventListener("click", volver_modal_generico);
$("#modal_generico").addEventListener("click", function(e) {
    if (e.target === this) {
        cerrar_modal_generico();
    }
});

// ============================================================
// ====== MODAL APILADO ========================================
// ============================================================

/**
 * Abre un modal apilado encima del modal genérico. El modal grande
 * queda vivo detrás (con su croquis y panel de asiento intactos) y
 * esto permite cerrar el sub-modal sin destruir el estado del de abajo.
 *
 * Sin botón "Volver": el modal grande sigue abierto y visible detrás,
 * no hay nada que "volver a abrir".
 *
 * @param {string} titulo Título del modal apilado.
 * @param {string} contenido_html HTML del cuerpo.
 */
function abrir_modal_apilado(titulo, contenido_html) {
    const tituloEl = document.getElementById('modal_apilado_titulo');
    const contenidoEl = document.getElementById('modal_apilado_contenido');
    const modalEl = document.getElementById('modal_apilado');
    if (!tituloEl || !contenidoEl || !modalEl) return;

    tituloEl.textContent = titulo;
    contenidoEl.innerHTML = contenido_html;
    modalEl.classList.remove('hidden');
    modalEl.style.display = 'flex';
}

/**
 * Cierra el modal apilado y limpia su contenido. No toca el modal
 * genérico ni su estado (micro_seleccionado, polling, etc.).
 */
function cerrar_modal_apilado() {
    const modalEl = document.getElementById('modal_apilado');
    if (modalEl) {
        modalEl.classList.add('hidden');
        modalEl.style.display = 'none';
    }
    const contenidoEl = document.getElementById('modal_apilado_contenido');
    if (contenidoEl) contenidoEl.innerHTML = '';
}

document.getElementById('cerrar_modal_apilado')?.addEventListener('click', cerrar_modal_apilado);
document.getElementById('modal_apilado')?.addEventListener('click', function(e) {
    if (e.target === this) cerrar_modal_apilado();
});

