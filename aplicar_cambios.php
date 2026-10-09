<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76y (fix de auto-detección de migraciones).
 *   - Aplicacion/Migraciones/Funciones.php: detectar_compartidos_terminal
 *     y detectar_arboles_compartidos ya no devuelven true por vacío.
 *   - Aplicacion/Migraciones/Comandos.php: nuevo comando
 *     app:migracion_limpiar_marcadores. Borra el testigo de una
 *     migración (o de todas) para que la auto-detección vuelva a
 *     correr.
 *   - Enrutador: subacción grafo/migracion_limpiar.
 *   - grafo.js: botón "Re-detectar" en cada migración aplicada.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Funciones.php — fix de detectores
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Funciones.php',
        'descripcion' => 'Funciones: fix detectar_compartidos_terminal',
        'buscar' => [
            'function detectar_compartidos_terminal(string $token): bool {',
            '    $todos_ok = true;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$todos_ok) {',
            '        if (!$todos_ok) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '        $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel_nodo) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '        }',
            '        if (!$nivel_nodo || $nivel_nodo->dato() !== \'dueno\') return null;',
            '',
            '        $cont_viajes = $nodo->adyacente(\'viajes\');',
            '        if (!$cont_viajes) {',
            '            $priv = $nodo->adyacente(\'privado\');',
            '            if ($priv) $cont_viajes = $priv->adyacente(\'viajes\');',
            '        }',
            '        if (!$cont_viajes) return null;',
            '',
            '        $terminales = [];',
            '        foreach ((array)$cont_viajes->adyacentes() as $nv => $nodo_viaje) {',
            '            $tas = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '            if (!$tas) continue;',
            '            foreach ((array)$tas->adyacentes() as $nombre_t => $nodo_tv) {',
            '                $terminales[(string)$nombre_t] = true;',
            '            }',
            '        }',
            '        foreach (array_keys($terminales) as $nombre_terminal) {',
            '            if (!$nodo->adyacente(\'compartido_con_\' . $nombre_terminal)) {',
            '                $todos_ok = false;',
            '                return null;',
            '            }',
            '        }',
            '        return null;',
            '    }, null);',
            '    return $todos_ok;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * ¿Está aplicada la migración de compartidos por terminal?',
            ' *',
            ' * Fix v76y: la detección solo devuelve true si NO hay nada',
            ' * que migrar (no hay dueños con terminales autorizados) o si',
            ' * TODOS los compartidos esperados ya existen. Antes devolvía',
            ' * true por vacío, lo cual auto-marcaba la migración como',
            ' * aplicada sin estarlo.',
            ' *',
            ' * @param string $token',
            ' * @return bool',
            ' */',
            'function detectar_compartidos_terminal(string $token): bool {',
            '    $falta_alguno = false;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {',
            '        if ($falta_alguno) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '        $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel_nodo) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '        }',
            '        if (!$nivel_nodo || $nivel_nodo->dato() !== \'dueno\') return null;',
            '',
            '        $cont_viajes = $nodo->adyacente(\'viajes\');',
            '        if (!$cont_viajes) {',
            '            $priv = $nodo->adyacente(\'privado\');',
            '            if ($priv) $cont_viajes = $priv->adyacente(\'viajes\');',
            '        }',
            '        if (!$cont_viajes) return null;',
            '',
            '        $terminales = [];',
            '        foreach ((array)$cont_viajes->adyacentes() as $nv => $nodo_viaje) {',
            '            $tas = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '            if (!$tas) continue;',
            '            foreach ((array)$tas->adyacentes() as $nombre_t => $nodo_tv) {',
            '                $terminales[(string)$nombre_t] = true;',
            '            }',
            '        }',
            '        foreach (array_keys($terminales) as $nombre_terminal) {',
            '            if (!$nodo->adyacente(\'compartido_con_\' . $nombre_terminal)) {',
            '                $falta_alguno = true;',
            '                return null;',
            '            }',
            '        }',
            '        return null;',
            '    }, null);',
            '    return !$falta_alguno;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Funciones.php',
        'descripcion' => 'Funciones: fix detectar_arboles_compartidos',
        'buscar' => [
            'function detectar_arboles_compartidos(string $token): bool {',
            '    $todos_ok = true;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$todos_ok) {',
            '        if (!$todos_ok) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '        $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel_nodo) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '        }',
            '        if (!$nivel_nodo || $nivel_nodo->dato() !== \'dueno\') return null;',
            '',
            '        $ady = (array)$nodo->adyacentes();',
            '        foreach ($ady as $enlace => $hijo) {',
            '            $enlace = (string)$enlace;',
            '            if (strpos($enlace, \'compartido_con_\') !== 0) continue;',
            '            if (!$hijo->adyacente(\'_es_compartido\')) {',
            '                $todos_ok = false;',
            '                return null;',
            '            }',
            '        }',
            '        return null;',
            '    }, null);',
            '    return $todos_ok;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * ¿Está aplicada la migración de árboles paralelos?',
            ' *',
            ' * Fix v76y: devuelve true solo si NO hay compartidos o si',
            ' * TODOS los compartidos tienen el marcador `_es_compartido`.',
            ' * Antes devolvía true por vacío.',
            ' *',
            ' * @param string $token',
            ' * @return bool',
            ' */',
            'function detectar_arboles_compartidos(string $token): bool {',
            '    $falta_alguno = false;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {',
            '        if ($falta_alguno) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '        $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel_nodo) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '        }',
            '        if (!$nivel_nodo || $nivel_nodo->dato() !== \'dueno\') return null;',
            '',
            '        $ady = (array)$nodo->adyacentes();',
            '        foreach ($ady as $enlace => $hijo) {',
            '            $enlace = (string)$enlace;',
            '            if (strpos($enlace, \'compartido_con_\') !== 0) continue;',
            '            if (!$hijo->adyacente(\'_es_compartido\')) {',
            '                $falta_alguno = true;',
            '                return null;',
            '            }',
            '        }',
            '        return null;',
            '    }, null);',
            '    return !$falta_alguno;',
            '}',
        ],
    ],

    // ============================================================
    // Comandos.php — comando de limpieza de marcadores
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comandos: app:migracion_limpiar_marcadores',
        'buscar' => [
            '    // ─── app:migracion_marcar ───────────────────────────',
            '    Controlador::registrar_comando(\'app:migracion_marcar\', function(string $token, array $args) {',
        ],
        'reemplazar' => [
            '    // ─── app:migracion_limpiar_marcadores ───────────────',
            '    //',
            '    // Fix v76y: permite limpiar el testigo persistente de una',
            '    // migración para que la auto-detección vuelva a correr en',
            '    // el próximo listado. Se usa cuando el testigo quedó mal',
            '    // puesto por la auto-detección por vacío de versiones',
            '    // anteriores.',
            '    //',
            '    // Args: [\'id\' => id_de_migracion] (o vacío para todas).',
            '    Controlador::registrar_comando(\'app:migracion_limpiar_marcadores\', function(string $token, array $args) {',
            '        $id = (string)($args[0][\'id\'] ?? \'\');',
            '        $contenedor = _migraciones_contenedor();',
            '        if (!$contenedor) {',
            '            return [\'exito\' => false, \'error\' => \'No existe el contenedor.\'];',
            '        }',
            '        if ($id !== \'\') {',
            '            if ($contenedor->adyacente($id)) {',
            '                $contenedor->eliminar_adyacente($id);',
            '            }',
            '            return [\'exito\' => true, \'limpiados\' => 1];',
            '        }',
            '        // Sin id: limpiar todos los testigos que no correspondan',
            '        // a migraciones del registro actual.',
            '        $registro = migraciones_registradas();',
            '        $limpiados = 0;',
            '        $ady = (array)$contenedor->adyacentes();',
            '        foreach ($ady as $enlace => $_nodo) {',
            '            $enlace = (string)$enlace;',
            '            if ($enlace === \'_es_compartido\') continue;',
            '            if (!isset($registro[$enlace])) {',
            '                $contenedor->eliminar_adyacente($enlace);',
            '                $limpiados++;',
            '            }',
            '        }',
            '        return [\'exito\' => true, \'limpiados\' => $limpiados];',
            '    }, null, false);',
            '',
            '    // ─── app:migracion_marcar ───────────────────────────',
            '    Controlador::registrar_comando(\'app:migracion_marcar\', function(string $token, array $args) {',
        ],
    ],

    // ============================================================
    // Enrutador.php — subacción grafo/migracion_limpiar
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: subacción grafo/migracion_limpiar',
        'buscar' => [
            '                case \'migraciones_aplicar\':',
        ],
        'reemplazar' => [
            '                case \'migracion_limpiar\':',
            '                    // Limpia el testigo persistente de una migración',
            '                    // (o de todas) para que la auto-detección vuelva',
            '                    // a correr. Fix v76y.',
            '                    if (!function_exists(\'registrar_comandos_migraciones\')) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Módulo de migraciones no cargado.\']);',
            '                    }',
            '                    $id_lim = $post[\'id\'] ?? \'\';',
            '                    $res_lim = Controlador::ejecutar_comando(\'app:migracion_limpiar_marcadores\', [\'id\' => $id_lim]);',
            '                    if (!is_array($res_lim) || empty($res_lim[\'exito\'])) {',
            '                        $err = is_array($res_lim) && isset($res_lim[\'error\']) ? $res_lim[\'error\'] : \'Error al limpiar.\';',
            '                        responder_json([\'exito\' => false, \'error\' => $err]);',
            '                    }',
            '                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '                    responder_json([\'exito\' => true, \'limpiados\' => $res_lim[\'limpiados\'] ?? 0]);',
            '                    break;',
            '',
            '                case \'migraciones_aplicar\':',
        ],
    ],

    // ============================================================
    // grafo.js — botón re-detectar
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: bump @version',
        'buscar' => [
            ' * @version 1.5piloto.76n',
            ' */',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.76y',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: botón Re-detectar en migraciones',
        'buscar' => [
            '    migraciones.forEach(m => {',
            '        const badge = m.aplicada',
            '            ? \'<span style="color:#060;">Aplicada</span>\'',
            '            : \'<span style="color:#900;">Pendiente</span>\';',
            '        const boton = m.aplicada',
            '            ? \'\'',
            '            : \'<button class="btn primary" data-migrar="\' + _grafo_escape(m.id) + \'">Aplicar</button>\';',
            '        html += \'<tr>\';',
            '        html += \'<td><strong>\' + _grafo_escape(m.nombre) + \'</strong><br><span class="muted small">\' + _grafo_escape(m.descripcion) + \'</span></td>\';',
            '        html += \'<td>\' + badge + \'</td>\';',
            '        html += \'<td>\' + boton + \'</td>\';',
            '        html += \'</tr>\';',
            '    });',
        ],
        'reemplazar' => [
            '    migraciones.forEach(m => {',
            '        const badge = m.aplicada',
            '            ? \'<span style="color:#060;">Aplicada</span>\'',
            '            : \'<span style="color:#900;">Pendiente</span>\';',
            '        let boton = \'\';',
            '        if (m.aplicada) {',
            '            // Fix v76y: botón "Re-detectar" para limpiar el testigo',
            '            // y dejar que la auto-detección vuelva a correr.',
            '            boton = \'<button class="btn" data-limpiar="\' + _grafo_escape(m.id) + \'" title="Limpia el testigo. La próxima vez que se recargue, la auto-detección decide si está realmente aplicada.">Re-detectar</button>\';',
            '        } else {',
            '            boton = \'<button class="btn primary" data-migrar="\' + _grafo_escape(m.id) + \'">Aplicar</button>\';',
            '        }',
            '        html += \'<tr>\';',
            '        html += \'<td><strong>\' + _grafo_escape(m.nombre) + \'</strong><br><span class="muted small">\' + _grafo_escape(m.descripcion) + \'</span></td>\';',
            '        html += \'<td>\' + badge + \'</td>\';',
            '        html += \'<td>\' + boton + \'</td>\';',
            '        html += \'</tr>\';',
            '    });',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: listener data-limpiar',
        'buscar' => [
            '    cont.querySelectorAll(\'button[data-migrar]\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => aplicar_migracion(btn.dataset.migrar));',
            '    });',
            '}',
        ],
        'reemplazar' => [
            '    cont.querySelectorAll(\'button[data-migrar]\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => aplicar_migracion(btn.dataset.migrar));',
            '    });',
            '    cont.querySelectorAll(\'button[data-limpiar]\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => limpiar_marcador_migracion(btn.dataset.limpiar));',
            '    });',
            '}',
            '',
            'async function limpiar_marcador_migracion(id) {',
            '    if (!confirm(\'¿Limpiar el testigo de "\' + id + \'"? Después se recalcula sola.\')) return;',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/migracion_limpiar",',
            '            nombre_solicitante: usuario_actual.nombre_usuario,',
            '            id',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al limpiar", \'error\');',
            '        return;',
            '    }',
            '    mostrar_aviso("Testigo limpiado. La migración se recalcula al recargar.", \'exito\');',
            '    await cargar_grafo();',
            '}',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump grafo.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET: bump ?v= de grafo.js',
        'buscar' => [
            '<script src="Aplicacion/grafo.js?v=1.5piloto.76n"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/grafo.js?v=1.5piloto.76y"></script>',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76y',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76x',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76y',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — actualizar
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: fix en estado',
        'buscar' => [
            '**Tanda actual:** v76x (Fase B2.3.3, migración que',
            'construye los árboles paralelos en los compartidos).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v76y (fix de auto-detección de migraciones).',
            '',
            '**Bug detectado en v76x:** las funciones `detectar_*` devolvían',
            '`true` por vacío (cuando no había nada que migrar). Como el',
            'listado de migraciones auto-crea el testigo cuando la',
            'detección da true, las migraciones quedaban marcadas como',
            '"Aplicada" aunque no se hubieran corrido. Se arregla en v76y:',
            'las funciones devuelven `false` si hay algo que migrar y falta.',
            'Nuevo comando `app:migracion_limpiar_marcadores` y botón',
            '"Re-detectar" en la pestaña Grafo para limpiar testigos mal',
            'puestos. Pendiente de aplicar B2.3.3 en serio.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";