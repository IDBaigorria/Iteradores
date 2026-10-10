<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77i (Fase B2.3.5b.3 del modelo topológico).
 *   - Pasajero.php: contexto opcional en las funciones del terminal
 *     (`obtener_contenedor_pasajeros_dueno`, `obtener_pasajero_nodo_por_dni`,
 *     `listar_pasajeros`, `buscar_pasajeros`, `obtener_pasajero_por_dni`,
 *     `crear_pasajero`, `actualizar_pasajero`).
 *   - Vehiculo.php: contexto opcional en `listar_vehiculos_de_empresa`.
 *   - Enrutador.php: `empresas/listar`, `vehiculos/listar` y las 5
 *     acciones de pasajeros del terminal pasan el contexto del
 *     solicitante.
 *   - Empresa.php: bump.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Sin cambio de comportamiento hoy. Falta B2.3.5b.4 (pruebas).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Empresa.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: bump @version a 1.5piloto.77i',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.76u',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77i',
        ],
    ],

    // ============================================================
    // Vehiculo.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo.php: bump @version a 1.5piloto.77i',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.75a',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77i',
        ],
    ],

    // ============================================================
    // Vehiculo.php — listar_vehiculos_de_empresa con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo.php: listar con contexto + helper',
        'buscar' => [
            'function listar_vehiculos_de_empresa(string $nombre_empresa): array {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) return [];',
            '',
            '        $adyacentes = $nodo_vehiculos->adyacentes();',
            '        if (!$adyacentes) return [];',
            '',
            '        $vehiculos = [];',
            '        foreach ($adyacentes as $nombre_vehiculo => $nodo_vehiculo) {',
            '            $nombre_vehiculo = (string)$nombre_vehiculo;',
            '            $nodo_nombre = $nodo_vehiculo->adyacente(\'nombre\');',
            '            $nodo_asientos = $nodo_vehiculo->adyacente(\'asientos\');',
            '            $asientos = $nodo_asientos ? $nodo_asientos->dato() : \'0\';',
            '',
            '            // Foto',
            '            $nodo_foto = $nodo_vehiculo->adyacente(\'foto\');',
            '            $foto = $nodo_foto ? $nodo_foto->dato() : \'\';',
            '',
            '            // Configuración',
            '            $configuracion = [\'pisos\' => []];',
            '            if ($nodo_asientos) {',
            '                for ($i = 1; $i <= 2; $i++) {',
            '                    $piso = $nodo_asientos->adyacente("piso_$i");',
            '                    if ($piso) {',
            '                        $config_piso = obtener_configuracion_piso($piso);',
            '                        if ($config_piso) {',
            '                            $configuracion[\'pisos\'][] = $config_piso;',
            '                        }',
            '                    }',
            '                }',
            '            }',
            '',
            '            $vehiculos[] = [',
            '                \'nombre_vehiculo\' => $nombre_vehiculo,',
            '                \'nombre\' => $nodo_nombre ? $nodo_nombre->dato() : $nombre_vehiculo,',
            '                \'asientos\' => $asientos,',
            '                \'foto\' => $foto,',
            '                \'configuracion\' => $configuracion',
            '            ];',
            '        }',
            '        return $vehiculos;',
            '    }',
            '    return [];',
            '}',
        ],
        'reemplazar' => [
            'function listar_vehiculos_de_empresa(string $nombre_empresa, ?Nodo $nodo_contexto = null): array {',
            '    // Fase B2.3.5b.3: contexto opcional. Si viene (terminal),',
            '    // navega por su compartido.',
            '    if ($nodo_contexto !== null) {',
            '        $nodo_empresas = $nodo_contexto->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) return [];',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) return [];',
            '        return _formatear_vehiculos_de_empresa($nodo_empresa);',
            '    }',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        return _formatear_vehiculos_de_empresa($nodo_empresa);',
            '    }',
            '    return [];',
            '}',
            '',
            '/**',
            ' * Formatea la lista de vehículos de una empresa.',
            ' *',
            ' * Extraído de listar_vehiculos_de_empresa para que el',
            ' * llamador con contexto y el default compartan el código.',
            ' *',
            ' * @param Nodo $nodo_empresa',
            ' * @return array',
            ' */',
            'function _formatear_vehiculos_de_empresa(Nodo $nodo_empresa): array {',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) return [];',
            '',
            '    $adyacentes = $nodo_vehiculos->adyacentes();',
            '    if (!$adyacentes) return [];',
            '',
            '    $vehiculos = [];',
            '    foreach ($adyacentes as $nombre_vehiculo => $nodo_vehiculo) {',
            '        $nombre_vehiculo = (string)$nombre_vehiculo;',
            '        $nodo_nombre = $nodo_vehiculo->adyacente(\'nombre\');',
            '        $nodo_asientos = $nodo_vehiculo->adyacente(\'asientos\');',
            '        $asientos = $nodo_asientos ? $nodo_asientos->dato() : \'0\';',
            '',
            '        // Foto',
            '        $nodo_foto = $nodo_vehiculo->adyacente(\'foto\');',
            '        $foto = $nodo_foto ? $nodo_foto->dato() : \'\';',
            '',
            '        // Configuración',
            '        $configuracion = [\'pisos\' => []];',
            '        if ($nodo_asientos) {',
            '            for ($i = 1; $i <= 2; $i++) {',
            '                $piso = $nodo_asientos->adyacente("piso_$i");',
            '                if ($piso) {',
            '                    $config_piso = obtener_configuracion_piso($piso);',
            '                    if ($config_piso) {',
            '                        $configuracion[\'pisos\'][] = $config_piso;',
            '                    }',
            '                }',
            '            }',
            '        }',
            '',
            '        $vehiculos[] = [',
            '            \'nombre_vehiculo\' => $nombre_vehiculo,',
            '            \'nombre\' => $nodo_nombre ? $nodo_nombre->dato() : $nombre_vehiculo,',
            '            \'asientos\' => $asientos,',
            '            \'foto\' => $foto,',
            '            \'configuracion\' => $configuracion',
            '        ];',
            '    }',
            '    return $vehiculos;',
            '}',
        ],
    ],

    // ============================================================
    // Pasajero.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: bump @version a 1.5piloto.77i',
        'buscar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.76',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.13',
            ' * @version   1.5piloto.77i',
        ],
    ],

    // ============================================================
    // Pasajero.php — obtener_contenedor_pasajeros_dueno con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: obtener_contenedor con contexto',
        'buscar' => [
            'function obtener_contenedor_pasajeros_dueno(string $nombre_dueno): ?Nodo {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
        ],
        'reemplazar' => [
            'function obtener_contenedor_pasajeros_dueno(string $nombre_dueno, ?Nodo $nodo_contexto = null): ?Nodo {',
            '    // Fase B2.3.5b.3: contexto opcional. Si viene (terminal),',
            '    // navega por su compartido.',
            '    if ($nodo_contexto !== null) {',
            '        $nodo_dueno = $nodo_contexto;',
            '    } else {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) return null;',
            '        $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    }',
            '    if (!$nodo_dueno) return null;',
        ],
    ],

    // ============================================================
    // Pasajero.php — obtener_pasajero_nodo_por_dni con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: obtener_pasajero_nodo con contexto',
        'buscar' => [
            'function obtener_pasajero_nodo_por_dni(string $nombre_dueno, string $dni): ?Nodo {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
        ],
        'reemplazar' => [
            'function obtener_pasajero_nodo_por_dni(string $nombre_dueno, string $dni, ?Nodo $nodo_contexto = null): ?Nodo {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
        ],
    ],

    // ============================================================
    // Pasajero.php — listar_pasajeros con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: listar_pasajeros con contexto',
        'buscar' => [
            'function listar_pasajeros(string $nombre_dueno): array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return [];',
        ],
        'reemplazar' => [
            'function listar_pasajeros(string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return [];',
        ],
    ],

    // ============================================================
    // Pasajero.php — buscar_pasajeros con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: buscar_pasajeros con contexto',
        'buscar' => [
            'function buscar_pasajeros(string $nombre_dueno, string $termino): array {',
            '    $termino = strtolower(trim($termino));',
            '    $todos = listar_pasajeros($nombre_dueno);',
        ],
        'reemplazar' => [
            'function buscar_pasajeros(string $nombre_dueno, string $termino, ?Nodo $nodo_contexto = null): array {',
            '    $termino = strtolower(trim($termino));',
            '    $todos = listar_pasajeros($nombre_dueno, $nodo_contexto);',
        ],
    ],

    // ============================================================
    // Pasajero.php — obtener_pasajero_por_dni con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: obtener_pasajero_por_dni con contexto',
        'buscar' => [
            'function obtener_pasajero_por_dni(string $nombre_dueno, string $dni): ?array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return null;',
        ],
        'reemplazar' => [
            'function obtener_pasajero_por_dni(string $nombre_dueno, string $dni, ?Nodo $nodo_contexto = null): ?array {',
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return null;',
        ],
    ],

    // ============================================================
    // Pasajero.php — crear_pasajero con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: crear_pasajero con contexto',
        'buscar' => [
            'function crear_pasajero(string $nombre_dueno, array $datos): array {',
        ],
        'reemplazar' => [
            'function crear_pasajero(string $nombre_dueno, array $datos, ?Nodo $nodo_contexto = null): array {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: crear_pasajero con contexto (2)',
        'buscar' => [
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'Dueno no encontrado\'];',
            '',
            '    if ($contenedor->adyacente($dni_norm)) {',
            '        return [\'exito\' => false, \'error\' => \'Ya existe un pasajero con ese DNI\'];',
            '    }',
        ],
        'reemplazar' => [
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'Dueno no encontrado\'];',
            '',
            '    if ($contenedor->adyacente($dni_norm)) {',
            '        return [\'exito\' => false, \'error\' => \'Ya existe un pasajero con ese DNI\'];',
            '    }',
        ],
    ],

    // ============================================================
    // Pasajero.php — actualizar_pasajero con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: actualizar_pasajero con contexto',
        'buscar' => [
            'function actualizar_pasajero(string $nombre_dueno, string $dni, array $datos): array {',
        ],
        'reemplazar' => [
            'function actualizar_pasajero(string $nombre_dueno, string $dni, array $datos, ?Nodo $nodo_contexto = null): array {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: actualizar_pasajero con contexto (2)',
        'buscar' => [
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'No hay pasajeros registrados\'];',
            '',
            '    $nodo_pasajero = $contenedor->adyacente($dni);',
            '    if (!$nodo_pasajero) return [\'exito\' => false, \'error\' => \'Pasajero no encontrado\'];',
            '',
            '    $campos = [\'nombres\', \'apellido\', \'email\', \'celular\', \'celular_emergencia\', \'fecha_nacimiento\', \'localidad\', \'direccion\'];',
        ],
        'reemplazar' => [
            '    $contenedor = obtener_contenedor_pasajeros_dueno($nombre_dueno, $nodo_contexto);',
            '    if (!$contenedor) return [\'exito\' => false, \'error\' => \'No hay pasajeros registrados\'];',
            '',
            '    $nodo_pasajero = $contenedor->adyacente($dni);',
            '    if (!$nodo_pasajero) return [\'exito\' => false, \'error\' => \'Pasajero no encontrado\'];',
            '',
            '    $campos = [\'nombres\', \'apellido\', \'email\', \'celular\', \'celular_emergencia\', \'fecha_nacimiento\', \'localidad\', \'direccion\'];',
        ],
    ],

    // ============================================================
    // Enrutador.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump @version a 1.5piloto.77i',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77h',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77i',
        ],
    ],

    // ============================================================
    // Enrutador.php — empresas/listar con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: empresas/listar con contexto',
        'buscar' => [
            '                    $empresas = listar_empresas_de_dueno($nombre_dueno);',
            '                    responder_json([\'exito\' => true, \'empresas\' => $empresas]);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $empresas = listar_empresas_de_dueno($nombre_dueno, $nodo_contexto);',
            '                    responder_json([\'exito\' => true, \'empresas\' => $empresas]);',
        ],
    ],

    // ============================================================
    // Enrutador.php — vehiculos/listar con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/listar con contexto',
        'buscar' => [
            '                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa);',
            '                    responder_json([\'exito\' => true, \'vehiculos\' => $vehiculos]);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa, $nodo_contexto);',
            '                    responder_json([\'exito\' => true, \'vehiculos\' => $vehiculos]);',
        ],
    ],

    // ============================================================
    // Enrutador.php — pasajeros/crear con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: pasajeros/crear con contexto',
        'buscar' => [
            '                    $resultado = crear_pasajero($nombre_dueno, $datos);',
            '                    responder_json($resultado);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = crear_pasajero($nombre_dueno, $datos, $nodo_contexto);',
            '                    responder_json($resultado);',
        ],
    ],

    // ============================================================
    // Enrutador.php — pasajeros/listar con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: pasajeros/listar con contexto',
        'buscar' => [
            '                    responder_json([\'exito\' => true, \'pasajeros\' => listar_pasajeros($nombre_dueno)]);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    responder_json([\'exito\' => true, \'pasajeros\' => listar_pasajeros($nombre_dueno, $nodo_contexto)]);',
        ],
    ],

    // ============================================================
    // Enrutador.php — pasajeros/buscar con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: pasajeros/buscar con contexto',
        'buscar' => [
            '                    responder_json([\'exito\' => true, \'pasajeros\' => buscar_pasajeros($nombre_dueno, $termino)]);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    responder_json([\'exito\' => true, \'pasajeros\' => buscar_pasajeros($nombre_dueno, $termino, $nodo_contexto)]);',
        ],
    ],

    // ============================================================
    // Enrutador.php — pasajeros/obtener con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: pasajeros/obtener con contexto',
        'buscar' => [
            '                    $pasajero = obtener_pasajero_por_dni($nombre_dueno, $dni);',
            '                    if ($pasajero) {',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $pasajero = obtener_pasajero_por_dni($nombre_dueno, $dni, $nodo_contexto);',
            '                    if ($pasajero) {',
        ],
    ],

    // ============================================================
    // Enrutador.php — pasajeros/actualizar con contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: pasajeros/actualizar con contexto',
        'buscar' => [
            '                    $resultado = actualizar_pasajero($nombre_dueno, $dni, $datos);',
            '                    responder_json($resultado);',
        ],
        'reemplazar' => [
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $resultado = actualizar_pasajero($nombre_dueno, $dni, $datos, $nodo_contexto);',
            '                    responder_json($resultado);',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77i',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77h',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77i',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77i',
        'buscar' => [
            '**Tanda actual:** v77h (Fase B2.3.5b.2: contexto en',
            'cambiar_asiento_pasaje y reservas).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77i (Fase B2.3.5b.3: contexto en',
            'empresas, vehículos y pasajeros).',
            '',
            '**v77i — Fase B2.3.5b.3.**',
            '',
            '- `Pasajero.php`: contexto opcional en',
            '  `obtener_contenedor_pasajeros_dueno`,',
            '  `obtener_pasajero_nodo_por_dni`, `listar_pasajeros`,',
            '  `buscar_pasajeros`, `obtener_pasajero_por_dni`,',
            '  `crear_pasajero`, `actualizar_pasajero`.',
            '- `Vehiculo.php`: contexto opcional en',
            '  `listar_vehiculos_de_empresa`. Nuevo helper',
            '  `_formatear_vehiculos_de_empresa`.',
            '- `Enrutador.php`: `empresas/listar`, `vehiculos/listar`,',
            '  `pasajeros/crear`, `pasajeros/listar`, `pasajeros/buscar`,',
            '  `pasajeros/obtener` y `pasajeros/actualizar` pasan el',
            '  contexto del solicitante.',
            '',
            'Sin cambio de comportamiento hoy. Falta B2.3.5b.4 (pruebas',
            'del plugin).',
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