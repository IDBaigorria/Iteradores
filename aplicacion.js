/***
 * Aplicación principal.
 * Contiene utilidades, estado global, autenticación y manejo de pestañas.
 * @version 1.5piloto.73i
 */

// Utilidades
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

/**
 * Devuelve true si el usuario actual es admin o soporte.
 * Se usa en lugar de comparar directamente con "admin", porque
 * el soporte tiene los mismos permisos sobre sus dueños asignados.
 */
function es_admin_o_soporte() {
    if (!usuario_actual) return false;
    return usuario_actual.nivel === 'admin' || usuario_actual.nivel === 'soporte';
}

// Interceptor de fetch: agrega nombre_solicitante a toda petición POST
// a index.php cuando hay usuario logueado.
const _fetch_original = window.fetch.bind(window);
window.fetch = function(url, opciones) {
    try {
        if (typeof url === 'string' && url.indexOf('index.php') !== -1 && opciones && opciones.method === 'POST') {
            if (typeof usuario_actual !== 'undefined' && usuario_actual && usuario_actual.nombre_usuario) {
                if (opciones.body instanceof URLSearchParams) {
                    opciones.body.set('nombre_solicitante', usuario_actual.nombre_usuario);
                } else if (opciones.body instanceof FormData) {
                    opciones.body.set('nombre_solicitante', usuario_actual.nombre_usuario);
                }
            }
        }
    } catch (e) { console.error('error agregando nombre_solicitante', e); }
    return _fetch_original(url, opciones);
};

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
        soporte: ['admin', 'micros', 'viajes', 'vendidos', 'pasajeros', 'rendiciones', 'liquidaciones'],
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
            _aplicar_login_exitoso(datos.usuario);
            mostrar_aviso("Bienvenido", 'exito');
        } else {
            mostrar_aviso(datos.error || "Código incorrecto", 'error');
        }
    } catch (error) {
        console.error("Error en autenticación:", error);
        mostrar_aviso("Error de comunicación", 'error');
    }
}

/**
 * Ingresa con usuario y contraseña.
 */
async function ingresar_con_usuario() {
    const usuario_input = $("#login_usuario").value.trim();
    const contrasena_input = $("#login_contrasena").value;
    if (!usuario_input || !contrasena_input) {
        mostrar_aviso("Ingrese usuario y contraseña", 'error');
        return;
    }
    try {
        const respuesta = await fetch("index.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                accion: "autenticar/verificar",
                usuario: usuario_input,
                contrasena: contrasena_input
            })
        });
        const datos = await respuesta.json();
        if (datos.exito) {
            _aplicar_login_exitoso(datos.usuario);
            mostrar_aviso("Bienvenido", 'exito');
        } else {
            mostrar_aviso(datos.error || "Usuario o contraseña incorrectos", 'error');
        }
    } catch (error) {
        console.error("Error en autenticación:", error);
        mostrar_aviso("Error de comunicación", 'error');
    }
}

/**
 * Aplica un login exitoso: guarda estado, muestra la app y configura pestañas.
 * Se usa tanto en login por código como por usuario.
 */
function _aplicar_login_exitoso(usuario) {
    // Limpieza defensiva: si quedó contenido del usuario anterior
    // (por ejemplo, al cambiar de sesión sin recargar la página),
    // se borra antes de configurar la nueva UI.
    _limpiar_contenido_dinamico();

    usuario_actual = usuario;
    localStorage.setItem('token_sesion', usuario.token_sesion);
    localStorage.setItem('usuario_actual', JSON.stringify(usuario));
    $("#pantalla_login").classList.add("hidden");
    $("#aplicacion").classList.remove("hidden");
    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;
    $("#nivel_usuario_actual").textContent = usuario.nivel;
    configurar_pestanas_segun_nivel(usuario.nivel);
}

/**
 * Muestra el bloque de login por usuario y oculta el de código.
 */
function mostrar_login_por_usuario() {
    $("#login_por_codigo").classList.add("hidden");
    $("#login_por_usuario").classList.remove("hidden");
    $("#login_usuario").focus();
}

