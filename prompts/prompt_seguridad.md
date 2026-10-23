# Prompt de trabajo — Seguridad por topología en Iteradores

Este documento es el **manual de buenas prácticas de seguridad**
del framework Iteradores, aplicado al piloto. Vive en
`prompts/prompt_seguridad.md` y se pega junto a los otros prompts
cuando se trabaja en temas de aislamiento, permisos o contextos.

Se actualiza cuando: se agrega un patrón nuevo de aislamiento, se
descubre una fuga, se modifica el modelo de contextos, o se
documenta un bug relevante.

**Estado al cierre de v1.5piloto.81m:**

- La topología es la única fuente de verdad de la seguridad. Los
  chequeos por código (nivel, dueño asignado) siguen existiendo
  como respaldo, pero la garantía fuerte viene del grafo.
- Los bugs estructurales del framework (§1.5, §1.5.7, §1.5.8,
  §1.5.10, §1.5.15, §1.5.16) están **resueltos**. Ver §8.
- El soft delete coordinado está aplicado en **ventas** y
  **cancelaciones**. Rendiciones y liquidaciones solo las crea
  el dueño, no aplica.
- La **carga parcial está activa** desde v78g. Toda operación
  POST con `nombre_solicitante` carga parcial con `[us_<sol>]`.
  Excepciones puntuales documentadas en
  `_contextos_para_operacion`. Ver §8.9.
- **B3.3c reducido a 4 helpers** (antes 7). Liquidación y
  rendición se adaptaron en v81h. Ver §8.6.
- El espejo JS del framework está alineado a **V1.5i.8c**
  (V1.5plugin.7b).
- Los **módulos menores** ya están todos adaptados. Ver §8.7.

---

## 1. PRINCIPIO FUNDAMENTAL

**La seguridad emerge de la topología del grafo.**

Un usuario solo puede alcanzar lo que tiene camino desde su raíz
(`us_<nombre>`) hasta ese nodo. Si no hay camino, no lo ve,
aunque el grafo se cargue completo.

Esto reemplaza el enfoque tradicional de "chequear el rol del
usuario en cada función". Los chequeos por código siguen
existiendo (y hay que mantenerlos), pero la defensa de fondo es
estructural: aunque un chequeo falle o se saltee, la topología
sigue aislada.

**Corolario 1.** Un usuario con nivel "terminal" no puede acceder
al subgrafo privado de su dueño, porque no hay enlace. Punto.

**Corolario 2.** Un usuario no puede "adivinar" un ID numérico
para acceder a un nodo que no está en su subgrafo alcanzable.
El framework no expone IDs numéricos entre cargas: los IDs
numéricos cambian en cada `cargar`/`cargar_parcial`.

**Corolario 3.** El admin y el soporte son la excepción
explícita: acceden por código, no por topología. El admin tiene
un enlace `us_Administrador → usuarios` que le da acceso a todo
por topología, pero además puede hacer chequeos por nivel en el
enrutador.

---

## 2. MECÁNICA DEL AISLAMIENTO

### 2.1 Raíces y BFS multi-fuente

Cada nodo con **ID especial** (string no numérico) es una raíz
de contexto. Al guardar con SQL64, el framework:

1. Recorre todos los nodos con ID especial del grafo cargado.
2. Asigna a cada uno un bit en la tabla `contexto`.
3. Hace BFS multi-fuente desde todas las raíces.
4. Cada nodo acumula la unión de los bits de todas las raíces
   que lo alcanzan.
5. Persiste el bitmask en `nodo_contexto.contexto_mascara`.

Al cargar parcialmente, el framework filtra:

```sql
SELECT * FROM nodo_contexto
WHERE idsuperestructura = ?
  AND (contexto_mascara & $mask_pedida) != 0
```

Solo cargan los nodos alcanzables desde alguno de los contextos
pedidos.

### 2.2 Qué significa "alcanzable"

Un nodo `B` es alcanzable desde `A` si hay un camino dirigido
`A → ... → B` en el grafo, siguiendo los enlaces en la dirección
en que están definidos.

**Importante:** el BFS es **dirigido**. `A → B` no implica
`B → A`. Los enlaces de permiso son unidireccionales a propósito.

### 2.3 Identidad de fila (v1.5i.8a)

A partir del framework 1.5i.8a, la DB usa `id_fila` (BIGINT
AUTO_INCREMENT) como identidad estable de cada nodo.

- `nodo_contexto.id_fila` es la PK. `id_especial` guarda el ID
  especial del framework (NULL para los numéricos, que son
  efímeros).
- `adyacente_contexto` referencia sus dos extremos por
  `idnodo_fila` / `idadyacente_fila`, no por los IDs del
  framework.
- `PerdurarSuperestructuraStringSQL64::$_mapa_id_a_fila`
  relaciona `id_nodo_framework ↔ id_fila` dentro de una request.

**Por qué importa:** los IDs numéricos del framework son
efímeros (cambian entre cargas). La seguridad topológica no
depende de ellos, pero el guardado parcial sí: sin `id_fila`, el
guardado parcial borraba/insertaba filas que no correspondían,
rompiendo la topología persistida.

### 2.4 Contextos típicos en el piloto

- `usuarios`: raíz global de usuarios. Solo alcanzable desde
  `us_Administrador`.
- `us_<nombre>`: cada usuario. Su raíz.
- `sesiones`: sesiones activas (grafo de credenciales).
- `aplicacion`: metadata (migraciones, etc.).
- `compartido_con_<terminal>`: NO tiene ID especial (es un nodo
  común colgando del dueño). No es raíz de contexto, es
  simplemente alcanzable desde el dueño y desde el terminal.

---

## 3. CONTENEDORES POR NIVEL DE EXPOSICIÓN

Cada usuario tiene tres tipos de contenedores colgando de su
raíz `us_<nombre>`:

### 3.1 `publico`

Dato = nombre del usuario. Contiene:

- `nivel`
- `nombre_real`
- `email`

**Uso:** cuando otro usuario necesita ver "quién es" este
usuario sin acceder a sus datos privados.

**Ejemplo:** el enlace `dueno → soporte` apunta al `publico`
del soporte, no al nodo del soporte. Si apuntara al nodo real,
el BFS desde el dueño alcanzaría el nodo del soporte y desde
ahí a los otros dueños del soporte (fuga cruzada).

### 3.2 `privado`

Dato vacío. Contiene los datos internos del rol:

- Dueño: `efectivo`, `banco`, `terminales`, `empresas`,
  `viajes`, `pasajeros`, `ventas`, `rendiciones`,
  `liquidaciones`, `cancelaciones`.
- Terminal: `efectivo`, `banco`, `ventas` (propias),
  `venta_actual`.

**Uso:** solo el propio usuario (y admin/soporte por código)
accede a este contenedor.

### 3.3 `compartido_con_<otro>`

Dato = nombre del dueño del contenedor (opción A). Tiene el
marcador `_es_compartido` para que las funciones sepan que es
un contexto compartido (y no el nodo real del dueño).

