<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda de documentación v1.5piloto.78a:
 *  - Actualiza plan_actual.md con el cierre de B3.3,
 *    el plan de carga parcial y la idea de iteradores
 *    con ID especial.
 *  - Actualiza prompt_framework_iteradores.md (§11.4 y §11.5).
 *  - Actualiza prompt_piloto.md (§8.7 y cierre).
 *  - Actualiza prompt_sistema_scripts.md (lecciones 18-19).
 *
 * Sin cambios de código. Solo documentación.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

// ------------------------------------------------------------
// plan_actual.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: bump §1.1 a v78a',
    'buscar' => [
        '**Última actualización de este archivo:** v1.5piloto.77o',
        '(cierre de la fase B2.3.5: pruebas del plugin + fixes).',
        'La Fase B2.3 quedó cerrada completa (B2.3.1 a B2.3.5). El',
        'repuntado de terminales al compartido está aplicado en',
        'producción, los terminales nuevos se repuntan al vuelo',
        '(`_asegurar_compartido_y_repuntar`, v77l), y las 3 pruebas',
        'del plugin (57, 58, 59) están verdes. La auditoría completa',
        'de B2.4 ("mantener vivos los compartidos") y B3 ("eliminar',
        'los usos de `Nodo::nodo_por_id(\'usuarios\')`") está en §1.9.',
        'Próximo paso: **Fase B2.4** (los 5 bugs funcionales que',
        'detectó la auditoría), empezando por B2.4.2 + B2.4.5.',
    ],
    'reemplazar' => [
        '**Última actualización de este archivo:** v1.5piloto.78a',
        '(cierre de la Fase B3.3 + fix v78a).',
        'La línea B del modelo topológico quedó cerrada salvo',
        'B3.3c: B2.4 (Tanda A, v77q) y B3 (B3.1 v77r; B3.3a v77t;',
        'B3.3b v77u; B3.3d v77v; B3.3e v77y + revert v77z). El fix',
        'v78a cierra el agujero de niveles al crear usuario (mismo',
        'patrón que v77l para compartidos). Queda pendiente B3.3c',
        '(los 6 helpers que iteran sobre todos los dueños),',
        'postergada hasta que el framework implemente iteradores',
        'con ID especial + carga parcial. Próximo paso: **tanda de',
        'documentación (esta)** y después **Etapa 0 del plan de',
        'carga parcial** (infraestructura en el Controlador +',
        '`_contextos_para_operacion` en el Enrutador).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: agregar cadena v77r-v78a',
    'buscar' => [
        '  desincroniza de los compartidos del dueño. `aplicacion_POST.php`',
        '  actualizado al modelo topológico.',
        '',
        '**Tanda actual:** v78a (asegurar niveles al crear usuario).',
    ],
    'reemplazar' => [
        '  desincroniza de los compartidos del dueño. `aplicacion_POST.php`',
        '  actualizado al modelo topológico.',
        '- **v77r** (Fase B3.1): aislación de usuarios con',
        '  `us_<nombre>` en Venta, Viaje, Enrutador, Usuario, Empresa,',
        '  ViajeMicros, Pasajero e index.php. `agregar_usuario` ya no',
        '  autocrea la raíz `usuarios`.',
        '- **v77s**: fix del `$raiz` residual en `eliminar_usuario`.',
        '- **v77t** (Fase B3.3a): `Liquidacion.php` y `Rendicion.php`',
        '  a `us_<nombre>` en las 7 resoluciones con nombre por',
        '  parámetro.',
        '- **v77u** (Fase B3.3b): `Pasajero.php` rama sin contexto +',
        '  `ViajeAsientos.php` (3 resoluciones).',
        '- **v77v** (Fase B3.3d): `Vehiculo.php` a `us_<nombre>` con',
        '  `$nombre_dueno` requerido. `Enrutador.php` pasa el dueño en',
        '  las 6 acciones. `micros.js` manda `nombre_dueno`.',
        '- **v77w**: fix del `continue` huérfano en',
        '  `subir_foto_vehiculo` (rompía el parseo de `Vehiculo.php`).',
        '- **v77x**: fix de `viajes-micros.js`. El modal "Agregar',
        '  micro" no mandaba `nombre_dueno` a `vehiculos/listar`.',
        '- **v77y** (Fase B3.3e): `Migraciones/Funciones.php` y',
        '  `Migraciones/Comandos.php` reescritos con',
        '  `por_cada_nodo_ejecutar` y `us_<nombre>`.',
        '- **v77z**: revert parcial de v77y. Los detectores vuelven a',
        '  `usuarios->adyacentes()` (el recorrido completo del grafo',
        '  encuentra otros nodos con adyacente `nivel`). Se mantiene',
        '  el fix de `Migraciones/Comandos.php`.',
        '- **v78a**: helper `_asegurar_niveles_usuario` en',
        '  `Usuario.php`, llamado desde `agregar_usuario`. Cierra el',
        '  agujero de niveles al crear usuario (mismo patrón que v77l',
        '  para compartidos).',
        '',
        '**Tanda actual:** v78a.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: actualizar §1.7 Plan de limpieza',
    'buscar' => [
        '**Plan de limpieza:**',
        '',
        '- **B2.3.4 (en curso):** reemplazar los usos que están dentro',
        '  del flujo de ventas y de contexto del terminal',
        '  (`confirmar_venta_actual`, `cancelar_venta`,',
        '  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion` y',
        '  `_contexto_terminal`). Se reemplazan por',
        '  `Nodo::nodo_por_id(\'us_\' . $nombre)`. Sin cambio visible',
        '  (funciona igual hoy con el grafo completo).',
        '- **B3 (limpieza final):** reemplazar el resto. Los 6 del',
        '  enrutador son los más delicados: requieren que el enrutador',
        '  empiece a pasar contexto (repuntado de B2.3.5). Los fallbacks',
        '  "sin contexto" de las funciones contenedoras se eliminan en',
        '  B3.',
    ],
    'reemplazar' => [
        '**Plan de limpieza (estado a v78a):**',
        '',
        '- **B2.3.4 (cerrada en v77e):** reemplazo por `us_<nombre>`',
        '  en `confirmar_venta_actual`, `cancelar_venta`,',
        '  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion` y',
        '  `_contexto_terminal`.',
        '- **B3.1 (cerrada en v77r):** aislación de usuarios con',
        '  `us_<nombre>` en los flujos que ya tenían el nombre a mano.',
        '- **B3.3a-e (cerradas):** Liquidacion, Rendicion, Pasajero',
        '  (rama sin contexto), ViajeAsientos, Vehiculo, Enrutador',
        '  (parcial) y Migraciones (con revert de v77z para los',
        '  detectores).',
        '- **B3.3c (pendiente, postergada):** los 6 helpers que',
        '  iteran sobre todos los dueños buscando por id. Se',
        '  resuelven cuando el framework implemente iteradores con',
        '  ID especial + carga parcial (ver §1.10). Si aparece',
        '  dolor real antes, se evalúa rediseño.',
        '- **B3.2 (eliminar los fallbacks "sin contexto"):** se',
        '  resuelve junto con la carga parcial, porque el fallback',
        '  "sin contexto" es justamente el que no se puede usar',
        '  cuando hay contextos activos.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: agregar §1.10, §1.11, §1.12',
    'buscar' => [
        'eso rompería la privacidad topológica (el terminal podría',
        'llegar al dueño por el enlace y desde ahí a todo el grafo).',
        '',
        '---',
        '',
        '## 2. PLAN DE LA LÍNEA DE TANDAS',
    ],
    'reemplazar' => [
        'eso rompería la privacidad topológica (el terminal podría',
        'llegar al dueño por el enlace y desde ahí a todo el grafo).',
        '',
        '### 1.10 Plan de carga parcial (línea siguiente)',
        '',
        'Plan consensuado en v78a. No implementado todavía. La',
        'línea B del modelo topológico dejó el grafo preparado',
        '(usuarios como IDs especiales, contenedores por nivel,',
        'compartidos por terminal, contextos opcionales en todas',
        'las funciones de contenedor). El framework PHP ya tiene',
        '`PerdurarSuperestructuraStringSQL64` (bitmask de',
        'contextos, 3 tablas nuevas, `cargar_parcial`), y el espejo',
        'JS tiene `PerdurarSuperestructuraStringIndexedDB64`.',
        'Falta usarlos.',
        '',
        '**Política.**',
        '',
        '- **Contexto por defecto: siempre parcial**, con el',
        '  contexto del usuario solicitante. Admin y soporte son',
        '  la excepción explícita: necesitan ver todo.',
        '- **Contexto por operación: caso por caso.** La mayoría',
        '  de las operaciones del piloto alcanzan con el propio',
        '  `us_<solicitante>`. Algunas necesitan sumar el del',
        '  dueño (por ejemplo, un terminal que necesita ver su',
        '  compartido y el dueño). Otras, contexto total (login,',
        '  módulo grafo, módulo administrador).',
        '- **Guardar delta como objetivo.** El framework tiene',
        '  una única superestructura estática en `Nodo`, así que',
        '  el grafo parcial y el total no coexisten en memoria.',
        '  La única vía es delta: al cargar parcial se marca qué',
        '  nodos vinieron del contexto; al guardar se persisten',
        '  solo los que cambiaron. La opción "merge en memoria"',
        '  que figuraba en §11.4 del prompt del framework queda',
        '  descartada por ese motivo.',
        '- **`_contextos_para_operacion` vive en el Enrutador.**',
        '  No en un archivo nuevo. El Enrutador ya conoce la',
        '  `accion` y el nombre del solicitante. Devuelve el',
        '  array de contextos a cargar.',
        '',
        '**Etapas.**',
        '',
        '- **Etapa 0 — Preparación.** Sin cambio de',
        '  comportamiento. Se agrega `_contextos_para_operacion`',
        '  en el Enrutador. Se decide cómo el Controlador sabe',
        '  que debe cargar parcial (probablemente un flag',
        '  `Controlador::establecer_modo_carga(\'parcial\'|\'total\')`',
        '  más el uso directo de `cargar_parcial`). No se activa',
        '  todavía.',
        '- **Etapa 1 — Carga parcial activa + guardar delta.**',
        '  El Enrutador pide la carga con los contextos',
        '  correspondientes. La persistencia implementa',
        '  `guardar_parcial` con delta real (marcar nodos sucios',
        '  al cargar, persistir solo los cambiados). Fallback a',
        '  carga total cuando la operación toca uno de los 6',
        '  helpers de B3.3c.',
        '- **Etapa 2 — Optimización.** Bajar el costo de',
        '  `guardar` en el caso general (sin contexto). Revisar',
        '  iteradores persistentes (§11.3 del framework).',
        '- **Etapa 3 — Espejo JS.** `PerdurarSuperestructuraStringIndexedDB64.js`',
        '  recibe el mismo tratamiento. `Controlador.js` decide',
        '  modo de carga. El plugin prueba desde el navegador.',
        '',
        '**Decisiones pendientes antes de Etapa 0:**',
        '',
        '- Tabla concreta de `_contextos_para_operacion` para',
        '  cada acción del enrutador. Se arma caso por caso.',
        '- Lista exacta de operaciones que caen al fallback',
        '  (las que tocan los 6 helpers de B3.3c).',
        '- Forma de "marcar nodos sucios" en la carga parcial.',
        '  Puede ser un flag en el Nodo (contradice la deuda de',
        '  §11.4 del framework de "dejar el Nodo limpio"), o',
        '  una estructura auxiliar del Controlador. A consensuar.',
        '',
        '### 1.11 Idea para el framework: iteradores con ID especial',
        '',
        'Anotada en v78a. No es parte del plan inmediato. Se',
        'retoma después de la Etapa 3 de la carga parcial, o',
        'antes si la carga parcial por sí sola no alcanza para',
        'los casos que quedaron postergados (B3.3c).',
        '',
        '**Idea.** Los iteradores del framework tienen hoy un',
        'nombre (string) y una posición en el grafo. La',
        'propuesta es darles también un ID especial persistente',
        '(`it_<nombre>` o similar), convirtiéndolos en raíces de',
        'subgrafo — igual que los IDs especiales que hoy usa el',
        'sistema de contextos (`us_*`, `sesiones`, `aplicacion`).',
        '',
        '**Dos consecuencias.**',
        '',
        '1. **Un iterador cargado trae su subgrafo alcanzable,**',
        '   no solo el nodo apuntado. Cargar `it_viajes_carmen1`',
        '   carga todo lo alcanzable desde su posición actual.',
        '2. **Un iterador tiene un usuario que lo "ocupa".** El',
        '   ocupante impide que dos usuarios lo muevan a la vez',
        '   y da un criterio claro de "quién es dueño de qué',
        '   subgrafo".',
        '',
        '**Especialización por tipo.** Varios iteradores, cada',
        'uno especializado en un tipo (`it_empresas_<dueno>`,',
        '`it_viajes_<dueno>`, `it_micros_<dueno>`,',
        '`it_pasajeros_<dueno>`, `it_asientos_<dueno>`,',
        '`it_cupones_<dueno>`, `it_liquidaciones_<dueno>`,',
        '`it_rendiciones_<dueno>`, `it_cancelaciones_<dueno>`,',
        '`it_usuarios_<dueno>`). Un iterador por tipo por',
        'usuario.',
        '',
        '**Beneficio.** El backend decide "qué iteradores cargar"',
        'según la operación y el usuario. En vez de "cargar todo',
        'el grafo" o "cargar todos los contextos del usuario",',
        'carga "los iteradores que el usuario ocupa, filtrados',
        'por tipo". Combinado con el bitmask de contextos, cada',
        'iterador es un bit de contexto más.',
        '',
        '**Diferencia con contextos actuales.** Un contexto es',
        'un conjunto de nodos alcanzables desde un ID especial',
        'estático. Un iterador con ID especial es lo mismo, pero',
        'con posición mutable y un ocupante. La diferencia es',
        'semántica, no estructural: en el bitmask, un `it_*` es',
        'un contexto igual que un `us_*`.',
        '',
        '**Interacción con la destrucción de nodos.** Hay que',
        'revisar los flujos de destrucción: si un nodo vive',
        'dentro del subgrafo de un iterador, y el iterador se',
        'mueve, hay que decidir si el nodo se destruye o queda',
        'huérfano. Conecta con §11.2 del prompt del framework',
        '(fuga de nodos huérfanos).',
        '',
        'La idea está también anotada en §11.5 del',
        '`prompt_framework_iteradores.md`.',
        '',
        '### 1.12 Lecciones de la sesión v77q-v78a',
        '',
        '**Sobre los reemplazos automáticos.**',
        '',
        '1. **Los reemplazos al final de una función deben',
        '   incluir el `}` de cierre.** Si se reemplaza solo',
        '   la primera mitad de un bloque (por ejemplo, un',
        '   bucle), quedan fragmentos huérfanos (`continue`,',
        '   `break`, `return`) que rompen el parseo del',
        '   archivo entero. Caso v77w: un `continue` fuera',
        '   de contexto en `subir_foto_vehiculo` rompió',
        '   `Vehiculo.php`.',
        '2. **Grep cruzado por nombre de acción POST al',
        '   cambiar firmas.** Cuando se cambia la firma de',
        '   una función PHP que responde a una acción POST',
        '   (por ejemplo, agregar `$nombre_dueno` a una',
        '   función de `Vehiculo.php`), hay que buscar no',
        '   solo por el nombre de la función, sino también',
        '   por el nombre de la acción (`vehiculos/listar`,',
        '   `vehiculos/agregar`, etc.) para encontrar todos',
        '   los lugares del frontend que la llaman. Caso',
        '   v77x: el modal "Agregar micro" llamaba a',
        '   `vehiculos/listar` sin `nombre_dueno` y el',
        '   select quedó vacío.',
        '',
        '**Sobre el framework y los IDs.**',
        '',
        '3. **Los detectores basados en `por_cada_nodo_ejecutar`',
        '   sobre el grafo completo son frágiles.** El',
        '   recorrido encuentra otros nodos con adyacentes',
        '   que imitan la forma buscada (nodos huérfanos',
        '   viejos, etc.) y da falsos negativos. Los',
        '   detectores que necesitan "listar todos los',
        '   usuarios" deben usar `usuarios->adyacentes()`',
        '   (revert v77z).',
        '4. **Los agujeros de mantenimiento topológico se',
        '   cierran al vuelo.** El fix v78a es el mismo',
        '   patrón que v77l: cada flujo de alta debe crear',
        '   los contenedores que le corresponden para que',
        '   las migraciones de detección no den `false`',
        '   cada vez que se crea un elemento nuevo.',
        '',
        '**Sobre el diseño de la carga parcial.**',
        '',
        '5. **El grafo parcial y el total no coexisten en',
        '   memoria.** El framework tiene una única',
        '   superestructura estática en `Nodo`. Cargar',
        '   parcial reemplaza todo. La única vía para',
        '   guardar es delta (marcar nodos sucios al',
        '   cargar y persistir solo los cambiados).',
        '6. **La función que decide los contextos vive en el',
        '   Enrutador.** No en un archivo nuevo, no en el',
        '   Controlador. El Enrutador ya conoce la acción y',
        '   el solicitante. El Controlador solo expone',
        '   `cargar_parcial`, `guardar_parcial` y un flag',
        '   de modo.',
        '',
        '---',
        '',
        '## 2. PLAN DE LA LÍNEA DE TANDAS',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: actualizar §2.2 fases',
    'buscar' => [
        '1. **Fase A** — Usuarios como IDs especiales `us_<nombre>`.',
        '   **Completada** (v76k).',
        '2. **Fase B1** — Contenedores `publico` y `privado` en cada',
        '   usuario, como alias de los nodos existentes. **Completada**',
        '   (v76m).',
        '3. **Fase B2.1** — Compartidos por terminal (`compartido_con_us_termX`)',
        '   creados con referencias filtradas. **Completada** (v76q).',
        '4. **Fase B2.2.1** — Contexto opcional en `obtener_contenedor_viajes_dueno`.',
        '   **Completada** (v76r).',
        '5. **Fase B2.2.2** — Contexto y filtro en Venta.php.',
        '   **Completada** (v76s).',
        '6. **Fase B2.2.3** — Contexto en ViajeAsientos.php.',
        '   **Completada** (v76t).',
        '7. **Fase B2.2.4** — Contexto en Empresa.php.',
        '   **Completada** (v76u).',
        '8. **Fase B2.3** — Árboles paralelos con nombres parametrizados',
        '   en `Arbol.php`. **En curso.** Detalle en §2.3.',
        '9. **Fase B2.4** — Mantener los compartidos vivos (cada operación',
        '   que modifique el contenido del compartido lo actualiza).',
        '   **Pendiente.**',
        '10. **Fase B3** — Eliminar los accesos viejos. **Pendiente.**',
        '11. **Fase C** (opcional) — Tipos como IDs especiales, ortogonal.',
        '12. **Fase D** — Aprovechar la carga parcial como optimización.',
        '    **Pendiente.**',
    ],
    'reemplazar' => [
        '1. **Fase A** — Usuarios como IDs especiales `us_<nombre>`.',
        '   **Completada** (v76k).',
        '2. **Fase B1** — Contenedores `publico` y `privado` en cada',
        '   usuario, como alias de los nodos existentes. **Completada**',
        '   (v76m).',
        '3. **Fase B2.1** — Compartidos por terminal (`compartido_con_us_termX`)',
        '   creados con referencias filtradas. **Completada** (v76q).',
        '4. **Fase B2.2** — Contexto opcional en Viaje, Venta,',
        '   ViajeAsientos y Empresa. **Completada** (v76r-v76u).',
        '5. **Fase B2.3** — Árboles paralelos con nombres',
        '   parametrizados en `Arbol.php`. **Completada** (v76v-v77o).',
        '6. **Fase B2.4** — Mantener los compartidos vivos. **Completada**',
        '   (Tanda A, v77q).',
        '7. **Fase B3** — Eliminar los accesos viejos. **Cerrada salvo',
        '   B3.3c** (postergada hasta la carga parcial; ver §1.10).',
        '8. **Fase C** (opcional) — Tipos como IDs especiales, ortogonal.',
        '   **Pendiente.**',
        '9. **Carga parcial** (ex Fase D) — Pasar a carga y guardado',
        '   parciales por contexto. **En preparación** (plan en §1.10).',
    ],
],

// ------------------------------------------------------------
// prompt_framework_iteradores.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: marcar SQL64 como implementado',
    'buscar' => [
        '**Método `SQL64` (fase 1, a implementar).** Misma base',
        'de datos que `SQL`, pero usa **tres tablas nuevas**',
    ],
    'reemplazar' => [
        '**Método `SQL64` (implementado, fase 1).** Misma base',
        'de datos que `SQL`, pero usa **tres tablas nuevas**',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: reescribir bloque de Fases en §11.4',
    'buscar' => [
        '**Fases.**',
        '',
        '- **Fase 1**: `PerdurarSuperestructuraStringSQL64` (PHP).',
        '  Comportamiento: `guardar` calcula y persiste el',
        '  bitmask; `cargar` lee todo; `cargar_parcial` filtra',
        '  con `WHERE contexto_mask & $mask != 0`. Los enlaces a',
        '  nodos fuera del filtro se descartan (silencioso).',
        '  `guardar_parcial` queda como stub que devuelve error.',
        '  El `Controlador` marca la superestructura como parcial',
        '  y `guardar` falla si se intenta guardar un grafo',
        '  parcial sin usar `guardar_parcial`.',
        '- **Fase 2**: `PerdurarSuperestructuraStringIndexedDB64.js`',
        '  (JS). Espejo de fase 1.',
        '- **Fase 3**: `PerdurarSuperestructuraStringJSON64.php` y',
        '  `PerdurarSuperestructuraStringXML64.php`. Estos',
        '  formatos agregan `guardar_parcial` escribiendo a un',
        '  archivo separado. Regla: solo se puede cargar un',
        '  subconjunto que fue guardado previamente.',
        '- **Fase 4** (opcional): implementar `guardar_parcial`',
        '  real en SQL64 e IndexedDB64. Requiere merge del',
        '  subgrafo en memoria con el persistido sin pisar',
        '  contextos fuera de la operación.',
        '- **Fase 5** (si hace falta): extender a 256 contextos',
        '  (bitmask de `BINARY(32)`) o al producto de primos',
        '  (ver discusión en §11.1).',
    ],
    'reemplazar' => [
        '**Fases.**',
        '',
        '- **Fase 1 (completada, framework 1.5i.7k)**:',
        '  `PerdurarSuperestructuraStringSQL64` (PHP).',
        '  Comportamiento: `guardar` calcula y persiste el',
        '  bitmask; `cargar` lee todo; `cargar_parcial` filtra',
        '  con `WHERE contexto_mask & $mask != 0`. Los enlaces a',
        '  nodos fuera del filtro se descartan (silencioso).',
        '  `guardar_parcial` queda como stub que devuelve error.',
        '  El `Controlador` marca la superestructura como parcial',
        '  y `guardar` falla si se intenta guardar un grafo',
        '  parcial sin usar `guardar_parcial`.',
        '- **Fase 2 (completada, framework 1.5i.7k)**:',
        '  `PerdurarSuperestructuraStringIndexedDB64.js` (JS).',
        '  Espejo de fase 1.',
        '- **Fase 3** (pendiente): `PerdurarSuperestructuraStringJSON64.php`',
        '  y `PerdurarSuperestructuraStringXML64.php`. Estos',
        '  formatos agregan `guardar_parcial` escribiendo a un',
        '  archivo separado. Regla: solo se puede cargar un',
        '  subconjunto que fue guardado previamente.',
        '- **Fase 4** (pendiente): implementar `guardar_parcial`',
        '  real en SQL64 e IndexedDB64. **No es merge en',
        '  memoria.** El framework tiene una única',
        '  superestructura estática en `Nodo`, así que un',
        '  grafo parcial y el total no coexisten en memoria:',
        '  la única vía es **delta**. Al cargar parcial se',
        '  marca qué nodos vinieron del contexto (o se',
        '  mantiene una lista de nodos sucios desde la carga);',
        '  al guardar se persisten solo los cambios. Esto da',
        '  la ganancia real de performance y evita el problema',
        '  de "leer el total desde la DB para mergear".',
        '- **Fase 5** (si hace falta): extender a 256 contextos',
        '  (bitmask de `BINARY(32)`) o al producto de primos',
        '  (ver discusión en §11.1).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: agregar §11.5 (iteradores con ID especial)',
    'buscar' => [
        '**Momento:** antes de la fase 5 (256 contextos) o antes',
        'de cualquier otro método de indexación. En la fase 1-2',
        'actual el campo en el nodo funciona; no bloquea nada.',
        '',
        '---',
        '',
        '## 10. HISTORIAL DEL FRAMEWORK',
    ],
    'reemplazar' => [
        '**Momento:** antes de la fase 5 (256 contextos) o antes',
        'de cualquier otro método de indexación. En la fase 1-2',
        'actual el campo en el nodo funciona; no bloquea nada.',
        '',
        '### 11.5 Dirección futura: iteradores como raíces de contexto',
        '',
        'Idea anotada en v1.5piloto.78a. No implementada. Se',
        'retoma después de la Fase 4 de §11.4, o antes si la',
        'carga parcial por sí sola no alcanza para los casos',
        'que quedaron postergados en el piloto (los 6 helpers',
        'que iteran sobre todos los dueños).',
        '',
        '**Propuesta.** Los iteradores actuales tienen un',
        'nombre (string) y una posición en el grafo. La idea',
        'es que también tengan un ID especial persistente',
        '(`it_<nombre>` o similar). Eso los convierte en',
        'raíces de subgrafo, igual que los IDs especiales que',
        'hoy usa el sistema de contextos (`us_*`, `sesiones`,',
        '`aplicacion`).',
        '',
        '**Dos consecuencias.**',
        '',
        '1. **Un iterador cargado trae su subgrafo alcanzable,**',
        '   no solo el nodo apuntado. Cargar el iterador',
        '   `it_viajes_carmen1` carga todo lo alcanzable desde',
        '   su posición actual.',
        '2. **Un iterador tiene un usuario que lo "ocupa".**',
        '   El ocupante impide que dos usuarios lo muevan a la',
        '   vez, y da un criterio claro de "quién es dueño de',
        '   qué subgrafo".',
        '',
        '**Especialización por tipo.** Varios iteradores, cada',
        'uno especializado en un tipo (`it_empresas_<dueno>`,',
        '`it_viajes_<dueno>`, `it_micros_<dueno>`,',
        '`it_pasajeros_<dueno>`, `it_asientos_<dueno>`,',
        '`it_cupones_<dueno>`, `it_liquidaciones_<dueno>`,',
        '`it_rendiciones_<dueno>`, `it_cancelaciones_<dueno>`,',
        '`it_usuarios_<dueno>`). Un iterador por tipo por',
        'usuario.',
        '',
        '**Beneficio.** El backend decide "qué iteradores',
        'cargar" según la operación y el usuario. En vez de',
        '"cargar todo el grafo" o "cargar todos los contextos',
        'del usuario", carga "los iteradores que el usuario',
        'ocupa, filtrados por tipo". Combinado con el bitmask',
        'de contextos, cada iterador es un bit de contexto',
        'más.',
        '',
        '**Diferencia con contextos actuales.** Un contexto es',
        'un conjunto de nodos alcanzables desde un ID especial',
        'estático. Un iterador con ID especial es lo mismo,',
        'pero con posición mutable y un ocupante. La',
        'diferencia es semántica, no estructural: en el',
        'bitmask, un `it_*` es un contexto igual que un `us_*`.',
        '',
        '**Interacción con la destrucción de nodos.** Hay que',
        'revisar los flujos de destrucción: si un nodo vive',
        'dentro del subgrafo de un iterador, y el iterador se',
        'mueve, hay que decidir si el nodo se destruye o',
        'queda huérfano. Conecta con §11.2 (fuga de nodos',
        'huérfanos).',
        '',
        '---',
        '',
        '## 10. HISTORIAL DEL FRAMEWORK',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: agregar entrada 1.5i.7n al historial',
    'buscar' => [
        '- **1.5i.7m**: `grafo:nodo` agrega el campo',
        '  `destino_es_compartido` a cada adyacente. Se calcula',
        '  con `$ady->adyacente(\'_es_compartido\') !== null`, que',
        '  es O(1) porque el nodo destino ya está en memoria.',
        '  Permite verificar topología por topología (por ejemplo,',
        '  "el enlace `dueno` del terminal apunta al compartido")',
        '  con una sola consulta, sin seguir IDs numéricos (que',
        '  no son estables entre cargas).',
    ],
    'reemplazar' => [
        '- **1.5i.7m**: `grafo:nodo` agrega el campo',
        '  `destino_es_compartido` a cada adyacente. Se calcula',
        '  con `$ady->adyacente(\'_es_compartido\') !== null`, que',
        '  es O(1) porque el nodo destino ya está en memoria.',
        '  Permite verificar topología por topología (por ejemplo,',
        '  "el enlace `dueno` del terminal apunta al compartido")',
        '  con una sola consulta, sin seguir IDs numéricos (que',
        '  no son estables entre cargas).',
        '- **1.5i.7n**: sin cambios funcionales al framework.',
        '  Documentación de §11.4 reescrita: la Fase 4 del',
        '  plan de contextos ahora es explícitamente "delta",',
        '  no merge en memoria (el framework no puede tener',
        '  grafo parcial y total al mismo tiempo; ver §11.4).',
        '  Fases 1 y 2 marcadas como completadas. Nueva §11.5',
        '  con la idea de iteradores como raíces de contexto',
        '  (IDs especiales + ocupante).',
    ],
],

// ------------------------------------------------------------
// prompt_piloto.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: actualizar §8.7 fases de implementación',
    'buscar' => [
        '**Fases de implementación.** El orden revisado es:',
        '',
        '1. **Fase A** (completada en v76k): usuarios como IDs',
        '   especiales (`us_<nombre>`).',
        '2. **Fase B1** (completada en v76m): contenedores',
        '   `publico` y `privado` creados como alias de los',
        '   nodos existentes. Los enlaces viejos quedan vivos.',
        '3. **Fase B2.1** (completada en v76q): crear los',
        '   contenedores `compartido_con_us_termX` en cada dueño,',
        '   con referencias filtradas a los viajes, empresas,',
        '   ventas y cancelaciones del terminal. Alias al',
        '   contenedor de pasajeros del dueño. **No repunta',
        '   nada**: los compartidos coexisten con la estructura',
        '   actual. La migración es idempotente.',
        '4. **Fase B2.2** (pendiente): cambiar el código para que',
        '   el terminal navegue por su compartido. Se agrega un',
        '   parámetro opcional `?Nodo $nodo_contexto` a',
        '   `obtener_contenedor_viajes_dueno`,',
        '   `obtener_contenedor_ventas_dueno` y',
        '   `obtener_contenedor_pasajeros_dueno`. Si el llamador',
        '   pasa el compartido, la función navega desde ahí. Si',
        '   no, navega desde la raíz `usuarios` como hoy.',
        '5. **Fase B2.3** (pendiente): repuntar `us_termX → dueno`',
        '   al contenedor `compartido_con_us_termX`. A partir de',
        '   acá, el acceso del terminal al grafo del dueño pasa',
        '   por la topología.',
        '6. **Fase B2.4** (pendiente): mantener vivos los',
        '   compartidos. Cada operación que modifica viajes',
        '   autorizados, micros, empresas o ventas del terminal',
        '   actualiza el compartido correspondiente.',
        '7. **Fase B3** (pendiente): eliminar los accesos viejos.',
        '8. **Fase C** (opcional): tipos como IDs especiales,',
        '   ortogonal.',
        '9. **Fase D** (pendiente): aprovechar la carga parcial',
        '   como optimización.',
    ],
    'reemplazar' => [
        '**Fases de implementación.** El orden revisado es:',
        '',
        '1. **Fase A** (completada en v76k): usuarios como IDs',
        '   especiales (`us_<nombre>`).',
        '2. **Fase B1** (completada en v76m): contenedores',
        '   `publico` y `privado` creados como alias de los',
        '   nodos existentes. Los enlaces viejos quedan vivos.',
        '3. **Fase B2.1** (completada en v76q): contenedores',
        '   `compartido_con_us_termX` creados como contenedores',
        '   filtrados. Alias al contenedor de pasajeros del',
        '   dueño. Sin repuntar.',
        '4. **Fase B2.2** (completada en v76r-v76u): contexto',
        '   opcional en Viaje, Venta, ViajeAsientos y Empresa.',
        '   Refactor sin cambio de comportamiento.',
        '5. **Fase B2.3** (completada en v76v-v77o): árboles',
        '   paralelos con nombres parametrizados; migración de',
        '   compartidos; repuntado de terminales al compartido;',
        '   pruebas del plugin. La Fase B2.3.5 dejó el',
        '   aislamiento activo en producción.',
        '6. **Fase B2.4** (completada en v77q, Tanda A): mantener',
        '   vivos los compartidos en cada operación de escritura',
        '   (`eliminar_viaje`, `agregar_terminal_autorizada`,',
        '   `eliminar_terminal_autorizada`, `agregar_micro_a_viaje`,',
        '   `eliminar_empresa`). Nuevo archivo',
        '   `Aplicacion/Compartidos.php`.',
        '7. **Fase B3** (B3.1 v77r, B3.3a-e v77t-v78a; B3.3c',
        '   postergada): eliminar los accesos viejos a la raíz',
        '   `usuarios`. Quedan pendientes los 6 helpers que',
        '   iteran sobre todos los dueños buscando por id',
        '   (B3.3c). Se resuelven cuando el framework',
        '   implemente iteradores con ID especial + carga',
        '   parcial (ver `plan_actual.md` §1.10 y',
        '   `prompt_framework_iteradores.md` §11.5).',
        '8. **Fase C** (opcional): tipos como IDs especiales,',
        '   ortogonal.',
        '9. **Carga parcial** (ex Fase D, en preparación):',
        '   pasar a carga y guardado parciales por contexto.',
        '   Plan detallado en `plan_actual.md` §1.10.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: actualizar estado al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.76u (framework 1.5i.7l).',
        'Todo funcional. Fixes de v74k a v74o acumulados.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.78a',
        '(framework 1.5i.7n). Todo funcional. La línea B del',
        'modelo topológico quedó cerrada salvo B3.3c',
        '(postergada hasta la carga parcial). Próximo: Etapa 0',
        'del plan de carga parcial (`plan_actual.md` §1.10).',
    ],
],

// ------------------------------------------------------------
// prompt_sistema_scripts.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: agregar aprendizajes 18 y 19',
    'buscar' => [
        '17. **Las pruebas que comparten estado acumulan problemas.**',
        '    Si las pruebas del plugin dependen del estado compartido',
        '    del piloto (por ejemplo, la cantidad de viajes o de',
        '    nodos), cuando el estado crece las pruebas se vuelven',
        '    lentas y frágiles. Paliativo: limpieza periódica +',
        '    timeouts holgados. Solución real: independencia de estado',
        '    entre pruebas o setup por prueba.',
    ],
    'reemplazar' => [
        '17. **Las pruebas que comparten estado acumulan problemas.**',
        '    Si las pruebas del plugin dependen del estado compartido',
        '    del piloto (por ejemplo, la cantidad de viajes o de',
        '    nodos), cuando el estado crece las pruebas se vuelven',
        '    lentas y frágiles. Paliativo: limpieza periódica +',
        '    timeouts holgados. Solución real: independencia de estado',
        '    entre pruebas o setup por prueba.',
        '18. **Reemplazos al final de una función deben incluir',
        '    el `}` de cierre.** Si se reemplaza solo la primera',
        '    mitad de un bloque (por ejemplo, un bucle), quedan',
        '    fragmentos huérfanos (`continue`, `break`, `return`)',
        '    que rompen el parseo del archivo entero. Caso v77w:',
        '    un `continue` fuera de contexto en',
        '    `subir_foto_vehiculo` rompió `Vehiculo.php`. Regla:',
        '    si se reemplaza un bloque de control de flujo,',
        '    incluir el `}` de cierre tanto en `buscar` como en',
        '    `reemplazar`.',
        '19. **Grep cruzado por nombre de acción POST al cambiar',
        '    firmas.** Cuando se cambia la firma de una función',
        '    PHP que responde a una acción POST (por ejemplo,',
        '    agregar `$nombre_dueno` a una función de',
        '    `Vehiculo.php`), hay que buscar no solo por el',
        '    nombre de la función, sino también por el nombre',
        '    de la acción (`vehiculos/listar`, `vehiculos/agregar`,',
        '    etc.) para encontrar todos los lugares del frontend',
        '    que la llaman. Caso v77x: el modal "Agregar micro"',
        '    llamaba a `vehiculos/listar` sin `nombre_dueno` y',
        '    el select quedó vacío. Se detectó tarde porque el',
        '    grep por función no alcanzaba.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: bump de última actualización',
    'buscar' => [
        '**Última actualización de este prompt:** v1.5piloto.77o.',
        'Se confirma que el sistema de scripts sigue igual. Recordatorio:',
        'los prompts del proyecto PHP (framework, piloto, sistema de',
        'scripts, plan actual) y del plugin (`iteradoresJS/prompts/`)',
        'se actualizan en la misma tanda que el código.',
    ],
    'reemplazar' => [
        '**Última actualización de este prompt:** v1.5piloto.78a.',
        'Se agregan dos aprendizajes (18-19): reemplazos al final',
        'de una función deben incluir el `}` de cierre (caso v77w),',
        'y grep cruzado por nombre de acción POST al cambiar',
        'firmas (caso v77x). El resto del sistema sigue igual.',
        'Recordatorio: los prompts del proyecto PHP (framework,',
        'piloto, sistema de scripts, plan actual) y del plugin',
        '(`iteradoresJS/prompts/`) se actualizan en la misma',
        'tanda que el código.',
    ],
],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
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
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";