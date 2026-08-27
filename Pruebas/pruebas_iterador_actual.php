<?php
/**
 * Pruebas exhaustivas v1.5i.2 – Interfaz Actual del Iterador
 *
 * Cubre: actual() y _actual() en condiciones normales y de error.
 *
 * @since 1.0
 * @version 1.5i.2
 * @author Ignacio David Baigorria
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Iteradores/Iterador.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;

// Helpers (reutilizables)
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
echo " PRUEBAS v1.5i.2 – INTERFAZ ACTUAL<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // 1. actual() sin nodo actual
    echo "<h3>1. actual() sin nodo actual</h3>";
    $iter = Iterador::crear("iter_actual");
    verificar_no_nulo($iter, "crear() para pruebas");
    verificar_iguales($iter->actual(), null, "actual() devuelve null si no hay nodo actual");

    // 2. _actual() con elemento no nodo
    echo "<h3>2. _actual() con elemento no nodo</h3>";
    $es_nodo = null;
    $resultado = $iter->_actual("valor_inicial", $es_nodo);
    verificar_verdadero($resultado, "_actual() devuelve true al asignar");
    verificar_falso($es_nodo, "es_nodo es false para elemento no nodo");
    $actual = $iter->actual();
    verificar_no_nulo($actual, "actual() devuelve un nodo");
    verificar_iguales($actual->dato(), "valor_inicial", "dato del nodo actual es correcto");

    // 3. _actual() con elemento que ya es Nodo
    echo "<h3>3. _actual() con elemento nodo</h3>";
    $nodo_pre = Nodo::crear_con_dato("soy_nodo");
    $es_nodo2 = null;
    $resultado2 = $iter->_actual($nodo_pre, $es_nodo2);
    verificar_verdadero($resultado2, "_actual() con nodo devuelve true");
    verificar_verdadero($es_nodo2, "es_nodo es true para elemento nodo");
    $actual2 = $iter->actual();
    verificar_verdadero($actual2 === $nodo_pre, "actual() devuelve exactamente el nodo pasado");

    // 4. _actual() sin elemento (crea nodo vacío)
    echo "<h3>4. _actual() sin elemento</h3>";
    $es_nodo3 = null;
    $resultado3 = $iter->_actual(null, $es_nodo3);
    verificar_verdadero($resultado3, "_actual() sin elemento devuelve true");
    verificar_falso($es_nodo3, "es_nodo es false para null");
    $actual3 = $iter->actual();
    verificar_no_nulo($actual3, "actual() devuelve nodo vacío");
    verificar_iguales($actual3->dato(), null, "dato del nodo vacío es null");

    // 5. Métodos con iterador no ocupado
    echo "<h3>5. Métodos sin ocupación</h3>";
    $iter->desocupar();
    verificar_falso($iter->actual(), "actual() sin ocupar devuelve false");
    verificar_falso($iter->_actual("algo"), "_actual() sin ocupar devuelve false");

    // 6. Limpieza final
    echo "<h3>6. Limpieza final</h3>";
    $iter2 = Iterador::cargar("iter_actual");
    verificar_verdadero($iter2->destruir(), "destruir() del iterador recargado funciona");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.2 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();