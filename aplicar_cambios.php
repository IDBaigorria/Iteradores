<?php
/**
 * Aplicador — Cierre documental (paso 2/3): plan_actual.md.
 *
 * Actualiza:
 *   1. §1.1 (última actualización + resumen del cierre).
 *   2. §1.2 (agrega v79j-v79o y el plugin).
 *   3. §1.5.15 nueva (bug del botón) + §1.5.16 nueva (runner v2).
 *   4. §1.6 (pendientes actualizados).
 *   5. §1.7 (plan de fases actualizado).
 *   6. §1.9 (lecciones nuevas).
 *   7. §2.3 (próximos pasos).
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // 1. §1.1 — Última actualización y resumen
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.1 actualizada a v79o',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79f.',
            '',
            'Cierre del fix del bug §1.5 (viaje viejo) y de los bugs',
            '§1.5.7, §1.5.8, §1.5.10 (nodos compartidos entre contextos,',
            'inserción diferida con buzones). La carga parcial está',
            'activa desde v78g. La topología sigue siendo la fuente de',
            'la seguridad: el bitmask lo propaga el framework, no la app.',
            '',
            '**Bug §1.5 RESUELTO en v78v.** Causa raíz: IDs numéricos',
            'del framework efímeros. Solución: `id_fila` (BIGINT',
            'AUTO_INCREMENT) en `nodo_contexto`. Ver §1.5.6.',
            '',
            '**Bug §1.5.7 RESUELTO en v78w-v78z.** Nodos compartidos',
            'entre contextos no pueden ser destruidos por el guardado',
            'parcial de un solo contexto. Solución: soft delete',
            'coordinado con autoenlace `cancelada`. Ver §1.5.7 y §1.5.8.',
            '',
            '**Bug §1.5.10 RESUELTO en v79a.** El terminal vende/cancela',
            'y el dueño no ve los nodos nuevos (con carga parcial,',
            '`us_<dueno>` no está en memoria del terminal). Solución:',
            'inserción diferida con buzones + propagación de máscaras a',
            'nodos nuevos por el framework (v1.5i.8c). Ver §1.5.10.',
            '',
            'Próximo paso sugerido: batería completa de pruebas con el',
            'plugin, por rol. Ver §2.3.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79o.',
            '',
            'Cierre de la línea "grafo + plugin + sistema de scripts".',
            'Se resolvieron: el bug del botón "Limpiar viajes de prueba"',
            'que rompía los compartidos, la limpieza profunda de viajes',
            'con ventas de prueba, el typo del prefijo `viajedeselegraf`,',
            'el try/finally en las pruebas del plugin (34/35/36), el',
            'bug del runner de bumps (v79o), y se consolidó el sistema',
            'de scripts en un único prompt v2. La carga parcial sigue',
            'activa. La topología sigue siendo la fuente de la',
            'seguridad: el bitmask lo propaga el framework, no la app.',
            '',
            '**Bug §1.5 RESUELTO en v78v.** Causa raíz: IDs numéricos',
            'del framework efímeros. Solución: `id_fila` (BIGINT',
            'AUTO_INCREMENT) en `nodo_contexto`. Ver §1.5.6.',
            '',
            '**Bug §1.5.7 RESUELTO en v78w-v78z.** Nodos compartidos',
            'entre contextos no pueden ser destruidos por el guardado',
            'parcial de un solo contexto. Solución: soft delete',
            'coordinado con autoenlace `cancelada`. Ver §1.5.7 y §1.5.8.',
            '',
            '**Bug §1.5.10 RESUELTO en v79a.** Inserción diferida con',
            'buzones + propagación de máscaras. Ver §1.5.10.',
            '',
            '**Bug §1.5.15 RESUELTO en v79j.** El botón "Limpiar viajes',
            'de prueba" destruía los viajes sin desincronizarlos de los',
            'compartidos. Las aristas del compartido quedaban colgadas,',
            'el BFS del compartido se rompía, y el terminal dejaba de',
            'ver sus viajes. Fix: `_desincronizar_viaje_de_compartidos`',
            'antes de destruir cada viaje + helper',
            '`reparar_compartidos_viajes_dueno`. Ver §1.5.15.',
            '',
            '**Bug §1.5.16 RESUELTO en v79o.** El runner de bumps',
            'releía el archivo desde el disco y pisaba los reemplazos',
            'pendientes en memoria. Detectado aplicando P55 v6x. Fix:',
            'el bump parte del contenido en memoria. Ver §1.5.16.',
            '',
            '**Sistema de scripts v2.** Se consolidaron',
            '`prompt_sistema_scripts.md` + `prompt_runner_aplicar_cambios.md`',
            'en `prompt_sistema_scripts_v2.md`. Los viejos quedan como',
            'backup. El runner v2 tiene bumps automáticos y el fix del',
            'pisado de reemplazos.',
            '',
            '**Plugin de pruebas.** Se agregó try/finally a las pruebas',
            '34, 35 y 36 (limpieza best-effort). Se corrigió P55',
            '(subida de foto con `nombre_campo` y comparación de',
            'rutas en vez de nombres originales). El plugin está en',
            '`v1.5plugin.6z`.',
            '',
            'Próximo paso sugerido: ver §2.3.',
        ],
    ],

    // ============================================================
    // 2. §1.2 — Agregar la cadena v79j-v79o + plugin
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.2 cadena de cierres ampliada',
        'buscar' => [
            '- **v79a** (buzones, §1.5.10): inserción diferida. El',
            '  terminal vende/cancela y deja aviso en un buzón del',
            '  compartido. El dueño drena el buzón en su próxima carga.',
            '  Framework 1.5i.8c: propagación de máscaras a nodos nuevos',
            '  por enlaces entrantes.',
        ],
        'reemplazar' => [
            '- **v79a** (buzones, §1.5.10): inserción diferida. El',
            '  terminal vende/cancela y deja aviso en un buzón del',
            '  compartido. El dueño drena el buzón en su próxima carga.',
            '  Framework 1.5i.8c: propagación de máscaras a nodos nuevos',
            '  por enlaces entrantes.',
            '- **v79j** (fix botón + compartidos + botón solo admin): ver',
            '  §1.5.15. Agrega `_desincronizar_viaje_de_compartidos` en',
            '  `limpiar_viajes_de_prueba`, el helper',
            '  `reparar_compartidos_viajes_dueno`, amplía prefijos de',
            '  prueba, y deja el botón visible solo para admin en modo',
            '  pruebas (CSS + clase en body).',
            '- **v79k**: agrega prefijo `viajeregres` y reporta',
            '  conservados en el toast del botón.',
            '- **v79l**: regla de ventas de prueba. Solo emails',
            '  terminados en `@test.local` marcan la venta como de',
            '  prueba. Si el viaje tiene alguna venta con email real o',
            '  vacío, se conserva.',
            '- **v79m**: fix typo del prefijo `viajedeselgraf` →',
            '  `viajedeselegraf`. La prueba 35 usa el nombre con la `e`.',
            '- **v79n**: runner v2 con bumps automáticos. Se elimina la',
            '  clase de errores de bumps mal calculados.',
            '- **v79o**: fix del runner v2. El bump parte del contenido',
            '  en memoria, no del disco. Ver §1.5.16. También consolida',
            '  el sistema de scripts en `prompt_sistema_scripts_v2.md`.',
            '- **v6w** (plugin): `try/finally` en pruebas 34, 35, 36.',
            '- **v6x** (plugin): P55 usa `nombre_campo: "foto"`.',
            '- **v6y** (plugin): fix del bloque `finally` mal armado en',
            '  P35.',
            '- **v6z** (plugin): P55 compara rutas de foto en vez de',
            '  nombres originales.',
        ],
    ],

    // ============================================================
    // 3. §1.5.15 + §1.5.16 nuevas (después de §1.5.14)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'Agregar §1.5.15 y §1.5.16',
        'buscar' => [
            '**Lección:** el bloque `?reparar_topologia=1` debe',
            'cubrir **todas** las aristas estructurales del',
            'modelo: admin → usuarios, dueño → compartido,',
            'viajes/empresas/pasajeros del dueño. Si una',
            'arista estructural puede perderse, debe poder',
            'repararse.',
        ],
        'reemplazar' => [
            '**Lección:** el bloque `?reparar_topologia=1` debe',
            'cubrir **todas** las aristas estructurales del',
            'modelo: admin → usuarios, dueño → compartido,',
            'viajes/empresas/pasajeros del dueño. Si una',
            'arista estructural puede perderse, debe poder',
            'repararse.',
            '',
            '#### 1.5.15 Botón "Limpiar viajes de prueba" rompía los compartidos (v79j)',
            '',
            '**Síntoma:** después de usar el botón "Limpiar viajes',
            'de prueba" como admin, el viaje principal desapareció',
            'de la pestaña Viajes de los terminales. El dueño y el',
            'admin seguían viéndolo.',
            '',
            '**Causa raíz:** `limpiar_viajes_de_prueba` destruía',
            'los viajes sin llamar a `_desincronizar_viaje_de_compartidos`.',
            'Los compartidos de las terminales quedaban con aristas',
            'colgadas al nodo viaje destruido. En la próxima carga',
            'parcial del terminal, el BFS del bitmask se rompía, y',
            'el compartido quedaba sin sus viajes. Como no había',
            'ninguna copia del compartido, el terminal no veía',
            'ningún viaje.',
            '',
            '**Cómo se llegó ahí:** `eliminar_viaje` (Fase B2.4.2,',
            'v77q) había agregado `_desincronizar_viaje_de_compartidos`',
            'antes de destruir el subárbol. `limpiar_viajes_de_prueba`',
            'se escribió antes (v74z) y nunca se actualizó.',
            '',
            '**Fix:**',
            '',
            '1. Agregar `_desincronizar_viaje_de_compartidos($nodo_viaje)`',
            '   en `limpiar_viajes_de_prueba`, antes de',
            '   `_destruir_viaje_completo`.',
            '2. Nuevo helper `reparar_compartidos_viajes_dueno` en',
            '   `miscelaneas/migrar_topologia.php`. Recorre cada',
            '   dueño, cada viaje y cada terminal autorizada, y',
            '   llama a `_sincronizar_terminal_en_compartido_del_viaje`.',
            '   Se ejecuta desde `?reparar_topologia=1`. Idempotente.',
            '3. Ampliar los prefijos de prueba: `viajeventgraf`,',
            '   `viajecambiomicro`, `viajeregres`, y corregir el typo',
            '   `viajedeselgraf` → `viajedeselegraf` (v79m).',
            '4. Botón solo admin: CSS defensivo',
            '   (`estilos-botones-admin.css`) + clase `rol-admin` en',
            '   el body desde `aplicacion.js`.',
            '',
            '**Verificación:** ejecutar `?reparar_topologia=1`. Debe',
            'reportar `compartidos asegurados: N` y',
            '`viajes sincronizados: N`. Después, el terminal ve sus',
            'viajes de nuevo.',
            '',
            '**Lección:** toda función que destruya un viaje debe',
            'desincronizarlo de los compartidos primero. El patrón',
            'está en `eliminar_viaje`. Ver §5.9 del prompt de',
            'seguridad (regla de detección para estructuras nuevas).',
            '',
            '#### 1.5.16 Runner de bumps pisaba los reemplazos (v79o)',
            '',
            '**Síntoma:** al aplicar un script con `$version_nueva` y',
            'reemplazos sobre el mismo archivo, los reemplazos se',
            'perdían. El `@version` se bumpeaba, pero el resto del',
            'cambio no se escribía.',
            '',
            '**Causa raíz:** el runner aplicaba los reemplazos en',
            'memoria (`$archivos_a_escribir[$ruta] = [contenido, eol]`)',
            'y después, para el bump, releía el archivo desde el',
            'disco y pisaba la entrada en memoria con el contenido',
            'sin reemplazos + el bump.',
            '',
            '**Fix:** en las fases 2a y 2b del runner, si el archivo',
            'ya está en `$archivos_a_escribir`, el bump parte de ese',
            'contenido en memoria. Solo si no está (no tiene',
            'reemplazos pendientes) lee del disco.',
            '',
            '**Detectado en:** aplicación del script de P55 v6x. El',
            '`@version` se bumpeó a `1.5plugin.6x` pero los',
            '`nombre_campo: "foto"` no se escribieron.',
            '',
            '**Lección:** en cualquier pipeline de transformaciones',
            'sobre un mismo archivo, las fases posteriores deben',
            'operar sobre el contenido ya transformado, nunca sobre',
            'el original. Ver `prompt_sistema_scripts_v2.md` §18',
            'lección 22.',
        ],
    ],

    // ============================================================
    // 4. §1.6 — Pendientes actualizados
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.6 pendientes actualizados',
        'buscar' => [
            '**Otros pendientes:**',
            '',
            '- **Espejo JS del fix `id_fila`.** HECHO en',
            '  V1.5plugin.7a (framework JS V1.5i.8b).',
            '- **Espejo JS del fix 1.5i.8c.** HECHO en V1.5plugin.7b.',
            '- **Carga parcial activa.** `_contextos_para_operacion`',
            '  está activo desde v78g.',
            '- **Batería completa de pruebas con el plugin, por rol.**',
            '  Ver §2.3.',
            '- **B3.3c** (7 helpers que iteran sobre todos los dueños).',
            '  Postergado. Ver §1.10.',
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-',
            '  tandas cortas.',
            '- **Plugin: dividir la ventana en pestañas, una por rol.**',
            '  Idea anotada al cierre de v78v.',
            '- **Deuda de seguridad en impresión.** Las URLs de',
            '  impresión (`?imprimir=1&tipo=...`) no validan sesión',
            '  ni permisos. Cualquiera con la URL ve el HTML. Se',
            '  anota para una tanda futura, cuando se rehaga el',
            '  módulo de impresión o se agregue un chequeo general.',
        ],
        'reemplazar' => [
            '**Otros pendientes:**',
            '',
            '- **Espejo JS del fix `id_fila`.** HECHO en',
            '  V1.5plugin.7a (framework JS V1.5i.8b).',
            '- **Espejo JS del fix 1.5i.8c.** HECHO en V1.5plugin.7b.',
            '- **Carga parcial activa.** `_contextos_para_operacion`',
            '  está activo desde v78g.',
            '- **Batería completa de pruebas con el plugin, por rol.**',
            '  Pendiente. Ver §2.3.',
            '- **B3.3c** (7 helpers que iteran sobre todos los dueños).',
            '  Postergado. Ver §1.10.',
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Sub-',
            '  tandas cortas.',
            '- **Plugin: dividir la ventana en pestañas, una por rol.**',
            '  Idea anotada al cierre de v78v.',
            '- **Deuda de seguridad en impresión.** Las URLs de',
            '  impresión (`?imprimir=1&tipo=...`) no validan sesión',
            '  ni permisos. Cualquiera con la URL ve el HTML. Se',
            '  anota para una tanda futura, cuando se rehaga el',
            '  módulo de impresión o se agregue un chequeo general.',
            '',
            '**Pendientes nuevos del cierre v79j-v79o:**',
            '',
            '- **Botón "Eliminar cancelaciones de prueba".** Limpia',
            '  los nodos Cancelación que dejó la limpieza profunda de',
            '  viajes con ventas de prueba (v79l). Análogo al de',
            '  "Limpiar viajes de prueba".',
            '- **Limpieza de empresas/vehículos de prueba.** Los',
            '  prefijos `viajecambiomicro_emp_*` y',
            '  `viajecambiomicro_veh_*` que crea la prueba 36 no',
            '  tienen cleanup. Se agrega al botón de "Limpiar viajes',
            '  de prueba" o a uno propio.',
            '- **Llamar a `reiniciar_seleccion_completa` desde el',
            '  login de terminal.** Hoy el endpoint existe pero solo',
            '  lo llaman las pruebas del plugin. Si un usuario cierra',
            '  el navegador a mitad de una venta, la venta_actual',
            '  queda colgada para siempre. Se puede limpiar al',
            '  arrancar la sesión del terminal.',
            '- **Refactor del plugin: centralizar el payload.** Un',
            '  helper `_post(ctx, accion, params)` que inyecte',
            '  `nombre_solicitante` y `nombre_dueno` automáticamente.',
            '  Evita la clase de bugs de "agregar campo al POST pero',
            '  no a la firma" (v6q).',
            '- **Test de "linting" del plugin.** Verifica que ningún',
            '  `pedir_post` a acciones de `vehiculos/*`, `empresas/*`,',
            '  etc. salga sin `nombre_dueno`.',
            '- **Auditar estados colgados.** Reservas colgadas,',
            '  venta a mitad de venta, DJ a medias, edición de viaje',
            '  con paradas. Empezar por el caso "cierre de navegador',
            '  a mitad de venta".',
        ],
    ],

    // ============================================================
    // 5. §1.7 — Plan de fases
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.7 fases actualizadas',
        'buscar' => [
            '**Fase C** (opcional) — Tipos como IDs especiales.',
            '**Pendiente.**',
            '',
            '**Fase D** — Carga parcial como optimización.',
            '**Pendiente.**',
        ],
        'reemplazar' => [
            '**Fase C** (opcional) — Tipos como IDs especiales.',
            '**Pendiente.**',
            '',
            '**Fase D** — Carga parcial como optimización.',
            '**Pendiente.**',
            '',
            '**Línea de cierre de grafo + plugin + scripts (v79j-v79o).**',
            '**Completada.**',
            '  - v79j: fix del botón + compartidos + botón solo admin.',
            '  - v79k-v79m: prefijos, reportar conservados, fix typo.',
            '  - v79l: regla de ventas de prueba.',
            '  - v79n-v79o: runner v2 con bumps automáticos + fix',
            '    del pisado de reemplazos + consolidación del',
            '    prompt de scripts.',
            '  - v6w-v6z del plugin: try/finally en pruebas 34-36,',
            '    fixes de P55.',
        ],
    ],

    // ============================================================
    // 6. §1.9 — Lecciones nuevas
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.9 lecciones nuevas',
        'buscar' => [
            '**Sobre las pruebas del plugin:**',
            '',
            '11. **El orden de las pruebas importa.**',
            '12. **"Primera vez falla, segunda no"** apunta a un estado',
            '    que se pisó una vez.',
        ],
        'reemplazar' => [
            '**Sobre las pruebas del plugin:**',
            '',
            '11. **El orden de las pruebas importa.**',
            '12. **"Primera vez falla, segunda no"** apunta a un estado',
            '    que se pisó una vez.',
            '',
            '**Sobre destrucción de nodos y compartidos (v79j):**',
            '',
            '13. **Toda función que destruya un viaje debe',
            '    desincronizarlo de los compartidos primero.** El',
            '    patrón está en `eliminar_viaje` (v77q).',
            '    `limpiar_viajes_de_prueba` se escribió antes y nunca',
            '    se actualizó. Lección: cuando se agrega un patrón',
            '    nuevo (v77q), auditar todas las funciones que hacen',
            '    lo mismo (v74z lo hacía distinto).',
            '14. **`?reparar_topologia=1` debe cubrir TODAS las',
            '    aristas estructurales.** Cuando una arista se puede',
            '    perder, tiene que poder repararse.',
            '',
            '**Sobre criterios por string (v79j, v79m):**',
            '',
            '15. **Los prefijos con typo fallan silenciosamente.**',
            '    `viajedeselgraf` vs `viajedeselegraf`. Los 10 viajes',
            '    de la prueba 35 quedaban conservados sin aviso.',
            '    Lección: cuando se agrega un criterio por string,',
            '    verificar el caso positivo y el negativo.',
            '16. **El email `@test.local` es la marca de prueba.**',
            '    Solo los emails que terminan en `@test.local` marcan',
            '    una venta como de prueba. Emails reales, vacíos o',
            '    inválidos se tratan como reales.',
            '',
            '**Sobre el sistema de scripts (v79n, v79o):**',
            '',
            '17. **Los bumps automáticos eliminan una clase entera de',
            '    errores.** Antes había que recordar la versión vieja,',
            '    el espaciado, el `?v=` del HTML. Ahora se declara',
            '    `$version_nueva` y listo.',
            '18. **En un pipeline de transformaciones, cada fase debe',
            '    operar sobre el resultado de la anterior, no sobre',
            '    el original.** El runner releía del disco para el',
            '    bump y pisaba los reemplazos en memoria.',
        ],
    ],

    // ============================================================
    // 7. §2.3 — Próximos pasos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§2.3 próximos pasos actualizados',
        'buscar' => [
            '### 2.3 Próximos pasos sugeridos',
            '',
            '1. **Batería completa de pruebas con el plugin,**',
            '   organizadas por rol. Reorganizar la ventana del plugin',
            '   en pestañas (base + admin + dueño + terminal +',
            '   soporte + multi-rol). Es el próximo foco.',
            '2. **Auditar estructuras nuevas** con la regla de §5.9',
            '   del prompt de seguridad.',
            '3. **Adaptar los 4 módulos menores** (Liquidacion,',
            '   Rendicion, Empresa, Vehiculo).',
            '4. **B3.3c** cuando aparezca dolor real o cuando lleguen',
            '   los iteradores con ID especial.',
        ],
        'reemplazar' => [
            '### 2.3 Próximos pasos sugeridos',
            '',
            'Ordenados por prioridad:',
            '',
            '1. **Verificación final del plugin.** Correr toda la',
            '   suite del plugin (24 pruebas del grafo + las de',
            '   fotos, DJ, etc.) para confirmar que todo sigue verde',
            '   después de los fixes v6w-v6z.',
            '2. **Botón "Eliminar cancelaciones de prueba".** Limpia',
            '   los nodos Cancelación que dejó la limpieza profunda',
            '   de viajes con ventas de prueba (v79l). Va junto con',
            '   la limpieza de empresas/vehículos de prueba.',
            '3. **Llamar a `reiniciar_seleccion_completa` desde el',
            '   login de terminal.** Cubre el caso real "usuario',
            '   cierra el navegador a mitad de venta". Hoy el',
            '   endpoint existe pero solo lo usan las pruebas.',
            '4. **Refactor del plugin: centralizar el payload.**',
            '   Helper `_post(ctx, accion, params)`. Base para que',
            '   no vuelvan a pasar bugs como el de `nombre_dueno`',
            '   (v6q).',
            '5. **Auditar estados colgados** (reservas, DJ, edición',
            '   de viaje). Empezar por "cierre de navegador a mitad',
            '   de venta".',
            '6. **Auditar estructuras nuevas** con la regla de §5.9',
            '   del prompt de seguridad.',
            '7. **Adaptar los 4 módulos menores** (Liquidacion,',
            '   Rendicion, Empresa, Vehiculo).',
            '8. **B3.3c** cuando aparezca dolor real o cuando lleguen',
            '   los iteradores con ID especial.',
        ],
    ],
];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador — Cierre documental (paso 2/3): plan_actual.md ===\n\n";

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