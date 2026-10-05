<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74v:
 *   - Fase 2, quinto flujo arreglado: cancelar_venta destruye
 *     el subárbol de la venta (asientos-en-venta, cupones con
 *     campos, campos del nodo venta, opciones_cobro).
 *   - Se mueve _destruir_campos_simples de Viaje.php a
 *     FuncionesAuxiliares.php para que Venta.php la use sin
 *     depender de Viaje.php.
 *   - Venta.php y Viaje.php agregan el include explícito de
 *     FuncionesAuxiliares.php.
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
    // FuncionesAuxiliares.php: agregar _destruir_campos_simples
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Bump @version a 1.5piloto.74v',
        'buscar' => [
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.74m',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.74v',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Agregar _destruir_campos_simples antes de Persistencia',
        'buscar' => [
            '// ============================================================',
            '// Persistencia',
            '// ============================================================',
            '',
            '/**',
            ' * Guarda una superestructura solo en SQL.',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Destrucción progresiva (Fase 2 del plan de optimización del grafo)',
            '// ============================================================',
            '',
            '/**',
            ' * Desenlaza y destruye los adyacentes de $padre que sean',
            ' * "campos simples": nodos sin adyacentes propios. No toca a',
            ' * los que sí tienen adyacentes (estructuras).',
            ' *',
            ' * Se usa desde los helpers de destrucción del piloto (por',
            ' * ejemplo _destruir_viaje_completo en Viaje.php y',
            ' * cancelar_venta en Venta.php). Se movió acá en v74v para',
            ' * que Venta.php la pueda usar sin depender de Viaje.php.',
            ' *',
            ' * @param Nodo  $padre',
            ' * @param array $excluir_enlaces Enlaces a NO tocar (referencias',
            ' *                               externas o circulares).',
            ' */',
            'function _destruir_campos_simples(Nodo $padre, array $excluir_enlaces = []): void {',
            '    $adyacentes = (array) $padre->adyacentes();',
            '    foreach ($adyacentes as $enlace => $nodo_hijo) {',
            '        $enlace = (string)$enlace;',
            '        if (in_array($enlace, $excluir_enlaces, true)) continue;',
            '        // Solo destruir si el hijo no tiene adyacentes propios.',
            '        $hijos_del_hijo = (array) $nodo_hijo->adyacentes();',
            '        if (!empty($hijos_del_hijo)) continue;',
            '',
            '        $padre->eliminar_adyacente($enlace);',
            '        Nodo::eliminar($nodo_hijo);',
            '    }',
            '}',
            '',
            '// ============================================================',
            '// Persistencia',
            '// ============================================================',
            '',
            '/**',
            ' * Guarda una superestructura solo en SQL.',
        ],
    ],

    // --------------------------------------------------------
    // Viaje.php: sacar _destruir_campos_simples + agregar include
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.74v',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74u',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74v',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            '',
            '/**',
            ' * Texto por defecto de la declaración jurada del pasajero mayor de 18 años',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Texto por defecto de la declaración jurada del pasajero mayor de 18 años',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Sacar _destruir_campos_simples (se movió a FuncionesAuxiliares)',
        'buscar' => [
            '/**',
            ' * Desenlaza y destruye los adyacentes de $padre que sean',
            ' * "campos simples": nodos sin adyacentes propios. No toca a',
            ' * los que sí tienen adyacentes (estructuras).',
            ' *',
            ' * Se llama al final de cada _destruir_*, después de procesar',
            ' * los hijos estructurales. Destruye los campos string (nombre,',
            ' * fecha, hora, monto, etc.) para no dejarlos huérfanos.',
            ' *',
            ' * @param Nodo  $padre',
            ' * @param array $excluir_enlaces Enlaces a NO tocar (referencias',
            ' *                               externas o circulares).',
            ' */',
            'function _destruir_campos_simples(Nodo $padre, array $excluir_enlaces = []): void {',
            '    $adyacentes = (array) $padre->adyacentes();',
            '    foreach ($adyacentes as $enlace => $nodo_hijo) {',
            '        $enlace = (string)$enlace;',
            '        if (in_array($enlace, $excluir_enlaces, true)) continue;',
            '        // Solo destruir si el hijo no tiene adyacentes propios.',
            '        $hijos_del_hijo = (array) $nodo_hijo->adyacentes();',
            '        if (!empty($hijos_del_hijo)) continue;',
            '',
            '        $padre->eliminar_adyacente($enlace);',
            '        Nodo::eliminar($nodo_hijo);',
            '    }',
            '}',
            '',
            '/**',
            ' * Destruye la lista circular de asientos de un piso.',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye la lista circular de asientos de un piso.',
        ],
    ],

    // --------------------------------------------------------
    // Venta.php: include + bump + cancelar_venta reescrita
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Bump @version a 1.5piloto.74v',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74a',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.74v',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            '',
            '/**',
            ' * Obtiene el contenedor de ventas de un dueño (raíz del árbol de ventas), creándolo si no existe.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Obtiene el contenedor de ventas de un dueño (raíz del árbol de ventas), creándolo si no existe.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Reescribir sección 2 (asientos-en-venta) de cancelar_venta',
        'buscar' => [
            '    // 2. Liberar asientos reales y eliminar los asientos-en-venta.',
            '    $asientos_liberados = 0;',
            '    $cabeza_asientos = $nodo_venta->adyacente(\'asientos\');',
            '    if ($cabeza_asientos) {',
            '        $asiento_venta = $cabeza_asientos->adyacente(\'primer\');',
            '        $seg = 0;',
            '        while ($asiento_venta && $seg < 200) {',
            '            $nodo_asiento_real = $asiento_venta->adyacente(\'asiento\');',
            '            if ($nodo_asiento_real) {',
            '                $estado = $nodo_asiento_real->adyacente(\'estado\');',
            '                if ($estado) $estado->_dato(\'libre\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'seleccionado_por\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'pasajero\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'venta\');',
            '                $asientos_liberados++;',
            '            }',
            '            $asiento_venta = $asiento_venta->adyacente(\'siguiente\');',
            '            $seg++;',
            '        }',
            '        while ($asiento_a_borrar = eliminar_hmi($cabeza_asientos)) {',
            '            Nodo::eliminar($asiento_a_borrar);',
            '        }',
            '        $nodo_venta->eliminar_adyacente(\'asientos\');',
            '        Nodo::eliminar($cabeza_asientos);',
            '    }',
        ],
        'reemplazar' => [
            '    // 2. Liberar asientos reales y destruir los asientos-en-venta.',
            '    //    La lista de asientos-en-venta es simple (primer/siguiente),',
            '    //    no árbol (hmi/hd/p). Antes se intentaba eliminar con',
            '    //    eliminar_hmi, que opera sobre hmi/hd: el while nunca',
            '    //    corría y los asientos-en-venta quedaban huérfanos.',
            '    //    Fase 2, v74v.',
            '    $asientos_liberados = 0;',
            '    $cabeza_asientos = $nodo_venta->adyacente(\'asientos\');',
            '    if ($cabeza_asientos) {',
            '        // Recolectar todos los asientos-en-venta y liberar los',
            '        // asientos reales (cambiarles el estado y desenlazar las',
            '        // referencias a pasajero y venta).',
            '        $asientos_venta_lista = [];',
            '        $asiento_venta = $cabeza_asientos->adyacente(\'primer\');',
            '        $seg = 0;',
            '        while ($asiento_venta && $seg < 200) {',
            '            $nodo_asiento_real = $asiento_venta->adyacente(\'asiento\');',
            '            if ($nodo_asiento_real) {',
            '                $estado = $nodo_asiento_real->adyacente(\'estado\');',
            '                if ($estado) $estado->_dato(\'libre\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'seleccionado_por\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'pasajero\');',
            '                $nodo_asiento_real->eliminar_adyacente(\'venta\');',
            '                $asientos_liberados++;',
            '            }',
            '            $asientos_venta_lista[] = $asiento_venta;',
            '            $asiento_venta = $asiento_venta->adyacente(\'siguiente\');',
            '            $seg++;',
            '        }',
            '',
            '        // Desenlazar la lista: `siguiente` de cada nodo y el',
            '        // `primer` de la cabeza. Después desenlazar la cabeza del',
            '        // propio nodo venta (que la referencia con `asientos`).',
            '        foreach ($asientos_venta_lista as $av) {',
            '            $av->eliminar_adyacente(\'siguiente\');',
            '        }',
            '        $cabeza_asientos->eliminar_adyacente(\'primer\');',
            '        $nodo_venta->eliminar_adyacente(\'asientos\');',
            '',
            '        // Destruir cada asiento-en-venta con sus campos. Las',
            '        // referencias a `asiento` y `pasajero` son a nodos',
            '        // compartidos: solo se desenlazan, no se destruyen.',
            '        foreach ($asientos_venta_lista as $av) {',
            '            _destruir_campos_simples($av, [\'asiento\', \'pasajero\']);',
            '            $av->eliminar_adyacente(\'asiento\');',
            '            $av->eliminar_adyacente(\'pasajero\');',
            '            Nodo::eliminar($av);',
            '        }',
            '',
            '        // Destruir la cabeza.',
            '        _destruir_campos_simples($cabeza_asientos);',
            '        Nodo::eliminar($cabeza_asientos);',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Reescribir sección 5 (cupones) de cancelar_venta',
        'buscar' => [
            '    // 5. Eliminar los cupones y su contenedor.',
            '    $contenedor_cupones = $nodo_venta->adyacente(\'cupones\');',
            '    if ($contenedor_cupones) {',
            '        while ($cupon_a_borrar = eliminar_hmi($contenedor_cupones)) {',
            '            Nodo::eliminar($cupon_a_borrar);',
            '        }',
            '        $nodo_venta->eliminar_adyacente(\'cupones\');',
            '        Nodo::eliminar($contenedor_cupones);',
            '    }',
        ],
        'reemplazar' => [
            '    // 5. Destruir los cupones y su contenedor.',
            '    //    Los cupones son un árbol hmi/hd/p, así que eliminar_hmi',
            '    //    sirve para desenlazarlos. Pero antes de destruir cada',
            '    //    cupón hay que desenlazar la referencia externa `rendido`',
            '    //    (apunta al Nodo Rendición, que sigue vivo) y destruir',
            '    //    sus campos hoja (numero, monto, estado, fecha_pago,',
            '    //    metodo_pago). Fase 2, v74v.',
            '    $contenedor_cupones = $nodo_venta->adyacente(\'cupones\');',
            '    if ($contenedor_cupones) {',
            '        while ($cupon_a_borrar = eliminar_hmi($contenedor_cupones)) {',
            '            $cupon_a_borrar->eliminar_adyacente(\'rendido\');',
            '            _destruir_campos_simples($cupon_a_borrar);',
            '            Nodo::eliminar($cupon_a_borrar);',
            '        }',
            '        $nodo_venta->eliminar_adyacente(\'cupones\');',
            '        _destruir_campos_simples($contenedor_cupones);',
            '        Nodo::eliminar($contenedor_cupones);',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Reescribir sección 8 (nodo venta) de cancelar_venta',
        'buscar' => [
            '    // 8. Eliminar el nodo venta entero.',
            '    Nodo::eliminar($nodo_venta);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
        ],
        'reemplazar' => [
            '    // 8. Destruir el nodo venta entero y sus campos hoja.',
            '    //    Antes de Nodo::eliminar hay que desenlazar las',
            '    //    referencias externas (comprador, viaje, micro,',
            '    //    terminal) y las estructurales ya no necesarias',
            '    //    (hd, hmi, p por si quedaron colgando), y destruir',
            '    //    el sub-nodo opciones_cobro con sus 4 campos.',
            '    //    Fase 2, v74v.',
            '    $nodo_venta->eliminar_adyacente(\'comprador\');',
            '    $nodo_venta->eliminar_adyacente(\'viaje\');',
            '    $nodo_venta->eliminar_adyacente(\'micro\');',
            '    $nodo_venta->eliminar_adyacente(\'terminal\');',
            '    $nodo_venta->eliminar_adyacente(\'hmi\');',
            '    $nodo_venta->eliminar_adyacente(\'hd\');',
            '    $nodo_venta->eliminar_adyacente(\'p\');',
            '',
            '    // Destruir el sub-nodo opciones_cobro (si existe).',
            '    $nodo_opciones_cobro = $nodo_venta->adyacente(\'opciones_cobro\');',
            '    if ($nodo_opciones_cobro) {',
            '        _destruir_campos_simples($nodo_opciones_cobro);',
            '        $nodo_venta->eliminar_adyacente(\'opciones_cobro\');',
            '        Nodo::eliminar($nodo_opciones_cobro);',
            '    }',
            '',
            '    // Destruir los campos hoja restantes del propio nodo venta',
            '    // (fecha_hora, fecha_ultimo_pago, metodo_pago, total,',
            '    // cuotas, pagado, cuotas_restantes).',
            '    _destruir_campos_simples($nodo_venta);',
            '',
            '    Nodo::eliminar($nodo_venta);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74v',
        'buscar' => [
            '- **v74u**: Fase 2, tercer y cuarto flujo arreglados.',
        ],
        'reemplazar' => [
            '- **v74v**: Fase 2, quinto flujo arreglado:',
            '  `cancelar_venta`. Antes dejaba huérfanos los asientos-',
            '  en-venta (el `eliminar_hmi` no aplicaba a la lista',
            '  simple), los campos de cada cupón, los campos del nodo',
            '  venta y el sub-nodo `opciones_cobro` completo (~20-36',
            '  nodos por venta cancelada). Ahora los destruye.',
            '  `_destruir_campos_simples` se mueve de `Viaje.php` a',
            '  `FuncionesAuxiliares.php` para que `Venta.php` la use',
            '  sin depender de `Viaje.php`.',
            '- **v74u**: Fase 2, tercer y cuarto flujo arreglados.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar quinto flujo',
        'buscar' => [
            '**Tercer y cuarto flujo arreglados en v74u:**',
        ],
        'reemplazar' => [
            '**Quinto flujo arreglado en v74v:** `cancelar_venta`.',
            'Antes dejaba huérfanos los asientos-en-venta (la lista',
            'cuelga con `primer`/`siguiente`, no con `hmi`/`hd`, así',
            'que `eliminar_hmi` no los alcanzaba), los campos de cada',
            'cupón, los campos hoja del nodo venta y el sub-nodo',
            '`opciones_cobro` con sus 4 hijos. Ahora los destruye',
            'explícitamente. `_destruir_campos_simples` vive en',
            '`FuncionesAuxiliares.php` (antes estaba en `Viaje.php`)',
            'para que `Venta.php` la use sin dependencia circular.',
            '',
            '**Tercer y cuarto flujo arreglados en v74u:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74v',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74u',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74v',
            '(Fase 2, quinto flujo arreglado: `cancelar_venta`. Ahora',
            'destruye los asientos-en-venta, los cupones con sus',
            'campos, los campos hoja del nodo venta y el sub-nodo',
            '`opciones_cobro`. `_destruir_campos_simples` se movió a',
            '`FuncionesAuxiliares.php`.).',
            'Antes: v1.5piloto.74u',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74v',
        'buscar' => [
            '- Cerramos en v74u los flujos 3 y 4 de la Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74v el quinto flujo de Fase 2:',
            '  `cancelar_venta`. Ahora destruye los asientos-en-venta,',
            '  los campos de cada cupón, los campos hoja del nodo',
            '  venta y el sub-nodo `opciones_cobro`. El helper',
            '  `_destruir_campos_simples` se movió de `Viaje.php` a',
            '  `FuncionesAuxiliares.php`.',
            '- Cerramos en v74u los flujos 3 y 4 de la Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74v',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74u (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74v (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74v',
        'buscar' => [
            'v74u: flujos 3 y 4 de Fase 2 (`eliminar_terminal_autorizada`',
            'y `_guardar_paradas_intermedias`).',
        ],
        'reemplazar' => [
            'v74u: flujos 3 y 4 de Fase 2 (`eliminar_terminal_autorizada`',
            'y `_guardar_paradas_intermedias`). v74v: flujo 5',
            '(`cancelar_venta`).',
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