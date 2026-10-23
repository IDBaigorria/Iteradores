<?php
/**
 * Auditoría topológica del grafo SQL64.
 *
 * Diagnóstico de solo lectura. Detecta aristas rotas,
 * nodos huérfanos, compartidos incompletos, etc. NO
 * corrige nada: los fixes van por ?reparar_topologia,
 * ?reparar_arboles o scripts manuales.
 *
 * Se ejecuta desde el comando app:auditar_topologia,
 * que a su vez invoca el endpoint ?auditar_topologia=1.
 *
 * Usa su propia conexión mysqli de solo lectura. No
 * hereda de la persistencia y no la modifica.
 *
 * @package   Iteradores
 * @since     1.5piloto.81p
 * @version   1.5piloto.81q
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;

/**
 * Punto de entrada. Devuelve el reporte completo.
 *
 * @param string $token Token del framework (para iterar nodos).
 * @return array
 */
function auditar_topologia(string $token): array {
    $nombre_app = (string)\ConfiguracionApli::NOMBRE_APP;

    $criticos = [];
    $errores = [];
    $warnings = [];

    // ─── Verificaciones SQL (solo lectura) ─────────────
    $sql = _auditoria_conectar();
    $contextos = [];
    if ($sql !== null) {
        $criticos = array_merge($criticos, _auditoria_aristas_rotas($sql, $nombre_app));
        $criticos = array_merge($criticos, _auditoria_mascaras_vacias($sql, $nombre_app));
        $contextos = _auditoria_contextos_registrados($sql, $nombre_app);
        $sql->close();
    } else {
        $warnings[] = [
            "tipo" => "sin_db",
            "detalle" => "No se pudo conectar a la DB. Solo se auditan las verificaciones en memoria.",
        ];
    }

    // ─── Verificaciones en memoria ─────────────────────
    $aristas_total = 0;
    $estructura = _auditoria_cargar_estructura($token, $aristas_total);

    $criticos = array_merge($criticos, _auditoria_admin_sin_usuarios($estructura));
    $criticos = array_merge($criticos, _auditoria_terminal_dueno_real($estructura));
    $criticos = array_merge($criticos, _auditoria_compartidos_incompletos($estructura));

    $errores = array_merge($errores, _auditoria_ventas_sin_param($estructura));
    $errores = array_merge($errores, _auditoria_ventas_sin_p_default($estructura));
    $errores = array_merge($errores, _auditoria_buzones_con_contenido($estructura));
    $errores = array_merge($errores, _auditoria_soporte_singular_legacy($estructura));
    $errores = array_merge($errores, _auditoria_soporte_sin_publico($estructura));

    $warnings = array_merge($warnings, _auditoria_huerfanos($estructura));
    $warnings = array_merge($warnings, _auditoria_hijos_sin_p($estructura));
    $warnings = array_merge($warnings, _auditoria_p_no_contenedor($estructura));
    $warnings = array_merge($warnings, _auditoria_ciclos_hd($estructura));

    return [
        "exito" => true,
        "resumen" => [
            "superestructura" => $nombre_app,
            "nodos_total" => count($estructura),
            "aristas_total" => $aristas_total,
            "contextos" => $contextos,
            "criticos" => count($criticos),
            "errores" => count($errores),
            "warnings" => count($warnings),
        ],
        "criticos" => $criticos,
        "errores" => $errores,
        "warnings" => $warnings,
    ];
}

// ============================================================
// Conexión SQL de solo lectura
// ============================================================

/**
 * Abre una conexión mysqli de solo lectura.
 *
 * No crea base de datos, no crea tablas, no transacciona.
 * Solo lee Conf::SUPERESTRUCTURA_*. Funciona en local,
 * pruebas y producción.
 *
 * @return \mysqli|null
 */
