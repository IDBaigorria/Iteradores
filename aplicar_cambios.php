<?php
/**
 * Aplicador de cambios — Proyecto iteradores.
 *
 * Tanda V1.5piloto.77x (fix de viajes-micros.js tras B3.3d).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // 1. viajes-micros.js — fix del POST vehiculos/listar
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros: manda nombre_dueno en vehiculos/listar',
        'buscar' => [
            '            const respV = await fetch("index.php", {',
            '                method: "POST",',
            '                headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '                body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa: selectEmpresa.value })',
            '            });',
        ],
        'reemplazar' => [
            '            const respV = await fetch("index.php", {',
            '                method: "POST",',
            '                headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '                // Fase B3.3d: el endpoint vehiculos/listar ahora',
            '                // requiere nombre_dueno (resuelve la empresa por',
            '                // us_<nombre> en lugar de iterar sobre todos los',
            '                // usuarios).',
            '                body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa: selectEmpresa.value, nombre_dueno })',
            '            });',
        ],
    ],

    // ============================================================
    // 2. viajes-micros.js — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros: bump a 1.5piloto.77x',
        'buscar' => [
            ' * @version 1.5piloto.74k',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.77x',
        ],
    ],

    // ============================================================
    // 3. aplicacion_GET.html — bump ?v= de viajes-micros.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de viajes-micros.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.74k"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.77x"></script>',
        ],
    ],

    // ============================================================
    // 4. plan_actual.md — tanda actual a v77x
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77x',
        'buscar' => [
            '**Tanda actual:** v77w (fix de `subir_foto_vehiculo`).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77x (fix de `viajes-micros.js` tras B3.3d).',
            '',
            '**Fix v77x.** El modal "Agregar micro" del viaje llama a',
            '`vehiculos/listar` sin `nombre_dueno`. Como ese parámetro',
            'pasó a ser requerido en B3.3d, el endpoint devolvía',
            '`"Dueño no especificado"` y el select de vehículos quedaba',
            'vacío. Fix de una línea en `viajes-micros.js`.',
            '',
            '**B3.3d (v77v).** `Vehiculo.php`: las 6 funciones reciben',
            '`$nombre_dueno` y resuelven por `us_<nombre>` en lugar de',
            'iterar sobre todos los dueños. `Enrutador.php`: las 6',
            'acciones de `vehiculos/*` pasan el dueño. `micros.js`:',
            'nuevo helper `_obtener_nombre_dueno_micros()` y las 5',
            'llamadas mandan `nombre_dueno`.',
            '',
            '**B3.3e y B3.3c siguen pendientes.** B3.3e: alinear',
            '`Migraciones/*` con `por_cada_nodo_ejecutar`. B3.3c:',
            'helpers que iteran sobre todos los dueños buscando por',
            'id (requieren rediseño).',
        ],
    ],

    // ============================================================
    // 5. prompt_piloto.md — agregar v77x
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77x',
        'buscar' => [
            '- **v77w**: fix del `continue` huérfano en',
        ],
        'reemplazar' => [
            '- **v77x**: fix de `viajes-micros.js`. El POST a',
            '  `vehiculos/listar` desde el modal "Agregar micro" no',
            '  mandaba `nombre_dueno`, que pasó a ser requerido en',
            '  B3.3d. El select de vehículos quedaba vacío con error',
            '  `"Dueño no especificado"`. Fix de una línea.',
            '- **v77w**: fix del `continue` huérfano en',
        ],
    ],

    // ============================================================
    // 6. index.php — bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77x',
        'buscar' => [
            ' * @version   1.5piloto.77w',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77x',
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