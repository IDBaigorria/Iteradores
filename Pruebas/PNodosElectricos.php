<?php
// ============================================================
// TEST DE NodoElectrico (PHP)
// ============================================================
// Estado de implementación:
// ✅ = implementado y probado
// ⏳ = pendiente / incompleto
// ❌ = no implementado
// ============================================================

require_once "Nodos/NodoElectrico.php";
require_once "Nodos/Nodo.php";
require_once "Controlador/Controlador.php";
require_once "Configuracion/Entorno.php";
require_once "Configuracion/Configuracion.php";

use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Configuracion\Conf;

// Forzar modo desarrollo si no está definido
if (!Entorno::es_desarrollo()) {
    echo "⚠️ Las pruebas deberían ejecutarse en entorno DESARROLLO<br>";
}

echo "🚀 Inicio de pruebas para NodoElectrico (PHP)<br>";
/*
// ──────────────────────────────────────────────────────────
// 1. PRUEBAS EXHAUSTIVAS DE LA INTERFAZ FASE
// ──────────────────────────────────────────────────────────
echo "<br>🔹 INTERFAZ FASE (NodoElectrico)<br>";

Controlador::ejecutar_prueba(function($token) {
    // 1.1 Establecer fase con token válido
    echo "▶ 1.1 Establecer fase con token válido<br>";
    NodoElectrico::_fase($token, 'fase_alpha');
    echo "   Fase actual (esperada 'fase_alpha'): " . NodoElectrico::fase() . "<br>";

    // 1.2 Establecer fase con token inválido (debe mostrar alerta/error)
    echo "▶ 1.2 Establecer fase con token inválido<br>";
    NodoElectrico::_fase('token_invalido', 'fase_beta');
    echo "   Fase actual (debe seguir siendo 'fase_alpha'): " . NodoElectrico::fase() . "<br>";

    // 1.3 Crear nodos y agregar actividad en diferentes fases
    echo "▶ 1.3 Preparar nodos con actividad en múltiples fases<br>";
    $nodo1 = NodoElectrico::crear_con_dato('Nodo 1');
    $nodo2 = NodoElectrico::crear_con_dato('Nodo 2');

    // Cambiar a fase 'gamma' y agregar adyacencia
    NodoElectrico::_fase($token, 'fase_gamma');
    $nodo1->_adyacente_en($nodo2, 'enlace_gamma');

    // Cambiar a fase 'delta' y agregar incidente (mediante adyacencia inversa)
    NodoElectrico::_fase($token, 'fase_delta');
    $nodo2->_adyacente_en($nodo1, 'enlace_delta');  // $nodo1 ahora tiene un incidente en fase delta

    // Volver a fase alpha (sin actividad en nodos)
    NodoElectrico::_fase($token, 'fase_alpha');

    // 1.4 Método de instancia: por_cada_fase_ejecutar (solo fases con actividad en el nodo)
    echo "▶ 1.4 por_cada_fase_ejecutar en nodo1 (debe mostrar 'fase_gamma' y 'fase_delta')<br>";
    $fases_nodo1 = [];
    $nodo1->por_cada_fase_ejecutar($token, function($fase) use (&$fases_nodo1) {
        echo "   Nodo1 actividad en fase: $fase<br>";
        $fases_nodo1[] = $fase;
    });
    echo "   Fases encontradas: " . implode(', ', $fases_nodo1) . "<br>";

    echo "▶ 1.5 por_cada_fase_ejecutar en nodo2 (debe mostrar 'fase_gamma' y 'fase_delta')<br>";
    $fases_nodo2 = [];
    $nodo2->por_cada_fase_ejecutar($token, function($fase) use (&$fases_nodo2) {
        echo "   Nodo2 actividad en fase: $fase<br>";
        $fases_nodo2[] = $fase;
    });

    // 1.6 Método estático: por_cada_fase_global_ejecutar (todas las fases registradas)
    echo "▶ 1.6 por_cada_fase_global_ejecutar (debe listar: fase_alpha, fase_gamma, fase_delta)<br>";
    $fases_globales = [];
    NodoElectrico::por_cada_fase_global_ejecutar($token, function($fase) use (&$fases_globales) {
        echo "   Fase global: $fase<br>";
        $fases_globales[] = $fase;
    });

    // 1.7 Probar método estático con token inválido (debe fallar silenciosamente o mostrar alerta)
    echo "▶ 1.7 por_cada_fase_global_ejecutar con token inválido (debe no listar fases)<br>";
    NodoElectrico::por_cada_fase_global_ejecutar('token_malo', function($fase) {
        echo "   Esto no debería ejecutarse: $fase<br>";
    });
    echo "   (Si no se ve el mensaje anterior, funcionó correctamente)<br>";

    // 1.8 Probar método de instancia en nodo sin actividad
    echo "▶ 1.8 Nodo sin actividad (recién creado)<br>";
    $nodo_solo = NodoElectrico::crear();
    $nodo_solo->por_cada_fase_ejecutar($token, function($fase) {
        echo "   Nodo solo NO debería tener fases, pero apareció: $fase<br>";
    });
    echo "   (Si no se ve ningún listado, correcto)<br>";
});*/

