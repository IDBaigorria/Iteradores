<?php
/**
 * Pruebas exhaustivas v1.5.2 – RelojArtificial (Plano Rítmico UTC)
 *
 * Cubre:
 *   - Instanciación y estructura del ramillete.
 *   - Unitariedad de vectores.
 *   - Caché por tiempo_unix.
 *   - Independencia de la ubicación geográfica.
 *   - Distancia temporal por escalas (segundos, minutos, horas, días,
 *     semanas, meses, años, décadas, siglos, milenios).
 *   - Agregar/eliminar ciclos y descubrimiento automático.
 *   - Método espin() y determinismo.
 *
 * @since 1.5.2
 * @version 1.5.2
 * @author Ignacio David Baigorria
 */

require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nucleo/Objeto.php';
require_once __DIR__ . '/../Tiempo/RelojArtificial.php';

use Iteradores\Configuracion\Conf;
use Iteradores\Tiempo\RelojArtificial;

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

echo "══════════════════════════════════<br>";
echo " PRUEBAS 1.5.2 – RELOJ ARTIFICIAL<br>";
echo "══════════════════════════════════<br><br>";

// ═══════════════════════════════════
// 1. INSTANCIACIÓN Y RAMILLETE BÁSICO
// ═══════════════════════════════════
echo "<h3>1. Instanciación y ramillete básico</h3>";
$reloj = new RelojArtificial();
$tiempo_base = strtotime('2026-07-15 12:00:00 UTC');
$espines = $reloj->espines($tiempo_base);

verificar_no_nulo($espines, "espines() retorna un array");
verificar_verdadero(is_array($espines), "espines() es array");
verificar_iguales(count($espines), count(Conf::RELOJ_CICLOS_RITMICOS), "Cantidad de espines coincide con ciclos registrados");

$nombres_esperados = array_keys(Conf::RELOJ_CICLOS_RITMICOS);
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
// 4. INDEPENDENCIA DE UBICACIÓN
// ═══════════════════════════════════
echo "<h3>4. Independencia de ubicación</h3>";
$reloj_ba = new RelojArtificial();
$reloj_mad = new RelojArtificial();
$esp_ba = $reloj_ba->espines($tiempo_base);
$esp_mad = $reloj_mad->espines($tiempo_base);
verificar_iguales(json_encode($esp_ba), json_encode($esp_mad), "Ramilletes idénticos para distintas instancias");

// ═══════════════════════════════════
// 5. DISTANCIA TEMPORAL POR ESCALA
// ═══════════════════════════════════
echo "<h3>5. Distancia temporal por escala</h3>";
$t0 = $tiempo_base;

// Escala segundos (ciclo: minuto, período 60s -> rango válido < 30s)
$s0  = $reloj->espines_por_escala('segundos', $t0);
$s1  = $reloj->espines_por_escala('segundos', $t0 + 1);
$s10 = $reloj->espines_por_escala('segundos', $t0 + 10);
$s20 = $reloj->espines_por_escala('segundos', $t0 + 20);

$d_s1  = distancia_ramilletes($s0, $s1);
$d_s10 = distancia_ramilletes($s0, $s10);
$d_s20 = distancia_ramilletes($s0, $s20);

echo "<b>Escala segundos (minuto):</b><br>";
echo "1 s: " . number_format($d_s1, 6) . "<br>";
echo "10 s: " . number_format($d_s10, 6) . "<br>";
echo "20 s: " . number_format($d_s20, 6) . "<br>";
verificar_verdadero($d_s1 < $d_s10, "Segundos: 1 s < 10 s");
verificar_verdadero($d_s10 < $d_s20, "Segundos: 10 s < 20 s");

// Escala minutos (ciclo: hora, período 3600s -> rango válido < 1800s)
$m0   = $reloj->espines_por_escala('minutos', $t0);
$m1   = $reloj->espines_por_escala('minutos', $t0 + 60);
$m10  = $reloj->espines_por_escala('minutos', $t0 + 600);
$m20  = $reloj->espines_por_escala('minutos', $t0 + 1200);

