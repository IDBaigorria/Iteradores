# Migraciones del piloto — guía completa

Este archivo documenta la **secuencia exacta** de migraciones que
hay que correr para migrar un grafo desde cero (o resetear y
volver a migrar). Refleja el estado post-v1.5piloto.81n (fix de
`reparar_arboles` + rediseño `soportes` plural).

Se actualiza cuando cambia la secuencia o el comportamiento de
algún paso.

---

## 1. Cuándo correrla

- **Grafo desde cero.** Primera vez que se levanta el proyecto, o
  después de un `TRUNCATE` de las tablas SQL64.
- **Reset + re-migración.** Después de un `?reseteo=1` (o similar)
  que deja el grafo en un estado conocido (por ejemplo, con
  datos de prueba cargados con topología "vieja").
- **Grafo actualizado desde otra versión.** Cuando se hizo pull de
  una versión con migraciones nuevas.

**No hace falta correrla** en el día a día. Las migraciones son
idempotentes, pero no aportan nada si el grafo ya está migrado.

---

## 2. Pre-requisitos

- Base de datos MySQL accesible con las credenciales de
  `config_servidor.php`.
- Los grafos `AdministradorDeViajes` y
  `AdministradorDeViajes_credenciales` accesibles (o vacíos, en
  cuyo caso `?migrar_a_sql64=1` los crea).
- El usuario admin (`Administrador`) creado. Lo crea `index.php`
  la primera vez que se carga el HTML (carga total). Si no existe,
  las migraciones de usuarios no van a poder correr.
- Backup del grafo actual. **Siempre.** Estas migraciones son
  idempotentes, pero si una falla a mitad, mejor tener cómo volver.

---

## 3. Secuencia completa (en orden estricto)

Correr desde el navegador, uno por uno, verificando el output.
**No saltear el orden.**

### Paso 1 — `index.php?migrar_a_sql64=1`

**Qué hace.** Pasa el grafo del formato SQL (tablas `nodo`,
`adyacente`) al formato SQL64 (tablas `nodo_contexto`,
`adyacente_contexto`, `contexto`). Es el paso crítico: sin esto,
la app no arranca porque el código nuevo usa SQL64.

Hace su propia carga desde SQL, así no depende de que el grafo ya
esté en memoria. Idempotente: correrlo dos veces reescribe con el
mismo contenido.

**Output esperado:**

```
Migración a SQL64
=================

-- app (AdministradorDeViajes) --
   migrado. Contextos: N
     - us_Administrador
     - us_carmen1
     - ...

-- credenciales (AdministradorDeViajes_credenciales) --
   migrado. Contextos: M
     - us_Administrador
     - ...
```

- **`Contextos: N > 0`** en los dos grafos → OK.
- **`no existe en SQL. Se saltea.`** → el grafo no existe. Si es
  la primera vez, es esperable. Si no, verificar la base.
- **`ERROR: no se pudo cargar desde SQL.`** → falla de conexión o
  tabla corrupta. **No continuar.** Revisar logs del Controlador.
- **`ERROR: no se pudo guardar en SQL64.`** → falla de SQL. Ver
  `Controlador::imprimir_errores()`. No continuar.

**Si falla, no avanzar.** Este paso es la base de todo lo demás.

---

### Paso 2 — `index.php?migrar_usuarios_especiales=1`

**Qué hace.** Convierte cada nodo usuario a ID especial
`us_<nombre>`. Los enlaces desde `usuarios` siguen llamándose
`<nombre>` (nombre visible), así que todos los accesos por
`adyacente()` siguen funcionando sin cambios. Idempotente.

**Output esperado:**

```
Migración de usuarios a IDs especiales
==========================================

-- app --
  migrados => 15
  saltados => 0

-- credenciales --
  migrados => 15
  saltados => 0

Listo.
```

- **`migrados > 0`** la primera vez. Después queda en `0` (ya
  migrados).
- **`saltados > 0`** es normal si ya estaban con ID especial.

---

### Paso 3 — `index.php?migrar_niveles_usuario=1`

**Qué hace.** Crea los contenedores `publico` y `privado` como
alias de los nodos ya existentes en cada usuario. **No elimina los
enlaces viejos**: los contenedores son alias a los mismos nodos
físicos, todo el código actual sigue funcionando. Idempotente.

