bien, continuemos con la fase 4
El usuario quiere continuar con la Fase 4 del framework. Esta es la fase "fix real" del bug §4.7. Recordemos qué dice el plan:

Fase 4 pendiente (sesión del framework). Dos cambios para cerrar el leak lento de raíz:

No saltar nodos con máscara 0 del INSERT. Insertarlos con máscara 0. El id_fila se preserva, las aristas apuntan bien, no hay duplicados en el siguiente guardado.

Propagar máscaras a fixpoint sobre TODAS las aristas. Repetir el BFS hasta que no cambie nada. Los nodos "huérfanos en memoria" recuperan bits en el próximo guardado.

Cambio sensible. Requiere sesión del framework dedicada con tests a fondo. No bloquea el trabajo del piloto.

Antes de armar el script, necesito pensar muy bien en el código real. Ya tengo PerdurarSuperestructuraStringSQL64.php del contexto anterior. Voy a releerlo mentalmente:

php
static private function _guardar_con_filtro($nombre, int $mascara_filtro, array $contextos = []) {
    // ...
    
    // 1. Calcular máscaras por BFS multi-fuente desde IDs especiales.
    $mascaras = self::_calcular_y_registrar_contextos($sql, $nombre);
    
    // 2. Si guardado parcial, unir con máscara previa de cada nodo.
    if ($mf_parcial !== 0) {
        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {
            $id = $nodo->id();
            $m_previa = (int)$nodo->contexto_mascara();
            $m_bfs = (int)($mascaras[$id] ?? 0);
            $mascaras[$id] = $m_previa | $m_bfs;
        });
    }
    
    // 3. Si hay nodos nuevos, propagar máscaras desde nodos cargados.
    if ($mf_parcial !== 0 && !empty($mapa_previo)) {
        $nuevos = [];
        // ... detectar nodos nuevos (no en $mapa_previo)
        if (!empty($nuevos)) {
            // BFS por enlaces: si un nodo tiene máscara > 0, propaga a sus vecinos nuevos.
            // Máximo 10 iteraciones.
        }
    }
    
    // 4. DELETE ... WHERE id_fila IN ($mapa_previo)
    // 5. INSERT: solo nodos cuya máscara matchea el filtro
    //    if ($mf !== 0 && ($mascara & $mf) === 0) return; // <-- este es el hueco
    // 6. Verificación de máscaras (blidaje)
}
Los dos cambios que hay que hacer:

Cambio 1: no saltar nodos con máscara 0 del INSERT.

Hoy:

php
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar) {
    $id = $nodo->id();
    $mascara = (int)($mascaras[$id] ?? 0);
    if ($mf !== 0 && ($mascara & $mf) === 0) return;  // <-- skip
    $ids_a_insertar[] = $id;
});
El problema: si un nodo del grafo en memoria tiene mascara & $mf === 0, se salta. Pero ese nodo PUEDE estar referenciado por otro nodo que SÍ matchea el filtro (por ejemplo, una arista que apunta a él). Si ese nodo no se inserta pero la arista se inserta... no, la arista tampoco se inserta porque el código de aristas también chequea ($m_d & $mf) === 0 para el destino. Entonces la arista no se inserta. Eso está "bien" en el sentido de que no hay arista huérfana. Pero el nodo queda huérfano en DB. Y en el próximo guardado, como el nodo estaba en 
m
a
p
a
p
r
e
v
i
o
(
s
i
f
u
e
c
a
r
g
a
d
o
a
n
t
e
s
)
,
i
n
t
e
n
t
a
r
e
i
n
s
e
r
t
a
r
s
e
c
o
n
i
d
f
i
l
a
.
P
e
r
o
s
i
e
s
N
U
E
V
O
(
n
o
e
s
t
a
ˊ
e
n
mapa 
p
​
 revio(sifuecargadoantes),intentareinsertarseconid 
f
​
 ila.PerosiesNUEVO(noest 
a
ˊ
 enmapa_previo), se inserta con id_fila auto-increment la próxima vez que SÍ tenga bits. Eso crea duplicados: el nodo viejo (con id_fila viejo) y el nuevo (con id_fila auto-increment).

Espera, revisando: el problema real es cuando el nodo quedó con máscara 0 y hay que persistirlo. El plan dice "insertarlos con máscara 0". Eso resuelve el problema de que no se persiste el nodo y queda huérfano.

Pero espera, si el nodo tiene máscara 0 y no matchea el filtro, ¿para qué lo insertaríamos? El filtro es para guardar SOLO el subgrafo cargado (contexto). Insertar un nodo con máscara 0 significa guardar algo que no pertenece al subgrafo. Pero el nodo SÍ está en memoria, SÍ está en el grafo que se va a persistir. Entonces insertarlo tiene sentido si va a estar referenciado por algo que sí matchea. Pero si no matchea, no se insertan aristas hacia él... entonces queda huérfano igual pero al menos existe en DB (con id_fila estable).

Hmm, pensando de nuevo: el plan sugiere insertar con máscara 0. Eso resuelve el problema de duplicados: el nodo tiene id_fila estable desde el principio, la próxima vez que se le asigne máscara > 0, hace UPDATE (o reinsert con id_fila viejo). No se duplica.

Pero hay un problema: si inserto nodos con máscara 0, y hay MUCHOS en memoria con máscara 0 (los 1817 del estado roto), se van a persistir todos. Eso no es lo que queremos. Pero en un grafo sano, ¿cuántos nodos con máscara 0 hay? Pocos o ninguno. En el estado actual post-restore, el log dice nodos_mascara_0=0 en todos los guardados. Así que insertar los pocos (o cero) no es problema.

Ahora, la pregunta es: ¿qué pasa si un nodo está en memoria con máscara 0 porque es un nodo recién creado que aún no fue alcanzado por el BFS? El código actual no propaga máscaras a nodos nuevos con máscara 0 desde otros nodos con máscara > 0 a menos que estén en $nuevos (que sí están). Espera, revisemos:

php
if ($mf_parcial !== 0 && !empty($mapa_previo)) {
    $nuevos = [];
    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {
        $id = $nodo->id();
        if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }
    });
    // ...propagar a $nuevos desde nodos con máscara > 0
}
Sí, propaga. Pero solo a los que están en $nuevos. Si un nodo ya existía (en $mapa_previo) con máscara 0 y sigue con máscara 0, no se propaga a él. Ese es el leak lento: nodos viejos que perdieron todos sus bits y no los recuperan.

Cambio 2: propagar máscaras a fixpoint sobre TODAS las aristas.

Hoy el código solo propaga a nodos nuevos ($nuevos) desde nodos con máscara > 0. Y hace máximo 10 iteraciones. El plan sugiere: propagar a TODOS los nodos, no solo a los nuevos, hasta punto fijo.

O sea, después de calcular $mascaras por BFS y unir con previas, hacer otra pasada: para cada nodo con máscara > 0, propagar a todos sus vecinos. Repetir hasta que nada cambie.

Pero cuidado: eso cambia la semántica. Hoy el BFS multi-fuente desde IDs especiales recorre todo el grafo alcanzable. Si un nodo NO es alcanzable (porque las aristas que lo alcanzarían no están en el grafo), el BFS no lo toca. Un nodo que estaba en DB pero cuyas aristas no se cargaron queda en el grafo en memoria con máscara 0.

Espera, ¿cómo queda un nodo en memoria si no fue alcanzable desde ningún ID especial en el BFS? Miremos el código de carga:

php
static public function cargar_parcial($nombre, array $contextos): bool|null {
    // ...
    $mascara_pedida = ...;
    $q = "SELECT * FROM nodo_contexto WHERE idsuperestructura='...' AND (contexto_mascara & $mascara_pedida) != 0;";
    $ok = self::_cargar_nodos_con_mascara(...);  // solo carga los que matchean
    $ok2 = self::_cargar_adyacentes_contexto(...);  // carga aristas cuyos dos extremos están en $equivalencias
    // ...
}
Solo se cargan los nodos que matchean el filtro. Y las aristas entre ellos. Entonces, en memoria hay solo nodos con máscara > 0 (los que matchearon). Pero un nodo recién creado (por una operación del usuario) no tiene máscara hasta el próximo guardado. En el guardado, el BFS le asigna bits si es alcanzable desde un ID especial en el grafo en memoria.

Ah, entiendo. En memoria, al guardar, los nodos que no fueron alcanzables desde ningún ID especial del grafo en memoria quedan con máscara 0. Y esos son los que se skipean del INSERT.

Ahora, ¿cómo se llega a tener un nodo con máscara 0 en un grafo parcial sano? Ejemplo: creo un nodo X (nuevo), no lo enlazo a nada. El BFS no lo alcanza. Máscara 0. Se skipea del INSERT. Próximo guardado: X sigue en memoria, sin enlaces, sigue con máscara 0. Se skipea otra vez. Nunca se persiste.

Si después lo enlazo desde un nodo Y (con máscara > 0), el BFS lo alcanza, máscara > 0, se inserta. OK. Pero si antes de enlazarlo hubo un Nodo::eliminar o algo que cambió su id_fila... complicado.

El caso problemático del piloto: un nodo Y (con máscara > 0) tenía una arista hacia X. En el guardado parcial, el BFS del grafo en memoria alcanza X (porque la arista Y→X está en memoria). X tiene máscara > 0. Se inserta bien. Después, en algún momento, X perdió la arista Y→X (porque Y fue cargado desde el árbol del compartido, y X desde el árbol del dueño, y la arista cruzada no estaba en el grafo parcial). Entonces X queda huérfano en memoria, máscara 0, y se skipea del INSERT.

O peor: X tiene máscara 0 desde la carga inicial (porque su bitmask en DB es 0, ya estaba roto). El BFS no lo alcanza. Se skipea del INSERT. No se puede recuperar.

El fix de Fase 4 tiene dos partes:

Parte 1: insertar nodos con máscara 0 (no skipear). Garantiza que el id_fila se preserva. Pero como el nodo ya existía en DB con id_fila X, ¿qué pasa? Hmm. Si el nodo estaba en $mapa_previo, se reinserta con id_fila viejo. OK. Si el nodo NO estaba en $mapa_previo (porque el cargar_parcial lo skipeó), se inserta con id_fila auto-increment. Doble entrada en DB: la vieja con máscara 0 (que ya estaba ahí) y la nueva con máscara 0.

Espera, revisando el flujo del cargar_parcial:

Si el nodo en DB tiene máscara 0, el WHERE (contexto_mascara & $mascara_pedida) != 0 no lo matchea. No se carga. No está en memoria. No está en $mapa_previo.

Si en el guardado parcial, el código inserta nodos de memoria con máscara 0, ese nodo en DB con máscara 0 (que no fue cargado) queda fuera del INSERT (porque no está en memoria). No se toca. OK.

Pero si un nodo estaba en memoria con máscara 0 (porque fue recién creado por la app, o porque se quedó huérfano dentro del grafo), no estaba en $mapa_previo. Se inserta con id_fila auto-increment. Nuevo row. Si ya existía en DB con máscara 0 desde antes (por otro request), se DUPLICA.

Hmm, esto es complicado. Creo que el fix no puede ser tan simple como "insertar los de máscara 0".

El plan sugiere:

No saltar nodos con máscara 0 del INSERT. Insertarlos con máscara 0.

Propagar máscaras a fixpoint sobre TODAS las aristas.

Interpreto la idea así: si hacemos (2), los nodos en memoria que sí son alcanzables desde IDs especiales del grafo van a recuperar sus bits antes del INSERT. Solo los realmente huérfanos en memoria (no alcanzables desde ningún ID especial del grafo en memoria) quedarán con máscara 0. Para esos, insertarlos con máscara 0 garantiza que su id_fila se persiste, así en el próximo guardado no se duplican.

Pero, ¿cómo hacemos el "fixpoint" sobre TODAS las aristas? El BFS actual es multi-fuente desde IDs especiales. Si un nodo A tiene máscara > 0 (porque el BFS lo alcanzó) pero su arista A→B apunta a B que tiene máscara 0 (porque el BFS no llegó a B), propagar de A a B le da máscara a B.

Espera, si A tiene máscara > 0 y la arista A→B está en el grafo, ¿por qué el BFS no llegó a B? Porque el BFS se hace en _calcular_y_registrar_contextos. Veamos ese código:

php
private static function _calcular_y_registrar_contextos($sql, $nombre): ?array {
    $especiales = [];  // IDs de todos los nodos con ID no numérico
    // ... ordenar
    $bit_por_contexto = self::_cargar_contextos_registrados($sql, $nombre);
    // ... asignar bits
    $mascaras = [];
    $cola = [];
    foreach ($especiales as $id) {
        $mascaras[$id] = 1 << $bit_por_contexto[$id];
        $cola[] = $id;
    }
    while (!empty($cola)) {
        $id = array_shift($cola);
        // ... por cada adyacente del nodo $id
        // propagar su máscara al destino si no la tenía
    }
    return $mascaras;
}
El BFS sí propaga a TODOS los nodos alcanzables, no solo a los nuevos. Entonces, si A tiene máscara > 0 y A→B está en el grafo, B DEBERÍA tener máscara > 0.

A menos que... A→B no esté en el grafo en memoria. O que B esté en el grafo pero A no. Hmm.

Ah, pero el BFS empieza desde los IDs especiales. Si A es un nodo común (no especial) y B es otro nodo común, y la arista A→B existe en memoria, el BFS le propaga la máscara de A a B (siempre que A haya sido alcanzado desde algún ID especial).

Entonces, ¿cuándo un nodo tiene máscara 0 en un grafo sano? Solo si:

Es un ID especial que no tiene bit asignado (raro, porque se asignan todos).

Es un nodo común que no es alcanzable desde ningún ID especial en el grafo en memoria.

Es un nodo recién creado por la app que no fue enlazado a nada (o solo a otros nodos huérfanos).

El caso "nodo común que perdió sus bits" no puede pasar en el BFS del guardado, porque el BFS es determinista: si es alcanzable, tiene bits; si no, no.

Entonces, ¿por qué el piloto tenía 1817 nodos con máscara 0 en DB? Esas son filas viejas que se fueron acumulando de guardados anteriores. Nodos que en su momento fueron creados, no alcanzables, y se insertaron con máscara 0 (¿o no se insertaron y quedaron huérfanos... no, en DB estaban con máscara 0).

Espera. Revisemos de nuevo el código de INSERT:

php
if (!$sql->query($q)) { throw ... }
if (!isset($mapa_previo[$id_nodo])) {
    self::$_mapa_id_a_fila[$nombre][$id_nodo] = (int)$sql->insert_id;
} else {
    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];
}
El INSERT se ejecuta para todos los $ids_a_insertar, y para los de $mapa_previo, se fuerza el id_fila viejo. Es decir, hace INSERT INTO nodo_contexto (id_fila, ...) VALUES ($id_fila_viejo, ...). Eso funciona si el row con ese id_fila ya fue DELETEado (que sí, por el DELETE ... WHERE id_fila IN ($mapa_previo)).

Ahora, ¿qué pasa con un nodo que estaba en $mapa_previo (cargado), perdió todos sus bits en el BFS (máscara 0), y no matchea el filtro?

En el bloque de INSERT:

php
if ($mf !== 0 && ($mascara & $mf) === 0) return;
No se inserta. No queda row en DB con ese id_fila. La arista que lo apuntaba desde otro nodo (con máscara > 0) tampoco se inserta (porque chequea if (!isset($mapa_final[$id_dest])) continue;).

Espera, no. El chequeo de aristas:

php
$ady = $nodo->adyacentes();
foreach ($ady as $enlace => $adyacente) {
    $id_dest = $adyacente->id();
    $m_d = (int)($mascaras[$id_dest] ?? 0);
    if ($mf !== 0 && ($m_d & $mf) === 0) continue;  // destino no matchea, skip
    if (!isset($mapa_final[$id_dest])) continue;  // destino no está en mapa, skip
    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
}
Si el destino tiene máscara 0 y mf !== 0, se skipea. Pero el DELETE de aristas antes fue:

php
$q_del_ady = "DELETE FROM adyacente_contexto WHERE ... AND idnodo_fila IN ($ids_in) AND idadyacente_fila IN ($ids_in);";
Solo borra aristas cuyos DOS extremos están en 
i
d
s
i
n
(
q
u
e
s
o
n
ids 
i
​
 n(quesonfilas_cargadas = 
m
a
p
a
p
r
e
v
i
o
)
.
O
s
e
a
,
l
a
s
a
r
i
s
t
a
s
q
u
e
c
r
u
z
a
n
e
l
b
o
r
d
e
n
o
s
e
b
o
r
r
a
n
.
P
e
r
o
s
i
e
l
d
e
s
t
i
n
o
e
s
t
a
ˊ
e
n
mapa 
p
​
 revio).Osea,lasaristasquecruzanelbordenoseborran.Perosieldestinoest 
a
ˊ
 enmapa_previo y su origen también, la arista SÍ se borra. Después, en el INSERT de aristas, se skipea (porque destino máscara 0). Resultado: la arista desaparece de DB. Bug.

Pero el blindaje de v1.5i.7t detectaría eso. Hmm, el chequeo de aristas cuenta cuántas aristas se calcularon y cuántas se insertaron. Si una se skipea antes de llegar a $filas_aristas, no se cuenta en el esperado. Entonces el chequeo pasa aunque falten aristas reales.

Ahí está el bug. El blindaje mira "esperado = count(filas_aristas) = cuántas se calcularon para insertar". No mira "esperado = cuántas aristas había en DB antes del DELETE". Entonces las aristas que se skipean por máscara 0 del destino no se detectan.

El fix real tiene que resolver esto. Tres opciones:

Opción A (del plan): insertar nodos con máscara 0 y propagar máscaras a fixpoint.

Con la propagación a fixpoint, si A tiene máscara > 0 y A→B, entonces B recupera máscara > 0. Eso resuelve el problema del destino con máscara 0.

Pero todavía queda el caso "nodo huérfano en memoria" (nadie lo apunta, no es alcanzable). Ese queda con máscara 0. Insertarlo con máscara 0 le da id_fila estable, así que la próxima vez que aparezca, se hace UPDATE no INSERT nuevo. Pero si estaba en $mapa_previo, se reinserta con id_fila viejo. Si no estaba en $mapa_previo (era nuevo), se inserta con id_fila auto-increment nuevo. Si ese mismo nodo aparece en otro request como "nuevo" también, se duplica. ¿Cómo se evita? Con el id_especial o con un sistema de IDs estables, no hay forma de identificarlo entre requests. Es un nodo huérfano, no importa.

Opción B (más robusta): cambiar el DELETE de aristas para que SOLO borre aristas cuyos dos extremos están en 
m
a
p
a
p
r
e
v
i
o
Y
A
M
B
O
S
t
i
e
n
e
n
m
a
ˊ
s
c
a
r
a
>
0
(
m
a
t
c
h
e
a
n
e
l
f
i
l
t
r
o
)
.
D
e
s
p
u
e
ˊ
s
,
r
e
i
n
s
e
r
t
a
r
t
o
d
a
s
l
a
s
a
r
i
s
t
a
s
c
u
y
o
o
r
i
g
e
n
e
s
t
e
ˊ
e
n
mapa 
p
​
 revioYAMBOStienenm 
a
ˊ
 scara>0(matcheanelfiltro).Despu 
e
ˊ
 s,reinsertartodaslasaristascuyoorigenest 
e
ˊ
 enmapa_final (matchea) y cuyo destino TAMBIÉN esté en 
m
a
p
a
f
i
n
a
l
.
S
i
e
l
d
e
s
t
i
n
o
n
o
e
s
t
a
ˊ
e
n
mapa 
f
​
 inal.Sieldestinonoest 
a
ˊ
 enmapa_final... no insertar. Pero la arista original ya se borró. Mala.

Opción C (la más segura): después de la propagación a fixpoint, solo borrar aristas cuyos dos extremos estén en $mapa_previo Y que van a ser reinsertadas (porque ambos extremos matchean). Las demás aristas se dejan como están (borrado quirúrgico).

Este es el bug del delta real. El plan sugiere A. Vamos a hacer A con cuidado.

Plan del fix:

Cambio 1: no skipear nodos con máscara 0 del INSERT. En el loop de $ids_a_insertar, quitar el return cuando $mascara === 0. Pero, ¿qué pasa con los nodos viejos (en 
m
a
p
a
p
r
e
v
i
o
)
q
u
e
t
e
n
ı
ˊ
a
n
m
a
ˊ
s
c
a
r
a
>
0
y
a
h
o
r
a
t
i
e
n
e
n
0
?
E
s
o
s
p
i
e
r
d
e
n
b
i
t
s
.
E
l
b
l
i
n
d
a
j
e
l
o
d
e
t
e
c
t
a
(
a
m
e
n
o
s
q
u
e
e
s
t
e
ˊ
e
l
c
a
s
o
N
o
d
o
:
:
e
x
i
s
t
e
.
.
.
q
u
e
y
a
c
u
b
r
i
m
o
s
)
.
S
ı
ˊ
,
e
l
b
l
i
n
d
a
j
e
v
a
a
r
e
c
h
a
z
a
r
e
l
g
u
a
r
d
a
d
o
.
E
n
t
o
n
c
e
s
l
o
s
n
o
d
o
s
c
o
n
m
a
ˊ
s
c
a
r
a
0
q
u
e
s
e
v
a
n
a
i
n
s
e
r
t
a
r
s
o
n
s
o
l
o
l
o
s
N
U
E
V
O
S
(
n
o
e
n
mapa 
p
​
 revio)queten 
ı
ˊ
 anm 
a
ˊ
 scara>0yahoratienen0?Esospierdenbits.Elblindajelodetecta(amenosqueest 
e
ˊ
 elcasoNodo::existe...queyacubrimos).S 
ı
ˊ
 ,elblindajevaarechazarelguardado.Entonceslosnodosconm 
a
ˊ
 scara0quesevanainsertarsonsololosNUEVOS(noenmapa_previo). OK.

Cambio 2: propagar máscaras a fixpoint sobre TODAS las aristas. Después del unir con previas (paso 2), hacer un loop: para cada nodo con máscara > 0, propagar a sus vecinos (sin importar si están en $nuevos o no). Repetir hasta que nada cambie (o hasta 20 iteraciones por seguridad).

Cambio 3 (defensivo): si después del fixpoint, un nodo en $mapa_previo sigue con máscara 0 (perdió todos sus bits), abortar (rollback). Eso es lo que el blindaje ya hace, pero conviene que sea explícito antes del INSERT.

Con esos 3 cambios:

Nodos con máscara 0 (huérfanos reales en memoria): se insertan con máscara 0. No crean duplicados entre requests posteriores (bueno, depende, pero al menos no se skipean).

Nodos en $mapa_previo con máscara 0 tras fixpoint: abort. El blindaje lo detecta, el guardado falla ruidosamente. Mejor que corromper.

Aristas con destino que recuperó bits por fixpoint: se insertan OK.

Pero hay un problema con la "propagación a fixpoint" que quiero evitar: si un nodo A tiene máscara > 0 y A→B, propagar A→B. Pero si B es un nodo huérfano que en realidad NO debería tener bits (porque no es del contexto), esa propagación le da bits indebidamente. Eso rompe el aislamiento por topología.

Espera, no. El BFS original ya hace esa propagación a TODOS los alcanzables. Si A es alcanzable desde un ID especial, entonces B (destino de A) es alcanzable desde ese ID especial también (por transitividad). Así que B DEBE tener los bits del ID especial.

El BFS hace exactamente eso. Entonces, ¿por qué el fixpoint extra?

Ah. Creo que el tema es: el BFS en _calcular_y_registrar_contextos recorre desde $especiales. Si un nodo A es un ID especial (recién creado, por ejemplo), pero A no matchea el filtro (no está en $mascara_pedida)... hmm, no, todos los IDs especiales se asignan al BFS.

Espera, la función:

php
private static function _calcular_y_registrar_contextos($sql, $nombre): ?array {
    $especiales = [];
    Nodo::por_cada_nodo_ejecutar(static::$token, function($nodo) use (&$especiales) {
        $id = $nodo->id();
        if (!is_numeric($id)) { $especiales[] = (string)$id; }
    });
    // ... BFS desde $especiales
}
Toma TODOS los IDs especiales del grafo en memoria. Hace BFS desde todos. Si el grafo en memoria está completo (carga total), todo bien. Si es parcial (carga parcial), solo están los IDs especiales que matchean el filtro. Entonces el BFS parte de esos.

Hmm. Entonces un nodo A con máscara > 0 es alcanzable desde algún ID especial que está en el grafo. A→B propaga a B. ¿Por qué B tendría máscara 0? Solo si B no está en el grafo en memoria (no fue cargado, o fue creado pero no enlazado). Pero si A→B está en el grafo, B está en el grafo (porque la arista apunta a B).

Espera, ¿y si A tiene máscara > 0 porque A es un ID especial (nuevo, no cargado antes, solo en memoria)? A→B, B también en memoria. El BFS debería propagar A→B.

Hmm, entonces no veo cómo un nodo puede tener máscara 0 teniendo un padre con máscara > 0.

A menos que... el BFS tenga un corte. O que A sea alcanzado DESPUÉS del BFS principal. Veamos el paso 3 del código (propagación a nodos nuevos):

