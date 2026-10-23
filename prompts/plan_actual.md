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

**Última actualización:** v1.5piloto.81o (fix de
`reparar_arboles` + estado del bug de cancelación desde
terminal post-migración).

En v81j se adaptaron los 3 helpers de B3.3c. En v81k se
arregló el frontend de ventas.js (no mandaba `nombre_dueno`
en 4 fetch, mismo patrón que el bug v81i de rendiciones).
En v81l se rediseñó `soporte` singular → `soportes`
contenedor plural, y se reordenó `agregar_usuario` para que
`_asegurar_niveles_usuario` corra antes de
`_asignar_duenos_a_soporte`. En v81m se actualizaron los
prompts. En v81n se corrigió `_reparar_arboles_precalcular`
para que indexe por nombre de enlace (antes borraba el
`hmi_<term>` de los compartidos, dejando ventas huérfanas).
**Con B3.3c cerrado, la Fase B del modelo topológico quedó
completa.** El bug de cancelación desde terminal
post-migración está en investigación (ver §4.6).

**Antes (v81g):** cierre del arco del visualizador de
grafo. Antes (v79o): cierre de la línea "grafo + plugin +
sistema de scripts". Carga parcial activa. La topología
sigue siendo la fuente de la seguridad: el bitmask lo
propaga el framework, no la app.

**Versiones:**

- **Piloto:** `1.5piloto.81g`.
- **Framework PHP:** `1.5i.8c` (SQL64). El archivo
  `Controlador/Controlador.php` está en `1.5i.7s` por los
  comandos del visualizador.
- **Framework JS:** `V1.5i.8c` (IndexedDB64).
- **Plugin de pruebas:** `v1.5plugin.8f`.

**Dónde estamos parados:**

El piloto tiene el modelo topológico completo (usuarios,
compartidos, contextos, carga parcial) y el visualizador de
grafo cerrado. La seguridad sigue emergiendo de la topología
del grafo: el bitmask lo propaga el framework, no la app.
No hay bugs abiertos de prioridad alta.

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

- **Fix de `reparar_arboles` (v81n).** El precalcular
  indexa por nombre de enlace (`p`, `hd`, `p_<term>`,
  `hd_<term>`), no por nombre fijo. Antes, para un
  compartido, `$por_padre[$id_cont]` venía vacío y
  `_reparar_arbol` borraba el `hmi_<term>`. Eso rompía
  el árbol del compartido: las ventas viejas quedaban
  huérfanas y el terminal solo veía las nuevas. Test de
  aceptación: `?reparar_arboles=1` dos veces seguidas
  debe dar `cambios: 0`.
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
- **Fase 2c (v80a-80e).** Migración del plugin a `_post` y
  `post_accion`. Detalle en el prompt del plugin §9.
- **Carga parcial (v78b-v79a).** Aplicada. La app ya usa
  `cargar_parcial`/`guardar_parcial` en los módulos
  principales. Detalle en `prompt_seguridad.md` §2-5.
- **Modelo topológico (Fases A, B1-B3).** Aplicado. Queda
  pendiente solo B3.3c. Detalle en `prompt_seguridad.md`
  §5 y `prompt_piloto.md` §8.7.
- **Framework SQL64 (1.5i.7k-8c).** Activo. Bugs §1.5,
  §1.5.7, §1.5.8, §1.5.10, §1.5.15 y §1.5.16 resueltos.
  Detalle en `prompt_framework_iteradores.md` §11.4 y en
  `prompt_seguridad.md` §8.

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
  destruye el contexto que tenga la vista completa.
- **Inserción diferida con buzones.** Un contexto que no
  puede insertar en el árbol del otro deja un aviso en un
  buzón del compartido. El otro lo drena en su próxima
  carga.
- **Comandos del framework vs de la app.** Los del framework
  (`grafo:*`, `comunicacion:*`, etc.) van en el
  `Controlador`. Los de la app van en `Aplicacion/`,
  registrados desde `index.php`.
- **Espejo PHP ↔ JS.** Cualquier cambio al framework PHP se
  refleja en JS en la misma tanda, con dos scripts y dos
  commits.
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

