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
require_once "Controlador/Controlador.php";
require_once "Configuracion/Entorno.php";

use Iteradores\Nodos\NodoElectrico;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;

// Forzar modo desarrollo si no está definido
if (!Entorno::es_desarrollo()) {
    echo "⚠️ Las pruebas deberían ejecutarse en entorno DESARROLLO<br>";
}

echo "🚀 Inicio de pruebas para NodoElectrico (PHP)<br>";

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
});
/*
// ──────────────────────────────────────────────────────────
// 2. FÁBRICA DE NODOS ELÉCTRICOS
// ──────────────────────────────────────────────────────────
echo "<br>🔹 Fábrica de Nodos Eléctricos<br>";
$nodo_vacio = NodoElectrico::crear();
echo "crear() -> id: {$nodo_vacio->id()}, dato: {$nodo_vacio->dato()}<br>";

$nodo_con_dato = NodoElectrico::crear_con_dato("Hola PHP");
echo "crear_con_dato() -> id: {$nodo_con_dato->id()}, dato: {$nodo_con_dato->dato()}<br>";

$nodo_con_id = NodoElectrico::crear_con_id("especial_php");
echo "crear_con_id() -> id: {$nodo_con_id->id()}, es_especial: " . ($nodo_con_id->es_especial() ? 'si' : 'no') . "<br>";

$nodo_completo = NodoElectrico::crear_con_dato_e_id("Dato especial", "id_compuesto");
echo "crear_con_dato_e_id() -> id: {$nodo_completo->id()}, dato: {$nodo_completo->dato()}<br>";

$nodo0 = NodoElectrico::nodo();
echo "nodo() sin params -> id: {$nodo0->id()}, dato: {$nodo0->dato()}<br>";

$es_nodo = null;
$nodo1 = NodoElectrico::nodo("Texto", $es_nodo);
echo "nodo() con referencia -> es_nodo: " . ($es_nodo ? 'true' : 'false') . ", id: {$nodo1->id()}<br>";

$es_nodo2 = null;
$nodo2 = NodoElectrico::nodo($nodo1, $es_nodo2);
echo "nodo() reutilizando nodo -> es_nodo: " . ($es_nodo2 ? 'true' : 'false') . ", id: {$nodo2->id()}<br>";

echo "Cantidad de nodos: " . NodoElectrico::cantidad_de_nodos() . "<br>";

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