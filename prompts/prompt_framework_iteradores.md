# Prompt de trabajo — Framework Iteradores

Este es un prompt autocontenido con todo lo que sabemos sobre el framework
**Iteradores**. Vive en el propio proyecto, en
`prompts/prompt_framework_iteradores.md`, y se pega al principio de la
conversación junto con `prompts/prompt_piloto.md` y
`prompts/prompt_sistema_scripts.md`.

Se actualiza cuando cambia el framework. No incluye nada específico del
piloto: eso vive en `prompts/prompt_piloto.md`.

El framework tiene un **espejo en JavaScript** para navegador (ver
sección 12). Comparten la API conceptual, pero difieren en persistencia
(SQL/JSON/XML en PHP, IndexedDB/JSON/XML en JS) y en detalles propios
de cada lenguaje.

---

## 1. QUÉ ES ITERADORES

Iteradores es un framework PHP para construir y manipular **grafos** y
**estructuras enlazadas**. Toda la información se modela como nodos
conectados por enlaces con nombre. No hay tablas ni registros: hay un
grafo dirigido con nombres de enlace que actúan como claves.

Las piezas centrales son tres:

- **Nodo**: unidad de información. Tiene un dato y enlaces salientes.
- **Iterador**: puntero con nombre que recorre el grafo por caminos.
- **Controlador**: coordina la persistencia y expone operaciones de alto
  nivel.

Además hay helpers de árbol (`miscelaneas/Arbol.php`) para representar
listas y árboles mediante enlaces `hmi`/`hd`/`p`.

---

## 2. CLASE NODO

Namespace: `Iteradores\Nodos\Nodo`

### 2.1 Métodos estáticos (fábrica)

- `Nodo::crear()`: crea un nodo vacío.
- `Nodo::crear_con_dato($dato)`: crea un nodo con el dato dado. El dato
  puede ser string, número o array. No se procesa, se encapsula tal cual.
- `Nodo::crear_con_id($id)`: crea un nodo con **ID especial** (string no
  numérico). Es la única forma de tener un ID estable entre cargas del
  grafo.
- `Nodo::crear_con_dato_e_id($dato, $id)`: combina los dos anteriores.
- `Nodo::nodo($elemento = null, &$es_nodo = null)`: garantiza que el
  argumento sea un nodo. Si ya lo es, lo devuelve. Si no, crea uno nuevo
  con ese dato. El segundo parámetro por referencia indica cuál fue el
  caso.
- `Nodo::eliminar($nodo)`: elimina un nodo. **Solo funciona si el nodo
  no tiene referencias entrantes.** Hay que desenlazar primero.
- `Nodo::nodo_por_id($id)`: busca un nodo por ID en la superestructura
  cargada. Devuelve `null` si no existe.
- `Nodo::hay_nodos_en_superestructura()`: devuelve `true` si hay nodos
  cargados.
- `Nodo::existe($id)`: chequeo booleano sin alertar.
- `Nodo::cantidad_de_nodos()`.
- `Nodo::vaciar_superestructura($token)`: vacía todo. Requiere el token
  de seguridad interno.

### 2.2 Métodos de instancia

- `$nodo->_dato($dato)` / `$nodo->dato()`: lee o escribe el dato.
- `$nodo->_adyacente_en($nodo_destino, $enlace, $reemplazar = false)`:
  crea un enlace saliente con ese nombre. Si ya existe un enlace con ese
  nombre y `$reemplazar` es `false`, falla.
- `$nodo->_adyacente($nodo_destino)`: crea un enlace con nombre = ID del
  nodo destino. Si ya existe, agrega sufijo `.1`, `.2`, etc.
- `$nodo->adyacente($enlace)`: devuelve el nodo adyacente o `null`. Nunca
  devuelve un string.
- `$nodo->adyacentes()`: devuelve un array `enlace => nodo`. Si no hay
  ninguno, devuelve `[]` (array vacío, no `null`).
- `$nodo->eliminar_adyacente($enlace)`.
- `$nodo->eliminar_adyacentes()`.
- `$nodo->tiene_adyacente()`.
- `$nodo->por_cada_adyacente_ejecutar(callable, ...$params)`.
- `$nodo->id()`: devuelve el ID. Si no tiene uno especial, se genera de
  forma perezosa en la primera llamada.
- `$nodo->es_especial()`: `true` si el ID es un string no numérico.
- `$nodo->cantidad_de_incidentes()`.

### 2.3 Reglas aprendidas a la fuerza

- **Todos los nombres de enlace son strings.** Aunque a veces parezcan
  números, son strings.
- **Los datos se guardan como strings llanos** para no romper la
  persistencia. Los números también son strings. Si guardás un `int`,
  al persistir y recargar te va a llegar como string.
- **PHP convierte automáticamente claves de array que son strings
  numéricos a `int`.** Esto causó bugs con DNIs y patentes. Solución:
  forzar `(string)$clave` al iterar sobre claves que pueden ser
  numéricas.
- **`adyacente()` siempre devuelve un Nodo o null.** Nunca un string ni
  un dato.
- **`_adyacente_en($nodo, $enlace, true)`** reemplaza el enlace existente
  con ese nombre.
- **No comparar nodos con `===`.** Comparar siempre por `->id()`.
- **`Nodo::eliminar($nodo)` solo funciona si no tiene referencias
  entrantes.** Hay que desenlazar primero, progresivamente, empezando
  por las hojas.
- **Los IDs especiales son la única forma de referenciar un nodo entre
  cargas.** Un nodo con ID normal (generado) puede cambiar su ID si se
  destruye y se vuelve a crear. Los IDs especiales son estables.
- **`es_id_especial($id)`** verifica si un string es un ID especial
  (string no numérico). Está en `Objeto`, no en `Nodo`.

---

## 3. CLASE ITERADOR

Namespace: `Iteradores\Iteradores\Iterador`

