# Plan — Migrar pasajeros de micro

Este archivo documenta el requerimiento completo para agregar
una funcionalidad de **migración de pasajeros entre micros de
un mismo viaje**. Es autocontenido: se pega en una nueva
conversación junto con los prompts principales
(`prompt_framework_iteradores.md`, `prompt_piloto.md`,
`prompt_seguridad.md`, `prompt_sistema_scripts_v2.md`,
`plan_actual.md`).

**Estado:** plan aprobado, sin código todavía. Se implementa
en tandas (backend → frontend → tests).

---

## 1. Para qué sirve

El dueño necesita cambiar el vehículo de un micro de un viaje
(por rotura, disponibilidad, cambio de último momento). Hoy no
hay forma de mover los pasajeros ya vendidos/reservados de un
micro a otro. La única salida es cancelar venta por venta y
volver a vender, lo que implica:
- Devolver plata.
- Perder cupones, rendiciones, cancelaciones asociadas.
- Re-vender con la config de pago actual (que puede haber
  cambiado).

La migración mueve los asientos ya ocupados del micro viejo al
micro nuevo, **preservando** la venta, los cupones, las
reservas y el estado de cada asiento. El dueño después elimina
el micro vacío con el botón existente (`eliminar_micro_de_viaje`).

**Caso de uso típico:**
1. El dueño agrega un micro vacío al viaje (vehículo nuevo,
   ya con asientos configurados).
2. Aprieta "Migrar pasajeros" en la tarjeta del micro viejo.
3. Elige el micro destino en el modal.
4. Confirma. Los pasajeros se mueven.
5. Elimina el micro viejo con el botón "Eliminar micro".

---

## 2. Decisiones tomadas (cerradas)

No re-abrir estas preguntas en la nueva conversación.

- **Mapeo por número de asiento.** El asiento número N del
  origen se mapea al asiento número N del destino. Si el
  destino no tiene el asiento N, la migración falla entera.
- **Todo o nada.** Si un asiento no se puede migrar, la
  migración falla sin mover nada. No hay migración parcial.
- **Destino debe estar vacío.** Solo se permite migrar a un
  micro cuyos asientos estén todos en `libre` o `no disponible`.
  Si tiene algún `vendido`, `reservado` o `seleccionado`, la
  migración falla con mensaje claro.
- **No venta en curso.** Si hay asientos `seleccionado` en
  cualquier micro del viaje, la migración falla. El dueño tiene
  que reiniciar la selección antes.
- **Estados que se migran:**
  - `vendido` → migra. Conserva `pasajero`, `venta`. Actualiza
    `venta->micro` y `asiento-en-venta->asiento`.
  - `reservado` con pasajero → migra. Conserva `reservado_por`
    y `pasajero`.
  - `reservado` sin pasajero → migra. Conserva `reservado_por`.
- **Estados que NO se migran:**
  - `libre` → no hay nada que migrar.
  - `no disponible` → se queda en el micro origen y se va con
    él al eliminarlo. **No** se propaga la marca al destino.
    Motivo: el `no disponible` es una decisión del dueño sobre
    el layout del vehículo original; el vehículo nuevo puede
    tener otro layout.
  - Si el asiento N del destino **ya viene** `no disponible`
    (porque el vehículo nuevo lo trae así), se respeta y no se
    toca.
- **Roles que pueden migrar:** dueño, admin, soporte.
  Terminal **no**. El backend valida nivel. El botón no aparece
  en la UI del terminal.
- **Botón por tarjeta de micro.** En el detalle del viaje,
  junto a "Eliminar micro" y "Editar monto". El modal pide solo
  el micro destino (el origen es el de la tarjeta).
- **Aviso si los layouts difieren.** Si la cantidad de columnas
  o pisos del origen y del destino difieren, el modal lo avisa.
  La migración procede igual (el mapeo es por número, no por
  posición física).
- **`venta->micro` se actualiza.** Crítico: si no se actualiza,
  la venta queda apuntando a un micro que se va a eliminar.
- **`asiento-en-venta->asiento` se actualiza.** Crítico: el
  asiento-en-venta persistente apunta al asiento real del micro
  viejo. Hay que redirigirlo al asiento del micro nuevo.
