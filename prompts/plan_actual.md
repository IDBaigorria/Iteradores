# Plan actual — Línea de trabajo en curso

Este archivo documenta la **línea de trabajo activa** del piloto.
Reemplaza a la sección "Discusión actual" del `prompt_piloto.md`,
que queda **congelada como referencia histórica** desde v1.5piloto.76v.

**Reglas:**

- Se actualiza al cerrar **cada tanda** (no el prompt del piloto).
- Es lo primero que hay que leer para retomar el trabajo.
- Estado (qué se cerró, decisiones, bugs, aprendizajes) arriba.
- Plan de la línea de tandas (fases y detalle de la fase en curso) abajo.

---

## 1. ESTADO ACTUAL

### 1.1 Última actualización

**Última actualización de este archivo:** v1.5piloto.79o.

Cierre de la línea "grafo + plugin + sistema de scripts".
Se resolvieron: el bug del botón "Limpiar viajes de prueba"
que rompía los compartidos, la limpieza profunda de viajes
con ventas de prueba, el typo del prefijo `viajedeselegraf`,
el try/finally en las pruebas del plugin (34/35/36), el
bug del runner de bumps (v79o), y se consolidó el sistema
de scripts en un único prompt v2. La carga parcial sigue
activa. La topología sigue siendo la fuente de la
seguridad: el bitmask lo propaga el framework, no la app.

**Bug §1.5 RESUELTO en v78v.** Causa raíz: IDs numéricos
del framework efímeros. Solución: `id_fila` (BIGINT
AUTO_INCREMENT) en `nodo_contexto`. Ver §1.5.6.

**Bug §1.5.7 RESUELTO en v78w-v78z.** Nodos compartidos
entre contextos no pueden ser destruidos por el guardado
parcial de un solo contexto. Solución: soft delete
coordinado con autoenlace `cancelada`. Ver §1.5.7 y §1.5.8.

**Bug §1.5.10 RESUELTO en v79a.** Inserción diferida con
buzones + propagación de máscaras. Ver §1.5.10.

**Bug §1.5.15 RESUELTO en v79j.** El botón "Limpiar viajes
de prueba" destruía los viajes sin desincronizarlos de los
compartidos. Las aristas del compartido quedaban colgadas,
el BFS del compartido se rompía, y el terminal dejaba de
ver sus viajes. Fix: `_desincronizar_viaje_de_compartidos`
antes de destruir cada viaje + helper
`reparar_compartidos_viajes_dueno`. Ver §1.5.15.

**Bug §1.5.16 RESUELTO en v79o.** El runner de bumps
releía el archivo desde el disco y pisaba los reemplazos
pendientes en memoria. Detectado aplicando P55 v6x. Fix:
el bump parte del contenido en memoria. Ver §1.5.16.

**Sistema de scripts v2.** Se consolidaron
`prompt_sistema_scripts.md` + `prompt_runner_aplicar_cambios.md`
en `prompt_sistema_scripts_v2.md`. Los viejos quedan como
backup. El runner v2 tiene bumps automáticos y el fix del
pisado de reemplazos.

**Plugin de pruebas.** Se agregó try/finally a las pruebas
34, 35 y 36 (limpieza best-effort). Se corrigió P55
(subida de foto con `nombre_campo` y comparación de
rutas en vez de nombres originales). El plugin está en
`v1.5plugin.6z`.

Próximo paso sugerido: ver §2.3.

### 1.2 Cadena de cierres de la sesión v78b-v79a

- **v78b-v78s** (línea de carga parcial): ver versiones
  anteriores del plan. Cubre Etapa 0, 1b, 1c.1a/b, 1c.2a-f.
- **v78v** (fix §1.5): cambio de esquema en SQL64.
  `nodo_contexto` usa `id_fila` (AUTO_INCREMENT, PK) e
  `id_especial` para el ID especial del framework.
  `adyacente_contexto` referencia sus extremos por
  `idnodo_fila`/`idadyacente_fila`. Mapa estático
  `$_mapa_id_a_fila`. Guardado parcial por `id_fila`.
- **v78w** (cancelación diferida, §1.5.7): un nodo
  compartido entre contextos no puede ser destruido por el
  guardado parcial de un solo contexto. El terminal marca
  la venta con autoenlace `cancelada` y la deja en el
  árbol del dueño. `listar_ventas_por_dueno` la destruye
  al recorrer.
- **v78x**: filtros por dueño en `Venta.php`. Nuevo helper
  `_nombre_dueno_solicitante` en el Enrutador.
- **v78y**: reparación de árboles. Comando
  `app:reparar_arboles`, ejecutable con
  `?reparar_arboles=1`. Reconstruye aristas `hmi` faltantes.
- **v78z** (conservar hd, §1.5.8): al marcar una venta
  desde terminal, conservar los enlaces `hmi`/`hd`/`p` del
  árbol del dueño. Sin el `hd`, el traversal se corta.
- **v79a** (buzones, §1.5.10): inserción diferida. El
  terminal vende/cancela y deja aviso en un buzón del
  compartido. El dueño drena el buzón en su próxima carga.
  Framework 1.5i.8c: propagación de máscaras a nodos nuevos
  por enlaces entrantes.
- **v79j** (fix botón + compartidos + botón solo admin): ver
  §1.5.15. Agrega `_desincronizar_viaje_de_compartidos` en
  `limpiar_viajes_de_prueba`, el helper
  `reparar_compartidos_viajes_dueno`, amplía prefijos de
  prueba, y deja el botón visible solo para admin en modo
  pruebas (CSS + clase en body).
- **v79k**: agrega prefijo `viajeregres` y reporta
  conservados en el toast del botón.
- **v79l**: regla de ventas de prueba. Solo emails
  terminados en `@test.local` marcan la venta como de
  prueba. Si el viaje tiene alguna venta con email real o
  vacío, se conserva.
- **v79m**: fix typo del prefijo `viajedeselgraf` →
  `viajedeselegraf`. La prueba 35 usa el nombre con la `e`.
- **v79n**: runner v2 con bumps automáticos. Se elimina la
  clase de errores de bumps mal calculados.
- **v79o**: fix del runner v2. El bump parte del contenido
  en memoria, no del disco. Ver §1.5.16. También consolida
  el sistema de scripts en `prompt_sistema_scripts_v2.md`.
