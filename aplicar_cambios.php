<?php
/**
 * Aplicador — Tanda U: reescritura del plan_actual.md + cierre documental.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
// Sin bumps automáticos: solo se tocan .md.
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) prompt_piloto.md §2 CSS: + estilos-grafo.css.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§2 CSS: + estilos-grafo.css',
        'buscar' => [
            '**CSS (dividido en 5 archivos):**',
            '- `estilos.css`: base.',
            '- `estilos-vehiculos.css`: croquis, editor de asientos, foto, panel datos',
            '  del vehículo.',
            '- `estilos-viajes.css`: tarjetas de viaje, detalle, micros, terminales,',
            '  panel de asientos, pasaje, modales, subsección DJ.',
            '- `estilos-ventas.css`: tarjetas de venta, badges, cuponera, cancelación,',
            '  saldos, sub-lista de cupones.',
            '- `estilos-rendiciones.css`: tabla de rendiciones, badges, detalle,',
            '  ajustes.',
        ],
        'reemplazar' => [
            '**CSS (dividido en 6 archivos):**',
            '- `estilos.css`: base.',
            '- `estilos-vehiculos.css`: croquis, editor de asientos, foto, panel datos',
            '  del vehículo.',
            '- `estilos-viajes.css`: tarjetas de viaje, detalle, micros, terminales,',
            '  panel de asientos, pasaje, modales, subsección DJ.',
            '- `estilos-ventas.css`: tarjetas de venta, badges, cuponera, cancelación,',
            '  saldos, sub-lista de cupones.',
            '- `estilos-rendiciones.css`: tabla de rendiciones, badges, detalle,',
            '  ajustes.',
            '- `estilos-grafo.css`: pestaña Grafo, sub-pestaña Visual. Sub-pestañas,',
            '  controles, nodos, enlaces, panel lateral, tres modos de tamaño',
            '  (ampliado, teatro, fullscreen).',
        ],
    ],

    // ============================================================
    // B) prompt_piloto.md §2 Aplicacion/: + grafo_visual.js.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§2 Aplicacion/: + grafo_visual.js',
        'buscar' => [
            '- `ventas.js`, `pasajeros.js`, `rendiciones.js`, `liquidaciones.js`.',
            '- `grafo.js`: pestaña Grafo (visualizador de la superestructura).',
        ],
        'reemplazar' => [
            '- `ventas.js`, `pasajeros.js`, `rendiciones.js`, `liquidaciones.js`.',
            '- `grafo.js`: pestaña Grafo, sub-pestaña Tabla. Resumen, listado',
            '  paginado con filtros, modal de detalle, raíces, migraciones,',
            '  botón "Eliminar nodos basura".',
            '- `grafo_visual.js`: pestaña Grafo, sub-pestaña Visual. SVG puro',
            '  (sin librerías), layouts Grilla y Forzar, pan/zoom, drag con',
            '  física, panel lateral, búsqueda, tres modos de tamaño',
            '  (ampliado, teatro, fullscreen), etiquetas y colores de enlace.',
        ],
    ],

    // ============================================================
    // C) prompt_piloto.md §3.4: reescritura.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§3.4: reescritura (dos sub-pestañas + visual)',
        'buscar' => [
            '### 3.4 Pestaña Grafo (visualizador de la superestructura)',
            '',
            'Implementada en v1.5piloto.74p (Fase 1 del plan de optimización,',
            'ver §8.6). Visible solo para **admin y soporte**.',
            '',
            '**Originalmente de solo lectura; desde v76g incluye una',
            'acción de escritura.** El botón "Eliminar nodos basura"',
            'borra todos los nodos no alcanzables desde las raíces',
            '(IDs especiales) del grafo de la aplicación. **No está',
            'restringido a modo pruebas**: se usa en producción, donde',
            'más se acumulan los huérfanos. El chequeo es admin/soporte,',
            'heredado del módulo `grafo` del enrutador. Antes de',
            'eliminar, muestra un modal de confirmación con la cuenta',
            'total y una vista previa de los primeros 20 nodos',
            'huérfanos. El backend usa el comando',
            '`grafo:eliminar_huerfanos`, que desenlaza las salientes',
            'entre huérfanos y después los elimina uno a uno.',
            '',
            '**Qué muestra:**',
            '',
            '- **Resumen:** total de nodos, alcanzables desde las raíces',
            '  (`usuarios`, `sesiones` y demás IDs especiales), huérfanos, y',
            '  top 20 de nodos por referencias entrantes.',
            '- **Tabla:** listado paginado con filtros por estado',
            '  (`todos` / `huerfanos` / `alcanzables`), por nombre de enlace y',
            '  por texto en el dato. Columnas: ID, tipo inferido, dato,',
            '  cantidad de enlaces salientes, cantidad de referencias',
            '  entrantes.',
            '- **Detalle (modal apilado):** click en una fila → enlaces',
            '  salientes, referencias entrantes, y navegación por los nodos',
            '  relacionados.',
            '',
            '**Cómo funciona:**',
            '',
            '- El frontend (`Aplicacion/grafo.js`) llama a tres subacciones',
            '  del enrutador: `grafo/resumen`, `grafo/listar`, `grafo/nodo`.',
            '- El enrutador (módulo `grafo`, chequeo admin/soporte) invoca',
            '  los comandos `grafo:resumen`, `grafo:listar`, `grafo:nodo`',
            '  del `Controlador` a través de `Controlador::ejecutar_comando()`.',
            '- Los comandos están definidos en',
            '  `Controlador::registrar_comandos_grafo()` (privado). Usan el',
            '  token interno sin exponerlo. **No usan el motor**: se ejecutan',
            '  de a uno.',
            '- Helpers privados en `Controlador`: `_grafo_cargar_estructura`,',
            '  `_grafo_bfs_desde_raices`, `_grafo_inferir_tipo`.',
            '',
            '**Limitaciones conocidas:**',
            '',
            '- El comando `grafo:nodo` recorre todo el grafo para encontrar',
            '  referencias entrantes (O(N) por click).',
            '- `grafo:listar` carga todo el grafo en memoria y filtra en PHP.',
            '- `_grafo_inferir_tipo` es heurística; puede devolver `?` para',
            '  nodos que no cumplen ningún patrón conocido.',
            '',
            'Estas limitaciones son aceptables para Fase 1 (diagnóstico). Se',
            'pueden optimizar en Fase 3.',
        ],
        'reemplazar' => [
            '### 3.4 Pestaña Grafo (visualizador de la superestructura)',
            '',
            'Implementada en v1.5piloto.74p (Fase 1 del plan de optimización,',
            'ver §8.6). Visible solo para **admin y soporte**.',
            '',
            '**Dos sub-pestañas.** Desde v80n la pestaña Grafo tiene dos',
            'vistas: **Tabla** y **Visual**. La pestaña arranca directamente',
            'en **Visual** desde v81d. Cada sub-pestaña preserva su estado',
            'al cambiar entre ellas.',
            '',
            '**Acción de escritura.** El botón "Eliminar nodos basura" borra',
            'todos los nodos no alcanzables desde las raíces (IDs',
            'especiales) del grafo de la aplicación. **No está restringido',
            'a modo pruebas**: se usa en producción, donde más se acumulan',
            'los huérfanos. El chequeo es admin/soporte, heredado del',
            'módulo `grafo` del enrutador. Antes de eliminar, muestra un',
            'modal de confirmación con la cuenta total y una vista previa',
            'de los primeros 20 nodos huérfanos. El backend usa el comando',
            '`grafo:eliminar_huerfanos`. Después de eliminar, si la',
            'sub-pestaña Visual está activa, se recarga el vecindario.',
            '',
            '#### Sub-pestaña Tabla',
            '',
            'Original de v74p. Vista "clásica" del grafo.',
            '',
            '- **Resumen:** total de nodos, alcanzables, huérfanos, top 20',
            '  por referencias entrantes.',
            '- **Tabla:** paginada, con filtros por estado (`todos` /',
            '  `huerfanos` / `alcanzables`), por nombre de enlace y por',
            '  texto en el dato. Columnas: ID, tipo inferido, dato, enlaces',
            '  salientes, referencias entrantes.',
            '- **Detalle (modal apilado):** click en una fila → enlaces',
            '  salientes, referencias entrantes, y navegación por los',
            '  nodos relacionados.',
            '',
            '#### Sub-pestaña Visual',
            '',
            'Nueva desde v80n. SVG puro, sin librerías.',
            '',
            '- **Carga inicial:** las raíces especiales (`usuarios`,',
            '  `sesiones`, `aplicacion`, etc.) y un nivel de adyacentes,',
            '  vía el comando `grafo:vecindario`. Selector de `max_nodos`',
            '  (100/250/500/1000/2000, default 500).',
            '- **Nodos:** rectángulos redondeados de 120×64, con tres',
            '  líneas: tipo (arriba), dato (medio, con `ellipsis`),',
            '  `#id` (abajo). Los IDs van siempre con `#` adelante. Los',
            '  IDs numéricos son efímeros pero se muestran porque sirven',
            '  para inspeccionar la estructura. Las raíces especiales',
            '  tienen borde naranja. Los compartidos (`_es_compartido`)',
            '  tienen borde punteado naranja.',
            '- **Inferencia de tipo:** si el backend devuelve `?`, el',
            '  frontend mira los enlaces entrantes. Un solo enlace → ese',
            '  nombre es el tipo. Varios → el más repetido. Empate → `?`.',
            '  Se recalcula al expandir.',
            '- **Enlaces:** Bézier con punta de flecha. Etiquetas con el',
            '  nombre del enlace. El color va por el tipo del nodo',
            '  **origen** (las flechas salen con el color del nodo que',
            '  las emite). Los compartidos van punteados naranja. Click',
            '  en un enlace → pan animado hasta centrar el nodo destino.',
            '- **Layouts:** Grilla (cuadrícula por BFS desde las raíces)',
            '  y Forzar (force-directed simplificado). Toggle en la barra',
            '  de controles. **Forzar** es el default desde v81d.',
            '- **Pan/zoom:** arrastrar el fondo = pan. Rueda del mouse =',
            '  zoom centrado en el cursor. La grilla de fondo se expande',
            '  automáticamente con el viewBox.',
            '- **Drag de nodos:** arrastrar un nodo lo sigue al cursor y',
            '  empuja/atrae a los vecinos (física amortiguada). Al soltar,',
            '  todo queda en la última posición. El layout no se recalcula',
            '  solo: "Reacomodar" o cambiar el toggle lo recalculan.',
            '- **Búsqueda:** atenúa los nodos que no matchean (por ID o',
            '  dato). Enter recorre los matches con zoom animado.',
            '- **Panel lateral:** click en un nodo → panel derecho con',
            '  ID, dato, tipo, enlaces salientes, referencias entrantes.',
            '  Los vecinos son links clickeables (reabren el panel en ese',
            '  nodo y lo centran).',
            '- **Doble click en un nodo:** expande sus vecinos (los',
            '  agrega al grafo local, cap de 2000 nodos) y centra en el',
            '  nodo tocado.',
            '- **Tres modos de tamaño:** normal (dentro del panel),',
            '  ampliado (`□`, ocupa todo el viewport con',
            '  `position: fixed; inset: 0`), teatro (`⛶`, ocupa casi',
            '  todo con margen de 10px y sombra), fullscreen (`⤢`,',
            '  Fullscreen API sobre el contenedor entero). Esc cierra',
            '  ampliado y teatro (si no está en fullscreen).',
            '',
            '**Cómo funciona:**',
            '',
            '- El frontend está partido en dos archivos:',
            '  - `Aplicacion/grafo.js`: sub-pestaña Tabla + resumen +',
            '    raíces + migraciones. Llama a `grafo/resumen`,',
            '    `grafo/listar`, `grafo/nodo`, `grafo/eliminar_huerfanos`,',
            '    `grafo/raices`, `grafo/migraciones_*`.',
            '  - `Aplicacion/grafo_visual.js`: sub-pestaña Visual. Llama',
            '    a `grafo/vecindario` (carga inicial) y `grafo/nodo`',
            '    (detalle de un nodo y expansión).',
            '- El enrutador (módulo `grafo`, chequeo admin/soporte)',
            '  invoca los comandos del `Controlador` vía',
            '  `Controlador::ejecutar_comando()`.',
            '- Los comandos están definidos en',
            '  `Controlador::registrar_comandos_grafo()` (privado). Usan',
            '  el token interno sin exponerlo. **No usan el motor**: se',
            '  ejecutan de a uno.',
            '- Helpers privados en `Controlador`: `_grafo_cargar_estructura`,',
            '  `_grafo_bfs_desde_raices`, `_grafo_inferir_tipo`.',
            '',
            '**Limitaciones conocidas:**',
            '',
            '- `grafo:nodo` recorre todo el grafo para encontrar',
            '  referencias entrantes (O(N) por click).',
            '- `grafo:listar` carga todo el grafo en memoria y filtra en',
            '  PHP.',
            '- `grafo:vecindario` también carga todo el grafo en memoria',
            '  (BFS sobre la estructura completa), pero el resultado que',
            '  devuelve está acotado por `niveles` y `max_nodos`.',
            '- `_grafo_inferir_tipo` es heurística; devuelve `?` cuando no',
            '  puede inferir. El frontend completa con inferencia por',
            '  enlace entrante.',
        ],
    ],

    // ============================================================
    // D) prompt_piloto.md §5.14: + vecindario y raices.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§5.14: + vecindario y raices',
        'buscar' => [
            '- Módulo `grafo` (admin y soporte): `grafo/resumen`,',
            '  `grafo/listar`, `grafo/nodo`, `grafo/eliminar_huerfanos`.',
            '  Los primeros tres invocan comandos del `Controlador`',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`). El',
            '  cuarto (agregado en v76g) invoca el comando',
            '  `grafo:eliminar_huerfanos` y guarda el grafo con',
            '  `guardar_ambos`. **No lleva chequeo de `es_pruebas`:',
            '  se usa en producción**, por decisión consciente (la',
            '  limpieza es mantenimiento, no prueba). El control es',
            '  admin/soporte. Ver §3.4.',
        ],
        'reemplazar' => [
            '- Módulo `grafo` (admin y soporte): `grafo/resumen`,',
            '  `grafo/listar`, `grafo/nodo`, `grafo/eliminar_huerfanos`,',
            '  `grafo/raices`, `grafo/vecindario`. Los seis invocan',
            '  comandos del `Controlador` (`grafo:resumen`,',
            '  `grafo:listar`, `grafo:nodo`, `grafo:eliminar_huerfanos`,',
            '  `grafo:raices`, `grafo:vecindario`). `grafo:nodo` desde',
            '  v80p devuelve `tipo`, `tipo_destino` y `tipo_origen`',
            '  (antes solo el detalle plano). `grafo:vecindario` desde',
            '  v80n devuelve los nodos alcanzables desde las raíces',
            '  hasta N niveles más los enlaces entre ellos; es la',
            '  carga inicial del visualizador gráfico.',
            '  `grafo:eliminar_huerfanos` **no lleva chequeo de',
            '  `es_pruebas`: se usa en producción**, por decisión',
            '  consciente (la limpieza es mantenimiento, no prueba).',
            '  El control es admin/soporte. Ver §3.4.',
        ],
    ],

    // ============================================================
    // E) prompt_piloto.md §7: entrada nueva al historial.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§7 Historial: + v80n-v81g',
        'buscar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v26-v67**: agencia de viajes madura.',
        ],
        'reemplazar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v80n-v81g** (línea del visualizador de grafo): ver',
            '  §3.4 y `prompts/plan_actual.md` §2. Nueva sub-pestaña',
            '  Visual con SVG puro. Layouts Grilla y Forzar, pan/zoom,',
            '  drag con física, panel lateral, búsqueda, tres modos',
            '  de tamaño (ampliado, teatro, fullscreen), etiquetas y',
            '  colores de enlace, inferencia de tipos por enlace',
            '  entrante, grilla infinita. **Archivos nuevos:**',
            '  `Aplicacion/grafo_visual.js` y `estilos-grafo.css`.',
            '  **Comando nuevo:** `grafo:vecindario`. **Subacción',
            '  nueva:** `grafo/vecindario`. Bumps del `Controlador`',
            '  hasta `1.5i.7s`.',
            '- **v26-v67**: agencia de viajes madura.',
        ],
    ],

    // ============================================================
    // F) plan_actual.md: reescritura completa.
    // ============================================================
    [
        'tipo' => 'crear',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'Reescritura del plan_actual.md',
        'contenido' => [
            '# Plan actual — Ubicación del proyecto',
            '',
            'Este archivo ubica al agente en el **punto exacto** del',
            'proyecto. No duplica información de los otros prompts:',
            'para el detalle, referenciar.',
            '',
            '**Reglas:**',
            '',
            '- Se actualiza al cerrar **cada tanda**.',
            '- Es lo primero que hay que leer para retomar el trabajo.',
            '- El detalle de cada tema vive en su prompt:',
            '  - Estructura de nodos, módulos, roles y frontend del',
            '    piloto: `prompts/prompt_piloto.md`.',
            '  - Framework Iteradores (clases, persistencia):',
            '    `prompts/prompt_framework_iteradores.md`.',
            '  - Seguridad topológica, contextos, compartidos:',
            '    `prompts/prompt_seguridad.md`.',
            '  - Sistema de scripts de aplicación de cambios:',
            '    `prompts/prompt_sistema_scripts_v2.md`.',
            '  - Plugin de pruebas (Chrome MV3):',
            '    `iteradoresJS/prompts/prompt_plugin_piloto.md` y',
            '    `estructura_plugin.md`.',
            '- Las lecciones aprendidas se archivan en cada prompt según',
            '  su tema. Este archivo no las acumula.',
            '',
            '---',
            '',
            '## 1. ESTADO ACTUAL',
            '',
            '**Última actualización:** v1.5piloto.81g (cierre del arco',
            'del visualizador de grafo).',
            '',
            '**Versiones:**',
            '',
            '- **Piloto:** `1.5piloto.81g`.',
            '- **Framework PHP:** `1.5i.8c` (SQL64). El archivo',
            '  `Controlador/Controlador.php` está en `1.5i.7s` por los',
            '  comandos del visualizador.',
            '- **Framework JS:** `V1.5i.8c` (IndexedDB64).',
            '- **Plugin de pruebas:** `v1.5plugin.8f`.',
            '',
            '**Dónde estamos parados:**',
            '',
            'El piloto tiene el modelo topológico completo (usuarios,',
            'compartidos, contextos, carga parcial) y el visualizador de',
            'grafo cerrado. La seguridad sigue emergiendo de la topología',
            'del grafo: el bitmask lo propaga el framework, no la app.',
            'No hay bugs abiertos de prioridad alta.',
            '',
            'La pestaña Grafo tiene dos sub-pestañas (Tabla y Visual), con',
            'Visual como default. La sub-pestaña Visual es un SVG puro con',
            'layout Forzar por defecto, drag con física, panel lateral y',
            'tres modos de tamaño.',
            '',
            'El plugin de pruebas tiene la UI reorganizada por pestañas',
            'temáticas y la persistencia de corridas en su propio grafo',
            '(estructura `pruebas → <id_prueba> → cadena de corridas`).',
            '66 pruebas en verde al último cierre registrado.',
            '',
            '---',
            '',
            '## 2. ARCOS CERRADOS RECIENTES',
            '',
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **Visualizador de grafo (v80n-v81g).** Nueva sub-pestaña',
            '  Visual con SVG puro. Detalle en `prompt_piloto.md` §3.4.',
            '  **Pendientes abiertos:** Fase 5 (persistencia con',
            '  `iteradoresJS` en cliente), colores para tipos inferidos',
            '  por enlace, tamaño del patrón de la grilla adaptativo al',
            '  zoom (opcional).',
            '- **UI del plugin (v1.5plugin.7p-7x).** Pestañas temáticas,',
            '  grupos por rol, botones contextuales de limpieza. Detalle',
            '  en `iteradoresJS/prompts/prompt_plugin_piloto.md` §9.',
            '- **Grafo del plugin (v1.5plugin.8b-8f).** Rediseño del',
            '  grafo de corridas: `pruebas → <id_prueba> → cadena`.',
            '  Estado "corriendo" como autoenlace. Mensajes nuevos',
            '  (`estado_pruebas`, `corrida_iniciada`, `corrida_terminada`).',
            '  Detalle en `iteradoresJS/prompts/estructura_plugin.md` y',
            '  en el prompt del plugin §9.',
            '- **Fase 2c (v80a-80e).** Migración del plugin a `_post` y',
            '  `post_accion`. Detalle en el prompt del plugin §9.',
            '- **Carga parcial (v78b-v79a).** Aplicada. La app ya usa',
            '  `cargar_parcial`/`guardar_parcial` en los módulos',
            '  principales. Detalle en `prompt_seguridad.md` §2-5.',
            '- **Modelo topológico (Fases A, B1-B3).** Aplicado. Queda',
            '  pendiente solo B3.3c. Detalle en `prompt_seguridad.md`',
            '  §5 y `prompt_piloto.md` §8.7.',
            '- **Framework SQL64 (1.5i.7k-8c).** Activo. Bugs §1.5,',
            '  §1.5.7, §1.5.8, §1.5.10, §1.5.15 y §1.5.16 resueltos.',
            '  Detalle en `prompt_framework_iteradores.md` §11.4 y en',
            '  `prompt_seguridad.md` §8.',
            '',
            '---',
            '',
            '## 3. DECISIONES DE DISEÑO EN VIGOR',
            '',
            'Solo las que aplican a cualquier tanda nueva. Las decisiones',
            'de cada módulo viven en su prompt.',
            '',
            '- **SQL64 es el método de persistencia activo.** Las tres',
            '  tablas nuevas (`nodo_contexto`, `adyacente_contexto`,',
            '  `contexto`) con esquema `id_fila` / `id_especial`.',
            '- **La seguridad emerge de la topología.** El bitmask lo',
            '  propaga el framework, no la app. La app nunca asigna bits',
            '  a mano.',
            '- **Carga parcial activa.** Toda operación POST con',
            '  `nombre_solicitante` carga parcial con `[us_<sol>]`.',
            '  Excepciones puntuales: gestión de usuarios, informes que',
            '  usan los 7 helpers de B3.3c.',
            '- **Soft delete coordinado para nodos multi-contexto.** Un',
            '  nodo con bits de varios contextos no puede ser destruido',
            '  por uno solo. Se marca con autoenlace (`cancelada`) y lo',
            '  destruye el contexto que tenga la vista completa.',
            '- **Inserción diferida con buzones.** Un contexto que no',
            '  puede insertar en el árbol del otro deja un aviso en un',
            '  buzón del compartido. El otro lo drena en su próxima',
            '  carga.',
            '- **Comandos del framework vs de la app.** Los del framework',
            '  (`grafo:*`, `comunicacion:*`, etc.) van en el',
            '  `Controlador`. Los de la app van en `Aplicacion/`,',
            '  registrados desde `index.php`.',
            '- **Espejo PHP ↔ JS.** Cualquier cambio al framework PHP se',
            '  refleja en JS en la misma tanda, con dos scripts y dos',
            '  commits.',
            '- **Cada cambio al piloto lleva su espejo de pruebas en el',
            '  plugin.** Salvo excepción justificada (estilos, refactor',
            '  sin cambio de comportamiento, herramientas de admin).',
            '',
            '---',
            '',
            '## 4. PENDIENTES CONSOLIDADOS',
            '',
            'Agrupados por naturaleza. Prioridad estimada entre',
            'paréntesis.',
            '',
            '### 4.1. Piloto — calidad y consistencia (media)',
            '',
            '- **Adaptar los 4 módulos menores al contexto del',
            '  solicitante:** `Liquidacion.php`, `Rendicion.php`,',
            '  `Empresa.php`, `Vehiculo.php`. Tanda repetitiva (misma',
            '  técnica que Viaje/Venta).',
            '- **Auditar estados colgados:** reservas sin pasajero, DJ a',
            '  medias, edición de viaje a mitad. Requiere relevamiento',
            '  primero.',
            '- **Auditar estructuras nuevas** con la regla de detección',
            '  de `prompt_seguridad.md` §5.9. Preventivo.',
            '- **Deuda de seguridad en impresión.** Las URLs',
            '  `?imprimir=1&tipo=...` no validan sesión ni permisos.',
            '  Cualquiera con la URL ve los datos. Preexistente.',
            '',
            '### 4.2. Plugin — mejoras (baja)',
            '',
            '- **Persistencia de la pestaña activa del plugin.**',
            '  Migrar de `localStorage` a IndexedDB. Anotado en el',
            '  código como `ANOTADO`.',
            '- **Pruebas 37 y 38 sin `try/finally`.** Si el flujo falla',
            '  a mitad, la empresa/vehículo de setup queda colgada.',
            '  Los limpiadores la agarran, pero la prueba debería',
            '  limpiar por su cuenta.',
            '- **Centralizar prefijos de prueba.** Hoy cada limpiador',
            '  tiene su lista hardcodeada. Una constante compartida, o',
            '  que las pruebas registren su prefijo.',
            '- **Secciones vacías del plugin:** Rendiciones,',
            '  Liquidaciones. Esperan pruebas nuevas.',
            '- **Linting del payload del plugin:** verificar que',
            '  ninguna llamada a `_post` a acciones que requieren',
            '  `nombre_dueno` salga sin él.',
            '',
            '### 4.3. Framework Iteradores (baja, tanda propia)',
            '',
            'Van todos juntos en una sesión del framework.',
            '',
            '- **Dejar el Nodo limpio.** Mover `contexto_mascara` a la',
            '  capa de persistencia antes de agregar más métodos de',
            '  indexación (256 bits, producto de primos). Bloqueante de',
            '  la Fase 5. Ver `prompt_framework_iteradores.md` §11.4.',
            '- **Fase 3: JSON64 / XML64** con `guardar_parcial`.',
            '- **Fase 4: `guardar_parcial` real por delta** (no merge',
            '  en memoria).',
            '- **Fase 5: 256 contextos / producto de primos** (si hace',
            '  falta).',
            '- **Iteradores con ID especial + ocupante** como raíces de',
            '  subgrafo. Resolvería los 7 helpers de B3.3c. Ver',
            '  `prompt_framework_iteradores.md` §11.5.',
            '',
            '### 4.4. Visualizador de grafo (baja)',
            '',
            '- **Fase 5: persistencia.** Guardar posiciones, zoom y',
            '  layout elegido. Va cuando integremos `iteradoresJS` en el',
            '  cliente del piloto. Es una decisión de arquitectura, no',
            '  del visualizador.',
            '- **Colores para tipos inferidos por enlace.** Los tipos',
            '  del backend ya tienen color. Los inferidos por enlace',
            '  (`usuarios`, `empresas`, `viajes`) caen a gris. Agregar',
            '  paleta cuando se use con datos reales.',
            '- **Tamaño del patrón de la grilla adaptativo al zoom**',
            '  (opcional). Hoy el patrón mide 40×40 user units fijo.',
            '',
            '---',
            '',
            '## 5. CAMINO A LARGO PLAZO',
            '',
            'Planes de mediano/largo plazo, no implementados. No bloquean',
            'el trabajo actual.',
            '',
            '- **B3.3c — 7 helpers que iteran sobre todos los dueños',
            '  buscando por id.** Postergado hasta que aparezca dolor',
            '  real o hasta que lleguen los iteradores con ID especial',
            '  (ver §4.3). La lista completa está en',
            '  `prompt_seguridad.md` §8.6.',
            '- **Iteradores con ID especial + ocupante.** Cuando el',
            '  framework los implemente, los B3.3c se resuelven',
            '  naturalmente. Ver `prompt_framework_iteradores.md`',
            '  §11.5.',
            '- **Framework — dejar el Nodo limpio.** Antes de agregar',
            '  más métodos de indexación de contextos.',
            '- **Framework — JSON64 / XML64** con `guardar_parcial`.',
            '- **Framework — `guardar_parcial` real por delta.**',
            '- **Diversificación del piloto por tipo de aplicación.**',
            '  Próximo tipo: `"tienda"` (catálogo de productos).',
            '  Requiere nodo `tipos_de_aplicacion` como raíz,',
            '  enlace `tipo_app` del dueño, ajustes del login y del',
            '  frontend por tipo. Ver `prompt_piloto.md` §1.2 y §8.7.',
            '- **Multi-cliente.** Cada cliente con su subdominio y su',
            '  propio grafo, misma base de código. `Conf::NOMBRE_APP`',
            '  derivado del subdominio. Ver `prompt_piloto.md` §1.4.',
            '',
            '---',
            '',
            '## 6. PRÓXIMO ARCO',
            '',
            'A definir. Cuando el usuario lo proponga, este bloque se',
            'actualiza con:',
            '',
            '- **Motivación** (por qué se hace).',
            '- **Decisiones tomadas.**',
            '- **Fases o tandas** con checklist.',
            '- **Archivos tocados.**',
            '- **Pendientes que abre.**',
            '',
            'Los candidatos que hoy están en la lista (sin prioridad) son',
            'los de §4. La decisión depende del usuario.',
            '',
            '---',
            '',
            '## 7. NOTAS CRÍTICAS QUE NO PUEDEN FALTAR',
            '',
            'Reglas de oro que aplican a cualquier tanda nueva.',
            '',
            '- **La app no toca bitmasks a mano.** Si lo hiciera, la',
            '  seguridad deja de emerger de la topología.',
            '- **Los IDs numéricos del framework son efímeros.** Solo los',
            '  IDs especiales (strings no numéricos) son estables entre',
            '  cargas.',
            '- **Antes de destruir un nodo, desenlazar.** `Nodo::eliminar`',
            '  falla si hay referencias entrantes.',
            '- **Nodos se comparan por `->id()`.** Nunca con `===`.',
            '- **Datos como strings.** Números también. Castear al leer.',
            '- **PHP convierte claves de array string numéricas a int.**',
            '  Forzar `(string)$clave` al iterar.',
            '- **En MV3, todos los imports del service worker tienen que',
            '  ser estáticos.** Cualquier `await import(...)` en el SW',
            '  rompe al reiniciarse por inactividad.',
            '- **`Object.entries(Map)` devuelve `[]`.** Iterar con',
            '  `for (const [k, v] of map)`.',
            '- **Los bloques `buscar` del `aplicar_cambios.php` tienen',
            '  que matchear exacto el archivo en disco.** Si un bloque',
            '  falla, pedir el fragmento exacto al usuario antes de',
            '  ajustarlo.',
            '',
            '---',
            '',
            '**FIN DEL PLAN ACTUAL**',
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";