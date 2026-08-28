<?php
/**
 * Pruebas exhaustivas v1.5i.3 – Interfaz Adyacente del Iterador
 *
 * Cubre: _adyacente_en, _adyacente, _adyacentes, adyacentes, adyacente,
 *        eliminar_adyacente, eliminar_adyacentes, _como_adyacente_de_nodo_en_alias,
 *        _adyacente_inverso.
 *
 * @since 1.0
 * @version 1.5i.3
 * @author Ignacio David Baigorria
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Iteradores/Iterador.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;

// Helpers
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
echo " PRUEBAS v1.5i.3 – INTERFAZ ADYACENTE<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // Estructura base: raíz con nodo A1, A1 con A2, A1 también con B1
    $nodo_raiz = Nodo::crear_con_dato("raiz");
    $nodo_a1 = Nodo::crear_con_dato("A1");
    $nodo_a2 = Nodo::crear_con_dato("A2");
    $nodo_b1 = Nodo::crear_con_dato("B1");

    $nodo_raiz->_adyacente_en($nodo_a1, "a");
    $nodo_a1->_adyacente_en($nodo_a2, "a");
    $nodo_a1->_adyacente_en($nodo_b1, "b");

    // ═══════════════════════════════════
    // 1. _adyacente_en (elemento obligatorio, alias, camino opcional)
    // ═══════════════════════════════════
    echo "<h3>1. _adyacente_en</h3>";

    $iter = Iterador::crear("iter_adyacente", $nodo_raiz);
    verificar_no_nulo($iter, "crear iterador");

    $es_nodo = null;
    $nodo_nuevo = $iter->_adyacente_en("Nuevo", "nuevo", null, $es_nodo);
    verificar_no_nulo($nodo_nuevo, "_adyacente_en devuelve nodo");
    verificar_falso($es_nodo, "es_nodo es false para elemento no nodo");
    verificar_iguales($nodo_nuevo->dato(), "Nuevo", "dato del nodo insertado correcto");
    // el actual no debe cambiar
    verificar_verdadero($iter->actual() === $nodo_raiz, "la posición actual no cambia");

    // Reemplazo en el mismo enlace
    $nodo_reemplazo = $iter->_adyacente_en("Reemplazo", "nuevo");
    verificar_no_nulo($nodo_reemplazo, "_adyacente_en reemplaza y devuelve nodo");
    verificar_iguales($nodo_raiz->adyacente("nuevo")->dato(), "Reemplazo", "el enlace fue actualizado");

    // Con camino previo
    $nodo_con_camino = $iter->_adyacente_en("Camino", "camino_enlace", "a");
    verificar_no_nulo($nodo_con_camino, "_adyacente_en con camino devuelve nodo");
    verificar_iguales($nodo_a1->adyacente("camino_enlace")->dato(), "Camino", "insertó en A1 (tras avanzar por 'a')");
    verificar_verdadero($iter->actual() === $nodo_raiz, "la posición actual se restableció tras camino");

    // Error por alias inválido
    $resultado_error = $iter->_adyacente_en("X", 123);
    verificar_iguales($resultado_error, null, "_adyacente_en con alias inválido devuelve null");

    // ═══════════════════════════════════
    // 2. _adyacente (alias primero, elemento opcional)
    // ═══════════════════════════════════
    echo "<h3>2. _adyacente</h3>";

    $es_nodo2 = null;
    $nodo2 = $iter->_adyacente("otro_enlace", "Elemento2", null, $es_nodo2);
    verificar_no_nulo($nodo2, "_adyacente devuelve nodo");
    verificar_falso($es_nodo2, "es_nodo false");
    verificar_iguales($nodo_raiz->adyacente("otro_enlace")->dato(), "Elemento2", "insertó correctamente");

    // Con camino
    $nodo2b = $iter->_adyacente("enlace_b", "DesdeCamino", "a");
    verificar_no_nulo($nodo2b, "_adyacente con camino devuelve nodo");
    verificar_iguales($nodo_a1->adyacente("enlace_b")->dato(), "DesdeCamino", "insertó en A1");

    // ═══════════════════════════════════
    // 3. _adyacentes (varios a la vez)
    // ═══════════════════════════════════
    echo "<h3>3. _adyacentes</h3>";

    $arreglo = [
        "enlace_1" => "Valor1",
        "enlace_2" => "Valor2",
        "enlace_3" => "Valor3"
    ];
    verificar_verdadero($iter->_adyacentes($arreglo), "_adyacentes devuelve true");
    verificar_iguales($nodo_raiz->adyacente("enlace_1")->dato(), "Valor1", "insertó enlace_1");
    verificar_iguales($nodo_raiz->adyacente("enlace_2")->dato(), "Valor2", "insertó enlace_2");
    verificar_iguales($nodo_raiz->adyacente("enlace_3")->dato(), "Valor3", "insertó enlace_3");

    // Con camino
    $arreglo2 = ["nuevo_enlace" => "ConCamino"];
    verificar_verdadero($iter->_adyacentes($arreglo2, "a"), "_adyacentes con camino devuelve true");
    verificar_iguales($nodo_a1->adyacente("nuevo_enlace")->dato(), "ConCamino", "insertó en A1");

    // Arreglo inválido
    verificar_falso($iter->_adyacentes("no_arreglo"), "_adyacentes con no-array devuelve false");

    // ═══════════════════════════════════
    // 4. adyacentes (obtener todos)
    // ═══════════════════════════════════
    echo "<h3>4. adyacentes</h3>";

    $todos = $iter->adyacentes();
    verificar_no_nulo($todos, "adyacentes devuelve array");
    verificar_verdadero(isset($todos["a"]), "existe enlace 'a'");
    verificar_verdadero(isset($todos["nuevo"]), "existe enlace 'nuevo'");
    verificar_iguales($todos["a"]->dato(), "A1", "adyacente 'a' correcto");

    // Con camino
    $todos_a1 = $iter->adyacentes("a");
    verificar_no_nulo($todos_a1, "adyacentes con camino devuelve array");
    verificar_verdadero(isset($todos_a1["a"]), "en A1 existe enlace 'a'");
    verificar_iguales($todos_a1["a"]->dato(), "A2", "adyacente 'a' en A1 es A2");

    // ═══════════════════════════════════
    // 5. adyacente (obtener uno)
    // ═══════════════════════════════════
    echo "<h3>5. adyacente</h3>";

    $un_ady = $iter->adyacente("a");
    verificar_no_nulo($un_ady, "adyacente('a') devuelve nodo");
    verificar_iguales($un_ady->dato(), "A1", "dato correcto");

    // Con camino
    $un_ady_camino = $iter->adyacente("a", "a");
    verificar_no_nulo($un_ady_camino, "adyacente('a','a') devuelve nodo");
    verificar_iguales($un_ady_camino->dato(), "A2", "dato correcto tras camino");

    // Alias inexistente
    verificar_iguales($iter->adyacente("no_existe"), null, "adyacente inexistente devuelve null");

    // ═══════════════════════════════════
    // 6. eliminar_adyacente
    // ═══════════════════════════════════
    echo "<h3>6. eliminar_adyacente</h3>";

    $eliminado = $iter->eliminar_adyacente("nuevo");
    verificar_no_nulo($eliminado, "eliminar_adyacente devuelve nodo eliminado");
    verificar_iguales($eliminado->dato(), "Reemplazo", "dato del eliminado correcto");
    verificar_iguales($nodo_raiz->adyacente("nuevo"), null, "el enlace fue eliminado");

    // Con camino (usamos el enlace "b" para no afectar "a")
    $eliminado2 = $iter->eliminar_adyacente("b", "a");
    verificar_no_nulo($eliminado2, "eliminar_adyacente con camino devuelve nodo");
    verificar_iguales($eliminado2->dato(), "B1", "dato del eliminado correcto");
    verificar_iguales($nodo_a1->adyacente("b"), null, "en A1 ya no existe enlace 'b'");

    // Alias inexistente
    verificar_falso($iter->eliminar_adyacente("no_existe"), "eliminar inexistente devuelve false");

    // ═══════════════════════════════════
    // 7. eliminar_adyacentes (todos)
    // ═══════════════════════════════════
    echo "<h3>7. eliminar_adyacentes</h3>";

    // Crear nodo temporal para no dañar la estructura principal
    $nodo_temp = Nodo::crear();
    $nodo_temp->_adyacente_en(Nodo::crear_con_dato("TempA"), "t1");
    $nodo_temp->_adyacente_en(Nodo::crear_con_dato("TempB"), "t2");

    $iter_temp = Iterador::crear("iter_temp", $nodo_temp);
    verificar_verdadero($iter_temp->eliminar_adyacentes(), "eliminar_adyacentes devuelve true con adyacentes");
    verificar_iguales($nodo_temp->adyacente("t1"), null, "t1 eliminado");
    verificar_iguales($nodo_temp->adyacente("t2"), null, "t2 eliminado");

    // Sin adyacentes (debe devolver false)
    verificar_falso($iter_temp->eliminar_adyacentes(), "eliminar_adyacentes sin adyacentes devuelve false");
    $iter_temp->destruir();

    // Con camino, usando otro nodo temporal
    $nodo_temp2 = Nodo::crear();
    $nodo_hijo = Nodo::crear_con_dato("Hijo");
    $nodo_temp2->_adyacente_en($nodo_hijo, "a");

    $iter_temp2 = Iterador::crear("iter_temp2", $nodo_temp2);
    // Agregar un adyacente en el hijo a través del camino "a"
    $iter_temp2->_adyacentes(["temp_camino" => "TC"], "a");
    // Ahora eliminar todos los adyacentes del hijo usando camino "a"
    verificar_verdadero($iter_temp2->eliminar_adyacentes("a"), "eliminar_adyacentes con camino devuelve true");
    verificar_iguales($nodo_hijo->adyacente("temp_camino"), null, "se eliminó en hijo (tras avanzar y volver)");
    $iter_temp2->destruir();

    // ═══════════════════════════════════
    // 8. _como_adyacente_de_nodo_en_alias
    // ═══════════════════════════════════
    echo "<h3>8. _como_adyacente_de_nodo_en_alias</h3>";
    $iter->_actual($nodo_raiz);
    $nodo_externo = Nodo::crear_con_dato("Externo");
    $es_nodo3 = null;
    $resultado8 = $iter->_como_adyacente_de_nodo_en_alias($nodo_externo, "hacia_estructura", null, $es_nodo3);
    verificar_no_nulo($resultado8, "_como_adyacente_de_nodo_en_alias devuelve nodo");
    verificar_verdadero($es_nodo3, "es_nodo true para elemento nodo");
    verificar_verdadero($nodo_externo->adyacente("hacia_estructura") === $nodo_raiz, "enlace desde externo hacia raíz");

    // Con camino
    $nodo_externo2 = Nodo::crear_con_dato("Externo2");
    $resultado8b = $iter->_como_adyacente_de_nodo_en_alias($nodo_externo2, "hacia_a1", "a");
    verificar_no_nulo($resultado8b, "_como_adyacente_de_nodo_en_alias con camino devuelve nodo");
    verificar_verdadero($nodo_externo2->adyacente("hacia_a1") === $nodo_a1, "enlace desde externo2 hacia A1");

    // ═══════════════════════════════════
    // 9. _adyacente_inverso (similar a _como_adyacente_de_nodo_en_alias)
    // ═══════════════════════════════════
    echo "<h3>9. _adyacente_inverso</h3>";
    $iter->_actual($nodo_raiz);
    $es_nodo4 = null;
    $resultado9 = $iter->_adyacente_inverso("inverso_enlace", "InversoValor", null, $es_nodo4);
    verificar_no_nulo($resultado9, "_adyacente_inverso devuelve nodo");
    verificar_falso($es_nodo4, "es_nodo false para elemento no nodo");
    verificar_iguales($resultado9->dato(), "InversoValor", "dato correcto");
    verificar_verdadero($resultado9->adyacente("inverso_enlace") === $nodo_raiz, "enlace inverso desde nodo creado hacia raíz");

    // ═══════════════════════════════════
    // 10. Limpieza final
    // ═══════════════════════════════════
    echo "<h3>10. Limpieza final</h3>";
    $iter->destruir();
    verificar_verdadero(true, "Iterador destruido");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.3 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();