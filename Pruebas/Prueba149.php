<?php
/**
 * Pruebas exhaustivas v1.4.9 – Verbos de Acción, AntenaAccion y Traducción
 *
 * Cubre: SenalAccion, AntenaAccion (singleton, multifase, emitir/recibir),
 *        AntenaTraduccionAccion, y constantes de verbos en Conf.
 *
 * @since 1.4.9
 * @version 1.4.9
 * @author Ignacio David Baigorria
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/NodoElectrico.php';
require_once __DIR__ . '/../Iteradores/SenalAccion.php';
require_once __DIR__ . '/../Iteradores/AntenaAccion.php';
require_once __DIR__ . '/../Controlador/AntenaTraduccionAccion.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Iteradores\SenalAccion;
use Iteradores\Iteradores\AntenaAccion;
use Iteradores\Controlador\AntenaTraduccionAccion;

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
echo " PRUEBAS 1.4.9 – VERBOS DE ACCIÓN Y ANTENAS<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════
    // 1. PRUEBAS DE CONSTANTES DE VERBOS
    // ═══════════════════════════════════
    echo "<h3>1. Constantes de Verbos en Conf</h3>";
    verificar_iguales(Conf::VERBO_CIERRE, 0, "VERBO_CIERRE = 0");
    verificar_iguales(Conf::VERBO_APRENDER, 1, "VERBO_APRENDER = 1");
    verificar_iguales(Conf::VERBO_EJECUTAR, 2, "VERBO_EJECUTAR = 2");
    verificar_iguales(Conf::VERBO_CONTROLAR, 3, "VERBO_CONTROLAR = 3");
    verificar_iguales(Conf::VERBO_CORREGIR, 4, "VERBO_CORREGIR = 4");
    verificar_iguales(Conf::VERBO_PREDECIR, 5, "VERBO_PREDECIR = 5");
    verificar_iguales(Conf::VERBO_IMAGINAR, 6, "VERBO_IMAGINAR = 6");
    verificar_iguales(Conf::VERBO_SUPERVISAR, 7, "VERBO_SUPERVISAR = 7");

    // ═══════════════════════════════════
    // 2. PRUEBAS DE SenalAccion
    // ═══════════════════════════════════
    echo "<h3>2. SenalAccion</h3>";

    echo "<b>2.1 Creación con verbo y fase:</b><br>";
    $senal = new SenalAccion(Conf::VERBO_APRENDER, 'Talamo:0');
    verificar_iguales($senal->verbo(), Conf::VERBO_APRENDER, "verbo() retorna VERBO_APRENDER");
    verificar_iguales($senal->fase_origen(), 'Talamo:0', "fase_origen() correcta");

    echo "<b>2.2 Verbo de cierre:</b><br>";
    $senal_cierre = new SenalAccion(Conf::VERBO_CIERRE, 'Origen:1');
    verificar_iguales($senal_cierre->verbo(), 0, "verbo() retorna 0 para cierre");

    echo "<b>2.3 Inmutabilidad:</b><br>";
    $verbo_inicial = $senal->verbo();
    $fase_inicial = $senal->fase_origen();
    // No hay setters, los getters devuelven lo mismo siempre
    verificar_iguales($senal->verbo(), $verbo_inicial, "verbo() no cambia");
    verificar_iguales($senal->fase_origen(), $fase_inicial, "fase_origen() no cambia");

    // ═══════════════════════════════════
    // 3. PRUEBAS DE AntenaAccion
    // ═══════════════════════════════════
    echo "<h3>3. AntenaAccion</h3>";

    // Singleton
    echo "<b>3.1 Singleton:</b><br>";
    $aa1 = AntenaAccion::antena();
    $aa2 = AntenaAccion::antena();
    verificar_verdadero($aa1 === $aa2, "antena() devuelve la misma instancia");

    // reiniciar solo en pruebas
    echo "<b>3.2 reiniciar:</b><br>";
    AntenaAccion::reiniciar();
    $aa3 = AntenaAccion::antena();
    verificar_falso($aa1 === $aa3, "Tras reiniciar, nueva instancia");
    AntenaAccion::reiniciar(); // limpiar para pruebas

    // Configurar fase
    $fase_test = 'Test:0';
    NodoElectrico::_fase($token, $fase_test);

    echo "<b>3.3 emitir con verbo:</b><br>";
    $antena = AntenaAccion::antena();
    $senal_emitida = $antena->emitir(Conf::VERBO_APRENDER);
    verificar_no_nulo($senal_emitida, "emitir() con verbo retorna SenalAccion");
    verificar_iguales($senal_emitida->verbo(), Conf::VERBO_APRENDER, "verbo en señal emitida es VERBO_APRENDER");
    verificar_iguales($senal_emitida->fase_origen(), $fase_test, "fase_origen es la fase actual");

    echo "<b>3.4 emitir sin verbo (acción actual):</b><br>";
    $senal_actual = $antena->emitir();
    verificar_no_nulo($senal_actual, "emitir() sin verbo retorna acción actual");
    verificar_verdadero($senal_actual === $senal_emitida, "La acción actual es la última emitida");

    echo "<b>3.5 emitir con verbo de cierre:</b><br>";
    $senal_cierre_emitida = $antena->emitir(Conf::VERBO_CIERRE);
    verificar_iguales($senal_cierre_emitida->verbo(), 0, "emitir(CIERRE) crea señal con verbo 0");

    echo "<b>3.6 recibir señal de acción:</b><br>";
    $senal_recibida = new SenalAccion(Conf::VERBO_CONTROLAR, 'Otro:1');
    $verbo_retornado = $antena->recibir($senal_recibida);
    verificar_iguales($verbo_retornado, Conf::VERBO_CONTROLAR, "recibir() retorna el verbo correcto");
    // La acción actual se actualiza
    $nueva_actual = $antena->emitir();
    verificar_verdadero($nueva_actual === $senal_recibida, "Acción actual actualizada a la recibida");

    echo "<b>3.7 recibir verbo de cierre:</b><br>";
    $senal_cierre = new SenalAccion(Conf::VERBO_CIERRE, 'Otro:1');
    $verbo_cierre = $antena->recibir($senal_cierre);
    verificar_iguales($verbo_cierre, 0, "recibir() de cierre retorna 0");
    // Acción actual se actualiza a señal de cierre
    verificar_verdadero($antena->emitir() === $senal_cierre, "Acción actual es la señal de cierre");

    echo "<b>3.8 Multifase (cambio de fase):</b><br>";
    $otra_fase = 'Otra:1';
    NodoElectrico::_fase($token, $otra_fase);
    // emitir en otra fase
    $senal_otra = $antena->emitir(Conf::VERBO_EJECUTAR);
    verificar_iguales($senal_otra->fase_origen(), $otra_fase, "Señal en otra fase tiene su fase_origen");
    verificar_iguales($senal_otra->verbo(), Conf::VERBO_EJECUTAR, "Verbo correcto en otra fase");
    // volver a la fase original y verificar que la acción actual sigue intacta
    NodoElectrico::_fase($token, $fase_test);
    $senal_fase_orig = $antena->emitir();
    verificar_verdadero($senal_fase_orig === $senal_cierre, "Acción actual de fase original no fue alterada");

    // ═══════════════════════════════════
    // 4. PRUEBAS DE AntenaTraduccionAccion
    // ═══════════════════════════════════
    echo "<h3>4. AntenaTraduccionAccion</h3>";

    echo "<b>4.1 Constructor y traducción a señal:</b><br>";
    $traductor = new AntenaTraduccionAccion('Controlador:accion');
    $senal_trad = $traductor->traducir_a_senal(Conf::VERBO_PREDECIR);
    verificar_no_nulo($senal_trad, "traducir_a_senal() retorna SenalAccion");
    verificar_iguales($senal_trad->verbo(), Conf::VERBO_PREDECIR, "Verbo correcto");
    verificar_iguales($senal_trad->fase_origen(), 'Controlador:accion', "Fase origen coincide con constructor");

    echo "<b>4.2 Traducción a verbo:</b><br>";
    $senal_rec = new SenalAccion(Conf::VERBO_IMAGINAR, 'Origen:2');
    $verbo = $traductor->traducir_a_verbo($senal_rec);
    verificar_iguales($verbo, Conf::VERBO_IMAGINAR, "traducir_a_verbo() extrae verbo correcto");

    echo "<b>4.3 Traducción de cierre:</b><br>";
    $senal_cierre_trad = $traductor->traducir_a_senal(Conf::VERBO_CIERRE);
    verificar_iguales($senal_cierre_trad->verbo(), 0, "Traducción de CIERRE a señal con verbo 0");
    $verbo_cierre_trad = $traductor->traducir_a_verbo($senal_cierre_trad);
    verificar_iguales($verbo_cierre_trad, 0, "Traducción inversa de cierre a 0");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.4.9 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
// Mostrar posibles errores/alertas acumulados
AntenaAccion::imprimir_alertas();
AntenaAccion::imprimir_errores();