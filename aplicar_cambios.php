<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.53: informe imprimible de rendición.
 * - Imprimir informe de rendición (membrete, resumen, terminales, cupones).
 * - Modal chico post-rendición con dos botones (imprimir / cerrar).
 * - Botón "Imprimir informe de rendición" en el modal de detalle.
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
    // 1. Rendicion.php: bump + exponer dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion.php: bump @version a 1.5piloto.53',
        'buscar' => [
            ' * @version   1.5piloto.52',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.53',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'descripcion' => 'Rendicion.php: exponer dueño en formatear_rendicion_resumida',
        'buscar' => [
            '    return [',
            '        \'id_rendicion\' => $id_rendicion,',
            '        \'fecha_hora\' => $fecha_hora,',
            '        \'fecha_iso\' => _fecha_rendicion_a_iso($fecha_hora),',
        ],
        'reemplazar' => [
            '    $nodo_dueno = $nodo_rendicion->adyacente(\'dueno\');',
            '    $nombre_dueno = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '',
            '    return [',
            '        \'id_rendicion\' => $id_rendicion,',
            '        \'dueno\' => $nombre_dueno,',
            '        \'fecha_hora\' => $fecha_hora,',
            '        \'fecha_iso\' => _fecha_rendicion_a_iso($fecha_hora),',
        ],
    ],

    // ============================================================
    // 2. Impresion.php: bump + include Rendicion
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: bump @version a 1.5piloto.53',
        'buscar' => [
            ' * @version   1.5piloto.49',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.53',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: incluir módulo de rendiciones',
        'buscar' => [
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
        ],
        'reemplazar' => [
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
            'include_once("./Aplicacion/Rendiciones/Rendicion.php");',
        ],
    ],

    // ============================================================
    // 3. Impresion.php: nueva función imprimir_informe_rendicion
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: agregar imprimir_informe_rendicion al final',
        'buscar' => [
            '    echo \'</div>\'; // cierre informe',
            '',
            '    echo \'<script>window.onload = function() { window.print(); }</script>\';',
            '    echo \'</body></html>\';',
            '}',
        ],
        'reemplazar' => [
            '    echo \'</div>\'; // cierre informe',
            '',
            '    echo \'<script>window.onload = function() { window.print(); }</script>\';',
            '    echo \'</body></html>\';',
            '}',
            '',
            '/**',
            ' * Imprime el informe completo de una rendición.',
            ' *',
            ' * Incluye membrete, datos generales, resumen, tabla por punto de',
            ' * venta y detalle de los cupones rendidos. Si la rendición está',
            ' * marcada como desactualizada, se agrega un aviso.',
            ' *',
            ' * @param string $id_rendicion',
            ' */',
            'function imprimir_informe_rendicion(string $id_rendicion): void {',
            '    if ($id_rendicion === \'\') {',
            '        echo "ID de rendición no especificado";',
            '        return;',
            '    }',
            '',
            '    $r = obtener_rendicion_por_id($id_rendicion);',
            '    if (!$r) {',
            '        echo "Rendición no encontrada";',
            '        return;',
            '    }',
            '',
            '    $nombre_dueno = $r[\'dueno\'] ?? \'\';',
            '    $nombre_dueno_visible = $nombre_dueno !== \'\' ? _nombre_real_usuario($nombre_dueno) : \'—\';',
            '    $fecha_informe = date(\'d/m/Y H:i\');',
            '    $logo_ruta = \'./Aplicacion/LogoPeque.png\';',
            '',
            '    // Cartel de desactualizada.',
            '    $aviso_desact = \'\';',
            '    if (!empty($r[\'desactualizada\'])) {',
            '        $aviso_desact = \'<div class="aviso-desactualizada"><strong>Rendición desactualizada.</strong> \'',
            '            . htmlspecialchars($r[\'motivo_desactualizada\'] ?? \'\') . \'</div>\';',
            '    }',
            '',
            '    // Tabla por terminal.',
            '    $terminales = $r[\'detalle_terminales\'] ?? [];',
            '    $terminales_html = \'\';',
            '    foreach ($terminales as $t) {',
            '        $nombre_term = $t[\'terminal_nombre_real\'] !== \'\' ? $t[\'terminal_nombre_real\'] : $t[\'terminal\'];',
            '        $terminales_html .= \'<tr>\';',
            '        $terminales_html .= \'<td>\' . htmlspecialchars($nombre_term) . \'</td>\';',
            '        $terminales_html .= \'<td class="num">\' . htmlspecialchars($t[\'cantidad_cupones\']) . \'</td>\';',
            '        $terminales_html .= \'<td class="num">$\' . htmlspecialchars($t[\'efectivo\']) . \'</td>\';',
            '        $terminales_html .= \'<td class="num">$\' . htmlspecialchars($t[\'banco\']) . \'</td>\';',
            '        $terminales_html .= \'<td class="num"><b>$\' . htmlspecialchars($t[\'total\']) . \'</b></td>\';',
            '        $terminales_html .= \'</tr>\';',
            '    }',
            '    if ($terminales_html === \'\') {',
            '        $terminales_html = \'<tr><td colspan="5" style="text-align:center;color:#666;">Sin datos</td></tr>\';',
            '    }',
            '',
            '    // Tabla de cupones.',
            '    $cupones = $r[\'detalle_cupones\'] ?? [];',
            '    $cupones_html = \'\';',
            '    foreach ($cupones as $c) {',
            '        $metodo = ($c[\'metodo_pago\'] === \'transferencia\') ? \'Transferencia\' : \'Efectivo\';',
            '        $nombre_term_c = $c[\'terminal_nombre_real\'] !== \'\' ? $c[\'terminal_nombre_real\'] : $c[\'terminal\'];',
            '        $cupones_html .= \'<tr>\';',
            '        $cupones_html .= \'<td>\' . htmlspecialchars($c[\'venta_id\']) . \'</td>\';',
            '        $cupones_html .= \'<td class="num">\' . htmlspecialchars($c[\'numero_cupon\']) . \'</td>\';',
            '        $cupones_html .= \'<td class="num">$\' . htmlspecialchars($c[\'monto\']) . \'</td>\';',
            '        $cupones_html .= \'<td>\' . htmlspecialchars($metodo) . \'</td>\';',
            '        $cupones_html .= \'<td>\' . htmlspecialchars($nombre_term_c) . \'</td>\';',
            '        $cupones_html .= \'</tr>\';',
            '    }',
            '    if ($cupones_html === \'\') {',
            '        $cupones_html = \'<tr><td colspan="5" style="text-align:center;color:#666;">Sin datos</td></tr>\';',
            '    }',
            '',
            '    echo \'<!DOCTYPE html>\';',
            '    echo \'<html lang="es">\';',
            '    echo \'<head><meta charset="UTF-8"><title>Informe de rendición</title>\';',
            '    echo \'<style>',
            '        body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 20px; background: white; color: black; font-size: 12px; }',
            '        .informe { max-width: 1000px; margin: 0 auto; }',
            '        .membrete { display: flex; align-items: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; }',
            '        .membrete img { width: 60px; height: 60px; object-fit: contain; filter: grayscale(100%); margin-right: 15px; }',
            '        .membrete-texto { font-size: 18px; font-weight: bold; letter-spacing: 1px; }',
            '        .titulo { text-align: center; margin-bottom: 20px; }',
            '        .titulo h1 { margin: 0 0 6px 0; font-size: 22px; }',
            '        .titulo .meta { font-size: 13px; color: #333; }',
            '        .titulo .meta b { color: black; }',
            '        .seccion { border: 1px solid black; border-radius: 6px; padding: 14px 16px; margin-bottom: 18px; }',
            '        .seccion h2 { margin: 0 0 12px 0; border-bottom: 1px solid black; padding-bottom: 6px; font-size: 16px; }',
            '        .datos-lista { margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.7; }',
            '        .datos-lista b { display: inline-block; min-width: 110px; }',
            '        .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }',
            '        .resumen-item { border: 1px solid #ccc; border-radius: 6px; padding: 10px; text-align: center; }',
            '        .resumen-item span { display: block; font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }',
            '        .resumen-item b { display: block; font-size: 18px; }',
            '        table.tabla { width: 100%; border-collapse: collapse; font-size: 12px; }',
            '        table.tabla th, table.tabla td { border: 1px solid black; padding: 6px 8px; text-align: left; }',
            '        table.tabla th { background: #f0f0f0; font-weight: 700; }',
            '        table.tabla td.num { text-align: right; }',
            '        .aviso-desactualizada { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; }',
            '        .aviso-desactualizada strong { display: block; margin-bottom: 4px; }',
            '        @media print { body { padding: 10px; } .informe { max-width: 100%; } }',
            '    </style>\';',
            '    echo \'</head><body>\';',
            '',
            '    echo \'<div class="informe">\';',
            '',
            '    echo \'<div class="membrete">\';',
            '    echo \'<img src="\' . htmlspecialchars($logo_ruta) . \'" alt="Logo">\';',
            '    echo \'<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'<div class="titulo">\';',
            '    echo \'<h1>Informe de rendición</h1>\';',
            '    echo \'<div class="meta"><b>Código:</b> \' . htmlspecialchars($r[\'id_rendicion\']) . \' · <b>Fecha:</b> \' . htmlspecialchars($r[\'fecha_hora\']) . \' · <b>Impreso:</b> \' . htmlspecialchars($fecha_informe) . \'</div>\';',
            '    echo \'</div>\';',
            '',
            '    echo $aviso_desact;',
            '',
            '    echo \'<div class="seccion">\';',
            '    echo \'<h2>Datos generales</h2>\';',
            '    echo \'<ul class="datos-lista">\';',
            '    echo \'<li><b>Dueño:</b> \' . htmlspecialchars($nombre_dueno_visible) . \'</li>\';',
            '    echo \'<li><b>Cupones rendidos:</b> \' . htmlspecialchars($r[\'cantidad_cupones\']) . \'</li>\';',
            '    echo \'<li><b>Ventas incluidas:</b> \' . htmlspecialchars($r[\'cantidad_ventas\']) . \'</li>\';',
            '    echo \'</ul>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'<div class="seccion">\';',
            '    echo \'<h2>Resumen</h2>\';',
            '    echo \'<div class="resumen-grid">\';',
            '    echo \'<div class="resumen-item"><span>Total</span><b>$\' . htmlspecialchars($r[\'total\']) . \'</b></div>\';',
            '    echo \'<div class="resumen-item"><span>Efectivo</span><b>$\' . htmlspecialchars($r[\'total_efectivo\']) . \'</b></div>\';',
            '    echo \'<div class="resumen-item"><span>Banco</span><b>$\' . htmlspecialchars($r[\'total_banco\']) . \'</b></div>\';',
            '    echo \'</div>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'<div class="seccion">\';',
            '    echo \'<h2>Por punto de venta</h2>\';',
            '    echo \'<table class="tabla">\';',
            '    echo \'<thead><tr><th>Terminal</th><th>Cupones</th><th>Efectivo</th><th>Banco</th><th>Total</th></tr></thead>\';',
            '    echo \'<tbody>\' . $terminales_html . \'</tbody>\';',
            '    echo \'</table>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'<div class="seccion">\';',
            '    echo \'<h2>Cupones rendidos</h2>\';',
            '    echo \'<table class="tabla">\';',
            '    echo \'<thead><tr><th>Venta</th><th>Cupón</th><th>Monto</th><th>Método</th><th>Terminal</th></tr></thead>\';',
            '    echo \'<tbody>\' . $cupones_html . \'</tbody>\';',
            '    echo \'</table>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'</div>\'; // cierre informe',
            '',
            '    echo \'<script>window.onload = function() { window.print(); }</script>\';',
            '    echo \'</body></html>\';',
            '}',
        ],
    ],

    // ============================================================
    // 4. index.php: bump + branch de impresión de rendición
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.53',
        'buscar' => [
            ' * @version   1.5piloto.50',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.53',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: branch para informe de rendición',
        'buscar' => [
            '    // Caso especial: pasajes actualizados de un pasajero en viajes activos.',
            '    // Se pasa dueño y DNI por GET.',
            '    if ($tipo === \'pasajes_actualizados\') {',
            '        imprimir_pasajes_actualizados(',
            '            $_GET[\'dueno\'] ?? \'\',',
            '            $_GET[\'dni\'] ?? \'\'',
            '        );',
            '        exit;',
            '    }',
        ],
        'reemplazar' => [
            '    // Caso especial: pasajes actualizados de un pasajero en viajes activos.',
            '    // Se pasa dueño y DNI por GET.',
            '    if ($tipo === \'pasajes_actualizados\') {',
            '        imprimir_pasajes_actualizados(',
            '            $_GET[\'dueno\'] ?? \'\',',
            '            $_GET[\'dni\'] ?? \'\'',
            '        );',
            '        exit;',
            '    }',
            '',
            '    // Caso especial: informe imprimible de una rendición.',
            '    // Se pasa el id_rendicion por GET.',
            '    if ($tipo === \'informe_rendicion\') {',
            '        imprimir_informe_rendicion($_GET[\'id_rendicion\'] ?? \'\');',
            '        exit;',
            '    }',
        ],
    ],

    // ============================================================
    // 5. aplicacion_GET.html: bump de ventas.js y rendiciones.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de ventas.js a 53',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.50"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.53"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump de rendiciones.js a 53',
        'buscar' => [
            '<script src="Aplicacion/rendiciones.js?v=1.5piloto.51"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/rendiciones.js?v=1.5piloto.53"></script>',
        ],
    ],

    // ============================================================
    // 6. aplicacion_GET.html: modal chico nuevo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: modal chico de impresión de rendición',
        'buscar' => [
            '<div id="modal_chico_impresion_pasajero" class="modal-chico hidden">',
            '    <strong id="modal_chico_impresion_pasajero_titulo">Pasajero actualizado</strong>',
            '    <div style="margin-top:10px;">',
            '        <button class="btn primary" id="btn_modal_chico_imprimir_pasajero">Imprimir pasajes actualizados</button>',
            '        <button class="btn" id="btn_modal_chico_cerrar_pasajero">Cerrar</button>',
            '    </div>',
            '</div>',
        ],
        'reemplazar' => [
            '<div id="modal_chico_impresion_pasajero" class="modal-chico hidden">',
            '    <strong id="modal_chico_impresion_pasajero_titulo">Pasajero actualizado</strong>',
            '    <div style="margin-top:10px;">',
            '        <button class="btn primary" id="btn_modal_chico_imprimir_pasajero">Imprimir pasajes actualizados</button>',
            '        <button class="btn" id="btn_modal_chico_cerrar_pasajero">Cerrar</button>',
            '    </div>',
            '</div>',
            '<div id="modal_chico_impresion_rendicion" class="modal-chico hidden">',
            '    <strong id="modal_chico_impresion_rendicion_titulo">Rendición cerrada</strong>',
            '    <div style="margin-top:10px;">',
            '        <button class="btn primary" id="btn_modal_chico_imprimir_rendicion">Imprimir informe de rendición</button>',
            '        <button class="btn" id="btn_modal_chico_cerrar_rendicion">Cerrar</button>',
            '    </div>',
            '</div>',
        ],
    ],

    // ============================================================
    // 7. ventas.js: bump + modal chico tras confirmar rendición
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.53',
        'buscar' => [
            ' * @version 1.5piloto.50',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.53',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: mostrar modal chico al confirmar rendición',
        'buscar' => [
            '    mostrar_aviso(`Rendición ${resultado.id_rendicion} guardada ($${_formatear_monto_rendicion(resultado.total)})`, \'exito\');',
            '',
            '    // Limpiar estado y cerrar modal.',
            '    window.rendicion_preview_actual = null;',
            '    window.rendicion_filtros_actual = null;',
            '    window.rendicion_seleccion = null;',
            '    cerrar_modal_generico();',
            '',
            '    // Refrescar la lista de ventas para que las tarjetas reflejen',
            '    // el nuevo estado (a_rendir actualizado).',
            '    if (typeof cargar_ventas === \'function\') cargar_ventas();',
            '}',
        ],
        'reemplazar' => [
            '    mostrar_aviso(`Rendición ${resultado.id_rendicion} guardada ($${_formatear_monto_rendicion(resultado.total)})`, \'exito\');',
            '',
            '    // Limpiar estado y cerrar modal.',
            '    window.rendicion_preview_actual = null;',
            '    window.rendicion_filtros_actual = null;',
            '    window.rendicion_seleccion = null;',
            '    cerrar_modal_generico();',
            '',
            '    // Refrescar la lista de ventas para que las tarjetas reflejen',
            '    // el nuevo estado (a_rendir actualizado).',
            '    if (typeof cargar_ventas === \'function\') cargar_ventas();',
            '',
            '    // Mostrar el modal chico para ofrecer la impresión del informe.',
            '    mostrar_modal_chico_impresion_rendicion(resultado.id_rendicion);',
            '}',
            '',
            '/**',
            ' * Muestra el modal chico flotante para imprimir el informe de una',
            ' * rendición recién cerrada. Se cierra solo con el botón "Cerrar" o',
            ' * al apretar "Imprimir informe de rendición".',
            ' *',
            ' * @param {string} id_rendicion',
            ' */',
            'function mostrar_modal_chico_impresion_rendicion(id_rendicion) {',
            '    const contenedor = document.getElementById(\'modal_chico_impresion_rendicion\');',
            '    const titulo = document.getElementById(\'modal_chico_impresion_rendicion_titulo\');',
            '    const btnImprimir = document.getElementById(\'btn_modal_chico_imprimir_rendicion\');',
            '    const btnCerrar = document.getElementById(\'btn_modal_chico_cerrar_rendicion\');',
            '    if (!contenedor || !titulo || !btnImprimir || !btnCerrar) return;',
            '',
            '    titulo.textContent = `Rendición ${id_rendicion} cerrada`;',
            '',
            '    btnImprimir.onclick = () => {',
            '        const url = `index.php?imprimir=1&tipo=informe_rendicion&id_rendicion=${encodeURIComponent(id_rendicion)}`;',
            '        window.open(url, \'_blank\');',
            '    };',
            '',
            '    btnCerrar.onclick = () => {',
            '        contenedor.classList.add(\'hidden\');',
            '    };',
            '',
            '    contenedor.classList.remove(\'hidden\');',
            '}',
        ],
    ],

    // ============================================================
    // 8. rendiciones.js: bump + botón imprimir en modal detalle
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'rendiciones.js: bump @version a 1.5piloto.53',
        'buscar' => [
            ' * @version 1.5piloto.51',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.53',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/rendiciones.js',
        'descripcion' => 'rendiciones.js: botón imprimir en el modal de detalle',
        'buscar' => [
            '            <div class="rendicion-acciones">',
            '                <button class="btn" id="btn_cerrar_detalle_rendicion">Cerrar</button>',
            '            </div>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(\'Detalle de rendición\', html);',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    cont.querySelector(\'#btn_cerrar_detalle_rendicion\').addEventListener(\'click\', cerrar_modal_generico);',
        ],
        'reemplazar' => [
            '            <div class="rendicion-acciones">',
            '                <button class="btn primary" id="btn_imprimir_detalle_rendicion">Imprimir informe de rendición</button>',
            '                <button class="btn" id="btn_cerrar_detalle_rendicion">Cerrar</button>',
            '            </div>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(\'Detalle de rendición\', html);',
            '',
            '    const cont = document.getElementById(\'modal_generico_contenido\');',
            '    cont.querySelector(\'#btn_cerrar_detalle_rendicion\').addEventListener(\'click\', cerrar_modal_generico);',
            '    cont.querySelector(\'#btn_imprimir_detalle_rendicion\').addEventListener(\'click\', () => {',
            '        const url = `index.php?imprimir=1&tipo=informe_rendicion&id_rendicion=${encodeURIComponent(r.id_rendicion)}`;',
            '        window.open(url, \'_blank\');',
            '    });',
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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";