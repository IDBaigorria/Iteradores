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

**Última actualización:** v1.5piloto.81g (cierre del arco
del visualizador de grafo).

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

- **Adaptar los 4 módulos menores al contexto del
  solicitante** — **EN CURSO**, ver §6.
  `Liquidacion.php`, `Rendicion.php`, `Empresa.php`,
  `Vehiculo.php`.
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

---

## 5. CAMINO A LARGO PLAZO

Planes de mediano/largo plazo, no implementados. No bloquean
el trabajo actual.

- **B3.3c — 7 helpers que iteran sobre todos los dueños
  buscando por id.** Postergado hasta que aparezca dolor
  real o hasta que lleguen los iteradores con ID especial
  (ver §4.3). La lista completa está en
  `prompt_seguridad.md` §8.6.
- **Iteradores con ID especial + ocupante.** Cuando el
  framework los implemente, los B3.3c se resuelven
  naturalmente. Ver `prompt_framework_iteradores.md`
  §11.5.
- **Framework — dejar el Nodo limpio.** Antes de agregar
  más métodos de indexación de contextos.
- **Framework — JSON64 / XML64** con `guardar_parcial`.
- **Framework — `guardar_parcial` real por delta.**
- **Diversificación del piloto por tipo de aplicación.**
  Próximo tipo: `"tienda"` (catálogo de productos).
  Requiere nodo `tipos_de_aplicacion` como raíz,
  enlace `tipo_app` del dueño, ajustes del login y del
  frontend por tipo. Ver `prompt_piloto.md` §1.2 y §8.7.
- **Multi-cliente.** Cada cliente con su subdominio y su
  propio grafo, misma base de código. `Conf::NOMBRE_APP`
  derivado del subdominio. Ver `prompt_piloto.md` §1.4.

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

- [ ] **Fase 2 — Los 4 módulos + Enrutador.** Una sola
  tanda con bloques por módulo.
  *Archivos:* `Aplicacion/Empresas/Empresa.php`,
  `Aplicacion/Vehiculos/Vehiculo.php`,
  `Aplicacion/Liquidaciones/Liquidacion.php`,
  `Aplicacion/Rendiciones/Rendicion.php`,
  `Aplicacion/Enrutador.php`.
  *Bumps:* cada archivo tocado a `1.5piloto.81h`.

- [ ] **Fase 3 — Verificación manual.**
  - Login **dueño**: Rendiciones, Liquidaciones,
    Empresas/Micros cargan sin errores.
  - Login **soporte** (con un dueño asignado): idem.
  - Login **admin**: no se rompe nada.
  - Si algo falla, fix chico antes de las pruebas del
    plugin.

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