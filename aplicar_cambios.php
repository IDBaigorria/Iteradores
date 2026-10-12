<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1b: preparación de la carga parcial (v2, bump corregido).
 *
 *  - Autenticacion.php: us_<nombre> directo en
 *    validar_token_sesion y _construir_respuesta_login.
 *  - Controlador.php (framework 1.5i.7p): nueva propiedad
 *    $contextos_cargados + getter público contextos_cargados().
 *    Se resetea en cargar() y se setea en cargar_parcial().
 *  - FuncionesAuxiliares.php: guardar_ambos consulta
 *    contextos_cargados() y usa guardar_parcial si
 *    corresponde. Rama nueva inactiva hoy.
 *
 * Sin cambio de comportamiento.
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
// Autenticacion.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'Autenticacion: bump @version a 1.5piloto.78c',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.76e',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78c',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'Autenticacion: validar_token_sesion usa us_<nombre>',
    'buscar' => [
        '    if ($nombre_usuario === null) return null;',
        '',
        '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_usuarios) return null;',
        '',
        '    $nodo_usuario_real = $raiz_usuarios->adyacente($nombre_usuario);',
        '    if (!$nodo_usuario_real) return null;',
        '',
        '    return [',
        '        \'nombre_usuario\' => $nombre_usuario,',
        '        \'nodo\' => $nodo_usuario_real,',
        '    ];',
    ],
    'reemplazar' => [
        '    if ($nombre_usuario === null) return null;',
        '',
        '    // Fase B3.1: el nodo del usuario vive en us_<nombre> (ID',
        '    // especial). No se usa la raíz `usuarios` porque en carga',
        '    // parcial puede no estar cargada.',
        '    $nodo_usuario_real = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_usuario_real) return null;',
        '',
        '    return [',
        '        \'nombre_usuario\' => $nombre_usuario,',
        '        \'nodo\' => $nodo_usuario_real,',
        '    ];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
    'descripcion' => 'Autenticacion: _construir_respuesta_login usa us_<nombre>',
    'buscar' => [
        'function _construir_respuesta_login(string $nombre_usuario): ?array {',
        '    $raiz_app = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_app) return null;',
        '    $nodo_app = $raiz_app->adyacente($nombre_usuario);',
        '    if (!$nodo_app) return null;',
        '',
        '    $nodo_nivel = $nodo_app->adyacente(\'nivel\');',
    ],
    'reemplazar' => [
        'function _construir_respuesta_login(string $nombre_usuario): ?array {',
        '    // Fase B3.1: ID especial directo. La raíz `usuarios` puede',
        '    // no estar cargada en modo de carga parcial.',
        '    $nodo_app = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_app) return null;',
        '',
        '    $nodo_nivel = $nodo_app->adyacente(\'nivel\');',
    ],
],

