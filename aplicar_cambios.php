<?php
/**
 * Aplicador — Tanda Z (bis): cierre del arco + foco en calidad.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) prompt_seguridad.md §8.6: corregir conteo.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.6: corregir conteo 4 → 3',
        'buscar' => [
            '**Reducido a 4 funciones** (v81h; antes eran 7). Las',
            'funciones de liquidación y rendición se adaptaron al patrón',
            '`?string $nombre_dueno`:',
        ],
        'reemplazar' => [
            '**Reducido a 3 funciones.** Nota sobre el conteo: el texto',
            'original decía "7" (era un typo: las funciones realmente',
            'listadas eran 6). De esas 6, se adaptaron 3 en v81h',
            '(liquidación y rendición). Las 3 restantes siguen',
            'pendientes.',
            '',
            'Las funciones de liquidación y rendición se adaptaron al',
            'patrón `?string $nombre_dueno`:',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.6: "las 4 restantes" → "las 3 restantes"',
        'buscar' => [
            '**Consecuencia:** con carga parcial, las 4 restantes',
            'devuelven null o vacío si el solicitante no tiene `usuarios`',
            'en memoria (dueño y soporte). Se resuelven cuando aparezca',
            'dolor real o cuando implementemos iteradores con ID especial.',
        ],
        'reemplazar' => [
            '**Consecuencia:** con carga parcial, las 3 restantes',
            'devuelven null o vacío si el solicitante no tiene `usuarios`',
            'en memoria (dueño y soporte). Se resuelven cuando aparezca',
            'dolor real o cuando implementemos iteradores con ID especial.',
        ],
    ],

    // ============================================================
    // B) plan_actual.md §1.1: cabecera a v81i (texto real).
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1.1: cabecera a v81i',
        'buscar' => [
            '**Última actualización:** v1.5piloto.81g (cierre del arco',
            'del visualizador de grafo).',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.81i (cierre del arco',
            'de los 4 módulos menores).',
            '',
            'Empresa.php y Vehiculo.php ya estaban adaptados',
            '(v76u-v77v); Liquidacion.php y Rendicion.php se adaptaron',
            'en v81h. El fix v81i (el frontend no mandaba',
            '`nombre_dueno` en `rendiciones/obtener`,',
            '`rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`)',
            'dejó la verificación manual y las 3 pruebas del plugin',
            'en verde. B3.3c reducido a 3 helpers (antes el texto',
            'decía "7", era un typo: eran 6; se adaptaron 3).',
            '',
            '**Antes (v81g):** cierre del arco del visualizador de',
            'grafo. Antes (v79o): cierre de la línea "grafo + plugin +',
            'sistema de scripts". Carga parcial activa. La topología',
            'sigue siendo la fuente de la seguridad: el bitmask lo',
            'propaga el framework, no la app.',
        ],
    ],

    // ============================================================
    // C) plan_actual.md §4.1: módulos menores cerrados.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§4.1: módulos menores cerrados',
        'buscar' => [
            '### 4.1. Piloto — calidad y consistencia (media)',
            '',
            '- **Adaptar los 4 módulos menores al contexto del',
            '  solicitante** — **EN CURSO**, ver §6.',
            '  `Liquidacion.php`, `Rendicion.php`, `Empresa.php`,',
            '  `Vehiculo.php`.',
        ],
        'reemplazar' => [
            '### 4.1. Piloto — calidad y consistencia (media)',
            '',
            '- [x] **Adaptar los 4 módulos menores al contexto del',
            '  solicitante** — CERRADO (v81h + v81i, ver §6).',
            '  `Empresa.php` y `Vehiculo.php` ya estaban adaptados',
            '  (fases v76u-v77v); `Liquidacion.php` y `Rendicion.php`',
            '  se adaptaron en v81h. El frontend del piloto no mandaba',
            '  `nombre_dueno` en `rendiciones/obtener`,',
            '  `rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`',
            '  (bug v81i); solo fallaba con soporte. Corregido en',
            '  `rendiciones.js` y `liquidaciones.js`.',
            '- **B3.3c restante (3 helpers).** `Venta.php`:',
            '  `_buscar_venta_por_id` (fallback) y',
            '  `obtener_cancelacion_por_id`. `Pasajero.php`:',
            '  `obtener_pasajero_por_dni` (rama del historial de',
            '  ventas). Postergado hasta que aparezca dolor real o',
            '  hasta que el framework tenga iteradores con ID especial.',
        ],
    ],

    // ============================================================
    // D) plan_actual.md: AGREGAR §4.5 con foco en calidad.
    //    (buscar el cierre de §4.4 + transición a §5)
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'Agregar §4.5 próximas tandas con foco en calidad',
        'buscar' => [
            '- **Tamaño del patrón de la grilla adaptativo al zoom**',
            '  (opcional). Hoy el patrón mide 40×40 user units fijo.',
            '',
            '---',
            '',
            '## 5. CAMINO A LARGO PLAZO',
        ],
        'reemplazar' => [
            '- **Tamaño del patrón de la grilla adaptativo al zoom**',
            '  (opcional). Hoy el patrón mide 40×40 user units fijo.',
            '',
            '### 4.5. Próximas tandas sugeridas',
            '',
            '**Foco inmediato: Piloto — calidad.**',
            '',
            'Con el arco de los 4 módulos menores cerrado (v81h +',
            'v81i), el foco pasa a los pendientes de calidad del',
            'piloto (§4.1). Candidatos, en orden tentativo a definir',
            'con el usuario:',
            '',
            '1. **Auditar estados colgados.** Reservas sin pasajero,',
            '   DJ a medias, edición de viaje a mitad. Requiere',
            '   relevamiento primero. Empezar por "cierre de navegador',
            '   a mitad de venta".',
            '2. **Auditar estructuras nuevas** con la regla de',
            '   detección de §5.9 del prompt de seguridad. Preventivo.',
            '3. **Deuda de seguridad en impresión.** Las URLs',
            '   `?imprimir=1&tipo=...` no validan sesión ni permisos.',
            '   Cualquiera con la URL ve los datos. Preexistente.',
            '4. **B3.3c restante (3 helpers).** Cuando aparezca dolor',
            '   real o lleguen los iteradores con ID especial.',
            '',
            '**Después, según prioridad:**',
            '',
            '- **Migrar persistencia de la pestaña activa del plugin**',
            '  (`localStorage` → IndexedDB).',
            '- **Visualizador de grafo, Fase 5** (persistencia de',
            '  posiciones, zoom y layout).',
            '- **Framework Iteradores:** dejar el Nodo limpio,',
            '  JSON64/XML64, `guardar_parcial` por delta, iteradores',
            '  con ID especial.',
            '- **Diversificación por tipo de aplicación** (`tienda`).',
            '',
            '---',
            '',
            '## 5. CAMINO A LARGO PLAZO',
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