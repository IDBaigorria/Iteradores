<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73h: limpieza de contenido dinámico al cambiar de sesión.
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
    // aplicacion.js — bump y función de limpieza
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73g',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73h',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar funcion _limpiar_contenido_dinamico',
        'buscar' => [
            'document.getElementById(\'cerrar_modal_apilado\')?.addEventListener(\'click\', cerrar_modal_apilado);',
            'document.getElementById(\'modal_apilado\')?.addEventListener(\'click\', function(e) {',
            '    if (e.target === this) cerrar_modal_apilado();',
            '});',
        ],
        'reemplazar' => [
            'document.getElementById(\'cerrar_modal_apilado\')?.addEventListener(\'click\', cerrar_modal_apilado);',
            'document.getElementById(\'modal_apilado\')?.addEventListener(\'click\', function(e) {',
            '    if (e.target === this) cerrar_modal_apilado();',
            '});',
            '',
            '// ============================================================',
            '// ====== LIMPIEZA DE CONTENIDO DINAMICO ======================',
            '// ============================================================',
            '',
            '/**',
            ' * Limpia todo el contenido dinámico (tablas, listas, selectores)',
            ' * que se llenó en la sesión anterior. Se llama al salir y al',
            ' * ingresar, para no exponer datos de un rol a otro cuando cambia',
            ' * el usuario logueado.',
            ' *',
            ' * No toca el HTML estático (headers de secciones, botones, etc.),',
            ' * solo los contenedores que se llenan desde el backend.',
            ' */',
            'function _limpiar_contenido_dinamico() {',
            '    const ids_a_limpiar = [',
            '        // Administrador',
            '        \'tabla_usuarios_admin\',',
            '        \'tabla_sesiones_admin\',',
            '        \'selector_dueno_admin\',',
            '        // Puntos de venta',
            '        \'tabla_terminales_dueno\',',
            '        \'tabla_sesiones_terminales\',',
            '        // Empresas / Micros',
            '        \'selector_dueno_micros\',',
            '        \'selector_empresa_micros\',',
            '        \'selector_vehiculo_micros\',',
            '        \'lista_micros_viaje\',',
            '        \'lista_terminales_viaje\',',
            '        // Viajes',
            '        \'selector_dueno_viajes\',',
            '        \'lista_viajes\',',
            '        // Vendidos',
            '        \'selector_dueno_vendidos\',',
            '        \'lista_ventas\',',
            '        \'saldos_vendidos\',',
            '        \'chips_filtros_activos_vendidos\',',
            '        \'contador_ventas_vendidos\',',
            '        \'selector_viaje_vendido\',',
            '        \'filtro_vendedor\',',
            '        // Rendiciones',
            '        \'selector_dueno_rendiciones\',',
            '        \'rendicion_filtro_terminal\',',
            '        \'tabla_rendiciones_container\',',
            '        \'resumen_rendiciones\',',
            '        \'chips_filtros_activos_rendiciones\',',
            '        \'aviso_rendiciones_desactualizadas\',',
            '        // Liquidaciones',
            '        \'selector_dueno_liquidaciones\',',
            '        \'tabla_liquidaciones_container\',',
            '        \'resumen_liquidaciones\',',
            '        \'chips_filtros_activos_liquidaciones\',',
            '        // Pasajeros',
            '        \'selector_dueno_pasajeros\',',
            '        \'tabla_pasajeros\',',
            '    ];',
            '    ids_a_limpiar.forEach(id => {',
            '        const el = document.getElementById(id);',
            '        if (el) el.innerHTML = \'\';',
            '    });',
            '',
            '    // Los selectores cuyo primer option es estático también se',
            '    // resetean a un estado neutro para que no queden con datos.',
            '    const selectores_con_default = {',
            '        \'selector_dueno_vendidos\': \'<option value="">Seleccione dueño...</option>\',',
            '        \'selector_dueno_rendiciones\': \'<option value="">Seleccione dueño...</option>\',',
            '        \'selector_dueno_liquidaciones\': \'<option value="">Seleccione dueño...</option>\',',
            '        \'rendicion_filtro_terminal\': \'<option value="">Todas</option>\',',
            '        \'selector_viaje_vendido\': \'<option value="todos">Todos</option>\',',
            '        \'filtro_vendedor\': \'<option value="Todos">Todos</option>\',',
            '    };',
            '    Object.keys(selectores_con_default).forEach(id => {',
            '        const el = document.getElementById(id);',
            '        if (el) el.innerHTML = selectores_con_default[id];',
            '    });',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: llamar limpieza en _aplicar_login_exitoso',
        'buscar' => [
            'function _aplicar_login_exitoso(usuario) {',
            '    usuario_actual = usuario;',
            '    localStorage.setItem(\'token_sesion\', usuario.token_sesion);',
            '    localStorage.setItem(\'usuario_actual\', JSON.stringify(usuario));',
            '    $("#pantalla_login").classList.add("hidden");',
            '    $("#aplicacion").classList.remove("hidden");',
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '}',
        ],
        'reemplazar' => [
            'function _aplicar_login_exitoso(usuario) {',
            '    // Limpieza defensiva: si quedó contenido del usuario anterior',
            '    // (por ejemplo, al cambiar de sesión sin recargar la página),',
            '    // se borra antes de configurar la nueva UI.',
            '    _limpiar_contenido_dinamico();',
            '',
            '    usuario_actual = usuario;',
            '    localStorage.setItem(\'token_sesion\', usuario.token_sesion);',
            '    localStorage.setItem(\'usuario_actual\', JSON.stringify(usuario));',
            '    $("#pantalla_login").classList.add("hidden");',
            '    $("#aplicacion").classList.remove("hidden");',
            '    $("#nombre_usuario_actual").textContent = usuario.nombre_usuario;',
            '    $("#nivel_usuario_actual").textContent = usuario.nivel;',
            '    configurar_pestanas_segun_nivel(usuario.nivel);',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: limpiar contenido al salir',
        'buscar' => [
            '    localStorage.removeItem(\'token_sesion\');',
            '    localStorage.removeItem(\'usuario_actual\');',
            '    usuario_actual = null;',
            '    $("#aplicacion").classList.add("hidden");',
            '    $("#pantalla_login").classList.remove("hidden");',
            '    $("#codigo_acceso").value = "";',
            '    $("#login_usuario").value = "";',
            '    $("#login_contrasena").value = "";',
            '}',
        ],
        'reemplazar' => [
            '    localStorage.removeItem(\'token_sesion\');',
            '    localStorage.removeItem(\'usuario_actual\');',
            '    usuario_actual = null;',
            '',
            '    // Borrar todo el contenido dinámico para no exponer datos del',
            '    // usuario que se acaba de ir.',
            '    _limpiar_contenido_dinamico();',
            '',
            '    $("#aplicacion").classList.add("hidden");',
            '    $("#pantalla_login").classList.remove("hidden");',
            '    $("#codigo_acceso").value = "";',
            '    $("#login_usuario").value = "";',
            '    $("#login_contrasena").value = "";',
            '}',
        ],
    ],

    // ==========================================================
    // admin.js — fix del fetch de usuarios en cargar_datos_admin
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73g',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73h',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: solo admin hace fetch de todos los usuarios',
        'buscar' => [
            'async function cargar_datos_admin() {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '',
            '    // Si es soporte, cargar el selector de dueños.',
            '    if (es_soporte) {',
            '        await _cargar_selector_dueno_admin();',
            '    } else {',
            '        // Si es admin, ocultar el selector.',
            '        const panel_sel = document.getElementById(\'panel_selector_dueno_admin\');',
            '        if (panel_sel) panel_sel.style.display = \'none\';',
            '    }',
            '',
            '    // Cargar usuarios',
            '    let respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })',
            '    });',
            '    let datos = await respuesta.json();',
            '    if (datos.exito) {',
            '        _renderizar_tabla_usuarios(datos.usuarios);',
            '    }',
            '',
            '    // Cargar sesiones',
            '    respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_sesiones" })',
            '    });',
            '    datos = await respuesta.json();',
        ],
        'reemplazar' => [
            'async function cargar_datos_admin() {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '',
            '    // Si es soporte, cargar el selector de dueños. El selector, al',
            '    // inicializarse, ya trae la lista filtrada del primer dueño',
            '    // asignado y la renderiza. NO hay que volver a pedir todos los',
            '    // usuarios porque eso pisaría el filtro con la lista completa.',
            '    if (es_soporte) {',
            '        await _cargar_selector_dueno_admin();',
            '    } else {',
            '        // Si es admin, ocultar el selector y cargar todos los usuarios.',
            '        const panel_sel = document.getElementById(\'panel_selector_dueno_admin\');',
            '        if (panel_sel) panel_sel.style.display = \'none\';',
            '',
            '        const respuesta_usuarios = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "administrador/listar_usuarios" })',
            '        });',
            '        const datos_usuarios = await respuesta_usuarios.json();',
            '        if (datos_usuarios.exito) {',
            '            _renderizar_tabla_usuarios(datos_usuarios.usuarios);',
            '        }',
            '    }',
            '',
            '    // Cargar sesiones',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_sesiones" })',
            '    });',
            '    const datos = await respuesta.json();',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html — bumps
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de aplicacion.js',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.73g"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.73h"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de admin.js',
        'buscar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73g"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73h"></script>',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — bump y bug anotado
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73g (alta de',
            'usuarios en modal, reutilizando el mismo patrón que la edición).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73h (limpieza',
            'de contenido dinámico al cambiar de sesión).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73h al historial',
        'buscar' => [
            '- **v73g**: el alta de usuarios también pasa a modal, reutilizando',
            '  el mismo patrón. Los formularios embebidos en el HTML quedan sin',
            '  uso (se limpian en una próxima tanda).',
        ],
        'reemplazar' => [
            '- **v73g**: el alta de usuarios también pasa a modal, reutilizando',
            '  el mismo patrón. Los formularios embebidos en el HTML quedan sin',
            '  uso (se limpian en una próxima tanda).',
            '- **v73h**: fix de fuga de datos al cambiar de sesión. Se limpia',
            '  todo el contenido dinámico al salir y al ingresar. Además, el',
            '  soporte ya no recibe la lista completa de usuarios: la tabla se',
            '  llena solo con lo que dejó el selector de dueños.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: anotar bug de sesiones',
        'buscar' => [
            '**Decisiones abiertas / temas pendientes sin consensuar:**',
            '',
            '- **Rehash automático**: lo mencionamos como parte de la Tanda C pero',
            '  quedó fuera de la implementación. No se agregó el chequeo',
            '  `password_needs_rehash`. Se puede agregar en un bloque chico dentro',
            '  de `_registrar_login_exitoso`.',
        ],
        'reemplazar' => [
            '**Decisiones abiertas / temas pendientes sin consensuar:**',
            '',
            '- **Rehash automático**: lo mencionamos como parte de la Tanda C pero',
            '  quedó fuera de la implementación. No se agregó el chequeo',
            '  `password_needs_rehash`. Se puede agregar en un bloque chico dentro',
            '  de `_registrar_login_exitoso`.',
            '- **Bug de sesiones para el rol soporte** (detectado en v73h):',
            '  `cargar_datos_admin` llama a `administrador/listar_sesiones` sin',
            '  filtrar por solicitante. El backend devuelve TODAS las sesiones',
            '  del sistema, así que un soporte ve las sesiones de usuarios que',
            '  no son sus dueños. Fix pendiente: que `listar_sesiones`',
            '  (en `Sesion.php`) reciba el solicitante y, si es soporte, devuelva',
            '  solo las sesiones de sus dueños asignados y las terminales de',
            '  esos dueños. Se puede hacer en una sub-tanda aparte (toca solo',
            '  el backend de sesiones).',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (limpieza al cambiar de sesion) ===\n\n";

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
        $permitir_multiples = !empty($cambio['permitir_multiples']);

        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1 && !$permitir_multiples) {
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