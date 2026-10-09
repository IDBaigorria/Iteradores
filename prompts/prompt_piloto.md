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

**Originalmente de solo lectura; desde v76g incluye una
acción de escritura.** El botón "Eliminar nodos basura"
borra todos los nodos no alcanzables desde las raíces
(IDs especiales) del grafo de la aplicación. **No está
restringido a modo pruebas**: se usa en producción, donde
más se acumulan los huérfanos. El chequeo es admin/soporte,
heredado del módulo `grafo` del enrutador. Antes de
eliminar, muestra un modal de confirmación con la cuenta
total y una vista previa de los primeros 20 nodos
huérfanos. El backend usa el comando
`grafo:eliminar_huerfanos`, que desenlaza las salientes
entre huérfanos y después los elimina uno a uno.

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
  La planilla acepta un 4to parámetro `$vacia` (desde v76h);
  si es true, imprime la estructura sin los datos del
  pasajero (útil para completar a mano).
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
- `grafo/resumen_credenciales`: **solo en modo pruebas**, admin o
  soporte. Devuelve el mismo resumen que `grafo/resumen` pero
  sobre el grafo de credenciales. Usa
  `en_grafo_credenciales_solo_lectura` (no guarda). Lo consume
  el plugin en la prueba de rate limiting.
- Módulo `grafo` (admin y soporte): `grafo/resumen`,
  `grafo/listar`, `grafo/nodo`, `grafo/eliminar_huerfanos`.
  Los primeros tres invocan comandos del `Controlador`
  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`). El
  cuarto (agregado en v76g) invoca el comando
  `grafo:eliminar_huerfanos` y guarda el grafo con
  `guardar_ambos`. **No lleva chequeo de `es_pruebas`:
  se usa en producción**, por decisión consciente (la
  limpieza es mantenimiento, no prueba). El control es
  admin/soporte. Ver §3.4.

### 5.15 `index.php`

- Carga framework, persistencia (SQL principal), módulos de
  `Aplicacion/`.
- Requiere `Aplicacion/FuncionesAuxiliares.php` (que define
  `guardar_ambos`) antes que todo lo demás.
- Crea admin en ambos grafos si no existe.
- Crea el nodo especial `aplicacion` con su contenedor
  `migraciones` si no existe (v76n).
- Registra los comandos de la app con
  `registrar_comandos_migraciones()` (v76n). El Controlador
  ya está inicializado cuando se cargan los `require_once`
  de la app, así que el registro es directo con
  `Controlador::registrar_comando(...)`.
- Bloques temporales de migración.
- Enrutado POST con `enrutar_peticion_post`.

### 5.16 Comandos de la app

Los comandos de la app viven en `Aplicacion/Migraciones/`
(v76n) y se registran al vuelo desde `index.php` con
`Controlador::registrar_comando(...)`, no desde el
`Controlador` del framework.

**Regla de convivencia:**

- Comandos del framework (`grafo:*`, `comunicacion:*`,
  `dominio:*`, etc.) → van en `Controlador.php`.
- Comandos de la app (`app:migracion_*`, y en el futuro
  los que correspondan a módulos de negocio) → van en
  archivos bajo `Aplicacion/`, registrados desde
  `index.php` después de los `require_once`.
- Los comandos específicos del piloto que hoy están en el
  `Controlador` del framework (por ejemplo
  `grafo:crear_niveles_usuario`) son deuda técnica: hay
  que moverlos a la app cuando se pueda.

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
  Con `&vacia=1`, la planilla sale sin los datos del
  pasajero (solo números de asiento y columnas en blanco).
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
- **v76**: Fase 2, flujos 15 a 19 arreglados.
  `eliminar_pasajero` y `limpiar_pasajeros_de_prueba`
  destruyen el subárbol completo del pasajero (campos
  personales, fecha_ultima_modificacion, y la
  declaración jurada adjunta con sus 4 sub-campos).
  `subir_declaracion_jurada_pasajero` y
  `eliminar_declaracion_jurada_pasajero` destruyen el
  nodo DJ con sus sub-hijos. `actualizar_pasajero`
  destruye las hojas al limpiar un campo. Helpers
  nuevos: `_destruir_declaracion_jurada_pasajero`,
  `_destruir_pasajero_completo`.
- **v76s**: Fase B2.2.2 del modelo topológico (Venta.php).
  Refactor sin cambio de comportamiento:
  `obtener_contenedor_ventas_dueno` acepta un `?Nodo $nodo_contexto`
  opcional. `_buscar_venta_por_id` acepta un `?string $nombre_terminal`
  opcional; si viene, restringe la búsqueda a su contexto.
  `obtener_venta_por_id`, `pagar_cupon_venta`, `cancelar_venta` y
  `obtener_info_cancelacion` heredan el filtro. `listar_ventas_por_terminal`
  y `confirmar_venta_actual` navegan por el contexto del
  terminal. `_construir_indice_ventas_por_viaje` (en Viaje.php)
  acepta contexto, y `listar_viajes_de_terminal` le pasa el nodo
  del dueño. El enrutador todavía no pasa el `$nombre_terminal`
  a las funciones de búsqueda, así que el comportamiento es
  idéntico al actual. La preparación para B2.3 (repuntado)
  queda completa.
- **v76r**: Fase B2.2.1 del modelo topológico. Refactor
  sin cambio de comportamiento: `obtener_contenedor_viajes_dueno`
  acepta un `?Nodo $nodo_contexto` opcional. Si viene, navega
  desde ahí en lugar de resolver `usuarios → dueño` otra vez.
  `listar_viajes_de_terminal` le pasa `$nodo_dueno` como
  contexto. Nuevo helper `_contexto_terminal($nombre_terminal)`
  en `Viaje.php`: devuelve el nodo desde el que un terminal
  debe navegar (hoy el nodo dueño, tras B2.3 el compartido).
  El comportamiento es idéntico al actual; el cambio prepara
  el terreno para B2.3.
- **v76q**: Fase B2.1 del modelo topológico. Crea los
  contenedores `compartido_con_us_termX` en cada dueño,
  uno por terminal autorizado en algún viaje del dueño.
  Estructura: `viajes` (solo los autorizados), `empresas`
  (referenciadas por esos viajes), `pasajeros` (alias al
  contenedor del dueño), `ventas` (del terminal),
  `cancelaciones` (del terminal) y `terminales` (solo el
  propio). **No repunta nada**: los compartidos coexisten
  con la estructura actual. La migración es idempotente.
  Nuevo comando `app:crear_compartidos_terminal` en
  `Aplicacion/Migraciones/Comandos.php` (no en el
  `Controlador` del framework, siguiendo la regla de
  v76p). Función `detectar_compartidos_terminal` y
  `aplicar_migracion_compartidos_terminal` en
  `Funciones.php`; entrada `compartidos_terminal` en
  `Registro.php`. Bloque `?migrar_compartidos_terminal=1`
  en `index.php`. Plan completo de B2 (B2.1, B2.2, B2.3,
  B2.4) documentado en §8.7.
- **v76p**: solo documentación. Se documenta todo el
  aprendizaje sobre el sistema de comandos del framework:
  nueva sección §4.4 en el prompt del framework (registro,
  ejecución, argumentos, reversa, cuándo se registran,
  `RegistroGlobal` para autoencolación, espejo JS). Cuatro
  lecciones nuevas en §13 (autoinicialización del
  Controlador, comandos del framework vs de la app,
  `RegistroGlobal` solo para archivos previos al
  Controlador, y la limitación de MV3 con `import()`
  dinámico). Nueva §5.16 en el prompt del piloto con la
  regla de convivencia entre comandos del framework y de
  la app.
- **v76o**: solo documentación. Se dejan asentados tres
  pendientes sobre el framework, sin tocar código:
  (1) espejar `grafo:reemplazar_referencias` en el
  `Controlador.js`;
  (2) mover `grafo:crear_niveles_usuario` fuera del
  `Controlador` del framework (es específico del piloto,
  debe levantarse al vuelo desde la app);
  (3) `grafo:raices` no se pudo verificar desde la consola
  del SW del plugin por limitación de MV3 (`import()`
  dinámico prohibido). La verificación se hizo desde el
  piloto PHP. Si se quiere verificar desde el plugin,
  exponer `globalThis.Controlador = Controlador` en el
  bootstrap del SW.
- **v76n**: pestaña Grafo ampliada con dos secciones nuevas
  y sistema de migraciones.
  - Comando `grafo:raices` en el framework: lista los IDs
    especiales del grafo con sus adyacentes directos.
  - Nodo especial `aplicacion` con contenedor `migraciones`.
    Cada migración aplicada deja un enlace testigo
    autoreferente (`migraciones/<id>` → `migraciones`).
    Idempotente y auto-detectable.
  - Módulo `Aplicacion/Migraciones/` con tres archivos:
    `Registro.php` (array de migraciones),
    `Funciones.php` (detección y aplicación),
    `Comandos.php` (registro de `app:migracion_*`).
  - Comandos `app:migracion_listar`, `app:migracion_aplicar`
    y `app:migracion_marcar` registrados directo (no
    encolados) porque el Controlador ya está inicializado
    cuando se cargan los `require_once` de la app.
  - Subacciones `grafo/raices`, `grafo/migraciones_listar`
    y `grafo/migraciones_aplicar` en el Enrutador.
  - Sección "Nodos raíz" y "Migraciones" en la pestaña
    Grafo. La segunda permite aplicar migraciones desde
    la UI, con confirmación previa.
  - **Excepción a la regla del plugin de pruebas** (lección
    14 del prompt del sistema de scripts): este cambio NO
    lleva pruebas del plugin. Es herramienta de admin, no
    flujo de negocio, y se usa a mano todo el tiempo.
- **v76m**: Fase B1 del modelo topológico. Crea los
  contenedores `publico` y `privado` como hijos de cada
  nodo usuario, enlazándolos con los nodos que ya existían
  (alias). `publico` tiene como dato el nombre de usuario
  y enlaza a `nivel`, `nombre_real` y `email`. `privado`
  enlaza al resto de los datos del usuario. Los enlaces
  "de permiso" (`dueno`, `soporte`, `duenos`) quedan en
  la raíz. **Los enlaces viejos NO se eliminan**, así el
  código actual sigue funcionando sin cambios. Nuevo
  comando `grafo:crear_niveles_usuario` en el `Controlador`
  (idempotente). Script `miscelaneas/migrar_niveles_usuario.php`
  y bloque `?migrar_niveles_usuario=1` en `index.php`
  (opcional `&usuario=carmen1`). Solo grafo de la app: el
  de credenciales queda plano por ahora. Fix posterior:
  si el `&usuario=X` no existe, el comando devuelve error
  y no migra nada (antes devolvía `migrados: 0` sin avisar).
- **v76l**: diseño del modelo topológico por niveles de
  exposición. Cada usuario va a tener contenedores
  `publico`, `privado` y `compartido_con_X` colgando de
  su nodo raíz, con los datos distribuidos según quién
  debe verlos. La seguridad emerge de la topología: si
  un usuario no tiene un enlace al `privado` de otro,
  no puede alcanzarlo. Los enlaces "de permiso"
  (`dueno`, `soporte`) van en la raíz. Admin y soporte
  no usan este modelo: siguen accediendo por código
  (con `_verificar_permiso_dueno`). Diseño completo en
  el PHPDoc de `aplicacion_POST.php` (sección "Diseño
  propuesto: contenedores por nivel de exposición").
  Plan por fases (B1/B2/B3) en §8.7. Solo documentación:
  no hay cambios de código todavía.
- **v76k**: Fase A de contextos del piloto. Todos los
  usuarios pasan a ser IDs especiales `us_<nombre>`. Los
  enlaces desde `usuarios` siguen llamándose `<nombre>`
  (nombre visible), así los accesos por `adyacente()` no
  cambian. `agregar_usuario`, `actualizar_usuario` (rama
  de credenciales) y la creación del admin en `index.php`
  (ambos grafos) ahora usan `crear_con_dato_e_id`. Nuevo
  comando genérico `grafo:reemplazar_referencias` en el
  `Controlador` (recibe un mapa `{viejo → nuevo}` y
  redirige todas las aristas del grafo que apunten a un
  viejo). Nuevo script `miscelaneas/migrar_usuarios_especiales.php`
  (idempotente) y bloque `?migrar_usuarios_especiales=1`
  en `index.php`. Sin cambios en los accesos, sin carga
  parcial todavía.
- **v76j**: cierre de la fase 2 del framework
  (contextos). El framework PHP llegó a 1.5i.7k con
  `PerdurarSuperestructuraStringSQL64` (3 tablas
  nuevas, BFS multi-fuente, `cargar_parcial`); el
  espejo JS llegó a la misma versión con
  `PerdurarSuperestructuraStringIndexedDB64` (base
  de datos separada `HyS_ctx`). Se agregó la
  interfaz `PerdurarSuperestructuraConContexto` y
  los métodos `cargar_parcial`, `guardar_parcial`
  (stub), `listar_contextos` y `es_grafo_parcial`
  en ambos `Controlador`. Fix del bug de tipos de
  ID en el `Map` de `_superestructura` (JS):
  normalización a `String(id)` de las claves y de
  `Nodo.existe` / `Nodo.nodo_por_id`. Deuda de
  diseño anotada: dejar el Nodo limpio antes de
  agregar más métodos de indexación de contextos.
  Ver §11.4 del prompt del framework. Solo
  documentación del piloto + bump de `index.php`.
- **v76i**: solo documentación. Se agrega la sección
  §8.7 con el plan de contextos del piloto (dueños
  y tipos como IDs especiales, integración con el
  plan del framework §11.4). No hay cambios de código.
- **v76h**: nuevo botón "Imprimir Planilla Vacía" en el
  croquis del micro. `imprimir_planilla_pasajeros_micro`
  recibe un 4to parámetro `$vacia = false`; cuando es
  true, imprime los mismos encabezados, los números de
  asiento y las columnas, pero sin los datos del
  pasajero (útil para completar a mano). `index.php`
  lee `$_GET['vacia']` y lo pasa. Solo PHP.
- **v76g**: eliminación de nodos huérfanos desde la pestaña
  Grafo. Nuevo comando `grafo:eliminar_huerfanos` en el
  framework PHP (1.5i.7i) y JS (espejado en la misma
  tanda), nueva subacción `grafo/eliminar_huerfanos` en el
  enrutador (admin/soporte, **sin chequeo de
  `es_pruebas`**: se usa en producción, donde más se
  acumulan los huérfanos — decisión consciente). Nuevo
  botón "Eliminar nodos basura" con modal de
  confirmación (cuenta total + vista previa de los
  primeros 20). Nuevo helper
  `en_grafo_credenciales_solo_lectura` (v76f) reutilizado
  en la prueba del plugin. Nueva prueba 56
  (`eliminar_huerfanos_limpia_grafo`) en el plugin.
- **v76f**: nuevo helper `en_grafo_credenciales_solo_lectura`
  (lee el grafo de credenciales sin guardarlo) y nueva
  subacción `grafo/resumen_credenciales` en el enrutador
  (solo en modo pruebas, admin o soporte). Permite al plugin
  medir huérfanos del grafo de credenciales.
- **v76d**: solo documentación. Se escribe completa la
  sección §8.6.1 "Criterios de eliminación" con el
  criterio concreto por entidad, extraído de los 19
  flujos arreglados en Fase 2 (v74r-v75a) + el cierre
  del Grupo B (v75a). Incluye reglas generales,
  anti-patrones, criterio por entidad y referencia a
  los helpers `_destruir_*` correspondientes.
- **v76c**: cierre del pendiente "convertir en modal la
  carga de empresas y micros" + eliminación de código
  muerto. `micros.js`: el alta de empresa y de vehículo
  pasan a modal genérico. `aplicacion_GET.html`: se
  eliminan los formularios embebidos
  (`#formulario_nuevo_usuario`, `#formulario_nueva_terminal`,
  `#formulario_nueva_empresa`, `#formulario_editar_empresa`,
  `#formulario_nuevo_vehiculo`) y 3 botones huérfanos sin
  listener (`#boton_editar_empresa_micros`,
  `#boton_eliminar_empresa_micros`,
  `#boton_eliminar_vehiculo_micros`). Se elimina también
  `__index.html`, backup viejo de la interfaz.
