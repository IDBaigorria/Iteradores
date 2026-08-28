<?php
/**
 * Pruebas exhaustivas v1.5i.4 – Persistencia del Iterador
 *
 * Comprueba que guardar y cargar la superestructura conserva
 * el cuerpo del iterador y su posición actual.
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
echo " PRUEBAS v1.5i.4 – PERSISTENCIA DEL ITERADOR<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    Controlador::establecer_metodo("SQL");

    // 1. Crear lista enlazada 0→1→2→3→4
    $nodos = [];
    for ($i = 0; $i < 5; $i++) {
        $nodos[] = Nodo::crear_con_dato($i);
    }
    for ($i = 0; $i < 4; $i++) {
        $nodos[$i]->_adyacente_en($nodos[$i + 1], "siguiente");
    }

    // 2. Crear iterador apuntando al nodo 0
    $nombre_iter = "iter_persistencia";
    $iter = Iterador::crear($nombre_iter, $nodos[0]);
    verificar_no_nulo($iter, "Iterador creado");
    verificar_iguales($iter->dato(), 0, "dato inicial es 0");

    // 3. Avanzar dos posiciones por "siguiente"
    $actual = $iter->avanzar("siguiente;siguiente");
    verificar_no_nulo($actual, "avanzar devuelve nodo");
    verificar_iguales($actual->dato(), 2, "tras avanzar 2, dato actual es 2");

    // 3.1 Desocupar el iterador antes de guardar (conserva posición actual)
    verificar_verdadero($iter->desocupar(), "desocupar iterador antes de guardar");

    // 4. Guardar superestructura
    $guardado = Controlador::guardar("persistencia_iterador_test");
    verificar_verdadero($guardado, "guardar superestructura devuelve true");

    // 5. Cargar superestructura guardada
    $cargado = Controlador::cargar("persistencia_iterador_test");
    verificar_verdadero($cargado, "cargar superestructura devuelve true");

    // 6. Recuperar iterador cargado
    $iter_cargado = Iterador::cargar($nombre_iter);
    verificar_no_nulo($iter_cargado, "iterador cargado correctamente");

    // 7. Verificar posición actual
    $actual_cargado = $iter_cargado->actual();
    verificar_no_nulo($actual_cargado, "nodo actual no nulo");
    verificar_iguales($actual_cargado->dato(), 2, "dato del nodo actual es 2");
    verificar_iguales($iter_cargado->dato(), 2, "método dato() devuelve 2");

    // 8. Verificar enlace siguiente desde actual
    $siguiente = $actual_cargado->adyacente("siguiente");
    verificar_no_nulo($siguiente, "existe enlace 'siguiente' desde actual");
    verificar_iguales($siguiente->dato(), 3, "el siguiente nodo tiene dato 3");

    // 9. Limpiar
    $iter_cargado->destruir();
    Controlador::eliminar("persistencia_iterador_test");
    verificar_verdadero(true, "Limpieza final completada");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.4 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();