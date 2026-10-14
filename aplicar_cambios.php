<?php
/**
 * Aplicador — Enrutador respeta nombre_terminal del POST.
 *
 * Las acciones ventas/{cancelar,obtener,pagar_cupon,info_cancelacion}
 * usan _nombre_terminal_solicitante($nombre_solicitante) para
 * decidir el filtro. Si el solicitante es admin/soporte, esa
 * función devuelve null y la búsqueda es sin filtro. Pero la
 * venta vive en el árbol del compartido del terminal (post
 * v79a), no en el árbol del dueño. Sin filtro, no la encuentra.
 *
 * Fix: nuevo helper _nombre_terminal_para_busqueda. Si el
 * solicitante no es terminal pero es admin/soporte, respeta
 * el `nombre_terminal` del POST. Mismo criterio que
 * confirmar_venta_actual (que acepta el nombre_terminal
 * directo).
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$helper = [
    '',
    '/**',
    ' * Devuelve el nombre de terminal para usar como filtro',
    ' * en funciones que lo aceptan (buscar venta por id,',
    ' * cancelar, pagar cupón, info cancelación).',
    ' *',
    ' * Diferencias con _nombre_terminal_solicitante:',
    ' *   - Solicitante terminal: su propio nombre (ignora el',
    ' *     POST; un terminal no puede operar sobre ventas',
    ' *     ajenas).',
    ' *   - Solicitante admin o soporte: el `nombre_terminal`',
    ' *     del POST si viene. El admin puede operar en nombre',
    ' *     de cualquier terminal (mismo criterio que',
    ' *     confirmar_venta_actual).',
    ' *   - Cualquier otro caso: null.',
    ' *',
    ' * @param string $nombre_solicitante',
    ' * @param array  $post',
    ' * @return string|null',
    ' * @since 1.5piloto.79i',
    ' */',
    'function _nombre_terminal_para_busqueda(string $nombre_solicitante, array $post): ?string {',
    '    $nt = _nombre_terminal_solicitante($nombre_solicitante);',
    '    if ($nt !== null) return $nt;',
    '',
    '    if ($nombre_solicitante === \'\') return null;',
    '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
    '    if (!$nodo_sol) return null;',
    '    $nivel = $nodo_sol->adyacente(\'nivel\');',
    '    if (!$nivel) {',
    '        $publico = $nodo_sol->adyacente(\'publico\');',
    '        if ($publico) $nivel = $publico->adyacente(\'nivel\');',
    '    }',
    '    $nivel_str = $nivel ? $nivel->dato() : \'\';',
    '    if ($nivel_str !== \'admin\' && $nivel_str !== \'soporte\') return null;',
    '',
    '    $nt_post = trim((string)($post[\'nombre_terminal\'] ?? \'\'));',
    '    return $nt_post !== \'\' ? $nt_post : null;',
    '}',
];

