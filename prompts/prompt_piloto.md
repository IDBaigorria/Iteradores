# Prompt de trabajo — Piloto (agencia de viajes)

Este es un prompt autocontenido con todo lo que sabemos sobre el **piloto**:
la aplicación concreta construida sobre el framework Iteradores. Hoy es una
agencia de viajes para la Parroquia Nuestra Señora del Carmen, Tres Arroyos,
Argentina. Vive en `prompts/prompt_piloto.md` y se pega al principio de la
conversación junto con `prompts/prompt_framework_iteradores.md` y
`prompts/prompt_sistema_scripts.md`.

Se actualiza con **cada cambio de código**, no solo al cerrar una tanda.
La sección **Discusión actual** (al final) es la fuente de verdad sobre
dónde quedamos.

Cuando se entrega un `aplicar_cambios.php` que toca código del piloto, ese
mismo script incluye los bloques que actualizan este archivo. Ver
`prompts/prompt_sistema_scripts.md` para el detalle de la regla.

---

## 1. VISIÓN DEL PILOTO

### 1.1 Objetivo actual

Aplicación web para que una parroquia administre viajes en micros:
empresas, vehículos, viajes, terminales (puntos de venta), pasajeros,
ventas, impresión de pasajes, declaraciones juradas, cupones de pago,
rendiciones y liquidaciones.

### 1.2 Visión a futuro

El proyecto dejará de ser monolíticamente "una agencia de viajes" y pasará
a soportar múltiples **tipos de aplicación**:

- Tipo actual: `"viajera"`.
- Próximo tipo: `"tienda"` (catálogo de productos).

El plan de diversificación está en la sección 8.

### 1.3 Plugin de pruebas (proyecto separado)

El piloto tiene un **plugin de pruebas automatizadas** que vive
en un proyecto separado: `iteradoresJS/`. Es una extensión de
Chrome (MV3) que corre pruebas contra la página del piloto,
usando el framework Iteradores JS para persistir sus
resultados.

El plugin tiene su propio prompt,
`iteradoresJS/prompts/prompt_plugin_piloto.md`, que se
actualiza con cada tanda de código del plugin. **Este prompt
(el del piloto) no se toca cuando se toca solo el plugin.**

Bugs del piloto descubiertos por las pruebas del plugin:
- v74d: refresco del croquis tras cancelar venta.
- v74e: condición de carrera entre el polling de asientos y
  el clic.
- v74f: modal del viaje abierto al cambiar de pestaña.

### 1.4 Arquitectura multi-cliente (multi-aplicación)

A futuro se planea que cada cliente tenga su propio subdominio y su propio
grafo, con la misma base de código. Los grafos actuales ya están pensados
para eso:

- El grafo de la aplicación (`Conf::NOMBRE_APP`).
- El grafo de credenciales (`Conf::NOMBRE_APP_CREDENCIALES`).

Cuando llegue el momento, `Conf::NOMBRE_APP` se derivará del subdominio.

---

## 2. ESTRUCTURA DE ARCHIVOS

**Raíz:**
- `config_servidor.php`: host y credenciales. **No se toca al desplegar.**
  Contiene la clase `ConfServidor`, de la que hereda `Conf`.
- `index.php`: punto de entrada. Carga framework, persistencia, módulos de
  `Aplicacion/`, crea admin si no existe, enruta POST o sirve HTML.
  Contiene bloques temporales de migración (ver sección 8.3).
- `aplicacion_POST.php`: manejador de POST. **Contiene la documentación
  viva de la estructura de nodos en un gran bloque PHPDoc.**
- `aplicacion_GET.html`: interfaz HTML con todos los `<link>` y `<script>`
  al final. Los recursos llevan `?v=1.5piloto.XX`.
- `aplicacion.js`: utilidades globales, autenticación, modal genérico,
  modal apilado, pestañas, header-wrapper sticky.

**`prompts/`:**
- `prompt_framework_iteradores.md`: sobre el framework.
- `prompt_piloto.md`: este archivo.
- `prompt_sistema_scripts.md`: sobre el sistema de scripts.
- `prompt_continuidad_proyecto.md`: stub histórico. Ver su contenido para
  más detalle.

**CSS (dividido en 5 archivos):**
- `estilos.css`: base.
- `estilos-vehiculos.css`: croquis, editor de asientos, foto, panel datos
  del vehículo.
- `estilos-viajes.css`: tarjetas de viaje, detalle, micros, terminales,
  panel de asientos, pasaje, modales, subsección DJ.
- `estilos-ventas.css`: tarjetas de venta, badges, cuponera, cancelación,
  saldos, sub-lista de cupones.
- `estilos-rendiciones.css`: tabla de rendiciones, badges, detalle,
  ajustes.

**`Aplicacion/`:**
- `GrafoCredenciales.php`: helper `en_grafo_credenciales`.
- `FuncionesAuxiliares.php`: helpers de formato, validación
  y `guardar_ambos`.
- `admin.js`, `terminales.js`, `micros.js`.
- `Usuarios/Usuario.php`, `Sesiones/Sesion.php`, `Admin/Admin.php`,
  `Autenticacion/Autenticacion.php`.
- `Empresas/Empresa.php`, `Vehiculos/Vehiculo.php`.
- `Viajes/Viaje.php`, `Viajes/ViajeMicros.php`,
  `Viajes/ViajeAsientos.php`, `Viajes/ViajeOpciones.php`.
- `Viajes/viajes-nucleo.js`, `Viajes/viajes-micros.js`,
  `Viajes/viajes-asientos.js`, `Viajes/viajes-opciones.js`.
- `Ventas/Venta.php`, `Pasajeros/Pasajero.php`.
- `Impresion/Impresion.php`.
- `Rendiciones/Rendicion.php`, `Liquidaciones/Liquidacion.php`.
- `Enrutador.php`.
- `FuncionesAuxiliares.php`.
- `ventas.js`, `pasajeros.js`, `rendiciones.js`, `liquidaciones.js`.
- `grafo.js`: pestaña Grafo (visualizador de la superestructura).

**`Configuracion/Configuracion.php`**: clase `Conf`.