Parámetro opcional `&usuario=<nombre>` para migrar uno solo.

**Output esperado:**

```
Migración de niveles de usuario
================================

Usuario objetivo: todos

Migrados: 15
Saltados: 0

Listo.
```

- **`Migrados > 0`** la primera vez.
- **`Saltados > 0`** si algún usuario ya tenía los contenedores.

---

### Paso 4 — `index.php?migrar_compartidos_terminal=1`

**Qué hace.** Crea los contenedores
`compartido_con_<nombre_terminal>` en cada dueño, uno por cada
terminal autorizado en algún viaje. Los compartidos tienen
referencias filtradas a los viajes/empresas/pasajeros/ventas/
cancelaciones del terminal. **No repunta nada**: coexisten con la
estructura actual. Idempotente.

**Output esperado:**

```
Migración de compartidos por terminal
=========================================

Creados:   N
Salteados: M

Listo.
```

- **`Creados > 0`** la primera vez. Después queda en `0`.
- **`Salteados > 0`** si ya existían.

---

### Paso 5 — `index.php?construir_arboles_compartidos=1`

**Qué hace.** Marca los compartidos con `_es_compartido` y
reconstruye sus árboles de ventas y cancelaciones con nombres
**parametrizados** (`hmi_<term>`, `hd_<term>`, `p_<term>`). Borra
los enlaces planos que dejó el paso 4. Idempotente.

**Output esperado:**

```
Construcción de árboles paralelos en compartidos
================================================

Marcados:  N
Saltados:  M

Listo.
```

- **`Marcados > 0`** la primera vez. Después queda en `0`.
- **`Saltados > 0`** si ya estaban marcados.

**Importante:** este paso es el que arma los árboles con nombres
parametrizados. Si un compartido tiene ventas viejas en su árbol,
quedan migradas acá.

---

### Paso 6 — `index.php?repuntar_terminales_compartido=1`

**Qué hace.** Cambia el enlace `dueno` de cada terminal para que
apunte a su contenedor `compartido_con_<nombre>` en vez del nodo
real del dueño. **Activa el aislamiento por topología.** Idempotente.

**Output esperado:**

```
Repuntado de terminales al compartido
======================================

Repuntados:      N
Ya repuntados:   M

Listo.
```

- **`Repuntados > 0`** la primera vez. Después queda en `0`.
- **`Ya repuntados > 0`** si ya estaban repuntados.

---

### Paso 7 — `index.php?migrar_topologia=1`

**Qué hace.** Crea el enlace `us_Administrador → usuarios` y repunta
el enlace `dueno → soporte` para que apunte al `publico` del
soporte. Idempotente.

**Output esperado:**

```
Migración topológica
====================

-- us_Administrador → usuarios --
  creado   (o "ya existía")

-- Dueño → soporte (repuntado al publico) --
  soportes procesados: N
  repuntados:          M
  ya estaban OK:       K

Guardado.
Listo.
```

**⚠️ ADVERTENCIA v81l (soportes plural):** este paso todavía usa
el diseño **viejo** (`soporte` singular). Con el rediseño v81l, el
dueño tiene un contenedor `soportes` (plural) con el `publico` de
cada soporte. Si corrés este paso **después** de tener soportes con
el diseño nuevo, vas a crear enlaces `soporte` singulares
**legacy** (que apuntan al `publico`, pero en el lugar equivocado).

**Regla práctica:**

- **Grafo desde cero, sin soportes todavía:** correr normal. El
  paso crea el admin link y no toca soportes (no hay).
- **Grafo con soportes creados post-v81l:** **saltear este paso**.
  El admin link ya existe (lo crea el paso 1 en SQL64), y los
  soportes ya están con `soportes` plural. Si querés forzar el
  admin link sin tocar soportes, ver §6 "Reparación del admin
  link" más abajo.

---

### Paso 8 — `index.php?reparar_topologia=1`

**Qué hace.** Reparación final de topología. Repone:
- `us_Administrador → usuarios` (si falta).
- `dueño → compartido_con_<terminal>` (si falta).
- Viajes faltantes en el contenedor `viajes` del dueño.
- Contenedor `empresas` del dueño (si falta).
- Contenedor `pasajeros` del dueño (si falta).
- Compartidos de cada terminal + viajes autorizados sincronizados.

