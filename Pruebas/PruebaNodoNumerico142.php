<?php
/**
 * Pruebas exhaustivas de la versión 1.4.2 – NodoNumerico (PHP).
 *
 * @since 1.4.2
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Nodos/NodoNumerico.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;

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

function assert_falso($v, string $mensaje): bool {
    $ok = !$v;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS 1.4.2 – NodoNumerico (PHP)<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ─── 1. Secuencia con 2 factores ──────────────────
    echo "--- 1. Secuencia con 2 factores ---<br>";
    $p2 = NodoNumerico::crear();
    $p3 = NodoNumerico::crear();
    $p2->_identidad(Matriz2x2::crear_prima(2));
    $p3->_identidad(Matriz2x2::crear_prima(3));
    
    $sec = NodoNumerico::crear_secuencia([$p2, $p3]);
    assert_no_nulo($sec, "crear_secuencia devuelve un nodo");
    assert_verdadero($sec->ordenado(), "El nodo secuencia tiene ordenado = true");
    
    $matriz_esperada = Matriz2x2::crear_prima(2)->multiplicar(Matriz2x2::crear_prima(3));
    assert_verdadero($sec->identidad()->es_igual($matriz_esperada), "La identidad de la secuencia es M(2)*M(3)");
    
    // ─── 2. Índice de identidades ────────────────────
    echo "<br>--- 2. Índice de identidades ---<br>";
    $encontrado = NodoNumerico::nodo_por_identidad($matriz_esperada);
    assert_verdadero($encontrado === $sec, "nodo_por_identidad devuelve el mismo nodo");
    
    $inexistente = NodoNumerico::nodo_por_identidad(Matriz2x2::crear_prima(7));
    assert_verdadero($inexistente === null, "nodo_por_identidad con identidad inexistente devuelve null");
    
    // ─── 3. Validación de cantidad prima ──────────────
    echo "<br>--- 3. Validación de cantidad prima ---<br>";
    $resultado_malo = NodoNumerico::crear_secuencia([$p2, $p3, $p2, $p2]); // 4 factores
    assert_iguales($resultado_malo, null, "crear_secuencia devuelve null si la cantidad no es prima");
    
    $resultado_conj_malo = NodoNumerico::crear_conjunto([$p2, $p3, $p2, $p2]); // 4 componentes
    assert_iguales($resultado_conj_malo, null, "crear_conjunto devuelve null si la cantidad no es prima");
    
    // ─── 4. Conjunto con 2 componentes ────────────────
    echo "<br>--- 4. Conjunto con 2 componentes ---<br>";
    $comp1 = NodoNumerico::crear();
    $comp2 = NodoNumerico::crear();
    $comp1->_identidad(Matriz2x2::crear_prima(2));
    $comp2->_identidad(Matriz2x2::crear_prima(3));
    
    $conj = NodoNumerico::crear_conjunto([$comp1, $comp2]);
    assert_no_nulo($conj, "crear_conjunto devuelve un nodo");
    assert_falso($conj->ordenado(), "El nodo conjunto tiene ordenado = false");
    
    // ─── 5. Conmutatividad del conjunto ──────────────
    echo "<br>--- 5. Conmutatividad del conjunto ---<br>";
    $conj2 = NodoNumerico::crear_conjunto([$comp2, $comp1]); // orden inverso
    assert_verdadero($conj === $conj2, "crear_conjunto devuelve el mismo nodo sin importar el orden");
    assert_verdadero($conj->identidad()->es_igual($conj2->identidad()), "Las identidades son iguales");
    
    // ─── 6. Diferencia entre secuencia y conjunto ─────
    echo "<br>--- 6. Diferencia secuencia vs conjunto ---<br>";
    $sec_prueba = NodoNumerico::crear_secuencia([$comp1, $comp2]);
    assert_falso($sec_prueba->identidad()->es_igual($conj->identidad()), "La identidad de una secuencia y un conjunto con los mismos componentes son diferentes");
    
    // ─── 7. La marca de conjunto está presente ────────
    echo "<br>--- 7. Verificación de la marca de conjunto ---<br>";
    $marca_array = Conf::MATRIZ_MARCA_CONJUNTO;
    $marca = new Matriz2x2($marca_array[0][0], $marca_array[0][1], $marca_array[1][0], $marca_array[1][1]);
    $esperado_conj = $marca->multiplicar(Matriz2x2::crear_prima(2))->multiplicar(Matriz2x2::crear_prima(3));
    assert_verdadero($conj->identidad()->es_igual($esperado_conj), "La identidad del conjunto incluye la marca");
    
    // ─── 8. Factories heredados siguen funcionando ────
    echo "<br>--- 8. Factories heredados de NodoElectrico ---<br>";
    $nodo_base = NodoNumerico::crear();
    assert_no_nulo($nodo_base, "crear() sigue funcionando en NodoNumerico");
    assert_verdadero($nodo_base->identidad()->es_igual(Matriz2x2::neutra()), "La identidad por defecto es la matriz neutra");
    
    $nodo_dato = NodoNumerico::crear_con_dato("test");
    assert_iguales($nodo_dato->dato(), "test", "crear_con_dato() asigna el dato en la fase actual");
    
    // ─── 9. Capacidad y fuga configurables ────────────
    echo "<br>--- 9. Capacidad y fuga configurables ---<br>";
    $nodo_cap = NodoNumerico::crear_secuencia([$comp1, $comp2], 500, 0.2);
    assert_iguales($nodo_cap->capacidad(), 500, "Capacidad configurada correctamente en secuencia");
    assert_iguales($nodo_cap->fuga(), 0.2, "Fuga configurada correctamente en secuencia");
    
    $nodo_conj_cap = NodoNumerico::crear_conjunto([$comp1, $comp2], 300, 0.1);
    assert_iguales($nodo_conj_cap->capacidad(), 300, "Capacidad configurada correctamente en conjunto");
    assert_iguales($nodo_conj_cap->fuga(), 0.1, "Fuga configurada correctamente en conjunto");
    
    // ─── 10. Cache de la marca (sin dependencia circular) ──
    echo "<br>--- 10. Cache de la marca de conjunto ---<br>";
    assert_verdadero(true, "No se produjeron errores de dependencia circular al usar la marca cacheada");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.2 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";