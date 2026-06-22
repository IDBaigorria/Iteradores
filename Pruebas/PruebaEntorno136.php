<?php
/**
 * Pruebas exhaustivas de Entorno v1.3.6 (ubicación).
 *
 * @since 1.3.6
 */
require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Configuracion/Entorno.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;

// ═══════════════════════════════════════
// HERRAMIENTAS
// ═══════════════════════════════════════
function assert_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    $ok = abs($a - $b) < $tolerancia;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) echo "   Esperado: $b, Obtenido: $a (tol: $tolerancia)<br>";
    return $ok;
}

function assert_no_nulo($v, string $mensaje): bool {
    $ok = $v !== null;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    return $ok;
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS ENTORNO 1.3.6 (PHP)<br>";
echo "══════════════════════════════════<br><br>";

// --- 1. Constantes en Conf ---
echo "--- 1. Constantes en Conf ---<br>";
assert_iguales(Conf::LATITUD_PREDETERMINADA, -34.0, "LATITUD_PREDETERMINADA es -34.0");
assert_iguales(Conf::LONGITUD_PREDETERMINADA, -64.0, "LONGITUD_PREDETERMINADA es -64.0");
assert_no_nulo(Conf::GEOLOCALIZACION_URL, "GEOLOCALIZACION_URL existe");

// --- 2. Caché estática (sin sesión) ---
echo "<br>--- 2. Caché estática ---<br>";
// Primera llamada: debería obtener coordenadas (probablemente IP local → fallback)
$coords1 = Entorno::coordenadas();
assert_no_nulo($coords1, "Primera llamada devuelve array");
assert_iguales($coords1['latitud'], -34.0, "Fallback latitud (IP local)");
assert_iguales($coords1['longitud'], -64.0, "Fallback longitud (IP local)");

// Segunda llamada: debe devolver lo mismo de la caché estática
$coords2 = Entorno::coordenadas();
assert_iguales($coords1['latitud'], $coords2['latitud'], "Cache: misma latitud");
assert_iguales($coords1['longitud'], $coords2['longitud'], "Cache: misma longitud");

// --- 3. Simular sesión ---
echo "<br>--- 3. Sesión ---<br>";
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$clave_sesion = Conf::PREFIJO_SESSION . 'coordenadas';
$_SESSION[$clave_sesion ] = ['latitud' => 40.4168, 'longitud' => -3.7038];
// Forzar limpieza de caché estática para que lea sesión
$ref = new ReflectionClass(Entorno::class);
$prop = $ref->getProperty('_coordenadas_cacheadas');
$prop->setAccessible(true);
$prop->setValue(null, null);

$coords_sesion = Entorno::coordenadas();
assert_iguales($coords_sesion['latitud'], 40.4168, "Sesión latitud Madrid");
assert_iguales($coords_sesion['longitud'], -3.7038, "Sesión longitud Madrid");
// Limpiar sesión y caché para no afectar otras pruebas
unset($_SESSION[$clave_sesion ]);
$prop->setValue(null, null);

// --- 4. Placeholder escuchar_cambios ---
echo "<br>--- 4. escuchar_cambios ---<br>";
$callback_invocado = false;
Entorno::escuchar_cambios(function($lat, $lon) use (&$callback_invocado) {
    $callback_invocado = true;
});
assert_iguales($callback_invocado, false, "escuchar_cambios NO invoca callback en PHP");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";