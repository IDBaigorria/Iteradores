<?php
/**
 * Prueba del depósito de IDs del framework Iteradores (PHP).
 *
 * Verifica que al vaciar la superestructura el depósito de IDs
 * especiales se limpia correctamente, permitiendo volver a crear
 * nodos con los mismos IDs especiales.
 *
 * Este test NO usa Controlador::cargar porque cargar reinserta los
 * IDs especiales en el depósito (los recrea). Un test que intente
 * crear el mismo ID después de cargar da un falso positivo: el
 * error "Ya existe ese id" es el comportamiento correcto.
 *
 * En cambio, se verifica que:
 *   1. Crear un ID especial funciona.
 *   2. Existe el nodo correspondiente.
 *   3. Al vaciar la superestructura, el nodo desaparece.
 *   4. Se puede volver a crear el mismo ID especial.
 *
 * Se ejecuta como bloque temporal desde index.php:
 *   http://localhost/.../index.php?probar_deposito=1
 *
 * @package   Iteradores
 * @since     1.5i.7d
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Nucleo/Objeto.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Nucleo\Objeto;

header('Content-Type: text/plain; charset=utf-8');

echo "=== PRUEBA DEL DEPOSITO DE IDS (PHP) ===\n\n";

$id_prueba = 'test_especial_deposito';

Controlador::ejecutar_prueba(function ($token) use ($id_prueba) {

    // 1. Crear un nodo especial.
    $n1 = Nodo::crear_con_id($id_prueba);
    echo "1. Crear '{$id_prueba}' (1ra vez): " . ($n1 ? 'OK' : 'FALLO') . "\n";

    // 2. Verificar que existe.
    $existe1 = Nodo::nodo_por_id($id_prueba);
    echo "2. El nodo existe: " . ($existe1 ? 'OK' : 'FALLO') . "\n";

    // 3. Vaciar la superestructura.
    $vaciado = Nodo::vaciar_superestructura($token);
    echo "3. Vaciar superestructura: " . ($vaciado ? 'OK' : 'FALLO') . "\n";

    // 4. Verificar que el nodo ya no existe.
    $existe2 = Nodo::nodo_por_id($id_prueba);
    echo "4. El nodo ya no existe: " . (!$existe2 ? 'OK' : 'FALLO') . "\n";

    // 5. Crear el mismo id especial de nuevo.
    $n2 = Nodo::crear_con_id($id_prueba);
    echo "5. Crear '{$id_prueba}' (2da vez tras vaciar): " . ($n2 ? 'OK' : 'FALLO') . "\n";

    echo "\n=== RESULTADO ===\n";
    if ($n2) {
        echo "SIN BUG: vaciar_superestructura limpia el deposito de IDs.\n";
    } else {
        echo "BUG PRESENTE: el deposito NO se limpio al vaciar.\n";
        echo "El id '{$id_prueba}' sigue en Objeto::\$deposito_de_ids.\n";
        echo "\nErrores:\n";
        echo Objeto::json_errores() . "\n";
    }
    echo "\n=== FIN DE LA PRUEBA ===\n";
});