**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`.
(Los scripts `migrar_*.php` se eliminaron en v73r.)

**`Pruebas/`**: `prueba_deposito.php`, script de verificación del
depósito de IDs (se ejecuta con `?probar_deposito=1` desde
`index.php`).

**`uploads/`**: `vehiculos/`, `declaraciones_juradas/{dueno}/`.

**`JSON/`**: respaldo de cada grafo.

---

## 3. ROLES Y PESTAÑAS

### 3.1 Roles

- **`admin`**: acceso a todo, elige dueño en varios selectores, gestiona
  usuarios.
- **`dueno`**: gestiona sus empresas, vehículos, viajes, terminales,
  pasajeros, ventas, declaraciones juradas, rendiciones y liquidaciones.
- **`terminal`**: solo ve viajes autorizados, selecciona/deselecciona
  asientos propios, vende pasajes, cobra cupones, ve declaraciones juradas
  si el dueño lo autoriza.

### 3.2 Pestañas

- Administrador (solo admin).
- Puntos de venta (solo dueño).
- Empresas/Micros (admin y dueño).
- Viajes (todos).
- Vendidos (todos).
- Rendiciones (admin y dueño).
- Liquidaciones (admin y dueño).
- Pasajeros/Clientes (todos).
- Grafo (solo admin y soporte): visualizador de la superestructura.

### 3.3 Header-wrapper y tabs sticky

Desde v61: header y tabs viven dentro de un
`<div class="header-wrapper">` sticky. Se pegan juntos al scrollear. Las
tabs se achican cuando `body.scrolled` está activo (scroll > 40px). En
pantallas angostas, las tabs van con scroll horizontal.

### 3.4 Pestaña Grafo (visualizador de la superestructura)

Implementada en v1.5piloto.74p (Fase 1 del plan de optimización,
ver §8.6). Visible solo para **admin y soporte**.

**Es de solo lectura.** No modifica el grafo.

**Qué muestra:**

- **Resumen:** total de nodos, alcanzables desde las raíces
  (`usuarios`, `sesiones` y demás IDs especiales), huérfanos, y
  top 20 de nodos por referencias entrantes.
- **Tabla:** listado paginado con filtros por estado
  (`todos` / `huerfanos` / `alcanzables`), por nombre de enlace y
  por texto en el dato. Columnas: ID, tipo inferido, dato,
  cantidad de enlaces salientes, cantidad de referencias
  entrantes.
- **Detalle (modal apilado):** click en una fila → enlaces
  salientes, referencias entrantes, y navegación por los nodos
  relacionados.

**Cómo funciona:**

- El frontend (`Aplicacion/grafo.js`) llama a tres subacciones
  del enrutador: `grafo/resumen`, `grafo/listar`, `grafo/nodo`.
- El enrutador (módulo `grafo`, chequeo admin/soporte) invoca
  los comandos `grafo:resumen`, `grafo:listar`, `grafo:nodo`
  del `Controlador` a través de `Controlador::ejecutar_comando()`.
- Los comandos están definidos en
  `Controlador::registrar_comandos_grafo()` (privado). Usan el
  token interno sin exponerlo. **No usan el motor**: se ejecutan
  de a uno.
- Helpers privados en `Controlador`: `_grafo_cargar_estructura`,
  `_grafo_bfs_desde_raices`, `_grafo_inferir_tipo`.

**Limitaciones conocidas:**

- El comando `grafo:nodo` recorre todo el grafo para encontrar
  referencias entrantes (O(N) por click).
- `grafo:listar` carga todo el grafo en memoria y filtra en PHP.
- `_grafo_inferir_tipo` es heurística; puede devolver `?` para
  nodos que no cumplen ningún patrón conocido.

Estas limitaciones son aceptables para Fase 1 (diagnóstico). Se
pueden optimizar en Fase 3.

---

## 4. ESTRUCTURA DE NODOS DEL PILOTO

### 4.1 Grafos separados

Hay dos grafos independientes:

**Grafo de la aplicación** (`Conf::NOMBRE_APP` = `"AdministradorDeViajes"`):
- Nodo especial `"usuarios"` con enlaces por nombre de usuario → Nodo
  Usuario (con datos visibles).
- Nodo especial `"sesiones"` vacío (las sesiones viven en credenciales).

**Grafo de credenciales** (`Conf::NOMBRE_APP_CREDENCIALES` =
`"AdministradorDeViajes_credenciales"`):
- Nodo especial `"usuarios"` con enlaces por nombre de usuario → Nodo
  Usuario (con `codigo_hash`, `contrasena`, y campos de rate limiting).
- Nodo especial `"sesiones"` con enlaces por token → Nodo Sesión.

El punto de unión entre ambos grafos es el **nombre de usuario** (clave
del enlace en `usuarios`).

### 4.2 Nodo Usuario — grafo de la app (dato = nombre_usuario)

Enlaces:
- `nivel` → string `"admin"`, `"dueno"`, `"terminal"`.
- `nombre_real` → string (opcional).
- `email` → string (opcional).
- `efectivo` → string numérico. En terminal es el saldo en caja. En dueño
  es el efectivo acumulado de las rendiciones.
- `banco` → nodo contenedor (opcional): el dato es el monto bancarizado.
  Tiene enlaces `nombre` y `cuenta` (strings).
- `dueno` → enlace directo al Nodo Usuario dueño (solo terminal).
- `terminales` → contenedor (solo dueño): enlaces por nombre de terminal.
- `empresas` → contenedor (solo dueño): enlaces por nombre de empresa.
- `viajes` → contenedor (solo dueño): enlaces por nombre de viaje.
- `pasajeros` → contenedor (solo dueño): enlaces por DNI.
- `ventas` → contenedor (solo dueño): árbol hmi/hd.
- `rendiciones` → contenedor (solo dueño): árbol hmi/hd.
- `liquidaciones` → contenedor (solo dueño): árbol hmi/hd.
- `cancelaciones` → contenedor (solo dueño): árbol hmi/hd.
- `venta_actual` → nodo venta actual (solo terminal).

### 4.3 Nodo Usuario — grafo de credenciales

Enlaces:
- `codigo_hash` → string: hash bcrypt del código de acceso (opcional).
- `contrasena` → string: hash de contraseña (opcional).
- `intentos_fallidos` → string numérico. Se inicializa en `"0"`.
- `bloqueado_hasta` → string: timestamp Unix. Solo existe si hubo
  bloqueo.
- `ultimo_acceso` → string `"DD/MM/YYYY HH:MM"`.
- `ip_ultimo_acceso` → string.

### 4.4 Nodo Pasajero (dato = DNI)

Enlaces: `nombres`, `apellido`, `email`, `celular`,
`celular_emergencia`, `fecha_nacimiento` (YYYY-MM-DD), `localidad`,
`direccion`, `fecha_ultima_modificacion` (ISO YYYY-MM-DD).

**`declaracion_jurada`** (desde v66): nodo con dato = ruta relativa del
archivo. Enlaces: `nombre_original`, `tipo` (MIME), `tamano` (bytes),
`fecha_subida`.

Formato de nombre completo: `[apellido],[nombres]` (con coma, sin
espacio). Se implementa con `formatear_nombre_completo()`.

### 4.5 Nodo Empresa (dato = nombre_empresa)

Enlaces: `nombre`, `vehiculos` (contenedor por patente).

### 4.6 Nodo Vehículo (dato = patente)

Enlaces: `nombre`, `foto` (ruta relativa), `asientos` → contenedor con
`piso_1`, `piso_2` (opcional).

### 4.7 Nodo Piso (dato vacío)

Enlaces: `filas`, `columnas`, `asientos` → nodo cabeza de lista circular,
con `primer`.

### 4.8 Nodo Asiento (dato = número)

Enlaces: `fila`, `columna`, `siguiente`, `estado`, `seleccionado_por`,
`reservado_por`, `pasajero`, `venta`, `punto_subida_bajada`,
`hora_subida_bajada`.

### 4.9 Nodo Viaje (dato = nombre_viaje)

Enlaces: `dueno`, `nombre`, `fecha`, `hora`, `origen`, `destino`,
contadores (`ocupacion`, `disponibles`, `seleccionados`, `vendidos`,
`reservados`), `micros`, `terminales_autorizadas`, `paradas_intermedias`,
`opciones_avanzadas`, `declaracion_jurada_mayor`, `declaracion_jurada_menor`.

`opciones_avanzadas`:
- `restriccion_edad`, `edad_minima`, `edad_maxima`.
- `permite_efectivo`, `cuotas_efectivo_max`.
- `permite_transferencia`, `cuotas_transferencia_max`.
- `mostrar_dj_en_terminales`.

### 4.10 Nodo Parada

Dato: nombre. Enlaces: `hora_estimada`.

### 4.11 Nodo TerminalViaje

Enlaces: `terminal`, `cambiar_punto_predeterminado`,
`punto_subida_bajada`, y overrides de condiciones de pago.

### 4.12 Nodo Micro

Enlaces: `empresa`, `monto`, `vehiculo_copia`, `viaje`, contadores.

### 4.13 Nodo Copia Vehículo

Misma estructura que Nodo Vehículo. Asientos con estado inicial
`"libre"`.

### 4.14 Nodo Venta Actual (temporal, colgando del usuario terminal)

Enlaces: `terminal`, `micro`, `viaje`, `asientos` (cabeza lista circular).

### 4.15 Nodo Venta Persistente (dato = id_venta)

Enlaces: `terminal`, `viaje`, `micro`, `fecha_hora`, `fecha_ultimo_pago`,
`metodo_pago`, `total`, `cuotas`, `pagado`, `cuotas_restantes`,
`comprador`, `asientos` (cabeza lista simple), `cupones` (contenedor),
`opciones_cobro` (contenedor, desde v74).

**`opciones_cobro` (desde v74):** sub-nodo que congela la config
de pago vigente al momento de la venta. Es la fuente de verdad
para cobrar los cupones de esta venta. Estructura:

- `permite_efectivo` → "0"/"1"
- `cuotas_efectivo_max` → "1".."12"
- `permite_transferencia` → "0"/"1"
- `cuotas_transferencia_max` → "1".."12"

Al editar las opciones de pago del viaje o el override del
TerminalViaje, un checkbox permite actualizar los `permite_*`
de las ventas afectadas con cupones pendientes. Los
`cuotas_*_max` no se tocan retroactivamente. Ventas viejas
sin `opciones_cobro`: se migran al guardar opciones (config
vieja si no se tildó el check, nueva si se tildó).

### 4.16 Nodo Asiento-en-Venta Persistente

Enlaces: `asiento`, `pasajero`, `siguiente`, `punto_subida_bajada`,
`hora_subida_bajada`.

### 4.17 Nodo Cupón

Enlaces: `numero`, `monto`, `estado` (`"pagado"`/`"pendiente"`),
`fecha_pago`, `metodo_pago` (opcional), `rendido`.

### 4.18 Nodo Rendición

Enlaces: `dueno`, `fecha_hora`, `total`, `total_efectivo`,
`total_banco`, `cantidad_cupones`, `cantidad_ventas`,
`detalle_terminales` (árbol), `detalle_cupones` (árbol),
`desactualizada` (contenedor de ajustes).

### 4.19 Nodo Liquidación

Enlaces: `dueno`, `fecha_hora`, `total`, `monto_efectivo`,
`monto_banco`, `efectivo_restante`, `banco_restante`,
`observaciones` (opcional).

### 4.20 Nodo Cancelación

Enlaces: `dueno`, `id_venta`, `fecha_hora`, `motivo`, `terminal`,
`terminal_nombre_real`, `viaje_visible`, `micro_visible`,
`comprador_dni`, `comprador_nombre_completo`, `total_venta`,
`devuelto_efectivo`, `devuelto_banco`, `cubierto_terminal_efectivo`,
`cubierto_terminal_banco`, `cubierto_dueno_efectivo`,
`cubierto_dueno_banco`, `no_cubierto_efectivo`, `no_cubierto_banco`,
`asientos_liberados`.

### 4.21 Nodo Sesión (en grafo de credenciales)

Enlaces: `usuario` (string), `creado_en` (timestamp Unix).

---

## 5. MÓDULOS Y FUNCIONES PRINCIPALES

### 5.1 `Aplicacion/FuncionesAuxiliares.php`

- `normalizar_dni`, `formatear_dni_con_puntos`.
- `formatear_fecha_visible`.
- `formatear_nombre_completo`.
- Validaciones: `validar_dni`, `validar_telefono`, `validar_email`,
  `validar_nombre_o_apellido`, `validar_fecha_nacimiento`,
  `validar_localidad`, `validar_direccion`.
- `guardar_ambos($nombre)`: guarda la superestructura en SQL
  (única fuente de verdad). Desde v73k vive acá, no en
  `GuardarAmbos.php`. Desde v74m ya NO guarda el JSON de
  respaldo automático: el `json_encode` de todo el grafo
  se volvió el cuello de botella del guardado cuando el
  grafo creció.

### 5.2 `Aplicacion/Autenticacion/Autenticacion.php`

- `autenticar_por_codigo($codigo)`: busca usuario por `codigo_hash` en
  credenciales. Aplica rate limiting y dummy verify. Lee datos visibles
  del grafo de la app.
- `autenticar_por_usuario($nombre, $contrasena)`: similar pero busca por
  nombre y verifica `contrasena`.
- `validar_token_sesion($token)`.
- Auxiliares: `_ip_cliente`, `_verificacion_dummy`, `_esta_bloqueado`,
  `_registrar_intento_fallido`, `_registrar_login_exitoso`,
  `_construir_respuesta_login`.

### 5.3 `Aplicacion/Usuarios/Usuario.php`

- `buscar_usuario_por_codigo($codigo)`.
- `listar_usuarios`, `listar_duenos`, `obtener_saldos_dueno`.
- `agregar_usuario`, `actualizar_usuario`, `eliminar_usuario`.
- `listar_terminales_de_dueno`, `actualizar_terminal`, `eliminar_terminal`.
- `listar_soportes`, `listar_duenos_de_soporte`, `listar_usuarios_de_soporte`.
- `listar_nombres_usuarios_para_soporte($nombre_soporte, $nombre_dueno_filtro = '')`.
- `_verificar_permiso_dueno`, `_asignar_duenos_a_soporte`,
  `_puede_cerrar_sesion`.
- `obtener_perfil_usuario`.

### 5.4 `Aplicacion/Sesiones/Sesion.php`

- `crear_sesion`, `listar_sesiones`, `cerrar_sesion`,
  `listar_sesiones_de_usuarios`, `eliminar_sesiones_de_usuario`.

### 5.5 Persistencia SQL + JSON

`guardar_ambos($nombre)` vive en `FuncionesAuxiliares.php`
(ver 5.1). Guarda la superestructura en SQL (única fuente
de verdad). Desde v74m ya no guarda el JSON de respaldo:
el `json_encode` de todo el grafo se volvió el cuello de
botella cuando el grafo creció (varias terminales,
vehículos, ventas). El respaldo en otros formatos pasa a
ser una acción manual del admin, a implementar en el
rediseño del panel.

El archivo `Aplicacion/GuardarAmbos.php` existió hasta v73j. Se
eliminó en v73k: el helper se movió a `FuncionesAuxiliares.php` y
se reordenaron los `require_once` en `index.php` para que
`guardar_ambos` esté definida antes de inicializar la persistencia.

### 5.6 `Aplicacion/GrafoCredenciales.php`

- `en_grafo_credenciales(callable $fn)`: guarda app, carga/crea
  credenciales, ejecuta callback, guarda credenciales, recarga app.

### 5.7 `Viaje.php` (núcleo)

- `obtener_contenedor_viajes_dueno`.
- `listar_viajes_de_dueno`, `listar_viajes_de_terminal`.
- `formatear_viaje`.
- `viaje_tiene_ventas`.
- `_guardar_paradas_intermedias`.
- `agregar_viaje`, `editar_viaje`, `eliminar_viaje`.
- `guardar_viaje_completo`.
- `agregar_terminal_autorizada`, `eliminar_terminal_autorizada`.
- `obtener_opciones_terminal_viaje`, `guardar_opciones_terminal_viaje`.
- `obtener_opciones_avanzadas_viaje`,
  `guardar_opciones_avanzadas_viaje`.
- `obtener_declaracion_jurada`, `guardar_declaracion_jurada`.
- `_sustituir_placeholders_dj`.
- `limpiar_viajes_de_prueba($nombre_dueno)`: elimina los viajes
  de prueba de un dueño, conservando el viaje principal (por
  nombre visible) y los que no tengan prefijo de prueba
  (`viajeprueba`, `viajemicro`, `viajeval`, `viajedup`,
  `viajecol`, `viajesin`). No elimina viajes con ventas.
  Pensada para el botón de limpieza del admin.
- Constantes `TEXTO_DJ_MAYOR_DEFAULT`, `TEXTO_DJ_MENOR_DEFAULT`.

### 5.8 `ViajeMicros.php`

- `clonar_vehiculo`, `agregar_micro_a_viaje`, `eliminar_micro_de_viaje`,
  `actualizar_monto_micro`.
- `obtener_micro_de_viaje`, `contar_contadores_micro`,
  `actualizar_contadores_micro`, `actualizar_contadores_viaje`.

### 5.9 `ViajeAsientos.php`

- `obtener_configuracion_piso_con_estado`, `_dni_asignado_en_viaje`.
- `reservar_asiento_micro`, `asignar_pasajero_a_reserva`,
  `liberar_reserva_asiento_micro`.
- `obtener_estados_asientos_micro`.
- `seleccionar_asiento_micro`, `deseleccionar_asiento_micro`.

### 5.10 `Vehiculo.php`

- `listar_vehiculos_de_empresa`, `obtener_configuracion_piso`.
- `agregar_vehiculo`, `actualizar_vehiculo`,
  `actualizar_configuracion_vehiculo`, `eliminar_vehiculo`.
- `subir_foto_vehiculo`.

### 5.11 `Venta.php`

- `obtener_contenedor_ventas_dueno`.
- `obtener_o_crear_pasajero`.
- `confirmar_venta_actual`.
- `listar_ventas_por_dueno`, `listar_ventas_por_terminal`.
- `formatear_venta_resumida`, `formatear_venta_completa`.
- `obtener_venta_por_id`.
- `cancelar_venta`.
- `pagar_cupon_venta`.
- `obtener_info_cancelacion`.

### 5.12 `Pasajero.php`

- `formatear_nombre_completo`.
- `obtener_contenedor_pasajeros_dueno`, `obtener_raiz_pasajeros`,
  `obtener_pasajero_nodo_por_dni`.
- `listar_pasajeros`, `buscar_pasajeros`, `formatear_pasajero`.
- `subir_declaracion_jurada_pasajero`,
  `eliminar_declaracion_jurada_pasajero`.
- `obtener_pasajero_por_dni`.
- `actualizar_pasajero`, `crear_pasajero`.
- `pasajero_tiene_pasajes`, `pasajero_tiene_pasajes_activos`,
  `pasajero_tiene_ventas_activas`, `pasajero_tiene_reservas_activas`.
- `obtener_reservas_de_pasajero`, `formatear_venta_para_pasajero`,
  `eliminar_pasajero`.
- `limpiar_pasajeros_de_prueba($nombre_dueno)`: elimina los
  pasajeros cuyo email termina en `@test.local` (marca que
  dejan las pruebas del plugin). Conserva los que tengan
  referencias entrantes (ventas o reservas). Disponible solo
  en modo pruebas. Pensada para el botón de limpieza del
  admin.

### 5.13 `Impresion.php`

- `generar_impresion($tipo, $id_venta, $dni_filtro, $numero_cupon)`.
- `imprimir_pasaje_reserva`.
- `imprimir_pasajes`, `imprimir_pasajes_actualizados`.
- `imprimir_cupon`.
- `imprimir_informe_ventas`.
- `imprimir_informe_cancelacion`, `imprimir_informe_liquidacion`.
- `imprimir_croquis_micro`, `imprimir_planilla_pasajeros_micro`.
- `imprimir_informe_rendicion`.
- `imprimir_declaracion_jurada`.

### 5.14 `Enrutador.php`

Módulos: `autenticar`, `administrador`, `dueno`, `sesiones`,
`empresas`, `vehiculos`, `viajes`, `ventas`, `pasajeros`,
`rendiciones`, `liquidaciones`, `cancelaciones`.

Subacciones especiales:
- `viajes/limpiar_prueba` (admin + modo pruebas): elimina los
  viajes de prueba del dueño seleccionado.
- `pasajeros/limpiar_prueba` (admin + modo pruebas): elimina
  los pasajeros con email `@test.local` que no tengan
  referencias entrantes.
- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin
  permisos. Lo consume el frontend para saber si mostrar los
  botones de limpieza.
- Módulo `grafo` (admin y soporte): `grafo/resumen`,
  `grafo/listar`, `grafo/nodo`. Invocan comandos del
  `Controlador` (`grafo:resumen`, `grafo:listar`,
  `grafo:nodo`). Ver §3.4.

### 5.15 `index.php`

- Carga framework, persistencia (SQL principal), módulos de
  `Aplicacion/`.
- Requiere `Aplicacion/FuncionesAuxiliares.php` (que define
  `guardar_ambos`) antes que todo lo demás.
- Crea admin en ambos grafos si no existe.
- Bloques temporales de migración.
- Enrutado POST con `enrutar_peticion_post`.

---

## 6. FRONTEND

### 6.1 `aplicacion.js`

- Utilidades: `$`, `$$`, `mostrar_aviso`.
- `configurar_pestanas_segun_nivel`, `activar_pestana`.
- Login: `ingresar_con_codigo`, `ingresar_con_usuario`, `salir`,
  `_aplicar_login_exitoso`.
- Modales: `abrir_modal_generico`, `cerrar_modal_generico`,
  `volver_modal_generico`, `abrir_modal_apilado`, `cerrar_modal_apilado`.
- `aplicar_estado_scroll`.
- `_notificar_cambio_venta_en_curso`.

### 6.2 `admin.js`

- Tabla de usuarios: columna Código muestra `•••••` si `codigo_asignado`.
- Alta acepta código o contraseña (al menos uno).
- Después de crear/editar con código: `alert()` con el código una vez.
- Input de código en edición vacío con placeholder.

### 6.3 `terminales.js`

Mismos cambios que `admin.js` para la pestaña Puntos de venta.

### 6.4 Otros JS

- `ventas.js`: autocompletado por DNI, atadura comprador-pasajero,
  cuponera digital, cancelación, informe imprimible, rendición
  selectiva.
- `pasajeros.js`: columna "Declaración jurada" con botón ver/anexar.
- `viajes-nucleo.js`: detalle del viaje con los 4 botones de
  declaraciones juradas.
- `viajes-micros.js`, `viajes-asientos.js`, `viajes-opciones.js`,
  `micros.js`: gestión de viajes y micros.
- `rendiciones.js`, `liquidaciones.js`.
- `grafo.js`: pestaña Grafo. Ver §3.4.

### 6.5 Pantalla de login

Dos sub-bloques alternables: por código o por usuario+contraseña.

### 6.6 URLs de impresión

- `index.php?imprimir=1&tipo=pasajes&id_venta=X`.
- `index.php?imprimir=1&tipo=cupon&id_venta=X&numero_cupon=N`.
- `index.php?imprimir=1&tipo=pasaje_reserva&dueno=X&viaje=Y&micro=Z&fila=F&columna=C`.
- `index.php?imprimir=1&tipo=informe_ventas&...`.
- `index.php?imprimir=1&tipo=informe_rendicion&id_rendicion=X`.
- `index.php?imprimir=1&tipo=informe_liquidacion&id_liquidacion=X`.
- `index.php?imprimir=1&tipo=informe_cancelacion&id_cancelacion=X`.
- `index.php?imprimir=1&tipo=croquis_micro&dueno=X&viaje=Y&micro=Z`.
- `index.php?imprimir=1&tipo=planilla_pasajeros_micro&dueno=X&viaje=Y&micro=Z`.
- `index.php?imprimir=1&tipo=declaracion_jurada&dueno=X&viaje=Y&tipo_dj=mayor|menor`.

---

## 7. HISTORIAL DE VERSIONES (resumen)

- **v26-v67**: agencia de viajes madura. Rendiciones, liquidaciones,
  cupones, declaraciones juradas, autocompletado, sticky tabs, etc. Ver
  commits anteriores para detalle.
- **v68**: hasheo de credenciales. `codigo_acceso` → `codigo_hash` con
  `password_hash`. Login por código y por usuario+contraseña conviven.
  Migración `migrar_hashear_credenciales`.
- **v69**: separación de grafos. Nuevo grafo
  `AdministradorDeViajes_credenciales`. Helper `en_grafo_credenciales`.
  Migración `migrar_separar_grafos`.
- **v70**: SQL como principal + JSON como respaldo. Helper
  `guardar_ambos`. Todos los módulos migrados.
- **v71**: rate limiting y tiempos constantes. Constantes
  `INTENTOS_MAXIMOS_AUTENTICACION`, `BLOQUEO_AUTENTICACION_SEGUNDOS`,
  `HASH_DUMMY_AUTENTICACION`. `Autenticacion.php` reescrita. Campos
  nuevos en credenciales: `intentos_fallidos`, `bloqueado_hasta`,
  `ultimo_acceso`, `ip_ultimo_acceso`. Eliminado el fallback
  `verificar_codigo_admin`.
- **v71a**: prompts versionados en `prompts/`.
- **v71b**: prompts divididos en framework y piloto.
- **v72**: host y credenciales movidos a `config_servidor.php` en la raíz.
  `Conf` ahora hereda de `ConfServidor`.
- **v73**: nuevo rol `soporte`. Nodo usuario con `duenos` (contenedor).
  Enlace `soporte` en el nodo dueño. Validaciones de permisos por
  dueño con `_verificar_permiso_dueno`. Chequeo global en el enrutador.
- **v73a**: alta de usuarios `soporte` desde el panel admin con
  asignación de dueños (checkboxes). Edición de los dueños asignados
  de un soporte existente.
- **v73b**: `listar_usuarios` devuelve el campo `duenos` para los
  usuarios de nivel `soporte`, para que el panel admin pueda mostrar
  los checkboxes marcados al editar.
- **v73c**: opción "Soporte" agregada al select de nivel de edición.
  El select queda deshabilitado cuando el usuario editado es un
  soporte, porque el nivel no se puede cambiar.
- **v73d**: helper `es_admin_o_soporte()` en aplicacion.js. Todos los
  JS que chequeaban `usuario_actual.nivel === 'admin'` ahora usan el
  helper, así el soporte ve los selectores de dueño en todas las
  pestañas.
- **v73e**: edición de usuarios en modal. Se reemplazó la edición
  inline en la tabla por un modal genérico con `form-grid`.
- **v73f**: el modal de edición se centralizó en
  `abrir_modal_editar_usuario_generico` (en `aplicacion.js`).
  `admin.js` y `terminales.js` ahora son solo wrappers que lo
  llaman con opciones distintas.
- **v73g**: el alta de usuarios también pasa a modal, reutilizando
  el mismo patrón. Los formularios embebidos en el HTML quedan sin
  uso (se limpian en una próxima tanda).
- **v73h**: fix de fuga de datos al cambiar de sesión. Se limpia
  todo el contenido dinámico al salir y al ingresar. Además, el
  soporte ya no recibe la lista completa de usuarios: la tabla se
  llena solo con lo que dejó el selector de dueños.
- **v73i**: modal "Mis datos". Se puede tocar el nombre del usuario
  en el header para ver el perfil propio. Nuevo endpoint
  `usuarios/mi_perfil` (sin permisos especiales, devuelve los
  datos del solicitante). Solo lectura.
- **v73j**: fix de permisos de sesiones.
  `administrador/listar_sesiones` filtra para soportes (solo
  sus dueños asignados, sus terminales, y él mismo). El filtro
  se ajusta al dueño seleccionado en el selector del panel
  admin. `dueno/listar_sesiones_terminales` ya no confía en la
  lista de terminales del cliente: lee las terminales reales
  del dueño y aplica chequeo de nivel (solo admin, soporte y
  dueño). `sesiones/cerrar` valida que el solicitante tenga
  permiso sobre la sesión (nuevo helper `_puede_cerrar_sesion`).
  Nueva función `listar_nombres_usuarios_para_soporte`.
- **v73k**: fix de orden de includes en `index.php`. El helper
  `guardar_ambos` se movió de `Aplicacion/GuardarAmbos.php` a
  `Aplicacion/FuncionesAuxiliares.php`, y el bloque de
  inicialización de la persistencia se reordenó después de los
  `require_once` para que `guardar_ambos` esté definida antes
  de usarse. Eliminado `Aplicacion/GuardarAmbos.php`.
- **v73m**: fix del framework 1.5i.7a aplicado al piloto.
  `Controlador::cargar` devuelve `bool|null` para distinguir
  "no existe" de "error". SQL cierra conexiones en los
  early-returns y escapa el nombre en todas las queries.
  JSON escribe atómicamente y valida la estructura. Rehash
  automático de credenciales en login exitoso: si el hash
  quedó desactualizado, se regenera con la contraseña o
  código recién verificado. Las tablas SQL fueron verificadas
  con `CHECK`/`OPTIMIZE` en local y en producción el
  30/09/2026, ambas OK.
- **v73n**: fix del framework 1.5i.7b aplicado al método de
  persistencia XML (escritura atómica, validación de `<nodos>`,
  `libxml_clear_errors`, sin doble vaciado en `cargar`). De paso,
  `listar()` en JSON y XML ahora chequea el resultado de `glob()`.
  XML no se usa en el piloto; los fixes quedan aplicados por
  consistencia. ESQL sigue pendiente.
- **v73o**: script de prueba del depósito de IDs en
  `Pruebas/prueba_deposito.php`, ejecutable con `?probar_deposito=1`
  desde `index.php`. Confirma que el framework PHP NO tiene el bug
  de limpieza de IDs que sí existió en el espejo JS hasta 1.5i.6.
  De paso, se documenta el espejo JS en el prompt del framework
  (sección 12).
- **v73p**: reescritura del test del depósito de IDs. El anterior
  daba falso positivo porque intentaba recrear el ID después de
  `cargar` (que reinserta el ID al recrear el nodo). El nuevo test
  verifica directamente `vaciar_superestructura`. Se alineó
  `crear_chunks_insertar_adyacentes` con el espejo JS para no
  emitir alertas por cada nodo sin adyacentes.
- **v73q**: tanda de documentación. Se aclaran en el prompt del
  framework los cambios del PHP que no tienen análogo en JS, y se
  documenta que el bug latente `if ($elemento)` de `Iterador`
  existe en AMBOS espejos.
- **v73r**: fix del bug latente `if ($elemento)` en `Iterador.php`
  (framework 1.5i.7f). Limpieza completa de migraciones: se
  eliminaron los bloques `?migrar_*=1` de `index.php` y los
  archivos `miscelaneas/migrar_*.php` (11 en total). Se conserva
  `migrar_pasajeros.php`. Prompts: se documenta que los prompts
  viven únicamente en el proyecto PHP y la regla de espejo JS.
- **v73t**: ajuste de la impresión de la declaración jurada.
  El `font-size` pasa de 12px a 11pt (equivalente al "tamaño 11"
  de Word) y el `line-height` de 1.6 a 1.5. Solo afecta a
  `imprimir_declaracion_jurada` en `Impresion.php`.
- **v73l**: fix del guardado SQL (framework 1.5i.7). `guardar`
  usa transacción y divide los INSERT en chunks de ~200 KB para
  no superar `max_allowed_packet` (1 MB en XAMPP). `guardar_ambos`
  no guarda si la superestructura está vacía. `en_grafo_credenciales`
  verifica la recarga y lanza excepción si falla. `index.php`
  muere con mensaje claro si el grafo existe pero no se puede
  cargar, en lugar de pisarlo con vacío.
- **v73v**: fix del Bug 2 (ligadura comprador-pasajero). Los
  tres lugares del frontend donde se escribía sobre un campo
  pisando con vacíos ahora escriben solo si el valor entrante
  no está vacío: `_aplicar_datos_comprador`,
  `_aplicar_datos_pasajero` y la copia inicial de
  `_activar_atadura` en `ventas.js`. Se confirmó que la
  comparación contra todos los pasajeros ya estaba bien
  implementada en `_verificar_atadura_por_dni`. Solo frontend,
  no se tocó backend.
- **v73w**: fix del caso restante del Bug 2. Cuando el comprador
  tiene datos cargados y el pasajero está vacío (típico cuando
  el comprador también viaja y no estaba registrado), la
  activación de la atadura ahora copia en la dirección correcta.
  La copia inicial en `_activar_atadura` es bidireccional
  simétrica: rellena vacíos y, cuando hay conflicto entre
  valores no vacíos, gana el lado que el usuario escribió por
  última vez (registrado por campo en
  `window.atadura_ultimo_campo`). Los listeners de atadura
  también registran el último campo editado por el usuario.
  Solo frontend.
- **v73x**: dos bugs relacionados con la ligadura y la corrección
  de DNI. (1) `_verificar_atadura_por_dni` ahora respeta el
  estado `duplicado` del pasajero: si el pasajero está marcado
  como duplicado (ya asignado a otro asiento del mismo viaje, o
  repetido en otro formulario de esta misma venta), la atadura
  no se activa. Además, si la atadura estaba apuntando a ese
  pasajero, se rompe al momento de detectar el duplicado. (2)
  Al corregir el DNI del pasajero por uno no registrado, se
  limpian los campos no-DNI antes de habilitarlos (antes
  quedaban los del DNI previo y se propagaban por ligadura).
  Lo mismo para el comprador: si antes se habían autocompletado
  datos desde otro DNI y el nuevo no está registrado, se limpian
  los campos (`window.comprador_dni_con_datos` trackea el DNI
  del último autocompletado exitoso). Se eliminó el botón
  "Usar primer pasajero" del modal de venta: obsoleto desde que
  la atadura funciona en ambos sentidos.
- **v74**: fix del Bug 1 (opciones de cobro). Al confirmar la
  venta, se congela la config de pago vigente en un sub-nodo
  `opciones_cobro` del nodo venta. Al cobrar un cupón, se leen
  las opciones de la venta, no la config viva del viaje. Los
  modales del viaje y del override de TerminalViaje tienen un
  checkbox "Aplicar cambios de método de pago a los cupones
  pendientes...": si se tilda, se actualizan los `permite_*`
  de las ventas afectadas con cupones pendientes (los
  `cuotas_*_max` no se tocan, respetando la cantidad de cuotas
  pactadas). Si no se tilda, las ventas ya hechas no se
  modifican. Las ventas viejas sin `opciones_cobro` se migran
  al guardar opciones: con la config vieja si no se tildó el
  check, con la nueva si se tildó. Backend y frontend.
- **v74s**: Fase 2, segundo flujo arreglado.
  `eliminar_micro_de_viaje` ahora destruye el micro
  completo (copia de vehículo, pisos, asientos, campos)
  en lugar de solo desenlazarlo del contenedor del viaje.
  Reutiliza `_destruir_micro` de `Viaje.php` (agregada en
  v74r). Libera ~100 nodos por micro.
- **v74r**: Fase 2 del plan de optimización del grafo,
  primer flujo arreglado. `eliminar_viaje` ahora destruye
  el subárbol completo del viaje (micros con copias de
  vehículo y asientos, TerminalViaje, paradas, DJs,
  opciones avanzadas) en lugar de dejarlo huérfano.
  Nuevos helpers `_destruir_*` en `Viaje.php`. Se agrega
  también el pendiente de los formularios embebidos
  muertos a §8.5.
- **v74q**: solo documentación. Se agregaron dos pendientes
  al backlog (§8.5): cerrar todos los modales al cerrar
  sesión (prioridad media) y convertir en modal la carga de
  empresas y micros (prioridad media). El prompt del
  framework ganó la sección 11 "Limitaciones conocidas"
  (carga parcial, fuga de nodos, iteradores persistentes
  subutilizados). El prompt del sistema de scripts ganó
  tres aprendizajes (15-17).
- **v74p**: pestaña "Grafo" (Fase 1 del plan de optimización
  del grafo). Visible solo para admin y soporte. Vista de solo
  lectura: totales, alcanzables vs huérfanos, top de
  referencias, tabla filtrable, y modal de detalle por nodo.
  Backend: 3 comandos nuevos en el `Controlador`
  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados
  desde `registrar_comandos_grafo()`. Los comandos usan el
  token interno sin exponerlo; se ejecutan de a uno (sin
  motor). Módulo `grafo` en el enrutador con chequeo
  admin/soporte. Frontend: `Aplicacion/grafo.js` nuevo y
  nueva sección en `aplicacion_GET.html`.
- **v74o**: botón "Limpiar pasajeros de prueba" en la pestaña
  Pasajeros/Clientes. Nueva función
  `limpiar_pasajeros_de_prueba($nombre_dueno)` en
  `Pasajero.php` y subacción `pasajeros/limpiar_prueba` en el
  enrutador. Criterio: email termina en `@test.local`.
  Conserva los pasajeros con referencias entrantes (ventas
  o reservas). También se agregó: `index.php` establece
  `Entorno::MODO_PRUEBAS` o `MODO_PRODUCCION` según
  `Conf::LOCAL` e inyecta `window.entorno_es_pruebas` en el
  HTML; nueva subacción `entorno/info`; los botones de
  limpieza (viajes y pasajeros) ahora aparecen solo si el
  admin está en modo pruebas.
- **v74n**: botón "Limpiar viajes de prueba" en la pestaña
  Viajes (solo admin). Nueva función
  `limpiar_viajes_de_prueba($nombre_dueno)` en `Viaje.php` y
  subacción `viajes/limpiar_prueba` en el enrutador.
  Conserva el viaje principal "Peregrinación a la Visita
  del Papa León XIV a Luján" (por nombre visible) y los
  viajes que no tengan prefijo de prueba. No elimina
  viajes con ventas. Motivo: las pruebas automáticas del
  plugin acumulan viajes (20 con el grafo actual) que
  ralentizan los listados: `formatear_viaje` recorre todas
  las ventas del dueño por cada viaje, así que el costo de
  `cargar_viajes` escala con V × W.
- **v74m**: eliminado el respaldo JSON automático de
  `guardar_ambos`. Motivo: el `json_encode` de todo el
  grafo se volvió el cuello de botella del guardado cuando
  el grafo creció (3 terminales, varios vehículos, ventas,
  cupones). Cada operación tardaba segundos, y eso rompía
  los timeouts de las pruebas del plugin. Ahora
  `guardar_ambos` solo guarda SQL. La implementación
  `PerdurarSuperestructuraStringJSON` sigue disponible en
  el framework. El respaldo en formatos alternativos
  (JSON, XML) pasa a ser una acción manual del admin, a
  implementar en el rediseño del panel.
- **v74k**: fixes de validación en el alta de micro.
  `agregar_micro_a_viaje` (en `ViajeMicros.php`) rechaza
  vehículos sin asientos configurados y vehículos ya
  agregados al mismo viaje (comparación case-insensitive
  con `strcasecmp`). El nombre del micro se calcula con
  `max(existentes) + 1` en vez de `count + 1`, para evitar
  colisiones cuando se borra un micro del medio; y se
  chequea el resultado de `_adyacente_en`. En el frontend
  (`viajes-micros.js`), los vehículos sin asientos
  aparecen deshabilitados en el select con sufijo
  "— Sin asientos configurados"; el botón Confirmar
  también valida que no se haya elegido uno disabled.
- **v74j**: modo prueba para alertas críticas. Nuevo helper
  `_mostrar_alerta_critica()` en `aplicacion.js`: solo
  dispara `alert()` si `window.__iteradores_modo_prueba`
  NO está en `true`. Los dos `alert("Código de acceso: ...")`
  (alta y edición de usuarios/terminales) usan el helper.
  El plugin `iteradoresJS/` activa el modo prueba antes de
  ejecutar flujos que disparan el alert, para no bloquear
  el page context durante las pruebas.
- **v74h**: cierre de auditoría de pendientes. Fix del
  autocompletado por DNI cuando el usuario es terminal y no
  hay viaje seleccionado (modal de alta de pasajero desde
  la pestaña Clientes): el dueño se resuelve desde
  `usuario_actual.dueno` como fallback. Bump de `?v=` de
  `ventas.js` en `aplicacion_GET.html` (estaba en `.73x`).
  Correcciones al prompt: rehash automático y limpieza de
  migraciones ya estaban implementados;
  `boton_reiniciar_numeracion` y `ver_compra_asiento`
  también; el autocompletado por DNI desde Clientes ya
  funciona; `migrar_pasajeros.php` ya no existe;
  `GuardarAmbos.php` fue eliminado en v73k.
- **v74g**: solo documentación. Se registran los bugs del
  piloto detectados por las pruebas automáticas del plugin
  (`iteradoresJS/`): v74d (refresco del croquis tras cancelar
  venta), v74e (condición de carrera entre el polling de
  asientos y el clic), v74f (modal del viaje abierto al
  cambiar de pestaña). Se aclara que el plugin es un
  proyecto independiente en `iteradoresJS/` con su propio
  prompt en `iteradoresJS/prompts/prompt_plugin_piloto.md`.
- **v74f**: cerrar el modal del viaje al cambiar de pestaña.
  Si el usuario o una prueba automática cambia de pestaña con
  el modal del detalle del viaje abierto, `ocultar_detalle_viaje`
  mataba el polling pero dejaba el modal con el croquis
  congelado en pantalla con datos viejos. Fix: en
  `activar_pestana`, si la pestaña destino no es "viajes" y
  hay un modal con `#lista_micros_viaje`, cerrarlo antes de
  ocultar el detalle. Detectado por las pruebas del plugin.
