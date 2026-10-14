<?php
/**
 * Aplicador — Limpieza de comentarios con el prefijo viejo.
 *
 * Los greps de auditoría confirman que el código está limpio
 * (ya no quedan filtros con `compartido_con_us_`). Solo hay
 * comentarios y descripciones que mencionan el prefijo viejo,
 * y podrían inducir a error en el futuro. Los corregimos.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // Comandos.php: 3 comentarios
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comentario en Comandos.php:174',
        'buscar' => [
            '    // Crea el contenedor `compartido_con_us_termX` en cada',
        ],
        'reemplazar' => [
            '    // Crea el contenedor `compartido_con_<nombre_terminal>` en cada',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comentario en Comandos.php:178',
        'buscar' => [
            '    //   us_duenoY/compartido_con_us_termX   (dato=nombre_dueno)',
        ],
        'reemplazar' => [
            '    //   us_duenoY/compartido_con_<terminal>   (dato=nombre_dueno)',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comentario en Comandos.php:456',
        'buscar' => [
            '    // parametrizados. Para cada compartido `compartido_con_us_termX`',
        ],
        'reemplazar' => [
            '    // parametrizados. Para cada compartido `compartido_con_<terminal>`',
        ],
    ],

    // ============================================================
    // Registro.php: 2 descripciones
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Registro.php',
        'descripcion' => 'Descripción en Registro.php:40',
        'buscar' => [
            '            \'descripcion\' => \'Crea el contenedor compartido_con_us_termX en cada dueño con terminales autorizados.\',',
        ],
        'reemplazar' => [
            '            \'descripcion\' => \'Crea el contenedor compartido_con_<terminal> en cada dueño con terminales autorizados.\',',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Registro.php',
        'descripcion' => 'Descripción en Registro.php:52',
        'buscar' => [
            '            \'descripcion\' => \'Cambia el enlace `dueno` de cada terminal para que apunte al contenedor `compartido_con_us_termX` en vez del nodo del dueño real. Activa el aislamiento por topología.\',',
        ],
        'reemplazar' => [
            '            \'descripcion\' => \'Cambia el enlace `dueno` de cada terminal para que apunte al contenedor `compartido_con_<terminal>` en vez del nodo del dueño real. Activa el aislamiento por topología.\',',
        ],
    ],

    // ============================================================
    // Compartidos.php: 1 comentario
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Compartidos.php',
        'descripcion' => 'Comentario en Compartidos.php:6',
        'buscar' => [
            ' * (`compartido_con_us_termX`) tienen que reflejar en cada momento',
        ],
        'reemplazar' => [
            ' * (`compartido_con_<terminal>`) tienen que reflejar en cada momento',
        ],
    ],

    // ============================================================
    // Bumps
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Bump Comandos.php',
        'buscar' => [
            ' * @version 1.5piloto.77y',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.79b',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Limpieza de comentarios ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
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
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";