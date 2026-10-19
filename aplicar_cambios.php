<?php
/**
 * Aplicador — Tanda A2: reducir §8.7 de prompt_piloto a un puntero.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: reemplazar por puntero a plan_actual §5.1',
        'buscar' => [
            '### 8.7 Plan de contextos y carga parcial (en desarrollo)',
            '',
            'Este apartado es el plan del piloto para aprovechar los',
            'contextos que se están implementando en el framework',
            '(ver §11.4 del prompt del framework). Todavía no está',
            'implementado. Se actualiza a medida que avanza cada fase.',
            '',
            '**Motivación.** El piloto crece en cantidad de dueños,',
            'viajes, ventas y pasajeros. Cada operación carga el',
            'grafo completo (ver §8.6, "frente A"). Aislar el',
            'subgrafo de un dueño permite cargar solo lo que se',
            'necesita y baja el costo de cada operación.',
            '',
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
            '',
            '**Comandos asociados.**',
            '',
            '- `app:crear_compartidos_terminal` (v76q): crea los',
            '  compartidos. Args: `{dueno, terminal}`, cada uno',
            '  `nombre` o `todos`. Idempotente.',
            '- Los demás comandos de migración están en el módulo',
            '  `Aplicacion/Migraciones/`. Los tres primeros',
            '  (`grafo:crear_niveles_usuario`, además) son deuda',
            '  técnica: `grafo:crear_niveles_usuario` está en el',
            '  framework pero debería vivir en la app.',
            '',
            'Cada fase es una tanda, con migración idempotente y',
            'verificación en local antes de producción.',
            '',
            'Lo que cambia:',
            '',
            '- Cada dueño es una raíz del grafo. Al cargar, se',
            '  puede hacer BFS desde ese root y traer solo su',
            '  subárbol.',
            '- Los terminales de un dueño quedan dentro del',
            '  contexto del dueño. Siguen siendo usuarios, pero',
            '  se alcanzan a través del dueño.',
            '- El nodo `usuarios` sigue existiendo como raíz',
            '  global (contiene a todos los usuarios, incluidos',
            '  los dueños), así que las operaciones globales',
            '  (login, admin) no se rompen.',
            '',
            '**Tipos como IDs especiales.** Segundo eje de',
            'contexto. Hoy los tipos son implícitos: "empresa" es',
            'un nodo bajo `dueno1 -> empresas -> <empresa>`. En el',
            'plan se agregan roots explícitos como `tipo_empresas`,',
            '`tipo_viajes`, `tipo_asientos`, `tipo_clientes`,',
            '`tipo_ventas`, `tipo_micros`. Cada root apunta a los',
            'nodos de su tipo, sin importar el dueño.',
            '',
            'Lo que cambia:',
            '',
            '- Consultas cross-cutting: "todas las empresas del',
            '  sistema" se hacen con BFS desde `tipo_empresas`',
            '  en vez de recorrer todos los dueños.',
            '- Un nodo puede pertenecer a dos contextos a la vez',
            '  (un dueño + un tipo). El bitmask soporta esa',
            '  situación.',
            '- La pertenencia múltiple tiene un costo: los nodos',
            '  tienen dos padres (uno por cada eje). El framework',
            '  lo soporta, pero hay que revisar los flujos de',
            '  destrucción para que desenlacen de ambos lados.',
            '',
            '**Multi-dueño real.** El plan contempla un mismo',
            'nodo perteneciente a varios dueños (por ejemplo, un',
            'catálogo de productos compartido entre clientes). El',
            'bitmask lo soporta sin cambios estructurales: la',
            'columna `contexto_mask` guarda varios bits en 1.',
            '',
            '**Cuándo se implementa.** El orden es:',
            '',
            '1. **Fase 1 del framework**: `SQL64` (bitmask + 3',
            '   tablas nuevas). Sin tocar el piloto.',
            '   **Completado** (framework 1.5i.7k).',
            '2. **Fase 2 del framework**: `IndexedDB64` (espejo).',
            '   **Completado** (framework 1.5i.7k).',
            '3. **Fase 3 del framework**: `JSON64` / `XML64`.',
            '   Pendiente.',
            '4. **Refactor del framework: dejar el Nodo limpio.**',
            '   Antes de agregar más métodos de indexación de',
            '   contextos (256 bits, producto de primos, etc.),',
            '   hay que mover la máscara fuera del Nodo. Ver',
            '   §11.4 del prompt del framework. Pendiente,',
            '   bloqueante de la Fase 5.',
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
            '',
            'Los pasos 1-4 son del framework. Los pasos 5-7 son',
            'del piloto. Cada paso en su propia tanda, con sus',
            'dos scripts donde corresponda.',
            '',
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
        'reemplazar' => [
            '### 8.7 Plan de contextos y carga parcial',
            '',
            'La hoja de ruta completa del modelo topológico (fases',
            'A, B1, B2.1-B2.4, B3, C, D) y su estado actual',
            '**viven en `plan_actual.md` §5.1**. Ahí está la tabla',
            'con cada fase, la versión en que se cerró y las',
            'pendientes.',
            '',
            'Resumen al día: las fases A, B1, B2.1, B2.2, B2.3 y',
            'B2.4 están completas. La Fase B3 está cerrada salvo',
            '**B3.3c** (3 helpers que todavía iteran `usuarios`),',
            'que es el próximo arco. Con B3.3c cerrado, la Fase B',
            'queda completa.',
            '',
            'El detalle histórico de cada fase (qué se hizo, en qué',
            'versión, con qué comandos) está en el historial del',
            'piloto (§7) y en `prompt_seguridad.md` §3-5.',
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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";