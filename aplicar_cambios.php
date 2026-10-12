<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP.
 *
 * Reparación topológica (v78n, v2 — anclas corregidas).
 *
 * Uso:
 *   php aplicar_cambios.php
 * Luego: index.php?reparar_topologia=1
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

// ------------------------------------------------------------
// migrar_topologia.php
// ------------------------------------------------------------

[
    'tipo' => 'reemplazar',
    'archivo' => 'miscelaneas/migrar_topologia.php',
    'descripcion' => 'migrar_topologia: agregar @version',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @since 1.5piloto.78f',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @since 1.5piloto.78f',
        ' * @version 1.5piloto.78n',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'miscelaneas/migrar_topologia.php',
    'descripcion' => 'migrar_topologia: agregar reparadores al final',
    'buscar' => [
        '            $nodo_dueno->eliminar_adyacente(\'soporte\');',
        '            $nodo_dueno->_adyacente_en($publico, \'soporte\');',
        '            $res[\'repuntados\']++;',
        '        }',
        '    }',
        '    return $res;',
        '}',
        '',
        '?>',
    ],
    'reemplazar' => [
        '            $nodo_dueno->eliminar_adyacente(\'soporte\');',
        '            $nodo_dueno->_adyacente_en($publico, \'soporte\');',
        '            $res[\'repuntados\']++;',
        '        }',
        '    }',
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Repara enlaces `dueño → compartido_con_<terminal>` faltantes.',
        ' *',
        ' * Recorre los terminales del dueño, sigue el enlace `dueno` de',
        ' * cada uno, y si el destino tiene `_es_compartido` verifica que',
        ' * el dueño tenga un enlace `compartido_con_<nombre_terminal>`',
        ' * apuntando a ese nodo. Si falta, lo agrega. Idempotente.',
        ' *',
        ' * Contexto: por algún flujo previo, el enlace del dueño al',
        ' * compartido puede haberse perdido. El compartido sigue vivo',
        ' * y el terminal lo sigue alcanzando (`terminal → dueno`), pero',
        ' * el dueño no lo tiene en su lista. Eso hace que el dueño no',
        ' * vea los recursos que viven en el compartido.',
        ' *',
        ' * @return array{duenos: int, reparados: int, detalles: array}',
        ' */',
        'function reparar_compartidos_dueno(): array {',
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
        '        $cont_terminales = $nodo_dueno->adyacente(\'terminales\');',
        '        if (!$cont_terminales) continue;',
        '',
        '        $arreglados = [];',
        '        foreach ((array)$cont_terminales->adyacentes() as $nombre_term => $nodo_term) {',
        '            $nombre_term = (string)$nombre_term;',
        '            $compartido = $nodo_term->adyacente(\'dueno\');',
        '            if (!$compartido) continue;',
        '            if (!$compartido->adyacente(\'_es_compartido\')) continue;',
        '            $enlace = \'compartido_con_\' . $nombre_term;',
        '            if ($nodo_dueno->adyacente($enlace)) continue;',
        '            $nodo_dueno->_adyacente_en($compartido, $enlace);',
        '            $arreglados[] = $enlace;',
        '        }',
        '',
        '        if (!empty($arreglados)) {',
        '            $res[\'reparados\'] += count($arreglados);',
        '            $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno, \'enlaces\' => $arreglados];',
        '        }',
        '    }',
        '    return $res;',
        '}',
        '',
        '/**',
        ' * Repara viajes que están en algún compartido del dueño pero',
        ' * no en `us_dueno/viajes`. Recolecta los compartidos por dos',
        ' * vías: el enlace directo `compartido_con_*` del dueño y el',
        ' * enlace `dueno` de cada terminal. Idempotente.',
        ' *',
        ' * @return array{duenos: int, reparados: int, detalles: array}',
        ' */',
        'function reparar_viajes_dueno(): array {',
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
        '        $cont_dueno = $nodo_dueno->adyacente(\'viajes\');',
        '        if (!$cont_dueno) {',
        '            $cont_dueno = Nodo::crear_con_dato(\'\');',
        '            $nodo_dueno->_adyacente_en($cont_dueno, \'viajes\');',
        '        }',
        '',
        '        $ya_presentes = [];',
        '        foreach ((array)$cont_dueno->adyacentes() as $n => $_) {',
        '            $ya_presentes[(string)$n] = true;',
        '        }',
        '',
        '        // Recolectar compartidos por dos vías.',
        '        $compartidos = [];',
        '        foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {',
        '            $enlace = (string)$enlace;',
        '            if (strpos($enlace, \'compartido_con_\') === 0) {',
        '                $compartidos[$hijo->id()] = $hijo;',
        '            }',
        '        }',
        '        $cont_term = $nodo_dueno->adyacente(\'terminales\');',
        '        if ($cont_term) {',
        '            foreach ((array)$cont_term->adyacentes() as $nodo_term) {',
        '                $comp = $nodo_term->adyacente(\'dueno\');',
        '                if ($comp && $comp->adyacente(\'_es_compartido\')) {',
        '                    $compartidos[$comp->id()] = $comp;',
        '                }',
        '            }',
        '        }',
        '',
        '        // Recolectar viajes de cada compartido.',
        '        $faltantes = [];',
        '        foreach ($compartidos as $comp) {',
        '            $c_viajes = $comp->adyacente(\'viajes\');',
        '            if (!$c_viajes) continue;',
        '            foreach ((array)$c_viajes->adyacentes() as $nombre_v => $nodo_v) {',
        '                $nombre_v = (string)$nombre_v;',
        '                if (isset($ya_presentes[$nombre_v])) continue;',
        '                $faltantes[$nombre_v] = $nodo_v;',
        '            }',
        '        }',
        '',
        '        if (empty($faltantes)) continue;',
        '',
        '        $agregados = [];',
        '        foreach ($faltantes as $nombre_v => $nodo_v) {',
        '            $cont_dueno->_adyacente_en($nodo_v, $nombre_v);',
        '            $agregados[] = $nombre_v;',
        '        }',
        '        $res[\'reparados\'] += count($agregados);',
        '        $res[\'detalles\'][] = [\'dueno\' => (string)$nombre_dueno, \'viajes\' => $agregados];',
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
    'descripcion' => 'index.php: bump @version a 1.5piloto.78n',
    'buscar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
    'reemplazar' => [
        ' * @author Ignacio David Baigorria',
        ' * @package   Iteradores',
        ' * @since     1.0.0',
        ' * @version   1.5piloto.78n',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'index.php: agregar bloque ?reparar_topologia=1',
    'buscar' => [
        '// ==== Migración topológica (v78f) ====',
        '// 1. Crea el enlace us_Administrador → usuarios.',
    ],
    'reemplazar' => [
        '// ==== Reparación topológica (v78n) ====',
        '// 1. Reparar enlaces `dueño → compartido_con_<terminal>` faltantes.',
        '// 2. Reparar viajes faltantes en el contenedor `viajes` del dueño.',
        '// Idempotente. Solo agrega enlaces, nunca borra.',
        '// Uso: index.php?reparar_topologia=1',
        'if (isset($_GET[\'reparar_topologia\'])) {',
        '    header(\'Content-Type: text/plain; charset=utf-8\');',
        '    require_once __DIR__ . \'/miscelaneas/migrar_topologia.php\';',
        '',
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
        '}',
        '',
        '// ==== Migración topológica (v78f) ====',
        '// 1. Crea el enlace us_Administrador → usuarios.',
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