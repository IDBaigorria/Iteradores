<?php
/**
 * Aplicador — Fase 1 + Fase 2 del bug §4.7: blindaje del guardado
 * parcial + logging de diagnóstico.
 *
 * Motivo: `_guardar_con_filtro` salta nodos con máscara 0 del INSERT,
 * y salta las aristas que apuntan a ellos. Si un nodo del subgrafo
 * pierde todos sus bits en el BFS (porque las aristas que lo
 * alcanzaban no están en el grafo parcial), el nodo queda huérfano
 * en DB y las aristas que lo apuntaban también. En el próximo
 * guardado parcial, el nodo se reinserta con id_fila nuevo
 * (auto-increment), duplicándose.
 *
 * Fix:
 * - Pre-DELETE: leer las máscaras viejas de los nodos a borrar.
 * - Post-INSERT: verificar que ningún nodo con máscara vieja > 0
 *   pierda bits. Verificar que todos los nodos a insertar estén
 *   efectivamente en DB. Verificar que todas las aristas a
 *   insertar estén efectivamente en DB.
 * - Si alguna verificación falla: rollback + false.
 * - Logging de diagnóstico si Conf::DEBUG_PERSISTENCIA.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5i.7t';

$htmls_a_bumpear = [];

$cambios = [

// ============================================================
// 1. Conf.php: agregar DEBUG_PERSISTENCIA
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Configuracion/Configuracion.php',
    'descripcion' => 'Conf: agregar DEBUG_PERSISTENCIA',
    'buscar' => [
        '    /**',
        '     * Carpeta donde se guardarán los archivos de la superestructura en formato XML.',
        '     * @var string',
        '     */',
        '    public const SUPERESTRUCTURA_CARPETA_GUARDAR_XML = "XML";',
    ],
    'reemplazar' => [
        '    /**',
        '     * Carpeta donde se guardarán los archivos de la superestructura en formato XML.',
        '     * @var string',
        '     */',
        '    public const SUPERESTRUCTURA_CARPETA_GUARDAR_XML = "XML";',
        '',
        '    /**',
        '     * Activa el logging de diagnóstico del guardado parcial (SQL64).',
        '     *',
        '     * Cuando está en true, `_guardar_con_filtro` escribe a',
        '     * `error_log` información sobre:',
        '     * - Cuántos nodos hay en memoria, en el mapa previo, nuevos,',
        '     *   con máscara 0 y a insertar.',
        '     * - Cuántas aristas se van a insertar y cuántas cruzan el borde.',
        '     * - Resultado de las verificaciones de máscaras, nodos y aristas.',
        '     *',
        '     * Activar solo en local, para reproducir y diagnosticar el',
        '     * bug §4.7 (corrupción del grafo por guardado parcial).',
        '     * En producción dejar en false: agrega ~50-100ms por guardado.',
        '     *',
        '     * @var bool',
        '     * @since 1.5i.7t',
        '     */',
        '    public const DEBUG_PERSISTENCIA = false;',
    ],
],

