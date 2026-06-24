<?php
/**
 * Pruebas de colas de comandos por fase v1.3.8.
 *
 * @since 1.3.8
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;

function assert_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    // Igualdad estricta primero (funciona con strings, números, etc.)
    if ($a === $b) {
        echo "✅ " . $mensaje . "<br>";
        return true;
    }
    // Si no son estrictamente iguales, intentar comparación numérica con tolerancia
    if (is_numeric($a) && is_numeric($b)) {
        $ok = abs($a - $b) < $tolerancia;
        echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
        if (!$ok) echo "   Esperado: $b, Obtenido: $a (tolerancia: $tolerancia)<br>";
        return $ok;
    }
    // Si no son numéricos y no son estrictamente iguales, es un fallo
    echo "❌ " . $mensaje . "<br>";
    echo "   Esperado: $b, Obtenido: $a<br>";
    return false;
}

function assert_no_nulo($v, string $mensaje): bool {
    $ok = $v !== null;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

// Limpiar colas antes de empezar
$ref = new ReflectionClass(Controlador::class);
$prop = $ref->getProperty('colas_comandos');
$prop->setAccessible(true);
$prop->setValue(null, []);

echo "══════════════════════════════════<br>";
echo " PRUEBAS COLAS 1.3.8 (PHP)<br>";
echo "══════════════════════════════════<br><br>";

// --- 1. Encolar y extraer ---
echo "--- 1. Encolar y extraer ---<br>";
$ejecutado = false;
$cmd = function() use (&$ejecutado) { $ejecutado = true; return 'ok'; };
Controlador::encolar_comando_en_fase('0', $cmd);
$extraido = $ref->getMethod('siguiente_comando_en_fase');
$extraido->setAccessible(true);
$comando = $extraido->invoke(null, '0');
assert_no_nulo($comando, "Se extrae comando encolado");
$resultado = $comando();
assert_iguales($resultado, 'ok', "Comando se ejecuta correctamente");
assert_iguales($ejecutado, true, "Efecto del comando confirmado");

// --- 2. Cola vacía ---
echo "<br>--- 2. Cola vacía ---<br>";
$vacio = $extraido->invoke(null, '0');
assert_iguales($vacio, null, "Cola vacía devuelve null");

// --- 3. Múltiples fases ---
echo "<br>--- 3. Múltiples fases ---<br>";
$contador = 0;
Controlador::encolar_comando_en_fase('0', function() use (&$contador) { $contador++; });
Controlador::encolar_comando_en_fase('0', function() use (&$contador) { $contador++; });
Controlador::encolar_comando_en_fase('1', function() use (&$contador) { $contador += 10; });
// Extraer de fase 0
$cmd1 = $extraido->invoke(null, '0');
$cmd1();
$cmd2 = $extraido->invoke(null, '0');
$cmd2();
assert_iguales($contador, 2, "Dos comandos en fase 0 incrementan contador a 2");
// La fase 0 debería desaparecer tras vaciarse
$cmd3 = $extraido->invoke(null, '0');
assert_iguales($cmd3, null, "Fase 0 ya no existe tras vaciarse");

// Extraer de fase 1
$cmd4 = $extraido->invoke(null, '1');
$cmd4();
assert_iguales($contador, 12, "Comando en fase 1 suma 10");

// --- 4. Péndulo dinámico ---
echo "<br>--- 4. Péndulo dinámico ---<br>";
$pendulo = $ref->getMethod('pendulo');
$pendulo->setAccessible(true);
// Añadir comandos en fases desordenadas
Controlador::encolar_comando_en_fase('b', function() {});
Controlador::encolar_comando_en_fase('a', function() {});
Controlador::encolar_comando_en_fase('c', function() {});
$fase = $pendulo->invoke(null, null);
assert_iguales($fase, 'a', "Primera fase (ordenada): a");
$fase = $pendulo->invoke(null, $fase);
assert_iguales($fase, 'b', "Segunda fase: b");
$fase = $pendulo->invoke(null, $fase);
assert_iguales($fase, 'c', "Tercera fase: c");
$fase = $pendulo->invoke(null, $fase);
assert_iguales($fase, 'a', "Vuelta a empezar: a");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";