function _auditoria_conectar(): ?\mysqli {
    $sql = @new \mysqli(
        Conf::SUPERESTRUCTURA_HOST_SQL,
        Conf::SUPERESTRUCTURA_USUARIO_SQL,
        Conf::SUPERESTRUCTURA_CONTRASENA_SQL,
        Conf::SUPERESTRUCTURA_NOMBRE_BD_SQL
    );
    if ($sql->connect_errno) {
        return null;
    }
    $sql->set_charset("utf8mb4");
    return $sql;
}

// ============================================================
// Verificaciones SQL (críticos)
// ============================================================

/**
 * #1: aristas de adyacente_contexto cuyo origen o destino
 * no existe en nodo_contexto.
 */
function _auditoria_aristas_rotas(\mysqli $sql, string $nombre_app): array {
    $nombre = $sql->real_escape_string($nombre_app);
    $q = "SELECT a.idnodo_fila, a.enlace, a.idadyacente_fila
          FROM adyacente_contexto a
          LEFT JOIN nodo_contexto n1
            ON n1.id_fila = a.idnodo_fila
           AND n1.idsuperestructura = a.idsuperestructura
          LEFT JOIN nodo_contexto n2
            ON n2.id_fila = a.idadyacente_fila
           AND n2.idsuperestructura = a.idsuperestructura
          WHERE a.idsuperestructura = '" . $nombre . "'
            AND (n1.id_fila IS NULL OR n2.id_fila IS NULL)";
    $res = $sql->query($q);
    if (!$res) return [];
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $out[] = [
            "tipo" => "arista_rota",
            "detalle" => "adyacente_contexto origen=" . $f["idnodo_fila"]
                       . " enlace='" . $f["enlace"] . "' destino=" . $f["idadyacente_fila"]
                       . " (falta una fila en nodo_contexto)",
        ];
    }
    return $out;
}

/**
 * #2: nodos con contexto_mascara = 0 e id_especial NULL.
 * No se cargan en carga parcial.
 */
function _auditoria_mascaras_vacias(\mysqli $sql, string $nombre_app): array {
    $nombre = $sql->real_escape_string($nombre_app);
    $q = "SELECT id_fila, dato FROM nodo_contexto
          WHERE idsuperestructura = '" . $nombre . "'
            AND contexto_mascara = 0
            AND id_especial IS NULL";
    $res = $sql->query($q);
    if (!$res) return [];
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $out[] = [
            "tipo" => "nodo_mascara_cero",
            "detalle" => "nodo_contexto id_fila=" . $f["id_fila"]
                       . " dato='" . mb_substr((string)$f["dato"], 0, 60)
                       . "' tiene contexto_mascara=0",
        ];
    }
    return $out;
}

/**
 * Contextos registrados en la tabla contexto.
 */
function _auditoria_contextos_registrados(\mysqli $sql, string $nombre_app): array {
    $nombre = $sql->real_escape_string($nombre_app);
    $res = $sql->query("SELECT bit, nombre FROM contexto WHERE idsuperestructura = '" . $nombre . "' ORDER BY bit");
    if (!$res) return [];
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $out[] = [
            "bit" => (int)$f["bit"],
            "nombre" => (string)$f["nombre"],
        ];
    }
    return $out;
}

// ============================================================
// Carga de la estructura en memoria
// ============================================================

/**
 * Carga id => [dato, ady => [enlace => id_destino]] y
 * cuenta el total de aristas.
 */
function _auditoria_cargar_estructura(string $token, &$aristas_total = 0): array {
    $nodos = [];
    $aristas_total = 0;
    Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$nodos, &$aristas_total) {
        $id = (string)$nodo->id();
        $ady = [];
        foreach ($nodo->adyacentes() as $enlace => $adyacente) {
            $ady[(string)$enlace] = (string)$adyacente->id();
            $aristas_total++;
        }
        $nodos[$id] = [
            "dato" => (string)$nodo->dato(),
            "ady" => $ady,
        ];
    }, null);
    return $nodos;
}

// ============================================================
// Verificaciones en memoria (críticos)
// ============================================================

