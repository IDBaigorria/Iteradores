<?php
/**
 * Aplicador — Actualizar prompts/plan_actual.md con el cierre de
 * la Fase 2c.
 *
 * Solo documentacion. No toca prompt_piloto.md (no cambio la
 * estructura del piloto: solo se agregaron endpoints).
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
        'descripcion' => 'plan_actual: agregar §1.11 (cierre de Fase 2c)',
        'buscar' => [
            'Detalle completo en `prompt_framework_iteradores.md` §11.5.',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
        'reemplazar' => [
            'Detalle completo en `prompt_framework_iteradores.md` §11.5.',
            '',
            '### 1.11 Cierre de la Fase 2c — migración del plugin a `_post`',
            '',
            '**Estado:** cerrada entre v1.5piloto.80a y v1.5piloto.80e',
            '(del lado del piloto) y v1.5plugin.7a-7k (del lado del',
            'plugin).',
            '',
            '**Qué se hizo.**',
            '',
            '- **Fase 1 de §3 (v1.5piloto.80a):** 5 endpoints backend',
            '  de limpieza de prueba:',
            '  - `ventas/limpiar_prueba` (comprador con email',
            '    `@test.local`).',
            '  - `cancelaciones/limpiar_prueba` (motivo con',
            '    "prueba"/"test").',
            '  - `empresas/limpiar_prueba` (prefijos `fotoauto`',
            '    y `viajecambiomicro_*`).',
            '  - `administrador/limpiar_usuarios_prueba`',
            '    (nombre_usuario con prueba/test/temp).',
            '  - `dueno/limpiar_terminales_prueba` (prefijo',
            '    `termprueba`).',
            '  Todos chequean admin + modo pruebas. Saltean',
            '  usuarios/entidades con referencias entrantes',
            '  (conservador).',
            '',
            '- **Fase 2 (plugin, v1.5plugin.7a-7k):** migración completa',
            '  de las ~50 pruebas y 3 helpers auxiliares al wrapper',
            '  `_post(ctx, accion, params, opts)` que delega en',
            '  `ctx.post_accion(accion, params, opts)`. Este último:',
            '  - resuelve `nombre_solicitante` del logueado (con',
            '    cache invalidado en `asegurar_login` y',
            '    `cerrar_sesion`),',
            '  - inyecta `nombre_dueno` si no viene en params,',
            '  - soporta `sin_solicitante`, `sin_dueno`,',
            '    `devolver_crudo`, `lanzar_en_error_backend`,',
            '  - respeta `params.nombre_solicitante` explícito',
            '    (para casos "admin opera como terminal").',
            '',
            '- **v1.5piloto.80b:** subir `BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS`',
            '  de 2 a 10. Con el grafo actual (~3000 nodos), los 5',
            '  intentos fallidos consecutivos de la prueba 54 tardan',
            '  más que 2s. El bloqueo funcionaba; era timing.',
            '',
            '- **v1.5piloto.80c:** logs `[DIAG-AUTH]` temporales en',
            '  `Autenticacion.php` para diagnosticar lo anterior.',
            '',
            '- **v1.5piloto.80d:** limpieza — se sacaron los logs',
            '  temporales y se arregló el doble `time()` en',
            '  `_esta_bloqueado` (bug latente: la comparación podía',
            '  fallar en el borde del segundo).',
            '',
            '**Lecciones aprendidas.**',
            '',
            '1. **`post_accion` cambia el modo de carga del backend.**',
            '   Antes, `ctx.pedir_post` sin `nombre_solicitante` hacía',
            '   **carga total** (grafo completo en memoria). Con',
            '   `post_accion`, el backend hace **carga parcial**',
            '   (`us_<sol>`) y no puede navegar `us_<dueño>` si el',
            '   logueado no es el dueño. Las pruebas que necesitan',
            '   carga total usan `{sin_solicitante: true,',
            '   sin_dueno: true}`. Documentado en el prompt del',
            '   plugin (§8, lección 57).',
            '',
            '2. **Si un fix no resuelve, verificar primero que los',
            '   fixes previos se hayan aplicado.** Costó 3 iteraciones',
            '   en la prueba 54 por no chequear el valor real de',
            '   `BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS`.',
            '',
            '3. **Pruebas con tiempos de bloqueo fijos son frágiles',
            '   ante el crecimiento del grafo.** El rate limiting',
            '   funcionaba; los 5 POSTs juntos tardaban más que los',
            '   2s del bloqueo.',
            '',
            '**Pendientes que abre este cierre.**',
            '',
            '- **Verificación final del plugin:** correr la suite',
            '  completa (65 pruebas) para confirmar que la migración',
            '  no rompió nada.',
            '- **Llamar a `reiniciar_seleccion_completa` desde el',
            '  login de terminal.** Caso real: usuario cierra el',
            '  navegador a mitad de venta y la venta_actual queda',
            '  colgada. Hoy el endpoint existe pero solo lo llaman',
            '  las pruebas.',
            '- **Auditar estados colgados:** reservas, DJ a medias,',
            '  edición de viaje con paradas.',
            '- **Auditar estructuras nuevas** con la regla de §5.9',
            '  del prompt de seguridad.',
            '- **Adaptar los 4 módulos menores** (Liquidacion,',
            '  Rendicion, Empresa, Vehiculo) al contexto del',
            '  solicitante.',
            '- **B3.3c** (los 7 helpers que iteran sobre todos los',
            '  dueños).',
            '',
            '**Próxima tanda sugerida:** verificación final del',
            'plugin, después elegir el siguiente frente de la lista.',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
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