/**
 * Muestra el bloque de login por código y oculta el de usuario.
 */
function mostrar_login_por_codigo() {
    $("#login_por_usuario").classList.add("hidden");
    $("#login_por_codigo").classList.remove("hidden");
    $("#codigo_acceso").focus();
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

    // Borrar todo el contenido dinámico para no exponer datos del
    // usuario que se acaba de ir.
    _limpiar_contenido_dinamico();

    $("#aplicacion").classList.add("hidden");
    $("#pantalla_login").classList.remove("hidden");
    $("#codigo_acceso").value = "";
    $("#login_usuario").value = "";
    $("#login_contrasena").value = "";
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
$("#boton_ingresar_usuario").addEventListener("click", ingresar_con_usuario);
$("#login_contrasena").addEventListener("keypress", (e) => {
    if (e.key === "Enter") ingresar_con_usuario();
});
$("#link_login_usuario").addEventListener("click", (e) => {
    e.preventDefault();
    mostrar_login_por_usuario();
});
$("#link_login_codigo").addEventListener("click", (e) => {
    e.preventDefault();
    mostrar_login_por_codigo();
});
$("#boton_salir").addEventListener("click", salir);

// Click en el nombre de usuario: abre el modal "Mis datos".
$("#nombre_usuario_actual")?.addEventListener("click", abrir_modal_mi_perfil);

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

// ============================================================
// ====== LIMPIEZA DE CONTENIDO DINAMICO ======================
// ============================================================

/**
 * Limpia todo el contenido dinámico (tablas, listas, selectores)
 * que se llenó en la sesión anterior. Se llama al salir y al
 * ingresar, para no exponer datos de un rol a otro cuando cambia
 * el usuario logueado.
 *
 * No toca el HTML estático (headers de secciones, botones, etc.),
 * solo los contenedores que se llenan desde el backend.
 */
function _limpiar_contenido_dinamico() {
    const ids_a_limpiar = [
        // Administrador
        'tabla_usuarios_admin',
        'tabla_sesiones_admin',
        'selector_dueno_admin',
        // Puntos de venta
        'tabla_terminales_dueno',
        'tabla_sesiones_terminales',
        // Empresas / Micros
        'selector_dueno_micros',
        'selector_empresa_micros',
        'selector_vehiculo_micros',
        'lista_micros_viaje',
        'lista_terminales_viaje',
        // Viajes
        'selector_dueno_viajes',
        'lista_viajes',
        // Vendidos
        'selector_dueno_vendidos',
        'lista_ventas',
        'saldos_vendidos',
        'chips_filtros_activos_vendidos',
        'contador_ventas_vendidos',
        'selector_viaje_vendido',
        'filtro_vendedor',
        // Rendiciones
        'selector_dueno_rendiciones',
        'rendicion_filtro_terminal',
        'tabla_rendiciones_container',
        'resumen_rendiciones',
        'chips_filtros_activos_rendiciones',
        'aviso_rendiciones_desactualizadas',
        // Liquidaciones
        'selector_dueno_liquidaciones',
        'tabla_liquidaciones_container',
        'resumen_liquidaciones',
        'chips_filtros_activos_liquidaciones',
        // Pasajeros
        'selector_dueno_pasajeros',
        'tabla_pasajeros',
    ];
    ids_a_limpiar.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });

    // Los selectores cuyo primer option es estático también se
    // resetean a un estado neutro para que no queden con datos.
    const selectores_con_default = {
        'selector_dueno_vendidos': '<option value="">Seleccione dueño...</option>',
        'selector_dueno_rendiciones': '<option value="">Seleccione dueño...</option>',
        'selector_dueno_liquidaciones': '<option value="">Seleccione dueño...</option>',
        'rendicion_filtro_terminal': '<option value="">Todas</option>',
        'selector_viaje_vendido': '<option value="todos">Todos</option>',
        'filtro_vendedor': '<option value="Todos">Todos</option>',
    };
    Object.keys(selectores_con_default).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = selectores_con_default[id];
    });
}

