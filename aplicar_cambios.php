<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores.
 *
 * Fix v1.5piloto.46c: los métodos de pago disponibles al pagar un cupón se
 * filtran también por el máximo de cuotas configurado por método. Si la venta
 * se pactó a más cuotas que el máximo permitido por ese método, ese método no
 * se ofrece.
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // =========================================================
    // 1. Venta.php — Bump de versión
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.46c',
        'buscar' => [
            ' * @version   1.5piloto.46',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.46c',
        ],
    ],

    // =========================================================
    // 2. Venta.php — Filtrar métodos por cuotas máximas
    // =========================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: filtrar métodos por cuotas máximas',
        'buscar' => [
            'function _resolver_metodos_permitidos_venta(Nodo $nodo_venta): array {',
            '    $nodo_viaje = $nodo_venta->adyacente(\'viaje\');',
            '    $nodo_terminal = $nodo_venta->adyacente(\'terminal\');',
            '    if (!$nodo_viaje || !$nodo_terminal) return [];',
            '',
            '    $nombre_viaje = $nodo_viaje->dato();',
            '    $nombre_terminal = $nodo_terminal->dato();',
            '    $nodo_dueno = $nodo_viaje->adyacente(\'dueno\');',
            '    $nombre_dueno = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '    if ($nombre_dueno === \'\') return [];',
            '',
            '    $opciones_viaje = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '    $opciones_terminal = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '',
            '    $resolver = function(string $campo, string $default) use ($opciones_viaje, $opciones_terminal) {',
            '        if (isset($opciones_terminal[$campo]) && trim((string)$opciones_terminal[$campo]) !== \'\') {',
            '            return (string)$opciones_terminal[$campo];',
            '        }',
            '        if (isset($opciones_viaje[$campo]) && trim((string)$opciones_viaje[$campo]) !== \'\') {',
            '            return (string)$opciones_viaje[$campo];',
            '        }',
            '        return $default;',
            '    };',
            '',
            '    $metodos = [];',
            '    if ($resolver(\'permite_efectivo\', \'1\') === \'1\') $metodos[] = \'efectivo\';',
            '    if ($resolver(\'permite_transferencia\', \'1\') === \'1\') $metodos[] = \'transferencia\';',
            '    return $metodos;',
            '}',
        ],
        'reemplazar' => [
            'function _resolver_metodos_permitidos_venta(Nodo $nodo_venta): array {',
            '    $nodo_viaje = $nodo_venta->adyacente(\'viaje\');',
            '    $nodo_terminal = $nodo_venta->adyacente(\'terminal\');',
            '    if (!$nodo_viaje || !$nodo_terminal) return [];',
            '',
            '    $nombre_viaje = $nodo_viaje->dato();',
            '    $nombre_terminal = $nodo_terminal->dato();',
            '    $nodo_dueno = $nodo_viaje->adyacente(\'dueno\');',
            '    $nombre_dueno = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '    if ($nombre_dueno === \'\') return [];',
            '',
            '    // Cuotas pactadas de la venta. Se usan para validar que el método',
            '    // elegido soporte esa cantidad de cuotas según la configuración',
            '    // del viaje o de la terminal.',
            '    $cuotas_pactadas = (int)($nodo_venta->adyacente(\'cuotas\') ? $nodo_venta->adyacente(\'cuotas\')->dato() : \'1\');',
            '    if ($cuotas_pactadas < 1) $cuotas_pactadas = 1;',
            '',
            '    $opciones_viaje = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '    $opciones_terminal = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '',
            '    $resolver = function(string $campo, string $default) use ($opciones_viaje, $opciones_terminal) {',
            '        if (isset($opciones_terminal[$campo]) && trim((string)$opciones_terminal[$campo]) !== \'\') {',
            '            return (string)$opciones_terminal[$campo];',
            '        }',
            '        if (isset($opciones_viaje[$campo]) && trim((string)$opciones_viaje[$campo]) !== \'\') {',
            '            return (string)$opciones_viaje[$campo];',
            '        }',
            '        return $default;',
            '    };',
            '',
            '    $metodos = [];',
            '',
            '    // Efectivo: se ofrece si está permitido y el máximo de cuotas',
            '    // configurado alcanza para las cuotas pactadas.',
            '    if ($resolver(\'permite_efectivo\', \'1\') === \'1\') {',
            '        $max_efectivo = (int)$resolver(\'cuotas_efectivo_max\', \'3\');',
            '        if ($max_efectivo >= $cuotas_pactadas) $metodos[] = \'efectivo\';',
            '    }',
            '',
            '    // Transferencia: mismo criterio.',
            '    if ($resolver(\'permite_transferencia\', \'1\') === \'1\') {',
            '        $max_transferencia = (int)$resolver(\'cuotas_transferencia_max\', \'1\');',
            '        if ($max_transferencia >= $cuotas_pactadas) $metodos[] = \'transferencia\';',
            '    }',
            '',
            '    return $metodos;',
            '}',
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