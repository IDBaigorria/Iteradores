<?php
/**
 * Pruebas exhaustivas de Matriz2x2 (versión 1.4.0).
 *
 * @since 1.4.0
 */
require_once __DIR__ . '/../Nodos/Matriz2x2.php';

use Iteradores\Nodos\Matriz2x2;

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

function assert_verdadero($valor, string $mensaje): bool {
    $ok = (bool) $valor;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

function assert_excepcion(callable $callable, string $mensaje): bool {
    try {
        $callable();
        echo "❌ " . $mensaje . " — no se lanzó excepción<br>";
        return false;
    } catch (\Throwable $e) {
        echo "✅ " . $mensaje . " (excepción capturada)<br>";
        return true;
    }
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS Matriz2x2 1.4.0 (PHP)<br>";
echo "══════════════════════════════════<br><br>";

// ─── 1. Constructor y getters ─────────────────────────
echo "--- 1. Constructor y acceso a componentes ---<br>";
$m = new Matriz2x2(2, 0, 1, 1);
assert_iguales($m->a, 2, "a = 2");
assert_iguales($m->b, 0, "b = 0");
assert_iguales($m->c, 1, "c = 1");
assert_iguales($m->d, 1, "d = 1");

// ─── 2. Fábrica neutra() ─────────────────────────────
echo "<br>--- 2. Matriz neutra ---<br>";
$neutra = Matriz2x2::neutra();
assert_iguales($neutra->a, 1, "neutra a = 1");
assert_iguales($neutra->d, 1, "neutra d = 1");
assert_iguales($neutra->b, 0, "neutra b = 0");
assert_iguales($neutra->c, 0, "neutra c = 0");
assert_verdadero($neutra->es_igual(new Matriz2x2(1,0,0,1)), "neutra es igual a [[1,0],[0,1]]");

// ─── 3. crear_prima() ─────────────────────────────────
echo "<br>--- 3. Matriz canónica de primo ---<br>";
$p2 = Matriz2x2::crear_prima(2);
assert_iguales($p2->a, 2, "primo 2: a");
assert_iguales($p2->b, 0, "primo 2: b");
assert_iguales($p2->c, 1, "primo 2: c");
assert_iguales($p2->d, 1, "primo 2: d");

$p3 = Matriz2x2::crear_prima(3);
assert_iguales($p3->a, 3, "primo 3: a");

// ─── 4. desde_array() ─────────────────────────────────
echo "<br>--- 4. Fábrica desde_array ---<br>";
$arr = Matriz2x2::desde_array([5,1,2,3]);
assert_iguales($arr->a, 5, "desde_array a");
assert_iguales($arr->d, 3, "desde_array d");

// Caso inválido
assert_excepcion(fn() => Matriz2x2::desde_array([1,2,3]), "desde_array con 3 elementos lanza excepción");

// ─── 5. multiplicar() ─────────────────────────────────
echo "<br>--- 5. Multiplicación ---<br>";
$A = new Matriz2x2(1, 2, 3, 4);
$B = new Matriz2x2(5, 6, 7, 8);
$C = $A->multiplicar($B);
// C = A*B = [[1*5+2*7, 1*6+2*8], [3*5+4*7, 3*6+4*8]]
// = [[5+14, 6+16], [15+28, 18+32]] = [[19,22],[43,50]]
assert_iguales($C->a, 19, "producto a");
assert_iguales($C->b, 22, "producto b");
assert_iguales($C->c, 43, "producto c");
assert_iguales($C->d, 50, "producto d");

// No conmutatividad: M(2)*M(3) vs M(3)*M(2)
$m2 = Matriz2x2::crear_prima(2);
$m3 = Matriz2x2::crear_prima(3);
$m23 = $m2->multiplicar($m3);
$m32 = $m3->multiplicar($m2);
assert_iguales($m23->determinante(), 6, "det(2*3) = 6");
assert_iguales($m32->determinante(), 6, "det(3*2) = 6");
assert_verdadero(!$m23->es_igual($m32), "M(2)*M(3) != M(3)*M(2)");
assert_iguales($m23->c, 4, "2*3 entrada c");
assert_iguales($m32->c, 3, "3*2 entrada c");

// Multiplicación por neutra
$prod = $A->multiplicar($neutra);
assert_verdadero($prod->es_igual($A), "A * neutra = A");
$prod = $neutra->multiplicar($A);
assert_verdadero($prod->es_igual($A), "neutra * A = A");

// ─── 6. determinante() ────────────────────────────────
echo "<br>--- 6. Determinante ---<br>";
assert_iguales($neutra->determinante(), 1, "det(neutra) = 1");
assert_iguales($m2->determinante(), 2, "det(primo 2) = 2");
$m6a = $m2->multiplicar($m3);
assert_iguales($m6a->determinante(), 6, "det(2*3) = 6");
$mDiag = new Matriz2x2(10, 0, 0, 5);
assert_iguales($mDiag->determinante(), 50, "det diagonal 10,0,0,5 = 50");

// ─── 7. es_igual() ────────────────────────────────────
echo "<br>--- 7. Igualdad ---<br>";
assert_verdadero((new Matriz2x2(1,2,3,4))->es_igual(new Matriz2x2(1,2,3,4)), "Matrices iguales");
assert_verdadero(! (new Matriz2x2(1,2,3,4))->es_igual(new Matriz2x2(1,2,3,5)), "Matrices diferentes");

// ─── 8. __toString() ──────────────────────────────────
echo "<br>--- 8. Representación en string ---<br>";
$str = (string) $m2;
assert_iguales($str, '[[2,0],[1,1]]', "toString M(2)");
$str = (string) $m23;
assert_iguales($str, '[[6,0],[4,1]]', "toString M(2)*M(3)");

// ─── 9. siguiente_numero_primo() ──────────────────────
echo "<br>--- 9. Siguiente número primo ---<br>";
assert_iguales(Matriz2x2::siguiente_numero_primo(1), 2, "después de 1 -> 2");
assert_iguales(Matriz2x2::siguiente_numero_primo(2), 3, "después de 2 -> 3");
assert_iguales(Matriz2x2::siguiente_numero_primo(3), 5, "después de 3 -> 5");
assert_iguales(Matriz2x2::siguiente_numero_primo(10), 11, "después de 10 -> 11");
assert_iguales(Matriz2x2::siguiente_numero_primo(100), 101, "después de 100 -> 101");

// ─── 10. Pruebas adicionales ──────────────────────────
echo "<br>--- 10. Pruebas de estabilidad ---<br>";
$p97 = Matriz2x2::siguiente_numero_primo(96);
assert_iguales($p97, 97, "primo después de 96 es 97");

// Crear matriz con entrada negativa (se acepta, aunque solo trabajaremos con positivos)
$neg = new Matriz2x2(-1, 0, 0, 1);
assert_iguales($neg->determinante(), -1, "det con entrada negativa");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";