Contiene referencias **filtradas**:

- `compartido_con_us_termX` (en el dueño):
  - `viajes` (solo los autorizados a termX)
  - `empresas` (solo las usadas por esos viajes)
  - `terminales` (solo termX)
  - `ventas` (solo las ventas hechas por termX)
  - `cancelaciones` (idem)
  - `pasajeros` (alias al contenedor del dueño — mismo nodo
    físico, porque los pasajeros son compartidos)
- `compartido_con_dueno` (en el terminal): no existe como tal
  en el piloto actual. El terminal usa directamente el
  `compartido_con_us_termX` del dueño a través de su enlace
  `dueno`.

**Importante:** el compartido **no tiene enlace al nodo real
del dueño**. Si lo tuviera, el BFS del terminal llegaría al
dueño y desde ahí a todo su subgrafo privado.

### 3.4 Enlaces de permiso

Van directo en la raíz del usuario, no en un contenedor:

- `dueno` en el terminal → apunta al `compartido_con_us_termX`
  del dueño.
- `soportes` en el dueño → contenedor (dato vacío) con los
  `publico` de cada soporte asignado
  (`soportes → publico_<soporte1>, publico_<soporte2>, ...`).
  **Permite varios soportes por dueño.** Reemplaza al enlace
  singular `soporte` desde v1.5piloto.81l. El nombre plural
  evita la colisión con el nodo real del soporte.
- `duenos` en el soporte → apunta al nodo real de cada dueño
  asignado. **Este es el enlace que abre la puerta del soporte
  hacia sus dueños.**

**Nota asimétrica:** `soporte → duenos → us_duenoY` apunta al
nodo real (el soporte ve todo del dueño);
`dueno → soportes → publico_soporteX` apunta al `publico` (el
dueño solo ve datos públicos del soporte). La asimetría es
intencional.

---

## 4. REGLAS DE DISEÑO PARA NUEVOS ENLACES

### 4.1 Regla de dirección

Antes de agregar un enlace `A → B`, preguntarse:

1. ¿Qué usuario o rol "es" A?
2. ¿Qué usuario o rol "es" B?
3. ¿A debe poder ver lo que hay del otro lado de B?

Si la respuesta a 3 es "no", el enlace no va.

### 4.2 Regla del "camino mínimo"

Un enlace debe apuntar al **nodo más específico** que sirva,
no al más general.

**Mal:** `dueno → soporte → us_soporteX` (apunta al nodo real
del soporte, expone `duenos` y desde ahí a otros dueños).

**Bien:** `dueno → soportes → publico_soporteX` (contenedor
`soportes` con el `publico` de cada soporte como hijo; el
`publico` solo tiene `nivel`, `nombre_real`, `email`).

### 4.3 Regla de la asimetría

Los enlaces de permiso son **unidireccionales por diseño**.

- Si `soporte → dueno`, el soporte ve al dueño.
- NO se agrega `dueno → soporte → us_soporteX`. Se agrega
  `dueno → soporte → publico_soporteX`.
- Si el dueño necesita saber "quién es mi soporte", ve el
  `publico` (nombre, email). No ve la lista de otros dueños
  del mismo soporte.

### 4.4 Regla del alias vs copia

Cuando dos usuarios comparten un recurso (por ejemplo, los
pasajeros de un dueño son accesibles desde el terminal), hay
dos opciones:

- **Alias:** el mismo nodo físico, referenciado desde dos
  contenedores. Cambios desde un lado se ven desde el otro.
  Usar cuando el recurso es compartido de verdad.
- **Copia:** dos nodos separados. Cambios desde un lado NO se
  ven desde el otro. Usar cuando el recurso debe aislarse.

**Ejemplo real:** el contenedor `pasajeros` del compartido es
un alias del `pasajeros` del dueño. El terminal y el dueño ven
el mismo pasajero, no dos copias.

**Ejemplo real (en contra):** las ventas usan árboles
**entrelazados** (mismo nodo venta físico, pero con dos juegos
de enlaces de árbol: `hmi`/`hd`/`p` para el dueño y
`hmi_<term>`/`hd_<term>`/`p_<term>` para el terminal). Ver §5.6.
Si compartieran el árbol, el BFS del terminal alcanzaría TODAS
las ventas del dueño.

### 4.5 Regla de no exponer IDs numéricos

Los IDs numéricos de nodos cambian entre cargas. Nunca usar un
ID numérico para "rastrear" un nodo entre dos POSTs. Para
referenciar establemente un nodo, usar:

- Un ID especial (`us_<nombre>`, `aplicacion`, etc.).
- Un nombre de enlace estable (`dueno`, `viajes`, etc.).
- Navegación desde una raíz dentro de una sola carga.

### 4.6 Regla del soft delete para nodos multi-contexto

Si un nodo es alcanzable desde **más de un contexto** (por
ejemplo, una venta del dueño referenciada desde el compartido
del terminal), **no puede ser destruido** por el guardado
parcial de uno solo de los contextos. La destrucción borra la
fila de la DB y deja huérfanas las aristas del otro contexto.

Patrón: **soft delete coordinado**. El contexto que no tiene
vista completa marca el nodo con un autoenlace (por ejemplo
`cancelada`) y lo deja en el árbol del contexto que sí lo verá.
Ese contexto lo destruye al recorrer. Ver §5.6.

---

## 5. PATRONES CONCRETOS

### 5.1 Contexto opcional en funciones que navegan

Patrón estándar para que una función sirva tanto a dueño como
a terminal:

```php
function obtener_contenedor_viajes_dueno(
    string $nombre_dueno,
    ?Nodo $nodo_contexto = null
) {
    if ($nodo_contexto === null) {
        $nodo_contexto = Nodo::nodo_por_id('us_' . $nombre_dueno);
    }
    if (!$nodo_contexto) return null;
    $nodo_viajes = $nodo_contexto->adyacente('viajes');
    // ...
}
```

El enrutador pasa `_contexto_solicitante($nombre_sol)`:

- Si el solicitante es terminal → devuelve su compartido.
- Si es dueño/admin/soporte → devuelve `null`.

### 5.2 Contexto del dueño en funciones que buscan por id

Con carga parcial, el dueño no tiene `usuarios` en memoria. Las
funciones que antes navegaban desde `usuarios` para buscar un
recurso por su id (una venta, una liquidación, una rendición)
deben recibir el nombre del dueño y navegar desde `us_<dueño>`
directamente.

Helper en el enrutador: `_nombre_dueno_solicitante($sol, $post)`:

- Solicitante dueño → su propio nombre.
- Solicitante admin/soporte → el del POST (`nombre_dueno`).
- Solicitante terminal → `null` (usa `_nombre_terminal_solicitante`).

Ejemplo en `Venta.php`: `_buscar_venta_por_id($id, $nombre_terminal,
$nombre_dueno)`.

### 5.3 Árboles paralelos con nombres parametrizados

Cuando dos contextos comparten nodos en una lista ordenada
(ventas, cancelaciones):