El Iterador es un puntero con nombre que recorre el grafo por caminos.

### 3.1 Fábrica

- `Iterador::crear($nombre, $elemento = null, &$es_nodo = null)`.
- `Iterador::cargar($nombre, ...)`.
- `Iterador::iterador(...)`.
- `Iterador::existe($nombre)`.

### 3.2 Operaciones

- `$iter->actual()`, `$iter->_actual()`.
- `$iter->dato()`, `$iter->_dato()`.
- `$iter->avanzar($camino, $cant, ...)`.
- `$iter->_adyacente_en(...)`, `$iter->_adyacente(...)`.
- `$iter->adyacente(...)`, `$iter->adyacentes(...)`.
- `$iter->eliminar_adyacente($alias, $camino)`.
- `$iter->eliminar_adyacentes($camino)`.
- `$iter->nombre()`.
- `$iter->ocupado()`, `$iter->desocupar()`, `$iter->liberar()`,
  `$iter->destruir()`.

### 3.3 Alias y caminos

- `_alias($enlace, $alias)`, `enlace($alias)`, `alias($enlace)`,
  `eliminar_alias($alias)`, `_varios_alias($arr)`,
  `eliminar_todos_los_alias()`.
- Los **caminos** son cadenas con eslabones separados por `;`. Cada
  eslabón es un alias.
- Modificador `>` para múltiples avances: `"siguiente>3"`.
- Carácter `/` para escapar.

---

## 4. CLASE CONTROLADOR

Namespace: `Iteradores\Controlador\Controlador`

### 4.1 Inicialización

- `Controlador::inicializar()`: registra implementaciones de persistencia,
  comandos y comunicadores. Se llama una sola vez.
- `Controlador::registrar_implementacion($nombre, $clase)`: registra una
  clase que implementa `PerdurarSuperestructura`.
- `Controlador::establecer_metodo($metodo)`: cambia el método activo
  (`"SQL"`, `"JSON"`, `"XML"`, `"ESQL"`, etc.).

### 4.2 Persistencia

- `Controlador::guardar($nombre)`: guarda la superestructura actual con
  ese nombre. **Falla si hay nodos ocupados.**
- `Controlador::cargar($nombre)`: vacía la superestructura y carga la
  pedida. Devuelve `true` si se cargó, `false` si no existe, `null`
  si hubo error (conexión, query). Los llamadores deben distinguir
  "no existe" de "error".
- `Controlador::eliminar($nombre)`: elimina la superestructura guardada
  con ese nombre.
- `Controlador::existe($nombre)`: chequeo booleano.

### 4.3 Errores y alertas

- `Controlador::_error($mensaje)`: registra un error en el sistema
  centralizado heredado de `Objeto`.
- `Controlador::_alerta($mensaje)`: registra una alerta.
- `Controlador::imprimir_errores()`, `Controlador::imprimir_alertas()`.
- `Controlador::html_errores()`, `Controlador::html_alertas()`.

El sistema de errores y alertas está en la clase `Objeto`, que es la raíz
de la jerarquía. Guarda cada mensaje con la pila de llamadas. Tiene un
límite de profundidad configurable en `Conf`.

---

## 5. HELPERS DE ÁRBOL (`miscelaneas/Arbol.php`)

Estructura de lista simple con enlaces `hmi` (hijo más izquierdo), `hd`
(hermano derecho), `p` (padre).

**Funciones:**

- `_hmi($padre, $hijo)`: agrega hijo al inicio.
- `_hd($nodo_actual, $nuevo_hermano)`: agrega hermano derecho.
- `hmi($nodo)`: devuelve hijo más izquierdo.
- `hd($nodo)`: devuelve hermano derecho.
- `p($nodo)`: devuelve padre.
- `eliminar_hmi($padre)`: elimina el hijo más izquierdo y lo devuelve.
- `eliminar_hd($nodo_actual)`: elimina el hermano derecho y lo devuelve.

**IMPORTANTE:** Esta estructura es una **lista simple**, no circular. Para
listas **circulares** (asientos dentro de un piso, cupones) se usa un nodo
cabeza con enlace `primer` → primer elemento, y cada elemento tiene
`siguiente` que apunta al próximo (el último apunta de vuelta a la
cabeza). La comparación se hace por `->id()`, no por objeto.

---

## 6. PERSISTENCIA

### 6.1 Implementaciones disponibles

- **SQL** (`PerdurarSuperestructuraStringSQL`): método principal.
- **JSON** (`PerdurarSuperestructuraStringJSON`): respaldo.
- **XML** (`PerdurarSuperestructuraStringXML`): no usado en el piloto.
- **ESQL** (`PerdurarSuperestructuraElectricosStringSQL`): variante para
  nodos eléctricos, no usado en el piloto.

### 6.2 Cómo funciona `guardar`

- **Desde 1.5i.7 usa transacción**: `begin_transaction` → DELETE de
  nodos y adyacentes → INSERT por chunks → `commit`. Si algo falla,
  `rollback`. O se reemplaza el grafo entero, o no se toca nada.
- **Los INSERT se dividen en chunks de ~200 KB.** Esto evita superar
  `max_allowed_packet` de MySQL (que en XAMPP por defecto es 1 MB).
  Antes de 1.5i.7, un grafo grande podía crashear MySQL o dejar la
  tabla corrupta.
- **Cada query se chequea.** Si una falla, `_error` con el mensaje de
  MySQL y `rollback`. `guardar` devuelve `false` en ese caso.
- **No falla con consultas vacías.** Si un grafo solo tiene nodos
  especiales sin enlaces, los armadores de chunks devuelven array
  vacío y se saltea la ejecución.

### 6.3 Cómo funciona `cargar`

- Vacía la superestructura actual.
- Lee los nodos de la base. Los que tienen ID especial se recrean con
  `crear_con_dato_e_id`. Los que no, se crean con `crear_con_dato` y se
  arma un mapa de equivalencias entre IDs viejos y nuevos.
