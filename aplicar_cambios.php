<?php
/**
 * Aplicador — Ampliar prefijos de prueba en los limpiadores.
 *
 * Contexto: el usuario corrio el snippet de limpieza y quedaron
 * 2 empresas "Empresa prueba grafo" sin borrar. Prefijo real:
 * `emprevgraf*`, que no estaba en la lista del limpiador.
 *
 * Al revisar las pruebas, aparecen varios prefijos mas que se
 * crean pero no se limpian:
 *
 *   Viajes (creados por pruebas del plugin):
 *     - viajelimpia (P30)
 *     - viajemicrograf (P31)
 *     - viajetermgraf (P32)
 *     - viajeparadgraf (P33)
 *     - viajeparahora (P42)
 *     - viajeborrar (P61)
 *     - viajedesaut (P63)
 *     - viajemicrocomp (P64)
 *
 *   Empresas:
 *     - emprevgraf (P37 y P38)
 *
 *   Vehiculos:
 *     - vehgraf (P37) y vehgrafA/vehgrafB (P38)
 *       (si la empresa matchea y se borra entera, se los lleva;
 *       se agrega igual por defensa)
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80f';

$htmls_a_bumpear = [];

$cambios = [

    // ============================================================
    // 1. Viaje.php: ampliar prefijos de limpiar_viajes_de_prueba
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: ampliar prefijos de limpiar_viajes_de_prueba',
        'buscar' => [
            '    $prefijos = [',
            '        \'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\',',
            '        // Prefijos de las pruebas del plugin (agregados en v79j).',
            '        \'viajeventgraf\',    // prueba 34: cancelar venta',
            '        \'viajedeselegraf\',  // prueba 35: deseleccionar asiento',
            '        \'viajecambiomicro\', // prueba 36: cambiar micro',
            '        \'viajeregres\',      // prueba 43: listar viajes con índice',
            '    ];',
        ],
        'reemplazar' => [
            '    $prefijos = [',
            '        \'viajeprueba\', \'viajemicro\', \'viajeval\', \'viajedup\', \'viajecol\', \'viajesin\',',
            '        // Prefijos de las pruebas del plugin (agregados en v79j).',
            '        \'viajeventgraf\',    // prueba 34: cancelar venta',
            '        \'viajedeselegraf\',  // prueba 35: deseleccionar asiento',
            '        \'viajecambiomicro\', // prueba 36: cambiar micro',
            '        \'viajeregres\',      // prueba 43: listar viajes con índice',
            '        // Prefijos agregados en v80f: faltaban en la lista y se',
            '        // acumulaban en el grafo.',
            '        \'viajelimpia\',      // prueba 30: eliminar viaje',
            '        \'viajemicrograf\',   // prueba 31: eliminar micro',
            '        \'viajetermgraf\',    // prueba 32: eliminar terminal',
            '        \'viajeparadgraf\',   // prueba 33: editar paradas',
            '        \'viajeparahora\',    // prueba 42: editar paradas sin hora',
            '        \'viajeborrar\',      // prueba 61: eliminar viaje con terminal',
            '        \'viajedesaut\',      // prueba 63: desautorizar terminal',
            '        \'viajemicrocomp\',   // prueba 64: agregar micro a compartido',
            '    ];',
        ],
    ],

    // ============================================================
    // 2. Empresa.php: ampliar prefijos de limpiar_empresas_vehiculos_de_prueba
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: ampliar prefijos de limpieza',
        'buscar' => [
            '    $prefijos_empresa = [\'fotoauto\', \'viajecambiomicro_emp_\'];',
            '    $prefijos_vehiculo = [\'fotoauto\', \'viajecambiomicro_veh_\'];',
        ],
        'reemplazar' => [
            '    // Prefijos agregados en v80f: emprevgraf es de las pruebas',
            '    // 37 (eliminar_vehiculo) y 38 (eliminar_empresa). Sin este',
            '    // prefijo, esas empresas quedaban colgadas en el grafo.',
            '    $prefijos_empresa = [',
            '        \'fotoauto\',',
            '        \'viajecambiomicro_emp_\',',
            '        \'emprevgraf\',',
            '    ];',
            '    $prefijos_vehiculo = [',
            '        \'fotoauto\',',
            '        \'viajecambiomicro_veh_\',',
            '        \'vehgraf\',',
            '    ];',
        ],
    ],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
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
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
}

$bumps_atversion = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) echo "Bumps @version:    $bumps_atversion\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";