$d_m1  = distancia_ramilletes($m0, $m1);
$d_m10 = distancia_ramilletes($m0, $m10);
$d_m20 = distancia_ramilletes($m0, $m20);

echo "<b>Escala minutos (hora):</b><br>";
echo "1 min: " . number_format($d_m1, 6) . "<br>";
echo "10 min: " . number_format($d_m10, 6) . "<br>";
echo "20 min: " . number_format($d_m20, 6) . "<br>";
verificar_verdadero($d_m1 < $d_m10, "Minutos: 1 min < 10 min");
verificar_verdadero($d_m10 < $d_m20, "Minutos: 10 min < 20 min");

// Escala horas (ciclo: dia_noche, período 86400s -> rango válido < 43200s)
$h0  = $reloj->espines_por_escala('horas', $t0);
$h1  = $reloj->espines_por_escala('horas', $t0 + 3600);
$h6  = $reloj->espines_por_escala('horas', $t0 + 21600);
$h12 = $reloj->espines_por_escala('horas', $t0 + 43200);

$d_h1  = distancia_ramilletes($h0, $h1);
$d_h6  = distancia_ramilletes($h0, $h6);
$d_h12 = distancia_ramilletes($h0, $h12);

echo "<b>Escala horas (dia_noche):</b><br>";
echo "1 h: " . number_format($d_h1, 6) . "<br>";
echo "6 h: " . number_format($d_h6, 6) . "<br>";
echo "12 h: " . number_format($d_h12, 6) . "<br>";
verificar_verdadero($d_h1 < $d_h6, "Horas: 1 h < 6 h");
verificar_verdadero($d_h6 < $d_h12, "Horas: 6 h < 12 h");

// Escala días (ciclo: semana, período 604800s -> rango válido < 302400s)
$d0  = $reloj->espines_por_escala('dias', $t0);
$d1  = $reloj->espines_por_escala('dias', $t0 + 86400);   // 1 día
$d2  = $reloj->espines_por_escala('dias', $t0 + 172800);  // 2 días
$d3  = $reloj->espines_por_escala('dias', $t0 + 259200);  // 3 días

$d_d1 = distancia_ramilletes($d0, $d1);
$d_d2 = distancia_ramilletes($d0, $d2);
$d_d3 = distancia_ramilletes($d0, $d3);

echo "<b>Escala días (semana):</b><br>";
echo "1 d: " . number_format($d_d1, 6) . "<br>";
echo "2 d: " . number_format($d_d2, 6) . "<br>";
echo "3 d: " . number_format($d_d3, 6) . "<br>";
verificar_verdadero($d_d1 < $d_d2, "Días: 1 d < 2 d");
verificar_verdadero($d_d2 < $d_d3, "Días: 2 d < 3 d");

// Escala semanas (ciclo: mes, período 2592000s -> rango válido < 1296000s)
$w0  = $reloj->espines_por_escala('semanas', $t0);
$w1  = $reloj->espines_por_escala('semanas', $t0 + 604800);   // 1 semana
$w2  = $reloj->espines_por_escala('semanas', $t0 + 1209600);  // 2 semanas

$d_w1 = distancia_ramilletes($w0, $w1);
$d_w2 = distancia_ramilletes($w0, $w2);

echo "<b>Escala semanas (mes):</b><br>";
echo "1 sem: " . number_format($d_w1, 6) . "<br>";
echo "2 sem: " . number_format($d_w2, 6) . "<br>";
verificar_verdadero($d_w1 < $d_w2, "Semanas: 1 sem < 2 sem");

// Escala meses (ciclo: anno, período 31557600s -> rango válido < 15778800s)
$me0  = $reloj->espines_por_escala('meses', $t0);
$me1  = $reloj->espines_por_escala('meses', $t0 + 2592000);   // 1 mes
$me2  = $reloj->espines_por_escala('meses', $t0 + 5184000);   // 2 meses
$me3  = $reloj->espines_por_escala('meses', $t0 + 7776000);   // 3 meses

