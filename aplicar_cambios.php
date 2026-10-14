<?php
/**
 * Aplicador — aplicacion_POST.php docblock (v2).
 *
 * El script anterior falló porque el bloque de Nodo Usuario no
 * incluye el prefijo ` * ` en cada línea del PHPDoc. Este script
 * usa las líneas con prefijo.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$nodo_usuario_nuevo = [
    ' * ### Nodo Usuario (dato del nodo: nombre de usuario)',
    ' *',
    ' * Cada usuario es un nodo con ID especial `us_<nombre>` (Fase A,',
    ' * v76k). Desde la Fase B1 (v76m), los datos del usuario NO cuelgan',
    ' * directamente del nodo usuario: cuelgan de contenedores por nivel',
    ' * de exposición. Ver "Modelo topológico" al final del docblock.',
    ' *',
    ' * **IMPORTANTE — modelo viejo + modelo nuevo coexisten.**',
    ' *',
    ' * El modelo viejo (enlaces directos al nodo usuario, apuntados por',
    ' * `usuarios → <nombre>` con el nombre visible) **sigue activo** como',
    ' * espejo del nuevo. Los enlaces viejos y los nuevos apuntan a los',
    ' * MISMOS nodos físicos. La migración a topología nueva se hizo por',
    ' * fases (B1, B2.1-B2.4) sin eliminar los accesos viejos. La',
    ' * eliminación completa del espejo viejo queda para una fase',
    ' * posterior (B3 está parcialmente hecho en v77r: los accesos',
    ' * cruzados por la raíz `usuarios` se reemplazaron por `us_<nombre>`',
    ' * directo, pero los enlaces viejos dentro del nodo usuario siguen',
    ' * existiendo como alias).',
    ' *',
    ' * #### Enlaces en la raíz del nodo usuario',
    ' *',
    ' * | Enlace               | Nodo destino y dato esperado                                                                |',
    ' * |----------------------|---------------------------------------------------------------------------------------------|',
    ' * | `publico`            | Nodo contenedor. **Dato = nombre de usuario**. Contiene `nivel`, `nombre_real`, `email`.   |',
    ' * | `privado`            | Nodo contenedor. **Dato vacío**. Contiene los datos internos del rol (ver abajo).          |',
    ' * | `dueno`              | **(solo `terminal`)** Enlace directo al `compartido_con_<terminal>` del dueño (Fase B2.3.5, v77f). El destino tiene `_es_compartido`. Antes de B2.3.5 apuntaba al nodo del dueño real. |',
    ' * | `soporte`            | **(solo `dueno`)** Enlace directo al `publico` del soporte (v78f). El `publico` solo expone `nivel`, `nombre_real`, `email`. Apunta al `publico` (no al nodo del soporte) para evitar fuga cruzada entre dueños que comparten un mismo soporte. |',
    ' * | `duenos`             | **(solo `soporte`)** Nodo contenedor. Enlaces por nombre de cada dueño asignado, apuntando al **nodo usuario real** del dueño. Es el enlace que abre la puerta del soporte hacia sus dueños. |',
    ' * | `venta_actual`       | **(solo `terminal`)** Nodo venta actual. Si no hay venta activa, el enlace no existe.       |',
    ' * | `nombre_real`        | **Alias viejo.** Nodo con dato string: nombre real o visible (opcional). Ver "Espejo viejo" abajo. |',
    ' * | `email`              | **Alias viejo.** Nodo con dato string (opcional).                                            |',
    ' * | `nivel`              | **Alias viejo.** Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.     |',
    ' * | `efectivo`           | **Alias viejo.** Nodo con dato string numérico. En `terminal` es el saldo en caja. En `dueno` es el efectivo acumulado de las rendiciones. Inicia en `"0"`. |',
    ' * | `banco`              | **Alias viejo.** Nodo contenedor. Dato = monto bancarizado. Tiene enlaces `nombre` y `cuenta` (strings). Para `dueno` son opcionales. |',
    ' * | `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones`, `cancelaciones` | **Alias viejo.** Ver "Espejo viejo" abajo. Solo para `dueno`. |',
    ' *',
    ' * #### Contenedor `publico` (dato = nombre de usuario)',
    ' *',
    ' * | Enlace          | Nodo destino y dato esperado                                                                |',
    ' * |-----------------|---------------------------------------------------------------------------------------------|',
    ' * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.                     |',
    ' * | `nombre_real`   | Nodo con dato string: nombre real o visible (opcional).                                     |',
    ' * | `email`         | Nodo con dato string: correo electrónico (opcional).                                        |',
    ' *',
    ' * #### Contenedor `privado` (dato vacío)',
    ' *',
    ' * Contiene los datos internos del rol.',
    ' *',
    ' * **Para `dueno`:**',
    ' *',
    ' * | Enlace           | Nodo destino                                                                                |',
    ' * |------------------|---------------------------------------------------------------------------------------------|',
    ' * | `efectivo`       | Nodo con dato string numérico. Inicia en `"0"`. Efectivo acumulado de las rendiciones.      |',
    ' * | `banco`          | Nodo contenedor. Dato = monto bancarizado. Enlaces `nombre` y `cuenta` (opcionales).        |',
    ' * | `terminales`     | Nodo contenedor. Enlaces por nombre de cada terminal.                                       |',
    ' * | `empresas`       | Nodo contenedor. Enlaces por nombre de cada empresa.                                        |',
    ' * | `viajes`         | Nodo contenedor. Enlaces por nombre de cada viaje.                                          |',
    ' * | `pasajeros`      | Nodo contenedor. Enlaces por DNI.                                                           |',
    ' * | `ventas`         | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |',
    ' * | `rendiciones`    | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |',
    ' * | `liquidaciones`  | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |',
    ' * | `cancelaciones`  | Nodo contenedor. Árbol `hmi`/`hd`/`p`.                                                       |',
    ' *',
    ' * **Para `terminal`:**',
    ' *',
    ' * | Enlace           | Nodo destino                                                                                |',
    ' * |------------------|---------------------------------------------------------------------------------------------|',
    ' * | `efectivo`       | Nodo con dato string numérico. Saldo en la caja de la terminal. Inicia en `"0"`.            |',
    ' * | `banco`          | Nodo contenedor. Dato = saldo bancarizado. Enlaces `nombre` y `cuenta`.                     |',
    ' * | `ventas`         | Nodo contenedor. Árbol `hmi_<term>`/`hd_<term>`/`p_<term>` (parametrizado). Solo ventas propias. |',
    ' * | `venta_actual`   | Nodo venta actual (temporal, mientras la terminal selecciona asientos).                     |',
    ' *',
    ' * #### Espejo viejo (compatibilidad)',
    ' *',
    ' * Los siguientes enlaces siguen existiendo en el nodo usuario (en la',
    ' * raíz, no dentro de `publico`/`privado`) como **alias a los mismos',
    ' * nodos físicos** que ahora cuelgan de `publico` o `privado`. Se',
    ' * mantienen por retrocompatibilidad con el código que navega desde',
    ' * `us_<nombre>` sin saber de los contenedores. Se eliminan en una',
    ' * fase posterior (B3 completa) cuando la batería de pruebas esté',
    ' * consolidada.',
    ' *',
    ' * **En el nodo usuario (raíz):**',
    ' *',
    ' * - `nivel`, `nombre_real`, `email` → mismo nodo físico que `publico/<campo>`.',
    ' * - `efectivo`, `banco` → mismo nodo físico que `privado/<campo>` (para dueño y terminal).',
    ' * - `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`,',
    ' *   `rendiciones`, `liquidaciones`, `cancelaciones` → mismo nodo',
    ' *   físico que `privado/<campo>` (solo para dueño).',
    ' * - `venta_actual` (para terminal) → mismo nodo físico que `privado/venta_actual`.',
    ' *',
    ' * **Observaciones:**',
    ' *',
    ' * - Los enlaces `dueno` y `venta_actual` solo existen en nodos de nivel `terminal`.',
    ' * - Los enlaces `efectivo` y `banco` existen en nodos de nivel `terminal` y en nodos de nivel `dueno`.',
    ' * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones` y `cancelaciones` solo existen en nodos de nivel `dueno`.',
    ' * - `nombre_real`, `email` pueden no existir si no se proporcionaron.',
    ' * - El monto en `efectivo` y en `banco` es automático (inicial `"0"`) y no se solicita al crear el usuario.',
    ' * - El dato del nodo usuario es el nombre de usuario, lo que facilita la obtención del dueño: `$nodo_dueno->dato()` devuelve el nombre del dueño.',
    ' *',
    ' * #### Grafo de credenciales (separado)',
    ' *',
    ' * Las credenciales NO cuelgan del nodo usuario del grafo de la app:',
    ' * viven en un grafo separado (`Conf::NOMBRE_APP_CREDENCIALES`), con su',
    ' * propio nodo `us_<nombre>`. El punto de unión entre ambos grafos es',
    ' * el nombre de usuario (clave del enlace en la raíz `usuarios`).',
    ' *',
    ' * | Enlace               | Nodo destino y dato esperado                                                          |',
    ' * |----------------------|---------------------------------------------------------------------------------------|',
    ' * | `codigo_hash`        | Nodo con dato string: hash bcrypt del código de acceso (opcional).                    |',
    ' * | `contrasena`         | Nodo con dato string: hash de contraseña (opcional).                                  |',
    ' * | `intentos_fallidos`  | Nodo con dato string numérico. Se inicializa en `"0"`. Se resetea con login exitoso.  |',
    ' * | `bloqueado_hasta`    | Nodo con dato string: timestamp Unix hasta el cual el usuario está bloqueado. Solo existe si hubo bloqueo. Al expirar, se elimina. |',
    ' * | `ultimo_acceso`      | Nodo con dato string `"DD/MM/YYYY HH:MM"`.                                            |',
    ' * | `ip_ultimo_acceso`   | Nodo con dato string.                                                                 |',
    ' *',
];

$nodo_compartido_nuevo = [
    ' * ### Nodo Compartido `compartido_con_<terminal>` (en el dueño)',
    ' *',
    ' * Fase B2.1 (v76q) + B2.3 (v76v-v77f) + B2.4 (v77q). Es un nodo',
    ' * contenedor (dato vacío) que cuelga del nodo usuario del dueño.',
    ' * Su nombre sigue la convención `compartido_con_<nombre_terminal>`',
    ' * (sin prefijo `us_`). El nodo lleva el marcador `_es_compartido`',
    ' * (→ `"1"`) para que las funciones detecten que están navegando un',
    ' * compartido y usen nombres parametrizados de árbol.',
    ' *',
    ' * **Estructura:**',
    ' *',
    ' * | Enlace            | Nodo destino                                                                                |',
    ' * |-------------------|---------------------------------------------------------------------------------------------|',
    ' * | `_es_compartido`  | Nodo con dato `"1"`. Marcador de contexto compartido.                                        |',
    ' * | `viajes`          | Nodo contenedor. Referencias filtradas a los viajes autorizados a esta terminal (mismos nodos viaje físicos que en `us_dueno/privado/viajes`). |',
    ' * | `empresas`        | Nodo contenedor. Referencias a las empresas usadas por esos viajes.                          |',
    ' * | `terminales`      | Nodo contenedor. Solo contiene el propio terminal (por nombre).                              |',
    ' * | `ventas`          | Nodo contenedor. Árbol `hmi_<term>`/`hd_<term>`/`p_<term>` (parametrizado). Solo las ventas hechas por esta terminal. |',
    ' * | `cancelaciones`   | Nodo contenedor. Árbol parametrizado. Solo las cancelaciones de esta terminal.               |',
    ' * | `pasajeros`       | Nodo contenedor. **Alias** al `pasajeros` del dueño (mismo nodo físico). Los pasajeros son compartidos. |',
    ' * | `pendientes_ventas` | Nodo contenedor (opcional). Buzón. Ver "Buzones" abajo.                                   |',
    ' * | `pendientes_cancelaciones` | Nodo contenedor (opcional). Buzón.                                                 |',
    ' *',
    ' * **Nota (no tiene enlace al nodo real del dueño).** Si lo tuviera,',
    ' * el BFS del terminal llegaría al dueño y desde ahí a todo su',
    ' * subgrafo privado. El aislamiento depende de esto: el compartido es',
    ' * el único punto de entrada del terminal al subgrafo del dueño.',
    ' *',
    ' * **Nota (árboles paralelos, Fase B2.3.4, v77e).** Las ventas de un',
    ' * terminal viven en **dos árboles paralelos** (mismo nodo venta',
    ' * físico). El árbol del dueño usa `hmi`/`hd`/`p`. El árbol del',
    ' * compartido usa `hmi_<terminal>`/`hd_<terminal>`/`p_<terminal>`. Los',
    ' * contenedores raíz son **distintos**: `us_dueno/privado/ventas` y',
    ' * `us_dueno/compartido_con_<terminal>/ventas` son nodos diferentes,',
    ' * con su propio `hmi`/`hmi_<term>` apuntando a la cadena de su',
    ' * contexto. Si compartieran la raíz, el BFS del terminal alcanzaría',
    ' * todas las ventas del dueño.',
    ' *',
    ' * **Nota (buzones, v79a).** Cuando el terminal crea una venta (o',
    ' * cancelación) con carga parcial, el subgrafo en memoria no incluye',
    ' * `us_<dueno>`, así que no puede insertar en el árbol del dueño. El',
    ' * terminal inserta en su árbol (compartido) y deja un aviso en un',
    ' * **buzón** del compartido (`pendientes_ventas` / `pendientes_cancelaciones`).',
    ' * El buzón es un contenedor plano: hijos por id apuntando al mismo',
    ' * nodo físico (no es copia). El dueño, en su próxima carga, drena el',
    ' * buzón (`_procesar_pendientes_del_dueno`) e inserta cada nodo en su',
    ' * árbol real. El bitmask de los nodos nuevos lo propaga el framework',
    ' * al guardar parcial (v1.5i.8c), no la app.',
    ' *',
    ' * **Nota (mantenimiento, Fase B2.4, v77q).** Las operaciones que',
    ' * afectan al compartido (autorizar/desautorizar terminal, agregar/',
    ' * quitar micro, eliminar viaje, eliminar empresa) sincronizan o',
    ' * desincronizan el compartido. Los helpers viven en',
    ' * `Aplicacion/Compartidos.php`. El bug §1.5.15 (v79j) fue olvidarse',
    ' * de esa desincronización en `limpiar_viajes_de_prueba`. Regla: toda',
    ' * función que destruya un viaje llama a',
    ' * `_desincronizar_viaje_de_compartidos` antes.',
    ' *',
];

$cambios = [
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Título y nota introductoria',
        'buscar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.77q)',
            ' *',
            ' * ### Nodos raíz especiales',
        ],
        'reemplazar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.79o)',
            ' *',
            ' * ### Modelo de dos capas: viejo + nuevo coexisten',
            ' *',
            ' * La estructura de nodos evolucionó de un modelo plano (datos',
            ' * directos sobre el nodo usuario) a uno con contenedores por',
            ' * nivel de exposición (Fases B1 a B4 del plan topológico).',
            ' * **Los dos modelos coexisten**: los enlaces viejos siguen',
            ' * existiendo como alias a los mismos nodos físicos. La',
            ' * eliminación del modelo viejo queda para una fase posterior,',
            ' * cuando la batería de pruebas esté consolidada. Ver',
            ' * "Modelo topológico" al final para el detalle completo.',
            ' *',
            ' * ### Nodos raíz especiales',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Nodos raíz: agregar aplicacion',
        'buscar' => [
            ' * - `"sesiones"` (nodo especial)',
            ' *   Contiene las sesiones activas. Cada enlace saliente tiene como nombre el',
            ' *   **token de sesión** (string) y apunta al nodo de sesión.',
            ' *',
            ' * Ya no existe un nodo especial `"pasajeros"`. Los pasajeros ahora se almacenan',
            ' * en un contenedor `pasajeros` que cuelga del nodo usuario dueño (ver Nodo Usuario).',
        ],
        'reemplazar' => [
            ' * - `"sesiones"` (nodo especial)',
            ' *   Contiene las sesiones activas. Cada enlace saliente tiene como nombre el',
            ' *   **token de sesión** (string) y apunta al nodo de sesión.',
            ' *',
            ' * - `"aplicacion"` (nodo especial, v76n)',
            ' *   Metadata de la aplicación. Contiene el contenedor `migraciones`.',
            ' *   Cada migración aplicada deja un enlace testigo autoreferente',
            ' *   (`migraciones/<id>` → `migraciones`), idempotente y auto-detectable',
            ' *   desde la pestaña Grafo.',
            ' *',
            ' * - `"us_<nombre>"` (nodo especial por usuario, Fase A, v76k)',
            ' *   Cada usuario es un nodo con ID especial `us_<nombre>`. Es la raíz del',
            ' *   subgrafo del usuario. Los enlaces `dueno` (terminal), `soporte` (dueño)',
            ' *   y `duenos` (soporte) van en la raíz del usuario, no en un contenedor.',
            ' *   Ver Nodo Usuario.',
            ' *',
            ' * Ya no existe un nodo especial `"pasajeros"`. Los pasajeros ahora se almacenan',
            ' * en un contenedor `pasajeros` que cuelga del nodo usuario dueño (ver Nodo Usuario).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Nodo Usuario: reescritura completa',
        'buscar' => [
            ' * ### Nodo Usuario (dato del nodo: nombre de usuario)',
            ' *',
            ' * Cada usuario es un nodo cuyo dato es el **nombre de usuario** (string).  ',
            ' * Toda la información adicional se almacena en enlaces salientes.',
        ],
        'reemplazar' => array_merge(
            $nodo_usuario_nuevo,
            [
                ' * ',
                ' * #### Sección eliminada del docblock viejo',
                ' * ',
                ' * A continuación quedan las líneas viejas del docblock original',
                ' * (mantenidas por compatibilidad de numeración y como referencia',
                ' * histórica). NO se actualizan. La versión vigente es la de arriba.',
                ' * ',
                ' * > Cada usuario es un nodo cuyo dato es el **nombre de usuario** (string).  ',
                ' * > Toda la información adicional se almacena en enlaces salientes.',
            ]
        ),
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Insertar Nodo Compartido antes de Pasajero',
        'buscar' => [
            ' * ### Nodo Pasajero (dato del nodo: DNI)',
        ],
        'reemplazar' => array_merge(
            $nodo_compartido_nuevo,
            [
                ' * ### Nodo Pasajero (dato del nodo: DNI)',
            ]
        ),
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Modelo topológico: cerrar bug persistente',
        'buscar' => [
            ' * **BUG PERSISTENTE (no resuelto al cierre de v78s):**',
            ' * ',
            ' * Después de que un terminal hace una operación de guardado',
            ' * (por ejemplo, la prueba "autocompletado" del plugin), el viaje',
            ' * viejo del dueño desaparece de su lista. El terminal lo sigue',
            ' * viendo (navega por su compartido). Los fixes v78r (regla de',
            ' * borrado de aristas con doble extremo) y v78s (preservar bits',
            ' * de máscara al guardar parcial) fueron intentos que NO',
            ' * alcanzaron. El bug completo, el diagnóstico y el próximo paso',
            ' * de diagnóstico (instrumentar `_guardar_con_filtro` con logs)',
            ' * están en `prompts/plan_actual.md` §1.5.',
        ],
        'reemplazar' => [
            ' * **Bug §1.5 (viaje viejo que desaparecía): RESUELTO en',
            ' * framework 1.5i.8a (v1.5piloto.78v).** Causa raíz: los IDs',
            ' * numéricos del framework son efímeros; el guardado parcial',
            ' * no puede mapear "ID del framework" ↔ "fila de la DB" entre',
            ' * requests. Solución: `id_fila` (BIGINT AUTO_INCREMENT, PK)',
            ' * en `nodo_contexto` como identidad estable, `id_especial`',
            ' * para el ID especial del framework (NULL para numéricos).',
            ' * `adyacente_contexto` referencia sus extremos por',
            ' * `idnodo_fila`/`idadyacente_fila`. Mapa estático',
            ' * `$_mapa_id_a_fila` relaciona ambos mundos dentro de una',
            ' * request. El DELETE parcial borra solo aristas cuyos DOS',
            ' * extremos están entre las filas cargadas. Ver',
            ' * `plan_actual.md` §1.5 para el detalle completo.',
            ' * ',
            ' * **Bug §1.5.15 (botón limpiar viajes rompía compartidos):',
            ' * RESUELTO en v79j.** El botón "Limpiar viajes de prueba"',
            ' * destruía los viajes sin llamar a',
            ' * `_desincronizar_viaje_de_compartidos`. Los compartidos',
            ' * quedaban con aristas colgadas y el BFS del terminal se',
            ' * rompía en la próxima carga. Fix: desincronizar antes de',
            ' * destruir; helper `reparar_compartidos_viajes_dueno` desde',
            ' * `?reparar_topologia=1` para recuperar. Ver `plan_actual.md`',
            ' * §1.5.15.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'Bump @version',
        'buscar' => [
            ' * @version   1.5piloto.78m',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79p',
            ' */',
        ],
    ],
];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador — aplicacion_POST.php docblock (v2) ===\n\n";

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

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar.\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";