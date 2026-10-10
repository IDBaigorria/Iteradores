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

**Última actualización de este archivo:** v1.5piloto.77d
(fix del modal de pasajero tras cambiar de asiento).
Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,
ViajeAsientos, Empresa). Después vinieron B2.3.1, B2.3.2 y
B2.3.3 (árboles paralelos parametrizados, aplicados también
en producción). Hubo un paréntesis de bugs (v76z a v77d) que
incluyó el fix de atadura comprador-pasajero, el modal
post-venta, y la funcionalidad completa de "cambiar de
asiento". Próximo paso: **Fase B2.3.4** (ajustar las
escrituras de venta para que inserten y borren en los dos
árboles paralelos cuando el contexto sea un compartido).
Detalle en §2.3.)

### 1.2 Estado de la conversación

**Cadena de cierres de la Fase B (modelo topológico):**

- **v76i**: solo documentación. §8.7 en el prompt del piloto con
  el plan inicial de contextos. Sin cambios de código.
- **v76j**: cierre de la Fase 2 del framework (SQL64 en PHP,
  IndexedDB64 en JS, interfaz `PerdurarSuperestructuraConContexto`,
  4 métodos en el Controlador y flag `$grafo_parcial`). Fix del
  bug de tipos de ID en el `Map` del espejo JS. Deuda anotada:
  dejar el Nodo limpio antes de la Fase 5.
- **v76k**: Fase A del piloto. Usuarios como IDs especiales
  `us_<nombre>`. Comando `grafo:reemplazar_referencias`. Script
  `miscelaneas/migrar_usuarios_especiales.php` y bloque
  `?migrar_usuarios_especiales=1`.
- **v76l**: solo diseño. Modelo topológico por niveles de
  exposición (publico, privado, compartido_con_X).
- **v76m**: Fase B1. Contenedores `publico` y `privado` como
  alias de los nodos existentes. Comando
  `grafo:crear_niveles_usuario`. Script
  `miscelaneas/migrar_niveles_usuario.php` y bloque
  `?migrar_niveles_usuario=1`.
- **v76n**: pestaña Grafo ampliada ("Nodos raíz", "Migraciones").
  Nodo `aplicacion` con contenedor `migraciones`. Módulo
  `Aplicacion/Migraciones/`. Comandos `grafo:raices` (framework)
  y `app:migracion_*` (app, registrados directo).
- **v76o**: solo documentación. Tres pendientes del framework
  (espejar `grafo:reemplazar_referencias`, mover
  `grafo:crear_niveles_usuario` al app, verificar
  `grafo:raices` desde el SW del plugin).
- **v76p**: solo documentación. Sistema de comandos (§4.4 del
  framework, §5.16 del piloto). Lecciones 24-27.
- **v76q**: Fase B2.1. Compartidos por terminal creados como
  contenedores filtrados. Comando
  `app:crear_compartidos_terminal`. Bloque
  `?migrar_compartidos_terminal=1`. No repunta nada.
- **v76r**: Fase B2.2.1. Contexto opcional en
  `obtener_contenedor_viajes_dueno`. Helper `_contexto_terminal`.
  `listar_viajes_de_terminal` lo usa.
- **v76s**: Fase B2.2.2. Venta.php: contexto opcional en
  `obtener_contenedor_ventas_dueno`, filtro opcional por
  terminal en las búsquedas por id, contexto en
  `listar_ventas_por_terminal` y `confirmar_venta_actual`.
- **v76t**: Fase B2.2.3. ViajeAsientos.php: contexto opcional
  en las funciones que navegan por el contenedor de viajes.
- **v76u**: Fase B2.2.4. Empresa.php: contexto opcional en
  `listar_empresas_de_dueno`. Con esto queda cerrada la
  Fase B2.2.
- **v76v**: reorganización de prompts (nuevo `plan_actual.md`,
  §12 del piloto congelada, regla de actualización) +
  parametrización de `miscelaneas/Arbol.php` (las 7 funciones
  aceptan `?array $nombres = null`). Fase B2.3.1.
