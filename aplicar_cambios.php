<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73g: alta de usuarios en modal.
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
    // aplicacion.js — agregar función de alta al final
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73f',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73g',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar funciones de alta genericas',
        'buscar' => [
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al actualizar", \'error\');',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al actualizar", \'error\');',
            '    }',
            '}',
            '',
            '// ============================================================',
            '// ====== MODAL GENERICO DE ALTA DE USUARIO ===================',
            '// ============================================================',
            '',
            '/**',
            ' * Abre el modal para crear un usuario. Lo usan tanto el admin',
            ' * (para usuarios de cualquier nivel) como el dueño (para sus',
            ' * propias terminales) y el soporte (solo terminales).',
            ' *',
            ' * Acepta las mismas opciones que abrir_modal_editar_usuario_generico',
            ' * salvo obtener_datos (no hay usuario previo).',
            ' *',
            ' * @param {object} opciones',
            ' *   - accion_guardar: string, accion POST',
            ' *   - titulo: string (opcional)',
            ' *   - mostrar_nivel: bool',
            ' *   - nivel_forzado: string (si mostrar_nivel es false)',
            ' *   - niveles_disponibles: array de {valor, etiqueta}',
            ' *   - mostrar_banco: bool',
            ' *   - mostrar_dueno: bool',
            ' *   - mostrar_duenos_soporte: bool',
            ' *   - listar_duenos: async () => array (opcional)',
            ' *   - datos_extra: objeto de campos fijos (opcional)',
            ' *   - al_guardar_exito: function (opcional)',
            ' */',
            'async function abrir_modal_agregar_usuario_generico(opciones) {',
            '    // Listar dueños si hace falta.',
            '    let duenos = [];',
            '    if ((opciones.mostrar_dueno || opciones.mostrar_duenos_soporte) && typeof opciones.listar_duenos === \'function\') {',
            '        try {',
            '            duenos = await opciones.listar_duenos();',
            '        } catch (e) {',
            '            console.error(\'Error al cargar dueños\', e);',
            '        }',
            '    }',
            '',
            '    const valor_nivel = opciones.nivel_forzado || (opciones.mostrar_nivel && opciones.niveles_disponibles && opciones.niveles_disponibles[0]',
            '        ? opciones.niveles_disponibles[0].valor',
            '        : \'dueno\');',
            '',
            '    // Bloque "Nivel".',
            '    let html_nivel = \'\';',
            '    if (opciones.mostrar_nivel) {',
            '        const opciones_nivel = (opciones.niveles_disponibles || []).map(n => {',
            '            const sel = (n.valor === valor_nivel) ? \' selected\' : \'\';',
            '            return `<option value="${n.valor}"${sel}>${n.etiqueta}</option>`;',
            '        }).join(\'\');',
            '        html_nivel = `',
            '            <div class="field">',
            '                <label>Nivel</label>',
            '                <select id="modal_agregar_nivel">',
            '                    ${opciones_nivel}',
            '                </select>',
            '            </div>',
            '        `;',
            '    }',
            '',
            '    // Bloque "Dueño" (select, solo para terminal del admin o soporte).',
            '    let html_dueno = \'\';',
            '    if (opciones.mostrar_dueno) {',
            '        let opciones_dueno = \'<option value="">Seleccione dueño...</option>\';',
            '        duenos.forEach(d => {',
            '            const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;',
            '            opciones_dueno += `<option value="${d.nombre_usuario}">${texto}</option>`;',
            '        });',
            '        html_dueno = `',
            '            <div class="field" id="modal_agregar_campo_dueno" style="display:none">',
            '                <label>Dueño (para terminales) *</label>',
            '                <select id="modal_agregar_dueno">${opciones_dueno}</select>',
            '            </div>',
            '        `;',
            '    }',
            '',
            '    // Bloque "Banco" (nombre y cuenta).',
            '    let html_banco = \'\';',
            '    if (opciones.mostrar_banco) {',
            '        html_banco = `',
            '            <div class="field" id="modal_agregar_campo_banco_nombre" style="display:none">',
            '                <label>Banco (nombre) *</label>',
            '                <input type="text" id="modal_agregar_banco_nombre" value="">',
            '            </div>',
            '            <div class="field" id="modal_agregar_campo_banco_cuenta" style="display:none">',
            '                <label>Cuenta bancaria *</label>',
            '                <input type="text" id="modal_agregar_banco_cuenta" value="">',
            '            </div>',
            '        `;',
            '    }',
            '',
            '    // Bloque "Dueños asignados" (checkboxes, solo para soporte).',
            '    let html_duenos_soporte = \'\';',
            '    if (opciones.mostrar_duenos_soporte) {',
            '        let checkboxes_html = \'\';',
            '        if (duenos.length === 0) {',
            '            checkboxes_html = \'<em>Sin dueños disponibles</em>\';',
            '        } else {',
            '            duenos.forEach(d => {',
            '                const texto = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;',
            '                checkboxes_html += `<label style="display:block;"><input type="checkbox" class="chk_modal_agregar_dueno_soporte" value="${d.nombre_usuario}"> ${texto}</label>`;',
            '            });',
            '        }',
            '        html_duenos_soporte = `',
            '            <div class="field full" id="modal_agregar_campo_duenos_soporte" style="display:none">',
            '                <label>Dueños asignados</label>',
            '                <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:6px; border-radius:4px;">',
            '                    ${checkboxes_html}',
            '                </div>',
            '            </div>',
            '        `;',
            '    }',
            '',
            '    const titulo = opciones.titulo || \'Nuevo usuario\';',
            '',
            '    const html = `',
            '        <div class="form-grid">',
            '            <div class="field">',
            '                <label>Nombre de usuario *</label>',
            '                <input type="text" id="modal_agregar_nombre_usuario" value="">',
            '            </div>',
            '            ${html_nivel}',
            '            <div class="field">',
            '                <label>Contraseña (opcional)</label>',
            '                <input type="password" id="modal_agregar_contrasena" value="">',
            '            </div>',
            '            <div class="field">',
            '                <label>Nombre real</label>',
            '                <input type="text" id="modal_agregar_nombre_real" value="">',
            '            </div>',
            '            <div class="field">',
            '                <label>Email (opcional)</label>',
            '                <input type="email" id="modal_agregar_email" value="">',
            '            </div>',
            '            <div class="field">',
            '                <label>Código de acceso *</label>',
            '                <input type="text" id="modal_agregar_codigo" value="">',
            '            </div>',
            '            ${html_dueno}',
            '            ${html_banco}',
            '            ${html_duenos_soporte}',
            '        </div>',
            '        <div class="actions" style="margin-top:15px">',
            '            <button class="btn primary" id="modal_btn_guardar_alta">Guardar</button>',
            '            <button class="btn" id="modal_btn_cancelar_alta">Cancelar</button>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(titulo, html);',
            '',
            '    const contenedor = document.getElementById(\'modal_generico_contenido\');',
            '    if (!contenedor) return;',
            '',
            '    const select_nivel = contenedor.querySelector(\'#modal_agregar_nivel\');',
            '    const campo_dueno = contenedor.querySelector(\'#modal_agregar_campo_dueno\');',
            '    const campo_banco_nombre = contenedor.querySelector(\'#modal_agregar_campo_banco_nombre\');',
            '    const campo_banco_cuenta = contenedor.querySelector(\'#modal_agregar_campo_banco_cuenta\');',
            '    const campo_duenos_soporte = contenedor.querySelector(\'#modal_agregar_campo_duenos_soporte\');',
            '',
            '    function _nivel_actual() {',
            '        if (opciones.mostrar_nivel && select_nivel) return select_nivel.value;',
            '        return opciones.nivel_forzado || \'\';',
            '    }',
            '',
            '    function actualizar_visibilidad() {',
            '        const nivel = _nivel_actual();',
            '        const es_terminal = nivel === \'terminal\';',
            '        const es_dueno = nivel === \'dueno\';',
            '        const es_soporte = nivel === \'soporte\';',
            '        const tiene_banco = es_terminal || es_dueno;',
            '        if (campo_banco_nombre) campo_banco_nombre.style.display = tiene_banco ? \'\' : \'none\';',
            '        if (campo_banco_cuenta) campo_banco_cuenta.style.display = tiene_banco ? \'\' : \'none\';',
            '        if (campo_dueno) campo_dueno.style.display = es_terminal ? \'\' : \'none\';',
            '        if (campo_duenos_soporte) campo_duenos_soporte.style.display = es_soporte ? \'\' : \'none\';',
            '    }',
            '    if (select_nivel) select_nivel.addEventListener(\'change\', actualizar_visibilidad);',
            '    actualizar_visibilidad();',
            '',
            '    contenedor.querySelector(\'#modal_btn_cancelar_alta\').addEventListener(\'click\', cerrar_modal_generico);',
            '    contenedor.querySelector(\'#modal_btn_guardar_alta\').addEventListener(\'click\', () => {',
            '        _guardar_alta_usuario_generico(contenedor, opciones);',
            '    });',
            '}',
            '',
            '/**',
            ' * Guarda un usuario creado desde el modal genérico.',
            ' */',
            'async function _guardar_alta_usuario_generico(contenedor, opciones) {',
            '    const nivel = opciones.mostrar_nivel',
            '        ? contenedor.querySelector(\'#modal_agregar_nivel\').value',
            '        : (opciones.nivel_forzado || \'\');',
            '    const select_dueno = contenedor.querySelector(\'#modal_agregar_dueno\');',
            '    const dueno = select_dueno ? select_dueno.value : \'\';',
            '',
            '    let duenos_asignados = [];',
            '    if (opciones.mostrar_duenos_soporte && nivel === \'soporte\') {',
            '        contenedor.querySelectorAll(\'.chk_modal_agregar_dueno_soporte:checked\').forEach(chk => {',
            '            duenos_asignados.push(chk.value);',
            '        });',
            '    }',
            '',
            '    const datos = {',
            '        accion: opciones.accion_guardar,',
            '        nombre_usuario: (contenedor.querySelector(\'#modal_agregar_nombre_usuario\')?.value || \'\').trim(),',
            '        contrasena: (contenedor.querySelector(\'#modal_agregar_contrasena\')?.value || \'\'),',
            '        nombre_real: (contenedor.querySelector(\'#modal_agregar_nombre_real\')?.value || \'\').trim(),',
            '        email: (contenedor.querySelector(\'#modal_agregar_email\')?.value || \'\').trim(),',
            '        codigo_acceso: (contenedor.querySelector(\'#modal_agregar_codigo\')?.value || \'\').trim(),',
            '        nivel: nivel',
            '    };',
            '',
            '    if (opciones.mostrar_banco) {',
            '        datos.banco_nombre = (contenedor.querySelector(\'#modal_agregar_banco_nombre\')?.value || \'\').trim();',
            '        datos.banco_cuenta = (contenedor.querySelector(\'#modal_agregar_banco_cuenta\')?.value || \'\').trim();',
            '    }',
            '',
            '    if (opciones.mostrar_dueno) {',
            '        datos.dueno = (nivel === \'terminal\') ? dueno : \'\';',
            '    }',
            '',
            '    if (opciones.mostrar_duenos_soporte) {',
            '        datos.duenos_asignados = (nivel === \'soporte\') ? JSON.stringify(duenos_asignados) : \'\';',
            '    }',
            '',
            '    if (opciones.datos_extra) {',
            '        Object.assign(datos, opciones.datos_extra);',
            '    }',
            '',
            '    // Validaciones locales.',
            '    if (!datos.nombre_usuario) {',
            '        mostrar_aviso(\'El nombre de usuario es obligatorio\', \'error\');',
            '        return;',
            '    }',
            '    if (!datos.codigo_acceso && !datos.contrasena) {',
            '        mostrar_aviso(\'Debe asignar al menos un código de acceso o una contraseña\', \'error\');',
            '        return;',
            '    }',
            '    if (nivel === \'terminal\') {',
            '        if (opciones.mostrar_dueno && !datos.dueno) {',
            '            mostrar_aviso(\'Debe seleccionar un dueño\', \'error\');',
            '            return;',
            '        }',
            '        if (opciones.mostrar_banco && (!datos.banco_nombre || !datos.banco_cuenta)) {',
            '            mostrar_aviso(\'Banco y cuenta son obligatorios para terminales\', \'error\');',
            '            return;',
            '        }',
            '    }',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(datos)',
            '    });',
            '    const resultado = await respuesta.json();',
            '    if (resultado.exito) {',
            '        if (resultado.codigo_asignado) {',
            '            alert("Código de acceso: " + resultado.codigo_asignado + "\\n\\nGuardalo, no se mostrará de nuevo.");',
            '        }',
            '        mostrar_aviso("Usuario creado correctamente", \'exito\');',
            '        cerrar_modal_generico();',
            '        if (typeof opciones.al_guardar_exito === \'function\') {',
            '            opciones.al_guardar_exito();',
            '        }',
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al crear usuario", \'error\');',
            '    }',
            '}',
        ],
    ],

    // ==========================================================
    // admin.js — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73f',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73g',
        ],
    ],

    // ==========================================================
    // admin.js — reemplazar listener del boton agregar usuario
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: boton agregar usuario abre modal',
        'buscar' => [
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
            '        const duenos = await _listar_duenos_admin();',
            '        const select_dueno = $("#nuevo_dueno_select");',
            '        select_dueno.innerHTML = \'<option value="">Seleccione dueño...</option>\';',
            '        duenos.forEach(dueno => {',
            '            const opcion = document.createElement("option");',
            '            opcion.value = dueno.nombre_usuario;',
            '            opcion.textContent = dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario;',
            '            select_dueno.appendChild(opcion);',
            '        });',
            '',
            '        // Cargar la lista de dueños como checkboxes para el campo de soporte.',
            '        const contenedor = $("#nuevo_soporte_duenos_lista");',
            '        if (contenedor) {',
            '            contenedor.innerHTML = \'\';',
            '            if (duenos.length === 0) {',
            '                contenedor.innerHTML = \'<em>No hay dueños disponibles</em>\';',
            '            } else {',
            '                duenos.forEach(dueno => {',
            '                    const label = document.createElement(\'label\');',
            '                    label.style.display = \'block\';',
            '                    const checkbox = document.createElement(\'input\');',
            '                    checkbox.type = \'checkbox\';',
            '                    checkbox.value = dueno.nombre_usuario;',
            '                    checkbox.className = \'chk_dueno_soporte\';',
            '                    label.appendChild(checkbox);',
            '                    const txt = document.createTextNode(\' \' + (dueno.nombre_real ? `${dueno.nombre_real} (${dueno.nombre_usuario})` : dueno.nombre_usuario));',
            '                    label.appendChild(txt);',
            '                    contenedor.appendChild(label);',
            '                });',
            '            }',
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
            '/**',
            ' * Abre el modal para crear un usuario. Envuelve la función',
            ' * común con las opciones de admin: cualquier nivel, selector',
            ' * de dueño, banco y checkboxes de soporte. Si el usuario actual',
            ' * es soporte, restringe el nivel a terminal.',
            ' */',
            'async function abrir_modal_agregar_usuario() {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '',
            '    const niveles_disponibles = es_soporte',
            '        ? [{ valor: \'terminal\', etiqueta: \'Terminal\' }]',
            '        : [',
            '            { valor: \'terminal\', etiqueta: \'Terminal\' },',
            '            { valor: \'dueno\', etiqueta: \'Dueño\' },',
            '            { valor: \'admin\', etiqueta: \'Administrador\' },',
            '            { valor: \'soporte\', etiqueta: \'Soporte\' }',
            '        ];',
            '',
            '    return abrir_modal_agregar_usuario_generico({',
            '        accion_guardar: \'administrador/agregar_usuario\',',
            '        titulo: \'Nuevo usuario\',',
            '        mostrar_nivel: true,',
            '        niveles_disponibles: niveles_disponibles,',
            '        mostrar_banco: true,',
            '        mostrar_dueno: true,',
            '        mostrar_duenos_soporte: !es_soporte,',
            '        listar_duenos: _listar_duenos_admin,',
            '        al_guardar_exito: cargar_datos_admin',
            '    });',
            '}',
            '',
            '$("#boton_agregar_usuario").addEventListener("click", abrir_modal_agregar_usuario);',
        ],
    ],

    // ==========================================================
    // admin.js — eliminar listeners huerfanos del formulario embebido
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: quitar listener de nuevo_nivel embebido',
        'buscar' => [
            '// Eventos del formulario de nuevo usuario',
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
            '',
        ],
        'reemplazar' => [
            '// El listener del formulario embebido de nuevo usuario (nuevo_nivel)',
            '// se eliminó al pasar al modal. El HTML embebido se limpia en una',
            '// tanda aparte.',
            '',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: quitar listener cancelar embebido',
        'buscar' => [
            '$("#boton_cancelar_nuevo_usuario").addEventListener("click", () => {',
            '    $("#formulario_nuevo_usuario").classList.add("hidden");',
            '});',
            '',
        ],
        'reemplazar' => [
            '',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: quitar listener guardar embebido',
        'buscar' => [
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
            '',
            '    if (!datos_usuario.nombre_usuario) {',
            '        mostrar_aviso("El nombre de usuario es obligatorio", \'error\');',
            '        return;',
            '    }',
            '    if (!datos_usuario.codigo_acceso && !datos_usuario.contrasena) {',
            '        mostrar_aviso("Debe asignar al menos un código de acceso o una contraseña", \'error\');',
            '        return;',
            '    }',
            '    if (nivel === "terminal") {',
            '        if (!datos_usuario.dueno) {',
            '            mostrar_aviso("Debe seleccionar un dueño", \'error\');',
            '            return;',
            '        }',
            '        if (!datos_usuario.banco_nombre || !datos_usuario.banco_cuenta) {',
            '            mostrar_aviso("Banco y cuenta son obligatorios para terminales", \'error\');',
            '            return;',
            '        }',
            '    }',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(datos_usuario)',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (datos.exito) {',
            '        if (datos.codigo_asignado) {',
            '            alert("Código de acceso: " + datos.codigo_asignado + "\\n\\nGuardalo, no se mostrará de nuevo.");',
            '        }',
            '        mostrar_aviso("Usuario agregado correctamente", \'exito\');',
            '        $("#formulario_nuevo_usuario").classList.add("hidden");',
            '        ["nuevo_nombre_usuario","nuevo_contrasena","nuevo_nombre_real","nuevo_email","nuevo_codigo_acceso","nuevo_dueno_select","nuevo_banco_nombre","nuevo_banco_cuenta"].forEach(id => $("#"+id).value="");',
            '        cargar_datos_admin();',
            '    } else {',
            '        mostrar_aviso(datos.error || "Error al agregar usuario", \'error\');',
            '    }',
            '});',
        ],
        'reemplazar' => [
            '',
        ],
    ],

    // ==========================================================
    // terminales.js — bump
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73f',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73g',
        ],
    ],

    // ==========================================================
    // terminales.js — reemplazar listener del boton agregar terminal
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/terminales.js',
        'descripcion' => 'terminales.js: boton agregar terminal abre modal',
        'buscar' => [
            '// Eventos de formulario de terminales',
            '$("#boton_agregar_terminal").addEventListener("click", () => {',
            '    $("#formulario_nueva_terminal").classList.remove("hidden");',
            '});',
            '',
            '$("#boton_cancelar_terminal").addEventListener("click", () => {',
            '    $("#formulario_nueva_terminal").classList.add("hidden");',
            '});',
            '',
            '$("#boton_guardar_terminal").addEventListener("click", async () => {',
            '    const datos_terminal = {',
            '        accion: "dueno/agregar_terminal",',
            '        nombre_dueno: usuario_actual.nombre_usuario,',
            '        nombre_usuario: $("#nuevo_terminal_nombre_usuario").value.trim(),',
            '        contrasena: $("#nuevo_terminal_contrasena").value,',
            '        nombre_real: $("#nuevo_terminal_nombre_real").value.trim(),',
            '        email: $("#nuevo_terminal_email").value.trim(),',
            '        codigo_acceso: $("#nuevo_terminal_codigo_acceso").value.trim(),',
            '        banco_nombre: $("#nuevo_terminal_banco_nombre").value.trim(),',
            '        banco_cuenta: $("#nuevo_terminal_banco_cuenta").value.trim()',
            '    };',
            '',
            '    if (!datos_terminal.nombre_usuario) {',
            '        mostrar_aviso("El nombre de usuario es obligatorio", \'error\');',
            '        return;',
            '    }',
            '    if (!datos_terminal.codigo_acceso && !datos_terminal.contrasena) {',
            '        mostrar_aviso("Debe asignar al menos un código de acceso o una contraseña", \'error\');',
            '        return;',
            '    }',
            '    if (!datos_terminal.banco_nombre || !datos_terminal.banco_cuenta) {',
            '        mostrar_aviso("Banco y cuenta son obligatorios", \'error\');',
            '        return;',
            '    }',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams(datos_terminal)',
            '    });',
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
            '});',
        ],
        'reemplazar' => [
            '/**',
            ' * Abre el modal para crear una terminal del dueño.',
            ' * Envuelve la función común con las opciones del dueño:',
            ' * nivel forzado a terminal, sin selector de dueño, con banco.',
            ' */',
            'async function abrir_modal_agregar_terminal() {',
            '    return abrir_modal_agregar_usuario_generico({',
            '        accion_guardar: \'dueno/agregar_terminal\',',
            '        titulo: \'Nuevo punto de venta\',',
            '        mostrar_nivel: false,',
            '        nivel_forzado: \'terminal\',',
            '        mostrar_banco: true,',
            '        mostrar_dueno: false,',
            '        mostrar_duenos_soporte: false,',
            '        datos_extra: { nombre_dueno: usuario_actual.nombre_usuario },',
            '        al_guardar_exito: cargar_datos_terminales',
            '    });',
            '}',
            '',
            '$("#boton_agregar_terminal").addEventListener("click", abrir_modal_agregar_terminal);',
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
            '<script src="aplicacion.js?v=1.5piloto.73f"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.73g"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de admin.js',
        'buscar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73f"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73g"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de terminales.js',
        'buscar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.73f"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/terminales.js?v=1.5piloto.73g"></script>',
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
            '**Última actualización de este prompt:** v1.5piloto.73f (modal de',
            'edición de usuario reutilizable).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73g (alta de',
            'usuarios en modal, reutilizando el mismo patrón que la edición).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73g al historial',
        'buscar' => [
            '- **v73f**: el modal de edición se centralizó en',
            '  `abrir_modal_editar_usuario_generico` (en `aplicacion.js`).',
            '  `admin.js` y `terminales.js` ahora son solo wrappers que lo',
            '  llaman con opciones distintas.',
        ],
        'reemplazar' => [
            '- **v73f**: el modal de edición se centralizó en',
            '  `abrir_modal_editar_usuario_generico` (en `aplicacion.js`).',
            '  `admin.js` y `terminales.js` ahora son solo wrappers que lo',
            '  llaman con opciones distintas.',
            '- **v73g**: el alta de usuarios también pasa a modal, reutilizando',
            '  el mismo patrón. Los formularios embebidos en el HTML quedan sin',
            '  uso (se limpian en una próxima tanda).',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (alta de usuarios en modal) ===\n\n";

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