- Cada nodo tiene DOS juegos de enlaces: `hmi`/`hd`/`p` para
  el árbol del dueño, `hmi_<term>`/`hd_<term>`/`p_<term>` para
  el del terminal.
- Las funciones que navegan reciben un array `$nombres` o
  `null` (default).
- El helper `_nombres_arbol_para_contexto($nodo_contexto,
  $nombre_terminal)` devuelve los nombres parametrizados si el
  contexto es un compartido, o `null` si no.

### 5.4 Contenedores raíz duplicados

Los contenedores raíz de árboles paralelos son **distintos
nodos**:

- `privado/ventas` (raíz del árbol del dueño).
- `compartido_con_us_termX/ventas` (raíz del árbol del terminal).

Si compartieran la raíz, el BFS del terminal alcanzaría todas
las ventas del dueño.

### 5.5 Enlaces de permiso explícitos

Cuando un usuario "administra" a otro sin ser su par:

- Soporte → dueño: `soporte → duenos → us_duenoY` (apunta al
  nodo real, porque el soporte ve todo del dueño).
- Dueño → soporte(s): `dueno → soportes → publico_soporteX`
  (contenedor, admite varios soportes; apunta al `publico`,
  porque el dueño solo ve datos públicos).

### 5.6 Árboles entrelazados

Cuando un mismo nodo lógico participa en dos árboles (uno por
cada contexto que lo ve), usamos **árboles entrelazados**:

- **Mismo nodo físico.** Una venta hecha por un terminal es UN
  solo nodo venta. No hay copia.
- **Dos juegos de enlaces de árbol.** `hmi`/`hd`/`p` (árbol
  del dueño) y `hmi_<term>`/`hd_<term>`/`p_<term>` (árbol del
  terminal).
- **Dos contenedores raíz distintos.** El `ventas` del dueño y
  el `ventas` del compartido del terminal son nodos diferentes.
  Cada uno tiene su `hmi` apuntando al primer elemento de SU
  cadena.
- **Dos contextos en el bitmask.** El nodo venta tiene bits del
  dueño Y del terminal. Es alcanzable desde los dos.

**Cuándo se inserta en ambos árboles.**

En `confirmar_venta_actual`, cuando la venta la hace un terminal:

1. Se inserta en el árbol del compartido (parametrizado).
2. Se inserta en el árbol del dueño (default).

El dueño no vende directamente: el árbol del dueño se crea al
vuelo la primera vez que un terminal vende.

**Cuándo se destruye.**

Aquí está el problema. El nodo tiene DOS contextos. Si un
terminal lo destruye:

1. El guardado parcial borra la fila de la DB (estaba en
   `$filas_cargadas` del terminal).
2. La arista del árbol del dueño (que el terminal no toca)
   queda huérfana: apunta a una fila que ya no existe.
3. Cuando el dueño carga, la arista se saltea: la venta no
   aparece.

**Solución: soft delete coordinado.**

- **Terminal cancela:** marca el nodo con un autoenlace
  `cancelada` (`venta → venta`). NO lo destruye. Desenlaza del
  árbol del compartido (con su relink correspondiente).
  **Conserva los enlaces `hmi`/`hd`/`p` del árbol del dueño.**
- **Dueño recorre su árbol (`listar_ventas_por_dueno`):** al
  encontrar una venta con `cancelada`, la desenlaza del árbol,
  le quita el autoenlace y la destruye. Ver
  `_venta_esta_cancelada()` y `_destruir_venta_marcada()`.
- **Dueño cancela:** como antes. Desenlaza de ambos árboles y
  destruye el nodo en un solo paso.

**Por qué autoenlace y no nodo hijo.**

Un autoenlace (`venta → venta`) hace que el framework rechace
cualquier `Nodo::eliminar` accidental: el nodo tiene una
referencia entrante (la de sí mismo). Protección extra.

**Por qué conservar `hmi`/`hd`/`p` del árbol del dueño.**

El `hd` es el enlace entre hermanos: la cadena del árbol.
Si al marcar la venta se le borra el `hd`, cuando el dueño
recorra el árbol y destruya la primera marcada, **no puede
llegar a las siguientes**. Todas las demás ventas quedan
inalcanzables.

Regla: los enlaces del árbol del contexto que verá el nodo
marcado se **conservan**. Los del árbol del contexto que ya no
lo verá (`hmi_<term>` etc.) se limpian.

**Aplica también a cancelaciones (v1.5piloto.81t).** El
patrón de árboles paralelos y soft delete coordinado también
se usa para el árbol de cancelaciones. Cada compartido tiene
su árbol de cancelaciones con nombres parametrizados
(`hmi_<term>`), y el dueño tiene el suyo con nombres default.
La cancelación creada por un terminal se inserta en el árbol
del compartido con parámetro, y se encola en
`pendientes_cancelaciones` para que el dueño la reinserte en
su árbol con default. Ver `prompt_piloto.md` §5.17.

### 5.7 `_es_compartido` como marca

Los compartidos llevan `_es_compartido → "1"`. Sirve para:

- Las funciones pueden detectar "estoy navegando un compartido"
  y usar los nombres parametrizados.
- El comando `grafo:nodo` del framework expone
  `destino_es_compartido` para las pruebas.

### 5.8 Inserción diferida (buzones) para árboles compartidos

Cuando un contexto **no puede insertar** en el árbol del
otro (porque el contenedor raíz no está en su subgrafo),
se usa un **buzón**: un contenedor plano dentro del
compartido, en el que el contexto parcial deposita el
nodo, y que el contexto completo lee y procesa en su
próxima carga.

**Formato:**

- El buzón vive en el compartido:
  `compartido_con_us_X/pendientes_<tipo>`.
- Los hijos son los **mismos nodos físicos** (venta,
  cancelación) referenciados por su id.
- El contexto parcial: agrega el nodo a su propio árbol
  (donde sí puede) y lo encola en el buzón.
- El contexto completo: al listar, drena el buzón
  (inserta en su árbol, idempotentemente) antes del
  barrido normal.

**Detalle crítico: el bitmask lo propaga el framework,**
**no la app.** Cuando el terminal crea el buzón y encola
la venta, ninguno de los dos tiene todavía el bit del
dueño. El framework, al guardar parcial (v1.5i.8c),
propaga máscaras a los nodos nuevos por sus enlaces
entrantes: el buzón hereda el bitmask del compartido
(que sí tiene el bit del dueño), y la venta hereda del
buzón o del árbol del compartido. La app no toca bits
a mano. Si lo hiciera, la seguridad dejaría de emerger
de la topología y volvería a depender de que la app
se acuerde de escribir los bits correctos.

**Idempotencia:** al drenar, verificar por id si el
nodo ya está en el árbol del dueño. Después de procesar,
destruir el buzón si quedó vacío.

### 5.9 Inventario de estructuras compartidas por patrón

No todo lo compartido tiene el mismo problema. Hay tres
tipos de estructuras:

**Tipo A — Árboles que se reorganizan al insertar.**
Cuando agregás un nodo nuevo, hay que tocar el `hmi` del
contenedor (y el `hd` de algún otro nodo). **Estas
tienen el problema del §5.8.**

Estructuras tipo A en el piloto:

| Estructura | Quién agrega | Contenedor | Estado |
|---|---|---|---|
| Ventas (dueño) | Terminal vía venta | Dueño + compartido | **Buzón aplicado (v79a)** |
| Cancelaciones (dueño) | Dueño o terminal | Dueño + compartido | **Buzón aplicado (v79a)** |
| Rendiciones (dueño) | Solo dueño | Dueño | No aplica |
| Liquidaciones (dueño) | Solo dueño | Dueño | No aplica |
| Cupones (dentro de venta) | Terminal vía venta | Sigue a la venta | No independiente |
| Paradas (dentro de viaje) | Solo dueño | Dueño | No aplica |
| Detalles de rendición | Solo dueño | Dueño | No aplica |
| Ajustes de rendición | Solo dueño | Dueño | No aplica |

**Tipo B — Contenedores planos (enlaces por nombre).**
Agregar un nodo es solo `padre->_adyacente_en($nuevo,
'nombre')`. Si el padre es accesible desde ambos
contextos, se ve desde ambos. **No tienen el problema.**

Estructuras tipo B en el piloto: micros, empresas,
vehículos, terminales, terminales autorizadas, opciones
avanzadas, sesiones, bancos, `duenos` del soporte.

**Caso especial — Alias.** El contenedor `pasajeros` del
compartido apunta al mismo nodo físico que el `pasajeros`
del dueño. No es copia. El terminal y el dueño ven el
mismo pasajero. No tiene el problema porque no hay dos
nodos que sincronizar.

**Caso especial — Sincronización explícita.** Los viajes
se agregan solo desde el dueño, pero los compartidos
necesitan reflejarlos. Ya existe `_sincronizar_*` en
`Aplicacion/Compartidos.php`. No usa buzón porque el
dueño es el único que agrega.

**Tipo C — Listas circulares (primer/siguiente).**
Agregar un nodo es encadenar `siguiente`. No hay
reorganización de la cabeza. **No tienen el problema.**

Estructuras tipo C en el piloto: asientos (dentro de
cada piso), asientos-en-venta (venta actual).

**Regla de detección para estructuras nuevas:**

1. ¿Es un árbol (hmi/hd/p)? Si no, no.
2. ¿El terminal puede agregar nodos nuevos a ese árbol?
   Si no, no.
3. ¿El contenedor raíz del árbol es alcanzable desde
   ambos contextos (dueño y terminal)? Si sí, sí.

Si las tres son "sí", necesita el patrón del buzón.

### 5.10 Naming de los compartidos

**Regla del nombre.** Los compartidos se llaman
`compartido_con_<nombre_usuario>`, donde
`<nombre_usuario>` es el **nombre de usuario** del
terminal, **sin el prefijo `us_`**. Ejemplo:
`compartido_con_secretaria_carmen`.

Esto es consistente con la convención general del
piloto: los nombres visibles son nombres de usuario,
no IDs especiales. El ID especial (`us_<nombre>`) es
para referenciar el nodo entre cargas, no para el
nombre de enlace.

**Trampa común.** Escribir
`strpos($enlace, 'compartido_con_us_')` en un filtro
que deba detectar compartidos. **Nunca matchea**
porque el `us_` de más no está en el nombre real.
El filtro falla silenciosamente: el loop recorre los
compartidos, no encuentra ninguno, y el bug se
manifiesta más tarde como "el dueño no ve las ventas".

**Fix.** El filtro correcto es:
`strpos($enlace, 'compartido_con_') === 0`.
La parte del nombre de usuario se extrae con
`substr($enlace, strlen('compartido_con_'))`.

**Auditoría.** Antes de dar por cerrado cualquier
flujo que toque compartidos, correr:

```bash
grep -rn "compartido_con_us_" Aplicacion/
```

Si devuelve algo, es un bug latente del mismo tipo.
En la sesión v79b aparecieron dos:
`_procesar_pendientes_del_dueno` (Venta.php) y
`reparar_arboles.php`.

### 5.12 Helpers con contexto implícito

Cuando un helper necesita información que el llamador
no tiene por qué conocer, **derivarla del contexto en
vez de exigir que se la pasen**. Firma robusta: el
parámetro es opcional, y si no viene, el helper lo
obtiene por su cuenta.

**Caso real (§1.5.11).** Tres funciones de
`Pasajero.php` llaman a
`_nombres_arbol_para_contexto($ctx, '')`. Pasar `''`
como nombre del terminal hacía que el helper devolviera
null, y las funciones cayeran silenciosamente a los
nombres default. Pero el contenedor del compartido usa
nombres parametrizados. Resultado: el terminal nunca
veía los badges ni los botones relacionados.

**Patrón de la trampa.** El llamador no conoce el
nombre del terminal (solo tiene el nodo compartido).
El helper exigía el nombre. El llamador pasaba `''`
para "no aplica". El helper interpretaba `''` como
"no hay contexto" y devolvía null. Bug silencioso:
sin errores, sin warnings, comportamiento incorrecto.

**Fix.** El helper deriva el nombre del propio
contexto cuando no se lo pasan. El compartido tiene
`terminales → <nombre>`, de donde se puede leer.
Retrocompatible: si el nombre viene, se usa tal cual.

**Regla.** Antes de agregar un parámetro obligatorio a
un helper, preguntarse: "¿el llamador realmente lo
tiene?". Si no, el helper debe derivarlo del contexto
o aceptar `null`/`''` como "derivalo vos".

### 5.11 Lecciones de método

**1. El bitmask correcto no garantiza que el flujo
funcione.** En v79b, los SQLs de diagnóstico mostraron
que las ventas nuevas tenían la máscara 120 (con el
bit del dueño) y que el buzón existía. Todo el
pipeline parecía sano. El bug era un filtro de
naming que fallaba silenciosamente antes de llegar
al bitmask. Moraleja: cuando el estado persistido
parece correcto pero el comportamiento no, **el bug
está en el código que lo consume, no en los datos**.

**2. Verificar por SQL el estado real antes de asumir.**
Cuando un fix no resuelve el síntoma, antes de
proponer otro fix, correr SQLs que muestren el
estado real. En v79b eso evitó seguir buscando en el
lugar equivocado (el guardado parcial) cuando el
problema estaba en la lectura (el filtro de drenado).

**3. Un bug en un helper sugiere el mismo bug en sus
hermanos.** Cuando se detecta un patrón problemático
(por ejemplo, un filtro mal escrito), auditar los
demás helpers que usan el mismo patrón. En v79b, el
fix del filtro en `Venta.php` reveló el mismo bug
en `reparar_arboles.php`.