- **v76w**: helper `_nombres_arbol_para_contexto` y ajuste
  de las funciones del piloto para pasarle los nombres
  parametrizados a `hmi`/`hd`. Sin cambio de comportamiento.
  Fase B2.3.2.
- **v76x**: migración `app:construir_arboles_compartidos`.
  Marca cada compartido con `_es_compartido`, limpia los
  enlaces planos que dejó B2.1 y reconstruye los árboles
  de `ventas` y `cancelaciones` con nombres parametrizados.
  Idempotente. Fase B2.3.3.
- **v76y**: fix de auto-detección de migraciones. Las
  funciones `detectar_*` ya no devuelven `true` por vacío.
  Nuevo comando `app:migracion_limpiar_marcadores` y botón
  "Re-detectar" en la pestaña Grafo.
- **v76z**: fix del modal post-venta. `confirmar_venta_modal`
  muestra el modal ANTES de los refrescos y envuelve los
  refrescos en try/catch.
- **v77 / v77a**: fix del Bug 1 (atadura). v77 no limpia
  campos si hay atadura activa. v77a habilita los campos
  no-DNI del pasajero al activar la atadura.
- **v77b**: backend de "cambiar de asiento".
  `formatear_venta_resumida` agrega `micro_enlace`. Nueva
  función `cambiar_asiento_pasaje` en `ViajeAsientos.php`.
  Subacción `viajes/cambiar_asiento` en el enrutador.
- **v77c**: frontend de "cambiar de asiento". Botón en los
  dos modales de detalle. Función compartida
  `abrir_modal_cambiar_asiento`.
- **v77d**: refresco del modal de pasajero tras cambiar de
  asiento. `abrir_modal_cambiar_asiento` acepta un callback
  `on_exito`.

**Tanda actual:** v77d-05 (documentación: refresh del plan
actual + preparación de B2.3.4).

**v77d — refresco del modal de pasajero.** Al cambiar el
asiento desde el detalle de un pasaje (pestaña Clientes), el
modal genérico quedaba con el asiento viejo. `abrir_modal_cambiar_asiento`
ahora acepta un 6to parámetro opcional `on_exito`, que se
ejecuta tras confirmar el cambio. Desde `pasajeros.js` se
pasa un callback que re-abre `ver_detalle_pasaje_individual`
con el nuevo número de asiento. Desde el croquis no se pasa
nada: sigue funcionando igual.

**Nueva funcionalidad "Cambiar de asiento".** Desde el
detalle de un pasaje (croquis o pestaña Clientes), botón
para mover el pasaje a otro asiento del mismo micro.
Reglas:

- Terminal: solo puede mover asientos que él mismo vendió,
  y solo a un asiento libre. No toca reservados.
- Dueño/admin/soporte: puede mover vendidos y reservados,
  a un asiento libre o reservado sin pasajero.
