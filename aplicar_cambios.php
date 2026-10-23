<?php
/**
 * Aplicador — Tanda 2 (frontend): migrar pasajeros entre micros.
 *
 * Plan: prompts/plan_migrar_micro.md §4 + §8.
 *
 * Cambios:
 * 1. viajes-micros.js: botón "Migrar pasajeros a..." en la tarjeta
 *    del micro (solo si tiene asientos ocupados y el usuario no es
 *    terminal).
 * 2. viajes-micros.js: nueva función abrir_modal_migrar_pasajeros
 *    (modal apilado, select de destinos vacíos, aviso de layouts
 *    distintos, confirm() nativo, POST, refresco).
 * 3. plan_actual.md: Tanda 2 cerrada.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.82f';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. viajes-micros.js: agregar botón "Migrar pasajeros a..." en
//    renderizar_micros_viaje
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
    'descripcion' => 'Render de micro: agregar botón migrar pasajeros',
    'buscar' => [
        '                <button class="btn btn-editar-monto" data-micro="${micro.nombre_micro}" data-monto="${micro.monto ?? \'0\'}">Editar monto</button>',
        '                <button class="btn btn-eliminar-micro" data-micro="${micro.nombre_micro}">Quitar</button>',
    ],
    'reemplazar' => [
        '                <button class="btn btn-editar-monto" data-micro="${micro.nombre_micro}" data-monto="${micro.monto ?? \'0\'}">Editar monto</button>',
        '                ${(parseInt(micro.vendidos || 0) + parseInt(micro.reservados || 0)) > 0 ? `<button class="btn btn-migrar-pasajeros" data-micro="${micro.nombre_micro}">Migrar pasajeros a...</button>` : \'\'}',
        '                <button class="btn btn-eliminar-micro" data-micro="${micro.nombre_micro}">Quitar</button>',
    ],
],

// ============================================================
// 2. viajes-micros.js: listener del botón (antes del de eliminar)
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
    'descripcion' => 'Render de micro: listener del botón migrar',
    'buscar' => [
        '        const btnQuitar = div.querySelector(\'.btn-eliminar-micro\');',
        '        if (btnQuitar) btnQuitar.addEventListener(\'click\', () => eliminar_micro(micro.nombre_micro));',
    ],
    'reemplazar' => [
        '        const btnMigrar = div.querySelector(\'.btn-migrar-pasajeros\');',
        '        if (btnMigrar) btnMigrar.addEventListener(\'click\', () => abrir_modal_migrar_pasajeros(micro.nombre_micro));',
        '        const btnQuitar = div.querySelector(\'.btn-eliminar-micro\');',
        '        if (btnQuitar) btnQuitar.addEventListener(\'click\', () => eliminar_micro(micro.nombre_micro));',
    ],
],

// ============================================================
// 3. viajes-micros.js: nueva función abrir_modal_migrar_pasajeros
//    al final del archivo
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
    'descripcion' => 'Nueva función abrir_modal_migrar_pasajeros',
    'buscar' => [
        '        const resultado = await resp.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Opciones guardadas", \'exito\');',
        '            cerrar_modal_apilado();',
        '        } else {',
        '            mostrar_aviso(resultado.error || "Error al guardar opciones", \'error\');',
        '        }',
        '    });',
        '}',
    ],
    'reemplazar' => [
        '        const resultado = await resp.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Opciones guardadas", \'exito\');',
        '            cerrar_modal_apilado();',
        '        } else {',
        '            mostrar_aviso(resultado.error || "Error al guardar opciones", \'error\');',
        '        }',
        '    });',
        '}',
        '',
        '/**',
        ' * Abre el modal apilado para migrar los pasajeros (asientos',
        ' * vendidos y reservados) de un micro a otro vacío del mismo',
        ' * viaje.',
        ' *',
        ' * Se construye sobre el modal del detalle del viaje (que es',
        ' * genérico), por eso usa `abrir_modal_apilado`. El origen se',
        ' * pasa como parámetro. Los candidatos a destino son los otros',
        ' * micros cuyos contadores estén todos en cero.',
        ' *',
        ' * El backend valida todo igual (destino vacío, venta en curso,',
        ' * números coincidentes, etc.), así que si el frontend tiene',
        ' * contadores viejos no hay problema: el backend es la fuente',
        ' * de verdad.',
        ' *',
        ' * @param {string} nombre_micro_origen',
        ' * @since 1.5piloto.82f',
        ' */',
        'async function abrir_modal_migrar_pasajeros(nombre_micro_origen) {',
        '    if (!viaje_seleccionado) return;',
        '    const micros = viaje_seleccionado.micros || [];',
        '    const micro_origen = micros.find(m => m.nombre_micro === nombre_micro_origen);',
        '    if (!micro_origen) {',
        '        mostrar_aviso("Micro origen no encontrado", \'error\');',
        '        return;',
        '    }',
        '',
        '    // Candidatos a destino: otros micros cuyos contadores estén',
        '    // todos en cero.',
        '    const candidatos = micros.filter(m => {',
        '        if (m.nombre_micro === nombre_micro_origen) return false;',
        '        const v = parseInt(m.vendidos || 0);',
        '        const r = parseInt(m.reservados || 0);',
        '        const s = parseInt(m.seleccionados || 0);',
        '        return v === 0 && r === 0 && s === 0;',
        '    });',
        '',
        '    const ocupados = parseInt(micro_origen.vendidos || 0) + parseInt(micro_origen.reservados || 0);',
        '    const nombre_visible_origen = `${micro_origen.nombre_empresa || micro_origen.empresa || \'\'} - ${micro_origen.nombre_vehiculo || micro_origen.patente || micro_origen.nombre_micro}`;',
        '',
        '    let html;',
        '    if (candidatos.length === 0) {',
        '        html = `',
        '            <p>No hay otros micros vacíos en este viaje. Agregá uno nuevo primero.</p>',
        '            <div class="actions" style="margin-top:12px;">',
        '                <button class="btn" id="migrar_cancelar">Cerrar</button>',
        '            </div>',
        '        `;',
        '    } else {',
        '        const opciones = candidatos.map(m => {',
        '            const nv = `${m.nombre_empresa || m.empresa || \'\'} - ${m.nombre_vehiculo || m.patente || m.nombre_micro}`;',
        '            return `<option value="${m.nombre_micro}">${nv}</option>`;',
        '        }).join(\'\');',
        '',
        '        html = `',
        '            <p>Vas a mover <b>${ocupados} asiento${ocupados === 1 ? \'\' : \'s\'}</b> del micro <b>${nombre_visible_origen}</b> a otro micro vacío del mismo viaje.</p>',
        '            <div class="field">',
        '                <label for="migrar_select_destino">Micro destino:</label>',
        '                <select id="migrar_select_destino">${opciones}</select>',
        '            </div>',
        '            <div id="migrar_aviso_layout" class="small muted" style="display:none; margin-top:10px; padding:8px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px;">',
        '                ⚠️ El micro destino tiene un layout distinto al origen. Los números de asiento se mantienen, pero la disposición física puede cambiar.',
        '            </div>',
        '            <div id="migrar_aviso_error" class="small" style="display:none; margin-top:10px; color:#c00;"></div>',
        '            <div class="actions" style="margin-top:12px;">',
        '                <button class="btn primary" id="migrar_confirmar">Confirmar</button>',
        '                <button class="btn" id="migrar_cancelar">Cancelar</button>',
        '            </div>',
        '        `;',
        '    }',
        '',
        '    abrir_modal_apilado(`Migrar pasajeros de ${nombre_visible_origen}`, html);',
        '    const contenedor = document.getElementById(\'modal_apilado_contenido\');',
        '    if (!contenedor) return;',
        '',
        '    if (candidatos.length === 0) {',
        '        contenedor.querySelector(\'#migrar_cancelar\').addEventListener(\'click\', cerrar_modal_apilado);',
        '        return;',
        '    }',
        '',
        '    const nombre_dueno = obtener_nombre_dueno_actual();',
        '    let config_origen = null;',
        '    let config_destino_actual = null;',
        '    let destino_seleccionado = candidatos[0].nombre_micro;',
        '',
        '    const pedir_config = async (nombre_micro) => {',
        '        try {',
        '            const r = await fetch("index.php", {',
        '                method: "POST",',
        '                headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '                body: new URLSearchParams({',
        '                    accion: "viajes/obtener_micro",',
        '                    nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                    nombre_micro,',
        '                    nombre_dueno',
        '                })',
        '            });',
        '            const d = await r.json();',
        '            if (d.exito && d.micro) return d.micro.configuracion || null;',
        '        } catch (e) { /* silencio: es solo el aviso de layout */ }',
        '        return null;',
        '    };',
        '',
        '    const evaluar_layout = () => {',
        '        const aviso = contenedor.querySelector(\'#migrar_aviso_layout\');',
        '        if (!aviso) return;',
        '        if (!config_origen || !config_destino_actual) {',
        '            aviso.style.display = \'none\';',
        '            return;',
        '        }',
        '        const p_o = config_origen.pisos || [];',
        '        const p_d = config_destino_actual.pisos || [];',
        '        let distintos = p_o.length !== p_d.length;',
        '        if (!distintos) {',
        '            for (let i = 0; i < p_o.length; i++) {',
        '                if (p_o[i].filas !== p_d[i].filas || p_o[i].columnas !== p_d[i].columnas) {',
        '                    distintos = true;',
        '                    break;',
        '                }',
        '            }',
        '        }',
        '        aviso.style.display = distintos ? \'\' : \'none\';',
        '    };',
        '',
        '    // Pedir config del origen y del primer destino en serie.',
        '    config_origen = await pedir_config(nombre_micro_origen);',
        '    config_destino_actual = await pedir_config(destino_seleccionado);',
        '    evaluar_layout();',
        '',
        '    const select = contenedor.querySelector(\'#migrar_select_destino\');',
        '    select.addEventListener(\'change\', async () => {',
        '        destino_seleccionado = select.value;',
        '        config_destino_actual = await pedir_config(destino_seleccionado);',
        '        evaluar_layout();',
        '    });',
        '',
        '    contenedor.querySelector(\'#migrar_cancelar\').addEventListener(\'click\', cerrar_modal_apilado);',
        '',
        '    contenedor.querySelector(\'#migrar_confirmar\').addEventListener(\'click\', async () => {',
        '        const error_div = contenedor.querySelector(\'#migrar_aviso_error\');',
        '        error_div.style.display = \'none\';',
        '',
        '        const confirmado = confirm(',
        '            `Vas a mover ${ocupados} asiento${ocupados === 1 ? \'\' : \'s\'} del micro "${nombre_visible_origen}" al micro "${destino_seleccionado}". ¿Continuar?`',
        '        );',
        '        if (!confirmado) return;',
        '',
        '        const btn = contenedor.querySelector(\'#migrar_confirmar\');',
        '        btn.disabled = true;',
        '        btn.textContent = \'Migrando...\';',
        '',
        '        try {',
        '            const r = await fetch("index.php", {',
        '                method: "POST",',
        '                headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '                body: new URLSearchParams({',
        '                    accion: "viajes/migrar_pasajeros",',
        '                    nombre_solicitante: usuario_actual.nombre_usuario,',
        '                    nombre_dueno,',
        '                    nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                    nombre_micro_origen,',
        '                    nombre_micro_destino: destino_seleccionado',
        '                })',
        '            });',
        '            const res = await r.json();',
        '            if (!res.exito) {',
        '                error_div.textContent = res.error || \'Error al migrar\';',
        '                error_div.style.display = \'\';',
        '                btn.disabled = false;',
        '                btn.textContent = \'Confirmar\';',
        '                return;',
        '            }',
        '',
        '            cerrar_modal_apilado();',
        '            const n = res.migrados;',
        '            const mensaje = `${n} asiento${n === 1 ? \'\' : \'s\'} migrado${n === 1 ? \'\' : \'s\'}.`;',
        '            mostrar_aviso(mensaje + (res.origen_vacio ? \' El micro origen quedó vacío.\' : \'\'), \'exito\');',
        '',
        '            // Refresco del panel de pasaje:',
        '            //   - origen abierto: cerrarlo (quedó vacío).',
        '            //   - destino abierto: cerrarlo, refrescar contadores',
        '            //     y reabrirlo (el croquis cambió: asientos que',
        '            //     eran libres ahora están vendidos).',
        '            //   - ninguno: solo refrescar contadores.',
        '            if (micro_seleccionado === nombre_micro_origen) {',
        '                cerrar_pasaje_micro();',
        '                await refrescar_contadores_viaje_actual();',
        '            } else if (micro_seleccionado === destino_seleccionado) {',
        '                cerrar_pasaje_micro();',
        '                await refrescar_contadores_viaje_actual();',
        '                await seleccionar_micro_viaje(destino_seleccionado);',
        '            } else {',
        '                await refrescar_contadores_viaje_actual();',
        '            }',
        '        } catch (e) {',
        '            error_div.textContent = \'Error de conexión: \' + e.message;',
        '            error_div.style.display = \'\';',
        '            btn.disabled = false;',
        '            btn.textContent = \'Confirmar\';',
        '        }',
        '    });',
        '}',
    ],
],

// ============================================================
// 4. plan_actual.md: Tanda 2 cerrada
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: marcar Tanda 2 cerrada',
    'buscar' => [
        '- **Tanda 2 (frontend): pendiente.** Botón "Migrar pasajeros"',
        '  en la tarjeta del micro + modal con select de destino +',
        '  wire-up del POST + refresco del detalle del viaje.',
        '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
    ],
    'reemplazar' => [
        '- **Tanda 2 (frontend): cerrada en `1.5piloto.82f`.**',
        '  Botón "Migrar pasajeros a..." en la tarjeta del micro (solo',
        '  visible si el micro tiene asientos ocupados y el usuario',
        '  no es terminal) + modal apilado con select de micros',
        '  vacíos, aviso de layouts distintos, confirm() nativo y',
        '  refresco post-migración (cierra o refresca el panel de',
        '  pasaje según qué micro estaba abierto).',
        '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: actualizar versión del cierre',
    'buscar' => [
        '**Última actualización:** v1.5piloto.82e (Tanda 1 del arco de',
        'migración de pasajeros entre micros: backend. Ver §2 y §4.5).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.82f (Tanda 2 del arco de',
        'migración de pasajeros entre micros: frontend. Ver §2 y §4.5).',
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