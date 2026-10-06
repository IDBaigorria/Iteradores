<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP (framework + piloto).
 *
 * Tanda V1.5piloto.76f:
 *   - Helper `en_grafo_credenciales_solo_lectura` para leer el
 *     grafo de credenciales sin guardarlo.
 *   - Nueva subacción `grafo/resumen_credenciales` (solo en modo
 *     pruebas, admin o soporte). Devuelve el mismo resumen que
 *     `grafo/resumen` pero del grafo de credenciales.
 *   - Documentación en el prompt del piloto.
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

    // ------------------------------------------------------------
    // Aplicacion/GrafoCredenciales.php
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/GrafoCredenciales.php',
        'descripcion' => 'GrafoCredenciales: bump @version',
        'buscar' => [
            ' * @package   Iteradores',
            ' * @since     1.5piloto.70',
            ' */',
        ],
        'reemplazar' => [
            ' * @package   Iteradores',
            ' * @since     1.5piloto.70',
            ' * @version   1.5piloto.76f',
            ' */',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/GrafoCredenciales.php',
        'descripcion' => 'GrafoCredenciales: agregar helper solo_lectura',
        'buscar' => [
            '        if (!$ok_carga) {',
            '            Controlador::_error("en_grafo_credenciales: no se pudo recargar la app \"" . ConfiguracionApli::NOMBRE_APP . "\".");',
            '            throw new \RuntimeException("No se pudo recargar el grafo de la aplicacion tras operar en credenciales.");',
            '        }',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '        if (!$ok_carga) {',
            '            Controlador::_error("en_grafo_credenciales: no se pudo recargar la app \"" . ConfiguracionApli::NOMBRE_APP . "\".");',
            '            throw new \RuntimeException("No se pudo recargar el grafo de la aplicacion tras operar en credenciales.");',
            '        }',
            '    }',
            '}',
            '',
            '/**',
            ' * Ejecuta un callback dentro del grafo de credenciales, en modo',
            ' * solo lectura.',
            ' *',
            ' * Diferencia con en_grafo_credenciales:',
            ' * - No guarda la app antes de cargar credenciales.',
            ' * - No guarda credenciales al terminar.',
            ' * - Recarga la app al final para dejar el estado como estaba.',
            ' *',
            ' * Pensado para operaciones de diagnóstico (por ejemplo, contar',
            ' * huérfanos) que no modifican el grafo. Es más rápido que el',
            ' * helper completo porque evita la transacción de guardado.',
            ' *',
            ' * Si el callback lanza una excepción, se loguea como error y',
            ' * se devuelve null (no se relanza: es solo lectura).',
            ' *',
            ' * @param callable $fn Callback a ejecutar en el contexto de credenciales.',
            ' * @return mixed El valor devuelto por el callback, o null si hubo error.',
            ' * @since 1.5piloto.76f',
            ' */',
            'function en_grafo_credenciales_solo_lectura(callable $fn) {',
            '    if (!empty($GLOBALS[\'__en_grafo_credenciales\'])) {',
            '        return $fn();',
            '    }',
            '    $GLOBALS[\'__en_grafo_credenciales\'] = true;',
            '    try {',
            '        // Crear credenciales si no existe (raro pero posible).',
            '        if (!Controlador::existe(ConfiguracionApli::NOMBRE_APP_CREDENCIALES)) {',
            '            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);',
            '            if (!Nodo::nodo_por_id(\'usuarios\')) {',
            '                Nodo::crear_con_id(\'usuarios\');',
            '            }',
            '            if (!Nodo::nodo_por_id(\'sesiones\')) {',
            '                Nodo::crear_con_id(\'sesiones\');',
            '            }',
            '            // No guardamos: solo lectura.',
            '        } else {',
            '            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);',
            '        }',
            '',
            '        $resultado = null;',
            '        try {',
            '            $resultado = $fn();',
            '        } catch (\Throwable $e) {',
            '            Controlador::_error("en_grafo_credenciales_solo_lectura: " . $e->getMessage());',
            '        }',
            '        return $resultado;',
            '    } finally {',
            '        // Recargar la app. Si falla, es un error fatal: la',
            '        // superestructura quedaría vacía y el próximo guardado',
            '        // podría pisar el grafo.',
            '        $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);',
            '        $GLOBALS[\'__en_grafo_credenciales\'] = false;',
            '        if (!$ok_carga) {',
            '            Controlador::_error("en_grafo_credenciales_solo_lectura: no se pudo recargar la app \"" . ConfiguracionApli::NOMBRE_APP . "\".");',
            '            throw new \RuntimeException("No se pudo recargar el grafo de la aplicacion tras leer credenciales.");',
            '        }',
            '    }',
            '}',
        ],
    ],

    // ------------------------------------------------------------
    // Aplicacion/Enrutador.php
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: bump @version',
        'buscar' => [' * @version   1.5piloto.74p'],
        'reemplazar' => [' * @version   1.5piloto.76f'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar grafo/resumen_credenciales',
        'buscar' => [
            '                case \'resumen\':',
            '                    $resumen_gr = Controlador::ejecutar_comando(\'grafo:resumen\');',
            '                    responder_json([\'exito\' => true, \'resumen\' => $resumen_gr]);',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'resumen\':',
            '                    $resumen_gr = Controlador::ejecutar_comando(\'grafo:resumen\');',
            '                    responder_json([\'exito\' => true, \'resumen\' => $resumen_gr]);',
            '                    break;',
            '',
            '                case \'resumen_credenciales\':',
            '                    // Solo en modo pruebas. El chequeo es del lado',
            '                    // del servidor: en producción el endpoint no',
            '                    // ejecuta nada, sin importar quién lo llame.',
            '                    // Además hereda el chequeo de nivel del módulo',
            '                    // `grafo` (admin o soporte).',
            '                    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Disponible solo en modo pruebas\']);',
            '                    }',
            '                    $resumen_cred = en_grafo_credenciales_solo_lectura(function() {',
            '                        return Controlador::ejecutar_comando(\'grafo:resumen\');',
            '                    });',
            '                    if ($resumen_cred === null) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'No se pudo cargar el grafo de credenciales\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'resumen\' => $resumen_cred]);',
            '                    break;',
        ],
    ],

    // ------------------------------------------------------------
    // prompts/prompt_piloto.md
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Prompt piloto: documentar resumen_credenciales',
        'buscar' => [
            '- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin',
            '  permisos. Lo consume el frontend para saber si mostrar los',
            '  botones de limpieza.',
        ],
        'reemplazar' => [
            '- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin',
            '  permisos. Lo consume el frontend para saber si mostrar los',
            '  botones de limpieza.',
            '- `grafo/resumen_credenciales`: **solo en modo pruebas**, admin o',
            '  soporte. Devuelve el mismo resumen que `grafo/resumen` pero',
            '  sobre el grafo de credenciales. Usa',
            '  `en_grafo_credenciales_solo_lectura` (no guarda). Lo consume',
            '  el plugin en la prueba de rate limiting.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Prompt piloto: historial v76f',
        'buscar' => [
            '- **v76d**: solo documentación. Se escribe completa la',
        ],
        'reemplazar' => [
            '- **v76f**: nuevo helper `en_grafo_credenciales_solo_lectura`',
            '  (lee el grafo de credenciales sin guardarlo) y nueva',
            '  subacción `grafo/resumen_credenciales` en el enrutador',
            '  (solo en modo pruebas, admin o soporte). Permite al plugin',
            '  medir huérfanos del grafo de credenciales.',
            '- **v76d**: solo documentación. Se escribe completa la',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Prompt piloto: última actualización a v76f',
        'buscar' => ['**Última actualización de este prompt:** v1.5piloto.76d'],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76f',
            '(nuevo helper `en_grafo_credenciales_solo_lectura` +',
            'subacción `grafo/resumen_credenciales` en el enrutador,',
            'solo en modo pruebas. Permite al plugin medir huérfanos',
            'del grafo de credenciales.).',
            'Antes: v1.5piloto.76d',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Prompt piloto: estado del proyecto a v76f',
        'buscar' => ['**Estado del proyecto al cierre:** v1.5piloto.76d (framework 1.5i.7g).'],
        'reemplazar' => ['**Estado del proyecto al cierre:** v1.5piloto.76f (framework 1.5i.7h).'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Prompt piloto: agregar v76f al cierre',
        'buscar' => [
            'v76d: §8.6.1 completado (documentación).',
        ],
        'reemplazar' => [
            'v76d: §8.6.1 completado (documentación).',
            'v76f: helper de solo lectura para credenciales + endpoint',
            '`grafo/resumen_credenciales` (solo en modo pruebas).',
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
        $es_todos = !empty($cambio['todos']);

        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if (!$es_todos && $ocurrencias > 1) {
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