- **v74e**: fix de condición de carrera entre el polling de
  asientos y el clic. Si el fetch del polling estaba en vuelo
  cuando se iniciaba una operación de asiento, el polling
  pisaba el estado nuevo con el viejo. El asiento se veía
  seleccionado y se deseleccionaba solo. Fix: descartar la
  respuesta del polling si `operacion_asiento_en_curso`.
  Detectado por las pruebas del plugin.
- **v74d**: fix de refresco del croquis tras cancelar venta.
  Al cancelar, el backend liberaba los asientos pero el
  frontend seguía mostrando el croquis viejo hasta el próximo
  polling (`SYNC_INTERVALO_MS`, hasta 15s, pausado si hubo
  inactividad). Ahora `cancelar_venta` captura el micro y el
  viaje abiertos antes de cerrar el modal, y después del
  éxito fuerza un fetch a `viajes/estado_asientos` para
  actualizar `estados_asientos_actuales`. Detectado por las
  pruebas automáticas del plugin.
- **v74c**: solo documentación. Se formalizó el arranque del
  proyecto plugin (segundo piloto). Decisiones tomadas:
  manifest en la raíz de `iteradoresJS/` y código en
  `Aplicacion/` (opción A), versión `v1.5plugin.0` para el
  plugin, persistencia con `PerdurarSuperestructuraStringIndexedDB`,
  salida en modo consola dentro del service worker (evita los
  caminos que tocan `document`), content script clásico
  (sin imports) comunicado por `chrome.runtime.sendMessage`,
  pruebas declarativas con objeto `{id, nombre, ejecutar(ctx)}`.
  El asistente leyó el framework JS (`Objeto`, `Nodo`,
  `Iterador`, `Controlador`, `Entorno`, `Conf`, persistencia,
  `Comando`). Se aclaró que `MOTOR_MAX_CICLOS` son ciclos
  totales del motor, no comandos por ciclo ni ciclos por
  minuto. El token de seguridad no lo maneja el plugin:
  todo pasa por `Controlador.ejecutar_prueba(cb)`.