**No toca `soporte` singular ni `soportes` plural.** Es seguro
correrlo siempre. Idempotente.

**Output esperado:**

```
Reparación topológica
====================

-- us_Administrador → usuarios --
  ya existía   (o "creado")

-- Enlaces dueño → compartido_con_* --
   dueños procesados: N
   enlaces agregados: 0

-- Viajes faltantes en el contenedor del dueño --
   dueños procesados: N
   viajes agregados:  0

-- Contenedor `empresas` faltante --
   dueños procesados: N
   reconstruidos:     0

-- Contenedor `pasajeros` faltante --
   dueños procesados: N
   recuperados:       0

-- Compartidos por terminal (v79j) --
   dueños procesados:     N
   compartidos asegurados: 0
   viajes sincronizados:   0

Listo.
```

- **Todo en `0` / `ya existía`** → grafo consistente.
- **Números > 0** → había faltantes. Es esperable la primera vez
  después de un reset brusco.

---

### Paso 9 — `index.php?reparar_arboles=1`

**Qué hace.** Repara aristas `hmi` faltantes de los contenedores
de árbol (ventas, rendiciones, liquidaciones, cancelaciones,
cupones), tanto en árboles del dueño (default) como en los
compartidos (parametrizados). **Con el fix v81n, respeta los
nombres parametrizados** (`hmi_<term>`, `hd_<term>`, `p_<term>`)
de los compartidos. Idempotente.

**Output esperado:**

```
Reparación de árboles
=====================

Cambios aplicados: N
Por categoría:
  duenos: X
  compartidos: Y
  cupones: Z

Detalles:
  - [carmen1/ventas] ...
  - [carmen1/compartido_con_carmen2/ventas] ...

Listo.
```

- **`Cambios aplicados: 0`** → árboles sanos. **Lo esperado en un
  grafo bien migrado.**
- **`Cambios > 0`** → había aristas faltantes y se repusieron. Es
  esperable la primera vez.
- **Detalles con prefijo `[*/compartido_con_*/ventas]` con "hmi
  apunta a hijo con p correcto, no se borra"** → advertencia del
  guard defensivo v81n. El árbol no se rompe, pero conviene
  auditar el precalcular. **No debería aparecer** en un grafo
  sano.

**Test de aceptación:** correr este paso **dos veces seguidas**.
La segunda debe reportar `cambios: 0` y **cero detalles** sobre
compartidos.

---

## 4. Paso post-migración — drenar el buzón

**Después del paso 9**, hacer **una** de estas cosas:

**Opción A — entrar como dueño primero (recomendado):**

1. Login como dueño.
2. Ir a la pestaña **Vendidos**.
3. Esperar a que la tabla cargue.
4. Si hay ventas en el buzón del compartido
   (`pendientes_ventas`), se drenan al árbol default del dueño.

Después de esto, el dueño ve sus ventas y el árbol del compartido
sigue intacto.

**Opción B — entrar como terminal primero:**

1. Login como terminal.
2. Ir a la pestaña **Viajes** → seleccionar un viaje → micro.
3. Vender.
4. Login como dueño.
5. Ir a **Vendidos** → drena el buzón del compartido.

**Con el fix v81n, las dos opciones son seguras.** Antes del fix,
la opción B rompía el árbol del compartido si `reparar_arboles`
había corrido entre medio. Ahora no.

**Por qué importa el drenado.** El buzón `pendientes_ventas` del
compartido es un mecanismo de sincronización entre el terminal
(que vende) y el dueño (que ve todo). Si nadie lo drena, las
ventas del terminal no aparecen en el árbol del dueño hasta que
el dueño entre a Vendidos. El drenado es **explícito** (no
automático).

---

## 5. Verificación final

Con el grafo migrado y el buzón drenado:

1. **Pestaña Grafo** (admin/soporte):
   - `Nodos raíz`: debe listar `usuarios`, `sesiones`,
     `aplicacion`, `us_<cada usuario>`.
   - `us_Administrador` tiene enlace `usuarios`.
   - `us_<dueño>` tiene `soportes` (plural, contenedor) con el
     `publico` de cada soporte asignado.
   - `us_<dueño>` **no tiene** `soporte` (singular). Si lo tiene,
     corriste `?migrar_topologia=1` con soportes ya creados (ver
     advertencia v81l en §3.7).
   - `us_<terminal>` tiene `dueno` apuntando a un nodo con
     `_es_compartido`.

