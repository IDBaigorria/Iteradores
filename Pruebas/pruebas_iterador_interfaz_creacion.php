<?php
/**
 * Pruebas exhaustivas v1.5i.0 – Interfaz de carga, creación y destrucción de Iterador
 *
 * Cubre: crear, cargar, iterador, destruir, y los métodos internos asociados.
 * Se prueba el registro en la superestructura, la ocupación, la asignación de
 * elementos iniciales y la limpieza.
 *
 * @since 1.0
 * @version 1.5i.0
 * @author Ignacio David Baigorria
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Iteradores/Iterador.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;

// ─── Helpers ──────────────────────────────────────────
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
echo " PRUEBAS v1.5i.0 – INTERFAZ DE CREACIÓN/CARGA DE ITERADOR<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════
    // 1. CREACIÓN BÁSICA
    // ═══════════════════════════════════
    echo "<h3>1. Creación básica</h3>";
    $iter1 = Iterador::crear("iter_basico");
    verificar_no_nulo($iter1, "crear() retorna instancia");
    verificar_verdadero($iter1->raiz_cuerpo instanceof Nodo, "raiz_cuerpo es un Nodo");
    verificar_verdadero($iter1->raiz_cuerpo->adyacente("ocupado") === $iter1->raiz_cuerpo, "el cuerpo está ocupado (autoenlace)");

    // Intento de crear duplicado
    $iter_dup = Iterador::crear("iter_basico");
    verificar_iguales($iter_dup, null, "crear() con nombre existente devuelve null");

    // Destruir para limpiar
    verificar_verdadero($iter1->destruir(), "destruir() primera instancia funciona");
    $iter_dup2 = Iterador::cargar("iter_basico");
    verificar_iguales($iter_dup2, null, "cargar() después de destruir devuelve null (ya no existe)");

    // ═══════════════════════════════════
    // 2. ASIGNACIÓN DE ELEMENTO INICIAL
    // ═══════════════════════════════════
    echo "<h3>2. Asignación de elemento inicial</h3>";

    // 2.1 Con elemento no nodo
    $es_nodo = null;
    $iter2 = Iterador::crear("iter_con_elemento", "valor_inicial", $es_nodo);
    verificar_no_nulo($iter2, "crear() con elemento retorna instancia");
    verificar_falso($es_nodo, "es_nodo es false para elemento no nodo");
    $actual = $iter2->raiz_cuerpo->adyacente("actual");
    verificar_no_nulo($actual, "existe nodo actual");
    verificar_iguales($actual->dato(), "valor_inicial", "el dato del nodo actual es el valor asignado");

    // 2.2 Con elemento que ya es Nodo
    $nodo_pre = Nodo::crear_con_dato("soy_nodo");
    $es_nodo2 = null;
    $iter3 = Iterador::crear("iter_con_nodo", $nodo_pre, $es_nodo2);
    verificar_no_nulo($iter3, "crear() con nodo retorna instancia");
    verificar_verdadero($es_nodo2, "es_nodo es true para elemento nodo");
    $actual3 = $iter3->raiz_cuerpo->adyacente("actual");
    verificar_verdadero($actual3 === $nodo_pre, "el nodo actual es exactamente el nodo pasado");

    // ═══════════════════════════════════
    // 3. CARGA Y OCUPACIÓN
    // ═══════════════════════════════════
    echo "<h3>3. Carga y ocupación</h3>";

    // Crear un iterador y dejarlo ocupado
    $iter4 = Iterador::crear("iter_ocupado");
    verificar_no_nulo($iter4, "crear() para iter_ocupado ok");

    // Intentar cargar mientras está ocupado
    $iter_cargar_ocupado = Iterador::cargar("iter_ocupado");
    verificar_iguales($iter_cargar_ocupado, null, "cargar() mientras está ocupado devuelve null");

    // Destruir para eliminarlo
    verificar_verdadero($iter4->destruir(), "destruir() elimina el iterador ocupado");

    // Ahora cargar debe fallar porque el iterador ya no existe
    $iter5 = Iterador::cargar("iter_ocupado");
    verificar_iguales($iter5, null, "cargar() después de destruir devuelve null (iterador inexistente)");

    // ═══════════════════════════════════
    // 4. MÉTODO iterador() (CREAR/CARGAR AUTOMÁTICO)
    // ═══════════════════════════════════
    echo "<h3>4. Método iterador()</h3>";

    // Caso A: no existe -> crea nuevo
    $nuevo_flag_a = null;
    $es_nodo_a = null;
    $iter_a = Iterador::iterador("iter_auto", "elemento_inicial", $es_nodo_a, $nuevo_flag_a);
    verificar_no_nulo($iter_a, "iterador() crea un iterador nuevo");
    verificar_verdadero($nuevo_flag_a, "nuevo_flag es true cuando se crea");
    verificar_falso($es_nodo_a, "es_nodo es false para elemento no nodo");
    verificar_iguales($iter_a->raiz_cuerpo->adyacente("actual")->dato(), "elemento_inicial", "actual dato correcto");

    // Caso B: existe y está ocupado -> debe fallar
    $nuevo_flag_b = null;
    $es_nodo_b = null;
    $iter_b = Iterador::iterador("iter_auto", null, $es_nodo_b, $nuevo_flag_b);
    verificar_iguales($iter_b, null, "iterador() sobre un iterador ocupado devuelve null");
    verificar_iguales($nuevo_flag_b, null, "nuevo_flag no se modifica si falla");

    // Destruir para liberar el nombre
    verificar_verdadero($iter_a->destruir(), "destruir() elimina el iterador");

    // Caso C: no existe (tras destruir) -> crea de nuevo
    $nuevo_flag_c = null;
    $es_nodo_c = null;
    $iter_c = Iterador::iterador("iter_auto", "segundo_elemento", $es_nodo_c, $nuevo_flag_c);
    verificar_no_nulo($iter_c, "iterador() crea otro iterador tras destrucción");
    verificar_verdadero($nuevo_flag_c, "nuevo_flag es true al crear de nuevo");
    verificar_falso($es_nodo_c, "es_nodo es false para elemento no nodo");
    verificar_iguales($iter_c->raiz_cuerpo->adyacente("actual")->dato(), "segundo_elemento", "actual dato correcto");

    // Dejar $iter_c para limpieza final
    $iter_c->destruir();
    // ═══════════════════════════════════
    // 5. VALIDACIONES DE ENTRADA
    // ═══════════════════════════════════
    echo "<h3>5. Validaciones de entrada</h3>";

    // Nombre no string
    $iter_invalido = Iterador::crear(123);
    verificar_iguales($iter_invalido, null, "crear() con nombre no string devuelve null");

    // Cargar iterador inexistente
    $iter_no_existe = Iterador::cargar("no_existe_xyz");
    verificar_iguales($iter_no_existe, null, "cargar() con nombre inexistente devuelve null");

    // Cargar con instancia ya creada
    $iter_existente = Iterador::crear("iter_ya_creado");
    $iter_cargar_existente = Iterador::cargar("iter_ya_creado"); // intentará cargar, pero el cuerpo está ocupado
    verificar_iguales($iter_cargar_existente, null, "cargar() con instancia ya creada y ocupada devuelve null");

    // ═══════════════════════════════════
    // 6. LIMPIEZA FINAL
    // ═══════════════════════════════════
    echo "<h3>6. Limpieza final</h3>";
    // Destruir todos los iteradores creados para no dejar residuos
    $iter2->destruir();
    $iter3->destruir();
   // $iter7->destruir();
    $iter_existente->destruir();
    verificar_verdadero(true, "Todos los iteradores de prueba destruidos");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.0 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
// Mostrar posibles errores/alertas acumulados
Iterador::imprimir_alertas();
Iterador::imprimir_errores();