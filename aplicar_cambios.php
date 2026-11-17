<?php
/**
 * Aplicador — Plan: Fase A (refactor de usuarios) + Fase B (juego).
 *
 * Solo toca prompts/plan_actual.md. Sin bumps.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // Header de §1
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: header v84h + Fases A y B',
        'buscar' => [
            '**Última actualización:** v1.5piloto.84g + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fases 1, 2 y 3 de seguridad aplicadas (v84a-v84g):**',
            'sesión PHP con cookie PHPSESSID + `$_SESSION`,',
            'impresión cerrada (sesión + permisos), POST valida',
            'sesión server-side, chequeo de token con TTL 30s',
            '(excluyendo polling), chequeo pre-escritura. Fase 4',
            '(tercer grafo de identidad) queda como deuda riesgosa.',
            'Ver §2 y §8).',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.84h + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fases 1, 2 y 3 de seguridad aplicadas (v84a-v84g):**',
            'sesión PHP con cookie PHPSESSID + `$_SESSION`,',
            'impresión cerrada (sesión + permisos), POST valida',
            'sesión server-side, chequeo de token con TTL 30s',
            '(excluyendo polling), chequeo pre-escritura.',
            '**v84h:** renumeración de asientos de un micro de',
            'viaje aplicada (`renumerar_asientos_micro` + subacción',
            '`viajes/renumerar_asientos` + modal en',
            '`viajes-micros.js`). **Plan de Fase A (refactor de',
            'usuarios en framework) y Fase B (juego en 2 modos)',
            'agregado:** ver §8.5 y §9. Ver §2 y §8).',
        ],
    ],

    // ============================================================
    // §2: arco de v84h antes de Fase 3c
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §2: arco v84h renumerar',
        'buscar' => [
            '- **Fase 3c de seguridad: chequeo pre-escritura (v84g).**',
        ],
        'reemplazar' => [
            '- **v84h: renumerar asientos de un micro de viaje.**',
            '  Nuevo módulo `Aplicacion/Viajes/ViajeRenumerar.php`',
            '  con `renumerar_asientos_micro` (cambia solo el `dato`',
            '  de cada nodo asiento; no toca pasajeros, ventas ni',
            '  reservas). Subacción `viajes/renumerar_asientos` en',
            '  el Enrutador (solo dueño/admin/soporte). Modal en',
            '  `viajes-micros.js` con grilla, edición por doble',
            '  click, "Reiniciar numeración (1..N)" y "Renumerar',
            '  desde acá" (cascada ascendente). Detalle en §5.5.',
            '',
            '- **Fase 3c de seguridad: chequeo pre-escritura (v84g).**',
        ],
    ],

    // ============================================================
    // §3: dos decisiones nuevas al final
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §3: decisiones app + juego',
        'buscar' => [
            '- **Cada cambio al piloto lleva su espejo de pruebas en el',
            '  plugin.** Salvo excepción justificada (estilos, refactor',
            '  sin cambio de comportamiento, herramientas de admin).',
        ],
        'reemplazar' => [
            '- **Cada cambio al piloto lleva su espejo de pruebas en el',
            '  plugin.** Salvo excepción justificada (estilos, refactor',
            '  sin cambio de comportamiento, herramientas de admin).',
            '- **`app` va en identidad, no en credenciales (Fase A,',
            '  §8.5).** El campo `app` ("viajes" | "juego" | "tienda"',
            '  | "global") vive en `perfil_<nombre>` del grafo de',
            '  identidad. Credenciales se carga on-demand (login,',
            '  validar token); identidad se carga al arranque de cada',
            '  request. El `app` es dato de perfil (ruteo), no de',
            '  autenticación. Default en migración: `"viajes"`.',
            '  Admin global: `"global"`.',
            '- **Grafo `AdministradorDeJuego` separado del de viajes',
            '  (Fase B, §9).** Aunque el usuario sea el mismo (mismo',
            '  `nombre_usuario` en los 3 grafos), los datos del juego',
            '  (partidas, preguntas, respuestas) no comparten grafo',
            '  con viajes. El login es único; el ruteo por `app`',
            '  decide qué grafo cargar.',
        ],
    ],

    // ============================================================
    // §5.2: fila nueva en la tabla del framework
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §5.2: fila usuarios+identidad',
        'buscar' => [
            '| **Iteradores con ID especial + ocupante** | Raíces de subgrafo con posición mutable. Resolvería B3.3c. | Pendiente |',
        ],
        'reemplazar' => [
            '| **Iteradores con ID especial + ocupante** | Raíces de subgrafo con posición mutable. Resolvería B3.3c. | Pendiente |',
            '| **Usuarios al framework + grafo identidad** | Sistema de usuarios sale de `Aplicacion/`; tercer grafo `identidad` con `perfil_*` y `app`; HMAC login O(1); carga parcial por grafo. Ver §8.5 (Fase A). | Pendiente |',
        ],
    ],

    // ============================================================
    // §5.3: bullets de multi-app y juego
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §5.3: multi-app y juego',
        'buscar' => [
            '- **Multi-cliente.** Cada cliente con su subdominio y su',
            '  propio grafo, misma base de código.',
            '  `Conf::NOMBRE_APP` derivado del subdominio. Ver',
            '  `prompt_piloto.md` §1.4.',
        ],
        'reemplazar' => [
            '- **Multi-cliente.** Cada cliente con su subdominio y su',
            '  propio grafo, misma base de código.',
            '  `Conf::NOMBRE_APP` derivado del subdominio. Ver',
            '  `prompt_piloto.md` §1.4.',
            '- **Multi-app por usuario.** Un mismo usuario puede',
            '  acceder a varias apps (viajes, juego, tienda) con el',
            '  mismo login. El campo `app` en `perfil_<nombre>`',
            '  (identidad) indica la app activa. Admin con',
            '  `app = "global"` ve todas. Ver §8.5 (Fase A).',
            '- **Juego de trivia.** Grafo `AdministradorDeJuego`',
            '  separado, con dos modos (directo y plugin). Ver §9',
            '  (Fase B).',
        ],
    ],

    // ============================================================
    // §5: agregar §5.5 antes de "## 6. ARCO EN CURSO"
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §5.5: fases A y B resumen',
        'buscar' => [
            '  inferidos por enlace, patrón de grilla adaptativo al',
            '  zoom.',
            '',
            '---',
            '',
            '## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante',
        ],
        'reemplazar' => [
            '  inferidos por enlace, patrón de grilla adaptativo al',
            '  zoom.',
            '',
            '### 5.5. Refactor de usuarios + juego (Fases A y B)',
            '',
            'Plan de fases para el refactor del sistema de usuarios',
            '(Fase A) y el juego de trivia (Fase B). Detalle',
            'completo en §8.5 y §9 respectivamente.',
            '',
            '**Fase A — Refactor de usuarios + identidad (ver §8.5):**',
            '',
            '| Tanda | Qué hace | Estado |',
            '|---|---|---|',
            '| **A.1** | Crear grafo `identidad` + `perfil_*` (con `app`) + migración. | Pendiente |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). | Pendiente |',
            '| **A.3** | Carga parcial por grafo + ruteo por `app`. | Pendiente |',
            '| **A.4** | Mover `Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework. | Pendiente |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales (destructiva). | Pendiente |',
            '',
            '**Fase B — Juego de trivia (ver §9):**',
            '',
            '| Tanda | Qué hace | Estado |',
            '|---|---|---|',
            '| **B.1** | Modelo de partida + endpoints base. | Pendiente |',
            '| **B.2** | UI owner (proyector) + UI terminal (modo directo). | Pendiente |',
            '| **B.3** | Ranking + empates + persistencia. | Pendiente |',
            '| **B.4** | Plugin de chat YouTube (modo inyección). | Pendiente |',
            '| **B.5** | Pruebas con celulares reales. | Pendiente |',
            '',
            '---',
            '',
            '## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante',
        ],
    ],

    // ============================================================
    // §8 header: mención de Fase A
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8: header con Fase A',
        'buscar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84g. Las Fases 1, 2 y 3',
            'están aplicadas y funcionando. La Fase 4 (tercer grafo',
            'de identidad) queda como deuda riesgosa, diferida por',
            'decisión consciente.',
        ],
        'reemplazar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84h. Las Fases 1, 2 y 3',
            'están aplicadas y funcionando. Lo que antes se llamaba',
            '"Fase 4 (tercer grafo de identidad)" se expandió a la',
            '**Fase A completa** (refactor del sistema de usuarios +',
            'grafo `identidad` + `app` en perfil + HMAC + carga',
            'parcial por grafo), con plan detallado en §8.5. No',
            'implementada todavía.',
        ],
    ],

    // ============================================================
    // §8.5: rewrite completo como Fase A
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8.5: rewrite como Fase A completa',
        'buscar' => [
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente, riesgosa)',
            '',
            '**Estado.** Diferida por decisión consciente. Requiere una',
            'migración riesgosa (mover datos entre grafos, agregar',
            '`indices/codigos_hmac`, tocar el login). Con la app en',
            'producción andando, no es momento. Se retoma cuando haya',
            'una ventana de mantenimiento.',
            '',
            'Objetivo: sacar el esqueleto `usuarios` de la app y de',
            'credenciales. Hoy `usuarios → us_<nombre>` está duplicado',
            'en ambos grafos, y al admin/soporte la carga "parcial"',
            'le trae todo (desde `usuarios` se alcanza a cada',
            '`us_<nombre>` con su subgrafo).',
            '',
            '**Diseño propuesto:** tercer grafo `AdministradorDeViajes_identidad`.',
        ],
        'reemplazar' => [
            '### 8.5. Fase A — Refactor del sistema de usuarios (plan)',
            '',
            '**Estado.** Plan aprobado, no implementado. La app sigue',
            'funcionando con el modelo actual. La migración es no',
            'destructiva por fases, así que se puede correr con la',
            'app andando. Se retoma cuando haya una ventana de',
            'trabajo dedicada.',
            '',
            '**Motivación.** Tres objetivos:',
            '',
            '1. **Escalar.** El admin/soporte no debe cargar los',
            '   subgrafos de todos los usuarios. Hoy su "carga',
            '   parcial" es total en la práctica (desde `usuarios`',
            '   se alcanza a cada `us_<nombre>` con todo su',
            '   subgrafo).',
            '2. **Multi-app.** Los usuarios deben poder acceder a',
            '   varias apps (viajes, juego, tienda) con el mismo',
            '   login. El campo `app` indica la app activa.',
            '3. **Reutilización.** El sistema de usuarios sale de',
            '   `Aplicacion/` y se mueve al framework, disponible',
            '   para todas las apps.',
            '',
            '**Diseño:** tercer grafo `AdministradorDeViajes_identidad`',
            'como "enganche" entre credenciales y los grafos de',
            'aplicación. El punto de unión es el `nombre_usuario`',
            '(string), no un enlace de grafo.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §8.5: bullets y footer con A.1-A.5',
        'buscar' => [
            '- `usuarios` sale del grafo de la app y del de credenciales.',
            '- `perfil_<nombre>` tiene `nivel`, `nombre_real`, `email`,',
            '  `dueno`/`duenos` (strings). Liviano (~5 nodos).',
            '- El admin/soporte carga `[\'usuarios\']` desde identidad',
            '  (solo perfiles) y, cuando opera sobre un usuario',
            '  específico, `[\'us_<nombre>\']` desde app o credenciales.',
            '- El índice HMAC (login por código O(1)) vive en identidad.',
            '- `en_grafo_identidad` helper nuevo (o generalizar',
            '  `en_grafo_credenciales`).',
            '',
            '**Esfuerzo**: 3-4 tandas. Bloqueado por Fases 2 y 3.',
            '',
            '**Deuda de diseño**: dejar el Nodo limpio antes de agregar',
            'más métodos de indexación (256 bits, HMAC, primos). Ver',
            '`prompt_framework_iteradores.md` §11.4.',
        ],
        'reemplazar' => [
            '**Estructura de `perfil_<nombre>`:**',
            '',
            '```',
            'perfil_<nombre_usuario>',
            '  ├─ nivel           "admin" | "dueno" | "terminal" | "soporte"',
            '  ├─ nombre_real     string',
            '  ├─ email           string (opcional)',
            '  ├─ dueno           (solo terminal: nombre del dueño)',
            '  ├─ duenos          contenedor (solo soporte: un hijo por dueño)',
            '  └─ app             "viajes" | "juego" | "tienda" | "global"',
            '```',
            '',
            '**Decisión sobre `app`:** va en **identidad**, no en',
            'credenciales. Credenciales se carga on-demand (login,',
            'validar token); identidad se carga al arranque de cada',
            'request. El `app` es dato de perfil (ruteo), no de',
            'autenticación. Default en migración: `"viajes"`. Admin',
            'global: `"global"` (ve todas las apps, elige activa',
            'desde un selector en el header).',
            '',
            '**Carga parcial por grafo.** `_contextos_para_operacion`',
            'devuelve un mapa por grafo, no un solo valor:',
            '',
            '- **Admin:** `identidad=[\'usuarios\']` (perfiles',
            '  livianos) + `app=[\'us_Administrador\']` (solo el admin).',
            '- **Soporte:** `identidad=[\'usuarios\']` +',
            '  `app=[\'us_<soporte>\']`.',
            '- **Dueño:** `identidad=null` (no necesita la lista) +',
            '  `app=[\'us_<dueno>\']`.',
            '- **Terminal:** `identidad=null` +',
            '  `app=[\'us_<terminal>\']`.',
            '',
            '**Ruteo por app.** `index.php` lee',
            '`$_SESSION.usuario.app`, resuelve el nombre del grafo',
            'con un mapa (`app → ConfiguracionApli::NOMBRE_APP_*`) y',
            'carga ese grafo. El Enrutador hace lo mismo en cada',
            'POST. Exactamente el mismo patrón que',
            '`nombre_solicitante` desde v84e.',
            '',
            '**Login O(1) con HMAC.**',
            '',
            '- Al crear/editar código: `h = HMAC(secret, codigo)`,',
            '  se guarda `indices/codigos_hmac/<h> → perfil_<nombre>`.',
            '- Al login por código: `h = HMAC(secret, codigo_ingresado)`',
            '  → lookup directo → `password_verify` 1 sola vez.',
            '- O(N) → O(1). Con 1000 usuarios: ~50ms vs ~50s.',
            '- Secret HMAC en `config_servidor.php`',
            '  (`Conf::SECRET_HMAC`).',
            '- Fallback: si `indices/codigos_hmac` está vacío',
            '  (usuarios viejos sin migrar), usa el recorrido O(N)',
            '  actual. Migración no destructiva.',
            '- Login por usuario+contraseña ya es O(1), sin cambios.',
            '',
            '**Tandas (Fase A):**',
            '',
            '| Tanda | Qué hace |',
            '|---|---|',
            '| **A.1** | Crear grafo `identidad` + `perfil_*` (con `app`) + migración. Sin tocar login ni carga parcial. |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). Script de migración del índice. |',
            '| **A.3** | Carga parcial por grafo + ruteo por `app` en `index.php` y Enrutador. |',
            '| **A.4** | Mover `Aplicacion/Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework (namespace + referencias). |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales. Última tanda, destructiva. Backup previo obligatorio. |',
            '',
            '**Riesgo de A.5.** Es la más delicada (elimina datos).',
            'Si falla, A.1-A.4 siguen funcionando (convivencia con',
            'el modelo viejo). Backup previo obligatorio.',
            '',
            '**Estimación:** 3-4 sesiones de trabajo. A.1 y A.2 son',
            'las más pesadas (migración + HMAC + script).',
            '',
            '**Deuda de diseño (heredada):** dejar el Nodo limpio',
            'antes de agregar más métodos de indexación (256 bits,',
            'HMAC, primos). Ver `prompt_framework_iteradores.md`',
            '§11.4.',
        ],
    ],

    // ============================================================
    // Insertar §9 (Fase B juego) antes de la nota histórica
    // y renombrar la nota histórica a §10
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: insertar §9 juego y renombrar §10',
        'buscar' => [
            '## 9. NOTA HISTÓRICA',
        ],
        'reemplazar' => [
            '## 9. PLAN DEL JUEGO (FASE B)',
            '',
            '**Estado.** Plan aprobado, no implementado. Depende de',
            'Fase A (§8.5): usa `identidad` para login, `app =',
            '"juego"` para ruteo, y el mismo sistema de credenciales.',
            '',
            '**Contexto.** Juego de trivia con dos modos:',
            '',
            '- **Modo 1 (directo):** ~10 terminales (capitanes de',
            '  equipo) con su celular en LAN, más el dueño',
            '  proyectando la pregunta. Los capitanes responden por',
            '  su propio celular. Ideal para evento presencial.',
            '- **Modo 2 (inyección):** el dueño transmite por',
            '  YouTube. Un plugin de Chrome lee el chat cada 500ms,',
            '  filtra respuestas (A/B/C/D), y las inyecta como si',
            '  fueran de usuarios. No hay terminales reales: los',
            '  "jugadores" son nombres del chat.',
            '',
            '**El código es el mismo.** Cambia solo el adaptador de',
            'input (LAN directo vs plugin YouTube).',
            '',
            '**Grafo:** `AdministradorDeJuego`, separado de',
            '`AdministradorDeViajes`. Mismos usuarios (mismo',
            '`nombre_usuario` en los 3 grafos: identidad,',
            'credenciales, app-juego), distinto grafo de datos. El',
            'ruteo lo hace `app = "juego"` en `perfil_*` (ver',
            '§8.5).',
            '',
            '**Estructura del grafo:**',
            '',
            '```',
            'us_<dueno>/partidas (árbol hmi/hd)',
            '  └─ partida_<id>:',
            '       ├─ estado          "esperando"|"mostrando"|',
            '       │                  "respondiendo"|"calculando"|',
            '       │                  "resultado"|"cerrada"',
            '       ├─ modo            "directo" | "plugin"',
            '       ├─ pregunta_actual (ref al banco de preguntas)',
            '       ├─ abre_en_ms      (tiempo restante, no timestamp)',
            '       ├─ cierra_en_ms',
            '       ├─ jugadores (contenedor)',
            '       │    └─ <nombre_jugador>/puntaje, streak',
            '       ├─ respuestas_<pregunta> (contenedor)',
            '       │    └─ <jugador>/opcion + ts_server',
            '       └─ ranking (árbol hmi/hd, top N)',
            '```',
            '',
            '**Jugadores:**',
            '',
            '- Modo 1: `nombre_usuario` de un terminal real.',
            '- Modo 2: username del chat (viewer). Nodo virtual,',
            '  no usuario del sistema.',
            '',
            '**Patrón "inyección" (ya existe en el piloto).** Igual',
            'que `ventas/confirmar` con `nombre_terminal` en el',
            'POST (el dueño crea una venta a nombre de un terminal),',
            'el juego usa `juego/responder` con `jugador` en el',
            'POST (el dueño responde a nombre de un viewer). Un',
            'solo endpoint, comportamiento según nivel del',
            'solicitante: si es `terminal`, el jugador es forzado',
            'al propio `nombre_sol`; si es `dueno`, se acepta el',
            '`jugador` del POST; cualquier otro nivel devuelve 403.',
            '',
            '**Polling adaptativo (sin WebSockets).** El servidor',
            'responde con **tiempo restante**, no timestamp',
            'absoluto. El cliente hace `setTimeout(ms)` y vuelve a',
            'preguntar. Inmune al desfase de reloj. ~4-5 polls por',
            'jugador por pregunta. Con 10 capitanes + 1 owner × 10',
            'preguntas = ~550 hits. Trivial en cualquier hosting',
            'pago; en LAN local es instantáneo.',
            '',
            '**Cronología de respuestas:** `ts_server` (microtime al',
            'recibir). Orden = `sort by ts_server ASC`. Regla de',
            'empate: si dos respuestas llegan dentro de 200ms,',
            'ambas reciben el mismo bonus. Elimina la ventaja del',
            'WiFi bueno.',
            '',
            '**Modo 2 — Plugin YouTube:** content script lee el DOM',
            'del chat cada 500ms, extrae `{usuario, texto}`, filtra',
            'por regex (A/B/C/D), y hace `POST juego/responder` al',
            'endpoint local. El plugin no sabe de trivias: solo',
            '"exporta" mensajes del chat.',
            '',
            '**Tandas (Fase B):**',
            '',
            '| Tanda | Qué hace |',
            '|---|---|',
            '| **B.1** | Modelo de partida + endpoints (`crear`, `iniciar`, `unirse`, `siguiente`, `responder`, `estado`). |',
            '| **B.2** | UI owner (proyector) + UI terminal (modo directo). |',
            '| **B.3** | Ranking + empates + persistencia. |',
            '| **B.4** | Plugin de chat YouTube (modo inyección). |',
            '| **B.5** | Pruebas en local con 5-10 celulares reales. |',
            '',
            '**Estimación:** 3-4 sesiones de trabajo.',
            '',
            '**Fuera de alcance (por ahora):** persistencia de',
            'rankings entre sesiones, badges, streaks, torneos,',
            'suscripciones, plataforma propia con video. Todo eso',
            'se evalúa después de validar la mecánica y la',
            'audiencia. Ver §5.4 (frentes pendientes) cuando se',
            'retome la monetización.',
            '',
            '---',
            '',
            '## 10. NOTA HISTÓRICA',
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
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
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
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
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
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";