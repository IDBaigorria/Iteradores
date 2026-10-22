<?php
/**
 * Aplicador — v81l: rediseño `soporte` singular → `soportes`
 * contenedor plural (n soportes por dueño).
 *
 * Causa raíz del bug del problema B: en `agregar_usuario`,
 * `_asignar_duenos_a_soporte` se llamaba ANTES de
 * `_asegurar_niveles_usuario`. Como el `publico` del soporte no
 * existía todavía, el enlace `dueno → soporte` caía al fallback
 * y apuntaba al nodo real del soporte (fuga cruzada entre
 * dueños que comparten un mismo soporte).
 *
 * Rediseño aplicado: el dueño ahora tiene un contenedor
 * `soportes → publico_<soporte1>, publico_<soporte2>, ...`.
 * Permite varios soportes por dueño. El enlace `soporte`
 * singular deja de existir (se elimina si se encuentra).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81l';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // 1. Reemplazar _asignar_duenos_a_soporte completo + agregar
    //    2 helpers nuevos después.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: rediseño soportes plural + helpers',
        'buscar' => [
            '/**',
            ' * Asigna una lista de dueños a un soporte, manteniendo consistencia',
            ' * bidireccional (soporte→dueno y dueno→soporte).',
            ' *',
            ' * @param Nodo $nodo_soporte Nodo del soporte.',
            ' * @param array $nuevos_duenos Lista de nombres de dueños.',
            ' * @return void',
            ' */',
            'function _asignar_duenos_a_soporte(Nodo $nodo_soporte, array $nuevos_duenos): void {',
            '    // Fase B3.1: los dueños se resuelven por ID especial.',
            '',
            '    // Asegurar que exista el contenedor.',
            '    $nodo_duenos = $nodo_soporte->adyacente(\'duenos\');',
            '    if (!$nodo_duenos) {',
            '        $nodo_duenos = Nodo::crear_con_dato(\'\');',
            '        $nodo_soporte->_adyacente_en($nodo_duenos, \'duenos\');',
            '    }',
            '',
            '    // Normalizar la lista de nuevos (solo strings).',
            '    $lista_nuevos = [];',
            '    foreach ($nuevos_duenos as $n) {',
            '        $n = (string)$n;',
            '        if ($n !== \'\') $lista_nuevos[$n] = true;',
            '    }',
            '',
            '    // Quitar los que ya no van.',
            '    $actuales = [];',
            '    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $actuales[(string)$nombre_dueno] = $nodo_dueno;',
            '    }',
            '    foreach ($actuales as $nombre_dueno => $nodo_dueno) {',
            '        if (!isset($lista_nuevos[$nombre_dueno])) {',
            '            $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '            $nodo_duenos->eliminar_adyacente($nombre_dueno);',
            '        }',
            '    }',
            '',
            '    // Agregar los nuevos.',
            '    foreach ($lista_nuevos as $nombre_dueno => $_) {',
            '        if (isset($actuales[$nombre_dueno])) continue;',
            '        $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '        if (!$nodo_dueno) continue;',
            '        $nodo_nivel_d = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nodo_nivel_d || $nodo_nivel_d->dato() !== \'dueno\') continue;',
            '',
            '        $nodo_duenos->_adyacente_en($nodo_dueno, $nombre_dueno);',
            '        // v78f: el enlace del dueño apunta al contenedor',
            '        // `publico` del soporte, no al nodo del soporte. Con',
            '        // esto, el BFS de contextos no propaga el bit del',
            '        // dueño al resto del subgrafo del soporte (fuga',
            '        // cruzada entre dueños que comparten un mismo soporte).',
            '        // Fallback: si el soporte no tiene `publico`, se usa',
            '        // el nodo del soporte como antes.',
            '        $publico_soporte = $nodo_soporte->adyacente(\'publico\');',
            '        $destino_soporte = $publico_soporte ?: $nodo_soporte;',
            '        $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '        $nodo_dueno->_adyacente_en($destino_soporte, \'soporte\');',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Asigna una lista de dueños a un soporte, manteniendo consistencia',
            ' * bidireccional (soporte→dueno y dueno→soportes).',
            ' *',
            ' * v1.5piloto.81l (rediseño): el dueño ahora tiene un contenedor',
            ' * `soportes → publico_<soporte1>, publico_<soporte2>, ...` en',
            ' * lugar del enlace singular `soporte → publico_<soporte>`.',
            ' * Permite varios soportes por dueño y elimina el fallback al',
            ' * nodo real del soporte (causa de la fuga cruzada entre dueños).',
            ' *',
            ' * Idempotente:',
            ' *   - Si el dueño ya estaba, se asegura el enlace inverso.',
            ' *   - Si el dueño tenía el enlace singular `soporte` legacy,',
            ' *     se elimina.',
            ' *',
            ' * @param Nodo $nodo_soporte Nodo del soporte.',
            ' * @param array $nuevos_duenos Lista de nombres de dueños.',
            ' * @return void',
            ' */',
            'function _asignar_duenos_a_soporte(Nodo $nodo_soporte, array $nuevos_duenos): void {',
            '    // Asegurar el contenedor `duenos` del soporte.',
            '    $nodo_duenos = $nodo_soporte->adyacente(\'duenos\');',
            '    if (!$nodo_duenos) {',
            '        $nodo_duenos = Nodo::crear_con_dato(\'\');',
            '        $nodo_soporte->_adyacente_en($nodo_duenos, \'duenos\');',
            '    }',
            '',
            '    // Asegurar el `publico` del soporte (lo usa el dueño para',
            '    // exponer solo datos públicos). Normalmente ya existe si el',
            '    // llamador siguió el orden correcto (`_asegurar_niveles_usuario`',
            '    // antes), pero por las dudas se crea si falta.',
            '    $publico_soporte = $nodo_soporte->adyacente(\'publico\');',
            '    if (!$publico_soporte) {',
            '        $nombre_soporte = (string)$nodo_soporte->dato();',
            '        $publico_soporte = Nodo::crear_con_dato($nombre_soporte);',
            '        $nodo_soporte->_adyacente_en($publico_soporte, \'publico\');',
            '        foreach ([\'nivel\', \'nombre_real\', \'email\'] as $campo) {',
            '            $nodo_campo = $nodo_soporte->adyacente($campo);',
            '            if ($nodo_campo) $publico_soporte->_adyacente_en($nodo_campo, $campo);',
            '        }',
            '    }',
            '    $nombre_soporte = (string)$nodo_soporte->dato();',
            '',
            '    // Normalizar la lista de nuevos (solo strings).',
            '    $lista_nuevos = [];',
            '    foreach ($nuevos_duenos as $n) {',
            '        $n = (string)$n;',
            '        if ($n !== \'\') $lista_nuevos[$n] = true;',
            '    }',
            '',
            '    // Recolectar el estado actual.',
            '    $actuales = [];',
            '    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $actuales[(string)$nombre_dueno] = $nodo_dueno;',
            '    }',
            '',
            '    // Quitar los que ya no van.',
            '    foreach ($actuales as $nombre_dueno => $nodo_dueno) {',
            '        if (!isset($lista_nuevos[$nombre_dueno])) {',
            '            $nodo_duenos->eliminar_adyacente($nombre_dueno);',
            '            _quitar_soporte_del_dueno($nodo_dueno, $nombre_soporte);',
            '        }',
            '    }',
            '',
            '    // Agregar los nuevos y asegurar el enlace inverso en los',
            '    // que ya estaban (idempotente: cubre el caso de re-guardar',
            '    // sin cambios, migra el enlace `soporte` legacy al',
            '    // contenedor `soportes` si es la primera vez que corre',
            '    // después del rediseño).',
            '    foreach ($lista_nuevos as $nombre_dueno => $_) {',
            '        $nodo_dueno = isset($actuales[$nombre_dueno])',
            '            ? $actuales[$nombre_dueno]',
            '            : Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '        if (!$nodo_dueno) continue;',
            '',
            '        $nodo_nivel_d = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nodo_nivel_d || $nodo_nivel_d->dato() !== \'dueno\') continue;',
            '',
            '        // Insertar en `duenos` del soporte (si no estaba).',
            '        if (!isset($actuales[$nombre_dueno])) {',
            '            $nodo_duenos->_adyacente_en($nodo_dueno, $nombre_dueno);',
            '        }',
            '',
            '        // Asegurar el enlace inverso en el dueño.',
            '        _asegurar_soporte_en_dueno($nodo_dueno, $publico_soporte, $nombre_soporte);',
            '    }',
            '}',
            '',
            '/**',
            ' * Asegura que el dueño tenga el `publico` del soporte en su',
            ' * contenedor `soportes`. Elimina el enlace `soporte` singular',
            ' * si quedó del diseño viejo.',
            ' *',
            ' * v1.5piloto.81l.',
            ' *',
            ' * @param Nodo   $nodo_dueno',
            ' * @param Nodo   $publico_soporte',
            ' * @param string $nombre_soporte',
            ' * @return void',
            ' */',
            'function _asegurar_soporte_en_dueno(Nodo $nodo_dueno, Nodo $publico_soporte, string $nombre_soporte): void {',
            '    // Eliminar el enlace `soporte` singular si quedó del diseño viejo.',
            '    if ($nodo_dueno->adyacente(\'soporte\')) {',
            '        $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '    }',
            '',
            '    $contenedor = $nodo_dueno->adyacente(\'soportes\');',
            '    if (!$contenedor) {',
            '        $contenedor = Nodo::crear_con_dato(\'\');',
            '        $nodo_dueno->_adyacente_en($contenedor, \'soportes\');',
            '    }',
            '    if (!$contenedor->adyacente($nombre_soporte)) {',
            '        $contenedor->_adyacente_en($publico_soporte, $nombre_soporte);',
            '    }',
            '}',
            '',
            '/**',
            ' * Quita el `publico` de un soporte del contenedor `soportes`',
            ' * del dueño. Si el contenedor queda vacío, lo destruye.',
            ' * Contempla también el enlace `soporte` singular legacy.',
            ' *',
            ' * v1.5piloto.81l.',
            ' *',
            ' * @param Nodo   $nodo_dueno',
            ' * @param string $nombre_soporte',
            ' * @return void',
            ' */',
            'function _quitar_soporte_del_dueno(Nodo $nodo_dueno, string $nombre_soporte): void {',
            '    // Limpiar enlace `soporte` singular legacy si apuntaba a este soporte.',
            '    $nodo_soporte_legacy = $nodo_dueno->adyacente(\'soporte\');',
            '    if ($nodo_soporte_legacy && (string)$nodo_soporte_legacy->dato() === $nombre_soporte) {',
            '        $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '    }',
            '',
            '    // Contenedor `soportes` plural.',
            '    $contenedor = $nodo_dueno->adyacente(\'soportes\');',
            '    if (!$contenedor) return;',
            '    $contenedor->eliminar_adyacente($nombre_soporte);',
            '    if (empty($contenedor->adyacentes())) {',
            '        $nodo_dueno->eliminar_adyacente(\'soportes\');',
            '        _destruir_campos_simples($contenedor);',
            '        Nodo::eliminar($contenedor);',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // 2. agregar_usuario: reordenar (niveles antes de asignar dueños)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: agregar_usuario, reordenar niveles antes de asignar dueños',
        'buscar' => [
            '    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);',
            '',
            '    // Si es soporte, asignar dueños.',
            '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
            '',
            '    // Fase B1: asegurar los contenedores `publico` y',
            '    // `privado`. Mismo cierre de agujero que v77l hizo',
            '    // para compartidos: sin esto, un usuario creado',
            '    // después de la migración B1 queda sin contenedores,',
            '    // y la detección de la migración da `false`',
            '    // permanentemente.',
            '    _asegurar_niveles_usuario($nodo_usuario);',
        ],
        'reemplazar' => [
            '    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);',
            '',
            '    // Fase B1: asegurar los contenedores `publico` y',
            '    // `privado`. Mismo cierre de agujero que v77l hizo',
            '    // para compartidos: sin esto, un usuario creado',
            '    // después de la migración B1 queda sin contenedores,',
            '    // y la detección de la migración da `false`',
            '    // permanentemente.',
            '    //',
            '    // v1.5piloto.81l: se llama ANTES de asignar dueños a',
            '    // un soporte. `_asignar_duenos_a_soporte` necesita el',
            '    // `publico` del soporte para enlazarlo en el contenedor',
            '    // `soportes` del dueño. Antes se llamaba después y el',
            '    // enlace `dueno → soporte` caía al fallback (nodo real',
            '    // del soporte), causando la fuga cruzada entre dueños.',
            '    _asegurar_niveles_usuario($nodo_usuario);',
            '',
            '    // Si es soporte, asignar dueños.',
            '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
        ],
    ],

    // ============================================================
    // 3. actualizar_usuario: asegurar niveles antes de asignar dueños
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: actualizar_usuario, asegurar niveles antes de asignar',
        'buscar' => [
            '    // Si es soporte y se pasaron dueños asignados, actualizarlos.',
            '    if ($nivel === \'soporte\' && $duenos_asignados !== null) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
        ],
        'reemplazar' => [
            '    // v1.5piloto.81l: asegurar `publico`/`privado` (idempotente)',
            '    // antes de tocar los enlaces de soporte. `_asignar_duenos_a_soporte`',
            '    // necesita el `publico` del soporte para el contenedor `soportes`',
            '    // del dueño.',
            '    _asegurar_niveles_usuario($nodo_usuario);',
            '',
            '    // Si es soporte y se pasaron dueños asignados, actualizarlos.',
            '    if ($nivel === \'soporte\' && $duenos_asignados !== null) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
        ],
    ],

    // ============================================================
    // 4. eliminar_usuario: rama soporte (limpieza en los dueños)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: eliminar_usuario, rama soporte adaptada a soportes plural',
        'buscar' => [
            '    // Soporte: limpiar los enlaces `soporte` en sus dueños.',
            '    $nodo_nivel_actual = $nodo_usuario->adyacente(\'nivel\');',
            '    $nivel_actual_del = $nodo_nivel_actual ? $nodo_nivel_actual->dato() : \'\';',
            '    if ($nivel_actual_del === \'soporte\') {',
            '        $nodo_duenos_del = $nodo_usuario->adyacente(\'duenos\');',
            '        if ($nodo_duenos_del) {',
            '            foreach ($nodo_duenos_del->adyacentes() as $nombre_d => $nodo_d) {',
            '                $nodo_d->eliminar_adyacente(\'soporte\');',
            '            }',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    // Soporte: limpiar los enlaces `soporte` en sus dueños.',
            '    $nodo_nivel_actual = $nodo_usuario->adyacente(\'nivel\');',
            '    $nivel_actual_del = $nodo_nivel_actual ? $nodo_nivel_actual->dato() : \'\';',
            '    if ($nivel_actual_del === \'soporte\') {',
            '        $nombre_soporte_del = (string)$nodo_usuario->dato();',
            '        $nodo_duenos_del = $nodo_usuario->adyacente(\'duenos\');',
            '        if ($nodo_duenos_del) {',
            '            foreach ($nodo_duenos_del->adyacentes() as $nombre_d => $nodo_d) {',
            '                // v1.5piloto.81l: quitar el `publico` del',
            '                // soporte del contenedor `soportes` del dueño.',
            '                _quitar_soporte_del_dueno($nodo_d, $nombre_soporte_del);',
            '            }',
            '        }',
            '    }',
        ],
    ],

    // ============================================================
    // 5. eliminar_usuario: rama dueño (limpieza en sus soportes)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: eliminar_usuario, rama dueño adaptada a soportes plural',
        'buscar' => [
            '    // Dueño: limpiar la referencia en su soporte.',
            '    if ($nivel_actual_del === \'dueno\') {',
            '        $nodo_soporte_del = $nodo_usuario->adyacente(\'soporte\');',
            '        if ($nodo_soporte_del) {',
            '            $nombre_soporte_del = $nodo_soporte_del->dato();',
            '            // Fase B3.1: ID especial directo.',
            '            $nodo_soporte_real = Nodo::nodo_por_id(\'us_\' . $nombre_soporte_del);',
            '            if ($nodo_soporte_real) {',
            '                $nodo_duenos_sop = $nodo_soporte_real->adyacente(\'duenos\');',
            '                if ($nodo_duenos_sop) {',
            '                    $nodo_duenos_sop->eliminar_adyacente($nombre_usuario);',
            '                }',
            '            }',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    // Dueño: limpiar la referencia en sus soportes.',
            '    // v1.5piloto.81l: el contenedor `soportes` permite varios',
            '    // soportes por dueño. Se limpia el `duenos` de cada uno.',
            '    // También se contempla el enlace `soporte` singular legacy.',
            '    if ($nivel_actual_del === \'dueno\') {',
            '        // Enlace singular legacy.',
            '        $nodo_soporte_legacy = $nodo_usuario->adyacente(\'soporte\');',
            '        if ($nodo_soporte_legacy) {',
            '            $nombre_soporte_legacy = (string)$nodo_soporte_legacy->dato();',
            '            $nodo_soporte_real_legacy = Nodo::nodo_por_id(\'us_\' . $nombre_soporte_legacy);',
            '            if ($nodo_soporte_real_legacy) {',
            '                $nodo_duenos_sop_legacy = $nodo_soporte_real_legacy->adyacente(\'duenos\');',
            '                if ($nodo_duenos_sop_legacy) {',
            '                    $nodo_duenos_sop_legacy->eliminar_adyacente($nombre_usuario);',
            '                }',
            '            }',
            '            $nodo_usuario->eliminar_adyacente(\'soporte\');',
            '        }',
            '',
            '        // Contenedor plural.',
            '        $nodo_soportes = $nodo_usuario->adyacente(\'soportes\');',
            '        if ($nodo_soportes) {',
            '            $adyacentes_sop = (array)$nodo_soportes->adyacentes();',
            '            foreach ($adyacentes_sop as $nombre_sop => $nodo_pub) {',
            '                $nombre_sop = (string)$nombre_sop;',
            '                $nodo_sop_real = Nodo::nodo_por_id(\'us_\' . $nombre_sop);',
            '                if ($nodo_sop_real) {',
            '                    $nodo_duenos_sop = $nodo_sop_real->adyacente(\'duenos\');',
            '                    if ($nodo_duenos_sop) {',
            '                        $nodo_duenos_sop->eliminar_adyacente($nombre_usuario);',
            '                    }',
            '                }',
            '                $nodo_soportes->eliminar_adyacente($nombre_sop);',
            '            }',
            '            $nodo_usuario->eliminar_adyacente(\'soportes\');',
            '            _destruir_campos_simples($nodo_soportes);',
            '            Nodo::eliminar($nodo_soportes);',
            '        }',
            '    }',
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

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
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

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";