<?php
/**
 * Prueba del depósito de IDs del framework Iteradores (PHP).
 *
 * Verifica que al cargar una superestructura el depósito de IDs
 * especiales se limpia correctamente, permitiendo recrear nodos con
 * los mismos IDs especiales. Si no se limpiara, aparecería un error
 * "Ya existe ese id" (bug que sí existió en el espejo JS hasta
 * 1.5i.6).
 *
 * Se ejecuta como bloque temporal desde index.php:
 *   http://localhost/.../index.php?probar_deposito=1
 *
 * @package   Iteradores
 * @since     1.5i.7a
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PRUEBA DEL DEPOSITO DE IDS (PHP) ===\n\n";

$id_prueba = 'test_especial_deposito';
$nombre_prueba = 'prueba_deposito_php';

// Limpieza por si la prueba se corrió antes.
if (Controlador::existe($nombre_prueba)) {
    Controlador::eliminar($nombre_prueba);
}

// 1. Crear un nodo especial.
$n1 = Nodo::crear_con_id($id_prueba);
echo "1. Crear '{$id_prueba}' (1ra vez): " . ($n1 ? 'OK' : 'FALLO') . "\n";

// 2. Guardar la superestructura.
guardar_ambos($nombre_prueba);
echo "2. Guardar '{$nombre_prueba}': OK\n";

// 3. Cargar (esto debe vaciar y limpiar el depósito).
$cargado = Controlador::cargar($nombre_prueba);
echo "3. Cargar '{$nombre_prueba}': " . ($cargado ? 'OK' : 'FALLO') . "\n";

// 4. Intentar crear el mismo id especial otra vez.
$n2 = Nodo::crear_con_id($id_prueba);
echo "4. Crear '{$id_prueba}' (2da vez tras cargar): " . ($n2 ? 'OK' : 'FALLO') . "\n";

// 5. Limpieza.
Controlador::eliminar($nombre_prueba);
echo "5. Eliminar '{$nombre_prueba}': OK\n";

echo "\n=== RESULTADO ===\n";
if ($n2) {
    echo "SIN BUG: el depósito de IDs se limpió correctamente.\n";
} else {
    echo "BUG PRESENTE: el depósito NO se limpió.\n";
    echo "El id '{$id_prueba}' sigue registrado en Objeto::\$deposito_de_ids.\n";
    echo "\nErrores:\n";
    echo Objeto::json_errores() . "\n";
    echo "\nAlertas:\n";
    echo Objeto::json_alertas() . "\n";
}
echo "\n=== FIN DE LA PRUEBA ===\n";