- El asiento viejo vendido queda libre.
- El asiento viejo reservado puede quedar libre o
  reservado sin pasajero (checkbox "Dejar el asiento
  viejo reservado", por defecto tildado).
- El asiento nuevo reservado solo se acepta si NO tiene
  pasajero asignado (regla confirmada por el usuario).
- El id_venta no cambia. El asiento-en-venta persistente
  solo cambia su enlace `asiento`.
- El `punto_subida_bajada` del asiento-en-venta no cambia.
- Solo se puede cambiar dentro del mismo micro.

**Backend v77b:** `Venta.php` agrega `micro_enlace` a
`formatear_venta_resumida` (el dato del micro es vacío, el
nombre del enlace es la única referencia). Nueva función
`cambiar_asiento_pasaje` en `ViajeAsientos.php` + helper
`_buscar_asiento_en_venta_persistente`. Subacción
`viajes/cambiar_asiento` en el enrutador.

**Frontend v77c:** botón "Cambiar de asiento" en los dos
modales de detalle del pasaje (`ver_pasaje_asiento` en
`viajes-asientos.js`, `ver_detalle_pasaje_individual` en
`pasajeros.js`). Función compartida
`abrir_modal_cambiar_asiento` en `viajes-asientos.js`. El
modal hace 2 fetches (estado de asientos + configuración del
micro), arma el croquis con asientos elegibles marcados,
checkbox "Dejar el asiento viejo reservado" (solo si el viejo
es reservado, tildado por defecto) y confirmación por POST.
Después del éxito: refresca el croquis si estamos ahí y
ofrece reimprimir el pasaje (modal chico de pasajero para
vendido, modal chico de reserva para reservado).

**Con esto queda cerrada la funcionalidad "cambiar de asiento".**
Para activarla: correr el backend (v77b) y el frontend (v77c)
y probar desde el croquis y desde la pestaña Clientes.

**Bug 1 (atadura) — diagnóstico completo (v77 + v77a):**
El usuario carga el comprador con un DNI nuevo (no existe
en el grafo), llena todos los datos. Después, en el form
del pasajero, ingresa el mismo DNI. La atadura se activa
y copia los datos del comprador al pasajero. Pero:
  a) (fix v77) al volver el fetch, el backend responde
     "no existe" y `_buscar_pasajero_por_dni` limpiaba los
     campos. Ahora no limpia si hay atadura activa.
  b) (fix v77a) los campos no-DNI del pasajero quedaban
     `disabled` hasta que volviera el fetch. Ahora
     `_activar_atadura` los habilita al activar. Los
     campos comunes reciben el dato del comprador; los
     no comunes (celular_emergencia, fecha_nacimiento,
     direccion, localidad) quedan libres para completar.

**Bug 2 (modal post venta) — diagnóstico pendiente:**
Vuelve a no aparecer. Se está diagnosticando con logs de
consola. Fix anterior (v76z): mostrar el modal antes de
los refrescos, envolver refrescos en try/catch.

**Bug 1 (atadura) — diagnóstico completo:**
El usuario carga el comprador con un DNI nuevo (no existe
en el grafo), llena todos los datos. Después, en el form
del pasajero, ingresa el mismo DNI. La atadura se activa
(por el fix v76z, corre antes del fetch) y copia los
datos del comprador al pasajero. Se ve "Vinculado". Pero
al volver el fetch, el backend responde "no existe" y
`_buscar_pasajero_por_dni` limpiaba los campos del pasajero,
borrando lo que la atadura acababa de copiar.

**Fix v77:** si hay atadura activa apuntando a este
formulario, no limpiar los campos. Los datos vinieron del
otro lado y siguen siendo válidos aunque el DNI no esté
registrado. Solo se limpia cuando no hay atadura (caso
"corregí el DNI por uno nuevo y quiero empezar de cero").
Mismo criterio aplicado a `_buscar_comprador_por_dni`.

**Bug 2 (modal post venta) — resuelto en v76z:**
`confirmar_venta_modal` ahora muestra el modal ANTES de
los refrescos, y envuelve los refrescos en try/catch
individuales. Un fallo en el refresco ya no bloquea el
modal.

### 1.3 Decisiones de diseño en vigor

- **SQL es siempre el método principal.** JSON/XML son respaldo
  manual (no automático desde v74m).
- **Host y credenciales en `config_servidor.php`.** Es el único
  archivo que no se toca al desplegar.
- **Rol `soporte`.** Ve las mismas pestañas que el admin,
  solo sobre sus dueños asignados.
- **Los prompts viven en el proyecto.** Los tres clásicos
  (framework, piloto, sistema de scripts) + este `plan_actual.md`.
- **Comandos del framework vs de la app.** Framework → en
  `Controlador.php`. App → en `Aplicacion/`, registrados desde
  `index.php` después de los `require_once`.
- **El modelo topológico.** Seguridad por topología: un usuario
  solo alcanza lo que tiene camino desde su raíz. Admin y
  soporte siguen accediendo por código, no por topología.

### 1.4 Bugs conocidos

