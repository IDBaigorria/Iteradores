<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.72a: consolidar la regla de actualizar prompts con cada cambio.
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

    // ==========================================================
    // prompts/prompt_sistema_scripts.md — reforzar la regla general
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt_sistema_scripts: reforzar regla de actualizar prompts',
        'buscar' => [
            '## ACTUALIZACIÓN DE LOS PROMPTS AL CERRAR CADA TANDA',
            '',
            'Cuando cierres una tanda, además de los cambios de código, agregá al',
            '`aplicar_cambios.php` los bloques para actualizar los prompts.',
            '',
            '**Regla general:** siempre que se toque código, el `aplicar_cambios.php`',
            'incluye también la actualización del prompt del piloto. No se hace en una',
            'tanda aparte ni se deja para después.',
            '',
            'Lo mínimo que se actualiza en `prompts/prompt_piloto.md`:',
        ],
        'reemplazar' => [
            '## ACTUALIZACIÓN DE LOS PROMPTS CON CADA CAMBIO',
            '',
            '**Regla general, sin excepciones:** cada vez que se modifica código o se',
            'actualiza la forma de trabajo, se actualizan también los prompts. No se',
            'hace en una tanda aparte ni se deja para después.',
            '',
            'Esto aplica a todo `aplicar_cambios.php` que se entregue. Si el script',
            'toca código, tiene que tocar el prompt. Si el script cambia la forma de',
            'trabajo, tiene que tocar el prompt del sistema de scripts.',
            '',
            'Los prompts son parte del proyecto. Se versionan con git como cualquier',
            'otro archivo. Cuando arranca una conversación nueva, el usuario pega los',
            'tres y el asistente lee la "Discusión actual" del prompt del piloto para',
            'saber dónde retomar.',
            '',
            '### Cuándo se actualiza cada prompt',
            '',
            '- **`prompts/prompt_piloto.md`**: siempre que se toque código del piloto',
            '  (backend o frontend) o cambien decisiones de diseño del piloto.',
            '- **`prompts/prompt_framework_iteradores.md`**: solo si se toca el',
            '  framework Iteradores (clases `Nodo`, `Iterador`, `Controlador`,',
            '  persistencia, etc.). No cambia cuando se toca solo el piloto.',
            '- **`prompts/prompt_sistema_scripts.md`** (este): solo si cambia la forma',
            '  de trabajo en sí. Por ejemplo, cómo se entregan los scripts, cómo se',
            '  validan, cómo se estructuran las tandas.',
            '',
            '### Qué se actualiza en `prompts/prompt_piloto.md`',
        ],
    ],

    // ==========================================================
    // prompts/prompt_sistema_scripts.md — agregar apartado de "cómo se hace"
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt_sistema_scripts: como se actualiza el prompt',
        'buscar' => [
            '**Cómo se actualiza:** con bloques `tipo => \'reemplazar\'` chicos, sobre',
            'las secciones puntuales que cambian. No se reescribe el prompt entero.',
            'Los bloques de reemplazo tienen que buscar exactamente el texto actual.',
            'Si un bloque falla, se ajusta el `buscar`.',
        ],
        'reemplazar' => [
            '### Cómo se actualiza',
            '',
            '- Con bloques `tipo => \'reemplazar\'` chicos, sobre las secciones',
            '  puntuales que cambian. **No se reescribe el prompt entero.**',
            '- Los bloques de reemplazo tienen que buscar exactamente el texto actual.',
            '  Si un bloque falla, se ajusta el `buscar`.',
            '- Cuando el bloque es chico y estable, se puede usar el mismo script.',
            '  Cuando el cambio es grande (por ejemplo, reorganizar todo), se puede',
            '  usar `tipo => \'crear\'` para sobrescribir el archivo entero. Es la',
            '  excepción, no la regla.',
            '- Los cambios al prompt van siempre en el mismo `aplicar_cambios.php`',
            '  que los cambios de código. Nunca en un script aparte.',
            '',
            '### Excepción: cuando el cambio afecta a los tres prompts',
            '',
            'Si el cambio toca el framework Y el piloto (por ejemplo, una nueva',
            'versión del framework que agrega funcionalidad y el piloto la usa),',
            'se actualizan los tres prompts. Es poco común, pero pasa.',
        ],
    ],

    // ==========================================================
    // prompts/prompt_sistema_scripts.md — actualizar discusión actual
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt_sistema_scripts: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.72. Se dejó explícito',
            'que siempre que se toca código, el `aplicar_cambios.php` actualiza el',
            'prompt del piloto con bloques `reemplazar` chicos.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.72a. Se reforzó la',
            'regla: cada vez que se modifica código o se actualiza la forma de',
            'trabajo, se actualizan los prompts con el mismo `aplicar_cambios.php`.',
            'Se agregaron criterios sobre cuándo se actualiza cada prompt.',
        ],
    ],

    // ==========================================================
    // prompts/prompt_sistema_scripts.md — actualizar estado
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt_sistema_scripts: actualizar estado',
        'buscar' => [
            '**Estado:**',
            '',
            '- El sistema de scripts funciona bien. No hay cambios de fondo pendientes.',
            '- Lo único nuevo es la costumbre de actualizar los prompts al cerrar cada tanda.',
            '  Eso ya está documentado en la sección "Actualización de los prompts al cerrar cada',
            '  tanda".',
        ],
        'reemplazar' => [
            '**Estado:**',
            '',
            '- El sistema de scripts funciona bien. No hay cambios de fondo pendientes.',
            '- La regla de actualizar prompts con cada cambio está documentada en la',
            '  sección "Actualización de los prompts con cada cambio".',
            '- Los bloques de reemplazo sobre los prompts son chicos y estables. En',
            '  general se puede hacer todo en el mismo `aplicar_cambios.php`.',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — reforzar nota del encabezado
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: reforzar nota de actualizacion',
        'buscar' => [
            'Se actualiza con cada tanda. La sección **Discusión actual** (al final) es',
            'la fuente de verdad sobre dónde quedamos.',
        ],
        'reemplazar' => [
            'Se actualiza con **cada cambio de código**, no solo al cerrar una tanda.',
            'La sección **Discusión actual** (al final) es la fuente de verdad sobre',
            'dónde quedamos.',
            '',
            'Cuando se entrega un `aplicar_cambios.php` que toca código del piloto, ese',
            'mismo script incluye los bloques que actualizan este archivo. Ver',
            '`prompts/prompt_sistema_scripts.md` para el detalle de la regla.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (regla de actualizar prompts) ===\n\n";

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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";