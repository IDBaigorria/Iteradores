# Plan actual — Ubicación del proyecto

Este archivo ubica al agente en el **punto exacto** del
proyecto. No duplica información de los otros prompts:
para el detalle, referenciar.

**Reglas:**

- Se actualiza al cerrar **cada tanda**.
- Es lo primero que hay que leer para retomar el trabajo.
- El detalle de cada tema vive en su prompt:
  - Estructura de nodos, módulos, roles y frontend del
    piloto: `prompts/prompt_piloto.md`.
  - Framework Iteradores (clases, persistencia):
    `prompts/prompt_framework_iteradores.md`.
  - Seguridad topológica, contextos, compartidos:
    `prompts/prompt_seguridad.md`.
  - Sistema de scripts de aplicación de cambios:
    `prompts/prompt_sistema_scripts_v2.md`.
  - Plugin de pruebas (Chrome MV3):
    `iteradoresJS/prompts/prompt_plugin_piloto.md` y
    `estructura_plugin.md`.
- Las lecciones aprendidas se archivan en cada prompt según
  su tema. Este archivo no las acumula.

---

## 1. ESTADO ACTUAL

**Última actualización:** v1.5piloto.83l (bug §4.7 cerrado
con evidencia: `id_fila` del dueño estable entre requests,
cero `mf=0 modo=total` en el log durante el ciclo completo
venta/cancelación/cupón, auditoría topológica limpia después
de la limpieza). Causa raíz: guardado total en cada request
autenticado por `validar_token_sesion` y otros 9 llamadores
de `en_grafo_credenciales`, más 2 bloques de bootstrap en
`index.php`. Fix: 10 funciones de solo lectura a
`en_grafo_credenciales_solo_lectura`, condicionar el bloque
del `aplicacion`, condicionar el bootstrap del admin, y
agregar un flag de "hubo cambios" al `finally` de
`en_grafo_credenciales`. Ver §4.7).

**Antes (v1.5piloto.82i):** piso en los flujos que faltaban
(cambiar de asiento desde Clientes, impresión de
pasaje_reserva y croquis impreso del micro). Ver §2.

**Antes (v1.5piloto.82d):** cierre del arco de cancelaciones en
compartidos + bug §4.6 de persistencia silenciosa en hostings
compartidos.

El arco de cancelaciones en compartidos y el bug §4.6
quedaron resueltos. Estado en pruebas (InfinityFree):

  - guardar_total.ok:        true, 551ms
  - guardar_parcial.ok:      true, 592ms
  - Cancelación desde carmen2: persiste
  - Auditoría topológica:    0 críticos / 0 errores / 0 warnings

**Antes (v81o):** fix de `reparar_arboles` + cierre de
B3.3c. **Antes (v81g):** cierre del arco del visualizador
de grafo. **Antes (v79o):** cierre de la línea "grafo +
plugin + sistema de scripts". Carga parcial activa. La
topología sigue siendo la fuente de la seguridad: el bitmask
lo propaga el framework, no la app.

**Versiones:**

- **Piloto:** `1.5piloto.83l`.
- **Framework PHP:** `1.5i.7w` (SQL64, con singleton de
  conexión, `mysqli_report(OFF)`, `SELECT 1` en lugar de
  `ping()`, `IN (...)` en lugar de TEMPORARY TABLE, y
  log de persistencia a archivo del proyecto vía
  `Configuracion/LogPersistencia.php`). El
  archivo `Controlador/Controlador.php` está en `1.5i.7s`
  por los comandos del visualizador.
- **Framework JS:** `V1.5i.8c` (IndexedDB64). **Deuda
  pendiente:** espejar `_hmi` y los cambios de
  persistencia (C.7-C.10) al espejo JS.
- **Plugin de pruebas:** `v1.5plugin.9p`.

**Dónde estamos parados:**

El piloto tiene el modelo topológico completo (usuarios,
compartidos, contextos, carga parcial), el visualizador de
grafo cerrado, la auditoría topológica disponible
(`?auditar_topologia=1`, comando `app:auditar_topologia`,
botón en el plugin) y todas las reparaciones idempotentes
(`?reparar_topologia=1`, `?reparar_arboles=1`,
`?reparar_cancelaciones=1`, `?reparar_cancelaciones_arboles=1`,
`?eliminar_huerfanos=1`). La seguridad sigue emergiendo de
la topología del grafo: el bitmask lo propaga el framework,
no la app.

**Sin bugs abiertos de prioridad alta.** El bug §4.7
(guardado total en cada GET) está cerrado con evidencia.
La deuda técnica del arco está consolidada en §4.8.

La pestaña Grafo tiene dos sub-pestañas (Tabla y Visual), con
Visual como default. La sub-pestaña Visual es un SVG puro con
layout Forzar por defecto, drag con física, panel lateral y
tres modos de tamaño.

El plugin de pruebas tiene la UI reorganizada por pestañas
temáticas y la persistencia de corridas en su propio grafo
(estructura `pruebas → <id_prueba> → cadena de corridas`).
66 pruebas en verde al último cierre registrado.

---

## 2. ARCOS CERRADOS RECIENTES

Resumen de 1-2 líneas por arco. El detalle completo vive en
los prompts correspondientes o en los commits.

- **Bug §4.7 cerrado (v83j-v83l).** El bug no era el leak
  lento ni la migración de pasajeros. Era guardado total en
  cada request autenticado. `validar_token_sesion` (y otras
  9 funciones de solo lectura) llamaban `en_grafo_credenciales`
  con el default, que disparaba `guardar_ambos(app)` y
  `guardar_ambos(credenciales)` en cada GET. Cada guardado
  total hacía DELETE ALL + INSERT ALL con `id_fila` nuevo.
  El `id_fila` del dueño cambiaba en cada request. Las
  aristas del próximo BFS apuntaban a filas inexistentes.
  El viaje "desaparecía" y el header mostraba "terminal".
  Fix: 10 funciones a `solo_lectura`, condicionar el
  `aplicacion`, condicionar el bootstrap del admin, y flag
  de "hubo cambios" en el `finally` de
  `en_grafo_credenciales`. Ver §4.7 para el diagnóstico
  completo.

- **Fix del croquis multi-piso (v1.5piloto.82h).** El croquis
  y todas las acciones de asiento matcheaban por
  `(fila, columna)` sin `piso`. En un micro con pisos que
  comparten filas/columnas (ej. piso 1 filas 1-3, piso 2
  filas 1-12), los asientos del piso 1 pisaban visualmente a
  los del piso 2 en las mismas coordenadas. Fix: `piso` en
  `obtener_estados_asientos_micro` y en todos los matcheos
  del frontend + backend. Las 5 acciones de asiento y
  `cambiar_asiento_pasaje` aceptan `?string $piso = null`
  (o `piso_vieja`/`piso_nueva`) y matchean por
  `(piso, fila, columna)` si viene. **Pendiente:** el flujo
  de "cambiar de asiento desde la pestaña Clientes"
  (`pasajeros.js`) no pasa `piso`; queda con el bug hasta
  que se adapte.
- **Piso en los flujos que faltaban (v1.5piloto.82i).**
  Cierre de los pendientes de la Tanda del croquis (v82h).
  `formatear_venta_completa` ahora incluye `piso` en cada
  asiento (precalculando un mapa `id_asiento → piso` una
  sola vez por venta). `pasajeros.js` pasa `asientoInfo.piso`
  a `abrir_modal_cambiar_asiento`. `imprimir_pasaje_reserva`
  acepta `?string $piso` y matchea por `(piso, fila, columna)`;
  `index.php` lo lee del GET. `imprimir_croquis_micro` usa
  clave `piso-fila-columna` en el mapa de pasajeros, así el
  croquis impreso del micro de 2 pisos ya no pierde los
  pasajeros de las filas 1-3 del piso 2. `viajes-asientos.js`
  pasa `piso` a la impresión desde reservar/asignar/cambiar.
  **Sin pendientes del bug de piso.**