**4. El orden de las llamadas importa cuando una crea
contenedores que la otra consume.** Caso v81l:
`_asignar_duenos_a_soporte` se llamaba antes de
`_asegurar_niveles_usuario`. El `publico` del soporte
todavía no existía, y el enlace `dueno → soporte` caía
al fallback (nodo real del soporte), causando la fuga
cruzada entre dueños. Fix: reordenar (niveles primero,
después asignar). Regla: antes de encadenar dos
operaciones sobre el mismo nodo, verificar cuál crea
los nodos que la otra consume, y ordenarlas en
consecuencia.

---

## 6. ANTI-PATRONES

### 6.1 Enlaces cruzados sin pensar

Agregar `A → B` y `B → A` "por simetría" es un error. La
simetría no es un objetivo; el aislamiento sí.

**Caso real:** el enlace `dueno → soporte` originalmente apuntaba
al nodo real del soporte. Eso creaba una fuga cruzada entre
todos los dueños de un mismo soporte. El fix v78f lo cambió
para que apunte al `publico`.

### 6.2 Compartir contenedores entre contextos

Dos contextos **no deben compartir contenedores raíz** si
comparten los nodos hijos, porque el BFS del uno alcanzaría
todo lo del otro.

**Caso real:** las ventas usan árboles entrelazados con
contenedores raíz distintos. Si compartieran, el terminal vería
las ventas del dueño entero.

### 6.3 Iterar sobre la raíz `usuarios`

`Nodo::nodo_por_id('usuarios')` solo funciona en carga total.
En carga parcial, `usuarios` no está en memoria (salvo para el
admin, que tiene el enlace).

**Anti-patrón:**

```php
function buscar_algo(string $id) {
    $raiz = Nodo::nodo_por_id('usuarios');
    foreach ($raiz->adyacentes() as $dueno) {
        // iterar sobre todos los dueños
    }
}
```

**Patrón correcto:** recibir el dueño (o el contexto) como
parámetro y navegar desde ahí.

### 6.4 Chequeos defensivos que bloquean al admin

Intentar "prevenir" que el admin no llame a funciones que
iteran sobre todos los usuarios es contraproducente. El admin
**sí** tiene el enlace `us_Administrador → usuarios`, y la
función itera legítimamente.

**Caso real:** v78d agregó chequeos en `Usuario.php` que
bloqueaban a cualquiera que llamara `listar_usuarios` con grafo
parcial. El admin carga parcial siempre, así que el chequeo lo
bloqueaba a él. Revertido en v78o.

### 6.5 Confiar en el `nombre_solicitante` del cliente

El frontend manda `nombre_solicitante` en cada POST. Ese dato
es la clave del contexto de carga parcial. Un cliente malicioso
podría mandar otro nombre para cargar el subgrafo de otro
usuario.

**Mitigación actual:** el enrutador verifica permisos con
`_verificar_permiso_dueno` antes de la operación. Y el
`solicitante` se cruza con la sesión (si se implementa).

**Futuro:** el `nombre_solicitante` debería venir de la sesión
del servidor, no del POST.

### 6.6 Compartir credenciales en el grafo de la app

Los datos de credenciales (`codigo_hash`, `contrasena`,
`intentos_fallidos`, `bloqueado_hasta`) viven en un **grafo
separado**. No se accede a ellos desde el grafo de la app.

### 6.7 Exponer IDs numéricos en respuestas JSON

Los IDs numéricos cambian entre cargas. Exponerlos al frontend
invita a que el frontend los use como referencias estables
entre requests. NUNCA exponer un ID numérico como referencia
persistente.

Los IDs que sí van al frontend son: `nombre_usuario`, `dni`,
`id_venta`, `patente`, `nombre_viaje`, `nombre_micro`,
`token_sesion`. Todos strings estables.

### 6.8 Destruir un nodo multi-contexto desde un solo contexto

Destruir un nodo con bits de múltiples contextos desde el
guardado parcial de uno solo **rompe la topología persistida**.
La arista del otro contexto queda huérfana.

**Caso real:** v78w. El terminal cancelaba una venta y la
destruía. La arista `dueño → ventas → venta` quedaba apuntando
a una fila inexistente. El dueño dejaba de ver TODAS las
ventas cuando el traversal se rompía.

**Patrón correcto:** soft delete coordinado. Ver §5.6.

### 6.9 Confundir el ID del framework con la fila de la DB

Los IDs del framework (`Nodo::id()`) son efímeros. Los `id_fila`
de la DB son estables. Nunca usar el ID del framework como
clave persistente entre requests.

**Caso real:** el bug §1.5. Los fixes v78r/v78s/v78t/v78u
intentaban mapear "ID del framework" ↔ "fila de la DB", pero
los IDs del framework cambiaban entre cargas. El grafo creció a
99k nodos por inserciones duplicadas.

### 6.11 Pasar `''` a `_nombres_arbol_para_contexto`

Ver §5.12. Pasar cadena vacía como nombre del
terminal cuando el contexto es un compartido hace que
el helper devuelva null y las funciones caigan a los
nombres default, pero el contenedor del compartido usa
nombres parametrizados. Bug silencioso.

**Caso real:** v79c. `pasajero_tiene_pasajes`,
`pasajero_tiene_pasajes_activos` y
`pasajero_tiene_ventas_activas` en `Pasajero.php`.
El helper ahora deriva el nombre del propio
compartido.

### 6.10 Filtrar compartidos con el prefijo `us_` de más

Ver §5.10. Los compartidos se llaman
`compartido_con_<nombre_usuario>`, no
`compartido_con_us_<nombre>`. Un filtro con el `us_` de
más nunca matchea y falla silenciosamente.

**Caso real:** v79b. `_procesar_pendientes_del_dueno`
tenía el filtro mal. El buzón de ventas nunca se
drenaba y el dueño no veía las ventas nuevas. El
mismo bug latente en `reparar_arboles.php`.

### 6.12 Confiar en el POST del backend sin probar el flujo del usuario

Un test que solo hace POST al backend puede pasar aunque
el flujo del usuario esté roto. Si el frontend no manda
los parámetros que el backend espera, el usuario ve un
error aunque el endpoint funcione con los parámetros
correctos.

**Caso real:** v81i. Los POST `rendiciones/obtener`,
`rendiciones/aceptar_ajuste` y `liquidaciones/obtener`
existían y funcionaban si se les pasaba `nombre_dueno`,
pero el frontend del piloto no lo mandaba, y el soporte
veía "no encontrada". Un test que solo hace POST con el
`nombre_dueno` explícito NO detecta el bug.

**Patrón correcto:** las pruebas que verifican flujos
del usuario deben hacer el clic del usuario y verificar
el efecto en el DOM (modal abierto con contenido). El
POST es un detalle de implementación del frontend, no
el contrato.

---

## 7. CÓMO VERIFICAR EL AISLAMIENTO

### 7.1 Verificación manual desde la pestaña Grafo

Como admin, en la pestaña Grafo del piloto:

- Buscar el nodo por texto (nombre del usuario, DNI, etc.).
- Ver "Referencias entrantes". Si un nodo tiene referencia
  entrante desde otro contexto que NO debería verlo, hay fuga.
- Ver "Enlaces salientes". Confirmar que desde el nodo de un
  usuario X no hay camino a nodos privados de otro usuario Y.

