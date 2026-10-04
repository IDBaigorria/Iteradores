<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74o: limpieza de pasajeros de prueba + control de modo.
 *
 * - index.php establece Entorno::MODO_PRUEBAS/ MODO_PRODUCCION según
 *   Conf::LOCAL, e inyecta window.entorno_es_pruebas en el HTML.
 * - Nuevo endpoint pasajeros/limpiar_prueba (admin + modo pruebas).
 * - Nuevo endpoint entorno/info.
 * - Nuevo botón "Limpiar pasajeros de prueba" en la pestaña Pasajeros.
 * - El botón "Limpiar viajes de prueba" pasa a exigir modo pruebas.
 * - Se anota la fuga de nodos como pendiente de prioridad alta.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // index.php — agregar use Entorno
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: agregar use de Entorno',
        'buscar' => [
            'use Iteradores\Controlador\Controlador;',
            'use Iteradores\Configuracion\Conf;',
            'use Iteradores\Nodos\Nodo;',
        ],
        'reemplazar' => [
            'use Iteradores\Controlador\Controlador;',
            'use Iteradores\Configuracion\Conf;',
            'use Iteradores\Configuracion\Entorno;',
            'use Iteradores\Nodos\Nodo;',
        ],
    ],

    // ========================================================
    // index.php — establecer modo
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: establecer modo segun Conf::LOCAL',
        'buscar' => [
            '// Inicialización de la persistencia.',
            '// Va después de los require_once para que `guardar_ambos` esté',
            '// definida (vive en FuncionesAuxiliares.php).',
            'Controlador::establecer_metodo(\'SQL\');',
        ],
        'reemplazar' => [
            '// Modo de ejecución. En local se considera modo pruebas',
            '// (habilita los botones de limpieza del admin). En',
            '// producción, modo producción (los botones quedan',
            '// ocultos porque el HTML no incluye la bandera JS).',
            'Entorno::establecer_modo(Conf::LOCAL ? Entorno::MODO_PRUEBAS : Entorno::MODO_PRODUCCION);',
            '',
            '// Inicialización de la persistencia.',
            '// Va después de los require_once para que `guardar_ambos` esté',
            '// definida (vive en FuncionesAuxiliares.php).',
            'Controlador::establecer_metodo(\'SQL\');',
        ],
    ],

    // ========================================================
    // index.php — inyectar bandera en HTML
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: inyectar bandera modo pruebas en HTML',
        'buscar' => [
            '// Si es GET, mostrar la interfaz',
            'readfile(__DIR__ . \'/aplicacion_GET.html\');',
        ],
        'reemplazar' => [
            '// Si es GET, mostrar la interfaz.',
            '// Se inyecta el modo de pruebas en el HTML (placeholder',
            '// MODO_PRUEBAS_PLACEHOLDER en aplicacion_GET.html). Los',
            '// botones de limpieza del admin consultan esa bandera',
            '// vía `window.entorno_es_pruebas`.',
            '$html = file_get_contents(__DIR__ . \'/aplicacion_GET.html\');',
            '$es_pruebas_js = Entorno::es_pruebas() ? \'true\' : \'false\';',
            '$html = str_replace(',
            '    \'<!-- MODO_PRUEBAS_PLACEHOLDER -->\',',
            '    \'<script>window.entorno_es_pruebas = \' . $es_pruebas_js . \';</script>\',',
            '    $html',
            ');',
            'echo $html;',
        ],
    ],

    // ========================================================
    // aplicacion_GET.html — placeholder + boton de pasajeros
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: placeholder modo pruebas + boton limpiar pasajeros',
        'buscar' => [
            '        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">',
            '          <h2 style="margin:0;">Pasajeros / Clientes</h2>',
            '          <button class="btn primary" id="boton_agregar_pasajero">Agregar pasajero/cliente</button>',
            '        </div>',
        ],
        'reemplazar' => [
            '        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">',
            '          <h2 style="margin:0;">Pasajeros / Clientes</h2>',
            '          <div style="display:flex; gap:8px;">',
            '            <button class="btn primary" id="boton_agregar_pasajero">Agregar pasajero/cliente</button>',
            '            <button class="btn danger" id="boton_limpiar_pasajeros_prueba" style="display:none;">Limpiar pasajeros de prueba</button>',
            '          </div>',
            '        </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: agregar placeholder antes de los scripts',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.74f"></script>',
        ],
        'reemplazar' => [
            '<!-- MODO_PRUEBAS_PLACEHOLDER -->',
            '<script src="aplicacion.js?v=1.5piloto.74f"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de pasajeros.js a 1.5piloto.74o',
        'buscar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.73d"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.74o"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de viajes-nucleo.js a 1.5piloto.74o',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.74n"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.74o"></script>',
        ],
    ],

    // ========================================================
    // Pasajero.php — nueva funcion limpiar_pasajeros_de_prueba
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero: agregar limpiar_pasajeros_de_prueba',
        'buscar' => [
            'use Iteradores\Nodos\Nodo;',
            'use Iteradores\Controlador\Controlador;',
            'use Iteradores\Configuracion\Conf;',
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
        ],
        'reemplazar' => [
            'use Iteradores\Nodos\Nodo;',
            'use Iteradores\Controlador\Controlador;',
            'use Iteradores\Configuracion\Conf;',
            'use Iteradores\Configuracion\Entorno;',
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/Ventas/Venta.php");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero: funcion limpiar_pasajeros_de_prueba',
        'buscar' => [
            '/**',
            ' * Determina si la fecha de un viaje corresponde a un viaje activo.',
        ],
        'reemplazar' => [
            '/**',
            ' * Elimina los pasajeros "de prueba" de un dueño.',
            ' *',
            ' * Criterio: el email termina en "@test.local". Esa es la',
            ' * marca que dejan las pruebas automáticas del plugin. Los',
            ' * pasajeros reales no usan ese dominio.',
            ' *',
            ' * Conserva los pasajeros que tengan referencias entrantes:',
            ' * ventas (comprador o asiento con pasajero) o reservas',
            ' * (asientos de micros de viajes). Nodo::eliminar falla si',
            ' * hay referencias entrantes; el enlace del contenedor se',
            ' * rompería igual pero el nodo quedaría huérfano. Preferimos',
            ' * conservar y avisar.',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' */',
            'function limpiar_pasajeros_de_prueba(string $nombre_dueno): array {',
            '    if (!Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado o sin pasajeros\'];',
            '    }',
            '',
            '    // Pre-pasada 1: DNIs referenciados desde ventas.',
            '    $dnis_referenciados = [];',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if ($contenedor_ventas) {',
            '        $venta = hmi($contenedor_ventas);',
            '        $seg = 0;',
            '        while ($venta && $seg < 500) {',
            '            $comprador = $venta->adyacente(\'comprador\');',
            '            if ($comprador) $dnis_referenciados[$comprador->dato()] = true;',
            '',
            '            $cabeza_asientos = $venta->adyacente(\'asientos\');',
            '            if ($cabeza_asientos) {',
            '                $asiento = $cabeza_asientos->adyacente(\'primer\');',
            '                $seg2 = 0;',
            '                while ($asiento && $seg2 < 200) {',
            '                    $pas = $asiento->adyacente(\'pasajero\');',
            '                    if ($pas) $dnis_referenciados[$pas->dato()] = true;',
            '                    $asiento = $asiento->adyacente(\'siguiente\');',
            '                    $seg2++;',
            '                }',
            '            }',
            '            $venta = hd($venta);',
            '            $seg++;',
            '        }',
            '    }',
            '',
            '    // Pre-pasada 2: DNIs referenciados desde asientos de micros',
            '    // (reservas del equipo).',
            '    $contenedor_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if ($contenedor_viajes) {',
            '        $adyacentes_viajes = (array) $contenedor_viajes->adyacentes();',
            '        foreach ($adyacentes_viajes as $nodo_viaje) {',
            '            $micros = $nodo_viaje->adyacente(\'micros\');',
            '            if (!$micros) continue;',
            '            $adyacentes_micros = (array) $micros->adyacentes();',
            '            foreach ($adyacentes_micros as $nodo_micro) {',
            '                $copia = $nodo_micro->adyacente(\'vehiculo_copia\');',
            '                if (!$copia) continue;',
            '                $asientos = $copia->adyacente(\'asientos\');',
            '                if (!$asientos) continue;',
            '                for ($i = 1; $i <= 2; $i++) {',
            '                    $piso = $asientos->adyacente("piso_$i");',
            '                    if (!$piso) continue;',
            '                    $cabeza = $piso->adyacente(\'asientos\');',
            '                    if (!$cabeza) continue;',
            '                    $asiento = $cabeza->adyacente(\'primer\');',
            '                    $seg3 = 0;',
            '                    while ($asiento && $asiento->id() !== $cabeza->id() && $seg3 < 200) {',
            '                        $pas = $asiento->adyacente(\'pasajero\');',
            '                        if ($pas) $dnis_referenciados[$pas->dato()] = true;',
            '                        $asiento = $asiento->adyacente(\'siguiente\');',
            '                        $seg3++;',
            '                    }',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    // Loop principal.',
            '    $adyacentes = (array) $contenedor->adyacentes();',
            '    $borrados = [];',
            '    $conservados_con_referencias = [];',
            '    $conservados_no_prueba = [];',
            '',
            '    foreach ($adyacentes as $dni => $nodo_pasajero) {',
            '        $dni = (string)$dni;',
            '        $nodo_email = $nodo_pasajero->adyacente(\'email\');',
            '        $email = $nodo_email ? $nodo_email->dato() : \'\';',
            '',
            '        // ¿Es de prueba?',
            '        if (substr($email, -11) !== \'@test.local\') {',
            '            $conservados_no_prueba[] = $dni;',
            '            continue;',
            '        }',
            '',
            '        // ¿Tiene referencias entrantes?',
            '        if (isset($dnis_referenciados[$dni])) {',
            '            $conservados_con_referencias[] = $dni;',
            '            continue;',
            '        }',
            '',
            '        // Desenlazar y eliminar. Si Nodo::eliminar falla',
            '        // (referencias residuales que se nos escaparon), el',
            '        // enlace ya está roto y el nodo queda huérfano.',
            '        $contenedor->eliminar_adyacente($dni);',
            '        Nodo::eliminar($nodo_pasajero);',
            '        $borrados[] = $dni;',
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'conservados_con_referencias\' => $conservados_con_referencias,',
            '        \'conservados_no_prueba\' => $conservados_no_prueba,',
            '    ];',
            '}',
            '',
            '/**',
            ' * Determina si la fecha de un viaje corresponde a un viaje activo.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero: bump @version a 1.5piloto.74o',
        'buscar' => [
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74o',
        ],
    ],

    // ========================================================
    // Enrutador.php — nueva subaccion pasajeros/limpiar_prueba
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar pasajeros/limpiar_prueba',
        'buscar' => [
            '                case \'eliminar\':',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $dni = $post[\'dni\'] ?? \'\';',
            '                    if (empty($nombre_dueno) || empty($dni)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño y DNI son obligatorios\']);',
            '                    }',
            '                    $resultado = eliminar_pasajero($nombre_dueno, $dni);',
            '                    responder_json($resultado);',
            '                    break;',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de pasajeros no válida\']);',
        ],
        'reemplazar' => [
            '                case \'eliminar\':',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $dni = $post[\'dni\'] ?? \'\';',
            '                    if (empty($nombre_dueno) || empty($dni)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño y DNI son obligatorios\']);',
            '                    }',
            '                    $resultado = eliminar_pasajero($nombre_dueno, $dni);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'limpiar_prueba\':',
            '                    // Solo admin y solo en modo pruebas.',
            '                    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\']);',
            '                    }',
            '                    $nombre_sol_lpp = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $raiz_sol_lpp = Nodo::nodo_por_id(\'usuarios\');',
            '                    $nodo_sol_lpp = ($raiz_sol_lpp && $nombre_sol_lpp !== \'\') ? $raiz_sol_lpp->adyacente($nombre_sol_lpp) : null;',
            '                    $nodo_nivel_lpp = $nodo_sol_lpp ? $nodo_sol_lpp->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_lpp = $nodo_nivel_lpp ? $nodo_nivel_lpp->dato() : \'\';',
            '                    if ($nivel_sol_lpp !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_lpp = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_lpp)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_lpp = limpiar_pasajeros_de_prueba($nombre_dueno_lpp);',
            '                    responder_json($resultado_lpp);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de pasajeros no válida\']);',
        ],
    ],

    // ========================================================
    // Enrutador.php — nueva subaccion entorno/info
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar entorno/info',
        'buscar' => [
            '        default:',
            '            responder_json([\'exito\' => false, \'error\' => \'Módulo no reconocido\']);',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '        case \'entorno\':',
            '            switch ($subaccion) {',
            '                case \'info\':',
            '                    responder_json([',
            '                        \'exito\' => true,',
            '                        \'modo\' => \\Iteradores\\Configuracion\\Entorno::modo(),',
            '                        \'es_pruebas\' => \\Iteradores\\Configuracion\\Entorno::es_pruebas(),',
            '                    ]);',
            '                    break;',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de entorno no válida\']);',
            '            }',
            '            break;',
            '',
            '        default:',
            '            responder_json([\'exito\' => false, \'error\' => \'Módulo no reconocido\']);',
            '    }',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: bump @version a 1.5piloto.74o',
        'buscar' => [
            ' * @version   1.5piloto.74n',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74o',
        ],
    ],

    // ========================================================
    // pasajeros.js — agregar funciones de limpieza
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: funcion limpiar_pasajeros_de_prueba_ui + visibilidad',
        'buscar' => [
            '// ===== Inicializacion de listeners del panel Pasajeros =====',
            '(function() {',
            '    const btn = document.getElementById(\'boton_agregar_pasajero\');',
            '    if (btn) btn.addEventListener(\'click\', abrir_modal_agregar_pasajero);',
            '})();',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Limpieza de pasajeros de prueba (solo admin, modo pruebas).',
            '// ============================================================',
            '',
            'function _actualizar_visibilidad_boton_limpiar_pasajeros() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    // Solo en modo pruebas (bandera inyectada por index.php).',
            '    if (window.entorno_es_pruebas !== true) return;',
            '    const btn = document.getElementById(\'boton_limpiar_pasajeros_prueba\');',
            '    if (!btn) return;',
            '    // Solo cuando hay un dueño seleccionado.',
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    btn.style.display = nombre_dueno ? \'inline-block\' : \'none\';',
            '}',
            '',
            'async function limpiar_pasajeros_de_prueba_ui() {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') {',
            '        mostrar_aviso("Solo el admin puede ejecutar esta acción", \'error\');',
            '        return;',
            '    }',
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso("Seleccione un dueño primero", \'error\');',
            '        return;',
            '    }',
            '    const ok = confirm(',
            '        "¿Eliminar todos los pasajeros de prueba del dueño \\"" + nombre_dueno + "\\"?\\n\\n"',
            '        + "Se consideran de prueba los que tienen email @test.local.\\n"',
            '        + "Se conservan los que tengan ventas o reservas asociadas.\\n\\n"',
            '        + "Esta acción no se puede deshacer."',
            '    );',
            '    if (!ok) return;',
            '',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "pasajeros/limpiar_prueba",',
            '            nombre_dueno,',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al limpiar pasajeros", \'error\');',
            '        return;',
            '    }',
            '    let msg = "Se eliminaron " + datos.cantidad_borrados + " pasajero(s) de prueba.";',
            '    const cons = (datos.conservados_con_referencias || []).length;',
            '    if (cons > 0) {',
            '        msg += " " + cons + " se conservaron por tener ventas o reservas.";',
            '    }',
            '    mostrar_aviso(msg, \'exito\');',
            '    await cargar_pasajeros();',
            '}',
            '',
            '// ===== Inicializacion de listeners del panel Pasajeros =====',
            '(function() {',
            '    const btn = document.getElementById(\'boton_agregar_pasajero\');',
            '    if (btn) btn.addEventListener(\'click\', abrir_modal_agregar_pasajero);',
            '',
            '    const btn_limpiar = document.getElementById(\'boton_limpiar_pasajeros_prueba\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', limpiar_pasajeros_de_prueba_ui);',
            '})();',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: llamar a visibilidad en cargar_pasajeros',
        'buscar' => [
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        mostrar_aviso(\'Seleccione un dueño para ver pasajeros\', \'info\');',
            '        return;',
            '    }',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "pasajeros/listar", nombre_dueno })',
            '    });',
        ],
        'reemplazar' => [
            '    const nombre_dueno = obtener_nombre_dueno_pasajeros();',
            '    if (!nombre_dueno) {',
            '        _actualizar_visibilidad_boton_limpiar_pasajeros();',
            '        mostrar_aviso(\'Seleccione un dueño para ver pasajeros\', \'info\');',
            '        return;',
            '    }',
            '',
            '    // Actualizar visibilidad del botón de limpieza (solo admin + modo pruebas).',
            '    _actualizar_visibilidad_boton_limpiar_pasajeros();',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "pasajeros/listar", nombre_dueno })',
            '    });',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/pasajeros.js',
        'descripcion' => 'pasajeros.js: bump @version a 1.5piloto.74o',
        'buscar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.66',
        ],
        'reemplazar' => [
            ' * Funciones del panel de pasajeros/clientes.',
            ' * @version 1.5piloto.74o',
        ],
    ],

    // ========================================================
    // viajes-nucleo.js — visibilidad del boton de viajes
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: exigir modo pruebas para el boton de limpieza',
        'buscar' => [
            'function _actualizar_visibilidad_boton_limpiar_viajes(visible) {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    const btn = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (!btn) return;',
            '    btn.style.display = visible ? \'inline-block\' : \'none\';',
            '}',
        ],
        'reemplazar' => [
            'function _actualizar_visibilidad_boton_limpiar_viajes(visible) {',
            '    if (!usuario_actual || usuario_actual.nivel !== \'admin\') return;',
            '    // Solo en modo pruebas (bandera inyectada por index.php).',
            '    if (window.entorno_es_pruebas !== true) return;',
            '    const btn = document.getElementById(\'boton_limpiar_viajes_prueba\');',
            '    if (!btn) return;',
            '    btn.style.display = visible ? \'inline-block\' : \'none\';',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'viajes-nucleo: bump @version a 1.5piloto.74o',
        'buscar' => [
            ' * @version 1.5piloto.74n',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.74o',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.12 Pasajero.php
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.12 agregar limpiar_pasajeros_de_prueba',
        'buscar' => [
            '- `obtener_reservas_de_pasajero`, `formatear_venta_para_pasajero`,',
            '  `eliminar_pasajero`.',
        ],
        'reemplazar' => [
            '- `obtener_reservas_de_pasajero`, `formatear_venta_para_pasajero`,',
            '  `eliminar_pasajero`.',
            '- `limpiar_pasajeros_de_prueba($nombre_dueno)`: elimina los',
            '  pasajeros cuyo email termina en `@test.local` (marca que',
            '  dejan las pruebas del plugin). Conserva los que tengan',
            '  referencias entrantes (ventas o reservas). Disponible solo',
            '  en modo pruebas. Pensada para el botón de limpieza del',
            '  admin.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.14 Enrutador
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.14 agregar pasajeros/limpiar_prueba y entorno/info',
        'buscar' => [
            'Subacción especial: `viajes/limpiar_prueba` (solo admin).',
            'Elimina los viajes de prueba del dueño seleccionado.',
        ],
        'reemplazar' => [
            'Subacciones especiales:',
            '- `viajes/limpiar_prueba` (admin + modo pruebas): elimina los',
            '  viajes de prueba del dueño seleccionado.',
            '- `pasajeros/limpiar_prueba` (admin + modo pruebas): elimina',
            '  los pasajeros con email `@test.local` que no tengan',
            '  referencias entrantes.',
            '- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin',
            '  permisos. Lo consume el frontend para saber si mostrar los',
            '  botones de limpieza.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — historial v74o
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: agregar v74o al historial',
        'buscar' => [
            '- **v74n**: botón "Limpiar viajes de prueba" en la pestaña',
        ],
        'reemplazar' => [
            '- **v74o**: botón "Limpiar pasajeros de prueba" en la pestaña',
            '  Pasajeros/Clientes. Nueva función',
            '  `limpiar_pasajeros_de_prueba($nombre_dueno)` en',
            '  `Pasajero.php` y subacción `pasajeros/limpiar_prueba` en el',
            '  enrutador. Criterio: email termina en `@test.local`.',
            '  Conserva los pasajeros con referencias entrantes (ventas',
            '  o reservas). También se agregó: `index.php` establece',
            '  `Entorno::MODO_PRUEBAS` o `MODO_PRODUCCION` según',
            '  `Conf::LOCAL` e inyecta `window.entorno_es_pruebas` en el',
            '  HTML; nueva subacción `entorno/info`; los botones de',
            '  limpieza (viajes y pasajeros) ahora aparecen solo si el',
            '  admin está en modo pruebas.',
            '- **v74n**: botón "Limpiar viajes de prueba" en la pestaña',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 cabecera
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 cabecera a v74o',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74n',
            '(botón "Limpiar viajes de prueba" en la pestaña Viajes para',
            'el admin. Nueva función `limpiar_viajes_de_prueba` en',
            '`Viaje.php` y subacción `viajes/limpiar_prueba` en el',
            'enrutador. Conserva el viaje principal y los que no tengan',
            'prefijo de prueba. Motivo: las pruebas del plugin acumulan',
            'viajes que ralentizan `cargar_viajes`).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74o',
            '(botón "Limpiar pasajeros de prueba" en la pestaña',
            'Pasajeros/Clientes. Nueva función',
            '`limpiar_pasajeros_de_prueba` en `Pasajero.php` y subacción',
            '`pasajeros/limpiar_prueba`. Los botones de limpieza (viajes',
            'y pasajeros) ahora aparecen solo si el admin está en modo',
            'pruebas (`Entorno::es_pruebas()`). Nuevo endpoint',
            '`entorno/info` y bandera `window.entorno_es_pruebas`',
            'inyectada por `index.php`).',
            'Antes: v1.5piloto.74n',
            '(botón "Limpiar viajes de prueba" en la pestaña Viajes para',
            'el admin. Nueva función `limpiar_viajes_de_prueba` en',
            '`Viaje.php` y subacción `viajes/limpiar_prueba` en el',
            'enrutador. Conserva el viaje principal y los que no tengan',
            'prefijo de prueba. Motivo: las pruebas del plugin acumulan',
            'viajes que ralentizan `cargar_viajes`).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar v74o al estado',
        'buscar' => [
            '- Cerramos en v74n el botón "Limpiar viajes de prueba" para',
            '  el admin. El grafo del dueño `carmen1` tenía 21 viajes',
            '  (20 de ellos de pruebas anteriores), y `formatear_viaje`',
            '  escala con V × W (viajes × ventas). El botón borra los',
            '  viajes de prueba conservando el viaje principal',
            '  "Peregrinación a la Visita del Papa León XIV a Luján" y',
            '  los que no tengan prefijo de prueba.',
        ],
        'reemplazar' => [
            '- Cerramos en v74o el botón "Limpiar pasajeros de prueba"',
            '  para el admin (solo en modo pruebas). Criterio: email',
            '  termina en `@test.local`. Conserva los que tienen',
            '  referencias entrantes. También: `index.php` establece',
            '  `Entorno::MODO_PRUEBAS`/`MODO_PRODUCCION` según',
            '  `Conf::LOCAL`, e inyecta `window.entorno_es_pruebas` en',
            '  el HTML. Los dos botones de limpieza (viajes y pasajeros)',
            '  aparecen solo si el admin está en modo pruebas.',
            '- **Decisión anotada como pendiente de prioridad alta:**',
            '  **fuga de nodos**. Cuando se elimina una venta, un',
            '  pasajero, un viaje o cualquier entidad, el nodo se',
            '  descuelga del contenedor pero no se destruye. Como el',
            '  framework no permite eliminar un nodo con referencias',
            '  entrantes, esos nodos quedan huérfanos y el grafo crece',
            '  indefinidamente. Afecta la performance de todo. Los',
            '  botones de limpieza son paliativos. La solución de fondo',
            '  requiere auditar cada flujo de eliminación y desenlazar',
            '  progresivamente antes de llamar a `Nodo::eliminar`.',
            '  También conviene revisar si el framework podría soportar',
            '  carga parcial del grafo (traer solo la rama de interés',
            '  en vez del grafo entero).',
            '- Cerramos en v74n el botón "Limpiar viajes de prueba" para',
            '  el admin. El grafo del dueño `carmen1` tenía 21 viajes',
            '  (20 de ellos de pruebas anteriores), y `formatear_viaje`',
            '  escala con V × W (viajes × ventas). El botón borra los',
            '  viajes de prueba conservando el viaje principal',
            '  "Peregrinación a la Visita del Papa León XIV a Luján" y',
            '  los que no tengan prefijo de prueba.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §13 estado al cierre
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §13 estado a v74o con fuga de nodos',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74n (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático. Fix de',
            'v74n: botón "Limpiar viajes de prueba" para el admin, que',
            'borra los viajes acumulados por las pruebas del plugin',
            '(conserva el viaje principal). El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.5d) tiene 29 pruebas',
            'corriendo.',
            '',
            '**Deuda técnica pendiente:** `formatear_viaje` en `Viaje.php`',
            'escala como O(V × W): por cada viaje, recorre todas las ventas',
            'del dueño para calcular `viaje_tiene_ventas` y',
            '`vendidos_por_micró`. Con muchos viajes y ventas, el costo',
            'crece. La limpieza de viajes mitiga el problema pero no lo',
            'elimina. La optimización real (índice de ventas por viaje +',
            'cacheo de contadores) queda para una tanda dedicada.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74o (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático. Fix de',
            'v74n: botón "Limpiar viajes de prueba". Fix de v74o: botón',
            '"Limpiar pasajeros de prueba" y control de modo',
            '(`Entorno::es_pruebas()`). El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.5d) tiene 29 pruebas',
            'corriendo.',
            '',
            '**Deuda técnica pendiente (prioridad alta):**',
            '**fuga de nodos**. Cuando se elimina una venta, un pasajero,',
            'un viaje o cualquier entidad, el nodo se descuelga del',
            'contenedor pero no se destruye. Como el framework no',
            'permite eliminar un nodo con referencias entrantes, esos',
            'nodos quedan huérfanos y el grafo crece indefinidamente.',
            'La aplicación se vuelve lenta porque todo itera sobre el',
            'grafo completo. La limpieza manual (botones de admin) es',
            'paliativa. La solución requiere auditar cada flujo de',
            'eliminación y desenlazar progresivamente antes de llamar',
            'a `Nodo::eliminar`. Requiere también revisar la',
            'arquitectura del framework para permitir carga parcial',
            'del grafo (cargar solo la rama de interés).',
            '',
            '**Deuda técnica pendiente (prioridad media):**',
            '`formatear_viaje` en `Viaje.php` escala como O(V × W): por',
            'cada viaje, recorre todas las ventas del dueño. Optimización',
            'real pendiente: índice de ventas por viaje + cacheo de',
            'contadores.',
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