- **Migración de pasajeros entre micros — Tanda 1
  (v1.5piloto.82e).** Nueva función
  `migrar_pasajeros_de_micro` en `ViajeMicros.php` +
  subacción `viajes/migrar_pasajeros` en el Enrutador.
  Mueve asientos vendidos/reservados de un micro a otro
  dentro del mismo viaje, mapeando por número de asiento.
  Todo o nada: si un chequeo previo falla, no se toca el
  grafo. Frontend y tests pendientes (Tandas 2 y 3). Ver
  `prompts/plan_migrar_micro.md`.
- **Cancelaciones en compartidos + persistencia robusta
  (v81t-v82d).** Escritura/lectura simétricas para el árbol
  de cancelaciones (parametrizado en el compartido, default
  en el dueño), cycle guard en lectura, `_hmi` idempotente
  + comando de reconstrucción de cadenas con ciclos, check
  de `guardar_ambos` en `cancelar_venta`, y saga de
  persistencia: singleton de conexión, `mysqli_report(OFF)`,
  `SELECT 1` en lugar de `ping()`, sin `CREATE TEMPORARY
  TABLE` (usa `IN (...)` directo). Cierra el bug §4.6. Ver
  §4.6, `prompt_seguridad.md` §8.10 y
  `prompt_framework_iteradores.md` §6.7.
- **Auditoría topológica (v81p-v81s).** Nuevo endpoint
  `?auditar_topologia=1`, comando `app:auditar_topologia`,
  botón en el plugin (`🔍 Auditar topología`). 14
  verificaciones en 3 severidades. Reporte persistido en el
  grafo `plugin_auditorias` con 1 nodo JSON por auditoría.
  Endpoints complementarios: `?eliminar_huerfanos=1`,
  `?reparar_cancelaciones=1`,
  `?reparar_cancelaciones_arboles=1`. Ver
  `prompt_piloto.md` §3.5 y `prompt_plugin_piloto.md` §12.
- **Fix de `reparar_arboles` (v81n).** El precalcular
  indexa por nombre de enlace (`p`, `hd`, `p_<term>`,
  `hd_<term>`), no por nombre fijo. Antes, para un
  compartido, `$por_padre[$id_cont]` venía vacío y
  `_reparar_arbol` borraba el `hmi_<term>`. Eso rompía
  el árbol del compartido: las ventas viejas quedaban
  huérfanas y el terminal solo veía las nuevas.
- **B3.3c — 3 helpers que iteraban `usuarios` (v81j-v81m).**
  `obtener_cancelacion_por_id` acepta dueño/terminal.
  `obtener_pasajero_por_dni` navega `us_<dueño>` directo
  (fix de privacidad). Frontend de `ventas.js` manda
  `nombre_dueno` en 4 fetch (v81k). Rediseño `soporte`
  singular → `soportes` contenedor plural (v81l). **Fase B
  del modelo topológico completa.** Detalle en
  `prompt_seguridad.md` §8.6.
- **Visualizador de grafo (v80n-v81g).** Nueva sub-pestaña
  Visual con SVG puro. Detalle en `prompt_piloto.md` §3.4.
  **Pendientes abiertos:** Fase 5 (persistencia con
  `iteradoresJS` en cliente), colores para tipos inferidos
  por enlace, tamaño del patrón de la grilla adaptativo al
  zoom (opcional).
- **UI del plugin (v1.5plugin.7p-7x).** Pestañas temáticas,
  grupos por rol, botones contextuales de limpieza. Detalle
  en `iteradoresJS/prompts/prompt_plugin_piloto.md` §9.
- **Grafo del plugin (v1.5plugin.8b-8f).** Rediseño del
  grafo de corridas: `pruebas → <id_prueba> → cadena`.
  Estado "corriendo" como autoenlace. Mensajes nuevos
  (`estado_pruebas`, `corrida_iniciada`, `corrida_terminada`).
  Detalle en `iteradoresJS/prompts/estructura_plugin.md` y
  en el prompt del plugin §9.
- **Carga parcial (v78b-v79a).** Aplicada. La app ya usa
  `cargar_parcial`/`guardar_parcial` en los módulos
  principales. Detalle en `prompt_seguridad.md` §2-5.
- **Modelo topológico (Fases A, B1-B3).** Aplicado. Detalle
  en `prompt_seguridad.md` §5 y `prompt_piloto.md` §8.7.
- **Framework SQL64 (1.5i.7k-8f).** Activo. Bugs §1.5,
  §1.5.7, §1.5.8, §1.5.10, §1.5.15 y §1.5.16 resueltos.
  La versión 1.5i.8d-f agregó la persistencia robusta para
  hostings compartidos. Detalle en
  `prompt_framework_iteradores.md` §6.7 y §11.4.

---

## 3. DECISIONES DE DISEÑO EN VIGOR

Solo las que aplican a cualquier tanda nueva. Las decisiones
de cada módulo viven en su prompt.

- **SQL64 es el método de persistencia activo.** Las tres
  tablas nuevas (`nodo_contexto`, `adyacente_contexto`,
  `contexto`) con esquema `id_fila` / `id_especial`.
- **La seguridad emerge de la topología.** El bitmask lo
  propaga el framework, no la app. La app nunca asigna bits
  a mano.
- **Rediseño `soportes` plural (v1.5piloto.81l).** El dueño
  tiene un contenedor `soportes` (dato vacío) con los
  `publico` de cada soporte asignado como hijos. Reemplaza
  al enlace singular `soporte`. Permite varios soportes por
  dueño. El orden en `agregar_usuario` importa:
  `_asegurar_niveles_usuario` antes de
  `_asignar_duenos_a_soporte`.
- **Carga parcial activa.** Toda operación POST con
  `nombre_solicitante` carga parcial con `[us_<sol>]`.
  Excepciones puntuales: gestión de usuarios, informes que
  usan los 7 helpers de B3.3c.
- **Soft delete coordinado para nodos multi-contexto.** Un
  nodo con bits de varios contextos no puede ser destruido
  por uno solo. Se marca con autoenlace (`cancelada`) y lo
  destruye el contexto que tenga la vista completa. Aplica
  a ventas Y cancelaciones.
- **Inserción diferida con buzones.** Un contexto que no
  puede insertar en el árbol del otro deja un aviso en un
  buzón del compartido. El otro lo drena en su próxima
  carga. Aplica a ventas Y cancelaciones.
- **Árboles paralelos para cancelaciones (v1.5piloto.81t).**
  El árbol de cancelaciones del compartido usa nombres
  parametrizados (`hmi_<term>`); el del dueño usa default.
  Ver `prompt_piloto.md` §5.17.
- **Comandos del framework vs de la app.** Los del framework
  (`grafo:*`, `comunicacion:*`, etc.) van en el
  `Controlador`. Los de la app van en `Aplicacion/`,
  registrados desde `index.php`.
- **Espejo PHP ↔ JS.** Cualquier cambio al framework PHP se
  refleja en JS en la misma tanda, con dos scripts y dos
  commits. **Deuda pendiente:** `_hmi` (C.4b) y persistencia
  (C.7-C.10) todavía no están espejados.
- **Cada cambio al piloto lleva su espejo de pruebas en el
  plugin.** Salvo excepción justificada (estilos, refactor
  sin cambio de comportamiento, herramientas de admin).

---

## 4. PENDIENTES CONSOLIDADOS

Agrupados por naturaleza. Prioridad estimada entre
paréntesis.

### 4.1. Piloto — calidad y consistencia (media)

