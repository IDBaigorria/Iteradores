<?php
/**
 * Aplicador — Fase 1 de §3: 5 endpoints backend de limpieza
 * de datos de prueba (ventas, cancelaciones, empresas/vehículos,
 * usuarios, terminales). Sin tocar la UI: los botones siguen
 * en la app, esto es solo el backend.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80a';

// No hay JS tocado: los PHP solo bumpean su @version.
$htmls_a_bumpear = [];

$cambios = [

    // ============================================================
    // 1. Empresa.php: agregar include de Vehiculo.php (para
    //    usar eliminar_vehiculo en la limpieza de prueba).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: agregar include de Vehiculo.php',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            'include_once("./Aplicacion/Compartidos.php");',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            'include_once("./Aplicacion/Compartidos.php");',
            'include_once("./Aplicacion/Vehiculos/Vehiculo.php");',
        ],
    ],

    // ============================================================
    // 2. Empresa.php: agregar limpiar_empresas_vehiculos_de_prueba
    //    al final del archivo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa.php: agregar limpiar_empresas_vehiculos_de_prueba',
        'buscar' => [
            '    // Desenlazar la empresa del contenedor del dueño y destruirla.',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '    _destruir_campos_simples($nodo_empresa);',
            '    Nodo::eliminar($nodo_empresa);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '    // Desenlazar la empresa del contenedor del dueño y destruirla.',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '    _destruir_campos_simples($nodo_empresa);',
            '    Nodo::eliminar($nodo_empresa);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
            '',
            '/**',
            ' * Elimina las empresas y vehículos "de prueba" de un dueño.',
            ' *',
            ' * Criterio por prefijo del identificador (clave del',
            ' * contenedor):',
            ' *   - Empresas: fotoauto, viajecambiomicro_emp_',
            ' *   - Vehículos: fotoauto, viajecambiomicro_veh_',
            ' *',
            ' * Si una empresa matchea, se destruye entera (con sus',
            ' * vehículos). Si no, se itera sus vehículos y se borran',
            ' * los que matcheen.',
            ' *',
            ' * Si un vehículo está referenciado por un micro de un',
            ' * viaje, eliminar_vehiculo falla y se reporta en',
            ' * `salteados`.',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' * @since 1.5piloto.80a',
            ' */',
            'function limpiar_empresas_vehiculos_de_prueba(string $nombre_dueno): array {',
            '    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '',
            '    $nodo_empresas = $nodo_dueno->adyacente(\'empresas\');',
            '    if (!$nodo_empresas) {',
            '        return [',
            '            \'exito\' => true,',
            '            \'empresas_borradas\' => [],',
            '            \'vehiculos_borrados\' => [],',
            '            \'cantidad_empresas_borradas\' => 0,',
            '            \'cantidad_vehiculos_borrados\' => 0,',
            '            \'salteados\' => [],',
            '            \'cantidad_salteados\' => 0,',
            '        ];',
            '    }',
            '',
            '    $prefijos_empresa = [\'fotoauto\', \'viajecambiomicro_emp_\'];',
            '    $prefijos_vehiculo = [\'fotoauto\', \'viajecambiomicro_veh_\'];',
            '',
            '    $empresas_borradas = [];',
            '    $vehiculos_borrados = [];',
            '    $salteados = [];',
            '',
            '    // Recolectar antes de modificar.',
            '    $empresas = (array) $nodo_empresas->adyacentes();',
            '',
            '    foreach ($empresas as $nombre_empresa => $nodo_empresa) {',
            '        $nombre_empresa = (string)$nombre_empresa;',
            '',
            '        $empresa_es_prueba = false;',
            '        foreach ($prefijos_empresa as $p) {',
            '            if (stripos($nombre_empresa, $p) === 0) {',
            '                $empresa_es_prueba = true;',
            '                break;',
            '            }',
            '        }',
            '',
            '        if ($empresa_es_prueba) {',
            '            $res = eliminar_empresa($nombre_dueno, $nombre_empresa);',
            '            if (is_array($res) && !empty($res[\'exito\'])) {',
            '                $empresas_borradas[] = $nombre_empresa;',
            '            } else {',
            '                $motivo = (is_array($res) && isset($res[\'error\'])) ? $res[\'error\'] : \'Error desconocido\';',
            '                $salteados[] = [\'nombre\' => $nombre_empresa, \'motivo\' => $motivo];',
            '            }',
            '            continue;',
            '        }',
            '',
            '        // Empresa no de prueba: revisar sus vehículos.',
            '        $nodo_vehiculos = $nodo_empresa->adyacente(\'vehiculos\');',
            '        if (!$nodo_vehiculos) continue;',
            '',
            '        $vehiculos = (array) $nodo_vehiculos->adyacentes();',
            '        foreach ($vehiculos as $nombre_vehiculo => $nodo_vehiculo) {',
            '            $nombre_vehiculo = (string)$nombre_vehiculo;',
            '',
            '            $vehiculo_es_prueba = false;',
            '            foreach ($prefijos_vehiculo as $p) {',
            '                if (stripos($nombre_vehiculo, $p) === 0) {',
            '                    $vehiculo_es_prueba = true;',
            '                    break;',
            '                }',
            '            }',
            '            if (!$vehiculo_es_prueba) continue;',
            '',
            '            $res = eliminar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno);',
            '            if (is_array($res) && !empty($res[\'exito\'])) {',
            '                $vehiculos_borrados[] = $nombre_empresa . \'/\' . $nombre_vehiculo;',
            '            } else {',
            '                $motivo = (is_array($res) && isset($res[\'error\'])) ? $res[\'error\'] : \'Error desconocido\';',
            '                $salteados[] = [\'nombre\' => $nombre_empresa . \'/\' . $nombre_vehiculo, \'motivo\' => $motivo];',
            '            }',
            '        }',
            '    }',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'empresas_borradas\' => $empresas_borradas,',
            '        \'vehiculos_borrados\' => $vehiculos_borrados,',
            '        \'cantidad_empresas_borradas\' => count($empresas_borradas),',
            '        \'cantidad_vehiculos_borrados\' => count($vehiculos_borrados),',
            '        \'salteados\' => $salteados,',
            '        \'cantidad_salteados\' => count($salteados),',
            '    ];',
            '}',
        ],
    ],

    // ============================================================
    // 3. Venta.php: agregar limpiar_ventas_de_prueba y
    //    limpiar_cancelaciones_de_prueba al final.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: agregar limpiar_ventas_de_prueba y limpiar_cancelaciones_de_prueba',
        'buscar' => [
            'function _nodo_esta_en_arbol(Nodo $contenedor, string $id_nodo): bool {',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        if ((string)$actual->dato() === $id_nodo) return true;',
            '        $actual = hd($actual);',
            '        $seg++;',
            '    }',
            '    return false;',
            '}',
        ],
        'reemplazar' => [
            'function _nodo_esta_en_arbol(Nodo $contenedor, string $id_nodo): bool {',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        if ((string)$actual->dato() === $id_nodo) return true;',
            '        $actual = hd($actual);',
            '        $seg++;',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Limpia las ventas "de prueba" de un dueño. Criterio:',
            ' * el comprador tiene email terminado en @test.local.',
            ' *',
            ' * Cada venta se cancela con motivo fijo "Limpieza de',
            ' * pruebas automáticas", lo que crea un Nodo Cancelación',
            ' * (auditoría). Ver también limpiar_cancelaciones_de_prueba,',
            ' * que barre esos nodos.',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' * @since 1.5piloto.80a',
            ' */',
            'function limpiar_ventas_de_prueba(string $nombre_dueno): array {',
            '    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor) {',
            '        return [',
            '            \'exito\' => true,',
            '            \'borrados\' => [],',
            '            \'cantidad_borrados\' => 0,',
            '            \'salteados\' => [],',
            '            \'cantidad_salteados\' => 0,',
            '        ];',
            '    }',
            '',
            '    // Recolectar ids de ventas de prueba (comprador con',
            '    // email @test.local) antes de modificar el árbol.',
            '    $ids_a_cancelar = [];',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 3000) {',
            '        $seg++;',
            '        $id_venta = (string)$actual->dato();',
            '        $nodo_comprador = $actual->adyacente(\'comprador\');',
            '        if ($nodo_comprador) {',
            '            $nodo_email = $nodo_comprador->adyacente(\'email\');',
            '            if ($nodo_email) {',
            '                $email = (string)$nodo_email->dato();',
            '                if (substr($email, -11) === \'@test.local\') {',
            '                    $ids_a_cancelar[] = $id_venta;',
            '                }',
            '            }',
            '        }',
            '        $actual = hd($actual);',
            '    }',
            '',
            '    $borrados = [];',
            '    $salteados = [];',
            '    foreach ($ids_a_cancelar as $id_venta) {',
            '        $res = cancelar_venta(',
            '            $id_venta,',
            '            \'Limpieza de pruebas automáticas\',',
            '            null,',
            '            $nombre_dueno',
            '        );',
            '        if (is_array($res) && !empty($res[\'exito\'])) {',
            '            $borrados[] = $id_venta;',
            '        } else {',
            '            $motivo = (is_array($res) && isset($res[\'error\'])) ? $res[\'error\'] : \'Error desconocido\';',
            '            $salteados[] = [\'nombre\' => $id_venta, \'motivo\' => $motivo];',
            '        }',
            '    }',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'salteados\' => $salteados,',
            '        \'cantidad_salteados\' => count($salteados),',
            '    ];',
            '}',
            '',
            '/**',
            ' * Limpia las cancelaciones "de prueba" de un dueño.',
            ' * Criterio: el motivo del nodo Cancelación contiene',
            ' * "prueba" o "test" (case-insensitive).',
            ' *',
            ' * Sirve para vaciar los nodos Cancelación que dejó',
            ' * limpiar_ventas_de_prueba (que usa el motivo fijo',
            ' * "Limpieza de pruebas automáticas").',
            ' *',
            ' * Devuelve el motivo de cada cancelación borrada,',
            ' * para auditoría.',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' * @since 1.5piloto.80a',
            ' */',
            'function limpiar_cancelaciones_de_prueba(string $nombre_dueno): array {',
            '    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '',
            '    $contenedor = $nodo_dueno->adyacente(\'cancelaciones\');',
            '    if (!$contenedor) {',
            '        return [',
            '            \'exito\' => true,',
            '            \'borrados\' => [],',
            '            \'cantidad_borrados\' => 0,',
            '            \'salteados\' => [],',
            '            \'cantidad_salteados\' => 0,',
            '        ];',
            '    }',
            '',
            '    // Recolectar los nodos a borrar (no modificar mientras',
            '    // se itera). Se guarda el motivo para reportarlo.',
            '    $a_borrar = [];',
            '    $actual = hmi($contenedor);',
            '    $seg = 0;',
            '    while ($actual && $seg < 3000) {',
            '        $seg++;',
            '        $motivo = $actual->adyacente(\'motivo\') ? $actual->adyacente(\'motivo\')->dato() : \'\';',
            '        $es_prueba = (stripos($motivo, \'prueba\') !== false || stripos($motivo, \'test\') !== false);',
            '        if ($es_prueba) {',
            '            $a_borrar[] = [',
            '                \'id\' => (string)$actual->dato(),',
            '                \'nodo\' => $actual,',
            '                \'motivo\' => $motivo,',
            '            ];',
            '        }',
            '        $actual = hd($actual);',
            '    }',
            '',
            '    $borrados = [];',
            '    $salteados = [];',
            '    foreach ($a_borrar as $item) {',
            '        $desenlazado = _desenlazar_venta_de_arbol($contenedor, $item[\'nodo\'], null);',
            '        if (!$desenlazado) {',
            '            $salteados[] = [\'nombre\' => $item[\'id\'], \'motivo\' => \'No se encontró en el árbol\'];',
            '            continue;',
            '        }',
            '        _destruir_campos_simples($item[\'nodo\']);',
            '        Nodo::eliminar($item[\'nodo\']);',
            '        $borrados[] = [\'id\' => $item[\'id\'], \'motivo\' => $item[\'motivo\']];',
            '    }',
            '',
            '    if (!empty($borrados)) {',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    }',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'salteados\' => $salteados,',
            '        \'cantidad_salteados\' => count($salteados),',
            '    ];',
            '}',
        ],
    ],

    // ============================================================
    // 4. Usuario.php: agregar limpiar_usuarios_de_prueba y
    //    limpiar_terminales_de_prueba al final.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: agregar limpiar_usuarios_de_prueba y limpiar_terminales_de_prueba',
        'buscar' => [
            '    // 3. Repuntar el enlace `dueno` del terminal al compartido.',
            '    $nodo_dueno_actual = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno_actual || $nodo_dueno_actual->id() !== $compartido->id()) {',
            '        $nodo_terminal->_adyacente_en($compartido, \'dueno\', true);',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '    // 3. Repuntar el enlace `dueno` del terminal al compartido.',
            '    $nodo_dueno_actual = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno_actual || $nodo_dueno_actual->id() !== $compartido->id()) {',
            '        $nodo_terminal->_adyacente_en($compartido, \'dueno\', true);',
            '    }',
            '}',
            '',
            '// ============================================================',
            '// Limpieza de datos de prueba (Fase 1 de §3 del plan actual).',
            '// ============================================================',
            '',
            '/**',
            ' * Elimina los usuarios "de prueba".',
            ' *',
            ' * Criterio D1: nombre_usuario contiene "prueba", "test" o',
            ' * "temp" (case-insensitive).',
            ' *',
            ' * Saltea (con motivo en `salteados`):',
            ' *   - el admin principal;',
            ' *   - usuarios con terminales asociadas (D1b);',
            ' *   - usuarios con contenido propio: ventas, pasajeros,',
            ' *     viajes, empresas, rendiciones, liquidaciones,',
            ' *     cancelaciones, dueños (si es soporte), o venta',
            ' *     en curso (D1c).',
            ' *',
            ' * No usa listar_usuarios: navega directo por la raíz',
            ' * `usuarios`, sin tocar credenciales (D9).',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @return array',
            ' * @since 1.5piloto.80a',
            ' */',
            'function limpiar_usuarios_de_prueba(): array {',
            '    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) {',
            '        return [\'exito\' => false, \'error\' => \'No existe la raíz usuarios\'];',
            '    }',
            '',
            '    $adyacentes = (array) $raiz->adyacentes();',
            '    $candidatos = [];',
            '    foreach ($adyacentes as $nombre_usuario => $nodo_usuario) {',
            '        $nombre_usuario = (string)$nombre_usuario;',
            '        $nombre_lower = strtolower($nombre_usuario);',
            '        $es_candidato = (',
            '            strpos($nombre_lower, \'prueba\') !== false',
            '            || strpos($nombre_lower, \'test\') !== false',
            '            || strpos($nombre_lower, \'temp\') !== false',
            '        );',
            '        if ($es_candidato) {',
            '            $candidatos[$nombre_usuario] = $nodo_usuario;',
            '        }',
            '    }',
            '',
            '    $borrados = [];',
            '    $salteados = [];',
            '    foreach ($candidatos as $nombre_usuario => $nodo_usuario) {',
            '        if ($nombre_usuario === ConfiguracionApli::NOMBRE_ADMIN) {',
            '            $salteados[] = [\'nombre\' => $nombre_usuario, \'motivo\' => \'Es el administrador\'];',
            '            continue;',
            '        }',
            '',
            '        // D1b: ¿tiene terminales asociadas?',
            '        $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '        if ($nodo_terminales && !empty($nodo_terminales->adyacentes())) {',
            '            $salteados[] = [\'nombre\' => $nombre_usuario, \'motivo\' => \'Tiene terminales asociadas\'];',
            '            continue;',
            '        }',
            '',
            '        // ¿Tiene una venta en curso colgada?',
            '        if ($nodo_usuario->adyacente(\'venta_actual\')) {',
            '            $salteados[] = [\'nombre\' => $nombre_usuario, \'motivo\' => \'Tiene una venta en curso\'];',
            '            continue;',
            '        }',
            '',
            '        // D1c: ¿tiene contenido asociado (ventas, pasajeros,',
            '        // viajes, empresas, rendiciones, liquidaciones,',
            '        // cancelaciones, o dueños asignados si es soporte)?',
            '        $contenedores = [',
            '            \'ventas\', \'pasajeros\', \'viajes\', \'empresas\',',
            '            \'rendiciones\', \'liquidaciones\', \'cancelaciones\',',
            '            \'duenos\',',
            '        ];',
            '        $tiene_contenido = false;',
            '        foreach ($contenedores as $c) {',
            '            $nodo_c = $nodo_usuario->adyacente($c);',
            '            if ($nodo_c && !empty($nodo_c->adyacentes())) {',
            '                $tiene_contenido = true;',
            '                break;',
            '            }',
            '        }',
            '        if ($tiene_contenido) {',
            '            $salteados[] = [\'nombre\' => $nombre_usuario, \'motivo\' => \'Tiene datos asociados\'];',
            '            continue;',
            '        }',
            '',
            '        $res = eliminar_usuario($nombre_usuario);',
            '        if (is_array($res) && !empty($res[\'exito\'])) {',
            '            $borrados[] = $nombre_usuario;',
            '        } else {',
            '            $motivo = (is_array($res) && isset($res[\'error\'])) ? $res[\'error\'] : \'Error desconocido\';',
            '            $salteados[] = [\'nombre\' => $nombre_usuario, \'motivo\' => $motivo];',
            '        }',
            '    }',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'salteados\' => $salteados,',
            '        \'cantidad_salteados\' => count($salteados),',
            '    ];',
            '}',
            '',
            '/**',
            ' * Elimina las terminales "de prueba" de un dueño.',
            ' *',
            ' * Criterio D3: nombre_usuario empieza con "termprueba".',
            ' *',
            ' * Reusa eliminar_terminal, que verifica permiso y llama',
            ' * a eliminar_usuario (que hace todo el cleanup del nodo',
            ' * usuario, credencial y sesiones).',
            ' *',
            ' * Disponible solo en modo pruebas.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array',
            ' * @since 1.5piloto.80a',
            ' */',
            'function limpiar_terminales_de_prueba(string $nombre_dueno): array {',
            '    if (!\\Iteradores\\Configuracion\\Entorno::es_pruebas()) {',
            '        return [\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\'];',
            '    }',
            '',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) {',
            '        return [\'exito\' => false, \'error\' => \'Dueño no encontrado\'];',
            '    }',
            '',
            '    $nodo_terminales = $nodo_dueno->adyacente(\'terminales\');',
            '    if (!$nodo_terminales) {',
            '        return [',
            '            \'exito\' => true,',
            '            \'borrados\' => [],',
            '            \'cantidad_borrados\' => 0,',
            '            \'salteados\' => [],',
            '            \'cantidad_salteados\' => 0,',
            '        ];',
            '    }',
            '',
            '    $prefijo = \'termprueba\';',
            '    $a_borrar = [];',
            '    $adyacentes = (array) $nodo_terminales->adyacentes();',
            '    foreach ($adyacentes as $nombre_terminal => $nodo_terminal) {',
            '        $nombre_terminal = (string)$nombre_terminal;',
            '        if (stripos($nombre_terminal, $prefijo) === 0) {',
            '            $a_borrar[] = $nombre_terminal;',
            '        }',
            '    }',
            '',
            '    $borrados = [];',
            '    $salteados = [];',
            '    foreach ($a_borrar as $nombre_terminal) {',
            '        $res = eliminar_terminal($nombre_terminal, $nombre_dueno);',
            '        if (is_array($res) && !empty($res[\'exito\'])) {',
            '            $borrados[] = $nombre_terminal;',
            '        } else {',
            '            $motivo = (is_array($res) && isset($res[\'error\'])) ? $res[\'error\'] : \'Error desconocido\';',
            '            $salteados[] = [\'nombre\' => $nombre_terminal, \'motivo\' => $motivo];',
            '        }',
            '    }',
            '',
            '    return [',
            '        \'exito\' => true,',
            '        \'borrados\' => $borrados,',
            '        \'cantidad_borrados\' => count($borrados),',
            '        \'salteados\' => $salteados,',
            '        \'cantidad_salteados\' => count($salteados),',
            '    ];',
            '}',
        ],
    ],

    // ============================================================
    // 5. Enrutador.php: 5 subacciones nuevas.
    // ============================================================

    // 5a. administrador/limpiar_usuarios_prueba
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: administrador/limpiar_usuarios_prueba',
        'buscar' => [
            '                case \'listar_duenos_disponibles\':',
            '                    if ($nivel_sol !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede listar todos los dueños\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos()]);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'listar_duenos_disponibles\':',
            '                    if ($nivel_sol !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede listar todos los dueños\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos()]);',
            '                    break;',
            '',
            '                case \'limpiar_usuarios_prueba\':',
            '                    // Solo admin + modo pruebas. Sin dueño',
            '                    // específico: aplica global.',
            '                    $resultado_lup = limpiar_usuarios_de_prueba();',
            '                    responder_json($resultado_lup);',
            '                    break;',
        ],
    ],

    // 5b. dueno/limpiar_terminales_prueba
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: dueno/limpiar_terminales_prueba',
        'buscar' => [
            '                    $sesiones = listar_sesiones_de_usuarios($nombres_terminales);',
            '                    responder_json([\'exito\' => true, \'sesiones\' => $sesiones]);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de dueño no válida\']);',
        ],
        'reemplazar' => [
            '                    $sesiones = listar_sesiones_de_usuarios($nombres_terminales);',
            '                    responder_json([\'exito\' => true, \'sesiones\' => $sesiones]);',
            '                    break;',
            '',
            '                case \'limpiar_terminales_prueba\':',
            '                    // Solo admin + modo pruebas (chequeo',
            '                    // interno de es_pruebas en la función).',
            '                    $nombre_sol_ltp = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $nodo_sol_ltp = ($nombre_sol_ltp !== \'\') ? Nodo::nodo_por_id(\'us_\' . $nombre_sol_ltp) : null;',
            '                    $nodo_nivel_ltp = $nodo_sol_ltp ? $nodo_sol_ltp->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_ltp = $nodo_nivel_ltp ? $nodo_nivel_ltp->dato() : \'\';',
            '                    if ($nivel_sol_ltp !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_ltp = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_ltp)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_ltp = limpiar_terminales_de_prueba($nombre_dueno_ltp);',
            '                    responder_json($resultado_ltp);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de dueño no válida\']);',
        ],
    ],

    // 5c. empresas/limpiar_prueba
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: empresas/limpiar_prueba',
        'buscar' => [
            '                case \'eliminar\':',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    if (empty($nombre_dueno) || empty($nombre_empresa)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño y empresa son obligatorios\']);',
            '                    }',
            '                    $resultado = eliminar_empresa($nombre_dueno, $nombre_empresa);',
            '                    responder_json($resultado);',
            '                    break;',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de empresas no válida\']);',
        ],
        'reemplazar' => [
            '                case \'eliminar\':',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $nombre_empresa = $post[\'nombre_empresa\'] ?? \'\';',
            '                    if (empty($nombre_dueno) || empty($nombre_empresa)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño y empresa son obligatorios\']);',
            '                    }',
            '                    $resultado = eliminar_empresa($nombre_dueno, $nombre_empresa);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'limpiar_prueba\':',
            '                    // Solo admin + modo pruebas (chequeo',
            '                    // interno de es_pruebas en la función).',
            '                    $nombre_sol_lep = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $nodo_sol_lep = ($nombre_sol_lep !== \'\') ? Nodo::nodo_por_id(\'us_\' . $nombre_sol_lep) : null;',
            '                    $nodo_nivel_lep = $nodo_sol_lep ? $nodo_sol_lep->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_lep = $nodo_nivel_lep ? $nodo_nivel_lep->dato() : \'\';',
            '                    if ($nivel_sol_lep !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_lep = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_lep)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_lep = limpiar_empresas_vehiculos_de_prueba($nombre_dueno_lep);',
            '                    responder_json($resultado_lep);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de empresas no válida\']);',
        ],
    ],

    // 5d. ventas/limpiar_prueba
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: ventas/limpiar_prueba',
        'buscar' => [
            '                case \'guardar\':',
            '                    $resultado = guardar_viaje_completo($post);',
            '                    responder_json($resultado);',
            '                    break;',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de ventas no válida\']);',
        ],
        'reemplazar' => [
            '                case \'guardar\':',
            '                    $resultado = guardar_viaje_completo($post);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'limpiar_prueba\':',
            '                    // Solo admin + modo pruebas (chequeo',
            '                    // interno de es_pruebas en la función).',
            '                    $nombre_sol_lvp = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $nodo_sol_lvp = ($nombre_sol_lvp !== \'\') ? Nodo::nodo_por_id(\'us_\' . $nombre_sol_lvp) : null;',
            '                    $nodo_nivel_lvp = $nodo_sol_lvp ? $nodo_sol_lvp->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_lvp = $nodo_nivel_lvp ? $nodo_nivel_lvp->dato() : \'\';',
            '                    if ($nivel_sol_lvp !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_lvp = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_lvp)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_lvp = limpiar_ventas_de_prueba($nombre_dueno_lvp);',
            '                    responder_json($resultado_lvp);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de ventas no válida\']);',
        ],
    ],

    // 5e. cancelaciones/limpiar_prueba
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: cancelaciones/limpiar_prueba',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    if (empty($id_cancelacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de cancelación no especificado\']);',
            '                    }',
            '                    $canc = obtener_cancelacion_por_id($id_cancelacion);',
            '                    if ($canc) {',
            '                        responder_json([\'exito\' => true, \'cancelacion\' => $canc]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Cancelación no encontrada\']);',
            '                    }',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de cancelaciones no válida\']);',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    if (empty($id_cancelacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de cancelación no especificado\']);',
            '                    }',
            '                    $canc = obtener_cancelacion_por_id($id_cancelacion);',
            '                    if ($canc) {',
            '                        responder_json([\'exito\' => true, \'cancelacion\' => $canc]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Cancelación no encontrada\']);',
            '                    }',
            '                    break;',
            '',
            '                case \'limpiar_prueba\':',
            '                    // Solo admin + modo pruebas (chequeo',
            '                    // interno de es_pruebas en la función).',
            '                    $nombre_sol_lcp = $post[\'nombre_solicitante\'] ?? \'\';',
            '                    $nodo_sol_lcp = ($nombre_sol_lcp !== \'\') ? Nodo::nodo_por_id(\'us_\' . $nombre_sol_lcp) : null;',
            '                    $nodo_nivel_lcp = $nodo_sol_lcp ? $nodo_sol_lcp->adyacente(\'nivel\') : null;',
            '                    $nivel_sol_lcp = $nodo_nivel_lcp ? $nodo_nivel_lcp->dato() : \'\';',
            '                    if ($nivel_sol_lcp !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede ejecutar esta acción\']);',
            '                    }',
            '                    $nombre_dueno_lcp = $post[\'nombre_dueno\'] ?? \'\';',
            '                    if (empty($nombre_dueno_lcp)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño no especificado\']);',
            '                    }',
            '                    $resultado_lcp = limpiar_cancelaciones_de_prueba($nombre_dueno_lcp);',
            '                    responder_json($resultado_lcp);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de cancelaciones no válida\']);',
        ],
    ],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

// --- Fase 1: aplicar reemplazos ---
$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

// --- Fase 2: bumps automáticos ---
$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    // 2a. @version de .php y .js
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
    }

    // 2b. ?v= en los HTMLs
    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

// --- Fase 3: escribir ---
$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";