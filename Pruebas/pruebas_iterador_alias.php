<?php
/**
 * Pruebas exhaustivas v1.5i.1 – Manejo de ALIAS del Iterador
 *
 * Cubre: _alias, eliminar_alias, _varios_alias, eliminar_todos_los_alias,
 *        enlace, alias, y validaciones es_alias_valido / es_enlace_valido.
 *
 * @since 1.0
 * @version 1.5i.1
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
echo " PRUEBAS v1.5i.1 – MANEJO DE ALIAS DEL ITERADOR<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════
    // 1. Validaciones base (es_alias_valido, es_enlace_valido)
    // ═══════════════════════════════════
    echo "<h3>1. Validaciones base</h3>";

    $iter = Iterador::crear("iter_alias");
    verificar_no_nulo($iter, "crear() para pruebas de alias");

    // Acceso a métodos protegidos estáticos
    $refEsAlias = new ReflectionMethod(Iterador::class, 'es_alias_valido');
    $refEsAlias->setAccessible(true);
    $refEsEnlace = new ReflectionMethod(Iterador::class, 'es_enlace_valido');
    $refEsEnlace->setAccessible(true);

    verificar_verdadero($refEsAlias->invoke(null, "alias_valido", $iter), "es_alias_valido() acepta string");
    verificar_falso($refEsAlias->invoke(null, 123, $iter), "es_alias_valido() rechaza entero");
    verificar_verdadero($refEsEnlace->invoke(null, "enlace", $iter), "es_enlace_valido() acepta string");
    verificar_verdadero($refEsEnlace->invoke(null, 42, $iter), "es_enlace_valido() acepta entero");
    verificar_falso($refEsEnlace->invoke(null, [1,2], $iter), "es_enlace_valido() rechaza array");

    // ═══════════════════════════════════
    // 2. Asignación simple de alias (_alias)
    // ═══════════════════════════════════
    echo "<h3>2. Asignación simple de alias</h3>";

    verificar_verdadero($iter->_alias("enlace_original", "mi_alias"), "_alias() asigna alias correctamente");
    verificar_iguales($iter->enlace("mi_alias"), "enlace_original", "enlace(alias) devuelve el enlace");
    verificar_iguales($iter->alias("enlace_original"), "mi_alias", "alias(enlace) devuelve el alias");

    // ═══════════════════════════════════
    // 3. Reemplazo de alias existente
    // ═══════════════════════════════════
    echo "<h3>3. Reemplazo de alias existente</h3>";

    verificar_verdadero($iter->_alias("nuevo_enlace", "mi_alias"), "_alias() reasigna alias a otro enlace");
    verificar_iguales($iter->enlace("mi_alias"), "nuevo_enlace", "enlace(alias) refleja el nuevo enlace");
    verificar_iguales($iter->alias("nuevo_enlace"), "mi_alias", "alias(enlace) refleja el nuevo enlace");
    // El enlace antiguo ya no tiene alias
    verificar_iguales($iter->alias("enlace_original"), "enlace_original", "alias() del enlace antiguo devuelve el propio enlace");

    // ═══════════════════════════════════
    // 4. Varios alias a la vez
    // ═══════════════════════════════════
    echo "<h3>4. Varios alias a la vez</h3>";

    $varios = [
        "alias_1" => "enlace_1",
        "alias_2" => "enlace_2",
        "alias_3" => "33"
    ];
    verificar_verdadero($iter->_varios_alias($varios), "_varios_alias() asigna varios correctamente");
    verificar_iguales($iter->enlace("alias_1"), "enlace_1", "enlace(alias_1) correcto");
    verificar_iguales($iter->enlace("alias_2"), "enlace_2", "enlace(alias_2) correcto");
    verificar_iguales($iter->enlace("alias_3"), "33", "enlace(alias_3) correcto (string)");
    verificar_iguales($iter->alias("enlace_2"), "alias_2", "alias(enlace_2) correcto");

    // ═══════════════════════════════════
    // 5. Eliminación de un alias individual
    // ═══════════════════════════════════
    echo "<h3>5. Eliminación de un alias individual</h3>";

    verificar_verdadero($iter->eliminar_alias("alias_2"), "eliminar_alias() devuelve true");
    verificar_iguales($iter->enlace("alias_2"), "alias_2", "enlace(alias_2) tras eliminar devuelve el propio alias");
    verificar_iguales($iter->alias("enlace_2"), "enlace_2", "alias(enlace_2) tras eliminar devuelve el propio enlace");

    // ═══════════════════════════════════
    // 6. Eliminación de todos los alias
    // ═══════════════════════════════════
    echo "<h3>6. Eliminación de todos los alias</h3>";

    verificar_verdadero($iter->eliminar_todos_los_alias(), "eliminar_todos_los_alias() devuelve true");
    verificar_iguales($iter->enlace("mi_alias"), "mi_alias", "enlace() tras limpiar devuelve el alias");
    verificar_iguales($iter->alias("nuevo_enlace"), "nuevo_enlace", "alias() tras limpiar devuelve el enlace");

    // ═══════════════════════════════════
    // 7. Validación de ocupación
    // ═══════════════════════════════════
    echo "<h3>7. Validación de ocupación</h3>";

    $iter->desocupar();
    verificar_falso($iter->_alias("x", "y"), "_alias() sin ocupar devuelve false");
    verificar_falso($iter->_varios_alias(["a" => "b"]), "_varios_alias() sin ocupar devuelve false");
    verificar_falso($iter->eliminar_alias("a"), "eliminar_alias() sin ocupar devuelve false");
    verificar_falso($iter->eliminar_todos_los_alias(), "eliminar_todos_los_alias() sin ocupar devuelve false");
    verificar_falso($iter->enlace("a"), "enlace() sin ocupar devuelve false");
    verificar_falso($iter->alias("b"), "alias() sin ocupar devuelve false");

    // ═══════════════════════════════════
    // 8. Limpieza final
    // ═══════════════════════════════════
    echo "<h3>8. Limpieza final</h3>";
    // Recargar y destruir
    $iter2 = Iterador::cargar("iter_alias");
    verificar_verdadero($iter2->destruir(), "destruir() del iterador recargado funciona");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.1 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();