- **v76b**: cierre del pendiente "cerrar todos los
  modales al cerrar sesión". `_limpiar_contenido_dinamico`
  ahora cierra el modal genérico (sin disparar el hook
  `window.on_cerrar_modal_generico`, que ya no tiene
  sentido al salir), el apilado, el `#opciones_impresion`
  y todos los `.modal-chico` flotantes. Cubre tanto el
  logout manual como el cambio de sesión sin recargar.
- **v76a**: Fase 3 del plan de optimización del grafo.
  `formatear_viaje` recibe un 4to parámetro opcional
  (`$indice_ventas`). `listar_viajes_de_dueno` y
  `listar_viajes_de_terminal` construyen un índice de
  ventas por viaje UNA SOLA VEZ antes del bucle de
  viajes. Helper nuevo
  `_construir_indice_ventas_por_viaje` y
  `_calcular_vendidos_por_micro_de_viaje` (extraído del
  inline anterior). Baja el costo de `listar_viajes_*`
  de O(V × W) a O(V + W).
- **v75a**: Fase 2, cierre de los "campos huérfanos" del
  Grupo B. Cinco fixes chicos: hojas que se desenlazaban
  sin destruirlas. `Autenticacion.php`: `bloqueado_hasta`
  al expirar el bloqueo y al registrar login exitoso.
  `Venta.php`: `metodo_pago` del cupón al coincidir con el
  de la venta. `Vehiculo.php`: `foto` al reemplazarla.
  `Viaje.php`: `hora_estimada` al quitar la hora de una
  parada. El `punto_subida_bajada` del TerminalViaje
  (Viaje.php:940) queda descartado: es una referencia
  externa al nodo parada del viaje, correcto por diseño.
- **v75**: Fase 2, flujos 13 y 14 arreglados.
  `eliminar_usuario` ahora destruye los campos del nodo
  usuario (nivel, nombre_real, email, efectivo, banco con
  sus hijos), el nodo credencial con sus campos
  (codigo_hash, contrasena, intentos_fallidos,
  bloqueado_hasta, ultimo_acceso, ip_ultimo_acceso), y las
  sesiones activas del usuario. `actualizar_usuario`
  destruye los campos al cambiar de nivel (efectivo,
  banco), al limpiar el banco del dueño (nombre, cuenta),
  y al resetear el bloqueo (bloqueado_hasta).
  `eliminar_sesiones_de_usuario` y `cerrar_sesion` de
  `Sesion.php` ahora destruyen los campos del nodo sesión.
  Nuevo helper `_destruir_banco_usuario` en `Usuario.php`.
- **v74z**: Fase 2, duodécimo flujo arreglado:
  `limpiar_viajes_de_prueba`. Antes desenlazaba los
  viajes de prueba del contenedor del dueño sin
  destruirlos, dejando el mismo subárbol huérfano que
  `eliminar_viaje` antes de v74r (~250 nodos por
  micro). Ahora llama a `_destruir_viaje_completo`
  antes de desenlazarlos. Cierra la deuda de que la
  herramienta de limpieza era en sí misma una fuente
  de fuga. Detectado por el detector ampliado de fugas.
