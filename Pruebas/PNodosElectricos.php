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
/*
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
*/
// ──────────────────────────────────────────────────────────
// 3. PRUEBAS EXHAUSTIVAS DE ADYACENTES
// ──────────────────────────────────────────────────────────
echo "\n🔹 Adyacentes<br>";

// 3.1 Preparación
$nodoA = NodoElectrico::crear_con_dato('A');
$nodoB = NodoElectrico::crear_con_dato('B');
$nodoC = NodoElectrico::crear_con_dato('C');

echo "▶ 3.1 _adyacente() (generación automática de nombre)<br>";
$enlace1 = $nodoA->_adyacente($nodoB);
echo "   Enlace generado (basado en id de B): $enlace1<br>";
$enlace2 = $nodoA->_adyacente($nodoB); // segundo enlace, debe numerarse
echo "   Segundo enlace: $enlace2 (debe ser algo como '{$nodoB->id()}.1')<br>";

echo "▶ 3.2 _adyacente_en() con nombre fijo y reemplazo<br>";
$ok = $nodoA->_adyacente_en($nodoB, 'fijo', true);
echo "   Asignación exitosa: " . ($ok ? 'true' : 'false') . "<br>";
$ok2 = $nodoA->_adyacente_en($nodoC, 'fijo', false);
echo "   Intento de reasignar 'fijo' sin reemplazar: " . ($ok2 ? 'true (error)' : 'false (correcto)') . "<br>";

echo "▶ 3.3 adyacente() obtener nodo por nombre de enlace<br>";
$nodoObtenido = $nodoA->adyacente('fijo');
echo "   Nodo en 'fijo': " . ($nodoObtenido ? $nodoObtenido->id() : 'null') . " (debe ser B)<br>";
$nodoInexistente = $nodoA->adyacente('noexiste');
echo "   Enlace inexistente: " . ($nodoInexistente === null ? 'null (correcto)' : 'error') . "<br>";

echo "▶ 3.4 adyacentes() y cantidad_de_adyacentes()<br>";
$todos = $nodoA->adyacentes();
if ($todos === null) {
    echo "   adyacentes() devuelve null (no hay adyacentes en la fase actual)<br>";
} else {
    echo "   adyacentes() devuelve array con " . count($todos) . " elementos<br>";
}
echo "   cantidad_de_adyacentes() (fase actual): " . $nodoA->cantidad_de_adyacentes() . "<br>";

echo "▶ 3.5 tiene_adyacente() y tiene_adyacente_a()<br>";
echo "   tiene_adyacente(): " . ($nodoA->tiene_adyacente() ? 'true' : 'false') . "<br>";
$nombreEnlace = $nodoA->tiene_adyacente_a($nodoB);
echo "   tiene_adyacente_a(nodoB) devuelve nombre: " . ($nombreEnlace !== false ? "'$nombreEnlace'" : 'false') . "<br>";
$falso = $nodoA->tiene_adyacente_a($nodoC);
echo "   tiene_adyacente_a(nodoC) (no existe): " . ($falso === false ? 'false (correcto)' : 'error') . "<br>";

echo "▶ 3.6 eliminar_adyacente()<br>";
$eliminado = $nodoA->eliminar_adyacente('fijo');
echo "   Nodo eliminado: " . ($eliminado ? $eliminado->id() : 'null') . " (debe ser B)<br>";
$eliminadoInex = $nodoA->eliminar_adyacente('fijo');
echo "   Eliminar otra vez: " . ($eliminadoInex === null ? 'null (correcto)' : 'error') . "<br>";

echo "▶ 3.7 eliminar_adyacentes()<br>";
$nodoA->_adyacente_en($nodoB, 'temp1');
$nodoA->_adyacente_en($nodoC, 'temp2');
$eliminados = $nodoA->eliminar_adyacentes();
echo "   Eliminados: " . count($eliminados) . " nodos (deben ser 2)<br>";

