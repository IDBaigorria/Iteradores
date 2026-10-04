<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74g — documentación.
 *
 * Actualiza el prompt del piloto con:
 * - Los bugs v74d/e/f detectados por las pruebas del plugin.
 * - Nota sobre el proyecto plugin en iteradoresJS/.
 *
 * Actualiza el prompt del sistema de scripts con:
 * - Nota sobre el sub-prompt del plugin en iteradoresJS/prompts/.
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

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74g',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74f (cerrar',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74g (solo',
            'documentación: se registran los bugs del piloto detectados',
            'por las pruebas automáticas del plugin en `iteradoresJS/`,',
            'y se aclara la relación con el proyecto plugin).',
            'Antes: v1.5piloto.74f (cerrar',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74g al historial',
        'buscar' => [
            '- **v74f**: cerrar el modal del viaje al cambiar de pestaña.',
        ],
        'reemplazar' => [
            '- **v74g**: solo documentación. Se registran los bugs del',
            '  piloto detectados por las pruebas automáticas del plugin',
            '  (`iteradoresJS/`): v74d (refresco del croquis tras cancelar',
            '  venta), v74e (condición de carrera entre el polling de',
            '  asientos y el clic), v74f (modal del viaje abierto al',
            '  cambiar de pestaña). Se aclara que el plugin es un',
            '  proyecto independiente en `iteradoresJS/` con su propio',
            '  prompt en `iteradoresJS/prompts/prompt_plugin_piloto.md`.',
            '- **v74f**: cerrar el modal del viaje al cambiar de pestaña.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado al cierre v74g',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74f (framework 1.5i.7f).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74g (framework 1.5i.7f).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: relacion con el plugin',
        'buscar' => [
            '### 1.3 Arquitectura multi-cliente (multi-aplicación)',
        ],
        'reemplazar' => [
            '### 1.3 Plugin de pruebas (proyecto separado)',
            '',
            'El piloto tiene un **plugin de pruebas automatizadas** que vive',
            'en un proyecto separado: `iteradoresJS/`. Es una extensión de',
            'Chrome (MV3) que corre pruebas contra la página del piloto,',
            'usando el framework Iteradores JS para persistir sus',
            'resultados.',
            '',
            'El plugin tiene su propio prompt,',
            '`iteradoresJS/prompts/prompt_plugin_piloto.md`, que se',
            'actualiza con cada tanda de código del plugin. **Este prompt',
            '(el del piloto) no se toca cuando se toca solo el plugin.**',
            '',
            'Bugs del piloto descubiertos por las pruebas del plugin:',
            '- v74d: refresco del croquis tras cancelar venta.',
            '- v74e: condición de carrera entre el polling de asientos y',
            '  el clic.',
            '- v74f: modal del viaje abierto al cambiar de pestaña.',
            '',
            '### 1.4 Arquitectura multi-cliente (multi-aplicación)',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: nota sobre el sub-prompt del plugin',
        'buscar' => [
            '**Los prompts viven únicamente en el proyecto PHP** (`prompts/`).',
            'El proyecto JS no tiene su propia copia de los prompts. Cuando se',
            'actualiza un prompt por un cambio en el framework, se hace en el',
            '`aplicar_cambios.php` del proyecto PHP.',
        ],
        'reemplazar' => [
            '**Los prompts del framework, del piloto y del sistema de scripts**',
            'viven en el proyecto PHP (`prompts/`). El proyecto `iteradoresJS/`',
            'tiene, además, su propio sub-prompt en',
            '`iteradoresJS/prompts/prompt_plugin_piloto.md`, que se actualiza',
            'con el `aplicar_cambios.php` del proyecto JS cuando se toca',
            'solo el plugin.',
            '',
            '**Regla de oro:** si el cambio toca el **framework** en cualquiera',
            'de los dos lenguajes, se actualizan los prompts del proyecto PHP',
            'y, si aplica, el sub-prompt del plugin. Si el cambio toca solo el',
            '**piloto PHP**, se actualiza `prompt_piloto.md`. Si toca solo el',
            '**plugin JS**, se actualiza `prompt_plugin_piloto.md` en',
            '`iteradoresJS/`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: nota extendida del plugin',
        'buscar' => [
            '**Proyecto nuevo: plugin de Chrome sobre Iteradores JS.**',
        ],
        'reemplazar' => [
            '**Proyecto plugin: estado (v1.5plugin.4m).**',
            '',
            'El plugin tiene 17 pruebas agrupadas en secciones (base,',
            'ventas). La ventana del popup tiene un botón "Correr todas"',
            'por sección. Los `aplicar_cambios.php` del proyecto JS se',
            'corren parados en `iteradoresJS/` y usan el mismo runner.',
            'El sub-prompt del plugin es',
            '`iteradoresJS/prompts/prompt_plugin_piloto.md`.',
            '',
            '**Proyecto nuevo: plugin de Chrome sobre Iteradores JS.**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: ultima actualizacion a v74g',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74c. Se',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74g. Se',
            'actualizó la nota sobre el proyecto plugin con el estado',
            'actual y la regla de qué prompt tocar según el proyecto.',
            'Antes: v1.5piloto.74c. Se',
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