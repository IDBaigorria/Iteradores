<?php
/**
 * Aplicador de cambios — Piloto PHP (frontend del Grafo).
 *
 * Tanda V1.5piloto.76g:
 *   - Bump @version de grafo.js a 1.5piloto.76g.
 *   - Nuevas funciones: `eliminar_huerfanos_grafo` (arma el
 *     modal de confirmación) y `_grafo_ejecutar_eliminacion`
 *     (hace el POST y refresca).
 *   - Listener del botón #grafo_boton_eliminar_huerfanos.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: bump @version a 1.5piloto.76g',
        'buscar' => [' * @version 1.5piloto.74p'],
        'reemplazar' => [' * @version 1.5piloto.76g'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: agregar funciones de eliminación',
        'buscar' => [
            '// Inicialización de listeners del panel Grafo.',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// ELIMINAR NODOS HUÉRFANOS (v1.5piloto.76g)',
            '// ============================================================',
            '',
            '/**',
            ' * Abre el modal de confirmación para eliminar los nodos',
            ' * huérfanos del grafo. Muestra la cantidad total y una',
            ' * vista previa de los primeros 20.',
            ' *',
            ' * No hay chequeo de modo pruebas en este flujo: la limpieza',
            ' * es útil en producción. El backend valida admin/soporte.',
            ' */',
            'async function eliminar_huerfanos_grafo() {',
            '    const resp_res = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/resumen",',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos_res = await resp_res.json();',
            '    if (!datos_res.exito) {',
            '        mostrar_aviso(datos_res.error || "Error al cargar el resumen", \'error\');',
            '        return;',
            '    }',
            '',
            '    const total_huerfanos = datos_res.resumen.huerfanos;',
            '    if (total_huerfanos === 0) {',
            '        mostrar_aviso("No hay nodos basura para eliminar.", \'info\');',
            '        return;',
            '    }',
            '',
            '    // Vista previa: los primeros 20.',
            '    const resp_lista = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/listar",',
            '            nombre_solicitante: usuario_actual.nombre_usuario,',
            '            filtro: "huerfanos",',
            '            enlace: "",',
            '            texto: "",',
            '            offset: "0",',
            '            limite: "20"',
            '        })',
            '    });',
            '    const datos_lista = await resp_lista.json();',
            '    if (!datos_lista.exito) {',
            '        mostrar_aviso(datos_lista.error || "Error al listar huérfanos", \'error\');',
            '        return;',
            '    }',
            '',
            '    let filas = \'\';',
            '    datos_lista.lista.nodos.forEach(n => {',
            '        filas += `<tr>`',
            '            + `<td><code>${_grafo_escape(n.id)}</code></td>`',
            '            + `<td>${_grafo_escape(n.tipo || \'?\')}</td>`',
            '            + `<td style="word-break:break-all;">${_grafo_escape(n.dato || \'\')}</td>`',
            '            + `</tr>`;',
            '    });',
            '',
            '    const aviso_extra = total_huerfanos > 20',
            '        ? `<p class="muted small">Se muestran los primeros 20 de ${total_huerfanos}.</p>`',
            '        : \'\';',
            '',
            '    const html = `',
            '        <p>Se eliminarán <strong>${total_huerfanos}</strong> nodos no alcanzables desde las raíces del grafo.</p>',
            '        <p class="muted small">Son nodos que ya no referencia nadie vivo. Igual, si no tenés backup reciente, conviene cancelar y hacer uno antes.</p>',
            '        <div style="max-height:300px; overflow-y:auto; border:1px solid #ccc; border-radius:4px; margin:10px 0;">',
            '            <table class="data-table" style="margin:0;">',
            '                <thead><tr><th>ID</th><th>Tipo</th><th>Dato</th></tr></thead>',
            '                <tbody>${filas}</tbody>',
            '            </table>',
            '        </div>',
            '        ${aviso_extra}',
            '        <div class="field" style="margin-top:10px;">',
            '            <label><input type="checkbox" id="grafo_eliminar_confirmo"> Confirmo que quiero eliminar estos nodos.</label>',
            '        </div>',
            '        <div class="actions" style="margin-top:15px;">',
            '            <button class="btn danger" id="grafo_eliminar_ejecutar" disabled>Eliminar ${total_huerfanos} nodos</button>',
            '            <button class="btn" id="grafo_eliminar_cancelar">Cancelar</button>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(\'Eliminar nodos basura\', html);',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    if (!cont) return;',
            '    const chk = cont.querySelector(\'#grafo_eliminar_confirmo\');',
            '    const btn = cont.querySelector(\'#grafo_eliminar_ejecutar\');',
            '    chk.addEventListener(\'change\', () => { btn.disabled = !chk.checked; });',
            '    cont.querySelector(\'#grafo_eliminar_cancelar\').addEventListener(\'click\', cerrar_modal_generico);',
            '    btn.addEventListener(\'click\', () => _grafo_ejecutar_eliminacion());',
            '}',
            '',
            '/**',
            ' * Ejecuta la eliminación y refresca el panel.',
            ' */',
            'async function _grafo_ejecutar_eliminacion() {',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/eliminar_huerfanos",',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al eliminar", \'error\');',
            '        return;',
            '    }',
            '    mostrar_aviso(`Eliminados ${datos.eliminados} nodos basura.`, \'exito\');',
            '    cerrar_modal_generico();',
            '    await cargar_grafo();',
            '}',
            '',
            '// Inicialización de listeners del panel Grafo.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'grafo.js: listener del botón eliminar',
        'buscar' => [
            '    const btn_limpiar = document.getElementById(\'grafo_boton_limpiar\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', () => {',
            '        document.getElementById(\'grafo_filtro\').value = \'todos\';',
            '        document.getElementById(\'grafo_filtro_enlace\').value = \'\';',
            '        document.getElementById(\'grafo_filtro_texto\').value = \'\';',
            '        _grafo_cargar_tabla(0);',
            '    });',
            '})();',
        ],
        'reemplazar' => [
            '    const btn_limpiar = document.getElementById(\'grafo_boton_limpiar\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', () => {',
            '        document.getElementById(\'grafo_filtro\').value = \'todos\';',
            '        document.getElementById(\'grafo_filtro_enlace\').value = \'\';',
            '        document.getElementById(\'grafo_filtro_texto\').value = \'\';',
            '        _grafo_cargar_tabla(0);',
            '    });',
            '    const btn_eliminar_huerfanos = document.getElementById(\'grafo_boton_eliminar_huerfanos\');',
            '    if (btn_eliminar_huerfanos) btn_eliminar_huerfanos.addEventListener(\'click\', eliminar_huerfanos_grafo);',
            '})();',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
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
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";