- Después lee los adyacentes y reconstruye los enlaces usando las
  equivalencias.
- **Desde 1.5i.7a:** la query de adyacentes se chequea (no se llama
  `fetch_assoc()` sobre `false`). Si falla, devuelve `null`.
- **Desde 1.5i.7a:** el nombre se escapa con `real_escape_string` en
  `cargar`, `existe` y `eliminar`.
- **Desde 1.5i.7a:** la conexión SQL se cierra en todos los
  early-returns (antes quedaban conexiones abiertas en algunos casos).

### 6.4 Grafos separados

El framework permite tener varios grafos con nombres distintos. Cada uno
es una superestructura independiente. En el piloto se usan dos:

- **Grafo de la aplicación**: datos visibles.
- **Grafo de credenciales**: credenciales y sesiones.

Ver `prompts/prompt_piloto.md` para el detalle.

### 6.5 JSON con consistencia de tipos

- Al guardar se fuerza string en `id` y referencias de adyacentes.
- Al cargar se castea `id` y referencias a string para archivos viejos.
- Se escribe con rutas absolutas basadas en `__DIR__`.
- **Escritura atómica desde 1.5i.7a:** se escribe a `.tmp` y después
  se renombra. Si el proceso muere a mitad, el `.json` original queda
  intacto.
- **Validación desde 1.5i.7a:** al cargar se chequea que exista la
  clave `nodos`. Si no, se devuelve `null` y se loguea error.
- **`listar()` desde 1.5i.7b:** chequea el resultado de `glob()`.
  Si falla, devuelve `null` y registra el error.

### 6.6 XML

`PerdurarSuperestructuraStringXML` sigue el mismo diseño que JSON,
con los mismos fixes aplicados desde 1.5i.7b:

- Escritura atómica (`.tmp` + `rename`).
- `cargar` valida que exista el nodo `<nodos>` antes de procesar.
- `cargar` no vacía dos veces (la vacía `Controlador::cargar`).
- `libxml_clear_errors()` después de parsear.
- `listar()` chequea el resultado de `glob()`.
- `cargar_desde_xml` (método público, no llamado por el
  `Controlador`) mantiene su `vaciar_superestructura` propio.

**XML no se usa en el piloto.** Los fixes están aplicados por
consistencia con JSON, para que el día que se use esté listo.

---

## 7. PATRONES DE CÓDIGO DEL FRAMEWORK

### 7.1 Iteración sobre los hijos de un contenedor

```php
$contenedor = $nodo->adyacente('hijos');
if ($contenedor) {
    foreach ($contenedor->adyacentes() as $enlace => $hijo) {
        $enlace = (string)$enlace; // por si es numérico
        // ... procesar $hijo
    }
}
```

### 7.2 Iteración sobre una lista tipo árbol (hmi/hd)

```php
$raiz = $nodo->adyacente('contenedor');
if ($raiz) {
    $actual = hmi($raiz);
    while ($actual) {
        // ... procesar $actual
        $actual = hd($actual);
    }
}
```

### 7.3 Iteración sobre una lista circular

```php
$cabeza = $piso->adyacente('asientos');
if ($cabeza) {
    $actual = $cabeza->adyacente('primer');
    while ($actual && $actual->id() !== $cabeza->id()) {
        // ... procesar $actual
        $actual = $actual->adyacente('siguiente');
    }
}
```

### 7.4 Eliminación progresiva de una lista

```php
while ($hijo = eliminar_hmi($contenedor)) {
    Nodo::eliminar($hijo);
}
$padre->eliminar_adyacente('contenedor');
Nodo::eliminar($contenedor);
```

### 7.5 Escribir un valor con fallback

```php
$nodo = $padre->adyacente('nombre');
if ($nodo) {
    $nodo->_dato($nuevo_valor);
} else {
    $padre->_adyacente_en(Nodo::crear_con_dato($nuevo_valor), 'nombre');
}
```

### 7.6 ID especial para referencias entre cargas

```php
$nodo = Nodo::crear_con_dato_e_id('usuarios', 'usuarios');
// El ID es "usuarios" y sobrevive a guardar/cargar.
// Nodo::nodo_por_id('usuarios') lo encuentra siempre.
```

### 7.7 Migración idempotente

```php
function migrar_xxx(): array {
    $res = ['procesados' => 0, 'migrados' => 0, 'ya_migrados' => 0];
    // Recorrer y migrar, salteando lo ya migrado.
    Controlador::guardar('NOMBRE_APP');
    return $res;
}
```

---

## 8. ERRORES COMUNES DEL FRAMEWORK

- **Claves numéricas como string.** PHP convierte strings numéricos a
  int al usarlos como clave de array. Forzar `(string)$clave` en los
  foreach.
- **Comparar nodos con `===`.** No funciona entre cargas del grafo.
  Comparar siempre por `->id()`.
- **Eliminar un nodo con referencias entrantes.** `Nodo::eliminar`
  devuelve `false`. Hay que desenlazar primero.
- **Usar `crear_con_dato` con arrays u objetos complejos.** El framework
  lo encapsula tal cual, pero al persistir y recargar puede perder
  información. Guardar como strings.
- **`adyacente()` devolviendo algo que no es un nodo.** No puede pasar,
  pero si pasa, es un bug del framework. Reportar.
- **`Nodo::nodo_por_id` sobre un ID especial que no existe.** Devuelve
  `null` y genera una alerta. Es normal, no un error.
- **Guardar con un nodo ocupado.** `Controlador::guardar` falla. Hay que
  desocupar antes.
- **`Controlador::cargar` devuelve `bool|null`.** `true` = cargó,
  `false` = no existe, `null` = error. No castear a bool sin
  distinguir los dos últimos casos.