Ninguno de prioridad alta. Los históricos (Bug 1 opciones de
cobro, Bug 2 ligadura comprador-pasajero) están resueltos.

### 1.5 Aprendizajes a la fuerza

1. **Los datos del framework viajan como strings.** Castear al
   comparar. Ejemplo: `"2" !== 2` en JS.
2. **PHP convierte claves de array string numéricas a int.**
   Forzar `(string)$clave` en los foreach que iteran sobre DNI
   o patentes.
3. **Los IDs sin ID especial cambian entre cargas.** Si un
   nodo debe sobrevivir a guardar/cargar y ser referenciado,
   usar ID especial.
4. **Nunca confiar en datos que el cliente manda** cuando hay
   fuente de verdad del lado del servidor.
5. **El guardado SQL debe ser transaccional y por chunks.**
6. **El guardado nunca debe pisar el grafo con vacío.**
7. **Los includes importan.** `guardar_ambos` disponible antes
   de usarse.
8. **`if ($elemento)` descarta falsy.** Usar `!== null` cuando
   el valor puede ser falsy legítimamente.
9. **PHP y JS van espejados.** Framework PHP ↔ espejo JS en
   la misma tanda, dos scripts, dos commits.
10. **En MV3, `import()` dinámico está prohibido en el
    `ServiceWorkerGlobalScope`.** Para debug desde la consola
    del SW, exponer `globalThis.Controlador = Controlador` en
    el bootstrap.

### 1.6 Preguntas abiertas

Ninguna. La conversación quedó en un punto de pausa limpio.

### 1.7 Deuda técnica

**Uso de `Nodo::nodo_por_id('usuarios')`.** Es la raíz global
del grafo y su uso impide la carga parcial por contextos: para
resolverla hay que tener todo el grafo cargado. El objetivo de
la Fase B es dejar de usarla, resolviendo los nodos por IDs
especiales (`Nodo::nodo_por_id('us_<nombre>')`) o por
navegación de contexto.

**Inventario actual (22 usos):**

- **`Venta.php` (8):**
  - `obtener_contenedor_ventas_dueno` — rama sin contexto.
  - `confirmar_venta_actual` — resuelve el terminal.
  - `_buscar_venta_por_id` — rama sin contexto (itera dueños).
  - `cancelar_venta` — resuelve el dueño.
  - `_calcular_cobertura_dueno` — resuelve el dueño.
  - `_crear_nodo_cancelacion` — resuelve el dueño.
  - `obtener_cancelacion_por_id` — resuelve el dueño (itera).
  - `listar_cancelaciones_de_dueno` — resuelve el dueño.
- **`Enrutador.php` (6):**
  - `autenticar/verificar` — dueño de un terminal.
  - Módulo `administrador` — nivel del solicitante.
  - `dueno/listar_sesiones_terminales` — nivel del solicitante.
  - `viajes/limpiar_prueba` — nivel del solicitante.
  - `pasajeros/limpiar_prueba` — nivel del solicitante.
  - Módulo `grafo` — nivel del solicitante.
- **`Viaje.php` (6):**
  - `_contexto_terminal` — terminal y su dueño.
  - `obtener_contenedor_viajes_dueno` — rama sin contexto.
  - `listar_viajes_de_terminal` — terminal y dueño.
  - `viaje_tiene_ventas` — resuelve dueño.
  - `agregar_terminal_autorizada` — resuelve terminal.
  - `formatear_viaje` — resuelve dueño para `empresas`.
- **`Funciones.php` (2):**
  - `detectar_usuarios_especiales`.
  - `detectar_niveles_usuario`.

**Plan de limpieza:**

