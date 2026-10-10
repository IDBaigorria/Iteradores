<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77f (Fase B2.3.5a del modelo topológico).
 *   - Aplicacion/Migraciones/Comandos.php: nuevo comando
 *     `app:repuntar_terminales_compartido`. Para cada dueño y cada
 *     compartido `compartido_con_us_termX`, marca el compartido con
 *     `dato = nombre_dueno` (opción A) y repunta el enlace `dueno`
 *     del terminal al compartido. Idempotente.
 *   - Aplicacion/Migraciones/Funciones.php: detección y aplicación.
 *   - Aplicacion/Migraciones/Registro.php: entrada nueva.
 *   - miscelaneas/repuntar_terminales_compartido.php (nuevo): wrapper.
 *   - index.php: bloque ?repuntar_terminales_compartido=1. Bump.
 *   - prompts/plan_actual.md: registro.
 *
 * NO toca el enrutador. NO cambia el código de flujo. Solo cambia
 * el destino del enlace `dueno` en el grafo. La app sigue
 * funcionando igual porque el código de navegación ya está
 * preparado (B2.2 + B2.3.4).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Comandos.php — nuevo comando
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Comandos.php',
        'descripcion' => 'Comandos: agregar app:repuntar_terminales_compartido',
        'buscar' => [
            '    // ─── app:construir_arboles_compartidos ──────────────',
        ],
        'reemplazar' => [
            '    // ─── app:repuntar_terminales_compartido ─────────────',
            '    //',
            '    // Fase B2.3.5a del modelo topológico (v77f).',
            '    //',
            '    // Para cada dueño y cada uno de sus compartidos',
            '    // `compartido_con_us_termX`:',
            '    //   1. Marca el compartido con `dato = nombre_dueno` (opción A).',
            '    //   2. Repunta el enlace `dueno` del terminal `us_termX` para',
            '    //      que apunte al compartido en vez del nodo del dueño real.',
            '    //',
            '    // Después del repuntado, el terminal navega SOLO por el',
            '    // subgrafo compartido. La app sigue funcionando igual porque',
            '    // el código ya está preparado (B2.2 + B2.3.4).',
            '    //',
            '    // Idempotente: si el terminal ya apunta al compartido, se',
            '    // saltea. Si el compartido ya tiene el dato correcto, también.',
            '    //',
            '    // Args: [\'dueno\' => nombre | \'todos\',',
            '    //        \'terminal\' => nombre | \'todos\']',
            '    // Devuelve: { repuntados: int, ya_repuntados: int, errores: [] }.',
            '    Controlador::registrar_comando(\'app:repuntar_terminales_compartido\', function(string $token, array $args) {',
            '        $opciones = $args[0] ?? [];',
            '        $dueno_filtro = (string)($opciones[\'dueno\'] ?? \'todos\');',
            '        $terminal_filtro = (string)($opciones[\'terminal\'] ?? \'todos\');',
            '',
            '        $repuntados = 0;',
            '        $ya_repuntados = 0;',
            '        $errores = [];',
            '',
            '        Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$repuntados, &$ya_repuntados, &$errores, $dueno_filtro, $terminal_filtro) {',
            '            $id = (string)$nodo->id();',
            '            if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '            // Nivel del usuario.',
            '            $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '            if (!$nivel_nodo) {',
            '                $publico = $nodo->adyacente(\'publico\');',
            '                if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '            }',
            '            $nivel = $nivel_nodo ? $nivel_nodo->dato() : \'\';',
            '            if ($nivel !== \'dueno\') return null;',
            '',
            '            $nombre_dueno = (string)$nodo->dato();',
            '            if ($dueno_filtro !== \'todos\' && $nombre_dueno !== $dueno_filtro) return null;',
            '',
            '            // Recorrer los compartidos del dueño.',
            '            $ady = (array)$nodo->adyacentes();',
            '            foreach ($ady as $enlace => $compartido) {',
            '                $enlace = (string)$enlace;',
            '                if (strpos($enlace, \'compartido_con_\') !== 0) continue;',
            '',
            '                $nombre_terminal = substr($enlace, strlen(\'compartido_con_\'));',
            '                if ($nombre_terminal === \'\') continue;',
            '                if ($terminal_filtro !== \'todos\' && $nombre_terminal !== $terminal_filtro) continue;',
            '',
            '                // Debe estar marcado como compartido (por B2.3.3).',
            '                if (!$compartido->adyacente(\'_es_compartido\')) {',
            '                    $errores[] = "Compartido $enlace no tiene _es_compartido. Correr B2.3.3 primero.";',
            '                    continue;',
            '                }',
            '',
            '                // Encontrar el nodo del terminal.',
            '                $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '                if (!$nodo_terminal) {',
            '                    $errores[] = "Terminal us_$nombre_terminal no encontrada para compartido $enlace.";',
            '                    continue;',
            '                }',
            '',
            '                // Verificar si ya está repuntado.',
            '                $nodo_dueno_actual = $nodo_terminal->adyacente(\'dueno\');',
            '                if ($nodo_dueno_actual && $nodo_dueno_actual->id() === $compartido->id()) {',
            '                    // Ya repuntado. Pero por las dudas chequear el dato.',
            '                    if ($compartido->dato() !== $nombre_dueno) {',
            '                        $compartido->_dato($nombre_dueno);',
            '                    }',
            '                    $ya_repuntados++;',
            '                    continue;',
            '                }',
            '',
            '                // 1. Marcar el compartido con el dato del dueño (opción A).',
            '                $compartido->_dato($nombre_dueno);',
            '',
            '                // 2. Repuntar el enlace `dueno` del terminal al compartido.',
            '                $nodo_terminal->_adyacente_en($compartido, \'dueno\', true);',
            '',
            '                $repuntados++;',
            '            }',
            '            return null;',
            '        }, null);',
            '',
            '        return [\'repuntados\' => $repuntados, \'ya_repuntados\' => $ya_repuntados, \'errores\' => $errores];',
            '    }, null, false);',
            '',
            '    // ─── app:construir_arboles_compartidos ──────────────',
        ],
    ],

    // ============================================================
    // Funciones.php — detección y aplicación
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Funciones.php',
        'descripcion' => 'Funciones: detección y aplicación de repuntado',
        'buscar' => [
            'function aplicar_migracion_arboles_compartidos(string $token): array {',
            '    $res = Controlador::ejecutar_comando(\'app:construir_arboles_compartidos\', [\'dueno\' => \'todos\', \'terminal\' => \'todos\']);',
            '    if (!is_array($res)) {',
            '        return [\'exito\' => false, \'detalles\' => [\'El comando no devolvió un resumen válido.\']];',
            '    }',
            '    if (!empty($res[\'errores\'])) {',
            '        return [\'exito\' => false, \'detalles\' => $res];',
            '    }',
            '    return [\'exito\' => true, \'detalles\' => $res];',
            '}',
            '?>',
        ],
        'reemplazar' => [
            'function aplicar_migracion_arboles_compartidos(string $token): array {',
            '    $res = Controlador::ejecutar_comando(\'app:construir_arboles_compartidos\', [\'dueno\' => \'todos\', \'terminal\' => \'todos\']);',
            '    if (!is_array($res)) {',
            '        return [\'exito\' => false, \'detalles\' => [\'El comando no devolvió un resumen válido.\']];',
            '    }',
            '    if (!empty($res[\'errores\'])) {',
            '        return [\'exito\' => false, \'detalles\' => $res];',
            '    }',
            '    return [\'exito\' => true, \'detalles\' => $res];',
            '}',
            '',
            '// ============================================================',
            '// REPUNTADO DE TERMINALES AL COMPARTIDO (Fase B2.3.5a, v77f)',
            '// ============================================================',
            '',
            '/**',
            ' * ¿Está aplicado el repuntado?',
            ' *',
            ' * Devuelve true si todos los terminales con dueño apuntan al',
            ' * compartido (que tiene `_es_compartido`), o si no hay',
            ' * terminales. Devuelve false si algún terminal con dueño',
            ' * sigue apuntando al nodo del dueño real.',
            ' *',
            ' * @param string $token',
            ' * @return bool',
            ' */',
            'function detectar_repuntado_compartido(string $token): bool {',
            '    $falta_alguno = false;',
            '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$falta_alguno) {',
            '        if ($falta_alguno) return null;',
            '        $id = (string)$nodo->id();',
            '        if (strpos($id, \'us_\') !== 0) return null;',
            '',
            '        $nivel_nodo = $nodo->adyacente(\'nivel\');',
            '        if (!$nivel_nodo) {',
            '            $publico = $nodo->adyacente(\'publico\');',
            '            if ($publico) $nivel_nodo = $publico->adyacente(\'nivel\');',
            '        }',
            '        if (!$nivel_nodo || $nivel_nodo->dato() !== \'terminal\') return null;',
            '',
            '        $nodo_dueno = $nodo->adyacente(\'dueno\');',
            '        if (!$nodo_dueno) return null; // terminal sin dueño: no aplica',
            '',
            '        if (!$nodo_dueno->adyacente(\'_es_compartido\')) {',
            '            $falta_alguno = true;',
            '        }',
            '        return null;',
            '    }, null);',
            '    return !$falta_alguno;',
            '}',
            '',
            '/**',
            ' * Aplica el repuntado.',
            ' *',
            ' * Delega en el comando `app:repuntar_terminales_compartido`.',
            ' *',
            ' * @param string $token',
            ' * @return array{exito: bool, detalles: array}',
            ' */',
            'function aplicar_migracion_repuntado_compartido(string $token): array {',
            '    $res = Controlador::ejecutar_comando(\'app:repuntar_terminales_compartido\', [\'dueno\' => \'todos\', \'terminal\' => \'todos\']);',
            '    if (!is_array($res)) {',
            '        return [\'exito\' => false, \'detalles\' => [\'El comando no devolvió un resumen válido.\']];',
            '    }',
            '    if (!empty($res[\'errores\'])) {',
            '        return [\'exito\' => false, \'detalles\' => $res];',
            '    }',
            '    return [\'exito\' => true, \'detalles\' => $res];',
            '}',
            '?>',
        ],
    ],

    // ============================================================
    // Registro.php — entrada nueva
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Migraciones/Registro.php',
        'descripcion' => 'Registro: entrada repuntado_compartido',
        'buscar' => [
            '        \'arboles_compartidos\' => [',
            '            \'nombre\' => \'Árboles paralelos en compartidos\',',
            '            \'descripcion\' => \'Marca los compartidos y reconstruye sus árboles con nombres parametrizados (hmi_<term>, hd_<term>, p_<term>).\',',
            '            \'detectar\' => \'detectar_arboles_compartidos\',',
            '            \'aplicar\' => \'aplicar_migracion_arboles_compartidos\',',
            '        ],',
            '    ];',
        ],
        'reemplazar' => [
            '        \'arboles_compartidos\' => [',
            '            \'nombre\' => \'Árboles paralelos en compartidos\',',
            '            \'descripcion\' => \'Marca los compartidos y reconstruye sus árboles con nombres parametrizados (hmi_<term>, hd_<term>, p_<term>).\',',
            '            \'detectar\' => \'detectar_arboles_compartidos\',',
            '            \'aplicar\' => \'aplicar_migracion_arboles_compartidos\',',
            '        ],',
            '        \'repuntado_compartido\' => [',
            '            \'nombre\' => \'Repuntado de terminales al compartido\',',
            '            \'descripcion\' => \'Cambia el enlace `dueno` de cada terminal para que apunte al contenedor `compartido_con_us_termX` en vez del nodo del dueño real. Activa el aislamiento por topología.\',',
            '            \'detectar\' => \'detectar_repuntado_compartido\',',
            '            \'aplicar\' => \'aplicar_migracion_repuntado_compartido\',',
            '        ],',
            '    ];',
        ],
    ],

    // ============================================================
    // miscelaneas/repuntar_terminales_compartido.php (nuevo)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'miscelaneas/repuntar_terminales_compartido.php',
        'descripcion' => 'Wrapper de repuntado de terminales al compartido',
        'contenido' => [
            '<?php',
            '/**',
            ' * Migración Fase B2.3.5a: repuntar el enlace `dueno` de cada',
            ' * terminal para que apunte a su contenedor compartido.',
            ' *',
            ' * Delega en el comando `app:repuntar_terminales_compartido`.',
            ' * Idempotente.',
            ' *',
            ' * @since 1.5piloto.77f',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            '',
            '/**',
            ' * Ejecuta la migración y guarda si hubo cambios.',
            ' *',
            ' * @return array Resumen.',
            ' */',
            'function repuntar_terminales_compartido(): array {',
            '    $res = Controlador::ejecutar_comando(',
            '        \'app:repuntar_terminales_compartido\',',
            '        [\'dueno\' => \'todos\', \'terminal\' => \'todos\']',
            '    );',
            '',
            '    if (!is_array($res)) {',
            '        return [',
            '            \'repuntados\' => 0,',
            '            \'ya_repuntados\' => 0,',
            '            \'errores\' => [\'El comando no devolvió un resumen válido.\'],',
            '        ];',
            '    }',
            '',
            '    if ($res[\'repuntados\'] > 0) {',
            '        guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    }',
            '',
            '    return $res;',
            '}',
            '?>',
        ],
    ],

    // ============================================================
    // index.php — bump + bloque ?repuntar_terminales_compartido
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77f',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77e',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77f',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bloque ?repuntar_terminales_compartido',
        'buscar' => [
            '// ==== Migración de usuarios a IDs especiales (v76k) ====',
        ],
        'reemplazar' => [
            '// ==== Repuntado de terminales al compartido (v77f) ====',
            '// Cambia el enlace `dueno` de cada terminal para que',
            '// apunte a su contenedor `compartido_con_us_termX` en vez',
            '// del nodo del dueño real. Activa el aislamiento por',
            '// topología. Idempotente.',
            'if (isset($_GET[\'repuntar_terminales_compartido\'])) {',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    require_once __DIR__ . \'/miscelaneas/repuntar_terminales_compartido.php\';',
            '    $res = repuntar_terminales_compartido();',
            '    echo "Repuntado de terminales al compartido\\n";',
            '    echo "======================================\\n\\n";',
            '    echo "Repuntados:      " . $res[\'repuntados\'] . "\\n";',
            '    echo "Ya repuntados:   " . $res[\'ya_repuntados\'] . "\\n";',
            '    if (!empty($res[\'errores\'])) {',
            '        echo "Errores:\\n";',
            '        foreach ($res[\'errores\'] as $e) echo "  - $e\\n";',
            '    }',
            '    echo "\\nListo.\\n";',
            '    exit;',
            '}',
            '',
            '// ==== Migración de usuarios a IDs especiales (v76k) ====',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77f',
        'buscar' => [
            '**Tanda actual:** v77e (Fase B2.3.4: escrituras de venta',
            'para dos árboles paralelos).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77f (Fase B2.3.5a: migración de repuntado',
            'de terminales al compartido).',
            '',
            '**v77f — Fase B2.3.5a.**',
            '',
            'Comando `app:repuntar_terminales_compartido`. Para cada',
            'dueño y cada compartido `compartido_con_us_termX`:',
            '',
            '1. Marca el compartido con `dato = nombre_dueno` (opción A).',
            '2. Repunta el enlace `dueno` del terminal `us_termX` para',
            '   que apunte al compartido en vez del nodo del dueño real.',
            '',
            'Idempotente. Bloque `?repuntar_terminales_compartido=1` en',
            '`index.php`. **NO toca el enrutador ni el código de flujo.**',
            'La app sigue funcionando igual porque el código ya está',
            'preparado (B2.2 + B2.3.4). El enrutador empieza a pasar',
            'contexto en B2.3.5b.',
            '',
            '**Por qué se parte B2.3.5 en a y b.** B2.3.5a es de bajo',
            'riesgo: solo cambia el destino del enlace `dueno` en el',
            'grafo. Se puede probar end-to-end antes de tocar el',
            'enrutador. Si algo se rompe, se revierte manualmente',
            'repuntando el enlace al nodo del dueño real (o borrando el',
            'testigo del comando y volviendo a correrlo desde el nodo',
            'original). B2.3.5b es de alto riesgo: toca el enrutador, el',
            'corazón del flujo.',
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