// ============================================================
// Tabs compactas al scrollear.
//
// El header y las tabs viven dentro de .header-wrapper, que es
// el que hace el sticky. Ya no hace falta medir alturas ni
// sincronizar variables CSS entre el header y las tabs.
// .scrolled en el body: lo usa el CSS para achicar las tabs.
// ============================================================

function aplicar_estado_scroll() {
    if (window.scrollY > 40) {
        document.body.classList.add('scrolled');
    } else {
        document.body.classList.remove('scrolled');
    }
}

window.addEventListener('scroll', aplicar_estado_scroll, { passive: true });
document.addEventListener('DOMContentLoaded', aplicar_estado_scroll);
aplicar_estado_scroll();

// ============================================================
// ====== MODAL GENERICO DE EDICION DE USUARIO ================
// ============================================================

/**
 * Abre el modal para editar un usuario. Lo usan tanto el admin
 * (para usuarios de cualquier nivel) como el dueño (para sus
 * propias terminales).
 *
 * @param {string} nombre_usuario
 * @param {object} opciones
 *   - obtener_datos: async (nombre) => usuario | null
 *   - accion_guardar: string, accion POST
 *   - titulo: string (opcional)
 *   - mostrar_nivel: bool
 *   - nivel_forzado: string (si mostrar_nivel es false)
 *   - niveles_disponibles: array de {valor, etiqueta} (si mostrar_nivel es true)
 *   - mostrar_banco: bool
 *   - mostrar_dueno: bool
 *   - mostrar_duenos_soporte: bool
 *   - listar_duenos: async () => array (opcional, si se necesita)
 *   - datos_extra: objeto de campos fijos (opcional)
 *   - al_guardar_exito: function (opcional)
 */
