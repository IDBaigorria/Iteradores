<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Mini-tanda: chequeos defensivos de grafo parcial en las
 * funciones que necesitan listar TODOS los usuarios.
 *
 * Sin cambio de comportamiento (el flag está en false
 * hasta que se active la carga parcial en la Tanda 1c).
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
// Usuario.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: bump @version a 1.5piloto.78d',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78a',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78d',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en buscar_usuario_por_codigo',
    'buscar' => [
        'function buscar_usuario_por_codigo(string $codigo): ?array {',
        '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_usuarios) return null;',
    ],
    'reemplazar' => [
        'function buscar_usuario_por_codigo(string $codigo): ?array {',
        '    // Chequeo defensivo: esta función necesita la raíz `usuarios`',
        '    // completa (itera sobre todos). En carga parcial no se puede.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("buscar_usuario_por_codigo: el grafo en memoria es parcial; no se pueden listar todos los usuarios.");',
        '        return null;',
        '    }',
        '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz_usuarios) return null;',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en listar_usuarios',
    'buscar' => [
        'function listar_usuarios(): array {',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) return [];',
    ],
    'reemplazar' => [
        'function listar_usuarios(): array {',
        '    // Chequeo defensivo: listar todos los usuarios requiere la',
        '    // raíz `usuarios` completa. En carga parcial no se puede.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("listar_usuarios: el grafo en memoria es parcial; no se pueden listar todos los usuarios.");',
        '        return [];',
        '    }',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) return [];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en listar_duenos',
    'buscar' => [
        'function listar_duenos(string $nombre_solicitante = \'\'): array {',
        '    $todos = listar_usuarios();',
    ],
    'reemplazar' => [
        'function listar_duenos(string $nombre_solicitante = \'\'): array {',
        '    // Chequeo defensivo: listar todos los dueños requiere la',
        '    // raíz `usuarios` completa (aunque listar_usuarios también',
        '    // lo chequea, el mensaje específico es más claro).',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("listar_duenos: el grafo en memoria es parcial; no se pueden listar todos los dueños.");',
        '        return [];',
        '    }',
        '    $todos = listar_usuarios();',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en listar_soportes',
    'buscar' => [
        'function listar_soportes(): array {',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) return [];',
    ],
    'reemplazar' => [
        'function listar_soportes(): array {',
        '    // Chequeo defensivo: listar todos los soportes requiere la',
        '    // raíz `usuarios` completa. En carga parcial no se puede.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("listar_soportes: el grafo en memoria es parcial; no se pueden listar todos los soportes.");',
        '        return [];',
        '    }',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) return [];',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en agregar_usuario',
    'buscar' => [
        '    // Fase B3.1: la raíz `usuarios` la crea `index.php` al',
        '    // arrancar. Acá no la autocreamos. El chequeo de duplicado',
        '    // se hace por ID especial, sin depender de la raíz.',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) {',
        '        return [\'exito\' => false, \'error\' => \'No existe la raíz `usuarios`.\'];',
        '    }',
    ],
    'reemplazar' => [
        '    // Chequeo defensivo: agregar un usuario requiere colgarlo',
        '    // de la raíz `usuarios` completa. En carga parcial no se',
        '    // puede.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        return [\'exito\' => false, \'error\' => \'agregar_usuario: el grafo en memoria es parcial; hace falta la carga completa.\'];',
        '    }',
        '',
        '    // Fase B3.1: la raíz `usuarios` la crea `index.php` al',
        '    // arrancar. Acá no la autocreamos. El chequeo de duplicado',
        '    // se hace por ID especial, sin depender de la raíz.',
        '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$raiz) {',
        '        return [\'exito\' => false, \'error\' => \'No existe la raíz `usuarios`.\'];',
        '    }',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Usuarios/Usuario.php',
    'descripcion' => 'Usuario: chequeo defensivo en eliminar_usuario',
    'buscar' => [
        '    if ($nombre_usuario === ConfiguracionApli::NOMBRE_ADMIN) {',
        '        return [\'exito\' => false, \'error\' => \'No se puede eliminar al administrador principal\'];',
        '    }',
        '',
        '    // Fase B3.1: ID especial directo.',
        '    $nodo_usuario = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_usuario) return [\'exito\' => false, \'error\' => \'Usuario no encontrado\'];',
    ],
    'reemplazar' => [
        '    if ($nombre_usuario === ConfiguracionApli::NOMBRE_ADMIN) {',
        '        return [\'exito\' => false, \'error\' => \'No se puede eliminar al administrador principal\'];',
        '    }',
        '',
        '    // Chequeo defensivo: eliminar un usuario requiere desenlazarlo',
        '    // de la raíz `usuarios` completa. En carga parcial no se',
        '    // puede (el nodo quedaría huérfano en la raíz).',
        '    if (Controlador::es_grafo_parcial()) {',
        '        return [\'exito\' => false, \'error\' => \'eliminar_usuario: el grafo en memoria es parcial; hace falta la carga completa.\'];',
        '    }',
        '',
        '    // Fase B3.1: ID especial directo.',
        '    $nodo_usuario = Nodo::nodo_por_id(\'us_\' . $nombre_usuario);',
        '    if (!$nodo_usuario) return [\'exito\' => false, \'error\' => \'Usuario no encontrado\'];',
    ],
],

// ------------------------------------------------------------
// Migraciones/Funciones.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Migraciones/Funciones.php',
    'descripcion' => 'Migraciones/Funciones: bump + @version',
    'buscar' => [
        ' * @since 1.5piloto.76n',
        ' */',
    ],
    'reemplazar' => [
        ' * @since 1.5piloto.76n',
        ' * @version 1.5piloto.78d',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Migraciones/Funciones.php',
    'descripcion' => 'Migraciones/Funciones: chequeo defensivo en detectar_usuarios_especiales',
    'buscar' => [
        'function detectar_usuarios_especiales(string $token): bool {',
        '    // Solo mira los hijos del contenedor `usuarios`. Es uno',
    ],
    'reemplazar' => [
        'function detectar_usuarios_especiales(string $token): bool {',
        '    // Chequeo defensivo: el detector necesita la raíz `usuarios`',
        '    // completa. En carga parcial no se puede decidir; se devuelve',
        '    // true (aplicada) para no ofrecer una migración que no se',
        '    // puede correr, y se registra el error.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("detectar_usuarios_especiales: el grafo en memoria es parcial; no se puede evaluar la migración.");',
        '        return true;',
        '    }',
        '    // Solo mira los hijos del contenedor `usuarios`. Es uno',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Migraciones/Funciones.php',
    'descripcion' => 'Migraciones/Funciones: chequeo defensivo en detectar_niveles_usuario',
    'buscar' => [
        'function detectar_niveles_usuario(string $token): bool {',
        '    // Mismo criterio que detectar_usuarios_especiales: solo',
    ],
    'reemplazar' => [
        'function detectar_niveles_usuario(string $token): bool {',
        '    // Chequeo defensivo: idem detectar_usuarios_especiales.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        Controlador::_error("detectar_niveles_usuario: el grafo en memoria es parcial; no se puede evaluar la migración.");',
        '        return true;',
        '    }',
        '    // Mismo criterio que detectar_usuarios_especiales: solo',
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