- **v6w** (plugin): `try/finally` en pruebas 34, 35, 36.
- **v6x** (plugin): P55 usa `nombre_campo: "foto"`.
- **v6y** (plugin): fix del bloque `finally` mal armado en
  P35.
- **v6z** (plugin): P55 compara rutas de foto en vez de
  nombres originales.

### 1.3 Decisiones de diseño en vigor

- **SQL es siempre el método principal.** JSON/XML son respaldo
  manual.
- **Host y credenciales en `config_servidor.php`.**
- **Rol `soporte`.** Ve las mismas pestañas que el admin,
  solo sobre sus dueños asignados.
- **Los prompts viven en el proyecto.**
- **Comandos del framework vs de la app.** Framework → en
  `Controlador.php`. App → en `Aplicacion/`, registrados desde
  `index.php` después de los `require_once`.
- **El modelo topológico.** Seguridad por topología.
- **SQL64 como método activo.** Persiste en las 3 tablas
  nuevas: `nodo_contexto`, `adyacente_contexto`, `contexto`.
  Esquema `id_fila` / `id_especial` (v78v).
- **Carga parcial por contexto (v78g).** Toda operación POST
  con `nombre_solicitante` carga parcial con `[us_<sol>]`.
  Sin solicitante, carga total. Excepciones puntuales: gestión
  de usuarios, informes con B3.3c.
- **`us_Administrador → usuarios` (v78f).** El admin carga
  parcial con `[us_Administrador]` pero ve todos los usuarios.
- **`dueno → soporte → publico` (v78f).** Evita la fuga
  cruzada entre dueños que comparten un soporte.
- **El bitmask lo propaga el framework, no la app (v79a).**
  Regla de oro: la app nunca asigna bits a mano. Si lo
  hiciera, la seguridad dejaría de emerger de la topología.
- **Soft delete coordinado para nodos multi-contexto (§1.5.7).**
  Un nodo con bits de varios contextos no puede ser destruido
  por uno solo. Se marca con autoenlace y lo destruye el
  contexto que tenga la vista completa.
- **Inserción diferida con buzones (§1.5.10).** Un contexto
  que no puede insertar en el árbol del otro deja un aviso
  en un buzón del compartido. El otro contexto lo drena.

### 1.4 Bugs conocidos

**Ninguno de prioridad alta.** Los bugs §1.5, §1.5.7,
§1.5.8 y §1.5.10 están resueltos.

Pendientes de auditoría:

- Aplicar el patrón de §1.5.7/§1.5.8 a rendiciones,
  liquidaciones y cancelaciones. Ver §9 del prompt de
  seguridad y §1.5.10 más abajo.
- Audit: verificar que las estructuras futuras cumplan
  la regla de detección de §5.9 del prompt de seguridad.

### 1.5 BUG RESUELTO — El viaje viejo desaparecía [RESUELTO en v1.5i.8a]

**RESUELTO en framework 1.5i.8a/b (v1.5piloto.78v).**
Ver §1.5.6 abajo. El resto de la sección queda como
referencia histórica: síntoma, diagnóstico y fixes que
no alcanzaron.

#### 1.5.1 Síntoma