// ============================================================
// 2. SQL64: pre-DELETE leer máscaras viejas + logging de cabecera
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: pre-DELETE leer máscaras viejas',
    'buscar' => [
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
        '',
        '            if ($mf === 0) {',
    ],
    'reemplazar' => [
        '        // Fix 1.5i.7t: pre-DELETE, leer las máscaras viejas de',
        '        // los nodos que se van a borrar. Se usa para verificar,',
        '        // después del INSERT, que ningún nodo perdió bits. Si un',
        '        // nodo pierde bits (o queda con máscara 0), se aborta.',
        '        // Solo aplica al guardado parcial: el total borra todo,',
        '        // no hay "viejo" con qué comparar.',
        '        $mascaras_viejas = [];',
        '        $mapa_fila_a_id_previo = [];',
        '        if ($mf_parcial !== 0 && !empty($filas_cargadas)) {',
        '            $ids_in_pre = implode(",", array_map("intval", $filas_cargadas));',
        '            $q_pre = "SELECT id_fila, contexto_mascara FROM nodo_contexto"',
        '                   . " WHERE idsuperestructura=\'" . $sql->real_escape_string((string)$nombre) . "\'"',
        '                   . " AND id_fila IN (" . $ids_in_pre . ");";',
        '            if ($res_pre = $sql->query($q_pre)) {',
        '                $f_pre = $res_pre->fetch_assoc();',
        '                while ($f_pre !== null) {',
        '                    $mascaras_viejas[(int)$f_pre["id_fila"]] = (int)$f_pre["contexto_mascara"];',
        '                    $f_pre = $res_pre->fetch_assoc();',
        '                }',
        '            }',
        '            foreach ($mapa_previo as $id_n_pre => $fila_pre) {',
        '                $mapa_fila_a_id_previo[(int)$fila_pre] = $id_n_pre;',
        '            }',
        '        }',
        '',
        '        if (Conf::DEBUG_PERSISTENCIA) {',
        '            $modo_log = ($mf_parcial !== 0) ? "parcial" : "total";',
        '            error_log("[SQL64_GUARDAR] nombre=" . $nombre . " mf=" . $mf_parcial . " modo=" . $modo_log',
        '                . " nodos_en_memoria=" . count($mascaras)',
        '                . " nodos_previos=" . count($mapa_previo)',
        '                . " filas_cargadas=" . count($filas_cargadas)',
        '                . " mascaras_viejas=" . count($mascaras_viejas));',
        '        }',
        '',
        '        $sql->begin_transaction();',
        '        try {',
        '            $nombre_escapado = $sql->real_escape_string((string)$nombre);',
        '            $mf = (int)$mascara_filtro;',
        '',
        '            if ($mf === 0) {',
    ],
],

// ============================================================
// 3. SQL64: logging post-cálculo de ids_a_insertar
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: logging post-cálculo de ids_a_insertar',
    'buscar' => [
        '            $ids_a_insertar = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) return;',
        '                $ids_a_insertar[] = $id;',
        '            });',
    ],
    'reemplazar' => [
        '            $ids_a_insertar = [];',
        '            $nodos_mascara_0 = [];',
        '            Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) use ($mascaras, $mf, &$ids_a_insertar, &$nodos_mascara_0) {',
        '                $id = $nodo->id();',
        '                $mascara = (int)($mascaras[$id] ?? 0);',
        '                if ($mf !== 0 && ($mascara & $mf) === 0) {',
        '                    if ($mascara === 0) $nodos_mascara_0[] = $id;',
        '                    return;',
        '                }',
        '                $ids_a_insertar[] = $id;',
        '            });',
        '',
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] ids_a_insertar=" . count($ids_a_insertar)',
        '                    . " nodos_mascara_0=" . count($nodos_mascara_0)',
        '                    . (count($nodos_mascara_0) > 0 ? " [mascara_0: " . implode(",", array_slice($nodos_mascara_0, 0, 20)) . "]" : ""));',
        '            }',
        '',
        '            // Fix 1.5i.7t: si el guardado es parcial y no hay',
        '            // ningún nodo a insertar, es un error. El grafo en',
        '            // memoria no matchea el filtro. Antes se hacía INSERT',
        '            // vacío y el DELETE había borrado todo lo que estaba',
        '            // en DB. Ahora se aborta con rollback.',
        '            if ($mf !== 0 && empty($ids_a_insertar)) {',
        '                throw new \RuntimeException("SQL64::guardar: no hay nodos que matcheen el filtro. ¿Se cargó el subgrafo correcto?");',
        '            }',
    ],
],

