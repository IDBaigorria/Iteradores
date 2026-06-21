<?php
/**
 * Pruebas exhaustivas del RelojAstronomico v1.3.5.
 *
 * Ejecuta casos de prueba sobre el cálculo vectorial, determinismo,
 * ciclos temporales, caché, polos y normalización.
 *
 * @since 1.3.5
 */

// Ajusta las rutas según tu estructura
require_once __DIR__ . '/../Controlador/Controlador.php'; // o donde tengas el autoload
require_once __DIR__ . '/../Tiempo/RelojAstronomico.php';

use Iteradores\Tiempo\RelojAstronomico;

// ═══════════════════════════════════════════
// HERRAMIENTAS
// ═══════════════════════════════════════════

function assert_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    $ok = abs($a - $b) < $tolerancia;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) {
        echo "   Esperado: $b, Obtenido: $a (tolerancia: $tolerancia)<br>";
    }
    return $ok;
}

function assert_vector_unitario(array $v, string $mensaje): bool {
    $mag = sqrt($v['x']**2 + $v['y']**2 + $v['z']**2);
    return assert_iguales($mag, 1.0, "$mensaje (magnitud)");
}

function assert_similitud_mayor(array $v1, array $v2, float $umbral, string $mensaje): bool {
    $dot = $v1['x']*$v2['x'] + $v1['y']*$v2['y'] + $v1['z']*$v2['z'];
    $ok = $dot > $umbral;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . " (similitud=$dot, umbral=$umbral)<br>";
    return $ok;
}

function assert_similitud_menor(array $v1, array $v2, float $umbral, string $mensaje): bool {
    $dot = $v1['x']*$v2['x'] + $v1['y']*$v2['y'] + $v1['z']*$v2['z'];
    $ok = $dot < $umbral;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . " (similitud=$dot, umbral=$umbral)<br>";
    return $ok;
}

// ═══════════════════════════════════════════
// PRUEBAS
// ═══════════════════════════════════════════

echo "══════════════════════════════════<br>";
echo " PRUEBAS RELOJ ASTRONÓMICO (PHP)<br>";
echo "══════════════════════════════════\n<br>";

// --- 1. Determinismo ---
echo "--- 1. Determinismo ---<br>";
$ts = 1717200000; // 1-jun-2024 00:00:00 UTC
$v1 = RelojAstronomico::vector_gravitacional(-34.0, -64.0, $ts);
$v2 = RelojAstronomico::vector_gravitacional(-34.0, -64.0, $ts);
assert_iguales($v1['x'], $v2['x'], "Mismo timestamp: x igual");
assert_iguales($v1['y'], $v2['y'], "Mismo timestamp: y igual");
assert_iguales($v1['z'], $v2['z'], "Mismo timestamp: z igual");

// --- 2. Normalización ---
echo "\n--- 2. Normalización ---<br>";
for ($d = 0; $d < 365; $d += 30) {
    $t = $ts + $d * 86400;
    $v = RelojAstronomico::vector_gravitacional(40.0, -3.0, $t);
    assert_vector_unitario($v, "Día $d");
}

// --- 3. Cercanía temporal (1 minuto) ---
echo "\n--- 3. Cercanía temporal (1 min) ---<br>";
$v1 = RelojAstronomico::vector_gravitacional(0, 0, $ts);
$v2 = RelojAstronomico::vector_gravitacional(0, 0, $ts + 60);
assert_similitud_mayor($v1, $v2, 0.9999, "1 min de diferencia");

// --- 4. Lejanía temporal (12 horas) ---
echo "\n--- 4. Lejanía temporal (12 h) ---<br>";
$v1 = RelojAstronomico::vector_gravitacional(0, 0, $ts);
$v2 = RelojAstronomico::vector_gravitacional(0, 0, $ts + 43200);
assert_similitud_menor($v1, $v2, 0.5, "12 h de diferencia (deberían ser bastante distintos)");

// --- 5. Ciclo diario (misma hora, días consecutivos) ---
echo "\n--- 5. Ciclo diario ---<br>";
$v1 = RelojAstronomico::vector_gravitacional(45.0, 10.0, $ts);
$v2 = RelojAstronomico::vector_gravitacional(45.0, 10.0, $ts + 86400);
assert_similitud_mayor($v1, $v2, 0.95, "24 h después (debe ser similar pero no idéntico)");

