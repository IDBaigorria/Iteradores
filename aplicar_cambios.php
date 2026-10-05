<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.74y:
 *   - Fase 2, noveno, décimo y undécimo flujo arreglados:
 *     * actualizar_configuracion_vehiculo destruye los pisos viejos.
 *     * eliminar_vehiculo destruye el vehículo completo.
 *     * eliminar_empresa destruye todos sus vehículos y la empresa.
 *   - Se mueven _destruir_lista_circular_asientos, _destruir_piso
 *     y _destruir_copia_vehiculo (renombrada a
 *     _destruir_vehiculo_completo) de Viaje.php a
 *     FuncionesAuxiliares.php, para que Vehiculo.php y Empresa.php
 *     las puedan usar sin depender de Viaje.php.
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
    // FuncionesAuxiliares.php: agregar helpers de vehículo
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Bump @version a 1.5piloto.74y',
        'buscar' => [
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.74v',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.37',
            ' * @version   1.5piloto.74y',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/FuncionesAuxiliares.php',
        'descripcion' => 'Agregar helpers de destrucción de vehículo',
        'buscar' => [
            '// ============================================================',
            '// Persistencia',
            '// ============================================================',
            '',
            '/**',
            ' * Guarda una superestructura solo en SQL.',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye la lista circular de asientos de un piso.',
            ' *',
            ' * Recolecta los asientos (todos menos la cabeza), rompe el',
            ' * círculo desenlazando el `siguiente` de todos, desenlaza',
            ' * el `primer` de la cabeza, desenlaza la cabeza del piso,',
            ' * y destruye cada asiento con sus campos. Al final destruye',
            ' * la cabeza.',
            ' *',
            ' * Se usa desde _destruir_piso, que a su vez se usa desde',
            ' * _destruir_vehiculo_completo. Movido de Viaje.php a',
            ' * FuncionesAuxiliares.php en v74y para que Vehiculo.php',
            ' * y Empresa.php lo puedan usar sin depender de Viaje.php.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {',
            '    $cabeza = $nodo_piso->adyacente(\'asientos\');',
            '    if (!$cabeza) return;',
            '',
            '    // Recolectar todos los asientos menos la cabeza.',
            '    $asientos = [];',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {',
            '        $asientos[] = $actual;',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '',
            '    // Desenlazar el `siguiente` de TODOS los asientos.',
            '    foreach ($asientos as $a) {',
            '        $a->eliminar_adyacente(\'siguiente\');',
            '    }',
            '',
            '    // Desenlazar el `primer` de la cabeza.',
            '    $cabeza->eliminar_adyacente(\'primer\');',
            '',
            '    // Desenlazar la cabeza del piso.',
            '    $nodo_piso->eliminar_adyacente(\'asientos\');',
            '',
            '    // Destruir cada asiento con sus campos. Las referencias',
            '    // externas (pasajero, venta) solo se desenlazan.',
            '    foreach ($asientos as $asiento) {',
            '        _destruir_campos_simples($asiento, [\'pasajero\', \'venta\']);',
            '        $asiento->eliminar_adyacente(\'pasajero\');',
            '        $asiento->eliminar_adyacente(\'venta\');',
            '        Nodo::eliminar($asiento);',
            '    }',
            '',
            '    // Destruir la cabeza.',
            '    _destruir_campos_simples($cabeza);',
            '    Nodo::eliminar($cabeza);',
            '}',
            '',
            '/**',
            ' * Destruye un piso: la lista circular de asientos, el nodo',
            ' * cabeza, y los campos filas/columnas.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_piso(Nodo $nodo_piso): void {',
            '    _destruir_lista_circular_asientos($nodo_piso);',
            '    _destruir_campos_simples($nodo_piso);',
            '    Nodo::eliminar($nodo_piso);',
            '}',
            '',
            '/**',
            ' * Destruye un vehículo completo: sus pisos, el contenedor de',
            ' * asientos, y el nodo vehículo con sus campos (nombre, foto).',
            ' *',
            ' * Se usa tanto para el vehículo original (empresa) como para',
            ' * la copia que se clona en un micro. La estructura es la',
            ' * misma en los dos casos.',
            ' *',
            ' * Antes de llamar a esta función, el llamador debe desenlazar',
            ' * el vehículo del contenedor que lo referencia.',
            ' *',
            ' * Movido de Viaje.php a FuncionesAuxiliares.php en v74y, y',
            ' * renombrado de _destruir_copia_vehiculo a',
            ' * _destruir_vehiculo_completo.',
            ' *',
            ' * @param Nodo $nodo_vehiculo',
            ' */',
            'function _destruir_vehiculo_completo(Nodo $nodo_vehiculo): void {',
            '    $nodo_asientos = $nodo_vehiculo->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso) {',
            '                // Desenlazar PRIMERO: el contenedor apunta al',
            '                // piso con `piso_$i`. Si no se desenlaza antes',
            '                // de destruir el piso, el piso tiene una',
            '                // referencia entrante y Nodo::eliminar falla.',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '                _destruir_piso($piso);',
            '            }',
            '        }',
            '        $nodo_vehiculo->eliminar_adyacente(\'asientos\');',
            '        _destruir_campos_simples($nodo_asientos);',
            '        Nodo::eliminar($nodo_asientos);',
            '    }',
            '    _destruir_campos_simples($nodo_vehiculo);',
            '    Nodo::eliminar($nodo_vehiculo);',
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
    // Viaje.php: sacar los 3 helpers movidos + renombrar llamadas
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.74y',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74v',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.74y',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Sacar _destruir_lista_circular_asientos (se movió)',
        'buscar' => [
            '/**',
            ' * Destruye la lista circular de asientos de un piso.',
            ' *',
            ' * Recolecta los asientos (todos menos la cabeza), rompe el',
            ' * círculo desenlazando el `siguiente` del último, desenlaza',
            ' * el `primer` de la cabeza, y destruye cada asiento con sus',
            ' * campos. Al final destruye la cabeza.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {',
            '    $cabeza = $nodo_piso->adyacente(\'asientos\');',
            '    if (!$cabeza) return;',
            '',
            '    // Recolectar todos los asientos menos la cabeza.',
            '    $asientos = [];',
            '    $actual = $cabeza->adyacente(\'primer\');',
            '    $seg = 0;',
            '    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {',
            '        $asientos[] = $actual;',
            '        $actual = $actual->adyacente(\'siguiente\');',
            '        $seg++;',
            '    }',
            '',
            '    // Desenlazar el `siguiente` de TODOS los asientos, no',
            '    // solo el del último. Cada asiento tiene un `siguiente`',
            '    // apuntando al próximo; si no se desenlaza antes de',
            '    // destruir, el próximo queda con una referencia entrante',
            '    // desde el asiento anterior.',
            '    foreach ($asientos as $a) {',
            '        $a->eliminar_adyacente(\'siguiente\');',
            '    }',
            '',
            '    // Desenlazar el `primer` de la cabeza.',
            '    $cabeza->eliminar_adyacente(\'primer\');',
            '',
            '    // Desenlazar la cabeza del piso: $nodo_piso->asientos',
            '    // apunta a la cabeza. Sin esto, la cabeza queda con una',
            '    // referencia entrante desde el piso y Nodo::eliminar',
            '    // falla silenciosamente, dejándola huérfana.',
            '    $nodo_piso->eliminar_adyacente(\'asientos\');',
            '',
            '    // Destruir cada asiento con sus campos. Las referencias',
            '    // externas (pasajero, venta) solo se desenlazan, no se',
            '    // destruyen.',
            '    foreach ($asientos as $asiento) {',
            '        _destruir_campos_simples($asiento, [\'pasajero\', \'venta\']);',
            '        $asiento->eliminar_adyacente(\'pasajero\');',
            '        $asiento->eliminar_adyacente(\'venta\');',
            '        Nodo::eliminar($asiento);',
            '    }',
            '',
            '    // Destruir la cabeza.',
            '    _destruir_campos_simples($cabeza);',
            '    Nodo::eliminar($cabeza);',
            '}',
            '',
            '/**',
            ' * Destruye un piso: la lista circular de asientos, el nodo',
            ' * cabeza, y los campos filas/columnas.',
            ' *',
            ' * @param Nodo $nodo_piso',
            ' */',
            'function _destruir_piso(Nodo $nodo_piso): void {',
            '    _destruir_lista_circular_asientos($nodo_piso);',
            '    _destruir_campos_simples($nodo_piso);',
            '    Nodo::eliminar($nodo_piso);',
            '}',
            '',
            '/**',
            ' * Destruye una copia de vehículo: pisos, contenedor de',
            ' * asientos y campos.',
            ' *',
            ' * @param Nodo $nodo_copia',
            ' */',
            'function _destruir_copia_vehiculo(Nodo $nodo_copia): void {',
            '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
            '    if ($nodo_asientos) {',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso) {',
            '                // Desenlazar PRIMERO: el contenedor apunta al',
            '                // piso con `piso_$i`. Si no se desenlaza antes',
            '                // de destruir el piso, el piso tiene una',
            '                // referencia entrante y Nodo::eliminar falla.',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '                _destruir_piso($piso);',
            '            }',
            '        }',
            '        // Desenlazar el contenedor de asientos de la copia',
            '        // antes de destruirlo.',
            '        $nodo_copia->eliminar_adyacente(\'asientos\');',
            '        _destruir_campos_simples($nodo_asientos);',
            '        Nodo::eliminar($nodo_asientos);',
            '    }',
            '    _destruir_campos_simples($nodo_copia);',
            '    Nodo::eliminar($nodo_copia);',
            '}',
            '',
            '/**',
            ' * Destruye un micro: copia de vehículo, campos, y desenlaza',
            ' * las referencias externas (empresa) y circulares (viaje).',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye un micro: copia de vehículo, campos, y desenlaza',
            ' * las referencias externas (empresa) y circulares (viaje).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Renombrar _destruir_copia_vehiculo en _destruir_micro',
        'buscar' => [
            '        // Desenlazar PRIMERO: el micro apunta a la copia. Si no',
            '        // se desenlaza antes de destruir la copia, la copia',
            '        // tiene una referencia entrante y Nodo::eliminar falla.',
            '        $nodo_micro->eliminar_adyacente(\'vehiculo_copia\');',
            '        _destruir_copia_vehiculo($nodo_copia);',
        ],
        'reemplazar' => [
            '        // Desenlazar PRIMERO: el micro apunta a la copia. Si no',
            '        // se desenlaza antes de destruir la copia, la copia',
            '        // tiene una referencia entrante y Nodo::eliminar falla.',
            '        $nodo_micro->eliminar_adyacente(\'vehiculo_copia\');',
            '        _destruir_vehiculo_completo($nodo_copia);',
        ],
    ],

    // --------------------------------------------------------
    // Vehiculo.php: fixes
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Bump @version a 1.5piloto.74y',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.74y',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            '/**',
            ' * Lista los vehículos asociados a una empresa.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Lista los vehículos asociados a una empresa.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Fix actualizar_configuracion_vehiculo: destruir pisos viejos',
        'buscar' => [
            '        // Eliminar pisos anteriores',
            '        $nodo_asientos->eliminar_adyacente(\'piso_1\');',
            '        $nodo_asientos->eliminar_adyacente(\'piso_2\');',
        ],
        'reemplazar' => [
            '        // Destruir los pisos anteriores antes de reemplazarlos.',
            '        // Fase 2, v74y: antes solo se desenlazaban, dejando los',
            '        // pisos completos (con sus listas circulares de',
            '        // asientos) huérfanos. ~100 nodos por reconfiguración.',
            '        for ($i = 1; $i <= 2; $i++) {',
            '            $piso_viejo = $nodo_asientos->adyacente("piso_$i");',
            '            if ($piso_viejo) {',
            '                $nodo_asientos->eliminar_adyacente("piso_$i");',
            '                _destruir_piso($piso_viejo);',
            '            }',
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Fix eliminar_vehiculo: destruir el vehículo completo',
        'buscar' => [
            '        // TODO: Aquí falta eliminar nodos huérfanos (pisos, asientos, foto, etc.).',
            '        // Por ahora solo se elimina el enlace.',
            '        $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
            '',
            '        guardar_ambos(Conf::NOMBRE_APP);',
            '        return [\'exito\' => true];',
        ],
        'reemplazar' => [
            '        // Fase 2, v74y: destruir el vehículo completo (asientos,',
            '        // pisos, listas circulares de asientos, campos nombre y',
            '        // foto). Antes solo se desenlazaba del contenedor,',
            '        // dejando ~100 nodos huérfanos por vehículo.',
            '        $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
            '        _destruir_vehiculo_completo($nodo_vehiculo);',
            '',
            '        guardar_ambos(Conf::NOMBRE_APP);',
            '        return [\'exito\' => true];',
        ],
    ],

    // --------------------------------------------------------
    // Empresa.php: fixes
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Bump @version a 1.5piloto.74y',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.74y',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            '/**',
            ' * Lista las empresas asociadas a un dueño.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Lista las empresas asociadas a un dueño.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Fix eliminar_empresa: destruir vehículos, contenedor y empresa',
        'buscar' => [
            '    // Eliminar vehículos asociados (se eliminan enlaces, nodos quedan huérfanos)',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if ($nodo_vehiculos) {',
            '        foreach ($nodo_vehiculos->adyacentes() as $nombre_vehiculo => $nodo_vehiculo) {',
            '            // TODO: Aquí se deberían eliminar los nodos huérfanos (pisos, asientos, etc.)',
            '            // Por ahora solo se elimina el enlace, dejando nodos inaccesibles.',
            '            $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
            '        }',
            '    }',
            '',
            '    // Eliminar enlace de la empresa',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '    // Fase 2, v74y: destruir cada vehículo completo (asientos,',
            '    // pisos, listas circulares de asientos, campos), después',
            '    // el contenedor `vehiculos`, y finalmente el nodo empresa',
            '    // con sus campos. Antes solo se desenlazaban los enlaces,',
            '    // dejando N × ~100 nodos huérfanos por empresa.',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if ($nodo_vehiculos) {',
            '        $adyacentes_vehiculos = (array) $nodo_vehiculos->adyacentes();',
            '        foreach ($adyacentes_vehiculos as $nombre_vehiculo => $nodo_vehiculo) {',
            '            $nodo_vehiculos->eliminar_adyacente((string)$nombre_vehiculo);',
            '            _destruir_vehiculo_completo($nodo_vehiculo);',
            '        }',
            '        $nodo_empresa->eliminar_adyacente(\'vehiculos\');',
            '        _destruir_campos_simples($nodo_vehiculos);',
            '        Nodo::eliminar($nodo_vehiculos);',
            '    }',
            '',
            '    // Desenlazar la empresa del contenedor del dueño y destruirla.',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '    _destruir_campos_simples($nodo_empresa);',
            '    Nodo::eliminar($nodo_empresa);',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v74y',
        'buscar' => [
            '- **v74x**: Fase 2, séptimo y octavo flujo arreglados:',
        ],
        'reemplazar' => [
            '- **v74y**: Fase 2, noveno, décimo y undécimo flujo',
            '  arreglados. `actualizar_configuracion_vehiculo` ahora',
            '  destruye los pisos viejos antes de reemplazarlos.',
            '  `eliminar_vehiculo` destruye el vehículo completo.',
            '  `eliminar_empresa` destruye todos sus vehículos y la',
            '  empresa. Se mueven `_destruir_lista_circular_asientos`,',
            '  `_destruir_piso` y `_destruir_copia_vehiculo` (renombrada',
            '  a `_destruir_vehiculo_completo`) de `Viaje.php` a',
            '  `FuncionesAuxiliares.php`, para que `Vehiculo.php` y',
            '  `Empresa.php` las puedan usar sin depender de',
            '  `Viaje.php`. Detectados por el detector ampliado de',
            '  fugas (`miscelaneas/detectar_fugas_eliminar.php`).',
            '- **v74x**: Fase 2, séptimo y octavo flujo arreglados:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujos 9, 10 y 11',
        'buscar' => [
            '**Séptimo y octavo flujo arreglados en v74x:**',
        ],
        'reemplazar' => [
            '**Noveno, décimo y undécimo flujo arreglados en v74y:**',
            '`actualizar_configuracion_vehiculo` (destruye los pisos',
            'viejos antes de reemplazarlos), `eliminar_vehiculo`',
            '(destruye el vehículo completo: asientos, pisos, listas',
            'circulares de asientos, campos) y `eliminar_empresa`',
            '(destruye todos sus vehículos y la empresa). Se movieron',
            'tres helpers de `Viaje.php` a `FuncionesAuxiliares.php`:',
            '`_destruir_lista_circular_asientos`, `_destruir_piso`, y',
            '`_destruir_copia_vehiculo` (renombrada a',
            '`_destruir_vehiculo_completo`). Fueron detectados por el',
            'detector ampliado de fugas',
            '(`miscelaneas/detectar_fugas_eliminar.php`).',
            '',
            '**Séptimo y octavo flujo arreglados en v74x:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v74y',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74x',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74y',
            '(Fase 2, flujos 9, 10 y 11: `actualizar_configuracion_vehiculo`,',
            '`eliminar_vehiculo`, `eliminar_empresa`. Se movieron tres',
            'helpers de `Viaje.php` a `FuncionesAuxiliares.php`.',
            'Detectados por el detector ampliado de fugas.).',
            'Antes: v1.5piloto.74x',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v74y',
        'buscar' => [
            '- Cerramos en v74x los flujos 7 y 8 de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v74y los flujos 9, 10 y 11 de Fase 2:',
            '  `actualizar_configuracion_vehiculo`, `eliminar_vehiculo`',
            '  y `eliminar_empresa`. Se movieron tres helpers de',
            '  destrucción de `Viaje.php` a `FuncionesAuxiliares.php`',
            '  (`_destruir_lista_circular_asientos`, `_destruir_piso`,',
            '  `_destruir_copia_vehiculo` renombrada a',
            '  `_destruir_vehiculo_completo`). Flujos identificados',
            '  por el detector ampliado de fugas.',
            '- Cerramos en v74x los flujos 7 y 8 de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v74y',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74x (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74y (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v74y',
        'buscar' => [
            'v74x: flujos 7 y 8',
            '(`seleccionar_asiento_micro` con `limpiar_lista`,',
            'y `deseleccionar_asiento_micro`).',
        ],
        'reemplazar' => [
            'v74x: flujos 7 y 8',
            '(`seleccionar_asiento_micro` con `limpiar_lista`,',
            'y `deseleccionar_asiento_micro`). v74y: flujos 9, 10',
            'y 11 (`actualizar_configuracion_vehiculo`,',
            '`eliminar_vehiculo`, `eliminar_empresa`).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.5: anotar el detector ampliado',
        'buscar' => [
            '**Implementados (ya no son pendientes):**',
        ],
        'reemplazar' => [
            '**Herramientas de diagnóstico disponibles:**',
            '',
            '- `miscelaneas/detectar_adyacente_en_true.php`: encuentra',
            '  usos de `_adyacente_en(..., true)`. Los 3 del piloto son',
            '  correctos (ver §8.6).',
            '- `miscelaneas/detectar_fugas_eliminar.php`: encuentra',
            '  `eliminar_adyacente` / `eliminar_hmi` / `eliminar_hd`',
            '  sin destrucción posterior en las 10 líneas siguientes.',
            '  Reporta candidatos clasificados en "PROBABLE FUGA"',
            '  (eliminar_hmi/hd sin retorno usado) y "REVISAR".',
            '  Sirve para priorizar flujos nuevos de Fase 2.',
            '',
            '**Implementados (ya no son pendientes):**',
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