/**
 * #3: us_Administrador existe y tiene enlace usuarios.
 */
function _auditoria_admin_sin_usuarios(array $estructura): array {
    $id_admin = "us_" . \ConfiguracionApli::NOMBRE_ADMIN;
    if (!isset($estructura[$id_admin])) {
        return [["tipo" => "admin_inexistente", "detalle" => "No existe el nodo $id_admin."]];
    }
    if (!isset($estructura[$id_admin]["ady"]["usuarios"])) {
        return [["tipo" => "admin_sin_usuarios",
                 "detalle" => "$id_admin no tiene enlace 'usuarios'. El admin no navega la raíz."]];
    }
    return [];
}

/**
 * #4: nodos con enlace dueno deben apuntar a un compartido
 * (con _es_compartido).
 */
function _auditoria_terminal_dueno_real(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        if (!isset($info["ady"]["dueno"])) continue;
        $id_dueno = $info["ady"]["dueno"];
        if (!isset($estructura[$id_dueno])) continue;
        if (!isset($estructura[$id_dueno]["ady"]["_es_compartido"])) {
            $out[] = [
                "tipo" => "terminal_dueno_real",
                "detalle" => "nodo $id tiene 'dueno' -> $id_dueno, que no tiene '_es_compartido'.",
            ];
        }
    }
    return $out;
}

/**
 * #5: cada compartido (enlace compartido_con_*) debe tener
 * _es_compartido, ventas y cancelaciones.
 */
function _auditoria_compartidos_incompletos(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            if (strpos($enlace, "compartido_con_") !== 0) continue;
            if (!isset($estructura[$destino_id])) continue;
            $comp = $estructura[$destino_id];
            if (!isset($comp["ady"]["_es_compartido"])) {
                $out[] = [
                    "tipo" => "compartido_sin_marca",
                    "detalle" => "nodo $id -> '$enlace' -> $destino_id no tiene '_es_compartido'.",
                ];
            }
            if (!isset($comp["ady"]["ventas"])) {
                $out[] = [
                    "tipo" => "compartido_sin_ventas",
                    "detalle" => "compartido $destino_id no tiene 'ventas'.",
                ];
            }
            if (!isset($comp["ady"]["cancelaciones"])) {
                $out[] = [
                    "tipo" => "compartido_sin_cancelaciones",
                    "detalle" => "compartido $destino_id no tiene 'cancelaciones'.",
                ];
            }
        }
    }
    return $out;
}

// ============================================================
// Verificaciones en memoria (errores)
// ============================================================

/**
 * #6: ventas en árbol del compartido sin p_<term>.
 */
function _auditoria_ventas_sin_param(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            if (strpos($enlace, "compartido_con_") !== 0) continue;
            if (!isset($estructura[$destino_id])) continue;
            $term = substr($enlace, strlen("compartido_con_"));
            $ventas_id = $estructura[$destino_id]["ady"]["ventas"] ?? null;
            if (!$ventas_id || !isset($estructura[$ventas_id])) continue;
            $primer = $estructura[$ventas_id]["ady"]["hmi_" . $term] ?? null;
            if (!$primer) continue;
            $actual = $primer;
            $visitados = [];
            while ($actual && !isset($visitados[$actual])) {
                $visitados[$actual] = true;
                if (!isset($estructura[$actual])) break;
                if (!isset($estructura[$actual]["ady"]["p_" . $term])) {
                    $out[] = [
                        "tipo" => "venta_sin_param",
                        "detalle" => "venta $actual en compartido $destino_id no tiene 'p_$term'.",
                    ];
                }
                $actual = $estructura[$actual]["ady"]["hd_" . $term] ?? null;
            }
        }
    }
    return $out;
}

/**
 * #7: ventas en árbol del compartido sin p default.
 */