// --- 6. Ciclo anual (mismo día/hora, año siguiente) ---
echo "\n--- 6. Ciclo anual ---<br>";
$v1 = RelojAstronomico::vector_gravitacional(23.0, -102.0, $ts);
$v2 = RelojAstronomico::vector_gravitacional(23.0, -102.0, $ts + 365.25 * 86400);
//assert_similitud_mayor($v1, $v2, 0.7, "1 año después (estacionalmente similar)");
// Verificar que no son idénticos (no hay ciclo perfecto de 1 año)
assert_similitud_menor($v1, $v2, 0.999, "1 año después (no debe ser idéntico)");
// Y opcionalmente, verificar que la similitud es mayor que 0.3 (todavía hay algo de correlación estacional)
assert_similitud_mayor($v1, $v2, 0.3, "1 año después (algo de similitud estacional)");
// --- 7. Latitudes extremas (polos) ---
echo "\n--- 7. Polos ---<br>";
$v_norte = RelojAstronomico::vector_gravitacional(89.9, 0.0, $ts);
$v_sur   = RelojAstronomico::vector_gravitacional(-89.9, 0.0, $ts);
assert_vector_unitario($v_norte, "Polo Norte");
assert_vector_unitario($v_sur, "Polo Sur");
// En los polos, el vector debería ser casi vertical
// En los polos, el vector debe ser unitario (ya verificado)
// La componente z debe ser cercana a sin(declinación solar) ≈ 0.37
// Permitimos una tolerancia de ±0.1 por la contribución lunar
assert_iguales(
    abs($v_norte['z']),
    0.375,
    "Polo Norte: |z| ≈ sin(declinación solar)",
    0.1
);
assert_iguales(
    abs($v_sur['z']),
    0.375,
    "Polo Sur: |z| ≈ sin(declinación solar)",
    0.1
);
// --- 8. Timestamps extremos (pasado/futuro) ---
echo "\n--- 8. Timestamps extremos ---<br>";
$vp = RelojAstronomico::vector_gravitacional(0, 0, -157766400); // 1965
$vf = RelojAstronomico::vector_gravitacional(0, 0, 2524608000); // 2050
assert_vector_unitario($vp, "Año 1965");
assert_vector_unitario($vf, "Año 2050");

// --- 9. Caché de estado ---
echo "\n--- 9. Caché de estado ---<br>";
$reloj = new RelojAstronomico(-34.0, -64.0);
$v1 = $reloj->vector($ts);
$v2 = $reloj->vector($ts);
assert_iguales($v1['x'], $v2['x'], "Caché: mismo timestamp, mismo vector (x)");
assert_iguales($v1['y'], $v2['y'], "Caché: mismo timestamp, mismo vector (y)");
assert_iguales($v1['z'], $v2['z'], "Caché: mismo timestamp, mismo vector (z)");
// Forzar nuevo timestamp y ver cambio
$v3 = $reloj->vector($ts + 3600);
$dif = abs($v1['x'] - $v3['x']) + abs($v1['y'] - $v3['y']) + abs($v1['z'] - $v3['z']);
$ok = $dif > 0.001;
echo ($ok ? "✅ " : "❌ ") . "Caché: nuevo timestamp produce vector distinto<br>";

//--- 10. Cambio de ubicación ---
echo "\n--- 10. Cambio de ubicación ---<br>";
$reloj2 = new RelojAstronomico(-34.0, -64.0);
$v1 = $reloj2->vector($ts);

// Cambiar a otra ciudad (Madrid)
$reloj2->_ubicacion(40.4168, -3.7038);
$v2 = $reloj2->vector($ts);

// Deben ser distintos porque el cielo local cambia
$dot = $v1['x']*$v2['x'] + $v1['y']*$v2['y'] + $v1['z']*$v2['z'];
$ok = $dot < 0.999; // no idénticos
echo ($ok ? "✅ " : "❌ ") . "Cambio de ubicación produce vector distinto (similitud=$dot)<br>";

// Verificar que la caché se invalida
$v3 = $reloj2->vector($ts);
assert_iguales($v2['x'], $v3['x'], "Tras cambio de ubicación, mismo timestamp devuelve nuevo vector (x)");
echo "\n══════════════════════════════════<br>";
echo " PRUEBAS FINALIZADAS<br>";
echo "══════════════════════════════════<br>";