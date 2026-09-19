<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores + Pasajes.
 *
 * v1.5piloto.56 (doc): actualizar la documentación viva de nodos.
 * - Bump de versión a 1.5piloto.56.
 * - Observaciones del Nodo Usuario: agregar `cancelaciones` a la lista.
 * - Nodo Rendición: reescribir la nota de desactualización para reflejar
 *   la estructura nueva (contenedor con hijos).
 * - Nodo Asiento: corregir `reservado_por` (es nodo con dato string, no
 *   enlace al nodo usuario).
 *
 * Uso:
 *   php aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [
    // ============================================================
    // 1. Bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: bump @version a 1.5piloto.56',
        'buscar' => [
            ' * @version   1.5piloto.55',
            ' */',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.56',
            ' */',
        ],
    ],

    // ============================================================
    // 2. Bump de la estructura
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: bump de la version de la estructura',
        'buscar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.55)',
        ],
        'reemplazar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.56)',
        ],
    ],

    // ============================================================
    // 3. Observaciones del Nodo Usuario: agregar cancelaciones
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: agregar cancelaciones a las observaciones',
        'buscar' => [
            ' * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones` y `liquidaciones` solo existen en nodos de nivel `dueno`.',
        ],
        'reemplazar' => [
            ' * - Los enlaces `terminales`, `empresas`, `viajes`, `pasajeros`, `ventas`, `rendiciones`, `liquidaciones` y `cancelaciones` solo existen en nodos de nivel `dueno`.',
        ],
    ],

    // ============================================================
    // 4. Nota (desactualización) del Nodo Rendición
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: reescribir la nota de desactualización',
        'buscar' => [
            ' * **Nota (desactualización):** Si se cancela una venta que tiene',
            ' * cupones ya rendidos, la rendición no se modifica en sus totales',
            ' * (es inmutable), pero se marca con un enlace `desactualizada` que',
            ' * contiene el motivo (ej. `"Se canceló la venta venta_123 el',
            ' * 17/05/2026 15:30"`). Si se acumulan varias cancelaciones, los',
            ' * motivos se concatenan con `"; "`. La pestaña Rendiciones muestra',
            ' * un badge de aviso cuando esto ocurre.',
        ],
        'reemplazar' => [
            ' * **Nota (desactualización):** Si se cancela una venta que tiene',
            ' * cupones ya rendidos, la rendición no se modifica en sus totales',
            ' * (es inmutable), pero se le agrega un hijo nuevo al contenedor',
            ' * `desactualizada` por cada cancelación. Cada hijo (Nodo Ajuste)',
            ' * guarda los datos de la cancelación: la venta, el motivo, la',
            ' * terminal, los montos que aporta cada cuenta (terminal vs dueño)',
            ' * y, cuando el dueño lo marca, la fecha en que lo aceptó.',
            ' * La pestaña Rendiciones muestra un badge de aviso mientras haya',
            ' * ajustes pendientes de aceptar.',
        ],
    ],

    // ============================================================
    // 5. Nodo Asiento: corregir reservado_por
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: corregir reservado_por del Nodo Asiento',
        'buscar' => [
            ' *   | `reservado_por`    | Enlace directo al **nodo usuario** del dueño que reservó el asiento para el equipo. Solo existe si `estado` es `"reservado"`. |',
        ],
        'reemplazar' => [
            ' *   | `reservado_por`    | Nodo con dato string: nombre de usuario del dueño que reservó el asiento para el equipo. Solo existe si `estado` es `"reservado"`. |',
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

$creaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";