<?php
/**
 * Aplicador — Tanda A: auditoría topológica del grafo SQL64.
 *
 * Crea Aplicacion/Reparacion/Auditoria.php (nuevo), agrega el
 * comando app:auditar_topologia a Comandos.php, y agrega el
 * endpoint ?auditar_topologia=1 en index.php. No toca el plugin.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.81p';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. Nuevo: Aplicacion/Reparacion/Auditoria.php
// ============================================================
[
    'tipo' => 'crear',
    'archivo' => 'Aplicacion/Reparacion/Auditoria.php',
    'descripcion' => 'Auditoría topológica de solo lectura (14 verificaciones)',
    'contenido' => [
        '<?php',
        '/**',
        ' * Auditoría topológica del grafo SQL64.',
        ' *',
        ' * Diagnóstico de solo lectura. Detecta aristas rotas,',
        ' * nodos huérfanos, compartidos incompletos, etc. NO',
        ' * corrige nada: los fixes van por ?reparar_topologia,',
        ' * ?reparar_arboles o scripts manuales.',
        ' *',
        ' * Se ejecuta desde el comando app:auditar_topologia,',
        ' * que a su vez invoca el endpoint ?auditar_topologia=1.',
        ' *',
        ' * Usa su propia conexión mysqli de solo lectura. No',
        ' * hereda de la persistencia y no la modifica.',
        ' *',
        ' * @package   Iteradores',
        ' * @since     1.5piloto.81p',
        ' * @version   1.5piloto.81p',
        ' */',
        '',
        'use Iteradores\\Nodos\\Nodo;',
        'use Iteradores\\Configuracion\\Conf;',
        '',
        '/**',
        ' * Punto de entrada. Devuelve el reporte completo.',
        ' *',
        ' * @param string $token Token del framework (para iterar nodos).',
        ' * @return array',
        ' */',
        'function auditar_topologia(string $token): array {',
        '    $nombre_app = (string)\\ConfiguracionApli::NOMBRE_APP;',
        '',
        '    $criticos = [];',
        '    $errores = [];',
        '    $warnings = [];',
        '',
        '    // ─── Verificaciones SQL (solo lectura) ─────────────',
        '    $sql = _auditoria_conectar();',
        '    $contextos = [];',
        '    if ($sql !== null) {',
        '        $criticos = array_merge($criticos, _auditoria_aristas_rotas($sql, $nombre_app));',
        '        $criticos = array_merge($criticos, _auditoria_mascaras_vacias($sql, $nombre_app));',
        '        $contextos = _auditoria_contextos_registrados($sql, $nombre_app);',
        '        $sql->close();',
        '    } else {',
        '        $warnings[] = [',
        '            "tipo" => "sin_db",',
        '            "detalle" => "No se pudo conectar a la DB. Solo se auditan las verificaciones en memoria.",',
        '        ];',
        '    }',
        '',
        '    // ─── Verificaciones en memoria ─────────────────────',
        '    $aristas_total = 0;',
        '    $estructura = _auditoria_cargar_estructura($token, $aristas_total);',
        '',
        '    $criticos = array_merge($criticos, _auditoria_admin_sin_usuarios($estructura));',
        '    $criticos = array_merge($criticos, _auditoria_terminal_dueno_real($estructura));',
        '    $criticos = array_merge($criticos, _auditoria_compartidos_incompletos($estructura));',
        '',
        '    $errores = array_merge($errores, _auditoria_ventas_sin_param($estructura));',
        '    $errores = array_merge($errores, _auditoria_ventas_sin_p_default($estructura));',
        '    $errores = array_merge($errores, _auditoria_buzones_con_contenido($estructura));',
        '    $errores = array_merge($errores, _auditoria_soporte_singular_legacy($estructura));',
        '    $errores = array_merge($errores, _auditoria_soporte_sin_publico($estructura));',
        '',
        '    $warnings = array_merge($warnings, _auditoria_huerfanos($estructura));',
        '    $warnings = array_merge($warnings, _auditoria_hijos_sin_p($estructura));',
        '    $warnings = array_merge($warnings, _auditoria_p_no_contenedor($estructura));',
        '    $warnings = array_merge($warnings, _auditoria_ciclos_hd($estructura));',
        '',
        '    return [',
        '        "exito" => true,',
        '        "resumen" => [',
        '            "superestructura" => $nombre_app,',
        '            "nodos_total" => count($estructura),',
        '            "aristas_total" => $aristas_total,',
        '            "contextos" => $contextos,',
        '            "criticos" => count($criticos),',
        '            "errores" => count($errores),',
        '            "warnings" => count($warnings),',
        '        ],',
        '        "criticos" => $criticos,',
        '        "errores" => $errores,',
        '        "warnings" => $warnings,',
        '    ];',
        '}',
        '',
        '// ============================================================',
        '// Conexión SQL de solo lectura',
        '// ============================================================',
        '',
        '/**',
        ' * Abre una conexión mysqli de solo lectura.',
        ' *',
        ' * No crea base de datos, no crea tablas, no transacciona.',
        ' * Solo lee Conf::SUPERESTRUCTURA_*. Funciona en local,',
        ' * pruebas y producción.',
        ' *',
        ' * @return \\mysqli|null',
        ' */',
        'function _auditoria_conectar(): ?\\mysqli {',
        '    $sql = @new \\mysqli(',
        '        Conf::SUPERESTRUCTURA_HOST_SQL,',
        '        Conf::SUPERESTRUCTURA_USUARIO_SQL,',
        '        Conf::SUPERESTRUCTURA_CONTRASENA_SQL,',
        '        Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL',
        '    );',
        '    if ($sql->connect_errno) {',
        '        return null;',
        '    }',
        '    $sql->set_charset("utf8mb4");',
        '    return $sql;',
        '}',
        '',
        '// ============================================================',
        '// Verificaciones SQL (críticos)',
        '// ============================================================',
        '',
        '/**',
        ' * #1: aristas de adyacente_contexto cuyo origen o destino',
        ' * no existe en nodo_contexto.',
        ' */',
        'function _auditoria_aristas_rotas(\\mysqli $sql, string $nombre_app): array {',
        '    $nombre = $sql->real_escape_string($nombre_app);',
        '    $q = "SELECT a.idnodo_fila, a.enlace, a.idadyacente_fila',
        '          FROM adyacente_contexto a',
        '          LEFT JOIN nodo_contexto n1',
        '            ON n1.id_fila = a.idnodo_fila',
        '           AND n1.idsuperestructura = a.idsuperestructura',
        '          LEFT JOIN nodo_contexto n2',
        '            ON n2.id_fila = a.idadyacente_fila',
        '           AND n2.idsuperestructura = a.idsuperestructura',
        '          WHERE a.idsuperestructura = \'" . $nombre . "\'',
        '            AND (n1.id_fila IS NULL OR n2.id_fila IS NULL)";',
        '    $res = $sql->query($q);',
        '    if (!$res) return [];',
        '    $out = [];',
        '    while ($f = $res->fetch_assoc()) {',
        '        $out[] = [',
        '            "tipo" => "arista_rota",',
        '            "detalle" => "adyacente_contexto origen=" . $f["idnodo_fila"]',
        '                       . " enlace=\'" . $f["enlace"] . "\' destino=" . $f["idadyacente_fila"]',
        '                       . " (falta una fila en nodo_contexto)",',
        '        ];',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #2: nodos con contexto_mascara = 0 e id_especial NULL.',
        ' * No se cargan en carga parcial.',
        ' */',
        'function _auditoria_mascaras_vacias(\\mysqli $sql, string $nombre_app): array {',
        '    $nombre = $sql->real_escape_string($nombre_app);',
        '    $q = "SELECT id_fila, dato FROM nodo_contexto',
        '          WHERE idsuperestructura = \'" . $nombre . "\'',
        '            AND contexto_mascara = 0',
        '            AND id_especial IS NULL";',
        '    $res = $sql->query($q);',
        '    if (!$res) return [];',
        '    $out = [];',
        '    while ($f = $res->fetch_assoc()) {',
        '        $out[] = [',
        '            "tipo" => "nodo_mascara_cero",',
        '            "detalle" => "nodo_contexto id_fila=" . $f["id_fila"]',
        '                       . " dato=\'" . mb_substr((string)$f["dato"], 0, 60)',
        '                       . "\' tiene contexto_mascara=0",',
        '        ];',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * Contextos registrados en la tabla contexto.',
        ' */',
        'function _auditoria_contextos_registrados(\\mysqli $sql, string $nombre_app): array {',
        '    $nombre = $sql->real_escape_string($nombre_app);',
        '    $res = $sql->query("SELECT bit, nombre FROM contexto WHERE idsuperestructura = \'" . $nombre . "\' ORDER BY bit");',
        '    if (!$res) return [];',
        '    $out = [];',
        '    while ($f = $res->fetch_assoc()) {',
        '        $out[] = [',
        '            "bit" => (int)$f["bit"],',
        '            "nombre" => (string)$f["nombre"],',
        '        ];',
        '    }',
        '    return $out;',
        '}',
        '',
        '// ============================================================',
        '// Carga de la estructura en memoria',
        '// ============================================================',
        '',
        '/**',
        ' * Carga id => [dato, ady => [enlace => id_destino]] y',
        ' * cuenta el total de aristas.',
        ' */',
        'function _auditoria_cargar_estructura(string $token, &$aristas_total = 0): array {',
        '    $nodos = [];',
        '    $aristas_total = 0;',
        '    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$nodos, &$aristas_total) {',
        '        $id = (string)$nodo->id();',
        '        $ady = [];',
        '        foreach ($nodo->adyacentes() as $enlace => $adyacente) {',
        '            $ady[(string)$enlace] = (string)$adyacente->id();',
        '            $aristas_total++;',
        '        }',
        '        $nodos[$id] = [',
        '            "dato" => (string)$nodo->dato(),',
        '            "ady" => $ady,',
        '        ];',
        '    }, null);',
        '    return $nodos;',
        '}',
        '',
        '// ============================================================',
        '// Verificaciones en memoria (críticos)',
        '// ============================================================',
        '',
        '/**',
        ' * #3: us_Administrador existe y tiene enlace usuarios.',
        ' */',
        'function _auditoria_admin_sin_usuarios(array $estructura): array {',
        '    $id_admin = "us_" . \\ConfiguracionApli::NOMBRE_ADMIN;',
        '    if (!isset($estructura[$id_admin])) {',
        '        return [["tipo" => "admin_inexistente", "detalle" => "No existe el nodo $id_admin."]];',
        '    }',
        '    if (!isset($estructura[$id_admin]["ady"]["usuarios"])) {',
        '        return [["tipo" => "admin_sin_usuarios",',
        '                 "detalle" => "$id_admin no tiene enlace \'usuarios\'. El admin no navega la raíz."]];',
        '    }',
        '    return [];',
        '}',
        '',
        '/**',
        ' * #4: nodos con enlace dueno deben apuntar a un compartido',
        ' * (con _es_compartido).',
        ' */',
        'function _auditoria_terminal_dueno_real(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        if (!isset($info["ady"]["dueno"])) continue;',
        '        $id_dueno = $info["ady"]["dueno"];',
        '        if (!isset($estructura[$id_dueno])) continue;',
        '        if (!isset($estructura[$id_dueno]["ady"]["_es_compartido"])) {',
        '            $out[] = [',
        '                "tipo" => "terminal_dueno_real",',
        '                "detalle" => "nodo $id tiene \'dueno\' -> $id_dueno, que no tiene \'_es_compartido\'.",',
        '            ];',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #5: cada compartido (enlace compartido_con_*) debe tener',
        ' * _es_compartido, ventas y cancelaciones.',
        ' */',
        'function _auditoria_compartidos_incompletos(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            if (strpos($enlace, "compartido_con_") !== 0) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            $comp = $estructura[$destino_id];',
        '            if (!isset($comp["ady"]["_es_compartido"])) {',
        '                $out[] = [',
        '                    "tipo" => "compartido_sin_marca",',
        '                    "detalle" => "nodo $id -> \'$enlace\' -> $destino_id no tiene \'_es_compartido\'.",',
        '                ];',
        '            }',
        '            if (!isset($comp["ady"]["ventas"])) {',
        '                $out[] = [',
        '                    "tipo" => "compartido_sin_ventas",',
        '                    "detalle" => "compartido $destino_id no tiene \'ventas\'.",',
        '                ];',
        '            }',
        '            if (!isset($comp["ady"]["cancelaciones"])) {',
        '                $out[] = [',
        '                    "tipo" => "compartido_sin_cancelaciones",',
        '                    "detalle" => "compartido $destino_id no tiene \'cancelaciones\'.",',
        '                ];',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '// ============================================================',
        '// Verificaciones en memoria (errores)',
        '// ============================================================',
        '',
        '/**',
        ' * #6: ventas en árbol del compartido sin p_<term>.',
        ' */',
        'function _auditoria_ventas_sin_param(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            if (strpos($enlace, "compartido_con_") !== 0) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            $term = substr($enlace, strlen("compartido_con_"));',
        '            $ventas_id = $estructura[$destino_id]["ady"]["ventas"] ?? null;',
        '            if (!$ventas_id || !isset($estructura[$ventas_id])) continue;',
        '            $primer = $estructura[$ventas_id]["ady"]["hmi_" . $term] ?? null;',
        '            if (!$primer) continue;',
        '            $actual = $primer;',
        '            $visitados = [];',
        '            while ($actual && !isset($visitados[$actual])) {',
        '                $visitados[$actual] = true;',
        '                if (!isset($estructura[$actual])) break;',
        '                if (!isset($estructura[$actual]["ady"]["p_" . $term])) {',
        '                    $out[] = [',
        '                        "tipo" => "venta_sin_param",',
        '                        "detalle" => "venta $actual en compartido $destino_id no tiene \'p_$term\'.",',
        '                    ];',
        '                }',
        '                $actual = $estructura[$actual]["ady"]["hd_" . $term] ?? null;',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #7: ventas en árbol del compartido sin p default.',
        ' */',
        'function _auditoria_ventas_sin_p_default(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            if (strpos($enlace, "compartido_con_") !== 0) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            $ventas_id = $estructura[$destino_id]["ady"]["ventas"] ?? null;',
        '            if (!$ventas_id || !isset($estructura[$ventas_id])) continue;',
        '            $primer = $estructura[$ventas_id]["ady"]["hmi"] ?? null;',
        '            if (!$primer) continue;',
        '            $actual = $primer;',
        '            $visitados = [];',
        '            while ($actual && !isset($visitados[$actual])) {',
        '                $visitados[$actual] = true;',
        '                if (!isset($estructura[$actual])) break;',
        '                if (!isset($estructura[$actual]["ady"]["p"])) {',
        '                    $out[] = [',
        '                        "tipo" => "venta_sin_p_default",',
        '                        "detalle" => "venta $actual en compartido $destino_id no tiene \'p\' default.",',
        '                    ];',
        '                }',
        '                $actual = $estructura[$actual]["ady"]["hd"] ?? null;',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #8: buzones pendientes_ventas / pendientes_cancelaciones',
        ' * con contenido.',
        ' */',
        'function _auditoria_buzones_con_contenido(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            if (strpos($enlace, "compartido_con_") !== 0) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            foreach (["pendientes_ventas", "pendientes_cancelaciones"] as $buzon) {',
        '                $buzon_id = $estructura[$destino_id]["ady"][$buzon] ?? null;',
        '                if (!$buzon_id || !isset($estructura[$buzon_id])) continue;',
        '                $cuenta = count($estructura[$buzon_id]["ady"]);',
        '                if ($cuenta > 0) {',
        '                    $out[] = [',
        '                        "tipo" => "buzon_con_contenido",',
        '                        "detalle" => "compartido $destino_id -> $buzon tiene $cuenta elemento(s).",',
        '                    ];',
        '                }',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #9: nodos con enlace soporte singular (legacy).',
        ' */',
        'function _auditoria_soporte_singular_legacy(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        if (!isset($info["ady"]["soporte"])) continue;',
        '        $out[] = [',
        '            "tipo" => "soporte_singular_legacy",',
        '            "detalle" => "nodo $id tiene \'soporte\' singular (debería ser \'soportes\' plural).",',
        '        ];',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #10: nodos con duenos (soportes) deben tener publico.',
        ' */',
        'function _auditoria_soporte_sin_publico(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        if (!isset($info["ady"]["duenos"])) continue;',
        '        if (!isset($info["ady"]["publico"])) {',
        '            $out[] = [',
        '                "tipo" => "soporte_sin_publico",',
        '                "detalle" => "nodo $id (soporte, tiene \'duenos\') no tiene \'publico\'.",',
        '            ];',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '// ============================================================',
        '// Verificaciones en memoria (warnings)',
        '// ============================================================',
        '',
        '/**',
        ' * #11: nodos no alcanzables desde ningún ID especial.',
        ' */',
        'function _auditoria_huerfanos(array $estructura): array {',
        '    $alcanzables = _auditoria_bfs_raices($estructura);',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        if (isset($alcanzables[$id])) continue;',
        '        $out[] = [',
        '            "tipo" => "huerfano",',
        '            "detalle" => "nodo $id dato=\'" . mb_substr($info["dato"], 0, 40)',
        '                       . "\' no es alcanzable desde ninguna raíz.",',
        '        ];',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #12: hijos en hmi/hd (o parametrizados) deben tener p de',
        ' * vuelta apuntando al padre.',
        ' */',
        'function _auditoria_hijos_sin_p(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            $sufijo = null;',
        '            if ($enlace === "hmi" || $enlace === "hd") {',
        '                $sufijo = "";',
        '            } elseif (strpos($enlace, "hmi_") === 0) {',
        '                $sufijo = "_" . substr($enlace, 4);',
        '            } elseif (strpos($enlace, "hd_") === 0) {',
        '                $sufijo = "_" . substr($enlace, 3);',
        '            }',
        '            if ($sufijo === null) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            $p_enlace = "p" . $sufijo;',
        '            if (!isset($estructura[$destino_id]["ady"][$p_enlace])) {',
        '                $out[] = [',
        '                    "tipo" => "hijo_sin_p",',
        '                    "detalle" => "nodo $id -> \'$enlace\' -> $destino_id no tiene \'$p_enlace\'.",',
        '                ];',
        '            } elseif ($estructura[$destino_id]["ady"][$p_enlace] !== $id) {',
        '                $out[] = [',
        '                    "tipo" => "p_apunta_a_otro",',
        '                    "detalle" => "nodo $destino_id tiene \'$p_enlace\' -> "',
        '                               . $estructura[$destino_id]["ady"][$p_enlace]',
        '                               . " en vez de a $id.",',
        '                ];',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #13: enlaces p (o parametrizados) deben apuntar a un nodo',
        ' * con hmi (o parametrizado) correspondiente.',
        ' */',
        'function _auditoria_p_no_contenedor(array $estructura): array {',
        '    $out = [];',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $destino_id) {',
        '            $sufijo = null;',
        '            if ($enlace === "p") {',
        '                $sufijo = "";',
        '            } elseif (strpos($enlace, "p_") === 0) {',
        '                $sufijo = "_" . substr($enlace, 2);',
        '            }',
        '            if ($sufijo === null) continue;',
        '            if (!isset($estructura[$destino_id])) continue;',
        '            $hmi_enlace = "hmi" . $sufijo;',
        '            if (!isset($estructura[$destino_id]["ady"][$hmi_enlace])) {',
        '                $out[] = [',
        '                    "tipo" => "p_a_no_contenedor",',
        '                    "detalle" => "nodo $id -> \'$enlace\' -> $destino_id, pero no tiene \'$hmi_enlace\'.",',
        '                ];',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '/**',
        ' * #14: ciclos en cadenas hd (o hd_<term>).',
        ' */',
        'function _auditoria_ciclos_hd(array $estructura): array {',
        '    $out = [];',
        '    $max_pasos = count($estructura) + 10;',
        '    foreach ($estructura as $id => $info) {',
        '        foreach ($info["ady"] as $enlace => $primer_id) {',
        '            $sufijo = null;',
        '            if ($enlace === "hmi") {',
        '                $sufijo = "";',
        '            } elseif (strpos($enlace, "hmi_") === 0) {',
        '                $sufijo = "_" . substr($enlace, 4);',
        '            }',
        '            if ($sufijo === null) continue;',
        '            $hd_enlace = "hd" . $sufijo;',
        '            $contenedor_id = $id;',
        '            $actual = $primer_id;',
        '            $visitados = [];',
        '            $pasos = 0;',
        '            while ($actual && $actual !== $contenedor_id && $pasos < $max_pasos) {',
        '                if (isset($visitados[$actual])) {',
        '                    $out[] = [',
        '                        "tipo" => "ciclo_hd",',
        '                        "detalle" => "cadena $hd_enlace de $contenedor_id entra en ciclo en $actual.",',
        '                    ];',
        '                    break;',
        '                }',
        '                $visitados[$actual] = true;',
        '                $actual = $estructura[$actual]["ady"][$hd_enlace] ?? null;',
        '                $pasos++;',
        '            }',
        '            if ($pasos >= $max_pasos) {',
        '                $out[] = [',
        '                    "tipo" => "cadena_hd_muy_larga",',
        '                    "detalle" => "cadena $hd_enlace de $contenedor_id excede $max_pasos pasos.",',
        '                ];',
        '            }',
        '        }',
        '    }',
        '    return $out;',
        '}',
        '',
        '// ============================================================',
        '// Helpers',
        '// ============================================================',
        '',
        '/**',
        ' * BFS desde todos los IDs especiales. Copia local del',
        ' * _grafo_bfs_desde_raices del Controlador, para no',
        ' * tocar el framework.',
        ' *',
        ' * @param array $estructura',
        ' * @return array [id => true]',
        ' */',
        'function _auditoria_bfs_raices(array $estructura): array {',
        '    $alcanzables = [];',
        '    $cola = [];',
        '    foreach ($estructura as $id => $info) {',
        '        if (!is_numeric($id)) {',
        '            $alcanzables[$id] = true;',
        '            $cola[] = $id;',
        '        }',
        '    }',
        '    while (!empty($cola)) {',
        '        $id = array_shift($cola);',
        '        if (!isset($estructura[$id])) continue;',
        '        foreach ($estructura[$id]["ady"] as $destino) {',
        '            if (!isset($alcanzables[$destino])) {',
        '                $alcanzables[$destino] = true;',
        '                $cola[] = $destino;',
        '            }',
        '        }',
        '    }',
        '    return $alcanzables;',
        '}',
    ],
],

