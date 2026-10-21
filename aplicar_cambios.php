<?php
/**
 * Aplicador — B3.3c: cerrar los 3 helpers que iteran `usuarios`.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81j';
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

    // ============================================================
    // Venta.php — obtener_cancelacion_por_id + docblock de _buscar_venta_por_id
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: docblock de _buscar_venta_por_id (nota del pendiente)',
        'buscar' => [
            '/**',
            ' * Busca una venta por ID y devuelve el nodo junto con el nombre del',
            ' * dueño. Devuelve [null, \'\'] si no la encuentra.',
            ' *',
            ' * @param string $id_venta',
            ' * @return array{0: ?Nodo, 1: string}',
            ' */',
        ],
        'reemplazar' => [
            '/**',
            ' * Busca una venta por ID y devuelve el nodo junto con el nombre del',
            ' * dueño. Devuelve [null, \'\'] si no la encuentra.',
            ' *',
            ' * Prioridad de búsqueda:',
            ' *   1. Si viene $nombre_terminal: contexto del terminal (compartido).',
            ' *   2. Si viene $nombre_dueno (sin terminal): us_<dueño> directo.',
            ' *   3. Ambos null: recorrido por `usuarios` (carga total).',
            ' *',
            ' * La rama 3 (fallback) solo se activa cuando $nombre_solicitante',
            ' * está vacío → carga total → `usuarios` disponible. Los llamadores',
            ' * del Enrutador siempre pasan al menos uno de los dos vía',
            ' * `_nombre_terminal_para_busqueda` + `_nombre_dueno_solicitante`.',
            ' *',
            ' * Pendiente anotado en plan_actual.md: convertir `usuarios` en un',
            ' * contenedor de nombres (dato string) para que la rama 3 no',
            ' * arrastre los subgrafos completos de todos los usuarios. Ese',
            ' * cambio es de esquema y va en una sesión del framework.',
            ' *',
            ' * @param string      $id_venta',
            ' * @param string|null $nombre_terminal',
            ' * @param string|null $nombre_dueno',
            ' * @return array{0: ?Nodo, 1: string}',
            ' * @version 1.5piloto.81j',
            ' */',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Venta.php: obtener_cancelacion_por_id con contexto del solicitante',
        'buscar' => [
            '/**',
            ' * Busca una cancelación por su id en todos los dueños.',
            ' *',
            ' * @param string $id_cancelacion',
            ' * @return array|null',
            ' */',
            'function obtener_cancelacion_por_id(string $id_cancelacion): ?array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return null;',
            '    foreach ($raiz->adyacentes() as $nombre_dueno => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_cancelacion) {',
            '                return formatear_cancelacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
        'reemplazar' => [
            '/**',
            ' * Busca una cancelación por su id.',
            ' *',
            ' * Fase B3.3c (v1.5piloto.81j): acepta contexto opcional para no',
            ' * depender de la raíz `usuarios` en carga parcial.',
            ' *',
            ' *   - Terminal: navega por el árbol del compartido.',
            ' *   - Dueño/admin/soporte: navega por us_<dueño>.',
            ' *   - Ambos null: fallback al recorrido por `usuarios` (carga total).',
            ' *',
            ' * Nota: el contenedor `cancelaciones` del compartido usa nombres',
            ' * default (hmi/hd/p), igual que el del dueño. No hay árboles',
            ' * paralelos acá (a diferencia de ventas).',
            ' *',
            ' * @param string      $id_cancelacion',
            ' * @param string|null $nombre_dueno',
            ' * @param string|null $nombre_terminal',
            ' * @return array|null',
            ' * @version 1.5piloto.81j',
            ' */',
            'function obtener_cancelacion_por_id(string $id_cancelacion, ?string $nombre_dueno = null, ?string $nombre_terminal = null): ?array {',
            '    // Caso 1: terminal → árbol del compartido.',
            '    if ($nombre_terminal !== null && $nombre_terminal !== \'\') {',
            '        $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '        if (!$nodo_terminal) return null;',
            '        $nodo_ctx = $nodo_terminal->adyacente(\'dueno\');',
            '        if (!$nodo_ctx) return null;',
            '        $cont = $nodo_ctx->adyacente(\'cancelaciones\');',
            '        if (!$cont) return null;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_cancelacion) {',
            '                return formatear_cancelacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '        return null;',
            '    }',
            '',
            '    // Caso 2: dueño/admin/soporte → us_<dueño>.',
            '    if ($nombre_dueno !== null && $nombre_dueno !== \'\') {',
            '        $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '        if (!$nodo_dueno) return null;',
            '        $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
            '        if (!$cont) return null;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_cancelacion) {',
            '                return formatear_cancelacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '        return null;',
            '    }',
            '',
            '    // Caso 3: fallback por `usuarios` (carga total, sin solicitante).',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return null;',
            '    foreach ($raiz->adyacentes() as $nombre_dueno_iter => $nodo_dueno) {',
            '        $nivel = $nodo_dueno->adyacente(\'nivel\');',
            '        if (!$nivel || $nivel->dato() !== \'dueno\') continue;',
            '        $cont = $nodo_dueno->adyacente(\'cancelaciones\');',
            '        if (!$cont) continue;',
            '        $actual = hmi($cont);',
            '        $seg = 0;',
            '        while ($actual && $seg < 2000) {',
            '            $seg++;',
            '            if ($actual->dato() === $id_cancelacion) {',
            '                return formatear_cancelacion_completa($actual);',
            '            }',
            '            $actual = hd($actual);',
            '        }',
            '    }',
            '    return null;',
            '}',
        ],
    ],

    // ============================================================
    // Pasajero.php — obtener_pasajero_por_dni (rama sin contexto)
    // + eliminar obtener_raiz_pasajeros
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: comentario de obtener_pasajero_por_dni',
        'buscar' => [
            '    // Obtener ventas relacionadas.',
            '    //',
            '    // Con contexto (terminal → compartido), navegar el',
            '    // árbol del contexto con nombres parametrizados.',
            '    // Sin contexto (dueño, admin, impresión), iterar sobre',
            '    // todos los dueños desde `usuarios` (comportamiento',
            '    // actual). Bug §1.5.12: con carga parcial del',
            '    // terminal, `usuarios` no está en memoria y el bloque',
            '    // devolvía lista vacía.',
        ],
        'reemplazar' => [
            '    // Obtener ventas relacionadas.',
            '    //',
            '    // Con contexto (terminal → compartido): navegar el árbol del',
            '    // contexto con nombres parametrizados.',
            '    //',
            '    // Sin contexto (dueño/admin/soporte/impresión): navegar directo',
            '    // por us_<dueño>. Fase B3.3c (v1.5piloto.81j): antes se iteraba',
            '    // sobre todos los dueños desde `usuarios`, lo que mezclaba',
            '    // ventas de distintos dueños cuando dos pasajeros compartían DNI',
            '    // (mismo DNI, distinto nodo pasajero en cada dueño). Ahora el',
            '    // historial se restringe al dueño del pasajero.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: rama sin contexto de obtener_pasajero_por_dni',
        'buscar' => [
            '    } else {',
            '        // Sin contexto: iterar sobre todos los dueños.',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if ($raiz_usuarios) {',
            '            foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno_iter => $nodo_dueno) {',
            '                $nodo_nivel = $nodo_dueno->adyacente(\'nivel\');',
            '                if (!$nodo_nivel || $nodo_nivel->dato() !== \'dueno\') continue;',
            '',
            '                $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno_iter);',
            '                if (!$contenedor_ventas) continue;',
            '',
            '                $actual = hmi($contenedor_ventas);',
            '                while ($actual) {',
            '                    $venta_formateada = formatear_venta_para_pasajero($actual, $dni);',
            '                    if ($venta_formateada) {',
            '                        if ($venta_formateada[\'es_comprador\'] && $venta_formateada[\'es_pasajero\']) {',
            '                            $venta_formateada[\'rol\'] = \'ambos\';',
            '                        } elseif ($venta_formateada[\'es_comprador\']) {',
            '                            $venta_formateada[\'rol\'] = \'comprador\';',
            '                        } else {',
            '                            $venta_formateada[\'rol\'] = \'pasajero\';',
            '                        }',
            '                        $ventas[] = $venta_formateada;',
            '                    }',
            '                    $actual = hd($actual);',
            '                }',
            '            }',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '    } else {',
            '        // Fase B3.3c (v1.5piloto.81j): navegar directo por us_<dueño>.',
            '        //',
            '        // Antes se iteraba sobre TODOS los dueños desde la raíz',
            '        // `usuarios`, lo que mezclaba ventas de distintos dueños cuando',
            '        // dos pasajeros compartían DNI. El detalle de un pasajero',
            '        // mostraba compras de otros dueños.',
            '        //',
            '        // Ahora el historial se restringe al dueño del pasajero. Es el',
            '        // comportamiento correcto por topología: el pasajero es un',
            '        // nodo del dueño, y el historial de ventas vive en el árbol',
            '        // de ese dueño. La carga parcial ya no ve otros dueños.',
            '        $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);',
            '        if ($contenedor_ventas) {',
            '            $actual = hmi($contenedor_ventas);',
            '            $seg = 0;',
            '            while ($actual && $seg < 2000) {',
            '                $seg++;',
            '                $venta_formateada = formatear_venta_para_pasajero($actual, $dni);',
            '                if ($venta_formateada) {',
            '                    if ($venta_formateada[\'es_comprador\'] && $venta_formateada[\'es_pasajero\']) {',
            '                        $venta_formateada[\'rol\'] = \'ambos\';',
            '                    } elseif ($venta_formateada[\'es_comprador\']) {',
            '                        $venta_formateada[\'rol\'] = \'comprador\';',
            '                    } else {',
            '                        $venta_formateada[\'rol\'] = \'pasajero\';',
            '                    }',
            '                    $ventas[] = $venta_formateada;',
            '                }',
            '                $actual = hd($actual);',
            '            }',
            '        }',
            '    }',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Pasajero.php: eliminar obtener_raiz_pasajeros (código muerto)',
        'buscar' => [
            '/**',
            ' * Obtiene el nodo raíz de pasajeros (por compatibilidad).',
            ' */',
            'function obtener_raiz_pasajeros(): ?Nodo {',
            '    return obtener_contenedor_pasajeros_dueno(\'Parroquia_del_Carmen\');',
            '}',
        ],
        'reemplazar' => [
            '// v1.5piloto.81j: obtener_raiz_pasajeros() eliminada.',
            '// Era código muerto: solo se definía, nunca se llamaba (verificado',
            '// con grep sobre Aplicacion/, index.php). Tenía un dueño',
            '// hardcodeado (\'Parroquia_del_Carmen\') que ya no aplica.',
        ],
    ],

    // ============================================================
    // Impresion.php — 3 imprimir_informe_* con $nombre_dueno
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: imprimir_informe_cancelacion acepta $nombre_dueno',
        'buscar' => [
            'function imprimir_informe_cancelacion(string $id_cancelacion): void {',
            '    if ($id_cancelacion === \'\') {',
            '        echo "ID de cancelación no especificado";',
            '        return;',
            '    }',
            '',
            '    $c = obtener_cancelacion_por_id($id_cancelacion);',
        ],
        'reemplazar' => [
            'function imprimir_informe_cancelacion(string $id_cancelacion, ?string $nombre_dueno = null): void {',
            '    if ($id_cancelacion === \'\') {',
            '        echo "ID de cancelación no especificado";',
            '        return;',
            '    }',
            '',
            '    // Fase B3.3c: pasar el dueño para navegar directo por us_<dueño>',
            '    // en carga parcial.',
            '    $c = obtener_cancelacion_por_id($id_cancelacion, $nombre_dueno);',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: imprimir_informe_liquidacion acepta $nombre_dueno',
        'buscar' => [
            'function imprimir_informe_liquidacion(string $id_liquidacion, bool $ocultar_datos_generales = false): void {',
            '    if ($id_liquidacion === \'\') {',
            '        echo "ID de liquidación no especificado";',
            '        return;',
            '    }',
            '',
            '    $liq = obtener_liquidacion_por_id($id_liquidacion);',
        ],
        'reemplazar' => [
            'function imprimir_informe_liquidacion(string $id_liquidacion, bool $ocultar_datos_generales = false, ?string $nombre_dueno = null): void {',
            '    if ($id_liquidacion === \'\') {',
            '        echo "ID de liquidación no especificado";',
            '        return;',
            '    }',
            '',
            '    // Fase B3.3c: pasar el dueño para navegar directo por us_<dueño>.',
            '    $liq = obtener_liquidacion_por_id($id_liquidacion, $nombre_dueno);',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Impresion.php: imprimir_informe_rendicion acepta $nombre_dueno',
        'buscar' => [
            'function imprimir_informe_rendicion(string $id_rendicion): void {',
            '    if ($id_rendicion === \'\') {',
            '        echo "ID de rendición no especificado";',
            '        return;',
            '    }',
            '',
            '    $r = obtener_rendicion_por_id($id_rendicion);',
        ],
        'reemplazar' => [
            'function imprimir_informe_rendicion(string $id_rendicion, ?string $nombre_dueno = null): void {',
            '    if ($id_rendicion === \'\') {',
            '        echo "ID de rendición no especificado";',
            '        return;',
            '    }',
            '',
            '    // Fase B3.3c: pasar el dueño para navegar directo por us_<dueño>.',
            '    $r = obtener_rendicion_por_id($id_rendicion, $nombre_dueno);',
        ],
    ],

    // ============================================================
    // index.php — pasar $_GET['dueno'] a los 3 informes
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: pasar dueno a los 3 imprimir_informe_*',
        'buscar' => [
            '    // Caso especial: informe imprimible de una rendición.',
            '    // Se pasa el id_rendicion por GET.',
            '    if ($tipo === \'informe_rendicion\') {',
            '        imprimir_informe_rendicion($_GET[\'id_rendicion\'] ?? \'\');',
            '        exit;',
            '    }',
            '',
            '    // Caso especial: informe imprimible de una liquidación.',
            '    // Si el dueño es quien imprime, el frontend manda',
            '    // ocultar_datos_generales=1 para no mostrarle su propio nombre.',
            '    if ($tipo === \'informe_liquidacion\') {',
            '        $ocultar_datos = ($_GET[\'ocultar_datos_generales\'] ?? \'0\') === \'1\';',
            '        imprimir_informe_liquidacion($_GET[\'id_liquidacion\'] ?? \'\', $ocultar_datos);',
            '        exit;',
            '    }',
            '',
            '    // Caso especial: informe imprimible de una cancelación de compra.',
            '    if ($tipo === \'informe_cancelacion\') {',
            '        imprimir_informe_cancelacion($_GET[\'id_cancelacion\'] ?? \'\');',
            '        exit;',
            '    }',
        ],
        'reemplazar' => [
            '    // Fase B3.3c: pasar el dueño por GET a los informes. Con carga',
            '    // parcial (us_<dueño>), los helpers de búsqueda por id navegan',
            '    // directo por el dueño sin tocar la raíz `usuarios`.',
            '    $nombre_dueno_informe = $_GET[\'dueno\'] ?? null;',
            '    if ($nombre_dueno_informe === \'\') $nombre_dueno_informe = null;',
            '',
            '    // Caso especial: informe imprimible de una rendición.',
            '    // Se pasa el id_rendicion por GET.',
            '    if ($tipo === \'informe_rendicion\') {',
            '        imprimir_informe_rendicion($_GET[\'id_rendicion\'] ?? \'\', $nombre_dueno_informe);',
            '        exit;',
            '    }',
            '',
            '    // Caso especial: informe imprimible de una liquidación.',
            '    // Si el dueño es quien imprime, el frontend manda',
            '    // ocultar_datos_generales=1 para no mostrarle su propio nombre.',
            '    if ($tipo === \'informe_liquidacion\') {',
            '        $ocultar_datos = ($_GET[\'ocultar_datos_generales\'] ?? \'0\') === \'1\';',
            '        imprimir_informe_liquidacion($_GET[\'id_liquidacion\'] ?? \'\', $ocultar_datos, $nombre_dueno_informe);',
            '        exit;',
            '    }',
            '',
            '    // Caso especial: informe imprimible de una cancelación de compra.',
            '    if ($tipo === \'informe_cancelacion\') {',
            '        imprimir_informe_cancelacion($_GET[\'id_cancelacion\'] ?? \'\', $nombre_dueno_informe);',
            '        exit;',
            '    }',
        ],
    ],

    // ============================================================
    // Enrutador.php — cancelaciones/obtener + _contextos_para_operacion
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: cancelaciones/obtener con contexto del solicitante',
        'buscar' => [
            '                case \'obtener\':',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    if (empty($id_cancelacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de cancelación no especificado\']);',
            '                    }',
            '                    $canc = obtener_cancelacion_por_id($id_cancelacion);',
            '                    if ($canc) {',
            '                        responder_json([\'exito\' => true, \'cancelacion\' => $canc]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Cancelación no encontrada\']);',
            '                    }',
            '                    break;',
        ],
        'reemplazar' => [
            '                case \'obtener\':',
            '                    $id_cancelacion = $post[\'id_cancelacion\'] ?? \'\';',
            '                    if (empty($id_cancelacion)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'ID de cancelación no especificado\']);',
            '                    }',
            '                    // Fase B3.3c: navegar por el contexto del solicitante.',
            '                    // Terminal → compartido. Dueño/admin/soporte → us_<dueño>.',
            '                    $nombre_terminal_sol = _nombre_terminal_para_busqueda($nombre_solicitante, $post);',
            '                    $nombre_dueno_sol = _nombre_dueno_solicitante($nombre_solicitante, $post);',
            '                    $canc = obtener_cancelacion_por_id($id_cancelacion, $nombre_dueno_sol, $nombre_terminal_sol);',
            '                    if ($canc) {',
            '                        responder_json([\'exito\' => true, \'cancelacion\' => $canc]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Cancelación no encontrada\']);',
            '                    }',
            '                    break;',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: eliminar excepción B3.3c de _contextos_para_operacion',
        'buscar' => [
            '        // Fase 1c.2: los informes de cancelación, liquidación y',
            '        // rendición usan helpers que iteran sobre todos los dueños',
            '        // (B3.3c, postergado). Carga total hasta que se reescriban.',
            '        $tipo_imp = $get[\'tipo\'] ?? \'\';',
            '        $tipos_con_b33c = [\'informe_cancelacion\', \'informe_liquidacion\', \'informe_rendicion\'];',
            '        if (in_array($tipo_imp, $tipos_con_b33c, true)) {',
            '            return null;',
            '        }',
            '',
            '        return [\'us_\' . $dueno];',
        ],
        'reemplazar' => [
            '        // Fase B3.3c (v1.5piloto.81j): los informes de',
            '        // cancelación, liquidación y rendición ahora navegan',
            '        // directo por us_<dueño>. Se eliminó la excepción de',
            '        // carga total que existía acá.',
            '        return [\'us_\' . $dueno];',
        ],
    ],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

// --- Fase 1: aplicar reemplazos ---
$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

// --- Fase 2: bumps automáticos ---
$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

// --- Fase 3: escribir ---
$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";