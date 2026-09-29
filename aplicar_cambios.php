<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73d: soporte ve los selectores de dueño en todas las pestañas.
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
    // aplicacion.js — bump y helper es_admin_o_soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73d',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: helper es_admin_o_soporte',
        'buscar' => [
            '// Utilidades',
            'const $ = s => document.querySelector(s);',
            'const $$ = s => document.querySelectorAll(s);',
        ],
        'reemplazar' => [
            '// Utilidades',
            'const $ = s => document.querySelector(s);',
            'const $$ = s => document.querySelectorAll(s);',
            '',
            '/**',
            ' * Devuelve true si el usuario actual es admin o soporte.',
            ' * Se usa en lugar de comparar directamente con "admin", porque',
            ' * el soporte tiene los mismos permisos sobre sus dueños asignados.',
            ' */',
            'function es_admin_o_soporte() {',
            '    if (!usuario_actual) return false;',
            '    return usuario_actual.nivel === \'admin\' || usuario_actual.nivel === \'soporte\';',
            '}',
        ],
    ],

    // ==========================================================
    // Reemplazos en cada archivo JS: usuario_actual.nivel === 'admin'
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/liquidaciones.js',
        'descripcion' => 'liquidaciones: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'rendiciones: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas: admin -> es_admin_o_soporte (multiples)',
        'permitir_multiples' => true,
        'buscar' => [
            'usuario_actual.nivel === \'admin\'',
        ],
        'reemplazar' => [
            'es_admin_o_soporte()',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html — bumps de version
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de aplicacion.js',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.73"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de viajes-nucleo.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de viajes-asientos.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de micros.js',
        'buscar' => [
            '<script src="Aplicacion/micros.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/micros.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de ventas.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.66"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de rendiciones.js',
        'buscar' => [
            '<script src="Aplicacion/rendiciones.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/rendiciones.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de liquidaciones.js',
        'buscar' => [
            '<script src="Aplicacion/liquidaciones.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/liquidaciones.js?v=1.5piloto.73d"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de pasajeros.js',
        'buscar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.66"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.73d"></script>',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73c (fix: opción',
            'soporte en el select de nivel al editar).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73d (soporte ve',
            'los selectores de dueño en todas las pestañas).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73d al historial',
        'buscar' => [
            '- **v73c**: opción "Soporte" agregada al select de nivel de edición.',
            '  El select queda deshabilitado cuando el usuario editado es un',
            '  soporte, porque el nivel no se puede cambiar.',
        ],
        'reemplazar' => [
            '- **v73c**: opción "Soporte" agregada al select de nivel de edición.',
            '  El select queda deshabilitado cuando el usuario editado es un',
            '  soporte, porque el nivel no se puede cambiar.',
            '- **v73d**: helper `es_admin_o_soporte()` en aplicacion.js. Todos los',
            '  JS que chequeaban `usuario_actual.nivel === \'admin\'` ahora usan el',
            '  helper, así el soporte ve los selectores de dueño en todas las',
            '  pestañas.',
        ],
    ],

];

// ============================================================
// Runner (con soporte para permitir_multiples)
// ============================================================

echo "=== Aplicador de cambios (soporte ve selectores de dueno) ===\n\n";

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
        $permitir_multiples = !empty($cambio['permitir_multiples']);

        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1 && !$permitir_multiples) {
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