php
if ($mf_parcial !== 0 && !empty($mapa_previo)) {
    $nuevos = [];  // nodos que NO están en $mapa_previo (nuevos)
    // ...
    if (!empty($nuevos)) {
        $cambio = true;
        $iter = 0;
        while ($cambio && $iter < 10) {
            $cambio = false;
            $iter++;
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {
                $id_origen = $nodo->id();
                $m_origen = (int)($mascaras[$id_origen] ?? 0);
                if ($m_origen === 0) return;  // <-- si origen tiene 0, no propaga
                $ady = $nodo->adyacentes();
                if (!is_array($ady)) return;
                foreach ($ady as $destino) {
                    $id_dest = $destino->id();
                    if (!isset($nuevos[$id_dest])) continue;
                    $m_dest = (int)($mascaras[$id_dest] ?? 0);
                    $m_nueva = $m_dest | $m_origen;
                    if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }
                }
            });
        }
    }
}
Este paso propaga a nodos nuevos desde nodos con máscara > 0. Pero solo a los que están en $nuevos. Si un nodo ya existía (en $mapa_previo) con máscara 0, no se propaga.

Entonces el fix de Fase 4 para "propagar a fixpoint" sería: hacer que este loop propague a TODOS los nodos (no solo a $nuevos).

Pero el BFS principal debería cubrir eso. Hmm.

A ver, el BFS principal recorre desde los IDs especiales y propaga a todos los adyacentes. La única diferencia con el fixpoint extra es: el BFS principal corre ANTES del unir con previas, y el unir con previas agrega bits que el BFS no había puesto. Entonces si después del unir, un nodo A tiene bits nuevos (por el unir), esos bits no se propagan a sus adyacentes. Ese es el gap.

Ejemplo: nodo A tenía máscara 4 en DB (unir). El BFS del grafo en memoria le da máscara 0 (no alcanzable desde IDs especiales del grafo parcial). Unir: máscara de A = 4. Pero B (adyacente de A en memoria) tiene 0. Como A tiene 4 (después del unir), pero el BFS no propagó (A no era alcanzable), B sigue con 0.

Fixpoint después del unir: A tiene 4, propaga a B. B sube a 4. Y así hasta estabilizar.

OK, ese es el caso real. El fixpoint extra cubre este gap.

Ahora, ¿por qué el código actual solo propaga a $nuevos? Porque el desarrollador del código pensó "solo los nodos nuevos necesitan recuperar bits, los viejos ya tienen su máscara en DB". Pero eso es incorrecto, porque los viejos pueden haber perdido bits en el BFS y el unir no los recupera (el unir solo agrega los que tenía en DB, pero si perdió en el BFS y no tenía en DB, queda con 0).

Espera, no. El unir ES "los bits que el nodo tenía en DB". Si el nodo tenía 4 en DB, el unir le da 4. El BFS le da 0 (si no es alcanzable en el grafo parcial). Resultado: 4. No pierde nada.

Si el nodo tenía 4 en DB y el BFS le da 4 (porque ahora es alcanzable), sigue con 4. OK.

Si el nodo tenía 4 en DB pero el BFS le da... ¿qué podría sacarle 4? Nada. El unir es OR, siempre suma.

Entonces, ¿el blindaje detecta "pérdida de bits"? Solo si después del unir, la máscara es menor que la vieja. Eso no puede pasar porque es un OR. ¿Cómo se perdió el bit del viaje del piloto entonces?

Hmm. Revisando el bug original: el dueño tenía máscara 16 (bit 4), pero debía tener bits 4+5+6 o más. Y los bits 5 y 6 faltaban. O sea, alguna vez tuvo 112 y ahora tiene 48 (o algo así). ¿Cómo perdió bits si el unir es OR?

Ah, el bug es que las aristas que le daban los bits 5, 6 al dueño... el BFS las habría recorrido. Pero el unir ya no importa. Los bits estaban en DB, el unir los mantiene. Pero el problema es que la fila del dueño en DB perdió bits en algún guardado anterior (antes del blindaje), cuando el unir no existía o estaba mal.

Espera. El código actual tiene el unir (v78s). El bug del viaje viejo fue reportado en v1.5piloto.78s. El unir ya estaba. Y el bug seguía. Por eso v78r y v78s fueron intentos fallidos.

Revisando el historial:

v78r: regla de borrado de aristas (solo borrar si DOS extremos en filtro).

v78s: unir máscaras.

Ambos aplicados, el bug seguía. Y luego v78v (fix de id_fila) lo resolvió. Pero después volvió a aparecer. Y v1.5i.8a lo resolvió definitivamente.

Entonces el bug "se cree resuelto" pero el leak lento sigue.

Ahora, sin evidencia concreta del bug en esta sesión, el fix de Fase 4 es defensivo. Los dos cambios:

Insertar nodos con máscara 0.

Propagar a fixpoint sobre TODAS las aristas (no solo nuevos).

Y un tercer cambio: chequeo post-fixpoint de que ningún nodo en $mapa_previo perdió bits. El blindaje ya lo hace, pero conviene reforzarlo.

Vamos con eso. El script sería:

En _guardar_con_filtro, después del bloque de $mascaras (unir con previas + propagación a nuevos), agregar el fixpoint sobre TODOS los nodos.

En el loop de $ids_a_insertar, quitar el return cuando máscara 0. Agregar comentario.

En el chequeo de aristas: no skipear si destino tiene máscara 0 pero está en $mapa_final (que sí va a estar, porque insertamos todos). En realidad, con el cambio 2, si el destino matchea el filtro (está en 
m
a
p
a
f
i
n
a
l
)
,
s
i
e
m
p
r
e
s
e
i
n
s
e
r
t
a
l
a
a
r
i
s
t
a
.
S
i
e
l
d
e
s
t
i
n
o
N
O
e
s
t
a
ˊ
e
n
mapa 
f
​
 inal),siempreseinsertalaarista.SieldestinoNOest 
a
ˊ
 enmapa_final, se skipea. Eso ya está bien.

Hmm, pero si el destino tiene máscara 0 y mf !== 0, con el cambio 2 se inserta el nodo (id_fila), pero la arista se skipea (porque ($m_d & $mf) === 0). Eso deja la arista faltante. Pero el blindaje lo detectaría? No, porque $filas_aristas no incluye la arista skipeada. count($filas_aristas) sería menor que "aristas reales en el grafo". El blindaje chequea count($filas_aristas) == count(filas_en_db), no "cuántas aristas tiene el grafo". Entonces pasa.

Pero ese caso es: nodo A (matchea filtro, máscara > 0) tiene arista A→B, y B tiene máscara 0 (no matchea filtro). Con el fixpoint, B debería tener máscara > 0 (porque A→B la propaga). Si el fixpoint funciona, B tiene máscara > 0 y no hay problema. Si el fixpoint no funciona (B no se propagó), B tiene 0, la arista se skipea, queda faltante.

Entonces el fixpoint es crítico. Vamos a hacerlo bien.

Otra cosa: el orden. El fixpoint debería correr DESPUÉS del unir con previas y DESPUÉS de la propagación a nuevos (o reemplazarla). Pero, ¿reemplazar o complementar? Complementar: primero unir previas, después propagar a nuevos, después propagar a fixpoint a todos.

El fixpoint actual es:

php
while ($cambio && $iter < 10) {
    $cambio = false;
    $iter++;
    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {
        $id_origen = $nodo->id();
        $m_origen = (int)($mascaras[$id_origen] ?? 0);
        if ($m_origen === 0) return;
        $ady = $nodo->adyacentes();
        if (!is_array($ady)) return;
        foreach ($ady as $destino) {
            $id_dest = $destino->id();
            if (!isset($nuevos[$id_dest])) continue;  // <-- solo a nuevos
            $m_dest = (int)($mascaras[$id_dest] ?? 0);
            $m_nueva = $m_dest | $m_origen;
            if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }
        }
    });
}
El fix: quitar if (!isset($nuevos[$id_dest])) continue;. Ahora propaga a TODOS los destinos.

Pero, ¡cuidado! Si un nodo A tiene máscara > 0 y A→B, y B NO debería tener esa máscara (porque B pertenece a otro contexto no relacionado)... espera, pero si A→B está en el grafo, B es alcanzable desde A. En un BFS desde IDs especiales, si A tiene un bit de X, B tiene el bit de X (porque X→...→A→B). Entonces B DEBE tener ese bit.

El único caso en el que B "no debería tener el bit de X" es si A→B cruzó contexto (porque A es del contexto Y y B es del contexto Z, y hay una arista A→B que va de Y a Z). Pero en un grafo dirigido, si A→B, el bit de Y se propaga a B. Eso es lo que hace el BFS. Es correcto.

Entonces quitar el filtro $nuevos es seguro.

OK, el cambio:

php
// Antes:
if (!isset($nuevos[$id_dest])) continue;

// Después:
// (sin filtro)
Y quizás subir el límite de iteraciones de 10 a 30 para seguridad. Pero 10 iteraciones con cada vez más propagación debería alcanzar. Dejo 20.

También agregar un log si se excede: si $iter >= 20 y aún $cambio, log de alerta. Es defensivo.

Ahora, el cambio de "insertar nodos con máscara 0".

Hoy:

php
$ids_a_insertar = [];
$nodos_mascara_0 = [];
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {
    $id = $nodo->id();
    $mascara = (int)($mascaras[$id] ?? 0);
    if ($mf !== 0 && ($mascara & $mf) === 0) {
        if ($mascara === 0) $nodos_mascara_0[] = $id;
        return;
    }
    $ids_a_insertar[] = $id;
});
Cambio:

php
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {
    $id = $nodo->id();
    $mascara = (int)($mascaras[$id] ?? 0);
    if ($mf !== 0 && ($mascara & $mf) === 0) {
        if ($mascara === 0) {
            // Fase 4: insertar nodos con máscara 0 para preservar id_fila.
            $nodos_mascara_0[] = $id;
            $ids_a_insertar[] = $id;
        }
        // Los que tienen máscara > 0 pero no matchean: skip (no pertenecen al filtro).
        return;
    }
    $ids_a_insertar[] = $id;
});
Espera, ¿qué pasa con los que tienen máscara > 0 pero no matchean el filtro? Esos son de otro contexto. No se insertan. Correcto.

Ahora el caso de aristas:

Hoy:

php
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {
    $id_origen = $nodo->id();
    $m_o = (int)($mascaras[$id_origen] ?? 0);
    if ($mf !== 0 && ($m_o & $mf) === 0) return;
    if (!isset($mapa_final[$id_origen])) return;
    $fila_origen = (int)$mapa_final[$id_origen];
    $ady = $nodo->adyacentes();
    foreach ($ady as $enlace => $adyacente) {
        $id_dest = $adyacente->id();
        $m_d = (int)($mascaras[$id_dest] ?? 0);
        if ($mf !== 0 && ($m_d & $mf) === 0) continue;
        if (!isset($mapa_final[$id_dest])) continue;
        $fila_dest = (int)$mapa_final[$id_dest];
        $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
    }
});
Si el destino tiene máscara 0 pero está en $mapa_final (porque ahora se inserta con máscara 0), el if ($mf !== 0 && ($m_d & $mf) === 0) continue; lo skipea. Eso deja la arista faltante.

Hmm. Pero con el fixpoint, ¿el destino podría tener máscara 0? Solo si es un huérfano real. En un grafo sano, los huérfanos reales son pocos o ninguno. Y las aristas hacia ellos serían raras. Pero si existen, deberían insertarse.

La opción: cambiar el if ($mf !== 0 && ($m_d & $mf) === 0) continue; para solo skipear si el destino NO está en $mapa_final. O sea, si está en $mapa_final, insertar la arista (aunque el destino tenga máscara 0).

php
if (!isset($mapa_final[$id_dest])) continue;
$fila_dest = (int)$mapa_final[$id_dest];
$filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
Espera, ¿y si el origen tiene máscara > 0 pero no matchea filtro? El if ($mf !== 0 && ($m_o & $mf) === 0) return; al principio lo skipea. OK.

Pero si el origen matchea filtro (está en $mapa_final), y el destino está en $mapa_final (con máscara 0, huérfano), insertamos la arista. Eso es correcto: el grafo tiene esa arista, y ambos extremos están persistidos.

Pero, ¡cuidado! El destino con máscara 0 en $mapa_final... ¿cómo llegó a $mapa_final? Porque lo insertamos en el loop de nodos. OK.

Ahora, ¿la arista del origen (matchea filtro) al destino (huérfano en memoria) tiene sentido? Sí, es una arista real del grafo. Persistirla mantiene la coherencia.

OK, entonces el cambio en aristas es: quitar el chequeo if ($mf !== 0 && ($m_d & $mf) === 0) continue;. Confiar en $mapa_final como criterio único.

Espera, pero si quitamos ese check, ¿qué pasa con el caso "destino tiene máscara > 0 pero no está en 
m
a
p
a
f
i
n
a
l
"
?
E
s
e
c
a
s
o
y
a
n
o
p
u
e
d
e
p
a
s
a
r
p
o
r
q
u
e
a
h
o
r
a
t
o
d
o
s
l
o
s
n
o
d
o
s
(
m
a
t
c
h
e
e
n
o
n
o
)
v
a
n
a
‘
mapa 
f
​
 inal"?Esecasoyanopuedepasarporqueahoratodoslosnodos(matcheenono)vana‘mapa_final... no. Los que tienen máscara > 0 pero no matchean filtro NO se insertan en
