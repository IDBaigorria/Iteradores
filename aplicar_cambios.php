<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda de documentación v1.5piloto.78m (cierre de la línea de carga parcial).
 *
 * Actualiza: aplicacion_POST.php, plan_actual.md,
 * prompt_piloto.md, prompt_framework_iteradores.md,
 * prompt_sistema_scripts.md.
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
// aplicacion_POST.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'aplicacion_POST: bump @version a 1.5piloto.78m',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.77q',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'aplicacion_POST: enlace soporte apunta al publico',
    'buscar' => [
        ' * | `soporte`       | Enlace directo al nodo usuario soporte (solo para `dueno`, opcional).                  |',
    ],
    'reemplazar' => [
        ' * | `soporte`       | Enlace directo al contenedor `publico` del soporte (solo para `dueno`,',
        ' * |                 | opcional). El `publico` solo expone `nivel`, `nombre_real`, `email`.',
        ' * |                 | A partir de v1.5piloto.78f el enlace apunta al `publico` (no al nodo',
        ' * |                 | del soporte) para evitar fuga cruzada entre dueños que comparten un',
        ' * |                 | mismo soporte: si apuntara al nodo real, el BFS desde un dueño',
        ' * |                 | llegaría al soporte y desde ahí a los otros dueños del soporte.        |',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'aplicacion_POST: bloque estado del modelo topologico',
    'buscar' => [
        ' * ## Modelo topológico: contenedores por nivel de exposición (v1.5piloto.77q)',
        ' * ',
        ' * **Estado:** implementado. Fases B1 (contenedores `publico`/`privado`),',
        ' * B2.1 (compartidos por terminal), B2.2 (contexto opcional en las',
        ' * funciones del piloto) y B2.3 (árboles paralelos parametrizados +',
        ' * repuntado de terminales al compartido) aplicadas. Próximo paso:',
        ' * Fase B2.4 (mantener vivos los compartidos en cada operación de',
        ' * escritura; ver `prompts/plan_actual.md` §1.9).',
    ],
    'reemplazar' => [
        ' * ## Modelo topológico: contenedores por nivel de exposición (v1.5piloto.78m)',
        ' * ',
        ' * **Estado:** implementado y con carga parcial activa. Fases B1',
        ' * (contenedores `publico`/`privado`), B2.1 (compartidos por terminal),',
        ' * B2.2 (contexto opcional), B2.3 (árboles paralelos + repuntado), B2.4',
        ' * (sincronización al vuelo) y B3 (aislación de usuarios) aplicadas.',
        ' * La carga parcial (Etapa 1c del plan) está activa: cada operación del',
        ' * terminal carga solo su compartido; el dueño su propio subgrafo; el',
        ' * admin todo (gracias al enlace `us_Administrador → usuarios`).',
        ' * ',
        ' * **Cambios de la v78f/v78m:**',
        ' * - `us_Administrador → usuarios`: enlace creado en v78f para que el',
        ' *   admin cargue todos los usuarios desde su propio contexto.',
        ' * - `dueno → soporte → publico`: el enlace `soporte` del dueño apunta',
        ' *   al `publico` del soporte (no al nodo real), para evitar fuga cruzada.',
        ' * - Carga parcial: index.php decide entre `cargar`/`cargar_parcial`',
        ' *   según los contextos devueltos por `_contextos_para_operacion`',
        ' *   (Enrutador). `guardar_ambos` detecta grafo parcial y llama a',
        ' *   `guardar_parcial` con los contextos cargados.',
        ' * - Persistencia en SQL64 (bitmask de contextos, 3 tablas nuevas:',
        ' *   nodo_contexto, adyacente_contexto, contexto).',
    ],
],

