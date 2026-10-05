<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74t:
 *   - Fix de la Fase 2 (v74r-v74s): los helpers _destruir_*
 *     no desenlazaban al hijo del padre antes de destruirlo.
 *     Resultado: 2 nodos huérfanos por micro (las cabezas de
 *     las listas circulares de asientos de cada piso).
 *   - Corrección del orden en _destruir_lista_circular_asientos,
 *     _destruir_copia_vehiculo y _destruir_micro.
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

    // --------------------------------------------------------
    // Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.74t',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74r',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74t',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reescribir _destruir_lista_circular_asientos',
        'buscar' => [
            'function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {',
            '    $cabeza = $nodo_piso->adyacente(\'asientos\');',
            '    if (!$cabeza) return;',
            '',
            '    // Recolectar todos los asientos menos la cabeza.',
            '    $asientos = [];',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {',
            '        $asientos[] = $actual;',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '',
            '    // Romper el círculo: desenlazar el `siguiente` del último.',
            '    if (!empty($asientos)) {',
            '        $ultimo = $asientos[count($asientos) - 1];',
            '        $ultimo->eliminar_adyacente(\'siguiente\');',
            '    }',
            '',
            '    // Desenlazar el `primer` de la cabeza antes de destruir asientos.',
            '    $cabeza->eliminar_adyacente(\'primer\');',
            '',
            '    // Destruir cada asiento con sus campos. Las referencias',
            '    // externas (pasajero, venta) solo se desenlazan, no se',
            '    // destruyen. En un viaje sin ventas no deberían existir,',
            '    // pero se cubre por defensa.',
            '    foreach ($asientos as $asiento) {',
            '        _destruir_campos_simples($asiento, [\'pasajero\', \'venta\']);',
            '        $asiento->eliminar_adyacente(\'pasajero\');',
            '        $asiento->eliminar_adyacente(\'venta\');',
            '        Nodo::eliminar($asiento);',
            '    }',
            '',
            '    // Destruir la cabeza.',
            '    _destruir_campos_simples($cabeza);',
            '    Nodo::eliminar($cabeza);',
            '}',
        ],
        'reemplazar' => [
            'function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {',
            '    $cabeza = $nodo_piso->adyacente(\'asientos\');',
            '    if (!$cabeza) return;',
            '',
            '    // Recolectar todos los asientos menos la cabeza.',
            '    $asientos = [];',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {',
            '        $asientos[] = $actual;',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '',
            '    // Desenlazar el `siguiente` de TODOS los asientos, no',
            '    // solo el del último. Cada asiento tiene un `siguiente`',
            '    // apuntando al próximo; si no se desenlaza antes de',
            '    // destruir, el próximo queda con una referencia entrante',
            '    // desde el asiento anterior.',
            '    foreach ($asientos as $a) {',
            '        $a->eliminar_adyacente(\'siguiente\');',
            '    }',
            '',
            '    // Desenlazar el `primer` de la cabeza.',
            '    $cabeza->eliminar_adyacente(\'primer\');',
            '',
            '    // Desenlazar la cabeza del piso: $nodo_piso->asientos',
            '    // apunta a la cabeza. Sin esto, la cabeza queda con una',
            '    // referencia entrante desde el piso y Nodo::eliminar',
            '    // falla silenciosamente, dejándola huérfana.',
            '    $nodo_piso->eliminar_adyacente(\'asientos\');',
            '',
            '    // Destruir cada asiento con sus campos. Las referencias',
            '    // externas (pasajero, venta) solo se desenlazan, no se',
            '    // destruyen.',
            '    foreach ($asientos as $asiento) {',
            '        _destruir_campos_simples($asiento, [\'pasajero\', \'venta\']);',
            '        $asiento->eliminar_adyacente(\'pasajero\');',
            '        $asiento->eliminar_adyacente(\'venta\');',
            '        Nodo::eliminar($asiento);',
            '    }',
            '',
            '    // Destruir la cabeza.',
            '    _destruir_campos_simples($cabeza);',
            '    Nodo::eliminar($cabeza);',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reescribir _destruir_copia_vehiculo (desenlazar antes)',
        'buscar' => [
            'function _destruir_copia_vehiculo(Nodo $nodo_copia): void {',
            '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso) {',
            '                _destruir_piso($piso);',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '            }',
            '        }',
            '        _destruir_campos_simples($nodo_asientos);',
            '        Nodo::eliminar($nodo_asientos);',
            '    }',
            '    _destruir_campos_simples($nodo_copia);',
            '    Nodo::eliminar($nodo_copia);',
            '}',
        ],
        'reemplazar' => [
            'function _destruir_copia_vehiculo(Nodo $nodo_copia): void {',
            '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso) {',
            '                // Desenlazar PRIMERO: el contenedor apunta al',
            '                // piso con `piso_$i`. Si no se desenlaza antes',
            '                // de destruir el piso, el piso tiene una',
            '                // referencia entrante y Nodo::eliminar falla.',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '                _destruir_piso($piso);',
            '            }',
            '        }',
            '        // Desenlazar el contenedor de asientos de la copia',
            '        // antes de destruirlo.',
            '        $nodo_copia->eliminar_adyacente(\'asientos\');',
            '        _destruir_campos_simples($nodo_asientos);',
            '        Nodo::eliminar($nodo_asientos);',
            '    }',
            '    _destruir_campos_simples($nodo_copia);',
            '    Nodo::eliminar($nodo_copia);',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Reescribir _destruir_micro (desenlazar antes)',
        'buscar' => [
            'function _destruir_micro(Nodo $nodo_micro): void {',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '    if ($nodo_copia) {',
            '        _destruir_copia_vehiculo($nodo_copia);',
            '        $nodo_micro->eliminar_adyacente(\'vehiculo_copia\');',
            '    }',
            '    // Desenlazar referencias externas/circulares antes de destruir.',
            '    $nodo_micro->eliminar_adyacente(\'empresa\');',
            '    $nodo_micro->eliminar_adyacente(\'viaje\');',
            '    _destruir_campos_simples($nodo_micro);',
            '    Nodo::eliminar($nodo_micro);',
            '}',
        ],
        'reemplazar' => [
            'function _destruir_micro(Nodo $nodo_micro): void {',
            '    $nodo_copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '    if ($nodo_copia) {',
            '        // Desenlazar PRIMERO: el micro apunta a la copia. Si no',
            '        // se desenlaza antes de destruir la copia, la copia',
            '        // tiene una referencia entrante y Nodo::eliminar falla.',
            '        $nodo_micro->eliminar_adyacente(\'vehiculo_copia\');',
            '        _destruir_copia_vehiculo($nodo_copia);',
            '    }',
            '    // Desenlazar referencias externas/circulares antes de destruir.',
            '    $nodo_micro->eliminar_adyacente(\'empresa\');',
            '    $nodo_micro->eliminar_adyacente(\'viaje\');',
            '    _destruir_campos_simples($nodo_micro);',
            '    Nodo::eliminar($nodo_micro);',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74t',
        'buscar' => [
            '- **v74s**: Fase 2, segundo flujo arreglado.',
        ],
        'reemplazar' => [
            '- **v74t**: fix del orden de destrucción en los helpers',
            '  `_destruir_*`. Los helpers no desenlazaban al hijo del',
            '  padre antes de destruirlo, lo que dejaba 2 nodos',
            '  huérfanos por micro (las cabezas de las listas',
            '  circulares de asientos de cada piso). Corregido el',
            '  orden en `_destruir_lista_circular_asientos`,',
            '  `_destruir_copia_vehiculo` y `_destruir_micro`:',
            '  siempre desenlazar antes de destruir. También se',
            '  desenlaza el `siguiente` de TODOS los asientos, no',
            '  solo el del último.',
            '- **v74s**: Fase 2, segundo flujo arreglado.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74t',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74s',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74t',
            '(fix del orden de destrucción en los helpers `_destruir_*`.',
            'Los helpers no desenlazaban al hijo del padre antes de',
            'destruirlo, dejando 2 nodos huérfanos por micro: las',
            'cabezas de las listas circulares de cada piso. Corregido).',
            'Antes: v1.5piloto.74s',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74t',
        'buscar' => [
            '- Cerramos en v74s el segundo flujo de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74t el fix del orden de destrucción en',
            '  los helpers `_destruir_*` de `Viaje.php`. El bug: los',
            '  helpers llamaban a `Nodo::eliminar` del hijo ANTES de',
            '  desenlazarlo del padre, dejando 2 nodos huérfanos por',
            '  micro (las cabezas de las listas circulares de cada',
            '  piso). El fix: desenlazar siempre antes de destruir.',
            '  También se desenlaza el `siguiente` de todos los',
            '  asientos (no solo el del último).',
            '- Cerramos en v74s el segundo flujo de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74t',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74s (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74t (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74t',
        'buscar' => [
            'v74r: `eliminar_viaje` destruye el subárbol completo',
            '(Fase 2, primer flujo). v74s: `eliminar_micro_de_viaje`',
            'destruye el micro completo (Fase 2, segundo flujo).',
        ],
        'reemplazar' => [
            'v74r: `eliminar_viaje` destruye el subárbol completo',
            '(Fase 2, primer flujo). v74s: `eliminar_micro_de_viaje`',
            'destruye el micro completo (Fase 2, segundo flujo).',
            'v74t: fix del orden de destrucción en los helpers',
            '`_destruir_*` (desenlazar siempre antes de destruir).',
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
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

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

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";