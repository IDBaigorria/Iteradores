<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74b — solo documentación.
 *
 * Registra en los prompts la decisión de arrancar un segundo piloto:
 * un plugin de Chrome (MV3) sobre el framework Iteradores JS. Vive
 * dentro de iteradoresJS/, en Aplicacion/. Tiene su propia carpeta
 * prompts/ con un prompt del plugin. Los scripts de aplicación de
 * cambios se ejecutan con el mismo flujo, parados en el directorio
 * de iteradoresJS/.
 *
 * No toca código.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
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

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74b',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74a (fix del',
            'retroactivo: la config nueva se resuelve en vivo, no desde el',
            '`opciones_cobro` ya congelado de la venta).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74b. Se dejó',
            'asentado el arranque de un segundo piloto: un plugin de Chrome',
            '(manifest v3) que usa el framework Iteradores JS y corre pruebas',
            'automatizadas sobre la página del piloto PHP. Vive dentro del',
            'proyecto `iteradoresJS/`, en una carpeta `Aplicacion/`. Tiene su',
            'propia carpeta `prompts/` (con un único archivo por ahora,',
            '`prompt_plugin_piloto.md`). Los scripts de aplicación de cambios',
            'se ejecutan con el mismo flujo, parados en el directorio del',
            'proyecto `iteradoresJS/`. El prompt del framework Iteradores y',
            'este prompt siguen viviendo en el proyecto PHP.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74b',
        'buscar' => [
            '- Cerramos en v74a el fix del retroactivo: la config nueva se',
            '  resuelve en vivo (viaje + terminal), ignorando el',
            '  `opciones_cobro` que ya tiene la venta. El flujo de migración',
            '  de ventas viejas no estaba afectado.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v74a el fix del retroactivo: la config nueva se',
            '  resuelve en vivo (viaje + terminal), ignorando el',
            '  `opciones_cobro` que ya tiene la venta. El flujo de migración',
            '  de ventas viejas no estaba afectado. Todas las pruebas del',
            '  Bug 1 pasaron.',
            '- Arrancamos el diseño de un **segundo piloto**: un plugin de',
            '  Chrome (manifest v3) que corre pruebas automatizadas sobre la',
            '  página del piloto PHP. Vive dentro del proyecto `iteradoresJS/`,',
            '  en una nueva carpeta `Aplicacion/`. Usa el framework Iteradores',
            '  JS para persistir su propia info (IndexedDB del contexto de la',
            '  extensión). El botón play del plugin dispara un script que',
            '  escribe el asistente, que actúa sobre la página del piloto.',
            '  Aplica el mismo flujo de trabajo: scripts PHP de aplicación de',
            '  cambios, ejecutados en el directorio del proyecto `iteradoresJS/`,',
            '  bump de versiones y actualización del prompt del plugin.',
            '- El proyecto `iteradoresJS/` tiene su propia carpeta `prompts/`,',
            '  con un único archivo por ahora: `prompt_plugin_piloto.md`. El',
            '  prompt del framework Iteradores y el del sistema de scripts',
            '  siguen viviendo en el proyecto PHP.',
            '- Pendiente: ver los archivos del framework JS para diseñar el',
            '  plugin (estructura, pruebas iniciales, sistema de persistencia).',
            '- No hay tandas de código en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado del proyecto al cierre v74b',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74a (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74b (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes. Arranca el diseño del segundo piloto: plugin de',
            'Chrome sobre el framework Iteradores JS, para automatizar pruebas.',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: nota sobre el proyecto plugin',
        'buscar' => [
            '**Para el asistente de la próxima sesión:**',
            '',
            '- Si vas a cerrar una tanda, además del código, actualizá el prompt de continuidad.',
            '- La sección "Discusión actual" del prompt de continuidad es la fuente de verdad',
            '  sobre dónde quedamos.',
            '- Este prompt (el de scripts) casi no se toca. Solo si cambia el método de trabajo.',
        ],
        'reemplazar' => [
            '**Proyecto nuevo: plugin de Chrome sobre Iteradores JS.**',
            '',
            'A partir de v1.5piloto.74b arranca un segundo piloto: un plugin',
            'de Chrome (manifest v3) que usa el framework Iteradores JS para',
            'persistir su propia info y corre pruebas automatizadas sobre la',
            'página del piloto PHP. Vive dentro del proyecto `iteradoresJS/`,',
            'en una carpeta `Aplicacion/`. Tiene su propia carpeta `prompts/`',
            'con un único archivo por ahora: `prompt_plugin_piloto.md`.',
            '',
            'Los scripts de aplicación de cambios se ejecutan con el mismo',
            'flujo y el mismo runner, pero parados en el directorio del',
            'proyecto `iteradoresJS/`. Los prompts del framework y del',
            'sistema de scripts siguen viviendo en el proyecto PHP.',
            '',
            '**Para el asistente de la próxima sesión:**',
            '',
            '- Si vas a cerrar una tanda, además del código, actualizá el prompt de continuidad.',
            '- La sección "Discusión actual" del prompt de continuidad es la fuente de verdad',
            '  sobre dónde quedamos.',
            '- Este prompt (el de scripts) casi no se toca. Solo si cambia el método de trabajo.',
            '- Si la tanda es sobre el plugin JS, actualizá `iteradoresJS/prompts/prompt_plugin_piloto.md`',
            '  en vez de (o además de) este prompt.',
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