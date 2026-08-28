<?php
// aplicacion_GET.php
// Este archivo se sirve cuando no es una petición POST con acción.
// Contiene la interfaz HTML completa con JavaScript en español.
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrador de Viajes - Pquia. del Carmen Tres Arroyos</title>
<style>
*{box-sizing:border-box}
:root{
  --bg:#eef1f5;--panel:#fff;--text:#243142;--muted:#687586;--border:#d8dee7;
  --primary:#315d8c;--primary-dark:#24486d;--free:#fff;--selected:#ffd84d;--sold:#e55b5b;
  --shadow:0 2px 8px rgba(30,45,65,.08);--radius:8px;
}
body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;font-size:15px}
button,select,input{font:inherit}
button{cursor:pointer}
.header{background:#fff;border-bottom:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;position:sticky;top:0;z-index:20}
.brand{font-size:20px;font-weight:700;color:#1f3d5b}
.terminal-box{display:flex;align-items:center;gap:8px;font-weight:600}
select,input{border:1px solid #c9d1dc;border-radius:6px;background:#fff;padding:8px 10px;color:var(--text)}
.tabs{background:#fff;border-bottom:1px solid var(--border);padding:0 24px;display:flex;overflow-x:auto}
.tab{border:0;background:none;padding:13px 18px;color:#637083;font-weight:600;border-bottom:3px solid transparent;white-space:nowrap}
.tab:hover{background:#f5f7fa}.tab.active{color:var(--primary);border-bottom-color:var(--primary)}
main{max-width:1400px;margin:0 auto;padding:22px 24px 40px}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;margin-bottom:18px}
h2{margin:0 0 16px;font-size:21px} h3{margin:0 0 12px;font-size:17px}
.muted{color:var(--muted)} .small{font-size:13px}.section-title{font-size:18px;font-weight:700;margin-bottom:14px}
.row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.row label{font-weight:600}
.btn{border:1px solid #b9c4d1;background:#fff;color:#33475d;border-radius:6px;padding:8px 13px;font-weight:600}
.btn:hover{background:#f2f5f8}.btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}.btn.primary:hover{background:var(--primary-dark)}
.btn.danger{border-color:#d77a7a;color:#a33f3f}.btn.warning{border-color:#d9b53b;color:#806500}
.info-grid{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:10px}
.info-card{background:#f7f9fb;border:1px solid var(--border);border-radius:7px;padding:12px}
.info-card strong{display:block;font-size:18px;margin-top:4px}
.placeholder{min-height:105px;background:#f7f9fb;border:1px dashed #b9c4d1;border-radius:7px;display:flex;align-items:center;justify-content:center;color:#7a8795;font-style:italic}
.stats{display:flex;gap:10px;flex-wrap:wrap}.stat{padding:10px 14px;border-radius:7px;background:#f7f9fb;border:1px solid var(--border)}
.stat b{font-size:17px}
.travel-layout{display:grid;grid-template-columns:minmax(530px,1fr) 330px;gap:20px;align-items:start}
.bus-area{display:flex;flex-direction:column;align-items:center}
.bus{width:390px;max-width:100%;background:#f4f6f8;border:3px solid #687686;border-radius:34px 34px 20px 20px;padding:14px 16px 18px;box-shadow:0 3px 10px rgba(0,0,0,.10)}
.bus-front,.bus-back{height:42px;border:1px solid #c4ccd6;border-radius:12px;background:#e8edf2;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#596777;letter-spacing:.5px}
.bus-front{margin-bottom:12px}.bus-back{margin-top:12px}
.seat-row{display:grid;grid-template-columns:1fr 1fr 26px 1fr 1fr;gap:7px;margin:6px 0}
.aisle{grid-column:3}
.seat{height:39px;border:2px solid #8793a0;border-radius:7px;background:#fff;color:#283746;font-weight:700;font-size:13px;box-shadow:inset 0 -2px 0 rgba(0,0,0,.06);transition:.12s}
.seat:hover{transform:translateY(-1px);border-color:#315d8c}
.seat.selected{background:var(--selected);border-color:#b29400}
.seat.sold{background:var(--sold);border-color:#a63f3f;color:#fff}
.legend{display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin-top:12px}
.legend-item{display:flex;align-items:center;gap:6px;font-size:13px}
.swatch{width:18px;height:18px;border:1px solid #9ca7b3;border-radius:4px;display:inline-block}.sw-free{background:#fff}.sw-selected{background:var(--selected)}.sw-sold{background:var(--sold)}
.side-panel{position:sticky;top:125px}
.detail-box{border:1px solid var(--border);border-radius:8px;padding:16px;background:#fff}
.detail-line{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #edf0f3}.detail-line:last-child{border-bottom:0}
.status{font-weight:700}.status.free{color:#4d687d}.status.selected{color:#8a7100}.status.sold{color:#b23d3d}
.sale-form{margin-top:15px;padding-top:15px;border-top:1px solid var(--border)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.field{display:flex;flex-direction:column;gap:5px}.field label{font-size:13px;font-weight:700;color:#586778}
.field.full{grid-column:1/-1}
.hidden{display:none!important}
.table-wrap{overflow-x:auto}.data-table{width:100%;border-collapse:collapse;min-width:750px}.data-table th,.data-table td{padding:10px;border-bottom:1px solid var(--border);text-align:left}.data-table th{background:#f5f7fa;font-size:13px}
.sales-list{display:grid;gap:12px}.sale-card{border:1px solid var(--border);border-radius:8px;padding:15px;background:#fff}
.sale-header{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}.sale-id{font-weight:700;font-size:17px}
.sale-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}.sale-metric{background:#f7f9fb;border:1px solid var(--border);padding:9px;border-radius:6px}.sale-metric span{display:block;color:var(--muted);font-size:12px}.sale-metric b{display:block;margin-top:3px}
.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:13px}
.customer-layout{display:grid;grid-template-columns:1fr 350px;gap:18px}.customer-detail{display:none}.customer-detail.visible{display:block}
.badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:12px;font-weight:700;background:#edf1f5}
.badge.paid{background:#e7f4ea;color:#28713b}.badge.pending{background:#fff2d7;color:#8a6500}
.toast{position:fixed;right:20px;bottom:20px;background:#273746;color:#fff;padding:12px 16px;border-radius:7px;box-shadow:0 5px 20px rgba(0,0,0,.2);display:none;z-index:50}
.toast.show{display:block}
.footer-note{text-align:center;color:#7a8795;font-size:12px;margin-top:25px}
@media(max-width:950px){.travel-layout,.customer-layout{grid-template-columns:1fr}.side-panel{position:static}.sale-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:650px){.header{align-items:flex-start;flex-direction:column}.tabs{padding:0 8px}main{padding:14px}.brand{font-size:17px}.info-grid{grid-template-columns:1fr 1fr}.bus{width:350px}.form-grid{grid-template-columns:1fr}.sale-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<header class="header">
  <div class="brand">Administrador de Viajes, Pquia. del Carmen Tres Arroyos</div>
  <div class="terminal-box"><span>Terminal:</span>
    <select id="terminal_encabezado">
      <option>Gaspar</option><option>Secretaria</option><option>Administrador</option>
    </select>
  </div>
</header>

<nav class="tabs" id="pestanas">
  <button class="tab" data-tab="admin">Administrador</button>
  <button class="tab active" data-tab="terminales">Terminales</button>
  <button class="tab" data-tab="viajes">Viajes</button>
  <button class="tab" data-tab="micros">Empresas/Micros</button>
  <button class="tab" data-tab="vendidos">Vendidos</button>
  <button class="tab" data-tab="pasajeros">Pasajeros/Clientes</button>
</nav>

<main>
  <section id="admin" class="tab-content hidden">
    <!-- Formulario de acceso -->
    <div id="acceso_admin" class="panel">
      <h2>Administrador de Viajes</h2>
      <div class="row">
        <label for="codigo_admin">Ingrese código de acceso:</label>
        <input type="password" id="codigo_admin" style="width:200px">
        <button class="btn primary" id="boton_ingresar_admin">Ingresar</button>
      </div>
    </div>

    <!-- Panel de gestión (oculto inicialmente) -->
    <div id="panel_admin" class="hidden">
      <div class="panel">
        <h2>Panel de Administración</h2>
        <button class="btn primary" id="boton_agregar_usuario">Agregar usuario</button>
      </div>

      <!-- Formulario para nuevo usuario (oculto) -->
      <div id="formulario_nuevo_usuario" class="panel hidden">
        <h3>Nuevo usuario</h3>
        <div class="form-grid">
          <div class="field"><label>Nombre de usuario</label><input id="nuevo_nombre_usuario"></div>
          <div class="field"><label>Contraseña</label><input type="password" id="nuevo_contrasena"></div>
          <div class="field"><label>Nombre real</label><input id="nuevo_nombre_real"></div>
          <div class="field"><label>Nivel</label>
            <select id="nuevo_nivel">
              <option value="usuario">usuario</option>
              <option value="admin">admin</option>
              <option value="vendedor">vendedor</option>
            </select>
          </div>
          <div class="field"><label>Efectivo inicial</label><input id="nuevo_efectivo" value="0"></div>
          <div class="field"><label>Banco (nombre)</label><input id="nuevo_banco_nombre"></div>
          <div class="field"><label>Cuenta bancaria</label><input id="nuevo_banco_cuenta"></div>
        </div>
        <div class="actions" style="margin-top:12px">
          <button class="btn primary" id="boton_guardar_usuario">Guardar</button>
          <button class="btn" id="boton_cancelar_nuevo_usuario">Cancelar</button>
        </div>
      </div>

      <!-- Lista de usuarios -->
      <div class="panel">
        <h3>Usuarios registrados</h3>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Usuario</th><th>Nombre real</th><th>Nivel</th><th>Efectivo</th><th>Banco</th><th>Cuenta</th></tr></thead>
            <tbody id="tabla_usuarios_admin"></tbody>
          </table>
        </div>
      </div>

      <!-- Lista de sesiones -->
      <div class="panel">
        <h3>Sesiones activas</h3>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Token</th><th>Usuario</th><th>Creado</th></tr></thead>
            <tbody id="tabla_sesiones_admin"></tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <section id="terminales" class="tab-content">
    <div class="panel">
      <h2>Terminales</h2>
      <div class="row">
        <label for="selector_terminal">Terminal:</label>
        <select id="selector_terminal"><option>Secretaria</option><option>Gaspar</option></select>
        <button class="btn" data-placeholder>Agregar terminal</button>
      </div>
    </div>
    <div class="panel">
      <div class="section-title">Resumen de la terminal</div>
      <div class="info-card" style="max-width:330px">
        <span class="muted">Cantidad de pasajes vendidos:</span>
        <strong id="ventas_terminal">37</strong>
      </div>
    </div>
    <div class="panel">
      <div class="placeholder">Acá pueden ir más datos</div>
    </div>
    <div class="panel"><button class="btn" data-placeholder>Imprimir</button></div>
  </section>

  <section id="viajes" class="tab-content hidden">
    <div class="panel">
      <h2>Viajes</h2>
      <div class="row">
        <label for="selector_viaje">Viaje:</label>
        <select id="selector_viaje"><option>Leon XIV en Lujan</option></select>
      </div>
      <div class="info-grid" style="margin-top:16px">
        <div class="info-card"><span class="muted">Fecha</span><strong>XX/XX/XXXX</strong></div>
        <div class="info-card"><span class="muted">Hora de salida</span><strong>XX:XX</strong></div>
        <div class="info-card"><span class="muted">Origen</span><strong>Tres Arroyos</strong></div>
        <div class="info-card"><span class="muted">Destino</span><strong>Luján</strong></div>
      </div>
      <div class="stats" style="margin-top:12px">
        <div class="stat">Ocupación: <b>28 / 44</b></div>
        <div class="stat">Disponibles: <b id="contador_disponibles">12</b></div>
        <div class="stat">Seleccionados: <b id="contador_seleccionados">2</b></div>
        <div class="stat">Vendidos: <b id="contador_vendidos">30</b></div>
      </div>
    </div>

    <div class="panel">
      <div class="row">
        <label for="selector_micro">Micro:</label>
        <select id="selector_micro"><option>Colectivo de 44 asientos</option></select>
        <button class="btn" data-placeholder>Agregar micro</button>
      </div>
    </div>

    <div class="travel-layout">
      <div class="panel bus-area">
        <div class="section-title">Croquis del colectivo</div>
        <div id="croquis" class="bus" aria-label="Croquis del colectivo de 44 asientos">
          <div class="bus-front">FRENTE · CONDUCTOR</div>
          <div id="filas_asientos"></div>
          <div class="bus-back">PARTE TRASERA</div>
        </div>
        <div class="legend">
          <div class="legend-item"><span class="swatch sw-free"></span> Libre</div>
          <div class="legend-item"><span class="swatch sw-selected"></span> Seleccionado</div>
          <div class="legend-item"><span class="swatch sw-sold"></span> Vendido</div>
        </div>
        <button class="btn" style="margin-top:14px" data-placeholder>Imprimir todo el pasaje</button>
      </div>

      <aside class="panel side-panel">
        <div class="section-title">Información del asiento</div>
        <div id="detalle_asiento" class="detail-box">
          <div class="muted">Seleccione un asiento del colectivo.</div>
        </div>
      </aside>
    </div>
  </section>

  <section id="micros" class="tab-content hidden">
    <div class="panel">
      <h2>Empresas / Micros</h2>
      <div class="row">
        <label for="selector_empresa">Nombre de empresa:</label>
        <select id="selector_empresa"><option>Empresa de ejemplo</option><option>Otra empresa</option></select>
        <button class="btn" data-placeholder>Agregar empresa</button>
      </div>
    </div>
    <div class="panel">
      <div class="row">
        <label for="selector_vehiculo">Seleccionar vehículo:</label>
        <select id="selector_vehiculo"><option>Colectivo de 44 asientos</option></select>
        <button class="btn" data-placeholder>Agregar vehículo</button>
        <button class="btn primary" data-placeholder>Modificar colectivo</button>
      </div>
    </div>
    <div class="panel bus-area">
      <div class="section-title">Configuración física del colectivo</div>
      <div class="bus">
        <div class="bus-front">FRENTE · CONDUCTOR</div>
        <div id="filas_asientos_estaticas"></div>
        <div class="bus-back">PARTE TRASERA</div>
      </div>
      <div class="small muted" style="margin-top:10px">Esta vista representa la configuración física del vehículo; no muestra estados de venta.</div>
    </div>
  </section>

  <section id="vendidos" class="tab-content hidden">
    <div class="panel">
      <h2>Ventas realizadas</h2>
      <div class="row">
        <label for="selector_viaje_vendido">Viaje:</label>
        <select id="selector_viaje_vendido"><option>Leon XIV en Lujan</option></select>
        <label for="filtro_vendedor">Vendedor:</label>
        <select id="filtro_vendedor"><option>Todos</option><option>Secretaria</option><option>Gaspar</option></select>
      </div>
    </div>
    <div class="panel">
      <div class="sales-list" id="lista_ventas"></div>
      <button class="btn" style="margin-top:15px" data-placeholder>Imprimir ventas</button>
    </div>
  </section>

  <section id="pasajeros" class="tab-content hidden">
    <div class="panel">
      <h2>Pasajeros / Clientes</h2>
      <div class="customer-layout">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Celular personal</th><th>Emergencia</th><th>Acción</th></tr></thead>
            <tbody id="tabla_pasajeros"></tbody>
          </table>
        </div>
        <div id="detalle_pasajero" class="customer-detail">
          <div class="detail-box">
            <h3>Datos del pasajero</h3>
            <div class="form-grid">
              <div class="field full"><label>Nombre</label><input id="pasajero_nombre"></div>
              <div class="field"><label>DNI</label><input id="pasajero_dni"></div>
              <div class="field"><label>Email (opcional)</label><input id="pasajero_email"></div>
              <div class="field"><label>Celular personal</label><input id="pasajero_celular"></div>
              <div class="field"><label>Celular emergencias</label><input id="pasajero_emergencia"></div>
            </div>
            <button class="btn primary" style="margin-top:12px" data-placeholder>Guardar</button>
            <h3 style="margin-top:20px">Pasajes vendidos</h3>
            <div id="pasajes_pasajero"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="footer-note">Prototipo visual — datos ficticios — funciones administrativas en desarrollo.</div>
</main>
<div id="toast" class="toast"></div>

<script>
// Utilidades
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

function mostrar_aviso(mensaje) {
  const aviso = $("#toast");
  aviso.textContent = mensaje;
  aviso.classList.add("show");
  clearTimeout(window.temporizador_aviso);
  window.temporizador_aviso = setTimeout(() => aviso.classList.remove("show"), 1800);
}

// Manejo de pestañas
$$(".tab").forEach(boton_pestana => {
  boton_pestana.addEventListener("click", () => {
    $$(".tab").forEach(b => b.classList.remove("active"));
    boton_pestana.classList.add("active");
    $$(".tab-content").forEach(seccion => seccion.classList.add("hidden"));
    $("#" + boton_pestana.dataset.tab).classList.remove("hidden");
  });
});

// Terminales
const ventas_por_terminal = { Secretaria: 37, Gaspar: 24 };
$("#selector_terminal").addEventListener("change", evento => {
  $("#ventas_terminal").textContent = ventas_por_terminal[evento.target.value];
});
$("#terminal_encabezado").addEventListener("change", evento => {
  mostrar_aviso("Terminal seleccionada: " + evento.target.value);
});

// Construcción de asientos
function construir_asientos(contenedor_id, interactivo = false) {
  const contenedor = $("#" + contenedor_id);
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
construir_asientos("filas_asientos", true);
construir_asientos("filas_asientos_estaticas", false);

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
actualizar_contadores_asientos();

// Datos de ejemplo de ventas
const lista_ventas_ejemplo = [
  { id: "000121", asientos: 2, total: "$120.000", cuotas: 3, abonado: "$80.000", deuda: "$40.000", vendedor: "Secretaria" },
  { id: "000122", asientos: 1, total: "$60.000", cuotas: 1, abonado: "$60.000", deuda: "$0", vendedor: "Gaspar" },
  { id: "000123", asientos: 3, total: "$180.000", cuotas: 3, abonado: "$60.000", deuda: "$120.000", vendedor: "Secretaria" },
  { id: "000124", asientos: 2, total: "$120.000", cuotas: 2, abonado: "$120.000", deuda: "$0", vendedor: "Gaspar" }
];

function renderizar_ventas(filtro = "Todos") {
  const lista = $("#lista_ventas");
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
renderizar_ventas();
$("#filtro_vendedor").addEventListener("change", evento => renderizar_ventas(evento.target.value));

// Datos de ejemplo de pasajeros
const lista_pasajeros_ejemplo = [
  { nombre: "Juan Pérez", dni: "12345678", email: "juan@email.com", celular: "2983-555555", emergencia: "2983-444444", asiento: "17" },
  { nombre: "María López", dni: "23456789", email: "", celular: "2983-666666", emergencia: "2983-777777", asiento: "18" },
  { nombre: "Carlos Gómez", dni: "34567890", email: "carlos@email.com", celular: "2983-888888", emergencia: "2983-999999", asiento: "25" },
  { nombre: "Ana Fernández", dni: "45678901", email: "ana@email.com", celular: "2983-111111", emergencia: "2983-222222", asiento: "26" }
];

function renderizar_pasajeros() {
  $("#tabla_pasajeros").innerHTML = lista_pasajeros_ejemplo.map((pasajero, indice) => `<tr>
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
renderizar_pasajeros();

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
vincular_placeholders();

// Lógica de la pestaña Administrador
$("#boton_ingresar_admin").addEventListener("click", async () => {
  const codigo = $("#codigo_admin").value.trim();
  if (!codigo) {
    mostrar_aviso("Ingrese el código");
    return;
  }
  const respuesta = await fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ accion: "administrador/verificar", codigo })
  });
  const datos = await respuesta.json();
  if (datos.exito) {
    $("#acceso_admin").classList.add("hidden");
    $("#panel_admin").classList.remove("hidden");
    cargar_datos_admin();
  } else {
    mostrar_aviso(datos.error || "Código incorrecto");
  }
});

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
    nivel: $("#nuevo_nivel").value,
    efectivo: $("#nuevo_efectivo").value.trim() || "0",
    banco_nombre: $("#nuevo_banco_nombre").value.trim(),
    banco_cuenta: $("#nuevo_banco_cuenta").value.trim()
  };
  if (!datos_usuario.nombre_usuario || !datos_usuario.contrasena) {
    mostrar_aviso("Nombre de usuario y contraseña son obligatorios");
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
    ["nuevo_nombre_usuario", "nuevo_contrasena", "nuevo_nombre_real", "nuevo_efectivo", "nuevo_banco_nombre", "nuevo_banco_cuenta"].forEach(id => $("#" + id).value = "");
    cargar_datos_admin();
  } else {
    mostrar_aviso(datos.error || "Error al agregar usuario");
  }
});
</script>
</body>
</html>