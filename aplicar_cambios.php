<?php
/**
 * Aplicador de cambios — v78r: fix del borrado de aristas en SQL64
 * + reparación extendida de contenedores del dueño.
 *
 * Uso:
 *   php aplicar_cambios.php
 * Luego: index.php?reparar_topologia=1
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

// ------------------------------------------------------------
// Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: bump @version a 1.5i.7q',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @version 1.5i.7o',
        ' * @since 1.5i.7k',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @version 1.5i.7q',
        ' * @since 1.5i.7k',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: regla de borrado de aristas con doble extremo',
    'buscar' => [
        '            // DELETE de adyacentes. Con filtro, borra las',
        '            // aristas cuyo origen está en el filtro. Sin',
        '            // filtro, borra todo.',
        '            if ($mf === 0) {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';";',
        '            } else {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND idnodo IN (SELECT idnodo FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND (contexto_mascara & " . $mf . ") != 0);";',
        '            }',
    ],
    'reemplazar' => [
        '            // DELETE de adyacentes. Con filtro, borra solo las',
        '            // aristas cuyos DOS extremos están en el filtro. Las',
        '            // que cruzan el borde (origen o destino fuera del',
        '            // filtro) se conservan tal cual: si se borraran sin',
        '            // reinsertarlas, se perderían enlaces del dueño hacia',
        '            // sus propios subárboles (bug v78r). Sin filtro,',
        '            // borra todo (comportamiento de guardar).',
        '            if ($mf === 0) {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\';";',
        '            } else {',
        '                $q_del_ady = "DELETE FROM adyacente_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND idnodo IN (SELECT idnodo FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND (contexto_mascara & " . $mf . ") != 0)"',
        '                           . " AND idadyacente IN (SELECT idnodo FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                           . " AND (contexto_mascara & " . $mf . ") != 0);";',
        '            }',
    ],
],

// ------------------------------------------------------------
// miscelaneas/migrar_topologia.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'miscelaneas/migrar_topologia.php',
    'descripcion' => 'migrar_topologia: bump @version a 1.5piloto.78r',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @since 1.5piloto.78f',
        ' * @version 1.5piloto.78n',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @since 1.5piloto.78f',
        ' * @version 1.5piloto.78r',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'miscelaneas/migrar_topologia.php',
    'descripcion' => 'migrar_topologia: agregar reparadores de empresas y pasajeros',
    'buscar' => [
        '        $res[\'reparados\'] += count($agregados);',
        '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno, \'viajes\' => $agregados];',
        '    }',
        '    return $res;',
        '}',
        '',
        '?>',
    ],
    'reemplazar' => [
        '        $res[\'reparados\'] += count($agregados);',
        '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno, \'viajes\' => $agregados];',
        '    }',
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Repara el contenedor `empresas` del dueño si falta.',
        ' *',
        ' * Reconstruye el contenedor recorriendo los micros de cada',
        ' * viaje del dueño: cada micro tiene un enlace `empresa` al',
        ' * nodo empresa. Se agrupan los nodos únicos y se enlazan al',
        ' * contenedor `empresas` del dueño con el nombre de empresa',
        ' * como clave. Idempotente.',
        ' *',
        ' * @return array{duenos: int, reparados: int, detalles: array}',
        ' */',
        'function reparar_empresas_dueno(): array {',
        '    $res = [\'duenos\' => 0, \'reparados\' => 0, \'detalles\' => []];',
        '',
        '    $nodo_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$nodo_usuarios) return $res;',
        '',
        '    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
        '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
        '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
        '        $res[\'duenos\']++;',
        '',
        '        if ($nodo_dueno->adyacente(\'empresas\')) continue;',
        '',
        '        $cont_viajes = $nodo_dueno->adyacente(\'viajes\');',
        '        if (!$cont_viajes) continue;',
        '',
        '        $empresas = [];',
        '        foreach ((array)$cont_viajes->adyacentes() as $nodo_viaje) {',
        '            $cont_micros = $nodo_viaje->adyacente(\'micros\');',
        '            if (!$cont_micros) continue;',
        '            foreach ((array)$cont_micros->adyacentes() as $nodo_micro) {',
        '                $nodo_emp = $nodo_micro->adyacente(\'empresa\');',
        '                if (!$nodo_emp) continue;',
        '                $empresas[$nodo_emp->id()] = $nodo_emp;',
        '            }',
        '        }',
        '',
        '        if (empty($empresas)) continue;',
        '',
        '        $cont_emp = Nodo::crear_con_dato(\'\');',
        '        $nodo_dueno->_adyacente_en($cont_emp, \'empresas\');',
        '        foreach ($empresas as $nodo_emp) {',
        '            $nombre_emp = (string)$nodo_emp->dato();',
        '            if ($nombre_emp === \'\') continue;',
        '            $cont_emp->_adyacente_en($nodo_emp, $nombre_emp);',
        '        }',
        '',
        '        $res[\'reparados\']++;',
        '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno, \'empresas\' => count($empresas)];',
        '    }',
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Repara el contenedor `pasajeros` del dueño si falta.',
        ' *',
        ' * Los compartidos de cada terminal apuntan al mismo nodo',
        ' * `pasajeros` del dueño (es un alias). Si el enlace directo',
        ' * del dueño se perdió, se puede recuperar desde cualquier',
        ' * compartido del dueño. Idempotente.',
        ' *',
        ' * @return array{duenos: int, reparados: int, detalles: array}',
        ' */',
        'function reparar_pasajeros_dueno(): array {',
        '    $res = [\'duenos\' => 0, \'reparados\' => 0, \'detalles\' => []];',
        '',
        '    $nodo_usuarios = Nodo::nodo_por_id(\'usuarios\');',
        '    if (!$nodo_usuarios) return $res;',
        '',
        '    foreach ($nodo_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
        '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
        '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
        '        $res[\'duenos\']++;',
        '',
        '        if ($nodo_dueno->adyacente(\'pasajeros\')) continue;',
        '',
        '        $encontrado = null;',
        '        foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {',
        '            $enlace = (string)$enlace;',
        '            if (strpos($enlace, \'compartido_con_\') !== 0) continue;',
        '            $cont_pas = $hijo->adyacente(\'pasajeros\');',
        '            if ($cont_pas) { $encontrado = $cont_pas; break; }',
        '        }',
        '        if (!$encontrado) continue;',
        '',
        '        $nodo_dueno->_adyacente_en($encontrado, \'pasajeros\');',
        '        $res[\'reparados\']++;',
        '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno];',
        '    }',
        '    return $res;',
        '}',
        '',
        '?>',
    ],
],

