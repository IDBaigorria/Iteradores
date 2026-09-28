<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * Tanda A (continuación) — v1.5piloto.68b: mismos cambios de credenciales
 * en la pestaña Puntos de venta (terminales.js).
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

    // ==========================================================
    // terminales.js
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.15',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.68b',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: tabla muestra codigo_asignado',
        'buscar' => [
            '            <td>${terminal.codigo_acceso || "—"}</td>',
        ],
        'reemplazar' => [
            '            <td>${terminal.codigo_asignado ? "•••••" : "—"}</td>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: validacion de nuevo terminal acepta uno de los dos',
        'buscar' => [
            '    if (!datos_terminal.nombre_usuario || !datos_terminal.codigo_acceso) {',
            '        mostrar_aviso("Nombre de usuario y código de acceso son obligatorios", \'error\');',
            '        return;',
            '    }',
        ],
        'reemplazar' => [
            '    if (!datos_terminal.nombre_usuario) {',
            '        mostrar_aviso("El nombre de usuario es obligatorio", \'error\');',
            '        return;',
            '    }',
            '    if (!datos_terminal.codigo_acceso && !datos_terminal.contrasena) {',
            '        mostrar_aviso("Debe asignar al menos un código de acceso o una contraseña", \'error\');',
            '        return;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: post-fetch de nuevo terminal muestra codigo_asignado',
        'buscar' => [
            '    const datos = await respuesta.json();',
            '    if (datos.exito) {',
            '        mostrar_aviso("Punto de venta agregado correctamente", \'exito\');',
            '        $("#formulario_nueva_terminal").classList.add("hidden");',
            '        ["nuevo_terminal_nombre_usuario","nuevo_terminal_contrasena","nuevo_terminal_nombre_real","nuevo_terminal_email","nuevo_terminal_codigo_acceso","nuevo_terminal_banco_nombre","nuevo_terminal_banco_cuenta"].forEach(id => $("#"+id).value="");',
            '        cargar_datos_terminales();',
            '    } else {',
            '        mostrar_aviso(datos.error || "Error al agregar punto de venta", \'error\');',
            '    }',
        ],
        'reemplazar' => [
            '    const datos = await respuesta.json();',
            '    if (datos.exito) {',
            '        if (datos.codigo_asignado) {',
            '            alert("Código de acceso: " + datos.codigo_asignado + "\\n\\nGuardalo, no se mostrará de nuevo.");',
            '        }',
            '        mostrar_aviso("Punto de venta agregado correctamente", \'exito\');',
            '        $("#formulario_nueva_terminal").classList.add("hidden");',
            '        ["nuevo_terminal_nombre_usuario","nuevo_terminal_contrasena","nuevo_terminal_nombre_real","nuevo_terminal_email","nuevo_terminal_codigo_acceso","nuevo_terminal_banco_nombre","nuevo_terminal_banco_cuenta"].forEach(id => $("#"+id).value="");',
            '        cargar_datos_terminales();',
            '    } else {',
            '        mostrar_aviso(datos.error || "Error al agregar punto de venta", \'error\');',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: edicion no prellena el codigo',
        'buscar' => [
            '    const valor_codigo = terminal.codigo_acceso || \'\';',
        ],
        'reemplazar' => [
            '    const valor_codigo = \'\';',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: input de codigo con placeholder en edicion',
        'buscar' => [
            '        <td><input type="text" id="editar_terminal_codigo" value="${valor_codigo}"></td>',
        ],
        'reemplazar' => [
            '        <td><input type="text" id="editar_terminal_codigo" value="" placeholder="Dejar vacío para no cambiar"></td>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: post-fetch de edicion muestra codigo_asignado',
        'buscar' => [
            '    const resultado = await respuesta.json();',
            '    if (resultado.exito) {',
            '        mostrar_aviso("Punto de venta actualizado correctamente", \'exito\');',
            '        cargar_datos_terminales();',
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al actualizar", \'error\');',
            '    }',
        ],
        'reemplazar' => [
            '    const resultado = await respuesta.json();',
            '    if (resultado.exito) {',
            '        if (resultado.codigo_asignado) {',
            '            alert("Nuevo código de acceso: " + resultado.codigo_asignado + "\\n\\nGuardalo, no se mostrará de nuevo.");',
            '        }',
            '        mostrar_aviso("Punto de venta actualizado correctamente", \'exito\');',
            '        cargar_datos_terminales();',
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al actualizar", \'error\');',
            '    }',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'aplicacion_GET.html: bump ?v= de terminales.js',
        'buscar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.68b"></script>',
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