// ------------------------------------------------------------
// prompts/plan_actual.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: bump §1.1 a v78m',
    'buscar' => [
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
    'reemplazar' => [
        '**Última actualización de este archivo:** v1.5piloto.78m',
        '(cierre de la línea de carga parcial — todos los módulos',
        'principales adaptados al contexto del solicitante).',
        'La línea B del modelo topológico quedó cerrada. La Etapa 1',
        'de carga parcial (1a, 1b, 1c.1a/b, 1c.2a/b/c/d/e/f) está',
        'aplicada:',
        '  - SQL64 como método de persistencia (con las 3 tablas',
        '    nuevas: nodo_contexto, adyacente_contexto, contexto).',
        '  - `guardar_parcial` real en SQL64 (PHP) e',
        '    IndexedDB64 (JS).',
        '  - `Controlador::contextos_cargados()` y `$grafo_parcial`.',
        '  - `us_Administrador → usuarios` (migración v78f).',
        '  - `dueno → soporte → publico` (evita fuga cruzada).',
        '  - `_contextos_para_operacion` en el Enrutador decide entre',
        '    carga parcial (`[us_<sol>]`) y carga total.',
        '  - `guardar_ambos` usa `guardar_parcial` si el grafo es',
        '    parcial.',
        'Queda pendiente B3.3c (los 6 helpers que iteran sobre',
        'todos los dueños) y la adaptación de contexto en',
        '`Liquidacion.php`, `Rendicion.php`, `Empresa.php` y',
        '`Vehiculo.php`. Próximo paso: pruebas exhaustivas del',
        'piloto y, según aparezcan, resolver los pendientes en',
        'sub-tandas cortas.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: cadena de cierres v78b-v78m',
    'buscar' => [
        '- **v78a**: helper `_asegurar_niveles_usuario` en',
        '  `Usuario.php`, llamado desde `agregar_usuario`. Cierra el',
        '  agujero de niveles al crear usuario (mismo patrón que v77l',
        '  para compartidos).',
        '',
        '**Tanda actual:** v78a.',
    ],
    'reemplazar' => [
        '- **v78a**: helper `_asegurar_niveles_usuario` en',
        '  `Usuario.php`, llamado desde `agregar_usuario`. Cierra el',
        '  agujero de niveles al crear usuario (mismo patrón que v77l',
        '  para compartidos).',
        '- **v78b** (Etapa 0 carga parcial): `delegar_con_args` en el',
        '  Controlador; `guardar_parcial` con chequeos; docblock del',
        '  flag. `_contextos_para_operacion` (stub) en el Enrutador.',
        '- **v78c** (Tanda 1b): `validar_token_sesion` y',
        '  `_construir_respuesta_login` usan `us_<nombre>` directo.',
        '  `Controlador::$contextos_cargados` + getter.',
        '  `guardar_ambos` consulta los contextos cargados.',
        '- **v78d**: chequeos defensivos de grafo parcial en',
        '  `Usuario.php` (6 funciones) y `Migraciones/Funciones.php`',
        '  (2 detectores).',
        '- **v78e** (Tanda 1c.1a): `SQL64` como método de persistencia.',
        '  Bloque `?migrar_a_sql64=1` (migra app + credenciales).',
        '- **v78f** (Tanda 1c.1b): migración topológica.',
        '  `us_Administrador → usuarios`, `dueno → soporte → publico`.',
        '  Cambio en `_asignar_duenos_a_soporte`.',
        '- **v78g** (Tanda 1c.2): carga parcial activa.',
        '  `_contextos_para_operacion` implementado. `index.php`',
        '  reorganizado. Bootstrap solo en carga total.',
        '  `GrafoCredenciales.php` preserva y restaura contextos.',
        '- **v78h** (Tanda 1c.2a): contexto del solicitante en',
        '  `ViajeMicros.php` (5 funciones). Enrutador: 4 acciones de',
        '  `viajes/*_micro`.',
        '- **v78i** (Tanda 1c.2b): contexto en `ViajeOpciones.php`',
        '  (2 funciones). Enrutador: 2 acciones de',
        '  `viajes/*_opciones_avanzadas`.',
        '- **v78j** (Tanda 1c.2c): contexto en `Viaje.php`.',
        '  `formatear_viaje`, `viaje_tiene_ventas`,',
        '  `_calcular_vendidos_por_micro_de_viaje`,',
        '  `obtener_opciones_terminal_viaje`,',
        '  `obtener_declaracion_jurada`. Enrutador: 2 acciones.',
        '- **v78k** (Tanda 1c.2d): contexto en `Venta.php`.',
        '  `_dni_asignado_en_viaje_venta`, `obtener_o_crear_pasajero`,',
        '  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion`,',
        '  `listar_cancelaciones_de_dueno`. `confirmar_venta_actual`',
        '  y `cancelar_venta` propagan contexto.',
        '- **v78l** (Tanda 1c.2e): contexto en `Pasajero.php`',
        '  (9 funciones). Enrutador: 3 acciones.',
        '- **v78m** (Tanda 1c.2f): contexto en `Impresion.php`',
        '  (2 helpers + `generar_impresion`).',
        '  `obtener_venta_por_id` y `_buscar_venta_por_id` aceptan',
        '  dueño. `_contextos_para_operacion` con excepciones para',
        '  informes que usan B3.3c.',
        '',
        '**Tanda actual:** v78m (documentación de cierre).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §1.7 Plan de limpieza actualizado',
    'buscar' => [
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
    'reemplazar' => [
        '**Plan de limpieza (estado a v78m):**',
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
        '- **Carga parcial (Etapa 1, aplicada en v78b-v78m):**',
        '  contexto del solicitante en los módulos principales',
        '  (Viajes, Venta, Pasajeros, Impresión). SQL64 +',
        '  `guardar_parcial` + `_contextos_para_operacion` activos.',
        '- **B3.3c (pendiente, postergada):** los helpers que iteran',
        '  sobre todos los dueños buscando por id. En el grep actual',
        '  quedan ~7 puntos:',
        '    - `Venta.php` L1092 (`_buscar_venta_por_id` fallback sin',
        '      contexto), L2648 (`obtener_cancelacion_por_id`).',
        '    - `Liquidacion.php` L241 (`obtener_liquidacion_por_id`).',
        '    - `Rendicion.php` L736 (`aceptar_ajuste_rendicion`),',
        '      L857 (`obtener_rendicion_por_id`).',
        '    - `Pasajero.php` L172 (`obtener_pasajero_por_dni` — rama',
        '      del historial de ventas).',
        '  Se resuelven cuando aparezca dolor real o cuando',
        '  implementemos iteradores con ID especial (ver §1.10/§1.11).',
        '- **Contexto en módulos menores (pendiente, sub-tandas',
        '  cortas):** `Liquidacion.php`, `Rendicion.php`,',
        '  `Empresa.php`, `Vehiculo.php`. Mismo patrón que los',
        '  módulos ya adaptados. Se hacen a medida que aparezcan',
        '  errores en las pruebas.',
        '- **B3.2 (eliminar los fallbacks "sin contexto"):** se',
        '  resuelve junto con la adaptación de los módulos menores.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: §2.2 fases al dia',
    'buscar' => [
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
        '7. **Fase B3** — Eliminar los accesos viejos. **Cerrada',
        '   salvo B3.3c** (los 7 helpers que iteran sobre todos los',
        '   dueños; ver §1.7).',
        '8. **Carga parcial** (ex Fase D) — Carga y guardado parciales',
        '   por contexto. **Etapa 1 completa** (v78b-v78m): SQL64 +',
        '   `guardar_parcial` + `_contextos_para_operacion` +',
        '   adaptación del contexto en módulos principales.',
        '   Pendiente: módulos menores y B3.3c.',
        '9. **Fase C** (opcional) — Tipos como IDs especiales, ortogonal.',
        '   **Pendiente.**',
    ],
],

// ------------------------------------------------------------
// prompts/prompt_piloto.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: historial v78b-v78m',
    'buscar' => [
        '- **v78a**: helper `_asegurar_niveles_usuario` en',
        '  `Usuario.php` + llamada desde `agregar_usuario`. Cierra',
        '  el mismo agujero que v77l para compartidos: los usuarios',
        '  creados después de la migración B1 (v76m) quedaban sin',
        '  `publico`/`privado`, y el detector de la migración',
        '  `niveles_usuario` daba `false` cada vez que se creaba un',
        '  usuario. Idempotente, no elimina los enlaces viejos.',
    ],
    'reemplazar' => [
        '- **v78a**: helper `_asegurar_niveles_usuario` en',
        '  `Usuario.php` + llamada desde `agregar_usuario`. Cierra',
        '  el mismo agujero que v77l para compartidos: los usuarios',
        '  creados después de la migración B1 (v76m) quedaban sin',
        '  `publico`/`privado`, y el detector de la migración',
        '  `niveles_usuario` daba `false` cada vez que se creaba un',
        '  usuario. Idempotente, no elimina los enlaces viejos.',
        '- **v78b** (Etapa 0 carga parcial): `delegar_con_args` en el',
        '  Controlador (variante con args extra). `guardar_parcial`',
        '  con chequeos (grafo parcial + nodos desocupados). Docblock',
        '  del flag `$grafo_parcial`. `_contextos_para_operacion`',
        '  (stub) en el Enrutador. `index.php`: comentario que',
        '  documenta el cambio de la Etapa 1.',
        '- **v78c** (Tanda 1b): `validar_token_sesion` y',
        '  `_construir_respuesta_login` usan `Nodo::nodo_por_id(\'us_\'',
        '  . $nombre)` directo. `Controlador::$contextos_cargados`',
        '  + getter `contextos_cargados()`. `guardar_ambos` consulta',
        '  los contextos cargados y usa `guardar_parcial` si',
        '  corresponde.',
        '- **v78d**: chequeos defensivos de grafo parcial en',
        '  `Usuario.php` (buscar_usuario_por_codigo, listar_usuarios,',
        '  listar_duenos, listar_soportes, agregar_usuario,',
        '  eliminar_usuario) y `Migraciones/Funciones.php` (2',
        '  detectores).',
        '- **v78e** (Tanda 1c.1a): `SQL64` como método de',
        '  persistencia. Bloque `?migrar_a_sql64=1` (migra app +',
        '  credenciales). Corrección de la secuencia de migración',
        '  (el bloque se movió antes de la carga normal y hace su',
        '  propia carga desde SQL).',
        '- **v78f** (Tanda 1c.1b): migración topológica.',
        '  `miscelaneas/migrar_topologia.php` (nuevo):',
        '  `migrar_topologia_admin` (crea `us_Administrador →',
        '  usuarios`) y `migrar_topologia_soporte` (asegura `publico`',
        '  en cada soporte y repunta el enlace `dueno → soporte` al',
        '  `publico`, no al nodo del soporte). Cambio en',
        '  `_asignar_duenos_a_soporte`. Bloque',
        '  `?migrar_topologia=1`.',
        '- **v78g** (Tanda 1c.2): carga parcial activa. `index.php`',
        '  decide entre `cargar`/`cargar_parcial` según los contextos',
        '  devueltos por `_contextos_para_operacion`. Bootstrap',
        '  (nodo `aplicacion` + admin) solo en carga total.',
        '  `_contextos_para_operacion` implementado. `Enrutador.php`',
        '  con la regla simple: hay solicitante → `[\'us_<sol>\']`;',
        '  no → carga total. `GrafoCredenciales.php` captura y',
        '  restaura contextos al ir/vover a credenciales.',
        '- **v78h** (Tanda 1c.2a): contexto del solicitante en',
        '  `ViajeMicros.php`. Las 4 funciones (agregar_micro,',
        '  eliminar_micro, actualizar_monto, obtener_micro) +',
        '  `actualizar_contadores_viaje` aceptan `?Nodo $nodo_contexto`.',
        '  Enrutador: 4 acciones de `viajes/*_micro` pasan',
        '  `_contexto_solicitante`.',
        '- **v78i** (Tanda 1c.2b): contexto en `ViajeOpciones.php`',
        '  (`obtener_opciones_avanzadas_viaje` y',
        '  `guardar_opciones_avanzadas_viaje`). Enrutador: 2',
        '  acciones.',
        '- **v78j** (Tanda 1c.2c): contexto en `Viaje.php`.',
        '  `formatear_viaje` acepta `?Nodo $nodo_contexto` y lo',
        '  propaga a `viaje_tiene_ventas` (que lo acepta),',
        '  `_calcular_vendidos_por_micro_de_viaje` (que lo acepta),',
        '  al pre-cargado de `empresas`, y a',
        '  `obtener_opciones_avanzadas_viaje`.',
        '  `listar_viajes_de_terminal` pasa el compartido a',
        '  `formatear_viaje`. `obtener_opciones_terminal_viaje` y',
        '  `obtener_declaracion_jurada` aceptan contexto. Enrutador:',
        '  2 acciones.',
        '- **v78k** (Tanda 1c.2d): contexto en `Venta.php`.',
        '  `_dni_asignado_en_viaje_venta`, `obtener_o_crear_pasajero`,',
        '  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion` y',
        '  `listar_cancelaciones_de_dueno` aceptan',
        '  `?Nodo $nodo_contexto`. `confirmar_venta_actual` propaga',
        '  el compartido. `cancelar_venta` y `obtener_info_cancelacion`',
        '  derivan contexto. Enrutador: `cancelaciones/listar`.',
        '- **v78l** (Tanda 1c.2e): contexto en `Pasajero.php`',
        '  (9 funciones). Las 3 que iteran el árbol de ventas',
        '  (`pasajero_tiene_pasajes`, `pasajero_tiene_pasajes_activos`,',
        '  `pasajero_tiene_ventas_activas`) usan los nombres',
        '  parametrizados (`hmi_<term>`/`hd_<term>`) cuando el',
        '  contexto es un compartido. Enrutador: 3 acciones.',
        '- **v78m** (Tanda 1c.2f): contexto en `Impresion.php`.',
        '  `_nombre_real_usuario` y `_recolectar_ventas_para_informe`',
        '  usan `us_<nombre>` directo. `generar_impresion` acepta',
        '  `?string $nombre_dueno` y lo pasa a',
        '  `obtener_venta_por_id`. En `Venta.php`,',
        '  `obtener_venta_por_id` y `_buscar_venta_por_id` aceptan',
        '  `?string $nombre_dueno` (búsqueda directa en su contexto).',
        '  `index.php` pasa `$_GET[\'dueno\']`.',
        '  `_contextos_para_operacion` carga total para',
        '  `informe_cancelacion`/`informe_liquidacion`/',
        '  `informe_rendicion` (usan B3.3c). Con esto la línea de',
        '  carga parcial queda aplicada en los módulos principales.',
    ],
],

// ------------------------------------------------------------
// prompts/prompt_framework_iteradores.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: historial 1.5i.7o y 1.5i.7p',
    'buscar' => [
        '- **1.5i.7n**: sin cambios funcionales al framework.',
        '  Documentación de §11.4 reescrita: la Fase 4 del',
        '  plan de contextos ahora es explícitamente "delta",',
        '  no merge en memoria (el framework no puede tener',
        '  grafo parcial y total al mismo tiempo; ver §11.4).',
        '  Fases 1 y 2 marcadas como completadas. Nueva §11.5',
        '  con la idea de iteradores como raíces de contexto',
        '  (IDs especiales + ocupante).',
    ],
    'reemplazar' => [
        '- **1.5i.7n**: sin cambios funcionales al framework.',
        '  Documentación de §11.4 reescrita: la Fase 4 del',
        '  plan de contextos ahora es explícitamente "delta",',
        '  no merge en memoria (el framework no puede tener',
        '  grafo parcial y total al mismo tiempo; ver §11.4).',
        '  Fases 1 y 2 marcadas como completadas. Nueva §11.5',
        '  con la idea de iteradores como raíces de contexto',
        '  (IDs especiales + ocupante).',
        '- **1.5i.7o**: `PerdurarSuperestructuraStringSQL64::guardar_parcial`',
        '  implementado. `guardar` pasa a ser wrapper de',
        '  `_guardar_con_filtro($nombre, 0)`. Nuevo privado',
        '  `_guardar_con_filtro($nombre, int $mascara_filtro)`.',
        '  `_crear_chunks_insertar_nodos_con_mascara` y',
        '  `_crear_chunks_insertar_adyacentes_contexto` aceptan el',
        '  filtro. Regla de borrado: con filtro, se borran las',
        '  aristas salientes de los nodos del filtro (idnodo in',
        '  filtro) y los nodos del filtro. El DELETE de',
        '  adyacente_contexto va antes del de nodo_contexto (la',
        '  subquery consulta nodo_contexto). Espejo JS en',
        '  `PerdurarSuperestructuraStringIndexedDB64` (V1.5i.7o).',
        '- **1.5i.7p**: `Controlador::$contextos_cargados` (array)',
        '  + getter público `contextos_cargados()`. Se resetea en',
        '  `cargar()`, se setea en `cargar_parcial()` con los',
        '  contextos pasados. Lo consulta `guardar_ambos` del',
        '  piloto para decidir entre `guardar` y `guardar_parcial`.',
        '  No cambia el comportamiento del framework por sí solo.',
    ],
],

// ------------------------------------------------------------
// prompts/prompt_sistema_scripts.md
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: aprendizajes 20-21',
    'buscar' => [
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
    'reemplazar' => [
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
        '20. **Bloques con anidación profunda de llaves: usar',
        '    firma de la función siguiente como ancla.** Cuando',
        '    el `buscar` termina con varias `}` de cierre en',
        '    distintos niveles de indentación (while dentro de',
        '    if dentro de if, etc.), es fácil errarle a los',
        '    espacios. Caso v78l: el bloque',
        '    `pasajero_tiene_ventas_activas` falló porque las',
        '    llaves de cierre tenían 20 espacios en el archivo',
        '    y yo puse 16. Regla: incluir en el `buscar` y el',
        '    `reemplazar` la firma o el comentario que viene',
        '    justo después del bloque (por ejemplo, el `/**`',
        '    del docblock siguiente). Así el ancla es única y',
        '    no depende de contar espacios.',
        '21. **Verificar el `@version` real antes de proponer un',
        '    bump.** Si una tanda anterior (incluso en la misma',
        '    sesión) ya bumpeó el archivo, el `buscar` del bump',
        '    falla con "bloque no encontrado". Caso v78b: el',
        '    bloque de bump del Controlador buscaba `1.5i.7o`',
        '    pero el archivo estaba en `1.5i.7n`. Regla: cuando',
        '    el archivo no fue tocado en la tanda inmediatamente',
        '    anterior, pedir la versión actual antes de proponer',
        '    el bump, o usar un `buscar` que no dependa del',
        '    número de versión (por ejemplo, solo el `@author`',
        '    y el `class`, que sí son estables).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: bump última actualización',
    'buscar' => [
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
    'reemplazar' => [
        '**Última actualización de este prompt:** v1.5piloto.78m.',
        'Se agregan dos aprendizajes (20-21): usar la firma de la',
        'función siguiente como ancla única en bloques con',
        'anidación profunda de llaves (caso v78l), y verificar el',
        '`@version` real antes de proponer un bump (caso v78b).',
        'El resto del sistema sigue igual. Recordatorio: los',
        'prompts del proyecto PHP (framework, piloto, sistema de',
        'scripts, plan actual) y del plugin',
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