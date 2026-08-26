<?php
/**
 * Pruebas exhaustivas v1.5.2 – RelojAstronomico (ramillete de espines galáctico‑eclíptico)
 *
 * Cubre:
 *   - Instanciación y estructura del ramillete.
 *   - Unitariedad y tipado.
 *   - Caché por tiempo_unix.
 *   - Separación cósmico/geográfico:
 *       * Los espines cósmicos son idénticos para distintas ubicaciones.
 *       * El espín centro_tierra varía con la ubicación.
 *   - Distancia temporal por escalas.
 *   - Distancia espacial (usando centro_tierra).
 *   - Método espin() y determinismo.
 *
 * @since 1.5.2
 * @version 1.5.2
 * @author Ignacio David Baigorria
 */

require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nucleo/Objeto.php';
require_once __DIR__ . '/../Tiempo/RelojAstronomico.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Tiempo\RelojAstronomico;

// ─── Helpers ──────────────────────────────────────────
function verificar_iguales($a, $b, string $mensaje, float $tolerancia = 1e-9): bool {
    if (is_numeric($a) && is_numeric($b)) {
        $ok = abs($a - $b) < $tolerancia;
        echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
        if (!$ok) echo "   Esperado: $b, Obtenido: $a<br>";
        return $ok;
    }
    $ok = $a === $b;
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) echo "   Esperado: " . var_export($b, true) . ", Obtenido: " . var_export($a, true) . "<br>";
    return $ok;
}

function verificar_vectores_iguales(array $a, array $b, string $mensaje, float $tolerancia = 1e-6): bool {
    $ok = true;
    foreach (['x', 'y', 'z'] as $componente) {
        if (abs($a[$componente] - $b[$componente]) > $tolerancia) {
            $ok = false;
            break;
        }
    }
    echo ($ok ? "✅ " : "❌ ") . $mensaje . "<br>";
    if (!$ok) {
        echo "   Se esperaban vectores casi idénticos.<br>";
        echo "   a = " . json_encode($a) . "<br>";
        echo "   b = " . json_encode($b) . "<br>";
    }
    return $ok;
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

function magnitud_vector(array $v): float {
    return sqrt($v['x']**2 + $v['y']**2 + $v['z']**2);
}

function distancia_espines(array $a, array $b): float {
    return sqrt(
        ($a['x'] - $b['x'])**2 +
        ($a['y'] - $b['y'])**2 +
        ($a['z'] - $b['z'])**2
    );
}

function distancia_ramilletes(array $r1, array $r2): float {
    $n = min(count($r1), count($r2));
    if ($n === 0) return 0.0;
    $suma = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $suma += distancia_espines($r1[$i]['vector'], $r2[$i]['vector']);
    }
    return $suma / $n;
}

function distancia_por_nombre(array $r1, array $r2, string $nombre): float {
    $e1 = null;
    $e2 = null;
    foreach ($r1 as $espin) {
        if ($espin['nombre'] === $nombre) $e1 = $espin['vector'];
    }
    foreach ($r2 as $espin) {
        if ($espin['nombre'] === $nombre) $e2 = $espin['vector'];
    }
    if ($e1 === null || $e2 === null) return -1.0;
    return distancia_espines($e1, $e2);
}

echo "══════════════════════════════════<br>";
echo " PRUEBAS 1.5.2 – RELOJ ASTRONÓMICO<br>";
echo "══════════════════════════════════<br><br>";

// ═══════════════════════════════════
// 1. INSTANCIACIÓN Y RAMILLETE BÁSICO
// ═══════════════════════════════════
echo "<h3>1. Instanciación y ramillete básico</h3>";
$lat = Conf::LATITUD_PREDETERMINADA;
$lon = Conf::LONGITUD_PREDETERMINADA;
$reloj = new RelojAstronomico($lat, $lon);
$tiempo_base = strtotime('2026-07-15 12:00:00 UTC');
$espines = $reloj->espines($tiempo_base);

verificar_no_nulo($espines, "espines() retorna un array");
verificar_verdadero(is_array($espines), "espines() es array");
verificar_iguales(count($espines), count(Conf::RELOJ_ASTROS), "Cantidad de espines coincide con astros registrados");

$nombres_esperados = array_keys(Conf::RELOJ_ASTROS);
$nombres_obtenidos = array_column($espines, 'nombre');
foreach ($nombres_esperados as $nombre) {
    verificar_verdadero(in_array($nombre, $nombres_obtenidos, true), "El ramillete contiene '$nombre'");
}

// ═══════════════════════════════════
// 2. UNITARIEDAD DE VECTORES
// ═══════════════════════════════════
echo "<h3>2. Unitariedad de vectores</h3>";
foreach ($espines as $espin) {
    verificar_iguales(magnitud_vector($espin['vector']), 1.0, "Vector de {$espin['nombre']} es unitario", 1e-6);
}