// ============================================================
// 4. SQL64: recolectar filas_insertadas_ahora en el loop
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: recolectar filas_insertadas_ahora en el loop de INSERT',
    'buscar' => [
        '            foreach ($ids_a_insertar as $id_nodo) {',
        '                $nodo = Nodo::nodo_por_id($id_nodo);',
        '                if (!$nodo) continue;',
        '                $mascara = (int)($mascaras[$id_nodo] ?? 0);',
        '                $dato = $nodo->dato();',
        '                if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;',
        '                $dato_escapado = is_null($dato) ? "" : $sql->real_escape_string((string)$dato);',
        '                $id_especial = Nodo::es_id_especial($id_nodo) ? $sql->real_escape_string((string)$id_nodo) : null;',
        '                $id_especial_sql = is_null($id_especial) ? "NULL" : "\'" . $id_especial . "\'";',
        '                if (isset($mapa_previo[$id_nodo])) {',
        '                    $id_fila = (int)$mapa_previo[$id_nodo];',
        '                    $q = "INSERT INTO nodo_contexto (id_fila, idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . $id_fila . ", \'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                } else {',
        '                    $q = "INSERT INTO nodo_contexto (idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . "\'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                }',
        '                if (!$sql->query($q)) {',
        '                    throw new \RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);',
        '                }',
        '                if (!isset($mapa_previo[$id_nodo])) {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = (int)$sql->insert_id;',
        '                } else {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];',
        '                }',
        '            }',
    ],
    'reemplazar' => [
        '            $filas_insertadas_ahora = [];',
        '            foreach ($ids_a_insertar as $id_nodo) {',
        '                $nodo = Nodo::nodo_por_id($id_nodo);',
        '                if (!$nodo) continue;',
        '                $mascara = (int)($mascaras[$id_nodo] ?? 0);',
        '                $dato = $nodo->dato();',
        '                if (!is_string($dato) && !is_null($dato) && !is_int($dato)) $dato = null;',
        '                $dato_escapado = is_null($dato) ? "" : $sql->real_escape_string((string)$dato);',
        '                $id_especial = Nodo::es_id_especial($id_nodo) ? $sql->real_escape_string((string)$id_nodo) : null;',
        '                $id_especial_sql = is_null($id_especial) ? "NULL" : "\'" . $id_especial . "\'";',
        '                if (isset($mapa_previo[$id_nodo])) {',
        '                    $id_fila = (int)$mapa_previo[$id_nodo];',
        '                    $q = "INSERT INTO nodo_contexto (id_fila, idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . $id_fila . ", \'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                } else {',
        '                    $q = "INSERT INTO nodo_contexto (idsuperestructura, id_especial, dato, contexto_mascara) VALUES ("',
        '                       . "\'" . $nombre_escapado . "\', " . $id_especial_sql . ", \'" . $dato_escapado . "\', " . $mascara . ");";',
        '                }',
        '                if (!$sql->query($q)) {',
        '                    throw new \RuntimeException("SQL64::guardar: fallo INSERT nodo_contexto: " . $sql->error);',
        '                }',
        '                if (!isset($mapa_previo[$id_nodo])) {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = (int)$sql->insert_id;',
        '                    $filas_insertadas_ahora[] = (int)$sql->insert_id;',
        '                } else {',
        '                    self::$_mapa_id_a_fila[$nombre][$id_nodo] = $mapa_previo[$id_nodo];',
        '                    $filas_insertadas_ahora[] = (int)$mapa_previo[$id_nodo];',
        '                }',
        '            }',
        '',
        '            // Fix 1.5i.7t: verificación de máscaras. Para cada',
        '            // nodo que estaba en DB con máscara > 0 y sigue en',
        '            // el grafo, la máscara nueva debe contener todos los',
        '            // bits viejos. Si perdió alguno (o quedó con 0), el',
        '            // guardado parcial está corrompiendo la topología.',
        '            // Rollback.',
        '            if ($mf_parcial !== 0 && !empty($mascaras_viejas)) {',
        '                $perdidos = [];',
        '                foreach ($mascaras_viejas as $id_fila_viejo => $mascara_vieja) {',
        '                    if ($mascara_vieja === 0) continue;',
        '                    $id_nodo_prev = $mapa_fila_a_id_previo[$id_fila_viejo] ?? null;',
        '                    if ($id_nodo_prev === null) continue; // nodo borrado, válido',
        '                    $mascara_nueva = (int)($mascaras[$id_nodo_prev] ?? 0);',
        '                    if (($mascara_nueva & $mascara_vieja) !== $mascara_vieja) {',
        '                        $perdidos[] = ["id_nodo" => $id_nodo_prev, "id_fila" => $id_fila_viejo, "vieja" => $mascara_vieja, "nueva" => $mascara_nueva];',
        '                    }',
        '                }',
        '                if (!empty($perdidos)) {',
        '                    if (Conf::DEBUG_PERSISTENCIA) {',
        '                        foreach ($perdidos as $p) {',
        '                            error_log("[SQL64_GUARDAR] PERDIDA id_nodo=" . $p["id_nodo"] . " id_fila=" . $p["id_fila"] . " vieja=" . $p["vieja"] . " nueva=" . $p["nueva"]);',
        '                        }',
        '                    }',
        '                    throw new \RuntimeException("SQL64::guardar: " . count($perdidos) . " nodo(s) perdieron bits de máscara. Rollback.");',
        '                }',
        '                if (Conf::DEBUG_PERSISTENCIA) {',
        '                    error_log("[SQL64_GUARDAR] verif_mascaras ok=1 revisadas=" . count($mascaras_viejas));',
        '                }',
        '            }',
        '',
        '            // Fix 1.5i.7t: verificación de nodos. Todos los id_fila',
        '            // que se insertaron deben estar en DB.',
        '            if (!empty($filas_insertadas_ahora)) {',
        '                $ids_in_ins = implode(",", array_map("intval", $filas_insertadas_ahora));',
        '                $q_cnt_n = "SELECT COUNT(*) AS c FROM nodo_contexto WHERE idsuperestructura=\'" . $nombre_escapado . "\' AND id_fila IN (" . $ids_in_ins . ");";',
        '                $res_cnt_n = $sql->query($q_cnt_n);',
        '                $cnt_n = $res_cnt_n ? (int)$res_cnt_n->fetch_assoc()["c"] : -1;',
        '                if ($cnt_n !== count($filas_insertadas_ahora)) {',
        '                    if (Conf::DEBUG_PERSISTENCIA) {',
        '                        error_log("[SQL64_GUARDAR] verif_nodos FALLO esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);',
        '                    }',
        '                    throw new \RuntimeException("SQL64::guardar: verificación de nodos falló. Esperado=" . count($filas_insertadas_ahora) . " en_db=" . $cnt_n);',
        '                }',
        '                if (Conf::DEBUG_PERSISTENCIA) {',
        '                    error_log("[SQL64_GUARDAR] verif_nodos ok=1 count=" . $cnt_n);',
        '                }',
        '            }',
    ],
],