// ──────────────────────────────────────────────────────────
// 2. PRUEBAS EXHAUSTIVAS DE FÁBRICA DE NODOS ELÉCTRICOS
// ──────────────────────────────────────────────────────────
echo "\n🔹 Fábrica de Nodos Eléctricos<br>";

// 2.1 Creación básica
echo "▶ 2.1 Creación básica<br>";
$nodoVacio = NodoElectrico::crear();
echo "   crear() -> id: {$nodoVacio->id()}, dato: {$nodoVacio->dato()}<br>";

$nodoConCapacidad = NodoElectrico::crear(500, 0.3);
echo "   crear(500, 0.3) -> capacidad: {$nodoConCapacidad->capacidad()}, fuga: {$nodoConCapacidad->fuga()}<br>";

// 2.2 Crear con dato (con y sin capacidad/fuga)
echo "▶ 2.2 crear_con_dato<br>";
$nodoConDato = NodoElectrico::crear_con_dato("Hola PHP");
echo "   crear_con_dato(\"Hola PHP\") -> id: {$nodoConDato->id()}, dato: {$nodoConDato->dato()}<br>";

$nodoConDatoYCapacidad = NodoElectrico::crear_con_dato("Sensor", false, 1000, 0.5);
echo "   crear_con_dato(\"Sensor\", false, 1000, 0.5) -> capacidad: {$nodoConDatoYCapacidad->capacidad()}, fuga: {$nodoConDatoYCapacidad->fuga()}<br>";

// 2.3 Crear con ID especial
echo "▶ 2.3 crear_con_id<br>";
$nodoConIdValido = NodoElectrico::crear_con_id("especial_php");
echo "   crear_con_id(\"especial_php\") -> id: {$nodoConIdValido->id()}, es_especial: " . ($nodoConIdValido->es_especial() ? 'si' : 'no') . "<br>";

$nodoConIdInvalido = NodoElectrico::crear_con_id(5465); // No cumple es_id_especial
echo "   crear_con_id(\"no_especial\") (debe fallar) -> " . ($nodoConIdInvalido === null ? "null (correcto)" : "ERROR: debería ser null") . "<br>";

// 2.4 Crear con dato e ID especial
echo "▶ 2.4 crear_con_dato_e_id<br>";
$nodoCompleto = NodoElectrico::crear_con_dato_e_id("Dato especial", "id_compuesto");
echo "   crear_con_dato_e_id() -> id: {$nodoCompleto->id()}, dato: {$nodoCompleto->dato()}<br>";

$nodoCompletoInvalido = NodoElectrico::crear_con_dato_e_id("Dato", 342);
echo "   crear_con_dato_e_id con ID inválido -> " . ($nodoCompletoInvalido === null ? "null (correcto)" : "ERROR: debería ser null") . "<br>";