// ═══════════════════════════════════
// 3. CACHÉ POR TIEMPO_UNIX
// ═══════════════════════════════════
echo "<h3>3. Caché por tiempo_unix</h3>";
$c1 = $reloj->espines($tiempo_base);
$c2 = $reloj->espines($tiempo_base);
verificar_verdadero($c1 === $c2, "Dos llamadas con el mismo tiempo devuelven la misma referencia");
$c3 = $reloj->espines($tiempo_base + 60);
verificar_falso($c1 === $c3, "Con otro tiempo se recalcula");

// ═══════════════════════════════════
// 4. SEPARACIÓN CÓSMICO / GEOGRÁFICO
// ═══════════════════════════════════
echo "<h3>4. Separación cósmico/geográfico</h3>";

$reloj_ba = new RelojAstronomico(-34.6, -58.4);
$reloj_mad = new RelojAstronomico(40.4, -3.7);

$esp_ba = $reloj_ba->espines($tiempo_base);
$esp_mad = $reloj_mad->espines($tiempo_base);

// Los espines cósmicos deben ser idénticos
foreach (['sol', 'luna', 'jupiter', 'eje_terrestre'] as $astro) {
    $v1 = null; $v2 = null;
    foreach ($esp_ba as $e) if ($e['nombre'] === $astro) $v1 = $e['vector'];
    foreach ($esp_mad as $e) if ($e['nombre'] === $astro) $v2 = $e['vector'];
    verificar_vectores_iguales($v1, $v2, "Espín cósmico '$astro' es idéntico para BA y Madrid");
}

// El espín geográfico debe ser diferente
$ct_ba = null; $ct_mad = null;
foreach ($esp_ba as $e) if ($e['nombre'] === 'centro_tierra') $ct_ba = $e['vector'];
foreach ($esp_mad as $e) if ($e['nombre'] === 'centro_tierra') $ct_mad = $e['vector'];

$dist_geo_ba_mad = distancia_espines($ct_ba, $ct_mad);
verificar_verdadero($dist_geo_ba_mad > 0.0, "Espín centro_tierra es diferente entre BA y Madrid");
echo "Distancia centro_tierra BA ↔ Madrid: " . number_format($dist_geo_ba_mad, 6) . "<br>";

// La distancia promedio del ramillete completo refleja solo la geografía
$dist_completa_ba_mad = distancia_ramilletes($esp_ba, $esp_mad);
verificar_verdadero($dist_completa_ba_mad > 0.0, "Distancia del ramillete completo > 0");
echo "Distancia ramillete completo BA ↔ Madrid: " . number_format($dist_completa_ba_mad, 6) . "<br>";

// ═══════════════════════════════════
// 5. DISTANCIA TEMPORAL POR ESCALA
// ═══════════════════════════════════
echo "<h3>5. Distancia temporal por escala</h3>";
$reloj->_ubicacion($lat, $lon); // volver a Tres Arroyos

$t0 = $tiempo_base;
$t_1s = $t0 + 1;
$t_1h = $t0 + 3600;
$t_6h = $t0 + 21600;
$t_1d = $t0 + 86400;
$t_15d = $t0 + 1296000;
$t_30d = $t0 + 2592000;
$t_1a = $t0 + 31557600;
$t_5a = $t0 + 157788000;
$t_100a = $t0 + 3155760000;
$t_1000a = $t0 + 31557600000;

// Escala segundos: centro_tierra
$seg0 = $reloj->espines_por_escala('segundos', $t0);
$seg1s = $reloj->espines_por_escala('segundos', $t_1s);
$seg1h = $reloj->espines_por_escala('segundos', $t_1h);
$seg6h = $reloj->espines_por_escala('segundos', $t_6h);

$d_seg1s = distancia_ramilletes($seg0, $seg1s);
$d_seg1h = distancia_ramilletes($seg0, $seg1h);
$d_seg6h = distancia_ramilletes($seg0, $seg6h);

echo "<b>Escala segundos (centro_tierra):</b><br>";
echo "1 s: " . number_format($d_seg1s, 6) . "<br>";
echo "1 h: " . number_format($d_seg1h, 6) . "<br>";
echo "6 h: " . number_format($d_seg6h, 6) . "<br>";
verificar_verdadero($d_seg1s < $d_seg1h, "Segundos: 1 s < 1 h");
verificar_verdadero($d_seg1h < $d_seg6h, "Segundos: 1 h < 6 h");

