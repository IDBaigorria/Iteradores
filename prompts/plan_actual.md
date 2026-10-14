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

**Última actualización de este archivo:** v1.5piloto.79b.

Cierre del fix del bug §1.5 (viaje viejo) y de los bugs
§1.5.7, §1.5.8, §1.5.10 (nodos compartidos entre contextos,
inserción diferida con buzones). La carga parcial está
activa desde v78g. La topología sigue siendo la fuente de
la seguridad: el bitmask lo propaga el framework, no la app.

**Bug §1.5 RESUELTO en v78v.** Causa raíz: IDs numéricos
del framework efímeros. Solución: `id_fila` (BIGINT
AUTO_INCREMENT) en `nodo_contexto`. Ver §1.5.6.

**Bug §1.5.7 RESUELTO en v78w-v78z.** Nodos compartidos
entre contextos no pueden ser destruidos por el guardado
parcial de un solo contexto. Solución: soft delete
coordinado con autoenlace `cancelada`. Ver §1.5.7 y §1.5.8.

**Bug §1.5.10 RESUELTO en v79a.** El terminal vende/cancela
y el dueño no ve los nodos nuevos (con carga parcial,
`us_<dueno>` no está en memoria del terminal). Solución:
inserción diferida con buzones + propagación de máscaras a
nodos nuevos por el framework (v1.5i.8c). Ver §1.5.10.

Próximo paso sugerido: batería completa de pruebas con el
plugin, por rol. Ver §2.3.

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
  Ver §2.3.
- **B3.3c** (7 helpers que iteran sobre todos los dueños).
  Postergado. Ver §1.10.
- **Módulos menores sin contexto:** `Liquidacion.php`,
  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-
  tandas cortas.
- **Plugin: dividir la ventana en pestañas, una por rol.**
  Idea anotada al cierre de v78v.

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

1. **Batería completa de pruebas con el plugin,**
   organizadas por rol. Reorganizar la ventana del plugin
   en pestañas (base + admin + dueño + terminal +
   soporte + multi-rol). Es el próximo foco.
2. **Auditar estructuras nuevas** con la regla de §5.9
   del prompt de seguridad.
3. **Adaptar los 4 módulos menores** (Liquidacion,
   Rendicion, Empresa, Vehiculo).
4. **B3.3c** cuando aparezca dolor real o cuando lleguen
   los iteradores con ID especial.

---

**FIN DEL PLAN ACTUAL**