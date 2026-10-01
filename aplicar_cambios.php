<?php
/**
 * Aplicador de cambios automáticos — Framework Iteradores (PHP).
 *
 * Tanda V1.5i.7c: script de prueba del depósito de IDs, prompts
 * actualizados con el espejo JS y los aprendizajes del capítulo.
 *
 * - Pruebas/prueba_deposito.php: nuevo archivo de verificación del
 *   depósito de IDs (se ejecuta con ?probar_deposito=1).
 * - index.php: bloque temporal que carga el script de prueba.
 * - prompts/prompt_framework_iteradores.md: nueva sección 12 (espejo
 *   JS), historial 1.5i.7b actualizado, header ampliado.
 * - prompts/prompt_piloto.md: estructura con Pruebas/, historial
 *   v73o, Discusión actual al día.
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
    // Pruebas/prueba_deposito.php (nuevo)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'Pruebas/prueba_deposito.php',
        'descripcion' => 'Script de verificación del depósito de IDs',
        'contenido' => [
            '<?php',
            '/**',
            ' * Prueba del depósito de IDs del framework Iteradores (PHP).',
            ' *',
            ' * Verifica que al cargar una superestructura el depósito de IDs',
            ' * especiales se limpia correctamente, permitiendo recrear nodos con',
            ' * los mismos IDs especiales. Si no se limpiara, aparecería un error',
            ' * "Ya existe ese id" (bug que sí existió en el espejo JS hasta',
            ' * 1.5i.6).',
            ' *',
            ' * Se ejecuta como bloque temporal desde index.php:',
            ' *   http://localhost/.../index.php?probar_deposito=1',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5i.7a',
            ' */',
            '',
            'header(\'Content-Type: text/plain; charset=utf-8\');',
            '',
            'echo "=== PRUEBA DEL DEPOSITO DE IDS (PHP) ===\\n\\n";',
            '',
            '$id_prueba = \'test_especial_deposito\';',
            '$nombre_prueba = \'prueba_deposito_php\';',
            '',
            '// Limpieza por si la prueba se corrió antes.',
            'if (Controlador::existe($nombre_prueba)) {',
            '    Controlador::eliminar($nombre_prueba);',
            '}',
            '',
            '// 1. Crear un nodo especial.',
            '$n1 = Nodo::crear_con_id($id_prueba);',
            'echo "1. Crear \'{$id_prueba}\' (1ra vez): " . ($n1 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '// 2. Guardar la superestructura.',
            'guardar_ambos($nombre_prueba);',
            'echo "2. Guardar \'{$nombre_prueba}\': OK\\n";',
            '',
            '// 3. Cargar (esto debe vaciar y limpiar el depósito).',
            '$cargado = Controlador::cargar($nombre_prueba);',
            'echo "3. Cargar \'{$nombre_prueba}\': " . ($cargado ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '// 4. Intentar crear el mismo id especial otra vez.',
            '$n2 = Nodo::crear_con_id($id_prueba);',
            'echo "4. Crear \'{$id_prueba}\' (2da vez tras cargar): " . ($n2 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '// 5. Limpieza.',
            'Controlador::eliminar($nombre_prueba);',
            'echo "5. Eliminar \'{$nombre_prueba}\': OK\\n";',
            '',
            'echo "\\n=== RESULTADO ===\\n";',
            'if ($n2) {',
            '    echo "SIN BUG: el depósito de IDs se limpió correctamente.\\n";',
            '} else {',
            '    echo "BUG PRESENTE: el depósito NO se limpió.\\n";',
            '    echo "El id \'{$id_prueba}\' sigue registrado en Objeto::\\$deposito_de_ids.\\n";',
            '    echo "\\nErrores:\\n";',
            '    echo Objeto::json_errores() . "\\n";',
            '    echo "\\nAlertas:\\n";',
            '    echo Objeto::json_alertas() . "\\n";',
            '}',
            'echo "\\n=== FIN DE LA PRUEBA ===\\n";',
        ],
    ],

    // ============================================================
    // index.php: bloque ?probar_deposito=1
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Bloque temporal para prueba del depósito de IDs',
        'buscar' => [
            '// ==== Bloque temporal para pruebas de árbol ====',
            'if (isset($_GET[\'probar_arbol\'])) {',
        ],
        'reemplazar' => [
            '// ==== Bloque temporal para prueba del depósito de IDs (v1.5i.7a) ====',
            'if (isset($_GET[\'probar_deposito\'])) {',
            '    require_once __DIR__ . \'/Pruebas/prueba_deposito.php\';',
            '    exit;',
            '}',
            '',
            '// ==== Bloque temporal para pruebas de árbol ====',
            'if (isset($_GET[\'probar_arbol\'])) {',
        ],
    ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Header: mencionar el espejo JS',
        'buscar' => [
            'Se actualiza cuando cambia el framework. No incluye nada específico del',
            'piloto: eso vive en `prompts/prompt_piloto.md`.',
        ],
        'reemplazar' => [
            'Se actualiza cuando cambia el framework. No incluye nada específico del',
            'piloto: eso vive en `prompts/prompt_piloto.md`.',
            '',
            'El framework tiene un **espejo en JavaScript** para navegador (ver',
            'sección 12). Comparten la API conceptual, pero difieren en persistencia',
            '(SQL/JSON/XML en PHP, IndexedDB/JSON/XML en JS) y en detalles propios',
            'de cada lenguaje.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Historial: mencionar versiones del espejo JS',
        'buscar' => [
            '- **1.5i.7b**: XML recibe los mismos fixes que JSON: escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`,',
            '  sin doble vaciado en `cargar`, `listar()` chequea `glob()`.',
            '  `PerdurarSuperestructuraStringJSON::listar()` también chequea',
            '  `glob()`. ESQL queda pendiente.',
            '',
            'El framework en sí no cambia mucho. La mayoría de los cambios son en el',
            'piloto.',
        ],
        'reemplazar' => [
            '- **1.5i.7b**: XML recibe los mismos fixes que JSON: escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`,',
            '  sin doble vaciado en `cargar`, `listar()` chequea `glob()`.',
            '  `PerdurarSuperestructuraStringJSON::listar()` también chequea',
            '  `glob()`. ESQL queda pendiente.',
            '- **1.5i.7c**: sin cambios funcionales al framework PHP. Se agrega',
            '  `Pruebas/prueba_deposito.php` para verificar que el depósito de',
            '  IDs se limpia correctamente al vaciar la superestructura. Se',
            '  documenta el espejo JS en la sección 12 de este prompt.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
            'Su historial es: 1.5i.4 → 1.5i.5 (robustez de persistencia)',
            '→ 1.5i.6 (fix del depósito de IDs) → 1.5i.7 (alineación con PHP).',
            '',
            'El framework en sí no cambia mucho. La mayoría de los cambios son en el',
            'piloto.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Nueva seccion 12 (espejo JS) y renumerar cierre',
        'buscar' => [
            '## 11. CIERRE',
        ],
        'reemplazar' => [
            '## 12. ESPEJO EN JAVASCRIPT',
            '',
            'El framework Iteradores tiene un espejo en JavaScript para navegador.',
            'Vive en un proyecto separado (por ejemplo, `iteradoresJS/`) con la misma',
            'estructura de carpetas y las mismas clases, pero adaptado al entorno',
            'navegador.',
            '',
            '### 12.1 Qué cambia respecto al PHP',
            '',
            '- **Persistencia principal:** IndexedDB (`PerdurarSuperestructuraStringIndexedDB`).',
            '  No hay SQL. JSON y XML descargan/cargan archivos vía interacción del',
            '  usuario.',
            '- **Sin acceso al filesystem:** no se puede escribir atómicamente con',
            '  `.tmp` + `rename` como en PHP; el navegador genera el Blob completo o',
            '  no lo genera.',
            '- **Campos privados:** JS tiene `#privados` reales, más estrictos que los',
            '  `private` de PHP. Un `#privado` de una clase base NO es accesible desde',
            '  una subclase.',
            '- **Métodos async:** IndexedDB es asíncrono. `Controlador.delegar`,',
            '  `Controlador.guardar`, `Controlador.cargar`, `Controlador.existe`,',
            '  `Controlador.eliminar` y `Controlador.ejecutar_prueba` son `async`.',
            '',
            '### 12.2 Persistencia en IndexedDB',
            '',
            'IndexedDB tiene dos object stores: `nodos` y `adyacentes`. Cada uno',
            'indexado por `idsuperestructura`.',
            '',
            '**Guardar** se hace en **una sola transacción atómica**:',
            '1. `db.transaction([nodos, adyacentes], \'readwrite\')`',
            '2. Recorrer los cursores del índice `idsuperestructura` y borrar los',
            '   registros con ese nombre.',
            '3. Cuando ambos cursores terminan, encolar los INSERT (`add`) en la',
            '   misma transacción.',
            '4. `tx.oncomplete` → commit. `tx.onerror`/`tx.onabort` → rollback.',
            '',
            'Si algo falla, la transacción se aborta y los datos previos quedan',
            'intactos. Es el equivalente JS de `begin_transaction/commit/rollback`',
            'en SQL.',
            '',
            '**Error que esto evita:** antes el DELETE y los INSERT iban en',
            'transacciones separadas. Un fallo a mitad dejaba el grafo a medio',
            'pisar. Igual que el bug de SQL pre-1.5i.7.',
            '',
            '**Cierre de conexión:** `db.close()` en `finally` en `guardar`,',
            '`cargar`, `existe` y `eliminar`.',
            '',
            '**Datos como strings:** los IDs y datos se guardan como strings, igual',
            'que en PHP. `String(id)`, `String(dato)` (o `\'\'` si es null/undefined).',
            '',
            '### 12.3 Trampas PHP ↔ JS',
            '',
            '**Campos privados en la clase base.** En PHP, `private static',
            '$deposito_de_ids` en `Objeto` es accesible desde la propia clase (por',
            'ejemplo, desde un método `limpiar_ids_especiales()`). En JS,',
            '`#deposito_de_ids` es accesible solo desde la clase `Objeto`.',
            '',
            '**Regla:** si un campo privado tiene que ser limpiado desde una',
            'subclase o desde otra clase, **la clase dueña del campo debe exponer',
            'un método público**. En PHP:',
            '`Objeto::limpiar_ids_especiales()` (limpia solo los especiales). En JS:',
            '`Objeto.limpiar_deposito_ids()` (limpia solo los especiales, alineado',
            'con PHP desde V1.5i.7).',
            '',
            '**Nunca acceder a un `#privado` desde otra clase.** Aunque el',
            'traductor de PHP a JS lo haga "por analogía", no funciona. Si en el',
            'código original PHP hay `typeof $this->campo !== \'undefined\'` para',
            'verificar un campo privado de otra clase, en JS ese chequeo siempre',
            'es `false`.',
            '',
            '**`if (elemento)` descarta falsy.** `0`, `\'\'`, `false` son falsy en',
            'ambos lenguajes, pero en JS es más fácil olvidarlo porque el tipo',
            'original puede cambiar entre llamadas. Pendiente en `Iterador.js`',
            '(bug latente): `if (elemento)` debería ser',
            '`if (elemento !== null && elemento !== undefined)`.',
            '',
            '### 12.4 API del Controlador JS',
            '',
            '`Controlador.cargar(nombre)` devuelve una promesa que resuelve a:',
            '- `true`: cargó.',
            '- `false`: no existe.',
            '- `null`: error (conexión, query, transacción abortada).',
            '',
            '`Controlador.guardar(nombre)`, `Controlador.existe(nombre)` y',
            '`Controlador.eliminar(nombre)` también son `async`.',
            '',
            '`Controlador.ejecutar_prueba(callback)` es `async` y espera al',
            'callback. Si el callback es `async`, se resuelve cuando el callback',
            'termina. Antes no esperaba, y los tests imprimían "finalizado" antes',
            'de que terminara el trabajo.',
            '',
            '### 12.5 Estado del espejo JS',
            '',
            'Versiones recientes del espejo JS:',
            '- **1.5i.4**: base.',
            '- **1.5i.5**: robustez de persistencia (transacción atómica en',
            '  IndexedDB, casteo a string, `db.close()` en `finally`, `delegar`',
            '  async con validación, `existe` y `eliminar` async).',
            '- **1.5i.6**: fix del depósito de IDs (`Objeto.limpiar_deposito_ids`).',
            '- **1.5i.7**: alineación con PHP (`limpiar_deposito_ids` borra solo',
            '  especiales), test con comparación string/number, silencio de',
            '  alertas en `#crear_datos_insertar_adyacentes`.',
            '',
            'Cualquier cambio al framework PHP que toque la API compartida debe',
            'reflejarse también en el espejo JS.',
            '',
            '---',
            '',
            '## 13. CIERRE',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estructura: agregar Pruebas/',
        'buscar' => [
            '**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`, y',
            'scripts de migración (`migrar_*.php`).',
            '',
            '**`uploads/`**: `vehiculos/`, `declaraciones_juradas/{dueno}/`.',
        ],
        'reemplazar' => [
            '**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`, y',
            'scripts de migración (`migrar_*.php`).',
            '',
            '**`Pruebas/`**: `prueba_deposito.php`, script de verificación del',
            'depósito de IDs (se ejecuta con `?probar_deposito=1` desde',
            '`index.php`).',
            '',
            '**`uploads/`**: `vehiculos/`, `declaraciones_juradas/{dueno}/`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v73o',
        'buscar' => [
            '- **v73n**: fix del framework 1.5i.7b aplicado al método de',
            '  persistencia XML (escritura atómica, validación de `<nodos>`,',
            '  `libxml_clear_errors`, sin doble vaciado en `cargar`). De paso,',
            '  `listar()` en JSON y XML ahora chequea el resultado de `glob()`.',
            '  XML no se usa en el piloto; los fixes quedan aplicados por',
            '  consistencia. ESQL sigue pendiente.',
        ],
        'reemplazar' => [
            '- **v73n**: fix del framework 1.5i.7b aplicado al método de',
            '  persistencia XML (escritura atómica, validación de `<nodos>`,',
            '  `libxml_clear_errors`, sin doble vaciado en `cargar`). De paso,',
            '  `listar()` en JSON y XML ahora chequea el resultado de `glob()`.',
            '  XML no se usa en el piloto; los fixes quedan aplicados por',
            '  consistencia. ESQL sigue pendiente.',
            '- **v73o**: script de prueba del depósito de IDs en',
            '  `Pruebas/prueba_deposito.php`, ejecutable con `?probar_deposito=1`',
            '  desde `index.php`. Confirma que el framework PHP NO tiene el bug',
            '  de limpieza de IDs que sí existió en el espejo JS hasta 1.5i.6.',
            '  De paso, se documenta el espejo JS en el prompt del framework',
            '  (sección 12).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Discusion actual: bump a v73o',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73n (framework',
            '1.5i.7b: XML con escritura atómica, validación de `<nodos>`,',
            '`libxml_clear_errors`, sin doble vaciado. `listar()` en JSON y XML',
            'chequea `glob()`. ESQL pendiente).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73o (script de',
            'prueba del depósito de IDs en `Pruebas/prueba_deposito.php`;',
            'prompts actualizados para reflejar el espejo JS del framework).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado de la conversacion: agregar v73o',
        'buscar' => [
            '- Cerramos en v73n el fix del framework 1.5i.7b: XML con escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`, sin',
            '  doble vaciado. `listar()` en JSON y XML chequea `glob()`. ESQL',
            '  queda pendiente para cuando se aborde.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73n el fix del framework 1.5i.7b: XML con escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`, sin',
            '  doble vaciado. `listar()` en JSON y XML chequea `glob()`. ESQL',
            '  queda pendiente para cuando se aborde.',
            '- Cerramos en v73o el script de prueba del depósito de IDs en',
            '  `Pruebas/` y la documentación del espejo JS en el prompt del',
            '  framework.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73n (framework 1.5i.7b).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73o (framework 1.5i.7c).',
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