$d_me1 = distancia_ramilletes($me0, $me1);
$d_me2 = distancia_ramilletes($me0, $me2);
$d_me3 = distancia_ramilletes($me0, $me3);

echo "<b>Escala meses (anno):</b><br>";
echo "1 mes: " . number_format($d_me1, 6) . "<br>";
echo "2 meses: " . number_format($d_me2, 6) . "<br>";
echo "3 meses: " . number_format($d_me3, 6) . "<br>";
verificar_verdadero($d_me1 < $d_me2, "Meses: 1 mes < 2 meses");
verificar_verdadero($d_me2 < $d_me3, "Meses: 2 meses < 3 meses");

// Escala años (ciclo: ciclo_128, período 4039372800s -> rango válido < 2019686400s)
$a0  = $reloj->espines_por_escala('anios', $t0);
$a1  = $reloj->espines_por_escala('anios', $t0 + 31557600);
$a5  = $reloj->espines_por_escala('anios', $t0 + 157788000);
$a10 = $reloj->espines_por_escala('anios', $t0 + 315576000);

$d_a1  = distancia_ramilletes($a0, $a1);
$d_a5  = distancia_ramilletes($a0, $a5);
$d_a10 = distancia_ramilletes($a0, $a10);

echo "<b>Escala años (ciclo_128):</b><br>";
echo "1 año: " . number_format($d_a1, 6) . "<br>";
echo "5 años: " . number_format($d_a5, 6) . "<br>";
echo "10 años: " . number_format($d_a10, 6) . "<br>";
verificar_verdadero($d_a1 < $d_a5, "Años: 1 año < 5 años");
verificar_verdadero($d_a5 < $d_a10, "Años: 5 años < 10 años");

// Escala décadas (ciclo: ciclo_siglo, período 3155760000s -> rango válido < 1577880000s)
$dec0  = $reloj->espines_por_escala('decadas', $t0);
$dec10 = $reloj->espines_por_escala('decadas', $t0 + 315576000);
$dec20 = $reloj->espines_por_escala('decadas', $t0 + 631152000);
$dec30 = $reloj->espines_por_escala('decadas', $t0 + 946728000);

$d_dec10 = distancia_ramilletes($dec0, $dec10);
$d_dec20 = distancia_ramilletes($dec0, $dec20);
$d_dec30 = distancia_ramilletes($dec0, $dec30);

echo "<b>Escala décadas (ciclo_siglo):</b><br>";
echo "10 años: " . number_format($d_dec10, 6) . "<br>";
echo "20 años: " . number_format($d_dec20, 6) . "<br>";
echo "30 años: " . number_format($d_dec30, 6) . "<br>";
verificar_verdadero($d_dec10 < $d_dec20, "Décadas: 10 años < 20 años");
verificar_verdadero($d_dec20 < $d_dec30, "Décadas: 20 años < 30 años");

// Escala siglos (ciclo: ciclo_milenio, período 31557600000s -> rango válido < 15778800000s)
$sig0   = $reloj->espines_por_escala('siglos', $t0);
$sig100 = $reloj->espines_por_escala('siglos', $t0 + 3155760000);
$sig200 = $reloj->espines_por_escala('siglos', $t0 + 6311520000);
$sig300 = $reloj->espines_por_escala('siglos', $t0 + 9467280000);

$d_sig100 = distancia_ramilletes($sig0, $sig100);
$d_sig200 = distancia_ramilletes($sig0, $sig200);
$d_sig300 = distancia_ramilletes($sig0, $sig300);

echo "<b>Escala siglos (ciclo_milenio):</b><br>";
echo "100 años: " . number_format($d_sig100, 6) . "<br>";
echo "200 años: " . number_format($d_sig200, 6) . "<br>";
echo "300 años: " . number_format($d_sig300, 6) . "<br>";
verificar_verdadero($d_sig100 < $d_sig200, "Siglos: 100 años < 200 años");
verificar_verdadero($d_sig200 < $d_sig300, "Siglos: 200 años < 300 años");

