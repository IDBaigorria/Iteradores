<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP + JS).
 *
 * Tanda V1.5piloto.77v (Fase B3.3d).
 *   - Vehiculo.php: 6 funciones reciben $nombre_dueno y resuelven
 *     por `us_<nombre>` en lugar de iterar sobre todos los dueños.
 *   - Enrutador.php: pasa $nombre_dueno a las 6 acciones de vehiculos/*.
 *   - micros.js: manda $nombre_dueno en cada POST de vehiculos/*.
 *   - aplicacion_GET.html: bump del ?v= de micros.js.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Vehiculo.php — listar_vehiculos_de_empresa
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: listar por us_ + firma',
        'buscar' => [
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
        ],
        'reemplazar' => [
            'function listar_vehiculos_de_empresa(string $nombre_empresa, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
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
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [];',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [];',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [];',
            '    return _formatear_vehiculos_de_empresa($nodo_empresa);',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — agregar_vehiculo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: agregar por us_ + firma',
        'buscar' => [
            'function agregar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_real = \'\'): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre del vehículo es obligatorio\'];',
            '    }',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) {',
            '            $nodo_vehiculos = Nodo::crear_con_dato(\'\');',
            '            $nodo_empresa->_adyacente_en($nodo_vehiculos, \'vehiculos\');',
            '        }',
            '',
            '        if ($nodo_vehiculos->adyacente($nombre_vehiculo)) {',
            '            return [\'exito\' => false, \'error\' => \'Ya existe un vehículo con ese nombre\'];',
            '        }',
            '',
            '        $nodo_vehiculo = Nodo::crear_con_dato($nombre_vehiculo);',
            '        $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_vehiculo), \'nombre\');',
            '        $nodo_asientos = Nodo::crear_con_dato(\'0\');',
            '        $nodo_vehiculo->_adyacente_en($nodo_asientos, \'asientos\');',
            '',
            '        $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);',
            '',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true];',
            '    }',
            '    return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '}',
        ],
        'reemplazar' => [
            'function agregar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, string $nombre_real = \'\'): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre del vehículo es obligatorio\'];',
            '    }',
            '',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) {',
            '        $nodo_vehiculos = Nodo::crear_con_dato(\'\');',
            '        $nodo_empresa->_adyacente_en($nodo_vehiculos, \'vehiculos\');',
            '    }',
            '',
            '    if ($nodo_vehiculos->adyacente($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'Ya existe un vehículo con ese nombre\'];',
            '    }',
            '',
            '    $nodo_vehiculo = Nodo::crear_con_dato($nombre_vehiculo);',
            '    $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_vehiculo), \'nombre\');',
            '    $nodo_asientos = Nodo::crear_con_dato(\'0\');',
            '    $nodo_vehiculo->_adyacente_en($nodo_asientos, \'asientos\');',
            '',
            '    $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — actualizar_vehiculo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: actualizar por us_ + firma',
        'buscar' => [
            'function actualizar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $datos): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El identificador del vehículo es obligatorio\'];',
            '    }',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) {',
            '        return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    }',
            '',
            '    // Buscar la empresa globalmente',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) {',
            '            return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos registrados\'];',
            '        }',
            '',
            '        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '        if (!$nodo_vehiculo) {',
            '            return [\'exito\' => false, \'error\' => \'Vehículo no encontrado en esta empresa\'];',
            '        }',
            '',
            '        // Actualizar nombre visible',
            '        if (array_key_exists(\'nombre_real\', $datos)) {',
            '            $nombre_real = trim($datos[\'nombre_real\']);',
            '            if (empty($nombre_real)) {',
            '                $nombre_real = $nombre_vehiculo;',
            '            }',
            '            $nodo_nombre = $nodo_vehiculo->adyacente(\'nombre\');',
            '            if ($nodo_nombre) {',
            '                $nodo_nombre->_dato($nombre_real);',
            '            } else {',
            '                $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real), \'nombre\');',
            '            }',
            '        }',
            '',
            '        // Actualizar cantidad de asientos',
            '        if (array_key_exists(\'asientos\', $datos)) {',
            '            $asientos = (int)$datos[\'asientos\'];',
            '            if ($asientos <= 0) {',
            '                return [\'exito\' => false, \'error\' => \'La cantidad de asientos debe ser mayor que cero\'];',
            '            }',
            '            $nodo_asientos = $nodo_vehiculo->adyacente(\'asientos\');',
            '            if ($nodo_asientos) {',
            '                $nodo_asientos->_dato((string)$asientos);',
            '            } else {',
            '                $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), \'asientos\');',
            '            }',
            '        }',
            '',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true];',
            '    }',
            '',
            '    return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '}',
        ],
        'reemplazar' => [
            'function actualizar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, array $datos): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El identificador del vehículo es obligatorio\'];',
            '    }',
            '',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) {',
            '        return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos registrados\'];',
            '    }',
            '',
            '    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '    if (!$nodo_vehiculo) {',
            '        return [\'exito\' => false, \'error\' => \'Vehículo no encontrado en esta empresa\'];',
            '    }',
            '',
            '    // Actualizar nombre visible',
            '    if (array_key_exists(\'nombre_real\', $datos)) {',
            '        $nombre_real = trim($datos[\'nombre_real\']);',
            '        if (empty($nombre_real)) {',
            '            $nombre_real = $nombre_vehiculo;',
            '        }',
            '        $nodo_nombre = $nodo_vehiculo->adyacente(\'nombre\');',
            '        if ($nodo_nombre) {',
            '            $nodo_nombre->_dato($nombre_real);',
            '        } else {',
            '            $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real), \'nombre\');',
            '        }',
            '    }',
            '',
            '    // Actualizar cantidad de asientos',
            '    if (array_key_exists(\'asientos\', $datos)) {',
            '        $asientos = (int)$datos[\'asientos\'];',
            '        if ($asientos <= 0) {',
            '            return [\'exito\' => false, \'error\' => \'La cantidad de asientos debe ser mayor que cero\'];',
            '        }',
            '        $nodo_asientos = $nodo_vehiculo->adyacente(\'asientos\');',
            '        if ($nodo_asientos) {',
            '            $nodo_asientos->_dato((string)$asientos);',
            '        } else {',
            '            $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), \'asientos\');',
            '        }',
            '    }',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — actualizar_configuracion_vehiculo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: actualizar_configuracion por us_ + firma',
        'buscar' => [
            'function actualizar_configuracion_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $configuracion): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El identificador del vehículo es obligatorio\'];',
            '    }',
            '',
            '    if (!isset($configuracion[\'pisos\']) || !is_array($configuracion[\'pisos\'])) {',
            '        return [\'exito\' => false, \'error\' => \'Configuración inválida\'];',
            '    }',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) {',
            '        return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    }',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) {',
            '            return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '        }',
            '',
            '        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '        if (!$nodo_vehiculo) {',
            '            return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '        }',
        ],
        'reemplazar' => [
            'function actualizar_configuracion_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, array $configuracion): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'El identificador del vehículo es obligatorio\'];',
            '    }',
            '',
            '    if (!isset($configuracion[\'pisos\']) || !is_array($configuracion[\'pisos\'])) {',
            '        return [\'exito\' => false, \'error\' => \'Configuración inválida\'];',
            '    }',
            '',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) {',
            '        return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '    }',
            '',
            '    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '    if (!$nodo_vehiculo) {',
            '        return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '    }',
        ],
    ],

    // ============================================================
    // Vehiculo.php — actualizar_configuracion: cierre del foreach
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: actualizar_configuracion cierre del foreach',
        'buscar' => [
            '        $nodo_asientos->_dato((string)$total_asientos);',
            '',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true];',
            '    }',
            '',
            '    return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '}',
        ],
        'reemplazar' => [
            '    $nodo_asientos->_dato((string)$total_asientos);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — eliminar_vehiculo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: eliminar por us_ + firma',
        'buscar' => [
            'function eliminar_vehiculo(string $nombre_empresa, string $nombre_vehiculo): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'Identificador de vehículo obligatorio\'];',
            '    }',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '        if (!$nodo_empresa) continue;',
            '',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '',
            '        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '        if (!$nodo_vehiculo) return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '',
            '        // Fase 2, v74y: destruir el vehículo completo (asientos,',
            '        // pisos, listas circulares de asientos, campos nombre y',
            '        // foto). Antes solo se desenlazaba del contenedor,',
            '        // dejando ~100 nodos huérfanos por vehículo.',
            '        $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
            '        _destruir_vehiculo_completo($nodo_vehiculo);',
            '',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '        return [\'exito\' => true];',
            '    }',
            '',
            '    return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '}',
        ],
        'reemplazar' => [
            'function eliminar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno): array {',
            '    $nombre_vehiculo = trim($nombre_vehiculo);',
            '    if (empty($nombre_vehiculo)) {',
            '        return [\'exito\' => false, \'error\' => \'Identificador de vehículo obligatorio\'];',
            '    }',
            '',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) return [\'exito\' => false, \'error\' => \'El dueño no tiene empresas\'];',
            '    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);',
            '    if (!$nodo_empresa) return [\'exito\' => false, \'error\' => \'Empresa no encontrada\'];',
            '',
            '    $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '    if (!$nodo_vehiculos) return [\'exito\' => false, \'error\' => \'La empresa no tiene vehículos\'];',
            '',
            '    $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);',
            '    if (!$nodo_vehiculo) return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '',
            '    // Fase 2, v74y: destruir el vehículo completo (asientos,',
            '    // pisos, listas circulares de asientos, campos nombre y',
            '    // foto). Antes solo se desenlazaba del contenedor,',
            '    // dejando ~100 nodos huérfanos por vehículo.',
            '    $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);',
            '    _destruir_vehiculo_completo($nodo_vehiculo);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — subir_foto_vehiculo (firma + bucle)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: subir_foto firma y bucle',
        'buscar' => [
            'function subir_foto_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $archivo): array {',
        ],
        'reemplazar' => [
            'function subir_foto_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_dueno, array $archivo): array {',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: subir_foto bucle por us_',
        'buscar' => [
            '    // Buscar vehículo y guardar enlace (insensible a mayúsculas/minúsculas)',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '    }',
            '',
            '    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '        if (!$nodo_empresas) continue;',
            '',
            '        // Buscar empresa comparando sin mayúsculas',
            '        $empresa_encontrada = null;',
            '        foreach ($nodo_empresas->adyacentes() as $nombre_empresa_actual => $nodo_empresa_actual) {',
            '            if (strcasecmp($nombre_empresa_actual, $nombre_empresa) === 0) {',
            '                $empresa_encontrada = $nodo_empresa_actual;',
            '                $nombre_empresa = $nombre_empresa_actual; // usar el original',
            '                break;',
            '            }',
            '        }',
            '        if (!$empresa_encontrada) continue;',
            '',
            '        $nodo_vehiculos = $empresa_encontrada->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) continue;',
        ],
        'reemplazar' => [
            '    // Buscar vehículo y guardar enlace (insensible a mayúsculas/minúsculas)',
            '    // Fase B3.3d: ID especial directo.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        unlink($ruta_destino);',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if ($nodo_empresas) {',
            '        // Buscar empresa comparando sin mayúsculas',
            '        $empresa_encontrada = null;',
            '        foreach ($nodo_empresas->adyacentes() as $nombre_empresa_actual => $nodo_empresa_actual) {',
            '            if (strcasecmp($nombre_empresa_actual, $nombre_empresa) === 0) {',
            '                $empresa_encontrada = $nodo_empresa_actual;',
            '                $nombre_empresa = $nombre_empresa_actual; // usar el original',
            '                break;',
            '            }',
            '        }',
            '',
            '        $nodo_vehiculos = $empresa_encontrada ? $empresa_encontrada->adyacente(\'vehiculos\') : null;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: subir_foto cierre',
        'buscar' => [
            '        // Guardar persistencia',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true, \'foto\' => $ruta_relativa];',
            '    }',
            '',
            '    // Si no encontró vehículo, eliminar archivo',
            '    unlink($ruta_destino);',
            '    return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '}',
        ],
        'reemplazar' => [
            '        // Guardar persistencia',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '',
            '        return [\'exito\' => true, \'foto\' => $ruta_relativa];',
            '    }',
            '',
            '    // Si no encontró vehículo, eliminar archivo',
            '    unlink($ruta_destino);',
            '    return [\'exito\' => false, \'error\' => \'Vehículo no encontrado\'];',
            '}',
        ],
    ],

    // ============================================================
    // Vehiculo.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'descripcion' => 'Vehiculo: bump a 1.5piloto.77v',
        'buscar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77i',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.5',
            ' * @version   1.5piloto.77v',
        ],
    ],

    // ============================================================
    // Enrutador.php — acciones de vehiculos/*
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/listar pasa dueño',
        'buscar' => [
            '                case \'listar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    if (empty($nombre_empresa)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa no especificada\']);',
            '                    }',
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa, $nodo_contexto);',
            '                    responder_json([\'exito\' => true, \'vehiculos\' => $vehiculos]);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'listar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_empresa)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa no especificada\']);',
            '                    }',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    // Fase B2.3.5b.3: contexto del solicitante.',
            '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
            '                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa, $nombre_dueno, $nodo_contexto);',
            '                    responder_json([\'exito\' => true, \'vehiculos\' => $vehiculos]);',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/agregar pasa dueño',
        'buscar' => [
            '                case \'agregar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_real = $post[\'nombre_real\'] ?? \'\';',
            '                    $resultado = agregar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_real);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'agregar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_real = $post[\'nombre_real\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado = agregar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno, $nombre_real);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/actualizar pasa dueño',
        'buscar' => [
            '                case \'actualizar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_real = $post[\'nombre_real\'] ?? null;',
            '                    $asientos = $post[\'asientos\'] ?? null;',
            '',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '',
            '                    $datos = [];',
            '                    if ($nombre_real !== null) {',
            '                        $datos[\'nombre_real\'] = $nombre_real;',
            '                    }',
            '                    if ($asientos !== null) {',
            '                        $datos[\'asientos\'] = (int)$asientos;',
            '                    }',
            '',
            '                    $resultado = actualizar_vehiculo($nombre_empresa, $nombre_vehiculo, $datos);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'actualizar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_real = $post[\'nombre_real\'] ?? null;',
            '                    $asientos = $post[\'asientos\'] ?? null;',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '',
            '                    $datos = [];',
            '                    if ($nombre_real !== null) {',
            '                        $datos[\'nombre_real\'] = $nombre_real;',
            '                    }',
            '                    if ($asientos !== null) {',
            '                        $datos[\'asientos\'] = (int)$asientos;',
            '                    }',
            '',
            '                    $resultado = actualizar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno, $datos);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/actualizar_configuracion pasa dueño',
        'buscar' => [
            '                case \'actualizar_configuracion\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $configuracion_json = $post[\'configuracion\'] ?? \'\';',
            '',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '',
            '                    $configuracion = json_decode($configuracion_json, true);',
            '                    if (!is_array($configuracion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Configuración inválida\']);',
            '                    }',
            '',
            '                    $resultado = actualizar_configuracion_vehiculo($nombre_empresa, $nombre_vehiculo, $configuracion);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'actualizar_configuracion\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $configuracion_json = $post[\'configuracion\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '',
            '                    $configuracion = json_decode($configuracion_json, true);',
            '                    if (!is_array($configuracion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Configuración inválida\']);',
            '                    }',
            '',
            '                    $resultado = actualizar_configuracion_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno, $configuracion);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/eliminar pasa dueño',
        'buscar' => [
            '                case \'eliminar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    $resultado = eliminar_vehiculo($nombre_empresa, $nombre_vehiculo);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'subir_foto\':',
        ],
        'reemplazar' => [
            '                case \'eliminar\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado = eliminar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno);',
            '                    responder_json($resultado);',
            '                    break;',
            '                case \'subir_foto\':',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: vehiculos/subir_foto pasa dueño',
        'buscar' => [
            '                case \'subir_foto\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    if (!isset($_FILES[\'foto\'])) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Archivo no enviado\']);',
            '                    }',
            '                    $resultado = subir_foto_vehiculo($nombre_empresa, $nombre_vehiculo, $_FILES[\'foto\']);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'subir_foto\':',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    $nombre_vehiculo = $post[\'nombre_vehiculo\'] ?? \'\';',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Empresa y vehículo son obligatorios\']);',
            '                    }',
            '                    if (empty($nombre_dueno)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    if (!isset($_FILES[\'foto\'])) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Archivo no enviado\']);',
            '                    }',
            '                    $resultado = subir_foto_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno, $_FILES[\'foto\']);',
            '                    responder_json($resultado);',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: bump a 1.5piloto.77v',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77i',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77v',
        ],
    ],

    // ============================================================
    // micros.js — variable global + helper
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: variable + helper dueno',
        'buscar' => [
            '// Variable global para el editor de asientos',
            'let editor_pisos_actuales = [];',
        ],
        'reemplazar' => [
            '// Variable global para el editor de asientos',
            'let editor_pisos_actuales = [];',
            '',
            '// Fase B3.3d: nombre del dueño cuya pestaña Micros se está',
            '// gestionando. Se usa para mandar `nombre_dueno` en cada POST',
            '// de vehiculos/*. Para admin/soporte es el elegido en el',
            '// selector; para dueño es él mismo.',
            'let dueno_micros_seleccionado = \'\';',
            '',
            'function _obtener_nombre_dueno_micros() {',
            '    if (es_admin_o_soporte()) {',
            '        const select = document.getElementById(\'selector_dueno_micros\');',
            '        return dueno_micros_seleccionado || (select ? select.value : \'\') || \'\';',
            '    }',
            '    if (usuario_actual && usuario_actual.nivel === \'dueno\') {',
            '        return usuario_actual.nombre_usuario;',
            '    }',
            '    return \'\';',
            '}',
        ],
    ],

    // ============================================================
    // micros.js — cargar_datos_micros
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: inicializar dueno en cargar_datos_micros',
        'buscar' => [
            '    } else if (usuario_actual.nivel === \'dueno\') {',
            '        await cargar_empresas_de_dueno(usuario_actual.nombre_usuario);',
            '        $("#panel_empresas_micros").style.display = \'block\';',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '    } else if (usuario_actual.nivel === \'dueno\') {',
            '        dueno_micros_seleccionado = usuario_actual.nombre_usuario;',
            '        await cargar_empresas_de_dueno(usuario_actual.nombre_usuario);',
            '        $("#panel_empresas_micros").style.display = \'block\';',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // micros.js — select admin: guardar dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: select admin guarda dueno',
        'buscar' => [
            '            select.onchange = async () => {',
            '                const nombre_dueno = select.value;',
            '                if (nombre_dueno) {',
            '                    await cargar_empresas_de_dueno(nombre_dueno);',
            '                    $("#panel_empresas_micros").style.display = \'block\';',
            '                } else {',
            '                    $("#panel_empresas_micros").style.display = \'none\';',
            '                }',
            '            };',
        ],
        'reemplazar' => [
            '            select.onchange = async () => {',
            '                const nombre_dueno = select.value;',
            '                dueno_micros_seleccionado = nombre_dueno;',
            '                if (nombre_dueno) {',
            '                    await cargar_empresas_de_dueno(nombre_dueno);',
            '                    $("#panel_empresas_micros").style.display = \'block\';',
            '                } else {',
            '                    $("#panel_empresas_micros").style.display = \'none\';',
            '                }',
            '            };',
        ],
    ],

    // ============================================================
    // micros.js — cargar_vehiculos_de_empresa manda dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: cargar_vehiculos manda dueño',
        'buscar' => [
            'async function cargar_vehiculos_de_empresa(nombre_empresa) {',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa })',
            '        });',
        ],
        'reemplazar' => [
            'async function cargar_vehiculos_de_empresa(nombre_empresa) {',
            '    const nombre_dueno = _obtener_nombre_dueno_micros();',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "vehiculos/listar", nombre_empresa, nombre_dueno })',
            '        });',
        ],
    ],

    // ============================================================
    // micros.js — _guardar_vehiculo_desde_modal manda dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: alta vehiculo manda dueño',
        'buscar' => [
            '    const datos = {',
            '        accion: "vehiculos/agregar",',
            '        nombre_empresa,',
            '        nombre_vehiculo,',
            '        nombre_real',
            '    };',
        ],
        'reemplazar' => [
            '    const datos = {',
            '        accion: "vehiculos/agregar",',
            '        nombre_empresa,',
            '        nombre_vehiculo,',
            '        nombre_real,',
            '        nombre_dueno: _obtener_nombre_dueno_micros()',
            '    };',
        ],
    ],

    // ============================================================
    // micros.js — guardar_configuracion manda dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: guardar config manda dueño',
        'buscar' => [
            '    const datos = {',
            '        accion: "vehiculos/actualizar_configuracion",',
            '        nombre_empresa,',
            '        nombre_vehiculo,',
            '        configuracion: JSON.stringify(configuracion)',
            '    };',
        ],
        'reemplazar' => [
            '    const datos = {',
            '        accion: "vehiculos/actualizar_configuracion",',
            '        nombre_empresa,',
            '        nombre_vehiculo,',
            '        configuracion: JSON.stringify(configuracion),',
            '        nombre_dueno: _obtener_nombre_dueno_micros()',
            '    };',
        ],
    ],

    // ============================================================
    // micros.js — subir foto manda dueño
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: subir foto manda dueño',
        'buscar' => [
            '    const formData = new FormData();',
            '    formData.append(\'accion\', \'vehiculos/subir_foto\');',
            '    formData.append(\'nombre_empresa\', nombre_empresa);',
            '    formData.append(\'nombre_vehiculo\', nombre_vehiculo);',
            '    formData.append(\'foto\', archivo);',
        ],
        'reemplazar' => [
            '    const formData = new FormData();',
            '    formData.append(\'accion\', \'vehiculos/subir_foto\');',
            '    formData.append(\'nombre_empresa\', nombre_empresa);',
            '    formData.append(\'nombre_vehiculo\', nombre_vehiculo);',
            '    formData.append(\'nombre_dueno\', _obtener_nombre_dueno_micros());',
            '    formData.append(\'foto\', archivo);',
        ],
    ],

    // ============================================================
    // micros.js — bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/micros.js',
        'descripcion' => 'micros.js: bump a 1.5piloto.77v',
        'buscar' => [
            ' * @version 1.5piloto.76c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.77v',
        ],
    ],

    // ============================================================
    // aplicacion_GET.html — bump ?v= de micros.js
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de micros.js',
        'buscar' => [
            '<script src="Aplicacion/micros.js?v=1.5piloto.76c"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/micros.js?v=1.5piloto.77v"></script>',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual + cierre B3.3d
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77v',
        'buscar' => [
            '**Tanda actual:** v77u (B3.3b).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77v (B3.3d).',
            '',
            '**B3.3d (v77v).** `Vehiculo.php`: las 6 funciones reciben',
            '`$nombre_dueno` y resuelven por `us_<nombre>` en lugar de',
            'iterar sobre todos los dueños. `Enrutador.php`: las 6',
            'acciones de `vehiculos/*` pasan el dueño. `micros.js`:',
            'nuevo helper `_obtener_nombre_dueno_micros()` y las 5',
            'llamadas mandan `nombre_dueno`. Queda pendiente B3.3c',
            '(helpers que iteran sobre todos los dueños buscando por',
            'id) y B3.3e (Migraciones).',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — agregar v77v
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77v',
        'buscar' => [
            '- **v77u** (Fase B3.3b): `Pasajero.php` rama sin contexto',
        ],
        'reemplazar' => [
            '- **v77v** (Fase B3.3d): `Vehiculo.php` pasa a `us_<nombre>`',
            '  en sus 6 funciones. Cambio de firma: `$nombre_dueno`',
            '  como parámetro requerido. `Enrutador.php` pasa el dueño',
            '  en las 6 acciones de `vehiculos/*`. `micros.js` agrega',
            '  helper `_obtener_nombre_dueno_micros()` y manda',
            '  `nombre_dueno` en los 5 POST. `aplicacion_GET.html` bump',
            '  del `?v=` de micros.js.',
            '- **v77u** (Fase B3.3b): `Pasajero.php` rama sin contexto',
        ],
    ],

    // ============================================================
    // index.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77v',
        'buscar' => [
            ' * @version   1.5piloto.77u',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77v',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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