<?php
/**
 * Pruebas de la versión 1.3.9 (colas, ejecutar_comando, modo dominio, reversa).
 *
 * @since 1.3.9
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;

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

function assert_estado(string $esperado, string $mensaje): bool {
    $ref = new ReflectionClass(Controlador::class);
    $prop = $ref->getProperty('estado_motor');
    $prop->setAccessible(true);
    $actual = $prop->getValue();
    $ok = $actual === $esperado;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . " (estado: $actual)<br>";
    return $ok;
}

function forzar_estado(string $nuevo_estado): void {
    $ref = new ReflectionClass(Controlador::class);
    $prop = $ref->getProperty('estado_motor');
    $prop->setAccessible(true);
    $prop->setValue(null, $nuevo_estado);
}

// Limpiar colas y estado antes de empezar
$ref = new ReflectionClass(Controlador::class);
$prop_colas = $ref->getProperty('colas_comandos');
$prop_colas->setAccessible(true);
$prop_colas->setValue(null, []);
$prop_dominio = $ref->getProperty('dominio_actual');
$prop_dominio->setAccessible(true);
$prop_dominio->setValue(null, null);
forzar_estado(Controlador::MOTOR_DETENIDO);

echo "══════════════════════════════════<br>";
echo " PRUEBAS 1.3.9 (PHP)<br>";
echo "══════════════════════════════════<br><br>";

// --- 1. Encolar con nombre de comando y ejecutar ---
echo "--- 1. Encolar y ejecutar comandos reales ---<br>";
Controlador::encolar_comando_en_fase('0', 'depuracion:imprimir', '--errores');
// Extraer y ejecutar manualmente para verificar
$extraido = $ref->getMethod('siguiente_comando_en_fase');
$extraido->setAccessible(true);
$entrada = $extraido->invoke(null, '0');
assert_no_nulo($entrada, "Comando encolado extraído");
assert_iguales($entrada[0], 'depuracion:imprimir', "Nombre del comando correcto");

// --- 2. Modo dominio ---
echo "<br>--- 2. Modo dominio ---<br>";
// Limpiar colas nuevamente
$prop_colas->setValue(null, []);
Controlador::encolar_comando_en_fase('html:0', 'dominio:leer_byte');
Controlador::encolar_comando_en_fase('html:1', 'dominio:leer_byte');
Controlador::encolar_comando_en_fase('talamo:0', 'dominio:escribir_byte');

Controlador::activar_dominio('html');
// Verificar que solo se ven fases de html
$pendulo = $ref->getMethod('pendulo');
$pendulo->setAccessible(true);
$fase = $pendulo->invoke(null, null);
assert_iguales($fase, 'html:0', "Modo html: primera fase html:0");
$fase = $pendulo->invoke(null, $fase);
assert_iguales($fase, 'html:1', "Modo html: segunda fase html:1");
$fase = $pendulo->invoke(null, $fase);
assert_iguales($fase, 'html:0', "Modo html: vuelve a html:0");

Controlador::desactivar_dominio();
// Ahora debería ver todas las fases
$fase = $pendulo->invoke(null, null);
assert_iguales($fase, 'html:0', "Global: primera fase ordenada");

// --- 3. Detención automática ---
echo "<br>--- 3. Detención automática ---<br>";
$prop_colas->setValue(null, []);
forzar_estado(Controlador::MOTOR_ACTIVO);
// Simular un ciclo del motor sin comandos → debe pasar a DETENIDO
$bucle = $ref->getMethod('bucle_motor');
$bucle->setAccessible(true);
$bucle->invoke(null);
assert_estado(Controlador::MOTOR_DETENIDO, "Motor se detiene automáticamente al no haber comandos");

// --- 4. Reversa del motor ---
echo "<br>--- 4. Reversa del motor ---<br>";
$prop_colas->setValue(null, []); // limpiar colas
forzar_estado(Controlador::MOTOR_ACTIVO);

// Encolar un comando con reversa
Controlador::encolar_comando_en_fase('0', 'prueba:crear_nodo', 'Nodo de prueba');

// Ejecutar un ciclo del motor (ejecutará el comando y luego se detendrá)
$bucle = $ref->getMethod('bucle_motor');
$bucle->setAccessible(true);
$bucle->invoke(null);

// Verificar que el motor se detuvo (porque se vaciaron las colas)
assert_estado(Controlador::MOTOR_DETENIDO, "Motor detenido automáticamente");

// Ahora deshacer con el motor detenido
$resultado_reversa = Controlador::deshacer_motor();
assert_no_nulo($resultado_reversa, "deshacer_motor devuelve un resultado");
// El resultado debe contener "eliminado por reversa" (o el mensaje que devuelva la reversa)
$ok_reversa = is_string($resultado_reversa) && strpos($resultado_reversa, 'eliminado por reversa') !== false;
echo ($ok_reversa ? "✅ " : "❌ ") . "La reversa eliminó el nodo correctamente<br>";
if (!$ok_reversa) echo "   Resultado obtenido: $resultado_reversa<br>";

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";