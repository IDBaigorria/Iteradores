# Plan — Herramienta "Auditar topología"

Este archivo documenta el **plan completo** para agregar una
herramienta de diagnóstico topológico al piloto y al plugin.
Es autocontenido: se pega en una nueva conversación junto con
los prompts principales (`prompt_framework_iteradores.md`,
`prompt_piloto.md`, `prompt_seguridad.md`,
`prompt_sistema_scripts_v2.md`, `plan_actual.md`) y
`prompt_plugin_piloto.md` / `estructura_plugin.md` del repo
JS.

**Estado al crear este plan:** v1.5piloto.81o / v1.5plugin.9m.
Bug de cancelación desde terminal post-migración en
investigación (ver `plan_actual.md` §4.6). Esta herramienta
es el paso siguiente para identificar el defecto exacto.

---

## 1. Para qué sirve

Un endpoint en el piloto + un botón en el plugin que corren un
chequeo de consistencia del grafo SQL64 y devuelven un
reporte con los defectos encontrados, agrupados por
severidad. **Solo lee. No corrige nada.**

Sirve para:

- Diagnosticar el bug de cancelación actual (ver §2).
- Detectar regresiones de las migraciones.
- Auditar el grafo antes de pasar a producción.

## 2. Contexto del bug que motiva la herramienta

Cuando un terminal cancela una venta después de las
migraciones (con `reparar_arboles` v81n aplicado), el
backend responde `exito: true` con `id_cancelacion`, pero:

- La cancelación **no se persiste** en la DB.
- El asiento sigue vendido en `viajes/estado_asientos`.
- `cancelaciones/listar` devuelve `[]`.

Reproducido en el servidor de pruebas (InfinityFree,
`wait_timeout = 20`). En local no se reproduce.

Hipótesis abiertas en `plan_actual.md` §4.6. Falta confirmar
si el `[fetch] ventas/cancelar` aparece en la consola del
navegador. La herramienta de auditoría puede revelar el
defecto subyacente (árbol del compartido roto, ventas
huérfanas, etc.) sin depender de más log.

## 3. Arquitectura

### 3.1 Backend (piloto PHP)

- Nuevo archivo `Aplicacion/Reparacion/Auditoria.php` con la
  función `auditar_topologia(string $token): array`.
- Registrado como comando `grafo:auditar_topologia` en
  `Controlador::_registrar_comandos_grafo()` (privado).
- Endpoint en `index.php`: `?auditar_topologia=1` → llama al
  comando y devuelve JSON. Solo admin/soporte (mismo criterio
  que `grafo:*`).

### 3.2 Frontend (plugin JS)

- Botón **"🔍 Auditar topología"** en el panel de
  migraciones, arriba de los checkboxes.
- Al clickear: GET a `URL_PILOTO + index.php?auditar_topologia=1`.
- Muestra el reporte agrupado por severidad con colores
  (rojo/naranja/amarillo).
- Botón **"Copiar reporte"** que arma un texto plano para
  pegar en consola.
- Botón **"Ver JSON"** que muestra el JSON crudo.
- El reporte se persiste en el grafo del plugin:
  `plugin → auditorias → corrida_<ts>` (mismas últimas 10
  corridas que migraciones). Coherente con el patrón.

## 4. Qué detecta (versión 1)

### 4.1 Críticos (rompen funcionalidad)

1. **Aristas rotas:** `adyacente_contexto` con origen o
   destino que no existe en `nodo_contexto`. Rompe cualquier
   traversal.
2. **Nodos con `contexto_mascara = 0`** sin `id_especial`. No
   se cargan en carga parcial.
3. **`us_Administrador` sin enlace `usuarios`.** El admin no
   navega la raíz.
4. **Terminal con `dueno` apuntando al nodo real del dueño**
   (en lugar del compartido). Rompe el aislamiento.
5. **Compartido sin `_es_compartido`** o sin contenedores
   `ventas`/`cancelaciones`.