async function abrir_modal_editar_usuario_generico(nombre_usuario, opciones) {
    const usuario = await opciones.obtener_datos(nombre_usuario);
    if (!usuario) {
        mostrar_aviso('Usuario no encontrado', 'error');
        return;
    }

    // Listar dueños si hace falta (para el select de dueño o los checkboxes).
    let duenos = [];
    if ((opciones.mostrar_dueno || opciones.mostrar_duenos_soporte) && typeof opciones.listar_duenos === 'function') {
        try {
            duenos = await opciones.listar_duenos();
        } catch (e) {
            console.error('Error al cargar dueños', e);
        }
    }

    const valor_nombre_real = usuario.nombre_real || '';
    const valor_email = usuario.email || '';
    const valor_nivel = usuario.nivel || (opciones.nivel_forzado || '');
    const valor_banco_nombre = usuario.banco?.nombre || '';
    const valor_banco_cuenta = usuario.banco?.cuenta || '';
    const valor_dueno = usuario.dueno || '';

    // Bloque "Nivel".
    let html_nivel = '';
    if (opciones.mostrar_nivel) {
        const opciones_nivel = (opciones.niveles_disponibles || []).map(n => {
            const sel = (n.valor === valor_nivel) ? ' selected' : '';
            const dis = (valor_nivel === 'soporte') ? ' disabled' : '';
            return `<option value="${n.valor}"${sel}>${n.etiqueta}</option>`;
        }).join('');
        const atributo_disabled = (valor_nivel === 'soporte') ? ' disabled' : '';
        html_nivel = `
            <div class="field">
                <label>Nivel</label>
                <select id="modal_editar_nivel"${atributo_disabled}>
                    ${opciones_nivel}
                </select>
            </div>
        `;
    }

    // Bloque "Dueño" (select, solo para terminal del admin).
    let html_dueno = '';
    if (opciones.mostrar_dueno) {
        let opciones_dueno = '<option value="">Seleccione dueño...</option>';
        duenos.forEach(d => {
            const sel = (d.nombre_usuario === valor_dueno) ? ' selected' : '';
            const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;
            opciones_dueno += `<option value="${d.nombre_usuario}"${sel}>${texto}</option>`;
        });
        html_dueno = `
            <div class="field" id="modal_campo_dueno" style="display:none">
                <label>Dueño (para terminales)</label>
                <select id="modal_editar_dueno">${opciones_dueno}</select>
            </div>
        `;
    }

    // Bloque "Banco" (nombre y cuenta).
    let html_banco = '';
    if (opciones.mostrar_banco) {
        html_banco = `
            <div class="field" id="modal_campo_banco_nombre" style="display:none">
                <label>Banco (nombre)</label>
                <input type="text" id="modal_editar_banco_nombre" value="${valor_banco_nombre}">
            </div>
            <div class="field" id="modal_campo_banco_cuenta" style="display:none">
                <label>Cuenta bancaria</label>
                <input type="text" id="modal_editar_banco_cuenta" value="${valor_banco_cuenta}">
            </div>
        `;
    }

    // Bloque "Dueños asignados" (checkboxes, solo para soporte del admin).
    let html_duenos_soporte = '';
    if (opciones.mostrar_duenos_soporte) {
        const asignados = usuario.duenos || [];
        let checkboxes_html = '';
        if (duenos.length === 0) {
            checkboxes_html = '<em>Sin dueños disponibles</em>';
        } else {
            duenos.forEach(d => {
                const marcado = (asignados.indexOf(d.nombre_usuario) !== -1) ? 'checked' : '';
                const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;
                checkboxes_html += `<label style="display:block;"><input type="checkbox" class="chk_modal_editar_dueno_soporte" value="${d.nombre_usuario}" ${marcado}> ${texto}</label>`;
            });
        }
        html_duenos_soporte = `
            <div class="field full" id="modal_campo_duenos_soporte" style="display:none">
                <label>Dueños asignados</label>
                <div id="modal_editar_duenos_soporte_lista" style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:6px; border-radius:4px;">
                    ${checkboxes_html}
                </div>
            </div>
        `;
    }

    const titulo = opciones.titulo || ('Editar usuario: ' + nombre_usuario);

    const html = `
        <div class="form-grid">
            <div class="field">
                <label>Nombre de usuario</label>
                <input type="text" value="${nombre_usuario}" disabled>
            </div>
            ${html_nivel}
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
            ${html_dueno}
            ${html_banco}
            ${html_duenos_soporte}
        </div>
        <div class="actions" style="margin-top:15px">
            <button class="btn primary" id="modal_btn_guardar_edicion">Guardar</button>
            <button class="btn" id="modal_btn_cancelar_edicion">Cancelar</button>
        </div>
    `;

    abrir_modal_generico(titulo, html);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    const select_nivel = contenedor.querySelector('#modal_editar_nivel');
    const campo_dueno = contenedor.querySelector('#modal_campo_dueno');
    const campo_banco_nombre = contenedor.querySelector('#modal_campo_banco_nombre');
    const campo_banco_cuenta = contenedor.querySelector('#modal_campo_banco_cuenta');
    const campo_duenos_soporte = contenedor.querySelector('#modal_campo_duenos_soporte');

    function _nivel_actual() {
        if (opciones.mostrar_nivel && select_nivel) return select_nivel.value;
        return opciones.nivel_forzado || '';
    }

    function actualizar_visibilidad() {
        const nivel = _nivel_actual();
        const es_terminal = nivel === 'terminal';
        const es_dueno = nivel === 'dueno';
        const es_soporte = nivel === 'soporte';
        const tiene_banco = es_terminal || es_dueno;
        if (campo_banco_nombre) campo_banco_nombre.style.display = tiene_banco ? '' : 'none';
        if (campo_banco_cuenta) campo_banco_cuenta.style.display = tiene_banco ? '' : 'none';
        if (campo_dueno) campo_dueno.style.display = es_terminal ? '' : 'none';
        if (campo_duenos_soporte) campo_duenos_soporte.style.display = es_soporte ? '' : 'none';
    }
    if (select_nivel) select_nivel.addEventListener('change', actualizar_visibilidad);
    actualizar_visibilidad();

    contenedor.querySelector('#modal_btn_cancelar_edicion').addEventListener('click', cerrar_modal_generico);
    contenedor.querySelector('#modal_btn_guardar_edicion').addEventListener('click', () => {
        _guardar_edicion_usuario_generico(nombre_usuario, contenedor, opciones);
    });
}