// 2.5 Método nodo() (con diferentes entradas)
echo "▶ 2.5 nodo()<br>";
$nodo0 = NodoElectrico::nodo();
echo "   nodo() sin params -> id: {$nodo0->id()}, dato: {$nodo0->dato()}<br>";

$esNodo = null;
$nodo1 = NodoElectrico::nodo("Texto", $esNodo);
echo "   nodo(\"Texto\", \$esNodo) -> esNodo: " . ($esNodo ? 'true' : 'false') . ", id: {$nodo1->id()}<br>";

$esNodo2 = null;
$nodo2 = NodoElectrico::nodo($nodo1, $esNodo2);
echo "   nodo(\$nodo1, \$esNodo) -> esNodo: " . ($esNodo2 ? 'true' : 'false') . ", id: {$nodo2->id()} (debe coincidir con nodo1)<br>";

$nodoNull = NodoElectrico::nodo(null);
echo "   nodo(null) -> dato: " . var_export($nodoNull->dato(), true) . " (debería ser null)<br>";

// 2.6 Capacidad y fuga por defecto vs personalizada
echo "▶ 2.6 Verificar capacidad y fuga por defecto<br>";
$nodoDefault = NodoElectrico::crear();
echo "   capacidad por defecto: {$nodoDefault->capacidad()} (esperado: " . Conf::CAPACIDAD_NODO_ELECTRICO . ")<br>";
echo "   fuga por defecto: {$nodoDefault->fuga()} (esperado: " . Conf::FUGA_NODO_ELECTRICO . ")<br>";

// 2.7 Conteo de nodos y superestructura
echo "▶ 2.7 Conteo de nodos y superestructura<br>";
$cantidadAntes = NodoElectrico::cantidad_de_nodos();
echo "   cantidad_de_nodos() antes de crear más: $cantidadAntes<br>";
$tempNode = NodoElectrico::crear();
echo "   después de crear 1 nodo más: " . NodoElectrico::cantidad_de_nodos() . " (debe ser " . ($cantidadAntes + 1) . ")<br>";
NodoElectrico::eliminar($tempNode);
echo "   después de eliminarlo: " . NodoElectrico::cantidad_de_nodos() . " (debe volver a $cantidadAntes)<br>";

// 2.8 Prueba de eliminación (nodo sin referencias)
echo "▶ 2.8 Eliminar nodo sin referencias<br>";
$nodoEliminar = NodoElectrico::crear_con_dato("Para eliminar");
$idEliminar = $nodoEliminar->id();
echo "   Nodo creado, id: $idEliminar<br>";
$resultadoEliminar = NodoElectrico::eliminar($nodoEliminar);
echo "   eliminar() -> " . ($resultadoEliminar === true ? "true (correcto)" : "ERROR") . "<br>";
echo "   ¿Sigue en superestructura? " . (NodoElectrico::existe($idEliminar) ? "SÍ (error)" : "NO (correcto)") . "<br>";

// 2.9 Eliminar nodo con referencias (debe fallar)
echo "▶ 2.9 Eliminar nodo con referencias<br>";
$nodoA = NodoElectrico::crear_con_dato('A');
$nodoB = NodoElectrico::crear_con_dato('B');
$nodoA->_adyacente_en($nodoB, 'enlaceAB');
$resultadoEliminarConRef = NodoElectrico::eliminar($nodoB);
echo "   eliminar(nodoB) (tiene incidente desde nodoA) -> " . ($resultadoEliminarConRef === false ? "false (correcto)" : "ERROR") . "<br>";
// Limpiar para no afectar otras pruebas
$nodoA->eliminar_adyacente('enlaceAB');

// 2.10 eliminar_autoenlazado (obsoleto, pero se prueba)
echo "▶ 2.10 eliminar_autoenlazado<br>";
$nodoAuto = NodoElectrico::crear_con_dato("Autoenlazado");
$nodoAuto->_adyacente($nodoAuto); // autoenlace
echo "   Nodo con autoenlace, referencias: {$nodoAuto->cantidad_de_incidentes_global()}<br>";
$resAuto = NodoElectrico::eliminar_autoenlazado($nodoAuto);
echo "   eliminar_autoenlazado() -> " . ($resAuto === true ? "true (correcto)" : "ERROR") . "<br>";
echo "   ¿Sigue en superestructura? " . (NodoElectrico::existe($idEliminar) ? "SÍ (error)" : "NO (correcto)") . "<br>";

