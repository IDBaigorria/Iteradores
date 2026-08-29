<?php
/**
 * Manejador de peticiones POST (API interna).
 *
 * Este archivo es invocado por index.php cuando se recibe una petición POST
 * con el parámetro 'accion'. Su función es delegar el procesamiento al
 * enrutador central de la aplicación, que despachará la acción solicitada
 * a los módulos correspondientes.
 *
 * ## Estructura de nodos actual (v1.5piloto.6)
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
 *
 * **Observaciones:**
 * - Los enlaces `efectivo`, `banco` y `dueno` solo existen en nodos de nivel `terminal`.
 * - El enlace `terminales` solo existe en nodos de nivel `dueno`.
 * - `contrasena`, `nombre_real` y `email` pueden no existir si no se proporcionaron.
 * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.
 * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención
 *   del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.
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
 *   | `asientos`  | Nodo con dato string numérico: cantidad de asientos. |
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
 * @version   1.5piloto.6
 */

// El framework y los módulos de la aplicación ya fueron cargados en index.php.
// Aquí simplemente ejecutamos el enrutador con los datos recibidos.
enrutar_peticion_post($_POST['accion'] ?? '', $_POST);