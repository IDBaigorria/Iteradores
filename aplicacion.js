/**
 * Aplicación principal.
 * @version 1.5piloto.2
 */

// Utilidades
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

const DEBUG_FETCH = true; // cambia a false para desactivar
if (DEBUG_FETCH) { // Interceptor de fetch para depuración
    const fetch_original = window.fetch;
    window.fetch = async function (...args) {
        const [url, opciones] = args;
        console.group(`[fetch] ${url}`);
        console.log("Opciones:", opciones);
        try {
            const respuesta = await fetch_original(...args);
            // Clonar la respuesta para no consumir el body original
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
            return respuesta; // devolver la respuesta original intacta
        } catch (error) {
            console.error("Error en fetch:", error);
            console.groupEnd();
            throw error;
        }
    };
}

let usuario_actual = null; // Almacena los datos del usuario autenticado

/**
 * Muestra un mensaje temporal (toast).
 * @param {string} mensaje Texto a mostrar.
 */
function mostrar_aviso(mensaje) {
  const aviso = $("#toast");
  aviso.textContent = mensaje;
  aviso.classList.add("show");
  clearTimeout(window.temporizador_aviso);
  window.temporizador_aviso = setTimeout(() => aviso.classList.remove("show"), 1800);
}

/**
 * Oculta todas las pestañas y muestra solo las permitidas según nivel.
 * @param {string} nivel Nivel del usuario ('admin', 'dueno', 'terminal').
 */
function configurar_pestanas_segun_nivel(nivel) {
  const pestanas_permitidas = {
    admin: ['admin', 'terminales', 'viajes', 'micros', 'vendidos', 'pasajeros'],
    dueno: ['terminales', 'viajes', 'micros', 'vendidos', 'pasajeros'],
    terminal: ['viajes', 'vendidos', 'pasajeros']
  };

  const permitidas = pestanas_permitidas[nivel] || [];
  const nav = $("#pestanas");
  nav.innerHTML = '';

  const nombres_pestanas = {
    admin: 'Administrador',
    terminales: 'Terminales',
    viajes: 'Viajes',
    micros: 'Empresas/Micros',
    vendidos: 'Vendidos',
    pasajeros: 'Pasajeros/Clientes'
  };

  permitidas.forEach(id_pestana => {
    const boton = document.createElement('button');
    boton.className = 'tab';
    boton.dataset.tab = id_pestana;
    boton.textContent = nombres_pestanas[id_pestana];
    boton.addEventListener('click', () => activar_pestana(id_pestana));
    nav.appendChild(boton);
  });

  // Activar la primera pestaña disponible
  if (permitidas.length > 0) {
    activar_pestana(permitidas[0]);
  }
}

/**
 * Activa una pestaña específica.
 * @param {string} id_pestana Identificador de la pestaña.
 */
function activar_pestana(id_pestana) {
  $$(".tab").forEach(boton => boton.classList.remove("active"));
  const boton_activo = document.querySelector(`.tab[data-tab="${id_pestana}"]`);
  if (boton_activo) boton_activo.classList.add("active");

  $$(".tab-content").forEach(seccion => seccion.classList.add("hidden"));
  const seccion_activa = document.getElementById(id_pestana);
  if (seccion_activa) seccion_activa.classList.remove("hidden");

  // Inicializar contenido específico al activar la pestaña

  if (id_pestana === 'viajes' || id_pestana === 'micros') {
    construir_asientos("filas_asientos", true);
    construir_asientos("filas_asientos_estaticas", false);
  }
  if (id_pestana === 'vendidos') {
    renderizar_ventas($("#filtro_vendedor").value);
  }
  if (id_pestana === 'pasajeros') {
    renderizar_pasajeros();
  }
if (id_pestana === 'admin') {
    cargar_datos_admin();
  }
}

/**
 * Maneja el ingreso con código de acceso.
 */
