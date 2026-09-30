<?php
/**
 * Aplicador de cambios automáticos — AdministradorDeViajes.
 *
 * Tanda v1.5piloto.73k: mover `guardar_ambos` a FuncionesAuxiliares,
 * reordenar los includes de index.php y eliminar GuardarAmbos.php.
 *
 * Corregido: el bloque de búsqueda para agregar `guardar_ambos` al
 * final de FuncionesAuxiliares.php ya no incluye la línea del regex
 * de validar_direccion (tenía caracteres especiales que no matcheaban).
 *
 * - FuncionesAuxiliares.php: agrega `use`/`include_once` y la función
 *   `guardar_ambos`.
 * - index.php: mueve el bloque de inicialización de la persistencia
 *   después de los require_once, y quita el require de GuardarAmbos.
 * - Aplicacion/GuardarAmbos.php: se elimina.
 * - prompts/prompt_piloto.md: se actualiza la estructura, secciones
 *   5.1 y 5.5, historial y Discusión actual.
 * - prompts/prompt_sistema_scripts.md: se agrega el tipo `eliminar`
 *   al runner.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
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

    // ============================================================
    // Aplicacion/FuncionesAuxiliares.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Header + use + include_once + bump a 73k',
        'buscar' => [
            '<?php',
            '/**',
            ' * Funciones auxiliares de formato y validación.',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.37',
            ' */',
            '',
            '/**',
            ' * Normaliza un DNI: devuelve solo los dígitos.',
        ],
        'reemplazar' => [
            '<?php',
            '/**',
            ' * Funciones auxiliares de formato, validación y persistencia.',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.73k',
            ' */',
            '',
            'use Iteradores\Controlador\Controlador;',
            'use Iteradores\Configuracion\Conf;',
            'use Iteradores\Nodos\Nodo;',
            'include_once(__DIR__ . \'/../Configuracion/Configuracion.php\');',
            'include_once(__DIR__ . \'/../Controlador/Controlador.php\');',
            'include_once(__DIR__ . \'/../Nodos/Nodo.php\');',
            '',
            '/**',
            ' * Normaliza un DNI: devuelve solo los dígitos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Agregar guardar_ambos al final',
        'buscar' => [
            '        return \'La dirección tiene caracteres no permitidos\';',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => [
            '        return \'La dirección tiene caracteres no permitidos\';',
            '    }',
            '    return null;',
            '}',
            '',
            '// ============================================================',
            '// Persistencia',
            '// ============================================================',
            '',
            '/**',
            ' * Guarda una superestructura en SQL y después en JSON.',
            ' *',
            ' * El JSON es solo respaldo. Si falla, se registra el error con el',
            ' * sistema centralizado de Objeto y la operación sigue siendo exitosa',
            ' * porque SQL ya persistió.',
            ' *',
            ' * @param string $nombre Nombre de la superestructura.',
            ' * @return bool True si el guardado en SQL fue exitoso.',
            ' */',
            'function guardar_ambos($nombre): bool {',
            '    if (!is_string($nombre) || $nombre === \'\') {',
            '        Controlador::_error("guardar_ambos: nombre invalido");',
            '        return false;',
            '    }',
            '',
            '    // 1) Guardar en SQL (fuente de verdad).',
            '    $ok_sql = Controlador::guardar($nombre);',
            '    if (!$ok_sql) {',
            '        return false;',
            '    }',
            '',
            '    // 2) Guardar en JSON (respaldo). No debe romper la operación.',
            '    try {',
            '        Controlador::establecer_metodo(\'JSON\');',
            '        $ok_json = Controlador::guardar($nombre);',
            '        if (!$ok_json) {',
            '            Controlador::_error("guardar_ambos: fallo el guardado JSON para el grafo \\"$nombre\\"");',
            '        }',
            '    } catch (\\Throwable $e) {',
            '        Controlador::_error("guardar_ambos: excepcion al guardar JSON para \\"$nombre\\": " . $e->getMessage());',
            '    } finally {',
            '        Controlador::establecer_metodo(\'SQL\');',
            '    }',
            '',
            '    return true;',
            '}',
        ],
    ],

    // ============================================================
    // index.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Bump de version a 1.5piloto.73k',
        'buscar' => [
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73k',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Mover bloque de persistencia y quitar require de GuardarAmbos',
        'buscar' => [
            '// Inicialización de la persistencia',
            'Controlador::establecer_metodo(\'SQL\');',
            '$nombre_app = Conf::NOMBRE_APP;',
            'if (Controlador::existe($nombre_app)) {',
            '    Controlador::cargar($nombre_app);',
            '} else {',
            '    guardar_ambos($nombre_app);',
            '}',
            '',
            '// Incluir módulos de la aplicación',
            'require_once __DIR__ . \'/Aplicacion/GuardarAmbos.php\';',
            'require_once __DIR__ . \'/Aplicacion/FuncionesAuxiliares.php\';',
        ],
        'reemplazar' => [
            '// Incluir módulos de la aplicación.',
            '// Primero `FuncionesAuxiliares.php`, que define `guardar_ambos`.',
            'require_once __DIR__ . \'/Aplicacion/FuncionesAuxiliares.php\';',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Agregar bloque de persistencia despues de require_once de Enrutador',
        'buscar' => [
            'require_once __DIR__ . \'/Aplicacion/Enrutador.php\';',
            '',
            '',
            '// ==== Bloque temporal para pruebas de árbol ====',
        ],
        'reemplazar' => [
            'require_once __DIR__ . \'/Aplicacion/Enrutador.php\';',
            '',
            '// Inicialización de la persistencia.',
            '// Va después de los require_once para que `guardar_ambos` esté',
            '// definida (vive en FuncionesAuxiliares.php).',
            'Controlador::establecer_metodo(\'SQL\');',
            '$nombre_app = Conf::NOMBRE_APP;',
            'if (Controlador::existe($nombre_app)) {',
            '    Controlador::cargar($nombre_app);',
            '} else {',
            '    guardar_ambos($nombre_app);',
            '}',
            '',
            '// ==== Bloque temporal para pruebas de árbol ====',
        ],
    ],

    // ============================================================
    // Eliminar Aplicacion/GuardarAmbos.php
    // ============================================================

    [
        'tipo' => 'eliminar',
        'archivo' => 'Aplicacion/GuardarAmbos.php',
        'descripcion' => 'Helper mudado a FuncionesAuxiliares.php',
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estructura de archivos: quitar GuardarAmbos y aclarar FuncionesAuxiliares',
        'buscar' => [
            '**`Aplicacion/`:**',
            '- `GrafoCredenciales.php`: helper `en_grafo_credenciales`.',
            '- `GuardarAmbos.php`: helper `guardar_ambos`.',
            '- `admin.js`, `terminales.js`, `micros.js`.',
        ],
        'reemplazar' => [
            '**`Aplicacion/`:**',
            '- `GrafoCredenciales.php`: helper `en_grafo_credenciales`.',
            '- `FuncionesAuxiliares.php`: helpers de formato, validación',
            '  y `guardar_ambos`.',
            '- `admin.js`, `terminales.js`, `micros.js`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Seccion 5.1: agregar guardar_ambos',
        'buscar' => [
            '### 5.1 `Aplicacion/FuncionesAuxiliares.php`',
            '',
            '- `normalizar_dni`, `formatear_dni_con_puntos`.',
            '- `formatear_fecha_visible`.',
            '- `formatear_nombre_completo`.',
            '- Validaciones: `validar_dni`, `validar_telefono`, `validar_email`,',
            '  `validar_nombre_o_apellido`, `validar_fecha_nacimiento`,',
            '  `validar_localidad`, `validar_direccion`.',
        ],
        'reemplazar' => [
            '### 5.1 `Aplicacion/FuncionesAuxiliares.php`',
            '',
            '- `normalizar_dni`, `formatear_dni_con_puntos`.',
            '- `formatear_fecha_visible`.',
            '- `formatear_nombre_completo`.',
            '- Validaciones: `validar_dni`, `validar_telefono`, `validar_email`,',
            '  `validar_nombre_o_apellido`, `validar_fecha_nacimiento`,',
            '  `validar_localidad`, `validar_direccion`.',
            '- `guardar_ambos($nombre)`: guarda la superestructura en SQL',
            '  (fuente de verdad) y después en JSON (respaldo). Desde v73k',
            '  vive acá, no en `GuardarAmbos.php`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Seccion 5.5: reescribir como nota historica',
        'buscar' => [
            '### 5.5 `Aplicacion/GuardarAmbos.php`',
            '',
            '- `guardar_ambos($nombre)`: SQL primero, después JSON. Si JSON falla,',
            '  `Controlador::_error()`. Devuelve true si SQL fue exitoso.',
        ],
        'reemplazar' => [
            '### 5.5 Persistencia SQL + JSON',
            '',
            '`guardar_ambos($nombre)` vive en `FuncionesAuxiliares.php`',
            '(ver 5.1). Guarda la superestructura en SQL (fuente de verdad)',
            'y después en JSON (respaldo). Si JSON falla, `Controlador::_error()`.',
            'Devuelve true si SQL fue exitoso.',
            '',
            'El archivo `Aplicacion/GuardarAmbos.php` existió hasta v73j. Se',
            'eliminó en v73k: el helper se movió a `FuncionesAuxiliares.php` y',
            'se reordenaron los `require_once` en `index.php` para que',
            '`guardar_ambos` esté definida antes de inicializar la persistencia.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v73k',
        'buscar' => [
            '  permiso sobre la sesión (nuevo helper `_puede_cerrar_sesion`).',
            '  Nueva función `listar_nombres_usuarios_para_soporte`.',
        ],
        'reemplazar' => [
            '  permiso sobre la sesión (nuevo helper `_puede_cerrar_sesion`).',
            '  Nueva función `listar_nombres_usuarios_para_soporte`.',
            '- **v73k**: fix de orden de includes en `index.php`. El helper',
            '  `guardar_ambos` se movió de `Aplicacion/GuardarAmbos.php` a',
            '  `Aplicacion/FuncionesAuxiliares.php`, y el bloque de',
            '  inicialización de la persistencia se reordenó después de los',
            '  `require_once` para que `guardar_ambos` esté definida antes',
            '  de usarse. Eliminado `Aplicacion/GuardarAmbos.php`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Discusion actual: bump a v73k',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73j (fix de',
            'permisos de sesiones: soporte filtrado por dueño, cierre validado).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73k (mover',
            '`guardar_ambos` a `FuncionesAuxiliares.php` y reordenar los includes',
            'de `index.php`).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado de la conversacion: agregar v73k',
        'buscar' => [
            '- Cerramos en v73j el fix de permisos de sesiones (fuga de datos',
            '  entre roles y falta de validación al cerrar sesión ajena).',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73j el fix de permisos de sesiones (fuga de datos',
            '  entre roles y falta de validación al cerrar sesión ajena).',
            '- Cerramos en v73k el fix del orden de includes en `index.php`',
            '  y la mudanza de `guardar_ambos` a `FuncionesAuxiliares.php`.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73j. Todo funcional.',
            'Listo para arrancar la diversificación por tipo de aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73k. Todo funcional.',
            'Listo para arrancar la diversificación por tipo de aplicación.',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Runner: agregar tipo eliminar',
        'buscar' => [
            '$creaciones = [];',
            '$reemplazos_por_archivo = [];',
            '',
            'foreach ($cambios as $cambio) {',
            '    $tipo = $cambio[\'tipo\'] ?? \'reemplazar\';',
            '    if ($tipo === \'crear\') { $creaciones[] = $cambio; continue; }',
            '    if (!isset($cambio[\'archivo\']) || !isset($cambio[\'buscar\']) || !isset($cambio[\'reemplazar\'])) {',
            '        echo "[FALLO] Cambio mal formado (faltan campos).\\n";',
            '        exit(1);',
            '    }',
            '    $reemplazos_por_archivo[$cambio[\'archivo\']][] = $cambio;',
            '}',
            '',
            '$total_reemplazos = 0;',
            'foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }',
            '',
            'echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "',
            '    . $total_reemplazos . " reemplazo(s) en "',
            '    . count($reemplazos_por_archivo) . " archivo(s).\\n\\n";',
        ],
        'reemplazar' => [
            '$creaciones = [];',
            '$eliminaciones = [];',
            '$reemplazos_por_archivo = [];',
            '',
            'foreach ($cambios as $cambio) {',
            '    $tipo = $cambio[\'tipo\'] ?? \'reemplazar\';',
            '    if ($tipo === \'crear\') { $creaciones[] = $cambio; continue; }',
            '    if ($tipo === \'eliminar\') { $eliminaciones[] = $cambio; continue; }',
            '    if (!isset($cambio[\'archivo\']) || !isset($cambio[\'buscar\']) || !isset($cambio[\'reemplazar\'])) {',
            '        echo "[FALLO] Cambio mal formado (faltan campos).\\n";',
            '        exit(1);',
            '    }',
            '    $reemplazos_por_archivo[$cambio[\'archivo\']][] = $cambio;',
            '}',
            '',
            '$total_reemplazos = 0;',
            'foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }',
            '',
            'echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "',
            '    . $total_reemplazos . " reemplazo(s) en "',
            '    . count($reemplazos_por_archivo) . " archivo(s), "',
            '    . count($eliminaciones) . " archivo(s) a eliminar.\\n\\n";',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Runner: bloque de eliminaciones',
        'buscar' => [
            '    $accion = $ya_existia ? \'sobrescrito\' : \'creado\';',
            '    echo "[OK] {$creacion[\'archivo\']} ($accion)\\n";',
            '}',
            '',
            'echo "\\n=== Resumen ===\\n";',
        ],
        'reemplazar' => [
            '    $accion = $ya_existia ? \'sobrescrito\' : \'creado\';',
            '    echo "[OK] {$creacion[\'archivo\']} ($accion)\\n";',
            '}',
            '',
            'foreach ($eliminaciones as $elim) {',
            '    $ruta_abs = $raiz_proyecto . \'/\' . $elim[\'archivo\'];',
            '    if (!file_exists($ruta_abs)) {',
            '        echo "[INFO] " . $elim[\'archivo\'] . " no existía (nada que eliminar).\\n";',
            '        continue;',
            '    }',
            '    if (unlink($ruta_abs)) {',
            '        echo "[OK] " . $elim[\'archivo\'] . " (eliminado)\\n";',
            '    } else {',
            '        echo "[FALLO] No se pudo eliminar: " . $elim[\'archivo\'] . "\\n";',
            '    }',
            '}',
            '',
            'echo "\\n=== Resumen ===\\n";',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Documentar tipo eliminar',
        'buscar' => [
            '**Ojo:** el tipo `crear` **sobrescribe** si el archivo ya existe. El runner lo',
            'reporta como `(sobrescrito)` en el log.',
        ],
        'reemplazar' => [
            '**Ojo:** el tipo `crear` **sobrescribe** si el archivo ya existe. El runner lo',
            'reporta como `(sobrescrito)` en el log.',
            '',
            '### Tipo `eliminar`',
            '',
            '```php',
            '[',
            '    \'tipo\' => \'eliminar\',',
            '    \'archivo\' => \'ruta/relativa/al/archivo/a/eliminar.ext\',',
            '    \'descripcion\' => \'Archivo que ya no se usa\',',
            '],',
            '```',
            '',
            'El runner lo borra si existe. Si no existe, lo reporta como',
            'informativo y sigue (es idempotente: correrlo dos veces no es error).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Discusion actual: bump a v73l',
        'buscar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.73k. Se agregó la',
            'regla del formato del título de commit: siempre arranca con la versión',
            'completa del proyecto, con `V` mayúscula (`V1.5piloto.XX`).',
            '',
            'Reglas incorporadas al método de trabajo en las últimas tandas:',
            '',
            '1. **Bumps obligatorios siempre.** En cada tanda se bumpean todos los',
            '   archivos modificados (tanto `@version` internos como `?v=` en HTML).',
            '   Si no se tiene el HTML a mano, se pide antes de entregar el script.',
            '2. **Vigencia de los archivos.** Durante la conversación el usuario no',
            '   modifica archivos por su cuenta. Las versiones que el asistente',
            '   tiene son siempre las últimas. Si el usuario cambia algo, lo avisa.',
            '3. **Formato del título de commit.** Siempre `V1.5piloto.XX: Título corto`,',
            '   con `V` mayúscula y la versión completa del proyecto.',
        ],
        'reemplazar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.73l. Se agregó el',
            'tipo `eliminar` al runner, para poder borrar archivos que ya no se usan',
            'en una tanda.',
            '',
            'Reglas incorporadas al método de trabajo en las últimas tandas:',
            '',
            '1. **Bumps obligatorios siempre.** En cada tanda se bumpean todos los',
            '   archivos modificados (tanto `@version` internos como `?v=` en HTML).',
            '   Si no se tiene el HTML a mano, se pide antes de entregar el script.',
            '2. **Vigencia de los archivos.** Durante la conversación el usuario no',
            '   modifica archivos por su cuenta. Las versiones que el asistente',
            '   tiene son siempre las últimas. Si el usuario cambia algo, lo avisa.',
            '3. **Formato del título de commit.** Siempre `V1.5piloto.XX: Título corto`,',
            '   con `V` mayúscula y la versión completa del proyecto.',
            '4. **Tipo `eliminar` en `$cambios`.** El runner acepta un tercer tipo',
            '   de cambio que borra un archivo. Es idempotente: si el archivo no',
            '   existe, lo reporta y sigue.',
            '',
            '**Lección aprendida (tanda v73k):** cuando un bloque `buscar` incluye',
            'caracteres especiales (tildes, símbolos, secuencias de escape), usar',
            'un ancla más corta y sin esos caracteres. Ejemplo: en vez de matchear',
            'la línea del `preg_match` con tildes y `\\s`, matchear solo el `return`',
            'que viene justo después.',
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