### 4.2 Errores (bugs visibles)

6. **Ventas en el árbol del compartido sin `p_<term>`.**
   Árbol parametrizado roto: la venta está en el contenedor
   pero el nodo no tiene el enlace padre parametrizado.
7. **Ventas en el árbol del compartido sin `p` default.**
   Árbol paralelo roto.
8. **Buzones `pendientes_ventas` o `pendientes_cancelaciones`
   con contenido.** Indica un drenado pendiente.
9. **Dueños con `soporte` singular legacy** (post-v81l).
   Debería ser `soportes` plural.
10. **Soportes sin `publico`.**

### 4.3 Warnings (inconsistencias menores)

11. **Nodos huérfanos** (no alcanzables desde raíces
    especiales).
12. **Hijos en `hmi`/`hd` sin `p` de vuelta.**
13. **`p` apuntando a un nodo que no es contenedor de árbol.**
14. **Ciclos en `hd`** (no se llega al contenedor en N pasos).

## 5. Formato del reporte

```json
{
  "exito": true,
  "resumen": {
    "superestructura": "AdministradorDeViajes",
    "nodos_total": 1593,
    "aristas_total": 2091,
    "criticos": 0,
    "errores": 2,
    "warnings": 5
  },
  "criticos": [
    {"tipo": "arista_rota", "detalle": "adyacente_contexto fila 12345: origen 900 no existe"}
  ],
  "errores": [
    {"tipo": "venta_sin_param", "detalle": "venta_1791947243 sin p_secretaria_carmen en árbol del compartido compartido_con_secretaria_carmen"}
  ],
  "warnings": [
    {"tipo": "huerfano", "detalle": "nodo #3847 (dato: 'cupon_1') sin referencias entrantes alcanzables"}
  ]
}
```

## 6. Qué NO hace

- **No corrige nada.** Solo diagnostica. Los fixes van por
  `?reparar_topologia=1`, `?reparar_arboles=1`, o scripts
  manuales.
- **No bloquea el grafo.** Es un GET de solo lectura.
- **No analiza la DB SQL vieja** (`nodo`/`adyacente`). Solo
  SQL64.

## 7. Decisiones tomadas

- **Endpoint en `index.php`** con `?auditar_topologia=1`
  (consistente con las migraciones del plugin). Internamente
  llama al comando `grafo:auditar_topologia`.
- **Persistencia en el grafo del plugin:**
  `plugin → auditorias → corrida_<ts>`, últimas 10 corridas.
- **Severidades:** `criticos` / `errores` / `warnings`. Cada
  defecto tiene `tipo` (enum corto) y `detalle` (texto
  legible).
- **Detección de ventas sin árbol parametrizado:** se requiere
  `p_<term>` como mínimo. Los otros enlaces
  (`hmi_<term>`, `hd_<term>`) los administra el árbol, pero
  si `p_<term>` falta, la venta está huérfana.
- **Restricción por nivel:** admin/soporte (mismo criterio que
  `grafo:*`). En localhost, sin chequeo.

## 8. Plan de ejecución

**Tanda A — Backend primero (para diagnosticar ya).**

Objetivo: tener el endpoint funcionando antes de tocar el
plugin, para correr `?auditar_topologia=1` en el servidor de
pruebas y obtener el reporte.

1. Crear `Aplicacion/Reparacion/Auditoria.php` con
   `auditar_topologia(string $token): array`.
2. Registrar `grafo:auditar_topologia` en
   `Controlador::_registrar_comandos_grafo()`.
3. Agregar bloque `?auditar_topologia=1` en `index.php`,
   antes de "Enrutar según método".
4. Subir a pruebas. Correr:
   `https://viajespquiadelcarmen.rf.gd/index.php?auditar_topologia=1`
   (logueado como admin o soporte).
5. Con el reporte, identificar el defecto que causa el bug
   de cancelación.

**Tanda B — Frontend del plugin.**

Objetivo: integrar la herramienta en el popup para uso
recurrente.