- [x] **Adaptar los 4 módulos menores al contexto del
  solicitante** — CERRADO (v81h + v81i, ver §6).
  `Empresa.php` y `Vehiculo.php` ya estaban adaptados
  (fases v76u-v77v); `Liquidacion.php` y `Rendicion.php`
  se adaptaron en v81h. El frontend del piloto no mandaba
  `nombre_dueno` en `rendiciones/obtener`,
  `rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`
  (bug v81i); solo fallaba con soporte. Corregido en
  `rendiciones.js` y `liquidaciones.js`.
- [x] **B3.3c — 3 helpers que iteraban `usuarios`** —
  CERRADO (v81j + v81k + v81l, ver §2). `_buscar_venta_por_id`
  queda como fallback defensivo (no aplica en la práctica);
  pendiente anotado para eliminar cuando `usuarios` sea
  contenedor de nombres. `obtener_cancelacion_por_id` y
  `obtener_pasajero_por_dni` adaptados al patrón
  `?string $nombre_dueno`. Rediseño `soporte` → `soportes`
  contenedor plural en v81l.
- **Auditar estados colgados:** reservas sin pasajero, DJ a
  medias, edición de viaje a mitad. Requiere relevamiento
  primero.
- **Auditar estructuras nuevas** con la regla de detección
  de `prompt_seguridad.md` §5.9. Preventivo.
- **Deuda de seguridad en impresión.** Las URLs
  `?imprimir=1&tipo=...` no validan sesión ni permisos.
  Cualquiera con la URL ve los datos. Preexistente.

### 4.2. Plugin — mejoras (baja)

- **Persistencia de la pestaña activa del plugin.**
  Migrar de `localStorage` a IndexedDB. Anotado en el
  código como `ANOTADO`.
- **Pruebas 37 y 38 sin `try/finally`.** Si el flujo falla
  a mitad, la empresa/vehículo de setup queda colgada.
  Los limpiadores la agarran, pero la prueba debería
  limpiar por su cuenta.
- **Centralizar prefijos de prueba.** Hoy cada limpiador
  tiene su lista hardcodeada. Una constante compartida, o
  que las pruebas registren su prefijo.
- **Secciones vacías del plugin:** Rendiciones,
  Liquidaciones. Esperan pruebas nuevas.
- **Linting del payload del plugin:** verificar que
  ninguna llamada a `_post` a acciones que requieren
  `nombre_dueno` salga sin él.

### 4.3. Framework Iteradores (baja, tanda propia)

Van todos juntos en una sesión del framework.

- **Dejar el Nodo limpio.** Mover `contexto_mascara` a la
  capa de persistencia antes de agregar más métodos de
  indexación (256 bits, producto de primos). Bloqueante de
  la Fase 5. Ver `prompt_framework_iteradores.md` §11.4.
- **Fase 3: JSON64 / XML64** con `guardar_parcial`.
- **Fase 4: `guardar_parcial` real por delta** (no merge
  en memoria).
- **Fase 5: 256 contextos / producto de primos** (si hace
  falta).
- **Iteradores con ID especial + ocupante** como raíces de
  subgrafo. Resolvería los 7 helpers de B3.3c. Ver
  `prompt_framework_iteradores.md` §11.5.
- **Espejar `_hmi` a JS** (`iteradoresJS/miscelaneas/Arbol.js`).
  El fix de C.4b está solo en PHP. Ver §4.7.
- **Espejar C.7-C.10 a JS**
  (`PerdurarSuperestructuraStringIndexedDB64.js`). Ver §4.7.

### 4.4. Visualizador de grafo (baja)

- **Fase 5: persistencia.** Guardar posiciones, zoom y
  layout elegido. Va cuando integremos `iteradoresJS` en el
  cliente del piloto. Es una decisión de arquitectura, no
  del visualizador.
- **Colores para tipos inferidos por enlace.** Los tipos
  del backend ya tienen color. Los inferidos por enlace
  (`usuarios`, `empresas`, `viajes`) caen a gris. Agregar
  paleta cuando se use con datos reales.
- **Tamaño del patrón de la grilla adaptativo al zoom**
  (opcional). Hoy el patrón mide 40×40 user units fijo.

### 4.5. Próximas tandas sugeridas

**Arco en curso: migración de pasajeros entre micros.**
**ESTADO: DESBLOQUEADA.** El bug §4.7 está cerrado
(v1.5piloto.83l). El `id_fila` es estable entre
requests, el grafo no se corrompe. Se puede retomar
la Tanda 3.

Plan completo en `prompts/plan_migrar_micro.md`.

- **Tanda 1 (backend): cerrada en `1.5piloto.82e`.**
  `migrar_pasajeros_de_micro` en
  `Aplicacion/Viajes/ViajeMicros.php` + subacción
  `viajes/migrar_pasajeros` en `Aplicacion/Enrutador.php`.
  **Aplicada en producción antes de conocer el bug
  §4.7.** No fue la causa del bug, pero la secuencia
  agregar micro → migrar → eliminar micro viejo → pagar
  cupón lo disparó. La base se restauró desde backup y
  se aplicaron las reparaciones idempotentes.
- **Tanda 2 (frontend): cerrada en `1.5piloto.82f`.**
  Botón "Migrar pasajeros a..." en la tarjeta del micro (solo
  visible si el micro tiene asientos ocupados y el usuario
  no es terminal) + modal apilado con select de micros
  vacíos, aviso de layouts distintos, confirm() nativo y
  refresco post-migración (cierra o refresca el panel de
  pasaje según qué micro estaba abierto).
- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin
  + actualización de `aplicacion_POST.php`,
  `prompt_piloto.md` §5.8 y §4.9, y este archivo.

**La migración YA PUEDE VOLVER A PRODUCCIÓN.** El bug
§4.7 está cerrado (v1.5piloto.83l). Antes de pasarla,
correr la Tanda 3 en local y validar el ciclo completo
en un viaje de prueba.

**Foco inmediato: Piloto — calidad.**

Con el arco de cancelaciones + persistencia robusta cerrado
(v81t-v82d), el foco vuelve a los pendientes de calidad del
piloto (§4.1). Candidatos, en orden tentativo a definir
con el usuario:

1. **Auditar estados colgados.** Reservas sin pasajero,
   DJ a medias, edición de viaje a mitad. Requiere
   relevamiento primero. Empezar por "cierre de navegador
   a mitad de venta".
2. **Auditar estructuras nuevas** con la regla de
   detección de §5.9 del prompt de seguridad. Preventivo.
3. **Deuda de seguridad en impresión.** Las URLs
   `?imprimir=1&tipo=...` no validan sesión ni permisos.
   Cualquiera con la URL ve los datos. Preexistente.

**Después, según prioridad:**

- **Limpieza de deuda técnica del arco** (§4.7): endpoints
  temporales, espejo JS de `_hmi`, `error_log` de
  diagnóstico.
- **Migrar persistencia de la pestaña activa del plugin**
  (`localStorage` → IndexedDB).
- **Visualizador de grafo, Fase 5** (persistencia de
  posiciones, zoom y layout).
- **Framework Iteradores:** dejar el Nodo limpio,
  JSON64/XML64, `guardar_parcial` por delta, iteradores
  con ID especial.
- **Diversificación por tipo de aplicación** (`tienda`).

### 4.6. Bug cerrado: persistencia silenciosa en hostings compartidos

**Estado:** cerrado en v1.5piloto.82d. No era un bug del
flujo de cancelaciones, era persistencia fallida en
hostings compartidos. Detalle completo de la causa raíz y
los fixes en `prompt_seguridad.md` §8.10.

**Síntoma.** En InfinityFree, una cancelación desde un
terminal respondía `exito: true` pero no se persistía. En
local, el mismo flujo funcionaba.

**Causa raíz (4 capas):**

1. **`CREATE TEMPORARY TABLE` no permitida.**
   InfinityFree no le da el privilegio al usuario de la
   DB. `_guardar_con_filtro` la usaba para el DELETE de
   las filas cargadas y fallaba con "Access denied".
