<?php
/**
 * Pruebas exhaustivas v1.5i.2 – Interfaz Avanzar del Iterador
 *
 * Cubre: avanzar_escapar, camino, avanzar_interno, avanzar, _avanzar
 * con distintos tipos de caminos y situaciones de error.
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
echo " PRUEBAS v1.5i.2 – INTERFAZ AVANZAR<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════
    // 1. avanzar_escapar
    // ═══════════════════════════════════
    echo "<h3>1. avanzar_escapar</h3>";
    $iter = new Iterador();
    verificar_iguales($iter->avanzar_escapar("a;b>c*d/e"), "a/;b/>c/*d//e", "escapa ; > * /");
    verificar_iguales($iter->avanzar_escapar("sin_especiales"), "sin_especiales", "sin especiales queda igual");
    verificar_iguales($iter->avanzar_escapar(""), "", "cadena vacía queda igual");

    // ═══════════════════════════════════
    // 2. camino (parseo)
    // ═══════════════════════════════════
    echo "<h3>2. camino (parseo)</h3>";

    // crear iterador para usar métodos de instancia
    $iter2 = Iterador::crear("iter_avanzar");
    verificar_no_nulo($iter2, "crear iterador para pruebas");

    // camino válido simple
    $cam = $iter2->camino("a;b;c");
    verificar_no_nulo($cam, "camino('a;b;c') devuelve nodo");
    verificar_iguales($cam->dato(), 3, "cantidad de eslabones es 3");
    // verificar primer eslabón
    $primero = $cam->adyacente("eslabon");
    verificar_no_nulo($primero, "existe primer eslabón");
    $alias = $primero->adyacente("alias");
    verificar_no_nulo($alias, "existe alias del primer eslabón");
    verificar_iguales($alias->dato(), "a", "primer alias es 'a'");

    // camino con símbolo >
    $cam2 = $iter2->camino("a>;b>3;c");
    verificar_no_nulo($cam2, "camino con símbolos > devuelve nodo");
    verificar_iguales($cam2->dato(), 3, "cantidad correcta");

    // camino inválido (empieza con ;)
    $cam_inv1 = $iter2->camino(";a");
    verificar_iguales($cam_inv1, null, "camino inválido que empieza con ; devuelve null");

    // camino inválido (segundo eslabón empieza con >)
    $cam_inv2 = $iter2->camino("a;>b");
    verificar_iguales($cam_inv2, null, "camino inválido con > al inicio de eslabón devuelve null");

    // camino válido con > sin número (avanza hasta el final)
    $cam_valido_sin_num = $iter2->camino("a;b>");
    verificar_no_nulo($cam_valido_sin_num, "camino('a;b>') es válido y devuelve nodo");
    verificar_iguales($cam_valido_sin_num->dato(), 2, "tiene 2 eslabones");
    $iter2->eliminar_camino($cam_valido_sin_num); // limpiar

    // limpiar caminos creados (no se guardan en caché aún)
    // no es necesario porque camino() no registra en caché; se elimina automáticamente en error o queda en memoria, pero lo limpiamos
    $iter2->eliminar_camino($cam);
    $iter2->eliminar_camino($cam2);

    // ═══════════════════════════════════
    // 3. avanzar con estructura simple
    // ═══════════════════════════════════
    echo "<h3>3. avanzar con estructura simple</h3>";

    // Crear nodos y enlaces
    $nodo_raiz = Nodo::crear_con_dato("raiz");
    $nodo_a1 = Nodo::crear_con_dato("A1");
    $nodo_a2 = Nodo::crear_con_dato("A2");
    $nodo_a3 = Nodo::crear_con_dato("A3");
    $nodo_b1 = Nodo::crear_con_dato("B1");

    $nodo_raiz->_adyacente_en($nodo_a1, "a");
    $nodo_a1->_adyacente_en($nodo_a2, "a");
    $nodo_a2->_adyacente_en($nodo_a3, "a");
    $nodo_a1->_adyacente_en($nodo_b1, "b");

    // Asignar actual en iterador
    $iter3 = Iterador::crear("iter_avanzar_2", $nodo_raiz);
    verificar_no_nulo($iter3, "crear iterador con actual raiz");

    // avanzar un paso
    $resultado = $iter3->avanzar("a");
    verificar_no_nulo($resultado, "avanzar('a') devuelve nodo");
    verificar_iguales($resultado->dato(), "A1", "avanzó a A1");

    // avanzar dos pasos con >2
    $resultado2 = $iter3->avanzar("a>2");
    verificar_no_nulo($resultado2, "avanzar('a>2') devuelve nodo");
    verificar_iguales($resultado2->dato(), "A3", "avanzó dos veces por 'a'");

    // Intentar avanzar por 'b' desde A3 (ruta errónea) – debe fallar sin colgarse
    $resultado_error = $iter3->avanzar("b");
    verificar_iguales($resultado_error, null, "avanzar('b') desde A3 devuelve null (ruta inexistente)");

    // Reposicionar en A1 para avanzar correctamente por 'b'
    $iter3->_actual($nodo_a1);
    $resultado3 = $iter3->avanzar("b");
    verificar_no_nulo($resultado3, "avanzar('b') desde A1 devuelve nodo");
    verificar_iguales($resultado3->dato(), "B1", "avanzó a B1");

    // ═══════════════════════════════════
    // 4. avanzar con camino de múltiples eslabones
    // ═══════════════════════════════════
    echo "<h3>4. avanzar con camino de múltiples eslabones</h3>";

    // Reiniciar posición actual
    $iter3->_actual($nodo_raiz);

    $camino_recorrido = "";
    $camino_restante = "";
    $resultado4 = $iter3->avanzar("a;a", null, $camino_recorrido, $camino_restante);
    verificar_no_nulo($resultado4, "avanzar('a;a') devuelve nodo");
    verificar_iguales($resultado4->dato(), "A2", "terminó en A2");
    verificar_iguales($camino_recorrido, "a;a", "camino_recorrido correcto");
    verificar_iguales($camino_restante, "", "no hay camino restante");

    // ═══════════════════════════════════
    // 5. avanzar con cantidad parcial (cant)
    // ═══════════════════════════════════
    echo "<h3>5. avanzar con cantidad parcial</h3>";

    $iter3->_actual($nodo_raiz);

    $camino_recorrido2 = "";
    $camino_restante2 = "";
    $resultado5 = $iter3->avanzar("a;a;a;b", 2, $camino_recorrido2, $camino_restante2);
    verificar_no_nulo($resultado5, "avanzar con cant=2 devuelve nodo");
    verificar_iguales($resultado5->dato(), "A2", "avanzó solo 2 eslabones");
    verificar_iguales($camino_recorrido2, "a;a;", "camino recorrido parcial correcto");
    verificar_iguales($camino_restante2, "a;b", "camino restante correcto");

    // ═══════════════════════════════════
    // 6. avanzar con > sin número (hasta el final)
    // ═══════════════════════════════════
    echo "<h3>6. avanzar con > sin número (hasta el final)</h3>";

    $iter3->_actual($nodo_raiz);

    $resultado6 = $iter3->avanzar("a>");
    verificar_no_nulo($resultado6, "avanzar('a>') devuelve nodo");
    verificar_iguales($resultado6->dato(), "A3", "llegó al final de la cadena 'a'");

    // ═══════════════════════════════════
    // 7. avanzar con error y retroceso
    // ═══════════════════════════════════
    echo "<h3>7. avanzar con error y retroceso</h3>";

    $iter3->_actual($nodo_raiz);
    $origen = $iter3->actual();

    $resultado7 = $iter3->avanzar("a;a;a;a"); // solo hay 3 'a'
    verificar_iguales($resultado7, null, "avanzar camino más largo devuelve null");
    $actual_ahora = $iter3->actual();
    verificar_verdadero($actual_ahora === $origen, "la posición actual se restableció al origen");

    // ═══════════════════════════════════
    // 8. _avanzar (insertar y avanzar)
    // ═══════════════════════════════════
    echo "<h3>8. _avanzar (insertar y avanzar)</h3>";

    $iter4 = Iterador::crear("iter_avanzar_3", $nodo_raiz);

    // Insertar nuevo nodo en enlace "nuevo"
    $es_nodo = null;
    $nuevo_nodo = $iter4->_avanzar("nuevo", "SoyNuevo", null, $es_nodo);
    verificar_no_nulo($nuevo_nodo, "_avanzar inserta nodo");
    verificar_falso($es_nodo, "elemento no era nodo");
    verificar_iguales($nuevo_nodo->dato(), "SoyNuevo", "dato del nodo insertado correcto");

    // Verificar que el nodo actual es el nuevo
    verificar_verdadero($iter4->actual() === $nuevo_nodo, "actual es el nodo insertado");

    // Insertar con camino previo
    $iter4->_actual($nodo_raiz);
    $nuevo_nodo2 = $iter4->_avanzar("nuevo", "ConCamino", "a", $es_nodo);
    verificar_no_nulo($nuevo_nodo2, "_avanzar con camino previo funciona");
    // Ahora actual es el nodo insertado después de avanzar por "a"
    $actual_nuevo = $iter4->actual();
    verificar_iguales($actual_nuevo->dato(), "ConCamino", "dato correcto tras camino");

    // Verificar que el enlace "nuevo" en nodo A1 apunta a nuevo nodo
    $nodo_a1_despues = $nodo_raiz->adyacente("a");
    $nodo_insertado_en_a1 = $nodo_a1_despues->adyacente("nuevo");
    verificar_no_nulo($nodo_insertado_en_a1, "existe enlace 'nuevo' en A1");
    verificar_iguales($nodo_insertado_en_a1->dato(), "ConCamino", "el dato del enlace es correcto");

    // ═══════════════════════════════════
    // 9. Limpieza final
    // ═══════════════════════════════════
    echo "<h3>9. Limpieza final</h3>";
    $iter2->destruir();
    $iter3->destruir();
    $iter4->destruir();
    verificar_verdadero(true, "Iteradores de prueba destruidos");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.2 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();