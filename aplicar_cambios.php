<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76k (Fase A de contextos del piloto):
 *   - Controlador.php: nuevo comando grafo:reemplazar_referencias.
 *   - Usuario.php: agregar_usuario y actualizar_usuario (rama
 *     credenciales) crean usuarios con ID especial us_<nombre>.
 *   - index.php: admin en ambos grafos con ID especial. Bloque
 *     ?migrar_usuarios_especiales=1.
 *   - miscelaneas/migrar_usuarios_especiales.php (nuevo): la
 *     migración, que usa el comando para redirigir referencias.
 *   - prompts/prompt_piloto.md: documentación.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Controlador/Controlador.php — nuevo comando
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: agregar comando grafo:reemplazar_referencias',
        'buscar' => [
            '            return [',
            '                \'eliminados\' => $eliminados,',
            '                \'total_huerfanos\' => $total_huerfanos,',
            '            ];',
            '        }, null, false);',
            '    }',
        ],
        'reemplazar' => [
            '            return [',
            '                \'eliminados\' => $eliminados,',
            '                \'total_huerfanos\' => $total_huerfanos,',
            '            ];',
            '        }, null, false);',
            '',
            '        // ─── grafo:reemplazar_referencias ──────────────────',
            '        //',
            '        // Recibe un mapa {id_viejo: id_nuevo}. Recorre todos',
            '        // los nodos del grafo actual y reemplaza cualquier',
            '        // enlace que apunte a id_viejo por un enlace al nodo',
            '        // id_nuevo. Necesario para migraciones estructurales',
            '        // (por ejemplo, convertir usuarios a IDs especiales).',
            '        //',
            '        // No modifica los nodos viejos. Solo redirige las',
            '        // referencias entrantes que apuntan a ellos.',
            '        //',
            '        // Devuelve { reemplazos: int }.',
            '        self::registrar_comando(\'grafo:reemplazar_referencias\', function(string $token, array $args) {',
            '            $mapa = $args[0] ?? [];',
            '            if (!is_array($mapa) || empty($mapa)) {',
            '                return [\'reemplazos\' => 0];',
            '            }',
            '',
            '            // Recolectar los cambios primero, para no modificar',
            '            // el grafo mientras lo recorremos.',
            '            $cambios = [];',
            '            Nodo::por_cada_nodo_ejecutar($token, function($nodo) use ($mapa, &$cambios) {',
            '                $adyacentes = $nodo->adyacentes();',
            '                if (!$adyacentes) return;',
            '                foreach ($adyacentes as $enlace => $destino) {',
            '                    $id_dest = (string)$destino->id();',
            '                    if (isset($mapa[$id_dest])) {',
            '                        $cambios[] = [$nodo, (string)$enlace, $mapa[$id_dest]];',
            '                    }',
            '                }',
            '            }, null);',
            '',
            '            $reemplazos = 0;',
            '            foreach ($cambios as [$origen, $enlace, $id_nuevo_destino]) {',
            '                $destino = Nodo::nodo_por_id($id_nuevo_destino);',
            '                if ($origen && $destino) {',
            '                    $origen->_adyacente_en($destino, $enlace, true);',
            '                    $reemplazos++;',
            '                }',
            '            }',
            '            return [\'reemplazos\' => $reemplazos];',
            '        }, null, false);',
            '    }',
        ],
    ],

    // ============================================================
    // Aplicacion/Usuarios/Usuario.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: bump @version',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.75',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.76k',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: crear usuario con ID especial',
        'buscar' => [
            '    if ($raiz->adyacente($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre de usuario ya existe\'];',
            '    }',
            '',
            '    $nodo_usuario = Nodo::crear_con_dato($nombre_usuario);',
        ],
        'reemplazar' => [
            '    if ($raiz->adyacente($nombre_usuario)) {',
            '        return [\'exito\' => false, \'error\' => \'El nombre de usuario ya existe\'];',
            '    }',
            '',
            '    // Fase A (v76k): el nodo usuario se crea con ID especial',
            '    // us_<nombre>. El enlace desde `usuarios` sigue llamándose',
            '    // <nombre> (nombre visible), así todos los accesos por',
            '    // adyacente() siguen funcionando sin cambios.',
            '    $id_especial = \'us_\' . $nombre_usuario;',
            '    if (Nodo::existe($id_especial)) {',
            '        return [\'exito\' => false, \'error\' => \'El ID especial del usuario ya existe\'];',
            '    }',
            '    $nodo_usuario = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial);',
            '    if (!$nodo_usuario) {',
            '        return [\'exito\' => false, \'error\' => \'No se pudo crear el nodo del usuario\'];',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: credenciales con ID especial',
        'buscar' => [
            '        $nodo_cred = Nodo::crear_con_dato($nombre_usuario);',
            '        if ($codigo_acceso !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($codigo_acceso, PASSWORD_DEFAULT)), \'codigo_hash\');',
        ],
        'reemplazar' => [
            '        // Fase A (v76k): el nodo credencial también con ID',
            '        // especial us_<nombre>. El enlace desde `usuarios`',
            '        // (en credenciales) sigue llamándose <nombre>.',
            '        $id_especial_cred = \'us_\' . $nombre_usuario;',
            '        $nodo_cred = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial_cred);',
            '        if (!$nodo_cred) return;',
            '        if ($codigo_acceso !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($codigo_acceso, PASSWORD_DEFAULT)), \'codigo_hash\');',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: actualizar credenciales con ID especial',
        'buscar' => [
            '            $nodo_cred = $raiz_cred->adyacente($nombre_usuario);',
            '            if (!$nodo_cred) {',
            '                $nodo_cred = Nodo::crear_con_dato($nombre_usuario);',
            '                $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);',
            '            }',
        ],
        'reemplazar' => [
            '            $nodo_cred = $raiz_cred->adyacente($nombre_usuario);',
            '            if (!$nodo_cred) {',
            '                $id_especial_cred = \'us_\' . $nombre_usuario;',
            '                $nodo_cred = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial_cred);',
            '                if ($nodo_cred) {',
            '                    $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);',
            '                }',
            '            }',
        ],
    ],

    // ============================================================
    // index.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76k',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76j',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76k',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bloque ?migrar_usuarios_especiales',
        'buscar' => [
            '// ==== Bloque temporal para prueba de contextos (v1.5i.7k) ====',
            'if (isset($_GET[\'probar_contextos\'])) {',
            '    require_once __DIR__ . \'/Pruebas/prueba_contextos.php\';',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// ==== Bloque temporal para prueba de contextos (v1.5i.7k) ====',
            'if (isset($_GET[\'probar_contextos\'])) {',
            '    require_once __DIR__ . \'/Pruebas/prueba_contextos.php\';',
            '    exit;',
            '}',
            '',
            '// ==== Migración de usuarios a IDs especiales (v76k) ====',
            '// Convierte cada nodo usuario a ID especial us_<nombre>.',
            '// Idempotente: si ya están migrados, no hace nada.',
            'if (isset($_GET[\'migrar_usuarios_especiales\'])) {',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    require_once __DIR__ . \'/miscelaneas/migrar_usuarios_especiales.php\';',
            '    $res = migrar_usuarios_a_especiales($nombre_app);',
            '    echo "Migración de usuarios a IDs especiales\\n";',
            '    echo "==========================================\\n\\n";',
            '    foreach ($res as $grafo => $detalle) {',
            '        echo "-- $grafo --\\n";',
            '        if (is_array($detalle)) {',
            '            foreach ($detalle as $k => $v) {',
            '                if (is_array($v)) {',
            '                    echo "  $k:\\n";',
            '                    foreach ($v as $linea) echo "    $linea\\n";',
            '                } else {',
            '                    echo "  $k => " . var_export($v, true) . "\\n";',
            '                }',
            '            }',
            '        } else {',
            '            echo "  " . var_export($detalle, true) . "\\n";',
            '        }',
            '        echo "\\n";',
            '    }',
            '    echo "Listo.\\n";',
            '    exit;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: admin app con ID especial',
        'buscar' => [
            '    $nodo_admin = Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN);',
            '    $nodo_admin->_adyacente_en(Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN), \'nombre_real\');',
            '    $nodo_admin->_adyacente_en(Nodo::crear_con_dato(\'admin\'), \'nivel\');',
            '    $raiz_usuarios->_adyacente_en($nodo_admin, ConfiguracionApli::NOMBRE_ADMIN);',
        ],
        'reemplazar' => [
            '    $id_especial_admin = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
            '    $nodo_admin = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin);',
            '    if ($nodo_admin) {',
            '        $nodo_admin->_adyacente_en(Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN), \'nombre_real\');',
            '        $nodo_admin->_adyacente_en(Nodo::crear_con_dato(\'admin\'), \'nivel\');',
            '        $raiz_usuarios->_adyacente_en($nodo_admin, ConfiguracionApli::NOMBRE_ADMIN);',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: admin credenciales con ID especial',
        'buscar' => [
            '    if (!$raiz_cred->adyacente(ConfiguracionApli::NOMBRE_ADMIN)) {',
            '        $nodo_admin_cred = Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN);',
            '        $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), \'codigo_hash\');',
            '        $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);',
            '    }',
        ],
        'reemplazar' => [
            '    if (!$raiz_cred->adyacente(ConfiguracionApli::NOMBRE_ADMIN)) {',
            '        $id_especial_admin_cred = \'us_\' . ConfiguracionApli::NOMBRE_ADMIN;',
            '        $nodo_admin_cred = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin_cred);',
            '        if ($nodo_admin_cred) {',
            '            $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), \'codigo_hash\');',
            '            $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);',
            '        }',
            '    }',
        ],
    ],

    // ============================================================
    // miscelaneas/migrar_usuarios_especiales.php (nuevo)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'miscelaneas/migrar_usuarios_especiales.php',
        'descripcion' => 'Script de migración de usuarios a IDs especiales',
        'contenido' => [
            '<?php',
            '/**',
            ' * Migración: convertir cada nodo usuario a ID especial',
            ' * us_<nombre>.',
            ' *',
            ' * Los enlaces desde `usuarios` siguen llamándose <nombre>',
            ' * (nombre visible), así todos los accesos por adyacente()',
            ' * siguen funcionando sin cambios.',
            ' *',
            ' * Idempotente: los usuarios ya migrados (con ID que empieza',
            ' * por us_) se saltean.',
            ' *',
            ' * @since 1.5piloto.76k',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            '',
            '/**',
            ' * Ejecuta la migración en ambos grafos.',
            ' *',
            ' * @param string $nombre_app Nombre de la superestructura de la app.',
            ' * @return array Resumen de la operación.',
            ' */',
            'function migrar_usuarios_a_especiales(string $nombre_app): array {',
            '    $res = [',
            '        \'app\' => null,',
            '        \'credenciales\' => null,',
            '    ];',
            '',
            '    // Migrar el grafo de la aplicación.',
            '    $res[\'app\'] = _migrar_usuarios_en_grafo_actual();',
            '',
            '    // Migrar el grafo de credenciales. `en_grafo_credenciales`',
            '    // guarda la app al entrar (con los usuarios ya migrados)',
            '    // y la recarga al salir.',
            '    $res[\'credenciales\'] = en_grafo_credenciales(function() {',
            '        return _migrar_usuarios_en_grafo_actual();',
            '    });',
            '',
            '    return $res;',
            '}',
            '',
            '/**',
            ' * Migra los usuarios del grafo actualmente cargado.',
            ' *',
            ' * Pasos:',
            ' *   1. Recolectar usuarios comunes (ID numérico).',
            ' *   2. Crear los nodos especiales us_<nombre>.',
            ' *   3. Copiar los adyacentes de cada viejo al nuevo,',
            ' *      redirigiendo destinos que también sean viejos.',
            ' *   4. Actualizar el enlace en `usuarios`.',
            ' *   5. Redirigir referencias externas (vía comando).',
            ' *   6. Destruir los nodos viejos.',
            ' *',
            ' * @return array Resumen.',
            ' */',
            'function _migrar_usuarios_en_grafo_actual(): array {',
            '    $res = [',
            '        \'migrados\' => 0,',
            '        \'saltados\' => 0,',
            '        \'enlaces_actualizados\' => 0,',
            '        \'errores\' => [],',
            '    ];',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) {',
            '        $res[\'errores\'][] = \'No existe el nodo usuarios.\';',
            '        return $res;',
            '    }',
            '',
            '    $adyacentes = $raiz_usuarios->adyacentes();',
            '    if (!$adyacentes) {',
            '        return $res;',
            '    }',
            '',
            '    // --- Paso 1 y 2: recolectar y crear los nuevos nodos ---',
            '    $migraciones = [];',
            '    foreach ($adyacentes as $nombre_enlace => $nodo_viejo) {',
            '        $nombre_enlace = (string)$nombre_enlace;',
            '        $id_viejo = (string)$nodo_viejo->id();',
            '',
            '        if (strpos($id_viejo, \'us_\') === 0) {',
            '            $res[\'saltados\']++;',
            '            continue;',
            '        }',
            '        if (!is_numeric($id_viejo)) {',
            '            $res[\'errores\'][] = "ID no numérico ni us_: $id_viejo (enlace: $nombre_enlace)";',
            '            continue;',
            '        }',
            '',
            '        $id_nuevo = \'us_\' . $nombre_enlace;',
            '        if (Nodo::existe($id_nuevo)) {',
            '            $res[\'errores\'][] = "Ya existe el nodo especial $id_nuevo";',
            '            continue;',
            '        }',
            '',
            '        $nodo_nuevo = Nodo::crear_con_dato_e_id($nodo_viejo->dato(), $id_nuevo);',
            '        if (!$nodo_nuevo) {',
            '            $res[\'errores\'][] = "No se pudo crear $id_nuevo";',
            '            continue;',
            '        }',
            '',
            '        $migraciones[] = [',
            '            \'viejo\' => $nodo_viejo,',
            '            \'nuevo\' => $nodo_nuevo,',
            '            \'enlace\' => $nombre_enlace,',
            '            \'id_viejo\' => $id_viejo,',
            '            \'id_nuevo\' => $id_nuevo,',
            '        ];',
            '    }',
            '',
            '    if (empty($migraciones)) {',
            '        return $res;',
            '    }',
            '',
            '    // Mapa id_viejo → id_nuevo. Lo usan el paso 3 y el comando del paso 5.',
            '    $mapa = [];',
            '    foreach ($migraciones as $m) {',
            '        $mapa[$m[\'id_viejo\']] = $m[\'id_nuevo\'];',
            '    }',
            '',
            '    // --- Paso 3: copiar adyacentes del viejo al nuevo ---',
            '    // Los destinos que también son viejos se redirigen al nuevo',
            '    // correspondiente (para no dejar referencias a nodos que se',
            '    // van a destruir).',
            '    foreach ($migraciones as $m) {',
            '        $ady_viejos = $m[\'viejo\']->adyacentes();',
            '        if (!$ady_viejos) continue;',
            '        foreach ($ady_viejos as $enlace_ady => $destino) {',
            '            $enlace_ady = (string)$enlace_ady;',
            '            $id_destino = (string)$destino->id();',
            '            if (isset($mapa[$id_destino])) {',
            '                $nodo_destino = Nodo::nodo_por_id($mapa[$id_destino]);',
            '                if ($nodo_destino) {',
            '                    $m[\'nuevo\']->_adyacente_en($nodo_destino, $enlace_ady);',
            '                }',
            '            } else {',
            '                $m[\'nuevo\']->_adyacente_en($destino, $enlace_ady);',
            '            }',
            '        }',
            '    }',
            '',
            '    // --- Paso 4: actualizar el enlace en `usuarios` ---',
            '    foreach ($migraciones as $m) {',
            '        $raiz_usuarios->_adyacente_en($m[\'nuevo\'], $m[\'enlace\'], true);',
            '    }',
            '',
            '    // --- Paso 5: redirigir referencias externas ---',
            '    // Usa el comando `grafo:reemplazar_referencias` que tiene',
            '    // el token encapsulado. Busca todas las aristas del grafo',
            '    // que apunten a un nodo viejo y las redirige al nuevo.',
            '    $resultado = Controlador::ejecutar_comando(\'grafo:reemplazar_referencias\', $mapa);',
            '    if (is_array($resultado) && isset($resultado[\'reemplazos\'])) {',
            '        $res[\'enlaces_actualizados\'] = (int)$resultado[\'reemplazos\'];',
            '    } else {',
            '        $res[\'errores\'][] = \'El comando grafo:reemplazar_referencias no devolvió un resultado válido.\';',
            '    }',
            '',
            '    // --- Paso 6: destruir los nodos viejos ---',
            '    // Primero, quitarles todas las salientes (para que los',
            '    // destinos dejen de tener referencias entrantes desde',
            '    // los viejos). Después, destruirlos.',
            '    foreach ($migraciones as $m) {',
            '        $m[\'viejo\']->eliminar_adyacentes();',
            '    }',
            '    foreach ($migraciones as $m) {',
            '        if (Nodo::eliminar($m[\'viejo\'])) {',
            '            $res[\'migrados\']++;',
            '        } else {',
            '            $res[\'errores\'][] = "No se pudo eliminar el nodo viejo {$m[\'id_viejo\']}";',
            '        }',
            '    }',
            '',
            '    return $res;',
            '}',
            '?>',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — documentación
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76j',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76k',
            '(Fase A de contextos del piloto. Todos los usuarios pasan a',
            'tener ID especial `us_<nombre>`. Los enlaces desde `usuarios`',
            'siguen llamándose `<nombre>` (nombre visible), por lo que',
            'todos los accesos por `adyacente()` siguen funcionando sin',
            'cambios. Script de migración idempotente en',
            '`miscelaneas/migrar_usuarios_especiales.php`, ejecutable con',
            '`?migrar_usuarios_especiales=1` desde `index.php`. Nuevo',
            'comando `grafo:reemplazar_referencias` en el `Controlador`',
            'para redirigir referencias cruzadas. Sin carga parcial',
            'todavía.).',
            'Antes: v1.5piloto.76j',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v76k al historial',
        'buscar' => [
            '- **v76j**: cierre de la fase 2 del framework',
        ],
        'reemplazar' => [
            '- **v76k**: Fase A de contextos del piloto. Todos los',
            '  usuarios pasan a ser IDs especiales `us_<nombre>`. Los',
            '  enlaces desde `usuarios` siguen llamándose `<nombre>`',
            '  (nombre visible), así los accesos por `adyacente()` no',
            '  cambian. `agregar_usuario`, `actualizar_usuario` (rama',
            '  de credenciales) y la creación del admin en `index.php`',
            '  (ambos grafos) ahora usan `crear_con_dato_e_id`. Nuevo',
            '  comando genérico `grafo:reemplazar_referencias` en el',
            '  `Controlador` (recibe un mapa `{viejo → nuevo}` y',
            '  redirige todas las aristas del grafo que apunten a un',
            '  viejo). Nuevo script `miscelaneas/migrar_usuarios_especiales.php`',
            '  (idempotente) y bloque `?migrar_usuarios_especiales=1`',
            '  en `index.php`. Sin cambios en los accesos, sin carga',
            '  parcial todavía.',
            '- **v76j**: cierre de la fase 2 del framework',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.7: registrar Fase A completada',
        'buscar' => [
            '**Dueños como IDs especiales.** El primer paso es',
            'convertir a los dueños en IDs especiales del grafo.',
            'Hoy los dueños son nodos comunes colgando del ID',
            'especial `usuarios`. En el plan pasan a ser roots',
            'propios, con un prefijo que los identifique (por',
            'ejemplo `us_dueno1`, `us_dueno2`).',
        ],
        'reemplazar' => [
            '**Usuarios como IDs especiales (Fase A, completada en v76k).**',
            'Todos los usuarios (no solo los dueños) son ahora nodos',
            'con ID especial `us_<nombre>`. Los enlaces desde',
            '`usuarios` siguen llamándose `<nombre>` (nombre visible),',
            'así todos los accesos por `adyacente()` funcionan sin',
            'cambios. Los nodos viejos se migraron con',
            '`miscelaneas/migrar_usuarios_especiales.php`, que usa el',
            'nuevo comando `grafo:reemplazar_referencias` para redirigir',
            'las aristas cruzadas. Próximo paso: aprovechar la carga',
            'parcial (Fase C) una vez cerrada la Fase B (tipos como',
            'IDs especiales).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de la Fase A',
        'buscar' => [
            '- Cerramos en v76j el cierre de la fase 2 del framework',
        ],
        'reemplazar' => [
            '- Cerramos en v76k la Fase A de contextos del piloto:',
            '  todos los usuarios pasan a ser IDs especiales',
            '  `us_<nombre>`. Los enlaces desde `usuarios` siguen',
            '  llamándose `<nombre>`, así todos los accesos por',
            '  `adyacente()` funcionan sin cambios. Solo se tocaron',
            '  los 3 lugares donde se CREAN usuarios (`agregar_usuario`,',
            '  `actualizar_usuario` rama credenciales, y la creación',
            '  del admin en `index.php`). Nuevo comando genérico',
            '  `grafo:reemplazar_referencias` en el `Controlador`,',
            '  que redirige todas las aristas del grafo desde un',
            '  mapa `{viejo → nuevo}`. Script de migración',
            '  idempotente en `miscelaneas/migrar_usuarios_especiales.php`,',
            '  ejecutable con `?migrar_usuarios_especiales=1`. Sin',
            '  cambios en los accesos, sin carga parcial todavía.',
            '- Cerramos en v76j el cierre de la fase 2 del framework',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76j (framework 1.5i.7k).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76k (framework 1.5i.7k).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76k al bloque de estado',
        'buscar' => [
            'v76j: cierre de la fase 2 del framework (contextos).',
            'El framework quedó en 1.5i.7k, con SQL64 (PHP) y',
            'IndexedDB64 (JS) completos. Deuda de diseño anotada:',
            'dejar el Nodo limpio antes de agregar más métodos',
            'de indexación de contextos (ver §11.4 del prompt del',
            'framework).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5w)',
            'tiene 56 pruebas corriendo.',
        ],
        'reemplazar' => [
            'v76j: cierre de la fase 2 del framework (contextos).',
            'El framework quedó en 1.5i.7k, con SQL64 (PHP) y',
            'IndexedDB64 (JS) completos. Deuda de diseño anotada:',
            'dejar el Nodo limpio antes de agregar más métodos',
            'de indexación de contextos (ver §11.4 del prompt del',
            'framework).',
            'v76k: Fase A de contextos del piloto. Todos los usuarios',
            'son IDs especiales `us_<nombre>`. Nuevo comando',
            '`grafo:reemplazar_referencias`. Script de migración',
            'idempotente en `miscelaneas/migrar_usuarios_especiales.php`.',
            'Sin cambios en los accesos, sin carga parcial todavía.',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5w)',
            'tiene 56 pruebas corriendo.',
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