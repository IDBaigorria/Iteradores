<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.73x:
 * - Fix de dos bugs relacionados con la ligadura comprador-pasajero:
 *   (1) la atadura ya no se activa con pasajeros marcados como
 *       duplicados (ya asignados a otro asiento del mismo viaje o
 *       repetidos en otro formulario de esta venta);
 *   (2) al corregir el DNI por uno no registrado, se limpian los
 *       campos autocompletados del pasajero y del comprador.
 * - Se elimina el botón "Usar primer pasajero": obsoleto desde que
 *   la atadura funciona en ambos sentidos.
 * - Actualización del prompt del piloto.
 *
 * Asume que v73v y v73w ya fueron aplicados.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
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
    // Aplicacion/ventas.js — Fix v73x
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: bump de version 73w a 73x',
        'buscar' => [
            '/***',
            ' * Funciones de venta, confirmación, listado y cancelación.',
            ' * @version 1.5piloto.73w',
            ' */',
        ],
        'reemplazar' => [
            '/***',
            ' * Funciones de venta, confirmación, listado y cancelación.',
            ' * @version 1.5piloto.73x',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: init de comprador_dni_con_datos',
        'buscar' => [
            'if (!window.pasajeros_autocompletado_estado) {',
            '    window.pasajeros_autocompletado_estado = {};',
            '}',
            'window.comprador_autocompletado_dni = null;',
        ],
        'reemplazar' => [
            'if (!window.pasajeros_autocompletado_estado) {',
            '    window.pasajeros_autocompletado_estado = {};',
            '}',
            'window.comprador_autocompletado_dni = null;',
            'window.comprador_dni_con_datos = null;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: reset de estado del comprador al abrir el modal',
        'buscar' => [
            '    // Resetear el estado de atadura por si quedo de una apertura previa.',
            '    window.atadura_actual = null;',
            '    window.atadura_ultimo_campo = {};',
        ],
        'reemplazar' => [
            '    // Resetear el estado de atadura y del comprador por si quedo',
            '    // algo de una apertura previa.',
            '    window.atadura_actual = null;',
            '    window.atadura_ultimo_campo = {};',
            '    window.comprador_autocompletado_dni = null;',
            '    window.comprador_dni_con_datos = null;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: eliminar el boton Usar primer pasajero del modal',
        'buscar' => [
            '                <div class="field"><label>Celular *</label><input id="comprador_celular"></div>',
            '            </div>',
            '            <button class="btn small" id="usar_pasajero_como_comprador">Usar primer pasajero</button>',
            '        </div>',
        ],
        'reemplazar' => [
            '                <div class="field"><label>Celular *</label><input id="comprador_celular"></div>',
            '            </div>',
            '        </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: eliminar el listener de Usar primer pasajero',
        'buscar' => [
            '    $("#usar_pasajero_como_comprador").addEventListener("click", async () => {',
            '        const r = recolectar_datos_pasajero(0, {',
            '            incluir_selector_sb: true',
            '        });',
            '        if (!r.ok) {',
            "            mostrar_aviso(r.error, 'error');",
            '            return;',
            '        }',
            '        const datos_pas = r.datos;',
            '',
            '        // Guardar el pasajero 0 en el sistema. Si ya existe, no',
            '        // pasa nada (el error de DNI duplicado se ignora).',
            "        const nombre_dueno_pas = (usuario_actual.nivel === 'terminal')",
            '            ? viaje_seleccionado.dueno',
            '            : obtener_nombre_dueno_actual();',
            '        try {',
            '            const resp = await fetch("index.php", {',
            '                method: "POST",',
            '                headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '                body: new URLSearchParams({',
            '                    accion: "pasajeros/crear",',
            '                    nombre_dueno: nombre_dueno_pas,',
            '                    dni: datos_pas.dni,',
            '                    apellido: datos_pas.apellido,',
            '                    nombres: datos_pas.nombres,',
            '                    email: datos_pas.email,',
            '                    celular: datos_pas.celular,',
            '                    celular_emergencia: datos_pas.celular_emergencia,',
            '                    fecha_nacimiento: datos_pas.fecha_nacimiento,',
            '                    direccion: datos_pas.direccion,',
            '                    localidad: datos_pas.localidad',
            '                })',
            '            });',
            '            const resultado = await resp.json();',
            '            if (!resultado.exito && !/ya existe/i.test(resultado.error || \'\')) {',
            '                mostrar_aviso(resultado.error || "Error al guardar el pasajero", \'error\');',
            '                return;',
            '            }',
            '        } catch (e) {',
            '            mostrar_aviso("Error de comunicacion al guardar el pasajero", \'error\');',
            '            return;',
            '        }',
            '',
            '        // Copiar solo el DNI al comprador y disparar la busqueda.',
            '        const input_dni_comp = $("#comprador_dni");',
            '        input_dni_comp.value = datos_pas.dni;',
            '        await _buscar_comprador_por_dni(true);',
            '    });',
            '',
            '    // Listener del DNI del comprador: mismo comportamiento que los',
            '    // formularios de pasajeros.',
            '    const input_dni_comp_el = document.getElementById(\'comprador_dni\');',
        ],
        'reemplazar' => [
            '    // Listener del DNI del comprador: mismo comportamiento que los',
            '    // formularios de pasajeros.',
            '    const input_dni_comp_el = document.getElementById(\'comprador_dni\');',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: romper atadura al detectar pasajero duplicado en otro formulario',
        'buscar' => [
            '    if (_dni_duplicado_en_otros_formularios(index, dni_norm)) {',
            "        _mostrar_aviso_en_formulario(index, 'Ese DNI ya esta cargado en otro pasajero de esta venta.', 'rojo');",
            '        _resetear_campos_pasajero(index);',
            '        window.pasajeros_autocompletado_estado[index] = { dni_buscado: dni_norm, duplicado: true };',
            "        mostrar_aviso('DNI duplicado en otro pasajero de esta venta', 'error');",
            '        return;',
            '    }',
        ],
        'reemplazar' => [
            '    if (_dni_duplicado_en_otros_formularios(index, dni_norm)) {',
            "        _mostrar_aviso_en_formulario(index, 'Ese DNI ya esta cargado en otro pasajero de esta venta.', 'rojo');",
            '        _resetear_campos_pasajero(index);',
            '        window.pasajeros_autocompletado_estado[index] = { dni_buscado: dni_norm, duplicado: true };',
            '        // Si la atadura estaba apuntando a este pasajero, romperla.',
            '        if (window.atadura_actual && window.atadura_actual.indice_pasajero === index) {',
            '            _romper_atadura();',
            '        }',
            "        mostrar_aviso('DNI duplicado en otro pasajero de esta venta', 'error');",
            '        return;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: romper atadura al detectar pasajero duplicado en otro asiento',
        'buscar' => [
            '    if (_dni_en_asientos_actuales(dni_norm)) {',
            "        _mostrar_aviso_en_formulario(index, 'Ese DNI ya esta asignado a otro asiento de este viaje.', 'rojo');",
            '        _resetear_campos_pasajero(index);',
            '        window.pasajeros_autocompletado_estado[index] = { dni_buscado: dni_norm, duplicado: true };',
            "        mostrar_aviso('DNI ya asignado a otro asiento de este viaje', 'error');",
            '        return;',
            '    }',
        ],
        'reemplazar' => [
            '    if (_dni_en_asientos_actuales(dni_norm)) {',
            "        _mostrar_aviso_en_formulario(index, 'Ese DNI ya esta asignado a otro asiento de este viaje.', 'rojo');",
            '        _resetear_campos_pasajero(index);',
            '        window.pasajeros_autocompletado_estado[index] = { dni_buscado: dni_norm, duplicado: true };',
            '        // Si la atadura estaba apuntando a este pasajero, romperla.',
            '        if (window.atadura_actual && window.atadura_actual.indice_pasajero === index) {',
            '            _romper_atadura();',
            '        }',
            "        mostrar_aviso('DNI ya asignado a otro asiento de este viaje', 'error');",
            '        return;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: limpiar campos del pasajero cuando el DNI no esta registrado',
        'buscar' => [
            '        } else {',
            '            _habilitar_campos_pasajero(index, true);',
            "            _mostrar_aviso_en_formulario(index, 'DNI no registrado. Complete los datos.', 'gris');",
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            "                fecha_modificacion: ''",
            '            };',
            '        }',
        ],
        'reemplazar' => [
            '        } else {',
            '            // Limpiar los campos no-DNI antes de habilitar. Si el',
            '            // usuario habia autocompletado un DNI previo y ahora',
            '            // corrige por uno no registrado, los datos viejos no',
            '            // deben quedar ni propagarse por ligadura.',
            '            _limpiar_campos_pasajero(index);',
            '            _habilitar_campos_pasajero(index, true);',
            "            _mostrar_aviso_en_formulario(index, 'DNI no registrado. Complete los datos.', 'gris');",
            '            window.pasajeros_autocompletado_estado[index] = {',
            '                dni_buscado: dni_norm,',
            '                encontrado: false,',
            "                fecha_modificacion: ''",
            '            };',
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _buscar_comprador_por_dni limpia si cambio a un DNI no registrado',
        'buscar' => [
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_comprador(datos.pasajero);',
            '            const antiguedad = _calcular_antiguedad_datos(datos.pasajero.fecha_ultima_modificacion);',
            '            _mostrar_aviso_comprador(antiguedad.texto, antiguedad.clase);',
            '        } else {',
            "            _mostrar_aviso_comprador('DNI no registrado. Complete los datos.', 'gris');",
            '        }',
        ],
        'reemplazar' => [
            '        if (datos.exito && datos.pasajero) {',
            '            _aplicar_datos_comprador(datos.pasajero);',
            '            window.comprador_dni_con_datos = dni_norm;',
            '            const antiguedad = _calcular_antiguedad_datos(datos.pasajero.fecha_ultima_modificacion);',
            '            _mostrar_aviso_comprador(antiguedad.texto, antiguedad.clase);',
            '        } else {',
            '            // Si antes se habian autocompletado datos para otro DNI',
            '            // y ahora el DNI cambio por uno no registrado, limpiar',
            '            // los campos del comprador para no arrastrar los datos',
            '            // del DNI anterior.',
            '            if (window.comprador_dni_con_datos && window.comprador_dni_con_datos !== dni_norm) {',
            '                _limpiar_campos_comprador();',
            '                window.comprador_dni_con_datos = null;',
            '            }',
            "            _mostrar_aviso_comprador('DNI no registrado. Complete los datos.', 'gris');",
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: agregar _limpiar_campos_comprador',
        'buscar' => [
            "    set_si_no_vacio('comprador_apellido', datos.apellido);",
            "    set_si_no_vacio('comprador_nombres', datos.nombres);",
            "    set_si_no_vacio('comprador_email', datos.email);",
            "    set_si_no_vacio('comprador_celular', datos.celular);",
            '}',
            '',
            '/**',
            ' * Muestra un cartel de estado en el bloque del comprador.',
            ' */',
        ],
        'reemplazar' => [
            "    set_si_no_vacio('comprador_apellido', datos.apellido);",
            "    set_si_no_vacio('comprador_nombres', datos.nombres);",
            "    set_si_no_vacio('comprador_email', datos.email);",
            "    set_si_no_vacio('comprador_celular', datos.celular);",
            '}',
            '',
            '/**',
            ' * Limpia los campos no-DNI del comprador. Se usa cuando el',
            ' * usuario corrige el DNI por uno no registrado y antes se',
            ' * habian autocompletado datos para el DNI previo.',
            ' */',
            'function _limpiar_campos_comprador() {',
            '    const ids = [',
            "        'comprador_apellido',",
            "        'comprador_nombres',",
            "        'comprador_email',",
            "        'comprador_celular'",
            '    ];',
            '    ids.forEach(id => {',
            '        const el = document.getElementById(id);',
            "        if (el) el.value = '';",
            '    });',
            '}',
            '',
            '/**',
            ' * Muestra un cartel de estado en el bloque del comprador.',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'ventas.js: _verificar_atadura_por_dni no activa con pasajero duplicado',
        'buscar' => [
            '            const dni_pas = _normalizar_dni_input(el.value);',
            '            if (dni_pas === comp_dni) {',
            '                indice_coincidente = i;',
            '                break;',
            '            }',
        ],
        'reemplazar' => [
            '            const dni_pas = _normalizar_dni_input(el.value);',
            '            if (dni_pas === comp_dni) {',
            '                // No activar la atadura si el pasajero esta marcado',
            '                // como duplicado (ya asignado a otro asiento del',
            '                // mismo viaje, o repetido en otro formulario de',
            '                // esta misma venta).',
            '                const estado_pas = window.pasajeros_autocompletado_estado[i];',
            '                if (estado_pas && estado_pas.duplicado) {',
            '                    continue;',
            '                }',
            '                indice_coincidente = i;',
            '                break;',
            '            }',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump del ?v= de ventas.js
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump del ?v= de ventas.js a 73x',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.73w"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.73x"></script>',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v73x',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73w (fix del',
            'caso restante del Bug 2: la atadura ahora copia en la dirección',
            'correcta cuando el comprador tiene datos y el pasajero está',
            'vacío, y resuelve conflictos por último escrito del usuario).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73x (dos bugs',
            'de la ligadura comprador-pasajero: no se activa con pasajeros',
            'duplicados y se limpian los campos al corregir el DNI por uno',
            'no registrado).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v73x al historial',
        'buscar' => [
            '- **v73w**: fix del caso restante del Bug 2. Cuando el comprador',
            '  tiene datos cargados y el pasajero está vacío (típico cuando',
            '  el comprador también viaja y no estaba registrado), la',
            '  activación de la atadura ahora copia en la dirección correcta.',
            '  La copia inicial en `_activar_atadura` es bidireccional',
            '  simétrica: rellena vacíos y, cuando hay conflicto entre',
            '  valores no vacíos, gana el lado que el usuario escribió por',
            '  última vez (registrado por campo en',
            '  `window.atadura_ultimo_campo`). Los listeners de atadura',
            '  también registran el último campo editado por el usuario.',
            '  Solo frontend.',
        ],
        'reemplazar' => [
            '- **v73w**: fix del caso restante del Bug 2. Cuando el comprador',
            '  tiene datos cargados y el pasajero está vacío (típico cuando',
            '  el comprador también viaja y no estaba registrado), la',
            '  activación de la atadura ahora copia en la dirección correcta.',
            '  La copia inicial en `_activar_atadura` es bidireccional',
            '  simétrica: rellena vacíos y, cuando hay conflicto entre',
            '  valores no vacíos, gana el lado que el usuario escribió por',
            '  última vez (registrado por campo en',
            '  `window.atadura_ultimo_campo`). Los listeners de atadura',
            '  también registran el último campo editado por el usuario.',
            '  Solo frontend.',
            '- **v73x**: dos bugs relacionados con la ligadura y la corrección',
            '  de DNI. (1) `_verificar_atadura_por_dni` ahora respeta el',
            '  estado `duplicado` del pasajero: si el pasajero está marcado',
            '  como duplicado (ya asignado a otro asiento del mismo viaje, o',
            '  repetido en otro formulario de esta misma venta), la atadura',
            '  no se activa. Además, si la atadura estaba apuntando a ese',
            '  pasajero, se rompe al momento de detectar el duplicado. (2)',
            '  Al corregir el DNI del pasajero por uno no registrado, se',
            '  limpian los campos no-DNI antes de habilitarlos (antes',
            '  quedaban los del DNI previo y se propagaban por ligadura).',
            '  Lo mismo para el comprador: si antes se habían autocompletado',
            '  datos desde otro DNI y el nuevo no está registrado, se limpian',
            '  los campos (`window.comprador_dni_con_datos` trackea el DNI',
            '  del último autocompletado exitoso). Se eliminó el botón',
            '  "Usar primer pasajero" del modal de venta: obsoleto desde que',
            '  la atadura funciona en ambos sentidos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ampliar la entrada del Bug 2 con v73x',
        'buscar' => [
            '**Bug 2 — Ligadura comprador ↔ pasajero en el alta de venta.**',
            '**Resuelto.** v73v fixeó los tres puntos donde se escribía',
            'pisando con vacíos. v73w fixeó el caso restante: cuando el',
            'comprador tiene datos y el pasajero está vacío (típico cuando',
            'el comprador también viaja y no estaba registrado), la atadura',
            'ahora copia en la dirección correcta. La copia inicial de',
            '`_activar_atadura` es bidireccional simétrica con resolución de',
            'conflictos por último escrito del usuario',
            '(`window.atadura_ultimo_campo`). Los listeners de atadura',
            'registran el último campo editado por el usuario en cada lado.',
            '',
            '**Bug 1 queda pendiente de resolver.**',
        ],
        'reemplazar' => [
            '**Bug 2 — Ligadura comprador ↔ pasajero en el alta de venta.**',
            '**Resuelto.** v73v fixeó los tres puntos donde se escribía',
            'pisando con vacíos. v73w fixeó el caso restante: cuando el',
            'comprador tiene datos y el pasajero está vacío (típico cuando',
            'el comprador también viaja y no estaba registrado), la atadura',
            'ahora copia en la dirección correcta. La copia inicial de',
            '`_activar_atadura` es bidireccional simétrica con resolución de',
            'conflictos por último escrito del usuario',
            '(`window.atadura_ultimo_campo`). Los listeners de atadura',
            'registran el último campo editado por el usuario en cada lado.',
            '',
            'v73x cerró dos bugs relacionados: (1) la atadura ya no se',
            'activa cuando el pasajero está marcado como duplicado (ya',
            'asignado a otro asiento del mismo viaje, o repetido en otro',
            'formulario de esta misma venta); (2) al corregir el DNI por',
            'uno no registrado, se limpian los campos autocompletados del',
            'pasajero y del comprador, para no arrastrar datos del DNI',
            'anterior. También se eliminó el botón "Usar primer pasajero",',
            'obsoleto.',
            '',
            '**Bug 1 queda pendiente de resolver.**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion',
        'buscar' => [
            '- Cerramos en v73w el caso restante del Bug 2: cuando el comprador',
            '  tiene datos y el pasajero está vacío, la atadura ahora copia en',
            '  la dirección correcta. La copia inicial es bidireccional con',
            '  prioridad al último escrito por el usuario.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73w el caso restante del Bug 2: cuando el comprador',
            '  tiene datos y el pasajero está vacío, la atadura ahora copia en',
            '  la dirección correcta. La copia inicial es bidireccional con',
            '  prioridad al último escrito por el usuario.',
            '- Cerramos en v73x dos bugs relacionados con la ligadura y la',
            '  corrección de DNI: (1) la atadura ya no se activa cuando el',
            '  pasajero está marcado como duplicado (ya asignado a otro asiento',
            '  o repetido en otro formulario); (2) al corregir el DNI por uno',
            '  no registrado, se limpian los campos del pasajero y del',
            '  comprador que habían sido autocompletados. Se eliminó el botón',
            '  "Usar primer pasajero" (obsoleto desde que la atadura funciona).',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73w (framework 1.5i.7f).',
            'Todo funcional. Bug 2 resuelto en su totalidad. Pendiente el Bug 1',
            '(opciones de cobro de cupones en dos niveles).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73x (framework 1.5i.7f).',
            'Todo funcional. Bug 2 resuelto en su totalidad, incluyendo los casos',
            'de duplicado y corrección de DNI. Pendiente el Bug 1 (opciones de',
            'cobro de cupones en dos niveles).',
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
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

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

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";