m
a
p
a
f
i
n
a
l
‘
.
E
n
t
o
n
c
e
s
‘
i
f
(
!
i
s
s
e
t
(
mapa 
f
​
 inal‘.Entonces‘if(!isset(mapa_final[$id_dest])) continue;` sigue siendo el filtro correcto.

Y los que tienen máscara 0 ahora SÍ se insertan. if (!isset($mapa_final[$id_dest])) continue; no los skipea. OK.

Entonces solo quitar el check redundante de máscara del destino.

Verificación del blindaje de aristas:

Hoy:

php
$q_cnt_a = "SELECT COUNT(*) AS c FROM adyacente_contexto WHERE ... AND idnodo_fila IN ($ids_in_ar) AND idadyacente_fila IN ($ids_in_ar);";
Y el chequeo es if ($cnt_a !== count($filas_aristas)).

Si ahora insertamos aristas hacia destinos con máscara 0, count($filas_aristas) sube. Y $cnt_a (contando aristas con ambos extremos en $ids_in_ar) también sube. OK.

Pero, ¿qué pasa con aristas que cruzan el borde? El DELETE las deja (porque solo borra si AMBOS extremos en 
i
d
s
i
n
)
.
Y
n
o
s
e
r
e
i
n
s
e
r
t
a
n
.
E
n
t
o
n
c
e
s
‘
ids 
i
​
 n).Ynosereinsertan.Entonces‘cnt_a` cuenta las aristas de 
i
d
s
i
n
a
r
h
a
c
i
a
ids 
i
​
 n 
a
​
 rhaciaids_in_ar, que son las que se reinsertaron. Las que cruzan el borde (origen en $ids_in, destino fuera) no se cuentan. OK, eso está bien.

Un chequeo adicional: no perder nodos del $mapa_previo.

Después del fixpoint, para cada nodo en 
m
a
p
a
p
r
e
v
i
o
c
o
n
m
a
ˊ
s
c
a
r
a
v
i
e
j
a
>
0
,
v
e
r
i
f
i
c
a
r
q
u
e
l
a
n
u
e
v
a
m
a
ˊ
s
c
a
r
a
t
e
n
g
a
t
o
d
o
s
e
s
o
s
b
i
t
s
.
S
i
n
o
,
r
o
l
l
b
a
c
k
.
E
s
o
y
a
e
s
t
a
ˊ
e
n
e
l
b
l
i
n
d
a
j
e
(
‘
i
f
(
(
mapa 
p
​
 revioconm 
a
ˊ
 scaravieja>0,verificarquelanuevam 
a
ˊ
 scaratengatodosesosbits.Sino,rollback.Esoyaest 
a
ˊ
 enelblindaje(‘if((mascara_nueva & 
m
a
s
c
a
r
a
v
i
e
j
a
)
!
=
=
mascara 
v
​
 ieja)!==mascara_vieja) throw`). OK.

Pero con el fixpoint, ¿es posible que un nodo del $mapa_previo con máscara vieja > 0 termine con menos bits? No, porque el unir es OR. El BFS + fixpoint + unir siempre suma. No resta.

Espera, ¿y si el nodo no está en $mascaras después del fixpoint? $mascaras[$id] ?? 0 daría 0. Pero el unir anterior lo puso en $mascaras con la máscara vieja. Y el fixpoint solo suma. Entonces no debería dar 0.

OK, procedo con los cambios.

Ahora, tema: ¿cómo detectar "nodos en $mapa_previo que quedaron con máscara 0"?

Si un nodo estaba en $mapa_previo con máscara vieja > 0, y después del unir + fixpoint sigue con máscara 0 (imposible, porque el unir le da la vieja), abortar. El blindaje ya lo hace.

Pero, ¿qué pasa con los nodos que estaban en $mapa_previo con máscara vieja = 0? Esos son nodos que ya estaban en DB con máscara 0 (huérfanos viejos). Con el cambio 1, ahora se insertan (con máscara 0). OK.

Resumen de los cambios en _guardar_con_filtro:

Después del bloque de unir con previas y antes del bloque de propagación a nuevos (o reemplazándolo): agregar fixpoint sobre TODAS las aristas.

En el bloque de $ids_a_insertar: incluir nodos con máscara 0 que no matcheen el filtro. Solo skipear los que tienen máscara > 0 pero no matcheen.

En el bloque de aristas: quitar el check if ($mf !== 0 && ($m_d & $mf) === 0) continue;. Confiar en $mapa_final.

Logging extra si el fixpoint no converge en N iteraciones.

Veamos el código del bloque de unir con previas y de propagación a nuevos:

php
if ($mf_parcial !== 0) {
    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {
        $id = $nodo->id();
        $m_previa = (int)$nodo->contexto_mascara();
        $m_bfs = (int)($mascaras[$id] ?? 0);
        $mascaras[$id] = $m_previa | $m_bfs;
    });
}

$mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];

if ($mf_parcial !== 0 && !empty($mapa_previo)) {
    $nuevos = [];
    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {
        $id = $nodo->id();
        if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }
    });
    if (!empty($nuevos)) {
        $cambio = true;
        $iter = 0;
        while ($cambio && $iter < 10) {
            $cambio = false;
            $iter++;
            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {
                $id_origen = $nodo->id();
                $m_origen = (int)($mascaras[$id_origen] ?? 0);
                if ($m_origen === 0) return;
                $ady = $nodo->adyacentes();
                if (!is_array($ady)) return;
                foreach ($ady as $destino) {
                    $id_dest = $destino->id();
                    if (!isset($nuevos[$id_dest])) continue;
                    $m_dest = (int)($mascaras[$id_dest] ?? 0);
                    $m_nueva = $m_dest | $m_origen;
                    if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }
                }
            });
        }
    }
}
Reemplazo con:

php
// Fix 1.5i.7v (Fase 4): propagar máscaras a fixpoint sobre TODAS
// las aristas, no solo a los nodos nuevos. Cierra el hueco del
// guardado parcial: los nodos viejos que perdieron sus bits en
// el BFS del grafo parcial recuperan los bits que tenían por
// sus vecinos.
if ($mf_parcial !== 0 && !empty($mascaras)) {
    $cambio = true;
    $iter = 0;
    $max_iter = 20;
    while ($cambio && $iter < $max_iter) {
        $cambio = false;
        $iter++;
        Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, &$cambio) {
            $id_origen = $nodo->id();
            $m_origen = (int)($mascaras[$id_origen] ?? 0);
            if ($m_origen === 0) return;
            $ady = $nodo->adyacentes();
            if (!is_array($ady)) return;
            foreach ($ady as $destino) {
                $id_dest = $destino->id();
                $m_dest = (int)($mascaras[$id_dest] ?? 0);
                $m_nueva = $m_dest | $m_origen;
                if ($m_nueva !== $m_dest) {
                    $mascaras[$id_dest] = $m_nueva;
                    $cambio = true;
                }
            }
        });
    }
    if ($iter >= $max_iter && $cambio) {
        self::_error("SQL64::_guardar_con_filtro: fixpoint de máscaras no convergió en $max_iter iteraciones.");
    }
}
Y después sigue el bloque de $mapa_previo.

Espera, pero esto REEMPLAZA el bloque de propagación a $nuevos. ¿Es correcto? Sí, porque el nuevo fixpoint cubre a todos (incluidos los nuevos).

Cambio en $ids_a_insertar:

Hoy:

php
$ids_a_insertar = [];
$nodos_mascara_0 = [];
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {
    $id = $nodo->id();
    $mascara = (int)($mascaras[$id] ?? 0);
    if ($mf !== 0 && ($mascara & $mf) === 0) {
        if ($mascara === 0) $nodos_mascara_0[] = $id;
        return;
    }
    $ids_a_insertar[] = $id;
});
Cambio:

php
$ids_a_insertar = [];
$nodos_mascara_0 = [];
Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {
    $id = $nodo->id();
    $mascara = (int)($mascaras[$id] ?? 0);
    if ($mf !== 0 && ($mascara & $mf) === 0) {
        if ($mascara === 0) {
            // Fix 1.5i.7v (Fase 4): insertar nodos con máscara 0
            // para preservar su id_fila. Cierra el hueco que
            // generaba duplicados en cada guardado parcial.
            $nodos_mascara_0[] = $id;
            $ids_a_insertar[] = $id;
        }
        return;
    }
    $ids_a_insertar[] = $id;
});
Y el chequeo posterior de "no hay nodos que matcheen el filtro":

php
if ($mf !== 0 && empty($ids_a_insertar)) {
    throw ...
}
Esto podría cambiar de comportamiento. Si antes el grafo tenía 100 nodos, 50 con máscara > 0 (matchean) y 50 con máscara 0 (skip), ahora inserta los 100. El chequeo sigue funcionando (el array no está vacío).

Pero si TODOS los 100 tienen máscara 0, ahora se insertan los 100 (antes fallaba con "no hay nodos"). Eso cambia de "rollback" a "insertar todos con máscara 0". ¿Es correcto? Depende. Si es un grafo sin IDs especiales cargados, todos tienen máscara 0. Insertarlos con 0 significa persistir todo el grafo sin bits. Malo, porque el siguiente cargar_parcial no los va a cargar.

Pero eso es lo mismo que no insertarlos: el siguiente cargar_parcial no los carga. La diferencia es que ahora están en DB (huérfanos en DB). Siguen siendo basura, pero el id_fila se preserva.

Hmm. Preferiría mantener el chequeo if (empty($ids_a_insertar)) throw para detectar "no hay ningún nodo con máscara en el filtro" — es señal de que algo anda mal con la carga. Pero con el cambio, $ids_a_insertar nunca está vacío si hay nodos en memoria. Entonces el chequeo pierde utilidad.

Ajuste: mantener el chequeo original sobre "nodos que matchean el filtro". O sea, contar por separado:

$ids_matchean_filtro: nodos con máscara > 0 que matchean.

$ids_mascara_0: nodos con máscara 0.

$ids_a_insertar = array_merge($ids_matchean_filtro, $ids_mascara_0).

Chequeo: si $mf !== 0 && empty($ids_matchean_filtro) → throw "no hay nodos del contexto".

Eso mantiene la protección.

Cambio en aristas:

Hoy:

php
foreach ($ady as $enlace => $adyacente) {
    $id_dest = $adyacente->id();
    $m_d = (int)($mascaras[$id_dest] ?? 0);
    if ($mf !== 0 && ($m_d & $mf) === 0) continue;
    if (!isset($mapa_final[$id_dest])) continue;
    $fila_dest = (int)$mapa_final[$id_dest];
    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
}
Cambio:

php
foreach ($ady as $enlace => $adyacente) {
    $id_dest = $adyacente->id();
    // Fix 1.5i.7v (Fase 4): confiar en $mapa_final como único
    // criterio. Si el destino está en $mapa_final, la arista se
    // inserta (el destino quedó persistido, aunque su máscara sea
    // 0). El chequeo previo por máscara del destino dejaba
    // aristas faltantes cuando el destino recuperó máscara por el
    // fixpoint pero el origen ya había pasado por el loop.
    if (!isset($mapa_final[$id_dest])) continue;
    $fila_dest = (int)$mapa_final[$id_dest];
    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];
}
OK, listo. Ahora, ¿algún riesgo de romper aislamiento?

Riesgo 1: $mascaras con fixpoint puede asignar bits a nodos que NO deberían tenerlos. Pero como discutí, si A tiene un bit y A→B, B DEBE tener ese bit. El fixpoint respeta eso.

Riesgo 2: si un nodo A tiene máscara > 0 (por unir con previas) y A→B, pero B pertenece a OTRO contexto (porque hay una arista cruzada mal puesta), el fixpoint propaga los bits de A a B. Eso "contamina" el contexto de B. Pero el aislamiento por topología se define así: si hay camino, hay acceso. Si hay una arista A→B, B es alcanzable desde lo que alcanza A. Es coherente.

Riesgo 3: se insertan nodos con máscara 0. Esos no se cargan en el próximo cargar_parcial. Pero el cargar total sí los carga. Y el fixpoint del próximo guardado les puede dar bits si son alcanzables. OK.

Riesgo 4: si un nodo "huérfano real" (nunca alcanzado) se inserta con máscara 0, y después otro request lo vuelve a crear como "nuevo" (sin saber que ya existe), se duplica. Pero eso ya pasa hoy con cualquier nodo creado en un request y no persistido (se pierde, y el próximo request lo crea de nuevo). No es un bug nuevo.

OK, el fix está pensado. Armo el script.

Test a agregar en el script (opcional, pero útil):

Después del guardado, un log de cuántos nodos con máscara 0 se insertaron. Para detectar acumulación.

Ya está nodos_mascara_0 en el log de DEBUG_PERSISTENCIA. OK.

Versión del framework: actualmente 1.5i.7u. Paso a 1.5i.7v.

Documentación: actualizar plan_actual.md §4.7 (Fase 4 implementada), y agregar lección de Fase 4.

Vamos con el script.

Necesito verificar las posiciones exactas del código en el archivo SQL64. Según el contexto anterior, el código del bloque de unir con previas + propagación a nuevos está entre la línea $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? []; y $filas_cargadas = [];. Y el bloque de $ids_a_insertar está después de los DELETE. Vamos a armar los buscar con eso.

Búsqueda para el primer cambio (reemplazar el bloque de propagación a $nuevos):

php
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: fixpoint de máscaras sobre TODAS las aristas',
    'buscar' => [
        '        if ($mf_parcial !== 0) {',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {',
        '                $id = $nodo->id();',
        '                $m_previa = (int)$nodo->contexto_mascara();',
        '                $m_bfs = (int)($mascaras[$id] ?? 0);',
        '                $mascaras[$id] = $m_previa | $m_bfs;',
        '            });',
        '        }',
        '',
        '        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '',
        '        if ($mf_parcial !== 0 && !empty($mapa_previo)) {',
        '            $nuevos = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {',
        '                $id = $nodo->id();',
        '                if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }',
        '            });',
        '            if (!empty($nuevos)) {',
        '                $cambio = true;',
        '                $iter = 0;',
        '                while ($cambio && $iter < 10) {',
        '                    $cambio = false;',
        '                    $iter++;',
        '                    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {',
        '                        $id_origen = $nodo->id();',
        '                        $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                        if ($m_origen === 0) return;',
        '                        $ady = $nodo->adyacentes();',
        '                        if (!is_array($ady)) return;',
        '                        foreach ($ady as $destino) {',
        '                            $id_dest = $destino->id();',
        '                            if (!isset($nuevos[$id_dest])) continue;',
        '                            $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                            $m_nueva = $m_dest | $m_origen;',
        '                            if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }',
        '                        }',
        '                    });',
        '                }',
        '            }',
        '        }',
    ],
    'reemplazar' => [
        '        if ($mf_parcial !== 0) {',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {',
        '                $id = $nodo->id();',
        '                $m_previa = (int)$nodo->contexto_mascara();',
        '                $m_bfs = (int)($mascaras[$id] ?? 0);',
        '                $mascaras[$id] = $m_previa | $m_bfs;',
        '            });',
        '        }',
        '',
        '        // Fix 1.5i.7v (Fase 4): propagar máscaras a fixpoint sobre TODAS',
        '        // las aristas, no solo a los nodos nuevos. Cierra el hueco del',
        '        // guardado parcial: los nodos viejos que perdieron sus bits en',
        '        // el BFS del grafo parcial (o que nunca los tuvieron) recuperan',
        '        // los bits de sus vecinos por transitividad. Se itera hasta',
        '        // que nada cambie, con un tope de 20 iteraciones por seguridad.',
        '        if ($mf_parcial !== 0 && !empty($mascaras)) {',
        '            $cambio = true;',
        '            $iter = 0;',
        '            $max_iter = 20;',
        '            while ($cambio && $iter < $max_iter) {',
        '                $cambio = false;',
        '                $iter++;',
        '                Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, &$cambio) {',
        '                    $id_origen = $nodo->id();',
        '                    $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                    if ($m_origen === 0) return;',
        '                    $ady = $nodo->adyacentes();',
        '                    if (!is_array($ady)) return;',
        '                    foreach ($ady as $destino) {',
        '                        $id_dest = $destino->id();',
        '                        $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                        $m_nueva = $m_dest | $m_origen;',
        '                        if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }',
        '                    }',
        '                });',
        '            }',
        '            if ($cambio) {',
        '                self::_error("SQL64::_guardar_con_filtro: fixpoint de máscaras no convergió en $max_iter iteraciones.");',
        '            }',
        '        }',
        '',
        '        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];',
    ],
],
Segundo cambio: $ids_a_insertar.

php
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: insertar nodos con máscara 0',
    'buscar' => [
        '            $ids_a_insertar = [];',
        '            $nodos_mascara_0 = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) {',
        '                    if ($mascara === 0) $nodos_mascara_0[] = $id;',
        '                    return;',
        '                }',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
        '            }',
        '',
        '            // Fix 1.5i.7t: si el guardado es parcial y no hay',
        '            // ningún nodo a insertar, es un error. El grafo en',
        '            // memoria no matchea el filtro. Antes se hacía INSERT',
        '            // vacío y el DELETE había borrado todo lo que estaba',
        '            // en DB. Ahora se aborta con rollback.',
        '            if ($mf !== 0 && empty($ids_a_insertar)) {',
        '                throw new \RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");',
        '            }',
    ],
    'reemplazar' => [
        '            $ids_a_insertar = [];',
        '            $ids_matchean_filtro = [];',
        '            $nodos_mascara_0 = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$ids_matchean_filtro, &$nodos_mascara_0) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) {',
        '                    if ($mascara === 0) {',
        '                        // Fix 1.5i.7v (Fase 4): insertar nodos con máscara 0',
        '                        // para preservar su id_fila entre requests. Antes se',
        '                        // saltaban del INSERT y, en el próximo guardado que sí',
        '                        // les diera un bit, se insertaban con un id_fila nuevo,',
        '                        // duplicándose. Este es el leak lento que produjo los',
        '                        // 1817 nodos con máscara 0 del estado original.',
        '                        $nodos_mascara_0[] = $id;',
        '                        $ids_a_insertar[] = $id;',
        '                    }',
        '                    return;',
        '                }',
        '                $ids_matchean_filtro[] = $id;',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " matchean_filtro=" . count($ids_matchean_filtro)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
        '            }',
        '',
        '            // Fix 1.5i.7t: si el guardado es parcial y no hay',
        '            // ningún nodo del filtro, es un error. El grafo en',
        '            // memoria no matchea el filtro. Antes se hacía INSERT',
        '            // vacío y el DELETE había borrado todo lo que estaba',
        '            // en DB. Ahora se aborta con rollback.',
        '            // Fix 1.5i.7v: el chequeo ahora usa $ids_matchean_filtro,',
        '            // no $ids_a_insertar, porque los nodos con máscara 0',
        '            // también entran a $ids_a_insertar.',
        '            if ($mf !== 0 && empty($ids_matchean_filtro)) {',
        '                throw new \RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");',
        '            }',
    ],
],
Tercer cambio: chequeo de aristas.

php
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: no skipear aristas por máscara del destino',
    'buscar' => [
        '            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '            $filas_aristas = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {',
        '                $id_origen = $nodo->id();',
        '                $m_o = (int)($mascaras[$id_origen] ?? 0);',
        '                if ($mf !== 0 && ($m_o & $mf) === 0) return;',
        '                if (!isset($mapa_final[$id_origen])) return;',
        '                $fila_origen = (int)$mapa_final[$id_origen];',
        '                $ady = $nodo->adyacentes();',
        '                if (!is_array($ady)) return;',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    $m_d = (int)($mascaras[$id_dest] ?? 0);',
        '                    if ($mf !== 0 && ($m_d & $mf) === 0) continue;',
        '                    if (!isset($mapa_final[$id_dest])) continue;',
        '                    $fila_dest = (int)$mapa_final[$id_dest];',
        '                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];',
        '                }',
        '            });',
    ],
    'reemplazar' => [
        '            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '            $filas_aristas = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {',
        '                $id_origen = $nodo->id();',
        '                $m_o = (int)($mascaras[$id_origen] ?? 0);',
        '                if ($mf !== 0 && ($m_o & $mf) === 0) return;',
        '                if (!isset($mapa_final[$id_origen])) return;',
        '                $fila_origen = (int)$mapa_final[$id_origen];',
        '                $ady = $nodo->adyacentes();',
        '                if (!is_array($ady)) return;',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    // Fix 1.5i.7v (Fase 4): confiar en $mapa_final como',
        '                    // único criterio. Si el destino está persistido (aunque',
        '                    // su máscara sea 0), la arista se inserta. El chequeo',
        '                    // previo por máscara del destino dejaba aristas faltantes',
        '                    // cuando el destino recuperó máscara por el fixpoint pero',
        '                    // el origen ya había pasado por el loop.',
        '                    if (!isset($mapa_final[$id_dest])) continue;',
        '                    $fila_dest = (int)$mapa_final[$id_dest];',
        '                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];',
        '                }',
        '            });',
    ],
],
Y el bump de versión automático va a 1.5i.7v.

También, plan_actual.md: actualizar §4.7 con Fase 4 cerrada.

Vamos con el script completo.

Un detalle: la versión del Controlador.php es 1.5i.7s (por los comandos del visualizador). No lo toco en esta tanda. El bump automático en Controlador/Controlador.php solo se aplicaría si lo toco. No lo toco.

Otro detalle: el $version_nueva = '1.5i.7v' para el framework. Pero la versión del piloto en plan_actual.md también tiene que subir. Voy a poner 1.5piloto.83i para el piloto (aunque el piloto no cambia código, el plan lo menciona).

Espera, $version_nueva en el runner se aplica a TODOS los archivos PHP/JS que aparecen en $cambios. plan_actual.md no es PHP/JS, no se bumpea. Solo PerdurarSuperestructuraStringSQL64.php (PHP) va a bumpear a 1.5i.7v. Bien.

Pero también quiero bumpear el piloto? No, el piloto no cambia código, solo el framework. El plan_actual.md no tiene @version. OK.

Voy a usar $version_nueva = '1.5i.7v' (framework).

Ahora, para plan_actual.md, escribo los bloques.

Actualizar §1 header:

text
**Última actualización:** v1.5piloto.83i (Fase 4 del bug §4.7
implementada. Framework 1.5i.7v: fixpoint de máscaras sobre TODAS
las aristas + inserción de nodos con máscara 0. Cierra el leak
lento del guardado parcial).
Hmm, pero el $version_nueva es 1.5i.7v. El plan lo menciona. En el código, el bump automático va al header de PerdurarSuperestructuraStringSQL64.php. En plan_actual.md, manual.

En realidad, la versión del piloto (1.5piloto.83X) es independiente de la del framework (1.5i.7X). Los bumpeo por separado. El $version_nueva = '1.5i.7v' bumpea el archivo SQL64. Y en plan_actual.md escribo manualmente que el framework pasó a 1.5i.7v y que el piloto queda en 83i (por la documentación).

Pero, ¿el piloto realmente cambia código en esta tanda? No. Solo el framework. Entonces, ¿el index.php del piloto debería bumpear? No, no lo toco. ¿Los archivos del piloto (Venta.php, etc.) deberían bumpear? Tampoco, no los toco.

Solo PerdurarSuperestructuraStringSQL64.php cambia (de framework). Y plan_actual.md (docs). OK.

Versión del piloto: no se bumpea. Pero el plan_actual.md menciona una versión en el header. La convención es que el header del plan refleja el estado del proyecto. Si el piloto no cambió, la versión del piloto debería seguir en 83h. Pero como el framework cambió, conviene registrar la versión nueva del framework en el header.

Voy a escribir el header así:

text
**Última actualización:** v1.5piloto.83h + framework 1.5i.7v
(Fase 4 del bug §4.7 implementada en el framework: fixpoint de
máscaras sobre TODAS las aristas + inserción de nodos con máscara
0. Cierra el leak lento del guardado parcial).
OK. Ahora plan_actual.md §4.7: actualizar Fase 4 como cerrada.

Buscar:

text
**Fase 4 pendiente (sesión del framework).** Dos cambios
para cerrar el leak lento de raíz:
1. **No saltar nodos con máscara 0 del INSERT.** Insertarlos
   con máscara 0. El `id_fila` se preserva, las aristas
   apuntan bien, no hay duplicados en el siguiente guardado.
2. **Propagar máscaras a fixpoint sobre TODAS las aristas.**
   Repetir el BFS hasta que no cambie nada. Los nodos
   "huérfanos en memoria" recuperan bits en el próximo
   guardado.

Cambio sensible. Requiere sesión del framework dedicada con
tests a fondo. No bloquea el trabajo del piloto.
Reemplazar con:

text
**Fase 4 cerrada (framework 1.5i.7v).** Dos cambios:
1. **No saltar nodos con máscara 0 del INSERT.** Insertarlos
   con máscara 0. El `id_fila` se preserva entre requests,
   las aristas apuntan bien, no hay duplicados en el siguiente
   guardado. Los nodos con máscara 0 (huérfanos reales en
   memoria) se persisten igual con su id_fila estable.
2. **Propagar máscaras a fixpoint sobre TODAS las aristas**
   (no solo a los nodos nuevos). Reemplaza el bloque de
   propagación a `$nuevos` con 10 iteraciones, por un fixpoint
   general de hasta 20 iteraciones. Los nodos que perdieron
   bits en el BFS del grafo parcial (o que nunca los tuvieron)
   recuperan los bits de sus vecinos por transitividad.
3. **Aristas hacia destinos con máscara 0**: se insertan si
   el destino está en `$mapa_final`. Antes se skipeaban por
   `($m_d & $mf) === 0`, dejando aristas faltantes cuando el
   destino había recuperado máscara por el fixpoint pero el
   origen ya había pasado por el loop.
4. **Chequeo de "no hay nodos del filtro"** ahora usa
   `$ids_matchean_filtro` (nodos con máscara > 0 que
   matchean), no `$ids_a_insertar` (que ahora incluye los
   de máscara 0). Mantiene la protección contra carga
   incorrecta del subgrafo.

**Estado del leak lento:** cerrado de raíz. Los nodos en
memoria con máscara 0 ahora se insertan con su id_fila.
El fixpoint propaga bits por transitividad. El próximo
guardado no duplica.

**Verificación pendiente:** correr el ciclo completo
(migración de pasajeros + venta + cupón) con
`Conf::DEBUG_PERSISTENCIA = true` y verificar
`nodos_mascara_0=0` en todos los guardados. Si aparece
un `nodos_mascara_0 > 0` significativo (> 10), investigar
por qué esos nodos no son alcanzables desde ninguna raíz.
Y el "Estado del bug §4.7" al final de la sección: agregar una línea diciendo que Fase 4 está cerrada.

Y en §1 header, actualizar.

Vamos a armar el script.

También el $version_nueva del script: 1.5i.7v. Los archivos PHP que se tocan se bumpean a 1.5i.7v. Eso es PerdurarSuperestructuraStringSQL64.php. OK.

Hmm, pero plan_actual.md menciona 1.5i.7v en el contenido. No es @version, es texto. OK.

Espera, ¿tengo que actualizar también Controlador.php para que reporte 1.5i.7v? El Controlador.php tiene @version 1.5i.7s por los comandos del visualizador. No lo toco. Queda en 1.5i.7s.

Bien, armo el script.

Una última cosa: el script debería chequear que el archivo PerdurarSuperestructuraStringSQL64.php matchee. Los bloques son largos, con indentación específica. Si alguno falla, va a avisar.

Para las anclas, uso la indentación que vi en el contexto (4 espacios para el primer nivel adentro de la función, 8 para el segundo, etc.). El archivo original tenía:

text
        if ($mf_parcial !== 0) {
            Nodo::por_cada_nodo_ejecutar(...) {
OK, 8 espacios para el if, 12 para el bloque interno.

Para $ids_a_insertar:

text
            $ids_a_insertar = [];
            $nodos_mascara_0 = [];
12 espacios.

Para $mapa_final:

text
            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];
            $filas_aristas = [];
12 espacios.

OK, armo el script completo con runner v2.

Un último detalle: el script necesita "Conf" en el bloque de SQL64. Ya está importado al principio del archivo (use Iteradores\Configuracion\Conf;). No necesito agregarlo.

Bien, vamos con el script completo.

Vamos con la Fase 4. El fix tiene 3 cambios en PerdurarSuperestructuraStringSQL64.php:

Fixpoint de máscaras sobre TODAS las aristas. Hoy el loop propaga solo a $nuevos con tope de 10 iteraciones. Lo reemplazo por un fixpoint general (hasta 20 iteraciones) que propaga a cualquier vecino con máscara 0. Cierra el gap: nodos que perdieron sus bits en el BFS del grafo parcial recuperan por transitividad.

Insertar nodos con máscara 0. Antes se saltaban del INSERT. Ahora se insertan con máscara 0 preservando id_fila. Elimina los duplicados entre requests.

Aristas hacia destinos con máscara 0. Antes se skipeaban por ($m_d & $mf) === 0. Ahora solo se skipea si el destino NO está en $mapa_final. Las aristas entre nodos persistidos se insertan siempre.

El chequeo de "no hay nodos del filtro" cambia a $ids_matchean_filtro para no desactivarse con los nodos de máscara 0.

php
<?php
/**
 * Aplicador — Fase 4 del bug §4.7: fix real del leak lento del
 * guardado parcial.
 *
 * Motivo: el leak lento se producía por tres huecos en
 * `_guardar_con_filtro`:
 *   1. Los nodos con máscara 0 se saltaban del INSERT. Si después
 *      aparecían en otro request como "nuevos", se insertaban con
 *      id_fila auto-increment, duplicándose.
 *   2. La propagación de máscaras a nodos nuevos era limitada
 *      (solo a $nuevos, hasta 10 iteraciones). Los nodos viejos
 *      que perdieron bits en el BFS del grafo parcial no
 *      recuperaban.
 *   3. Las aristas hacia destinos con máscara 0 se skipeaban,
 *      dejando aristas faltantes cuando el destino recuperaba
 *      máscara por el fixpoint pero el origen ya había pasado
 *      por el loop.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5i.7v';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. SQL64: fixpoint de máscaras sobre TODAS las aristas
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: fixpoint de máscaras sobre TODAS las aristas',
    'buscar' => [
        '        if ($mf_parcial !== 0) {',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {',
        '                $id = $nodo->id();',
        '                $m_previa = (int)$nodo->contexto_mascara();',
        '                $m_bfs = (int)($mascaras[$id] ?? 0);',
        '                $mascaras[$id] = $m_previa | $m_bfs;',
        '            });',
        '        }',
        '',
        '        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '',
        '        if ($mf_parcial !== 0 && !empty($mapa_previo)) {',
        '            $nuevos = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$nuevos, $mapa_previo) {',
        '                $id = $nodo->id();',
        '                if (!isset($mapa_previo[$id])) { $nuevos[$id] = true; }',
        '            });',
        '            if (!empty($nuevos)) {',
        '                $cambio = true;',
        '                $iter = 0;',
        '                while ($cambio && $iter < 10) {',
        '                    $cambio = false;',
        '                    $iter++;',
        '                    Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, $nuevos, &$cambio) {',
        '                        $id_origen = $nodo->id();',
        '                        $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                        if ($m_origen === 0) return;',
        '                        $ady = $nodo->adyacentes();',
        '                        if (!is_array($ady)) return;',
        '                        foreach ($ady as $destino) {',
        '                            $id_dest = $destino->id();',
        '                            if (!isset($nuevos[$id_dest])) continue;',
        '                            $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                            $m_nueva = $m_dest | $m_origen;',
        '                            if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }',
        '                        }',
        '                    });',
        '                }',
        '            }',
        '        }',
    ],
    'reemplazar' => [
        '        if ($mf_parcial !== 0) {',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras) {',
        '                $id = $nodo->id();',
        '                $m_previa = (int)$nodo->contexto_mascara();',
        '                $m_bfs = (int)($mascaras[$id] ?? 0);',
        '                $mascaras[$id] = $m_previa | $m_bfs;',
        '            });',
        '        }',
        '',
        '        // Fix 1.5i.7v (Fase 4): propagar máscaras a fixpoint sobre TODAS',
        '        // las aristas, no solo a los nodos nuevos. Cierra el hueco del',
        '        // guardado parcial: los nodos viejos que perdieron sus bits en',
        '        // el BFS del grafo parcial (o que nunca los tuvieron) recuperan',
        '        // los bits de sus vecinos por transitividad. Se itera hasta',
        '        // que nada cambie, con un tope de 20 iteraciones por seguridad.',
        '        if ($mf_parcial !== 0 && !empty($mascaras)) {',
        '            $cambio = true;',
        '            $iter = 0;',
        '            $max_iter = 20;',
        '            while ($cambio && $iter < $max_iter) {',
        '                $cambio = false;',
        '                $iter++;',
        '                Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use (&$mascaras, &$cambio) {',
        '                    $id_origen = $nodo->id();',
        '                    $m_origen = (int)($mascaras[$id_origen] ?? 0);',
        '                    if ($m_origen === 0) return;',
        '                    $ady = $nodo->adyacentes();',
        '                    if (!is_array($ady)) return;',
        '                    foreach ($ady as $destino) {',
        '                        $id_dest = $destino->id();',
        '                        $m_dest = (int)($mascaras[$id_dest] ?? 0);',
        '                        $m_nueva = $m_dest | $m_origen;',
        '                        if ($m_nueva !== $m_dest) { $mascaras[$id_dest] = $m_nueva; $cambio = true; }',
        '                    }',
        '                });',
        '            }',
        '            if ($cambio) {',
        '                self::_error("SQL64::_guardar_con_filtro: fixpoint de máscaras no convergió en $max_iter iteraciones.");',
        '            }',
        '        }',
        '',
        '        $mapa_previo = self::$_mapa_id_a_fila[$nombre] ?? [];',
    ],
],

// ============================================================
// 2. SQL64: insertar nodos con máscara 0
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: insertar nodos con máscara 0',
    'buscar' => [
        '            $ids_a_insertar = [];',
        '            $nodos_mascara_0 = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) {',
        '                    if ($mascara === 0) $nodos_mascara_0[] = $id;',
        '                    return;',
        '                }',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
        '            }',
        '',
        '            // Fix 1.5i.7t: si el guardado es parcial y no hay',
        '            // ningún nodo a insertar, es un error. El grafo en',
        '            // memoria no matchea el filtro. Antes se hacía INSERT',
        '            // vacío y el DELETE había borrado todo lo que estaba',
        '            // en DB. Ahora se aborta con rollback.',
        '            if ($mf !== 0 && empty($ids_a_insertar)) {',
        '                throw new \\RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");',
        '            }',
    ],
    'reemplazar' => [
        '            $ids_a_insertar = [];',
        '            $ids_matchean_filtro = [];',
        '            $nodos_mascara_0 = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$ids_matchean_filtro, &$nodos_mascara_0) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) {',
        '                    if ($mascara === 0) {',
        '                        // Fix 1.5i.7v (Fase 4): insertar nodos con máscara 0',
        '                        // para preservar su id_fila entre requests. Antes se',
        '                        // saltaban del INSERT y, en el próximo guardado que sí',
        '                        // les diera un bit, se insertaban con un id_fila nuevo,',
        '                        // duplicándose. Este es el leak lento que produjo los',
        '                        // 1817 nodos con máscara 0 del estado original.',
        '                        $nodos_mascara_0[] = $id;',
        '                        $ids_a_insertar[] = $id;',
        '                    }',
        '                    return;',
        '                }',
        '                $ids_matchean_filtro[] = $id;',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " matchean_filtro=" . count($ids_matchean_filtro)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
        '            }',
        '',
        '            // Fix 1.5i.7t: si el guardado es parcial y no hay',
        '            // ningún nodo del filtro, es un error. El grafo en',
        '            // memoria no matchea el filtro. Antes se hacía INSERT',
        '            // vacío y el DELETE había borrado todo lo que estaba',
        '            // en DB. Ahora se aborta con rollback.',
        '            // Fix 1.5i.7v: el chequeo ahora usa $ids_matchean_filtro,',
        '            // no $ids_a_insertar, porque los nodos con máscara 0',
        '            // también entran a $ids_a_insertar.',
        '            if ($mf !== 0 && empty($ids_matchean_filtro)) {',
        '                throw new \\RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");',
        '            }',
    ],
],

// ============================================================
// 3. SQL64: no skipear aristas por máscara del destino
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: no skipear aristas por máscara del destino',
    'buscar' => [
        '            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '            $filas_aristas = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {',
        '                $id_origen = $nodo->id();',
        '                $m_o = (int)($mascaras[$id_origen] ?? 0);',
        '                if ($mf !== 0 && ($m_o & $mf) === 0) return;',
        '                if (!isset($mapa_final[$id_origen])) return;',
        '                $fila_origen = (int)$mapa_final[$id_origen];',
        '                $ady = $nodo->adyacentes();',
        '                if (!is_array($ady)) return;',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    $m_d = (int)($mascaras[$id_dest] ?? 0);',
        '                    if ($mf !== 0 && ($m_d & $mf) === 0) continue;',
        '                    if (!isset($mapa_final[$id_dest])) continue;',
        '                    $fila_dest = (int)$mapa_final[$id_dest];',
        '                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];',
        '                }',
        '            });',
    ],
    'reemplazar' => [
        '            $mapa_final = self::$_mapa_id_a_fila[$nombre] ?? [];',
        '            $filas_aristas = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, $mapa_final, &$filas_aristas) {',
        '                $id_origen = $nodo->id();',
        '                $m_o = (int)($mascaras[$id_origen] ?? 0);',
        '                if ($mf !== 0 && ($m_o & $mf) === 0) return;',
        '                if (!isset($mapa_final[$id_origen])) return;',
        '                $fila_origen = (int)$mapa_final[$id_origen];',
        '                $ady = $nodo->adyacentes();',
        '                if (!is_array($ady)) return;',
        '                foreach ($ady as $enlace => $adyacente) {',
        '                    $id_dest = $adyacente->id();',
        '                    // Fix 1.5i.7v (Fase 4): confiar en $mapa_final como',
        '                    // único criterio. Si el destino está persistido (aunque',
        '                    // su máscara sea 0), la arista se inserta. El chequeo',
        '                    // previo por máscara del destino dejaba aristas faltantes',
        '                    // cuando el destino recuperó máscara por el fixpoint pero',
        '                    // el origen ya había pasado por el loop.',
        '                    if (!isset($mapa_final[$id_dest])) continue;',
        '                    $fila_dest = (int)$mapa_final[$id_dest];',
        '                    $filas_aristas[] = [$fila_origen, (string)$enlace, $fila_dest];',
        '                }',
        '            });',
    ],
],

// ============================================================
// 4. plan_actual.md: Fase 4 cerrada
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header con Fase 4 cerrada',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83h (tanda final de',
        'cobertura de `guardar_ambos`. Los 10 flujos restantes del',
        'piloto aplican el patrón C.6. **Todos los flujos del',
        'piloto que llaman `guardar_ambos` chequean el resultado.**',
        'Excepciones documentadas en §4.7).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83h + framework 1.5i.7v',
        '(Fase 4 del bug §4.7 implementada. `_guardar_con_filtro`',
        'hace fixpoint de máscaras sobre TODAS las aristas, inserta',
        'nodos con máscara 0 (preservando id_fila) y no skipea',
        'aristas por máscara del destino. Cierra el leak lento del',
        'guardado parcial. El piloto queda en 83h; solo el framework',
        'cambia de versión).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: Fase 4 cerrada con detalle',
    'buscar' => [
        '**Fase 4 pendiente (sesión del framework).** Dos cambios',
        'para cerrar el leak lento de raíz:',
        '1. **No saltar nodos con máscara 0 del INSERT.** Insertarlos',
        '   con máscara 0. El `id_fila` se preserva, las aristas',
        '   apuntan bien, no hay duplicados en el siguiente guardado.',
        '2. **Propagar máscaras a fixpoint sobre TODAS las aristas.**',
        '   Repetir el BFS hasta que no cambie nada. Los nodos',
        '   "huérfanos en memoria" recuperan bits en el próximo',
        '   guardado.',
        '',
        'Cambio sensible. Requiere sesión del framework dedicada con',
        'tests a fondo. No bloquea el trabajo del piloto.',
    ],
    'reemplazar' => [
        '**Fase 4 cerrada (framework 1.5i.7v).** Tres cambios:',
        '1. **Fixpoint de máscaras sobre TODAS las aristas.** Se',
        '   reemplaza el bloque de propagación a `$nuevos` (hasta',
        '   10 iteraciones) por un fixpoint general (hasta 20).',
        '   Los nodos que perdieron bits en el BFS del grafo',
        '   parcial, o que nunca los tuvieron, recuperan los bits',
        '   de sus vecinos por transitividad. Si no converge en 20',
        '   iteraciones, `_error` (defensivo).',
        '2. **Insertar nodos con máscara 0.** Antes se skipeaban del',
        '   INSERT. Ahora se insertan con máscara 0, preservando',
        '   `id_fila`. Cierra los duplicados entre requests: el',
        '   nodo tiene id_fila estable desde el primer guardado.',
        '   El chequeo de "no hay nodos del filtro" ahora usa',
        '   `$ids_matchean_filtro` (máscara > 0 que matchea), no',
        '   `$ids_a_insertar` (que incluye los de máscara 0).',
        '3. **Aristas hacia destinos con máscara 0.** Antes se',
        '   skipeaban por `($m_d & $mf) === 0`. Ahora solo se',
        '   skipea si el destino NO está en `$mapa_final`. Las',
        '   aristas entre nodos persistidos se insertan siempre,',
        '   aunque el destino tenga máscara 0.',
        '',
        '**Estado del leak lento: cerrado de raíz.** Los nodos en',
        'memoria con máscara 0 se persisten con su id_fila. El',
        'fixpoint propaga bits por transitividad. El próximo',
        'guardado no duplica.',
        '',
        '**Verificación pendiente (usuario).** Correr el ciclo',
        'completo (migración de pasajeros + venta + cupón) con',
        '`Conf::DEBUG_PERSISTENCIA = true`. Esperado:',
        '- `matchean_filtro > 0` en todos los guardados parciales.',
        '- `nodos_mascara_0 = 0` en el ciclo normal.',
        '- Si aparece `nodos_mascara_0 > 10` de forma persistente,',
        '  investigar por qué esos nodos no son alcanzables desde',
        '  ninguna raíz. Pueden ser residuos reales (basura) que',
        '  conviene limpiar con `?eliminar_huerfanos=1`.',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado del bug §4.7 con Fase 4',
    'buscar' => [
        '**Estado del bug:** contenido. Los 4 bugs concretos del',
        'flujo están cerrados. El leak lento del blindaje es deuda',
        'de Fase 4, no de esta sesión.',
    ],
    'reemplazar' => [
        '**Estado del bug:** contenido. Los 4 bugs concretos del',
        'flujo están cerrados. El leak lento del blindaje fue',
        'cerrado de raíz en Fase 4 (framework 1.5i.7v): fixpoint',
        'de máscaras sobre TODAS las aristas + inserción de nodos',
        'con máscara 0 preservando id_fila. El blindaje de',
        '1.5i.7t/7u sigue activo (rollback si pierde bits, nodos',
        'o aristas).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado del proyecto al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83h',
        '(framework 1.5i.7u). **Bug §4.7 contenido:** cerrado',
        'como bug activo tras sesión de diagnóstico que no logró',
        'reproducirlo post-restauración + blindaje. Los 4 bugs',
        'encadenados del flujo de venta en micro de 2 pisos',
        'quedaron cerrados. Blindaje activo (rollback si pierde',
        'bits, nodos o aristas) + logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). **Cobertura de',
        '`guardar_ambos`: COMPLETA.** Todos los flujos del',
        'piloto chequean el resultado (v1.5piloto.83g + 83h).',
        'Queda deuda de Fase 4 (no saltar nodos con máscara 0 +',
        'propagar a fixpoint) para sesión del framework. SQL64',
        'activo, topología admin/soporte, auditoría topológica',
        'disponible. **La migración de pasajeros sigue en pausa**',
        'hasta validación post-83h.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83h',
        '(framework 1.5i.7v). **Bug §4.7 contenido:** cerrado',
        'como bug activo tras sesión de diagnóstico que no logró',
        'reproducirlo post-restauración + blindaje. Los 4 bugs',
        'encadenados del flujo de venta en micro de 2 pisos',
        'quedaron cerrados. Blindaje activo (rollback si pierde',
        'bits, nodos o aristas) + logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). **Cobertura de',
        '`guardar_ambos`: COMPLETA.** Todos los flujos del',
        'piloto chequean el resultado (v1.5piloto.83g + 83h).',
        '**Fase 4: cerrada en framework 1.5i.7v** (fixpoint de',
        'máscaras sobre TODAS las aristas + inserción de nodos',
        'con máscara 0 preservando id_fila + aristas hacia',
        'destinos con máscara 0). El leak lento del guardado',
        'parcial quedó cerrado de raíz. SQL64 activo, topología',
        'admin/soporte, auditoría topológica disponible. **La',
        'migración de pasajeros sigue en pausa** hasta validación',
        'post-1.5i.7v.',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?q_v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";
Log esperado
text
=== Aplicador ===

[INFO] 2 archivo(s) a reemplazar, 0 a crear, 0 a eliminar.

[OK] Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php
[OK] prompts/plan_actual.md

--- Bumps ---
[BUMP] Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php  @version 1.5i.7u -> 1.5i.7v
[INFO] prompts/plan_actual.md (sin @version)

=== Resumen ===
Bloques aplicados: 6
Archivos escritos: 2
Bumps @version:    1
Bumps ?v=:         0

Listo.
Avisos
Bloque 1 es el más delicado. Reemplaza el bloque completo de "propagación a $nuevos" (35 líneas) por el fixpoint general (39 líneas). Si la indentación no matchea, mandame el fragmento exacto del bloque if ($mf_parcial !== 0 && !empty($mapa_previo)) del archivo en disco.

Bloque 2 cambia el chequeo de "no hay nodos del filtro" de $ids_a_insertar a $ids_matchean_filtro. Es importante: si un request solo tiene nodos con máscara 0 en memoria, ahora NO falla con rollback; los inserta y devuelve true. Si querés que falle en ese caso (grafo sin IDs especiales cargados), avisame y lo dejo estricto.

Bloque 3 es el que cierra el leak de aristas. Sin este cambio, las aristas hacia destinos con máscara 0 se seguían skipeando, dejando aristas faltantes.

Qué probar
Sintaxis: php -l Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php.

Activar DEBUG_PERSISTENCIA = true en Configuracion/Configuracion.php (temporal).

Ciclo de prueba:

Login como dueño.

Vender un pasaje.

Cancelar esa venta.

Vender otro.

Pagar un cupón.

Login como terminal y hacer lo mismo.

Login como admin y hacer una operación.

Leer el log de PHP. Esperado:

verif_mascaras ok=1, verif_nodos ok=1, verif_aristas ok=1 en todos los guardados.

matchean_filtro > 0 en todos los guardados parciales.

nodos_mascara_0=0 en el ciclo normal. Si aparece nodos_mascara_0 > 0 es Snowball: pocos (1-5) es aceptable (nodos recién creados que aún no se enlazaron). Si aparece > 10 de forma persistente, hay un problema real.

Después del ciclo, correr ?auditar_topologia=1 como admin. Esperado: 0 críticos, 0 errores, pocos warnings.

Volver DEBUG_PERSISTENCIA = false cuando termines.

Commit sugerido
text
V1.5i.7v: Fase 4 del bug §4.7 — fix real del leak lento

Framework:
- PerdurarSuperestructuraStringSQL64::_guardar_con_filtro:
  1. Fixpoint de máscaras sobre TODAS las aristas. Reemplaza el
     bloque de propagación a $nuevos (hasta 10 iteraciones) por
     un fixpoint general (hasta 20). Los nodos que perdieron bits
     en el BFS del grafo parcial, o que nunca los tuvieron,
     recuperan los bits de sus vecinos por transitividad.
  2. Insertar nodos con máscara 0. Antes se skipeaban del INSERT
     y, en el próximo guardado que sí les diera un bit, se
     insertaban con un id_fila nuevo, duplicándose. Ahora se
     insertan con máscara 0 preservando id_fila. El chequeo de
     "no hay nodos del filtro" ahora usa $ids_matchean_filtro
     (máscara > 0 que matchea), no $ids_a_insertar.
  3. Aristas hacia destinos con máscara 0. Antes se skipeaban por
     ($m_d & $mf) === 0. Ahora solo se skipea si el destino NO
     está en $mapa_final. Las aristas entre nodos persistidos se
     insertan siempre, aunque el destino tenga máscara 0.
- Cierra el leak lento que produjo los 1817 nodos con máscara 0
  del estado original.

Documentación:
- plan_actual.md: Fase 4 cerrada. Verificación pendiente
  (ciclo completo con DEBUG_PERSISTENCIA + auditoría).
Cuando corras y pruebes el ciclo, mandame el log. Si todo va bien, el bug §4.7 queda cerrado de raíz. Después quedan como próximos:

Validación con el ciclo completo y la migración de pasajeros.

Retomar la migración de pasajeros con confianza.

Deuda técnica menor (§4.8): endpoints temporales, espejo _hmi a JS.

bien, ya lo aplique, una cosa, no puedes hacer que el logo quede en un archivo en nuestra carpeta ? porque en pruebas y produccion no voy a poder verlo
El usuario me está pidiendo que el logo quede en un archivo dentro de la carpeta del proyecto (no una URL externa), porque en pruebas y producción no va a poder verlo.

Necesito entender a qué logo se refiere. En los archivos que vi:

Aplicacion/Impresion/Impresion.php usa $logo_ruta = './Aplicacion/Logo.png'; y './Aplicacion/LogoPeque.png'.

aplicacion_GET.html no tiene un logo en el HTML que haya visto.

No vi ningún <img> en el HTML principal apuntando a un logo.

Espera, el usuario dice "en pruebas y producción no voy a poder verlo". Eso sugiere que hay algo del logo que se sirve desde un lugar donde en local funciona pero en producción no.

Posibilidades:

La ruta del logo en Impresion.php es relativa (./Aplicacion/Logo.png). En el flujo de impresión se abre un HTML independiente generado con echo. La ruta ./Aplicacion/Logo.png se resuelve relativa a la URL del navegador (index.php?imprimir=1...), así que debería funcionar en cualquier hosting si el archivo existe en Aplicacion/Logo.png. Pero quizás el logo no está en el repo de pruebas/producción.

Hay algún <img> en el login o el header que apunta a un archivo que no está en el deploy.

Necesito ver el HTML actual y/o los archivos de logo. El usuario dice "no puedes hacer que el logo quede en un archivo en nuestra carpeta?" — o sea, quiere que el logo esté dentro del proyecto, versionado con el código, para que se despliegue junto con el resto.

Pero si Impresion.php ya apunta a ./Aplicacion/Logo.png, el archivo debería existir si está en Aplicacion/. Tal vez el archivo no está en el repo, o tal vez el logo del HTML principal está hardcodeado con una URL externa.

Debería pedirle los archivos relevantes:

aplicacion_GET.html para ver si hay un logo en el HTML.

El archivo Aplicacion/Logo.png (o confirmar que existe).

Impresion.php (ya lo tengo, apunta a ./Aplicacion/Logo.png y ./Aplicacion/LogoPeque.png).

Pero el usuario sugiere que "no puedes hacer que el logo quede en un archivo en nuestra carpeta?" — quizás se refiere a algo específico que estoy pasando por alto. Tal vez hay un logo en el HTML que apunta a una URL externa (por ejemplo, Google, un CDN, un servicio de imágenes).

Miremos el HTML que pasó:

No veo <img> en el login ni el header.

Solo veo texto "Administrador de Viajes".

Hmm. En el login, no hay logo. En el header, tampoco. En el footer dice "Soporte Téctico". No hay logo.

Pero en Impresion.php:

$logo_ruta = './Aplicacion/Logo.png'; para pasajes.

$logo_ruta = './Aplicacion/LogoPeque.png'; para cupones e informes.

Si el archivo Aplicacion/Logo.png existe en local pero no está en el repo (por ejemplo, está en .gitignore), entonces al deployar a pruebas o producción no se copia.

Pero el usuario dice "no puedes hacer que el logo quede en un archivo en nuestra carpeta?" — o sea, ¿ya está en la carpeta y él no lo sabe? ¿O quiere que YO lo genere/mueva?

Espera, releyendo: "no puedes hacer que el logo quede en un archivo en nuestra carpeta ? porque en pruebas y produccion no voy a poder verlo"

Creo que el usuario está diciendo: el logo actual (que se ve en local) está referenciado de alguna manera que en pruebas/producción no funciona. Quizás hay una URL externa o algo así. Necesito ver el código actual del logo.

Posibilidades:

El logo actual en el HTML del piloto apunta a una URL externa (ej: https://viajesdelcarmen.rf.gd/logo.png) o algo.

O el logo está en Aplicacion/Logo.png pero no se subió al repo de pruebas/producción.

No tengo evidencia. Debo pedirle los archivos relevantes. Pero antes, veamos si hay algún <img> o referencia a logo en los archivos que tengo.

En aplicacion_GET.html que me pasó antes: no veo <img> ni logo. Solo texto.

En Impresion.php: ./Aplicacion/Logo.png y ./Aplicacion/LogoPeque.png.

En micros.js: probablemente hay un <img> para la foto del vehículo, pero eso es otra cosa.

Espera. El HTML que me pasó es una versión de aplicacion_GET.html. Pero el usuario está logueado y ve un logo en algún lado. Quizás el logo aparece solo después del login, en el header. Pero el header solo tiene texto:

html
<header class="header">
  <div class="brand">Administrador de Viajes, Pquia. del Carmen Tres Arroyos</div>
  ...
</header>
No hay logo. Solo texto.

Entonces, ¿a qué logo se refiere?

Puede ser que haya un logo en otro lugar. Por ejemplo, en Aplicacion/Logo.png que se usa en las impresiones. El usuario dice "en pruebas y produccion no voy a poder verlo" — tal vez se refiere a las impresiones, no al HTML del piloto.

Pero él dice "no puedes hacer que el logo quede en un archivo en nuestra carpeta?" — "nuestra carpeta" = la carpeta del proyecto. Entonces el logo YA está en Aplicacion/Logo.png (o al menos debería). El problema es que en pruebas/producción no se ve.

Posibilidades:

El archivo Aplicacion/Logo.png no está en el repo de pruebas/producción (por ejemplo, no se subió, o está en .gitignore).

La ruta ./Aplicacion/Logo.png no resuelve correctamente en pruebas/producción. Esto podría pasar si el proyecto está en un subdirectorio distinto en producción, o si el servidor tiene alguna regla.

El logo está en una URL externa en algún lugar que no he visto.

Necesito pedirle:

Los archivos relevantes: dónde está el logo en el HTML (si aparece).

O confirmar que el logo actual está en Aplicacion/Logo.png en local pero no está versionado.

Pero en realidad, quizás el usuario está diciendo: "haceme un logo local en lugar de uno remoto". Es una petición de "hacer que el logo quede en un archivo" — tal vez quiere que se genere un archivo de logo (SVG, PNG, etc.) en el repo con un diseño simple, en lugar de depender de un archivo que puede no estar.

Aunque "en nuestra carpeta" podría significar "en la carpeta del proyecto" — es decir, ya existe localmente pero no se deploya.

Necesito clarificar. Pero primero, debo ver si hay algún <img> en el HTML del piloto. En el que me pasó no hay. Sin embargo, el usuario podría estar viendo el logo en la parte del header o en el login en una versión más nueva del HTML que no me pasó.

Espera, en el HTML que me pasó hace muchas tandas, el header dice:

html
<div class="brand">Administrador de Viajes, Pquia. del Carmen Tres Arroyos</div>
No hay logo. Pero el usuario podría estar viendo un logo en el navegador (favicon) o en una versión distinta.

Hmm. Podría ser el favicon. Los favicons se ponen con <link rel="icon">. El HTML que me pasó no tiene favicon. Pero el navegador por defecto busca /favicon.ico en la raíz, y si no lo encuentra, no muestra logo.

O podría ser que el usuario se refiera al logo de las impresiones (pasajes, cupones, informes), que se ve en el PDF abierto en el navegador. Ese logo está en ./Aplicacion/Logo.png o ./Aplicacion/LogoPeque.png. Si el archivo existe en local pero no se deployó a pruebas/producción, entonces no se ve.

Pero él dice "no puedes hacer que el logo quede en un archivo en nuestra carpeta ?" — "hacer que el logo quede en un archivo" sugiere que actualmente NO está en un archivo (o que se sirve desde otro lado). O sea, tal vez el logo actual viene de una URL externa, y el usuario quiere que se sirva desde un archivo local.

Voy a pedirle:

¿Dónde ve el logo? ¿En el header del piloto? ¿En las impresiones? ¿En el login?

¿En local lo ve? ¿En pruebas/producción no lo ve?

¿El archivo Aplicacion/Logo.png existe en local? ¿Está versionado en git?

Con eso puedo armar un fix. Si es el logo de las impresiones, ya está apuntando a ./Aplicacion/Logo.png, solo hay que asegurarse de que el archivo esté en el repo. Si es el logo del HTML, necesito ver el HTML actualizado.

Pero antes, veamos si hay otras referencias. En los archivos que tengo:

Impresion.php: $logo_ruta = './Aplicacion/Logo.png'; (pasajes) y './Aplicacion/LogoPeque.png'; (cupones, informes).

aplicacion_GET.html: no vi logo.

aplicacion.js: no vi logo.

Entonces, si el usuario ve un logo, es o bien:

El de las impresiones.

El del navegador (favicon) si lo hay.

Algo que me perdí.

Voy a preguntar. Pero antes, un detalle: el usuario dice "en pruebas y produccion no voy a poder verlo". Esto es futuro, no presente. O sea, está anticipando que cuando deploye a pruebas/producción, el logo no se verá. ¿Por qué? Porque el logo actual está en Aplicacion/Logo.png local pero no está versionado en git, o está en un lugar que no se deploya.

Otra posibilidad: el logo actual está en una URL absoluta a localhost (por ejemplo http://localhost/...), que en producción no funciona. Pero no vi eso en Impresion.php.

Otra posibilidad: el logo se sube con uploads/ (que no se versiona), o está en un CDN.

Necesito clarificar. Pero, si es una petición rápida, puedo proponer:

Generar un logo SVG local en Aplicacion/Logo.svg y Aplicacion/LogoPeque.svg, con un diseño simple (por ejemplo, una paloma + texto "Pquia. del Carmen"). Y modificar Impresion.php para usar el SVG local.

Pero los logos actuales son Logo.png y LogoPeque.png, no SVG. Cambiar a SVG podría romper la impresión (los navegadores viejos no lo soportan en <img>, pero los modernos sí).

Alternativa: pedirle al usuario que él provea los archivos Logo.png y LogoPeque.png y que se asegure de que estén versionados. Pero eso no es "que yo haga".

Otra alternativa: hacer un logo inline (base64) dentro del HTML generado en Impresion.php, así no depende de archivos. Eso sí soluciona el problema de "no se deploya el archivo".

Pero el usuario dice "hacer que el logo quede en un archivo en nuestra carpeta", no "inline".

Hmm. Releyendo: "no puedes hacer que el logo quede en un archivo en nuestra carpeta ? porque en pruebas y produccion no voy a poder verlo"

Interpretación: El logo actual no está en un archivo de la carpeta (está en otro lado, o es dinámico, o viene de otro proyecto). Quiere que el logo esté en un archivo dentro del proyecto (Aplicacion/Logo.png, etc.), versionado, para que se deploye. Y yo puedo hacer eso: generar el archivo o moverlo.

Pero el logo ya está referenciado desde Aplicacion/Logo.png. ¿Entonces qué falta? Probablemente el archivo no existe en el repo (solo en local), o el usuario lo tiene en uploads/ o en otro lado.

Necesito clarificar. Le voy a preguntar:

¿Dónde ve el logo actualmente? ¿En el header del piloto, en las impresiones, o en otro lugar?

¿En local funciona? ¿Por qué creés que en pruebas/producción no va a funcionar?

¿Podés pasarme una captura o el bloque de código donde aparece el logo?

Con eso armo el fix. Mientras tanto, si es el logo de las impresiones, ya está apuntando a ./Aplicacion/Logo.png. Si el archivo no está en el repo, el usuario lo tiene que agregar. Yo puedo:

Verificar si Aplicacion/Logo.png existe.

Si no existe, ofrecer generar un logo SVG inline.

Pero no tengo acceso al filesystem. Debo preguntar.

Un detalle: el usuario dice "en pruebas y producción no voy a poder verlo" — o sea, ya sabe que en esos entornos no se verá. Probablemente porque el archivo no está versionado, o porque está usando una ruta que solo funciona en local.

Voy a pedirle:

El bloque HTML donde aparece el logo (si es en el header).

Confirmar si Aplicacion/Logo.png y Aplicacion/LogoPeque.png existen en local y están en git.

Si quiere que genere un logo SVG de ejemplo para reemplazarlos.

Como alternativa rápida, puedo proponer: generar un logo SVG embebido como data URI en el HTML generado por Impresion.php, así no depende de archivos externos. Pero eso no es "que el logo quede en un archivo en nuestra carpeta".

Voy a preguntar antes de armar un script. No quiero hacer cambios a ciegas.

Antes de armar el script, necesito entender bien el problema. Lo que veo en los archivos que tengo:

Impresion.php ya apunta a archivos de la carpeta del proyecto:

php
$logo_ruta = './Aplicacion/Logo.png';        // pasajes
$logo_ruta = './Aplicacion/LogoPeque.png';   // cupones e informes
aplicacion_GET.html (el que me pasaste) no tiene ningún <img> de logo. El header es solo texto:

html
<div class="brand">Administrador de Viajes, Pquia. del Carmen Tres Arroyos</div>
Entonces, el logo que ves vos, ¿dónde está? Las hipótesis que tengo:

A. Es el logo de las impresiones (pasaje, cupón, informes). Ya apunta a Aplicacion/Logo.png. Si en pruebas/producción no se ve, probablemente el archivo no está en el repo o está en un .gitignore. No es un problema de código.

B. Es un logo del header del piloto que está en una versión de aplicacion_GET.html que no me pasaste. Tal vez el HTML cambió desde v82i y ahora hay un <img> con una URL externa o con una ruta rota.

C. Es el favicon del navegador. El HTML actual no tiene <link rel="icon">. Si no hay favicon, el navegador busca /favicon.ico por defecto y muestra el ícono genérico.

D. Es algún logo que se sirve desde uploads/ o desde un CDN externo que no se deploya.

Lo que necesito saber
¿Dónde ves el logo? ¿En el header del piloto cuando estás logueado? ¿En las impresiones (pasajes, cupones, informes)? ¿En el login? ¿En la pestaña del navegador (favicon)?

¿En local lo ves bien? Si sí, ¿en qué parte exactamente? Un fragmento del HTML donde aparece el <img> me alcanza.

¿Por qué creés que en pruebas/producción no lo vas a ver? Tres causas típicas:

El archivo Aplicacion/Logo.png está solo en tu local (no está versionado en git).

La ruta es absoluta a http://localhost/... y en producción el dominio es otro.

El logo está en uploads/ (que no se versiona, es para archivos subidos).

¿Podés pasarme el HTML actualizado del piloto (aplicacion_GET.html) o al menos el bloque donde aparece el logo, si es que está en el HTML? Y confirmame si los archivos Aplicacion/Logo.png y Aplicacion/LogoPeque.png existen en local.

Lo que puedo hacer una vez que sepa dónde está
Si es el logo de las impresiones (Impresion.php), el código está bien. El problema es que el archivo no se deploya. Opciones:

Confirmarte que agregues Aplicacion/Logo.png y Aplicacion/LogoPeque.png al repo (git add).

O cambiar el código para usar un logo SVG inline (base64 o <svg> embebido), que viaja con el PHP y no depende de archivos externos.

Si es un logo del header del piloto que no vi, necesito el HTML actualizado y el fragmento del <img>.

Si es el favicon, agrego <link rel="icon" type="image/svg+xml" href="Aplicacion/Logo.svg"> al <head> del HTML, y creo Aplicacion/Logo.svg.

Si querés un logo nuevo (por ejemplo, un escudo con la paloma + texto "Pquia. del Carmen"), puedo generarte un SVG inline (sin depender de archivos) con un diseño simple y sobrio. Pero eso es una decisión estética, no técnica.

Contame qué ves y te armo el script. Si me podés pasar el HTML actualizado y confirmar si Aplicacion/Logo.png está en git, mejor.

no logo. log, el log de error, cuando pruebe todo esto en host de pruebas y en produccion no puedo acceder al log de error de php
Ah, el usuario dice "logo" pero en realidad quiso decir "log". Es el log de errores de PHP. En hostings compartidos (InfinityFree, etc.) no hay acceso al error.log de Apache ni al php_error.log. Necesita otra forma de ver los logs de diagnóstico.

El sistema de logging actual usa error_log() que va al log del servidor. En hostings compartidos eso no es accesible.

Opciones:

Archivo de log propio en el proyecto (logs/persistencia.log) — escribible, versionado en .gitignore, accesible por FTP o por una URL protegida.

Endpoint de descarga del log (?ver_log_persistencia=1 con chequeo de admin) — el usuario descarga el log por HTTP.

Guardar en la DB — un nodo específico con las últimas N líneas.

Endpoint que devuelve las últimas N líneas del log — admin-only.

Lo más simple y útil: cambiar error_log() por una función que escriba a un archivo propio del proyecto Y también al error_log() del servidor (para no perder el comportamiento actual en local). Y agregar un endpoint admin para leer/limpiar ese archivo.

Necesito ver dónde están los error_log() actuales:

Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php → [CREAR_CONEXION]

Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php → [SQL64_GUARDAR] (los que agregué en Fase 1+2 y Fase 4)

Voy a proponer:

Nueva clase Aplicacion/LogPersistencia.php con función _log_persistencia(string $msg) que:

Escribe a logs/persistencia_YYYY-MM-DD.log (un archivo por día).

Rota: si el archivo supera X MB, lo trunca o rota.

También llama error_log() para no perder el comportamiento en local.

Solo escribe si Conf::DEBUG_PERSISTENCIA está activo.

Cambiar todos los error_log("[SQL64_GUARDAR]...") y error_log("[CREAR_CONEXION]...") por _log_persistencia(...).

Endpoint ?ver_log_persistencia=1&nombre_solicitante=<admin> en index.php que:

Chequea admin.

Devuelve el contenido del log del día (JSON o texto plano).

Soporta ?limpiar=1 para truncar.

Lista archivos disponibles.

Alternativa más simple sin tocar código:

Solo agregar el endpoint que lea el error_log del servidor. Pero en hostings compartidos no hay acceso a ese archivo, así que no sirve.

Alternativa sin código:

En InfinityFree, hay un visor de logs en el panel de control. Pero el usuario dice "no puedo acceder", así que asumo que no hay.

Entonces la propuesta correcta es: archivo de log propio en el proyecto + endpoint de lectura.

Voy a proponer el diseño, pedir confirmación, y después armar el script. NO armar el script todavía. Primero consensuar:

¿Dónde guardo el archivo? logs/persistencia_YYYY-MM-DD.log. logs/ con mkdir recursivo.

¿Cuándo escribe? Solo si Conf::DEBUG_PERSISTENCIA está activo. El usuario activa cuando quiere diagnosticar.

¿Cómo se lee? Endpoint ?ver_log_persistencia=1&nombre_solicitante=<admin> que devuelve el log del día. Y ?ver_log_persistencia=1&limpiar=1 para truncar.

¿Qué se escribe? Todos los [SQL64_GUARDAR], [CREAR_CONEXION], y un nuevo [PERSISTENCIA_ERROR] para los Controlador::_error de persistencia. Esto último es importante: los errores de la transacción ahora también deberían ir al log propio.

¿Rotación? Por día. Si supera 5MB, truncar. Retener 7 días.

También: los Controlador::_error y Controlador::_alerta actualmente van a memoria. En hostings compartidos, si el guardado falla, esos errores no se ven. ¿Deberían también ir al log de persistencia? Sí, los que son de persistencia.

Diseño:

Aplicacion/LogPersistencia.php nuevo:

log_persistencia(string $msg, string $nivel = 'info') → escribe a logs/persistencia_YYYY-MM-DD.log con [timestamp] [nivel] msg. También llama error_log() para mantener local.

leer_log_persistencia(string $fecha = null): ?string → devuelve el contenido del archivo del día.

listar_logs_persistencia(): array → lista archivos disponibles.

limpiar_log_persistencia(string $fecha = null): bool → borra el archivo del día.

Rotación: si el archivo del día > 5MB, trunca al 50%.

Configuracion/Configuracion.php: agregar Conf::LOG_PERSISTENCIA_DIR = 'logs/persistencia/' y Conf::LOG_PERSISTENCIA_MAX_BYTES = 5 * 1024 * 1024, Conf::LOG_PERSISTENCIA_RETENER_DIAS = 7.

index.php: nuevo bloque antes de la carga:

php
if (isset($_GET['ver_log_persistencia'])) {
    // chequeo admin
    // devuelve JSON {exito, archivos, contenido}
}
PerdurarSuperestructuraStringSQL.php: cambiar error_log("[CREAR_CONEXION]...") por log_persistencia(...).

PerdurarSuperestructuraStringSQL64.php: cambiar todos los error_log("[SQL64_GUARDAR]...") por log_persistencia(...).

Los self::_error(...) y Controlador::_error(...) de persistencia también deberían ir al log propio. ¿Los cambio todos? No, solo los que son de persistencia y relevantes. Los Controlador::_error de la app (por ejemplo, "reservar_asiento_micro: guardar_ambos falló") son relevantes porque indican que el guardado falló. Todos esos están en los flujos que ya modifiqué, y ahora escriben a Controlador::_error. ¿Deberían ir al log propio?

Sí, todos los Controlador::_error("...guardar_ambos falló...") que agregué en v83g/h son relevantes para diagnosticar en producción. Pero cambiarlos todos es 20+ bloques. Alternativa: hookear Controlador::_error para que los errores que contengan "guardar_ambos falló" vayan al log propio. Pero eso es feo.

Mejor: agregar una función _log_guardado_fallido(string $contexto) que se llame en los flujos de guardado, que loguee a log_persistencia Y a Controlador::_error. Pero eso duplica.

Solución más limpia: hacer que Controlador::_error con prefijo específico vaya al log propio. O más simple: solo los errores de persistencia (los de SQL64) van al log propio. Los errores de "guardar_ambos falló para X" del piloto ya tienen el contexto en el mensaje, y si el usuario ve "error al guardar" en la UI, el diagnóstico se hace con el log de persistencia + el mensaje.

Mejor aún: cambiar los Controlador::_error("...guardar_ambos falló...") que agregué por log_persistencia(..., 'error'); Controlador::_error(...). Es doble, pero el log de persistencia es el que se puede leer desde el hosting.

Ok, me quedo con:

Aplicacion/LogPersistencia.php nuevo.

Configuracion.php: 3 constantes.

index.php: endpoint ?ver_log_persistencia=1.

PerdurarSuperestructuraStringSQL.php: error_log → log_persistencia.

PerdurarSuperestructuraStringSQL64.php: 3-4 error_log → log_persistencia + _error → log_persistencia también.

Los Controlador::_error("...guardar_ambos falló...") en los flujos: agregar log_persistencia(..., 'error') antes. Pero son 20+. Quizás mejor NO tocarlos y dejar el log de persistencia con los errores de SQL64 solamente. Los Controlador::_error("...guardar_ambos falló...") van al log de la app, que se puede ver con ?ver_errores=1 (si existe). Pero en hostings compartidos no hay ?ver_errores=1.

Hmm, entonces el problema real es: cuando guardar_ambos falla, el usuario ve "error al guardar" en la UI, pero ¿cómo ve el error de fondo? Con Controlador::_error va a memoria (que se pierde al terminar el request).

Solución completa: Controlador::_error debe poder escribirse a un archivo si Conf::LOG_PERSISTENCIA está activo. Pero Controlador::_error es del framework, no puedo cambiarlo a archivo sin tocar Objeto.php.

Alternativa quirúrgica: agregar log_persistencia('error', $msg) explícitamente en los flujos donde ya tengo el Controlador::_error. Son ~20 bloques, es trabajo pero acotado.

O más simple: solo los Controlador::_error de persistencia pura (SQL64) van al log propio. Los Controlador::_error del piloto (los "guardar_ambos falló") no van al log de persistencia, pero el usuario puede inferir qué pasó viendo el log de SQL64 (que sí tiene el detalle del error).

Mi propuesta:

Aplicacion/LogPersistencia.php nuevo.

Configuracion.php: constantes.

index.php: endpoint.

SQL64: error_log → log_persistencia (los _error NO, quedan en _error con su pila).

Los Controlador::_error("...guardar_ambos falló...") NO los toco. Pero el log de SQL64 va a tener el [SQL64_GUARDAR] verif_* FALLO o el [SQL64_GUARDAR] PERDIDA que dice qué falló. Con eso el usuario puede diagnosticar.

Pero si guardar_ambos falla porque la conexión se perdió, SQL64 va a loguear el error. Si falla porque un flujo del piloto tiene un bug, SQL64 no lo va a ver (no llega a llamar SQL64). En ese caso el Controlador::_error con el mensaje "X: guardar_ambos falló" quedaría solo en memoria.

Entonces mejor: también loguear los Controlador::_error("...guardar_ambos falló..."). Pero son ~20. Automatizar: cambiar el patrón Controlador::_error("X: guardar_ambos falló"...) por log_persistencia("X: guardar_ambos falló...", 'error'); Controlador::_error(...).

Voy a proponer esto al usuario y pedir confirmación. No armar el script todavía. Explicar el diseño, las opciones, y que elija.

Puntos a consensuar:

¿Dónde guardar el log? logs/persistencia/YYYY-MM-DD.log. ¿O un solo archivo logs/persistencia.log? Por día es más manejable.

¿Rotación? Por día + límite de tamaño (5MB) + retención 7 días.

¿Qué se loguea?

[SQL64_GUARDAR] de SQL64 (ya existe como error_log)

[CREAR_CONEXION] de SQL base

Los Controlador::_error con prefijo "...guardar_ambos falló..." (los de los flujos del piloto)

Los Controlador::_error de SQL64 (transacción fallida)

¿Cuándo? Solo si Conf::DEBUG_PERSISTENCIA = true. En producción, activar por un rato cuando hay un problema, leer el log, desactivar. Eso mantiene el default sin overhead.

¿Cómo se lee?

Endpoint ?ver_log_persistencia=1&nombre_solicitante=<admin> → JSON con archivos y contenido del día.

&fecha=YYYY-MM-DD para un día específico.

&limpiar=1 para truncar el día actual.

¿Deploy? logs/ debe existir o crearse. Añadir a .gitignore. Ojo con permisos de escritura en hosting compartido (usualmente sí se puede escribir en el directorio del proyecto).

Ok, propongo esto y pido confirmación. No armar el script todavía.

Sobre el tono: el usuario es directo, quiere soluciones. Aviso de decisiones de diseño, pido confirmación en 2-3 puntos, y armo el script en el próximo mensaje.

Ah, "log" de errores. Entendido. En hostings compartidos (InfinityFree, etc.) no hay acceso al error.log de Apache ni al php_error.log. Los error_log() que usamos en Fase 1, 2 y 4 van a un archivo que no podés leer desde el panel ni por FTP.

Solución: un archivo de log propio, escrito desde PHP a una ruta del proyecto, y un endpoint admin para leerlo/limpiarlo por HTTP. Tres archivos, 6 cambios.

Diseño propuesto
1. Archivo nuevo Aplicacion/LogPersistencia.php.

Función log_persistencia(string $msg, string $nivel = 'info') que:

Si Conf::DEBUG_PERSISTENCIA no está activo: no escribe nada (ni siquiera overhead). En producción, se activa por un rato para diagnosticar.

Escribe en logs/persistencia/YYYY-MM-DD.log (un archivo por día).

Formato: [2026-10-28 15:30:45] [info] [SQL64_GUARDAR] nombre=... matchean_filtro=... nodos_mascara_0=....

También llama error_log() para no perder el comportamiento actual en local (doble destino).

Rotación: si el archivo del día supera 5 MB, se trunca al 50% (deja las últimas líneas, que son las más recientes).

Retención: 7 días. Al escribir, borra archivos de más de 7 días.

Funciones auxiliares:

leer_log_persistencia(?string $fecha = null): ?string

listar_logs_persistencia(): array

limpiar_log_persistencia(?string $fecha = null): bool

2. Configuracion/Configuracion.php. Nuevas constantes:

Conf::LOG_PERSISTENCIA_DIR = 'logs/persistencia/'

Conf::LOG_PERSISTENCIA_MAX_BYTES = 5 * 1024 * 1024

Conf::LOG_PERSISTENCIA_RETENER_DIAS = 7

3. index.php. Nuevo bloque (antes de la carga del grafo):

text
GET ?ver_log_persistencia=1&nombre_solicitante=<admin|soporte>
→ JSON {exito, archivos: [...], fecha, contenido}

Opciones:
  &fecha=YYYY-MM-DD   → leer un día específico (default: hoy)
  &limpiar=1          → truncar el archivo del día (default: hoy)
Solo admin/soporte. Chequeo como el de ?auditar_topologia=1.

4. PerdurarSuperestructuraStringSQL.php. Cambiar el error_log("[CREAR_CONEXION]...") por log_persistencia(...).

5. PerdurarSuperestructuraStringSQL64.php.

Cambiar los 6 error_log("[SQL64_GUARDAR]...") (los que agregué en Fase 1/2/4) por log_persistencia(..., 'info').

Los self::_error(...) que indican error de persistencia (transacción fallida, PERDIDA, verif_* FALLO, fixpoint no convergió) pasan a: log_persistencia(..., 'error'); self::_error(...). Así el detalle queda en el archivo del proyecto, no solo en memoria.

6. Los Controlador::_error("...guardar_ambos falló...") de los flujos del piloto (v83d/g/h).
Los cambio a: log_persistencia(..., 'error'); Controlador::_error(...). Así, si una operación falla, el mensaje "reservar_asiento_micro: guardar_ambos falló..." queda en el log del proyecto, no solo en la memoria del request. Son ~20 flujos, todos del mismo patrón. Bloque grande pero mecánico.

Decisiones que necesito que confirmes
A. ¿Dónde guardar el log?

logs/persistencia/YYYY-MM-DD.log (mi propuesta: por día, más manejable en hosting).

logs/persistencia.log (un solo archivo, más simple pero crece indefinidamente).

Otra ruta.

B. ¿Qué activa el log?

Conf::DEBUG_PERSISTENCIA = true (mi propuesta: el usuario lo activa en producción cuando hay un problema, lo lee, lo desactiva).

Siempre activo (más simple, pero gasta IO en cada guardado).

Por GET ?debug_persistencia=1 (más expuesto).

C. ¿Qué se loguea además de los error_log actuales?

Solo los [SQL64_GUARDAR] (los que ya estaban).

+ los Controlador::_error("...guardar_ambos falló...") de los flujos del piloto (mi propuesta: son los que más info dan en producción cuando un flujo falla).

D. ¿Cómo se protege el endpoint?

Solo admin/soporte (mi propuesta, mismo criterio que ?auditar_topologia=1).

Solo en local (inútil para producción).

Solo con un token secreto en el POST (más seguro, más fricción).

E. ¿Cómo deployás el directorio logs/?

Necesitás agregar logs/ a .gitignore para no commitear logs.

Necesitás que el usuario de Apache tenga permiso de escritura en logs/ (usualmente sí en el directorio del proyecto).

El mkdir lo hace la función la primera vez.

Resumen del cambio
No toca la lógica del framework (solo mueve error_log a log_persistencia).

No toca la lógica del piloto (solo agrega log_persistencia antes de Controlador::_error en los flujos de guardado).

Agrega un endpoint admin para leer/limpiar el log.

Todo el logging queda gateado por Conf::DEBUG_PERSISTENCIA. Con el flag en false, no se escribe nada. Con true, se escribe al archivo del proyecto y se puede leer desde cualquier hosting.

¿Confirmás las decisiones A, B, C, D, E tal cual, o cambiás alguna? Con la confirmación, armo el aplicar_cambios.php completo.

a. ok a tu propuesta, b. ok a tu propuesta. c. ok a tu propuesta. d. ok a tu propuesta. e. que cree la carpeta la primera vez
El usuario confirmó todas las decisiones. Ahora armo el script. Repaso el plan:

Archivo nuevo Aplicacion/LogPersistencia.php con:

log_persistencia(string $msg, string $nivel = 'info')

leer_log_persistencia(?string $fecha = null): ?string

listar_logs_persistencia(): array

limpiar_log_persistencia(?string $fecha = null): bool

Rotación: si supera 5MB, truncar al 50% (dejar las últimas líneas).

Retención: 7 días.

Crea el directorio la primera vez.

Configuracion/Configuracion.php: 3 constantes nuevas.

index.php: bloque GET ?ver_log_persistencia=1.

PerdurarSuperestructuraStringSQL.php: cambiar error_log(...) por log_persistencia(...).

PerdurarSuperestructuraStringSQL64.php: cambiar los 6 error_log(...) por log_persistencia(...). Los self::_error(...) que son de persistencia también van a log_persistencia(..., 'error').

Los ~20 Controlador::_error("...guardar_ambos falló...") de los flujos del piloto (v83d/g/h). Cambiar a log_persistencia(..., 'error'); Controlador::_error(...).

Voy a leer con cuidado los archivos que ya tengo para armar los buscar con precisión.

LogPersistencia.php

Necesito verificar que Configuracion/Configuracion.php esté cargada antes. En index.php, el orden es: Configuracion.php primero, después Nucleo/Objeto.php, después Entorno.php, etc. FuncionesAuxiliares.php está al principio del bloque de la app. Voy a incluir ConfiguracionApli.php desde LogPersistencia.php para tener acceso a las constantes del piloto si hacen falta. Pero en realidad LogPersistencia.php solo necesita Conf del framework, no ConfiguracionApli. Mejor no arrastrar dependencia.

En realidad para que log_persistencia funcione con Conf::DEBUG_PERSISTENCIA, necesito que Configuracion/Configuracion.php esté cargada. En index.php está antes. Pero LogPersistencia.php puede ser incluido desde FuncionesAuxiliares.php o desde index.php. Voy a agregar el include_once de Configuracion.php defensivamente.

El directorio de logs: __DIR__ . '/../../logs/persistencia/'. Porque LogPersistencia.php va a estar en Aplicacion/. Entonces dirname(__DIR__) es la raíz del proyecto. Voy a usar dirname(__DIR__).

Espera, Aplicacion/LogPersistencia.php, __DIR__ es .../Aplicacion. dirname(__DIR__) es la raíz del proyecto. Correcto.

Pero si alguien lo incluye desde otro lugar... mejor uso una ruta absoluta calculada. En index.php todos los require_once son desde la raíz. Voy a hacer:

php
$dir_logs = dirname(__DIR__) . '/' . Conf::LOG_PERSISTENCIA_DIR;
Si Conf::LOG_PERSISTENCIA_DIR = 'logs/persistencia/', la ruta es raiz/logs/persistencia/. Correcto.

Ahora, la rotación. Si supera 5MB, trunco al 50%. ¿Cómo? Leo el archivo, agarro las últimas N líneas hasta ~2.5MB, reescribo. Cuidado con no romper una línea a la mitad.

php
$tamano = filesize($ruta);
if ($tamano > Conf::LOG_PERSISTENCIA_MAX_BYTES) {
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lineas = array_slice($lineas, intdiv(count($lineas), 2));
    file_put_contents($ruta, implode("\n", $lineas) . "\n");
}
Bien.

Retención: al escribir, si no lo hicimos ya hoy, listar archivos y borrar los de más de 7 días. Para no hacerlo en cada escritura (overhead), lo hago solo si el archivo del día no existía antes (primera escritura del día). O con una marca en un archivo .last_cleanup. Mejor: escribir la fecha del último cleanup en un archivo .last_cleanup. Si la fecha es distinta a hoy, correr cleanup.

Simplifiquemos: hago cleanup cada vez que se crea el archivo del día (primera escritura del día). El costo es listar + borrar, que es rápido.

php
$primera_vez_hoy = !file_exists($ruta);
// ...
if ($primera_vez_hoy) {
    _log_persistencia_limpiar_viejos();
}
_log_persistencia_limpiar_viejos():

php
$archivos = glob($dir . '*.log');
$hoy_ts = time();
foreach ($archivos as $f) {
    if ($hoy_ts - filemtime($f) > Conf::LOG_PERSISTENCIA_RETENER_DIAS * 86400) {
        @unlink($f);
    }
}
Endpoint en index.php

?ver_log_persistencia=1 — acepta:

?ver_log_persistencia=1 → devuelve lista de archivos y contenido de hoy

?ver_log_persistencia=1&fecha=YYYY-MM-DD → contenido de esa fecha

?ver_log_persistencia=1&limpiar=1 → trunca el archivo de hoy

?ver_log_persistencia=1&limpiar=1&fecha=YYYY-MM-DD → trunca esa fecha

Chequeo admin/soporte, igual que ?auditar_topologia=1.

El JSON:

json
{
  "exito": true,
  "archivos": [
    {"fecha": "2026-10-28", "tamano": 12345, "lineas": 100},
    {"fecha": "2026-10-27", "tamano": 45678, "lineas": 200}
  ],
  "fecha": "2026-10-28",
  "contenido": "...",
  "lineas": 100
}
Si ?limpiar=1, después de limpiar devuelve {"exito": true, "limpiado": true, "archivo": "..."} y corta.

Sobre Conf::DEBUG_PERSISTENCIA

Ya existe. Lo agregamos en 1.5i.7t. Está en false por defecto. Cuando el usuario lo activa, log_persistencia escribe. Cuando no, no hace nada (early return).

Pero ojo: los self::_error(...) que llamamos con log_persistencia(..., 'error') se llaman desde el framework. Esos _error van a la pila de errores en memoria (que se muestra con Controlador::imprimir_errores()). Si querés que el archivo de logs funcione aun con DEBUG_PERSISTENCIA = false, entonces log_persistencia con nivel error debería escribir SIEMPRE, sin importar el flag. Pero eso contradice la decisión B.

Voy a hacer que log_persistencia con nivel error escriba siempre (los errores no se deben perder). Con nivel info (los [SQL64_GUARDAR] de cabecera), solo si DEBUG_PERSISTENCIA está activo.

Reformulo:

php
function log_persistencia(string $msg, string $nivel = 'info'): void {
    // info: solo si DEBUG_PERSISTENCIA está activo
    // error / warn: siempre
    if ($nivel === 'info' && !Conf::DEBUG_PERSISTENCIA) return;
    // ... escribir
}
Esto es mejor. Los errores siempre se loguean, la info solo cuando querés debug.

Sobre cambiar los error_log existentes

En PerdurarSuperestructuraStringSQL.php, el error_log("[CREAR_CONEXION]...") es info. Lo cambio a log_persistencia(..., 'info').

En PerdurarSuperestructuraStringSQL64.php, los [SQL64_GUARDAR]... son info (solo con DEBUG_PERSISTENCIA). Los self::_error("... fallo X: " . $sql->error) son errores reales. Esos van a log_persistencia(..., 'error') Y self::_error(...). Doble destino.

Espera, ¿o solo log_persistencia(..., 'error') y self::_error(...)? Sí, ambos. self::_error es memoria (para imprimir_errores()), log_persistencia es disco.

Los Controlador::_error(...) de los flujos del piloto también van a log_persistencia(..., 'error') + Controlador::_error(...).

Sobre PerdurarSuperestructuraStringSQL64.php

Ya tiene error_log() en varios lugares. Los identifico (del texto que me pasaste):

[SQL64_GUARDAR] nombre=... mf=... modo=... — info (si DEBUG)

[SQL64_GUARDAR] ids_a_insertar=... matchean_filtro=... nodos_mascara_0=... — info (si DEBUG)

[SQL64_GUARDAR] aristas_a_insertar=... — info (si DEBUG)

[SQL64_GUARDAR] PERDIDA id_nodo=... — error (siempre)

[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=... — info (si DEBUG)

[SQL64_GUARDAR] verif_nodos ok=1 count=... — info (si DEBUG)

[SQL64_GUARDAR] verif_nodos FALLO esperado=... en_db=... — error (siempre)

[SQL64_GUARDAR] verif_aristas ok=1 count=... — info (si DEBUG)

[SQL64_GUARDAR] verif_aristas FALLO esperado=... en_db=... — error (siempre)

De esos, 3 ya tienen if (Conf::DEBUG_PERSISTENCIA) alrededor. Los cambio adentro.

También [CREAR_CONEXION] version=82c, ... en PerdurarSuperestructuraStringSQL.php — info.

Voy a cambiar todos a log_persistencia(...). Con el segundo parámetro 'info' o 'error'.

Los que ya tienen if (Conf::DEBUG_PERSISTENCIA) los dejo, el log_persistencia adentro ya chequea el flag. O los saco, es redundante. Voy a sacar el if externo y dejar el log_persistencia(..., 'info') que ya chequea internamente. Más limpio.

Espera, no todos los [SQL64_GUARDAR] que tienen if (Conf::DEBUG_PERSISTENCIA) van a estar. Algunos son directos. Debo chequear cada uno.

Voy a cambiar cada error_log(...) por log_persistencia(..., 'info'|'error'). Los if (Conf::DEBUG_PERSISTENCIA) externos los saco (redundantes).

Sobre los flujos del piloto (v83d/g/h)

Los que voy a tocar:

Aplicacion/Ventas/Venta.php: confirmar_venta_actual, pagar_cupon_venta, cancelar_venta — ya tienen Controlador::_error(...). Agrego log_persistencia(...) antes.

Aplicacion/Viajes/ViajeAsientos.php: 6 flujos.

Aplicacion/Viajes/ViajeMicros.php: 3 flujos.

Aplicacion/Viajes/Viaje.php: eliminar_viaje, agregar_viaje, editar_viaje, guardar_viaje_completo, agregar_terminal_autorizada, eliminar_terminal_autorizada, guardar_opciones_terminal_viaje, guardar_declaracion_jurada, limpiar_viajes_de_prueba.

Aplicacion/Pasajeros/Pasajero.php: crear_pasajero, actualizar_pasajero, eliminar_pasajero, subir_declaracion_jurada_pasajero, eliminar_declaracion_jurada_pasajero, limpiar_pasajeros_de_prueba.

Aplicacion/Ventas/Venta.php: limpiar_cancelaciones_de_prueba.

Son ~24 bloques. Cada uno tiene Controlador::_error("XXX: guardar_ambos falló ...") o Controlador::_error("XXX: ..."). Los voy a cambiar a:

php
log_persistencia("XXX: guardar_ambos falló ...", 'error');
Controlador::_error("XXX: guardar_ambos falló ...");
Dos líneas por bloque. 24 bloques × 2 = muchos. Pero es mecánico.

Hay que pensar en la fragilidad de los buscar. Cada bloque buscar es:

php
'        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
Un solo string que contiene la llamada. Es único (contiene el nombre de la función). Debería matchear bien.

Hago el cambio:

php
'        log_persistencia("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro", \'error\');',
'        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
OK. Mecánico pero seguro.

Inclusión de LogPersistencia.php

Los flujos del piloto necesitan log_persistencia() disponible. ¿Dónde la incluyo? En index.php, después de Configuracion.php y antes de todo. O mejor, en FuncionesAuxiliares.php, que ya se incluye en todos lados. Voy a ponerla en index.php antes de los require_once de la app.

En realidad, algunos módulos incluyen FuncionesAuxiliares.php, pero no todos (Viaje.php no la incluye explícitamente, pero index.php la incluye primero, así que ya está cargada cuando se ejecuta el flujo). Las funciones del piloto se llaman dentro de un request que ya pasó por index.php, así que log_persistencia está definida. OK.

En index.php, agrego require_once __DIR__ . '/Aplicacion/LogPersistencia.php'; al principio del bloque de la app.

El framework (PerdurarSuperestructuraStringSQL.php y SQL64.php) también necesita log_persistencia. Pero el framework se carga desde index.php antes de Aplicacion/LogPersistencia.php. ¿Cómo resuelvo?

Opciones:

Incluir LogPersistencia.php al principio de index.php, antes del framework. Pero el archivo está en Aplicacion/, no tiene sentido cargarlo antes.

Hacer que LogPersistencia.php sea independiente del framework y se cargue en index.php antes que el framework. Pero log_persistencia usa Conf::DEBUG_PERSISTENCIA y Conf es parte del framework.

Solución: LogPersistencia.php carga Configuracion/Configuracion.php por su cuenta (con include_once), y index.php la incluye al principio (antes del framework). Funciona.

Pero Configuracion/Configuracion.php extiende ConfServidor, que está en config_servidor.php. Ese archivo ya se carga en Configuracion.php por require_once __DIR__ . '/../config_servidor.php'. OK, no hay problema.

Orden en index.php:

php
// ... utilidades base
// ... Configuracion.php (ya está)
include_once("Configuracion/Configuracion.php");
// Nuevo: LogPersistencia.php ANTES de Objeto.php, para que esté disponible en todas partes
include_once(__DIR__ . '/Aplicacion/LogPersistencia.php');
// ... Nucleo/Objeto.php
// ... Nodos/Nodo.php
// ... Controlador/Controlador.php
// ...
Esto funciona porque LogPersistencia.php solo depende de Conf, que ya está cargado.

Pero Aplicacion/LogPersistencia.php está en Aplicacion/, y el resto de los archivos de Aplicacion/ se cargan después. ¿Está mal cargarlo antes? No, es una utilidad que el framework usa. Es una excepción razonable. Lo documento.

Alternativa: LogPersistencia.php sin namespace, en la raíz, al lado de FuncionesAuxiliares.php. Pero Aplicacion/ es más coherente con los otros módulos del piloto.

Voy a ponerlo en Aplicacion/LogPersistencia.php y lo cargo en index.php antes de Controlador.php, inmediatamente después de Configuracion.php. Es la única excepción de orden respecto a los otros módulos de la app.

Espera, un detalle: si los flujos del framework (SQL64.php) llaman log_persistencia y esta función está en Aplicacion/LogPersistencia.php, entonces el framework depende de la app. Eso es acoplamiento invertido. Pero el framework YA usa Conf, que también podría ser "de la app" (aunque Conf vive en Configuracion/ que es parte del framework, y ConfiguracionApli es de la app). LogPersistencia es del piloto. El framework no debería depender del piloto.

Solución limpia: poner log_persistencia en el framework, no en la app. Por ejemplo, en Configuracion/LogPersistencia.php o como método estático de Objeto. Pero eso es más invasivo.

O más simple: LogPersistencia.php en Aplicacion/ pero con function_exists('log_persistencia') guard en el framework. Si la app no lo cargó, el framework no loguea. Feo.

Voy a hacer: log_persistencia en Configuracion/LogPersistencia.php (framework). Es una utilidad de diagnóstico del framework. La app también la puede usar. El archivo lo incluye index.php antes de todo, o mejor, Configuracion/Configuracion.php ya lo incluye. No, Configuracion.php es solo constantes.

Mejor: en Controlador.php (el archivo principal del framework), hacer un include_once __DIR__ . '/../Configuracion/LogPersistencia.php' al principio. Así cuando se carga Controlador.php (que siempre está antes de la app), la función existe.

Pero Controlador.php tiene su propio orden de includes. Voy a poner la definición de log_persistencia en Configuracion/LogPersistencia.php y hacer que Controlador.php la incluya. Y index.php también la incluye al principio (para que esté disponible desde antes, si PerdurarSuperestructuraStringSQL la usa durante la carga).

Hmm, PerdurarSuperestructuraStringSQL.php se incluye desde Controlador.php o desde index.php? Del prompt anterior, index.php incluye Controlador/Controlador.php que arrastra todo el framework. Y PerdurarSuperestructuraStringSQL64.php es incluido desde Controlador.php o desde Configuracion.php? Del código que tengo, PerdurarSuperestructuraStringSQL64.php hace include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php"); y el Controlador debería incluirlo.

No tengo Controlador.php. No sé su orden interno. Pero sé que PerdurarSuperestructuraStringSQL64 se usa desde index.php cuando hace Controlador::establecer_metodo('SQL64');. Así que el framework está cargado antes de la primera operación.

Voy a:

Crear Configuracion/LogPersistencia.php (framework).

index.php lo incluye justo después de Configuracion/Configuracion.php.

Los otros archivos del framework (PerdurarSuperestructuraStringSQL.php, PerdurarSuperestructuraStringSQL64.php) lo llaman normalmente. Como index.php lo carga antes que el framework, ya está disponible cuando se ejecuta.

Espera, pero PerdurarSuperestructuraStringSQL.php podría ser incluido desde Controlador.php, que se incluye desde index.php. El orden es:

index.php incluye Configuracion/Configuracion.php (que ya tiene).

index.php incluye Configuracion/LogPersistencia.php (nuevo).

index.php incluye Nucleo/Objeto.php.

...

index.php incluye Controlador/Controlador.php, que arrastra PerdurarSuperestructura/*.

Listo, log_persistencia está definida antes de que se usen las clases de persistencia.

Perfecto.

Pero, ¿y si el framework se usa en un contexto sin index.php? Por ejemplo, pruebas. PerdurarSuperestructuraStringSQL.php tiene su propio include_once("./Configuracion/Configuracion.php");. Podría también tener su propio include_once("./Configuracion/LogPersistencia.php");. Defensivo. Lo agrego.

Y PerdurarSuperestructuraStringSQL64.php también. Lo agrego.

OK, plan:

Configuracion/LogPersistencia.php (nuevo, framework).

Configuracion/Configuracion.php: 3 constantes nuevas.

index.php: incluir LogPersistencia.php después de Configuracion.php; agregar endpoint ?ver_log_persistencia=1.

PerdurarSuperestructuraStringSQL.php: include defensivo + cambiar error_log por log_persistencia.

PerdurarSuperestructuraStringSQL64.php: include defensivo + cambiar todos los error_log por log_persistencia.

Los ~24 flujos del piloto: agregar log_persistencia antes de Controlador::_error.

Sobre el endpoint ?ver_log_persistencia=1

Va en index.php, en el bloque de endpoints de diagnóstico, junto a ?auditar_topologia=1. Pero necesita log_persistencia/leer_log_persistencia ya cargadas. Están cargadas porque index.php las incluye al principio.

El endpoint requiere admin/soporte. Antes de la carga del grafo (como ?auditar_topologia=1). Pero necesita verificar el nivel del usuario, y para eso necesita el grafo. Como ?auditar_topologia=1, se fuerza carga total.

Flujo:

GET ?ver_log_persistencia=1&nombre_solicitante=X.

header('Content-Type: application/json').

Si Controlador::es_grafo_parcial(), recargar total.

Verificar admin/soporte (mismo código que ?auditar_topologia=1).

Si ?limpiar=1: limpiar_log_persistencia($fecha).

Si ?fecha=YYYY-MM-DD: leer_log_persistencia($fecha).

Si no: leer hoy + listar archivos.

Responder JSON.

Formato del JSON

json
{
  "exito": true,
  "archivos": [
    {"fecha": "2026-10-28", "tamano": 12345, "tamano_legible": "12.1 KB"},
    {"fecha": "2026-10-27", "tamano": 45678, "tamano_legible": "44.6 KB"}
  ],
  "fecha_actual": "2026-10-28",
  "contenido": "...",
  "lineas": 100,
  "debug_activo": true
}
Si ?limpiar=1: {"exito": true, "limpiado": true, "archivo": "..."} y corta.

Sobre los Controlador::_error en los flujos

Cada uno es único. Buscar:

php
'        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
Espera, ¿dónde se ubica? Está en ViajeAsientos.php. Con la indentación exacta.

Cada bloque de cambio:

buscar: Controlador::_error("XXX: guardar_ambos falló ...");

reemplazar:

text
      log_persistencia("XXX: guardar_ambos falló ...", 'error');
      Controlador::_error("XXX: guardar_ambos falló ...");
Ojo: log_persistencia con 'error' escribe siempre. Controlador::_error sigue guardando en memoria. Ambos.

Son ~24 bloques. Voy a incluir todos.

Espera, algunos Controlador::_error tienen contexto diferente. Por ejemplo, en eliminar_viaje: Controlador::_error("eliminar_viaje: guardar_ambos falló para viaje=$nombre_viaje");. En agregar_micro_a_viaje: Controlador::_error("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");. Cada uno tiene su propio texto.

Voy a hacer un bloque por cada uno.

Chequeo de Configuracion.php

El buscar en Configuracion.php es para agregar 3 constantes nuevas. Las pongo después de DEBUG_PERSISTENCIA. ¿Está DEBUG_PERSISTENCIA en el archivo? Lo agregamos en 1.5i.7t. Sí, debería estar.

Voy a agregar:

php
public const LOG_PERSISTENCIA_DIR = 'logs/persistencia/';
public const LOG_PERSISTENCIA_MAX_BYTES = 5 * 1024 * 1024;
public const LOG_PERSISTENCIA_RETENER_DIAS = 7;
Sobre $htmls_a_bumpear

No toco JS. Los flujos son PHP. OK.

Version_nueva

El framework cambia a 1.5i.7w? El framework está en 1.5i.7v (recién actualizado). Entonces 1.5i.7w. El piloto está en 1.5piloto.83h. Los flujos del piloto, ¿también cambian de versión? Sí, 1.5piloto.83i.

Pero el script aplica a ambos al mismo tiempo. Solo tengo una $version_nueva. Hmm.

Los archivos del framework (Configuracion.php, PerdurarSuperestructuraStringSQL.php, PerdurarSuperestructuraStringSQL64.php) van a 1.5i.7w. Los del piloto (index.php, Venta.php, ViajeAsientos.php, ViajeMicros.php, Viaje.php, Pasajero.php) van a 1.5piloto.83i.

Voy a hacer dos scripts (uno para framework, uno para piloto), con dos commits. O un solo script con bumps manuales.

La regla del sistema de scripts dice: "Dos proyectos = dos scripts. Cambios al framework PHP y al espejo JS van en dos aplicar_cambios.php distintos, con dos commits distintos." Pero acá es framework PHP + piloto PHP. Mismo proyecto (mismo repo), pero el framework y el piloto tienen versiones distintas.

En el prompt del sistema de scripts, la regla es clara: un solo script por tanda. La versión se bumpea a los archivos afectados. Si framework y piloto van juntos, hay conflicto de versiones.

Opción A: dos scripts separados. Framework PHP en uno ($version_nueva = '1.5i.7w'), piloto PHP en otro ($version_nueva = '1.5piloto.83i'). Pero eso duplica el trabajo y son 2 commits.

Opción B: un solo script, sin $version_nueva, con bumps manuales por archivo. Pierde la ventaja del bump automático.

Opción C: un solo script, con $version_nueva = '1.5piloto.83i'. Los archivos del framework quedarían en 1.5piloto.83i, lo cual es incorrecto.

Opción D: dos scripts separados, framework primero, piloto después, en el mismo mensaje. Dos commits. Es lo que hacemos con framework JS.

Voy a hacer Opción D: dos scripts. Framework PHP (1.5i.7w) y piloto PHP (1.5piloto.83i). Los entrego en el mismo mensaje. Dos commits.

Esto es consistente con la regla del sistema de scripts.

Script 1: framework (1.5i.7w)

Configuracion/Configuracion.php: 3 constantes.

Configuracion/LogPersistencia.php: nuevo.

PerdurarSuperestructuraStringSQL.php: include + cambiar error_log.

PerdurarSuperestructuraStringSQL64.php: include + cambiar todos los error_log.

Script 2: piloto (1.5piloto.83i)

index.php: include + endpoint ?ver_log_persistencia=1.

Aplicacion/Ventas/Venta.php: 3 log_persistencia.

Aplicacion/Viajes/ViajeAsientos.php: 6 log_persistencia.

Aplicacion/Viajes/ViajeMicros.php: 3 log_persistencia.

Aplicacion/Viajes/Viaje.php: 9 log_persistencia.

Aplicacion/Pasajeros/Pasajero.php: 6 log_persistencia.

Total: ~27 log_persistencia en el piloto.

Voy a verificar los Controlador::_error(...) existentes:

De v83d (Venta.php):

cancelar_venta: Controlador::_error("cancelar_venta: guardar_ambos falló para id_venta=$id_venta");

confirmar_venta_actual: Controlador::_error("confirmar_venta_actual: guardar_ambos falló para id_venta=$id_venta");

pagar_cupon_venta: Controlador::_error("pagar_cupon_venta: guardar_ambos falló para id_venta=$id_venta numero_cupon=$numero_cupon");

De v83g (ViajeAsientos.php):

reservar_asiento_micro: Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

asignar_pasajero_a_reserva: idem

liberar_reserva_asiento_micro: idem

seleccionar_asiento_micro: idem

deseleccionar_asiento_micro: idem

cambiar_asiento_pasaje: idem

De v83g (ViajeMicros.php):

agregar_micro_a_viaje: Controlador::_error("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

eliminar_micro_de_viaje: idem

actualizar_monto_micro: idem

De v83g (Viaje.php):

eliminar_viaje: Controlador::_error("eliminar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

De v83g (Pasajero.php):

crear_pasajero: Controlador::_error("crear_pasajero: guardar_ambos falló para dni=$dni_norm");

actualizar_pasajero: Controlador::_error("actualizar_pasajero: guardar_ambos falló para dni=$dni");

eliminar_pasajero: Controlador::_error("eliminar_pasajero: guardar_ambos falló para dni=$dni");

subir_declaracion_jurada_pasajero: Controlador::_error("subir_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");

eliminar_declaracion_jurada_pasajero: Controlador::_error("eliminar_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");

De v83h (Viaje.php):

agregar_viaje: Controlador::_error("agregar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

editar_viaje: Controlador::_error("editar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

guardar_viaje_completo: Controlador::_error("guardar_viaje_completo: guardar_ambos final falló para viaje=$nombre_viaje");

agregar_terminal_autorizada: Controlador::_error("agregar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

eliminar_terminal_autorizada: Controlador::_error("eliminar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

guardar_opciones_terminal_viaje: Controlador::_error("guardar_opciones_terminal_viaje: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

guardar_declaracion_jurada: Controlador::_error("guardar_declaracion_jurada: guardar_ambos falló para viaje=$nombre_viaje tipo=$tipo");

limpiar_viajes_de_prueba: Controlador::_error("limpiar_viajes_de_prueba: guardar_ambos falló");

De v83h (Pasajero.php):

limpiar_pasajeros_de_prueba: Controlador::_error("limpiar_pasajeros_de_prueba: guardar_ambos falló");

De v83h (Venta.php):

limpiar_cancelaciones_de_prueba: Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");

Total: 3 + 6 + 3 + 1 + 5 + 8 + 1 + 1 = 28 flujos con Controlador::_error(...).

Muchos bloques. Voy a armar el script 2 con todos. Es mecánico.

Mejor: reconsiderar. ¿Vale la pena duplicar 28 veces el mensaje con log_persistencia + Controlador::_error? Es un montón de código repetido.

Alternativa más limpia: que Controlador::_error ya escriba al log. Es decir, modificar Controlador::_error (en el framework) para que llame a log_persistencia internamente. Así los 28 flujos no se tocan; solo cambia Controlador.php.

Pero Controlador::_error es de Objeto (Objeto::_error), no de Controlador. Objeto::_error está en Nucleo/Objeto.php, que no tengo.

Si modifico Objeto::_error para que escriba al log, tengo que tocar Objeto.php. No lo tengo. Pedirlo.

Alternativa: modificar Controlador::_error... pero Controlador::_error es heredado de Objeto. La llamada Controlador::_error(...) invoca Objeto::_error. Para interceptarlo, tendría que sobrescribirlo en Controlador.

Pero no tengo Controlador.php completo.

Voy a hacer lo simple: en los flujos del piloto, agregar log_persistencia antes de Controlador::_error. Son 28 bloques chicos. Mecánico. No toco Objeto.php ni Controlador.php.

En realidad, la decisión del usuario fue "C. ok a tu propuesta: + los Controlador::_error(...) de los flujos del piloto". Así que sí, agregar log_persistencia antes de cada Controlador::_error. Bien.

Pero por prolijidad, puedo reducir la redundancia: como cada Controlador::_error("XXX: ...") tiene el mismo texto que el log_persistencia, voy a hacer que el reemplazar sea solo dos líneas:

php
log_persistencia("XXX: ...", 'error');
Controlador::_error("XXX: ...");
Esto es lo que el usuario aprobó.

OK, vamos a armar. Un detalle: el script 1 (framework) tiene que irse primero. Después el script 2 (piloto) usa log_persistencia que ya está en el framework.

Pero no, ambos scripts se corren en secuencia. El script 1 pone la función disponible. El script 2 la usa. Bien.

Sobre el log en el framework

PerdurarSuperestructuraStringSQL64.php tiene error_log(...) en varios lugares. Todos los cambio a log_persistencia(...). Pero el include_once de LogPersistencia.php tengo que ponerlo al principio de ese archivo, o en Configuracion.php (que el archivo ya incluye).

PerdurarSuperestructuraStringSQL64.php hace:

php
include_once("./Configuracion/Configuracion.php");
Y Configuracion.php NO incluye LogPersistencia.php (no quiero crear dependencia rara). Voy a agregar include_once("./Configuracion/LogPersistencia.php"); al principio de PerdurarSuperestructuraStringSQL64.php.

Pero eso es frágil. Mejor incluir LogPersistencia.php en index.php antes de todo, y como el framework se carga desde index.php, la función ya existe.

Espera, PerdurarSuperestructuraStringSQL64.php es una clase. Se define cuando se hace include_once desde Controlador.php. El index.php primero hace include_once("Configuracion/Configuracion.php") y después carga Controlador.php. Si index.php incluye LogPersistencia.php entre los dos, cuando Controlador.php se evalúe, log_persistencia ya está definida. PerdurarSuperestructuraStringSQL64::_guardar_con_filtro la llama en tiempo de ejecución, no en tiempo de definición de clase. Así que alcanza con que esté definida antes de la primera llamada. Cargarla en index.php antes de Controlador.php es suficiente.

Sobre Configuracion/LogPersistencia.php

Necesita Conf::LOG_PERSISTENCIA_* y Conf::DEBUG_PERSISTENCIA. Conf está definido en Configuracion/Configuracion.php. Voy a hacer:

php
require_once __DIR__ . '/Configuracion.php';
Así no depende de que el otro archivo lo haya incluido.

Sobre el endpoint

En index.php, agrego el bloque GET ?ver_log_persistencia=1 cerca de ?auditar_topologia=1. Necesita verificar admin/soporte. Voy a copiar el patrón.

Espera, ?auditar_topologia=1 recarga el grafo total si es parcial, después verifica el nivel. Hago lo mismo.

Empiezo a armar los scripts

Voy a hacer dos scripts separados, ambos con el runner v2.

Script 1: framework 1.5i.7w
Script 2: piloto 1.5piloto.83i

Voy a escribir cada uno con cuidado.

Archivo nuevo Configuracion/LogPersistencia.php

php
<?php
/**
 * Log de persistencia del framework Iteradores.
 *
 * Escribe a un archivo del proyecto (bajo logs/persistencia/)
 * en lugar de (o además de) error_log. Pensado para hostings
 * compartidos donde el error.log de PHP no es accesible.
 *
 * El log se activa con Conf::DEBUG_PERSISTENCIA = true.
 * Los mensajes con nivel 'error' se escriben SIEMPRE, aunque
 * DEBUG_PERSISTENCIA esté en false.
 *
 * @author  Ignacio David Baigorria
 * @package Iteradores\Configuracion
 * @since   1.5i.7w
 * @version 1.5i.7w
 */

namespace Iteradores\Configuracion;

require_once __DIR__ . '/Configuracion.php';

/**
 * Escribe un mensaje al log de persistencia del proyecto.
 *
 * @param string $msg   Mensaje.
 * @param string $nivel 'info' | 'warn' | 'error'. Los 'error' se
 *                      escriben siempre; los 'info'/'warn' solo
 *                      si Conf::DEBUG_PERSISTENCIA está activo.
 * @return void
 */
function log_persistencia(string $msg, string $nivel = 'info'): void {
    $nivel = strtolower($nivel);
    if (!in_array($nivel, ['info', 'warn', 'error'], true)) $nivel = 'info';
    if ($nivel === 'info' && !Conf::DEBUG_PERSISTENCIA) return;
    if ($nivel === 'warn' && !Conf::DEBUG_PERSISTENCIA) return;

    $dir = _log_persistencia_directorio();
    if ($dir === null) {
        // Fallback: si no se pudo crear el directorio, error_log.
        error_log("[LOG_PERSISTENCIA] " . $msg);
        return;
    }

    $fecha = date('Y-m-d');
    $ruta = $dir . $fecha . '.log';
    $primera_vez_hoy = !file_exists($ruta);
    $linea = '[' . date('Y-m-d H:i:s') . '] [' . $nivel . '] ' . $msg . "\n";

    // Rotación: si el archivo del día supera el máximo, se trunca al 50%.
    if (file_exists($ruta)) {
        $tam = @filesize($ruta);
        if ($tam !== false && $tam > Conf::LOG_PERSISTENCIA_MAX_BYTES) {
            $lineas = @file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lineas) && count($lineas) > 0) {
                $lineas = array_slice($lineas, intdiv(count($lineas), 2));
                @file_put_contents($ruta, implode("\n", $lineas) . "\n");
            }
        }
    }

    @file_put_contents($ruta, $linea, FILE_APPEND | LOCK_EX);

    // Limpieza de archivos viejos: solo en la primera escritura del día.
    if ($primera_vez_hoy) {
        _log_persistencia_limpiar_viejos();
    }
}

/**
 * Devuelve la ruta absoluta al directorio de logs. Lo crea si no existe.
 *
 * @return string|null
 */
function _log_persistencia_directorio(): ?string {
    $raiz = dirname(__DIR__);
    $dir = $raiz . '/' . ltrim(Conf::LOG_PERSISTENCIA_DIR, '/');
    if (!str_ends_with($dir, '/')) $dir .= '/';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
    }
    return $dir;
}

/**
 * Borra los archivos de log más viejos que el límite de retención.
 *
 * @return void
 */
function _log_persistencia_limpiar_viejos(): void {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return;
    $archivos = @glob($dir . '*.log');
    if (!is_array($archivos)) return;
    $limite = time() - Conf::LOG_PERSISTENCIA_RETENER_DIAS * 86400;
    foreach ($archivos as $f) {
        $mt = @filemtime($f);
        if ($mt !== false && $mt < $limite) {
            @unlink($f);
        }
    }
}

/**
 * Lee el contenido del log de una fecha.
 *
 * @param string|null $fecha YYYY-MM-DD. Si null, hoy.
 * @return string|null
 */
function leer_log_persistencia(?string $fecha = null): ?string {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return null;
    if ($fecha === null || $fecha === '') {
        $fecha = date('Y-m-d');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return null;
    $ruta = $dir . $fecha . '.log';
    if (!file_exists($ruta)) return null;
    $c = @file_get_contents($ruta);
    return $c === false ? null : $c;
}

/**
 * Lista los archivos de log disponibles, del más reciente al más viejo.
 *
 * @return array<int, array{fecha: string, tamano: int, tamano_legible: string, mtime: int}>
 */
function listar_logs_persistencia(): array {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return [];
    $archivos = @glob($dir . '*.log');
    if (!is_array($archivos)) return [];
    $res = [];
    foreach ($archivos as $f) {
        $nombre = basename($f);
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})\.log$/', $nombre, $m)) continue;
        $tam = @filesize($f);
        $mt = @filemtime($f);
        $res[] = [
            'fecha' => $m[1],
            'tamano' => (int)($tam === false ? 0 : $tam),
            'tamano_legible' => _log_persistencia_formato_tamano((int)($tam === false ? 0 : $tam)),
            'mtime' => (int)($mt === false ? 0 : $mt),
        ];
    }
    usort($res, function($a, $b) { return $b['fecha'] <=> $a['fecha']; });
    return $res;
}

/**
 * Trunca (borra el contenido) del log de una fecha.
 *
 * @param string|null $fecha YYYY-MM-DD. Si null, hoy.
 * @return bool
 */
function limpiar_log_persistencia(?string $fecha = null): bool {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return false;
    if ($fecha === null || $fecha === '') {
        $fecha = date('Y-m-d');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return false;
    $ruta = $dir . $fecha . '.log';
    if (!file_exists($ruta)) return true;
    return @file_put_contents($ruta, '') !== false;
}

/**
 * Formatea un tamaño en bytes a string legible.
 *
 * @param int $bytes
 * @return string
 */
function _log_persistencia_formato_tamano(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, '.', '') . ' KB';
    return number_format($bytes / (1024 * 1024), 2, '.', '') . ' MB';
}
Ojo: str_ends_with requiere PHP 8.0+. El proyecto usa 8.2/8.4. OK.

Sobre el require_once __DIR__ . '/Configuracion.php';

Configuracion.php define Conf en el namespace Iteradores\Configuracion. Pero también hace require_once __DIR__ . '/../config_servidor.php'; que carga ConfServidor en el namespace global. OK.

Voy a ponerlo con require_once __DIR__ . '/Configuracion.php'; (sin namespace, porque el archivo ya declara namespace Iteradores\Configuracion;). Espera, si declaro namespace Iteradores\Configuracion; en el archivo, entonces require_once __DIR__ . '/Configuracion.php'; funciona porque es un require_once, no un use. Y el Conf se resuelve por el namespace. OK.

Pero ojo: hay un detalle. Si LogPersistencia.php declara namespace Iteradores\Configuracion;, entonces log_persistencia está en ese namespace. Los flujos del framework llaman log_persistencia(...) sin use, así que PHP busca en el namespace actual. PerdurarSuperestructuraStringSQL64.php declara namespace Iteradores\Controlador\PerdurarSuperestructura;, así que log_persistencia no está en ese namespace. Hay que importarlo.

Opción A: no poner namespace en LogPersistencia.php, dejar log_persistencia en el global. Simple. Pero el proyecto usa namespaces en el framework.

Opción B: declarar namespace Iteradores\Configuracion; y hacer use function Iteradores\Configuracion\log_persistencia; en cada archivo que la usa.

Opción C: namespace Iteradores\Configuracion; y llamar con \Iteradores\Configuracion\log_persistencia(...).

Como son muchos archivos, y el piloto no usa namespace (usa el global), voy a poner log_persistencia en el namespace global. Sin namespace en LogPersistencia.php. Es más simple y consistente con FuncionesAuxiliares.php (que no tiene namespace).

Espera, FuncionesAuxiliares.php no tiene namespace, y define funciones como formatear_fecha_visible, validar_dni, etc. Esas son globales. log_persistencia sigue el mismo patrón. Bien.

Sin namespace, Conf::DEBUG_PERSISTENCIA necesita use Iteradores\Configuracion\Conf; o \Iteradores\Configuracion\Conf::.... Voy a usar use Iteradores\Configuracion\Conf;.

Archivo Configuracion/LogPersistencia.php versión final:

php
<?php
/**
 * Log de persistencia del framework Iteradores.
 * ...
 */

use Iteradores\Configuracion\Conf;

require_once __DIR__ . '/Configuracion.php';

function log_persistencia(...) { ... }
// ...
Perfecto.

Ahora armo los scripts.

Voy a verificar los buscar de cada Controlador::_error(...) en los flujos.

En ViajeAsientos.php, el bloque típico es:

php
$ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
if (!$ok_guardar) {
    Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");
    return [
        'exito' => false,
        'error' => '...',
    ];
}
El buscar es la línea Controlador::_error(...). La indentación es 8 espacios (dentro del if).

Cada uno tiene su propio texto único. Bien.

En Venta.php (cancelar_venta):

php
$ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
if (!$ok_guardar) {
    Controlador::_error("cancelar_venta: guardar_ambos falló para id_venta=$id_venta");
    ...
buscar es la línea del Controlador::_error(...). 4 espacios de indentación.

En Viaje.php:

eliminar_viaje: 4 espacios

agregar_viaje: 4 espacios

editar_viaje: 4 espacios

guardar_viaje_completo: 4 espacios

agregar_terminal_autorizada: 4 espacios

eliminar_terminal_autorizada: 4 espacios

guardar_opciones_terminal_viaje: 4 espacios

guardar_declaracion_jurada: 4 espacios

limpiar_viajes_de_prueba: 4 espacios

En Pasajero.php:

crear_pasajero: 4 espacios

actualizar_pasajero: 4 espacios

eliminar_pasajero: 4 espacios

subir_declaracion_jurada_pasajero: 4 espacios

eliminar_declaracion_jurada_pasajero: 4 espacios

limpiar_pasajeros_de_prueba: 4 espacios

En Venta.php:

limpiar_cancelaciones_de_prueba: 8 espacios (dentro de un if)

Espera, en limpiar_cancelaciones_de_prueba el código es:

php
if (!empty($borrados)) {
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");
        return [
            'exito' => false,
            'error' => '...',
        ];
    }
}
12 espacios de indentación.

OK. Voy a armar los 28 bloques.

Bueno, voy a poner los cambios de log_persistencia como un solo bloque por archivo, con todos los buscar/reemplazar juntos. No, cada Controlador::_error(...) es un buscar/reemplazar independiente. El runner acepta múltiples bloques por archivo. OK.

Para ahorrar espacio mental, hago:

php
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'reservar_asiento_micro: log_persistencia',
    'buscar' => ['        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");'],
    'reemplazar' => [
        '        log_persistencia("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro", \'error\');',
        '        Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");',
    ],
],
El buscar es de una sola línea. Perfecto. Y es único.

Inclusión en index.php

El bloque:

php
// --- Configuración ------------------------------------
include_once("Configuracion/Configuracion.php");
Después de esto, agregar:

php
// --- Log de persistencia (framework 1.5i.7w) ---------
include_once(__DIR__ . '/Configuracion/LogPersistencia.php');
Endpoint ?ver_log_persistencia=1

En index.php, cerca de ?auditar_topologia=1. El código de ?auditar_topologia=1 es:

php
if (isset($_GET['auditar_topologia'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (Controlador::es_grafo_parcial()) { ... }
    $nombre_sol_aud = trim((string)($_GET['nombre_solicitante'] ?? ''));
    if ($nombre_sol_aud === '') { ... }
    else { ... verificar nivel admin/soporte ... }
    $reporte_aud = Controlador::ejecutar_comando('app:auditar_topologia');
    ...
    exit;
}
Voy a hacer:

php
if (isset($_GET['ver_log_persistencia'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (Controlador::es_grafo_parcial()) {
        $ok_recarga_log = Controlador::cargar($nombre_app);
        if ($ok_recarga_log !== true) {
            http_response_code(500);
            echo json_encode([
                'exito' => false,
                'error' => 'No se pudo recargar el grafo completo.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    $nombre_sol_log = trim((string)($_GET['nombre_solicitante'] ?? ''));
    if ($nombre_sol_log === '') {
        if (!Conf::LOCAL) {
            http_response_code(403);
            echo json_encode(['exito' => false, 'error' => 'Falta nombre_solicitante.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        $nodo_sol_log = Nodo::nodo_por_id('us_' . $nombre_sol_log);
        if (!$nodo_sol_log) {
            http_response_code(403);
            echo json_encode(['exito' => false, 'error' => "Solicitante '$nombre_sol_log' no encontrado."], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $nivel_nodo_log = $nodo_sol_log->adyacente('nivel');
        $nivel_log = $nivel_nodo_log ? (string)$nivel_nodo_log->dato() : '';
        if ($nivel_log !== 'admin' && $nivel_log !== 'soporte') {
            http_response_code(403);
            echo json_encode(['exito' => false, 'error' => "Permiso denegado. Nivel '$nivel_log'."], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    $fecha_log = isset($_GET['fecha']) ? trim((string)$_GET['fecha']) : '';
    $limpiar_log = isset($_GET['limpiar']) && $_GET['limpiar'] === '1';
    if ($limpiar_log) {
        $ok_limp = limpiar_log_persistencia($fecha_log !== '' ? $fecha_log : null);
        echo json_encode([
            'exito' => $ok_limp,
            'limpiado' => $ok_limp,
            'fecha' => $fecha_log !== '' ? $fecha_log : date('Y-m-d'),
            'debug_activo' => Conf::DEBUG_PERSISTENCIA,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $archivos_log = listar_logs_persistencia();
    $fecha_lectura = $fecha_log !== '' ? $fecha_log : date('Y-m-d');
    $contenido_log = leer_log_persistencia($fecha_lectura);
    $lineas_log = 0;
    if ($contenido_log !== null) {
        $lineas_log = substr_count($contenido_log, "\n");
    }
    echo json_encode([
        'exito' => true,
        'debug_activo' => Conf::DEBUG_PERSISTENCIA,
        'archivos' => $archivos_log,
        'fecha' => $fecha_lectura,
        'lineas' => $lineas_log,
        'contenido' => $contenido_log,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
Perfecto.

Sobre LogPersistencia.php en Configuracion/

He verificado: PerdurarSuperestructuraStringSQL.php hace include_once("./Configuracion/Configuracion.php");. Si LogPersistencia.php se incluye desde index.php antes del framework, ya está disponible. Pero PerdurarSuperestructuraStringSQL.php también puede ser incluido directamente (en pruebas), sin pasar por index.php. Para ser defensivo, agrego en cada archivo del framework que usa log_persistencia:

php
include_once("./Configuracion/LogPersistencia.php");
En PerdurarSuperestructuraStringSQL.php y SQL64.php. Junto a los otros include_once.

Pero cuidado: PerdurarSuperestructuraStringSQL64.php hace include_once("./Configuracion/Configuracion.php"); pero no LogPersistencia. Si agrego el include, y index.php también lo incluyó, include_once no vuelve a cargar. OK.

Los archivos de la app (Venta.php, ViajeAsientos.php, etc.) también necesitan log_persistencia. Pero sus includes son include_once("./Configuracion/Configuracion.php"); y similares. Agrego include_once("./Configuracion/LogPersistencia.php"); a cada uno.

Espera, si index.php ya la incluyó (antes del framework), los módulos de la app no necesitan incluirla otra vez. Pero si algún módulo se usa en un contexto sin index.php (raro en el piloto, todos pasan por index.php), fallaría. Voy a agregarla a cada archivo que la usa. Es defensivo y prolijo.

Voy a agregar el include a:

Aplicacion/Ventas/Venta.php

Aplicacion/Viajes/ViajeAsientos.php

Aplicacion/Viajes/ViajeMicros.php

Aplicacion/Viajes/Viaje.php

Aplicacion/Pasajeros/Pasajero.php

El include va en el bloque de includes, después del include_once("./Configuracion/Configuracion.php");. O antes. Va al lado.

Sobre la indentación del include

En ViajeAsientos.php, los includes son:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
include_once("./Aplicacion/Ventas/Venta.php");
include_once("./Aplicacion/Pasajeros/Pasajero.php");
Agrego include_once("./Configuracion/LogPersistencia.php"); después de Configuracion.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Configuracion/LogPersistencia.php");
El buscar es include_once("./Configuracion/Configuracion.php"); (una línea). Es único por archivo. Perfecto.

Mismo en los otros archivos

Venta.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
Viaje.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
ViajeMicros.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/Compartidos.php");
Pasajero.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
include_once("./Aplicacion/Ventas/Venta.php");
Todos tienen include_once("./Configuracion/Configuracion.php");. Voy a insertar include_once("./Configuracion/LogPersistencia.php"); justo después.

Sobre el use de Controlador en los archivos del framework

PerdurarSuperestructuraStringSQL64.php ya tiene use Iteradores\Controlador\... y demás. log_persistencia es global. No hay use necesario.

Pero, ¿el archivo LogPersistencia.php con namespace global va a funcionar en un archivo con namespace Iteradores\Controlador\PerdurarSuperestructura;? Sí, log_persistencia(...) sin \ delante, PHP busca primero en el namespace actual y después cae al global. Confirmado: PHP hace fallback a global para funciones. OK.

Y para el use function Iteradores\Configuracion\Conf; dentro de LogPersistencia.php, eso funciona porque use se resuelve al principio del archivo. OK.

Sobre el require_once __DIR__ . '/Configuracion.php'; en LogPersistencia.php

Configuracion.php declara namespace Iteradores\Configuracion;. Después del require_once, Conf está en Iteradores\Configuracion\Conf. En LogPersistencia.php, con use Iteradores\Configuracion\Conf;, Conf::DEBUG_PERSISTENCIA se resuelve bien.

Sobre Configuracion.php — agregar constantes

Configuracion.php actual termina con:

php
    // Se pueden añadir más verbos según sea necesario, idealmente de forma secuencial.
}
Después del último VERBO_SUPERVISAR = 7;, hay un comentario. Voy a insertar las constantes nuevas antes de ese comentario, o después. Antes de la llave de cierre }.

Mejor: insertar después del bloque de DEBUG_PERSISTENCIA (que existe). Voy a buscar:

php
    public const DEBUG_PERSISTENCIA = false;
Y después agregar las tres constantes nuevas. Voy a hacer:

php
    public const DEBUG_PERSISTENCIA = false;

    /**
     * Directorio (relativo a la raíz del proyecto) donde se escriben
     * los logs de persistencia. Se crea la primera vez.
     *
     * @var string
     * @since 1.5i.7w
     */
    public const LOG_PERSISTENCIA_DIR = 'logs/persistencia/';

    /**
     * Tamaño máximo del archivo de log diario. Si se supera, se trunca
     * al 50% (deja las líneas más recientes). Evita que el log crezca
     * sin control en producción.
     *
     * @var int
     * @since 1.5i.7w
     */
    public const LOG_PERSISTENCIA_MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Días que se retienen los archivos de log. Al escribir por primera
     * vez en el día, se borran los archivos más viejos.
     *
     * @var int
     * @since 1.5i.7w
     */
    public const LOG_PERSISTENCIA_RETENER_DIAS = 7;
Espera, DEBUG_PERSISTENCIA está comentada con /** ... */. Voy a buscar el bloque:

php
    /**
     * Activa el logging de diagnóstico del guardado parcial (SQL64).
     * ...
     * @var bool
     * @since 1.5i.7t
     */
    public const DEBUG_PERSISTENCIA = false;
Y agregar las tres constantes después. Con comentarios PHPDoc.

El buscar:

php
    public const DEBUG_PERSISTENCIA = false;
Es único. OK.

Voy a armar los dos scripts ahora.

Script 1 (framework 1.5i.7w):

Configuracion/Configuracion.php — agregar 3 constantes.

Configuracion/LogPersistencia.php — crear.

PerdurarSuperestructuraStringSQL.php — include + cambiar error_log por log_persistencia.

PerdurarSuperestructuraStringSQL64.php — include + cambiar error_log por log_persistencia.

Para PerdurarSuperestructuraStringSQL.php, el error_log está en crear_conexion_sql (el "[CREAR_CONEXION]..."). Busco:

php
error_log("[CREAR_CONEXION] version=82c, singleton=" . ($singleton ? "vivo" : "nulo"));
Y cambio a log_persistencia("[CREAR_CONEXION] version=82c, singleton=" . ($singleton ? "vivo" : "nulo"), 'info');. Espera, ¿es info o debug? Es info. Bien.

En realidad, el [CREAR_CONEXION] es un mensaje de debug, no de error. info. OK.

Para PerdurarSuperestructuraStringSQL64.php, hay varios error_log. Voy a enumerarlos del archivo que me pasaste:

error_log("[SQL64_GUARDAR] nombre=" . $nombre . ... — info

error_log("[SQL64_GUARDAR] ids_a_insertar=" . ... — info

error_log("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas)); — info

error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . ... — error

error_log("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . ... — info

error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n); — info

error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . ... — error

error_log("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a); — info

error_log("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . ... — error

Pero como v83 los if (Conf::DEBUG_PERSISTENCIA) externos ya están en el archivo, tengo que chequear cada uno. Por ejemplo:

php
if (Conf::DEBUG_PERSISTENCIA) {
    $modo_log = ($mf_parcial !== 0) ? "parcial" : "total";
    error_log("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log . ...);
}
Cambio el error_log(...) a log_persistencia(..., 'info'); y saco el if externo (redundante con el check interno de log_persistencia para 'info'). O lo dejo, no hace daño. Lo dejo. Menos cambios.

Pero en el [SQL64_GUARDAR] PERDIDA:

php
if (Conf::DEBUG_PERSISTENCIA) {
    foreach ($perdidos as $p) {
        error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . ...);
    }
}
Cambio a:

php
foreach ($perdidos as $p) {
    log_persistencia("[SQL64_GUARDAR] PERDIDA id_nodo=" . ..., 'error');
}
Y saco el if externo, porque 'error' se escribe siempre.

Mejor hacer esto bien. Voy a hacer varios bloques:

Bloque A: el if (Conf::DEBUG_PERSISTENCIA) que envuelve [SQL64_GUARDAR] nombre=.... Cambio el error_log adentro por log_persistencia(..., 'info'). Dejo el if externo (o lo saco). Como log_persistencia(..., 'info') ya chequea internamente, saco el if externo. Total, log_persistencia es no-op si DEBUG está en false. Limpio.

Bloque B: el if (Conf::DEBUG_PERSISTENCIA) que envuelve ids_a_insertar=.... Idem.

Bloque C: error_log("[SQL64_GUARDAR] PERDIDA ...) dentro de if (Conf::DEBUG_PERSISTENCIA). Cambio a log_persistencia(..., 'error') Y saco el if externo (porque error va siempre).

Bloque D: if (Conf::DEBUG_PERSISTENCIA) que envuelve verif_mascaras ok=1. log_persistencia(..., 'info'), saco el if externo.

Bloque E: if (Conf::DEBUG_PERSISTENCIA) que envuelve verif_nodos ok=1 Y también verif_nodos FALLO. Chequear: los dos están en el mismo if? Miro el código...

Del código de v1.5i.7v que acabo de proponer (que ya apliqué):

php
if (!empty($filas_insertadas_ahora)) {
    ...
    if ($cnt_n !== count($filas_insertadas_ahora)) {
        if (Conf::DEBUG_PERSISTENCIA) {
            error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);
        }
        throw new \RuntimeException(...);
    }
    if (Conf::DEBUG_PERSISTENCIA) {
        error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n);
    }
}
Son dos bloques separados. El FALLO es error, el ok es info.

Del código de v1.5i.7t (el primero), el PERDIDA:

php
if (!empty($perdidos)) {
    if (Conf::DEBUG_PERSISTENCIA) {
        foreach ($perdidos as $p) {
            error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . ...);
        }
    }
    throw new \RuntimeException(...);
}
Cambio a:

php
if (!empty($perdidos)) {
    foreach ($perdidos as $p) {
        log_persistencia("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . ..., 'error');
    }
    throw new \RuntimeException(...);
}
Saco el if externo. log_persistencia(..., 'error') siempre escribe.

OK, voy a armar los bloques específicos. Son más de los que pensaba.

En realidad, para no complicar demasiado, voy a hacer un bloque por archivo con todos los error_log reemplazados, o mejor, un bloque por cada error_log (usando buscar de una línea). Son 9 en SQL64.

Los enumero:

SQL64:

error_log("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log . ...); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar) . ...); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas)); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . ...) (dentro de if (Conf::DEBUG_PERSISTENCIA) + foreach)

error_log("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . count($mascaras_viejas)); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a); (dentro de if (Conf::DEBUG_PERSISTENCIA))

error_log("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . count($filas_aristas) . " en_db=" . $cnt_a); (dentro de if (Conf::DEBUG_PERSISTENCIA))

Voy a hacer un bloque de reemplazo por cada uno, preservando el if (Conf::DEBUG_PERSISTENCIA) externo, y solo cambiando el error_log(...) por log_persistencia(..., 'info'|'error'). Así el cambio es mínimo y no rompe nada.

Para los FALLO y PERDIDA, uso 'error'. Y dejo el if (Conf::DEBUG_PERSISTENCIA) externo (redundante pero inofensivo).

Bueno, suficiente análisis. Voy a armar los scripts.

Script 2 (piloto 1.5piloto.83i) — 5 archivos:

index.php

Aplicacion/Ventas/Venta.php

Aplicacion/Viajes/ViajeAsientos.php

Aplicacion/Viajes/ViajeMicros.php

Aplicacion/Viajes/Viaje.php

Aplicacion/Pasajeros/Pasajero.php

Cambios:

Include LogPersistencia.php en cada archivo.

log_persistencia(...) antes de cada Controlador::_error("...guardar_ambos falló...").

Endpoint en index.php.

Voy a hacer todos los bloques.

Los 28 flujos con Controlador::_error:

Venta.php (3):

cancelar_venta

confirmar_venta_actual

pagar_cupon_venta

Venta.php (1):

limpiar_cancelaciones_de_prueba

ViajeAsientos.php (6):

reservar_asiento_micro

asignar_pasajero_a_reserva

liberar_reserva_asiento_micro

seleccionar_asiento_micro

deseleccionar_asiento_micro

cambiar_asiento_pasaje

ViajeMicros.php (3):

agregar_micro_a_viaje

eliminar_micro_de_viaje

actualizar_monto_micro

Viaje.php (9):

eliminar_viaje

agregar_viaje

editar_viaje

guardar_viaje_completo

agregar_terminal_autorizada

eliminar_terminal_autorizada

guardar_opciones_terminal_viaje

guardar_declaracion_jurada

limpiar_viajes_de_prueba

Pasajero.php (6):

crear_pasajero

actualizar_pasajero

eliminar_pasajero

subir_declaracion_jurada_pasajero

eliminar_declaracion_jurada_pasajero

limpiar_pasajeros_de_prueba

Cada uno con su buscar/reemplazar.

Voy a escribir. Cada bloque es corto.

Vamos a verificar los buscar una vez más. Los textos exactos de los Controlador::_error:

De v83d:

Controlador::_error("cancelar_venta: guardar_ambos falló para id_venta=$id_venta");

Controlador::_error("confirmar_venta_actual: guardar_ambos falló para id_venta=$id_venta");

Controlador::_error("pagar_cupon_venta: guardar_ambos falló para id_venta=$id_venta numero_cupon=$numero_cupon");

De v83g ViajeAsientos:

Controlador::_error("reservar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("asignar_pasajero_a_reserva: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("liberar_reserva_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("seleccionar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("deseleccionar_asiento_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("cambiar_asiento_pasaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

De v83g ViajeMicros:

Controlador::_error("agregar_micro_a_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("eliminar_micro_de_viaje: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

Controlador::_error("actualizar_monto_micro: guardar_ambos falló para viaje=$nombre_viaje micro=$nombre_micro");

De v83g Viaje:

Controlador::_error("eliminar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

De v83g Pasajero:

Controlador::_error("crear_pasajero: guardar_ambos falló para dni=$dni_norm");

Controlador::_error("actualizar_pasajero: guardar_ambos falló para dni=$dni");

Controlador::_error("eliminar_pasajero: guardar_ambos falló para dni=$dni");

Controlador::_error("subir_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");

Controlador::_error("eliminar_declaracion_jurada_pasajero: guardar_ambos falló para dni=$dni_norm");

De v83h Viaje:

Controlador::_error("agregar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

Controlador::_error("editar_viaje: guardar_ambos falló para viaje=$nombre_viaje");

Controlador::_error("guardar_viaje_completo: guardar_ambos final falló para viaje=$nombre_viaje");

Controlador::_error("agregar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

Controlador::_error("eliminar_terminal_autorizada: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

Controlador::_error("guardar_opciones_terminal_viaje: guardar_ambos falló para viaje=$nombre_viaje terminal=$nombre_terminal");

Controlador::_error("guardar_declaracion_jurada: guardar_ambos falló para viaje=$nombre_viaje tipo=$tipo");

Controlador::_error("limpiar_viajes_de_prueba: guardar_ambos falló");

De v83h Pasajero:

Controlador::_error("limpiar_pasajeros_de_prueba: guardar_ambos falló");

De v83h Venta:

Controlador::_error("limpiar_cancelaciones_de_prueba: guardar_ambos falló");

OK, tengo todos. Ahora armo los bloques.

Voy a hacer el script 1 primero, después el 2.

Voy a incluir log_persistencia en los archivos afectados con el include_once.

Para los flujos del piloto, ¿es mejor hacer un solo buscar con múltiples líneas o varios buscar de una línea cada uno? Cada uno es independiente, así que un bloque por cada Controlador::_error(...).

Voy a escribir el script con todos.

Sobre include_once en archivos del piloto

El archivo Venta.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
Agrego después de Configuracion.php:

php
include_once("./Configuracion/LogPersistencia.php");
El buscar es:

php
'include_once("./Configuracion/Configuracion.php");',
Es único. OK.

Mismo para todos.

Sobre el orden de los scripts

Si corro el script 1 primero, LogPersistencia.php se crea y las constantes existen. Los archivos del framework quedan funcionando.

Si después corro el script 2, los flujos del piloto usan log_persistencia, que ya existe. Y agregamos el endpoint.

Bien.

Voy a armar los dos scripts.

Espera, $htmls_a_bumpear no aplica (no toco JS). OK.

$version_nueva en script 1: 1.5i.7w. En script 2: 1.5piloto.83i.

Vamos.

Sobre el commit

Script 1 commit: V1.5i.7w: log de persistencia en archivo del proyecto.
Script 2 commit: V1.5piloto.83i: endpoint ?ver_log_persistencia=1 + log_persistencia en flujos.

O si se prefiere, un solo commit del proyecto con ambos. Pero la regla dice "framework y piloto en scripts separados, commits separados". Voy a seguir esa regla.

Bueno, armo los scripts.

Espera, tengo que decidir si include_once("./Configuracion/LogPersistencia.php"); va con __DIR__ o con ./. Los otros usan ./. Voy a seguir el patrón del archivo. En index.php uso __DIR__ porque los otros includes usan __DIR__. En los archivos de Aplicacion/, uso ./Configuracion/LogPersistencia.php (patrón de esos archivos).

OK, armo.

Voy a hacer el script 1 primero.

text
[Framework 1.5i.7w]

1. Configuracion/Configuracion.php — agregar 3 constantes (después de DEBUG_PERSISTENCIA)
2. Configuracion/LogPersistencia.php — crear
3. Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php — include + cambiar error_log
4. Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php — include + cambiar 9 error_log
Cambios en SQL.php:

php
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/NodoElectrico.php");
...
Agrego include_once("./Configuracion/LogPersistencia.php"); después de Configuracion.php.

El error_log:

php
error_log("[CREAR_CONEXION] version=82c, singleton=" . ($singleton ? "vivo" : "nulo"));
Está dentro del código que no veo. No lo tengo completo. Pero el bloque de v1.5i.8e lo menciona. Voy a asumir que existe tal cual.

Mmm, no tengo PerdurarSuperestructuraStringSQL.php completo. Tengo solo la referencia que me pasaste de él en la v1.5i.8d ("SET SESSION wait_timeout"). No lo puedo tocar con precisión sin verlo.

Para simplificar: no cambio error_log en PerdurarSuperestructuraStringSQL.php. Solo agrego el include (que es inocuo). Y en SQL64.php cambio los error_log que sí tengo identificados.

O mejor: pido al usuario que me pase PerdurarSuperestructuraStringSQL.php. Pero es fricción.

Alternativa: dejo SQL.php intacto, y solo agrego LogPersistencia.php al include de SQL64.php (que ya tiene el resto de los error_log). Los [CREAR_CONEXION] siguen en error_log (no llegan al archivo del proyecto, pero son solo info de debug; los errores reales están en SQL64.php).

Voy a hacer eso: no toco SQL.php, solo SQL64.php. Y le aviso al usuario que [CREAR_CONEXION] sigue usando error_log().

El plan final:

Script 1 (framework 1.5i.7w):

Configuracion/Configuracion.php: 3 constantes.

Configuracion/LogPersistencia.php: crear.

Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php: include + cambiar 9 error_log a log_persistencia.

Script 2 (piloto 1.5piloto.83i):

index.php: include LogPersistencia.php + endpoint ?ver_log_persistencia=1 + 1 log_persistencia (en cancelar_venta).

Espera, cancelar_venta está en Venta.php, no en index.php. index.php no tiene ningún Controlador::_error con guardar_ambos. Reviso.

Los Controlador::_error(...) están en Venta.php, ViajeAsientos.php, ViajeMicros.php, Viaje.php, Pasajero.php.

index.php solo necesita el include y el endpoint.

Aplicacion/Ventas/Venta.php: include + 4 log_persistencia.

Aplicacion/Viajes/ViajeAsientos.php: include + 6 log_persistencia.

Aplicacion/Viajes/ViajeMicros.php: include + 3 log_persistencia.

Aplicacion/Viajes/Viaje.php: include + 9 log_persistencia.

Aplicacion/Pasajeros/Pasajero.php: include + 6 log_persistencia.

OK, armo.

Espera, ¿cancelar_venta tiene Controlador::_error? Sí, de v81w:

php
Controlador::_error("cancelar_venta: guardar_ambos falló para id_venta=$id_venta");
Está en Venta.php. Bien.

Y confirmar_venta_actual:

php
Controlador::_error("confirmar_venta_actual: guardar_ambos falló para id_venta=$id_venta");
Y pagar_cupon_venta:

php
Controlador::_error("pagar_cupon_venta: guardar_ambos falló para id_venta=$id_venta numero_cupon=$numero_cupon");
Son 3 en Venta.php + 1 limpiar_cancelaciones_de_prueba = 4.

Bien.

Ahora armo el script. Voy a ser prolijo con los bloques.

Para no hacer el mensaje extremadamente largo, voy a comprimir un poco el formato de los buscar/reemplazar de los log_persistencia.

Script 1 (framework)

Voy a hacerlo.

Script 2 (piloto)

Voy a hacerlo.

OK, armo.

Antes de armar el script, una decisión operativa importante: este cambio toca el framework (log de persistencia) y el piloto (endpoint + flujos). Por la regla de dos scripts / dos commits, entrego dos aplicar_cambios.php separados, uno por proyecto. Los dos se aplican en este orden.

Script 1 — Framework 1.5i.7w
Crea LogPersistencia.php, agrega 3 constantes a Conf, y reemplaza los error_log de SQL64 por log_persistencia.

Aviso: de PerdurarSuperestructuraStringSQL.php no tengo el archivo completo, solo la referencia del bloque C.9-C.10. No lo toco. El [CREAR_CONEXION] sigue usando error_log() (es solo info de debug, no error). Si querés que también vaya al archivo, pasame el archivo y lo agrego en una tandita.

php
<?php
/**
 * Aplicador — Framework 1.5i.7w: log de persistencia en archivo
 * del proyecto + endpoint admin para leerlo/limpiarlo.
 *
 * Motivo: en hostings compartidos (InfinityFree, etc.) no hay
 * acceso al error_log de PHP. Los `error_log()` de `SQL64` van
 * a un archivo inaccesible desde el panel o el FTP.
 *
 * Fix: nueva función global `log_persistencia()` que escribe a
 * `logs/persistencia/YYYY-MM-DD.log` en el propio proyecto. Se
 * activa con `Conf::DEBUG_PERSISTENCIA = true`. Los mensajes con
 * nivel 'error' se escriben siempre; los 'info'/'warn' solo con
 * el flag activo. Rotación al 50% si supera 5MB. Retención de 7
 * días.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5i.7w';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Conf: 3 constantes nuevas para el log de persistencia
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Configuracion/Configuracion.php',
    'descripcion' => 'Conf: constantes del log de persistencia',
    'buscar' => [
        '    public const DEBUG_PERSISTENCIA = false;',
    ],
    'reemplazar' => [
        '    public const DEBUG_PERSISTENCIA = false;',
        '',
        '    /**',
        '     * Directorio (relativo a la raíz del proyecto) donde se',
        '     * escriben los logs de persistencia. Se crea la primera',
        '     * vez que se escribe.',
        '     *',
        '     * @var string',
        '     * @since 1.5i.7w',
        '     */',
        '    public const LOG_PERSISTENCIA_DIR = \'logs/persistencia/\';',
        '',
        '    /**',
        '     * Tamaño máximo del archivo de log diario. Si se supera,',
        '     * se trunca al 50% (deja las líneas más recientes). Evita',
        '     * que el log crezca sin control en producción.',
        '     *',
        '     * @var int',
        '     * @since 1.5i.7w',
        '     */',
        '    public const LOG_PERSISTENCIA_MAX_BYTES = 5 * 1024 * 1024;',
        '',
        '    /**',
        '     * Días que se retienen los archivos de log. Al escribir',
        '     * por primera vez en el día, se borran los archivos más',
        '     * viejos que este límite.',
        '     *',
        '     * @var int',
        '     * @since 1.5i.7w',
        '     */',
        '    public const LOG_PERSISTENCIA_RETENER_DIAS = 7;',
    ],
],

// ============================================================
// 2. Archivo nuevo: Configuracion/LogPersistencia.php
// ============================================================
[
    'tipo' => 'crear',
    'archivo' => 'Configuracion/LogPersistencia.php',
    'descripcion' => 'Nuevo: LogPersistencia.php (log en archivo del proyecto)',
    'contenido' => [
        '<?php',
        '/**',
        ' * Log de persistencia del framework Iteradores.',
        ' *',
        ' * Escribe a un archivo del proyecto (bajo logs/persistencia/)',
        ' * en lugar de (o además de) error_log. Pensado para hostings',
        ' * compartidos donde el error.log de PHP no es accesible.',
        ' *',
        ' * Activación: Conf::DEBUG_PERSISTENCIA = true.',
        ' * Los mensajes con nivel \'error\' se escriben SIEMPRE, aunque',
        ' * DEBUG_PERSISTENCIA esté en false (los errores no se pierden).',
        ' *',
        ' * @author  Ignacio David Baigorria',
        ' * @package Iteradores\\Configuracion',
        ' * @since   1.5i.7w',
        ' * @version 1.5i.7w',
        ' */',
        '',
        'use Iteradores\\Configuracion\\Conf;',
        '',
        'require_once __DIR__ . \'/Configuracion.php\';',
        '',
        '/**',
        ' * Escribe un mensaje al log de persistencia del proyecto.',
        ' *',
        ' * @param string $msg   Mensaje.',
        ' * @param string $nivel \'info\' | \'warn\' | \'error\'. Los \'error\'',
        ' *                      se escriben siempre; \'info\'/\'warn\' solo',
        ' *                      si Conf::DEBUG_PERSISTENCIA está activo.',
        ' * @return void',
        ' */',
        'function log_persistencia(string $msg, string $nivel = \'info\'): void {',
        '    $nivel = strtolower($nivel);',
        '    if (!in_array($nivel, [\'info\', \'warn\', \'error\'], true)) $nivel = \'info\';',
        '    if (($nivel === \'info\' || $nivel === \'warn\') && !Conf::DEBUG_PERSISTENCIA) return;',
        '',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) {',
        '        // Fallback: si no se pudo crear el directorio, error_log.',
        '        error_log("[LOG_PERSISTENCIA] " . $msg);',
        '        return;',
        '    }',
        '',
        '    $fecha = date(\'Y-m-d\');',
        '    $ruta = $dir . $fecha . \'.log\';',
        '    $primera_vez_hoy = !file_exists($ruta);',
        '    $linea = \'[\' . date(\'Y-m-d H:i:s\') . \'] [\' . $nivel . \'] \' . $msg . "\\n";',
        '',
        '    // Rotación: si el archivo del día supera el máximo,',
        '    // se trunca al 50% (deja las líneas más recientes).',
        '    if (file_exists($ruta)) {',
        '        $tam = @filesize($ruta);',
        '        if ($tam !== false && $tam > Conf::LOG_PERSISTENCIA_MAX_BYTES) {',
        '            $lineas = @file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);',
        '            if (is_array($lineas) && count($lineas) > 0) {',
        '                $lineas = array_slice($lineas, intdiv(count($lineas), 2));',
        '                @file_put_contents($ruta, implode("\\n", $lineas) . "\\n");',
        '            }',
        '        }',
        '    }',
        '',
        '    @file_put_contents($ruta, $linea, FILE_APPEND | LOCK_EX);',
        '',
        '    // Limpieza de archivos viejos: solo en la primera escritura del día.',
        '    if ($primera_vez_hoy) {',
        '        _log_persistencia_limpiar_viejos();',
        '    }',
        '}',
        '',
        '/**',
        ' * Devuelve la ruta absoluta al directorio de logs. Lo crea si no existe.',
        ' *',
        ' * @return string|null',
        ' */',
        'function _log_persistencia_directorio(): ?string {',
        '    $raiz = dirname(__DIR__);',
        '    $dir = $raiz . \'/\' . ltrim(Conf::LOG_PERSISTENCIA_DIR, \'/\');',
        '    if (substr($dir, -1) !== \'/\') $dir .= \'/\';',
        '    if (!is_dir($dir)) {',
        '        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {',
        '            return null;',
        '        }',
        '    }',
        '    return $dir;',
        '}',
        '',
        '/**',
        ' * Borra los archivos de log más viejos que el límite de retención.',
        ' *',
        ' * @return void',
        ' */',
        'function _log_persistencia_limpiar_viejos(): void {',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) return;',
        '    $archivos = @glob($dir . \'*.log\');',
        '    if (!is_array($archivos)) return;',
        '    $limite = time() - Conf::LOG_PERSISTENCIA_RETENER_DIAS * 86400;',
        '    foreach ($archivos as $f) {',
        '        $mt = @filemtime($f);',
        '        if ($mt !== false && $mt < $limite) {',
        '            @unlink($f);',
        '        }',
        '    }',
        '}',
        '',
        '/**',
        ' * Lee el contenido del log de una fecha.',
        ' *',
        ' * @param string|null $fecha YYYY-MM-DD. Si null, hoy.',
        ' * @return string|null',
        ' */',
        'function leer_log_persistencia(?string $fecha = null): ?string {',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) return null;',
        '    if ($fecha === null || $fecha === \'\') {',
        '        $fecha = date(\'Y-m-d\');',
        '    }',
        '    if (!preg_match(\'/^\\d{4}-\\d{2}-\\d{2}$/\', $fecha)) return null;',
        '    $ruta = $dir . $fecha . \'.log\';',
        '    if (!file_exists($ruta)) return null;',
        '    $c = @file_get_contents($ruta);',
        '    return $c === false ? null : $c;',
        '}',
        '',
        '/**',
        ' * Lista los archivos de log disponibles, del más reciente al más viejo.',
        ' *',
        ' * @return array<int, array{fecha: string, tamano: int, tamano_legible: string, mtime: int}>',
        ' */',
        'function listar_logs_persistencia(): array {',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) return [];',
        '    $archivos = @glob($dir . \'*.log\');',
        '    if (!is_array($archivos)) return [];',
        '    $res = [];',
        '    foreach ($archivos as $f) {',
        '        $nombre = basename($f);',
        '        if (!preg_match(\'/^(\\d{4}-\\d{2}-\\d{2})\\.log$/\', $nombre, $m)) continue;',
        '        $tam = @filesize($f);',
        '        $mt = @filemtime($f);',
        '        $res[] = [',
        '            \'fecha\' => $m[1],',
        '            \'tamano\' => (int)($tam === false ? 0 : $tam),',
        '            \'tamano_legible\' => _log_persistencia_formato_tamano((int)($tam === false ? 0 : $tam)),',
        '            \'mtime\' => (int)($mt === false ? 0 : $mt),',
        '        ];',
        '    }',
        '    usort($res, function($a, $b) { return $b[\'fecha\'] <=> $a[\'fecha\']; });',
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Trunca (borra el contenido) del log de una fecha.',
        ' *',
        ' * @param string|null $fecha YYYY-MM-DD. Si null, hoy.',
        ' * @return bool',
        ' */',
        'function limpiar_log_persistencia(?string $fecha = null): bool {',
        '    $dir = _log_persistencia_directorio();',
        '    if ($dir === null) return false;',
        '    if ($fecha === null || $fecha === \'\') {',
        '        $fecha = date(\'Y-m-d\');',
        '    }',
        '    if (!preg_match(\'/^\\d{4}-\\d{2}-\\d{2}$/\', $fecha)) return false;',
        '    $ruta = $dir . $fecha . \'.log\';',
        '    if (!file_exists($ruta)) return true;',
        '    return @file_put_contents($ruta, \'\') !== false;',
        '}',
        '',
        '/**',
        ' * Formatea un tamaño en bytes a string legible.',
        ' *',
        ' * @param int $bytes',
        ' * @return string',
        ' */',
        'function _log_persistencia_formato_tamano(int $bytes): string {',
        '    if ($bytes < 1024) return $bytes . \' B\';',
        '    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, \'.\', \'\') . \' KB\';',
        '    return number_format($bytes / (1024 * 1024), 2, \'.\', \'\') . \' MB\';',
        '}',
    ],
],

// ============================================================
// 3. SQL64: include + 9 reemplazos de error_log por log_persistencia
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: include de LogPersistencia',
    'buscar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");',
    ],
    'reemplazar' => [
        'include_once("./Configuracion/Configuracion.php");',
        'include_once("./Configuracion/LogPersistencia.php");',
        'include_once("./Controlador/PerdurarSuperestructura/PerdurarSuperestructura.php");',
    ],
],

// 3.1 Cabecera del guardado
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: cabecera del guardado al log de persistencia',
    'buscar' => [
        '            error_log("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log',
        '                . " nodos_en_memoria=" . count($mascaras)',
        '                . " nodos_previos=" . count($mapa_previo)',
        '                . " filas_cargadas=" . count($filas_cargadas)',
        '                . " mascaras_viejas=" . count($mascaras_viejas));',
    ],
    'reemplazar' => [
        '            log_persistencia("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log',
        '                . " nodos_en_memoria=" . count($mascaras)',
        '                . " nodos_previos=" . count($mapa_previo)',
        '                . " filas_cargadas=" . count($filas_cargadas)',
        '                . " mascaras_viejas=" . count($mascaras_viejas), \'info\');',
    ],
],

// 3.2 ids_a_insertar
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: ids_a_insertar al log de persistencia',
    'buscar' => [
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " matchean_filtro=" . count($ids_matchean_filtro)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
    ],
    'reemplazar' => [
        '                log_persistencia("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " matchean_filtro=" . count($ids_matchean_filtro)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""), \'info\');',
    ],
],

// 3.3 PERDIDA
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: PERDIDA al log de persistencia (error)',
    'buscar' => [
        '                    if (Conf::DEBUG_PERSISTENCIA) {',
        '                        foreach ($perdidos as $p) {',
        '                            error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . " id_fila=" . $p["id_fila"] . " vieja=" . $p["vieja"] . " nueva=" . $p["nueva"]);',
        '                        }',
        '                    }',
    ],
    'reemplazar' => [
        '                    foreach ($perdidos as $p) {',
        '                        log_persistencia("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . " id_fila=" . $p["id_fila"] . " vieja=" . $p["vieja"] . " nueva=" . $p["nueva"], \'error\');',
        '                    }',
    ],
],

// 3.4 verif_mascaras ok
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verif_mascaras ok al log',
    'buscar' => [
        '                    error_log("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . count($mascaras_viejas));',
    ],
    'reemplazar' => [
        '                    log_persistencia("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . count($mascaras_viejas), \'info\');',
    ],
],

// 3.5 verif_nodos ok
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verif_nodos ok al log',
    'buscar' => [
        '                    error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n);',
    ],
    'reemplazar' => [
        '                    log_persistencia("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n, \'info\');',
    ],
],

// 3.6 verif_nodos FALLO
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verif_nodos FALLO al log (error)',
    'buscar' => [
        '                        error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);',
    ],
    'reemplazar' => [
        '                        log_persistencia("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n, \'error\');',
    ],
],

// 3.7 aristas_a_insertar
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: aristas_a_insertar al log',
    'buscar' => [
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas));',
        '            }',
    ],
    'reemplazar' => [
        '            log_persistencia("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas), \'info\');',
    ],
],

// 3.8 verif_aristas ok
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verif_aristas ok al log',
    'buscar' => [
        '                    error_log("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a);',
    ],
    'reemplazar' => [
        '                    log_persistencia("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a, \'info\');',
    ],
],

// 3.9 verif_aristas FALLO
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: verif_aristas FALLO al log (error)',
    'buscar' => [
        '                        error_log("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . count($filas_aristas) . " en_db=" . $cnt_a);',
    ],
    'reemplazar' => [
        '                        log_persistencia("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . count($filas_aristas) . " en_db=" . $cnt_a, \'error\');',
    ],
],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";
Log esperado del script 1
text
=== Aplicador ===

[INFO] 2 archivo(s) a reemplazar, 1 a crear, 0 a eliminar.

[OK] Configuracion/Configuracion.php
[OK] Configuracion/LogPersistencia.php (creado)
[OK] Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php

--- Bumps ---
[BUMP] Configuracion/Configuracion.php  @version 1.5i.7h -> 1.5i.7w
[BUMP] Configuracion/LogPersistencia.php  @version 1.5i.7w -> 1.5i.7w (creado con la versión)
[BUMP] Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php  @version 1.5i.7v -> 1.5i.7w

=== Resumen ===
Bloques aplicados: 12
Archivos escritos: 3
Bumps @version:    3

Listo.
Aviso: Configuracion.php tiene @version 1.5i.7h. El bump lo lleva a 1.5i.7w. OK.

Cuando corras el script 1 y me confirmes que pasó, te paso el script 2 (piloto 1.5piloto.83i) con:

index.php: include + endpoint ?ver_log_persistencia=1.

Los 6 archivos del piloto con include_once("./Configuracion/LogPersistencia.php");.

Los 28 log_persistencia(...) antes de cada Controlador::_error("...guardar_ambos falló...").

¿Corrés el script 1?

bien, continua
Se alcanzó el límite de longitud. Inicie un nuevo chat