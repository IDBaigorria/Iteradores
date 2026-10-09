<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77a (fix bug 1: atadura habilita campos).
 *   - Aplicacion/ventas.js: _activar_atadura habilita los campos
 *     del pasajero al activarse. Sin esto, el usuario ve los
 *     datos copiados pero no puede completar los campos que el
 *     comprador no tiene (celular_emergencia, fecha_nacimiento,
 *     direccion, localidad) hasta que vuelve el fetch.
 *   - aplicacion_GET.html: bump ?v= de ventas.js.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // ventas.js — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.77a',
        'buscar' => [
            ' * @version 1.5piloto.77',
            ' */',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.77a',
            ' */',
        ],
    ],

    // ============================================================
    // ventas.js — _activar_atadura habilita campos
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _activar_atadura habilita campos',
        'buscar' => [
            'function _activar_atadura(index_pasajero) {',
            '    window.atadura_actual = { indice_pasajero: index_pasajero };',
            '',
            '    // Copia inicial bidireccional entre comprador y pasajero.',
        ],
        'reemplazar' => [
            'function _activar_atadura(index_pasajero) {',
            '    window.atadura_actual = { indice_pasajero: index_pasajero };',
            '',
            '    // Fix v77a: habilitar los campos no-DNI del pasajero al',
            '    // activar la atadura. Los campos comunes (apellido,',
            '    // nombres, email, celular) reciben el dato del comprador',
            '    // en el bloque de copia inicial. Los no comunes',
            '    // (celular_emergencia, fecha_nacimiento, direccion,',
            '    // localidad) quedan habilitados y vacios para que el',
            '    // usuario los complete. Sin esto, el usuario veia los',
            '    // datos copiados pero no podia escribir en los campos',
            '    // faltantes hasta que volvia el fetch del DNI.',
            '    _habilitar_campos_pasajero(index_pasajero, true);',
            '',
            '    // Copia inicial bidireccional entre comprador y pasajero.',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump ventas.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET: bump ?v= de ventas.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.77"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.77a"></script>',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77a',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77a',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77a',
        'buscar' => [
            '**Tanda actual:** v77 (fix del bug 1 de la atadura:',
            'DNI nuevo + datos ya cargados en el otro lado).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77a (fix del bug 1, parte 2).',
            '',
            '**Bug 1 (atadura) — diagnóstico completo (v77 + v77a):**',
            'El usuario carga el comprador con un DNI nuevo (no existe',
            'en el grafo), llena todos los datos. Después, en el form',
            'del pasajero, ingresa el mismo DNI. La atadura se activa',
            'y copia los datos del comprador al pasajero. Pero:',
            '  a) (fix v77) al volver el fetch, el backend responde',
            '     "no existe" y `_buscar_pasajero_por_dni` limpiaba los',
            '     campos. Ahora no limpia si hay atadura activa.',
            '  b) (fix v77a) los campos no-DNI del pasajero quedaban',
            '     `disabled` hasta que volviera el fetch. Ahora',
            '     `_activar_atadura` los habilita al activar. Los',
            '     campos comunes reciben el dato del comprador; los',
            '     no comunes (celular_emergencia, fecha_nacimiento,',
            '     direccion, localidad) quedan libres para completar.',
            '',
            '**Bug 2 (modal post venta) — diagnóstico pendiente:**',
            'Vuelve a no aparecer. Se está diagnosticando con logs de',
            'consola. Fix anterior (v76z): mostrar el modal antes de',
            'los refrescos, envolver refrescos en try/catch.',
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