<?php
/**
 * Aplicador de cambios — Prompt del framework.
 *
 * Tanda de documentación: 1.5i.7h (separación ConfiguracionApli)
 * y 1.5i.7i (comando grafo:eliminar_huerfanos + espejo JS del
 * módulo grafo). Además, idea del índice de contexto por
 * producto de primos para la sección 11.1.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ------------------------------------------------------------
    // Historial: agregar 1.5i.7h y 1.5i.7i después de 1.5i.7g
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Historial: agregar 1.5i.7h y 1.5i.7i',
        'buscar' => [
            '  operación; con 2.000 nodos, 15-18s.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
        'reemplazar' => [
            '  operación; con 2.000 nodos, 15-18s.',
            '- **1.5i.7h**: separación de la configuración del framework',
            '  de la del piloto. `Configuracion.php` (framework) se',
            '  queda solo con las constantes propias del framework;',
            '  las constantes del piloto (`NOMBRE_APP`,',
            '  `NOMBRE_APP_CREDENCIALES`, `VERSION_APP`, `AUTOR_APP`,',
            '  `PREFIJO_SESSION`, `INTENTOS_MAXIMOS_AUTENTICACION`,',
            '  `BLOQUEO_AUTENTICACION_SEGUNDOS`, `HASH_DUMMY_AUTENTICACION`,',
            '  `NOMBRE_ADMIN`) se mueven al nuevo',
            '  `Aplicacion/ConfiguracionApli.php` (extends `Conf`).',
            '  `Entorno.php` recibe `establecer_prefijo_sesion()` y',
            '  `prefijo_sesion()` para desacoplarse del `PREFIJO_SESSION`',
            '  del piloto. Refactor masivo en 12 archivos del piloto:',
            '  `Conf::X` → `ConfiguracionApli::X`. Espejado en JS:',
            '  `Aplicacion/ConfiguracionApli.js` reemplaza a',
            '  `ConfPlugin.js` y aplica los valores al `Conf` del',
            '  framework vía `configurar_conf(Conf)`.',
            '- **1.5i.7i**: comando `grafo:eliminar_huerfanos` en el',
            '  `Controlador`. Elimina todos los nodos no alcanzables',
            '  desde las raíces. Desenlaza las salientes entre',
            '  huérfanos antes de destruirlos. Sin chequeo de',
            '  `es_pruebas`: el enrutador del piloto decide cuándo',
            '  exponerlo (admin/soporte). Se espeja el módulo grafo',
            '  completo al `Controlador` JS (los comandos `grafo:*`',
            '  existían solo en PHP desde 1.5i.7g-pre; ahora los',
            '  cuatro + helpers privados están en ambos espejos).',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
    ],

    // ------------------------------------------------------------
    // §11.1: agregar idea del índice de contexto por primos
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => '§11.1: agregar idea del índice por primos',
        'buscar' => [
            '- Persistir por partes usando índices auxiliares.',
            '- Snapshot por rama con marca de "raíz".',
            '',
            'Requiere una sesión del framework, no del piloto.',
        ],
        'reemplazar' => [
            '- Persistir por partes usando índices auxiliares.',
            '- Snapshot por rama con marca de "raíz".',
            '- **Índice de contexto por producto de primos (idea',
            '  anotada, sin implementar).** Asignar a cada raíz',
            '  especial (nodo con ID no numérico) un número primo',
            '  distinto. Guardar en cada nodo un campo — columna en',
            '  la tabla `nodos` — con el producto de los primos de',
            '  todas las raíces desde las que el nodo es alcanzable.',
            '  Como la factorización en primos es única (teorema',
            '  fundamental de la aritmética), el producto identifica',
            '  exactamente el conjunto de contextos a los que',
            '  pertenece el nodo. Para cargar solo el contexto de una',
            '  raíz R, filtrar `WHERE contexto % primo_R = 0`. Un',
            '  nodo alcanzable desde varias raíces queda con el',
            '  producto de sus primos.',
            '  - **Ventaja:** un único índice numérico reemplaza a',
            '    una tabla de pertenencias. Filtrado con una sola',
            '    condición aritmética.',
            '  - **Límite:** el producto crece rápido. Con N raíces,',
            '    si un nodo es alcanzable desde muchas, el producto',
            '    puede overflow. En la práctica el piloto tiene 2',
            '    raíces (`usuarios`, `sesiones`); con 5-10 raíces el',
            '    producto de los primeros primos entra en un',
            '    `BIGINT` sin problema.',
            '  - **Mantenimiento:** cada vez que se agrega un enlace,',
            '    hay que propagar el producto por el grafo (un nodo',
            '    nuevo hereda el producto de su padre; si el nodo ya',
            '    existía y suma un contexto, se multiplica el primo).',
            '    Requiere diseñar la propagación incremental.',
            '  - **Interacción con el framework:** habría que',
            '    modificar `PerdurarSuperestructuraStringSQL` para',
            '    agregar la columna, calcular el producto al guardar',
            '    y usarlo al cargar con un filtro. Es un cambio de',
            '    la implementación de persistencia, no de la API.',
            '',
            'Requiere una sesión del framework, no del piloto.',
        ],
    ],

    // ------------------------------------------------------------
    // §12.5: agregar 1.5i.7i a la lista del espejo JS
    // ------------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => '§12.5: agregar 1.5i.7i al estado del espejo',
        'buscar' => [
            '- **1.5i.7f**: fix del bug latente `if (elemento)` en',
            '  `_crear_interno`, `_cargar_interno` y `_iterador_interno`',
            '  (dos veces). Espejo del fix PHP 1.5i.7f.',
            '',
            'Cualquier cambio al framework PHP que toque la API compartida debe',
        ],
        'reemplazar' => [
            '- **1.5i.7f**: fix del bug latente `if (elemento)` en',
            '  `_crear_interno`, `_cargar_interno` y `_iterador_interno`',
            '  (dos veces). Espejo del fix PHP 1.5i.7f.',
            '- **1.5i.7h**: separación `ConfiguracionApli` (ver',
            '  1.5i.7h del historial). `Configuracion.js` del',
            '  framework se queda solo con las constantes del',
            '  framework; `ConfiguracionApli.js` (antes',
            '  `ConfPlugin.js`) define las del plugin y aplica los',
            '  valores al `Conf` del framework vía',
            '  `configurar_conf(Conf)`. `Entorno.js` recibe',
            '  `_prefijo_sesion`, `establecer_prefijo_sesion()` y',
            '  `prefijo_sesion()`.',
            '- **1.5i.7i**: espejado del módulo grafo completo',
            '  (`grafo:resumen`, `grafo:listar`, `grafo:nodo`,',
            '  `grafo:eliminar_huerfanos` + helpers privados',
            '  `_grafo_cargar_estructura`, `_grafo_bfs_desde_raices`,',
            '  `_grafo_inferir_tipo`). En JS,',
            '  `Nodo.por_cada_nodo_ejecutar` devuelve un objeto',
            '  plano `{id: resultado}`, no un `Map`; los helpers',
            '  del Controlador lo normalizan a un objeto',
            '  `{id: {dato, ady}}` para el resto del módulo.',
            '',
            'Cualquier cambio al framework PHP que toque la API compartida debe',
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
echo "[INFO] $total_reemplazos reemplazo(s).\n\n";
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
        $es_todos = !empty($cambio['todos']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if (!$es_todos && $ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir.\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";