- **v74y**: Fase 2, noveno, décimo y undécimo flujo
  arreglados. `actualizar_configuracion_vehiculo` ahora
  destruye los pisos viejos antes de reemplazarlos.
  `eliminar_vehiculo` destruye el vehículo completo.
  `eliminar_empresa` destruye todos sus vehículos y la
  empresa. Se mueven `_destruir_lista_circular_asientos`,
  `_destruir_piso` y `_destruir_copia_vehiculo` (renombrada
  a `_destruir_vehiculo_completo`) de `Viaje.php` a
  `FuncionesAuxiliares.php`, para que `Vehiculo.php` y
  `Empresa.php` las puedan usar sin depender de
  `Viaje.php`. Detectados por el detector ampliado de
  fugas (`miscelaneas/detectar_fugas_eliminar.php`).
- **v74x**: Fase 2, séptimo y octavo flujo arreglados:
  `seleccionar_asiento_micro` (destruye los asientos-en-venta
  viejos al cambiar de micro a mitad de selección, cuando
  `limpiar_lista = true`) y `deseleccionar_asiento_micro`
  (destruye el asiento-en-venta del asiento que se
  deselecciona, que antes quedaba huérfano al filtrarse
  fuera de la lista). Nuevo helper
  `_destruir_asiento_en_venta` en `Venta.php`, reutilizado
  por `_destruir_venta_actual` y por los dos flujos de
  `ViajeAsientos.php`.
- **v74w**: Fase 2, sexto flujo arreglado:
  `confirmar_venta_actual`. Antes desenlazaba la venta
  actual de la terminal pero no destruía su subárbol,
  dejando ~5 nodos huérfanos por venta confirmada (nodo
  venta_actual, cabeza de asientos-en-venta, cada
  asiento-en-venta, y los campos viaje y micro). Nuevo
  helper `_destruir_venta_actual` en `Venta.php`. Bug
  detectado por la prueba espejo
  `cancelar_venta_limpia_nodos` del plugin: crear la
  venta incrementaba el contador de huérfanos en 5.
- **v74v**: Fase 2, quinto flujo arreglado:
  `cancelar_venta`. Antes dejaba huérfanos los asientos-
  en-venta (el `eliminar_hmi` no aplicaba a la lista
  simple), los campos de cada cupón, los campos del nodo
  venta y el sub-nodo `opciones_cobro` completo (~20-36
  nodos por venta cancelada). Ahora los destruye.
  `_destruir_campos_simples` se mueve de `Viaje.php` a
  `FuncionesAuxiliares.php` para que `Venta.php` la use
  sin depender de `Viaje.php`.
- **v74u**: Fase 2, tercer y cuarto flujo arreglados.
  `eliminar_terminal_autorizada` ahora destruye el
  TerminalViaje (con sus campos) en lugar de dejarlo
  huérfano. `_guardar_paradas_intermedias` ahora destruye
  las paradas viejas que no se reutilizan al editar el
  viaje. Ambos reutilizan helpers `_destruir_*` de v74r.
- **v74t**: fix del orden de destrucción en los helpers
  `_destruir_*`. Los helpers no desenlazaban al hijo del
  padre antes de destruirlo, lo que dejaba 2 nodos
  huérfanos por micro (las cabezas de las listas
  circulares de asientos de cada piso). Corregido el
  orden en `_destruir_lista_circular_asientos`,
  `_destruir_copia_vehiculo` y `_destruir_micro`:
  siempre desenlazar antes de destruir. También se
  desenlaza el `siguiente` de TODOS los asientos, no
  solo el del último.
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

**Bug de UX pendiente (prioridad media):**

- **DJ del viaje en el modal de edición.** Reportado durante
  v76h. Con el rol dueño: se abre el modal de edición de la
  DJ del viaje, se edita, se cancela. Al volver a tocar el
  botón de editar, el modal abre pero queda bloqueado: no
  deja editar. Se sospecha que algún estado interno
  (contenteditable, listener, flag global) queda
  inconsistente después del primer ciclo. Requiere revisar
  `Aplicacion/Viajes/viajes-nucleo.js` y el HTML del modal
  de DJ. No está cubierto por pruebas del plugin.

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
- ~~**Cerrar todos los modales al cerrar sesión**~~.
  **Resuelto en v76b.** `_limpiar_contenido_dinamico` cierra
  el modal genérico (con cierre directo, sin ejecutar el hook
  `window.on_cerrar_modal_generico`), el apilado, el
  `#opciones_impresion` y todos los `.modal-chico` flotantes.
  Cubre el logout manual y el cambio de sesión sin recargar
  (que también llama a `_limpiar_contenido_dinamico` desde
  `_aplicar_login_exitoso`).
- ~~**Convertir en modal la carga de nuevas empresas y micros**~~.
  **Resuelto en v76c.** El alta de empresa y de vehículo
  pasan a modal genérico. Se eliminaron los formularios
  embebidos de `aplicacion_GET.html`:
  `#formulario_nueva_empresa`, `#formulario_nuevo_vehiculo`,
  y también los ya muertos desde v73g
  (`#formulario_nuevo_usuario`, `#formulario_nueva_terminal`),
  más `#formulario_editar_empresa` (muerto en JS).
  Se eliminaron también 3 botones huérfanos sin listener
  (`#boton_editar_empresa_micros`,
  `#boton_eliminar_empresa_micros`,
  `#boton_eliminar_vehiculo_micros`) y `__index.html`
  (backup viejo de la interfaz).

**Herramientas de diagnóstico disponibles:**

- `miscelaneas/detectar_adyacente_en_true.php`: encuentra
  usos de `_adyacente_en(..., true)`. Los 3 del piloto son
  correctos (ver §8.6).
- `miscelaneas/detectar_fugas_eliminar.php`: encuentra
  `eliminar_adyacente` / `eliminar_hmi` / `eliminar_hd`
  sin destrucción posterior en las 10 líneas siguientes.
  Reporta candidatos clasificados en "PROBABLE FUGA"
  (eliminar_hmi/hd sin retorno usado) y "REVISAR".
  Sirve para priorizar flujos nuevos de Fase 2.