// ------------------------------------------------------------
// Controlador.php (framework)
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: bump @version a 1.5i.7p',
    'buscar' => [
        ' * @since V1.2.0',
        ' * @version 1.5i.7n',
        ' */',
        'class Controlador extends Objeto',
    ],
    'reemplazar' => [
        ' * @since V1.2.0',
        ' * @version 1.5i.7p',
        ' */',
        'class Controlador extends Objeto',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: agregar propiedad $contextos_cargados',
    'buscar' => [
        '    /**',
        '     * Indica si la superestructura cargada es parcial.',
        '     *',
        '     * Ciclo de vida:',
        '     *   - false por defecto.',
        '     *   - `cargar()` lo resetea a false.',
        '     *   - `cargar_parcial()` lo resetea a false antes de',
        '     *     delegar, y lo pone a true solo si la carga fue',
        '     *     exitosa.',
        '     *   - `guardar()` falla si el flag está en true.',
        '     *   - `guardar_parcial()` exige el flag en true.',
        '     *',
        '     * @var bool',
        '     */',
        '    protected static bool $grafo_parcial = false;',
    ],
    'reemplazar' => [
        '    /**',
        '     * Indica si la superestructura cargada es parcial.',
        '     *',
        '     * Ciclo de vida:',
        '     *   - false por defecto.',
        '     *   - `cargar()` lo resetea a false.',
        '     *   - `cargar_parcial()` lo resetea a false antes de',
        '     *     delegar, y lo pone a true solo si la carga fue',
        '     *     exitosa.',
        '     *   - `guardar()` falla si el flag está en true.',
        '     *   - `guardar_parcial()` exige el flag en true.',
        '     *',
        '     * @var bool',
        '     */',
        '    protected static bool $grafo_parcial = false;',
        '',
        '    /**',
        '     * Contextos que se usaron en la última carga parcial.',
        '     *',
        '     * Vacío si la última carga fue completa (`cargar()`).',
        '     * Lo consulta `guardar_ambos` para decidir entre',
        '     * `guardar()` (total) y `guardar_parcial()`',
        '     * (restringido a estos contextos).',
        '     *',
        '     * @var string[]',
        '     * @since 1.5i.7p',
        '     */',
        '    protected static array $contextos_cargados = [];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: getter contextos_cargados()',
    'buscar' => [
        '    public static function es_grafo_parcial(): bool {',
        '        return static::$grafo_parcial;',
        '    }',
    ],
    'reemplazar' => [
        '    public static function es_grafo_parcial(): bool {',
        '        return static::$grafo_parcial;',
        '    }',
        '',
        '    /**',
        '     * Devuelve los contextos que se usaron en la última',
        '     * carga parcial. Array vacío si la última carga fue',
        '     * completa.',
        '     *',
        '     * @return string[]',
        '     * @since 1.5i.7p',
        '     */',
        '    public static function contextos_cargados(): array {',
        '        return static::$contextos_cargados;',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: cargar resetea contextos_cargados',
    'buscar' => [
        '    public static function cargar($nombre): bool|null {',
        '        Nodo::vaciar_superestructura(static::$token);',
        '        static::$grafo_parcial = false;',
        '        return static::delegar(\'cargar\', $nombre);',
        '    }',
    ],
    'reemplazar' => [
        '    public static function cargar($nombre): bool|null {',
        '        Nodo::vaciar_superestructura(static::$token);',
        '        static::$grafo_parcial = false;',
        '        static::$contextos_cargados = [];',
        '        return static::delegar(\'cargar\', $nombre);',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: cargar_parcial setea contextos_cargados',
    'buscar' => [
        '    public static function cargar_parcial($nombre, array $contextos): bool|null {',
        '        Nodo::vaciar_superestructura(static::$token);',
        '        static::$grafo_parcial = false;',
        '        $res = static::delegar_con_args(\'cargar_parcial\', $nombre, [$contextos]);',
        '        if ($res === true) {',
        '            static::$grafo_parcial = true;',
        '        }',
        '        return $res;',
        '    }',
    ],
    'reemplazar' => [
        '    public static function cargar_parcial($nombre, array $contextos): bool|null {',
        '        Nodo::vaciar_superestructura(static::$token);',
        '        static::$grafo_parcial = false;',
        '        static::$contextos_cargados = [];',
        '        $res = static::delegar_con_args(\'cargar_parcial\', $nombre, [$contextos]);',
        '        if ($res === true) {',
        '            static::$grafo_parcial = true;',
        '            static::$contextos_cargados = $contextos;',
        '        }',
        '        return $res;',
        '    }',
    ],
],

// ------------------------------------------------------------
// FuncionesAuxiliares.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
    'descripcion' => 'FuncionesAuxiliares: bump @version a 1.5piloto.78c',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.37',
        ' * @version   1.5piloto.74y',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.37',
        ' * @version   1.5piloto.78c',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
    'descripcion' => 'FuncionesAuxiliares: guardar_ambos consulta contextos_cargados',
    'buscar' => [
        'function guardar_ambos($nombre): bool {',
        '    if (!is_string($nombre) || $nombre === \'\') {',
        '        Controlador::_error("guardar_ambos: nombre invalido");',
        '        return false;',
        '    }',
        '',
        '    // Defensa: no guardar si la superestructura esta vacia.',
        '    // Guardar vacio pisa el grafo con nada.',
        '    if (!Nodo::hay_nodos_en_superestructura()) {',
        '        Controlador::_error("guardar_ambos: superestructura vacia para \\"$nombre\\". Se aborta para no pisar el grafo.");',
        '        return false;',
        '    }',
        '',
        '    // Guardar en SQL (única fuente de verdad).',
        '    return (bool) Controlador::guardar($nombre);',
        '}',
    ],
    'reemplazar' => [
        'function guardar_ambos($nombre): bool {',
        '    if (!is_string($nombre) || $nombre === \'\') {',
        '        Controlador::_error("guardar_ambos: nombre invalido");',
        '        return false;',
        '    }',
        '',
        '    // Defensa: no guardar si la superestructura esta vacia.',
        '    // Guardar vacio pisa el grafo con nada.',
        '    if (!Nodo::hay_nodos_en_superestructura()) {',
        '        Controlador::_error("guardar_ambos: superestructura vacia para \\"$nombre\\". Se aborta para no pisar el grafo.");',
        '        return false;',
        '    }',
        '',
        '    // Fase de carga parcial (v1.5piloto.78c): si el grafo en',
        '    // memoria se cargó de forma parcial, guardar solo los',
        '    // contextos cargados. Si la carga fue completa, guardar',
        '    // todo (comportamiento actual).',
        '    $contextos_cargados = Controlador::contextos_cargados();',
        '    if (!empty($contextos_cargados)) {',
        '        return Controlador::guardar_parcial($nombre, $contextos_cargados);',
        '    }',
        '',
        '    // Guardar en SQL (única fuente de verdad).',
        '    return (bool) Controlador::guardar($nombre);',
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