2. **Login dueño:**
   - Pestaña Vendidos: ve las ventas de sus terminales.
   - Pestaña Rendiciones: ve las rendiciones.
   - Pestaña Liquidaciones: ve las liquidaciones.

3. **Login terminal:**
   - Pestaña Viajes: ve los viajes autorizados.
   - Vender: la venta aparece en Vendidos del dueño (después del
     drenado) y en Vendidos del terminal.

4. **Login soporte:**
   - Pestaña Vendidos (con dueño elegido): ve las ventas.
   - Pestaña Rendiciones / Liquidaciones: ve el detalle al
     clickear "Ver detalle" (fix v81i + v81k).

5. **`?reparar_arboles=1` dos veces:** segunda corrida en `0`.

---

## 6. Reparación del admin link sin tocar soportes

Si por alguna razón el admin link (`us_Administrador → usuarios`)
desaparece pero **no querés** correr `?migrar_topologia=1` (porque
va a crear `soporte` singular legacy), existe `?reparar_topologia=1`
que ya lo repone **sin tocar soportes**. Es el paso 8 de la
secuencia. Usalo solo si necesitás forzar el admin link.

---

## 7. Advertencias

**Idempotencia.** Todos los pasos son idempotentes: correrlos dos
veces no rompe nada. Pero:

- **El paso 1 (`migrar_a_sql64`)** reescribe todo. Si lo corrés con
  un grafo cargado en memoria, pierde los cambios no guardados.
  Correrlo **solo** con el grafo en estado conocido (por ejemplo,
  después de un reset).
- **El paso 7 (`migrar_topologia`)** en un grafo con soportes
  creados post-v81l crea `soporte` singular. **Saltearlo** (ver
  advertencia en §3.7).
- **El paso 9 (`reparar_arboles`)** con el fix v81n es seguro. Antes
  del fix, borraba `hmi_<term>` de los compartidos. Si todavía
  tenés el fix viejo, **no correrlo** hasta aplicar v81n.

**Grafo parcial.** Todos los pasos asumen **carga total** (GET sin
`nombre_solicitante`). Con carga parcial, algunos fallan silencioso
porque `usuarios` no está en memoria. **Correrlos siempre desde el
navegador, logueado como admin o sin sesión, no desde el flujo de
la app.**

**Backup.** Antes de empezar, backup de las tablas SQL64:

```sql
-- backup de las 3 tablas nuevas del grafo app
CREATE TABLE nodo_contexto_backup AS SELECT * FROM nodo_contexto;
CREATE TABLE adyacente_contexto_backup AS SELECT * FROM adyacente_contexto;
CREATE TABLE contexto_backup AS SELECT * FROM contexto;
```

Si algo sale mal, `TRUNCATE` de las tablas + `INSERT ... SELECT`
desde el backup + correr los pasos de nuevo.

---

## 8. Historial

### 12/10/26
Secuencia original (v1.5piloto.79j):
```
index.php?migrar_a_sql64=1
index.php?migrar_usuarios_especiales=1
index.php?migrar_niveles_usuario=1
index.php?migrar_compartidos_terminal=1
index.php?construir_arboles_compartidos=1
index.php?repuntar_terminales_compartido=1
index.php?migrar_topologia=1
index.php?reparar_topologia=1
```

### 13/10/26
Se agregó `?reparar_arboles=1` como red de seguridad, seguido de
"entrar como dueño a Vendidos" para drenar el buzón.

**Problema detectado:** `?reparar_arboles=1` rompía los compartidos
(borraba `hmi_<term>`), y la siguiente venta del terminal dejaba
huérfanas las ventas viejas.

**Fix aplicado en v1.5piloto.81n:** `_reparar_arboles_precalcular`
indexa por nombre de enlace (no por nombre fijo) y respeta
`p_<term>` / `hd_<term>`. Con el fix, `?reparar_arboles=1` es
seguro y el test de aceptación es "dos corridas seguidas → 0
cambios".

### Pendiente (tanda B, próximamente)
Rediseño de `Arbol.php` a "solo el último hijo tiene `p`". Implica
reescribir `_reparar_arboles_precalcular` para no depender de `p`
en todos los hijos. Se retoma después de cerrar la línea de
migraciones actual.