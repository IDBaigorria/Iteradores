<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77d-05 (documentación).
 *   - prompts/plan_actual.md: refresh de la última actualización,
 *     cadena de cierres v76v–v76y, §1.7 deuda técnica, y detalle
 *     de B2.3.4 / B2.3.5.
 *
 * Sin cambios de código. Solo documentación.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // plan_actual.md — §1.1 última actualización
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: §1.1 última actualización a v77d',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.76v',
            '(reorganización de prompts + inicio de la Fase B2.3).',
            'Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,',
            'ViajeAsientos, Empresa). Próximo paso: **Fase B2.3**, con un',
            'cambio de enfoque respecto del plan original. En lugar del',
            '"compartido plano" (opción A original), se va con la **opción D**:',
            'árboles paralelos con nombres de enlace parametrizados en',
            '`miscelaneas/Arbol.php`. Detalle en §2.3.)',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.77d',
            '(fix del modal de pasajero tras cambiar de asiento).',
            'Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,',
            'ViajeAsientos, Empresa). Después vinieron B2.3.1, B2.3.2 y',
            'B2.3.3 (árboles paralelos parametrizados, aplicados también',
            'en producción). Hubo un paréntesis de bugs (v76z a v77d) que',
            'incluyó el fix de atadura comprador-pasajero, el modal',
            'post-venta, y la funcionalidad completa de "cambiar de',
            'asiento". Próximo paso: **Fase B2.3.4** (ajustar las',
            'escrituras de venta para que inserten y borren en los dos',
            'árboles paralelos cuando el contexto sea un compartido).',
            'Detalle en §2.3.)',
        ],
    ],

    // ============================================================
    // plan_actual.md — cadena de cierres v76v–v76y
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar cierres v76v–v76y',
        'buscar' => [
            '- **v76u**: Fase B2.2.4. Empresa.php: contexto opcional en',
            '  `listar_empresas_de_dueno`. Con esto queda cerrada la',
            '  Fase B2.2.',
            '',
            '**Tanda actual:** v77d (cambiar de asiento: refrescar modal',
            'de pasajero).',
        ],
        'reemplazar' => [
            '- **v76u**: Fase B2.2.4. Empresa.php: contexto opcional en',
            '  `listar_empresas_de_dueno`. Con esto queda cerrada la',
            '  Fase B2.2.',
            '- **v76v**: reorganización de prompts (nuevo `plan_actual.md`,',
            '  §12 del piloto congelada, regla de actualización) +',
            '  parametrización de `miscelaneas/Arbol.php` (las 7 funciones',
            '  aceptan `?array $nombres = null`). Fase B2.3.1.',
            '- **v76w**: helper `_nombres_arbol_para_contexto` y ajuste',
            '  de las funciones del piloto para pasarle los nombres',
            '  parametrizados a `hmi`/`hd`. Sin cambio de comportamiento.',
            '  Fase B2.3.2.',
            '- **v76x**: migración `app:construir_arboles_compartidos`.',
            '  Marca cada compartido con `_es_compartido`, limpia los',
            '  enlaces planos que dejó B2.1 y reconstruye los árboles',
            '  de `ventas` y `cancelaciones` con nombres parametrizados.',
            '  Idempotente. Fase B2.3.3.',
            '- **v76y**: fix de auto-detección de migraciones. Las',
            '  funciones `detectar_*` ya no devuelven `true` por vacío.',
            '  Nuevo comando `app:migracion_limpiar_marcadores` y botón',
            '  "Re-detectar" en la pestaña Grafo.',
            '- **v76z**: fix del modal post-venta. `confirmar_venta_modal`',
            '  muestra el modal ANTES de los refrescos y envuelve los',
            '  refrescos en try/catch.',
            '- **v77 / v77a**: fix del Bug 1 (atadura). v77 no limpia',
            '  campos si hay atadura activa. v77a habilita los campos',
            '  no-DNI del pasajero al activar la atadura.',
            '- **v77b**: backend de "cambiar de asiento".',
            '  `formatear_venta_resumida` agrega `micro_enlace`. Nueva',
            '  función `cambiar_asiento_pasaje` en `ViajeAsientos.php`.',
            '  Subacción `viajes/cambiar_asiento` en el enrutador.',
            '- **v77c**: frontend de "cambiar de asiento". Botón en los',
            '  dos modales de detalle. Función compartida',
            '  `abrir_modal_cambiar_asiento`.',
            '- **v77d**: refresco del modal de pasajero tras cambiar de',
            '  asiento. `abrir_modal_cambiar_asiento` acepta un callback',
            '  `on_exito`.',
            '',
            '**Tanda actual:** v77d-05 (documentación: refresh del plan',
            'actual + preparación de B2.3.4).',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.7 deuda técnica
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.7 deuda técnica',
        'buscar' => [
            '### 1.6 Preguntas abiertas',
            '',
            'Ninguna. La conversación quedó en un punto de pausa limpio.',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
        'reemplazar' => [
            '### 1.6 Preguntas abiertas',
            '',
            'Ninguna. La conversación quedó en un punto de pausa limpio.',
            '',
            '### 1.7 Deuda técnica',
            '',
            '**Uso de `Nodo::nodo_por_id(\'usuarios\')`.** Es la raíz global',
            'del grafo y su uso impide la carga parcial por contextos: para',
            'resolverla hay que tener todo el grafo cargado. El objetivo de',
            'la Fase B es dejar de usarla, resolviendo los nodos por IDs',
            'especiales (`Nodo::nodo_por_id(\'us_<nombre>\')`) o por',
            'navegación de contexto.',
            '',
            '**Inventario actual (22 usos):**',
            '',
            '- **`Venta.php` (8):**',
            '  - `obtener_contenedor_ventas_dueno` — rama sin contexto.',
            '  - `confirmar_venta_actual` — resuelve el terminal.',
            '  - `_buscar_venta_por_id` — rama sin contexto (itera dueños).',
            '  - `cancelar_venta` — resuelve el dueño.',
            '  - `_calcular_cobertura_dueno` — resuelve el dueño.',
            '  - `_crear_nodo_cancelacion` — resuelve el dueño.',
            '  - `obtener_cancelacion_por_id` — resuelve el dueño (itera).',
            '  - `listar_cancelaciones_de_dueno` — resuelve el dueño.',
            '- **`Enrutador.php` (6):**',
            '  - `autenticar/verificar` — dueño de un terminal.',
            '  - Módulo `administrador` — nivel del solicitante.',
            '  - `dueno/listar_sesiones_terminales` — nivel del solicitante.',
            '  - `viajes/limpiar_prueba` — nivel del solicitante.',
            '  - `pasajeros/limpiar_prueba` — nivel del solicitante.',
            '  - Módulo `grafo` — nivel del solicitante.',
            '- **`Viaje.php` (6):**',
            '  - `_contexto_terminal` — terminal y su dueño.',
            '  - `obtener_contenedor_viajes_dueno` — rama sin contexto.',
            '  - `listar_viajes_de_terminal` — terminal y dueño.',
            '  - `viaje_tiene_ventas` — resuelve dueño.',
            '  - `agregar_terminal_autorizada` — resuelve terminal.',
            '  - `formatear_viaje` — resuelve dueño para `empresas`.',
            '- **`Funciones.php` (2):**',
            '  - `detectar_usuarios_especiales`.',
            '  - `detectar_niveles_usuario`.',
            '',
            '**Plan de limpieza:**',
            '',
            '- **B2.3.4 (en curso):** reemplazar los usos que están dentro',
            '  del flujo de ventas y de contexto del terminal',
            '  (`confirmar_venta_actual`, `cancelar_venta`,',
            '  `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion` y',
            '  `_contexto_terminal`). Se reemplazan por',
            '  `Nodo::nodo_por_id(\'us_\' . $nombre)`. Sin cambio visible',
            '  (funciona igual hoy con el grafo completo).',
            '- **B3 (limpieza final):** reemplazar el resto. Los 6 del',
            '  enrutador son los más delicados: requieren que el enrutador',
            '  empiece a pasar contexto (repuntado de B2.3.5). Los fallbacks',
            '  "sin contexto" de las funciones contenedoras se eliminan en',
            '  B3.',
            '',
            '**Contenedores raíz de árboles paralelos.** Cada árbol tiene su',
            'propio contenedor raíz. `privado/ventas` es un nodo, y',
            '`compartido_con_us_termX/ventas` es otro nodo (distinto). Los',
            'nodos venta son compartidos entre árboles (mismo nodo físico)',
            'y cada uno lleva dos juegos de enlaces (`hmi`/`hd`/`p` para el',
            'árbol del dueño, `hmi_<term>`/`hd_<term>`/`p_<term>` para el',
            'del terminal). Es la condición para que la topología aísle',
            'correctamente: si el contenedor fuera el mismo, el BFS desde',
            'el terminal alcanzaría todas las ventas del dueño.',
            '',
            '**Dato del compartido (opción A).** El nodo',
            '`compartido_con_us_termX` tiene `dato = nombre_del_dueño` para',
            'que el código que hace `$nodo_dueno->dato()` siga funcionando',
            'después del repuntado (B2.3.5). **NO** se agrega un enlace',
            '`dueno` en el compartido apuntando al nodo del dueño real:',
            'eso rompería la privacidad topológica (el terminal podría',
            'llegar al dueño por el enlace y desde ahí a todo el grafo).',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
    ],

    // ============================================================
    // plan_actual.md — §2.3 sub-tandas B2.3.4 y B2.3.5
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: detalle de B2.3.4 y B2.3.5',
        'buscar' => [
            '- **B2.3.4** — Repuntar `us_termX → dueno` al compartido.',
            '  Acá se activa el aislamiento. Con pruebas del plugin.',
        ],
        'reemplazar' => [
            '- **B2.3.4** — Ajustar las escrituras de venta para que',
            '  inserten y borren en los dos árboles paralelos. **En curso.**',
            '  Alcance:',
            '  - Reemplazar `Nodo::nodo_por_id(\'usuarios\')->adyacente(...)`',
            '    por `Nodo::nodo_por_id(\'us_\' . $nombre)` en',
            '    `confirmar_venta_actual`, `cancelar_venta`,',
            '    `_calcular_cobertura_dueno`, `_crear_nodo_cancelacion`',
            '    (Venta.php) y `_contexto_terminal` (Viaje.php).',
            '  - En `confirmar_venta_actual`: si el contexto tiene',
            '    `_es_compartido`, insertar la venta también en el árbol',
            '    parametrizado del compartido, además del árbol del dueño',
            '    real. Orden de inserción: dueño primero (default),',
            '    compartido después (parametrizado).',
            '  - En `cancelar_venta`: desenlazar del compartido primero,',
            '    después del árbol del dueño (orden inverso al de la',
            '    inserción).',
            '  - **NO toca el enrutador.** Los 6 chequeos de nivel siguen',
            '    usando `nodo_por_id(\'usuarios\')`. Eso es B3.',
            '  - Dato del compartido: `dato = nombre_dueno` (opción A). No',
            '    se agrega enlace `dueno` en el compartido: apuntar al',
            '    nodo del dueño real rompería la privacidad topológica.',
            '  - Sin pruebas del plugin todavía: el aislamiento no se',
            '    activa hasta B2.3.5.',
            '- **B2.3.5** — Repuntar `us_termX → dueno` al compartido y',
            '  pasar el contexto desde el enrutador. Acá se activa el',
            '  aislamiento. Con pruebas del plugin.',
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