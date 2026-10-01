<?php
/**
 * Aplicador de cambios automáticos — Framework Iteradores (PHP).
 *
 * Tanda V1.5i.7e: documentación del espejo JS.
 *
 * - prompts/prompt_framework_iteradores.md: sección 12.1 ampliada
 *   (cambios del PHP sin análogo en JS), sección 12.3 ampliada
 *   (bug `if ($elemento)` en AMBOS espejos), historial 1.5i.7e.
 * - prompts/prompt_piloto.md: historial v73q, Discusión actual.
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

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Seccion 12.1: agregar cambios sin analogo en JS',
        'buscar' => [
            '- **Métodos async:** IndexedDB es asíncrono. `Controlador.delegar`,',
            '  `Controlador.guardar`, `Controlador.cargar`, `Controlador.existe`,',
            '  `Controlador.eliminar` y `Controlador.ejecutar_prueba` son `async`.',
            '',
            '### 12.2 Persistencia en IndexedDB',
        ],
        'reemplazar' => [
            '- **Métodos async:** IndexedDB es asíncrono. `Controlador.delegar`,',
            '  `Controlador.guardar`, `Controlador.cargar`, `Controlador.existe`,',
            '  `Controlador.eliminar` y `Controlador.ejecutar_prueba` son `async`.',
            '',
            '**Cambios del PHP sin análogo en JS.** No son gaps pendientes,',
            'son diferencias de plataforma:',
            '',
            '- `libxml_clear_errors()` y `libxml_use_internal_errors()`: JS no',
            '  tiene libxml. El parseo XML usa `DOMParser`, que no deja estado',
            '  acumulado entre llamadas.',
            '- `listar()` sobre JSON/XML: no aplica. El navegador no da acceso al',
            '  filesystem, así que no hay carpeta que listar. Los archivos se',
            '  descargan/cargan uno por uno vía interacción del usuario.',
            '- `real_escape_string` y todo lo relacionado con `mysqli`: no aplica.',
            '  No hay motor SQL. IndexedDB es un object store, no una base',
            '  relacional.',
            '',
            '### 12.2 Persistencia en IndexedDB',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Seccion 12.3: bug if(elemento) en ambos espejos',
        'buscar' => [
            '**`if (elemento)` descarta falsy.** `0`, `\'\'`, `false` son falsy en',
            'ambos lenguajes, pero en JS es más fácil olvidarlo porque el tipo',
            'original puede cambiar entre llamadas. Pendiente en `Iterador.js`',
            '(bug latente): `if (elemento)` debería ser',
            '`if (elemento !== null && elemento !== undefined)`.',
            '',
            '### 12.4 API del Controlador JS',
        ],
        'reemplazar' => [
            '**`if (elemento)` descarta falsy.** `0`, `\'\'`, `false` son falsy en',
            'ambos lenguajes. Este bug existe en los DOS espejos, en los mismos',
            'métodos de `Iterador`:',
            '',
            '- PHP: `Iterador::crear_interno`, `Iterador::cargar_interno` e',
            '  `Iterador::iterador_interno` usan `if ($elemento)`.',
            '- JS: `Iterador._crear_interno`, `Iterador._cargar_interno` e',
            '  `Iterador._iterador_interno` usan `if (elemento)`.',
            '',
            'En todos los casos, si el elemento inicial es `0`, `\'\'` o `false`,',
            'el iterador se crea/carga sin posición actual. Es un bug latente',
            '(nadie inicializa iteradores con esos valores en la práctica), pero',
            'real. El fix es:',
            '',
            '- PHP: `if ($elemento !== null)`',
            '- JS: `if (elemento !== null && elemento !== undefined)`',
            '',
            'Pendiente en ambos espejos.',
            '',
            '### 12.4 API del Controlador JS',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Historial framework: agregar 1.5i.7e',
        'buscar' => [
            '- **1.5i.7d**: `PerdurarSuperestructuraStringSQL::crear_chunks_insertar_adyacentes`',
            '  usa `adyacentes()` en lugar de `por_cada_adyacente_ejecutar`, para',
            '  no emitir una alerta por cada nodo sin adyacentes (alineado con el',
            '  espejo JS). Reescritura de `Pruebas/prueba_deposito.php` con un',
            '  test bien diseñado: el anterior daba un falso positivo porque',
            '  intentaba crear el mismo ID especial después de `cargar` (que',
            '  reinserta el ID al recrear el nodo). Ahora verifica directamente',
            '  `vaciar_superestructura`.',
        ],
        'reemplazar' => [
            '- **1.5i.7d**: `PerdurarSuperestructuraStringSQL::crear_chunks_insertar_adyacentes`',
            '  usa `adyacentes()` en lugar de `por_cada_adyacente_ejecutar`, para',
            '  no emitir una alerta por cada nodo sin adyacentes (alineado con el',
            '  espejo JS). Reescritura de `Pruebas/prueba_deposito.php` con un',
            '  test bien diseñado: el anterior daba un falso positivo porque',
            '  intentaba crear el mismo ID especial después de `cargar` (que',
            '  reinserta el ID al recrear el nodo). Ahora verifica directamente',
            '  `vaciar_superestructura`.',
            '- **1.5i.7e**: sin cambios funcionales al framework. Documentación:',
            '  en la sección 12 del prompt se aclaran los cambios del PHP que no',
            '  tienen análogo en JS (libxml, `glob()`, `real_escape_string`), y',
            '  se documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos (PHP y JS).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto historial: agregar v73q',
        'buscar' => [
            '- **v73p**: reescritura del test del depósito de IDs. El anterior',
            '  daba falso positivo porque intentaba recrear el ID después de',
            '  `cargar` (que reinserta el ID al recrear el nodo). El nuevo test',
            '  verifica directamente `vaciar_superestructura`. Se alineó',
            '  `crear_chunks_insertar_adyacentes` con el espejo JS para no',
            '  emitir alertas por cada nodo sin adyacentes.',
        ],
        'reemplazar' => [
            '- **v73p**: reescritura del test del depósito de IDs. El anterior',
            '  daba falso positivo porque intentaba recrear el ID después de',
            '  `cargar` (que reinserta el ID al recrear el nodo). El nuevo test',
            '  verifica directamente `vaciar_superestructura`. Se alineó',
            '  `crear_chunks_insertar_adyacentes` con el espejo JS para no',
            '  emitir alertas por cada nodo sin adyacentes.',
            '- **v73q**: tanda de documentación. Se aclaran en el prompt del',
            '  framework los cambios del PHP que no tienen análogo en JS, y se',
            '  documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Discusion actual: bump a v73q',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73p (reescritura',
            'del test del depósito de IDs; alineación de las alertas de guardado',
            'SQL con el espejo JS).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73q (tanda de',
            'documentación: cambios sin análogo en JS; bug latente',
            '`if ($elemento)` en ambos espejos de `Iterador`).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Estado de la conversacion: agregar v73q',
        'buscar' => [
            '- Cerramos en v73p la reescritura del test (el anterior daba falso',
            '  positivo) y la alineación de `crear_chunks_insertar_adyacentes`',
            '  con el espejo JS.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73p la reescritura del test (el anterior daba falso',
            '  positivo) y la alineación de `crear_chunks_insertar_adyacentes`',
            '  con el espejo JS.',
            '- Cerramos en v73q la documentación del espejo JS y del bug',
            '  latente `if ($elemento)` de `Iterador`.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto estado al cierre: bump a v73q',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73p (framework 1.5i.7d).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73q (framework 1.5i.7e).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
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