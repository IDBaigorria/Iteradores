<?php
/**
 * Aplicador — Agregar migrar_topologia_admin a ?reparar_topologia=1.
 *
 * Después de un TRUNCATE + migrar_a_sql64, la arista
 * `us_Administrador → usuarios` no se recrea. El bloque
 * ?reparar_topologia=1 repara aristas del dueño pero no la
 * del admin. Sin ella, el admin no alcanza la raíz `usuarios`
 * en carga parcial y `listar_duenos` devuelve vacío.
 *
 * Fix: invocar migrar_topologia_admin desde el bloque
 * ?reparar_topologia=1. Idempotente.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // 1. index.php: agregar migrar_topologia_admin
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: migrar_topologia_admin en ?reparar_topologia=1',
        'buscar' => [
            '    $r_comp = reparar_compartidos_dueno();',
            '    $r_viajes = reparar_viajes_dueno();',
            '    $r_emp = reparar_empresas_dueno();',
            '    $r_pas = reparar_pasajeros_dueno();',
        ],
        'reemplazar' => [
            '    // Reparar la arista `us_Administrador → usuarios`',
            '    // (v78f). Sin ella, el admin no alcanza la raíz',
            '    // `usuarios` en carga parcial y `listar_duenos`',
            '    // devuelve vacío. Se pierde al recuperar el grafo',
            '    // desde un backup viejo (TRUNCATE + migrar_a_sql64)',
            '    // porque esa migración copia fielmente lo que hay:',
            '    // si la arista no estaba en las tablas viejas, no',
            '    // se crea. v79f.',
            '    $r_admin = migrar_topologia_admin($nombre_app);',
            '',
            '    $r_comp = reparar_compartidos_dueno();',
            '    $r_viajes = reparar_viajes_dueno();',
            '    $r_emp = reparar_empresas_dueno();',
            '    $r_pas = reparar_pasajeros_dueno();',
        ],
    ],

    // ============================================================
    // 2. index.php: reportar el resultado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: reporte del admin',
        'buscar' => [
            '    echo "Reparación topológica\\n";',
            '    echo "====================\\n\\n";',
            '',
            '    echo "-- Enlaces dueño → compartido_con_* --\\n";',
        ],
        'reemplazar' => [
            '    echo "Reparación topológica\\n";',
            '    echo "====================\\n\\n";',
            '',
            '    echo "-- us_Administrador → usuarios --\\n";',
            '    if (!empty($r_admin[\'errores\'])) {',
            '        foreach ($r_admin[\'errores\'] as $e) echo "  ERROR: $e\\n";',
            '    } elseif ($r_admin[\'ya_existia\']) {',
            '        echo "  ya existía\\n";',
            '    } elseif ($r_admin[\'creado\']) {',
            '        echo "  creado\\n";',
            '    }',
            '',
            '    echo "\\n-- Enlaces dueño → compartido_con_* --\\n";',
        ],
    ],

    // ============================================================
    // 3. index.php: total_reparados incluye al admin
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: total_reparados con admin',
        'buscar' => [
            '    $total_reparados = $r_comp[\'reparados\'] + $r_viajes[\'reparados\'] + $r_emp[\'reparados\'] + $r_pas[\'reparados\'];',
        ],
        'reemplazar' => [
            '    $total_reparados = ($r_admin[\'creado\'] ? 1 : 0)',
            '        + $r_comp[\'reparados\']',
            '        + $r_viajes[\'reparados\']',
            '        + $r_emp[\'reparados\']',
            '        + $r_pas[\'reparados\'];',
        ],
    ],

    // ============================================================
    // 4. index.php: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Bump index.php a 79f',
        'buscar' => [
            ' * @version   1.5piloto.78z',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.79f',
        ],
    ],

    // ============================================================
    // 5. plan_actual.md: nota del bug
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.5.14',
        'buscar' => [
            '### 1.6 Pendientes de la línea',
        ],
        'reemplazar' => [
            '#### 1.5.14 Arista `us_Administrador → usuarios` perdida tras recuperar desde backup (v79f)',
            '',
            '**Síntoma:** logueado como admin, todas las pestañas',
            'mostraban el select de dueño vacío. Sin poder elegir',
            'dueño, ninguna pestaña funcionaba.',
            '',
            '**Causa:** la arista `us_Administrador → usuarios`',
            'no existía en el grafo. `listar_duenos` navega por',
            'esa arista para llegar a `usuarios` y enumerar los',
            'dueños. Sin ella, devolvía vacío. `usuarios` tenía',
            'el bit del admin (máscara 4) pero como residuo:',
            'en algún momento la arista existió, después se',
            'perdió, y el bitmask no se actualizó.',
            '',
            '**Cómo se perdió:** el flujo de recuperación tras',
            'un TRUNCATE + `?migrar_a_sql64=1` copia fielmente',
            'el grafo de las tablas viejas (`nodo`/`adyacente`).',
            'Si la arista no estaba en esas tablas, no se crea.',
            'El bloque `?reparar_topologia=1` (v78n) reparaba',
            'aristas del dueño pero **no** la del admin.',
            '',
            '**Fix:** agregar `migrar_topologia_admin($nombre_app)`',
            'al bloque `?reparar_topologia=1`. Idempotente. El',
            'total reportado incluye la creación si ocurrió.',
            '',
            '**Lección:** el bloque `?reparar_topologia=1` debe',
            'cubrir **todas** las aristas estructurales del',
            'modelo: admin → usuarios, dueño → compartido,',
            'viajes/empresas/pasajeros del dueño. Si una',
            'arista estructural puede perderse, debe poder',
            'repararse.',
            '',
            '### 1.6 Pendientes de la línea',
        ],
    ],

    // ============================================================
    // 6. plan_actual.md: bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: bump a 79f',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.79e.',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.79f.',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Reparar admin → usuarios ===\n\n";

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