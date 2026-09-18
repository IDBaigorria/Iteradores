<?php
/**
 * Manejador de peticiones POST (API interna).
 *
 * Este archivo es invocado por index.php cuando se recibe una petición POST
 * con el parámetro 'accion'. Su función es delegar el procesamiento al
 * enrutador central de la aplicación, que despachará la acción solicitada
 * a los módulos correspondientes.
 *
 * ## Estructura de nodos actual (v1.5piloto.47c)
 *
 * ### Nodos raíz especiales
 *
 * - `"usuarios"` (nodo especial)
 *   Contiene a todos los usuarios del sistema. Cada enlace saliente tiene como
 *   nombre el **nombre de usuario** (string) y apunta al nodo del usuario.
 *
 * - `"sesiones"` (nodo especial)
 *   Contiene las sesiones activas. Cada enlace saliente tiene como nombre el
 *   **token de sesión** (string) y apunta al nodo de sesión.
 *
 * Ya no existe un nodo especial `"pasajeros"`. Los pasajeros ahora se almacenan
 * en un contenedor `pasajeros` que cuelga del nodo usuario dueño (ver Nodo Usuario).
 *
 * ### Nodo Usuario (dato del nodo: nombre de usuario)
 *
 * Cada usuario es un nodo cuyo dato es el **nombre de usuario** (string).  
 * Toda la información adicional se almacena en enlaces salientes.
 *
 * | Enlace          | Nodo destino y dato esperado                                                         |
 * |-----------------|---------------------------------------------------------------------------------------|
 * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"` o `"terminal"`.                           |
 * | `codigo_acceso` | Nodo con dato string: código único de acceso.                                         |
 * | `contrasena`    | Nodo con dato string: hash de contraseña (opcional).                                  |
 * | `nombre_real`   | Nodo con dato string: nombre real o visible (opcional).                               |
 * | `email`         | Nodo con dato string: correo electrónico (opcional).                                  |
 * | `efectivo`      | Nodo con dato string numérico: monto en efectivo (solo para `terminal`). Inicia en `"0"`. |
 * | `banco`         | Nodo con dato string numérico: monto en cuenta bancaria (solo para `terminal`). Inicia en `"0"`. |
 * |                 | Este nodo tiene además enlaces salientes:                                               |
 * |                 | ├─ `nombre` → Nodo con dato string: nombre del banco.                                  |
 * |                 | └─ `cuenta` → Nodo con dato string: número de cuenta bancaria.                         |
 * | `dueno`         | Enlace directo al nodo del usuario dueño (solo para `terminal`).                       |
 * |                 | El nodo destino tiene como dato el nombre del dueño (string).                          |
 * | `terminales`    | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes con nombre de cada terminal apuntando a su nodo usuario.          |
 * | `empresas`      | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes con nombre de cada empresa apuntando a su nodo empresa.           |
 * | `viajes`        | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes con nombre de cada viaje apuntando a su nodo viaje.               |
 * | `pasajeros`     | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes con nombre = **DNI** del pasajero apuntando a su nodo pasajero.   |
 * | `ventas`        | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes: utiliza árbol (hmi/hd) para almacenar las ventas. El primer hijo es `hmi`, los siguientes hermanos se acceden con `hd`. |
 * | `venta_actual`  | Enlace a un nodo venta actual (solo para `terminal`). Si no existe venta activa, el enlace no existe. |
 *
 * **Observaciones:**
 * - Los enlaces `efectivo`, `banco`, `dueno` y `venta_actual` solo existen en nodos de nivel `terminal`.
 * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros` y `ventas` solo existen en nodos de nivel `dueno`.
 * - `contrasena`, `nombre_real` y `email` pueden no existir si no se proporcionaron.
 * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.
 * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención
 *   del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.
 *
 * ### Nodo Pasajero (dato del nodo: DNI)
 *
 * Los pasajeros cuelgan del contenedor `pasajeros` del nodo usuario dueño.
 * Cada enlace saliente del contenedor tiene como nombre el DNI (string) y apunta al nodo pasajero.
 *
 * | Enlace              | Nodo destino y dato esperado                          |
 * |---------------------|-------------------------------------------------------|
 * | `nombres`           | Nodo con dato string: nombres del pasajero.           |
 * | `apellido`          | Nodo con dato string: apellido del pasajero. Puede estar vacío si el pasajero se migró desde una sola palabra y todavía no se editó. |
 * | `email`             | Nodo con dato string: correo electrónico (opcional).  |
 * | `celular`           | Nodo con dato string: celular personal.               |
 * | `celular_emergencia`| Nodo con dato string: celular de emergencia.          |
 * | `fecha_nacimiento`  | Nodo con dato string: fecha de nacimiento (YYYY-MM-DD). |
 * | `localidad`         | Nodo con dato string: localidad del pasajero.         |
 * | `direccion`         | Nodo con dato string: dirección del pasajero.         |
 * | `ficha_salud`       | Nodo contenedor con dato vacío (opcional).            |
 * |                     | Contiene los siguientes enlaces directos a nodos con dato string: |
 * |                     | ├─ `grupo_sanguineo` → string (ej. "O+", "Desconocido"). |
 * |                     | ├─ `obra_social` → string (texto libre).              |
 * |                     | ├─ `alergias` → string (texto libre).                 |
 * |                     | ├─ `enfermedades` → string (texto libre).             |
 * |                     | ├─ `medicamentos` → string (texto libre).             |
 * |                     | ├─ `impedimentos` → string (texto libre).             |
 * |                     | ├─ `regimenes_comida` → string (texto libre).         |
 * |                     | └─ `observaciones` → string (texto libre).            |
 *
 * **Nota (nombres y apellido):** A partir de v1.5piloto.36, el campo `nombre`
 * dejó de existir. Se separó en dos enlaces: `nombres` y `apellido`. La
 * migración `migrar_nombres_pasajeros` tomó la última palabra del `nombre`
 * original como `apellido` y el resto como `nombres`. Cuando el nombre
 * original tenía una sola palabra, `apellido` quedó vacío y se completa
 * editando el pasajero. El formato de visualización es `[apellido],[nombres]`
 * (con coma, sin espacio): por ejemplo, `Pérez,Juan` o `,Juan` si no hay
 * apellido. La función `formatear_nombre_completo()` en `Pasajero.php` aplica
 * este formato, y el backend lo expone como `nombre_completo` en
 * `formatear_pasajero` y en `formatear_venta_completa`.
 *
 * **Nota (ficha de salud):** A partir de la versión 1.5piloto.26, los campos
 * que antes se almacenaban como listas enlazadas (`enfermedades`,
 * `medicamentos`, `impedimentos`, `alergias`) ahora se guardan como un único
 * string. Si existieran datos antiguos con listas, al leerlos se concatenan
 * con `"; "` y se devuelven como string.
 *
 * **Nota (DNI - formato de visualización):** A partir de v1.5piloto.37, el DNI
 * se guarda sin puntos (validado desde el frontend y el backend). Para los
 * DNIs históricos que se cargaron con puntos, el backend expone un campo
 * `dni_visible` (normalizado, sin puntos) que la UI usa para mostrar. En
 * impresión (pasajes, cupón, ficha de salud), el DNI se muestra con puntos
 * usando `formatear_dni_con_puntos`. Los helpers están en
 * `Aplicacion/FuncionesAuxiliares.php`.
 *
 * **Nota (fechas):** Las fechas se guardan en formato ISO (`YYYY-MM-DD`) pero
 * se exponen como `fecha_visible` en formato `DD/MM/YYYY` para la UI. La
 * impresión también usa `formatear_fecha_visible`.
 *
 * **Nota (validaciones):** A partir de v1.5piloto.37, los campos del formulario
 * de venta se validan en el frontend y en el backend con las funciones
 * `validar_dni`, `validar_telefono`, `validar_email`,
 * `validar_nombre_o_apellido`, `validar_fecha_nacimiento`,
 * `validar_localidad` y `validar_direccion` (en `FuncionesAuxiliares.php`).
 *
 * **Nota (pasajes activos):** A partir de v1.5piloto.39, la edición de un
 * pasajero (`actualizar_pasajero`) devuelve dos campos adicionales en la
 * respuesta JSON:
 *   - `tiene_pasajes_activos` (bool): true si el pasajero tiene al menos
 *     un asiento propio en un viaje activo.
 *   - `ventas_activas` (array): lista de `{id_venta, fecha_viaje, nombre_viaje}`
 *     de las ventas activas donde el pasajero viaja.
 *
 * Un "pasaje activo" se define como un asiento-en-venta persistente cuyo
 * enlace `pasajero` apunta al DNI consultado, dentro de una venta cuyo viaje
 * asociado tiene fecha activa. Se considera fecha activa a cualquier fecha
 * ISO posterior a hoy, o a la cadena `"a confirmar"`. **No cuenta** si el
 * DNI es solo el comprador de la venta y no tiene asiento propio (puede ser
 * el comprador y no viajar).
 *
 * La UI usa esta información para ofrecer, tras editar los datos personales
 * de un pasajero, el botón "Imprimir pasajes actualizados", que apunta a
 * `index.php?imprimir=1&tipo=pasajes_actualizados&dueno=X&dni=Y`. Ese flujo
 * (en `Impresion.php`, función `imprimir_pasajes_actualizados`) reúne los
 * pasajes propios del pasajero en todos sus viajes activos y los imprime
 * juntos en un solo HTML.
 *
 * ### Nodo Empresa
 *
 * Las empresas son nodos contenidos dentro del enlace `empresas` de un usuario dueño.
 * Se asume que el **nombre de empresa es único globalmente** (aunque varios dueños
 * pueden referenciar la misma empresa, en la práctica cada dueño tiene sus propias
 * empresas en esta versión).
 *
 * - Dato del nodo: `nombre_empresa` (string).
 * - Enlaces salientes:
 *   | Enlace      | Nodo destino y dato esperado                          |
 *   |-------------|-------------------------------------------------------|
 *   | `nombre`    | Nodo con dato string: nombre visible de la empresa.  |
 *   | `vehiculos` | Nodo contenedor con dato vacío.                       |
 *   |             | └─ Enlaces salientes: nombre = **patente del vehículo** (única globalmente). |
 *
 * ### Nodo Vehículo
 *
 * - Dato del nodo: `patente` (string).
 * - Enlaces salientes:
 *   | Enlace      | Nodo destino y dato esperado                          |
 *   |-------------|-------------------------------------------------------|
 *   | `nombre`    | Nodo con dato string: nombre visible del vehículo.   |
 *   | `foto`      | Nodo con dato string: ruta relativa de la foto (opcional). |
 *   | `asientos`  | Nodo contenedor con dato string = cantidad total de asientos. |
 *   |             | ├─ Enlace `piso_1` → Nodo piso (dato vacío).           |
 *   |             | └─ Enlace `piso_2` → Nodo piso (opcional).             |
 *
 * ### Nodo Piso
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace      | Nodo destino y dato esperado                          |
 *   |-------------|-------------------------------------------------------|
 *   | `filas`     | Nodo con dato string numérico (número de filas).     |
 *   | `columnas`  | Nodo con dato string numérico (número de columnas).  |
 *   | `asientos`  | Nodo cabeza de lista circular (dato vacío).           |
 *   |             | └─ Enlace `primer` → primer nodo asiento de la lista. |
 *
 * ### Nodo Asiento (en vehículo original o copia)
 *
 * - Dato del nodo: número de asiento (string).
 * - Enlaces salientes:
 *   | Enlace             | Nodo destino y dato esperado                          |
 *   |--------------------|-------------------------------------------------------|
 *   | `fila`             | Nodo con dato string numérico (posición en la cuadrícula). |
 *   | `columna`          | Nodo con dato string numérico (posición en la cuadrícula). |
 *   | `siguiente`        | Nodo asiento siguiente en la lista circular, o la cabeza si es el último. |
 *   | `estado`           | Nodo con dato string: `"libre"`, `"seleccionado"`, `"reservado"`, `"vendido"` o `"no disponible"`. |
 *   | `seleccionado_por` | Enlace directo al **nodo usuario** de la terminal que seleccionó el asiento. Solo existe si `estado` es `"seleccionado"`. |
 *   | `reservado_por`    | Enlace directo al **nodo usuario** del dueño que reservó el asiento para el equipo. Solo existe si `estado` es `"reservado"`. |
 *   | `pasajero`         | Enlace directo al **nodo pasajero** asociado. Puede estar presente en `"vendido"`, `"no disponible"`, o en un asiento `"reservado"` con pasajero ya asignado. |
 *   | `venta`            | Enlace directo al nodo **venta persistente** (si `estado` es `"vendido"`). |
 * 
 * **Nota (asignación de pasajeros a reservas):** A partir de v1.5piloto.38, el
 * enlace `pasajero` puede existir también en un asiento en estado `"reservado"`.
 * Esto permite que un dueño/admin reserve un asiento para el equipo y, o bien
 * asigne los datos del pasajero en el mismo paso (al reservar, con el checkbox
 * "¿Asignar datos del pasajero ahora?"), o bien asigne el pasajero más adelante
 * con el botón "Asignar pasajero" que aparece en la tarjeta del asiento mientras
 * siga reservado y sin pasajero.
 *
 * El estado `"reservado"` es el mismo en ambos casos (con o sin pasajero): no se
 * agrega un estado nuevo. La diferencia entre "reservado sin pasajero" y
 * "reservado con pasajero" se detecta por la presencia del enlace `pasajero`.
 *
 * Al liberar la reserva, se eliminan tanto `reservado_por` como `pasajero`, para
 * que el asiento quede limpio.
 *
 * Un mismo DNI no puede estar asignado a dos asientos del mismo viaje. El
 * backend lo valida con la función `_dni_asignado_en_viaje`.
 * 
 * ### Nodo Viaje
 *
 * - Dato del nodo: `nombre_viaje` (string, identificador único dentro del dueño).
 * - Enlaces salientes:
 *   | Enlace                   | Nodo destino y dato esperado                          |
 *   |--------------------------|-------------------------------------------------------|
 *   | `dueno`                  | Nodo con dato string: nombre del dueño.               |
 *   | `nombre`                 | Nodo con dato string: nombre visible del viaje.      |
 *   | `fecha`                  | Nodo con dato string: fecha (YYYY-MM-DD) o `"a confirmar"` si aún no está definida. |
 *   | `hora`                   | Nodo con dato string: hora (HH:MM) o `"a confirmar"` si aún no está definida. |
 *   | `origen`                 | Nodo con dato string: lugar de partida.              |
 *   | `destino`                | Nodo con dato string: destino.                       |
 *   | `paradas_intermedias`    | Nodo contenedor con dato vacío (opcional). Es la raíz de una lista tipo árbol. |
 *   |                          | └─ Cada parada es un Nodo Parada (ver abajo). Las paradas se enlazan con `hmi`/`hd`. |
 *   | `ocupacion`              | Nodo con dato string numérico: capacidad total de asientos del viaje. |
 *   | `disponibles`            | Nodo con dato string numérico: total asientos disponibles (capacidad - vendidos - reservados). |
 *   | `seleccionados`          | Nodo con dato string numérico: total asientos seleccionados. |
 *   | `vendidos`               | Nodo con dato string numérico: total asientos vendidos. |
 *   | `reservados`             | Nodo con dato string numérico: total asientos reservados para el equipo. |
 *   | `micros`                 | Nodo contenedor con dato vacío.                       |
 *   |                          | └─ Enlaces salientes con nombre único (`micro_1`, `micro_2`, etc.) apuntando a nodos micro. |
 *   | `terminales_autorizadas` | Nodo contenedor con dato vacío.                       |
 *   |                          | └─ Enlaces salientes con nombre de terminal apuntando a **Nodos TerminalViaje** (nodo intermedio, ver abajo). |
 *   | `opciones_avanzadas`     | Nodo contenedor con dato vacío (opcional).            |
 *   |                          | ├─ `mostrar_ficha_medica` → string: `"1"` si se debe mostrar la opción de ficha médica en la venta, `"0"` en caso contrario. |
 *   |                          | ├─ `restriccion_edad` → string: `"1"` si se aplica restricción de edad, `"0"` en caso contrario. |
 *   |                          | ├─ `edad_minima` → string numérico: edad mínima permitida (default `"18"`). |
 *   |                          | ├─ `edad_maxima` → string numérico: edad máxima permitida (default `"80"`). |
 *   |                          | ├─ `permite_efectivo` → string: `"1"` si se permite vender en efectivo, `"0"` en caso contrario. Default `"1"`. |
 *   |                          | ├─ `cuotas_efectivo_max` → string numérico 1-12: máximo de cuotas permitidas en efectivo. Default `"3"`. |
 *   |                          | ├─ `permite_transferencia` → string: `"1"` si se permite vender por transferencia, `"0"` en caso contrario. Default `"1"`. |
 *   |                          | └─ `cuotas_transferencia_max` → string numérico 1-12: máximo de cuotas permitidas por transferencia. Default `"1"`. |
 *
 * **Nota (condiciones de pago):** A partir de v1.5piloto.32, las condiciones de
 * pago (métodos habilitados y máximo de cuotas por método) son configurables por
 * viaje. Cada nodo TerminalViaje puede opcionalmente tener un override (ver abajo).
 * La validación efectiva se hace en `confirmar_venta_actual`, que resuelve el
 * valor por método así: override del TerminalViaje > configuración del viaje >
 * default duro. Ya no hay una regla fija de "transferencia siempre 1 cuota":
 * ahora es configurable.
 *
 * ### Nodo Parada (dentro de `paradas_intermedias` de un viaje)
 *
 * Cada parada intermedia es un nodo cuyo dato es el **nombre visible** de la
 * parada (string). Están enlazados como lista tipo árbol (hmi/hd).
 *
 * - Dato del nodo: nombre de la parada (string).
 * - Enlaces salientes:
 *   | Enlace           | Nodo destino y dato esperado                          |
 *   |------------------|-------------------------------------------------------|
 *   | `hora_estimada`  | Nodo con dato string `"HH:MM"` (formato 24h). **Opcional**. Si no existe, la parada no tiene hora estimada. |
 *
 * **Nota (hora estimada):** A partir de v1.5piloto.34, cada parada puede tener
 * una hora estimada opcional. Se usa para mostrarle al pasajero cuándo sube/baja
 * en esa parada intermedia, tanto en el detalle del viaje como en el pasaje
 * impreso. Si no tiene hora, se muestra "hora a confirmar" (o equivalente) en
 * la interfaz.
 *
 * ### Nodo TerminalViaje (intermedio en `terminales_autorizadas` de un viaje)
 *
 * A partir de v1.5piloto.31, cada terminal autorizada en un viaje se representa
 * con un nodo intermedio (dato vacío) que cuelga del contenedor
 * `terminales_autorizadas`. El nombre del enlace sigue siendo el nombre de usuario
 * de la terminal. Este nodo almacena las opciones específicas de la combinación
 * viaje + terminal.
 *
 * Estructura:
 * ```
 * terminales_autorizadas (contenedor)
 * └─ nombre_terminal → Nodo TerminalViaje (dato vacío)
 *    ├─ terminal → Nodo Usuario terminal
 *    ├─ cambiar_punto_predeterminado → "0" / "1"
 *    ├─ punto_subida_bajada → Nodo parada (de paradas_intermedias del viaje) [opcional]
 *    ├─ permite_efectivo → "0" / "1" [opcional, override]
 *    ├─ cuotas_efectivo_max → "1".."12" [opcional, override]
 *    ├─ permite_transferencia → "0" / "1" [opcional, override]
 *    └─ cuotas_transferencia_max → "1".."12" [opcional, override]
 * ```
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace                         | Nodo destino y dato esperado                          |
 *   |--------------------------------|-------------------------------------------------------|
 *   | `terminal`                     | Enlace directo al **Nodo Usuario** de la terminal.    |
 *   | `cambiar_punto_predeterminado` | Nodo con dato string: `"1"` si la terminal cambia el punto de subida/bajada predeterminado del viaje, `"0"` en caso contrario. Default: `"0"`. |
 *   | `punto_subida_bajada`          | Enlace directo a uno de los nodos parada que cuelgan de `paradas_intermedias` del viaje. Solo existe si `cambiar_punto_predeterminado` es `"1"` y se eligió una parada. |
 *   | `permite_efectivo`             | Nodo con dato string: `"1"` / `"0"`. **Opcional**. Si existe, sobrescribe el valor del viaje para esta terminal. |
 *   | `cuotas_efectivo_max`          | Nodo con dato string numérico 1-12. **Opcional**. Override. |
 *   | `permite_transferencia`        | Nodo con dato string: `"1"` / `"0"`. **Opcional**. Override. |
 *   | `cuotas_transferencia_max`     | Nodo con dato string numérico 1-12. **Opcional**. Override. |
 *
 * **Nota (override de condiciones de pago):** Los 4 campos de condiciones de pago
 * son opcionales y se tratan como una unidad: si todos están vacíos, se eliminan
 * los 4 enlaces del nodo (sin override). Si al menos uno tiene valor, se escriben
 * los 4. En `guardar_opciones_terminal_viaje` se valida que no se desmarquen
 * ambos métodos a la vez. Los máximos se clipean a `[1, 12]`.
 *
 * **Nota histórica:** Antes de la v1.5piloto.31, el contenedor `terminales_autorizadas`
 * apuntaba directamente al Nodo Usuario terminal (o, en versiones aún más viejas, a un
 * nodo suelto cuyo dato era el nombre de la terminal). Se ejecutó la migración
 * `migrar_terminales_autorizadas` para convertir todos los enlaces al formato actual
 * con nodo intermedio. `formatear_viaje` lee las claves del contenedor para listar
 * las terminales; no le importa el tipo de nodo destino.
 *
 * **Nota (integridad):** `_guardar_paradas_intermedias` impide eliminar una parada
 * que esté siendo referenciada por algún TerminalViaje vía `punto_subida_bajada`.
 * Si se intenta, se devuelve un error indicando la parada y la terminal en conflicto.
 *
 * ### Nodo Micro (dentro de `micros` de un viaje)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace           | Nodo destino y dato esperado                          |
 *   |------------------|-------------------------------------------------------|
 *   | `empresa`        | Enlace directo al nodo empresa (dentro del contenedor `empresas` del dueño). El dato del nodo empresa es su identificador. |
 *   | `patente`        | **Obsoleto desde 1.5piloto.27.** Antes se guardaba un string con la patente. Ahora la patente se obtiene desde `vehiculo_copia->dato()`. Se mantiene solo como respaldo para micros antiguos. |
 *   | `monto`          | Nodo con dato string numérico: precio del pasaje.     |
 *   | `vehiculo_copia` | Enlace al nodo raíz de la copia clonada del vehículo. |
 *   | `viaje`          | Enlace directo al nodo viaje al que pertenece.        |
 *   | `ocupacion`      | Nodo con dato string numérico: capacidad total del micro (cantidad de asientos). |
 *   | `seleccionados`  | Nodo con dato string numérico: asientos seleccionados. |
 *   | `vendidos`       | Nodo con dato string numérico: asientos vendidos.     |
 *   | `reservados`     | Nodo con dato string numérico: asientos reservados para el equipo. |
 *   | `disponibles`    | Nodo con dato string numérico: asientos disponibles (capacidad - vendidos - reservados). |
 *
 * **Nota (empresa):** A partir de la versión 1.5piloto.27, el enlace `empresa` apunta
 * al nodo empresa real (no a un string suelto). Antes de esa versión se guardaba un
 * nodo con el identificador como dato. Se ejecutó una migración para convertir los
 * micros existentes. `formatear_viaje` mantiene un fallback por si aparecen micros
 * antiguos: si el nodo enlazado no tiene el enlace `nombre`, se busca la empresa
 * real en el contenedor del dueño usando el identificador.
 *
 * **Nota (patente):** A partir de la versión 1.5piloto.27, el enlace `patente` dejó
 * de escribirse en micros nuevos. La patente se obtiene siempre desde
 * `vehiculo_copia->dato()`. Se ejecutó una migración para eliminar los enlaces
 * redundantes de micros antiguos.
 *
 * ### Nodo Copia Vehículo (clonado para un micro de viaje)
 *
 * Tiene la misma estructura que un Nodo Vehículo original:
 * - Dato: patente original.
 * - Enlaces: `nombre`, `foto`, `asientos` (con pisos y lista circular de asientos).
 * - Es independiente del vehículo original; cualquier cambio posterior en el original no afecta a la copia.
 * - Sus asientos poseen `estado`, `seleccionado_por`, `reservado_por`, `pasajero` y `venta` (según corresponda).
 *
 * ### Nodo Venta Actual (temporal, colgando del nodo usuario terminal)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace     | Nodo destino y dato esperado                                      |
 *   |------------|-------------------------------------------------------------------|
 *   | `terminal` | Enlace al nodo usuario de la terminal propietaria de la venta.    |
 *   | `micro`    | Nodo con dato string = nombre del micro sobre el que se vende.    |
 *   | `viaje`    | Nodo con dato string = nombre del viaje.                          |
 *   | `asientos` | Nodo cabeza de lista circular (dato vacío).                       |
 *   |            | └─ Enlace `primer` → primer nodo asiento-en-venta temporal.       |
 *
 * ### Nodo Asiento-en-Venta (dentro de la lista circular de la venta actual)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace      | Nodo destino y dato esperado                                      |
 *   |-------------|-------------------------------------------------------------------|
 *   | `asiento`   | Enlace directo al nodo asiento real en la copia del vehículo.     |
 *   | `siguiente` | Siguiente nodo asiento-en-venta, o la cabeza si es el último.     |
 *
 * ### Nodo Venta Persistente (almacenado en el contenedor `ventas` del dueño)
 *
 * - Dato del nodo: `id_venta` (string único, ej. `venta_1234567890`).
 * - Enlaces salientes:
 *   | Enlace            | Nodo destino y dato esperado                                      |
 *   |-------------------|-------------------------------------------------------------------|
 *   | `terminal`        | Enlace al nodo usuario de la terminal que realizó la venta.       |
 *   | `viaje`           | Enlace al nodo viaje.                                             |
 *   | `micro`           | Enlace al nodo micro (copia).                                     |
 *   | `fecha_hora`      | Nodo con dato string: fecha y hora de la venta en formato `"DD/MM/YYYY HH:MM"`. |
 *   | `fecha_ultimo_pago` | Nodo con dato string: fecha y hora del último pago en el mismo formato. Si no hay pagos adicionales, es igual a `fecha_hora`. |
 *   | `metodo_pago`     | Nodo con dato string: `"efectivo"` o `"transferencia"`.           |
 *   | `total`           | Nodo con dato string numérico: monto total de la venta.           |
 *   | `cuotas`          | Nodo con dato string numérico: cantidad de cuotas elegida (rango configurable según condiciones de pago del viaje/terminal). |
 *   | `pagado`          | Nodo con dato string numérico: monto abonado inicialmente.        |
 *   | `cuotas_restantes`| Nodo con dato string numérico: cuotas que faltan pagar.           |
 *   | `comprador`       | Enlace al nodo pasajero (o un nodo con datos del comprador).      |
 *   | `asientos`        | Nodo cabeza de lista enlazada (no circular) de asientos-en-venta persistente. |
 *   |                   | └─ Enlace `primer` → primer nodo asiento-en-venta persistente.
 * | `cupones`         | Nodo contenedor con dato vacío. Es el "padre" de los cupones.
 * |                   | └─ Cada cupón es un hijo, enlazados con la estructura de árbol
 * |                   |    (`hmi`/`hd`/`p`) de miscelaneas/Arbol.php. Ver Nodo Cupón.    |
 *
 * **Nota (cuotas):** A partir de v1.5piloto.32, el rango de cuotas ya no está fijo
 * en 1-3 para efectivo y 1 para transferencia. Se resuelve al confirmar la venta
 * usando la configuración del viaje (o su override por terminal). Ver Nodo Viaje
 * y Nodo TerminalViaje.
 *
 * ### Nodo Asiento-en-Venta Persistente (dentro de la venta persistente)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace                 | Nodo destino y dato esperado                                      |
 *   |------------------------|-------------------------------------------------------------------|
 *   | `asiento`              | Enlace directo al nodo asiento real en la copia del vehículo.     |
 *   | `pasajero`             | Enlace al nodo pasajero correspondiente.                          |
 *   | `siguiente`            | Siguiente nodo asiento-en-venta (no circular, termina en null).   |
 *   | `punto_subida_bajada`  | Nodo con dato string: nombre del punto elegido por el pasajero (puede ser la parada predeterminada de la terminal o el origen del viaje). Solo existe si la terminal tenía configurada la opción de cambiar el punto predeterminado. |
 *   | `hora_subida_bajada`   | Nodo con dato string `"HH:MM"` (formato 24h). **Opcional**. Solo existe si se guardó `punto_subida_bajada` y ese punto tiene una hora estimada asociada. Si la parada no tenía hora, no se guarda este enlace. |
 *
 * **Nota (punto de subida/bajada):** A partir de v1.5piloto.33, las terminales
 * autorizadas que tengan configurada la opción `cambiar_punto_predeterminado`
 * pueden elegir, por cada pasajero, si sube/baja en la parada predeterminada
 * del viaje (preseleccionada) o en el origen del viaje. La elección queda
 * registrada como string en el nodo asiento-en-venta persistente, no como
 * enlace, para que sea histórica: si después el dueño edita el origen o borra
 * la parada, la venta no cambia. La validación es estricta en el backend:
 * el valor tiene que ser exactamente uno de los dos.
 *
 * **Nota (hora de subida/bajada):** A partir de v1.5piloto.34, si el pasajero
 * elige la parada predeterminada y esa parada tiene `hora_estimada`, se guarda
 * también el string de la hora en el enlace `hora_subida_bajada`. Si la parada
 * no tiene hora, el enlace no se crea (en la interfaz se muestra "hora estimada
 * a confirmar"). Si el pasajero elige el origen, no se guarda ni punto ni hora:
 * el origen ya tiene la hora general del viaje.
 *
 * ### Nodo Cupón (hijo del contenedor `cupones` de una venta)
 *
 * A partir de v1.5piloto.44. Cada venta tiene un contenedor `cupones`
 * con N hijos, uno por cuota. Los cupones se enlazan con la estructura
 * de árbol (`hmi`/`hd`/`p`) de `miscelaneas/Arbol.php`.
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace       | Nodo destino y dato esperado                          |
 *   |--------------|-------------------------------------------------------|
 *   | `numero`     | Nodo con dato string numérico: número de cupón (1..N). |
 *   | `monto`      | Nodo con dato string numérico: monto teórico de la cuota. El último absorbe el remanente para que la suma sea exactamente igual al total. |
 *   | `estado`     | Nodo con dato string: `"pagado"` o `"pendiente"`.     |
 *   | `fecha_pago` | Nodo con dato string `"DD/MM/YYYY HH:MM"`. Solo existe si `estado` es `"pagado"`. |
 *   | `metodo_pago`| Nodo con dato string: `"efectivo"` o `"transferencia"`. **Opcional**. Solo se escribe cuando el método del pago concreto difiere del método de la venta. Si no existe, hereda el de la venta. |
 *   | `rendido`    | Nodo con dato string: marca el cupón como rendido al dueño. Solo existe cuando el dueño ya rindió ese dinero. Se usa para calcular "A rendir" en la sección Saldos de la pestaña Vendidos. |
 *
 * **Nota (resumen en la venta):** Los enlaces `cuotas`, `pagado` y
 * `cuotas_restantes` del Nodo Venta Persistente se siguen escribiendo
 * como antes, para no romper los filtros y lecturas rápidas. Pero la
 * fuente de verdad de los cupones individuales son los nodos de esta
 * lista. El frontend lee los cupones del contenedor y calcula los
 * totales a partir de ellos.
 *
 * **Nota (migración):** Las ventas creadas antes de v1.5piloto.44 no
 * tienen contenedor `cupones`. Se ejecutó la migración
 * `migrar_cupones` (bloque temporal en `index.php`) para crearlos. Si
 * por alguna razón el contenedor no existe al leer la venta,
 * `formatear_venta_completa` deriva los cupones al vuelo.
 *
 * ### Nodo Sesión (dato del nodo: `""`)
 *
 * | Enlace      | Nodo destino y dato esperado                          |
 * |-------------|-------------------------------------------------------|
 * | `usuario`   | Nodo con dato string: nombre de usuario autenticado.  |
 * | `creado_en` | Nodo con dato string: timestamp Unix (segundos).      |
 *
 * ## Flujo de autenticación y administración
 *
 * - `autenticar_por_codigo($codigo)`: busca usuario por `codigo_acceso`; si existe,
 *   crea sesión y devuelve token. Si coincide con `Conf::CODIGO_ADMIN` y no existe,
 *   se crea automáticamente en `index.php`.
 * - `validar_token_sesion($token)`: busca en `"sesiones"` por token, obtiene nombre
 *   de usuario, y devuelve array con `nombre_usuario` y `nodo` del usuario.
 * - Panel de administración usa `listar_usuarios()`, `listar_sesiones()` y
 *   `listar_duenos()` para mostrar y gestionar datos.
 *
 * ## Sistema de "Volver" en el modal genérico
 *
 * A partir de v1.5piloto.32, `abrir_modal_generico()` acepta un tercer parámetro
 * opcional `on_volver` (callback). Si se pasa, el botón "← Volver" aparece en el
 * header del modal (a la izquierda del título). Al pulsarlo, se ejecuta el callback
 * sin cerrar el modal; el callback es responsable de reabrir el modal anterior.
 *
 * Convenciones:
 * - **X (cerrar)**: siempre cierra todo el flujo y limpia el callback.
 * - **Volver**: ejecuta el callback y NO cierra el modal (el callback decide qué
 *   mostrar). Si no hay callback, no se muestra el botón.
 * - **Cancelar** en formularios: si hay callback, se comporta como "Volver";
 *   si no, como "X". Esto se implementa en cada formulario llamando a
 *   `volver_modal_generico()` si `on_volver_modal` está seteado, y a
 *   `cerrar_modal_generico()` si no.
 *
 * El sistema no usa pila: solo hay un nivel de callback activo. Alcanza para los
 * flujos actuales (máximo 2 niveles: modal raíz → sub-modal). Si en el futuro se
 * necesita más profundidad, se puede convertir en pila sin cambiar la API pública.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.47c
 */

// El framework y los módulos de la aplicación ya fueron cargados en index.php.
// Aquí simplemente ejecutamos el enrutador con los datos recibidos.
enrutar_peticion_post($_POST['accion'] ?? '', $_POST);