/**
 * Guarda los cambios de un usuario editado desde el modal genérico.
 */
async function _guardar_edicion_usuario_generico(nombre_usuario, contenedor, opciones) {
    const nivel = opciones.mostrar_nivel
        ? contenedor.querySelector('#modal_editar_nivel').value
        : (opciones.nivel_forzado || '');
    const select_dueno = contenedor.querySelector('#modal_editar_dueno');
    const dueno = select_dueno ? select_dueno.value : '';

    let duenos_asignados = [];
    if (opciones.mostrar_duenos_soporte && nivel === 'soporte') {
        contenedor.querySelectorAll('.chk_modal_editar_dueno_soporte:checked').forEach(chk => {
            duenos_asignados.push(chk.value);
        });
    }

    const datos = {
        accion: opciones.accion_guardar,
        nombre_usuario: nombre_usuario,
        nombre_real: (contenedor.querySelector('#modal_editar_nombre_real')?.value || '').trim(),
        email: (contenedor.querySelector('#modal_editar_email')?.value || '').trim(),
        nivel: nivel,
        codigo_acceso: (contenedor.querySelector('#modal_editar_codigo')?.value || '').trim(),
        contrasena: ''
    };

    if (opciones.mostrar_banco) {
        datos.banco_nombre = (contenedor.querySelector('#modal_editar_banco_nombre')?.value || '').trim();
        datos.banco_cuenta = (contenedor.querySelector('#modal_editar_banco_cuenta')?.value || '').trim();
    }

    if (opciones.mostrar_dueno) {
        datos.dueno = (nivel === 'terminal') ? dueno : '';
    }

    if (opciones.mostrar_duenos_soporte) {
        datos.duenos_asignados = (nivel === 'soporte') ? JSON.stringify(duenos_asignados) : '';
    }

    // Campos extra del llamador (por ejemplo, nombre_dueno).
    if (opciones.datos_extra) {
        Object.assign(datos, opciones.datos_extra);
    }

    // Validaciones locales.
    if (nivel === 'terminal') {
        if (opciones.mostrar_dueno && !datos.dueno) {
            mostrar_aviso('Debe seleccionar un dueño', 'error');
            return;
        }
        if (opciones.mostrar_banco && (!datos.banco_nombre || !datos.banco_cuenta)) {
            mostrar_aviso('Banco y cuenta son obligatorios para terminales', 'error');
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
        if (typeof opciones.al_guardar_exito === 'function') {
            opciones.al_guardar_exito();
        }
    } else {
        mostrar_aviso(resultado.error || "Error al actualizar", 'error');
    }
}

// ============================================================
// ====== MODAL GENERICO DE ALTA DE USUARIO ===================
// ============================================================

/**
 * Abre el modal para crear un usuario. Lo usan tanto el admin
 * (para usuarios de cualquier nivel) como el dueño (para sus
 * propias terminales) y el soporte (solo terminales).
 *
 * Acepta las mismas opciones que abrir_modal_editar_usuario_generico
 * salvo obtener_datos (no hay usuario previo).
 *
 * @param {object} opciones
 *   - accion_guardar: string, accion POST
 *   - titulo: string (opcional)
 *   - mostrar_nivel: bool
 *   - nivel_forzado: string (si mostrar_nivel es false)
 *   - niveles_disponibles: array de {valor, etiqueta}
 *   - mostrar_banco: bool
 *   - mostrar_dueno: bool
 *   - mostrar_duenos_soporte: bool
 *   - listar_duenos: async () => array (opcional)
 *   - datos_extra: objeto de campos fijos (opcional)
 *   - al_guardar_exito: function (opcional)
 */
async function abrir_modal_agregar_usuario_generico(opciones) {
    // Listar dueños si hace falta.
    let duenos = [];
    if ((opciones.mostrar_dueno || opciones.mostrar_duenos_soporte) && typeof opciones.listar_duenos === 'function') {
        try {
            duenos = await opciones.listar_duenos();
        } catch (e) {
            console.error('Error al cargar dueños', e);
        }
    }

    const valor_nivel = opciones.nivel_forzado || (opciones.mostrar_nivel && opciones.niveles_disponibles && opciones.niveles_disponibles[0]
        ? opciones.niveles_disponibles[0].valor
        : 'dueno');

    // Bloque "Nivel".
    let html_nivel = '';
    if (opciones.mostrar_nivel) {
        const opciones_nivel = (opciones.niveles_disponibles || []).map(n => {
            const sel = (n.valor === valor_nivel) ? ' selected' : '';
            return `<option value="${n.valor}"${sel}>${n.etiqueta}</option>`;
        }).join('');
        html_nivel = `
            <div class="field">
                <label>Nivel</label>
                <select id="modal_agregar_nivel">
                    ${opciones_nivel}
                </select>
            </div>
        `;
    }

    // Bloque "Dueño" (select, solo para terminal del admin o soporte).
    let html_dueno = '';
    if (opciones.mostrar_dueno) {
        let opciones_dueno = '<option value="">Seleccione dueño...</option>';
        duenos.forEach(d => {
            const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;
            opciones_dueno += `<option value="${d.nombre_usuario}">${texto}</option>`;
        });
        html_dueno = `
            <div class="field" id="modal_agregar_campo_dueno" style="display:none">
                <label>Dueño (para terminales) *</label>
                <select id="modal_agregar_dueno">${opciones_dueno}</select>
            </div>
        `;
    }

    // Bloque "Banco" (nombre y cuenta).
    let html_banco = '';
    if (opciones.mostrar_banco) {
        html_banco = `
            <div class="field" id="modal_agregar_campo_banco_nombre" style="display:none">
                <label>Banco (nombre) *</label>
                <input type="text" id="modal_agregar_banco_nombre" value="">
            </div>
            <div class="field" id="modal_agregar_campo_banco_cuenta" style="display:none">
                <label>Cuenta bancaria *</label>
                <input type="text" id="modal_agregar_banco_cuenta" value="">
            </div>
        `;
    }

    // Bloque "Dueños asignados" (checkboxes, solo para soporte).
    let html_duenos_soporte = '';
    if (opciones.mostrar_duenos_soporte) {
        let checkboxes_html = '';
        if (duenos.length === 0) {
            checkboxes_html = '<em>Sin dueños disponibles</em>';
        } else {
            duenos.forEach(d => {
                const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;
                checkboxes_html += `<label style="display:block;"><input type="checkbox" class="chk_modal_agregar_dueno_soporte" value="${d.nombre_usuario}"> ${texto}</label>`;
            });
        }
        html_duenos_soporte = `
            <div class="field full" id="modal_agregar_campo_duenos_soporte" style="display:none">
                <label>Dueños asignados</label>
                <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:6px; border-radius:4px;">
                    ${checkboxes_html}
                </div>
            </div>
        `;
    }

    const titulo = opciones.titulo || 'Nuevo usuario';

    const html = `
        <div class="form-grid">
            <div class="field">
                <label>Nombre de usuario *</label>
                <input type="text" id="modal_agregar_nombre_usuario" value="">
            </div>
            ${html_nivel}
            <div class="field">
                <label>Contraseña (opcional)</label>
                <input type="password" id="modal_agregar_contrasena" value="">
            </div>
            <div class="field">
                <label>Nombre real</label>
                <input type="text" id="modal_agregar_nombre_real" value="">
            </div>
            <div class="field">
                <label>Email (opcional)</label>
                <input type="email" id="modal_agregar_email" value="">
            </div>
            <div class="field">
                <label>Código de acceso *</label>
                <input type="text" id="modal_agregar_codigo" value="">
            </div>
            ${html_dueno}
            ${html_banco}
            ${html_duenos_soporte}
        </div>
        <div class="actions" style="margin-top:15px">
            <button class="btn primary" id="modal_btn_guardar_alta">Guardar</button>
            <button class="btn" id="modal_btn_cancelar_alta">Cancelar</button>
        </div>
    `;

    abrir_modal_generico(titulo, html);

    const contenedor = document.getElementById('modal_generico_contenido');
    if (!contenedor) return;

    const select_nivel = contenedor.querySelector('#modal_agregar_nivel');
    const campo_dueno = contenedor.querySelector('#modal_agregar_campo_dueno');
    const campo_banco_nombre = contenedor.querySelector('#modal_agregar_campo_banco_nombre');
    const campo_banco_cuenta = contenedor.querySelector('#modal_agregar_campo_banco_cuenta');
    const campo_duenos_soporte = contenedor.querySelector('#modal_agregar_campo_duenos_soporte');

    function _nivel_actual() {
        if (opciones.mostrar_nivel && select_nivel) return select_nivel.value;
        return opciones.nivel_forzado || '';
    }

    function actualizar_visibilidad() {
        const nivel = _nivel_actual();
        const es_terminal = nivel === 'terminal';
        const es_dueno = nivel === 'dueno';
        const es_soporte = nivel === 'soporte';
        const tiene_banco = es_terminal || es_dueno;
        if (campo_banco_nombre) campo_banco_nombre.style.display = tiene_banco ? '' : 'none';
        if (campo_banco_cuenta) campo_banco_cuenta.style.display = tiene_banco ? '' : 'none';
        if (campo_dueno) campo_dueno.style.display = es_terminal ? '' : 'none';
        if (campo_duenos_soporte) campo_duenos_soporte.style.display = es_soporte ? '' : 'none';
    }
    if (select_nivel) select_nivel.addEventListener('change', actualizar_visibilidad);
    actualizar_visibilidad();

    contenedor.querySelector('#modal_btn_cancelar_alta').addEventListener('click', cerrar_modal_generico);
    contenedor.querySelector('#modal_btn_guardar_alta').addEventListener('click', () => {
        _guardar_alta_usuario_generico(contenedor, opciones);
    });
}

/**
 * Guarda un usuario creado desde el modal genérico.
 */
async function _guardar_alta_usuario_generico(contenedor, opciones) {
    const nivel = opciones.mostrar_nivel
        ? contenedor.querySelector('#modal_agregar_nivel').value
        : (opciones.nivel_forzado || '');
    const select_dueno = contenedor.querySelector('#modal_agregar_dueno');
    const dueno = select_dueno ? select_dueno.value : '';

    let duenos_asignados = [];
    if (opciones.mostrar_duenos_soporte && nivel === 'soporte') {
        contenedor.querySelectorAll('.chk_modal_agregar_dueno_soporte:checked').forEach(chk => {
            duenos_asignados.push(chk.value);
        });
    }

    const datos = {
        accion: opciones.accion_guardar,
        nombre_usuario: (contenedor.querySelector('#modal_agregar_nombre_usuario')?.value || '').trim(),
        contrasena: (contenedor.querySelector('#modal_agregar_contrasena')?.value || ''),
        nombre_real: (contenedor.querySelector('#modal_agregar_nombre_real')?.value || '').trim(),
        email: (contenedor.querySelector('#modal_agregar_email')?.value || '').trim(),
        codigo_acceso: (contenedor.querySelector('#modal_agregar_codigo')?.value || '').trim(),
        nivel: nivel
    };

    if (opciones.mostrar_banco) {
        datos.banco_nombre = (contenedor.querySelector('#modal_agregar_banco_nombre')?.value || '').trim();
        datos.banco_cuenta = (contenedor.querySelector('#modal_agregar_banco_cuenta')?.value || '').trim();
    }

    if (opciones.mostrar_dueno) {
        datos.dueno = (nivel === 'terminal') ? dueno : '';
    }

    if (opciones.mostrar_duenos_soporte) {
        datos.duenos_asignados = (nivel === 'soporte') ? JSON.stringify(duenos_asignados) : '';
    }

    if (opciones.datos_extra) {
        Object.assign(datos, opciones.datos_extra);
    }

    // Validaciones locales.
    if (!datos.nombre_usuario) {
        mostrar_aviso('El nombre de usuario es obligatorio', 'error');
        return;
    }
    if (!datos.codigo_acceso && !datos.contrasena) {
        mostrar_aviso('Debe asignar al menos un código de acceso o una contraseña', 'error');
        return;
    }
    if (nivel === 'terminal') {
        if (opciones.mostrar_dueno && !datos.dueno) {
            mostrar_aviso('Debe seleccionar un dueño', 'error');
            return;
        }
        if (opciones.mostrar_banco && (!datos.banco_nombre || !datos.banco_cuenta)) {
            mostrar_aviso('Banco y cuenta son obligatorios para terminales', 'error');
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
            alert("Código de acceso: " + resultado.codigo_asignado + "\n\nGuardalo, no se mostrará de nuevo.");
        }
        mostrar_aviso("Usuario creado correctamente", 'exito');
        cerrar_modal_generico();
        if (typeof opciones.al_guardar_exito === 'function') {
            opciones.al_guardar_exito();
        }
    } else {
        mostrar_aviso(resultado.error || "Error al crear usuario", 'error');
    }
}

// ============================================================
// ====== MODAL "MIS DATOS" ===================================
// ============================================================

/**
 * Abre el modal con los datos del usuario actual. Lo puede usar
 * cualquier rol. Solo lectura.
 */
function abrir_modal_mi_perfil() {
    fetch("index.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ accion: "usuarios/mi_perfil" })
    })
    .then(r => r.json())
    .then(datos => {
        if (!datos.exito) {
            mostrar_aviso(datos.error || "No se pudo cargar el perfil", 'error');
            return;
        }
        _renderizar_modal_mi_perfil(datos.perfil);
    })
    .catch(e => {
        console.error("Error al cargar mi perfil:", e);
        mostrar_aviso("Error de comunicación", 'error');
    });
}