// ------------------------------------------------------------
// index.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bump @version a 1.5piloto.78r',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78n',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78r',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: bloque ?reparar_topologia con empresas y pasajeros',
    'buscar' => [
        '    $r_comp = reparar_compartidos_dueno();',
        '    $r_viajes = reparar_viajes_dueno();',
        '',
        '    echo "Reparación topológica\\n";',
        '    echo "====================\\n\\n";',
        '',
        '    echo "-- Enlaces dueño → compartido_con_* --\\n";',
        '    echo "   dueños procesados: " . $r_comp[\'duenos\'] . "\\n";',
        '    echo "   enlaces agregados: " . $r_comp[\'reparados\'] . "\\n";',
        '    foreach ($r_comp[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . ": " . implode(\', \', $d[\'enlaces\']) . "\\n";',
        '    }',
        '',
        '    echo "\\n-- Viajes faltantes en el contenedor del dueño --\\n";',
        '    echo "   dueños procesados: " . $r_viajes[\'duenos\'] . "\\n";',
        '    echo "   viajes agregados:  " . $r_viajes[\'reparados\'] . "\\n";',
        '    foreach ($r_viajes[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . ": " . implode(\', \', $d[\'viajes\']) . "\\n";',
        '    }',
        '',
        '    if ($r_comp[\'reparados\'] > 0 || $r_viajes[\'reparados\'] > 0) {',
        '        guardar_ambos($nombre_app);',
        '        echo "\\nGuardado.\\n";',
        '    }',
        '    echo "\\nListo.\\n";',
        '    exit;',
    ],
    'reemplazar' => [
        '    $r_comp = reparar_compartidos_dueno();',
        '    $r_viajes = reparar_viajes_dueno();',
        '    $r_emp = reparar_empresas_dueno();',
        '    $r_pas = reparar_pasajeros_dueno();',
        '',
        '    echo "Reparación topológica\\n";',
        '    echo "====================\\n\\n";',
        '',
        '    echo "-- Enlaces dueño → compartido_con_* --\\n";',
        '    echo "   dueños procesados: " . $r_comp[\'duenos\'] . "\\n";',
        '    echo "   enlaces agregados: " . $r_comp[\'reparados\'] . "\\n";',
        '    foreach ($r_comp[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . ": " . implode(\', \', $d[\'enlaces\']) . "\\n";',
        '    }',
        '',
        '    echo "\\n-- Viajes faltantes en el contenedor del dueño --\\n";',
        '    echo "   dueños procesados: " . $r_viajes[\'duenos\'] . "\\n";',
        '    echo "   viajes agregados:  " . $r_viajes[\'reparados\'] . "\\n";',
        '    foreach ($r_viajes[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . ": " . implode(\', \', $d[\'viajes\']) . "\\n";',
        '    }',
        '',
        '    echo "\\n-- Contenedor `empresas` faltante --\\n";',
        '    echo "   dueños procesados: " . $r_emp[\'duenos\'] . "\\n";',
        '    echo "   reconstruidos:     " . $r_emp[\'reparados\'] . "\\n";',
        '    foreach ($r_emp[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . ": " . $d[\'empresas\'] . " empresas\\n";',
        '    }',
        '',
        '    echo "\\n-- Contenedor `pasajeros` faltante --\\n";',
        '    echo "   dueños procesados: " . $r_pas[\'duenos\'] . "\\n";',
        '    echo "   recuperados:       " . $r_pas[\'reparados\'] . "\\n";',
        '    foreach ($r_pas[\'detalles\'] as $d) {',
        '        echo "     " . $d[\'dueno\'] . "\\n";',
        '    }',
        '',
        '    $total_reparados = $r_comp[\'reparados\'] + $r_viajes[\'reparados\'] + $r_emp[\'reparados\'] + $r_pas[\'reparados\'];',
        '    if ($total_reparados > 0) {',
        '        guardar_ambos($nombre_app);',
        '        echo "\\nGuardado.\\n";',
        '    }',
        '    echo "\\nListo.\\n";',
        '    exit;',
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