- **v74a**: fix del retroactivo de opciones de cobro. En v74,
  `_aplicar_retroactivo_a_ventas_del_viaje` y
  `_aplicar_retroactivo_a_ventas_de_terminal` leían la config
  nueva con `_config_pago_resuelta_para_venta`, que devuelve
  el `opciones_cobro` congelado de la venta. Resultado: si la
  venta ya tenía `opciones_cobro`, el retroactivo no hacía
  nada (los `permite_*` no cambiaban). Fix: resolver con
  `_config_pago_resuelta_para_viaje_terminal`, que mira el
  viaje + override del TerminalViaje en vivo, ignorando el
  `opciones_cobro` de la venta. El flujo de migración de
  ventas viejas sin `opciones_cobro` no estaba afectado
  (resolvía en vivo, que post-save ya devolvía la config
  nueva). Solo backend.

---

## 8. ESTADO ACTUAL Y PRÓXIMOS PASOS

### 8.1 Estado al cierre de v1.5piloto.71

La app tiene todo lo de v67 más:

- **Credenciales hasheadas** con `password_hash`.
- **Login por código y por usuario+contraseña** conviviendo.
- **Grafo de credenciales separado** del grafo de la aplicación.
- **Rate limiting**: 5 intentos, 15 minutos de bloqueo, tiempos
  constantes con dummy verify.
