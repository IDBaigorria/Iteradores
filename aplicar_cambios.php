<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77e (Fase B2.3.4 del modelo topológico).
 *   - Venta.php: confirmar_venta_actual y cancelar_venta escriben
 *     en AMBOS árboles paralelos cuando el contexto es un compartido.
 *     Resolución de terminal/dueño por ID especial. Nuevo helper
 *     _desenlazar_venta_de_arbol.
 *   - Viaje.php: _contexto_terminal por ID especial.
 *   - index.php: bump.
 *   - prompts/plan_actual.md: registro.
 *
 * Sin cambio de comportamiento hoy: el enrutador sigue pasando el
 * nodo del dueño real como contexto. Se activa con B2.3.5.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Venta.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: bump @version a 1.5piloto.77e',
        'buscar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.77b',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.14',
            ' * @version   1.5piloto.77e',
        ],
    ],

    // ============================================================
    // Venta.php — confirmar_venta_actual: resolver terminal por ID
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'confirmar_venta_actual: resolver terminal por ID especial',
        'buscar' => [
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return [\'exito\' => false, \'error\' => \'No hay usuarios registrados\'];',
            '',
            '    $nodo_terminal = $raiz_usuarios->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal) return [\'exito\' => false, \'error\' => \'Terminal no encontrada\'];',
        ],
        'reemplazar' => [
            '    // Fase B2.3.4: resolver el terminal por ID especial',
            '    // `us_<nombre>` en vez de la raíz global `usuarios`',
            '    // (compatible con carga parcial por contextos).',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if (!$nodo_terminal) return [\'exito\' => false, \'error\' => \'Terminal no encontrada\'];',
        ],
    ],

    // ============================================================
    // Venta.php — confirmar_venta_actual: insertar en ambos árboles
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'confirmar_venta_actual: insertar en ambos árboles paralelos',
        'buscar' => [
            '    // Insertar venta en el árbol de ventas del dueño usando _hmi.',
            '    // Fase B2.2.2: se navega por el contexto del terminal para',
            '    // que la venta se inserte en el contenedor correcto.',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno);',
            '    if (!$contenedor_ventas) {',
            '        return [\'exito\' => false, \'error\' => \'No se pudo obtener contenedor de ventas\'];',
            '    }',
            '    _hmi($contenedor_ventas, $nodo_venta);',
        ],
        'reemplazar' => [
            '    // Insertar la venta en el/los árboles correspondientes.',
            '    // Fase B2.3.4: si el contexto es un compartido, la venta',
            '    // participa en DOS árboles paralelos (mismo nodo físico):',
            '    // el del dueño (default) y el del terminal que la vendió',
            '    // (parametrizado). Orden: dueño primero, compartido después.',
            '    $nombres_arbol = _nombres_arbol_para_contexto($nodo_dueno, $nombre_terminal);',
            '',
            '    if ($nombres_arbol === null) {',
            '        // Contexto = nodo del dueño real (o admin). Insertar',
            '        // solo en el árbol del dueño, con enlaces default.',
            '        $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno);',
            '        if (!$contenedor_ventas) {',
            '            return [\'exito\' => false, \'error\' => \'No se pudo obtener contenedor de ventas\'];',
            '        }',
            '        _hmi($contenedor_ventas, $nodo_venta);',
            '    } else {',
            '        // Contexto = compartido del terminal. Insertar en',
            '        // AMBOS árboles.',
            '        // (1) Árbol del dueño real, default.',
            '        $nodo_dueno_real = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '        if ($nodo_dueno_real) {',
            '            $contenedor_ventas_dueno = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno_real);',
            '            if ($contenedor_ventas_dueno) {',
            '                _hmi($contenedor_ventas_dueno, $nodo_venta);',
            '            }',
            '        }',
            '        // (2) Árbol del compartido, parametrizado.',
            '        $contenedor_ventas_compartido = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno);',
            '        if ($contenedor_ventas_compartido) {',
            '            _hmi($contenedor_ventas_compartido, $nodo_venta, $nombres_arbol);',
            '        }',
            '    }',
        ],
    ],

    // ============================================================
    // Venta.php — cancelar_venta: resolver dueño por ID especial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'cancelar_venta: resolver dueño por ID especial',
        'buscar' => [
            '    // 1b. Revertir montos del dueño (parte rendida que puede cubrir).',
            '    $raiz_usuarios_c = Nodo::nodo_por_id(\'usuarios\');',
            '    $nodo_dueno_c = $raiz_usuarios_c ? $raiz_usuarios_c->adyacente($nombre_dueno) : null;',
        ],
        'reemplazar' => [
            '    // 1b. Revertir montos del dueño (parte rendida que puede cubrir).',
            '    // Fase B2.3.4: ID especial.',
            '    $nodo_dueno_c = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
        ],
    ],

    // ============================================================
    // Venta.php — cancelar_venta: desenlazar de ambos árboles
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'cancelar_venta: desenlazar de ambos árboles paralelos',
        'buscar' => [
            '    // 7. Desenlazar la venta del árbol del dueño.',
            '    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '    if ($contenedor_ventas) {',
            '        $anterior = null;',
            '        $actual = hmi($contenedor_ventas);',
            '        $seg = 0;',
            '        while ($actual && $seg < 1000) {',
            '            if ($actual->id() === $nodo_venta->id()) {',
            '                if ($anterior) {',
            '                    $siguiente = hd($actual);',
            '                    if ($siguiente) {',
            '                        $anterior->_adyacente_en($siguiente, \'hd\', true);',
            '                    } else {',
            '                        $anterior->eliminar_adyacente(\'hd\');',
            '                    }',
            '                } else {',
            '                    $siguiente = hd($actual);',
            '                    if ($siguiente) {',
            '                        $contenedor_ventas->_adyacente_en($siguiente, \'hmi\', true);',
            '                    } else {',
            '                        $contenedor_ventas->eliminar_adyacente(\'hmi\');',
            '                    }',
            '                }',
            '                break;',
            '            }',
            '            $anterior = $actual;',
            '            $actual = hd($actual);',
            '            $seg++;',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    // 7. Desenlazar la venta de los árboles paralelos.',
            '    // Fase B2.3.4: la venta puede estar en DOS árboles',
            '    // (dueño + compartido del terminal que la vendió).',
            '    // Orden inverso al de la inserción: compartido primero,',
            '    // después el dueño.',
            '    $nodo_terminal_venta = $nodo_venta->adyacente(\'terminal\');',
            '    $nombre_terminal_venta = $nodo_terminal_venta ? (string)$nodo_terminal_venta->dato() : \'\';',
            '',
            '    // 7a. Desenlazar del árbol del compartido (si la venta',
            '    // fue hecha por un terminal y su dueño tiene el',
            '    // compartido marcado con `_es_compartido`).',
            '    if ($nombre_terminal_venta !== \'\') {',
            '        $nodo_terminal_resuelto = Nodo::nodo_por_id(\'us_\' . $nombre_terminal_venta);',
            '        if ($nodo_terminal_resuelto) {',
            '            $nodo_dueno_ctx = $nodo_terminal_resuelto->adyacente(\'dueno\');',
            '            if ($nodo_dueno_ctx && $nodo_dueno_ctx->adyacente(\'_es_compartido\')) {',
            '                $nombres_comp = _nombres_arbol_para_contexto($nodo_dueno_ctx, $nombre_terminal_venta);',
            '                if ($nombres_comp !== null) {',
            '                    $cont_comp = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno_ctx);',
            '                    _desenlazar_venta_de_arbol($cont_comp, $nodo_venta, $nombres_comp);',
            '                }',
            '            }',
            '        }',
            '    }',
            '',
            '    // 7b. Desenlazar del árbol del dueño (default).',
            '    $nodo_dueno_real = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if ($nodo_dueno_real) {',
            '        $cont_dueno = obtener_contenedor_ventas_dueno($nombre_dueno, $nodo_dueno_real);',
            '        _desenlazar_venta_de_arbol($cont_dueno, $nodo_venta, null);',
            '    }',
        ],
    ],

    // ============================================================
    // Venta.php — cancelar_venta: limpiar parametrizados residuales
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'cancelar_venta: limpiar enlaces parametrizados residuales',
        'buscar' => [
            '    $nodo_venta->eliminar_adyacente(\'hmi\');',
            '    $nodo_venta->eliminar_adyacente(\'hd\');',
            '    $nodo_venta->eliminar_adyacente(\'p\');',
        ],
        'reemplazar' => [
            '    $nodo_venta->eliminar_adyacente(\'hmi\');',
            '    $nodo_venta->eliminar_adyacente(\'hd\');',
            '    $nodo_venta->eliminar_adyacente(\'p\');',
            '    // Fase B2.3.4: también los parametrizados por si quedaron',
            '    // residuales (defensivo).',
            '    if ($nombre_terminal_venta !== \'\') {',
            '        $nodo_venta->eliminar_adyacente(\'hmi_\' . $nombre_terminal_venta);',
            '        $nodo_venta->eliminar_adyacente(\'hd_\' . $nombre_terminal_venta);',
            '        $nodo_venta->eliminar_adyacente(\'p_\' . $nombre_terminal_venta);',
            '    }',
        ],
    ],

    // ============================================================
    // Venta.php — _calcular_cobertura_dueno: ID especial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => '_calcular_cobertura_dueno: resolver dueño por ID especial',
        'buscar' => [
            'function _calcular_cobertura_dueno(string $nombre_dueno, float $monto_ef, float $monto_ba): array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return [\'cubierto_ef\' => 0.0, \'cubierto_ba\' => 0.0, \'no_cubierto_ef\' => $monto_ef, \'no_cubierto_ba\' => $monto_ba];',
            '    $nodo = $raiz->adyacente($nombre_dueno);',
            '    if (!$nodo) return [\'cubierto_ef\' => 0.0, \'cubierto_ba\' => 0.0, \'no_cubierto_ef\' => $monto_ef, \'no_cubierto_ba\' => $monto_ba];',
        ],
        'reemplazar' => [
            'function _calcular_cobertura_dueno(string $nombre_dueno, float $monto_ef, float $monto_ba): array {',
            '    // Fase B2.3.4: ID especial.',
            '    $nodo = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo) return [\'cubierto_ef\' => 0.0, \'cubierto_ba\' => 0.0, \'no_cubierto_ef\' => $monto_ef, \'no_cubierto_ba\' => $monto_ba];',
        ],
    ],

    // ============================================================
    // Venta.php — _crear_nodo_cancelacion: ID especial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => '_crear_nodo_cancelacion: resolver dueño por ID especial',
        'buscar' => [
            'function _crear_nodo_cancelacion(string $nombre_dueno, string $id_venta, string $motivo, Nodo $nodo_venta, array $desglose, array $cubierto, int $asientos_liberados): string {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return \'\';',
            '    $nodo_dueno = $raiz->adyacente($nombre_dueno);',
            '    if (!$nodo_dueno) return \'\';',
        ],
        'reemplazar' => [
            'function _crear_nodo_cancelacion(string $nombre_dueno, string $id_venta, string $motivo, Nodo $nodo_venta, array $desglose, array $cubierto, int $asientos_liberados): string {',
            '    // Fase B2.3.4: ID especial.',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return \'\';',
        ],
    ],

    // ============================================================
    // Venta.php — helper _desenlazar_venta_de_arbol
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: helper _desenlazar_venta_de_arbol',
        'buscar' => [
            '/**',
            ' * Calcula cuánto hay que devolver por cada método al cancelar una',
            ' * venta, leyendo los cupones pagados. Cada cupón aporta su monto al',
            ' * método que tenga asignado, o al de la venta si no tiene uno propio.',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array{efectivo: float, banco: float, total: float}',
            ' */',
            'function _calcular_devolucion_venta(Nodo $nodo_venta): array {',
        ],
        'reemplazar' => [
            '/**',
            ' * Desenlaza un nodo venta de un árbol (del dueño o de un',
            ' * compartido). El árbol puede usar nombres default',
            ' * (`hmi`/`hd`/`p`) o parametrizados por terminal',
            ' * (`hmi_<term>`/`hd_<term>`/`p_<term>`).',
            ' *',
            ' * Fase B2.3.4. Devuelve true si la venta estaba en el',
            ' * árbol y se desenlazó. Si no estaba, devuelve false y no',
            ' * modifica nada.',
            ' *',
            ' * @param Nodo|null $contenedor Contenedor raíz del árbol.',
            ' * @param Nodo      $nodo_venta Nodo venta a desenlazar.',
            ' * @param array|null $nombres   Nombres de enlace (`hmi`/`hd`/`p`) o',
            ' *                              null para usar los default.',
            ' * @return bool',
            ' */',
            'function _desenlazar_venta_de_arbol(?Nodo $contenedor, Nodo $nodo_venta, ?array $nombres): bool {',
            '    if (!$contenedor) return false;',
            '    $n = $nombres !== null ? $nombres : [\'hmi\' => \'hmi\', \'hd\' => \'hd\', \'p\' => \'p\'];',
            '    $anterior = null;',
            '    $actual = $contenedor->adyacente($n[\'hmi\']);',
            '    $seg = 0;',
            '    while ($actual && $seg < 2000) {',
            '        if ($actual->id() === $nodo_venta->id()) {',
            '            $siguiente = $actual->adyacente($n[\'hd\']);',
            '            if ($anterior) {',
            '                if ($siguiente) {',
            '                    $anterior->_adyacente_en($siguiente, $n[\'hd\'], true);',
            '                } else {',
            '                    $anterior->eliminar_adyacente($n[\'hd\']);',
            '                }',
            '            } else {',
            '                if ($siguiente) {',
            '                    $contenedor->_adyacente_en($siguiente, $n[\'hmi\'], true);',
            '                } else {',
            '                    $contenedor->eliminar_adyacente($n[\'hmi\']);',
            '                }',
            '            }',
            '            // Desenlazar el enlace `p` del nodo venta al contenedor.',
            '            $nodo_venta->eliminar_adyacente($n[\'p\']);',
            '            return true;',
            '        }',
            '        $anterior = $actual;',
            '        $actual = $actual->adyacente($n[\'hd\']);',
            '        $seg++;',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Calcula cuánto hay que devolver por cada método al cancelar una',
            ' * venta, leyendo los cupones pagados. Cada cupón aporta su monto al',
            ' * método que tenga asignado, o al de la venta si no tiene uno propio.',
            ' *',
            ' * @param Nodo $nodo_venta',
            ' * @return array{efectivo: float, banco: float, total: float}',
            ' */',
            'function _calcular_devolucion_venta(Nodo $nodo_venta): array {',
        ],
    ],

    // ============================================================
    // Viaje.php — bump @version
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: bump @version a 1.5piloto.77e',
        'buscar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.76w',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.8',
            ' * @version   1.5piloto.77e',
        ],
    ],

    // ============================================================
    // Viaje.php — _contexto_terminal por ID especial
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje.php: _contexto_terminal por ID especial',
        'buscar' => [
            'function _contexto_terminal(string $nombre_terminal) {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return null;',
            '    $nodo_terminal = $raiz->adyacente($nombre_terminal);',
            '    if (!$nodo_terminal) return null;',
            '    return $nodo_terminal->adyacente(\'dueno\');',
            '}',
        ],
        'reemplazar' => [
            'function _contexto_terminal(string $nombre_terminal) {',
            '    // Fase B2.3.4: resolver el terminal por ID especial',
            '    // `us_<nombre>` en vez de la raíz global `usuarios`.',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if (!$nodo_terminal) return null;',
            '    return $nodo_terminal->adyacente(\'dueno\');',
            '}',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77e',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77b',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77e',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77e',
        'buscar' => [
            '**Tanda actual:** v77d-05 (documentación: refresh del plan',
            'actual + preparación de B2.3.4).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77e (Fase B2.3.4: escrituras de venta',
            'para dos árboles paralelos).',
            '',
            '**v77e — Fase B2.3.4.**',
            '',
            '- `Venta.php`:',
            '  - `confirmar_venta_actual` resuelve el terminal por',
            '    `Nodo::nodo_por_id(\'us_\' . $nombre_terminal)`.',
            '  - `confirmar_venta_actual` inserta la venta en AMBOS',
            '    árboles paralelos si el contexto tiene `_es_compartido`:',
            '    primero el del dueño (default), después el del compartido',
            '    (parametrizado).',
            '  - `cancelar_venta` resuelve el dueño por ID especial.',
            '  - `cancelar_venta` desenlaza del compartido primero y del',
            '    árbol del dueño después (orden inverso al de la',
            '    inserción). Nuevo helper `_desenlazar_venta_de_arbol`.',
            '  - `cancelar_venta` limpia también los enlaces',
            '    parametrizados residuales.',
            '  - `_calcular_cobertura_dueno` y `_crear_nodo_cancelacion`',
            '    resuelven el dueño por ID especial.',
            '- `Viaje.php`: `_contexto_terminal` resuelve el terminal',
            '  por ID especial.',
            '- **Sin cambio de comportamiento hoy.** El enrutador sigue',
            '  pasando el nodo del dueño real como contexto, entonces',
            '  `_nombres_arbol_para_contexto` devuelve null y no se',
            '  activa la rama nueva. Se activa con B2.3.5.',
            '- **No toca el enrutador.** Los 6 chequeos de nivel siguen',
            '  usando `nodo_por_id(\'usuarios\')`. Eso es B3.',
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