- **B2.3.4 (en curso):** reemplazar los usos que están dentro
  del flujo de ventas y de contexto del terminal
  (`confirmar_venta_actual`, `cancelar_venta`,
  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion` y
  `_contexto_terminal`). Se reemplazan por
  `Nodo::nodo_por_id('us_' . $nombre)`. Sin cambio visible
  (funciona igual hoy con el grafo completo).
- **B3 (limpieza final):** reemplazar el resto. Los 6 del
  enrutador son los más delicados: requieren que el enrutador
  empiece a pasar contexto (repuntado de B2.3.5). Los fallbacks
  "sin contexto" de las funciones contenedoras se eliminan en
  B3.

**Contenedores raíz de árboles paralelos.** Cada árbol tiene su
propio contenedor raíz. `privado/ventas` es un nodo, y
`compartido_con_us_termX/ventas` es otro nodo (distinto). Los
nodos venta son compartidos entre árboles (mismo nodo físico)
y cada uno lleva dos juegos de enlaces (`hmi`/`hd`/`p` para el
árbol del dueño, `hmi_<term>`/`hd_<term>`/`p_<term>` para el
del terminal). Es la condición para que la topología aísle
correctamente: si el contenedor fuera el mismo, el BFS desde
el terminal alcanzaría todas las ventas del dueño.

**Dato del compartido (opción A).** El nodo
`compartido_con_us_termX` tiene `dato = nombre_del_dueño` para
que el código que hace `$nodo_dueno->dato()` siga funcionando
después del repuntado (B2.3.5). **NO** se agrega un enlace
`dueno` en el compartido apuntando al nodo del dueño real:
eso rompería la privacidad topológica (el terminal podría
llegar al dueño por el enlace y desde ahí a todo el grafo).

---

## 2. PLAN DE LA LÍNEA DE TANDAS

### 2.1 Contexto

La línea en curso es la **Fase B del modelo topológico**.
Objetivo: aislar por topología, para que cada usuario solo
alcance lo que le corresponde. La seguridad emerge de la
estructura del grafo, no de chequeos de código.

### 2.2 Fases

1. **Fase A** — Usuarios como IDs especiales `us_<nombre>`.
   **Completada** (v76k).
2. **Fase B1** — Contenedores `publico` y `privado` en cada
   usuario, como alias de los nodos existentes. **Completada**
   (v76m).
3. **Fase B2.1** — Compartidos por terminal (`compartido_con_us_termX`)
   creados con referencias filtradas. **Completada** (v76q).
4. **Fase B2.2.1** — Contexto opcional en `obtener_contenedor_viajes_dueno`.
   **Completada** (v76r).
5. **Fase B2.2.2** — Contexto y filtro en Venta.php.
   **Completada** (v76s).
6. **Fase B2.2.3** — Contexto en ViajeAsientos.php.
   **Completada** (v76t).
7. **Fase B2.2.4** — Contexto en Empresa.php.
   **Completada** (v76u).
8. **Fase B2.3** — Árboles paralelos con nombres parametrizados
   en `Arbol.php`. **En curso.** Detalle en §2.3.
9. **Fase B2.4** — Mantener los compartidos vivos (cada operación
   que modifique el contenido del compartido lo actualiza).
   **Pendiente.**
10. **Fase B3** — Eliminar los accesos viejos. **Pendiente.**
11. **Fase C** (opcional) — Tipos como IDs especiales, ortogonal.
12. **Fase D** — Aprovechar la carga parcial como optimización.
    **Pendiente.**

### 2.3 Detalle de la Fase B2.3 (en curso)

**Cambio de enfoque respecto del plan original.** El plan
original proponía un "compartido plano" (opción A): el
`compartido_con_us_termX` guarda enlaces planos por id, y las
funciones del terminal iteran con `adyacentes()`. Se descartó
en favor de la **opción D**: árboles paralelos con nombres de
enlace parametrizados.

**Por qué.** El compartido puede usar el mismo mecanismo de
árbol binario (`hmi`/`hd`/`p`) que el privado, solo que con
nombres alternativos. Cada venta pertenece a UN solo terminal,
así que solo participa en 2 árboles: el del dueño (con
`hmi`/`hd`/`p`) y el del terminal que la vendió (con
`hmi_<terminal>`/`hd_<terminal>`/`p_<terminal>`). No hay
N+1 enlaces por nodo.

**La raíz del árbol SÍ se duplica (una por árbol).** Cada
terminal tiene su propio contenedor `compartido_con_us_termX/ventas`,
distinto del contenedor del dueño (`privado/ventas`). Eso
es fundamental para que la topología no filtre todas las
ventas al terminal: el BFS desde el terminal llega a su
contenedor y recorre solo SU árbol. Si compartieran el
contenedor, el BFS alcanzaría todas las ventas del dueño
por el `hmi`/`hd` default del árbol principal.

**Los nodos hijos SÍ se comparten (mismo nodo físico).** Cada
venta pertenece a dos contextos: el del dueño y el del
terminal que la vendió. Vive una sola vez, pero participa en
dos árboles paralelos: uno con `hmi`/`hd`/`p` (el del dueño),
otro con `hmi_<terminal>`/`hd_<terminal>`/`p_<terminal>` (el
del terminal). Cero enlaces `hd_<otro_terminal>` porque la
venta solo la vendió un terminal.

**Sub-tandas de B2.3:**

- **B2.3.1** — Parametrizar `miscelaneas/Arbol.php`.
  **Completada** (v76v).
- **B2.3.2** — Ajustar las funciones del piloto para que el
  terminal use los nombres parametrizados. **Completada**
  (v76w): helper `_nombres_arbol_para_contexto`, aplicado en
  `listar_ventas_por_terminal`, `_buscar_venta_por_id` (rama
  con contexto), `_recorrer_ventas_del_viaje` y
  `_recorrer_ventas_de_terminal` (Venta.php) y
  `_construir_indice_ventas_por_viaje` (Viaje.php). Como
  todavía no hay compartidos marcados, el helper devuelve
  null y los nombres son default. Sin cambio de comportamiento.
- **B2.3.3** — Migración que convierte los compartidos
  existentes (B2.1) de enlaces planos a árboles paralelos
  con los nombres parametrizados. **Completada** (v76x):
  comando `app:construir_arboles_compartidos`. Marca cada
  compartido con `_es_compartido`. Limpia el contenedor del
  compartido (que tenía enlaces planos de B2.1) y lo
  reconstruye como árbol con `_hmi` y nombres parametrizados.
  Idem para `cancelaciones`. Idempotente. Bloque
  `?construir_arboles_compartidos=1` en `index.php`.
- **B2.3.4** — Ajustar las escrituras de venta para que
  inserten y borren en los dos árboles paralelos. **En curso.**
  Alcance:
  - Reemplazar `Nodo::nodo_por_id('usuarios')->adyacente(...)`
    por `Nodo::nodo_por_id('us_' . $nombre)` en
    `confirmar_venta_actual`, `cancelar_venta`,
    `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion`
    (Venta.php) y `_contexto_terminal` (Viaje.php).
  - En `confirmar_venta_actual`: si el contexto tiene
    `_es_compartido`, insertar la venta también en el árbol
    parametrizado del compartido, además del árbol del dueño
    real. Orden de inserción: dueño primero (default),
    compartido después (parametrizado).
  - En `cancelar_venta`: desenlazar del compartido primero,
    después del árbol del dueño (orden inverso al de la
    inserción).
  - **NO toca el enrutador.** Los 6 chequeos de nivel siguen
    usando `nodo_por_id('usuarios')`. Eso es B3.
  - Dato del compartido: `dato = nombre_dueno` (opción A). No
    se agrega enlace `dueno` en el compartido: apuntar al
    nodo del dueño real rompería la privacidad topológica.
  - Sin pruebas del plugin todavía: el aislamiento no se
    activa hasta B2.3.5.
- **B2.3.5** — Repuntar `us_termX → dueno` al compartido y
  pasar el contexto desde el enrutador. Acá se activa el
  aislamiento. Con pruebas del plugin.

### 2.4 Próximas fases

- **B2.4** — Mantener vivos los compartidos.
- **B3** — Eliminar accesos viejos.
- **C** (opcional) — Tipos como IDs especiales.
- **D** — Carga parcial como optimización.

---

**FIN DEL PLAN ACTUAL**