function _auditoria_ventas_sin_p_default(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            if (strpos($enlace, "compartido_con_") !== 0) continue;
            if (!isset($estructura[$destino_id])) continue;
            $ventas_id = $estructura[$destino_id]["ady"]["ventas"] ?? null;
            if (!$ventas_id || !isset($estructura[$ventas_id])) continue;
            $primer = $estructura[$ventas_id]["ady"]["hmi"] ?? null;
            if (!$primer) continue;
            $actual = $primer;
            $visitados = [];
            while ($actual && !isset($visitados[$actual])) {
                $visitados[$actual] = true;
                if (!isset($estructura[$actual])) break;
                if (!isset($estructura[$actual]["ady"]["p"])) {
                    $out[] = [
                        "tipo" => "venta_sin_p_default",
                        "detalle" => "venta $actual en compartido $destino_id no tiene 'p' default.",
                    ];
                }
                $actual = $estructura[$actual]["ady"]["hd"] ?? null;
            }
        }
    }
    return $out;
}

/**
 * #8: buzones pendientes_ventas / pendientes_cancelaciones
 * con contenido.
 */
function _auditoria_buzones_con_contenido(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            if (strpos($enlace, "compartido_con_") !== 0) continue;
            if (!isset($estructura[$destino_id])) continue;
            foreach (["pendientes_ventas", "pendientes_cancelaciones"] as $buzon) {
                $buzon_id = $estructura[$destino_id]["ady"][$buzon] ?? null;
                if (!$buzon_id || !isset($estructura[$buzon_id])) continue;
                $cuenta = count($estructura[$buzon_id]["ady"]);
                if ($cuenta > 0) {
                    $out[] = [
                        "tipo" => "buzon_con_contenido",
                        "detalle" => "compartido $destino_id -> $buzon tiene $cuenta elemento(s).",
                    ];
                }
            }
        }
    }
    return $out;
}

/**
 * #9: nodos con enlace soporte singular (legacy).
 */
function _auditoria_soporte_singular_legacy(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        if (!isset($info["ady"]["soporte"])) continue;
        $out[] = [
            "tipo" => "soporte_singular_legacy",
            "detalle" => "nodo $id tiene 'soporte' singular (debería ser 'soportes' plural).",
        ];
    }
    return $out;
}

/**
 * #10: nodos con duenos (soportes) deben tener publico.
 */
function _auditoria_soporte_sin_publico(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        if (!isset($info["ady"]["duenos"])) continue;
        if (!isset($info["ady"]["publico"])) {
            $out[] = [
                "tipo" => "soporte_sin_publico",
                "detalle" => "nodo $id (soporte, tiene 'duenos') no tiene 'publico'.",
            ];
        }
    }
    return $out;
}

// ============================================================
// Verificaciones en memoria (warnings)
// ============================================================

/**
 * #11: nodos no alcanzables desde ningún ID especial.
 */
function _auditoria_huerfanos(array $estructura): array {
    $alcanzables = _auditoria_bfs_raices($estructura);
    $out = [];
    foreach ($estructura as $id => $info) {
        if (isset($alcanzables[$id])) continue;
        $out[] = [
            "tipo" => "huerfano",
            "detalle" => "nodo $id dato='" . mb_substr($info["dato"], 0, 40)
                       . "' no es alcanzable desde ninguna raíz.",
        ];
    }
    return $out;
}

/**
 * #12: hijos en hmi/hd (o parametrizados) deben tener p de
 * vuelta apuntando al padre.
 */
function _auditoria_hijos_sin_p(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            $sufijo = null;
            if ($enlace === "hmi" || $enlace === "hd") {
                $sufijo = "";
            } elseif (strpos($enlace, "hmi_") === 0) {
                $sufijo = "_" . substr($enlace, 4);
            } elseif (strpos($enlace, "hd_") === 0) {
                $sufijo = "_" . substr($enlace, 3);
            }
            if ($sufijo === null) continue;
            if (!isset($estructura[$destino_id])) continue;
            $p_enlace = "p" . $sufijo;
            if (!isset($estructura[$destino_id]["ady"][$p_enlace])) {
                $out[] = [
                    "tipo" => "hijo_sin_p",
                    "detalle" => "nodo $id -> '$enlace' -> $destino_id no tiene '$p_enlace'.",
                ];
            } elseif ((string)$estructura[$destino_id]["ady"][$p_enlace] !== (string)$id) {
                $out[] = [
                    "tipo" => "p_apunta_a_otro",
                    "detalle" => "nodo $destino_id tiene '$p_enlace' -> "
                               . $estructura[$destino_id]["ady"][$p_enlace]
                               . " en vez de a $id.",
                ];
            }
        }
    }
    return $out;
}

