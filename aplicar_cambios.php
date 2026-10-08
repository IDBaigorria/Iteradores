<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5i.7k (solo prompt). Documenta la trampa de tipos
 * de ID en el Map de superestructura del espejo JS.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => '§12.3: agregar trampa de IDs y claves de Map',
        'buscar' => [
            '**Resuelto** en v73r (PHP) y V1.5i.7f (JS).',
        ],
        'reemplazar' => [
            '**Resuelto** en v73r (PHP) y V1.5i.7f (JS).',
            '',
            '**IDs y claves de Map en JS.** En PHP, `isset($arr["1"])`',
            'funciona aunque la clave original sea el int `1`, porque PHP',
            'coerciona strings numéricos a int en claves de array. En JS,',
            '`Map.has("1")` y `Map.has(1)` son distintos. Regla: normalizar',
            'a `String(id)` todas las claves del `Map` de',
            '`_superestructura` y `_nodos_especiales`, y normalizar también',
            'en `Nodo.existe` y `Nodo.nodo_por_id`. Es el equivalente JS del',
            'comportamiento de PHP. Bug detectado en la fase 2 de contextos',
            '(IndexedDB64, V1.5i.7k): el BFS del bitmask llamaba a',
            '`Nodo.nodo_por_id(String(id))` sobre un nodo común cuyo ID',
            'original era número, y el lookup fallaba silenciosamente.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Historial: entrada 1.5i.7k',
        'buscar' => [
            '  eventualmente 256 bits y producto de primos). Ver §11.4.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
        'reemplazar' => [
            '  eventualmente 256 bits y producto de primos). Ver §11.4.',
            '',
            '- **1.5i.7k**: fase 1 (PHP) y fase 2 (JS) de contextos.',
            '  PHP: interfaz `PerdurarSuperestructuraConContexto`, clase',
            '  `PerdurarSuperestructuraStringSQL64` con 3 tablas nuevas,',
            '  `Nodo::contexto_mascara`, métodos `cargar_parcial`,',
            '  `guardar_parcial` (stub) y `listar_contextos` en el',
            '  `Controlador`, flag `$grafo_parcial`. JS: espejo en',
            '  `PerdurarSuperestructuraStringIndexedDB64` con base de datos',
            '  separada (`HyS_ctx`). Fix posterior en JS: normalizar a',
            '  `String(id)` las claves del `Map` de `_superestructura` y',
            '  `_nodos_especiales`, y normalizar en `Nodo.existe` y',
            '  `Nodo.nodo_por_id`, para replicar el comportamiento de PHP.',
            '  Ver §12.3 y §11.4.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
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