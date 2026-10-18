<?php
/**
 * Aplicador — Tanda V: 3 funciones de rendiciones/liquidaciones + Enrutador.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) Liquidacion.php: obtener_liquidacion_por_id con $nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'obtener_liquidacion_por_id acepta $nombre_dueno',
        'buscar' => [
            '/**',
            ' * Busca una liquidación por su id en todos los dueños.',
            ' */',
            'function obtener_liquidacion_por_id(string $id_liquidacion): ?array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $contenedor = $nodo_dueno->adyacente(\'liquidaciones\');',
            '        if (!$contenedor) continue;',
            '        $actual = hmi($contenedor);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_liquidacion) {',
            '                return formatear_liquidacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Busca una liquidación por su id.',
            ' *',
            ' * Fase B3.3a: acepta $nombre_dueno. Si viene, navega',
            ' * directo desde `us_<dueño>` (con carga parcial del',
            ' * dueño/soporte, la raíz `usuarios` no está en memoria).',
            ' * Si no viene, recorre todos los dueños desde la raíz',
            ' * `usuarios` (compatibilidad con llamadores que hacen',
            ' * carga total, por ejemplo Impresion.php).',
            ' */',
            'function obtener_liquidacion_por_id(string $id_liquidacion, ?string $nombre_dueno = null): ?array {',
            '    if ($nombre_dueno !== null && $nombre_dueno !== \'\') {',
            '        $contenedor = obtener_contenedor_liquidaciones_dueno($nombre_dueno);',
            '        if ($contenedor) {',
            '            $actual = hmi($contenedor);',
            '            $seg = 0;',
            '            while ($actual && $seg < 2000) {',
            '                $seg++;',
            '                if ($actual->dato() === $id_liquidacion) {',
            '                    return formatear_liquidacion_completa($actual);',
            '                }',
            '                $actual = hd($actual);',
            '            }',
            '        }',
            '        return null;',
            '    }',
            '',
            '    // Fallback: recorrido completo desde la raíz `usuarios`.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno_actual => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $contenedor = $nodo_dueno->adyacente(\'liquidaciones\');',
            '        if (!$contenedor) continue;',
            '        $actual = hmi($contenedor);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_liquidacion) {',
            '                return formatear_liquidacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'descripcion' => 'Bump @version de Liquidacion.php',
        'buscar' => [
            ' * @version   1.5piloto.77t',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.81h',
        ],
    ],

    // ============================================================
    // B) Rendicion.php: obtener_rendicion_por_id con $nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'obtener_rendicion_por_id acepta $nombre_dueno',
        'buscar' => [
            '/**',
            ' * Busca una rendición por su id en todos los dueños.',
            ' *',
            ' * @param string $id_rendicion',
            ' * @return array|null',
            ' */',
            'function obtener_rendicion_por_id(string $id_rendicion): ?array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $contenedor = $nodo_dueno->adyacente(\'rendiciones\');',
            '        if (!$contenedor) continue;',
            '        $actual = hmi($contenedor);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_rendicion) {',
            '                return formatear_rendicion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Busca una rendición por su id.',
            ' *',
            ' * Fase B3.3a: acepta $nombre_dueno. Si viene, navega',
            ' * directo desde `us_<dueño>`. Si no, fallback a recorrer',
            ' * todos los dueños desde la raíz `usuarios`.',
            ' *',
            ' * @param string      $id_rendicion',
            ' * @param string|null $nombre_dueno',
            ' * @return array|null',
            ' */',
            'function obtener_rendicion_por_id(string $id_rendicion, ?string $nombre_dueno = null): ?array {',
            '    if ($nombre_dueno !== null && $nombre_dueno !== \'\') {',
            '        $contenedor = obtener_contenedor_rendiciones_dueno($nombre_dueno);',
            '        if ($contenedor) {',
            '            $actual = hmi($contenedor);',
            '            $seg = 0;',
            '            while ($actual && $seg < 2000) {',
            '                $seg++;',
            '                if ($actual->dato() === $id_rendicion) {',
            '                    return formatear_rendicion_completa($actual);',
            '                }',
            '                $actual = hd($actual);',
            '            }',
            '        }',
            '        return null;',
            '    }',
            '',
            '    // Fallback: recorrido completo desde la raíz `usuarios`.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno_actual => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $contenedor = $nodo_dueno->adyacente(\'rendiciones\');',
            '        if (!$contenedor) continue;',
            '        $actual = hmi($contenedor);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_rendicion) {',
            '                return formatear_rendicion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
    ],

    // ============================================================
    // C) Rendicion.php: aceptar_ajuste_rendicion con $nombre_dueno.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'aceptar_ajuste_rendicion acepta $nombre_dueno',
        'buscar' => [
            'function aceptar_ajuste_rendicion(string $id_rendicion, string $id_cancelacion): array {',
            '    if ($id_rendicion === \'\' || $id_cancelacion === \'\') {',
            '        return [\'exito\' => false, \'error\' => \'Parámetros incompletos\'];',
            '    }',
            '',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [\'exito\' => false, \'error\' => \'No hay usuarios\'];',
            '',
            '    $nodo_rendicion = null;',
            '    foreach ($raiz->adyacentes() as $nd => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = $nodo_dueno->adyacente(\'rendiciones\');',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_rendicion) { $nodo_rendicion = $actual; break 2; }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    if (!$nodo_rendicion) return [\'exito\' => false, \'error\' => \'Rendición no encontrada\'];',
        ],
        'reemplazar' => [
            'function aceptar_ajuste_rendicion(string $id_rendicion, string $id_cancelacion, ?string $nombre_dueno = null): array {',
            '    if ($id_rendicion === \'\' || $id_cancelacion === \'\') {',
            '        return [\'exito\' => false, \'error\' => \'Parámetros incompletos\'];',
            '    }',
            '',
            '    // Fase B3.3a: si viene $nombre_dueno, navegar directo',
            '    // desde us_<dueño>. Si no, fallback a iterar todos los',
            '    // dueños (carga total).',
            '    $nodo_rendicion = null;',
            '    if ($nombre_dueno !== null && $nombre_dueno !== \'\') {',
            '        $cont = obtener_contenedor_rendiciones_dueno($nombre_dueno);',
            '        if ($cont) {',
            '            $actual = hmi($cont);',
            '            $seg = 0;',
            '            while ($actual && $seg < 2000) {',
            '                $seg++;',
            '                if ($actual->dato() === $id_rendicion) { $nodo_rendicion = $actual; break; }',
            '                $actual = hd($actual);',
            '            }',
            '        }',
            '    } else {',
            '        $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '        if ($raiz) {',
            '            foreach ($raiz->adyacentes() as $nd => $nodo_dueno) {',
            '                $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '                if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '                $cont = $nodo_dueno->adyacente(\'rendiciones\');',
            '                if (!$cont) continue;',
            '                $actual = hmi($cont);',
            '                $seg = 0;',
            '                while ($actual && $seg < 2000) {',
            '                    $seg++;',
            '                    if ($actual->dato() === $id_rendicion) { $nodo_rendicion = $actual; break 2; }',
            '                    $actual = hd($actual);',
            '                }',
            '            }',
            '        }',
            '    }',
            '    if (!$nodo_rendicion) return [\'exito\' => false, \'error\' => \'Rendición no encontrada\'];',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Bump @version de Rendicion.php',
        'buscar' => [
            ' * @version   1.5piloto.77t',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.81h',
        ],
    ],

    // ============================================================
    // D) Enrutador.php: rendiciones/obtener.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'rendiciones/obtener pasa $nombre_dueno',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_rendicion = $post[\'id_rendicion\'] ?? \'\';',
            '                    if (empty($id_rendicion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de rendición no especificado\']);',
            '                    }',
            '                    $rendicion = obtener_rendicion_por_id($id_rendicion);',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_rendicion = $post[\'id_rendicion\'] ?? \'\';',
            '                    if (empty($id_rendicion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de rendición no especificado\']);',
            '                    }',
            '                    // Fase B3.3a: pasar $nombre_dueno para navegar directo.',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $rendicion = obtener_rendicion_por_id($id_rendicion, $nombre_dueno_sol);',
        ],
    ],

    // ============================================================
    // E) Enrutador.php: rendiciones/aceptar_ajuste.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'rendiciones/aceptar_ajuste pasa $nombre_dueno',
        'buscar' => [
            '                case \'aceptar_ajuste\':',
            '                    $id_rendicion = $post[\'id_rendicion\'] ?? \'\';',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    $resultado = aceptar_ajuste_rendicion($id_rendicion, $id_cancelacion);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'aceptar_ajuste\':',
            '                    $id_rendicion = $post[\'id_rendicion\'] ?? \'\';',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $resultado = aceptar_ajuste_rendicion($id_rendicion, $id_cancelacion, $nombre_dueno_sol);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // F) Enrutador.php: liquidaciones/obtener.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'liquidaciones/obtener pasa $nombre_dueno',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_liquidacion = $post[\'id_liquidacion\'] ?? \'\';',
            '                    if (empty($id_liquidacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de liquidación no especificado\']);',
            '                    }',
            '                    $liq = obtener_liquidacion_por_id($id_liquidacion);',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_liquidacion = $post[\'id_liquidacion\'] ?? \'\';',
            '                    if (empty($id_liquidacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de liquidación no especificado\']);',
            '                    }',
            '                    // Fase B3.3a: pasar $nombre_dueno para navegar directo.',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $liq = obtener_liquidacion_por_id($id_liquidacion, $nombre_dueno_sol);',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Bump @version del Enrutador',
        'buscar' => [
            ' * @version   1.5piloto.80n',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.81h',
        ],
    ],

    // ============================================================
    // G) plan_actual §6: marcar Fase 2 como completada.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §6.4: Fase 2 completada',
        'buscar' => [
            '- [ ] **Fase 2 — Los 4 módulos + Enrutador.** Una sola',
            '  tanda con bloques por módulo.',
            '  *Archivos:* `Aplicacion/Empresas/Empresa.php`,',
            '  `Aplicacion/Vehiculos/Vehiculo.php`,',
            '  `Aplicacion/Liquidaciones/Liquidacion.php`,',
            '  `Aplicacion/Rendiciones/Rendicion.php`,',
            '  `Aplicacion/Enrutador.php`.',
            '  *Bumps:* cada archivo tocado a `1.5piloto.81h`.',
        ],
        'reemplazar' => [
            '- [x] **Fase 2 — Módulos + Enrutador.** (v81h)',
            '  **Alcance corregido tras la auditoría:** Empresa.php',
            '  y Vehiculo.php ya estaban adaptados (fases v76u-v77v).',
            '  Solo quedaban 3 funciones en Liquidacion.php y',
            '  Rendicion.php con el patrón viejo + las 3 subacciones',
            '  del Enrutador que las invocan sin `$nombre_dueno`.',
            '  *Archivos tocados:* `Aplicacion/Liquidaciones/Liquidacion.php`',
            '  (`obtener_liquidacion_por_id`),',
            '  `Aplicacion/Rendiciones/Rendicion.php`',
            '  (`obtener_rendicion_por_id` y `aceptar_ajuste_rendicion`),',
            '  `Aplicacion/Enrutador.php` (3 subacciones).',
            '  *Patrón:* `?string $nombre_dueno = null`. Si viene,',
            '  navega desde `us_<dueño>`. Si no, fallback al',
            '  recorrido completo (compatibilidad con Impresion.php,',
            '  que hace carga total).',
            '  *Bumps:* Liquidacion.php, Rendicion.php y Enrutador.php',
            '  a `1.5piloto.81h`.',
        ],
    ],
];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";