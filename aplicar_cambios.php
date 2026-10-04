<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74i (solo documentación):
 * - Incorpora al prompt del sistema de scripts dos reglas nuevas del
 *   método de trabajo:
 *     1. Cada aplicar_cambios.php va acompañado de un commit sugerido.
 *     2. Cada aplicar_cambios.php del piloto PHP va acompañado de otro
 *        aplicar_cambios.php del proyecto JS (iteradoresJS/) con las
 *        pruebas que verifiquen los cambios del piloto.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // --------------------------------------------------------
    // §APRENDIZAJES A LA FUERZA — agregar puntos 13 y 14
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Aprendizajes: agregar puntos 13 y 14',
        'buscar' => [
            '12. **No asumir la estructura de un archivo.** Pedirlo siempre',
            '    antes de tocarlo, aunque parezca conocido.',
            '',
            '---',
            '',
            '## RECORDATORIOS FINALES',
        ],
        'reemplazar' => [
            '12. **No asumir la estructura de un archivo.** Pedirlo siempre',
            '    antes de tocarlo, aunque parezca conocido.',
            '13. **Cada `aplicar_cambios.php` va acompañado de un commit',
            '    sugerido.** Siempre, sin excepción, en cualquiera de los',
            '    dos proyectos. El commit arranca con `V1.5piloto.XX:` o',
            '    `V1.5plugin.XX:` según corresponda, y separa los cambios',
            '    en las secciones conocidas (Servidor, Interfaz,',
            '    Documentación, Plugin, etc.).',
            '14. **Cada cambio al piloto PHP lleva su espejo de pruebas en',
            '    el plugin JS.** Cuando la tanda toca el piloto (backend o',
            '    frontend), se entrega además un `aplicar_cambios.php` para',
            '    `iteradoresJS/` que agregue las pruebas del plugin que',
            '    verifiquen los cambios. Dos scripts, dos commits, dos',
            '    repos. La única excepción es cuando el cambio del piloto',
            '    no es verificable desde el plugin (por ejemplo, cambios',
            '    de estilo visual interno o refactors sin cambio de',
            '    comportamiento). Aun así, avisar al usuario que no se',
            '    agregan pruebas y por qué.',
            '',
            '---',
            '',
            '## RECORDATORIOS FINALES',
        ],
    ],

    // --------------------------------------------------------
    // §RECORDATORIOS FINALES — agregar los dos nuevos
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Recordatorios finales: agregar commit + espejo de pruebas',
        'buscar' => [
            '- No escribas código sin consensuar primero.',
            '- No asumas la estructura de un archivo. Pedilo.',
            '- Un script, autocontenido, completo.',
            '- Bloques chicos y específicos.',
            '- Modo estricto, sin backups.',
            '- Bump de versiones siempre.',
            '- Actualizar prompts al cerrar cada tanda.',
        ],
        'reemplazar' => [
            '- No escribas código sin consensuar primero.',
            '- No asumas la estructura de un archivo. Pedilo.',
            '- Un script, autocontenido, completo.',
            '- Bloques chicos y específicos.',
            '- Modo estricto, sin backups.',
            '- Bump de versiones siempre.',
            '- Actualizar prompts al cerrar cada tanda.',
            '- Cada `aplicar_cambios.php` va con un commit sugerido.',
            '- Cada cambio al piloto lleva su espejo de pruebas del plugin',
            '  en `iteradoresJS/` (salvo excepción justificada).',
        ],
    ],

    // --------------------------------------------------------
    // §DISCUSIÓN ACTUAL — registrar las reglas nuevas
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Discusion actual: version + reglas nuevas',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74g. Se',
            'actualizó la nota sobre el proyecto plugin con el estado',
            'actual y la regla de qué prompt tocar según el proyecto.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74i. Se',
            'incorporan dos reglas nuevas al método de trabajo:',
            '(13) cada `aplicar_cambios.php` va acompañado de un commit',
            'sugerido; (14) cada cambio al piloto PHP lleva su espejo de',
            'pruebas en el plugin JS de `iteradoresJS/`, con dos scripts y',
            'dos commits.',
            'Antes: v1.5piloto.74g. Se',
            'actualizó la nota sobre el proyecto plugin con el estado',
            'actual y la regla de qué prompt tocar según el proyecto.',
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