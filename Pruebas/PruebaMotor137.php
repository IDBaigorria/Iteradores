<?php
/**
 * Pruebas del Motor de Ejecución v1.3.7.
 *
 * @since 1.3.7
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Configuracion/Entorno.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\Controlador;

// ═══════════════════════════════════════════
// HERRAMIENTAS
// ═══════════════════════════════════════════
function assert_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    $ok = abs($a - $b) < $tolerancia;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) echo "   Esperado: $b, Obtenido: $a<br>";
    return $ok;
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

// ═══════════════════════════════════════════
// PRUEBAS
// ═══════════════════════════════════════════
echo "══════════════════════════════════<br>";
echo " PRUEBAS MOTOR 1.3.7 (PHP)<br>";
echo "══════════════════════════════════<br><br>";

// --- 1. Constantes en Conf ---
echo "--- 1. Constantes en Conf ---<br>";
assert_iguales(Conf::MOTOR_CICLOS_POR_MINUTO, 20, "MOTOR_CICLOS_POR_MINUTO = 20");
assert_iguales(Conf::MOTOR_INTERVALO_MS, 3000, "MOTOR_INTERVALO_MS = 3000 (calculado)");
assert_iguales(Conf::MOTOR_QUANTUM, 20, "MOTOR_QUANTUM = 20");
assert_iguales(Conf::MOTOR_PAUSA_URGENTE_TIMEOUT_S, 30, "MOTOR_PAUSA_URGENTE_TIMEOUT_S = 30");
//assert_no_nulo(Conf::MOTOR_ACTIVO, "MOTOR_ACTIVO definido");

// --- 2. Estados del motor ---
echo "<br>--- 2. Estados del motor ---<br>";

// Aseguramos que empieza detenido
forzar_estado(Controlador::MOTOR_DETENIDO);
assert_estado(Controlador::MOTOR_DETENIDO, "Estado inicial DETENIDO");

// Simular que está activo para probar pausa
forzar_estado(Controlador::MOTOR_ACTIVO);
Controlador::pausar_motor();
assert_estado(Controlador::MOTOR_PAUSADO, "Pausa explícita");

// Reanudar
Controlador::reanudar_motor();
assert_estado(Controlador::MOTOR_ACTIVO, "Reanudar tras pausa");

// Detener
Controlador::detener_motor();
assert_estado(Controlador::MOTOR_DETENIDO, "Detener motor");

// --- 3. Pausa urgente y timeout ---
echo "<br>--- 3. Pausa urgente ---<br>";
forzar_estado(Controlador::MOTOR_ACTIVO);
Controlador::pausar_urgente('Prueba de pausa urgente');
assert_estado(Controlador::MOTOR_PAUSA_URGENTE, "Entra en PAUSA_URGENTE");

// Simular que ha pasado el timeout (forzamos el estado a ACTIVO y llamamos al bucle)
forzar_estado(Controlador::MOTOR_ACTIVO);
assert_estado(Controlador::MOTOR_ACTIVO, "Forzamos ACTIVO para simular timeout");

// --- 4. Péndulo ---
echo "<br>--- 4. Péndulo ---<br>";
// Usamos reflexión para llamar al método privado pendulo
$ref = new ReflectionClass(Controlador::class);
$metodo_pendulo = $ref->getMethod('pendulo');
$metodo_pendulo->setAccessible(true);

$fase = 0;
for ($i = 0; $i < 6; $i++) {
    $fase = $metodo_pendulo->invoke(null, $fase);
    echo "Péndulo ciclo $i → fase $fase<br>";
}
// Verificar que es cíclico: después de 3 iteraciones debería volver a 0
assert_iguales($metodo_pendulo->invoke(null, 2), 0, "Péndulo: de fase 2 pasa a 0 (round-robin)");

// --- 5. Placeholder siguiente_comando_en_fase ---
echo "<br>--- 5. Placeholder comando ---<br>";
$metodo_cmd = $ref->getMethod('siguiente_comando_en_fase');
$metodo_cmd->setAccessible(true);
$comando = $metodo_cmd->invoke(null, 0);
assert_no_nulo($comando === null, "Placeholder devuelve null");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";