2. **`mysqli_report` strict activo (PHP 8.4).**
   Cualquier fallo de mysqli lanza excepción en lugar de
   devolver `false`. El `@` silencia warnings pero no
   excepciones.
3. **Rate limit de conexiones.** El flujo de
   `cancelar_venta` abría 4-5 conexiones por request.
   InfinityFree limita las conexiones por minuto.
4. **`cancelar_venta` no chequeaba `guardar_ambos`.**
   Cuando `guardar_parcial` fallaba, la UI decía "venta
   cancelada" igual.

**Fixes (saga C.6-C.10):**

- C.6 (v81w): `cancelar_venta` chequea `guardar_ambos`.
  Si falla, `exito: false`.
  `_reparar_ventas_canceladas_incompletas` detecta ventas
  con cancelación pero sin autoenlace `cancelada`.
- C.7-C.8 (v81z-82a): singleton de conexión. Una sola
  conexión física por request.
- C.9-C.9b (v82b-82c): `mysqli_report(OFF)` + `SELECT 1`
  en lugar de `ping()`.
- C.10 (v82d): sin `CREATE TEMPORARY TABLE`.
  `IN (...)` directo.

**Lecciones registradas en `prompt_seguridad.md` §8.10.**

**Verificación en pruebas (post-fix):**

  - guardar_total.ok:        true, 551ms
  - guardar_parcial.ok:      true, 592ms
  - Cancelación desde carmen2: persiste
  - Auditoría topológica:    0 críticos / 0 errores / 0 warnings

### 4.7. Bug §4.7: cerrado (era guardado total en cada GET)

**Estado:** cerrado en v1.5piloto.83l. Validado con el ciclo
completo: `id_fila` del dueño estable entre requests, cero
`mf=0 modo=total` en el log, auditoría topológica limpia
después de la limpieza.

**Diagnóstico real (sesión v83i-v83l).** El bug NO era el
leak lento (aunque Fase 4 fue necesaria para hacerlo
visible). NO era la migración de pasajeros. NO era la
concurrencia per se. Era que **cada request autenticado
hacía dos guardados TOTALES**:

1. `validar_token_sesion` (Autenticacion.php:134) se llama
   en cada request. Llamaba a `en_grafo_credenciales` con el
   default, que disparaba `guardar_ambos($nombre_app)`
   **antes** del callback y `guardar_ambos($credenciales)`
   **en el finally**.
2. Otros 9 llamadores de solo lectura (`listar_sesiones`,
   `listar_usuarios`, `listar_terminales_de_dueno`,
   `obtener_perfil_usuario`, `_puede_cerrar_sesion`, etc.)
   hacían lo mismo.
3. El bloque del `aplicacion` (index.php:253-275) guardaba
   en cada GET si faltaba `migraciones`. Como el guardado
   total reinsertaba `migraciones` con `id_fila` nuevo, en
   el próximo GET volvía a faltar → loop infinito.
4. El bloque del admin en credenciales (index.php:639-655)
   guardaba aunque el admin ya existiera.

Cada `DELETE ALL + INSERT ALL` reinsertaba el grafo entero
con `id_fila` auto-increment. El `id_fila` del dueño
cambiaba en cada request. Las aristas del BFS del próximo
request apuntaban a filas que ya no existían. El viaje
viejo "desaparecía" y el header mostraba "terminal" en vez
de "dueño".

**Fix (v83j-v83l).**
- v83j: `en_grafo_credenciales` acepta `bool
  $guardar_app_antes = true`. El bootstrap del admin pasa
  `false`.
- v83k: `en_grafo_credenciales` solo guarda credenciales si
  el callback no devolvió exactamente `false`. El callback
  del bootstrap del admin devuelve `$creado`.
- v83k: el bloque del `aplicacion` solo guarda si creó algo
  en este request (flag `$hubo_cambio_app_meta`).
- v83l: las 10 funciones de solo lectura pasan a
  `en_grafo_credenciales_solo_lectura`. Corta el 90% de los
  guardados totales. Es el fix de raíz.

**Cobertura.** Las funciones que sí modifican (autenticar,
crear/cerrar sesión, agregar/actualizar/eliminar usuario,
eliminar terminal, eliminar sesiones) siguen con
`en_grafo_credenciales`. Solo se cambiaron las de lectura.

**Estado post-fix (validado por el usuario).**
- `id_fila` del dueño: estable entre recargas, ventas,
  cancelaciones, pagos de cupón.
- Log: cero `mf=0 modo=total`, cero
  `nombre=credenciales`. Solo guardados parciales de las
  operaciones reales.
- Auditoría topológica post-limpieza: 0 críticos, 0
  errores, 0 warnings.

**Histórico (sesión v83a-h, conservado para contexto).**
Lo que sigue documenta la investigación que precedió al
diagnóstico real. Fue necesaria (Fase 1-4 del framework,
cobertura de `guardar_ambos` en 25 flujos, blindaje de
persistencia robusta) pero el trigger real estaba en otro
lado.

**Resultado de la sesión de diagnóstico (v1.5piloto.83a-f).**
El bug no se reproduce post-restauración + blindaje. Los
20+ guardados parciales de la sesión pasaron todos con
`verif_mascaras ok=1`, `verif_nodos ok=1`, `verif_aristas ok=1`.
Cero `PERDIDA`, cero `FALLO`. La DB quedó consistente.

**4 bugs encadenados del flujo de venta en micro de 2 pisos
que SÍ se encontraron y cerraron:**
- Falso positivo del blindaje con `_destruir_venta_actual`
  (1.5i.7u): la verificación de máscaras marcaba como
  "PERDIDA" a los 5 nodos que se destruyen legítimamente al
  confirmar la venta. Fix: `if (!Nodo::existe($id_nodo_prev))
  continue;`.
- `confirmar_venta_actual` sin chequear `guardar_ambos`
  (v1.5piloto.83d): el backend respondía `exito: true` con la
  DB intacta cuando el guardado fallaba. El usuario veía
  "Venta confirmada" con el asiento todavía "seleccionado"
  (por el polling) e "imprimir pasaje" devolvía "venta no
  encontrada". Fix: patrón C.6.
- `pagar_cupon_venta` sin chequear `guardar_ambos`
  (v1.5piloto.83d): mismo patrón. Fix aplicado.
- Impresión de ventas para terminal sin `nombre_terminal`
  (v1.5piloto.83e): las URLs de impresión llevaban `id_venta`
  y `nombre_dueno` pero no `nombre_terminal`. El backend
  buscaba en el árbol default del dueño (donde no está la
  venta del compartido). Fix aplicado en 5 URLs +
  `generar_impresion` + `index.php`.

**Hueco del blindaje (deuda de la Fase 4).** El blindaje
atrapa la pérdida de bits, pero NO atrapa:
- Nodos que **nunca tuvieron bits** (creados con máscara 0,
  o cargados así de la DB). El check `if ($mf !== 0 &&
  ($mascara & $mf) === 0) return;` los salta del INSERT sin
  avisar.
- Aristas que apuntan a esos nodos: también se saltan.

Ese es el **leak lento acumulativo** que produjo los 1817
nodos con máscara 0 del estado original. Se necesita Fase 4
(fix real) para cerrarlo. Ver "Fase 4" más abajo.

**Mitigación operativa mientras Fase 4 no esté:**
- Correr `?auditar_topologia=1` periódicamente (semanal o
  mensual) para detectar acumulación de huérfanos.
- Correr `?reparar_topologia=1` + `?reparar_arboles=1` si
  aparecen huérfanos.
- Si en el futuro "desaparece un viaje", seguir el mismo
  procedimiento de diagnóstico de esta sesión: restaurar,
  migrar, auditar, reparar.

