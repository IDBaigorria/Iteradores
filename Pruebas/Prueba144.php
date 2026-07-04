<?php
/**
 * Pruebas exhaustivas v1.4.4 – Framework Iteradores (PHP)
 *
 * @since 1.4.4
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Nodos/NodoNumerico.php';
require_once __DIR__ . '/../Nodos/NodoPrimo.php';
require_once __DIR__ . '/../Nodos/NodoParalelo.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;

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
echo " PRUEBAS 1.4.4 - Framework Iteradores (PHP)<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ══════════════════════════════════
    // 1. PRUEBAS DE Matriz2x2
    // ══════════════════════════════════
    echo "<h3>1. Matriz2x2</h3>";
    
    echo "<b>1.1 Fábricas:</b><br>";
    $m_inicial = Matriz2x2::inicial();
    assert_iguales($m_inicial->a, 1, "inicial() a=1");
    assert_iguales($m_inicial->b, 0, "inicial() b=0");
    assert_iguales($m_inicial->c, 1, "inicial() c=1");
    assert_iguales($m_inicial->d, 1, "inicial() d=1");
    
    $m_prima = Matriz2x2::crear_prima(5);
    assert_iguales($m_prima->a, 5, "crear_prima(5) a=5");
    assert_iguales($m_prima->b, 0, "crear_prima(5) b=0");
    
    $m_neg = Matriz2x2::crear_negativa_prima(3);
    assert_iguales($m_neg->a, -3, "crear_negativa_prima(3) a=-3");
    assert_iguales($m_neg->b, 0, "crear_negativa_prima(3) b=0");
    
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
    
    echo "<b>1.3 Inmutabilidad de b:</b><br>";
    $m_prueba = new Matriz2x2(2, 0, 1, 1);
    assert_iguales($m_prueba->b, 0, "b es 0 en matriz canónica");
    assert_iguales($m_prueba->determinante(), 2, "det([[2,0],[1,1]])=2");
    
    echo "<b>1.4 Referencia al nodo:</b><br>";
    $m_ref = Matriz2x2::inicial();
    assert_verdadero($m_ref->nodo() === null, "nodo() inicialmente null");
    
    echo "<b>1.5 Determinante:</b><br>";
    $m_det = new Matriz2x2(3, 0, 1, 1);
    assert_iguales($m_det->determinante(), 3, "det([[3,0],[1,1]])=3");
    
    // ══════════════════════════════════
    // 2. PRUEBAS DE NodoNumerico
    // ══════════════════════════════════
    echo "<h3>2. NodoNumerico</h3>";
    
    echo "<b>2.1 Identidad multifase:</b><br>";
    $nodo = NodoNumerico::tomar_nodo_libre();
    $m_fase_a = $nodo->identidad();
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
    
    echo "<b>2.4 Pool de nodos libres:</b><br>";
    $libre = NodoNumerico::tomar_nodo_libre();
    assert_no_nulo($libre, "tomar_nodo_libre() devuelve nodo");
    NodoNumerico::devolver_nodo_libre($libre);
    $libre2 = NodoNumerico::tomar_nodo_libre();
    assert_verdadero($libre === $libre2, "tomar_nodo_libre reutiliza nodo devuelto");
    
    echo "<b>2.5 Fábricas:</b><br>";
    // crear_primo positivo
    $p2 = NodoNumerico::crear_primo(2);
    assert_no_nulo($p2, "crear_primo(2) devuelve nodo");
    assert_verdadero($p2->es_primo(), "crear_primo devuelve NodoPrimo");
    assert_iguales($p2->numero_primo(), 2, "numero_primo = 2");
    
    // crear_primo negativo (deshacer)
    $p_neg = NodoNumerico::crear_primo(-2);
    assert_no_nulo($p_neg, "crear_primo(-2) devuelve nodo");
    assert_verdadero($p_neg->es_primo(), "crear_primo negativo devuelve NodoPrimo");
    assert_iguales($p_neg->numero_primo(), -2, "numero_primo = -2");
    assert_iguales($p_neg->identidad()->a, -2, "matriz de primo negativo tiene a=-2");
    
    $p_malo = NodoNumerico::crear_primo(4);
    assert_verdadero($p_malo === null, "crear_primo(4) devuelve null");
    
    // crear_numerico
    $p3 = NodoNumerico::crear_primo(3);
    $sec = NodoNumerico::crear_numerico([$p2, $p3]);
    assert_no_nulo($sec, "crear_numerico([p2,p3]) devuelve nodo");
    $m_esperada = Matriz2x2::crear_prima(2)->multiplicar(Matriz2x2::crear_prima(3));
    assert_verdadero($sec->identidad()->es_igual($m_esperada), "identidad secuencia correcta");

    // Verificar p‑grama de secuencia
    $pgrama_sec = $sec->pgrama();
    assert_verdadero($pgrama_sec === [2, 3], "p‑grama de secuencia es [2, 3]");
    assert_verdadero($pgrama_sec[0] !== 1, "p‑grama de secuencia NO empieza con 1");
    
    // crear_numerico con cantidad no prima
    $sec_mala = NodoNumerico::crear_numerico([$p2, $p3, $p2, $p2]);
    assert_verdadero($sec_mala === null, "crear_numerico con 4 componentes devuelve null");
    
    // crear_paralelo
    $par = NodoNumerico::crear_paralelo([$p2, $p3]);
    assert_no_nulo($par, "crear_paralelo([p2,p3]) devuelve nodo");

    // Verificar p‑grama de paralelo
    $pgrama_par = $par->pgrama();
    assert_verdadero($pgrama_par === [1, 2, 3], "p‑grama de paralelo es [1, 2, 3]");
    assert_verdadero($pgrama_par[0] === 1, "p‑grama de paralelo EMPIEZA con 1");
    
    // Verificar que crear_conjunto ya no existe
    assert_verdadero(!method_exists(NodoNumerico::class, 'crear_conjunto'), "crear_conjunto ya no existe en v1.4.4");
    
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
    
    echo "<b>3.3 Primos negativos y p‑grama:</b><br>";
    $p_deshacer = NodoNumerico::crear_primo(-5);
    assert_iguales($p_deshacer->numero_primo(), -5, "numero_primo de deshacer es -5");
    assert_verdadero($p_deshacer->identidad()->a === -5, "matriz tiene a=-5");
    assert_verdadero($p_deshacer->identidad()->b === 0, "matriz tiene b=0");
    assert_verdadero($p_deshacer->identidad()->determinante() === -5, "determinante es -5");
    assert_verdadero($p_deshacer->pgrama() === [-5], "p‑grama de deshacer es [-5]");
    
    // ══════════════════════════════════
    // 4. PRUEBAS DE NodoParalelo
    // ══════════════════════════════════
    echo "<h3>4. NodoParalelo</h3>";
    
    $p5 = NodoNumerico::crear_primo(5);
    $p7 = NodoNumerico::crear_primo(7);
    
    echo "<b>4.1 Creación y p‑grama:</b><br>";
    $par_ok = NodoNumerico::crear_paralelo([$p5, $p7]);
    assert_no_nulo($par_ok, "crear_paralelo con 2 componentes");
    
    $pgrama_par_ok = $par_ok->pgrama();
    assert_verdadero($pgrama_par_ok[0] === 1, "p‑grama de paralelo empieza con 1");
    assert_verdadero(count($pgrama_par_ok) === 3, "p‑grama de paralelo tiene 3 elementos (1 + 2 primos)");
    
    $m_esperada_par = Matriz2x2::inicial()->multiplicar(Matriz2x2::crear_prima(5))->multiplicar(Matriz2x2::crear_prima(7));
    assert_verdadero($par_ok->identidad()->es_igual($m_esperada_par), "Identidad del paralelo usa M(1) en lugar de marca antigua");
    
    echo "<b>4.2 Conmutatividad:</b><br>";
    $par_inv = NodoNumerico::crear_paralelo([$p7, $p5]);
    assert_verdadero($par_ok->identidad()->es_igual($par_inv->identidad()), "Identidad conmutativa");
    assert_verdadero($par_ok->pgrama() === $par_inv->pgrama(), "p‑grama conmutativo");
    
    echo "<b>4.3 Cantidad no prima:</b><br>";
    $p11 = NodoNumerico::crear_primo(11);
    $p13 = NodoNumerico::crear_primo(13);
    $p17 = NodoNumerico::crear_primo(17);
    $par_malo = NodoNumerico::crear_paralelo([$p11, $p13, $p17, $p5]);
    assert_verdadero($par_malo === null, "crear_paralelo con 4 componentes (no primo) devuelve null");
    
    echo "<b>4.4 Paralelo con deshaceres:</b><br>";
    $p_neg5 = NodoNumerico::crear_primo(-5);
    $p_neg7 = NodoNumerico::crear_primo(-7);
    $par_neg = NodoNumerico::crear_paralelo([$p_neg5, $p_neg7]);
    assert_no_nulo($par_neg, "crear_paralelo con deshaceres devuelve nodo");
    $pgrama_neg = $par_neg->pgrama();
    assert_verdadero($pgrama_neg[0] === 1, "p‑grama de paralelo de deshaceres empieza con 1");
    assert_verdadero(in_array(-5, $pgrama_neg) && in_array(-7, $pgrama_neg), "p‑grama contiene los primos negativos");
    
    // ══════════════════════════════════
    // 5. PRUEBAS DE ASCENSO Y DESCENSO
    // ══════════════════════════════════
    echo "<h3>5. Ascenso y descenso entre fases</h3>";
    
    echo "<b>5.1 Ascenso de secuencia:</b><br>";
    NodoElectrico::_fase($token, 'a');
    $p29 = NodoNumerico::crear_primo(29);
    $p31 = NodoNumerico::crear_primo(31);
    $secuencia = NodoNumerico::crear_numerico([$p29, $p31]);
    $matriz_original = $secuencia->identidad();
    
    NodoPrimo::inicializar_fase('b', 10);
    
    $primo_superior = $secuencia->ascender('b');
    assert_no_nulo($primo_superior, "ascender() devuelve NodoPrimo");
    assert_verdadero($primo_superior->es_primo(), "El nodo superior es primo");
    
    // Verificar que el paquete contiene factores y fase_origen
    $paquete_asc = $primo_superior->dato('abajo');
    assert_no_nulo($paquete_asc, "El primo superior tiene dato 'abajo'");
    assert_verdadero(isset($paquete_asc['factores']) && isset($paquete_asc['fase_origen']), "El paquete contiene factores y fase_origen");
    assert_verdadero($paquete_asc['factores'] === [29, 31], "Los factores guardados son [29, 31]");
    assert_verdadero($paquete_asc['fase_origen'] === 'a', "La fase origen guardada es 'a'");
    
    // Verificar que el nodo original sigue existiendo (no se liberó)
    assert_no_nulo($secuencia, "El nodo original sigue existiendo después de ascender");
    assert_verdadero($secuencia->pgrama('a') === [29, 31], "El nodo original conserva su p‑grama");
    
    NodoElectrico::_fase($token, 'a');
    
    echo "<b>5.2 Descenso de secuencia:</b><br>";
    $nodo_descendido = NodoNumerico::descender($primo_superior);
    
    assert_no_nulo($nodo_descendido, "descender() devuelve NodoNumerico");
    assert_verdadero($nodo_descendido->identidad()->es_igual($matriz_original), "La identidad reconstruida coincide con la original");
    
    $pgrama_desc = $nodo_descendido->pgrama();
    assert_verdadero($pgrama_desc === [29, 31], "El p‑grama descendido es [29, 31] (secuencia)");
    
    echo "<b>5.3 Ascenso de paralelo:</b><br>";
    NodoElectrico::_fase($token, 'a');
    $p37 = NodoNumerico::crear_primo(37);
    $p41 = NodoNumerico::crear_primo(41);
    $paralelo = NodoNumerico::crear_paralelo([$p37, $p41]);
    $matriz_paralelo = $paralelo->identidad();
    
    NodoPrimo::inicializar_fase('c', 10);
    $primo_paralelo = $paralelo->ascender('c');
    assert_no_nulo($primo_paralelo, "ascender() de paralelo devuelve NodoPrimo");
    
    $paquete_par = $primo_paralelo->dato('abajo');
    assert_verdadero($paquete_par['factores'][0] === 1, "El paquete del paralelo empieza con 1");
    assert_verdadero(isset($paquete_par['fase_origen']), "El paquete del paralelo contiene fase_origen");
    
    $paralelo_descendido = NodoNumerico::descender($primo_paralelo);
    assert_no_nulo($paralelo_descendido, "descender() de paralelo devuelve NodoNumerico");
    assert_verdadero($paralelo_descendido->identidad()->es_igual($matriz_paralelo), "Identidad de paralelo coincide");
    assert_verdadero($paralelo_descendido->pgrama()[0] === 1, "El nodo descendido es un paralelo (p‑grama empieza con 1)");
    
    echo "<b>5.4 Ascenso y descenso con deshaceres (marca -1):</b><br>";
    NodoElectrico::_fase($token, 'a');
    $p_neg29 = NodoNumerico::crear_primo(-29);
    $p_neg31 = NodoNumerico::crear_primo(-31);
    $sec_neg = NodoNumerico::crear_numerico([$p_neg29, $p_neg31]);
    $matriz_neg_original = $sec_neg->identidad();
    
    // Verificar que el p‑grama incluye la marca -1
    $pgrama_neg_asc = $sec_neg->pgrama();
    assert_verdadero($pgrama_neg_asc === [-1, -29, -31], "p‑grama de secuencia de deshaceres incluye marca -1");
    assert_verdadero($matriz_neg_original->determinante() > 0, "Producto de dos deshaceres tiene determinante positivo");
    
    NodoPrimo::inicializar_fase('d', 10);
    $primo_neg = $sec_neg->ascender('d');
    assert_no_nulo($primo_neg, "ascender() de secuencia de deshaceres devuelve NodoPrimo");
    
    $paquete_neg = $primo_neg->dato('abajo');
    assert_verdadero($paquete_neg['factores'] === [-1, -29, -31], "Los factores guardados incluyen marca -1");
    assert_verdadero($paquete_neg['fase_origen'] === 'a', "La fase origen guardada es 'a'");
    
    $sec_neg_descendida = NodoNumerico::descender($primo_neg);
    assert_no_nulo($sec_neg_descendida, "descender() de deshaceres devuelve nodo");
    assert_verdadero($sec_neg_descendida->identidad()->es_igual($matriz_neg_original), "Identidad de deshaceres coincide");
    // El p‑grama del nodo descendido debe incluir la marca -1 (porque crear_numerico la vuelve a añadir)
    assert_verdadero($sec_neg_descendida->pgrama() === [-1, -29, -31], "p‑grama de deshaceres descendido incluye marca -1");
    
    echo "<b>5.5 Error en ascenso sin p‑grama:</b><br>";
    NodoElectrico::_fase($token, 'a');
    $nodo_sin_factores = NodoNumerico::tomar_nodo_libre();
    try {
        $nodo_sin_factores->ascender('b');
        echo "❌ ascender() sin p‑grama no lanzó excepción<br>";
    } catch (\RuntimeException $e) {
        echo "✅ ascender() sin p‑grama lanza RuntimeException<br>";
    }
    NodoNumerico::devolver_nodo_libre($nodo_sin_factores);
    
    echo "<b>5.6 Error en descenso sin paquete:</b><br>";
    $primo_vacio = NodoPrimo::siguiente_primo_libre('a');
    try {
        NodoNumerico::descender($primo_vacio);
        echo "❌ descender() sin paquete no lanzó excepción<br>";
    } catch (\RuntimeException $e) {
        echo "✅ descender() sin paquete lanza RuntimeException<br>";
    }
    NodoPrimo::devolver_primo_libre($primo_vacio, 'a');
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.4 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";