$cambios = [
    // 1. Insertar el helper justo después de _nombre_terminal_solicitante.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Nuevo helper _nombre_terminal_para_busqueda',
        'buscar' => [
            'function _nombre_terminal_solicitante(string $nombre_solicitante): ?string {',
            '    if ($nombre_solicitante === \'\') return null;',
            '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
            '    if (!$nodo_sol) return null;',
            '    $nivel = $nodo_sol->adyacente(\'nivel\');',
            '    if (!$nivel) {',
            '        $publico = $nodo_sol->adyacente(\'publico\');',
            '        if ($publico) $nivel = $publico->adyacente(\'nivel\');',
            '    }',
            '    if (!$nivel || $nivel->dato() !== \'terminal\') return null;',
            '    return $nombre_solicitante;',
            '}',
        ],
        'reemplazar' => array_merge(
            [
                'function _nombre_terminal_solicitante(string $nombre_solicitante): ?string {',
                '    if ($nombre_solicitante === \'\') return null;',
                '    $nodo_sol = Nodo::nodo_por_id(\'us_\' . $nombre_solicitante);',
                '    if (!$nodo_sol) return null;',
                '    $nivel = $nodo_sol->adyacente(\'nivel\');',
                '    if (!$nivel) {',
                '        $publico = $nodo_sol->adyacente(\'publico\');',
                '        if ($publico) $nivel = $publico->adyacente(\'nivel\');',
                '    }',
                '    if (!$nivel || $nivel->dato() !== \'terminal\') return null;',
                '    return $nombre_solicitante;',
                '}',
            ],
            $helper
        ),
    ],

    // 2. ventas/obtener
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'ventas/obtener usa _nombre_terminal_para_busqueda',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: si el solicitante es terminal,',
            '                    // restringir la búsqueda a su contexto.',
            '                    // Fase 1c.2/v78x: si es dueño/admin/soporte,',
            '                    // restringir al dueño para no depender de la',
            '                    // raíz `usuarios`.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $venta = obtener_venta_por_id($id_venta, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: si el solicitante es terminal,',
            '                    // restringir la búsqueda a su contexto.',
            '                    // Fase 1c.2/v78x: si es dueño/admin/soporte,',
            '                    // restringir al dueño para no depender de la',
            '                    // raíz `usuarios`.',
            '                    // v79i: si es admin/soporte, respetar el',
            '                    // nombre_terminal del POST (mismo criterio',
            '                    // que confirmar_venta_actual).',
            '                    $nombre_terminal_sol = _nombre_terminal_para_busqueda($nombre_solicitante, $post);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $venta = obtener_venta_por_id($id_venta, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
    ],

    // 3. ventas/cancelar
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'ventas/cancelar usa _nombre_terminal_para_busqueda',
        'buscar' => [
            '                case \'cancelar\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $motivo = $post[\'motivo\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño para',
            '                    // dueño/admin/soporte.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = cancelar_venta($id_venta, $motivo, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
        'reemplazar' => [
            '                case \'cancelar\':',
            '                    $id_venta = $post[\'id_venta\'] ?? \'\';',
            '                    $motivo = $post[\'motivo\'] ?? \'\';',
            '                    if (empty($id_venta)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de venta no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño para',
            '                    // dueño/admin/soporte.',
            '                    // v79i: si es admin/soporte, respetar el',
            '                    // nombre_terminal del POST.',
            '                    $nombre_terminal_sol = _nombre_terminal_para_busqueda($nombre_solicitante, $post);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = cancelar_venta($id_venta, $motivo, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
    ],

    // 4. ventas/pagar_cupon
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'ventas/pagar_cupon usa _nombre_terminal_para_busqueda',
        'buscar' => [
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = pagar_cupon_venta($id_venta, $numero_cupon, $monto, $metodo_pago, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño.',
            '                    // v79i: si es admin/soporte, respetar el',
            '                    // nombre_terminal del POST.',
            '                    $nombre_terminal_sol = _nombre_terminal_para_busqueda($nombre_solicitante, $post);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = pagar_cupon_venta($id_venta, $numero_cupon, $monto, $metodo_pago, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
    ],

    // 5. ventas/info_cancelacion
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'ventas/info_cancelacion usa _nombre_terminal_para_busqueda',
        'buscar' => [
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño.',
            '                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = obtener_info_cancelacion($id_venta, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.1: filtro opcional por terminal.',
            '                    // Fase 1c.2/v78x: filtro por dueño.',
            '                    // v79i: si es admin/soporte, respetar el',
            '                    // nombre_terminal del POST.',
            '                    $nombre_terminal_sol = _nombre_terminal_para_busqueda($nombre_solicitante, $post);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = obtener_info_cancelacion($id_venta, $nombre_terminal_sol, $nombre_dueno_sol);',
        ],
    ],

    // 6. Bump Enrutador.php.
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Bump Enrutador.php',
        'buscar' => [' * @version   1.5piloto.79h'],
        'reemplazar' => [' * @version   1.5piloto.79i'],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador — Enrutador respeta nombre_terminal del POST ===\n\n";

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