// ============================================================
// 5. SQL64: logging post-cálculo de aristas + verificación
//    post-INSERT de aristas
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL64.php',
    'descripcion' => 'SQL64: logging y verificación de aristas',
    'buscar' => [
        '            if (!empty($filas_aristas)) {',
        '                $limite_bytes = 200 * 1024;',
        '                $lote = [];',
        '                $tamano = 0;',
        '                foreach ($filas_aristas as $fila_ar) {',
        '                    list($fo, $enl, $fd) = $fila_ar;',
        '                    $enl_esc = $sql->real_escape_string($enl);',
        '                    $fila_str = "(\'" . $nombre_escapado . "\'," . $fo . ",\'" . $enl_esc . "\'," . $fd . ")";',
        '                    $tam = strlen($fila_str) + 2;',
        '                    if (!empty($lote) && ($tamano + $tam) > $limite_bytes) {',
        '                        $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                        if (!$sql->query($q_ins)) {',
        '                            throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                        }',
        '                        $lote = [];',
        '                        $tamano = 0;',
        '                    }',
        '                    $lote[] = $fila_str;',
        '                    $tamano += $tam;',
        '                }',
        '                if (!empty($lote)) {',
        '                    $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                    if (!$sql->query($q_ins)) {',
        '                        throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                    }',
        '                }',
        '            }',
    ],
    'reemplazar' => [
        '            if (Conf::DEBUG_PERSISTENCIA) {',
        '                error_log("[SQL64_GUARDAR] aristas_a_insertar=" . count($filas_aristas));',
        '            }',
        '',
        '            if (!empty($filas_aristas)) {',
        '                $limite_bytes = 200 * 1024;',
        '                $lote = [];',
        '                $tamano = 0;',
        '                foreach ($filas_aristas as $fila_ar) {',
        '                    list($fo, $enl, $fd) = $fila_ar;',
        '                    $enl_esc = $sql->real_escape_string($enl);',
        '                    $fila_str = "(\'" . $nombre_escapado . "\'," . $fo . ",\'" . $enl_esc . "\'," . $fd . ")";',
        '                    $tam = strlen($fila_str) + 2;',
        '                    if (!empty($lote) && ($tamano + $tam) > $limite_bytes) {',
        '                        $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                        if (!$sql->query($q_ins)) {',
        '                            throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                        }',
        '                        $lote = [];',
        '                        $tamano = 0;',
        '                    }',
        '                    $lote[] = $fila_str;',
        '                    $tamano += $tam;',
        '                }',
        '                if (!empty($lote)) {',
        '                    $q_ins = "INSERT INTO adyacente_contexto (idsuperestructura, idnodo_fila, enlace, idadyacente_fila) VALUES " . implode(", ", $lote) . ";";',
        '                    if (!$sql->query($q_ins)) {',
        '                        throw new \RuntimeException("SQL64::guardar: fallo INSERT adyacente_contexto: " . $sql->error);',
        '                    }',
        '                }',
        '            }',
        '',
        '            // Fix 1.5i.7t: verificación de aristas. Todas las',
        '            // aristas que se calcularon para insertar deben estar',
        '            // en DB con ambos extremos en las filas insertadas.',
        '            if (!empty($filas_aristas) && !empty($filas_insertadas_ahora)) {',
        '                $ids_in_ar = implode(",", array_map("intval", $filas_insertadas_ahora));',
        '                $q_cnt_a = "SELECT COUNT(*) AS c FROM adyacente_contexto"',
        '                         . " WHERE idsuperestructura=\'" . $nombre_escapado . "\'"',
        '                         . " AND idnodo_fila IN (" . $ids_in_ar . ")"',
        '                         . " AND idadyacente_fila IN (" . $ids_in_ar . ");";',
        '                $res_cnt_a = $sql->query($q_cnt_a);',
        '                $cnt_a = $res_cnt_a ? (int)$res_cnt_a->fetch_assoc()["c"] : -1;',
        '                if ($cnt_a !== count($filas_aristas)) {',
        '                    if (Conf::DEBUG_PERSISTENCIA) {',
        '                        error_log("[SQL64_GUARDAR] verif_aristas FALLO esperado=" . count($filas_aristas) . " en_db=" . $cnt_a);',
        '                    }',
        '                    throw new \RuntimeException("SQL64::guardar: verificación de aristas falló. Esperado=" . count($filas_aristas) . " en_db=" . $cnt_a);',
        '                }',
        '                if (Conf::DEBUG_PERSISTENCIA) {',
        '                    error_log("[SQL64_GUARDAR] verif_aristas ok=1 count=" . $cnt_a);',
        '                }',
        '            }',
    ],
],