// ============================================================
// 2. Comandos.php: agregar app:auditar_topologia
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Reparacion/Comandos.php',
    'descripcion' => 'Registrar app:auditar_topologia',
    'buscar' => [
        '        false // disponible en todos los entornos',
        '    );',
        '}',
    ],
    'reemplazar' => [
        '        false // disponible en todos los entornos',
        '    );',
        '',
        '    Controlador::registrar_comando(',
        '        \'app:auditar_topologia\',',
        '        function (string $token, array $args) {',
        '            require_once __DIR__ . \'/Auditoria.php\';',
        '            return auditar_topologia($token);',
        '        },',
        '        null,',
        '        false // disponible en todos los entornos',
        '    );',
        '}',
    ],
],

// ============================================================
// 3. index.php: agregar endpoint ?auditar_topologia=1
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'index.php',
    'descripcion' => 'Endpoint ?auditar_topologia=1',
    'buscar' => [
        '// Manejo de impresión',
        'if (isset($_GET[\'imprimir\']) && $_GET[\'imprimir\'] === \'1\') {',
    ],
    'reemplazar' => [
        '// ==== Auditoría topológica (v1.5piloto.81p) ====',
        '// Diagnóstico de solo lectura. Reporta aristas rotas,',
        '// nodos huérfanos, compartidos incompletos, etc.',
        '// No corrige nada.',
        '//',
        '// Uso: index.php?auditar_topologia=1&nombre_solicitante=<admin_o_soporte>',
        '//',
        '// En local se puede omitir nombre_solicitante (debug).',
        '// En pruebas y producción es obligatorio y debe ser',
        '// admin o soporte. El plugin arma la URL con el nombre',
        '// del usuario logueado.',
        'if (isset($_GET[\'auditar_topologia\'])) {',
        '    header(\'Content-Type: application/json; charset=utf-8\');',
        '',
        '    // Si el grafo quedó parcial, recargar total: la auditoría',
        '    // necesita ver el grafo completo.',
        '    if (Controlador::es_grafo_parcial()) {',
        '        $ok_recarga_aud = Controlador::cargar($nombre_app);',
        '        if ($ok_recarga_aud !== true) {',
        '            http_response_code(500);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => \'No se pudo recargar el grafo completo para la auditoría.\',',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    }',
        '',
        '    $nombre_sol_aud = trim((string)($_GET[\'nombre_solicitante\'] ?? \'\'));',
        '',
        '    if ($nombre_sol_aud === \'\') {',
        '        if (!Conf::LOCAL) {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => \'Falta nombre_solicitante. La auditoría requiere un usuario admin o soporte.\',',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    } else {',
        '        $nodo_sol_aud = Nodo::nodo_por_id(\'us_\' . $nombre_sol_aud);',
        '        if (!$nodo_sol_aud) {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => "Solicitante \'$nombre_sol_aud\' no encontrado.",',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '        $nivel_nodo_aud = $nodo_sol_aud->adyacente(\'nivel\');',
        '        $nivel_aud = $nivel_nodo_aud ? (string)$nivel_nodo_aud->dato() : \'\';',
        '        if ($nivel_aud !== \'admin\' && $nivel_aud !== \'soporte\') {',
        '            http_response_code(403);',
        '            echo json_encode([',
        '                \'exito\' => false,',
        '                \'error\' => "Permiso denegado. Nivel \'$nivel_aud\' (se requiere admin o soporte).",',
        '            ], JSON_UNESCAPED_UNICODE);',
        '            exit;',
        '        }',
        '    }',
        '',
        '    $reporte_aud = Controlador::ejecutar_comando(\'app:auditar_topologia\');',
        '    if (!is_array($reporte_aud)) {',
        '        http_response_code(500);',
        '        echo json_encode([',
        '            \'exito\' => false,',
        '            \'error\' => \'El comando app:auditar_topologia no devolvió un resultado válido.\',',
        '        ], JSON_UNESCAPED_UNICODE);',
        '        exit;',
        '    }',
        '    echo json_encode($reporte_aud, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);',
        '    exit;',
        '}',
        '',
        '// Manejo de impresión',
        'if (isset($_GET[\'imprimir\']) && $_GET[\'imprimir\'] === \'1\') {',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
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
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
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
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

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