- **Los IDs de nodos sin ID especial cambian entre cargas.** Si
  necesitás referenciarlos desde otro nodo persistido, usá ID especial.

---

## 9. CÓMO USAR EL FRAMEWORK CORRECTAMENTE

1. **Los datos se guardan como strings.** Números también. En el
   código, cuando leas, castealos si necesitás operar.
2. **Los enlaces se llaman con strings.** No uses números como nombres
   de enlace.
3. **Los nodos se comparan por `->id()`.** Nunca con `===`.
4. **Los nodos especiales van con `crear_con_id` o `crear_con_dato_e_id`.**
   Sirven para referenciarlos entre cargas.
5. **Antes de eliminar un nodo, desenlazá.** Empezá por las hojas.
6. **Cuando iteres sobre claves que pueden ser numéricas, castealas a
   string.**
7. **Los enlaces salientes siempre tienen un nombre único por nodo.**
   Si agregás dos con el mismo nombre sin `reemplazar=true`, falla.
8. **Guardá con `Controlador::guardar($nombre)`** (o el helper
   `guardar_ambos` si el proyecto lo define).
9. **Verificá que no haya nodos ocupados antes de guardar.**
10. **Los caminos del Iterador usan `;` como separador.** Escapá con
    `/` si un alias contiene ese carácter.

---

## 11. LIMITACIONES CONOCIDAS DEL FRAMEWORK

Esta sección documenta limitaciones estructurales del framework
que no son bugs, pero que condicionan su uso. Son candidatas a
mejora en futuras versiones del framework.

### 11.1 Carga y guardado del grafo entero

Toda operación de persistencia (`Controlador::cargar`,
`Controlador::guardar`) procesa el grafo completo. No existe hoy
un mecanismo para cargar o guardar **partes reducidas** del grafo
(por ejemplo, solo la rama de un dueño, solo los nodos de un viaje).

**Consecuencia:** el tiempo de cada operación crece linealmente con
la cantidad total de nodos. Los listados, las altas y las bajas
también, porque iteran sobre el grafo completo.

**Caso testigo:** el piloto llegó a un grafo de ~10.000 nodos, con
tiempos de 50-70s por operación. Con ~2.000 nodos, los mismos
tiempos bajaron a 15-18s. La performance depende directamente del
tamaño total del grafo.

**Posibles direcciones (a discutir):**

- Guardar en cada nodo un "ID especial de origen" o similar, para
  reconstruir sub-grafos.
  - **Problema:** un nodo puede estar referenciado desde más de un
    lado. No hay un árbol natural de pertenencia. Requiere revisar
    teoría de grafos (componentes conexas, sub-grafos inducidos).
- Persistir por partes usando índices auxiliares.
- Snapshot por rama con marca de "raíz".
- **Índice de contexto por producto de primos (idea
  anotada, sin implementar).** Asignar a cada raíz
  especial (nodo con ID no numérico) un número primo
  distinto. Guardar en cada nodo un campo — columna en
  la tabla `nodos` — con el producto de los primos de
  todas las raíces desde las que el nodo es alcanzable.
  Como la factorización en primos es única (teorema
  fundamental de la aritmética), el producto identifica
  exactamente el conjunto de contextos a los que
  pertenece el nodo. Para cargar solo el contexto de una
  raíz R, filtrar `WHERE contexto % primo_R = 0`. Un
  nodo alcanzable desde varias raíces queda con el
  producto de sus primos.
  - **Ventaja:** un único índice numérico reemplaza a
    una tabla de pertenencias. Filtrado con una sola
    condición aritmética.
  - **Límite:** el producto crece rápido. Con N raíces,
    si un nodo es alcanzable desde muchas, el producto
    puede overflow. En la práctica el piloto tiene 2
    raíces (`usuarios`, `sesiones`); con 5-10 raíces el
    producto de los primeros primos entra en un
    `BIGINT` sin problema.
  - **Mantenimiento:** cada vez que se agrega un enlace,
    hay que propagar el producto por el grafo (un nodo
    nuevo hereda el producto de su padre; si el nodo ya
    existía y suma un contexto, se multiplica el primo).
    Requiere diseñar la propagación incremental.
  - **Interacción con el framework:** habría que
    modificar `PerdurarSuperestructuraStringSQL` para
    agregar la columna, calcular el producto al guardar
    y usarlo al cargar con un filtro. Es un cambio de
    la implementación de persistencia, no de la API.

Requiere una sesión del framework, no del piloto.

### 11.2 Fuga de nodos huérfanos

`Nodo::eliminar($nodo)` falla si el nodo tiene referencias
entrantes. La forma correcta de eliminarlo es desenlazar todas las
referencias entrantes antes, de a una, empezando por las hojas.

**Consecuencia:** si el código que elimina entidades no hace este
desenlazado progresivo, los nodos quedan **huérfanos**: ya no se
alcanzan desde ninguna raíz, pero siguen ocupando memoria y disco.
No hay recolección automática de basura.

**Caso testigo:** el piloto acumuló miles de nodos huérfanos
(asientos de ventas canceladas, cupones, nodos de pasajeros
borrados, etc.). El grafo creció de 2.000 a 10.000 nodos. Afectó la
performance global.

**Posibles direcciones (a discutir):**

- Extender el framework con un garbage collector que recorra el
  grafo y libere nodos no alcanzables desde raíces especiales.
- Documentar patrones de "eliminación progresiva" como el de §7.4.
- Proveer helpers de "desenlazado en cascada" para el caso común.

### 11.3 Iteradores persistentes subutilizados

