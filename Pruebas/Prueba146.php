<?php
/**
 * Pruebas exhaustivas v1.4.5 + Integración del ciclo de salida v1.4.6
 *
 * Cubre: Senal, Antena, ProcesadorDeDominio, secuencia_de_matrices(),
 *        AplanadorSenal y flujo completo de bytes → señal → bytes.
 *
 * @since 1.4.5
 * @version 1.4.6
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Controlador/MapeoBytesMatrices.php';   // ← ruta corregida
require_once __DIR__ . '/../Nodos/NodoNumerico.php';
require_once __DIR__ . '/../Nodos/NodoPrimo.php';
require_once __DIR__ . '/../Nodos/NodoParalelo.php';
require_once __DIR__ . '/../Controlador/Senal.php';
require_once __DIR__ . '/../Controlador/Antena.php';
require_once __DIR__ . '/../Controlador/ProcesadorDeDominio.php';
require_once __DIR__ . '/../Controlador/AplanadorSenal.php';


use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;
use Iteradores\Controlador\Senal;
use Iteradores\Controlador\AplanadorSenal;
use Iteradores\Controlador\MapeoBytesMatrices;                  // ← namespace corregido
use Iteradores\Controlador\Antena;
use Iteradores\Controlador\ProcesadorDeDominio;

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
echo " PRUEBAS 1.4.5 + INTEGRACIÓN 1.4.6<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ══════════════════════════════════
    // 1. PRUEBAS DE Senal
    // ══════════════════════════════════
    echo "<h3>1. Señal (Senal)</h3>";
    
    echo "<b>1.1 Creación y agregado de matrices:</b><br>";
    $senal = new Senal();
    assert_verdadero($senal->longitud_cruda() === 0, "Señal nueva tiene longitud 0");
    
    $m_a = Matriz2x2::crear_prima(2);
    $m_b = Matriz2x2::crear_prima(3);
    $m_c = Matriz2x2::crear_prima(5);
    $senal->_matriz($m_a);
    $senal->_matriz($m_b);
    $senal->_matriz($m_c);
    assert_verdadero($senal->longitud_cruda() === 3, "Señal con 3 matrices tiene longitud 3");
    
    echo "<b>1.2 Consumo sin patrón:</b><br>";
    $senal->consumir(1);
    assert_verdadero($senal->longitud_no_consumida() === 2, "Tras consumir 1, no consumidas = 2");
    assert_verdadero($senal->indice_consumido() === 1, "Índice comido = 1");
    
    $elementos = $senal->elementos_procesados();
    assert_verdadero(count($elementos) === 1, "Un elemento procesado");
    assert_verdadero($elementos[0] instanceof Matriz2x2, "Elemento procesado sin patrón es Matriz2x2");
    assert_verdadero($elementos[0]->es_igual($m_a), "La matriz consumida es la primera (M(2))");
    
    echo "<b>1.3 Consumo con patrón:</b><br>";
    $nodo_patron = NodoNumerico::crear_primo(97);
    $senal->consumir(2, $nodo_patron);
    
    assert_verdadero($senal->longitud_no_consumida() === 0, "Tras consumir 2 más, no consumidas = 0");
    $elementos = $senal->elementos_procesados();
    assert_verdadero(count($elementos) === 2, "Ahora hay 2 elementos procesados");
    assert_verdadero($elementos[1] === $nodo_patron, "El segundo elemento procesado es el NodoNumerico patrón");
    
    echo "<b>1.4 Consumo excesivo (manejo de error):</b><br>";
    $senal_err = new Senal();
    $senal_err->_matriz(Matriz2x2::inicial());
    $senal_err->consumir(5);
    assert_verdadero($senal_err->indice_consumido() === 0, "Tras intento de consumo excesivo, índice sigue en 0");
    
    echo "<b>1.5 no_consumidas:</b><br>";
    $no_cons_1 = $senal->no_consumidas();
    assert_verdadero(count($no_cons_1) === 0, "no_consumidas() vacío al haber consumido todo");
    
    $senal_fresca = new Senal([Matriz2x2::inicial(), Matriz2x2::crear_prima(11)]);
    $no_cons_2 = $senal_fresca->no_consumidas();
    assert_verdadero(count($no_cons_2) === 2, "no_consumidas() devuelve todas sin consumir");
    
    echo "<b>1.6 crudas() y elementos_procesados():</b><br>";
    $crudas = $senal->crudas();
    assert_verdadero(count($crudas) === 3, "crudas() devuelve las 3 matrices originales");
    
    // ══════════════════════════════════
    // 2. PRUEBAS DE secuencia_de_matrices()
    // ══════════════════════════════════
    echo "<h3>2. Método secuencia_de_matrices() en NodoNumerico</h3>";
    
    NodoElectrico::_fase($token, 'a');
    
    echo "<b>2.1 NodoPrimo positivo:</b><br>";
    $p2 = NodoNumerico::crear_primo(2);
    $sec_p2 = $p2->secuencia_de_matrices();
    assert_verdadero(count($sec_p2) === 1, "secuencia de primo(2) tiene 1 matriz");
    assert_verdadero($sec_p2[0]->es_igual(Matriz2x2::crear_prima(2)), "secuencia de primo(2) = [M(2)]");
    
    echo "<b>2.2 NodoPrimo negativo (deshacer):</b><br>";
    $p_neg = NodoNumerico::crear_primo(-2);
    $sec_p_neg = $p_neg->secuencia_de_matrices();
    assert_verdadero(count($sec_p_neg) === 1, "secuencia de primo(-2) tiene 1 matriz");
    assert_verdadero($sec_p_neg[0]->es_igual(Matriz2x2::crear_negativa_prima(2)), "secuencia de primo(-2) = [M(-2)]");
    
    echo "<b>2.3 NodoParalelo:</b><br>";
    $p3 = NodoNumerico::crear_primo(3);
    $p5 = NodoNumerico::crear_primo(5);
    $paralelo = NodoNumerico::crear_paralelo([$p3, $p5]);
    $sec_par = $paralelo->secuencia_de_matrices();
    assert_verdadero(count($sec_par) === 2, "secuencia de paralelo(3,5) tiene 2 matrices (omite marca 1)");
    assert_verdadero($sec_par[0]->es_igual(Matriz2x2::crear_prima(3)), "primera matriz es M(3)");
    assert_verdadero($sec_par[1]->es_igual(Matriz2x2::crear_prima(5)), "segunda matriz es M(5)");
    
    echo "<b>2.4 Secuencia compuesta (hacer):</b><br>";
    $p7 = NodoNumerico::crear_primo(7);
    $comp_hacer = NodoNumerico::crear_numerico([$p2, $p7]);
    $sec_hacer = $comp_hacer->secuencia_de_matrices();
    assert_verdadero(count($sec_hacer) === 2, "secuencia compuesta(2,7) tiene 2 matrices");
    assert_verdadero($sec_hacer[0]->es_igual(Matriz2x2::crear_prima(2)), "primera es M(2)");
    assert_verdadero($sec_hacer[1]->es_igual(Matriz2x2::crear_prima(7)), "segunda es M(7)");
    
    echo "<b>2.5 Secuencia con deshacer (marca -1 omitida):</b><br>";
    $p_neg3 = NodoNumerico::crear_primo(-3);
    $p_neg5 = NodoNumerico::crear_primo(-5);
    $comp_deshacer = NodoNumerico::crear_numerico([$p_neg3, $p_neg5]);
    $sec_deshacer = $comp_deshacer->secuencia_de_matrices();
    assert_verdadero(count($sec_deshacer) === 2, "secuencia de deshacer omite marca -1: tiene 2 matrices");
    assert_verdadero($sec_deshacer[0]->es_igual(Matriz2x2::crear_negativa_prima(3)), "primera es M(-3)");
    assert_verdadero($sec_deshacer[1]->es_igual(Matriz2x2::crear_negativa_prima(5)), "segunda es M(-5)");
    
    echo "<b>2.6 Nodo sin p‑grama:</b><br>";
    $nodo_vacio = NodoNumerico::tomar_nodo_libre();
    $sec_vacia = $nodo_vacio->secuencia_de_matrices();
    assert_verdadero(count($sec_vacia) === 0, "secuencia de nodo sin p‑grama es []");
    NodoNumerico::devolver_nodo_libre($nodo_vacio);
    
    // ══════════════════════════════════
    // 3. PRUEBAS DE Antena
    // ══════════════════════════════════
    echo "<h3>3. Antena</h3>";
    
    $fase_antena = 10;
    NodoElectrico::_fase($token, $fase_antena);
    
    echo "<b>3.1 Registro de patrón válido e inválido:</b><br>";
    $antena = new Antena($fase_antena);
    
    $patron1 = NodoNumerico::crear_primo(101);
    $antena->_patron($patron1);
    assert_verdadero(count($antena->patrones()) === 1, "Patrón (primo 101) se registra");
    assert_verdadero($antena->patrones()[0] === $patron1, "El patrón registrado es el correcto");
    
    $nodo_malo = NodoNumerico::tomar_nodo_libre();
    $antena->_patron($nodo_malo);
    assert_verdadero(count($antena->patrones()) === 1, "Nodo sin identidad real no se registra");
    NodoNumerico::devolver_nodo_libre($nodo_malo);
    
    echo "<b>3.2 Captura simple con patrón de 1 matriz:</b><br>";
    $senal_a = new Senal([Matriz2x2::crear_prima(101)]);
    $capturo_a = $antena->intentar_capturar($senal_a);
    assert_verdadero($capturo_a, "Antena captura M(101)");
    assert_verdadero($senal_a->indice_consumido() === 1, "Índice avanzó 1");
    $elem_a = $senal_a->elementos_procesados();
    assert_verdadero($elem_a[0] === $patron1, "Elemento procesado es el patrón esperado");
    
    echo "<b>3.3 Captura con patrón de longitud 2:</b><br>";
    $p103 = NodoNumerico::crear_primo(103);
    $p107 = NodoNumerico::crear_primo(107);
    $comp_103_107 = NodoNumerico::crear_numerico([$p103, $p107]);
    
    $antena2 = new Antena($fase_antena);
    $antena2->_patron($comp_103_107);
    
    $senal_b = new Senal([Matriz2x2::crear_prima(103), Matriz2x2::crear_prima(107)]);
    $capturo_b = $antena2->intentar_capturar($senal_b);
    assert_verdadero($capturo_b, "Antena captura [M(103), M(107)]");
    assert_verdadero($senal_b->indice_consumido() === 2, "Índice avanzó 2");
    
    echo "<b>3.4 Prioridad por longitud (voracidad):</b><br>";
    $p109 = NodoNumerico::crear_primo(109);
    $comp_103_107_109 = NodoNumerico::crear_numerico([$p103, $p107, $p109]);
    
    $antena3 = new Antena($fase_antena);
    $antena3->_patron($comp_103_107);
    $antena3->_patron($comp_103_107_109);
    
    $senal_voraz = new Senal([
        Matriz2x2::crear_prima(103),
        Matriz2x2::crear_prima(107),
        Matriz2x2::crear_prima(109)
    ]);
    assert_verdadero($antena3->intentar_capturar($senal_voraz), "Antena captura en modo voraz");
    assert_verdadero($senal_voraz->indice_consumido() === 3, "Consumió 3 matrices");
    $elem_voraz = $senal_voraz->elementos_procesados();
    assert_verdadero($elem_voraz[0] === $comp_103_107_109, "El patrón usado fue el de longitud 3");
    
    echo "<b>3.5 Sin coincidencia:</b><br>";
    $senal_no_coincide = new Senal([Matriz2x2::crear_prima(113)]);
    $capturo_no = $antena3->intentar_capturar($senal_no_coincide);
    assert_falso($capturo_no, "Antena no captura M(113) (sin patrón)");
    assert_verdadero($senal_no_coincide->indice_consumido() === 0, "Índice sin cambios");
    
    echo "<b>3.6 Patrón con secuencia de deshacer:</b><br>";
    $p_neg103 = NodoNumerico::crear_primo(-103);
    $p_neg107 = NodoNumerico::crear_primo(-107);
    $comp_neg_103_107 = NodoNumerico::crear_numerico([$p_neg103, $p_neg107]);
    
    $antena_deshacer = new Antena($fase_antena);
    $antena_deshacer->_patron($comp_neg_103_107);
    
    $senal_neg = new Senal([Matriz2x2::crear_negativa_prima(103), Matriz2x2::crear_negativa_prima(107)]);
    assert_verdadero($antena_deshacer->intentar_capturar($senal_neg), "Antena captura deshacer [M(-103), M(-107)]");
    assert_verdadero($senal_neg->indice_consumido() === 2, "Índice avanzó 2");
    
    // ══════════════════════════════════
    // 4. PRUEBAS DE ProcesadorDeDominio
    // ══════════════════════════════════
    echo "<h3>4. ProcesadorDeDominio</h3>";
    
    $fase1 = 20;
    $fase2 = 30;
    
    echo "<b>4.1 Creación y nombre:</b><br>";
    $proc = new ProcesadorDeDominio('test', 'entrada');
    assert_verdadero($proc->medio() === 'test', "Nombre del medio correcto");
    assert_verdadero($proc->direccion() === 'entrada', "Dirección correcta");
    
    echo "<b>4.2 Procesamiento con múltiples fases:</b><br>";
    NodoElectrico::_fase($token, $fase1);
    $p_a = NodoNumerico::crear_primo(211);
    $p_b = NodoNumerico::crear_primo(223);
    
    NodoElectrico::_fase($token, $fase2);
    $comp_ab = NodoNumerico::crear_numerico([$p_a, $p_b]);
    
    $proc->_patron($p_a, $fase1);
    $proc->_patron($p_b, $fase1);
    $proc->_patron($comp_ab, $fase2);
    
    $senal_ab = new Senal([Matriz2x2::crear_prima(211), Matriz2x2::crear_prima(223)]);
    $proc->procesar($senal_ab);
    
    assert_verdadero($senal_ab->indice_consumido() === 2, "Procesador consumió 2 matrices ('ab')");
    $elem_ab = $senal_ab->elementos_procesados();
    assert_verdadero(count($elem_ab) === 1, "Procesador produjo 1 elemento procesado");
    assert_verdadero($elem_ab[0] === $comp_ab, "El elemento procesado es el patrón 'ab'");
    
    echo "<b>4.3 Señal sin capturas:</b><br>";
    $senal_sin = new Senal([Matriz2x2::crear_prima(227)]);
    $proc->procesar($senal_sin);
    // Ahora (el aprendizaje trivial lo consume):
    assert_verdadero($senal_sin->indice_consumido() === 1, "Procesador consumió 1 matriz (aprendizaje trivial)");
    
    echo "<b>4.4 Capturas múltiples (reinicio voraz):</b><br>";
    $senal_abab = new Senal([
        Matriz2x2::crear_prima(211),
        Matriz2x2::crear_prima(223),
        Matriz2x2::crear_prima(211),
        Matriz2x2::crear_prima(223)
    ]);
    $proc->procesar($senal_abab);
    assert_verdadero($senal_abab->indice_consumido() === 4, "Procesador consumió 4 matrices ('abab')");
    $elem_abab = $senal_abab->elementos_procesados();
    assert_verdadero(count($elem_abab) === 2, "Procesador produjo 2 elementos");
    assert_verdadero($elem_abab[0] === $comp_ab, "Primer elemento es 'ab'");
    assert_verdadero($elem_abab[1] === $comp_ab, "Segundo elemento es 'ab'");
    
    echo "<b>4.5 Orden de captura con mezcla de fases:</b><br>";
    $senal_aba = new Senal([
        Matriz2x2::crear_prima(211),
        Matriz2x2::crear_prima(223),
        Matriz2x2::crear_prima(211)
    ]);
    $proc->procesar($senal_aba);
    assert_verdadero($senal_aba->indice_consumido() === 3, "Procesador consumió 3 matrices ('aba')");
    $elem_aba = $senal_aba->elementos_procesados();
    assert_verdadero(count($elem_aba) === 2, "Procesador produjo 2 elementos");
    assert_verdadero($elem_aba[0] === $comp_ab, "Primer elemento es 'ab' (fase 30)");
    assert_verdadero($elem_aba[1] === $p_a, "Segundo elemento es 'a' (fase 20)");
    
    // ══════════════════════════════════
    // 5. PRUEBAS DE INTEGRACIÓN 1.4.5 (tom)
    // ══════════════════════════════════
    echo "<h3>5. Integración: flujo 'tom' simplificado</h3>";
    
    $fase_car = 40;
    $fase_pal = 50;
    
    NodoElectrico::_fase($token, $fase_car);
    $p_t = NodoNumerico::crear_primo(307);
    $p_o = NodoNumerico::crear_primo(311);
    $p_m = NodoNumerico::crear_primo(313);

    NodoElectrico::_fase($token, $fase_pal);
    $nodo_tom = NodoNumerico::crear_numerico([$p_t, $p_o, $p_m]);
    assert_no_nulo($nodo_tom, "crear_numerico con 3 componentes ('tom') devuelve nodo");
    
    $proc_texto = new ProcesadorDeDominio('texto', 'entrada');
    $proc_texto::recibir_token($token); 
    $proc_texto->_patron($p_t, $fase_car);
    $proc_texto->_patron($p_o, $fase_car);
    $proc_texto->_patron($p_m, $fase_car);
    $proc_texto->_patron($nodo_tom, $fase_pal);
    
    $senal_tom = new Senal([
        Matriz2x2::crear_prima(307),
        Matriz2x2::crear_prima(311),
        Matriz2x2::crear_prima(313)
    ]);
    
    $proc_texto->procesar($senal_tom);
    
    assert_verdadero($senal_tom->indice_consumido() === 3, "Procesador consumió 3 matrices ('tom')");
    $elem_tom = $senal_tom->elementos_procesados();
    assert_verdadero(count($elem_tom) === 1, "Procesador dejó 1 elemento: 'tom'");
    assert_verdadero($elem_tom[0] === $nodo_tom, "El elemento es el patrón 'tom'");
    
    echo "<b>5.1 senal_de_salida:</b><br>";
    $senal_salida = $senal_tom->senal_de_salida($fase_car);
    $crudas_salida = $senal_salida->crudas();
    assert_verdadero(count($crudas_salida) === 1, "Señal de salida tiene 1 matriz");
    assert_verdadero($crudas_salida[0]->es_igual($nodo_tom->identidad()), "Es la identidad del patrón 'tom'");
    
    echo "<b>5.2 Sin fases registradas:</b><br>";
    $proc_vacio = new ProcesadorDeDominio('vacio', 'entrada');
    $senal_vacia = new Senal([Matriz2x2::inicial()]);
    $proc_vacio->procesar($senal_vacia);
    assert_verdadero($senal_vacia->indice_consumido() === 1, "Procesador consumió 1 matriz (aprendizaje trivial)");

    // ══════════════════════════════════
    // 6. PRUEBA DE INTEGRACIÓN DEL CICLO DE SALIDA 1.4.6
    // ══════════════════════════════════
    echo "<h3>6. Integración del ciclo de salida 1.4.6</h3>";

    echo "<b>6.1 Flujo completo: bytes → dominio vacío → aprendizaje trivial → bytes</b><br>";

    $texto_original = "fin";

    // Convertir bytes a señal
    $senal = Senal::desde_bytes($texto_original);
    assert_verdadero($senal->longitud_cruda() === 3, "Señal cruda tiene 3 matrices (f, i, n)");

    // Procesar con un dominio vacío (sin antenas) – activa el aprendizaje trivial
    $proc_texto = new ProcesadorDeDominio('texto', 'entrada');
    $proc_texto::recibir_token($token);
    $proc_texto->procesar($senal);

    // El aprendizaje trivial debe haber consumido las 3 matrices y creado 3 primos con 'abajo'
    assert_verdadero($senal->indice_consumido() === 3, "Procesador consumió 3 matrices");
    $elem = $senal->elementos_procesados();
    assert_verdadero(count($elem) === 3, "Señal dejó 3 elementos procesados");
    assert_verdadero($elem[0] instanceof NodoPrimo, "Elemento 0 es NodoPrimo");
    assert_verdadero($elem[1] instanceof NodoPrimo, "Elemento 1 es NodoPrimo");
    assert_verdadero($elem[2] instanceof NodoPrimo, "Elemento 2 es NodoPrimo");

    // Cambiar a la fase donde se guardaron los datos 'abajo' (fase 0 del dominio)
    $fase_anterior = NodoElectrico::fase();
    NodoElectrico::_fase($token, 'texto:entrada:0');

    // Aplanar y convertir a bytes
    $bytes_salida = Senal::a_bytes($senal);
    $ok = assert_verdadero($bytes_salida === $texto_original, "Bytes de salida coinciden con la entrada original ('fin')");

    // Restaurar la fase anterior
    NodoElectrico::_fase($token, $fase_anterior);

    if ($ok) {
        echo "<br><b>Resultado final:</b><br>";
        echo "Entrada: " . htmlspecialchars($texto_original) . "<br>";
        echo "Salida:  " . htmlspecialchars($bytes_salida) . "<br>";
        echo "<br>✅ Ciclo de salida 1.4.6 verificado correctamente.<br>";
    }
 });

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.5 + INTEGRACIÓN 1.4.6 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
NodoNumerico::imprimir_alertas();
NodoNumerico::imprimir_errores();