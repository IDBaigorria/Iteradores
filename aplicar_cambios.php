<?php
/**
 * Aplicador — plan_actual.md: §1.12 (botón del plugin + prefijos).
 *
 * Solo documentacion. No bumpea @version.
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
        'descripcion' => 'plan_actual: agregar §1.12',
        'buscar' => [
            '**Próxima tanda sugerida:** verificación final del',
            'plugin, después elegir el siguiente frente de la lista.',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
        'reemplazar' => [
            '**Próxima tanda sugerida:** verificación final del',
            'plugin, después elegir el siguiente frente de la lista.',
            '',
            '### 1.12 Botón "Limpiar datos de prueba" en el plugin',
            '',
            '**Estado:** cerrada entre v1.5piloto.80f (piloto) y',
            'v1.5plugin.7m-7n (plugin).',
            '',
            '**Motivación.** Las pruebas del plugin acumulan datos de',
            'prueba que quedan en el grafo y frenan las pruebas',
            'siguientes (empresas sin vehículo configurado, viajes',
            'duplicados, pasajeros con `@test.local`, etc.). El',
            'usuario tenía que correr un snippet de consola',
            'manualmente para limpiarlos. Ahora hay un botón.',
            '',
            '**Qué se hizo.**',
            '',
            '- **v1.5piloto.80f:** ampliar los prefijos que',
            '  reconocen los limpiadores:',
            '  - `Viaje.php` → `limpiar_viajes_de_prueba` reconoce',
            '    `viajelimpia`, `viajemicrograf`, `viajetermgraf`,',
            '    `viajeparadgraf`, `viajeparahora`, `viajeborrar`,',
            '    `viajedesaut`, `viajemicrocomp`.',
            '  - `Empresa.php` → `limpiar_empresas_vehiculos_de_prueba`',
            '    reconoce `emprevgraf` y `vehgraf`.',
            '  Sin esto, las empresas de las pruebas 37/38 quedaban',
            '  colgadas y bloqueaban pruebas posteriores.',
            '',
            '- **v1.5plugin.7m:** botón "Limpiar datos de prueba" en',
            '  el popup. Arquitectura:',
            '  - Nuevo módulo `Aplicacion/LimpiezaPrueba.js`: itera',
            '    dueños, llama los 6 endpoints por dueño + el global',
            '    de usuarios. Chequea modo pruebas + nivel admin',
            '    antes de empezar. Reporta progreso por callback.',
            '  - `servicio.js`: import + `_ejecutar_limpieza` +',
            '    caso `limpiar_datos_prueba` en el switch +',
            '    `globalThis.limpiar_prueba` para la consola del SW.',
            '  - `ventana.html`: botón + `<pre>` para el log.',
            '  - `ventana.js`: confirm previo + progreso incremental',
            '    + resumen final.',
            '',
            '- **v1.5plugin.7n:** fix del listener del botón. El',
            '  bloque `DOMContentLoaded` anidado nunca disparaba',
            '  (el DOM ya estaba cargado dentro del handler padre).',
            '  Fix: conexión directa del botón.',
            '',
            '**Orden de la limpieza (importa).**',
            '',
            '1. Ventas (crea cancelaciones de auditoría).',
            '2. Cancelaciones (barre las recién creadas).',
            '3. Viajes (destruye micros, que referencian empresas).',
            '4. Pasajeros (`@test.local` sin referencias).',
            '5. Empresas/vehículos (los que ya no tienen micros).',
            '6. Terminales (`termprueba`).',
            '7. Usuarios (global, al final).',
            '',
            '**Resultado en una corrida real:**',
            '',
            '- Antes: 5480 nodos, 0 huérfanos.',
            '- Después: 2057 nodos, 0 huérfanos.',
            '- Borrados: 8 ventas, 65 cancelaciones, 2+4 viajes, 206',
            '  pasajeros, 2 empresas.',
            '',
            '**Lecciones aprendidas.**',
            '',
            '4. **Un `addEventListener("DOMContentLoaded")` anidado',
            '   nunca dispara.** El evento ya ocurrió cuando el',
            '   bloque interno se registra. Bug v7n: el listener',
            '   del botón quedó dentro del handler padre. Fix:',
            '   conectar directo (el DOM ya está listo).',
            '',
            '5. **Los prefijos de prueba deben estar centralizados.**',
            '   Cuando una prueba nueva usa un prefijo distinto, el',
            '   limpiador no lo reconoce y los datos se acumulan.',
            '   Anotado para una tanda futura: una constante',
            '   compartida con todos los prefijos, o que las',
            '   pruebas registren su prefijo en algún lado.',
            '',
            '**Pendientes que abre este cierre.**',
            '',
            '- **Pruebas 37 y 38 sin `try/finally`:** si el flujo',
            '  falla a mitad, la empresa/vehículo de setup queda',
            '  colgada. Los limpiadores ahora la agarran, pero la',
            '  prueba debería limpiar por su cuenta.',
            '- **Reorganización de la ventana en pestañas por rol**',
            '  (plan del prompt del plugin §10).',
            '- El resto de los pendientes de §1.11 sigue vigente.',
            '',
            '**Próxima tanda sugerida:** depende del paréntesis',
            'urgente del usuario. Al volver, elegir entre la',
            'reorganización del plugin o el siguiente punto de',
            '§1.11.',
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