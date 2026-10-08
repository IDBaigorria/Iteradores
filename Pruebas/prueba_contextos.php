<?php
/**
 * Prueba funcional de la fase 1 de contextos (SQL64).
 *
 * Se ejecuta con ?probar_contextos=1 desde index.php.
 *
 * Convención: todo ID especial es un contexto. Por eso
 * acá los roots (ctx_a, ctx_b) son especiales, y los
 * nodos hijos tienen IDs normales (no especiales).
 *
 * @since 1.5i.7k
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

header('Content-Type: text/plain; charset=utf-8');
echo "=== Prueba de contextos (SQL64) ===\n\n";

$nombre_test = 'TestContextos_' . date('YmdHis');
$res = [];

Controlador::establecer_metodo('SQL64');

// --- 1. Crear grafo ---
// ctx_a y ctx_b son IDs especiales → contextos.
// n1, n2, n3 son IDs normales → no son contextos.
Controlador::ejecutar_prueba(function($token) use (&$res) {
    Nodo::vaciar_superestructura($token);
    $raiz_a = Nodo::crear_con_id('ctx_a');
    $raiz_b = Nodo::crear_con_id('ctx_b');
    $n1 = Nodo::crear_con_dato('nodo1');
    $n2 = Nodo::crear_con_dato('nodo2');
    $n3 = Nodo::crear_con_dato('nodo3');
    $raiz_a->_adyacente_en($n1, 'hijo');
    $raiz_b->_adyacente_en($n2, 'hijo');
    $n1->_adyacente_en($n3, 'compartido');
    $n2->_adyacente_en($n3, 'compartido');
});

// --- 2. Guardar y cargar completo ---
$res['guardar'] = Controlador::guardar($nombre_test);
$res['cargar'] = Controlador::cargar($nombre_test);

// --- 3. Verificar máscaras ---
// n3 es alcanzable desde ctx_a Y desde ctx_b, entonces
// su máscara debe ser 3 (bits 0 y 1).
Controlador::ejecutar_prueba(function($token) use (&$res) {
    $raiz_a = Nodo::nodo_por_id('ctx_a');
    $raiz_b = Nodo::nodo_por_id('ctx_b');
    $n1 = $raiz_a ? $raiz_a->adyacente('hijo') : null;
    $n2 = $raiz_b ? $raiz_b->adyacente('hijo') : null;
    $n3 = $n1 ? $n1->adyacente('compartido') : null;
    $res['mascara_ctx_a'] = $raiz_a ? $raiz_a->contexto_mascara() : 'NO';
    $res['mascara_ctx_b'] = $raiz_b ? $raiz_b->contexto_mascara() : 'NO';
    $res['mascara_n1'] = $n1 ? $n1->contexto_mascara() : 'NO';
    $res['mascara_n2'] = $n2 ? $n2->contexto_mascara() : 'NO';
    $res['mascara_n3'] = $n3 ? $n3->contexto_mascara() : 'NO';
});

// --- 4. Listar contextos ---
$res['contextos'] = Controlador::listar_contextos($nombre_test);

// --- 5. Cargar parcial por ctx_a ---
$res['cargar_parcial'] = Controlador::cargar_parcial($nombre_test, ['ctx_a']);
$res['es_parcial'] = Controlador::es_grafo_parcial();

Controlador::ejecutar_prueba(function($token) use (&$res) {
    $raiz_a = Nodo::nodo_por_id('ctx_a');
    $n1 = $raiz_a ? $raiz_a->adyacente('hijo') : null;
    $n3 = $n1 ? $n1->adyacente('compartido') : null;
    $res['tiene_ctx_a_tras_parcial'] = Nodo::existe('ctx_a');
    $res['tiene_ctx_b_tras_parcial'] = Nodo::existe('ctx_b');
    $res['tiene_n1_tras_parcial'] = ($n1 !== null);
    $res['tiene_n3_tras_parcial'] = ($n3 !== null);
});

// --- 6. Guardar completo sobre parcial (debe fallar) ---
$res['guardar_sobre_parcial'] = Controlador::guardar($nombre_test);

// --- 7. Limpieza ---
$res['eliminar'] = Controlador::eliminar($nombre_test);

echo "--- Resultados ---\n";
foreach ($res as $k => $v) {
    echo $k . " => " . var_export($v, true) . "\n";
}
echo "\n--- Fin ---\n";
?>