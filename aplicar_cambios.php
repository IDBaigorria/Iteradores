<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76u (Fase B2.2.4 del modelo topológico).
 *   - Empresa.php: contexto opcional en listar_empresas_de_dueno.
 *     Las funciones de alta/edición/baja no reciben contexto: son
 *     operaciones del dueño (navega por la raíz `usuarios`).
 *   - index.php: bump.
 *   - prompts/prompt_piloto.md: registro.
 *
 * Refactor sin cambio de comportamiento. Cierra B2.2.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Empresa.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: bump @version a 1.5piloto.76u',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.74y',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.76u',
        ],
    ],

    // ============================================================
    // Empresa.php — listar_empresas_de_dueno con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: listar_empresas_de_dueno con contexto',
        'buscar' => [
            'function listar_empresas_de_dueno(string $nombre_dueno): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [];',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return [];',
            '',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [];',
        ],
        'reemplazar' => [
            'function listar_empresas_de_dueno(string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.2.4 (v76u): contexto opcional. Si viene, navega',
            '    // por ahí en lugar de resolver `usuarios → dueño`. Mismo',
            '    // patrón que Viaje y Venta. Lo usa el terminal para ver',
            '    // solo las empresas compartidas.',
            '    if ($nodo_contexto === null) {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) return [];',
            '        $nodo_contexto = $raiz_usuarios->adyacente($nombre_dueno);',
            '    }',
            '    if (!$nodo_contexto) return [];',
            '',
            '    $nodo_empresas = $nodo_contexto->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [];',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76u',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76t',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76u',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76t',
            '(Fase B2.2.3 del modelo topológico: ViajeAsientos.php.',
            'Contexto opcional (`?Nodo $nodo_contexto`) en las funciones',
            'que navegan por el contenedor de viajes del dueño:',
            '`_dni_asignado_en_viaje`, `reservar_asiento_micro`,',
            '`asignar_pasajero_a_reserva`, `liberar_reserva_asiento_micro`,',
            '`obtener_estados_asientos_micro`. `seleccionar_asiento_micro`',
            'y `deseleccionar_asiento_micro` resuelven su contexto vía',
            '`_contexto_terminal($nombre_terminal)`. Refactor sin cambio',
            'de comportamiento: el enrutador todavía no pasa el contexto.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76u',
            '(Fase B2.2.4 del modelo topológico: Empresa.php. Contexto',
            'opcional en `listar_empresas_de_dueno`. Las funciones de',
            'alta/edición/baja no reciben contexto: son operaciones del',
            'dueño y navegan por la raíz `usuarios`. Cierra la Fase B2.2',
            'completa (Viaje, Venta, ViajeAsientos, Empresa). Próximo paso:',
            'B2.3 — repuntar `us_termX → dueno` al contenedor compartido y',
            'empezar a pasar el contexto desde el enrutador.).',
            'Antes: v1.5piloto.76t',
            '(Fase B2.2.3 del modelo topológico: ViajeAsientos.php.',
            'Contexto opcional (`?Nodo $nodo_contexto`) en las funciones',
            'que navegan por el contenedor de viajes del dueño:',
            '`_dni_asignado_en_viaje`, `reservar_asiento_micro`,',
            '`asignar_pasajero_a_reserva`, `liberar_reserva_asiento_micro`,',
            '`obtener_estados_asientos_micro`. `seleccionar_asiento_micro`',
            'y `deseleccionar_asiento_micro` resuelven su contexto vía',
            '`_contexto_terminal($nombre_terminal)`. Refactor sin cambio',
            'de comportamiento: el enrutador todavía no pasa el contexto.).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76u',
        'buscar' => [
            '- **v76t**: Fase B2.2.3 del modelo topológico',
        ],
        'reemplazar' => [
            '- **v76u**: Fase B2.2.4 del modelo topológico (Empresa.php).',
            '  Contexto opcional (`?Nodo $nodo_contexto`) en',
            '  `listar_empresas_de_dueno`. Las funciones de alta, edición',
            '  y baja no reciben contexto: son operaciones del dueño y',
            '  siguen navegando por la raíz `usuarios`. Con esto queda',
            '  cerrada la Fase B2.2 completa (Viaje, Venta, ViajeAsientos,',
            '  Empresa): todas las funciones que navegan por contenedores',
            '  del dueño aceptan contexto, pero el enrutador todavía no',
            '  lo pasa. Próximo paso: B2.3 (repuntar).',
            '- **v76t**: Fase B2.2.3 del modelo topológico',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 bullet
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet v76u',
        'buscar' => [
            '- Cerramos en v76t la Fase B2.2.3 del modelo topológico',
        ],
        'reemplazar' => [
            '- Cerramos en v76u la Fase B2.2.4 del modelo topológico',
            '  (Empresa.php) y con eso **la Fase B2.2 completa**. Contexto',
            '  opcional en `listar_empresas_de_dueno`. Las funciones de',
            '  alta, edición y baja no reciben contexto: son operaciones',
            '  del dueño. Estado: todas las funciones del piloto que',
            '  navegan por contenedores del dueño aceptan `?Nodo $nodo_contexto`,',
            '  pero el enrutador todavía no lo pasa. Refactor sin cambio',
            '  de comportamiento. Próximo: B2.3 (repuntar',
            '  `us_termX → dueno` al contenedor compartido y pasar el',
            '  contexto desde el enrutador).',
            '- Cerramos en v76t la Fase B2.2.3 del modelo topológico',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §13 estado
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado al cierre a v76u',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76t (framework 1.5i.7l).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76u (framework 1.5i.7l).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76u al bloque de estado',
        'buscar' => [
            'v76t: Fase B2.2.3 (ViajeAsientos.php). Contexto opcional en',
        ],
        'reemplazar' => [
            'v76u: Fase B2.2.4 (Empresa.php). Contexto opcional en',
            '`listar_empresas_de_dueno`. Con esto queda cerrada la Fase',
            'B2.2 completa: todas las funciones del piloto que navegan',
            'por contenedores del dueño aceptan contexto.',
            'v76t: Fase B2.2.3 (ViajeAsientos.php). Contexto opcional en',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
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