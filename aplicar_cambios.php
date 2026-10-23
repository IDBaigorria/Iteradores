<?php
/**
 * Aplicador — actualización de plan_actual.md al estado v1.5piloto.81o.
 *
 * Uso (raíz del proyecto PHP, iteradores/codigo.worktrees/v1.5i/):
 *   php aplicar_cambios.php
 */

$modo_estricto = false;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

    // ============================================================
    // 1. plan_actual.md §1: estado actual
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §1: estado a v81o',
        'buscar' => [
            '**Última actualización:** v1.5piloto.81m (cierre de B3.3c',
            '+ rediseño soportes plural).',
            '',
            'En v81j se adaptaron los 3 helpers de B3.3c. En v81k se',
            'arregló el frontend de ventas.js (no mandaba `nombre_dueno`',
            'en 4 fetch, mismo patrón que el bug v81i de rendiciones).',
            'En v81l se rediseñó `soporte` singular → `soportes`',
            'contenedor plural, y se reordenó `agregar_usuario` para que',
            '`_asegurar_niveles_usuario` corra antes de',
            '`_asignar_duenos_a_soporte` (causa raíz del enlace al nodo',
            'real del soporte). En v81m se actualizaron los prompts.',
            '**Con B3.3c cerrado, la Fase B del modelo topológico quedó',
            'completa.**',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.81o (fix de',
            '`reparar_arboles` + estado del bug de cancelación desde',
            'terminal post-migración).',
            '',
            'En v81j se adaptaron los 3 helpers de B3.3c. En v81k se',
            'arregló el frontend de ventas.js (no mandaba `nombre_dueno`',
            'en 4 fetch, mismo patrón que el bug v81i de rendiciones).',
            'En v81l se rediseñó `soporte` singular → `soportes`',
            'contenedor plural, y se reordenó `agregar_usuario` para que',
            '`_asegurar_niveles_usuario` corra antes de',
            '`_asignar_duenos_a_soporte`. En v81m se actualizaron los',
            'prompts. En v81n se corrigió `_reparar_arboles_precalcular`',
            'para que indexe por nombre de enlace (antes borraba el',
            '`hmi_<term>` de los compartidos, dejando ventas huérfanas).',
            '**Con B3.3c cerrado, la Fase B del modelo topológico quedó',
            'completa.** El bug de cancelación desde terminal',
            'post-migración está en investigación (ver §4.6).',
        ],
    ],

    // ============================================================
    // 2. plan_actual.md §2: arcos cerrados (agregar v81n)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §2: agregar arco v81n',
        'buscar' => [
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **B3.3c — 3 helpers que iteraban `usuarios` (v81j-v81m).**',
        ],
        'reemplazar' => [
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **Fix de `reparar_arboles` (v81n).** El precalcular',
            '  indexa por nombre de enlace (`p`, `hd`, `p_<term>`,',
            '  `hd_<term>`), no por nombre fijo. Antes, para un',
            '  compartido, `$por_padre[$id_cont]` venía vacío y',
            '  `_reparar_arbol` borraba el `hmi_<term>`. Eso rompía',
            '  el árbol del compartido: las ventas viejas quedaban',
            '  huérfanas y el terminal solo veía las nuevas. Test de',
            '  aceptación: `?reparar_arboles=1` dos veces seguidas',
            '  debe dar `cambios: 0`.',
            '- **B3.3c — 3 helpers que iteraban `usuarios` (v81j-v81m).**',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md: agregar §4.6 (bug en investigación)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual.md §4.6: bug en investigación',
        'buscar' => [
            '## 5. PLAN GLOBAL — HOJA DE RUTA',
        ],
        'reemplazar' => [
            '### 4.6. Bug en investigación: cancelación desde terminal post-migración',
            '',
            'Cuando un terminal cancela una venta después de las',
            'migraciones (con `reparar_arboles` v81n aplicado), el',
            'backend responde `exito: true` con `id_cancelacion`, pero:',
            '',
            '- La cancelación **no se persiste** en la DB.',
            '- El asiento sigue vendido en `viajes/estado_asientos`.',
            '- `cancelaciones/listar` devuelve `[]`.',
            '',
            'Reproducido en el servidor de pruebas (InfinityFree,',
            '`wait_timeout = 20`). En local no se reproduce.',
            '',
            '**Hipótesis abiertas:**',
            '',
            '- **Frontend no dispara el `fetch ventas/cancelar`.** En',
            '  el log de la consola del navegador no aparece el bloque',
            '  `[fetch] index.php` con `body accion: "ventas/cancelar"`.',
            '  Falta confirmar si el `confirm()` del modal aparece al',
            '  clickear "Cancelar venta". Si no aparece, el listener',
            '  no está conectado.',
            '- **Árbol del compartido con `hmi` vs `hmi_<term>`',
            '  inconsistentes.** `construir_arboles_compartidos` arma',
            '  el árbol con nombres parametrizados. Pero',
            '  `_crear_nodo_cancelacion` inserta con `_hmi` default.',
            '  Si el contenedor ya tiene `hmi_secretaria_carmen`, el',
            '  `_hmi` default no lo toma en cuenta y la nueva queda',
            '  huérfana. `listar_cancelaciones_de_dueno` busca en `hmi`',
            '  default y devuelve `[]`.',
            '- **`guardar_parcial([us_carmen2])` falla silenciosamente.**',
            '  El subgrafo del terminal no es chico (~1000 nodos si',
            '  incluye pasajeros alias). En InfinityFree con',
            '  `wait_timeout = 20`, el guardado puede morir a mitad,',
            '  PHP no lo detecta, `guardar_parcial` retorna `true`',
            '  sin commitear. Descartable si el log del navegador no',
            '  muestra el fetch.',
            '',
            '**Cómo seguir:**',
            '',
            '1. Capturar `[fetch] ventas/cancelar` en la consola del',
            '   navegador. Si aparece con `exito: true` y el count SQL',
            '   de `cancelacion_%` no sube, es la hipótesis del',
            '   `guardar_parcial` o del árbol roto.',
            '2. Si el `confirm()` del modal no aparece al clickear',
            '   "Cancelar venta", es bug del frontend (listener',
            '   desconectado).',
            '3. Correr `SELECT COUNT(*) FROM nodo_contexto WHERE',
            '   idsuperestructura=... AND CAST(dato AS CHAR) LIKE',
            '   \'cancelacion_%\'` antes y después del intento.',
            '',
            '## 5. PLAN GLOBAL — HOJA DE RUTA',
        ],
    ],

];

// ============================================================
// Runner v2 (corregido: escribe cambios parciales en no estricto)
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

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) $htmls_a_bumpear = [];
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['buscar']) || !isset($cambio['reemplazar'])) continue;
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if ($contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    if (file_put_contents($ruta_abs, normalizar_a_original($contenido_unix, $eol)) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n"; continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";