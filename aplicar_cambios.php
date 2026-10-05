<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74p: pestaña "Grafo" (visualizador de superestructura).
 *
 * - Controlador.php: 3 comandos nuevos (grafo:resumen, grafo:listar,
 *   grafo:nodo) + helpers privados. Se registran en inicializar().
 * - Enrutador.php: módulo grafo con chequeo admin/soporte.
 * - aplicacion.js: agregar 'grafo' a pestañas de admin/soporte.
 * - aplicacion_GET.html: sección nueva + script tag + bumps.
 * - Aplicacion/grafo.js: nuevo. UI completa.
 * - prompts/prompt_piloto.md: documentación extensa del plan de
 *   optimización del grafo (Fases 1-3), criterios de eliminación
 *   (stub), historial v74p, §12 y §13.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // Controlador.php — registrar comandos grafo
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: agregar registro de comandos grafo',
        'buscar' => [
            '            // ─── Comandos genéricos de dominio ──────────────────',
            '            self::registrar_comandos_dominio();',
            '            static::$inicializo = true;',
        ],
        'reemplazar' => [
            '            // ─── Comandos genéricos de dominio ──────────────────',
            '            self::registrar_comandos_dominio();',
            '',
            '            // ─── Comandos del visualizador de grafo ────────────',
            '            self::registrar_comandos_grafo();',
            '            static::$inicializo = true;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: agregar registrar_comandos_grafo y helpers',
        'buscar' => [
            '    // ══════════════════════════════════════════════════════',
            '    // INICIALIZACION',
            '    // ══════════════════════════════════════════════════════',
        ],
        'reemplazar' => [
            '    // ══════════════════════════════════════════════════════',
            '    // COMANDOS DEL VISUALIZADOR DE GRAFO (v1.5piloto.74p)',
            '    // ══════════════════════════════════════════════════════',
            '',
            '    /**',
            '     * Registra los comandos del visualizador de grafo.',
            '     *',
            '     * Estos comandos exponen operaciones de solo lectura sobre',
            '     * la superestructura sin revelar el token de seguridad.',
            '     * El token queda encapsulado en los closures. La seguridad',
            '     * la aporta el enrutador, que es el único que los invoca',
            '     * (y ya valida admin/soporte).',
            '     *',
            '     * @return void',
            '     */',
            '    private static function registrar_comandos_grafo(): void',
            '    {',
            '        // ─── grafo:resumen ─────────────────────────────────',
            '        self::registrar_comando(\'grafo:resumen\', function(string $token, array $args) {',
            '            $nodos = self::_grafo_cargar_estructura($token);',
            '            $alcanzables = self::_grafo_bfs_desde_raices($nodos);',
            '            $total = count($nodos);',
            '            $huerfanos = $total - count($alcanzables);',
            '',
            '            // Top de referencias entrantes.',
            '            $refs = [];',
            '            foreach ($nodos as $id => $info) {',
            '                foreach ($info[\'ady\'] as $enlace => $destino) {',
            '                    $refs[$destino] = ($refs[$destino] ?? 0) + 1;',
            '                }',
            '            }',
            '            arsort($refs);',
            '            $top = array_slice($refs, 0, 20, true);',
            '',
            '            return [',
            '                \'total\' => $total,',
            '                \'alcanzables\' => count($alcanzables),',
            '                \'huerfanos\' => $huerfanos,',
            '                \'top_referencias\' => (object) $top,',
            '            ];',
            '        }, null, false);',
            '',
            '        // ─── grafo:listar ──────────────────────────────────',
            '        self::registrar_comando(\'grafo:listar\', function(string $token, array $args) {',
            '            $opciones = $args[0] ?? [];',
            '            $filtro = (string)($opciones[\'filtro\'] ?? \'todos\');',
            '            $filtro_enlace = (string)($opciones[\'enlace\'] ?? \'\');',
            '            $filtro_texto = (string)($opciones[\'texto\'] ?? \'\');',
            '            $offset = max(0, (int)($opciones[\'offset\'] ?? 0));',
            '            $limite = max(1, min(500, (int)($opciones[\'limite\'] ?? 50)));',
            '',
            '            $nodos = self::_grafo_cargar_estructura($token);',
            '            $alcanzables = ($filtro !== \'todos\') ? self::_grafo_bfs_desde_raices($nodos) : [];',
            '',
            '            // Cantidad de referencias entrantes por nodo, calculada una sola vez.',
            '            $refs_count = [];',
            '            foreach ($nodos as $id => $info) {',
            '                foreach ($info[\'ady\'] as $destino) {',
            '                    $refs_count[$destino] = ($refs_count[$destino] ?? 0) + 1;',
            '                }',
            '            }',
            '',
            '            $resultados = [];',
            '            foreach ($nodos as $id => $info) {',
            '                if ($filtro === \'huerfanos\' && isset($alcanzables[$id])) continue;',
            '                if ($filtro === \'alcanzables\' && !isset($alcanzables[$id])) continue;',
            '                if ($filtro_enlace !== \'\' && !isset($info[\'ady\'][$filtro_enlace])) continue;',
            '                if ($filtro_texto !== \'\' && stripos($info[\'dato\'], $filtro_texto) === false) continue;',
            '',
            '                $resultados[] = [',
            '                    \'id\' => $id,',
            '                    \'dato\' => mb_substr($info[\'dato\'], 0, 100),',
            '                    \'es_especial\' => !is_numeric($id),',
            '                    \'n_adyacentes\' => count($info[\'ady\']),',
            '                    \'n_referencias\' => $refs_count[$id] ?? 0,',
            '                    \'tipo\' => self::_grafo_inferir_tipo($id, $info[\'ady\']),',
            '                ];',
            '            }',
            '',
            '            return [',
            '                \'total\' => count($resultados),',
            '                \'offset\' => $offset,',
            '                \'limite\' => $limite,',
            '                \'nodos\' => array_slice($resultados, $offset, $limite),',
            '            ];',
            '        }, null, false);',
            '',
            '        // ─── grafo:nodo ────────────────────────────────────',
            '        self::registrar_comando(\'grafo:nodo\', function(string $token, array $args) {',
            '            $id = (string)($args[0] ?? \'\');',
            '            if ($id === \'\') return null;',
            '',
            '            $nodo = Nodo::nodo_por_id($id);',
            '            if (!$nodo) return null;',
            '',
            '            $adyacentes = [];',
            '            foreach ($nodo->adyacentes() as $enlace => $ady) {',
            '                $adyacentes[] = [',
            '                    \'enlace\' => (string)$enlace,',
            '                    \'id_destino\' => $ady->id(),',
            '                    \'dato_destino\' => mb_substr((string)$ady->dato(), 0, 80),',
            '                ];',
            '            }',
            '',
            '            // Referencias entrantes: recorrido completo, filtrado.',
            '            $referencias = [];',
            '            Nodo::por_cada_nodo_ejecutar($token, function($otro) use ($id, &$referencias) {',
            '                foreach ($otro->adyacentes() as $enlace => $destino) {',
            '                    if ($destino->id() === $id) {',
            '                        $referencias[] = [',
            '                            \'id_origen\' => $otro->id(),',
            '                            \'enlace\' => (string)$enlace,',
            '                            \'dato_origen\' => mb_substr((string)$otro->dato(), 0, 80),',
            '                        ];',
            '                    }',
            '                }',
            '            }, null);',
            '',
            '            return [',
            '                \'id\' => $id,',
            '                \'dato\' => (string)$nodo->dato(),',
            '                \'es_especial\' => !is_numeric($id),',
            '                \'adyacentes\' => $adyacentes,',
            '                \'referencias\' => $referencias,',
            '            ];',
            '        }, null, false);',
            '    }',
            '',
            '    /**',
            '     * Carga la estructura básica de la superestructura en memoria.',
            '     *',
            '     * Devuelve [id => [dato, ady => [enlace => id_destino]]].',
            '     *',
            '     * @param string $token',
            '     * @return array',
            '     */',
            '    private static function _grafo_cargar_estructura(string $token): array',
            '    {',
            '        $nodos = [];',
            '        Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$nodos) {',
            '            $ady = [];',
            '            foreach ($nodo->adyacentes() as $enlace => $adyacente) {',
            '                $ady[(string)$enlace] = $adyacente->id();',
            '            }',
            '            $nodos[$nodo->id()] = [',
            '                \'dato\' => (string)$nodo->dato(),',
            '                \'ady\' => $ady,',
            '            ];',
            '        }, null);',
            '        return $nodos;',
            '    }',
            '',
            '    /**',
            '     * BFS desde los nodos especiales (raíces del grafo).',
            '     *',
            '     * Devuelve [id => true] para cada nodo alcanzable.',
            '     *',
            '     * @param array $nodos',
            '     * @return array',
            '     */',
            '    private static function _grafo_bfs_desde_raices(array $nodos): array',
            '    {',
            '        $alcanzables = [];',
            '        $cola = [];',
            '        foreach ($nodos as $id => $info) {',
            '            if (!is_numeric($id)) {',
            '                $alcanzables[$id] = true;',
            '                $cola[] = $id;',
            '            }',
            '        }',
            '        while (!empty($cola)) {',
            '            $id = array_shift($cola);',
            '            if (!isset($nodos[$id])) continue;',
            '            foreach ($nodos[$id][\'ady\'] as $destino) {',
            '                if (!isset($alcanzables[$destino])) {',
            '                    $alcanzables[$destino] = true;',
            '                    $cola[] = $destino;',
            '                }',
            '            }',
            '        }',
            '        return $alcanzables;',
            '    }',
            '',
            '    /**',
            '     * Infiere un tipo legible para un nodo a partir de sus enlaces.',
            '     * Heurística. Se puede refinar con el tiempo.',
            '     *',
            '     * @param string $id',
            '     * @param array  $ady',
            '     * @return string',
            '     */',
            '    private static function _grafo_inferir_tipo(string $id, array $ady): string',
            '    {',
            '        // Especiales (raíces conocidas).',
            '        if ($id === \'usuarios\') return \'Contenedor raíz: usuarios\';',
            '        if ($id === \'sesiones\') return \'Contenedor raíz: sesiones\';',
            '        if (!is_numeric($id)) return \'Especial\';',
            '',
            '        // Usuarios.',
            '        if (isset($ady[\'nivel\'])) return \'Usuario\';',
            '        // Pasajeros.',
            '        if (isset($ady[\'apellido\']) && isset($ady[\'nombres\'])) return \'Pasajero\';',
            '        // Viaje.',
            '        if (isset($ady[\'origen\']) && isset($ady[\'destino\']) && isset($ady[\'micros\'])) return \'Viaje\';',
            '        // Micro.',
            '        if (isset($ady[\'vehiculo_copia\']) && isset($ady[\'monto\'])) return \'Micro\';',
            '        // Venta.',
            '        if (isset($ady[\'total\']) && isset($ady[\'comprador\'])) return \'Venta\';',
            '        // Cupón.',
            '        if (isset($ady[\'numero\']) && isset($ady[\'estado\'])) return \'Cupón\';',
            '        // Rendición.',
            '        if (isset($ady[\'detalle_terminales\']) && isset($ady[\'detalle_cupones\'])) return \'Rendición\';',
            '        // Liquidación.',
            '        if (isset($ady[\'monto_efectivo\']) && isset($ady[\'monto_banco\'])) return \'Liquidación\';',
            '        // Cancelación.',
            '        if (isset($ady[\'id_venta\']) && isset($ady[\'motivo\'])) return \'Cancelación\';',
            '        // Sesión.',
            '        if (isset($ady[\'usuario\']) && isset($ady[\'creado_en\'])) return \'Sesión\';',
            '        // Copia de vehículo.',
            '        if (isset($ady[\'asientos\']) && isset($ady[\'foto\'])) return \'Vehículo/Copia\';',
            '        if (isset($ady[\'asientos\'])) return \'Vehículo\';',
            '        // Empresa.',
            '        if (isset($ady[\'vehiculos\'])) return \'Empresa\';',
            '        // Piso.',
            '        if (isset($ady[\'filas\']) && isset($ady[\'columnas\'])) return \'Piso\';',
            '        // Asiento.',
            '        if (isset($ady[\'fila\']) && isset($ady[\'columna\'])) return \'Asiento\';',
            '        // Contenedor genérico (dato vacío y varios enlaces).',
            '        return \'?\';',
            '    }',
            '',
            '    // ══════════════════════════════════════════════════════',
            '    // INICIALIZACION',
            '    // ══════════════════════════════════════════════════════',
        ],
    ],

    // ========================================================
    // Enrutador.php — módulo grafo
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: agregar modulo grafo',
        'buscar' => [
            '        case \'entorno\':',
        ],
        'reemplazar' => [
            '        case \'grafo\':',
            '            // Solo admin y soporte.',
            '            $nombre_sol_gr = $post[\'nombre_solicitante\'] ?? \'\';',
            '            $raiz_sol_gr = Nodo::nodo_por_id(\'usuarios\');',
            '            $nodo_sol_gr = ($raiz_sol_gr && $nombre_sol_gr !== \'\') ? $raiz_sol_gr->adyacente($nombre_sol_gr) : null;',
            '            $nodo_nivel_gr = $nodo_sol_gr ? $nodo_sol_gr->adyacente(\'nivel\') : null;',
            '            $nivel_sol_gr = $nodo_nivel_gr ? $nodo_nivel_gr->dato() : \'\';',
            '            if (!in_array($nivel_sol_gr, [\'admin\', \'soporte\'], true)) {',
            '                responder_json([\'exito\' => false, \'error\' => \'Permiso denegado\']);',
            '            }',
            '',
            '            switch ($subaccion) {',
            '                case \'resumen\':',
            '                    $resumen_gr = Controlador::ejecutar_comando(\'grafo:resumen\');',
            '                    responder_json([\'exito\' => true, \'resumen\' => $resumen_gr]);',
            '                    break;',
            '',
            '                case \'listar\':',
            '                    $opciones_gr = [',
            '                        \'filtro\' => $post[\'filtro\'] ?? \'todos\',',
            '                        \'enlace\' => $post[\'enlace\'] ?? \'\',',
            '                        \'texto\' => $post[\'texto\'] ?? \'\',',
            '                        \'offset\' => (int)($post[\'offset\'] ?? 0),',
            '                        \'limite\' => (int)($post[\'limite\'] ?? 50),',
            '                    ];',
            '                    $lista_gr = Controlador::ejecutar_comando(\'grafo:listar\', $opciones_gr);',
            '                    responder_json([\'exito\' => true, \'lista\' => $lista_gr]);',
            '                    break;',
            '',
            '                case \'nodo\':',
            '                    $id_gr = $post[\'id\'] ?? \'\';',
            '                    if ($id_gr === \'\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID no especificado\']);',
            '                    }',
            '                    $nodo_gr = Controlador::ejecutar_comando(\'grafo:nodo\', $id_gr);',
            '                    if (!$nodo_gr) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Nodo no encontrado\']);',
            '                    }',
            '                    responder_json([\'exito\' => true, \'nodo\' => $nodo_gr]);',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de grafo no válida\']);',
            '            }',
            '            break;',
            '',
            '        case \'entorno\':',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador: bump @version a 1.5piloto.74p',
        'buscar' => [
            ' * @version   1.5piloto.74o',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.74p',
        ],
    ],

    // ========================================================
    // aplicacion.js — pestaña grafo
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar grafo a pestanas permitidas',
        'buscar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        soporte: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
        'reemplazar' => [
            '    const pestanas_permitidas = {',
            '        admin: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\', \'grafo\'],',
            '        soporte: [\'admin\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\', \'grafo\'],',
            '        dueno: [\'terminales\', \'micros\', \'viajes\', \'vendidos\', \'pasajeros\', \'rendiciones\', \'liquidaciones\'],',
            '        terminal: [\'viajes\', \'vendidos\', \'pasajeros\']',
            '    };',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: agregar nombre visible de grafo',
        'buscar' => [
            '        liquidaciones: \'Liquidaciones\',',
            '        terminales: \'Puntos de venta\'',
            '    };',
        ],
        'reemplazar' => [
            '        liquidaciones: \'Liquidaciones\',',
            '        terminales: \'Puntos de venta\',',
            '        grafo: \'Grafo\'',
            '    };',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: dispatch de grafo en activar_pestana',
        'buscar' => [
            '    if (id_pestana === \'admin\') return cargar_datos_admin();',
            '    if (id_pestana === \'terminales\') return cargar_datos_terminales();',
            '    return Promise.resolve();',
        ],
        'reemplazar' => [
            '    if (id_pestana === \'admin\') return cargar_datos_admin();',
            '    if (id_pestana === \'terminales\') return cargar_datos_terminales();',
            '    if (id_pestana === \'grafo\') return cargar_grafo();',
            '    return Promise.resolve();',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: limpiar contenedores del grafo',
        'buscar' => [
            '        // Pasajeros',
            '        \'selector_dueno_pasajeros\',',
            '        \'tabla_pasajeros\',',
            '    ];',
        ],
        'reemplazar' => [
            '        // Pasajeros',
            '        \'selector_dueno_pasajeros\',',
            '        \'tabla_pasajeros\',',
            '        // Grafo',
            '        \'grafo_resumen\',',
            '        \'grafo_tabla\',',
            '        \'grafo_paginacion\',',
            '    ];',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump @version a 1.5piloto.74p',
        'buscar' => [
            ' * @version 1.5piloto.74f',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.74p',
        ],
    ],

    // ========================================================
    // aplicacion_GET.html — seccion grafo + script tag + bumps
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: agregar seccion grafo',
        'buscar' => [
            '    <!-- Sección Pasajeros -->',
            '    <section id="pasajeros" class="tab-content hidden">',
        ],
        'reemplazar' => [
            '    <!-- Sección Grafo (solo admin y soporte) -->',
            '    <section id="grafo" class="tab-content hidden">',
            '      <div class="panel">',
            '        <h2>Grafo (superestructura)</h2>',
            '        <p class="muted">Vista de solo lectura. Muestra la superestructura completa: totales, nodos alcanzables vs huérfanos, y permite inspeccionar los enlaces de cada nodo. Los nodos huérfanos son la causa principal de la acumulación del grafo.</p>',
            '        <div id="grafo_resumen" class="panel" style="background:#f7f7f7;"></div>',
            '        <div class="row" style="gap:8px; flex-wrap:wrap; margin-bottom:10px; align-items:flex-end;">',
            '          <div class="field"><label for="grafo_filtro">Filtro</label>',
            '            <select id="grafo_filtro">',
            '              <option value="todos">Todos</option>',
            '              <option value="huerfanos">Solo huérfanos</option>',
            '              <option value="alcanzables">Solo alcanzables</option>',
            '            </select>',
            '          </div>',
            '          <div class="field"><label for="grafo_filtro_enlace">Enlace</label>',
            '            <input id="grafo_filtro_enlace" placeholder="Ej: pasajeros">',
            '          </div>',
            '          <div class="field"><label for="grafo_filtro_texto">Texto en dato</label>',
            '            <input id="grafo_filtro_texto" placeholder="Buscar...">',
            '          </div>',
            '          <button class="btn primary" id="grafo_boton_filtrar">Filtrar</button>',
            '          <button class="btn" id="grafo_boton_limpiar">Limpiar</button>',
            '        </div>',
            '        <div class="table-wrap">',
            '          <table class="data-table">',
            '            <thead>',
            '              <tr>',
            '                <th>ID</th>',
            '                <th>Tipo</th>',
            '                <th>Dato</th>',
            '                <th>Enl. sal.</th>',
            '                <th>Refs ent.</th>',
            '                <th></th>',
            '              </tr>',
            '            </thead>',
            '            <tbody id="grafo_tabla"></tbody>',
            '          </table>',
            '        </div>',
            '        <div id="grafo_paginacion" class="row" style="margin-top:10px; gap:8px; align-items:center;"></div>',
            '      </div>',
            '    </section>',
            '',
            '    <!-- Sección Pasajeros -->',
            '    <section id="pasajeros" class="tab-content hidden">',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump ?v= de aplicacion.js a 1.5piloto.74p',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.74f"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.74p"></script>',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: agregar script tag de grafo.js',
        'buscar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.74o"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/pasajeros.js?v=1.5piloto.74o"></script>',
            '<script src="Aplicacion/grafo.js?v=1.5piloto.74p"></script>',
        ],
    ],

    // ========================================================
    // Aplicacion/grafo.js — nuevo
    // ========================================================

    [
        'tipo' => 'crear',
        'archivo' => 'Aplicacion/grafo.js',
        'descripcion' => 'Nuevo archivo grafo.js',
        'contenido' => [
            '/**',
            ' * Pestaña Grafo — visualizador de la superestructura.',
            ' *',
            ' * Vista de solo lectura. Muestra totales, nodos alcanzables vs',
            ' * huérfanos, y permite inspeccionar los enlaces de cada nodo.',
            ' *',
            ' * Es la base para la auditoría de la fuga de nodos (Fase 2).',
            ' * Ver prompts/prompt_piloto.md §8.6.',
            ' *',
            ' * @version 1.5piloto.74p',
            ' */',
            '',
            'let grafo_offset_actual = 0;',
            'let grafo_limite_actual = 50;',
            'let grafo_total_actual = 0;',
            '',
            'async function cargar_grafo() {',
            '    // Cargar resumen.',
            '    const resp_res = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/resumen",',
            '            nombre_solicitante: usuario_actual.nombre_usuario',
            '        })',
            '    });',
            '    const datos_res = await resp_res.json();',
            '    if (!datos_res.exito) {',
            '        mostrar_aviso(datos_res.error || "Error al cargar resumen", \'error\');',
            '        return;',
            '    }',
            '    _grafo_renderizar_resumen(datos_res.resumen);',
            '    await _grafo_cargar_tabla(0);',
            '}',
            '',
            'function _grafo_renderizar_resumen(resumen) {',
            '    const cont = document.getElementById(\'grafo_resumen\');',
            '    if (!cont) return;',
            '    let top_html = \'\';',
            '    const top = resumen.top_referencias || {};',
            '    const ids_top = Object.keys(top).slice(0, 10);',
            '    if (ids_top.length > 0) {',
            '        top_html = \'<div style="margin-top:8px;"><strong>Top nodos por referencias entrantes:</strong><ul style="margin:6px 0; padding-left:20px; font-family:monospace; font-size:12px;">\';',
            '        for (const id of ids_top) {',
            '            top_html += `<li><code>${_grafo_escape(id)}</code> → ${top[id]} referencia(s)</li>`;',
            '        }',
            '        top_html += \'</ul></div>\';',
            '    }',
            '    const color_huerfanos = (resumen.huerfanos > 0) ? \'#c00\' : \'#333\';',
            '    cont.innerHTML = `',
            '        <div style="display:flex; gap:24px; flex-wrap:wrap;">',
            '            <div><span class="muted">Total nodos:</span> <strong>${resumen.total}</strong></div>',
            '            <div><span class="muted">Alcanzables:</span> <strong>${resumen.alcanzables}</strong></div>',
            '            <div><span class="muted">Huérfanos:</span> <strong style="color:${color_huerfanos};">${resumen.huerfanos}</strong></div>',
            '        </div>',
            '        ${top_html}',
            '    `;',
            '}',
            '',
            'async function _grafo_cargar_tabla(offset) {',
            '    grafo_offset_actual = offset;',
            '    const filtro = document.getElementById(\'grafo_filtro\').value || \'todos\';',
            '    const enlace = document.getElementById(\'grafo_filtro_enlace\').value.trim();',
            '    const texto = document.getElementById(\'grafo_filtro_texto\').value.trim();',
            '',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/listar",',
            '            nombre_solicitante: usuario_actual.nombre_usuario,',
            '            filtro,',
            '            enlace,',
            '            texto,',
            '            offset: String(offset),',
            '            limite: String(grafo_limite_actual)',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al listar", \'error\');',
            '        return;',
            '    }',
            '    grafo_total_actual = datos.lista.total;',
            '    _grafo_renderizar_tabla(datos.lista.nodos);',
            '    _grafo_renderizar_paginacion(datos.lista);',
            '}',
            '',
            'function _grafo_renderizar_tabla(nodos) {',
            '    const tbody = document.getElementById(\'grafo_tabla\');',
            '    if (!tbody) return;',
            '    tbody.innerHTML = \'\';',
            '    if (nodos.length === 0) {',
            '        tbody.innerHTML = \'<tr><td colspan="6" style="text-align:center;color:#888;">Sin resultados</td></tr>\';',
            '        return;',
            '    }',
            '    nodos.forEach(n => {',
            '        const tr = document.createElement(\'tr\');',
            '        tr.innerHTML = `',
            '            <td><code>${_grafo_escape(n.id)}</code></td>',
            '            <td>${_grafo_escape(n.tipo || \'?\')}</td>',
            '            <td style="word-break:break-all;">${_grafo_escape(n.dato || \'\')}</td>',
            '            <td>${n.n_adyacentes}</td>',
            '            <td>${n.n_referencias}</td>',
            '            <td><button class="btn" data-id="${_grafo_escape(n.id)}">Ver</button></td>',
            '        `;',
            '        tr.querySelector(\'button\').addEventListener(\'click\', () => ver_nodo_grafo(n.id));',
            '        tbody.appendChild(tr);',
            '    });',
            '}',
            '',
            'function _grafo_renderizar_paginacion(lista) {',
            '    const cont = document.getElementById(\'grafo_paginacion\');',
            '    if (!cont) return;',
            '    const total = lista.total;',
            '    const offset = lista.offset;',
            '    const limite = lista.limite;',
            '    const desde = total > 0 ? offset + 1 : 0;',
            '    const hasta = Math.min(offset + limite, total);',
            '    const boton_prev = offset > 0 ? `<button class="btn" id="grafo_prev">← Anterior</button>` : \'\';',
            '    const boton_next = (offset + limite < total) ? `<button class="btn" id="grafo_next">Siguiente →</button>` : \'\';',
            '    cont.innerHTML = `<span class="muted">${desde}-${hasta} de ${total}</span> ${boton_prev} ${boton_next}`;',
            '    const btn_prev = document.getElementById(\'grafo_prev\');',
            '    if (btn_prev) btn_prev.addEventListener(\'click\', () => _grafo_cargar_tabla(Math.max(0, offset - limite)));',
            '    const btn_next = document.getElementById(\'grafo_next\');',
            '    if (btn_next) btn_next.addEventListener(\'click\', () => _grafo_cargar_tabla(offset + limite));',
            '}',
            '',
            'async function ver_nodo_grafo(id) {',
            '    const resp = await fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({',
            '            accion: "grafo/nodo",',
            '            nombre_solicitante: usuario_actual.nombre_usuario,',
            '            id',
            '        })',
            '    });',
            '    const datos = await resp.json();',
            '    if (!datos.exito) {',
            '        mostrar_aviso(datos.error || "Error al obtener nodo", \'error\');',
            '        return;',
            '    }',
            '    _grafo_renderizar_modal_nodo(datos.nodo);',
            '}',
            '',
            'function _grafo_renderizar_modal_nodo(n) {',
            '    let ady_html = \'\';',
            '    if (n.adyacentes && n.adyacentes.length > 0) {',
            '        ady_html = \'<ul style="margin:4px 0; padding-left:20px; font-family:monospace; font-size:12px;">\';',
            '        n.adyacentes.forEach(a => {',
            '            const dato = a.dato_destino ? \' (\' + _grafo_escape(a.dato_destino) + \')\' : \'\';',
            '            ady_html += `<li><code>${_grafo_escape(a.enlace)}</code> → <code>${_grafo_escape(a.id_destino)}</code>${dato} <button class="btn" data-nav="${_grafo_escape(a.id_destino)}" style="padding:0 6px; font-size:11px;">→</button></li>`;',
            '        });',
            '        ady_html += \'</ul>\';',
            '    } else {',
            '        ady_html = \'<p class="muted">Sin enlaces salientes.</p>\';',
            '    }',
            '',
            '    let refs_html = \'\';',
            '    if (n.referencias && n.referencias.length > 0) {',
            '        refs_html = \'<ul style="margin:4px 0; padding-left:20px; font-family:monospace; font-size:12px;">\';',
            '        n.referencias.forEach(r => {',
            '            const dato = r.dato_origen ? \' (\' + _grafo_escape(r.dato_origen) + \')\' : \'\';',
            '            refs_html += `<li><code>${_grafo_escape(r.id_origen)}</code> → <code>${_grafo_escape(r.enlace)}</code>${dato} <button class="btn" data-nav="${_grafo_escape(r.id_origen)}" style="padding:0 6px; font-size:11px;">→</button></li>`;',
            '        });',
            '        refs_html += \'</ul>\';',
            '    } else {',
            '        refs_html = \'<p class="muted">Sin referencias entrantes. Es una raíz o un nodo huérfano.</p>\';',
            '    }',
            '',
            '    const html = `',
            '        <div class="seccion">',
            '            <div class="detail-line"><span>ID:</span><strong><code>${_grafo_escape(n.id)}</code></strong></div>',
            '            <div class="detail-line"><span>Es especial:</span><strong>${n.es_especial ? \'Sí\' : \'No\'}</strong></div>',
            '            <div class="detail-line"><span>Dato:</span><strong style="word-break:break-all;">${_grafo_escape(n.dato || \'(vacío)\')}</strong></div>',
            '        </div>',
            '        <div class="seccion">',
            '            <h4>Enlaces salientes (${(n.adyacentes || []).length})</h4>',
            '            ${ady_html}',
            '        </div>',
            '        <div class="seccion">',
            '            <h4>Referencias entrantes (${(n.referencias || []).length})</h4>',
            '            ${refs_html}',
            '        </div>',
            '        <div class="actions" style="margin-top:15px;">',
            '            <button class="btn" id="grafo_cerrar_modal">Cerrar</button>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_apilado(\'Nodo: \' + n.id, html);',
            '',
            '    const cont = document.getElementById(\'modal_apilado_contenido\');',
            '    if (!cont) return;',
            '    cont.querySelector(\'#grafo_cerrar_modal\').addEventListener(\'click\', cerrar_modal_apilado);',
            '    cont.querySelectorAll(\'button[data-nav]\').forEach(btn => {',
            '        btn.addEventListener(\'click\', () => {',
            '            const id_destino = btn.dataset.nav;',
            '            cerrar_modal_apilado();',
            '            ver_nodo_grafo(id_destino);',
            '        });',
            '    });',
            '}',
            '',
            'function _grafo_escape(s) {',
            '    return String(s == null ? \'\' : s)',
            '        .replace(/&/g, \'&amp;\')',
            '        .replace(/</g, \'&lt;\')',
            '        .replace(/>/g, \'&gt;\')',
            '        .replace(/"/g, \'&quot;\');',
            '}',
            '',
            '// Inicialización de listeners del panel Grafo.',
            '(function() {',
            '    const btn_filtrar = document.getElementById(\'grafo_boton_filtrar\');',
            '    if (btn_filtrar) btn_filtrar.addEventListener(\'click\', () => _grafo_cargar_tabla(0));',
            '    const btn_limpiar = document.getElementById(\'grafo_boton_limpiar\');',
            '    if (btn_limpiar) btn_limpiar.addEventListener(\'click\', () => {',
            '        document.getElementById(\'grafo_filtro\').value = \'todos\';',
            '        document.getElementById(\'grafo_filtro_enlace\').value = \'\';',
            '        document.getElementById(\'grafo_filtro_texto\').value = \'\';',
            '        _grafo_cargar_tabla(0);',
            '    });',
            '})();',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §2 estructura de archivos
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §2 agregar grafo.js',
        'buscar' => [
            '- `ventas.js`, `pasajeros.js`, `rendiciones.js`, `liquidaciones.js`.',
        ],
        'reemplazar' => [
            '- `ventas.js`, `pasajeros.js`, `rendiciones.js`, `liquidaciones.js`.',
            '- `grafo.js`: pestaña Grafo (visualizador de la superestructura).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §3.2 Pestañas
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §3.2 agregar pestana Grafo',
        'buscar' => [
            '- Pasajeros/Clientes (todos).',
        ],
        'reemplazar' => [
            '- Pasajeros/Clientes (todos).',
            '- Grafo (solo admin y soporte): visualizador de la superestructura.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §3.4 Pestaña Grafo (nueva)
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §3.4 nueva subseccion Pestaña Grafo',
        'buscar' => [
            '---',
            '',
            '## 4. ESTRUCTURA DE NODOS DEL PILOTO',
        ],
        'reemplazar' => [
            '### 3.4 Pestaña Grafo (visualizador de la superestructura)',
            '',
            'Implementada en v1.5piloto.74p (Fase 1 del plan de optimización,',
            'ver §8.6). Visible solo para **admin y soporte**.',
            '',
            '**Es de solo lectura.** No modifica el grafo.',
            '',
            '**Qué muestra:**',
            '',
            '- **Resumen:** total de nodos, alcanzables desde las raíces',
            '  (`usuarios`, `sesiones` y demás IDs especiales), huérfanos, y',
            '  top 20 de nodos por referencias entrantes.',
            '- **Tabla:** listado paginado con filtros por estado',
            '  (`todos` / `huerfanos` / `alcanzables`), por nombre de enlace y',
            '  por texto en el dato. Columnas: ID, tipo inferido, dato,',
            '  cantidad de enlaces salientes, cantidad de referencias',
            '  entrantes.',
            '- **Detalle (modal apilado):** click en una fila → enlaces',
            '  salientes, referencias entrantes, y navegación por los nodos',
            '  relacionados.',
            '',
            '**Cómo funciona:**',
            '',
            '- El frontend (`Aplicacion/grafo.js`) llama a tres subacciones',
            '  del enrutador: `grafo/resumen`, `grafo/listar`, `grafo/nodo`.',
            '- El enrutador (módulo `grafo`, chequeo admin/soporte) invoca',
            '  los comandos `grafo:resumen`, `grafo:listar`, `grafo:nodo`',
            '  del `Controlador` a través de `Controlador::ejecutar_comando()`.',
            '- Los comandos están definidos en',
            '  `Controlador::registrar_comandos_grafo()` (privado). Usan el',
            '  token interno sin exponerlo. **No usan el motor**: se ejecutan',
            '  de a uno.',
            '- Helpers privados en `Controlador`: `_grafo_cargar_estructura`,',
            '  `_grafo_bfs_desde_raices`, `_grafo_inferir_tipo`.',
            '',
            '**Limitaciones conocidas:**',
            '',
            '- El comando `grafo:nodo` recorre todo el grafo para encontrar',
            '  referencias entrantes (O(N) por click).',
            '- `grafo:listar` carga todo el grafo en memoria y filtra en PHP.',
            '- `_grafo_inferir_tipo` es heurística; puede devolver `?` para',
            '  nodos que no cumplen ningún patrón conocido.',
            '',
            'Estas limitaciones son aceptables para Fase 1 (diagnóstico). Se',
            'pueden optimizar en Fase 3.',
            '',
            '---',
            '',
            '## 4. ESTRUCTURA DE NODOS DEL PILOTO',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §6.4 Otros JS
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §6.4 agregar grafo.js',
        'buscar' => [
            '- `rendiciones.js`, `liquidaciones.js`.',
            '',
            '### 6.5 Pantalla de login',
        ],
        'reemplazar' => [
            '- `rendiciones.js`, `liquidaciones.js`.',
            '- `grafo.js`: pestaña Grafo. Ver §3.4.',
            '',
            '### 6.5 Pantalla de login',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §5.14 Enrutador
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §5.14 agregar modulo grafo',
        'buscar' => [
            'Subacciones especiales:',
            '- `viajes/limpiar_prueba` (admin + modo pruebas): elimina los',
            '  viajes de prueba del dueño seleccionado.',
            '- `pasajeros/limpiar_prueba` (admin + modo pruebas): elimina',
            '  los pasajeros con email `@test.local` que no tengan',
            '  referencias entrantes.',
            '- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin',
            '  permisos. Lo consume el frontend para saber si mostrar los',
            '  botones de limpieza.',
        ],
        'reemplazar' => [
            'Subacciones especiales:',
            '- `viajes/limpiar_prueba` (admin + modo pruebas): elimina los',
            '  viajes de prueba del dueño seleccionado.',
            '- `pasajeros/limpiar_prueba` (admin + modo pruebas): elimina',
            '  los pasajeros con email `@test.local` que no tengan',
            '  referencias entrantes.',
            '- `entorno/info`: devuelve `{modo, es_pruebas}`. Público, sin',
            '  permisos. Lo consume el frontend para saber si mostrar los',
            '  botones de limpieza.',
            '- Módulo `grafo` (admin y soporte): `grafo/resumen`,',
            '  `grafo/listar`, `grafo/nodo`. Invocan comandos del',
            '  `Controlador` (`grafo:resumen`, `grafo:listar`,',
            '  `grafo:nodo`). Ver §3.4.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — historial v74p
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: agregar v74p al historial',
        'buscar' => [
            '- **v74o**: botón "Limpiar pasajeros de prueba" en la pestaña',
        ],
        'reemplazar' => [
            '- **v74p**: pestaña "Grafo" (Fase 1 del plan de optimización',
            '  del grafo). Visible solo para admin y soporte. Vista de solo',
            '  lectura: totales, alcanzables vs huérfanos, top de',
            '  referencias, tabla filtrable, y modal de detalle por nodo.',
            '  Backend: 3 comandos nuevos en el `Controlador`',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados',
            '  desde `registrar_comandos_grafo()`. Los comandos usan el',
            '  token interno sin exponerlo; se ejecutan de a uno (sin',
            '  motor). Módulo `grafo` en el enrutador con chequeo',
            '  admin/soporte. Frontend: `Aplicacion/grafo.js` nuevo y',
            '  nueva sección en `aplicacion_GET.html`.',
            '- **v74o**: botón "Limpiar pasajeros de prueba" en la pestaña',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §8.4 actualizada
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §8.4 actualizar estado del visualizador',
        'buscar' => [
            '### 8.4 Pestaña especial: visualizador del grafo (futuro)',
            '',
            'Pestaña nueva que verán **admin y soporte**. Objetivo: dar una',
            'interfaz amigable para inspeccionar la estructura de nodos y',
            'enlaces, sin depender de `imprimir_superestructura` ni de la',
            'consola. Incluye:',
            '',
            '- Navegación por la superestructura desde la raíz.',
            '- Vista de nodos con sus datos y enlaces salientes.',
            '- Listado de iteradores creados con sus cuerpos, alias y posición',
            '  actual.',
            '- Acciones sobre iteradores (crear, destruir, desocupar, ver',
            '  caminos registrados).',
            '',
            'Se implementa **después** de los bugs y los pendientes menores.',
        ],
        'reemplazar' => [
            '### 8.4 Pestaña Grafo (visualizador de la superestructura)',
            '',
            '**Implementada en v74p (Fase 1).** Ver §3.4 para el detalle de',
            'qué hace y cómo funciona, y §8.6 para el plan completo de las',
            'tres fases de optimización del grafo.',
            '',
            'Pendiente para futuras iteraciones del visualizador:',
            '',
            '- Listado de iteradores creados con sus cuerpos, alias y',
            '  posición actual (no implementado en Fase 1).',
            '- Acciones sobre iteradores (crear, destruir, desocupar, ver',
            '  caminos registrados) (no implementado en Fase 1).',
            '- Acciones sobre nodos (eliminar huérfanos, etc.) (no',
            '  implementado en Fase 1).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §8.6 (nueva sección)
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §8.6 nuevo plan de optimizacion del grafo',
        'buscar' => [
            '---',
            '',
            '## 9. PATRONES DE CÓDIGO DEL PILOTO',
        ],
        'reemplazar' => [
            '### 8.6 Plan de optimización del grafo (tres fases)',
            '',
            '**Contexto.** La aplicación se vuelve lenta a medida que el',
            'grafo crece. Mediciones concretas:',
            '',
            '- Grafo de ~10.000 nodos → ~50-70s por prueba del plugin.',
            '- Grafo de ~2.000 nodos → ~15-18s por prueba.',
            '',
            'El problema es la cantidad de nodos, no el código de las',
            'pruebas ni el guardado. El grafo crece porque el piloto no',
            'elimina bien los nodos cuando se cancela o elimina una',
            'entidad: los descuelga del contenedor pero no los destruye.',
            'Como el framework no permite eliminar un nodo con referencias',
            'entrantes, esos nodos quedan huérfanos. Con cada operación se',
            'acumulan.',
            '',
            'Hay dos frentes pendientes que interactúan:',
            '',
            '**(A) Limitación del framework.** El framework no puede',
            'cargar/guardar partes reducidas del grafo. Toda operación',
            'carga los N nodos enteros. Se discute a futuro; requiere',
            'revisar teoría de grafos y el modelo de persistencia. No se',
            'toca ahora.',
            '',
            '**(B) Falencias del piloto.** No elimina bien los nodos al',
            'cancelar cosas. En algunos casos NO hay que eliminar',
            '(datos del cliente que se conservan a propósito). En otros',
            'SÍ hay que eliminar (viaje, cliente, terminal, micro,',
            'cancelaciones, cupones).',
            '',
            '**Plan en tres fases:**',
            '',
            '**Fase 1 — Pestaña Grafo (diagnóstico). Implementada en v74p.**',
            '',
            'Vista de solo lectura para ver la fuga con los ojos. Ver §3.4.',
            'Es la herramienta que habilita las Fases 2 y 3.',
            '',
            '**Fase 2 — Auditoría de la fuga de nodos (pendiente, prioridad alta).**',
            '',
            'Con la pestaña Grafo como herramienta:',
            '',
            '1. Recorrer cada flujo de eliminación del piloto:',
            '   - Alta/baja de viaje (`eliminar_viaje`).',
            '   - Alta/baja de cliente (`eliminar_pasajero`).',
            '   - Alta/baja de terminal (`eliminar_terminal`).',
            '   - Alta/baja de micro (`eliminar_micro_de_viaje`).',
            '   - Alta/baja de vehículo (`eliminar_vehiculo`).',
            '   - Alta/baja de empresa (`eliminar_empresa`).',
            '   - Cancelación de venta (`cancelar_venta`).',
            '   - Cupones, rendiciones, liquidaciones, cancelaciones.',
            '2. Para cada uno, anotar:',
            '   - Qué nodos se desenlazan.',
            '   - Qué nodos deberían destruirse también (y hoy no se',
            '     destruyen).',
            '   - Qué nodos se conservan a propósito (ej.: datos del',
            '     cliente).',
            '3. Implementar los fixes en tandas chicas, midiendo el total',
            '   de nodos antes y después.',
            '4. Documentar los criterios en §8.6.1.',
            '',
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
            '',
            '### 8.6.1 Criterios de eliminación',
            '',
            '**Stub. A completar en Fase 2.**',
            '',
            'Cuando se cancela/elimina una entidad, no siempre hay que',
            'destruir los nodos asociados. Algunos se conservan a',
            'propósito (datos del cliente). Otros deben destruirse',
            '(viaje, micro, terminal). Esta sección documenta el criterio',
            'por tipo de entidad.',
            '',
            '**Criterio general (a validar caso por caso):**',
            '',
            '- **Datos del cliente (pasajero, comprador):** se conservan',
            '  aunque la venta se cancele. El cliente puede volver a',
            '  comprar.',
            '- **Entidades operativas (viaje, micro, venta, cupón,',
            '  terminal):** al eliminarse, sus nodos deberían destruirse.',
            '  Los nodos que cuelgan de ellas también (asientos de copia,',
            '  cupones, etc.) salvo que estén referenciados desde otro',
            '  lado.',
            '- **Nodos "hijos" (asientos, cupones, etc.):** se destruyen',
            '  junto con su padre, salvo que tengan referencias',
            '  entrantes (en cuyo caso primero se desenlazan las',
            '  referencias).',
            '',
            '**Pendiente:** documentar el criterio por entidad concreta',
            'después de la auditoría.',
            '',
            '---',
            '',
            '## 9. PATRONES DE CÓDIGO DEL PILOTO',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 cabecera
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 cabecera a v74p',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74o',
            '(botón "Limpiar pasajeros de prueba" en la pestaña',
            'Pasajeros/Clientes. Nueva función',
            '`limpiar_pasajeros_de_prueba` en `Pasajero.php` y subacción',
            '`pasajeros/limpiar_prueba`. Los botones de limpieza (viajes',
            'y pasajeros) ahora aparecen solo si el admin está en modo',
            'pruebas (`Entorno::es_pruebas()`). Nuevo endpoint',
            '`entorno/info` y bandera `window.entorno_es_pruebas`',
            'inyectada por `index.php`).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74p',
            '(pestaña "Grafo", Fase 1 del plan de optimización del grafo.',
            'Visible solo para admin y soporte. Vista de solo lectura.',
            'Backend: 3 comandos nuevos en el `Controlador`',
            '(`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados',
            'desde `registrar_comandos_grafo()`, ejecutados vía',
            '`Controlador::ejecutar_comando()` sin usar el motor. Módulo',
            '`grafo` en el enrutador. Frontend nuevo: `Aplicacion/grafo.js`.',
            'Se agregaron §3.4 (pestaña Grafo), §8.6 (plan de las tres',
            'fases) y §8.6.1 (criterios de eliminación, stub)).',
            'Antes: v1.5piloto.74o',
            '(botón "Limpiar pasajeros de prueba" en la pestaña',
            'Pasajeros/Clientes. Nueva función',
            '`limpiar_pasajeros_de_prueba` en `Pasajero.php` y subacción',
            '`pasajeros/limpiar_prueba`. Los botones de limpieza (viajes',
            'y pasajeros) ahora aparecen solo si el admin está en modo',
            'pruebas (`Entorno::es_pruebas()`). Nuevo endpoint',
            '`entorno/info` y bandera `window.entorno_es_pruebas`',
            'inyectada por `index.php`).',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §12 agregar v74p al estado',
        'buscar' => [
            '- Cerramos en v74o el botón "Limpiar pasajeros de prueba"',
            '  para el admin (solo en modo pruebas). Criterio: email',
            '  termina en `@test.local`. Conserva los que tienen',
            '  referencias entrantes. También: `index.php` establece',
            '  `Entorno::MODO_PRUEBAS`/`MODO_PRODUCCION` según',
            '  `Conf::LOCAL`, e inyecta `window.entorno_es_pruebas` en',
            '  el HTML. Los dos botones de limpieza (viajes y pasajeros)',
            '  aparecen solo si el admin está en modo pruebas.',
            '- **Decisión anotada como pendiente de prioridad alta:**',
            '  **fuga de nodos**. Cuando se elimina una venta, un',
            '  pasajero, un viaje o cualquier entidad, el nodo se',
            '  descuelga del contenedor pero no se destruye. Como el',
            '  framework no permite eliminar un nodo con referencias',
            '  entrantes, esos nodos quedan huérfanos y el grafo crece',
            '  indefinidamente. Afecta la performance de todo. Los',
            '  botones de limpieza son paliativos. La solución de fondo',
            '  requiere auditar cada flujo de eliminación y desenlazar',
            '  progresivamente antes de llamar a `Nodo::eliminar`.',
            '  También conviene revisar si el framework podría soportar',
            '  carga parcial del grafo (traer solo la rama de interés',
            '  en vez del grafo entero).',
        ],
        'reemplazar' => [
            '- Cerramos en v74p la Fase 1 del plan de optimización del',
            '  grafo: pestaña "Grafo" (solo admin y soporte). Vista de',
            '  solo lectura con totales, alcanzables vs huérfanos, top',
            '  de referencias, tabla filtrable y modal de detalle por',
            '  nodo. Backend: 3 comandos en el `Controlador`',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`).',
            '  Frontend: `Aplicacion/grafo.js`.',
            '- **Decisión anotada como pendiente de prioridad alta:**',
            '  **fuga de nodos**. Cuando se elimina una venta, un',
            '  pasajero, un viaje o cualquier entidad, el nodo se',
            '  descuelga del contenedor pero no se destruye. Como el',
            '  framework no permite eliminar un nodo con referencias',
            '  entrantes, esos nodos quedan huérfanos y el grafo crece',
            '  indefinidamente. Medición: grafo de 10.000 nodos → 50-70s',
            '  por prueba; grafo de 2.000 nodos → 15-18s por prueba.',
            '  La pestaña Grafo es la herramienta de diagnóstico para la',
            '  Fase 2 (auditoría de la fuga). Ver §8.6.',
            '- **Plan de optimización del grafo (3 fases):** ver §8.6.',
            '  Fase 1 implementada en v74p (pestaña Grafo). Fase 2',
            '  pendiente (prioridad alta): auditoría de la fuga por',
            '  flujo de eliminación + implementación de fixes + criterios',
            '  en §8.6.1. Fase 3 pendiente (prioridad media): iteradores',
            '  persistentes, cacheo de contadores, eventual carga parcial',
            '  del grafo (esta última requiere tocar el framework).',
            '- **Frente de framework (no se toca ahora):** el framework',
            '  no puede cargar/guardar partes reducidas del grafo. Un',
            '  nodo puede estar referenciado desde más de un lado, así',
            '  que no hay un árbol natural de pertenencia. Requiere',
            '  revisar teoría de grafos. Se retoma en una sesión del',
            '  framework.',
            '- Cerramos en v74o el botón "Limpiar pasajeros de prueba"',
            '  para el admin (solo en modo pruebas). Criterio: email',
            '  termina en `@test.local`. Conserva los que tienen',
            '  referencias entrantes. También: `index.php` establece',
            '  `Entorno::MODO_PRUEBAS`/`MODO_PRODUCCION` según',
            '  `Conf::LOCAL`, e inyecta `window.entorno_es_pruebas` en',
            '  el HTML. Los dos botones de limpieza (viajes y pasajeros)',
            '  aparecen solo si el admin está en modo pruebas.',
        ],
    ],

    // ========================================================
    // prompt_piloto.md — §13 cierre
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt: §13 estado a v74p',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74o (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Fixes de v74k',
            'endurecen el alta de micro. Fix de v74m: `guardar_ambos`',
            'deja de guardar el JSON de respaldo automático. Fix de',
            'v74n: botón "Limpiar viajes de prueba". Fix de v74o: botón',
            '"Limpiar pasajeros de prueba" y control de modo',
            '(`Entorno::es_pruebas()`). El plugin de pruebas',
            '(`iteradoresJS/`, v1.5plugin.5d) tiene 29 pruebas',
            'corriendo.',
            '',
            '**Deuda técnica pendiente (prioridad alta):**',
            '**fuga de nodos**. Cuando se elimina una venta, un pasajero,',
            'un viaje o cualquier entidad, el nodo se descuelga del',
            'contenedor pero no se destruye. Como el framework no',
            'permite eliminar un nodo con referencias entrantes, esos',
            'nodos quedan huérfanos y el grafo crece indefinidamente.',
            'La aplicación se vuelve lenta porque todo itera sobre el',
            'grafo completo. La limpieza manual (botones de admin) es',
            'paliativa. La solución requiere auditar cada flujo de',
            'eliminación y desenlazar progresivamente antes de llamar',
            'a `Nodo::eliminar`. Requiere también revisar la',
            'arquitectura del framework para permitir carga parcial',
            'del grafo (cargar solo la rama de interés).',
            '',
            '**Deuda técnica pendiente (prioridad media):**',
            '`formatear_viaje` en `Viaje.php` escala como O(V × W): por',
            'cada viaje, recorre todas las ventas del dueño. Optimización',
            'real pendiente: índice de ventas por viaje + cacheo de',
            'contadores.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74p (framework 1.5i.7f).',
            'Todo funcional. Fixes de v74k a v74o acumulados. Fix de',
            'v74p: pestaña "Grafo" (Fase 1 del plan de optimización).',
            'El plugin de pruebas (`iteradoresJS/`, v1.5plugin.5d)',
            'tiene 29 pruebas corriendo.',
            '',
            '**Plan de optimización del grafo (ver §8.6):**',
            '',
            '- **Fase 1 — Pestaña Grafo.** Implementada en v74p.',
            '- **Fase 2 — Auditoría de la fuga de nodos.** Pendiente,',
            '  prioridad alta. Recorrer cada flujo de eliminación (viaje,',
            '  cliente, terminal, micro, venta, cancelación, cupón) y',
            '  anotar qué nodos quedan huérfanos. Implementar los fixes.',
            '  Documentar criterios en §8.6.1.',
            '- **Fase 3 — Optimizaciones.** Pendiente, prioridad media.',
            '  Iteradores persistentes, cacheo de contadores',
            '  (`formatear_viaje`), eventual carga parcial del grafo.',
            '',
            '**Mediciones concretas de la fuga:**',
            '',
            '- Grafo de ~10.000 nodos → ~50-70s por prueba del plugin.',
            '- Grafo de ~2.000 nodos → ~15-18s por prueba.',
            '',
            '**Deuda técnica de framework (prioridad alta, no se toca',
            'ahora):** el framework no puede cargar/guardar partes',
            'reducidas del grafo. Un nodo puede estar referenciado desde',
            'más de un lado, así que no hay un árbol natural de',
            'pertenencia. Se retoma en una sesión del framework.',
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