- `miscelaneas/detectar_botones_sin_listener.php`:
  encuentra IDs de elementos en HTML sin referencia
  literal en ningún JS (getElementById, querySelector
  #id, $("#id"), [id="id"]). Útil para detectar código
  muerto del frontend. Limitaciones: no detecta IDs
  construidos dinámicamente ni referencias vía dataset
  o getAttribute. Excluye los que tienen onclick inline.

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

**Sexto flujo arreglado en v74w:** `confirmar_venta_actual`.
Antes desenlazaba la venta_actual de la terminal pero no
la destruía: quedaban huérfanos el propio nodo, la cabeza
de la lista de asientos-en-venta, cada asiento-en-venta
creado durante la selección, y los campos `viaje` y
`micro` (~5 nodos por venta). Nuevo helper
`_destruir_venta_actual`. Bug detectado por la prueba
`cancelar_venta_limpia_nodos` del plugin, que al crear
la venta vio un incremento de huérfanos de 5.

**Decimoquinto a decimonoveno flujos arreglados en v76:**
`eliminar_pasajero` y `limpiar_pasajeros_de_prueba`
(destruyen el subárbol del pasajero: campos personales,
fecha de última modificación, y la declaración jurada
adjunta con sus 4 sub-campos),
`subir_declaracion_jurada_pasajero` (destruye la DJ
previa al reemplazarla),
`eliminar_declaracion_jurada_pasajero` (destruye la DJ),
y `actualizar_pasajero` (destruye las hojas al limpiar
un campo). Helpers nuevos:
`_destruir_declaracion_jurada_pasajero`,
`_destruir_pasajero_completo`.

**Cierre de campos huérfanos (Grupo B) en v75a:**
cinco fixes chicos, hojas que se desenlazaban sin
destruirlas:

- `Autenticacion.php` (`_esta_bloqueado` y
  `_registrar_login_exitoso`): `bloqueado_hasta`.
- `Venta.php` (`pagar_cupon_venta`): `metodo_pago` del
  cupón, cuando coincide con el de la venta.
- `Vehiculo.php` (`subir_foto_vehiculo`): `foto`, al
  reemplazar la foto anterior.
- `Viaje.php` (`_guardar_paradas_intermedias`):
  `hora_estimada`, al quitar la hora de una parada.

El `punto_subida_bajada` del TerminalViaje
(`Viaje.php:940`) queda **descartado**: es una referencia
externa al nodo parada del viaje, que sigue vivo en
`paradas_intermedias`. Correcto por diseño.

**Decimotercer y decimocuarto flujo arreglados en v75:**
`eliminar_usuario` (destruye los campos del nodo usuario,
el banco con sus hijos, el nodo credencial con sus campos,
y las sesiones activas) y `actualizar_usuario` (destruye
efectivo, banco, y las hojas de banco y bloqueo_hasta en
los flujos de cambio de nivel y limpieza). También se
arreglan `eliminar_sesiones_de_usuario` y `cerrar_sesion`
en `Sesion.php` (destruían el nodo sesión sin sus campos).
Nuevo helper `_destruir_banco_usuario`.

**Duodécimo flujo arreglado en v74z:**
`limpiar_viajes_de_prueba`. Antes desenlazaba los viajes
de prueba del contenedor del dueño sin destruirlos:
quedaba el mismo subárbol huérfano que `eliminar_viaje`
antes de v74r (~250 nodos por micro arrastrado). Ahora
llama a `_destruir_viaje_completo` antes de desenlazar
cada viaje. La herramienta de limpieza era en sí misma
una fuente de fuga.

**Noveno, décimo y undécimo flujo arreglados en v74y:**
`actualizar_configuracion_vehiculo` (destruye los pisos
viejos antes de reemplazarlos), `eliminar_vehiculo`
(destruye el vehículo completo: asientos, pisos, listas
circulares de asientos, campos) y `eliminar_empresa`
(destruye todos sus vehículos y la empresa). Se movieron
tres helpers de `Viaje.php` a `FuncionesAuxiliares.php`:
`_destruir_lista_circular_asientos`, `_destruir_piso`, y
`_destruir_copia_vehiculo` (renombrada a
`_destruir_vehiculo_completo`). Fueron detectados por el
detector ampliado de fugas
(`miscelaneas/detectar_fugas_eliminar.php`).

**Séptimo y octavo flujo arreglados en v74x:**
`seleccionar_asiento_micro` (bloque `limpiar_lista = true`:
antes solo desenlazaba el `primer` de la cabeza, dejando
huérfanos los asientos-en-venta viejos con sus campos) y
`deseleccionar_asiento_micro` (antes filtraba el nodo
asiento-en-venta del asiento deseleccionado sin
destruirlo). Ambos reutilizan el helper nuevo
`_destruir_asiento_en_venta` de `Venta.php`.

**Quinto flujo arreglado en v74v:** `cancelar_venta`.
Antes dejaba huérfanos los asientos-en-venta (la lista
cuelga con `primer`/`siguiente`, no con `hmi`/`hd`, así
que `eliminar_hmi` no los alcanzaba), los campos de cada
cupón, los campos hoja del nodo venta y el sub-nodo
`opciones_cobro` con sus 4 hijos. Ahora los destruye
explícitamente. `_destruir_campos_simples` vive en
`FuncionesAuxiliares.php` (antes estaba en `Viaje.php`)
para que `Venta.php` la use sin dependencia circular.

**Tercer y cuarto flujo arreglados en v74u:**
`eliminar_terminal_autorizada` ahora llama a
`_destruir_terminal_viaje` después de desenlazar del
contenedor. `_guardar_paradas_intermedias` ahora destruye
las paradas viejas que no se reutilizan al editar el viaje
(las que sí se reutilizan conservan su identidad, para no
romper las referencias de los TerminalViaje que apuntan a
ellas).

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
4. Documentar los criterios en §8.6.1. **Hecho en v76d**
   (ver §8.6.1).

**Fase 3 — Optimizaciones (en curso, prioridad media).**

**Índice de ventas por viaje implementado en v76a.**
`formatear_viaje` recibe un 4to parámetro opcional
(`$indice_ventas`). `listar_viajes_de_dueno` y
`listar_viajes_de_terminal` construyen el índice UNA SOLA
VEZ antes del bucle de viajes, con un solo recorrido del
contenedor de ventas del dueño. Helper nuevo
`_construir_indice_ventas_por_viaje`. El cómputo inline
anterior se extrajo a `_calcular_vendidos_por_micro_de_viaje`,
que se usa como fallback cuando `formatear_viaje` se llama
sin índice (por ejemplo, desde llamados puntuales como
`ventas/obtener`). Baja el costo de `listar_viajes_*`
de O(V × W) a O(V + W).

**Pendientes de Fase 3:**

- **Iteradores persistentes.** El framework permite iteradores
  que van perdurando su posición actual en el grafo. Podrían
  reducir recorridos repetidos en otros flujos.
- **Eventual carga parcial del grafo.** Requiere cambiar el
  framework (frente A). No se hace por ahora.

### 8.6.1 Criterios de eliminación

Documentado al cerrar Fase 2 (v76d). El criterio se extrajo
de los 19 flujos arreglados en v74r-v75a + el Grupo B.

**Reglas generales (aplican siempre):**

1. **Sub-árbol interno vs referencia externa.** Al destruir
   una entidad, hay que distinguir qué nodos viven solo
   como parte de ella (sub-árbol interno) y qué nodos
   tienen vida propia o son compartidos (referencias
   externas).
   - **Sub-árbol interno:** se destruye recursivamente.
     Ejemplos: campos string, contenedores, cupones de una
     venta, asientos de una copia de vehículo, TerminalViaje,
     opciones avanzadas, opciones_cobro.
   - **Referencia externa:** solo se desenlaza, no se
     destruye. Ejemplos: pasajero/comprador (reutilizable),
     empresa (pertenece al dueño), terminal (usuario),
     asiento real del micro (vive en el micro, no en la
     venta), parada (vive en el viaje), rendición (vive en
     el dueño), sesión (vive en credenciales).

2. **Orden: hojas a raíz.** Antes de `Nodo::eliminar($n)`,
   hay que haber desenlazado todas sus referencias
   entrantes. El patrón recursivo es: primero desenlazar
   al hijo del padre, después destruir el hijo. Si no,
   `Nodo::eliminar` falla silenciosamente y el nodo queda
   huérfano.

3. **El contenedor padre se vacía antes de destruirse.**
   Si la entidad tiene un contenedor propio (ej. `micros`
   de un viaje), hay que vaciarlo (destruyendo cada hijo)
   antes de destruir el contenedor.

4. **Desenlazar del contenedor de arriba.** Después de
   destruir el sub-árbol, desenlazar el nodo principal
   del contenedor que lo contenía (el contenedor del
   dueño, por ejemplo).

5. **Los campos hoja se destruyen con su padre.** Un nodo
   con dato string y sin adyacentes propios es un "campo
   hoja". Se destruye junto con el nodo que lo contiene.
   El helper `_destruir_campos_simples($padre, $excluir)`
   hace esto automáticamente, dejando intactos los
   enlaces que apuntan a sub-árboles o a referencias
   externas.

**Anti-patrones (lo que causaba las fugas pre-Fase 2):**

- **Desenlazar sin destruir.** `$padre->eliminar_adyacente($enlace)`
  solo desconecta el nodo. Si el nodo no tiene otra
  referencia entrante, queda huérfano. Fue la causa
  principal de las ~10.000 fugas del piloto.
- **Destruir sin desenlazar.** `Nodo::eliminar($n)` con
  referencias entrantes falla silenciosamente. El nodo
  sigue vivo con sus referencias rotas. Fue el bug del
  orden de destrucción (v74t).
- **Usar `eliminar_hmi` sobre listas simples.** Las listas
  con `primer`/`siguiente` no son árboles `hmi`/`hd`. El
  `eliminar_hmi` no las ve. Bug de `cancelar_venta`
  (v74v).

**Criterio por entidad concreta:**

**Usuario (`eliminar_usuario`, `actualizar_usuario`):**
- Destruir: campos del nodo usuario (`nivel`, `nombre_real`,
  `email`, `efectivo`), banco (contenedor con `nombre`,
  `cuenta`), contenedor `duenos` si es soporte, nodo
  credencial en credenciales (con `codigo_hash`,
  `contrasena`, `intentos_fallidos`, `bloqueado_hasta`,
  `ultimo_acceso`, `ip_ultimo_acceso`), y las sesiones
  activas del usuario.
- Desenlazar: referencias cruzadas entre usuarios (`soporte`
  en dueños, `dueno` en terminales, terminales del dueño).
- No destruir: pasajeros asociados, terminales (impiden la
  eliminación si las tiene), ventas.
- Helper: `_destruir_banco_usuario`.

**Pasajero (`eliminar_pasajero`, `limpiar_pasajeros_de_prueba`):**
- Destruir: campos personales (`nombres`, `apellido`,
  `email`, `celular`, `celular_emergencia`,
  `fecha_nacimiento`, `localidad`, `direccion`,
  `fecha_ultima_modificacion`), declaración jurada adjunta
  con sus 4 sub-campos (`nombre_original`, `tipo`, `tamano`,
  `fecha_subida`).
- No eliminar si tiene pasajes comprados (regla del negocio:
  no se puede borrar un cliente que viajó).
- Helpers: `_destruir_declaracion_jurada_pasajero`,
  `_destruir_pasajero_completo`.

**Declaración jurada del pasajero (subir / reemplazar /
eliminar):**
- Destruir: el nodo DJ con sus 4 sub-campos.
- Helper: `_destruir_declaracion_jurada_pasajero`.

**Empresa (`eliminar_empresa`):**
- Destruir: cada vehículo completo (con asientos, pisos,
  listas circulares, campos), el contenedor `vehiculos`, y
  la propia empresa (con sus campos).
- No destruir: los micros de viajes que apunten a esta
  empresa. La referencia es solo por identificador de
  empresa, no rompe al destruir la empresa.
- Helper: `_destruir_vehiculo_completo`.

**Vehículo (`eliminar_vehiculo`, `actualizar_configuracion_vehiculo`):**
- Destruir: asientos, pisos, listas circulares de asientos,
  campos (`nombre`, `foto`).
- No destruir: copias del vehículo en micros. Son nodos
  independientes.
- Helper: `_destruir_vehiculo_completo`, `_destruir_piso`,
  `_destruir_lista_circular_asientos`.

**Viaje (`eliminar_viaje`, `limpiar_viajes_de_prueba`):**
- Destruir, en orden: micros (cada uno con su copia de
  vehículo y asientos), contenedor `micros`, TerminalViaje
  de cada terminal autorizada, contenedor
  `terminales_autorizadas`, paradas intermedias, DJs
  mayor y menor, opciones avanzadas, campos del viaje.
- Desenlazar: `dueno` (referencia externa al usuario).
- Helper: `_destruir_viaje_completo` (que usa
  `_destruir_micro`, `_destruir_terminal_viaje`,
  `_destruir_parada`).

**Micro de viaje (`eliminar_micro_de_viaje`):**
- Destruir: copia de vehículo (con pisos y asientos),
  campos del micro (`monto`, contadores).
- Desenlazar: `empresa` (externa), `viaje` (circular).
- Helper: `_destruir_micro`.

**Terminal autorizada de un viaje
(`eliminar_terminal_autorizada`):**
- Destruir: el TerminalViaje con sus campos override.
- Desenlazar: `terminal` (usuario externo),
  `punto_subida_bajada` (parada del viaje, sigue vivo).
- Helper: `_destruir_terminal_viaje`.

**Paradas intermedias (`_guardar_paradas_intermedias`):**
- Reutilizadas: se conservan (su identidad importa para
  los TerminalViaje que las referencian por
  `punto_subida_bajada`).
- No reutilizadas: se destruyen.
- Helper: `_destruir_parada`.

**Venta persistente (`cancelar_venta`):**
- Destruir: asientos-en-venta (con sus campos
  `punto_subida_bajada`, `hora_subida_bajada`), cupones
  (con campos), contenedor de cupones, sub-nodo
  `opciones_cobro`, campos del nodo venta.
- Desenlazar: `comprador` (pasajero reutilizable),
  `asiento` real (vuelve a libre en el micro),
  `viaje`/`micro`/`terminal` (referencias externas),
  `rendido` del cupón (nodo Rendición que sigue vivo).
- Helper: `_destruir_asiento_en_venta` (usado por
  `cancelar_venta`, `_destruir_venta_actual` y
  `ViajeAsientos.php`).

**Venta actual (`confirmar_venta_actual`):**
- Destruir: venta_actual, lista de asientos-en-venta
  temporales.
- Desenlazar: `terminal` (usuario externo), `asiento` real.
- Helper: `_destruir_venta_actual`.

**Asiento-en-venta (`seleccionar_asiento_micro`,
`deseleccionar_asiento_micro`):**
- Al cambiar de micro a mitad de selección, o al
  deseleccionar un asiento, el nodo asiento-en-venta se
  destruye.
- Helper: `_destruir_asiento_en_venta`.

**Cupón (`pagar_cupon_venta`):**
- Al eliminar cupones sobrantes tras pagar el saldo, se
  destruyen con sus campos.
- Helper: `_eliminar_cupon_del_contenedor` (implícito).

**Sesión (`cerrar_sesion`, `eliminar_sesiones_de_usuario`):**
- Destruir: el nodo sesión con sus campos (`usuario`,
  `creado_en`).

**Campos hoja específicos:**
- `bloqueado_hasta` en credenciales: al expirar el bloqueo
  y al registrar login exitoso (`Autenticacion.php`).
- `metodo_pago` del cupón: al coincidir con el método de la
  venta (`Venta.php`).
- `foto` del vehículo: al reemplazarla (`Vehiculo.php`).
- `hora_estimada` de una parada: al quitarle la hora
  (`Viaje.php`).
- Campos al limpiar un valor en `actualizar_pasajero` y
  `actualizar_usuario`.

**Regla de oro:** si un nodo se desenlaza sin destruirse,
primero preguntar "¿tiene otra referencia entrante?". Si la
respuesta es no, hay que destruirlo.

### 8.7 Plan de contextos y carga parcial (en desarrollo)

Este apartado es el plan del piloto para aprovechar los
contextos que se están implementando en el framework
(ver §11.4 del prompt del framework). Todavía no está
implementado. Se actualiza a medida que avanza cada fase.

**Motivación.** El piloto crece en cantidad de dueños,
viajes, ventas y pasajeros. Cada operación carga el
grafo completo (ver §8.6, "frente A"). Aislar el
subgrafo de un dueño permite cargar solo lo que se
necesita y baja el costo de cada operación.

**Fase A — Usuarios como IDs especiales (completada en v76k).**
Todos los usuarios (no solo los dueños) son ahora nodos
con ID especial `us_<nombre>`. Los enlaces desde
`usuarios` siguen llamándose `<nombre>` (nombre visible),
así todos los accesos por `adyacente()` funcionan sin
cambios. Los nodos viejos se migraron con
`miscelaneas/migrar_usuarios_especiales.php`, que usa el
comando `grafo:reemplazar_referencias` para redirigir
las aristas cruzadas.

**Fase B — Contenedores por nivel de exposición (en diseño).**
Cada usuario pasa a tener contenedores por nivel colgando
directamente de su nodo raíz:

```
us_X
├── publico                    (dato = nombre_usuario)
│   ├── nivel
│   ├── nombre_real
│   └── email
├── privado                    (los datos internos del rol)
├── compartido_con_us_Y        (uno por cada usuario con quien comparte)
└── ...
```

Los enlaces "de permiso" (`dueno` en el terminal, `soporte`
en el dueño) van directamente en la raíz del usuario, no
en un contenedor.

La seguridad emerge de la topología: si un usuario no
tiene un enlace al `privado` de otro, no puede alcanzarlo.
Los datos privados no están en el grafo alcanzable desde
otros usuarios.

Los contenedores concretos por rol están en el PHPDoc de
`aplicacion_POST.php`, sección "Diseño propuesto:
contenedores por nivel de exposición".

Admin y soporte NO usan este modelo: siguen accediendo a
todo por código (con `_verificar_permiso_dueno`). El admin
es todopoderoso; el soporte lo es solo sobre sus dueños
asignados.

**Fases de implementación.** El orden revisado es:

1. **Fase A** (completada en v76k): usuarios como IDs
   especiales (`us_<nombre>`).
2. **Fase B1** (completada en v76m): contenedores
   `publico` y `privado` creados como alias de los
   nodos existentes. Los enlaces viejos quedan vivos.
3. **Fase B2.1** (completada en v76q): crear los
   contenedores `compartido_con_us_termX` en cada dueño,
   con referencias filtradas a los viajes, empresas,
   ventas y cancelaciones del terminal. Alias al
   contenedor de pasajeros del dueño. **No repunta
   nada**: los compartidos coexisten con la estructura
   actual. La migración es idempotente.
4. **Fase B2.2** (pendiente): cambiar el código para que
   el terminal navegue por su compartido. Se agrega un
   parámetro opcional `?Nodo $nodo_contexto` a
   `obtener_contenedor_viajes_dueno`,
   `obtener_contenedor_ventas_dueno` y
   `obtener_contenedor_pasajeros_dueno`. Si el llamador
   pasa el compartido, la función navega desde ahí. Si
   no, navega desde la raíz `usuarios` como hoy.
5. **Fase B2.3** (pendiente): repuntar `us_termX → dueno`
   al contenedor `compartido_con_us_termX`. A partir de
   acá, el acceso del terminal al grafo del dueño pasa
   por la topología.
6. **Fase B2.4** (pendiente): mantener vivos los
   compartidos. Cada operación que modifica viajes
   autorizados, micros, empresas o ventas del terminal
   actualiza el compartido correspondiente.
7. **Fase B3** (pendiente): eliminar los accesos viejos.
8. **Fase C** (opcional): tipos como IDs especiales,
   ortogonal.
9. **Fase D** (pendiente): aprovechar la carga parcial
   como optimización.

**Comandos asociados.**

- `app:crear_compartidos_terminal` (v76q): crea los
  compartidos. Args: `{dueno, terminal}`, cada uno
  `nombre` o `todos`. Idempotente.
- Los demás comandos de migración están en el módulo
  `Aplicacion/Migraciones/`. Los tres primeros
  (`grafo:crear_niveles_usuario`, además) son deuda
  técnica: `grafo:crear_niveles_usuario` está en el
  framework pero debería vivir en la app.

Cada fase es una tanda, con migración idempotente y
verificación en local antes de producción.

Lo que cambia:

- Cada dueño es una raíz del grafo. Al cargar, se
  puede hacer BFS desde ese root y traer solo su
  subárbol.
- Los terminales de un dueño quedan dentro del
  contexto del dueño. Siguen siendo usuarios, pero
  se alcanzan a través del dueño.
- El nodo `usuarios` sigue existiendo como raíz
  global (contiene a todos los usuarios, incluidos
  los dueños), así que las operaciones globales
  (login, admin) no se rompen.

**Tipos como IDs especiales.** Segundo eje de
contexto. Hoy los tipos son implícitos: "empresa" es
un nodo bajo `dueno1 -> empresas -> <empresa>`. En el
plan se agregan roots explícitos como `tipo_empresas`,
`tipo_viajes`, `tipo_asientos`, `tipo_clientes`,
`tipo_ventas`, `tipo_micros`. Cada root apunta a los
nodos de su tipo, sin importar el dueño.

Lo que cambia:

- Consultas cross-cutting: "todas las empresas del
  sistema" se hacen con BFS desde `tipo_empresas`
  en vez de recorrer todos los dueños.
- Un nodo puede pertenecer a dos contextos a la vez
  (un dueño + un tipo). El bitmask soporta esa
  situación.
- La pertenencia múltiple tiene un costo: los nodos
  tienen dos padres (uno por cada eje). El framework
  lo soporta, pero hay que revisar los flujos de
  destrucción para que desenlacen de ambos lados.

**Multi-dueño real.** El plan contempla un mismo
nodo perteneciente a varios dueños (por ejemplo, un
catálogo de productos compartido entre clientes). El
bitmask lo soporta sin cambios estructurales: la
columna `contexto_mask` guarda varios bits en 1.

**Cuándo se implementa.** El orden es:

1. **Fase 1 del framework**: `SQL64` (bitmask + 3
   tablas nuevas). Sin tocar el piloto.
   **Completado** (framework 1.5i.7k).
2. **Fase 2 del framework**: `IndexedDB64` (espejo).
   **Completado** (framework 1.5i.7k).
3. **Fase 3 del framework**: `JSON64` / `XML64`.
   Pendiente.
4. **Refactor del framework: dejar el Nodo limpio.**
   Antes de agregar más métodos de indexación de
   contextos (256 bits, producto de primos, etc.),
   hay que mover la máscara fuera del Nodo. Ver
   §11.4 del prompt del framework. Pendiente,
   bloqueante de la Fase 5.
5. **Fase A del piloto**: usuarios como IDs especiales.
   **Completada** en v76k.
6. **Fase B1 del piloto**: crear `publico` y `privado`
   en cada usuario. Mover los datos. `usuarios` apunta
   al `publico`. Pendiente.
7. **Fase B2 del piloto**: crear los `compartido_con_X`
   y reescribir los enlaces desde los terminales.
   Pendiente.
8. **Fase B3 del piloto**: eliminar los accesos viejos.
   Pendiente.
9. **Fase C (opcional)**: tipos como IDs especiales.
10. **Fase D**: aprovechar la carga parcial como
    optimización.

Los pasos 1-4 son del framework. Los pasos 5-7 son
del piloto. Cada paso en su propia tanda, con sus
dos scripts donde corresponda.

**Preguntas abiertas (a consensuar cuando llegue el
momento):**

- Qué operaciones del piloto van a aprovechar el modelo
  topológico, y cuáles van a seguir requiriendo un
  acceso de admin/soporte.
- Cómo se comporta `listar_usuarios` desde la Fase B1 en
  adelante: recorre `usuarios → cada hijo`, lee el dato
  del contenedor `publico`, sin cargar subárboles.
- Cómo migrar los grafos existentes sin perder datos.
  La migración es idempotente y se corre por fases.

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

**Última actualización de este prompt:** v1.5piloto.76s
(Fase B2.2.2 del modelo topológico: Venta.php. Contexto
opcional en `obtener_contenedor_ventas_dueno`; filtro opcional
por terminal en `_buscar_venta_por_id`, `obtener_venta_por_id`,
`pagar_cupon_venta`, `cancelar_venta` y `obtener_info_cancelacion`;
`listar_ventas_por_terminal` y `confirmar_venta_actual` navegan
por el contexto del terminal. Refactor sin cambio de
comportamiento: el enrutador todavía no pasa el `$nombre_terminal`,
así que la búsqueda sigue siendo global. La preparación de B2.3
está completa.).
Antes: v1.5piloto.76r
(Fase B2.2.1 del modelo topológico: `obtener_contenedor_viajes_dueno`
acepta un `?Nodo $nodo_contexto` opcional, y
`listar_viajes_de_terminal` le pasa el nodo del dueño como
contexto. Nuevo helper `_contexto_terminal` en `Viaje.php`.
Refactor sin cambio de comportamiento: el código del terminal
sigue navegando por el nodo del dueño, pero ya con el
mecanismo listo para B2.3 (repuntado).).
Antes: v1.5piloto.76n
(pestaña Grafo: sección "Nodos raíz" y sistema de
migraciones. Nuevo comando `grafo:raices` en el framework
(lista los IDs especiales con sus adyacentes). Nuevo nodo
especial `aplicacion` con contenedor `migraciones`; cada
migración aplicada deja un enlace testigo autoreferente.
Nuevo módulo `Aplicacion/Migraciones/` con el registro de
migraciones, sus funciones de detección y aplicación, y los
comandos `app:migracion_*`. La pestaña Grafo permite
aplicar migraciones desde la UI.).
Antes: v1.5piloto.76m
(Fase B1 del modelo topológico. Crea los contenedores
`publico` y `privado` como alias de los nodos ya existentes.
Los enlaces viejos NO se tocan: los contenedores apuntan a
los mismos nodos físicos, así el código actual sigue
funcionando sin cambios. La migración es idempotente y se
corre con `?migrar_niveles_usuario=1` desde `index.php`
(opcionalmente con `&usuario=carmen1` para un solo usuario).
Nuevo comando `grafo:crear_niveles_usuario` en el
`Controlador`, que opera sobre el grafo cargado usando el
token encapsulado.).
Antes: v1.5piloto.76l
(diseño del modelo topológico por niveles de exposición.
Cada usuario va a tener contenedores `publico`, `privado` y
`compartido_con_X` colgando de su nodo raíz. La seguridad
emerge de la topología. Admin y soporte siguen accediendo
por código, no por topología. Diseño completo en el PHPDoc
de `aplicacion_POST.php`; plan por fases en §8.7. Solo
documentación, no hay cambios de código todavía.).
Antes: v1.5piloto.76k
(Fase A de contextos del piloto. Todos los usuarios pasan a
tener ID especial `us_<nombre>`. Los enlaces desde `usuarios`
siguen llamándose `<nombre>` (nombre visible), por lo que
todos los accesos por `adyacente()` siguen funcionando sin
cambios. Script de migración idempotente en
`miscelaneas/migrar_usuarios_especiales.php`, ejecutable con
`?migrar_usuarios_especiales=1` desde `index.php`. Nuevo
comando `grafo:reemplazar_referencias` en el `Controlador`
para redirigir referencias cruzadas. Sin carga parcial
todavía.).
Antes: v1.5piloto.76j
(cierre de la fase 2 del framework. El framework
quedó en 1.5i.7k: SQL64 en PHP e IndexedDB64 en JS,
ambos cerrados y verificados. §8.7 se actualiza con
el estado del framework (pasos 1-2 completados, más
la deuda del Nodo limpio). §12 y §13 reflejan el
cierre. Bump de `index.php` a `1.5piloto.76j`.).
Antes: v1.5piloto.76i
(solo documentación. Se agrega §8.7 con el plan de
contextos del piloto: dueños y tipos como IDs
especiales, integración con el plan del framework
§11.4, fases y preguntas abiertas.).
Antes: v1.5piloto.76h
(planilla de pasajeros "vacía": nuevo botón "Imprimir
Planilla Vacía" en el croquis del micro.
`imprimir_planilla_pasajeros_micro` recibe un 4to
parámetro `$vacia`; `index.php` lee `$_GET['vacia']`.
Solo PHP.).
Antes: v1.5piloto.76g
(eliminación de nodos huérfanos desde la pestaña Grafo.
Nuevo comando `grafo:eliminar_huerfanos` (framework PHP
1.5i.7i + espejo JS), subacción `grafo/eliminar_huerfanos`
en el enrutador — admin/soporte, sin chequeo de
`es_pruebas` por decisión consciente — y botón
"Eliminar nodos basura" en `Aplicacion/grafo.js` con modal
de confirmación y vista previa. Nueva prueba 56 del plugin
(`eliminar_huerfanos_limpia_grafo`).).
Antes: v1.5piloto.76f
(nuevo helper `en_grafo_credenciales_solo_lectura` +
subacción `grafo/resumen_credenciales` en el enrutador,
solo en modo pruebas. Permite al plugin medir huérfanos
del grafo de credenciales.).
Antes: v1.5piloto.76d
(solo documentación. Se escribe completa la sección
§8.6.1 "Criterios de eliminación" con el criterio por
entidad extraído de toda la Fase 2.).
Antes: v1.5piloto.76c
(cierre del pendiente "convertir en modal la carga de
empresas y micros" + eliminación de código muerto del
HTML: 5 formularios embebidos, 3 botones huérfanos y
`__index.html`.).
Antes: v1.5piloto.76b
(cierre del pendiente "cerrar todos los modales al
cerrar sesión". `_limpiar_contenido_dinamico` ahora
cierra el modal genérico, el apilado, el
`#opciones_impresion` y todos los `.modal-chico`.
Cubre logout manual y cambio de sesión.).
Antes: v1.5piloto.76a
(Fase 3 del plan de optimización del grafo: índice de
ventas por viaje precalculado. `formatear_viaje` recibe
un 4to parámetro opcional. `listar_viajes_de_dueno` y
`listar_viajes_de_terminal` construyen el índice UNA VEZ
antes del bucle. Baja el costo de O(V × W) a O(V + W).).
Antes: v1.5piloto.75a
(cierre de los "campos huérfanos" del Grupo B: cinco
fixes chicos en `Autenticacion.php`, `Venta.php`,
`Vehiculo.php` y `Viaje.php`. El `punto_subida_bajada`
del TerminalViaje queda descartado por diseño.).
Antes: v1.5piloto.76
(Fase 2, flujos 15 a 19: `eliminar_pasajero`,
`limpiar_pasajeros_de_prueba`, `subir_declaracion_jurada_pasajero`,
`eliminar_declaracion_jurada_pasajero`, `actualizar_pasajero`.
Helpers nuevos: `_destruir_declaracion_jurada_pasajero`,
`_destruir_pasajero_completo`.).
Antes: v1.5piloto.75
(Fase 2, flujos 13 y 14: `eliminar_usuario` y
`actualizar_usuario`. También `Sesion.php` (destrucción
de campos del nodo sesión). Nuevo helper
`_destruir_banco_usuario` en `Usuario.php`.).
Antes: v1.5piloto.74z
(Fase 2, flujo 12: `limpiar_viajes_de_prueba`. Ahora
destruye el subárbol completo de cada viaje antes de
desenlazarlo. La herramienta de limpieza era en sí misma
una fuente de fuga.).
Antes: v1.5piloto.74y
(Fase 2, flujos 9, 10 y 11: `actualizar_configuracion_vehiculo`,
`eliminar_vehiculo`, `eliminar_empresa`. Se movieron tres
helpers de `Viaje.php` a `FuncionesAuxiliares.php`.
Detectados por el detector ampliado de fugas.).
Antes: v1.5piloto.74x
(Fase 2, séptimo y octavo flujo arreglados:
`seleccionar_asiento_micro` (cambio de micro a mitad de
selección) y `deseleccionar_asiento_micro`. Nuevo helper
`_destruir_asiento_en_venta` en `Venta.php`.).
Antes: v1.5piloto.74w
(Fase 2, sexto flujo arreglado: `confirmar_venta_actual`.
Antes desenlazaba la venta_actual sin destruir su subárbol,
dejando ~5 nodos huérfanos por venta. Nuevo helper
`_destruir_venta_actual`. Bug detectado por la prueba
`cancelar_venta_limpia_nodos` del plugin.).
Antes: v1.5piloto.74v
(Fase 2, quinto flujo arreglado: `cancelar_venta`. Ahora
destruye los asientos-en-venta, los cupones con sus
campos, los campos hoja del nodo venta y el sub-nodo
`opciones_cobro`. `_destruir_campos_simples` se movió a
`FuncionesAuxiliares.php`.).
Antes: v1.5piloto.74u
(Fase 2, tercer y cuarto flujo arreglados:
`eliminar_terminal_autorizada` y `_guardar_paradas_intermedias`).
Antes: v1.5piloto.74t
(fix del orden de destrucción en los helpers `_destruir_*`.
Los helpers no desenlazaban al hijo del padre antes de
destruirlo, dejando 2 nodos huérfanos por micro: las
cabezas de las listas circulares de cada piso. Corregido).
Antes: v1.5piloto.74s
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
- Cerramos en v76 los flujos 15 a 19 de Fase 2:
  `eliminar_pasajero`, `limpiar_pasajeros_de_prueba`,
  `subir_declaracion_jurada_pasajero`,
  `eliminar_declaracion_jurada_pasajero`, y
  `actualizar_pasajero`. Helpers nuevos:
  `_destruir_declaracion_jurada_pasajero`,
  `_destruir_pasajero_completo`.
- Cerramos en v76d la sección §8.6.1 "Criterios de
  eliminación": reemplazado el stub por el criterio
  completo. Reglas generales, anti-patrones, criterio
  por entidad, referencia a los helpers `_destruir_*`.
- Cerramos en v76c el pendiente "convertir en modal la
  carga de empresas y micros" + eliminación de código
  muerto del HTML. Se eliminaron los 5 formularios
  embebidos, 3 botones huérfanos sin listener y
  `__index.html`.
- Cerramos en v76b el pendiente de backlog "cerrar
  todos los modales al cerrar sesión". Ahora
  `_limpiar_contenido_dinamico` cierra el modal
  genérico, el apilado, el `#opciones_impresion` y
  todos los `.modal-chico` flotantes. Cubre el logout
  manual y el cambio de sesión sin recargar.
- Cerramos en v76a la primera parte de Fase 3:
  índice de ventas por viaje precalculado. `formatear_viaje`
  acepta un 4to parámetro opcional. `listar_viajes_de_dueno`
  y `listar_viajes_de_terminal` construyen el índice UNA
  VEZ antes del bucle de viajes. Baja el costo de
  O(V × W) a O(V + W). Pendientes de Fase 3: iteradores
  persistentes y eventual carga parcial del grafo.
- Cerramos en v75a el Grupo B de Fase 2 (campos
  huérfanos): `bloqueado_hasta` (Autenticacion.php, 2
  usos), `metodo_pago` del cupón (Venta.php), `foto`
  del vehículo (Vehiculo.php), `hora_estimada` de la
  parada (Viaje.php). El `punto_subida_bajada` del
  TerminalViaje queda descartado (referencia externa).
- Cerramos en v75 los flujos 13 y 14 de Fase 2:
  `eliminar_usuario` (destruye campos del usuario, banco,
  credenciales y sesiones activas) y `actualizar_usuario`
  (destruye efectivo, banco, hojas de banco y
  bloqueado_hasta). También `eliminar_sesiones_de_usuario`
  y `cerrar_sesion` en `Sesion.php`. Nuevo helper
  `_destruir_banco_usuario`.
- Cerramos en v74z el flujo 12 de Fase 2:
  `limpiar_viajes_de_prueba`. Ahora destruye el subárbol
  completo de cada viaje antes de desenlazarlo.
  Reutiliza `_destruir_viaje_completo` de v74r.
- Cerramos en v74y los flujos 9, 10 y 11 de Fase 2:
  `actualizar_configuracion_vehiculo`, `eliminar_vehiculo`
  y `eliminar_empresa`. Se movieron tres helpers de
  destrucción de `Viaje.php` a `FuncionesAuxiliares.php`
  (`_destruir_lista_circular_asientos`, `_destruir_piso`,
  `_destruir_copia_vehiculo` renombrada a
  `_destruir_vehiculo_completo`). Flujos identificados
  por el detector ampliado de fugas.
- Cerramos en v74x los flujos 7 y 8 de Fase 2:
  `seleccionar_asiento_micro` (bloque de cambio de micro)
  y `deseleccionar_asiento_micro`. Nuevo helper
  `_destruir_asiento_en_venta` en `Venta.php`,
  reutilizado por `_destruir_venta_actual` y por los
  dos flujos de `ViajeAsientos.php`.
- Cerramos en v74w el sexto flujo de Fase 2:
  `confirmar_venta_actual`. Ahora destruye el subárbol
  de la venta_actual después de desenlazarla de la
  terminal. Bug detectado por la prueba espejo
  `cancelar_venta_limpia_nodos` (crear la venta
  incrementaba el contador de huérfanos en 5).
  Pendientes dos fugas menores en `deseleccionar_
  asiento_micro` y `seleccionar_asiento_micro`.
- Cerramos en v74v el quinto flujo de Fase 2:
  `cancelar_venta`. Ahora destruye los asientos-en-venta,
  los campos de cada cupón, los campos hoja del nodo
  venta y el sub-nodo `opciones_cobro`. El helper
  `_destruir_campos_simples` se movió de `Viaje.php` a
  `FuncionesAuxiliares.php`.
- Cerramos en v74u los flujos 3 y 4 de la Fase 2:
  `eliminar_terminal_autorizada` ahora destruye el
  TerminalViaje; `_guardar_paradas_intermedias` ahora
  destruye las paradas viejas no reutilizadas.
- Cerramos en v74t el fix del orden de destrucción en
  los helpers `_destruir_*` de `Viaje.php`. El bug: los
  helpers llamaban a `Nodo::eliminar` del hijo ANTES de
  desenlazarlo del padre, dejando 2 nodos huérfanos por
  micro (las cabezas de las listas circulares de cada
  piso). El fix: desenlazar siempre antes de destruir.
  También se desenlaza el `siguiente` de todos los
  asientos (no solo el del último).
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
- Cerramos en v76k la Fase A de contextos del piloto:
  todos los usuarios pasan a ser IDs especiales
  `us_<nombre>`. Los enlaces desde `usuarios` siguen
  llamándose `<nombre>`, así todos los accesos por
  `adyacente()` funcionan sin cambios. Solo se tocaron
  los 3 lugares donde se CREAN usuarios (`agregar_usuario`,
  `actualizar_usuario` rama credenciales, y la creación
  del admin en `index.php`). Nuevo comando genérico
  `grafo:reemplazar_referencias` en el `Controlador`,
  que redirige todas las aristas del grafo desde un
  mapa `{viejo → nuevo}`. Script de migración
  idempotente en `miscelaneas/migrar_usuarios_especiales.php`,
  ejecutable con `?migrar_usuarios_especiales=1`. Sin
  cambios en los accesos, sin carga parcial todavía.
- Cerramos en v76o con documentación. Se dejan asentados
  tres pendientes sobre el framework, sin tocar código:
  (1) espejar `grafo:reemplazar_referencias` en el
  `Controlador.js` (se agregó al PHP en 76k, es genérico);
  (2) mover `grafo:crear_niveles_usuario` fuera del
  `Controlador` del framework: es específico del piloto
  y debe levantarse al vuelo desde la app;
  (3) no se pudo verificar `grafo:raices` desde la consola
  del service worker del plugin, porque MV3 prohíbe
  `import()` dinámico en `ServiceWorkerGlobalScope`. La
  verificación se hizo desde el piloto PHP vía la pestaña
  Grafo. Si se quiere verificar desde el plugin, hay que
  exponer `globalThis.Controlador = Controlador` en el
  bootstrap del SW. Pendiente anotado, sin urgencia.
- Cerramos en v76s la Fase B2.2.2 del modelo topológico
  (Venta.php). Refactor sin cambio de comportamiento:
  `obtener_contenedor_ventas_dueno` acepta un `?Nodo $nodo_contexto`
  opcional. `_buscar_venta_por_id` y sus llamadores
  (`obtener_venta_por_id`, `pagar_cupon_venta`, `cancelar_venta`,
  `obtener_info_cancelacion`) aceptan un `?string $nombre_terminal`
  opcional que restringe la búsqueda al contexto del terminal.
  `listar_ventas_por_terminal` navega por el contexto.
  `confirmar_venta_actual` usa el contexto al resolver viajes y
  al insertar la venta. `_construir_indice_ventas_por_viaje`
  (en Viaje.php) acepta contexto y `listar_viajes_de_terminal`
  lo pasa. Pendiente: B2.2.3 (ViajeAsientos.php) y B2.2.4
  (Empresa.php). Después: B2.3 (repuntar `us_termX → dueno`
  al compartido).
- Cerramos en v76r la Fase B2.2.1 del modelo topológico.
  Refactor sin cambio de comportamiento: `obtener_contenedor_viajes_dueno`
  acepta un `?Nodo $nodo_contexto` opcional. Nuevo helper
  `_contexto_terminal($nombre_terminal)` en `Viaje.php`.
  `listar_viajes_de_terminal` le pasa el nodo del dueño como
  contexto. La idea es preparar el terreno para B2.3: cuando
  el enlace `us_termX → dueno` apunte al compartido, el código
  del terminal ya navega por el nodo correcto sin cambios.
  Pendientes de B2.2: B2.2.2 (Venta.php), B2.2.3
  (ViajeAsientos.php), B2.2.4 (Empresa.php).
- Cerramos en v76q la Fase B2.1 del modelo topológico.
  Nuevo comando `app:crear_compartidos_terminal` (en el
  módulo `Aplicacion/Migraciones/`, siguiendo la regla
  de convivencia framework/app). Crea los contenedores
  `compartido_con_us_termX` en cada dueño, con
  referencias filtradas a los viajes autorizados,
  empresas referenciadas por esos viajes, ventas y
  cancelaciones del terminal. Alias al contenedor de
  pasajeros del dueño (mismo nodo físico). **No repunta
  los accesos viejos.** Bloque
  `?migrar_compartidos_terminal=1` en `index.php`.
  Idempotente. Plan completo de B2 (B2.1, B2.2, B2.3,
  B2.4) documentado en §8.7. Pendientes: B2.2 (contexto
  opcional en las funciones de contenedor), B2.3
  (repuntar `us_termX → dueno`), B2.4 (mantener los
  compartidos vivos en cada operación).
- Cerramos en v76n la pestaña Grafo ampliada y el sistema
  de migraciones. Sección "Nodos raíz" (lista los IDs
  especiales con sus adyacentes; botón "Ver" reusa el
  modal existente) y sección "Migraciones" (lista con
  estado y botón "Aplicar"). Nuevo nodo especial
  `aplicacion` con contenedor `migraciones`; cada
  migración aplicada deja un enlace testigo autoreferente.
  Nuevo módulo `Aplicacion/Migraciones/` (Registro,
  Funciones, Comandos). Los comandos `app:migracion_*` se
  registran directo con `Controlador::registrar_comando`,
  porque el Controlador ya está inicializado cuando
  corre el `require_once` de la app. Excepción documentada
  a la regla del plugin: no lleva pruebas del plugin.
- Cerramos en v76m la Fase B1 del modelo topológico:
  los contenedores `publico` y `privado` se crean como
  **alias** de los nodos ya existentes. Los enlaces viejos
  NO se tocan: los contenedores apuntan a los mismos nodos
  físicos, así el código actual sigue funcionando. Nuevo
  comando genérico `grafo:crear_niveles_usuario` en el
  `Controlador` (idempotente), script de migración en
  `miscelaneas/migrar_niveles_usuario.php` y bloque
  `?migrar_niveles_usuario=1` en `index.php` (opcional
  `&usuario=carmen1`). Solo grafo de la app; el de
  credenciales queda plano por ahora.
- Cerramos en v76l el diseño del modelo topológico por
  niveles de exposición. Cada usuario va a tener
  contenedores `publico`, `privado` y `compartido_con_X`
  colgando de su nodo raíz. La seguridad emerge de la
  topología: si un usuario no tiene un enlace al
  `privado` de otro, no puede alcanzarlo. Los enlaces
  "de permiso" (`dueno`, `soporte`) van en la raíz.
  Admin y soporte no usan la topología: siguen
  accediendo por código. El diseño completo está en el
  PHPDoc de `aplicacion_POST.php`; el plan por fases
  está en §8.7. No hay cambios de código todavía.
- Cerramos en v76j el cierre de la fase 2 del framework
  (contextos). El framework PHP llegó a 1.5i.7k con
  `PerdurarSuperestructuraStringSQL64` completo (3 tablas
  nuevas, BFS multi-fuente desde los IDs especiales,
  `cargar_parcial` con filtro por bitmask). El espejo
  JS llegó a la misma versión con
  `PerdurarSuperestructuraStringIndexedDB64` (base de
  datos separada `HyS_ctx`, mismos almacenes y misma
  API). Se agregó la interfaz
  `PerdurarSuperestructuraConContexto` en ambos lenguajes.
  El `Controlador` (PHP y JS) ganó 4 métodos nuevos:
  `cargar_parcial`, `guardar_parcial` (stub en fase 1-2),
  `listar_contextos` y `es_grafo_parcial`. Nuevo flag
  `$grafo_parcial` (PHP) / `_grafo_parcial` (JS) que
  bloquea `guardar()` sobre grafo parcial.
- Bug de tipos de ID en el `Map` de `_superestructura`
  (JS) detectado y corregido: en JS, `Map.has("1")` y
  `Map.has(1)` son distintos, a diferencia de PHP que
  coerciona strings numéricos a int en claves de array.
  Fix: normalizar a `String(id)` las claves de
  `_superestructura` y `_nodos_especiales`, y normalizar
  los lookups en `Nodo.existe` y `Nodo.nodo_por_id`.
  Documentado en §12.3 del prompt del framework.
- Deuda de diseño anotada en §11.4 del prompt del
  framework: **dejar el Nodo limpio** antes de agregar
  más métodos de indexación de contextos (256 bits,
  producto de primos, etc.). Hoy el campo
  `contexto_mascara` / `_contexto_mascara` vive en el
  Nodo; el objetivo es moverlo a una estructura auxiliar
  de la capa de persistencia. Bloqueante de la Fase 5
  del framework.
- Cerramos en v76j el cierre formal del piloto: bump de
  `index.php` a `1.5piloto.76j` y actualización de este
  prompt. Sin cambios de código de aplicación.
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

**Estado del proyecto al cierre:** v1.5piloto.76s (framework 1.5i.7l).
Todo funcional. Fixes de v74k a v74o acumulados. Fix de
v74p: pestaña "Grafo" (Fase 1 del plan de optimización).
v74r: `eliminar_viaje` destruye el subárbol completo
(Fase 2, primer flujo). v74s: `eliminar_micro_de_viaje`
destruye el micro completo (Fase 2, segundo flujo).
v74t: fix del orden de destrucción en los helpers
`_destruir_*` (desenlazar siempre antes de destruir).
v74u: flujos 3 y 4 de Fase 2 (`eliminar_terminal_autorizada`
y `_guardar_paradas_intermedias`). v74v: flujo 5
(`cancelar_venta`). v74w: flujo 6
(`confirmar_venta_actual`). v74x: flujos 7 y 8
(`seleccionar_asiento_micro` con `limpiar_lista`,
y `deseleccionar_asiento_micro`). v74y: flujos 9, 10
y 11 (`actualizar_configuracion_vehiculo`,
`eliminar_vehiculo`, `eliminar_empresa`).
v74z: flujo 12 (`limpiar_viajes_de_prueba`).
v75: flujos 13 y 14 (`eliminar_usuario`,
`actualizar_usuario`) y `Sesion.php`.
v75a: cierre del Grupo B (campos huérfanos).
v76: flujos 15 a 19 (pasajeros y declaraciones
juradas adjuntas). v76a: Fase 3, índice de ventas
por viaje. v76b: cerrar todos los modales al
cerrar sesión. v76c: alta de empresa y vehículo a
modal + eliminación de código muerto del HTML.
v76d: §8.6.1 completado (documentación).
v76f: helper de solo lectura para credenciales + endpoint
`grafo/resumen_credenciales` (solo en modo pruebas).
v76g: eliminación de nodos huérfanos desde la pestaña
Grafo (comando `grafo:eliminar_huerfanos` en PHP y JS,
subacción `grafo/eliminar_huerfanos` en el enrutador,
botón en el frontend). Decisión consciente: no está
restringido a modo pruebas.
v76h: planilla de pasajeros "vacía" (parámetro `$vacia`
en `imprimir_planilla_pasajeros_micro` + botón en el
croquis del micro).
v76j: cierre de la fase 2 del framework (contextos).
El framework quedó en 1.5i.7k, con SQL64 (PHP) y
IndexedDB64 (JS) completos. Deuda de diseño anotada:
dejar el Nodo limpio antes de agregar más métodos
de indexación de contextos (ver §11.4 del prompt del
framework).
v76k: Fase A de contextos del piloto. Todos los usuarios
son IDs especiales `us_<nombre>`. Nuevo comando
`grafo:reemplazar_referencias`. Script de migración
idempotente en `miscelaneas/migrar_usuarios_especiales.php`.
Sin cambios en los accesos, sin carga parcial todavía.
v76l: diseño del modelo topológico por niveles de
exposición (`publico`, `privado`, `compartido_con_X`).
Documentado en el PHPDoc de `aplicacion_POST.php`
(sección "Diseño propuesto") y en §8.7. Sin cambios
de código todavía.
v76m: Fase B1. Contenedores `publico` y `privado` creados
como alias de los nodos existentes. Enlaces viejos
intactos. Nuevo comando `grafo:crear_niveles_usuario` y
script `miscelaneas/migrar_niveles_usuario.php` (idempotente).
Bloque `?migrar_niveles_usuario=1` en `index.php`.
v76s: Fase B2.2.2 (Venta.php). Contexto opcional en
`obtener_contenedor_ventas_dueno` y filtro opcional por
terminal en las búsquedas por id. `listar_ventas_por_terminal`
y `confirmar_venta_actual` navegan por el contexto. Refactor
sin cambio de comportamiento.
v76r: Fase B2.2.1. Contexto opcional en
`obtener_contenedor_viajes_dueno` y uso desde
`listar_viajes_de_terminal`. Nuevo helper
`_contexto_terminal`. Refactor sin cambio de comportamiento.
v76q: Fase B2.1. Compartidos por terminal creados como
contenedores filtrados en cada dueño. Nuevo comando
`app:crear_compartidos_terminal` en la app. No repunta
nada, coexiste con la estructura actual. Idempotente.
v76n: pestaña Grafo con sección "Nodos raíz" y "Migraciones".
Nuevo nodo especial `aplicacion` con contenedor `migraciones`.
Módulo `Aplicacion/Migraciones/` con Registro, Funciones y
Comandos. Comandos `grafo:raices` (framework) y
`app:migracion_*` (app, registrados directo). Subacciones
`grafo/raices`, `grafo/migraciones_listar` y
`grafo/migraciones_aplicar` en el Enrutador. Excepción
documentada a la regla del plugin (no lleva pruebas).
El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5w)
tiene 56 pruebas corriendo.

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