- **`cupones`, `opciones_cobro`, `punto_subida_bajada` y
  `hora_subida_bajada`** no se tocan. Ya están en la venta o en
  el asiento-en-venta, no en el asiento. La migración no los
  afecta.
- **Cancelaciones**: no se tocan. `micro_visible` en el nodo
  Cancelación es histórico y refleja el micro original, no el
  nuevo. Correcto.

---

## 3. Plan de backend

### 3.1. Función

`migrar_pasajeros_de_micro(string $nombre_dueno, string $nombre_viaje, string $nombre_micro_origen, string $nombre_micro_destino): array`

Vive en `Aplicacion/Viajes/ViajeMicros.php` (junto a
`agregar_micro_a_viaje`, `eliminar_micro_de_viaje`, etc.).

Devuelve `{exito: bool, migrados: int, detalles: array, error: string|null}`.

### 3.2. Chequeos previos (en orden)

Todos los chequeos se hacen **antes** de modificar nada. Si uno
falla, se aborta y no se toca el grafo.

1. Existe el contenedor `micros` del viaje.
2. Existen ambos micros en el contenedor.
3. `$nombre_micro_origen !== $nombre_micro_destino`.
4. No hay asientos en estado `seleccionado` en ningún micro del
   viaje. Si los hay, error: "Hay una venta en curso. Reiniciá
   la selección antes de migrar."
5. El micro destino está vacío: todos sus asientos en `libre` o
   `no disponible`. Si no, error: "El micro destino ya tiene
   asientos ocupados. Migrá a un micro vacío."
6. Capacidad suficiente: cantidad de asientos ocupados en el
   origen (`vendido` + `reservado`) ≤ cantidad total de asientos
   del destino.
7. Todos los asientos ocupados del origen tienen un asiento
   equivalente (mismo número) en el destino. Si falta uno,
   error: "El micro destino no tiene el asiento N. No se puede
   migrar."

### 3.3. Recolección del origen

Recorrer los asientos de cada piso del micro origen (lista
circular `primer`/`siguiente`). Por cada asiento, según su
estado:

| Estado origen | Acción |
|---|---|
| `vendido` | Agregar a la lista de migración con `{numero, pasajero, venta, estado_origen: "vendido"}` |
| `reservado` (con o sin pasajero) | Agregar con `{numero, reservado_por, pasajero?, estado_origen: "reservado"}` |
| `seleccionado` | Error (ya cubierto en 3.2 paso 4) |
| `no disponible` | No migrar. Se queda en el origen. |
| `libre` | No migrar. |

### 3.4. Aplicación de la migración

Para cada asiento de la lista:

1. Buscar el asiento del micro destino con el mismo `numero`.
2. Cambiar su estado:
   - Si origen era `vendido`: destino pasa a `vendido`.
     - `destino->_adyacente_en($pasajero_origen, 'pasajero')`.
     - `destino->_adyacente_en($venta_origen, 'venta')`.
     - Actualizar `venta->micro` para que apunte al micro destino.
     - Actualizar el asiento-en-venta persistente: encontrar el
       nodo asiento-en-venta dentro de la venta (recorriendo
       `venta->asientos->primer/siguiente`) cuyo `asiento`
       apunta al asiento origen. Cambiar su `asiento` para que
       apunte al asiento destino.
   - Si origen era `reservado`: destino pasa a `reservado`.
     - `destino->_adyacente_en($reservado_por_origen, 'reservado_por')`.
     - Si tenía pasajero, `destino->_adyacente_en($pasajero_origen, 'pasajero')`.
3. Limpiar el asiento del micro origen:
   - `estado->_dato('libre')`.
   - `eliminar_adyacente('pasajero')`.
   - `eliminar_adyacente('venta')`.
   - `eliminar_adyacente('reservado_por')`.
4. Registrar en `detalles` la migración: "Asiento N: vendido → vendido" o "Asiento N: reservado → reservado".

**Transaccionalidad.** Los chequeos previos completos garantizan
que la aplicación no falla a mitad. Pero por defensa, envolver
la fase de aplicación en un `try/catch`. Si algo falla, el
grafo en memoria queda modificado, pero **no se guarda** (no se
llama a `guardar_ambos`). El backend responde `exito: false` con
el error. El grafo persistido queda intacto.

### 3.5. Post-migración

