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

**Última actualización de este archivo:** v1.5piloto.78v.

Cierre de la línea de carga parcial (v78b-v78s). Toda la
infraestructura de contextos está aplicada: SQL64 como
método de persistencia, `guardar_parcial` en PHP,
`Controlador::$contextos_cargados`, `_contextos_para_operacion`
en el Enrutador, adaptación del contexto del solicitante en
los módulos principales (Viajes, Venta, Pasajeros,
Impresión) y reparación topológica.

**BUG PERSISTENTE ACTIVO (§1.5).** El bug de "el viaje viejo
desaparece cuando el terminal hace una operación" **no está
resuelto**. Los fixes v78r (regla de borrado de aristas) y
v78s (preservar bits de máscara) no alcanzaron. Se sigue
investigando. Ver §1.5 para el diagnóstico completo y las
hipótesis descartadas.

Próximo paso sugerido: retomar el bug §1.5 con logs de
diagnóstico en el `_guardar_con_filtro` (qué máscaras tenía
cada nodo antes y después de cada guardado parcial del
terminal). Ver §1.5.4.

### 1.2 Cadena de cierres de la sesión v78b-v78s

- **v78b** (Etapa 0 carga parcial): `delegar_con_args` en el
  Controlador. `guardar_parcial` con chequeos (grafo parcial
  + nodos desocupados). Docblock del flag `$grafo_parcial`.
  `_contextos_para_operacion` (stub) en el Enrutador.
- **v78c** (Tanda 1b): `validar_token_sesion` y
  `_construir_respuesta_login` usan `us_<nombre>` directo.
  `Controlador::$contextos_cargados` + getter.
  `guardar_ambos` consulta los contextos cargados.
- **v78d**: chequeos defensivos de grafo parcial en
  `Usuario.php` (6 funciones) y `Migraciones/Funciones.php`
  (2 detectores). **Revertidos en v78o.**
- **v78e** (Tanda 1c.1a): `SQL64` como método de
  persistencia. Bloque `?migrar_a_sql64=1`. Corrección de
  la secuencia de migración (bloque movido antes de la
  carga normal).
- **v78f** (Tanda 1c.1b): migración topológica.
  `miscelaneas/migrar_topologia.php` (nuevo):
  `migrar_topologia_admin` (crea `us_Administrador →
  usuarios`) y `migrar_topologia_soporte` (asegura `publico`
  en cada soporte y repunta el enlace `dueno → soporte` al
  `publico`). Cambio en `_asignar_duenos_a_soporte`.
- **v78g** (Tanda 1c.2): carga parcial activa. `index.php`
  decide entre `cargar`/`cargar_parcial`. Bootstrap
  (`aplicacion` + admin) solo en carga total.
  `_contextos_para_operacion` implementado.
  `GrafoCredenciales.php` captura y restaura contextos.
- **v78h** (Tanda 1c.2a): contexto en `ViajeMicros.php`.
  Enrutador: 4 acciones.
- **v78i** (Tanda 1c.2b): contexto en `ViajeOpciones.php`.
  Enrutador: 2 acciones.
- **v78j** (Tanda 1c.2c): contexto en `Viaje.php`.
  Enrutador: 2 acciones.
- **v78k** (Tanda 1c.2d): contexto en `Venta.php`.
  Enrutador: `cancelaciones/listar`.
- **v78l** (Tanda 1c.2e): contexto en `Pasajero.php`
  (9 funciones). Enrutador: 3 acciones.
- **v78m** (Tanda 1c.2f): contexto en `Impresion.php`.
  `obtener_venta_por_id`/`_buscar_venta_por_id` con
  dueño. `_contextos_para_operacion` con excepciones.
- **v78n** (reparación topológica): `miscelaneas/migrar_topologia.php`
  con `reparar_compartidos_dueno()` (agrega enlaces
  `dueño → compartido_con_X` faltantes) y
  `reparar_viajes_dueno()`. Bloque `?reparar_topologia=1`.
- **v78o**: revertir los chequeos defensivos de v78d.
  El admin carga parcial siempre (con `[us_Administrador]`)
  y la topología le trae todos los usuarios por el enlace
  `us_Administrador → usuarios`. Los chequeos bloqueaban
  al admin sin agregar seguridad.
