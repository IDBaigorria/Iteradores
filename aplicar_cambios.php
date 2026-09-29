<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73: nuevo rol "soporte".
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
    // Usuario.php — bump de version
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.71',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73',
        ],
    ],

    // ==========================================================
    // Usuario.php — listar_duenos con filtro por rol
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: listar_duenos con filtro por rol',
        'buscar' => [
            'function listar_duenos(): array {',
            '    $todos = listar_usuarios();',
            '    $duenos = [];',
            '    foreach ($todos as $usuario) {',
            '        if ($usuario[\'nivel\'] === \'dueno\') {',
            '            $duenos[] = [',
            '                \'nombre_usuario\' => $usuario[\'nombre_usuario\'],',
            '                \'nombre_real\' => $usuario[\'nombre_real\'],',
            '            ];',
            '        }',
            '    }',
            '    return $duenos;',
            '}',
        ],
        'reemplazar' => [
            'function listar_duenos(string $nombre_solicitante = \'\'): array {',
            '    $todos = listar_usuarios();',
            '    $duenos = [];',
            '    foreach ($todos as $usuario) {',
            '        if ($usuario[\'nivel\'] === \'dueno\') {',
            '            $duenos[] = [',
            '                \'nombre_usuario\' => $usuario[\'nombre_usuario\'],',
            '                \'nombre_real\' => $usuario[\'nombre_real\'],',
            '            ];',
            '        }',
            '    }',
            '',
            '    // Si el solicitante es soporte, filtrar a sus dueños asignados.',
            '    if ($nombre_solicitante !== \'\' && $nombre_solicitante !== Conf::NOMBRE_ADMIN) {',
            '        $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '        if ($raiz) {',
            '            $nodo_sol = $raiz->adyacente($nombre_solicitante);',
            '            if ($nodo_sol) {',
            '                $nodo_nivel_sol = $nodo_sol->adyacente(\'nivel\');',
            '                $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
            '                if ($nivel_sol === \'soporte\') {',
            '                    $nodo_duenos_sol = $nodo_sol->adyacente(\'duenos\');',
            '                    $permitidos = [];',
            '                    if ($nodo_duenos_sol) {',
            '                        foreach ($nodo_duenos_sol->adyacentes() as $nombre_d => $nodo_d) {',
            '                            $permitidos[(string)$nombre_d] = true;',
            '                        }',
            '                    }',
            '                    $duenos = array_values(array_filter($duenos, function($d) use ($permitidos) {',
            '                        return isset($permitidos[(string)$d[\'nombre_usuario\']]);',
            '                    }));',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    return $duenos;',
            '}',
        ],
    ],

    // ==========================================================
    // Usuario.php — nuevas funciones de soporte (antes de obtener_saldos_dueno)
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: funciones de soporte',
        'buscar' => [
            '/**',
            ' * Devuelve los saldos actuales de un dueño (efectivo, banco, total).',
            ' *',
            ' * Se usa tanto en la pestaña Rendiciones como en Liquidaciones para',
            ' * mostrar el saldo disponible antes de mover dinero.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array{efectivo: string, banco: string, total: string}',
            ' */',
        ],
        'reemplazar' => [
            '/**',
            ' * Lista todos los usuarios con nivel "soporte".',
            ' *',
            ' * Cada uno con sus dueños asignados.',
            ' *',
            ' * @return array Lista de soportes.',
            ' */',
            'function listar_soportes(): array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [];',
            '',
            '    $soportes = [];',
            '    foreach ($raiz->adyacentes() as $nombre_usuario => $nodo_usuario) {',
            '        $nodo_nivel = $nodo_usuario->adyacente(\'nivel\');',
            '        $nivel = $nodo_nivel ? $nodo_nivel->dato() : \'\';',
            '        if ($nivel !== \'soporte\') continue;',
            '',
            '        $nodo_nombre_real = $nodo_usuario->adyacente(\'nombre_real\');',
            '        $nodo_email = $nodo_usuario->adyacente(\'email\');',
            '',
            '        $duenos = [];',
            '        $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '        if ($nodo_duenos) {',
            '            foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_d) {',
            '                $duenos[] = (string)$nombre_dueno;',
            '            }',
            '        }',
            '',
            '        $soportes[] = [',
            '            \'nombre_usuario\' => (string)$nombre_usuario,',
            '            \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : \'\',',
            '            \'email\' => $nodo_email ? $nodo_email->dato() : \'\',',
            '            \'nivel\' => \'soporte\',',
            '            \'duenos\' => $duenos,',
            '        ];',
            '    }',
            '    return $soportes;',
            '}',
            '',
            '/**',
            ' * Lista los dueños asignados a un soporte.',
            ' *',
            ' * @param string $nombre_soporte Nombre del soporte.',
            ' * @return array Lista de dueños con nombre_usuario y nombre_real.',
            ' */',
            'function listar_duenos_de_soporte(string $nombre_soporte): array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [];',
            '    $nodo_soporte = $raiz->adyacente($nombre_soporte);',
            '    if (!$nodo_soporte) return [];',
            '',
            '    $nodo_nivel = $nodo_soporte->adyacente(\'nivel\');',
            '    $nivel = $nodo_nivel ? $nodo_nivel->dato() : \'\';',
            '    if ($nivel !== \'soporte\') return [];',
            '',
            '    $nodo_duenos = $nodo_soporte->adyacente(\'duenos\');',
            '    if (!$nodo_duenos) return [];',
            '',
            '    $resultado = [];',
            '    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nodo_nombre_real = $nodo_dueno->adyacente(\'nombre_real\');',
            '        $resultado[] = [',
            '            \'nombre_usuario\' => (string)$nombre_dueno,',
            '            \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : \'\',',
            '        ];',
            '    }',
            '    return $resultado;',
            '}',
            '',
            '/**',
            ' * Lista los usuarios visibles para un soporte: cada dueño asignado',
            ' * más sus terminales.',
            ' *',
            ' * @param string $nombre_soporte Nombre del soporte.',
            ' * @return array Lista de usuarios.',
            ' */',
            'function listar_usuarios_de_soporte(string $nombre_soporte): array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [];',
            '    $nodo_soporte = $raiz->adyacente($nombre_soporte);',
            '    if (!$nodo_soporte) return [];',
            '',
            '    $nodo_duenos = $nodo_soporte->adyacente(\'duenos\');',
            '    if (!$nodo_duenos) return [];',
            '',
            '    // Cargar el mapa de usuarios con código (para codigo_asignado).',
            '    $con_codigo = en_grafo_credenciales(function() {',
            '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_cred) return [];',
            '        $mapa = [];',
            '        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {',
            '            $mapa[(string)$nombre] = $nodo->adyacente(\'codigo_hash\') ? true : false;',
            '        }',
            '        return $mapa;',
            '    });',
            '',
            '    $usuarios = [];',
            '    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $usuarios[] = _formatear_usuario_para_admin((string)$nombre_dueno, $nodo_dueno, $con_codigo);',
            '',
            '        $nodo_terminales = $nodo_dueno->adyacente(\'terminales\');',
            '        if ($nodo_terminales) {',
            '            foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {',
            '                $usuarios[] = _formatear_usuario_para_admin((string)$nombre_terminal, $nodo_terminal, $con_codigo);',
            '            }',
            '        }',
            '    }',
            '    return $usuarios;',
            '}',
            '',
            '/**',
            ' * Formatea un nodo usuario para el listado del panel admin.',
            ' *',
            ' * @param string $nombre_usuario Nombre de usuario.',
            ' * @param Nodo $nodo_usuario Nodo del usuario.',
            ' * @param array $con_codigo Mapa nombre → tiene_codigo.',
            ' * @return array Datos formateados.',
            ' */',
            'function _formatear_usuario_para_admin(string $nombre_usuario, Nodo $nodo_usuario, array $con_codigo): array {',
            '    $nodo_efectivo = $nodo_usuario->adyacente(\'efectivo\');',
            '    $nodo_banco = $nodo_usuario->adyacente(\'banco\');',
            '    $nodo_nivel = $nodo_usuario->adyacente(\'nivel\');',
            '    $nodo_nombre_real = $nodo_usuario->adyacente(\'nombre_real\');',
            '    $nodo_email = $nodo_usuario->adyacente(\'email\');',
            '    $nodo_pasajes = $nodo_usuario->adyacente(\'pasajes\');',
            '',
            '    $banco_nombre = \'\';',
            '    $banco_cuenta = \'\';',
            '    $monto_banco = \'0\';',
            '    if ($nodo_banco) {',
            '        $monto_banco = $nodo_banco->dato();',
            '        $nombre = $nodo_banco->adyacente(\'nombre\');',
            '        $cuenta = $nodo_banco->adyacente(\'cuenta\');',
            '        $banco_nombre = $nombre ? $nombre->dato() : \'\';',
            '        $banco_cuenta = $cuenta ? $cuenta->dato() : \'\';',
            '    }',
            '',
            '    $usuario = [',
            '        \'nombre_usuario\' => $nombre_usuario,',
            '        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : \'\',',
            '        \'email\' => $nodo_email ? $nodo_email->dato() : \'\',',
            '        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '        \'efectivo\' => $nodo_efectivo ? $nodo_efectivo->dato() : \'0\',',
            '        \'bancarizado\' => $monto_banco,',
            '        \'codigo_asignado\' => !empty($con_codigo[$nombre_usuario]),',
            '        \'pasajes\' => $nodo_pasajes ? $nodo_pasajes->dato() : \'0\',',
            '        \'banco\' => [',
            '            \'nombre\' => $banco_nombre,',
            '            \'cuenta\' => $banco_cuenta,',
            '        ],',
            '    ];',
            '',
            '    $nivel = $usuario[\'nivel\'];',
            '    if ($nivel === \'terminal\') {',
            '        $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '    }',
            '    if ($nivel === \'dueno\') {',
            '        $terminales = [];',
            '        $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '        if ($nodo_terminales) {',
            '            foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {',
            '                $terminales[] = (string)$nombre_terminal;',
            '            }',
            '        }',
            '        $usuario[\'terminales\'] = $terminales;',
            '    }',
            '    if ($nivel === \'soporte\') {',
            '        $duenos = [];',
            '        $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '        if ($nodo_duenos) {',
            '            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                $duenos[] = (string)$nombre_d;',
            '            }',
            '        }',
            '        $usuario[\'duenos\'] = $duenos;',
            '    }',
            '',
            '    return $usuario;',
            '}',
            '',
            '/**',
            ' * Verifica si un usuario solicitante tiene permiso sobre un dueño.',
            ' *',
            ' * Reglas:',
            ' * - Admin: permiso sobre todos.',
            ' * - Soporte: permiso solo sobre los dueños asignados.',
            ' * - Dueño: permiso sobre sí mismo.',
            ' * - Terminal: permiso sobre su propio dueño.',
            ' *',
            ' * @param string $nombre_solicitante Nombre del usuario que solicita.',
            ' * @param string $nombre_dueno Nombre del dueño sobre el que se quiere operar.',
            ' * @return bool',
            ' */',
            'function _verificar_permiso_dueno(string $nombre_solicitante, string $nombre_dueno): bool {',
            '    if ($nombre_solicitante === \'\') return true;',
            '    if ($nombre_solicitante === $nombre_dueno) return true;',
            '    if ($nombre_solicitante === Conf::NOMBRE_ADMIN) return true;',
            '',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return false;',
            '    $nodo_sol = $raiz->adyacente($nombre_solicitante);',
            '    if (!$nodo_sol) return false;',
            '',
            '    $nodo_nivel = $nodo_sol->adyacente(\'nivel\');',
            '    $nivel = $nodo_nivel ? $nodo_nivel->dato() : \'\';',
            '',
            '    if ($nivel === \'soporte\') {',
            '        $nodo_duenos = $nodo_sol->adyacente(\'duenos\');',
            '        if (!$nodo_duenos) return false;',
            '        return $nodo_duenos->adyacente($nombre_dueno) !== null;',
            '    }',
            '',
            '    if ($nivel === \'terminal\') {',
            '        $nodo_dueno = $nodo_sol->adyacente(\'dueno\');',
            '        return $nodo_dueno && $nodo_dueno->dato() === $nombre_dueno;',
            '    }',
            '',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Asigna una lista de dueños a un soporte, manteniendo consistencia',
            ' * bidireccional (soporte→dueno y dueno→soporte).',
            ' *',
            ' * @param Nodo $nodo_soporte Nodo del soporte.',
            ' * @param array $nuevos_duenos Lista de nombres de dueños.',
            ' * @return void',
            ' */',
            'function _asignar_duenos_a_soporte(Nodo $nodo_soporte, array $nuevos_duenos): void {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return;',
            '',
            '    // Asegurar que exista el contenedor.',
            '    $nodo_duenos = $nodo_soporte->adyacente(\'duenos\');',
            '    if (!$nodo_duenos) {',
            '        $nodo_duenos = Nodo::crear_con_dato(\'\');',
            '        $nodo_soporte->_adyacente_en($nodo_duenos, \'duenos\');',
            '    }',
            '',
            '    // Normalizar la lista de nuevos (solo strings).',
            '    $lista_nuevos = [];',
            '    foreach ($nuevos_duenos as $n) {',
            '        $n = (string)$n;',
            '        if ($n !== \'\') $lista_nuevos[$n] = true;',
            '    }',
            '',
            '    // Quitar los que ya no van.',
            '    $actuales = [];',
            '    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $actuales[(string)$nombre_dueno] = $nodo_dueno;',
            '    }',
            '    foreach ($actuales as $nombre_dueno => $nodo_dueno) {',
            '        if (!isset($lista_nuevos[$nombre_dueno])) {',
            '            $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '            $nodo_duenos->eliminar_adyacente($nombre_dueno);',
            '        }',
            '    }',
            '',
            '    // Agregar los nuevos.',
            '    foreach ($lista_nuevos as $nombre_dueno => $_) {',
            '        if (isset($actuales[$nombre_dueno])) continue;',
            '        $nodo_dueno = $raiz->adyacente($nombre_dueno);',
            '        if (!$nodo_dueno) continue;',
            '        $nodo_nivel_d = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nodo_nivel_d || $nodo_nivel_d->dato() !== \'dueno\') continue;',
            '',
            '        $nodo_duenos->_adyacente_en($nodo_dueno, $nombre_dueno);',
            '        $nodo_dueno->eliminar_adyacente(\'soporte\');',
            '        $nodo_dueno->_adyacente_en($nodo_soporte, \'soporte\');',
            '    }',
            '}',
            '',
            '/**',
            ' * Devuelve los saldos actuales de un dueño (efectivo, banco, total).',
            ' *',
            ' * Se usa tanto en la pestaña Rendiciones como en Liquidaciones para',
            ' * mostrar el saldo disponible antes de mover dinero.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @return array{efectivo: string, banco: string, total: string}',
            ' */',
        ],
    ],

    // ==========================================================
    // Usuario.php — agregar_usuario: manejar nivel soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: agregar_usuario con soporte',
        'buscar' => [
            '    $dueno = trim($datos[\'dueno\'] ?? \'\');',
            '    $email = trim($datos[\'email\'] ?? \'\');',
            '',
            '    if (empty($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre de usuario es obligatorio\'];',
            '    }',
        ],
        'reemplazar' => [
            '    $dueno = trim($datos[\'dueno\'] ?? \'\');',
            '    $email = trim($datos[\'email\'] ?? \'\');',
            '',
            '    // Dueños asignados, solo para nivel soporte. Vienen como JSON string.',
            '    $duenos_asignados = [];',
            '    if (isset($datos[\'duenos_asignados\']) && $datos[\'duenos_asignados\'] !== \'\') {',
            '        $decodificado = json_decode($datos[\'duenos_asignados\'], true);',
            '        if (is_array($decodificado)) $duenos_asignados = $decodificado;',
            '    }',
            '',
            '    if (empty($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre de usuario es obligatorio\'];',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: agregar_usuario escribe nivel soporte',
        'buscar' => [
            '    if ($nivel === \'dueno\') {',
            '        // Crear cuenta de efectivo. El banco se crea solo si el admin',
            '        // lo cargó al momento del alta; si no, se crea más adelante',
            '        // cuando haga falta (por ejemplo, al cerrar una rendición).',
            '        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'efectivo\');',
            '        if ($banco_nombre !== \'\' && $banco_cuenta !== \'\') {',
            '            $nodo_banco = Nodo::crear_con_dato(\'0\');',
            '            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), \'nombre\');',
            '            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), \'cuenta\');',
            '            $nodo_usuario->_adyacente_en($nodo_banco, \'banco\');',
            '        }',
            '    }',
            '',
            '    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);',
            '    guardar_ambos(Conf::NOMBRE_APP);',
        ],
        'reemplazar' => [
            '    if ($nivel === \'dueno\') {',
            '        // Crear cuenta de efectivo. El banco se crea solo si el admin',
            '        // lo cargó al momento del alta; si no, se crea más adelante',
            '        // cuando haga falta (por ejemplo, al cerrar una rendición).',
            '        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'efectivo\');',
            '        if ($banco_nombre !== \'\' && $banco_cuenta !== \'\') {',
            '            $nodo_banco = Nodo::crear_con_dato(\'0\');',
            '            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), \'nombre\');',
            '            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), \'cuenta\');',
            '            $nodo_usuario->_adyacente_en($nodo_banco, \'banco\');',
            '        }',
            '    }',
            '',
            '    if ($nivel === \'soporte\') {',
            '        // Crear contenedor vacío; los enlaces se llenan después.',
            '        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(\'\'), \'duenos\');',
            '    }',
            '',
            '    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);',
            '',
            '    // Si es soporte, asignar dueños.',
            '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
            '',
            '    guardar_ambos(Conf::NOMBRE_APP);',
        ],
    ],

    // ==========================================================
    // Usuario.php — actualizar_usuario: manejar soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: actualizar_usuario con soporte',
        'buscar' => [
            '    $nivel = trim($datos[\'nivel\'] ?? $nivel_actual);',
            '    $nombre_real = trim($datos[\'nombre_real\'] ?? \'\');',
            '    $email = trim($datos[\'email\'] ?? \'\');',
            '    $codigo_acceso = trim($datos[\'codigo_acceso\'] ?? \'\');',
            '    $contrasena = trim($datos[\'contrasena\'] ?? \'\');',
        ],
        'reemplazar' => [
            '    $nivel = trim($datos[\'nivel\'] ?? $nivel_actual);',
            '    $nombre_real = trim($datos[\'nombre_real\'] ?? \'\');',
            '    $email = trim($datos[\'email\'] ?? \'\');',
            '    $codigo_acceso = trim($datos[\'codigo_acceso\'] ?? \'\');',
            '    $contrasena = trim($datos[\'contrasena\'] ?? \'\');',
            '',
            '    // El nivel no se puede cambiar entre "soporte" y otros. El rol',
            '    // se fija en la creación.',
            '    if (($nivel_actual === \'soporte\') !== ($nivel === \'soporte\')) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede cambiar el nivel desde o hacia soporte\'];',
            '    }',
            '',
            '    // Dueños asignados, solo para nivel soporte.',
            '    $duenos_asignados = null;',
            '    if ($nivel === \'soporte\' && isset($datos[\'duenos_asignados\'])) {',
            '        $decodificado = json_decode($datos[\'duenos_asignados\'], true);',
            '        $duenos_asignados = is_array($decodificado) ? $decodificado : [];',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: actualizar_usuario asigna duenos de soporte',
        'buscar' => [
            '    // Asegurar que el dueño tenga cuenta de efectivo.',
            '    if ($nivel === \'dueno\' && !$nodo_usuario->adyacente(\'efectivo\')) {',
            '        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'efectivo\');',
            '    }',
        ],
        'reemplazar' => [
            '    // Asegurar que el dueño tenga cuenta de efectivo.',
            '    if ($nivel === \'dueno\' && !$nodo_usuario->adyacente(\'efectivo\')) {',
            '        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'efectivo\');',
            '    }',
            '',
            '    // Si es soporte y se pasaron dueños asignados, actualizarlos.',
            '    if ($nivel === \'soporte\' && $duenos_asignados !== null) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
        ],
    ],

    // ==========================================================
    // Usuario.php — eliminar_usuario: limpiar enlaces soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: eliminar_usuario limpia enlaces de soporte',
        'buscar' => [
            '    $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '    if ($nodo_dueno) {',
            '        $dueno_nombre = $nodo_dueno->dato();',
            '        $nodo_dueno_real = $raiz->adyacente($dueno_nombre);',
            '        if ($nodo_dueno_real) {',
            '            $nodo_contenedor_terminales = $nodo_dueno_real->adyacente(\'terminales\');',
            '            if ($nodo_contenedor_terminales) {',
            '                $nodo_contenedor_terminales->eliminar_adyacente($nombre_usuario);',
            '            }',
            '        }',
            '    }',
            '',
            '    $raiz->eliminar_adyacente($nombre_usuario);',
        ],
        'reemplazar' => [
            '    $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '    if ($nodo_dueno) {',
            '        $dueno_nombre = $nodo_dueno->dato();',
            '        $nodo_dueno_real = $raiz->adyacente($dueno_nombre);',
            '        if ($nodo_dueno_real) {',
            '            $nodo_contenedor_terminales = $nodo_dueno_real->adyacente(\'terminales\');',
            '            if ($nodo_contenedor_terminales) {',
            '                $nodo_contenedor_terminales->eliminar_adyacente($nombre_usuario);',
            '            }',
            '        }',
            '    }',
            '',
            '    // Si es soporte, limpiar los enlaces `soporte` en sus dueños.',
            '    $nodo_nivel_actual = $nodo_usuario->adyacente(\'nivel\');',
            '    $nivel_actual_del = $nodo_nivel_actual ? $nodo_nivel_actual->dato() : \'\';',
            '    if ($nivel_actual_del === \'soporte\') {',
            '        $nodo_duenos_del = $nodo_usuario->adyacente(\'duenos\');',
            '        if ($nodo_duenos_del) {',
            '            foreach ($nodo_duenos_del->adyacentes() as $nombre_d => $nodo_d) {',
            '                $nodo_d->eliminar_adyacente(\'soporte\');',
            '            }',
            '        }',
            '    }',
            '',
            '    // Si es dueño, limpiar la referencia en su soporte.',
            '    if ($nivel_actual_del === \'dueno\') {',
            '        $nodo_soporte_del = $nodo_usuario->adyacente(\'soporte\');',
            '        if ($nodo_soporte_del) {',
            '            $nombre_soporte_del = $nodo_soporte_del->dato();',
            '            $nodo_soporte_real = $raiz->adyacente($nombre_soporte_del);',
            '            if ($nodo_soporte_real) {',
            '                $nodo_duenos_sop = $nodo_soporte_real->adyacente(\'duenos\');',
            '                if ($nodo_duenos_sop) {',
            '                    $nodo_duenos_sop->eliminar_adyacente($nombre_usuario);',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    $raiz->eliminar_adyacente($nombre_usuario);',
        ],
    ],

    // ==========================================================
    // Autenticacion.php — bump y devolver duenos
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.71',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion.php: devolver duenos si es soporte',
        'buscar' => [
            '    $nodo_nivel = $nodo_app->adyacente(\'nivel\');',
            '    $nodo_nombre_real = $nodo_app->adyacente(\'nombre_real\');',
            '',
            '    $usuario = [',
            '        \'nombre_usuario\' => $nombre_usuario,',
            '        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,',
            '        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '    ];',
            '',
            '    $token = crear_sesion($nombre_usuario);',
            '    $usuario[\'token_sesion\'] = $token;',
            '',
            '    return $usuario;',
            '}',
        ],
        'reemplazar' => [
            '    $nodo_nivel = $nodo_app->adyacente(\'nivel\');',
            '    $nodo_nombre_real = $nodo_app->adyacente(\'nombre_real\');',
            '',
            '    $usuario = [',
            '        \'nombre_usuario\' => $nombre_usuario,',
            '        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,',
            '        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '    ];',
            '',
            '    if ($usuario[\'nivel\'] === \'soporte\') {',
            '        $duenos = [];',
            '        $nodo_duenos = $nodo_app->adyacente(\'duenos\');',
            '        if ($nodo_duenos) {',
            '            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                $duenos[] = (string)$nombre_d;',
            '            }',
            '        }',
            '        $usuario[\'duenos\'] = $duenos;',
            '    }',
            '',
            '    $token = crear_sesion($nombre_usuario);',
            '    $usuario[\'token_sesion\'] = $token;',
            '',
            '    return $usuario;',
            '}',
        ],
    ],

    // ==========================================================
    // Enrutador.php — bump y chequeo global
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: chequeo global de permiso sobre dueno',
        'buscar' => [
            'function enrutar_peticion_post(string $accion, array $post): void {',
            '    $partes = explode(\'/\', $accion);',
            '    $modulo = $partes[0] ?? \'\';',
            '    $subaccion = $partes[1] ?? \'\';',
            '',
            '    switch ($modulo) {',
        ],
        'reemplazar' => [
            'function enrutar_peticion_post(string $accion, array $post): void {',
            '    $partes = explode(\'/\', $accion);',
            '    $modulo = $partes[0] ?? \'\';',
            '    $subaccion = $partes[1] ?? \'\';',
            '',
            '    // Chequeo global: si viene nombre_solicitante y nombre_dueno,',
            '    // verificar que el solicitante tenga permiso sobre ese dueño.',
            '    $nombre_solicitante = $post[\'nombre_solicitante\'] ?? \'\';',
            '    $nombre_dueno_post = $post[\'nombre_dueno\'] ?? \'\';',
            '    if ($nombre_solicitante !== \'\' && $nombre_dueno_post !== \'\') {',
            '        if (!_verificar_permiso_dueno($nombre_solicitante, $nombre_dueno_post)) {',
            '            responder_json([\'exito\' => false, \'error\' => \'Permiso denegado sobre el dueño solicitado\']);',
            '        }',
            '    }',
            '',
            '    switch ($modulo) {',
        ],
    ],

    // ==========================================================
    // Enrutador.php — modulo administrador con chequeo
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: chequeo de nivel en administrador',
        'buscar' => [
            '        case \'administrador\':',
            '            switch ($subaccion) {',
            '                case \'verificar\':',
            '                    $codigo = $post[\'codigo\'] ?? \'\';',
            '                    if (verificar_codigo_admin($codigo)) {',
            '                        responder_json([\'exito\' => true]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Código incorrecto\']);',
            '                    }',
            '                    break;',
            '',
            '                case \'listar_usuarios\':',
            '                    responder_json([\'exito\' => true, \'usuarios\' => listar_usuarios()]);',
            '                    break;',
            '',
            '                case \'listar_duenos\':',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos()]);',
            '                    break;',
        ],
        'reemplazar' => [
            '        case \'administrador\':',
            '            // Determinar el nivel del solicitante (excepto verificar).',
            '            $nombre_sol_admin = $post[\'nombre_solicitante\'] ?? \'\';',
            '            $raiz_admin = Nodo::nodo_por_id(\'usuarios\');',
            '            $nodo_sol_admin = ($raiz_admin && $nombre_sol_admin !== \'\') ? $raiz_admin->adyacente($nombre_sol_admin) : null;',
            '            $nodo_nivel_sol = $nodo_sol_admin ? $nodo_sol_admin->adyacente(\'nivel\') : null;',
            '            $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : \'\';',
            '            if ($subaccion !== \'verificar\' && !in_array($nivel_sol, [\'admin\', \'soporte\'], true)) {',
            '                responder_json([\'exito\' => false, \'error\' => \'Permiso denegado\']);',
            '            }',
            '',
            '            switch ($subaccion) {',
            '                case \'verificar\':',
            '                    $codigo = $post[\'codigo\'] ?? \'\';',
            '                    if (verificar_codigo_admin($codigo)) {',
            '                        responder_json([\'exito\' => true]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Código incorrecto\']);',
            '                    }',
            '                    break;',
            '',
            '                case \'listar_usuarios\':',
            '                    if ($nivel_sol === \'soporte\') {',
            '                        responder_json([\'exito\' => true, \'usuarios\' => listar_usuarios_de_soporte($nombre_sol_admin)]);',
            '                    } else {',
            '                        responder_json([\'exito\' => true, \'usuarios\' => listar_usuarios()]);',
            '                    }',
            '                    break;',
            '',
            '                case \'listar_duenos\':',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos($nombre_sol_admin)]);',
            '                    break;',
        ],
    ],

    // ==========================================================
    // Enrutador.php — nuevas subacciones de soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: subacciones de soporte',
        'buscar' => [
            '                case \'eliminar_usuario\':',
            '                    $nombre_usuario = $post[\'nombre_usuario\'] ?? \'\';',
            '                    $resultado = eliminar_usuario($nombre_usuario);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de administrador no válida\']);',
        ],
        'reemplazar' => [
            '                case \'eliminar_usuario\':',
            '                    $nombre_usuario = $post[\'nombre_usuario\'] ?? \'\';',
            '                    $resultado = eliminar_usuario($nombre_usuario);',
            '                    responder_json($resultado);',
            '                    break;',
            '',
            '                case \'listar_soportes\':',
            '                    if ($nivel_sol !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede listar soportes\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'soportes\' => listar_soportes()]);',
            '                    break;',
            '',
            '                case \'listar_duenos_de_soporte\':',
            '                    if ($nivel_sol !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede listar los dueños de un soporte\']);',
            '                    }',
            '                    $nombre_soporte = $post[\'nombre_soporte\'] ?? \'\';',
            '                    if (empty($nombre_soporte)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Soporte no especificado\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos_de_soporte($nombre_soporte)]);',
            '                    break;',
            '',
            '                case \'listar_duenos_disponibles\':',
            '                    if ($nivel_sol !== \'admin\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Solo el administrador puede listar todos los dueños\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'duenos\' => listar_duenos()]);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de administrador no válida\']);',
        ],
    ],

    // ==========================================================
    // Enrutador.php — sesiones/validar devuelve duenos si es soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: validar sesion devuelve duenos de soporte',
        'buscar' => [
            '                    // Si es terminal, incluir dueño',
            '                    if ($usuario[\'nivel\'] === \'terminal\') {',
            '                        $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '                        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '                    }',
            '                    responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
        ],
        'reemplazar' => [
            '                    // Si es terminal, incluir dueño',
            '                    if ($usuario[\'nivel\'] === \'terminal\') {',
            '                        $nodo_dueno = $nodo_usuario->adyacente(\'dueno\');',
            '                        $usuario[\'dueno\'] = $nodo_dueno ? $nodo_dueno->dato() : \'\';',
            '                    }',
            '                    // Si es soporte, incluir la lista de dueños asignados.',
            '                    if ($usuario[\'nivel\'] === \'soporte\') {',
            '                        $duenos = [];',
            '                        $nodo_duenos = $nodo_usuario->adyacente(\'duenos\');',
            '                        if ($nodo_duenos) {',
            '                            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {',
            '                                $duenos[] = (string)$nombre_d;',
            '                            }',
            '                        }',
            '                        $usuario[\'duenos\'] = $duenos;',
            '                    }',
            '                    responder_json([\'exito\' => true, \'usuario\' => $usuario]);',
        ],
    ],

    // ==========================================================
    // aplicacion.js — interceptor de fetch y pestañas de soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.68',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: interceptor de fetch con nombre_solicitante',
        'buscar' => [
            '// Utilidades',
            'const $ = s => document.querySelector(s);',
            'const $$ = s => document.querySelectorAll(s);',
            '',
            'const DEBUG_FETCH = true;',
        ],
        'reemplazar' => [
            '// Utilidades',
            'const $ = s => document.querySelector(s);',
            'const $$ = s => document.querySelectorAll(s);',
            '',
            '// Interceptor de fetch: agrega nombre_solicitante a toda petición POST',
            '// a index.php cuando hay usuario logueado.',
            'const _fetch_original = window.fetch.bind(window);',
            'window.fetch = function(url, opciones) {',
            '    try {',
            '        if (typeof url === \'string\' && url.indexOf(\'index.php\') !== -1 && opciones && opciones.method === \'POST\') {',
            '            if (typeof usuario_actual !== \'undefined\' && usuario_actual && usuario_actual.nombre_usuario) {',
            '                if (opciones.body instanceof URLSearchParams) {',
            '                    opciones.body.set(\'nombre_solicitante\', usuario_actual.nombre_usuario);',
            '                } else if (opciones.body instanceof FormData) {',
            '                    opciones.body.set(\'nombre_solicitante\', usuario_actual.nombre_usuario);',
            '                }',
            '            }',
            '        }',
            '    } catch (e) { console.error(\'error agregando nombre_solicitante\', e); }',
            '    return _fetch_original(url, opciones);',
            '};',
            '',
            'const DEBUG_FETCH = true;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: pestanas para soporte',
        'buscar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
        'reemplazar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        soporte: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
    ],

    // ==========================================================
    // admin.js — bump y logica condicional por nivel
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.68',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: detectar nivel en cargar_datos_admin',
        'buscar' => [
            'async function cargar_datos_admin() {',
            '    // Cargar usuarios',
            '    let respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })',
            '    });',
        ],
        'reemplazar' => [
            'async function cargar_datos_admin() {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '',
            '    // Si es soporte, cargar el selector de dueños.',
            '    if (es_soporte) {',
            '        await _cargar_selector_dueno_admin();',
            '    } else {',
            '        // Si es admin, ocultar el selector.',
            '        const panel_sel = document.getElementById(\'panel_selector_dueno_admin\');',
            '        if (panel_sel) panel_sel.style.display = \'none\';',
            '    }',
            '',
            '    // Cargar usuarios',
            '    let respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })',
            '    });',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: agregar helpers de soporte al final',
        'buscar' => [
            '$("#boton_guardar_usuario").addEventListener("click", async () => {',
        ],
        'reemplazar' => [
            '/**',
            ' * Carga el selector de dueños en la pestaña admin para un soporte.',
            ' */',
            'async function _cargar_selector_dueno_admin() {',
            '    const panel = document.getElementById(\'panel_selector_dueno_admin\');',
            '    const select = document.getElementById(\'selector_dueno_admin\');',
            '    if (!panel || !select) return;',
            '',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '    });',
            '    const datos = await respuesta.json();',
            '',
            '    select.innerHTML = \'\';',
            '    if (datos.exito && datos.duenos.length > 0) {',
            '        datos.duenos.forEach(d => {',
            '            const opt = document.createElement(\'option\');',
            '            opt.value = d.nombre_usuario;',
            '            opt.textContent = d.nombre_real ? `${d.nombre_real} (${d.nombre_usuario})` : d.nombre_usuario;',
            '            select.appendChild(opt);',
            '        });',
            '        panel.style.display = \'\';',
            '    } else {',
            '        select.innerHTML = \'<option value="">Sin dueños asignados</option>\';',
            '        panel.style.display = \'\';',
            '    }',
            '',
            '    // Listener único (usando onclick para no acumular).',
            '    select.onchange = async () => {',
            '        await _cargar_usuarios_filtrados_por_dueno(select.value);',
            '    };',
            '    // Cargar la primera vez.',
            '    if (select.value) {',
            '        await _cargar_usuarios_filtrados_por_dueno(select.value);',
            '    }',
            '}',
            '',
            '/**',
            ' * Carga usuarios del dueño seleccionado (él mismo y sus terminales).',
            ' * Solo se usa cuando el usuario es soporte.',
            ' */',
            'async function _cargar_usuarios_filtrados_por_dueno(nombre_dueno) {',
            '    if (!nombre_dueno) return;',
            '    const respuesta = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "administrador/listar_usuarios" })',
            '    });',
            '    const datos = await respuesta.json();',
            '    if (!datos.exito) return;',
            '',
            '    // Filtrar a los usuarios visibles del soporte.',
            '    const visibles = datos.usuarios.filter(u => {',
            '        if (u.nivel === \'dueno\' && u.nombre_usuario === nombre_dueno) return true;',
            '        if (u.nivel === \'terminal\' && u.dueno === nombre_dueno) return true;',
            '        return false;',
            '    });',
            '    _renderizar_tabla_usuarios(visibles);',
            '}',
            '',
            '/**',
            ' * Renderiza la tabla de usuarios con los datos dados.',
            ' */',
            'function _renderizar_tabla_usuarios(usuarios) {',
            '    const cuerpo_tabla = $("#tabla_usuarios_admin");',
            '    cuerpo_tabla.innerHTML = "";',
            '',
            '    const mapa_nombres_reales = {};',
            '    usuarios.forEach(u => {',
            '        mapa_nombres_reales[u.nombre_usuario] = u.nombre_real || u.nombre_usuario;',
            '    });',
            '',
            '    usuarios.forEach(usuario => {',
            '        const fila = document.createElement("tr");',
            '        const nombre_dueno_mostrar = usuario.dueno',
            '            ? (mapa_nombres_reales[usuario.dueno] || usuario.dueno)',
            '            : \'—\';',
            '        fila.innerHTML = `',
            '            <td>${usuario.nombre_usuario}</td>',
            '            <td>${usuario.nombre_real || "—"}</td>',
            '            <td>${usuario.email || "—"}</td>',
            '            <td>${usuario.nivel}</td>',
            '            <td>${usuario.codigo_asignado ? "•••••" : "—"}</td>',
            '            <td>${usuario.efectivo || "0"}</td>',
            '            <td>${usuario.bancarizado || "0"}</td>',
            '            <td>${usuario.banco.nombre || "—"}</td>',
            '            <td>${usuario.banco.cuenta || "—"}</td>',
            '            <td>${nombre_dueno_mostrar}</td>',
            '            <td>',
            '                <button class="btn_editar_usuario" data-usuario="${usuario.nombre_usuario}">✏️</button>',
            '                <button class="btn_eliminar_usuario" data-usuario="${usuario.nombre_usuario}">🗑️</button>',
            '            </td>',
            '        `;',
            '        cuerpo_tabla.appendChild(fila);',
            '    });',
            '',
            '    cuerpo_tabla.querySelectorAll(\'.btn_editar_usuario\').forEach(boton => {',
            '        boton.addEventListener(\'click\', () => iniciar_edicion_usuario(boton.dataset.usuario));',
            '    });',
            '    cuerpo_tabla.querySelectorAll(\'.btn_eliminar_usuario\').forEach(boton => {',
            '        boton.addEventListener(\'click\', () => eliminar_usuario_confirmado(boton.dataset.usuario));',
            '    });',
            '}',
            '',
            '$("#boton_guardar_usuario").addEventListener("click", async () => {',
        ],
    ],

    // ==========================================================
    // admin.js — restricciones del formulario segun nivel del usuario
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/admin.js',
        'descripcion' => 'admin.js: restringir formulario si es soporte',
        'buscar' => [
            '$("#boton_agregar_usuario").addEventListener("click", async () => {',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '        });',
        ],
        'reemplazar' => [
            '$("#boton_agregar_usuario").addEventListener("click", async () => {',
            '    const es_soporte = usuario_actual && usuario_actual.nivel === \'soporte\';',
            '    // Si es soporte, restringir el selector de nivel a "terminal".',
            '    if (es_soporte) {',
            '        const select_nivel = $("#nuevo_nivel");',
            '        select_nivel.innerHTML = \'<option value="terminal">Terminal</option>\';',
            '        select_nivel.value = \'terminal\';',
            '        select_nivel.disabled = true;',
            '    }',
            '    try {',
            '        const respuesta = await fetch("index.php", {',
            '            method: "POST",',
            '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '            body: new URLSearchParams({ accion: "administrador/listar_duenos" })',
            '        });',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html — selector de dueño en admin
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: selector de dueno en admin',
        'buscar' => [
            '        <div class="panel">',
            '          <h2>Panel de Administración</h2>',
            '          <button class="btn primary" id="boton_agregar_usuario">Agregar usuario</button>',
            '        </div>',
        ],
        'reemplazar' => [
            '        <div class="panel">',
            '          <h2>Panel de Administración</h2>',
            '          <button class="btn primary" id="boton_agregar_usuario">Agregar usuario</button>',
            '        </div>',
            '        <div class="panel" id="panel_selector_dueno_admin" style="display:none;">',
            '          <div class="row">',
            '            <label for="selector_dueno_admin">Dueño:</label>',
            '            <select id="selector_dueno_admin"></select>',
            '          </div>',
            '        </div>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de aplicacion.js',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.68"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.73"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de admin.js',
        'buscar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.68"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/admin.js?v=1.5piloto.73"></script>',
        ],
    ],

    // ==========================================================
    // aplicacion_POST.php — documentar nodo soporte
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.71',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: nodo soporte',
        'buscar' => [
            ' * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"` o `"terminal"`.                           |',
        ],
        'reemplazar' => [
            ' * | `nivel`         | Nodo con dato string: `"admin"`, `"dueno"`, `"terminal"` o `"soporte"`.              |',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: enlace duenos del soporte',
        'buscar' => [
            ' * | `venta_actual`  | Enlace a un nodo venta actual (solo para `terminal`). Si no existe venta activa, el enlace no existe. |',
        ],
        'reemplazar' => [
            ' * | `venta_actual`  | Enlace a un nodo venta actual (solo para `terminal`). Si no existe venta activa, el enlace no existe. |',
            ' * | `duenos`        | Nodo contenedor con dato vacío (solo para `soporte`).                                  |',
            ' * |                 | └─ Enlaces salientes con nombre de cada dueño asignado apuntando a su nodo usuario.    |',
            ' * | `soporte`       | Enlace directo al nodo usuario soporte (solo para `dueno`, opcional).                  |',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — actualizar
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73 al historial',
        'buscar' => [
            '- **v72**: host y credenciales movidos a `config_servidor.php` en la raíz.',
            '  `Conf` ahora hereda de `ConfServidor`.',
        ],
        'reemplazar' => [
            '- **v72**: host y credenciales movidos a `config_servidor.php` en la raíz.',
            '  `Conf` ahora hereda de `ConfServidor`.',
            '- **v73**: nuevo rol `soporte`. Nodo usuario con `duenos` (contenedor).',
            '  Enlace `soporte` en el nodo dueño. Validaciones de permisos por',
            '  dueño con `_verificar_permiso_dueno`. Chequeo global en el enrutador.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.72 (host y',
            'credenciales movidos a `config_servidor.php`).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73 (nuevo rol',
            '`soporte`).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: estado de la conversacion',
        'buscar' => [
            '- Cerramos la Tanda C (rate limiting y tiempos constantes) en v71.',
            '- Introdujimos la carpeta `prompts/` en v71a.',
            '- Dividimos el prompt de continuidad en framework y piloto en v71b.',
            '- Separamos host y credenciales a `config_servidor.php` en v72.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos la Tanda C (rate limiting y tiempos constantes) en v71.',
            '- Introdujimos la carpeta `prompts/` en v71a.',
            '- Dividimos el prompt de continuidad en framework y piloto en v71b.',
            '- Separamos host y credenciales a `config_servidor.php` en v72.',
            '- Agregamos el rol `soporte` en v73.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: decision de rol soporte',
        'buscar' => [
            '- **Host y credenciales viven en `config_servidor.php`** en la raíz del',
            '  proyecto. Es el único archivo que no se toca al desplegar. `Conf`',
            '  hereda de `ConfServidor`.',
        ],
        'reemplazar' => [
            '- **Host y credenciales viven en `config_servidor.php`** en la raíz del',
            '  proyecto. Es el único archivo que no se toca al desplegar. `Conf`',
            '  hereda de `ConfServidor`.',
            '- **El rol `soporte`** asiste a uno o varios dueños. Ve las mismas',
            '  pestañas que el admin, pero solo sobre sus dueños asignados. Solo',
            '  puede crear/editar/eliminar terminales de esos dueños y editar al',
            '  propio dueño. No puede cambiar el nivel de un usuario. El admin es',
            '  "soporte universal".',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump de version en cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.72. Todo funcional. Listo',
            'para arrancar la diversificación por tipo de aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73. Todo funcional. Listo',
            'para arrancar la diversificación por tipo de aplicación.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (rol soporte) ===\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";