<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.76a (Fase 3 del plan de optimización del grafo):
 *   - Índice precalculado de ventas por viaje.
 *   - formatear_viaje acepta un 4to parámetro opcional.
 *   - listar_viajes_de_dueno y listar_viajes_de_terminal
 *     construyen el índice UNA VEZ antes del bucle de viajes.
 *   - Helper _calcular_vendidos_por_micro_de_viaje para el fallback.
 *   - Nuevo helper _construir_indice_ventas_por_viaje.
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
    // Viaje.php
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump @version a 1.5piloto.76a',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.75a',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'listar_viajes_de_dueno: construir y pasar el índice',
        'buscar' => [
            'function listar_viajes_de_dueno(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [];',
            '',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    if (!$adyacentes) return [];',
            '',
            '    $viajes = [];',
            '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
            '        $viajes[] = formatear_viaje($nombre_viaje, $nodo_viaje);',
            '    }',
            '    return $viajes;',
            '}',
        ],
        'reemplazar' => [
            'function listar_viajes_de_dueno(string $nombre_dueno): array {',
            '    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);',
            '    if (!$nodo_viajes) return [];',
            '',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    if (!$adyacentes) return [];',
            '',
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ. Antes, formatear_viaje recorría todo el contenedor',
            '    // de ventas del dueño por cada viaje (O(V × W)).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno);',
            '',
            '    $viajes = [];',
            '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
            '        $viajes[] = formatear_viaje($nombre_viaje, $nodo_viaje, null, $indice_ventas);',
            '    }',
            '    return $viajes;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'listar_viajes_de_terminal: construir y pasar el índice',
        'buscar' => [
            '    $viajes_autorizados = [];',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
            '        $viaje = formatear_viaje($nombre_viaje, $nodo_viaje, $nombre_terminal); ',
            '        if (in_array($nombre_terminal, $viaje[\'terminales_autorizadas\'])) {',
            '            $viajes_autorizados[] = $viaje;',
            '        }',
            '    }',
            '    return $viajes_autorizados;',
            '}',
        ],
        'reemplazar' => [
            '    // Fase 3, v76a: precalcular el índice de ventas por viaje',
            '    // UNA VEZ, con el terminal como filtro. Antes, formatear_viaje',
            '    // recorría todo el contenedor de ventas del dueño por cada',
            '    // viaje (O(V × W)).',
            '    $indice_ventas = _construir_indice_ventas_por_viaje($nombre_dueno, $nombre_terminal);',
            '',
            '    $viajes_autorizados = [];',
            '    $adyacentes = (array) $nodo_viajes->adyacentes();',
            '    foreach ($adyacentes as $nombre_viaje => $nodo_viaje) {',
            '        $viaje = formatear_viaje($nombre_viaje, $nodo_viaje, $nombre_terminal, $indice_ventas);',
            '        if (in_array($nombre_terminal, $viaje[\'terminales_autorizadas\'])) {',
            '            $viajes_autorizados[] = $viaje;',
            '        }',
            '    }',
            '    return $viajes_autorizados;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'formatear_viaje: agregar 4to parámetro',
        'buscar' => [
            'function formatear_viaje(string $nombre_viaje, $nodo_viaje, ?string $nombre_terminal = null): array {',
        ],
        'reemplazar' => [
            'function formatear_viaje(string $nombre_viaje, $nodo_viaje, ?string $nombre_terminal = null, ?array $indice_ventas = null): array {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'formatear_viaje: usar el índice para tiene_ventas',
        'buscar' => [
            '    // Verificar si tiene ventas',
            '    $datos[\'tiene_ventas\'] = viaje_tiene_ventas($datos[\'dueno\'], $nombre_viaje) ? \'1\' : \'0\';',
        ],
        'reemplazar' => [
            '    // Verificar si tiene ventas.',
            '    // Fase 3, v76a: si viene el índice precalculado, usarlo.',
            '    // Si no, computar al vuelo (fallback para llamadores que no',
            '    // pasan el índice, como los llamados puntuales).',
            '    if ($indice_ventas !== null) {',
            '        $tiene_ventas_bool = !empty($indice_ventas[\'tiene_ventas\'][$nombre_viaje]);',
            '    } else {',
            '        $tiene_ventas_bool = viaje_tiene_ventas($datos[\'dueno\'], $nombre_viaje);',
            '    }',
            '    $datos[\'tiene_ventas\'] = $tiene_ventas_bool ? \'1\' : \'0\';',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'formatear_viaje: usar el índice para vendidos_por_micro',
        'buscar' => [
            '    // Calcular ventas por micro de la terminal actual (si corresponde)',
            '    $vendidos_por_micro = [];',
            '    if ($nombre_terminal !== null) {',
            '        $contenedor_ventas = obtener_contenedor_ventas_dueno($datos[\'dueno\']);',
            '        if ($contenedor_ventas) {',
            '            $venta_iter = hmi($contenedor_ventas);',
            '            while ($venta_iter) {',
            '                $nodo_terminal_venta = $venta_iter->adyacente(\'terminal\');',
            '                $nodo_micro_venta = $venta_iter->adyacente(\'micro\');',
            '                $nodo_viaje_venta = $venta_iter->adyacente(\'viaje\');',
            '',
            '                if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal',
            '                    && $nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje',
            '                    && $nodo_micro_venta) {',
            '                    $micro_id = $nodo_micro_venta->id();',
            '                    $cabeza = $venta_iter->adyacente(\'asientos\');',
            '                    $cantidad = 0;',
            '                    if ($cabeza) {',
            '                        $asiento = $cabeza->adyacente(\'primer\');',
            '                        $seg = 0;',
            '                        while ($asiento && $seg < 100) {',
            '                            $cantidad++;',
            '                            $asiento = $asiento->adyacente(\'siguiente\');',
            '                            $seg++;',
            '                        }',
            '                    }',
            '                    $vendidos_por_micro[$micro_id] = ($vendidos_por_micro[$micro_id] ?? 0) + $cantidad;',
            '                }',
            '                $venta_iter = hd($venta_iter);',
            '            }',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    // Calcular ventas por micro de la terminal actual (si corresponde).',
            '    // Fase 3, v76a: si viene el índice precalculado, usarlo.',
            '    // Si no, computar al vuelo (fallback).',
            '    $vendidos_por_micro = [];',
            '    if ($nombre_terminal !== null) {',
            '        if ($indice_ventas !== null) {',
            '            $vendidos_por_micro = $indice_ventas[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] ?? [];',
            '        } else {',
            '            $vendidos_por_micro = _calcular_vendidos_por_micro_de_viaje($datos[\'dueno\'], $nombre_viaje, $nombre_terminal);',
            '        }',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Agregar los dos helpers de Fase 3 después de viaje_tiene_ventas',
        'buscar' => [
            '    $actual = hmi($contenedor_ventas);',
            '    while ($actual) {',
            '        $nodo_viaje_venta = $actual->adyacente(\'viaje\');',
            '        if ($nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje) {',
            '            return true;',
            '        }',
            '        $actual = hd($actual);',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Guarda la lista de paradas intermedias en el nodo viaje.',
        ],
        'reemplazar' => [
            '    $actual = hmi($contenedor_ventas);',
            '    while ($actual) {',
            '        $nodo_viaje_venta = $actual->adyacente(\'viaje\');',
            '        if ($nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje) {',
            '            return true;',
            '        }',
            '        $actual = hd($actual);',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Calcula las ventas por micro de una terminal para un viaje.',
            ' *',
            ' * Recorre todo el contenedor de ventas del dueño y devuelve un',
            ' * mapa micro_id => cantidad de asientos vendidos por esa terminal',
            ' * en ese viaje. Solo cuenta las ventas cuya terminal y viaje',
            ' * coinciden.',
            ' *',
            ' * Es el cómputo original que hacía formatear_viaje inline. Se',
            ' * extrajo a un helper en v76a (Fase 3) para que el fallback',
            ' * (cuando formatear_viaje se llama sin índice precalculado)',
            ' * use el mismo código que la variante con índice.',
            ' *',
            ' * @param string $nombre_dueno',
            ' * @param string $nombre_viaje',
            ' * @param string $nombre_terminal',
            ' * @return array<int|string, int> micro_id => cantidad',
            ' */',
            'function _calcular_vendidos_por_micro_de_viaje(string $nombre_dueno, string $nombre_viaje, string $nombre_terminal): array {',
            '    $vendidos_por_micro = [];',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor_ventas) return $vendidos_por_micro;',
            '    $venta_iter = hmi($contenedor_ventas);',
            '    $seg = 0;',
            '    while ($venta_iter && $seg < 2000) {',
            '        $nodo_terminal_venta = $venta_iter->adyacente(\'terminal\');',
            '        $nodo_micro_venta = $venta_iter->adyacente(\'micro\');',
            '        $nodo_viaje_venta = $venta_iter->adyacente(\'viaje\');',
            '',
            '        if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal',
            '            && $nodo_viaje_venta && $nodo_viaje_venta->dato() === $nombre_viaje',
            '            && $nodo_micro_venta) {',
            '            $micro_id = $nodo_micro_venta->id();',
            '            $cabeza = $venta_iter->adyacente(\'asientos\');',
            '            $cantidad = 0;',
            '            if ($cabeza) {',
            '                $asiento = $cabeza->adyacente(\'primer\');',
            '                $seg2 = 0;',
            '                while ($asiento && $seg2 < 100) {',
            '                    $cantidad++;',
            '                    $asiento = $asiento->adyacente(\'siguiente\');',
            '                    $seg2++;',
            '                }',
            '            }',
            '            $vendidos_por_micro[$micro_id] = ($vendidos_por_micro[$micro_id] ?? 0) + $cantidad;',
            '        }',
            '        $venta_iter = hd($venta_iter);',
            '        $seg++;',
            '    }',
            '    return $vendidos_por_micro;',
            '}',
            '',
            '/**',
            ' * Construye el índice de ventas por viaje de un dueño,',
            ' * recorriendo el contenedor de ventas UNA SOLA VEZ.',
            ' *',
            ' * Devuelve un array con dos partes:',
            ' *',
            ' *   [',
            ' *     \'tiene_ventas\' => [nombre_viaje => bool],',
            ' *     \'vendidos_por_micro\' => [',
            ' *       nombre_viaje => [nombre_terminal => [micro_id => int]]',
            ' *     ]',
            ' *   ]',
            ' *',
            ' * La parte `vendidos_por_micro` solo se llena si se pasa',
            ' * $nombre_terminal. Si es null, se omite (los llamadores que',
            ' * no necesitan ese dato evitan el costo).',
            ' *',
            ' * Se usa desde listar_viajes_de_dueno y listar_viajes_de_terminal',
            ' * para pasar el índice a formatear_viaje, y así evitar',
            ' * recorrer el contenedor de ventas por cada viaje (O(V × W)).',
            ' * Fase 3 del plan de optimización del grafo, v76a.',
            ' *',
            ' * @param string      $nombre_dueno',
            ' * @param string|null $nombre_terminal',
            ' * @return array',
            ' */',
            'function _construir_indice_ventas_por_viaje(string $nombre_dueno, ?string $nombre_terminal = null): array {',
            '    $indice = [',
            '        \'tiene_ventas\' => [],',
            '        \'vendidos_por_micro\' => [],',
            '    ];',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if (!$contenedor_ventas) return $indice;',
            '',
            '    $venta_iter = hmi($contenedor_ventas);',
            '    $seg = 0;',
            '    while ($venta_iter && $seg < 2000) {',
            '        $nodo_viaje_venta = $venta_iter->adyacente(\'viaje\');',
            '        if ($nodo_viaje_venta) {',
            '            $nombre_viaje = $nodo_viaje_venta->dato();',
            '            $indice[\'tiene_ventas\'][$nombre_viaje] = true;',
            '',
            '            if ($nombre_terminal !== null) {',
            '                $nodo_terminal_venta = $venta_iter->adyacente(\'terminal\');',
            '                $nodo_micro_venta = $venta_iter->adyacente(\'micro\');',
            '                if ($nodo_terminal_venta && $nodo_terminal_venta->dato() === $nombre_terminal',
            '                    && $nodo_micro_venta) {',
            '                    $micro_id = $nodo_micro_venta->id();',
            '                    $cabeza = $venta_iter->adyacente(\'asientos\');',
            '                    $cantidad = 0;',
            '                    if ($cabeza) {',
            '                        $asiento = $cabeza->adyacente(\'primer\');',
            '                        $seg2 = 0;',
            '                        while ($asiento && $seg2 < 100) {',
            '                            $cantidad++;',
            '                            $asiento = $asiento->adyacente(\'siguiente\');',
            '                            $seg2++;',
            '                        }',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje] = [];',
            '                    }',
            '                    if (!isset($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal])) {',
            '                        $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal] = [];',
            '                    }',
            '                    $indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] =',
            '                        ($indice[\'vendidos_por_micro\'][$nombre_viaje][$nombre_terminal][$micro_id] ?? 0) + $cantidad;',
            '                }',
            '            }',
            '        }',
            '        $venta_iter = hd($venta_iter);',
            '        $seg++;',
            '    }',
            '    return $indice;',
            '}',
            '',
            '/**',
            ' * Guarda la lista de paradas intermedias en el nodo viaje.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v76a antes de v75a',
        'buscar' => [
            '- **v75a**: Fase 2, cierre de los "campos huérfanos" del',
        ],
        'reemplazar' => [
            '- **v76a**: Fase 3 del plan de optimización del grafo.',
            '  `formatear_viaje` recibe un 4to parámetro opcional',
            '  (`$indice_ventas`). `listar_viajes_de_dueno` y',
            '  `listar_viajes_de_terminal` construyen un índice de',
            '  ventas por viaje UNA SOLA VEZ antes del bucle de',
            '  viajes. Helper nuevo',
            '  `_construir_indice_ventas_por_viaje` y',
            '  `_calcular_vendidos_por_micro_de_viaje` (extraído del',
            '  inline anterior). Baja el costo de `listar_viajes_*`',
            '  de O(V × W) a O(V + W).',
            '- **v75a**: Fase 2, cierre de los "campos huérfanos" del',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: agregar Fase 3 al final',
        'buscar' => [
            '**Fase 3 — Optimizaciones (pendiente, prioridad media).**',
            '',
            'Solo si después de Fase 2 todavía hace falta velocidad:',
            '',
            '- **Iteradores persistentes.** El framework permite iteradores',
            '  que van perdurando su posición actual en el grafo. Podrían',
            '  reducir los recorridos repetidos (por ejemplo, en',
            '  `formatear_viaje`).',
            '- **Cacheo de contadores.** El `formatear_viaje` actual',
            '  escala como O(V × W): por cada viaje, recorre todas las',
            '  ventas del dueño. Con un índice de ventas por viaje +',
            '  cacheo de contadores, baja a O(V + W).',
            '- **Eventual carga parcial del grafo.** Requiere cambiar el',
            '  framework (frente A). No se hace por ahora.',
        ],
        'reemplazar' => [
            '**Fase 3 — Optimizaciones (en curso, prioridad media).**',
            '',
            '**Índice de ventas por viaje implementado en v76a.**',
            '`formatear_viaje` recibe un 4to parámetro opcional',
            '(`$indice_ventas`). `listar_viajes_de_dueno` y',
            '`listar_viajes_de_terminal` construyen el índice UNA SOLA',
            'VEZ antes del bucle de viajes, con un solo recorrido del',
            'contenedor de ventas del dueño. Helper nuevo',
            '`_construir_indice_ventas_por_viaje`. El cómputo inline',
            'anterior se extrajo a `_calcular_vendidos_por_micro_de_viaje`,',
            'que se usa como fallback cuando `formatear_viaje` se llama',
            'sin índice (por ejemplo, desde llamados puntuales como',
            '`ventas/obtener`). Baja el costo de `listar_viajes_*`',
            'de O(V × W) a O(V + W).',
            '',
            '**Pendientes de Fase 3:**',
            '',
            '- **Iteradores persistentes.** El framework permite iteradores',
            '  que van perdurando su posición actual en el grafo. Podrían',
            '  reducir recorridos repetidos en otros flujos.',
            '- **Eventual carga parcial del grafo.** Requiere cambiar el',
            '  framework (frente A). No se hace por ahora.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v76a',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.75a',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76a',
            '(Fase 3 del plan de optimización del grafo: índice de',
            'ventas por viaje precalculado. `formatear_viaje` recibe',
            'un 4to parámetro opcional. `listar_viajes_de_dueno` y',
            '`listar_viajes_de_terminal` construyen el índice UNA VEZ',
            'antes del bucle. Baja el costo de O(V × W) a O(V + W).).',
            'Antes: v1.5piloto.75a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v76a',
        'buscar' => [
            '- Cerramos en v75a el Grupo B de Fase 2 (campos',
        ],
        'reemplazar' => [
            '- Cerramos en v76a la primera parte de Fase 3:',
            '  índice de ventas por viaje precalculado. `formatear_viaje`',
            '  acepta un 4to parámetro opcional. `listar_viajes_de_dueno`',
            '  y `listar_viajes_de_terminal` construyen el índice UNA',
            '  VEZ antes del bucle de viajes. Baja el costo de',
            '  O(V × W) a O(V + W). Pendientes de Fase 3: iteradores',
            '  persistentes y eventual carga parcial del grafo.',
            '- Cerramos en v75a el Grupo B de Fase 2 (campos',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v76a',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.75a (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76a (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v76a entre v76 y el plan',
        'buscar' => [
            'v75a: cierre del Grupo B (campos huérfanos).',
            'v76: flujos 15 a 19 (pasajeros y declaraciones',
            'juradas adjuntas).',
        ],
        'reemplazar' => [
            'v75a: cierre del Grupo B (campos huérfanos).',
            'v76: flujos 15 a 19 (pasajeros y declaraciones',
            'juradas adjuntas). v76a: Fase 3, índice de ventas',
            'por viaje.',
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