- **v78p**: `listar_duenos` navega directo por
  `us_<soporte> → duenos` cuando el solicitante es soporte.
  Con carga parcial, `usuarios` no está en memoria y
  `listar_usuarios` devolvía vacío.
- **v78q**: `_contextos_para_operacion` carga total para
  las acciones de gestión de usuarios (`dueno/agregar_terminal`,
  `dueno/actualizar_terminal`, `dueno/eliminar_terminal`,
  `administrador/agregar_usuario`, `administrador/actualizar_usuario`,
  `administrador/eliminar_usuario`). Necesitan la raíz
  `usuarios` en memoria.
- **v78r**: (a) fix del framework: `_guardar_con_filtro`
  ahora borra solo aristas cuyos DOS extremos están en el
  filtro. Antes borraba aristas cuyo ORIGEN estaba en el
  filtro y no reinsertaba las que tenían un extremo fuera,
  perdiendo enlaces del dueño. (b) `reparar_empresas_dueno()`
  y `reparar_pasajeros_dueno()` agregados al bloque
  `?reparar_topologia=1`.
- **v78s**: fix del framework: en `_guardar_con_filtro`,
  con filtro != 0, unir las máscaras que el BFS calculó
  con las que el nodo ya tenía en memoria. Nunca se pierde
  un bit preexistente. **NO resuelve el bug §1.5.**

### 1.3 Decisiones de diseño en vigor

- **SQL es siempre el método principal.** JSON/XML son respaldo
  manual (no automático desde v74m).
- **Host y credenciales en `config_servidor.php`.** Es el único
  archivo que no se toca al desplegar.
- **Rol `soporte`.** Ve las mismas pestañas que el admin,
  solo sobre sus dueños asignados. Ve el `publico` del dueño
  (nombre, email), no su `privado`.
- **Los prompts viven en el proyecto.** Los tres clásicos
  (framework, piloto, sistema de scripts) + este `plan_actual.md`.
- **Comandos del framework vs de la app.** Framework → en
  `Controlador.php`. App → en `Aplicacion/`, registrados desde
  `index.php` después de los `require_once`.
- **El modelo topológico.** Seguridad por topología: un usuario
  solo alcanza lo que tiene camino desde su raíz. Admin y
  soporte siguen accediendo por código, no por topología.
- **SQL64 como método activo (v78e).** Persiste en las 3 tablas
  nuevas: `nodo_contexto`, `adyacente_contexto`, `contexto`.
  Las tablas viejas (`nodo`, `adyacente`) quedan intactas como
  respaldo de la última migración manual.
- **Carga parcial por contexto (v78g).** Toda operación POST
  con `nombre_solicitante` carga parcial con `[us_<sol>]`.
  Sin solicitante, carga total. Excepciones puntuales:
  `_contextos_para_operacion` devuelve `null` para acciones
  que necesitan la raíz `usuarios` (gestión de usuarios) y
  para informes que usan B3.3c.
- **`us_Administrador → usuarios` (v78f).** El admin carga
  parcial con `[us_Administrador]` pero la topología le trae
  todos los usuarios.
- **`dueno → soporte → publico` (v78f).** El enlace `soporte`
  del dueño apunta al `publico` del soporte, no al nodo real.
  Evita la fuga cruzada entre dueños que comparten un soporte.

### 1.4 Bugs conocidos

**BUG PERSISTENTE (ver §1.5).** El bug del viaje viejo que
desaparece desde el punto de vista del dueño tras una operación
del terminal. **No resuelto.** v78r y v78s fueron intentos de
fix que no alcanzaron.

Sin otros bugs de prioridad alta.

### 1.5 BUG PERSISTENTE — El viaje viejo desaparece [RESUELTO en v1.5i.8a]

**RESUELTO en framework 1.5i.8a.** Ver §1.5.6 abajo.


#### 1.5.1 Síntoma