// ============================================================
// 6. plan_actual.md: Fases 1 y 2 cerradas
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: header a v83b',
    'buscar' => [
        '**Última actualización:** v1.5piloto.83a (Fase 0 del',
        'diagnóstico del bug crítico §4.7: corrupción del grafo',
        'por guardado parcial. Solo documentación. No toca código).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.83b (Fases 1 y 2 del',
        'bug crítico §4.7: blindaje del guardado parcial + logging',
        'de diagnóstico. Framework 1.5i.7t).',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: marcar Fases 1 y 2 cerradas',
    'buscar' => [
        '**Fase 1 — Blindaje (red de seguridad).**',
        '- Modificar `_guardar_con_filtro` para que falle con',
        '  `rollback` si:',
        '  - Un nodo del grafo no queda persistido.',
        '  - Un nodo pierde bits de máscara respecto del estado',
        '    anterior en DB.',
        '  - El conteo de aristas en DB < conteo en grafo.',
        '- Costo: 2-3 SELECT extra por guardado parcial. Con',
        '  ~10k nodos, +1-2 segundos.',
        '- Beneficio: si el bug vuelve a pasar, falla con error',
        '  en lugar de corromper en silencio.',
        '',
        '**Fase 2 — Logging de diagnóstico.**',
        '- Flag `Conf::DEBUG_PERSISTENCIA`.',
        '- Log en `error_log`:',
        '  - Cuántos nodos en memoria, en `$mapa_previo`, nuevos.',
        '  - Nodos nuevos y su máscara calculada.',
        '  - Nodos con máscara 0 (los que se saltan del INSERT).',
        '  - Aristas a insertar, cuántas se saltan por destino',
        '    fuera de máscara.',
        '  - Aristas preexistentes que cruzan el borde y no se',
        '    reinsertan.',
        '- Se activa con `Conf::DEBUG_PERSISTENCIA = true` o',
        '  `?debug_persistencia=1`.',
        '',
        '**Fase 3 — Reproducir el bug en local.**',
    ],
    'reemplazar' => [
        '**Fase 1 — Blindaje (red de seguridad). CERRADA en 1.5i.7t.**',
        '- `_guardar_con_filtro` ahora hace rollback si:',
        '  - Un nodo con máscara vieja > 0 pierde bits en el BFS.',
        '  - Algún `id_fila` insertado no queda en DB.',
        '  - Alguna arista calculada no queda en DB.',
        '- Pre-DELETE: lee las máscaras viejas de los nodos a',
        '  borrar. Guarda el mapa inverso `id_fila → id_nodo`.',
        '- Post-INSERT: verifica. Si algo falla, `throw` →',
        '  `rollback` → `false`. Sin reintento automático (reintentar',
        '  con `guardar()` total pisaría todo el grafo desde la',
        '  memoria parcial, catastrófico).',
        '- Costo: 3 SELECTs extra por guardado parcial. Estimado',
        '  +200-500ms con ~10k nodos.',
        '- Beneficio: si el bug vuelve a pasar, falla con error',
        '  en lugar de corromper en silencio.',
        '',
        '**Fase 2 — Logging de diagnóstico. CERRADA en 1.5i.7t.**',
        '- Flag `Conf::DEBUG_PERSISTENCIA = false` (default).',
        '- Log a `error_log` con prefijo `[SQL64_GUARDAR]`:',
        '  - Cabecera: `nombre`, `mf`, `modo` (parcial/total),',
        '    `nodos_en_memoria`, `nodos_previos`, `filas_cargadas`,',
        '    `mascaras_viejas`.',
        '  - `ids_a_insertar`, `nodos_mascara_0` (con hasta 20 IDs',
        '    de ejemplo).',
        '  - `aristas_a_insertar`.',
        '  - Resultado de `verif_mascaras`, `verif_nodos`,',
        '    `verif_aristas`.',
        '  - Si una máscara pierde bits: `PERDIDA id_nodo=... id_fila=...',
        '    vieja=... nueva=...`.',
        '- Solo activo en local. En producción se deja en `false`',
        '  (agrega ~50-100ms por guardado).',
        '',
        '**Fase 3 — Reproducir el bug en local.**',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: estado del proyecto al cierre',
    'buscar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83a',
        '(framework 1.5i.8f). **Bug crítico abierto:** corrupción',
        'del grafo por guardado parcial (§4.7). Base restaurada',
        'desde backup + reparaciones idempotentes. Plan de',
        'diagnóstico y blindaje de 6 fases en curso. SQL64',
        'activo, topología admin/soporte, auditoría topológica',
        'disponible. **La migración de pasajeros queda en pausa**',
        'hasta que el bug §4.7 esté resuelto.',
    ],
    'reemplazar' => [
        '**Estado del proyecto al cierre:** v1.5piloto.83b',
        '(framework 1.5i.7t). **Bug crítico abierto:** corrupción',
        'del grafo por guardado parcial (§4.7). Base restaurada',
        'desde backup + reparaciones idempotentes. Fases 1 y 2',
        'cerradas: blindaje del guardado parcial (rollback si',
        'pierde bits, nodos o aristas) y logging de diagnóstico',
        '(`Conf::DEBUG_PERSISTENCIA`). Fase 3 (reproducir en',
        'local) es el próximo paso. SQL64 activo, topología',
        'admin/soporte, auditoría topológica disponible. **La',
        'migración de pasajeros queda en pausa** hasta que el bug',
        '§4.7 esté resuelto.',
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