<?php
/**
 * Aplicador de cambios — Documentación de cierre de la sesión v78b-v78s.
 *
 * Actualiza:
 *   - prompts/plan_actual.md (sobrescrito completo)
 *   - prompts/prompt_piloto.md (historial + estado al cierre)
 *   - prompts/prompt_framework_iteradores.md (historial 1.5i.7o-r)
 *   - prompts/prompt_sistema_scripts.md (aprendizajes 22-23)
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

// ============================================================
// plan_actual.md — sobrescritura completa
// ============================================================

[
    'tipo' => 'crear',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual.md — reescritura completa al estado v78s',
    'contenido' => [
        '# Plan actual — Línea de trabajo en curso',
        '',
        'Este archivo documenta la **línea de trabajo activa** del piloto.',
        'Reemplaza a la sección "Discusión actual" del `prompt_piloto.md`,',
        'que queda **congelada como referencia histórica** desde v1.5piloto.76v.',
        '',
        '**Reglas:**',
        '',
        '- Se actualiza al cerrar **cada tanda** (no el prompt del piloto).',
        '- Es lo primero que hay que leer para retomar el trabajo.',
        '- Estado (qué se cerró, decisiones, bugs, aprendizajes) arriba.',
        '- Plan de la línea de tandas (fases y detalle de la fase en curso) abajo.',
        '',
        '---',
        '',
        '## 1. ESTADO ACTUAL',
        '',
        '### 1.1 Última actualización',
        '',
        '**Última actualización de este archivo:** v1.5piloto.78s.',
        '',
        'Cierre de la línea de carga parcial (v78b-v78s). Toda la',
        'infraestructura de contextos está aplicada: SQL64 como',
        'método de persistencia, `guardar_parcial` en PHP,',
        '`Controlador::$contextos_cargados`, `_contextos_para_operacion`',
        'en el Enrutador, adaptación del contexto del solicitante en',
        'los módulos principales (Viajes, Venta, Pasajeros,',
        'Impresión) y reparación topológica.',
        '',
        '**BUG PERSISTENTE ACTIVO (§1.5).** El bug de "el viaje viejo',
        'desaparece cuando el terminal hace una operación" **no está',
        'resuelto**. Los fixes v78r (regla de borrado de aristas) y',
        'v78s (preservar bits de máscara) no alcanzaron. Se sigue',
        'investigando. Ver §1.5 para el diagnóstico completo y las',
        'hipótesis descartadas.',
        '',
        'Próximo paso sugerido: retomar el bug §1.5 con logs de',
        'diagnóstico en el `_guardar_con_filtro` (qué máscaras tenía',
        'cada nodo antes y después de cada guardado parcial del',
        'terminal). Ver §1.5.4.',
        '',
        '### 1.2 Cadena de cierres de la sesión v78b-v78s',
        '',
        '- **v78b** (Etapa 0 carga parcial): `delegar_con_args` en el',
        '  Controlador. `guardar_parcial` con chequeos (grafo parcial',
        '  + nodos desocupados). Docblock del flag `$grafo_parcial`.',
        '  `_contextos_para_operacion` (stub) en el Enrutador.',
        '- **v78c** (Tanda 1b): `validar_token_sesion` y',
        '  `_construir_respuesta_login` usan `us_<nombre>` directo.',
        '  `Controlador::$contextos_cargados` + getter.',
        '  `guardar_ambos` consulta los contextos cargados.',
        '- **v78d**: chequeos defensivos de grafo parcial en',
        '  `Usuario.php` (6 funciones) y `Migraciones/Funciones.php`',
        '  (2 detectores). **Revertidos en v78o.**',
        '- **v78e** (Tanda 1c.1a): `SQL64` como método de',
        '  persistencia. Bloque `?migrar_a_sql64=1`. Corrección de',
        '  la secuencia de migración (bloque movido antes de la',
        '  carga normal).',
        '- **v78f** (Tanda 1c.1b): migración topológica.',
        '  `miscelaneas/migrar_topologia.php` (nuevo):',
        '  `migrar_topologia_admin` (crea `us_Administrador →',
        '  usuarios`) y `migrar_topologia_soporte` (asegura `publico`',
        '  en cada soporte y repunta el enlace `dueno → soporte` al',
        '  `publico`). Cambio en `_asignar_duenos_a_soporte`.',
        '- **v78g** (Tanda 1c.2): carga parcial activa. `index.php`',
        '  decide entre `cargar`/`cargar_parcial`. Bootstrap',
        '  (`aplicacion` + admin) solo en carga total.',
        '  `_contextos_para_operacion` implementado.',
        '  `GrafoCredenciales.php` captura y restaura contextos.',
        '- **v78h** (Tanda 1c.2a): contexto en `ViajeMicros.php`.',
        '  Enrutador: 4 acciones.',
        '- **v78i** (Tanda 1c.2b): contexto en `ViajeOpciones.php`.',
        '  Enrutador: 2 acciones.',
        '- **v78j** (Tanda 1c.2c): contexto en `Viaje.php`.',
        '  Enrutador: 2 acciones.',
        '- **v78k** (Tanda 1c.2d): contexto en `Venta.php`.',
        '  Enrutador: `cancelaciones/listar`.',
        '- **v78l** (Tanda 1c.2e): contexto en `Pasajero.php`',
        '  (9 funciones). Enrutador: 3 acciones.',
        '- **v78m** (Tanda 1c.2f): contexto en `Impresion.php`.',
        '  `obtener_venta_por_id`/`_buscar_venta_por_id` con',
        '  dueño. `_contextos_para_operacion` con excepciones.',
        '- **v78n** (reparación topológica): `miscelaneas/migrar_topologia.php`',
        '  con `reparar_compartidos_dueno()` (agrega enlaces',
        '  `dueño → compartido_con_X` faltantes) y',
        '  `reparar_viajes_dueno()`. Bloque `?reparar_topologia=1`.',
        '- **v78o**: revertir los chequeos defensivos de v78d.',
        '  El admin carga parcial siempre (con `[us_Administrador]`)',
        '  y la topología le trae todos los usuarios por el enlace',
        '  `us_Administrador → usuarios`. Los chequeos bloqueaban',
        '  al admin sin agregar seguridad.',
        '- **v78p**: `listar_duenos` navega directo por',
        '  `us_<soporte> → duenos` cuando el solicitante es soporte.',
        '  Con carga parcial, `usuarios` no está en memoria y',
        '  `listar_usuarios` devolvía vacío.',
        '- **v78q**: `_contextos_para_operacion` carga total para',
        '  las acciones de gestión de usuarios (`dueno/agregar_terminal`,',
        '  `dueno/actualizar_terminal`, `dueno/eliminar_terminal`,',
        '  `administrador/agregar_usuario`, `administrador/actualizar_usuario`,',
        '  `administrador/eliminar_usuario`). Necesitan la raíz',
        '  `usuarios` en memoria.',
        '- **v78r**: (a) fix del framework: `_guardar_con_filtro`',
        '  ahora borra solo aristas cuyos DOS extremos están en el',
        '  filtro. Antes borraba aristas cuyo ORIGEN estaba en el',
        '  filtro y no reinsertaba las que tenían un extremo fuera,',
        '  perdiendo enlaces del dueño. (b) `reparar_empresas_dueno()`',
        '  y `reparar_pasajeros_dueno()` agregados al bloque',
        '  `?reparar_topologia=1`.',
        '- **v78s**: fix del framework: en `_guardar_con_filtro`,',
        '  con filtro != 0, unir las máscaras que el BFS calculó',
        '  con las que el nodo ya tenía en memoria. Nunca se pierde',
        '  un bit preexistente. **NO resuelve el bug §1.5.**',
        '',
        '### 1.3 Decisiones de diseño en vigor',
        '',
        '- **SQL es siempre el método principal.** JSON/XML son respaldo',
        '  manual (no automático desde v74m).',
        '- **Host y credenciales en `config_servidor.php`.** Es el único',
        '  archivo que no se toca al desplegar.',
        '- **Rol `soporte`.** Ve las mismas pestañas que el admin,',
        '  solo sobre sus dueños asignados. Ve el `publico` del dueño',
        '  (nombre, email), no su `privado`.',
        '- **Los prompts viven en el proyecto.** Los tres clásicos',
        '  (framework, piloto, sistema de scripts) + este `plan_actual.md`.',
        '- **Comandos del framework vs de la app.** Framework → en',
        '  `Controlador.php`. App → en `Aplicacion/`, registrados desde',
        '  `index.php` después de los `require_once`.',
        '- **El modelo topológico.** Seguridad por topología: un usuario',
        '  solo alcanza lo que tiene camino desde su raíz. Admin y',
        '  soporte siguen accediendo por código, no por topología.',
        '- **SQL64 como método activo (v78e).** Persiste en las 3 tablas',
        '  nuevas: `nodo_contexto`, `adyacente_contexto`, `contexto`.',
        '  Las tablas viejas (`nodo`, `adyacente`) quedan intactas como',
        '  respaldo de la última migración manual.',
        '- **Carga parcial por contexto (v78g).** Toda operación POST',
        '  con `nombre_solicitante` carga parcial con `[us_<sol>]`.',
        '  Sin solicitante, carga total. Excepciones puntuales:',
        '  `_contextos_para_operacion` devuelve `null` para acciones',
        '  que necesitan la raíz `usuarios` (gestión de usuarios) y',
        '  para informes que usan B3.3c.',
        '- **`us_Administrador → usuarios` (v78f).** El admin carga',
        '  parcial con `[us_Administrador]` pero la topología le trae',
        '  todos los usuarios.',
        '- **`dueno → soporte → publico` (v78f).** El enlace `soporte`',
        '  del dueño apunta al `publico` del soporte, no al nodo real.',
        '  Evita la fuga cruzada entre dueños que comparten un soporte.',
        '',
        '### 1.4 Bugs conocidos',
        '',
        '**BUG PERSISTENTE (ver §1.5).** El bug del viaje viejo que',
        'desaparece desde el punto de vista del dueño tras una operación',
        'del terminal. **No resuelto.** v78r y v78s fueron intentos de',
        'fix que no alcanzaron.',
        '',
        'Sin otros bugs de prioridad alta.',
        '',
        '### 1.5 BUG PERSISTENTE — El viaje viejo desaparece',
        '',
        '#### 1.5.1 Síntoma',
        '',
        'Con las pruebas del plugin:',
        '  1. Se corre la prueba "autocompletado por DNI" (logueado',
        '     como terminal).',
        '  2. Después la prueba "alta de viaje" (logueado como dueño).',
        '  3. El viaje viejo (el principal, "Peregrinación a la Visita',
        '     del Papa León XIV a Luján") **desaparece de la lista del',
        '     dueño**.',
        '  4. El terminal lo sigue viendo (navega por su compartido).',
        '  5. Corriendo la prueba de nuevo, ya no falla: los viajes',
        '     nuevos se agregan normalmente y el viejo sigue sin',
        '     aparecer.',
        '',
        'Patrón: **"primera vez falla, segunda no"**. Después de la',
        'primera operación del terminal, el estado del grafo queda',
        '"estable" (aunque roto).',
        '',
        '#### 1.5.2 Diagnóstico',
        '',
        'Cuando el terminal hace `guardar_parcial([us_<terminal>])`,',
        'el framework en `_guardar_con_filtro`:',
        '',
        '1. Carga parcialmente `[us_<terminal>]`. En memoria **no está',
        '   `us_<dueno>`** (por diseño topológico: el terminal no',
        '   alcanza al nodo del dueño real).',
        '2. Ejecuta `_calcular_y_registrar_contextos`: BFS multi-fuente',
        '   desde los IDs especiales que están en memoria. Como',
        '   `us_<dueno>` no está, su bit **no se propaga**.',
        '3. Guarda con la máscara recalculada. El nodo viaje viejo',
        '   queda con máscara = solo bit del terminal (o sin bit del',
        '   dueño).',
        '4. En la próxima carga parcial del dueño (`[us_<dueno>]`), el',
        '   filtro `(contexto_mascara & bit_dueno) != 0` excluye al',
        '   viaje viejo. No aparece.',
        '',
        '**Por qué "primera vez falla, segunda no":** la primera',
        'operación del terminal es la que pisa el bit del dueño. A',
        'partir de ahí el viaje ya no tiene el bit, y las siguientes',
        'operaciones del terminal no lo tocan.',
        '',
        '**Por qué el terminal sí lo ve:** el compartido tiene su',
        'propio bit del terminal, y la venta referencia al viaje por',
        'id. El nodo viaje está en el grafo del terminal, pero sin',
        'bit del dueño.',
        '',
        '#### 1.5.3 Fixes intentados (ninguno alcanzó)',
        '',
        '- **v78r:** cambió la regla de borrado de aristas en',
        '  `_guardar_con_filtro`: ahora borra solo aristas cuyos DOS',
        '  extremos están en el filtro. Antes borraba las aristas',
        '  cuyo ORIGEN estaba en el filtro sin reinsertar las que',
        '  tenían un extremo fuera. **Aplicado, no resolvió.** El',
        '  bug del viaje viejo es de máscaras, no de aristas.',
        '- **v78s:** unir las máscaras que el BFS calculó con las que',
        '  el nodo ya tenía en memoria. **Aplicado, no resolvió.**',
        '  Hipótesis: la máscara que llega al guardado del terminal',
        '  no tiene el bit del dueño ni siquiera antes de calcular el',
        '  BFS (el nodo se carga sin ese bit). El `unir` sería con',
        '  un valor ya degradado.',
        '',
        '#### 1.5.4 Próximo paso sugerido',
        '',
        'Instrumentar `_guardar_con_filtro` para emitir un log con:',
        '',
        '- El `$mf` de cada llamada.',
        '- Los IDs especiales en memoria (los que el BFS usa como',
        '  fuentes).',
        '- Antes del `unir` de v78s: para cada nodo con id especial',
        '  del viaje viejo, su máscara actual y la que el BFS calculó.',
        '- Después del `unir`: los mismos valores.',
        '',
        'Comparar el estado entre la última carga del dueño (que sí',
        'tenía el bit) y la primera operación del terminal (que lo',
        'pierde). La diferencia mostrará en qué punto exacto se va',
        'el bit.',
        '',
        'Otra hipótesis a chequear: el `_cargar_nodos_con_mascara`',
        'cuando carga **parcial** filtra por máscara. Si un nodo no',
        'tiene el bit del terminal pero SÍ otros bits (por ejemplo,',
        'solo el del dueño), **no se carga** y queda fuera del grafo',
        'del terminal. Si ese nodo está en el camino del BFS hacia',
        'el viaje viejo (por ejemplo, un contenedor intermedio), la',
        'propagación se corta y el viaje queda sin bit. Habría que',
        'verificar que el BFS no se interrumpa por nodos filtrados.',
        '',
        '#### 1.5.5 Impacto actual',
        '',
        '- El plugin de pruebas (autocompletado + alta de viaje)',
        '  reproducen el bug de manera consistente.',
        '- El dueño no ve el viaje viejo después de que un terminal',
        '  hace cualquier operación.',
        '- El terminal sí lo ve (navega por su compartido).',
        '- La reparación `?reparar_topologia=1` recupera el viaje',
        '  temporalmente, pero la próxima operación del terminal lo',
        '  vuelve a romper.',
        '',
        '### 1.6 Pendientes de la línea',
        '',
        '- **BUG §1.5.** Bloqueante funcional. Requiere logs de',
        '  diagnóstico.',
        '- **Espejo JS del fix v78r.** `PerdurarSuperestructuraStringIndexedDB64.js`',
        '  tiene el mismo bug de borrado de aristas. Pendiente.',
        '- **Espejo JS del fix v78s.** Idem: preservar bits de máscara.',
        '  Pendiente.',
        '- **B3.3c** (7 helpers que iteran sobre todos los dueños).',
        '  Postergado hasta que aparezca dolor real o implementemos',
        '  iteradores con ID especial. Ver §1.10.',
        '- **Módulos menores sin contexto:** `Liquidacion.php`,',
        '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Mismo',
        '  patrón que los módulos ya adaptados. Sub-tandas cortas',
        '  cuando aparezcan errores.',
        '- **Chequeos defensivos revertidos.** Si aparecen otros',
        '  casos donde el chequeo ayuda, se reintroduce selectivo.',
        '',
        '### 1.7 Plan de la línea de tandas (fases)',
        '',
        '**Fase A** — Usuarios como IDs especiales `us_<nombre>`.',
        '**Completada** (v76k).',
        '',
        '**Fase B1** — Contenedores `publico`/`privado` en cada',
        'usuario. **Completada** (v76m).',
        '',
        '**Fase B2.1** — Compartidos por terminal creados con',
        'referencias filtradas. **Completada** (v76q).',
        '',
        '**Fase B2.2** — Contexto opcional en Viaje, Venta,',
        'ViajeAsientos, Empresa. **Completada** (v76r-v76u).',
        '',
        '**Fase B2.3** — Árboles paralelos con nombres parametrizados',
        'en `Arbol.php`. **Completada** (v76v-v77o).',
        '',
        '**Fase B2.4** — Mantener los compartidos vivos. **Completada**',
        '(Tanda A, v77q).',
        '',
        '**Fase B3** — Eliminar los accesos viejos. **Cerrada salvo',
        'B3.3c** (7 helpers).',
        '',
        '**Carga parcial (Etapa 1, v78b-v78s)** — Carga y guardado',
        'parciales por contexto. **Aplicada.**',
        '  1a (framework): `guardar_parcial` real + espejo JS.',
        '  1b (framework): `contextos_cargados` + getter.',
        '  1c.1 (migraciones): SQL64 + topología admin/soporte.',
        '  1c.2 (piloto): `_contextos_para_operacion` + adaptación',
        '    del contexto en Viajes, Venta, Pasajeros, Impresión.',
        '  Reparación topológica + fix de regla de borrado +',
        '  preservación de bits.',
        '  **Bug §1.5 sin resolver.**',
        '',
        '**Fase C** (opcional) — Tipos como IDs especiales.',
        '**Pendiente.**',
        '',
        '**Fase D** — Carga parcial como optimización.',
        '**Pendiente.**',
        '',
        '### 1.8 Espejo JS del framework',
        '',
        'El framework PHP y el espejo JS deben ir en sincronía. Esta',
        'sesión tocó:',
        '',
        '- `PerdurarSuperestructuraStringSQL64.php` (PHP): fix',
        '  v78r (doble extremo en borrado de aristas) y v78s',
        '  (preservar bits de máscara).',
        '- **Pendiente:** los mismos fixes en',
        '  `PerdurarSuperestructuraStringIndexedDB64.js`.',
        '',
        'Los fixes del espejo se hacen en la próxima tanda larga del',
        'plugin, junto con las pruebas correspondientes.',
        '',
        '### 1.9 Lecciones de la sesión v78b-v78s',
        '',
        '**Sobre el framework y los IDs:**',
        '',
        '1. **En carga parcial, no todos los IDs especiales están en',
        '   memoria.** El BFS de máscaras parte solo de los que hay.',
        '   Esto es un problema cuando el guardado pisa el estado de',
        '   nodos que ya tenían bits de contextos que no están en',
        '   memoria. Ver §1.5.',
        '2. **Las reparaciones topológicas son necesarias pero no',
        '   suficientes.** Recuperan enlaces faltantes, pero no',
        '   previenen que se vuelvan a perder.',
        '',
        '**Sobre la persistencia:**',
        '',
        '3. **La regla de borrado de aristas importa.** Borrar aristas',
        '   cuyo extremo cruza el filtro pierde información que no se',
        '   puede reconstruir solo desde el subgrafo parcial.',
        '   Preservar aristas que cruzan el borde.',
        '4. **La preservación de bits de máscara es la segunda mitad',
        '   del problema.** No alcanza con preservar aristas; hay que',
        '   preservar también los bits del nodo.',
        '',
        '**Sobre las migraciones:**',
        '',
        '5. **Recargar la base y volver a migrar es un test valioso.**',
        '   Detectar bugs que solo aparecen en la secuencia de',
        '   migración completa. Casos: v78n (compartidos faltantes),',
        '   v78p (listar_duenos para soporte).',
        '6. **Migraciones de datos que reestructuran el grafo deben',
        '   ser idempotentes y re-ejecutables.** El `?reparar_topologia=1`',
        '   cumple.',
        '',
        '**Sobre las pruebas del plugin:**',
        '',
        '7. **El orden de las pruebas importa.** Una prueba puede',
        '   dejar el grafo en un estado que rompe a la siguiente.',
        '   El bug §1.5 apareció justamente por el orden',
        '   autocompletado → alta de viaje.',
        '8. **"Primera vez falla, segunda no"** casi siempre indica',
        '   que un estado se pisó una vez y quedó consistente',
        '   (aunque roto). El diagnóstico tiene que apuntar al',
        '   primer guardado que modifica ese estado.',
        '',
        '### 1.10 Diseño futuro: iteradores con ID especial',
        '',
        'Anotado en v78a. No implementado. Objetivo: cuando el',
        'framework tenga iteradores con ID especial persistente y un',
        'usuario que los "ocupe", los B3.3c se resuelven naturalmente.',
        '',
        'Idea:',
        '',
        '- Iteradores con ID especial (`it_<nombre>`) como raíces de',
        '  subgrafo.',
        '- Un iterador cargado trae su subgrafo alcanzable.',
        '- Un iterador tiene un usuario que lo "ocupa".',
        '- Especialización por tipo: `it_empresas_<dueno>`,',
        '  `it_viajes_<dueno>`, etc.',
        '- El backend decide qué iteradores cargar por operación.',
        '',
        'Detalle completo en `prompt_framework_iteradores.md` §11.5.',
        '',
        '---',
        '',
        '## 2. PLAN DE LA LÍNEA DE TANDAS',
        '',
        '### 2.1 Contexto',
        '',
        'La línea en curso es la **carga parcial por contexto**.',
        'Objetivo: que cada operación cargue solo el subgrafo que',
        'necesita, para bajar el costo de O(grafo completo) a O(subgrafo).',
        'Se apoya en el modelo topológico (Fases A, B1-B3) y en SQL64',
        'del framework.',
        '',
        '### 2.2 Estado por fase',
        '',
        '1. **Modelo topológico (Fases A, B1-B3).** Aplicado.',
        '   Pendiente: B3.3c.',
        '2. **Framework — SQL64.** Aplicado. `guardar_parcial` real,',
        '   `cargar_parcial`, `listar_contextos`, `contextos_cargados`.',
        '   Bug §1.5 sin resolver.',
        '3. **Migraciones del piloto.** Aplicadas. SQL64 activo,',
        '   topología admin/soporte, carga parcial en módulos',
        '   principales.',
        '4. **Espejo JS.** Pendiente de los fixes v78r/v78s.',
        '5. **BUG §1.5.** Bloqueante.',
        '',
        '### 2.3 Próximos pasos sugeridos',
        '',
        '1. **Retomar el bug §1.5 con logs de diagnóstico** en',
        '   `_guardar_con_filtro`. Ver §1.5.4.',
        '2. **Espejar v78r/v78s en el framework JS.**',
        '3. **Adaptar los 4 módulos menores** (Liquidacion, Rendicion,',
        '   Empresa, Vehiculo) con el mismo patrón de contexto.',
        '4. **B3.3c** cuando aparezca dolor real o cuando lleguen',
        '   los iteradores con ID especial.',
        '',
        '---',
        '',
        '**FIN DEL PLAN ACTUAL**',
    ],
],

// ============================================================
// prompt_piloto.md
// ============================================================

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: bump estado al cierre a v78s',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.78a',
        '(framework 1.5i.7n). Todo funcional. La línea B del',
        'modelo topológico quedó cerrada salvo B3.3c',
        '(postergada hasta la carga parcial). Próximo: Etapa 0',
        'del plan de carga parcial (`plan_actual.md` §1.10).',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.78s',
        '(framework 1.5i.7r). La línea de carga parcial quedó',
        'aplicada en los módulos principales (Viajes, Venta,',
        'Pasajeros, Impresión). SQL64 activo, topología',
        'admin/soporte, reparación topológica disponible.',
        '',
        '**BUG PERSISTENTE.** El viaje viejo del dueño desaparece',
        'cuando un terminal hace una operación de guardado',
        '(reproducible con el plugin: autocompletado → alta de',
        'viaje). Los fixes v78r (regla de borrado de aristas) y',
        'v78s (preservar bits de máscara) no alcanzaron. Detalle',
        'completo, hipótesis y próximo paso de diagnóstico en',
        '`prompts/plan_actual.md` §1.5.',
        '',
        'Próximo paso sugerido: retomar el bug con logs de',
        'diagnóstico en `_guardar_con_filtro`. Después: espejar',
        'v78r/v78s en el framework JS y adaptar los módulos',
        'menores (Liquidacion, Rendicion, Empresa, Vehiculo).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_piloto.md',
    'descripcion' => 'prompt_piloto: historial v78b-v78s',
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
        '- **v78b-v78s** (línea de carga parcial): ver',
        '  `prompts/plan_actual.md` §1.2 para la cadena completa',
        '  (Etapa 0, 1b, 1c.1a/b, 1c.2a-f, reparación topológica,',
        '  fixes del framework v78r/v78s). El prompt del piloto',
        '  no se actualiza por cada tanda de la línea; la fuente',
        '  viva es `plan_actual.md`.',
    ],
],

// ============================================================
// prompt_framework_iteradores.md
// ============================================================

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_framework_iteradores.md',
    'descripcion' => 'framework: historial 1.5i.7q y 1.5i.7r',
    'buscar' => [
        '- **1.5i.7p**: `Controlador::$contextos_cargados` (array)',
        '  + getter público `contextos_cargados()`. Se resetea en',
        '  `cargar()`, se setea en `cargar_parcial()` con los',
        '  contextos pasados. Lo consulta `guardar_ambos` del',
        '  piloto para decidir entre `guardar` y `guardar_parcial`.',
        '  No cambia el comportamiento del framework por sí solo.',
    ],
    'reemplazar' => [
        '- **1.5i.7p**: `Controlador::$contextos_cargados` (array)',
        '  + getter público `contextos_cargados()`. Se resetea en',
        '  `cargar()`, se setea en `cargar_parcial()` con los',
        '  contextos pasados. Lo consulta `guardar_ambos` del',
        '  piloto para decidir entre `guardar` y `guardar_parcial`.',
        '  No cambia el comportamiento del framework por sí solo.',
        '- **1.5i.7q**: `PerdurarSuperestructuraStringSQL64::_guardar_con_filtro`',
        '  cambia la regla de borrado de aristas. Antes: borraba',
        '  aristas cuyo ORIGEN estaba en el filtro, y solo reinsertaba',
        '  las que tenían ambos extremos dentro. Resultado: las aristas',
        '  que cruzaban el borde (dueño → subárbol que quedó sin bit)',
        '  se borraban sin reinsertarse. Ahora: borra solo aristas',
        '  cuyos DOS extremos están en el filtro. Las que cruzan el',
        '  borde se conservan tal cual. Aplicado.',
        '- **1.5i.7r**: mismo `_guardar_con_filtro`, con filtro != 0,',
        '  une las máscaras que el BFS calculó con las que el nodo',
        '  ya tenía en memoria. Nunca se pierde un bit preexistente.',
        '  Aplicado, **no alcanzó para resolver el bug del viaje',
        '  viejo** (ver `plan_actual.md` §1.5).',
        '',
        '**BUG PENDIENTE (framework).** El bug del viaje viejo que',
        'desaparece tras una operación del terminal (§1.5 del',
        '`plan_actual.md`) afecta a `_guardar_con_filtro` y no está',
        'resuelto. v78r y v78s fueron intentos que no alcanzaron.',
        'Próximo paso: instrumentar `_guardar_con_filtro` con logs',
        'de las máscaras antes/después del `unir` para identificar',
        'en qué punto se pierde el bit del dueño.',
        '',
        '**Espejo JS pendiente.** Los fixes v78r y v78s **no están',
        'en el espejo JS**. `PerdurarSuperestructuraStringIndexedDB64.js`',
        'tiene el bug de borrado de aristas original. Se espeja en',
        'una próxima tanda del plugin.',
    ],
],

// ============================================================
// prompt_sistema_scripts.md
// ============================================================

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: aprendizajes 22-23',
    'buscar' => [
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
    'reemplazar' => [
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
        '22. **Cuando un bug persiste después de un fix, anotar el',
        '    intento fallido antes de seguir.** Caso v78r/v78s:',
        '    dos intentos de fix (regla de borrado de aristas +',
        '    preservar bits de máscara) que no resolvieron el bug',
        '    del viaje viejo que desaparece. Cada intento tiene',
        '    que quedar en `plan_actual.md` con: qué se cambió,',
        '    por qué, y por qué no alcanzó. Así el próximo que',
        '    retome el bug no repite el mismo intento a ciegas.',
        '    Regla: un bug que se resiste a dos intentos de fix',
        '    necesita instrumentación (logs), no otro fix a ojo.',
        '23. **Los bugs que aparecen por interacción entre pruebas',
        '    del plugin son los más difíciles.** El bug §1.5 del',
        '    `plan_actual.md` apareció solo cuando se corre',
        '    "autocompletado" seguido de "alta de viaje". Cada',
        '    prueba en aislamiento pasaba. Regla: si un bug',
        '    aparece en combinación, anotar explícitamente el',
        '    orden de las pruebas que lo reproducen. Es el dato',
        '    más valioso para diagnosticarlo.',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/prompt_sistema_scripts.md',
    'descripcion' => 'sistema_scripts: bump última actualización',
    'buscar' => [
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
    'reemplazar' => [
        '**Última actualización de este prompt:** v1.5piloto.78s.',
        'Se agregan dos aprendizajes (22-23): anotar intentos de',
        'fix fallidos antes de seguir (caso v78r/v78s) y anotar el',
        'orden de las pruebas cuando un bug solo aparece por',
        'interacción entre ellas (bug §1.5 del `plan_actual.md`).',
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