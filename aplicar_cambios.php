<?php
/**
 * Aplicador de cambios — v78s: aplicacion_POST.php al día.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'aplicacion_POST: bump @version a 1.5piloto.78s',
    'buscar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78m',
        ' */',
    ],
    'reemplazar' => [
        ' * @package   Iteradores',
        ' * @since     1.5piloto.1',
        ' * @version   1.5piloto.78s',
        ' */',
    ],
],

[
    'tipo' => 'reemplazar',
    'archivo' => 'aplicacion_POST.php',
    'descripcion' => 'aplicacion_POST: agregar detalle de carga parcial y bug persistente',
    'buscar' => [
        ' * **Cambios de la v78f/v78m:**',
        ' * - `us_Administrador → usuarios`: enlace creado en v78f para que el',
        ' *   admin cargue todos los usuarios desde su propio contexto.',
        ' * - `dueno → soporte → publico`: el enlace `soporte` del dueño apunta',
        ' *   al `publico` del soporte (no al nodo real), para evitar fuga cruzada.',
        ' * - Carga parcial: index.php decide entre `cargar`/`cargar_parcial`',
        ' *   según los contextos devueltos por `_contextos_para_operacion`',
        ' *   (Enrutador). `guardar_ambos` detecta grafo parcial y llama a',
        ' *   `guardar_parcial` con los contextos cargados.',
        ' * - Persistencia en SQL64 (bitmask de contextos, 3 tablas nuevas:',
        ' *   nodo_contexto, adyacente_contexto, contexto).',
    ],
    'reemplazar' => [
        ' * **Cambios de la v78f/v78m:**',
        ' * - `us_Administrador → usuarios`: enlace creado en v78f para que el',
        ' *   admin cargue todos los usuarios desde su propio contexto.',
        ' * - `dueno → soporte → publico`: el enlace `soporte` del dueño apunta',
        ' *   al `publico` del soporte (no al nodo real), para evitar fuga cruzada.',
        ' * - Carga parcial: index.php decide entre `cargar`/`cargar_parcial`',
        ' *   según los contextos devueltos por `_contextos_para_operacion`',
        ' *   (Enrutador). `guardar_ambos` detecta grafo parcial y llama a',
        ' *   `guardar_parcial` con los contextos cargados.',
        ' * - Persistencia en SQL64 (bitmask de contextos, 3 tablas nuevas:',
        ' *   nodo_contexto, adyacente_contexto, contexto).',
        ' * ',
        ' * **Flujo de carga parcial (v78g-v78s):**',
        ' * ',
        ' * 1. `index.php` lee `$_POST[\'accion\']` y `$_POST[\'nombre_solicitante\']`,',
        ' *    y llama a `_contextos_para_operacion($accion, $sol, $_GET)`.',
        ' * 2. La función devuelve:',
        ' *    - `null` → carga total (`Controlador::cargar`).',
        ' *    - `[\'us_<sol>\']` → carga parcial (`Controlador::cargar_parcial`).',
        ' * 3. Excepciones que cargan total: acciones de gestión de usuarios',
        ' *    (`administrador/agregar_usuario`, `dueno/agregar_terminal`,',
        ' *    etc.) y los informes imprimibles que usan helpers de B3.3c',
        ' *    (`informe_cancelacion`, `informe_liquidacion`,',
        ' *    `informe_rendicion`).',
        ' * 4. `guardar_ambos` consulta `Controlador::contextos_cargados()`:',
        ' *    si está vacío, `guardar`; si no, `guardar_parcial`.',
        ' * ',
        ' * **BUG PERSISTENTE (no resuelto al cierre de v78s):**',
        ' * ',
        ' * Después de que un terminal hace una operación de guardado',
        ' * (por ejemplo, la prueba "autocompletado" del plugin), el viaje',
        ' * viejo del dueño desaparece de su lista. El terminal lo sigue',
        ' * viendo (navega por su compartido). Los fixes v78r (regla de',
        ' * borrado de aristas con doble extremo) y v78s (preservar bits',
        ' * de máscara al guardar parcial) fueron intentos que NO',
        ' * alcanzaron. El bug completo, el diagnóstico y el próximo paso',
        ' * de diagnóstico (instrumentar `_guardar_con_filtro` con logs)',
        ' * están en `prompts/plan_actual.md` §1.5.',
        ' * ',
        ' * **Reparación topológica (v78n, v78r):** `?reparar_topologia=1`',
        ' * repara enlaces faltantes en el grafo (compartidos, viajes,',
        ' * empresas, pasajeros). Idempotente. Solo agrega enlaces, nunca',
        ' * borra. Se usa para recuperar el estado después de bugs de',
        ' * guardado parcial.',
    ],
],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($cambios as $cambio) {
    $ruta_abs = $raiz_proyecto . '/' . $cambio['archivo'];
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: {$cambio['archivo']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $buscar_str = implode("\n", $cambio['buscar']);
    $reemplazar_str = implode("\n", $cambio['reemplazar']);
    $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
    if ($ocurrencias === 0) {
        $bloques_fallidos[] = "{$cambio['archivo']}: bloque no encontrado - {$cambio['descripcion']}";
        continue;
    }
    if ($ocurrencias > 1) {
        $bloques_fallidos[] = "{$cambio['archivo']}: bloque ambiguo - {$cambio['descripcion']}";
        continue;
    }
    $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
    $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    $bloques_ok++;
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "\nListo.\n";