Con las pruebas del plugin:
  1. Se corre la prueba "autocompletado por DNI" (logueado
     como terminal).
  2. Después la prueba "alta de viaje" (logueado como dueño).
  3. El viaje viejo (el principal, "Peregrinación a la Visita
     del Papa León XIV a Luján") **desaparece de la lista del
     dueño**.
  4. El terminal lo sigue viendo.
  5. Corriendo la prueba de nuevo, ya no falla.

Patrón: **"primera vez falla, segunda no"**.

#### 1.5.2 Diagnóstico

Cuando el terminal hace `guardar_parcial([us_<terminal>])`,
el framework no ve `us_<dueno>` en memoria. El bit del
dueño no se propaga al viaje viejo, y al guardar se pisa
su máscara. La próxima carga parcial del dueño no lo ve.

#### 1.5.3 Fixes intentados (ninguno alcanzó)

- **v78r**: regla de borrado de aristas con doble extremo.
- **v78s**: unir máscaras del BFS con las de memoria.

#### 1.5.4 Próximo paso sugerido

Instrumentar `_guardar_con_filtro`.

#### 1.5.5 Impacto actual

Resuelto. El viaje viejo ya no desaparece.

#### 1.5.6 Causa raíz y solución (v1.5i.8a)

**Causa raíz:** los IDs numéricos del framework son
**efímeros**. Cambian entre cada carga del grafo. Los
fixes previos intentaban hacer un guardado parcial
mapeando "ID del framework" ↔ "fila de la DB", y esa
correspondencia se rompía. El grafo creció a 99.290 nodos.

**Solución:** la DB no depende de los IDs del framework.

- `nodo_contexto`: `id_fila` (BIGINT AUTO_INCREMENT, PK),
  e `id_especial` (NULL para numéricos).
- `adyacente_contexto`: `idnodo_fila` y `idadyacente_fila`.
- Mapa `$_mapa_id_a_fila` relaciona ambos mundos dentro de
  una request.
- El DELETE parcial borra solo aristas cuyos DOS extremos
  están entre las filas cargadas.

#### 1.5.7 Nodos compartidos entre contextos (v78w)

**Síntoma:** el terminal cancela una venta y, al volver el
dueño, **desaparecen todas las ventas**.

**Causa:** una venta hecha por un terminal vive en **dos
árboles paralelos** (mismo nodo físico). Cuando el terminal
la destruía, el guardado parcial borraba la fila de la DB.
La arista del árbol del dueño quedaba huérfana.

**Solución:** soft delete coordinado. El terminal hace todo
el trabajo de negocio pero **no destruye** el nodo venta:
le pone un autoenlace `cancelada` y lo deja en el árbol del
dueño. Cuando el dueño visita su árbol, la destruye.

**Cambios en Venta.php:**

- `cancelar_venta`: rama nueva para terminal.
- `listar_ventas_por_dueno`: barrido de marcadas.
- `_venta_esta_cancelada()` y `_destruir_venta_marcada()`.
- `_buscar_venta_por_id`: rechaza canceladas.

**Autoenlace en vez de nodo hijo:** protección contra
destrucciones accidentales (`Nodo::eliminar` falla con
referencias entrantes).

#### 1.5.8 Conservar hd/hmi/p del árbol del dueño al marcar (v78z)

**Síntoma:** al recorrer el árbol del dueño con una venta
cancelada, el dueño destruía la primera cancelada y
**perdía todas las demás**.

**Causa:** el terminal limpiaba `hmi`/`hd`/`p` antes de
marcar. El `hd` es el enlace al siguiente hermano; sin él,
el traversal no puede llegar a los siguientes.

**Solución:** los enlaces del árbol del dueño solo se
limpian si el que cancela **destruye** el nodo. Para
terminal, se conservan. Los parametrizados del compartido
sí se limpian siempre.

#### 1.5.9 Reparación de árboles (v78y)

Comando `app:reparar_arboles`, ejecutable con
`index.php?reparar_arboles=1`. Repara aristas `hmi`
faltantes de todos los contenedores de árbol + cupones.
Idempotente.

#### 1.5.10 Inserción diferida (buzones) para ventas y cancelaciones (v79a)

**Síntoma:** el dueño no ve las ventas nuevas hechas por
el terminal. El terminal las ve (navega su árbol
parametrizado del compartido). El dueño solo ve las viejas
(de antes del repuntado B2.3.5).

**Causa raíz:** con carga parcial del terminal, el
subgrafo es `us_term → dueno → compartido`. El compartido
no tiene enlace al nodo real del dueño. `confirmar_venta_actual`
intentaba insertar la venta en el árbol del dueño real,
pero `Nodo::nodo_por_id('us_<dueno>')` devolvía null.
La venta quedaba solo en el árbol del compartido.

**Solución: inserción diferida (buzones).** El terminal
escribe en su árbol y deja un aviso en un buzón dentro del
compartido. El dueño, en su próxima carga, drena el buzón.

**Estructura:**

- `compartido_con_us_termX/pendientes_ventas`
- `compartido_con_us_termX/pendientes_cancelaciones`

**El bitmask lo propaga el framework, no la app.** Framework
v1.5i.8c: `_guardar_con_filtro` propaga máscaras a nodos
nuevos por enlaces entrantes (iterar hasta punto fijo).
El buzón hereda el bitmask del compartido; la venta hereda
del buzón o del árbol del compartido. La app no toca bits
a mano.

**Cambios en Venta.php:**

- `confirmar_venta_actual`: rama compartido → inserta
  solo en el árbol del compartido. Encola en el buzón.
- `_crear_nodo_cancelacion`: encola en el buzón si el
  contexto es compartido.
- `listar_ventas_por_dueno` y `listar_cancelaciones_de_dueno`:
  drenan buzones antes del barrido normal.
- Helpers nuevos: `_encolar_en_buzon`,
  `_procesar_pendientes_del_dueno`, `_drenar_buzon`,
  `_nodo_esta_en_arbol`.

**Bug del filtro (v79b).** En la primera prueba, el
buzón se creaba y las ventas nuevas se guardaban con
la máscara correcta (bit del dueño), pero el dueño no
las veía. Causa: el filtro de compartidos en
`_procesar_pendientes_del_dueno` era
`strpos($enlace, 'compartido_con_us_')`, pero los
compartidos se llaman `compartido_con_<nombre>` (con
el nombre de usuario del terminal, sin `us_`). El
filtro nunca matcheaba, el buzón nunca se drenaba, y
las ventas quedaban varadas.

Fix: `strpos($enlace, 'compartido_con_')`. El mismo
bug estaba latente en `reparar_arboles.php` (helper
hermano), corregido en la misma tanda. Lección:
auditar todos los filtros de compartidos con
`grep 'compartido_con_us_'`.

**Verificación:**

1. Como terminal: vender 2 ventas.
2. Como dueño: verificar que aparecen las 2.
3. Como terminal: cancelar 1.
4. Como dueño: verificar que aparece solo la otra.
5. SQL: `SELECT enlace FROM adyacente_contexto WHERE
   enlace LIKE 'pendientes_%';` debe devolver 0
   filas (buzones drenados).

#### 1.5.11 Helper de nombres de árbol con contexto implícito (v79c)

**Síntoma:** logueado como terminal, en la pestaña
Clientes, un pasajero con pasajes no mostraba el badge
"tiene pasajes" ni el botón "Ver pasajes" ni el aviso
de "imprimir pasajes actualizados". Como dueño sí
funcionaba.

**Causa:** tres funciones de `Pasajero.php`
(`pasajero_tiene_pasajes`,
`pasajero_tiene_pasajes_activos`,
`pasajero_tiene_ventas_activas`) llamaban
`_nombres_arbol_para_contexto($nodo_contexto, '')`.
Con `''` como nombre de terminal, el helper devolvía
null (por el `if ($nombre_terminal === '')`), y las
funciones caían a los nombres default (`hmi`/`hd`).
Pero el contenedor de ventas del compartido usa
nombres parametrizados (`hmi_<term>`). `hmi($cont, null)`
buscaba `hmi`, no lo encontraba, devolvía null, y el
while no corría.

**Solución:** hacer que `_nombres_arbol_para_contexto`
derive el nombre del terminal desde el propio
compartido cuando no se lo pasan. El compartido tiene
`terminales → <nombre_terminal>`, y ese nombre es el
que se necesita para construir los enlaces
parametrizados. Retrocompatible: si el llamador pasa el
nombre, se usa tal cual.

**Helper nuevo:** `_nombre_terminal_de_compartido`
(`miscelaneas/Arbol.php`). Deriva el nombre del
terminal del contenedor `terminales` del compartido.

**Lección:** un helper que necesita un dato que el
llamador no tiene por qué conocer debe derivarlo del
contexto, no confiar en que el llamador se lo pase.
El patrón "`''` = no aplica" es una trampa: mezcla
"no aplica" con "no me lo pasaron". Ver §5.12 del
prompt de seguridad.

#### 1.5.12 obtener_pasajero_por_dni con contexto (v79d)

**Síntoma:** logueado como terminal, en Clientes, el
botón "Ver pasajes" aparecía (fix v79c), pero al
abrirlo el modal decía "Sin pasajes registrados"
aunque el pasajero sí tuviera ventas.

**Causa:** `obtener_pasajero_por_dni` iteraba sobre la
raíz `usuarios` para buscar las ventas del pasajero.
Con carga parcial del terminal, `usuarios` no está en
memoria: `$raiz_usuarios` es null, el bloque no corre,
y `$ventas` queda vacío.

Es uno de los 7 helpers B3.3c (ver §1.10) que iteran
sobre todos los dueños. La lista completa:

- `Venta.php`: `_buscar_venta_por_id` (fallback),
  `obtener_cancelacion_por_id`.
- `Liquidacion.php`: `obtener_liquidacion_por_id`.
- `Rendicion.php`: `aceptar_ajuste_rendicion`,
  `obtener_rendicion_por_id`.
- `Pasajero.php`: `obtener_pasajero_por_dni`
  (rama del historial de ventas).

**Solución parcial:** `obtener_pasajero_por_dni` ahora
recibe `$nodo_contexto`. Si viene, navega el árbol del
contexto (parametrizado si es compartido). Si no,
mantiene el comportamiento actual (iterar `usuarios`).

**Pendiente:** los otros 5 helpers siguen igual. Se
resuelven cuando aparezca dolor real (por ejemplo,
que el terminal intente cancelar desde el plugin y
el helper no encuentre la venta), o cuando lleguen
los iteradores con ID especial (§1.10).

**Nota secundaria:** `obtener_reservas_de_pasajero`
sigue llamándose sin contexto desde
`obtener_pasajero_por_dni`. Con carga parcial,
devuelve vacío para el terminal. **Es correcto**:
el terminal no debe ver las reservas del equipo.

#### 1.5.13 Botones de impresión del micro para terminal (v79e)

**Síntoma:** los botones "Imprimir Croquis",
"Imprimir Planilla" e "Imprimir Planilla Vacía"
aparecían para el dueño pero no para la terminal.

**Causa:** condición JS en `renderizar_pasaje_micro`
(`viajes-asientos.js`):

```js
if (usuario_actual.nivel === 'dueno' || es_admin_o_soporte())
```

La terminal quedaba afuera. Cuando se agregaron esos
botones, el backend de impresión no estaba adaptado a
carga parcial. Hoy sí: `_contextos_para_operacion`
devuelve `['us_<dueno>']` para `?imprimir=1&dueno=X`
(salvo los tipos B3.3c, que no incluyen croquis ni
planilla).

**Solución:** agregar `terminal` a la condición.

**Nota:** la información que exponen croquis y
planilla (nombre de pasajero, DNI, contacto) ya la
ve la terminal en el modal "Ver pasaje" y en el
propio croquis. No es una fuga nueva.

**Deuda de seguridad anotada:** las URLs de impresión
(`?imprimir=1&tipo=...`) no validan sesión ni
permisos. Cualquiera con la URL puede ver los datos.
Es un problema preexistente. Anotado para una tanda
futura.

#### 1.5.14 Arista `us_Administrador → usuarios` perdida tras recuperar desde backup (v79f)

**Síntoma:** logueado como admin, todas las pestañas
mostraban el select de dueño vacío. Sin poder elegir
dueño, ninguna pestaña funcionaba.

**Causa:** la arista `us_Administrador → usuarios`
no existía en el grafo. `listar_duenos` navega por
esa arista para llegar a `usuarios` y enumerar los
dueños. Sin ella, devolvía vacío. `usuarios` tenía
el bit del admin (máscara 4) pero como residuo:
en algún momento la arista existió, después se
perdió, y el bitmask no se actualizó.

**Cómo se perdió:** el flujo de recuperación tras
un TRUNCATE + `?migrar_a_sql64=1` copia fielmente
el grafo de las tablas viejas (`nodo`/`adyacente`).
Si la arista no estaba en esas tablas, no se crea.
El bloque `?reparar_topologia=1` (v78n) reparaba
aristas del dueño pero **no** la del admin.

**Fix:** agregar `migrar_topologia_admin($nombre_app)`
al bloque `?reparar_topologia=1`. Idempotente. El
total reportado incluye la creación si ocurrió.

**Lección:** el bloque `?reparar_topologia=1` debe
cubrir **todas** las aristas estructurales del
modelo: admin → usuarios, dueño → compartido,
viajes/empresas/pasajeros del dueño. Si una
arista estructural puede perderse, debe poder
repararse.

#### 1.5.15 Botón "Limpiar viajes de prueba" rompía los compartidos (v79j)

**Síntoma:** después de usar el botón "Limpiar viajes
de prueba" como admin, el viaje principal desapareció
de la pestaña Viajes de los terminales. El dueño y el
admin seguían viéndolo.

**Causa raíz:** `limpiar_viajes_de_prueba` destruía
los viajes sin llamar a `_desincronizar_viaje_de_compartidos`.
Los compartidos de las terminales quedaban con aristas
colgadas al nodo viaje destruido. En la próxima carga
parcial del terminal, el BFS del bitmask se rompía, y
el compartido quedaba sin sus viajes. Como no había
ninguna copia del compartido, el terminal no veía
ningún viaje.

**Cómo se llegó ahí:** `eliminar_viaje` (Fase B2.4.2,
v77q) había agregado `_desincronizar_viaje_de_compartidos`
antes de destruir el subárbol. `limpiar_viajes_de_prueba`
se escribió antes (v74z) y nunca se actualizó.

**Fix:**

1. Agregar `_desincronizar_viaje_de_compartidos($nodo_viaje)`
   en `limpiar_viajes_de_prueba`, antes de
   `_destruir_viaje_completo`.
2. Nuevo helper `reparar_compartidos_viajes_dueno` en
   `miscelaneas/migrar_topologia.php`. Recorre cada
   dueño, cada viaje y cada terminal autorizada, y
   llama a `_sincronizar_terminal_en_compartido_del_viaje`.
   Se ejecuta desde `?reparar_topologia=1`. Idempotente.
3. Ampliar los prefijos de prueba: `viajeventgraf`,
   `viajecambiomicro`, `viajeregres`, y corregir el typo
   `viajedeselgraf` → `viajedeselegraf` (v79m).
4. Botón solo admin: CSS defensivo
   (`estilos-botones-admin.css`) + clase `rol-admin` en
   el body desde `aplicacion.js`.

**Verificación:** ejecutar `?reparar_topologia=1`. Debe
reportar `compartidos asegurados: N` y
`viajes sincronizados: N`. Después, el terminal ve sus
viajes de nuevo.

**Lección:** toda función que destruya un viaje debe
desincronizarlo de los compartidos primero. El patrón
está en `eliminar_viaje`. Ver §5.9 del prompt de
seguridad (regla de detección para estructuras nuevas).

#### 1.5.16 Runner de bumps pisaba los reemplazos (v79o)

**Síntoma:** al aplicar un script con `$version_nueva` y
reemplazos sobre el mismo archivo, los reemplazos se
perdían. El `@version` se bumpeaba, pero el resto del
cambio no se escribía.

**Causa raíz:** el runner aplicaba los reemplazos en
memoria (`$archivos_a_escribir[$ruta] = [contenido, eol]`)
y después, para el bump, releía el archivo desde el
disco y pisaba la entrada en memoria con el contenido
sin reemplazos + el bump.

**Fix:** en las fases 2a y 2b del runner, si el archivo
ya está en `$archivos_a_escribir`, el bump parte de ese
contenido en memoria. Solo si no está (no tiene
reemplazos pendientes) lee del disco.

**Detectado en:** aplicación del script de P55 v6x. El
`@version` se bumpeó a `1.5plugin.6x` pero los
`nombre_campo: "foto"` no se escribieron.

**Lección:** en cualquier pipeline de transformaciones
sobre un mismo archivo, las fases posteriores deben
operar sobre el contenido ya transformado, nunca sobre
el original. Ver `prompt_sistema_scripts_v2.md` §18
lección 22.

### 1.6 Pendientes de la línea

**Estado del inventario de estructuras compartidas.**
Ver prompt de seguridad §5.9. Resumen:

- **Ventas y cancelaciones:** resueltas en v79a con
  buzones. Ver §1.5.10.
- **Rendiciones, liquidaciones:** solo el dueño las crea.
  No tienen el problema.
- **Cupones, paradas, ajustes:** no se agregan desde
  terminal. No tienen el problema.
- **Micros, empresas, vehículos, terminales, etc.:**
  contenedores planos, no árboles. No tienen el problema.
- **Asientos:** solo se modifica el `estado` del nodo.
  No tienen el problema.
- **Pasajeros:** el compartido es alias del contenedor
  del dueño. No tienen el problema.

**Pendiente único:** auditar estructuras nuevas con la
regla de detección de §5.9 del prompt de seguridad.

**Otros pendientes:**

- **Espejo JS del fix `id_fila`.** HECHO en
  V1.5plugin.7a (framework JS V1.5i.8b).
- **Espejo JS del fix 1.5i.8c.** HECHO en V1.5plugin.7b.
- **Carga parcial activa.** `_contextos_para_operacion`
  está activo desde v78g.
- **Batería completa de pruebas con el plugin, por rol.**
  Pendiente. Ver §2.3.
- **B3.3c** (7 helpers que iteran sobre todos los dueños).
  Postergado. Ver §1.10.
- **Módulos menores sin contexto:** `Liquidacion.php`,
  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-
  tandas cortas.
- **Plugin: dividir la ventana en pestañas, una por rol.**
  Idea anotada al cierre de v78v.
- **Deuda de seguridad en impresión.** Las URLs de
  impresión (`?imprimir=1&tipo=...`) no validan sesión
  ni permisos. Cualquiera con la URL ve el HTML. Se
  anota para una tanda futura, cuando se rehaga el
  módulo de impresión o se agregue un chequeo general.

**Pendientes nuevos del cierre v79j-v79o:**

- **Botón "Eliminar cancelaciones de prueba".** Limpia
  los nodos Cancelación que dejó la limpieza profunda de
  viajes con ventas de prueba (v79l). Análogo al de
  "Limpiar viajes de prueba".
- **Limpieza de empresas/vehículos de prueba.** Los
  prefijos `viajecambiomicro_emp_*` y
  `viajecambiomicro_veh_*` que crea la prueba 36 no
  tienen cleanup. Se agrega al botón de "Limpiar viajes
  de prueba" o a uno propio.
- **Llamar a `reiniciar_seleccion_completa` desde el
  login de terminal.** Hoy el endpoint existe pero solo
  lo llaman las pruebas del plugin. Si un usuario cierra
  el navegador a mitad de una venta, la venta_actual
  queda colgada para siempre. Se puede limpiar al
  arrancar la sesión del terminal.
- **Refactor del plugin: centralizar el payload.** Un
  helper `_post(ctx, accion, params)` que inyecte
  `nombre_solicitante` y `nombre_dueno` automáticamente.
  Evita la clase de bugs de "agregar campo al POST pero
  no a la firma" (v6q).
- **Test de "linting" del plugin.** Verifica que ningún
  `pedir_post` a acciones de `vehiculos/*`, `empresas/*`,
  etc. salga sin `nombre_dueno`.
- **Auditar estados colgados.** Reservas colgadas,
  venta a mitad de venta, DJ a medias, edición de viaje
  con paradas. Empezar por el caso "cierre de navegador
  a mitad de venta".

### 1.7 Plan de la línea de tandas (fases)

**Fase A** — Usuarios como IDs especiales `us_<nombre>`.
**Completada** (v76k).

**Fase B1** — Contenedores `publico`/`privado` en cada
usuario. **Completada** (v76m).

**Fase B2.1** — Compartidos por terminal. **Completada** (v76q).

**Fase B2.2** — Contexto opcional en Viaje, Venta,
ViajeAsientos, Empresa. **Completada** (v76r-v76u).

**Fase B2.3** — Árboles paralelos con nombres parametrizados.
**Completada** (v76v-v77o).

**Fase B2.4** — Mantener los compartidos vivos. **Completada**
(v77q).

**Fase B3** — Eliminar los accesos viejos. **Cerrada salvo
B3.3c**.

**Carga parcial (Etapa 1, v78b-v79a)** — Aplicada.
  1a (framework): `guardar_parcial` real + espejo JS.
  1b (framework): `contextos_cargados` + getter.
  1c.1 (migraciones): SQL64 + topología admin/soporte.
  1c.2 (piloto): `_contextos_para_operacion` + adaptación
    del contexto en módulos principales.
  Fixes v78v, v78w-v78z, v79a.

**Fase C** (opcional) — Tipos como IDs especiales.
**Pendiente.**

**Fase D** — Carga parcial como optimización.
**Pendiente.**

**Línea de cierre de grafo + plugin + scripts (v79j-v79o).**
**Completada.**
  - v79j: fix del botón + compartidos + botón solo admin.
  - v79k-v79m: prefijos, reportar conservados, fix typo.
  - v79l: regla de ventas de prueba.
  - v79n-v79o: runner v2 con bumps automáticos + fix
    del pisado de reemplazos + consolidación del
    prompt de scripts.
  - v6w-v6z del plugin: try/finally en pruebas 34-36,
    fixes de P55.

### 1.8 Espejo JS del framework

El framework PHP y el espejo JS van en sincronía.

- **PHP:** `PerdurarSuperestructuraStringSQL64.php`
  en versión 1.5i.8c. Esquema `id_fila` + `id_especial`,
  mapa estático, propagación de máscaras a nodos nuevos.
- **JS:** `PerdurarSuperestructuraStringIndexedDB64.js`
  en versión V1.5i.8c. Mismo esquema, misma propagación.

### 1.9 Lecciones de la sesión v78b-v79a

**Sobre el framework y los IDs:**

1. **En carga parcial, no todos los IDs especiales están en
   memoria.** El BFS de máscaras parte solo de los que hay.
2. **Las reparaciones topológicas son necesarias pero no
   suficientes.**
3. **Los IDs numéricos del framework son efímeros.** El
   guardado parcial tiene que basarse en `id_fila`.
4. **El bitmask lo propaga el framework, no la app.** Si
   la app asigna bits a mano, la seguridad deja de emerger
   de la topología.

**Sobre la persistencia:**

5. **La regla de borrado de aristas importa.**
6. **La preservación de bits de máscara es la segunda mitad.**
7. **Un nodo nuevo hereda máscaras por enlaces entrantes.**
   El framework lo propaga en `_guardar_con_filtro`.

**Sobre los nodos multi-contexto:**

8. **Un nodo con bits de varios contextos no puede ser
   destruido por uno solo.** Soft delete coordinado.
9. **Al marcar, conservar los enlaces del árbol del
   contexto que verá el nodo.**
10. **Un contexto que no puede insertar en el árbol del
    otro deja un aviso en un buzón.**

**Sobre las pruebas del plugin:**

11. **El orden de las pruebas importa.**
12. **"Primera vez falla, segunda no"** apunta a un estado
    que se pisó una vez.

**Sobre destrucción de nodos y compartidos (v79j):**

13. **Toda función que destruya un viaje debe
    desincronizarlo de los compartidos primero.** El
    patrón está en `eliminar_viaje` (v77q).
    `limpiar_viajes_de_prueba` se escribió antes y nunca
    se actualizó. Lección: cuando se agrega un patrón
    nuevo (v77q), auditar todas las funciones que hacen
    lo mismo (v74z lo hacía distinto).
14. **`?reparar_topologia=1` debe cubrir TODAS las
    aristas estructurales.** Cuando una arista se puede
    perder, tiene que poder repararse.

**Sobre criterios por string (v79j, v79m):**

15. **Los prefijos con typo fallan silenciosamente.**
    `viajedeselgraf` vs `viajedeselegraf`. Los 10 viajes
    de la prueba 35 quedaban conservados sin aviso.
    Lección: cuando se agrega un criterio por string,
    verificar el caso positivo y el negativo.
16. **El email `@test.local` es la marca de prueba.**
    Solo los emails que terminan en `@test.local` marcan
    una venta como de prueba. Emails reales, vacíos o
    inválidos se tratan como reales.

**Sobre el sistema de scripts (v79n, v79o):**

17. **Los bumps automáticos eliminan una clase entera de
    errores.** Antes había que recordar la versión vieja,
    el espaciado, el `?v=` del HTML. Ahora se declara
    `$version_nueva` y listo.
18. **En un pipeline de transformaciones, cada fase debe
    operar sobre el resultado de la anterior, no sobre
    el original.** El runner releía del disco para el
    bump y pisaba los reemplazos en memoria.

### 1.10 Diseño futuro: iteradores con ID especial

Anotado en v78a. No implementado. Objetivo: cuando el
framework tenga iteradores con ID especial persistente y un
usuario que los "ocupe", los B3.3c se resuelven naturalmente.

Idea:

- Iteradores con ID especial (`it_<nombre>`) como raíces de
  subgrafo.
- Un iterador cargado trae su subgrafo alcanzable.
- Un iterador tiene un usuario que lo "ocupa".
- Especialización por tipo.

Detalle completo en `prompt_framework_iteradores.md` §11.5.

### 1.11 Cierre de la Fase 2c — migración del plugin a `_post`

**Estado:** cerrada entre v1.5piloto.80a y v1.5piloto.80e
(del lado del piloto) y v1.5plugin.7a-7k (del lado del
plugin).

**Qué se hizo.**

- **Fase 1 de §3 (v1.5piloto.80a):** 5 endpoints backend
  de limpieza de prueba:
  - `ventas/limpiar_prueba` (comprador con email
    `@test.local`).
  - `cancelaciones/limpiar_prueba` (motivo con
    "prueba"/"test").
  - `empresas/limpiar_prueba` (prefijos `fotoauto`
    y `viajecambiomicro_*`).
  - `administrador/limpiar_usuarios_prueba`
    (nombre_usuario con prueba/test/temp).
  - `dueno/limpiar_terminales_prueba` (prefijo
    `termprueba`).
  Todos chequean admin + modo pruebas. Saltean
  usuarios/entidades con referencias entrantes
  (conservador).

- **Fase 2 (plugin, v1.5plugin.7a-7k):** migración completa
  de las ~50 pruebas y 3 helpers auxiliares al wrapper
  `_post(ctx, accion, params, opts)` que delega en
  `ctx.post_accion(accion, params, opts)`. Este último:
  - resuelve `nombre_solicitante` del logueado (con
    cache invalidado en `asegurar_login` y
    `cerrar_sesion`),
  - inyecta `nombre_dueno` si no viene en params,
  - soporta `sin_solicitante`, `sin_dueno`,
    `devolver_crudo`, `lanzar_en_error_backend`,
  - respeta `params.nombre_solicitante` explícito
    (para casos "admin opera como terminal").

- **v1.5piloto.80b:** subir `BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS`
  de 2 a 10. Con el grafo actual (~3000 nodos), los 5
  intentos fallidos consecutivos de la prueba 54 tardan
  más que 2s. El bloqueo funcionaba; era timing.

- **v1.5piloto.80c:** logs `[DIAG-AUTH]` temporales en
  `Autenticacion.php` para diagnosticar lo anterior.

- **v1.5piloto.80d:** limpieza — se sacaron los logs
  temporales y se arregló el doble `time()` en
  `_esta_bloqueado` (bug latente: la comparación podía
  fallar en el borde del segundo).

**Lecciones aprendidas.**

1. **`post_accion` cambia el modo de carga del backend.**
   Antes, `ctx.pedir_post` sin `nombre_solicitante` hacía
   **carga total** (grafo completo en memoria). Con
   `post_accion`, el backend hace **carga parcial**
   (`us_<sol>`) y no puede navegar `us_<dueño>` si el
   logueado no es el dueño. Las pruebas que necesitan
   carga total usan `{sin_solicitante: true,
   sin_dueno: true}`. Documentado en el prompt del
   plugin (§8, lección 57).

2. **Si un fix no resuelve, verificar primero que los
   fixes previos se hayan aplicado.** Costó 3 iteraciones
   en la prueba 54 por no chequear el valor real de
   `BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS`.

3. **Pruebas con tiempos de bloqueo fijos son frágiles
   ante el crecimiento del grafo.** El rate limiting
   funcionaba; los 5 POSTs juntos tardaban más que los
   2s del bloqueo.

**Pendientes que abre este cierre.**

- **Verificación final del plugin:** correr la suite
  completa (65 pruebas) para confirmar que la migración
  no rompió nada.
- **Llamar a `reiniciar_seleccion_completa` desde el
  login de terminal.** Caso real: usuario cierra el
  navegador a mitad de venta y la venta_actual queda
  colgada. Hoy el endpoint existe pero solo lo llaman
  las pruebas.
- **Auditar estados colgados:** reservas, DJ a medias,
  edición de viaje con paradas.
- **Auditar estructuras nuevas** con la regla de §5.9
  del prompt de seguridad.
- **Adaptar los 4 módulos menores** (Liquidacion,
  Rendicion, Empresa, Vehiculo) al contexto del
  solicitante.
- **B3.3c** (los 7 helpers que iteran sobre todos los
  dueños).

**Próxima tanda sugerida:** verificación final del
plugin, después elegir el siguiente frente de la lista.

---

## 2. PLAN DE LA LÍNEA DE TANDAS

### 2.1 Contexto

La línea en curso es la **carga parcial por contexto**.
Objetivo: que cada operación cargue solo el subgrafo que
necesita.

### 2.2 Estado por fase

1. **Modelo topológico (Fases A, B1-B3).** Aplicado.
   Pendiente: B3.3c.
2. **Framework — SQL64.** Aplicado (1.5i.8c). Bugs §1.5 y
   §1.5.10 resueltos.
3. **Migraciones del piloto.** Aplicadas.
4. **Espejo JS.** Alineado a V1.5i.8c.
5. **BUG §1.5 + §1.5.7 + §1.5.8 + §1.5.10.** Resueltos.

### 2.3 Próximos pasos sugeridos

Ordenados por prioridad:

1. **Verificación final del plugin.** Correr toda la
   suite del plugin (24 pruebas del grafo + las de
   fotos, DJ, etc.) para confirmar que todo sigue verde
   después de los fixes v6w-v6z.
2. **Botón "Eliminar cancelaciones de prueba".** Limpia
   los nodos Cancelación que dejó la limpieza profunda
   de viajes con ventas de prueba (v79l). Va junto con
   la limpieza de empresas/vehículos de prueba.
3. **Llamar a `reiniciar_seleccion_completa` desde el
   login de terminal.** Cubre el caso real "usuario
   cierra el navegador a mitad de venta". Hoy el
   endpoint existe pero solo lo usan las pruebas.
4. **Refactor del plugin: centralizar el payload.**
   Helper `_post(ctx, accion, params)`. Base para que
   no vuelvan a pasar bugs como el de `nombre_dueno`
   (v6q).
5. **Auditar estados colgados** (reservas, DJ, edición
   de viaje). Empezar por "cierre de navegador a mitad
   de venta".
6. **Auditar estructuras nuevas** con la regla de §5.9
   del prompt de seguridad.
7. **Adaptar los 4 módulos menores** (Liquidacion,
   Rendicion, Empresa, Vehiculo).
8. **B3.3c** cuando aparezca dolor real o cuando lleguen
   los iteradores con ID especial.


---

## 3. PLAN: MUDANZA DE BOTONES DE LIMPIEZA AL PLUGIN

Anotado al cierre de v1.5piloto.79o. No implementado todavía.
Objetivo: mover los botones de limpieza de datos de prueba de
la app piloto al plugin de pruebas. La app piloto solo mantiene
los endpoints backend.

### 3.1 Motivo

Los botones de limpieza son herramientas de testing, no de
producción. Hoy viven en la UI del piloto (pestaña Viajes,
pestaña Clientes, pestaña Grafo), visibles solo para admin en
modo pruebas. Pero el usuario que corre pruebas ya está en el
plugin: es natural que los botones de limpieza estén al lado
de las pruebas, en la misma ventana.

**Ventajas:**

1. Un solo lugar para correr pruebas y limpiar.
2. La app queda sin código de testing. Los endpoints son APIs
   limpias, reusables a futuro.
3. Cuando agreguemos un tipo nuevo de prueba, el botón va al
   plugin; la app no se toca.
4. `body.rol-admin` + `body.modo-pruebas` + el CSS defensivo
   de v79j quedan obsoletos. Se eliminan.

**Trade-off aceptado:** si alguien quiere limpiar sin el
plugin, tiene que llamar al endpoint a mano. Es aceptable —
son herramientas de admin + modo pruebas.

### 3.2 Qué se elimina del piloto (Fase 3)

- `estilos-botones-admin.css` (creado en v79j).
- Link al CSS en `aplicacion_GET.html`.
- `_actualizar_visibilidad_boton_limpiar_viajes` y el
  listener del botón en `Aplicacion/Viajes/viajes-nucleo.js`.
- `_aplicar_clases_de_rol` y sus llamadas en `aplicacion.js`
  (ya no hay CSS que dependa de las clases `rol-admin` y
  `modo-pruebas`).
- Botón "Limpiar viajes de prueba" del HTML.
- Botón "Limpiar pasajeros de prueba" del HTML.
- Lógica JS de esos botones.

**No se elimina:**

- Los endpoints backend (`viajes/limpiar_prueba`,
  `pasajeros/limpiar_prueba`, `grafo/eliminar_huerfanos`,
  y los que se agreguen en Fase 1).
- El chequeo `Entorno::es_pruebas()` en el backend.
- El chequeo de nivel admin en el backend.

### 3.3 Endpoints backend del piloto (Fase 1)

**Ya existen:**

- `viajes/limpiar_prueba` — `limpiar_viajes_de_prueba`. Amplía
  prefijos según v79j-v79m. Limpia viajes de prueba, con regla
  de ventas de prueba (v79l: solo `@test.local` marca prueba).
- `pasajeros/limpiar_prueba` — `limpiar_pasajeros_de_prueba`.
  Email `@test.local`.
- `grafo/eliminar_huerfanos` — comando
  `grafo:eliminar_huerfanos`.

**A agregar en Fase 1:**

1. `ventas/limpiar_prueba` — nuevo helper
   `limpiar_ventas_cancelaciones_de_prueba($nombre_dueno)`.
   Limpia:
   - Ventas cuyo comprador tenga email `@test.local`.
   - Nodos Cancelación cuyo motivo contenga "prueba" o "test"
     (case-insensitive), o cuyo `comprador_dni` empiece con
     prefijos conocidos (`cancelar_`, `auto_`, `regres_`,
     `dj_`, `foto`).
2. `empresas/limpiar_prueba` — nuevo helper
   `limpiar_empresas_vehiculos_de_prueba($nombre_dueno)`.
   Limpia empresas y vehículos con prefijos conocidos
   (`fotoauto`, `viajecambiomicro_emp_`, `viajecambiomicro_veh_`,
   y los que se agreguen).
3. `administrador/limpiar_usuarios_prueba` — nuevo helper
   `limpiar_usuarios_de_prueba()`. Criterio a consensuar:
   - `nombre_usuario` contiene `prueba`, `test` o `temp`
     (case-insensitive).
   - O `nombre_real` contiene `Prueba` o `Test`.
   - O `email` termina en `@test.local`.
4. `dueno/limpiar_terminales_prueba` — nuevo helper
   `limpiar_terminales_de_prueba($nombre_dueno)`. Prefijo
   `termprueba`.

**Todos:** chequeo de nivel admin + `Entorno::es_pruebas()`.

**Formato de respuesta:** `{exito, borrados: [...], cantidad_borrados: N, ...}`. Mismo patrón que `limpiar_viajes_de_prueba`.

### 3.4 Reorganización del plugin en pestañas (Fase 2)

Cambio de estructura de `catalogo.js`: pasa de `SECCIONES` a
`PESTAÑAS` (con secciones adentro). Cada pestaña tiene su botón
"Correr todas" y su botón de limpieza.

**Mapeo propuesto:**
Plugin
├── Base [ ] Limpiar todo lo de prueba
├── Administrador [ ] Limpiar usuarios de prueba
│ [ ] Limpiar terminales de prueba
├── Empresas/Micros [ ] Limpiar empresas y vehículos de prueba
├── Viajes [ ] Limpiar viajes de prueba
├── Vendidos [ ] Limpiar ventas y cancelaciones de prueba
├── Clientes [ ] Limpiar pasajeros de prueba
└── Grafo [ ] Eliminar nodos basura

text

**Bonus:** botón "Limpiar todo lo de prueba" en Base que llama
a todos los endpoints en orden. Útil al empezar una sesión con
el grafo sucio.

**Cambios técnicos:**

- `catalogo.js`: array `PESTAÑAS` con `{id, nombre, secciones, boton_limpieza}`.
- `ventana.html`: div para las pestañas + un contenedor de
  secciones que cambia según la pestaña activa.
- `ventana.js`: renderizar pestañas + secciones + botones.
- Agregar un helper `ctx.limpiar(categoria)` que llame al
  endpoint correspondiente y muestre el resultado.

**Las pruebas no cambian.** Solo cambia cómo se agrupan en el
catálogo y cómo se renderizan en la ventana.

**Fase previa a Fase 2:** confirmar el esquema de pestañas
(¿agregar/quitar alguna?) y la ubicación exacta de cada
prueba en el nuevo esquema.

### 3.5 Fase 3: limpieza de la app

Una vez que los botones del plugin funcionan, eliminar del
piloto todo lo que sobra. Ver §3.2 para el listado.

### 3.6 Decisiones pendientes

Antes de arrancar Fase 1:

1. **Criterio de "usuario de prueba".** ¿Solo nombre_usuario
   con `prueba`/`test`/`temp`? ¿O también nombre_real con
   "Prueba"? ¿O email `@test.local`? Propuesta: los tres
   criterios en OR.

2. **Un solo endpoint para ventas + cancelaciones o dos
   separados.** Propuesta: uno solo
   (`ventas/limpiar_prueba`). Las cancelaciones son
   consecuencia de las ventas de prueba — se limpian juntas.

3. **Prefijos de terminales de prueba.** ¿Solo `termprueba`?
   ¿Algún otro?

4. **¿"Limpiar todo lo de prueba" en Base llama a todos los
   endpoints, o es un endpoint nuevo del piloto que hace todo
   de una?** Propuesta: el plugin llama a los endpoints
   individuales en secuencia. No hay endpoint "todo" en el
   backend, es una composición del lado del plugin.

5. **¿El botón "Limpiar todo" es uno solo, o hay uno por
   pestaña y un botón global extra?** Propuesta: uno global en
   Base + uno por pestaña.

### 3.7 Orden de implementación

1. **Fase 1**: agregar los 4 endpoints nuevos al backend del
   piloto. Tanda mediana (4 helpers + 4 subacciones).
2. **Fase 1.5**: probar cada endpoint desde el navegador a
   mano (llamada POST) para verificar que funciona.
3. **Fase 2**: refactor del plugin. Cambio de `SECCIONES` a
   `PESTAÑAS` + botones de limpieza + helper `ctx.limpiar`.
4. **Fase 3**: eliminar del piloto todo lo que sobra (§3.2).

Cada fase es una tanda. La Fase 1 lleva su script en el
proyecto PHP, la Fase 2 lleva su script en `iteradoresJS/`,
la Fase 3 lleva su script en el proyecto PHP.


---

**FIN DEL PLAN ACTUAL**