<?php
/**
 * Aplicador — plan_actual.md: §4 Pendientes consolidados.
 *
 * Agrega al final de plan_actual.md una seccion con TODOS los
 * pendientes abiertos, agrupados por naturaleza (cierre del
 * arco, piloto, framework, plugin).
 *
 * Es el indice central. Los prompts especificos (prompt_piloto
 * §8.5, prompt_plugin §9, prompt_framework §11) mantienen sus
 * listas propias pero mas chicas.
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: §4 pendientes consolidados',
        'buscar' => [
            '**FIN DEL PLAN ACTUAL**',
        ],
        'reemplazar' => [
            '---',
            '',
            '## 4. PENDIENTES CONSOLIDADOS',
            '',
            '**Estado al cierre:** piloto v1.5piloto.80j, plugin',
            'v1.5plugin.7y, framework PHP 1.5i.8c, framework JS',
            'V1.5i.8c. **66 pruebas del plugin corren en verde.**',
            '',
            'Lista de todo lo abierto, agrupada por naturaleza.',
            'Cada item tiene un origen (donde esta el detalle) y',
            'una prioridad estimada.',
            '',
            '### 4.1. Cierre del arco actual (Piloto + Plugin)',
            '',
            'Estos son los que cierran formalmente el plan §3 y la',
            'linea de UI del plugin. Prioridad alta.',
            '',
            '- **Fase 3 del plan §3: limpiar el piloto.** Sacar',
            '  `estilos-botones-admin.css`, el link en',
            '  `aplicacion_GET.html`, `_aplicar_clases_de_rol` + sus',
            '  llamadas en `aplicacion.js`, los botones viejos de',
            '  limpieza del HTML, y la logica JS asociada.',
            '  Los endpoints backend se quedan (los usa el plugin).',
            '  Ver §3.2 del plan original.',
            '',
            '- **Persistencia de la pestaña activa del plugin.**',
            '  Migrar de `localStorage[\'plugin_tab_activa\']` a la',
            '  persistencia del framework. Anotado en el codigo',
            '  como `ANOTADO`.',
            '',
            '- **Pruebas 37 y 38 sin `try/finally`.** Si el flujo',
            '  falla a mitad, la empresa/vehiculo de setup queda',
            '  colgada. Los limpiadores la agarran, pero la prueba',
            '  deberia limpiar por su cuenta.',
            '',
            '### 4.2. Piloto — calidad y consistencia',
            '',
            'Prioridad media. Mejoran la consistencia del piloto',
            'con la carga parcial y cierran deudas tecnicas.',
            '',
            '- **Adaptar los 4 modulos menores al contexto del',
            '  solicitante:** `Liquidacion.php`, `Rendicion.php`,',
            '  `Empresa.php`, `Vehiculo.php`. Tanda repetitiva',
            '  (misma tecnica que Viaje/Venta). Ver §1.11.',
            '',
            '- **Auditar estados colgados:** reservas sin pasajero,',
            '  DJ a medias, edicion de viaje a mitad. Requiere',
            '  relevamiento primero. Ver §1.11.',
            '',
            '- **Auditar estructuras nuevas** con la regla de',
            '  deteccion de §5.9 del prompt de seguridad. Preventivo.',
            '',
            '- **B3.3c — 7 helpers que iteran sobre todos los',
            '  dueños buscando por id.** Postergado hasta que',
            '  aparezca dolor real o lleguen los iteradores con ID',
            '  especial. Ver §1.11.',
            '',
            '- **Deuda de seguridad en impresion.** Las URLs de',
            '  impresion (`?imprimir=1&tipo=...`) no validan sesion',
            '  ni permisos. Cualquiera con la URL ve los datos.',
            '  Ver §1.11.',
            '',
            '### 4.3. Framework Iteradores',
            '',
            'Prioridad baja hasta que el piloto lo requiera. Van',
            'todos juntos en una sesion del framework. Ver §11 del',
            'prompt del framework.',
            '',
            '- **Dejar el Nodo limpio.** Mover `contexto_mascara` a',
            '  la capa de persistencia antes de agregar mas metodos',
            '  de indexacion (256 bits, producto de primos).',
            '  Bloqueante de la Fase 5. Ver §11.4.',
            '',
            '- **Fase 3 del framework: JSON64 / XML64** con',
            '  `guardar_parcial`. Ver §11.4.',
            '',
            '- **Fase 4 del framework: `guardar_parcial` real por',
            '  delta** (no merge en memoria). Ver §11.4.',
            '',
            '- **Fase 5 (si hace falta): 256 contextos / producto de',
            '  primos.** Ver §11.4 y §11.1.',
            '',
            '- **Iteradores con ID especial + ocupante como raices',
            '  de subgrafo.** Resolveria los B3.3c. Ver §11.5.',
            '',
            '### 4.4. Plugin — mejoras futuras',
            '',
            'Prioridad baja. Se abordan cuando aparezcan pruebas',
            'nuevas o necesidad concreta.',
            '',
            '- **Centralizar prefijos de prueba.** Hoy cada',
            '  limpiador del piloto tiene su lista hardcodeada.',
            '  Cuando una prueba nueva usa un prefijo distinto, el',
            '  limpiador no lo reconoce. Idea: una constante',
            '  compartida o que las pruebas registren su prefijo.',
            '  Ver §1.12.',
            '',
            '- **Secciones vacias del plugin:** Rendiciones y',
            '  Liquidaciones esperan pruebas nuevas. Ver §1.13.',
            '',
            '- **Linting del payload del plugin:** verificar que',
            '  ninguna llamada a `_post` a acciones que requieren',
            '  `nombre_dueno` salga sin el. Se pospone hasta que',
            '  aparezca necesidad concreta. Ver §1.6.',
            '',
            '### 4.5. Proximas tandas sugeridas',
            '',
            'En orden:',
            '',
            '1. **Fase 3 del plan §3** (limpiar el piloto).',
            '2. **Migrar persistencia de la pestaña activa al',
            '   framework.**',
            '3. **Adaptar los 4 modulos menores.**',
            '4. **Auditar estados colgados.**',
            '5. **Auditar estructuras nuevas.**',
            '6. Resto de los pendientes.',
            '',
            '---',
            '',
            '**FIN DEL PLAN ACTUAL**',
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