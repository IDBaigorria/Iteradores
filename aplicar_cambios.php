<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5i.7m (Fase B2.3.5c.1 del modelo topológico).
 *   - Controlador.php: `grafo:nodo` agrega el campo
 *     `destino_es_compartido` a cada adyacente.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Controlador.php — bump @version (el docblock no tenía
    // @version, se agrega después de @since V1.2.0)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador.php: agregar @version 1.5i.7m',
        'buscar' => [
            ' * @implements Dominios',
            ' * @since V1.2.0',
            ' */',
            'class Controlador extends Objeto implements PerdurarSuperestructura, Comandos, Comunicadores, VectorGravitacional, Motor, Dominios {',
        ],
        'reemplazar' => [
            ' * @implements Dominios',
            ' * @since V1.2.0',
            ' * @version 1.5i.7m',
            ' */',
            'class Controlador extends Objeto implements PerdurarSuperestructura, Comandos, Comunicadores, VectorGravitacional, Motor, Dominios {',
        ],
    ],

    // ============================================================
    // Controlador.php — grafo:nodo con destino_es_compartido
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'grafo:nodo: agregar destino_es_compartido',
        'buscar' => [
            '            $adyacentes = [];',
            '            foreach ($nodo->adyacentes() as $enlace => $ady) {',
            '                $adyacentes[] = [',
            '                    \'enlace\' => (string)$enlace,',
            '                    \'id_destino\' => $ady->id(),',
            '                    \'dato_destino\' => mb_substr((string)$ady->dato(), 0, 80),',
            '                ];',
            '            }',
        ],
        'reemplazar' => [
            '            $adyacentes = [];',
            '            foreach ($nodo->adyacentes() as $enlace => $ady) {',
            '                // Fase B2.3.5c.1 (1.5i.7m): marcar si el destino es',
            '                // un contenedor compartido. El chequeo es O(1)',
            '                // porque el nodo destino ya está en memoria (el',
            '                // grafo está cargado).',
            '                $destino_es_compartido = $ady->adyacente(\'_es_compartido\') !== null;',
            '                $adyacentes[] = [',
            '                    \'enlace\' => (string)$enlace,',
            '                    \'id_destino\' => $ady->id(),',
            '                    \'dato_destino\' => mb_substr((string)$ady->dato(), 0, 80),',
            '                    \'destino_es_compartido\' => $destino_es_compartido,',
            '                ];',
            '            }',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77k',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77j',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77k',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77k',
        'buscar' => [
            '**Tanda actual:** v77j (fix de zona horaria).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77k (Fase B2.3.5c.1: `grafo:nodo` con',
            '`destino_es_compartido`).',
            '',
            '**Bug detectado al correr las pruebas del plugin (v77k).**',
            '',
            'Dos problemas independientes:',
            '',
            '1. **Bug real del flujo de alta de terminal.** El comando',
            '   `app:repuntar_terminales_compartido` (v77f) iteraba sobre',
            '   los compartidos ya existentes del dueño, no sobre los',
            '   terminales. Un terminal creado **después** del repuntado',
            '   quedaba sin compartido y sin repuntar: su enlace `dueno`',
            '   apuntaba al nodo del dueño real, rompiendo el aislamiento',
            '   topológico silenciosamente. `agregar_usuario` tampoco',
            '   crea el compartido al crear un terminal nuevo. Fix',
            '   pendiente en B2.3.5c.2 + c.3.',
            '',
            '2. **Test mal diseñado.** Los IDs numéricos del grafo no son',
            '   estables entre cargas: cada POST a `grafo/nodo` recarga',
            '   el grafo y regenera los IDs. La tabla de equivalencias',
            '   del framework solo vive dentro de una carga. La prueba',
            '   57 seguía un ID numérico entre dos consultas, y por eso',
            '   fallaba. Fix: exponer en `grafo:nodo` un campo',
            '   `destino_es_compartido` para verificar la topología con',
            '   una sola consulta.',
            '',
            '**v77k — Fase B2.3.5c.1.**',
            '',
            '`grafo:nodo` (framework PHP, 1.5i.7m) agrega el campo',
            '`destino_es_compartido` a cada adyacente. El chequeo es',
            'O(1) porque el nodo destino ya está en memoria. No cambia',
            'el comportamiento observable de la app.',
            '',
            '**Pendiente: B2.3.5c.2 + c.3.** Fix real del flujo de alta:',
            'crear el compartido al dar de alta un terminal, y reescribir',
            '`app:repuntar_terminales_compartido` para que itere sobre',
            'los terminales del dueño (no sobre los compartidos).',
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