- `actualizar_contadores_micro($micro_origen)` y `($micro_destino)`.
- `actualizar_contadores_viaje($nombre_viaje, $nombre_dueno)`.
- `guardar_ambos(ConfiguracionApli::NOMBRE_APP)`.
- Devolver `{exito: true, migrados: N, detalles: [...], origen_vacio: bool}`.

---

## 4. Plan de frontend

### 4.1. Botón

En la tarjeta de cada micro del detalle del viaje, junto a los
botones existentes ("Eliminar micro", "Editar monto", etc.).

Texto: **"Migrar pasajeros a..."** (o similar).
Visible solo si:
- Hay al menos otro micro en el viaje.
- El nivel del usuario es dueño, admin o soporte.
- No hay venta en curso (ningún asiento `seleccionado` en
  ningún micro).

Si el usuario es terminal, el botón no aparece.

### 4.2. Modal

Al clickear el botón:
- Abre un modal (modal genérico o apilado, a definir por el
  patrón existente).
- Título: "Migrar pasajeros de <micro origen> a...".
- Select con los otros micros del viaje. **Filtrado**: solo
  micros cuyos asientos estén todos en `libre` o `no disponible`.
  Si un micro no califica, no aparece en el select.
- Si no hay ningún micro disponible: mensaje "No hay otros
  micros vacíos en este viaje. Agregá uno nuevo primero." y el
  botón Confirmar deshabilitado.
- Aviso con la cantidad de asientos a migrar: "Se van a mover N
  asientos (X vendidos, Y reservados)."
- Si los layouts difieren (columnas, pisos): aviso extra
  "El micro destino tiene layout distinto al origen. Los
  números de asiento se mantienen, pero la disposición física
  puede cambiar."
- Botón "Confirmar" y "Cancelar".
- Confirmación final con `confirm()` de "Vas a mover N asientos.
  ¿Continuar?".

### 4.3. Envío y respuesta

- POST a `viajes/migrar_pasajeros` con `{nombre_dueno, nombre_viaje, nombre_micro_origen, nombre_micro_destino}`.
- Al éxito: toast "N asientos migrados", cerrar modal,
  refrescar el detalle del viaje.
- Al error: mostrar el mensaje del backend sin cerrar el modal.

### 4.4. Endpoint del enrutador

Nueva subacción en `Aplicacion/Enrutador.php`:
`viajes/migrar_pasajeros`.

Valida nivel (`dueno`, `admin`, `soporte`), valida que el
`nombre_dueno` del POST coincida con el solicitante o que el
solicitante sea admin/soporte. Pasa los 4 strings a
`migrar_pasajeros_de_micro`.

---

## 5. Casos borde

Todos cubiertos por los chequeos previos. Ninguno requiere
manejo especial en el frontend más allá de mostrar el mensaje
del backend.

- Micro destino con algún asiento ocupado → error de chequeo 5.
- Micro destino con capacidad insuficiente → error de chequeo 6.
- Micro destino sin el asiento N → error de chequeo 7.
- Ventas en curso → error de chequeo 4.
- Origen y destino iguales → error de chequeo 3.
- Micro origen sin asientos ocupados → migración vacía (0
  migrados). Es válida: no hace nada. El botón podría no
  aparecer si el micro origen no tiene asientos ocupados.
- Layout distinto → aviso en el modal, no error.
- Asiento `no disponible` en origen → se queda, no se migra.
- Asiento `no disponible` en destino → se respeta, no se toca.

---

## 6. Tests del plugin

Sugeridos para una tanda de tests después de la implementación.

1. **`migrar_pasajeros_vendidos_exitoso`**: crear viaje, agregar
   2 micros, vender 2 asientos en el micro A, migrar a B,
   verificar que los 2 asientos están en B, la venta apunta a B,
   y el micro A está vacío.
2. **`migrar_reservas`**: reservar 1 asiento sin pasajero y 1
   con pasajero en A, migrar a B, verificar que ambas reservas
   están en B con sus marcas (`reservado_por`, `pasajero`).
3. **`migrar_falla_capacidad`**: micro B con menos asientos que
   ocupados en A.
4. **`migrar_falla_destino_ocupado`**: micro B con un asiento ya
   vendido.
5. **`migrar_falla_venta_en_curso`**: micro A con un asiento
   `seleccionado`.
6. **`migrar_falla_asiento_inexistente`**: origen con asiento 44,
   destino con 42 asientos.