async function ingresar_con_codigo() {
  const codigo = $("#codigo_acceso").value.trim();
  if (!codigo) {
    mostrar_aviso("Ingrese el código");
    return;
  }

  const respuesta = await fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ accion: "autenticar/verificar", codigo })
  });
  const datos = await respuesta.json();

  if (datos.exito) {
    usuario_actual = datos.usuario;
    $("#pantalla_login").classList.add("hidden");
    $("#aplicacion").classList.remove("hidden");
    $("#nombre_usuario_actual").textContent = usuario_actual.nombre_usuario;
    $("#nivel_usuario_actual").textContent = usuario_actual.nivel;
    configurar_pestanas_segun_nivel(usuario_actual.nivel);
  } else {
    mostrar_aviso(datos.error || "Código incorrecto");
  }
}

/**
 * Cierra la sesión actual y vuelve a la pantalla de login.
 */
async function salir() {
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
  usuario_actual = null;
  $("#aplicacion").classList.add("hidden");
  $("#pantalla_login").classList.remove("hidden");
  $("#codigo_acceso").value = "";
}

// Asignar eventos iniciales
$("#boton_ingresar").addEventListener("click", ingresar_con_codigo);
$("#codigo_acceso").addEventListener("keypress", (e) => { if (e.key === "Enter") ingresar_con_codigo(); });
$("#boton_salir").addEventListener("click", salir);



/**
 * Carga los datos de usuarios y sesiones en el panel de administración.
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
        <td>${usuario.nivel}</td>
        <td>${usuario.codigo_acceso || "—"}</td>
        <td>${usuario.efectivo}</td>
        <td>${usuario.banco.nombre || "—"}</td>
        <td>${usuario.banco.cuenta || "—"}</td>
      `;
      cuerpo_tabla.appendChild(fila);
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
      fila.innerHTML = `
        <td>${sesion.token}</td>
        <td>${sesion.usuario}</td>
        <td>${sesion.creado_en}</td>
      `;
      cuerpo_tabla.appendChild(fila);
    });
  }
}

// Manejo del formulario de nuevo usuario
$("#nuevo_nivel").addEventListener("change", function() {
  const nivel = this.value;
  document.getElementById("campo_dueno").style.display = (nivel === "terminal") ? "block" : "none";
});

$("#boton_agregar_usuario").addEventListener("click", () => {
  $("#formulario_nuevo_usuario").classList.remove("hidden");
});

$("#boton_cancelar_nuevo_usuario").addEventListener("click", () => {
  $("#formulario_nuevo_usuario").classList.add("hidden");
});

$("#boton_guardar_usuario").addEventListener("click", async () => {
  const datos_usuario = {
    accion: "administrador/agregar_usuario",
    nombre_usuario: $("#nuevo_nombre_usuario").value.trim(),
    contrasena: $("#nuevo_contrasena").value,
    nombre_real: $("#nuevo_nombre_real").value.trim(),
    codigo_acceso: $("#nuevo_codigo_acceso").value.trim(),
    nivel: $("#nuevo_nivel").value,
    dueno: $("#nuevo_dueno").value.trim(),
    efectivo: $("#nuevo_efectivo").value.trim() || "0",
    banco_nombre: $("#nuevo_banco_nombre").value.trim(),
    banco_cuenta: $("#nuevo_banco_cuenta").value.trim()
  };
  if (!datos_usuario.nombre_usuario || !datos_usuario.contrasena || !datos_usuario.codigo_acceso) {
    mostrar_aviso("Nombre de usuario, contraseña y código de acceso son obligatorios");
    return;
  }
  if (datos_usuario.nivel === "terminal" && !datos_usuario.dueno) {
    mostrar_aviso("Debe especificar el dueño para una terminal");
    return;
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
    // Limpiar campos
    ["nuevo_nombre_usuario", "nuevo_contrasena", "nuevo_nombre_real", "nuevo_codigo_acceso", "nuevo_dueno", "nuevo_efectivo", "nuevo_banco_nombre", "nuevo_banco_cuenta"].forEach(id => $("#" + id).value = "");
    cargar_datos_admin();
  } else {
    mostrar_aviso(datos.error || "Error al agregar usuario");
  }
});

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
$("#selector_terminal").addEventListener("change", e => {
  const ventas_por_terminal = { Secretaria: 37, Gaspar: 24 };
  $("#ventas_terminal").textContent = ventas_por_terminal[e.target.value] || 0;
});

// Construir asientos iniciales (se reconstruyen al activar pestañas)
// No se llaman aquí para evitar errores si el DOM está oculto.