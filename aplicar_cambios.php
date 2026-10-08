<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76l (diseño del modelo topológico).
 *   - prompts/prompt_piloto.md: documentar el modelo por
 *     niveles de exposición y el plan por fases B1/B2/B3.
 *   - aplicacion_POST.php: nueva sección "Diseño propuesto:
 *     contenedores por nivel de exposición".
 *
 * Solo documentación. No hay cambios de código de aplicación.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // prompts/prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76k',
            '(Fase A de contextos del piloto. Todos los usuarios pasan a',
            'tener ID especial `us_<nombre>`. Los enlaces desde `usuarios`',
            'siguen llamándose `<nombre>` (nombre visible), por lo que',
            'todos los accesos por `adyacente()` siguen funcionando sin',
            'cambios. Script de migración idempotente en',
            '`miscelaneas/migrar_usuarios_especiales.php`, ejecutable con',
            '`?migrar_usuarios_especiales=1` desde `index.php`. Nuevo',
            'comando `grafo:reemplazar_referencias` en el `Controlador`',
            'para redirigir referencias cruzadas. Sin carga parcial',
            'todavía.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76l',
            '(diseño del modelo topológico por niveles de exposición.',
            'Cada usuario va a tener contenedores `publico`, `privado` y',
            '`compartido_con_X` colgando de su nodo raíz. La seguridad',
            'emerge de la topología. Admin y soporte siguen accediendo',
            'por código, no por topología. Diseño completo en el PHPDoc',
            'de `aplicacion_POST.php`; plan por fases en §8.7. Solo',
            'documentación, no hay cambios de código todavía.).',
            'Antes: v1.5piloto.76k',
            '(Fase A de contextos del piloto. Todos los usuarios pasan a',
            'tener ID especial `us_<nombre>`. Los enlaces desde `usuarios`',
            'siguen llamándose `<nombre>` (nombre visible), por lo que',
            'todos los accesos por `adyacente()` siguen funcionando sin',
            'cambios. Script de migración idempotente en',
            '`miscelaneas/migrar_usuarios_especiales.php`, ejecutable con',
            '`?migrar_usuarios_especiales=1` desde `index.php`. Nuevo',
            'comando `grafo:reemplazar_referencias` en el `Controlador`',
            'para redirigir referencias cruzadas. Sin carga parcial',
            'todavía.).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v76l al historial',
        'buscar' => [
            '- **v76k**: Fase A de contextos del piloto. Todos los',
            '  usuarios pasan a ser IDs especiales `us_<nombre>`. Los',
            '  enlaces desde `usuarios` siguen llamándose `<nombre>`',
            '  (nombre visible), así los accesos por `adyacente()` no',
            '  cambian. `agregar_usuario`, `actualizar_usuario` (rama',
            '  de credenciales) y la creación del admin en `index.php`',
            '  (ambos grafos) ahora usan `crear_con_dato_e_id`. Nuevo',
            '  comando genérico `grafo:reemplazar_referencias` en el',
            '  `Controlador` (recibe un mapa `{viejo → nuevo}` y',
            '  redirige todas las aristas del grafo que apunten a un',
            '  viejo). Nuevo script `miscelaneas/migrar_usuarios_especiales.php`',
            '  (idempotente) y bloque `?migrar_usuarios_especiales=1`',
            '  en `index.php`. Sin cambios en los accesos, sin carga',
            '  parcial todavía.',
        ],
        'reemplazar' => [
            '- **v76l**: diseño del modelo topológico por niveles de',
            '  exposición. Cada usuario va a tener contenedores',
            '  `publico`, `privado` y `compartido_con_X` colgando de',
            '  su nodo raíz, con los datos distribuidos según quién',
            '  debe verlos. La seguridad emerge de la topología: si',
            '  un usuario no tiene un enlace al `privado` de otro,',
            '  no puede alcanzarlo. Los enlaces "de permiso"',
            '  (`dueno`, `soporte`) van en la raíz. Admin y soporte',
            '  no usan este modelo: siguen accediendo por código',
            '  (con `_verificar_permiso_dueno`). Diseño completo en',
            '  el PHPDoc de `aplicacion_POST.php` (sección "Diseño',
            '  propuesto: contenedores por nivel de exposición").',
            '  Plan por fases (B1/B2/B3) en §8.7. Solo documentación:',
            '  no hay cambios de código todavía.',
            '- **v76k**: Fase A de contextos del piloto. Todos los',
            '  usuarios pasan a ser IDs especiales `us_<nombre>`. Los',
            '  enlaces desde `usuarios` siguen llamándose `<nombre>`',
            '  (nombre visible), así los accesos por `adyacente()` no',
            '  cambian. `agregar_usuario`, `actualizar_usuario` (rama',
            '  de credenciales) y la creación del admin en `index.php`',
            '  (ambos grafos) ahora usan `crear_con_dato_e_id`. Nuevo',
            '  comando genérico `grafo:reemplazar_referencias` en el',
            '  `Controlador` (recibe un mapa `{viejo → nuevo}` y',
            '  redirige todas las aristas del grafo que apunten a un',
            '  viejo). Nuevo script `miscelaneas/migrar_usuarios_especiales.php`',
            '  (idempotente) y bloque `?migrar_usuarios_especiales=1`',
            '  en `index.php`. Sin cambios en los accesos, sin carga',
            '  parcial todavía.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §8.7 (Fase A → Fases A/B/C/D)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: reemplazar bloque de Fase A por diseño completo',
        'buscar' => [
            '**Usuarios como IDs especiales (Fase A, completada en v76k).**',
            'Todos los usuarios (no solo los dueños) son ahora nodos',
            'con ID especial `us_<nombre>`. Los enlaces desde',
            '`usuarios` siguen llamándose `<nombre>` (nombre visible),',
            'así todos los accesos por `adyacente()` funcionan sin',
            'cambios. Los nodos viejos se migraron con',
            '`miscelaneas/migrar_usuarios_especiales.php`, que usa el',
            'nuevo comando `grafo:reemplazar_referencias` para redirigir',
            'las aristas cruzadas. Próximo paso: aprovechar la carga',
            'parcial (Fase C) una vez cerrada la Fase B (tipos como',
            'IDs especiales).',
        ],
        'reemplazar' => [
            '**Fase A — Usuarios como IDs especiales (completada en v76k).**',
            'Todos los usuarios (no solo los dueños) son ahora nodos',
            'con ID especial `us_<nombre>`. Los enlaces desde',
            '`usuarios` siguen llamándose `<nombre>` (nombre visible),',
            'así todos los accesos por `adyacente()` funcionan sin',
            'cambios. Los nodos viejos se migraron con',
            '`miscelaneas/migrar_usuarios_especiales.php`, que usa el',
            'comando `grafo:reemplazar_referencias` para redirigir',
            'las aristas cruzadas.',
            '',
            '**Fase B — Contenedores por nivel de exposición (en diseño).**',
            'Cada usuario pasa a tener contenedores por nivel colgando',
            'directamente de su nodo raíz:',
            '',
            '```',
            'us_X',
            '├── publico                    (dato = nombre_usuario)',
            '│   ├── nivel',
            '│   ├── nombre_real',
            '│   └── email',
            '├── privado                    (los datos internos del rol)',
            '├── compartido_con_us_Y        (uno por cada usuario con quien comparte)',
            '└── ...',
            '```',
            '',
            'Los enlaces "de permiso" (`dueno` en el terminal, `soporte`',
            'en el dueño) van directamente en la raíz del usuario, no',
            'en un contenedor.',
            '',
            'La seguridad emerge de la topología: si un usuario no',
            'tiene un enlace al `privado` de otro, no puede alcanzarlo.',
            'Los datos privados no están en el grafo alcanzable desde',
            'otros usuarios.',
            '',
            'Los contenedores concretos por rol están en el PHPDoc de',
            '`aplicacion_POST.php`, sección "Diseño propuesto:',
            'contenedores por nivel de exposición".',
            '',
            'Admin y soporte NO usan este modelo: siguen accediendo a',
            'todo por código (con `_verificar_permiso_dueno`). El admin',
            'es todopoderoso; el soporte lo es solo sobre sus dueños',
            'asignados.',
            '',
            '**Fases de implementación.** El orden revisado es:',
            '',
            '1. Fase A (completada): usuarios como IDs especiales.',
            '2. Fase B1: crear `publico` y `privado` en cada usuario.',
            '   Mover los datos existentes. `usuarios` apunta al',
            '   `publico`. Los `compartido_con_X` todavía no existen.',
            '   Los terminales siguen accediendo como hoy.',
            '3. Fase B2: crear los `compartido_con_X` de cada dueño',
            '   (uno por terminal autorizado) y reescribir los enlaces',
            '   desde los terminales.',
            '4. Fase B3: eliminar los accesos viejos.',
            '5. Fase C: (opcional) tipos como IDs especiales,',
            '   ortogonal.',
            '6. Fase D: aprovechar la carga parcial como optimización.',
            '',
            'Cada fase es una tanda, con migración idempotente y',
            'verificación en local antes de producción.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §8.7 "Cuándo se implementa"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: actualizar "Cuándo se implementa"',
        'buscar' => [
            '5. **Cambio del piloto**: convertir dueños a IDs',
            '   especiales. Requiere migración de datos y de',
            '   código. Es una tanda grande.',
            '6. **Segundo cambio del piloto**: agregar los',
            '   `tipo_*` como IDs especiales.',
            '7. **Tercer cambio del piloto**: aprovechar la carga',
            '   parcial en las operaciones más frecuentes',
            '   (listar viajes, listar ventas, etc.).',
        ],
        'reemplazar' => [
            '5. **Fase A del piloto**: usuarios como IDs especiales.',
            '   **Completada** en v76k.',
            '6. **Fase B1 del piloto**: crear `publico` y `privado`',
            '   en cada usuario. Mover los datos. `usuarios` apunta',
            '   al `publico`. Pendiente.',
            '7. **Fase B2 del piloto**: crear los `compartido_con_X`',
            '   y reescribir los enlaces desde los terminales.',
            '   Pendiente.',
            '8. **Fase B3 del piloto**: eliminar los accesos viejos.',
            '   Pendiente.',
            '9. **Fase C (opcional)**: tipos como IDs especiales.',
            '10. **Fase D**: aprovechar la carga parcial como',
            '    optimización.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §8.7 Preguntas abiertas
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: actualizar "Preguntas abiertas"',
        'buscar' => [
            '**Preguntas abiertas (a consensuar cuando llegue el',
            'momento):**',
            '',
            '- Nombre exacto del prefijo para los dueños',
            '  (`us_dueno1` vs `u_dueno1` vs otro).',
            '- Qué hacer con los nodos que hoy cuelgan directo',
            '  de `usuarios` y no pertenecen a ningún dueño',
            '  (por ejemplo el admin). Siguen en el contexto',
            '  global del nodo `usuarios`.',
            '- Cómo migrar los grafos existentes sin perder',
            '  datos. Se puede hacer una migración ad-hoc que',
            '  recorra el grafo, cree los nuevos roots y',
            '  reescriba los enlaces.',
            '- Qué operaciones del piloto pasan a usar carga',
            '  parcial primero. Candidatas: listar viajes,',
            '  listar ventas, ver detalle de un viaje.',
        ],
        'reemplazar' => [
            '**Preguntas abiertas (a consensuar cuando llegue el',
            'momento):**',
            '',
            '- Qué operaciones del piloto van a aprovechar el modelo',
            '  topológico, y cuáles van a seguir requiriendo un',
            '  acceso de admin/soporte.',
            '- Cómo se comporta `listar_usuarios` desde la Fase B1 en',
            '  adelante: recorre `usuarios → cada hijo`, lee el dato',
            '  del contenedor `publico`, sin cargar subárboles.',
            '- Cómo migrar los grafos existentes sin perder datos.',
            '  La migración es idempotente y se corre por fases.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 (ancla corregida)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: prepender bullet v76l antes de v76j',
        'buscar' => [
            '- Cerramos en v76j el cierre de la fase 2 del framework',
        ],
        'reemplazar' => [
            '- Cerramos en v76l el diseño del modelo topológico por',
            '  niveles de exposición. Cada usuario va a tener',
            '  contenedores `publico`, `privado` y `compartido_con_X`',
            '  colgando de su nodo raíz. La seguridad emerge de la',
            '  topología: si un usuario no tiene un enlace al',
            '  `privado` de otro, no puede alcanzarlo. Los enlaces',
            '  "de permiso" (`dueno`, `soporte`) van en la raíz.',
            '  Admin y soporte no usan la topología: siguen',
            '  accediendo por código. El diseño completo está en el',
            '  PHPDoc de `aplicacion_POST.php`; el plan por fases',
            '  está en §8.7. No hay cambios de código todavía.',
            '- Cerramos en v76j el cierre de la fase 2 del framework',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §13 (estado al cierre)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: actualizar estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76k (framework 1.5i.7k).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76l (framework 1.5i.7k).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76l al bloque de estado',
        'buscar' => [
            'v76k: Fase A de contextos del piloto. Todos los usuarios',
            'son IDs especiales `us_<nombre>`. Nuevo comando',
            '`grafo:reemplazar_referencias`. Script de migración',
            'idempotente en `miscelaneas/migrar_usuarios_especiales.php`.',
            'Sin cambios en los accesos, sin carga parcial todavía.',
        ],
        'reemplazar' => [
            'v76k: Fase A de contextos del piloto. Todos los usuarios',
            'son IDs especiales `us_<nombre>`. Nuevo comando',
            '`grafo:reemplazar_referencias`. Script de migración',
            'idempotente en `miscelaneas/migrar_usuarios_especiales.php`.',
            'Sin cambios en los accesos, sin carga parcial todavía.',
            'v76l: diseño del modelo topológico por niveles de',
            'exposición (`publico`, `privado`, `compartido_con_X`).',
            'Documentado en el PHPDoc de `aplicacion_POST.php`',
            '(sección "Diseño propuesto") y en §8.7. Sin cambios',
            'de código todavía.',
        ],
    ],

    // ============================================================
    // aplicacion_POST.php — nueva sección (ancla corregida)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: nueva sección "Diseño propuesto"',
        'buscar' => [
            ' * ## Sistema de "Volver" en el modal genérico',
        ],
        'reemplazar' => [
            ' * ## Diseño propuesto: contenedores por nivel de exposición (v1.5piloto.76l)',
            ' * ',
            ' * **Estado:** diseño consensuado, todavía no implementado.',
            ' * La migración se hace por fases (ver §8.7 del prompt',
            ' * del piloto). Una vez implementado, esta sección',
            ' * reemplazará a la descripción "actual" de más arriba.',
            ' * ',
            ' * ### Idea general',
            ' * ',
            ' * Cada usuario (`us_<nombre>`) deja de tener sus datos',
            ' * colgando directamente de la raíz. En su lugar, cuelgan',
            ' * varios contenedores, uno por cada nivel de exposición.',
            ' * ',
            ' * Ejemplo del dueño:',
            ' * ',
            ' * ```',
            ' * us_dueno1',
            ' * ├── publico                       (dato = "dueno1")',
            ' * │   ├── nivel → "dueno"',
            ' * │   ├── nombre_real → "..."',
            ' * │   └── email → "..."',
            ' * ├── privado',
            ' * │   ├── efectivo, banco',
            ' * │   ├── terminales, empresas, viajes, pasajeros',
            ' * │   ├── ventas, rendiciones, liquidaciones, cancelaciones',
            ' * │   └── ...',
            ' * ├── compartido_con_us_term1       (uno por cada terminal autorizado)',
            ' * │   ├── viajes         (solo los autorizados a term1)',
            ' * │   ├── empresas       (solo las que usa term1)',
            ' * │   ├── terminales     (solo term1 a sí mismo)',
            ' * │   └── ventas         (solo las ventas de term1)',
            ' * └── compartido_con_us_term2',
            ' *     └── ...',
            ' * ```',
            ' * ',
            ' * Y desde el lado del terminal:',
            ' * ',
            ' * ```',
            ' * us_term1',
            ' * ├── publico                       (dato = "term1")',
            ' * │   ├── nivel → "terminal"',
            ' * │   ├── nombre_real → "..."',
            ' * │   └── email → "..."',
            ' * ├── privado',
            ' * │   ├── efectivo, banco',
            ' * │   ├── ventas         (solo las propias)',
            ' * │   └── venta_actual   (la venta en curso)',
            ' * └── compartido_con_dueno',
            ' *     ├── dueno → us_dueno1/compartido_con_us_term1',
            ' *     └── empresas → { empresa1 → ... }',
            ' * ```',
            ' * ',
            ' * Los enlaces "de permiso" (como `dueno` en el terminal',
            ' * o `soporte` en el dueño) van directamente en la raíz',
            ' * del usuario, no en un contenedor.',
            ' * ',
            ' * ### Principio de seguridad',
            ' * ',
            ' * La seguridad emerge de la topología. Un usuario no',
            ' * puede acceder a un nodo si no hay un camino desde su',
            ' * raíz hasta ese nodo. Si el terminal no tiene un enlace',
            ' * al `privado` del dueño, no lo puede alcanzar, aunque',
            ' * el grafo se cargue completo.',
            ' * ',
            ' * ### Qué va en cada contenedor',
            ' * ',
            ' * **Dueño (`us_duenoX`):**',
            ' * ',
            ' * | Contenedor | Contenido |',
            ' * |---|---|',
            ' * | `publico` | `nivel`, `nombre_real`, `email`. Dato = nombre de usuario. |',
            ' * | `privado` | `efectivo`, `banco`, `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones`, `cancelaciones`. |',
            ' * | `compartido_con_us_term1` | `viajes` (autorizados), `empresas` (usadas), `terminales` (solo term1), `ventas` (de term1). |',
            ' * | ... | uno por cada terminal autorizado. |',
            ' * | `soporte` | enlace directo en la raíz, si tiene soporte asignado. |',
            ' * ',
            ' * **Terminal (`us_termX`):**',
            ' * ',
            ' * | Contenedor | Contenido |',
            ' * |---|---|',
            ' * | `publico` | `nivel`, `nombre_real`, `email`. Dato = nombre de usuario. |',
            ' * | `privado` | `efectivo`, `banco`, `ventas` (propias), `venta_actual`. |',
            ' * | `compartido_con_dueno` | `dueno` (→ `us_duenoY/compartido_con_us_termX`), `empresas` (las que necesita para vender). |',
            ' * ',
            ' * **Soporte (`us_soporteX`):**',
            ' * ',
            ' * Sin cambios estructurales por ahora. Los soportes',
            ' * acceden por código (con `_verificar_permiso_dueno`),',
            ' * no por topología.',
            ' * ',
            ' * **Admin (`us_admin`):**',
            ' * ',
            ' * Sin cambios. Acceso total por código.',
            ' * ',
            ' * ### Notas de diseño',
            ' * ',
            ' * - **Los contenedores son directos**, no cuelgan de un',
            ' *   contenedor intermedio `niveles`. Los niveles son la',
            ' *   estructura principal del usuario.',
            ' * - **Un contenedor por terminal**: como el dueño autoriza',
            ' *   terminales por viaje, no alcanza un único',
            ' *   `compartido_con_terminales`. Cada terminal tiene su',
            ' *   propio contenedor de compartición.',
            ' * - **Ventas y cupones**: la venta vive en',
            ' *   `us_dueno1/privado/ventas`. También hay una referencia',
            ' *   desde `us_term1/privado/ventas` (solo las ventas',
            ' *   propias). La venta tiene dos referencias entrantes;',
            ' *   la destrucción debe desenlazar de ambas.',
            ' * - **`usuarios` apunta al `publico`**: el nodo `usuarios`',
            ' *   es un índice. Cada enlace `usuarios → <nombre>` apunta',
            ' *   al contenedor `publico` del usuario. Así',
            ' *   `listar_usuarios` puede leer el dato y los datos',
            ' *   públicos sin cargar el subárbol privado.',
            ' * - **`terminales_autorizadas` de un viaje no cambia',
            ' *   internamente.** Sigue siendo un contenedor con un',
            ' *   TerminalViaje por terminal. Lo que cambia es cómo se',
            ' *   llega al viaje: desde el dueño por `privado/viajes`,',
            ' *   desde el terminal por `compartido_con_dueno/dueno/viajes`.',
            ' * ',
            ' * ### Plan de migración',
            ' * ',
            ' * Por fases, con la aplicación funcionando entre cada una:',
            ' * ',
            ' * 1. **Fase B1**: crear `publico` y `privado` en cada',
            ' *    usuario. Mover los datos existentes. `usuarios`',
            ' *    apunta al `publico`. Los `compartido_con_X` todavía',
            ' *    no existen. Los terminales siguen accediendo como hoy.',
            ' * 2. **Fase B2**: crear los `compartido_con_X` de cada',
            ' *    dueño (uno por terminal autorizado) y reescribir los',
            ' *    enlaces desde los terminales.',
            ' * 3. **Fase B3**: eliminar los accesos viejos.',
            ' * ',
            ' * Cada fase es una tanda, con migración idempotente y',
            ' * verificación en local antes de producción. Ver §8.7',
            ' * del prompt del piloto.',
            ' * ',
            ' * ## Sistema de "Volver" en el modal genérico',
        ],
    ],

    // ============================================================
    // aplicacion_POST.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: bump @version',
        'buscar' => [
            ' * @package   Iteradores',
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.73',
            ' */',
        ],
        'reemplazar' => [
            ' * @package   Iteradores',
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.76l',
            ' */',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";