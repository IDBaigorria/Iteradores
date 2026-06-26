<?php
/**
 * Pruebas exhaustivas de la versión 1.4.1 – NodoElectrico con dato multifase.
 *
 * @since 1.4.1
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/NodoElectrico.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\NodoElectrico;

// ─── Helpers ──────────────────────────────────────────
function assert_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
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

function assert_no_nulo($v, string $mensaje): bool {
    $ok = $v !== null;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

function assert_verdadero($v, string $mensaje): bool {
    $ok = (bool)$v;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS 1.4.1 – NodoElectrico (PHP)<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ─── 1. dato() sin dimensión ───
    echo "--- 1. dato() sin dimensión ---<br>";
    NodoElectrico::_fase($token, 'prueba1');
    $nodo = NodoElectrico::crear_con_dato("Hola");
    assert_iguales($nodo->dato(), "Hola", "dato() devuelve lo asignado en la fase actual");

    $nodo->_dato("Mundo");
    assert_iguales($nodo->dato(), "Mundo", "_dato(valor) sobrescribe el dato por defecto");

    // ─── 2. Cambio de fase ───
    echo "<br>--- 2. Cambio de fase ---<br>";
    NodoElectrico::_fase($token, 'fase_a');
    $nodoA = NodoElectrico::crear_con_dato("Dato en fase A");
    assert_iguales($nodoA->dato(), "Dato en fase A", "Dato en fase A");

    NodoElectrico::_fase($token, 'fase_b');
    assert_iguales($nodoA->dato(), null, "En fase B, el nodo no tiene dato aún");
    $nodoA->_dato("Dato en fase B");
    assert_iguales($nodoA->dato(), "Dato en fase B", "Ahora tiene dato en fase B");

    NodoElectrico::_fase($token, 'fase_a');
    assert_iguales($nodoA->dato(), "Dato en fase A", "En fase A, el dato original sigue intacto");

    // ─── 3. dato con dimensión explícita ───
    echo "<br>--- 3. dato con dimensión ---<br>";
    NodoElectrico::_fase($token, 'fase_dim');
    $nodoB = NodoElectrico::crear();
    $nodoB->_dato("abajo_val", 'abajo');
    $nodoB->_dato("arriba_val", 'arriba');
    $nodoB->_dato("default_val");

    assert_iguales($nodoB->dato('abajo'), "abajo_val", "dato('abajo') correcto");
    assert_iguales($nodoB->dato('arriba'), "arriba_val", "dato('arriba') correcto");
    assert_iguales($nodoB->dato(), "default_val", "dato() sin dimensión correcto");
    assert_iguales($nodoB->dato('inexistente'), null, "dato('inexistente') es null");

    // ─── 4. Múltiples dimensiones ───
    echo "<br>--- 4. Múltiples dimensiones ---<br>";
    $nodoC = NodoElectrico::crear();
    $nodoC->_dato(10, 'peso');
    $nodoC->_dato(20, 'altura');
    $nodoC->_dato(30);
    assert_iguales($nodoC->dato('peso'), 10, "dimensión 'peso'");
    assert_iguales($nodoC->dato('altura'), 20, "dimensión 'altura'");
    assert_iguales($nodoC->dato(), 30, "dimensión por defecto");

    // ─── 5. Factories ───
    echo "<br>--- 5. Factories con dato multifase ---<br>";
    NodoElectrico::_fase($token, 'fase_fact');
    $nodoD = NodoElectrico::crear_con_dato_e_id("dato especial", "id_especial");
    assert_iguales($nodoD->dato(), "dato especial", "crear_con_dato_e_id asigna el dato en fase actual");
    assert_iguales($nodoD->id(), "id_especial", "id correcto");

    $nodoE = NodoElectrico::crear();
    assert_iguales($nodoE->dato(), null, "crear() sin dato no asigna nada en la fase actual");

    $nodoF = NodoElectrico::nodo("nuevo dato");
    assert_iguales($nodoF->dato(), "nuevo dato", "nodo() con dato nuevo asigna en fase actual");

    $nodoG = NodoElectrico::crear_con_dato("original");
    $esNodo = false;
    $nodoH = NodoElectrico::nodo($nodoG, $esNodo);
    assert_verdadero($esNodo, "nodo() reconoce que el elemento ya era nodo");
    assert_iguales($nodoH->dato(), "original", "nodo() no modifica el dato de un nodo existente");

    // ─── 6. Independencia entre fases ───
    echo "<br>--- 6. Independencia entre fases ---<br>";
    NodoElectrico::_fase($token, 'indep_a');
    $nodoI = NodoElectrico::crear();
    $nodoI->_dato("A");
    $nodoI->_dato("extra_a", 'extra');

    NodoElectrico::_fase($token, 'indep_b');
    $nodoI->_dato("B");
    $nodoI->_dato("extra_b", 'extra');

    NodoElectrico::_fase($token, 'indep_a');
    assert_iguales($nodoI->dato(), "A", "Fase indep_a mantiene 'A'");
    assert_iguales($nodoI->dato('extra'), "extra_a", "Fase indep_a mantiene 'extra_a'");

    NodoElectrico::_fase($token, 'indep_b');
    assert_iguales($nodoI->dato(), "B", "Fase indep_b mantiene 'B'");
    assert_iguales($nodoI->dato('extra'), "extra_b", "Fase indep_b mantiene 'extra_b'");

    // ─── 7. Impresión global con Controlador::imprimir_superestructura() ───
    echo "<br>--- 7. Impresión global con Controlador::imprimir_superestructura() ---<br>";
    NodoElectrico::_fase($token, 'impresion');

    // Creamos varios nodos para que la superestructura tenga contenido
    $nodoJ = NodoElectrico::crear();
    $nodoJ->_dato("principal");
    $nodoJ->_dato("subordinado", 'abajo');
    $nodoJ->_adyacente(NodoElectrico::crear_con_dato("vecino"));

    // Capturamos la salida
    ob_start();
    Controlador::imprimir_superestructura();
    $salida = ob_get_clean();

    // Verificaciones
    assert_verdadero(strpos($salida, 'NODOELECTRICO') !== false, "imprimir_superestructura() genera salida con nodos eléctricos");
    // En las verificaciones de la prueba 7
    assert_verdadero(strpos($salida, '[(defecto)] => principal') !== false, "Aparece la dimensión por defecto");
    assert_verdadero(strpos($salida, '[abajo] => subordinado') !== false, "Aparece la dimensión 'abajo'");

    // Mostramos la salida capturada para inspección visual
    echo '<hr><strong>Salida de la superestructura:</strong><br>';
    if (php_sapi_name() === 'cli') {
        echo $salida;
    } else {
        // En entorno web, mostramos el HTML generado directamente (es seguro porque es nuestra propia salida de depuración)
        echo $salida;
    }
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.1 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";