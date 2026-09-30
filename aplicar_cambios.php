<?php
/**
 * Aplicador de cambios automáticos — AdministradorDeViajes.
 *
 * Tanda v73j-bis: bumps faltantes + reglas nuevas en el prompt del
 * sistema de scripts.
 *
 * - aplicacion_GET.html: bump de admin.js y terminales.js a 73j.
 * - prompts/prompt_sistema_scripts.md: reglas sobre bumps obligatorios
 *   y vigencia de archivos durante la conversación.
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
    // aplicacion_GET.html
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de admin.js a 1.5piloto.73j',
        'buscar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73h"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73j"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de terminales.js a 1.5piloto.73j',
        'buscar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.73g"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.73j"></script>',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Reforzar seccion de bumps obligatorios',
        'buscar' => [
            '## CÓMO MENCIONAR LOS BUMPS DE VERSIÓN',
            '',
            '- **Actualizá el `@version` de cada archivo modificado.**',
            '- **Actualizá los `?v=` en HTML.**',
            '- **Nombrá la versión en el título del script.**',
            '- **No bumpees archivos que no cambian.**',
            '- **Los CSS no tienen `@version`.** Se bumpean solo desde el `?v=` del HTML.',
        ],
        'reemplazar' => [
            '## CÓMO MENCIONAR LOS BUMPS DE VERSIÓN',
            '',
            '**Regla sin excepción: en CADA tanda se bumpean TODOS los archivos',
            'que se modifican, tanto en el `@version` interno como en el `?v=` del',
            'HTML.**',
            '',
            '- **Actualizá el `@version` de cada archivo modificado.**',
            '- **Actualizá los `?v=` en HTML.** Si no tenés el HTML a mano,',
            '  pedilo ANTES de entregar el script. No entregues una tanda con',
            '  bumps incompletos.',
            '- **Nombrá la versión en el título del script.**',
            '- **No bumpees archivos que no cambian.**',
            '- **Los CSS no tienen `@version`.** Se bumpean solo desde el `?v=` del HTML.',
            '- **Antes de entregar, revisá el listado de cambios de la tanda.**',
            '  Cada archivo que aparece en `$cambios` tiene que estar bumpeado,',
            '  tanto adentro como en el HTML.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Agregar regla sobre vigencia de archivos',
        'buscar' => [
            '## CÓMO MENCIONAR LO QUE HACE FALTA DE PARTE DEL USUARIO',
            '',
            'Antes de escribir el script, si necesitás ver archivos actualizados, pedilos',
            'explícitamente. **Nunca asumas que un archivo no cambió.**',
        ],
        'reemplazar' => [
            '## CÓMO MENCIONAR LO QUE HACE FALTA DE PARTE DEL USUARIO',
            '',
            'Antes de escribir el script, si necesitás ver archivos actualizados, pedilos',
            'explícitamente. **Nunca asumas que un archivo no cambió.**',
            '',
            '**Regla del entorno de trabajo:** durante la conversación, el usuario NO',
            'modifica archivos por su cuenta. Si te pasó un archivo al principio de',
            'la sesión, ese archivo está vigente hasta que él te diga lo contrario.',
            'Esto significa que:',
            '',
            '- **Las versiones de los archivos que tenés son siempre las últimas.**',
            '  No hace falta volver a pedirlas "por si acaso".',
            '- **Igual conviene pedir los archivos que vas a tocar si no los tenés',
            '  a mano.** Especialmente los HTML, porque los `?v=` viven ahí y son',
            '  fáciles de olvidar.',
            '- **Si el usuario cambia algo, te lo avisa.** Vos no tenés que',
            '  preguntar en cada tanda.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Actualizar Discusion actual del prompt de scripts',
        'buscar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.72a. Se reforzó la',
            'regla: cada vez que se modifica código o se actualiza la forma de',
            'trabajo, se actualizan los prompts con el mismo `aplicar_cambios.php`.',
            'Se agregaron criterios sobre cuándo se actualiza cada prompt.',
        ],
        'reemplazar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.73j. Se agregaron',
            'dos reglas al método de trabajo:',
            '',
            '1. **Bumps obligatorios siempre.** En cada tanda se bumpean todos los',
            '   archivos modificados (tanto `@version` internos como `?v=` en HTML).',
            '   Si no se tiene el HTML a mano, se pide antes de entregar el script.',
            '2. **Vigencia de los archivos.** Durante la conversación el usuario no',
            '   modifica archivos por su cuenta. Las versiones que el asistente',
            '   tiene son siempre las últimas. Si el usuario cambia algo, lo avisa.',
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