**Fase 4 cerrada (framework 1.5i.7v).** Tres cambios:
1. **Fixpoint de máscaras sobre TODAS las aristas.** Se
   reemplaza el bloque de propagación a `$nuevos` (hasta
   10 iteraciones) por un fixpoint general (hasta 20).
   Los nodos que perdieron bits en el BFS del grafo
   parcial, o que nunca los tuvieron, recuperan los bits
   de sus vecinos por transitividad. Si no converge en 20
   iteraciones, `_error` (defensivo).
2. **Insertar nodos con máscara 0.** Antes se skipeaban del
   INSERT. Ahora se insertan con máscara 0, preservando
   `id_fila`. Cierra los duplicados entre requests: el
   nodo tiene id_fila estable desde el primer guardado.
   El chequeo de "no hay nodos del filtro" ahora usa
   `$ids_matchean_filtro` (máscara > 0 que matchea), no
   `$ids_a_insertar` (que incluye los de máscara 0).
3. **Aristas hacia destinos con máscara 0.** Antes se
   skipeaban por `($m_d & $mf) === 0`. Ahora solo se
   skipea si el destino NO está en `$mapa_final`. Las
   aristas entre nodos persistidos se insertan siempre,
   aunque el destino tenga máscara 0.

**Estado del leak lento: cerrado de raíz.** Los nodos en
memoria con máscara 0 se persisten con su id_fila. El
fixpoint propaga bits por transitividad. El próximo
guardado no duplica.

**Verificación pendiente (usuario).** Correr el ciclo
completo (migración de pasajeros + venta + cupón) con
`Conf::DEBUG_PERSISTENCIA = true`. Esperado:
- `matchean_filtro > 0` en todos los guardados parciales.
- `nodos_mascara_0 = 0` en el ciclo normal.
- Si aparece `nodos_mascara_0 > 10` de forma persistente,
  investigar por qué esos nodos no son alcanzables desde
  ninguna raíz. Pueden ser residuos reales (basura) que
  conviene limpiar con `?eliminar_huerfanos=1`.

**Historial del diagnóstico (sesiones):**
- **Fase 0** (v1.5piloto.83a): anotar el bug y congelar
  producción.
- **Fase 1+2** (framework 1.5i.7t): blindaje del guardado
  parcial + logging de diagnóstico.
- **Fase 3** (v1.5piloto.83c-f): falso positivo del blindaje,
  los 4 bugs encadenados del flujo de venta, y esta sesión
  de reproducción que no logró reproducir el bug.

**Estado del bug:** cerrado en v1.5piloto.83l. El leak
lento del blindaje fue cerrado de raíz en Fase 4
(framework 1.5i.7v). El trigger real (guardado total en
cada GET) fue cerrado en v83j-v83l. El blindaje de
1.5i.7t/7u sigue activo (rollback si pierde bits, nodos
o aristas).

**Estado histórico:**

**Síntoma.** Después de una secuencia de operaciones
(agregar micro de 2 pisos → migrar pasajeros del micro
viejo al nuevo → eliminar micro viejo → pagar cupón), el
viaje desaparece de la lista del dueño. La terminal
también deja de verlo. El header del dueño muestra
"terminal" en lugar de "dueño".

**Causa raíz (parcial, en investigación).**
`_guardar_con_filtro` (en
`PerdurarSuperestructuraStringSQL64.php`) es un
DELETE+INSERT que filtra por máscara. Cuando el grafo
tiene aristas que cruzan el borde (origen en el filtro,
destino fuera), las aristas pueden quedar huérfanas y los
nodos con máscara 0 no se persisten. La Fase 4 del
framework (§11.4 del prompt del framework) documenta que
el guardado parcial real por delta no está implementado.
El bug estaba latente desde antes de C.10 (el parche de
`IN (...)` no es la causa); la secuencia específica lo
disparó.

**Evidencia en la DB (antes de restaurar):**
- `us_Parroquia_del_Carmen` con máscara `16` (solo bit 4,
  el dueño). Le faltaban los bits 0-3 (aplicacion,
  admin, usuarios, sesiones).
- El viaje `166694` huérfano: nadie lo apuntaba desde el
  contenedor `viajes` del dueño.
- `contexto_mascara = 0` en 1817 nodos.
- Header del dueño con "terminal": perdió el enlace
  `nivel`.
- `id_fila` del dueño cambiados entre requests
  (`165810` → `190041`): duplicados por guardado parcial.

**Estado post-restauración (v1.5piloto.82i):**
- Backup SQL viejo (`nodo` + `adyacente`) restaurado.
- `?migrar_a_sql64=1` corrido.
- `?reparar_topologia=1`, `?reparar_arboles=1`,
  `?reparar_cancelaciones=1`,
  `?reparar_cancelaciones_arboles=1` corridos.
- Verificación: dueño con máscara `116` (bits 2, 4, 5,
  6). Solo 3 nodos huérfanos (residuos históricos, no
  miles). App funcionando.

**Plan de diagnóstico y blindaje (6 fases).**

**Fase 0 — Anotar el plan y congelar producción.**
- [x] Registrar este bug en `plan_actual.md`.
- Reglas operativas mientras esté abierto:
  - No aplicar migración de pasajeros.
  - No aplicar Tanda 4 del arco de piso (v82i).
  - Operación normal (vender, cancelar, pagar cupones)
    permitida. Si una operación deja el viaje huérfano,
    NO repetirla; anotar la secuencia exacta.

**Fase 1 — Blindaje (red de seguridad). CERRADA en 1.5i.7t.**
- `_guardar_con_filtro` ahora hace rollback si:
  - Un nodo con máscara vieja > 0 pierde bits en el BFS.
  - Algún `id_fila` insertado no queda en DB.
  - Alguna arista calculada no queda en DB.
- Pre-DELETE: lee las máscaras viejas de los nodos a
  borrar. Guarda el mapa inverso `id_fila → id_nodo`.
- Post-INSERT: verifica. Si algo falla, `throw` →
  `rollback` → `false`. Sin reintento automático (reintentar
  con `guardar()` total pisaría todo el grafo desde la
  memoria parcial, catastrófico).
- Costo: 3 SELECTs extra por guardado parcial. Estimado
  +200-500ms con ~10k nodos.
- Beneficio: si el bug vuelve a pasar, falla con error
  en lugar de corromper en silencio.

**Fase 2 — Logging de diagnóstico. CERRADA en 1.5i.7t.**
- Flag `Conf::DEBUG_PERSISTENCIA = false` (default).
- Log a `error_log` con prefijo `[SQL64_GUARDAR]`:
  - Cabecera: `nombre`, `mf`, `modo` (parcial/total),
    `nodos_en_memoria`, `nodos_previos`, `filas_cargadas`,
    `mascaras_viejas`.
  - `ids_a_insertar`, `nodos_mascara_0` (con hasta 20 IDs
    de ejemplo).
  - `aristas_a_insertar`.
  - Resultado de `verif_mascaras`, `verif_nodos`,
    `verif_aristas`.
  - Si una máscara pierde bits: `PERDIDA id_nodo=... id_fila=...
    vieja=... nueva=...`.
- Solo activo en local. En producción se deja en `false`
  (agrega ~50-100ms por guardado).

**Fase 3 — Reproducir el bug en local. EN CURSO.**
- Con el backup restaurado + migración + log activo.
- Secuencia probada: vender un pasaje en el micro de
  2 pisos. El blindaje abortó con rollback.
- **Diagnóstico del aborto:** falso positivo. La
  verificación de máscaras marcó como "PERDIDA" a los
  5 nodos que `_destruir_venta_actual` destruye
  legítimamente al confirmar la venta (`venta_actual`,
  cabeza de asientos, `asiento_venta`, `viaje`, `micro`).
  Esos nodos ya no están en `$mascaras` porque se
  borraron del grafo, entonces `$mascaras[$id] ?? 0` = 0.
  La verificación no distinguía "borrado" de "perdió
  bits".
- **Fix (1.5i.7u):** agregar
  `if (!Nodo::existe($id_nodo_prev)) continue;` antes de
  comparar máscaras. `Nodo::existe()` es O(1)
  (`isset(self::$superestructura[$id])`).
