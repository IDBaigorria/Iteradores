<?php
/**
 * Pruebas exhaustivas v1.5i.4 – Interfaces Dato y Liberar
 *
 * @since 1.0
 * @version 1.5i.4
 */

require_once __DIR__ . '/../Controlador/Controlador.php';
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Nodos/Nodo.php';
require_once __DIR__ . '/../Iteradores/Iterador.php';

use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\Nodo;
use Iteradores\Iteradores\Iterador;

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
echo " PRUEBAS v1.5i.4 – DATO Y LIBERAR<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // 1. _dato simple
    $nodo_raiz = Nodo::crear_con_dato("raiz");
    $iter = Iterador::crear("iter_dato_liberar", $nodo_raiz);

    echo "<h3>1. _dato simple</h3>";
    $res = $iter->_dato("nuevo_valor");
    verificar_no_nulo($res, "_dato() retorna nodo");
    verificar_iguales($res->dato(), "nuevo_valor", "dato asignado correctamente");
    verificar_iguales($iter->dato(), "nuevo_valor", "dato() retorna el nuevo valor");

    // 2. _dato con camino
    echo "<h3>2. _dato con camino</h3>";
    $nodo_hijo = Nodo::crear_con_dato("hijo");
    $nodo_raiz->_adyacente_en($nodo_hijo, "a");
    $res2 = $iter->_dato("dato_en_hijo", "a");
    verificar_no_nulo($res2, "_dato() con camino retorna nodo");
    verificar_iguales($nodo_hijo->dato(), "dato_en_hijo", "dato asignado en hijo");
    verificar_verdadero($iter->actual() === $nodo_raiz, "posición actual restaurada");

    // 3. _dato rechaza nodo
    echo "<h3>3. _dato rechaza nodo</h3>";
    $otro_nodo = Nodo::crear();
    verificar_iguales($iter->_dato($otro_nodo), null, "_dato(nodo) devuelve null");

    // 4. dato con camino
    echo "<h3>4. dato con camino</h3>";
    verificar_iguales($iter->dato("a"), "dato_en_hijo", "dato('a') devuelve el dato del hijo");

    // 5. liberar
    echo "<h3>5. liberar</h3>";
    $actual_antes = $iter->actual();
    $liberado = $iter->liberar();
    verificar_verdadero($liberado === $actual_antes, "liberar() retorna el nodo que era actual");
    verificar_verdadero($iter->actual() === $iter->raiz_cuerpo, "el actual ahora es el cuerpo");
    verificar_iguales($iter->liberar(), null, "liberar() de nuevo devuelve null (ya liberado)");

    // 6. limpieza
    $iter->destruir();
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.4 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();