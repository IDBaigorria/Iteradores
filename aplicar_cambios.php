<?php
/**
 * Aplicador de cambios automáticos — Framework Iteradores (PHP).
 *
 * Tanda V1.5i.7d:
 * - Reescritura de Pruebas/prueba_deposito.php con un test bien
 *   diseñado: verifica directamente que vaciar_superestructura
 *   limpia el depósito de IDs, sin pasar por cargar (que reinserta
 *   el ID y da un falso positivo).
 * - PerdurarSuperestructuraStringSQL.php: crear_chunks_insertar_adyacentes
 *   usa `adyacentes()` en lugar de `por_cada_adyacente_ejecutar` para
 *   no emitir una alerta por cada nodo sin adyacentes (alineación con
 *   el espejo JS).
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

    // ============================================================
    // Pruebas/prueba_deposito.php (reescribir)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'Pruebas/prueba_deposito.php',
        'descripcion' => 'Reescritura del test del depósito de IDs',
        'contenido' => [
            '<?php',
            '/**',
            ' * Prueba del depósito de IDs del framework Iteradores (PHP).',
            ' *',
            ' * Verifica que al vaciar la superestructura el depósito de IDs',
            ' * especiales se limpia correctamente, permitiendo volver a crear',
            ' * nodos con los mismos IDs especiales.',
            ' *',
            ' * Este test NO usa Controlador::cargar porque cargar reinserta los',
            ' * IDs especiales en el depósito (los recrea). Un test que intente',
            ' * crear el mismo ID después de cargar da un falso positivo: el',
            ' * error "Ya existe ese id" es el comportamiento correcto.',
            ' *',
            ' * En cambio, se verifica que:',
            ' *   1. Crear un ID especial funciona.',
            ' *   2. Existe el nodo correspondiente.',
            ' *   3. Al vaciar la superestructura, el nodo desaparece.',
            ' *   4. Se puede volver a crear el mismo ID especial.',
            ' *',
            ' * Se ejecuta como bloque temporal desde index.php:',
            ' *   http://localhost/.../index.php?probar_deposito=1',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5i.7d',
            ' */',
            '',
            'require_once __DIR__ . \'/../Controlador/Controlador.php\';',
            'require_once __DIR__ . \'/../Configuracion/Configuracion.php\';',
            'require_once __DIR__ . \'/../Nodos/Nodo.php\';',
            'require_once __DIR__ . \'/../Nucleo/Objeto.php\';',
            '',
            'use Iteradores\\Controlador\\Controlador;',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Nucleo\\Objeto;',
            '',
            'header(\'Content-Type: text/plain; charset=utf-8\');',
            '',
            'echo "=== PRUEBA DEL DEPOSITO DE IDS (PHP) ===\\n\\n";',
            '',
            '$id_prueba = \'test_especial_deposito\';',
            '',
            'Controlador::ejecutar_prueba(function ($token) use ($id_prueba) {',
            '',
            '    // 1. Crear un nodo especial.',
            '    $n1 = Nodo::crear_con_id($id_prueba);',
            '    echo "1. Crear \'{$id_prueba}\' (1ra vez): " . ($n1 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '    // 2. Verificar que existe.',
            '    $existe1 = Nodo::nodo_por_id($id_prueba);',
            '    echo "2. El nodo existe: " . ($existe1 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '    // 3. Vaciar la superestructura.',
            '    $vaciado = Nodo::vaciar_superestructura($token);',
            '    echo "3. Vaciar superestructura: " . ($vaciado ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '    // 4. Verificar que el nodo ya no existe.',
            '    $existe2 = Nodo::nodo_por_id($id_prueba);',
            '    echo "4. El nodo ya no existe: " . (!$existe2 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '    // 5. Crear el mismo id especial de nuevo.',
            '    $n2 = Nodo::crear_con_id($id_prueba);',
            '    echo "5. Crear \'{$id_prueba}\' (2da vez tras vaciar): " . ($n2 ? \'OK\' : \'FALLO\') . "\\n";',
            '',
            '    echo "\\n=== RESULTADO ===\\n";',
            '    if ($n2) {',
            '        echo "SIN BUG: vaciar_superestructura limpia el deposito de IDs.\\n";',
            '    } else {',
            '        echo "BUG PRESENTE: el deposito NO se limpio al vaciar.\\n";',
            '        echo "El id \'{$id_prueba}\' sigue en Objeto::\\$deposito_de_ids.\\n";',
            '        echo "\\nErrores:\\n";',
            '        echo Objeto::json_errores() . "\\n";',
            '    }',
            '    echo "\\n=== FIN DE LA PRUEBA ===\\n";',
            '});',
        ],
    ],

    // ============================================================
    // Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'Bump de version a 1.5i.7d',
        'buscar' => [
            ' * @version 1.5i.7a',
        ],
        'reemplazar' => [
            ' * @version 1.5i.7d',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'crear_chunks_insertar_adyacentes sin alertas',
        'buscar' => [
            '	static private function crear_chunks_insertar_adyacentes($sql, $nombre): array',
            '	{',
            '		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {',
            '			return $nodo->por_cada_adyacente_ejecutar(function ($ady) {',
            '				return $ady->id();',
            '			});',
            '		});',
            '',
            '		if (empty($datos)) return [];',
        ],
        'reemplazar' => [
            '	static private function crear_chunks_insertar_adyacentes($sql, $nombre): array',
            '	{',
            '		$datos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {',
            '			$enlaces = [];',
            '			// Usamos adyacentes() en lugar de por_cada_adyacente_ejecutar',
            '			// porque el primero devuelve [] sin alerta cuando el nodo no',
            '			// tiene adyacentes. El segundo emite una alerta por cada nodo',
            '			// sin adyacentes, lo que llena la lista de alertas con ruido',
            '			// (alineado con el espejo JS desde V1.5i.7).',
            '			$ady = $nodo->adyacentes();',
            '			if (is_array($ady)) {',
            '				foreach ($ady as $enlace => $adyacente) {',
            '					$enlaces[$enlace] = $adyacente->id();',
            '				}',
            '			}',
            '			return $enlaces;',
            '		});',
            '',
            '		if (empty($datos)) return [];',
        ],
    ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework historial: agregar 1.5i.7d',
        'buscar' => [
            '- **1.5i.7c**: sin cambios funcionales al framework PHP. Se agrega',
            '  `Pruebas/prueba_deposito.php` para verificar que el depósito de',
            '  IDs se limpia correctamente al vaciar la superestructura. Se',
            '  documenta el espejo JS en la sección 12 de este prompt.',
        ],
        'reemplazar' => [
            '- **1.5i.7c**: sin cambios funcionales al framework PHP. Se agrega',
            '  `Pruebas/prueba_deposito.php` para verificar que el depósito de',
            '  IDs se limpia correctamente al vaciar la superestructura. Se',
            '  documenta el espejo JS en la sección 12 de este prompt.',
            '- **1.5i.7d**: `PerdurarSuperestructuraStringSQL::crear_chunks_insertar_adyacentes`',
            '  usa `adyacentes()` en lugar de `por_cada_adyacente_ejecutar`, para',
            '  no emitir una alerta por cada nodo sin adyacentes (alineado con el',
            '  espejo JS). Reescritura de `Pruebas/prueba_deposito.php` con un',
            '  test bien diseñado: el anterior daba un falso positivo porque',
            '  intentaba crear el mismo ID especial después de `cargar` (que',
            '  reinserta el ID al recrear el nodo). Ahora verifica directamente',
            '  `vaciar_superestructura`.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto historial: agregar v73p',
        'buscar' => [
            '- **v73o**: script de prueba del depósito de IDs en',
            '  `Pruebas/prueba_deposito.php`, ejecutable con `?probar_deposito=1`',
            '  desde `index.php`. Confirma que el framework PHP NO tiene el bug',
            '  de limpieza de IDs que sí existió en el espejo JS hasta 1.5i.6.',
            '  De paso, se documenta el espejo JS en el prompt del framework',
            '  (sección 12).',
        ],
        'reemplazar' => [
            '- **v73o**: script de prueba del depósito de IDs en',
            '  `Pruebas/prueba_deposito.php`, ejecutable con `?probar_deposito=1`',
            '  desde `index.php`. Confirma que el framework PHP NO tiene el bug',
            '  de limpieza de IDs que sí existió en el espejo JS hasta 1.5i.6.',
            '  De paso, se documenta el espejo JS en el prompt del framework',
            '  (sección 12).',
            '- **v73p**: reescritura del test del depósito de IDs. El anterior',
            '  daba falso positivo porque intentaba recrear el ID después de',
            '  `cargar` (que reinserta el ID al recrear el nodo). El nuevo test',
            '  verifica directamente `vaciar_superestructura`. Se alineó',
            '  `crear_chunks_insertar_adyacentes` con el espejo JS para no',
            '  emitir alertas por cada nodo sin adyacentes.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Discusion actual: bump a v73p',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73o (script de',
            'prueba del depósito de IDs en `Pruebas/prueba_deposito.php`;',
            'prompts actualizados para reflejar el espejo JS del framework).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73p (reescritura',
            'del test del depósito de IDs; alineación de las alertas de guardado',
            'SQL con el espejo JS).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Estado de la conversacion: agregar v73p',
        'buscar' => [
            '- Cerramos en v73o el script de prueba del depósito de IDs en',
            '  `Pruebas/` y la documentación del espejo JS en el prompt del',
            '  framework.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73o el script de prueba del depósito de IDs en',
            '  `Pruebas/` y la documentación del espejo JS en el prompt del',
            '  framework.',
            '- Cerramos en v73p la reescritura del test (el anterior daba falso',
            '  positivo) y la alineación de `crear_chunks_insertar_adyacentes`',
            '  con el espejo JS.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto estado al cierre: bump a v73p',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73o (framework 1.5i.7c).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73p (framework 1.5i.7d).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
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