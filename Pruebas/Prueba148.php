<?php
/**
 * Pruebas exhaustivas v1.4.8 – Nuevas Antenas y Señal
 *
 * Cubre: Senal, AntenaComun, AntenaDeMarcado, AntenaTraduccion,
 *        aprendizaje trivial bidireccional, multifase, validación de marcado,
 *        y almacenamiento de señales completas.
 *
 * @since 1.4.8
 * @version 1.4.8
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Matriz2x2.php';
require_once __DIR__ . '/../Nodos/NodoNumerico.php';
require_once __DIR__ . '/../Nodos/NodoPrimo.php';
require_once __DIR__ . '/../Nodos/NodoParalelo.php';
require_once __DIR__ . '/../Iteradores/Senal.php';
require_once __DIR__ . '/../Iteradores/AntenaComun.php';
require_once __DIR__ . '/../Iteradores/AntenaDeMarcado.php';
require_once __DIR__ . '/../Controlador/AntenaTraduccion.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Iteradores\Senal;
use Iteradores\Iteradores\AntenaComun;
use Iteradores\Iteradores\AntenaDeMarcado;
use Iteradores\Controlador\AntenaTraduccion;
use Iteradores\Configuracion\Entorno;

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
echo " PRUEBAS 1.4.8 – NUEVAS ANTENAS Y SEÑAL<br>";
echo "══════════════════════════════════<br><br>";

// Inicializar caché de primos para las traducciones
NodoNumerico::inicializar_cache_primos();

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════════════
    // 1. PRUEBAS DE Senal
    // ═══════════════════════════════════════════
    echo "<h3>1. Señal (Senal)</h3>";

    echo "<b>1.1 Creación con valores predeterminados:</b><br>";
    $senal_vacia = new Senal();
    verificar_iguales($senal_vacia->longitud(), 0, "Señal vacía tiene longitud 0");
    verificar_iguales($senal_vacia->fase_origen(), '', "Fase origen vacía por defecto");
    verificar_falso($senal_vacia->marcado(), "Marcado es false por defecto");

    echo "<b>1.2 Creación con parámetros:</b><br>";
    $m1 = Matriz2x2::crear_prima(2);
    $m2 = Matriz2x2::crear_prima(3);
    $senal = new Senal([$m1, $m2], 'Talamo:0', true);
    verificar_iguales($senal->longitud(), 2, "Longitud 2 con matrices iniciales");
    verificar_iguales($senal->fase_origen(), 'Talamo:0', "Fase origen correcta");
    verificar_verdadero($senal->marcado(), "Marcado true cuando se indica");

    echo "<b>1.3 Acceso a matrices (copia defensiva):</b><br>";
    $matrices = $senal->matrices();
    verificar_iguales(count($matrices), 2, "matrices() devuelve array de 2");
    verificar_verdadero($matrices[0]->es_igual($m1), "Primera matriz correcta");
    verificar_verdadero($matrices[1]->es_igual($m2), "Segunda matriz correcta");
    // Verificar que modificar el array devuelto no afecta a la señal
    $matrices[0] = Matriz2x2::inicial();
    verificar_verdadero($senal->matrices()[0]->es_igual($m1), "La señal original no se modifica");

    echo "<b>1.4 Señal común (marcado false):</b><br>";
    $senal_comun = new Senal([$m1], 'Test:1', false);
    verificar_falso($senal_comun->marcado(), "Señal común tiene marcado false");

    echo "<b>1.5 Inmutabilidad tras construcción:</b><br>";
    // La señal no tiene setters públicos, así que verificamos que los getters devuelvan siempre lo mismo
    $senal_fija = new Senal([Matriz2x2::crear_prima(5)], 'Fijo:0', true);
    verificar_iguales($senal_fija->longitud(), 1, "Longitud inicial 1");
    verificar_iguales($senal_fija->fase_origen(), 'Fijo:0', "Fase inicial Fijo:0");
    verificar_verdadero($senal_fija->marcado(), "Marcado inicial true");

    // ═══════════════════════════════════════════
    // 2. PRUEBAS DE AntenaComun
    // ═══════════════════════════════════════════
    echo "<h3>2. AntenaComun</h3>";

    // Singleton
    echo "<b>2.1 Singleton:</b><br>";
    $ac1 = AntenaComun::antena();
    $ac2 = AntenaComun::antena();
    verificar_verdadero($ac1 === $ac2, "antena() devuelve la misma instancia");

    // reiniciar solo en pruebas
    echo "<b>2.2 reiniciar en entorno de pruebas:</b><br>";
    // Asumimos entorno de pruebas por cómo se ejecutan estas pruebas
    AntenaComun::reiniciar();
    $ac3 = AntenaComun::antena();
    verificar_falso($ac1 === $ac3, "Tras reiniciar, se obtiene una nueva instancia");
    // Limpiamos la instancia para las pruebas siguientes
    AntenaComun::reiniciar();

    // Configurar fase para las pruebas de recepción/emisión
    $fase_test = 'Test:0';
    NodoElectrico::_fase($token, $fase_test);

    echo "<b>2.3 Recibir señal común (aprendizaje trivial):</b><br>";
    $antena_comun = AntenaComun::antena();
    $matriz_a = Matriz2x2::crear_prima(101);
    $senal_a = new Senal([$matriz_a], 'Talamo:0', false);  // señal común, fase origen Talamo:0
    $nodo_a = $antena_comun->recibir($senal_a);
    verificar_no_nulo($nodo_a, "recibir() devuelve nodo tras aprendizaje trivial");
    verificar_verdadero($nodo_a->es_primo(), "El nodo aprendido es un NodoPrimo");
    verificar_verdadero($nodo_a->dato('contenido') instanceof Senal, "El contenido del nodo es una Señal");
    verificar_verdadero($nodo_a->dato('contenido') === $senal_a, "El contenido es la misma señal recibida");

    echo "<b>2.4 Recibir la misma matriz de nuevo (sin modificar contenido):</b><br>";
    $senal_a2 = new Senal([$matriz_a], 'Talamo:0', false); // misma matriz, distinta instancia de señal
    $nodo_a2 = $antena_comun->recibir($senal_a2);
    verificar_verdadero($nodo_a2 === $nodo_a, "Devuelve el mismo nodo para la misma matriz");
    verificar_verdadero($nodo_a2->dato('contenido') === $senal_a, "El contenido sigue siendo la señal original (no se actualiza)");

    echo "<b>2.5 Recibir señal marcada (debe ignorar):</b><br>";
    $senal_marcada = new Senal([$matriz_a], 'Talamo:0', true);
    $nodo_ignorado = $antena_comun->recibir($senal_marcada);
    verificar_falso($nodo_ignorado !== null, "recibir() retorna null para señal marcada");

    echo "<b>2.6 Emitir NodoPrimo (aprendido):</b><br>";
    $senal_emitida = $antena_comun->emitir($nodo_a, 'Talamo:1');
    verificar_no_nulo($senal_emitida, "emitir() con NodoPrimo devuelve señal");
    verificar_verdadero($senal_emitida->marcado() === false, "La señal emitida es común");
    verificar_verdadero($senal_emitida === $senal_a, "La señal emitida es exactamente la almacenada en contenido");

    echo "<b>2.7 Emitir nodo compuesto (no primo):</b><br>";
    // Crear un nodo compuesto (secuencia) a partir de primos
    $p_x = NodoNumerico::crear_primo(307);
    $p_y = NodoNumerico::crear_primo(311);
    $nodo_compuesto = NodoNumerico::crear_numerico([$p_x, $p_y]);
    verificar_no_nulo($nodo_compuesto, "Nodo compuesto creado");
    verificar_falso($nodo_compuesto->es_primo(), "El nodo compuesto no es primo");

    $senal_comp = $antena_comun->emitir($nodo_compuesto, 'Talamo:1');
    verificar_no_nulo($senal_comp, "emitir() con nodo compuesto devuelve señal");
    verificar_iguales($senal_comp->longitud(), 1, "Señal de una matriz (identidad del compuesto)");
    verificar_verdadero($senal_comp->matrices()[0]->es_igual($nodo_compuesto->identidad()), "La matriz es la identidad del compuesto");
    verificar_falso($nodo_compuesto->dato('contenido') instanceof Senal, "El nodo compuesto NO tiene contenido guardado");

    echo "<b>2.8 Multifase (cambio de fase global):</b><br>";
    // Cambiar a otra fase
    $otra_fase = 'Otra:1';
    NodoElectrico::_fase($token, $otra_fase);
    $matriz_b = Matriz2x2::crear_prima(202);
    $senal_b = new Senal([$matriz_b], 'Talamo:0', false);
    $nodo_b = $antena_comun->recibir($senal_b);
    verificar_no_nulo($nodo_b, "Aprendizaje en otra fase crea nodo");
    verificar_verdadero($nodo_b !== $nodo_a, "Nodo distinto al de la fase anterior");
    // Volver a la fase original y verificar que el nodo a sigue ahí
    NodoElectrico::_fase($token, $fase_test);
    $nodo_a_otravez = $antena_comun->recibir(new Senal([$matriz_a], 'Talamo:0', false));
    verificar_verdadero($nodo_a_otravez === $nodo_a, "En la fase original, el nodo aprendido sigue siendo el mismo");

    // Limpiar singleton para siguientes pruebas (opcional, no afecta)
    AntenaComun::reiniciar();

    // ═══════════════════════════════════════════
    // 3. PRUEBAS DE AntenaDeMarcado
    // ═══════════════════════════════════════════
    echo "<h3>3. AntenaDeMarcado</h3>";

    echo "<b>3.1 Singleton:</b><br>";
    $adm1 = AntenaDeMarcado::antena();
    $adm2 = AntenaDeMarcado::antena();
    verificar_verdadero($adm1 === $adm2, "antena() devuelve la misma instancia");

    AntenaDeMarcado::reiniciar();
    $adm3 = AntenaDeMarcado::antena();
    verificar_falso($adm1 === $adm3, "Tras reiniciar, nueva instancia");

    // Volver a la fase test
    NodoElectrico::_fase($token, $fase_test);

    echo "<b>3.2 Recibir señal marcada (aprendizaje trivial):</b><br>";
    $antena_marcado = AntenaDeMarcado::antena();
    $senal_m1 = new Senal([Matriz2x2::crear_prima(501)], 'Origen:0', true);
    $nodo_m1 = $antena_marcado->recibir($senal_m1);
    verificar_no_nulo($nodo_m1, "recibir() señal marcada crea nodo marcador");
    verificar_verdadero($nodo_m1->es_primo(), "Es un NodoPrimo");
    verificar_verdadero($nodo_m1->dato('contenido') === $senal_m1, "Contenido es la señal completa");

    echo "<b>3.3 Recibir otra señal en el mismo par (sobrescribir contenido):</b><br>";
    $senal_m2 = new Senal([Matriz2x2::crear_prima(502), Matriz2x2::crear_prima(503)], 'Origen:0', true);
    $nodo_m2 = $antena_marcado->recibir($senal_m2);
    verificar_verdadero($nodo_m2 === $nodo_m1, "Mismo nodo marcador para el mismo par");
    verificar_verdadero($nodo_m2->dato('contenido') === $senal_m2, "Contenido actualizado a la nueva señal");

    echo "<b>3.4 Recibir señal común (ignorar):</b><br>";
    $senal_comun = new Senal([Matriz2x2::crear_prima(601)], 'Origen:0', false);
    $nodo_ignorado_m = $antena_marcado->recibir($senal_comun);
    verificar_falso($nodo_ignorado_m !== null, "Señal común devuelve null");

    echo "<b>3.5 Emitir desde nodo marcador:</b><br>";
    $senal_emitida_m = $antena_marcado->emitir($nodo_m2, 'Destino:0');
    verificar_no_nulo($senal_emitida_m, "emitir() devuelve señal");
    verificar_verdadero($senal_emitida_m === $senal_m2, "La señal emitida es la misma que la última almacenada");
    verificar_verdadero($senal_emitida_m->marcado(), "La señal emitida es marcada");

    echo "<b>3.6 Emitir sin contenido (error):</b><br>";
    $nodo_primo_vacio = NodoPrimo::siguiente_primo_libre();
    $nodo_primo_vacio->_dato(null, 'contenido'); // borrar contenido
    $sin_contenido = $antena_marcado->emitir($nodo_primo_vacio, 'Destino:1');
    verificar_falso($sin_contenido !== null, "Devuelve null si no hay contenido");

    echo "<b>3.7 Multifase:</b><br>";
    NodoElectrico::_fase($token, $otra_fase);
    $senal_m3 = new Senal([Matriz2x2::crear_prima(701)], 'Origen:0', true);
    $nodo_m3 = $antena_marcado->recibir($senal_m3);
    verificar_no_nulo($nodo_m3, "En otra fase, crea nodo distinto");
    verificar_verdadero($nodo_m3 !== $nodo_m2, "Nodo diferente al de la fase anterior");
    // Volver y verificar que el original sigue
    NodoElectrico::_fase($token, $fase_test);
    $nodo_m2_otravez = $antena_marcado->recibir(new Senal([Matriz2x2::crear_prima(501)], 'Origen:0', true));
    verificar_verdadero($nodo_m2_otravez === $nodo_m2, "En fase original, mismo nodo");

    AntenaDeMarcado::reiniciar();

    // ═══════════════════════════════════════════
    // 4. PRUEBAS DE AntenaTraduccion
    // ═══════════════════════════════════════════
    echo "<h3>4. AntenaTraduccion</h3>";

    echo "<b>4.1 Constructor y origen:</b><br>";
    $at = new AntenaTraduccion('Controlador:traduccion_entrada');
    // No hay getter para origen, lo comprobamos indirectamente en las señales emitidas

    echo "<b>4.2 emitir() – bytes a señal:</b><br>";
    $bytes_entrada = "AB";
    $senal_trad = $at->emitir($bytes_entrada);
    verificar_no_nulo($senal_trad, "emitir() devuelve señal");
    verificar_verdadero($senal_trad->marcado(), "La señal es de tipo marcado");
    verificar_iguales($senal_trad->fase_origen(), 'Controlador:traduccion_entrada', "Fase origen coincide con el constructor");
    verificar_iguales($senal_trad->longitud(), 2, "Longitud 2 para 2 bytes");
    // Verificar que las matrices corresponden a los bytes 'A' y 'B' (65 y 66)
    $primo_a = Conf::PRIMOS_PRECARGADOS[ord('A')];
    $primo_b = Conf::PRIMOS_PRECARGADOS[ord('B')];
    verificar_verdadero($senal_trad->matrices()[0]->es_igual(Matriz2x2::crear_prima($primo_a)), "Primera matriz es la del byte A");
    verificar_verdadero($senal_trad->matrices()[1]->es_igual(Matriz2x2::crear_prima($primo_b)), "Segunda matriz es la del byte B");

    echo "<b>4.3 recibir() – señal a bytes:</b><br>";
    $bytes_salida = $at->recibir($senal_trad);
    verificar_iguales($bytes_salida, 'AB', "recibir() decodifica correctamente a 'AB'");

    echo "<b>4.4 Traducción completa con caracteres no imprimibles:</b><br>";
    $bytes_ext = chr(0) . chr(255);
    $senal_ext = $at->emitir($bytes_ext);
    verificar_iguales($senal_ext->longitud(), 2, "Longitud correcta para bytes extremos");
    $bytes_ext_salida = $at->recibir($senal_ext);
    verificar_iguales($bytes_ext_salida, $bytes_ext, "Recupera bytes extremos correctamente");

    echo "<b>4.5 Emisión de cadena vacía:</b><br>";
    $senal_vacia = $at->emitir('');
    verificar_iguales($senal_vacia->longitud(), 0, "Señal vacía para cadena vacía");

    echo "<b>4.6 Recepción de señal vacía:</b><br>";
    $bytes_vacios = $at->recibir(new Senal([], '', true));
    verificar_iguales($bytes_vacios, '', "Cadena vacía para señal sin matrices");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.8 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
// Mostrar posibles errores/alertas acumulados
NodoNumerico::imprimir_alertas();
NodoNumerico::imprimir_errores();