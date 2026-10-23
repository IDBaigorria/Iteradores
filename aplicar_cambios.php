<?php
/**
 * Aplicador — Tanda 4: piso en los flujos que faltan.
 *
 * Motivo: después del fix del croquis (v1.5piloto.82h), quedaron
 * tres flujos que todavía matchean por (fila, columna) sin piso:
 *  - Cambiar de asiento desde la pestaña Clientes (pasajeros.js).
 *  - Imprimir pasaje de reserva (pasaje_reserva).
 *  - Croquis impreso del micro (imprimir_croquis_micro).
 *
 * Fix: agregar `piso` a `formatear_venta_completa` (Venta.php),
 * pasarlo en los 3 flujos, y hacer que `imprimir_pasaje_reserva`
 * e `imprimir_croquis_micro` matcheen por (piso, fila, columna).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.82i';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. Venta.php — precalcular mapa id_asiento => piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'formatear_venta_completa: precalcular mapa id_asiento => piso',
    'buscar' => [
        '    // Asientos',
        '    $asientos = [];',
        '    $cabeza_asientos = $nodo_venta->adyacente(\'asientos\');',
    ],
    'reemplazar' => [
        '    // Fix v1.5piloto.82i: precalcular mapa id_asiento => piso.',
        '    // Necesario para desambiguar asientos de pisos distintos con',
        '    // la misma (fila, columna) en formatear_venta_completa.',
        '    $nodo_micro_para_piso = $nodo_venta->adyacente(\'micro\');',
        '    $mapa_piso_asientos = [];',
        '    if ($nodo_micro_para_piso) {',
        '        $nodo_copia_mapa = $nodo_micro_para_piso->adyacente(\'vehiculo_copia\');',
        '        if ($nodo_copia_mapa) {',
        '            $nodo_asientos_mapa = $nodo_copia_mapa->adyacente(\'asientos\');',
        '            if ($nodo_asientos_mapa) {',
        '                for ($i_piso_mapa = 1; $i_piso_mapa <= 2; $i_piso_mapa++) {',
        '                    $piso_mapa = $nodo_asientos_mapa->adyacente("piso_$i_piso_mapa");',
        '                    if (!$piso_mapa) continue;',
        '                    $cabeza_mapa = $piso_mapa->adyacente(\'asientos\');',
        '                    if (!$cabeza_mapa) continue;',
        '                    $actual_mapa = $cabeza_mapa->adyacente(\'primer\');',
        '                    $seg_mapa = 0;',
        '                    while ($actual_mapa && $actual_mapa->id() !== $cabeza_mapa->id() && $seg_mapa < 200) {',
        '                        $mapa_piso_asientos[(string)$actual_mapa->id()] = (string)$i_piso_mapa;',
        '                        $actual_mapa = $actual_mapa->adyacente(\'siguiente\');',
        '                        $seg_mapa++;',
        '                    }',
        '                }',
        '            }',
        '        }',
        '    }',
        '',
        '    // Asientos',
        '    $asientos = [];',
        '    $cabeza_asientos = $nodo_venta->adyacente(\'asientos\');',
    ],
],

// ============================================================
// 2. Venta.php — agregar 'piso' al asiento_info
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Ventas/Venta.php',
    'descripcion' => 'formatear_venta_completa: agregar piso al asiento_info',
    'buscar' => [
        '            $asiento_info = [',
        '                \'numero\' => $nodo_asiento_real ? $nodo_asiento_real->dato() : \'\',',
        '                \'fila\' => $nodo_asiento_real && $nodo_asiento_real->adyacente(\'fila\') ? $nodo_asiento_real->adyacente(\'fila\')->dato() : \'\',',
        '                \'columna\' => $nodo_asiento_real && $nodo_asiento_real->adyacente(\'columna\') ? $nodo_asiento_real->adyacente(\'columna\')->dato() : \'\',',
        '                \'punto_subida_bajada\' => $nodo_punto_sb ? $nodo_punto_sb->dato() : null,',
        '                \'hora_subida_bajada\' => $nodo_hora_sb ? $nodo_hora_sb->dato() : null,',
        '            ];',
    ],
    'reemplazar' => [
        '            $asiento_info = [',
        '                \'numero\' => $nodo_asiento_real ? $nodo_asiento_real->dato() : \'\',',
        '                \'piso\' => $nodo_asiento_real ? ($mapa_piso_asientos[(string)$nodo_asiento_real->id()] ?? \'\') : \'\',',
        '                \'fila\' => $nodo_asiento_real && $nodo_asiento_real->adyacente(\'fila\') ? $nodo_asiento_real->adyacente(\'fila\')->dato() : \'\',',
        '                \'columna\' => $nodo_asiento_real && $nodo_asiento_real->adyacente(\'columna\') ? $nodo_asiento_real->adyacente(\'columna\')->dato() : \'\',',
        '                \'punto_subida_bajada\' => $nodo_punto_sb ? $nodo_punto_sb->dato() : null,',
        '                \'hora_subida_bajada\' => $nodo_hora_sb ? $nodo_hora_sb->dato() : null,',
        '            ];',
    ],
],

// ============================================================
// 3. Impresion.php — imprimir_pasaje_reserva: firma con piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'imprimir_pasaje_reserva: firma con piso',
    'buscar' => [
        'function imprimir_pasaje_reserva(string $nombre_dueno, string $nombre_viaje, string $nombre_micro, string $fila, string $columna): void {',
    ],
    'reemplazar' => [
        'function imprimir_pasaje_reserva(string $nombre_dueno, string $nombre_viaje, string $nombre_micro, string $fila, string $columna, ?string $piso = null): void {',
    ],
],

// ============================================================
// 4. Impresion.php — imprimir_pasaje_reserva: matcheo por piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'imprimir_pasaje_reserva: matcheo por piso',
    'buscar' => [
        '    // Buscar el asiento por fila/columna',
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    // Buscar el asiento por (piso, fila, columna). Si $piso viene',
        '    // null, comportamiento anterior (compatibilidad con URLs viejas).',
        '    // Fix v1.5piloto.82i.',
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
    ],
],

// ============================================================
// 5. Impresion.php — imprimir_croquis_micro: mapa con piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'imprimir_croquis_micro: mapa de pasajeros con piso',
    'buscar' => [
        '    // Mapa de pasajeros por fila-columna para lookup rápido.',
        '    $pasajeros = _recolectar_asientos_con_pasajero_de_micro($nodo_micro);',
        '    $por_fc = [];',
        '    foreach ($pasajeros as $p) {',
        '        $por_fc[$p[\'fila\'] . \'-\' . $p[\'columna\']] = $p;',
        '    }',
    ],
    'reemplazar' => [
        '    // Mapa de pasajeros por (piso, fila, columna) para lookup rápido.',
        '    // Fix v1.5piloto.82i: clave con piso para no pisar asientos de',
        '    // pisos distintos con la misma (fila, columna).',
        '    $pasajeros = _recolectar_asientos_con_pasajero_de_micro($nodo_micro);',
        '    $por_pfc = [];',
        '    foreach ($pasajeros as $p) {',
        '        $por_pfc[$p[\'piso\'] . \'-\' . $p[\'fila\'] . \'-\' . $p[\'columna\']] = $p;',
        '    }',
    ],
],

// ============================================================
// 6. Impresion.php — imprimir_croquis_micro: lookup con piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Impresion/Impresion.php',
    'descripcion' => 'imprimir_croquis_micro: lookup con piso',
    'buscar' => [
        '                $info_pas = $por_fc[$clave_fc] ?? null;',
    ],
    'reemplazar' => [
        '                $info_pas = $por_pfc[$i . \'-\' . $clave_fc] ?? null;',
    ],
],

// ============================================================
// 7. pasajeros.js — pasar asientoInfo.piso a cambiar_asiento
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/pasajeros.js',
    'descripcion' => 'ver_detalle_pasaje_individual: pasar piso a cambiar_asiento',
    'buscar' => [
        '            abrir_modal_cambiar_asiento(',
        '                nombre_dueno,',
        '                venta.viaje,',
        '                nombre_micro_real,',
        '                asientoInfo.fila,',
        '                asientoInfo.columna,',
        '                (info) => {',
        '                    // v77d: re-abrir el detalle con el asiento',
        '                    // nuevo. El modal apilado ya está cerrado,',
        '                    // así que el modal genérico se actualiza',
        '                    // con los datos frescos del backend.',
        '                    if (info && info.destino_numero) {',
        '                        ver_detalle_pasaje_individual(',
        '                            venta.id_venta,',
        '                            pasajero.dni,',
        '                            info.destino_numero',
        '                        );',
        '                    }',
        '                }',
        '            );',
    ],
    'reemplazar' => [
        '            abrir_modal_cambiar_asiento(',
        '                nombre_dueno,',
        '                venta.viaje,',
        '                nombre_micro_real,',
        '                asientoInfo.fila,',
        '                asientoInfo.columna,',
        '                (info) => {',
        '                    // v77d: re-abrir el detalle con el asiento',
        '                    // nuevo. El modal apilado ya está cerrado,',
        '                    // así que el modal genérico se actualiza',
        '                    // con los datos frescos del backend.',
        '                    if (info && info.destino_numero) {',
        '                        ver_detalle_pasaje_individual(',
        '                            venta.id_venta,',
        '                            pasajero.dni,',
        '                            info.destino_numero',
        '                        );',
        '                    }',
        '                },',
        '                asientoInfo.piso',
        '            );',
    ],
],

// ============================================================
// 8. viajes-asientos.js — mostrar_modal_chico_impresion_reserva:
//    aceptar piso y agregarlo a la URL
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'mostrar_modal_chico_impresion_reserva: firma con piso',
    'buscar' => [
        'function mostrar_modal_chico_impresion_reserva(nombre_dueno, nombre_viaje, nombre_micro, fila, columna, numero_asiento) {',
    ],
    'reemplazar' => [
        'function mostrar_modal_chico_impresion_reserva(nombre_dueno, nombre_viaje, nombre_micro, fila, columna, numero_asiento, piso = null) {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'mostrar_modal_chico_impresion_reserva: URL con piso',
    'buscar' => [
        '    btnImprimir.onclick = () => {',
        '        const url = `index.php?imprimir=1&tipo=pasaje_reserva`',
        '            + `&dueno=${encodeURIComponent(nombre_dueno)}`',
        '            + `&viaje=${encodeURIComponent(nombre_viaje)}`',
        '            + `&micro=${encodeURIComponent(nombre_micro)}`',
        '            + `&fila=${encodeURIComponent(fila)}`',
        '            + `&columna=${encodeURIComponent(columna)}`;',
        '        window.open(url, \'_blank\');',
        '    };',
    ],
    'reemplazar' => [
        '    btnImprimir.onclick = () => {',
        '        let url = `index.php?imprimir=1&tipo=pasaje_reserva`',
        '            + `&dueno=${encodeURIComponent(nombre_dueno)}`',
        '            + `&viaje=${encodeURIComponent(nombre_viaje)}`',
        '            + `&micro=${encodeURIComponent(nombre_micro)}`',
        '            + `&fila=${encodeURIComponent(fila)}`',
        '            + `&columna=${encodeURIComponent(columna)}`;',
        '        if (piso !== null && piso !== undefined && piso !== \'\') {',
        '            url += `&piso=${encodeURIComponent(piso)}`;',
        '        }',
        '        window.open(url, \'_blank\');',
        '    };',
    ],
],

// ============================================================
// 9. viajes-asientos.js — llamada desde
//    abrir_modal_reservar_asiento: pasar piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_reservar_asiento: pasar piso a la impresión',
    'buscar' => [
        '            if (datos_pasajero) {',
        '                mostrar_modal_chico_impresion_reserva(',
        '                    nombre_dueno_reserva,',
        '                    nombre_viaje_reserva,',
        '                    nombre_micro_reserva,',
        '                    fila,',
        '                    columna,',
        '                    asiento.numero',
        '                );',
        '            }',
    ],
    'reemplazar' => [
        '            if (datos_pasajero) {',
        '                mostrar_modal_chico_impresion_reserva(',
        '                    nombre_dueno_reserva,',
        '                    nombre_viaje_reserva,',
        '                    nombre_micro_reserva,',
        '                    fila,',
        '                    columna,',
        '                    asiento.numero,',
        '                    piso',
        '                );',
        '            }',
    ],
],

// ============================================================
// 10. viajes-asientos.js — llamada desde
//     abrir_modal_asignar_pasajero: pasar piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_asignar_pasajero: pasar piso a la impresión',
    'buscar' => [
        '            mostrar_modal_chico_impresion_reserva(',
        '                nombre_dueno,',
        '                nombre_viaje_asig,',
        '                nombre_micro_asig,',
        '                fila,',
        '                columna,',
        '                asiento.numero',
        '            );',
    ],
    'reemplazar' => [
        '            mostrar_modal_chico_impresion_reserva(',
        '                nombre_dueno,',
        '                nombre_viaje_asig,',
        '                nombre_micro_asig,',
        '                fila,',
        '                columna,',
        '                asiento.numero,',
        '                piso',
        '            );',
    ],
],

// ============================================================
// 11. viajes-asientos.js — ver_pasaje_asiento: botón con
//     data-piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'ver_pasaje_asiento: botón imprimir con data-piso',
    'buscar' => [
        '        html += `<div class="actions" style="margin-top:15px;">',
        '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="reserva" data-dueno="${nd}" data-viaje="${nv}" data-micro="${nm}" data-fila="${fila}" data-columna="${columna}">Imprimir pasaje</button>',
        '            ${btn_cambiar_html}',
        '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
        '        </div>`;',
    ],
    'reemplazar' => [
        '        html += `<div class="actions" style="margin-top:15px;">',
        '            <button class="btn primary" id="btn_imprimir_pasaje_asiento" data-modo="reserva" data-dueno="${nd}" data-viaje="${nv}" data-micro="${nm}" data-fila="${fila}" data-columna="${columna}" data-piso="${piso || \'\'}">Imprimir pasaje</button>',
        '            ${btn_cambiar_html}',
        '            <button class="btn" id="btn_cerrar_ver_pasaje">Cerrar</button>',
        '        </div>`;',
    ],
],

// ============================================================
// 12. viajes-asientos.js — ver_pasaje_asiento: listener
//     con piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'ver_pasaje_asiento: URL con piso',
    'buscar' => [
        '            } else {',
        '                const nd = this.dataset.dueno;',
        '                const nv = this.dataset.viaje;',
        '                const nm = this.dataset.micro;',
        '                const fila = this.dataset.fila;',
        '                const columna = this.dataset.columna;',
        '                window.open(',
        '                    `index.php?imprimir=1&tipo=pasaje_reserva`',
        '                    + `&dueno=${encodeURIComponent(nd)}`',
        '                    + `&viaje=${encodeURIComponent(nv)}`',
        '                    + `&micro=${encodeURIComponent(nm)}`',
        '                    + `&fila=${fila}&columna=${columna}`,',
        '                    \'_blank\'',
        '                );',
        '            }',
    ],
    'reemplazar' => [
        '            } else {',
        '                const nd = this.dataset.dueno;',
        '                const nv = this.dataset.viaje;',
        '                const nm = this.dataset.micro;',
        '                const fila = this.dataset.fila;',
        '                const columna = this.dataset.columna;',
        '                const piso = this.dataset.piso;',
        '                let url = `index.php?imprimir=1&tipo=pasaje_reserva`',
        '                    + `&dueno=${encodeURIComponent(nd)}`',
        '                    + `&viaje=${encodeURIComponent(nv)}`',
        '                    + `&micro=${encodeURIComponent(nm)}`',
        '                    + `&fila=${fila}&columna=${columna}`;',
        '                if (piso !== null && piso !== undefined && piso !== \'\') {',
        '                    url += `&piso=${encodeURIComponent(piso)}`;',
        '                }',
        '                window.open(url, \'_blank\');',
        '            }',
    ],
],

// ============================================================
// 13. viajes-asientos.js — _confirmar_cambiar_asiento:
//     snapshot con destino_piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => '_confirmar_cambiar_asiento: snapshot con destino_piso',
    'buscar' => [
        '            const snap = {',
        '                estado: d.origen_estado,',
        '                dueno: d.dueno,',
        '                viaje: d.viaje,',
        '                micro: d.micro,',
        '                destino_fila: d.destino.fila,',
        '                destino_columna: d.destino.columna,',
        '                destino_numero: d.destino.numero,',
        '                pasajero_dni: d.origen_pasajero_dni',
        '            };',
    ],
    'reemplazar' => [
        '            const snap = {',
        '                estado: d.origen_estado,',
        '                dueno: d.dueno,',
        '                viaje: d.viaje,',
        '                micro: d.micro,',
        '                destino_fila: d.destino.fila,',
        '                destino_columna: d.destino.columna,',
        '                destino_numero: d.destino.numero,',
        '                destino_piso: d.destino.piso || \'\',',
        '                pasajero_dni: d.origen_pasajero_dni',
        '            };',
    ],
],

// ============================================================
// 14. viajes-asientos.js — _confirmar_cambiar_asiento:
//     pasar destino_piso a la impresión
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => '_confirmar_cambiar_asiento: pasar destino_piso',
    'buscar' => [
        '            } else if (snap.estado === \'reservado\') {',
        '                if (typeof mostrar_modal_chico_impresion_reserva === \'function\') {',
        '                    mostrar_modal_chico_impresion_reserva(',
        '                        snap.dueno,',
        '                        snap.viaje,',
        '                        snap.micro,',
        '                        snap.destino_fila,',
        '                        snap.destino_columna,',
        '                        snap.destino_numero',
        '                    );',
        '                }',
        '            }',
    ],
    'reemplazar' => [
        '            } else if (snap.estado === \'reservado\') {',
        '                if (typeof mostrar_modal_chico_impresion_reserva === \'function\') {',
        '                    mostrar_modal_chico_impresion_reserva(',
        '                        snap.dueno,',
        '                        snap.viaje,',
        '                        snap.micro,',
        '                        snap.destino_fila,',
        '                        snap.destino_columna,',
        '                        snap.destino_numero,',
        '                        snap.destino_piso',
        '                    );',
        '                }',
        '            }',
    ],
],

// ============================================================
// 15. index.php — pasaje_reserva: pasar $_GET['piso']
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'pasaje_reserva: pasar piso desde el GET',
    'buscar' => [
        '    if ($tipo === \'pasaje_reserva\') {',
        '        imprimir_pasaje_reserva(',
        '            $_GET[\'dueno\'] ?? \'\',',
        '            $_GET[\'viaje\'] ?? \'\',',
        '            $_GET[\'micro\'] ?? \'\',',
        '            $_GET[\'fila\'] ?? \'\',',
        '            $_GET[\'columna\'] ?? \'\'',
        '        );',
        '        exit;',
        '    }',
    ],
    'reemplazar' => [
        '    if ($tipo === \'pasaje_reserva\') {',
        '        imprimir_pasaje_reserva(',
        '            $_GET[\'dueno\'] ?? \'\',',
        '            $_GET[\'viaje\'] ?? \'\',',
        '            $_GET[\'micro\'] ?? \'\',',
        '            $_GET[\'fila\'] ?? \'\',',
        '            $_GET[\'columna\'] ?? \'\',',
        '            $_GET[\'piso\'] ?? null',
        '        );',
        '        exit;',
        '    }',
    ],
],

// ============================================================
// 16. plan_actual.md — registrar la Tanda 4
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: Tanda 4 (piso en flujos que faltan)',
    'buscar' => [
        '- **Migración de pasajeros entre micros — Tanda 1',
        '  (v1.5piloto.82e).** Nueva función',
    ],
    'reemplazar' => [
        '- **Piso en los flujos que faltaban (v1.5piloto.82i).**',
        '  Cierre de los pendientes de la Tanda del croquis (v82h).',
        '  `formatear_venta_completa` ahora incluye `piso` en cada',
        '  asiento (precalculando un mapa `id_asiento → piso` una',
        '  sola vez por venta). `pasajeros.js` pasa `asientoInfo.piso`',
        '  a `abrir_modal_cambiar_asiento`. `imprimir_pasaje_reserva`',
        '  acepta `?string $piso` y matchea por `(piso, fila, columna)`;',
        '  `index.php` lo lee del GET. `imprimir_croquis_micro` usa',
        '  clave `piso-fila-columna` en el mapa de pasajeros, así el',
        '  croquis impreso del micro de 2 pisos ya no pierde los',
        '  pasajeros de las filas 1-3 del piso 2. `viajes-asientos.js`',
        '  pasa `piso` a la impresión desde reservar/asignar/cambiar.',
        '  **Sin pendientes del bug de piso.**',
        '- **Migración de pasajeros entre micros — Tanda 1',
        '  (v1.5piloto.82e).** Nueva función',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: versión del cierre',
    'buscar' => [
        '**Última actualización:** v1.5piloto.82h (fix del croquis',
        'multi-piso + selección por piso. Ver §2 y §4.5).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.82i (piso en los',
        'flujos que faltaban: cambiar de asiento desde Clientes,',
        'impresión de pasaje_reserva y croquis impreso del micro.',
        'Ver §2 y §4.5).',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
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
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
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
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
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