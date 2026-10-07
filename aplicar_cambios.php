<?php
/**
 * Aplicador de cambios — Piloto PHP.
 *
 * Tanda V1.5piloto.76h:
 *   - Nueva variante de impresión: planilla de pasajeros "vacía".
 *     Mismos encabezados, mismos números de asiento, pero sin
 *     datos del pasajero en cada fila.
 *   - Parámetro `$vacia = false` en `imprimir_planilla_pasajeros_micro`.
 *   - `index.php` lee `$_GET['vacia']` y lo pasa.
 *   - `viajes-asientos.js`: nuevo botón + listener.
 *   - `aplicacion_GET.html`: bump del ?v= del JS.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ------------------------------------------------------------
    // Aplicacion/Impresion/Impresion.php
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: bump @version a 1.5piloto.76h',
        'buscar' => [' * @version   1.5piloto.67c'],
        'reemplazar' => [' * @version   1.5piloto.76h'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: firma con $vacia + doc',
        'buscar' => [
            ' * Imprime la planilla de pasajeros del micro, en A4 horizontal.',
            ' *',
            ' * Header: logo a la izquierda, dirección y teléfono al lado, y a',
            ' * la derecha DESTINO, FECHA y HORA del viaje.',
            ' * Cuerpo: tabla con 7 columnas.',
            ' *   1. 😊 (header) — en cada fila va el número de asiento. Columna',
            ' *      fina, sin título de texto.',
            ' *   2. APELLIDO Y NOMBRES',
            ' *   3. NACIMIENTO',
            ' *   4. DNI',
            ' *   5. TELEFONO',
            ' *   6. TEL. FAMILIAR',
            ' *   7. DOMICILIO',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_micro',
            ' */',
            'function imprimir_planilla_pasajeros_micro(string $nombre_dueno, string $nombre_viaje, string $nombre_micro): void {',
        ],
        'reemplazar' => [
            ' * Imprime la planilla de pasajeros del micro, en A4 horizontal.',
            ' *',
            ' * Header: logo a la izquierda, dirección y teléfono al lado, y a',
            ' * la derecha DESTINO, FECHA y HORA del viaje.',
            ' * Cuerpo: tabla con 7 columnas.',
            ' *   1. 😊 (header) — en cada fila va el número de asiento. Columna',
            ' *      fina, sin título de texto.',
            ' *   2. APELLIDO Y NOMBRES',
            ' *   3. NACIMIENTO',
            ' *   4. DNI',
            ' *   5. TELEFONO',
            ' *   6. TEL. FAMILIAR',
            ' *   7. DOMICILIO',
            ' *',
            ' * Si `$vacia` es true, se imprimen los mismos encabezados,',
            ' * los mismos números de asiento y las mismas columnas, pero',
            ' * sin los datos de cada pasajero (útil para completar a mano).',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_micro',
            ' * @param bool   $vacia  Si true, no llena las filas con los datos del pasajero.',
            ' */',
            'function imprimir_planilla_pasajeros_micro(string $nombre_dueno, string $nombre_viaje, string $nombre_micro, bool $vacia = false): void {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: filas vacías cuando $vacia',
        'buscar' => [
            '        foreach ($asientos as $a) {',
            '            $pa = $a[\'pasajero\'];',
            '            $tiene_pas = ($pa !== null && ($pa[\'nombre_completo\'] !== \'\' || $pa[\'dni_visible\'] !== \'\'));',
            '            echo \'<tr>\';',
            '            echo \'<td class="num">\' . htmlspecialchars($a[\'numero\']) . \'</td>\';',
            '            if ($tiene_pas) {',
        ],
        'reemplazar' => [
            '        foreach ($asientos as $a) {',
            '            echo \'<tr>\';',
            '            echo \'<td class="num">\' . htmlspecialchars($a[\'numero\']) . \'</td>\';',
            '',
            '            // Modo planilla vacía: solo el número de asiento, sin',
            '            // datos del pasajero. Se corta antes de leerlos.',
            '            if ($vacia) {',
            '                echo \'<td></td><td></td><td></td><td></td><td></td><td></td>\';',
            '                echo \'</tr>\';',
            '                continue;',
            '            }',
            '',
            '            $pa = $a[\'pasajero\'];',
            '            $tiene_pas = ($pa !== null && ($pa[\'nombre_completo\'] !== \'\' || $pa[\'dni_visible\'] !== \'\'));',
            '            if ($tiene_pas) {',
        ],
    ],

    // ------------------------------------------------------------
    // index.php
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76h',
        'buscar' => [' * @version   1.5piloto.76e'],
        'reemplazar' => [' * @version   1.5piloto.76h'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: pasar $vacia a la planilla',
        'buscar' => [
            '    // Caso especial: planilla de pasajeros del micro.',
            '    if ($tipo === \'planilla_pasajeros_micro\') {',
            '        imprimir_planilla_pasajeros_micro(',
            '            $_GET[\'dueno\'] ?? \'\',',
            '            $_GET[\'viaje\'] ?? \'\',',
            '            $_GET[\'micro\'] ?? \'\'',
            '        );',
            '        exit;',
            '    }',
        ],
        'reemplazar' => [
            '    // Caso especial: planilla de pasajeros del micro.',
            '    // Si viene &vacia=1, se imprimen los encabezados y los',
            '    // números de asiento, pero sin los datos del pasajero.',
            '    if ($tipo === \'planilla_pasajeros_micro\') {',
            '        $vacia = (($_GET[\'vacia\'] ?? \'0\') === \'1\');',
            '        imprimir_planilla_pasajeros_micro(',
            '            $_GET[\'dueno\'] ?? \'\',',
            '            $_GET[\'viaje\'] ?? \'\',',
            '            $_GET[\'micro\'] ?? \'\',',
            '            $vacia',
            '        );',
            '        exit;',
            '    }',
        ],
    ],

    // ------------------------------------------------------------
    // Aplicacion/Viajes/viajes-asientos.js
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: bump @version a 1.5piloto.76h',
        'buscar' => [' * @version 1.5piloto.74e'],
        'reemplazar' => [' * @version 1.5piloto.76h'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: agregar botón planilla vacía',
        'buscar' => [
            '        const botonesHTML = `',
            '            <div class="acciones-impresion-micro" style="margin-top:12px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
            '                <button class="btn" id="btn_imprimir_croquis_micro">Imprimir Croquis</button>',
            '                <button class="btn primary" id="btn_imprimir_planilla_micro">Imprimir Planilla</button>',
            '            </div>',
            '        `;',
        ],
        'reemplazar' => [
            '        const botonesHTML = `',
            '            <div class="acciones-impresion-micro" style="margin-top:12px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
            '                <button class="btn" id="btn_imprimir_croquis_micro">Imprimir Croquis</button>',
            '                <button class="btn primary" id="btn_imprimir_planilla_micro">Imprimir Planilla</button>',
            '                <button class="btn" id="btn_imprimir_planilla_vacia_micro">Imprimir Planilla Vacía</button>',
            '            </div>',
            '        `;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'viajes-asientos.js: listener del botón planilla vacía',
        'buscar' => [
            '        const btn_planilla = document.getElementById(\'btn_imprimir_planilla_micro\');',
            '        if (btn_planilla) {',
            '            btn_planilla.addEventListener(\'click\', () => {',
            '                const url = `index.php?imprimir=1&tipo=planilla_pasajeros_micro`',
            '                    + `&dueno=${encodeURIComponent(nombre_dueno_imp)}`',
            '                    + `&viaje=${encodeURIComponent(nombre_viaje_imp)}`',
            '                    + `&micro=${encodeURIComponent(nombre_micro_imp)}`;',
            '                window.open(url, \'_blank\');',
            '            });',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '        const btn_planilla = document.getElementById(\'btn_imprimir_planilla_micro\');',
            '        if (btn_planilla) {',
            '            btn_planilla.addEventListener(\'click\', () => {',
            '                const url = `index.php?imprimir=1&tipo=planilla_pasajeros_micro`',
            '                    + `&dueno=${encodeURIComponent(nombre_dueno_imp)}`',
            '                    + `&viaje=${encodeURIComponent(nombre_viaje_imp)}`',
            '                    + `&micro=${encodeURIComponent(nombre_micro_imp)}`;',
            '                window.open(url, \'_blank\');',
            '            });',
            '        }',
            '',
            '        const btn_planilla_vacia = document.getElementById(\'btn_imprimir_planilla_vacia_micro\');',
            '        if (btn_planilla_vacia) {',
            '            btn_planilla_vacia.addEventListener(\'click\', () => {',
            '                const url = `index.php?imprimir=1&tipo=planilla_pasajeros_micro`',
            '                    + `&dueno=${encodeURIComponent(nombre_dueno_imp)}`',
            '                    + `&viaje=${encodeURIComponent(nombre_viaje_imp)}`',
            '                    + `&micro=${encodeURIComponent(nombre_micro_imp)}`',
            '                    + `&vacia=1`;',
            '                window.open(url, \'_blank\');',
            '            });',
            '        }',
            '    }',
        ],
    ],

    // ------------------------------------------------------------
    // aplicacion_GET.html
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de viajes-asientos.js a 1.5piloto.76h',
        'buscar' => ['<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.74e"></script>'],
        'reemplazar' => ['<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.76h"></script>'],
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
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
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
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";