<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74m: eliminar el respaldo JSON automático.
 *
 * El `json_encode` de todo el grafo en cada guardado se volvió el
 * cuello de botella de la app cuando el grafo creció (3 terminales,
 * varios vehículos, ventas, cupones). Cada operación tardaba segundos.
 * `guardar_ambos` ahora solo guarda SQL (fuente de verdad única). El
 * respaldo en otros formatos pasa a ser una acción manual del admin.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // FuncionesAuxiliares.php — reescribir guardar_ambos
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'FuncionesAuxiliares: guardar_ambos solo SQL',
        'buscar' => [
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
            '    // Defensa: no guardar si la superestructura esta vacia.',
            '    // Guardar vacio pisa el grafo con nada.',
            '    if (!Nodo::hay_nodos_en_superestructura()) {',
            '        Controlador::_error("guardar_ambos: superestructura vacia para \"$nombre\". Se aborta para no pisar el grafo.");',
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
            '            Controlador::_error("guardar_ambos: fallo el guardado JSON para el grafo \"$nombre\"");',
            '        }',
            '    } catch (\\Throwable $e) {',
            '        Controlador::_error("guardar_ambos: excepcion al guardar JSON para \"$nombre\": " . $e->getMessage());',
            '    } finally {',
            '        Controlador::establecer_metodo(\'SQL\');',
            '    }',
            '',
            '    return true;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Guarda una superestructura solo en SQL.',
            ' *',
            ' * Hasta v74j esta función también escribía un respaldo JSON',
            ' * automático (json_encode de todo el grafo en cada guardado).',
            ' * Cuando el grafo creció (varias terminales, vehículos, ventas,',
            ' * cupones), ese encode se volvió el cuello de botella: cada',
            ' * operación tardaba segundos y rompía los timeouts de las',
            ' * pruebas del plugin. Desde v74m se eliminó.',
            ' *',
            ' * La implementación `PerdurarSuperestructuraStringJSON` sigue',
            ' * disponible en el framework. El respaldo en formatos',
            ' * alternativos pasa a ser una acción manual del admin, a',
            ' * implementar en el rediseño del panel.',
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
            '    // Defensa: no guardar si la superestructura esta vacia.',
            '    // Guardar vacio pisa el grafo con nada.',
            '    if (!Nodo::hay_nodos_en_superestructura()) {',
            '        Controlador::_error("guardar_ambos: superestructura vacia para \"$nombre\". Se aborta para no pisar el grafo.");',
            '        return false;',
            '    }',
            '',
            '    // Guardar en SQL (única fuente de verdad).',
            '    return (bool) Controlador::guardar($nombre);',
            '}',
        ],
    ],

    // ========================================================
    // FuncionesAuxiliares.php — bump @version
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'FuncionesAuxiliares: bump @version a 1.5piloto.74m',
        'buscar' => [
            ' * @version   1.5piloto.73k',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74m',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.1 guardar_ambos
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.1 guardar_ambos ya no guarda JSON',
        'buscar' => [
            '- `guardar_ambos($nombre)`: guarda la superestructura en SQL',
            '  (fuente de verdad) y después en JSON (respaldo). Desde v73k',
            '  vive acá, no en `GuardarAmbos.php`.',
        ],
        'reemplazar' => [
            '- `guardar_ambos($nombre)`: guarda la superestructura en SQL',
            '  (única fuente de verdad). Desde v73k vive acá, no en',
            '  `GuardarAmbos.php`. Desde v74m ya NO guarda el JSON de',
            '  respaldo automático: el `json_encode` de todo el grafo',
            '  se volvió el cuello de botella del guardado cuando el',
            '  grafo creció.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.5 Persistencia SQL + JSON
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.5 persistencia solo SQL',
        'buscar' => [
            '`guardar_ambos($nombre)` vive en `FuncionesAuxiliares.php`',
            '(ver 5.1). Guarda la superestructura en SQL (fuente de verdad)',
            'y después en JSON (respaldo). Si JSON falla, `Controlador::_error()`.',
            'Devuelve true si SQL fue exitoso.',
        ],
        'reemplazar' => [
            '`guardar_ambos($nombre)` vive en `FuncionesAuxiliares.php`',
            '(ver 5.1). Guarda la superestructura en SQL (única fuente',
            'de verdad). Desde v74m ya no guarda el JSON de respaldo:',
            'el `json_encode` de todo el grafo se volvió el cuello de',
            'botella cuando el grafo creció (varias terminales,',
            'vehículos, ventas). El respaldo en otros formatos pasa a',
            'ser una acción manual del admin, a implementar en el',
            'rediseño del panel.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — historial v74m
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: agregar v74m al historial',
        'buscar' => [
            '- **v74k**: fixes de validación en el alta de micro.',
        ],
        'reemplazar' => [
            '- **v74m**: eliminado el respaldo JSON automático de',
            '  `guardar_ambos`. Motivo: el `json_encode` de todo el',
            '  grafo se volvió el cuello de botella del guardado cuando',
            '  el grafo creció (3 terminales, varios vehículos, ventas,',
            '  cupones). Cada operación tardaba segundos, y eso rompía',
            '  los timeouts de las pruebas del plugin. Ahora',
            '  `guardar_ambos` solo guarda SQL. La implementación',
            '  `PerdurarSuperestructuraStringJSON` sigue disponible en',
            '  el framework. El respaldo en formatos alternativos',
            '  (JSON, XML) pasa a ser una acción manual del admin, a',
            '  implementar en el rediseño del panel.',
            '- **v74k**: fixes de validación en el alta de micro.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 cabecera
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 cabecera a v74m',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74k (fixes',
            'de validación en el alta de micro. `agregar_micro_a_viaje`',
            'rechaza vehículos sin asientos y vehículos duplicados en el',
            'mismo viaje; nombre del micro con `max+1` para evitar',
            'colisiones. Frontend: vehículos sin asientos aparecen',
            'deshabilitados en el select).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74m',
            '(eliminado el respaldo JSON automático de `guardar_ambos`.',
            'El `json_encode` de todo el grafo se volvió el cuello de',
            'botella cuando el grafo creció con terminales, vehículos y',
            'ventas: cada operación de guardado tardaba segundos y',
            'rompía los timeouts de las pruebas del plugin. Ahora',
            '`guardar_ambos` solo guarda SQL. El respaldo en otros',
            'formatos pasa a ser una acción manual del admin, a',
            'implementar en el rediseño del panel).',
            'Antes: v1.5piloto.74k (fixes',
            'de validación en el alta de micro. `agregar_micro_a_viaje`',
            'rechaza vehículos sin asientos y vehículos duplicados en el',
            'mismo viaje; nombre del micro con `max+1` para evitar',
            'colisiones. Frontend: vehículos sin asientos aparecen',
            'deshabilitados en el select).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar v74m al estado',
        'buscar' => [
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
            '  por DNI para terminal sin viaje seleccionado, bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones',
            '  en este prompt (rehash, migraciones, botones, autocompletado,',
            '  `GuardarAmbos.php`, `migrar_pasajeros.php`).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '- Cerramos en v74m el fix de performance: `guardar_ambos` ya',
            '  no guarda el JSON de respaldo automático. El `json_encode`',
            '  de todo el grafo se volvió el cuello de botella cuando',
            '  creció (3 terminales, varios vehículos, ventas). El',
            '  respaldo JSON pasa a ser acción manual del admin (a',
            '  implementar en el rediseño del panel).',
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
            '  por DNI para terminal sin viaje seleccionado, bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones',
            '  en este prompt (rehash, migraciones, botones, autocompletado,',
            '  `GuardarAmbos.php`, `migrar_pasajeros.php`).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 decisiones de diseño
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar decision del respaldo JSON',
        'buscar' => [
            '- **SQL es siempre el método principal.** El JSON es solo respaldo.',
            '  `Conf::LOCAL` ya no decide el método de persistencia.',
        ],
        'reemplazar' => [
            '- **SQL es siempre el método principal.** El JSON es solo respaldo.',
            '  `Conf::LOCAL` ya no decide el método de persistencia.',
            '- **El respaldo JSON automático se eliminó en v74m.**',
            '  `guardar_ambos` solo guarda SQL. Motivo: el `json_encode`',
            '  de todo el grafo se volvió el cuello de botella del',
            '  guardado cuando el grafo creció (varias terminales,',
            '  vehículos, ventas). El respaldo en formatos alternativos',
            '  (JSON, XML) pasa a ser una acción manual del admin, a',
            '  implementar en el rediseño del panel admin.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §13 estado al cierre
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §13 estado a v74m',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74k (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Los fixes de v74k',
            'endurecen el alta de micro: rechaza vehículos sin asientos y',
            'duplicados en el mismo viaje, y evita colisiones de numeración',
            'al quitar un micro del medio. El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.4z) tiene 29 pruebas corriendo,',
            'incluidas las tres que verifiquen estos fixes.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74m (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro: rechaza vehículos sin asientos y',
            'duplicados en el mismo viaje, y evita colisiones de numeración',
            'al quitar un micro del medio. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático, que se había',
            'vuelto el cuello de botella del guardado cuando el grafo',
            'creció. El plugin de pruebas (`iteradoresJS/`,',
            'v1.5plugin.5c) tiene 29 pruebas corriendo.',
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