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

### 1.3 Arquitectura multi-cliente (multi-aplicación)

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
- `GuardarAmbos.php`: helper `guardar_ambos`.
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

**`Configuracion/Configuracion.php`**: clase `Conf`.

**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`, y
scripts de migración (`migrar_*.php`).

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

### 3.3 Header-wrapper y tabs sticky

Desde v61: header y tabs viven dentro de un
`<div class="header-wrapper">` sticky. Se pegan juntos al scrollear. Las
tabs se achican cuando `body.scrolled` está activo (scroll > 40px). En
pantallas angostas, las tabs van con scroll horizontal.

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
`comprador`, `asientos` (cabeza lista simple), `cupones` (contenedor).

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

### 5.4 `Aplicacion/Sesiones/Sesion.php`

- `crear_sesion`, `listar_sesiones`, `cerrar_sesion`,
  `listar_sesiones_de_usuarios`, `eliminar_sesiones_de_usuario`.

### 5.5 `Aplicacion/GuardarAmbos.php`

- `guardar_ambos($nombre)`: SQL primero, después JSON. Si JSON falla,
  `Controlador::_error()`. Devuelve true si SQL fue exitoso.

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

### 5.15 `index.php`

- Carga framework, persistencia (SQL principal), módulos de
  `Aplicacion/`.
- Requiere `Aplicacion/GuardarAmbos.php` antes que todo lo demás.
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

### 8.2 Limpieza pendiente

Bloques y archivos de migración que se pueden eliminar cuando se
confirmen en los 3 entornos:

- `miscelaneas/migrar_fichas_medicas.php` + bloque
  `?migrar_fichas_medicas=1`.
- `miscelaneas/migrar_hashear_credenciales.php` + bloque
  `?migrar_hashear_credenciales=1`.
- `miscelaneas/migrar_separar_grafos.php` + bloque
  `?migrar_separar_grafos=1`.

Migraciones más viejas que también se pueden limpiar: `migrar_micros`,
`migrar_micros_patente`, `migrar_terminales_autorizadas`,
`migrar_cupones`, `migrar_nombres_pasajeros`,
`migrar_fecha_ultima_modificacion_pasajeros`,
`migrar_declaraciones_juradas_v2`, `migrar_declaraciones_juradas_v3`.

`migrar_pasajeros.php` sigue usando `Controlador::guardar($nombre_app)`
directo. Es histórico, no vale la pena migrarlo.

### 8.3 Próximos pasos posibles

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

- Panel de super admin.
- Autocompletado de pasajeros por DNI en el alta desde la pestaña
  Clientes.
- `boton_reiniciar_numeracion`: agregarlo al HTML.
- `ver_compra_asiento`: modal con el detalle completo.
- Métricas / reportes adicionales.

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
- `Aplicacion/GrafoCredenciales.php`, `Aplicacion/GuardarAmbos.php`.
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

**Última actualización de este prompt:** v1.5piloto.73e (edición de
usuarios en modal).

**Estado de la conversación:**

- Cerramos la Tanda C (rate limiting y tiempos constantes) en v71.
- Introdujimos la carpeta `prompts/` en v71a.
- Dividimos el prompt de continuidad en framework y piloto en v71b.
- Separamos host y credenciales a `config_servidor.php` en v72.
- Agregamos el rol `soporte` en v73.
- No hay tandas en curso.

**Decisiones de diseño tomadas y en vigor:**

- **SQL es siempre el método principal.** El JSON es solo respaldo.
  `Conf::LOCAL` ya no decide el método de persistencia.
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

**Decisiones abiertas / temas pendientes sin consensuar:**

- **Rehash automático**: lo mencionamos como parte de la Tanda C pero
  quedó fuera de la implementación. No se agregó el chequeo
  `password_needs_rehash`. Se puede agregar en un bloque chico dentro
  de `_registrar_login_exitoso`.
- **Limpieza de migraciones**: hay varias tandas de migración que se
  pueden eliminar cuando se confirmen en los 3 entornos. Ver sección
  8.2.
- **Diversificación por tipo de aplicación**: próximo gran frente. Ya
  hay un diseño inicial consensuado (nodo `tipos_de_aplicacion`, enlace
  `tipo_app` en el dueño). Falta ver el código antes de arrancar.

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

**Estado del proyecto al cierre:** v1.5piloto.73. Todo funcional. Listo
para arrancar la diversificación por tipo de aplicación.

---

**FIN DEL PROMPT**