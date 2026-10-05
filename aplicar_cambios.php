<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.75:
 *   - Fase 2, decimotercer y decimocuarto flujo arreglados:
 *     * eliminar_usuario destruye los campos del nodo usuario,
 *       el banco (con nombre y cuenta), el nodo credencial con
 *       sus campos, y las sesiones activas del usuario.
 *     * actualizar_usuario destruye los campos al cambiar de
 *       nivel (efectivo, banco), al limpiar el banco del dueño
 *       (nombre, cuenta), y al resetear el bloqueo
 *       (bloqueado_hasta).
 *   - Sesion.php: eliminar_sesiones_de_usuario y cerrar_sesion
 *     ahora destruyen los campos del nodo sesión (usuario,
 *     creado_en).
 *   - Helper nuevo _destruir_banco_usuario en Usuario.php.
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
    // Sesion.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Bump @version a 1.5piloto.75',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.75',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            '',
            '/**',
            ' * Elimina todas las sesiones activas de un usuario.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '',
            '/**',
            ' * Elimina todas las sesiones activas de un usuario.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Fix eliminar_sesiones_de_usuario: destruir campos',
        'buscar' => [
            '            if ($nodo_usuario && $nodo_usuario->dato() === $nombre_usuario) {',
            '                $raiz->eliminar_adyacente($token);',
            '                Nodo::eliminar($nodo_sesion);',
            '            }',
        ],
        'reemplazar' => [
            '            if ($nodo_usuario && $nodo_usuario->dato() === $nombre_usuario) {',
            '                $raiz->eliminar_adyacente($token);',
            '                // Fase 2, v75: destruir los campos del nodo',
            '                // sesión (usuario, creado_en). Antes quedaban',
            '                // huérfanos (~2 nodos por sesión eliminada).',
            '                _destruir_campos_simples($nodo_sesion);',
            '                Nodo::eliminar($nodo_sesion);',
            '            }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Sesiones/Sesion.php',
        'descripcion' => 'Fix cerrar_sesion: destruir campos',
        'buscar' => [
            '        $nodo_sesion = $raiz->adyacente($token);',
            '        if (!$nodo_sesion) return false;',
            '',
            '        $raiz->eliminar_adyacente($token);',
            '        Nodo::eliminar($nodo_sesion);',
            '        return true;',
        ],
        'reemplazar' => [
            '        $nodo_sesion = $raiz->adyacente($token);',
            '        if (!$nodo_sesion) return false;',
            '',
            '        $raiz->eliminar_adyacente($token);',
            '        // Fase 2, v75: destruir los campos del nodo sesión',
            '        // (usuario, creado_en).',
            '        _destruir_campos_simples($nodo_sesion);',
            '        Nodo::eliminar($nodo_sesion);',
            '        return true;',
        ],
    ],

    // --------------------------------------------------------
    // Usuario.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Bump @version a 1.5piloto.75',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.73j',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.75',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Agregar include de FuncionesAuxiliares',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            '/**',
            ' * Busca un usuario por su código de acceso.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Busca un usuario por su código de acceso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Fix cambio a admin: destruir efectivo y banco',
        'buscar' => [
            '    if ($nivel_actual !== $nivel) {',
            '        if ($nivel === \'admin\') {',
            '            $nodo_usuario->eliminar_adyacente(\'efectivo\');',
            '            $nodo_usuario->eliminar_adyacente(\'banco\');',
            '            $nodo_usuario->eliminar_adyacente(\'dueno\');',
            '        }',
        ],
        'reemplazar' => [
            '    if ($nivel_actual !== $nivel) {',
            '        if ($nivel === \'admin\') {',
            '            // Fase 2, v75: destruir efectivo (hoja) y banco',
            '            // (contenedor con nombre/cuenta) en lugar de solo',
            '            // desenlazarlos. `dueno` es una referencia externa,',
            '            // solo se desenlaza.',
            '            $nodo_ef = $nodo_usuario->adyacente(\'efectivo\');',
            '            if ($nodo_ef) {',
            '                $nodo_usuario->eliminar_adyacente(\'efectivo\');',
            '                Nodo::eliminar($nodo_ef);',
            '            }',
            '            $nodo_banco = $nodo_usuario->adyacente(\'banco\');',
            '            if ($nodo_banco) {',
            '                $nodo_usuario->eliminar_adyacente(\'banco\');',
            '                _destruir_banco_usuario($nodo_banco);',
            '            }',
            '            $nodo_usuario->eliminar_adyacente(\'dueno\');',
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Fix limpiar nombre del banco',
        'buscar' => [
            '        if ($banco_nombre !== \'\') {',
            '            $nodo_banco_nombre = $nodo_banco->adyacente(\'nombre\');',
            '            if ($nodo_banco_nombre) $nodo_banco_nombre->_dato($banco_nombre);',
            '            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), \'nombre\');',
            '        } else if ($nivel === \'dueno\') {',
            '            $nodo_banco->eliminar_adyacente(\'nombre\');',
            '        }',
        ],
        'reemplazar' => [
            '        if ($banco_nombre !== \'\') {',
            '            $nodo_banco_nombre = $nodo_banco->adyacente(\'nombre\');',
            '            if ($nodo_banco_nombre) $nodo_banco_nombre->_dato($banco_nombre);',
            '            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), \'nombre\');',
            '        } else if ($nivel === \'dueno\') {',
            '            // Fase 2, v75: destruir la hoja `nombre` en lugar',
            '            // de solo desenlazarla.',
            '            $nodo_banco_nombre = $nodo_banco->adyacente(\'nombre\');',
            '            if ($nodo_banco_nombre) {',
            '                $nodo_banco->eliminar_adyacente(\'nombre\');',
            '                Nodo::eliminar($nodo_banco_nombre);',
            '            }',
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Fix limpiar cuenta del banco',
        'buscar' => [
            '        if ($banco_cuenta !== \'\') {',
            '            $nodo_banco_cuenta = $nodo_banco->adyacente(\'cuenta\');',
            '            if ($nodo_banco_cuenta) $nodo_banco_cuenta->_dato($banco_cuenta);',
            '            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), \'cuenta\');',
            '        } else if ($nivel === \'dueno\') {',
            '            $nodo_banco->eliminar_adyacente(\'cuenta\');',
            '        }',
        ],
        'reemplazar' => [
            '        if ($banco_cuenta !== \'\') {',
            '            $nodo_banco_cuenta = $nodo_banco->adyacente(\'cuenta\');',
            '            if ($nodo_banco_cuenta) $nodo_banco_cuenta->_dato($banco_cuenta);',
            '            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), \'cuenta\');',
            '        } else if ($nivel === \'dueno\') {',
            '            // Fase 2, v75: destruir la hoja `cuenta`.',
            '            $nodo_banco_cuenta = $nodo_banco->adyacente(\'cuenta\');',
            '            if ($nodo_banco_cuenta) {',
            '                $nodo_banco->eliminar_adyacente(\'cuenta\');',
            '                Nodo::eliminar($nodo_banco_cuenta);',
            '            }',
            '        }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Fix reset de bloqueo: destruir bloqueado_hasta',
        'buscar' => [
            '            $nodo_intentos = $nodo_cred->adyacente(\'intentos_fallidos\');',
            '            if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '            else $nodo_cred->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'intentos_fallidos\');',
            '            $nodo_cred->eliminar_adyacente(\'bloqueado_hasta\');',
        ],
        'reemplazar' => [
            '            $nodo_intentos = $nodo_cred->adyacente(\'intentos_fallidos\');',
            '            if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '            else $nodo_cred->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'intentos_fallidos\');',
            '            // Fase 2, v75: destruir `bloqueado_hasta` en lugar',
            '            // de solo desenlazarlo.',
            '            $nodo_bloqueo = $nodo_cred->adyacente(\'bloqueado_hasta\');',
            '            if ($nodo_bloqueo) {',
            '                $nodo_cred->eliminar_adyacente(\'bloqueado_hasta\');',
            '                Nodo::eliminar($nodo_bloqueo);',
            '            }',
        ],
    ],

    // --------------------------------------------------------
    // Reemplazo completo de eliminar_usuario + helper nuevo
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Agregar helper _destruir_banco_usuario y reescribir eliminar_usuario',
        'buscar' => [
            '/**',
            ' * Elimina un usuario existente.',
            ' *',
            ' * @param string $nombre_usuario Nombre del usuario a eliminar.',
            ' * @return array Resultado de la operación.',
            ' */',
            'function eliminar_usuario(string $nombre_usuario): array {',
            '    if (empty($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'Nombre de usuario no proporcionado\'];',
            '    }',
            '',
            '    if ($nombre_usuario === Conf::NOMBRE_ADMIN) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar al administrador principal\'];',
            '    }',
            '',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    $nodo_usuario = $raiz->adyacente($nombre_usuario);',
            '    if (!$nodo_usuario) return [\'exito\' => false, \'error\' => \'Usuario no encontrado\'];',
            '',
            '    $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '    if ($nodo_terminales && $nodo_terminales->adyacentes()) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar un dueño con terminales asociadas\'];',
            '    }',
            '',
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
            '    Nodo::eliminar($nodo_usuario);',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    // Eliminar también en credenciales.',
            '    en_grafo_credenciales(function() use ($nombre_usuario) {',
            '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_cred) return;',
            '        $nodo_cred = $raiz_cred->adyacente($nombre_usuario);',
            '        if ($nodo_cred) {',
            '            $raiz_cred->eliminar_adyacente($nombre_usuario);',
            '            Nodo::eliminar($nodo_cred);',
            '        }',
            '    });',
            '',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Destruye el nodo banco de un usuario: sus hojas (nombre,',
            ' * cuenta) y el propio nodo banco.',
            ' *',
            ' * El nodo banco es un contenedor cuyo dato es el monto',
            ' * bancarizado. Sus hijos son `nombre` y `cuenta`, ambos',
            ' * hojas. `_destruir_campos_simples` recorre los adyacentes',
            ' * y destruye los que no tienen hijos; después se destruye',
            ' * el contenedor.',
            ' *',
            ' * @param Nodo $nodo_banco',
            ' * @return void',
            ' */',
            'function _destruir_banco_usuario(Nodo $nodo_banco): void {',
            '    _destruir_campos_simples($nodo_banco);',
            '    Nodo::eliminar($nodo_banco);',
            '}',
            '',
            '/**',
            ' * Elimina un usuario existente.',
            ' *',
            ' * A partir de v1.5piloto.75 (Fase 2 del plan de optimización',
            ' * del grafo): destruye los campos del nodo usuario (nivel,',
            ' * nombre_real, email, efectivo, banco con sus hijos), el',
            ' * nodo credencial con sus campos (codigo_hash, contrasena,',
            ' * intentos_fallidos, bloqueado_hasta, ultimo_acceso,',
            ' * ip_ultimo_acceso), y las sesiones activas del usuario.',
            ' * Antes quedaban ~15-20 nodos huérfanos por usuario.',
            ' *',
            ' * @param string $nombre_usuario Nombre del usuario a eliminar.',
            ' * @return array Resultado de la operación.',
            ' */',
            'function eliminar_usuario(string $nombre_usuario): array {',
            '    if (empty($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'Nombre de usuario no proporcionado\'];',
            '    }',
            '',
            '    if ($nombre_usuario === Conf::NOMBRE_ADMIN) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar al administrador principal\'];',
            '    }',
            '',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    $nodo_usuario = $raiz->adyacente($nombre_usuario);',
            '    if (!$nodo_usuario) return [\'exito\' => false, \'error\' => \'Usuario no encontrado\'];',
            '',
            '    $nodo_terminales = $nodo_usuario->adyacente(\'terminales\');',
            '    if ($nodo_terminales && $nodo_terminales->adyacentes()) {',
            '        return [\'exito\' => false, \'error\' => \'No se puede eliminar un dueño con terminales asociadas\'];',
            '    }',
            '',
            '    // === 1. Desenlazar referencias cruzadas entre usuarios ===',
            '',
            '    // Terminal: desenlazar del contenedor `terminales` del dueño.',
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
            '    // Soporte: limpiar los enlaces `soporte` en sus dueños.',
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
            '    // Dueño: limpiar la referencia en su soporte.',
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
            '    // === 2. Destruir el banco (contenedor con nombre/cuenta) ===',
            '    $nodo_banco = $nodo_usuario->adyacente(\'banco\');',
            '    if ($nodo_banco) {',
            '        $nodo_usuario->eliminar_adyacente(\'banco\');',
            '        _destruir_banco_usuario($nodo_banco);',
            '    }',
            '',
            '    // === 3. Destruir el contenedor `duenos` (si es soporte) ===',
            '    $nodo_duenos_del = $nodo_usuario->adyacente(\'duenos\');',
            '    if ($nodo_duenos_del) {',
            '        // Vaciar el contenedor antes de destruirlo.',
            '        $adyacentes_duenos = (array) $nodo_duenos_del->adyacentes();',
            '        foreach ($adyacentes_duenos as $nombre_d => $nodo_d) {',
            '            $nodo_duenos_del->eliminar_adyacente((string)$nombre_d);',
            '        }',
            '        $nodo_usuario->eliminar_adyacente(\'duenos\');',
            '        _destruir_campos_simples($nodo_duenos_del);',
            '        Nodo::eliminar($nodo_duenos_del);',
            '    }',
            '',
            '    // === 4. Desenlazar referencias externas ===',
            '    $nodo_usuario->eliminar_adyacente(\'dueno\');',
            '    $nodo_usuario->eliminar_adyacente(\'soporte\');',
            '',
            '    // === 5. Destruir campos simples del propio usuario ===',
            '    // (nivel, nombre_real, email, efectivo).',
            '    _destruir_campos_simples($nodo_usuario);',
            '',
            '    // === 6. Desenlazar del contenedor raíz y destruir el usuario ===',
            '    $raiz->eliminar_adyacente($nombre_usuario);',
            '    Nodo::eliminar($nodo_usuario);',
            '    guardar_ambos(Conf::NOMBRE_APP);',
            '',
            '    // === 7. Destruir en credenciales: sesiones + nodo + campos ===',
            '    en_grafo_credenciales(function() use ($nombre_usuario) {',
            '        // Eliminar sesiones activas del usuario (destruye campos).',
            '        eliminar_sesiones_de_usuario($nombre_usuario);',
            '',
            '        // Destruir el nodo credencial y sus campos.',
            '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_cred) return;',
            '        $nodo_cred = $raiz_cred->adyacente($nombre_usuario);',
            '        if ($nodo_cred) {',
            '            $raiz_cred->eliminar_adyacente($nombre_usuario);',
            '            _destruir_campos_simples($nodo_cred);',
            '            Nodo::eliminar($nodo_cred);',
            '        }',
            '    });',
            '',
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
        'descripcion' => 'Historial: agregar v75',
        'buscar' => [
            '- **v74z**: Fase 2, duodécimo flujo arreglado:',
        ],
        'reemplazar' => [
            '- **v75**: Fase 2, flujos 13 y 14 arreglados.',
            '  `eliminar_usuario` ahora destruye los campos del nodo',
            '  usuario (nivel, nombre_real, email, efectivo, banco con',
            '  sus hijos), el nodo credencial con sus campos',
            '  (codigo_hash, contrasena, intentos_fallidos,',
            '  bloqueado_hasta, ultimo_acceso, ip_ultimo_acceso), y las',
            '  sesiones activas del usuario. `actualizar_usuario`',
            '  destruye los campos al cambiar de nivel (efectivo,',
            '  banco), al limpiar el banco del dueño (nombre, cuenta),',
            '  y al resetear el bloqueo (bloqueado_hasta).',
            '  `eliminar_sesiones_de_usuario` y `cerrar_sesion` de',
            '  `Sesion.php` ahora destruyen los campos del nodo sesión.',
            '  Nuevo helper `_destruir_banco_usuario` en `Usuario.php`.',
            '- **v74z**: Fase 2, duodécimo flujo arreglado:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar flujos 13 y 14',
        'buscar' => [
            '**Duodécimo flujo arreglado en v74z:**',
        ],
        'reemplazar' => [
            '**Decimotercer y decimocuarto flujo arreglados en v75:**',
            '`eliminar_usuario` (destruye los campos del nodo usuario,',
            'el banco con sus hijos, el nodo credencial con sus campos,',
            'y las sesiones activas) y `actualizar_usuario` (destruye',
            'efectivo, banco, y las hojas de banco y bloqueo_hasta en',
            'los flujos de cambio de nivel y limpieza). También se',
            'arreglan `eliminar_sesiones_de_usuario` y `cerrar_sesion`',
            'en `Sesion.php` (destruían el nodo sesión sin sus campos).',
            'Nuevo helper `_destruir_banco_usuario`.',
            '',
            '**Duodécimo flujo arreglado en v74z:**',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v75',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74z',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.75',
            '(Fase 2, flujos 13 y 14: `eliminar_usuario` y',
            '`actualizar_usuario`. También `Sesion.php` (destrucción',
            'de campos del nodo sesión). Nuevo helper',
            '`_destruir_banco_usuario` en `Usuario.php`.).',
            'Antes: v1.5piloto.74z',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v75',
        'buscar' => [
            '- Cerramos en v74z el flujo 12 de Fase 2:',
        ],
        'reemplazar' => [
            '- Cerramos en v75 los flujos 13 y 14 de Fase 2:',
            '  `eliminar_usuario` (destruye campos del usuario, banco,',
            '  credenciales y sesiones activas) y `actualizar_usuario`',
            '  (destruye efectivo, banco, hojas de banco y',
            '  bloqueado_hasta). También `eliminar_sesiones_de_usuario`',
            '  y `cerrar_sesion` en `Sesion.php`. Nuevo helper',
            '  `_destruir_banco_usuario`.',
            '- Cerramos en v74z el flujo 12 de Fase 2:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v75',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74z (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.75 (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v75',
        'buscar' => [
            'v74z: flujo 12 (`limpiar_viajes_de_prueba`).',
        ],
        'reemplazar' => [
            'v74z: flujo 12 (`limpiar_viajes_de_prueba`).',
            'v75: flujos 13 y 14 (`eliminar_usuario`,',
            '`actualizar_usuario`) y `Sesion.php`.',
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