### 7.2 Verificación desde las pruebas del plugin

El plugin de pruebas (en `iteradoresJS/`) tiene pruebas que
verifican aislamiento. Ejemplos:

- Prueba 57 (`enlace_dueno_apunta_al_compartido`): verifica que
  el enlace `dueno` del terminal apunta a un compartido.
- Prueba 58 (`venta_terminal_arbol_dual`): verifica que una
  venta hecha por un terminal aparece en los dos listados
  (`ventas/listar` tipo=dueno y tipo=terminal).
- Prueba 60 (`cancelar_venta_arbol_dual`): simétrica. Cancelar
  una venta la quita de los dos árboles.
- Pruebas 67, 68, 69 (v1.5plugin.8f): verifican el flujo del
  usuario completo (login → pestaña → elegir dueño → click en
  "Ver detalle") para rendiciones y liquidaciones con
  solicitante soporte y dueño. Detectan bugs del frontend que
  un POST directo no vería (ver §6.12).

### 7.3 Comando `grafo:raices`

Lista los IDs especiales con sus adyacentes directos. Útil para
auditar qué es raíz y qué no.

### 7.4 Comando `grafo:nodo`

Dado un ID, devuelve sus adyacentes y referencias entrantes. El
campo `destino_es_compartido` en cada adyacente ayuda a verificar
topología sin seguir IDs numéricos.

### 7.5 Verificación de carga parcial

La carga parcial está **activa desde v78g**. Las
verificaciones de abajo aplican al código actual, con las
excepciones documentadas en `_contextos_para_operacion`
(gestión de usuarios, informes de cancelación/liquidación/
rendición).

Después de un `cargar_parcial([us_X])`:

- `Nodo::nodo_por_id('us_Y')` donde Y ≠ X **debe devolver
  null** (a menos que Y sea alcanzable desde X, por ejemplo
  porque X es admin o el compartido apunta a Y).
- `Nodo::nodo_por_id('usuarios')` **debe devolver null** (salvo
  para admin).

### 7.6 Verificación de árboles entrelazados

Cuando una venta hecha por terminal está bien formada:

- Tiene dos juegos de enlaces de árbol: `hmi`/`hd`/`p`
  (default) y `hmi_<term>`/`hd_<term>`/`p_<term>`.
- Tiene DOS `p`: uno al contenedor `ventas` del dueño y otro
  al contenedor `ventas` del compartido del terminal.
- Aparece en ambos listados (`ventas/listar` tipo=dueno y
  tipo=terminal).

Verificación por SQL sobre `adyacente_contexto`:

```sql
-- Aristas salientes de una venta
SELECT enlace, idadyacente_fila
FROM adyacente_contexto
WHERE idnodo_fila = <id_fila_venta>;

-- Aristas entrantes de una venta
SELECT idnodo_fila, enlace
FROM adyacente_contexto
WHERE idadyacente_fila = <id_fila_venta>;
```

Si tiene los dos `p` y el `hmi` correspondiente en cada contenedor,
está bien formada.

### 7.7 Reparación de árboles

Comando `app:reparar_arboles`, ejecutable con
`index.php?reparar_arboles=1`. Reconstruye aristas `hmi`
faltantes de todos los contenedores de árbol. Idempotente. Se
usa en producción como red de seguridad.

---

## 8. BUGS CONOCIDOS Y LIMITACIONES

### 8.1 Bug del viaje viejo — RESUELTO en v78v

**Estado:** resuelto. Framework 1.5i.8a.

**Síntoma original:** después de una operación de guardado
del terminal, el viaje viejo del dueño perdía el bit del dueño
en su `contexto_mascara`. La próxima carga parcial del dueño no
lo veía.

**Causa raíz:** los IDs numéricos del framework son efímeros.
El guardado parcial intentaba mapear "ID del framework" ↔ "fila
de la DB" entre requests, y esa correspondencia se rompía.

**Solución:** cambio de esquema en SQL64 (v1.5i.8a). La DB usa
`id_fila` (AUTO_INCREMENT, PK) como identidad estable de cada
nodo, e `id_especial` para el ID especial del framework (NULL
para numéricos). `adyacente_contexto` referencia sus extremos
por `idnodo_fila` / `idadyacente_fila`. Mapa estático
`$_mapa_id_a_fila` relaciona ambos mundos dentro de una request.

**Fixes intentados que no alcanzaron:** v78r (regla de borrado
de aristas), v78s (unir máscaras). El bug era de esquema, no de
máscaras. La lección: dos fixes que no alcanzan son la señal de
parar y hacer un test de diagnóstico en serio.

### 8.2 Nodos compartidos entre contextos — RESUELTO

**Estado:** resuelto para ventas (v78w-v78z) y cancelaciones
(v79a con buzón). Rendiciones y liquidaciones solo las crea el
dueño, no aplica. Ver §9.

**Síntoma:** el terminal cancela una venta y, al volver el
dueño, desaparecen todas las ventas.

**Causa raíz:** nodo multi-contexto destruido por el guardado
parcial de un solo contexto. La arista del otro contexto
quedaba huérfana. Además, al marcar sin conservar `hd`, el
traversal del árbol del dueño se cortaba.

**Solución:** soft delete coordinado con autoenlace `cancelada`
y conservación de `hmi`/`hd`/`p` del árbol del dueño. Ver §5.6.

### 8.3 Regla de borrado de aristas

En `_guardar_con_filtro` con filtro != 0, se borran solo
aristas cuyos DOS extremos están en las filas cargadas
(v78v: la lista sale de `$_mapa_id_a_fila`, no de la DB). Las
aristas que cruzan el borde se conservan tal cual.

**Limitación:** si un flujo modifica una arista que cruza el
filtro (raro), el cambio no se persiste. Se resuelve con
guardado por delta real (Fase 4 del framework).

### 8.4 Preservación de bits

Con filtro != 0, las máscaras del BFS se unen con las que el
nodo ya tenía en memoria (fijadas al cargar). Nunca se pierde
un bit preexistente.

**Limitación:** quitar un bit de un nodo requiere un mecanismo
explícito. Hoy no existe; se hace a mano.

### 8.5 Espejo JS

Alineado a **V1.5i.8c** en V1.5plugin.7b. El espejo JS
implementa el mismo esquema `id_fila` + `id_especial` + mapa
estático, y la misma propagación de máscaras a nodos nuevos
que el PHP (fix v1.5i.8c).

**Nota:** al abrir la extensión por primera vez tras el bump,
IndexedDB hace upgrade a la versión 2 y borra los almacenes
viejos. Los datos del plugin se pierden (aceptable: la fuente
real es MySQL del piloto).

### 8.6 Helpers que iteran sobre todos los dueños (B3.3c)

**Reducido a 3 funciones.** Nota sobre el conteo: el texto
original decía "7" (era un typo: las funciones realmente
listadas eran 6). De esas 6, se adaptaron 3 en v81h
(liquidación y rendición). Las 3 restantes siguen
pendientes.

