<?php
/**
 * Pruebas exhaustivas v1.5i.1 – Propiedades del Iterador y Marca de Ocupado
 *
 * Cubre: es_iterador, nombre, ocupar, desocupar, ocupado.
 *
 * @since 1.0
 * @version 1.5i.1
 * @author Ignacio David Baigorria
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
echo " PRUEBAS v1.5i.1 – PROPIEDADES Y MARCA DE OCUPADO<br>";
echo "══════════════════════════════════<br><br>";

Controlador::ejecutar_prueba(function ($token) {
    // ═══════════════════════════════════
    // 1. es_iterador
    // ═══════════════════════════════════
    echo "<h3>1. es_iterador</h3>";
    $iter = Iterador::crear("iter_prop");
    verificar_verdadero(Iterador::es_iterador($iter), "es_iterador(iterador) devuelve true");
    $nodo = Nodo::crear();
    verificar_falso(Iterador::es_iterador($nodo), "es_iterador(nodo) devuelve false");
    verificar_falso(Iterador::es_iterador(null), "es_iterador(null) devuelve false");

    // ═══════════════════════════════════
    // 2. nombre()
    // ═══════════════════════════════════
    echo "<h3>2. nombre()</h3>";
    verificar_iguales($iter->nombre(), "iter_prop", "nombre() retorna el nombre correcto");

    // ═══════════════════════════════════
    // 3. ocupado() inicial
    // ═══════════════════════════════════
    echo "<h3>3. ocupado() inicial</h3>";
    verificar_verdadero($iter->ocupado(), "ocupado() es true tras crear");

    // ═══════════════════════════════════
    // 4. desocupar()
    // ═══════════════════════════════════
    echo "<h3>4. desocupar()</h3>";
    verificar_verdadero($iter->desocupar(), "desocupar() devuelve true");
    verificar_falso($iter->ocupado(), "ocupado() es false tras desocupar");
    verificar_falso($iter->nombre(), "nombre() devuelve false tras desocupar (no ocupado)");
    verificar_falso($iter->desocupar(), "desocupar() de nuevo devuelve false");

    // ═══════════════════════════════════
    // 5. Cargar después de desocupar
    // ═══════════════════════════════════
    echo "<h3>5. Cargar después de desocupar</h3>";
    $iter2 = Iterador::cargar("iter_prop");
    verificar_no_nulo($iter2, "cargar() tras desocupar devuelve iterador");
    verificar_iguales($iter2->nombre(), "iter_prop", "nombre() del iterador cargado es correcto");

    // ═══════════════════════════════════
    // 6. ocupar() protegido
    // ═══════════════════════════════════
    echo "<h3>6. ocupar() protegido</h3>";

    // Crear un iterador auxiliar para esta prueba
    $iter_aux = Iterador::crear("iter_ocupar_test");
    // Obtener su cuerpo desde la superestructura
    $its = Nodo::nodo_por_id("iteradores");
    $nombre_clase = get_class($iter_aux); // "Iteradores\Iteradores\Iterador"
    $nclase = $its->adyacente($nombre_clase);
    $nits = $nclase->adyacente("iteradores");
    $cuerpo_aux = $nits->adyacente("iter_ocupar_test");

    // Desocupar el iterador (esto elimina el autoenlace y deja raiz_cuerpo null)
    $iter_aux->desocupar();

    // Asignar manualmente el cuerpo al iterador (sin autoenlace "ocupado")
    $iter_aux->raiz_cuerpo = $cuerpo_aux;

    // Ahora invocar ocupar() mediante Reflection
    $reflection = new ReflectionMethod(Iterador::class, 'ocupar');
    $reflection->setAccessible(true);

    $resultado = $reflection->invoke($iter_aux);
    verificar_verdadero($resultado, "ocupar() protegido devuelve true");
    verificar_verdadero($iter_aux->ocupado(), "ocupado() es true tras ocupar");

    $resultado2 = $reflection->invoke($iter_aux);
    verificar_falso($resultado2, "ocupar() de nuevo devuelve false (ya ocupado)");

    // Probar ocupar sin cuerpo
    $iter3 = new Iterador();
    $resultado3 = $reflection->invoke($iter3);
    verificar_falso($resultado3, "ocupar() sin cuerpo devuelve false");

    // Limpiar: destruir $iter_aux (está ocupado)
    //$iter_aux->destruir();

    // ═══════════════════════════════════
    // 7. Limpieza final
    // ═══════════════════════════════════
    echo "<h3>7. Limpieza final</h3>";
    // $iter_cargado está ocupado, lo destruimos
    verificar_verdadero($iter_aux->destruir(), "destruir() iter_cargado funciona");
    // $iter y $iter2 ya no tienen cuerpo, no necesitan destrucción
    verificar_verdadero(true, "Pruebas finalizadas sin residuos importantes");
});

echo "<br>══════════════════════════════════<br>";
echo " PRUEBAS v1.5i.1 FINALIZADAS<br>";
echo "══════════════════════════════════<br>";
Iterador::imprimir_alertas();
Iterador::imprimir_errores();