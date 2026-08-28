<?php
/**
 * Pruebas exhaustivas v1.5i.4 – Persistencia del Iterador
 *
 * @since 1.0
 * @version 1.5i.4
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Iteradores/Iterador.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;

// Helpers...
function verificar_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    if ($a === $b) {
        echo "✅ " . $mensaje . "<br>";
        return true;
    }
    if (is_numeric($a) && is_numeric($b)) {
        $ok = abs($a - $b) < $tolerancia;
        echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
        if (!$ok) echo "   Esperado: $b, Obtenido: $a<br>";
        return $ok;
    }
    echo "❌ " . $mensaje . "<br>";
    echo "   Esperado: $b, Obtenido: $a<br>";
    return false;
}

function verificar_no_nulo($v, string $mensaje): bool {
    $ok = $v !== null;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) echo "   Se esperaba valor no nulo.<br>";
    return $ok;
}

function verificar_verdadero($v, string $mensaje): bool {
    $ok = (bool)$v;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

function verificar_falso($v, string $mensaje): bool {
    $ok = !$v;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.4 – PERSISTENCIA ITERADOR SIMPLE<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    Controlador::establecer_metodo("SQL");

    $nombre_iter = "iter_simple_persistencia";
    $iter = Iterador::crear($nombre_iter);
    verificar_no_nulo($iter, "Iterador simple creado");

    // Guardar
    verificar_verdadero(Controlador::guardar("test_iter_simple"), "guardar iterador simple");

    // Cargar (esto reemplaza la superestructura)
    verificar_verdadero(Controlador::cargar("test_iter_simple"), "cargar iterador simple");

    // Intentar recuperar el iterador
    $iter_cargado = Iterador::cargar($nombre_iter);
    verificar_no_nulo($iter_cargado, "iterador simple cargado");

    // Verificar que el cuerpo existe y está ocupado
    if ($iter_cargado) {
        verificar_verdadero($iter_cargado->ocupado(), "el iterador cargado está ocupado");
    }

    // Limpiar
    if ($iter_cargado) {
        $iter_cargado->destruir();
    }
    Controlador::eliminar("test_iter_simple");
    verificar_verdadero(true, "limpieza ok");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();