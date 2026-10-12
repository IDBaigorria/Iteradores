<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.2: activar carga parcial.
 *
 *  - index.php (78g): determina modo de carga antes de cargar.
 *    Bootstrap (aplicación, admin, migraciones) solo en total.
 *  - Enrutador.php (78g): _contextos_para_operacion implementado.
 *  - GrafoCredenciales.php (78g): preserva y restaura contextos.
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
// index.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bump @version a 1.5piloto.78g',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78f',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78g',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: reestructurar carga (parcial o total)',
    'buscar' => [
        '// NOTA (v1.5piloto.78e, Etapa 1c.1 del plan de carga parcial):',
        '// El método de persistencia ya es SQL64 (soporta contextos).',
        '// La carga sigue siendo total (Controlador::cargar). En la',
        '// Etapa 1c.2 se pospone la carga: primero se resuelve el',
        '// solicitante con un bootstrap mínimo, después se llama a',
        '// _contextos_para_operacion($accion, $nombre_solicitante)',
        '// para decidir entre Controlador::cargar() (carga total) y',
        '// Controlador::cargar_parcial($nombre, $contextos).',
        '// Ver `prompts/plan_actual.md` §1.10.',
        'Controlador::establecer_metodo(\'SQL64\');',
        '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
        'if (Controlador::existe($nombre_app)) {',
        '    if (!Controlador::cargar($nombre_app)) {',
        '        // Existe en SQL pero no se pudo cargar. No caemos al else:',
        '        // guardar vacío pisaría el grafo. Morimos con mensaje claro.',
        '        Controlador::_error("index.php: no se pudo cargar el grafo \\"$nombre_app\\".");',
        '        http_response_code(500);',
        '        die(\'Error fatal: no se pudo cargar el grafo principal. Revise los logs.\');',
        '    }',
        '} else {',
        '    // Primera ejecución: no existe el grafo. Se crea vacío.',
        '    guardar_ambos($nombre_app);',
        '}',
    ],
    'reemplazar' => [
        '// NOTA (v1.5piloto.78g, Etapa 1c.2):',
        '// El modo de carga (parcial o total) se decide ANTES de cargar.',
        '// Regla: si hay solicitante, se carga parcial con [\'us_<sol>\'].',
        '// La topología decide el resto: el admin tiene un enlace a',
        '// `usuarios` desde su propio nodo, así que su carga parcial',
        '// termina trayendo todo; el soporte, solo a sus dueños.',
        '// Si no hay solicitante (GET, migraciones, imprimir sin dueño),',
        '// se carga total. Ver `prompts/plan_actual.md` §1.10.',
        'Controlador::establecer_metodo(\'SQL64\');',
        '$nombre_app = ConfiguracionApli::NOMBRE_APP;',
        '$contextos_carga = _contextos_para_operacion(',
        '    $_POST[\'accion\'] ?? \'\',',
        '    $_POST[\'nombre_solicitante\'] ?? \'\',',
        '    $_GET',
        ');',
        '$modo_parcial = ($contextos_carga !== null);',
        'if (Controlador::existe($nombre_app)) {',
        '    $ok_carga = $modo_parcial',
        '        ? Controlador::cargar_parcial($nombre_app, $contextos_carga)',
        '        : Controlador::cargar($nombre_app);',
        '    if (!$ok_carga) {',
        '        // Existe en SQL64 pero no se pudo cargar. No caemos al else:',
        '        // guardar vacío pisaría el grafo. Morimos con mensaje claro.',
        '        Controlador::_error("index.php: no se pudo cargar el grafo \\"$nombre_app\\".");',
        '        http_response_code(500);',
        '        die(\'Error fatal: no se pudo cargar el grafo principal. Revise los logs.\');',
        '    }',
        '} else {',
        '    // Primera ejecución: no existe el grafo. Se crea vacío.',
        '    guardar_ambos($nombre_app);',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: envolver bootstrap de aplicacion y admin en !modo_parcial',
    'buscar' => [
        '// Crear el nodo especial `aplicacion` con su contenedor',
        '// `migraciones`, si no existen. Se usa para marcar las',
        '// migraciones aplicadas.',
        'if (!Nodo::nodo_por_id(\'aplicacion\')) {',
        '    $nodo_app_meta = Nodo::crear_con_id(\'aplicacion\');',
        '    if ($nodo_app_meta) {',
        '        $contenedor_mig = Nodo::crear_con_dato(\'\');',
        '        $nodo_app_meta->_adyacente_en($contenedor_mig, \'migraciones\');',
        '        guardar_ambos($nombre_app);',
        '    }',
        '} else {',
        '    // Existe el nodo aplicacion pero puede faltar el contenedor.',
        '    $nodo_app_meta = Nodo::nodo_por_id(\'aplicacion\');',
        '    if ($nodo_app_meta && !$nodo_app_meta->adyacente(\'migraciones\')) {',
        '        $contenedor_mig = Nodo::crear_con_dato(\'\');',
        '        $nodo_app_meta->_adyacente_en($contenedor_mig, \'migraciones\');',
        '        guardar_ambos($nombre_app);',
        '    }',
        '}',
    ],
    'reemplazar' => [
        '// Crear el nodo especial `aplicacion` con su contenedor',
        '// `migraciones`, si no existen. Se usa para marcar las',
        '// migraciones aplicadas. Solo en carga total: con carga',
        '// parcial no se puede navegar la raíz `usuarios` ni crear',
        '// nodos nuevos que no pertenezcan al contexto.',
        'if (!$modo_parcial) {',
        '    if (!Nodo::nodo_por_id(\'aplicacion\')) {',
        '        $nodo_app_meta = Nodo::crear_con_id(\'aplicacion\');',
        '        if ($nodo_app_meta) {',
        '            $contenedor_mig = Nodo::crear_con_dato(\'\');',
        '            $nodo_app_meta->_adyacente_en($contenedor_mig, \'migraciones\');',
        '            guardar_ambos($nombre_app);',
        '        }',
        '    } else {',
        '        // Existe el nodo aplicacion pero puede faltar el contenedor.',
        '        $nodo_app_meta = Nodo::nodo_por_id(\'aplicacion\');',
        '        if ($nodo_app_meta && !$nodo_app_meta->adyacente(\'migraciones\')) {',
        '            $contenedor_mig = Nodo::crear_con_dato(\'\');',
        '            $nodo_app_meta->_adyacente_en($contenedor_mig, \'migraciones\');',
        '            guardar_ambos($nombre_app);',
        '        }',
        '    }',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: envolver bootstrap del admin en !modo_parcial',
    'buscar' => [
        '// Crear usuario administrador si no existe (en ambos grafos).',
        '// Fase B3.1: el chequeo del admin se hace por ID especial.',
        '$raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '$nodo_admin_existente = Nodo::nodo_por_id(\'us_\' . ConfiguracionApli::NOMBRE_ADMIN);',
        'if (!$nodo_admin_existente) {',
        '    if (!$raiz_usuarios) {',
        '        Nodo::crear_con_id(\'usuarios\');',
        '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    }',
        '    $id_especial_admin = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
        '    $nodo_admin = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin);',
        '    if ($nodo_admin) {',
        '        $nodo_admin->_adyacente_en(Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN), \'nombre_real\');',
        '        $nodo_admin->_adyacente_en(Nodo::crear_con_dato(\'admin\'), \'nivel\');',
        '        $raiz_usuarios->_adyacente_en($nodo_admin, ConfiguracionApli::NOMBRE_ADMIN);',
        '    }',
        '    guardar_ambos($nombre_app);',
        '}',
        '',
        '// Crear admin en el grafo de credenciales si no existe.',
        'en_grafo_credenciales(function() {',
        '    $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_cred) {',
        '        Nodo::crear_con_id(\'usuarios\');',
        '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
        '    }',
        '    // Fase B3.1: chequeo por ID especial.',
        '    if (!Nodo::nodo_por_id(\'us_\' . ConfiguracionApli::NOMBRE_ADMIN)) {',
        '        $id_especial_admin_cred = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
        '        $nodo_admin_cred = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin_cred);',
        '        if ($nodo_admin_cred) {',
        '            $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), \'codigo_hash\');',
        '            $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);',
        '        }',
        '    }',
        '});',
    ],
    'reemplazar' => [
        '// Crear usuario administrador si no existe (en ambos grafos).',
        '// Fase B3.1: el chequeo del admin se hace por ID especial.',
        '// Solo en carga total: el bootstrap del admin necesita la',
        '// raíz `usuarios` completa, que en carga parcial no está.',
        'if (!$modo_parcial) {',
        '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    $nodo_admin_existente = Nodo::nodo_por_id(\'us_\' . ConfiguracionApli::NOMBRE_ADMIN);',
        '    if (!$nodo_admin_existente) {',
        '        if (!$raiz_usuarios) {',
        '            Nodo::crear_con_id(\'usuarios\');',
        '            $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '        }',
        '        $id_especial_admin = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
        '        $nodo_admin = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin);',
        '        if ($nodo_admin) {',
        '            $nodo_admin->_adyacente_en(Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN), \'nombre_real\');',
        '            $nodo_admin->_adyacente_en(Nodo::crear_con_dato(\'admin\'), \'nivel\');',
        '            $raiz_usuarios->_adyacente_en($nodo_admin, ConfiguracionApli::NOMBRE_ADMIN);',
        '        }',
        '        guardar_ambos($nombre_app);',
        '    }',
        '',
        '    // Crear admin en el grafo de credenciales si no existe.',
        '    en_grafo_credenciales(function() {',
        '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
        '        if (!$raiz_cred) {',
        '            Nodo::crear_con_id(\'usuarios\');',
        '            $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
        '        }',
        '        // Fase B3.1: chequeo por ID especial.',
        '        if (!Nodo::nodo_por_id(\'us_\' . ConfiguracionApli::NOMBRE_ADMIN)) {',
        '            $id_especial_admin_cred = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
        '            $nodo_admin_cred = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin_cred);',
        '            if ($nodo_admin_cred) {',
        '                $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), \'codigo_hash\');',
        '                $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);',
        '            }',
        '        }',
        '    });',
        '}',
    ],
],

// ------------------------------------------------------------
// Enrutador.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: bump @version a 1.5piloto.78g',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78b',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78g',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: implementar _contextos_para_operacion',
    'buscar' => [
        'function _contextos_para_operacion(string $accion, string $nombre_solicitante): ?array {',
        '    // Etapa 0: sin activar. La carga parcial se activa en la',
        '    // Etapa 1, cuando el flujo de index.php se reorganice para',
        '    // decidir entre cargar() y cargar_parcial() antes de cargar.',
        '    return null;',
        '}',
    ],
    'reemplazar' => [
        'function _contextos_para_operacion(string $accion, string $nombre_solicitante, array $get = []): ?array {',
        '    // Caso 1: POST con acción. Es la operación principal.',
        '    // Si hay solicitante, carga parcial con su contexto. La',
        '    // topología decide el resto:',
        '    //   - Admin: tiene un enlace `usuarios` desde su propio',
        '    //     nodo (migración 1c.1b). Su carga parcial trae todo.',
        '    //   - Soporte: su carga parcial trae solo sus dueños',
        '    //     asignados (enlace `duenos`).',
        '    //   - Dueño / terminal: su propio subgrafo.',
        '    // Si no hay solicitante, carga total (fallback).',
        '    if ($accion !== \'\') {',
        '        if ($nombre_solicitante === \'\') return null;',
        '        return [\'us_\' . $nombre_solicitante];',
        '    }',
        '',
        '    // Caso 2: GET de impresión. Los flujos imprimibles pasan',
        '    // el dueño por query string. Si está, carga parcial de',
        '    // ese dueño. Si no, carga total (los 6 helpers de B3.3c',
        '    // que iteran sobre todos los dueños todavía no están',
        '    // reescritos; ver `plan_actual.md` §1.10).',
        '    if (isset($get[\'imprimir\']) && $get[\'imprimir\'] === \'1\') {',
        '        $dueno = $get[\'dueno\'] ?? \'\';',
        '        if ($dueno === \'\') return null;',
        '        return [\'us_\' . $dueno];',
        '    }',
        '',
        '    // Caso 3: cualquier otro GET. Incluye:',
        '    //   - la carga del HTML de la interfaz,',
        '    //   - los bloques ?migrar_*=1 y ?probar_*=1 (necesitan',
        '    //     el grafo completo),',
        '    //   - cualquier diagnóstico.',
        '    // Todos carga total.',
        '    return null;',
        '}',
    ],
],