Las funciones de liquidación y rendición se adaptaron al
patrón `?string $nombre_dueno`:

- `Venta.php`: `_buscar_venta_por_id` (fallback),
  `obtener_cancelacion_por_id`.
- `Pasajero.php`: `obtener_pasajero_por_dni` (rama del
  historial de ventas).

**Ya adaptadas en v81h:**
- `Liquidacion.php`: `obtener_liquidacion_por_id(?string
  $nombre_dueno = null)`. Si viene, navega directo desde
  `us_<dueño>`. Si no, fallback a recorrer todos los dueños
  desde la raíz (carga total, compatibilidad con
  `Impresion.php`).
- `Rendicion.php`: `obtener_rendicion_por_id(...)` y
  `aceptar_ajuste_rendicion(...)` con el mismo patrón.

**Consecuencia:** con carga parcial, las 3 restantes
devuelven null o vacío si el solicitante no tiene `usuarios`
en memoria (dueño y soporte). Se resuelven cuando aparezca
dolor real o cuando implementemos iteradores con ID especial.

**Cierre de B3.3c (v1.5piloto.81j-81l).** Las 3 funciones
pendientes quedaron resueltas:

- `obtener_cancelacion_por_id`: acepta `?string $nombre_dueno`
  y `?string $nombre_terminal` (v81j).
- `obtener_pasajero_por_dni`: la rama sin contexto navega
  `us_<dueño>` directo (v81j). Fix de privacidad: antes
  iteraba sobre todos los dueños y mezclaba ventas de
  distintos dueños cuando dos pasajeros compartían DNI.
- `_buscar_venta_por_id`: fallback defensivo, no aplica en la
  práctica (todos los llamadores del Enrutador pasan dueño o
  terminal). Pendiente anotado: `usuarios` como contenedor de
  nombres, para eliminar el fallback.

Con eso, la Fase B del modelo topológico quedó completa.

**Bug del frontend de ventas.js (v81k).** `ventas.js` no
mandaba `nombre_dueno` en 4 fetch (`ventas/obtener`,
`ventas/pagar_cupon`, `ventas/info_cancelacion`,
`ventas/cancelar`). Mismo patrón que el bug v81i de
rendiciones/liquidaciones. Nuevo helper
`_nombre_dueno_para_peticiones_vendidos()` en el frontend.

**Rediseño `soportes` plural (v81l).** El enlace
`dueno → soporte → publico` singular se reemplazó por un
contenedor `dueno → soportes → publico_<soporte1>, ...`.
Causa raíz del fallback al nodo real: en `agregar_usuario`,
`_asignar_duenos_a_soporte` corría antes de
`_asegurar_niveles_usuario`, y el `publico` no existía
todavía. Ver §5.11 lección 4.

**Bug de frontend asociado (v81i).** El backend ya navegaba
correctamente con `nombre_dueno`, pero el frontend del piloto
no lo mandaba en `rendiciones/obtener`,
`rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`. Solo
fallaba con soporte (dueño y admin lo resolvían por otros
caminos). Fix en `rendiciones.js` y `liquidaciones.js`. Ver
§6.12.

### 8.7 Módulos menores sin contexto — CERRADO en v81h

Los cuatro módulos ya están adaptados al patrón `us_<dueño>`:

- `Empresa.php`: adaptado en v76u-v77r.
- `Vehiculo.php`: adaptado en v77v-v77x.
- `Liquidacion.php`: adaptado en v81h.
- `Rendicion.php`: adaptado en v81h.

Solo quedan los 4 helpers de §8.6, que son funciones que
buscan por id y no módulos completos.

### 8.8 Contexto del solicitante viene del cliente

El `nombre_solicitante` se manda por POST y se usa para
determinar los contextos de carga. Un cliente malicioso podría
mandar otro nombre para cargar el subgrafo de otro usuario.

**Mitigación actual:** `_verificar_permiso_dueno` antes de la
operación. La verificación de nivel sigue en el enrutador.

**Futuro:** el `nombre_solicitante` debería venir de la sesión
del servidor.

### 8.9 Carga parcial: activa y en producción

La carga parcial está **activa desde v78g**. Toda operación
POST con `nombre_solicitante` carga parcial con `[us_<sol>]`.
Sin solicitante, carga total.

Las excepciones (acciones que siguen usando carga total) están
documentadas en `_contextos_para_operacion`:

- Gestión de usuarios (crear, editar, eliminar usuario y
  terminal).
- Informes de cancelación, liquidación y rendición (usan los
  helpers de §8.6).

Los bugs §1.5 y §1.5.10, que aparecían solo con carga parcial
activa, están resueltos en el framework 1.5i.8a-c.

### 8.10 Persistencia silenciosa en hostings compartidos (bug §4.6)

**Síntoma.** En hostings compartidos (InfinityFree), una
cancelación desde un terminal respondía `exito: true` pero no
se persistía. El asiento seguía vendido, la venta aparecía
en el listado, la cancelación no se veía desde el dueño ni
desde el terminal. En local, el mismo flujo funcionaba.

**Causa raíz.** Múltiples capas:

1. **`CREATE TEMPORARY TABLE` no permitida.** InfinityFree no
   le da el privilegio `CREATE TEMPORARY TABLES` al usuario de
   la DB. `_guardar_con_filtro` la usaba para el DELETE de las
   filas cargadas y fallaba con "Access denied".

2. **`mysqli_report` strict activo (PHP 8.4).** Con
   `MYSQLI_REPORT_STRICT` (default en 8.4), cualquier fallo
   de mysqli lanza excepción en lugar de devolver `false`. El
   `@` silencia warnings pero no excepciones. El error de
   `CREATE TEMPORARY TABLE` subía como excepción y se
   propagaba hasta `_guardar_con_filtro`, que lo capturaba
   como un "fallo CREATE TEMPORARY TABLE" (que era el
   síntoma, no la causa).

3. **Rate limit de conexiones.** El flujo de `cancelar_venta`
   abría 4-5 conexiones (cargar_parcial + guardar_parcial +
   refrescos del frontend). InfinityFree limita las conexiones
   por minuto; la 5ª o 6ª fallaba con "Access denied".

4. **`cancelar_venta` no chequeaba `guardar_ambos`.** Cuando
   `guardar_parcial` fallaba, `cancelar_venta` igual respondía
   `exito: true`. La UI decía "venta cancelada" pero la DB no
   se actualizaba.

**Fixes (saga C.6-C.10):**

- C.6: `cancelar_venta` chequea `guardar_ambos`. Si falla,
  `exito: false`.
- C.7-C.8: singleton de conexión. Una sola conexión física
  por request.
- C.9-C.9b: `mysqli_report(OFF)` + `SELECT 1` en lugar de
  `ping()`.
- C.10: sin `CREATE TEMPORARY TABLE`. `IN (...)` directo.

**Lecciones:**

- **`@` no atrapa excepciones.** Solo warnings. En PHP 8.4,
  con `mysqli_report` strict, cualquier fallo de mysqli es
  excepción. Hay que usar `try/catch` o `mysqli_report(OFF)`.
