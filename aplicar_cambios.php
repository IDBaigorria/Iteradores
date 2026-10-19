<?php
/**
 * Aplicador — Tanda A1: reescribir §5 de plan_actual como hoja de ruta.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) plan_actual.md §5: reescribir como "Plan global".
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§5: reescribir como hoja de ruta',
        'buscar' => [
            '## 5. CAMINO A LARGO PLAZO',
            '',
            'Planes de mediano/largo plazo, no implementados. No bloquean',
            'el trabajo actual.',
            '',
            '- **B3.3c — 7 helpers que iteran sobre todos los dueños',
            '  buscando por id.** Postergado hasta que aparezca dolor',
            '  real o hasta que lleguen los iteradores con ID especial',
            '  (ver §4.3). La lista completa está en',
            '  `prompt_seguridad.md` §8.6.',
            '- **Iteradores con ID especial + ocupante.** Cuando el',
            '  framework los implemente, los B3.3c se resuelven',
            '  naturalmente. Ver `prompt_framework_iteradores.md`',
            '  §11.5.',
            '- **Framework — dejar el Nodo limpio.** Antes de agregar',
            '  más métodos de indexación de contextos.',
            '- **Framework — JSON64 / XML64** con `guardar_parcial`.',
            '- **Framework — `guardar_parcial` real por delta.**',
            '- **Diversificación del piloto por tipo de aplicación.**',
            '  Próximo tipo: `"tienda"` (catálogo de productos).',
            '  Requiere nodo `tipos_de_aplicacion` como raíz,',
            '  enlace `tipo_app` del dueño, ajustes del login y del',
            '  frontend por tipo. Ver `prompt_piloto.md` §1.2 y §8.7.',
            '- **Multi-cliente.** Cada cliente con su subdominio y su',
            '  propio grafo, misma base de código. `Conf::NOMBRE_APP`',
            '  derivado del subdominio. Ver `prompt_piloto.md` §1.4.',
            '',
            '---',
            '',
            '## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante',
        ],
        'reemplazar' => [
            '## 5. PLAN GLOBAL — HOJA DE RUTA',
            '',
            'Mapa completo del proyecto. Se actualiza cuando una fase',
            'se cierra o se abre una nueva. El detalle de cada fase',
            'vive en el prompt correspondiente.',
            '',
            '### 5.1. Modelo topológico del piloto',
            '',
            'Plan de fases A → B → C → D, con la seguridad emergiendo',
            'de la topología. Detalle de cada fase en',
            '`prompt_seguridad.md` §3-5 y §8.6.',
            '',
            '| Fase | Qué hace | Estado |',
            '|---|---|---|',
            '| **A** — Usuarios como IDs especiales `us_<nombre>` | Reemplazo de la navegación `usuarios → dueño` por `us_<dueño>`. | ✅ v76k |',
            '| **B1** — Contenedores `publico`/`privado` | Cada usuario tiene contenedores por nivel de exposición. | ✅ v76m |',
            '| **B2.1** — Compartidos por terminal | Contenedores `compartido_con_<terminal>` colgando del dueño. | ✅ v76q |',
            '| **B2.2** — Contexto opcional | Viaje, Venta, ViajeAsientos y Empresa aceptan `?Nodo $nodo_contexto`. | ✅ v76r-v76u |',
            '| **B2.3** — Árboles paralelos | Ventas con `hmi`/`hd`/`p` y `hmi_<term>`/`hd_<term>`/`p_<term>`. | ✅ v76v-v77o |',
            '| **B2.4** — Mantener compartidos vivos | Sincronización en cada operación de escritura. | ✅ v77q |',
            '| **B3.1** — Accesos viejos eliminados | `usuarios → dueño` → `us_<dueño>` en todos los módulos principales. | ✅ v77r |',
            '| **B3.3a-e** — Módulos menores adaptados | Liquidacion, Rendicion, Empresa, Vehiculo, y Vehiculo JS. | ✅ v77t-v77x + v81h |',
            '| **B3.3c** — 3 helpers que iteran `usuarios` | `_buscar_venta_por_id` (fallback), `obtener_cancelacion_por_id`, `obtener_pasajero_por_dni` (rama historial). | **Pendiente — próximo arco** |',
            '| **C** — Tipos como IDs especiales (opcional) | Roots explícitos `tipo_empresas`, `tipo_viajes`, etc. | Pendiente |',
            '| **D** — Carga parcial como optimización real | Guardado por delta. Bloqueada por framework Fase 4. | Bloqueada |',
            '',
            'Con **B3.3c** cerrado, la **Fase B queda completa**.',
            '',
            '### 5.2. Framework Iteradores',
            '',
            'Plan de fases del framework. Detalle en',
            '`prompt_framework_iteradores.md` §11.4 y §11.5.',
            '',
            '| Fase | Qué hace | Estado |',
            '|---|---|---|',
            '| SQL64 (bitmask + 3 tablas nuevas) | `nodo_contexto`, `adyacente_contexto`, `contexto`. | ✅ 1.5i.7k |',
            '| IndexedDB64 (espejo JS) | Misma API, base `HyS_ctx`. | ✅ V1.5i.7k |',
            '| `guardar_parcial` real (SQL64) | Filtro por `contexto_mask`. | ✅ 1.5i.7o |',
            '| Propagación de máscaras a nodos nuevos | `_guardar_con_filtro` con BFS por enlaces entrantes. | ✅ 1.5i.8c |',
            '| **JSON64 / XML64** con `guardar_parcial` | Fase 3 del framework. | Pendiente |',
            '| **`guardar_parcial` real por delta** | Delta real, no merge en memoria. Bloquea Fase D del piloto. | Pendiente |',
            '| **Dejar el Nodo limpio** | Mover `contexto_mascara` fuera del Nodo. Bloquea 256 contextos. | Pendiente |',
            '| **256 contextos / producto de primos** | Fase 5 del framework, si hace falta. | Pendiente |',
            '| **Iteradores con ID especial + ocupante** | Raíces de subgrafo con posición mutable. Resolvería B3.3c. | Pendiente |',
            '',
            '### 5.3. Diversificación del piloto',
            '',
            '- **Tipos de aplicación.** Próximo tipo: `"tienda"`',
            '  (catálogo de productos). Requiere nodo',
            '  `tipos_de_aplicacion` como raíz, enlace `tipo_app` del',
            '  dueño, ajustes del login y del frontend por tipo. Ver',
            '  `prompt_piloto.md` §1.2.',
            '- **Multi-cliente.** Cada cliente con su subdominio y su',
            '  propio grafo, misma base de código.',
            '  `Conf::NOMBRE_APP` derivado del subdominio. Ver',
            '  `prompt_piloto.md` §1.4.',
            '',
            '### 5.4. Otros frentes',
            '',
            'Calidad, plugin y visualizador. Detalle en §4.',
            '',
            '- **Piloto — calidad** (§4.1): auditar estados colgados,',
            '  auditar estructuras nuevas, deuda de seguridad en',
            '  impresión.',
            '- **Plugin** (§4.2): migrar persistencia de la pestaña',
            '  activa a IndexedDB, pruebas 37/38 con `try/finally`,',
            '  centralizar prefijos.',
            '- **Visualizador de grafo** (§4.4): Fase 5 (persistencia',
            '  de posiciones/zoom/layout), colores para tipos',
            '  inferidos por enlace, patrón de grilla adaptativo al',
            '  zoom.',
            '',
            '---',
            '',
            '## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante',
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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";