7. **`migrar_actualiza_venta`**: la venta migrada sigue
   funcionando después. Verificar por `ventas/obtener` que
   `micro` es el nuevo y que los cupones y opciones están
   intactos.

---

## 7. Impacto en docs

- **`aplicacion_POST.php`**: nota en `Nodo Venta Persistente` y
  `Nodo Asiento` de que `micro` y `asiento` pueden cambiar por
  migración.
- **`prompt_piloto.md`**:
  - §5.8 (ViajeMicros): agregar `migrar_pasajeros_de_micro`.
  - §4.9 (Nodo Viaje): nota sobre el cambio de micro.
- **`plan_actual.md`**:
  - §2: nueva línea de arco cerrado.
  - §4.5: si aplica, actualizar próximas tandas.
- **`miscelaneas/migraciones.md`**: no cambia. La migración de
  pasajeros es una operación, no una migración de estructura.

---

## 8. Plan de ejecución por tandas

### Tanda 1 — Backend

- Nueva función `migrar_pasajeros_de_micro` en
  `Aplicacion/Viajes/ViajeMicros.php`.
- Nueva subacción `viajes/migrar_pasajeros` en
  `Aplicacion/Enrutador.php`.
- Sin frontend todavía. Se testea por POST directo (con curl o
  el plugin).

### Tanda 2 — Frontend

- Botón "Migrar pasajeros a..." en la tarjeta de cada micro.
- Modal con select de micros destino, avisos, confirmación.
- Wire-up del POST y manejo de respuesta.
- Actualización del HTML si hace falta.
- CSS si hace falta (probablemente reusa `.modal` existente).

### Tanda 3 — Tests del plugin + docs

- Los 7 tests de §6.
- Actualización de los 4 docs de §7.

Cada tanda es un `aplicar_cambios.php` separado, con su commit.

---

## 9. Archivos que el asistente debe pedir

Para la **Tanda 1** (backend):

- `Aplicacion/Viajes/ViajeMicros.php` completo (para ver el
  patrón de las funciones existentes y agregar la nueva).
- `Aplicacion/Viajes/ViajeAsientos.php` (para ver cómo se
  actualizan los asientos y los asientos-en-venta).
- `Aplicacion/Ventas/Venta.php` (para ver el patrón de
  `_crear_nodo_cancelacion` y las funciones que tocan ventas).
- `Aplicacion/Enrutador.php` completo (o al menos el módulo
  `viajes`).
- `Aplicacion/FuncionesAuxiliares.php` (para ver
  `_destruir_campos_simples` y otros helpers).

Para la **Tanda 2** (frontend):

- `Aplicacion/Viajes/viajes-micros.js` completo.
- `Aplicacion/Viajes/viajes-nucleo.js` (para ver el detalle
  del viaje).
- `aplicacion_GET.html` (para ver el modal del viaje).
- `estilos-viajes.css` (si hay que agregar estilos al modal).

Para la **Tanda 3** (tests):

- `iteradoresJS/Aplicacion/pruebas/catalogo.js` (para saber
  dónde enganchar los tests).
- `iteradoresJS/Aplicacion/pruebas/_micros_helpers.js` (para
  reusar helpers de setup).
- `iteradoresJS/Aplicacion/pruebas/prueba_NN_*.js` (los
  existentes sobre micros, para ver el patrón).

**Regla:** pedir antes de armar cada `aplicar_cambios.php`. No
trabajar de memoria.

---

## 10. Cómo retomar

Pegar en una nueva conversación, en este orden:

1. `prompts/plan_actual.md`
2. `prompts/prompt_framework_iteradores.md`
3. `prompts/prompt_piloto.md`
4. `prompts/prompt_seguridad.md`
5. `prompts/prompt_sistema_scripts_v2.md`
6. `prompts/plan_migrar_micro.md` (este archivo)

Y decir: "Retomamos el plan de migración de pasajeros entre
micros. Arrancamos por la Tanda 1 (backend). Pedime los
archivos que necesitás."

El asistente debe pedir los archivos de §9, no trabajar de
memoria. Antes de armar el `aplicar_cambios.php`, consensuar
los detalles finos de la implementación (por ejemplo, dónde
exactamente va el botón en la tarjeta, cómo se llama la
subacción, qué formato tiene el JSON de respuesta).

---

**FIN DEL PLAN**