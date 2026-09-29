<?php
/**
 * Diagnóstico de la persistencia JSON.
 *
 * Uso:
 *   php diagnostico_json.php
 */

echo "=== Diagnóstico de persistencia JSON ===\n\n";

echo "Directorio actual (cwd):\n";
echo "  " . getcwd() . "\n\n";

echo "Directorio del script:\n";
echo "  " . __DIR__ . "\n\n";

$carpeta = 'JSON';
$ruta_carpeta_abs = __DIR__ . DIRECTORY_SEPARATOR . $carpeta;

echo "Carpeta JSON según Conf (relativa):\n";
echo "  $carpeta\n\n";

echo "Carpeta JSON (resuelta desde el script):\n";
echo "  $ruta_carpeta_abs\n\n";

echo "¿Existe la carpeta (relativa al cwd)?\n";
echo "  " . (is_dir($carpeta) ? "SÍ" : "NO") . "\n\n";

echo "¿Existe la carpeta (junto al script)?\n";
echo "  " . (is_dir($ruta_carpeta_abs) ? "SÍ" : "NO") . "\n\n";

// Listar archivos dentro de la carpeta si existe
foreach ([$carpeta, $ruta_carpeta_abs] as $indice => $dir) {
    $etiqueta = $indice === 0 ? "relativa" : "junto al script";
    echo "Contenido de la carpeta ($etiqueta):\n";
    if (!is_dir($dir)) {
        echo "  (no existe)\n";
        continue;
    }
    $archivos = glob($dir . DIRECTORY_SEPARATOR . '*.json');
    if (empty($archivos)) {
        echo "  (no hay archivos .json)\n";
    } else {
        foreach ($archivos as $archivo) {
            $tamano = filesize($archivo);
            $fecha = date('Y-m-d H:i:s', filemtime($archivo));
            echo "  - " . basename($archivo) . " ($tamano bytes, modificado $fecha)\n";
        }
    }
    echo "\n";
}

// Prueba de escritura
$archivo_prueba = $ruta_carpeta_abs . DIRECTORY_SEPARATOR . '_prueba_escritura.json';
if (!is_dir($ruta_carpeta_abs)) {
    echo "Intentando crear la carpeta junto al script...\n";
    if (@mkdir($ruta_carpeta_abs, 0755, true)) {
        echo "  OK\n\n";
    } else {
        echo "  FALLO: " . (error_get_last()['message'] ?? 'sin mensaje') . "\n\n";
    }
}

if (is_dir($ruta_carpeta_abs)) {
    echo "Intentando escribir archivo de prueba...\n";
    $res = @file_put_contents($archivo_prueba, '{"test": true}');
    if ($res === false) {
        echo "  FALLO: " . (error_get_last()['message'] ?? 'sin mensaje') . "\n";
    } else {
        echo "  OK ($res bytes)\n";
        @unlink($archivo_prueba);
        echo "  Archivo de prueba eliminado.\n";
    }
    echo "\n";
}

echo "=== Fin del diagnóstico ===\n";