- **Auditoría de accesos**: `ultimo_acceso` e `ip_ultimo_acceso`.
- **Respaldo JSON** con cada guardado (SQL principal).

### 8.2 Limpieza de migraciones

**Integridad de las tablas SQL:** verificada con `CHECK TABLE` y
`OPTIMIZE TABLE` en local y en producción el 30/09/2026, ambas OK.

**Limpieza de migraciones: completada en v73r.** Se eliminaron los
bloques `?migrar_*=1` de `index.php` y los archivos
`miscelaneas/migrar_*.php`:

- `migrar_micros`, `migrar_micros_patente`,
  `migrar_terminales_autorizadas`, `migrar_cupones`,
  `migrar_nombres_pasajeros`,
  `migrar_fecha_ultima_modificacion_pasajeros`,
  `migrar_declaraciones_juradas_v2`,
  `migrar_declaraciones_juradas_v3`, `migrar_fichas_medicas`,
  `migrar_hashear_credenciales`, `migrar_separar_grafos`.

(No quedan archivos `migrar_*.php` en el proyecto.)

### 8.3 Bugs conocidos (prioridad alta)

**Bug 1 — Opciones de cobro de cupones.** Las opciones generales
de pago (del viaje) se usan al cobrar cuotas, sin considerar las
opciones individuales de la venta. Si el dueño cambia las generales
después de la venta, el cobro toma las nuevas, lo cual es incorrecto.

Diseño acordado: **opciones en dos niveles, individuales pisan a
generales**. Al confirmar la venta, se guardan copias de las
opciones de cobro vigentes en ese momento como opciones individuales
de la venta. Los cambios posteriores en las generales no afectan a
las ventas ya hechas. En el modal de pago de cupón, agregar un
botón "Cambiar método de cobro" que abra otro modal para editar
esas opciones individuales.

**Bug 2 — Ligadura comprador ↔ pasajero en el alta de venta.**
**Resuelto.** v73v fixeó los tres puntos donde se escribía
pisando con vacíos. v73w fixeó el caso restante: cuando el
comprador tiene datos y el pasajero está vacío (típico cuando
el comprador también viaja y no estaba registrado), la atadura
ahora copia en la dirección correcta. La copia inicial de
`_activar_atadura` es bidireccional simétrica con resolución de
conflictos por último escrito del usuario
(`window.atadura_ultimo_campo`). Los listeners de atadura
registran el último campo editado por el usuario en cada lado.

v73x cerró dos bugs relacionados: (1) la atadura ya no se
activa cuando el pasajero está marcado como duplicado (ya
asignado a otro asiento del mismo viaje, o repetido en otro
formulario de esta misma venta); (2) al corregir el DNI por
uno no registrado, se limpian los campos autocompletados del
pasajero y del comprador, para no arrastrar datos del DNI
anterior. También se eliminó el botón "Usar primer pasajero",
obsoleto.

**Bug 1 — Opciones de cobro de cupones. Resuelto en v74.**
Diseño: al confirmar la venta se congela la config de pago
vigente en un sub-nodo `opciones_cobro` del nodo venta. Al
cobrar un cupón se leen las opciones de la venta, no la config
viva del viaje. El modal del viaje y el del override del
TerminalViaje tienen un checkbox "Aplicar cambios de método de
pago a los cupones pendientes de ventas ya hechas (no afecta
la cantidad de cuotas pactadas)". Si se tilda, se actualizan
los `permite_*` de las ventas afectadas con cupones pendientes.
Los `cuotas_*_max` nunca se tocan retroactivamente. Las ventas
viejas sin `opciones_cobro` se migran al guardar opciones:
con la config vieja si no se tildó el check, con la nueva si
se tildó. No hay botón ni submodal en el modal de pago de
cupón: la decisión se toma siempre en el momento de editar
las condiciones de pago.

### 8.4 Pestaña Grafo (visualizador de la superestructura)

**Implementada en v74p (Fase 1).** Ver §3.4 para el detalle de
qué hace y cómo funciona, y §8.6 para el plan completo de las
tres fases de optimización del grafo.

Pendiente para futuras iteraciones del visualizador:

- Listado de iteradores creados con sus cuerpos, alias y
  posición actual (no implementado en Fase 1).
- Acciones sobre iteradores (crear, destruir, desocupar, ver
  caminos registrados) (no implementado en Fase 1).
- Acciones sobre nodos (eliminar huérfanos, etc.) (no
  implementado en Fase 1).

### 8.5 Próximos pasos posibles

**Diversificación por tipo de aplicación** (próximo gran frente):

- Agregar `tipos_de_aplicacion` como nodo especial raíz.
- Agregar `tipo_app` como enlace del nodo dueño.
- Ajustar el login para devolver `tipo_app`.
- Ajustar el frontend para mostrar pestañas según el tipo.
- Eventualmente, dividir el enrutador por tipo.

**Tienda virtual**: catálogo de productos, categorías, stock, carrito,
checkout. Reutilizar usuarios, terminales, ventas, cupones, rendiciones
y liquidaciones.

**Otros**:

- Panel de super admin (incremental al admin actual; alcance a
  consensuar).
- Métricas / reportes adicionales: solo ocupación por viaje y
  consolidado de liquidaciones.
- **Cerrar todos los modales al cerrar sesión** (prioridad
  media). Actualmente el modal chico de post-venta
  (`#opciones_impresion`) y a veces otros quedan abiertos al
  salir. Debería limpiarse todo en `salir()` de `aplicacion.js`.
  El `_limpiar_contenido_dinamico` actual limpia el contenido
  de los contenedores pero no oculta los overlays de modal
  (`#modal_generico`, `#modal_apilado`) ni los modales chicos
  flotantes (`#modal_chico_*`).
- **Convertir en modal la carga de nuevas empresas y micros**
  (prioridad media). Hoy son formularios inline
  (`#formulario_nueva_empresa`, `#formulario_nuevo_vehiculo`).
  Migrar al patrón de modal genérico como se hizo con usuarios
  y terminales (v73e-v73g).
  - **Recordatorio:** al migrar a modal, eliminar los
    formularios embebidos de `aplicacion_GET.html` que
    queden muertos: `#formulario_nueva_empresa`,
    `#formulario_nuevo_vehiculo`, y también los ya muertos
    desde v73g (`#formulario_nuevo_usuario`,
    `#formulario_nueva_terminal`). Es código muerto que
    sigue en el HTML y puede inducir a error al escribir
    pruebas del plugin (ver aprendizaje 30 en el prompt
    del plugin).

**Implementados (ya no son pendientes):**

- Autocompletado de pasajeros por DNI en el alta desde la pestaña
  Clientes: los listeners de `conectar_listeners_formulario_pasajero`
  ya autocompletan. Fix v74h: cuando el usuario es terminal y no
  hay viaje seleccionado, el dueño se resuelve desde
  `usuario_actual.dueno`.
- `boton_reiniciar_numeracion`: existe como botón dinámico dentro
  del modal de "números de asiento duplicados"
  (`mostrar_aviso_numeros_duplicados`). No se agrega al HTML
  estático.
- `ver_compra_asiento`: implementado como navegación a la pestaña
  Vendidos con resaltado (vía `ir_a_venta_en_vendidos`). No es
  modal.

### 8.6 Plan de optimización del grafo (tres fases)

**Contexto.** La aplicación se vuelve lenta a medida que el
grafo crece. Mediciones concretas:

- Grafo de ~10.000 nodos → ~50-70s por prueba del plugin.
- Grafo de ~2.000 nodos → ~15-18s por prueba.

El problema es la cantidad de nodos, no el código de las
pruebas ni el guardado. El grafo crece porque el piloto no
elimina bien los nodos cuando se cancela o elimina una
entidad: los descuelga del contenedor pero no los destruye.
Como el framework no permite eliminar un nodo con referencias
entrantes, esos nodos quedan huérfanos. Con cada operación se
acumulan.

Hay dos frentes pendientes que interactúan:

**(A) Limitación del framework.** El framework no puede
cargar/guardar partes reducidas del grafo. Toda operación
carga los N nodos enteros. Se discute a futuro; requiere
revisar teoría de grafos y el modelo de persistencia. No se
toca ahora.

**(B) Falencias del piloto.** No elimina bien los nodos al
cancelar cosas. En algunos casos NO hay que eliminar
(datos del cliente que se conservan a propósito). En otros
SÍ hay que eliminar (viaje, cliente, terminal, micro,
cancelaciones, cupones).

**Plan en tres fases:**

**Fase 1 — Pestaña Grafo (diagnóstico). Implementada en v74p.**

Vista de solo lectura para ver la fuga con los ojos. Ver §3.4.
Es la herramienta que habilita las Fases 2 y 3.

**Fase 2 — Auditoría de la fuga de nodos (en curso, prioridad alta).**

**Segundo flujo arreglado en v74s:** `eliminar_micro_de_viaje`.
Antes solo desenlazaba el micro del contenedor `micros` del
viaje. Ahora llama a `_destruir_micro` (helper de `Viaje.php`
agregado en v74r), que destruye la copia del vehículo (con
sus pisos y asientos), los campos del micro, y desenlaza las
referencias externas (empresa) y circular (viaje). Libera
~100 nodos por micro.

