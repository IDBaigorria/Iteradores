<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores.
 *
 * Tanda v1.5piloto.59f: bloqueo de botones de tarjetas de asiento durante una venta.
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
    // Aplicacion/ventas.js
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Bump de version a 1.5piloto.59f',
        'buscar' => [
            ' * @version 1.5piloto.59e',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.59f',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Notificar cambio de venta en curso al abrir el modal',
        'buscar' => [
            '    $("#metodo_pago").disabled = false;',
            '    venta_form_abierto = true;',
            '    $("#contenedor_boton_confirmar_venta").classList.add("hidden");',
            '',
            '    actualizar_visibilidad_cuotas();',
        ],
        'reemplazar' => [
            '    $("#metodo_pago").disabled = false;',
            '    venta_form_abierto = true;',
            '    $("#contenedor_boton_confirmar_venta").classList.add("hidden");',
            '',
            '    actualizar_visibilidad_cuotas();',
            '    _notificar_cambio_venta_en_curso();',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Notificar cambio de venta en curso al cancelar el modal',
        'buscar' => [
            '    $("#cancelar_venta_modal").addEventListener("click", () => {',
            '        $("#formulario_confirmacion_venta").classList.add("hidden");',
            '        $("#info_asiento_viaje").classList.remove("hidden");',
            '        venta_form_abierto = false;',
            '        mostrar_boton_confirmar_venta();',
            '    });',
        ],
        'reemplazar' => [
            '    $("#cancelar_venta_modal").addEventListener("click", () => {',
            '        $("#formulario_confirmacion_venta").classList.add("hidden");',
            '        $("#info_asiento_viaje").classList.remove("hidden");',
            '        venta_form_abierto = false;',
            '        mostrar_boton_confirmar_venta();',
            '        _notificar_cambio_venta_en_curso();',
            '    });',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Notificar cambio de venta en curso al confirmar la venta',
        'buscar' => [
            '            // Cerrar formulario y limpiar',
            '            $("#formulario_confirmacion_venta").classList.add("hidden");',
            '            $("#info_asiento_viaje").classList.remove("hidden");',
            '            venta_form_abierto = false;',
            '            await solicitar_estado_asientos();',
        ],
        'reemplazar' => [
            '            // Cerrar formulario y limpiar',
            '            $("#formulario_confirmacion_venta").classList.add("hidden");',
            '            $("#info_asiento_viaje").classList.remove("hidden");',
            '            venta_form_abierto = false;',
            '            _notificar_cambio_venta_en_curso();',
            '            await solicitar_estado_asientos();',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Agregar hook venta_en_curso y notificador al final',
        'buscar' => [
            'function _romper_atadura() {',
            '    if (!window.atadura_actual) return;',
            '    const idx = window.atadura_actual.indice_pasajero;',
            '    window.atadura_actual = null;',
            '',
            '    const badge_comp = document.getElementById(\'badge_atadura_comprador\');',
            '    if (badge_comp) badge_comp.classList.add(\'hidden\');',
            '    const badge_pas = document.getElementById(`badge_atadura_pasajero_${idx}`);',
            '    if (badge_pas) badge_pas.classList.add(\'hidden\');',
            '}',
        ],
        'reemplazar' => [
            'function _romper_atadura() {',
            '    if (!window.atadura_actual) return;',
            '    const idx = window.atadura_actual.indice_pasajero;',
            '    window.atadura_actual = null;',
            '',
            '    const badge_comp = document.getElementById(\'badge_atadura_comprador\');',
            '    if (badge_comp) badge_comp.classList.add(\'hidden\');',
            '    const badge_pas = document.getElementById(`badge_atadura_pasajero_${idx}`);',
            '    if (badge_pas) badge_pas.classList.add(\'hidden\');',
            '}',
            '',
            '// ============================================================',
            '// Puente con viajes-asientos.js.',
            '//',
            '// Expone si hay una venta en curso para que otros modulos',
            '// puedan bloquear acciones. Tambien notifica a la grilla de',
            '// asientos cuando el estado cambia, para que re-aplique los',
            '// disabled de los botones sin tener que re-renderizar.',
            '// ============================================================',
            '',
            'window.venta_en_curso = function() {',
            '    try {',
            '        return venta_form_abierto === true;',
            '    } catch (e) {',
            '        return false;',
            '    }',
            '};',
            '',
            'function _notificar_cambio_venta_en_curso() {',
            '    if (typeof window.actualizar_bloqueo_botones_asientos === \'function\') {',
            '        try {',
            '            window.actualizar_bloqueo_botones_asientos();',
            '        } catch (e) {',
            '            console.error(\'Error notificando cambio de venta en curso:\', e);',
            '        }',
            '    }',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // Aplicacion/Viajes/viajes-asientos.js
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Bump de version a 1.5piloto.59f',
        'buscar' => [
            ' * @version 1.5piloto.57',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.59f',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Agregar helper _venta_en_curso',
        'buscar' => [
            'function obtener_dueno_viaje_seleccionado() {',
            '    if (viaje_seleccionado && viaje_seleccionado.dueno) {',
            '        return viaje_seleccionado.dueno;',
            '    }',
            '    return obtener_nombre_dueno_actual();',
            '}',
        ],
        'reemplazar' => [
            'function obtener_dueno_viaje_seleccionado() {',
            '    if (viaje_seleccionado && viaje_seleccionado.dueno) {',
            '        return viaje_seleccionado.dueno;',
            '    }',
            '    return obtener_nombre_dueno_actual();',
            '}',
            '',
            '/**',
            ' * Devuelve true si hay una venta en curso. Se apoya en el hook',
            ' * expuesto por ventas.js para no acceder directamente a la',
            ' * variable venta_form_abierto (que es un let de scope global y',
            ' * podria dar TDZ si se la lee muy temprano).',
            ' */',
            'function _venta_en_curso() {',
            '    if (typeof window.venta_en_curso === \'function\') {',
            '        try {',
            '            return window.venta_en_curso() === true;',
            '        } catch (e) {',
            '            return false;',
            '        }',
            '    }',
            '    return false;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Chequeo defensivo en deseleccionar_asiento_pasaje',
        'buscar' => [
            'async function deseleccionar_asiento_pasaje(fila, columna) {',
            '    if (!micro_seleccionado || !viaje_seleccionado) return;',
            '    if (operacion_asiento_en_curso) return;',
        ],
        'reemplazar' => [
            'async function deseleccionar_asiento_pasaje(fila, columna) {',
            '    if (!micro_seleccionado || !viaje_seleccionado) return;',
            '    if (operacion_asiento_en_curso) return;',
            '    if (_venta_en_curso()) {',
            '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar el asiento.\', \'error\');',
            '        return;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'disabled + chequeo en abrir_modal_reservar_asiento',
        'buscar' => [
            'function abrir_modal_reservar_asiento(fila, columna) {',
            '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        ],
        'reemplazar' => [
            'function abrir_modal_reservar_asiento(fila, columna) {',
            '    if (_venta_en_curso()) {',
            '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de reservar un asiento.\', \'error\');',
            '        return;',
            '    }',
            '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Chequeo defensivo en abrir_modal_asignar_pasajero',
        'buscar' => [
            'function abrir_modal_asignar_pasajero(fila, columna) {',
            '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        ],
        'reemplazar' => [
            'function abrir_modal_asignar_pasajero(fila, columna) {',
            '    if (_venta_en_curso()) {',
            '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de asignar un pasajero.\', \'error\');',
            '        return;',
            '    }',
            '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Chequeo defensivo en liberar_reserva_equipo',
        'buscar' => [
            'async function liberar_reserva_equipo(fila, columna) {',
            '    if (!micro_seleccionado || !viaje_seleccionado) return;',
            '    if (operacion_asiento_en_curso) return;',
        ],
        'reemplazar' => [
            'async function liberar_reserva_equipo(fila, columna) {',
            '    if (!micro_seleccionado || !viaje_seleccionado) return;',
            '    if (operacion_asiento_en_curso) return;',
            '    if (_venta_en_curso()) {',
            '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar la reserva.\', \'error\');',
            '        return;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'disabled en botones de tarjeta segun venta en curso',
        'buscar' => [
            '    // Botones',
            '    const botones = [];',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'libre\') {',
            '        botones.push(`<button class="btn primary btn-reservar-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Reservar para equipo</button>`);',
            '    }',
            '',
            '    if (es_terminal && es_propio) {',
            '        botones.push(`<button class="btn danger btn-liberar-seleccion" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar</button>`);',
            '    }',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'reservado\' && !asiento.tiene_pasajero) {',
            '        botones.push(`<button class="btn primary btn-asignar-pasajero" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Asignar pasajero</button>`);',
            '    }',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'reservado\') {',
            '        botones.push(`<button class="btn danger btn-liberar-reserva" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar reserva</button>`);',
            '    }',
        ],
        'reemplazar' => [
            '    // Botones',
            '    const botones = [];',
            '',
            '    // Si hay una venta en curso, los botones que modifican el asiento',
            '    // arrancan deshabilitados. Los de lectura (Ver pasaje / Ver compra)',
            '    // siguen activos.',
            '    const bloqueo_venta = _venta_en_curso() ? \' disabled\' : \'\';',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'libre\') {',
            '        botones.push(`<button class="btn primary btn-reservar-asiento"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Reservar para equipo</button>`);',
            '    }',
            '',
            '    if (es_terminal && es_propio) {',
            '        botones.push(`<button class="btn danger btn-liberar-seleccion"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar</button>`);',
            '    }',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'reservado\' && !asiento.tiene_pasajero) {',
            '        botones.push(`<button class="btn primary btn-asignar-pasajero"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Asignar pasajero</button>`);',
            '    }',
            '',
            '    if (es_dueno_o_admin && asiento.estado === \'reservado\') {',
            '        botones.push(`<button class="btn danger btn-liberar-reserva"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar reserva</button>`);',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Aplicar estado de bloqueo al final de renderizar_tarjetas_asientos',
        'buscar' => [
            '    panel.querySelectorAll(\'.btn-ver-compra-asiento\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => ver_compra_asiento(btn.dataset.ventaId));',
            '    });',
            '}',
        ],
        'reemplazar' => [
            '    panel.querySelectorAll(\'.btn-ver-compra-asiento\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => ver_compra_asiento(btn.dataset.ventaId));',
            '    });',
            '',
            '    // Re-aplicar el estado de bloqueo por si la grilla se dibujo',
            '    // durante una venta en curso.',
            '    actualizar_bloqueo_botones_asientos();',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
        'descripcion' => 'Definir y exponer actualizar_bloqueo_botones_asientos al final',
        'buscar' => [
            '// Listeners globales de actividad',
            'document.addEventListener(\'mousemove\', registrar_actividad_usuario);',
            'document.addEventListener(\'keydown\', registrar_actividad_usuario);',
            'document.addEventListener(\'click\', registrar_actividad_usuario);',
            'document.addEventListener(\'touchstart\', registrar_actividad_usuario);',
        ],
        'reemplazar' => [
            '// Listeners globales de actividad',
            'document.addEventListener(\'mousemove\', registrar_actividad_usuario);',
            'document.addEventListener(\'keydown\', registrar_actividad_usuario);',
            'document.addEventListener(\'click\', registrar_actividad_usuario);',
            'document.addEventListener(\'touchstart\', registrar_actividad_usuario);',
            '',
            '/**',
            ' * Aplica o quita el disabled de los botones que modifican el',
            ' * asiento (Reservar, Liberar, Asignar, Liberar reserva) segun',
            ' * si hay una venta en curso. Los botones de lectura (Ver pasaje,',
            ' * Ver compra) quedan siempre activos.',
            ' *',
            ' * Se llama desde renderizar_tarjetas_asientos y desde el hook',
            ' * que expone ventas.js al abrir/cerrar el modal de venta.',
            ' */',
            'function actualizar_bloqueo_botones_asientos() {',
            '    const bloqueado = _venta_en_curso();',
            '    const selectores = [',
            '        \'.btn-reservar-asiento\',',
            '        \'.btn-liberar-seleccion\',',
            '        \'.btn-asignar-pasajero\',',
            '        \'.btn-liberar-reserva\'',
            '    ];',
            '    selectores.forEach(sel => {',
            '        document.querySelectorAll(sel).forEach(btn => {',
            '            btn.disabled = bloqueado;',
            '        });',
            '    });',
            '}',
            '',
            'window.actualizar_bloqueo_botones_asientos = actualizar_bloqueo_botones_asientos;',
        ],
    ],

    // --------------------------------------------------------
    // estilos-viajes.css
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos-viajes.css',
        'descripcion' => 'Agregar estilos para botones bloqueados por venta en curso',
        'buscar' => [
            '.asiento-card-acciones .btn {',
            '    font-size: 12px;',
            '    padding: 5px 10px;',
            '}',
            '',
            '@media (max-width: 800px) {',
        ],
        'reemplazar' => [
            '.asiento-card-acciones .btn {',
            '    font-size: 12px;',
            '    padding: 5px 10px;',
            '}',
            '',
            '/* v1.5piloto.59f: botones de tarjetas de asiento bloqueados',
            '   mientras hay una venta en curso. */',
            '.asiento-card-acciones .btn:disabled {',
            '    opacity: 0.45;',
            '    cursor: not-allowed;',
            '    filter: grayscale(0.6);',
            '}',
            '',
            '@media (max-width: 800px) {',
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de version de ventas.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.59e"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.59f"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de version de viajes-asientos.js',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.57"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-asientos.js?v=1.5piloto.59f"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump de version de estilos-viajes.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos-viajes.css?v=1.5piloto.40">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos-viajes.css?v=1.5piloto.59f">',
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