- **Los errores silenciosos son peores que los ruidosos.**
  `cancelar_venta` decía "éxito" y el usuario pensaba que
  funcionaba. Un `try/catch` con `exito: false` es más
  honesto.
- **Verificar la causa raíz, no el síntoma.** El primer fix
  (C.6) apuntó al síntoma (guardar_ambos no chequeado). El
  error seguía pero cambiaba de línea. Recién con C.10 se
  resolvió de verdad.
- **Los hostings compartidos tienen privilegios limitados.**
  `CREATE TEMPORARY TABLES`, `SET SESSION wait_timeout`,
  `mysqli_report` no son garantía. Cada uno hay que testearlo
  en el hosting real.

Ver también: `prompt_framework_iteradores.md` §6.7 y §11.6.

---

## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN

Los tres bugs encadenados que resolvimos en ventas pueden
aparecer en los otros árboles. Hay que revisarlos uno por uno:

### 9.1 Rendiciones — no aplica

Solo el dueño las crea. El contenedor vive en
`us_dueno/rendiciones`. No es alcanzable desde ningún
compartido. No tiene el problema de nodos multi-contexto. Los
listados y detalles funcionan con carga parcial desde v81h.

### 9.2 Liquidaciones — no aplica

Igual que rendiciones. Solo el dueño.

### 9.3 Cancelaciones — resuelto en v79a (buzón)

Pueden crearse desde el dueño o desde un terminal. Se resolvió
con el mismo patrón que las ventas: **inserción diferida con
buzón**. El terminal deposita la cancelación en
`compartido_con_us_termX/pendientes_cancelaciones`; el dueño
lo drena en su próxima carga. Ver §5.8.

### 9.4 Regla general a aplicar en cada caso

1. Si un nodo es referenciado por más de un contexto, **no
   destruirlo** desde el contexto parcial. Usar soft delete
   (autoenlace tipo `cancelada`) y que lo destruya quien tenga
   la vista completa.
2. Al marcar, **conservar los enlaces del árbol**
   (`hmi`/`hd`/`p`) del contexto que sí lo verá.
3. Después de tocar un contenedor de árbol, correr
   `?reparar_arboles=1` como red de seguridad.

---

## 10. CHECKLIST PARA NUEVOS FLUJOS

Cuando agregues un flujo nuevo (nueva acción del enrutador,
nueva función que navega el grafo), pasar por esta lista:

1. **¿Qué usuario ejecuta la acción?** Dueño, terminal, admin,
   soporte.
2. **¿Qué subgrafo necesita?** Solo su propio contexto (carga
   parcial) o todo el grafo (carga total).
3. **Si el flujo puede ser ejecutado por un terminal, ¿la
   función que navega acepta `?Nodo $nodo_contexto`?** Si no,
   agregarlo.
4. **Si el flujo puede ser ejecutado por un dueño, ¿la función
   que busca por id acepta `?string $nombre_dueno`?** Si no,
   agregarlo (para no depender de la raíz `usuarios`).
5. **¿El enrutador pasa `_contexto_solicitante($sol)` o
   `_nombre_dueno_solicitante($sol, $post)`?** Si no, agregarlo.
6. **Si la función itera un árbol (`hmi`/`hd`), ¿usa los
   nombres parametrizados cuando el contexto es un compartido?**
   Ver `_nombres_arbol_para_contexto`.
7. **Si el flujo destruye un nodo multi-contexto, ¿usa soft
   delete?** Si no, replantear.
8. **¿La función resuelve IDs por `Nodo::nodo_por_id('usuarios')`?**
   Si sí, replantear para usar `us_<nombre>` directo o navegar
   por contexto.
9. **¿El flujo necesita guardar?** Verificar que `guardar_ambos`
   elige bien entre `guardar` y `guardar_parcial`.
10. **¿Hay una prueba del plugin que verifique el flujo?** Si
    no, agregarla.

---

## 11. GLOSARIO

- **Raíz de contexto.** Nodo con ID especial. El BFS de
  contextos parte de todas las raíces.
- **Bitmask / contexto_mascara.** Entero donde cada bit
  representa una raíz. Un nodo con bit N activo es alcanzable
  desde la raíz N.
- **Carga parcial.** `Controlador::cargar_parcial($nombre,
  $contextos)`. Carga solo los nodos alcanzables desde los
  contextos pedidos. **Desactivada desde v78v.**
- **Carga total.** `Controlador::cargar($nombre)`. Carga todo
  el grafo.
- **Guardado parcial.** `Controlador::guardar_parcial($nombre,
  $contextos)`. Persiste solo los nodos con bitmask que
  intersecta el filtro.
- **id_fila.** Identidad estable de una fila en `nodo_contexto`
  (BIGINT AUTO_INCREMENT). Introducida en v1.5i.8a.
- **id_especial.** ID especial del framework (NULL para
  numéricos, string para especiales). Columna de `nodo_contexto`.
- **Mapa `$_mapa_id_a_fila`.** Mapa estático de SQL64 que
  relaciona `id_nodo_framework ↔ id_fila` dentro de una request.
- **Compartido.** Nodo `compartido_con_<terminal>` colgando del
  dueño. Referencias filtradas a los viajes/empresas/ventas del
  terminal. Marcado con `_es_compartido`.
- **Contenedor por nivel.** `publico`, `privado`,
  `compartido_con_X`. Cuelgan de la raíz del usuario.
- **Enlace de permiso.** Enlace directo en la raíz del usuario
  (`dueno` en el terminal, `soporte` en el dueño) que abre
  camino a otro usuario.
- **Árbol paralelo / entrelazado.** Mismo nodo físico, dos
  juegos de enlaces (`hmi`/`hd`/`p` y
  `hmi_<term>`/`hd_<term>`/`p_<term>`) para aislar el recorrido
  entre contextos.
- **Soft delete.** Marcar un nodo con un autoenlace (por
  ejemplo `cancelada`) para que lo destruya otro contexto que
  tenga vista completa. Patrón para nodos multi-contexto.
- **Autoenlace.** Enlace de un nodo hacia sí mismo. Se usa para
  marcar y para que `Nodo::eliminar` falle por referencia
  entrante (protección).

---

## 12. PRINCIPIO DE ORO

**Antes de agregar un enlace nuevo, preguntarse: ¿esto expone
un camino que no debería existir?**

Si la respuesta es "quizás", no agregarlo. Es más fácil agregar
un enlace nuevo con un propósito claro que quitar uno que ya
está causando fugas.

**Antes de destruir un nodo, preguntarse: ¿este nodo es
alcanzable desde más de un contexto?**

Si la respuesta es "sí", usar soft delete. La destrucción
desde un solo contexto rompe la topología persistida.

Y cuando aparezca una fuga: **anotarla antes de arreglarla.**
Saber exactamente qué está expuesto y por qué es más valioso
que un fix rápido que puede romper otra cosa.

---

**FIN DEL PROMPT**