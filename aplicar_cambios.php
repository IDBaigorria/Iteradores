<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.76b:
 *   - Cerrar todos los modales al cerrar sesión.
 *     _limpiar_contenido_dinamico ahora cierra el modal genérico,
 *     el apilado, el modal chico de post-venta (#opciones_impresion)
 *     y todos los modales chicos flotantes (.modal-chico).
 *   - Bump del ?v= de aplicacion.js en aplicacion_GET.html.
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

    // --------------------------------------------------------
    // aplicacion.js
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Bump @version a 1.5piloto.76b',
        'buscar' => [
            ' * @version 1.5piloto.74p',
            ' */',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.76b',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'Cerrar modales al principio de _limpiar_contenido_dinamico',
        'buscar' => [
            'function _limpiar_contenido_dinamico() {',
            '    const ids_a_limpiar = [',
        ],
        'reemplazar' => [
            'function _limpiar_contenido_dinamico() {',
            '    // Fase 8.5, v76b: cerrar todos los modales antes de limpiar',
            '    // el contenido. Antes solo se limpiaba el contenido interno',
            '    // (tablas, listas, selectores), pero los overlays de modal',
            '    // quedaban visibles al salir. Esto tapaba la pantalla de',
            '    // login con el modal abierto hasta el próximo clic.',
            '    //',
            '    // Se llama desde salir() y desde _aplicar_login_exitoso(),',
            '    // así que cubre tanto el logout manual como el cambio de',
            '    // sesión sin recargar la página.',
            '',
            '    // 1. Modal apilado (está encima).',
            '    if (typeof cerrar_modal_apilado === \'function\') {',
            '        cerrar_modal_apilado();',
            '    }',
            '',
            '    // 2. Modal genérico. Cierre directo, sin ejecutar el hook',
            '    //    window.on_cerrar_modal_generico (que puede disparar',
            '    //    fetches que ya no tienen sentido al cerrar sesión).',
            '    window.on_cerrar_modal_generico = null;',
            '    on_volver_modal = null;',
            '    const modal_generico_el = document.getElementById(\'modal_generico\');',
            '    if (modal_generico_el) {',
            '        modal_generico_el.classList.add(\'hidden\');',
            '        modal_generico_el.style.display = \'none\';',
            '    }',
            '    const modal_generico_contenido = document.getElementById(\'modal_generico_contenido\');',
            '    if (modal_generico_contenido) modal_generico_contenido.innerHTML = \'\';',
            '',
            '    // 3. Modal chico de post-venta (#opciones_impresion).',
            '    const modal_impresion = document.getElementById(\'opciones_impresion\');',
            '    if (modal_impresion) {',
            '        modal_impresion.classList.add(\'hidden\');',
            '        modal_impresion.style.display = \'none\';',
            '    }',
            '',
            '    // 4. Resto de los modales chicos flotantes (.modal-chico).',
            '    document.querySelectorAll(\'.modal-chico\').forEach(el => {',
            '        el.classList.add(\'hidden\');',
            '        el.style.display = \'none\';',
            '    });',
            '',
            '    const ids_a_limpiar = [',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de aplicacion.js a 1.5piloto.76b',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.74p"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.76b"></script>',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v76b',
        'buscar' => [
            '- **v76a**: Fase 3 del plan de optimización del grafo.',
        ],
        'reemplazar' => [
            '- **v76b**: cierre del pendiente "cerrar todos los',
            '  modales al cerrar sesión". `_limpiar_contenido_dinamico`',
            '  ahora cierra el modal genérico (sin disparar el hook',
            '  `window.on_cerrar_modal_generico`, que ya no tiene',
            '  sentido al salir), el apilado, el `#opciones_impresion`',
            '  y todos los `.modal-chico` flotantes. Cubre tanto el',
            '  logout manual como el cambio de sesión sin recargar.',
            '- **v76a**: Fase 3 del plan de optimización del grafo.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.5: marcar el pendiente de modales como hecho',
        'buscar' => [
            '- **Cerrar todos los modales al cerrar sesión** (prioridad',
            '  media). Actualmente el modal chico de post-venta',
            '  (`#opciones_impresion`) y a veces otros quedan abiertos al',
            '  salir. Debería limpiarse todo en `salir()` de `aplicacion.js`.',
            '  El `_limpiar_contenido_dinamico` actual limpia el contenido',
            '  de los contenedores pero no oculta los overlays de modal',
            '  (`#modal_generico`, `#modal_apilado`) ni los modales chicos',
            '  flotantes (`#modal_chico_*`).',
        ],
        'reemplazar' => [
            '- ~~**Cerrar todos los modales al cerrar sesión**~~.',
            '  **Resuelto en v76b.** `_limpiar_contenido_dinamico` cierra',
            '  el modal genérico (con cierre directo, sin ejecutar el hook',
            '  `window.on_cerrar_modal_generico`), el apilado, el',
            '  `#opciones_impresion` y todos los `.modal-chico` flotantes.',
            '  Cubre el logout manual y el cambio de sesión sin recargar',
            '  (que también llama a `_limpiar_contenido_dinamico` desde',
            '  `_aplicar_login_exitoso`).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v76b',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76a',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76b',
            '(cierre del pendiente "cerrar todos los modales al',
            'cerrar sesión". `_limpiar_contenido_dinamico` ahora',
            'cierra el modal genérico, el apilado, el',
            '`#opciones_impresion` y todos los `.modal-chico`.',
            'Cubre logout manual y cambio de sesión.).',
            'Antes: v1.5piloto.76a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v76b',
        'buscar' => [
            '- Cerramos en v76a la primera parte de Fase 3:',
        ],
        'reemplazar' => [
            '- Cerramos en v76b el pendiente de backlog "cerrar',
            '  todos los modales al cerrar sesión". Ahora',
            '  `_limpiar_contenido_dinamico` cierra el modal',
            '  genérico, el apilado, el `#opciones_impresion` y',
            '  todos los `.modal-chico` flotantes. Cubre el logout',
            '  manual y el cambio de sesión sin recargar.',
            '- Cerramos en v76a la primera parte de Fase 3:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v76b',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76a (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76b (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v76b',
        'buscar' => [
            'v76a: Fase 3, índice de ventas',
            'por viaje.',
        ],
        'reemplazar' => [
            'v76a: Fase 3, índice de ventas',
            'por viaje. v76b: cerrar todos los modales al',
            'cerrar sesión.',
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