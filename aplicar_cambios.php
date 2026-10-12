<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Etapa 0 del plan de carga parcial (v1.5piloto.78b).
 *
 * Framework (1.5i.7n):
 *  - Nuevo delegar_con_args() en el Controlador.
 *  - cargar_parcial usa delegar_con_args.
 *  - guardar_parcial agrega chequeos (grafo parcial +
 *    nodos desocupados).
 *  - Docblock del flag $grafo_parcial (ciclo de vida).
 *  - Docblock de listar_contextos (contrato de retorno).
 *
 * Piloto (1.5piloto.78b):
 *  - _contextos_para_operacion() en el Enrutador (stub).
 *  - Comentario en index.php sobre la Etapa 1.
 *
 * Sin cambios de comportamiento.
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
// Controlador.php (framework)
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: bump @version a 1.5i.7n',
    'buscar' => [
        ' * @since V1.2.0',
        ' * @version 1.5i.7m',
        ' */',
        'class Controlador extends Objeto',
    ],
    'reemplazar' => [
        ' * @since V1.2.0',
        ' * @version 1.5i.7n',
        ' */',
        'class Controlador extends Objeto',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: delegar() wrapper + delegar_con_args()',
    'buscar' => [
        '    protected static function delegar(string $funcion, $nombre): mixed {',
        '        if (!is_string($nombre)){',
        '			static::_error("el nombre no es un string");',
        '			return null;',
        '		}',
        '        $clase = static::$claseActual;',
        '        if (!static::$claseActual){',
        '            static::_alerta("salio por aca.");',
        '            return null;',
        '        }elseif(!class_exists($clase)) {',
        '            static::_alerta("Clase ".static::$claseActual." de persistencia no disponible para el método actual.");',
        '            return null;',
        '        }',
        '       ',
        '',
        '        if (!method_exists($clase, $funcion)) {',
        '            static::_alerta("El método \'$funcion\' no existe en la clase \'$clase\'.");',
        '            return null;',
        '        }',
        '',
        '        return $clase::$funcion($nombre);',
        '    }',
    ],
    'reemplazar' => [
        '    protected static function delegar(string $funcion, $nombre): mixed {',
        '        return static::delegar_con_args($funcion, $nombre, []);',
        '    }',
        '',
        '    /**',
        '     * Ejecuta una operación delegada a la clase de persistencia activa,',
        '     * pasando argumentos adicionales al método delegado.',
        '     *',
        '     * Es la variante con argumentos extra de {@link delegar()}. La usan',
        '     * las operaciones que necesitan pasar más de un parámetro a la',
        '     * implementación de persistencia (cargar_parcial, guardar_parcial).',
        '     *',
        '     * @param string $funcion    Nombre del método a ejecutar.',
        '     * @param mixed  $nombre     Parámetro principal (nombre de la superestructura).',
        '     * @param array  $args_extra Argumentos adicionales para el método.',
        '     * @return mixed|null',
        '     * @since 1.5i.7n',
        '     */',
        '    protected static function delegar_con_args(string $funcion, $nombre, array $args_extra = []): mixed {',
        '        if (!is_string($nombre)){',
        '			static::_error("el nombre no es un string");',
        '			return null;',
        '		}',
        '        $clase = static::$claseActual;',
        '        if (!static::$claseActual){',
        '            static::_alerta("salio por aca.");',
        '            return null;',
        '        }elseif(!class_exists($clase)) {',
        '            static::_alerta("Clase ".static::$claseActual." de persistencia no disponible para el método actual.");',
        '            return null;',
        '        }',
        '',
        '        if (!method_exists($clase, $funcion)) {',
        '            static::_alerta("El método \'$funcion\' no existe en la clase \'$clase\'.");',
        '            return null;',
        '        }',
        '',
        '        return $clase::$funcion($nombre, ...$args_extra);',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: docblock del flag $grafo_parcial',
    'buscar' => [
        '    /**',
        '     * @var bool Indica si la superestructura cargada es parcial.',
        '     */',
        '    protected static bool $grafo_parcial = false;',
        '',
        '    /**',
        '     * Devuelve true si la superestructura en memoria fue',
        '     * cargada de forma parcial.',
        '     *',
        '     * @return bool',
        '     * @since 1.5i.7k',
        '     */',
        '    public static function es_grafo_parcial(): bool {',
        '        return static::$grafo_parcial;',
        '    }',
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
        '     * Devuelve true si la superestructura en memoria fue',
        '     * cargada de forma parcial.',
        '     *',
        '     * @return bool',
        '     * @since 1.5i.7k',
        '     */',
        '    public static function es_grafo_parcial(): bool {',
        '        return static::$grafo_parcial;',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: cargar_parcial usa delegar_con_args',
    'buscar' => [
        '    public static function cargar_parcial($nombre, array $contextos): bool|null {',
        '        Nodo::vaciar_superestructura(static::$token);',
        '        static::$grafo_parcial = false;',
        '        $clase = static::$claseActual;',
        '        if (!$clase || !method_exists($clase, \'cargar_parcial\')) {',
        '            static::_error("El método de persistencia activo no soporta cargar_parcial.");',
        '            return null;',
        '        }',
        '        $res = $clase::cargar_parcial($nombre, $contextos);',
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
        '        $res = static::delegar_con_args(\'cargar_parcial\', $nombre, [$contextos]);',
        '        if ($res === true) {',
        '            static::$grafo_parcial = true;',
        '        }',
        '        return $res;',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: guardar_parcial con chequeos',
    'buscar' => [
        '    public static function guardar_parcial($nombre, array $contextos): bool {',
        '        $clase = static::$claseActual;',
        '        if (!$clase || !method_exists($clase, \'guardar_parcial\')) {',
        '            static::_error("El método de persistencia activo no soporta guardar_parcial.");',
        '            return false;',
        '        }',
        '        return (bool) $clase::guardar_parcial($nombre, $contextos);',
        '    }',
    ],
    'reemplazar' => [
        '    public static function guardar_parcial($nombre, array $contextos): bool {',
        '        if (!static::$grafo_parcial) {',
        '            static::_error("No se puede guardar_parcial: el grafo en memoria no es parcial. Usar guardar().");',
        '            return false;',
        '        }',
        '        if (!static::verificar_superestructura_desocupada()) {',
        '            return false;',
        '        }',
        '        return (bool) static::delegar_con_args(\'guardar_parcial\', $nombre, [$contextos]);',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/Controlador.php',
    'descripcion' => 'Controlador: docblock de listar_contextos (contrato)',
    'buscar' => [
        '    /**',
        '     * Lista los contextos registrados bajo un nombre.',
        '     *',
        '     * @param string $nombre',
        '     * @return string[]|null',
        '     * @since 1.5i.7k',
        '     */',
        '    public static function listar_contextos($nombre): ?array {',
    ],
    'reemplazar' => [
        '    /**',
        '     * Lista los contextos registrados bajo un nombre.',
        '     *',
        '     * Contrato de retorno:',
        '     *   - `[\'us_X\', \'us_Y\', ...]` → contextos registrados.',
        '     *   - `[]`                    → el nombre no existe, o no',
        '     *                               tiene contextos registrados.',
        '     *   - `null`                  → error (método no soportado',
        '     *                               por la persistencia activa,',
        '     *                               error de DB, etc.).',
        '     *',
        '     * @param string $nombre',
        '     * @return string[]|null',
        '     * @since 1.5i.7k',
        '     * @version 1.5i.7n',
        '     */',
        '    public static function listar_contextos($nombre): ?array {',
    ],
],

// ------------------------------------------------------------
// Enrutador.php (piloto)
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78b',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.77v',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78b',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: agregar _contextos_para_operacion (stub)',
    'buscar' => [
        '/**',
        ' * Enruta una petición POST según la acción indicada.',
        ' *',
        ' * @param string $accion Acción en formato "modulo/subaccion".',
        ' * @param array $post Datos recibidos por POST.',
        ' * @return void',
        ' */',
        'function enrutar_peticion_post(string $accion, array $post): void {',
    ],
    'reemplazar' => [
        '/**',
        ' * Devuelve los contextos que hay que cargar para una',
        ' * operación dada, según el solicitante.',
        ' *',
        ' * Etapa 0 del plan de carga parcial (v1.5piloto.78b):',
        ' * **siempre devuelve null** (carga total). No se activa',
        ' * todavía. La tabla concreta se arma en la Etapa 1.',
        ' *',
        ' * Contrato de retorno:',
        ' *   - null      → carga total. El llamador debe usar',
        ' *                 Controlador::cargar($nombre).',
        ' *   - string[]  → carga parcial. El llamador debe usar',
        ' *                 Controlador::cargar_parcial($nombre, $contextos).',
        ' *',
        ' * Criterio para Etapa 1 (a confirmar caso por caso):',
        ' *   - null                        → carga total (fallback).',
        ' *   - [\'usuarios\', \'sesiones\', \'aplicacion\']',
        ' *                                 → bootstrap mínimo (login).',
        ' *   - [\'us_<sol>\']                → operaciones del solicitante.',
        ' *   - [\'us_<sol>\', \'us_<dueno>\']  → terminal que necesita ver',
        ' *                                   al dueño (o su compartido).',
        ' *   - Todos los us_*              → admin / soporte.',
        ' *',
        ' * Ver `prompts/plan_actual.md` §1.10 para el plan completo.',
        ' *',
        ' * @param string $accion',
        ' * @param string $nombre_solicitante',
        ' * @return string[]|null',
        ' * @since 1.5piloto.78b',
        ' */',
        'function _contextos_para_operacion(string $accion, string $nombre_solicitante): ?array {',
        '    // Etapa 0: sin activar. La carga parcial se activa en la',
        '    // Etapa 1, cuando el flujo de index.php se reorganice para',
        '    // decidir entre cargar() y cargar_parcial() antes de cargar.',
        '    return null;',
        '}',
        '',
        '/**',
        ' * Enruta una petición POST según la acción indicada.',
        ' *',
        ' * @param string $accion Acción en formato "modulo/subaccion".',
        ' * @param array $post Datos recibidos por POST.',
        ' * @return void',
        ' */',
        'function enrutar_peticion_post(string $accion, array $post): void {',
    ],
],

// ------------------------------------------------------------
// index.php (piloto)
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bump @version a 1.5piloto.78b',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78a',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78b',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: comentario sobre Etapa 1',
    'buscar' => [
        '// Inicialización de la persistencia.',
        '// Va después de los require_once para que `guardar_ambos` esté',
        '// definida (vive en FuncionesAuxiliares.php).',
        'Controlador::establecer_metodo(\'SQL\');',
        '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
    ],
    'reemplazar' => [
        '// Inicialización de la persistencia.',
        '// Va después de los require_once para que `guardar_ambos` esté',
        '// definida (vive en FuncionesAuxiliares.php).',
        '//',
        '// NOTA (v1.5piloto.78b, Etapa 0 del plan de carga parcial):',
        '// Hoy se carga el grafo entero. En la Etapa 1, esta carga se',
        '// va a posponer: primero se resuelve el solicitante con un',
        '// bootstrap mínimo (cargar_parcial de [\'usuarios\',',
        '// \'sesiones\', \'aplicacion\']), después se llama a',
        '// _contextos_para_operacion($accion, $nombre_solicitante) para',
        '// decidir entre Controlador::cargar() (carga total) y',
        '// Controlador::cargar_parcial($nombre, $contextos).',
        '// El método de persistencia tiene que pasar de SQL a SQL64,',
        '// que es el que soporta contextos.',
        '// Ver `prompts/plan_actual.md` §1.10.',
        'Controlador::establecer_metodo(\'SQL\');',
        '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
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