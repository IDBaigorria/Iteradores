<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Tanda 1c.1a (v3): migración a SQL64 también del grafo de
 * credenciales. El bloque anterior solo migraba la app; el
 * login usa en_grafo_credenciales(), que carga el grafo de
 * credenciales, y con SQL64 activo ese grafo no existía.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Después de correrlo: visitar index.php?migrar_a_sql64=1
 * (migra los dos grafos) y probar login con un usuario no-admin.
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

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bloque ?migrar_a_sql64 migra los dos grafos',
    'buscar' => [
        '// Uso: index.php?migrar_a_sql64=1',
        'if (isset($_GET[\'migrar_a_sql64\'])) {',
        '    header(\'Content-Type: text/plain; charset=utf-8\');',
        '    $nombre_app_mig = ConfiguracionApli::NOMBRE_APP;',
        '',
        '    // 1. Cargar el grafo original desde SQL.',
        '    Controlador::establecer_metodo(\'SQL\');',
        '    if (!Controlador::existe($nombre_app_mig)) {',
        '        echo "ERROR: no existe el grafo \\"$nombre_app_mig\\" en SQL. Nada que migrar.\\n";',
        '        exit;',
        '    }',
        '    if (!Controlador::cargar($nombre_app_mig)) {',
        '        echo "ERROR: no se pudo cargar el grafo desde SQL.\\n";',
        '        echo "Ver logs de errores del Controlador.\\n";',
        '        exit;',
        '    }',
        '    if (!Nodo::hay_nodos_en_superestructura()) {',
        '        echo "ERROR: el grafo en SQL está vacío. Nada que migrar.\\n";',
        '        exit;',
        '    }',
        '',
        '    // 2. Cambiar a SQL64 y guardar.',
        '    Controlador::establecer_metodo(\'SQL64\');',
        '    if (!Controlador::guardar($nombre_app_mig)) {',
        '        echo "ERROR: no se pudo guardar el grafo en SQL64.\\n";',
        '        echo "Ver logs de errores del Controlador.\\n";',
        '        exit;',
        '    }',
        '',
        '    // 3. Reportar.',
        '    echo "Migración a SQL64\\n";',
        '    echo "=================\\n\\n";',
        '    echo "Grafo \\"$nombre_app_mig\\" migrado de SQL a SQL64.\\n\\n";',
        '    $ctx = Controlador::listar_contextos($nombre_app_mig);',
        '    if (is_array($ctx)) {',
        '        echo "Contextos registrados (" . count($ctx) . "):\\n";',
        '        foreach ($ctx as $c) echo "  - $c\\n";',
        '    } else {',
        '        echo "No se pudieron listar los contextos.\\n";',
        '    }',
        '    echo "\\nListo.\\n";',
        '    exit;',
        '}',
    ],
    'reemplazar' => [
        '// Uso: index.php?migrar_a_sql64=1',
        'if (isset($_GET[\'migrar_a_sql64\'])) {',
        '    header(\'Content-Type: text/plain; charset=utf-8\');',
        '    $grafos_migrar = [',
        '        \'app\'          => ConfiguracionApli::NOMBRE_APP,',
        '        \'credenciales\' => ConfiguracionApli::NOMBRE_APP_CREDENCIALES,',
        '    ];',
        '    echo "Migración a SQL64\\n";',
        '    echo "=================\\n\\n";',
        '    foreach ($grafos_migrar as $etiqueta => $nombre_mig) {',
        '        echo "-- $etiqueta ($nombre_mig) --\\n";',
        '',
        '        Controlador::establecer_metodo(\'SQL\');',
        '        if (!Controlador::existe($nombre_mig)) {',
        '            echo "   no existe en SQL. Se saltea.\\n";',
        '            continue;',
        '        }',
        '        if (!Controlador::cargar($nombre_mig)) {',
        '            echo "   ERROR: no se pudo cargar desde SQL. Se saltea.\\n";',
        '            continue;',
        '        }',
        '        if (!Nodo::hay_nodos_en_superestructura()) {',
        '            echo "   el grafo en SQL está vacío. Se saltea.\\n";',
        '            continue;',
        '        }',
        '',
        '        Controlador::establecer_metodo(\'SQL64\');',
        '        if (!Controlador::guardar($nombre_mig)) {',
        '            echo "   ERROR: no se pudo guardar en SQL64.\\n";',
        '            echo "   Ver logs de errores del Controlador.\\n";',
        '            exit;',
        '        }',
        '',
        '        $ctx = Controlador::listar_contextos($nombre_mig);',
        '        $n_ctx = is_array($ctx) ? count($ctx) : 0;',
        '        echo "   migrado. Contextos: $n_ctx\\n";',
        '        if (is_array($ctx) && $n_ctx > 0) {',
        '            foreach ($ctx as $c) echo "     - $c\\n";',
        '        }',
        '    }',
        '    echo "\\nListo.\\n";',
        '    exit;',
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