**Primer flujo arreglado en v74r:** `eliminar_viaje`. Antes
solo desenlazaba el viaje del contenedor del dueño y dejaba
huérfanos el nodo viaje, todos sus micros con copias de
vehículo y asientos, los TerminalViaje, las paradas
intermedias, las DJs y las opciones avanzadas. Ahora llama
a `_destruir_viaje_completo`, que recorre el subárbol en
orden (micros → TerminalViaje → paradas → DJs → opciones
→ campos del viaje) y destruye cada nodo. Los helpers
`_destruir_*` en `Viaje.php` son reutilizables para los
próximos flujos.

**Hallazgo del detector de `_adyacente_en(..., true)`:** los
3 usos en `Venta.php:1279/1286` (en `cancelar_venta`, sección
7: desenlazar la venta del árbol del dueño, seguido de
`Nodo::eliminar($nodo_venta)`) y `Viaje.php:900` (en
`guardar_opciones_terminal_viaje`: reemplazar el enlace
`punto_subida_bajada` del TerminalViaje, donde la parada
vieja sigue viva en `paradas_intermedias`) son correctos
por diseño. No son fugas.

**Otros flujos con fuga pendientes de arreglar** (mismo
patrón de "desenlazar sin destruir"):

- `eliminar_micro_de_viaje` (en `ViajeMicros.php`):
  desenlaza el micro del contenedor pero no destruye
  el micro, su copia de vehículo, ni sus asientos.
- `eliminar_terminal_autorizada` (en `Viaje.php`):
  desenlaza el TerminalViaje pero no lo destruye.
- `_guardar_paradas_intermedias` (en `Viaje.php`): al
  editar las paradas, las que no se reutilizan quedan
  huérfanas.
- `eliminar_pasajero` (en `Pasajero.php`): a auditar.
- `cancelar_venta` (en `Venta.php`): auditar el uso
  de `Nodo::eliminar($nodo_venta)` sin chequear el
  resultado.

Con la pestaña Grafo como herramienta:

1. Recorrer cada flujo de eliminación del piloto:
   - Alta/baja de viaje (`eliminar_viaje`).
   - Alta/baja de cliente (`eliminar_pasajero`).
   - Alta/baja de terminal (`eliminar_terminal`).
   - Alta/baja de micro (`eliminar_micro_de_viaje`).
   - Alta/baja de vehículo (`eliminar_vehiculo`).
   - Alta/baja de empresa (`eliminar_empresa`).
   - Cancelación de venta (`cancelar_venta`).
   - Cupones, rendiciones, liquidaciones, cancelaciones.
2. Para cada uno, anotar:
   - Qué nodos se desenlazan.
   - Qué nodos deberían destruirse también (y hoy no se
     destruyen).
   - Qué nodos se conservan a propósito (ej.: datos del
     cliente).
3. Implementar los fixes en tandas chicas, midiendo el total
   de nodos antes y después.
4. Documentar los criterios en §8.6.1.

**Fase 3 — Optimizaciones (pendiente, prioridad media).**

Solo si después de Fase 2 todavía hace falta velocidad:

- **Iteradores persistentes.** El framework permite iteradores
  que van perdurando su posición actual en el grafo. Podrían
  reducir los recorridos repetidos (por ejemplo, en
  `formatear_viaje`).
- **Cacheo de contadores.** El `formatear_viaje` actual
  escala como O(V × W): por cada viaje, recorre todas las
  ventas del dueño. Con un índice de ventas por viaje +
  cacheo de contadores, baja a O(V + W).
- **Eventual carga parcial del grafo.** Requiere cambiar el
  framework (frente A). No se hace por ahora.

### 8.6.1 Criterios de eliminación

**Stub. A completar en Fase 2.**

Cuando se cancela/elimina una entidad, no siempre hay que
destruir los nodos asociados. Algunos se conservan a
propósito (datos del cliente). Otros deben destruirse
(viaje, micro, terminal). Esta sección documenta el criterio
por tipo de entidad.

**Criterio general (a validar caso por caso):**

- **Datos del cliente (pasajero, comprador):** se conservan
  aunque la venta se cancele. El cliente puede volver a
  comprar.
- **Entidades operativas (viaje, micro, venta, cupón,
  terminal):** al eliminarse, sus nodos deberían destruirse.
  Los nodos que cuelgan de ellas también (asientos de copia,
  cupones, etc.) salvo que estén referenciados desde otro
  lado.
- **Nodos "hijos" (asientos, cupones, etc.):** se destruyen
  junto con su padre, salvo que tengan referencias
  entrantes (en cuyo caso primero se desenlazan las
  referencias).

**Pendiente:** documentar el criterio por entidad concreta
después de la auditoría.

---

## 9. PATRONES DE CÓDIGO DEL PILOTO

### 9.1 Modal apilado

```js
abrir_modal_apilado('Título', html);
const contenedorModal = document.getElementById('modal_apilado_contenido');
// ...
cerrar_modal_apilado();
```

### 9.2 Modal genérico con hook de cierre

```js
window.on_cerrar_modal_generico = () => {
    window.venta_cuponera_actual = null;
    if (typeof cargar_ventas === 'function') cargar_ventas();
};
abrir_modal_generico('Título', html);
```

### 9.3 Formulario de pasajero reutilizable

```js
const html_campos = construir_html_formulario_pasajero(0, {
    incluir_selector_sb: false
});
conectar_listeners_formulario_pasajero(contenedorModal, 0);

const r = recolectar_datos_pasajero(0, {
    incluir_selector_sb: false
});
if (!r.ok) { mostrar_aviso(r.error, 'error'); return; }
const datos = r.datos;
```

### 9.4 Endpoint POST desde el frontend (urlencoded)

```js
fetch("index.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
        accion: "modulo/subaccion",
        param1: valor1
    })
})
.then(r => r.json())
.then(resultado => {
    if (resultado.exito) { /* OK */ }
    else { mostrar_aviso(resultado.error || "Error", 'error'); }
});
```

### 9.5 Endpoint POST (multipart con archivo)

```js
const form_data = new FormData();
form_data.append('accion', 'pasajeros/subir_declaracion');
form_data.append('nombre_dueno', nombre_dueno);
form_data.append('dni', dni);
form_data.append('archivo', archivo);

const resp = await fetch("index.php", { method: "POST", body: form_data });
const resultado = await resp.json();
```

### 9.6 Subida de archivo (patrón a imitar)

```php
function subir_X(string $nombre_dueno, string $dni, array $archivo): array {
    if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
        return ['exito' => false, 'error' => 'No se recibió archivo válido'];
    }
    $tamano = (int)($archivo['size'] ?? 0);
    if ($tamano > 5 * 1024 * 1024) return ['exito' => false, 'error' => '...'];
    $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($extension, $permitidas)) return ['exito' => false, 'error' => '...'];
    $carpeta = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre_dueno);
    $directorio = __DIR__ . '/../../uploads/...' . $carpeta . '/';
    if (!is_dir($directorio)) mkdir($directorio, 0777, true);
    // ... borrar anterior si existe, mover, actualizar nodo
    guardar_ambos(Conf::NOMBRE_APP);
    return ['exito' => true];
}
```

### 9.7 Operar sobre el grafo de credenciales

```php
$resultado = en_grafo_credenciales(function() use ($parametro) {
    $raiz = Nodo::nodo_por_id('usuarios');
    // ...
    return $valor;
});
// Al salir, la app ya está cargada de nuevo.
```

### 9.8 Guardar después de modificar el grafo

```php
// BIEN:
guardar_ambos(Conf::NOMBRE_APP);
guardar_ambos(Conf::NOMBRE_APP_CREDENCIALES);  // dentro de en_grafo_credenciales

// MAL (solo persiste en SQL):
Controlador::guardar(Conf::NOMBRE_APP);
```

### 9.9 Uso correcto de `obtener_dueno_viaje_seleccionado()`

```js
// MAL: para terminal, devuelve el nombre de la terminal.
const nombre_dueno = obtener_nombre_dueno_actual();

// BIEN: prioriza viaje_seleccionado.dueno.
const nombre_dueno = obtener_dueno_viaje_seleccionado();
```

### 9.10 Controlador de bloqueo por venta en curso

En `ventas.js`:

```js
window.venta_en_curso = function() {
    try { return venta_form_abierto === true; } catch (e) { return false; }
};
```

En `viajes-asientos.js`:

```js
function _venta_en_curso() {
    if (typeof window.venta_en_curso === 'function') {
        try { return window.venta_en_curso() === true; } catch (e) { return false; }
    }
    return false;
}
```

---

## 10. CONTEXTO SOBRE EL USUARIO

- Es programador y está aprendiendo el framework Iteradores en paralelo.
- Entiende bien PHP, JS, HTML y CSS.
- Es muy meticuloso con el formato y con no perder funcionalidad.
- Prefiere archivos completos a parches, aunque sean largos.
- Trabaja en paralelo en varias versiones (local, clon, producción).
- Cuando algo no funciona, suele pasar los mensajes de la consola o los
  JSON del backend.
- Aprecia el orden: cada cosa en su lugar, cada versión en su rama de Git.
- Valora que se le avise cuando algo puede romper.
- Prefiere el sistema de scripts de aplicación de cambios al copiado
  manual.
- Es abierto a sugerencias de mejora razonables.
- **Le interesa la diversificación del proyecto** hacia una plataforma
  multi-tipo.
- Desde v71a los prompts viven en el proyecto y se actualizan con cada
  tanda.

---

## 11. ARCHIVOS QUE EL USUARIO PUEDE PASAR

**Backend:**
- `index.php`, `aplicacion_POST.php`, `aplicacion_GET.html`.
- `Aplicacion/Enrutador.php`, `Aplicacion/FuncionesAuxiliares.php`.
- `Aplicacion/GrafoCredenciales.php`.
- `Aplicacion/Viajes/Viaje.php`, `ViajeMicros.php`, `ViajeAsientos.php`,
  `ViajeOpciones.php`.
- `Aplicacion/Ventas/Venta.php`.
- `Aplicacion/Pasajeros/Pasajero.php`.
- `Aplicacion/Impresion/Impresion.php`.
- `Aplicacion/Empresas/Empresa.php`.
- `Aplicacion/Vehiculos/Vehiculo.php`.
- `Aplicacion/Rendiciones/Rendicion.php`,
  `Aplicacion/Liquidaciones/Liquidacion.php`.
- `Aplicacion/Usuarios/Usuario.php`, `Sesiones/Sesion.php`,
  `Admin/Admin.php`, `Autenticacion/Autenticacion.php`.

**Frontend:**
- `aplicacion.js`.
- `Aplicacion/ventas.js`, `pasajeros.js`, `micros.js`, `admin.js`,
  `terminales.js`, `rendiciones.js`, `liquidaciones.js`.
- `Aplicacion/Viajes/viajes-nucleo.js`, `viajes-micros.js`,
  `viajes-asientos.js`, `viajes-opciones.js`.

**CSS:**
- `estilos.css`, `estilos-vehiculos.css`, `estilos-viajes.css`,
  `estilos-ventas.css`, `estilos-rendiciones.css`.

**Config:**
- `Configuracion/Configuracion.php`.

**Utilidades:**
- `miscelaneas/Arbol.php`, `benchmark.php`, `generarUUID.php`.
- Cualquier `miscelaneas/migrar_*.php` activo.

**Prompts:**
- `prompts/prompt_framework_iteradores.md`.
- `prompts/prompt_piloto.md`.
- `prompts/prompt_sistema_scripts.md`.

---

## 12. DISCUSIÓN ACTUAL

**Este bloque es lo primero que hay que actualizar al cerrar cada tanda.**