// Escala milenios (ciclo: ciclo_precesion, período 814155840000s -> rango válido < 407077920000s)
$mil0    = $reloj->espines_por_escala('milenios', $t0);
$mil1000 = $reloj->espines_por_escala('milenios', $t0 + 31557600000);
$mil2000 = $reloj->espines_por_escala('milenios', $t0 + 63115200000);
$mil3000 = $reloj->espines_por_escala('milenios', $t0 + 94672800000);

$d_mil1000 = distancia_ramilletes($mil0, $mil1000);
$d_mil2000 = distancia_ramilletes($mil0, $mil2000);
$d_mil3000 = distancia_ramilletes($mil0, $mil3000);

echo "<b>Escala milenios (ciclo_precesion):</b><br>";
echo "1000 años: " . number_format($d_mil1000, 6) . "<br>";
echo "2000 años: " . number_format($d_mil2000, 6) . "<br>";
echo "3000 años: " . number_format($d_mil3000, 6) . "<br>";
verificar_verdadero($d_mil1000 < $d_mil2000, "Milenios: 1000 años < 2000 años");
verificar_verdadero($d_mil2000 < $d_mil3000, "Milenios: 2000 años < 3000 años");

// ═══════════════════════════════════
// 6. AGREGAR Y ELIMINAR CICLOS
// ═══════════════════════════════════
echo "<h3>6. Agregar y eliminar ciclos</h3>";

$reloj_mod = new RelojArtificial();
$esp_antes = $reloj_mod->espines($t0);

$reloj_mod->agregar_ciclo('siesta', 3600.0, 0.0, 2.5, 'Ritmo');
$esp_despues = $reloj_mod->espines($t0);

verificar_verdadero(count($esp_despues) === count($esp_antes) + 1, "agregar_ciclo agrega un espin");
verificar_verdadero(
    array_key_exists('siesta', $reloj_mod->ciclos_registrados()),
    "El nuevo ciclo aparece registrado"
);

$reloj_mod->eliminar_ciclo('siesta');
$esp_final = $reloj_mod->espines($t0);
verificar_iguales(count($esp_final), count($esp_antes), "eliminar_ciclo restaura cantidad original");

// ═══════════════════════════════════
// 7. DESCUBRIR CICLO
// ═══════════════════════════════════
echo "<h3>7. Descubrir ciclo</h3>";

$reloj_desc = new RelojArtificial();
$periodo_desc = 7200.0; // 2 horas
$tiempo_referencia = $t0;
$reloj_desc->descubrir_ciclo('ritmo_2h', $periodo_desc, $tiempo_referencia, 1.0);

$espin_desc = $reloj_desc->espin('ritmo_2h', $tiempo_referencia);
verificar_no_nulo($espin_desc, "descubrir_ciclo crea un espin");
verificar_iguales($espin_desc['tipo'], 'RitmoDescubierto', "El tipo del ciclo descubierto es 'RitmoDescubierto'");

// ═══════════════════════════════════
// 8. MÉTODO espin() ESPECÍFICO
// ═══════════════════════════════════
echo "<h3>8. Método espin()</h3>";

$espin_hora = $reloj->espin('hora', $t0);
verificar_no_nulo($espin_hora, "espin('hora') retorna espin");
verificar_iguales($espin_hora['nombre'], 'hora', "Nombre correcto");
verificar_iguales(magnitud_vector($espin_hora['vector']), 1.0, "Vector unitario", 1e-6);

$espin_invalido = $reloj->espin('no_existe', $t0);
verificar_falso($espin_invalido !== null, "espin('no_existe') devuelve null");

// ═══════════════════════════════════
// 9. DETERMINISMO
// ═══════════════════════════════════
echo "<h3>9. Determinismo</h3>";
$reloj2 = new RelojArtificial();
$esp2 = $reloj2->espines($t0);
verificar_iguales(json_encode($esp2), json_encode($espines), "Dos relojes generan el mismo ramillete");

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS 1.5.2 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";

RelojArtificial::imprimir_alertas();
RelojArtificial::imprimir_errores();