El framework tiene Iteradores con posición persistente entre
operaciones. Son una herramienta para reducir recorridos repetidos
sobre el grafo (por ejemplo, mantener un puntero a "última venta
creada" o "último viaje activo").

En la práctica, el piloto rara vez los usa: la mayoría de las
operaciones abren un nuevo recorrido desde las raíces.

**Posible mejora:** usar iteradores persistentes en los flujos de
lectura frecuente para reducir el costo O(N) por operación.
Requiere diseñar qué iteradores conviene mantener y dónde
persistirlos.

### 11.4 Contextos y carga parcial (plan, en desarrollo)

Esta sección documenta el **plan** para resolver §11.1
(carga parcial del grafo) usando **contextos**. Todavía
no está implementado. Se avanza por fases y se
actualiza este bloque a medida que cada fase se cierra.

**Definición de contexto.** Un **contexto** es un ID
especial del grafo (nodo con ID no numérico) que actúa
como raíz. Cualquier nodo alcanzable desde ese ID
especial pertenece a ese contexto. El framework no
distingue la semántica de un contexto (usuarios,
sesiones, tipos, dueños, etc.): todos son contextos por
igual. Esta abstracción es la clave del diseño: el
framework solo entiende "contextos".

**Representación.** Cada nodo lleva un `contexto_mask`:
un entero donde cada bit representa un contexto.
Bit 0 = contexto #1, bit 1 = contexto #2, etc. Un nodo
puede pertenecer a varios contextos a la vez (bits
múltiples en 1).

**Cálculo.** Al guardar, BFS multi-fuente desde todos los
IDs especiales. Cada nodo acumula el conjunto de
contextos alcanzantes; el conjunto se convierte a
bitmask. Al cargar, el bitmask se asigna al nodo en
memoria.

**Método `SQL64` (fase 1, a implementar).** Misma base
de datos que `SQL`, pero usa **tres tablas nuevas**
(las tablas `nodo` y `adyacente` de SQL quedan
intactas, así los dos métodos coexisten sin pisarse):

```sql
CREATE TABLE nodo_contexto (
  idsuperestructura VARCHAR(50) NOT NULL,
  idnodo            VARCHAR(50) NOT NULL,
  dato              BLOB,
  contexto_mask     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (idsuperestructura, idnodo),
  INDEX idx_nodo_contexto_super_ctx (idsuperestructura, contexto_mask)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE adyacente_contexto (
  idsuperestructura VARCHAR(50) NOT NULL,
  idnodo            VARCHAR(50) NOT NULL,
  enlace            VARCHAR(100) NOT NULL,
  idadyacente       VARCHAR(50) NOT NULL,
  PRIMARY KEY (idsuperestructura, idnodo, enlace, idadyacente)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE contexto (
  idsuperestructura VARCHAR(50) NOT NULL,
  bit               TINYINT UNSIGNED NOT NULL,
  nombre            VARCHAR(100) NOT NULL,
  PRIMARY KEY (idsuperestructura, bit),
  UNIQUE KEY uq_ctx_nombre (idsuperestructura, nombre)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

La tabla `contexto` mapea bit ↔ nombre del ID especial.
Los bits se asignan por orden alfabético de los IDs
especiales en el primer `guardar`; los contextos nuevos
van al próximo bit libre. Los contextos que desaparecen
no liberan su bit (histórico).

**Interfaz.** Se crea
`PerdurarSuperestructuraConContexto extends PerdurarSuperestructura`
con tres métodos nuevos:

- `cargar_parcial($nombre, array $contextos)`
- `guardar_parcial($nombre, array $contextos)`
- `listar_contextos($nombre)`

La interfaz recibe **nombres de contextos** (IDs
especiales), no bitmasks. El bitmask es un detalle de
implementación. La retrocompatibilidad está garantizada:
los métodos que no implementan la nueva interfaz siguen
funcionando.

**Fases.**

- **Fase 1**: `PerdurarSuperestructuraStringSQL64` (PHP).
  Comportamiento: `guardar` calcula y persiste el
  bitmask; `cargar` lee todo; `cargar_parcial` filtra
  con `WHERE contexto_mask & $mask != 0`. Los enlaces a
  nodos fuera del filtro se descartan (silencioso).
  `guardar_parcial` queda como stub que devuelve error.
  El `Controlador` marca la superestructura como parcial
  y `guardar` falla si se intenta guardar un grafo
  parcial sin usar `guardar_parcial`.
- **Fase 2**: `PerdurarSuperestructuraStringIndexedDB64.js`
  (JS). Espejo de fase 1.
- **Fase 3**: `PerdurarSuperestructuraStringJSON64.php` y
  `PerdurarSuperestructuraStringXML64.php`. Estos
  formatos agregan `guardar_parcial` escribiendo a un
  archivo separado. Regla: solo se puede cargar un
  subconjunto que fue guardado previamente.
- **Fase 4** (opcional): implementar `guardar_parcial`
  real en SQL64 e IndexedDB64. Requiere merge del
  subgrafo en memoria con el persistido sin pisar
  contextos fuera de la operación.
- **Fase 5** (si hace falta): extender a 256 contextos
  (bitmask de `BINARY(32)`) o al producto de primos
  (ver discusión en §11.1).

**Límite fase 1-4.** 64 contextos por superestructura.
Alcanza para el piloto hoy y para varios años.

**Extrapolación a los métodos de persistencia.**
Solo SQL e IndexedDB ganan con el bitmask. JSON y XML
se adaptan por consistencia (el bitmask permite decidir
qué escribir en el archivo parcial, pero la carga sigue
siendo total o de un archivo ya guardado).

---

## 10. HISTORIAL DEL FRAMEWORK

- **1.5i.4**: versión base del framework al cierre de v67.
- **1.5i.5**: fix para que `guardar` no falle con consultas SQL vacías.
- **1.5i.6**: fix real del anterior: los armadores de consulta devuelven
  string vacío cuando no hay filas.
- **1.5i.7**: `guardar` usa transacción y divide los INSERT en chunks
  de ~200 KB. Chequea el resultado de cada query. Fix del bug que
  crasheaba la tabla cuando el grafo superaba `max_allowed_packet`.
- **1.5i.7a**: `cargar` chequea el resultado de la query de adyacentes
  (no llama `fetch_assoc()` sobre `false`). `real_escape_string` en
  `cargar`, `existe` y `eliminar`. Conexiones SQL se cierran en
  todos los early-returns. JSON con escritura atómica (`.tmp` +
  `rename`) y validación de la clave `nodos`. `Controlador::cargar`
  devuelve `bool|null` para distinguir "no existe" de "error".
- **1.5i.7b**: XML recibe los mismos fixes que JSON: escritura
  atómica, validación de `<nodos>`, `libxml_clear_errors`,
  sin doble vaciado en `cargar`, `listar()` chequea `glob()`.
  `PerdurarSuperestructuraStringJSON::listar()` también chequea
  `glob()`. ESQL queda pendiente.
- **1.5i.7c**: sin cambios funcionales al framework PHP. Se agrega
  `Pruebas/prueba_deposito.php` para verificar que el depósito de
  IDs se limpia correctamente al vaciar la superestructura. Se
  documenta el espejo JS en la sección 12 de este prompt.
- **1.5i.7d**: `PerdurarSuperestructuraStringSQL::crear_chunks_insertar_adyacentes`
  usa `adyacentes()` en lugar de `por_cada_adyacente_ejecutar`, para
  no emitir una alerta por cada nodo sin adyacentes (alineado con el
  espejo JS). Reescritura de `Pruebas/prueba_deposito.php` con un
  test bien diseñado: el anterior daba un falso positivo porque
  intentaba crear el mismo ID especial después de `cargar` (que
  reinserta el ID al recrear el nodo). Ahora verifica directamente
  `vaciar_superestructura`.
- **1.5i.7e**: sin cambios funcionales al framework. Documentación:
  en la sección 12 del prompt se aclaran los cambios del PHP que no
  tienen análogo en JS (libxml, `glob()`, `real_escape_string`), y
  se documenta que el bug latente `if ($elemento)` de `Iterador`
  existe en AMBOS espejos (PHP y JS).
- **1.5i.7f**: fix del bug latente `if ($elemento)` en `Iterador.php`.
  Los 4 usos (en `crear_interno`, `cargar_interno` y
  `iterador_interno` dos veces) se cambian por `if ($elemento !==
  null)`. Así los valores falsy (`0`, `''`, `false`) ya no se
  descartan. Mismo fix aplicado al espejo JS (V1.5i.7f).
- **1.5i.7g**: sin cambios funcionales al framework. Se agrega la
  sección 11 "Limitaciones conocidas del framework" con tres
  puntos: (a) toda operación procesa el grafo completo (sin
  carga parcial); (b) los nodos huérfanos se acumulan porque
  `Nodo::eliminar` falla con referencias entrantes y no hay
  recolección automática; (c) los iteradores persistentes están
  subutilizados. Estas limitaciones se descubrieron trabajando
  en el piloto: llegó a 10.000 nodos con tiempos de 50-70s por
  operación; con 2.000 nodos, 15-18s.
- **1.5i.7h**: separación de la configuración del framework
  de la del piloto. `Configuracion.php` (framework) se
  queda solo con las constantes propias del framework;
  las constantes del piloto (`NOMBRE_APP`,
  `NOMBRE_APP_CREDENCIALES`, `VERSION_APP`, `AUTOR_APP`,
  `PREFIJO_SESSION`, `INTENTOS_MAXIMOS_AUTENTICACION`,
  `BLOQUEO_AUTENTICACION_SEGUNDOS`, `HASH_DUMMY_AUTENTICACION`,
  `NOMBRE_ADMIN`) se mueven al nuevo
  `Aplicacion/ConfiguracionApli.php` (extends `Conf`).
  `Entorno.php` recibe `establecer_prefijo_sesion()` y
  `prefijo_sesion()` para desacoplarse del `PREFIJO_SESSION`
  del piloto. Refactor masivo en 12 archivos del piloto:
  `Conf::X` → `ConfiguracionApli::X`. Espejado en JS:
  `Aplicacion/ConfiguracionApli.js` reemplaza a
  `ConfPlugin.js` y aplica los valores al `Conf` del
  framework vía `configurar_conf(Conf)`.
- **1.5i.7i**: comando `grafo:eliminar_huerfanos` en el
  `Controlador`. Elimina todos los nodos no alcanzables
  desde las raíces. Desenlaza las salientes entre
  huérfanos antes de destruirlos. Sin chequeo de
  `es_pruebas`: el enrutador del piloto decide cuándo
  exponerlo (admin/soporte). Se espeja el módulo grafo
  completo al `Controlador` JS (los comandos `grafo:*`
  existían solo en PHP desde 1.5i.7g-pre; ahora los
  cuatro + helpers privados están en ambos espejos).

- **1.5i.7j**: solo documentación. Se agrega la sección
  §11.4 con el plan de contextos y carga parcial. No hay
  cambios funcionales todavía; el plan se implementará por
  fases (SQL64, luego IndexedDB64, luego JSON64/XML64, y
  eventualmente 256 bits y producto de primos). Ver §11.4.

El espejo JS también recibió mejoras en paralelo (ver sección 12).
Su historial es: 1.5i.4 → 1.5i.5 (robustez de persistencia)
→ 1.5i.6 (fix del depósito de IDs) → 1.5i.7 (alineación con PHP).

El framework en sí no cambia mucho. La mayoría de los cambios son en el
piloto.

---

## 12. ESPEJO EN JAVASCRIPT

El framework Iteradores tiene un espejo en JavaScript para navegador.
Vive en un proyecto separado (por ejemplo, `iteradoresJS/`) con la misma
estructura de carpetas y las mismas clases, pero adaptado al entorno
navegador.

### 12.1 Qué cambia respecto al PHP

- **Persistencia principal:** IndexedDB (`PerdurarSuperestructuraStringIndexedDB`).
  No hay SQL. JSON y XML descargan/cargan archivos vía interacción del
  usuario.
- **Sin acceso al filesystem:** no se puede escribir atómicamente con
  `.tmp` + `rename` como en PHP; el navegador genera el Blob completo o
  no lo genera.
- **Campos privados:** JS tiene `#privados` reales, más estrictos que los
  `private` de PHP. Un `#privado` de una clase base NO es accesible desde
  una subclase.
- **Métodos async:** IndexedDB es asíncrono. `Controlador.delegar`,
  `Controlador.guardar`, `Controlador.cargar`, `Controlador.existe`,
  `Controlador.eliminar` y `Controlador.ejecutar_prueba` son `async`.

**Cambios del PHP sin análogo en JS.** No son gaps pendientes,
son diferencias de plataforma:

- `libxml_clear_errors()` y `libxml_use_internal_errors()`: JS no
  tiene libxml. El parseo XML usa `DOMParser`, que no deja estado
  acumulado entre llamadas.
- `listar()` sobre JSON/XML: no aplica. El navegador no da acceso al
  filesystem, así que no hay carpeta que listar. Los archivos se
  descargan/cargan uno por uno vía interacción del usuario.
- `real_escape_string` y todo lo relacionado con `mysqli`: no aplica.
  No hay motor SQL. IndexedDB es un object store, no una base
  relacional.

### 12.2 Persistencia en IndexedDB

IndexedDB tiene dos object stores: `nodos` y `adyacentes`. Cada uno
indexado por `idsuperestructura`.

**Guardar** se hace en **una sola transacción atómica**:
1. `db.transaction([nodos, adyacentes], 'readwrite')`
2. Recorrer los cursores del índice `idsuperestructura` y borrar los
   registros con ese nombre.
3. Cuando ambos cursores terminan, encolar los INSERT (`add`) en la
   misma transacción.
4. `tx.oncomplete` → commit. `tx.onerror`/`tx.onabort` → rollback.

Si algo falla, la transacción se aborta y los datos previos quedan
intactos. Es el equivalente JS de `begin_transaction/commit/rollback`
en SQL.

**Error que esto evita:** antes el DELETE y los INSERT iban en
transacciones separadas. Un fallo a mitad dejaba el grafo a medio
pisar. Igual que el bug de SQL pre-1.5i.7.

**Cierre de conexión:** `db.close()` en `finally` en `guardar`,
`cargar`, `existe` y `eliminar`.

**Datos como strings:** los IDs y datos se guardan como strings, igual
que en PHP. `String(id)`, `String(dato)` (o `''` si es null/undefined).

### 12.3 Trampas PHP ↔ JS

**Campos privados en la clase base.** En PHP, `private static
$deposito_de_ids` en `Objeto` es accesible desde la propia clase (por
ejemplo, desde un método `limpiar_ids_especiales()`). En JS,
`#deposito_de_ids` es accesible solo desde la clase `Objeto`.

**Regla:** si un campo privado tiene que ser limpiado desde una
subclase o desde otra clase, **la clase dueña del campo debe exponer
un método público**. En PHP:
`Objeto::limpiar_ids_especiales()` (limpia solo los especiales). En JS:
`Objeto.limpiar_deposito_ids()` (limpia solo los especiales, alineado
con PHP desde V1.5i.7).

**Nunca acceder a un `#privado` desde otra clase.** Aunque el
traductor de PHP a JS lo haga "por analogía", no funciona. Si en el
código original PHP hay `typeof $this->campo !== 'undefined'` para
verificar un campo privado de otra clase, en JS ese chequeo siempre
es `false`.

**`if (elemento)` descarta falsy.** `0`, `''`, `false` son falsy en
ambos lenguajes. Este bug existe en los DOS espejos, en los mismos
métodos de `Iterador`:

- PHP: `Iterador::crear_interno`, `Iterador::cargar_interno` e
  `Iterador::iterador_interno` usan `if ($elemento)`.
- JS: `Iterador._crear_interno`, `Iterador._cargar_interno` e
  `Iterador._iterador_interno` usan `if (elemento)`.

En todos los casos, si el elemento inicial es `0`, `''` o `false`,
el iterador se crea/carga sin posición actual. Es un bug latente
(nadie inicializa iteradores con esos valores en la práctica), pero
real. El fix es:

- PHP: `if ($elemento !== null)`
- JS: `if (elemento !== null && elemento !== undefined)`

**Resuelto** en v73r (PHP) y V1.5i.7f (JS).

### 12.4 API del Controlador JS

`Controlador.cargar(nombre)` devuelve una promesa que resuelve a:
- `true`: cargó.
- `false`: no existe.
- `null`: error (conexión, query, transacción abortada).

`Controlador.guardar(nombre)`, `Controlador.existe(nombre)` y
`Controlador.eliminar(nombre)` también son `async`.

`Controlador.ejecutar_prueba(callback)` es `async` y espera al
callback. Si el callback es `async`, se resuelve cuando el callback
termina. Antes no esperaba, y los tests imprimían "finalizado" antes
de que terminara el trabajo.

### 12.5 Estado del espejo JS

Versiones recientes del espejo JS:
- **1.5i.4**: base.
- **1.5i.5**: robustez de persistencia (transacción atómica en
  IndexedDB, casteo a string, `db.close()` en `finally`, `delegar`
  async con validación, `existe` y `eliminar` async).
- **1.5i.6**: fix del depósito de IDs (`Objeto.limpiar_deposito_ids`).
- **1.5i.7**: alineación con PHP (`limpiar_deposito_ids` borra solo
  especiales), test con comparación string/number, silencio de
  alertas en `#crear_datos_insertar_adyacentes`.
- **1.5i.7f**: fix del bug latente `if (elemento)` en
  `_crear_interno`, `_cargar_interno` y `_iterador_interno`
  (dos veces). Espejo del fix PHP 1.5i.7f.
- **1.5i.7h**: separación `ConfiguracionApli` (ver
  1.5i.7h del historial). `Configuracion.js` del
  framework se queda solo con las constantes del
  framework; `ConfiguracionApli.js` (antes
  `ConfPlugin.js`) define las del plugin y aplica los
  valores al `Conf` del framework vía
  `configurar_conf(Conf)`. `Entorno.js` recibe
  `_prefijo_sesion`, `establecer_prefijo_sesion()` y
  `prefijo_sesion()`.
- **1.5i.7i**: espejado del módulo grafo completo
  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`,
  `grafo:eliminar_huerfanos` + helpers privados
  `_grafo_cargar_estructura`, `_grafo_bfs_desde_raices`,
  `_grafo_inferir_tipo`). En JS,
  `Nodo.por_cada_nodo_ejecutar` devuelve un objeto
  plano `{id: resultado}`, no un `Map`; los helpers
  del Controlador lo normalizan a un objeto
  `{id: {dato, ady}}` para el resto del módulo.

Cualquier cambio al framework PHP que toque la API compartida debe
reflejarse también en el espejo JS.

---

## 13. APRENDIZAJES A LA FUERZA (FRAMEWORK)

Lecciones acumuladas en el desarrollo y corrección del framework.
Cada una costó un bug en producción o en pruebas.

**Persistencia:**

1. **Todo `guardar` sobre SQL debe ser transaccional.** `begin_transaction`
   → DELETE → INSERT por chunks → `commit`. Si algo falla, `rollback`.
   Sin esto, un grafo grande pisa el anterior a medias.
2. **Dividir los INSERT en chunks.** `max_allowed_packet` en XAMPP
   es 1 MB. Chunks de ~200 KB. Sin esto, MySQL crashea la tabla.
3. **Nunca guardar vacío.** Si la superestructura no tiene nodos,
   `guardar_ambos` aborta. Guardar vacío pisa el grafo bueno.
4. **Los IDs numéricos cambian entre cargas.** Si un nodo debe
   sobrevivir, usar ID especial (`crear_con_id` o
   `crear_con_dato_e_id`).
5. **Los datos se guardan como strings.** Números también.
6. **Cuidado con `cargar` + `vaciar_superestructura`.** Si se
   vacía dos veces (una en `Controlador::cargar` y otra en la
   implementación), y algo falla entre medio, queda vacío.
7. **La transacción de IndexedDB (JS) reemplaza a la de SQL.**
   DELETE + INSERT en la misma `db.transaction`, resolver en
   `tx.oncomplete`, rechazar en `tx.onerror`/`onabort`.
8. **Escritura atómica en JSON/XML:** `.tmp` + `rename`. El
   navegador no puede; PHP sí.

**Campos privados y visibilidad:**

9. **PHP `private static` es accesible desde la propia clase.**
   Un método público dentro de la misma clase puede limpiar el
   campo. Es la solución correcta.
10. **JS `#privado` no es accesible desde subclases.** Si una
    subclase necesita operar sobre el campo, la clase base debe
    exponer un método público.
11. **Cuidado al traducir PHP → JS:** un bloque que usa
    `typeof $this->campo !== 'undefined'` desde otra clase
    siempre da `false` en JS. Bug histórico del depósito de IDs.

**Nodos y enlaces:**

12. **Todos los enlaces son strings.** Los números se convierten.
13. **Comparar nodos por `->id()`.** Nunca con `===`.
14. **Antes de eliminar un nodo, desenlazar.** `Nodo::eliminar`
    falla si hay referencias entrantes.
15. **`Nodo::nodo_por_id` alerta si no encuentra.** El patrón
    "buscar y crear si no existe" debería usar `Nodo::existe`
    para evitar ruido.
16. **PHP convierte claves de array string numéricas a int.**
    Castear a string al iterar.

**Iterador:**

17. **`if ($elemento)` descarta falsy (`0`, `""`, `false`).**
    Usar `!== null` cuando el valor puede ser falsy legítimo.
    Bug latente arreglado en framework 1.5i.7f.

**Controlador y async (JS):**

18. **`delegar` debe esperar a las implementaciones async.**
    `await` funciona tanto si la implementación es sync como
    async.
19. **`ejecutar_prueba` debe esperar al callback.** Si no, los
    tests imprimen "finalizado" antes de que termine el trabajo.
20. **`db.close()` en `finally`.** Las conexiones abiertas se
    acumulan hasta que el navegador las recolecte.

**Limitaciones estructurales (documentadas en v1.5i.7g, ver §11):**

21. **Toda operación procesa el grafo completo.** No hay carga
    parcial. El costo crece con N. Caso testigo: piloto con
    10.000 nodos → 50-70s por operación; con 2.000 nodos,
    15-18s.
22. **Los nodos huérfanos se acumulan.** `Nodo::eliminar` falla
    con referencias entrantes. Si el llamador no desenlaza
    progresivamente, los nodos quedan huérfanos sin recolección
    automática. El framework no incluye garbage collector.
23. **Los iteradores persistentes están subutilizados.** Pueden
    reducir recorridos repetidos. En la práctica, la mayoría de
    las operaciones abren un nuevo recorrido desde las raíces.

**Regla de oro:** cualquier cambio al framework PHP se refleja en
JS en la misma tanda, con dos scripts y dos commits.

---

## 14. CIERRE

Este prompt es autocontenido sobre el framework. Con esta información más
el `prompt_piloto.md` y el `prompt_sistema_scripts.md` podés retomar el
trabajo.

**Recordá:**

- Este archivo se actualiza solo cuando cambia el framework.
- Todo lo específico del piloto vive en `prompt_piloto.md`.
- No escribas código de una. Consensuá el plan primero.
- Pedí los archivos actuales antes de tocarlos.

---

**FIN DEL PROMPT**