**Última actualización de este prompt:** v1.5piloto.74s
(Fase 2, segundo flujo arreglado: `eliminar_micro_de_viaje`.
Ahora destruye el micro completo (copia de vehículo, pisos,
asientos, campos) en lugar de solo desenlazarlo del contenedor
del viaje. Reutiliza `_destruir_micro` de `Viaje.php`. Libera
~100 nodos por micro).
Antes: v1.5piloto.74r
(Fase 2 del plan de optimización del grafo: primer flujo
arreglado, `eliminar_viaje`. Ahora destruye el subárbol
completo del viaje en lugar de dejarlo huérfano. Nuevos
helpers `_destruir_*` en `Viaje.php`, reutilizables para
los próximos flujos. Se agrega también el pendiente de
los formularios embebidos muertos a §8.5).
Antes: v1.5piloto.74q
(solo documentación. Se agregaron dos pendientes al backlog:
cerrar todos los modales al cerrar sesión y convertir en modal
la carga de empresas y micros, ambos de prioridad media).
Antes: v1.5piloto.74p
(pestaña "Grafo", Fase 1 del plan de optimización del grafo.
Visible solo para admin y soporte. Vista de solo lectura.
Backend: 3 comandos nuevos en el `Controlador`
(`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados
desde `registrar_comandos_grafo()`, ejecutados vía
`Controlador::ejecutar_comando()` sin usar el motor. Módulo
`grafo` en el enrutador. Frontend nuevo: `Aplicacion/grafo.js`.
Se agregaron §3.4 (pestaña Grafo), §8.6 (plan de las tres
fases) y §8.6.1 (criterios de eliminación, stub)).
Antes: v1.5piloto.74o
(botón "Limpiar pasajeros de prueba" en la pestaña
Pasajeros/Clientes. Nueva función
`limpiar_pasajeros_de_prueba` en `Pasajero.php` y subacción
`pasajeros/limpiar_prueba`. Los botones de limpieza (viajes
y pasajeros) ahora aparecen solo si el admin está en modo
pruebas (`Entorno::es_pruebas()`). Nuevo endpoint
`entorno/info` y bandera `window.entorno_es_pruebas`
inyectada por `index.php`).
Antes: v1.5piloto.74n
(botón "Limpiar viajes de prueba" en la pestaña Viajes para
el admin. Nueva función `limpiar_viajes_de_prueba` en
`Viaje.php` y subacción `viajes/limpiar_prueba` en el
enrutador. Conserva el viaje principal y los que no tengan
prefijo de prueba. Motivo: las pruebas del plugin acumulan
viajes que ralentizan `cargar_viajes`).
Antes: v1.5piloto.74m
(eliminado el respaldo JSON automático de `guardar_ambos`.
El `json_encode` de todo el grafo se volvió el cuello de
botella cuando el grafo creció con terminales, vehículos y
ventas: cada operación de guardado tardaba segundos y
rompía los timeouts de las pruebas del plugin. Ahora
`guardar_ambos` solo guarda SQL. El respaldo en otros
formatos pasa a ser una acción manual del admin, a
implementar en el rediseño del panel).
Antes: v1.5piloto.74k (fixes
de validación en el alta de micro. `agregar_micro_a_viaje`
rechaza vehículos sin asientos y vehículos duplicados en el
mismo viaje; nombre del micro con `max+1` para evitar
colisiones. Frontend: vehículos sin asientos aparecen
deshabilitados en el select).
Antes: v1.5piloto.74j (modo
prueba para alertas críticas: nuevo helper
`_mostrar_alerta_critica()` en `aplicacion.js` que solo
dispara `alert()` si `window.__iteradores_modo_prueba` no
está activo. Los dos `alert("Código de acceso: ...")` (alta
y edición de usuarios/terminales) usan el helper. El plugin
de `iteradoresJS/` activa el modo prueba antes de los flujos
que disparan el alert, para no bloquear el page context).
Antes: v1.5piloto.74h (fix del
autocompletado por DNI cuando el usuario es terminal y no hay viaje
seleccionado; el dueño se resuelve desde `usuario_actual.dueno`.
Bump de `?v=` de `ventas.js` en `aplicacion_GET.html`. Corrección
de documentación: rehash automático y limpieza de migraciones
ya están implementados, `boton_reiniciar_numeracion` y
`ver_compra_asiento` también; `migrar_pasajeros.php` ya no existe;
`GuardarAmbos.php` fue eliminado en v73k).
Antes: v1.5piloto.74g (solo
documentación: se registran los bugs del piloto detectados
por las pruebas automáticas del plugin en `iteradoresJS/`,
y se aclara la relación con el proyecto plugin).
Antes: v1.5piloto.74f (cerrar
el modal del viaje al cambiar de pestaña. Si el usuario o una
prueba automática cambia de pestaña con el modal del detalle
del viaje abierto, `ocultar_detalle_viaje` mataba el polling
pero dejaba el modal con el croquis congelado. Fix: en
`activar_pestana`, si la pestaña destino no es "viajes" y hay
un modal con `#lista_micros_viaje`, cerrarlo antes de ocultar
el detalle. Detectado por las pruebas del plugin).
Antes: v1.5piloto.74e (fix de
condición de carrera en `solicitar_estado_asientos`: si un
fetch del polling estaba en vuelo cuando se iniciaba una
operación de asiento, el polling pisaba el estado nuevo con
el viejo. El asiento se veía seleccionado y se deseleccionaba
solo. Fix: descartar la respuesta del polling si
`operacion_asiento_en_curso` es true. Detectado por las
pruebas automáticas del plugin). Antes: v1.5piloto.74d (fix de
refresco del croquis tras cancelar venta: al cancelar una venta,
el backend libera los asientos pero el frontend seguía mostrando
el croquis viejo hasta el próximo polling. Ahora se captura el
micro y el viaje abiertos antes de cerrar el modal, y después
del éxito se fuerza un fetch a `viajes/estado_asientos` para
actualizar `estados_asientos_actuales`). Antes: v1.5piloto.74c. Se
terminó de consensuar el diseño del plugin de Chrome: manifest
en la raíz de `iteradoresJS/`, código en `Aplicacion/`,
persistencia con el framework Iteradores JS vía IndexedDB,
pruebas declarativas que el popup lista y dispara. La versión
del plugin arranca en v1.5plugin.0 (prefijo distinto al del
piloto PHP). El asistente ya leyó el framework JS: `Objeto`,
`Nodo`, `Iterador`, `Controlador`, `Entorno`, `Conf`,
`PerdurarSuperestructuraStringIndexedDB` y `Comando`.

**Estado de la conversación:**

- Cerramos la Tanda C (rate limiting y tiempos constantes) en v71.
- Introdujimos la carpeta `prompts/` en v71a.
- Dividimos el prompt de continuidad en framework y piloto en v71b.
- Separamos host y credenciales a `config_servidor.php` en v72.
- Agregamos el rol `soporte` en v73.
- Cerramos en v73j el fix de permisos de sesiones (fuga de datos
  entre roles y falta de validación al cerrar sesión ajena).
- Cerramos en v73k el fix del orden de includes en `index.php`
  y la mudanza de `guardar_ambos` a `FuncionesAuxiliares.php`.
- Cerramos en v73l el fix del guardado SQL (framework 1.5i.7):
  transacción + chunks. Era la causa raíz de las tablas corruptas
  y del riesgo de que el grafo quede vacío.
- Cerramos en v73m el fix del framework 1.5i.7a: chequeos y escape
  en `cargar`/`existe`/`eliminar` de SQL, escritura atómica y
  validación en JSON, `Controlador::cargar` distingue "no existe"
  de "error", y rehash automático de credenciales en login exitoso.
- Cerramos en v73n el fix del framework 1.5i.7b: XML con escritura
  atómica, validación de `<nodos>`, `libxml_clear_errors`, sin
  doble vaciado. `listar()` en JSON y XML chequea `glob()`. ESQL
  queda pendiente para cuando se aborde.
- Cerramos en v73o el script de prueba del depósito de IDs en
  `Pruebas/` y la documentación del espejo JS en el prompt del
  framework.
- Cerramos en v73p la reescritura del test (el anterior daba falso
  positivo) y la alineación de `crear_chunks_insertar_adyacentes`
  con el espejo JS.
- Cerramos en v73q la documentación del espejo JS y del bug
  latente `if ($elemento)` de `Iterador`.
- Cerramos en v73r el fix del bug `if ($elemento)` (PHP y JS) y la
  limpieza completa de migraciones.
- Cerramos en v73t el ajuste de tamaño de letra de la DJ impresa.
- Cerramos en v73v el fix del Bug 2: la ligadura comprador-pasajero
  ya no pisa con vacíos. Se confirmó que la comparación contra todos
  los pasajeros ya estaba bien implementada en `_verificar_atadura_por_dni`.
- Cerramos en v73w el caso restante del Bug 2: cuando el comprador
  tiene datos y el pasajero está vacío, la atadura ahora copia en
  la dirección correcta. La copia inicial es bidireccional con
  prioridad al último escrito por el usuario.
- Cerramos en v73x dos bugs relacionados con la ligadura y la
  corrección de DNI: (1) la atadura ya no se activa cuando el
  pasajero está marcado como duplicado (ya asignado a otro asiento
  o repetido en otro formulario); (2) al corregir el DNI por uno
  no registrado, se limpian los campos del pasajero y del
  comprador que habían sido autocompletados. Se eliminó el botón
  "Usar primer pasajero" (obsoleto desde que la atadura funciona).
- Cerramos en v74 el fix del Bug 1 (opciones de cobro congeladas
  al vender + retroactivo opcional al editar condiciones de pago).
  Sin botón ni submodal en el modal de pago de cupón: la
  decisión de aplicar retroactivo se toma al editar las
  condiciones de pago (del viaje o del override de TerminalViaje).
- Cerramos en v74a el fix del retroactivo: la config nueva se
  resuelve en vivo (viaje + terminal), ignorando el
  `opciones_cobro` que ya tiene la venta. El flujo de migración
  de ventas viejas no estaba afectado. Todas las pruebas del
  Bug 1 pasaron.
- Arrancamos el diseño de un **segundo piloto**: un plugin de
  Chrome (manifest v3) que corre pruebas automatizadas sobre la
  página del piloto PHP. Vive dentro del proyecto `iteradoresJS/`,
  en una nueva carpeta `Aplicacion/`. Usa el framework Iteradores
  JS para persistir su propia info (IndexedDB del contexto de la
  extensión). El botón play del plugin dispara un script que
  escribe el asistente, que actúa sobre la página del piloto.
  Aplica el mismo flujo de trabajo: scripts PHP de aplicación de
  cambios, ejecutados en el directorio del proyecto `iteradoresJS/`,
  bump de versiones y actualización del prompt del plugin.
- El proyecto `iteradoresJS/` tiene su propia carpeta `prompts/`,
  con un único archivo por ahora: `prompt_plugin_piloto.md`. El
  prompt del framework Iteradores y el del sistema de scripts
  siguen viviendo en el proyecto PHP.
- Se leyeron los archivos clave del framework JS: `Objeto`,
  `Nodo`, `Iterador`, `Controlador`, `Entorno`, `Conf`,
  `PerdurarSuperestructuraStringIndexedDB` y `Comando`.
  El framework está más avanzado que el espejo PHP: tiene
  sistema de comandos, motor con péndulo, dominios, reloj
  astronómico, tálamo y señal. El plugin no los usa en su
  primera versión, pero quedan disponibles.
- Cerramos en v74c el diseño del plugin. Decisiones: manifest
  en la raíz de `iteradoresJS/`, código en `Aplicacion/`,
  persistencia IndexedDB, content script clásico, salida en
  modo consola en el service worker, nombre de versión
  `v1.5plugin.0`. La próxima tanda es la creación del
  esqueleto del plugin (manifest + SW + content + popup +
  bootstrap + ConfPlugin + GrafoPlugin + primera prueba
  smoke).
- Pendiente: escribir el código del plugin. El prompt del
  plugin vive en `iteradoresJS/prompts/prompt_plugin_piloto.md`
  y se creó en esta misma tanda.
- Cerramos en v74d el fix del refresco del croquis tras
  cancelar venta. Reportado por las pruebas del plugin:
  los asientos cancelados seguían viéndose como vendidos
  hasta el próximo polling.
- Cerramos en v74e el fix de condición de carrera entre el
  polling de asientos y el clic. También reportado por las
  pruebas del plugin: un asiento recién seleccionado volvía a
  verse libre por el polling en vuelo.
- Cerramos en v74f el fix de "modal del viaje abierto al
  cambiar de pestaña". El croquis quedaba congelado en
  pantalla tras cancelar una venta desde una prueba
  automática. También reportado por las pruebas del plugin.
- Cerramos en v74k los fixes de validación del alta de micro:
  el backend rechaza vehículos sin asientos y duplicados en el
  mismo viaje, el nombre del micro se calcula con `max+1` para
  evitar colisiones cuando se quita uno del medio, y el
  frontend filtra los vehículos sin asientos del select.
  Estos fixes surgieron de la batería de pruebas del plugin
  (v1.5plugin.4y): `micro_mismo_vehiculo_dos_veces` (verifica
  rechazo de duplicados), `micro_vehiculo_sin_asientos`
  (verifica filtro del select) y `micro_colision_numeracion`
  (reproduce el bug de colisión).
- Cerramos en v74j el modo prueba para alertas críticas: el
  helper `_mostrar_alerta_critica()` respeta la bandera
  `window.__iteradores_modo_prueba` que el plugin setea antes
  de los flujos que disparan `alert()`.
- Cerramos en v74p la Fase 1 del plan de optimización del
  grafo: pestaña "Grafo" (solo admin y soporte). Vista de
  solo lectura con totales, alcanzables vs huérfanos, top
  de referencias, tabla filtrable y modal de detalle por
  nodo. Backend: 3 comandos en el `Controlador`
  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`).
  Frontend: `Aplicacion/grafo.js`.
- Cerramos en v74s el segundo flujo de Fase 2:
  `eliminar_micro_de_viaje`. Ahora destruye el micro
  completo (copia de vehículo, pisos, asientos, campos)
  en lugar de solo desenlazarlo del contenedor. Reutiliza
  `_destruir_micro` de `Viaje.php`. Libera ~100 nodos
  por micro.
- Cerramos en v74r el primer flujo de Fase 2: `eliminar_viaje`
  ahora destruye el subárbol completo del viaje, no solo
  desenlaza del contenedor. Nuevos helpers `_destruir_*`
  en `Viaje.php`. Con esto, eliminar un viaje con N micros
  libera ~250 × N nodos. Otros flujos con el mismo patrón
  pendientes: `eliminar_micro_de_viaje` (en `ViajeMicros.php`),
  `eliminar_terminal_autorizada` (en `Viaje.php`),
  `_guardar_paradas_intermedias` (en `Viaje.php`),
  `eliminar_pasajero` (en `Pasajero.php`, a auditar).
- **Decisión anotada como pendiente de prioridad alta:**
  **fuga de nodos**. Cuando se elimina una venta, un
  pasajero, un viaje o cualquier entidad, el nodo se
  descuelga del contenedor pero no se destruye. Como el
  framework no permite eliminar un nodo con referencias
  entrantes, esos nodos quedan huérfanos y el grafo crece
  indefinidamente. Medición: grafo de 10.000 nodos → 50-70s
  por prueba; grafo de 2.000 nodos → 15-18s por prueba.
  La pestaña Grafo es la herramienta de diagnóstico para la
  Fase 2 (auditoría de la fuga). Ver §8.6.
- **Plan de optimización del grafo (3 fases):** ver §8.6.
  Fase 1 implementada en v74p (pestaña Grafo). Fase 2
  pendiente (prioridad alta): auditoría de la fuga por
  flujo de eliminación + implementación de fixes + criterios
  en §8.6.1. Fase 3 pendiente (prioridad media): iteradores
  persistentes, cacheo de contadores, eventual carga parcial
  del grafo (esta última requiere tocar el framework).
- **Frente de framework (no se toca ahora):** el framework
  no puede cargar/guardar partes reducidas del grafo. Un
  nodo puede estar referenciado desde más de un lado, así
  que no hay un árbol natural de pertenencia. Requiere
  revisar teoría de grafos. Se retoma en una sesión del
  framework.
- Cerramos en v74o el botón "Limpiar pasajeros de prueba"
  para el admin (solo en modo pruebas). Criterio: email
  termina en `@test.local`. Conserva los que tienen
  referencias entrantes. También: `index.php` establece
  `Entorno::MODO_PRUEBAS`/`MODO_PRODUCCION` según
  `Conf::LOCAL`, e inyecta `window.entorno_es_pruebas` en
  el HTML. Los dos botones de limpieza (viajes y pasajeros)
  aparecen solo si el admin está en modo pruebas.
- Cerramos en v74n el botón "Limpiar viajes de prueba" para
  el admin. El grafo del dueño `carmen1` tenía 21 viajes
  (20 de ellos de pruebas anteriores), y `formatear_viaje`
  escala con V × W (viajes × ventas). El botón borra los
  viajes de prueba conservando el viaje principal
  "Peregrinación a la Visita del Papa León XIV a Luján" y
  los que no tengan prefijo de prueba.
- Cerramos en v74m el fix de performance: `guardar_ambos` ya
  no guarda el JSON de respaldo automático. El `json_encode`
  de todo el grafo se volvió el cuello de botella cuando
  creció (3 terminales, varios vehículos, ventas). El
  respaldo JSON pasa a ser acción manual del admin (a
  implementar en el rediseño del panel).
- Cerramos en v74h la tanda chica de cierre: fix del autocompletado
  por DNI para terminal sin viaje seleccionado, bump de `?v=` de
  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones
  en este prompt (rehash, migraciones, botones, autocompletado,
  `GuardarAmbos.php`, `migrar_pasajeros.php`).
- Cerramos en v74q la documentación de cierre: se agregaron
  dos pendientes nuevos al backlog (§8.5): cerrar todos los
  modales al cerrar sesión (prioridad media) y convertir en
  modal la carga de empresas y micros (prioridad media).
  También se documentó la limitación del framework en su
  propio prompt (sección 11 nueva).
- No hay tandas de código en curso en este proyecto.

**Decisiones de diseño tomadas y en vigor:**

- **SQL es siempre el método principal.** El JSON es solo respaldo.
  `Conf::LOCAL` ya no decide el método de persistencia.
- **El respaldo JSON automático se eliminó en v74m.**
  `guardar_ambos` solo guarda SQL. Motivo: el `json_encode`
  de todo el grafo se volvió el cuello de botella del
  guardado cuando el grafo creció (varias terminales,
  vehículos, ventas). El respaldo en formatos alternativos
  (JSON, XML) pasa a ser una acción manual del admin, a
  implementar en el rediseño del panel admin.
- **Host y credenciales viven en `config_servidor.php`** en la raíz del
  proyecto. Es el único archivo que no se toca al desplegar. `Conf`
  hereda de `ConfServidor`.
- **El rol `soporte`** asiste a uno o varios dueños. Ve las mismas
  pestañas que el admin, pero solo sobre sus dueños asignados. Solo
  puede crear/editar/eliminar terminales de esos dueños y editar al
  propio dueño. No puede cambiar el nivel de un usuario. El admin es
  "soporte universal".
- **Los prompts viven en el proyecto**, en `prompts/`. Hay tres:
  framework, piloto, sistema de scripts.
- **Los logs de errores usan `Controlador::_error()`**, no archivos de
  log.
- **La sección "Discusión actual"** de este prompt es lo primero que se
  actualiza al cerrar una tanda.

**Bugs conocidos (prioridad alta):**

- **Bug de cupones: resuelto en v74.** Ver sección 8.3, "Bug 1".

- **Bug de ligadura comprador-pasajero: resuelto en v73v.** Ver
  sección 8.3, "Bug 2".

**Decisiones abiertas / temas pendientes sin consensuar:**

- **Diversificación por tipo de aplicación**: próximo gran frente. Ya
  hay un diseño inicial consensuado (nodo `tipos_de_aplicacion`, enlace
  `tipo_app` en el dueño). Falta ver el código antes de arrancar.

**Aprendizajes a la fuerza del piloto:**

1. **Los datos del framework viajan como strings.** Números también.
   Al comparar, castear. Ejemplo: `"2" !== 2` en JS.
2. **PHP convierte claves de array string numéricas a int.** Forzar
   `(string)$clave` en los foreach que iteran sobre DNI o patentes.
3. **Los IDs sin ID especial cambian entre cargas.** Si un nodo debe
   sobrevivir a guardar/cargar y ser referenciado, usar ID especial.
4. **La prueba del depósito de IDs dio un falso positivo** la primera
   vez. Moraleja: diseñar el test para que no dependa del estado
   intermedio que el propio `cargar` reconstruye.
5. **Nunca confiar en datos que el cliente manda** cuando hay una
   fuente de verdad del lado del servidor. Bug de
   `dueno/listar_sesiones_terminales` (fix en v73j): el cliente
   mandaba la lista de terminales y el backend la usaba sin validar.
6. **`sesiones/cerrar` debe validar el ámbito del solicitante.** El
   fix v73j agregó `_puede_cerrar_sesion`. Regla: cualquier acción
   sobre recursos de otros usuarios pasa por un chequeo explícito.
7. **El guardado SQL debe ser transaccional y por chunks.** El fix
   v73l usó `begin_transaction` + DELETE + INSERT por chunks de
   ~200 KB + `commit`. Antes, un grafo grande crasheaba MySQL.
8. **El guardado nunca debe pisar el grafo con vacío.**
   `guardar_ambos` aborta si `Nodo::hay_nodos_en_superestructura()`
   devuelve false.
9. **Los includes importan.** `guardar_ambos` debe estar disponible
   antes de usarse. Bug v73k: fatal error por orden de includes.
10. **Los tests con callbacks async deben esperar el callback.**
    `Controlador::ejecutar_prueba` en JS ahora es async y espera.
11. **`if ($elemento)` descarta falsy** (`0`, `""`, `false`). Usar
    `!== null` cuando el valor puede ser falsy legítimamente.
12. **En PHP, `private` de una clase base NO es accesible desde una
    subclase.** Pero `private static` sí es accesible desde dentro
    de la misma clase, aunque sea por un método público.
13. **En JS, `#privado` es más estricto que `private` de PHP.**
    Nunca accesible desde una subclase, ni siquiera con trucos.
    Regla: exponer un método público para operar sobre el campo.
14. **PHP y JS deben ir espejados.** Cualquier cambio al framework
    PHP se refleja en JS, con dos `aplicar_cambios.php` y dos
    commits distintos.

**Preguntas abiertas para el usuario:**

- Ninguna. La conversación quedó en un punto de pausa limpio.

---

## 13. CIERRE

Este prompt es autocontenido sobre el piloto. Con esta información más
el `prompt_framework_iteradores.md` y el `prompt_sistema_scripts.md`
podés retomar el trabajo.

**Recordá:**

- No escribir código de una. Consensuar el plan primero.
- Pedir los archivos actuales.
- Entregar un `aplicar_cambios.php` completo.
- Bumpear versiones (los CSS no tienen `@version`).
- Documentar cada cambio.
- **Actualizar este prompt al cerrar cada tanda, incluyendo la sección
  "Discusión actual".**
- Avisar de riesgos.

**Estado del proyecto al cierre:** v1.5piloto.74s (framework 1.5i.7g).
Todo funcional. Fixes de v74k a v74o acumulados. Fix de
v74p: pestaña "Grafo" (Fase 1 del plan de optimización).
v74r: `eliminar_viaje` destruye el subárbol completo
(Fase 2, primer flujo). v74s: `eliminar_micro_de_viaje`
destruye el micro completo (Fase 2, segundo flujo).
El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5g)
tiene 31 pruebas corriendo.

**Plan de optimización del grafo (ver §8.6):**

- **Fase 1 — Pestaña Grafo.** Implementada en v74p.
- **Fase 2 — Auditoría de la fuga de nodos.** Pendiente,
  prioridad alta. Recorrer cada flujo de eliminación (viaje,
  cliente, terminal, micro, venta, cancelación, cupón) y
  anotar qué nodos quedan huérfanos. Implementar los fixes.
  Documentar criterios en §8.6.1.
- **Fase 3 — Optimizaciones.** Pendiente, prioridad media.
  Iteradores persistentes, cacheo de contadores
  (`formatear_viaje`), eventual carga parcial del grafo.

**Mediciones concretas de la fuga:**

- Grafo de ~10.000 nodos → ~50-70s por prueba del plugin.
- Grafo de ~2.000 nodos → ~15-18s por prueba.

**Deuda técnica de framework (prioridad alta, no se toca
ahora):** el framework no puede cargar/guardar partes
reducidas del grafo. Un nodo puede estar referenciado desde
más de un lado, así que no hay un árbol natural de
pertenencia. Se retoma en una sesión del framework.

---

**FIN DEL PROMPT**