/***
 * Aplicación principal.
 * Contiene utilidades, estado global, autenticación y manejo de pestañas.
 * @version 1.5piloto.16
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

// Activación de pestañas
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
    }
    if (id_pestana === 'viajes') {
        cargar_viajes();
    }
    if (id_pestana === 'vendidos') cargar_ventas();
    if (id_pestana === 'pasajeros') cargar_pasajeros();
    if (id_pestana === 'admin') cargar_datos_admin();
    if (id_pestana === 'terminales') cargar_datos_terminales();
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