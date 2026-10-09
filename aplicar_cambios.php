<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77 (fix del bug 1: atadura vs DNI nuevo).
 *   - Aplicacion/ventas.js: en _buscar_pasajero_por_dni y
 *     _buscar_comprador_por_dni, no limpiar los campos si hay
 *     atadura activa. Los datos vienen del otro formulario.
 *   - aplicacion_GET.html: bump ?v= de ventas.js.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // ventas.js — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump @version a 1.5piloto.77',
        'buscar' => [
            ' * @version 1.5piloto.76z',
            ' */',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.77',
            ' */',
        ],
    ],

    // ============================================================
    // ventas.js — _buscar_pasajero_por_dni: no limpiar si hay atadura
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: pasajero no limpia si hay atadura',
        'buscar' => [
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_pasajero(index, datos.pasajero);',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: true,',
            '                fecha_modificacion: datos.pasajero.fecha_ultima_modificacion || \'\'',
            '            };',
            '        } else {',
            '            // Limpiar los campos no-DNI antes de habilitar. Si el',
            '            // usuario habia autocompletado un DNI previo y ahora',
            '            // corrige por uno no registrado, los datos viejos no',
            '            // deben quedar ni propagarse por ligadura.',
            '            _limpiar_campos_pasajero(index);',
            '            _habilitar_campos_pasajero(index, true);',
            '            _mostrar_aviso_en_formulario(index, \'DNI no registrado. Complete los datos.\', \'gris\');',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            '                fecha_modificacion: \'\'',
            '            };',
            '        }',
        ],
        'reemplazar' => [
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_pasajero(index, datos.pasajero);',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: true,',
            '                fecha_modificacion: datos.pasajero.fecha_ultima_modificacion || \'\'',
            '            };',
            '        } else {',
            '            // Fix v77: si hay atadura activa apuntando a este',
            '            // pasajero, NO limpiar los campos. Los datos del',
            '            // pasajero vinieron del comprador y siguen siendo',
            '            // válidos, aunque el DNI no esté registrado todavía.',
            '            // Solo se limpia cuando no hay atadura (caso "corregí',
            '            // el DNI por uno nuevo y quiero empezar de cero").',
            '            const hay_atadura = window.atadura_actual',
            '                && window.atadura_actual.indice_pasajero === index;',
            '            if (!hay_atadura) {',
            '                _limpiar_campos_pasajero(index);',
            '                _mostrar_aviso_en_formulario(index, \'DNI no registrado. Complete los datos.\', \'gris\');',
            '            } else {',
            '                _mostrar_aviso_en_formulario(index, \'DNI no registrado. Datos copiados del comprador.\', \'gris\');',
            '            }',
            '            _habilitar_campos_pasajero(index, true);',
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            '                fecha_modificacion: \'\'',
            '            };',
            '        }',
        ],
    ],

    // ============================================================
    // ventas.js — _buscar_comprador_por_dni: no limpiar si hay atadura
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: comprador no limpia si hay atadura',
        'buscar' => [
            '        } else {',
            '            // Si antes se habian autocompletado datos para otro DNI',
            '            // y ahora el DNI cambio por uno no registrado, limpiar',
            '            // los campos del comprador para no arrastrar los datos',
            '            // del DNI anterior.',
            '            if (window.comprador_dni_con_datos && window.comprador_dni_con_datos !== dni_norm) {',
            '                _limpiar_campos_comprador();',
            '                window.comprador_dni_con_datos = null;',
            '            }',
            '            _mostrar_aviso_comprador(\'DNI no registrado. Complete los datos.\', \'gris\');',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando comprador:", e);',
            '        _limpiar_aviso_comprador();',
            '    }',
        ],
        'reemplazar' => [
            '        } else {',
            '            // Fix v77: si hay atadura activa, los datos del',
            '            // comprador vinieron del pasajero. No limpiar.',
            '            if (window.atadura_actual) {',
            '                _mostrar_aviso_comprador(\'DNI no registrado. Datos copiados del pasajero.\', \'gris\');',
            '            } else {',
            '                // Si antes se habian autocompletado datos para otro DNI',
            '                // y ahora el DNI cambio por uno no registrado, limpiar',
            '                // los campos del comprador para no arrastrar los datos',
            '                // del DNI anterior.',
            '                if (window.comprador_dni_con_datos && window.comprador_dni_con_datos !== dni_norm) {',
            '                    _limpiar_campos_comprador();',
            '                    window.comprador_dni_con_datos = null;',
            '                }',
            '                _mostrar_aviso_comprador(\'DNI no registrado. Complete los datos.\', \'gris\');',
            '            }',
            '        }',
            '    } catch (e) {',
            '        console.error("Error buscando comprador:", e);',
            '        _limpiar_aviso_comprador();',
            '    }',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump ventas.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET: bump ?v= de ventas.js',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.76z"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.77"></script>',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76z',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77',
        ],
    ],

    // ============================================================
    // prompts/plan_actual.md — registro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77',
        'buscar' => [
            '**Tanda actual:** v76z (fixes urgentes en el frontend del',
            'formulario de venta). Dos bugs detectados en producción:',
            '',
            '1. **Atadura comprador-pasajero.** El badge "Vinculado"',
            '   aparecía un instante y desaparecía. Causa: el listener',
            '   del DNI disparaba varios fetches en paralelo, y uno',
            '   viejo pisaba el estado nuevo. Fix: chequear que el DNI',
            '   del input no haya cambiado cuando vuelve el fetch; si',
            '   cambió, descartar la respuesta. Además, activar la',
            '   atadura síncronamente antes del fetch.',
            '2. **Modal post venta.** No aparecía. Causa:',
            '   `confirmar_venta_modal` hacía dos `await` antes de',
            '   llamar `mostrar_opciones_impresion`. Si cualquiera',
            '   fallaba, el flujo saltaba al `catch` y el modal nunca',
            '   se mostraba. Fix: mostrar el modal primero y envolver',
            '   los refrescos en try/catch individual.',
            '',
            '**Bug detectado en v76x (sigue pendiente):** las funciones',
            '`detectar_*` devolvían `true` por vacío. Se arregla en v76y.',
            'Testigos mal puestos se limpian con "Re-detectar".',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77 (fix del bug 1 de la atadura:',
            'DNI nuevo + datos ya cargados en el otro lado).',
            '',
            '**Bug 1 (atadura) — diagnóstico completo:**',
            'El usuario carga el comprador con un DNI nuevo (no existe',
            'en el grafo), llena todos los datos. Después, en el form',
            'del pasajero, ingresa el mismo DNI. La atadura se activa',
            '(por el fix v76z, corre antes del fetch) y copia los',
            'datos del comprador al pasajero. Se ve "Vinculado". Pero',
            'al volver el fetch, el backend responde "no existe" y',
            '`_buscar_pasajero_por_dni` limpiaba los campos del pasajero,',
            'borrando lo que la atadura acababa de copiar.',
            '',
            '**Fix v77:** si hay atadura activa apuntando a este',
            'formulario, no limpiar los campos. Los datos vinieron del',
            'otro lado y siguen siendo válidos aunque el DNI no esté',
            'registrado. Solo se limpia cuando no hay atadura (caso',
            '"corregí el DNI por uno nuevo y quiero empezar de cero").',
            'Mismo criterio aplicado a `_buscar_comprador_por_dni`.',
            '',
            '**Bug 2 (modal post venta) — resuelto en v76z:**',
            '`confirmar_venta_modal` ahora muestra el modal ANTES de',
            'los refrescos, y envuelve los refrescos en try/catch',
            'individuales. Un fallo en el refresco ya no bloquea el',
            'modal.',
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