**Foco inmediato: Piloto — calidad.**

Con el arco de los 4 módulos menores cerrado (v81h +
v81i), el foco pasa a los pendientes de calidad del
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
4. **B3.3c restante (3 helpers).** Cuando aparezca dolor
   real o lleguen los iteradores con ID especial.

**Después, según prioridad:**

- **Migrar persistencia de la pestaña activa del plugin**
  (`localStorage` → IndexedDB).
- **Visualizador de grafo, Fase 5** (persistencia de
  posiciones, zoom y layout).
- **Framework Iteradores:** dejar el Nodo limpio,
  JSON64/XML64, `guardar_parcial` por delta, iteradores
  con ID especial.
- **Diversificación por tipo de aplicación** (`tienda`).

---

### 4.6. Bug en investigación: cancelación desde terminal post-migración

Cuando un terminal cancela una venta después de las
migraciones (con `reparar_arboles` v81n aplicado), el
backend responde `exito: true` con `id_cancelacion`, pero:

- La cancelación **no se persiste** en la DB.
- El asiento sigue vendido en `viajes/estado_asientos`.
- `cancelaciones/listar` devuelve `[]`.

Reproducido en el servidor de pruebas (InfinityFree,
`wait_timeout = 20`). En local no se reproduce.

**Hipótesis abiertas:**

- **Frontend no dispara el `fetch ventas/cancelar`.** En
  el log de la consola del navegador no aparece el bloque
  `[fetch] index.php` con `body accion: "ventas/cancelar"`.
  Falta confirmar si el `confirm()` del modal aparece al
  clickear "Cancelar venta". Si no aparece, el listener
  no está conectado.
- **Árbol del compartido con `hmi` vs `hmi_<term>`
  inconsistentes.** `construir_arboles_compartidos` arma
  el árbol con nombres parametrizados. Pero
  `_crear_nodo_cancelacion` inserta con `_hmi` default.
  Si el contenedor ya tiene `hmi_secretaria_carmen`, el
  `_hmi` default no lo toma en cuenta y la nueva queda
  huérfana. `listar_cancelaciones_de_dueno` busca en `hmi`
  default y devuelve `[]`.
- **`guardar_parcial([us_carmen2])` falla silenciosamente.**
  El subgrafo del terminal no es chico (~1000 nodos si
  incluye pasajeros alias). En InfinityFree con
  `wait_timeout = 20`, el guardado puede morir a mitad,
  PHP no lo detecta, `guardar_parcial` retorna `true`
  sin commitear. Descartable si el log del navegador no
  muestra el fetch.

**Cómo seguir:**

1. Capturar `[fetch] ventas/cancelar` en la consola del
   navegador. Si aparece con `exito: true` y el count SQL
   de `cancelacion_%` no sube, es la hipótesis del
   `guardar_parcial` o del árbol roto.
2. Si el `confirm()` del modal no aparece al clickear
   "Cancelar venta", es bug del frontend (listener
   desconectado).
3. Correr `SELECT COUNT(*) FROM nodo_contexto WHERE
   idsuperestructura=... AND CAST(dato AS CHAR) LIKE
   'cancelacion_%'` antes y después del intento.

**Herramienta de diagnóstico en desarrollo:**

Para identificar el defecto de forma sistemática (sin
depender de más logs manuales), hay un plan de auditoría
topológica en `prompts/plan_auditar_topologia.md`. La
primera tanda crea un endpoint `?auditar_topologia=1` en
el piloto que devuelve un reporte con los defectos del
grafo (aristas rotas, ventas sin árbol parametrizado,
buzones con contenido, soporte singular legacy, nodos
huérfanos). Ese endpoint es el paso siguiente para
diagnosticar este bug.

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

**Estado:** planificado. Sin código todavía.

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

- [ ] **Fase 1 — Auditoría.** Leer los 4 archivos y el
  Enrutador completos. Listar todos los
  `nodo_por_id('usuarios')` y todos los casos donde un
  `$nombre_dueno` no se usa para navegar directo.
  Confirmar diagnósticos y ajustar si hay sorpresas.
  *Solo lectura, sin código.*