Con las pruebas del plugin:
  1. Se corre la prueba "autocompletado por DNI" (logueado
     como terminal).
  2. Después la prueba "alta de viaje" (logueado como dueño).
  3. El viaje viejo (el principal, "Peregrinación a la Visita
     del Papa León XIV a Luján") **desaparece de la lista del
     dueño**.
  4. El terminal lo sigue viendo (navega por su compartido).
  5. Corriendo la prueba de nuevo, ya no falla: los viajes
     nuevos se agregan normalmente y el viejo sigue sin
     aparecer.

Patrón: **"primera vez falla, segunda no"**. Después de la
primera operación del terminal, el estado del grafo queda
"estable" (aunque roto).

#### 1.5.2 Diagnóstico

Cuando el terminal hace `guardar_parcial([us_<terminal>])`,
el framework en `_guardar_con_filtro`:

1. Carga parcialmente `[us_<terminal>]`. En memoria **no está
   `us_<dueno>`** (por diseño topológico: el terminal no
   alcanza al nodo del dueño real).
2. Ejecuta `_calcular_y_registrar_contextos`: BFS multi-fuente
   desde los IDs especiales que están en memoria. Como
   `us_<dueno>` no está, su bit **no se propaga**.
3. Guarda con la máscara recalculada. El nodo viaje viejo
   queda con máscara = solo bit del terminal (o sin bit del
   dueño).
4. En la próxima carga parcial del dueño (`[us_<dueno>]`), el
   filtro `(contexto_mascara & bit_dueno) != 0` excluye al
   viaje viejo. No aparece.

**Por qué "primera vez falla, segunda no":** la primera
operación del terminal es la que pisa el bit del dueño. A
partir de ahí el viaje ya no tiene el bit, y las siguientes
operaciones del terminal no lo tocan.

**Por qué el terminal sí lo ve:** el compartido tiene su
propio bit del terminal, y la venta referencia al viaje por
id. El nodo viaje está en el grafo del terminal, pero sin
bit del dueño.

#### 1.5.3 Fixes intentados (ninguno alcanzó)

- **v78r:** cambió la regla de borrado de aristas en
  `_guardar_con_filtro`: ahora borra solo aristas cuyos DOS
  extremos están en el filtro. Antes borraba las aristas
  cuyo ORIGEN estaba en el filtro sin reinsertar las que
  tenían un extremo fuera. **Aplicado, no resolvió.** El
  bug del viaje viejo es de máscaras, no de aristas.
- **v78s:** unir las máscaras que el BFS calculó con las que
  el nodo ya tenía en memoria. **Aplicado, no resolvió.**
  Hipótesis: la máscara que llega al guardado del terminal
  no tiene el bit del dueño ni siquiera antes de calcular el
  BFS (el nodo se carga sin ese bit). El `unir` sería con
  un valor ya degradado.

#### 1.5.4 Próximo paso sugerido

Instrumentar `_guardar_con_filtro` para emitir un log con:

- El `$mf` de cada llamada.
- Los IDs especiales en memoria (los que el BFS usa como
  fuentes).
- Antes del `unir` de v78s: para cada nodo con id especial
  del viaje viejo, su máscara actual y la que el BFS calculó.
- Después del `unir`: los mismos valores.

Comparar el estado entre la última carga del dueño (que sí
tenía el bit) y la primera operación del terminal (que lo
pierde). La diferencia mostrará en qué punto exacto se va
el bit.

Otra hipótesis a chequear: el `_cargar_nodos_con_mascara`
cuando carga **parcial** filtra por máscara. Si un nodo no
tiene el bit del terminal pero SÍ otros bits (por ejemplo,
solo el del dueño), **no se carga** y queda fuera del grafo
del terminal. Si ese nodo está en el camino del BFS hacia
el viaje viejo (por ejemplo, un contenedor intermedio), la
propagación se corta y el viaje queda sin bit. Habría que
verificar que el BFS no se interrumpa por nodos filtrados.

#### 1.5.6 Causa raíz y solución (v1.5i.8a)

**Causa raíz:** los IDs numéricos del framework son
**efímeros**. Cambian entre cada carga del grafo porque
`Objeto` los genera de forma perezosa y no los conserva
entre requests. Todos los fixes previos (v78r, v78s,
v78t, v78u) intentaban hacer un guardado parcial mapeando
"ID del framework" ↔ "fila de la DB", y esa
correspondencia se rompía en cada request. El DELETE no
encontraba los IDs viejos, no borraba nada, e insertaba
duplicados. El grafo creció a 99.290 nodos.

**Solución (diseño acordado con el usuario):** cambiar el
esquema para que la DB no dependa de los IDs del
framework.

- `nodo_contexto`: `id_fila` (BIGINT AUTO_INCREMENT, PK)
  es la identidad estable. `id_especial` guarda el ID
  especial del framework (NULL para numéricos).
- `adyacente_contexto`: `idnodo_fila` y `idadyacente_fila`
  referencian los extremos por `id_fila`, no por los IDs
  efímeros.
- `PerdurarSuperestructuraStringSQL64::$_mapa_id_a_fila`
  relaciona ambos mundos dentro de una request. Se llena
  al cargar y se consulta al guardar parcial.
- El DELETE parcial borra solo aristas cuyos DOS extremos
  están entre las filas cargadas. Las que cruzan el borde
  sobreviven. Ese es el fix del síntoma (el enlace
  `dueño → compartido` ya no se borra cuando el terminal
  guarda).

**Estado:** framework 1.5i.8a. Falta espejar en
`PerdurarSuperestructuraStringIndexedDB64.js`. Mientras
tanto, `_contextos_para_operacion` devuelve `null`
(carga total) para no arriesgar el grafo.

#### 1.5.5 Impacto actual

- El plugin de pruebas (autocompletado + alta de viaje)
  reproducen el bug de manera consistente.
- El dueño no ve el viaje viejo después de que un terminal
  hace cualquier operación.
- El terminal sí lo ve (navega por su compartido).
- La reparación `?reparar_topologia=1` recupera el viaje
  temporalmente, pero la próxima operación del terminal lo
  vuelve a romper.

### 1.6 Pendientes de la línea

- **BUG §1.5.** Bloqueante funcional. Requiere logs de
  diagnóstico.
- **Espejo JS del fix v78r.** `PerdurarSuperestructuraStringIndexedDB64.js`
  tiene el mismo bug de borrado de aristas. Pendiente.
- **Espejo JS del fix v78s.** Idem: preservar bits de máscara.
  Pendiente.
- **B3.3c** (7 helpers que iteran sobre todos los dueños).
  Postergado hasta que aparezca dolor real o implementemos
  iteradores con ID especial. Ver §1.10.
- **Módulos menores sin contexto:** `Liquidacion.php`,
  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Mismo
  patrón que los módulos ya adaptados. Sub-tandas cortas
  cuando aparezcan errores.
- **Chequeos defensivos revertidos.** Si aparecen otros
  casos donde el chequeo ayuda, se reintroduce selectivo.

### 1.7 Plan de la línea de tandas (fases)

**Fase A** — Usuarios como IDs especiales `us_<nombre>`.
**Completada** (v76k).

**Fase B1** — Contenedores `publico`/`privado` en cada
usuario. **Completada** (v76m).

**Fase B2.1** — Compartidos por terminal creados con
referencias filtradas. **Completada** (v76q).

**Fase B2.2** — Contexto opcional en Viaje, Venta,
ViajeAsientos, Empresa. **Completada** (v76r-v76u).

**Fase B2.3** — Árboles paralelos con nombres parametrizados
en `Arbol.php`. **Completada** (v76v-v77o).

**Fase B2.4** — Mantener los compartidos vivos. **Completada**
(Tanda A, v77q).

**Fase B3** — Eliminar los accesos viejos. **Cerrada salvo
B3.3c** (7 helpers).

**Carga parcial (Etapa 1, v78b-v78s)** — Carga y guardado
parciales por contexto. **Aplicada.**
  1a (framework): `guardar_parcial` real + espejo JS.
  1b (framework): `contextos_cargados` + getter.
  1c.1 (migraciones): SQL64 + topología admin/soporte.
  1c.2 (piloto): `_contextos_para_operacion` + adaptación
    del contexto en Viajes, Venta, Pasajeros, Impresión.
  Reparación topológica + fix de regla de borrado +
  preservación de bits.
  **Bug §1.5 sin resolver.**

**Fase C** (opcional) — Tipos como IDs especiales.
**Pendiente.**

**Fase D** — Carga parcial como optimización.
**Pendiente.**

### 1.8 Espejo JS del framework

El framework PHP y el espejo JS deben ir en sincronía. Esta
sesión tocó:

- `PerdurarSuperestructuraStringSQL64.php` (PHP): fix
  v78r (doble extremo en borrado de aristas) y v78s
  (preservar bits de máscara).
- **Pendiente:** los mismos fixes en
  `PerdurarSuperestructuraStringIndexedDB64.js`.

Los fixes del espejo se hacen en la próxima tanda larga del
plugin, junto con las pruebas correspondientes.

### 1.9 Lecciones de la sesión v78b-v78s

**Sobre el framework y los IDs:**

1. **En carga parcial, no todos los IDs especiales están en
   memoria.** El BFS de máscaras parte solo de los que hay.
   Esto es un problema cuando el guardado pisa el estado de
   nodos que ya tenían bits de contextos que no están en
   memoria. Ver §1.5.
2. **Las reparaciones topológicas son necesarias pero no
   suficientes.** Recuperan enlaces faltantes, pero no
   previenen que se vuelvan a perder.

**Sobre la persistencia:**

3. **La regla de borrado de aristas importa.** Borrar aristas
   cuyo extremo cruza el filtro pierde información que no se
   puede reconstruir solo desde el subgrafo parcial.
   Preservar aristas que cruzan el borde.
4. **La preservación de bits de máscara es la segunda mitad
   del problema.** No alcanza con preservar aristas; hay que
   preservar también los bits del nodo.

**Sobre las migraciones:**

5. **Recargar la base y volver a migrar es un test valioso.**
   Detectar bugs que solo aparecen en la secuencia de
   migración completa. Casos: v78n (compartidos faltantes),
   v78p (listar_duenos para soporte).
6. **Migraciones de datos que reestructuran el grafo deben
   ser idempotentes y re-ejecutables.** El `?reparar_topologia=1`
   cumple.

**Sobre las pruebas del plugin:**

7. **El orden de las pruebas importa.** Una prueba puede
   dejar el grafo en un estado que rompe a la siguiente.
   El bug §1.5 apareció justamente por el orden
   autocompletado → alta de viaje.
8. **"Primera vez falla, segunda no"** casi siempre indica
   que un estado se pisó una vez y quedó consistente
   (aunque roto). El diagnóstico tiene que apuntar al
   primer guardado que modifica ese estado.

### 1.10 Diseño futuro: iteradores con ID especial

Anotado en v78a. No implementado. Objetivo: cuando el
framework tenga iteradores con ID especial persistente y un
usuario que los "ocupe", los B3.3c se resuelven naturalmente.

Idea:

- Iteradores con ID especial (`it_<nombre>`) como raíces de
  subgrafo.
- Un iterador cargado trae su subgrafo alcanzable.
- Un iterador tiene un usuario que lo "ocupa".
- Especialización por tipo: `it_empresas_<dueno>`,
  `it_viajes_<dueno>`, etc.
- El backend decide qué iteradores cargar por operación.

Detalle completo en `prompt_framework_iteradores.md` §11.5.

---

## 2. PLAN DE LA LÍNEA DE TANDAS

### 2.1 Contexto

La línea en curso es la **carga parcial por contexto**.
Objetivo: que cada operación cargue solo el subgrafo que
necesita, para bajar el costo de O(grafo completo) a O(subgrafo).
Se apoya en el modelo topológico (Fases A, B1-B3) y en SQL64
del framework.

### 2.2 Estado por fase

1. **Modelo topológico (Fases A, B1-B3).** Aplicado.
   Pendiente: B3.3c.
2. **Framework — SQL64.** Aplicado. `guardar_parcial` real,
   `cargar_parcial`, `listar_contextos`, `contextos_cargados`.
   Bug §1.5 sin resolver.
3. **Migraciones del piloto.** Aplicadas. SQL64 activo,
   topología admin/soporte, carga parcial en módulos
   principales.
4. **Espejo JS.** Pendiente de los fixes v78r/v78s.
5. **BUG §1.5.** Bloqueante.

### 2.3 Próximos pasos sugeridos

1. **Retomar el bug §1.5 con logs de diagnóstico** en
   `_guardar_con_filtro`. Ver §1.5.4.
2. **Espejar v78r/v78s en el framework JS.**
3. **Adaptar los 4 módulos menores** (Liquidacion, Rendicion,
   Empresa, Vehiculo) con el mismo patrón de contexto.
4. **B3.3c** cuando aparezca dolor real o cuando lleguen
   los iteradores con ID especial.

---

**FIN DEL PLAN ACTUAL**