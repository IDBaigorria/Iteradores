<?php
/**
 * Aplicador — Tanda U: anotar el plan del arco de los 4 módulos menores.
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
// Sin bumps automáticos: solo se toca un .md.
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) §6: reemplazar "A definir" por el plan completo del arco.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§6: plan del arco de los 4 módulos menores',
        'buscar' => [
            '## 6. PRÓXIMO ARCO',
            '',
            'A definir. Cuando el usuario lo proponga, este bloque se',
            'actualiza con:',
            '',
            '- **Motivación** (por qué se hace).',
            '- **Decisiones tomadas.**',
            '- **Fases o tandas** con checklist.',
            '- **Archivos tocados.**',
            '- **Pendientes que abre.**',
            '',
            'Los candidatos que hoy están en la lista (sin prioridad) son',
            'los de §4. La decisión depende del usuario.',
        ],
        'reemplazar' => [
            '## 6. ARCO EN CURSO — 4 módulos menores al contexto del solicitante',
            '',
            '**Estado:** planificado. Sin código todavía.',
            '',
            '### 6.1. Motivación',
            '',
            'Los módulos `Empresa.php`, `Vehiculo.php`,',
            '`Liquidacion.php` y `Rendicion.php` siguen navegando desde la',
            'raíz `usuarios` en varias funciones. Bajo **carga parcial**',
            'esa raíz solo está en el grafo del admin (vía',
            '`us_Administrador → usuarios`). Para **dueño** y **soporte**',
            'no está: cualquier `nodo_por_id(\'usuarios\')` devuelve null y',
            'la operación falla silenciosa o cae a "no encontrado".',
            '',
            'El terminal **no** ve estas pestañas, así que no es un',
            'problema de terminal. Es de dueño y soporte.',
            '',
            '### 6.2. Diagnóstico',
            '',
            '- **Admin:** tiene `us_Administrador → usuarios`. Los 4',
            '  módulos andan.',
            '- **Dueño:** carga parcial `[us_<dueño>]`. Sin `usuarios`.',
            '  Falla cualquier navegación por la raíz.',
            '- **Soporte:** carga parcial `[us_<soporte>] → duenos →',
            '  us_<dueño>`. Puede llegar a `us_<dueño>` directo, pero no',
            '  a `usuarios` global. Mismo problema que dueño.',
            '- **Terminal:** no aplica (no ve estas pestañas).',
            '',
            'Es el mismo patrón de la Fase B3.1 (v77r), aplicado a los',
            'módulos que quedaron sin tocar.',
            '',
            '### 6.3. Decisiones tomadas',
            '',
            '- **Cambio de navegación:** `usuarios → dueño → X` pasa a',
            '  `us_<dueño> → X` (ID especial directo).',
            '- **Funciones que buscan por id:** reciben',
            '  `?string $nombre_dueno` y navegan desde `us_<dueño>`.',
            '  Mismo patrón que `obtener_venta_por_id` en `Venta.php`.',
            '- **Enrutador:** `_nombre_dueno_solicitante($sol, $post)`',
            '  provee el `$nombre_dueno` a esas funciones.',
            '- **Sin cambios de API pública** para el frontend: los',
            '  POST y las respuestas quedan iguales.',
            '- **Empresa y Vehiculo** (contenedores planos): cambio de',
            '  navegación. **Liquidacion y Rendicion** (árboles hmi/hd):',
            '  cambio de navegación + `$nombre_dueno` en las búsquedas',
            '  por id.',
            '',
            '### 6.4. Fases',
            '',
            '- [ ] **Fase 1 — Auditoría.** Leer los 4 archivos y el',
            '  Enrutador completos. Listar todos los',
            '  `nodo_por_id(\'usuarios\')` y todos los casos donde un',
            '  `$nombre_dueno` no se usa para navegar directo.',
            '  Confirmar diagnósticos y ajustar si hay sorpresas.',
            '  *Solo lectura, sin código.*',
            '',
            '- [ ] **Fase 2 — Los 4 módulos + Enrutador.** Una sola',
            '  tanda con bloques por módulo.',
            '  *Archivos:* `Aplicacion/Empresas/Empresa.php`,',
            '  `Aplicacion/Vehiculos/Vehiculo.php`,',
            '  `Aplicacion/Liquidaciones/Liquidacion.php`,',
            '  `Aplicacion/Rendiciones/Rendicion.php`,',
            '  `Aplicacion/Enrutador.php`.',
            '  *Bumps:* cada archivo tocado a `1.5piloto.81h`.',
            '',
            '- [ ] **Fase 3 — Verificación manual.**',
            '  - Login **dueño**: Rendiciones, Liquidaciones,',
            '    Empresas/Micros cargan sin errores.',
            '  - Login **soporte** (con un dueño asignado): idem.',
            '  - Login **admin**: no se rompe nada.',
            '  - Si algo falla, fix chico antes de las pruebas del',
            '    plugin.',
            '',
            '- [ ] **Fase 4 — Pruebas del plugin (espejo).**',
            '  Tres pruebas mínimas:',
            '  - `rendiciones_listar_carga_parcial_dueno`: login',
            '    dueño, `rendiciones/listar`, verificar que devuelve',
            '    sin error.',
            '  - `liquidaciones_listar_carga_parcial_dueno`: idem.',
            '  - `empresas_listar_carga_parcial_soporte`: login',
            '    soporte, elegir un dueño asignado,',
            '    `empresas/listar`, verificar sin error.',
            '  *Archivo:* `iteradoresJS/Aplicacion/pruebas/`.',
            '  *Commit separado en el repo del plugin.*',
            '',
            '- [ ] **Fase 5 — Documentación.**',
            '  - `prompt_piloto.md` §5.11-5.12: si cambian firmas,',
            '    anotarlo.',
            '  - `plan_actual.md` §2: una línea con el arco cerrado.',
            '  - `prompt_seguridad.md` §8.6 (B3.3c): de los 7',
            '    helpers, quedan 3 sin adaptar (los de `Venta.php` y',
            '    la rama de historial de `Pasajero.php`).',
            '',
            '### 6.5. Pendientes que abre',
            '',
            '- **B3.3c se reduce:** de 7 helpers sin adaptar a 3',
            '  (`Venta.php`: `_buscar_venta_por_id` fallback y',
            '  `obtener_cancelacion_por_id`; `Pasajero.php`:',
            '  `obtener_pasajero_por_dni` rama del historial de',
            '  ventas).',
            '- **Verificación con soporte real:** hoy las pruebas del',
            '  plugin no cubren el caso "soporte opera sobre un dueño',
            '  asignado". Las 3 pruebas nuevas de la Fase 4 lo hacen',
            '  para Empresas. Si en el futuro aparecen más módulos con',
            '  este patrón, replicar.',
            '- **Falta de cobertura en rendiciones/liquidaciones',
            '  para soporte:** las pruebas de la Fase 4 cubren',
            '  `rendiciones/listar` y `liquidaciones/listar` con',
            '  dueño, pero no con soporte. Se puede agregar después.',
        ],
    ],

    // ============================================================
    // B) §4.1: marcar el ítem de los 4 módulos como en curso.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§4.1: marcar los 4 módulos como en curso',
        'buscar' => [
            '### 4.1. Piloto — calidad y consistencia (media)',
            '',
            '- **Adaptar los 4 módulos menores al contexto del',
            '  solicitante:** `Liquidacion.php`, `Rendicion.php`,',
            '  `Empresa.php`, `Vehiculo.php`. Tanda repetitiva (misma',
            '  técnica que Viaje/Venta).',
        ],
        'reemplazar' => [
            '### 4.1. Piloto — calidad y consistencia (media)',
            '',
            '- **Adaptar los 4 módulos menores al contexto del',
            '  solicitante** — **EN CURSO**, ver §6.',
            '  `Liquidacion.php`, `Rendicion.php`, `Empresa.php`,',
            '  `Vehiculo.php`.',
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