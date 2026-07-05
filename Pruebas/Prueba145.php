<?php
/**
 * Pruebas exhaustivas v1.4.5 – Framework Iteradores (PHP)
 *
 * Cubre: Senal, Antena, ProcesadorDeDominio y secuencia_de_matrices() en NodoNumerico.
 *
 * @since 1.4.5
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Nodos/NodoNumerico.php';
require_once __DIR__ . '/../Nodos/NodoPrimo.php';
require_once __DIR__ . '/../Nodos/NodoParalelo.php';
require_once __DIR__ . '/../Controlador/Senal.php';
require_once __DIR__ . '/../Controlador/Antena.php';
require_once __DIR__ . '/../Controlador/ProcesadorDeDominio.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\NodoParalelo;
use Iteradores\Controlador\Senal;
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
echo " PRUEBAS 1.4.5 - Framework Iteradores (PHP)<br>";
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
    $senal->agregar_matriz($m_a);
    $senal->agregar_matriz($m_b);
    $senal->agregar_matriz($m_c);
    assert_verdadero($senal->longitud_cruda() === 3, "Señal con 3 matrices tiene longitud 3");
    
    echo "<b>1.2 Consumo sin patrón:</b><br>";
    $senal->consumir(1);
    assert_verdadero($senal->longitud_no_consumida() === 2, "Tras consumir 1, no consumidas = 2");
    assert_verdadero($senal->obtener_indice_comido() === 1, "Índice comido = 1");
    
    $elementos = $senal->obtener_elementos_procesados();
    assert_verdadero(count($elementos) === 1, "Un elemento procesado");
    assert_verdadero($elementos[0] instanceof Matriz2x2, "Elemento procesado sin patrón es Matriz2x2");
    assert_verdadero($elementos[0]->es_igual($m_a), "La matriz consumida es la primera (M(2))");
    
    echo "<b>1.3 Consumo con patrón:</b><br>";
    // Crear un NodoPrimo real que sí tiene p‑grama y secuencia de matrices
    $nodo_patron = NodoNumerico::crear_primo(7);
    $senal->consumir(2, $nodo_patron);
    
    assert_verdadero($senal->longitud_no_consumida() === 0, "Tras consumir 2 más, no consumidas = 0");
    $elementos = $senal->obtener_elementos_procesados();
    assert_verdadero(count($elementos) === 2, "Ahora hay 2 elementos procesados");
    assert_verdadero($elementos[1] === $nodo_patron, "El segundo elemento procesado es el NodoNumerico patrón");
    
    echo "<b>1.4 Consumo excesivo (manejo de error):</b><br>";
    $senal_err = new Senal();
    $senal_err->agregar_matriz(Matriz2x2::inicial());
    $senal_err->consumir(5); // intenta consumir más de lo disponible
    assert_verdadero($senal_err->obtener_indice_comido() === 0, "Tras intento de consumo excesivo, índice sigue en 0");
    
    echo "<b>1.5 obtener_no_consumidas:</b><br>";
    $no_cons_1 = $senal->obtener_no_consumidas();
    assert_verdadero(count($no_cons_1) === 0, "obtener_no_consumidas() vacío al haber consumido todo");
    
    $senal_fresca = new Senal([Matriz2x2::inicial(), Matriz2x2::crear_prima(11)]);
    $no_cons_2 = $senal_fresca->obtener_no_consumidas();
    assert_verdadero(count($no_cons_2) === 2, "obtener_no_consumidas() devuelve todas sin consumir");
    
    echo "<b>1.6 obtener_crudas() y obtener_elementos_procesados():</b><br>";
    $crudas = $senal->obtener_crudas();
    assert_verdadero(count($crudas) === 3, "obtener_crudas() devuelve las 3 matrices originales");
    
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
    $comp_hacer = NodoNumerico::crear_numerico([$p2, $p7]); // 2 componentes, 2 es primo
    $sec_hacer = $comp_hacer->secuencia_de_matrices();
    assert_verdadero(count($sec_hacer) === 2, "secuencia compuesta(2,7) tiene 2 matrices");
    assert_verdadero($sec_hacer[0]->es_igual(Matriz2x2::crear_prima(2)), "primera es M(2)");
    assert_verdadero($sec_hacer[1]->es_igual(Matriz2x2::crear_prima(7)), "segunda es M(7)");
    
    echo "<b>2.5 Secuencia con deshacer (marca -1 omitida):</b><br>";
    $p_neg3 = NodoNumerico::crear_primo(-3);
    $p_neg5 = NodoNumerico::crear_primo(-5);
    $comp_deshacer = NodoNumerico::crear_numerico([$p_neg3, $p_neg5]); // 2 componentes, 2 es primo
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
    
    // Para las pruebas de antena, usaremos la fase 10 para evitar colisiones con otras fases.
    $fase_antena = 10;
    NodoElectrico::_fase($token, $fase_antena);  // Todos los nodos creados a continuación tendrán identidad en fase 10
    
    echo "<b>3.1 Registro de patrón válido e inválido:</b><br>";
    $antena = new Antena($fase_antena);
    
    // Nodo con identidad real (NodoPrimo con p‑grama)
    $patron1 = NodoNumerico::crear_primo(11);
    $antena->registrar_patron($patron1);
    assert_verdadero(count($antena->obtener_patrones()) === 1, "Patrón (primo 11) se registra");
    assert_verdadero($antena->obtener_patrones()[0] === $patron1, "El patrón registrado es el correcto");
    
    // Nodo sin identidad real (nodo libre) no debe registrarse
    $nodo_malo = NodoNumerico::tomar_nodo_libre();
    $antena->registrar_patron($nodo_malo);
    assert_verdadero(count($antena->obtener_patrones()) === 1, "Nodo sin identidad real no se registra (sigue 1 patrón)");
    NodoNumerico::devolver_nodo_libre($nodo_malo);
    
    echo "<b>3.2 Captura simple con patrón de 1 matriz:</b><br>";
    $senal_a = new Senal([Matriz2x2::crear_prima(11)]);
    $capturo_a = $antena->intentar_capturar($senal_a);
    assert_verdadero($capturo_a, "Antena captura M(11)");
    assert_verdadero($senal_a->obtener_indice_comido() === 1, "Índice avanzó 1");
    $elem_a = $senal_a->obtener_elementos_procesados();
    assert_verdadero($elem_a[0] === $patron1, "Elemento procesado es el patrón esperado");
    
    echo "<b>3.3 Captura con patrón de longitud 2:</b><br>";
    $p13 = NodoNumerico::crear_primo(13);
    $p17 = NodoNumerico::crear_primo(17);
    $comp_13_17 = NodoNumerico::crear_numerico([$p13, $p17]); // 2 componentes, 2 es primo
    
    $antena2 = new Antena($fase_antena);
    $antena2->registrar_patron($comp_13_17);
    
    $senal_b = new Senal([Matriz2x2::crear_prima(13), Matriz2x2::crear_prima(17)]);
    $capturo_b = $antena2->intentar_capturar($senal_b);
    assert_verdadero($capturo_b, "Antena captura [M(13), M(17)]");
    assert_verdadero($senal_b->obtener_indice_comido() === 2, "Índice avanzó 2");
    
    echo "<b>3.4 Prioridad por longitud (voracidad):</b><br>";
    $p19 = NodoNumerico::crear_primo(19);
    $comp_13_17_19 = NodoNumerico::crear_numerico([$p13, $p17, $p19]); // 3 componentes, 3 es primo
    
    $antena3 = new Antena($fase_antena);
    // Registramos primero el corto, luego el largo
    $antena3->registrar_patron($comp_13_17);       // longitud 2
    $antena3->registrar_patron($comp_13_17_19);    // longitud 3
    
    $senal_voraz = new Senal([
        Matriz2x2::crear_prima(13),
        Matriz2x2::crear_prima(17),
        Matriz2x2::crear_prima(19)
    ]);
    $capturo_voraz = $antena3->intentar_capturar($senal_voraz);
    assert_verdadero($capturo_voraz, "Antena captura en modo voraz");
    assert_verdadero($senal_voraz->obtener_indice_comido() === 3, "Consumió 3 matrices (la secuencia más larga)");
    $elem_voraz = $senal_voraz->obtener_elementos_procesados();
    assert_verdadero($elem_voraz[0] === $comp_13_17_19, "El patrón usado fue el de longitud 3");
    
    echo "<b>3.5 Sin coincidencia:</b><br>";
    $senal_no_coincide = new Senal([Matriz2x2::crear_prima(23)]);
    $capturo_no = $antena3->intentar_capturar($senal_no_coincide);
    assert_falso($capturo_no, "Antena no captura M(23) (sin patrón)");
    assert_verdadero($senal_no_coincide->obtener_indice_comido() === 0, "Índice sin cambios");
    
    echo "<b>3.6 Patrón con secuencia de deshacer:</b><br>";
    $p_neg13 = NodoNumerico::crear_primo(-13);
    $p_neg17 = NodoNumerico::crear_primo(-17);
    $comp_neg_13_17 = NodoNumerico::crear_numerico([$p_neg13, $p_neg17]); // 2 componentes, 2 es primo
    
    $antena_deshacer = new Antena($fase_antena);
    $antena_deshacer->registrar_patron($comp_neg_13_17);
    
    $senal_neg = new Senal([Matriz2x2::crear_negativa_prima(13), Matriz2x2::crear_negativa_prima(17)]);
    $capturo_neg = $antena_deshacer->intentar_capturar($senal_neg);
    assert_verdadero($capturo_neg, "Antena captura secuencia de deshacer [M(-13), M(-17)]");
    assert_verdadero($senal_neg->obtener_indice_comido() === 2, "Índice avanzó 2 en señal de deshacer");
    
    // ══════════════════════════════════
    // 4. PRUEBAS DE ProcesadorDeDominio
    // ══════════════════════════════════
    echo "<h3>4. ProcesadorDeDominio</h3>";
    
    // Para estas pruebas, usaremos fases 20 y 30
    $fase1 = 20;
    $fase2 = 30;
    
    echo "<b>4.1 Creación y nombre:</b><br>";
    $proc = new ProcesadorDeDominio('test');
    assert_verdadero($proc->obtener_nombre_dominio() === 'test', "Nombre del dominio correcto");
    
    echo "<b>4.2 Procesamiento con múltiples fases:</b><br>";
    // Mini‑lenguaje:
    // Fase 20: letras 'a' = primo 2, 'b' = primo 3
    // Fase 30: palabra "ab" = composición de 2 y 3
    
    // Crear primos en fase 20
    NodoElectrico::_fase($token, $fase1);
    $p_a = NodoNumerico::crear_primo(2);
    $p_b = NodoNumerico::crear_primo(3);
    
    // Crear compuesto en fase 30
    NodoElectrico::_fase($token, $fase2);
    $comp_ab = NodoNumerico::crear_numerico([$p_a, $p_b]); // 2 componentes, 2 es primo
    
    $proc->registrar_patron($p_a, $fase1);
    $proc->registrar_patron($p_b, $fase1);
    $proc->registrar_patron($comp_ab, $fase2);
    
    // Señal "ab" → debe capturarse como palabra compuesta (fase 30)
    $senal_ab = new Senal([Matriz2x2::crear_prima(2), Matriz2x2::crear_prima(3)]);
    $proc->procesar($senal_ab);
    
    assert_verdadero($senal_ab->obtener_indice_comido() === 2, "Procesador consumió 2 matrices ('ab')");
    $elem_ab = $senal_ab->obtener_elementos_procesados();
    assert_verdadero(count($elem_ab) === 1, "Procesador produjo 1 elemento procesado");
    assert_verdadero($elem_ab[0] === $comp_ab, "El elemento procesado es el patrón 'ab'");
    
    echo "<b>4.3 Señal sin capturas:</b><br>";
    $senal_sin = new Senal([Matriz2x2::crear_prima(5)]); // primo 5 no tiene patrón
    $proc->procesar($senal_sin);
    assert_verdadero($senal_sin->obtener_indice_comido() === 0, "Procesador no consumió nada");
    
    echo "<b>4.4 Capturas múltiples (reinicio voraz):</b><br>";
    // Señal "abab" = [M(2), M(3), M(2), M(3)]
    $senal_abab = new Senal([
        Matriz2x2::crear_prima(2),
        Matriz2x2::crear_prima(3),
        Matriz2x2::crear_prima(2),
        Matriz2x2::crear_prima(3)
    ]);
    $proc->procesar($senal_abab);
    assert_verdadero($senal_abab->obtener_indice_comido() === 4, "Procesador consumió 4 matrices ('abab')");
    $elem_abab = $senal_abab->obtener_elementos_procesados();
    assert_verdadero(count($elem_abab) === 2, "Procesador produjo 2 elementos");
    assert_verdadero($elem_abab[0] === $comp_ab, "Primer elemento es 'ab'");
    assert_verdadero($elem_abab[1] === $comp_ab, "Segundo elemento es 'ab'");
    
    echo "<b>4.5 Orden de captura con mezcla de fases:</b><br>";
    // Señal "aba" = [M(2), M(3), M(2)]
    $senal_aba = new Senal([
        Matriz2x2::crear_prima(2),
        Matriz2x2::crear_prima(3),
        Matriz2x2::crear_prima(2)
    ]);
    $proc->procesar($senal_aba);
    assert_verdadero($senal_aba->obtener_indice_comido() === 3, "Procesador consumió 3 matrices ('aba')");
    $elem_aba = $senal_aba->obtener_elementos_procesados();
    assert_verdadero(count($elem_aba) === 2, "Procesador produjo 2 elementos");
    assert_verdadero($elem_aba[0] === $comp_ab, "Primer elemento es 'ab' (fase 30)");
    assert_verdadero($elem_aba[1] === $p_a, "Segundo elemento es 'a' (fase 20)");
    
    // ══════════════════════════════════
    // 5. PRUEBAS DE INTEGRACIÓN
    // ══════════════════════════════════
    echo "<h3>5. Integración: flujo 'tom' simplificado</h3>";
    
    // Fase 40: caracteres, Fase 50: palabra
    $fase_caracter = 40;
    $fase_palabra  = 50;
    
    // Crear primos en fase 40
    NodoElectrico::_fase($token, $fase_caracter);
    $p_t = NodoNumerico::crear_primo(29); // t
    $p_o = NodoNumerico::crear_primo(31); // o
    $p_m = NodoNumerico::crear_primo(37); // m

    // Crear compuesto en fase 50
    NodoElectrico::_fase($token, $fase_palabra);
    $nodo_tom = NodoNumerico::crear_numerico([$p_t, $p_o, $p_m]);
    assert_no_nulo($nodo_tom, "crear_numerico con 3 componentes ('tom') devuelve nodo");
    
    $proc_texto = new ProcesadorDeDominio('texto:entrada');
    
    // Registrar caracteres en fase 40
    $proc_texto->registrar_patron($p_t, $fase_caracter);
    $proc_texto->registrar_patron($p_o, $fase_caracter);
    $proc_texto->registrar_patron($p_m, $fase_caracter);
    
    // Registrar palabra en fase 50
    $proc_texto->registrar_patron($nodo_tom, $fase_palabra);
    
    // Señal: 't','o','m'
    $senal_tom = new Senal([
        Matriz2x2::crear_prima(29),
        Matriz2x2::crear_prima(31),
        Matriz2x2::crear_prima(37)
    ]);
    
    $proc_texto->procesar($senal_tom);
    
    assert_verdadero($senal_tom->obtener_indice_comido() === 3, "Procesador consumió toda la señal 'tom' (3 matrices)");
    $elem_tom = $senal_tom->obtener_elementos_procesados();
    assert_verdadero(count($elem_tom) === 1, "Procesador dejó 1 elemento procesado: 'tom'");
    assert_verdadero($elem_tom[0] === $nodo_tom, "El elemento es el patrón 'tom'");
    
    echo "<b>5.1 generar_senal_de_salida:</b><br>";
    $senal_salida = $senal_tom->generar_senal_de_salida($fase_caracter);
    $crudas_salida = $senal_salida->obtener_crudas();
    assert_verdadero(count($crudas_salida) === 1, "Señal de salida tiene 1 matriz (la de 'tom')");
    assert_verdadero($crudas_salida[0]->es_igual($nodo_tom->identidad($fase_caracter)), "La matriz es la identidad del patrón 'tom' en fase caracter");
    
    echo "<b>5.2 Sin fases registradas:</b><br>";
    $proc_vacio = new ProcesadorDeDominio('vacio');
    $senal_vacia = new Senal([Matriz2x2::inicial()]);
    $proc_vacio->procesar($senal_vacia);
    assert_verdadero($senal_vacia->obtener_indice_comido() === 0, "Procesador sin fases no consume nada");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.5 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";

NodoElectrico::imprimir_alertas();
NodoElectrico::imprimir_errores();