6. Crear `Aplicacion/Auditoria.js` (llamada GET + persistencia
   en el grafo).
7. Agregar cases `auditar_topologia` y `estado_auditorias` en
   `servicio.js`.
8. Agregar persistencia en `GrafoPlugin.js`
   (`plugin → auditorias → corrida_<ts>`).
9. Agregar botón + panel de resultado en `ventana.js`,
   dentro de `_renderizar_migraciones` o en un panel aparte.
10. Bump de versión en `ConfiguracionApli.js`.

**Tanda C — Documentación.**

11. Actualizar `prompt_plugin_piloto.md` §11 con el nuevo
    botón y el endpoint.
12. Actualizar `plan_actual.md` §4.6 con el reporte obtenido
    y el fix aplicado (si lo hay).

## 9. Archivos que el asistente debe pedir al usuario

**Para la Tanda A (backend):**

- `Controlador/Controlador.php` — solo el bloque
  `_registrar_comandos_grafo()` (buscar con `Ctrl+F` la línea
  `function _registrar_comandos_grafo`; pegar desde ahí hasta
  el `}` de cierre del método).
- `index.php` — solo el bloque final antes de "Enrutar según
  método" (donde ya agregamos `benchmark_guardar`), para
  saber dónde enganchar el nuevo endpoint.

**Para la Tanda B (plugin):**

- `Aplicacion/ventana.js` — solo `_renderizar_migraciones`
  (el HTML del panel y los listeners del formulario de
  migraciones), para saber dónde enganchar el botón.
- `Aplicacion/servicio.js` — para confirmar la ubicación de
  los cases existentes (`correr_migraciones`, etc.).
- `Aplicacion/GrafoPlugin.js` — para agregar las funciones de
  persistencia de auditorías (mismo patrón que migraciones).

**Regla del sistema de scripts:** antes de armar cualquier
`aplicar_cambios.php`, pedir los archivos actuales. No
trabajar de memoria.

## 10. Notas de contexto

- **El bug de cancelación sigue abierto.** La Tanda A busca
  exactamente identificarlo. Si el reporte dice
  `venta_sin_param`, es el árbol del compartido. Si dice
  `arista_rota`, es la persistencia. Si dice
  `buzon_con_contenido`, es el drenado pendiente.
- **Los scripts van en dos repos distintos.** Backend en
  `iteradores/`, frontend en `iteradoresJS/`. Dos
  `aplicar_cambios.php`, dos commits.
- **Las URLs del plugin** son: pruebas
  `https://viajespquiadelcarmen.rf.gd/`, producción
  `https://viajespquiadelcarmen.infy.click/` (v9m).
- **El servidor de pruebas tiene `wait_timeout = 20`**
  (InfinityFree). Cualquier operación de guardado que tarde
  más puede morir a mitad sin que PHP lo detecte.
- **No se debe romper nada.** La herramienta es aditiva. El
  endpoint es un GET nuevo, no reemplaza nada. El botón del
  plugin es un botón nuevo, no cambia los existentes.

## 11. Cómo retomar

Pegar en una nueva conversación, en este orden:

1. `prompts/prompt_framework_iteradores.md`
2. `prompts/prompt_piloto.md`
3. `prompts/prompt_seguridad.md`
4. `prompts/prompt_sistema_scripts_v2.md`
5. `prompts/plan_actual.md`
6. `prompts/plan_auditar_topologia.md` (este archivo)
7. `iteradoresJS/prompts/prompt_plugin_piloto.md`
8. `iteradoresJS/prompts/estructura_plugin.md`

Y decir: "Retomamos el plan de auditoría de topología.
Arrancamos por la Tanda A (backend). Pedime los archivos
que necesitás."

El asistente debe pedir los archivos listados en §9, no
trabajar de memoria. Y antes de armar el script de la Tanda
A, consensuar el diseño de las 14 verificaciones (si el
usuario quiere agregar, quitar o cambiar severidades).

---

**FIN DEL PLAN**