- **Importante:** el bug real §4.7 (nodo pierde bits en
  el BFS) **NO se reprodujo** en este flujo. Los guardar
  total del bootstrap (login, recarga) pasaron todos con
  `verif_nodos ok=1` y `verif_aristas ok=1`. La DB creció
  legítimamente por los nodos de sesión, no por
  corrupción. **Próximo paso:** volver a probar con el
  fix. Si el ciclo completo (migración + venta +
  cupón) no rompe, evaluar marcar §4.7 como "no
  reproducido post-restauración + blindaje". Si rompe,
  ahora sí el log va a mostrar el punto exacto.
- **Segundo bug detectado (1.5piloto.83d):**
  `confirmar_venta_actual` y `pagar_cupon_venta`
  llamaban `guardar_ambos` **sin chequear el
  resultado**. Cuando el guardado parcial fallaba (por
  el falso positivo del blindaje), el backend devolvía
  `exito: true` igual. El frontend mostraba "Venta
  confirmada" con la DB intacta. El asiento seguía
  "seleccionado" en el croquis (por el polling) y
  "Imprimir pasaje" devolvía "venta no encontrada".
  **Fix:** patrón C.6. Chequear `guardar_ambos`; si
  falla, devolver `exito: false` con error específico
  ("La venta se procesó en memoria pero no se pudo
  guardar en la base de datos. Revisá el log del
  servidor o intentá de nuevo.").
- **15 flujos con `guardar_ambos` sin chequear: CERRADOS
  en v1.5piloto.83g.** `reservar_asiento_micro`,
  `asignar_pasajero_a_reserva`,
  `liberar_reserva_asiento_micro`, `seleccionar_asiento_micro`,
  `deseleccionar_asiento_micro`, `cambiar_asiento_pasaje`,
  `agregar_micro_a_viaje`, `eliminar_micro_de_viaje`,
  `actualizar_monto_micro`, `eliminar_viaje`,
  `crear_pasajero`, `actualizar_pasajero`,
  `eliminar_pasajero`,
  `eliminar_declaracion_jurada_pasajero`,
  `subir_declaracion_jurada_pasajero`. Todos aplican
  el patrón C.6: si `guardar_ambos` falla, devuelven
  `exito: false` con error específico.
- **10 flujos restantes: CERRADOS en v1.5piloto.83h.**
  `agregar_viaje`, `editar_viaje`,
  `guardar_viaje_completo`,
  `agregar_terminal_autorizada`,
  `eliminar_terminal_autorizada`,
  `guardar_opciones_terminal_viaje`,
  `guardar_declaracion_jurada`,
  `limpiar_viajes_de_prueba`,
  `limpiar_pasajeros_de_prueba`,
  `limpiar_cancelaciones_de_prueba`. Todos aplican el
  patrón C.6.
- **Excepciones (no requieren fix):**
  - `migrar_pasajeros_de_micro`: ya cubierto (Tanda 1,
    1.5piloto.82e).
  - `limpiar_ventas_de_prueba`: no llama `guardar_ambos`
    directamente (cada `cancelar_venta` interno lo hace
    y ya está chequeado desde v81w).
  - `listar_viajes_de_dueno` (drenar buzón): lectura
    con side-effect. Si falla, se loguea pero se devuelve
    el listado igual.
- **Estado de cobertura: COMPLETA.** Todos los flujos
  del piloto que llaman `guardar_ambos` chequean el
  resultado.
- **Tercer bug detectado (1.5piloto.83e):** impresión de
  ventas para terminal. Las URLs `?imprimir=1&tipo=pasajes`
  y `tipo=cupon` llevaban `id_venta` y `nombre_dueno` pero
  NO `nombre_terminal`. `generar_impresion` llamaba
  `obtener_venta_por_id($id, null, $nombre_dueno)`, que
  busca en el árbol default del dueño. La venta de un
  terminal vive en el árbol parametrizado del compartido
  (`hmi_<term>`), así que no se encontraba → "Venta no
  encontrada". Dueño funcionaba porque su árbol es el
  default. **Fix:** `generar_impresion` acepta
  `?string $nombre_terminal`; `index.php` lee
  `$_GET['nombre_terminal']`; las 5 URLs del frontend
  (`mostrar_opciones_impresion` × 2, cuponera × 1,
  `ver_pasajes_pasajero` × 1,
  `ver_detalle_pasaje_individual` × 1) agregan
  `nombre_terminal` si el usuario es terminal.
- **Estado del bug §4.7:** el ciclo completo (venta en
  micro de 2 pisos + impresión de pasaje/cupón como
  terminal y como dueño) funciona post-83e. El blindaje
  de 1.5i.7t/7u no se dispara. La DB no se corrompe. Los
  4 bugs encadenados de Fase 3 (falso positivo del
  blindaje, venta sin chequear `guardar_ambos`, cupón
  sin chequear, impresión de terminal sin
  `nombre_terminal`) quedaron cerrados. La migración de
  pasajeros **sigue en pausa** hasta que el usuario
  valide un ciclo completo post-83e.

**Fase 4 — Fix real.**
- Depende del diagnóstico:
  - Si es "nodo nuevo con máscara 0 no se inserta": fix
    es calcular la máscara en el INSERT, no confiar en
    el BFS previo. O no saltar nodos con máscara 0 si
    son alcanzables desde un nodo insertado.
  - Si es "arista que cruza el borde no se reinserta":
    fix es reinsertar aristas aunque el destino no esté
    en `$mapa_final`, apuntando al `id_fila` previo.
  - Si es otro: depende.
- Aplicar el fix en PHP y espejar a JS en la misma
  tanda (dos scripts, dos commits).

**Fase 5 — Tanda 4 del arco de piso (v82i).**
- Después del blindaje y el fix. Independiente del bug
  §4.7. Se aplica cuando el guardado parcial esté
  blindado.

**Fase 6 — Migración de pasajeros a producción.**
- Después de la Fase 4. Con el blindaje activo y el fix
  aplicado.
- Probarla primero en un viaje de prueba, no en
  producción.

### 4.8. Deuda técnica del arco (baja)

Pendientes generados por la línea v81p-v82d. Ninguno es
bloqueante.

- **`GET_LOCK('grafo_<nombre>', 5)` en `_guardar_con_filtro`.**
  Para blindar la concurrencia real. Ahora que el guardado
  total no se dispara en cada GET, la concurrencia es
  mucho menos probable (solo operaciones reales se
  pueden solapar). Pero dos terminales que venden al
  mismo tiempo todavía pueden pisarse. Con GET_LOCK, el
  segundo espera o falla, pero no duplica. Es deuda
  técnica, no bug activo. Tanda del framework.

- **Endpoints temporales de diagnóstico en index.php.**
  - `?diag_persistencia=1` (C.6b).
  - `?diagnostico_cancelaciones=1` (C.4a).
  Ambos son seguros (solo admin/soporte, solo lectura),
  pero no deben quedar en producción. Tanda chiquita de
  limpieza.

- **Espejar `_hmi` a JS.** El fix de C.4b (idempotente +
  limpiar `hd` residual) está solo en
  `miscelaneas/Arbol.php`. El espejo
  `iteradoresJS/miscelaneas/Arbol.js` tiene la versión
  vieja. No urgente porque el plugin no usa `_hmi` sobre
  cancelaciones.

- **Espejar C.7-C.10 a JS.**
  `PerdurarSuperestructuraStringIndexedDB64.js` no tiene
  singleton de conexión ni check de `commit()`. IndexedDB
  no tiene el problema del `TEMPORARY TABLE` ni
  `mysqli_report`, pero sí se beneficia del singleton
  (menos transacciones). No urgente.

- **`\ConfiguracionApli::VERSION_APP` devuelve "0.0.0"
  en pruebas.** El diag lo muestra así. Probablemente el
  `ConfiguracionApli.php` de pruebas no tiene
  `VERSION_APP` configurada. Detalle menor.

- **`error_log("[CREAR_CONEXION] version=82c, ...")` en
  SQL base.** Era para diagnóstico. Ahora que se
  confirmó que funciona, se puede quitar. Se va con
  la limpieza de endpoints.

---

## 5. PLAN GLOBAL — HOJA DE RUTA

Mapa completo del proyecto. Se actualiza cuando una fase
se cierra o se abre una nueva. El detalle de cada fase
vive en el prompt correspondiente.

### 5.1. Modelo topológico del piloto

Plan de fases A → B → C → D, con la seguridad emergiendo
de la topología. Detalle de cada fase en
`prompt_seguridad.md` §3-5 y §8.6.

| Fase | Qué hace | Estado |
|---|---|---|
| **A** — Usuarios como IDs especiales `us_<nombre>` | Reemplazo de la navegación `usuarios → dueño` por `us_<dueño>`. | ✅ v76k |
| **B1** — Contenedores `publico`/`privado` | Cada usuario tiene contenedores por nivel de exposición. | ✅ v76m |
| **B2.1** — Compartidos por terminal | Contenedores `compartido_con_<terminal>` colgando del dueño. | ✅ v76q |
| **B2.2** — Contexto opcional | Viaje, Venta, ViajeAsientos y Empresa aceptan `?Nodo $nodo_contexto`. | ✅ v76r-v76u |
| **B2.3** — Árboles paralelos | Ventas con `hmi`/`hd`/`p` y `hmi_<term>`/`hd_<term>`/`p_<term>`. | ✅ v76v-v77o |
| **B2.4** — Mantener compartidos vivos | Sincronización en cada operación de escritura. | ✅ v77q |
| **B3.1** — Accesos viejos eliminados | `usuarios → dueño` → `us_<dueño>` en todos los módulos principales. | ✅ v77r |
| **B3.3a-e** — Módulos menores adaptados | Liquidacion, Rendicion, Empresa, Vehiculo, y Vehiculo JS. | ✅ v77t-v77x + v81h |
| **B3.3c** — 3 helpers que iteran `usuarios` | `_buscar_venta_por_id` (fallback defensivo), `obtener_cancelacion_por_id`, `obtener_pasajero_por_dni` (rama historial). | ✅ v81j-v81l |
| **C** — Tipos como IDs especiales (opcional) | Roots explícitos `tipo_empresas`, `tipo_viajes`, etc. | Pendiente |
| **D** — Carga parcial como optimización real | Guardado por delta. Bloqueada por framework Fase 4. | Bloqueada |

Con **B3.3c** cerrado, la **Fase B queda completa**.

### 5.2. Framework Iteradores

Plan de fases del framework. Detalle en
`prompt_framework_iteradores.md` §11.4 y §11.5.

| Fase | Qué hace | Estado |
|---|---|---|
| SQL64 (bitmask + 3 tablas nuevas) | `nodo_contexto`, `adyacente_contexto`, `contexto`. | ✅ 1.5i.7k |
| IndexedDB64 (espejo JS) | Misma API, base `HyS_ctx`. | ✅ V1.5i.7k |
| `guardar_parcial` real (SQL64) | Filtro por `contexto_mask`. | ✅ 1.5i.7o |
| Propagación de máscaras a nodos nuevos | `_guardar_con_filtro` con BFS por enlaces entrantes. | ✅ 1.5i.8c |
| **Persistencia robusta en hostings compartidos** | Singleton de conexión, `mysqli_report(OFF)`, `SELECT 1`, `IN (...)` directo. | ✅ 1.5i.8d-f |
| **JSON64 / XML64** con `guardar_parcial` | Fase 3 del framework. | Pendiente |
| **`guardar_parcial` real por delta** | Delta real, no merge en memoria. Bloquea Fase D del piloto. | Pendiente |
| **Dejar el Nodo limpio** | Mover `contexto_mascara` fuera del Nodo. Bloquea 256 contextos. | Pendiente |
| **256 contextos / producto de primos** | Fase 5 del framework, si hace falta. | Pendiente |
| **Iteradores con ID especial + ocupante** | Raíces de subgrafo con posición mutable. Resolvería B3.3c. | Pendiente |

### 5.3. Diversificación del piloto

- **Tipos de aplicación.** Próximo tipo: `"tienda"`
  (catálogo de productos). Requiere nodo
  `tipos_de_aplicacion` como raíz, enlace `tipo_app` del
  dueño, ajustes del login y del frontend por tipo. Ver
  `prompt_piloto.md` §1.2.
- **Multi-cliente.** Cada cliente con su subdominio y su
  propio grafo, misma base de código.
  `Conf::NOMBRE_APP` derivado del subdominio. Ver
  `prompt_piloto.md` §1.4.

### 5.4. Otros frentes

Calidad, plugin y visualizador. Detalle en §4.

- **Piloto — calidad** (§4.1): auditar estados colgados,
  auditar estructuras nuevas, deuda de seguridad en
  impresión.
- **Plugin** (§4.2): migrar persistencia de la pestaña
  activa a IndexedDB, pruebas 37/38 con `try/finally`,
  centralizar prefijos.
- **Visualizador de grafo** (§4.4): Fase 5 (persistencia
  de posiciones/zoom/layout), colores para tipos
  inferidos por enlace, patrón de grilla adaptativo al
  zoom.

---

## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante

**Estado:** CERRADO en v81h-v81i. Detalle histórico en
§6.4. Ver también §2.

### 6.1. Motivación

Los módulos `Empresa.php`, `Vehiculo.php`,
`Liquidacion.php` y `Rendicion.php` siguen navegando desde la
raíz `usuarios` en varias funciones. Bajo **carga parcial**
esa raíz solo está en el grafo del admin (vía
`us_Administrador → usuarios`). Para **dueño** y **soporte**
no está: cualquier `nodo_por_id('usuarios')` devuelve null y
la operación falla silenciosa o cae a "no encontrado".

El terminal **no** ve estas pestañas, así que no es un
problema de terminal. Es de dueño y soporte.

### 6.2. Diagnóstico

- **Admin:** tiene `us_Administrador → usuarios`. Los 4
  módulos andan.
- **Dueño:** carga parcial `[us_<dueño>]`. Sin `usuarios`.
  Falla cualquier navegación por la raíz.
- **Soporte:** carga parcial `[us_<soporte>] → duenos →
  us_<dueño>`. Puede llegar a `us_<dueño>` directo, pero no
  a `usuarios` global. Mismo problema que dueño.
- **Terminal:** no aplica (no ve estas pestañas).

Es el mismo patrón de la Fase B3.1 (v77r), aplicado a los
módulos que quedaron sin tocar.

### 6.3. Decisiones tomadas

- **Cambio de navegación:** `usuarios → dueño → X` pasa a
  `us_<dueño> → X` (ID especial directo).
- **Funciones que buscan por id:** reciben
  `?string $nombre_dueno` y navegan desde `us_<dueño>`.
  Mismo patrón que `obtener_venta_por_id` en `Venta.php`.
- **Enrutador:** `_nombre_dueno_solicitante($sol, $post)`
  provee el `$nombre_dueno` a esas funciones.
- **Sin cambios de API pública** para el frontend: los
  POST y las respuestas quedan iguales.
- **Empresa y Vehiculo** (contenedores planos): cambio de
  navegación. **Liquidacion y Rendicion** (árboles hmi/hd):
  cambio de navegación + `$nombre_dueno` en las búsquedas
  por id.

### 6.4. Fases

- [x] **Fase 1 — Auditoría.**
- [x] **Fase 2 — Módulos + Enrutador.** (v81h)
- [x] **Fase 3 — Verificación manual.** (v81h + v81i)
- [ ] **Fase 4 — Pruebas del plugin (espejo).** Pendiente.
- [ ] **Fase 5 — Documentación.** Parcialmente hecho en D.1.

---

## 7. NOTAS CRÍTICAS QUE NO PUEDEN FALTAR

Reglas de oro que aplican a cualquier tanda nueva.

- **La app no toca bitmasks a mano.** Si lo hiciera, la
  seguridad deja de emerger de la topología.
- **Los IDs numéricos del framework son efímeros.** Solo los
  IDs especiales (strings no numéricos) son estables entre
  cargas.
- **Antes de destruir un nodo, desenlazar.** `Nodo::eliminar`
  falla si hay referencias entrantes.
- **Nodos se comparan por `->id()`.** Nunca con `===`.
- **Datos como strings.** Números también. Castear al leer.
- **PHP convierte claves de array string numéricas a int.**
  Forzar `(string)$clave` al iterar.
- **En MV3, todos los imports del service worker tienen que
  ser estáticos.** Cualquier `await import(...)` en el SW
  rompe al reiniciarse por inactividad.
- **`Object.entries(Map)` devuelve `[]`.** Iterar con
  `for (const [k, v] of map)`.
- **Los bloques `buscar` del `aplicar_cambios.php` tienen
  que matchear exacto el archivo en disco.** Si un bloque
  falla, pedir el fragmento exacto al usuario antes de
  ajustarlo.
- **En PHP 8.4, `mysqli_report(MYSQLI_REPORT_STRICT)` es el
  default.** Cualquier fallo de mysqli lanza excepción. El
  `@` no la atrapa. Usar `try/catch` o `mysqli_report(OFF)`.
- **Hostings compartidos tienen privilegios limitados.**
  `CREATE TEMPORARY TABLES`, `SET SESSION wait_timeout`,
  `mysqli_report` no son garantía. Cada uno hay que
  testearlo en el hosting real. Ver
  `prompt_framework_iteradores.md` §6.7.

---

**Estado del proyecto al cierre:** v1.5piloto.83l
(framework 1.5i.7w). **Bug §4.7 cerrado.** No era el
leak lento (Fase 4 fue necesaria, pero no suficiente).
Era guardado total en cada request autenticado por
`validar_token_sesion` y otros 9 llamadores de
`en_grafo_credenciales`, más 2 bloques de bootstrap en
`index.php`. Fix: 10 funciones a `solo_lectura`,
condicionar `aplicacion`, condicionar bootstrap del
admin, flag de "hubo cambios" en `finally`. Validado
con ciclo completo (venta/cancelación/cupón): `id_fila`
estable, cero totales en el log, auditoría limpia.
**La migración de pasajeros se puede desbloquear**
(Tanda 3 pendiente). El log de persistencia está activo,
escribiendo a `logs/persistencia/YYYY-MM-DD.log`,
legible por HTTP con `?ver_log_persistencia=1`.

**Deuda técnica del arco** en §4.7: endpoints temporales
de diagnóstico, espejo `_hmi` a JS, espejo C.7-C.10 a JS,
`VERSION_APP` en pruebas, `error_log` de diagnóstico.

---

## 8. NOTA DE CIERRE — v1.5piloto.82d

Documento de retomada del arco "cancelaciones en compartidos
+ persistencia robusta en hostings compartidos". Se puede
borrar cuando la próxima sesión haya revisado los pendientes.

### 8.1 Estado verificado

En pruebas (InfinityFree):

  - guardar_total.ok:        true, 551ms
  - guardar_parcial.ok:      true, 592ms
  - Cancelación desde carmen2: persiste
  - Auditoría topológica:    0 críticos / 0 errores / 0 warnings

Sin bugs abiertos de prioridad alta.

### 8.2 Pendientes por prioridad

**Media (deuda del arco):**
- Espejar `_hmi` a JS (`iteradoresJS/miscelaneas/Arbol.js`).
- Espejar C.7-C.10 a JS (`PerdurarSuperestructuraStringIndexedDB64.js`).
- Limpieza de endpoints temporales: `?diag_persistencia=1`,
  `?diagnostico_cancelaciones=1`, `error_log(...)` en SQL base.
- `\ConfiguracionApli::VERSION_APP` devuelve "0.0.0" en pruebas.

**Baja (futuro):**
- Framework Fase 3/4/5 (§5.2).
- Iteradores con ID especial + ocupante (§5.2).
- Auditar estados colgados (§4.1).
- Deuda de seguridad en impresión (§4.1).
- Fusionar las dos §11 de `prompt_plugin_piloto.md`.

### 8.3 Aprendizajes clave (compactos)

1. **PHP 8.4 rompe `mysqli_report` y `mysqli_ping`.**
   `MYSQLI_REPORT_STRICT` es default: cualquier fallo lanza
   excepción. El `@` no la atrapa. Usar `mysqli_report(OFF)`
   + `SELECT 1` en lugar de `ping()`.

2. **Hostings compartidos tienen privilegios limitados.**
   `CREATE TEMPORARY TABLES`, `SET SESSION wait_timeout`,
   rate limit de conexiones. Solución: singleton de conexión,
   `IN (...)` directo, `SET SESSION wait_timeout = 300`.
   **Testear siempre en el hosting real antes de dar un fix
   por bueno.**

3. **Los errores silenciosos son peores que los ruidosos.**
   `cancelar_venta` no chequeaba `guardar_ambos`. Lección:
   toda operación de escritura que pueda fallar debe
   chequear el resultado y reportarlo.

4. **Un bug que cambia de síntoma no significa que el fix
   no sirvió.** §4.6 tuvo 4 capas de causa raíz. Cada fix
   movió el error sin resolverlo. Cuando el síntoma cambia
   pero persiste, hay una causa más profunda.

5. **`_hmi` debe ser idempotente.** Antes de C.4b:
   reinsertar un nodo que ya era el hmi generaba autoenlace
   `hd → sí mismo` (ciclo). Fix: no-op si el hijo ya es el
   hmi; limpiar `hd` residual cuando el padre no tenía hmi.

6. **Cycle guard en lectura de cadenas.** Aunque `_hmi` esté
   bien, un bug en otro lugar puede generar ciclos. Todo
   `while ($actual && $seg < 2000)` que recorre una cadena
   `hd`/`hd_<term>` debe trackear nodos visitados por id.
   Aplica a cancelaciones hoy; auditar ventas, rendiciones,
   liquidaciones en el futuro.

7. **Soft delete coordinado también aplica a cancelaciones.**
   El patrón de "árboles paralelos + buzón diferido" no era
   solo para ventas.

8. **Bloques `buscar` con estructura repetida necesitan
   desambiguación.** El prompt del plugin tiene dos §11. Los
   `buscar` deben anclar al header único, no al contenido
   compartido.

9. **Invertir en la herramienta de diagnóstico paga.**
   `?auditar_topologia=1` encontró en 500ms lo que a ojo
   hubiera costado horas.

10. **Cuando el reporte de la auditoría tiene muchos items
    de un mismo tipo, revisar la verificación antes de
    asumir que hay muchos bugs.** Los 96 `p_apunta_a_otro`
    y 7 `terminal_dueno_real` eran falsos positivos.

### 8.4 Próximos pasos sugeridos

1. Limpieza de endpoints temporales (§4.7).
2. Espejo `_hmi` a JS.
3. Auditar cycle guard en otros flujos (ventas,
   rendiciones, liquidaciones).
4. Fusionar las dos §11 de `prompt_plugin_piloto.md`.

### 8.5 Archivo obsoleto

`prompts/plan_auditar_topologia.md` quedó obsoleto. Su
contenido está cubierto por:
- §3.5 de `prompt_piloto.md` (auditoría).
- §12 de `prompt_plugin_piloto.md` (botón del plugin).
- §2 de este archivo (arco cerrado).

Se puede borrar.

---

**FIN DEL PLAN ACTUAL**