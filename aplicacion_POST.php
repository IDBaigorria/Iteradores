<?php
/**
 * Manejador de peticiones POST (API interna).
 *
 * Este archivo es invocado por index.php cuando se recibe una petición POST
 * con el parámetro 'accion'. Su función es delegar el procesamiento al
 * enrutador central de la aplicación, que despachará la acción solicitada
 * a los módulos correspondientes.
 *
 * ## Estructura de nodos actual (v1.5piloto.82d)
 *
 * ### Modelo de dos capas: viejo + nuevo coexisten
 *
 * La estructura de nodos evolucionó de un modelo plano (datos
 * directos sobre el nodo usuario) a uno con contenedores por
 * nivel de exposición (Fases B1 a B4 del plan topológico).
 * **Los dos modelos coexisten**: los enlaces viejos siguen
 * existiendo como alias a los mismos nodos físicos. La
 * eliminación del modelo viejo queda para una fase posterior,
 * cuando la batería de pruebas esté consolidada. Ver
 * "Modelo topológico" al final para el detalle completo.
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
 * - `"aplicacion"` (nodo especial, v76n)
 *   Metadata de la aplicación. Contiene el contenedor `migraciones`.
 *   Cada migración aplicada deja un enlace testigo autoreferente
 *   (`migraciones/<id>` → `migraciones`), idempotente y auto-detectable
 *   desde la pestaña Grafo.
 *
 * - `"us_<nombre>"` (nodo especial por usuario, Fase A, v76k)
 *   Cada usuario es un nodo con ID especial `us_<nombre>`. Es la raíz del
 *   subgrafo del usuario. Los enlaces `dueno` (terminal), `soporte` (dueño)
 *   y `duenos` (soporte) van en la raíz del usuario, no en un contenedor.
 *   Ver Nodo Usuario.
 *
 * Ya no existe un nodo especial `"pasajeros"`. Los pasajeros ahora se almacenan
 * en un contenedor `pasajeros` que cuelga del nodo usuario dueño (ver Nodo Usuario).
 *
 * ### Grafos separados (v1.5piloto.69)
 *
 * A partir de la v1.5piloto.69 la persistencia se divide en dos grafos:
 *
 * - Grafo de la aplicación (`Conf::NOMBRE_APP`): usuarios con datos
 *   visibles (nivel, nombre_real, email, efectivo, banco, empresas,
 *   viajes, terminales, pasajeros, ventas, rendiciones, liquidaciones,
 *   cancelaciones, venta_actual). NO contiene credenciales.
 *
 * - Grafo de credenciales (`Conf::NOMBRE_APP_CREDENCIALES`): usuarios
 *   con `codigo_hash`, `contrasena` y los campos de rate limiting
 *   (`intentos_fallidos`, `bloqueado_hasta`, `ultimo_acceso`,
 *   `ip_ultimo_acceso`), más las sesiones activas.
 *   El nombre de usuario (clave del enlace en `usuarios`) es el punto de
 *   unión entre ambos grafos.
 *
 * Ambos grafos usan el mismo esquema de nodos especiales `usuarios` y
 * `sesiones`. Los datos visibles del usuario viven en el grafo de la
 * aplicación; las credenciales y las sesiones viven en el de credenciales.
 *
 * ### Persistencia (solo SQL)
 *
 * A partir de v1.5piloto.74m, `guardar_ambos($nombre)` solo escribe en
 * SQL. El respaldo JSON automático se eliminó porque el `json_encode`
 * de todo el grafo se volvió el cuello de botella cuando el grafo
 * creció (varias terminales, vehículos, ventas). El respaldo en
 * formatos alternativos (JSON, XML) pasa a ser una acción manual del
 * admin, a implementar en el rediseño del panel.
 *
 * ### Nodo Usuario (dato del nodo: nombre de usuario)
 *
 * Cada usuario es un nodo con ID especial `us_<nombre>` (Fase A,
 * v76k). Desde la Fase B1 (v76m), los datos del usuario NO cuelgan
 * directamente del nodo usuario: cuelgan de contenedores por nivel
 * de exposición. Ver "Modelo topológico" al final del docblock.
 *
 * **IMPORTANTE — modelo viejo + modelo nuevo coexisten.**
 *
 * El modelo viejo (enlaces directos al nodo usuario, apuntados por
 * `usuarios → <nombre>` con el nombre visible) **sigue activo** como
 * espejo del nuevo. Los enlaces viejos y los nuevos apuntan a los
 * MISMOS nodos físicos. La migración a topología nueva se hizo por
 * fases (B1, B2.1-B2.4) sin eliminar los accesos viejos. La
 * eliminación completa del espejo viejo queda para una fase
 * posterior (B3 está parcialmente hecho en v77r: los accesos
 * cruzados por la raíz `usuarios` se reemplazaron por `us_<nombre>`
 * directo, pero los enlaces viejos dentro del nodo usuario siguen
 * existiendo como alias).
 *
 * #### Enlaces en la raíz del nodo usuario
 *
 * | Enlace               | Nodo destino y dato esperado                                                                |
 * |----------------------|---------------------------------------------------------------------------------------------|
 * | `publico`            | Nodo contenedor. **Dato = nombre de usuario**. Contiene `nivel`, `nombre_real`, `email`.   |
 * | `privado`            | Nodo contenedor. **Dato vacío**. Contiene los datos internos del rol (ver abajo).          |
 * | `dueno`              | **(solo `terminal`)** Enlace directo al `compartido_con_<terminal>` del dueño (Fase B2.3.5, v77f). El destino tiene `_es_compartido`. Antes de B2.3.5 apuntaba al nodo del dueño real. |
 * | `soporte`            | **(solo `dueno`)** Enlace directo al `publico` del soporte (v78f). El `publico` solo expone `nivel`, `nombre_real`, `email`. Apunta al `publico` (no al nodo del soporte) para evitar fuga cruzada entre dueños que comparten un mismo soporte. |
 * | `duenos`             | **(solo `soporte`)** Nodo contenedor. Enlaces por nombre de cada dueño asignado, apuntando al **nodo usuario real** del dueño. Es el enlace que abre la puerta del soporte hacia sus dueños. |
 * | `venta_actual`       | **(solo `terminal`)** Nodo venta actual. Si no hay venta activa, el enlace no existe.       |
 * | `nombre_real`        | **Alias viejo.** Nodo con dato string: nombre real o visible (opcional). Ver "Espejo viejo" abajo. |
 * | `email`              | **Alias viejo.** Nodo con dato string (opcional).                                            |
 * | `nivel`              | **Alias viejo.** Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.     |
 * | `efectivo`           | **Alias viejo.** Nodo con dato string numérico. En `terminal` es el saldo en caja. En `dueno` es el efectivo acumulado de las rendiciones. Inicia en `"0"`. |
 * | `banco`              | **Alias viejo.** Nodo contenedor. Dato = monto bancarizado. Tiene enlaces `nombre` y `cuenta` (strings). Para `dueno` son opcionales. |
 * | `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones`, `cancelaciones` | **Alias viejo.** Ver "Espejo viejo" abajo. Solo para `dueno`. |
 *
 * #### Contenedor `publico` (dato = nombre de usuario)
 *
 * | Enlace          | Nodo destino y dato esperado                                                                |
 * |-----------------|---------------------------------------------------------------------------------------------|
 * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.                     |
 * | `nombre_real`   | Nodo con dato string: nombre real o visible (opcional).                                     |
 * | `email`         | Nodo con dato string: correo electrónico (opcional).                                        |
 *
 * #### Contenedor `privado` (dato vacío)
 *
 * Contiene los datos internos del rol.
 *
 * **Para `dueno`:**
 *
 * | Enlace           | Nodo destino                                                                                |
 * |------------------|---------------------------------------------------------------------------------------------|
 * | `efectivo`       | Nodo con dato string numérico. Inicia en `"0"`. Efectivo acumulado de las rendiciones.      |
 * | `banco`          | Nodo contenedor. Dato = monto bancarizado. Enlaces `nombre` y `cuenta` (opcionales).        |
 * | `terminales`     | Nodo contenedor. Enlaces por nombre de cada terminal.                                       |
 * | `empresas`       | Nodo contenedor. Enlaces por nombre de cada empresa.                                        |
 * | `viajes`         | Nodo contenedor. Enlaces por nombre de cada viaje.                                          |
 * | `pasajeros`      | Nodo contenedor. Enlaces por DNI.                                                           |
 * | `ventas`         | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |
 * | `rendiciones`    | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |
 * | `liquidaciones`  | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |
 * | `cancelaciones`  | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |
 *
 * **Para `terminal`:**
 *
 * | Enlace           | Nodo destino                                                                                |
 * |------------------|---------------------------------------------------------------------------------------------|
 * | `efectivo`       | Nodo con dato string numérico. Saldo en la caja de la terminal. Inicia en `"0"`.            |
 * | `banco`          | Nodo contenedor. Dato = saldo bancarizado. Enlaces `nombre` y `cuenta`.                     |
 * | `ventas`         | Nodo contenedor. Árbol `hmi_<term>`/`hd_<term>`/`p_<term>` (parametrizado). Solo ventas propias. |
 * | `venta_actual`   | Nodo venta actual (temporal, mientras la terminal selecciona asientos).                     |
 *
 * #### Espejo viejo (compatibilidad)
 *
 * Los siguientes enlaces siguen existiendo en el nodo usuario (en la
 * raíz, no dentro de `publico`/`privado`) como **alias a los mismos
 * nodos físicos** que ahora cuelgan de `publico` o `privado`. Se
 * mantienen por retrocompatibilidad con el código que navega desde
 * `us_<nombre>` sin saber de los contenedores. Se eliminan en una
 * fase posterior (B3 completa) cuando la batería de pruebas esté
 * consolidada.
 *
 * **En el nodo usuario (raíz):**
 *
 * - `nivel`, `nombre_real`, `email` → mismo nodo físico que `publico/<campo>`.
 * - `efectivo`, `banco` → mismo nodo físico que `privado/<campo>` (para dueño y terminal).
 * - `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`,
 *   `rendiciones`, `liquidaciones`, `cancelaciones` → mismo nodo
 *   físico que `privado/<campo>` (solo para dueño).
 * - `venta_actual` (para terminal) → mismo nodo físico que `privado/venta_actual`.
 *
 * **Observaciones:**
 *
 * - Los enlaces `dueno` y `venta_actual` solo existen en nodos de nivel `terminal`.
 * - Los enlaces `efectivo` y `banco` existen en nodos de nivel `terminal` y en nodos de nivel `dueno`.
 * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones` y `cancelaciones` solo existen en nodos de nivel `dueno`.
 * - `nombre_real`, `email` pueden no existir si no se proporcionaron.
 * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.
 * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.
 *
 * #### Grafo de credenciales (separado)
 *
 * Las credenciales NO cuelgan del nodo usuario del grafo de la app:
 * viven en un grafo separado (`Conf::NOMBRE_APP_CREDENCIALES`), con su
 * propio nodo `us_<nombre>`. El punto de unión entre ambos grafos es
 * el nombre de usuario (clave del enlace en la raíz `usuarios`).
 *
 * | Enlace               | Nodo destino y dato esperado                                                          |
 * |----------------------|---------------------------------------------------------------------------------------|
 * | `codigo_hash`        | Nodo con dato string: hash bcrypt del código de acceso (opcional).                    |
 * | `contrasena`         | Nodo con dato string: hash de contraseña (opcional).                                  |
 * | `intentos_fallidos`  | Nodo con dato string numérico. Se inicializa en `"0"`. Se resetea con login exitoso.  |
 * | `bloqueado_hasta`    | Nodo con dato string: timestamp Unix hasta el cual el usuario está bloqueado. Solo existe si hubo bloqueo. Al expirar, se elimina. |
 * | `ultimo_acceso`      | Nodo con dato string `"DD/MM/YYYY HH:MM"`.                                            |
 * | `ip_ultimo_acceso`   | Nodo con dato string.                                                                 |
 *
 * 
 * #### Sección eliminada del docblock viejo
 * 
 * A continuación quedan las líneas viejas del docblock original
 * (mantenidas por compatibilidad de numeración y como referencia
 * histórica). NO se actualizan. La versión vigente es la de arriba.
 * 
 * > Cada usuario es un nodo cuyo dato es el **nombre de usuario** (string).  
 * > Toda la información adicional se almacena en enlaces salientes.
 *
 * | Enlace          | Nodo destino y dato esperado                                                         |
 * |-----------------|---------------------------------------------------------------------------------------|
 * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.              |
 * | `codigo_hash`   | **(en el grafo de credenciales)** Nodo con dato string: hash bcrypt del código de acceso. |
 * | `contrasena`    | **(en el grafo de credenciales)** Nodo con dato string: hash de contraseña (opcional).    |
 * | `intentos_fallidos` | **(en el grafo de credenciales)** Nodo con dato string numérico: cantidad de intentos fallidos consecutivos. Se resetea a `"0"` con un login exitoso o al cambiar credenciales. |
 * | `bloqueado_hasta`   | **(en el grafo de credenciales)** Nodo con dato string: timestamp Unix hasta el cual el usuario está bloqueado. Solo existe si hubo bloqueo. Al expirar, se elimina y se resetean los intentos. |
 * | `ultimo_acceso`     | **(en el grafo de credenciales)** Nodo con dato string `"DD/MM/YYYY HH:MM"`: fecha del último login exitoso. |
 * | `ip_ultimo_acceso`  | **(en el grafo de credenciales)** Nodo con dato string: dirección IP del último login exitoso. |
 * | `nombre_real`   | Nodo con dato string: nombre real o visible (opcional).                               |
 * | `email`         | Nodo con dato string: correo electrónico (opcional).                                  |
 * | `efectivo`      | Nodo con dato string numérico: monto en efectivo. Para `terminal`, es el saldo en la caja de la terminal. Para `dueno`, es la cuenta de efectivo acumulada de las rendiciones. Inicia en `"0"`. |
 * | `banco`         | Nodo con dato string numérico: monto en cuenta bancaria. Para `terminal`, es el saldo bancarizado de la terminal. Para `dueno`, es la cuenta bancaria acumulada de las rendiciones. Inicia en `"0"`. |
 * |                 | Este nodo tiene además enlaces salientes:                                               |
 * |                 | ├─ `nombre` → Nodo con dato string: nombre del banco. Para `dueno` es opcional.        |
 * |                 | └─ `cuenta` → Nodo con dato string: número de cuenta bancaria. Para `dueno` es opcional. |
 * | `dueno`         | Enlace directo al **compartido** del terminal en el dueño (solo para `terminal`).      |
 * |                 | El nodo destino tiene como dato el nombre del dueño (string) y el marcador           |
 * |                 | `_es_compartido`. A partir de v1.5piloto.77f (Fase B2.3.5a), el enlace ya no apunta    |
 * |                 | al nodo del dueño real: apunta al contenedor `compartido_con_us_<terminal>` en el      |
 * |                 | dueño. Esa es la base del aislamiento por topología.                                    |
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
 * | `rendiciones`   | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes: utiliza árbol (hmi/hd) para almacenar las rendiciones. Ver Nodo Rendición. |
 * | `liquidaciones` | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes: utiliza árbol (hmi/hd) para almacenar las liquidaciones. Ver Nodo Liquidación. |
 * | `cancelaciones` | Nodo contenedor con dato vacío (solo para `dueno`).                                    |
 * |                 | └─ Enlaces salientes: utiliza árbol (hmi/hd) para almacenar las cancelaciones. Ver Nodo Cancelación. |
 * | `venta_actual`  | Enlace a un nodo venta actual (solo para `terminal`). Si no existe venta activa, el enlace no existe. |
 * | `duenos`        | Nodo contenedor con dato vacío (solo para `soporte`).                                  |
 * |                 | └─ Enlaces salientes con nombre de cada dueño asignado apuntando a su nodo usuario.    |
 * | `soporte`       | Enlace directo al contenedor `publico` del soporte (solo para `dueno`,
 * |                 | opcional). El `publico` solo expone `nivel`, `nombre_real`, `email`.
 * |                 | A partir de v1.5piloto.78f el enlace apunta al `publico` (no al nodo
 * |                 | del soporte) para evitar fuga cruzada entre dueños que comparten un
 * |                 | mismo soporte: si apuntara al nodo real, el BFS desde un dueño
 * |                 | llegaría al soporte y desde ahí a los otros dueños del soporte.        |
 *
 * **Observaciones:**
 * - Los enlaces `dueno` y `venta_actual` solo existen en nodos de nivel `terminal`.
 * - Los enlaces `efectivo` y `banco` existen en nodos de nivel `terminal` y en nodos de nivel `dueno`. En `terminal` son el saldo en la caja y en el banco. En `dueno` son las cuentas acumuladas de las rendiciones.
 * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones` y `cancelaciones` solo existen en nodos de nivel `dueno`.
 * - `contrasena`, `nombre_real` y `email` pueden no existir si no se proporcionaron.
 * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.
 * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención
 *   del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.
 *
 * ### Nodo Compartido `compartido_con_<terminal>` (en el dueño)
 *
 * Fase B2.1 (v76q) + B2.3 (v76v-v77f) + B2.4 (v77q). Es un nodo
 * contenedor (dato vacío) que cuelga del nodo usuario del dueño.
 * Su nombre sigue la convención `compartido_con_<nombre_terminal>`
 * (sin prefijo `us_`). El nodo lleva el marcador `_es_compartido`
 * (→ `"1"`) para que las funciones detecten que están navegando un
 * compartido y usen nombres parametrizados de árbol.
 *
 * **Estructura:**
 *
 * | Enlace            | Nodo destino                                                                                |
 * |-------------------|---------------------------------------------------------------------------------------------|
 * | `_es_compartido`  | Nodo con dato `"1"`. Marcador de contexto compartido.                                        |
 * | `viajes`          | Nodo contenedor. Referencias filtradas a los viajes autorizados a esta terminal (mismos nodos viaje físicos que en `us_dueno/privado/viajes`). |
 * | `empresas`        | Nodo contenedor. Referencias a las empresas usadas por esos viajes.                          |
 * | `terminales`      | Nodo contenedor. Solo contiene el propio terminal (por nombre).                              |
 * | `ventas`          | Nodo contenedor. Árbol `hmi_<term>`/`hd_<term>`/`p_<term>` (parametrizado). Solo las ventas hechas por esta terminal. |
 * | `cancelaciones`   | Nodo contenedor. Árbol parametrizado. Solo las cancelaciones de esta terminal.               |
 * | `pasajeros`       | Nodo contenedor. **Alias** al `pasajeros` del dueño (mismo nodo físico). Los pasajeros son compartidos. |
 * | `pendientes_ventas` | Nodo contenedor (opcional). Buzón. Ver "Buzones" abajo.                                   |
 * | `pendientes_cancelaciones` | Nodo contenedor (opcional). Buzón.                                                 |
 *
 * **Nota (no tiene enlace al nodo real del dueño).** Si lo tuviera,
 * el BFS del terminal llegaría al dueño y desde ahí a todo su
 * subgrafo privado. El aislamiento depende de esto: el compartido es
 * el único punto de entrada del terminal al subgrafo del dueño.
 *
 * **Nota (árboles paralelos, Fase B2.3.4, v77e).** Las ventas de un
 * terminal viven en **dos árboles paralelos** (mismo nodo venta
 * físico). El árbol del dueño usa `hmi`/`hd`/`p`. El árbol del
 * compartido usa `hmi_<terminal>`/`hd_<terminal>`/`p_<terminal>`. Los
 * contenedores raíz son **distintos**: `us_dueno/privado/ventas` y
 * `us_dueno/compartido_con_<terminal>/ventas` son nodos diferentes,
 * con su propio `hmi`/`hmi_<term>` apuntando a la cadena de su
 * contexto. Si compartieran la raíz, el BFS del terminal alcanzaría
 * todas las ventas del dueño.
 *
 * **Nota (buzones, v79a).** Cuando el terminal crea una venta (o
 * cancelación) con carga parcial, el subgrafo en memoria no incluye
 * `us_<dueno>`, así que no puede insertar en el árbol del dueño. El
 * terminal inserta en su árbol (compartido) y deja un aviso en un
 * **buzón** del compartido (`pendientes_ventas` / `pendientes_cancelaciones`).
 * El buzón es un contenedor plano: hijos por id apuntando al mismo
 * nodo físico (no es copia). El dueño, en su próxima carga, drena el
 * buzón (`_procesar_pendientes_del_dueno`) e inserta cada nodo en su
 * árbol real. El bitmask de los nodos nuevos lo propaga el framework
 * al guardar parcial (v1.5i.8c), no la app.
 *
 * **Nota (mantenimiento, Fase B2.4, v77q).** Las operaciones que
 * afectan al compartido (autorizar/desautorizar terminal, agregar/
 * quitar micro, eliminar viaje, eliminar empresa) sincronizan o
 * desincronizan el compartido. Los helpers viven en
 * `Aplicacion/Compartidos.php`. El bug §1.5.15 (v79j) fue olvidarse
 * de esa desincronización en `limpiar_viajes_de_prueba`. Regla: toda
 * función que destruya un viaje llama a
 * `_desincronizar_viaje_de_compartidos` antes.
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
 * | `fecha_ultima_modificacion` | Nodo con dato string ISO `"YYYY-MM-DD"`: fecha de la última modificación de los datos del pasajero. Se inicializa al crear el pasajero y se actualiza en cada edición. Se usa para el autocompletado del formulario de venta. |
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
 * **Nota (fecha de última modificación):** A partir de v1.5piloto.58, cada
 * pasajero tiene un enlace `fecha_ultima_modificacion` con la fecha ISO
 * (`YYYY-MM-DD`) de la última modificación de sus datos. Se actualiza al
 * crear y al editar un pasajero. Se usa para el autocompletado del
 * formulario de venta (un pasajero reciente es candidato a autocompletar,
 * uno viejo no).
 *
 * Los pasajeros existentes se migraron con `migrar_fecha_ultima_modificacion_pasajeros`.
 * A cada uno se le puso la fecha de la venta más reciente en la que aparecía
 * (como comprador o como pasajero en algún asiento). Los que no aparecían en
 * ninguna venta quedaron con `"2000-01-01"`, que actúa como "fecha muy vieja"
 * para que cualquier venta nueva los supere.
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
 *   | `reservado_por`    | Nodo con dato string: nombre de usuario del dueño que reservó el asiento para el equipo. Solo existe si `estado` es `"reservado"`. |
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
 * **Nota (declaraciones juradas):** A partir de v1.5piloto.62, cada viaje
 * puede tener dos declaraciones juradas editables por el dueño:
 *   - `declaracion_jurada_mayor` → Nodo con dato string: HTML completo del
 *     Anexo I (declaración jurada del pasajero mayor de 18 años). Si no
 *     existe, se usa el texto por defecto.
 *   - `declaracion_jurada_menor` → Nodo con dato string: HTML completo del
 *     Anexo II (autorización y declaración del pasajero menor de 18 años).
 *     Si no existe, se usa el texto por defecto.
 *
 * El contenido es HTML crudo y se imprime tal cual, sin membrete, con
 * `index.php?imprimir=1&tipo=declaracion_jurada&dueno=X&viaje=Y&tipo_dj=mayor|menor`.
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
 *   | `rendido`    | Enlace al Nodo Rendición que incluyó este cupón. Solo existe cuando el dueño ya rindió ese dinero. Se usa para calcular "A rendir" en la sección Saldos de la pestaña Vendidos. |
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
 * ### Nodo Rendición (dato del nodo: `id_rendicion`)
 *
 * A partir de v1.5piloto.50. Cuelga del contenedor `rendiciones` del
 * usuario dueño, en lista tipo árbol (hmi/hd), igual que las ventas.
 * Representa un hecho inmutable: el retiro de dinero de las terminales.
 *
 * - Dato del nodo: `id_rendicion` (string único, ej. `rendicion_1747526400`).
 * - Enlaces salientes:
 *   | Enlace                 | Nodo destino y dato esperado                          |
 *   |------------------------|-------------------------------------------------------|
 *   | `dueno`                | Nodo con dato string: nombre de usuario del dueño.    |
 *   | `fecha_hora`           | Nodo con dato string `"DD/MM/YYYY HH:MM"`.            |
 *   | `total`                | Nodo con dato string numérico: total rendido.         |
 *   | `total_efectivo`       | Nodo con dato string numérico: total rendido en efectivo. |
 *   | `total_banco`          | Nodo con dato string numérico: total rendido por transferencia. |
 *   | `cantidad_cupones`     | Nodo con dato string numérico: cantidad de cupones rendidos. |
 *   | `cantidad_ventas`      | Nodo con dato string numérico: cantidad de ventas distintas incluidas. |
 *   | `detalle_terminales`   | Nodo contenedor con hijos en lista árbol (hmi/hd).    |
 *   |                        | └─ Cada hijo: `terminal`, `terminal_nombre_real`, `total`, `efectivo`, `banco`, `cantidad_cupones`. |
 *   | `detalle_cupones`      | Nodo contenedor con hijos en lista árbol (hmi/hd).    |
 *   |                        | └─ Cada hijo: `venta_id`, `numero_cupon`, `monto`, `metodo_pago`, `terminal`, `terminal_nombre_real`. |
 *   | `desactualizada`       | Nodo contenedor con hijos en lista árbol (hmi/hd). **Opcional**. Solo existe si se canceló alguna venta con cupones rendidos en esta rendición. |
 *   |                        | └─ Cada hijo (Nodo Ajuste): `id_cancelacion`, `id_venta`, `fecha_hora`, `motivo`, `terminal`, `terminal_nombre_real`, `monto_terminal_efectivo`, `monto_terminal_banco`, `monto_dueno_efectivo`, `monto_dueno_banco`, `monto_no_cubierto_efectivo`, `monto_no_cubierto_banco`, `aceptada_en` (opcional). |
 *
 * **Nota (enlace `rendido` en cupones):** Al confirmar la rendición,
 * cada cupón incluido recibe un enlace `rendido` que apunta al Nodo
 * Rendición. Los métodos que calculan "A rendir" chequean la existencia
 * de este enlace con `adyacente('rendido')`, así que quedan
 * automáticamente descontados.
 *
 * **Nota (inmutabilidad):** El preview del modal de rendición es
 * inmutable. El frontend manda la lista exacta de cupones que el
 * usuario vio y seleccionó. Si entre abrir y confirmar algo cambió
 * (un cupón se rindió, se canceló la venta, se pagó un cupón nuevo
 * fuera de la lista), la confirmación aborta con un mensaje genérico
 * y no se escribe nada en el grafo.
 *
 * **Nota (desactualización):** Si se cancela una venta que tiene
 * cupones ya rendidos, la rendición no se modifica en sus totales
 * (es inmutable), pero se le agrega un hijo nuevo al contenedor
 * `desactualizada` por cada cancelación. Cada hijo (Nodo Ajuste)
 * guarda los datos de la cancelación: la venta, el motivo, la
 * terminal, los montos que aporta cada cuenta (terminal vs dueño)
 * y, cuando el dueño lo marca, la fecha en que lo aceptó.
 * La pestaña Rendiciones muestra un badge de aviso mientras haya
 * ajustes pendientes de aceptar.
 *
 * **Nota (listado):** La pestaña Rendiciones permite filtrar por
 * código, rango de fecha de la rendición y terminal. El orden por
 * defecto es el más reciente primero (el contenedor usa `_hmi`).
 *
 * ### Nodo Liquidación (dato del nodo: `id_liquidacion`)
 *
 * A partir de v1.5piloto.54. Cuelga del contenedor `liquidaciones` del
 * usuario dueño, en lista tipo árbol (hmi/hd). Representa un hecho
 * inmutable: la salida de dinero del sistema, es decir, el retiro del
 * dueño desde sus cuentas.
 *
 * - Dato del nodo: `id_liquidacion` (string único, ej. `liquidacion_1747526400`).
 * - Enlaces salientes:
 *   | Enlace                 | Nodo destino y dato esperado                          |
 *   |------------------------|-------------------------------------------------------|
 *   | `dueno`                | Nodo con dato string: nombre de usuario del dueño.    |
 *   | `fecha_hora`           | Nodo con dato string `"DD/MM/YYYY HH:MM"`.            |
 *   | `total`                | Nodo con dato string numérico: total liquidado.       |
 *   | `monto_efectivo`       | Nodo con dato string numérico: monto extraído de la cuenta de efectivo. |
 *   | `monto_banco`          | Nodo con dato string numérico: monto extraído de la cuenta bancaria. |
 *   | `efectivo_restante`    | Nodo con dato string numérico: saldo de efectivo después de la liquidación. |
 *   | `banco_restante`       | Nodo con dato string numérico: saldo bancario después de la liquidación. |
 *   | `observaciones`        | Nodo con dato string: texto libre. **Opcional**. Se guarda solo si el dueño escribió algo. |
 *
 * **Nota (inmutabilidad):** Al confirmar la liquidación se valida que
 * los montos no superen el saldo actual del dueño. Si cambió desde que
 * se abrió el modal, la confirmación aborta con un mensaje genérico y
 * no se escribe nada en el grafo.
 *
 * **Nota (listado):** La pestaña Liquidaciones permite filtrar por
 * código y rango de fecha. El orden por defecto es el más reciente
 * primero.
 *
 * ### Nodo Cancelación (dato del nodo: `id_cancelacion`)
 *
 * A partir de v1.5piloto.55. Cuelga del contenedor `cancelaciones` del
 * usuario dueño, en lista tipo árbol (hmi/hd). Representa un hecho
 * inmutable: la cancelación de una compra, con el ajuste de saldos
 * que eso implicó, incluso si parte del dinero ya había sido rendido
 * o liquidado por el dueño.
 *
 * - Dato del nodo: `id_cancelacion` (string único, ej. `cancelacion_1747526400`).
 * - Enlaces salientes:
 *   | Enlace                       | Nodo destino y dato esperado                          |
 *   |------------------------------|-------------------------------------------------------|
 *   | `dueno`                      | Nodo con dato string: nombre de usuario del dueño.    |
 *   | `id_venta`                   | Nodo con dato string: ID de la venta cancelada.       |
 *   | `fecha_hora`                 | Nodo con dato string `"DD/MM/YYYY HH:MM"`.            |
 *   | `motivo`                     | Nodo con dato string. **Opcional**.                   |
 *   | `terminal`                   | Enlace al Nodo Usuario de la terminal.                |
 *   | `terminal_nombre_real`       | Nodo con dato string: nombre visible de la terminal.  |
 *   | `viaje_visible`              | Nodo con dato string: nombre visible del viaje.       |
 *   | `micro_visible`              | Nodo con dato string: nombre visible del micro.       |
 *   | `comprador_dni`              | Nodo con dato string: DNI del comprador.              |
 *   | `comprador_nombre_completo`  | Nodo con dato string: nombre completo del comprador.  |
 *   | `total_venta`                | Nodo con dato string numérico: total original de la venta. |
 *   | `devuelto_efectivo`          | Nodo con dato string numérico: devuelto al comprador en efectivo. |
 *   | `devuelto_banco`             | Nodo con dato string numérico: devuelto al comprador por transferencia. |
 *   | `cubierto_terminal_efectivo` | Nodo con dato string numérico: parte del devuelto que salió de la terminal en efectivo. |
 *   | `cubierto_terminal_banco`    | Nodo con dato string numérico: parte del devuelto que salió de la terminal en banco. |
 *   | `cubierto_dueno_efectivo`    | Nodo con dato string numérico: parte del devuelto que salió del dueño en efectivo. |
 *   | `cubierto_dueno_banco`       | Nodo con dato string numérico: parte del devuelto que salió del dueño en banco. |
 *   | `no_cubierto_efectivo`       | Nodo con dato string numérico: parte del devuelto que no pudo cubrirse (el dueño no tenía saldo). |
 *   | `no_cubierto_banco`          | Nodo con dato string numérico: idem, en banco. |
 *   | `asientos_liberados`         | Nodo con dato string numérico: cantidad de asientos liberados. |
 *
 * **Nota (ajuste al cancelar):** Al cancelar una venta se calcula cuánto
 * del total devuelto estaba en la terminal (cupones sin rendir) y cuánto
 * ya había sido rendido (cupones con enlace `rendido`). La parte en la
 * terminal se descuenta de la terminal. La parte rendida se descuenta
 * del dueño con `max(0, ...)`, y lo que no alcance a cubrirse se
 * registra en `no_cubierto_*`.
 *
 * **Nota (rendiciones afectadas):** Cada rendición que incluía cupones
 * de la venta cancelada recibe un ajuste nuevo en su enlace
 * `desactualizada` (que pasa de string a contenedor con hijos). El
 * dueño puede marcar cada ajuste como aceptado con la subacción
 * `rendiciones/aceptar_ajuste`, que escribe `aceptada_en`.
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
 * - `autenticar_por_codigo($codigo)`: busca usuario por `codigo_hash` (password_verify);
 *   si existe, crea sesión y devuelve token.
 * - `autenticar_por_usuario($nombre, $contrasena)`: busca usuario por nombre y verifica
 *   su `contrasena` (hash). Devuelve el mismo formato que `autenticar_por_codigo`.
 *
 * **Nota (migración v1.5piloto.68):** Los códigos de acceso existentes se hashearon
 * con `password_hash` y se guardaron como `codigo_hash`. El admin principal ya no se
 * crea con `codigo_acceso`: se crea con `codigo_hash` y se chequea por nombre en
 * `index.php`.
 *
 * **Nota (rate limiting, v1.5piloto.71):** Después de
 * `Conf::INTENTOS_MAXIMOS_AUTENTICACION` intentos fallidos consecutivos, el usuario
 * queda bloqueado durante `Conf::BLOQUEO_AUTENTICACION_SEGUNDOS` segundos. Mientras
 * esté bloqueado, el login devuelve el mismo error genérico que un fallo normal, y
 * el tiempo de respuesta se mantiene similar usando `Conf::HASH_DUMMY_AUTENTICACION`.
 * - `validar_token_sesion($token)`: busca en `"sesiones"` por token, obtiene nombre
 *   de usuario, y devuelve array con `nombre_usuario` y `nodo` del usuario.
 * - Panel de administración usa `listar_usuarios()`, `listar_sesiones()` y
 *   `listar_duenos()` para mostrar y gestionar datos.
 *
 * ## Modelo topológico: contenedores por nivel de exposición (v1.5piloto.78m)
 * 
 * **Estado:** implementado y con carga parcial activa. Fases B1
 * (contenedores `publico`/`privado`), B2.1 (compartidos por terminal),
 * B2.2 (contexto opcional), B2.3 (árboles paralelos + repuntado), B2.4
 * (sincronización al vuelo) y B3 (aislación de usuarios) aplicadas.
 * La carga parcial (Etapa 1c del plan) está activa: cada operación del
 * terminal carga solo su compartido; el dueño su propio subgrafo; el
 * admin todo (gracias al enlace `us_Administrador → usuarios`).
 * 
 * **Cambios de la v78f/v78m:**
 * - `us_Administrador → usuarios`: enlace creado en v78f para que el
 *   admin cargue todos los usuarios desde su propio contexto.
 * - `dueno → soporte → publico`: el enlace `soporte` del dueño apunta
 *   al `publico` del soporte (no al nodo real), para evitar fuga cruzada.
 * - Carga parcial: index.php decide entre `cargar`/`cargar_parcial`
 *   según los contextos devueltos por `_contextos_para_operacion`
 *   (Enrutador). `guardar_ambos` detecta grafo parcial y llama a
 *   `guardar_parcial` con los contextos cargados.
 * - Persistencia en SQL64 (bitmask de contextos, 3 tablas nuevas:
 *   nodo_contexto, adyacente_contexto, contexto).
 * 
 * **Flujo de carga parcial (v78g-v78s):**
 * 
 * 1. `index.php` lee `$_POST['accion']` y `$_POST['nombre_solicitante']`,
 *    y llama a `_contextos_para_operacion($accion, $sol, $_GET)`.
 * 2. La función devuelve:
 *    - `null` → carga total (`Controlador::cargar`).
 *    - `['us_<sol>']` → carga parcial (`Controlador::cargar_parcial`).
 * 3. Excepciones que cargan total: acciones de gestión de usuarios
 *    (`administrador/agregar_usuario`, `dueno/agregar_terminal`,
 *    etc.) y los informes imprimibles que usan helpers de B3.3c
 *    (`informe_cancelacion`, `informe_liquidacion`,
 *    `informe_rendicion`).
 * 4. `guardar_ambos` consulta `Controlador::contextos_cargados()`:
 *    si está vacío, `guardar`; si no, `guardar_parcial`.
 * 
 * **Bug §1.5 (viaje viejo que desaparecía): RESUELTO en
 * framework 1.5i.8a (v1.5piloto.78v).** Causa raíz: los IDs
 * numéricos del framework son efímeros; el guardado parcial
 * no puede mapear "ID del framework" ↔ "fila de la DB" entre
 * requests. Solución: `id_fila` (BIGINT AUTO_INCREMENT, PK)
 * en `nodo_contexto` como identidad estable, `id_especial`
 * para el ID especial del framework (NULL para numéricos).
 * `adyacente_contexto` referencia sus extremos por
 * `idnodo_fila`/`idadyacente_fila`. Mapa estático
 * `$_mapa_id_a_fila` relaciona ambos mundos dentro de una
 * request. El DELETE parcial borra solo aristas cuyos DOS
 * extremos están entre las filas cargadas. Ver
 * `plan_actual.md` §1.5 para el detalle completo.
 * 
 * **Bug §1.5.15 (botón limpiar viajes rompía compartidos):
 * RESUELTO en v79j.** El botón "Limpiar viajes de prueba"
 * destruía los viajes sin llamar a
 * `_desincronizar_viaje_de_compartidos`. Los compartidos
 * quedaban con aristas colgadas y el BFS del terminal se
 * rompía en la próxima carga. Fix: desincronizar antes de
 * destruir; helper `reparar_compartidos_viajes_dueno` desde
 * `?reparar_topologia=1` para recuperar. Ver `plan_actual.md`
 * §1.5.15.
 * 
 * **Reparación topológica (v78n, v78r):** `?reparar_topologia=1`
 * repara enlaces faltantes en el grafo (compartidos, viajes,
 * empresas, pasajeros). Idempotente. Solo agrega enlaces, nunca
 * borra. Se usa para recuperar el estado después de bugs de
 * guardado parcial.
 * 
 * ### Idea general
 * 
 * Cada usuario (`us_<nombre>`) deja de tener sus datos
 * colgando directamente de la raíz. En su lugar, cuelgan
 * varios contenedores, uno por cada nivel de exposición.
 * 
 * Ejemplo del dueño:
 * 
 * ```
 * us_dueno1
 * ├── publico                       (dato = "dueno1")
 * │   ├── nivel → "dueno"
 * │   ├── nombre_real → "..."
 * │   └── email → "..."
 * ├── privado
 * │   ├── efectivo, banco
 * │   ├── terminales, empresas, viajes, pasajeros
 * │   ├── ventas, rendiciones, liquidaciones, cancelaciones
 * │   └── ...
 * ├── compartido_con_us_term1       (uno por cada terminal autorizado)
 * │   ├── viajes         (solo los autorizados a term1)
 * │   ├── empresas       (solo las que usa term1)
 * │   ├── terminales     (solo term1 a sí mismo)
 * │   └── ventas         (solo las ventas de term1)
 * └── compartido_con_us_term2
 *     └── ...
 * ```
 * 
 * Y desde el lado del terminal:
 * 
 * ```
 * us_term1
 * ├── publico                       (dato = "term1")
 * │   ├── nivel → "terminal"
 * │   ├── nombre_real → "..."
 * │   └── email → "..."
 * ├── privado
 * │   ├── efectivo, banco
 * │   ├── ventas         (solo las propias)
 * │   └── venta_actual   (la venta en curso)
 * └── compartido_con_dueno
 *     ├── dueno → us_dueno1/compartido_con_us_term1
 *     └── empresas → { empresa1 → ... }
 * ```
 * 
 * Los enlaces "de permiso" (como `dueno` en el terminal
 * o `soporte` en el dueño) van directamente en la raíz
 * del usuario, no en un contenedor.
 * 
 * ### Principio de seguridad
 * 
 * La seguridad emerge de la topología. Un usuario no
 * puede acceder a un nodo si no hay un camino desde su
 * raíz hasta ese nodo. Si el terminal no tiene un enlace
 * al `privado` del dueño, no lo puede alcanzar, aunque
 * el grafo se cargue completo.
 * 
 * ### Qué va en cada contenedor
 * 
 * **Dueño (`us_duenoX`):**
 * 
 * | Contenedor | Contenido |
 * |---|---|
 * | `publico` | `nivel`, `nombre_real`, `email`. Dato = nombre de usuario. |
 * | `privado` | `efectivo`, `banco`, `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones`, `cancelaciones`. |
 * | `compartido_con_us_term1` | `viajes` (autorizados), `empresas` (usadas), `terminales` (solo term1), `ventas` (de term1). |
 * | ... | uno por cada terminal autorizado. |
 * | `soporte` | enlace directo en la raíz, si tiene soporte asignado. |
 * 
 * **Terminal (`us_termX`):**
 * 
 * | Contenedor | Contenido |
 * |---|---|
 * | `publico` | `nivel`, `nombre_real`, `email`. Dato = nombre de usuario. |
 * | `privado` | `efectivo`, `banco`, `ventas` (propias), `venta_actual`. |
 * | `compartido_con_dueno` | `dueno` (→ `us_duenoY/compartido_con_us_termX`), `empresas` (las que necesita para vender). |
 * 
 * **Soporte (`us_soporteX`):**
 * 
 * Sin cambios estructurales por ahora. Los soportes
 * acceden por código (con `_verificar_permiso_dueno`),
 * no por topología.
 * 
 * **Admin (`us_admin`):**
 * 
 * Sin cambios. Acceso total por código.
 * 
 * ### Notas de diseño
 * 
 * - **Los contenedores son directos**, no cuelgan de un
 *   contenedor intermedio `niveles`. Los niveles son la
 *   estructura principal del usuario.
 * - **Un contenedor por terminal**: como el dueño autoriza
 *   terminales por viaje, no alcanza un único
 *   `compartido_con_terminales`. Cada terminal tiene su
 *   propio contenedor de compartición.
 * - **Ventas y cupones**: la venta vive en
 *   `us_dueno1/privado/ventas`. También hay una referencia
 *   desde `us_term1/privado/ventas` (solo las ventas
 *   propias). La venta tiene dos referencias entrantes;
 *   la destrucción debe desenlazar de ambas.
 * - **`usuarios` apunta al `publico`**: el nodo `usuarios`
 *   es un índice. Cada enlace `usuarios → <nombre>` apunta
 *   al contenedor `publico` del usuario. Así
 *   `listar_usuarios` puede leer el dato y los datos
 *   públicos sin cargar el subárbol privado.
 * - **`terminales_autorizadas` de un viaje no cambia
 *   internamente.** Sigue siendo un contenedor con un
 *   TerminalViaje por terminal. Lo que cambia es cómo se
 *   llega al viaje: desde el dueño por `privado/viajes`,
 *   desde el terminal por `compartido_con_dueno/dueno/viajes`.
 * 
 * ### Plan de migración
 * 
 * Por fases, con la aplicación funcionando entre cada una:
 * 
 * 1. **Fase B1**: crear `publico` y `privado` en cada
 *    usuario. Mover los datos existentes. `usuarios`
 *    apunta al `publico`. Los `compartido_con_X` todavía
 *    no existen. Los terminales siguen accediendo como hoy.
 * 2. **Fase B2**: crear los `compartido_con_X` de cada
 *    dueño (uno por terminal autorizado) y reescribir los
 *    enlaces desde los terminales.
 * 3. **Fase B3**: eliminar los accesos viejos.
 * 
 * Cada fase es una tanda, con migración idempotente y
 * verificación en local antes de producción. Ver §8.7
 * del prompt del piloto.
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
 * ## Estado al cierre de v1.5piloto.82d (arco de cancelaciones
 * + persistencia robusta)
 *
 * **Estructura de nodos:** sin cambios estructurales. La
 * estructura documentada arriba (modelo por capas + espejo
 * viejo + estructura vieja al final) sigue vigente. **No se
 * borró nada de la estructura vieja**: se mantiene como
 * referencia hasta que se pruebe en producción.
 *
 * **Cambios de comportamiento del arco v81t-82d:**
 *
 * 1. **Árbol de cancelaciones del compartido con nombres
 *    parametrizados.**
 *    - Escritura (`_crear_nodo_cancelacion` en Venta.php):
 *      cuando el contexto es un compartido, inserta con
 *      `hmi_<term>`/`hd_<term>`/`p_<term>`. Antes insertaba
 *      con `hmi` default sobre el contenedor del compartido,
 *      que lee con parametrizados. La cancelación quedaba en
 *      el árbol pero no se encontraba al leer.
 *    - Lectura (`listar_cancelaciones_de_dueno`,
 *      `obtener_cancelacion_por_id`): usan
 *      `_nombres_arbol_para_contexto` para elegir default vs
 *      parametrizado. Antes leían siempre con `hmi`/`hd`.
 *    - Buzón `pendientes_cancelaciones`: el terminal encola,
 *      el dueño drena en su próxima carga. Sigue vigente.
 *    - Cycle guard en lectura: trackear nodos visitados por
 *      id. Defensa en profundidad contra ciclos residuales.
 *
 * 2. **`_hmi` idempotente** (miscelaneas/Arbol.php, v81v).
 *    - No-op si el hijo ya es el hmi actual.
 *    - Limpia `hd` residual cuando el padre no tenía hmi.
 *    - Antes: reinsertar un nodo que ya era el hmi generaba
 *      autoenlace `hd → sí mismo` (ciclo).
 *
 * 3. **Comandos de reparación nuevos.**
 *    - `app:reparar_cancelaciones_compartidos`
 *      (`?reparar_cancelaciones=1`): reinserta cancelaciones
 *      en ambos árboles. También repara ventas canceladas
 *      incompletas (tienen cancelación pero no autoenlace
 *      `cancelada`).
 *    - `app:reparar_cancelaciones_arboles`
 *      (`?reparar_cancelaciones_arboles=1`): detecta ciclos
 *      en las cadenas `hd`/`hd_<term>` y reconstruye como
 *      cadena lineal.
 *    - `app:auditar_topologia` (`?auditar_topologia=1`):
 *      14 verificaciones topológicas en 3 severidades. Ver
 *      `prompt_piloto.md` §3.5.
 *    - `?eliminar_huerfanos=1`: elimina los nodos no
 *      alcanzables desde raíces (fuerza carga total, la
 *      pestaña Grafo en parcial no los ve).
 *    - Todos idempotentes, todos admin/soporte.
 *
 * 4. **`cancelar_venta` chequea `guardar_ambos`.**
 *    - Si falla, `exito: false` con mensaje. Antes respondía
 *      "éxito" aunque la DB no se hubiera actualizado.
 *
 * **Bug §4.6: cerrado.** No era bug del flujo de
 * cancelaciones, era persistencia silenciosa en InfinityFree.
 * Causa raíz en 4 capas: `CREATE TEMPORARY TABLE` no
 * permitida, `mysqli_report` strict en PHP 8.4, rate limit
 * de conexiones, `cancelar_venta` no chequeaba
 * `guardar_ambos`. Fixes en framework 1.5i.8d-f (singleton
 * de conexión, `mysqli_report(OFF)`, `SELECT 1` en lugar de
 * `ping()`, `IN (...)` directo). Ver `prompt_seguridad.md`
 * §8.10 y `prompt_framework_iteradores.md` §6.7.
 *
 * **Migración del plugin:** secuencia de 12 pasos.
 * Pasos 10 (`reparar_cancelaciones`) y 11
 * (`reparar_cancelaciones_arboles`) agregados entre
 * `reparar_arboles` y el drenado. Ver
 * `miscelaneas/migraciones.md` y `prompt_plugin_piloto.md`
 * §11.1.
 *
 * **Deuda de la estructura vieja:** el bloque "Sección
 * eliminada del docblock viejo" sigue al pie de la letra.
 * Se eliminará cuando la batería de pruebas del plugin
 * valide la topología nueva en producción. Hasta entonces,
 * sigue siendo la referencia para entender el modelo
 * pre-B1.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.82d
 */

// El framework y los módulos de la aplicación ya fueron cargados en index.php.
// Aquí simplemente ejecutamos el enrutador con los datos recibidos.
enrutar_peticion_post($_POST['accion'] ?? '', $_POST);