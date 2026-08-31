<?php
/**
 * Pruebas exhaustivas para el manejo de árbol con nodos.
 * Este archivo se incluye temporalmente en index.php para ejecutar las pruebas.
 *
 * @package   Iteradores
 * @since     1.5piloto.14
 */

use Iteradores\Nodos\Nodo;

// Función para imprimir resultados de prueba
function probar($condicion, $descripcion) {
    if ($condicion) {
        echo "✔ $descripcion\n";
    } else {
        echo "✘ $descripcion\n";
        exit(1); // Detener si falla
    }
}

// Incluir funciones de árbol
require_once __DIR__ . '/Arbol.php';

echo "=== Pruebas de Árbol ===\n";

// Crear nodos
$A = Nodo::crear_con_dato('A');
$B = Nodo::crear_con_dato('B');
$C = Nodo::crear_con_dato('C');
$D = Nodo::crear_con_dato('D');
$E = Nodo::crear_con_dato('E');
$F = Nodo::crear_con_dato('F');
$G = Nodo::crear_con_dato('G'); // para pruebas adicionales
$H = Nodo::crear_con_dato('H');

// Construir árbol inicial:
// A -> B, C
// B -> D, E
// C -> F

_hmi($A, $B);
_hd($B, $C);

_hmi($B, $D);
_hd($D, $E);

_hmi($C, $F);

// Verificaciones iniciales
probar(hmi($A) === $B, "El hijo más izquierdo de A es B");
probar(hd($B) === $C, "El hermano derecho de B es C");
probar(hd($C) === null, "C no tiene hermano derecho");
probar(p($B) === $A, "El padre de B es A");
probar(p($C) === $A, "El padre de C es A");
probar(hmi($B) === $D, "El hijo más izquierdo de B es D");
probar(hd($D) === $E, "El hermano derecho de D es E");
probar(hd($E) === null, "E no tiene hermano derecho");
probar(hmi($C) === $F, "El hijo más izquierdo de C es F");
probar(p($F) === $C, "El padre de F es C");

// Prueba de inserción de nuevo hijo más izquierdo en A
_hmi($A, $G); // G debería ser nuevo hmi de A, y su hd debe ser B
probar(hmi($A) === $G, "Después de insertar G como hmi, hmi(A) es G");
probar(hd($G) === $B, "El hermano derecho de G es B");
probar(hd($B) === $C, "El hermano derecho de B sigue siendo C");
probar(p($G) === $A, "El padre de G es A");

// Prueba de inserción de hermano derecho en medio (entre B y C)
_hd($B, $H); // Insertar H entre B y C
probar(hd($B) === $H, "Después de insertar H, el hermano derecho de B es H");
probar(hd($H) === $C, "El hermano derecho de H es C");
probar(p($H) === $A, "El padre de H es A");
probar(p($C) === $A, "C sigue teniendo padre A");

// Verificar que la lista de hijos de A desde hmi es G, B, H, C
$hijo = hmi($A);
$secuencia = [];
while ($hijo) {
    $secuencia[] = $hijo->dato();
    $hijo = hd($hijo);
}
probar($secuencia === ['G', 'B', 'H', 'C'], "La secuencia de hijos de A es G, B, H, C");

// Prueba de que p() devuelve null para raíz
probar(p($A) === null, "El padre de A es null");

// ---- Pruebas de eliminación ----
// Estado actual después de las pruebas anteriores:
// A tiene como hmi a G, y lista: G -> B -> H -> C
// B tiene hmi D, hd(D)=E
// C tiene hmi F

$nodo_eliminado = eliminar_hmi($A); // debería eliminar G, y B pasa a ser hmi(A)
probar($nodo_eliminado === $G, "eliminar_hmi(A) devuelve G");
probar(hmi($A) === $B, "Después de eliminar hmi, hmi(A) es B");
probar(p($G) === null, "G ya no tiene padre");
probar(hd($G) === null, "G ya no tiene hermano derecho");

// Probar eliminar hermano derecho: eliminar H (hermano de B)
$nodo_eliminado2 = eliminar_hd($B); // debería eliminar H
probar($nodo_eliminado2 === $H, "eliminar_hd(B) devuelve H");
probar(hd($B) === $C, "Después de eliminar, hd(B) es C");
probar(p($H) === null, "H ya no tiene padre");
probar(hd($H) === null, "H ya no tiene hermano derecho");

// Verificar que A tiene hijos B, C
$hijo = hmi($A);
$secuencia = [];
while ($hijo) {
    $secuencia[] = $hijo->dato();
    $hijo = hd($hijo);
}
probar($secuencia === ['B', 'C'], "La secuencia de hijos de A es B, C");

// Eliminar hijo más izquierdo sin hermanos: eliminar hmi(B) (D)
$eliminado3 = eliminar_hmi($B); // elimina D
probar($eliminado3 === $D, "eliminar_hmi(B) devuelve D");
probar(hmi($B) === $E, "Después de eliminar, hmi(B) es E (porque D tenía hd E)");
probar(p($D) === null, "D ya no tiene padre");

// Eliminar hmi(C) para eliminar F (hijo más izquierdo de C)
$eliminado4 = eliminar_hmi($C); // elimina F
probar($eliminado4 === $F, "eliminar_hmi(C) devuelve F");
probar(hmi($C) === null, "C ya no tiene hijo");
probar(p($F) === null, "F ya no tiene padre");

// Verificar que A aún tiene B y C, y B tiene E
probar(hmi($A) === $B, "hmi(A) sigue siendo B");
probar(hd($B) === $C, "hd(B) sigue siendo C");
probar(hmi($B) === $E, "hmi(B) es E");

echo "Todas las pruebas pasaron correctamente.\n";