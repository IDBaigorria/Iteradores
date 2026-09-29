<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73a: alta de usuarios soporte y asignación de dueños.
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
    // aplicacion_GET.html — agregar opción soporte al select
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: opcion soporte en el select de nivel',
        'buscar' => [
            '            <div class="field"><label>Nivel</label>',
            '              <select id="nuevo_nivel">',
            '                <option value="terminal">Terminal</option>',
            '                <option value="dueno" selected>Dueño</option>',
            '                <option value="admin">Administrador</option>',
            '              </select>',
            '            </div>',
        ],
        'reemplazar' => [
            '            <div class="field"><label>Nivel</label>',
            '              <select id="nuevo_nivel">',
            '                <option value="terminal">Terminal</option>',
            '                <option value="dueno" selected>Dueño</option>',
            '                <option value="admin">Administrador</option>',
            '                <option value="soporte">Soporte</option>',
            '              </select>',
            '            </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: campo de duenos para soporte',
        'buscar' => [
            '            <div class="field" id="campo_banco_nombre" style="display:none"><label>Banco (nombre) *</label><input id="nuevo_banco_nombre"></div>',
            '            <div class="field" id="campo_banco_cuenta" style="display:none"><label>Cuenta bancaria *</label><input id="nuevo_banco_cuenta"></div>',
            '          </div>',
            '          <div class="actions" style="margin-top:12px">',
            '            <button class="btn primary" id="boton_guardar_usuario">Guardar</button>',
            '            <button class="btn" id="boton_cancelar_nuevo_usuario">Cancelar</button>',
            '          </div>',
            '        </div>',
        ],
        'reemplazar' => [
            '            <div class="field" id="campo_banco_nombre" style="display:none"><label>Banco (nombre) *</label><input id="nuevo_banco_nombre"></div>',
            '            <div class="field" id="campo_banco_cuenta" style="display:none"><label>Cuenta bancaria *</label><input id="nuevo_banco_cuenta"></div>',
            '            <div class="field" id="campo_duenos_soporte" style="display:none">',
            '              <label>Dueños asignados</label>',
            '              <div id="nuevo_soporte_duenos_lista" style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:6px; border-radius:4px; min-width:260px;">',
            '                <!-- Se llena dinámicamente -->',
            '              </div>',
            '            </div>',
            '          </div>',
            '          <div class="actions" style="margin-top:12px">',
            '            <button class="btn primary" id="boton_guardar_usuario">Guardar</button>',
            '            <button class="btn" id="boton_cancelar_nuevo_usuario">Cancelar</button>',
            '          </div>',
            '        </div>',
        ],
    ],

    // ==========================================================
    // admin.js — handler de nivel: mostrar/ocultar campo soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: handler de nivel con soporte',
        'buscar' => [
            '$("#nuevo_nivel").addEventListener("change", function() {',
            '    const es_terminal = this.value === "terminal";',
            '    const es_dueno = this.value === "dueno";',
            '    const tiene_banco = es_terminal || es_dueno;',
            '    // Se usa "" en vez de "block" para no pisar el display: flex del CSS.',
            '    document.getElementById("campo_dueno").style.display = es_terminal ? "" : "none";',
            '    document.getElementById("campo_banco_nombre").style.display = tiene_banco ? "" : "none";',
            '    document.getElementById("campo_banco_cuenta").style.display = tiene_banco ? "" : "none";',
            '});',
        ],
        'reemplazar' => [
            '$("#nuevo_nivel").addEventListener("change", function() {',
            '    const es_terminal = this.value === "terminal";',
            '    const es_dueno = this.value === "dueno";',
            '    const es_soporte = this.value === "soporte";',
            '    const tiene_banco = es_terminal || es_dueno;',
            '    // Se usa "" en vez de "block" para no pisar el display: flex del CSS.',
            '    document.getElementById("campo_dueno").style.display = es_terminal ? "" : "none";',
            '    document.getElementById("campo_banco_nombre").style.display = tiene_banco ? "" : "none";',
            '    document.getElementById("campo_banco_cuenta").style.display = tiene_banco ? "" : "none";',
            '    document.getElementById("campo_duenos_soporte").style.display = es_soporte ? "" : "none";',
            '});',
        ],
    ],

    // ==========================================================
    // admin.js — cargar dueños como checkboxes al abrir el form
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: cargar lista de duenos como checkboxes',
        'buscar' => [
            '$("#boton_agregar_usuario").addEventListener("click", async () => {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '    // Si es soporte, restringir el selector de nivel a "terminal".',
            '    if (es_soporte) {',
            '        const select_nivel = $("#nuevo_nivel");',
            '        select_nivel.innerHTML = \'<option value="terminal">Terminal</option>\';',
            '        select_nivel.value = \'terminal\';',
            '        select_nivel.disabled = true;',
            '    }',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '        });',
            '        const datos = await respuesta.json();',
            '        if (datos.exito) {',
            '            const select_dueno = $("#nuevo_dueno_select");',
            '            select_dueno.innerHTML = \'<option value="">Seleccione dueño...</option>\';',
            '            datos.duenos.forEach(dueno => {',
            '                const opcion = document.createElement("option");',
            '                opcion.value = dueno.nombre_usuario;',
            '                opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;',
            '                select_dueno.appendChild(opcion);',
            '            });',
            '        } else {',
            '            mostrar_aviso("No se pudieron cargar los dueños", \'error\');',
            '        }',
            '    } catch (e) {',
            '        console.error("Error al cargar dueños", e);',
            '        mostrar_aviso("Error al cargar dueños", \'error\');',
            '    }',
            '    $("#formulario_nuevo_usuario").classList.remove("hidden");',
            '    $("#nuevo_nivel").dispatchEvent(new Event("change"));',
            '});',
        ],
        'reemplazar' => [
            '$("#boton_agregar_usuario").addEventListener("click", async () => {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '    // Si es soporte, restringir el selector de nivel a "terminal".',
            '    if (es_soporte) {',
            '        const select_nivel = $("#nuevo_nivel");',
            '        select_nivel.innerHTML = \'<option value="terminal">Terminal</option>\';',
            '        select_nivel.value = \'terminal\';',
            '        select_nivel.disabled = true;',
            '    } else {',
            '        const select_nivel = $("#nuevo_nivel");',
            '        select_nivel.disabled = false;',
            '    }',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '        });',
            '        const datos = await respuesta.json();',
            '        if (datos.exito) {',
            '            const select_dueno = $("#nuevo_dueno_select");',
            '            select_dueno.innerHTML = \'<option value="">Seleccione dueño...</option>\';',
            '            datos.duenos.forEach(dueno => {',
            '                const opcion = document.createElement("option");',
            '                opcion.value = dueno.nombre_usuario;',
            '                opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;',
            '                select_dueno.appendChild(opcion);',
            '            });',
            '',
            '            // Cargar la lista de dueños como checkboxes para el campo de soporte.',
            '            const contenedor = $("#nuevo_soporte_duenos_lista");',
            '            if (contenedor) {',
            '                contenedor.innerHTML = \'\';',
            '                if (datos.duenos.length === 0) {',
            '                    contenedor.innerHTML = \'<em>No hay dueños disponibles</em>\';',
            '                } else {',
            '                    datos.duenos.forEach(dueno => {',
            '                        const label = document.createElement(\'label\');',
            '                        label.style.display = \'block\';',
            '                        const checkbox = document.createElement(\'input\');',
            '                        checkbox.type = \'checkbox\';',
            '                        checkbox.value = dueno.nombre_usuario;',
            '                        checkbox.className = \'chk_dueno_soporte\';',
            '                        label.appendChild(checkbox);',
            '                        const txt = document.createTextNode(\' \' + (dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario));',
            '                        label.appendChild(txt);',
            '                        contenedor.appendChild(label);',
            '                    });',
            '                }',
            '            }',
            '        } else {',
            '            mostrar_aviso("No se pudieron cargar los dueños", \'error\');',
            '        }',
            '    } catch (e) {',
            '        console.error("Error al cargar dueños", e);',
            '        mostrar_aviso("Error al cargar dueños", \'error\');',
            '    }',
            '    $("#formulario_nuevo_usuario").classList.remove("hidden");',
            '    $("#nuevo_nivel").dispatchEvent(new Event("change"));',
            '});',
        ],
    ],

    // ==========================================================
    // admin.js — guardar usuario: recolectar duenos_asignados
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: guardar usuario con duenos_asignados',
        'buscar' => [
            '$("#boton_guardar_usuario").addEventListener("click", async () => {',
            '    const nivel = $("#nuevo_nivel").value;',
            '    const datos_usuario = {',
            '        accion: "administrador/agregar_usuario",',
            '        nombre_usuario: $("#nuevo_nombre_usuario").value.trim(),',
            '        contrasena: $("#nuevo_contrasena").value,',
            '        nombre_real: $("#nuevo_nombre_real").value.trim(),',
            '        email: $("#nuevo_email").value.trim(),',
            '        codigo_acceso: $("#nuevo_codigo_acceso").value.trim(),',
            '        nivel: nivel,',
            '        dueno: nivel === "terminal" ? $("#nuevo_dueno_select").value : "",',
            '        banco_nombre: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_nombre").value.trim() : "",',
            '        banco_cuenta: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_cuenta").value.trim() : ""',
            '    };',
        ],
        'reemplazar' => [
            '$("#boton_guardar_usuario").addEventListener("click", async () => {',
            '    const nivel = $("#nuevo_nivel").value;',
            '',
            '    // Recolectar dueños asignados si es soporte.',
            '    let duenos_asignados = [];',
            '    if (nivel === "soporte") {',
            '        document.querySelectorAll(\'.chk_dueno_soporte:checked\').forEach(chk => {',
            '            duenos_asignados.push(chk.value);',
            '        });',
            '    }',
            '',
            '    const datos_usuario = {',
            '        accion: "administrador/agregar_usuario",',
            '        nombre_usuario: $("#nuevo_nombre_usuario").value.trim(),',
            '        contrasena: $("#nuevo_contrasena").value,',
            '        nombre_real: $("#nuevo_nombre_real").value.trim(),',
            '        email: $("#nuevo_email").value.trim(),',
            '        codigo_acceso: $("#nuevo_codigo_acceso").value.trim(),',
            '        nivel: nivel,',
            '        dueno: nivel === "terminal" ? $("#nuevo_dueno_select").value : "",',
            '        banco_nombre: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_nombre").value.trim() : "",',
            '        banco_cuenta: (nivel === "terminal" || nivel === "dueno") ? $("#nuevo_banco_cuenta").value.trim() : "",',
            '        duenos_asignados: nivel === "soporte" ? JSON.stringify(duenos_asignados) : ""',
            '    };',
        ],
    ],

    // ==========================================================
    // admin.js — iniciar_edicion_usuario: celda de duenos para soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: edicion de usuario con celda para soporte',
        'buscar' => [
            '    let opciones_dueno = \'<option value="">Seleccione dueño...</option>\';',
            '    duenos.forEach(dueno => {',
            '        const seleccionado = dueno.nombre_usuario === valor_dueno ? \'selected\' : \'\';',
            '        opciones_dueno += `<option value="${dueno.nombre_usuario}" ${seleccionado}>${dueno.nombre_real ? dueno.nombre_real + \' (\' + dueno.nombre_usuario + \')\' : dueno.nombre_usuario}</option>`;',
            '    });',
            '',
            '    let celda_dueno;',
            '    if (valor_nivel === \'terminal\') {',
            '        celda_dueno = `<td><select id="editar_dueno">${opciones_dueno}</select></td>`;',
            '    } else {',
            '        celda_dueno = `<td>—</td>`;',
            '    }',
        ],
        'reemplazar' => [
            '    let opciones_dueno = \'<option value="">Seleccione dueño...</option>\';',
            '    duenos.forEach(dueno => {',
            '        const seleccionado = dueno.nombre_usuario === valor_dueno ? \'selected\' : \'\';',
            '        opciones_dueno += `<option value="${dueno.nombre_usuario}" ${seleccionado}>${dueno.nombre_real ? dueno.nombre_real + \' (\' + dueno.nombre_usuario + \')\' : dueno.nombre_usuario}</option>`;',
            '    });',
            '',
            '    let celda_dueno;',
            '    if (valor_nivel === \'terminal\') {',
            '        celda_dueno = `<td><select id="editar_dueno">${opciones_dueno}</select></td>`;',
            '    } else if (valor_nivel === \'soporte\') {',
            '        // Lista de checkboxes con todos los dueños, marcando los asignados.',
            '        const asignados = usuario.duenos || [];',
            '        let checkboxes_html = \'<div id="editar_soporte_duenos" style="max-height:120px; overflow-y:auto; min-width:180px;">\';',
            '        if (duenos.length === 0) {',
            '            checkboxes_html += \'<em>Sin dueños disponibles</em>\';',
            '        } else {',
            '            duenos.forEach(dueno => {',
            '                const marcado = asignados.indexOf(dueno.nombre_usuario) !== -1 ? \'checked\' : \'\';',
            '                const texto = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;',
            '                checkboxes_html += `<label style="display:block;"><input type="checkbox" class="chk_editar_dueno_soporte" value="${dueno.nombre_usuario}" ${marcado}> ${texto}</label>`;',
            '            });',
            '        }',
            '        checkboxes_html += \'</div>\';',
            '        celda_dueno = `<td>${checkboxes_html}</td>`;',
            '    } else {',
            '        celda_dueno = `<td>—</td>`;',
            '    }',
        ],
    ],

    // ==========================================================
    // admin.js — guardar_edicion_usuario: enviar duenos_asignados
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: guardar edicion con duenos_asignados',
        'buscar' => [
            '    const datos = {',
            '        accion: "administrador/actualizar_usuario",',
            '        nombre_usuario: nombre_usuario,',
            '        nombre_real: fila.querySelector(\'#editar_nombre_real\').value.trim(),',
            '        email: fila.querySelector(\'#editar_email\').value.trim(),',
            '        nivel: nivel,',
            '        codigo_acceso: fila.querySelector(\'#editar_codigo\').value.trim(),',
            '        banco_nombre: fila.querySelector(\'#editar_banco_nombre\').value.trim(),',
            '        banco_cuenta: fila.querySelector(\'#editar_banco_cuenta\').value.trim(),',
            '        dueno: nivel === \'terminal\' ? dueno : \'\',',
            '        contrasena: \'\'',
            '    };',
        ],
        'reemplazar' => [
            '    let duenos_asignados = [];',
            '    if (nivel === \'soporte\') {',
            '        fila.querySelectorAll(\'.chk_editar_dueno_soporte:checked\').forEach(chk => {',
            '            duenos_asignados.push(chk.value);',
            '        });',
            '    }',
            '',
            '    const datos = {',
            '        accion: "administrador/actualizar_usuario",',
            '        nombre_usuario: nombre_usuario,',
            '        nombre_real: fila.querySelector(\'#editar_nombre_real\').value.trim(),',
            '        email: fila.querySelector(\'#editar_email\').value.trim(),',
            '        nivel: nivel,',
            '        codigo_acceso: fila.querySelector(\'#editar_codigo\').value.trim(),',
            '        banco_nombre: fila.querySelector(\'#editar_banco_nombre\').value.trim(),',
            '        banco_cuenta: fila.querySelector(\'#editar_banco_cuenta\').value.trim(),',
            '        dueno: nivel === \'terminal\' ? dueno : \'\',',
            '        contrasena: \'\',',
            '        duenos_asignados: nivel === \'soporte\' ? JSON.stringify(duenos_asignados) : \'\'',
            '    };',
        ],
    ],

    // ==========================================================
    // admin.js — actualizar_visibilidad en edicion: soporte tambien oculta banco
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: visibilidad en edicion segun nivel soporte',
        'buscar' => [
            '    function actualizar_visibilidad() {',
            '        const es_terminal = select_nivel.value === \'terminal\';',
            '        const es_dueno = select_nivel.value === \'dueno\';',
            '        const tiene_banco = es_terminal || es_dueno;',
            '        campo_banco_nombre.style.display = tiene_banco ? \'\' : \'none\';',
            '        campo_banco_cuenta.style.display = tiene_banco ? \'\' : \'none\';',
            '        if (campo_dueno) {',
            '            campo_dueno.style.display = es_terminal ? \'\' : \'none\';',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    function actualizar_visibilidad() {',
            '        const es_terminal = select_nivel.value === \'terminal\';',
            '        const es_dueno = select_nivel.value === \'dueno\';',
            '        const es_soporte = select_nivel.value === \'soporte\';',
            '        const tiene_banco = es_terminal || es_dueno;',
            '        campo_banco_nombre.style.display = tiene_banco ? \'\' : \'none\';',
            '        campo_banco_cuenta.style.display = tiene_banco ? \'\' : \'none\';',
            '        if (campo_dueno) {',
            '            campo_dueno.style.display = es_terminal ? \'\' : \'none\';',
            '        }',
            '    }',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html — bump de admin.js
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de admin.js',
        'buscar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73a"></script>',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73 (nuevo rol',
            '`soporte`).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73a (alta de',
            'usuarios `soporte` con asignación de dueños).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73a al historial',
        'buscar' => [
            '- **v73**: nuevo rol `soporte`. Nodo usuario con `duenos` (contenedor).',
            '  Enlace `soporte` en el nodo dueño. Validaciones de permisos por',
            '  dueño con `_verificar_permiso_dueno`. Chequeo global en el enrutador.',
        ],
        'reemplazar' => [
            '- **v73**: nuevo rol `soporte`. Nodo usuario con `duenos` (contenedor).',
            '  Enlace `soporte` en el nodo dueño. Validaciones de permisos por',
            '  dueño con `_verificar_permiso_dueno`. Chequeo global en el enrutador.',
            '- **v73a**: alta de usuarios `soporte` desde el panel admin con',
            '  asignación de dueños (checkboxes). Edición de los dueños asignados',
            '  de un soporte existente.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (alta de soportes) ===\n\n";

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