/**
 * Arma y muestra el HTML del modal "Mis datos" a partir del
 * perfil recibido del backend.
 *
 * @param {object} p Datos del perfil.
 */
function _renderizar_modal_mi_perfil(p) {
    const filas = [];
    filas.push(['Usuario', p.nombre_usuario || '—']);
    filas.push(['Nivel', p.nivel || '—']);
    if (p.nombre_real) filas.push(['Nombre real', p.nombre_real]);
    if (p.email) filas.push(['Email', p.email]);
    filas.push(['Código de acceso', p.codigo_asignado ? 'Asignado' : '—']);
    filas.push(['Efectivo', '$' + (p.efectivo || '0')]);
    filas.push(['Bancarizado', '$' + (p.bancarizado || '0')]);
    if (p.banco && p.banco.nombre) filas.push(['Banco', p.banco.nombre]);
    if (p.banco && p.banco.cuenta) filas.push(['Cuenta bancaria', p.banco.cuenta]);
    if (p.nivel === 'terminal' && p.dueno) filas.push(['Dueño', p.dueno]);
    if (p.nivel === 'soporte' && Array.isArray(p.duenos)) {
        filas.push(['Dueños asignados', p.duenos.length > 0 ? p.duenos.join(', ') : '—']);
    }

    const filas_html = filas.map(f => `<tr><th>${f[0]}</th><td>${f[1]}</td></tr>`).join('');

    const html = `
        <table class="perfil-tabla">
            <tbody>
                ${filas_html}
            </tbody>
        </table>
        <div class="actions" style="margin-top:15px">
            <button class="btn" id="perfil_cerrar_btn">Cerrar</button>
        </div>
    `;

    abrir_modal_generico('Mis datos', html);

    document.getElementById('perfil_cerrar_btn')?.addEventListener('click', cerrar_modal_generico);
}

