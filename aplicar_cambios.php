<?php
/**
 * Aplicador de cambios — Prompt del piloto.
 *
 * Tanda de documentación: v76i. Anota el plan de contextos
 * en el piloto (dueños y tipos como IDs especiales).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ------------------------------------------------------------
    // Historial: agregar v76i
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v76i (plan de contextos)',
        'buscar' => [
            '- **v76h**: nuevo botón "Imprimir Planilla Vacía" en el',
        ],
        'reemplazar' => [
            '- **v76i**: solo documentación. Se agrega la sección',
            '  §8.7 con el plan de contextos del piloto (dueños',
            '  y tipos como IDs especiales, integración con el',
            '  plan del framework §11.4). No hay cambios de código.',
            '- **v76h**: nuevo botón "Imprimir Planilla Vacía" en el',
        ],
    ],

    // ------------------------------------------------------------
    // Nueva sección §8.7
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: agregar plan de contextos del piloto',
        'buscar' => [
            '**Regla de oro:** si un nodo se desenlaza sin destruirse,',
            'primero preguntar "¿tiene otra referencia entrante?". Si la',
            'respuesta es no, hay que destruirlo.',
            '',
            '---',
            '',
            '## 9. PATRONES DE CÓDIGO DEL PILOTO',
        ],
        'reemplazar' => [
            '**Regla de oro:** si un nodo se desenlaza sin destruirse,',
            'primero preguntar "¿tiene otra referencia entrante?". Si la',
            'respuesta es no, hay que destruirlo.',
            '',
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
            '**Dueños como IDs especiales.** El primer paso es',
            'convertir a los dueños en IDs especiales del grafo.',
            'Hoy los dueños son nodos comunes colgando del ID',
            'especial `usuarios`. En el plan pasan a ser roots',
            'propios, con un prefijo que los identifique (por',
            'ejemplo `us_dueno1`, `us_dueno2`).',
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
            '2. **Fase 2 del framework**: `IndexedDB64` (espejo).',
            '3. **Fase 3 del framework**: `JSON64` / `XML64`.',
            '4. **Cambio del piloto**: convertir dueños a IDs',
            '   especiales. Requiere migración de datos y de',
            '   código. Es una tanda grande.',
            '5. **Segundo cambio del piloto**: agregar los',
            '   `tipo_*` como IDs especiales.',
            '6. **Tercer cambio del piloto**: aprovechar la carga',
            '   parcial en las operaciones más frecuentes',
            '   (listar viajes, listar ventas, etc.).',
            '',
            'Los pasos 1-3 son del framework. Los pasos 4-6 son',
            'del piloto. Cada paso en su propia tanda, con sus',
            'dos scripts donde corresponda.',
            '',
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
            '',
            '---',
            '',
            '## 9. PATRONES DE CÓDIGO DEL PILOTO',
        ],
    ],

    // ------------------------------------------------------------
    // §12: nueva "Última actualización"
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: nueva Última actualización a v76i',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76h',
            '(planilla de pasajeros "vacía": nuevo botón "Imprimir',
            'Planilla Vacía" en el croquis del micro.',
            '`imprimir_planilla_pasajeros_micro` recibe un 4to',
            'parámetro `$vacia`; `index.php` lee `$_GET[\'vacia\']`.',
            'Solo PHP.).',
            'Antes: v1.5piloto.76g',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76i',
            '(solo documentación. Se agrega §8.7 con el plan de',
            'contextos del piloto: dueños y tipos como IDs',
            'especiales, integración con el plan del framework',
            '§11.4, fases y preguntas abiertas.).',
            'Antes: v1.5piloto.76h',
            '(planilla de pasajeros "vacía": nuevo botón "Imprimir',
            'Planilla Vacía" en el croquis del micro.',
            '`imprimir_planilla_pasajeros_micro` recibe un 4to',
            'parámetro `$vacia`; `index.php` lee `$_GET[\'vacia\']`.',
            'Solo PHP.).',
            'Antes: v1.5piloto.76g',
        ],
    ],

    // ------------------------------------------------------------
    // §13: bump de versión del estado
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: bump de versión del estado a v76i',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76h (framework 1.5i.7i).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76i (framework 1.5i.7j).',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir.\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";