// Escala días: sol
$dia0 = $reloj->espines_por_escala('dias', $t0);
$dia1 = $reloj->espines_por_escala('dias', $t_1d);
$dia15 = $reloj->espines_por_escala('dias', $t_15d);
$dia30 = $reloj->espines_por_escala('dias', $t_30d);

$d_dia1 = distancia_ramilletes($dia0, $dia1);
$d_dia15 = distancia_ramilletes($dia0, $dia15);
$d_dia30 = distancia_ramilletes($dia0, $dia30);

echo "<b>Escala días (sol):</b><br>";
echo "1 d: " . number_format($d_dia1, 6) . "<br>";
echo "15 d: " . number_format($d_dia15, 6) . "<br>";
echo "30 d: " . number_format($d_dia30, 6) . "<br>";
verificar_verdadero($d_dia1 < $d_dia15, "Días: 1 d < 15 d");
verificar_verdadero($d_dia15 < $d_dia30, "Días: 15 d < 30 d");

// Escala años: júpiter
$anio0 = $reloj->espines_por_escala('anios', $t0);
$anio1 = $reloj->espines_por_escala('anios', $t_1a);
$anio5 = $reloj->espines_por_escala('anios', $t_5a);

$d_anio1 = distancia_ramilletes($anio0, $anio1);
$d_anio5 = distancia_ramilletes($anio0, $anio5);

echo "<b>Escala años (júpiter):</b><br>";
echo "1 año: " . number_format($d_anio1, 6) . "<br>";
echo "5 años: " . number_format($d_anio5, 6) . "<br>";
verificar_verdadero($d_anio1 < $d_anio5, "Años: 1 año < 5 años");

// Escala siglos: eje_terrestre
$sig0 = $reloj->espines_por_escala('siglos', $t0);
$sig100 = $reloj->espines_por_escala('siglos', $t_100a);
$sig1000 = $reloj->espines_por_escala('siglos', $t_1000a);

$d_sig100 = distancia_ramilletes($sig0, $sig100);
$d_sig1000 = distancia_ramilletes($sig0, $sig1000);

echo "<b>Escala siglos (eje_terrestre):</b><br>";
echo "100 años: " . number_format($d_sig100, 6) . "<br>";
echo "1000 años: " . number_format($d_sig1000, 6) . "<br>";
verificar_verdadero($d_sig100 < $d_sig1000, "Siglos: 100 años < 1000 años");

// ═══════════════════════════════════
// 6. DISTANCIA ESPACIAL (centro_tierra)
// ═══════════════════════════════════
echo "<h3>6. Distancia espacial</h3>";

$reloj_ba2 = new RelojAstronomico(-34.6, -58.4);
$reloj_mad2 = new RelojAstronomico(40.4, -3.7);
$reloj_sidney2 = new RelojAstronomico(-33.9, 151.2);

$esp_ba2 = $reloj_ba2->espines($t0);
$esp_mad2 = $reloj_mad2->espines($t0);
$esp_sidney2 = $reloj_sidney2->espines($t0);

$dist_ct_ba_mad = distancia_por_nombre($esp_ba2, $esp_mad2, 'centro_tierra');
$dist_ct_ba_sid = distancia_por_nombre($esp_ba2, $esp_sidney2, 'centro_tierra');

echo "Distancia centro_tierra BA ↔ Madrid: " . number_format($dist_ct_ba_mad, 6) . "<br>";
echo "Distancia centro_tierra BA ↔ Sidney: " . number_format($dist_ct_ba_sid, 6) . "<br>";

verificar_verdadero($dist_ct_ba_mad > 0.0, "Distancia espacial Madrid > 0");
verificar_verdadero($dist_ct_ba_sid > 0.0, "Distancia espacial Sidney > 0");

// ═══════════════════════════════════
// 7. MÉTODO espin() ESPECÍFICO
// ═══════════════════════════════════
echo "<h3>7. Método espin()</h3>";
$sol = $reloj->espin('sol', $t0);
verificar_no_nulo($sol, "espin('sol') retorna espin");
verificar_iguales($sol['nombre'], 'sol', "Nombre 'sol'");
verificar_iguales(magnitud_vector($sol['vector']), 1.0, "Vector unitario", 1e-6);
$marte = $reloj->espin('marte', $t0);
verificar_falso($marte !== null, "espin('marte') devuelve null");

// ═══════════════════════════════════
// 8. DETERMINISMO
// ═══════════════════════════════════
echo "<h3>8. Determinismo</h3>";
$reloj2 = new RelojAstronomico($lat, $lon);
$esp2 = $reloj2->espines($t0);
verificar_iguales(json_encode($esp2), json_encode($espines), "Dos relojes generan el mismo ramillete");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.5.2 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
RelojAstronomico::imprimir_alertas();
RelojAstronomico::imprimir_errores();