echo "▶ 3.8 por_cada_adyacente_ejecutar()<br>";
$nodoA->_adyacente($nodoB);
$nodoA->_adyacente($nodoC);
$resultados = $nodoA->por_cada_adyacente_ejecutar(function($n, $e) { return $n->id(); });
echo "   Resultados: " . print_r($resultados, true) . "<br>";

echo "▶ 3.9 Adyacentes con múltiples fases<br>";
Controlador::ejecutar_prueba(function($token) use ($nodoA, $nodoB) {
    NodoElectrico::_fase($token, 'faseX');
    $nodoA->_adyacente_en($nodoB, 'enlaceX');
    NodoElectrico::_fase($token, 'faseY');
    $nodoA->_adyacente_en($nodoB, 'enlaceY');
});
echo "   cantidad_de_adyacentes() (fase actual, debe ser 1): " . $nodoA->cantidad_de_adyacentes() . "<br>";
echo "   cantidad_de_adyacentes_global() (debe ser 2): " . $nodoA->cantidad_de_adyacentes_global() . "<br>";
echo "   tiene_adyacente_a(nodoB) en fase actual (debe devolver 'enlaceY'): " . ($nodoA->tiene_adyacente_a($nodoB) ?: 'false') . "<br>";

// ──────────────────────────────────────────────────────────
// 4. PRUEBAS EXHAUSTIVAS DE INCIDENTES
// ──────────────────────────────────────────────────────────
echo "\n🔹 Incidentes<br>";

// 4.1 Preparación (usando los nodos anteriores limpios)
$nodoX = NodoElectrico::crear_con_dato('X');
$nodoY = NodoElectrico::crear_con_dato('Y');
$nodoZ = NodoElectrico::crear_con_dato('Z');

echo "▶ 4.1 Creación de incidentes mediante _adyacente_en()<br>";
$nodoX->_adyacente_en($nodoY, 'incidente1');  // nodoY recibe incidente desde X
$nodoX->_adyacente_en($nodoY, 'incidente2');  // segundo enlace desde X hacia Y
$nodoZ->_adyacente_en($nodoY, 'incidenteZ');  // desde Z hacia Y
echo "   NodoY tiene incidentes desde X (2 enlaces) y desde Z (1 enlace)<br>";

echo "▶ 4.2 tiene_incidente()<br>";
echo "   tiene_incidente() en nodoY: " . ($nodoY->tiene_incidente() ? 'true' : 'false') . "<br>";
echo "   tiene_incidente() en nodoX (sin incidentes): " . ($nodoX->tiene_incidente() ? 'true' : 'false') . "<br>";

echo "▶ 4.3 tiene_incidente_a() debe devolver nombre del enlace<br>";
$enlaceDesdeX = $nodoY->tiene_incidente_a($nodoX);
echo "   Incidente desde X hacia Y (primer enlace encontrado): " . ($enlaceDesdeX !== false ? "'$enlaceDesdeX'" : 'false') . "<br>";
$enlaceDesdeZ = $nodoY->tiene_incidente_a($nodoZ);
echo "   Incidente desde Z: " . ($enlaceDesdeZ !== false ? "'$enlaceDesdeZ'" : 'false') . "<br>";
$falsoInc = $nodoY->tiene_incidente_a($nodoY);
echo "   Incidente inexistente: " . ($falsoInc === false ? 'false (correcto)' : 'error') . "<br>";

echo "▶ 4.4 incidentes() estructura<br>";
$incidentes = $nodoY->incidentes();
if ($incidentes === null) {
    echo "   incidentes() devuelve null (no hay incidentes en la fase actual)<br>";
} else {
    echo "   incidentes() devuelve un array con " . count($incidentes) . " entradas (por cada nodo origen)<br>";
    foreach ($incidentes as $idOrigen => $enlaces) {
        echo "     Origen: $idOrigen<br>";
        // $enlaces es un array asociativo [nombre_enlace => Nodo]
        $listaEnlaces = array_keys($enlaces);
        echo "       Enlaces: " . implode(', ', $listaEnlaces) . "<br>";
    }
}

