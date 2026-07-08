<?php
/**
 * Prueba exhaustiva v1.4.7 – Ciclo completo de comunicación
 *
 * Verifica:
 *   1. Precarga del Tálamo (256 patrones)
 *   2. Lectura de archivo → señal
 *   3. Procesamiento con dominio "texto" (aprendizaje trivial)
 *   4. Inversión de p‑gramas y emisión de señal invertida
 *   5. Escritura del resultado invertido en disco
 *   6. Verificación de que el contenido invertido es correcto
 *   7. Limpieza del archivo temporal
 *
 * Requiere que el archivo `hola.txt` exista en el mismo directorio.
 *
 * @since 1.4.7
 * @version 1.4.7
 */
require_once __DIR__ . '/../index.php'; // Inicializa Controlador y Tálamo

use Iteradores\Controlador\Controlador;
use Iteradores\Controlador\Talamo;

echo "=== Prueba exhaustiva v1.4.7 ===\n\n";

// ─── 1. Verificar precarga del Tálamo ─────────────────
$talamo = Talamo::obtener();
$patrones = $talamo->antena(0)->patrones();
echo "1. Patrones precargados en el Tálamo: " . count($patrones) . " (esperado 256)\n";

// ─── 2. Crear dominio "texto" (vacío, aprenderá solo) ──
echo "2. Creando dominio 'texto' (sin precarga, usará aprendizaje trivial)...\n";
$textoProc = Controlador::procesador('texto', 'entrada');
echo "   Patrones iniciales en dominio texto: " . count($textoProc->antena(0)->patrones()) . " (esperado 0)\n";

// ─── 3. Leer archivo de prueba ───────────────────────
$ruta = __DIR__ . '/hola.txt';
if (!file_exists($ruta)) {
    die("ERROR: El archivo '$ruta' no existe. Crea uno con algo de texto para continuar.\n");
}
echo "3. Leyendo archivo '$ruta'...\n";
$senal = Controlador::ejecutar_comando('comunicacion:leer', 'archivo', $ruta);
if (!$senal) {
    die("ERROR: No se pudo leer el archivo.\n");
}
echo "   Leído correctamente.\n";

// ─── 4. Procesar señal con el dominio texto (aprendizaje trivial) ─
echo "4. Procesando señal con dominio 'texto' (aprendiendo)...\n";
$textoProc->procesar($senal);
$pgramas = $textoProc->elementos_procesados();
$patrones_texto = $textoProc->antena(0)->patrones();
echo "   P‑gramas capturados: " . count($pgramas) . "\n";
echo "   Patrones ahora en dominio texto: " . count($patrones_texto) . " (deberían ser los bytes distintos del archivo)\n";

// ─── 5. Invertir los p‑gramas y emitir señal invertida ──
echo "5. Invirtiendo p‑gramas y emitiendo señal invertida...\n";
$pgramas_invertidos = array_reverse($pgramas);
$senal_invertida = $textoProc->antena(0)->emitir($pgramas_invertidos);
if (!$senal_invertida) {
    die("ERROR: La emisión invertida falló.\n");
}

// ─── 6. Convertir la señal invertida a bytes y guardarla ──
$bytes_invertidos = $talamo->traducir_salida($senal_invertida);
$ruta_invertida = __DIR__ . '/hola_invertido.txt';
file_put_contents($ruta_invertida, $bytes_invertidos);
echo "6. Señal invertida guardada en '$ruta_invertida'.\n";

// ─── 7. Leer el archivo invertido y verificar ───────
echo "7. Verificando archivo invertido...\n";
$senal_inv_leida = Controlador::ejecutar_comando('comunicacion:leer', 'archivo', $ruta_invertida);
$bytes_inv_leidos = $talamo->traducir_salida($senal_inv_leida);
$original_bytes = $talamo->traducir_salida($senal);
$original_invertido_esperado = strrev($original_bytes);
if ($bytes_inv_leidos === $original_invertido_esperado) {
    echo "   ¡Éxito! El contenido invertido coincide con el esperado.\n";
} else {
    echo "   ERROR: El contenido invertido no coincide.\n";
    echo "   Original         : " . substr($original_bytes, 0, 50) . "...\n";
    echo "   Invertido leído  : " . substr($bytes_inv_leidos, 0, 50) . "...\n";
    echo "   Invertido esperado: " . substr($original_invertido_esperado, 0, 50) . "...\n";
}

// ─── 8. Mostrar en consola ──────────────────────────
echo "8. Mostrando contenidos por consola:\n";
Controlador::ejecutar_comando('comunicacion:escribir', 'consola', $senal, '');
Controlador::ejecutar_comando('comunicacion:escribir', 'consola', $senal_invertida, '');

// ─── 9. Limpiar archivo invertido ───────────────────
Controlador::ejecutar_comando('comunicacion:eliminar', 'archivo', $ruta_invertida);
echo "9. Archivo invertido eliminado.\n";

echo "\n=== Prueba finalizada exitosamente ===\n";
Controlador::imprimir_alertas();
Controlador::imprimir_errores();