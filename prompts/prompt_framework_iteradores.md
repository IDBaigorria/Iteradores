# Prompt de trabajo — Framework Iteradores

Este es un prompt autocontenido con todo lo que sabemos sobre el framework
**Iteradores**. Vive en el propio proyecto, en
`prompts/prompt_framework_iteradores.md`, y se pega al principio de la
conversación junto con `prompts/prompt_piloto.md` y
`prompts/prompt_sistema_scripts.md`.

Se actualiza cuando cambia el framework. No incluye nada específico del
piloto: eso vive en `prompts/prompt_piloto.md`.

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
  pedida. Si el nombre no existe, deja la superestructura vacía y
  devuelve `false`.
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

- Recorre todos los nodos de la superestructura y arma dos consultas SQL:
  una para nodos, otra para adyacentes.
- **No falla con consultas vacías.** Si un grafo solo tiene nodos
  especiales sin enlaces, los armadores de consulta devuelven string
  vacío y se saltea la ejecución.

### 6.3 Cómo funciona `cargar`

- Vacía la superestructura actual.
- Lee los nodos de la base. Los que tienen ID especial se recrean con
  `crear_con_dato_e_id`. Los que no, se crean con `crear_con_dato` y se
  arma un mapa de equivalencias entre IDs viejos y nuevos.
- Después lee los adyacentes y reconstruye los enlaces usando las
  equivalencias.

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

## 10. HISTORIAL DEL FRAMEWORK

- **1.5i.4**: versión base del framework al cierre de v67.
- **1.5i.5**: fix para que `guardar` no falle con consultas SQL vacías.
- **1.5i.6**: fix real del anterior: los armadores de consulta devuelven
  string vacío cuando no hay filas.

El framework en sí no cambia mucho. La mayoría de los cambios son en el
piloto.

---

## 11. CIERRE

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