echo "Cantidad final de nodos: " . NodoElectrico::cantidad_de_nodos() . "<br>";

// 2.11 Probar getters globales de adyacentes/incidentes
echo "▶ 2.11 cantidad_de_adyacentes_global y cantidad_de_incidentes_global<br>";
$nodoGlobal = NodoElectrico::crear();
$aux1 = NodoElectrico::crear();
$aux2 = NodoElectrico::crear();
Controlador::ejecutar_prueba(function($token) use ($nodoGlobal, $aux1, $aux2) {
    NodoElectrico::_fase($token, 'faseX');
    $nodoGlobal->_adyacente_en($aux1, 'x');        // aux1 recibe un incidente
    NodoElectrico::_fase($token, 'faseY');
    $nodoGlobal->_adyacente_en($aux2, 'y');        // aux2 recibe un incidente
});
echo "   adyacentes global de nodoGlobal (debe ser 2): " . $nodoGlobal->cantidad_de_adyacentes_global() . "<br>";
echo "   adyacentes fase actual de nodoGlobal (debe ser 1, faseY): " . $nodoGlobal->cantidad_de_adyacentes() . "<br>";
echo "   incidentes de aux1 (debe ser 0, por enlace 'x'): " . $aux1->cantidad_de_incidentes() . "<br>";
echo "   incidentes de aux2 (debe ser 1, por enlace 'y'): " . $aux2->cantidad_de_incidentes() . "<br>";
echo "   incidentes global de aux1 (debe ser 1, por enlace 'x'): " . $aux1->cantidad_de_incidentes_global() . "<br>";
echo "   incidentes global de aux2 (debe ser 1, por enlace 'y'): " . $aux2->cantidad_de_incidentes_global() . "<br>";
/*
// ──────────────────────────────────────────────────────────
// 3. ADYACENTES
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Adyacentes<br>";
$nodo_a = NodoElectrico::crear_con_dato('A');
$nodo_b = NodoElectrico::crear_con_dato('B');

$enlace_auto = $nodo_a->_adyacente($nodo_b);
echo "_adyacente() -> enlace generado: $enlace_auto<br>";

$ok = $nodo_a->_adyacente_en($nodo_b, 'enlace_fijo', true);
echo "_adyacente_en() -> éxito: " . ($ok ? 'true' : 'false') . "<br>";

$obtenido = $nodo_a->adyacente('enlace_fijo');
echo "adyacente('enlace_fijo') -> dato: " . ($obtenido ? $obtenido->dato() : 'null') . "<br>";

$copia_ady = $nodo_a->adyacentes();
echo "adyacentes() -> tamaño: " . (is_array($copia_ady) ? count($copia_ady) : 0) . "<br>";
echo "cantidad_de_adyacentes(): " . $nodo_a->cantidad_de_adyacentes() . "<br>";
echo "tiene_adyacente(): " . ($nodo_a->tiene_adyacente() ? 'true' : 'false') . "<br>";

$enlace_encontrado = $nodo_a->tiene_adyacente_a($nodo_b);
echo "tiene_adyacente_a(nodo_b) -> enlace: " . ($enlace_encontrado ?: 'false') . "<br>";

$eliminado_ady = $nodo_a->eliminar_adyacente('enlace_fijo');
echo "eliminar_adyacente() -> nodo eliminado id: " . ($eliminado_ady ? $eliminado_ady->id() : 'null') . "<br>";

$todos_ady = $nodo_a->eliminar_adyacentes();
echo "eliminar_adyacentes() -> cantidad eliminada: " . count($todos_ady) . "<br>";

$resultados_ady = $nodo_a->por_cada_adyacente_ejecutar(function($n, $e) { return $n->dato(); });
echo "por_cada_adyacente_ejecutar() -> resultados: " . print_r($resultados_ady, true) . "<br>";

// ──────────────────────────────────────────────────────────
// 4. INCIDENTES
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Incidentes<br>";
echo "tiene_incidente() (antes): " . ($nodo_b->tiene_incidente() ? 'true' : 'false') . "<br>";
$nodo_a->_adyacente_en($nodo_b, 'prueba');
echo "tiene_incidente() (después): " . ($nodo_b->tiene_incidente() ? 'true' : 'false') . "<br>";

$incidente_enlace = $nodo_b->tiene_incidente_a($nodo_a);
echo "tiene_incidente_a(nodo_a) -> enlace: " . ($incidente_enlace ?: 'false') . "<br>";

$incidentes_map = $nodo_b->incidentes();
echo "incidentes() -> estructura: " . print_r($incidentes_map, true) . "<br>";
echo "cantidad_de_incidentes(): " . $nodo_b->cantidad_de_incidentes() . "<br>";

$resultados_inc = $nodo_b->por_cada_incidente_ejecutar(function($n, $e) { return $n->id(); });
echo "por_cada_incidente_ejecutar() -> resultados: " . print_r($resultados_inc, true) . "<br>";

// ──────────────────────────────────────────────────────────
// 5. ENERGÍA (PENDIENTE)
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Energía ❌ FALTA IMPLEMENTAR<br>";
echo "❌ _energia(cantidad)<br>";
echo "❌ energia()<br>";
echo "❌ _ejecutar_cuando_satura() / ejecutar_cuando_satura()<br>";
echo "❌ _ejecutar_cuando_agota() / ejecutar_cuando_agota()<br>";
echo "❌ métodos estáticos *_por_fase<br>";

// ──────────────────────────────────────────────────────────
// 6. ELIMINACIÓN DE NODOS
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Eliminación de nodos<br>";
$nodo_eliminar = NodoElectrico::crear_con_dato('Eliminame');
echo "Nodo creado, id: {$nodo_eliminar->id()}<br>";
$eliminado_ok = NodoElectrico::eliminar($nodo_eliminar);
echo "eliminar() -> resultado: " . ($eliminado_ok ? 'true' : 'false') . "<br>";
echo "Cantidad de nodos después: " . NodoElectrico::cantidad_de_nodos() . "<br>";

// ──────────────────────────────────────────────────────────
// 7. IMPRESIÓN
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Impresión<br>";
$nodo_print = NodoElectrico::crear_con_dato('Para imprimir');
$nodo_print->_adyacente_en(NodoElectrico::crear_con_dato('Hijo'), 'hijo');
echo "imprimir2() en consola:<br>";
$nodo_print->imprimir2();

// ──────────────────────────────────────────────────────────
// 8. PERSISTENCIA (Controlador)
// ──────────────────────────────────────────────────────────
function probar_persistencia() {
    echo "<br>🔹 Persistencia<br>";
    $nombre_db = 'test_nodos_electricos_php';
    Controlador::eliminar($nombre_db);

    $n1 = NodoElectrico::crear_con_dato('N1');
    $n2 = NodoElectrico::crear_con_dato('N2');
    $n1->_adyacente_en($n2, 'enlace');

    echo "Guardando...<br>";
    $guardado = Controlador::guardar($nombre_db);
    echo "Guardado: " . ($guardado ? 'true' : 'false') . "<br>";

    echo "Verificando existencia...<br>";
    $existe = Controlador::existe($nombre_db);
    echo "Existe: " . ($existe ? 'true' : 'false') . "<br>";

    echo "Cargando...<br>";
    $cargado = Controlador::cargar($nombre_db);
    echo "Cargado: " . ($cargado ? 'true' : 'false') . "<br>";

    echo "Eliminando de DB...<br>";
    $eliminado_db = Controlador::eliminar($nombre_db);
    echo "Eliminado: " . ($eliminado_db ? 'true' : 'false') . "<br>";
}
probar_persistencia();

echo "<br>🏁 Fin de las pruebas PHP<br>";
*/