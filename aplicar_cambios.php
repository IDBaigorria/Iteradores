<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2f (Grupo 6, último grande): contexto del solicitante en Impresion.php.
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

// ------------------------------------------------------------
// Impresion.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'Impresion: bump @version a 1.5piloto.78m',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.16',
        ' * @version   1.5piloto.76h',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.16',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'Impresion: generar_impresion acepta dueño',
    'buscar' => [
        'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\'): void {',
        '    $venta = obtener_venta_por_id($id_venta);',
        '    if (!$venta) {',
        '        echo "Venta no encontrada";',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\', ?string $nombre_dueno = null): void {',
        '    // Fase 1c.2: pasar el dueño si viene, para buscar la venta en',
        '    // su contexto (carga parcial desde el flujo de impresión).',
        '    $venta = obtener_venta_por_id($id_venta, null, $nombre_dueno);',
        '    if (!$venta) {',
        '        echo "Venta no encontrada";',
        '        return;',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'Impresion: _nombre_real_usuario usa us_<nombre>',
    'buscar' => [
        'function _nombre_real_usuario(string $nombre_usuario): string {',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) return $nombre_usuario;',
        '    $nodo = $raiz->adyacente($nombre_usuario);',
        '    if (!$nodo) return $nombre_usuario;',
    ],
    'reemplazar' => [
        'function _nombre_real_usuario(string $nombre_usuario): string {',
        '    // Fase 1c.2: ID especial directo (sin la raíz `usuarios`).',
        '    $nodo = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo) return $nombre_usuario;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'Impresion: _recolectar_ventas_para_informe usa us_<nombre>',
    'buscar' => [
        'function _recolectar_ventas_para_informe(string $tipo, string $nombre): array {',
        '    $ventas = [];',
        '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_usuarios) return [];',
        '',
        '    if ($tipo === \'terminal\') {',
        '        $nodo_terminal = $raiz_usuarios->adyacente($nombre);',
        '        if (!$nodo_terminal) return [];',
    ],
    'reemplazar' => [
        'function _recolectar_ventas_para_informe(string $tipo, string $nombre): array {',
        '    $ventas = [];',
        '',
        '    if ($tipo === \'terminal\') {',
        '        // Fase 1c.2: ID especial directo (sin la raíz `usuarios`).',
        '        $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre);',
        '        if (!$nodo_terminal) return [];',
    ],
],

// ------------------------------------------------------------
// Venta.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: bump @version a 1.5piloto.78m',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.14',
        ' * @version   1.5piloto.78k',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.14',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: obtener_venta_por_id acepta dueño',
    'buscar' => [
        'function obtener_venta_por_id(string $id_venta, ?string $nombre_terminal = null): ?array {',
        '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
        '    // a su contexto. Si no, buscar en todos los dueños (compat',
        '    // para admin/soporte).',
        '    [$nodo_venta, ] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
        '    if (!$nodo_venta) return null;',
        '    return formatear_venta_completa($nodo_venta);',
        '}',
    ],
    'reemplazar' => [
        'function obtener_venta_por_id(string $id_venta, ?string $nombre_terminal = null, ?string $nombre_dueno = null): ?array {',
        '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
        '    // a su contexto. Si no, buscar en todos los dueños (compat',
        '    // para admin/soporte).',
        '    // Fase 1c.2: si se pasa el dueño, buscar directo en su',
        '    // contexto (usado por el flujo de impresión con carga parcial).',
        '    [$nodo_venta, ] = _buscar_venta_por_id($id_venta, $nombre_terminal, $nombre_dueno);',
        '    if (!$nodo_venta) return null;',
        '    return formatear_venta_completa($nodo_venta);',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'Venta: _buscar_venta_por_id acepta dueño y busca en su contexto',
    'buscar' => [
        'function _buscar_venta_por_id(string $id_venta, ?string $nombre_terminal = null): array {',
        '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
        '    // a su contexto (hoy el nodo del dueño, tras B2.3 el',
        '    // compartido). Acelera la búsqueda y prepara el aislamiento.',
        '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
    ],
    'reemplazar' => [
        'function _buscar_venta_por_id(string $id_venta, ?string $nombre_terminal = null, ?string $nombre_dueno = null): array {',
        '    // Fase 1c.2: si se pasa el dueño (sin terminal), buscar',
        '    // directo en su contexto. Se usa desde el flujo de',
        '    // impresión (que llega con ?dueno=X en el GET).',
        '    if ($nombre_terminal === null || $nombre_terminal === \'\') {',
        '        if ($nombre_dueno !== null && $nombre_dueno !== \'\') {',
        '            $nodo_dueno_d = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        '            if (!$nodo_dueno_d) return [null, \'\'];',
        '            $cont = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno_d);',
        '            if (!$cont) return [null, \'\'];',
        '            $actual = hmi($cont);',
        '            $seg = 0;',
        '            while ($actual && $seg < 1000) {',
        '                if ($actual->dato() === $id_venta) {',
        '                    return [$actual, $nombre_dueno];',
        '                }',
        '                $actual = hd($actual);',
        '                $seg++;',
        '            }',
        '            return [null, \'\'];',
        '        }',
        '    }',
        '',
        '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
        '    // a su contexto (hoy el nodo del dueño, tras B2.3 el',
        '    // compartido). Acelera la búsqueda y prepara el aislamiento.',
        '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
    ],
],

// ------------------------------------------------------------
// index.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bump @version a 1.5piloto.78m',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78g',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: generar_impresion pasa el dueño del GET',
    'buscar' => [
        '    $dni_filtro = $_GET[\'dni\'] ?? \'\';',
        '    $numero_cupon = $_GET[\'numero_cupon\'] ?? \'\';',
        '    generar_impresion($tipo, $_GET[\'id_venta\'] ?? \'\', $dni_filtro, $numero_cupon);',
        '    exit;',
    ],
    'reemplazar' => [
        '    $dni_filtro = $_GET[\'dni\'] ?? \'\';',
        '    $numero_cupon = $_GET[\'numero_cupon\'] ?? \'\';',
        '    // Fase 1c.2: pasar el dueño si viene en el GET, para que',
        '    // la búsqueda de la venta use su contexto en carga parcial.',
        '    generar_impresion($tipo, $_GET[\'id_venta\'] ?? \'\', $dni_filtro, $numero_cupon, $_GET[\'dueno\'] ?? \'\');',
        '    exit;',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78m',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78l',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: _contextos_para_operacion con excepciones de impresión',
    'buscar' => [
        '    if (isset($get[\'imprimir\']) && $get[\'imprimir\'] === \'1\') {',
        '        $dueno = $get[\'dueno\'] ?? \'\';',
        '        if ($dueno === \'\') return null;',
        '        return [\'us_\' . $dueno];',
        '    }',
    ],
    'reemplazar' => [
        '    if (isset($get[\'imprimir\']) && $get[\'imprimir\'] === \'1\') {',
        '        $dueno = $get[\'dueno\'] ?? \'\';',
        '        if ($dueno === \'\') return null;',
        '',
        '        // Fase 1c.2: los informes de cancelación, liquidación y',
        '        // rendición usan helpers que iteran sobre todos los dueños',
        '        // (B3.3c, postergado). Carga total hasta que se reescriban.',
        '        $tipo_imp = $get[\'tipo\'] ?? \'\';',
        '        $tipos_con_b33c = [\'informe_cancelacion\', \'informe_liquidacion\', \'informe_rendicion\'];',
        '        if (in_array($tipo_imp, $tipos_con_b33c, true)) {',
        '            return null;',
        '        }',
        '',
        '        return [\'us_\' . $dueno];',
        '    }',
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