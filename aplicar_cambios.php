<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76r (Fase B2.2.1 del modelo topológico).
 * Corrección: el bloque "Última actualización" del prompt del
 * piloto quedó en v76n (mis scripts v76o/v76p/v76q no lo tocaron).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Viaje.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: bump @version a 1.5piloto.76r',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76a',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76r',
        ],
    ],

    // ============================================================
    // Viaje.php — helper _contexto_terminal + context opcional
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: helper _contexto_terminal + context opcional',
        'buscar' => [
            '/**',
            ' * Obtiene el contenedor de viajes de un dueño, creándolo si no existe.',
            ' */',
            'function obtener_contenedor_viajes_dueno(string $nombre_dueno) {',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return null;',
            '',
            '    $nodo_viajes = $nodo_dueno->adyacente(\'viajes\');',
            '    if (!$nodo_viajes) {',
            '        $nodo_viajes = Nodo::crear_con_dato(\'\');',
            '        $nodo_dueno->_adyacente_en($nodo_viajes, \'viajes\');',
            '    }',
            '    return $nodo_viajes;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Devuelve el nodo desde el que un terminal debe navegar',
            ' * para acceder al subgrafo de su dueño.',
            ' *',
            ' * A partir de v76r (Fase B2.2.1 del modelo topológico).',
            ' * Antes de la Fase B2.3 (repuntado), el enlace `dueno` del',
            ' * terminal apunta al nodo real del dueño. Después del',
            ' * repuntado, apuntará al contenedor `compartido_con_<terminal>`.',
            ' *',
            ' * En ambos casos, el nodo devuelto tiene el mismo dato',
            ' * (nombre del dueño) y los mismos sub-contenedores (`viajes`,',
            ' * `empresas`, `ventas`, `pasajeros`, `cancelaciones`,',
            ' * `terminales`), así el código del terminal puede navegar',
            ' * por `adyacente()` sin saber si está viendo el grafo',
            ' * completo o solo lo compartido.',
            ' *',
            ' * @param string $nombre_terminal',
            ' * @return Nodo|null',
            ' */',
            'function _contexto_terminal(string $nombre_terminal) {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return null;',
            '    $nodo_terminal = $raiz->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal) return null;',
            '    return $nodo_terminal->adyacente(\'dueno\');',
            '}',
            '',
            '/**',
            ' * Obtiene el contenedor de viajes de un dueño, creándolo si no existe.',
            ' *',
            ' * A partir de v76r (Fase B2.2.1 del modelo topológico): acepta',
            ' * un `?Nodo $nodo_contexto` opcional. Si viene, navega desde',
            ' * ahí en lugar de resolver `usuarios → dueño`. Es el mecanismo',
            ' * por el que un terminal accede a sus viajes compartidos sin',
            ' * pasar por la raíz `usuarios`.',
            ' *',
            ' * @param string    $nombre_dueno',
            ' * @param Nodo|null $nodo_contexto',
            ' * @return Nodo|null',
            ' */',
            'function obtener_contenedor_viajes_dueno(string $nombre_dueno, ?Nodo $nodo_contexto = null) {',
            '    if ($nodo_contexto === null) {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) return null;',
            '',
            '        $nodo_contexto = $raiz_usuarios->adyacente($nombre_dueno);',
            '    }',
            '    if (!$nodo_contexto) return null;',
            '',
            '    $nodo_viajes = $nodo_contexto->adyacente(\'viajes\');',
            '    if (!$nodo_viajes) {',
            '        $nodo_viajes = Nodo::crear_con_dato(\'\');',
            '        $nodo_contexto->_adyacente_en($nodo_viajes, \'viajes\');',
            '    }',
            '    return $nodo_viajes;',
            '}',
        ],
    ],

    // ============================================================
    // Viaje.php — listar_viajes_de_terminal usa el contexto
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: listar_viajes_de_terminal usa contexto',
        'buscar' => [
            '    $nombre_dueno = $nodo_dueno->dato();',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [];',
        ],
        'reemplazar' => [
            '    $nombre_dueno = $nodo_dueno->dato();',
            '',
            '    // Fase B2.2.1 del modelo topológico: pasar el nodo del',
            '    // dueño (o, tras el repuntado de B2.3, el compartido del',
            '    // terminal) como contexto, en lugar de resolver',
            '    // `usuarios → dueño` otra vez. Así el terminal navega por',
            '    // el subgrafo correcto sin depender de la raíz global.',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno, $nodo_dueno);',
            '    if (!$nodo_viajes) return [];',
        ],
    ],

    // ============================================================
    // index.php — bump de versión
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76r',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76q',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76r',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — "Última actualización"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76n',
            '(pestaña Grafo: sección "Nodos raíz" y sistema de',
            'migraciones. Nuevo comando `grafo:raices` en el framework',
            '(lista los IDs especiales con sus adyacentes). Nuevo nodo',
            'especial `aplicacion` con contenedor `migraciones`; cada',
            'migración aplicada deja un enlace testigo autoreferente.',
            'Nuevo módulo `Aplicacion/Migraciones/` con el registro de',
            'migraciones, sus funciones de detección y aplicación, y los',
            'comandos `app:migracion_*`. La pestaña Grafo permite',
            'aplicar migraciones desde la UI.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76r',
            '(Fase B2.2.1 del modelo topológico: `obtener_contenedor_viajes_dueno`',
            'acepta un `?Nodo $nodo_contexto` opcional, y',
            '`listar_viajes_de_terminal` le pasa el nodo del dueño como',
            'contexto. Nuevo helper `_contexto_terminal` en `Viaje.php`.',
            'Refactor sin cambio de comportamiento: el código del terminal',
            'sigue navegando por el nodo del dueño, pero ya con el',
            'mecanismo listo para B2.3 (repuntado).).',
            'Antes: v1.5piloto.76n',
            '(pestaña Grafo: sección "Nodos raíz" y sistema de',
            'migraciones. Nuevo comando `grafo:raices` en el framework',
            '(lista los IDs especiales con sus adyacentes). Nuevo nodo',
            'especial `aplicacion` con contenedor `migraciones`; cada',
            'migración aplicada deja un enlace testigo autoreferente.',
            'Nuevo módulo `Aplicacion/Migraciones/` con el registro de',
            'migraciones, sus funciones de detección y aplicación, y los',
            'comandos `app:migracion_*`. La pestaña Grafo permite',
            'aplicar migraciones desde la UI.).',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76r',
        'buscar' => [
            '- **v76q**: Fase B2.1 del modelo topológico. Crea los',
        ],
        'reemplazar' => [
            '- **v76r**: Fase B2.2.1 del modelo topológico. Refactor',
            '  sin cambio de comportamiento: `obtener_contenedor_viajes_dueno`',
            '  acepta un `?Nodo $nodo_contexto` opcional. Si viene, navega',
            '  desde ahí en lugar de resolver `usuarios → dueño` otra vez.',
            '  `listar_viajes_de_terminal` le pasa `$nodo_dueno` como',
            '  contexto. Nuevo helper `_contexto_terminal($nombre_terminal)`',
            '  en `Viaje.php`: devuelve el nodo desde el que un terminal',
            '  debe navegar (hoy el nodo dueño, tras B2.3 el compartido).',
            '  El comportamiento es idéntico al actual; el cambio prepara',
            '  el terreno para B2.3.',
            '- **v76q**: Fase B2.1 del modelo topológico. Crea los',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §12 bullet
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet v76r',
        'buscar' => [
            '- Cerramos en v76q la Fase B2.1 del modelo topológico.',
        ],
        'reemplazar' => [
            '- Cerramos en v76r la Fase B2.2.1 del modelo topológico.',
            '  Refactor sin cambio de comportamiento: `obtener_contenedor_viajes_dueno`',
            '  acepta un `?Nodo $nodo_contexto` opcional. Nuevo helper',
            '  `_contexto_terminal($nombre_terminal)` en `Viaje.php`.',
            '  `listar_viajes_de_terminal` le pasa el nodo del dueño como',
            '  contexto. La idea es preparar el terreno para B2.3: cuando',
            '  el enlace `us_termX → dueno` apunte al compartido, el código',
            '  del terminal ya navega por el nodo correcto sin cambios.',
            '  Pendientes de B2.2: B2.2.2 (Venta.php), B2.2.3',
            '  (ViajeAsientos.php), B2.2.4 (Empresa.php).',
            '- Cerramos en v76q la Fase B2.1 del modelo topológico.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — §13 estado
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado al cierre a v76r',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76q (framework 1.5i.7l).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76r (framework 1.5i.7l).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76r al bloque de estado',
        'buscar' => [
            'v76q: Fase B2.1. Compartidos por terminal creados como',
        ],
        'reemplazar' => [
            'v76r: Fase B2.2.1. Contexto opcional en',
            '`obtener_contenedor_viajes_dueno` y uso desde',
            '`listar_viajes_de_terminal`. Nuevo helper',
            '`_contexto_terminal`. Refactor sin cambio de comportamiento.',
            'v76q: Fase B2.1. Compartidos por terminal creados como',
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