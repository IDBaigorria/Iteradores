<?php
/**
 * Aplicador — Documentación v83m: cierre del bug §4.7.
 *
 * Solo toca prompts. No toca código PHP ni JS.
 * No bumpea versiones (los prompts no tienen @version).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// plan_actual.md
// ============================================================

// 1. Header
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header con bug §4.7 cerrado',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83h + framework 1.5i.7v',
        '(Fase 4 del bug §4.7 implementada. `_guardar_con_filtro`',
        'hace fixpoint de máscaras sobre TODAS las aristas, inserta',
        'nodos con máscara 0 (preservando id_fila) y no skipea',
        'aristas por máscara del destino. Cierra el leak lento del',
        'guardado parcial. El piloto queda en 83h; solo el framework',
        'cambia de versión).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83l (bug §4.7 cerrado',
        'con evidencia: `id_fila` del dueño estable entre requests,',
        'cero `mf=0 modo=total` en el log durante el ciclo completo',
        'venta/cancelación/cupón, auditoría topológica limpia después',
        'de la limpieza). Causa raíz: guardado total en cada request',
        'autenticado por `validar_token_sesion` y otros 9 llamadores',
        'de `en_grafo_credenciales`, más 2 bloques de bootstrap en',
        '`index.php`. Fix: 10 funciones de solo lectura a',
        '`en_grafo_credenciales_solo_lectura`, condicionar el bloque',
        'del `aplicacion`, condicionar el bootstrap del admin, y',
        'agregar un flag de "hubo cambios" al `finally` de',
        '`en_grafo_credenciales`. Ver §4.7).',
    ],
],

// 2. Versiones
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: bump de versiones a 83l',
    'buscar' => [
        '- **Piloto:** `1.5piloto.82d`.',
        '- **Framework PHP:** `1.5i.8f` (SQL64, con singleton de',
        '  conexión, `mysqli_report(OFF)`, `SELECT 1` en lugar de',
        '  `ping()` y `IN (...)` en lugar de TEMPORARY TABLE). El',
        '  archivo `Controlador/Controlador.php` está en `1.5i.7s`',
        '  por los comandos del visualizador.',
    ],
    'reemplazar' => [
        '- **Piloto:** `1.5piloto.83l`.',
        '- **Framework PHP:** `1.5i.7w` (SQL64, con singleton de',
        '  conexión, `mysqli_report(OFF)`, `SELECT 1` en lugar de',
        '  `ping()`, `IN (...)` en lugar de TEMPORARY TABLE, y',
        '  log de persistencia a archivo del proyecto vía',
        '  `Configuracion/LogPersistencia.php`). El',
        '  archivo `Controlador/Controlador.php` está en `1.5i.7s`',
        '  por los comandos del visualizador.',
    ],
],

// 3. Sin bugs abiertos
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: sin bugs abiertos',
    'buscar' => [
        '**Sin bugs abiertos de prioridad alta.** La deuda técnica',
        'del arco está consolidada en §4.7.',
    ],
    'reemplazar' => [
        '**Sin bugs abiertos de prioridad alta.** El bug §4.7',
        '(guardado total en cada GET) está cerrado con evidencia.',
        'La deuda técnica del arco está consolidada en §4.8.',
    ],
],

// 4. §2 arcos cerrados: agregar bullet del bug
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: agregar arco del bug §4.7',
    'buscar' => [
        '## 2. ARCOS CERRADOS RECIENTES',
        '',
        'Resumen de 1-2 líneas por arco. El detalle completo vive en',
        'los prompts correspondientes o en los commits.',
    ],
    'reemplazar' => [
        '## 2. ARCOS CERRADOS RECIENTES',
        '',
        'Resumen de 1-2 líneas por arco. El detalle completo vive en',
        'los prompts correspondientes o en los commits.',
        '',
        '- **Bug §4.7 cerrado (v83j-v83l).** El bug no era el leak',
        '  lento ni la migración de pasajeros. Era guardado total en',
        '  cada request autenticado. `validar_token_sesion` (y otras',
        '  9 funciones de solo lectura) llamaban `en_grafo_credenciales`',
        '  con el default, que disparaba `guardar_ambos(app)` y',
        '  `guardar_ambos(credenciales)` en cada GET. Cada guardado',
        '  total hacía DELETE ALL + INSERT ALL con `id_fila` nuevo.',
        '  El `id_fila` del dueño cambiaba en cada request. Las',
        '  aristas del próximo BFS apuntaban a filas inexistentes.',
        '  El viaje "desaparecía" y el header mostraba "terminal".',
        '  Fix: 10 funciones a `solo_lectura`, condicionar el',
        '  `aplicacion`, condicionar el bootstrap del admin, y flag',
        '  de "hubo cambios" en el `finally` de',
        '  `en_grafo_credenciales`. Ver §4.7 para el diagnóstico',
        '  completo.',
    ],
],

// 5. §4.7 título + diagnóstico real
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.7 cerrado con diagnóstico real',
    'buscar' => [
        '### 4.7. Bug contenido: corrupción del grafo por guardado parcial',
        '',
        '**Estado:** contenido y cerrado como bug activo.',
        '',
        '**Resultado de la sesión de diagnóstico (v1.5piloto.83a-f).**',
    ],
    'reemplazar' => [
        '### 4.7. Bug §4.7: cerrado (era guardado total en cada GET)',
        '',
        '**Estado:** cerrado en v1.5piloto.83l. Validado con el ciclo',
        'completo: `id_fila` del dueño estable entre requests, cero',
        '`mf=0 modo=total` en el log, auditoría topológica limpia',
        'después de la limpieza.',
        '',
        '**Diagnóstico real (sesión v83i-v83l).** El bug NO era el',
        'leak lento (aunque Fase 4 fue necesaria para hacerlo',
        'visible). NO era la migración de pasajeros. NO era la',
        'concurrencia per se. Era que **cada request autenticado',
        'hacía dos guardados TOTALES**:',
        '',
        '1. `validar_token_sesion` (Autenticacion.php:134) se llama',
        '   en cada request. Llamaba a `en_grafo_credenciales` con el',
        '   default, que disparaba `guardar_ambos($nombre_app)`',
        '   **antes** del callback y `guardar_ambos($credenciales)`',
        '   **en el finally**.',
        '2. Otros 9 llamadores de solo lectura (`listar_sesiones`,',
        '   `listar_usuarios`, `listar_terminales_de_dueno`,',
        '   `obtener_perfil_usuario`, `_puede_cerrar_sesion`, etc.)',
        '   hacían lo mismo.',
        '3. El bloque del `aplicacion` (index.php:253-275) guardaba',
        '   en cada GET si faltaba `migraciones`. Como el guardado',
        '   total reinsertaba `migraciones` con `id_fila` nuevo, en',
        '   el próximo GET volvía a faltar → loop infinito.',
        '4. El bloque del admin en credenciales (index.php:639-655)',
        '   guardaba aunque el admin ya existiera.',
        '',
        'Cada `DELETE ALL + INSERT ALL` reinsertaba el grafo entero',
        'con `id_fila` auto-increment. El `id_fila` del dueño',
        'cambiaba en cada request. Las aristas del BFS del próximo',
        'request apuntaban a filas que ya no existían. El viaje',
        'viejo "desaparecía" y el header mostraba "terminal" en vez',
        'de "dueño".',
        '',
        '**Fix (v83j-v83l).**',
        '- v83j: `en_grafo_credenciales` acepta `bool',
        '  $guardar_app_antes = true`. El bootstrap del admin pasa',
        '  `false`.',
        '- v83k: `en_grafo_credenciales` solo guarda credenciales si',
        '  el callback no devolvió exactamente `false`. El callback',
        '  del bootstrap del admin devuelve `$creado`.',
        '- v83k: el bloque del `aplicacion` solo guarda si creó algo',
        '  en este request (flag `$hubo_cambio_app_meta`).',
        '- v83l: las 10 funciones de solo lectura pasan a',
        '  `en_grafo_credenciales_solo_lectura`. Corta el 90% de los',
        '  guardados totales. Es el fix de raíz.',
        '',
        '**Cobertura.** Las funciones que sí modifican (autenticar,',
        'crear/cerrar sesión, agregar/actualizar/eliminar usuario,',
        'eliminar terminal, eliminar sesiones) siguen con',
        '`en_grafo_credenciales`. Solo se cambiaron las de lectura.',
        '',
        '**Estado post-fix (validado por el usuario).**',
        '- `id_fila` del dueño: estable entre recargas, ventas,',
        '  cancelaciones, pagos de cupón.',
        '- Log: cero `mf=0 modo=total`, cero',
        '  `nombre=credenciales`. Solo guardados parciales de las',
        '  operaciones reales.',
        '- Auditoría topológica post-limpieza: 0 críticos, 0',
        '  errores, 0 warnings.',
        '',
        '**Histórico (sesión v83a-h, conservado para contexto).**',
        'Lo que sigue documenta la investigación que precedió al',
        'diagnóstico real. Fue necesaria (Fase 1-4 del framework,',
        'cobertura de `guardar_ambos` en 25 flujos, blindaje de',
        'persistencia robusta) pero el trigger real estaba en otro',
        'lado.',
        '',
        '**Resultado de la sesión de diagnóstico (v1.5piloto.83a-f).**',
    ],
],

// 6. §4.7 estado del bug al final del bloque
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.7 estado del bug cerrado',
    'buscar' => [
        '**Estado del bug:** contenido. Los 4 bugs concretos del',
        'flujo están cerrados. El leak lento del blindaje fue',
        'cerrado de raíz en Fase 4 (framework 1.5i.7v): fixpoint',
        'de máscaras sobre TODAS las aristas + inserción de nodos',
        'con máscara 0 preservando id_fila. El blindaje de',
        '1.5i.7t/7u sigue activo (rollback si pierde bits, nodos',
        'o aristas).',
    ],
    'reemplazar' => [
        '**Estado del bug:** cerrado en v1.5piloto.83l. El leak',
        'lento del blindaje fue cerrado de raíz en Fase 4',
        '(framework 1.5i.7v). El trigger real (guardado total en',
        'cada GET) fue cerrado en v83j-v83l. El blindaje de',
        '1.5i.7t/7u sigue activo (rollback si pierde bits, nodos',
        'o aristas).',
    ],
],

// 7. §4.8 GET_LOCK
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.8 agregar GET_LOCK',
    'buscar' => [
        '### 4.8. Deuda técnica del arco (baja)',
        '',
        'Pendientes generados por la línea v81p-v82d. Ninguno es',
        'bloqueante.',
        '',
        '- **Endpoints temporales de diagnóstico en index.php.**',
    ],
    'reemplazar' => [
        '### 4.8. Deuda técnica del arco (baja)',
        '',
        'Pendientes generados por la línea v81p-v82d. Ninguno es',
        'bloqueante.',
        '',
        '- **`GET_LOCK(\'grafo_<nombre>\', 5)` en `_guardar_con_filtro`.**',
        '  Para blindar la concurrencia real. Ahora que el guardado',
        '  total no se dispara en cada GET, la concurrencia es',
        '  mucho menos probable (solo operaciones reales se',
        '  pueden solapar). Pero dos terminales que venden al',
        '  mismo tiempo todavía pueden pisarse. Con GET_LOCK, el',
        '  segundo espera o falla, pero no duplica. Es deuda',
        '  técnica, no bug activo. Tanda del framework.',
        '',
        '- **Endpoints temporales de diagnóstico en index.php.**',
    ],
],

// 8. §4.5 desbloquear migración
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.5 desbloquear migración',
    'buscar' => [
        '**Arco en curso: migración de pasajeros entre micros.**',
        '**ESTADO: EN PAUSA** hasta que el bug del guardado',
        'parcial (§4.7) esté diagnosticado y blindado.',
    ],
    'reemplazar' => [
        '**Arco en curso: migración de pasajeros entre micros.**',
        '**ESTADO: DESBLOQUEADA.** El bug §4.7 está cerrado',
        '(v1.5piloto.83l). El `id_fila` es estable entre',
        'requests, el grafo no se corrompe. Se puede retomar',
        'la Tanda 3.',
    ],
],

// 9. §4.5 final: la migración puede volver
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §4.5 migración desbloqueada',
    'buscar' => [
        '**La migración NO vuelve a producción** hasta que el',
        'guardado parcial esté blindado (Fases 4 y 6 de §4.7).',
    ],
    'reemplazar' => [
        '**La migración YA PUEDE VOLVER A PRODUCCIÓN.** El bug',
        '§4.7 está cerrado (v1.5piloto.83l). Antes de pasarla,',
        'correr la Tanda 3 en local y validar el ciclo completo',
        'en un viaje de prueba.',
    ],
],

// 10. Estado al cierre
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado al cierre',
    'buscar' => [
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
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83l',
        '(framework 1.5i.7w). **Bug §4.7 cerrado.** No era el',
        'leak lento (Fase 4 fue necesaria, pero no suficiente).',
        'Era guardado total en cada request autenticado por',
        '`validar_token_sesion` y otros 9 llamadores de',
        '`en_grafo_credenciales`, más 2 bloques de bootstrap en',
        '`index.php`. Fix: 10 funciones a `solo_lectura`,',
        'condicionar `aplicacion`, condicionar bootstrap del',
        'admin, flag de "hubo cambios" en `finally`. Validado',
        'con ciclo completo (venta/cancelación/cupón): `id_fila`',
        'estable, cero totales en el log, auditoría limpia.',
        '**La migración de pasajeros se puede desbloquear**',
        '(Tanda 3 pendiente). El log de persistencia está activo,',
        'escribiendo a `logs/persistencia/YYYY-MM-DD.log`,',
        'legible por HTTP con `?ver_log_persistencia=1`.',
    ],
],

// ============================================================
// prompt_piloto.md
// ============================================================

// 11. §5.2 validar_token_sesion
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: §5.2 validar_token_sesion',
    'buscar' => [
        '- `validar_token_sesion($token)`.',
    ],
    'reemplazar' => [
        '- `validar_token_sesion($token)`: solo lectura. Usa',
        '  `en_grafo_credenciales_solo_lectura` desde v83l (antes',
        '  usaba `en_grafo_credenciales`, lo que disparaba dos',
        '  guardados totales por request autenticado, causando el',
        '  bug §4.7).',
    ],
],

// 12. §5.6 en_grafo_credenciales con flag
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: §5.6 en_grafo_credenciales',
    'buscar' => [
        '### 5.6 `Aplicacion/GrafoCredenciales.php`',
        '',
        '- `en_grafo_credenciales(callable $fn)`: guarda app, carga/crea',
        '  credenciales, ejecuta callback, guarda credenciales, recarga app.',
    ],
    'reemplazar' => [
        '### 5.6 `Aplicacion/GrafoCredenciales.php`',
        '',
        '- `en_grafo_credenciales(callable $fn, bool $guardar_app_antes = true)`:',
        '  guarda app (si `$guardar_app_antes`), carga/crea',
        '  credenciales, ejecuta callback, guarda credenciales (solo',
        '  si el callback no devolvió exactamente `false`), recarga',
        '  app. El flag `$guardar_app_antes` permite a los',
        '  llamadores que no modifican la app evitar el guardado',
        '  total (bug §4.7). Desde v83j. El "solo si no devolvió',
        '  `false`" desde v83k.',
        '- `en_grafo_credenciales_solo_lectura(callable $fn)`: lee',
        '  credenciales sin guardar. Usar en funciones de solo',
        '  lectura (validar sesión, listar, buscar). Desde v76f.',
        '  En v83l se cambiaron `validar_token_sesion` y otras 9',
        '  funciones a este helper.',
    ],
],

// 13. §7 historial
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: §7 historial v83j-l',
    'buscar' => [
        '## 7. HISTORIAL DE VERSIONES (resumen)',
        '',
        '- **v81t-v82d** (línea de cancelaciones en compartidos +',
    ],
    'reemplazar' => [
        '## 7. HISTORIAL DE VERSIONES (resumen)',
        '',
        '- **v83j-v83l** (cierre del bug §4.7): ver',
        '  `plan_actual.md` §4.7 para el diagnóstico completo.',
        '  Resumen:',
        '  - 83j: `en_grafo_credenciales` acepta',
        '    `bool $guardar_app_antes = true`. El bootstrap del',
        '    admin pasa `false`. El bloque del `aplicacion`',
        '    guarda solo si creó algo (flag',
        '    `$hubo_cambio_app_meta`).',
        '  - 83k: `en_grafo_credenciales` solo guarda',
        '    credenciales si el callback no devolvió `false`.',
        '    El callback del bootstrap del admin devuelve',
        '    `$creado`.',
        '  - 83l: las 10 funciones de solo lectura pasan a',
        '    `en_grafo_credenciales_solo_lectura`:',
        '    `validar_token_sesion`, `listar_sesiones`,',
        '    `listar_sesiones_de_usuarios`, `listar_usuarios`,',
        '    `listar_duenos_de_soporte`, `_puede_cerrar_sesion`,',
        '    `agregar_usuario` (bloque codigo_duplicado),',
        '    `actualizar_usuario` (bloque codigo_duplicado),',
        '    `listar_terminales_de_dueno`, `obtener_perfil_usuario`.',
        '  El bug NO era el leak lento ni la migración. Era',
        '  guardado total en cada request autenticado.',
        '  Validado con ciclo completo.',
        '- **v81t-v82d** (línea de cancelaciones en compartidos +',
    ],
],

// 14. §13 cierre estado
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: §13 estado al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.78s',
        '(framework 1.5i.7r). La línea de carga parcial quedó',
        'aplicada en los módulos principales (Viajes, Venta,',
        'Pasajeros, Impresión). SQL64 activo, topología',
        'admin/soporte, reparación topológica disponible.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83l',
        '(framework 1.5i.7w). La línea de carga parcial está',
        'aplicada en los módulos principales (Viajes, Venta,',
        'Pasajeros, Impresión). SQL64 activo, topología',
        'admin/soporte, reparación topológica disponible.',
        'El log de persistencia escribe a',
        '`logs/persistencia/YYYY-MM-DD.log` (legible por HTTP',
        'con `?ver_log_persistencia=1`, admin/soporte).',
    ],
],

// 15. §13 bug persistente -> cerrado
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: §13 bug §4.7 cerrado',
    'buscar' => [
        '**BUG PERSISTENTE.** El viaje viejo del dueño desaparece',
        'cuando un terminal hace una operación de guardado',
        '(reproducible con el plugin: autocompletado → alta de',
        'viaje). Los fixes v78r (regla de borrado de aristas) y',
        'v78s (preservar bits de máscara) no alcanzaron. Detalle',
        'completo, hipótesis y próximo paso de diagnóstico en',
        '`prompts/plan_actual.md` §1.5.',
    ],
    'reemplazar' => [
        '**Bug §4.7 cerrado** (v1.5piloto.83l). No era el leak',
        'lento ni la migración. Era guardado total en cada',
        'request autenticado. Detalle completo en',
        '`prompts/plan_actual.md` §4.7. El `id_fila` del dueño es',
        'estable entre requests. La migración de pasajeros se',
        'puede desbloquear (Tanda 3 pendiente).',
    ],
],

// ============================================================
// prompt_framework_iteradores.md
// ============================================================

// 16. §11.4 nota de cierre
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'prompt_framework: §11.4 nota de cierre',
    'buscar' => [
        '**Momento:** antes de la fase 5 (256 contextos) o antes',
        'de cualquier otro método de indexación. En la fase 1-2',
        'actual el campo en el nodo funciona; no bloquea nada.',
    ],
    'reemplazar' => [
        '**Momento:** antes de la fase 5 (256 contextos) o antes',
        'de cualquier otro método de indexación. En la fase 1-2',
        'actual el campo en el nodo funciona; no bloquea nada.',
        '',
        '**Nota de cierre (v83l).** El bug §4.7 del piloto NO',
        'era del guardado parcial ni del leak lento. Era',
        'guardado TOTAL en cada GET. Ver',
        '`plan_actual.md` §4.7 del piloto. La Fase 4 de §11.4',
        '(fixpoint de máscaras, inserción de nodos con máscara 0)',
        'fue necesaria pero no suficiente. El diagnóstico real',
        'es una lección para el framework: cuando un bug',
        '"desaparece" post-restauración y vuelve, buscar en el',
        'código que corre en CADA request, no en las operaciones',
        'puntuales.',
    ],
],

// 17. §13 lección nueva
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'prompt_framework: §13 lección 30',
    'buscar' => [
        '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
        'JS en la misma tanda, con dos scripts y dos commits.',
    ],
    'reemplazar' => [
        '**30. Un bug que "desaparece" post-restauración y vuelve',
        'con una secuencia específica probablemente NO está en',
        'las operaciones que disparan el síntoma.** En el piloto',
        '(v83l), el bug §4.7 no era del guardado parcial ni del',
        'leak lento: era que `validar_token_sesion` llamaba',
        '`en_grafo_credenciales` (que guarda app + credenciales)',
        'en cada request. Cada guardado total hacía DELETE ALL',
        '+ INSERT ALL con `id_fila` nuevo, y el `id_fila` del',
        'dueño cambiaba en cada GET. El síntoma aparecía con',
        'una secuencia específica (micro de 2 pisos → migración',
        '→ cupón) porque esa secuencia generaba más requests.',
        'Lección: cuando un bug se resiste a dos intentos de',
        'fix, instrumentar el código que corre en cada request,',
        'no el de la operación puntual.',
        '',
        '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
        'JS en la misma tanda, con dos scripts y dos commits.',
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
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
}

$bumps_atversion = 0;
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