<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76s (Fase B2.2.2 del modelo topológico).
 * Corrección: `_construir_indice_ventas_por_viaje` vive en Viaje.php,
 * no en Venta.php.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Venta.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.76s',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.75a',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.76s',
        ],
    ],

    // ============================================================
    // Venta.php — obtener_contenedor_ventas_dueno con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: obtener_contenedor_ventas_dueno con contexto',
        'buscar' => [
            '/**',
            ' * Obtiene el contenedor de ventas de un dueño (raíz del árbol de ventas), creándolo si no existe.',
            ' */',
            'function obtener_contenedor_ventas_dueno(string $nombre_dueno) {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '',
            '    $nodo_ventas = $nodo_dueno->adyacente(\'ventas\');',
            '    if (!$nodo_ventas) {',
            '        $nodo_ventas = Nodo::crear_con_dato(\'\');',
            '        $nodo_dueno->_adyacente_en($nodo_ventas, \'ventas\');',
            '    }',
            '    return $nodo_ventas;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Obtiene el contenedor de ventas de un dueño (raíz del árbol de ventas), creándolo si no existe.',
            ' *',
            ' * A partir de v76s (Fase B2.2.2 del modelo topológico): acepta',
            ' * un `?Nodo $nodo_contexto` opcional. Si viene, navega desde',
            ' * ahí en lugar de resolver `usuarios → dueño`. Mismo patrón',
            ' * que `obtener_contenedor_viajes_dueno` (v76r).',
            ' *',
            ' * @param string    $nombre_dueno',
            ' * @param Nodo|null $nodo_contexto',
            ' * @return Nodo|null',
            ' */',
            'function obtener_contenedor_ventas_dueno(string $nombre_dueno, ?Nodo $nodo_contexto = null) {',
            '    if ($nodo_contexto === null) {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) return null;',
            '',
            '        $nodo_contexto = $raiz_usuarios->adyacente($nombre_dueno);',
            '    }',
            '    if (!$nodo_contexto) return null;',
            '',
            '    $nodo_ventas = $nodo_contexto->adyacente(\'ventas\');',
            '    if (!$nodo_ventas) {',
            '        $nodo_ventas = Nodo::crear_con_dato(\'\');',
            '        $nodo_contexto->_adyacente_en($nodo_ventas, \'ventas\');',
            '    }',
            '    return $nodo_ventas;',
            '}',
        ],
    ],

    // ============================================================
    // Venta.php — _buscar_venta_por_id con filtro por terminal
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: _buscar_venta_por_id con filtro opcional',
        'buscar' => [
            'function _buscar_venta_por_id(string $id_venta): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [null, \'\'];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return [$actual, $nombre_dueno];',
            '            }',
            '            $actual = hd($actual);',
            '            $seg++;',
            '        }',
            '    }',
            '    return [null, \'\'];',
            '}',
        ],
        'reemplazar' => [
            'function _buscar_venta_por_id(string $id_venta, ?string $nombre_terminal = null): array {',
            '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
            '    // a su contexto (hoy el nodo del dueño, tras B2.3 el',
            '    // compartido). Acelera la búsqueda y prepara el aislamiento.',
            '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
            '        $contexto = _contexto_terminal($nombre_terminal);',
            '        if (!$contexto) return [null, \'\'];',
            '        $nombre_dueno = (string)$contexto->dato();',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno, $contexto);',
            '        if (!$cont) return [null, \'\'];',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return [$actual, $nombre_dueno];',
            '            }',
            '            $actual = hd($actual);',
            '            $seg++;',
            '        }',
            '        return [null, \'\'];',
            '    }',
            '',
            '    // Sin filtro: comportamiento actual, buscar en todos los dueños.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [null, \'\'];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return [$actual, $nombre_dueno];',
            '            }',
            '            $actual = hd($actual);',
            '            $seg++;',
            '        }',
            '    }',
            '    return [null, \'\'];',
            '}',
        ],
    ],

    // ============================================================
    // Venta.php — obtener_venta_por_id con filtro
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: obtener_venta_por_id con filtro',
        'buscar' => [
            'function obtener_venta_por_id(string $id_venta): ?array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nodo_nivel || $nodo_nivel->dato() !== \'dueno\') continue;',
            '',
            '        $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '        if (!$contenedor) continue;',
            '',
            '        $actual = hmi($contenedor);',
            '        while ($actual) {',
            '            if ($actual->dato() === $id_venta) {',
            '                return formatear_venta_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => [
            'function obtener_venta_por_id(string $id_venta, ?string $nombre_terminal = null): ?array {',
            '    // Fase B2.2.2: si se pasa el terminal, restringir la búsqueda',
            '    // a su contexto. Si no, buscar en todos los dueños (compat',
            '    // para admin/soporte).',
            '    [$nodo_venta, ] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
            '    if (!$nodo_venta) return null;',
            '    return formatear_venta_completa($nodo_venta);',
            '}',
        ],
    ],

    // ============================================================
    // Venta.php — listar_ventas_por_terminal navega por contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: listar_ventas_por_terminal por contexto',
        'buscar' => [
            '/**',
            ' * Lista ventas de una terminal (filtra las del dueño).',
            ' */',
            'function listar_ventas_por_terminal(string $nombre_terminal): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [];',
            '',
            '    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal) return [];',
            '',
            '    $nodo_dueno = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno) return [];',
            '',
            '    $nombre_dueno = $nodo_dueno->dato();',
            '    $ventas_dueno = listar_ventas_por_dueno($nombre_dueno);',
            '    $ventas_terminal = array_filter($ventas_dueno, function($venta) use ($nombre_terminal) {',
            '        return $venta[\'terminal\'] === $nombre_terminal;',
            '    });',
            '',
            '    return array_values($ventas_terminal);',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Lista ventas de una terminal, navegando por el contexto',
            ' * del terminal.',
            ' *',
            ' * A partir de v76s (Fase B2.2.2): en lugar de recorrer todas',
            ' * las ventas del dueño y filtrar, navega por el contexto del',
            ' * terminal (hoy el nodo del dueño, tras B2.3 el compartido).',
            ' * El filtro por `terminal` se mantiene para que el resultado',
            ' * sea idéntico en ambos modos.',
            ' *',
            ' * @param string $nombre_terminal',
            ' * @return array',
            ' */',
            'function listar_ventas_por_terminal(string $nombre_terminal): array {',
            '    $contexto = _contexto_terminal($nombre_terminal);',
            '    if (!$contexto) return [];',
            '',
            '    $contenedor = obtener_contenedor_ventas_dueno((string)$contexto->dato(), $contexto);',
            '    if (!$contenedor) return [];',
            '',
            '    $ventas = [];',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        $seg++;',
            '        $nodo_terminal = $actual->adyacente(\'terminal\');',
            '        if (!$nodo_terminal || $nodo_terminal->dato() !== $nombre_terminal) {',
            '            $actual = hd($actual);',
            '            continue;',
            '        }',
            '        $ventas[] = formatear_venta_resumida($actual);',
            '        $actual = hd($actual);',
            '    }',
            '    return $ventas;',
            '}',
        ],
    ],

    // ============================================================
    // Venta.php — confirmar_venta_actual usa contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: confirmar_venta_actual usa contexto',
        'buscar' => [
            '    $nodo_dueno = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'La terminal no tiene dueño asignado\'];',
            '    $nombre_dueno = $nodo_dueno->dato();',
            '',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
        ],
        'reemplazar' => [
            '    $nodo_dueno = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'La terminal no tiene dueño asignado\'];',
            '    $nombre_dueno = $nodo_dueno->dato();',
            '',
            '    // Fase B2.2.2: navegar por el contexto del terminal (hoy el',
            '    // nodo del dueño, tras B2.3 el compartido).',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_dueno);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: confirmar_venta_actual usa contexto (ventas)',
        'buscar' => [
            '    // Insertar venta en el árbol de ventas del dueño usando _hmi',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
        ],
        'reemplazar' => [
            '    // Insertar venta en el árbol de ventas del dueño usando _hmi.',
            '    // Fase B2.2.2: se navega por el contexto del terminal para',
            '    // que la venta se inserte en el contenedor correcto.',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno);',
        ],
    ],

    // ============================================================
    // Venta.php — pagar_cupon_venta con filtro por terminal
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: pagar_cupon_venta con filtro',
        'buscar' => [
            'function pagar_cupon_venta(string $id_venta, string $numero_cupon, string $monto, string $metodo_pago): array {',
            '    $monto_num = (float)$monto;',
            '    if ($monto_num <= 0) return [\'exito\' => false, \'error\' => \'Monto inválido\'];',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    // Buscar la venta en el árbol del dueño.',
            '    $nodo_venta = null;',
            '    $nombre_dueno_venta = \'\';',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        while ($actual) {',
            '            if ($actual->dato() === $id_venta) {',
            '                $nodo_venta = $actual;',
            '                $nombre_dueno_venta = $nombre_dueno;',
            '                break 2;',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
        'reemplazar' => [
            'function pagar_cupon_venta(string $id_venta, string $numero_cupon, string $monto, string $metodo_pago, ?string $nombre_terminal = null): array {',
            '    $monto_num = (float)$monto;',
            '    if ($monto_num <= 0) return [\'exito\' => false, \'error\' => \'Monto inválido\'];',
            '',
            '    // Fase B2.2.2: buscar la venta con filtro opcional por',
            '    // terminal. Si viene, restringe la búsqueda a su contexto.',
            '    [$nodo_venta, $nombre_dueno_venta] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
    ],

    // ============================================================
    // Venta.php — cancelar_venta con filtro por terminal
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: cancelar_venta con filtro',
        'buscar' => [
            'function cancelar_venta(string $id_venta, string $motivo = \'\'): array {',
            '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta);',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
        'reemplazar' => [
            'function cancelar_venta(string $id_venta, string $motivo = \'\', ?string $nombre_terminal = null): array {',
            '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
    ],

    // ============================================================
    // Venta.php — obtener_info_cancelacion con filtro por terminal
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: obtener_info_cancelacion con filtro',
        'buscar' => [
            'function obtener_info_cancelacion(string $id_venta): array {',
            '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta);',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
        'reemplazar' => [
            'function obtener_info_cancelacion(string $id_venta, ?string $nombre_terminal = null): array {',
            '    [$nodo_venta, $nombre_dueno] = _buscar_venta_por_id($id_venta, $nombre_terminal);',
            '    if (!$nodo_venta) return [\'exito\' => false, \'error\' => \'Venta no encontrada\'];',
        ],
    ],

    // ============================================================
    // Viaje.php — _construir_indice_ventas_por_viaje con contexto
    // (esta función vive en Viaje.php, no en Venta.php)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: _construir_indice_ventas_por_viaje con contexto',
        'buscar' => [
            'function _construir_indice_ventas_por_viaje(string $nombre_dueno, ?string $nombre_terminal = null): array {',
            '    $indice = [',
            '        \'tiene_ventas\' => [],',
            '        \'vendidos_por_micro\' => [],',
            '    ];',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
        ],
        'reemplazar' => [
            'function _construir_indice_ventas_por_viaje(string $nombre_dueno, ?string $nombre_terminal = null, ?Nodo $nodo_contexto = null): array {',
            '    $indice = [',
            '        \'tiene_ventas\' => [],',
            '        \'vendidos_por_micro\' => [],',
            '    ];',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_contexto);',
        ],
    ],

    // ============================================================
    // Viaje.php — listar_viajes_de_terminal pasa contexto al índice
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: listar_viajes_de_terminal pasa contexto al índice',
        'buscar' => [
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ, con el terminal como filtro. Antes, formatear_viaje',
            '    // recorría todo el contenedor de ventas del dueño por cada',
            '    // viaje (O(V × W)).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno, $nombre_terminal);',
        ],
        'reemplazar' => [
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ, con el terminal como filtro. Antes, formatear_viaje',
            '    // recorría todo el contenedor de ventas del dueño por cada',
            '    // viaje (O(V × W)).',
            '    // Fase B2.2.2 (v76s): pasar el contexto del terminal',
            '    // (hoy el nodo del dueño, tras B2.3 el compartido).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno, $nombre_terminal, $nodo_dueno);',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76s',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76r',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76s',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76r',
            '(Fase B2.2.1 del modelo topológico: `obtener_contenedor_viajes_dueno`',
            'acepta un `?Nodo $nodo_contexto` opcional, y',
            '`listar_viajes_de_terminal` le pasa el nodo del dueño como',
            'contexto. Nuevo helper `_contexto_terminal` en `Viaje.php`.',
            'Refactor sin cambio de comportamiento: el código del terminal',
            'sigue navegando por el nodo del dueño, pero ya con el',
            'mecanismo listo para B2.3 (repuntado).).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76s',
            '(Fase B2.2.2 del modelo topológico: Venta.php. Contexto',
            'opcional en `obtener_contenedor_ventas_dueno`; filtro opcional',
            'por terminal en `_buscar_venta_por_id`, `obtener_venta_por_id`,',
            '`pagar_cupon_venta`, `cancelar_venta` y `obtener_info_cancelacion`;',
            '`listar_ventas_por_terminal` y `confirmar_venta_actual` navegan',
            'por el contexto del terminal. Refactor sin cambio de',
            'comportamiento: el enrutador todavía no pasa el `$nombre_terminal`,',
            'así que la búsqueda sigue siendo global. La preparación de B2.3',
            'está completa.).',
            'Antes: v1.5piloto.76r',
            '(Fase B2.2.1 del modelo topológico: `obtener_contenedor_viajes_dueno`',
            'acepta un `?Nodo $nodo_contexto` opcional, y',
            '`listar_viajes_de_terminal` le pasa el nodo del dueño como',
            'contexto. Nuevo helper `_contexto_terminal` en `Viaje.php`.',
            'Refactor sin cambio de comportamiento: el código del terminal',
            'sigue navegando por el nodo del dueño, pero ya con el',
            'mecanismo listo para B2.3 (repuntado).).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76s',
        'buscar' => [
            '- **v76r**: Fase B2.2.1 del modelo topológico. Refactor',
        ],
        'reemplazar' => [
            '- **v76s**: Fase B2.2.2 del modelo topológico (Venta.php).',
            '  Refactor sin cambio de comportamiento:',
            '  `obtener_contenedor_ventas_dueno` acepta un `?Nodo $nodo_contexto`',
            '  opcional. `_buscar_venta_por_id` acepta un `?string $nombre_terminal`',
            '  opcional; si viene, restringe la búsqueda a su contexto.',
            '  `obtener_venta_por_id`, `pagar_cupon_venta`, `cancelar_venta` y',
            '  `obtener_info_cancelacion` heredan el filtro. `listar_ventas_por_terminal`',
            '  y `confirmar_venta_actual` navegan por el contexto del',
            '  terminal. `_construir_indice_ventas_por_viaje` (en Viaje.php)',
            '  acepta contexto, y `listar_viajes_de_terminal` le pasa el nodo',
            '  del dueño. El enrutador todavía no pasa el `$nombre_terminal`',
            '  a las funciones de búsqueda, así que el comportamiento es',
            '  idéntico al actual. La preparación para B2.3 (repuntado)',
            '  queda completa.',
            '- **v76r**: Fase B2.2.1 del modelo topológico. Refactor',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 bullet
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet v76s',
        'buscar' => [
            '- Cerramos en v76r la Fase B2.2.1 del modelo topológico.',
        ],
        'reemplazar' => [
            '- Cerramos en v76s la Fase B2.2.2 del modelo topológico',
            '  (Venta.php). Refactor sin cambio de comportamiento:',
            '  `obtener_contenedor_ventas_dueno` acepta un `?Nodo $nodo_contexto`',
            '  opcional. `_buscar_venta_por_id` y sus llamadores',
            '  (`obtener_venta_por_id`, `pagar_cupon_venta`, `cancelar_venta`,',
            '  `obtener_info_cancelacion`) aceptan un `?string $nombre_terminal`',
            '  opcional que restringe la búsqueda al contexto del terminal.',
            '  `listar_ventas_por_terminal` navega por el contexto.',
            '  `confirmar_venta_actual` usa el contexto al resolver viajes y',
            '  al insertar la venta. `_construir_indice_ventas_por_viaje`',
            '  (en Viaje.php) acepta contexto y `listar_viajes_de_terminal`',
            '  lo pasa. Pendiente: B2.2.3 (ViajeAsientos.php) y B2.2.4',
            '  (Empresa.php). Después: B2.3 (repuntar `us_termX → dueno`',
            '  al compartido).',
            '- Cerramos en v76r la Fase B2.2.1 del modelo topológico.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §13 estado
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado al cierre a v76s',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76r (framework 1.5i.7l).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76s (framework 1.5i.7l).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76s al bloque de estado',
        'buscar' => [
            'v76r: Fase B2.2.1. Contexto opcional en',
        ],
        'reemplazar' => [
            'v76s: Fase B2.2.2 (Venta.php). Contexto opcional en',
            '`obtener_contenedor_ventas_dueno` y filtro opcional por',
            'terminal en las búsquedas por id. `listar_ventas_por_terminal`',
            'y `confirmar_venta_actual` navegan por el contexto. Refactor',
            'sin cambio de comportamiento.',
            'v76r: Fase B2.2.1. Contexto opcional en',
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