echo "▶ 4.5 cantidad_de_incidentes() y cantidad_de_incidentes_global()<br>";
echo "   cantidad_de_incidentes() (fase actual): " . $nodoY->cantidad_de_incidentes() . "<br>";
// Forzar múltiples fases
Controlador::ejecutar_prueba(function($token) use ($nodoY, $nodoX) {
    NodoElectrico::_fase($token, 'faseAlpha');
    $nodoX->_adyacente_en($nodoY, 'alfa');
    NodoElectrico::_fase($token, 'faseBeta');
    $nodoX->_adyacente_en($nodoY, 'beta');
});
echo "   Después de añadir incidentes en otras fases:<br>";
echo "      cantidad_de_incidentes() (fase actual Beta, debe ser 1): " . $nodoY->cantidad_de_incidentes() . "<br>";
echo "      cantidad_de_incidentes_global() (debe sumar todos): " . $nodoY->cantidad_de_incidentes_global() . "<br>";

echo "▶ 4.6 por_cada_incidente_ejecutar()<br>";
$resultadosInc = $nodoY->por_cada_incidente_ejecutar(function($nodoOrigen, $enlace) {
    return "{$nodoOrigen->id()}->$enlace";
});
echo "   Resultados: " . print_r($resultadosInc, true) . "<br>";
/*
// ──────────────────────────────────────────────────────────
// 5. PRUEBAS EXHAUSTIVAS DE ENERGÍA
// ──────────────────────────────────────────────────────────
echo "\n🔹 Energía<br>";

// 5.1 Getters básicos
echo "▶ 5.1 capacidad() y fuga()<br>";
$nodo = NodoElectrico::crear(100, 10);
echo "   capacidad: " . $nodo->capacidad() . " (debe ser 100)<br>";
echo "   fuga: " . $nodo->fuga() . " (debe ser 10)<br>";

// 5.2 Energía inicial y _energia()
echo "▶ 5.2 energia() inicial y _energia()<br>";
echo "   energia inicial: " . $nodo->energia() . " (debe ser 0)<br>";
$nodo->_energia(50);
echo "   después de _energia(50): " . $nodo->energia() . " (debe ser 50)<br>";
$nodo->_energia(60);
echo "   después de _energia(60): " . $nodo->energia() . " (debe ser 100, saturado)<br>";

// 5.3 Callbacks de saturación (instancia, reemplazar true/false)
echo "▶ 5.3 Callbacks de saturación<br>";
$saturado = false;
$fase_callback_ejecutado = false;

// Registrar callback por defecto en la fase actual
NodoElectrico::_ejecutar_cuando_satura_por_fase(function($n) use (&$fase_callback_ejecutado) {
    $fase_callback_ejecutado = true;
    echo "   [FASE] Callback por defecto de saturación ejecutado<br>";
});

// Probar con reemplazar = true (por defecto)
$nodo2 = NodoElectrico::crear(50, 0);
$nodo2->_ejecutar_cuando_satura(function($n) use (&$saturado) {
    $saturado = true;
    echo "   [INSTANCIA] Callback de saturación (reemplazar) ejecutado<br>";
});
$nodo2->_energia(60); // debe saturar
echo "   saturación con reemplazar: " . ($saturado ? "OK" : "FALLÓ") . "<br>";
echo "   callback de fase ejecutado? " . ($fase_callback_ejecutado ? "SÍ (error, no debió)" : "NO (correcto)") . "<br>";

// Reiniciar
$saturado = false;
$fase_callback_ejecutado = false;
$nodo3 = NodoElectrico::crear(50, 0);
$nodo3->_ejecutar_cuando_satura(function($n) use (&$saturado) {
    $saturado = true;
    echo "   [INSTANCIA] Callback de saturación (complementar) ejecutado<br>";
}, false); // complementar
$nodo3->_energia(60);
echo "   saturación con complementar: " . ($saturado ? "OK" : "FALLÓ") . "<br>";
echo "   callback de fase ejecutado? " . ($fase_callback_ejecutado ? "SÍ (correcto)" : "NO (error)") . "<br>";

// 5.4 Callbacks de agotamiento (instancia)
echo "▶ 5.4 Callbacks de agotamiento<br>";
$agotado = false;
$fase_agotado = false;

NodoElectrico::_ejecutar_cuando_agota_por_fase(function($n) use (&$fase_agotado) {
    $fase_agotado = true;
    echo "   [FASE] Callback por defecto de agotamiento ejecutado<br>";
});

$nodo4 = NodoElectrico::crear(30, 0);
$nodo4->_ejecutar_cuando_agota(function($n) use (&$agotado) {
    $agotado = true;
    echo "   [INSTANCIA] Callback de agotamiento (reemplazar) ejecutado<br>";
});
$nodo4->_energia(20);
$nodo4->_energia(-30);
echo "   agotamiento con reemplazar: " . ($agotado ? "OK" : "FALLÓ") . "<br>";
echo "   callback de fase ejecutado? " . ($fase_agotado ? "SÍ (error, no debió)" : "NO (correcto)") . "<br>";

// Complementar
$agotado = false;
$fase_agotado = false;
$nodo5 = NodoElectrico::crear(30, 0);
$nodo5->_ejecutar_cuando_agota(function($n) use (&$agotado) {
    $agotado = true;
    echo "   [INSTANCIA] Callback de agotamiento (complementar) ejecutado<br>";
}, false);
$nodo5->_energia(20);
$nodo5->_energia(-30);
echo "   agotamiento con complementar: " . ($agotado ? "OK" : "FALLÓ") . "<br>";
echo "   callback de fase ejecutado? " . ($fase_agotado ? "SÍ (correcto)" : "NO (error)") . "<br>";

// 5.5 Fuga por tiempo real
echo "▶ 5.5 Fuga por tiempo real<br>";
$nodo6 = NodoElectrico::crear(100, 5);
$nodo6->_energia(100);
echo "   energía inicial: " . $nodo6->energia() . "<br>";
sleep(2); // esperar 2 segundos (2 ciclos de 1 segundo)
$energia_despues = $nodo6->energia();
echo "   energía después de 2 segundos (2 ciclos, fuga 5*2=10): " . $energia_despues . " (debe ser 90)<br>";

// 5.6 Callback global de agotamiento (todas las fases)
echo "▶ 5.6 Callback global de agotamiento (todas las fases)<br>";
$global_agotado = false;
NodoElectrico::_ejecutar_cuando_agota_global(function($n) use (&$global_agotado) {
    $global_agotado = true;
    echo "   [GLOBAL] Todas las fases sin energía<br>";
});

$nodo7 = NodoElectrico::crear(50, 0);
Controlador::ejecutar_prueba(function($token) use ($nodo7) {
    $faseactual=NodoElectrico::fase();
    NodoElectrico::_fase($token, 'faseA');
    $nodo7->_energia(10);
    NodoElectrico::_fase($token, 'faseB');
    $nodo7->_energia(20);
    // Vaciar ambas fases
    NodoElectrico::_fase($token, 'faseA');
    $nodo7->_energia(-10);
    NodoElectrico::_fase($token, 'faseB');
    $nodo7->_energia(-20);
    NodoElectrico::_fase($token, $faseactual);
});
echo "   callback global " . ($global_agotado ? "ejecutado (OK)" : "NO ejecutado (ERROR)") . "<br>";

// 5.7 Obtener callbacks registrados
echo "▶ 5.7 Obtener callbacks registrados<br>";
$cb_sat = NodoElectrico::ejecutar_cuando_satura_por_fase();
echo "   callback saturación fase actual: " . ($cb_sat ? "registrado" : "ninguno") . "<br>";
$cb_agot = NodoElectrico::ejecutar_cuando_agota_por_fase();
echo "   callback agotamiento fase actual: " . ($cb_agot ? "registrado" : "ninguno") . "<br>";
$global_cb = NodoElectrico::ejecutar_cuando_agota_global();
echo "   callback global: " . ($global_cb ? "registrado" : "ninguno") . "<br>";

echo "✅ Pruebas de energía completadas<br>";
/*
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