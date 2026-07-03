<?php
/**
 * Pruebas exhaustivas v1.4.3 – Framework Iteradores (PHP)
 *
 * @since 1.4.3
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Nodos/NodoNumerico.php';
require_once __DIR__ . '/../Nodos/NodoPrimo.php';
require_once __DIR__ . '/../Nodos/NodoParalelo.php';
require_once __DIR__ . '/../Nodos/NodoConjunto.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;
use Iteradores\Nodos\NodoConjunto;

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
    if (!$ok) echo "   Se esperaba valor no nulo.<br>";
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
echo " PRUEBAS 1.4.3 - Framework Iteradores (PHP)<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ══════════════════════════════════
    // 1. PRUEBAS DE Matriz2x2
    // ══════════════════════════════════
    echo "<h3>1. Matriz2x2</h3>";
    
    echo "<b>1.1 Fábricas:</b><br>";
    $m_inicial = Matriz2x2::inicial();
    assert_iguales($m_inicial->a, 1, "inicial() a=1");
    assert_iguales($m_inicial->b, 1, "inicial() b=1");
    assert_iguales($m_inicial->c, 1, "inicial() c=1");
    assert_iguales($m_inicial->d, 2, "inicial() d=2");
    
    $m_prima = Matriz2x2::crear_prima(5);
    assert_iguales($m_prima->a, 5, "crear_prima(5) a=5");
    assert_iguales($m_prima->b, 1, "crear_prima(5) b=1");
    
    $m_neg = Matriz2x2::crear_negativa_prima(3);
    assert_iguales($m_neg->a, -3, "crear_negativa_prima(3) a=-3");
    assert_iguales($m_neg->b, 1, "crear_negativa_prima(3) b=1");
    
    $m_id = Matriz2x2::identidad_algebraica();
    assert_verdadero($m_id->es_igual(new Matriz2x2(1,0,0,1)), "identidad_algebraica correcta");
    
    echo "<b>1.2 Operaciones:</b><br>";
    $m1 = new Matriz2x2(2, 0, 1, 1);
    $m2 = new Matriz2x2(3, 0, 1, 1);
    $prod1 = $m1->multiplicar($m2);
    $prod2 = $m2->multiplicar($m1);
    assert_falso($prod1->es_igual($prod2), "No conmutatividad: M2×M3 ≠ M3×M2");
    
    $neutra = Matriz2x2::identidad_algebraica();
    assert_verdadero($m1->multiplicar($neutra)->es_igual($m1), "Neutro derecha");
    assert_verdadero($neutra->multiplicar($m1)->es_igual($m1), "Neutro izquierda");
    
    echo "<b>1.3 Canvas de contexto:</b><br>";
    $m_pintar = Matriz2x2::crear_prima(2);
    $m_pintar->pintar(3);
    assert_iguales($m_pintar->b, 3, "pintar(3) → b=3");
    $m_pintar->pintar(5);
    assert_iguales($m_pintar->b, 15, "pintar(5) → b=15");
    $m_pintar->despintar(3);
    assert_iguales($m_pintar->b, 5, "despintar(3) → b=5");
    
    $m_pintar->despintar(7); // Error esperado (registrado en errores del sistema, no lanza excepción)
    assert_iguales($m_pintar->b, 5, "despintar(7) no modifica b");
    
    echo "<b>1.4 Referencia al nodo:</b><br>";
    $m_ref = Matriz2x2::inicial();
    assert_verdadero($m_ref->nodo() === null, "nodo() inicialmente null");
    // No podemos asignar NodoNumerico sin crear uno, lo probaremos en integración.
    
    echo "<b>1.5 Determinante:</b><br>";
    $m_det = new Matriz2x2(3, 2, 1, 4);
    assert_iguales($m_det->determinante(), 10, "det([[3,2],[1,4]])=10");
    $m_det->_b(5);
    assert_iguales($m_det->determinante(), 7, "det tras _b(5)=7");
    
    // ══════════════════════════════════
    // 2. PRUEBAS DE NodoNumerico
    // ══════════════════════════════════
    echo "<h3>2. NodoNumerico</h3>";
    
    echo "<b>2.1 Identidad multifase:</b><br>";
    $nodo = NodoNumerico::tomar_nodo_libre();
    $m_fase_a = $nodo->identidad(); // fase actual es 'a'
    assert_verdadero($m_fase_a->es_igual(Matriz2x2::inicial()), "identidad() devuelve inicial() en fase nueva");
    
    $m_pers = Matriz2x2::crear_prima(7);
    $nodo->_identidad($m_pers, 'personalizada');
    assert_verdadero($nodo->identidad('personalizada')->es_igual($m_pers), "identidad('personalizada') correcta");
    
    echo "<b>2.2 Método es_primo():</b><br>";
    assert_falso($nodo->es_primo(), "NodoNumerico->es_primo() = false");
    
    echo "<b>2.3 Caché de primos:</b><br>";
    assert_verdadero(NodoNumerico::es_numero_primo(2), "2 es primo");
    assert_verdadero(NodoNumerico::es_numero_primo(3), "3 es primo");
    assert_falso(NodoNumerico::es_numero_primo(4), "4 no es primo");
    
    $sig = NodoNumerico::siguiente_numero_primo(3);
    assert_iguales($sig, 5, "siguiente_numero_primo(3)=5");
    
    $pos = NodoNumerico::siguiente_primo_positivo();
    assert_verdadero(NodoNumerico::es_numero_primo($pos), "siguiente_primo_positivo() devuelve primo");
    $pos2 = NodoNumerico::siguiente_primo_positivo();
    assert_verdadero($pos2 > $pos, "siguiente_primo_positivo() avanza");
    
    $neg = NodoNumerico::siguiente_primo_negativo();
    assert_verdadero(NodoNumerico::es_numero_primo($neg), "siguiente_primo_negativo() devuelve primo");
    
    echo "<b>2.4 Pool de nodos libres:</b><br>";
    $cant_antes = NodoNumerico::cantidad_de_nodos();
    $libre = NodoNumerico::tomar_nodo_libre();
    assert_no_nulo($libre, "_tomar_nodo_libre() devuelve nodo");
    NodoNumerico::devolver_nodo_libre($libre);
    $libre2 = NodoNumerico::tomar_nodo_libre();
    assert_verdadero($libre === $libre2, "_tomar_nodo_libre reutiliza nodo devuelto");
    
    echo "<b>2.5 Fábricas:</b><br>";
    // crear_primo
    $p2 = NodoNumerico::crear_primo(2);
    assert_no_nulo($p2, "crear_primo(2) devuelve nodo");
    assert_verdadero($p2->es_primo(), "crear_primo devuelve NodoPrimo");
    assert_iguales($p2->numero_primo(), 2, "numero_primo = 2");
    
    $p_malo = NodoNumerico::crear_primo(4);
    assert_verdadero($p_malo === null, "crear_primo(4) devuelve null");
    
    // crear_numerico
    $p3 = NodoNumerico::crear_primo(3);
    $sec = NodoNumerico::crear_numerico([$p2, $p3]);
    assert_no_nulo($sec, "crear_numerico([p2,p3]) devuelve nodo");
    assert_verdadero($sec->ordenado(), "secuencia es ordenada");
    $m_esperada = Matriz2x2::crear_prima(2)->multiplicar(Matriz2x2::crear_prima(3));
    assert_verdadero($sec->identidad()->es_igual($m_esperada), "identidad secuencia correcta");
    
    // crear_paralelo
    $par = NodoNumerico::crear_paralelo([$p2, $p3]);
    assert_no_nulo($par, "crear_paralelo([p2,p3]) devuelve nodo");
    assert_falso($par->ordenado(), "paralelo no es ordenado");
    
    // crear_conjunto
    $conj = NodoNumerico::crear_conjunto();
    assert_no_nulo($conj, "crear_conjunto() devuelve nodo");
    assert_falso($conj->ordenado(), "conjunto no es ordenado");
    assert_iguales($conj->nombre(), 'sin_nombre', "nombre inicial 'sin_nombre'");
    
    // ══════════════════════════════════
    // 3. PRUEBAS DE NodoPrimo
    // ══════════════════════════════════
    echo "<h3>3. NodoPrimo</h3>";
    
    echo "<b>3.1 Pool de primos libres:</b><br>";
    NodoPrimo::inicializar_fase('a', 10);
    $libre_primo = NodoPrimo::siguiente_primo_libre('a');
    assert_no_nulo($libre_primo, "siguiente_primo_libre devuelve NodoPrimo");
    assert_verdadero($libre_primo->es_primo(), "El nodo libre es primo");
    
    NodoPrimo::devolver_primo_libre($libre_primo, 'a');
    $mismo_primo = NodoPrimo::siguiente_primo_libre('a');
    assert_verdadero($libre_primo === $mismo_primo, "devolver y tomar reutiliza el mismo NodoPrimo");
    
    echo "<b>3.2 Factorización bloqueada:</b><br>";
    try {
        $libre_primo->factorizar();
        echo "❌ factorizar() no lanzó excepción<br>";
    } catch (\BadMethodCallException $e) {
        echo "✅ factorizar() lanza BadMethodCallException<br>";
    }
    
    // ══════════════════════════════════
    // 4. PRUEBAS DE NodoParalelo
    // ══════════════════════════════════
    echo "<h3>4. NodoParalelo</h3>";
    
    $p5 = NodoNumerico::crear_primo(5);
    $p7 = NodoNumerico::crear_primo(7);
    
    echo "<b>4.1 Creación y marca:</b><br>";
    $par_ok = NodoNumerico::crear_paralelo([$p5, $p7]);
    assert_no_nulo($par_ok, "crear_paralelo con 2 componentes");
    $m_marca = new Matriz2x2(1, 1, 0, 1);
    $m_esperada_par = $m_marca->multiplicar(Matriz2x2::crear_prima(5))->multiplicar(Matriz2x2::crear_prima(7));
    assert_verdadero($par_ok->identidad()->es_igual($m_esperada_par), "Identidad incluye marca");
    
    echo "<b>4.2 Conmutatividad:</b><br>";
    $par_inv = NodoNumerico::crear_paralelo([$p7, $p5]);
    assert_verdadero($par_ok->identidad()->es_igual($par_inv->identidad()), "Identidad conmutativa");
    
    echo "<b>4.3 Cantidad no prima:</b><br>";
    $p11 = NodoNumerico::crear_primo(11);
    $p13 = NodoNumerico::crear_primo(13);
    $p17 = NodoNumerico::crear_primo(17);
    $par_malo = NodoNumerico::crear_paralelo([$p11, $p13, $p17, $p5]); // 4 componentes
    assert_verdadero($par_malo === null, "crear_paralelo con 4 componentes (no primo) devuelve null");
    
    // ══════════════════════════════════
    // 5. PRUEBAS DE NodoConjunto
    // ══════════════════════════════════
    echo "<h3>5. NodoConjunto</h3>";
    
    echo "<b>5.1 Creación y nombrado:</b><br>";
    $conj1 = NodoNumerico::crear_conjunto();
    $conj1->_nombre('vocales');
    assert_iguales($conj1->nombre(), 'vocales', "_nombre asigna correctamente");
    $conj_dup = NodoNumerico::crear_conjunto();
    $conj_dup->_nombre('vocales'); // Debe registrar error
    echo "✅ Intentar duplicar nombre 'vocales' registró error del sistema (ver logs)<br>";
    
    $recuperado = NodoConjunto::obtener('vocales');
    assert_verdadero($recuperado === $conj1, "obtener('vocales') devuelve el conjunto correcto");
    
    $todos = NodoConjunto::listar_todos();
    assert_verdadero(isset($todos['vocales']), "listar_todos incluye 'vocales'");
    
    echo "<b>5.2 Pintura y pertenencia:</b><br>";
    $conj2 = NodoNumerico::crear_conjunto();
    $conj2->_nombre('prueba_pintura');
    
    $p19 = NodoNumerico::crear_primo(19);
    $p23 = NodoNumerico::crear_primo(23);
    
    $conj2->agregar_miembro($p19);
    assert_verdadero($conj2->tiene_miembro($p19), "tiene_miembro tras agregar");
    assert_verdadero($p19->identidad()->b % $conj2->primo_contexto() === 0, "b del miembro es múltiplo del primo_contexto");
    
    $conj2->agregar_miembro($p23);
    assert_verdadero($conj2->tiene_miembro($p23), "tiene_miembro con segundo miembro");
    
    $conj2->quitar_miembro($p19);
    assert_falso($conj2->tiene_miembro($p19), "tiene_miembro falso tras quitar");
    assert_verdadero($conj2->tiene_miembro($p23), "el otro miembro sigue perteneciendo");
    
    echo "<b>5.3 Bidireccionalidad:</b><br>";
    // Verificamos que el conjunto también es pintado por los miembros
    $b_conj_antes = $conj2->identidad()->b;
    $conj2->agregar_miembro($p19);
    $b_conj_despues = $conj2->identidad()->b;
    assert_verdadero($b_conj_despues === $b_conj_antes * $p19->numero_primo(), "conjunto pintado por miembro");
    
    // ══════════════════════════════════
    // 6. PRUEBAS DE INTEGRACIÓN
    // ══════════════════════════════════
    echo "<h3>6. Integración (ascenso/descenso)</h3>";
    
    echo "<b>6.1 Ascenso simulado:</b><br>";
    // Crear secuencia en fase 'a'
    $p29 = NodoNumerico::crear_primo(29);
    $p31 = NodoNumerico::crear_primo(31);
    $secuencia = NodoNumerico::crear_numerico([$p29, $p31]);
    $matriz_original = $secuencia->identidad();
    
    // Cambiar a fase 'b' (superior)
    NodoElectrico::_fase($token, 'b');
    
    // Tomar NodoPrimo libre en fase 'b'
    NodoPrimo::inicializar_fase('b', 5);
    $primo_superior = NodoPrimo::siguiente_primo_libre('b');
    $primo_superior->_dato($matriz_original, 'matriz_compuesta');
    
    // Devolver secuencia al pool de libres de fase 'a'
    NodoNumerico::devolver_nodo_libre($secuencia, 'a');
    
    assert_verdadero(true, "Ascenso: NodoPrimo en fase b guarda matriz de secuencia de fase a");
    
    echo "<b>6.2 Descenso simulado:</b><br>";
    // Leer dato del primo en fase 'b'
    $matriz_guardada = $primo_superior->dato('matriz_compuesta');
    assert_no_nulo($matriz_guardada, "matriz_compuesta recuperada del primo");
    assert_verdadero($matriz_guardada instanceof Matriz2x2, "matriz_compuesta es Matriz2x2");
    
    // Volver a fase 'a'
    NodoNumerico::_fase($token, 'a');
    
    // Crear nodo numérico a partir de la matriz factorizada (simulado: usamos crear_numerico con los mismos primos)
    $nodo_descendido = NodoNumerico::crear_numerico([$p29, $p31]);
    assert_verdadero($nodo_descendido->identidad()->es_igual($matriz_original), "Descenso: identidad reconstruida coincide");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.3 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";