// ------------------------------------------------------------
// GrafoCredenciales.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/GrafoCredenciales.php',
    'descripcion' => 'GrafoCredenciales: bump @version a 1.5piloto.78g',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.70',
        ' * @version   1.5piloto.76f',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.70',
        ' * @version   1.5piloto.78g',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/GrafoCredenciales.php',
    'descripcion' => 'GrafoCredenciales: en_grafo_credenciales preserva contextos',
    'buscar' => [
        'function en_grafo_credenciales(callable $fn) {',
        '    if (!empty($GLOBALS[\'__en_grafo_credenciales\'])) {',
        '        return $fn();',
        '    }',
        '    $GLOBALS[\'__en_grafo_credenciales\'] = true;',
        '    try {',
    ],
    'reemplazar' => [
        'function en_grafo_credenciales(callable $fn) {',
        '    if (!empty($GLOBALS[\'__en_grafo_credenciales\'])) {',
        '        return $fn();',
        '    }',
        '    $GLOBALS[\'__en_grafo_credenciales\'] = true;',
        '',
        '    // v78g: capturar los contextos activos de la app para',
        '    // restaurarlos al volver. Sin esto, la carga de credenciales',
        '    // resetea el modo de carga a total, y al recargar la app se',
        '    // pierde el contexto original (guardar_ambos guardaría todo',
        '    // en lugar de solo el contexto).',
        '    $contextos_app_previos = Controlador::contextos_cargados();',
        '',
        '    try {',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/GrafoCredenciales.php',
    'descripcion' => 'GrafoCredenciales: en_grafo_credenciales restaura contextos',
    'buscar' => [
        '    } finally {',
        '        // Recargar la app. Si falla, es un error fatal: la superestructura',
        '        // quedaría vacía y el próximo guardado podría pisar el grafo.',
        '        $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);',
        '        $GLOBALS[\'__en_grafo_credenciales\'] = false;',
        '        if (!$ok_carga) {',
        '            Controlador::_error("en_grafo_credenciales: no se pudo recargar la app \\"" . ConfiguracionApli::NOMBRE_APP . "\\".");',
        '            throw new \\RuntimeException("No se pudo recargar el grafo de la aplicacion tras operar en credenciales.");',
        '        }',
        '    }',
        '}',
    ],
    'reemplazar' => [
        '    } finally {',
        '        // Recargar la app respetando el modo previo (parcial o total).',
        '        // Si falla, es un error fatal: la superestructura quedaría',
        '        // vacía y el próximo guardado podría pisar el grafo.',
        '        if (!empty($contextos_app_previos)) {',
        '            $ok_carga = Controlador::cargar_parcial(ConfiguracionApli::NOMBRE_APP, $contextos_app_previos);',
        '        } else {',
        '            $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);',
        '        }',
        '        $GLOBALS[\'__en_grafo_credenciales\'] = false;',
        '        if (!$ok_carga) {',
        '            Controlador::_error("en_grafo_credenciales: no se pudo recargar la app \\"" . ConfiguracionApli::NOMBRE_APP . "\\".");',
        '            throw new \\RuntimeException("No se pudo recargar el grafo de la aplicacion tras operar en credenciales.");',
        '        }',
        '    }',
        '}',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/GrafoCredenciales.php',
    'descripcion' => 'GrafoCredenciales: solo_lectura preserva contextos',
    'buscar' => [
        'function en_grafo_credenciales_solo_lectura(callable $fn) {',
        '    if (!empty($GLOBALS[\'__en_grafo_credenciales\'])) {',
        '        return $fn();',
        '    }',
        '    $GLOBALS[\'__en_grafo_credenciales\'] = true;',
        '    try {',
        '        // Crear credenciales si no existe (raro pero posible).',
    ],
    'reemplazar' => [
        'function en_grafo_credenciales_solo_lectura(callable $fn) {',
        '    if (!empty($GLOBALS[\'__en_grafo_credenciales\'])) {',
        '        return $fn();',
        '    }',
        '    $GLOBALS[\'__en_grafo_credenciales\'] = true;',
        '',
        '    // v78g: idem en_grafo_credenciales. Preservar contextos.',
        '    $contextos_app_previos = Controlador::contextos_cargados();',
        '',
        '    try {',
        '        // Crear credenciales si no existe (raro pero posible).',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/GrafoCredenciales.php',
    'descripcion' => 'GrafoCredenciales: solo_lectura restaura contextos',
    'buscar' => [
        '    } finally {',
        '        // Recargar la app. Si falla, es un error fatal: la',
        '        // superestructura quedaría vacía y el próximo guardado',
        '        // podría pisar el grafo.',
        '        $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);',
        '        $GLOBALS[\'__en_grafo_credenciales\'] = false;',
        '        if (!$ok_carga) {',
        '            Controlador::_error("en_grafo_credenciales_solo_lectura: no se pudo recargar la app \\"" . ConfiguracionApli::NOMBRE_APP . "\\".");',
        '            throw new \\RuntimeException("No se pudo recargar el grafo de la aplicacion tras leer credenciales.");',
        '        }',
        '    }',
        '}',
    ],
    'reemplazar' => [
        '    } finally {',
        '        // Recargar la app respetando el modo previo (parcial o total).',
        '        if (!empty($contextos_app_previos)) {',
        '            $ok_carga = Controlador::cargar_parcial(ConfiguracionApli::NOMBRE_APP, $contextos_app_previos);',
        '        } else {',
        '            $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);',
        '        }',
        '        $GLOBALS[\'__en_grafo_credenciales\'] = false;',
        '        if (!$ok_carga) {',
        '            Controlador::_error("en_grafo_credenciales_solo_lectura: no se pudo recargar la app \\"" . ConfiguracionApli::NOMBRE_APP . "\\".");',
        '            throw new \\RuntimeException("No se pudo recargar el grafo de la aplicacion tras leer credenciales.");',
        '        }',
        '    }',
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