- [x] **Fase 2 — Módulos + Enrutador.** (v81h)
  **Alcance corregido tras la auditoría:** Empresa.php
  y Vehiculo.php ya estaban adaptados (fases v76u-v77v).
  Solo quedaban 3 funciones en Liquidacion.php y
  Rendicion.php con el patrón viejo + las 3 subacciones
  del Enrutador que las invocan sin `$nombre_dueno`.
  *Archivos tocados:* `Aplicacion/Liquidaciones/Liquidacion.php`
  (`obtener_liquidacion_por_id`),
  `Aplicacion/Rendiciones/Rendicion.php`
  (`obtener_rendicion_por_id` y `aceptar_ajuste_rendicion`),
  `Aplicacion/Enrutador.php` (3 subacciones).
  *Patrón:* `?string $nombre_dueno = null`. Si viene,
  navega desde `us_<dueño>`. Si no, fallback al
  recorrido completo (compatibilidad con Impresion.php,
  que hace carga total).
  *Bumps:* Liquidacion.php, Rendicion.php y Enrutador.php
  a `1.5piloto.81h`.

  **Bug encontrado en la verificación manual (v81i):**
  el backend ya navegaba bien, pero el frontend no
  mandaba `nombre_dueno` en `rendiciones/obtener`,
  `rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`.
  Como el soporte no tiene `usuarios` en su carga
  parcial, el fallback del backend devolvía null y el
  modal decía "no encontrada". Fix: los 3 POST del
  frontend agregan `nombre_dueno` vía
  `obtener_nombre_dueno_rendiciones()` /
  `obtener_nombre_dueno_liquidaciones()`. Con dueño y
  admin ya funcionaba porque el backend devuelve el
  nombre solo; el bug era exclusivo de soporte.

- [x] **Fase 3 — Verificación manual.** (v81h + v81i)
  - Login **dueño**: Rendiciones y Liquidaciones cargan
    y el detalle abre. OK.
  - Login **soporte**: tabla cargaba pero el detalle
    decía "no encontrada" (bug v81i, ver §6.4).
    Fix aplicado en el frontend. Verificado tras el fix.
  - Login **admin**: no se rompió nada.

- [ ] **Fase 4 — Pruebas del plugin (espejo).**
  Tres pruebas mínimas:
  - `rendiciones_listar_carga_parcial_dueno`: login
    dueño, `rendiciones/listar`, verificar que devuelve
    sin error.
  - `liquidaciones_listar_carga_parcial_dueno`: idem.
  - `empresas_listar_carga_parcial_soporte`: login
    soporte, elegir un dueño asignado,
    `empresas/listar`, verificar sin error.
  *Archivo:* `iteradoresJS/Aplicacion/pruebas/`.
  *Commit separado en el repo del plugin.*

- [ ] **Fase 5 — Documentación.**
  - `prompt_piloto.md` §5.11-5.12: si cambian firmas,
    anotarlo.
  - `plan_actual.md` §2: una línea con el arco cerrado.
  - `prompt_seguridad.md` §8.6 (B3.3c): de los 7
    helpers, quedan 3 sin adaptar (los de `Venta.php` y
    la rama de historial de `Pasajero.php`).

### 6.5. Pendientes que abre

- **B3.3c se reduce:** de 7 helpers sin adaptar a 3
  (`Venta.php`: `_buscar_venta_por_id` fallback y
  `obtener_cancelacion_por_id`; `Pasajero.php`:
  `obtener_pasajero_por_dni` rama del historial de
  ventas).
- **Verificación con soporte real:** hoy las pruebas del
  plugin no cubren el caso "soporte opera sobre un dueño
  asignado". Las 3 pruebas nuevas de la Fase 4 lo hacen
  para Empresas. Si en el futuro aparecen más módulos con
  este patrón, replicar.
- **Falta de cobertura en rendiciones/liquidaciones
  para soporte:** las pruebas de la Fase 4 cubren
  `rendiciones/listar` y `liquidaciones/listar` con
  dueño, pero no con soporte. Se puede agregar después.

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

---

**FIN DEL PLAN ACTUAL**