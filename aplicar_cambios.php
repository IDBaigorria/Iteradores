<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74 — Bug 1: opciones de cobro congeladas al vender
 * + retroactivo opcional. (v2, corregida: bloques del override movidos
 * a viajes-micros.js, bloques de guardar_opciones_terminal_viaje
 * movidos a Viaje.php.)
 *
 * Asume v73v, v73w y v73x ya aplicados.
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
    // Aplicacion/Ventas/Venta.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump de version 70 a 74',
        'buscar' => [
            ' * @version   1.5piloto.70',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: crear opciones_cobro al confirmar venta',
        'buscar' => [
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato(\$metodo_pago), 'metodo_pago');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$total), 'total');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$cuotas), 'cuotas');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$monto_pagado), 'pagado');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$cuotas_restantes), 'cuotas_restantes');",
        ],
        'reemplazar' => [
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato(\$metodo_pago), 'metodo_pago');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$total), 'total');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$cuotas), 'cuotas');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$monto_pagado), 'pagado');",
            "    \$nodo_venta->_adyacente_en(Nodo::crear_con_dato((string)\$cuotas_restantes), 'cuotas_restantes');",
            '',
            '    // A partir de v74: congelar las opciones de cobro vigentes',
            '    // al momento de la venta. Desde acá, estas son las opciones',
            '    // que se usan al cobrar los cupones, en lugar de resolver',
            '    // en vivo contra el viaje/TerminalViaje.',
            '    _crear_opciones_cobro_venta($nodo_venta, [',
            "        'permite_efectivo' => \$permite_efectivo,",
            "        'cuotas_efectivo_max' => (string)\$cuotas_efectivo_max,",
            "        'permite_transferencia' => \$permite_transferencia,",
            "        'cuotas_transferencia_max' => (string)\$cuotas_transferencia_max,",
            '    ]);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: _resolver_metodos_permitidos_venta lee opciones_cobro + helpers nuevos',
        'buscar' => [
            '/**',
            ' * Resuelve la lista de métodos de pago permitidos para una venta,',
            ' * según el override del TerminalViaje > configuración del viaje >',
            ' * default. Devuelve un array con "efectivo" y/o "transferencia".',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array<int, string>',
            ' */',
            'function _resolver_metodos_permitidos_venta(Nodo $nodo_venta): array {',
            "    \$nodo_viaje = \$nodo_venta->adyacente('viaje');",
            "    \$nodo_terminal = \$nodo_venta->adyacente('terminal');",
            '    if (!$nodo_viaje || !$nodo_terminal) return [];',
            '',
            '    $nombre_viaje = $nodo_viaje->dato();',
            '    $nombre_terminal = $nodo_terminal->dato();',
            "    \$nodo_dueno = \$nodo_viaje->adyacente('dueno');",
            "    \$nombre_dueno = \$nodo_dueno ? \$nodo_dueno->dato() : '';",
            "    if (\$nombre_dueno === '') return [];",
            '',
            '    // Cuotas pactadas de la venta. Se usan para validar que el método',
            '    // elegido soporte esa cantidad de cuotas según la configuración',
            '    // del viaje o de la terminal.',
            "    \$cuotas_pactadas = (int)(\$nodo_venta->adyacente('cuotas') ? \$nodo_venta->adyacente('cuotas')->dato() : '1');",
            '    if ($cuotas_pactadas < 1) $cuotas_pactadas = 1;',
            '',
            '    $opciones_viaje = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '    $opciones_terminal = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '',
            '    $resolver = function(string $campo, string $default) use ($opciones_viaje, $opciones_terminal) {',
            "        if (isset(\$opciones_terminal[\$campo]) && trim((string)\$opciones_terminal[\$campo]) !== '') {",
            '            return (string)$opciones_terminal[$campo];',
            '        }',
            "        if (isset(\$opciones_viaje[\$campo]) && trim((string)\$opciones_viaje[\$campo]) !== '') {",
            '            return (string)$opciones_viaje[$campo];',
            '        }',
            '        return $default;',
            '    };',
            '',
            '    $metodos = [];',
            '',
            '    // Efectivo: se ofrece si está permitido y el máximo de cuotas',
            '    // configurado alcanza para las cuotas pactadas.',
            "    if (\$resolver('permite_efectivo', '1') === '1') {",
            "        \$max_efectivo = (int)\$resolver('cuotas_efectivo_max', '3');",
            "        if (\$max_efectivo >= \$cuotas_pactadas) \$metodos[] = 'efectivo';",
            '    }',
            '',
            '    // Transferencia: mismo criterio.',
            "    if (\$resolver('permite_transferencia', '1') === '1') {",
            "        \$max_transferencia = (int)\$resolver('cuotas_transferencia_max', '1');",
            "        if (\$max_transferencia >= \$cuotas_pactadas) \$metodos[] = 'transferencia';",
            '    }',
            '',
            '    return $metodos;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Resuelve la lista de métodos de pago permitidos para una venta.',
            ' *',
            ' * A partir de v74: primero se leen las opciones de cobro congeladas',
            ' * en el sub-nodo `opciones_cobro` del nodo venta. Si la venta es',
            ' * vieja (no tiene ese nodo), se cae a la resolución en vivo contra',
            ' * viaje/TerminalViaje.',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array<int, string>',
            ' */',
            'function _resolver_metodos_permitidos_venta(Nodo $nodo_venta): array {',
            '    $opciones = _leer_opciones_cobro_venta($nodo_venta);',
            '    if ($opciones === null) {',
            '        $opciones = _config_pago_resuelta_para_venta($nodo_venta);',
            '    }',
            '',
            '    $metodos = [];',
            "    if ((\$opciones['permite_efectivo'] ?? '1') === '1') \$metodos[] = 'efectivo';",
            "    if ((\$opciones['permite_transferencia'] ?? '1') === '1') \$metodos[] = 'transferencia';",
            '    return $metodos;',
            '}',
            '',
            '/**',
            ' * Lee las opciones de cobro congeladas en el nodo venta. Devuelve',
            ' * null si el nodo `opciones_cobro` no existe (venta anterior a',
            ' * v74). En caso contrario, devuelve los 4 campos como strings.',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array|null',
            ' */',
            'function _leer_opciones_cobro_venta(Nodo $nodo_venta): ?array {',
            "    \$nodo_opciones = \$nodo_venta->adyacente('opciones_cobro');",
            '    if (!$nodo_opciones) return null;',
            '',
            '    $valores = [',
            "        'permite_efectivo' => '1',",
            "        'cuotas_efectivo_max' => '3',",
            "        'permite_transferencia' => '1',",
            "        'cuotas_transferencia_max' => '1',",
            '    ];',
            '    foreach (array_keys($valores) as $campo) {',
            '        $nodo_campo = $nodo_opciones->adyacente($campo);',
            '        if ($nodo_campo) $valores[$campo] = $nodo_campo->dato();',
            '    }',
            '    return $valores;',
            '}',
            '',
            '/**',
            ' * Crea (o actualiza) el sub-nodo `opciones_cobro` en el nodo venta',
            ' * con los 4 campos congelados al momento de la venta.',
            ' *',
            ' * @param Nodo  $nodo_venta',
            ' * @param array $opciones Array con los 4 campos.',
            ' * @return void',
            ' */',
            'function _crear_opciones_cobro_venta(Nodo $nodo_venta, array $opciones): void {',
            "    \$nodo_opciones = \$nodo_venta->adyacente('opciones_cobro');",
            '    if (!$nodo_opciones) {',
            "        \$nodo_opciones = Nodo::crear_con_dato('');",
            "        \$nodo_venta->_adyacente_en(\$nodo_opciones, 'opciones_cobro');",
            '    }',
            "    \$campos = ['permite_efectivo', 'cuotas_efectivo_max', 'permite_transferencia', 'cuotas_transferencia_max'];",
            '    foreach ($campos as $campo) {',
            "        \$valor = (string)(\$opciones[\$campo] ?? '');",
            '        _actualizar_o_crear_campo($nodo_opciones, $campo, $valor);',
            '    }',
            '}',
            '',
            '/**',
            ' * Actualiza solo los `permite_*` de las opciones de cobro de una',
            ' * venta. No toca los `cuotas_*_max` (respeta la cantidad de cuotas',
            ' * pactadas). Si el nodo `opciones_cobro` no existe, no hace nada.',
            ' *',
            ' * @param Nodo   $nodo_venta',
            ' * @param string $permite_efectivo',
            ' * @param string $permite_transferencia',
            ' * @return void',
            ' */',
            'function _actualizar_permite_opciones_cobro_venta(Nodo $nodo_venta, string $permite_efectivo, string $permite_transferencia): void {',
            "    \$nodo_opciones = \$nodo_venta->adyacente('opciones_cobro');",
            '    if (!$nodo_opciones) return;',
            '',
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'permite_efectivo', \$permite_efectivo);",
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'permite_transferencia', \$permite_transferencia);",
            '}',
            '',
            '/**',
            ' * Resuelve la configuración de pago efectiva para una combinación',
            ' * viaje + terminal con la lógica de override del TerminalViaje >',
            ' * viaje > default. Devuelve los 4 campos como strings.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_terminal',
            ' * @return array',
            ' */',
            'function _config_pago_resuelta_para_viaje_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {',
            '    $defaults = [',
            "        'permite_efectivo' => '1',",
            "        'cuotas_efectivo_max' => '3',",
            "        'permite_transferencia' => '1',",
            "        'cuotas_transferencia_max' => '1',",
            '    ];',
            '    $opciones_viaje = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '    $opciones_terminal = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '',
            '    $resolver = function(string $campo) use ($opciones_viaje, $opciones_terminal, $defaults) {',
            "        if (isset(\$opciones_terminal[\$campo]) && trim((string)\$opciones_terminal[\$campo]) !== '') {",
            '            return (string)$opciones_terminal[$campo];',
            '        }',
            "        if (isset(\$opciones_viaje[\$campo]) && trim((string)\$opciones_viaje[\$campo]) !== '') {",
            '            return (string)$opciones_viaje[$campo];',
            '        }',
            '        return $defaults[$campo];',
            '    };',
            '',
            '    return [',
            "        'permite_efectivo' => \$resolver('permite_efectivo'),",
            "        'cuotas_efectivo_max' => \$resolver('cuotas_efectivo_max'),",
            "        'permite_transferencia' => \$resolver('permite_transferencia'),",
            "        'cuotas_transferencia_max' => \$resolver('cuotas_transferencia_max'),",
            '    ];',
            '}',
            '',
            '/**',
            ' * Devuelve la configuración efectiva de pago para una venta:',
            ' * primero `opciones_cobro` si existe; si no, resolución en vivo.',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array',
            ' */',
            'function _config_pago_resuelta_para_venta(Nodo $nodo_venta): array {',
            '    $opciones = _leer_opciones_cobro_venta($nodo_venta);',
            '    if ($opciones !== null) return $opciones;',
            '',
            "    \$nodo_viaje = \$nodo_venta->adyacente('viaje');",
            "    \$nodo_terminal = \$nodo_venta->adyacente('terminal');",
            '    $defaults = [',
            "        'permite_efectivo' => '1',",
            "        'cuotas_efectivo_max' => '3',",
            "        'permite_transferencia' => '1',",
            "        'cuotas_transferencia_max' => '1',",
            '    ];',
            '    if (!$nodo_viaje || !$nodo_terminal) return $defaults;',
            '',
            '    $nombre_viaje = $nodo_viaje->dato();',
            '    $nombre_terminal = $nodo_terminal->dato();',
            "    \$nodo_dueno = \$nodo_viaje->adyacente('dueno');",
            "    \$nombre_dueno = \$nodo_dueno ? \$nodo_dueno->dato() : '';",
            "    if (\$nombre_dueno === '') return \$defaults;",
            '',
            '    return _config_pago_resuelta_para_viaje_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '}',
            '',
            '/**',
            ' * Recorre las ventas de un dueño que pertenecen a un viaje,',
            ' * ejecutando un callback por cada nodo venta.',
            ' *',
            ' * @param string   $nombre_dueno',
            ' * @param string   $nombre_viaje',
            ' * @param callable $callback function(Nodo $nodo_venta): void',
            ' * @return void',
            ' */',
            'function _recorrer_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, callable $callback): void {',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor) return;',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            "        \$nodo_viaje = \$actual->adyacente('viaje');",
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje) {',
            '            $callback($actual);',
            '        }',
            "        \$actual = hd(\$actual);",
            '        $seg++;',
            '    }',
            '}',
            '',
            '/**',
            ' * Recorre las ventas de un dueño que pertenecen a un viaje Y una',
            ' * terminal específicos.',
            ' *',
            ' * @param string   $nombre_dueno',
            ' * @param string   $nombre_viaje',
            ' * @param string   $nombre_terminal',
            ' * @param callable $callback function(Nodo $nodo_venta): void',
            ' * @return void',
            ' */',
            'function _recorrer_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, callable $callback): void {',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor) return;',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            "        \$nodo_viaje = \$actual->adyacente('viaje');",
            "        \$nodo_terminal = \$actual->adyacente('terminal');",
            '        if ($nodo_viaje && $nodo_viaje->dato() === $nombre_viaje',
            "            && \$nodo_terminal && \$nodo_terminal->dato() === \$nombre_terminal) {",
            '            $callback($actual);',
            '        }',
            "        \$actual = hd(\$actual);",
            '        $seg++;',
            '    }',
            '}',
            '',
            '/**',
            ' * Toma un snapshot de la configuración de cobro de cada venta',
            ' * del viaje. Se usa antes de guardar opciones, para preservar la',
            ' * config vieja si el usuario NO tilda el retroactivo.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @return array Mapa id_venta => config (4 campos).',
            ' */',
            'function _snapshot_config_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje): array {',
            '    $snap = [];',
            '    _recorrer_ventas_del_viaje($nombre_dueno, $nombre_viaje, function(Nodo $v) use (&$snap) {',
            '        $snap[$v->id()] = _config_pago_resuelta_para_venta($v);',
            '    });',
            '    return $snap;',
            '}',
            '',
            '/**',
            ' * Toma un snapshot de la configuración de cobro de cada venta',
            ' * de la terminal en el viaje.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_terminal',
            ' * @return array Mapa id_venta => config (4 campos).',
            ' */',
            'function _snapshot_config_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {',
            '    $snap = [];',
            '    _recorrer_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, function(Nodo $v) use (&$snap) {',
            '        $snap[$v->id()] = _config_pago_resuelta_para_venta($v);',
            '    });',
            '    return $snap;',
            '}',
            '',
            '/**',
            ' * Aplica los cambios de opciones de cobro a las ventas del viaje',
            ' * después de guardar. Reglas:',
            ' *  - Ventas sin `opciones_cobro` (viejas): se les fija la config.',
            ' *    Si el flag está: config nueva. Si no: config vieja (snapshot).',
            ' *  - Ventas con `opciones_cobro`:',
            ' *      Si el flag está: se actualizan solo los `permite_*`.',
            ' *      Si no: no se tocan.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param array  $snapshots Mapa id_venta => config vieja.',
            ' * @param bool   $aplicar_retroactivo',
            ' * @return void',
            ' */',
            'function _aplicar_retroactivo_a_ventas_del_viaje(string $nombre_dueno, string $nombre_viaje, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_del_viaje($nombre_dueno, $nombre_viaje, function(Nodo $v) use ($snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            '                $nueva = _config_pago_resuelta_para_venta($v);',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $nueva = _config_pago_resuelta_para_venta($v);',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
            '',
            '/**',
            ' * Igual que el anterior, pero aplicado a las ventas de una',
            ' * terminal específica dentro de un viaje.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_terminal',
            ' * @param array  $snapshots',
            ' * @param bool   $aplicar_retroactivo',
            ' * @return void',
            ' */',
            'function _aplicar_retroactivo_a_ventas_de_terminal(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $snapshots, bool $aplicar_retroactivo): void {',
            '    _recorrer_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, function(Nodo $v) use ($snapshots, $aplicar_retroactivo) {',
            '        $id = $v->id();',
            "        \$nodo_opc = \$v->adyacente('opciones_cobro');",
            '        if ($nodo_opc) {',
            '            if ($aplicar_retroactivo) {',
            '                $nueva = _config_pago_resuelta_para_venta($v);',
            "                _actualizar_permite_opciones_cobro_venta(\$v, \$nueva['permite_efectivo'], \$nueva['permite_transferencia']);",
            '            }',
            '        } else {',
            '            $nueva = _config_pago_resuelta_para_venta($v);',
            '            $vieja = $snapshots[$id] ?? $nueva;',
            '            $a_fijar = $aplicar_retroactivo ? $nueva : $vieja;',
            '            _crear_opciones_cobro_venta($v, $a_fijar);',
            '        }',
            '    });',
            '}',
        ],
    ],

    // ============================================================
    // Aplicacion/Viajes/ViajeOpciones.php
    // (solo guardar_opciones_avanzadas_viaje + bump)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'ViajeOpciones.php: bump de version 70 a 74',
        'buscar' => [
            ' * @version   1.5piloto.70',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'ViajeOpciones.php: guardar_opciones_avanzadas_viaje con retroactivo (firma + snapshot)',
        'buscar' => [
            'function guardar_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, array $opciones): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            "    if (!\$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];",
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            "    if (!\$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];",
            '',
            "    \$nodo_opciones = \$nodo_viaje->adyacente('opciones_avanzadas');",
            '    if (!$nodo_opciones) {',
            "        \$nodo_opciones = Nodo::crear_con_dato('');",
            "        \$nodo_viaje->_adyacente_en(\$nodo_opciones, 'opciones_avanzadas');",
            '    }',
        ],
        'reemplazar' => [
            'function guardar_opciones_avanzadas_viaje(string $nombre_dueno, string $nombre_viaje, array $opciones, bool $aplicar_retroactivo = false): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            "    if (!\$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];",
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            "    if (!\$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];",
            '',
            "    \$nodo_opciones = \$nodo_viaje->adyacente('opciones_avanzadas');",
            '    if (!$nodo_opciones) {',
            "        \$nodo_opciones = Nodo::crear_con_dato('');",
            "        \$nodo_viaje->_adyacente_en(\$nodo_opciones, 'opciones_avanzadas');",
            '    }',
            '',
            '    // Capturar los valores viejos de permite_* para detectar si',
            '    // cambian y, si hace falta, migrar/actualizar las ventas.',
            "    \$nodo_perm_e_viejo = \$nodo_opciones->adyacente('permite_efectivo');",
            "    \$permite_efectivo_viejo = \$nodo_perm_e_viejo ? \$nodo_perm_e_viejo->dato() : '1';",
            "    \$nodo_perm_t_viejo = \$nodo_opciones->adyacente('permite_transferencia');",
            "    \$permite_transferencia_viejo = \$nodo_perm_t_viejo ? \$nodo_perm_t_viejo->dato() : '1';",
            '',
            '    // Snapshot de la config de cobro de cada venta del viaje,',
            '    // antes de tocar las opciones. Se usa para fijar la config',
            '    // vieja en las ventas sin opciones_cobro cuando el usuario',
            '    // NO tilda el retroactivo.',
            '    $snapshot_ventas = _snapshot_config_ventas_del_viaje($nombre_dueno, $nombre_viaje);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'ViajeOpciones.php: guardar_opciones_avanzadas_viaje aplica retroactivo',
        'buscar' => [
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'permite_transferencia', \$permite_transferencia);",
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'cuotas_transferencia_max', \$cuotas_transferencia_max);",
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'mostrar_dj_en_terminales', \$mostrar_dj_en_terminales);",
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            "    return ['exito' => true];",
            '}',
        ],
        'reemplazar' => [
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'permite_transferencia', \$permite_transferencia);",
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'cuotas_transferencia_max', \$cuotas_transferencia_max);",
            "    _actualizar_o_crear_campo(\$nodo_opciones, 'mostrar_dj_en_terminales', \$mostrar_dj_en_terminales);",
            '',
            '    // A partir de v74: si los permite_* del viaje cambiaron,',
            '    // migrar las ventas viejas del viaje sin opciones_cobro',
            '    // (fijarles la config) y, si el flag está activo, actualizar',
            '    // los permite_* de las ventas que ya tienen opciones_cobro.',
            '    $cambio_permite_viaje = ($permite_efectivo !== $permite_efectivo_viejo',
            '        || $permite_transferencia !== $permite_transferencia_viejo);',
            '    if ($cambio_permite_viaje) {',
            '        _aplicar_retroactivo_a_ventas_del_viaje($nombre_dueno, $nombre_viaje, $snapshot_ventas, $aplicar_retroactivo);',
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            "    return ['exito' => true];",
            '}',
        ],
    ],

    // ============================================================
    // Aplicacion/Viajes/Viaje.php
    // (bump + guardar_viaje_completo + guardar_opciones_terminal_viaje)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: bump de version 70 a 74',
        'buscar' => [
            ' * @version   1.5piloto.70',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: guardar_viaje_completo pasa aplicar_retroactivo',
        'buscar' => [
            "    \$resultado_opciones = guardar_opciones_avanzadas_viaje(\$nombre_dueno, \$nombre_viaje, \$opciones);",
            "    if (!\$resultado_opciones['exito']) {",
            '        return $resultado_opciones;',
            '    }',
        ],
        'reemplazar' => [
            "    \$aplicar_retroactivo = ((\$datos['aplicar_retroactivo'] ?? '') === '1');",
            "    \$resultado_opciones = guardar_opciones_avanzadas_viaje(\$nombre_dueno, \$nombre_viaje, \$opciones, \$aplicar_retroactivo);",
            "    if (!\$resultado_opciones['exito']) {",
            '        return $resultado_opciones;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: guardar_opciones_terminal_viaje con retroactivo (firma + snapshot)',
        'buscar' => [
            'function guardar_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $opciones): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            "    if (!\$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];",
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            "    if (!\$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];",
            '',
            "    \$nodo_terminales = \$nodo_viaje->adyacente('terminales_autorizadas');",
            "    if (!\$nodo_terminales) return ['exito' => false, 'error' => 'El viaje no tiene terminales autorizadas'];",
            '',
            '    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal_viaje) {',
            "        return ['exito' => false, 'error' => 'La terminal no está autorizada en este viaje'];",
            '    }',
        ],
        'reemplazar' => [
            'function guardar_opciones_terminal_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal, array $opciones, bool $aplicar_retroactivo = false): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            "    if (!\$nodo_viajes) return ['exito' => false, 'error' => 'Dueño no encontrado'];",
            '',
            '    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);',
            "    if (!\$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];",
            '',
            "    \$nodo_terminales = \$nodo_viaje->adyacente('terminales_autorizadas');",
            "    if (!\$nodo_terminales) return ['exito' => false, 'error' => 'El viaje no tiene terminales autorizadas'];",
            '',
            '    $nodo_terminal_viaje = $nodo_terminales->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal_viaje) {',
            "        return ['exito' => false, 'error' => 'La terminal no está autorizada en este viaje'];",
            '    }',
            '',
            '    // Snapshot de la config de cobro de las ventas de esta terminal,',
            '    // antes de tocar el override.',
            '    $snapshot_ventas = _snapshot_config_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            '    $config_antes = _config_pago_resuelta_para_viaje_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: guardar_opciones_terminal_viaje aplica retroactivo',
        'buscar' => [
            "        _actualizar_o_crear_campo(\$nodo_terminal_viaje, 'permite_transferencia', \$permite_transferencia);",
            "        _actualizar_o_crear_campo(\$nodo_terminal_viaje, 'cuotas_transferencia_max', \$cuotas_transferencia_max);",
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            "    return ['exito' => true];",
            '}',
        ],
        'reemplazar' => [
            "        _actualizar_o_crear_campo(\$nodo_terminal_viaje, 'permite_transferencia', \$permite_transferencia);",
            "        _actualizar_o_crear_campo(\$nodo_terminal_viaje, 'cuotas_transferencia_max', \$cuotas_transferencia_max);",
            '    }',
            '',
            '    // A partir de v74: si la config efectiva de pago de las ventas',
            '    // de esta terminal cambió en algún permite_*, migrar las ventas',
            '    // viejas y (si el flag está activo) aplicar retroactivo a las',
            '    // que ya tienen opciones_cobro.',
            '    $config_despues = _config_pago_resuelta_para_viaje_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal);',
            "    \$cambio_permite_terminal = (\$config_antes['permite_efectivo'] !== \$config_despues['permite_efectivo']",
            "        || \$config_antes['permite_transferencia'] !== \$config_despues['permite_transferencia']);",
            '    if ($cambio_permite_terminal) {',
            '        _aplicar_retroactivo_a_ventas_de_terminal($nombre_dueno, $nombre_viaje, $nombre_terminal, $snapshot_ventas, $aplicar_retroactivo);',
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            "    return ['exito' => true];",
            '}',
        ],
    ],

    // ============================================================
    // Aplicacion/Enrutador.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump de version 73j a 74',
        'buscar' => [
            ' * @version   1.5piloto.73j',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: guardar_opciones_terminal pasa aplicar_retroactivo',
        'buscar' => [
            "                        'permite_transferencia' => \$post['permite_transferencia'] ?? '',",
            "                        'cuotas_transferencia_max' => \$post['cuotas_transferencia_max'] ?? '',",
            '                    ];',
            "                    \$resultado = guardar_opciones_terminal_viaje(\$nombre_dueno, \$nombre_viaje, \$nombre_terminal, \$opciones);",
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            "                        'permite_transferencia' => \$post['permite_transferencia'] ?? '',",
            "                        'cuotas_transferencia_max' => \$post['cuotas_transferencia_max'] ?? '',",
            '                    ];',
            "                    \$aplicar_retroactivo = ((\$post['aplicar_retroactivo'] ?? '') === '1');",
            "                    \$resultado = guardar_opciones_terminal_viaje(\$nombre_dueno, \$nombre_viaje, \$nombre_terminal, \$opciones, \$aplicar_retroactivo);",
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],

    // ============================================================
    // Aplicacion/Viajes/viajes-opciones.js
    // (checkbox del modal del viaje)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-opciones.js',
        'descripcion' => 'viajes-opciones.js: bump de version 66 a 74',
        'buscar' => [
            '/***',
            ' * Modal de alta/edición de viaje, opciones avanzadas y condiciones de pago.',
            ' * @version 1.5piloto.66',
            ' */',
        ],
        'reemplazar' => [
            '/***',
            ' * Modal de alta/edición de viaje, opciones avanzadas y condiciones de pago.',
            ' * @version 1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-opciones.js',
        'descripcion' => 'viajes-opciones.js: checkbox aplicar_retroactivo en modal del viaje',
        'buscar' => [
            '                <div class="field" id="modal_viaje_cuotas_transferencia_container" style="margin-left:20px; margin-top:6px; ${datos.permite_transferencia === \'1\' ? \'\' : \'display:none;\'}">',
            '                    <label>Máximo de cuotas (transferencia):</label>',
            '                    <input type="number" id="modal_viaje_cuotas_transferencia_max" value="${datos.cuotas_transferencia_max}" min="1" max="12" style="max-width:100px;">',
            '                </div>',
            '            </div>',
            '        </div>',
        ],
        'reemplazar' => [
            '                <div class="field" id="modal_viaje_cuotas_transferencia_container" style="margin-left:20px; margin-top:6px; ${datos.permite_transferencia === \'1\' ? \'\' : \'display:none;\'}">',
            '                    <label>Máximo de cuotas (transferencia):</label>',
            '                    <input type="number" id="modal_viaje_cuotas_transferencia_max" value="${datos.cuotas_transferencia_max}" min="1" max="12" style="max-width:100px;">',
            '                </div>',
            '            </div>',
            '            <div style="margin-top:12px;">',
            '                <label><input type="checkbox" id="modal_viaje_aplicar_retroactivo"> Aplicar cambios de método de pago a los cupones pendientes de ventas ya hechas (no afecta la cantidad de cuotas pactadas)</label>',
            '                <div class="small muted" style="margin-left:20px; margin-top:4px;">Solo afecta a las ventas del viaje que todavía tienen cuotas pendientes de pago. No cambia la cantidad de cuotas ya pactadas en cada venta.</div>',
            '            </div>',
            '        </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-opciones.js',
        'descripcion' => 'viajes-opciones.js: enviar aplicar_retroactivo en POST del viaje',
        'buscar' => [
            "            mostrar_dj_en_terminales: document.getElementById('modal_viaje_mostrar_dj_terminales').checked ? '1' : '0',",
            '            paradas_intermedias: JSON.stringify(paradas)',
        ],
        'reemplazar' => [
            "            mostrar_dj_en_terminales: document.getElementById('modal_viaje_mostrar_dj_terminales').checked ? '1' : '0',",
            "            aplicar_retroactivo: document.getElementById('modal_viaje_aplicar_retroactivo').checked ? '1' : '0',",
            '            paradas_intermedias: JSON.stringify(paradas)',
        ],
    ],

    // ============================================================
    // Aplicacion/Viajes/viajes-micros.js
    // (checkbox del modal del override de TerminalViaje)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros.js: bump de version 41 a 74',
        'buscar' => [
            '/**',
            ' * Micros y terminales dentro de viajes.',
            ' * @version 1.5piloto.41',
            ' */',
        ],
        'reemplazar' => [
            '/**',
            ' * Micros y terminales dentro de viajes.',
            ' * @version 1.5piloto.74',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros.js: checkbox aplicar_retroactivo en modal del override',
        'buscar' => [
            '                <div style="margin-top:12px;">',
            '                    <label><input type="checkbox" id="opciones_terminal_permite_transferencia" ${permite_transferencia_val === \'1\' ? \'checked\' : \'\'}> Permitir transferencia bancaria</label>',
            '                    <div class="field" id="opciones_terminal_cuotas_transferencia_container" style="margin-left:20px; margin-top:6px; ${permite_transferencia_val === \'1\' ? \'\' : \'display:none;\'}">',
            '                        <label>Máximo de cuotas (transferencia):</label>',
            '                        <input type="number" id="opciones_terminal_cuotas_transferencia_max" value="${cuotas_transferencia_max_val}" min="1" max="12" style="max-width:100px;">',
            '                    </div>',
            '                </div>',
            '            </div>',
        ],
        'reemplazar' => [
            '                <div style="margin-top:12px;">',
            '                    <label><input type="checkbox" id="opciones_terminal_permite_transferencia" ${permite_transferencia_val === \'1\' ? \'checked\' : \'\'}> Permitir transferencia bancaria</label>',
            '                    <div class="field" id="opciones_terminal_cuotas_transferencia_container" style="margin-left:20px; margin-top:6px; ${permite_transferencia_val === \'1\' ? \'\' : \'display:none;\'}">',
            '                        <label>Máximo de cuotas (transferencia):</label>',
            '                        <input type="number" id="opciones_terminal_cuotas_transferencia_max" value="${cuotas_transferencia_max_val}" min="1" max="12" style="max-width:100px;">',
            '                    </div>',
            '                </div>',
            '                <div style="margin-top:12px;">',
            '                    <label><input type="checkbox" id="opciones_terminal_aplicar_retroactivo"> Aplicar cambios de método de pago a los cupones pendientes de ventas ya hechas (no afecta la cantidad de cuotas pactadas)</label>',
            '                    <div class="small muted" style="margin-left:20px; margin-top:4px;">Solo afecta a las ventas de esta terminal en este viaje que todavía tienen cuotas pendientes de pago. No cambia la cantidad de cuotas ya pactadas.</div>',
            '                </div>',
            '            </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-micros.js',
        'descripcion' => 'viajes-micros.js: enviar aplicar_retroactivo en POST del override',
        'buscar' => [
            '                accion: "viajes/guardar_opciones_terminal",',
            '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
            '                nombre_terminal,',
            '                nombre_dueno,',
            '                cambiar_punto_predeterminado: cambiar,',
            '                punto_subida_bajada: punto,',
            '                permite_efectivo,',
            '                cuotas_efectivo_max,',
            '                permite_transferencia,',
            '                cuotas_transferencia_max',
            '            })',
        ],
        'reemplazar' => [
            '                accion: "viajes/guardar_opciones_terminal",',
            '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
            '                nombre_terminal,',
            '                nombre_dueno,',
            '                cambiar_punto_predeterminado: cambiar,',
            '                punto_subida_bajada: punto,',
            '                permite_efectivo,',
            '                cuotas_efectivo_max,',
            '                permite_transferencia,',
            '                cuotas_transferencia_max,',
            "                aplicar_retroactivo: document.getElementById('opciones_terminal_aplicar_retroactivo').checked ? '1' : '0'",
            '            })',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bumps
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump del ?v= de viajes-opciones.js a 74',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-opciones.js?v=1.5piloto.66"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-opciones.js?v=1.5piloto.74"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump del ?v= de viajes-micros.js a 74',
        'buscar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.65"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/Viajes/viajes-micros.js?v=1.5piloto.74"></script>',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73x (dos bugs',
            'de la ligadura comprador-pasajero: no se activa con pasajeros',
            'duplicados y se limpian los campos al corregir el DNI por uno',
            'no registrado).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74 (Bug 1:',
            'opciones de cobro congeladas al vender + retroactivo opcional al',
            'editar las condiciones de pago del viaje o el override de',
            'TerminalViaje).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74 al historial',
        'buscar' => [
            '  "Usar primer pasajero" del modal de venta: obsoleto desde que',
            '  la atadura funciona en ambos sentidos.',
        ],
        'reemplazar' => [
            '  "Usar primer pasajero" del modal de venta: obsoleto desde que',
            '  la atadura funciona en ambos sentidos.',
            '- **v74**: fix del Bug 1 (opciones de cobro). Al confirmar la',
            '  venta, se congela la config de pago vigente en un sub-nodo',
            '  `opciones_cobro` del nodo venta. Al cobrar un cupón, se leen',
            '  las opciones de la venta, no la config viva del viaje. Los',
            '  modales del viaje y del override de TerminalViaje tienen un',
            '  checkbox "Aplicar cambios de método de pago a los cupones',
            '  pendientes...": si se tilda, se actualizan los `permite_*`',
            '  de las ventas afectadas con cupones pendientes (los',
            '  `cuotas_*_max` no se tocan, respetando la cantidad de cuotas',
            '  pactadas). Si no se tilda, las ventas ya hechas no se',
            '  modifican. Las ventas viejas sin `opciones_cobro` se migran',
            '  al guardar opciones: con la config vieja si no se tildó el',
            '  check, con la nueva si se tildó. Backend y frontend.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: Bug 1 resuelto',
        'buscar' => [
            '**Bug 1 queda pendiente de resolver.**',
        ],
        'reemplazar' => [
            '**Bug 1 — Opciones de cobro de cupones. Resuelto en v74.**',
            'Diseño: al confirmar la venta se congela la config de pago',
            'vigente en un sub-nodo `opciones_cobro` del nodo venta. Al',
            'cobrar un cupón se leen las opciones de la venta, no la config',
            'viva del viaje. El modal del viaje y el del override del',
            'TerminalViaje tienen un checkbox "Aplicar cambios de método de',
            'pago a los cupones pendientes de ventas ya hechas (no afecta',
            'la cantidad de cuotas pactadas)". Si se tilda, se actualizan',
            'los `permite_*` de las ventas afectadas con cupones pendientes.',
            'Los `cuotas_*_max` nunca se tocan retroactivamente. Las ventas',
            'viejas sin `opciones_cobro` se migran al guardar opciones:',
            'con la config vieja si no se tildó el check, con la nueva si',
            'se tildó. No hay botón ni submodal en el modal de pago de',
            'cupón: la decisión se toma siempre en el momento de editar',
            'las condiciones de pago.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: Nodo Venta Persistente agrega opciones_cobro',
        'buscar' => [
            '### 4.15 Nodo Venta Persistente (dato = id_venta)',
            '',
            'Enlaces: `terminal`, `viaje`, `micro`, `fecha_hora`, `fecha_ultimo_pago`,',
            '`metodo_pago`, `total`, `cuotas`, `pagado`, `cuotas_restantes`,',
            '`comprador`, `asientos` (cabeza lista simple), `cupones` (contenedor).',
        ],
        'reemplazar' => [
            '### 4.15 Nodo Venta Persistente (dato = id_venta)',
            '',
            'Enlaces: `terminal`, `viaje`, `micro`, `fecha_hora`, `fecha_ultimo_pago`,',
            '`metodo_pago`, `total`, `cuotas`, `pagado`, `cuotas_restantes`,',
            '`comprador`, `asientos` (cabeza lista simple), `cupones` (contenedor),',
            '`opciones_cobro` (contenedor, desde v74).',
            '',
            '**`opciones_cobro` (desde v74):** sub-nodo que congela la config',
            'de pago vigente al momento de la venta. Es la fuente de verdad',
            'para cobrar los cupones de esta venta. Estructura:',
            '',
            '- `permite_efectivo` → "0"/"1"',
            '- `cuotas_efectivo_max` → "1".."12"',
            '- `permite_transferencia` → "0"/"1"',
            '- `cuotas_transferencia_max` → "1".."12"',
            '',
            'Al editar las opciones de pago del viaje o el override del',
            'TerminalViaje, un checkbox permite actualizar los `permite_*`',
            'de las ventas afectadas con cupones pendientes. Los',
            '`cuotas_*_max` no se tocan retroactivamente. Ventas viejas',
            'sin `opciones_cobro`: se migran al guardar opciones (config',
            'vieja si no se tildó el check, nueva si se tildó).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74',
        'buscar' => [
            '  "Usar primer pasajero" (obsoleto desde que la atadura funciona).',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '  "Usar primer pasajero" (obsoleto desde que la atadura funciona).',
            '- Cerramos en v74 el fix del Bug 1 (opciones de cobro congeladas',
            '  al vender + retroactivo opcional al editar condiciones de pago).',
            '  Sin botón ni submodal en el modal de pago de cupón: la',
            '  decisión de aplicar retroactivo se toma al editar las',
            '  condiciones de pago (del viaje o del override de TerminalViaje).',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado del proyecto al cierre v74',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73x (framework 1.5i.7f).',
            'Todo funcional. Bug 2 resuelto en su totalidad, incluyendo los casos',
            'de duplicado y corrección de DNI. Pendiente el Bug 1 (opciones de',
            'cobro de cupones en dos niveles).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74 (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: quitar Bug 1 de la lista de pendientes',
        'buscar' => [
            '**Bugs conocidos (prioridad alta, antes de todo lo demás):**',
            '',
            '- **Bug de cupones.** Ver sección 8.3, "Bug 1". Opciones de cobro en',
            '  dos niveles: individuales (fijadas al momento de la venta) pisan',
            '  a generales (del viaje). Requiere:',
            '  - Al confirmar venta, copiar las opciones de cobro vigentes al',
            '    nodo de la venta (nuevo sub-nodo `opciones_cobro` o similar).',
            '  - Al cobrar una cuota, leer primero las individuales. Si no hay,',
            '    caer a las generales.',
            '  - Botón "Cambiar método de cobro" en el modal de pago de cupón,',
            '    que abre otro modal para editar las opciones individuales.',
            '  - Confirmar el formato exacto con el usuario antes de codear.',
            '',
            '- **Bug de ligadura comprador-pasajero: resuelto en v73v.** Ver',
            '  sección 8.3, "Bug 2".',
        ],
        'reemplazar' => [
            '**Bugs conocidos (prioridad alta):**',
            '',
            '- **Bug de cupones: resuelto en v74.** Ver sección 8.3, "Bug 1".',
            '',
            '- **Bug de ligadura comprador-pasajero: resuelto en v73v.** Ver',
            '  sección 8.3, "Bug 2".',
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