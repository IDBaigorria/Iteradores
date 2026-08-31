<?php
/**
 * Manejador de peticiones POST (API interna).
 *
 * Este archivo es invocado por index.php cuando se recibe una petición POST
 * con el parámetro 'accion'. Su función es delegar el procesamiento al
 * enrutador central de la aplicación, que despachará la acción solicitada
 * a los módulos correspondientes.
 *
 * ## Estructura de nodos actual (v1.5piloto.16)
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
 * - `"pasajeros"` (nodo especial)
 *   Contiene a todos los pasajeros registrados. Cada enlace saliente tiene como
 *   nombre el **DNI** (string) y apunta al nodo del pasajero.
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
 * | `ventas`        | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes: utiliza árbol (hmi/hd) para almacenar las ventas. El primer hijo es `hmi`, los siguientes hermanos se acceden con `hd`. |
 * | `venta_actual`  | Enlace a un nodo venta actual (solo para `terminal`). Si no existe venta activa, el enlace no existe. |
 *
 * **Observaciones:**
 * - Los enlaces `efectivo`, `banco`, `dueno` y `venta_actual` solo existen en nodos de nivel `terminal`.
 * - El enlace `terminales` solo existe en nodos de nivel `dueno`.
 * - Los enlaces `empresas`, `viajes` y `ventas` solo existen en nodos de nivel `dueno`.
 * - `contrasena`, `nombre_real` y `email` pueden no existir si no se proporcionaron.
 * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.
 * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención
 *   del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.
 *
 * ### Nodo Pasajero (dato del nodo: DNI)
 *
 * Almacenado en el contenedor raíz `"pasajeros"`. Cada enlace saliente tiene como nombre el DNI.
 *
 * | Enlace              | Nodo destino y dato esperado                          |
 * |---------------------|-------------------------------------------------------|
 * | `nombre`            | Nodo con dato string: nombre completo.                |
 * | `email`             | Nodo con dato string: correo electrónico (opcional).  |
 * | `celular`           | Nodo con dato string: celular personal.               |
 * | `celular_emergencia`| Nodo con dato string: celular de emergencia.          |
 * | `fecha_nacimiento`  | Nodo con dato string: fecha de nacimiento (YYYY-MM-DD). |
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
 *   | `estado`           | Nodo con dato string: `"libre"`, `"seleccionado"`, `"vendido"` o `"no disponible"`. |
 *   | `seleccionado_por` | Enlace directo al **nodo usuario** de la terminal que seleccionó el asiento. Solo existe si `estado` es `"seleccionado"`. |
 *   | `pasajero`         | Enlace directo al **nodo pasajero** asociado (si `estado` es `"vendido"` o `"no disponible"`). |
 *   | `venta`            | Enlace directo al nodo **venta persistente** (si `estado` es `"vendido"`). |
 *
 * ### Nodo Viaje
 *
 * - Dato del nodo: `nombre_viaje` (string, identificador único dentro del dueño).
 * - Enlaces salientes:
 *   | Enlace                   | Nodo destino y dato esperado                          |
 *   |--------------------------|-------------------------------------------------------|
 *   | `dueno`                  | Nodo con dato string: nombre del dueño.               |
 *   | `nombre`                 | Nodo con dato string: nombre visible del viaje.      |
 *   | `fecha`                  | Nodo con dato string: fecha (YYYY-MM-DD).            |
 *   | `hora`                   | Nodo con dato string: hora (HH:MM).                  |
 *   | `origen`                 | Nodo con dato string: lugar de partida.              |
 *   | `destino`                | Nodo con dato string: destino.                       |
 *   | `ocupacion`              | Nodo con dato string numérico: total asientos ocupados. |
 *   | `disponibles`            | Nodo con dato string numérico: total asientos disponibles. |
 *   | `seleccionados`          | Nodo con dato string numérico: total asientos seleccionados. |
 *   | `vendidos`               | Nodo con dato string numérico: total asientos vendidos. |
 *   | `micros`                 | Nodo contenedor con dato vacío.                       |
 *   |                          | └─ Enlaces salientes con nombre único (`micro_1`, `micro_2`, etc.) apuntando a nodos micro. |
 *   | `terminales_autorizadas` | Nodo contenedor con dato vacío.                       |
 *   |                          | └─ Enlaces salientes con nombre de terminal apuntando a nodos terminal (dato = nombre). |
 *
 * ### Nodo Micro (dentro de `micros` de un viaje)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace           | Nodo destino y dato esperado                          |
 *   |------------------|-------------------------------------------------------|
 *   | `empresa`        | Nodo con dato string: identificador de la empresa original. |
 *   | `patente`        | Nodo con dato string: patente del vehículo original.  |
 *   | `monto`          | Nodo con dato string numérico: precio del pasaje.     |
 *   | `vehiculo_copia` | Enlace al nodo raíz de la copia clonada del vehículo. |
 *   | `viaje`          | Enlace directo al nodo viaje al que pertenece.        |
 *   | `ocupacion`      | Nodo con dato string numérico: asientos ocupados del micro. |
 *   | `seleccionados`  | Nodo con dato string numérico: asientos seleccionados. |
 *   | `vendidos`       | Nodo con dato string numérico: asientos vendidos.     |
 *
 * ### Nodo Copia Vehículo (clonado para un micro de viaje)
 *
 * Tiene la misma estructura que un Nodo Vehículo original:
 * - Dato: patente original.
 * - Enlaces: `nombre`, `foto`, `asientos` (con pisos y lista circular de asientos).
 * - Es independiente del vehículo original; cualquier cambio posterior en el original no afecta a la copia.
 * - Sus asientos poseen `estado`, `seleccionado_por`, `pasajero` y `venta` (según corresponda).
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
 *   | `fecha_hora`      | Nodo con dato string: timestamp Unix (segundos).                  |
 *   | `metodo_pago`     | Nodo con dato string: `"efectivo"` o `"transferencia"`.           |
 *   | `total`           | Nodo con dato string numérico: monto total de la venta.           |
 *   | `cuotas`          | Nodo con dato string numérico: cantidad de cuotas elegida (1-3).  |
 *   | `pagado`          | Nodo con dato string numérico: monto abonado inicialmente.        |
 *   | `cuotas_restantes`| Nodo con dato string numérico: cuotas que faltan pagar.           |
 *   | `comprador`       | Enlace al nodo pasajero (o un nodo con datos del comprador).      |
 *   | `asientos`        | Nodo cabeza de lista enlazada (no circular) de asientos-en-venta persistente. |
 *   |                   | └─ Enlace `primer` → primer nodo asiento-en-venta persistente.    |
 *
 * ### Nodo Asiento-en-Venta Persistente (dentro de la venta persistente)
 *
 * - Dato del nodo: vacío.
 * - Enlaces salientes:
 *   | Enlace      | Nodo destino y dato esperado                                      |
 *   |-------------|-------------------------------------------------------------------|
 *   | `asiento`   | Enlace directo al nodo asiento real en la copia del vehículo.     |
 *   | `pasajero`  | Enlace al nodo pasajero correspondiente.                          |
 *   | `siguiente` | Siguiente nodo asiento-en-venta (no circular, termina en null).   |
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
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.16
 */

// El framework y los módulos de la aplicación ya fueron cargados en index.php.
// Aquí simplemente ejecutamos el enrutador con los datos recibidos.
enrutar_peticion_post($_POST['accion'] ?? '', $_POST);