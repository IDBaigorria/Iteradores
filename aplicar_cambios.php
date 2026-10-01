<?php
/**
 * Aplicador de cambios automáticos — AdministradorDeViajes.
 *
 * Tanda V1.5piloto.73n / Framework 1.5i.7b:
 * robustez del método de persistencia XML y fix de listar() en JSON.
 *
 * - PerdurarSuperestructuraStringXML.php: escritura atómica, validación
 *   de <nodos>, libxml_clear_errors, sin doble vaciado en cargar,
 *   listar() chequea glob(). Version 1.0.1.
 * - PerdurarSuperestructuraStringJSON.php: listar() chequea glob().
 *   Version 1.0.4.
 * - prompts/prompt_framework_iteradores.md: sección 6.6 nueva (XML),
 *   historial 1.5i.7b.
 * - prompts/prompt_piloto.md: historial v73n, Discusión actual.
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
    // Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php',
        'descripcion' => 'Bump de version a 1.0.1',
        'buscar' => [
            ' * @version 1.0.0 (Última revisión: 01/09/2025)',
        ],
        'reemplazar' => [
            ' * @version 1.0.1 (Última revisión: 30/09/2026)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php',
        'descripcion' => 'guardar: escritura atomica',
        'buscar' => [
            '        $ruta_archivo = self::obtener_ruta_archivo($nombre);',
            '        $xml = self::construir_estructura_xml();',
            '',
            '        if (file_put_contents($ruta_archivo, $xml) === false) {',
            '            self::_error("No se pudo guardar el archivo XML: " . $ruta_archivo);',
            '            return false;',
            '        }',
            '',
            '        return true;',
            '    }',
        ],
        'reemplazar' => [
            '        $ruta_archivo = self::obtener_ruta_archivo($nombre);',
            '        $xml = self::construir_estructura_xml();',
            '',
            '        // Escritura atomica: escribir a .tmp y renombrar.',
            '        $ruta_temporal = $ruta_archivo . \'.tmp\';',
            '        if (file_put_contents($ruta_temporal, $xml) === false) {',
            '            self::_error("No se pudo guardar el archivo XML temporal: " . $ruta_temporal);',
            '            return false;',
            '        }',
            '        if (!rename($ruta_temporal, $ruta_archivo)) {',
            '            self::_error("No se pudo renombrar el archivo XML temporal: " . $ruta_temporal);',
            '            @unlink($ruta_temporal);',
            '            return false;',
            '        }',
            '',
            '        return true;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php',
        'descripcion' => 'cargar: validacion, clear_errors, sin doble vaciado',
        'buscar' => [
            '        // Parsear XML',
            '        libxml_use_internal_errors(true);',
            '        $xml = simplexml_load_string($contenido);',
            '        if ($xml === false) {',
            '            $errors = libxml_get_errors();',
            '            $error_messages = [];',
            '            foreach ($errors as $error) {',
            '                $error_messages[] = $error->message;',
            '            }',
            '            self::_error("Error al decodificar el archivo XML: " . implode(\'; \', $error_messages));',
            '            return null;',
            '        }',
            '',
            '        // Limpiar la superestructura actual antes de cargar',
            '        Nodo::vaciar_superestructura(static::$token);',
            '',
            '        $equivalencias = [];',
        ],
        'reemplazar' => [
            '        // Parsear XML',
            '        libxml_use_internal_errors(true);',
            '        $xml = simplexml_load_string($contenido);',
            '        if ($xml === false) {',
            '            $errors = libxml_get_errors();',
            '            $error_messages = [];',
            '            foreach ($errors as $error) {',
            '                $error_messages[] = $error->message;',
            '            }',
            '            libxml_clear_errors();',
            '            self::_error("Error al decodificar el archivo XML: " . implode(\'; \', $error_messages));',
            '            return null;',
            '        }',
            '        libxml_clear_errors();',
            '',
            '        // Validar que el XML tenga la estructura esperada.',
            '        if (!isset($xml->nodos)) {',
            '            self::_error("El XML no tiene el nodo <nodos> esperado: " . $ruta_archivo);',
            '            return null;',
            '        }',
            '',
            '        // La superestructura ya fue vaciada por Controlador::cargar.',
            '        // No vaciar de nuevo: si algo fallara entre las dos limpiezas,',
            '        // quedaria una superestructura vacia sin que nadie lo note.',
            '',
            '        $equivalencias = [];',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php',
        'descripcion' => 'cargar_desde_xml: validacion y clear_errors',
        'buscar' => [
            '        // Parsear XML',
            '        libxml_use_internal_errors(true);',
            '        $xml = simplexml_load_string($xml_string);',
            '        if ($xml === false) {',
            '            $errors = libxml_get_errors();',
            '            $error_messages = [];',
            '            foreach ($errors as $error) {',
            '                $error_messages[] = $error->message;',
            '            }',
            '            self::_error("Error al decodificar el XML: " . implode(\'; \', $error_messages));',
            '            return false;',
            '        }',
            '',
            '        // Limpiar la superestructura actual antes de cargar',
            '        Nodo::vaciar_superestructura(static::$token);',
        ],
        'reemplazar' => [
            '        // Parsear XML',
            '        libxml_use_internal_errors(true);',
            '        $xml = simplexml_load_string($xml_string);',
            '        if ($xml === false) {',
            '            $errors = libxml_get_errors();',
            '            $error_messages = [];',
            '            foreach ($errors as $error) {',
            '                $error_messages[] = $error->message;',
            '            }',
            '            libxml_clear_errors();',
            '            self::_error("Error al decodificar el XML: " . implode(\'; \', $error_messages));',
            '            return false;',
            '        }',
            '        libxml_clear_errors();',
            '',
            '        // Validar que el XML tenga la estructura esperada.',
            '        if (!isset($xml->nodos)) {',
            '            self::_error("El XML no tiene el nodo <nodos> esperado.");',
            '            return false;',
            '        }',
            '',
            '        // Limpiar la superestructura actual antes de cargar.',
            '        // Esta funcion se llama desde fuera del Controlador (por ejemplo,',
            '        // desde codigo de pruebas), por eso mantiene el vaciado propio.',
            '        Nodo::vaciar_superestructura(static::$token);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringXML.php',
        'descripcion' => 'listar: chequear glob',
        'buscar' => [
            '        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_XML;',
            '        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . \'*.xml\');',
            '        ',
            '        $superestructuras = [];',
            '        foreach ($archivos as $archivo) {',
            '            $nombre = pathinfo($archivo, PATHINFO_FILENAME);',
            '            $superestructuras[] = $nombre;',
            '        }',
        ],
        'reemplazar' => [
            '        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_XML;',
            '        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . \'*.xml\');',
            '        if ($archivos === false) {',
            '            self::_error("No se pudo listar los archivos XML en: " . $carpeta);',
            '            return null;',
            '        }',
            '',
            '        $superestructuras = [];',
            '        foreach ($archivos as $archivo) {',
            '            $nombre = pathinfo($archivo, PATHINFO_FILENAME);',
            '            $superestructuras[] = $nombre;',
            '        }',
        ],
    ],

    // ============================================================
    // Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php',
        'descripcion' => 'Bump de version a 1.0.4',
        'buscar' => [
            ' * @version 1.0.3 (Última revisión: 30/09/2026)',
        ],
        'reemplazar' => [
            ' * @version 1.0.4 (Última revisión: 30/09/2026)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php',
        'descripcion' => 'listar: chequear glob',
        'buscar' => [
            '        $carpeta = self::obtener_carpeta_absoluta();',
            '        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . \'*.json\');',
            '        ',
            '        $superestructuras = [];',
            '        foreach ($archivos as $archivo) {',
            '            $nombre = pathinfo($archivo, PATHINFO_FILENAME);',
            '            $superestructuras[] = $nombre;',
            '        }',
        ],
        'reemplazar' => [
            '        $carpeta = self::obtener_carpeta_absoluta();',
            '        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . \'*.json\');',
            '        if ($archivos === false) {',
            '            self::_error("No se pudo listar los archivos JSON en: " . $carpeta);',
            '            return null;',
            '        }',
            '',
            '        $superestructuras = [];',
            '        foreach ($archivos as $archivo) {',
            '            $nombre = pathinfo($archivo, PATHINFO_FILENAME);',
            '            $superestructuras[] = $nombre;',
            '        }',
        ],
    ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework 6.5: agregar listar() y nueva seccion 6.6 XML',
        'buscar' => [
            '- **Validación desde 1.5i.7a:** al cargar se chequea que exista la',
            '  clave `nodos`. Si no, se devuelve `null` y se loguea error.',
            '',
            '---',
            '',
            '## 7. PATRONES DE CÓDIGO DEL FRAMEWORK',
        ],
        'reemplazar' => [
            '- **Validación desde 1.5i.7a:** al cargar se chequea que exista la',
            '  clave `nodos`. Si no, se devuelve `null` y se loguea error.',
            '- **`listar()` desde 1.5i.7b:** chequea el resultado de `glob()`.',
            '  Si falla, devuelve `null` y registra el error.',
            '',
            '### 6.6 XML',
            '',
            '`PerdurarSuperestructuraStringXML` sigue el mismo diseño que JSON,',
            'con los mismos fixes aplicados desde 1.5i.7b:',
            '',
            '- Escritura atómica (`.tmp` + `rename`).',
            '- `cargar` valida que exista el nodo `<nodos>` antes de procesar.',
            '- `cargar` no vacía dos veces (la vacía `Controlador::cargar`).',
            '- `libxml_clear_errors()` después de parsear.',
            '- `listar()` chequea el resultado de `glob()`.',
            '- `cargar_desde_xml` (método público, no llamado por el',
            '  `Controlador`) mantiene su `vaciar_superestructura` propio.',
            '',
            '**XML no se usa en el piloto.** Los fixes están aplicados por',
            'consistencia con JSON, para que el día que se use esté listo.',
            '',
            '---',
            '',
            '## 7. PATRONES DE CÓDIGO DEL FRAMEWORK',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework historial: agregar 1.5i.7b',
        'buscar' => [
            '- **1.5i.7a**: `cargar` chequea el resultado de la query de adyacentes',
            '  (no llama `fetch_assoc()` sobre `false`). `real_escape_string` en',
            '  `cargar`, `existe` y `eliminar`. Conexiones SQL se cierran en',
            '  todos los early-returns. JSON con escritura atómica (`.tmp` +',
            '  `rename`) y validación de la clave `nodos`. `Controlador::cargar`',
            '  devuelve `bool|null` para distinguir "no existe" de "error".',
        ],
        'reemplazar' => [
            '- **1.5i.7a**: `cargar` chequea el resultado de la query de adyacentes',
            '  (no llama `fetch_assoc()` sobre `false`). `real_escape_string` en',
            '  `cargar`, `existe` y `eliminar`. Conexiones SQL se cierran en',
            '  todos los early-returns. JSON con escritura atómica (`.tmp` +',
            '  `rename`) y validación de la clave `nodos`. `Controlador::cargar`',
            '  devuelve `bool|null` para distinguir "no existe" de "error".',
            '- **1.5i.7b**: XML recibe los mismos fixes que JSON: escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`,',
            '  sin doble vaciado en `cargar`, `listar()` chequea `glob()`.',
            '  `PerdurarSuperestructuraStringJSON::listar()` también chequea',
            '  `glob()`. ESQL queda pendiente.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto historial: agregar v73n',
        'buscar' => [
            '  automático de credenciales en login exitoso: si el hash',
            '  quedó desactualizado, se regenera con la contraseña o',
            '  código recién verificado. Las tablas SQL fueron verificadas',
            '  con `CHECK`/`OPTIMIZE` en local y en producción el',
            '  30/09/2026, ambas OK.',
        ],
        'reemplazar' => [
            '  automático de credenciales en login exitoso: si el hash',
            '  quedó desactualizado, se regenera con la contraseña o',
            '  código recién verificado. Las tablas SQL fueron verificadas',
            '  con `CHECK`/`OPTIMIZE` en local y en producción el',
            '  30/09/2026, ambas OK.',
            '- **v73n**: fix del framework 1.5i.7b aplicado al método de',
            '  persistencia XML (escritura atómica, validación de `<nodos>`,',
            '  `libxml_clear_errors`, sin doble vaciado en `cargar`). De paso,',
            '  `listar()` en JSON y XML ahora chequea el resultado de `glob()`.',
            '  XML no se usa en el piloto; los fixes quedan aplicados por',
            '  consistencia. ESQL sigue pendiente.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Discusion actual: bump a v73n',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73m (framework',
            '1.5i.7a: chequeos y escape en SQL, JSON atómico, `Controlador::cargar`',
            'con `bool|null`. Rehash automático de credenciales. Tablas SQL',
            'revisadas con `CHECK`/`OPTIMIZE` en los 3 entornos).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73n (framework',
            '1.5i.7b: XML con escritura atómica, validación de `<nodos>`,',
            '`libxml_clear_errors`, sin doble vaciado. `listar()` en JSON y XML',
            'chequea `glob()`. ESQL pendiente).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Estado de la conversacion: agregar v73n',
        'buscar' => [
            '- Cerramos en v73m el fix del framework 1.5i.7a: chequeos y escape',
            '  en `cargar`/`existe`/`eliminar` de SQL, escritura atómica y',
            '  validación en JSON, `Controlador::cargar` distingue "no existe"',
            '  de "error", y rehash automático de credenciales en login exitoso.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73m el fix del framework 1.5i.7a: chequeos y escape',
            '  en `cargar`/`existe`/`eliminar` de SQL, escritura atómica y',
            '  validación en JSON, `Controlador::cargar` distingue "no existe"',
            '  de "error", y rehash automático de credenciales en login exitoso.',
            '- Cerramos en v73n el fix del framework 1.5i.7b: XML con escritura',
            '  atómica, validación de `<nodos>`, `libxml_clear_errors`, sin',
            '  doble vaciado. `listar()` en JSON y XML chequea `glob()`. ESQL',
            '  queda pendiente para cuando se aborde.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto estado al cierre: bump a v73n',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73m (framework 1.5i.7a).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73n (framework 1.5i.7b).',
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