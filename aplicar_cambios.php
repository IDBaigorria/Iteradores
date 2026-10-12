<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.78a.
 *   - Usuario.php: nuevo helper _asegurar_niveles_usuario y
 *     llamada desde agregar_usuario. Cierra el mismo agujero que
 *     v77l cerró para compartidos: los usuarios creados después
 *     de la migración B1 quedaban sin `publico`/`privado`.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // 1. Usuario.php — llamada al helper en agregar_usuario
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario: llamada a _asegurar_niveles_usuario',
        'buscar' => [
            '    // Si es soporte, asignar dueños.',
            '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        ],
        'reemplazar' => [
            '    // Si es soporte, asignar dueños.',
            '    if ($nivel === \'soporte\' && !empty($duenos_asignados)) {',
            '        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);',
            '    }',
            '',
            '    // Fase B1: asegurar los contenedores `publico` y',
            '    // `privado`. Mismo cierre de agujero que v77l hizo',
            '    // para compartidos: sin esto, un usuario creado',
            '    // después de la migración B1 queda sin contenedores,',
            '    // y la detección de la migración da `false`',
            '    // permanentemente.',
            '    _asegurar_niveles_usuario($nodo_usuario);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        ],
    ],

    // ============================================================
    // 2. Usuario.php — nuevo helper al final del archivo
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario: agregar helper _asegurar_niveles_usuario',
        'buscar' => [
            '// ============================================================',
            '// Fase B2.3.5c.2: aseguramiento del compartido de un terminal',
        ],
        'reemplazar' => [
            '// ============================================================',
            '// Fase B1: aseguramiento de los contenedores `publico` y',
            '// `privado`. Se llaman desde `agregar_usuario` para que',
            '// todo usuario nuevo salga con la estructura completa.',
            '// Idempotente. No elimina los enlaces viejos (viven en',
            '// la raíz del usuario, en paralelo a los contenedores).',
            '// ============================================================',
            '',
            '/**',
            ' * Asegura que un nodo usuario tenga los contenedores',
            ' * `publico` y `privado`, con los alias a los nodos ya',
            ' * existentes. Idempotente.',
            ' *',
            ' * Distribución:',
            ' *  - `publico` (dato = nombre_usuario): `nivel`,',
            ' *    `nombre_real`, `email`.',
            ' *  - `privado` (dato = ``): `efectivo`, `banco`,',
            ' *    `terminales`, `empresas`, `viajes`, `pasajeros`,',
            ' *    `ventas`, `rendiciones`, `liquidaciones`,',
            ' *    `cancelaciones`.',
            ' *  - Enlaces "de permiso" (`dueno`, `soporte`, `duenos`)',
            ' *    quedan en la raíz, no se mueven.',
            ' *',
            ' * Los enlaces viejos en la raíz NO se eliminan: el',
            ' * contenedor es un alias, apunta a los mismos nodos',
            ' * físicos. Todo el código actual sigue funcionando.',
            ' *',
            ' * @param Nodo $nodo_usuario',
            ' * @return void',
            ' */',
            'function _asegurar_niveles_usuario(Nodo $nodo_usuario): void {',
            '    if (!$nodo_usuario) return;',
            '',
            '    $nombre = (string)$nodo_usuario->dato();',
            '',
            '    // === publico ===',
            '    $publico = $nodo_usuario->adyacente(\'publico\');',
            '    if (!$publico) {',
            '        $publico = Nodo::crear_con_dato($nombre);',
            '        $nodo_usuario->_adyacente_en($publico, \'publico\');',
            '    } elseif ((string)$publico->dato() !== $nombre) {',
            '        $publico->_dato($nombre);',
            '    }',
            '    foreach ([\'nivel\', \'nombre_real\', \'email\'] as $campo) {',
            '        $nodo_campo = $nodo_usuario->adyacente($campo);',
            '        if ($nodo_campo && !$publico->adyacente($campo)) {',
            '            $publico->_adyacente_en($nodo_campo, $campo);',
            '        }',
            '    }',
            '',
            '    // === privado ===',
            '    $privado = $nodo_usuario->adyacente(\'privado\');',
            '    if (!$privado) {',
            '        $privado = Nodo::crear_con_dato(\'\');',
            '        $nodo_usuario->_adyacente_en($privado, \'privado\');',
            '    }',
            '    $campos_privados = [',
            '        \'efectivo\', \'banco\', \'terminales\', \'empresas\',',
            '        \'viajes\', \'pasajeros\', \'ventas\', \'rendiciones\',',
            '        \'liquidaciones\', \'cancelaciones\',',
            '    ];',
            '    foreach ($campos_privados as $campo) {',
            '        $nodo_campo = $nodo_usuario->adyacente($campo);',
            '        if ($nodo_campo && !$privado->adyacente($campo)) {',
            '            $privado->_adyacente_en($nodo_campo, $campo);',
            '        }',
            '    }',
            '}',
            '',
            '// ============================================================',
            '// Fase B2.3.5c.2: aseguramiento del compartido de un terminal',
        ],
    ],

    // ============================================================
    // 3. Usuario.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario: bump a 1.5piloto.78a',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77s',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.78a',
        ],
    ],

    // ============================================================
    // 4. plan_actual.md — tanda actual a v78a
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v78a',
        'buscar' => [
            '**Tanda actual:** v77z (fix de B3.3e).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v78a (asegurar niveles al crear usuario).',
            '',
            '**v78a.** Mismo cierre de agujero que v77l hizo para',
            'compartidos: los usuarios creados después de la migración',
            'B1 (v76m) quedaban sin contenedores `publico`/`privado`.',
            'Resultado: el detector de la migración `niveles_usuario`',
            'daba `false` cada vez que se creaba un usuario nuevo, y',
            'había que volver a correr `grafo:crear_niveles_usuario` a',
            'mano. Fix: nuevo helper `_asegurar_niveles_usuario` en',
            '`Usuario.php`, llamado desde `agregar_usuario`. Idempotente.',
            '',
            '**Fix v77z.** Los dos detectores reescritos en v77y',
            '(`detectar_usuarios_especiales` y `detectar_niveles_usuario`)',
            'se revierten a la lógica original basada en',
            '`usuarios->adyacentes()`.',
            '',
            '**B3.3c sigue pendiente.** Los helpers que iteran sobre',
            'todos los dueños buscando por id requieren rediseño.',
        ],
    ],

    // ============================================================
    // 5. prompt_piloto.md — agregar v78a
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v78a',
        'buscar' => [
            '- **v77z**: fix de B3.3e. Los dos detectores reescritos en',
        ],
        'reemplazar' => [
            '- **v78a**: helper `_asegurar_niveles_usuario` en',
            '  `Usuario.php` + llamada desde `agregar_usuario`. Cierra',
            '  el mismo agujero que v77l para compartidos: los usuarios',
            '  creados después de la migración B1 (v76m) quedaban sin',
            '  `publico`/`privado`, y el detector de la migración',
            '  `niveles_usuario` daba `false` cada vez que se creaba un',
            '  usuario. Idempotente, no elimina los enlaces viejos.',
            '- **v77z**: fix de B3.3e. Los dos detectores reescritos en',
        ],
    ],

    // ============================================================
    // 6. index.php — bump
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.78a',
        'buscar' => [
            ' * @version   1.5piloto.77z',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.78a',
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