/**
 * #13: enlaces p (o parametrizados) deben apuntar a un nodo
 * con hmi (o parametrizado) correspondiente.
 */
function _auditoria_p_no_contenedor(array $estructura): array {
    $out = [];
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $destino_id) {
            $sufijo = null;
            if ($enlace === "p") {
                $sufijo = "";
            } elseif (strpos($enlace, "p_") === 0) {
                $sufijo = "_" . substr($enlace, 2);
            }
            if ($sufijo === null) continue;
            if (!isset($estructura[$destino_id])) continue;
            $hmi_enlace = "hmi" . $sufijo;
            if (!isset($estructura[$destino_id]["ady"][$hmi_enlace])) {
                $out[] = [
                    "tipo" => "p_a_no_contenedor",
                    "detalle" => "nodo $id -> '$enlace' -> $destino_id, pero no tiene '$hmi_enlace'.",
                ];
            }
        }
    }
    return $out;
}

/**
 * #14: ciclos en cadenas hd (o hd_<term>).
 */
function _auditoria_ciclos_hd(array $estructura): array {
    $out = [];
    $max_pasos = count($estructura) + 10;
    foreach ($estructura as $id => $info) {
        foreach ($info["ady"] as $enlace => $primer_id) {
            $sufijo = null;
            if ($enlace === "hmi") {
                $sufijo = "";
            } elseif (strpos($enlace, "hmi_") === 0) {
                $sufijo = "_" . substr($enlace, 4);
            }
            if ($sufijo === null) continue;
            $hd_enlace = "hd" . $sufijo;
            $contenedor_id = (string)$id;
            $actual = $primer_id;
            $visitados = [];
            $pasos = 0;
            while ($actual && (string)$actual !== $contenedor_id && $pasos < $max_pasos) {
                if (isset($visitados[$actual])) {
                    $out[] = [
                        "tipo" => "ciclo_hd",
                        "detalle" => "cadena $hd_enlace de $contenedor_id entra en ciclo en $actual.",
                    ];
                    break;
                }
                $visitados[$actual] = true;
                $actual = $estructura[$actual]["ady"][$hd_enlace] ?? null;
                $pasos++;
            }
            if ($pasos >= $max_pasos) {
                $out[] = [
                    "tipo" => "cadena_hd_muy_larga",
                    "detalle" => "cadena $hd_enlace de $contenedor_id excede $max_pasos pasos.",
                ];
            }
        }
    }
    return $out;
}

// ============================================================
// Helpers
// ============================================================

/**
 * BFS desde todos los IDs especiales. Copia local del
 * _grafo_bfs_desde_raices del Controlador, para no
 * tocar el framework.
 *
 * @param array $estructura
 * @return array [id => true]
 */
function _auditoria_bfs_raices(array $estructura): array {
    $alcanzables = [];
    $cola = [];
    foreach ($estructura as $id => $info) {
        if (!is_numeric($id)) {
            $alcanzables[$id] = true;
            $cola[] = $id;
        }
    }
    while (!empty($cola)) {
        $id = array_shift($cola);
        if (!isset($estructura[$id])) continue;
        foreach ($estructura[$id]["ady"] as $destino) {
            if (!isset($alcanzables[$destino])) {
                $alcanzables[$destino] = true;
                $cola[] = $destino;
            }
        }
    }
    return $alcanzables;
}